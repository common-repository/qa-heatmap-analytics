<?php
/**
 * プラグインで読み込むファイルの管理
 *
 * @package qa_heatmap
 */

new QAHM_Load();

// ファイルのロードや管理画面のメニューを管理するクラス
class QAHM_Load extends QAHM_File_Base {

	public function __construct() {
		// 翻訳ファイルの読み込み
		load_plugin_textdomain( 'qa-heatmap-analytics', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

		// 管理画面にプラグインメニューを追加＆CSS, JS読み込み
		add_action( 'admin_menu', array( $this, 'create_plugin_menu' ) );

		// 管理画面以外のCSS, JS読み込み
		add_action( 'wp_enqueue_scripts', array( $this, 'load_user_scripts' ) );

		// PV数制限アラート
		add_action( 'admin_notices', array( $this, 'show_pv_limit_notice' ) );

		// 管理画面に表示するアラート
		add_action( 'admin_enqueue_scripts', array( $this, 'add_announce_style' ) );

		// QA専用ユーザーのリダイレクト処理
		add_action( 'admin_init', array( $this, 'qahmuser_redirect' ) );
		// QA専用ユーザーの場合不要なメニューの削除
		add_action( 'admin_menu', array( $this, 'qahmuser_remove_menus' ) );
		// QA専用ユーザーのアドミンバーのカスタマイズ
		add_action( 'wp_before_admin_bar_render', array( $this, 'qahmuser_custom_admin_bar' ) );
	}


	function show_pv_limit_notice() {
		$pv_limit_rate = $this->wrap_get_option( 'pv_limit_rate' );
		if ( ! $pv_limit_rate ) {
			return;
		}
		$lang_set = get_bloginfo('language');
		if ( $lang_set == 'ja' ) {
			$upgrade_link_atag = '<a href="https://quarka.org/plan/" target="_blank" rel="noopener">'; 
			$referral_link_atag = '<a href="https://quarka.org/referral-program/" target="_blank" rel="noopener">';
		} else {
			$upgrade_link_atag = '<a href="https://quarka.org/en/#plans" target="_blank" rel="noopener">';
			$referral_link_atag = '<a href="https://quarka.org/en/referral-program/" target="_blank" rel="noopener">';
		}
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
			// リンクタグ
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
				'rel' => array(),
			),
			// 画像タグ
			'img' => array(
				'src' => array(),
				'alt' => array(),
				'width' => array(),
				'height' => array(),
				'class' => array(),
			),
		);
		if ( $pv_limit_rate >= 100 ) {
			/* translators: placeholders are for the link */
			$qa_announce_html= $this->create_qa_announce_html( sprintf( esc_html__( 'Limit Reached: Data collection paused due to reaching the page view capacity limit for this month. %1$sUpgrade your plan%2$s for continuous tracking, or %3$srefer friends%4$s to increase your capacity for free.', 'qa-heatmap-analytics' ), $upgrade_link_atag, '</a>', $referral_link_atag, '</a>' ), 'error' );
			echo wp_kses( $qa_announce_html, $qa_announce_allowed_tags );
		} else if ( $pv_limit_rate >= 80 ) {
			/* translators: placeholders are for the link */
			$qa_announce_html = $this->create_qa_announce_html( sprintf( esc_html__( 'Attention: Your site has reached 80%% of its page view capacity for this month. To ensure uninterrupted service, %1$srefer friends%2$s or consider %3$supgrading your plan%4$s.', 'qa-heatmap-analytics' ), $referral_link_atag, '</a>', $upgrade_link_atag, '</a>' ), 'error' );
			echo wp_kses( $qa_announce_html, $qa_announce_allowed_tags );
		}
	}

	function add_announce_style() {
		$css_dir_url = $this->get_css_dir_url();
		wp_enqueue_style( QAHM_NAME . '-admin-page-announce', $css_dir_url . 'admin-page-announce.css', null, QAHM_PLUGIN_VERSION );	
	}

	// 読者用のファイル読み込み
	public function load_user_scripts() {
		if ( $this->is_bot() ) {
			return;
		}

		if ( is_home() || is_front_page() ) {
			$type = 'home';
			$id   = 1;
		} elseif ( is_page() ) {
			$type = 'page_id';
			$id   = get_the_id();
		} elseif ( is_single() ) {
			$type = 'p';
			$id   = get_the_id();
		} elseif ( is_category() ) {
			$type = 'cat';
			$id   = get_query_var( 'cat' );
		} elseif ( is_tag() ) {
			$type = 'tag';
			$id   = get_query_var( 'tag_id' );
		} elseif ( is_tax() ) {
			$type = 'tax';
			$id   = get_queried_object_id();
		} else {
			$type = '';
			$id   = '';
		}

		$js_dir_url = $this->get_js_dir_url();
		$dev001     = defined( 'QAHM_DEV001' ) ? true : false;
		$dev002     = defined( 'QAHM_DEV002' ) ? true : false;
		$dev003     = defined( 'QAHM_DEV003' ) ? true : false;
		
		$behave   = new QAHM_Behavioral_Data();
        $thashary = $behave->get_tracking_hash_array();
        $thash    = $thashary[0]['tracking_hash'];

		$cb_sup_mode = $this->wrap_get_option( 'cb_sup_mode' );
		if( $cb_sup_mode == "no" ){
			$cookie_mode = false;
		}else{
			$cb_init_consent = $this->wrap_get_option( 'cb_init_consent' );
			if( !$cb_init_consent ){
				$cb_init_consent = "yes";
			}
			$cookie_mode = true;
		}

		if ( is_user_logged_in() ) {
			// ログインユーザー アドミンバーの処理が今はないのでコメント中
			// enqueue script
			//wp_enqueue_script( QAHM_NAME . '-common',  $js_dir_url . 'common.js', array( 'jquery' ), QAHM_PLUGIN_VERSION );
			//wp_enqueue_script( QAHM_NAME . '-load-screen',  $js_dir_url . 'load-screen.js', array( QAHM_NAME . '-common' ), QAHM_PLUGIN_VERSION );
			//wp_enqueue_script( QAHM_NAME . '-cap-create',  $js_dir_url . 'cap-create.js', array( QAHM_NAME . '-load-screen' ), QAHM_PLUGIN_VERSION );

			// inline script
			$scripts = array();
			$scripts['ajax_url']          = admin_url( 'admin-ajax.php' );
            //mkdummy
			$scripts['plugin_dir_url']    = plugin_dir_url( __FILE__ );
			$scripts['tracking_hash']     = $thash;
            //mkdummy
			$scripts['const_debug_level'] = QAHM_DEBUG_LEVEL;
			$scripts['const_debug']       = QAHM_DEBUG;
			$scripts['license_plans']     = $this->wrap_get_option( 'license_plans' );
			$scripts['type']              = $type;
			$scripts['id']                = $id;
			$scripts['version_id']        = 1;	// あとで変更するかも imai
			$scripts['dev001']            = $dev001;
			$scripts['dev002']            = $dev002;
			$scripts['dev003']            = $dev003;
			wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		} else {
			// 一般ユーザー データ測定用
			// enqueue script
			wp_enqueue_script( QAHM_NAME . '-polyfill-object-assign',  $js_dir_url . 'polyfill/object_assign.js', null, QAHM_PLUGIN_VERSION, false );
			if( $cookie_mode ){
				wp_enqueue_script( QAHM_NAME . '-cookie-consent-obj',  $js_dir_url . 'cookie-consent-obj.js', null, QAHM_PLUGIN_VERSION, false );
				wp_enqueue_script( QAHM_NAME . '-cookie-consent-qtag', plugin_dir_url( __FILE__ ). 'cookie-consent-qtag.php?cookie_consent='.$cb_init_consent, null, QAHM_PLUGIN_VERSION, false );
			}
			wp_enqueue_script( QAHM_NAME . '-behavioral-data-init',  $js_dir_url . 'behavioral-data-init.js', array( QAHM_NAME . '-polyfill-object-assign' ), QAHM_PLUGIN_VERSION, false );
			wp_enqueue_script( QAHM_NAME . '-common',  $js_dir_url . 'common.js', array( 'jquery', QAHM_NAME . '-behavioral-data-init' ), QAHM_PLUGIN_VERSION, false );
			wp_enqueue_script( QAHM_NAME . '-behavioral-data-record',  $js_dir_url . 'behavioral-data-record.js', array( QAHM_NAME . '-common', QAHM_NAME . '-behavioral-data-init' ), QAHM_PLUGIN_VERSION, true );

			// inline script
			$scripts = array();
			$scripts['ajax_url']          = admin_url( 'admin-ajax.php' );
            //mkdummy
			$scripts['plugin_dir_url']    = plugin_dir_url( __FILE__ );
			$scripts['tracking_hash']    = $thash;
            //mkdummy
			$scripts['nonce_init']        = wp_create_nonce( QAHM_Behavioral_Data::NONCE_INIT );
			$scripts['nonce_behavioral']  = wp_create_nonce( QAHM_Behavioral_Data::NONCE_BEHAVIORAL );
			$scripts['const_debug_level'] = QAHM_DEBUG_LEVEL;
			$scripts['const_debug']       = QAHM_DEBUG;
			$scripts['type']              = $type;
			$scripts['id']                = $id;
			$scripts['dev001']            = $dev001;
			$scripts['dev002']            = $dev002;
			$scripts['dev003']            = $dev003;
			$scripts['cookieMode']        = $cookie_mode;
			wp_add_inline_script( QAHM_NAME . '-behavioral-data-init', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', obj );', 'before' );
		}
	}

	/**
	 * 管理画面のQAHMメニュー
	 * ここから各管理ページのファイル読み込みフックを呼び出している
	 */
	public function create_plugin_menu() {
		if ( ! is_user_logged_in() || ! is_admin() ) {
			return false;
		}

		global $qahm_admin_page_home;
		global $qahm_admin_page_realtime;
		global $qahm_admin_page_dataportal;
		global $qahm_admin_page_heatmap;
		global $qahm_admin_page_seo;
		global $qahm_admin_page_config;
		global $qahm_admin_page_license;
		global $qahm_admin_page_help;

		$svg_icon = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="utf-8"?>
			<!-- Generator: Adobe Illustrator 25.4.1, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
			<svg version="1.1" id="レイヤー_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
				y="0px" viewBox="0 0 256 256" style="enable-background:new 0 0 256 256;" xml:space="preserve">
			<style type="text/css">
				.st0{fill:#ED807F;}
				.st1{fill:#EB8180;}
				.st2{fill:#F8CCC4;}
			</style>
			<g>
				<g>
					<path class="st0" d="M232.7,237.5c4.6,0,9.1-2.1,12-6c4.9-6.6,3.6-16-3.1-20.9l-39.4-29.3l-15.2,26l36.7,27.3
						C226.4,236.6,229.6,237.5,232.7,237.5z"/>
					<path class="st1" d="M186.5,189.8c-1.3,0-2.7-0.4-3.8-1.3l-78.5-59c-2.1-1.6-3-4.3-2.3-6.8c0.7-2.5,2.9-4.4,5.5-4.7l44.9-4.6
						c3.6-0.4,6.7,2.2,7,5.7c0.4,3.5-2.2,6.7-5.7,7l-28.6,2.9l65.4,49.1c2.8,2.1,3.4,6.1,1.3,9C190.4,188.9,188.4,189.8,186.5,189.8z"
						/>
					<path class="st2" d="M117.4,237c-19.1,0-38-5.1-54.9-14.9c-25.2-14.7-43.2-38.4-50.6-66.6C-3.3,97.2,31.6,37.4,89.9,22.1
						C148.1,6.8,208,41.7,223.3,100l0,0c15.3,58.2-19.6,118.1-77.9,133.4C136.1,235.8,126.7,237,117.4,237z M117.6,44.1
						c-7,0-14.1,0.9-21.2,2.8C51.8,58.6,25,104.4,36.8,149c5.7,21.6,19.4,39.7,38.7,51c19.3,11.3,41.8,14.3,63.4,8.7
						c44.6-11.7,71.3-57.5,59.6-102.1C188.6,69,154.7,44.1,117.6,44.1z"/>
					<path class="st1" d="M169.4,124.8c-0.1,0-0.2,0-0.3,0c-4-0.2-7.1-3.5-6.9-7.5c0,0,0,0,0,0c0.2-3.9,3.5-7,7.5-6.9
						c4,0.2,7.1,3.5,6.9,7.5C176.4,121.8,173.2,124.8,169.4,124.8z"/>
					<path class="st1" d="M186.5,123.2c-0.1,0-0.2,0-0.3,0c-4-0.2-7.1-3.5-6.9-7.5c0,0,0,0,0,0c0.2-3.9,3.5-7,7.5-6.9
						c1.9,0.1,3.7,0.9,5,2.3c1.3,1.4,2,3.3,1.9,5.2C193.5,120.2,190.3,123.2,186.5,123.2z"/>
				</g>
			</g>
			</svg>
			');

		// capability切り替え
		if ( current_user_can('manage_options') ) {
			$manage_cap = 'manage_options';
			$view_cap  = 'manage_options';
		} else {
			$manage_cap = 'qahm_manage_settings';
			$view_cap = 'qahm_view_reports';
		}

		// プラグインメインメニュー
		add_menu_page(
			'QA Analytics',
			'QA Analytics',
			$view_cap,
			QAHM_Admin_Page_Home::SLUG,
			array( $qahm_admin_page_home, 'create_html' ),
			$svg_icon,
			99
		);

		// ホーム
		$qahm_admin_page_home->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'Home', 'qa-heatmap-analytics' ),
			esc_html__( 'Home', 'qa-heatmap-analytics' ),
			$view_cap,
			QAHM_Admin_Page_Home::SLUG,
			array( $qahm_admin_page_home, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_home, 'enqueue_scripts' ) );

		// データポータル連携
		$qahm_admin_page_dataportal->hook_suffix = add_submenu_page(
			null,
			esc_html__( 'Looker Studio Connector', 'qa-heatmap-analytics' ),
			esc_html__( 'Looker Studio Connector', 'qa-heatmap-analytics' ),
			$view_cap,
			QAHM_Admin_Page_Dataportal::SLUG,
			array( $qahm_admin_page_dataportal, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_dataportal, 'enqueue_scripts' ) );
		

		// リアルタイムビュー（旧ダッシュボード）
		$qahm_admin_page_realtime->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'Real-Time View', 'qa-heatmap-analytics' ),
			esc_html__( 'Real-Time View', 'qa-heatmap-analytics' ),
			$view_cap,
			QAHM_Admin_Page_Realtime::SLUG,
			array( $qahm_admin_page_realtime, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_realtime, 'enqueue_scripts' ) );

		// SEO分析
		$qahm_admin_page_seo->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'SEO Analysis', 'qa-heatmap-analytics' ),
			esc_html__( 'SEO Analysis', 'qa-heatmap-analytics' ),
			$view_cap,
			QAHM_Admin_Page_Seo::SLUG,
			array( $qahm_admin_page_seo, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_seo, 'enqueue_scripts' ) );

		// ページバージョン（旧ヒートマップ管理）
		$qahm_admin_page_heatmap->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'Page Version Hub', 'qa-heatmap-analytics' ),
			esc_html__( 'Page Version Hub', 'qa-heatmap-analytics' ),
			$manage_cap,
			QAHM_Admin_Page_Heatmap::SLUG,
			array( $qahm_admin_page_heatmap, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_heatmap, 'enqueue_scripts' ) );

		// ライセンス認証
		$qahm_admin_page_license->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'License Activation', 'qa-heatmap-analytics' ),
			esc_html__( 'License Activation', 'qa-heatmap-analytics' ),
			$manage_cap,
			QAHM_Admin_Page_License::SLUG,
			array( $qahm_admin_page_license, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_license, 'enqueue_scripts' ) );

		// 設定
		$qahm_admin_page_config->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'Settings', 'qa-heatmap-analytics' ),
			esc_html__( 'Settings', 'qa-heatmap-analytics' ),
			$manage_cap,
			QAHM_Admin_Page_Config::SLUG,
			[$qahm_admin_page_config, 'create_html']
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_config, 'enqueue_scripts' ) );

		// ヘルプ
		$qahm_admin_page_help->hook_suffix = add_submenu_page(
			QAHM_Admin_Page_Home::SLUG,
			esc_html__( 'Help', 'qa-heatmap-analytics' ),
			esc_html__( 'Help', 'qa-heatmap-analytics' ),
			$view_cap,
			QAHM_Admin_Page_Help::SLUG,
			array( $qahm_admin_page_help, 'create_html' )
		);
		add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_help, 'enqueue_scripts' ) );

		if ( defined( 'QAHM_DEV001' ) ) {
			// imai test
			require_once dirname( __FILE__ ) . '/class-qahm-admin-page-imai.php';
			$qahm_admin_page_imai->hook_suffix = add_submenu_page(
				QAHM_Admin_Page_Home::SLUG,
				'imai test',
				'imai test',
				'manage_options',
				QAHM_Admin_Page_Imai::SLUG,
				array( $qahm_admin_page_imai, 'create_html' )
			);
			add_action( 'admin_enqueue_scripts', array( $qahm_admin_page_imai, 'enqueue_scripts' ) );
		}
	}

	
	// QAHM専用ユーザーの場合不要なメニューの削除
	public function qahmuser_remove_menus() {
		$user  = wp_get_current_user();
		$roles  = (array) $user->roles;
		$role   = array_shift($roles); // 最初のロールを取得（通常、ユーザーは1つのロールしか持たない）
		if ( $role === 'qahm-manager' || $role === 'qahm-viewer' ) {
			remove_menu_page( 'index.php' );                  // ダッシュボードを隠します
			remove_menu_page( 'edit.php' );                   // 投稿メニュを隠します
			remove_menu_page( 'upload.php' );                 // メディアを隠します
			remove_menu_page( 'edit.php?post_type=page' );    // ページ追加を隠します
			remove_menu_page( 'edit-comments.php' );          // コメントメニューを隠します
			remove_menu_page( 'themes.php' );                 // 外観メニューを隠します
			remove_menu_page( 'plugins.php' );                // プラグインメニューを隠します
			remove_menu_page( 'users.php' );                  // ユーザーを隠します
			remove_menu_page( 'tools.php' );                  // ツールメニューを隠します
			remove_menu_page( 'options-general.php' );        // 設定メニューを隠します
			remove_menu_page( 'profile.php' );                // プロフィールメニューを隠します
		}
	}

	// QAHM専用ユーザーのリダイレクト処理
	public function qahmuser_redirect() {
		$user  = wp_get_current_user();
		$roles  = (array) $user->roles;
		$role   = array_shift($roles);
		if ( $role === 'qahm-manager' || $role === 'qahm-viewer' ) {
			// pagenowを使わないとajaxでコケるっぽい
			global $pagenow;
			if ( $pagenow === 'index.php' ||
				$pagenow === 'edit.php' ||
				$pagenow === 'upload.php' ||
				$pagenow === 'comment.php' ||
				$pagenow === 'edit.php?post_type=page' ||
				$pagenow === 'edit-comments.php' ||
				$pagenow === 'edit-tags.php' ||
				$pagenow === 'themes.php' ||
				$pagenow === 'plugins.php' ||
				$pagenow === 'users.php' ||
				$pagenow === 'tools.php' ||
				$pagenow === 'options-general.php' ||
				$pagenow === 'post.php' ||
				$pagenow === 'post-new.php' ||
				$pagenow === 'profile.php' ||
				$pagenow === 'update-core.php'
			) {
				wp_safe_redirect( admin_url( 'admin.php?page=qahm-home' ) );
				exit;
			}
			// WordPress本体の更新通知を非表示
			remove_action( 'admin_notices', 'update_nag', 3 );
		}
	}

	// QAHM専用ユーザーのアドミンバーのカスタマイズ
	public function qahmuser_custom_admin_bar() {
		$user  = wp_get_current_user();
		$roles  = (array) $user->roles;
		$role   = array_shift($roles);
		if ( $role === 'qahm-manager' || $role === 'qahm-viewer'  ) {
			global $wp_admin_bar;

			// プロフィール編集リンクを削除
			$wp_admin_bar->remove_node( 'edit-profile' );

		}
	}
}
