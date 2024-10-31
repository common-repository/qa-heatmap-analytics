<?php
/**
 * プラグイン作りでどのクラスからも参照する汎用クラス
 *
 * @package qa_heatmap
 */

class QAHM_Base {
	/**
	 * wordpressのget_option関数をqahm用に使いやすくした関数
	 */
	public function wrap_get_option( $option, $default = false ) {
		if ( $default === false ) {
			foreach ( QAHM_OPTIONS as $key => $value ) {
				if ( $option === $key ) {
					$default = $value;
					break;
				}
			}
			foreach ( QAHM_DB_OPTIONS as $key => $value ) {
				if ( $option === $key ) {
					$default = $value;
					break;
				}
			}
		}
		return get_option( QAHM_OPTION_PREFIX . $option, $default );
	}

	/**
	 * wordpressのupdate_option関数をqahm用に使いやすくした関数
	 */
	public function wrap_update_option( $option, $value ) {
		/*
			DBオプションが既に存在した状態でfalseを指定すると空文字になる
			DBオプションが存在しない状態でfalseを指定するとDBにオプションが登録されない
			このif文はその差異を吸収している
		*/
		if ( $value === false ) {
			$value = '';
		}
		return update_option( QAHM_OPTION_PREFIX . $option, $value );
	}

	/**
	 * wordpressのget_user_meta関数をqahm用に使いやすくした関数
	 */
	public function wrap_get_user_meta( $user_id, $meta_key = '', $single = false ) {
		// 空文字列の場合、指定されたユーザーのすべてのメタデータを返される
		if ( $meta_key === '' ) {
			return get_user_meta( $user_id, $meta_key, $single );
		} else {
			return get_user_meta( $user_id, QAHM_OPTION_PREFIX . $meta_key, $single );
		}
	}

