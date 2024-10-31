<?php
/**
 * QAデータ（DBやファイルに入っている）にアクセスするためのラッパークラス
 * wpdbと似たコマンドを揃え、SQLに対応するが、セキュリティ対策も含め、決まったいくつかのコマンド(限定されたSELECTなど）しか受け付けないようにする。
 * またwpdbのprepareは単にSQLインジェクション対策を施したSQL文字列を返すだけなので（つまり純粋なDBのprepareとは違う）、そのまま活用することができる。
 * QAの関数群は、このqahm_dbを利用することでデータがどこに保存されているのかを意識せずに任意のデータをひくことができる。
 * @package qa_heatmap
 */

$qahm_db = new QAHM_Db();
class QAHM_Db extends QAHM_File_Data {
	
	public $prefix;
	public $insert_id;
	public $last_error;

	/**
	 * const
	 */
	const QAHM_VIEW_PV_COL = array (
		'pv_id',
		'reader_id',
		'UAos',
		'UAbrowser',
		'page_id',
		'url',
		'title',
		'device_id',
		'source_id',
		'utm_source',
		'source_domain',
		'medium_id',
		'utm_medium',
		'campaign_id',
		'utm_campaign',
		'session_no',
		'access_time',
		'pv',
		'speed_msec',
		'browse_sec',
		'is_last',
		'is_newuser',
		'version_id',
		'is_raw_p',
		'is_raw_c',
		'is_raw_e',
	);
	
	const QAHM_VR_VIEW_PV_COL = array (
		'pv_id',
		'reader_id',
		'UAos',
		'UAbrowser',
		'page_id',
		'url',
		'title',
		'device_id',
		'source_id',
		'utm_source',
		'source_domain',
		'medium_id',
		'utm_medium',
		'campaign_id',
		'utm_campaign',
		'session_no',
		'access_time',
		'pv',
		'speed_msec',
		'browse_sec',
		'is_last',
		'is_newuser',
		'version_id',
		'is_raw_p',
		'is_raw_c',
		'is_raw_e',
		'version_no',
	);

	const QAHM_SUMMARY_DAYS_ACCESS_DETAIL = array (
		'date',
		'device_id',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'is_newuser',
		'is_QA',
		'pv_count',
		'session_count',
		'user_count',
		'bounce_count',
		'time_on_page',
	);

	const QAHM_VR_SUMMARY_ALLPAGE_COL = array (
		'date',
		'page_id',
		'device_id',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'is_newuser',
		'is_QA',
		'pv_count',
		'user_count',
		'bounce_count',
		'exit_count',
		'time_on_page',
		'lp_count',
		'title',
		'url',
		'wp_qa_id'
	);

	const QAHM_VR_SUMMARY_LANDINGPAGE_COL = array (
		'date',
		'page_id',
		'device_id',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'is_newuser',
		'second_page',
		'is_QA',
		'pv_count',
		'session_count',
		'user_count',
		'bounce_count',
		'session_time',
		'title',
		'url',
		'wp_qa_id'
	);

	const QAHM_VR_SUMMARY_GROWTHPAGE_COL = array (
		'page_id',
		'device_id',
		'utm_source',
		'utm_medium',
		'start_session_count',
		'end_session_count',
		'title',
		'url',
		'wp_qa_id'
	);


	public function __construct() {
		// wordpressはコアの初期化→dbの初期化→その他の初期化（プラグイン含む）といった流れになるので
		// コンストラクタの時点でおそらくwpdbが読み込まれているはず
		// ダメならフックを用いて以下の処理の実行タイミングを変えるべき imai
		global $wpdb;
		$this->prefix     = $wpdb->prefix;
		$this->insert_id  = 0;
		$this->last_error = '';
	}

	/**
	 * useful
	 */
	public function alltable_name () {
		$tbname_ary = [];
		foreach ( QAHM_DB_OPTIONS as $key => $val ) {
			$tbname = substr( $key,0, -8 );
			$tbname_ary[] = $this->prefix . $tbname;
		}
		return $tbname_ary;
	}
	/**
	 * prepare
	 */
	public function prepare( $query, ...$args ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- This code simply wraps $wpdb->prepare.
		return $wpdb->prepare( $query, ...$args );
	}

	/**
	 * print_error
	 */
	public function print_error() {
		global $wpdb;
		return $wpdb->print_error();
	}

