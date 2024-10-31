<?php
/**
 * ヒートマップビューで様々な操作をやりやすくするクラス（予定）
 *
 * @package qa_heatmap
 */

$qahm_view_heatmap = new QAHM_View_Heatmap();

class QAHM_View_Heatmap extends QAHM_View_base {

	const ATTENTION_LIMIT_TIME = 30;
	//熟読度
	//4.0.0.0から50%ではなく40%のユーザーが熟読した箇所をMAX15にしたいため、30->37.5へ変更
    const MAX_READING_LEVEL = 37.5;
	const ONE_PER_SIX = 1 / 6;
	const FOUR_PER_SIX = 4 / 6;

	public function __construct() {
		$this->regist_ajax_func( 'ajax_create_heatmap_file' );
		$this->regist_ajax_func( 'ajax_create_heatmap_file_zero' );
		$this->regist_ajax_func( 'ajax_init_heatmap_view' );
		//$this->regist_ajax_func( 'ajax_change_rec_checkbox' );
		//$this->regist_ajax_func( 'ajax_change_heatmap_view_bar' );
		//QA ZERO
		$this->regist_ajax_func( 'ajax_get_separate_data' );
		//QA ZERO END
		add_action( 'init', array( $this, 'init_wp_filesystem' ) );
	}

	public function get_heatmap_view_work_dir_url() {
		return parent::get_data_dir_url() . 'heatmap-view-work/';
	}

