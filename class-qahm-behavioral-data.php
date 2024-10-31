<?php
/**
 * rawデータ関連のajax通信処理をまとめたクラス
 *
 * @package qa_heatmap
 */

new QAHM_Behavioral_Data();

class QAHM_Behavioral_Data extends QAHM_File_Data {

	const NONCE_INIT       = 'init';
	const NONCE_BEHAVIORAL = 'behavioral';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init_wp_filesystem' ) );
	}



	/**
	 * セッションデータの初期化
	 */
	public function init_session_data( $qa_id, $wp_qa_type, $wp_qa_id, $title, $url, $ref, $country, $ua, $is_new_user, $is_cookie_reject ) {

		global $qahm_time;
		
		$dev_name         = $this->user_agent_to_device_name( $ua );
		$utm_source       = '';
		$utm_medium       = '';
		$utm_campaign     = '';
		$utm_term         = '';
		$user_original_id = '';

		// utm_***の設定＆urlの一部パラメーターを削除して保存できるよう対応
		$parse_url = wp_parse_url( $url, PHP_URL_QUERY );
		if ( $parse_url ) {
			parse_str( $parse_url, $query_ary );
			
			if ( array_key_exists( 'utm_source', $query_ary ) ) {
				$utm_source = $query_ary[ 'utm_source' ];
			}
			if ( array_key_exists( 'utm_medium', $query_ary ) ) {
				$utm_medium = $query_ary[ 'utm_medium' ];
			}
			if ( array_key_exists( 'utm_campaign', $query_ary ) ) {
				$utm_campaign = $query_ary[ 'utm_campaign' ];
			}
			if ( array_key_exists( 'utm_term', $query_ary ) ) {
				$utm_term = $query_ary[ 'utm_term' ];
			}

			//QA ZERO add start
			if ( array_key_exists( 'gad', $query_ary ) ) {
				if ( ! $utm_source ) {
					$utm_source = 'google';
				}
				if ( ! $utm_medium ) {
					$utm_medium = 'cpc';
				}
			}
			//QA ZERO add end
							
			if ( array_key_exists( 'gclid', $query_ary ) ) {
				if ( ! $utm_source ) {
					$utm_source = 'google';
				}
				if ( ! $utm_medium ) {
					$utm_medium = 'cpc';
				}
			}

			if ( array_key_exists( 'fbclid', $query_ary ) ) {
				if ( ! $utm_medium ) {
					$utm_medium = 'social';
				}
				if ( ! $utm_source ) {
					$utm_source = 'facebook';
				}
			}
			
			if ( array_key_exists( 'twclid', $query_ary ) ) {
				if ( ! $utm_medium ) {
					$utm_medium = 'social';
				}
				if ( ! $utm_source ) {
					$utm_source = 'twitter';
				}
			}
			
			//QA ZERO ADD START 20230810
			if ( array_key_exists( 'yclid', $query_ary ) ) {
				if ( ! $utm_medium ) {
					$utm_medium = 'cpc';
				}
				if ( ! $utm_source ) {
					$utm_source = 'yahoo';
				}
			}
			
			if ( array_key_exists( 'ldtag_cl', $query_ary ) ) {
				if ( ! $utm_medium ) {
					$utm_medium = 'cpc';
				}
				if ( ! $utm_source ) {
					$utm_source = 'line';
				}
			}
			
			if ( array_key_exists( 'msclkid', $query_ary ) ) {
				if ( ! $utm_medium ) {
					$utm_medium = 'cpc';
				}
				if ( ! $utm_source ) {
					$utm_source = 'microsoft';
				}
			}
			//QA ZERO ADD END 20230810
		}

		$url                = $this->opt_url_param( $url );
		$readers_temp_dir   = $this->get_data_dir_path( 'readers/temp/' );
		$readers_finish_dir = $this->get_data_dir_path( 'readers/finish/' );

		// sessionデータ作成
		$today_str          = $qahm_time->today_str();
		$session_temp_ary   = null;

		// 保存対象のセッションファイルを調べる。まずはtempディレクトリ
		$file_info = $this->get_latest_readers_file_info( $readers_temp_dir, $qa_id );

		if ( $file_info ) {
			$before_30min = $qahm_time->now_unixtime() - ( 60 * 30 );
			if ( $file_info['lastmodunix'] < $before_30min ) {
				// 作られてから30分以上経過している場合はcronが止まっている可能性があるので、session_no+1で新規ファイル作成
				if ( $file_info['day_str'] === $today_str ) {
					$session_num  = $file_info['session_num'] + 1;
				} else {
					$session_num = 1;
				}
				$readers_name = $qa_id . '_' . $today_str . '_' . $session_num;

			} else {
				// 作られてから30分経過していない場合は前のファイルに追記書き込み
				$session_num  = $file_info['session_num'];
				$readers_name = $qa_id . '_' . $file_info['day_str'] . '_' . $session_num;
				$session_temp_ary = $this->wrap_unserialize( $this->wrap_get_contents( $readers_temp_dir . $readers_name . '.php' ) );
			}

		} else {
			// tempディレクトリにファイルがない場合はfinishディレクトリを確認
			$file_info = $this->get_latest_readers_file_info( $readers_finish_dir, $qa_id );

			if ( $file_info ) {
				if ( $file_info['day_str'] === $today_str ) {
					$session_num  = $file_info['session_num'] + 1;
				} else {
					$session_num = 1;
				}

			} else {
				$session_num  = 1;
			}

			$readers_name = $qa_id . '_' . $today_str . '_' . $session_num;
		}

		if ( $this->wrap_filter_input( INPUT_COOKIE, '_ga' ) ) {
			$is_new_user = 0;
		}

		// session temp data
		if ( ! $session_temp_ary ) {
			$session_temp_ary = array();
			$session_temp_ary['head']['version']        = 1;
			$session_temp_ary['head']['tracking_id']    = $this->get_tracking_id();
			$session_temp_ary['head']['device_name']    = $dev_name;
			$session_temp_ary['head']['is_new_user']    = $is_new_user;
			$session_temp_ary['head']['user_agent']     = $ua;
			$session_temp_ary['head']['first_referrer'] = $ref;
			$session_temp_ary['head']['utm_source']     = $utm_source;
			$session_temp_ary['head']['utm_medium']     = $utm_medium;
			$session_temp_ary['head']['utm_campaign']   = $utm_campaign;
			$session_temp_ary['head']['utm_term']       = $utm_term;
			$session_temp_ary['head']['original_id']    = $user_original_id;
			$session_temp_ary['head']['country']        = $country;
			$session_temp_ary['head']['is_reject']      = $is_cookie_reject;
		}

		$body = array(
			'page_url'    => $url,
			'page_title'  => $title,
			'page_type'   => $wp_qa_type,
			'page_id'     => $wp_qa_id,
			'access_time' => $qahm_time->now_unixtime(),
			'page_speed'  => 0,
		);

		if ( ! isset( $session_temp_ary['body'] ) ) {
			$session_temp_ary['body'] = array();
		}

		$data['readers_name']       = $readers_name;
		$data['readers_body_index'] = array_push( $session_temp_ary['body'], $body ) - 1;
		$this->wrap_put_contents( $readers_temp_dir . $readers_name . '.php', $this->wrap_serialize( $session_temp_ary ) );

		// qahm測定対象外のページの場合は生データを作らない
		if ( $this->is_qahm_page( $wp_qa_type ) ) {

			// 読者のPV数を取得。検索上限PV数は10000（仮）
			$raw_dir = $this->get_raw_dir_path( $wp_qa_type, $wp_qa_id, $dev_name );
			$limit  = 10000;
			$pv_num = 1;
			for( $i = 1; $i < $limit; $i++ ) {
				if ( ! $this->wrap_exists( $raw_dir . $readers_name . '_' . $i . '-p.php' ) ) {
					$pv_num  = $i;
					break;
				}
			}

			$data['raw_name'] = $readers_name . '_' . $pv_num;
		}

		$data['qa_id']    = $qa_id;

		return $data;
	}


	/**
	 * msecを更新
	 */
	public function update_msec( $readers_name, $readers_body_index, $speed_msec ) {
		$readers_temp_dir = $this->get_data_dir_path( 'readers/temp/' );
		$readers_data_ary = $this->wrap_unserialize( $this->wrap_get_contents( $readers_temp_dir . $readers_name . '.php' ) );

		if ( isset( $readers_data_ary['body'][$readers_body_index]['page_speed'] ) ) {
			$readers_data_ary['body'][$readers_body_index]['page_speed'] = $speed_msec;
			$this->wrap_put_contents( $readers_temp_dir . $readers_name . '.php', $this->wrap_serialize( $readers_data_ary ) );
		}
	}


	/**
	 * URLパラメーターをsession tempに格納するurl用に最適化
	 * http_build_queryは強制的にエンコードされるので使わない
	 */
	private function opt_url_param( $url, $del_param_ary=Array() ){
		$url_exp = explode( '?', $url );

		if( ! isset( $url_exp[1] ) ){
			return $url;
		}
		parse_str( $url_exp[1], $query_ary );

		$query_str = '';
		foreach( $query_ary as $key => $value ){
			if(
				$key !== 'gclid' && 
				$key !== '_ga' && 
				$key !== 'uid' &&
				strpos( $key, 'utm_' ) !== 0 &&
				$key !== 'fbclid' &&
				$key !== 'twclid' &&
				$key !== 'gad' &&
				$key !== 'yclid' &&
				$key !== 'ldtag_cl' &&
				$key !== 'msclkid'
			){
				if ( $query_str ) {
					$query_str .= '&';
				}
				$query_str .= $key . '=' . $value;
			}
		}

		return $query_str ? $url_exp[0] . '?' . $query_str : $url_exp[0];
	}


	// 指定したQA IDを持つひとにとって、最新のreadersファイルを取得
	private function get_latest_readers_file_info( $tar_dir, $qa_id ) {
		global $qahm_time;

		// 一番新しいreadersファイルの情報を格納する配列
		$file_info = array();

		if( QAHM_USE_LSCMD_LISTFILE ){
			$file_list = $this->listfiles_ls( $tar_dir, $qa_id."_*.php" );
		}else{
			$file_list = $this->wrap_dirlist( $tar_dir );
		}
		
		if ( $file_list ) {
			foreach ( $file_list as $file ) {
				if ( strpos( $file['name'], $qa_id . '_' ) === false ) {
					continue;
				}

				if ( ! preg_match( '/' . $qa_id . '_(.*)_(.*)\.php/', $file['name'], $match ) ) {
					continue;
				}
	
				$day_str     = $match[1];
				$session_num = (int) $match[2];

				if ( ! empty( $file_info ) ) {
					$xday_num = $qahm_time->xday_num( $file_info['day_str'], $day_str ); 
					
					if ( $xday_num === 0 ) {
						if ( $file_info['session_num'] >= $session_num ) {
							continue;
						}
					}
					
				}

				$file_info['name']        = $file['name'];
				$file_info['lastmodunix'] = $file['lastmodunix'];
				$file_info['session_num'] = $session_num;
				$file_info['day_str']     = $day_str;
			}
		}

		return empty( $file_info ) ? null : $file_info;
	}


	/**
	 * 行動データ作成
	 */
	public function record_behavioral_data($is_pos, $is_click, $is_event, $raw_name, $readers_name, $type, $id, $ua, $is_cookie_reject) {
		try {
			global $qahm_time;

			$dev_name       = $this->user_agent_to_device_name( $ua );
			$raw_dir        = $this->get_raw_dir_path( $type, $id, $dev_name );
			$readers_temp_path = $this->get_data_dir_path( 'readers/temp/' ) . $readers_name . '.php';

			if ( $this->wrap_exists( $readers_temp_path ) ) {
				$lastmodunix = filemtime( $readers_temp_path );
				$readers_data = $this->wrap_get_contents( $readers_temp_path );

				$readers_data_ary = $this->wrap_unserialize( $readers_data );
				$is_reject_temp = $readers_data_ary['head']['is_reject'];
				$readers_data_ary['head']['is_reject'] = $is_cookie_reject;

			 	if ( $qahm_time->now_unixtime() - $lastmodunix > 30 || $is_reject_temp != $is_cookie_reject ) {
			 		$this->wrap_put_contents( $readers_temp_path, $this->wrap_serialize( $readers_data_ary ) );
			 	}
			}

			// validate
			if ( ! $raw_dir ) {
				throw new Exception( 'Failed to specify the directory for raw data.' );
			}
			if ( ! $this->validate_qa_type( $type ) ) {
				throw new Exception( 'The value of $type is invalid.' );
			}
			if ( ! $this->validate_number( $id ) ) {
				throw new Exception( 'The value of $id is invalid.' );
			}

			$output = 'output data /';
			if ( $is_pos ) {
				$pos_ver       = $this->wrap_filter_input( INPUT_POST, 'pos_ver' );
				$is_scroll_max = $this->wrap_filter_input( INPUT_POST, 'is_scroll_max' );
				if ( ! $pos_ver ) {
					$pos_ver = 1;
				} else {
					$pos_ver = (int) $pos_ver;
				}
				$pos_ary  = array(
					array(
						self::DATA_HEADER_VERSION => $pos_ver,
					)
				);
				$pos_path = $raw_dir . $raw_name . '-p' . '.php';

				// validate & optimize
				if ( $pos_ver === 2 ) {
					$stay_height_ary = json_decode( $this->wrap_filter_input( INPUT_POST, 'stay_height' ), true );

					foreach ( $stay_height_ary as $stay_height => $stay_time ) {
						if ( ! $stay_time ) {
							continue;
						}

						if ( ! $this->validate_number( $stay_time ) ) {
							throw new Exception( 'The value of $stay_height is invalid.' );
						}

						array_push(
							$pos_ary,
							array(
								self::DATA_POS_2['STAY_HEIGHT'] => $stay_height,
								self::DATA_POS_2['STAY_TIME']   => $stay_time,
							)
						);
					}

					if ( $is_scroll_max === 'true' ) {
						array_push(
							$pos_ary,
							array(
								self::DATA_POS_2['STAY_HEIGHT'] => 'a',
							)
						);
					}
				} elseif ( $pos_ver === 1 ) {
					$percent_height = json_decode( $this->wrap_filter_input( INPUT_POST, 'percent_height' ), true );
					for ( $i = 0; $i < 100; $i++ ) {
						if ( ! $this->validate_number( $percent_height[$i] ) ) {
							throw new Exception( 'The value of $percent_height[$i] is invalid.' );
						}

						if ( $percent_height[ $i ] > 0 ) {
							array_push(
								$pos_ary,
								array(
									self::DATA_POS_1['PERCENT_HEIGHT'] => $i,
									self::DATA_POS_1['TIME_ON_HEIGHT'] => $percent_height[ $i ],
								)
							);
						}
					}

					if ( $is_scroll_max === 'true' ) {
						array_push(
							$pos_ary,
							array(
								self::DATA_POS_1['PERCENT_HEIGHT'] => 'a',
							)
						);
					}
				}

				$pos_tsv = $this->convert_array_to_tsv( $pos_ary );
				$this->wrap_put_contents( $pos_path, $pos_tsv );
				$output .= ' p /';
			}


			if ( $is_click ) {
				$click_ary = json_decode( $this->wrap_filter_input( INPUT_POST, 'click_ary' ), true );
				$click_ver = $this->wrap_filter_input( INPUT_POST, 'click_ver' );
				if ( ! $click_ver ) {
					$click_ver = 1;
				} else {
					$click_ver = (int) $click_ver;
				}
				$click_path = $raw_dir . $raw_name . '-c' . '.php';

				// validate & optimize
				$validated_click_ary = [];

				for ( $i = 0, $click_ary_cnt = count( $click_ary ); $i < $click_ary_cnt; $i++ ) {
					if ( ! $this->validate_number( $click_ary[$i][self::DATA_CLICK_1['SELECTOR_X']] ) ) {
						continue;
						//throw new Exception( 'The value of $click_ary is invalid.' );
					}

					if ( ! $this->validate_number( $click_ary[$i][self::DATA_CLICK_1['SELECTOR_Y']] ) ) {
						continue;
						//throw new Exception( 'The value of $click_ary is invalid.' );
					}

					if( array_key_exists( self::DATA_CLICK_1['TRANSITION'], $click_ary[$i] ) ) {
						$click_ary[$i][self::DATA_CLICK_1['TRANSITION']] = mb_strtolower( $click_ary[$i][self::DATA_CLICK_1['TRANSITION']] );
					}

					array_push( $validated_click_ary, $click_ary[$i] );
				}

				$click_head = array(
					array(
						self::DATA_HEADER_VERSION => $click_ver,
					)
				);
				$validated_click_ary = array_merge( $click_head, $validated_click_ary );
				$click_tsv = $this->convert_array_to_tsv( $validated_click_ary );
				$this->wrap_put_contents( $click_path, $click_tsv );
				$output .= ' c /';
			}


			if( $is_event ){
				$event_ary     = json_decode( $this->wrap_filter_input( INPUT_POST, 'event_ary' ), true );
				$init_window_w = $this->wrap_filter_input( INPUT_POST, 'init_window_w' );
				$init_window_h = $this->wrap_filter_input( INPUT_POST, 'init_window_h' );
				$event_ver     = $this->wrap_filter_input( INPUT_POST, 'event_ver' );
				if ( ! $event_ver ) {
					$event_ver = 1;
				} else {
					$event_ver = (int) $event_ver;
				}
				$event_path = $raw_dir . $raw_name . '-e' . '.php';

				// validate
				if ( ! $this->validate_number( $init_window_w ) ) {
					throw new Exception( 'The value of $init_window_w is invalid.' );
				}

				if ( ! $this->validate_number( $init_window_h ) ) {
					throw new Exception( 'The value of $init_window_h is invalid.' );
				}

				for ( $i = 0, $event_ary_cnt = count( $event_ary ); $i < $event_ary_cnt; $i++ ) {
					$event_type = $event_ary[$i][self::DATA_EVENT_1['TYPE']];
					if ( strlen( $event_type ) !== 1 ) {
						throw new Exception( 'The value of $event_ary is invalid.' );
					}

					if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['TIME']] ) ) {
						throw new Exception( 'The value of $event_ary is invalid.' );
					}

					switch ( $event_type ) {
						case 'c':
							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['CLICK_X']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}

							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['CLICK_Y']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}
							break;

						case 's':
							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['SCROLL_Y']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}
							break;

						case 'm':
							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['MOUSE_X']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}

							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['MOUSE_Y']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}
							break;

						case 'r':
							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['RESIZE_X']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}

							if ( ! $this->validate_number( $event_ary[$i][self::DATA_EVENT_1['RESIZE_Y']] ) ) {
								throw new Exception( 'The value of $event_ary is invalid.' );
							}
							break;

						// videoタグのセレクタはこの時点では文字列で格納
						case 'p':
							break;

						case 'a':
							break;

						default:
							throw new Exception( 'The value of $event_ary is invalid.' );
					}
				}

				$event_head = array(
					array(
						self::DATA_HEADER_VERSION            => $event_ver,
						self::DATA_EVENT_1['WINDOW_INNER_W'] => (int) $init_window_w,
						self::DATA_EVENT_1['WINDOW_INNER_H'] => (int) $init_window_h,
					)
				);
				$event_ary = array_merge( $event_head, $event_ary );
				$event_tsv = $this->convert_array_to_tsv( $event_ary );
				$this->wrap_put_contents( $event_path, $event_tsv );
				$output .= ' e /';
			}

			// ファイルの整合性を合わせるためにファイルの保存はここで一気にする
			return $output;

		} catch ( Exception $e ) {
			http_response_code( 500 );
			echo esc_html($e->getMessage());
		}
	}

	// 数値のチェック
	private function validate_qa_type( $qa_type ) {
		switch( $qa_type ) {
			case 'home':
			case 'page_id':
			case 'p':
			case 'cat':
			case 'tag':
			case 'tax':
				return true;
			default:
				return false;
		}
	}

	// 数値のチェック
	private function validate_number( $val, $type = 'numeric' ) {
		switch( $type ) {
			case 'numeric':
				return is_numeric( $val );
			case 'int':
				return is_int( $val );
			case 'float':
				return is_float( $val );
			default:
				return false;
		}
	}

	/**
	 * セキュリティを強化するためのトラッキングハッシュ配列を取得する。なければ作成 mkdummy
	 */
	public function get_tracking_hash_array( $url = null ) {
		//mkdummy
        $tracking_id = $this->get_tracking_id( $url );
        $data_dir    = $this->get_data_dir_path();
        $thash_file  = $data_dir . $tracking_id . '_tracking_hash.php';

        $new_thash_ary = [];
        //get now hash
        global $wp_filesystem;
        global $qahm_time;
        $now_utime = $qahm_time->now_unixtime();
		$newhash   = hash( 'fnv164', (string)wp_rand() );
        if ( $wp_filesystem->exists( $thash_file ) ) {
            $th_serial = $this->wrap_get_contents( $thash_file );
            $thash_ary = $this->wrap_unserialize( $th_serial );

            $recent_utime = $thash_ary[0]['create_utime'];
            $th_interval  = $now_utime - $recent_utime;
            if ( 3600 * 24 < $th_interval  ) {
                $new_thash_ary[0] = ['create_utime' => $now_utime, 'tracking_hash' => $newhash];
                $new_thash_ary[1] = $thash_ary[0];
                $new_th_serial    = $this->wrap_serialize( $new_thash_ary );
                $this->wrap_put_contents( $thash_file, $new_th_serial );
            } else {
                $new_thash_ary = $thash_ary;
            }
        } else {
                $new_thash_ary[0] = ['create_utime' => $now_utime, 'tracking_hash' => $newhash];
                $new_th_serial   = $this->wrap_serialize( $new_thash_ary );
                $this->wrap_put_contents( $thash_file, $new_th_serial );
        }
        return $new_thash_ary;
	}

	/**
	 * hash値があればtrue。なければfalse mkdummy
	 */
	public function check_tracking_hash( $checkhash, $url = null ) {
		//mkdummy
        $hash_ary = $this->get_tracking_hash_array( $url );
        $is_in    = false;
        foreach ( $hash_ary as $hash ) {
            if ( $checkhash === $hash['tracking_hash'] ) {
                $is_in = true;
            }
        }
        return $is_in;
	}


} // end of class


