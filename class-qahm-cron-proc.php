<?php
/**
 *
 *
 * @package qa_heatmap
 */

new QAHM_Cron_Proc();

// wp_optionsにcron登録予約配列
// type, id, リビジョンアップフラグ

class QAHM_Cron_Proc extends QAHM_File_Data {

	const TIME_OUT                 = 7200;
	const PV_LOOP_MAX              = 800;
	const RAW_LOOP_MAX             = 400;
	//const NIGHT_START              = 3;
	const DEFAULT_DELETE_MONTH     = 2 + 1;
	const DEFAULT_DELETE_DATA_DAY  = 90 + 1;
	const DATA_SAVE_MONTH          = 1 + 1;
    const DATA_SAVE_ONE_YEAR       = 12;
    const VIEWPV_DAY_LOOP_MAX      = 12;
    const VIEW_READERS_MAX_IDS     = 50000;
    const URL_PARAMETER_MAX        = 128;
    const MAX10000                 = 10000;
    const ID_INDEX_MAX10MAN        = 100000;
	const DEFAULT_LIMIT_PV_MONTH   = 10000;

	// mk dummy replace
	const PHP404_ELM       = 0;
	const HEADER_ELM       = 1;
	const BODY_ELM         = 2;
	const TEMP_BODY_ELM_NO = 1;
	// mk dummy replace

	const LOOPLAST_MSG   = 'last loop';
	const MAX_WHILECOUNT = 10000;
	const WP_SEARCH_PERM = '?s=';