	public function enqueue_scripts() {
		// グローバルなスクリプトとスタイルのキューを取得
		global $wp_scripts;
		global $wp_styles;
		global $qahm_log;
		global $qahm_time;
		global $wp_filesystem;
	
		$version_id        = (int) filter_input( INPUT_GET, 'version_id' );
		$heatmap_view_work_dir = $this->get_data_dir_path( 'heatmap-view-work' );
		$heatmap_view_work_url = $this->get_heatmap_view_work_dir_url();
		$file_info             = $heatmap_view_work_dir . $version_id . '-info.php';
		if ( ! $wp_filesystem->exists( $file_info ) ) {
			throw new Exception( 'heatmap view info file does not exist.' );
		}
		$content_info_ary = $wp_filesystem->get_contents_array( $file_info );
	
	
		// info ファイル読み込み
		foreach ( $content_info_ary as $content_info ) {
			$exp_info = explode( '=', $content_info );
			switch ( $exp_info[0] ) {
				case 'data_num':
					$data_num = (int) trim( $exp_info[1] );
					break;
				case 'wp_qa_type':
					$wp_qa_type = trim( $exp_info[1] );
					break;
				case 'wp_qa_id':
					$wp_qa_id = (int) trim( $exp_info[1] );
					break;
				case 'version_no':
					$version_no = (int) trim( $exp_info[1] );
					break;
				case 'device_name':
					$device_name = trim( $exp_info[1] );
					break;
				case 'time_on_page':
					$time_on_page = (float) trim( $exp_info[1] );
					$time_on_page = $qahm_time->seconds_to_timestr( $time_on_page );
					$time_on_page = substr( $time_on_page, strlen('00:') );
					break;
			}
		}
	
		// 読み込まれているすべてのスクリプトを解除
		foreach( $wp_scripts->queue as $handle ) {
			wp_dequeue_script( $handle );
		}
	
		// 読み込まれているすべてのスタイルを解除
		foreach( $wp_styles->queue as $handle ) {
			wp_dequeue_style( $handle );
		}
	
		// 自分たちのプラグインのスタイルやスクリプトを読み込む
		$css_dir_url = $this->get_css_dir_url();
		wp_enqueue_style( QAHM_NAME . '-sweet-alert-2', $css_dir_url . '/lib/sweet-alert-2/sweetalert2.min.css', null, QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-doctor-reset', $css_dir_url . 'doctor-reset.css', array( QAHM_NAME . '-sweet-alert-2' ), QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-common', $css_dir_url . 'common.css', array( QAHM_NAME . '-doctor-reset' ), QAHM_PLUGIN_VERSION );
		wp_enqueue_style( QAHM_NAME . '-heatmap-view', $css_dir_url . 'heatmap-view.css', array( QAHM_NAME . '-doctor-reset' ), QAHM_PLUGIN_VERSION );
	
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( QAHM_NAME . '-font-awesome',  $js_dir_url . 'lib/font-awesome/all.min.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-sweet-alert-2',  $js_dir_url . 'lib/sweet-alert-2/sweetalert2.min.js', array( 'jquery' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-alert-message',  $js_dir_url . 'alert-message.js', array( QAHM_NAME . '-sweet-alert-2' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-common',  $js_dir_url . 'common.js', array( 'jquery' ), QAHM_PLUGIN_VERSION, false );

		if ( $data_num === 0 ) {
			return;
		}

		$js_dir_url = $this->get_js_dir_url();
		wp_enqueue_script( QAHM_NAME . '-load-screen',  $js_dir_url . 'load-screen.js', array( QAHM_NAME . '-common' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-cap-create',  $js_dir_url . 'cap-create.js', array( QAHM_NAME . '-load-screen' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-lib-heatmap',  $js_dir_url . 'lib/heatmap/heatmap.min.js', array( QAHM_NAME . '-cap-create' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-heatmap-view',  $js_dir_url . 'heatmap-view.js', array( QAHM_NAME . '-lib-heatmap' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-heatmap-bar',  $js_dir_url . 'heatmap-bar.js', array( QAHM_NAME . '-heatmap-view' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-heatmap-main',  $js_dir_url . 'heatmap-main.js', array( QAHM_NAME . '-heatmap-bar' ), QAHM_PLUGIN_VERSION, false );
	
		$scripts = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'type' => $wp_qa_type,
			'id' => $wp_qa_id,
			'ver' => $version_no,
			'dev' => $device_name,
			'version_id' => $version_id,
			'attention_limit_time' => self::ATTENTION_LIMIT_TIME,
		);
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );
		
		$localize = array(
			'people' => esc_html_x( 'people', 'counting number (unit) of people', 'qa-heatmap-analytics' ),
			);
        wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
	}


	/**
	 * ヒートマップ表示用のファイルを作成
	 */
	public function ajax_create_heatmap_file_zero() {
		global $qahm_log;

		try {
			$start_date  = $this->wrap_filter_input( INPUT_POST, 'start_date' );
			$end_date    = $this->wrap_filter_input( INPUT_POST, 'end_date' );
			$page_id     = (int) $this->wrap_filter_input( INPUT_POST, 'page_id' );
			$device_name = $this->wrap_filter_input( INPUT_POST, 'device_name' );

			$this->write_wp_load_path();
			$version_id = $this->create_heatmap_file_zero( $start_date, $end_date, $page_id, $device_name );

			//$heatmap_view_url  = esc_url( plugin_dir_url( __FILE__ ) . 'zero-heatmap-view.php' ) . '?';
			$heatmap_view_url  = esc_url( plugin_dir_url( __FILE__ ) . 'heatmap-view.php' ) . '?';
			$heatmap_view_url .= 'version_id=' . $version_id;

			echo wp_json_encode( esc_url_raw( $heatmap_view_url ) );

		} catch ( Exception $e ) {
			$log = $qahm_log->error( $e->getMessage() );
			echo wp_json_encode( esc_html( $log ) );

		} finally {
			die();
		}
	}


	//メモ
	// base_selectorの配列にversion_idごとのキー配列を作る
	// そこを参照する
	// 期間内の全PVをもってくる

	/**
	 * ヒートマップビュー用の一時ファイルを作成
	 * 関数名は一時的なもの。残ったQAの処理をこちらの関数に移行した際にzeroを外す予定
	 */
	public function create_heatmap_file_zero( $start_date, $end_date, $page_id, $device_name ) {
		global $qahm_db;
		global $wp_filesystem;
		global $qahm_log;
		global $qahm_time;

		//ゴールセッションを取得
		global $qahm_data_api;
		$dateterm = 'date = between ' . substr($start_date, 0, 10) . ' and ' . substr($end_date, 0, 10);
		// $goals_sessions配列をキーをpv_idとする新しい配列に変換します。
		$goals_sessions_keys = array();
		$all_goals_sessions = $qahm_data_api->get_goals_sessions( $dateterm );
		foreach ($all_goals_sessions as $goals_sessions ) {
			foreach ( $goals_sessions as $goal_session ) {
				foreach ( $goal_session as $session ) {
					$goals_sessions_keys[ $session[ 'pv_id' ] ] = true;
				}
			}
		}
		//ヒートマップのデータを取得
		$heatmap_view_work_dir = $this->get_data_dir_path( 'heatmap-view-work' );
		$this->wrap_mkdir( $heatmap_view_work_dir );

		// base.htmlをdbから取得
		$base_html            = null;
		$base_selector_ary    = array();
		$qa_pages             = null;
		$qa_page_version_hist = null;
		$base_url             = null;
		$version_id           = null;
		$version_no           = null;
		$device_id            = $this->device_name_to_device_id( $device_name );
		$wp_qa_type           = null;
		$wp_qa_id             = null;
		$start_unixtime       = $qahm_time->str_to_unixtime( $start_date );
		$end_unixtime         = $qahm_time->str_to_unixtime( $end_date );

		$merge_att_scr_ary_v1 = array();
		$merge_att_scr_ary_v2 = array();
		$merge_click_ary   = array();
		//QA ZERO
		// QA ZERO はv2のみ
		$pkey_merge_att_scr_ary_v2 = array();
		$separate_merge_att_scr_ary_v2 = array();
		$separate_total_stay_time = array();
		$separate_exit_idx = array();
		$separate_merge_click_ary = array();
		$separate_data_num = array();
		$separate_time_on_page = array();
		//QA ZERO END
		$data_num          = 0;
		$time_on_page      = 0;

		// page_idから最新のversion_idを求める
		$table_name = 'view_page_version_hist';
		$query      = 'SELECT version_id,device_id,version_no,base_html,base_selector FROM ' . $qahm_db->prefix . $table_name . ' WHERE page_id = %d';
		$qa_page_version_hist   = $qahm_db->get_results( $qahm_db->prepare( $query, $page_id ), ARRAY_A );
		if ( ! $qa_page_version_hist ) {
			return null;
		}

		// ルール
		// ヒートマップビューでは最新のbase_htmlを使用する
		// 格納する$version_idや$version_noも最新のものを使用する
		// base_selectorはversion_idをキーとした配列を作成する
		foreach ( $qa_page_version_hist as $hist ) {
			$base_selector_ary[(int)$hist['version_id']] = $hist['base_selector'];

			if ( $version_no ) {
				if ( $device_id === (int) $hist['device_id'] && $version_no < (int) $hist['version_no'] ) {
					$version_id    = (int) $hist['version_id'];
					$version_no    = (int) $hist['version_no'];
					$base_html     = $hist['base_html'];
				}
			} else {
				if ( $device_id === (int) $hist['device_id'] ) {
					$version_id    = (int) $hist['version_id'];
					$version_no    = (int) $hist['version_no'];
					$base_html     = $hist['base_html'];
				}
			}
		}

		// wp_qa_type,wp_qa_idはZEROではいらないがエラー回避のため今は残しておく
		$table_name = 'qa_pages';
		$query      = 'SELECT wp_qa_type,wp_qa_id,url FROM ' . $qahm_db->prefix . $table_name . ' WHERE page_id = %d';
		$qa_pages   = $qahm_db->get_results( $qahm_db->prepare( $query, $page_id ), ARRAY_A );
		if ( $qa_pages ) {
			$page       = $qa_pages[0];
			$wp_qa_type = $page['wp_qa_type'];
			$wp_qa_id   = $page['wp_qa_id'];
			$base_url   = $page['url'];
		}

		//speed up 2023/12/03 by maruyama
		$table_name = 'view_pv';
		$query      = 'SELECT pv_id,device_id,access_time,version_id,is_raw_p,is_raw_c,is_raw_e FROM ' . $qahm_db->prefix . $table_name . ' WHERE page_id = %d and access_time between %s and %s';
		$res_ary    = $qahm_db->get_results( $qahm_db->prepare( $query, $page_id, $start_date, $end_date ), ARRAY_A );
		if ( $res_ary ) {
			foreach( $res_ary as $pv ) {
				$access_time = $qahm_time->str_to_unixtime( $pv['access_time'] );
				if ( $access_time < $start_unixtime || $access_time > $end_unixtime ) {
					continue;
				}
				$qa_pv_log[] = $pv;
			}
		}

		if ( $qa_pv_log ) {
			usort($qa_pv_log, function($a, $b) {
				global $qahm_time;
				$a_access_time = $qahm_time->str_to_unixtime( $a['access_time'] );
				$b_access_time = $qahm_time->str_to_unixtime( $b['access_time'] );
                return ($a_access_time < $b_access_time) ? -1 : (($a_access_time > $b_access_time) ? 1 : 0);
			});

			// 100分率 + 精読率100%の分配列を用意
			for ( $iii = 0; $iii < 100 + 1; $iii++ ) {
				$merge_att_scr_ary_v1[ $iii ] = array( $iii, 0, 0, 0 );
			}

			// base_selectorの配列
			$click_idx         = 0;

			$total_stay_time = 0;

			$view_pv_dir   = $this->get_data_dir_path( 'view' ) . $this->get_tracking_id() . '/view_pv/';
			$raw_p_dirlist = $this->wrap_dirlist( $view_pv_dir . 'raw_p/' );
			$raw_c_dirlist = $this->wrap_dirlist( $view_pv_dir . 'raw_c/' );

			$raw_p_filemap = $this->file_mapping_cache( $raw_p_dirlist );
			$raw_c_filemap = $this->file_mapping_cache( $raw_c_dirlist );
			$last_date = null; // 前回処理した日付を追跡するための変数

			foreach ( $qa_pv_log as $pv_log ) {
				if ( (int) $pv_log['device_id'] !== $device_id ) {
					continue;
				}

				$raw_p_tsv = null;
				$raw_c_tsv = null;

				//QA ZERO
				// $separate_merge_click_aryの作成
				$utm_medium = isset($pv_log['utm_medium']) ? $pv_log['utm_medium'] : '(not set)';
				$utm_source = isset( $pv_log['utm_source'] ) ? $pv_log['utm_source'] : null;

				if ( ! empty( $utm_source ) ) {
					$source_domain = $utm_source;
				} else {
					$source_domain = isset( $pv_log['source_domain'] ) ? $pv_log['source_domain'] : '(not set)';
				}

				if ( empty( $utm_medium ) ) {
					$utm_medium = '(not set)';
				}

				if ( empty( $source_domain ) ) {
					$source_domain = '(not set)';
				}

				// pv_idが$goals_sessions_keys配列のキーに存在するかどうかを確認します。
				if ( isset( $pv_log[ 'pv_id' ]) && is_array( $goals_sessions_keys ) !== null ) {
					$is_goal = array_key_exists($pv_log['pv_id'], $goals_sessions_keys) ? "○" : "×";
				} else {
					$is_goal = "(不明)";
				}

				// キーを作成します。
				$key = $utm_medium . '_' . $source_domain . '_' . $is_goal;


				if ( !isset( $separate_merge_att_scr_ary_v2[$key] ) ) {
					$separate_merge_att_scr_ary_v2[$key] = array();
					$separate_total_stay_time[$key] = 0;
				}
				//QA ZERO END

				if ( array_key_exists( 'is_raw_p', $pv_log ) ) {
					// view_pv
					if ( $pv_log['is_raw_p'] || $pv_log['is_raw_c'] || $pv_log['is_raw_e'] ) {
						$pv_id = $pv_log['pv_id'];
						// 日付を取得し、ファイル名を特定
						// QAではaccess_timeは日付時刻になっているので、日付のみのフォーマットに変換
						$current_date = $pv_log['access_time'];
						$current_date = substr($current_date, 0, 10);
						if ( $last_date !== $current_date ) {
							$last_date = $current_date; // 日付を更新
							if ( isset( $raw_p_filemap[ $current_date ] ) ) {
								$raw_p_file = $raw_p_filemap[ $current_date ];
								$raw_c_file = $raw_c_filemap[ $current_date ];
								$raw_p_data_ary = $this->wrap_unserialize( $this->wrap_get_contents( $view_pv_dir . 'raw_p/' . $raw_p_file ) );
								$raw_p_cached_ary = array();
								foreach ( $raw_p_data_ary as $raw_p_data ) {
									$raw_p_cached_ary[$raw_p_data['pv_id']] = $raw_p_data['raw_p'];
								}
								$raw_c_data_ary = $this->wrap_unserialize( $this->wrap_get_contents( $view_pv_dir . 'raw_c/' . $raw_c_file ) );
								$raw_c_cached_ary = array();
								foreach ( $raw_c_data_ary as $raw_c_data ) {
									$raw_c_cached_ary[$raw_c_data['pv_id']] = $raw_c_data['raw_c'];
								}
							} else {
								continue;
							}
						}
						if ( $pv_log['is_raw_p'] ) {
							if ( isset( $raw_p_cached_ary[$pv_id]) ) {
								$raw_p_tsv = $raw_p_cached_ary[$pv_id];
							}
						}

						if ( $pv_log['is_raw_c'] ) {
							if ( isset( $raw_c_cached_ary[$pv_id]) ) {
								$raw_c_tsv = $raw_c_cached_ary[$pv_id];
							}
						}

						if ( $raw_p_tsv || $raw_c_tsv ) {
							$data_num++;
							//QA ZERO
							if ( !isset( $separate_data_num[$key] ) ) {
								$separate_data_num[$key] = 0;
							}
							$separate_data_num[$key]++;
							//QA ZERO END
						}
					}
				} else {
					// qa_pv_log
					if ( $pv_log['raw_p'] || $pv_log['raw_c'] || $pv_log['raw_e'] ) {
						$raw_p_tsv = $pv_log['raw_p'];
						$raw_c_tsv = $pv_log['raw_c'];

						$data_num++;
						//QA ZERO
						$separate_data_num[$key]++;
						//QA ZERO END
					}
				}

				if ( $raw_p_tsv ) {
					$raw_p_ary   = $this->convert_tsv_to_array( $raw_p_tsv );
					$exit_idx    = -1;
					//QA ZERO
					$separate_exit_idx[$key] = -1;
					$raw_p_max = count($raw_p_ary);
					//QA ZERO END
					// 滞在時間
					$ver = (int) $raw_p_ary[self::DATA_COLUMN_HEADER][self::DATA_HEADER_VERSION];
					if ( $ver === 2 ) {
						// 最大の滞在時間を取得
						$max_stay_time = 0;
						foreach ($raw_p_ary as $p) {
							if (isset ($p[self::DATA_POS_2['STAY_TIME']])) {
								if ( $p[ self::DATA_POS_2[ 'STAY_TIME' ] ] > $max_stay_time ) {
									$max_stay_time = $p[ self::DATA_POS_2[ 'STAY_TIME' ] ];
								}
							}
						}
						if ( $max_stay_time <= 0 ) {
							$max_stay_time = 1;
						}
						if ( self::ATTENTION_LIMIT_TIME < $max_stay_time ) {
							$max_stay_time = self::ATTENTION_LIMIT_TIME;
						}
						//1pvの滞在時間を全ポジションごとに処理する
						for ( $raw_p_idx = self::DATA_COLUMN_BODY; $raw_p_idx < $raw_p_max; $raw_p_idx++ ) {
							$p = $raw_p_ary[$raw_p_idx];
							if ( ! isset( $p[ self::DATA_POS_2['STAY_HEIGHT'] ] ) ) {
								break;
							}

							if ( $p[ self::DATA_POS_2['STAY_HEIGHT'] ] === 'a' ) {
								// あとでこの処理を詰める
								break;
							}

							$stay_time = min( (int) $p[ self::DATA_POS_2['STAY_TIME'] ], self::ATTENTION_LIMIT_TIME );
							// 滞在時間を熟読度に変換。センターに4/6、その前後に1/6ずつ割り振る（正規分布）
							if ($max_stay_time <= 2) {
								// max_stay_timeが2秒以下の場合、reading_levelを固定値に設定
								$reading_level = $stay_time == 2 ? 4 : 2;
							} else {
								$reading_level = ($stay_time / $max_stay_time) * self::MAX_READING_LEVEL;
							}
							//$merge_att_scr_ary_v2[STAY_HEIGHT]の中身
							// STAY_HEIGHT：ユーザーが滞在したページの高さを示す値。100pxで割った値
							// STAY_TIME：その高さでユーザーが滞在した時間。秒単位だったが熟読度に変更。
							// STAY_NUM：その高さで滞在したユーザーの数。
							// EXIT_NUM：その高さでページを離脱したユーザーの数。

							//まず現在処理中の STAY_HEIGHT を取得
							$merge_att_scr_idx = (int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ];
							// 中心のインデックスに4/6を割り振る
							if ( isset( $merge_att_scr_ary_v2[ $merge_att_scr_idx ] ) ) {
								$merge_att_scr_ary_v2[ $merge_att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ] += $reading_level * 4 / 6;
								$merge_att_scr_ary_v2[ $merge_att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_NUM' ] ]++;
							} else {
								$merge_att_scr_ary_v2[ $merge_att_scr_idx ] = array(
									(int)$p[ self::DATA_POS_2[ 'STAY_HEIGHT' ] ],
									$reading_level * self::FOUR_PER_SIX,
									self::FOUR_PER_SIX,
									0
								);
							}

							// 前後のインデックスが存在する場合、それぞれに1/6を割り振る
							if ( $merge_att_scr_idx - 1 >= 0 ) {
								if ( isset( $merge_att_scr_ary_v2[ $merge_att_scr_idx - 1 ] ) ) {
									$merge_att_scr_ary_v2[ $merge_att_scr_idx - 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ] += $reading_level * 1 / 6;
									$merge_att_scr_ary_v2[ $merge_att_scr_idx - 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_NUM' ] ]++;
								} else {
									$merge_att_scr_ary_v2[ $merge_att_scr_idx - 1 ] = array(
										(int)$p[ self::DATA_POS_2[ 'STAY_HEIGHT' ] ] - 1,
										$reading_level * self::ONE_PER_SIX,
										self::ONE_PER_SIX,
										0
									);
								}
							}

							if ( isset( $merge_att_scr_ary_v2[ $merge_att_scr_idx + 1 ] ) ) {
								$merge_att_scr_ary_v2[ $merge_att_scr_idx + 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ] += $reading_level * 1 / 6;
								$merge_att_scr_ary_v2[ $merge_att_scr_idx + 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_NUM' ] ]++;
							} else {
								$merge_att_scr_ary_v2[ $merge_att_scr_idx + 1 ] = array(
									(int)$p[ self::DATA_POS_2[ 'STAY_HEIGHT' ] ] + 1,
									$reading_level * self::ONE_PER_SIX,
									self::ONE_PER_SIX,
									0
								);
							}

							// 離脱位置を更新。既にソートされた配列なので比較の必要なし
							if ( $exit_idx < $merge_att_scr_idx ) {
								$exit_idx = $merge_att_scr_idx;
							}

							// 合計滞在時間に加算
							$total_stay_time += $stay_time;


							//QA ZERO
							$separate_merge_att_scr_idx = (int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ];
							// 中心のインデックスに4/6を割り振る
							if ( isset( $separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx] ) ) {
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME']] += $reading_level * 4 / 6;
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM']]++;
							} else {
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx] = array(
									(int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ],
									$reading_level * self::FOUR_PER_SIX,
									self::FOUR_PER_SIX,
									0
								);
							}

							// 前後のインデックスが存在する場合、それぞれに1/6を割り振る
							if ($separate_merge_att_scr_idx - 1 >= 0) {
								if ( isset ($separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx - 1] ) ) {
									$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx - 1][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME']] += $reading_level * 1 / 6;
									$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx - 1][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM']]++;
								} else {
									$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx - 1] = array(
										(int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ] - 1,
										$reading_level * self::ONE_PER_SIX,
										self::ONE_PER_SIX,
										0
									);
								}
							}

							if ( isset( $separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx + 1] ) ) {
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx + 1][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME']] += $reading_level * 1 / 6;
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx + 1][self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM']]++;
							} else {
								$separate_merge_att_scr_ary_v2[$key][$separate_merge_att_scr_idx + 1] = array(
									(int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ] + 1,
									$reading_level * self::ONE_PER_SIX,
									self::ONE_PER_SIX,
									0
								);
							}
							// 離脱位置を更新。既にソートされた配列なので比較の必要なし
							if ( $separate_exit_idx[$key] < $separate_merge_att_scr_idx ) {
								$separate_exit_idx[$key] = $separate_merge_att_scr_idx;
							}

							// 合計滞在時間に加算
							$separate_total_stay_time[$key] += $stay_time;
							//QA ZERO END


						}

						// body部が存在しなかったユーザーの対策。離脱位置を強制的に0の部分にする。
						// これにより、ヒートマップビューのデータ数とスクロールマップトップのデータ数との見た目上の整合性を合わせる
						if ( $exit_idx === -1 ) {
							$exit_idx = 0;
						}
						if ( isset( $merge_att_scr_ary_v2[ $exit_idx ] ) ) {
							// 離脱ユーザーの位置を増やす
							$merge_att_scr_ary_v2[ $exit_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'EXIT_NUM' ] ]++;
						} else {
							// 離脱ユーザーの位置を新たに作成
							$merge_att_scr_ary_v2[ $exit_idx ] = array(
								0,
								0,
								1,
								0
							);
						}

						// QA ZEROでもbody部が存在しなかったユーザーの対策。離脱位置を強制的に0の部分にする。
						// これにより、ヒートマップビューのデータ数とスクロールマップトップのデータ数との見た目上の整合性を合わせる
						if ( $separate_exit_idx[$key] === -1 ) {
							$separate_exit_idx[ $key ] = 0;
						}
						if ( isset( $separate_merge_att_scr_ary_v2[ $key ][ $separate_exit_idx[$key] ] ) ) {
							// 離脱ユーザーの位置を増やす
							$separate_merge_att_scr_ary_v2[ $key ][ $separate_exit_idx[$key] ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'EXIT_NUM' ] ]++;
						} else {
							// 離脱ユーザーの位置を新たに作成
							$separate_merge_att_scr_ary_v2[ $key ][ $separate_exit_idx[$key] ] = array(
								0,
								0,
								1,
								0
							);
						}
					}
				}

				if ( $raw_c_tsv && $base_selector_ary[$pv_log['version_id']] ) {
					$raw_c_ary     = $this->convert_tsv_to_array( $raw_c_tsv );
					$base_selector = explode( "\t", $base_selector_ary[$pv_log['version_id']] );

					foreach ( $raw_c_ary as $index => $c ) {
						if ( $index === self::DATA_COLUMN_HEADER ) {
							// header部。現在は何もしない
						} else {
							// body部
							if ( ! isset( $c[ self::DATA_CLICK_1['SELECTOR_NAME'] ] ) ) {
								continue;
							}
							$merge_click_ary[ $click_idx ] = array();
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_NAME'] ] = $base_selector[ $c[ self::DATA_CLICK_1['SELECTOR_NAME'] ] ];
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_X'] ]    = $c[ self::DATA_CLICK_1['SELECTOR_X'] ];
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_Y'] ]    = $c[ self::DATA_CLICK_1['SELECTOR_Y'] ];
							$click_idx++;
							//QA ZERO
							if (  ! isset( $separate_merge_click_ary[$key] ) ) {
								$separate_merge_click_ary[ $key ] = array();
							}
							$separate_merge_click_ary[$key][] = array(
								self::DATA_MERGE_CLICK_1['SELECTOR_NAME'] => $base_selector[ $c[ self::DATA_CLICK_1['SELECTOR_NAME'] ] ],
								self::DATA_MERGE_CLICK_1['SELECTOR_X'] => $c[ self::DATA_CLICK_1['SELECTOR_X'] ],
								self::DATA_MERGE_CLICK_1['SELECTOR_Y'] => $c[ self::DATA_CLICK_1['SELECTOR_Y'] ]
							);
							//QA ZERO END
						}
					}
				}

			}

			if ( $data_num > 0 ) {
				$time_on_page = round( $total_stay_time / $data_num, 2 );
			}

			// 合算したデータの平均値を求める
			$merge_max = count( $merge_att_scr_ary_v2 );
			if ( $merge_max > 0 ) {
				for ( $merge_idx = 0; $merge_idx < $merge_max; $merge_idx++ ) {
					if ( $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ] > 1 ) {
						$merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ] /= $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ];
						$merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ]  = round( $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ], 3 );
					}
				}
			}
			//QA ZERO
			//separateはサーバー側では平均せず、クライアント側で自由に合算し、平均を求める。
