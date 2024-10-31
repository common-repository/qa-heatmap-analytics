<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_google_api = new QAHM_Google_Api();

class QAHM_Google_Api extends QAHM_File_Base {
	/**
	 * Google_Client
	 */
	public $client   = null;
	public $prop_url = null;

	// 認証済みか判定
	public function is_auth() {
		if ( ! $this->client || $this->client->isAccessTokenExpired() ) {
			return false;
		}
		return true;
	}

	public function get_client_id() {
		if ( ! $this->client ) {
			return null;
		}
		return $this->client->getClientId();
	}

	public function get_client_secret() {
		if ( ! $this->client ) {
			return null;
		}
		return $this->client->getClientSecret();
	}

	public function get_redirect_uri() {
		if ( ! $this->client ) {
			return null;
		}
		return $this->client->getRedirectUri();
	}

	public function get_access_token() {
		if ( ! $this->client ) {
			return null;
		}
		return $this->client->getAccessToken();
	}

	public function set_credentials( $client_id, $client_secret, $token ) {
		global $qahm_data_enc;
		$credentials = wp_json_encode( array( 'client_id' => $client_id, 'client_secret' => $client_secret, 'token' => $token ) );
		$credentials = $qahm_data_enc->encrypt( $credentials );
		$this->wrap_update_option( 'google_credentials', $credentials );
	}