	public function __construct() {
		$this->init_wp_filesystem();

		// スケジュールイベント用に関数を登録
		add_action( QAHM_OPTION_PREFIX . 'cron_data_manage', array( $this, 'cron_data_manage' ) );
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	/**
	 * アクティベート時に実行するクエリ→cron時に実行するクエリに変更
	 * この関数内ではデータベースの各テーブル毎のバージョン差異も吸収する予定
	 * →今後アップデートフック関数でやるかも
	 */
	private function exec_database_query() {
		global $qahm_db;
		$query = '';
		$qahm_sql_table = new QAHM_Sql_Table();
		$check_exists = -123454321;

		$ver = $this->wrap_get_option( 'qa_readers_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_readers_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_pages_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_pages_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_utm_media_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_utm_media_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_utm_sources_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_utm_sources_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_utm_campaigns_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_utm_campaigns_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_pv_log_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_pv_log_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_search_log_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_search_log_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_page_version_hist_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_page_version_hist_create_table();
		}

		$ver = $this->wrap_get_option( 'qa_gsc_query_log_version', $check_exists );
		if ( $ver === $check_exists ) {
			$query .= $qahm_sql_table->get_qa_gsc_query_log_create_table();
		}

		if ( $query ) {
			// queryのコメント、先頭末尾のスペースやTAB等を削除
			$query_ary = explode( PHP_EOL, $query );
			for ( $query_idx = 0, $query_max = count( $query_ary ); $query_idx < $query_max; $query_idx++ ) {
				$query_ary[$query_idx] = trim( $query_ary[$query_idx], "\t" );
				if ( substr( $query_ary[$query_idx], 0, 2 ) === '--' ) {
					unset( $query_ary[$query_idx] );
				}
			}
			$query = implode( '', $query_ary );

			// クエリ実行
			$query_ary = explode( ';', $query );
			for ( $query_idx = 0, $query_max = count( $query_ary ); $query_idx < $query_max; $query_idx++ ) {
				if ( $query_ary[$query_idx] ) {
					$qahm_db->query( $query_ary[$query_idx] );
				}
			}
			$this->wrap_put_contents( 'qa_gsc_query_log_version', QAHM_DB_OPTIONS['qa_gsc_query_log_version'] );
		}
	}

	/**
	 * オプションが存在しなければアップデート
	 */
	private function check_exist_update( $option, $value ) {
		if ( $this->wrap_get_option( $option, -123454321 ) === -123454321 ) {
			$this->wrap_update_option( $option, $value );
		}
	}

	// 下記からは今までのcron用function
	public function get_status() {
		global $wp_filesystem;

		$status = 'Cron start';
		if ( $wp_filesystem->exists( $this->get_cron_status_path() ) ) {
				$status = $wp_filesystem->get_contents( $this->get_cron_status_path() );
		} else {
			// cron statusファイル生成
			if ( ! $wp_filesystem->put_contents( $this->get_cron_status_path(), 'Cron start' ) ) {
				throw new Exception( 'cronステータスファイルの生成に失敗しました。終了します。' );
			}
		}
		//ステータスチェック
		if ( ! $this->is_status_ok( $status ) ) {
			if ( $wp_filesystem->exists( $this->get_cron_backup_path() ) ) {
				$status = $wp_filesystem->get_contents( $this->get_cron_backup_path() );
				$this->set_next_status( $status );
			}
		}
		if ( ! $this->is_status_ok( $status ) ) {
			$status = 'Cron start';
		}
		if ( QAHM_DEBUG >= QAHM_DEBUG_LEVEL['debug'] ) {
			print 'get:' . esc_html($status) . '<br>';
		}
		return $status;
	}

	public function is_status_ok ( $status ) {
		global $wp_filesystem;
		$cronfile = $wp_filesystem->get_contents( plugin_dir_path( __FILE__ ) . 'class-qahm-cron-proc.php' );
		$pregstr = "/case.*'" . $status . "'/";
		return preg_match( $pregstr, $cronfile );
	}

	public function set_next_status( $nextstatus ) {
		/*
		global $qahm_time;
		if ( QAHM_DEBUG >= QAHM_DEBUG_LEVEL['debug'] ) {
			$msg = $qahm_time->now_str() . 'next:' . $nextstatus . '<br>' . PHP_EOL;
			print $msg;
			$logfile = $this->get_data_dir_path() . 'cronlog.txt';
			file_put_contents( $logfile, $msg, FILE_APPEND );
		}
		*/
		global $wp_filesystem;
		if ( ! $wp_filesystem->put_contents( $this->get_cron_status_path(), $nextstatus ) ) {
			throw new Exception( esc_html( $nextstatus ) . 'のセットでcronステータスファイルの書込に失敗しました。終了します。' );
		}
	}

	public function backup_prev_status( $prevstatus ) {
		global $wp_filesystem;
		if ( ! $wp_filesystem->put_contents( $this->get_cron_backup_path(), $prevstatus ) ) {
			throw new Exception( esc_html( $prevstatus ) . 'のセットでcronバックアップファイルの書込に失敗しました。終了します。' );
		}
	}

	private function opt_data_array( $ary ) {
		$comb = '';
		$cnt  = count( $ary );
		$str  = '';
		for ( $i = 0; $i < $cnt; $i++ ) {
			if ( $i === $cnt - 1 ) {
				$comb .= $str . PHP_EOL;
			} else {
				// $comb .= $ary[ $i ] . ',';
				$comb .= $str . "\t";
			}
		}

		return $comb;
	}

	// mk add
	public function write_ary_to_temp( $ary, $tempfile ) {

		global  $wp_filesystem;
		$str = '<?php http_response_code(404);exit; ?>' . PHP_EOL;
		$ret = false;
		if ( ! empty( $ary ) ) {
			foreach ( $ary as $lines ) {
				if ( is_array( $lines ) ) {
					$cnt   = count( $lines );
					$lpmax = $cnt - 1;
					$line  = '';
					for ( $iii = 0; $iii < $cnt; $iii++ ) {
						$elm = $lines[ $iii ];
						// 各種rawファイルに要素が足りない場合、改行コードが入ってくることがあるので除去
						$elm = str_replace( PHP_EOL, '', $elm );
						// 最終行は改行を抜く
						if ( $iii == $lpmax ) {
							$line .= $elm . PHP_EOL;
						} else {
							$line .= $elm . "\t";
						}
					}
				} else {
					$lines = str_replace( PHP_EOL, '', $lines );
					$line  = $lines . PHP_EOL;
				}
				$str .= $line;
			}
			if ( ! empty( $str ) ) {
				$wp_filesystem->put_contents( $tempfile, $str );
				$ret = true;
			}
		}
		return $ret;
	}

	public function write_string_to_tempphp( $str, $tempfile ) {

		global  $wp_filesystem;
		$put = '<?php http_response_code(404);exit; ?>' . PHP_EOL;
		$ret = false;
		if ( ! empty( $str ) ) {
			$put .= $str;
			$wp_filesystem->put_contents( $tempfile, $put );
			$ret = true;
		}
		return $ret;
	}

	/**
	 * 各IDからIDを求めるインデックス配列をセットするための関数
	 */
	private function make_index_array( &$index_ary, $from_id, $to_id, $date_str ) {
	    if ( 0 < (int)$from_id && 0 < (int)$to_id && $date_str !== '') {
            $nowidx   = floor( (int)$from_id / self::ID_INDEX_MAX10MAN );
            if ( ! isset( $index_ary[$nowidx] ) ) {
                //初期化
                $start = self::ID_INDEX_MAX10MAN * $nowidx + 1;
                $index_ary[$nowidx] = array_fill( $start, self::ID_INDEX_MAX10MAN, false);
            }
            //version_idの保存
            if ( $index_ary[$nowidx][(int)$from_id] !== false ) {
                if ( isset( $index_ary[$nowidx][(int)$from_id][$date_str] ) ) {
                    $id_ary  = $index_ary[$nowidx][(int)$from_id][$date_str];
                    $is_find = false;
                    foreach ( $id_ary as $id ) {
                        if ( (int)$to_id === (int) $id) {
                            $is_find = true;
                            break;
                        }
                    }
                    if ( !$is_find ) {
                        $index_ary[$nowidx][(int)$from_id][$date_str][] = (int)$to_id;
                    }
                } else {
                    $index_ary[$nowidx][(int)$from_id][$date_str]   = [(int)$to_id];
                }
            } else {
                $index_ary[$nowidx][(int)$from_id][$date_str]   = [(int)$to_id];
            }
        }
	}

	private function save_index_array( $index_ary, $basedir, $filename ) {
        for ($jjj = 0; $jjj < count($index_ary); $jjj++) {
            $start_index = $jjj * self::ID_INDEX_MAX10MAN + 1;
            $end_index = $start_index + self::ID_INDEX_MAX10MAN - 1;
            $pageid_index_file = $start_index . '-' . $end_index . '_' . $filename;
            $this->wrap_put_contents($basedir . 'index/' . $pageid_index_file, $this->wrap_serialize($index_ary[$jjj]));
        }
    }

    //mktmp
	public function debugcron() {
		// ----------
		// set variables
		// ----------
		global $wpdb;
		global $wp_filesystem;
		global $qahm_license;
		global $qahm_time;
		global $qahm_log;
		global $qahm_google_api;
		global $qahm_admin_page_heatmap;
		global $qahm_article_list;

		// dir
		$data_dir          = $this->get_data_dir_path();
		$readers_dir       = $data_dir . 'readers/';
		$readerstemp_dir   = $data_dir . 'readers/temp/';
		$readersfinish_dir = $data_dir . 'readers/finish/';
		$readersdbin_dir   = $data_dir . 'readers/dbin/';
		$temp_dir          = $data_dir . 'temp/';
		$tempdelete_dir    = $data_dir . 'temp/delete/';
		$heatmapwork_dir   = $data_dir . 'heatmap-view-work/';
		$replaywork_dir    = $data_dir . 'replay-view-work/';
		$cache_dir         = $data_dir . 'cache/';
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$viewpv_dir        = $myview_dir . 'view_pv/';
		$raw_p_dir         = $viewpv_dir . 'raw_p/';
		$raw_c_dir         = $viewpv_dir . 'raw_c/';
		$raw_e_dir         = $viewpv_dir . 'raw_e/';
		$vw_reader_dir     = $myview_dir . 'readers/';
		$vw_verhst_dir     = $myview_dir . 'version_hist/';
		$vw_summary_dir     = $myview_dir . 'summary/';
		$vw_bshtml_dir     = $vw_verhst_dir . 'base_html/';

		// yday
		$dbin_session_file   = $temp_dir . 'dbin_session_file.php';
		$yday_loopcount_file = $temp_dir . 'ydayloopfile.php';
		$yday_pvmaxcnt_file  = $temp_dir . 'yday_pvmaxcnt_file';
		$ary_readers_file    = $temp_dir . 'ary_readers_file.php';
		$ary_media_file      = $temp_dir . 'ary_media_file.php';
		$ary_sources_file    = $temp_dir . 'ary_sources_file.php';
		$ary_campaigns_file  = $temp_dir . 'ary_campaigns_file.php';
		$ary_pages_file      = $temp_dir . 'ary_pages_file.php';
		$ary_pv_file         = $temp_dir . 'ary_pv_file.php';
		$ary_wp_s_file       = $temp_dir . 'ary_wp_s_file.php';

		// raw
		$raw_loopcount_file  = $temp_dir . 'raw_loopcount_file.php';
		$ary_new_pvrows_file = $temp_dir . 'ary_new_pvrows_file.php';

		// cache
		$cache_heatmap_list_file        = $cache_dir . 'heatmap_list.php';
		$cache_heatmap_list_temp_file   = $cache_dir . 'heatmap_list_temp.php';
		$cache_heatmap_list_idx_temp_file = $cache_dir . 'heatmap_list_idx_temp.php';
        $summary_days_access_file       = $vw_summary_dir . 'days_access.php';
		$summary_days_access_detail_file = $vw_summary_dir . 'days_access_detail.php';
		// loop count max
		$now_pv_loop_maxfile  = $temp_dir . 'now_pv_loop_maxfile.php';
		$NOW_PV_LOOP_MAX      = self::PV_LOOP_MAX;
		$now_raw_loop_maxfile = $temp_dir . 'now_raw_loop_maxfile.php';
		$NOW_RAW_LOOP_MAX     = self::RAW_LOOP_MAX;

		$now_pvlog_count_fetchfile = $temp_dir . 'now_pvlog_count_fetchfile.php';

		// delete files list
		$del_rawfileslist_temp = $tempdelete_dir . 'del';
		$del_rawfileslist_file = $data_dir . 'del_rawfileslist_file.php';

		// start
		$while_lpcnt        = 0;
		$is_night_comp_file = $data_dir . 'is_night_comp_file.php';
		$is_night_complete  = false;

		// ここから自由
		// ここまで消してもいい
	}

	// mk add end


	// PHP7未満用 エラーハンドラ
	public function cron_error_handler($errno, $errstr, $errfile, $errline) {
        throw new ErrorException( esc_html( $errstr ), 0, (int)$errno, esc_html( $errfile ), esc_html( (string) $errline ) );
    }

	// cron処理
	public function cron_data_manage() {
		global $qahm_log;
        if ( version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
            // PHP 7.0.0 未満
            try {
                $this->data_manage();
            } catch ( Exception $e ) {
                $qahm_log->error( 'Catch, ' . basename( $e->getFile() ) . ':' . $e->getLine() . ', ' . $e->getMessage() );
            }

        } else {
            // PHP 7.0.0 以上
            try {
                $this->data_manage();
            } catch ( Throwable $e ) {
                $qahm_log->error( 'Catch, ' . basename( $e->getFile() ) . ':' . $e->getLine() . ', ' . $e->getMessage() );
            }
        }
    }

	// cron処理 本体
	public function data_manage() {

		$php_memory_limit = ini_get( 'memory_limit' );
		$memory_size = 0;
		if ( $php_memory_limit ) {
			$search_str = [ 'g', 'G', 'm', 'M', 'k', 'K' ];
			foreach ( $search_str as $str ) {
				if ( stristr( $php_memory_limit, $str ) ) {
					$memory_size = stristr( $php_memory_limit, $str, true );
					switch ( $str ) {
						case 'g':
						case 'G':
							$memory_size = (int)$memory_size * 1000000000;
							break;
						case 'm':
						case 'M':
							$memory_size = (int)$memory_size * 1000000;
							break;
						case 'k':
						case 'K':
							$memory_size = (int)$memory_size * 1000;
							break;
					}
					break;
				}
			}
			if ( $memory_size === 0 ) {
				$memory_size = (int)$php_memory_limit;
			}
			if ( QAHM_MEMORY_LIMIT_MIN * 1000000  > $memory_size ) {
				// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- ini_set() is required here to adjust runtime configuration dynamically for specific functionality.
				ini_set( 'memory_limit', QAHM_MEMORY_LIMIT_MIN . 'M' );
			}
		}

		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- set_time_limit() is necessary here to ensure long-running operations complete without timing out.
		set_time_limit( 60 * 10 );

		// ----------
		// set variables
		// ----------
		global $wpdb;
		global $wp_filesystem;
		global $qahm_license;
		global $qahm_time;
		global $qahm_log;
		global $qahm_google_api;
		global $qahm_admin_page_heatmap;
		global $qahm_article_list;

		// dir
		$data_dir          = $this->get_data_dir_path();
		$readers_dir       = $data_dir . 'readers/';
		$readerstemp_dir   = $data_dir . 'readers/temp/';
		$readersfinish_dir = $data_dir . 'readers/finish/';
		$readersdbin_dir   = $data_dir . 'readers/dbin/';
		$temp_dir          = $data_dir . 'temp/';
		$tempdelete_dir    = $data_dir . 'temp/delete/';
		$heatmapwork_dir   = $data_dir . 'heatmap-view-work/';
		$replaywork_dir    = $data_dir . 'replay-view-work/';
		$cache_dir         = $data_dir . 'cache/';
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$viewpv_dir        = $myview_dir . 'view_pv/';
		$raw_p_dir         = $viewpv_dir . 'raw_p/';
		$raw_c_dir         = $viewpv_dir . 'raw_c/';
		$raw_e_dir         = $viewpv_dir . 'raw_e/';
		$vw_reader_dir     = $myview_dir . 'readers/';
		$vw_verhst_dir     = $myview_dir . 'version_hist/';
		$vw_summary_dir     = $myview_dir . 'summary/';
		$vw_bshtml_dir     = $vw_verhst_dir . 'base_html/';

		//.donotbackup( for JetPack )
		$donotbackup_file		= $data_dir . '.donotbackup';

		// yday
		$dbin_session_file   = $temp_dir . 'dbin_session_file.php';
		$yday_loopcount_file = $temp_dir . 'ydayloopfile.php';
		$yday_pvmaxcnt_file  = $temp_dir . 'yday_pvmaxcnt_file';
		$ary_readers_file    = $temp_dir . 'ary_readers_file.php';
		$ary_media_file      = $temp_dir . 'ary_media_file.php';
		$ary_sources_file    = $temp_dir . 'ary_sources_file.php';
		$ary_campaigns_file  = $temp_dir . 'ary_campaigns_file.php';
		$ary_pages_file      = $temp_dir . 'ary_pages_file.php';
		$ary_pv_file         = $temp_dir . 'ary_pv_file.php';
		$ary_wp_s_file       = $temp_dir . 'ary_wp_s_file.php';

		// raw
		$raw_loopcount_file  = $temp_dir . 'raw_loopcount_file.php';
		$ary_new_pvrows_file = $temp_dir . 'ary_new_pvrows_file.php';

		// cache
		$cache_heatmap_list_file        = $cache_dir . 'heatmap_list.php';
		$cache_heatmap_list_temp_file   = $cache_dir . 'heatmap_list_temp.php';
		$cache_heatmap_list_idx_temp_file = $cache_dir . 'heatmap_list_idx_temp.php';
        $summary_days_access_file       = $vw_summary_dir . 'days_access.php';
		$summary_days_access_detail_file = $vw_summary_dir . 'days_access_detail.php';
		// loop count max
		$now_pv_loop_maxfile  = $temp_dir . 'now_pv_loop_maxfile.php';
		$NOW_PV_LOOP_MAX      = self::PV_LOOP_MAX;
		$now_raw_loop_maxfile = $temp_dir . 'now_raw_loop_maxfile.php';
		$NOW_RAW_LOOP_MAX     = self::RAW_LOOP_MAX;

		$now_pvlog_count_fetchfile = $temp_dir . 'now_pvlog_count_fetchfile.php';

		// delete files list
		$del_rawfileslist_temp = $tempdelete_dir . 'del';
		$del_rawfileslist_file = $data_dir . 'del_rawfileslist_file.php';

		// start
		$while_lpcnt        = 0;
		$is_night_comp_file = $data_dir . 'is_night_comp_file.php';
		$is_night_complete  = false;

		$qahm_log->info( QAHM_NAME . ' cron_data_manage start ' . $this->get_data_dir_path() );

		// ----------
		// 1st,check cron lock
		// ----------

		// cron lock?
		if ( $wp_filesystem->exists( $this->get_cron_lock_path() ) ) {
			$cron_retry = (int) $wp_filesystem->get_contents( $this->get_cron_lock_path() );
			// wait 5 times or forced proceed
			++$cron_retry;
			if ( 6 > $cron_retry ) {
				$wp_filesystem->put_contents( $this->get_cron_lock_path(), $cron_retry );
				// exit
				$cron_retry_str = (string) $cron_retry;
				throw new Exception( esc_html( QAHM_NAME ) . ' cronは既に' . esc_html( $cron_retry_str ) . '回稼働しています。終了します。' );
			} else {
				// 異常終了しているはず。確認して強制継続
				// check cron status
				$cron_status     = $this->get_status();
				$cron_step_array = explode( '>', $cron_status );
				if ( 'Idle' === $cron_step_array[0] ) {
					$cron_status = 'Cron start';
					$this->set_next_status( $cron_status );
				} else {
					switch ( $cron_step_array[0] ) {
						case 'Day':
							// おそらく10分間でも完了しないほどのファイル数で異常事態。ログファイルに記入してcron lockを削除して強制終了
							// delete cron lock
							if ( ! $wp_filesystem->delete( $this->get_cron_lock_path() ) ) {
								throw new Exception( '$wp_filesystem->delete()に失敗しました。パス：' . esc_html( $this->get_cron_lock_path() ) );
							}
							throw new Exception( 'Dayの処理が異常終了しました。終了します。' );
							break;

						case 'Night':
							// 　夜間で同じタスクを継続してエラーになるのはないが、Loop系は10分で処理が終わらないと思われるのでLoop数を小さくする。但し100以下は遅すぎるので元に戻す
							if ( $cron_step_array[1] == 'Create yesterday data' ) {
								if ( $wp_filesystem->exists( $now_pv_loop_maxfile ) ) {
									$NOW_PV_LOOP_MAX = $wp_filesystem->get_contents( $now_pv_loop_maxfile );
									$NOW_PV_LOOP_MAX = ceil( $NOW_PV_LOOP_MAX * 0.8 );
									if ( $NOW_PV_LOOP_MAX < 100 ) {
										$NOW_PV_LOOP_MAX = self::PV_LOOP_MAX;
									}
									$wp_filesystem->put_contents( $now_pv_loop_maxfile, $NOW_PV_LOOP_MAX );
									$cron_status = 'Night>Create yesterday data>Loop>Start';
								}
							}
							if ( $cron_step_array[1] == 'Insert raw data' ) {
								if ( ! empty( $cron_step_array[2] ) ) {
									if ( $cron_step_array[2] == 'Loop' ) {
										if ( $wp_filesystem->exists( $now_raw_loop_maxfile ) ) {
											$NOW_RAW_LOOP_MAX = $wp_filesystem->get_contents( $now_raw_loop_maxfile );
											$NOW_RAW_LOOP_MAX = ceil( $NOW_RAW_LOOP_MAX * 0.8 );
											if ( $NOW_RAW_LOOP_MAX < 100 ) {
												$NOW_RAW_LOOP_MAX = self::RAW_LOOP_MAX;
											}
											$wp_filesystem->put_contents( $now_raw_loop_maxfile, $NOW_RAW_LOOP_MAX );
											$cron_status = 'Night>Insert raw data>Loop>Start';
										}
									}
								}
							}
							break;

						default:
							break;
					}
				}
				// delete cron lock
				if ( ! $wp_filesystem->delete( $this->get_cron_lock_path() ) ) {
					throw new Exception( '$wp_filesystem->delete()に失敗しました。パス：' . esc_html( $this->get_cron_lock_path() ) );
				}
			}
		} else {
			// cron is not lock.keep working!
			$cron_status = $this->get_status();
		}

		// cron ロックファイル生成
		if ( ! $wp_filesystem->put_contents( $this->get_cron_lock_path(), '1' ) ) {
			throw new Exception( 'cronのロックファイル生成に失敗しました。終了します。' );
		}
		
		// ログの削除
		$qahm_log->delete();

		// last, final check cron status
		$cron_step_array = explode( '>', $cron_status );
		if ( 'Idle' === $cron_step_array[0] ) {
			$cron_status = 'Cron start';
			$this->set_next_status( $cron_status );
		}

		// update
		$qahm_update = new QAHM_Update();
		$qahm_update->check_version();

		// ----------
		// do cron job
		// ----------

		$while_continue = true;
		while ( $while_continue ) {
			$qahm_log->info( 'cron_status:' . $cron_status );
			switch ( $cron_status ) {
				case 'Cron start':
					$this->backup_prev_status( $cron_status );
					// ---next
					$cron_status = 'Check base dir';
					$this->set_next_status( $cron_status );
					break;


				case 'Check base dir':
					$this->backup_prev_status( $cron_status );

					// dataディレクトリはこのタイミングで作成
					if ( ! $wp_filesystem->exists( $data_dir ) ) {
						$wp_filesystem->mkdir( $data_dir );
					}
					//.donotbackup(FILE for JetPack)
					if ( ! $wp_filesystem->exists( $donotbackup_file ) ) {
						$wp_filesystem->put_contents( $donotbackup_file, '' );
					}						
					if ( ! $wp_filesystem->exists( $readers_dir ) ) {
						$wp_filesystem->mkdir( $readers_dir );
					}
					if ( ! $wp_filesystem->exists( $readerstemp_dir ) ) {
						$wp_filesystem->mkdir( $readerstemp_dir );
					}
					if ( ! $wp_filesystem->exists( $readersfinish_dir ) ) {
						$wp_filesystem->mkdir( $readersfinish_dir );
					}
					if ( ! $wp_filesystem->exists( $readersdbin_dir ) ) {
						$wp_filesystem->mkdir( $readersdbin_dir );
					}
					if ( ! $wp_filesystem->exists( $temp_dir ) ) {
						$wp_filesystem->mkdir( $temp_dir );
					}
					if ( ! $wp_filesystem->exists( $tempdelete_dir ) ) {
						$wp_filesystem->mkdir( $tempdelete_dir );
					}
					if ( ! $wp_filesystem->exists( $heatmapwork_dir ) ) {
						$wp_filesystem->mkdir( $heatmapwork_dir );
					}
					if ( ! $wp_filesystem->exists( $replaywork_dir ) ) {
						$wp_filesystem->mkdir( $replaywork_dir );
					}
					if ( ! $wp_filesystem->exists( $cache_dir ) ) {
						$wp_filesystem->mkdir( $cache_dir );
					}
					//view_base
					if ( ! $wp_filesystem->exists( $view_dir ) ) {
						$wp_filesystem->mkdir( $view_dir );
					}
					if ( ! $wp_filesystem->exists( $myview_dir ) ) {
						$wp_filesystem->mkdir( $myview_dir );
					}
					//view_pv
					if ( ! $wp_filesystem->exists( $viewpv_dir ) ) {
						$wp_filesystem->mkdir( $viewpv_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_p_dir ) ) {
						$wp_filesystem->mkdir( $raw_p_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_c_dir ) ) {
						$wp_filesystem->mkdir( $raw_c_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_e_dir ) ) {
						$wp_filesystem->mkdir( $raw_e_dir );
					}
					// reader
					if ( ! $wp_filesystem->exists( $vw_reader_dir ) ) {
						$wp_filesystem->mkdir( $vw_reader_dir );
					}
					// version_hist
					if ( ! $wp_filesystem->exists( $vw_verhst_dir ) ) {
						$wp_filesystem->mkdir( $vw_verhst_dir );
					}
					if ( ! $wp_filesystem->exists( $vw_bshtml_dir ) ) {
						$wp_filesystem->mkdir( $vw_bshtml_dir );
					}

					// ---next
					$cron_status = 'Check summary day access detail file';
					$this->set_next_status( $cron_status );
					break;

				case 'Check summary day access detail file':
					$this->backup_prev_status( $cron_status );

					global $qahm_db;
					if ( ! $wp_filesystem->exists( $summary_days_access_detail_file ) ) {
						if ( $wp_filesystem->exists( $summary_days_access_file ) ) {
							//過去データがある人なのでサマリー計算
							$qahm_db->make_summary_days_access_detail();
						}
					}

					// ---next
					$cron_status = 'Check free';
					$this->set_next_status( $cron_status );
					break;

				case 'Check free':
					$this->backup_prev_status( $cron_status );

					$cron_status = 'Check license';
					$license_plan = $this->wrap_get_option( 'license_plans' );
					// フリーユーザーはライセンス確認不要
					if ( ! $license_plan ) {
						$cron_status = 'Check time';
					}
					// ---next
					$this->set_next_status( $cron_status );
					break;

				case 'Check license':
					$this->backup_prev_status( $cron_status );

					$license_activate_time = $this->wrap_get_option( 'license_activate_time' );
					$today_str             = $qahm_time->today_str();
					$today_start           = $qahm_time->str_to_unixtime( $today_str . ' 00:00:00' );

					// 本日ライセンス未確認だったら実行。アクセスが少ないサイトではいつ実行されるかわからないので、分は40分で分散させる。
                    if ( $license_activate_time < $today_start ) {
                        // NONCE_SALTが定義されているかどうかをチェック
                        if ( defined( 'NONCE_SALT' ) && NONCE_SALT !== '' ) {
                            $seed = NONCE_SALT;
                        } else {
							if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
                            	//$seed = $_SERVER['SERVER_ADDR'];
								$seed = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
							}
                        }

                        // シード値をハッシュ化して数値に変換
                        $hash = md5( $seed );
                        $numeric_seed = hexdec( substr( $hash, 0, 8 ) );

                        $now_hour   = $qahm_time->hour();
                        $now_min    = $qahm_time->minute();
                        $check_hour = $numeric_seed % 24;
                        $check_min  = $numeric_seed % 40;

                        if ( $check_hour <= $now_hour && $check_min <= $now_min ) {
                            global $qahm_license;
                            $key = $this->wrap_get_option( 'license_key' );
                            $id  = $this->wrap_get_option( 'license_id' );
                            $qahm_license->activate( $key, $id );
                        }
                    }

					// ---next
					$cron_status = 'Check time';
					$this->set_next_status( $cron_status );
					break;

				case 'Check time':
					$this->backup_prev_status( $cron_status );

					// 標準はDay
					$cron_status = 'Day>Start';

					// 夜間バッチの状態を確認。ファイルがない＝インストール直後は夜間バッチは未完了状態とする
					if ( $wp_filesystem->exists( $is_night_comp_file ) ) {
						$night_comp_mtime = $wp_filesystem->mtime( $is_night_comp_file );
						$today_str        = $qahm_time->today_str();
						$today_start      = $qahm_time->str_to_unixtime( $today_str . ' 00:00:00' );

						if ( $today_start < $night_comp_mtime ) {
							$is_night_complete = true;
						} else {
							$is_night_complete = false;
						}
					} else {
						$is_night_complete = false;
					}

					/*
					// 定時になったら夜間バッチを開始。一旦開始すると夜間が終了までこの「Check time」は発生しない。終わっていたら常にDay Startに
					$nowhour = (int) $qahm_time->hour();
					if ( $nowhour >= self::NIGHT_START ) {
						$cron_status = 'Night>Start';
						if ( $is_night_complete ) {
							$cron_status = 'Day>Start';
						}
					}*/

                    // 現在の時間を取得
                    $nowhour = (int) $qahm_time->hour();
                    $nowmin = (int) $qahm_time->minute();

                    // $readersfinish_dirのファイル数を取得
                    $readersfinish_files = glob($readersfinish_dir . '*');
                    $file_count = count($readersfinish_files);

                    // サーバーのIPアドレスまたはNONCE_SALTを使用してシードを設定
                    if (defined('NONCE_SALT') && NONCE_SALT !== '') {
                        $seed = NONCE_SALT;
                    } else {
						if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
                        	//$seed = $_SERVER['SERVER_ADDR'];
							$seed = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
						}
                    }

                    // シード値をハッシュ化して数値に変換
                    $hash = md5($seed);
                    $numeric_seed = hexdec(substr($hash, 0, 8));

                    // シード値に基づいてランダムな開始時間を設定
                    $start_hour = 1 + $numeric_seed % 3;
                    $start_min  = $numeric_seed % 60;

                    // 条件に基づいてcronのステータスを設定
                    if ($is_night_complete) {
                        $cron_status = 'Day>Start';
                    } elseif ($file_count > 5000 && $nowhour >= 1) {
                        $cron_status = 'Night>Start';
                    } elseif ($nowhour > $start_hour) {
                        $cron_status = 'Night>Start';
                    } elseif ($nowhour === $start_hour && $nowmin >= $start_min) {
                        $cron_status = 'Night>Start';
                    }


					// ---next
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Daytime
				// ----------
				case 'Day>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Day>Session check';
					$this->set_next_status( $cron_status );
					break;

				case 'Day>Session check':
					$this->backup_prev_status( $cron_status );

					$session_files = $this->wrap_dirlist( $readerstemp_dir );
					$now_unixtime = $qahm_time->now_unixtime();
					// セッションファイルを一つずつ処理
					if ( $session_files ) {

						// メール送信判定
						$count_pv = $this->count_this_month_pv();
						$limit_pv = self::DEFAULT_LIMIT_PV_MONTH;
						$measure  = $this->get_license_option( 'measure' );
						if ( $measure ) {
							$limit_pv = $measure;
						}
						$this_month = $qahm_time->monthstr();

						if ( $count_pv >= $limit_pv ) {
							$this->wrap_update_option( 'pv_limit_rate', 100 );

							$mail_month = $this->wrap_get_option( 'pv_over_mail_month' );
							if ( $this_month !== $mail_month ) {
								$lang_set = get_bloginfo('language');
								if ( $lang_set == 'ja' ) {
									$upgrade_link = 'https://quarka.org/plan/'; 
									$referral_link = 'https://quarka.org/referral-program/';
								} else {
									$upgrade_link = 'https://quarka.org/en/#plans';
									$referral_link = 'https://quarka.org/en/referral-program/';
								}
								$subject  = __( 'Page View Limit Reached: Upgrade or Earn Extra Capacity', 'qa-heatmap-analytics' );
								/* translators: placeholders are for the site name */
								$message  = sprintf( __( 'This is to inform you that the page view capacity limit for QA Analytics on your site, %s, has been reached.', 'qa-heatmap-analytics' ), get_bloginfo('name') ) . PHP_EOL;
								$message .= __( 'As a result, data collection has been temporarily paused until the beginning of the next month. This means that analytics data will not be recorded during this period.', 'qa-heatmap-analytics' ) . PHP_EOL;
								$message .= __( 'Check your QA Analytics Dashboard:', 'qa-heatmap-analytics' ) . PHP_EOL;
								$message .= admin_url( 'admin.php?page=qahm-home' ) . PHP_EOL . PHP_EOL;
								$message .= __( 'To ensure your access to analytics data uninterrupted, consider upgrading your plan. You can also earn additional page view capacity by joining our Referral Program.', 'qa-heatmap-analytics' ) . PHP_EOL . PHP_EOL;
								$message .= __( 'Refer friends to gain extra PV capacity', 'qa-heatmap-analytics' ). PHP_EOL;
								$message .= $referral_link . PHP_EOL . PHP_EOL;
								$message .= __( 'QA Analytics "Plan and Pricing"', 'qa-heatmap-analytics' ). PHP_EOL;
								$message .= $upgrade_link . PHP_EOL. PHP_EOL;
								
								$this->qa_mail( $subject, $message );
								$this->wrap_update_option( 'pv_warning_mail_month', $this_month );
								$this->wrap_update_option( 'pv_over_mail_month', $this_month );
								$this->wrap_update_option( 'announce_friend_plan', true );
							}

							// PV上限超過時はrawファイル & セッションファイルの削除
							foreach ( $session_files as $session_file ) {
								$elapsed_sec = $now_unixtime - $session_file['lastmodunix'];

								// 作成されてから30分以上たってたら削除
								$min = 30;
								if ( $elapsed_sec > ( $min * 60 ) ) {
									$readers_temp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $readerstemp_dir . $session_file['name'] ) );
									if ( ! $readers_temp_ary ) {
										$this->wrap_delete( $readerstemp_dir . $session_file['name'] );
										continue;
									}

									$dev_name = $readers_temp_ary['head']['device_name'];
									$readers_temp_body_max = count( $readers_temp_ary['body'] );

									// セッションファイルに記録されているPVを一つずつ処理
									for ( $iii = 0; $iii < $readers_temp_body_max; $iii++ ) {
										$body = $readers_temp_ary['body'][$iii];

										// Detect position file
										$raw_dir    = $this->get_raw_dir_path( $body['page_type'], $body['page_id'], $dev_name );
										$qa_id_ary  = explode( '.', $session_file['name'] );

										// 遡って同じページを見ていないか確認する。
										// 2ページ目を見ている場合など、ページタイプとページIDが同じなのにURLが違うケースもあるのでそこも想定
										$pv_num = 1;
										if ( $iii > 0 ) {
											for ( $jjj = $iii - 1; $jjj >= 0; $jjj-- ) {
												$prev_body = $readers_temp_ary['body'][$jjj];
												if ( $prev_body['page_url'] === $body['page_url'] ||
													( $prev_body['page_type'] === $body['page_type'] && $prev_body['page_id'] === $body['page_id'] ) ) {
													$pv_num++;
												}
											}
										}

										$raw_p_path = $raw_dir . $qa_id_ary[0] . '_' . $pv_num . '-p.php';
										$raw_p_tsv = null;
										if ( $wp_filesystem->exists( $raw_p_path ) ) {
											$raw_p_tsv = $this->wrap_delete( $raw_p_path );
										}

										$raw_c_path = $raw_dir . $qa_id_ary[0] . '_' . $pv_num . '-c.php';
										$raw_c_tsv = null;
										if ( $wp_filesystem->exists( $raw_c_path ) ) {
											$raw_c_tsv = $this->wrap_delete( $raw_c_path );
										}

										$raw_e_path = $raw_dir . $qa_id_ary[0] . '_' . $pv_num . '-e.php';
										$raw_e_tsv = null;
										if ( $wp_filesystem->exists( $raw_e_path ) ) {
											$raw_e_tsv = $this->wrap_delete( $raw_e_path );
										}
									}

									$this->wrap_delete( $readerstemp_dir . $session_file['name'] );
								}
							}

							// ---next
							$cron_status = 'Day>End';
							$this->set_next_status( $cron_status );
							break;

						} else {
							$rate = ( $count_pv / $limit_pv ) * 100;
							$rate = floor( $rate );
							$this->wrap_update_option( 'pv_limit_rate', $rate );

							if( $rate >= 80 ) {
								$mail_month = $this->wrap_get_option( 'pv_warning_mail_month' );
								if ( $this_month !== $mail_month ) {
									$lang_set = get_bloginfo('language');
									if ( $lang_set == 'ja' ) {
										$upgrade_link = 'https://quarka.org/plan/'; 
										$referral_link = 'https://quarka.org/referral-program/';
									} else {
										$upgrade_link = 'https://quarka.org/en/#plans';
										$referral_link = 'https://quarka.org/en/referral-program/';
									}
									$subject  = __( 'Page View Capacity Reached 80%: Consider Upgrading or Earning Extra Capacity', 'qa-heatmap-analytics' );
									/* translators: placeholders are for the site name */
									$message  = sprintf( __( 'The page view capacity for QA Analytics on your site, %s, has reached 80%%.', 'qa-heatmap-analytics' ), get_bloginfo('name') ) . PHP_EOL;
									$message .= __( 'Once the page view capacity is reached, data collection will be paused until the next month.', 'qa-heatmap-analytics' ) . PHP_EOL;
									$message .= __( 'Check your QA Analytics Dashboard:', 'qa-heatmap-analytics' ) . PHP_EOL;
									$message .= admin_url( 'admin.php?page=qahm-home' ) . PHP_EOL . PHP_EOL;
									$message .= __( 'To ensure uninterrupted access to analytics data, you may want to consider upgrading your plan. You can also earn page view capacity by participating in our Referral Program.', 'qa-heatmap-analytics' ) . PHP_EOL . PHP_EOL;
									$message .= __( 'Refer friends to gain extra PV capacity', 'qa-heatmap-analytics' ). PHP_EOL;
									$message .= $referral_link . PHP_EOL . PHP_EOL;
									$message .= __( 'QA Analytics "Plan and Pricing"', 'qa-heatmap-analytics' ). PHP_EOL;
									$message .= $upgrade_link . PHP_EOL. PHP_EOL;
									$this->qa_mail( $subject, $message );
									$this->wrap_update_option( 'pv_warning_mail_month', $this_month );
									$this->wrap_update_option( 'announce_friend_plan', true );
								}
							}
						}

						global $qahm_view_replay;

						$realtime_view_path = $readers_dir . 'realtime_view.php';
						if ( $wp_filesystem->exists( $realtime_view_path ) ) {
							$realtime_view_ary = $this->wrap_unserialize( $this->wrap_get_contents( $realtime_view_path ) );
						} else {
							$realtime_view_ary = array();
							$realtime_view_ary['head']['version'] = 1;
							$realtime_view_ary['body'] = array();
						}

						foreach ( $session_files as $session_file ) {
							$elapsed_sec = $now_unixtime - $session_file['lastmodunix'];

							// 作成されてから30分以上たってたらfinishへ
							$min = 30;
							if ( $elapsed_sec > ( $min * 60 ) ) {
								$readers_temp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $readerstemp_dir . $session_file['name'] ) );
								if ( ! $readers_temp_ary ) {
									$wp_filesystem->delete( $readerstemp_dir . $session_file['name'] );
									continue;
								}
                                if ( ! ( isset( $readers_temp_ary['head'] ) && isset( $readers_temp_ary['body'] ) ) ) {
                                    $wp_filesystem->delete( $readerstemp_dir . $session_file['name'] );
                                    continue;
                                }

								$readers_finish_ary = array();
								$readers_finish_ary['head']['version']        = 1;
								$readers_finish_ary['head']['tracking_id']    = $readers_temp_ary['head']['tracking_id'];
								$readers_finish_ary['head']['device_name']    = $readers_temp_ary['head']['device_name'];
								$readers_finish_ary['head']['is_new_user']    = $readers_temp_ary['head']['is_new_user'];
								$readers_finish_ary['head']['user_agent']     = $readers_temp_ary['head']['user_agent'];
								$readers_finish_ary['head']['first_referrer'] = $readers_temp_ary['head']['first_referrer'];
								$readers_finish_ary['head']['utm_source']     = $readers_temp_ary['head']['utm_source'];
								$readers_finish_ary['head']['utm_medium']     = $readers_temp_ary['head']['utm_medium'];
								$readers_finish_ary['head']['utm_campaign']   = $readers_temp_ary['head']['utm_campaign'];
								$readers_finish_ary['head']['utm_term']       = $readers_temp_ary['head']['utm_term'];
								$readers_finish_ary['head']['original_id']    = $readers_temp_ary['head']['original_id'];
								$readers_finish_ary['head']['country']        = $readers_temp_ary['head']['country'];
								$readers_finish_ary['body'] = array();

								$dev_name = $readers_temp_ary['head']['device_name'];
								$readers_temp_body_max = count( $readers_temp_ary['body'] );

								$first_access  = '';
								$first_url     = '';
								$first_title   = '';
								$last_exit     = '';
								$last_url      = '';
								$last_title    = '';
								$total_pv      = 0;
								$sec_on_site   = 0;

								// セッションファイルに記録されているPVを一つずつ処理
								for ( $iii = 0; $iii < $readers_temp_body_max; $iii++ ) {
									$body = $readers_temp_ary['body'][$iii];

									// Detect position file
									$raw_dir    = $this->get_raw_dir_path( $body['page_type'], $body['page_id'], $dev_name );
									$qa_id_ary  = explode( '.', $session_file['name'] );

									// 遡って同じページを見ていないか確認する。
									// 2ページ目を見ている場合など、ページタイプとページIDが同じなのにURLが違うケースもあるのでそこも想定
									$pv_num = 1;
									if ( $iii > 0 ) {
										for ( $jjj = $iii - 1; $jjj >= 0; $jjj-- ) {
											$prev_body = $readers_temp_ary['body'][$jjj];
											if ( $prev_body['page_url'] === $body['page_url'] ||
												( $prev_body['page_type'] === $body['page_type'] && $prev_body['page_id'] === $body['page_id'] ) ) {
												$pv_num++;
											}
										}
									}

									$raw_p_path = $raw_dir . $qa_id_ary[0] . '_' . $pv_num . '-p.php';
									$raw_p_tsv = null;
									if ( $wp_filesystem->exists( $raw_p_path ) ) {
										$raw_p_tsv = $this->wrap_get_contents( $raw_p_path );
									}
									
									$raw_e_path = $raw_dir . $qa_id_ary[0] . '_' . $pv_num . '-e.php';
									$raw_e_tsv = null;
									if ( $wp_filesystem->exists( $raw_e_path ) ) {
										$raw_e_tsv = $this->wrap_get_contents( $raw_e_path );
									}
									
									/*
										ajaxの通信タイミングによっては意図しないデータになる可能性があるので、念の為ソートする
									*/
									// raw_pのソート（おそらく必要なし）
									if ( $raw_p_tsv ) {
										$raw_p_ary = null;
										$raw_p_ary = $this->convert_tsv_to_array( $raw_p_tsv );
										$sort_ary  = array();
										$sort_ary[self::DATA_COLUMN_HEADER] = -1;

										if( 2 === (int) $raw_p_ary[self::DATA_COLUMN_HEADER][self::DATA_HEADER_VERSION] ) {
											for ( $raw_p_idx = self::DATA_COLUMN_BODY, $raw_p_max = count( $raw_p_ary ); $raw_p_idx < $raw_p_max; $raw_p_idx++ ) {
												$sort_ary[$raw_p_idx] = $raw_p_ary[$raw_p_idx][self::DATA_POS_2['STAY_HEIGHT']];
											}
										}

										array_multisort( $sort_ary, SORT_ASC, $raw_p_ary );
										$raw_p_tsv = $this->convert_array_to_tsv( $raw_p_ary );
										$this->wrap_put_contents( $raw_p_path, $raw_p_tsv );
									}
									
									// raw_eのソート
									if ( $raw_e_tsv ) {
										$raw_e_ary = null;
										$raw_e_ary = $this->convert_tsv_to_array( $raw_e_tsv );
										$sort_ary  = array();
										$sort_ary[self::DATA_COLUMN_HEADER] = -1;

										if( 1 === (int) $raw_e_ary[self::DATA_COLUMN_HEADER][self::DATA_HEADER_VERSION] ) {
											for ( $raw_e_idx = self::DATA_COLUMN_BODY, $raw_e_max = count( $raw_e_ary ); $raw_e_idx < $raw_e_max; $raw_e_idx++ ) {
												$sort_ary[$raw_e_idx] = $raw_e_ary[$raw_e_idx][self::DATA_EVENT_1['TIME']];
											}
										}

										array_multisort( $sort_ary, SORT_ASC, $raw_e_ary );
										$raw_e_tsv = $this->convert_array_to_tsv( $raw_e_ary );
										$this->wrap_put_contents( $raw_e_path, $raw_e_tsv );
									}

									// 滞在時間（秒）をraw_p, raw_eから求める
									$raw_p_time = $qahm_view_replay->get_time_on_page_to_raw_p( $raw_p_tsv );
									$raw_e_time = $qahm_view_replay->get_time_on_page_to_raw_e( $raw_e_tsv );

									$sec_on_page         = max( $raw_p_time, $raw_e_time );
									$body['sec_on_page'] = $sec_on_page;
									array_push( $readers_finish_ary['body'], $body );

									/*
									debug
									$qahm_log->debug( 'name: ' . $session_file['name'] );
									$qahm_log->debug( '$pv_num: ' . $pv_num );
									$qahm_log->debug( '$raw_p_time: ' . $raw_p_time );
									$qahm_log->debug( '$raw_e_time: ' . $raw_e_time );
									$qahm_log->debug( '$sec_on_page: ' . $sec_on_page );
									*/

									// set tsv variables
									$sec_on_site += $sec_on_page;
									$total_pv++;
									if ( $iii === 0 ) {
										$first_access = $body['access_time'];
										$first_url    = $body['page_url'];
										$first_title  = $body['page_title'];
									}
									if ( $iii === $readers_temp_body_max - 1 ) {
										$last_exit_time = $body['access_time'] + $sec_on_page;
										$last_url       = $body['page_url'];
										$last_title     = $body['page_title'];
									}
								}

								// finishの生成 & tempの削除
								if ( $readers_finish_ary ) {
									$this->wrap_mkdir( $readersfinish_dir );
									$this->wrap_put_contents( $readersfinish_dir . $session_file['name'], $this->wrap_serialize( $readers_finish_ary ) );

									// realtime viewの要素を追加
									$realtime_view_body = array(
										'file_name'         => $session_file['name'],
										'tracking_id'       => $readers_finish_ary['head']['tracking_id'],
										'device_name'       => $readers_finish_ary['head']['device_name'],
										'is_new_user'       => $readers_finish_ary['head']['is_new_user'],
										'user_agent'        => $readers_finish_ary['head']['user_agent'],
										'first_referrer'    => $readers_finish_ary['head']['first_referrer'],
										'utm_source'        => $readers_finish_ary['head']['utm_source'],
										'utm_medium'        => $readers_finish_ary['head']['utm_medium'],
										'utm_campaign'      => $readers_finish_ary['head']['utm_campaign'],
										'utm_term'          => $readers_finish_ary['head']['utm_term'],
										'original_id'       => $readers_finish_ary['head']['original_id'],
										'country'           => $readers_finish_ary['head']['country'],
										'first_access_time' => $first_access,
										'first_url'         => $first_url,
										'first_title'       => $first_title,
										'last_exit_time'    => $last_exit_time,
										'last_url'          => $last_url,
										'last_title'        => $last_title,
										'page_view'         => $total_pv,
										'sec_on_site'       => $sec_on_site,
									);
									array_push( $realtime_view_ary['body'], $realtime_view_body );
								}
								$wp_filesystem->delete( $readerstemp_dir . $session_file['name'] );
							}
						}

						// realtime_viewを離脱時刻でソート
						$realtime_view_body_max = count( $realtime_view_ary['body'] );
						if ( $realtime_view_body_max > 1 ) {
							// バブルソート
							for ( $ooo = $realtime_view_body_max; $ooo > 0; $ooo-- ) {
								for ( $sss = 0; $sss < $ooo - 1; $sss++ ) {
									$now_exit_time = $realtime_view_ary['body'][ $sss ][ 'last_exit_time' ];
									$next_exit_time = $realtime_view_ary['body'][ $sss + 1 ][ 'last_exit_time' ];

									if ( $now_exit_time < $next_exit_time ) {
										$temp_ary = $realtime_view_ary['body'][ $sss ];
										$realtime_view_ary['body'][ $sss ] = $realtime_view_ary['body'][ $sss + 1 ];
										$realtime_view_ary['body'][ $sss + 1 ] = $temp_ary;
									}
								}
							}
						}
							
						if ( $realtime_view_body_max > 0 ) {
							$this->wrap_put_contents( $realtime_view_path, $this->wrap_serialize( $realtime_view_ary ) );
						}
					}

					// ---next
					$cron_status = 'Day>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Day>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Cron end';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Night
				// ----------
				case 'Night>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Create yesterday data>Start';
					// if Immediately after 1st install -> db create -> today's night end (exit)-> day start
					$check_exists = -123454321;
					$ver = $this->wrap_get_option( 'qa_readers_version', $check_exists );
					if ( $ver === $check_exists ) {
						$cron_status = 'Night>Dbinit>Start';
					}
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Dbinit>Start':
					$this->backup_prev_status( $cron_status );

					$cron_status = 'Night>Dbinit>Exec';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Dbinit>Exec':
					$this->backup_prev_status( $cron_status );

					// クエリの実行 -maru 20201114
					$this->exec_database_query();
					// upper is a long time execution. set next and exit
					$cron_status = 'Night>Dbinit>Updateoption';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Dbinit>Updateoption':
					$this->backup_prev_status( $cron_status );

					// wp_optionsの初期値設定
					foreach ( QAHM_DB_OPTIONS as $key => $value ) {
						$this->check_exist_update( $key, $value );
					}
					$cron_status = 'Night>Dbinit>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Dbinit>End':
					$this->backup_prev_status( $cron_status );

					$cron_status = 'Night>End';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Create yesterday data
				// ----------
				case 'Night>Create yesterday data>Start':
					$this->backup_prev_status( $cron_status );

					$loopstart = 0;
					$wp_filesystem->put_contents( $yday_loopcount_file, $loopstart );
					$wp_filesystem->put_contents( $yday_pvmaxcnt_file, $loopstart );
					$wp_filesystem->put_contents( $now_pv_loop_maxfile, self::PV_LOOP_MAX );

					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Start':
					$this->backup_prev_status( $cron_status );

					// temp file delete
					if ( $wp_filesystem->exists( $ary_readers_file ) ) {
						$wp_filesystem->delete( $ary_readers_file );
					}
					if ( $wp_filesystem->exists( $ary_pages_file ) ) {
						$wp_filesystem->delete( $ary_pages_file );
					}
					if ( $wp_filesystem->exists( $ary_media_file ) ) {
						$wp_filesystem->delete( $ary_media_file );
					}
					if ( $wp_filesystem->exists( $ary_sources_file ) ) {
						$wp_filesystem->delete( $ary_sources_file );
					}
					if ( $wp_filesystem->exists( $ary_campaigns_file ) ) {
						$wp_filesystem->delete( $ary_campaigns_file );
					}
					if ( $wp_filesystem->exists( $ary_pv_file ) ) {
						$wp_filesystem->delete( $ary_pv_file );
					}
					if ( $wp_filesystem->exists( $ary_wp_s_file ) ) {
						$wp_filesystem->delete( $ary_wp_s_file );
					}

					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Make Array';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Make Array':
					$this->backup_prev_status( $cron_status );

					// finish dir search and making loop list
					$fin_session_files = $this->wrap_dirlist( $readersfinish_dir );
					// yesterday
					$yday_session_files = array();

					$yesterday_str      = $qahm_time->xday_str( -1 );
					$yesterday_end      = $qahm_time->str_to_unixtime( $yesterday_str . ' 23:59:59' );
					// 昨日のセッションファイルを取得
					$iii = 0;
					if ( is_array( $fin_session_files ) ) {
						$dbin_session_files = [];
						foreach ( $fin_session_files as $fin_session_file ) {
							$fin_time = $fin_session_file['lastmodunix'];
								if ( $fin_time <= $yesterday_end ){
								$yday_session_files[ $iii ] = $fin_session_file;
								$dbin_session_files[ $iii ] = $readersfinish_dir . $fin_session_file['name'];
								$iii++;
								}
						}
					}

					if ( $iii > 0 ) {

						$nowloop = 0;
						if ( $wp_filesystem->exists( $yday_loopcount_file ) ) {
							$nowloop_ary = $wp_filesystem->get_contents_array( $yday_loopcount_file );
							$nowloop     = (int) $nowloop_ary[0];
						} else {
							$wp_filesystem->put_contents( $yday_loopcount_file, $nowloop );
						}

						$loopadd = self::PV_LOOP_MAX;
						if ( $wp_filesystem->exists( $now_pv_loop_maxfile ) ) {
							$loopadd = $wp_filesystem->get_contents( $now_pv_loop_maxfile );
						}
						$thisloopmax = $nowloop + $loopadd;

						$yday_loopmax = count( $yday_session_files );
						if ( $yday_loopmax < $thisloopmax ) {
							$thisloopmax = $yday_loopmax;
							$loop_str    = $yday_loopmax . PHP_EOL . self::LOOPLAST_MSG;
							$wp_filesystem->put_contents( $yday_loopcount_file, $loop_str );
						}
						$readers_ary   = array();
						$media_ary     = array();
						$sources_ary   = array();
						$campaigns_ary = array();
						$pages_ary     = array();
						$pv_ary        = array();
						$wp_s_ary      = array();

						// 小分けにした（初期300回）ループで昨日のセッションファイルを処理して各種Table用の配列を作成
						for ( $iii = $nowloop; $iii < $thisloopmax; $iii++ ) {
							$yday_session_file = $yday_session_files[ $iii ];
							$yday_filename_str = $yday_session_file['name'];
							$yday_file_ary     = $this->wrap_unserialize( $this->wrap_get_contents( $readersfinish_dir . $yday_filename_str ) );
							
							// set variables
							$qa_id = substr( $yday_filename_str, 0, 28 );
							preg_match( '/_([0-9]*).php/', $yday_filename_str, $matches );
							$session_no  = $matches[1];
							$tracking_id = $yday_file_ary['head']['tracking_id'];
							$device      = $yday_file_ary['head']['device_name'];
							$device_code = 0;
							$user_agent  = $yday_file_ary['head']['user_agent'];
							$referer     = $yday_file_ary['head']['first_referrer'];
							$source      = $yday_file_ary['head']['utm_source'];
							$media       = $yday_file_ary['head']['utm_medium'];
							$campaign    = mb_substr( urldecode( $yday_file_ary['head']['utm_campaign'] ), 0, 127 );
							$utm_term    = mb_substr( urldecode( $yday_file_ary['head']['utm_term'] ), 0, 255 );
							$original_id = $yday_file_ary['head']['original_id'];
							$is_new_user = $yday_file_ary['head']['is_new_user'];

							/*
							$yday_file_lines   = $wp_filesystem->get_contents_array( $readersfinish_dir . $yday_filename_str );
							$yday_first_line   = $yday_file_lines[1];
							$yday_first_ary    = explode( "\t", $yday_first_line );

							// set variables
							$qa_id = substr( $yday_filename_str, 0, 28 );
							preg_match( '/_([0-9]*)/', $yday_filename_str, $matches );
							$session_no  = $matches[1];
							$tracking_id = $yday_first_ary[ self::DATA_SESSION_FINISH_1['TRACKING_ID'] ];
							$device      = $yday_first_ary[ self::DATA_SESSION_FINISH_1['DEVICE_NAME'] ];
							$device_code = 0;
							$user_agent  = $yday_first_ary[ self::DATA_SESSION_FINISH_1['USER_AGENT'] ];
							$referer     = $yday_first_ary[ self::DATA_SESSION_FINISH_1['FIRST_REFERRER'] ];
							$source      = $yday_first_ary[ self::DATA_SESSION_FINISH_1['UTM_SOURCE'] ];
							$media       = $yday_first_ary[ self::DATA_SESSION_FINISH_1['UTM_MEDIUM'] ];
							$campaign    = mb_substr( urldecode( $yday_first_ary[ self::DATA_SESSION_FINISH_1['UTM_CAMPAIGN'] ] ), 0, 127 );
							$utm_term    = mb_substr( urldecode( $yday_first_ary[ self::DATA_SESSION_FINISH_1['UTM_TERM'] ] ), 0, 255 );
							$original_id = $yday_first_ary[ self::DATA_SESSION_FINISH_1['ORIGINAL_ID'] ];
							$is_new_user = $yday_first_ary[ self::DATA_SESSION_FINISH_1['IS_NEW_USER'] ];
							*/

							$device_code = QAHM_DEVICES['desktop']['id'];
							foreach ( QAHM_DEVICES as $qahm_dev ) {
								if ( $device === $qahm_dev['name'] ) {
									$device_code = $qahm_dev['id'];
									break;
								}
							}

							// each array add , more faster than array_push
							$device_os = $this->os_from_ua( $user_agent );
							$browser   = $this->browser_from_ua( $user_agent );
							if ( ! empty( $qa_id ) ) {
								$readers_ary[] = array( $qa_id, $original_id, $device_os, $browser );
							}
							if ( ! empty( $media ) ) {
								$media_ary[] = $media;
							}
							$source_domain = '';
							if ( ! empty( $referer ) ) {
								//20220415 add all referer strings are must be lower.
								$referer = mb_strtolower( $referer );

								if ( $referer == 'direct') {
									$source_domain = 'direct';
								} else {
									$parse_url     = wp_parse_url( $referer );
									if ($parse_url['host']) {
										$ref_host      = $parse_url['host'];
										$source_domain = $ref_host;
									}
									if ( isset($parse_url['query']) ) {
										$param_url = $parse_url['query'];
										$newref        = $referer;
										parse_str( $param_url, $param_ary );
										foreach ( $param_ary as $key => $param ) {
											if ( self::URL_PARAMETER_MAX < mb_strlen( $param ) ) {
												$orgparam   = urlencode( $param );
												$shortparam = substr( $orgparam, 0, self::URL_PARAMETER_MAX );
												$newref = str_replace( $orgparam, $shortparam, $newref);
											}
										}
										$referer = $newref;
									}
								}
							}
							$sources_ary[] = array( $source, $referer, $source_domain, $media, $utm_term );

							if ( ! empty( $campaign ) ) {
								$campaigns_ary[] = $campaign;
							}
							// PVをチェック。PV関連の配列を作る
							$pvline_max = count( $yday_file_ary['body'] );
							for ( $jjj = 0; $jjj < $pvline_max; $jjj++ ) {
								// Detect pv
								$pvline_ary   = $yday_file_ary['body'][$jjj];
								$page_id      = $pvline_ary['page_id'];
								$type         = $pvline_ary['page_type'];
								$lp_time      = $pvline_ary['access_time'];
								$page_url     = $pvline_ary['page_url'];
								$page_title   = mb_substr( $pvline_ary['page_title'], 0, 64 );
								$page_speed   = $pvline_ary['page_speed'];
								$time_on_page = $pvline_ary['sec_on_page'];

								// site search?
								if ( ! empty( $page_url ) ) {
									$wp_serch_chk = self::WP_SEARCH_PERM;
									$page_url_ary = explode( $wp_serch_chk, $page_url );
									$separate_cnt = count( $page_url_ary );
									if ( $separate_cnt > 1 ) {
										$page_url     = $page_url_ary[0] . $wp_serch_chk;
										$page_title   = 'WP Site Search Result';
										$wp_s_keyword = mb_substr( urldecode( $page_url_ary[1] ), 0, 127 );
										$wp_s_ary[]   = array( $qa_id, $lp_time, $wp_s_keyword );
									}

									//20220415 add all page_url strings are must be lower.
									$page_url = mb_strtolower( $page_url );

								}
								$url_hash = hash( 'fnv164', $page_url );

								$is_last = 0;
								if ( $jjj === $pvline_max - 1 ) {
									$is_last = 1;
								}
								if ( ! empty( $page_url ) ) {
									$pages_ary[] = array( $tracking_id, $type, $page_id, $page_url, $url_hash, $page_title );
								}
								if ( ! empty( $qa_id ) ) {
									$pv_num    = $jjj + 1;
									$islast    = (string) $is_last;
									$isnewuser = (string) $is_new_user;
									$pv_ary[]  = array( $qa_id, $url_hash, $page_url, $device_code, $source, $referer, $source_domain, $media, $campaign, $utm_term, $session_no, $lp_time, $pv_num, $page_speed, $time_on_page, $islast, $isnewuser );
								}
							}
						}

						// 作成した配列をファイルに書き出して終了。ユニークチェック処理は行わない。セパレートした数百行程度ではあまり発生しないし、DBにやらせた方が速そうなため
						if ( ! empty( $dbin_session_files ) ) {
							$this->write_ary_to_temp( $dbin_session_files, $dbin_session_file );
						}
						if ( ! empty( $readers_ary ) ) {
							$this->write_ary_to_temp( $readers_ary, $ary_readers_file );
						}
						if ( ! empty( $media_ary ) ) {
							$this->write_ary_to_temp( $media_ary, $ary_media_file );
						}
						if ( ! empty( $sources_ary ) ) {
							$this->write_ary_to_temp( $sources_ary, $ary_sources_file );
						}
						if ( ! empty( $campaigns_ary ) ) {
							$this->write_ary_to_temp( $campaigns_ary, $ary_campaigns_file );
						}
						if ( ! empty( $pages_ary ) ) {
							$this->write_ary_to_temp( $pages_ary, $ary_pages_file );
						}
						if ( ! empty( $pv_ary ) ) {
							$this->write_ary_to_temp( $pv_ary, $ary_pv_file );
						}
						if ( ! empty( $wp_s_ary ) ) {
							$this->write_ary_to_temp( $wp_s_ary, $ary_wp_s_file );
						}
						// ---next
						$cron_status = 'Night>Create yesterday data>Loop>Insert>Readers';
						$this->set_next_status( $cron_status );
					} else {
						// ---no data. end
						$cron_status = 'Night>Delete>Start';
						$this->set_next_status( $cron_status );
					}
					break;

				case 'Night>Create yesterday data>Loop>Insert>Readers':
					$this->backup_prev_status( $cron_status );

					// insert readers (if Dupplicate,then error)
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Media';
					$nowstr   = $qahm_time->now_str();
					$data_ary = $wp_filesystem->get_contents_array( $ary_readers_file );
					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// qa_idのユニークはDB側で保証
						$is_first_line = true;
						foreach ( $data_ary as $line ) {
							if ( ! $is_first_line ) {
								// インサートするデータを格納
								$line        = str_replace( PHP_EOL, '', $line );
								$data        = explode( "\t", $line );
								$qa_id       = $data[0];
								$original_id = $data[1];
								$device_os   = $data[2];
								$browser     = $data[3];

								// プレースホルダーの作成
								$arrayValues[]   = $qa_id;
								$arrayValues[]   = $original_id;
								$arrayValues[]   = $device_os;
								$arrayValues[]   = $browser;
								$arrayValues[]   = $nowstr;
								$place_holders[] = '(%s, %s, %s, %s, %s)';
							} else {
								$is_first_line = false;
							}
						}

						if ( ! empty( $arrayValues ) ) {
							// SQLの生成
							$table_name = $wpdb->prefix . 'qa_readers';
							$sql        = 'INSERT INTO ' . $table_name . ' ' .
									'(qa_id, original_id, UAos, UAbrowser, update_date) ' .
									'VALUES ' . join( ',', $place_holders ) . ' ' .
									'ON DUPLICATE KEY UPDATE ' .
									'original_id = VALUES(original_id), update_date = CURDATE()';
							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
								// ---next
								$cron_status = 'Night>Sql error';
							}
						}
					}
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Media':
					// insert readers (if Dupplicate,then error)
					$data_ary = $wp_filesystem->get_contents_array( $ary_media_file );
					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// mediaのユニークはDB側で保証
						$is_first_line = true;
						foreach ( $data_ary as $line ) {
							if ( ! $is_first_line ) {
								// インサートするデータを格納
								$line   = str_replace( PHP_EOL, '', $line );
								$data   = explode( "\t", $line );
								$medium = $data[0]; // media
								// プレースホルダーの作成
								if ( ! empty( $medium ) ) {
									$arrayValues[]   = $medium;
									$place_holders[] = '(%s)';
								}
							} else {
								$is_first_line = false;
							}
						}
						if ( ! empty( $arrayValues ) ) {
							$table_name = $wpdb->prefix . 'qa_utm_media';
							$sql        = 'INSERT IGNORE INTO ' . $table_name . ' ' .
								'(utm_medium) ' .
								'VALUES ' . join( ',', $place_holders );
							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
							}
						}
						// SQLの生成
					}
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Sources';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Sources':
					$this->backup_prev_status( $cron_status );

					// insert utm_sources (If dupulicate,then nothing do)
					$data_ary = $wp_filesystem->get_contents_array( $ary_sources_file );

					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// 重複を削除
						$newdata_ary = array();
						$maxcnt      = count( $data_ary );
						for ( $iii = self::TEMP_BODY_ELM_NO; $iii < $maxcnt - 1; $iii++ ) {
							$dataline = str_replace( PHP_EOL, '', $data_ary[ $iii ] );
							$is_uniq  = true;
							for ( $jjj = $iii + 1; $jjj < $maxcnt; $jjj ++ ) {
								$newdata_line = str_replace( PHP_EOL, '', $data_ary[ $jjj ] );
								if ( $dataline == $newdata_line ) {
									$is_uniq = false;
								}
							}
							if ( $is_uniq ) {
								$newdata_ary[] = $dataline;
							}
						}
						$newdata_ary[] = str_replace( PHP_EOL, '', $data_ary[ $maxcnt - 1 ] );
						// $newdata_aryは重複を除いたインサートするデータ配列が入っている
						foreach ( $newdata_ary as $line ) {
							$data = explode( "\t", $line );
							// インサートするデータを格納
							$source        = $data[0];
							$referer       = $data[1];
							$source_domain = $data[2];
							$medium        = $data[3];
							$utm_term      = $data[4];
							$keyword       = '';

							// uniq check
							$is_uniq_source = true;
							$medium_id      = 0;

							// If medium is not null, This is original medium. Check midium_id.
							if ( ! empty( $medium ) ) {
								$table_name = $wpdb->prefix . 'qa_utm_media';
								$query      = 'SELECT medium_id FROM ' . $table_name . ' WHERE utm_medium = %s';
								//$preobj     = $wpdb->prepare( $query, $medium );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $medium ) );
								$medium_id  = $result[0]->medium_id;
							}

							// first source check
							if ( empty( $source ) ) {
								// 1st search engine check
								foreach ( SEARCH_ENGINES as $se ) {
									if ( $source_domain == $se['DOMAIN'] ) {
										if ( $se['SOURCE_ID'] > 0 ) {
											$is_uniq_source = false;
											break;
										} else {
											// other search engine. check keyword
											$parse_ref  = wp_parse_url( $referer );
											if ( array_key_exists( 'query', $parse_ref ) ) {
												$query_perm = $parse_ref['query'];
												$keyword_perm_ary = explode( ',', $se['QUERY_PERM'] );
												if ( $keyword_perm_ary ) {
													$perm_ary         = array();
													parse_str( $query_perm, $perm_ary );
													foreach ( $keyword_perm_ary as $keyword ) {
														if ( ! empty( $perm_ary[ $keyword ] ) ) {
															$source    = $se['NAME'];
															$keyword   = urldecode( $perm_ary[ $keyword ] );
															$medium_id = UTM_MEDIUM_ID['ORGANIC']; // organic
															break 2;

														}
													}
												}

											} elseif( $se['NOT_PROVIDED'] == 1 ) {
												if ( preg_match('/'. preg_quote( $se['DOMAIN'] ) . '.$/', $referer ) ) {
													//no queryの検索エンジン
													$source = $se[ 'NAME' ];
													$keyword = '';
													$medium_id = UTM_MEDIUM_ID[ 'ORGANIC' ]; // organic
													break;
												}
											}
										}
									}
								}
								// 2nd social check
								foreach ( SOCIAL_DOMAIN as $social ) {
									if ( $source_domain == $social['DOMAIN'] ) {
										if ( $social['SOURCE_ID'] > 0 ) {
											$is_uniq_source = false;
											break;
										}
									}
								}
							} else {
								// 3rd GCLID check
								foreach ( GCLID as $gclid ) {
									if ( $source_domain == $gclid['DOMAIN'] ) {
										if ( $medium_id == UTM_MEDIUM_ID['GCLID'] ) {
											if ( empty( $utm_term ) ) {
												$is_uniq_source = false;
											}
											break;
										}
									}
								}
							}

							// Is this really unique source ?
							// 1st db
							if ( $is_uniq_source ) {
								$table_name = $wpdb->prefix . 'qa_utm_sources';
								$query      = 'SELECT source_id FROM ' . $table_name . ' WHERE source_domain= %s';
								//$preobj     = $wpdb->prepare( $query, $source_domain );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$resids     = $wpdb->get_results( $wpdb->prepare( $query, $source_domain ) );
								foreach ( $resids as $ids ) {
									$query      = 'SELECT utm_source,referer,medium_id,utm_term FROM ' . $table_name . ' WHERE source_id= %d';
									//$preobj     = $wpdb->prepare( $query, $ids->source_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$res        = $wpdb->get_results( $wpdb->prepare( $query, $ids->source_id ) );
									foreach ( $res as $raw ) {
										if ($raw->utm_source == $source && $raw->medium_id == $medium_id) {
											if ($raw->utm_term == $utm_term && $raw->referer == $referer) {
												$is_uniq_source = false;
											}
										}
									}
								}

//									$table_name = $wpdb->prefix . 'qa_utm_sources';
//									$query      = 'SELECT utm_source,referer,medium_id,utm_term FROM ' . $table_name . ' WHERE source_domain= %s';
//									$preobj     = $wpdb->prepare( $query, $source_domain );
//									$result     = $wpdb->get_results( $preobj );
//									foreach ( $result as $raw ) {
//										if ( $raw->utm_source == $source && $raw->medium_id == $medium_id ) {
//											if ( $raw->utm_term == $utm_term && $raw->referer == $referer ) {
//												$is_uniq_source = false;
//											}
//										}
//									}
							}
							// 2nd array

							// OK、insert raw
							if ( $is_uniq_source ) {
								$arrayValues[] = $source;
								$arrayValues[] = $referer;
								$arrayValues[] = $source_domain;
								$arrayValues[] = $medium_id;
								$arrayValues[] = $utm_term;
								$arrayValues[] = mb_substr( $keyword, 0, 255 );

								// プレースホルダーの作成
								$place_holders[] = '(%s, %s, %s, %d, %s, %s)';
							}
						}

						if ( ! empty( $arrayValues ) ) {
							// SQLの生成
							$table_name = $wpdb->prefix . 'qa_utm_sources';
							$sql        = 'INSERT INTO ' . $table_name . ' ' .
								'(utm_source, referer, source_domain, medium_id, utm_term, keyword) ' .
								'VALUES ' . join( ',', $place_holders );
							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
							}
						}
					}
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Campaigns';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Campaigns':
					$this->backup_prev_status( $cron_status );

					// insert utm_campaigns (all recored innsert)
					$data_ary = $wp_filesystem->get_contents_array( $ary_campaigns_file );
					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// campaignのユニークはDB側で保証
						$is_first_line = true;
						foreach ( $data_ary as $line ) {
							if ( ! $is_first_line ) {
								$line = str_replace( PHP_EOL, '', $line );
								$data = explode( "\t", $line );
								// インサートするデータを格納
								$campaign      = $data[0];
								$arrayValues[] = $campaign;

								// プレースホルダーの作成
								$place_holders[] = '(%s)';
							} else {
								$is_first_line = false;
							}
						}
						if ( ! empty( $arrayValues ) ) {
							// SQLの生成
							$table_name = $wpdb->prefix . 'qa_utm_campaigns';
							$sql        = 'INSERT IGNORE INTO ' . $table_name . ' ' .
								'(utm_campaign) ' .
								'VALUES ' . join( ',', $place_holders );
							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
							}
						}
					}
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Pages';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Pages':
					$this->backup_prev_status( $cron_status );

					// insert pages (if Dupplicate,then update or nothing do)
					$data_ary = $wp_filesystem->get_contents_array( $ary_pages_file );
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Pv_log';
					if ( ! empty( $data_ary ) ) {
						$today_str = $qahm_time->today_str();
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// もし入っていない場合はデフォルト値を挿入

						// url uniq check in array
						$uniq_ary     = array();
						$data_ary_max = count( $data_ary );
						for ( $iii = self::TEMP_BODY_ELM_NO; $iii < $data_ary_max; $iii++ ) {
							$line = $data_ary[ $iii ];
							$line = str_replace( PHP_EOL, '', $line );
							$data = explode( "\t", $line );
							// インサートするデータを格納

							$page_url = $data[3];
							$url_hash = $data[4];

							// url uniq check in array
							if ( ! empty( $url_hash ) ) {
								// last
								if ( $iii == $data_ary_max - 1 ) {
									$uniq_ary[] = $line;
								} else {
									$is_url_uniq = true;
									$jjj         = $iii + 1;
									while ( $is_url_uniq ) {
										$cmpline     = $data_ary[ $jjj ];
										$cmpline     = str_replace( PHP_EOL, '', $cmpline );
										$cmpdata     = explode( "\t", $cmpline );
										$cmppage_url = $cmpdata[3];
										$cmpurl_hash = $cmpdata[4];

										if ( $url_hash == $cmpurl_hash ) {
											if ( $page_url == $cmppage_url ) {
												$is_url_uniq = false;
											}
										}
										++$jjj;
										// last
										if ( $jjj == $data_ary_max ) {
												break;
										}
									}
									if ( $is_url_uniq ) {
										$uniq_ary[] = $line;
									}
								}
							}
						}

						// make insert data
						foreach ( $uniq_ary as $uniqline ) {
							$uniqdata = explode( "\t", $uniqline );
							// インサートするデータを格納

							$tracking_id = $uniqdata[0];
							$type        = $uniqdata[1];
							$id          = $uniqdata[2];
							$page_url    = $uniqdata[3];
							$url_hash    = $uniqdata[4];
							$page_title  = $uniqdata[5];
							$page_title  = mb_substr( $page_title, 0, 128 );

							// url uniq check in table
							$is_url_uniq     = true;
							$update_page_id  = 0;
							if ( ! empty( $url_hash ) && ! empty( $type ) && ! empty( $id ) ) {
								$table_name = $wpdb->prefix . 'qa_pages';
								$query      = 'SELECT page_id,url,url_hash,title FROM ' . $table_name . ' WHERE url_hash = %s';
								//$preobj     = $wpdb->prepare( $query, $url_hash );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $url_hash ) );
								if ( ! empty( $result ) ) {
									foreach ( $result as $raw ) {
										if ( $page_url == $raw->url ) {
											$is_url_uniq = false;
											if ( $page_title !== $raw->title ) {
												$update_page_id = $raw->page_id;
											}
										}
									}
								}
							}
							// make insert array
							if ( $is_url_uniq ) {
								$arrayValues[] = $tracking_id;
								$arrayValues[] = $type;
								$arrayValues[] = $id;
								$arrayValues[] = $page_url;
								$arrayValues[] = $url_hash;
								$arrayValues[] = $page_title;
								$arrayValues[] = $today_str;

								// プレースホルダーの作成
								$place_holders[] = '(%s, %s, %d, %s, %s, %s, %s)';
							}
							// title update now
							if ( $update_page_id !== 0 ) {
								$table_name = $wpdb->prefix . 'qa_pages';
								$query      = 'UPDATE ' . $table_name . ' set wp_qa_type = %s, wp_qa_id = %s, title = %s, update_date = CURDATE() WHERE page_id = %d';
								//$preobj     = $wpdb->prepare( $query, $type, $id, $page_title, $update_page_id );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $type, $id, $page_title, $update_page_id ) );
							}
						}
						if ( ! empty( $arrayValues ) ) {
							$table_name = $wpdb->prefix . 'qa_pages';
							// SQLの生成
							$sql = 'INSERT INTO ' . $table_name . ' ' .
								'(tracking_id, wp_qa_type, wp_qa_id, url, url_hash, title, update_date) ' .
								'VALUES ' . join( ',', $place_holders );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
								// ---next
								$cron_status = 'Night>Sql error';
							}
						}
					}
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Pv_log':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>Insert>Search_log';

					// insert pv_log (All recored insert)
					$data_ary = $wp_filesystem->get_contents_array( $ary_pv_file );
					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// もし入っていない場合はデフォルト値を挿入
						$is_first_line = true;
						foreach ( $data_ary as $line ) {
							if ( ! $is_first_line ) {
								$line = str_replace( PHP_EOL, '', $line );
								$data = explode( "\t", $line );
								// インサートするデータを格納

								$qa_id         = $data[0];
								$url_hash      = $data[1];
								$page_url      = $data[2];
								$device_code   = $data[3];
								$source        = $data[4];
								$referer       = $data[5];
								$source_domain = $data[6];
								$medium        = $data[7];
								$campaign      = $data[8];
								$utm_term      = $data[9];
								$session_no    = $data[10];
								$lp_time       = $data[11];
								$now_pv        = $data[12];
								$page_speed    = $data[13];
								$time_on_page  = $data[14];
								$is_last       = $data[15];
								$is_new_user   = $data[16];

								// each id check

								$reader_id = 0;
								if ( ! empty( $qa_id ) ) {
									$table_name = $wpdb->prefix . 'qa_readers';
									$query      = 'SELECT reader_id FROM ' . $table_name . ' WHERE qa_id=%s';
									//$preobj     = $wpdb->prepare( $query, $qa_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $qa_id ) );
									if ( ! empty( $result ) ) {
										$reader_id = $result[0]->reader_id;
									}
								}

								$page_id = 0;
								if ( ! empty( $url_hash ) ) {
									$table_name = $wpdb->prefix . 'qa_pages';
									$query      = 'SELECT page_id,url FROM ' . $table_name . ' WHERE url_hash=%s';
									//$preobj     = $wpdb->prepare( $query, $url_hash );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $url_hash ) );
									if ( ! empty( $result ) ) {
										foreach ( $result as $raw ) {
											if ( $page_url == $raw->url ) {
												$page_id = $raw->page_id;
											}
										}
									}
								}

								$medium_id = 0;

								// check medium_id
								if ( ! empty( $medium ) ) {
									$table_name = $wpdb->prefix . 'qa_utm_media';
									$query      = 'SELECT medium_id FROM ' . $table_name . ' WHERE utm_medium=%s';
									//$preobj     = $wpdb->prepare( $query, $medium );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $medium ) );
									foreach ( $result as $raw ) {
										$medium_id = $raw->medium_id;
									}
								}

								$source_id = 0;
								// first source_id check
								if ( empty( $source ) ) {
									// 1st search engine check
									foreach ( SEARCH_ENGINES as $se ) {
										if ( $source_domain == $se['DOMAIN'] ) {
											$source    = $se['NAME'];
											$medium_id = UTM_MEDIUM_ID['ORGANIC'];
											if ( $se['SOURCE_ID'] > 0 ) {
												$source_id = $se['SOURCE_ID'];
												break;
											}
										}
									}
									if ( $source_id == 0 ) {
										// 2nd social check
										foreach ( SOCIAL_DOMAIN as $social ) {
											if ( $source_domain == $social['DOMAIN'] ) {
												$medium_id = UTM_MEDIUM_ID['SOCIAL'];
												if ( $social['SOURCE_ID'] > 0 ) {
													$source_id = $social['SOURCE_ID'];
													break;
												}
											}
										}
									}
								} else {
									// 3rd only GLCID check
									foreach ( GCLID as $gclid ) {
										if ( $source_domain == $gclid['DOMAIN'] ) {
											if ( $medium_id == UTM_MEDIUM_ID['GCLID'] ) {
												if ( empty( $utm_term ) ) {
													$source_id = $gclid['SOURCE_ID'];
													break;
												}
											}
										}
									}
								}

								if ( $source_id == 0 ) {
									$table_name = $wpdb->prefix . 'qa_utm_sources';
									$query      = 'SELECT source_id,utm_source,referer,medium_id,utm_term,keyword FROM ' . $table_name . ' WHERE source_domain= %s';
									//$preobj     = $wpdb->prepare( $query, $source_domain );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $source_domain ) );
									foreach ( $result as $raw ) {
										if ( $raw->utm_source == $source && $raw->medium_id == $medium_id ) {
											if ( $raw->utm_term == $utm_term && $raw->referer == $referer ) {
													$source_id = $raw->source_id;
											}
										}
									}
								}

								$campaign_id = 0;
								if ( ! empty( $campaign ) ) {
									$table_name = $wpdb->prefix . 'qa_utm_campaigns';
									$query      = 'SELECT campaign_id FROM ' . $table_name . ' WHERE utm_campaign = %s';
									//$preobj     = $wpdb->prepare( $query, $campaign );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $campaign ) );
									if ( ! empty( $result ) ) {
										$campaign_id = $result[0]->campaign_id;
									}
								}

								if ( $page_id > 0 ) {
									// make insert array
									$arrayValues[] = (int) $reader_id;
									$arrayValues[] = (int) $page_id;
									$arrayValues[] = (int) $device_code;
									$arrayValues[] = (int) $source_id;
									$arrayValues[] = (int) $medium_id;
									$arrayValues[] = (int) $campaign_id;
									$arrayValues[] = (int) $session_no;
									$arrayValues[] = $qahm_time->unixtime_to_str( $lp_time );
									$arrayValues[] = (int) $now_pv;
									$arrayValues[] = (int) $page_speed;
									$arrayValues[] = (int) $time_on_page;
									$arrayValues[] = (int) $is_last;
									$arrayValues[] = (int) $is_new_user;

									// プレースホルダーの作成
									$place_holders[] = '(%d, %d, %d, %d, %d, %d, %d, %s, %d, %d, %d, %d, %d)';
								}
							} else {
								$is_first_line = false;
							}
						}
						if ( ! empty( $arrayValues ) ) {
							// SQLの生成
							$table_name = $wpdb->prefix . 'qa_pv_log';
							$sql        = 'INSERT IGNORE INTO ' . $table_name . ' ' .
								'(reader_id, page_id, device_id, source_id, medium_id, campaign_id, session_no, access_time, pv, speed_msec, browse_sec, is_last, is_newuser) ' .
								'VALUES ' . join( ',', $place_holders );

							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );



							if ( $result > 0 ) {
								$cnt           = 0;
								if ( $wp_filesystem->exists( $yday_pvmaxcnt_file ) ) {
									$cnt = $wp_filesystem->get_contents( $yday_pvmaxcnt_file );
								}
								$yday_pvmaxcnt = $result + (int) $cnt;
								$wp_filesystem->put_contents( $yday_pvmaxcnt_file, $yday_pvmaxcnt );

							}

							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
								// ---next
								$cron_status = 'Night>Sql error';
							}
						}
					}
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>Insert>Search_log':
					$this->backup_prev_status( $cron_status );

					// insert search log (数が少ないし、where句必須なので一行ずつインサート)
					$data_ary = $wp_filesystem->get_contents_array( $ary_wp_s_file );
					if ( ! empty( $data_ary ) ) {
						// プレースホルダーとインサートするデータ配列
						$arrayValues   = array();
						$place_holders = array();

						// $data_aryはインサートするデータ配列が入っている
						// もし入っていない場合はデフォルト値を挿入
						$is_first_line = true;
						foreach ( $data_ary as $line ) {
							if ( ! $is_first_line ) {
								$line = str_replace( PHP_EOL, '', $line );
								$data = explode( "\t", $line );
								// データを取り出し
								$qa_id        = $data[0];
								$lp_time      = $data[1];
								$wp_s_keyword = $data[2];

								$reader_id = 0;
								// get readers id.
								if ( ! empty( $qa_id ) ) {
									$table_name = $wpdb->prefix . 'qa_readers';
									$query      = 'SELECT reader_id FROM ' . $table_name . ' WHERE qa_id = %s';
									//$preobj     = $wpdb->prepare( $query, $qa_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $qa_id ) );
									$reader_id  = $result[0]->reader_id;
								}

								$pv_id = 0;
								// get readers id.
								if ( ! empty( $lp_time ) && ! empty( $reader_id ) ) {
									$search_time = $qahm_time->unixtime_to_str( $lp_time );
									$table_name  = $wpdb->prefix . 'qa_pv_log';
									$query       = 'SELECT pv_id FROM ' . $table_name . ' WHERE access_time = %s AND reader_id = %d';
									//$preobj      = $wpdb->prepare( $query, $search_time, $reader_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result      = $wpdb->get_results( $wpdb->prepare( $query, $search_time, $reader_id ) );
									$pv_id       = $result[0]->pv_id;
								}

								if ( ! empty( $pv_id ) && ! empty( $wp_s_keyword ) ) {
									// make insert array
									$arrayValues[] = (int) $pv_id;
									$arrayValues[] = (string) $wp_s_keyword;
									// プレースホルダーの作成
									$place_holders[] = '(%d, %s)';
								}
							} else {
								$is_first_line = false;
							}
						}
						if ( ! empty( $arrayValues ) ) {
							// SQLの生成
							$table_name = $wpdb->prefix . 'qa_search_log';
							$sql        = 'INSERT INTO ' . $table_name . ' ' .
								'(pv_id, query) ' .
								'VALUES ' . join( ',', $place_holders );

							// SQL実行
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result = $wpdb->query( $wpdb->prepare( $sql, $arrayValues ) );
							if ($result === false && $wpdb->last_error !== '') {
								$qahm_log->error( $wpdb->print_error() );
							}
						}
					}
					// ---next
					$cron_status = 'Night>Create yesterday data>Loop>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Create yesterday data>Loop>End':
					$this->backup_prev_status( $cron_status );

					$loopstart = 0;
					if ( $wp_filesystem->exists( $yday_loopcount_file ) ) {
						$loopstart_ary = $wp_filesystem->get_contents_array( $yday_loopcount_file );
						$is_loop_end   = '';
						if ( count( $loopstart_ary ) >= 2 ) {
							$is_loop_end = $loopstart_ary[1];
						}

						if ( $is_loop_end == self::LOOPLAST_MSG ) {
							$cron_status = 'Night>Create yesterday data>End';
						} else {
							$nowloop = (int) $loopstart_ary[0] + self::PV_LOOP_MAX;
							$wp_filesystem->put_contents( $yday_loopcount_file, $nowloop );
							$cron_status = 'Night>Create yesterday data>Loop>Start';
						}
					} else {
						// 　ファイルがない。異常終了
						$cron_status = 'Night>End';
						throw new Exception( 'cronステータスファイルの生成に失敗しました。終了します。' );
					}
					// ---next
					$errmsg = $this->set_next_status( $cron_status );

					// loop exit
					$while_continue = false;
					break;

				case 'Night>Create yesterday data>End':
					$this->backup_prev_status( $cron_status );

					//dbinに移動する
					if ( $wp_filesystem->exists( $dbin_session_file ) ) {
						$dbin_ary       = $wp_filesystem->get_contents_array( $dbin_session_file );
						$is_first_line = true;
						foreach ( $dbin_ary as $del ) {
							if ( ! $is_first_line ) {
								$del = trim( $del );
								$filename = basename( $del );
								$contents = $wp_filesystem->get_contents ( $del );
								$putfile = $readersdbin_dir . $filename;
								$putfile = trim($putfile);
								$wp_filesystem->put_contents( $putfile, $contents );
								$wp_filesystem->delete( $del );
							} else {
								$is_first_line = false;
							}
						}
						if ( QAHM_DEBUG <= QAHM_DEBUG_LEVEL['staging'] ) {
							$wp_filesystem->delete( $dbin_session_file );
						}
					}

					if ( QAHM_DEBUG <= QAHM_DEBUG_LEVEL['staging'] ) {
						// delete temp files
						if ( $wp_filesystem->exists( $yday_loopcount_file ) ) {
							$wp_filesystem->delete( $yday_loopcount_file );
						}
						if ( $wp_filesystem->exists( $ary_readers_file ) ) {
							$wp_filesystem->delete( $ary_readers_file );
						}
						if ( $wp_filesystem->exists( $ary_pages_file ) ) {
							$wp_filesystem->delete( $ary_pages_file );
						}
						if ( $wp_filesystem->exists( $ary_media_file ) ) {
							$wp_filesystem->delete( $ary_media_file );
						}
						if ( $wp_filesystem->exists( $ary_sources_file ) ) {
							$wp_filesystem->delete( $ary_sources_file );
						}
						if ( $wp_filesystem->exists( $ary_campaigns_file ) ) {
							$wp_filesystem->delete( $ary_campaigns_file );
						}
						if ( $wp_filesystem->exists( $ary_pv_file ) ) {
							$wp_filesystem->delete( $ary_pv_file );
						}
						if ( $wp_filesystem->exists( $ary_wp_s_file ) ) {
							$wp_filesystem->delete( $ary_wp_s_file );
						}
						if ( $wp_filesystem->exists( $now_pv_loop_maxfile ) ) {
							$wp_filesystem->delete( $now_pv_loop_maxfile );
						}
						if ( $wp_filesystem->exists( $now_pvlog_count_fetchfile ) ) {
							$wp_filesystem->delete( $now_pvlog_count_fetchfile );
						}
					}
					// ---next
					$cron_status = 'Night>Insert raw data>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Insert raw
				// ----------
				case 'Night>Insert raw data>Start':
					$this->backup_prev_status( $cron_status );

					// 1st initial loop count and delete file list.
					$loopstart = 0;
					$wp_filesystem->put_contents( $raw_loopcount_file, $loopstart );
					$wp_filesystem->put_contents( $now_raw_loop_maxfile, self::RAW_LOOP_MAX );
					$yesterday_str = $qahm_time->xday_str( -1 );
					$yesterday_end = $qahm_time->str_to_unixtime( $yesterday_str . ' 23:59:59' );

					if ( $wp_filesystem->exists( $del_rawfileslist_file . '_old1.php' ) ) {
						// 異常終了じゃない=昨日作成
						$make_time = $wp_filesystem->mtime( $del_rawfileslist_file . '_old1.php' );
						if ( $make_time <= $yesterday_end ) {
							$temp_str = $wp_filesystem->get_contents( $del_rawfileslist_file . '_old1.php' );
							$wp_filesystem->put_contents( $del_rawfileslist_file . '_old2.php', $temp_str );
						}
					}
					if ( $wp_filesystem->exists( $del_rawfileslist_file ) ) {
						// 異常終了じゃない=昨日作成
						$make_time = $wp_filesystem->mtime( $del_rawfileslist_file );
						if ( $make_time <= $yesterday_end ) {
							$temp_str = $wp_filesystem->get_contents( $del_rawfileslist_file );
							$wp_filesystem->put_contents( $del_rawfileslist_file . '_old1.php', $temp_str );
						}
					}
					$wp_filesystem->put_contents( $del_rawfileslist_file, '' );

					// ---next
					$cron_status = 'Night>Insert raw data>Check pv';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Insert raw data>Check pv':
					$this->backup_prev_status( $cron_status );

					// get all new pv_ids
					$yday_pvmaxcnt = (int) $wp_filesystem->get_contents( $yday_pvmaxcnt_file );
					$table_name    = $wpdb->prefix . 'qa_pv_log';
					$query         = 'SELECT pv_id, reader_id, page_id, device_id, session_no, access_time FROM ' . $table_name . ' ORDER BY pv_id DESC limit %d';
					//$preobj        = $wpdb->prepare( $query, $yday_pvmaxcnt );
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$result        = $wpdb->get_results( $wpdb->prepare( $query, $yday_pvmaxcnt ) );
					if ( ! empty( $result ) ) {
						// 古い順番に反転させ、セッション番号とPV番号を挿入する。
						$newpv_ary = array();
						$max_no    = count( $result ) - 1;
						for ( $iii = $max_no; $iii >= 0; $iii-- ) {
							$pv_id      = $result[ $iii ]->pv_id;
							$reader_id  = $result[ $iii ]->reader_id;
							$page_id    = $result[ $iii ]->page_id;
							$device_no  = $result[ $iii ]->device_id;
							$session_no = $result[ $iii ]->session_no;
							$access_time = $result[ $iii ]->access_time;

							// default pv_no = 1. check pv_no
							$pv_no = 1;
							if ( $iii < $max_no ) {
								$jjj             = $iii + 1;
								$reader_id_prev  = $result[ $jjj ]->reader_id;
								$session_no_prev = $result[ $jjj ]->session_no;
								while ( $reader_id_prev == $reader_id && $session_no_prev == $session_no ) {
									$page_id_prev = $result[ $jjj ]->page_id;
									if ( $page_id_prev == $page_id ) {
										$pv_no++;
									}
									$jjj++;
									if ( $jjj > $max_no ) {
										break;
									}
									$reader_id_prev  = $result[ $jjj ]->reader_id;
									$session_no_prev = $result[ $jjj ]->session_no;
								}
							}
							$newpv_ary[] = array( $pv_id, $reader_id, $page_id, $device_no, $session_no, $pv_no, $access_time );
						}
						$this->write_ary_to_temp( $newpv_ary, $ary_new_pvrows_file );
						// next
						$cron_status = 'Night>Insert raw data>Loop>Start';
					} else {
						// no data next
						$cron_status = 'Night>Delete>Start';
					}

					// ---next
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Insert raw data>Loop>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Insert raw data>Loop>Update pv_log';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Insert raw data>Loop>Update pv_log':
					$this->backup_prev_status( $cron_status );

					// get all new pv_log (All recored insert)
					$data_ary = $wp_filesystem->get_contents_array( $ary_new_pvrows_file );
					if ( ! empty( $data_ary ) ) {
						// initialize
						$yday_pvmaxcnt = count( $data_ary );
						$loop_start    = (int) $wp_filesystem->get_contents( $raw_loopcount_file );
						if ( $loop_start == 0 ) {
							// 1行目は読み飛ばし
							$loop_start = 1;
						}

						$loopadd = self::RAW_LOOP_MAX;
						if ( $wp_filesystem->exists( $now_raw_loop_maxfile ) ) {
							$loopadd = $wp_filesystem->get_contents( $now_raw_loop_maxfile );
						}
						$loop_end = $loop_start + $loopadd;

						if ( $loop_end > $yday_pvmaxcnt ) {
							$loop_end = $yday_pvmaxcnt;
						}
						// 　生データのファイル一式を保存する配列
						$raw_files_ary = array();
						// loop each max count. ex) 150
						for ( $iii = $loop_start; $iii < $loop_end; $iii++ ) {
							$line = $data_ary[ $iii ];
							$line = str_replace( PHP_EOL, '', $line );
							$data = explode( "\t", $line );

							$pv_id       = $data[0];
							$reader_id   = $data[1];
							$page_id     = $data[2];
							$device_no   = $data[3];
							$session_no  = $data[4];
							$pv_no       = $data[5];
							$access_time = $data[6];

							$dev = 'dsk';
							foreach ( QAHM_DEVICES as $qahm_dev ) {
								if ( (int)$device_no === $qahm_dev['id'] ) {
									$dev = $qahm_dev['name'];
									break;
								}
							}
							// ↑が動かなければ↓で nozawa
/* 								switch ( $device_no ) {
								case QAHM_DEVICES['desktop']['id']:
									$dev = QAHM_DEVICES['desktop']['name'];
									break;
								case QAHM_DEVICES['TABLET']:
									$dev = QAHM_DEVICES['tablet']['name'];
									break;
								case QAHM_DEVICES['MOBILE']:
									$dev = QAHM_DEVICES['mobile']['name'];
									break;
								default:
									$dev = 'dsk';
									break;
							} */

							// detect WordPress post ID from page_id
							$table_name = $wpdb->prefix . 'qa_pages';
							$query      = 'SELECT wp_qa_type, wp_qa_id, url FROM ' . $table_name . ' WHERE page_id = %d';
							//$preobj     = $wpdb->prepare( $query, $page_id );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$result     = $wpdb->get_results( $wpdb->prepare( $query, $page_id ) );
							$id         = $result[0]->wp_qa_id;

							// When this ID is not archive page, then search raw file and update pv_log
							if ( ! empty( $id ) ) {
								$type     = $result[0]->wp_qa_type;
								$base_url = $result[0]->url;
								$raw_dir  = $this->get_raw_dir_path( $type, $id, $dev );


								// check this page version, and get recent version_id & base_selector
								$table_name = $wpdb->prefix . 'qa_page_version_hist';
								$query      = 'SELECT version_id, base_selector, insert_datetime FROM ' . $table_name . ' WHERE page_id = %d AND device_id = %d ORDER BY version_id DESC';
								//$preobj     = $wpdb->prepare( $query, $page_id, $device_no );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $page_id, $device_no ) );
								if ( empty( $result ) ) {
									$version_no = 1;
									// create version
									$options       = $this->get_stream_options( $dev );

									// HTTPヘッダーの設定を抽出
									$headers = array(
										'User-Agent' => $options['http']['header'],
									);
									// タイムアウトとその他のオプションを抽出
									$timeout = $options['http']['timeout'];
									$method  = $options['http']['method'];

									$base_html = false;  // 初期値としてfalseを設定
									$bbb = 0;            // 再試行カウンタ

									// 初回リクエストを送信
									$res_base_html = wp_remote_get( $base_url, array(
										'timeout'    => $timeout,
										'headers'    => $headers,
										'method'     => $method,
										'sslverify'  => false,  // SSL検証を無効化
									) );

									// レスポンスを処理
									if ( !is_wp_error( $res_base_html ) && wp_remote_retrieve_response_code( $res_base_html ) === 200 ) {
										$body = wp_remote_retrieve_body( $res_base_html );
										if ( $this->is_zip( $body ) ) {
											$temphtml = gzdecode( $body );
											if ( $temphtml !== false ) {
												$base_html = $temphtml;
											}
										} else {
											$base_html = $body;
										}
									}

									// 再試行処理
									while ( $base_html === false ) {
										usleep(500);  // 500マイクロ秒待機

										$res_base_html = wp_remote_get( $base_url, array(
											'timeout'    => $timeout,
											'headers'    => $headers,
											'method'     => $method,
											'sslverify'  => false,
										));

										if ( !is_wp_error( $res_base_html ) && wp_remote_retrieve_response_code( $res_base_html ) === 200 ) {
											$body = wp_remote_retrieve_body( $res_base_html );
											if ( $this->is_zip( $body ) ) {
												$temphtml = gzdecode( $body );
												if ( $temphtml !== false ) {
													$base_html = $temphtml;
												}
											} else {
												$base_html = $body;
											}
										}

										$bbb++;
										if ( $bbb > 2 ) {
											$qahm_log->error( 'failed get base_html:' . $base_html );
											break;
										}
									}

									$table_name    = $wpdb->prefix . 'qa_page_version_hist';
									$query         = 'INSERT INTO ' . $table_name . ' (page_id, device_id, version_no, base_html, update_date, insert_datetime) VALUES (%d, %d, %d, %s, now(), now())';
									//$preobj        = $wpdb->prepare( $query, $page_id, $device_no, $version_no, $base_html );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result        = $wpdb->get_results( $wpdb->prepare( $query, $page_id, $device_no, $version_no, $base_html ) );
									$version_id    = $wpdb->insert_id;
									$base_selector = '';
								} else {
									$version_id    = $result[0]->version_id;
									$base_selector = $result[0]->base_selector;
									foreach ( $result as $row ) {
										if ( $qahm_time->str_to_unixtime( $access_time ) >= $qahm_time->str_to_unixtime( $row->insert_datetime ) ) {
											$version_id = $row->version_id;
											$base_selector = $row->base_selector;
											break;
										}
									}
								}


								// check qa_id
								$table_name = $wpdb->prefix . 'qa_readers';
								$query      = 'SELECT qa_id FROM ' . $table_name . ' WHERE reader_id = %d limit 1';
								//$preobj     = $wpdb->prepare( $query, $reader_id );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $reader_id ) );
								$qa_id      = $result[0]->qa_id;

								// raw file check
								if ( empty( $raw_files_ary [ $raw_dir ] ) ) {
									$raw_files_ary[ $raw_dir ] = $this->wrap_dirlist( $raw_dir );
								}
								$raw_files = $raw_files_ary [ $raw_dir ];
								$p_str     = '';
								$c_str     = '';
								$e_str     = '';
								// initial delete file list
								$delete_rawfiles_ary = array();

								// 生データを一つずつチェック
								if ( is_array( $raw_files ) ) {
									foreach ( $raw_files as $raw_file ) {
										$raw_file_name    = $raw_file['name'];
										$raw_file_modtime = $raw_file['lastmodunix'];

										// search -p file
										$preg_str = '/' . $qa_id . '.*_([0-9]*)_([0-9]*)-p.php/';
										if ( preg_match( $preg_str, $raw_file_name, $matches ) ) {
											if ( $session_no == $matches[1] && $pv_no == $matches[2] ) {
												// get -p raw file string
												$deletefile            = $raw_dir . $raw_file_name;
												$delete_rawfiles_ary[] = $deletefile;
												$p_ary = $wp_filesystem->get_contents_array( $raw_dir . $raw_file_name );
												$p_str = '';
												if ( ! empty( $p_ary ) ) {
													$max_p_no = count( $p_ary );
													for ( $jjj = self::HEADER_ELM; $jjj < $max_p_no; $jjj++ ) {
														$p_str .= $p_ary[ $jjj ];
													}
												}
											}
										}

										// search -e file
										$preg_str = '/' . $qa_id . '.*_([0-9]*)_([0-9]*)-e.php/';
										if ( preg_match( $preg_str, $raw_file_name, $matches ) ) {
											if ( $session_no == $matches[1] && $pv_no == $matches[2] ) {
												// get -e raw file string
												$deletefile            = $raw_dir . $raw_file_name;
												$delete_rawfiles_ary[] = $deletefile;
												$e_ary = $wp_filesystem->get_contents_array( $raw_dir . $raw_file_name );
												$e_str = '';
												if ( ! empty( $e_ary ) ) {
													$max_e_no = count( $e_ary );
													for ( $jjj = self::HEADER_ELM; $jjj < $max_e_no; $jjj++ ) {
														$e_str .= $e_ary[ $jjj ];
													}
												}
											}
										}

										// search -c file
										$preg_str = '/' . $qa_id . '.*_([0-9]*)_([0-9]*)-c.php/';
										if ( preg_match( $preg_str, $raw_file_name, $matches ) ) {
											if ( $session_no == $matches[1] && $pv_no == $matches[2] ) {
												// get -c raw file string
												$deletefile            = $raw_dir . $raw_file_name;
												$delete_rawfiles_ary[] = $deletefile;

												// 　selectorをindex変換しながらc_strを作る
												$c_ary = $wp_filesystem->get_contents_array( $raw_dir . $raw_file_name );

												// まず今回のraw_fileに対応するbase_selectorをGETし、selector_aryに変換。
												if ( empty( $base_selector ) ) {
													$selector_ary = array();
												} else {
													$selector_ary    = explode( "\t", $base_selector );
													$max_selector_no = count( $selector_ary );
												}

												// selector_aryがなければ新しくselectorを作る必要がある
												$c_str = '';
												if ( empty( $selector_ary ) ) {
													$is_selector_exist = false;
												} else {
													$is_selector_exist = true;
												}

												// raw_cファイルの全行を確認し、Selector Indexを作成、変換しながらc_strを作っていく
												if ( ! empty( $c_ary ) ) {
													$max_c_no = count( $c_ary );

													// raw_cファイルの全行を確認
													for ( $jjj = self::HEADER_ELM; $jjj < $max_c_no; $jjj++ ) {
														if ( $jjj == self::HEADER_ELM ) {
															$c_str .= $c_ary[ $jjj ];

														} else {
															$c_line        = str_replace( PHP_EOL, '', $c_ary[ $jjj ] );
															$c_line_ary    = explode( "\t", $c_line );
															$max_c_line_no = count( $c_line_ary );
															$selector      = $c_line_ary[0];

															// search selector
															if ( $is_selector_exist ) {
																$selector_not_found = true;
																for ( $selector_idx = 0; $selector_idx < $max_selector_no; $selector_idx++ ) {
																	if ( $selector == $selector_ary[ $selector_idx ] ) {
																		$c_line_ary[0]      = $selector_idx;
																		$selector_not_found = false;
																		break;
																	}
																}

																if ( $selector_not_found ) {
																	// add new selector and index
																	$selector_ary[] = $selector;
																	$c_line_ary[0]  = $selector_idx;
																	$max_selector_no++;
																}
															} else {
																// this is 1st selector
																$selector_ary[]    = $selector;
																$c_line_ary[0]     = 0;
																$is_selector_exist = true;
																$max_selector_no   = 1;
															}
															// make new line
															$new_c_line = '';
															for ( $kkk = 0; $kkk < $max_c_line_no; $kkk++ ) {
																if ( $kkk == $max_c_line_no - 1 ) {
																	if ($jjj == $max_c_no - 1 ) {
																		$new_c_line .= $c_line_ary[ $kkk ];
																	} else {
																		$new_c_line .= $c_line_ary[ $kkk ] . PHP_EOL;
																	}
																} else {
																	$new_c_line .= $c_line_ary[ $kkk ] . "\t";
																}
															}
															// make new c_string
															$c_str .= $new_c_line;
														}
													}
													// all c line check end. UPDATE page_version_hist(base_selector)
													if ( ! empty( $selector_ary ) ) {
														$max_selector_no   = count( $selector_ary );
														$new_base_selector = '';
														for ( $selector_idx = 0; $selector_idx < $max_selector_no; $selector_idx++ ) {
															if ( $selector_idx == $max_selector_no - 1 ) {
																// last
																$new_base_selector .= $selector_ary[ $selector_idx ];
															} else {
																$new_base_selector .= $selector_ary[ $selector_idx ] . "\t";
															}
														}
														$table_name = $wpdb->prefix . 'qa_page_version_hist';
														$query      = 'UPDATE ' . $table_name . ' SET base_selector = %s, update_date = now() WHERE version_id = %d';														
														//$preobj     = $wpdb->prepare( $query, $new_base_selector, $version_id );
														// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
														$result     = $wpdb->get_results( $wpdb->prepare( $query, $new_base_selector, $version_id ) );
													}
												}
											}
										}

										// if p,c,e raw files are already found, foreach ID directory search can stop.
										if ( ! empty( $p_str ) && ! empty( $c_str ) ) {
											if ( ! empty( $e_str ) ) {
												break;
											}
										}
									}
								}

								// p,c,e all file check & make string done.
								$table_name = $wpdb->prefix . 'qa_pv_log';
								$query      = 'UPDATE ' . $table_name . ' SET raw_p = %s, raw_c = %s, raw_e = %s,version_id = %d WHERE pv_id = %d';
								//$preobj     = $wpdb->prepare( $query, $p_str, $c_str, $e_str, $version_id, $pv_id );
								// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
								$result     = $wpdb->get_results( $wpdb->prepare( $query, $p_str, $c_str, $e_str, $version_id, $pv_id ) );

								// if version is accessed without raw_c. then update date
								if ( ! empty( $p_str ) || ! empty( $e_str ) ) {
									$table_name = $wpdb->prefix . 'qa_page_version_hist';
									$query      = 'UPDATE ' . $table_name . ' SET update_date = now() WHERE version_id = %d';
									//$preobj     = $wpdb->prepare( $query, $version_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$result     = $wpdb->get_results( $wpdb->prepare( $query, $version_id ) );
								}
								// write delete raw files list & update loop count
								$this->write_ary_to_temp( $delete_rawfiles_ary, $del_rawfileslist_temp . $iii );
							}
							$wp_filesystem->put_contents( $raw_loopcount_file, $iii );
						}
					}

					// ---next
					$cron_status = 'Night>Insert raw data>Loop>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Insert raw data>Loop>End':
					$this->backup_prev_status( $cron_status );

					// 初期値はPV_logにいれた個数
					$yday_pvmaxcnt  = (int) $wp_filesystem->get_contents( $yday_pvmaxcnt_file );
					// 実際にrawデータの入力に使った配列ファイルがあれば、この行数(1行目は404なので1つ多い)から今日のループ最大値を導く
					$data_ary = $wp_filesystem->get_contents_array( $ary_new_pvrows_file );
					if ( ! empty( $data_ary ) ) {
						// initialize
						$yday_pvmaxcnt = count( $data_ary );
					}
					$now_pvloop_elm = $wp_filesystem->get_contents( $raw_loopcount_file );

					if ( $now_pvloop_elm >= $yday_pvmaxcnt - 1 ) {
						$cron_status = 'Night>Insert raw data>Merge delete file';
					} else {
						$cron_status = 'Night>Insert raw data>Loop>Start';
					}
					// ---next
					$errmsg = $this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Insert raw data>Merge delete file':
					$this->backup_prev_status( $cron_status );

					$delete_files              = $this->wrap_dirlist( $tempdelete_dir );
					$del_rawfileslist_file_ary = array();
					// ファイルを一つずつ処理
					if ( $delete_files ) {
						foreach ( $delete_files as $delete_file ) {
							$filenames_ary = $wp_filesystem->get_contents_array( $tempdelete_dir . $delete_file['name'] );
							$is_first_line = true;
							foreach ( $filenames_ary as $del_raw_file ) {
								if ( ! $is_first_line ) {
									$del_rawfileslist_file_ary[] = $del_raw_file;
								} else {
									$is_first_line = false;
								}
							}
						}
					}
					$this->write_ary_to_temp( $del_rawfileslist_file_ary, $del_rawfileslist_file );
					// 書き込んだらtempファイルは削除
					if ( $delete_files ) {
						foreach ( $delete_files as $delete_file ) {
							$wp_filesystem->delete( $tempdelete_dir . $delete_file['name'] );
						}
					}
					$cron_status = 'Night>Insert raw data>End';
					// ---next
					$errmsg = $this->set_next_status( $cron_status );
					break;

				case 'Night>Insert raw data>End':
					$this->backup_prev_status( $cron_status );

					// yday files
					if (  QAHM_DEBUG <= QAHM_DEBUG_LEVEL['staging']  ) {
						if ( $wp_filesystem->exists( $yday_pvmaxcnt_file ) ) {
							$wp_filesystem->delete( $yday_pvmaxcnt_file );
						}
						if ( $wp_filesystem->exists( $ary_new_pvrows_file ) ) {
							$wp_filesystem->delete( $ary_new_pvrows_file );
						}
						// raw files
						if ( $wp_filesystem->exists( $now_raw_loop_maxfile ) ) {
							$wp_filesystem->delete( $now_raw_loop_maxfile );
						}
						if ( $wp_filesystem->exists( $raw_loopcount_file ) ) {
							$wp_filesystem->delete( $raw_loopcount_file );
						}
					}
					// ---next
					//$cron_status = 'Night>Delete>Start';
					$cron_status = 'Night>Make view file>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// SQL Error
				// ----------
				case 'Night>Sql error':
					$this->backup_prev_status( $cron_status );

					$qahm_log->error( 'SQL Error:' . $wpdb->last_query );
					// ---next
					$cron_status = 'Night>Delete>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Make view data
				// ----------
				case 'Night>Make view file>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make view file>View_pv>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make view file>View_pv>Start':
					$this->backup_prev_status( $cron_status );

					//check dir
					//view_base
					if ( ! $wp_filesystem->exists( $view_dir ) ) {
						$wp_filesystem->mkdir( $view_dir );
					}
					if ( ! $wp_filesystem->exists( $myview_dir ) ) {
						$wp_filesystem->mkdir( $myview_dir );
					}
					//view_pv
					if ( ! $wp_filesystem->exists( $viewpv_dir ) ) {
						$wp_filesystem->mkdir( $viewpv_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_p_dir ) ) {
						$wp_filesystem->mkdir( $raw_p_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_c_dir ) ) {
						$wp_filesystem->mkdir( $raw_c_dir );
					}
					if ( ! $wp_filesystem->exists( $raw_e_dir ) ) {
						$wp_filesystem->mkdir( $raw_e_dir );
					}

					//index
					if ( ! $wp_filesystem->exists( $viewpv_dir . 'index/' ) ) {
						$wp_filesystem->mkdir( $viewpv_dir . 'index/' );
					}

					// ---next
					$cron_status = 'Night>Make view file>View_pv>Make loop';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Make view file>View_pv>Make loop':
					$this->backup_prev_status( $cron_status );

					//x month ago
					$yearx = $qahm_time->year();
					$month = $qahm_time->month();
					$data_save_month = self::DATA_SAVE_MONTH;
					//  same_month
					$save_yearx = $yearx;
					$save_month = $month - $data_save_month;
					if ( $save_month <= 0 ) {
						$save_month = 12 + $save_month;
						$save_yearx = $yearx - 1;
					}

					//below is ok
					$save_month = sprintf('%02d', $save_month);
					$s_datetime = $save_yearx . '-' . $save_month . '-01 00:00:00';
					$e_datetime = $qahm_time->xday_str( -1 ) . ' 23:59:59';
					$max_datetime = $e_datetime;

					//mkdummy ファイルをサーチをして、当日作成されているファイルはもう再作成不要。
					//search dir
					$allfiles = $this->wrap_dirlist( $viewpv_dir );
					if ($allfiles) {
						foreach ( $allfiles as $file ) {
							$filename = $file[ 'name' ];
							if ( is_file( $viewpv_dir . $filename ) ) {
								$file_unixtime = $file['lastmodunix'];
								$yesterday_end = $qahm_time->xday_str( -1 ) . ' 23:59:59';
								if ( $qahm_time->str_to_unixtime($yesterday_end) < $file_unixtime ) {
									$f_date = substr( $filename, 0, 10 );
									$f_datetime = $f_date . ' 00:00:00';
									//既にファイルが存在するので、startするdatetimeは次の日付になる。
									$s_datetime = $qahm_time->xday_str( 1, $f_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
								}
							}
						}
					}

					//日付判定
					if ( $qahm_time->str_to_unixtime( $max_datetime ) < $qahm_time->str_to_unixtime( $s_datetime )) {
						$is_endday = true;
						$is_loop = false;
					} else {
						//今回のタスク内でファイルを作成し続ける場合はis_endday = false;
						$is_endday = false;
						//view_pvファイルが今回のタスク内だけで完成しない場合はis_loop = true;
						$is_loop = false;
					}

					$max_day_dist  = self::VIEWPV_DAY_LOOP_MAX;
					$sec_dist = $qahm_time->str_to_unixtime( $e_datetime ) -  $qahm_time->str_to_unixtime( $s_datetime );
					if ( $max_day_dist < ($sec_dist/3600/24)) {
						//期間が長すぎるのでまずdefault12日(1万PVで2分間想定)でどのくらいアクセス数があるかチェック。このcaseが走る初回のみここを通る（あとは通常毎日1ファイルずつ作られる）

						//view_pvファイルが今回のタスク内だけでは完成しない想定なのでis_loop = true;
						$is_loop    = true;

						//とりあえず、データが見つからない限り（is_nodata=true)どんなデータが入っているか見てみる。
						$is_nodata  = true;
						while ( $is_nodata ) {
							$e_datetime     = $qahm_time->xday_str( -1 ) . ' 23:59:59';
							$e_date_new     = $qahm_time->xday_str(self::VIEWPV_DAY_LOOP_MAX, $s_datetime );
							$e_datetime_new = $e_date_new . ' 23:59:59';
							if ( $qahm_time->str_to_unixtime( $e_datetime_new ) < $qahm_time->str_to_unixtime( $e_datetime ) ){
								$e_datetime = $e_datetime_new;
							}

							$table_name = $wpdb->prefix . 'qa_pv_log';
							$query      = 'SELECT count(*)  FROM ' . $table_name . ' WHERE  access_time between %s AND %s';
							//$preobj     = $wpdb->prepare( $query,  $s_datetime, $e_datetime );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$countx     = $wpdb->get_var( $wpdb->prepare( $query,  $s_datetime, $e_datetime ) );

							if ((int)$countx === 0) {
								$s_datetime = $qahm_time->xday_str($max_day_dist, $s_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
								if ($qahm_time->str_to_unixtime( $e_datetime ) <  $qahm_time->str_to_unixtime( $s_datetime ) ) {
									//もし最終日までいったのに、全くデータがない場合、viewファイル作成タスク自体を終了して次のタスクへ
									$is_nodata = false;
									$is_loop = false;
									$is_endday = true;
								}
							} else {
								//データがあったのでアクセス数（count）を判断し、今回のタスク内（cron2分間）で作成するファイル数を決める
								$day_access     = ceil( $countx / self::VIEWPV_DAY_LOOP_MAX );
								$pv_msec        = 2;
								$day_sec        = $pv_msec * $day_access / 1000;
								$cron_2min_max  = ceil(120/$day_sec);
								if ($cron_2min_max > 365) {
									$cron_2min_max = 365;
								}

								$e_datetime     = $qahm_time->xday_str( -1 ) . ' 23:59:59';
								$e_date_new     = $qahm_time->xday_str($cron_2min_max, $s_datetime );
								$e_datetime_new = $e_date_new . ' 23:59:59';
								if ( $qahm_time->str_to_unixtime( $e_datetime_new ) < $qahm_time->str_to_unixtime( $e_datetime ) ){
									$e_datetime = $e_datetime_new;
								}
								// データ作成へ
								$is_nodata = false;
								$is_loop = true;
								$is_endday = false;
							}
						}
					}

					// for 不要なファイル削除用
					$allfiles = $this->wrap_dirlist( $viewpv_dir );
					//暫定raw_p
					$allpfiles = $this->wrap_dirlist( $raw_p_dir );


					//make
					while ( ! $is_endday ){
						$s_date = substr( $s_datetime,0, 10 );
						$s_dateend = $s_date . ' 23:59:59';

						//make file
						$table_name = $wpdb->prefix . 'qa_pv_log';
						$query      = 'SELECT pv_id,reader_id,page_id ,device_id,source_id,medium_id,campaign_id,session_no,access_time,pv,speed_msec,browse_sec,is_last,is_newuser,version_id,raw_p,raw_c,raw_e  FROM ' . $table_name . ' WHERE  access_time between %s AND %s';
						//$preobj     = $wpdb->prepare( $query,  $s_datetime, $s_dateend );
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
						$result     = $wpdb->get_results( $wpdb->prepare( $query,  $s_datetime, $s_dateend ) );

						if ( ! empty( $result ) ) {
							$newary = array();
							$raw_p_ary = [];
							$raw_c_ary = [];
							$raw_e_ary = [];

							foreach ($result as $idx => $row ) {
								$newary[$idx]['pv_id'] = $row->pv_id;

								// reader_id
								$newary[$idx]['reader_id'] = $row->reader_id;
								$newary[$idx]['UAos'] = '';
								$newary[$idx]['UAbrowser'] = '';
								//mkdummy 国ができたら変更必須
								$newary[$idx]['country'] = '';
								//mkdummy end
								if ( $row->reader_id ) {
									$table_name = $wpdb->prefix . 'qa_readers';

									$query      = 'SELECT UAos,UAbrowser FROM ' . $table_name . ' WHERE  reader_id = %d';
									//$preobj     = $wpdb->prepare( $query, $row->reader_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$select     = $wpdb->get_results( $wpdb->prepare( $query, $row->reader_id ) );
									if ( $select ) {
										$newary[$idx]['UAos'] = $select[0]->UAos;
										$newary[$idx]['UAbrowser'] = $select[0]->UAbrowser;
									}
								}

								// page_id
								$newary[$idx]['page_id'] = $row->page_id;
								$newary[$idx]['url'] = '';
								$newary[$idx]['title'] = '';
								if ( $row->page_id ) {
									$table_name = $wpdb->prefix . 'qa_pages';
									$query      = 'SELECT url,title FROM ' . $table_name . ' WHERE  page_id = %d';
									//$preobj     = $wpdb->prepare( $query, $row->page_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$select     = $wpdb->get_results( $wpdb->prepare( $query, $row->page_id ) );
									if ( $select ) {
										$newary[$idx]['url'] = $select[0]->url;
										$newary[$idx]['title'] = esc_html( $select[0]->title );
									}
								}

								// device_id
								$newary[$idx]['device_id'] = $row->device_id;

								// source_id
								$newary[$idx]['source_id'] = $row->source_id;
								$newary[$idx]['utm_source'] = '';
								$newary[$idx]['source_domain'] = '';
								if ( $row->source_id ) {
									$table_name = $wpdb->prefix . 'qa_utm_sources';
									$query      = 'SELECT utm_source,source_domain FROM ' . $table_name . ' WHERE  source_id = %d';
									//$preobj     = $wpdb->prepare( $query, $row->source_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$select     = $wpdb->get_results( $wpdb->prepare( $query, $row->source_id ) );
									if ( $select ) {
										$newary[$idx]['utm_source'] = $select[0]->utm_source;
										$newary[$idx]['source_domain'] = $select[0]->source_domain;
									}
								}

								// medium_id
								$newary[$idx]['medium_id'] = $row->medium_id;
								$newary[$idx]['utm_medium'] = '';
								if ( $row->medium_id ) {
									$table_name = $wpdb->prefix . 'qa_utm_media';
									$query      = 'SELECT utm_medium FROM ' . $table_name . ' WHERE  medium_id = %d';
									//$preobj     = $wpdb->prepare( $query, $row->medium_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$select     = $wpdb->get_results( $wpdb->prepare( $query, $row->medium_id ) );
									if ( $select ) {
										$newary[$idx]['utm_medium'] = $select[0]->utm_medium;
									}
								}

								// campaign_id
								$newary[$idx]['campaign_id'] = $row->campaign_id;
								$newary[$idx]['utm_campaign'] = '';
								if ( $row->campaign_id ) {
									$table_name = $wpdb->prefix . 'qa_utm_campaigns';
									$query      = 'SELECT utm_campaign FROM ' . $table_name . ' WHERE  campaign_id = %d';
									//$preobj     = $wpdb->prepare( $query, $row->campaign_id );
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
									$select     = $wpdb->get_results( $wpdb->prepare( $query, $row->campaign_id ) );
									if ( $select ) {
										$newary[$idx]['utm_campaign'] = $select[0]->utm_campaign;
									}
								}

								// others
								$newary[$idx]['session_no']    = $row->session_no;
								$newary[$idx]['access_time']   = $row->access_time;
								$newary[$idx]['pv']            = $row->pv;
								$newary[$idx]['speed_msec']    = $row->speed_msec;
								$newary[$idx]['browse_sec']    = $row->browse_sec;
								$newary[$idx]['is_last']       = $row->is_last;
								$newary[$idx]['is_newuser']    = $row->is_newuser;
								$newary[$idx]['version_id']    = $row->version_id;


								if ($row->raw_p) {
									$tmpstrary = explode(PHP_EOL, $row->raw_p);
									$newary[$idx]['is_raw_p'] = (int)$tmpstrary[0];
									$raw_p_ary[] = ['pv_id' =>$row->pv_id, 'raw_p' => $row->raw_p];

									//mkdummy ver1.0.2.0から1.0.6.0までのbrowse_sec問題対策
									/*
									if ( (int)$newary[$idx]['browse_sec'] === 0) {
										$browse_sec = 0;
										$posline_max = count( $tmpstrary );
										// calc sec_on_page from position file.
										for ( $jjj = self::DATA_COLUMN_BODY; $jjj < $posline_max; $jjj++ ) {
											$posline_ary = explode( "\t", $tmpstrary[$jjj] );
											if ( $posline_ary[ self::DATA_POS_1[ 'PERCENT_HEIGHT' ] ] === 'a' ) {
												continue;
											}
											$browse_sec += (int)$posline_ary[ self::DATA_POS_1[ 'TIME_ON_HEIGHT' ] ];
										}
										$newary[$idx]['browse_sec'] = $browse_sec;
									}*/
								} else {
									$newary[$idx]['is_raw_p'] = 0;
								}
								if ($row->raw_c) {
									$tmpstrary = explode(PHP_EOL, $row->raw_c);
									$newary[$idx]['is_raw_c'] = (int)$tmpstrary[0];
									$raw_c_ary[] = ['pv_id' =>$row->pv_id, 'raw_c' => $row->raw_c];
								} else {
									$newary[$idx]['is_raw_c'] = 0;
								}
								if ($row->raw_e) {
									$tmpstrary = explode(PHP_EOL, $row->raw_e);
									$newary[$idx]['is_raw_e'] = (int)$tmpstrary[0];
									$raw_e_ary[] = ['pv_id' =>$row->pv_id, 'raw_e' => $row->raw_e];
								} else {
									$newary[$idx]['is_raw_e'] = 0;
								}

							}
							//旧ファイルがあればdelete
							//search dir
							$delete_viewfile = '';
							if ($allfiles) {
								foreach ( $allfiles as $file ) {
									$filename = $file[ 'name' ];
									if ( is_file( $viewpv_dir . $filename ) ) {
										$f_date = substr( $filename, 0, 10 );
										if ( $f_date === $s_date ) {
											$delete_viewfile = $filename;
											break;
										}
									}
								}
							}
							if ( $delete_viewfile !== '') {
								$delete_base = str_replace( 'viewpv.php', '', $delete_viewfile );
								if ( $wp_filesystem->exists( $raw_p_dir . $delete_base . 'rawp.php' ) ) $wp_filesystem->delete( $raw_p_dir . $delete_base . 'rawp.php' );
								if ( $wp_filesystem->exists( $raw_c_dir . $delete_base . 'rawc.php' ) ) $wp_filesystem->delete( $raw_c_dir . $delete_base . 'rawc.php' );
								if ( $wp_filesystem->exists( $raw_e_dir . $delete_base . 'rawe.php' ) ) $wp_filesystem->delete( $raw_e_dir . $delete_base . 'rawe.php' );
								if ( $wp_filesystem->exists( $viewpv_dir . $delete_base . 'viewpv.php' ) ) $wp_filesystem->delete( $viewpv_dir . $delete_base . 'viewpv.php' );
							}
							//暫定
							$delete_rawpfile = '';
							if ($allpfiles) {
								foreach ( $allpfiles as $file ) {
									$filename = $file[ 'name' ];
									if ( is_file( $raw_p_dir . $filename ) ) {
										$f_date = substr( $filename, 0, 10 );
										if ( $f_date === $s_date ) {
											$delete_rawpfile = $filename;
											break;
										}
									}
								}
							}
							if ( $delete_rawpfile !== '') {
								$delete_base = str_replace( 'rawp.php', '', $delete_rawpfile );
								if ( $wp_filesystem->exists( $raw_p_dir . $delete_base . 'rawp.php' ) ) $wp_filesystem->delete( $raw_p_dir . $delete_base . 'rawp.php' );
								if ( $wp_filesystem->exists( $raw_c_dir . $delete_base . 'rawc.php' ) ) $wp_filesystem->delete( $raw_c_dir . $delete_base . 'rawc.php' );
								if ( $wp_filesystem->exists( $raw_e_dir . $delete_base . 'rawe.php' ) ) $wp_filesystem->delete( $raw_e_dir . $delete_base . 'rawe.php' );
								if ( $wp_filesystem->exists( $viewpv_dir . $delete_base . 'viewpv.php' ) ) $wp_filesystem->delete( $viewpv_dir . $delete_base . 'viewpv.php' );
							}

							//書込
							$filename_base = $s_date . '_' . (string)$newary[0]['pv_id'] . '-' . (string)$newary[count($newary) - 1]['pv_id'] . '_';
							$this->wrap_put_contents( $raw_p_dir . $filename_base . 'rawp.php', $this->wrap_serialize( $raw_p_ary ) );
							$this->wrap_put_contents( $raw_c_dir . $filename_base . 'rawc.php', $this->wrap_serialize( $raw_c_ary ) );
							$this->wrap_put_contents( $raw_e_dir . $filename_base . 'rawe.php', $this->wrap_serialize( $raw_e_ary ) );
							$this->wrap_put_contents( $viewpv_dir . $filename_base . 'viewpv.php', $this->wrap_serialize( $newary ) );
						}

						// is end day?
						if ( $qahm_time->str_to_unixtime( $e_datetime ) <= $qahm_time->str_to_unixtime( $s_dateend ) ) {
							//この期間で2分間cronは終わり。
							$is_endday = true;
							if ( $qahm_time->str_to_unixtime( $max_datetime ) <= $qahm_time->str_to_unixtime( $s_dateend ) ) {
								$is_loop = false;
							}
						} else {
							//次の日へ進める
							$is_endday  = false;
							$s_datetime = $qahm_time->xday_str( 1, $s_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
						}
					}
					//ループする場合は再度同じところを稼働する
					if ($is_loop) {
						$cron_status = 'Night>Make view file>View_pv>Make loop';
					}else{
						$cron_status = 'Night>Make view file>View_pv>Make index loop';
					}
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make view file>View_pv>Make index loop':
					$this->backup_prev_status( $cron_status );

					$indexids = ['reader', 'page', 'source', 'medium', 'campaign', 'version'];
					foreach ( $indexids as $indexid ) {
						//x month ago
						$yearx = $qahm_time->year();
						$month = $qahm_time->month();
						$data_save_month = self::DATA_SAVE_MONTH;
						//  same_month
						$save_yearx = $yearx;
						$save_month = $month - $data_save_month;
						if ( $save_month <= 0 ) {
							$save_month = 12 + $save_month;
							$save_yearx = $yearx - 1;
						}

						//below is ok
						$save_month = sprintf('%02d', $save_month);
						$s_datetime = $save_yearx . '-' . $save_month . '-01 00:00:00';
						$e_datetime = $qahm_time->xday_str( -1 ) . ' 23:59:59';

						//最初のpage_id用のindex配列を作る。page_id用の配列はIDですぐ飛べるように、1スタートの固定長にする。
						$is_already_done = false;
						if ( $wp_filesystem->exists( $viewpv_dir . 'index/' ) ) {
							$allindexfiles = $this->wrap_dirlist($viewpv_dir . 'index/');
							if ($allindexfiles) {
								foreach ($allindexfiles as $file) {
									$filename = $file['name'];
									if ( is_file($viewpv_dir . 'index/' . $filename ) ) {
										if ( strpos( $filename, $indexid . 'id') !== false ) {
											$file_unixtime = $file['lastmodunix'];
											$yesterday_end = $qahm_time->xday_str( -1 ) . ' 23:59:59';
											if ( $qahm_time->str_to_unixtime($yesterday_end) < $file_unixtime ) {
												//既に本日作成済みは次のIDへ
												$is_already_done = true;
												continue;
											} else {
												$indexary = explode('-', $filename );
												$aryindex = floor( (int)$indexary[0] / self::ID_INDEX_MAX10MAN );
												$getconts = $this->wrap_get_contents( $viewpv_dir . 'index/' . $filename );
												$viewpv_id_index[$aryindex]= $this->wrap_unserialize( $getconts );
												unset( $getconts );
												$is_already_done = false;
											}
										}
									}
								}
							}
						}
						if ( $is_already_done ) {
							continue;
						}
						if ( !isset($viewpv_id_index[0]) ) {
							$viewpv_id_index[0] = array_fill( 1, self::ID_INDEX_MAX10MAN, false);
						}

						//make file
						$table_id   = $indexid . '_id';
						$is_endday  = false;

						//make index array and save
						while ( ! $is_endday ) {
							$s_date = substr( $s_datetime,0, 10 );
							$s_dateend = $s_date . ' 23:59:59';

							global $qahm_data_api;
							$dateid = 'date = between '. $s_date . ' and '. $s_date;
							$someday_pv = $qahm_data_api->select_data('view_pv', '*', $dateid );
							if ( $someday_pv ) {
								$result = $someday_pv[0];
								if ( ! empty( $result ) ) {
									foreach ( $result as $idx => $row ) {
										$this->make_index_array( $viewpv_id_index, (int)$row[$table_id], (int)$row['pv_id'], $s_date );
									}
									unset( $result );
									unset( $someday_pv );
								}
							}
							// is end day?
							if ( $qahm_time->str_to_unixtime( $e_datetime ) <= $qahm_time->str_to_unixtime( $s_dateend ) ) {
								$is_endday = true;
								//index配列を保存
								$this->save_index_array( $viewpv_id_index, $viewpv_dir, $indexid . 'id.php' );
								unset( $viewpv_id_index );
							} else {
								$is_endday  = false;
								$s_datetime = $qahm_time->xday_str( 1, $s_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
							}
						}
					}
					//次へ
					$cron_status = 'Night>Make view file>View_pv>End';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make view file>View_pv>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make view file>Readers>Start';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Make view file>Readers>Start':
					$this->backup_prev_status( $cron_status );

					// reader
					if ( ! $wp_filesystem->exists( $vw_reader_dir ) ) {
						$wp_filesystem->mkdir( $vw_reader_dir );
					}
					// ---next
					$cron_status = 'Night>Make view file>Readers>Make';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Make view file>Readers>Make':
					$this->backup_prev_status( $cron_status );

					global $qahm_db;

					$save_s_id = 1;
					$save_e_id = 1;
					//search dir
					$allfiles = $this->wrap_dirlist( $vw_reader_dir );
					$beforefile = '';
					$beforestat = 0;
					$beforeend  = 0;
					if ($allfiles) {
						foreach ($allfiles as $file) {
							$filename = $file['name'];
							if (is_file($vw_reader_dir . $filename)) {
								$tmpary = explode('_', $filename);
								$reader_ids = explode('-', $tmpary[0]);
								if ( $save_s_id < $reader_ids[0] ) {
									$save_s_id = $reader_ids[0];
								}

								if ($save_e_id < $reader_ids[1]) {
									$save_e_id = $reader_ids[1];
								}
							}
							if ( $reader_ids[0] === $beforestat && $beforeend < $reader_ids[1]) {
								if ($beforefile) {
									$wp_filesystem->delete( $vw_reader_dir . $beforefile);
								}
							}
							$beforefile = $filename;
							$beforestat = $reader_ids[0];
							$beforeend  = $reader_ids[1];
						}
					}

					//現在の最終IDを調査
					$table_name = $wpdb->prefix . 'qa_readers';
					$query      = 'SELECT reader_id FROM ' . $table_name . ' order by reader_id asc limit 1';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$stat_id    = $wpdb->get_var( $wpdb->prepare($query) );

					$query      = 'SELECT reader_id FROM ' . $table_name . ' order by reader_id desc limit 1';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$last_id    = $wpdb->get_var( $wpdb->prepare($query) );

					if ( $save_s_id < $stat_id ) {
						$save_s_id = $stat_id;
					}
					$lastdist   = $last_id - $save_s_id;
					if ( $lastdist <= self::VIEW_READERS_MAX_IDS ) {
						if ( $save_e_id !== $last_id ) {
							//最終IDだけ保存すればOK
							$query      = 'SELECT * FROM ' . $table_name . ' WHERE reader_id between %d AND %d';
							//$preobj     = $wpdb->prepare( $query,  $save_s_id, $last_id );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$allrecord  = $qahm_db->get_results( $wpdb->prepare( $query,  $save_s_id, $last_id ) );

							//既存のファイルをオープンし、新しくカラムを追加して保存する
							$oldfile = $save_s_id . '-' . $save_e_id . '_readers.php';
							$newfile = $save_s_id . '-' . $last_id . '_readers.php';
							if ( $wp_filesystem->exists( $vw_reader_dir.$oldfile) ) {
								$oldary = $this->wrap_get_contents($vw_reader_dir . $oldfile);
								$newary = [];
								$newary[] = $oldary;
								foreach ($allrecord as $row ) {
									if ( $save_e_id < $row->reader_id) {
										$newary[] = $row;
									}
								}
								$this->wrap_put_contents( $vw_reader_dir . $newfile, $this->wrap_serialize( $newary ) );
								if ( $newfile !== $oldfile ) {
									$wp_filesystem->delete( $vw_reader_dir.$oldfile );
								}
							} else {
								$this->wrap_put_contents( $vw_reader_dir . $newfile, $this->wrap_serialize( $allrecord ) );
							}
						}
					} else {
						//最後まで保存ループが必要
						$is_last = false;
						while ( ! $is_last ) {
							$now_lastid = $save_s_id + self::VIEW_READERS_MAX_IDS;
							if ( $last_id < $now_lastid) {
								$now_lastid = $last_id;
								$is_last    = true;
							}
							$query      = 'SELECT * FROM ' . $table_name . ' WHERE reader_id between %d AND %d';
							//$preobj     = $wpdb->prepare( $query,  $save_s_id, $now_lastid );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
							$allrecord  = $qahm_db->get_results( $wpdb->prepare( $query,  $save_s_id, $now_lastid ) );

							$allcount   = count($allrecord);
							$dbstatid   = $allrecord[0]->reader_id;
							$dblastid   = $allrecord[$allcount -1]->reader_id;

							//新しく保存する
							$newfile = $dbstatid . '-' . $dblastid . '_readers.php';
							$this->wrap_put_contents( $vw_reader_dir . $newfile, $this->wrap_serialize( $allrecord ) );
							//値を進める
							$save_s_id = $dblastid + 1;
						}
					}
					$cron_status = 'Night>Make view file>Readers>End';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make view file>Readers>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make view file>Version_hist>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make view file>Version_hist>Start':
					$this->backup_prev_status( $cron_status );

					// reader
					if ( ! $wp_filesystem->exists( $vw_verhst_dir ) ) {
						$wp_filesystem->mkdir( $vw_verhst_dir );
					}
					if ( ! $wp_filesystem->exists( $vw_verhst_dir . 'index/' ) ) {
						$wp_filesystem->mkdir( $vw_verhst_dir . 'index/' );
					}
					// ---next
					$cron_status = 'Night>Make view file>Version_hist>Make';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Make view file>Version_hist>Make':
					$this->backup_prev_status( $cron_status );

					global $qahm_db;
					//最初のpage_id用のindex配列を作る。page_id用の配列はIDですぐ飛べるように、1スタートの固定長にする。
					if ( $wp_filesystem->exists( $vw_verhst_dir . 'index/' ) ) {
						$allindexfiles = $this->wrap_dirlist($vw_verhst_dir . 'index/');
						if ($allindexfiles) {
							foreach ($allindexfiles as $file) {
								$filename = $file['name'];
								if ( is_file($vw_verhst_dir . 'index/' . $filename ) ) {
									$indexary = explode('-', $filename );
									$aryindex = floor( (int)$indexary[0] / self::ID_INDEX_MAX10MAN );
									$verhst_pageid_index[$aryindex]= $this->wrap_unserialize( $this->wrap_get_contents( $vw_verhst_dir . 'index/' . $filename ) );
								}
							}
						}
					}
					if ( !isset($verhst_pageid_index[0]) ) {
						$verhst_pageid_index[0] = array_fill( 1, self::ID_INDEX_MAX10MAN, false);
					}

					//現在のIDを調査
					$table_name = $wpdb->prefix . 'qa_page_version_hist';
					$query      = 'SELECT version_id FROM ' . $table_name . ' order by version_id asc limit 1';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$stat_id    = $wpdb->get_var( $wpdb->prepare($query) );

					$query      = 'SELECT version_id FROM ' . $table_name . ' order by version_id desc limit 1';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$last_id    = $wpdb->get_var( $wpdb->prepare($query) );
					for ($iii = $stat_id; $iii <= $last_id; $iii++) {
						//一つずつ保存していく
						$query      = 'SELECT * FROM ' . $table_name . ' WHERE version_id = %d';
						//$preobj     = $wpdb->prepare( $query,  $iii );
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses placeholders and $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
						$allrecord  = $qahm_db->get_results( $wpdb->prepare( $query,  $iii ) );
						if ( $allrecord ) {
							//pageidのindexを作成
							$pageid   = (int)$allrecord[0]->page_id;
							$nowidx   = floor( $pageid / self::ID_INDEX_MAX10MAN );
							if ( ! isset( $verhst_pageid_index[$nowidx] ) ) {
								//初期化
								$start = self::ID_INDEX_MAX10MAN * $nowidx + 1;
								$verhst_pageid_index[$nowidx] = array_fill( $start, self::ID_INDEX_MAX10MAN, false);
							}
							//version_idの保存
							if ( $verhst_pageid_index[$nowidx][$pageid] !== false ) {
								$verid_ary  = $verhst_pageid_index[$nowidx][$pageid];
								$is_verfind = false;
								foreach ( $verid_ary as $verid ) {
									if ( (int)$iii === (int) $verid) {
										$is_verfind = true;
										break;
									}
								}
								if ( !$is_verfind ) {
									$verhst_pageid_index[$nowidx][$pageid][] = $iii;
								}
							} else {
								$verhst_pageid_index[$nowidx][$pageid]   = [$iii];
							}

							//ファイルに保存
							$newfile = $iii . '_version.php';
							$this->wrap_put_contents( $vw_verhst_dir . $newfile, $this->wrap_serialize( $allrecord ) );
						}
					}
					//最後にpageidのインデックスを保存しておく
					for ( $jjj = 0; $jjj < count($verhst_pageid_index); $jjj++ ) {
						$start_index       = $jjj * self::ID_INDEX_MAX10MAN + 1;
						$end_index         = $start_index + self::ID_INDEX_MAX10MAN - 1;
						$pageid_index_file = $start_index . '-' . $end_index . '_pageid.php';
						$this->wrap_put_contents( $vw_verhst_dir . 'index/' . $pageid_index_file, $this->wrap_serialize( $verhst_pageid_index[$jjj] ) );
					}
					$cron_status = 'Night>Make view file>Version_hist>End';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make view file>Version_hist>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make view file>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make view file>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Delete
				// ----------
				case 'Night>Delete>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>Files>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Files>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>Files>Readers';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Files>Readers':
					$this->backup_prev_status( $cron_status );

					// readers finish dir search and delete
					$readersfin_files = $this->wrap_dirlist( $readersdbin_dir );
					// 2days before
					$day2before_str     = $qahm_time->xday_str( -2 );
					$day2before_end     = $qahm_time->str_to_unixtime( $day2before_str . ' 23:59:59' );
					// 一昨日のセッションファイルを削除
					if ( is_array( $readersfin_files ) ) {
						foreach ( $readersfin_files as $readersfin_file ) {
							$make_time = $readersfin_file['lastmodunix'];
							if ( $make_time <= $day2before_end ) {
								$wp_filesystem->delete( trim( $readersdbin_dir . $readersfin_file['name'] ) );
							}
						}
					}

					// ---next
					$cron_status = 'Night>Delete>Files>Work';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Files>Work':
					$this->backup_prev_status( $cron_status );

					// works dir search and delete
					$heatmapwork_files = $this->wrap_dirlist( $heatmapwork_dir );
					$replaywork_files  = $this->wrap_dirlist( $replaywork_dir );

					$day2before_str     = $qahm_time->xday_str( -2 );
					$day2before_end     = $qahm_time->str_to_unixtime( $day2before_str . ' 23:59:59' );
					if ( is_array( $heatmapwork_files ) ) {
						foreach ( $heatmapwork_files as $heatmapwork_file ) {
							$make_time = $heatmapwork_file['lastmodunix'];
							if ( $make_time <= $day2before_end ) {
								$wp_filesystem->delete( trim( $heatmapwork_dir . $heatmapwork_file['name'] ) );
							}
						}
					}

					if ( is_array( $replaywork_files ) ) {
						foreach ( $replaywork_files as $replaywork_file ) {
							$make_time = $replaywork_file['lastmodunix'];
							if ( $make_time <= $day2before_end ) {
								$wp_filesystem->delete( trim( $replaywork_dir . $replaywork_file['name'] ) );
							}
						}
					}

					// ---next
					$cron_status = 'Night>Delete>Files>Realtime tsv';
					$this->set_next_status( $cron_status );
					break;

/*
				case 'Night>Delete>Files>Cache':
					$this->backup_prev_status( $cron_status );

					// cache dir search and delete
					$cache_files = $this->wrap_dirlist( $cache_dir );

					if ( is_array( $cache_files ) ) {
						foreach ( $cache_files as $cache_file ) {
							$wp_filesystem->delete( trim( $cache_dir . $cache_file['name'] ) );
						}
					}

					// ---next
					$cron_status = 'Night>Delete>Files>Realtime tsv';
					$this->set_next_status( $cron_status );
					break;
*/
				case 'Night>Delete>Files>Realtime tsv':
					$this->backup_prev_status( $cron_status );

					$realtime_file  = $readers_dir . 'realtime_view.php';
					if ( $wp_filesystem->exists( $realtime_file ) ) {
						$realtime_ary = $this->wrap_unserialize( $this->wrap_get_contents( $realtime_file ) );
						$day2before_str = $qahm_time->xday_str( -2 );
						$day2before_end = $qahm_time->str_to_unixtime( $day2before_str . ' 23:59:59' );
						
						// 一昨日のデータは不要
						$new_body = array();
						for ( $iii = 0; $iii < count( $realtime_ary['body'] ); $iii++ ) {
							$body = $realtime_ary['body'][$iii];
							$exit_time = $body['last_exit_time'];
							if ( $exit_time <= $day2before_end ) {
								break;
							}
							array_push( $new_body, $body );
						}

						//indexを詰めて保存
						$realtime_ary['body'] = $new_body;
						$this->wrap_put_contents( $realtime_file, $this->wrap_serialize( $realtime_ary ) );
					}

					/*
					foreach ( $tsvlines_ary as $line ) {
						$line_ary  = explode( "\t", $line );
						$exit_time = $line_ary[ self::DATA_REALTIME_VIEW_1['LAST_EXIT_TIME'] ];
						if ( $exit_time <= $day2before_end ) {
							break;
						}
						$newtsvfile_str .= $line;
						$iii++;
					}
					// write new line
					$wp_filesystem->put_contents( $tsvfile, $newtsvfile_str );
					*/

					// ---next
					$cron_status = 'Night>Delete>Files>Raw';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Files>Raw':
					$this->backup_prev_status( $cron_status );

					if ( $wp_filesystem->exists( $del_rawfileslist_file . '_old2.php' ) ) {
						$del_ary       = $wp_filesystem->get_contents_array( $del_rawfileslist_file . '_old2.php' );
						$is_first_line = true;
						foreach ( $del_ary as $del ) {
							if ( ! $is_first_line ) {
								$wp_filesystem->delete( trim( $del ) );
							} else {
								$is_first_line = false;
							}
						}
						$wp_filesystem->delete( $del_rawfileslist_file . '_old2.php' );
					}
					// ---next
					$cron_status = 'Night>Delete>Files>Xmonth ago';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Delete>Files>Xmonth ago':
					$this->backup_prev_status( $cron_status );

					$yearx     = $qahm_time->year();
					$month     = $qahm_time->month();
					$del_month = $month - self::DEFAULT_DELETE_MONTH;
					if ( $del_month <= 0 ) {
						$del_month = 12 + $del_month;
						$yearx     = $yearx - 1;
					}

					// 現在の年月を YYYYMM 形式の整数として計算
					$current_period = $yearx * 100 + $del_month;

					$search_dirs = array( $data_dir );
					// 再帰検索を行いつつdataディレクトリ内の3ヶ月前のファイルを削除していく
					for ( $iii = 0; $iii < count( $search_dirs ); $iii++ ) {   // 再帰のためループ毎にcount関数を実行しなければならない
						$dir = $search_dirs[ $iii ];
						if ( ! $wp_filesystem->is_dir( $dir ) ) {
							continue;
						}

						// 検索対象外のディレクトリ
						if ( false !== strpos( $dir, QAHM_TEXT_DOMAIN . '-data/view/' ) ||
							false !== strpos( $dir, QAHM_TEXT_DOMAIN . '-data/brains/' ) ) {
							continue;
						}

						// ディレクトリ内に存在するファイルのリストを取得
						$file_list = $this->wrap_dirlist( $dir );
						if ( $file_list ) {
							// ディレクトリ内のファイルを全てチェック
							foreach ( $file_list as $file ) {
								// ディレクトリなら再帰検索用の配列にディレクトリを登録
								if ( is_dir( $dir . $file['name'] ) ) {
									$search_dirs[] = $dir . $file['name'] . '/';
								} else {
									// 削除対象外のファイル
									if ( $file['name'] === '.donotbackup' ||	// JetPack用ファイル
										 $file['name'] === 'qa-config.php' ) {	// QA設定ファイル
										continue;
									}
									// ファイルの更新日時を取得
									$file_date  = $qahm_time->unixtime_to_str( $file['lastmodunix'] );
									$file_year  = $qahm_time->year( $file_date );
									$file_month = $qahm_time->month( $file_date );

									// ファイルの更新年月を YYYYMM 形式で計算
									$file_period = $file_year * 100 + $file_month;

									// 現在からDEFAULT_DELETE_MONTHを引いた月よりファイルの更新月が古いかどうかで判断
									if ( $file_period <= $current_period ) {
										if ( ! $wp_filesystem->delete( $dir . $file['name'] ) ) {
											$qahm_log->error( '$wp_filesystem->delete()に失敗しました。パス：' . $dir . $file['name'] );
										}
									}
								}
							}
						}
					}

					// ---next
					$cron_status = 'Night>Delete>Files>View dir';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;


				case 'Night>Delete>Files>View dir':
					$this->backup_prev_status( $cron_status );

					$base_date = $qahm_time->today_str();
					$leap_date = $qahm_time->year() . '-02-29';
					if ( $qahm_time->xday_num( $base_date, $leap_date ) !== 0 ) {

						$del_date = $this->wrap_get_option( 'data_retention_dur' );
						if ( $del_date ) {
							$del_date = $qahm_time->diff_str( $base_date, '-' . $del_date . ' day' );
							$del_date = $qahm_time->diff_str( $del_date, '-1 day' );
						} else {
							$del_date = $qahm_time->diff_str( $base_date, '-' . self::DEFAULT_DELETE_DATA_DAY . ' day' );
						}

						$search_dirs = array( $data_dir . 'view/' );
						// 再帰検索を行いつつview_pv
						for ( $iii = 0; $iii < count( $search_dirs ); $iii++ ) {   // 再帰のためループ毎にcount関数を実行しなければならない
							$dir = $search_dirs[ $iii ];
							if ( $wp_filesystem->is_dir( $dir ) && $wp_filesystem->exists( $dir ) ) {
								$is_pv_search = false;
								$is_sm_search = false;
								$is_hm_search = false;
			
								// 調査対象のディレクトリか判定
								if ( 0 === strpos( substr( $dir, -strlen( 'view_pv/' ) ), 'view_pv/' ) ) {
									$is_pv_search = true;
								}
								
								if ( 0 === strpos( substr( $dir, -strlen( 'summary/' ) ), 'summary/' ) ) {
									$is_pv_search = true;
									$is_sm_search = true;
								}
			
								if ( 0 === strpos( substr( $dir, -strlen( 'raw_p/' ) ), 'raw_p/' ) ||
									0 === strpos( substr( $dir, -strlen( 'raw_c/' ) ), 'raw_c/' ) ||
									0 === strpos( substr( $dir, -strlen( 'raw_e/' ) ), 'raw_e/' ) ) {
									$is_hm_search = true;
								}
			
								// ディレクトリ内に存在するファイルのリストを取得
								$file_list = $this->wrap_dirlist( $dir );
								if ( $file_list ) {
									// ディレクトリ内のファイルを全てチェック
									foreach ( $file_list as $file ) {
										// 対象viewディレクトリの処理
										if ( $is_pv_search ) {
											// ディレクトリなら再帰検索用の配列にディレクトリを登録
											if ( is_dir( $dir . $file['name'] ) ) {
												$search_dirs[] = $dir . $file['name'] . '/';
											} else {
												$is_days_file = false;
												if ( $is_sm_search ) {
													if ( 'days_access.php' === $file['name'] || 'days_access_detail.php' === $file['name'] ) {
														$days_ary = $this->wrap_unserialize( $this->wrap_get_contents( $dir . $file['name'] ) );
														if ( $days_ary ) {
															for ( $days_idx = 0, $days_max = count( $days_ary ); $days_idx < $days_max; $days_idx++ ) {
																$f_date = $days_ary[$days_idx]['date'];
																$diff_day = $qahm_time->xday_num( $f_date, $del_date );
			
																// 期限が過ぎた時の処理
																if ( 0 >= $diff_day ) {
																	unset( $days_ary[$days_idx] );
																}
															}
															$days_ary = array_values( $days_ary );
															$this->wrap_put_contents( $dir . $file['name'], $this->wrap_serialize( $days_ary ) );
														}
														$is_days_file = true;
													}
												}
			
												if ( ! $is_days_file ) {
													$f_date   = substr( $file['name'], 0, 10 );
													$diff_day = $qahm_time->xday_num( $f_date, $del_date );
			
													// 期限が過ぎた時の処理
													if ( 0 >= $diff_day ) {
														$this->wrap_delete( $dir . $file['name'] );
													}
												}
											}
										// rawディレクトリの処理
										} elseif ( $is_hm_search ) {
											if ( ! is_dir( $dir . $file['name'] ) ) {
												$f_date   = substr( $file['name'], 0, 10 );
												$diff_day = $qahm_time->xday_num( $f_date, $del_date );
			
												// 期限が過ぎた時の処理
												if ( 0 >= $diff_day ) {
													$this->wrap_delete( $dir . $file['name'] );
												}
											}
										// 上記以外の処理
										} else {
											// ディレクトリなら再帰検索用の配列にディレクトリを登録
											if ( is_dir( $dir . $file['name'] ) ) {
												$search_dirs[] = $dir . $file['name'] . '/';
											}
										}
									}
								}
							}
						}
					}

					// ---next
					$cron_status = 'Night>Delete>Files>End';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;
					
				case 'Night>Delete>Files>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>Db>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Db>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>Db>Truncate partition';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Db>Truncate partition':
					$this->backup_prev_status( $cron_status );

					$yearx     = $qahm_time->year();
					$month     = $qahm_time->month();
					$data_save_month = self::DATA_SAVE_MONTH;
					// delete same_month -1
					$del_month = $month - $data_save_month -1;
					if ( $del_month <= 0 ) {
						$del_month = 12 + $del_month;
						$yearx     = $yearx - 1;
					}
					$del_month = sprintf( '%02d', $del_month );

					$del_partition_name = 'p' . $yearx . $del_month;


					// qa_pv_log
					$table_name = $wpdb->prefix . 'qa_pv_log';
					$query      = 'ALTER TABLE ' . $table_name . ' TRUNCATE PARTITION ' . $del_partition_name;
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$result     = $wpdb->query( $wpdb->prepare($query) );

					// qa_page_version_hist
					$table_name = $wpdb->prefix . 'qa_page_version_hist';
					$query      = 'ALTER TABLE ' . $table_name . ' TRUNCATE PARTITION ' . $del_partition_name;
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$result     = $wpdb->query( $wpdb->prepare($query) );

//20240401 delete 1years ago for readers
                    $yearx_1y     = $qahm_time->year();
                    $month_1y     = $qahm_time->month();
                    $data_save_month_1y = self::DATA_SAVE_ONE_YEAR;
// delete same_month -1
                    $del_month_1y = $month_1y - $data_save_month_1y - 1;
                    while ( $del_month_1y <= 0 ) {
                        $del_month_1y += 12;
                        $yearx_1y -= 1;
                    }
                    $del_month_1y = sprintf( '%02d', $del_month_1y );

                    $del_partition_name_1y = 'p' . $yearx_1y . $del_month_1y;
                    // qa_readers
                    $table_name = $wpdb->prefix . 'qa_readers';
                    $query      = 'ALTER TABLE ' . $table_name . ' TRUNCATE PARTITION ' . $del_partition_name_1y;
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This SQL query uses $wpdb->prepare(), but it may trigger warnings due to the dynamic construction of the SQL string. Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
                    $result     = $wpdb->query( $wpdb->prepare($query) );

					// ---next
					$cron_status = 'Night>Delete>Db>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>Db>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Delete>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Delete>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make summary file>Start';
					$this->set_next_status( $cron_status );
					break;


				// ----------
				// Making Summary File
				// ----------
				case 'Night>Make summary file>Start':
					$this->backup_prev_status( $cron_status );

					//check dir
					//view_base
					if ( ! $wp_filesystem->exists( $vw_summary_dir ) ) {
						$wp_filesystem->mkdir( $vw_summary_dir );
					}
					$cron_status = 'Night>Make summary file>Days access>Start';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>Make summary file>Days access>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make summary file>Days access>Make loop';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make summary file>Days access>Make loop':
					$this->backup_prev_status( $cron_status );

					$days_access_ary = [];
					/*
					$s_datetime = '1999-12-31 00:00:00';
					if ( $wp_filesystem->exists( $summary_days_access_file ) ) {
						$days_access_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_days_access_file ) );
						if ( 0 < count( $days_access_ary ) ) {
							//　結局、毎月DBから上書きして値が増えるので、DBの期間分は上書きが必要
							// x month ago
							$yearx = $qahm_time->year();
							$month = $qahm_time->month();
							$data_save_month = self::DATA_SAVE_MONTH;
							//  same_month
							$save_yearx = $yearx;
							$save_month = $month - $data_save_month;
							if ( $save_month <= 0 ) {
								$save_month = 12 + $save_month;
								$save_yearx = $yearx - 1;
							}
			
							//below is ok
							$save_month = sprintf('%02d', $save_month);
							$s_datetime = $save_yearx . '-' . $save_month . '-01 00:00:00';
						}
					}*/
			
					//search
					$start_idx = 0;
					/*
					foreach ( $days_access_ary as $idx => $days_access ) {
						if ( isset( $days_access['sum_datetime'] ) ) {
							if ( ($qahm_time->now_unixtime() - 3 * 60 * 60) < $qahm_time->str_to_unixtime($days_access['sum_datetime'])) {
								//本日集計済みなので、この日付は飛ばすべき
								$s_datetime = $days_access['date'] . ' 23:59:59';
								$start_idx = $idx + 1;
							}
						} else {
							//dummyの古い値を入れる
							$tmpary = array_merge( $days_access, array('sum_datetime' => '1999-12-31 00:00:00') );
							$days_access_ary[$idx] = $tmpary;
						}
						if ( isset( $days_access['date'])) {
							$ary_datetime = $days_access['date'] . ' 00:00:00';
							if ( $qahm_time->str_to_unixtime($s_datetime) <= $qahm_time->str_to_unixtime($ary_datetime)) {
								if ($start_idx === 0) {
									$start_idx = $idx;
								}
							}
						}
					}
					if ( count($days_access_ary) <= $start_idx && $start_idx !== 0 ) {
						$start_idx = -1;
					}*/

					// search view_pv dir
					$allfiles = $this->wrap_dirlist( $viewpv_dir );
					if ($allfiles) {

						// 指定期間内全て、データ数0の配列を作成
						$start_date = null;
						$end_date   = null;
						$filename = $allfiles[0]['name'];
						if ( is_file( $viewpv_dir . $filename ) ) {
							$start_date = substr( $filename, 0, 10 );
						}
						$end_date = $qahm_time->xday_str( -1 );
						if ( $start_date !== null && $end_date !== null ) {
							// DateTimeオブジェクトを作成
							$start_datetime = new DateTime($start_date);
							$end_datetime = new DateTime($end_date);

							// 終了日に1日追加（終了日を含めるため）
							$end_datetime = $end_datetime->modify('+1 day');

							// 日付間隔を設定（1日ごと）
							$interval = new DateInterval('P1D');

							// DatePeriodで指定期間の日付を取得
							$date_range = new DatePeriod($start_datetime, $interval ,$end_datetime);

							// DatePeriodをループして日付を配列に追加
							foreach($date_range as $date){
								$days_access_ary[] = array( 'date' => $date->format('Y-m-d'), 'pv_count' => 0, 'session_count' => 0, 'user_count' => 0, 'sum_datetime' =>  $qahm_time->now_str());
							}

							// view_pv search
							foreach ( $allfiles as $file ) {
								$filename = $file[ 'name' ];
								if ( is_file( $viewpv_dir . $filename ) ) {
									$f_date = substr( $filename, 0, 10 );
									$f_datetime = $f_date . ' 00:00:00';
									//if ( $qahm_time->str_to_unixtime( $s_datetime )  <= $qahm_time->str_to_unixtime( $f_datetime ) ) {
										//集計対象
										$view_pv_ary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
										$pv_cnt      = count( $view_pv_ary );
										$session_cnt = 0;
										$all_readers = [];
										foreach ( $view_pv_ary as $pv_ary ) {
											//count session 当日の1ページ目（LP着地）をカウント
											if ( (int)$pv_ary['pv'] === 1 ) {
												++$session_cnt;
												$all_readers[] = (int)$pv_ary['reader_id'];
											}
										}
										$user_cnt = count( array_unique( $all_readers, SORT_NUMERIC ) );
										//set array
										$access_ary = array( 'date' => $f_date, 'pv_count' => $pv_cnt, 'session_count' => $session_cnt, 'user_count' => $user_cnt, 'sum_datetime' =>  $qahm_time->now_str());
				
										// 今回のファイルは既存aryの中にないので追加する
										//if ($start_idx < 0 ) {
										//	$days_access_ary[] = $access_ary;
										// 今回の再計算した対象ファイルは既存aryの中に入る予定なので、どこに追加するかをチェック
										//} else {
											$is_find = false;
											$afterary = [];
											//既存aryの中で一致する日付を検索していれる
											for ($ddd = $start_idx; $ddd < count($days_access_ary); $ddd++ ) {
												if ( isset( $days_access_ary[$ddd]['date'])) {
													$ary_datetime = $days_access_ary[$ddd]['date'] . ' 00:00:00';
													if ( $qahm_time->str_to_unixtime( $ary_datetime ) <= $qahm_time->str_to_unixtime( $f_datetime )) {
														$start_idx++;
													} else {
														$afterary[] = $days_access_ary[$ddd];
													}
													if ($days_access_ary[$ddd]['date'] === $f_date) {
														$days_access_ary[$ddd] = $access_ary;
														$is_find = true;
														break;
													}
												}
											}
											//まったく見つからなかった場合は、aryのおしりか間に追加
											if ( ! $is_find) {
												//そもそも日付がオーバーした時は、おしりに追加
												if ( count($days_access_ary) <= $start_idx ) {
													$days_access_ary[] = $access_ary;
													//以後の日付はお尻に追加
													$start_idx = -1;
												//日付がオーバーしていない場合は、間に追加
												} else {
													$new_days_access_ary = [];
													for ( $ccc = 0; $ccc < $start_idx; $ccc++ ) {
														$new_days_access_ary[] = $days_access_ary[$ccc];
													}
													//start_idxのところに挿入
													$new_days_access_ary[] = $access_ary;
													//お尻はいままで通り
													for ( $ccc = 0; $ccc < count($afterary); $ccc++ ) {
														$new_days_access_ary[] = $afterary[$ccc];
													}
													$days_access_ary = $new_days_access_ary;
													// 次の$fileの日付検索は次のstart_idxから
													$start_idx++;
													if ( count($days_access_ary) <= $start_idx ) {
														//以後の日付はお尻に追加
														$start_idx = -1;
													}
												}
											}
										//}
										//write today access
										$this->wrap_put_contents( $summary_days_access_file, $this->wrap_serialize( $days_access_ary ) );
				
										// startするdatetimeは次の日付になる。
										//$s_datetime  = $qahm_time->xday_str( 1, $f_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
									//}
								}
							}
						}
					}
					$cron_status = 'Night>Make summary file>Days access detail>Start';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make summary file>Days access detail>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make summary file>Days access detail>Make loop';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make summary file>Days access detail>Make loop':
					$this->backup_prev_status( $cron_status );
					global $qahm_db;

					$qahm_db->make_summary_days_access_detail();

					$cron_status = 'Night>Make summary file>Days access detail>End';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Make summary file>Days access detail>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make summary file>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make summary file>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>SC get>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Making Search Console File
				// ----------
				case 'Night>SC get>Start':
					$current_unixtime = $qahm_time->now_unixtime();
					$this->wrap_put_contents( $temp_dir . 'cron_first_scget_time.php', $current_unixtime );
					
					// ---next
					$cron_status = 'Night>SC get>Each day>Start';
					$this->set_next_status( $cron_status );
					break;


				case 'Night>SC get>Each day>Start':

					// 最初のヤツから30分以上経っていたら、このcase (SC get>Eachday)は終了
					$first_scget_unixtime = $this->wrap_get_contents( $temp_dir . 'cron_first_scget_time.php' );				
					$elapsed_time = $qahm_time->now_unixtime() - $first_scget_unixtime;					
					if ( $elapsed_time >= ( 60 * 30 ) ) {
						$qahm_log->error( 'GSC取得で30分以上経過しました。' );
						$cron_status = 'Night>SC get>Each day>End';
						$this->set_next_status( $cron_status );
						break;
					}

					$is_init = $qahm_google_api->init(
						'Google API Integration',
						array( 'https://www.googleapis.com/auth/webmasters.readonly' ),
						admin_url( 'admin.php?page=qahm-config' )
					);
					if ( ! $is_init ) {
						$cron_status = 'Night>SC get>Each day>End';
						$this->set_next_status( $cron_status );
						break;
					}

					// ループ開始
					$gsc_loop_start_time = $qahm_time->now_str();			
					$loop_limit_sec    = 60 * 4.5; // 4分半でループ抜ける  < max_execution_time
					$timelimit_end_loop = false;
					
					$first_y = $qahm_time->year();
					$first_m = null;
					$first_d = null;

					$last_y   = $first_y - 2; //サーチコンソールAPIで取得できる情報の上限は486日前まで
					$last_m   = 1;
					$last_d   = 1;

					$sc_max_search_cnt = 486;
					$qa_max_search_cnt = $qahm_time->xday_num( $qahm_time->now_str(), $this->get_pvterm_start_date() );
					$max_search_cnt    = $sc_max_search_cnt < $qa_max_search_cnt ? $sc_max_search_cnt : $qa_max_search_cnt;
					$now_search_cnt    = 0;

					$is_loop_end = false;
					for ( $y = $first_y; $y >= $last_y; $y-- ) {
						if ( $first_m === null ) {
							$first_m = $qahm_time->month();
						} else {
							$first_m = 12;
						}

						for ( $m = $first_m; $m >= $last_m; $m-- ) {
							if ( $first_d === null ) {
								$first_d = $qahm_time->day();
							} else {
								$first_d = (int) gmdate( 't', strtotime( 'last day of ' . $y . '-' . $m ) );
							}

							// 一日ごとのデータ
							for ( $d = $first_d; $d >= $last_d; $d-- ) {								
								if ( $now_search_cnt > $max_search_cnt ) {
									$is_loop_end = true;
									break;
								}
								
								$date = sprintf( '%04d-%02d-%02d', $y, $m, $d );
								$qahm_google_api->insert_search_console_keyword( $date, $date );
								$qahm_google_api->create_search_console_data( $date, $date, false );
								$now_search_cnt++;

								if ( $qahm_time->xsec_num( $qahm_time->now_str(), $gsc_loop_start_time ) > $loop_limit_sec ) {
									$timelimit_end_loop = true;
									$is_loop_end = true;
									break;
								}
							}
							if ( $is_loop_end ) {
								break;
							}

							// 月データ
							$start_date = sprintf( '%04d-%02d-01', $y, $m );
							$end_date = sprintf( '%04d-%02d-%02d', $y, $m, $first_d );
							$qahm_google_api->create_search_console_data( $start_date, $end_date, true );

							if ( $qahm_time->xsec_num( $qahm_time->now_str(), $gsc_loop_start_time ) > $loop_limit_sec ) {
								$timelimit_end_loop = true;
								$is_loop_end = true;
								break;
							}
						}
						if ( $is_loop_end ) {
							break;
						}
					}

					if ( $timelimit_end_loop ) {
						// ループ強制終了＝時間がかかり過ぎているので、現在立ち上がっているcron自体を強制終了
						$qahm_log->error( '4分30秒以上経ちました。終了します。' );
						die();
					}
					
					// ---next
					$cron_status = 'Night>SC get>Each day>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>SC get>Each day>End':

					// ---next
					$cron_status = 'Night>SC get>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>SC get>End':
					$this->wrap_delete( $temp_dir . 'cron_first_scget_time.php' );
										
					// ---next
					$cron_status = 'Night>Make cache file>Start';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Making Cache File
				// ----------
				case 'Night>Make cache file>Start':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make cache file>Admin heatmap>Start';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make cache file>Admin heatmap>Start':
					$this->backup_prev_status( $cron_status );

					if ( $wp_filesystem->exists( $cache_heatmap_list_idx_temp_file ) ) {
						$wp_filesystem->delete( $cache_heatmap_list_idx_temp_file );
					}
					// ---next
					$cron_status = 'Night>Make cache file>Admin heatmap>Create heatmap list';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make cache file>Admin heatmap>Create heatmap list':
					$this->backup_prev_status( $cron_status );

					//$heatmap_list = $qahm_admin_page_heatmap->create_heatmap_list();
					//if ( $heatmap_list ) {
					//	$this->wrap_put_contents( $cache_heatmap_list_temp_file, $this->wrap_serialize( $heatmap_list ) );
						$cron_status = 'Night>Make cache file>Admin heatmap>Add version info';
					//} else {
					//	$cron_status = 'Night>Make cache file>Admin heatmap>End';
					//}

					// ---next
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make cache file>Admin heatmap>Add version info':
					$this->backup_prev_status( $cron_status );

					//if ( empty( $heatmap_list ) ) {
					//	$heatmap_list = $this->wrap_unserialize( $this->wrap_get_contents( $cache_heatmap_list_temp_file ) );
					//}
					//$heatmap_list = $qahm_admin_page_heatmap->add_version_info( $heatmap_list );
					//$this->wrap_put_contents( $cache_heatmap_list_file, $this->wrap_serialize( $heatmap_list ) );

					$qahm_admin_page_heatmap->delete_refresh_info();

					// ---next
					$cron_status = 'Night>Make cache file>Admin heatmap>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make cache file>Admin heatmap>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Make cache file>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Make cache file>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>Announce friend plan>Start';
					$this->set_next_status( $cron_status );
					$while_continue = false;
					break;

				case 'Night>Announce friend plan>Start':
					$this->backup_prev_status( $cron_status );
					
					// ---next
					$cron_status = 'Night>Announce friend plan>Monthly process';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Announce friend plan>Monthly process':
					$this->backup_prev_status( $cron_status );

					// 簡易的なフラグ操作。毎月1日にフレンド案内フラグを立てる
					// Day>Session checkでもPV上限によってフラグを立てている
					if ( $qahm_time->day() === 1 ) {
						$this->wrap_update_option( 'announce_friend_plan', true );
					}
					// ---next
					$cron_status = 'Night>Announce friend plan>End';
					$this->set_next_status( $cron_status );
					break;

				case 'Night>Announce friend plan>End':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Night>End';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// Night End
				// ----------
				case 'Night>End':
					$this->backup_prev_status( $cron_status );

					if ( ! $this->wrap_put_contents( $is_night_comp_file, '1' ) ) {
						throw new Exception( 'cronのnight do file生成に失敗しました。終了します。' );
					}
					// ---next
					$cron_status = 'Cron end';
					$this->set_next_status( $cron_status );
					break;

				// ----------
				// End
				// ----------
				case 'Cron end':
					$this->backup_prev_status( $cron_status );

					// ---next
					$cron_status = 'Idle';
					$this->set_next_status( $cron_status );
					break;

				case 'Idle':
					$this->backup_prev_status( $cron_status );

					$while_continue = false;
					break;

				case 'error':
					$this->backup_prev_status( $cron_status );

					$while_continue = false;
					break;

				default:
					$this->backup_prev_status( $cron_status );

					$cron_status = 'Idle';
					$this->set_next_status( $cron_status );
					break;
			}
			usleep( '30' );
			++$while_lpcnt;
			if ( $while_lpcnt > self::MAX_WHILECOUNT ) {
					$while_continue = false;
			}
		}

		// ----------
		// Last,delete cron lock
		// ----------
		if ( ! $wp_filesystem->delete( $this->get_cron_lock_path() ) ) {
			throw new Exception( '$wp_filesystem->delete()に失敗しました。パス：' . esc_html( $this->get_cron_lock_path() ) );
		}
	}

} // end of class
