<?php
/**
 * 
 *
 * @package qa_heatmap_analytics
 */

$qahm_admin_page_home = new QAHM_Admin_Page_Home();

class QAHM_Admin_Page_Home extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-home';

	// nonce
	const NONCE_ACTION = self::SLUG . '-nonce-action';
	const NONCE_NAME   = self::SLUG . '-nonce-name';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
		$this->regist_ajax_func( 'ajax_get_session_num' );
		$this->regist_ajax_func( 'ajax_get_realtime_list' );
		$this->regist_ajax_func( 'ajax_get_json' );
//		$this->regist_ajax_func( 'ajax_url_to_page_id' );
	}

	/**
	 * 初期化
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		if( ! $this->is_enqueue_jquery() ) {
			return;
		}

		if( $this->is_maintenance() ) {
			return;
		}

		if( $this->wrap_get_option( 'plugin_first_launch' ) ) {
			$this->common_enqueue_style();
			$this->common_enqueue_script();
			$scripts = $this->get_common_inline_script();
			wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );
			$localize = $this->get_common_localize_script();
			wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
			return;
		}

		$css_dir_url = $this->get_css_dir_url();
		$js_dir_url  = $this->get_js_dir_url();

		// enqueue style
		$this->common_enqueue_style();
		wp_enqueue_style( QAHM_NAME . '-admin-page-base-css', $css_dir_url . 'admin-page-base.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-common-css', $css_dir_url . 'admin-page-home-common.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );
		//		wp_enqueue_style( QAHM_NAME . '-admin-page-home-realtime-css', $css_dir_url. 'admin-page-home-realtime.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-stats-css', $css_dir_url. 'admin-page-home-stats.css', null, QAHM_PLUGIN_VERSION );	
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-ds-css', $css_dir_url. 'admin-page-home-ds.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-daterangepicker-css', $css_dir_url . 'lib/date-range-picker/daterangepicker.css', null, QAHM_PLUGIN_VERSION );
	
		// enqueue script
		$this->common_enqueue_script();
//		wp_enqueue_script( QAHM_NAME . '-admin-page-realtime', $js_dir_url . 'admin-page-realtime.js', array( QAHM_NAME . '-admin-page-base' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-table', $js_dir_url . 'table.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-progress-bar', $js_dir_url . '/progress-bar-exec.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-chart', $js_dir_url . 'lib/chart/chart.min.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-moment-with-locales', $js_dir_url . 'lib/moment/moment-with-locales.min.js', null, QAHM_PLUGIN_VERSION, false );	
		wp_enqueue_script( QAHM_NAME . '-daterangepicker', $js_dir_url . 'lib/date-range-picker/daterangepicker.js', array( QAHM_NAME . '-moment-with-locales' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-admin-page-home-datasearch', $js_dir_url . 'admin-page-home-datasearch.js', array( 'jquery' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-admin-page-statistics', $js_dir_url . 'admin-page-statistics.js', array( QAHM_NAME . '-table', QAHM_NAME . '-chart', QAHM_NAME . '-moment-with-locales', QAHM_NAME . '-daterangepicker', QAHM_NAME . '-admin-page-home-datasearch' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-cap-create', $js_dir_url . 'cap-create.js', array( QAHM_NAME . '-admin-page-base' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-speedcheck', $js_dir_url . 'speedcheck.js', null, QAHM_PLUGIN_VERSION, false );

		// inline script
		$scripts = $this->get_common_inline_script();
		$scripts['wp_time_adj'] = get_option('gmt_offset');
		$scripts['wp_lang_set'] =  get_bloginfo('language');
		//mkadd 202206 for goal
        global $qahm_data_api;
		$scripts['goalsJson'] =  $qahm_data_api->get_goals_json();
		$scripts['siteinfoJson'] =  $qahm_data_api->get_siteinfo_json();
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		$localize['table_tanmatsu'] 		= esc_html__( 'Device', 'qa-heatmap-analytics' );
		$localize['table_ridatsujikoku'] 	= esc_html__( 'Time Session End', 'qa-heatmap-analytics' );
		$localize['table_id'] 				= esc_html__( 'ID', 'qa-heatmap-analytics' );
		$localize['table_id_tooltip']		= esc_html__( 'An ID which was uniquely assigned by QA to a user.', 'qa-heatmap-analytics' );
		$localize['table_1page_me'] 		= esc_html__( 'Landing Page', 'qa-heatmap-analytics' );
		$localize['table_ridatsu_page'] 	= esc_html__( 'Exit Page', 'qa-heatmap-analytics' );
		$localize['table_sanshoumoto'] 		= esc_html__( 'Source', 'qa-heatmap-analytics' );
		$localize['table_pv'] 				= esc_html__( 'Pageviews', 'qa-heatmap-analytics' );
		$localize['table_site_taizaijikan'] = esc_html__( 'Time on Site', 'qa-heatmap-analytics' );
		$localize['table_saisei'] 			= esc_html__( 'Replay', 'qa-heatmap-analytics' );
		$localize['table_page_title'] 		= esc_html__( 'Page Title', 'qa-heatmap-analytics' );
		$localize['table_data_total'] 		= esc_html__( 'Data Total', 'qa-heatmap-analytics' );
		$localize['table_page_version'] 	= esc_html__( 'Page Version : Span', 'qa-heatmap-analytics' );

		$localize['table_user_type'] 	      = esc_html__( 'User Type', 'qa-heatmap-analytics' );
		$localize['table_device_cat']         = esc_html__( 'Device Category', 'qa-heatmap-analytics' );
		$localize['table_user']               = esc_html__( 'Users', 'qa-heatmap-analytics' );
		$localize['table_new_user']           = esc_html__( 'New Users', 'qa-heatmap-analytics' );
		$localize['table_session']            = esc_html_x( 'Sessions', 'number of sessions', 'qa-heatmap-analytics' );
		$localize['table_bounce_rate']        = esc_html__( 'Bounce Rate', 'qa-heatmap-analytics' );
		$localize['table_page_session']       = esc_html__( 'Pages / Session', 'qa-heatmap-analytics' );
		$localize['table_avg_session_time']   = esc_html__( 'Avg. Time on Site', 'qa-heatmap-analytics' );		
		$localize['table_channel']            = esc_html__( 'Channel', 'qa-heatmap-analytics' );
		$localize['table_referrer']           = esc_html__( 'Source', 'qa-heatmap-analytics' );
		$localize['table_media']              = esc_html__( 'Medium', 'qa-heatmap-analytics' );
		$localize['table_title']              = esc_html__( 'Title', 'qa-heatmap-analytics' );
		$localize['table_url']                = esc_html__( 'URL', 'qa-heatmap-analytics' );
		$localize['table_new_session_rate']   = esc_html__( '% New Sessions', 'qa-heatmap-analytics' );
		$localize['table_edit']               = esc_html__( 'Edit', 'qa-heatmap-analytics' );
		$localize['table_heatmap']            = esc_html__( 'Heatmap', 'qa-heatmap-analytics' );
		$localize['table_past_session']       = esc_html__( 'Sessions (Earliest 7days)', 'qa-heatmap-analytics' );
		$localize['table_recent_session']     = esc_html__( 'Sessions (Latest 7days)', 'qa-heatmap-analytics' );
		$localize['table_growth_rate']        = esc_html__( 'Growth Rate', 'qa-heatmap-analytics' );
		$localize['table_page_view_num']      = esc_html__( 'Pageviews', 'qa-heatmap-analytics' );
		$localize['table_page_visit_num']     = esc_html__( 'Unique Pageviews', 'qa-heatmap-analytics' );
		$localize['table_page_avg_stay_time'] = esc_html__( 'Avg. Time on Page', 'qa-heatmap-analytics' );
		$localize['table_entrance_num']       = esc_html__( 'Entrance', 'qa-heatmap-analytics' );
		$localize['table_exit_rate']          = esc_html__( '% Exit', 'qa-heatmap-analytics' );
        $localize['table_goal_conversion_rate'] = esc_html__( 'Goal Conversion Rate', 'qa-heatmap-analytics' );
        $localize['table_goal_completions']     = esc_html__( 'Goal Completions', 'qa-heatmap-analytics' );
        $localize['table_goal_value']           = esc_html__( 'Goal Value', 'qa-heatmap-analytics' );
        $localize['table_page_value']           = esc_html__( 'Page Value', 'qa-heatmap-analytics' );

		$localize['graph_users'] 			= esc_html_x( 'Users', 'a label in a graph', 'qa-heatmap-analytics' );
		$localize['graph_sessions'] 		= esc_html_x( 'Sessions', 'a lebel in a graph', 'qa-heatmap-analytics' );
		$localize['graph_pvs'] 				= esc_html_x( 'Pageviews', 'a label in a graph', 'qa-heatmap-analytics' );
        $localize['graph_posts']            = esc_html_x( 'Posts', 'a label in a graph', 'qa-heatmap-analytics' );

        $localize['cnv_all_goals']          = esc_html__( 'All Goals', 'qa-heatmap-analytics' );
        $localize['cnv_graph_present']      = esc_html__( 'Present', 'qa-heatmap-analytics' );
		$localize['cnv_graph_goal']         = esc_html__( 'Completions Target', 'qa-heatmap-analytics' );
        $localize['cnv_graph_completions']  = esc_html__( 'Completions', 'qa-heatmap-analytics' );

		$localize['calender_kinou'] 		= esc_html_x( 'Yesterday', 'a word in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_kako7days'] 	= esc_html_x( 'Last 7 Days', 'words in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_kako30days'] 	= esc_html_x( 'Last 30 Days', 'words in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_kongetsu'] 		= esc_html_x( 'This Month', 'words in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_sengetsu'] 		= esc_html_x( 'Last Month', 'words in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_erabu'] 		= esc_html_x( 'Custom Range', 'words in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_cancel'] 		= esc_html_x( 'Cancel', 'a word in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_ok'] 			= esc_html_x( 'Apply', 'a word in a date range picker', 'qa-heatmap-analytics' );
		$localize['calender_kara']			= esc_html_x( '-', 'a connector between dates in a date range picker', 'qa-heatmap-analytics' );

		/* translators: placeholders represent the start and end dates for the download */
		$localize['download_msg1']			= esc_html__( 'Download the data from %1$s to %2$s.', 'qa-heatmap-analytics' );
		$localize['download_msg2']			= esc_html__( '*If the data size is too large, depending on the server, it may not be possible to download. In that case, try shortening the date range.', 'qa-heatmap-analytics' );
		/* translators: placeholders represent the start and end dates for the download */
		$localize['download_done_nodata']	= esc_html__( 'No data between %1$s and %2$s.', 'qa-heatmap-analytics' );
		$localize['download_error1']		= esc_html__( 'A communication error occurred when acquiring data.', 'qa-heatmap-analytics' );
		$localize['download_error2']		= esc_html__( 'It may be acquired too much data. Please shorten the date range and try again. (It depends on the server, but in general, it would be better to make the total number of PVs for the period less than 10,000.)', 'qa-heatmap-analytics' );
		$localize['ds_download_msg1']		= esc_html__( 'Download the pageview data matched with the chosen conversion.', 'qa-heatmap-analytics' );
		$localize['ds_download_error1']		= esc_html__( 'No data to download.', 'qa-heatmap-analytics' );
		$localize['ds_download_done_nodata'] = esc_html__( 'No matched data.', 'qa-heatmap-analytics' );


		$localize['ds_cyusyutsu_kensu'] 	= esc_html_x( 'session results', 'displaying the number of filtered sessions', 'qa-heatmap-analytics' );
		$localize['ds_cyusyutsu_button'] 	= esc_html_x( 'Apply', 'value of the button in narrow-down-data-section', 'qa-heatmap-analytics' );
		$localize['ds_cyusyutsu_cyu'] 		= esc_html_x( 'Filtering...', 'value of the button in narrow-down-data-section', 'qa-heatmap-analytics' );
		$localize['ds_cyusyutsu_error1'] 	= esc_html_x( ': NO data found.', 'error message1 in narrow-down-data-section', 'qa-heatmap-analytics' );
		$localize['ds_cyusyutsu_error2'] 	= esc_html_x( 'A communication error occurred when acquiring data. It may be acquired too much data. Please narrow down the condition and try again.', 'error message2 in narrow-down-data-section', 'qa-heatmap-analytics' );
		/* translators: placeholders represent the number of days for displaying report data */
		$localize['ds_cyusyutsu_error3'] 	= esc_html_x( 'Showing for the last %d day(s) data because of too many data.', 'error message1 in narrow-down-data-section', 'qa-heatmap-analytics' );
		$localize['ds_free_plan_msg1']	 	= esc_html_x( 'Would like to see the data of all pages?', 'a message appears if only one page can be measured.', 'qa-heatmap-analytics' );
		/* translators: placeholders are for the link */
		$localize['ds_free_plan_msg2']	 	= esc_html_x( '%1$s Get Upgrade Options %2$s, have event data collected without page limit.', 'a message appears if only one page can be measured.', 'qa-heatmap-analytics' );


		$localize['tjs_open_filter']  			= esc_attr_x( 'Show Filter', 'a button appears above a table', 'qa-heatmap-analytics' );
		$localize['tjs_close_filter'] 			= esc_attr_x( 'Hide Filter', 'a button appears above a table', 'qa-heatmap-analytics' );
		$localize['tjs_clear_filter'] 			= esc_html_x( 'CLEAR ALL Filter', 'a-button-like words appears above a table', 'qa-heatmap-analytics' );
		$localize['tjs_komejirushi']	 		= '';
		$localize['tjs_howto_use_filter'] 		= '';
		$localize['tjs_sort']					= esc_html_x( 'Sort', 'word(s) in a table filter funcion', 'qa-heatmap-analytics' );
		$localize['tjs_filter'] 				= esc_html_x( 'Filter', 'word(s) in a table filter funcion', 'qa-heatmap-analytics' );
		$localize['tjs_word_for_filter'] 		= esc_attr_x( 'filtering word', 'word(s) in a table filter funcion', 'qa-heatmap-analytics' );
		$localize['tjs_include'] 				= esc_html_x( 'include', 'word(s) in a table filter funcion', 'qa-heatmap-analytics' );
		$localize['tjs_not_include'] 			= esc_html_x( 'exclude', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_select_all'] 			= esc_html_x( 'Select all', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_filter_equal'] 			= esc_html_x( '(equal)', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_filter_lt'] 				= esc_html_x( '(and less)', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_filter_gt'] 				= esc_html_x( '(and more)', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_filter_data_tani_currency'] = esc_html_x( 'yen', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_filter_data_tani_second']   = esc_html_x( 'second(s)', 'word(s) in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_alert_msg_over500'] 		= esc_html_x( '"Select all" cannot be used when over 500 selectors exist. Please filter and make them less.', 'an alert in a table filter function', 'qa-heatmap-analytics' );
		$localize['tjs_alert_msg_over100']		= esc_html_x( '"Select all" cannot be used when over 100 selectors exist. Please filter and make them less.', 'an alert in a table filter function', 'qa-heatmap-analytics' );

        $localize['good'] = esc_html__( 'Good', 'qa-heatmap-analytics' );
        $localize['caution'] = esc_html__( 'Caution', 'qa-heatmap-analytics' );

		$localize['result_cannot_be_all_pages'] = esc_html__( 'It will return all pages as results. Please specify more specific criteria.', 'qa-heatmap-analytics' );
		/* translators: placeholders represents the number of pages for displaying report data */
		$localize['too_many_result_pages_msg'] = esc_html__('The number of pages exceeds %d. Aggregating all results may not be feasible. Consider refining the search parameters or narrowing down the time period. Is it okay to proceed with the current search settings?', 'qa-heatmap-analytics');

		wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
	}

	/**
	 * ページの表示
	 */
	public function create_html() {
		if( ! $this->is_enqueue_jquery() ) {
			$this->view_not_enqueue_jquery_html();
			return;
		}

		if( $this->is_maintenance() ) {
			$this->view_maintenance_html();
			return;
		}

		if( $this->wrap_get_option( 'plugin_first_launch' ) ) {
			$this->view_first_launch_html();
			return;
		}

		$lang_set = get_bloginfo('language');
		if ( $lang_set == 'ja' ) {
			$upgrade_link_atag = '<a href="' . esc_url('https://quarka.org/plan/') . '" target="_blank" rel="noopener">';
			$referral_link_atag = '<a href="' . esc_url('https://quarka.org/referral-program/') . '" target="_blank" rel="noopener">';
		} else {
			$upgrade_link_atag = '<a href="' . esc_url('https://quarka.org/en/#plans') . '" target="_blank" rel="noopener">';
			$referral_link_atag = '<a href="' . esc_url('https://quarka.org/en/referral-program/') . '" target="_blank" rel="noopener">';
		}
		$is_subscribed = $this->is_subscribed();
		?>
		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap qa-admin-home">
				<h1>QA <?php esc_html_e( 'Home', 'qa-heatmap-analytics' ); ?></h1>
                <div class="bl_news flex_item">
                    <?php $this->view_rss_feed(); ?>
                    <?php //$this->view_announce_html(); ?>
                </div>
            <?php
                global $qahm_data_api;
                global $qahm_time;
				if ( ! $is_subscribed ) {
					$msg_yuryouikaga = '<div class="qahm-using-free-announce"><span class="qahm_margin-right4"><span class="dashicons dashicons-megaphone"></span></span><span class="qahm_fontsize12em">';					
					$msg_yuryouikaga .= sprintf(
						/* translators: placeholders are for the link */ 
						esc_html__( 'Upgrade for better insights! Gain more PV capacity for free by %1$sreferring friends%2$s, or choose our %3$sPremium Plan%4$s to increase PV limits and unlock more goals.', 'qa-heatmap-analytics' ), $referral_link_atag, '</a>', $upgrade_link_atag, '</a>'
					);
                    $msg_yuryouikaga .= '</span></div>';
                    echo wp_kses_post($msg_yuryouikaga);
				}
                global $qahm_data_api;
                global $qahm_time;

                $allposts  = $qahm_data_api->get_each_posts_count(1);
                $MAXPV     = 10000;
				$measure   = $this->get_license_option( 'measure' );
                if ( $measure ) {
                    $MAXPV = $measure;
                }
                $MAXPV_STR   = number_format($MAXPV);
                $MAXDATATERM = 90;
                $ALL_POSTS   = number_format($allposts[0]);
                $MAX_HDD_B   = 5000000000;
                $BYTE_2_GB   = 1000000000;
                $FREEHTML    = '<span class="freetext">' . esc_html__( 'FREE version', 'qa-heatmap-analytics' ) . '</span>';
                $PAIDHTML    = '<span class="paidtext"><i class="fas fa-award"></i>&nbsp;Licensed</span>';
                $WARNHTML    = '<span class="el_warning"><i class="fa fa-exclamation-circle"></i>' . esc_html__( 'Warning', 'qa-heatmap-analytics' ) . '</span>';
                $max_hdd_gb  = $MAX_HDD_B / $BYTE_2_GB;

				$pv_limit_over_html = '';

                $this_month_pv = $qahm_data_api->count_this_month_pv();
				if ( $this_month_pv > $MAXPV ) {
					$this_month_pv = $MAXPV;
					$pv_limit_over_html =  '<span class="pv_warning">' . $referral_link_atag . esc_html__( 'PV Limit Exceeded', 'qa-heatmap-analytics' ) . '</a></span>';
				}
                $files_ary     = $qahm_data_api->count_files();
                $files_size    = $files_ary['size'];

                //pv
                $pvstart_date  = $qahm_data_api->get_pvterm_start_date();
                $pvstart_yyyy  = (int)substr( $pvstart_date , 0, 4 );
                $pvstart_mm    = (int)substr( $pvstart_date , 5, 2 );
                $pvstart_dd    = (int)substr( $pvstart_date , 8, 2 );

                //データ保存期間
				$used_day      = $qahm_time->xday_num( $qahm_time->today_str(), $pvstart_date );

                //file
                $used_gb       = round( $files_size / $BYTE_2_GB, 2);

                //利用状況
                if ( ! $is_subscribed ) {
                    $freehtml  = $FREEHTML;
                } else {
                    $freehtml  = $PAIDHTML;
                }

				// ここでデータ保存期間の設定を取得
				$data_retention_dur = $this->wrap_get_option( 'data_retention_dur' );
				if ( $data_retention_dur ) {
					$MAXDATATERM = $data_retention_dur;
				}

                //---progress bar value and class value
                //pv term
                $prg_used_day = ceil( $used_day / $MAXDATATERM * 100);
                $cls_used_day = 'qagreen';
                if ( 80 < $prg_used_day ) {
                    $cls_used_day = 'qapink';
                }elseif ( 95 < $prg_used_day ) {
                    $cls_used_day = 'qapinklimit';
                }

                //pv count
                $prg_used_pv    = ceil( $this_month_pv / $MAXPV * 100);
                $cls_used_pv    = 'qagreen';
                $warning_html   = '';
                if ( 75 < $prg_used_pv ) {
                    $cls_used_pv = 'qapink';
                    $warning_html  = $WARNHTML;
                }elseif ( 90 < $prg_used_pv ) {
                    $cls_used_pv = 'qapinklimit';
                    $warning_html  = $WARNHTML;
                }

                //file
                $prg_used_size  = ceil( $files_size / $MAX_HDD_B * 100);
                $cls_used_size  = 'qagreen';
                if ( 80 < $prg_used_size ) {
                    $cls_used_size = 'qapink';
                }elseif ( 95 < $prg_used_size ) {
                    $cls_used_size = 'qapinklimit';
                }


                $ja_en_words = array(
                    'riyou-jyoukyou'  => esc_html__( 'Data Storage: Current / Max', 'qa-heatmap-analytics' ),
                    'gekkan-pv'       => esc_html__( 'This Month PVs', 'qa-heatmap-analytics' ), //月間PV数
                    'kikan-data'      => esc_html__( 'Data Retention Period', 'qa-heatmap-analytics' ),
                    'niti'            => esc_html__( 'days', 'qa-heatmap-analytics' ), //日
                    'ka-getsu'        => esc_html__( 'months', 'qa-heatmap-analytics' ), //ヶ月
                    'hozon-data'      => esc_html__( 'Stored Data Size', 'qa-heatmap-analytics' ),
                    'data-plan'       => esc_html__( 'Upgrade', 'qa-heatmap-analytics' )
                );

				$cm_friendplan = '';
	 			if ( ! $is_subscribed ) { 
					$cm_friendplan = '<p><strong>';
					$cm_friendplan .= esc_html__( 'Running short on PV capacity?', 'qa-heatmap-analytics' );
					$cm_friendplan .= '<br>';
					/* translators: placeholders are for the link */
					$cm_friendplan .= sprintf( esc_html__( '%1$sRefer friends%2$s to expand it!', 'qa-heatmap-analytics' ), $referral_link_atag, '</a>' );
					$cm_friendplan .= '</strong></p>';

	 			} 

$use_status = <<< EOF
                <div class="bl_infobox flexitem">
                    <h3 class="el_useStatus"><span style="display: inline-block"><span class="dashicons dashicons-archive"></span> {$ja_en_words['riyou-jyoukyou']}</span></h3>
					<p class="free-or-lic">${freehtml}</p>
                    <div class="bl_datalimits">
						<div class="datalimit_sec">
							<div class="el_useStatus"><span class="limit_label">{$ja_en_words['gekkan-pv']}</span> <span class="used_label"><span class="used_num">{$this_month_pv}</span>&nbsp;<span class="limit_bunbo">/ {$MAXPV_STR}&nbsp;</span>PV{$warning_html}</span></div>
							<div><progress value="{$prg_used_pv}" max="100" class="{$cls_used_pv}">{$prg_used_pv}%</progress></div>
							{$cm_friendplan}
						</div>
						<div class="datalimit_sec">
							<div class="el_useStatus"><span class="limit_label">{$ja_en_words['kikan-data']}</span> <span class="used_label"><span class="used_num">{$used_day}</span> <span class="limit_bunbo">/ {$MAXDATATERM}&nbsp;</span>{$ja_en_words['niti']}</span></div>
							<div><progress value="{$prg_used_day}" max="100" class="{$cls_used_day}">{$prg_used_day}%</progress></div>
						</div>
						<div class="datalimit_sec">
							<div class="el_useStatus"><span class="limit_label">{$ja_en_words['hozon-data']}</span> <span class="used_label"><span class="used_num">{$used_gb}</span> <span class="limit_bunbo">/ {$max_hdd_gb}&nbsp;</span>GB</span></div>
							<div><progress value="{$prg_used_size}" max="100" class="{$cls_used_size}">{$prg_used_size}%</progress></div>
						</div>
                    </div>
					<div style="margin-top: 28px;">
                    	<a href="https://quarka.org/en/#plans" target="_blank" rel="noopener"><div class="btn"><p>{$ja_en_words['data-plan']}</p></div></a>
					</div>
                </div>
EOF;
            $g_endday      = $qahm_time->xday_str( -1 );
            $g_end_ym      = substr( $g_endday, 0, 7 );
            $g_sttday      = $qahm_time->xmonth_str( -1, $g_end_ym . '-01');
            $g_init_term   = 'date = between ' . $g_sttday . ' and ' . $g_endday;
			$g_session_ary = $qahm_data_api->get_goals_sessions( $g_init_term );
			$g_lmon_cv = 0;
			$g_nmon_cv = 0;
			$g_nmon_01 = $g_end_ym . '-01 00:00:00';
            $g_nmon_01_utime = $qahm_time->str_to_unixtime( $g_nmon_01 );
            $gid0ary = [];
            foreach ( $g_session_ary as $gid => $sessions ) {
                $gid0ary = array_merge( $gid0ary, $sessions );
            }
            $g_session_ary[0] = $gid0ary;
			foreach ( $g_session_ary[0] as $sessions ) {
			    $lp_utime = $qahm_time->str_to_unixtime( $sessions[0]['access_time'] );
			    if ($lp_utime < $g_nmon_01_utime ) {
			        $g_lmon_cv++;
                }else{
			        $g_nmon_cv++;
                }
            }
            $g_n_lastday = (new DateTimeImmutable())->modify('last day of' . $g_end_ym )->format('j');
			$g_n_nowday  = (new DateTime($g_endday))->format('j');
			$past_days   = $g_n_nowday;
			if ( $g_end_ym === ($pvstart_yyyy . '-' . $pvstart_mm) ) {
                $past_days = $g_n_nowday - (int)$pvstart_dd + 1;
            }
            if ( $past_days !== 0 && $g_n_lastday !== 0 ) {
    			$g_estimate = round( $g_nmon_cv / $past_days * $g_n_lastday );
            } else {
			    $g_estimate = 0;
            }
            //ゴール計算は時間がかかるのでajaxをなるべく避けて事前処理
            $g_2mon_json = wp_json_encode( $g_session_ary );
            if ( $g_2mon_json ) {
				?>
            <script>
				if ( qahm !== undefined ) {
                    qahm.g2monSessionsJson = <?php echo wp_json_encode( $g_session_ary ); ?>;
				}			
			</script> 
			<?php
            }
            ?>


            <!--ダッシュボード-->
            <div class="bl_dashBoardH">
				<h2 id="h_dashboard" ><?php esc_html_e( 'Dashboard', 'qa-heatmap-analytics' ); echo wp_kses_post($pv_limit_over_html); ?></h2>
			</div>
            <div id="bl_dashBoard" class="bl_infoArea">
                <div class="bl_dashBoard flex">
                    <div class="bl_dashContents flexitem">
                        <div class="flex_column">

                            <div class="bl_grapharea flex_item">
                                <div class="flex">
                                    <div class="bl_data flex_item">
                                        <h3><i class="fas fa-user"></i>  <?php echo esc_html_x( 'Sessions', 'number of sessions', 'qa-heatmap-analytics' ); ?></h3>
                                        <table>
                                            <thead><th><?php esc_html_e( 'Last Month', 'qa-heatmap-analytics' ); ?></th><th><span class="qa-view-count-info qahm-tooltip" data-qahm-tooltip="<?php esc_attr_e( 'until yesterday', 'qa-heatmap-analytics' ); ?>"><?php esc_html_e( 'Month-to-Date', 'qa-heatmap-analytics' ); ?></span></th></thead>
                                            <tbody>
                                                <tr><td id="js_last_mn_sessions">0</td><td id="js_this_mn_sessions">0<br></td></tr>
                                                <tr class="yosoku"><td>&nbsp;</td><td>( <?php esc_html_e( 'Current Forecast', 'qa-heatmap-analytics' ); ?>: <span id="js_this_mn_estimate">0</span> )<br></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="bl_graph flex_item">
                                        <div style="width:500px">
                                          <canvas id="access_graph" width="500px" height="200px"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="bl_grapharea flex_item">
                                <div class="flex">
                                    <div class="bl_data flex_item">
                                        <h3><i class="fas fa-crosshairs"></i>  <?php esc_html_e( 'Goals', 'qa-heatmap-analytics' ); ?></h3>
                                        <table>
                                            <thead><th><?php esc_html_e( 'Last Month', 'qa-heatmap-analytics' ); ?></th><th><span class="qa-view-count-info qahm-tooltip" data-qahm-tooltip="<?php esc_attr_e( 'until yesterday', 'qa-heatmap-analytics' ); ?>"><?php esc_html_e( 'Month-to-Date', 'qa-heatmap-analytics' ); ?></span></th></thead>
                                            <tbody>
                                                <tr><td id="js_last_mn_cv_sessions"><?php echo esc_html($g_lmon_cv); ?></td><td id="js_this_mn_cv_sessions"><?php echo esc_html($g_nmon_cv); ?><br></td></tr>
                                                <tr class="yosoku"><td>&nbsp;</td><td>( <?php esc_html_e( 'Current Forecast', 'qa-heatmap-analytics' ); ?>: <span id="js_this_mn_cv_estimate"><?php echo esc_html($g_estimate); ?></span> )<br></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="bl_graph flex_item">
                                        <div style="width:500px">
                                          <canvas id="conversion_graph" width="500px" height="200px"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bl_grapharea flex_item">
                                <div class="flex">
                                <div class="topreport_halfcolumn_ch">
										<h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Channel', 'qa-heatmap-analytics' ); ?></h3>
										<div class="topreport_channel_circle">
											<div style="width:250px;">
												<canvas id="channel_graph" width="250px" height="200px"></canvas>
											</div>
										</div>
									</div>
									<div class="topreport_halfcolumn_post">
										<h3><span class="dashicons dashicons-admin-post"></span>  <?php esc_html_e( 'Post Count', 'qa-heatmap-analytics' ); ?></h3>
										<div class="topreport_posts_sect">
											<div class="topreport_table_half">
												<?php
													global $qahm_data_api;
													$postcnt_ary = $qahm_data_api->get_each_posts_count(2);
													$postcnt_new = $postcnt_ary[0] - $postcnt_ary[1];
												?>
												<table>
													<thead><th><?php esc_html_e( 'Current Post Count', 'qa-heatmap-analytics' ); ?></th></thead>
													<tbody>
														<tr><?php echo '<td>' . esc_html($postcnt_ary[0]) . '<br></td>'; ?></tr>
														<tr class="posttotal"><td>( <?php esc_html_e( 'New in This Month', 'qa-heatmap-analytics' ); ?>: <span><?php echo esc_html($postcnt_new); ?></span> )<br></td></tr>
													</tbody>
												</table>
											</div>
											<div class="topreport_graph">
													<div style="width:250px;">
													<canvas id="post_count" width="250px" height="200px"></canvas>
													</div>
											</div>
										</div>
									</div>
                                </div>
                            </div>
                            <div class="bl_report flex_item">
									<h3><span class="dashicons dashicons-analytics"></span> <?php esc_html_e( 'Reports', 'qa-heatmap-analytics' ); ?></h3>
									<div class="flex">
										<div class="bl_reportmenu flex_item">
											<h4><a href="#h_audience"><?php esc_html_e( 'Audience', 'qa-heatmap-analytics' ); ?></a></h4>
											<ul>
												<li><a href="#h_allpv"><?php esc_html_e( 'Overview', 'qa-heatmap-analytics' ); ?></a></li>
												<li><a href="#h_audienceDevice"><?php esc_html_e( 'Users / Device', 'qa-heatmap-analytics' ); ?></a></li>
											</ul>
										</div>
										<div class="bl_reportmenu flex_item">
											<h4><a href="#h_acquisition"><?php esc_html_e( 'Acquisition', 'qa-heatmap-analytics' ); ?></a></h4>
											<ul>
												<li><a href="#h_channels"><?php esc_html_e( 'Channel', 'qa-heatmap-analytics' ); ?></a></li>
												<li><a href="#h_sourceMedium"><?php esc_html_e( 'Source / Medium', 'qa-heatmap-analytics' ); ?></a></li>
											</ul>
										</div>
										<div class="bl_reportmenu flex_item">
											<h4><a href="#h_behavior"><?php esc_html_e( 'Behavior', 'qa-heatmap-analytics' ); ?></a></h4>
											<ul>
												<li><a href="#h_landingpage"><?php esc_html_e( 'Landing Pages', 'qa-heatmap-analytics' ); ?></a></li>
												<li><a href="#h_growthpage"><?php esc_html_e( 'Growing Landing Pages', 'qa-heatmap-analytics' ); ?></a></li>
												<li><a href="#h_allpage"><?php esc_html_e( 'All Pages', 'qa-heatmap-analytics' ); ?></a></li>
											</ul>
										</div>
										<div class="bl_reportmenu flex_item">
											<h4><a href="#h_conversion"><?php esc_html_e( 'Conversions', 'qa-heatmap-analytics' ); ?></a></h4>
											<ul>
												<li><a href="#h_finduser"><?php esc_html_e( 'Goals', 'qa-heatmap-analytics' ); ?></a></li>
												<li><a href="#h_finduser"><?php esc_html_e( 'Specifying Page', 'qa-heatmap-analytics' ); ?></a></li>
												<ul>
													<li>- <?php esc_html_e( 'Heatmap', 'qa-heatmap-analytics' ); ?></li>
													<li>- <?php esc_html_e( 'Session Replay', 'qa-heatmap-analytics' ); ?></li>
												</ul>
											</ul>
										</div>
									</div>
								</div>
                        </div>
                    </div>
                    <div class="bl_anotation flexitem">
                        <div class="flex_column">
							<?php
							echo wp_kses($use_status, array(
								'div' => array(
									'class' => array(),
									'style' => array(),
								),
								'h3' => array(
									'class' => array(),
									'span' => array(),
									'style' => array(),
								),
								'span' => array(
									'class' => array(),
									'style' => array(),
								),
								'p' => array(
									'class' => array(),
								),
								'a' => array(
									'href' => array(),
									'target' => array(),
									'rel' => array(),
								),
								'progress' => array(
									'value' => array(),
									'max' => array(),
									'class' => array(),
								),
								'br' => array(),
								'strong' => array(),
								'i' => array(
									'class' => array(),
								),
							));							
							?>
							<div class="bl_infobox flexitem">
								<h3><span class="dashicons dashicons-performance"></span>  <?php esc_html_e( 'Page Speed of QA', 'qa-heatmap-analytics' ); ?></h3>
								<div class="status" id="js_speedstatus"></div>
								<div class="loadsec" id="js_speedsec"></div>
								<script>
									window.addEventListener('DOMContentLoaded', function() {
										let statusdiv  = document.getElementById('js_speedstatus');
										let speed_div  = document.getElementById('js_speedsec');

										//each value are "sec"
										let clientspeed = qahm.clientLoadTime;
										let serverspeed = <?php global $qahm_loadtime; echo esc_js($qahm_loadtime); ?>;
										let allspeed = Number(clientspeed) + Number(serverspeed);
										let roundallspeed = Math.round(allspeed*10000)/10;

										if ( allspeed > 0.2 ) {
											statusdiv.innerHTML = '<p class="qapink"><i class="fas fa-exclamation-circle"></i> ' + qahml10n['caution'] + '</p>';
											speed_div.innerHTML = `<p class="qapink">(  ${roundallspeed} ms  )</p>`;
										} else {
											statusdiv.innerHTML = '<p class="qasafegreen"><i class="fas fa-check-circle"></i> ' + qahml10n['good'] + '</p>';
											speed_div.innerHTML = `<p class="qagreen">(  ${roundallspeed} ms  )</p>`;
										}
									});
								</script>
							</div>
							<div class="bl_infobox flexitem">
								<a href="./admin.php?page=qahm-dataportal"><div class="btn_gls"><p><i class="fab fa-google"></i> <?php esc_html_e( 'Looker Studio Connector', 'qa-heatmap-analytics' ); ?></p></div></a>
							</div>
							<div class="bl_infobox flexitem">
								<a href="https://mem.quarka.org/en/manual/" target="_blank" rel="noopener"><div class="btn"><p><i class="fas fa-question-circle"></i> <?php esc_html_e( 'User Guide / Manual', 'qa-heatmap-analytics' ); ?></p></div></a>
							</div>
                        </div>
                    </div>
                </div>
            </div>

            <!--カレンダー固定メニュー-->
            <div id="bl_calStickey">
                <div id="bl_calenderAndMenu">
                    <div class="flex" style="align-items:flex-end">
                        <div class="flex_item">
                            <p><span class="qahm_margin-right4"><i class="fas fa-calendar-check"></i></span><?php esc_html_e( 'Date Range', 'qa-heatmap-analytics' ); ?></p>
                            <div class="datepicker-frame">
                                    <div class="chart-reportrange" id="pv-chart-reportrange">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span></span> <i class="fa fa-caret-down"></i>
                                    </div>
                            </div>
                        </div>
                        <div class="flex_item" style="width:100%;text-align: right"><p><a href="#class-qahm-admin-page-home">TOP</a>  |  <a href="#h_audience"><?php esc_html_e( 'Audience', 'qa-heatmap-analytics' ); ?></a>  |  <a href="#h_acquisition"><?php esc_html_e( 'Acquisition', 'qa-heatmap-analytics' ); ?></a>  |  <a href="#h_behavior"><?php esc_html_e( 'Behavior', 'qa-heatmap-analytics' ); ?></a>  |  <a href="#h_conversion"><?php esc_html_e( 'Conversions', 'qa-heatmap-analytics' ); ?></a></p></div>
                    </div>
                </div>
                <!--ユーザー-->
                <div class="bl_reportField">
                    <h2 id="h_audience"><?php esc_html_e( 'Audience', 'qa-heatmap-analytics' ); ?></h2>
                    <div id="stats_container">
                        <div class="bl_contentsArea">
                            <!--ユーザー開始-->
                            <h3 id="h_allpv"><?php esc_html_e( 'Overview', 'qa-heatmap-analytics' ); ?></h3>
                            <div class="qa-chart-access">
                                    <div id="chart1-legend" class="grf-legend"></div>
                                    <div class="chart-container">
                                    <canvas id="statsChart1"></canvas>
                                    </div>
                            </div>
                            <div class="stats-open" id="stats-total">
                                    <div class="qa-chart-access-view">
                                            <div class="qa-view-count-box">
                                                    <span class="dashicons dashicons-groups"></span>
                                                    <span id="qa-num-readers" class="qa-view-count qa-count"><span style="font-size:24px;">counting...</span></span>
                                                    <span class="qa-view-count-info qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'Total number of Users who have initiated at least one session during the date range.', 'qa-heatmap-analytics' ); ?>"><?php esc_html_e( 'Users', 'qa-heatmap-analytics' ); ?></span>
                                            </div>
                                            <div class="qa-view-count-box">
                                                    <span class="stats-icons"><i class="fas fa-door-open"></i></span>
                                                    <span id="qa-num-sessions" class="qa-view-count qa-count"><span style="font-size:24px;">counting...</span></span>
                                                    <span class="qa-view-count-info qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'Total number of Sessions within the date range. A session is the period time a user is actively engaged with your website.', 'qa-heatmap-analytics' ); ?>"><?php echo esc_html_x( 'Sessions', 'number of sessions', 'qa-heatmap-analytics' ); ?></span>
                                            </div>
                                            <div class="qa-view-count-box">
                                                    <span class="dashicons dashicons-admin-page"></span>
                                                    <span id="qa-num-pvs" class="qa-view-count qa-count"><span style="font-size:24px;">counting...</span></span>
                                                    <span class="qa-view-count-info qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'Total number of Pages Viewed. Repeated views of a single page are counted.', 'qa-heatmap-analytics' ); ?>"><?php esc_html_e( 'Pageviews', 'qa-heatmap-analytics' ); ?></span>
                                            </div>
                                    </div>
                            </div>
                            <div class="qahm_textalign_right">
                                <input type="button" id="csv-download-btn" value="<?php esc_attr_e( 'Download Data (as TSV)', 'qa-heatmap-analytics' ); ?>">
                            </div>
                        </div>
                        <div class="bl_contentsArea">
                            <h3 id="h_audienceDevice"><?php esc_html_e( 'User Type / Device Category', 'qa-heatmap-analytics' ); ?></h3>
                            <div class="bl_goalradio">
                            <?php
                                $goals_ary = $qahm_data_api->get_goals_array();
                                echo '<label for="js_nrdGoals_0"><input type="radio" id="js_nrdGoals_0" name="js_nrdGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                                foreach ( $goals_ary as $gid => $goal ) {
                                    echo '<label for="', esc_attr('js_nrdGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_nrdGoals_'. $gid), '" name="js_nrdGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                                }
                            ?>
                            </div>
                            <div id="pg_audienceDevice"></div>
                            <div id="tb_audienceDevice" class="tablejs_default"></div>
                        </div>
                    </div><!-- end of #stats_container -->
                </div>

                <!--集客-->
                <div class="bl_reportField">
                    <h2 id="h_acquisition"><?php esc_html_e( 'Acquisition', 'qa-heatmap-analytics' ); ?></h2>
                    <div class="bl_contentsArea">
                        <h3 id="h_channels"><?php esc_html_e( 'Channel', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="bl_goalradio">
                        <?php
                            $goals_ary = $qahm_data_api->get_goals_array();
                            echo '<label for="js_chGoals_0"><input type="radio" id="js_chGoals_0" name="js_chGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                            foreach ( $goals_ary as $gid => $goal ) {
                                echo '<label for="', esc_attr('js_chGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_chGoals_'. $gid), '" name="js_chGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                            }
                        ?>
                        </div>
                        <div id="pg_channels"></div>
                        <div id="tb_channels" class="tablejs_default"></div>
                        <hr class="el_viewseparator">
                        <h3 id="h_sourceMedium"><?php esc_html_e( 'Source / Medium', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="bl_goalradio">
                        <?php
                            $goals_ary = $qahm_data_api->get_goals_array();
                            echo '<label for="js_smGoals_0"><input type="radio" id="js_smGoals_0" name="js_smGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                            foreach ( $goals_ary as $gid => $goal ) {
                                echo '<label for="', esc_attr('js_smGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_smGoals_'. $gid), '" name="js_smGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                            }
                        ?>
                        </div>
                        <div id="pg_sourceMedium"></div>
                        <div id="tb_sourceMedium" class="tablejs_default"></div>
                    </div>
                </div>

                <!--行動-->
                <div class="bl_reportField">
                    <h2 id="h_behavior"><?php esc_html_e( 'Behavior', 'qa-heatmap-analytics' ); ?></h2>
                    <div class="bl_contentsArea">
                        <h3 id="h_landingpage"><?php esc_html_e( 'Landing Pages', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="bl_goalradio">
                        <?php
                            $goals_ary = $qahm_data_api->get_goals_array();
                            echo '<label for="js_lpGoals_0"><input type="radio" id="js_lpGoals_0" name="js_lpGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                            foreach ( $goals_ary as $gid => $goal ) {
                                echo '<label for="', esc_attr('js_lpGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_lpGoals_'. $gid), '" name="js_lpGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                            }
                        ?>
                        </div>
                        <div id="pg_landingpage"></div>
                        <div id="tb_landingpage" class="tablejs_default"></div>
                        <hr class="el_viewseparator">
                        <h3 id="h_growthpage"><?php esc_html_e( 'Growing Landing Pages', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="bl_goalradio">
                        <?php
                            $goals_ary = $qahm_data_api->get_goals_array();
                            echo '<label for="js_gwGoals_0"><input type="radio" id="js_gwGoals_0" name="js_gwGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                            foreach ( $goals_ary as $gid => $goal ) {
                                echo '<label for="', esc_attr('js_gwGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_gwGoals_'. $gid), '" name="js_gwGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                            }
                        ?>
                        </div>
                        <div id="pg_growthpage"></div>
                        <div id="tb_growthpage" class="tablejs_default"></div>
                        <hr class="el_viewseparator">
                        <h3 id="h_allpage"><?php esc_html_e( 'All Pages', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="bl_goalradio">
                        <?php
                            $goals_ary = $qahm_data_api->get_goals_array();
                            echo '<label for="js_apGoals_0"><input type="radio" id="js_apGoals_0" name="js_apGoals" checked>'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                            foreach ( $goals_ary as $gid => $goal ) {
                                echo '<label for="', esc_attr('js_apGoals_'. $gid), '"><input type="radio" id="', esc_attr('js_apGoals_'. $gid), '" name="js_apGoals">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                            }
                        ?>
                        </div>
                        <div id="pg_allpage"></div>
                        <div id="tb_allpage" class="tablejs_default"></div>
                    </div>
                </div>

                <!--Goal-->
                <div class="bl_reportField">
                    <h2 id="h_conversion"><?php esc_html_e( 'Conversions', 'qa-heatmap-analytics' ); ?></h2>

                     <div id="extraction-view-container" class="bl_contentsArea">
						<div class="goal_graph_container">
						<div><canvas id="cvConversionGraph" style="height: 300px"></canvas></div>
						</div>
                        <div style="width: 100%">
                            <p style="text-align: right"><a href="admin.php?page=qahm-config"><?php esc_html_e( 'Edit Goals', 'qa-heatmap-analytics' ); ?></a></p>
                        </div>


						<h3 id="h_finduser"><?php esc_html_e( 'Explore', 'qa-heatmap-analytics' ); ?></h3>
						<p><?php esc_html_e( 'Toggle the data displayed with each goal\'s radio button. Alternatively, specify a specific page to display the data.', 'qa-heatmap-analytics' ); ?></p>

						<div class="goal_graph_container">
							<div class="flex">
							<?php
							$gcomplete = esc_html__( 'Goal Completions', 'qa-heatmap-analytics' );
							$gvalue    = esc_html__( 'Goal Value', 'qa-heatmap-analytics' );
							$goalrate  = esc_html__( 'Goal Conversion Rate', 'qa-heatmap-analytics' );

							$goals_ary = $qahm_data_api->get_goals_array();
							$goals_ary[0] = ['gtitle' => esc_html__( 'All Goals', 'qa-heatmap-analytics' )];

							for ($gid = 0; $gid < count($goals_ary); $gid++) {
								$gtitle = esc_html(urldecode($goals_ary[$gid]['gtitle'])); // エスケープ処理
								$checked = '';
								$checkedclass = '';
								if ($gid === 0) {
									$checked = 'checked';
									$checkedclass = 'bl_goalBoxChecked';
								}
								
								$gsession_selector = <<< EOL
								<div class="flex_item bl_goalBoxFlex bl_goalBox bl_goalAll {$checkedclass}">
									<p><input type="radio" name="js_gsession_selector" id="js_gsession_selector_{$gid}" {$checked}><label class="el_bold" for="js_gsession_selector_{$gid}">{$gtitle}</label></p>
									<div><canvas id="js_gssCanvas_{$gid}" width="250px" height="200px"></canvas></div>
									<div class="bl_goalSummary">
										<div class="flex">
											<div class="flex_item">
												<p>{$gcomplete}<br><span id="js_gcomplete_{$gid}">0</span></p>
											</div>
											<div class="flex_item">
												<p>{$gvalue}<br><span id="js_gvalue_{$gid}">0</span></p>
											</div>
											<div class="flex_item">
												<p>{$goalrate}<br><span id="js_gcvrate_{$gid}">0</span></p>
											</div>
										</div>
									</div>
								</div>
EOL;
								
								// $gsession_selectorを出力
								echo wp_kses($gsession_selector, array(
									'div' => array('class' => array(), 'id' => array()),
									'p' => array(),
									'input' => array('type' => array(), 'name' => array(), 'id' => array(), 'checked' => array()),
									'label' => array('for' => array(), 'class' => array()),
									'canvas' => array('id' => array(), 'width' => array(), 'height' => array()),
									'span' => array('id' => array()),
									'br' => array(),
								));
							}
							?>

                        	</div>
						</div>
                        <div class="bl_goalBox goalsearch_sec">
							<p style="display:none;"><input type="radio" name="js_gsession_selector" id="js_gsession_selectuser"></p>
							<h4 class="goalsearch_h"><?php esc_html_e( 'Specifying Page', 'qa-heatmap-analytics' ); ?></h4>
							<div class="goalsearch_inner">
								<p style="text-align: left"><?php esc_html_e( 'Enter the page URL to see the data for a specific page.', 'qa-heatmap-analytics' ); ?></p>
								<div class="margin-eight">
									<div class="bl_pagepatch_selector" style="margin-bottom: 5px">
										<input type="radio" name="selectuser_pagematch" id="selectuser_pagematch_prefix" value="pagematch_prefix" checked ><label for="selectuser_pagematch_prefix"><?php esc_html_e( 'Begins with', 'qa-heatmap-analytics' ); ?></label>
										<input type="radio" name="selectuser_pagematch" id="selectuser_pagematch_complete" value="pagematch_complete" ><label for="selectuser_pagematch_complete"><?php esc_html_e( 'Equals to', 'qa-heatmap-analytics' ); ?></label>
									</div>
								</div>
								<div>
									<input type="text" size="100" id="jsSearchPageUrl" placeholder="https://example.com/goal/"><label for="jsSearchPageUrl"></label>
								</div>
								<div class="margin-eight">
									<button type="button" id="extraction-proc-button" class="button button-primary"><?php esc_html_e( 'Apply', 'qa-heatmap-analytics' ); ?></button>
								</div>
							</div>
                        </div>

                        <p class="goal_sessionresult"><span class="qahm_margin-right4"><i class="fas fa-caret-down"></i></span><span id="cyusyutsu_notice"></span> <span class="qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'Session count achieved for either the selected goal or the specified page. Reports are displayed below for further analysis.', 'qa-heatmap-analytics' ); ?>"><i class="fas fa-info-circle"></i></p>

                        <div class="tab_content_item ds_item">
                            <h4 class="back-blue-grad_h3"><?php esc_html_e( 'Source / Medium', 'qa-heatmap-analytics' ); ?></h4>
                            <div id="pg_goalsm"></div>
                            <div id="tb_goalsm"></div>
                        <div class="tab_content_item ds_item">
                            <h4 class="back-blue-grad_h3"><?php esc_html_e( 'Landing Pages', 'qa-heatmap-analytics' ); ?></h4>
                            <div id="pg_goallp"></div>
                            <div id="tb_goallp"></div>
                        </div>
                        <div class="tab_content_item ds_item">
                            <h4 id="datafilter_heatmap" class="back-blue-grad_h3"><?php esc_html_e( 'Heatmaps', 'qa-heatmap-analytics' ); ?></h4>
                            <div>
                                <div id="heatmap-table-progbar"></div>
                                <div id="heatmap-table"></div>
                                <table id="table-heatmap-list"></table>
                            </div>
                        </div>
                        <div class="tab_content_item ds_item">
                            <h4 id="datafilter_sessions" class="back-blue-grad_h3"><?php echo esc_html_x( 'Sessions', 'sessions (noun)', 'qa-heatmap-analytics' ); ?></h4>
                            <div id="sday-table-progbar"></div>
                            <div id="sday_table"></div>
                        </div>
						<div class="qahm_textalign_right">
                                <input type="button" id="ds-csv-download-btn" value="<?php esc_attr_e( 'Download Data (as TSV)', 'qa-heatmap-analytics' ); ?>">
                        </div>

                    </div>
                </div>
            </div>
            <div id="bl_homeFooter">
                <h3><span class="dashicons dashicons-analytics"></span></h3>
                <div class="flex">
                    <div class="bl_reportmenu flex_item">
                        <h4><a href="#class-qahm-admin-page-home">TOP</a></h4>
                    </div>
                    <div class="bl_reportmenu flex_item">
                        <h4><a href="#h_audience"><?php esc_html_e( 'Audience', 'qa-heatmap-analytics' ); ?></a></h4>
                        <ul>
                            <li><a href="#h_allpv"><?php esc_html_e( 'Overview', 'qa-heatmap-analytics' ); ?></a></li>
                            <li><a href="#h_audienceDevice"><?php esc_html_e( 'Users / Device', 'qa-heatmap-analytics' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="bl_reportmenu flex_item">
                        <h4><a href="#h_acquisition"><?php esc_html_e( 'Acquisition', 'qa-heatmap-analytics' ); ?></a></h4>
                        <ul>
                            <li><a href="#h_channels"><?php esc_html_e( 'Channel', 'qa-heatmap-analytics' ); ?></a></li>
                            <li><a href="#h_sourceMedium"><?php esc_html_e( 'Source / Medium', 'qa-heatmap-analytics' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="bl_reportmenu flex_item">
                        <h4><a href="#h_behavior"><?php esc_html_e( 'Behavior', 'qa-heatmap-analytics' ); ?></a></h4>
                        <ul>
                            <li><a href="#h_landingpage"><?php esc_html_e( 'Landing Pages', 'qa-heatmap-analytics' ); ?></a></li>
                            <li><a href="#h_growthpage"><?php esc_html_e( 'Growing Landing Pages', 'qa-heatmap-analytics' ); ?></a></li>
                            <li><a href="#h_allpage"><?php esc_html_e( 'All Pages', 'qa-heatmap-analytics' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="bl_reportmenu flex_item">
                        <h4><a href="#h_conversion"><?php esc_html_e( 'Conversions', 'qa-heatmap-analytics' ); ?></h4>
                        <ul>
							<li><a href="#h_finduser"><?php esc_html_e( 'Goals', 'qa-heatmap-analytics' ); ?></a></li>
                            <li><a href="#h_finduser"><?php esc_html_e( 'Specifying Page', 'qa-heatmap-analytics' ); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
		</div><!-- end of .qahm-admin-page-->
<?php
	}

	/**
	 * セッション数の取得
	 */
	public function ajax_get_session_num() {
		if( $this->is_maintenance() ) {
			return;
		}

		$data = array();
		$session_num = 0;
		$session_num_1min = 0;

		global $wp_filesystem;
		global $qahm_time;
		$before1min = $qahm_time->now_unixtime() - 60;
		$session_temp_dir_path = $this->get_data_dir_path( 'readers/temp' );
		if( $wp_filesystem->exists( $session_temp_dir_path ) ) {

			$session_temp_dirlist = $this->wrap_dirlist( $session_temp_dir_path );
			if ( $session_temp_dirlist ) {
				$session_num = count( $session_temp_dirlist );
				foreach ( $session_temp_dirlist as $session_temp_fileobj ) {
					if ( $session_temp_fileobj['lastmodunix'] > $before1min ) {
						++$session_num_1min;
					}
				}
			}
		}

		$data['session_num']      = $session_num;
		$data['session_num_1min'] = $session_num_1min;

		echo wp_json_encode( $data );
		die();
	}


	/**
	 * リアルタイムリストの取得
	 */
	public function ajax_get_realtime_list() {
		if( $this->is_maintenance() ) {
			return;
		}

		$data = array();
		$alldataary = array();
		$realtime_list = '';

		global $wp_filesystem;
		global $qahm_time;

		$ellipsis     = '...';
		$title_width  = 80 + mb_strlen( $ellipsis );
		$domain_width = 30 + mb_strlen( $ellipsis );

		$realtime_view_path = $this->get_data_dir_path( 'readers' ) . 'realtime_view.php';
		if ( ! $wp_filesystem->exists( $realtime_view_path ) ) {
			echo 'null';
			die();
		}

		$realtime_view_ary = $this->wrap_unserialize( $this->wrap_get_contents( $realtime_view_path ) );
		if ( ! $realtime_view_ary ) {
			echo 'null';
			die();
		}

		$realtime_cnt = count( $realtime_view_ary['body'] );
		if ( $realtime_cnt === 0 ) {
			echo 'null';
			die();
		}

		for ( $i = 0; $i < $realtime_cnt; $i++ ) {
			$body = $realtime_view_ary['body'][$i];
			$first_title        = $body['first_title'];
			$first_title_el     = mb_strimwidth( $first_title, 0, $title_width, $ellipsis );
			$first_url          = $body['first_url'];
			$last_title         = $body['last_title'];
			$last_title_el      = mb_strimwidth( $last_title, 0, $title_width, $ellipsis );
			$last_url           = $body['last_url'];
			$last_exit_time     = $body['last_exit_time'];
			$sec_on_site        = $qahm_time->seconds_to_timestr( (int) $body['sec_on_site'] );
			$referrer           = $body['first_referrer'];
			$source_domain_html = 'direct';
			$work_base_name     = pathinfo( $body['file_name'], PATHINFO_FILENAME );

			if ( ! empty( $referrer ) ) {
				if ( 0 === strncmp( $referrer, 'http', 4 ) ) {
					$parse_url          = wp_parse_url( $referrer );
					$ref_host           = $parse_url['host'];
					$source_domain      = mb_strimwidth( $ref_host, 0, $domain_width, $ellipsis );
					$source_domain_html = '<a href="' . esc_url( $referrer ) . '" target="_blank" class="qahm-tooltip" data-qahm-tooltip="'. esc_url( $referrer ) . '">' . esc_html( $source_domain ) . '</a>';
				} else {
					$source_domain      = mb_strimwidth( $referrer, 0, $domain_width, $ellipsis );
					$source_domain_html = esc_html( $source_domain );
				}
			}

			$device = $body['device_name'];
			if ( 'dsk' === $device ) {
				$device = 'pc';
			}

			$dataary = [];
			$dataary[] = esc_html( $device );
			$dataary[] = esc_html( $last_exit_time );
			$dataary[] = esc_url( $first_url );
			$dataary[] = esc_attr( $first_title );
			$dataary[] = esc_html( $first_title_el );
			$dataary[] = esc_url( $last_url );
			$dataary[] = esc_attr( $last_title );
			$dataary[] = esc_html( $last_title_el );
			$dataary[] = esc_url( $referrer );
			$dataary[] = esc_html( $source_domain );
			$dataary[] = esc_html( $body['page_view'] );
			$dataary[] = esc_html( $body['sec_on_site'] );
			$dataary[] = esc_attr( $work_base_name );
			$alldataary[] = $dataary;
		}

		$data['update_time']   = $qahm_time->now_str();

		//$data['realtime_list'] = $realtime_list;
		$data['realtime_list'] = $alldataary;

		echo wp_json_encode( $data );
		die();
	}

	/**
	 *
	*/
	public function ajax_get_json() {
		if( $this->is_maintenance() ) {
			return;
		}

		global $wp_filesystem;

		$cache_path = $this->get_data_dir_path() . 'cache/';
		if ( ! $wp_filesystem->exists( $cache_path ) ) {
			$wp_filesystem->mkdir( $cache_path );
		}

		// リストファイル読み込み
		$list_path = $cache_path . '/statistics_list.php';
		if ( $wp_filesystem->exists( $list_path ) ) {
			$list = $this->wrap_unserialize( $wp_filesystem->get_contents( $list_path ) );
		} else {
			$time = new QAHM_Time();
			global $wpdb;

			$yesterday_str = $time->xday_str(-1);

			$table_name = $wpdb->prefix . 'qa_pv_log';
			//$query      = 'SELECT pv_id,reader_id,page_id ,device_id,source_id,medium_id,campaign_id,session_no,access_time,pv,speed_msec,browse_sec,is_last,is_newuser,is_cv_session,flag_bit,version_id FROM ' . $table_name . ' WHERE  access_time between %s AND %s';
			//$preobj     = $wpdb->prepare( $query,  '2020-08-01 00:00:00', $yesterday_str . ' 23:59:59' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query. 
			$result     = $wpdb->get_results( $wpdb->prepare(
				'SELECT pv_id,reader_id,page_id,device_id,source_id,medium_id,campaign_id,session_no,access_time,pv,speed_msec,browse_sec,is_last,is_newuser,is_cv_session,flag_bit,version_id FROM ' . esc_sql($table_name) . ' WHERE  access_time between %s AND %s',
				'2020-08-01 00:00:00',
				$yesterday_str . ' 23:59:59'
			) );

			if ( ! empty( $result ) ) {
				$newary = array();
				foreach ($result as $idx => $row ) {
					$newary[$idx]['pv_id'] = $row->pv_id;
					// reader_id
					$newary[$idx]['reader_id'] = $row->reader_id;
					$table_name = $wpdb->prefix . 'qa_readers';
					//$query      = 'SELECT UAos,UAbrowser FROM ' . $table_name . ' WHERE  reader_id = %d';
					//$preobj     = $wpdb->prepare( $query, $row->reader_id );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
					$select     = $wpdb->get_results( $wpdb->prepare( 'SELECT UAos,UAbrowser FROM ' . esc_sql($table_name) . ' WHERE  reader_id = %d', $row->reader_id ) );
					$newary[$idx]['UAos'] = $select[0]->UAos;
					$newary[$idx]['UAbrowser'] = $select[0]->UAbrowser;

					// page_id
					$newary[$idx]['page_id'] = $row->page_id;
					$table_name = $wpdb->prefix . 'qa_pages';
					//$query      = 'SELECT url,title FROM ' . $table_name . ' WHERE  page_id = %d';
					//$preobj     = $wpdb->prepare( $query, $row->page_id );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
					$select     = $wpdb->get_results( $wpdb->prepare( 'SELECT url,title FROM ' . esc_sql($table_name) . ' WHERE  page_id = %d', $row->page_id ) );
					$newary[$idx]['url'] = $select[0]->url;
					$newary[$idx]['title'] = esc_html( $select[0]->title );

					// device_id
					$newary[$idx]['device_id'] = $row->device_id;

					// source_id
					$newary[$idx]['source_id'] = $row->source_id;
					$table_name = $wpdb->prefix . 'qa_utm_sources';
					//$query      = 'SELECT utm_source,source_domain FROM ' . $table_name . ' WHERE  source_id = %d';
					//$preobj     = $wpdb->prepare( $query, $row->source_id );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
					$select     = $wpdb->get_results( $wpdb->prepare( 'SELECT utm_source,source_domain FROM ' . esc_sql($table_name) . ' WHERE  source_id = %d', $row->source_id ) );
					$newary[$idx]['utm_source'] = $select[0]->utm_source;
					$newary[$idx]['source_domain'] = $select[0]->source_domain;

					// medium_id
					$newary[$idx]['medium_id'] = $row->medium_id;
					if ( $row->medium_id ) {
						$table_name = $wpdb->prefix . 'qa_utm_media';
						//$query      = 'SELECT utm_medium FROM ' . $table_name . ' WHERE  medium_id = %d';
						//$preobj     = $wpdb->prepare( $query, $row->medium_id );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
						$select     = $wpdb->get_results( $wpdb->prepare( 'SELECT utm_medium FROM ' . esc_sql($table_name) . ' WHERE  medium_id = %d', $row->medium_id ) );
						$newary[$idx]['utm_medium'] = $select[0]->utm_medium;
					}

					// campaign_id
					$newary[$idx]['campaign_id'] = $row->campaign_id;
					if ( $row->campaign_id ) {
						$table_name = $wpdb->prefix . 'qa_utm_campaigns';
						//$query      = 'SELECT utm_campaign FROM ' . $table_name . ' WHERE  campaign_id = %d';
						//$preobj     = $wpdb->prepare( $query, $row->campaign_id );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
						$select     = $wpdb->get_results( $wpdb->prepare( 'SELECT utm_campaign FROM ' . esc_sql($table_name) . ' WHERE  campaign_id = %d', $row->campaign_id ) );
						$newary[$idx]['utm_campaign'] = $select[0]->utm_campaign;
					}

					// others
					$newary[$idx]['session_no']    = $row->session_no;
					$newary[$idx]['access_time']   = $row->access_time;
					$newary[$idx]['pv']            = $row->pv;
					$newary[$idx]['speed_msec']    = $row->speed_msec;
					$newary[$idx]['browse_sec']    = $row->browse_sec;
					$newary[$idx]['is_last']       = $row->is_last;
					$newary[$idx]['is_newuser']    = $row->is_newuser;
					$newary[$idx]['is_cv_session'] = $row->is_cv_session;
					$newary[$idx]['flag_bit']      = $row->flag_bit;
					$newary[$idx]['version_id']    = $row->version_id;
				}
			
				$list = wp_json_encode($newary) ;
				$wp_filesystem->put_contents( $list_path, $this->wrap_serialize( $list ) );
			}
		}
		
		echo esc_js($list);
		die();
	}

	/**
	 * urlをpage idに変換して出力
	*/
	public function ajax_url_to_page_id() {
		/*
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_REFRESH ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}
		*/

		global $qahm_db;

		// スラッシュあり、なしの両方を検索
		$url1 = $this->wrap_filter_input( INPUT_POST, 'url' );
		if ( ! $url1 ) {
			die();
		}

		$url1 = mb_strtolower( $url1 );
		if( substr( $url1, -1 ) === '/' ) {
			$url2 = rtrim( $url1, '/' );
		} else {
			$url2 = $url1 . '/';
		}
		
		$query = 'SELECT page_id FROM ' . $qahm_db->prefix . 'qa_pages WHERE url = BINARY %s OR url = BINARY %s';
		$res   = $qahm_db->get_results( $qahm_db->prepare( $query, $url1, $url2 ) );

		if ( $res ) {
			echo wp_json_encode( $res[0]->page_id );
		}
		die();
	}
} // end of class
