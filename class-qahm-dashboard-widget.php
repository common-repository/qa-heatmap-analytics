<?php
/**
 *
 *
 * @package qa_heatmap
 */

$qahm_wpdashboard_widjet = new QAHM_Dashboard_Widjet();

class QAHM_Dashboard_Widjet extends QAHM_Admin_Page_Base {



	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// css, js読み込み
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// dashboard set up
		add_action( 'wp_dashboard_setup', array( $this, 'wpdocs_add_dashboard_widgets' ) );
	}



	/**
	 * enqueue style / scripts
	 */
	function enqueue_scripts($hook) {
		if ( 'index.php' !== $hook ) {
			return;
		}
		$current_user = wp_get_current_user();
		if ( ! in_array( 'administrator', $current_user->roles, true ) ) {
			return;
		}

		
		$this->maintenance_widget_msg = '';
		if( $this->is_maintenance() ) {
			//if under maintenance, show message instead.
			$this->maintenance_widget_msg = _x( 'Data is currently under maintenance.', 'a maintenance notice in QA widget', 'qa-heatmap-analytics' );

		} else {
			//usual enqueue
			wp_enqueue_style( QAHM_NAME . '-dashboard-widget', plugins_url( 'css/dashboard-widget.css', __FILE__ ), array(), QAHM_PLUGIN_VERSION );

			wp_enqueue_script( QAHM_NAME . '-font-awesome', plugins_url( 'js/lib/font-awesome/all.min.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );
			wp_enqueue_script( QAHM_NAME . '-moment-with-locales', plugins_url( 'js/lib/moment/moment-with-locales.min.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );
			wp_enqueue_script( QAHM_NAME . '-chart', plugins_url( 'js/lib/chart/chart.min.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );
			wp_enqueue_script( QAHM_NAME . '-common', plugins_url( 'js/common.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );

			//enqueue after footer ( content読込エラー防止 )
			wp_enqueue_script( QAHM_NAME . '-widget', plugins_url( 'js/dashboard-widget.js', __FILE__ ), array( QAHM_NAME . '-chart', QAHM_NAME . '-moment-with-locales'), QAHM_PLUGIN_VERSION, true );
			
			// inline script
			global $qahm_time;
			$kyou = $qahm_time->today_str();
			$hyouji_nissu = 28;
			$scripts = array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce_api'		=> wp_create_nonce( QAHM_Data_Api::NONCE_API ),
				'wp_lang_set'	=> get_bloginfo('language'),
				'kyou_str'		=> $kyou,
				'nisuu'				=> $hyouji_nissu,
			);
			wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'WidgetObj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'WidgetObj );', 'before' );

			//localize
			$localize = array(
				'msg_ajax_failed_cannot_display_data'	=> esc_html__( 'Ajax error. Could not display the data.', 'qa-heatmap-analytics' ),
				'msg_there_is_no_data'								=> esc_html__( 'No data to display.', 'qa-heatmap-analytics' ),
				'Visits_graph' 		=> esc_html__( 'Visits Graph', 'qa-heatmap-analytics' ),
				'Channel'	 				=> esc_html__( 'Channel', 'qa-heatmap-analytics' ),
				'Sessions_today'	=> esc_html__( 'Hourly Sessions Today', 'qa-heatmap-analytics' ),
				'graph_users' 		=> esc_html_x( 'Users', 'a label in a graph', 'qa-heatmap-analytics' ),
				'graph_sessions' 	=> esc_html_x( 'Sessions', 'a lebel in a graph', 'qa-heatmap-analytics' ),
				'graph_pvs' 			=> esc_html_x( 'Pageviews', 'a label in a graph', 'qa-heatmap-analytics' ),
				'page_exit'				=> esc_html__( 'Exit', 'qa-heatmap-analytics' ),
		
			);
			wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );

		}
	}



  /**
	 * Add a new dashboard widget.
	 */
	function wpdocs_add_dashboard_widgets() {
		//administrator以外は表示しない
		$current_user = wp_get_current_user();
		if ( ! in_array( 'administrator', $current_user->roles, true ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'qahm_dashboard_widget1',
			__( '[QA] Realtime', 'qa-heatmap-analytics' ),
			array( $this, 'dashboard_widget_realtime')
		);
		wp_add_dashboard_widget(
			'qahm_dashboard_widget2',
			__( '[QA] Reports Overview', 'qa-heatmap-analytics' ),
			array( $this, 'dashboard_widget_visits')
		);
		wp_add_dashboard_widget(
			'qahm_dashboard_widget3',
			__( '[QA] Growing Landing Page', 'qa-heatmap-analytics' ),
			array( $this, 'dashboard_widget_growing')
		);
		

		//Forcing QA-widget to the top
		global $wp_meta_boxes;
		$default_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$qahm_widget_backup = array(
			'qahm_dashboard_widget1' => $default_dashboard['qahm_dashboard_widget1'],
			'qahm_dashboard_widget2' => $default_dashboard['qahm_dashboard_widget2'],
			'qahm_dashboard_widget3' => $default_dashboard['qahm_dashboard_widget3'],
		);
		unset( $default_dashboard['qahm_dashboard_widget1'] );
		unset( $default_dashboard['qahm_dashboard_widget2'] );
		unset( $default_dashboard['qahm_dashboard_widget3'] );	
		$sorted_dashboard = array_merge( $qahm_widget_backup, $default_dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;

	}
	

	/**
	* Output the contents of the dashboard widget
	*/

	function dashboard_widget_realtime( $post, $callback_args ) {
		if ( empty( $this->maintenance_widget_msg ) ) {
?>
			<div id="qahm-widget-realtime">
				<div class="realtime-users-section">
					<div class="realtime-users">
						<div class="realtime-users-term"><?php esc_html_e( 'USERS in LAST 30 MIN', 'qa-heatmap-analytics' ); ?></div>
						<div class="realtime-users-number" id="realtime-users-thirtymin"></div>
					</div>
					<div class="realtime-users">
						<div class="realtime-users-term"><?php esc_html_e( 'USERS PER MIN', 'qa-heatmap-analytics' ); ?></div>
						<div class="realtime-users-number" id="realtime-users-permin"></div>
					</div>
				</div>
				<div class="chart-section">
					<div id="chart-container-hourly">
						<canvas id="widgetChartHourly"></canvas>
					</div>
				</div>
				<div class="realtime-list-section">
					<div id="realtime-list-container">		
					</div>
				</div>
				<div class="qa-link-section">
					<a href="./admin.php?page=qahm-realtime"><button class="qa-link-button"><?php esc_html_e( 'QA Realtime View', 'qa-heatmap-analytics' ); ?></button></a>
				</div>
			</div>
<?php
		} else {
			echo esc_html($this->maintenance_widget_msg);
		}
	} //end function dashboard_widget_realtime
	

	function dashboard_widget_growing( $post, $callback_args ) {
		if ( empty( $this->maintenance_widget_msg ) ) {	
?>
			<div id="qahm-widget-growing">
				<div class="growing-daterange-section">
					<div><?php esc_html_e( '4 weeks ago', 'qa-heatmap-analytics' ); ?><br><span id="growing-daterange-earliest" class="growing-daterange"></span></div>
					<div><span class="growing-daterange-fa"><i class="fas fa-arrow-right"></i></span></div>
					<div><?php esc_html_e( 'The last week', 'qa-heatmap-analytics' ); ?><br><span id="growing-daterange-latest" class="growing-daterange"></span></div>
				</div>
				<div class="growing-table-section">
					<div id="growing-table-container">
						<table id="growing-table">
							<thead>
								<tr>
									<th width="60%"><?php esc_html_e( 'Page Title', 'qa-heatmap-analytics' ); ?></th>
									<th><?php esc_html_e( 'Medium', 'qa-heatmap-analytics' ); ?></th>
									<th colspan="2"><?php esc_html_e( 'Growth %', 'qa-heatmap-analytics' ); ?></th>
								</tr>
							</thead>
							<tbody id="growing-table-tbody">
							</tboday>
						</table>			
					</div>
				</div>
				<div class="qa-link-section">
					<a href="./admin.php?page=qahm-home"><button class="qa-link-button"><?php esc_html_e( 'QA Home', 'qa-heatmap-analytics' ); ?></button></a>
				</div>
			</div>
<?php
		} else {
			echo esc_html($this->maintenance_widget_msg);
		}
	} //end function dashboard_widget_growing


	function dashboard_widget_visits( $post, $callback_args ) {
		if ( empty( $this->maintenance_widget_msg ) ) {	
?>
			<div id="qahm-widget-visits">
				<div class="visits-daterange-section">
					<p id="visits-daterange"><?php esc_html_e( 'Date Range ( past 28 days )', 'qa-heatmap-analytics' ); ?></p>
				</div>
				<div class="visits-sum-section">
					<div class="visits-sum">
						<div class="visits-sum-term"><span class="qahm-fa"><i class="fas fa-door-open"></i></span><?php esc_html_e( 'Sessions', 'qa-heatmap-analytics' ); ?></div>
						<div class="visits-sum-number" id="visits-sessions"></div>
					</div>
					<div class="visits-sum">
						<div class="visits-sum-term"><span class="dashicons dashicons-admin-page qahm-fa"></span><?php esc_html_e( 'Pageviews', 'qa-heatmap-analytics' ); ?></div>
						<div class="visits-sum-number" id="visits-pvs"></div>
					</div>
				</div>
				<div>
					<div class="chart-section">
						<div id="chart-container-audience">
							<canvas id="widgetChartAudience"></canvas>
						</div>
					</div>
					<div class="chart-section">
						<div id="chart-container-channel">
							<canvas id="widgetChartChannel"></canvas>
						</div>
					</div>
				</div>
				<div class="qa-link-section">
					<a href="./admin.php?page=qahm-home"><button class="qa-link-button"><?php esc_html_e( 'QA Home', 'qa-heatmap-analytics' ); ?></button></a>
				</div>
			</div>
<?php
		} else {
			echo esc_html($this->maintenance_widget_msg);
		}
	} //end function dashboard_widget_visits



	/**
	 * セッション数の取得 ---------------
	 * class-qahm-admin-page-realtime.php で定義済み。参照できるようなのでコメントアウト。June 17, 2022.
	 */
	/*
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
	*/

	/**
	 * リアルタイムリストの取得 ------------------
	 * class-qahm-admin-page-realtime.php で定義済み。参照できるようなのでコメントアウト。June 17, 2022.
	 */
	/*
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
					$parse_url          = parse_url( $referrer );
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
	*/
	
} //end of class