	/**
	 * result
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		global $wpdb;

		//switch function from table name
		$tb_view_pv    = $this->prefix . 'view_pv';
		$tb_vr_view_pv = $this->prefix . 'vr_view_pv';
		$tb_view_ver   = $this->prefix . 'view_page_version_hist';
		$tb_days_access = $this->prefix . 'summary_days_access';
		$tb_days_access_detail = $this->prefix . 'summary_days_access_detail';
		$tb_vr_summary_allpage = $this->prefix . 'vr_summary_allpage';
		$tb_vr_summary_landingpage = $this->prefix . 'vr_summary_landingpage';

		if ( preg_match('/from ' . $tb_vr_view_pv . '/i', $query ) ) {
			return $this->get_results_view_pv( $query, true );
		} elseif ( preg_match('/from ' . $tb_view_pv . '/i', $query ) ) {
			return $this->get_results_view_pv( $query );
		} elseif ( preg_match('/from ' . $tb_view_ver . '/i', $query ) ) {
			return $this->get_results_view_page_version_hist( $query );
		} elseif( preg_match('/from ' . $tb_days_access_detail . ' /i', $query ) ) {
			return $this->get_results_days_access_detail( $query );
		} elseif( preg_match('/from ' . $tb_days_access . ' /i', $query ) ) {
			return $this->get_results_days_access( $query );
		} elseif( preg_match('/from ' . $tb_vr_summary_allpage . ' /i', $query ) ) {
			return $this->get_results_vr_summary_allpage( $query );
		} elseif( preg_match('/from ' . $tb_vr_summary_landingpage . ' /i', $query ) ) {
			return $this->get_results_vr_summary_landingpage( $query );
        } else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This code simply wraps $wpdb->get_results.Caching cannot be used because the arguments change depending on the situation.
			return $wpdb->get_results( $query, $output );
		}
	}


	/**
	 * result
	 */
	public function get_vr_view_session( $column, $date, $where, $count = false ) {
		global $wp_filesystem;
		global $qahm_time;
		/*

		メモ

		idの優先順位（数が少ない方から優先 / いまはpage_idのみ対応）
		reader_id
		page_id
		version_id
		campaign_id
		source_id
		medium_id

		*/

		// date 必須のため、適切な形になっていないならnullを返す
		// もしも単一の日付で検索することが今後あるなら「if ( strptime( $date_or_id, '%Y-%m-%d' ) ) {」で判定すれば良さそう
		if ( ! preg_match( '/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $date, $date_strings ) ) {
			return null;
		}

		$s_daystr   = $date_strings[1];
		$e_daystr   = $date_strings[2];
		if ( ! $s_daystr || ! $e_daystr ) {
			return null;
		}
		$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
		$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );

		$date_period_ary = new DatePeriod(
			new DateTime( $s_daystr . ' 00:00:00' ),
			new DateInterval('P1D'),
			new DateTime( $e_daystr . ' 23:59:59' )
	   );

		// column配列を作成
		$column = str_replace( ' ', '', $column );
		$column_ary = explode( ',', $column );

		$view_dir = $this->get_data_dir_path( 'view' );
		$traking_id = $this->get_tracking_id();
		$viewpv_dir  = $view_dir . $traking_id . '/view_pv/';
		$viewpv_idx_dir  = $viewpv_dir . 'index/';
		$verhist_dir = $view_dir . $traking_id . '/version_hist/';


		// AND に対応するときは事前にexplode関数で配列に分割するとよさそう


		// whereの構文が対応不可ならnullを返す
		//mkmod 20220617
		$whereok = false;
		$ids_ary = [];
		//単一
		preg_match_all( '/[^ =]+/', $where, $matches );
		if ( isset( $matches[0][0] ) && isset( $matches[0][1] ) ) {
			$id_type = $matches[0][0];
			$id_num  = $matches[0][1];
			if ( ! is_numeric( $id_num ) ) {
				$whereok = false;
			} else {
				$ids_ary[0] = (int) $id_num;
				$whereok    = 'single';
			}
		}


		//複数
		$wheretrim = str_replace(' ', '', $where );
		preg_match_all( '/(.*)in\(([0-9]*,.*)\)+/i', $wheretrim, $matches );
		if ( isset( $matches[1][0] ) && isset( $matches[2][0] ) ) {
			$id_type = $matches[1][0];
			$ids_num = $matches[2][0];
			$ids_ary = explode(',', $ids_num);

			if ( ! is_array( $ids_ary ) ) {
				$whereok = false;
			} else {
				foreach ( $ids_ary as $iii => $id ) {
					$ids_ary[$iii] = (int)$id;
				}
				$whereok = 'multiple';
			}
		}
		if ( ! $whereok ) {
			return null;
		}

		//初期化
		$idx_base        = '';
		$before_idx_file = '';
		switch ( $id_type ) {
			case 'page_id':
				$idx_base = '_pageid.php';
				break;
			case 'pv_id':
				//indexなし（viewpv.phpをそのまま使う）
				break;
			default:
				return null;
		}

		$ret_ary = array();
		$ret_cnt = 0;

		switch ( $id_type ) {
			
			case 'page_id':
				foreach ( $ids_ary as $id_num ) {

					//indexファイルを探す
					$search_range = 100000;
					$search_max = 10000000;
					if ( $id_num > $search_max ) {
						return null;
					}
					$idx_file = '';
					for ( $i = 1; $i < $search_max; $i += $search_range ) {
						if ( $i <= $id_num && $i + $search_range > $id_num ) {
							$idx_file = $i . '-' . ( $i + $search_range - 1 ) . $idx_base;
							break;
						}
					}

					if ( ! $wp_filesystem->exists( $viewpv_idx_dir . $idx_file ) ) {
						continue;
					}

					//mkdummy
					if ( $idx_file !== $before_idx_file) {
						$pageid_idx_file = $this->wrap_get_contents( $viewpv_idx_dir . $idx_file );
						$pageid_idx_ary = $this->wrap_unserialize( $pageid_idx_file );
						$before_idx_file = $idx_file;
					}
					$viewpv_idx_ary = array();

					foreach ( $date_period_ary as $value ) {
						$date_period = $value->format( 'Y-m-d' );
						if ( $pageid_idx_ary[ $id_num ] == false ) {
							continue;
						}
						if ( array_key_exists( $date_period, $pageid_idx_ary[ $id_num ] ) ) {
							$viewpv_idx_ary[ $date_period ] = $pageid_idx_ary[ $id_num ][ $date_period ];
						}
					}

					$viewpv_ary = null;
					$viewpv_dirlist = $this->wrap_dirlist( $viewpv_dir );
					if ( ! $viewpv_dirlist ) {
						return null;
					}

					$searched_pv_id = array();
					$view_pv_idx_ary_keys = array_keys( $viewpv_idx_ary );
					$view_pv_idx_cnt = 0;
					$view_pv_idx_max = count( $viewpv_idx_ary );

					foreach ( $viewpv_dirlist as $viewpv_fileobj ) {
						$view_pv_file_name = $viewpv_fileobj[ 'name' ];

						// この時点でbetweenの最小値、最大値と日付比較を行うことで、更に高速化できそう

						for ( $i = $view_pv_idx_cnt; $i < $view_pv_idx_max; $i++ ) {
							$key = $view_pv_idx_ary_keys[ $i ];
							if ( substr( $view_pv_file_name, 0, 10 ) !== $key ) {
								continue;
							}

							$viewpv_ary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $viewpv_fileobj[ 'name' ] ) );
							foreach ( $viewpv_ary as $idx => $viewpv ) {
								if ( ! is_array( $viewpv_idx_ary[ $key ] ) ) {
									continue;
								}
								foreach ( $viewpv_idx_ary[ $key ] as $viewpv_idx ) {
									if ( (int)$viewpv[ 'pv_id' ] === $viewpv_idx ) {
										// 前回調べたか確認し、既に調べていたら（セッションに組み込まれていたら）スルー
										if ( ! empty( $searched_pv_id ) ) {
											$find = false;
											foreach ( $searched_pv_id as $pv_id ) {
												if ( $pv_id === $viewpv[ 'pv_id' ] ) {
													$find = true;
													break;
												}
											}
											if ( $find ) {
												break;
											}
										}
										$searched_pv_id = array();    // 毎回調べる度にクリアする

										// セッションの構築
										$session_ary = array();
										$pv_no = (int)$viewpv[ 'pv' ];

										// １PV目でなかったとき、前のPVを遡って取りに行く。
										if ( $pv_no > 1 ) {
											$pv_idx = $idx - $pv_no + 1;
											$now_reader = $viewpv[ 'reader_id' ];
											while ( $pv_idx < $idx ) {
												//20220622 pv_noが飛んでいる時があるのでその対策。reader_idが違うなら違うセッションなので処理しない。
												if ( $viewpv_ary[ $pv_idx ][ 'reader_id' ] !== $now_reader ) {
													$pv_idx++;
													continue;
												}
												if ( $count ) {
													$ret_cnt++;
												} else {
													if ( $column === '*' ) {
														if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
															$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
															$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
															if ( $verhist_file ) {
																$verhist_ary = $this->wrap_unserialize( $verhist_file );
																$viewpv_ary[ $pv_idx ][ 'version_no' ] = $verhist_ary[ 0 ]->version_no;
															}
														}

														$session_ary[] = $viewpv_ary[ $pv_idx ];
													} else {
														$temp_ary = array();
														foreach ( $column_ary as $column_val ) {
															switch ( $column_val ) {
																case 'version_no':
																	if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
																		$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
																		$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
																		if ( $verhist_file ) {
																			$verhist_ary = $this->wrap_unserialize( $verhist_file );
																			$temp_ary[ $column_val ] = $verhist_ary[ 0 ]->version_no;
																		}
																	}
																	break;

																default:
																	$temp_ary[ $column_val ] = $viewpv_ary[ $pv_idx ][ $column_val ];
																	break;
															}
														}
														$session_ary[] = $temp_ary;
													}
												}
												$pv_idx++;
											}
										}

										// （$viewpv_idx配列に含まれている）ループ現時点PVと、それ以降のPV（同一セッション）を取得。念のため以下ループ上限（一セッション最大PV数）は10000に設定。※datasearch.jsに関連箇所あり。
										$first_search = true;
										$now_reader = $viewpv[ 'reader_id' ];
										for ( $pv_cnt = 0; $pv_cnt < 10000; $pv_cnt++ ) {
											$pv_idx = $idx + $pv_cnt;

											//is_lastが無く、別セッションになった場合。reader_idで判断。20230410ym
											if ( $viewpv_ary[ $pv_idx ][ 'reader_id' ] !== $now_reader ) {
												$session_ary_last_keynum = count( $session_ary ) - 1;
												$session_ary[ $session_ary_last_keynum ][ 'is_last' ] = '1';
												break;
											}

											if ( $first_search ) {
												$first_search = false;
											} else {
												$searched_pv_id[] = $viewpv_ary[ $pv_idx ][ 'pv_id' ];
											}

											if ( $count ) {
												$ret_cnt++;
											} else {
												if ( $column === '*' ) {
													if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
														$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
														$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
														if ( $verhist_file ) {
															$verhist_ary = $this->wrap_unserialize( $verhist_file );
															$viewpv_ary[ $pv_idx ][ 'version_no' ] = $verhist_ary[ 0 ]->version_no;
														}
													}

													$session_ary[] = $viewpv_ary[ $pv_idx ];
												} else {
													$temp_ary = array();
													foreach ( $column_ary as $column_val ) {
														switch ( $column_val ) {
															case 'version_no':
																if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
																	$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
																	$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
																	if ( $verhist_file ) {
																		$verhist_ary = $this->wrap_unserialize( $verhist_file );
																		$temp_ary[ $column_val ] = $verhist_ary[ 0 ]->version_no;
																	}
																}
																break;

															default:
																$temp_ary[ $column_val ] = $viewpv_ary[ $pv_idx ][ $column_val ];
																break;
														}
													}
													$session_ary[] = $temp_ary;
												}
											}

											if ( $viewpv_ary[ $pv_idx ][ 'is_last' ] ) {
												break;
											}
										}
										//sessionの重複を防ぐ
										$session_find = false;
										for ( $iii = 0; $iii < count($ret_ary); $iii++ ) {
											if ( (int)$ret_ary[$iii][0]['pv_id'] === (int)$session_ary[0]['pv_id'] ) {
												$session_find = true;
												break;
											}
										}
										if ( ! $session_find ) {
											$ret_ary[] = $session_ary;
										}
									}
								}
							}

							$view_pv_idx_cnt++;
						}
						if ( $view_pv_idx_cnt >= $view_pv_idx_max ) {
							break;
						}
					}
				}
			break;

			case 'pv_id':

				$searched_pv_id = array();
				$viewpv_dirlist = $this->wrap_dirlist( $viewpv_dir );
				if ( ! $viewpv_dirlist ) {
					return null;
				}

				foreach ( $ids_ary as $id_num ) {
					$viewpv_ary = null;

					foreach ( $viewpv_dirlist as $viewpv_fileobj ) {
						$view_pv_file_name = $viewpv_fileobj[ 'name' ];	
						//pv_idの場合の処理
						if ( preg_match( '/_(\d+)-(\d+)_/', $view_pv_file_name, $matches ) ) {
							$pvid_min = $matches[ 1 ];
							$pvid_max = $matches[ 2 ];
						}
						if ( $id_num >= $pvid_min && $id_num <= $pvid_max ) {
							$viewpv_ary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $viewpv_fileobj[ 'name' ] ) );
							$key = substr( $view_pv_file_name, 0, 10 );
							
							//fileが存在しない場合はFalseが返る
							if( ! is_array($viewpv_ary) ) {
								continue;
							}
							foreach ( $viewpv_ary as $idx => $viewpv ) {
								if ( (int)$viewpv[ 'pv_id' ] === $id_num ) {
									// 既にセッションに組み込まれていたらスルー（セッションの重複を防ぐ）
									if ( in_array($viewpv['pv_id'], $searched_pv_id) ) {
										break;
									}
									//$searched_pv_id[] = $viewpv[ 'pv_id' ];

									// セッションの構築
									$session_ary = array();
									$pv_no = (int)$viewpv[ 'pv' ];
									$now_reader = $viewpv[ 'reader_id' ];

									// １PV目でなかったとき、前のPVを遡って取りに行く。
									if ( $pv_no > 1 ) {
										$pv_idx = $idx - $pv_no + 1;										
										while ( $pv_idx < $idx ) {
											//20220622 pv_noが飛んでいる時があるのでその対策。reader_idが違うなら違うセッションなので処理しない。
											if ( $viewpv_ary[ $pv_idx ][ 'reader_id' ] !== $now_reader ) {
												$pv_idx++;
												continue;
											}
											$searched_pv_id[] = $viewpv_ary[ $pv_idx ][ 'pv_id' ];
											if ( $count ) {
												$ret_cnt++;
											} else {
												if ( $column === '*' ) {
													if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
														$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
														$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
														if ( $verhist_file ) {
															$verhist_ary = $this->wrap_unserialize( $verhist_file );
															$viewpv_ary[ $pv_idx ][ 'version_no' ] = $verhist_ary[ 0 ]->version_no;
														}
													}

													$session_ary[] = $viewpv_ary[ $pv_idx ];
													
												} else {
													$temp_ary = array();
													foreach ( $column_ary as $column_val ) {
														switch ( $column_val ) {
															case 'version_no':
																if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
																	$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
																	$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
																	if ( $verhist_file ) {
																		$verhist_ary = $this->wrap_unserialize( $verhist_file );
																		$temp_ary[ $column_val ] = $verhist_ary[ 0 ]->version_no;
																	}
																}
																break;

															default:
																$temp_ary[ $column_val ] = $viewpv_ary[ $pv_idx ][ $column_val ];
																break;
														}
													}
													$session_ary[] = $temp_ary;
												}
											}
											$pv_idx++;
										}
									}

									// $viewpv_aryループ現時点PV(=$id_num)と、それ以降のPV（同一セッション）を取得。念のためPVのループ上限は10000に設定
									//$first_search = true;
									for ( $pv_cnt = 0; $pv_cnt < 10000; $pv_cnt++ ) {
										$pv_idx = $idx + $pv_cnt;

										//is_lastが無く、別セッションになった場合。reader_idで判断。
										if ( $viewpv_ary[ $pv_idx ][ 'reader_id' ] !== $now_reader ) {
											$session_ary_last_keynum = count( $session_ary ) - 1;
											$session_ary[ $session_ary_last_keynum ][ 'is_last' ] = '1';
											break;
										}
										
										// 
										//if ( $first_search ) {
										//	$first_search = false;
										//} else {
											$searched_pv_id[] = $viewpv_ary[ $pv_idx ][ 'pv_id' ];
										//}

										if ( $count ) {
											$ret_cnt++;
										} else {
											if ( $column === '*' ) {
												if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
													$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
													$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
													if ( $verhist_file ) {
														$verhist_ary = $this->wrap_unserialize( $verhist_file );
														$viewpv_ary[ $pv_idx ][ 'version_no' ] = $verhist_ary[ 0 ]->version_no;
													}
												}

												$session_ary[] = $viewpv_ary[ $pv_idx ];
											} else {
												$temp_ary = array();
												foreach ( $column_ary as $column_val ) {
													switch ( $column_val ) {
														case 'version_no':
															if ( $viewpv_ary[ $pv_idx ][ 'version_id' ] ) {
																$verhist_filename = $viewpv_ary[ $pv_idx ][ 'version_id' ] . '_version.php';
																$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
																if ( $verhist_file ) {
																	$verhist_ary = $this->wrap_unserialize( $verhist_file );
																	$temp_ary[ $column_val ] = $verhist_ary[ 0 ]->version_no;
																}
															}
															break;

														default:
															$temp_ary[ $column_val ] = $viewpv_ary[ $pv_idx ][ $column_val ];
															break;
													}
												}
												$session_ary[] = $temp_ary;
											}
										}

										if ( $viewpv_ary[ $pv_idx ][ 'is_last' ] ) {
											break;
										}
									}


									$ret_ary[] = $session_ary;

								}
							}
						}
					}
				}
			break;

		}
		//return
		if ( $count ) {
			return $ret_cnt;
		} else {
			if ( empty( $ret_ary ) ) {
				return null;
			} else {
				return $ret_ary;
			}
		}
	}


	public function query( $query ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This code simply wraps $wpdb->query.Caching cannot be used because the arguments change depending on the situation.
		$result = $wpdb->query( $query );
		$this->insert_id  = $wpdb->insert_id;
		$this->last_error = $wpdb->last_error;
		return $result;
	}

	// get_varに問題がありそうなので使わなくなった
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		global $wpdb;

		//switch function from table name
		$tb_view_pv    = $this->prefix . 'view_pv';
		$tb_vr_view_pv = $this->prefix . 'vr_view_pv';
		$tb_view_ver   = $this->prefix . 'view_page_version_hist';
		if ( preg_match('/from ' . $tb_vr_view_pv . '/i', $query ) ) {
			return $this->get_results_view_pv( $query, true );
		} elseif ( preg_match('/from ' . $tb_view_pv . '/i', $query ) ) {
			return $this->get_results_view_pv( $query );
		} elseif ( preg_match('/from ' . $tb_view_ver . '/i', $query ) ) {
			return $this->get_results_view_page_version_hist( $query );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This code simply wraps $wpdb->get_var.Caching cannot be used because the arguments change depending on the situation.
			return $wpdb->get_var( $query, $x, $y );
		}
	}

	/**
	 * useful
	 */
	public function show_column( $table ) {
		global $wpdb;
		$retary = [];
		switch ($table) {
			case 'view_pv':
				$retary = self::QAHM_VIEW_PV_COL;
				break;
				
			case 'vr_view_pv':
				$retary = self::QAHM_VR_VIEW_PV_COL;
				break;

			default:
				$is_table = false;
				foreach ( QAHM_DB_OPTIONS as $table_ver_name ) {
					$tablename = str_replace('_version', '', $table_ver_name);
					if ($tablename === $table) {
						$is_table = true;
					}
				}
				if ($is_table) {
					// 20241018 imai Plugin Checkの結果を見ている最中に、ここのコードがまともに動いていない可能性があると気付いたので修正。ただし未テスト
					//$res = $wpdb->get_results(' columns from '.$tablename);
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
					$res = $wpdb->get_results( $wpdb->prepare('SHOW COLUMNS FROM %s', $tablename), ARRAY_A );
					foreach ($res as $line) {
						$retary[] = $line['Field'];
					}
				}
				break;
		}
		return $retary;
	}
	/**
	 * protected
	 */
	protected function get_results_view_pv( $query = null, $is_vr = false ) {
		global $qahm_time;

		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$traking_id = $this->get_tracking_id();
		$view_dir = $data_dir . 'view/';
		//view_pv dirlist
		$viewpv_dir  = $view_dir . $traking_id . '/view_pv/';
		$viewpv_idx_dir  = $viewpv_dir . '/index/';
		$allfiles = $this->wrap_dirlist( $viewpv_dir );
		$verhist_dir = $view_dir . $traking_id . '/version_hist/';

		// columns
		preg_match( '/select (.*) from/i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = [];
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}
		$is_error = false;
		$results_ary = [];
		$countall   = 0;
		// where date
		if (preg_match('/where access_time between.*([0-9]{4}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_unixtime = $qahm_time->str_to_unixtime( $date_strings[1] );
			$e_unixtime = $qahm_time->str_to_unixtime( $date_strings[2] );

			foreach ( $allfiles as $file ) {
				$filename = $file['name'];
				if ( is_file( $viewpv_dir . $filename ) ) {
					$f_date   = substr( $filename, 0, 10) . ' 00:00:00';

					$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
					if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
						$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						switch ( $columns_ary[0] ) {
							case 'count':
								if ( $columns_ary[1] === '*' ){
									$countall = $countall + count( $tmpary );
								} else {
									foreach ( $tmpary as $bodyary ) {
										if ( $bodyary[$columns_ary[1]] ){
											++$countall;
										}
									}
								}
								break;

							case '*':
								foreach ( $tmpary as &$bodyary ) {
									if ( $is_vr ) {
										$verhist_filename = $bodyary['version_id'] . '_version.php';
										$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
										if ( $verhist_file ) {
											$verhist_ary = $this->wrap_unserialize( $verhist_file );
											$bodyary['version_no'] = $verhist_ary[0]->version_no;
										}
									}
								}
								$results_ary[] = $tmpary;
								break;

							default:
								foreach ( $tmpary as $bodyary ) {
									$lineary = [];
									foreach ($columns_ary as $column ) {
										if ( $is_vr ) {
											switch ( $column ) {
												case 'version_no':
													$verhist_filename = $bodyary['version_id'] . '_version.php';
													$verhist_file = $this->wrap_get_contents( $verhist_dir . $verhist_filename );
													if ( $verhist_file ) {
														$verhist_ary = $this->wrap_unserialize( $verhist_file );
														$lineary[$column] = $verhist_ary[0]->version_no;
													}
													break;

												default:
													$lineary[$column] = $bodyary[$column];
													break;
											}
										} else {
											$lineary[$column] = $bodyary[$column];
										}
									}
									$results_ary[] = $lineary;
								}
								break;
						}
					}
				}
			}


		// where pv_id
		} elseif ( preg_match('/where pv_id = ([0-9]*)/i', $query, $pvidary ) ){
			$pv_id = (int) $pvidary[1];
			for ( $iii = 0; $iii < count( $allfiles ); $iii++ ) {
				$filename = $allfiles[$iii]['name'];
				$fnameexp = explode('_', $filename);
				$pvno_exp = explode('-', $fnameexp[1]);
				$s_pvidno = (int) $pvno_exp[0];
				$e_pvidno = (int) $pvno_exp[1];
				if ($s_pvidno <= $pv_id && $pv_id <= $e_pvidno ){
					$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
					//データは連想配列に入っている
					//取得するデータによって処理をわける
					for ($jjj = 0; $jjj < count($tmpary); $jjj++) {
						if ( (int)$tmpary[$jjj]['pv_id'] === $pv_id ) {
							switch ( $columns_ary[0] ) {
								case 'count':
									if ( $columns_ary[1] === '*' ){
										$countall = 1;
									} else {
										if ( $tmpary[$jjj][$columns_ary[1]] ){
											$countall = 1;
										}
									}
									break;

								case '*':
									$results_ary[] = $tmpary[$jjj];
									break;

								default:
									$lineary = [];
									foreach ($columns_ary as $column ) {
										$lineary[$column] = $tmpary[$jjj][$column];
									}
									$results_ary[] = $lineary;
									break;
							}
							$jjj = 	count($tmpary) + 888;
							$iii = 	count( $allfiles ) + 888;
						}
					}
				}
			}
		
		// where version_id
		} elseif ( preg_match('/where version_id = ([0-9]*)/i', $query, $idary ) ){
			$id = (int) $idary[1];

			$idx_file = $this->get_index_file_contents( $viewpv_idx_dir, 'versionid', $id );
			if ( ! $idx_file ) {
				return false;
			}

			$allfiles_cnt = count( $allfiles );
			foreach ( $idx_file as $date => $id_ary ) {
				for ( $iii = 0; $iii < $allfiles_cnt; $iii++ ) {
					$filename = $allfiles[$iii]['name'];
					if ( ! is_file( $viewpv_dir . $filename ) ) {
						continue;
					}
					
					if( substr( $filename, 0, 10 ) !== $date ) {
						continue;
					}
					
					$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
					
					$jjj = 0;
					$tmp_cnt = count($tmpary);
					foreach ( $id_ary as $id ) {
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						for ( ; $jjj < $tmp_cnt; $jjj++ ) {
							if ( (int) $tmpary[$jjj]['pv_id'] === (int) $id ) {
								switch ( $columns_ary[0] ) {
									case 'count':
										if ( $columns_ary[1] === '*' ){
											++$countall;
										} else {
											if ( $tmpary[$jjj][$columns_ary[1]] ){
												++$countall;
											}
										}
										break;

									case '*':
										$results_ary[] = $tmpary[$jjj];
										break;

									default:
										$lineary = [];
										foreach ($columns_ary as $column ) {
											$lineary[$column] = $tmpary[$jjj][$column];
										}
										$results_ary[] = $lineary;
								}

								$jjj++;
								break;
							}
						}
					}

					$iii++;
					break;
				}
			}

		// where page_id
		// version_idとほぼ同じ手法なので、余裕があればソースをマージした方が良さそう
		} elseif ( preg_match('/where page_id = ([0-9]*)/i', $query, $idary ) ){
			$id = (int) $idary[1];

			$idx_file = $this->get_index_file_contents( $viewpv_idx_dir, 'pageid', $id );
			if ( ! $idx_file ) {
				return false;
			}

			$allfiles_cnt = count( $allfiles );
			foreach ( $idx_file as $date => $id_ary ) {
				for ( $iii = 0; $iii < $allfiles_cnt; $iii++ ) {
					$filename = $allfiles[$iii]['name'];
					if ( ! is_file( $viewpv_dir . $filename ) ) {
						continue;
					}
					
					if( substr( $filename, 0, 10 ) !== $date ) {
						continue;
					}
					
					$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
					
					$jjj = 0;
					$tmp_cnt = count($tmpary);
					foreach ( $id_ary as $id ) {
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						for ( ; $jjj < $tmp_cnt; $jjj++ ) {
							if ( (int) $tmpary[$jjj]['pv_id'] === (int) $id ) {
								switch ( $columns_ary[0] ) {
									case 'count':
										if ( $columns_ary[1] === '*' ){
											++$countall;
										} else {
											if ( $tmpary[$jjj][$columns_ary[1]] ){
												++$countall;
											}
										}
										break;

									case '*':
										$results_ary[] = $tmpary[$jjj];
										break;

									default:
										$lineary = [];
										foreach ($columns_ary as $column ) {
											$lineary[$column] = $tmpary[$jjj][$column];
										}
										$results_ary[] = $lineary;
								}

								$jjj++;
								break;
							}
						}
					}

					$iii++;
					break;
				}
			}

		// 必須項目の指定がないのでエラー
		} else {
			$is_error = true;
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_ary;
			}
		}
	}


	/**
	 * protected
	 */
	protected function get_results_view_page_version_hist( $query = null ) {
		global $qahm_time;

		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$traking_id = $this->get_tracking_id();
		$view_dir = $data_dir . 'view/';
		//view_pv dirlist
		$verhist_dir = $view_dir . $traking_id . '/version_hist/';
		$verhist_idx_dir = $view_dir . $traking_id . '/version_hist/index/';
		$allfiles = $this->wrap_dirlist( $verhist_dir );

		// columns
		preg_match( '/select (.*?) /i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = array();
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}
		$is_error = false;
		$results_ary = [];
		$countall   = 0;
		// where date
		if (preg_match('/where update_date between.*([0-9]{4}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_unixtime = $qahm_time->str_to_unixtime( $date_strings[1] );
			$e_unixtime = $qahm_time->str_to_unixtime( $date_strings[2] );

			foreach ( $allfiles as $file ) {
				$filename = $file['name'];
				if ( is_file( $verhist_dir . $filename ) ) {
					$f_date   = substr( $filename, 0, 10) . ' 00:00:00';

					$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
					if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
						$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_dir . $filename ) );
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						switch ( $columns_ary[0] ) {
							case 'count':
								if ( $columns_ary[1] === '*' ){
									$countall = $countall + count( $tmpary );
								} else {
									foreach ( $tmpary as $bodyary ) {
										if ( $bodyary[$columns_ary[1]] ){
											++$countall;
										}
									}
								}
								break;

							case '*':
								$results_ary[] = $tmpary;
								break;

							default:
								foreach ( $tmpary as $bodyary ) {
									$lineary = [];
									foreach ($columns_ary as $column ) {
										$lineary[$column] = $bodyary[$column];
									}
									$results_ary[] = $lineary;
								}
								break;
						}
					}
				}
			}


		// where version_id
		} elseif ( preg_match('/where version_id = ([0-9]*)/i', $query, $veridary ) ){
			$version_id = (int) $veridary[1];
			$filename = $version_id . '_version.php';
			$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_dir . $filename ) );
			
			// stdClassから連想配列にキャスト変換
			$tmpary = json_decode(wp_json_encode($tmpary), true);

			//データは連想配列に入っている
			//取得するデータによって処理をわける
			for ( $jjj = 0; $jjj < count($tmpary); $jjj++ ) {
				if ( (int) $tmpary[$jjj]['version_id'] === $version_id ) {
					switch ( $columns_ary[0] ) {
						case 'count':
							if ( $columns_ary[1] === '*' ){
								$countall = 1;
							} else {
								if ( $tmpary[$jjj][$columns_ary[1]] ){
									$countall = 1;
								}
							}
							break;

						case '*':
							$results_ary[] = $tmpary[$jjj];
							break;

						default:
							$lineary = [];
							foreach ($columns_ary as $column ) {
								$lineary[$column] = $tmpary[$jjj][$column];
							}
							$results_ary[] = $lineary;
							break;
					}
				}
			}
		} elseif ( preg_match('/where page_id = ([0-9]*)/i', $query, $pageidary ) ){
			$page_id = (int) $pageidary[1];

			$id_ary = $this->get_index_file_contents( $verhist_idx_dir, 'pageid', $page_id );
			if ( ! $id_ary ) {
				return false;
			}

			foreach ( $id_ary as $id ) {
				$filename = $id . '_version.php';
				if ( is_file( $verhist_dir . $filename ) ) {
					$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_dir . $filename ) );
					
					// stdClassから連想配列にキャスト変換
					$tmpary = json_decode(wp_json_encode($tmpary), true);

					//データは連想配列に入っている
					//取得するデータによって処理をわける
					for ( $jjj = 0; $jjj < count($tmpary); $jjj++ ) {
						if ( (int) $tmpary[$jjj]['page_id'] === (int) $page_id ) {
							switch ( $columns_ary[0] ) {
								case 'count':
									if ( $columns_ary[1] === '*' ){
										$countall = 1;
									} else {
										if ( $tmpary[$jjj][$columns_ary[1]] ){
											$countall = 1;
										}
									}
									break;

								case '*':
									$results_ary[] = $tmpary[$jjj];
									break;

								default:
									$lineary = [];
									foreach ($columns_ary as $column ) {
										$lineary[$column] = $tmpary[$jjj][$column];
									}
									$results_ary[] = $lineary;
									break;
							}
						}
					}
				}
			}
		// 必須項目の指定がないのでエラー
		} else {
			$is_error = true;
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_ary;
			}
		}
	}

	protected function get_results_days_access( $query = null ) {
		global $qahm_time;
		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';
		$summary_days_access_file = $summary_dir . 'days_access.php';
		global $wp_filesystem;
		// columns
		preg_match( '/select (.*) from/i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = [];
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}
		$is_error = false;
		$results_ary = [];
		$countall   = 0;

        // アクセス一覧を読み込む。存在しなければfalseを返す
		if ( $wp_filesystem->exists( $summary_days_access_file ) ) {
			$days_access_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_days_access_file ) );
		} else {
			return false;
		}
		// where date
		if (preg_match('/where date between.*([0-9]{4}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_daystr   = $date_strings[1];
			$e_daystr   = $date_strings[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );

			$is_loop = true;
			$loopcnt = 0;
			while ( $is_loop ) {
			    $rowdate      = $days_access_ary[$loopcnt]['date'];
			    $row_unixtime = $qahm_time->str_to_unixtime( $rowdate . ' 00:00:00' );

			    if ( $s_unixtime <= $row_unixtime && $row_unixtime <= $e_unixtime) {
                    //データは連想配列に入っている
                    //取得するデータによって処理をわける
                    switch ( $columns_ary[0] ) {
                        case 'count':
                            if ( $columns_ary[1] === '*' ){
                                ++$countall;
                            } else {
                                if ( $days_access_ary[$loopcnt][$columns_ary[1]] ){
                                    ++$countall;
                                }
                            }
                            break;

                        case '*':
                            $results_ary[] = $days_access_ary[$loopcnt];
                            break;

                        default:
                            $lineary = [];
                            foreach ($columns_ary as $column ) {
                                $lineary[$column] = $days_access_ary[$loopcnt][$column];
                            }
                            $results_ary[] = $lineary;
                            break;
                    }

                }
                if ( $e_unixtime < $row_unixtime ) {
                    $is_loop = false;
                }
			    if ( $rowdate === $e_daystr ) {
                    $is_loop = false;
				}
                ++$loopcnt;
			    if ( $loopcnt >= count( $days_access_ary ) ) {
                    $is_loop = false;
				}
            }

		// where pv_id
		// 必須項目の指定がないのでエラー
		} else {
			$is_error = true;
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_ary;
			}
		}
	}

	/**
	 * @param null $query
	 * @return array|bool|int
	 */
	protected function get_results_days_access_detail( $query = null ) {
		global $qahm_time;
		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';
		$summary_days_access_detail_file = $summary_dir . 'days_access_detail.php';
		global $wp_filesystem;
		// columns
		preg_match( '/select (.*) from/i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = [];
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}
		$is_error = false;
		$results_ary = [];
		$results_raw_ary = [];
		$countall   = 0;

        // アクセス一覧を読み込む。存在しなければfalseを返す
		if ( $wp_filesystem->exists( $summary_days_access_detail_file ) ) {
			$days_access_detail_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_days_access_detail_file ) );
		} else {
			return false;
		}
		// where date
		if (preg_match('/where date between.*([0-9]{4}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_daystr   = $date_strings[1];
			$e_daystr   = $date_strings[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );

			$is_loop = true;
			$loopcnt = 0;
			while ( $is_loop ) {
			    $rowdate      = $days_access_detail_ary[$loopcnt]['date'];
			    $row_unixtime = $qahm_time->str_to_unixtime( $rowdate . ' 00:00:00' );
				$tmp_retary   = [];
			    if ( $s_unixtime <= $row_unixtime && $row_unixtime <= $e_unixtime) {
                    //データは連想配列に入っている
                    //取得するデータによって処理をわける
                    switch ( $columns_ary[0] ) {
                        case 'count':
                            if ( $columns_ary[1] === '*' ){
                                ++$countall;
                            } else {
                                if ( $days_access_detail_ary[$loopcnt][$columns_ary[1]] ){
                                    ++$countall;
                                }
                            }
                            break;

                        case '*':
                            $tmp_retary = $days_access_detail_ary[$loopcnt]['data'];
							foreach ( $tmp_retary as $idxd => $aryd ) {
								foreach ( $aryd as $idxs => $arys) {
									foreach ( $arys as $idxm => $arym) {
										foreach ( $arym as $idxc => $aryc) {
											foreach ( $aryc as $idxn => $aryn) {
												foreach ( $aryn as $idxq => $aryq) {
													$results_raw_ary[] = array
													(self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[0]  => $rowdate,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[1]  => $idxd,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[2]  => $idxs,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[3]  => $idxm,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[4]  => $idxc,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[5]  => $idxn,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[6]  => $idxq,
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[7]  => $tmp_retary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['pv_count'],
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[8]  => $tmp_retary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['session_count'],
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[9]  => $tmp_retary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['user_count'],
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[10] => $tmp_retary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['bounce_count'],
													 self::QAHM_SUMMARY_DAYS_ACCESS_DETAIL[11] => $tmp_retary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['time_on_page']
													);
												}
											}
										}
									}
								}
							}
                            break;

                        default:
                            break;
                    }

                }
                if ( $e_unixtime < $row_unixtime ) {
                    $is_loop = false;
                }
			    if ( $rowdate === $e_daystr ) {
                    $is_loop = false;
				}
                ++$loopcnt;
			    if ( $loopcnt >= count( $days_access_detail_ary ) ) {
                    $is_loop = false;
				}
            }

		// where pv_id
		// 必須項目の指定がないのでエラー
		} else {
			$is_error = true;
		}
		//配列の要素を文字列変換
		$sid_ary = [];
		$mid_ary = [];
		$cid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$sid = (int)$line_ary['utm_source'];
			if ( !isset( $sid_ary[$sid]) ) {
				$query = 'select utm_source, source_domain from ' . $this->prefix . 'qa_utm_sources where source_id=%d';
				$query = $this->prepare($query, $sid);
				$utm_source = $this->get_results( $query );
				if ( ! empty( $utm_source ) ) {
					$results_raw_ary[$idx]['utm_source'] = $utm_source[0]->utm_source;
					$results_raw_ary[$idx]['source_domain'] = $utm_source[0]->source_domain;
					$sid_ary[$sid] = array( $utm_source[0]->utm_source, $utm_source[0]->source_domain );
				}
			} else {
				$results_raw_ary[$idx]['utm_source']    = $sid_ary[$sid][0];
				$results_raw_ary[$idx]['source_domain'] = $sid_ary[$sid][1];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
			$cid = (int)$line_ary['utm_campaign'];
			if ( !isset( $cid_ary[$cid]) ) {
				$query = 'select utm_campaign from ' . $this->prefix . 'qa_utm_campaigns where campaign_id=%d';
				$query = $this->prepare( $query, $cid );
				$utm_campaign = $this->get_results( $query );
				if ( ! empty( $utm_campaign ) ) {
					$results_raw_ary[$idx]['utm_campaign'] = $utm_campaign[0]->utm_campaign;
					$cid_ary[$cid] = $utm_campaign[0]->utm_campaign;
				}
			} else {
				$results_raw_ary[$idx]['utm_campaign'] = $cid_ary[$cid];
			}
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_raw_ary;
			}
		}
	}

	protected function get_results_vr_summary_landingpage( $query = null ) {
		global $qahm_time;
		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';

		global $wp_filesystem;

		// columns
		preg_match( '/select (.*) from/i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = [];
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}

		$is_error = false;
		$results_raw_ary = [];
		$countall   = 0;
		$allfiles = $this->wrap_dirlist( $summary_dir );

		// where date
		if (preg_match('/where date between.*([0-9]{4}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_daystr   = $date_strings[1];
			$e_daystr   = $date_strings[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );

			foreach ( $allfiles as $file ) {
				$filename = $file[ 'name' ];
				if ( strrpos( $filename, 'landingpage.php' ) === false ) {
					continue;
				} else {
					$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';

					$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
					if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
						$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $filename ) );
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						switch ( $columns_ary[ 0 ] ) {
							case 'count':
								if ( $columns_ary[ 1 ] === '*' ) {
									$countall = $countall + count( $tmpary );
								} else {
									foreach ( $tmpary as $bodyary ) {
										if ( $bodyary[ $columns_ary[ 1 ] ] ) {
											++$countall;
										}
									}
								}
								break;

							case '*':
								foreach ( $tmpary as $idxp => $aryp ) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idx2 => $ary2 ) {
															foreach ( $ary2 as $idxq => $aryq ) {
																$results_raw_ary[] = array
																( self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 0 ] => $f_date,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 1 ] => $idxp,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 2 ] => $idxd,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 3 ] => $idxs,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 4 ] => $idxm,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 5 ] => $idxc,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 6 ] => $idxn,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 7 ] => $idx2,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 8 ] => $idxq,
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 9 ]  => $tmpary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'pv_count' ],
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 10 ] => $tmpary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ],
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 11 ] => $tmpary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'user_count' ],
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 12 ] => $tmpary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'bounce_count' ],
																	self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 13 ] => $tmpary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_time' ],
																);
															}
														}
													}
												}
											}
										}
									}
								}
								break;

							default:
								break;
						}
					}
				}
			}
		}
		//配列の要素を文字列変換
		$pid_ary = [];
		$sid_ary = [];
		$mid_ary = [];
		$cid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$pid = (int)$line_ary['page_id'];
			if ( !isset( $pid_ary[$pid]) ) {
				$query = 'select title, url, wp_qa_id from ' . $this->prefix . 'qa_pages where page_id=%d';
				$query = $this->prepare($query, $pid);
				$page  = $this->get_results( $query );
				$results_raw_ary[$idx]['title'] = $page[0]->title;
				$results_raw_ary[$idx]['url'] = $page[0]->url;
				$results_raw_ary[$idx]['wp_qa_id'] = $page[0]->wp_qa_id;
				$pid_ary[$pid] = array( $page[0]->title, $page[0]->url, $page[0]->wp_qa_id );
			} else {
				$results_raw_ary[$idx]['title']    = $pid_ary[$pid][0];
				$results_raw_ary[$idx]['url']      = $pid_ary[$pid][1];
				$results_raw_ary[$idx]['wp_qa_id'] = $pid_ary[$pid][2];
			}
			$sid = (int)$line_ary['utm_source'];
			if ( !isset( $sid_ary[$sid]) ) {
				$query = 'select utm_source, source_domain from ' . $this->prefix . 'qa_utm_sources where source_id=%d';
				$query = $this->prepare($query, $sid);
				$utm_source = $this->get_results( $query );
				if ( ! empty( $utm_source ) ) {
					$results_raw_ary[$idx]['utm_source'] = $utm_source[0]->utm_source;
					$results_raw_ary[$idx]['source_domain'] = $utm_source[0]->source_domain;
					$sid_ary[$sid] = array( $utm_source[0]->utm_source, $utm_source[0]->source_domain );
				}
			} else {
				$results_raw_ary[$idx]['utm_source']    = $sid_ary[$sid][0];
				$results_raw_ary[$idx]['source_domain'] = $sid_ary[$sid][1];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
			$cid = (int)$line_ary['utm_campaign'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_campaign from ' . $this->prefix . 'qa_utm_campaigns where campaign_id=%d';
				$query = $this->prepare( $query, $cid );
				$utm_campaign = $this->get_results( $query );
				if ( ! empty( $utm_campaign ) ) {
					$results_raw_ary[$idx]['utm_campaign'] = $utm_campaign[0]->utm_campaign;
					$cid_ary[$cid] = $utm_campaign[0]->utm_campaign;
				}
			} else {
				$results_raw_ary[$idx]['utm_campaign'] = $cid_ary[$cid];
			}
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_raw_ary;
			}
		}
	}

	protected function get_results_vr_summary_allpage( $query = null ) {
		global $qahm_time;
		//view_pv table is in file
		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';


		// columns
		preg_match( '/select (.*) from/i', $query, $sel_columns );
		$sel_column = str_replace( ' ', '', $sel_columns[1] );
		$columns_ary = [];
		if ( $sel_column === '*' ) {
			$columns_ary[0] = '*';
		} elseif ( preg_match('/count\((.*)\)/', $sel_column,$counts ) ) {
			$columns_ary[0] = 'count';
			$columns_ary[1] = $counts[1];
		} else {
			$columns_ary = explode( ',', $sel_column );
		}

		$is_error = false;
		$results_raw_ary = [];
		$countall   = 0;
		$allfiles = $this->wrap_dirlist( $summary_dir );

		// where date
		if (preg_match('/where date between.*([0-9]{4}.[0-9]{2}.[0-9]{2}).*and.*([0-9]{4}.[0-9]{2}.[0-9]{2})/i', $query, $date_strings )) {
			$s_daystr   = $date_strings[1];
			$e_daystr   = $date_strings[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );

			foreach ( $allfiles as $file ) {
				$filename = $file[ 'name' ];
				if ( strrpos( $filename, 'allpage.php' ) === false ) {
					continue;
				} else {
					$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';

					$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
					if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
						$tmpary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $filename ) );
						//データは連想配列に入っている
						//取得するデータによって処理をわける
						switch ( $columns_ary[ 0 ] ) {
							case 'count':
								if ( $columns_ary[ 1 ] === '*' ) {
									$countall = $countall + count( $tmpary );
								} else {
									foreach ( $tmpary as $bodyary ) {
										if ( $bodyary[ $columns_ary[ 1 ] ] ) {
											++$countall;
										}
									}
								}
								break;

							case '*':
								foreach ( $tmpary as $idxp => $aryp ) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idxq => $aryq ) {
															$results_raw_ary[] = array
															(   self::QAHM_VR_SUMMARY_ALLPAGE_COL[0] => $f_date,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[1] => $idxp,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[2] => $idxd,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[3] => $idxs,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[4] => $idxm,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[5] => $idxc,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[6] => $idxn,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[7] => $idxq,
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[8] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['pv_count'],
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[9] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['user_count'],
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[10] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['bounce_count'],
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[11] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['exit_count'],
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[12] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['time_on_page'],
																self::QAHM_VR_SUMMARY_ALLPAGE_COL[13] => $tmpary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['lp_count']
															);
														}
													}
												}
											}
										}
									}
								}
								break;

							default:
								break;
						}
					}
				}
			}
		}
		//配列の要素を文字列変換
		$pid_ary = [];
		$sid_ary = [];
		$mid_ary = [];
		$cid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$pid = (int)$line_ary['page_id'];
			if ( !isset( $pid_ary[$pid]) ) {
				$query = 'select title, url, wp_qa_id from ' . $this->prefix . 'qa_pages where page_id=%d';
				$query = $this->prepare($query, $pid);
				$page  = $this->get_results( $query );
				$results_raw_ary[$idx]['title'] = $page[0]->title;
				$results_raw_ary[$idx]['url'] = $page[0]->url;
				$results_raw_ary[$idx]['wp_qa_id'] = $page[0]->wp_qa_id;
				$pid_ary[$pid] = array( $page[0]->title, $page[0]->url, $page[0]->wp_qa_id );
			} else {
				$results_raw_ary[$idx]['title']    = $pid_ary[$pid][0];
				$results_raw_ary[$idx]['url']      = $pid_ary[$pid][1];
				$results_raw_ary[$idx]['wp_qa_id'] = $pid_ary[$pid][2];
			}
			$sid = (int)$line_ary['utm_source'];
			if ( !isset( $sid_ary[$sid]) ) {
				$query = 'select utm_source, source_domain from ' . $this->prefix . 'qa_utm_sources where source_id=%d';
				$query = $this->prepare($query, $sid);
				$utm_source = $this->get_results( $query );
				if ( ! empty( $utm_source ) ) {
					$results_raw_ary[$idx]['utm_source'] = $utm_source[0]->utm_source;
					$results_raw_ary[$idx]['source_domain'] = $utm_source[0]->source_domain;
					$sid_ary[$sid] = array( $utm_source[0]->utm_source, $utm_source[0]->source_domain );
				}
			} else {
				$results_raw_ary[$idx]['utm_source']    = $sid_ary[$sid][0];
				$results_raw_ary[$idx]['source_domain'] = $sid_ary[$sid][1];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
			$cid = (int)$line_ary['utm_campaign'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_campaign from ' . $this->prefix . 'qa_utm_campaigns where campaign_id=%d';
				$query = $this->prepare( $query, $cid );
				$utm_campaign = $this->get_results( $query );
				if ( ! empty( $utm_campaign ) ) {
					$results_raw_ary[$idx]['utm_campaign'] = $utm_campaign[0]->utm_campaign;
					$cid_ary[$cid] = $utm_campaign[0]->utm_campaign;
				}
			} else {
				$results_raw_ary[$idx]['utm_campaign'] = $cid_ary[$cid];
			}
		}

		//返値をセット
		if ( $is_error ) {
			return false;
		} else {
			if ( $columns_ary[0] === 'count' ) {
				return $countall;
			}else{
				return $results_raw_ary;
			}
		}
	}



	private function get_index_file_contents( $dir_path, $id_type, $id_num ) {
		$file_name    = null;
		$search_range = 100000;
		$search_max   = 10000000;
		if ( $id_num > $search_max ) {
			return null;
		}
		for ( $i = 1; $i < $search_max; $i += $search_range ) {
			if ( $i <= $id_num && $i + $search_range > $id_num ) {
				$file_name = $i . '-' . ( $i + $search_range - 1 ) . '_' . $id_type . '.php';
				break;
			}
		}

		if ( ! $file_name || ! $this->wrap_exists( $dir_path . $file_name ) ) {
			return null;
		}

		$file = $this->wrap_unserialize( $this->wrap_get_contents( $dir_path . $file_name ) );
		return $file[$id_num];
	}


	//-----------------------------
	//summary summary pages file
	//
	public function summary_days_landingpages ( $dateterm ) {
		global $qahm_time;
		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';
		$sumallday_ary = [];
		$results_raw_ary = [];
		if ( preg_match('/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $dateterm, $datestrs ) ) {
			$s_daystr = $datestrs[1];
			$e_daystr = $datestrs[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );
			$allfiles = $this->wrap_dirlist( $summary_dir );
			if ($allfiles) {
				//まず該当ファイルだけを取得する
				$sum_filename_ary   = [];
				//まず一月分のデータがどこまで揃っているか確認
				$start1mon_utime = 0;
				$end1mon_utime = 0;
				foreach ( $allfiles as $file ) {
					$filename = $file[ 'name' ];
					if ( strrpos( $filename, 'landingpage_1mon.php' ) === false ) {
						continue;
					} else {
						$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';
						$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
						if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
							$sum_filename_ary[] = $filename;
							if ( $start1mon_utime === 0 ) {
								$start1mon_utime = $qahm_time->str_to_unixtime( $f_date );
							} elseif (  $f_unixtime < $start1mon_utime ) {
								$start1mon_utime = $f_unixtime;
							}
							$f_year = (int)substr( $filename, 0, 4 );
							$f_monx = (int)substr( $filename, 5, 2 );
							if ( $f_monx === 12 ) {
								$next_year = $f_year + 1;
								$next_year = (string)$next_year;
								$next_monx = '01';
							} else {
								$next_year = (string)$f_year;
								$next_monx = $f_monx + 1;
								$next_monx = sprintf('%02d', $next_monx);
							}
							$next_utime = $qahm_time->str_to_unixtime( $next_year . '-' . $next_monx . '-01 00:00:00');
							$next_utime = $next_utime - 1 ;
							if ( $end1mon_utime === 0 ) {
								$end1mon_utime = $next_utime;
							} elseif ( $end1mon_utime < $next_utime ) {
								$end1mon_utime = $next_utime;
							}
						}
					}
				}
				//1monthに入らなかった日を追加
				foreach ( $allfiles as $file ) {
					$filename = $file[ 'name' ];
					if ( strrpos( $filename, 'landingpage.php' ) === false ) {
						continue;
					} else {
						$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';

						$f_unixtime = $qahm_time->str_to_unixtime( $f_date );

						if ( $start1mon_utime !== 0 ) {
							if ( $s_unixtime <= $f_unixtime && $f_unixtime < $start1mon_utime ) {
								$sum_filename_ary[] = $filename;
							}
							if ( $end1mon_utime < $f_unixtime && $f_unixtime <= $e_unixtime ) {
								$sum_filename_ary[] = $filename;
							}
						} else {
							if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
								$sum_filename_ary[] = $filename;
							}
						}
					}
				}

				//該当ファイルを集計していく
				$maxcnt = count( $sum_filename_ary );
				$sumallday_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[0] ) );
				for ( $iii = 1; $iii < $maxcnt; $iii++ ) {
					$afterday_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[ $iii ] ) );
					$after_remain = $afterday_ary;
					foreach ( $sumallday_ary as $idxp => $aryp) {
						foreach ( $aryp as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idx2 => $ary2 ) {
												foreach ( $ary2 as $idxq => $aryq ) {
													if ( isset( $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq] ) ) {
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['pv_count'] += $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['pv_count'];
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['session_count'] += $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['session_count'];
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['user_count'] += $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['user_count'];
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['bounce_count'] += $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['bounce_count'];
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['session_time'] += $afterday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['session_time'];
														//要素を消していく
														unset( $after_remain[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq] );
													}
												}
											}
										}
									}
								}
							}
						}
					}
					foreach ( $after_remain as $idxp => $aryp) {
						foreach ( $aryp as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idx2 => $ary2 ) {
												foreach ( $ary2 as $idxq => $aryq ) {
													if ( isset( $after_remain[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['pv_count'] ) ) {
														$sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq] = $after_remain[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq];
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
		}
		foreach ( $sumallday_ary as $idxp => $aryp ) {
			foreach ( $aryp as $idxd => $aryd ) {
				foreach ( $aryd as $idxs => $arys ) {
					foreach ( $arys as $idxm => $arym ) {
						foreach ( $arym as $idxc => $aryc ) {
							foreach ( $aryc as $idxn => $aryn ) {
								foreach ( $aryn as $idx2 => $ary2 ) {
									foreach ( $ary2 as $idxq => $aryq ) {
										if ( isset($sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'pv_count' ]) ) {
											$results_raw_ary[] = array
											(
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 1 ] => $idxp,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 2 ] => $idxd,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 3 ] => $idxs,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 4 ] => $idxm,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 5 ] => $idxc,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 6 ] => $idxn,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 7 ] => $idx2,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 8 ] => $idxq,
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 9 ]  => $sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'pv_count' ],
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 10 ] => $sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ],
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 11 ] => $sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'user_count' ],
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 12 ] => $sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'bounce_count' ],
												self::QAHM_VR_SUMMARY_LANDINGPAGE_COL[ 13 ] => $sumallday_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_time' ],
											);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		//配列の要素を文字列変換
		$pid_ary = [];
		$sid_ary = [];
		$mid_ary = [];
		$cid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$pid = (int)$line_ary['page_id'];
			if ( !isset( $pid_ary[$pid]) ) {
				$query = 'select title, url, wp_qa_id from ' . $this->prefix . 'qa_pages where page_id=%d';
				$query = $this->prepare($query, $pid);
				$page  = $this->get_results( $query );
				$results_raw_ary[$idx]['title'] = $page[0]->title;
				$results_raw_ary[$idx]['url'] = $page[0]->url;
				$results_raw_ary[$idx]['wp_qa_id'] = $page[0]->wp_qa_id;
				$pid_ary[$pid] = array( $page[0]->title, $page[0]->url, $page[0]->wp_qa_id );
			} else {
				$results_raw_ary[$idx]['title']    = $pid_ary[$pid][0];
				$results_raw_ary[$idx]['url']      = $pid_ary[$pid][1];
				$results_raw_ary[$idx]['wp_qa_id'] = $pid_ary[$pid][2];
			}
			$sid = (int)$line_ary['utm_source'];
			if ( !isset( $sid_ary[$sid]) ) {
				$query = 'select utm_source, source_domain from ' . $this->prefix . 'qa_utm_sources where source_id=%d';
				$query = $this->prepare($query, $sid);
				$utm_source = $this->get_results( $query );
				if ( ! empty( $utm_source ) ) {
					$results_raw_ary[$idx]['utm_source'] = $utm_source[0]->utm_source;
					$results_raw_ary[$idx]['source_domain'] = $utm_source[0]->source_domain;
					$sid_ary[$sid] = array( $utm_source[0]->utm_source, $utm_source[0]->source_domain );
				}
			} else {
				$results_raw_ary[$idx]['utm_source']    = $sid_ary[$sid][0];
				$results_raw_ary[$idx]['source_domain'] = $sid_ary[$sid][1];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
			$cid = (int)$line_ary['utm_campaign'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_campaign from ' . $this->prefix . 'qa_utm_campaigns where campaign_id=%d';
				$query = $this->prepare( $query, $cid );
				$utm_campaign = $this->get_results( $query );
				if ( ! empty( $utm_campaign ) ) {
					$results_raw_ary[$idx]['utm_campaign'] = $utm_campaign[0]->utm_campaign;
					$cid_ary[$cid] = $utm_campaign[0]->utm_campaign;
				}
			} else {
				$results_raw_ary[$idx]['utm_campaign'] = $cid_ary[$cid];
			}
		}
		
		return $results_raw_ary;
	}

	public function summary_days_growthpages ( $dateterm ) {
		global $qahm_time;
		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';
		$pids_ary_stt  = [];
		$pids_ary_end  = [];
		$pid_files_ary = [];
		$SUM_TERM = 7;
		$gw_ary = [];
		$results_raw_ary = [];
		if ( preg_match('/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $dateterm, $datestrs ) ) {
			$s_daystr = $datestrs[ 1 ];
			$e_daystr = $datestrs[ 2 ];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );
			$allfiles = $this->wrap_dirlist( $summary_dir );
			if ( $allfiles ) {
				//まず該当ファイルだけを取得する
				$sum_filename_ary = [];
				foreach ( $allfiles as $file ) {
					$filename = $file[ 'name' ];
					if ( strrpos( $filename, 'landingpage.php' ) === false ) {
						continue;
					} else {
						$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';

						$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
						if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
							$sum_filename_ary[] = $filename;
						}
					}
				}
				// 考え方
				// 1.まず最初にpage_idごとのファイルネームをそのまま保存していく。これはループがpidの一回転だから早い
				// 2.次に、上記をみて、今度はファイル名ごとに「最初の7日分のpageid」と「最後の7日分のpageid」の2つの配列を作る
				// 3.次に各ファイルをオープンしていき、「最初の7日分のpageid」と「最後の7日分のpageid」のgrowthpageデータを作る。
				// 4.最後にpage_idごとに集計してgrowthpageデータにして返す。

				//1.ファイルを集計していく
				$maxcnt = count( $sum_filename_ary );
				for ( $iii = 0; $iii < $maxcnt; $iii++ ) {
					$lp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[ $iii ] ) );
					foreach ( $lp_ary as $idxp => $aryp ) {
						if ( isset ( $pid_files_ary[ $idxp ][ $iii ] ) ) {
							break;
						} else {
							$pid_files_ary[ $idxp ][ $iii ] = true;
						}
					}
				}
				// 2.
				foreach ( $pid_files_ary as $pid => $fileidx_ary ) {
					$daycnt = count( $pid_files_ary[ $pid ] );
					$rev_fileidx_ary = array_reverse( $fileidx_ary, true );
					if ( $SUM_TERM < $daycnt ) {
						$sss = 1;
						foreach ( $fileidx_ary as $idx => $true ) {
							$pids_ary_stt[$idx][] = $pid;
							$sss++;
							if ( $SUM_TERM < $sss ) {
								break;
							}
						}
						$eee = 1;
						foreach ( $rev_fileidx_ary as $idx => $true ) {
							$pids_ary_end[$idx][] = $pid;
							$eee++;
							if ( $SUM_TERM < $eee ) {
								break;
							}
						}
					} else {
						//7日経っていない記事は初日と最終日をとる
						foreach ( $fileidx_ary as $idx => $true ) {
							$pids_ary_stt[$idx][] = $pid;
							break;
						}
						foreach ( $rev_fileidx_ary as $idx => $true ) {
							$pids_ary_end[$idx][] = $pid;
							break;
						}
					}
				}

				// 3.
				foreach ( $pids_ary_stt as $iii => $pids_ary ) {
					$lp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[ $iii ] ) );
					foreach ( $pids_ary as $idxp ) {
						foreach ( $lp_ary[$idxp] as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idx2 => $ary2 ) {
												foreach ( $ary2 as $idxq => $aryq ) {
													if ( isset( $gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ] ) ) {
														$gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ] += $lp_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ];
													} else {
														$gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ] = $lp_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ];
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
				foreach ( $pids_ary_end as $iii => $pids_ary ) {
					$lp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[ $iii ] ) );
					foreach ( $pids_ary as $idxp ) {
						foreach ( $lp_ary[$idxp] as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idx2 => $ary2 ) {
												foreach ( $ary2 as $idxq => $aryq ) {
													if ( isset( $gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ] ) ) {
														if ( isset( $gw_ary[ $idxp ][ $idxm ][ 'end_session_count' ] ) ) {
															$gw_ary[ $idxp ][ $idxm ][ 'end_session_count' ] += $lp_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ];
														} else {
															$gw_ary[ $idxp ][ $idxm ][ 'end_session_count' ] = $lp_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ];
														}
													} else {
														$gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ] = 0;
														$gw_ary[ $idxp ][ $idxm ][ 'end_session_count' ] = $lp_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'session_count' ];
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
		}