	public function get_credentials() {
		global $qahm_data_enc;
		$credentials = $this->wrap_get_option( 'google_credentials' );
	
		if ( $credentials ) {
			$credentials = $qahm_data_enc->decrypt( $credentials );
			$credentials = json_decode( $credentials, true );
	
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $credentials;
			} else {
				// JSONのデコードに失敗した場合のエラーハンドリング
				return null;
			}
		} else {
			// 認証情報が保存されていない場合のエラーハンドリング
			return null;
		}
	}

	// 初期化
	// 参考：https://googleapis.github.io/google-api-php-client/v2.7.1/Google_Client.html
	public function init( $app_name, $scopes, $redirect_uri, $redirect_flag = false, $token = null ) {
		// $this->wrap_get_option( 'google_credentials' )はapiの認証画面で設定することを前提に
		$credentials   = $this->wrap_get_option( 'google_credentials' );
		if ( ! $credentials ) {
			return false;
		}

		global $qahm_data_enc;

		$this->client  = new QAAnalyticsVendor\Google\Client();
		$credentials   = $qahm_data_enc->decrypt( $credentials );
		$credentials   = json_decode( $credentials, true );
		$client_id     = $credentials['client_id'];
		$client_secret = $credentials['client_secret'];
		$this->client->setApplicationName( $app_name );
		$this->client->setClientId( $client_id );
		$this->client->setClientSecret( $client_secret );
		$this->client->setRedirectUri( $redirect_uri );
		$this->client->setScopes( $scopes );
		$this->client->setAccessType( 'offline' );
		$this->client->setApprovalPrompt( 'force' );

		if ( ! $token ) {
			if ( $redirect_flag ) {
				$this->wrap_update_option( 'google_is_redirect', true );
				$authUrl = $this->client->createAuthUrl();
				wp_redirect( $authUrl );
				exit;
			}

			$code  = $this->wrap_filter_input( INPUT_GET, 'code' );
			if ( $code && $this->client->fetchAccessTokenWithAuthCode( $code ) ) {
				$token                = $this->client->getAccessToken();
				$credentials['token'] = $token;
				$this->set_credentials( $credentials['client_id'], $credentials['client_secret'], $credentials['token'] );
			} elseif ( $credentials['token'] ) {
				$token = $credentials['token'];
			}

			/*
			if ( ! $token ) {
				if ( $redirect_flag ) {
					$this->wrap_update_option( 'google_is_redirect', true );
					$authUrl = $this->client->createAuthUrl();
					wp_redirect( $authUrl );
					exit;
				} else {
					return false;
				}
			}
			*/
			if ( ! $token ) {
				return false;
			}
		}

		// アクセストークンの有効期限を確認する
		// 有効期限切れなら再取得して保存する
		$this->client->setAccessToken( $token );
		if( $this->client->isAccessTokenExpired() ){  // if token expired
			$this->client->refreshToken( $token['refresh_token'] );
			$credentials['token'] = $this->client->getAccessToken();
			$this->set_credentials( $credentials['client_id'], $credentials['client_secret'], $credentials['token'] );
		}

		return $this->is_auth();
	}


	/**
	 * サーチコンソールに接続するURLを返す
	 * 
	 * 処理の流れとしては
	 * 1. urlプレフィックスで接続
	 * 2. 1.に失敗した場合、ドメインで接続
	 * 3. 1, 2, において成功したurlを返す。失敗した場合はnullを返す
	 */
	public function get_property_url() {
		if ( $this->prop_url ) {
			return $this->prop_url;
		}

		if ( $this->client === null ) {
			return null;
		}

		$token = $this->client->getAccessToken();

		// URLをパースする
		$parse_url = wp_parse_url( home_url() );
		$sc_domain = 'sc-domain:' . urlencode( $parse_url['host'] );

		$url_ary = [
			'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( home_url() ) . '/searchAnalytics/query',
			'https://www.googleapis.com/webmasters/v3/sites/' . $sc_domain . '/searchAnalytics/query'
		];

		foreach ( $url_ary as $ep_url ) {
			$headers = array(
				'Authorization' => 'Bearer ' . $token['access_token'],
				'Content-Type' => 'application/json'
			);

			$body = '{
				"startDate":"2010-01-01",
				"endDate":"2010-01-01",
				"type":"web",
				"dimensions":["query"],
				"rowLimit":"1",
				"startRow":"0"
			}';

			$args = array( 'headers' => $headers, 'body' => $body );
			$response = wp_remote_post( $ep_url, $args );
			if ( is_wp_error( $response ) ) {
				continue;
			}

			$query_ary = json_decode( $response['body'], true );
			if ( array_key_exists( 'error', $query_ary ) ) {
				continue;
			}

			$this->prop_url = $ep_url;
			return $this->prop_url;
		}

		return null;
	}


	/**
	 * サーチコンソールの接続テスト
	 * エラーが発生していればエラー配列が返る
	 * 初期化していなければfalseが返る
	 * 処理に問題なければnullが返る
	 */
	public function test_search_console_connect() {
		if ( $this->client === null ) {
			return false;
		}
		$token   = $this->client->getAccessToken();
		$ep_url  = $this->get_property_url();
		if ( $ep_url === null ) {
			$ep_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( home_url() ) . '/searchAnalytics/query';
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $token['access_token'],
			'Content-Type' => 'application/json'
		);

		$body = '{
			"startDate":"2010-01-01",
			"endDate":"2010-01-01",
			"type":"web",
			"dimensions":["query"],
			"rowLimit":"1",
			"startRow":"0"
		}';

		$args = array( 'headers' => $headers, 'body' => $body );
		$response = wp_remote_post( $ep_url, $args );
		if ( is_wp_error( $response ) ) {
			return array(
				'code' => '001',
				'message' => 'is_wp_error',
			);
		}
		$query_ary = json_decode( $response['body'], true );
		if ( array_key_exists( 'error', $query_ary ) ) {
			return $query_ary['error'];
		}

		return null;
	}

	/**
	 * サーチコンソールから取得した検索キーワードをdbにインサート
	 * update_dateに挿入する日付は$start_dateとなる
	 * 現在1日毎にしかデータを取得していないので引数はひとつでも良いが、後々の拡張性のためこのままに
	 */
	public function insert_search_console_keyword( $start_date, $end_date ) {
		global $qahm_db;
		global $qahm_time;
		global $qahm_log;

		// 今日の日付の3日前のデータは溜まっていない可能性があるのでサーチしない
		$tar_date = $qahm_time->today_str();
		$tar_date = $qahm_time->xday_str( '-3', $tar_date );
		if ( $qahm_time->xday_num( $start_date, $tar_date ) > 0 ) {
			return;
		}

		// すでに該当日付のデータが作成されていれば処理を中断
		// insert関数では引数に月フラグを設けていないため、この処理は数日単位では実行できない（ファイル存在チェックが邪魔となる）
		// もしも今後拡張するならここは変更する必要あり
		$gsc_dir           = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc' );
		$summary_dir       = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc/summary' );
		$temp_dir          = $this->get_data_dir_path( 'temp' );
		$gsc_lp_query_path = $gsc_dir . $start_date . '_gsc_lp_query.php';
		if ( $this->wrap_exists( $gsc_lp_query_path ) ) {
			return;
		}

		$temp_dir = $this->get_data_dir_path( 'temp' );
		$token    = $this->client->getAccessToken();
		$ep_url   = $this->get_property_url();
		if ( $ep_url === null ) {
			$ep_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( home_url() ) . '/searchAnalytics/query';
		}
		$type_ary = array( 'web', 'image', 'video' );

		$headers = array(
			'Authorization' => 'Bearer ' . $token['access_token'],
			'Content-Type' => 'application/json'
		);

		// その日の検索キーワード一覧を取得
		$key_ary   = array();
		$row_idx   = 0;
		$row_limit = 25000;
		for ( $type_idx = 0, $type_max = count( $type_ary ); $type_idx < $type_max; $type_idx++ ) {
			while ( true ) {
				$start_row = $row_limit * $row_idx;
				$body = '{
					"startDate":"' . $start_date . '",
					"endDate":"' . $end_date . '",
					"type":"' . $type_ary[$type_idx] . '",
					"dimensions":["query"],
					"rowLimit":"' . $row_limit . '",
					"startRow":"' . $start_row . '"
				}';
				$args = array( 'headers' => $headers, 'body' => $body );

				$response = wp_remote_post( $ep_url, $args );
				if ( is_wp_error( $response ) ) {
					break;
				}

				$query_cnt = $this->wrap_get_contents( $temp_dir . 'cron_gsc_query_cnt.php' );
				$this->wrap_put_contents( $temp_dir . 'cron_gsc_query_cnt.php', $query_cnt + 1 );
				
				$query_ary = json_decode( $response['body'], true );
				if ( ! array_key_exists( 'rows', $query_ary ) ) {
					break;
				}
				
				foreach ( $query_ary['rows'] as $query_rows ) {
					$query = $query_rows['keys'][0];
					if ( ! in_array( $query, $key_ary ) ) {
						$key_ary[] = substr( $query, 0, 190 );
					}
				}

				if ( count( $query_ary['rows'] ) < $row_limit ) {
					break;
				} else {
					$row_idx++;
				}
			}
		}

		// 取得した検索キーワード一覧をdbテーブルにインサート

		// dbテーブルに挿入されているキーワードとの重複チェック
		$table_name      = $qahm_db->prefix . 'qa_gsc_query_log';
		$insert_val_ary  = array();
		$insert_type_ary = array();
		$update_val_ary  = array();
		$update_type_ary = array();
		for( $key_idx = 0, $key_max = count( $key_ary ); $key_idx < $key_max; $key_idx++ ) {
			$is_insert = true;
			$is_update = false;

			$query            = 'SELECT update_date FROM ' . $table_name . ' WHERE keyword = %s';
			$qa_gsc_query_log = $qahm_db->get_results( $qahm_db->prepare( $query, $key_ary[$key_idx] ), ARRAY_A );
			if ( $qa_gsc_query_log ) {
				$is_insert = false;
				if ( $qahm_time->xday_num( $qa_gsc_query_log[0]['update_date'], $start_date ) < 0 ) {
					$is_update = true;
				}
			}
			
			if ( $is_insert ) {
				$insert_val_ary[] = $key_ary[$key_idx];
				$insert_val_ary[] = $start_date;
				$insert_type_ary[] = '(%s, %s)';
			}
			if ( $is_update ) {
				$update_val_ary[]  = $start_date;
				$update_type_ary[] = '%s';
			}
		}

		// insert
		if ( ! empty( $insert_val_ary ) ) {
			$sql = 'INSERT IGNORE INTO ' . $table_name . ' ' .
					'(keyword, update_date) ' .
					'VALUES ' . join( ',', $insert_type_ary );
			$result = $qahm_db->query( $qahm_db->prepare( $sql, $insert_val_ary ) );
			if ( $result === false && $qahm_db->last_error !== '' ) {
				$qahm_log->error( $qahm_db->last_error );
			}
		}

		// update
		if ( ! empty( $update_val_ary ) ) {
			array_unshift( $update_val_ary, $start_date );
			$sql = 'UPDATE ' . $table_name . ' ' .
					'SET update_date = %s ' .
					'WHERE keyword IN (' . join( ',', $update_type_ary ) . ')';
			$result = $qahm_db->query( $qahm_db->prepare( $sql, $update_val_ary ) );
			if ( $result === false && $qahm_db->last_error !== '' ) {
				$qahm_log->error( $qahm_db->last_error );
			}
		}

		//echo '------------------------<br>';
		//var_dump( $gsc_query_log );
		//echo '<br>';
		//echo '------------------------<br>';
	}

	/**
	 * サーチコンソールデータの作成
	 */
	public function create_search_console_data( $start_date, $end_date, $is_month_data ) {
		global $qahm_time;
		global $qahm_db;
		global $qahm_log;

		// 今日の日付の3日前のデータは溜まっていない可能性があるのでサーチしない
		$tar_date = $qahm_time->today_str();
		$tar_date = $qahm_time->xday_str( '-3', $tar_date );
		if ( $qahm_time->xday_num( $start_date, $tar_date ) > 0 ) {
			return;
		}

		// すでに該当日付のデータが作成されていれば処理を中断
		$gsc_dir     = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc' );
		$summary_dir = $this->get_data_dir_path( 'view/' . $this->get_tracking_id() . '/gsc/summary' );
		$temp_dir    = $this->get_data_dir_path( 'temp' );
		if ( $is_month_data ) {
			// 現在時刻の3日前が同じ月ならサーチコンソールの月データが溜まっていない可能性があるので処理を省く
			$comp_y = $qahm_time->year( $start_date );
			$comp_m = $qahm_time->month( $start_date );
			
			$tar_date = $qahm_time->today_str();
			$tar_date = $qahm_time->xday_str( '-3', $tar_date );
			$tar_y = $qahm_time->year( $tar_date );
			$tar_m = $qahm_time->month( $tar_date );
			
			if ( $comp_y > $tar_y ) {
				return;
			}
			
			if ( $comp_y === $tar_y && $comp_m >= $tar_m ) {
				return;
			}

			$gsc_lp_query_path      = $gsc_dir . $start_date . '_gsc_lp_query_1mon.php';
			$gsc_query_lp_path      = $gsc_dir . $start_date . '_gsc_query_lp_1mon.php';
			$summary_gsc_query_path = $summary_dir . $start_date . '_summary_gsc_query_1mon.php';
		} else {
			$gsc_lp_query_path      = $gsc_dir . $start_date . '_gsc_lp_query.php';
			$gsc_query_lp_path      = $gsc_dir . $start_date . '_gsc_query_lp.php';
			$summary_gsc_query_path = $summary_dir . $start_date . '_summary_gsc_query.php';
		}

		if ( $this->wrap_exists( $gsc_lp_query_path ) ) {
			return;
		}
		/*
		if ( $this->wrap_exists( $gsc_lp_query_path ) && $this->wrap_exists( $summary_gsc_query_path ) ) {
			return;
		}
		*/
		/*
		if ( $this->wrap_exists( $gsc_lp_query_path ) && $this->wrap_exists( $gsc_query_lp_path ) && $this->wrap_exists( $summary_gsc_query_path ) ) {
			return;
		}
		*/

		$token   = $this->client->getAccessToken();
		$ep_url  = $this->get_property_url();
		if ( $ep_url === null ) {
			$ep_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( home_url() ) . '/searchAnalytics/query';
		}
		$type_ary = array( 'web', 'image', 'video' );

		$headers = array(
			'Authorization' => 'Bearer ' . $token['access_token'],
			'Content-Type' => 'application/json'
		);

		//echo $date . '<br><br>';

		$gsc_lp_query_ary      = array();
		$gsc_query_lp_ary      = array();
		$summary_gsc_query_ary = array();

		// gsc_lp_query_aryを作成
		if ( $this->wrap_exists( $gsc_lp_query_path ) ) {
			$gsc_lp_query_ary = $this->wrap_get_contents( $gsc_lp_query_path );
			$gsc_lp_query_ary = unserialize( $gsc_lp_query_ary );

		} else {
			for ( $type_idx = 0, $type_max = count( $type_ary ); $type_idx < $type_max; $type_idx++ ) {
				
				$body = '{
					"startDate":"' . $start_date . '",
					"endDate":"' . $end_date . '",
					"type":"' . $type_ary[$type_idx] . '",
					"dimensions":["page"],
					"rowLimit":"25000",
				}';
				$args = array( 'headers' => $headers, 'body' => $body );
	
				$response = wp_remote_post( $ep_url, $args );
				if ( is_wp_error( $response ) ) {
					continue;
				}

				$query_cnt = $this->wrap_get_contents( $temp_dir . 'cron_gsc_query_cnt.php' );
				$this->wrap_put_contents( $temp_dir . 'cron_gsc_query_cnt.php', $query_cnt + 1 );
				
				$page_ary = json_decode( $response['body'], true );
				if ( ! array_key_exists( 'rows', $page_ary ) ) {
					continue;
				}
				/*
				var_dump($page_ary);
				echo '<br><br><br>';
				*/

				//echo $start_date . '-' . $end_date . '<br>';
				//echo '<strong>[' . $type_ary[$type_idx] . '] count: ' . count( $page_ary['rows'] ) . '</strong><br>';
				foreach ( $page_ary['rows'] as $page_rows ) {
					$base_url = $page_rows['keys'][0];
					//var_dump( $base_url );
					//echo '<br>';
	
					$body = '{
						"startDate":"' . $start_date . '",
						"endDate":"' . $end_date . '",
						"type":"' . $type_ary[$type_idx] . '",
						"dimensions":["query"],
						"dimensionFilterGroups": [
							{
								"groupType": "and",
								"filters": [
									{
										"dimension": "page",
										"operator": "equals",
										"expression": "' . $base_url . '"
									}
								]
							}
						],
						"rowLimit":"25000",
					}';
					$args = array( 'headers' => $headers, 'body' => $body );

					$response = wp_remote_post( $ep_url, $args );
					if ( is_wp_error( $response ) ) {
						continue;
					}
	
					$query_cnt = $this->wrap_get_contents( $temp_dir . 'cron_gsc_query_cnt.php' );
					$this->wrap_put_contents( $temp_dir . 'cron_gsc_query_cnt.php', $query_cnt + 1 );

					// 取得したURLをもとに、QAのDBから情報を取得
					$table_name = $qahm_db->prefix . 'qa_pages';
					$query      = 'SELECT page_id,wp_qa_type,wp_qa_id,title,update_date FROM ' . $table_name . ' WHERE url_hash = %s';
					$query      = $qahm_db->prepare( $query, hash( 'fnv164', $base_url ) );
					$qa_pages   = $qahm_db->get_results( $query, ARRAY_A );

					// 該当URLのqa_pagesが存在しなければインサートする
					if ( empty( $qa_pages ) ) {
						// URLからWPの投稿情報を求める
						$post = get_post( url_to_postid( $base_url ) );
						if ( $post === null ) {
							continue;
						}
						
						$type = '';
						if ( $post->post_type === 'post' ) {
							$type = 'p';
						} elseif( $post->post_type === 'page' ) {
							$type = 'page_id';
						}
						
						// SQLの生成
						$array_values    = array();
						$array_values[]  = $type;
						$array_values[]  = $post->ID;
						$array_values[]  = $base_url;
						$array_values[]  = hash( 'fnv164', $base_url );
						$array_values[]  = $post->post_title ;
						$array_values[]  = $qahm_time->now_str();
						$place_holders[] = '(%s, %s, %d, %s, %s, %s, %s)';

						$sql = 'INSERT INTO ' . $table_name . ' ' .
								'(tracking_id, wp_qa_type, wp_qa_id, url, url_hash, title, update_date) ' .
								'VALUES ' . join( ',', $place_holders );
						
						// SQL実行
						$result = $qahm_db->query( $qahm_db->prepare( $sql, $array_values ) );
						if ( $result === false && $qahm_db->last_error !== '' ) {
							$qahm_log->error( $qahm_db->last_error );
							continue;
						}
			
						$query      = 'SELECT page_id,wp_qa_type,wp_qa_id,title,update_date FROM ' . $table_name . ' WHERE page_id = %d';
						$query      = $qahm_db->prepare( $query, $qahm_db->insert_id );
						$qa_pages   = $qahm_db->get_results( $query, ARRAY_A );
					}

					$qa_pages_idx = 0;
					$qa_pages_max = count( $qa_pages );
					if ( $qa_pages_max > 0 ) {
						for( $temp_idx = 1; $temp_idx < $qa_pages_max; $temp_idx++ ) {
							if ( $qahm_time->xday_num( $qa_pages[$qa_pages_idx]['update_date'], $qa_pages[$temp_idx]['update_date'] ) < 0 ) {
								$qa_pages_idx = $temp_idx;
							}
						}
					}
					$qa_page    = $qa_pages[$qa_pages_idx];
					$page_id    = (int) $qa_page['page_id'];
					$wp_qa_type = $qa_page['wp_qa_type'];
					$wp_qa_id   = (int) $qa_page['wp_qa_id'];
					$title      = $qa_page['title'];
	
					$lp_query_idx = -1;
					$lp_query_max = count( $gsc_lp_query_ary );
					if ( $lp_query_max > 0 ) {
						for( $temp_lp_query_idx = 0; $temp_lp_query_idx < $lp_query_max; $temp_lp_query_idx++ ) {
							if ( $gsc_lp_query_ary[$temp_lp_query_idx]['page_id'] === $page_id ) {
								$lp_query_idx = $temp_lp_query_idx;
								break;
							}
						}
					}
	
					if ( $lp_query_idx === -1 ) {
						$gsc_lp_query_ary[] = array(
							'page_id'    => (int) $page_id,
							'wp_qa_type' => $wp_qa_type,
							'wp_qa_id'   => (int) $wp_qa_id,
							'title'      => $title,
							'url'        => $base_url
						);
						$lp_query_idx = $lp_query_max;
						$gsc_lp_query_ary[$lp_query_idx]['query'] = null;
					}
	
					$query_ary = json_decode( $response['body'], true );
					if ( ! array_key_exists( 'rows', $query_ary ) ) {
						continue;
					}
	
					/*
					echo '<strong>[' . $base_url . '] count: ' . count( $query_ary['rows'] ) . '</strong><br>';
					echo 'url: ' . $base_url . '<br>';
					echo 'page_id: ' . $page_id . '<br>';
					echo 'wp_qa_type: ' . $wp_qa_type . '<br>';
					echo 'wp_qa_id: ' . $wp_qa_id . '<br>';
					echo 'title: ' . $title . '<br>';
					echo '<br>';
					
					var_dump($query_ary);
					echo '<br><br><br>';
					*/
					
					$table_name = $qahm_db->prefix . 'qa_gsc_query_log';
					foreach ( $query_ary['rows'] as $query_idx => $query_rows ) {
						//var_dump( $rows );
						//echo '<br>';
	
						// query_idを求める
						$query_id         = null;
						$query            = 'SELECT query_id FROM ' . $table_name . ' WHERE keyword = %s';
						$qa_gsc_query_log = $qahm_db->get_results( $qahm_db->prepare( $query, $query_rows['keys'][0] ), ARRAY_A );
						if ( $qa_gsc_query_log ) {
							$query_id = (int) $qa_gsc_query_log[0]['query_id'];
						}
	
						$query_ary = array(
							'query_id'    => $query_id,
							'keyword'     => $query_rows['keys'][0],
							'search_type' => $type_idx + 1,
							'impressions' => (int) $query_rows['impressions'],
							'clicks'      => (int) $query_rows['clicks'],
							'position'    => (float) round( $query_rows['position'], 1 ),
						);
	
						if ( $gsc_lp_query_ary[$lp_query_idx]['query'] === null ) {
							$gsc_lp_query_ary[$lp_query_idx]['query'] = array();
						}
						$gsc_lp_query_ary[$lp_query_idx]['query'][] = $query_ary;
						
						/*
						$search_query = $query_rows['keys'][0];
						$clicks       = $query_rows['clicks'];
						$impression   = $query_rows['impressions'];
						$position     = $query_rows['position'];
	
						echo 'search_query: ' . $search_query . '<br>';
						echo 'clicks: ' . $clicks . '<br>';
						echo 'impression: ' . $impression . '<br>';
						echo 'position: ' . $position . '<br>';
						
						echo '<br>';
						*/
					}
	
					//echo '<br>';
				}
				//echo '<br>';
			}

			if ( ! empty( $gsc_lp_query_ary ) ) {
				$content = serialize( $gsc_lp_query_ary );
				$this->wrap_put_contents( $gsc_lp_query_path, $content );
				unset( $content );
			}
		}

		/*
		// gsc_query_lp_aryを作成
		// 名前がややこしいので、ここでは $gsc_lp_query = $lp_q, $gsc_query_lp = $q_lp と省略した表記にしている
		// ※gsc_query_lpは現在作らないようにしている。詳しくは仕様書参照
		if ( $this->wrap_exists( $gsc_query_lp_path ) ) {
			$gsc_query_lp_ary = $this->wrap_get_contents( $gsc_query_lp_path );
			$gsc_query_lp_ary = unserialize( $gsc_query_lp_ary );

		} else {
			for ( $lp_q_idx = 0, $lp_q_max = count( $gsc_lp_query_ary ); $lp_q_idx < $lp_q_max; $lp_q_idx++ ) {
				$lp_q_ary = $gsc_lp_query_ary[$lp_q_idx];
				if ( $lp_q_ary['query'] === null ) {
					continue;
				}

				$lp_q_param_ary = array(
					'page_id'    => $lp_q_ary['page_id'],
					'wp_qa_type' => $lp_q_ary['wp_qa_type'],
					'wp_qa_id'   => $lp_q_ary['wp_qa_id'],
					'title'      => $lp_q_ary['title'],
					'url'        => $lp_q_ary['url'],
				);

				$lp_q_query_ary = $lp_q_ary['query'];
				for ( $lp_q_query_idx = 0, $lp_q_query_max = count( $lp_q_query_ary ); $lp_q_query_idx < $lp_q_query_max; $lp_q_query_idx++ ) {
					$query_param_ary = $lp_q_query_ary[$lp_q_query_idx];
					
					// gsc_query_lpに配列が存在しなければ追加する
					$is_find = false;
					for ( $q_lp_idx = 0, $q_lp_max = count( $gsc_query_lp_ary ); $q_lp_idx < $q_lp_max; $q_lp_idx++ ) {
						if ( $gsc_query_lp_ary[$q_lp_idx]['query_id'] === $query_param_ary['query_id'] &&
							$gsc_query_lp_ary[$q_lp_idx]['search_type'] === $query_param_ary['search_type'] ) {
							$is_find = true;
							break;
						}
					}
					
					if ( $is_find ) {
						$gsc_query_lp_ary[$q_lp_idx]['lp'][] = $lp_q_param_ary;
					} else {
						$gsc_query_lp_ary[] = array(
							'query_id'    => $query_param_ary['query_id'],
							'keyword'     => $query_param_ary['keyword'],
							'search_type' => $query_param_ary['search_type'],
							'impressions' => $query_param_ary['impressions'],
							'clicks'      => $query_param_ary['clicks'],
							'position'    => $query_param_ary['position'],
							'lp'          => array( $lp_q_param_ary ),
						);
					}
				}
			}
			
			if ( ! empty( $gsc_query_lp_ary ) ) {
				$content = serialize( $gsc_query_lp_ary );
				$this->wrap_put_contents( $gsc_query_lp_path, $content );
				unset( $content );
			}
		}
		*/

		// summary_gsc_query_aryを作成
		// ※summary_gsc_query_aryは現在作らないようにしている。詳しくは仕様書参照
		/*
		if ( $this->wrap_exists( $summary_gsc_query_path ) ) {
			$summary_gsc_query_ary = $this->wrap_get_contents( $summary_gsc_query_path );
			$summary_gsc_query_ary = unserialize( $summary_gsc_query_ary );

		} else {
			$lp_q_max = count( $gsc_lp_query_ary );
			for ( $lp_q_idx = 0; $lp_q_idx < $lp_q_max; $lp_q_idx++ ) {
				if ( $gsc_lp_query_ary[$lp_q_idx]['query'] === null ) {
					// サマリーデータはnullのとき作らない。この仕様でいいのか確認
					continue;
				}

				// 一時的なクエリ結合配列を作成、search_type違いの結果を結合する
				$mearge_query_ary = array();

				// 一時的なqueryのコピー配列。こちらではmeargeフラグを追加して既に結合済か判定している
				$copy_query_ary = $gsc_lp_query_ary[$lp_q_idx]['query'];
				$query_max      = count( $copy_query_ary );

				// 合算クエリ配列の作成
				for ( $query_idx = 0; $query_idx < $query_max; $query_idx++ ) {
					if ( array_key_exists( 'merge', $copy_query_ary[$query_idx] ) ) {
						continue;
					}

					$query_id    = $copy_query_ary[$query_idx]['query_id'];
					$keyword     = $copy_query_ary[$query_idx]['keyword'];
					$impressions = $copy_query_ary[$query_idx]['impressions'];
					$clicks      = $copy_query_ary[$query_idx]['clicks'];
					$position    = $copy_query_ary[$query_idx]['position'];
					$tar_num     = 1;

					for ( $comp_query_idx = $query_idx + 1; $comp_query_idx < $query_max; $comp_query_idx++ ) {
						if ( $copy_query_ary[$query_idx]['query_id'] === $copy_query_ary[$comp_query_idx]['query_id'] ) {
							$impressions += $copy_query_ary[$comp_query_idx]['impressions'];
							$clicks      += $copy_query_ary[$comp_query_idx]['clicks'];
							$position    += $copy_query_ary[$comp_query_idx]['position'];
							$tar_num++;
							$copy_query_ary[$query_idx]['merge'] = true;
						}
					}

					// positionは平均値を求めている
					$position = round( $position / $tar_num, 1 );

					$mearge_query_ary[] = array(
						'page_id'     => $gsc_lp_query_ary[$lp_q_idx]['page_id'],
						'wp_qa_id'    => $gsc_lp_query_ary[$lp_q_idx]['wp_qa_id'],
						'wp_qa_type'  => $gsc_lp_query_ary[$lp_q_idx]['wp_qa_type'],
						'title'       => $gsc_lp_query_ary[$lp_q_idx]['title'],
						'url'         => $gsc_lp_query_ary[$lp_q_idx]['url'],
						'query_id'    => $query_id,
						'keyword'     => $keyword,
						'impressions' => $impressions,
						'clicks'      => $clicks,
						'position'    => $position,
					);
				}

				// クエリ配列の中から、その日に最もアクセス数が多い記事を求める = クリック数で良いのか確認
				$most_clicks_num = 0;
				$most_clicks_idx = 0;
				for ( $query_idx = 0, $query_max = count( $mearge_query_ary ); $query_idx < $query_max; $query_idx++ ) {
					if ( $most_clicks_num < $mearge_query_ary[$query_idx]['clicks']) {
						$most_clicks_num = $mearge_query_ary[$query_idx]['clicks'];
						$most_clicks_idx = $query_idx;
					}
				}

				$summary_gsc_query_ary[] = array(
					'page_id'     => $mearge_query_ary[$most_clicks_idx]['page_id'],
					'wp_qa_id'    => $mearge_query_ary[$most_clicks_idx]['wp_qa_id'],
					'wp_qa_type'  => $mearge_query_ary[$most_clicks_idx]['wp_qa_type'],
					'title'       => $mearge_query_ary[$most_clicks_idx]['title'],
					'url'         => $mearge_query_ary[$most_clicks_idx]['url'],
					'query_id'    => $mearge_query_ary[$most_clicks_idx]['query_id'],
					'keyword'     => $mearge_query_ary[$most_clicks_idx]['keyword'],
					'impressions' => $mearge_query_ary[$most_clicks_idx]['impressions'],
					'clicks'      => $mearge_query_ary[$most_clicks_idx]['clicks'],
					'position'    => $mearge_query_ary[$most_clicks_idx]['position'],
				);
			}

			if ( ! empty( $summary_gsc_query_ary ) ) {
				$content = serialize( $summary_gsc_query_ary );
				$this->wrap_put_contents( $summary_gsc_query_path, $content );
				unset( $content );
			}
		}
		*/
	}
} // end of class