//			foreach ( $separate_merge_att_scr_ary_v2 as $key => $value ) {
//				if ( $separate_data_num[ $key ] > 0 ) {
//					$separate_time_on_page[ $key ] = round( $separate_total_stay_time[ $key ] / $separate_data_num[ $key ], 2 );
//				}
//				// 合算したデータの平均値を求める
//
//				$separate_merge_max = count( $separate_merge_att_scr_ary_v2[ $key ] );
//				if ( $separate_merge_max > 0 ) {
//					for ( $separate_merge_idx = 0; $separate_merge_idx < $separate_merge_max; $separate_merge_idx++ ) {
//						if ( $separate_merge_att_scr_ary_v2[ $key ][ $separate_merge_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_NUM' ] ] > 1 ) {
//							$separate_merge_att_scr_ary_v2[ $key ][ $separate_merge_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ] /= $separate_merge_att_scr_ary_v2[ $key ][ $separate_merge_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_NUM' ] ];
//							$separate_merge_att_scr_ary_v2[ $key ][ $separate_merge_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ] = round( $separate_merge_att_scr_ary_v2[ $key ][ $separate_merge_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2[ 'STAY_TIME' ] ], 3 );
//						}
//					}
//				}
//			}
			//QA ZERO END
		}

		// dbにbase_htmlが存在しない場合は作る
		if ( ! $base_html ) {
			$response = $this->wrap_remote_get( $base_url, $device_name );
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'wp_remote_get failed.' );
			}
			if( ! ( $response['response']['code'] === 200 || $response['response']['code'] === 404 ) ) {
				throw new Exception( 'wp_remote_get status error. status: ' . esc_html( $response['response']['code'] ) );
			}
			$base_html = $response['body'];
			if ( $this->is_zip( $base_html ) ) {
				$temphtml = gzdecode( $base_html );
				if ( $temphtml !== false ) {
					$base_html = $temphtml;
				}
			}
		}

		// baseが存在した場合、cap.phpを作成する
		if ( $base_html ) {
			// capはbaseを加工
			$cap_path    = $heatmap_view_work_dir . $version_id . '-cap.php';
			//$cap_content = $this->opt_html( $cap_path, $base_html, $type, $id, $ver, $device_name );
			$cap_content = $this->opt_base_html( $cap_path, $base_html, $base_url, $device_name );

			if ( $cap_content ) {
				// cap
				if ( ! $wp_filesystem->put_contents( $cap_path, $cap_content ) ) {
					$qahm_log->error( '$wp_filesystem->put_contents()に失敗しました。パス：' . $cap_path );
				}

				// マージファイル
				if ( $merge_att_scr_ary_v2 ) {
					// ソート後、tsvに変換して保存
					$sort_ary = array();
					foreach ( $merge_att_scr_ary_v2 as $val_idx => $val_ary ) {
						$sort_ary[$val_idx] = $val_ary[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT'] ];
					}
					array_multisort( $sort_ary, SORT_ASC, $merge_att_scr_ary_v2 );
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 2,
						)
					);
					$merge_att_scr_ary_v2 = array_merge( $head, $merge_att_scr_ary_v2 );
					$merge_att_scr_tsv = $this->convert_array_to_tsv( $merge_att_scr_ary_v2 );

					$path = $heatmap_view_work_dir . $version_id . '-merge-as-v2.php';
					$this->wrap_put_contents( $path, $merge_att_scr_tsv );
				}

				if ( $merge_click_ary ) {
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 1,
						)
					);
					$merge_click_ary = array_merge( $head, $merge_click_ary );
					$merge_click_tsv = $this->convert_array_to_tsv( $merge_click_ary );

					$path = $heatmap_view_work_dir . $version_id . '-merge-c.php';
					$this->wrap_put_contents( $path, $merge_click_tsv );
				}
				//QA ZERO
				// Separate マージファイル
				if ( $separate_merge_att_scr_ary_v2 ) {
					//各キーごとにソート
					foreach ($separate_merge_att_scr_ary_v2 as $key => &$array) {
						ksort($array);
					}
					unset($array); // remove reference

					//ヘッダー付与
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 2,
						)
					);
					$separate_merge_att_scr_ary_v2 = array_merge( $head, $separate_merge_att_scr_ary_v2 );
					$separate_merge_att_scr_slz = $this->wrap_serialize( $separate_merge_att_scr_ary_v2 );

					$path = $heatmap_view_work_dir . $version_id . '-separate-merge-as-v2-slz.php';
					$this->wrap_put_contents( $path, $separate_merge_att_scr_slz );
				}

				if ( $separate_merge_click_ary ) {
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 1,
						)
					);
					$separate_merge_click_ary = array_merge( $head, $separate_merge_click_ary );
					$separate_merge_click_tsv = $this->wrap_serialize( $separate_merge_click_ary );

					$path = $heatmap_view_work_dir . $version_id . '-separate-merge-c-slz.php';
					$this->wrap_put_contents( $path, $separate_merge_click_tsv );
				}
				//QA ZERO END


				// 情報を格納するinfoファイル
				// iniファイルと同じような書き方。シンプルにしたいが為にセクションは無し
				// $separate_time_on_pageをJSONに変換して追加
				$json_separate_time_on_page = wp_json_encode($separate_time_on_page);
				$info_str  = '';
				$info_str .= 'base_url=' . $base_url . PHP_EOL;
				$info_str .= 'data_num=' . $data_num . PHP_EOL;
				$info_str .= 'wp_qa_type=' . $wp_qa_type . PHP_EOL;
				$info_str .= 'wp_qa_id=' . $wp_qa_id . PHP_EOL;
				$info_str .= 'version_no=' . $version_no . PHP_EOL;
				$info_str .= 'device_name=' . $device_name . PHP_EOL;
				$info_str .= 'time_on_page=' . $time_on_page . PHP_EOL;
				$info_str .= 'separate_time_on_page=' . $json_separate_time_on_page;

				$path = $heatmap_view_work_dir . $version_id . '-info.php';
				$this->wrap_put_contents( $path, $info_str );
			}
		}

		return $version_id;
	}


	/**
	 * ファイルサーチ高速化のための関数
	 */
	private function file_mapping_cache( $dirlist ) {
		$file_mapping_cache = array();
		foreach ( $dirlist as $file_info ) {
			if ( preg_match('/^(\d{4}-\d{2}-\d{2})_/', $file_info['name'], $matches ) ) {
				// ファイル名から日付を抽出
				$file_date = $matches[1];

				// 日付が $date と一致する場合、ファイルマッピングキャッシュに追加
				if ( $file_date ) {
					$file_mapping_cache[$file_date] = $file_info['name'];
				}
			}
		}
		return $file_mapping_cache;
	}


	/**
	 * ヒートマップ表示用のファイルを作成
	 */
	public function ajax_create_heatmap_file() {
		global $qahm_log;

		try {
			$version_id = (int) $this->wrap_filter_input( INPUT_POST, 'version_id' );
			
			$this->write_wp_load_path();
			$this->create_heatmap_file( $version_id );

			$heatmap_view_url  = esc_url( plugin_dir_url( __FILE__ ) . 'heatmap-view.php' ) . '?';
			$heatmap_view_url .= 'version_id=' . $version_id;

			echo wp_json_encode( esc_url_raw( $heatmap_view_url ) );

		} catch ( Exception $e ) {
			$log = $qahm_log->error( $e->getMessage() );
			echo wp_json_encode( esc_html( $log ) );

		} finally {
			die();
		}
	}

	/**
	 * ヒートマップビュー用の一時ファイルを作成
	 */
	public function create_heatmap_file( $version_id ) {
		global $qahm_db;
		global $wp_filesystem;
		global $qahm_log;
		
		$qa_pv_log   = json_decode( $this->wrap_filter_input( INPUT_POST, 'view_pv_ary' ), true );
		$start_date  = $this->wrap_filter_input( INPUT_POST, 'start_date' );
		$end_date    = $this->wrap_filter_input( INPUT_POST, 'end_date' );
		$base_html   = null;
		$heatmap_view_work_dir = $this->get_data_dir_path( 'heatmap-view-work' );
		$this->wrap_mkdir( $heatmap_view_work_dir );

		// base.htmlをdbから取得
		$base_html            = null;
		$base_selector        = null;
		$qa_pages             = null;
		$qa_page_version_hist = null;
		$page_id              = null;
		$base_url             = null;
		$version_no           = null;
		$device_name          = null;
		$device_id            = null;
		$wp_qa_type           = null;
		$wp_qa_id             = null;

		$merge_att_scr_ary_v1 = array();
		$merge_att_scr_ary_v2 = array();
		$merge_click_ary   = array();
		$data_num          = 0;
		$time_on_page      = 0;

		// 以下ネストが深くならないよう処理を区切っている
		$table_name            = $qahm_db->prefix . 'view_page_version_hist';
		$query                 = "SELECT * FROM " . $table_name . " WHERE version_id = %d";
		$query                 = $qahm_db->prepare( $query, $version_id );
		$qa_page_version_hist  = $qahm_db->get_results( $query, ARRAY_A );
		
		if ( $qa_page_version_hist ) {
			$hist          = $qa_page_version_hist[0];
			$version_id    = (int) $hist['version_id'];
			$page_id       = (int) $hist['page_id'];
			$device_id     = (int) $hist['device_id'];
			$device_name   = $this->device_id_to_device_name( $device_id );
			$version_no    = (int) $hist['version_no'];
			$base_html     = $hist['base_html'];
			$base_selector = $hist['base_selector'];

			$table_name = 'qa_pages';
			$query      = 'SELECT wp_qa_type,wp_qa_id,url FROM ' . $qahm_db->prefix . $table_name . ' WHERE page_id = %d';
			$qa_pages   = $qahm_db->get_results( $qahm_db->prepare( $query, $page_id ), ARRAY_A );

			// 引数でview_pv配列を渡されないとき（ヒートマップ管理画面からアクセスするとき）は、この時点でview_pvを検索
			if ( ! $qa_pv_log ) {
				$table_name = 'view_pv';
				$query     = 'SELECT pv_id,is_raw_p,is_raw_c,is_raw_e FROM ' . $qahm_db->prefix . $table_name . ' WHERE version_id = %d';
				$qa_pv_log  = $qahm_db->get_results( $qahm_db->prepare( $query, $version_id ), ARRAY_A );

				// view_pv配列が存在しないときはDBからデータ取得
				if ( ! $qa_pv_log ) {
					if ( $start_date && $end_date ) {
						$query     = "select pv_id,raw_p,raw_c,raw_e from " . $qahm_db->prefix . "qa_pv_log where version_id = %d AND access_time BETWEEN %s AND %s";
						$qa_pv_log = $qahm_db->get_results( $qahm_db->prepare( $query, $version_id, $start_date, $end_date ), ARRAY_A );
					} else {
						$query     = "select pv_id,raw_p,raw_c,raw_e from " . $qahm_db->prefix . "qa_pv_log where version_id = %d";
						$qa_pv_log = $qahm_db->get_results( $qahm_db->prepare( $query, $version_id ), ARRAY_A );
					}
				}
			}
		}

		if ( $qa_pages ) {
			$page       = $qa_pages[0];
			$wp_qa_type = $page['wp_qa_type'];
			$wp_qa_id   = $page['wp_qa_id'];
			$base_url   = $page['url'];
		}

		if ( $qa_pv_log ) {
			// 100分率 + 精読率100%の分配列を用意
			for ( $iii = 0; $iii < 100 + 1; $iii++ ) {
				$merge_att_scr_ary_v1[ $iii ] = array( $iii, 0, 0, 0 );
			}

			// base_selectorの配列
			$click_idx         = 0;
			$base_selector_ary = explode( "\t", $base_selector );

			$total_stay_time = 0;
			
			$view_pv_dir   = $this->get_data_dir_path( 'view' ) . $this->get_tracking_id() . '/view_pv/';
			$raw_p_dirlist = $this->wrap_dirlist( $view_pv_dir . 'raw_p/' );
			$raw_c_dirlist = $this->wrap_dirlist( $view_pv_dir . 'raw_c/' );

			$raw_p_dir_idx = 0;
			$raw_p_data_ary = null;
			$raw_p_data_pv_id_min = 0;
			$raw_p_data_pv_id_max = 0;
			$raw_c_dir_idx = 0;
			$raw_c_data_ary = null;
			$raw_c_data_pv_id_min = 0;
			$raw_c_data_pv_id_max = 0;

			foreach ( $qa_pv_log as $pv_log ) {
				$raw_p_tsv = null;
				$raw_c_tsv = null;

				if ( array_key_exists( 'is_raw_p', $pv_log ) ) {
					// view_pv
					if ( $pv_log['is_raw_p'] || $pv_log['is_raw_c'] || $pv_log['is_raw_e'] ) {
						$pv_id = $pv_log['pv_id'];

						if ( $pv_log['is_raw_p'] ) {
							$raw_p_dir_first_search = false;

							if ( $raw_p_data_ary && $raw_p_data_pv_id_min <= $pv_id && $raw_p_data_pv_id_max >= $pv_id ) {
								foreach ( $raw_p_data_ary as $raw_p_data ) {
									if( $pv_id !== $raw_p_data['pv_id'] ) {
										continue;
									}
									$raw_p_tsv = $raw_p_data['raw_p'];
									break;
								}
							}

							if ( ! $raw_p_tsv ) {
								for ( $i = $raw_p_dir_idx, $raw_p_file_max = count( $raw_p_dirlist ); $i < $raw_p_file_max; $i++ ) {
									preg_match( '/_(\d+)-(\d+)_/', $raw_p_dirlist[$i]['name'], $matches );
									if ( ! array_key_exists( 1, $matches ) || ! array_key_exists( 2, $matches ) ) {
										continue;
									}

									if ( $matches[1] > $pv_id || $matches[2] < $pv_id ) {
										continue;
									}
		
									$raw_p_data_ary = $this->wrap_unserialize( $this->wrap_get_contents( $view_pv_dir . 'raw_p/' . $raw_p_dirlist[$i]['name'] ) );
									if ( ! $raw_p_dir_first_search ) {
										$raw_p_dir_first_search = true;
										$raw_p_dir_idx = $i;
										$raw_p_data_pv_id_min = $matches[1];
										$raw_p_data_pv_id_max = $matches[2];
									}

									foreach ( $raw_p_data_ary as $raw_p_data ) {
										if( $pv_id !== $raw_p_data['pv_id'] ) {
											continue;
										}
										$raw_p_tsv = $raw_p_data['raw_p'];
										break;
									}
		
									if( $raw_p_tsv ) {
										break;
									}
								}
							}
						}
	
						if ( $pv_log['is_raw_c'] ) { 
							$raw_c_dir_first_search = false;

							if ( $raw_c_data_ary && $raw_c_data_pv_id_min <= $pv_id && $raw_c_data_pv_id_max >= $pv_id ) {
								foreach ( $raw_c_data_ary as $raw_c_data ) {
									if( $pv_id !== $raw_c_data['pv_id'] ) {
										continue;
									}
									$raw_c_tsv = $raw_c_data['raw_c'];
									break;
								}
							}

							if ( ! $raw_c_tsv ) {
								for ( $i = $raw_c_dir_idx, $raw_c_file_max = count( $raw_c_dirlist ); $i < $raw_c_file_max; $i++ ) {
									preg_match( '/_(\d+)-(\d+)_/', $raw_c_dirlist[$i]['name'], $matches );
									if ( ! array_key_exists( 1, $matches ) || ! array_key_exists( 2, $matches ) ) {
										continue;
									}

									if ( $matches[1] > $pv_id || $matches[2] < $pv_id ) {
										continue;
									}

									$raw_c_data_ary = $this->wrap_unserialize( $this->wrap_get_contents( $view_pv_dir . 'raw_c/' . $raw_c_dirlist[$i]['name'] ) );
									if ( ! $raw_c_dir_first_search ) {
										$raw_c_dir_first_search = true;
										$raw_c_dir_idx = $i;
										$raw_c_data_pv_id_min = $matches[1];
										$raw_c_data_pv_id_max = $matches[2];
									}

									foreach ( $raw_c_data_ary as $raw_c_data ) {
										if( $pv_id !== $raw_c_data['pv_id'] ) {
											continue;
										}
										$raw_c_tsv = $raw_c_data['raw_c'];
										break;
									}

									if( $raw_c_tsv ) {
										break;
									}
								}
							}
						}
	
						//$query     = "select raw_p,raw_c from " . $wpdb->prefix . "qa_pv_log where pv_id=%d";
						//$qa_pv_log = $wpdb->get_results( $wpdb->prepare( $query, $view_pv['pv_id'] ) );
						//$pv_log    = $qa_pv_log[0];
						
						if ( $raw_p_tsv || $raw_c_tsv ) {
							$data_num++;
						}
					}
				} else {
					// qa_pv_log
					if ( $pv_log['raw_p'] || $pv_log['raw_c'] || $pv_log['raw_e'] ) {
						$raw_p_tsv = $pv_log['raw_p'];
						$raw_c_tsv = $pv_log['raw_c'];

						$data_num++;
					}
				}

				if ( $raw_p_tsv ) {

					$raw_p_ary   = $this->convert_tsv_to_array( $raw_p_tsv );
					$exit_idx    = -1;

					// 滞在時間
					$ver = (int) $raw_p_ary[self::DATA_COLUMN_HEADER][self::DATA_HEADER_VERSION];
					if ( $ver === 2 ) {
						for ( $raw_p_idx = self::DATA_COLUMN_BODY, $raw_p_max = count($raw_p_ary); $raw_p_idx < $raw_p_max; $raw_p_idx++ ) {
							$p = $raw_p_ary[$raw_p_idx];
							if ( $p[ self::DATA_POS_2['STAY_HEIGHT'] ] === 'a' ) {
								// あとでこの処理を詰める
								break;
							}

							$stay_time = min( (int) $p[ self::DATA_POS_2['STAY_TIME'] ], self::ATTENTION_LIMIT_TIME );
							$merge_att_scr_idx = count( $merge_att_scr_ary_v2 );
							$is_find = false;
							if ( $merge_att_scr_idx > 0 ) {
								// 既に高さのデータがあれば滞在時間を加算
								foreach ( $merge_att_scr_ary_v2 as $val_idx => $val_ary ) {
									if ( $val_ary[self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT']] === (int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ] ) {
										$merge_att_scr_idx = $val_idx;
										$merge_att_scr_ary_v2[ $merge_att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ] += $stay_time;
										$merge_att_scr_ary_v2[ $merge_att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ]++;
										$is_find = true;
										break;
									}
								}
							}

							if ( ! $is_find ) {
								$merge_att_scr_ary_v2[ $merge_att_scr_idx ] = array(
									(int) $p[ self::DATA_POS_2['STAY_HEIGHT'] ],
									$stay_time,
									1,
									0
								);
							}

							// 離脱位置を更新。既にソートされた配列なので比較の必要なし
							$exit_idx = $merge_att_scr_idx;

							// 合計滞在時間に加算
							$total_stay_time += $stay_time;
						}

						// body部が存在しなかったユーザーの対策。離脱位置を強制的に0の部分にする。
						// これにより、ヒートマップビューのデータ数とスクロールマップトップのデータ数との見た目上の整合性を合わせる
						if ( $exit_idx === -1 ) {
							if ( count( $merge_att_scr_ary_v2 ) > 0 ) {
								foreach ( $merge_att_scr_ary_v2 as $val_idx => $val_ary ) {
									if ( $val_ary[self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT']] === 0 ) {
										$exit_idx = $val_idx;
										break;
									}
								}
							}
							if ( $exit_idx === -1 ) {
								$exit_idx = count( $merge_att_scr_ary_v2 );
								$merge_att_scr_ary_v2[ $exit_idx ] = array(
									0,
									0,
									1,
									0
								);
							}
						}
						
						// 離脱ユーザーの位置
						$merge_att_scr_ary_v2[ $exit_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_2['EXIT_NUM'] ]++;

					} elseif ( $ver === 1 ) {
						for ( $raw_p_idx = self::DATA_COLUMN_BODY, $raw_p_max = count($raw_p_ary); $raw_p_idx < $raw_p_max; $raw_p_idx++ ) {
							$p = $raw_p_ary[$raw_p_idx];
							if ( $p[ self::DATA_POS_1['PERCENT_HEIGHT'] ] === 'a' ) {
								$exit_idx = 100;
							} else {
								// 滞在位置
								$att_scr_idx = (int) $p[ self::DATA_POS_1['PERCENT_HEIGHT'] ];
								// 滞在位置が0地点なら1に補正する
								if ( $att_scr_idx === 0 ) {
									$att_scr_idx = 1;
								}

								// 滞在地点ごとの合計滞在時間を求める
								$stay_time = min( (int) $p[ self::DATA_POS_1['TIME_ON_HEIGHT'] ], self::ATTENTION_LIMIT_TIME );
								$merge_att_scr_ary_v1[ $att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ] += $stay_time;
								$merge_att_scr_ary_v1[ $att_scr_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ]++;

								// 離脱位置を更新。既にソートされた配列なので比較の必要なし
								$exit_idx = $att_scr_idx;

								// 合計滞在時間に加算
								$total_stay_time += $stay_time;
							}
						}

						// body部が存在しなかったユーザーの対策。離脱位置を強制的に1の部分にする。
						// これにより、ヒートマップビューのデータ数とスクロールマップトップのデータ数との見た目上の整合性を合わせる
						if ( $exit_idx === -1 ) {
							$merge_att_scr_ary_v1[ 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ]++;
							$exit_idx = 1;
						}
	
						// 離脱ユーザーの位置
						$merge_att_scr_ary_v1[ $exit_idx ][ self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'] ]++;
					}
				}

				if ( $raw_c_tsv && $base_selector ) {
					$raw_c_ary = $this->convert_tsv_to_array( $raw_c_tsv );

					foreach ( $raw_c_ary as $index => $c ) {
						if ( $index === self::DATA_COLUMN_HEADER ) {
							// header部。現在は何もしない
						} else {
							// body部
							$merge_click_ary[ $click_idx ] = array();
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_NAME'] ] = $base_selector_ary[ $c[ self::DATA_CLICK_1['SELECTOR_NAME'] ] ];
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_X'] ]    = $c[ self::DATA_CLICK_1['SELECTOR_X'] ];
							$merge_click_ary[ $click_idx ][ self::DATA_MERGE_CLICK_1['SELECTOR_Y'] ]    = $c[ self::DATA_CLICK_1['SELECTOR_Y'] ];
							$click_idx++;
						}
					}
				}
			}

			if ( $data_num > 0 ) {
				$time_on_page = round( $total_stay_time / $data_num, 2 );

				$exit_num = 0;
				foreach( $merge_att_scr_ary_v2 as $val_ary ) {
					$exit_num += $val_ary[ self::DATA_MERGE_ATTENTION_SCROLL_2['EXIT_NUM'] ];
				}
				foreach( $merge_att_scr_ary_v1 as $val_ary ) {
					$exit_num += $val_ary[ self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'] ];
				}

				// raw_pが存在しない、かつraw_eが存在するデータがある（昔のデータやファイル作成失敗？）
				// その対策に、データ数を補正する。離脱位置は1の部分
				if ( $data_num !== $exit_num ) {
					$dif_num = $data_num - $exit_num;

					// 離脱ユーザーの位置
					$merge_att_scr_ary_v1[ 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'] ] += $dif_num;
					$merge_att_scr_ary_v1[ 1 ][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ] += $dif_num;
				}
			}

			// 合算したデータの平均値を求める
			$merge_max = count( $merge_att_scr_ary_v2 );
			if ( $merge_max > 0 ) {
				for ( $merge_idx = 0; $merge_idx < $merge_max; $merge_idx++ ) {
					if ( $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ] > 1 ) {
						$merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ] /= $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ];
						$merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ]  = round( $merge_att_scr_ary_v2[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ], 3 );
					}
				}
			}

			$merge_max = count( $merge_att_scr_ary_v1 );
			if ( $merge_max > 0 ) {
				for ( $merge_idx = 0; $merge_idx < $merge_max; $merge_idx++ ) {
					if ( $merge_att_scr_ary_v1[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ] > 1 ) {
						$merge_att_scr_ary_v1[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ] /= $merge_att_scr_ary_v1[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ];
						$merge_att_scr_ary_v1[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ]  = round( $merge_att_scr_ary_v1[$merge_idx][ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ], 3 );
					}
				}
			}
		}

		// 配列のソート
		if ( $merge_att_scr_ary_v2 ) {
			// ソート後、tsvに変換して保存
			$sort_ary = array();
			foreach ( $merge_att_scr_ary_v2 as $val_idx => $val_ary ) {
				$sort_ary[$val_idx] = $val_ary[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT'] ];
			}
			array_multisort( $sort_ary, SORT_ASC, $merge_att_scr_ary_v2 );
		}

		// dbにbase_htmlが存在しない場合は作る
		if ( ! $base_html ) {
			$response = $this->wrap_remote_get( $base_url, $dev_name );
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'wp_remote_get failed.' );
			}
			if( ! ( $response['response']['code'] === 200 || $response['response']['code'] === 404 ) ) {
				throw new Exception( 'wp_remote_get status error. status: ' . esc_html( $response['response']['code'] ) );
			}
			$base_html = $response['body'];
			
			if ( $this->is_zip( $base_html ) ) {
				$temphtml = gzdecode( $base_html );
				if ( $temphtml !== false ) {
					$base_html = $temphtml;
				}
			}
		}

		// baseが存在した場合、cap.phpを作成する
		if ( $base_html ) {
			// capはbaseを加工
			$cap_path    = $heatmap_view_work_dir . $version_id . '-cap.php';
			//$cap_content = $this->opt_html( $cap_path, $base_html, $type, $id, $ver, $device_name );
			$cap_content = $this->opt_base_html( $cap_path, $base_html, $base_url, $device_name );

			if ( $cap_content ) {
				// cap
				if ( ! $wp_filesystem->put_contents( $cap_path, $cap_content ) ) {
					$qahm_log->error( '$wp_filesystem->put_contents()に失敗しました。パス：' . $cap_path );
				}

				// マージファイル
				if ( $merge_att_scr_ary_v2 ) {
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 2,
						)
					);
					$merge_att_scr_ary_v2 = array_merge( $head, $merge_att_scr_ary_v2 );
					$merge_att_scr_tsv = $this->convert_array_to_tsv( $merge_att_scr_ary_v2 );

					$path = $heatmap_view_work_dir . $version_id . '-merge-as-v2.php';
					$this->wrap_put_contents( $path, $merge_att_scr_tsv );
				}

				if ( $merge_att_scr_ary_v1 ) {
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 1,
						)
					);
					$merge_att_scr_ary_v1 = array_merge( $head, $merge_att_scr_ary_v1 );
					$merge_att_scr_tsv = $this->convert_array_to_tsv( $merge_att_scr_ary_v1 );

					$path = $heatmap_view_work_dir . $version_id . '-merge-as-v1.php';
					$this->wrap_put_contents( $path, $merge_att_scr_tsv );
				}

				if ( $merge_click_ary ) {
					$head = array(
						array(
							self::DATA_HEADER_VERSION => 1,
						)
					);
					$merge_click_ary = array_merge( $head, $merge_click_ary );
					$merge_click_tsv = $this->convert_array_to_tsv( $merge_click_ary );

					$path = $heatmap_view_work_dir . $version_id . '-merge-c.php';
					$this->wrap_put_contents( $path, $merge_click_tsv );
				}

				// 情報を格納するinfoファイル
				// iniファイルと同じような書き方。シンプルにしたいが為にセクションは無し
				$info_str  = '';
				$info_str .= 'base_url=' . $base_url . PHP_EOL;
				$info_str .= 'data_num=' . $data_num . PHP_EOL;
				$info_str .= 'wp_qa_type=' . $wp_qa_type . PHP_EOL;
				$info_str .= 'wp_qa_id=' . $wp_qa_id . PHP_EOL;
				$info_str .= 'version_no=' . $version_no . PHP_EOL;
				$info_str .= 'device_name=' . $device_name . PHP_EOL;
				$info_str .= 'time_on_page=' . $time_on_page;

				$path = $heatmap_view_work_dir . $version_id . '-info.php';
				$this->wrap_put_contents( $path, $info_str );
			}
		}
	}

	public function get_html_bar_text( $id, $html, $tooltip, $both = false, $link = '' ) {
		$clear_both = '';
		if ( $both ) {
			$clear_both = ' style="clear: both;"'; 
		}
		$link_start = '';
		$link_end   = '';
		if ( $link ) {
			$link_start = '<a href="' . $link . '" target="_blank" rel="noopener noreferrer"">';
			$link_end   = '</a>';
		}

		return <<< EOT
			<li id="{$id}"{$clear_both}>
				{$link_start}
				<span class="qahm-tooltip-bottom" data-qahm-tooltip="{$tooltip}">
					{$html}
				</span>
				{$link_end}
			</li>
EOT;
	}

	public function get_html_bar_checkbox( $id, $html, $tooltip, $is_check, $is_mobile ) {
		$check_html = '';
		if ( $is_check ) {
			$check_html = ' checked';
		}

		$mobile_html = '';
		if ( $is_mobile ) {
			$mobile_html = '-mobile';
		}

		return <<< EOT
			<li id="{$id}-checkbox{$mobile_html}">
				<label class="control control--checkbox">
					<span class="qahm-tooltip-bottom" data-qahm-tooltip="{$tooltip}">
						{$html}
						<input class="heatmap-bar-check {$id}" type="checkbox"{$check_html} disabled>
						<div class="control__indicator"></div>
					</span>
				</label>
			</li>
EOT;
	}

	// ヒートマップ表示画面上で必要な初期情報を取得
	public function ajax_init_heatmap_view() {
		$data = array();

		global $wp_filesystem;
		global $qahm_recterm;

		$type       = $this->wrap_filter_input( INPUT_POST, 'type' );
		$id         = $this->wrap_filter_input( INPUT_POST, 'id' );
		$ver        = $this->wrap_filter_input( INPUT_POST, 'ver' );
		$dev        = $this->wrap_filter_input( INPUT_POST, 'dev' );
		$version_id = $this->wrap_filter_input( INPUT_POST, 'version_id' );

		// cap.phpは一日ごとの更新のため、リアルタイムに変わってほしい変数や
		// QAHMバーを初期化する際に必須の情報を受け取る
		$data['const_debug_level'] = QAHM_DEBUG_LEVEL;
		$data['const_debug']       = QAHM_DEBUG;
		$data['locale']            = get_locale();
		$data['data_num']          = 0;            // データ数はcap.phpに移動予定
		$data['ver_max']           = 1;				// 後々修正 imai
		$data['heatmap']           = false;
		$data['attention']         = false;

		$heatmap_view_work_dir = $this->get_data_dir_path( 'heatmap-view-work' );

		$data['merge_c']  = null;
		$data['merge_as'] = null;
		$data['data_num'] = 0;

		// infoファイルの読み込み
		$info_ary = $wp_filesystem->get_contents_array( $heatmap_view_work_dir . $version_id . '-info.php' );
		foreach ( $info_ary as $info ) {
			$info_param = explode( '=', $info );
			if ( count( $info_param ) === 2 ) {
				switch ( $info_param[0] ) {
					case 'data_num':
						$data['data_num'] = (int) $info_param[1];
						break;
				}
			}
		}


		$lists = $this->wrap_dirlist( $heatmap_view_work_dir );
		foreach ( $lists as $list ) {			
			// 内部でデータヘッダーを削除しているが、今後js側で必要になるようならデータヘッダーも残す
			if ( $list['name'] === $version_id . '-merge-c.php' ) {
				$merge_c_str = $this->wrap_get_contents( $heatmap_view_work_dir . $list['name'] );
				$merge_c_ary = $this->convert_tsv_to_array( $merge_c_str );

				// ここでバージョンの差異を吸収

				// ヘッダー情報は不要なため削除。必要な時が来れば別ファイルに出力すればリスクも低そう
				unset( $merge_c_ary[ self::DATA_COLUMN_HEADER ] );
				$merge_c_ary = array_values( $merge_c_ary );

				// 型変換
				foreach ( $merge_c_ary as &$merge_c ) {
					$merge_c[ self::DATA_MERGE_CLICK_1['SELECTOR_X'] ] = (int) $merge_c[ self::DATA_MERGE_CLICK_1['SELECTOR_X'] ];
					$merge_c[ self::DATA_MERGE_CLICK_1['SELECTOR_Y'] ] = (int) $merge_c[ self::DATA_MERGE_CLICK_1['SELECTOR_Y'] ];
				}

				$data['merge_c'] = $merge_c_ary;

			} elseif ( $list['name'] === $version_id . '-merge-as-v2.php' ) {
				$merge_as_str = $this->wrap_get_contents( $heatmap_view_work_dir . $list['name'] );
				$merge_as_ary = $this->convert_tsv_to_array( $merge_as_str );

				// ここでバージョンの差異を吸収

				// ヘッダー情報は不要なため削除。必要な時が来れば別ファイルに出力すればリスクも低そう
				unset( $merge_as_ary[ self::DATA_COLUMN_HEADER ] );
				$merge_as_ary = array_values( $merge_as_ary );

				// 型変換
				foreach ( $merge_as_ary as &$merge_as ) {
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT'] ] = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ]   = (float) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ]    = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['EXIT_NUM'] ]    = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_2['EXIT_NUM'] ];
				}
				$data['merge_as_v2'] = $merge_as_ary;

			} elseif ( $list['name'] === $version_id . '-merge-as-v1.php' || $list['name'] === $version_id . '-merge-as-v1.php' ) {
				$merge_as_str = $this->wrap_get_contents( $heatmap_view_work_dir . $list['name'] );
				$merge_as_ary = $this->convert_tsv_to_array( $merge_as_str );

				// ここでバージョンの差異を吸収

				// ヘッダー情報は不要なため削除。必要な時が来れば別ファイルに出力すればリスクも低そう
				unset( $merge_as_ary[ self::DATA_COLUMN_HEADER ] );
				$merge_as_ary = array_values( $merge_as_ary );

				// 型変換
				foreach ( $merge_as_ary as &$merge_as ) {
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['PERCENT'] ]   = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['PERCENT'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ] = (float) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ]  = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'] ];
					$merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'] ]  = (int) $merge_as[ self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'] ];
				}
				$data['merge_as_v1'] = $merge_as_ary;

			} elseif ( $data['merge_c'] && $data['merge_as'] ) {
				break;
			}
		}

		$data['DATA_HEATMAP_SELECTOR_NAME'] = self::DATA_MERGE_CLICK_1['SELECTOR_NAME'];
		$data['DATA_HEATMAP_SELECTOR_X']    = self::DATA_MERGE_CLICK_1['SELECTOR_X'];
		$data['DATA_HEATMAP_SELECTOR_Y']    = self::DATA_MERGE_CLICK_1['SELECTOR_Y'];

		$data['DATA_ATTENTION_SCROLL_PERCENT_V1']   = self::DATA_MERGE_ATTENTION_SCROLL_1['PERCENT'];
		$data['DATA_ATTENTION_SCROLL_STAY_TIME_V1'] = self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_TIME'];
		$data['DATA_ATTENTION_SCROLL_STAY_NUM_V1']  = self::DATA_MERGE_ATTENTION_SCROLL_1['STAY_NUM'];
		$data['DATA_ATTENTION_SCROLL_EXIT_NUM_V1']  = self::DATA_MERGE_ATTENTION_SCROLL_1['EXIT_NUM'];
		
		$data['DATA_ATTENTION_SCROLL_STAY_HEIGHT_V2'] = self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_HEIGHT'];
		$data['DATA_ATTENTION_SCROLL_STAY_TIME_V2']   = self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_TIME'];
		$data['DATA_ATTENTION_SCROLL_STAY_NUM_V2']    = self::DATA_MERGE_ATTENTION_SCROLL_2['STAY_NUM'];
		$data['DATA_ATTENTION_SCROLL_EXIT_NUM_V2']    = self::DATA_MERGE_ATTENTION_SCROLL_2['EXIT_NUM'];

		echo wp_json_encode( $data );
		die();
	}


	//QA ZERO
	public function ajax_get_separate_data()	{
		// Check nonce, authentication, or any other necessary verification here.

		// Get the version_id from the request.
		$version_id = $this->wrap_filter_input( INPUT_POST, 'version_id' );
		$data = $this->get_separate_data( $version_id );
		// Return the data as JSON.
		wp_send_json( $data );

	}

	public function get_separate_data( $version_id ) {
		// Get the heatmap view work directory.
		$heatmap_view_work_dir = $this->get_data_dir_path( 'heatmap-view-work' );

		// Initialize the data array.
		$data = array(
			'merge_c' => null,
			'merge_as' => null,
		);

		// Define the file paths.
		$merge_c_file = $heatmap_view_work_dir . $version_id . '-separate-merge-c-slz.php';
		$merge_as_file = $heatmap_view_work_dir . $version_id . '-separate-merge-as-v2-slz.php';

		// Check if the files exist and read the data from the files.
		if ( file_exists( $merge_c_file ) ) {
			$merge_c_data = $this->wrap_get_contents( $merge_c_file );
			// Unserialize the data if it's not empty.
			if ( ! empty( $merge_c_data ) ) {
				$data['merge_c'] = $this->wrap_unserialize( $merge_c_data );
			}
		}

		if ( file_exists( $merge_as_file ) ) {
			$merge_as_data = $this->wrap_get_contents( $merge_as_file );
			// Unserialize the data if it's not empty.
			if ( ! empty( $merge_as_data ) ) {
				$data['merge_as'] = $this->wrap_unserialize( $merge_as_data );
			}
		}

		return $data;
	}
	//QA ZERO END
}
