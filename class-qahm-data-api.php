<?php
/**
 * qa関連のdataをひいてくるAPI
 * qahm-dbクラスを通じてデータを取得する。
 * 初期APIとしては、自プラグインデータ閲覧画面からのajaxリクエストを裁くAPIを想定するが、今後は、他のプラグインやウェブサービス上からデータを取得できるようにする可能性がある
 * （しかし、その場合はバージョン管理が必須。かつ要セキュリティ対応？プラグインはDB直接参照できるのでセキュリティは不要な気もする。そもそもデータは匿名化している）
 * どちらにせよ、初期はバージョン管理が容易な自プラグインのためのAPIだけを作成する。
 * 他所からの参照にはajaxだけでなくURLによるget やdeleteなどのmethod対応も考えられ、WordPressのRESTを使う可能性もあり、別ファイルで管理すべきだと思われる
 * @package qa_heatmap
 */

$qahm_data_api = new QAHM_Data_Api();
class QAHM_Data_Api extends QAHM_Db {
	/**
	 *
	 */
	const NONCE_API  = 'api';
	public function __construct() {
		$this->regist_ajax_func( 'ajax_select_data' );
		$this->regist_ajax_func( 'ajax_get_pvterm_start_date' );
		$this->regist_ajax_func( 'ajax_save_first_launch' );
		$this->regist_ajax_func( 'ajax_save_siteinfo' );
		$this->regist_ajax_func( 'ajax_save_goal_x' );
		$this->regist_ajax_func( 'ajax_delete_goal_x' );
		$this->regist_ajax_func( 'ajax_get_goals_sessions' );
		$this->regist_ajax_func( 'ajax_url_to_page_id' );
		$this->regist_ajax_func( 'ajax_get_each_posts_count' );
		$this->regist_ajax_func( 'ajax_get_nrd_data' );
		$this->regist_ajax_func( 'ajax_get_ch_data' );
		$this->regist_ajax_func( 'ajax_get_sm_data' );
		$this->regist_ajax_func( 'ajax_get_lp_data' );
		$this->regist_ajax_func( 'ajax_get_gw_data' );
		$this->regist_ajax_func( 'ajax_get_ap_data' );
		$this->regist_ajax_func( 'ajax_get_nonce' );

		// wordpressはコアの初期化→dbの初期化→その他の初期化（プラグイン含む）といった流れになるので
		// コンストラクタの時点でおそらくwpdbが読み込まれているはず
		// ダメならフックを用いて以下の処理の実行タイミングを変えるべき imai
		global $wpdb;
		$this->prefix = $wpdb->prefix;
	}

