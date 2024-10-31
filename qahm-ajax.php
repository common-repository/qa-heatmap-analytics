<?php
/**
 * Created by PhpStorm.
 * User: maruyama
 * Date: 2022/08/27
 * Time: 16:01
 */

//return wp-setting.php at line141
define('SHORTINIT', true);
//IMPORTANT: Change with the correct path to wp-load.php in your installation
require '../../../wp-load.php';
require_once '../../../wp-settings.php';
require_once ABSPATH . WPINC . '/l10n.php';

wp_plugin_directory_constants();
$GLOBALS['wp_plugin_paths'] = array();
require_once ABSPATH . WPINC . '/link-template.php';

// wp functions
require_once ABSPATH . WPINC . '/http.php';
require_once ABSPATH . WPINC . '/pluggable.php';
require_once ABSPATH . WPINC . '/formatting.php';

// call qahm files
require_once 'qahm-const.php';
require_once 'class-qahm-base.php';
$qahm_base = new QAHM_Base;
$qahm_base->init_wp_filesystem(); //<-Needed!
require_once 'class-qahm-time.php';
require_once 'class-qahm-log.php';
require_once 'class-qahm-file-base.php';
require_once 'class-qahm-file-data.php';
require_once 'class-qahm-behavioral-data.php';

// work around for apache_request_headers
if( !function_exists('apache_request_headers') ) {
	function apache_request_headers() {
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if ( preg_match( $rx_http, $key ) ) {
			  $arh_key = preg_replace( $rx_http, '', $key );
			  $rx_matches = array();
			  $rx_matches = explode( '_', $arh_key );
			  if ( count($rx_matches) > 0 && strlen($arh_key) > 2 ) {
				foreach ( $rx_matches as $ak_key => $ak_val ) {
					$rx_matches[$ak_key] = ucfirst( $ak_val );
				}
				$arh_key = implode( '-', $rx_matches );
			  }
			  $arh[$arh_key] = $val;
			}
		}
		return( $arh );
	}
}

// qahm start
$behave         = new QAHM_Behavioral_Data;
$base           = new QAHM_Base;
$owndomain      = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
$allowed_origin = [ $owndomain ];
$req_headers    = apache_request_headers();
$req_origin     = '';
if ( array_key_exists( 'Origin', $req_headers ) ) {
	$req_origin = sanitize_text_field(wp_unslash($req_headers['Origin']));
}
if ( array_key_exists( 'ORIGIN', $req_headers ) ) {
	$req_origin = sanitize_text_field(wp_unslash($req_headers['ORIGIN']));
}
if ( array_key_exists( 'origin', $req_headers ) ) {
	$req_origin = sanitize_text_field(wp_unslash($req_headers['origin']));
}
$is_own_domain  = true;
$orgin          = '';
$action         = $base->wrap_filter_input( INPUT_POST, 'action' );

// allowed domain?
if ( ! empty($req_origin) ) {
    $is_allowed = false;
    foreach ( $allowed_origin as $domain ) {
        $host = wp_parse_url( $req_origin, PHP_URL_HOST );
        if ( $host === $domain ) {
            $orgin = $req_origin;
            if ($host === $owndomain ) {
                $is_own_domain = true;
            }
            $is_allowed = true;
            break;
        }
    }
    if ( ! $is_allowed ) {
        http_response_code(404);
        exit;
    }
}

// get ip address
$ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

if (isset($_SERVER['HTTP_X_READ_IP'])) {
    $ip_address = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_READ_IP']));
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_addresses = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
    $ip_address = trim($ip_addresses[0]);
}

//check tracking_hash
$tracking_hash  = $base->wrap_filter_input( INPUT_POST, 'tracking_hash' );
if ( ! $behave->check_tracking_hash( $tracking_hash ) ) {
    http_response_code(404);
    exit;
}

// ok, start behavioral data
$errmsg = '';

switch ( $action ) {
    case 'init_session_data':
        $wp_qa_type = $base->wrap_filter_input( INPUT_POST, 'wp_qa_type' );
        $wp_qa_id   = $base->wrap_filter_input( INPUT_POST, 'wp_qa_id' );
        $title      = $base->wrap_filter_input( INPUT_POST, 'title' );
        $url        = $base->wrap_filter_input( INPUT_POST, 'url' );
        $ref        = $base->wrap_filter_input( INPUT_POST, 'referrer' );
        $country    = $base->wrap_filter_input( INPUT_POST, 'country' );
        $is_new_user   = (int)$base->wrap_filter_input( INPUT_POST, 'is_new_user' );
        $is_reject     = $base->wrap_filter_input( INPUT_POST, 'is_reject' );
        $is_reject     = ( $is_reject === 'true' ) ? true : false;
        $ua         = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        $posted_qa_id = $base->wrap_filter_input( INPUT_POST, 'qa_id' );
        if ( empty( $posted_qa_id ) ) {
            $anontrack = $base->wrap_get_option( 'anontrack' );
            if( $anontrack == 1 ){
                $qa_id = $base->create_qa_id( $ip_address, $ua, $tracking_hash );
            }else{
                $qa_id = $base->create_qa_id( $ip_address, $ua, '' );
            }
        } else {
            $qa_id = $posted_qa_id;
        }

        if ( empty( $ref ) ) {
            $ref = 'direct';
        }
        $url = mb_strtolower( $url );
        $ref = mb_strtolower( $ref );

        //init
        $data = $behave->init_session_data( $qa_id, $wp_qa_type, $wp_qa_id, $title, $url, $ref, $country, $ua , $is_new_user, $is_reject ) ;
        return_json( $data, $is_own_domain, $orgin );
        break;

    case 'update_msec':
        $readers_name       = $base->wrap_filter_input( INPUT_POST, 'readers_name' );
        $readers_body_index = (int) $base->wrap_filter_input( INPUT_POST, 'readers_body_index' );
        $speed_msec         = (int) $base->wrap_filter_input( INPUT_POST, 'speed_msec' );
        $behave->update_msec( $readers_name, $readers_body_index, $speed_msec );
        break;

    case 'record_behavioral_data':
        if ( $behave->is_maintenance() ) {
            http_response_code(500);
            exit;
        }
        $is_pos   = $base->wrap_filter_input( INPUT_POST, 'is_pos' );
        $is_pos   = ( $is_pos === 'true' ) ? true : false;
        $is_click = $base->wrap_filter_input( INPUT_POST, 'is_click' );
        $is_click = ( $is_click === 'true' ) ? true : false;
        $is_event = $base->wrap_filter_input( INPUT_POST, 'is_event' );
        $is_event = ( $is_event === 'true' ) ? true : false;

        $raw_name       = $base->wrap_filter_input( INPUT_POST, 'raw_name' );
        $readers_name   = $base->wrap_filter_input( INPUT_POST, 'readers_name' );
        $type           = $base->wrap_filter_input( INPUT_POST, 'type' );
        $id             = $base->wrap_filter_input( INPUT_POST, 'id' );
        $ua             = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $is_reject = $base->wrap_filter_input( INPUT_POST, 'is_reject' );
        $is_reject = ( $is_reject === 'true' ) ? true : false;
        if( ! $ua ) {
            $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        }
        $output = $behave->record_behavioral_data( $is_pos, $is_click, $is_event, $raw_name, $readers_name, $type, $id, $ua, $is_reject );
        echo esc_html( $output );
        break;

    default:
        http_response_code(404);
        exit;
}

exit;

function return_json ( $data, $is_own_domain = true, $origin = '') {
    if ( ! $is_own_domain ) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    header("Content-Type: application/json; charset=utf-8");
    echo wp_json_encode($data);
    exit;
}

?>