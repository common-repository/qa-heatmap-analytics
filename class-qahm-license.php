<?php
/**
 *
 *
 * @package qa_heatmap
 */

//
// ライセンスに関するクラス
//

$qahm_license = new QAHM_License();

// ライセンスを管理するクラス
class QAHM_License extends QAHM_File_Base {

	const FREE_MEASURE_PAGE = 1;        // 無料ユーザーの最大計測ページ数

	// メッセージのレベル
	const MESSAGE_LEVEL = array(
		'success'       => 0,
		'info'          => 1,
		'warning'       => 2,
		'error'         => 3
	);

	// メッセージの通知領域
	const MESSAGE_VIEW = array(
		'admin'         => 0,				// 管理画面全体。こちらを選択した場合はqahmのプラグイン名も先頭に自動付与
		'license'       => 1,				// 管理画面のライセンス認証画面のみ
		'hidden'        => 2				// 非表示
	);

	// valuables
	static public $dom = '';

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'view_message' ) );

		$wp_url    = get_option('home');
		$parse_url = wp_parse_url( $wp_url );
		$ref_host  = $parse_url['host'];
		self::$dom = $ref_host;
		
		add_action( 'init', array( $this, 'init_wp_filesystem' ) );
	}

	/*
	 * ライセンスシステムのメッセージを管理画面に表示
	 */
	public function view_message() {
		$msg_ary  = $this->wrap_get_option( 'license_message' );
		if ( ! $msg_ary ) {
			return;
		}

		$plugin_name = QAHM_PLUGIN_NAME . ' : ';
		foreach ( $msg_ary as &$msg ) {
			$is_view = true;
			switch ( $msg['view'] ) {
				case self::MESSAGE_VIEW['admin']:
					break;

				case self::MESSAGE_VIEW['license']:
					require_once dirname( __FILE__ ) . '/class-qahm-admin-page-license.php';
					$page = $this->wrap_filter_input( INPUT_GET, 'page' );
					if ( QAHM_Admin_Page_License::SLUG !== $page ) {
						$is_view = false;
					}
					$plugin_name = '';
					break;
	
				case self::MESSAGE_VIEW['hidden']:
				default:
					$is_view = false;
					break;
			}
			if ( ! $is_view ) {
				continue;
			}

			//$class_level = '';
			$status = null;
			switch ( $msg['level'] ) {
				case self::MESSAGE_LEVEL['success']:
					if ( self::MESSAGE_VIEW['license'] !== $msg['view'] ) {
						continue 2;
					}
					//$class_level = 'notice-success ';
					$status = 'success';
					break;
				case self::MESSAGE_LEVEL['info']:
					//$class_level = 'notice-info ';
					$status = 'info';
					break;
				case self::MESSAGE_LEVEL['warning']:
					//$class_level = 'notice-warning ';
					$status = 'warning';
					break;
				case self::MESSAGE_LEVEL['error']:
					//$class_level = 'notice-error ';
					$status = 'error';
					break;
			}
			
			//echo '<div class="qahm-license-message notice ' . esc_attr( $class_level ) . 'is-dismissible" data-no="' . $msg['no'] . '">';
			//echo '<p>' . esc_html( $plugin_name . $msg['message'] ) .'</p>';
			//echo '</div>';

			// create_qa_announce_html用
			// 許可するHTMLタグと属性のリスト
			$qa_announce_allowed_tags = array(
				'div' => array(
					'class' => array(),
					'style' => array(),
				),
				'span' => array(
					'class' => array(),
					'style' => array(),
				),
				'p' => array(
					'class' => array(),
					'style' => array(),
				),
				'br' => array(),
				'blockquote' => array(),
				'ul' => array(),
				'ol' => array(),
				'li' => array(),
				'strong' => array(),
				'em' => array(),
				'b' => array(),
				'i' => array(),
				'a' => array(
					'href' => array(),
					'title' => array(),
					'target' => array(),
					'rel' => array(),
				),
				'img' => array(
					'src' => array(),
					'alt' => array(),
					'width' => array(),
					'height' => array(),
					'class' => array(),
				),
			);
						
			$qa_announce = $this->create_qa_announce_html( $msg['message'], $status );
			echo wp_kses( $qa_announce, $qa_announce_allowed_tags );
		}
	}

	/**
	 * プラグイン側で設定したいメッセージの出力 & ログの出力
	 * この関数はwp_remote_postで返ってきたjsonの中身（配列）を入れるのではない
	 * 引数によってjsonのメッセージと同じ形式のデータを作りlicese_messageに格納する
	 * この関数により作られたメッセージのメッセージナンバーは空とする。
	 */
	private function set_plugin_message ( $level, $msg, $view, $log = '' ) {
		$msg_ary = array(
			'no'      => '',
			'level'   => $level,
			'message' => $msg,
			'view'    => $view
		);
		$this->wrap_update_option( 'license_message', array( $msg_ary ) );
		
		if ( $log ) {
			global $qahm_log;
			$qahm_log->error( $log );
		}
	}

	/**
	 * jsonで返されたメッセージ配列をプラグイン用に最適化してreturn
	 */
	private function opt_json_message ( $json_msg_ary, $view ) {
		foreach ( $json_msg_ary as &$json_msg ) {
			$json_msg['level'] = self::MESSAGE_LEVEL[ $json_msg['level'] ];
			$json_msg['view']  = $view;
			if ( get_bloginfo('language') !== 'ja' ) {
				switch ( $json_msg['no'] ) {
					case 200 :
					case 202 :
						$json_msg['message'] = esc_html_x('The distribution file is not available. If you keep seeing this message, please contact our support team.', 'License activation error message (code 200, 202).', 'qa-heatmap-analytics');
						break;					
					case 203 :
						$json_msg['message'] = esc_html_x('Authentication Failed: Your site domain may already be authenticated by another license. Please contact our support team.', 'License activation error message (code 203).', 'qa-heatmap-analytics');
						break;
					case 204 :
						$json_msg['message'] = esc_html_x('An error occurred during the registration to the license server\'s database. If you keep seeing this message, please contact our support team.', 'License activation error message (code 204).', 'qa-heatmap-analytics');
						break;
					case 300 :
						$json_msg['message'] = esc_html_x('Authentication Failed: The user ID is invalid.', 'License activation error message (code 300).', 'qa-heatmap-analytics');
						break;
					case 301 :
					case 302 :
						$json_msg['message'] = esc_html_x('Authentication Failed: The license key is invalid.', 'License activation error message (code 301, 302).', 'qa-heatmap-analytics');
						break;
					case 303 :
						$json_msg['message'] = esc_html_x('Authentication Failed: The number of domains that can be authenticated with your license has been exceeded.', 'License activation error message (code 303).', 'qa-heatmap-analytics');
						break;
					case 304 :
						$json_msg['message'] = esc_html_x('Error: This site domain is not registered for authentication.', 'License activation error message (code 304).', 'qa-heatmap-analytics');
						break;
					case 305 :
						$json_msg['message'] = esc_html_x('Error: The submitted information is incomplete.', 'License activation error message (code 305).', 'qa-heatmap-analytics');
						break;
					default :
						break;
				}
			}

		}
		return $json_msg_ary;
	}
	
	/**
	 * ライセンス認証の通信処理。戻り値はjsonデータ。通信に失敗した場合はfalse
	 */
	private function remote_post ( $url, $args, $view ) {
		$level = self::MESSAGE_LEVEL['error'];
		$msg   = esc_html__( 'An error occurred during authentication.', 'qa-heatmap-analytics');
		$msg2	 = esc_html__( ' (1) If this error message appears after your license has been activated and continues to occur, please contact our support team.', 'qa-heatmap-analytics' );
		$msg2	 .= esc_html__( ' (2) If this occurs during the first attempt to activate your license, please try activating the license again. And if you still encounter the same message, kindly contact our support team.', 'qa-heatmap-analytics' );

		//since WP6.4, 'timeout' extended.
		$allmix    = wp_remote_post( $url, array( 'body' => $args, 'timeout' => 30 ) );

		if ( is_wp_error( $allmix ) ) {
			$wp_error_code = $allmix->get_error_code();
			$wp_error_message = $allmix->get_error_message();
			if ( ! $wp_error_code ) {
				$wp_error_code = '';
			}
			if ( ! $wp_error_message ) {
				$wp_error_message = '';
			}
			$msg .= ' [WP_Error] ' . $msg2;
			$this->set_plugin_message( $level, $msg, $view, esc_html__( 'License authentication error', 'qa-heatmap-analytics' ) . ' : wp_remote_post > is_wp_error' . ' Code: ' . $wp_error_code . ' Message: ' . $wp_error_message );
			return false;
		}

		$msg .= $msg2;
		$ret_body = wp_remote_retrieve_body( $allmix );
		if ( ! $ret_body ) {
			$this->set_plugin_message( $level, $msg, $view, esc_html__( 'License authentication error', 'qa-heatmap-analytics' ) . ' : wp_remote_post > wp_remote_retrieve_body' );
			return false;
		}

		$res_code = wp_remote_retrieve_response_code( $allmix );
		if ( ! $res_code ) {
			$this->set_plugin_message( $level, $msg, $view, esc_html__( 'License authentication error', 'qa-heatmap-analytics' ) . ' : wp_remote_post > wp_remote_retrieve_response_code' );
			return false;

		} elseif ( $res_code !== 200 ) {
			$this->set_plugin_message( $level, $msg, $view, esc_html__( 'License authentication error', 'qa-heatmap-analytics' ) . ' : http status code ' . $res_code );
			return false;
		}

		$json_array  = json_decode( $ret_body, true );
		if ( $json_array === null ) {
			$this->set_plugin_message( $level, $msg, $view, esc_html__( 'License authentication error', 'qa-heatmap-analytics' ) . ' : wp_remote_post > json_decode' );
			return false;
		}

		return $json_array;
	}

	/**
	 * アクティベート（認証）
	 * $viewではエラーの通知領域をMESSAGE_VIEWの中から設定可能
	 */
	public function activate( $key, $uid, $view = self::MESSAGE_VIEW['admin'], $url = 'https://mem.quarka.org/tsushin/' ) {
		global $qahm_time;
		$this->wrap_update_option( 'license_activate_time', $qahm_time->now_unixtime() );

		$parm        = array();
		$parm['sec'] = 'license';
		$parm['cmd'] = 'check';
		$parm['ver'] = QAHM_PLUGIN_VERSION;
		$parm['dom'] = self::$dom;
		$parm['uid'] = $uid;
		$parm['key'] = $key;
		
		$json_array = $this->remote_post( $url, $parm, $view );
		if ( ! $json_array ) {
			return false;
		}
		if ( QAHM_DEBUG >= QAHM_DEBUG_LEVEL['debug'] ) {
			//print_r( $json_array );
		}

		$is_success =  false;
		if ( $json_array['is_success'] ) {
			$is_success = true;
			$this->change_paid( $json_array['val'], $json_array['bin'] );
			$json_array['msg'][0]['message'] = esc_html__( 'Your license has been successfully authenticated.', 'qa-heatmap-analytics' );
			$json_array['msg'][0]['level']   = 'success';
			$json_array['msg'][0]['no']      = 0;

			if( $view === self::MESSAGE_VIEW['admin'] ) {
				$view = self::MESSAGE_VIEW['hidden'];
			} elseif ( $view === self::MESSAGE_VIEW['license'] ) {
				// ライセンス認証画面の紙吹雪エフェクト用
				$json_array['msg'][0]['confetti'] = true;
			}
		} else {
			$this->change_free();
		}
		
		if ( $json_array['msg'] ) {
			$msg = $this->opt_json_message( $json_array['msg'], $view );
			$this->wrap_update_option( 'license_message', $msg );
		} else {
			$this->wrap_update_option( 'license_message', '' );
		}
		return $is_success;
	}

	// このドメインのライセンスを削除する
	public function deactivate( $key, $uid, $view = self::MESSAGE_VIEW['admin'], $url = 'https://mem.quarka.org/tsushin/' ) {
		$parm        = array();
		$parm['sec'] = 'license';
		$parm['cmd'] = 'deactivate';
		$parm['dom'] = self::$dom;
		$parm['uid'] = $uid;
		$parm['key'] = $key;
		
		$json_array = $this->remote_post( $url, $parm, $view );
		if ( ! $json_array ) {
			return false;
		}
		if ( QAHM_DEBUG >= QAHM_DEBUG_LEVEL['debug'] ) {
			//print_r( $json_array );
		}

		$is_success =  false;
		if ( $json_array['is_success'] ) {
			$is_success = true;
			$this->change_free();
			$json_array['msg'][0]['message'] = esc_html( __( 'Your license has been deactivated. You will now revert to the free version.', 'qa-heatmap-analytics' ) );
			$json_array['msg'][0]['level']   = 'success';
			$json_array['msg'][0]['no']      = 0;

			if( $view === self::MESSAGE_VIEW['admin'] ) {
				$view = self::MESSAGE_VIEW['hidden'];
			}
		}
		
		if ( $json_array['msg'] ) {
			$msg = $this->opt_json_message( $json_array['msg'], $view );
			$this->wrap_update_option( 'license_message', $msg );
		} else {
			$this->wrap_update_option( 'license_message', '' );
		}
		return $is_success;
	}

	// 有償化
	public function change_paid( $val, $bin ) {
		global $wp_filesystem;
		
		// パラメータをwp_optionsに格納
		if ( array_key_exists( 'plans', $val ) && $val['plans'] ) {
			$this->wrap_update_option( 'license_plans', json_decode( $val['plans'], true ) );
		}
		if ( array_key_exists( 'option', $val ) && $val['option'] ) {
			$this->wrap_update_option( 'license_option', json_decode( $val['option'], true ) );
		}
		// pv_limit_rateは即更新
		$count_pv = $this->count_this_month_pv();
		$limit_pv = $this->get_license_option( 'measure' );
		if ( $count_pv >= $limit_pv ) {
			$this->wrap_update_option( 'pv_limit_rate', 100 );
		} else {
			$rate = ( $count_pv / $limit_pv ) * 100;
			$rate = round( $rate );
			$this->wrap_update_option( 'pv_limit_rate', $rate );
		}

		if ( ! $bin ) {
			return;
		}

		// js 作成
		// jsのためセキュリティコードは不要。そのためwrap_put_contentsを通していない。
		// wrap_put_contentsにセキュリティコードを省く用の引数を増やしたい
		foreach ( $bin as $data ) {
			if ( $data['extension'] === 'js' ) {
				$path = $this->get_data_dir_path() . 'js/';
				if ( ! $wp_filesystem->exists( $path ) ) {
					$wp_filesystem->mkdir( $path );
				}

				$path .= $data['name'];
				$body  = base64_decode( $data['body'] );

				$wp_filesystem->put_contents( $path, $body );
			}
		}
	}

	// 無償化
	public function change_free() {
		global $wp_filesystem;

		// js 削除
		$dir_path = $this->get_data_dir_path() . 'js/';
		$list = $this->wrap_dirlist( $dir_path );
		if ( $list ) {
			foreach ( $list as $file ) {
				if( strncmp( $file['name'], 'powerup-', strlen( 'powerup-' ) ) === 0 ) {
					$wp_filesystem->delete( $dir_path . $file['name'] );
				}
			}
		}

		// wp_optionsのパラメーターを初期化
		$this->wrap_update_option( 'license_plans', null );
		$this->wrap_update_option( 'license_option', null );
		$this->wrap_update_option( 'data_retention_dur', 90 );
		$goals_json = $this->wrap_get_option( 'goals' );
		if ( $goals_json ) {
			$goals_ary = json_decode( $goals_json, true );
			$goals_cnt = count($goals_ary);
			if ( $goals_cnt >= 2 ) {
				for ( $i = 2; $i <= $goals_cnt; $i++ ) {
					if ( array_key_exists( $i, $goals_ary ) ) {
						unset( $goals_ary[$i] );
					}
				}
			}
			$goals_json = wp_json_encode( $goals_ary );
			$this->wrap_update_option( 'goals', $goals_json );
		}
		//$this->wrap_update_option( 'access_role', 'administrator' );

		// pv_limit_rateは即更新
		$count_pv = $this->count_this_month_pv();
		$limit_pv = QAHM_Cron_Proc::DEFAULT_LIMIT_PV_MONTH;

		if ( $count_pv >= $limit_pv ) {
			$this->wrap_update_option( 'pv_limit_rate', 100 );
		} else {
			$rate = ( $count_pv / $limit_pv ) * 100;
			$rate = round( $rate );
			$this->wrap_update_option( 'pv_limit_rate', $rate );
		}
	}
}
