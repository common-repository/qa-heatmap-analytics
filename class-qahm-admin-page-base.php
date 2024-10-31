<?php
/**
 * 管理画面のページを表示するクラスの基本クラス
 *
 * @package qa_heatmap
 */

class QAHM_Admin_Page_Base extends QAHM_File_Data {
	public $hook_suffix;

	function __construct() {
		//$this->regist_ajax_func( 'ajax_view_oneyear_popup' );

		// RSSフィードのキャッシュを3時間に変更
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'set_feed_cache_time' ) );
	}
	
	/**
	 * 共通部分のスタイル
	 */
	protected function common_enqueue_style() {
		$css_dir_url = $this->get_css_dir_url();
		wp_enqueue_style( QAHM_NAME . '-sweet-alert-2', $css_dir_url . '/lib/sweet-alert-2/sweetalert2.min.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-reset', $css_dir_url . 'reset.css', array( QAHM_NAME . '-sweet-alert-2' ), QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-common', $css_dir_url . 'common.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-style', $css_dir_url . 'style.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-admin-page-base', $css_dir_url . 'admin-page-base.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );
	}
	
	/**
	 * 共通部分のスクリプト
	 */
	protected function common_enqueue_script() {
		$js_dir_url = $this->get_js_dir_url();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( QAHM_NAME . '-font-awesome',  $js_dir_url . 'lib/font-awesome/all.min.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-sweet-alert-2',  $js_dir_url . 'lib/sweet-alert-2/sweetalert2.min.js', array( 'jquery' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-alert-message',  $js_dir_url . 'alert-message.js', array( QAHM_NAME . '-sweet-alert-2' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-common',  $js_dir_url . 'common.js', array( 'jquery' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-load-screen',  $js_dir_url . 'load-screen.js', array( QAHM_NAME . '-common' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-effect',  $js_dir_url . 'effect.js', array( QAHM_NAME . '-load-screen' ), QAHM_PLUGIN_VERSION, false );
		if( ! $this->wrap_get_option( 'plugin_first_launch' ) && ! $this->is_maintenance() ) {
			wp_enqueue_script( QAHM_NAME . '-admin-page-base',  $js_dir_url . 'admin-page-base.js', array( QAHM_NAME . '-effect' ), QAHM_PLUGIN_VERSION, false );
		}
	}

	/**
	 * 共通部分のインラインスクリプト
	 */
	protected function get_common_inline_script() {
		$dev001 = defined( 'QAHM_DEV001' ) ? true : false;
		$dev002 = defined( 'QAHM_DEV002' ) ? true : false;
		$dev003 = defined( 'QAHM_DEV003' ) ? true : false;

		$scripts = array(
			'nonce_api'            => wp_create_nonce( QAHM_Data_Api::NONCE_API ),
			'ajax_url'             => admin_url( 'admin-ajax.php' ),
			'const_debug_level'    => QAHM_DEBUG_LEVEL,
			'const_debug'          => QAHM_DEBUG,
			'license_plans'        => $this->wrap_get_option( 'license_plans' ),
			'site_url'             => get_site_url(),
			'plugin_dir_url'       => plugin_dir_url( __FILE__ ),
			'plugin_version'       => QAHM_PLUGIN_VERSION,
			'data_dir_url'         => $this->get_data_dir_url(),
			'devices'              => QAHM_DEVICES,
			'announce_friend_plan' => $this->wrap_get_option( 'announce_friend_plan' ),
			'language'             => get_bloginfo( 'language' ),
			'dev001'               => $dev001,
			'dev002'               => $dev002,
			'dev003'               => $dev003,
		);

		if( $this->wrap_get_option( 'announce_friend_plan' ) && ! $this->wrap_get_option( 'plugin_first_launch' ) && ! $this->is_maintenance() ) {
			$this->wrap_update_option( 'announce_friend_plan', false );
		}
		return $scripts;
	}

	/**
	 * 共通部分の翻訳
	 */
	protected function get_common_localize_script() {
		$localize = array(
		//	'test' => $this->test( 'ここに共通ローカライズ単語を書いていく。このメッセージは翻訳不要', 'qa-heatmap-analytics' ),
		);

		return $localize;
	}

	/**
	 * jQueryのキューイングチェック
	 */
	protected function is_enqueue_jquery() {
		if ( wp_script_is( 'jquery' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * jQueryが存在しない時のメッセージを表示
	 */
	protected function view_not_enqueue_jquery_html() {
		$msg = '<div id="qahm-error" class="error notice is-dismissible"><p>';
		/* translators: placeholders are for the plugin name */
		$msg .= sprintf( esc_html__( 'Since jquery is not loaded, the function of %s cannot be enabled.', 'qa-heatmap-analytics' ), QAHM_PLUGIN_NAME );
		$msg .= '<br>';
		$msg .= esc_html__( 'Use "wp_enqueue_script" to load jquery.', 'qa-heatmap-analytics' );
		$msg .= '</p></div>';
		echo wp_kses_post( $msg );
	}

	/**
	 * メンテナンス表示
	 */
	protected function view_maintenance_html() {
		$style = <<< EOD
		<style>
		.mainteqa {
			width: 800px;
			background-color: #fcfcfc;
			padding: 24px;
		}
		.mainteqa h1 {
			border-bottom: solid 2px #f9cdc5;
			margin-bottom: 32px;
			font-size: 1.2rem;
			line-height: 2;
		}
		</style>
EOD;

		$mes  = '<div class="mainteqa">';
		$mes .= '<h1>' . esc_html__( 'Maintenance Notice', 'qa-heatmap-analytics' ) . '</h1>';
		$mes .= '<p>' . esc_html__( 'Your data is currently undergoing maintenance. Please note that this process may take some time.', 'qa-heatmap-analytics' ) . '</p>';
		$mes .= '<p>' . esc_html__( 'Usually, after updating the plugin, it may take a few to several minutes for the changes to take effect. You may need to reload the page afterward.', 'qa-heatmap-analytics' ) . '</p>';		
		$mes .= '<p>' . sprintf( 
				/* translators: placeholders are for the link */
				esc_html__( 'If you continue to see this notice for an extended period, please refer to our %1$stroubleshooting page%2$s.', 'qa-heatmap-analytics' ), 
				'<a href="https://mem.quarka.org/en/manual/keep-getting-data-is-under-maintenance/" target="_blank" rel="noopener">', 
				'</a>'
			) . '</p>';
		$mes .= '</div>';

		echo wp_kses( $style, array(
			'style' => array(),
		) );
		echo wp_kses_post( $mes );
	}

	protected function view_first_launch_html( $is_config_page = false ) {
	    $img_dir_url = $this->get_img_dir_url();
		$img_dir_url = esc_attr( $img_dir_url );

		/* translators: placeholders are for the plugin version */
		$h_welcome   	= sprintf( esc_html__( 'Welcome to QA Analytics %s !', 'qa-heatmap-analytics' ), QAHM_PLUGIN_VERSION );
		$desc_cansee 	= esc_html__( 'You can use Real-Time View today.', 'qa-heatmap-analytics' ) . '<br>';
		$desc_cansee 	.= esc_html__( 'As for analytics reports and a heatmap, wait and look forward to tomorrow when the data will be collected and processed.', 'qa-heatmap-analytics' );
		/* translators: placeholders are for the link */
		$helplink_starter = sprintf( esc_html__( '%1$s Starter Guide %2$s', 'qa-heatmap-analytics' ), '<a href="https://mem.quarka.org/en/manual/starter-quick-guide/" target="_blank" rel="noopener">', '<span class="qahm_link-mark"><i class="fas fa-external-link-alt"></i></span></a>' );

		$h_withoutcookie = esc_html__( 'To Enable Cookieless Measurement', 'qa-heatmap-analytics' );
		$set_cookiebannermode = esc_html__( 'If your site is using a cookie banner tool, please enable "Cookie Banner Compatibility Mode" in the plugin settings.', 'qa-heatmap-analytics' );

		$h_precheck	= esc_html__( 'Pre-check', 'qa-heatmap-analytics' );
		$list_precheck1 = esc_html__( 'JavaScript is NOT minified.', 'qa-heatmap-analytics' );
		$list_precheck3 = esc_html__( 'PHP memory limit (memory_limit) is recommended 1G bytes or higher.', 'qa-heatmap-analytics' );
		$list_precheck4 = esc_html__( 'PHP time limit (max_execution_time) is recomemended 240 seconds or longer.', 'qa-heatmap-analytics' );
		/* translators: placeholders are for the link */
		$helplink_precheck = sprintf( esc_html__( '%1$s Supported Environments %2$s', 'qa-heatmap-analytics' ), '<a href="https://mem.quarka.org/en/manual/site-environment/" target="_blank" rel="noopener">', '<span class="qahm_link-mark"><i class="fas fa-external-link-alt"></i></span></a>' );
		
		$h_start    = esc_html__( 'Get started!', 'qa-heatmap-analytics' );
		/* translators: placeholders are for the link */
		$agreepolicy = sprintf( esc_html__( 'By checking this box, I agree to the QA Analytics %1$s terms %2$s and %3$s privacy policy %4$s', 'qa-heatmap-analytics' ), '<a href="https://quarka.org/en/terms/" target="_blank" rel="noopener">', '</a>', '<a href="https://quarka.org/en/privacy-policy/" target="_blank" rel="noopener">', '</a>' );
		$continue    = esc_attr_x( 'Continue', 'on pre-check panel', 'qa-heatmap-analytics' );
		$alert_update_failed = esc_html__( 'Failed updating. You can set and update it from Settings.', 'qa-heatmap-analytics' );

	    $html = <<< EOL

		<style>
			.welcometoqa {
				width: 800px;
				background-color: white;
				padding: 30px;
			}
			.welcometoqa h3 {
				border-bottom: solid 2px #f9cdc5;
				margin-top: 35px;
				line-height: 2;
			}
			ul {
				list-style: inside;
			    margin-left: 6px;
			}
		</style>

	    <div class="bl_whitediv">
            <div class="welcometoqa">
                <p style="text-align: center"><img src="{$img_dir_url}qa-a-head.png"></p>
                <h3>{$h_welcome}</h3>
				<p>{$desc_cansee}</p>
                <p><span class="qahm_hatena-mark"><i class="far fa-question-circle"></i></span>{$helplink_starter}</p>
				<h3>{$h_withoutcookie}</h3>
				<p>{$set_cookiebannermode}</p>
				<h3>{$h_precheck}</h3>
				<ul>
					<li>
						{$list_precheck1}
					</li>
					<li>
						{$list_precheck3}
					</li>
					<li>
						{$list_precheck4}
					</li>
				</ul>
				<p><span class="qahm_hatena-mark"><i class="far fa-question-circle"></i></span>{$helplink_precheck}</p>
                <form onsubmit="formSubmit(this);return false">
					<h3>{$h_start}</h3>
					<p class="mailselect"><input type="checkbox" id="agreement" name="agreement" required>&nbsp;{$agreepolicy}</p>
               		<p align="right"><input type="submit" name="submit" id="" value="{$continue}" class="button button-primary"></p>
                </form>
            </div>
	    </div>

		<script>
		function formSubmit( formobj ) {
			let submitb = formobj['submit'];
			submitb.setAttribute( 'disabled', true );

			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action': 'qahm_ajax_save_first_launch',
						'nonce' : qahm.nonce_api,
					},
				}
			).done(
				function( data ){
					submitb.removeAttribute( 'disabled' );
					location.reload();
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					alert('{$alert_update_failed}');
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				}
			);
		}
		</script>
EOL;

		echo wp_kses_post($html);
	}

	/**
	 * QA 告知を表示
	 *  2021-03-13 以降使っていないのでコメントアウト
	 */
	/*
	protected function view_announce_html() {
		global $qahm_time;
		$diff_s = $qahm_time->xsec_num( '2021-03-13' );
		if( $diff_s < 0 ) {
			return;
		}

		$camp_btn = '';
		
		$date   = '2021-02-12 14:00:00';
		$diff_s = $qahm_time->xsec_num( $date );
		$diff_d = $qahm_time->xday_num( $date );
		if ( 0 < $diff_d ) {
			// カウントダウン
			$camp_text = $this->qa_lang__( sprintf( 'QA 1周年ありがとうキャンペーンまであと<span class="qahm-announce-emphasize">%d</span>日', $diff_d ), 'qa-heatmap-analytics' );
		} elseif ( 0 === $diff_d && $diff_s > 0 ) {
			// 当日 発表まで
			$camp_text = $this->qa_lang__( 'QA 1周年ありがとうキャンペーンは本日14時オープン。楽しみにお待ちください！', 'qa-heatmap-analytics' );
		} else {
			// キャンペーン中
			$camp_text = $this->qa_lang__( 'QA 1周年記念ありがとうWキャンペーンは3/12 23:59まで。先着順で当たります。詳しくは<a href="https://quarka.org/wcampaign-licence?ap=db&pv=' . QAHM_PLUGIN_VERSION . '" target="_blank" rel="noopener">こちら</a>', 'qa-heatmap-analytics' );
			$camp_btn  = '<div class="qahm-announce-button">' .
				'<a href="https://quarka.org/wcampaign-licence?ap=db&pv=' . QAHM_PLUGIN_VERSION . '" target="_blank" rel="noopener">' .
				$this->qa_lang__( '詳しくはこちら', 'qa-heatmap-analytics' ) .
				'</a>'.
				'</div>';
		}
		$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 130 126"><defs><style>.cls-1{fill:#00709e;}.cls-2{fill:none;}</style></defs><path class="cls-1" d="M312.85,477.41h-1.46a2.28,2.28,0,1,1,0-4.55h.66c20.14.07,29.47-4.91,33.75-9.16a17.25,17.25,0,0,0,5.09-12.9,21.7,21.7,0,0,0-8-16.54,2.28,2.28,0,0,1-.94-1.79c-.44-20.23-9.06-33-9.15-33.14a2.3,2.3,0,0,1-.19-2.24c0-.08,3.52-7.67,4-12.64.55-6,.52-16.44-5.89-18.84l-.11-.05c-2.09-.92-8.47,5.79-11.9,9.4-5.25,5.52-8.63,8.9-11.75,8.9a2.23,2.23,0,0,1-1-.22,18.27,18.27,0,0,0-14,0,2.23,2.23,0,0,1-1,.22c-3.12,0-6.5-3.38-11.75-8.9-3.43-3.6-9.81-10.32-11.9-9.4l-.11.05c-6.41,2.4-6.44,12.83-5.89,18.84.47,5,3.95,12.56,4,12.64a2.29,2.29,0,0,1-.19,2.25c-.09.12-8.71,12.9-9.15,33.13a2.29,2.29,0,0,1-1,1.81,21,21,0,0,0-7.95,16.52,17.26,17.26,0,0,0,5.1,12.9c4.21,4.2,13.36,9.16,33,9.16h1.44a2.28,2.28,0,0,1,0,4.55H286c-17.4.07-29.87-3.41-37-10.47a21.9,21.9,0,0,1-6.44-16.13,25.62,25.62,0,0,1,9-19.5c.62-17.83,7-29.86,9.22-33.45-1.07-2.48-3.46-8.44-3.88-13-1.16-12.56,1.95-20.9,8.76-23.48,5-2.13,10.8,4,16.95,10.44,2.62,2.76,6.5,6.84,8.17,7.43a22.95,22.95,0,0,1,16.53,0c1.65-.59,5.53-4.67,8.16-7.43,6.15-6.46,12-12.57,17-10.44,6.8,2.58,9.91,10.92,8.75,23.48-.42,4.55-2.81,10.51-3.87,13,2.17,3.59,8.59,15.62,9.22,33.47a26.11,26.11,0,0,1,8.95,19.48A21.9,21.9,0,0,1,349,466.94c-7,6.95-19.16,10.47-36.15,10.47Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M270.59,398.56c-1.22-.44-3.72-7.75-4.85-12.43-1.28-5.61-1.88-12.75,2.52-14.88a3.23,3.23,0,0,1,1.4-.34c2,0,3.48,2.14,4.95,4.21.35.49.7,1,1.07,1.47a29.44,29.44,0,0,0,5.75,5.19c1.88,1.38,2.44,1.94,2,2.57-.14.21-.44.33-1.38.7a23.45,23.45,0,0,0-7.12,4c-2.86,2.45-3.51,6.37-3.83,8.25-.13.83-.18,1.06-.32,1.2a.48.48,0,0,1-.23.1Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M327.41,398.56a.4.4,0,0,1-.23-.1c-.14-.14-.19-.37-.32-1.2-.32-1.89-1-5.81-3.83-8.25a23.41,23.41,0,0,0-7.1-4c-1-.39-1.27-.5-1.4-.7-.4-.63.16-1.2,2-2.57a29.52,29.52,0,0,0,5.76-5.2c.36-.47.71-1,1.06-1.45,1.47-2.08,3-4.22,4.95-4.22a3.16,3.16,0,0,1,1.39.34c4.42,2.13,3.81,9.3,2.52,14.94-1.59,6.37-3.8,12-4.84,12.37Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M278.77,412.57s-1.53,4.08,0,5.92a7.45,7.45,0,0,0,5.24,2,8.57,8.57,0,1,1-5.25-7.93Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M325.93,412.57s-1.53,4.08,0,5.92a7.47,7.47,0,0,0,5.25,2,8.59,8.59,0,1,1-5.26-7.93Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M299,437.87c-5.84,0-10.58,3-10.58,6.63S293.15,455,299,455s10.58-6.85,10.58-10.51S304.83,437.87,299,437.87Z" transform="translate(-233 -357.5)"/><rect id="_スライス_" data-name="&lt;スライス&gt;" class="cls-2" width="130" height="126"/></svg>';
		$allowed_html = array(
			'svg' => array(
				'xmlns' => array(),
				'width' => array(),
				'height' => array(),
				'viewBox' => array(),
				'fill' => array(),
			),
			'path' => array(
				'd' => array(),
				'fill' => array(),
			),
		);
		$svg_icon = wp_kses( $svg_icon, $allowed_html );

		echo <<< EOH
			<div class="qahm-announce-container">
				<div class="qahm-announce-icon">{$svg_icon}</div>
				<div class="qahm-announce-text">{$camp_text}</div>
				{$camp_btn}
			</div>
EOH;

	}
	*/


	/**
	 * RSSを表示
	 */
	protected function view_rss_feed() {
		$wp_lang_set = get_bloginfo( 'language' );
		if ( $wp_lang_set === 'ja' ) {
			include_once ABSPATH . WPINC . '/feed.php';
			$rss = fetch_feed( 'https://mem.quarka.org/category/wpuserinfo/feed/' );
			if ( is_wp_error( $rss ) ) {
				//echo $rss->get_error_message();
				return;
			}

			$maxitems = $rss->get_item_quantity( 5 );
			if ( ! empty( $maxitems ) && $maxitems > 0 ) {
				$rss_items = $rss->get_items( 0, $maxitems );
				?>
				<div id="qahm-rss" class="metabox-holder">
					
				<?php //キャンペーン告知窓読込み
				//file removed
				?>

					<div class="postbox">
						<h2><?php esc_html_e( 'QA Analytics News and Announcements', 'qa-heatmap-analytics' ); ?></h2>
						<div class="rss-widget">
							<h3><?php echo esc_html__( 'Installed Version', 'qa-heatmap-analytics' ) . ': ' . esc_html( QAHM_PLUGIN_VERSION ); ?></h3>
							<ul>
							<?php foreach ( $rss_items as $item ) { ?>
							<li>
								<span>
									<?php echo esc_html( $item->get_date( __( 'M j, Y', 'qa-heatmap-analytics' ) ) ); ?>
								</span>
								<a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank" class="rsswidget" rel="noopener">
									<?php echo esc_html( $item->get_title() ); ?>
								</a>
							</li>
							<?php } ?>
							</ul>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}


	/**
	 * 1周年キャンペーンのメッセージの表示判定
	 * ajaxはテキストを返すので true / false ではなく0か1かのテキストを返して判定
	 */
	public function ajax_view_oneyear_popup() {
		global $qahm_time;
		$start = $qahm_time->xsec_num( '2021-02-12 14:00:00' );
		$end   = $qahm_time->xsec_num( '2021-03-13 00:00:00' );
		$popup = $this->wrap_get_option( 'campaign_oneyear_popup' );
		if ( $start <= 0 && $end > 0 && ! $popup ) {
			$this->wrap_update_option( 'campaign_oneyear_popup', true );
			echo '1';
		} else {
			echo '0';
		}
		die();
	}


	/**
	 * RSSフィードのキャッシュを3時間に変更
	 */
	public function set_feed_cache_time() {
		return 60 * 60 * 3;
	}
} // end of class
