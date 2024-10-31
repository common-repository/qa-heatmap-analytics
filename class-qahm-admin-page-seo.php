<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_admin_page_seo = new QAHM_Admin_Page_Seo();

class QAHM_Admin_Page_Seo extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-seo';

	// nonce
	const NONCE_ACTION = self::SLUG . '-nonce-action';
	const NONCE_NAME   = self::SLUG . '-nonce-name';
	
	// このページ専用の定数
	const DATE_FORMAT  = 'Y-m-d';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
		$this->regist_ajax_func( 'ajax_get_rewrite_table' );
		$this->regist_ajax_func( 'ajax_get_keyword_param_table' );
        //mkdummy
		$this->regist_ajax_func( 'ajax_get_all_keyword_param_table' );
        //mkdummy
		$this->regist_ajax_func( 'ajax_update_seo_monitoring_keyword' );
		add_action( 'load-qa-analytics_page_qahm-seo', array( $this, 'admin_init' ) );
	}


	// 管理画面の初期化
	public function admin_init(){
		if( defined('DOING_AJAX') && DOING_AJAX ){
			return;
		}

		global $qahm_google_api;

		// nonceで設定したcredentialのチェック
		// 設定画面
		if ( isset( $_POST[ self::NONCE_NAME ] ) ) {
			if ( check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME ) ) {
				// フォームから値が送信されていればDBに保存
				$client_id     = $this->wrap_filter_input( INPUT_POST, 'client_id' );
				$client_secret = $this->wrap_filter_input( INPUT_POST, 'client_secret' );
				$qahm_google_api->set_credentials( $client_id, $client_secret, null );

				$is_post = true;
			}
		}
		
		$qahm_google_api->init(
			'Google API Integration',
			array( 'https://www.googleapis.com/auth/webmasters.readonly' ),
			admin_url( 'admin.php?page=qahm-config' )
		);
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
		$keyword_ary = $this->wrap_get_option( 'seo_monitoring_keyword' );
		if ( $keyword_ary ) {
			$keyword_ary = json_decode( $keyword_ary );
		}

		// enqueue_style
		$this->common_enqueue_style();
		wp_enqueue_style( QAHM_NAME . '-admin-page-home-common', $css_dir_url. 'admin-page-home-common.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-admin-page-seo-css', $css_dir_url. 'admin-page-seo.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-table-css', $css_dir_url. 'table.css', null, QAHM_PLUGIN_VERSION );

		// enqueue script
		$this->common_enqueue_script();
		wp_enqueue_script( QAHM_NAME . '-table', $js_dir_url . 'table.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-progress-bar', $js_dir_url . '/progress-bar-exec.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-admin-page-seo', plugins_url( 'js/admin-page-seo.js', __FILE__ ), array( QAHM_NAME . '-table' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-simple-statistics', plugins_url( 'js/lib/simple-statistics/simple-statistics.min.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-chart', plugins_url( 'js/lib/chart/chart.min.js', __FILE__ ), null, QAHM_PLUGIN_VERSION, false );

		// inline script
        global $qahm_data_api;
		$scripts = $this->get_common_inline_script();
		$scripts['goalsJson'] =  $qahm_data_api->get_goals_json();
		$scripts['seo_monitoring_keyword'] = $keyword_ary;
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		$localize['update_page']              = esc_html( __( 'The Page', 'qa-heatmap-analytics' ) );
		$localize['impression']               = esc_html( __( 'Impressions', 'qa-heatmap-analytics' ) );
		$localize['ctr']                      = esc_html( __( 'CTR', 'qa-heatmap-analytics' ) );
		//$localize['publish_pos']              = esc_html( $this->japan( '掲載順位', 'qa-heatmap-analytics' ) );
		$localize['title']                    = esc_html__( 'Title', 'qa-heatmap-analytics' );
		$localize['url']                      = esc_html__( 'URL', 'qa-heatmap-analytics' );
		$localize['search_keyword']           = esc_html( __( 'Search Keywords', 'qa-heatmap-analytics' ) );
		$localize['edit']                     = esc_html( __( 'Edit', 'qa-heatmap-analytics' ) );
		$localize['create_update_date']       = esc_html( __( 'pub/upd Date', 'qa-heatmap-analytics' ) );
		$localize['total_num']                = esc_html( __( 'Total', 'qa-heatmap-analytics' ) );
		//$localize['earliest_session_tooltip'] = esc_attr( $this->japan( '集計期間内の古い日付から7日分を合計した数です。但し期間内に最低7日分より多いデータが存在しない場合は、新旧の合計値が同一になってしまうため、期間内の一番古い1日分のデータ数を表示します。', 'qa-heatmap-analytics' ) );
		//$localize['latest_session_tooltip']   = esc_attr( $this->japan( '集計期間内の新しい日付から7日分を合計した数です。但し期間内に最低7日分より多いデータが存在しない場合は、新旧の合計値が同一になってしまうため、期間内の一番新しい1日分のデータ数を表示します。', 'qa-heatmap-analytics' ) );
		$localize['change_rate']              = esc_html( __( 'Rate of Change', 'qa-heatmap-analytics' ) );
		$localize['change_rate_tooltip']      = esc_html( __( 'The data for the analysis period is divided into two parts, and the average of the first and second halves of the values is calculated respectively, and then the extent to which they have changed is determined.', 'qa-heatmap-analytics' ) );
		$localize['goal_complite']            = esc_html( __( 'Goal Completion from the page', 'qa-heatmap-analytics' ) );
		$localize['goal_complite_tooltip']    = esc_html( __( 'The number of people who landed on this landing page and completed the goal. All traffics are counted, not just those from organic search.', 'qa-heatmap-analytics' ) );
		$localize['view_count']               = esc_html( __( 'Impressions', 'qa-heatmap-analytics' ) );
		$localize['click']                    = esc_html( __( 'Clicks', 'qa-heatmap-analytics' ) );
		$localize['total']                    = esc_html( __( 'Total', 'qa-heatmap-analytics' ) );
		$localize['total_view_count']					= esc_html( __( 'Total Impressions', 'qa-heatmap-analytics' ) );
		$localize['total_click']							= esc_html( __( 'Total Clicks', 'qa-heatmap-analytics' ) );
		$localize['average']                  = esc_html( _x( 'Avg.', 'the abbreviation for "Average"', 'qa-heatmap-analytics' ) );
		$localize['average_ctr']							= esc_html( __( 'Avg. CTR', 'qa-heatmap-analytics' ) );
		$localize['prev_pos']                 = esc_html( __( 'Prev. Position', 'qa-heatmap-analytics' ) );
		$localize['recent_change']            = esc_html( __( 'Recent Change', 'qa-heatmap-analytics' ) );
		$localize['recent_pos']               = esc_html( __( 'Recent Position', 'qa-heatmap-analytics' ) );
		$localize['landing_page']             = esc_html( _x( 'Landing Page', '"Landing Page" as a table header in SEO Analysis.', 'qa-heatmap-analytics' ) );
		$localize['landing_page_comment']     = esc_html( __( 'Click a search keyword to see the details below.', 'qa-heatmap-analytics' ) );
		$localize['aggregation_period']       = esc_html( __( 'Aggregation during the date range', 'qa-heatmap-analytics' ) );
		$localize['trend']                    = esc_html( __( 'Trend', 'qa-heatmap-analytics' ) );
		$localize['trend_tooltip']            = esc_html( __( 'It is the value of the slope of the regression line that represents the daily fluctuations in the positioning. E.g.) When the place improves by 20 positions in 100 days, "+20%" it will be.', 'qa-heatmap-analytics' ) );
		$localize['reliability']              = esc_html( __( 'Reliability', 'qa-heatmap-analytics' ) );
		$localize['reliability_tooltip']      = esc_html( __( 'It is shown based on the correlation coefficient of the trend regression line. The correlation coefficient is displayed in (parentheses).', 'qa-heatmap-analytics' ) );
		$localize['error']                    = esc_html( __( 'Error', 'qa-heatmap-analytics' ) );
		$localize['data_not_calc']            = esc_html( __( '*Unable to calculate', 'qa-heatmap-analytics' ) );
		$localize['data_none']                = esc_html( __( 'No data', 'qa-heatmap-analytics' ) );
		$localize['data_deficiency']          = esc_html( __( 'Data deficiency', 'qa-heatmap-analytics' ) );
		$localize['data_low']                 = esc_html( __( 'Low', 'qa-heatmap-analytics' ) );
		$localize['data_middle']              = esc_html( __( 'Middle', 'qa-heatmap-analytics' ) );
		$localize['data_high']                = esc_html( __( 'High', 'qa-heatmap-analytics' ) );
		$localize['data_very_high']           = esc_html( __( 'Very High', 'qa-heatmap-analytics' ) );
		$localize['keyword_pos_change']       = esc_html( __( 'Changes of the position for the keyword', 'qa-heatmap-analytics' ) );
		$localize['trend_line']               = esc_html( __( 'Trend Line', 'qa-heatmap-analytics' ) );
		$localize['click_num']                = esc_html( __( 'Clicks', 'qa-heatmap-analytics' ) );
		$localize['position_axis']            = esc_html( _x( 'Position', 'labeling the axis', 'qa-heatmap-analytics' ) );
		$localize['click_axis']               = esc_html( _x( 'Clicks', 'labeling the axis', 'qa-heatmap-analytics' ) );
		$localize['nodata_to_show']  		  = esc_html( __( 'No data to show.', 'qa-heatmap-analytics' ) );
		wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
	}



	/**
	 * seo_monitoring_keywordの更新
	 */
	function ajax_update_seo_monitoring_keyword() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, QAHM_Data_Api::NONCE_API ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}

		$keyword = $this->wrap_filter_input( INPUT_POST, 'keyword' );
		$this->wrap_update_option( 'seo_monitoring_keyword', $keyword );
		die();
	}
	

	/**
	 * rewrite tableの取得 ajax
	 */
	public function ajax_get_rewrite_table() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, QAHM_Data_Api::NONCE_API ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}
		
		$start_date = $this->wrap_filter_input( INPUT_POST, 'start_date' );
		$end_date   = $this->wrap_filter_input( INPUT_POST, 'end_date' );
		$table_ary  = $this->get_rewrite_table( $start_date, $end_date );
		echo wp_json_encode( $table_ary );
		die();
	}

	/**
	 * rewrite tableの取得
	 */
	public function get_rewrite_table( $start_date, $end_date ) {
		global $qahm_time;
		global $qahm_log;
		global $qahm_db;
		global $wpdb;
		global $qahm_google_api;

		// wp_postから上記範囲内の更新日付を持つ記事一覧を取得する
		$table_name = $qahm_db->prefix . 'posts';
		$post_types = get_post_types( array( 'exclude_from_search' => false ) );
		$wherein_post_types = "('" . implode( "', '", array_map( 'esc_sql', $post_types ) ) . "')";
		//$where    = " WHERE post_status = 'publish' AND post_type IN ('" . implode( "', '", array_map( 'esc_sql', $post_types ) ) . "') AND post_modified BETWEEN %s AND %s";
		//$order    = ' ORDER BY post_date DESC';
		//$query    = 'SELECT ID,post_modified FROM ' . $table_name . $where . $order;
		// phpcs:ignore  WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query.
		$post_ary = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $wherein_post_types is already escaped.
			 'SELECT ID,post_modified FROM ' . esc_sql($table_name) . " WHERE post_status = 'publish' AND post_type IN " . $wherein_post_types . ' AND post_modified BETWEEN %s AND %s ORDER BY post_date DESC',
			 $start_date,
			 $end_date
		), ARRAY_A );
		//var_dump($post_ary);
		//echo $start_date;
		//return;
		//echo '<br><br>';

		// dir
		$gsc_dir     = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc' );
		$summary_dir = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc/summary' );
		
		// 日付の差分を求め、その数ループして求める
		$table_ary      = array();
		$page_imp_ary   = array();
		$page_click_ary = array();
		//$query_id_ary = array();

		for ( $date_idx = 0, $date_max = $qahm_time->xday_num( $end_date, $start_date ); $date_idx <= $date_max; $date_idx++ ) {
			$tar_date          = $qahm_time->diff_str( $start_date, '+' . $date_idx . ' day', self::DATE_FORMAT );
			$gsc_lp_query_path = $gsc_dir . $tar_date . '_gsc_lp_query.php';

			if ( ! $this->wrap_exists( $gsc_lp_query_path ) ) {
				continue;
			}

			$lp_query_ary = $this->wrap_get_contents( $gsc_lp_query_path );
			$lp_query_ary = unserialize( $lp_query_ary );

			// 速度を重視し、テーブルへの格納は連想配列のkeyに対して行う（一致するキーなどのサーチ不要）
			// 使用する際はforeachを使う前提で作る
			for ( $lp_query_idx = 0, $lp_query_max = count( $lp_query_ary ); $lp_query_idx < $lp_query_max; $lp_query_idx++ ) {
				for ( $post_idx = 0, $post_max = count( $post_ary ); $post_idx < $post_max; $post_idx++ ) {
					if( $lp_query_ary[$lp_query_idx]['wp_qa_id'] === (int) $post_ary[$post_idx]['ID'] ) {
						
						$wp_qa_id = $lp_query_ary[$lp_query_idx]['wp_qa_id'];

						if ( ! array_key_exists( $wp_qa_id, $table_ary ) ) {
							$title = $lp_query_ary[$lp_query_idx]['title'];
							$url   = wp_parse_url( $lp_query_ary[$lp_query_idx]['url'] );
							$url   = $url['path'];
							$date  = substr( $post_ary[$post_idx]['post_modified'], 0, 10 );

							$table_ary[$wp_qa_id] = array(
								//'wp_qa_id' => $wp_qa_id,
								'title'              => $title,
								'url'                => $url,
								'date'               => $date,
								'total_impressions'  => 0,
								'total_clicks'       => 0,
								'change_impressions' => 0,
								'change_clicks'      => 0,
								'query'              => array(),
							);
						}

						if ( $lp_query_ary[$lp_query_idx]['query'] === null ) {
							continue;
						}
						
						$page_total_imp   = 0;
						$page_first_imp   = 0;
						$page_last_imp    = 0;
						$page_total_click = 0;
						$page_first_click = 0;
						$page_last_click  = 0;
						for ( $query_idx = 0, $query_max = count( $lp_query_ary[$lp_query_idx]['query'] ); $query_idx < $query_max; $query_idx++ ) {
							if ( $lp_query_ary[$lp_query_idx]['query'][$query_idx]['search_type'] !== 1 ) {
								continue;
							}
							$query_id = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['query_id'];
							$key      = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['keyword'];
							$imp      = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['impressions'];
							$click    = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['clicks'];
							$pos      = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['position'];
							$ctr      = 0;
							if ( $click > 0 && $imp > 0 ) {
								$ctr = round( $click / $imp * 100, 1 );
							}

							if ( ! array_key_exists( $key, $table_ary[$wp_qa_id]['query'] ) ) {
								$table_ary[$wp_qa_id]['query'][$key] = array();
								//$query_id_ary[$key]                  = $query_id;
							}
							$table_ary[$wp_qa_id]['query'][$key][] = array(
								'position'    => $pos,
								'impressions' => $imp,
								'clicks'      => $click,
								'ctr'         => $ctr,
							);

							// ページデータを求める
							$page_total_imp   += $imp;
							$page_total_click += $click;
						}

						$table_ary[$wp_qa_id]['total_impressions'] = $page_total_imp;
						$table_ary[$wp_qa_id]['total_clicks']      = $page_total_click;

						// 変化率を求めるためにこの日のデータを配列に保存していく
						if ( ! array_key_exists( $wp_qa_id, $page_imp_ary ) ) {
							$page_imp_ary[$wp_qa_id] = array();
						}
						if ( ! array_key_exists( $wp_qa_id, $page_click_ary ) ) {
							$page_click_ary[$wp_qa_id] = array();
						}
						$page_imp_ary[$wp_qa_id][]   = $page_total_imp;
						$page_click_ary[$wp_qa_id][] = $page_total_click;
						break;
					}
				}
			}
		}

		// 変化率を求める
		// $page_imp_aryと$page_click_aryは同じ配列数。それを前提にした処理にしている
		//var_dump( $page_imp_ary );
		$first_imp   = 0;
		$last_imp    = 0;
		$first_click = 0;
		$last_click  = 0;
		$first_cnt   = 0;
		$last_cnt    = 0;
		foreach ( $page_imp_ary as $wp_qa_id => $imp_ary ) {
			$imp_max = count( $imp_ary );
			$imp_mid = 0;
			if ( $imp_max > 0 ) {
				$imp_mid = $imp_max / 2;
			}

			for ( $imp_idx = 0; $imp_idx < $imp_max; $imp_idx++ ) {
				if ( $imp_idx < $imp_mid ) {
					$first_imp   += $page_imp_ary[$wp_qa_id][$imp_idx];
					$first_click += $page_click_ary[$wp_qa_id][$imp_idx];
					$first_cnt++;
				} else {
					$last_imp    += $page_imp_ary[$wp_qa_id][$imp_idx];
					$last_click  +=$page_click_ary[$wp_qa_id][$imp_idx];
					$last_cnt++;
				}
			}

			if ( $first_imp > 0 ) {
				$first_imp   /= $first_cnt;
			}
			if ( $first_click > 0 ) {
				$first_click /= $first_cnt;
			}
			if ( $last_imp > 0 ) {
				$last_imp   /= $last_cnt;
			}
			if ( $last_click > 0 ) {
				$last_click /= $last_cnt;
			}
			/*
			echo '<br>';
			echo '$first_imp:' . $first_imp;
			echo '<br>';
			echo '$last_imp:' . $last_imp;
			echo '<br>';
			echo '$first_click:' . $first_click;
			echo '<br>';
			echo '$last_click:' . $last_click;
			echo '<br>';
			*/
			if ( 1 < $imp_max ) {
				if ( $first_imp !== 0 ) {
					$table_ary[$wp_qa_id]['change_impressions'] = round( ( $last_imp - $first_imp ) / $first_imp * 100, 1 );
				}
				if ( $first_click !== 0 ) {
					$table_ary[$wp_qa_id]['change_clicks'] = round( ( $last_click - $first_click ) / $first_click * 100, 1 );
				}

				/*
				echo 'インプレッション変化率:' . $table_ary[$wp_qa_id]['change_impressions'];
				echo '<br>';
				echo 'クリック変化率:' . $table_ary[$wp_qa_id]['change_clicks'];
				echo '<br>';
				*/
			}
		}

		/*
		echo '<br><br>';
		var_dump( $table_ary );
		echo '<br><br>';
		*/

		// table.jsでそのまま使える形に加工
		// この段階で加工する意図としては、送るデータを選定してデータを軽くするため
		foreach ( $table_ary as $wp_qa_id => $post_ary ) {
			foreach ( $post_ary['query'] as $key => $query_ary ) {


				//echo '$key' . $key . '<br>';

				// 配列の数が8以上の場合のみ、7日間掲載順位の計算を行う。
				// 配列の数が7以下の場合は、1日のみの掲載順位を取得
				$total_click = 0;
				$total_imp   = 0;
				$total_ctr   = 0;
				$avg_ctr     = 0;
				$first_pos   = 0;
				$first_imp   = 0;
				//$first_click = 0;
				$first_ctr   = 0;
				$last_pos    = 0;
				$last_imp    = 0;
				//$last_click  = 0;
				$last_ctr    = 0;
				$gene_ctr    = null;
				$trend       = 1 * 100;
				$high_pos     = null;
				$low_pos     = null;
				$recent_ctr  = 0;

				// query_idを埋め込むコード
				// 今は使っていないので封印
				//$table_ary[$wp_qa_id]['query'][$key]['query_id'] = $query_id_ary[$key];

				$query_max = count( $query_ary );
				
				// 直前&前日順位
				$recent_pos = $query_ary[$query_max-1]['position'];
				if ( $query_max > 1 ) {
					$prev_pos = $query_ary[$query_max-2]['position'];
				} else {
					// 暫定
					$prev_pos = $recent_pos;
				}
				$table_ary[$wp_qa_id]['query'][$key]['recent_position'] = $recent_pos;
				$table_ary[$wp_qa_id]['query'][$key]['prev_position']   = $prev_pos;

				// 直前CTR
				if ( $query_ary[$query_max-1]['clicks'] > 0 && $query_ary[$query_max-1]['impressions'] > 0 ) {
					$recent_ctr = $query_ary[$query_max-1]['clicks'] / $query_ary[$query_max-1]['impressions'];
				}
				$table_ary[$wp_qa_id]['query'][$key]['recent_ctr'] = $recent_ctr;

				// 期間中トレンド
				if ( $query_max > 1 ) {
					$trend = ( $query_ary[0]['position'] / $query_ary[$query_max-1]['position'] - 1 )  * 100;
					$trend = round( $trend, 0 );
				}
				$table_ary[$wp_qa_id]['query'][$key]['trend'] = $trend;

				// 一般的なCTR
				$query_ary[$query_max-1]['clicks'] / $query_ary[$query_max-1]['impressions'];
				if ( $query_ary[$query_max-1]['position'] <= 10 ) {

					$gene_ctr;
				}


				/*
				if ( $query_max > 7 ) {
					// 初め7日間
					for ( $query_idx = 0; $query_idx < 7; $query_idx++ ) {
						$first_pos   += $query_ary[$query_idx]['position'];
						$first_imp   += $query_ary[$query_idx]['impressions'];
						//$first_click += $query_ary[$query_idx]['clicks'];
						$first_ctr   += $query_ary[$query_idx]['ctr'];
					}
					if ( $first_pos > 0 ) {
						$first_pos   = round( $first_pos / 7, 1 );
					}
					if ( $first_imp > 0 ) {
						$first_imp   = round( $first_imp / 7, 1 );
					}
					//if ( $first_click > 0 ) {
					//	$first_click = round( $first_click / 7, 1 );
					//}
					if ( $first_ctr > 0 ) {
						$first_ctr   = round( $first_ctr / 7, 1 );
					}
					$table_ary[$wp_qa_id]['query'][$key]['first_position']    = $first_pos;
					$table_ary[$wp_qa_id]['query'][$key]['first_impressions'] = $first_imp;
					//$table_ary[$wp_qa_id]['query'][$key]['first_clicks']      = $first_click;
					$table_ary[$wp_qa_id]['query'][$key]['first_ctr']         = $first_ctr;

					// 後ろ7日間
					for ( $query_idx = $query_max - 1; $query_idx >= $query_max - 7; $query_idx-- ) {
						$last_pos   += $query_ary[$query_idx]['position'];
						$last_imp   += $query_ary[$query_idx]['impressions'];
						//$last_click += $query_ary[$query_idx]['clicks'];
						$last_ctr   += $query_ary[$query_idx]['ctr'];
					}

					if ( $last_pos > 0 ) {
						$last_pos   = round( $last_pos / 7, 1 );
					}
					if ( $last_imp > 0 ) {
						$last_imp   = round( $last_imp / 7, 1 );
					}
					//if ( $last_click > 0 ) {
					//	$last_click = round( $last_click / 7, 1 );
					//}
					if ( $last_ctr > 0 ) {
						$last_ctr   = round( $last_ctr / 7, 1 );
					}
					$table_ary[$wp_qa_id]['query'][$key]['last_position']    = $last_pos;
					$table_ary[$wp_qa_id]['query'][$key]['last_impressions'] = $last_imp;
					//$table_ary[$wp_qa_id]['query'][$key]['last_clicks']      = $last_click;
					$table_ary[$wp_qa_id]['query'][$key]['last_ctr']         = $last_ctr;


				} else {
					// 初め1日
					$first_pos   = $query_ary[0]['position'];
					$first_imp   = $query_ary[0]['impressions'];
					//$first_click = $query_ary[0]['clicks'];
					$first_ctr   = $query_ary[0]['ctr'];
					$table_ary[$wp_qa_id]['query'][$key]['first_position']    = $first_pos;
					$table_ary[$wp_qa_id]['query'][$key]['first_impressions'] = $first_imp;
					//$table_ary[$wp_qa_id]['query'][$key]['first_clicks']      = $first_click;
					$table_ary[$wp_qa_id]['query'][$key]['first_ctr']         = $first_ctr;

					// 後ろ1日
					$last_pos   = $query_ary[$query_max-1]['position'];
					$last_imp   = $query_ary[$query_max-1]['impressions'];
					//$last_click = $query_ary[$query_max-1]['clicks'];
					$last_ctr   = $query_ary[$query_max-1]['ctr'];
					$table_ary[$wp_qa_id]['query'][$key]['last_position']    = $last_pos;
					$table_ary[$wp_qa_id]['query'][$key]['last_impressions'] = $last_imp;
					//$table_ary[$wp_qa_id]['query'][$key]['last_clicks']      = $last_click;
					$table_ary[$wp_qa_id]['query'][$key]['last_ctr']         = $last_ctr;
				}
				*/

				// 変化率
				$table_ary[$wp_qa_id]['query'][$key]['change_position'] = 0;
				if ( $first_pos > 0 && $last_pos > 0 ) {
					$table_ary[$wp_qa_id]['query'][$key]['change_position'] = round( ( $last_pos / $first_pos - 1 ) * -100, 1 );
				}
				$table_ary[$wp_qa_id]['query'][$key]['change_impressions'] = 0;
				if ( $first_imp > 0 && $last_imp > 0 ) {
					$table_ary[$wp_qa_id]['query'][$key]['change_impressions'] = round( ( $last_imp / $first_imp - 1 ) * -100, 1 );
				}
				//$table_ary[$wp_qa_id]['query'][$key]['change_clicks'] = 0;
				//if ( $first_click > 0 && $last_click > 0 ) {
				//	$table_ary[$wp_qa_id]['query'][$key]['change_clicks'] = round( ( $last_click / $first_click - 1 ) * -100, 1 );
				//}
				$table_ary[$wp_qa_id]['query'][$key]['change_ctr'] = 0;
				if ( $first_ctr > 0 && $last_ctr > 0 ) {
					$table_ary[$wp_qa_id]['query'][$key]['change_ctr'] = round( ( $last_ctr / $first_ctr - 1 ) * -100, 1 );
				}

				for ( $query_idx = 0; $query_idx < $query_max; $query_idx++ ) {
					//if ( $high_pos === null || $high_pos > $query_ary[$query_idx]['position'] ) {
					//	$high_pos = $query_ary[$query_idx]['position'];
					//}

					//if ( $low_pos === null || $low_pos < $query_ary[$query_idx]['position'] ) {
					//	$low_pos = $query_ary[$query_idx]['position'];
					//}
					
					$total_imp   += $query_ary[$query_idx]['impressions'];
					$total_click += $query_ary[$query_idx]['clicks'];
					$total_ctr   += $query_ary[$query_idx]['ctr'];

					/*
					echo 'position:' . $query_ary[$query_idx]['position'] . ', ';
					echo 'impressions:' . $query_ary[$query_idx]['impressions'] . ', ';
					echo 'clicks' . $query_ary[$query_idx]['clicks'] . ', ';
					echo 'ctr' . $query_ary[$query_idx]['ctr'] . ', ';
					echo '<br>';
					*/

					// 不要になった配列は削除してデータを軽くする
					unset( $table_ary[$wp_qa_id]['query'][$key][$query_idx] );
				}

				if ( $query_max > 0 && $total_ctr > 0 ) {
					$avg_ctr = round( $total_ctr / $query_max, 1 );
				}
				$table_ary[$wp_qa_id]['query'][$key]['total_clicks']      = $total_click;
				$table_ary[$wp_qa_id]['query'][$key]['total_impressions'] = $total_imp;
				$table_ary[$wp_qa_id]['query'][$key]['avg_ctr']           = $avg_ctr;
				//$table_ary[$wp_qa_id]['query'][$key]['high_pos']          = $high_pos;
				//$table_ary[$wp_qa_id]['query'][$key]['low_pos']           = $low_pos;

				//echo '<br>';
			}

			//echo '<br>';
		}

		return $table_ary;
	}


	/**
	 * 日付ごとのキーワードの情報を配列で取得
	 * 今は大本の連想配列の中に日付配列と順位配列しか入れていないが、後々拡張しても良い
	 */
	public function ajax_get_keyword_param_table() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, QAHM_Data_Api::NONCE_API ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}

		global $qahm_time;

		$start_date  = $this->wrap_filter_input( INPUT_POST, 'start_date' );
		$end_date    = $this->wrap_filter_input( INPUT_POST, 'end_date' );
		$wp_qa_id    = (int) $this->wrap_filter_input( INPUT_POST, 'wp_qa_id' );
		$keyword     = $this->wrap_filter_input( INPUT_POST, 'keyword' );

		// dir
		$gsc_dir     = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc' );

		// 日付の差分を求め、その数ループして求める
		$param_ary             = array();
		$param_ary['date']     = array();
		$param_ary['position'] = array();
		$last_pos              = 0;
		for ( $date_idx = 0, $date_max = $qahm_time->xday_num( $end_date, $start_date ) + 1; $date_idx <= $date_max; $date_idx++ ) {
			$tar_date          = $qahm_time->diff_str( $start_date, '+' . $date_idx . ' day', self::DATE_FORMAT );
			$gsc_lp_query_path = $gsc_dir . $tar_date . '_gsc_lp_query.php';

			$param_ary['date'][$date_idx]     = $tar_date;
			$param_ary['position'][$date_idx] = 0;

			if ( ! $this->wrap_exists( $gsc_lp_query_path ) ) {
				continue;
			}

			$lp_query_ary = $this->wrap_get_contents( $gsc_lp_query_path );
			$lp_query_ary = unserialize( $lp_query_ary );

			for ( $lp_query_idx = 0, $lp_query_max = count( $lp_query_ary ); $lp_query_idx < $lp_query_max; $lp_query_idx++ ) {
				if( $lp_query_ary[$lp_query_idx]['wp_qa_id'] !== $wp_qa_id ||
					$lp_query_ary[$lp_query_idx]['query'] === null ) {
					continue;
				}
				
				for ( $query_idx = 0, $query_max = count( $lp_query_ary[$lp_query_idx]['query'] ); $query_idx < $query_max; $query_idx++ ) {
					// ここ速度的にquery_idで判定したい
					if ( $lp_query_ary[$lp_query_idx]['query'][$query_idx]['keyword'] !== $keyword ||
						 $lp_query_ary[$lp_query_idx]['query'][$query_idx]['search_type'] !== 1 ) {
						continue;
					}
					
					$pos = $lp_query_ary[$lp_query_idx]['query'][$query_idx]['position'];
					$param_ary['position'][$date_idx] = $pos;
					$last_pos                         = $pos;
					break;
				}
				break;
			}
		}

		// 順位に0（初期値）が入っている配列を補正する
		$pos_max  = count( $param_ary['position'] );
		$prev_pos = $last_pos;
		for ( $pos_idx = $pos_max - 1; $pos_idx >= 0; $pos_idx-- ) {
			if ( $param_ary['position'][$pos_idx] === 0 ) {
				$param_ary['position'][$pos_idx] = $prev_pos;
			} else {
				$prev_pos = $param_ary['position'][$pos_idx];
			}
		}

		echo wp_json_encode( $param_ary );
		die();
	}

	/**
	 * maruyama add 全キーワードを日付ごとのキーワードの情報を配列で取得
	 * 今は大本の連想配列の中に日付配列と順位配列しか入れていないが、後々拡張しても良い
	 */
	public function ajax_get_all_keyword_param_table()
	{
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, QAHM_Data_Api::NONCE_API ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}


		$start_date = $this->wrap_filter_input( INPUT_POST, 'start_date' );
		$end_date = $this->wrap_filter_input( INPUT_POST, 'end_date' );
		$param_ary = $this->get_all_keyword_param_table( $start_date, $end_date );
		echo wp_json_encode( $param_ary );
		die();
	}

	// メモリリークを今後なんとかするのが課題
	public function get_all_keyword_param_table( $start_date, $end_date ) {

		//$start_mem_size = memory_get_usage();
		
		global $qahm_time;
		// dir
		$gsc_dir     = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc' );

		// 日付の差分を求め、その数ループして求める
		$param_ary             = array();
		for ( $date_idx = 0, $date_max = $qahm_time->xday_num( $end_date, $start_date ) + 1; $date_idx < $date_max; $date_idx++ ) {
			$tar_date          = $qahm_time->diff_str( $start_date, '+' . $date_idx . ' day', self::DATE_FORMAT );
			$gsc_lp_query_path = $gsc_dir . $tar_date . '_gsc_lp_query.php';

			if ( ! $this->wrap_exists( $gsc_lp_query_path ) ) {
				continue;
			}

			$lp_query_ary = $this->wrap_get_contents( $gsc_lp_query_path );
			$lp_query_ary = unserialize( $lp_query_ary );

			for ( $lp_query_idx = 0, $lp_query_max = count( $lp_query_ary ); $lp_query_idx < $lp_query_max; $lp_query_idx++ ) {
                $nowpage = $lp_query_ary[$lp_query_idx];
                $pid = $nowpage['page_id'];
                $wpi = $nowpage['wp_qa_id'];
				$ttl = $nowpage['title'];
				$url = $nowpage['url'];
				if ( isset($lp_query_ary[$lp_query_idx]['query'] ) ) {
                    for ( $query_idx = 0, $query_max = count( $lp_query_ary[$lp_query_idx]['query'] ); $query_idx < $query_max; $query_idx++ ) {
                        // ここ速度的にquery_idで判定したい
                        $nowquery = $lp_query_ary[$lp_query_idx]['query'][$query_idx];
                        $key = $nowquery['keyword'];
                        $pos = $nowquery['position'];
                        $imp = $nowquery['impressions'];
                        $clk = $nowquery['clicks'];

                        if ( isset( $param_ary[$pid]['keyword'][$key] ) ) {
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['impressions'] = $imp;
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['clicks']      = $clk;
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['position']    = $pos;
                        } else {
                            $param_ary[$pid]['wp_qa_id']        = $wpi;
                            $param_ary[$pid]['title']           = $ttl;
                            $param_ary[$pid]['url']             = $url;
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['impressions'] = $imp;
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['clicks']      = $clk;
                            $param_ary[$pid]['keyword'][$key]['date'][$date_idx]['position']    = $pos;
                        }
                    }
                }
			}
		}

		//$mem_size = memory_get_usage() - $start_mem_size; //変数コピー分のメモリサイズ計算
		//echo '$mem_size:' . $mem_size . '<br>';
		
		// 今だけメモリリーク対策のためparam_ary2に値を入れている。今後この変数を使わずphpで色々求めて対応予定
		// ↓ここから
		$param_ary2 = array();
        foreach ( $param_ary as $pid => $key_ary ) {
			foreach ( $key_ary['keyword'] as $key => $ary ) {
				for ( $ddd = 0; $ddd < $date_max; $ddd++ ) {
					if( $param_ary[$pid]['keyword'][$key]['date'][$ddd]['clicks'] > 0 ) {
						if ( ! isset($param_ary2[$pid]) ) {
							$param_ary2[$pid] = array();
						}
						if ( ! isset($param_ary2[$pid]['keyword']) ) {
							$param_ary2[$pid]['keyword'] = array();
						}
						if ( ! isset($param_ary2[$pid]['keyword'][$key]) ) {
							$param_ary2[$pid]['keyword'][$key] = array();
						}
						$param_ary2[$pid]['wp_qa_id'] = $param_ary[$pid]['wp_qa_id'];
						$param_ary2[$pid]['title'] = $param_ary[$pid]['title'];
						$param_ary2[$pid]['url'] = $param_ary[$pid]['url'];
						$param_ary2[$pid]['keyword'][$key]['date'] = $param_ary[$pid]['keyword'][$key]['date'];
						break;
					}
				}
			}
		}
		/*
		echo '<pre>';
		print_r( $param_ary );
		echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
		echo 'aaaaaaaaaaaaaaaaaaaaaa';
		print_r( $param_ary2 );
		echo '</pre>';
		*/
		$param_ary = $param_ary2;
		unset( $param_ary2 );
		// ↑ここまで


		// 飛んでいる日付を補正する
        // 初期値は0インプレッション/0クリック/nullとする
		
        foreach ( $param_ary as $pid => $key_ary ) {
		    foreach ( $key_ary['keyword'] as $key => $ary ) {


		        $newdate_ary = array();
                for ( $ddd = 0; $ddd < $date_max; $ddd++ ) {
                    if ( isset( $ary['date'][$ddd] ) ) {
                        $newdate_ary[$ddd] = $ary['date'][$ddd];
                    } else {
                        if ( $ddd === 0 ) {
                            $newdate_ary[$ddd]['impressions'] = 0;
                            $newdate_ary[$ddd]['clicks']      = 0;
                            $newdate_ary[$ddd]['position']    = null;
                        } else {
                            $newdate_ary[$ddd]['impressions'] = 0;
                            $newdate_ary[$ddd]['clicks']      = 0;
                            $newdate_ary[$ddd]['position']    = null;
                        }
                    }

                }
                $param_ary[$pid]['keyword'][$key]['date'] = $newdate_ary;
			}
			//$mem_size = memory_get_usage() - $start_mem_size; //変数コピー分のメモリサイズ計算
			//echo '$mem_size:' . $mem_size . '<br>';
        }

        return $param_ary;
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

		global $qahm_time;
		global $qahm_google_api;
		global $qahm_data_api;

		if ( ! $qahm_google_api->is_auth() ) {
			?>
			<p><?php echo esc_html( __( 'To use the SEO analysis, please authenticate Google API from the Settings.', 'qa-heatmap-analytics' ) ); ?></p>
			<p><a href="admin.php?page=qahm-config#tab_google"><?php echo esc_html( __( 'Go to the Settings', 'qa-heatmap-analytics' ) ); ?></a></p>
			<?php
			return;
		}
		$err_ary = $qahm_google_api->test_search_console_connect();
		if ( $err_ary ) {
			$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 130 126"><defs><style>.cls-1{fill:#00709e;}.cls-2{fill:none;}</style></defs><path class="cls-1" d="M312.85,477.41h-1.46a2.28,2.28,0,1,1,0-4.55h.66c20.14.07,29.47-4.91,33.75-9.16a17.25,17.25,0,0,0,5.09-12.9,21.7,21.7,0,0,0-8-16.54,2.28,2.28,0,0,1-.94-1.79c-.44-20.23-9.06-33-9.15-33.14a2.3,2.3,0,0,1-.19-2.24c0-.08,3.52-7.67,4-12.64.55-6,.52-16.44-5.89-18.84l-.11-.05c-2.09-.92-8.47,5.79-11.9,9.4-5.25,5.52-8.63,8.9-11.75,8.9a2.23,2.23,0,0,1-1-.22,18.27,18.27,0,0,0-14,0,2.23,2.23,0,0,1-1,.22c-3.12,0-6.5-3.38-11.75-8.9-3.43-3.6-9.81-10.32-11.9-9.4l-.11.05c-6.41,2.4-6.44,12.83-5.89,18.84.47,5,3.95,12.56,4,12.64a2.29,2.29,0,0,1-.19,2.25c-.09.12-8.71,12.9-9.15,33.13a2.29,2.29,0,0,1-1,1.81,21,21,0,0,0-7.95,16.52,17.26,17.26,0,0,0,5.1,12.9c4.21,4.2,13.36,9.16,33,9.16h1.44a2.28,2.28,0,0,1,0,4.55H286c-17.4.07-29.87-3.41-37-10.47a21.9,21.9,0,0,1-6.44-16.13,25.62,25.62,0,0,1,9-19.5c.62-17.83,7-29.86,9.22-33.45-1.07-2.48-3.46-8.44-3.88-13-1.16-12.56,1.95-20.9,8.76-23.48,5-2.13,10.8,4,16.95,10.44,2.62,2.76,6.5,6.84,8.17,7.43a22.95,22.95,0,0,1,16.53,0c1.65-.59,5.53-4.67,8.16-7.43,6.15-6.46,12-12.57,17-10.44,6.8,2.58,9.91,10.92,8.75,23.48-.42,4.55-2.81,10.51-3.87,13,2.17,3.59,8.59,15.62,9.22,33.47a26.11,26.11,0,0,1,8.95,19.48A21.9,21.9,0,0,1,349,466.94c-7,6.95-19.16,10.47-36.15,10.47Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M270.59,398.56c-1.22-.44-3.72-7.75-4.85-12.43-1.28-5.61-1.88-12.75,2.52-14.88a3.23,3.23,0,0,1,1.4-.34c2,0,3.48,2.14,4.95,4.21.35.49.7,1,1.07,1.47a29.44,29.44,0,0,0,5.75,5.19c1.88,1.38,2.44,1.94,2,2.57-.14.21-.44.33-1.38.7a23.45,23.45,0,0,0-7.12,4c-2.86,2.45-3.51,6.37-3.83,8.25-.13.83-.18,1.06-.32,1.2a.48.48,0,0,1-.23.1Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M327.41,398.56a.4.4,0,0,1-.23-.1c-.14-.14-.19-.37-.32-1.2-.32-1.89-1-5.81-3.83-8.25a23.41,23.41,0,0,0-7.1-4c-1-.39-1.27-.5-1.4-.7-.4-.63.16-1.2,2-2.57a29.52,29.52,0,0,0,5.76-5.2c.36-.47.71-1,1.06-1.45,1.47-2.08,3-4.22,4.95-4.22a3.16,3.16,0,0,1,1.39.34c4.42,2.13,3.81,9.3,2.52,14.94-1.59,6.37-3.8,12-4.84,12.37Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M278.77,412.57s-1.53,4.08,0,5.92a7.45,7.45,0,0,0,5.24,2,8.57,8.57,0,1,1-5.25-7.93Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M325.93,412.57s-1.53,4.08,0,5.92a7.47,7.47,0,0,0,5.25,2,8.59,8.59,0,1,1-5.26-7.93Z" transform="translate(-233 -357.5)"/><path class="cls-1" d="M299,437.87c-5.84,0-10.58,3-10.58,6.63S293.15,455,299,455s10.58-6.85,10.58-10.51S304.83,437.87,299,437.87Z" transform="translate(-233 -357.5)"/><rect id="_スライス_" data-name="&lt;スライス&gt;" class="cls-2" width="130" height="126"/></svg>';
			$err_text  = esc_html__( 'Failed to connect with Google API.', 'qa-heatmap-analytics' ) . '<br>';
			$err_text .= '<br>';
			$err_text .= 'error code: ' . esc_html( $err_ary['code'] ) . '<br>';
			$err_text .= 'error message: ' . esc_html( $err_ary['message'] );

			echo '<div class="qahm-announce-container">';
			echo '<div class="qahm-announce-icon">' . wp_kses_post( $svg_icon ) . '</div>';
			echo '<div>' . wp_kses_post( $err_text ) . '</div>';
			echo '</div>';
			return;
		}

		$keyword_ary     = $this->wrap_get_option( 'seo_monitoring_keyword' );
		$monitor_keyword = '';
		if ( $keyword_ary ) {
			$keyword_ary     = json_decode( $keyword_ary );
			$monitor_keyword = implode( "&#13;&#10;", $keyword_ary );
		}

		$end_date      = $qahm_time->diff_str( $qahm_time->today_str(), '-3 day', self::DATE_FORMAT );
		$start_date    = $qahm_time->diff_str( $qahm_time->today_str(), '-92 day', self::DATE_FORMAT );
		$end_date_2    = $qahm_time->diff_str( $qahm_time->today_str(), '-3 day', self::DATE_FORMAT );
		$start_date_2  = $qahm_time->diff_str( $qahm_time->today_str(), '-1250 day', self::DATE_FORMAT );	// biva用
		$biva_test_opt = '';
		//$biva_test_opt = '<option data-start-date="' . $start_date_2 . '" data-end-date="' . $end_date_2 . '">' . $start_date_2 . ' - ' . $end_date_2 . '（biva テスト用）</option>';
		?>

		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1>QA <?php echo esc_html( __( 'SEO Analysis', 'qa-heatmap-analytics' ) ); ?></h1>

            <div id="bl_calStickey">
                <div id="bl_calenderAndMenu" style="visibility: hidden;">
                    <p><i class="fas fa-calendar-check"></i> <?php echo esc_html( __( 'Date Range to analyze', 'qa-heatmap-analytics' ) ); ?></p>
                    <select id="analysis-date">
                    <option data-start-date="<?php echo esc_attr($start_date); ?>" data-end-date="<?php echo esc_attr($end_date); ?>"><?php echo esc_html($start_date); ?> - <?php echo esc_html($end_date); ?></option>
                    <?php //echo $biva_test_opt; ?>
					</select>
                </div>
				<p><?php echo esc_html__( 'Google Search Console data here does not include information from the previous day or the day before due to Google\'s specifications and the time required to fetch data.', 'qa-heatmap-analytics' ); ?></p>
				<div class="bl_reportField">
					<h2 id="h_realtime">
						<?php echo esc_html( __( 'Search Performance of the Pages which were published or updated during the date range', 'qa-heatmap-analytics' ) ); ?>
					</h2>
					<div id="tday_container" style="padding-top: 0px;">
						<div id="tday_upper" class="bl_contentsArea" style="padding-top: 0px;">

							<div class="test-block">								
								<p class="sub-title"><i class="fas fa-edit"></i> <?php echo esc_html( __( 'Pages published or updated', 'qa-heatmap-analytics' ) ); ?></p>
								<div id="rewrite-table-progbar"></div>
								<div id="rewrite-table"></div>
								<table id="table-rewrite-list"></table>
							</div>
						</div>
					</div>
	
					<h2 id="h_realtime">
						<?php echo esc_html( __( 'Search Position and Goal Completion', 'qa-heatmap-analytics' ) ); ?>
					</h2>
					<div id="tday_container" style="padding-top: 0px;">
						<div id="tday_upper" class="bl_contentsArea" style="padding-top: 0px;">
							<div style="display:none;">
								<div class="test-block">
									<p class="sub-title"><i class="fas fa-spell-check"></i> <?php echo esc_html( __( 'Keyword(s) to be monitored', 'qa-heatmap-analytics' ) ); ?></p>
									<p><?php echo esc_html( __( 'Enter one keyword per line.', 'qa-heatmap-analytics' ) ); ?></p>
									<textarea id="monitor-keyword-textarea" cols="30" rows="5"><?php echo esc_textarea($monitor_keyword); ?></textarea><br>
									<input type="button" id="monitor-keyword-input" value="<?php echo esc_html( __( 'Reflect keyword(s) in the table', 'qa-heatmap-analytics' ) ); ?>" class="button-primary">
								</div>
	<!--maru 20220919-->
	<!--							<hr class="fade">-->
	<!--maru 20220919 end-->

								
								<div class="test-block">
									<p class="sub-title"><i class="fas fa-spell-check"></i> <?php echo esc_html( __( 'Keyword(s) under monitoring', 'qa-heatmap-analytics' ) ); ?></p>
									<p><?php echo esc_html( __( 'You can check the data of keywords entered in the above form.', 'qa-heatmap-analytics' ) ); ?></p>
									<div class="bl_goalradio">
									<?php
										$goals_ary = $qahm_data_api->get_goals_array();
										echo '<input type="radio" id="js_manualGoals_0" name="js_manualGoals" checked><label for="js_manualGoals_0">'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
										foreach ( $goals_ary as $gid => $goal ) {
											echo '<input type="radio" id="', esc_attr('js_manualGoals_'. $gid), '" name="js_manualGoals"><label for="', esc_attr('js_manualGoals_'. $gid), '">'. esc_html(urldecode( $goal["gtitle"])), '</label>';
										}
									?>
									</div>
									<div id="ManualKeyTableProg"></div>
									<div id="ManualKeyTable"></div>
								</div>
							</div

							<hr class="fade">

							<div class="test-block">
								<p class="sub-title"><i class="fas fa-spell-check"></i> <?php echo esc_html( __( 'All Keywords', 'qa-heatmap-analytics' ) ); ?></p>
                                <div class="bl_goalradio">
                                <?php
                                    $goals_ary = $qahm_data_api->get_goals_array();
                                    echo '<input type="radio" id="js_autoGoals_0" name="js_autoGoals" checked><label for="js_autoGoals_0">'. esc_html__( 'All Goals', 'qa-heatmap-analytics' ) . '</label>';
                                    foreach ( $goals_ary as $gid => $goal ) {
                                        echo '<input type="radio" id="', esc_attr('js_autoGoals_'. $gid), '" name="js_autoGoals"><label for="', esc_attr('js_autoGoals_'. $gid), '">', esc_html(urldecode( $goal["gtitle"])), '</label>';
                                    }
                                ?>
                                </div>
								<div id="AutoKeyTableProg"></div>
								<div id="AutoKeyTable"></div>
							</div>

							<hr class="fade">

							<span id="seo-monitoring-keyword-detail-pos"></span>
							<div class="test-block" id="seo-monitoring-keyword-detail">
								<p class="sub-title"><i class="fas fa-search-plus"></i> <?php echo esc_html( __( 'Search Keyword Detail Information', 'qa-heatmap-analytics' ) ); ?></p>
									
								<div class="test-2-block box11">
									<div id="seo-keyword-detail-title-block">
										<h4 id="seo-keyword-detail-keyword" style="font-size:18px;">
											<i class="fas fa-long-arrow-alt-right"></i> 
											<a href="" target="_blank" rel="noopener noreferrer">
												<strong></strong>
											</a>
										</h4>
										<p id="seo-keyword-detail-title"></p>
										<span id="seo-keyword-detail-url"></span>
										<a id="seo-keyword-detail-edit" href="" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( 'Edit', 'qa-heatmap-analytics' ) ); ?></a>
									</div>
                                    <div class="flex_test-box">
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Recent Changes', 'qa-heatmap-analytics' ) ); ?></div>
											<div id="seo-keyword-detail-change-pos" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Recent Position', 'qa-heatmap-analytics' ) ); ?></div>
											<div id="seo-keyword-detail-recent-pos" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'The Highest Position', 'qa-heatmap-analytics' ) ); ?></div>
											<div id="seo-keyword-detail-high-pos" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'The Lowest Position', 'qa-heatmap-analytics' ) ); ?></div>
											<div id="seo-keyword-detail-low-pos" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Recent CTR', 'qa-heatmap-analytics' ) ); ?> <span class="qahm-tooltip" data-qahm-tooltip="<?php echo esc_html( __( 'The CTR for the most recent date in the period.', 'qa-heatmap-analytics' ) ); ?>"><i class="far fa-question-circle"></i></span></div>
											<div id="seo-keyword-detail-recent-ctr" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Ordinary CTR', 'qa-heatmap-analytics' ) ); ?> <span class="qahm-tooltip" data-qahm-tooltip="<?php echo esc_html( __( 'Based on SEO Clarity\'s survey results and other data, the average CTR for a listing from 10th to 1st position is shown. 11 or lower positions are not shown.', 'qa-heatmap-analytics' ) ); ?>"><i class="far fa-question-circle"></i></span></div>
											<div id="seo-keyword-detail-general-ctr" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Trend', 'qa-heatmap-analytics' ) ); ?> <span class="qahm-tooltip" data-qahm-tooltip="<?php echo esc_html( __( 'It is the value of the slope of the regression line that represents the daily fluctuations in the positioning. E.g.) When the place improves by 20 positions in 100 days, "+20%" will be shown.', 'qa-heatmap-analytics' ) ); ?>"><i class="far fa-question-circle"></i></span></div>
											<div id="seo-keyword-detail-trend" class="flex_test-value"></div>
										</div>
										<div class="flex_test-item">
											<div class="flex_test-title"><?php echo esc_html( __( 'Reliability of Trend', 'qa-heatmap-analytics' ) ); ?> <span class="qahm-tooltip" data-qahm-tooltip="<?php echo esc_html( __( 'It is shown based on the correlation coefficient of the trend regression line. The correlation coefficient is displayed in (parentheses).', 'qa-heatmap-analytics' ) ); ?>"><i class="far fa-question-circle"></i></span></div>
											<div id="seo-keyword-detail-trend-reliability" class="flex_test-value"></div>
										</div>
									</div>
									<hr class="fade">
									<div class="test-2-block" id="bl_keyRankGraphContainer">
										<canvas id="keyRankGraph" height="400px"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
            </div>
			</div>
		</div>
		<?php
	}


} // end of class