	/**
	 * public function
	 */
	public function ajax_select_data(){
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$table      = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'table' ) );
		$column     = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'select' ) );
		$date_or_id = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date_or_id' ) ) );
		$count      = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'count' ) );
		$where      = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'where' ) );

		if ( $count === 'true' || $count == 1 ) {
			$count = true;
		} else {
			$count = false;
		}

		$resary     = $this->select_data( $table, $column, $date_or_id, $count, $where );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();

	}
	public function ajax_get_pvterm_start_date() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		$res     = $this->get_pvterm_start_date();
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($res);
		die();
	}

	/**
	 * public function
	 */

	// 初回起動時に表示される画面
	public function ajax_save_first_launch(){
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API ) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}

		$this->wrap_update_option( 'plugin_first_launch', false );
		$this->wrap_update_option( 'send_email_address', get_option( 'admin_email' ) );

		header("Content-type: application/json; charset=UTF-8");
		echo( '{"success":true}' );
		die();
	}

	//目標設定用
	public function ajax_save_siteinfo(){
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API ) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		if ( ! $this->check_qahm_access_cap( 'qahm_manage_settings' ) ) {
			http_response_code( 400 );
			die( 'cap error' );
		}
		global $qahm_time;

		$target_customer = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'target_customer' ) );
		$sitetype = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'sitetype' ) );
		$membership = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'membership' ) );
		$payment = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'payment' ) );
		$month_later = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'month_later' ) );
		$session_goal = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'session_goal' ) );


		$goalday        = $qahm_time->xmonth_str( $month_later );
		$goaldaysession = floor($session_goal / 30);

		$siteinfo_ary = [
			'target_customer'      =>  $target_customer,
			'sitetype'  =>  $sitetype,
			'membership'  =>  $membership,
			'payment'       =>  $payment,
			'month_later'  =>  $month_later,
			'session_goal' =>  $session_goal,
			'goalday' =>  $goalday,
			'goaldaysession' =>  $goaldaysession,
			];
		$siteinfo_json_new = wp_json_encode( $siteinfo_ary );
		$savestatus = $this->wrap_update_option('siteinfo', $siteinfo_json_new );
		header("Content-type: application/json; charset=UTF-8");
		echo( '{"success":true}' );
		die();
	}

	public function get_siteinfo_array() {
		$goals_ary  = [];
		$goals_json = $this->wrap_get_option( 'siteinfo' );
		if ($goals_json) {
			$goals_ary  = json_decode( $goals_json, true );
		}
		return $goals_ary;
	}

	public function get_siteinfo_json() {
		$goals_json = $this->wrap_get_option( 'siteinfo' );
		return $goals_json;
	}


	public function ajax_save_goal_x() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		if ( ! $this->check_qahm_access_cap( 'qahm_manage_settings' ) ) {
			http_response_code( 400 );
			die( 'cap error' );
		}
		
		$gid         = $this->alltrim( $this->wrap_filter_input( INPUT_POST,      'gid' ) ) ;
		$gtitle      = $this->alltrim( $this->wrap_filter_input( INPUT_POST,   'gtitle' ) ) ;
		$gnum_scale  = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'gnum_scale' ) ) ;
		$gnum_value  = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'gnum_value' ) ) ;
		$gtype       = $this->alltrim( $this->wrap_filter_input( INPUT_POST,    'gtype' ) ) ;
		$g_goalpage  = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_goalpage' ) ) ;
		$g_pagematch = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_pagematch' ) ) ;
		$g_clickpage = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_clickpage' ) ) ;
		$g_eventtype = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_eventtype' ) ) ;
		$g_clickselector = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_clickselector' ) ) ;
		$g_eventselector = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'g_eventselector' ) ) ;


		//validate
		if ( !$gtitle ) {
			http_response_code( 401 );
			die('title error');
		}
		switch ($gtype) {
			case 'gtype_page':
				if ( !$g_goalpage ) {
					http_response_code( 402 );
					die('required null error');
				}
				break;

			case 'gtype_click':
				if ( !$g_clickselector ) {
					http_response_code( 402 );
					die('required null error');
				}
				break;

			case 'gtype_event':
				if ( !$g_eventselector ) {
					http_response_code( 402 );
					die('required null error');
				}
				break;

			default:
				http_response_code( 402 );
				die('required null error');
				break;
		}
		//save goal_x
		$goals_json_ary = [];
		$goals_json = $this->wrap_get_option( 'goals' );
		if ( $goals_json ) {
			$goals_json_ary = json_decode( $goals_json, true );
		}

		$goal_ary = [
			'gtitle'      =>  $gtitle,
			'gnum_scale'  =>  $gnum_scale,
			'gnum_value'  =>  $gnum_value,
			'gtype'       =>  $gtype,
			'g_goalpage'  =>  $g_goalpage,
			'g_pagematch' =>  $g_pagematch,
			'g_clickpage' =>  $g_clickpage,
			'g_eventtype' =>  $g_eventtype,
			'g_clickselector' => $g_clickselector,
			'g_eventselector' => $g_eventselector,
			'pageid_ary' => []
			];

		$pageid_ary = [];
		switch ( $gtype ) {
			case 'gtype_event':
				$pageid_ary = array( 'page_id' => null );
				//デリミタチェック
				$correct_regex = true;
				if ( strlen($g_eventselector) < 3 ) {
					$correct_regex = false;
				}
			
				$validModifiers = 'imsxeADSUXJu';
				$startDelimiter = $g_eventselector[0];			
				$patternWithoutModifiers = rtrim( $g_eventselector, $validModifiers );
				$endDelimiter = $patternWithoutModifiers[strlen($patternWithoutModifiers) - 1];
				
				if ( $startDelimiter !== $endDelimiter ) {
					$correct_regex = false;
				}			

				$validDelimiters = '/#~';
				if ( strpos($validDelimiters, $startDelimiter ) === false) {
					$correct_regex = false;
				}
				// 終了デリミタがエスケープされていないことを確認
				if ( strlen($g_eventselector ) > 3 && $g_eventselector[strlen($g_eventselector) - 2] === '\\' ) {
					$correct_regex = false;
				}
				// 開始デリミタの直後に「?, +, *, {}, (), []」などの量指定子やメタ文字が来ないことを確認
				$invalidAfterDelimiter = '/^[\/#~][?+*{}()[\]]/';
				if ( preg_match($invalidAfterDelimiter, $g_eventselector) ) {
					$correct_regex = false;
				}
				if ( $correct_regex === false ) {
					header("Content-type: application/json; charset=UTF-8");
					echo( wp_json_encode('wrong_delimiter') );
					die();
				}
				break;

			case 'gtype_click':
				$pageid_ary = $this->url_to_page_id( $g_clickpage, false );
				if( ! $pageid_ary ) {
					header("Content-type: application/json; charset=UTF-8");
					echo( wp_json_encode('no_page_id') );
					die();
				}
				break;

			case 'gtype_page':
			default:
				$match_prefix = false;
				if ( $g_pagematch === 'pagematch_prefix' ) {
					$match_prefix = true;
				}
				$pageid_ary = $this->url_to_page_id( $g_goalpage, $match_prefix );
				if( ! $pageid_ary ) {
					header("Content-type: application/json; charset=UTF-8");
					echo( wp_json_encode('no_page_id') );
					die();
				}
				break;
		}
		$goal_ary['pageid_ary'] = $pageid_ary;

		//save now ary and overwrite
		$save_goal_ary = $goals_json_ary[$gid];
		$goals_json_ary[ $gid ] = $goal_ary;

		$goals_json_new = wp_json_encode( $goals_json_ary );
		$savestatus = $this->wrap_update_option('goals', $goals_json_new );
		if ( $savestatus ) {
			// 目標条件が変更されていたら直近2ヶ月のサマリーファイルを作ってしまい、クライアントに件数を返す。
			// サマリーファイルにより今後の処理が早くなるし、ユーザーに条件が正しいことも伝えられる。
			$is_recalc = false;
			if ( $goal_ary['gtype'] !== $save_goal_ary['gtype'] ) {
				$is_recalc = true;
			} else {
				$gary = array_values( $goal_ary );
				$sary = array_values( $save_goal_ary );
				for ( $iii = 4; $iii < count( $gary ); $iii++ ) {
					if ( $gary[$iii] !== $sary[$iii] ) {
						switch ( $goal_ary['gtype'] ) {
							case 'gtype_page':
								if ( $iii === 4 || $iii === 5 ) {
									$is_recalc = true;
								}
								break;

							case 'gtype_click':
								if ( $iii === 6 || $iii === 8 ) {
									$is_recalc = true;
								}
								break;

							case 'gtype_event':
								if ( $iii === 7 || $iii === 9 ) {
									$is_recalc = true;
								}
								break;

							default:
								break;
						}
					}
				}
			}
			$session_count = 'no change';
			if ($is_recalc) {
				global $qahm_time;
				$this->delete_goal_X_file( $gid );
				$g_endday  = $qahm_time->xday_str( -1 );
				$g_end_ym  = substr( $g_endday, 0, 7 );
				$g_sttday  = $qahm_time->xmonth_str( -1, $g_end_ym . '-01');
				$dateterm  = 'date = between ' . $g_sttday . ' and '. $g_endday;
				$session_ary   = $this->get_goals_sessions( $dateterm );
				$session_count = count( $session_ary[$gid] );
			}
			header("Content-type: application/json; charset=UTF-8");
			echo wp_json_encode( array( 'count' => esc_html( $session_count ) ) );
			die();
		} else {
			http_response_code( 500 );
			die('options save error');
		}
	}

	public function ajax_delete_goal_x(){
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API ) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		if ( ! $this->check_qahm_access_cap( 'qahm_manage_settings' ) ) {
			http_response_code( 400 );
			die( 'cap error' );
		}
		$gid  = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'gid' ) );
		if ( is_numeric( $gid ) ) {
			$stat = $this->delete_goal_x( (int)$gid );
		} else {
			$stat = false;
		}
		if ( $stat ) {
			header("Content-type: application/json; charset=UTF-8");
			echo( '{"save":"success"}' );
			die();
		} else {
			http_response_code( 500 );
			die('options save error');
		}

	}

	public function delete_goal_x( $gid ) {
		$gary = $this->get_goals_array();
		$nary = [];
		if ( count( $gary ) < $gid ) {
			return false;
		}
		for ( $iii = 1; $iii <= count($gary); $iii++ ) {
			if ( $iii < $gid ) {
				$nary[$iii] = $gary[$iii];
			} elseif ( $iii === $gid ) {
				$donothing = true;
			} else {
				$nary[$iii - 1] = $gary[$iii];
			}
		}
		$goals_json_new = wp_json_encode( $nary );
		$savestatus = $this->wrap_update_option('goals', $goals_json_new );
		if ( $savestatus ) {
			$this->delete_goal_X_file( $gid );
			for ( $iii = 1; $iii <= count($gary); $iii++ ) {
				if ( $gid < $iii ) {
					$this->rename_goal_X_file( $iii, $iii - 1 );
				}
			}			
		}
		return $savestatus;
	}

	public function get_goals_array() {
		$goals_ary  = [];
		$goals_json = $this->wrap_get_option( 'goals' );
		if ($goals_json) {
			$goals_ary  = json_decode( $goals_json, true );
		}
		return $goals_ary;
	}

	public function get_goals_json() {
		$goals_json = $this->wrap_get_option( 'goals' );
		return $goals_json;
	}

	public function ajax_get_goals_sessions(){
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );
		$resary     = $this->get_goals_sessions( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();

	}

	public function delete_goal_X_file( $gid ) {
		global $wp_filesystem;

		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';

		$searchstr = '_goal_' . (string)$gid;
		$summary_files = $session_files = $this->wrap_dirlist( $summary_dir );
		if ( $summary_files ) {
			foreach ( $summary_files as $fileobj ) {
				$filename = $fileobj['name'];
				$find = strpos( $fileobj['name'], $searchstr );
				if ( $find !== false ) {
					if ( $wp_filesystem->exists( $summary_dir . $filename ) ) {
						$wp_filesystem->delete( $summary_dir . $filename );
					}
				}
			}
		}
	}

	public function rename_goal_X_file( $old_gid, $new_gid ) {
		global $wp_filesystem;

		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';

		$old_searchstr = '_goal_' . (string)$old_gid;
		$new_searchstr = '_goal_' . (string)$new_gid;
		$summary_files = $session_files = $this->wrap_dirlist( $summary_dir );
		if ( $summary_files ) {
			foreach ( $summary_files as $fileobj ) {
				$filename = $fileobj['name'];
				$find = strpos( $fileobj['name'], $old_searchstr );
				if ( $find !== false ) {
					$new_filename = str_replace( $old_searchstr, $new_searchstr, $filename );
					if ( $wp_filesystem->exists( $summary_dir . $filename ) ) {
						$wp_filesystem->move( $summary_dir . $filename, $summary_dir . $new_filename );
					}
				}
			}
		}
	}

	public function get_goals_sessions( $dateterm ) {
		global $qahm_time;
		global $wp_filesystem;

		$goals_ary  = $this->get_goals_array();
		$resary = [];

		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';


		//Which month files (yyyy-mm-01_goal_X_1mon.php) needed for $dateterm
		$goal_files_dateranges = [];
		if ( ! preg_match( '/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $dateterm, $date_strings ) ) {
			null;
		}

		$s_daystr   = $date_strings[1];
		$e_daystr   = $date_strings[2];

		if ( ! $s_daystr || ! $e_daystr ) {
			return null;
		}

		$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
		$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );
		$a_date  = substr( $s_daystr, 0, 7 ) . '-01';

		$nowmonth = $qahm_time->month();
		$makeary = true;
		$iii = 0;
		while( $makeary ) {
			$goal_file_name_prefix  = $a_date . '_goal_';

			//次月からb_monを求める
			$n_year = (int)substr( $a_date, 0, 4 );
			$n_monx = (int)substr( $a_date, 5, 2 );
			$current_month = false;
			if ( $nowmonth === $n_monx ) { $current_month = true; }

			if ( $n_monx === 12 ) {
				$next_year = $n_year + 1;
				$next_year = (string)$next_year;
				$next_monx = '01';
			} else {
				$next_year = (string)$n_year;
				$next_monx = $n_monx + 1;
				$next_monx = sprintf('%02d', $next_monx);
			}
			$n_day_01 = $next_year. '-' . $next_monx . '-01';
			$b_date   = $qahm_time->xday_str( -1, $n_day_01 . ' 00:00:00', 'Y-m-d' );

			// between start and end
			if ( $e_unixtime <= $qahm_time->str_to_unixtime( $b_date. ' 23:59:59' ) ) {
				$b_date = $e_daystr;
				$makeary = false;
			}

			$between = 'date = between ' . $a_date . ' and ' . $b_date;
			$goal_files_dateranges[$iii] = ['file_name_prefix' => $goal_file_name_prefix, 'between' => $between, 'current' => $current_month, 'a_date'=> $a_date, 'b_date' => $b_date ];
			$iii++;
			$a_date = $n_day_01;
		}

		//1. get data from goal files (if no file or is old, get data and make goal file)
		foreach ( $goals_ary as $gid => $goal_ary ) {
			$goal_sessions_ary = [];
			$pageid_ary = [];

			switch ( $goal_ary['gtype'] ) {
				//page_idから
				case 'gtype_page' :
				case 'gtype_click' :
				default :
					$pageid_ary = $goal_ary['pageid_ary'];
					$pageid_cnt = count($pageid_ary);
					if ( $pageid_cnt === 0 ) {
						$resary[$gid] = null;
						continue 2;
					}
					//summary/ goal file
					foreach ( $goal_files_dateranges as $each_mon_goal_file ) {
						$between   = $each_mon_goal_file['between'];
						$goal_file = $each_mon_goal_file['file_name_prefix'] . $gid . '_1mon.php';

						$res = '';
						if ( $wp_filesystem->exists( $summary_dir . $goal_file ) ) {
							$file_mtime = $wp_filesystem->mtime( $summary_dir . $goal_file );
							if ( $file_mtime ) {
								if ( $each_mon_goal_file['current'] ) {
									$today  = $qahm_time->today_str();
									$tutime = $qahm_time->str_to_unixtime( $today . ' 00:00:00' );
									if ( $tutime <= $file_mtime ) {
										$res = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $goal_file ) );
									}
								} else {
									$okutime = $qahm_time->str_to_unixtime( $each_mon_goal_file['b_date'] . ' 23:59:59' );
									//1day after is ok
									$okutime = $okutime + ( 3600 * 24 );
									if ( $okutime < $file_mtime ) {
										$res = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $goal_file ) );
									}
								}
							}
						}
						//if no file or is old, get data and make goal file
						if( ! is_array( $res ) ) {
							$where = '';
							if ( $pageid_cnt == 1 ) {
								$page_id  = $pageid_ary[0]['page_id'];
								$where =  'page_id=' . (string)$page_id;
							} elseif ( $pageid_cnt > 1 ) {
								$instr = 'in (';
								foreach ( $pageid_ary as $idx => $pageid ) {
									$page_id = $pageid[ 'page_id' ];
									if ( (int)$page_id > 0 ) {
										$instr .= (string)$page_id;
									}
									if ( $idx === $pageid_cnt -1 ) {
										$instr .= ')';
									} else {
										$instr .= ',';
									}
								}
								$where = 'page_id ' . $instr;
							}
							//execute
							$res = $this->select_data('vr_view_session', '*',  $between, false, $where);
							if ( is_array( $res )) {
								$this->wrap_put_contents( $summary_dir . $goal_file, $this->wrap_serialize( $res ) );
							}
						}
						if ( is_array( $res ) ) {
							$goal_sessions_ary = array_merge( $goal_sessions_ary, $res );
						}
					}
				break;

				//pv_idから
				case 'gtype_event' :
					//summary/ goal file
					foreach ( $goal_files_dateranges as $each_mon_goal_file ) {
						$each_mon_pvid_ary = [];
						$between   = $each_mon_goal_file['between'];
						$goal_file = $each_mon_goal_file['file_name_prefix'] . $gid . '_1mon.php';

						$res = '';
						if ( $wp_filesystem->exists( $summary_dir . $goal_file ) ) {
							$file_mtime = $wp_filesystem->mtime( $summary_dir . $goal_file );
							if ( $file_mtime ) {
								if ( $each_mon_goal_file['current'] ) {
									$today  = $qahm_time->today_str();
									$tutime = $qahm_time->str_to_unixtime( $today . ' 00:00:00' );
									if ( $tutime <= $file_mtime ) {
										$res = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $goal_file ) );
									}
								} else {
									$okutime = $qahm_time->str_to_unixtime( $each_mon_goal_file['b_date'] . ' 23:59:59' );
									//1day after is ok
									$okutime = $okutime + ( 3600 * 24 );
									if ( $okutime < $file_mtime ) {
										$res = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $goal_file ) );
									}
								}
							}
						}
						//if no file or is old, get data and make goal file
						if( ! is_array( $res ) ) {						
							// pvid_ary の作成
							// s_daystrからe_daystrのイベントサマリーファイル YYYY-MM-DD_summary_event.php を開ける
							// 開始日と終了日
							$s_daystr = $each_mon_goal_file['a_date'];
							$e_daystr = $each_mon_goal_file['b_date'];				
							// DateTimeオブジェクトの作成
							$start = new DateTime($s_daystr);
							$end = new DateTime($e_daystr);				
							// DatePeriodオブジェクトを作成
							$interval = new DateInterval('P1D'); // 1日のインターバル
							$period = new DatePeriod($start, $interval, $end->modify('+1 day')); // 終了日も含めるために+1日する

							$g_cv_type = '';
							$parameter_name = '';							
							switch( $goal_ary['g_eventtype'] ) {
								case 'onclick' :
									$g_cv_type = 'c';
									$parameter_name = 'url';
									break;
								default:
									$g_cv_type = '';
									$parameter_name = '';
									break;
							}				
							foreach ( $period as $date ) {
								$filename = $summary_dir . $date->format('Y-m-d') . '_summary_event.php';

								if ( $wp_filesystem->exists( $filename ) ) {
									$file_contents = $this->wrap_unserialize($this->wrap_get_contents($filename));
									if ( $file_contents ) {
										foreach ( $file_contents as $content ) {
											foreach ( $content['event'] as $event ) {
												if ( $event['cv_type'] == $g_cv_type && preg_match( $goal_ary['g_eventselector'], $event[$parameter_name] ) ) {
													// pv_idを追加
													foreach ( $event['pv_id'] as $pv_id ) {
														$each_mon_pvid_ary[] = $pv_id;
													}				
												}
											}
										}
									} 
								}
							}
							
							$pvid_cnt = count($each_mon_pvid_ary);					
							$where = '';
							if ( $pvid_cnt == 1 ) {
								$pv_id  = $each_mon_pvid_ary[0];
								$where =  'pv_id=' . (string)$pv_id;
							} elseif ( $pvid_cnt > 1 ) {
								$instr = 'in (';
								foreach ( $each_mon_pvid_ary as $idx => $pvid ) {
									$pv_id = $pvid;
									if ( (int)$pv_id > 0 ) {
										$instr .= (string)$pv_id;
									}
									if ( $idx === $pvid_cnt -1 ) {
										$instr .= ')';
									} else {
										$instr .= ',';
									}
								}
								$where = 'pv_id ' . $instr;
							}
							// execute
							if ( $where ) {
								$res = $this->select_data('vr_view_session', '*',  $between, false, $where );
							}
							if ( is_array( $res )) {
							    $this->wrap_put_contents( $summary_dir . $goal_file, $this->wrap_serialize( $res ) );
							}
						}
						
						if ( is_array( $res ) ) {
							$goal_sessions_ary = array_merge( $goal_sessions_ary, $res );
						}
					}

				break;
			} //end switch

			//2. access_timeで絞り込み($goal_sessions_aryは月まとめてなので、指定期間内のみを抽出)
			$gs_ary    = [];
			$goal_pvno = [];
			foreach ( $goal_sessions_ary as $each_session ) {
				$lp_time  = $each_session[0]['access_time'];
				$lp_utime = $qahm_time->str_to_unixtime( $lp_time );
				if ( $s_unixtime <= $lp_utime && $lp_utime <= $e_unixtime ) {
					$sessions = [];
					foreach ($each_session as $pv) {
						if (isset($pv['access_time'] )) {
							$sessions[] = $pv;
						}
					}
					if ( ! empty( $sessions ) ) {
						$gs_ary[] = $sessions;
					}
				}
			}


			//3. filter sessions to get gid goal completed sessions
			switch ( $goal_ary['gtype'] ) {

				//no filter
				case 'gtype_page':
				case 'gtype_event':
				default:
					$resary[$gid] = $gs_ary;
					break;

				//filter
				case 'gtype_click':
					$g_clickselector = $goal_ary['g_clickselector'];
					//view_pv dirlist
					$view_pv_dir = $myview_dir . 'view_pv/';
					$raw_c_dir   = $view_pv_dir . 'raw_c/';
					$verhist_dir = $myview_dir . 'version_hist/';
					$verhist_idx_dir = $myview_dir . 'version_hist/index/';

					$event_session_ary = [];

					//1st page_idから全てのversion_histをオープンし、version_id、セレクタindexを配列に保存
					$vid_sidx_ary = [];
					$idx_base  = '_pageid.php';
					$idx_file = '';
					$mem_index = [];

					foreach ( $pageid_ary as $id_ary ) {
						//indexファイルを探す
						$id_num = (int)$id_ary['page_id'];
						$search_range = 100000;
						$search_max = 10000000;
						if ( $id_num > $search_max ) {
							return null;
						}
						for ( $i = 1; $i < $search_max; $i += $search_range ) {
							if ( $i <= $id_num && $i + $search_range > $id_num ) {
								$idx_file = $i . '-' . ( $i + $search_range - 1 ) . $idx_base;
								break;
							}
						}
						if ( ! isset($mem_index[$idx_file] ) ) {
							$mem_index[$idx_file] = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_idx_dir . $idx_file ) );
						}
						if ( is_array($mem_index[$idx_file][$id_num]) ) {
							foreach ($mem_index[$idx_file][$id_num] as $version_id ) {
								$verhist_filename = $version_id . '_version.php';
								$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
								if ( $verhist_file ) {
									$verhist_ary   = $this->wrap_unserialize( $verhist_file );
									$base_selector = $verhist_ary[ 0 ]->base_selector;
									$base_selector_ary = explode("\t", $base_selector);
									for ( $sidx = 0; $sidx < count( $base_selector_ary ); $sidx++ ) {
										//$shape_fact   = preg_replace('/:nth-of-type\([0-9]*\)/i','', $base_selector_ary[$sidx] );
										//$shape_search = preg_replace('/:nth-of-type\([0-9]*\)/i','', $g_clickselector );
										//厳密にする
										$shape_fact   = $base_selector_ary[$sidx];
										$shape_search = $g_clickselector;
										if ( $shape_fact === $shape_search ) {
											$vid_sidx_ary[] = [$version_id, $sidx];
											break;
										}
									}
								}
							}
						}
					}

					//2nd sessionからversion_idを探し、見つかったら該当のpvidのraw_cをオープン。セレクタindexがあるかチェック
					//page_ids search

					//save versionid and session no
					$raw_c_list = $this->wrap_dirlist( $raw_c_dir );
					if ( is_array( $raw_c_list ) ) {
						$raw_c_ary = [];
						foreach ( $gs_ary as $session_ary ) {
							$access_date = substr( $session_ary[ 0 ][ 'access_time' ], 0, 10 );
							for ( $pid = 0; $pid < count( $session_ary ); $pid++ ) {
								$pv = $session_ary[ $pid ];
								$vid = $pv[ 'version_id' ];
								for ( $iii = 0; $iii < count( $vid_sidx_ary ); $iii++ ) {
									if ( (int)$vid === (int)$vid_sidx_ary[ $iii ][ 0 ] ) {
										$pv_id = (int)$pv[ 'pv_id' ];
										for ( $fno = 0; $fno < count( $raw_c_list ); $fno++ ) {
											if ( strstr( $raw_c_list[ $fno ][ 'name' ], $access_date ) ) {
												if ( ! isset($raw_c_ary[ $access_date ] ) ) {
													$raw_c_ary[$access_date] = $this->wrap_unserialize( $this->wrap_get_contents( $raw_c_dir . $raw_c_list[ $fno ][ 'name' ] ) );
												}
												for ( $pvx = 0; $pvx < count( $raw_c_ary[ $access_date ] ); $pvx++ ) {
													if ( (int)$raw_c_ary[$access_date][ $pvx ][ 'pv_id' ] === $pv_id ) {
														$pv_raw_c_str = $raw_c_ary[$access_date][ $pvx ][ 'raw_c' ];
														$pv_raw_c_ary = explode( '\n', $pv_raw_c_str );
														for ( $sidx = 0; $sidx < count( $pv_raw_c_ary ); $sidx++ ) {
															$raw_c_events = $this->convert_tsv_to_array( $pv_raw_c_ary[ $sidx ] );
															if ( (int)$raw_c_events[ 1 ][ 0 ] === (int)$vid_sidx_ary[ $iii ][ 1 ] ) {
																$event_session_ary[] = $session_ary;
																break 5;
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
					//if find ary++
					$resary[$gid] = $event_session_ary;
					break;
			} //switch end 'goal type'
			
		} //foreach end 'goals_ary'
		return $resary;
	}


	public function ajax_url_to_page_id() {
		/*
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' );
		}
		*/

		$url = $this->wrap_filter_input( INPUT_POST, 'url' );
		$prefix_match = $this->wrap_filter_input( INPUT_POST, 'prefix' );
		if ( ! $url ) {
			die();
		}
		if ( $prefix_match ) {
			$match_prefix = false;
			if ( $prefix_match === 'pagematch_prefix' ) {
				$match_prefix = true;
			}
			$res = $this->url_to_page_id( $url, $match_prefix );
		} else {
			$res = $this->url_to_page_id( $url );
		}
		if ( $res ) {
			if ( $prefix_match ) {
				echo wp_json_encode( $res );
			} else {
				echo wp_json_encode( esc_html( $res[0]['page_id'] ) . 'id' );
			}
		} else {
			echo wp_json_encode( esc_html( $res ) . 'error' );
		}
		die();
	}

	public function url_to_page_id( $url,  $prefix_match = false ) {
		global $qahm_db;
		global $wpdb;

		$url1 = mb_strtolower( $url );
		if ( $prefix_match ) {
			// 前方一致
			$query = 'SELECT page_id FROM ' . $qahm_db->prefix . 'qa_pages WHERE url LIKE %s';
			$res   = $qahm_db->get_results( $qahm_db->prepare( $query, $wpdb->esc_like($url1) . '%' ), ARRAY_A );
		} else {
			// スラッシュあり、なしの両方を検索
			if( substr( $url1, -1 ) === '/' ) {
				$url2 = rtrim( $url1, '/' );
			} else {
				$url2 = $url1 . '/';
			}
			$query = 'SELECT page_id FROM ' . $qahm_db->prefix . 'qa_pages WHERE url = BINARY %s OR url = BINARY %s';
			$res   = $qahm_db->get_results( $qahm_db->prepare( $query, $url1, $url2 ), ARRAY_A );
		}

		return $res;

	}

	//いつかAPIのために作りたい
	public function summary_data( $table, $dimensions, $metrics, $date_or_id, $count = false, $where = '' )
	{

		//table名の補完
		if ( strpos( $table, $this->prefix ) === false ) {
			$table = $this->prefix . $table;
		}
	}

	//暫定的に専用配列を返す。いつかAPIで吸収したい。
	// new repeat / device table用
	public function ajax_get_nrd_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_nrd_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_nrd_data( $dateterm ) {

    //make new/repeat device array
		$sad_ary = $this->select_data( 'summary_days_access_detail', '*', $dateterm);
		$nrd_ary = [];

		$nrd_ary[] = (['New Visitor', 'dsk', 0, 0, 0, 0, 0, 0]);
		$nrd_ary[] = (['New Visitor', 'tab', 0, 0, 0, 0, 0, 0]);
		$nrd_ary[] = (['New Visitor', 'smp', 0, 0, 0, 0, 0, 0]);
		$nrd_ary[] = (['Returning Visitor', 'dsk', 0, 0, 0, 0, 0, 0]);
		$nrd_ary[] = (['Returning Visitor', 'tab', 0, 0, 0, 0, 0, 0]);
		$nrd_ary[] = (['Returning Visitor', 'smp', 0, 0, 0, 0, 0, 0]);


            // new/repeat device report
		$maxcnt = count( $sad_ary );
		for ( $iii = 0; $iii < $maxcnt; $iii++ ) {

            if ( $sad_ary[$iii]['is_newuser'] ) {
                switch ($sad_ary[$iii]['device_id']) {
                    case 1:
                        $nrd_ary[0][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[0][3] += $sad_ary[$iii]['is_newuser'];
                        $nrd_ary[0][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[0][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[0][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[0][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    case 2:
                        $nrd_ary[1][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[1][3] += $sad_ary[$iii]['is_newuser'];
                        $nrd_ary[1][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[1][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[1][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[1][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    case 3:
                        $nrd_ary[2][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[2][3] += $sad_ary[$iii]['is_newuser'];
                        $nrd_ary[2][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[2][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[2][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[2][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    default:
                        break;
                }

            } else {
                switch ($sad_ary[$iii]['device_id']) {
                    case 1:
                        $nrd_ary[3][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[3][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[3][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[3][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[3][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    case 2:
                        $nrd_ary[4][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[4][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[4][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[4][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[4][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    case 3:
                        $nrd_ary[5][2] += $sad_ary[$iii]['user_count'];
                        $nrd_ary[5][4] += $sad_ary[$iii]['session_count'];
                        $nrd_ary[5][5] += $sad_ary[$iii]['bounce_count'];
                        $nrd_ary[5][6] += $sad_ary[$iii]['pv_count'];
                        $nrd_ary[5][7] += $sad_ary[$iii]['time_on_page'];
                        break;

                    default:
                        break;
                }
            }
    	}
    	$nnncnt = count( $nrd_ary );
		for ( $nnn = 0; $nnn < $nnncnt; $nnn++ ) {
			$sessions = $nrd_ary[$nnn][4];
			$bounces  = $nrd_ary[$nnn][5];
			$pages    = $nrd_ary[$nnn][6];
			$times    = $nrd_ary[$nnn][7];
			if ( 0 < $sessions ) {
				//直帰はセッションのうちの直帰数
				$bouncerate = ( $bounces / $sessions ) * 100;
				$nrd_ary[$nnn][5] = round( $bouncerate, 1 );
				//ページ/セッション
				$sessionavg = ( $pages / $sessions );
				$nrd_ary[$nnn][6] = round ( $sessionavg, 2 );
				//平均セッション時間（秒）
				$sessiontim = ( $times / $sessions );
				$nrd_ary[$nnn][7] = round ( $sessiontim, 0 );
			}
		}
		return $nrd_ary;
	}


	// new repeat / device table用
	public function ajax_get_ch_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_ch_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_ch_data( $dateterm ) {

    	//make chanell array
		$sad_ary = $this->select_data( 'summary_days_access_detail', '*', $dateterm);

		//make channel array
		//https://support.google.com/analytics/answer/3297892?hl=en
		//user newuser session bouncerate page/session avgsessiontime
		$ch_ary = [];
		$ch_ary[] = (['Direct', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Organic Search', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Social', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Email', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Affiliates', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Referral', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Paid Search', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Other Advertising', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Display', 0, 0, 0, 0, 0, 0]);
		$ch_ary[] = (['Other', 0, 0, 0, 0, 0, 0]);


		//default channel group
		$paidsearch = '/^(cpc|ppc|paidsearch)$/';
		$display    = '/(display|cpm|banner)$/';
		$otheradv   = '/^(cpv|cpa|cpp|content-text)$/';
		$social     = '/^(social|social-network|social-media|sm|social network|social media)$/';

        // new/repeat device report
		$maxcnt = count( $sad_ary );

		// HTTP_HOSTが存在するか確認し、必要な処理を行う
		$domain = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			// wp_unslash()でスラッシュを除去し、sanitize_text_field()でサニタイズ
			$domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

		for ( $iii = 0; $iii < $maxcnt; $iii++ ) {

			//channel report

			switch ( $sad_ary[ $iii ][ 'utm_medium' ] ) {
				case '':
				case null:
					if ( $sad_ary[ $iii ][ 'source_domain' ] !== null ) {
						if ( $sad_ary[ $iii ][ 'source_domain' ] === 'direct' || $sad_ary[ $iii ][ 'source_domain' ] === $domain ) {
							$ch_ary[ 0 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
							if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
								$ch_ary[ 0 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
							}
							$ch_ary[ 0 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
							$ch_ary[ 0 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
							$ch_ary[ 0 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
							$ch_ary[ 0 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
						} else {
							$ch_ary[ 5 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
							if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
								$ch_ary[ 5 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
							}
							$ch_ary[ 5 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
							$ch_ary[ 5 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
							$ch_ary[ 5 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
							$ch_ary[ 5 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
						}
					} else {
						$ch_ary[ 0 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 0 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 0 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 0 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 0 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 0 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					}
					break;

				case 'organic':
					$ch_ary[ 1 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
					if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
						$ch_ary[ 1 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
					}
					$ch_ary[ 1 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
					$ch_ary[ 1 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
					$ch_ary[ 1 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
					$ch_ary[ 1 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					break;

				case 'email':
					$ch_ary[ 3 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
					if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
						$ch_ary[ 3 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
					}
					$ch_ary[ 3 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
					$ch_ary[ 3 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
					$ch_ary[ 3 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
					$ch_ary[ 3 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					break;

				case 'affiliate':
					$ch_ary[ 4 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
					if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
						$ch_ary[ 4 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
					}
					$ch_ary[ 4 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
					$ch_ary[ 4 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
					$ch_ary[ 4 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
					$ch_ary[ 4 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					break;

				case 'referral':
					$ch_ary[ 5 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
					if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
						$ch_ary[ 5 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
					}
					$ch_ary[ 5 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
					$ch_ary[ 5 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
					$ch_ary[ 5 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
					$ch_ary[ 5 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					break;

				default:
					if ( preg_match( $paidsearch, $sad_ary[ $iii ][ 'utm_medium' ] ) ) {
						$ch_ary[ 6 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 6 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 6 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 6 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 6 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 6 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					} else if ( preg_match( $display, $sad_ary[ $iii ][ 'utm_medium' ] ) ) {
						$ch_ary[ 8 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 8 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 8 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 8 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 8 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 8 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					} else if ( preg_match( $social, $sad_ary[ $iii ][ 'utm_medium' ] ) ) {
						$ch_ary[ 2 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 2 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 2 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 2 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 2 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 2 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					} else if ( preg_match( $otheradv, $sad_ary[ $iii ][ 'utm_medium' ] ) ) {
						$ch_ary[ 7 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 7 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 7 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 7 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 7 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 7 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					} else {
						$ch_ary[ 9 ][ 1 ] += $sad_ary[ $iii ][ 'user_count' ];
						if ( $sad_ary[ $iii ][ 'is_newuser' ] ) {
							$ch_ary[ 9 ][ 2 ] += $sad_ary[ $iii ][ 'user_count' ];
						}
						$ch_ary[ 9 ][ 3 ] += $sad_ary[ $iii ][ 'session_count' ];
						$ch_ary[ 9 ][ 4 ] += $sad_ary[ $iii ][ 'bounce_count' ];
						$ch_ary[ 9 ][ 5 ] += $sad_ary[ $iii ][ 'pv_count' ];
						$ch_ary[ 9 ][ 6 ] += $sad_ary[ $iii ][ 'time_on_page' ];
					}
					break;

			}
		}
		
		//generate channel table
    	$nnncnt = count( $ch_ary );
		for ( $nnn = 0; $nnn < $nnncnt; $nnn++ ) {
			$sessions = $ch_ary[$nnn][3];
			$bounces  = $ch_ary[$nnn][4];
			$pages    = $ch_ary[$nnn][5];
			$times    = $ch_ary[$nnn][6];
			if ( 0 < $sessions ) {
				//直帰はセッションのうちの直帰数
				$bouncerate = ( $bounces / $sessions ) * 100;
				$ch_ary[$nnn][4] = round( $bouncerate, 1 );
				//ページ/セッション
				$sessionavg = ( $pages / $sessions );
				$ch_ary[$nnn][5] = round( $sessionavg, 2 );
				//平均セッション時間（秒）
				$sessiontim = ( $times / $sessions );
				$ch_ary[$nnn][6] = round( $sessiontim, 0 );
			}
		}
		return $ch_ary;
	}

	// new repeat / device table用
	public function ajax_get_sm_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_sm_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_sm_data( $dateterm ) {

    	//make chanell array
		$sad_ary = $this->select_data( 'summary_days_access_detail', '*', $dateterm );

		//make channel array
		//https://support.google.com/analytics/answer/3297892?hl=en
		//user newuser session bouncerate page/session avgsessiontime
		$sm_ary = [];
            //source/media report
		$maxcnt = count( $sad_ary );
		for ( $iii = 0; $iii < $maxcnt; $iii++ ) {
            $source  = $sad_ary[$iii]['source_domain'];
            if ( ! $source ) {
				$source = 'direct';
			}
            $medium  = $sad_ary[$iii]['utm_medium'];
            if ( ! $medium ) {
				if ( $source === 'direct' ) {
					$medium = '(none)';
				} else {
					$medium = 'referral';
				}
			}
            $usercnt = $sad_ary[$iii]['user_count'];
            $newuser = $sad_ary[$iii]['is_newuser'];
            $session = $sad_ary[$iii]['session_count'];
            $bounce  = $sad_ary[$iii]['bounce_count'];
            $pvcnt   = $sad_ary[$iii]['pv_count'];
            $timeon  = $sad_ary[$iii]['time_on_page'];
            if ( ! $sad_ary[$iii]['is_newuser'] ) {
                $newuser = 0;
            }
            $is_find = false;
			$ssscnt = count( $sm_ary );
            for ( $sss = 0; $sss < $ssscnt; $sss++ ) {
                if ( $sm_ary[$sss][0] === $source && $sm_ary[$sss][1] === $medium ) {
					$sm_ary[$sss][2] += $usercnt;
					$sm_ary[$sss][3] += $newuser;
					$sm_ary[$sss][4] += $session;
					$sm_ary[$sss][5] += $bounce;
					$sm_ary[$sss][6] += $pvcnt;
					$sm_ary[$sss][7] += $timeon;
					$is_find = true;
					break;
                }
            }
            if ( ! $is_find ) {
                $sm_ary[] = [ $source, $medium, $usercnt, $newuser, $session, $bounce, $pvcnt, $timeon ];
            }
        }
		$ssscnt = count ( $sm_ary );
		//generate source/media table
		for ( $nnn = 0; $nnn < $ssscnt; $nnn++ ) {
			$sessions = $sm_ary[$nnn][4];
			$bounces  = $sm_ary[$nnn][5];
			$pages    = $sm_ary[$nnn][6];
			$times    = $sm_ary[$nnn][7];
			if ( 0 < $sessions ) {
				//直帰はセッションのうちの直帰数
				$bouncerate = ( $bounces / $sessions) * 100;
				$sm_ary[$nnn][5] = round( $bouncerate, 1 );
				//ページ/セッション
				$sessionavg = ( $pages / $sessions );
				$sm_ary[$nnn][6] = round( $sessionavg, 2 );
				//平均セッション時間（秒）
				$sessiontim = ( $times / $sessions );
				$sm_ary[$nnn][7] = round( $sessiontim, 0 );
			}
		}
		return $sm_ary;
	}

	// landing page table用
	public function ajax_get_lp_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_lp_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_lp_data( $dateterm ) {

    	//make chanell array
		$lps_ary = [];

		// HTTP_HOSTが存在するかチェックし、必要な処理を行う
		$domain = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			// wp_unslash()でスラッシュを除去し、sanitize_text_field()でサニタイズ
			$domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

		global $qahm_db;
		$lp_ary = $qahm_db->summary_days_landingpages( $dateterm );

		//遅いので上記に変更
		//$lp_ary = $this->select_data( 'vr_summary_landingpage', '*', $dateterm );
		$lp_cnt = count( $lp_ary );
    	for ( $iii = 0; $iii < $lp_cnt; $iii++ ) {

            //usually start
            $pageid  = $lp_ary[$iii]['page_id'];
            $wpqaid  = $lp_ary[$iii]['wp_qa_id'];
            $title   = $lp_ary[$iii]['title'];
            $url     = $lp_ary[$iii]['url'];
            $source  = $lp_ary[$iii]['source_domain'];
            $utm_sce = $lp_ary[$iii]['utm_source'];
            if ( $source === null ) { $source = ''; }
            $medium  = $lp_ary[$iii]['utm_medium'];
            if ( $medium === null ) { $medium = ''; }
            if ( $source === 'direct' || $source === $domain ) { $medium = 'Direct'; }
            $pvcnt   = (int)($lp_ary[$iii]['pv_count']);
            $session = (int)($lp_ary[$iii]['session_count']);
            $usercnt = (int)($lp_ary[$iii]['user_count']);
            $bounce  = (int)($lp_ary[$iii]['bounce_count']);
            $timeon  = (int)($lp_ary[$iii]['session_time']);

            $newsession = 0;
            $newuser    = 0;
            if ( $lp_ary[$iii]['is_newuser'] ) {
                $newsession = (int)( $session );
                $newuser    = (int)( $usercnt );
            }

            // landingpage report
            $is_find = false;
            $lpscnt  = count( $lps_ary );
            for ( $ppp = 0; $ppp < $lpscnt; $ppp++ ) {
                if ( $lps_ary[$ppp][0] === $pageid ) {
                    $lps_ary[$ppp][3] += $session;
                    $lps_ary[$ppp][4] += $newsession;
                    $lps_ary[$ppp][5] += $newuser;
                    $lps_ary[$ppp][6] += $bounce;
                    $lps_ary[$ppp][7] += $pvcnt;
                    $lps_ary[$ppp][8] += $timeon;
                    $is_find = true;
                    break;
                }
            }
            if ( ! $is_find ) {
                $lps_ary[] = [ $pageid, $title, $url, $session, $newsession, $newuser, $bounce, $pvcnt, $timeon, $wpqaid, $pageid ];
            }
        }



		//urlクレンジング。大文字だった場合に小文字と同一視してマージする。page_idは配列にする。
		$cleansing_url_ary = [];
		$except_ary      = [];
		$cidx            = 0;
		$lpscnt = count( $lps_ary );
		for ( $ccc = 0; $ccc < $lpscnt; $ccc++ ) {
			//この処理で対応したpage_idはArrayにしておくことで飛ばす
			if ( ! is_array( $lps_ary[$ccc][0] ) ) {
				if ( preg_match('/[A-Z]/',  $lps_ary[$ccc][2] ) ) {
					//search and re calc
					$pageids = [];
					$pageids[] = $lps_ary[$ccc][0];
					$title    = $lps_ary[$ccc][1];
					$urllow   = strtolower($lps_ary[$ccc][2]);
					$session = $lps_ary[$ccc][3];
					$newsession = $lps_ary[$ccc][4];
					$newuser    = $lps_ary[$ccc][5];
					$bounce = $lps_ary[$ccc][6];
					$pvcount  = $lps_ary[$ccc][7];
					$timeon    = $lps_ary[$ccc][8];
					$wpqaid   = $lps_ary[$ccc][9];
	
					for ( $sss = 0; $sss < $lpscnt; $sss++ ) {
						$nowlow = strtolower($lps_ary[$sss][2]);
						if ( $urllow === $nowlow && $ccc !== $sss ) {
							//先に$sssを入れてしまったのでクレンジングから抜く必要がある。
							if ($sss < $ccc) {
								$except_ary[] = $lps_ary[$sss][0];
							}
							$pageids[] = $lps_ary[$sss][0];
							$session += $lps_ary[$sss][3];
							$newsession += $lps_ary[$sss][4];
							$newuser    += $lps_ary[$sss][5];
							$bounce += $lps_ary[$sss][6];
							$pvcount  += $lps_ary[$sss][7];
							$timeon    += $lps_ary[$sss][8];
							$lps_ary[$sss][0] = $pageids;
						}
					}
					$cleansing_url_ary[$cidx] = [$pageids,$title, $urllow, $session, $newsession, $newuser, $bounce, $pvcount, $timeon, $wpqaid, $pageids];
					$cidx++;
				} else {
					$cleansing_url_ary[$cidx] = $lps_ary[$ccc];
					$cidx++;
				}
			}
		}
		$new_cleansing_url_ary = [];
		$cuacnt = count($cleansing_url_ary);
		$exacnt = count($except_ary);

		for ( $iii = 0; $iii < $cuacnt; $iii++ ) {
			$is_find = false;
			$page_id = 0;
			if ( ! is_array( $cleansing_url_ary[$iii][0] ) ) {
				$page_id = $cleansing_url_ary[$iii][0];
			}
			for ( $kkk = 0; $kkk < $exacnt; $kkk++ ) {
				if ( $page_id === $except_ary[$kkk]) {
					$is_find = true;
				}
			}
			if (! $is_find ) {
				$new_cleansing_url_ary[] =  $cleansing_url_ary[$iii];
			}
		}
	
		$lps_ary = $new_cleansing_url_ary;
    
		return $lps_ary;
	}


	// growth page table用
	public function ajax_get_gw_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_gw_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_gw_data( $dateterm )
	{
		global $qahm_db;
		// HTTP_HOSTが存在するか確認し、安全に取得する
		$domain = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			// スラッシュを解除し、テキスト用にサニタイズ
			$domain = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

		$gw_ary = $qahm_db->summary_days_growthpages( $dateterm );
		$retary = [];
		foreach ( $gw_ary as $line_ary ) {
			$pageid  = $line_ary['page_id'];
			$medium  = $line_ary['utm_medium'];
			$start_pv_count = $line_ary['start_session_count'];
			$end_pv_count   = $line_ary['end_session_count'];
			$title   = $line_ary['title'];
			$url     = $line_ary['url'];
			$wpqaid  = $line_ary['wp_qa_id'];
			$editurl = admin_url( 'post.php');
			$editurl = $editurl . '?post=' . (string)$wpqaid . '\&action=edit';
			//urlの置換
			$growth_rate = 0;
			if ( $start_pv_count <= $end_pv_count && $start_pv_count !== 0 ) {
				$growth_rate = round( $end_pv_count / $start_pv_count *100 - 100, 2);
			} else {
				if ( $end_pv_count !== 0 ) {
					$growth_rate = -1 * round( $start_pv_count / $end_pv_count *100 - 100, 2);
				}
			}
			$retary[] = [ $pageid, $title, $url, $medium, $start_pv_count, $end_pv_count, $growth_rate, $editurl ];
		}
		return $retary;
	}

	// all page table用
	public function ajax_get_ap_data() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 406 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$dateterm = mb_strtolower( trim( $this->wrap_filter_input( INPUT_POST, 'date' ) ) );

		$resary = $this->get_ap_data( $dateterm );
		header("Content-type: application/json; charset=UTF-8");
		echo wp_json_encode($resary);
		die();
	}

	public function get_ap_data( $dateterm ) {
		$aps_ary = [];

		global $qahm_db;
		$ap_ary = $qahm_db->summary_days_allpages( $dateterm );
		//遅いので上記に変更
//		$ap_ary = $this->select_data( 'vr_summary_allpage', '*', $dateterm );

		$ap_cnt = count( $ap_ary );
    	for ( $iii = 0; $iii < $ap_cnt; $iii++ ) {
            // allpage report
            $pageid  = $ap_ary[$iii]['page_id'];
            $wpqaid  = $ap_ary[$iii]['wp_qa_id'];
            $title   = $ap_ary[$iii]['title'];
            $url     = $ap_ary[$iii]['url'];
            $source  = $ap_ary[$iii]['source_domain'];
            if ( $source === null ) { $source = ''; }
            $medium  = $ap_ary[$iii]['utm_medium'];
            if ( $medium === null ) { $medium = ''; }
            if ( $source === 'direct' ) { $medium = 'Direct'; }
            $usercnt = $ap_ary[$iii]['user_count'];
            $bounce  = $ap_ary[$iii]['bounce_count'];
            $pvcnt   = $ap_ary[$iii]['pv_count'];
            $exitcnt = $ap_ary[$iii]['exit_count'];
            $timeon  = $ap_ary[$iii]['time_on_page'];
            $lpcount = $ap_ary[$iii]['lp_count'];
            if ( ! $ap_ary[$iii]['is_newuser'] ) {
                $newuser = 0;
            }
            $is_find = false;
            $apscnt = count($aps_ary);
            for ( $ppp = 0; $ppp < $apscnt; $ppp++ ) {
                if ( $aps_ary[$ppp][0] === $pageid ) {
                    $aps_ary[$ppp][3] += $pvcnt;
                    $aps_ary[$ppp][4] += $usercnt;
                    $aps_ary[$ppp][5] += $timeon;
                    $aps_ary[$ppp][6] += $lpcount;
                    $aps_ary[$ppp][7] += $bounce;
                    $aps_ary[$ppp][8] += $exitcnt;
                    $is_find = true;
                    break;
                }
            }
            if ( ! $is_find ) {
                $aps_ary[] = ( [ $pageid, $title, $url, $pvcnt, $usercnt, $timeon, $lpcount, $bounce, $exitcnt, $wpqaid, $pageid ]);
            }
        }
		//urlクレンジング。大文字だった場合に小文字と同一視してマージする。page_idは配列にする。
		$cleansing_url_ary = [];
		$except_ary      = [];
		$cidx            = 0;
		$apscnt          = count($aps_ary);
		for ( $ccc = 0; $ccc < $apscnt; $ccc++ ) {
			//この処理で対応したpage_idはArrayにしておくことで飛ばす
			if ( ! is_array($aps_ary[$ccc][0]) ) {
				if ( preg_match('/[A-Z]/', $aps_ary[$ccc][2] ) ) {
					//search and re calc
					$pageids = [];
					$pageids[] = $aps_ary[$ccc][0];
					$title    = $aps_ary[$ccc][1];
					$urllow   = strtolower($aps_ary[$ccc][2]);
					$pvcounts = $aps_ary[$ccc][3];
					$usercnts = $aps_ary[$ccc][4];
					$times    = $aps_ary[$ccc][5];
					$lpcounts = $aps_ary[$ccc][6];
					$bounces  = $aps_ary[$ccc][7];
					$exits    = $aps_ary[$ccc][8];
					$wpqaid   = $aps_ary[$ccc][9];

					for ( $sss = 0; $sss < $apscnt; $sss++ ) {
						$nowlow = strtolower($aps_ary[$sss][2]);
						if ( $urllow === $nowlow && $ccc !== $sss ) {
							//先に$sssを入れてしまったのでクレンジングから抜く必要がある。
							if ($sss < $ccc) {
								$except_ary[] = $aps_ary[$sss][0];
							}
							$pageids[] = $aps_ary[$sss][0];
							$pvcounts += $aps_ary[$sss][3];
							$usercnts += $aps_ary[$sss][4];
							$times    += $aps_ary[$sss][5];
							$lpcounts += $aps_ary[$sss][6];
							$bounces  += $aps_ary[$sss][7];
							$exits    += $aps_ary[$sss][8];
							$aps_ary[$sss][0] = $pageids;
						}
					}
					$cleansing_url_ary[$cidx] = [$pageids, $title, $urllow, $pvcounts, $usercnts, $times, $lpcounts, $bounces, $exits, $wpqaid, $pageids];
					$cidx++;
				} else {
					$cleansing_url_ary[$cidx] = $aps_ary[$ccc];
					$cidx++;
				}
			}
		}
		$new_cleansing_url_ary = [];
		$clucnt = count($cleansing_url_ary);
		for ( $iii = 0; $iii < $clucnt; $iii++ ) {
			$is_find = false;
			$page_id = 0;
			if ( ! is_array( $cleansing_url_ary[$iii][0] ) ) {
				$page_id = $cleansing_url_ary[$iii][0];
			}
			$ecacnt = count($except_ary);
			for ( $kkk = 0; $kkk < $ecacnt; $kkk++ ) {
				if ( $page_id === $except_ary[$kkk]) {
					$is_find = true;
				}
			}
			if (! $is_find ) {
				$new_cleansing_url_ary[] = $cleansing_url_ary[$iii];
			}
		}
		$aps_ary = $new_cleansing_url_ary;
		return $aps_ary;
	}



	public function select_data( $table, $column, $date_or_id, $count = false, $where = '' ) {
		global $qahm_db;

		//table名の補完
		if ( strpos( $table, $this->prefix) === false ) {
			$table = $this->prefix . $table;
		}

		//必須フィールドのチェック
		$colname = [];
		$is_err  = false;
		$is_datetime = false;
		switch ($table) {
			case $this->prefix . 'view_pv':
			case $this->prefix . 'vr_view_pv':
				$colname['date'] = 'access_time';
				$colname['id']   = 'pv_id';
				$is_datetime     = true;
				break;
			
			case $this->prefix . 'summary_days_access':
				$colname['date'] = 'date';
				$colname['id']   = '';
				$is_datetime     = false;
				break;

			case $this->prefix . 'summary_days_access_detail':
				$colname['date'] = 'date';
				$colname['id']   = '';
				$is_datetime     = false;
				break;

			case $this->prefix . 'vr_summary_allpage':
				$colname['date'] = 'date';
				$colname['id']   = '';
				$is_datetime     = false;
				break;

			case $this->prefix . 'vr_summary_landingpage':
				$colname['date'] = 'date';
				$colname['id']   = '';
				$is_datetime     = false;
				break;

			case $this->prefix . 'qa_page_version_hist':
				$colname['date'] = 'update_date';
				$colname['id'] = 'version_id';
				break;
				
			case $this->prefix . 'qa_readers':
				$colname['date'] = 'update_date';
				$colname['id']   = 'reader_id';
				break;
				
			case $this->prefix . 'qa_utm_sources':
				$colname['id'] = 'source_id';
				break;
			
			case $this->prefix . 'qa_pages':
				$colname['date'] = 'update_date';
				$colname['id']   = 'page_id';
				break;

			case $this->prefix . 'qa_pv_log':
				$colname['date'] = 'access_time';
				$colname['id']   = 'pv_id';
				$is_datetime     = true;
				break;

			case $this->prefix . 'vr_view_session':
				return $this->get_vr_view_session( $column, $date_or_id, $where, $count );

			default:
				$is_err = true;
				break;
		}
		if ( $is_err ) {
			http_response_code( 401 );
			die( 'table error' );
		}
		//カラムのチェック
		if ($column !== '*') {
			$table_allcol  = $this->show_column( $table );
			$columns       = explode(',', $column );
			if ( 1 < count($columns) && $count === true ) {
				http_response_code( 402 );
				die( 'count too many colmuns error' );
			}
			foreach ( $columns as $col ) {
				if (! in_array($col, $table_allcol)) {
					http_response_code( 402 );
					die( 'colmuns error' );
				}
			}
		}

		//カラムの完成
		if ( $count ) {
			$column = 'count(' . $column . ')';
		}

		// 最初のクエリを作成
		if ( preg_match('/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $date_or_id, $datestrs ) ) {
			//$basequery = 'select ' . $column . ' from ' . $table . ' where ' . $colname[ 'date' ] . ' = %s between %s';
			$basequery = 'select ' . $column . ' from ' . $table . ' where ' . $colname[ 'date' ] . ' between %s and %s';
			$aday = $datestrs[1];
			$bday = $datestrs[2];
			if ( $is_datetime ) {
				if ( strpos( $aday, ':') === false ) {
					$aday = $aday . ' 00:00:00';
				}
				if ( strpos( $bday, ':') === false ) {
					$bday = $bday . ' 23:59:59';
				}
			}
			$query  = $this->prepare( $basequery, $aday, $bday );
		} elseif ( preg_match('/^id\s*=\s*([0-9]*)$/', $date_or_id, $idnum ) ) {
			$basequery = 'select ' . $column . ' from ' . $table . ' where ' . $colname[ 'id' ] . ' = %d';
			$query = $this->prepare( $basequery, $idnum[1] );
		} else {
			$is_err = true;
		}
		if ( $is_err ) {
			http_response_code( 408 );
			die( esc_html( $aday ) . esc_html( $bday ) . 'date_or_id error' );
		}

		if ( $count ) {
			return $this->get_var( $query );
		} else {
			return $this->get_results( $query );
		}
	}


	public function ajax_get_each_posts_count() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_API) || $this->is_maintenance() ) {
			http_response_code( 400 );
			die( 'nonce error' );
		}
		// 全パラメーターを取得する
		$month      = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'month' ) );

		if ( is_numeric( $month ) ) {
			$resary     = $this->get_each_posts_count( (int)$month );
			header("Content-type: application/json; charset=UTF-8");
			echo wp_json_encode($resary);
		} else {
			http_response_code( 409 );
			die();
		}
		die();
	}

	public function get_each_posts_count( $month ) {
		global $qahm_time;
		global $qahm_db;
		
		$table_name = $qahm_db->prefix . 'posts';
		$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
		$where    = " WHERE post_status = 'publish' AND post_type IN ('" . implode( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "')";
		$order    = ' ORDER BY post_date DESC';
		$query    = 'SELECT post_date FROM ' . $table_name . $where . $order;
		$allposts = $qahm_db->get_results( $query, ARRAY_A );
		$allposts_count = count( $allposts );
		$thisyear     = $qahm_time->year();
		$thismonth    = $qahm_time->month();
		$m1unixtime_ary = [];
		$minusyear = 0;
		$plusmonth = 0;
		for ( $iii = 0; $iii < $month ; $iii++ ) {
			if ( ( $thismonth + $plusmonth - $iii ) === 0 ) {
				$minusyear++;
				$plusmonth = 12 * $minusyear;
			}
			$nowyear  = $thisyear - $minusyear;
			$nowmonth = $thismonth + $plusmonth - $iii;
			$zeroume  = '';
			if ( $nowmonth < 10) {$zeroume = '0';}
			$month1st = $nowyear . '-' . $zeroume . $nowmonth . '-01 00:00:00';
			$m1unixtime_ary[] = $qahm_time->str_to_unixtime( $month1st );
		}

		$mnt_post_ary  = [];
		$eachmonth_ary = [];
		for ( $ccc = 0; $ccc < $month ; $ccc++ ) {
			$mnt_post_ary[$ccc] = 0;
		}

		//post month find
		for ( $iii = $allposts_count - 1; 0 <= $iii; --$iii ) {
			$postunixtim = $qahm_time->str_to_unixtime( $allposts[$iii]['post_date'] );
			for ( $ccc = 0; $ccc < $month ; $ccc++ ) {
				if ( $m1unixtime_ary[$ccc] <= $postunixtim ) {
					++$mnt_post_ary[$ccc];
					break;
				}
			}
		}
		//set chart array
		$minuscount = 0;
		for ( $ccc = 0; $ccc < $month ; $ccc++ ) {
			$eachmonth_ary[$ccc] = $allposts_count - $minuscount;
			$minuscount += $mnt_post_ary[$ccc];
		}
		return $eachmonth_ary;
	}

	/**
	 * private function
	 */
	private function alltrim( $string ) {
		return str_replace(' ', '', $string);
	}

	/*
	Plugin Checkでnonceがないということで引っかかった。そのためコメントアウト
	private function get_all_parameter() {
		$postarray = array();
		if ( isset( $_POST ) && count( $_POST ) ) {
			$postarray = $_POST;
		}

		$getarray = array();
		if( isset( $_GET ) && count( $_GET ) ){
			$getarray = $_GET;
		}

		$paramarray = array_merge( $getarray, $postarray );

		foreach ( $paramarray as $key => $value ) {
			$paramarray[ $key ] = $this->h( $value );
		}
		return $paramarray;
	}

	private function get_get_parameter() {
		$getarray = array();
		if( isset( $_GET ) && count( $_GET ) ){
			$getarray = $_GET;
		}

		$paramarray = $getarray;

		foreach ( $paramarray as $key => $value ) {
			$paramarray[ $key ] = $this->h( $value );
		}
		return $paramarray;
	}

	private function get_post_parameter() {
		$postarray = array();
		if ( isset( $_POST ) && count( $_POST ) ) {
			$postarray = $_POST;
		}

		$paramarray = $postarray;

		foreach ( $paramarray as $key => $value ) {
			$paramarray[ $key ] = $this->h( $value );
		}
		return $paramarray;
	}
	*/


	/**
	 * private function
	 */
	private function h( $string ) {
		if ( is_array( $string ) ) {
			return array_map( "$this->h", $string );
		} else {
			return htmlspecialchars( $string, ENT_QUOTES, 'UTF-8' );
		}
	}
	

	/**
	 * ユーザー名とパスワードの認証が通ればnonceを返す
	 */
	public function ajax_get_nonce () {
		$name = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'user_name' ) );
		$pass = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'password' ) );
		$type = $this->alltrim( $this->wrap_filter_input( INPUT_POST, 'client_type' ) );

		$user = wp_authenticate( $name, $pass );
		if ( is_wp_error( $user ) ) {
			echo 'エラー' + ': ' + 'ログイン認証に失敗しました。';
			die();
		}

		// 管理者権限判定
		// $user->rolesでループしてadminを調べる方法でよさそう 
		$is_admin = false;
		foreach( $user->roles as $role ) {
			if ( 'administrator' === $role ) {
				$is_admin = true;
				break;
			}
		}
		if ( ! $is_admin ) {
			echo 'エラー' + ': ' + '管理者権限がありません。';
			die();
		}

		echo esc_html(wp_create_nonce( self::NONCE_API ) );
		die();
	}
}