//		echo '<pre>';
//		print_r($gw_ary);
//		echo '</pre>';
		foreach ( $gw_ary as $idxp => $aryp ) {
			foreach ( $aryp as $idxm => $arym ) {
				if ( isset($gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ]) ) {
					$sttcnt = $gw_ary[ $idxp ][ $idxm ][ 'stt_session_count' ];
					if ( !$sttcnt ){ $sttcnt = 0; }
					$endcnt = $gw_ary[ $idxp ][ $idxm ][ 'end_session_count' ];
					if ( !$endcnt ){ $endcnt = 0; }
					$results_raw_ary[] = array
					(
						self::QAHM_VR_SUMMARY_GROWTHPAGE_COL[ 0 ] => $idxp,
						self::QAHM_VR_SUMMARY_GROWTHPAGE_COL[ 3 ] => $idxm,
						self::QAHM_VR_SUMMARY_GROWTHPAGE_COL[ 4 ] => $sttcnt,
						self::QAHM_VR_SUMMARY_GROWTHPAGE_COL[ 5 ] => $endcnt
					);
				}
			}
		}
		//配列の要素を文字列変換
		$pid_ary = [];
		$sid_ary = [];
		$mid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$pid = (int)$line_ary['page_id'];
			if ( !isset( $pid_ary[$pid]) ) {
				$query = 'select title, url, wp_qa_id from ' . $this->prefix . 'qa_pages where page_id=%d';
				$query = $this->prepare($query, $pid);
				$page  = $this->get_results( $query );
				$results_raw_ary[$idx]['title'] = $page[0]->title;
				$results_raw_ary[$idx]['url'] = $page[0]->url;
				$results_raw_ary[$idx]['wp_qa_id'] = $page[0]->wp_qa_id;
				$pid_ary[$pid] = array( $page[0]->title, $page[0]->url, $page[0]->wp_qa_id );
			} else {
				$results_raw_ary[$idx]['title']    = $pid_ary[$pid][0];
				$results_raw_ary[$idx]['url']      = $pid_ary[$pid][1];
				$results_raw_ary[$idx]['wp_qa_id'] = $pid_ary[$pid][2];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
		}
		return $results_raw_ary;
	}
	
	
	
	public function summary_days_allpages ( $dateterm ) {
		global $qahm_time;
		// dir
		$data_dir = $this->get_data_dir_path();
		$view_dir = $data_dir . 'view/';
		$traking_id = $this->get_tracking_id();
		$myview_dir = $view_dir . $traking_id . '/';
		$summary_dir = $myview_dir . 'summary/';
		$sumallday_ary = [];
		$results_raw_ary = [];
		if ( preg_match('/^date\s*=\s*between\s*([0-9]{4}.[0-9]{2}.[0-9]{2})\s*and\s*([0-9]{4}.[0-9]{2}.[0-9]{2})$/', $dateterm, $datestrs ) ) {
			$s_daystr = $datestrs[1];
			$e_daystr = $datestrs[2];
			$s_unixtime = $qahm_time->str_to_unixtime( $s_daystr . ' 00:00:00' );
			$e_unixtime = $qahm_time->str_to_unixtime( $e_daystr . ' 23:59:59' );
			$allfiles = $this->wrap_dirlist( $summary_dir );
			if ($allfiles) {
				//まず該当ファイルだけを取得する
				$sum_filename_ary   = [];
				//まず一月分のデータがどこまで揃っているか確認
				$start1mon_utime = 0;
				$end1mon_utime = 0;
				foreach ( $allfiles as $file ) {
					$filename = $file[ 'name' ];
					if ( strrpos( $filename, 'allpage_1mon.php' ) === false ) {
						continue;
					} else {
						$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';
						$f_unixtime = $qahm_time->str_to_unixtime( $f_date );
						if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
							$sum_filename_ary[] = $filename;
							if ( $start1mon_utime === 0 ) {
								$start1mon_utime = $qahm_time->str_to_unixtime( $f_date );
							} elseif (  $f_unixtime < $start1mon_utime ) {
								$start1mon_utime = $f_unixtime;
							}
							$f_year = (int)substr( $filename, 0, 4 );
							$f_monx = (int)substr( $filename, 5, 2 );
							if ( $f_monx === 12 ) {
								$next_year = $f_year + 1;
								$next_year = (string)$next_year;
								$next_monx = '01';
							} else {
								$next_year = (string)$f_year;
								$next_monx = $f_monx + 1;
								$next_monx = sprintf('%02d', $next_monx);
							}
							$next_utime = $qahm_time->str_to_unixtime( $next_year . '-' . $next_monx . '-01 00:00:00');
							$next_utime = $next_utime - 1 ;
							if ( $end1mon_utime === 0 ) {
								$end1mon_utime = $next_utime;
							} elseif ( $end1mon_utime < $next_utime ) {
								$end1mon_utime = $next_utime;
							}
						}
					}
				}
				//1monthに入らなかった日を追加
				foreach ( $allfiles as $file ) {
					$filename = $file[ 'name' ];
					if ( strrpos( $filename, 'allpage.php' ) === false ) {
						continue;
					} else {
						$f_date = substr( $filename, 0, 10 ) . ' 00:00:00';

						$f_unixtime = $qahm_time->str_to_unixtime( $f_date );

						if ( $start1mon_utime !== 0 ) {
							if ( $s_unixtime <= $f_unixtime && $f_unixtime < $start1mon_utime ) {
								$sum_filename_ary[] = $filename;
							}
							if ( $end1mon_utime < $f_unixtime && $f_unixtime <= $e_unixtime ) {
								$sum_filename_ary[] = $filename;
							}
						} else {
							if ( $s_unixtime <= $f_unixtime && $f_unixtime <= $e_unixtime ) {
								$sum_filename_ary[] = $filename;
							}
						}
					}
				}

				//該当ファイルを集計していく
				$maxcnt = count( $sum_filename_ary );
				$sumallday_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[0] ) );
				for ( $iii = 1; $iii < $maxcnt; $iii++ ) {
					$afterday_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_dir . $sum_filename_ary[ $iii ] ) );
					$after_remain = $afterday_ary;
					foreach ( $sumallday_ary as $idxp => $aryp) {
						foreach ( $aryp as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idxq => $aryq ) {
												if ( isset( $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]) ) {
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['pv_count'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['pv_count'];
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['user_count'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['user_count'];
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['bounce_count'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['bounce_count'];
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['exit_count'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['exit_count'];
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['time_on_page'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['time_on_page'];
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['lp_count'] += $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['lp_count'];
													//要素を消していく
													unset( $after_remain[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ] );
												}
											}
										}
									}
								}
							}
						}
					}

					foreach ( $after_remain as $idxp => $aryp) {
						foreach ( $aryp as $idxd => $aryd ) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idxq => $aryq ) {
												if ( isset( $afterday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ]['pv_count'] ) ) {
													$sumallday_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ] = $after_remain[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ];
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
		foreach ( $sumallday_ary as $idxp => $aryp ) {
			foreach ( $aryp as $idxd => $aryd ) {
				foreach ( $aryd as $idxs => $arys ) {
					foreach ( $arys as $idxm => $arym ) {
						foreach ( $arym as $idxc => $aryc ) {
							foreach ( $aryc as $idxn => $aryn ) {
								foreach ( $aryn as $idxq => $aryq ) {
									$results_raw_ary[] = array
									(
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[1] => $idxp,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[2] => $idxd,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[3] => $idxs,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[4] => $idxm,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[5] => $idxc,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[6] => $idxn,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[7] => $idxq,
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[8] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['pv_count'],
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[9] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['user_count'],
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[10] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['bounce_count'],
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[11] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['exit_count'],
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[12] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['time_on_page'],
										self::QAHM_VR_SUMMARY_ALLPAGE_COL[13] => $sumallday_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['lp_count']
									);
								}
							}
						}
					}
				}
			}
		}
		//配列の要素を文字列変換
		$pid_ary = [];
		$sid_ary = [];
		$mid_ary = [];
		$cid_ary = [];
		foreach ( $results_raw_ary as $idx => $line_ary ) {
			$pid = (int)$line_ary['page_id'];
			if ( !isset( $pid_ary[$pid]) ) {
				$query = 'select title, url, wp_qa_id from ' . $this->prefix . 'qa_pages where page_id=%d';
				$query = $this->prepare($query, $pid);
				$page  = $this->get_results( $query );
				$results_raw_ary[$idx]['title'] = $page[0]->title;
				$results_raw_ary[$idx]['url'] = $page[0]->url;
				$results_raw_ary[$idx]['wp_qa_id'] = $page[0]->wp_qa_id;
				$pid_ary[$pid] = array( $page[0]->title, $page[0]->url, $page[0]->wp_qa_id );
			} else {
				$results_raw_ary[$idx]['title']    = $pid_ary[$pid][0];
				$results_raw_ary[$idx]['url']      = $pid_ary[$pid][1];
				$results_raw_ary[$idx]['wp_qa_id'] = $pid_ary[$pid][2];
			}
			$sid = (int)$line_ary['utm_source'];
			if ( !isset( $sid_ary[$sid]) ) {
				$query = 'select utm_source, source_domain from ' . $this->prefix . 'qa_utm_sources where source_id=%d';
				$query = $this->prepare($query, $sid);
				$utm_source = $this->get_results( $query );
				if ( ! empty( $utm_source ) ) {
					$results_raw_ary[$idx]['utm_source'] = $utm_source[0]->utm_source;
					$results_raw_ary[$idx]['source_domain'] = $utm_source[0]->source_domain;
					$sid_ary[$sid] = array( $utm_source[0]->utm_source, $utm_source[0]->source_domain );
				}
			} else {
				$results_raw_ary[$idx]['utm_source']    = $sid_ary[$sid][0];
				$results_raw_ary[$idx]['source_domain'] = $sid_ary[$sid][1];
			}
			$mid = (int)$line_ary['utm_medium'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_medium from ' . $this->prefix . 'qa_utm_media where medium_id=%d';
				$query = $this->prepare( $query, $mid );
				$utm_medium = $this->get_results( $query );
				if ( ! empty( $utm_medium ) ) {
					$results_raw_ary[$idx]['utm_medium'] = $utm_medium[0]->utm_medium;
					$mid_ary[$mid] = $utm_medium[0]->utm_medium;
				}else{
					$results_raw_ary[$idx]['utm_medium'] = '';
					$mid_ary[$mid] = '';
				}
			} else {
				$results_raw_ary[$idx]['utm_medium'] = $mid_ary[$mid];
			}
			$cid = (int)$line_ary['utm_campaign'];
			if ( !isset( $mid_ary[$mid]) ) {
				$query = 'select utm_campaign from ' . $this->prefix . 'qa_utm_campaigns where campaign_id=%d';
				$query = $this->prepare( $query, $cid );
				$utm_campaign = $this->get_results( $query );
				if ( ! empty( $utm_campaign ) ) {
					$results_raw_ary[$idx]['utm_campaign'] = $utm_campaign[0]->utm_campaign;
					$cid_ary[$cid] = $utm_campaign[0]->utm_campaign;
				}
			} else {
				$results_raw_ary[$idx]['utm_campaign'] = $cid_ary[$cid];
			}
		}

		return $results_raw_ary;
	}
	//-----------------------------
	//make summary file
	//
	public function make_summary_days_access_detail () {
		global $wp_filesystem;
		global $qahm_time;
		// dir
		$data_dir          = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$viewpv_dir        = $myview_dir . 'view_pv/';
		$verhist_dir       = $myview_dir . 'version_hist/';
		$raw_c_dir         = $viewpv_dir . 'raw_c/';
		$vw_summary_dir    = $myview_dir . 'summary/';
		$summary_days_access_detail_file = $vw_summary_dir . 'days_access_detail.php';
		$days_access_detail_ary = [];
		$s_datetime = '1999-12-31 00:00:00';
		if ( $wp_filesystem->exists($summary_days_access_detail_file ) ) {
			$days_access_detail_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_days_access_detail_file ) );
			if ( 0 < count( $days_access_detail_ary ) ) {
				//　結局、毎月DBから上書きして値が増えるので、DBの期間分は上書きが必要
				// x month ago
				$yearx = $qahm_time->year();
				$month = $qahm_time->month();
				$data_save_month = QAHM_Cron_Proc::DATA_SAVE_MONTH;
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
		}
		//search どの日付から変更するべきか
		$start_idx = 0;
		foreach ( $days_access_detail_ary as $idx => $days_access ) {
			if ( isset( $days_access['sum_datetime'] ) ) {
				if ( ($qahm_time->now_unixtime() - 3 * 60 * 60) < $qahm_time->str_to_unixtime($days_access['sum_datetime'])) {
					//本日集計済みなので、この日付は飛ばすべき
					$s_datetime = $days_access['date'] . ' 23:59:59';
					$start_idx = $idx + 1;
				}
			} else {
				//dummyの古い値を入れる
				$tmpary = array_merge( $days_access, array('sum_datetime' => '1999-12-31 00:00:00') );
				$days_access_detail_ary[$idx] = $tmpary;
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
		if ( count($days_access_detail_ary) <= $start_idx && $start_idx !== 0 ) {
			$start_idx = -1;
		}

		//page関連は一ヶ月毎にサマリーファイルを作る
		$sap_1mon_ary  = [];
		$sl_1mon_ary   = [];
		$make_1mon_cnt = 0;
		$sap_name_1mon = '';
		$lp_name_1mon  = '';
		$base_sel_ary  = [];
		// search view_pv dir
		$allfiles = $this->wrap_dirlist( $viewpv_dir );
		if ($allfiles) {
			foreach ( $allfiles as $file ) {
				$filename = $file[ 'name' ];
				if ( is_file( $viewpv_dir . $filename ) ) {
					$f_date = substr( $filename, 0, 10 );
					$f_datetime = $f_date . ' 00:00:00';
					if ( $qahm_time->str_to_unixtime( $s_datetime ) <= $qahm_time->str_to_unixtime( $f_datetime ) ) {
						if ( substr( $f_date, -2) === '01' ) {
							if ( 0 < $make_1mon_cnt) {
								//exchange user_count
								foreach ( $sl_1mon_ary as $idxp => $aryp) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idx2 => $ary2 ) {
															foreach ( $ary2 as $idxq => $aryq ) {
																if ( isset( $aryq[ 'user_count' ] ) ) {
																	$unique = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
																	$uniqusr = array_values( $unique );
																	$user_cnt = count( $uniqusr );
																	$sl_1mon_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['user_count'] = $user_cnt;
																}
															}
														}
													}
												}
											}
										}
									}
								}
								foreach ( $sap_1mon_ary as $idxp => $aryp) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idxq => $aryq ) {
															if ( isset( $aryq[ 'user_count' ] ) ) {
																$unique  = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
																$uniqusr = array_values( $unique );
																$user_cnt = count( $uniqusr );
																$sap_1mon_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ][ 'user_count' ] = $user_cnt;
															}
														}
													}
												}
											}
										}
									}
								}
								//write 1mon access
								$this->wrap_put_contents( $vw_summary_dir . $lp_name_1mon, $this->wrap_serialize( $sl_1mon_ary ) );
								$this->wrap_put_contents( $vw_summary_dir . $sap_name_1mon, $this->wrap_serialize( $sap_1mon_ary ) );
							}
							$make_1mon_cnt++;
							$lp_name_1mon  = $f_date . '_summary_landingpage_1mon.php';
							$sap_name_1mon = $f_date . '_summary_allpage_1mon.php';

							$sl_1mon_ary  = [];
							$sap_1mon_ary = [];
						}

						//集計対象
						$view_pv_ary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
						
						$raw_c_file_ary = null;
						$raw_c_file_idx = 0;
						$raw_c_filename = str_replace( 'viewpv', 'rawc', $filename );
						if ( $this->wrap_exists( $raw_c_dir . $raw_c_filename ) ) {
							$raw_c_file_ary = $this->wrap_unserialize( $this->wrap_get_contents( $raw_c_dir . $raw_c_filename ) );
						}
						
						//作るファイルは4つ。
						//summary_days_access_detail(spad)
						//YYYY-MM-DD_summary_allpage(sap)
						//YYYY-MM-DD_summary_landingpage(sl)
						//YYYY-MM-DD_summary_event(se)
						$sdad_ary = [];
						$sap_ary  = [];
						$sl_ary   = [];
						$se_ary   = [];
						$total_pv = count( $view_pv_ary );
						foreach ( $view_pv_ary as $idx => $pv_ary ) {
							//Dimensions
							//(int)=nullや空文字は0になる
							$pv_id       = (int)$pv_ary['pv_id'];
							$reader_id   = (int)$pv_ary['reader_id'];
							$page_id     = (int)$pv_ary['page_id'];
							$device_id   = (int)$pv_ary['device_id'];
							$source_id   = (int)$pv_ary['source_id'];
							$medium_id   = (int)$pv_ary['medium_id'];
							$campaign_id = (int)$pv_ary['campaign_id'];
							$version_id  = (int)$pv_ary['version_id'];
							$is_newuser  = (int)$pv_ary['is_newuser'];
							$is_QA       = 1;
							//Metrics
							$browse_sec  = (int)$pv_ary['browse_sec'];
							//条件判定
							$is_last     = (int)$pv_ary['is_last'];
							$is_raw_c    = (int)$pv_ary['is_raw_c'];

							//----make tmp_array
							$tmp_sdad_ary = ['pv_count' => 1, 'session_count' => 0, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'time_on_page' => $browse_sec];
							$tmp_sap_ary  = ['pv_count' => 1, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'exit_count'=> 0, 'time_on_page' => $browse_sec, 'lp_count' => 0];
							$tmp_sl_ary   = ['pv_count' => 1, 'session_count' => 0, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'session_time' => $browse_sec];

							$is_landingpage = 0;
							$second_page    = 0;
							//count session 当日の1ページ目（LP着地）をカウント
							if ( (int)$pv_ary['pv'] === 1 ) {
								$is_landingpage = 1;
							}
							//tmp_array start
							//直帰ページ
							if ( $is_landingpage && $is_last ) {
								$tmp_sdad_ary['bounce_count'] = 1;
								$tmp_sap_ary['bounce_count'] = 1;
								$tmp_sl_ary['bounce_count']   = 1;
							//2ページ以上見たセッション
							} elseif ( $is_landingpage ) {
								if ( $idx < $total_pv -1 ) {
									$second_page = $view_pv_ary[$idx+1]['page_id'];
									//calc session_time
									for ( $iii = $idx + 1; $iii < $total_pv; $iii++ ) {
										$tmp_sl_ary['session_time'] += $view_pv_ary[$iii]['browse_sec'];
										++$tmp_sl_ary['pv_count'];
										if ( $view_pv_ary[$iii]['is_last'] ) {
											break;
										}
									}
								}
							}
							//離脱ページ
							if ( $is_last ) {
								$tmp_sap_ary['exit_count'] = 1;
							}
							//lpの時の処理。slはここで作る
							if ( $is_landingpage ) {
								$tmp_sdad_ary['session_count'] = 1;
								$tmp_sl_ary['session_count']   = 1;
								$tmp_sap_ary['lp_count']       = 1;
								if ( isset ( $sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA] ) ) {
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['pv_count']      += $tmp_sl_ary['pv_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_count'] += $tmp_sl_ary['session_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['user_count'][]   = $tmp_sl_ary['user_count'][0];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['bounce_count']  += $tmp_sl_ary['bounce_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_time']  += $tmp_sl_ary['session_time'];
								} else {
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]                   = $tmp_sl_ary;
								}

								//1mon
								if ( isset ( $sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA] ) ) {
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['pv_count']      += $tmp_sl_ary['pv_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_count'] += $tmp_sl_ary['session_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['user_count'][]   = $tmp_sl_ary['user_count'][0];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['bounce_count']  += $tmp_sl_ary['bounce_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_time']  += $tmp_sl_ary['session_time'];
								} else {
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]                   = $tmp_sl_ary;
								}


							}

							
							
							
							//sdadとsapを作る
							if ( isset ( $sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sdad_ary['pv_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['session_count'] += $tmp_sdad_ary['session_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sdad_ary['user_count'][0];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sdad_ary['bounce_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sdad_ary['time_on_page'];
							} else {
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sdad_ary;
							}
							if ( isset ( $sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sap_ary['pv_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sap_ary['user_count'][0];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sap_ary['bounce_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['exit_count']    += $tmp_sap_ary['exit_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sap_ary['time_on_page'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['lp_count']      += $tmp_sap_ary['lp_count'];
							} else {
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sap_ary;
							}
							//1mon
							if ( isset ( $sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sap_ary['pv_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sap_ary['user_count'][0];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sap_ary['bounce_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['exit_count']    += $tmp_sap_ary['exit_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sap_ary['time_on_page'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['lp_count']      += $tmp_sap_ary['lp_count'];
							} else {
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sap_ary;
							}



							// seを作る
							if ( $is_raw_c && $raw_c_file_ary ) {
								$raw_c_ary = null;
								for ( $raw_c_file_max = count( $raw_c_file_ary ); $raw_c_file_idx < $raw_c_file_max; $raw_c_file_idx++ ) {
									if( $pv_id === (int) $raw_c_file_ary[$raw_c_file_idx]['pv_id'] ) {
										$raw_c_ary = $this->convert_tsv_to_array( $raw_c_file_ary[$raw_c_file_idx]['raw_c'] );
										break;
									}
								}

								if ( $raw_c_ary ) {
									// raw_cの配列をイベントサマリー用に最適化
									// raw_cのデータバージョンによる処理は今は無し
									$event_ary = [];
									for ( $raw_c_idx = QAHM_File_Data::DATA_COLUMN_BODY, $raw_c_max = count( $raw_c_ary ); $raw_c_idx < $raw_c_max; $raw_c_idx++ ) {
										$sel_num = (int) $raw_c_ary[$raw_c_idx][QAHM_File_Data::DATA_CLICK_1['SELECTOR_NAME']];
										$url     = null;
										if ( array_key_exists( QAHM_File_Data::DATA_CLICK_1['TRANSITION'], $raw_c_ary[$raw_c_idx] ) ) {
											$url = $raw_c_ary[$raw_c_idx][QAHM_File_Data::DATA_CLICK_1['TRANSITION']];
										}
										$is_add_event = false;
										$event_max = count( $event_ary );
										if ( $event_max > 0 ) {
											for ( $event_idx = 0; $event_idx < $event_max; $event_idx++ ) {
												if ( $event_ary[$event_idx]['selector'] === $sel_num ) {
													$is_add_event = true;
													break;
												}
											}
										}
										if ( ! $is_add_event ) {
											$event_ary[] = [ 'cv_type' => 'c', 'selector' =>$sel_num , 'pv_id' => [ $pv_id ], 'url' => $url ];
										}
									}
									
									// 挿入するイベント配列のインデックスを求める
									$se_idx = -1;
									$se_max = count( $se_ary );
									if ( $se_max > 0 ) {
										for ( $tmp_se_idx = 0; $tmp_se_idx < $se_max; $tmp_se_idx++ ) {
											if ( $se_ary[$tmp_se_idx]['version_id'] === $version_id ) {
												$se_idx = $tmp_se_idx;
												break;
											}
										}
									}

									if ( $se_idx === -1 ) {
										$se_ary[] = [ 'page_id' => $page_id, 'version_id' => $version_id, 'event' => $event_ary ];
										if ( ! isset( $base_sel_ary[$version_id] ) ) {
											if ( $this->wrap_exists( $verhist_dir . $version_id . '_version.php' ) ) {
												$verhist_ary = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_dir . $version_id . '_version.php' ) );
												$base_sel_ary[$version_id] = $this->convert_tsv_to_array( $verhist_ary[0]->base_selector )[0];
											}
										}
										
									} else {
										for ( $event_idx = 0, $event_max = count( $event_ary ); $event_idx < $event_max; $event_idx++ ) {
											$is_add_sel = false;
											for ( $se_event_idx = 0, $se_event_max = count( $se_ary[$se_idx]['event'] ); $se_event_idx < $se_event_max; $se_event_idx++ ) {
												// 同名のイベントタイプ＆セレクタが追加されている場合、pv_idのみ追加
												if ( $se_ary[$se_idx]['event'][$se_event_idx]['selector'] === $event_ary[$event_idx]['selector'] &&
													$se_ary[$se_idx]['event'][$se_event_idx]['cv_type'] === $event_ary[$event_idx]['cv_type'] ) {
													$se_ary[$se_idx]['event'][$se_event_idx]['pv_id'][] = $event_ary[$event_idx]['pv_id'][0];
													$is_add_sel = true;
													break;
												}
											}

											if ( ! $is_add_sel ) {
												$se_ary[$se_idx]['event'][] = $event_ary[$event_idx];
											}
										}
									}
								}
							}
						}

						// イベントサマリーデータのセレクタIDをセレクタ名に変換
						for ( $se_idx = 0, $se_max = count( $se_ary ); $se_idx < $se_max; $se_idx++ ) {
							for ( $event_idx = 0, $event_max = count( $se_ary[$se_idx]['event'] ); $event_idx < $event_max; $event_idx++ ) {
								$se_ary[$se_idx]['event'][$event_idx]['selector'] = $base_sel_ary[$se_ary[$se_idx]['version_id']][ $se_ary[$se_idx]['event'][$event_idx]['selector'] ];
							}
						}

						//容量を減らすためユーザーカウントを置き換えていく必要がある。
						foreach ( $sdad_ary as $idxd => $aryd) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idxq => $aryq ) {
												if ( isset( $aryq['user_count'] ) ) {
													$unique  = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
													$uniqusr = array_values( $unique );
													$user_cnt = count( $uniqusr );
													$sdad_ary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['user_count'] = $user_cnt;
												}
											}
										}
									}
								}
							}
						}
						foreach ( $sl_ary as $idxp => $aryp) {
							foreach ( $aryp as $idxd => $aryd ) {
								foreach ( $aryd as $idxs => $arys ) {
									foreach ( $arys as $idxm => $arym ) {
										foreach ( $arym as $idxc => $aryc ) {
											foreach ( $aryc as $idxn => $aryn ) {
												foreach ( $aryn as $idx2 => $ary2 ) {
													foreach ( $ary2 as $idxq => $aryq ) {
														if ( isset( $aryq[ 'user_count' ] ) ) {
															$unique = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
															$uniqusr = array_values( $unique );
															$user_cnt = count( $uniqusr );
															$sl_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['user_count'] = $user_cnt;
														}
													}
												}
											}
										}
									}
								}
							}
						}
						foreach ( $sap_ary as $idxp => $aryp) {
							foreach ( $aryp as $idxd => $aryd ) {
								foreach ( $aryd as $idxs => $arys ) {
									foreach ( $arys as $idxm => $arym ) {
										foreach ( $arym as $idxc => $aryc ) {
											foreach ( $aryc as $idxn => $aryn ) {
												foreach ( $aryn as $idxq => $aryq ) {
													if ( isset( $aryq[ 'user_count' ] ) ) {
														$unique  = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
														$uniqusr = array_values( $unique );
														$user_cnt = count( $uniqusr );
														$sap_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ][ 'user_count' ] = $user_cnt;
													}
												}
											}
										}
									}
								}
							}
						}


						// 今回のファイルは既存aryの中にないので追加する
						if ($start_idx < 0 ) {
							$days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
						// 今回の再計算した対象ファイルは既存aryの中に入る予定なので、どこに追加するかをチェック
						} else {
							$is_find = false;
							$afterary = [];
							//既存aryの中で一致する日付を検索していれる
							for ($ddd = $start_idx; $ddd < count($days_access_detail_ary); $ddd++ ) {
								if ( isset( $days_access_detail_ary[$ddd]['date'])) {
									$ary_datetime = $days_access_detail_ary[$ddd]['date'] . ' 00:00:00';
									if ( $qahm_time->str_to_unixtime( $ary_datetime ) <= $qahm_time->str_to_unixtime( $f_datetime )) {
										$start_idx++;
									} else {
										$afterary[] = $days_access_detail_ary[$ddd];
									}
									if ($days_access_detail_ary[$ddd]['date'] === $f_date) {
										$days_access_detail_ary[$ddd] = array( 'date' => $f_date, 'data' => $sdad_ary );
										$is_find = true;
										break;
									}
								}
							}
							//まったく見つからなかった場合は、aryのおしりか間に追加
							if ( ! $is_find) {
								//そもそも日付がオーバーした時は、おしりに追加
								if ( count($days_access_detail_ary) <= $start_idx ) {
									$days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
									//以後の日付はお尻に追加
									$start_idx = -1;
								//日付がオーバーしていない場合は、間に追加
								} else {
									$new_days_access_detail_ary = [];
									for ( $ccc = 0; $ccc < $start_idx; $ccc++ ) {
										$new_days_access_detail_ary[] = $days_access_detail_ary[$ccc];
									}
									//start_idxのところに挿入
									$new_days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
									//お尻はいままで通り
									for ( $ccc = 0; $ccc < count($afterary); $ccc++ ) {
										$new_days_access_detail_ary[] = $afterary[$ccc];
									}
									$days_access_detail_ary = $new_days_access_detail_ary;
									// 次の$fileの日付検索は次のstart_idxから
									$start_idx++;
									if ( count($days_access_detail_ary) <= $start_idx ) {
										//以後の日付はお尻に追加
										$start_idx = -1;
									}
								}
							}
						}
						//write today access
						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_allpage.php', $this->wrap_serialize( $sap_ary ) );
						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_landingpage.php', $this->wrap_serialize( $sl_ary ) );
						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_event.php', $this->wrap_serialize( $se_ary ) );
						$this->wrap_put_contents( $summary_days_access_detail_file, $this->wrap_serialize( $days_access_detail_ary ) );

						// startするdatetimeは次の日付になる。
						$s_datetime  = $qahm_time->xday_str( 1, $f_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );

					} elseif( ! $this->wrap_exists( $vw_summary_dir . $f_date . '_summary_event.php' ) ) {
						// summary_eventファイルが存在しない場合は作成する
						$view_pv_ary    = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
						$raw_c_file_ary = null;
						$raw_c_file_idx = 0;
						$raw_c_filename = str_replace( 'viewpv', 'rawc', $filename );
						if ( $this->wrap_exists( $raw_c_dir . $raw_c_filename ) ) {
							$raw_c_file_ary = $this->wrap_unserialize( $this->wrap_get_contents( $raw_c_dir . $raw_c_filename ) );
						}

						$se_ary   = [];
						$total_pv = count( $view_pv_ary );
						if ( $raw_c_file_ary ) {
							foreach ( $view_pv_ary as $idx => $pv_ary ) {
								//Dimensions
								//(int)=nullや空文字は0になる
								$pv_id       = (int)$pv_ary['pv_id'];
								$page_id     = (int)$pv_ary['page_id'];
								$version_id  = (int)$pv_ary['version_id'];
								$is_raw_c    = (int)$pv_ary['is_raw_c'];

								// seを作る
								if ( $is_raw_c ) {
									$raw_c_ary = null;
									for ( $raw_c_file_max = count( $raw_c_file_ary ); $raw_c_file_idx < $raw_c_file_max; $raw_c_file_idx++ ) {
										if( $pv_id === (int) $raw_c_file_ary[$raw_c_file_idx]['pv_id'] ) {
											$raw_c_ary = $this->convert_tsv_to_array( $raw_c_file_ary[$raw_c_file_idx]['raw_c'] );
											break;
										}
									}

									if ( $raw_c_ary ) {
										// raw_cの配列をイベントサマリー用に最適化
										// raw_cのデータバージョンによる処理は今は無し
										$event_ary = [];
										for ( $raw_c_idx = QAHM_File_Data::DATA_COLUMN_BODY, $raw_c_max = count( $raw_c_ary ); $raw_c_idx < $raw_c_max; $raw_c_idx++ ) {
											$sel_num = (int) $raw_c_ary[$raw_c_idx][QAHM_File_Data::DATA_CLICK_1['SELECTOR_NAME']];
											$url     = null;
											if ( array_key_exists( QAHM_File_Data::DATA_CLICK_1['TRANSITION'], $raw_c_ary[$raw_c_idx] ) ) {
												$url = $raw_c_ary[$raw_c_idx][QAHM_File_Data::DATA_CLICK_1['TRANSITION']];
											}
											$is_add_event = false;
											$event_max = count( $event_ary );
											if ( $event_max > 0 ) {
												for ( $event_idx = 0; $event_idx < $event_max; $event_idx++ ) {
													if ( $event_ary[$event_idx]['selector'] === $sel_num ) {
														$is_add_event = true;
														break;
													}
												}
											}
											if ( ! $is_add_event ) {
												$event_ary[] = [ 'cv_type' => 'c', 'selector' =>$sel_num , 'pv_id' => [ $pv_id ], 'url' => $url ];
											}
										}
										
										// 挿入するイベント配列のインデックスを求める
										$se_idx = -1;
										$se_max = count( $se_ary );
										if ( $se_max > 0 ) {
											for ( $tmp_se_idx = 0; $tmp_se_idx < $se_max; $tmp_se_idx++ ) {
												if ( $se_ary[$tmp_se_idx]['version_id'] === $version_id ) {
													$se_idx = $tmp_se_idx;
													break;
												}
											}
										}

										if ( $se_idx === -1 ) {
											$se_ary[] = [ 'page_id' => $page_id, 'version_id' => $version_id, 'event' => $event_ary ];
											if ( ! isset( $base_sel_ary[$version_id] ) ) {
												if ( $this->wrap_exists( $verhist_dir . $version_id . '_version.php' ) ) {
													$verhist_ary = $this->wrap_unserialize( $this->wrap_get_contents( $verhist_dir . $version_id . '_version.php' ) );
													$base_sel_ary[$version_id] = $this->convert_tsv_to_array( $verhist_ary[0]->base_selector )[0];
												}
											}
											
										} else {
											for ( $event_idx = 0, $event_max = count( $event_ary ); $event_idx < $event_max; $event_idx++ ) {
												$is_add_sel = false;
												for ( $se_event_idx = 0, $se_event_max = count( $se_ary[$se_idx]['event'] ); $se_event_idx < $se_event_max; $se_event_idx++ ) {
													// 同名のイベントタイプ＆セレクタが追加されている場合、pv_idのみ追加
													if ( $se_ary[$se_idx]['event'][$se_event_idx]['selector'] === $event_ary[$event_idx]['selector'] &&
														$se_ary[$se_idx]['event'][$se_event_idx]['cv_type'] === $event_ary[$event_idx]['cv_type'] ) {
														$se_ary[$se_idx]['event'][$se_event_idx]['pv_id'][] = $event_ary[$event_idx]['pv_id'][0];
														$is_add_sel = true;
														break;
													}
												}

												if ( ! $is_add_sel ) {
													$se_ary[$se_idx]['event'][] = $event_ary[$event_idx];
												}
											}
										}
									}
								}
							}

							// イベントサマリーデータのセレクタIDをセレクタ名に変換
							for ( $se_idx = 0, $se_max = count( $se_ary ); $se_idx < $se_max; $se_idx++ ) {
								for ( $event_idx = 0, $event_max = count( $se_ary[$se_idx]['event'] ); $event_idx < $event_max; $event_idx++ ) {
									$se_ary[$se_idx]['event'][$event_idx]['selector'] = $base_sel_ary[$se_ary[$se_idx]['version_id']][ $se_ary[$se_idx]['event'][$event_idx]['selector'] ];
								}
							}
						}

						// write
						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_event.php', $this->wrap_serialize( $se_ary ) );
					}
				}
			}
		}
	}



	//mkdummy debug
	public function debug_summary_days_access_detail () {
		global $wp_filesystem;
		global $qahm_time;
		// dir
		$data_dir          = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$viewpv_dir        = $myview_dir . 'view_pv/';
		$vw_summary_dir     = $myview_dir . 'summary/';
		$summary_days_access_detail_file = $vw_summary_dir . 'days_access_detail.php';
		$days_access_detail_ary = [];
		$s_datetime = '1999-12-31 00:00:00';
		if ( $wp_filesystem->exists($summary_days_access_detail_file ) ) {
			$days_access_detail_ary = $this->wrap_unserialize( $this->wrap_get_contents( $summary_days_access_detail_file ) );
			if ( 0 < count( $days_access_detail_ary ) ) {
				//　結局、毎月DBから上書きして値が増えるので、DBの期間分は上書きが必要
				// x month ago
				$yearx = $qahm_time->year();
				$month = $qahm_time->month();
				$data_save_month = QAHM_Cron_Proc::DATA_SAVE_MONTH;
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
		}
		echo esc_html( $s_datetime ) . 'sdate<br>';

		//search どの日付から変更するべきか
		$start_idx = 0;
		foreach ( $days_access_detail_ary as $idx => $days_access ) {
			if ( isset( $days_access['sum_datetime'] ) ) {
				if ( ($qahm_time->now_unixtime() - 3 * 60 * 60) < $qahm_time->str_to_unixtime($days_access['sum_datetime'])) {
					//本日集計済みなので、この日付は飛ばすべき
					$s_datetime = $days_access['date'] . ' 23:59:59';
					$start_idx = $idx + 1;
				}
			} else {
				//dummyの古い値を入れる
				$tmpary = array_merge( $days_access, array('sum_datetime' => '1999-12-31 00:00:00') );
				$days_access_detail_ary[$idx] = $tmpary;
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
		if ( count($days_access_detail_ary) <= $start_idx && $start_idx !== 0 ) {
			$start_idx = -1;
		}

		//page関連は一ヶ月毎にサマリーファイルを作る
		$sap_1mon_ary  = [];
		$sl_1mon_ary   = [];
		$make_1mon_cnt = 0;
		$sap_name_1mon = '';
		$lp_name_1mon  = '';
		// search view_pv dir
		$allfiles = $this->wrap_dirlist( $viewpv_dir );
		if ($allfiles) {
			foreach ( $allfiles as $file ) {
				$filename = $file[ 'name' ];
				if ( is_file( $viewpv_dir . $filename ) ) {
					$f_date = substr( $filename, 0, 10 );
					$f_datetime = $f_date . ' 00:00:00';
					if ( $qahm_time->str_to_unixtime( $s_datetime )  <= $qahm_time->str_to_unixtime( $f_datetime ) ) {
						if ( substr( $f_date, -2 ) === '01' ) {
							if ( 0 < $make_1mon_cnt ) {
								//exchange user_count
								foreach ( $sl_1mon_ary as $idxp => $aryp ) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idx2 => $ary2 ) {
															foreach ( $ary2 as $idxq => $aryq ) {
																if ( isset( $aryq[ 'user_count' ] ) ) {
																	$unique = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
																	$uniqusr = array_values( $unique );
																	$user_cnt = count( $uniqusr );
																	$sl_1mon_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idx2 ][ $idxq ][ 'user_count' ] = $user_cnt;
																}
															}
														}
													}
												}
											}
										}
									}
								}
								foreach ( $sap_1mon_ary as $idxp => $aryp ) {
									foreach ( $aryp as $idxd => $aryd ) {
										foreach ( $aryd as $idxs => $arys ) {
											foreach ( $arys as $idxm => $arym ) {
												foreach ( $arym as $idxc => $aryc ) {
													foreach ( $aryc as $idxn => $aryn ) {
														foreach ( $aryn as $idxq => $aryq ) {
															if ( isset( $aryq[ 'user_count' ] ) ) {
																$unique = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
																$uniqusr = array_values( $unique );
																$user_cnt = count( $uniqusr );
																$sap_1mon_ary[ $idxp ][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ][ 'user_count' ] = $user_cnt;
															}
														}
													}
												}
											}
										}
									}
								}
								echo esc_html( $lp_name_1mon ) . '<br>';
								echo esc_html( $sap_name_1mon ) . '<br>';

								//write 1mon access
								//							$this->wrap_put_contents( $vw_summary_dir . $lp_name_1mon, $this->wrap_serialize( $sl_1mon_ary ) );
								//							$this->wrap_put_contents( $vw_summary_dir . $sap_name_1mon, $this->wrap_serialize( $sap_1mon_ary ) );
							}
							$make_1mon_cnt++;
							$lp_name_1mon = $f_date . '_summary_landingpage_1mon.php';
							$sap_name_1mon = $f_date . '_summary_allpage_1mon.php';

							$sl_1mon_ary = [];
							$sap_1mon_ary = [];
						}
						//集計対象
						$view_pv_ary = $this->wrap_unserialize( $this->wrap_get_contents( $viewpv_dir . $filename ) );
						//作るファイルは3つ。summary_days_access_detail(spad)、YYYY-MM-DD_summary_allpage(sap)、YYYY-MM-DD_summary_landingpage(sl)
						$sdad_ary = [];
						$sap_ary  = [];
						$sl_ary   = [];
						$total_pv = count( $view_pv_ary );
						foreach ( $view_pv_ary as $idx => $pv_ary ) {
							//Dimensions
							//(int)=nullや空文字は0になる
							$reader_id   = (int)$pv_ary['reader_id'];
							$page_id     = (int)$pv_ary['page_id'];
							$device_id   = (int)$pv_ary['device_id'];
							$source_id   = (int)$pv_ary['source_id'];
							$medium_id   = (int)$pv_ary['medium_id'];
							$campaign_id = (int)$pv_ary['campaign_id'];
							$is_newuser  = (int)$pv_ary['is_newuser'];
							$is_QA       = 1;
							//Metrics
							$browse_sec  = (int)$pv_ary['browse_sec'];
							//条件判定
							$is_last     = (int)$pv_ary['is_last'];

							//----make tmp_array
							$tmp_sdad_ary = ['pv_count' => 1, 'session_count' => 0, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'time_on_page' => $browse_sec];
							$tmp_sap_ary  = ['pv_count' => 1, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'exit_count'=> 0, 'time_on_page' => $browse_sec, 'lp_count' => 0];
							$tmp_sl_ary   = ['pv_count' => 1, 'session_count' => 0, 'user_count' => [$reader_id], 'bounce_count'=> 0, 'session_time' => $browse_sec];


							$is_landingpage = 0;
							$second_page    = 0;
							//count session 当日の1ページ目（LP着地）をカウント
							if ( (int)$pv_ary['pv'] === 1 ) {
								$is_landingpage = 1;
							}
							//tmp_array start
							//直帰ページ
							if ( $is_landingpage && $is_last ) {
								$tmp_sdad_ary['bounce_count'] = 1;
								$tmp_sap_ary['bounce_count'] = 1;
								$tmp_sl_ary['bounce_count']   = 1;
							//2ページ以上見たセッション
							} elseif ( $is_landingpage ) {
								if ( $idx < $total_pv -1 ) {
									$second_page = $view_pv_ary[$idx+1]['page_id'];
									//calc session_time
									for ( $iii = $idx + 1; $iii < $total_pv; $iii++ ) {
										$tmp_sl_ary['session_time'] += $view_pv_ary[$iii]['browse_sec'];
										++$tmp_sl_ary['pv_count'];
										if ( $view_pv_ary[$iii]['is_last'] ) {
											break;
										}
									}
								}
							}
							//離脱ページ
							if ( $is_last ) {
								$tmp_sap_ary['exit_count'] = 1;
							}
							//lpの時の処理。slはここで作る
							if ( $is_landingpage ) {
								$tmp_sdad_ary['session_count'] = 1;
								$tmp_sl_ary['session_count']   = 1;
								$tmp_sap_ary['lp_count']       = 1;
								if ( isset ( $sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA] ) ) {
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['pv_count']      += $tmp_sl_ary['pv_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_count'] += $tmp_sl_ary['session_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['user_count'][]   = $tmp_sl_ary['user_count'][0];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['bounce_count']  += $tmp_sl_ary['bounce_count'];
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_time']  += $tmp_sl_ary['session_time'];
								} else {
									$sl_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]                   = $tmp_sl_ary;
								}

								//1mon
								if ( isset ( $sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA] ) ) {
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['pv_count']      += $tmp_sl_ary['pv_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_count'] += $tmp_sl_ary['session_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['user_count'][]   = $tmp_sl_ary['user_count'][0];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['bounce_count']  += $tmp_sl_ary['bounce_count'];
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]['session_time']  += $tmp_sl_ary['session_time'];
								} else {
									$sl_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$second_page][$is_QA]                   = $tmp_sl_ary;
								}
							}




							//sdadとsapを作る
							if ( isset ( $sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sdad_ary['pv_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['session_count'] += $tmp_sdad_ary['session_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sdad_ary['user_count'][0];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sdad_ary['bounce_count'];
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sdad_ary['time_on_page'];
							} else {
								$sdad_ary[$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sdad_ary;
							}
							if ( isset ( $sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sap_ary['pv_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sap_ary['user_count'][0];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sap_ary['bounce_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['exit_count']    += $tmp_sap_ary['exit_count'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sap_ary['time_on_page'];
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['lp_count']      += $tmp_sap_ary['lp_count'];
							} else {
								$sap_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sap_ary;
							}
							//1mon
							if ( isset ( $sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA] ) ) {
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['pv_count']      += $tmp_sap_ary['pv_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['user_count'][]   = $tmp_sap_ary['user_count'][0];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['bounce_count']  += $tmp_sap_ary['bounce_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['exit_count']    += $tmp_sap_ary['exit_count'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['time_on_page']  += $tmp_sap_ary['time_on_page'];
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]['lp_count']      += $tmp_sap_ary['lp_count'];
							} else {
								$sap_1mon_ary[$page_id][$device_id][$source_id][$medium_id][$campaign_id][$is_newuser][$is_QA]                   = $tmp_sap_ary;
							}
						}
						//容量を減らすためユーザーカウントを置き換えていく必要がある。
						foreach ( $sdad_ary as $idxd => $aryd) {
							foreach ( $aryd as $idxs => $arys ) {
								foreach ( $arys as $idxm => $arym ) {
									foreach ( $arym as $idxc => $aryc ) {
										foreach ( $aryc as $idxn => $aryn ) {
											foreach ( $aryn as $idxq => $aryq ) {
												if ( isset( $aryq['user_count'] ) ) {
													$unique  = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
													$uniqusr = array_values( $unique );
													$user_cnt = count( $uniqusr );
													$sdad_ary[$idxd][$idxs][$idxm][$idxc][$idxn][$idxq]['user_count'] = $user_cnt;
												}
											}
										}
									}
								}
							}
						}
						foreach ( $sl_ary as $idxp => $aryp) {
							foreach ( $aryp as $idxd => $aryd ) {
								foreach ( $aryd as $idxs => $arys ) {
									foreach ( $arys as $idxm => $arym ) {
										foreach ( $arym as $idxc => $aryc ) {
											foreach ( $aryc as $idxn => $aryn ) {
												foreach ( $aryn as $idx2 => $ary2 ) {
													foreach ( $ary2 as $idxq => $aryq ) {
														if ( isset( $aryq[ 'user_count' ] ) ) {
															$unique = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
															$uniqusr = array_values( $unique );
															$user_cnt = count( $uniqusr );
															$sl_ary[$idxp][$idxd][$idxs][$idxm][$idxc][$idxn][$idx2][$idxq]['user_count'] = $user_cnt;
														}
													}
												}
											}
										}
									}
								}
							}
						}
						foreach ( $sap_ary as $idxp => $aryp) {
							foreach ( $aryp as $idxd => $aryd ) {
								foreach ( $aryd as $idxs => $arys ) {
									foreach ( $arys as $idxm => $arym ) {
										foreach ( $arym as $idxc => $aryc ) {
											foreach ( $aryc as $idxn => $aryn ) {
												foreach ( $aryn as $idxq => $aryq ) {
													if ( isset( $aryq[ 'user_count' ] ) ) {
														$unique  = array_unique( $aryq[ 'user_count' ], SORT_NUMERIC );
														$uniqusr = array_values( $unique );
														$user_cnt = count( $uniqusr );
														$sap_ary[$idxp][ $idxd ][ $idxs ][ $idxm ][ $idxc ][ $idxn ][ $idxq ][ 'user_count' ] = $user_cnt;
													}
												}
											}
										}
									}
								}
							}
						}


						// 今回のファイルは既存aryの中にないので追加する
						if ($start_idx < 0 ) {
							$days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
						// 今回の再計算した対象ファイルは既存aryの中に入る予定なので、どこに追加するかをチェック
						} else {
							$is_find = false;
							$afterary = [];
							//既存aryの中で一致する日付を検索していれる
							for ($ddd = $start_idx; $ddd < count($days_access_detail_ary); $ddd++ ) {
								if ( isset( $days_access_detail_ary[$ddd]['date'])) {
									$ary_datetime = $days_access_detail_ary[$ddd]['date'] . ' 00:00:00';
									if ( $qahm_time->str_to_unixtime( $ary_datetime ) <= $qahm_time->str_to_unixtime( $f_datetime )) {
										$start_idx++;
									} else {
										$afterary[] = $days_access_detail_ary[$ddd];
									}
									if ($days_access_detail_ary[$ddd]['date'] === $f_date) {
										$days_access_detail_ary[$ddd] = array( 'date' => $f_date, 'data' => $sdad_ary );
										$is_find = true;
										break;
									}
								}
							}
							//まったく見つからなかった場合は、aryのおしりか間に追加
							if ( ! $is_find) {
								//そもそも日付がオーバーした時は、おしりに追加
								if ( count($days_access_detail_ary) <= $start_idx ) {
									$days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
									//以後の日付はお尻に追加
									$start_idx = -1;
								//日付がオーバーしていない場合は、間に追加
								} else {
									$new_days_access_detail_ary = [];
									for ( $ccc = 0; $ccc < $start_idx; $ccc++ ) {
										$new_days_access_detail_ary[] = $days_access_detail_ary[$ccc];
									}
									//start_idxのところに挿入
									$new_days_access_detail_ary[] = array( 'date' => $f_date, 'data' => $sdad_ary );
									//お尻はいままで通り
									for ( $ccc = 0; $ccc < count($afterary); $ccc++ ) {
										$new_days_access_detail_ary[] = $afterary[$ccc];
									}
									$days_access_detail_ary = $new_days_access_detail_ary;
									// 次の$fileの日付検索は次のstart_idxから
									$start_idx++;
									if ( count($days_access_detail_ary) <= $start_idx ) {
										//以後の日付はお尻に追加
										$start_idx = -1;
									}
								}
							}
						}
						//write today access
//						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_allpage.php', $this->wrap_serialize( $sap_ary ) );
//						$this->wrap_put_contents( $vw_summary_dir . $f_date . '_summary_landingpage.php', $this->wrap_serialize( $sl_ary ) );
//						$this->wrap_put_contents( $summary_days_access_detail_file, $this->wrap_serialize( $days_access_detail_ary ) );

						// startするdatetimeは次の日付になる。
						$s_datetime  = $qahm_time->xday_str( 1, $f_datetime, QAHM_Time::DEFAULT_DATETIME_FORMAT );
					}
				}
			}
		}

	}

}
