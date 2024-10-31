<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_admin_page_realtime = new QAHM_Admin_Page_Realtime();

class QAHM_Admin_Page_Realtime extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-realtime';

	// nonce
	const NONCE_ACTION = self::SLUG . '-nonce-action';
	const NONCE_NAME   = self::SLUG . '-nonce-name';

	function __construct() {
		parent::__construct();
		$this->regist_ajax_func( 'ajax_get_session_num' );
		$this->regist_ajax_func( 'ajax_get_realtime_list' );
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

		// enqueue script
		$this->common_enqueue_script();
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-common-css', $css_dir_url . 'admin-page-home-common.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-realtime-css', $css_dir_url. 'admin-page-home-realtime.css', null, QAHM_PLUGIN_VERSION, false );

		wp_enqueue_script( QAHM_NAME . '-admin-page-realtime', $js_dir_url . 'admin-page-realtime.js', array( QAHM_NAME . '-admin-page-base' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-table', $js_dir_url . 'table.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-progress-bar', $js_dir_url . '/progress-bar-exec.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-chart', $js_dir_url . 'lib/chart/chart.min.js', null, QAHM_PLUGIN_VERSION, false );

		// inline script
		$scripts = $this->get_common_inline_script();
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		$localize['table_tanmatsu']			= esc_html__( 'Device', 'qa-heatmap-analytics' );
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


		$localize['graph_users'] 			= esc_html_x( 'Users', 'a label in a graph', 'qa-heatmap-analytics' );
		$localize['graph_sessions'] 		= esc_html_x( 'Sessions', 'a lebel in a graph', 'qa-heatmap-analytics' );
		$localize['graph_pvs'] 				= esc_html_x( 'Pageviews', 'a label in a graph', 'qa-heatmap-analytics' );
		$localize['graph_hourly_sessions']  = esc_html_x( 'Hourly Sessions', 'a label in a graph', 'qa-heatmap-analytics' );
		$localize['sessions_today']		= esc_html_x( 'Hourly Sessions Today', 'a title of a graph', 'qa-heatmap-analytics' );
		$localize['graph_hours']				= esc_html_x( 'Hours', 'a label in a graph', 'qa-heatmap-analytics' );

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
		$localize['ds_download_msg1']		= esc_html__( 'Download the data of pageviews which filtered.', 'qa-heatmap-analytics' );
		$localize['ds_download_error1']		= esc_html__( 'No data to download.', 'qa-heatmap-analytics' );
		$localize['ds_download_done_nodata'] = esc_html__( 'No filtered data.', 'qa-heatmap-analytics' );


		$localize['ds_cyusyutsu_kensu'] 	= esc_html_x( 'session(s) found.', 'displaying the number of filtered sessions', 'qa-heatmap-analytics' );
		$localize['ds_cyusyutsu_button'] 	= esc_html_x( 'Filter and Search', 'value of the button in narrow-down-data-section', 'qa-heatmap-analytics' );
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
		$localize['tjs_clear_filter'] 			= esc_html_x( 'CLEAR ALL Filters', 'a-button-like words appears above a table', 'qa-heatmap-analytics' );
		$localize['tjs_komejirushi']	 		= esc_html_x( '*', 'a mark before a sentence', 'qa-heatmap-analytics' );
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
?>
		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1>QA <?php esc_html_e( 'Realtime View', 'qa-heatmap-analytics' ); ?></h1>

				<?php 
				$is_subscribed = $this->is_subscribed();
				if ( ! $is_subscribed ) {
					$msg_yuryouikaga = '<div class="qahm-using-free-announce"><span class="qahm_margin-right4"><span class="dashicons dashicons-megaphone"></span></span><span class="qahm_fontsize12em">';
					$msg_yuryouikaga .= sprintf( 
						/* translators: placeholders are for the link */
						esc_html__( 'Upgrade for better insights! Gain more PV capacity for free by %1$sreferring friends%2$s, or choose our %3$sPremium Plan%4$s to increase PV limits and unlock more goals.', 'qa-heatmap-analytics' ), $referral_link_atag, '</a>', $upgrade_link_atag, '</a>'
					);
                    $msg_yuryouikaga .= '</span></div>';
                    echo wp_kses_post($msg_yuryouikaga);
				}
				?>

            <!--リアルタイム-->
            <div class="bl_reportField">
                <h2 id="h_realtime"><?php esc_html_e( 'Realtime', 'qa-heatmap-analytics' ); ?></h2>
                <div id="tday_container">
                    <div id="tday_upper" class="bl_contentsArea">
                        <h3 id="h_realtimeoverview"><?php esc_html_e( 'Overview', 'qa-heatmap-analytics' ); ?></h3>
                        <div class="realtime_num flex" style="flex-wrap: wrap;">
                            <div class="flex_item" style="width: 260px; margin-right: 6px;">
		                        <div>
								<div class="num_title"><?php esc_html_e( 'Users in Last 30 Min', 'qa-heatmap-analytics' ); ?></div>
								<div id="session_num" class="tday_now_rtn"></div>
								</div><!-- now_number_end -->
                            </div>
                            <div class="flex_item" style="width: 260px; margin-right: 6px;">
		                        <div>
								<div class="num_title"><?php esc_html_e( 'Active Users in Last Min', 'qa-heatmap-analytics' ); ?></div>
								<div  id="session_num_1min" class="tday_now_rtn"></div>
								</div><!-- now_number_end -->
                            </div>
                            <div class="flex_item bl_dayGraph"  style="max-width: 100%; width: 500px;"><canvas id="realtime" height="200px"></canvas></div>
                        </div>

						<p style="margin-bottom: 48px;"><span class="qahm_bikkuri-mark"><i class="fas fa-exclamation-circle"></i></span><a href="https://mem.quarka.org/en/manual/realtimeview-doesnt-proceed/" target="_blank" rel="noopener"><?php esc_html_e( 'If doesn\'t proceed', 'qa-heatmap-analytics' ); ?><span class="qahm_link-mark"><i class="fas fa-external-link-alt"></i></span></a></p>

						<h3 id="h_realtimereplay"><?php esc_html_e( 'Explore', 'qa-heatmap-analytics' ); ?></h3>
						<div class="rtsession_title_container">
                        <div class="rtsession_title_section">
							<div class="tday_upper_jpn"><?php esc_html_e( 'Recent Sessions', 'qa-heatmap-analytics' ); ?></div>
							<div class="rtsession_icons">
								<span class="qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'You can sort and filter the session data using the filter function of the table.', 'qa-heatmap-analytics' ); ?>">
									<span class="dashicons dashicons-analytics" style="background-color: #a9a9a9;"></span>
								</span>
								<span class="qahm-tooltip-bottom" data-qahm-tooltip="<?php esc_attr_e( 'To watch Session Replay, click the play button in the \'Replay\' column of each session row.', 'qa-heatmap-analytics' ); ?>">
									<span class="dashicons dashicons-format-video" style="background-color: #f39c12;"></span>
								</span>
							</div>
						</div>
							<div class="tday_upper_txt">
                                <div class="tday_upper_eng color_red"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Latest Update', 'qa-heatmap-analytics' ); ?>:
                                    <span id="update_time"></span>
                                    <script>
                                        // time();
                                        function time(){
                                            var now = new Date();
                                            var formatnow = now.toLocaleString().slice(0,-3);
                                            document.getElementById("time").innerHTML = formatnow;
                                        }
                                        // setInterval('time()',60000);
                                    </script>
                                </div>
                                
                                <p><span class="qahm_bikkuri-mark"><i class="fas fa-exclamation-circle"></i></span><?php esc_html_e( 'There would be empty space below when no data to show.', 'qa-heatmap-analytics' ); ?></p>
                            </div>
                        </div><!-- upper_title_end -->

                        <!--<div class="tday_data_box">-->
                        <!--<img src="--><?php //echo plugin_dir_url( __FILE__ ); ?><!--img/graf.jpg">-->
                        <!--</div>--><!-- data_box_end -->
                        <div id="tday-table-progbar"></div>
                        <div class="tday_scroll-table" id="tday_table"></div>
                    </div><!-- upper_end -->

                </div>
            </div>
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
} // end of class