	/**
	 * wordpressのupdate_user_meta関数をqahm用に使いやすくした関数
	 */
	public function wrap_update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_user_meta( $user_id, QAHM_OPTION_PREFIX . $meta_key, $meta_value, $prev_value );
	}
	
	/**
	 * phpの$this->filter_inputのラップ関数
	 */
	public function wrap_filter_input( $type, $variable_name, $filter = FILTER_DEFAULT, $options = 0 )
	{
		$checkTypes =[
			INPUT_GET,
			INPUT_POST,
			INPUT_COOKIE
		];

		if (in_array($type, $checkTypes) || filter_has_var($type, $variable_name)) {
			return filter_input($type, $variable_name, $filter, $options);
		} else if ($type == INPUT_SERVER && isset($_SERVER[$variable_name])) {
			$sanitized_value = sanitize_text_field(wp_unslash($_SERVER[$variable_name]));
			return filter_var($sanitized_value, $filter, $options);
		} else if ($type == INPUT_ENV && isset($_ENV[$variable_name])) {
			return filter_var($_ENV[$variable_name], $filter, $options);
		} else {
			return null;
		}
	}

	/**
	 * wordpressのwp_mailをベースにQAから送れるようにしたもの
	 */
	public function qa_mail( $subject, $message ) {
		global $qahm_data_api;
		//$subject = mb_encode_mimeheader($subject, 'UTF-8');
		$homeurl = get_home_url();
		$domain  = wp_parse_url( $homeurl, PHP_URL_HOST );
		$from    = 'wordpress@' . $domain;
		$return  = false;

		$headers = array("From: QA Analytics <{$from}>", "Content-Type: text/plain; charset=UTF-8");
		$to = $this->wrap_get_option( 'send_email_address' );
		$return = wp_mail( $to, $subject, $message, $headers );
		return $return;
	}

	/**
	 * アクセス権限判定
	 */
	public function check_access_role() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user   = wp_get_current_user();
		$roles  = (array) $user->roles;
		$role   = $roles[0];
		$access = $this->wrap_get_option( 'access_role', 'administrator' );

		switch ( $role ) {
			case 'administrator':
				return ( 'administrator' === $access || 'editor' === $access ) ? true : false;
			case 'editor':
				return ( 'editor' === $access ) ? true : false;
			default:
				return false;
		}
	}
	public function check_qahm_access_cap( $cap ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		switch ( $cap ) {
			case 'manage_options':
				if ( $user->has_cap( 'manage_options' ) ) {
					return true;
				} else {
					return false;
				}
				break;

			case 'qahm_manage_settings':
				if ( $user->has_cap( 'manage_options' ) || $user->has_cap( 'qahm_manage_settings' ) ) {
					return true;
				} else {
					return false;
				}
				break;

			case 'qahm_view_reports':
				if ( $user->has_cap( 'manage_options' ) || $user->has_cap( 'qahm_view_reports' ) ) {
					return true;
				} else {
					return false;
				}
				break;
			default:
				return false;
		}
	}
	


	/**
	 * 有料メンバーかどうかの判別（画面表示用）
	 */
	public function is_subscribed() {
		$plans = $this->wrap_get_option( 'license_plans' );
		if ( $plans && $plans['paid'] === true ) {
			return true;
		}
		return false;
	}


	/**
	* 該当のライセンスプランが組み込まれていればその値を返す
	*/
	public function get_license_plan( $plan_name ) {
		$plan_ary = $this->wrap_get_option( 'license_plans' );

		if ( $plan_ary && array_key_exists( $plan_name, $plan_ary ) ) {
			return $plan_ary[$plan_name];
		} else {
			return null;
		}
	}


	/**
	* 該当のライセンスオプションが組み込まれていればその値を返す
	*/
	public function get_license_option( $option_name ) {
		$option_ary = $this->wrap_get_option( 'license_option' );

		if ( $option_ary && array_key_exists( $option_name, $option_ary ) ) {
			return $option_ary[$option_name];
		} else {
			return null;
		}
	}


	/**
	 * プラグインのメインファイルパスを取得
	 */
	public function get_plugin_main_file_path() {
		return dirname( __FILE__ ) . '/' . QAHM_NAME . '.php';
	}

	/**
	 * jsディレクトリのパスを取得
	 */
	public function get_js_dir_path() {
		return plugin_dir_path( __FILE__ ) . 'js/';
	}

	/**
	 * jsディレクトリのURLを取得
	 */
	public function get_js_dir_url() {
		return plugin_dir_url( __FILE__ ) . 'js/';
	}

	/**
	 * cssディレクトリのパスを取得
	 */
	public function get_css_dir_path() {
		return plugin_dir_path( __FILE__ ) . 'css/';
	}

	/**
	 * cssディレクトリのURLを取得
	 */
	public function get_css_dir_url() {
		return plugin_dir_url( __FILE__ ) . 'css/';
	}

	/**
	 * imgディレクトリのパスを取得
	 */
	public function get_img_dir_path() {
		return plugin_dir_path( __FILE__ ) . 'img/';
	}

	/**
	 * imgディレクトリのURLを取得
	 */
	public function get_img_dir_url() {
		return plugin_dir_url(__FILE__) . 'img/';
	}

	/**
	 * dataディレクトリのパスを取得
	 * 引数にdataディレクトリからのパスを入力することにより、
	 * dataディレクトリからの相対パスを取得することができる。
	 * 
	 * なおこの関数では念のためディレクトリの存在チェック＆mkdirも行うが、
	 * 相対パスに深い階層を指定してもmkdirされるのは最後の階層のみであり
	 * 道中の階層にはmkdirされない。その点には注意
	 */
	public function get_data_dir_path( $data_rel_path = '' ) {
		global $wp_filesystem;
		$path = $wp_filesystem->wp_content_dir() . QAHM_TEXT_DOMAIN . '-data/';
		if ( ! $wp_filesystem->exists( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		if ( $data_rel_path ) {
			$path .= $data_rel_path;
			if ( substr( $path, -1 ) !== '/' ) {
				$path .= '/';
			}

			if ( ! $wp_filesystem->exists( $path ) ) {
				$wp_filesystem->mkdir( $path );
			}
		}

		return $path;
	}

	/**
	 * dataディレクトリのURLを取得
	 */
	public function get_data_dir_url( $data_rel_path = '' ) {
		$path = content_url() . '/' . QAHM_TEXT_DOMAIN . '-data/';

		if ( $data_rel_path ) {
			$path .= $data_rel_path;
			if ( substr( $path, -1 ) !== '/' ) {
				$path .= '/';
			}
		}

		return $path;
	}

	/**
	 * tempディレクトリのパスを取得
	 */
	public function get_temp_dir_path() {
		return plugin_dir_path( __FILE__ ) . 'temp/';
	}

	/**
	 * cronのロックファイルのパスを取得
	 */
	public function get_cron_lock_path() {
		return $this->get_data_dir_path() . 'cron_lock';
	}

	/**
	 * cronのステータスファイルのパスを取得 -- maruyama
	 */
	public function get_cron_status_path() {
		return $this->get_data_dir_path() . 'cron_status';
	}

	/**
	 * cronのバックアップファイルのパスを取得 -- maruyama
	 */
	public function get_cron_backup_path() {
		return $this->get_data_dir_path() . 'cron_backup';
	}

	/**
	 * プラグインのメンテナンスモードか判定
	 */
	public function is_maintenance() {
		global $wp_filesystem;
		$maintenance_path = $this->get_temp_dir_path() . 'maintenance.php';
		if ( $wp_filesystem->exists( $maintenance_path ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * トラッキングIDを取得
	 */
	public function get_tracking_id( $url = null ) {
		if ( $this->is_wordpress() ) {
			// auto
			$parse_url = wp_parse_url( get_home_url() );
			$id = 'a&' . $parse_url['host'];
		} else {
			// manual
			$parse_url = wp_parse_url( $url );
			$id = 'm&' . $parse_url['host'];
		}
		return hash( 'fnv164', $id );
	}

	/**
	 * WPサイトか判定
	 * この判定方法で良いのかは要検証。今後変わる可能性あり
	 */
	public function is_wordpress() {
		if ( function_exists( 'wp_nonce_field' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * qahm対象ページか判定
	 * ※プラグイン読み込み直後のコンストラクタやwp_ajax内では$type引数指定無しの形は使えないので注意
	 */
	public function is_qahm_page( $type = null ) {
		if ( $type ){
			if(
				$type === 'home' ||
				$type === 'page_id' ||
				$type === 'p' ||
				$type === 'cat' ||
				$type === 'tag' ||
				$type === 'tax'
			) {
				return true;
			} else {
				return false;
			}
		} else {
			if ( is_home() || is_front_page() || is_page() || is_single() || is_category() || is_tag() || is_tax() ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * ajaxに関数を登録
	 */
	public function regist_ajax_func( $func ) {
		add_action( 'wp_ajax_' . QAHM_NAME . '_' . $func, array( $this, $func ) );
		add_action( 'wp_ajax_nopriv_' . QAHM_NAME . '_' . $func, array( $this, $func ) );
	}
	
	/**
	 * wp_filesystem 初期化
	 */
	public function init_wp_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			$creds = false;

			$access_type = get_filesystem_method();
			if ( $access_type === 'ftpext' ) {
				$creds = request_filesystem_credentials( '', '', false, false, null );
			}
			if ( ! WP_Filesystem( $creds ) ) {
				http_response_code(503);
				throw new Exception( 'Service Temporarily Unavailable. WP_Filesystem Invalid Credentials' );
			}
		}
	}

	/**
	 * uriエンコードし小文字にして返す
	 */
	public function encode_uri( $uri ) {
		$uri = preg_replace_callback( "{[^0-9a-z_.!~*'();,/?:@&=+$#-]}i", function ($m) {
			return sprintf('%%%02X', ord($m[0]));
		}, $uri );
		return mb_strtolower( $uri );
	}
	
	/**
	 * bot判定
	 */
	public function is_bot() {
		$bot = array(
			'Googlebot',
			'msnbot',
			'bingbot',
			'Yahoo! Slurp',
			'Y!J',
			'facebookexternalhit',
			'Twitterbot',
			'Applebot',
			'Linespider',
			'Baidu',
			'YandexBot',
			'Yeti',
			'dotbot',
			'rogerbot',
			'AhrefsBot',
			'MJ12bot',
			'SMTBot',
			'BLEXBot',
			'linkdexbot',
			'SemrushBot',
			'360Spider',
			'spider',
			'YoudaoBot',
			'DuckDuckGo',
			'Daum',
			'Exabot',
			'SeznamBot',
			'Steeler',
			'Sonic',
			'BUbiNG',
			'Barkrowler',
			'GrapeshotCrawler',
			'MegaIndex.ru',
			'archive.org_bot',
			'TweetmemeBot',
			'PaperLiBot',
			'admantx-apacas',
			'SafeDNSBot',
			'TurnitinBot',
			'proximic',
			'ICC-Crawler',
			'Mappy',
			'YaK',
			'CCBot',
			'Pockey',
			'psbot',
			'Feedly',
			'Superfeedr bot',
			'ltx71',
			'Mail.RU_Bot',
			'Linguee Bot',
			'DuckDuckBot',
			'bidswitchbot',
			'applebot',
			'istellabot',
			'integralads',
			'jet-bot',
			'trendictionbot',
			'blogmuraBot',
			'NetSeer crawler',
			QAHM_NAME . 'bot',
		);

		// 正規表現用に配列を置換
		for ( $i = 0, $bot_len = count( $bot ); $i < $bot_len; $i++ ) {
			$bot[ $i ] = str_replace( '.', '\.', $bot[ $i ] );
			$bot[ $i ] = str_replace( '-', '\-', $bot[ $i ] );
		}

		$ua = $this->wrap_filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );
		if( $ua ) {
			if ( preg_match( '/' . implode( '|', $bot ) . '/', $ua ) ) {
				return true;
			}
		}
		return false;
	}

	public function os_from_ua( $ua ) {
		$device_os = '';
		if ( preg_match( '/(iPhone|iPod|iPad|Windows Phone|Opera Mobi|Fennec|Android)/', $ua, $match ) ) {
			$device_os = $match[1];
		} else {
			if ( preg_match( '/(Mac OS X [0-9]*.[0-9]*|Windows NT [0-9]*.[0-9]*)/', $ua, $match ) ) {
				$device_os = $match[1];
			}
		}
		$device_os = str_replace( '_', '.', $device_os );
		return $device_os;
	}

	public function browser_from_ua( $ua ) {
		$browser = '';
		$version = '';
		// ブラウザの判別。
		if ( preg_match( '/(MSIE|Chrome|Firefox|Android|Safari|Opera|jp.co.yahoo.ipn.appli)[\/ ]([0-9.]*)/', $ua, $match ) ) {
			$browser = $match[1];
			$version = $match[2];
		}
		return $browser . '/' . $version;
	}

	public function is_zip ($string) {
		$is_zip = false;
		if ( 3 < strlen( $string ) ) {
			$byte1 = strtoupper( bin2hex( substr( $string,0,1 ) ) );
			$byte2 = strtoupper( bin2hex( substr( $string,1,1 ) ) );
			$byte3 = strtoupper( bin2hex( substr( $string,2,1 ) ) );

			if( $byte1=="1F" && $byte2=="8B" && $byte3=="08" ){
				$is_zip = true;
			}
		}
		return $is_zip;
	}

	/**
	 * qa 翻訳関数
	 */
	public function japan( $text, $domain = '' ) {
		return $text;
	}

	/**
	 * qa_idの生成
	 */
	public function create_qa_id( $ip_address, $ua, $tracking_hash ){
		$unique_server_value = NONCE_SALT.AUTH_SALT;
		//$id_base       = $ip_address.$ua.$tracking_hash;
		$id_base       = $ip_address.$ua.$unique_server_value.$tracking_hash;
		$qa_id_hash    = hash( 'fnv164', $id_base );

		return '000000000000' . $qa_id_hash;

	}

	/**
	 * QA用のアラートhtmlを作成（WordPress管理画面用）
	 */
	public function create_qa_announce_html( $text, $status = 'success' ){
		$base_color = null;
		switch ( $status ) {
			case 'success':
				$base_color = '#00a32a';
				break;

			case 'info':
				$base_color = '#72aee6';
				break;

			case 'warning':
				$base_color = '#dba617';
				break;

			case 'error':
				$base_color = '#d63638';
				break;

			default:
				return null;
		}

		/*
		$svg_icon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
				x="0px" y="0px" width="20" height="20" viewBox="0 0 256 256" style="enable-background:new 0 0 256 256;" xml:space="preserve">
			<g>
				<g>
					<path class="qahm-announce-icon-' . $status . '" d="M232.7,237.5c4.6,0,9.1-2.1,12-6c4.9-6.6,3.6-16-3.1-20.9l-39.4-29.3l-15.2,26l36.7,27.3
						C226.4,236.6,229.6,237.5,232.7,237.5z"/>
					<path class="qahm-announce-icon-' . $status . '" d="M186.5,189.8c-1.3,0-2.7-0.4-3.8-1.3l-78.5-59c-2.1-1.6-3-4.3-2.3-6.8c0.7-2.5,2.9-4.4,5.5-4.7l44.9-4.6
						c3.6-0.4,6.7,2.2,7,5.7c0.4,3.5-2.2,6.7-5.7,7l-28.6,2.9l65.4,49.1c2.8,2.1,3.4,6.1,1.3,9C190.4,188.9,188.4,189.8,186.5,189.8z"
						/>
					<path class="qahm-announce-icon-' . $status . '" d="M117.4,237c-19.1,0-38-5.1-54.9-14.9c-25.2-14.7-43.2-38.4-50.6-66.6C-3.3,97.2,31.6,37.4,89.9,22.1
						C148.1,6.8,208,41.7,223.3,100l0,0c15.3,58.2-19.6,118.1-77.9,133.4C136.1,235.8,126.7,237,117.4,237z M117.6,44.1
						c-7,0-14.1,0.9-21.2,2.8C51.8,58.6,25,104.4,36.8,149c5.7,21.6,19.4,39.7,38.7,51c19.3,11.3,41.8,14.3,63.4,8.7
						c44.6-11.7,71.3-57.5,59.6-102.1C188.6,69,154.7,44.1,117.6,44.1z"/>
					<path class="qahm-announce-icon-' . $status . '" d="M169.4,124.8c-0.1,0-0.2,0-0.3,0c-4-0.2-7.1-3.5-6.9-7.5c0,0,0,0,0,0c0.2-3.9,3.5-7,7.5-6.9
						c4,0.2,7.1,3.5,6.9,7.5C176.4,121.8,173.2,124.8,169.4,124.8z"/>
					<path class="qahm-announce-icon-' . $status . '" d="M186.5,123.2c-0.1,0-0.2,0-0.3,0c-4-0.2-7.1-3.5-6.9-7.5c0,0,0,0,0,0c0.2-3.9,3.5-7,7.5-6.9
						c1.9,0.1,3.7,0.9,5,2.3c1.3,1.4,2,3.3,1.9,5.2C193.5,120.2,190.3,123.2,186.5,123.2z"/>
				</g>
			</g>
			</svg>';
			
			// cut out from returning html
			<!--<div class="qahm-announce-icon">{$svg_icon}</div>-->
			*/
		$text = esc_html__( 'QA Analytics', 'qa-heatmap-analytics' ) . ': ' . $text;

		return <<< EOH
			<div class="qahm-announce-container qahm-announce-container-{$status}">
				<div class="qahm-announce-text">{$text}</div>
			</div>
EOH;
	}
}

?>