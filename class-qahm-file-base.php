<?php
/**
 *
 *
 * @package qa_heatmap
 */

class QAHM_File_Base extends QAHM_Base {
	protected function wrap_exists( $path ) {
		global $wp_filesystem;
		return $wp_filesystem->exists( $path );
	}

	/**
	 * ディレクトリの存在チェック＆ディレクトリを作成
	 * ディレクトリが既に存在した場合や新規作成した場合にtrueを返す
	 */
	protected function wrap_mkdir( $path ) {
		global $wp_filesystem;
		if ( $wp_filesystem->exists( $path ) ) {
			return true;
		} else {
			return $wp_filesystem->mkdir( $path );
		}
	}

	protected function wrap_dirlist( $path ) {
		global $wp_filesystem;
		$ret_ary = array();
		if ( is_readable( $path ) ) {

			if ( defined( 'FS_METHOD' ) ) {
				switch ( FS_METHOD ) {
					case 'ftpext':
						$files = $wp_filesystem->dirlist( $path );
						foreach ( $files as $file ) {
							// 「.」「..」以外のファイルを出力
							$lastmodunix = filemtime( $path . $file['name'] );
							$ret_ary[]   = array(
								'name'        => $file['name'],
								'lastmodunix' => $lastmodunix,
								'size'        => $file['size']
							);
						}
						break;

					default:
						// ディレクトリ内のファイルを取得
						$files = scandir( $path );
						foreach ( $files as $file_name ) {
							// 「.」「..」以外のファイルを出力
							if ( ! preg_match( '/^(\.|\.\.)$/', $file_name ) ) {
								$lastmodunix = filemtime( $path . $file_name );
								$filesize    = filesize( $path . $file_name );
								$ret_ary[]   = array(
									'name'        => $file_name,
									'lastmodunix' => $lastmodunix,
									'size'        => $filesize
								);
							}
						}
						break;
				}
			} else {
				$files = scandir( $path );
				foreach ( $files as $file_name ) {
					// 「.」「..」以外のファイルを出力
					if ( ! preg_match( '/^(\.|\.\.)$/', $file_name ) ) {
						$lastmodunix = filemtime( $path . $file_name );
						$filesize    = filesize( $path . $file_name );
						$ret_ary[]   = array(
							'name'        => $file_name,
							'lastmodunix' => $lastmodunix,
							'size'        => $filesize
						);
					}
				}
			}
		}
		if ( ! empty( $ret_ary ) ) {
			return $ret_ary;
		} else {
			return false;
		}
	}

	/**
	 * wp_remote_getをqahm用にラップした関数
	 * 失敗した場合WP_Errorが返る
	 */
	protected function wrap_remote_get( $url, $dev_name='dsk' ) {
		$bot = QAHM_NAME . 'bot/' . QAHM_PLUGIN_VERSION;
		
		// デバイスによるユーザーエージェント指定
		switch ( $dev_name ) {
			case 'smp':
				$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1' . ' ' . $bot;
				break;
			case 'tab':
				$ua = 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1' . ' ' . $bot;
				break;
			default:
				$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36' . ' ' . $bot;
				break;
		}
		$args = array(
			'user-agent' => $ua,
			'timeout'    => 60,
			'sslverify'  => false,
		);

		return wp_remote_get( $url, $args );
	}

	/**
	 * qahm共通のfile_get_contentsのstream_context_create内で使用するオプションを取得
	 * ここは後々remote_getを使う形に変更
	 */
	protected function get_stream_options( $dev_name ) {
		$bot = QAHM_NAME . 'bot/' . QAHM_PLUGIN_VERSION;

		// デバイスによるユーザーエージェント指定
		switch ( $dev_name ) {
			case 'smp':
				$ua = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1' . ' ' . $bot;
				break;
			case 'tab':
				$ua = 'User-Agent: Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1' . ' ' . $bot;
				break;
			default:
				$ua = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36' . ' ' . $bot;
				break;
		}

		$options = array(
			'http' => array(
				'method'           => 'GET',
				'header'           => $ua,
				'timeout'          => 10,
				'ignore_errors'    => true,
			),
			'ssl' => array(
				'verify_peer'      => false,
				'verify_peer_name' => false,
			)
		);

		return $options;
	}
	

	/**
	 * rawデータのディレクトリのパスを取得
	 */
	protected function get_raw_dir_path( $type, $id, $dev_name, $tracking_id = null ) {
		// $wp_filesystemオブジェクトの呼び出し
		global $wp_filesystem;

		$dir = $this->get_data_dir_path();
		if ( ! $this->wrap_mkdir( $dir ) ) {
			return false;
		}
		
		// トラッキングIDがnullなら今使用しているWPのトラッキングID
		if ( ! $tracking_id ) {
			$tracking_id = $this->get_tracking_id();
		}
		$dir .= $tracking_id . '/';
		if ( ! $this->wrap_mkdir( $dir ) ) {
			return false;
		}

		if ( $type ) {
			$dir .= $type . '/';
			if ( ! $this->wrap_mkdir( $dir ) ) {
				return false;
			}
		}

		$dir .= $id . '/';
		if ( ! $this->wrap_mkdir( $dir ) ) {
			return false;
		}

		$dir .= 'temp/';
		if ( ! $this->wrap_mkdir( $dir ) ) {
			return false;
		}

		$dir .= $dev_name . '/';
		if ( ! $this->wrap_mkdir( $dir ) ) {
			return false;
		}

		return $dir;
	}

	/**
	 * ディレクトリのURL or パスから要素を求める
	 */
	protected function get_raw_dir_elem( $url ) {
		$url_exp = explode( '/', $url );

		$data_num = null;
		for ( $i = 0; $i < count( $url_exp ); $i++ ) {
			// dataフォルダの位置を求める
			if ( $url_exp[ $i ] === 'data' ) {
				$data_num = $i;
				break;
			}
		}
		if ( $data_num === null || ! isset( $url_exp[ $i + 4 ] ) ) {
			return null;
		}
		if ( ! $url_exp[ $i + 5 ] ) {
			return null;
		}

		$data         = array();
		$data['type'] = $url_exp[ $i + 2 ];
		$data['id']   = $url_exp[ $i + 3 ];
		$data['ver']  = $url_exp[ $i + 4 ];
		$data['dev']  = $url_exp[ $i + 5 ];
		return $data;
	}

	/**
	 * タイプとIDから元URLを取得
	 */
	protected function get_base_url( $type, $id ) {
		switch ( $type ) {
			case 'home':
				return home_url( '/' );
			case 'page_id':
			case 'p':
				return get_permalink( $id );
			case 'cat':
				return get_category_link( $id );
			case 'tag':
				return get_tag_link( $id );
			case 'tax':
				return get_term_link( $id );
			default:
				return null;
		}
	}

	/** ------------------------------
	 * 容量計算ルーチン一式
	 */

	//DB
	public function count_db() {
		//calc db
		global $qahm_db;
		global $wpdb;
		$alldbsize_ary = [];
		$alltb_ary = $qahm_db->alltable_name();
		foreach ( $alltb_ary as $tablename ) {
			//1行だけとる
			$rowsize = 0;
			$query = 'SELECT * from '. $tablename . ' LIMIT 1';
			$res   = $qahm_db->get_results( $query,'ARRAY_A' );
			$line = $res[0];
			if ($line !== null ) {
				foreach ($line as $val ) {
					if ( is_string( $val ) ) {
						if ( is_numeric( $val ) ) {
							$num = (int)$val;
							if ( $num <= 255 ) {
								$rowsize += 1;
							} else if ( $num <= 65535 ) {
								$rowsize += 2;
							} else {
								$rowsize += 4;
							}
						} else {
							$rowsize += strlen( $val );
						}
					}
				}
			}
			if ( $rowsize === 0 ) {
				$rowsize = 100;
			}

			$query = 'SELECT count(*) from ' . $tablename;
			$res   = $qahm_db->get_results( $query );
			$count = (int)$res[0]->{'count(*)'};
			$byte = $rowsize * $count;
			$alldbsize_ary[] =  [ 'tablename' => $tablename, 'count' => $count, 'byte' => $byte ];
		}
		$allcount = 0;
		$allbyte  = 0;
		foreach ( $alldbsize_ary as $table ) {
			$allcount += $table['count'];
			$allbyte  += $table['byte'];
		}
		$alldbsize_ary[] = ['tablename' => 'all', 'count' => $allcount, 'byte' => $allbyte ];
		return $alldbsize_ary;
	}

	//file
	public function count_files() {
		global $qahm_time;
		global $wp_filesystem;
		$data_dir = $this->get_data_dir_path();

		// データディレクトリの再帰検索を行い、ファイル数と総容量を求める
		$search_dirs =  array( $data_dir );
		$allfile_cnt = 0;
		$allfilesize = 0;
		for ( $iii = 0; $iii < count( $search_dirs ); $iii++ ) {   // 再帰のためループ毎にcount関数を実行しなければならない
			$dir = $search_dirs[ $iii ];
			if ( $wp_filesystem->is_dir( $dir ) && $wp_filesystem->exists( $dir ) ) {

				// ディレクトリ内に存在するファイルのリストを取得
				$file_list = $this->wrap_dirlist( $dir );
				if ( $file_list ) {
					// ディレクトリ内のファイルを全てチェック
					foreach ( $file_list as $file ) {
						// ディレクトリなら再帰検索用の配列にディレクトリを登録
						if ( is_dir( $dir . $file['name'] ) ) {
							$search_dirs[] = $dir . $file['name'] . '/';
						} else {
							// ファイルをカウントしサイズを取得
							++$allfile_cnt;
							$allfilesize += $file['size'];
						}
					}
				}
			}
		}
		return [ 'filecount' => $allfile_cnt, 'size' => $allfilesize ];
	}

	//days pv
	public function count_this_month_pv() {
		$ret_count = 0;

		global $qahm_db;
		global $qahm_time;
		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$vw_summary_dir     = $myview_dir . 'summary/';
		if ( $this->wrap_exists($vw_summary_dir . 'days_access.php' ) ) {
			$daysum_ary = $this->wrap_unserialize($qahm_db->wrap_get_contents($vw_summary_dir . 'days_access.php'));
			$month = $qahm_time->month();
			if ((int)$month < 10 ) {
				$month = '0' . (string)$month;
			} else {
				$month = (string)$month;
			}
			$this_month_1st = $qahm_time->year() . '-' . $month . '-01 00:00:00';
			$this_month_1st_unix = $qahm_time->str_to_unixtime( $this_month_1st );
			foreach ($daysum_ary as $val ) {
				$nowunixtime = $qahm_time->str_to_unixtime( $val['date'] . ' 00:00:00' );
				if ($this_month_1st_unix <= $nowunixtime) {
					$ret_count +=  $val['pv_count'];
				}
			}
		}
		return $ret_count;
	}

	//days pv
	public function get_pvterm_start_date() {

		global $qahm_db;
		global $qahm_time;
		$ret_day = $qahm_time->now_str('Y-m-d');

		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/';
		$vw_summary_dir     = $myview_dir . 'summary/';

		$daysum_ary = $this->wrap_unserialize($qahm_db->wrap_get_contents($vw_summary_dir . 'days_access.php'));
		if ( isset ( $daysum_ary[0] ) ) {
			$ret_day = $daysum_ary[0]['date'];
		}
		return $ret_day;
	}

	//days heatmap
	public function get_hmterm_start_date() {
		global $qahm_time;

		$data_dir = $this->get_data_dir_path();
		$view_dir          = $data_dir . 'view/';
		$traking_id        = $this->get_tracking_id();
		$myview_dir        = $view_dir . $traking_id . '/view_pv';
		$raw_p_dir         = $myview_dir . '/raw_p/';

		$allfiles = $this->wrap_dirlist( $raw_p_dir );
		$minunixt = $qahm_time->now_unixtime();
		if ($allfiles) {
			foreach ( $allfiles as $file ) {
				$filename = $file[ 'name' ];
				if ( is_file( $raw_p_dir . $filename ) ) {
					$f_date = substr( $filename, 0, 10 );
					$f_datetime = $f_date . ' 00:00:00';
				}
				$f_unixt = $qahm_time->str_to_unixtime( $f_datetime );
				if ( $f_unixt < $minunixt && $f_unixt !== 0 ) {
					$minunixt = $f_unixt;
				}
			}
		}
		$mindate = $qahm_time->unixtime_to_str( $minunixt );
		$ret_day = substr( $mindate, 0, 10 );
		return $ret_day;
	}

	/** ------------------------------
	 * ユーザーエージェントからデバイス名に変換
	 */
	public function user_agent_to_device_name( $ua ) {
		// スマホからのアクセス
		if ( stripos( $ua, 'iphone' ) !== false || // iphone
			stripos( $ua, 'ipod' ) !== false || // ipod
			( stripos( $ua, 'android' ) !== false && stripos( $ua, 'mobile' ) !== false ) || // android
			( stripos( $ua, 'windows' ) !== false && stripos( $ua, 'mobile' ) !== false ) || // windows phone
			( stripos( $ua, 'firefox' ) !== false && stripos( $ua, 'mobile' ) !== false ) || // firefox phone
			( stripos( $ua, 'bb10' ) !== false && stripos( $ua, 'mobile' ) !== false ) || // blackberry 10
			( stripos( $ua, 'blackberry' ) !== false ) // blackberry
			) {
			return 'smp';
		}
		// タブレット
		// mobileという文字が含まれていないAndroid端末はすべてタブレット
		elseif ( stripos( $ua, 'android' ) !== false || stripos( $ua, 'ipad' ) !== false ) {
			return 'tab';
		} else {
			return 'dsk';
		}
	}

	/**
	 * ユーザーエージェントからOS名に変換
	 */
	public function user_agent_to_os_name( $ua ) {
		if (preg_match('/Windows NT 10.0/', $ua)) {
			return 'Windows 10';
		} elseif (preg_match('/Windows NT 6.3/', $ua)) {
			return 'Windows 8.1';
		} elseif (preg_match('/Windows NT 6.2/', $ua)) {
			return 'Windows 8';
		} elseif (preg_match('/Windows NT 6.1/', $ua)) {
			return 'Windows 7';
		} elseif (preg_match('/Mac OS X ([0-9\._]+)/', $ua, $matches)) {
			return 'Mac OS X ' . str_replace('_', '.', $matches[1]);
		} elseif (preg_match('/Linux ([a-z0-9_]+)/', $ua, $matches)) {
			return 'Linux ' . $matches[1];
		} elseif (preg_match('/OS ([a-z0-9_]+)/', $ua, $matches)) {
			return 'iOS ' . str_replace('_', '.', $matches[1]);
		} elseif (preg_match('/Android ([a-z0-9\.]+)/', $ua, $matches)) {
			return 'Android ' . $matches[1];
		} else {
			return 'Unknown';
		}
	}

	/**
	 * ユーザーエージェントからブラウザ名に変換
	 */
	public function user_agent_to_browser_name( $ua ) {
		if (preg_match('/(Iron|Sleipnir|Maxthon|Lunascape|SeaMonkey|Camino|PaleMoon|Waterfox|Cyberfox)\/([0-9\.]+)/', $ua, $matches)) {
			return $matches[1] . $matches[2];
		} elseif (preg_match('/Edge\/([0-9\.]+)/', $ua, $matches)) {
			return 'Edge' . ' ' . $matches[2];
		} elseif (preg_match('/(^Opera|OPR).*\/([0-9\.]+)/', $ua, $matches)) {
			return 'Opera' . ' ' . $matches[2];
		} elseif (preg_match('/Chrome\/([0-9\.]+)/', $ua, $matches)) {
			return 'Chrome' . ' ' . $matches[1];
		} elseif (preg_match('/Firefox\/([0-9\.]+)/', $ua, $matches)) {
			return 'Firefox' . ' ' . $matches[1];
		} elseif (preg_match('/(MSIE\s|Trident.*rv:)([0-9\.]+)/', $ua, $matches)) {
			return 'Internet Explorer' . ' ' . $matches[2];
		} elseif (preg_match('/\/([0-9\.]+)(\sMobile\/[A-Z0-9]{6})?\sSafari/', $ua, $matches)) {
			return 'Safari' . ' ' . $matches[1];
		} else {
			return 'Unknown';
		}
	}

	/**
	 * デバイスIDをデバイス名に変換
	 */
	protected function device_id_to_device_name( $id ) {
		foreach ( QAHM_DEVICES as $qahm_dev ) {
			if ( $qahm_dev['id'] === (int) $id ) {
				return $qahm_dev['name'];
			}
		}

		return false;
	}

	/**
	 * デバイス名をデバイスIDに変換
	 */
	protected function device_name_to_device_id( $name ) {
		foreach ( QAHM_DEVICES as $qahm_dev ) {
			if ( $qahm_dev['name'] === $name ) {
				return $qahm_dev['id'];
			}
		}

		return false;
	}


	/**
	 * $wp_filesystem->put_contentsのラップ関数。ファイル書込用
	 */
	function wrap_put_contents( $file, $data) {
		global $wp_filesystem;
		$newstr  = '<?php http_response_code(404);exit; ?>'.PHP_EOL;
		$newstr .= $data;
		return $wp_filesystem->put_contents( $file, $newstr );

	}

	/**
	 * $wp_filesystem->get_contentsのラップ関数。ファイル読み出し用。1行目の 404を抜く
	 */

	function wrap_get_contents( $file ) {
		global $wp_filesystem;
		$string = $wp_filesystem->get_contents( $file );
		if ( $string ) {
			if ( strpos ( $string, 'http_response_code(404)' ) ) {
				return substr(strstr( $string, PHP_EOL ),1);
			} else {
				return $string;
			}
		} else {
			return false;
		}
	}

	/**
	 * $wp_filesystem->get_contents_arrayのラップ関数。ファイル読み出し用。1行目の 404を抜く
	 */
	function wrap_get_contents_array( $file ) {
		global $wp_filesystem;
		$ary    = $wp_filesystem->get_contents_array( $file );
		if ( $ary ) {
			$retary = array();
			$maxcnt = count( $ary );
			if ( strpos( $ary[0],'http_response_code(404)' ) ) {
				$startn = 1;
			} else {
				$startn = 0;
			}
			for ( $iii = $startn; $iii < $maxcnt; $iii++ ) {
				$retary[] = $ary[$iii];
			}
			return $retary;
		} else {
			return false;
		}
	}

	/**
	 * $wp_filesystem->deleteのラップ関数
	 */
	function wrap_delete( $file ) {
		global $wp_filesystem;
		return $wp_filesystem->delete( $file );
	}
	
	/**
	 * wp_json_encodeのラップ関数。ファイル書込用
	 */
	protected function wrap_json_encode( $data ) {
		return wp_json_encode( $data );
	}


	/**
	 * json_decodeのラップ関数。ファイル読み出し用。1行目の 404を抜く
	 * 簡易的なserialzeデータのチェックも行う
	 * この関数を使用する際は引数$dataの先頭に$aryの中身のいずれかの値を入れないこと
	 */
	function wrap_json_decode( $data ) {
		$str = substr( $data, 0, 2 );
		$ary = array( 'a:', 'b:', 'd:', 'i:', 'O:', 's:' );
		if( in_array( $str, $ary, true ) ) {
			return $this->wrap_unserialize( $data );
		}else{
			return json_decode( $data );
		}
	}


	/**
	 * serializeのラップ関数
	 */
	protected function wrap_serialize( $value ) {
		return serialize( $value );
	}


	/**
	 * serializeのラップ関数
	 */
	public function wrap_unserialize( $data ){

		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- ini_set() is required here to adjust runtime configuration dynamically for specific functionality.
		ini_set('pcre.backtrack_limit', 5000000);

		$arr = @unserialize($data);

		if ($arr !== FALSE) {
			return $arr;
		}

		$pattern = "/(;s:[0-9]+:\")([\s\S]+?)([\"N];s:[0-9]+:)/";
		//$pattern = "/(\"base_html\";s:[0-9]+:\")(.+)/";

		$fixed_data = preg_replace_callback($pattern, function ($matches) {

			if(!preg_match("/^N/",$matches[3])){
				$matchfix = str_replace("\"","'",$matches[2]);
			}else{
				$matchfix = $matches[2];
			}

			#return $matches[1].$matchfix.$matches[3];
			return $matches[1].$matchfix.$matches[3];

		}, $data );


		$strFixed  = preg_replace_callback(
			'/s:([0-9]+):\"(.*?)\";/',
			function ($matches) { return "s:" . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
			$fixed_data
		);
		$arr = @unserialize($strFixed);
		if (FALSE !== $arr) {
			return $arr;
		}

		$strFixed  = preg_replace_callback(
			'/s:([0-9]+):\"(.*?)\";/',
			function ($match) {
				return "s:" . strlen($match[2]) . ':"' . $match[2] . '";';
			},
			$fixed_data);

		$arr = @unserialize($strFixed);
		if (FALSE !== $arr) {
			return $arr;
		}

		$strFixed = preg_replace("%\n%", "", $fixed_data);
		$data     = preg_replace('%";%', "µµµ", $strFixed);
		$tab      = explode("µµµ", $data);
		$new_data = '';
		foreach ($tab as $line) {
			$new_data .= preg_replace_callback(
				'%\bs:(\d+):"(.*)%',
				function ($matches) {
					$string       = $matches[2];
					$right_length = strlen($string); // yes, strlen even for UTF-8 characters, PHP wants the mem size, not the char count

					return 's:' . $right_length . ':"' . $string . '";';
				},
				$line);
		}
		$strFixed = $new_data;
		$arr = @unserialize($strFixed);
		if (FALSE !== $arr) {
			return $arr;
		}


		$strFixed  = preg_replace_callback(
			'/s:([0-9]+):"(.*?)";/',
			function ($match) {
				return "s:" . strlen($match[2]) . ":\"" . $match[2] . "\";";
			},
			$fixed_data
		);

		$arr = @unserialize($strFixed);
		if (FALSE !== $arr) {
			return $arr;
		}

		$strFixed  = preg_replace_callback('/s\:(\d+)\:\"(.*?)\";/s', function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; }, $fixed_data);
		$arr       = @unserialize($strFixed);
		if (FALSE !== $arr) {
			return $arr;
		}

		$strFixed  = preg_replace_callback(
			'/s\:(\d+)\:\"(.*?)\";/s',
			function ($matches) { return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";'; },
			$fixed_data);;
		$arr = @unserialize($strFixed);
		if (FALSE !== $arr) {

			return $arr;
		}

		return FALSE;

	}


	/**
	 * tsv形式の文字列データを二次元配列に変換して返す
	 */
	protected function convert_tsv_to_array( $tsv ) {
		$tsv_ary = array();
		$tsv_col = explode( PHP_EOL, $tsv );

		foreach ( $tsv_col as $tsv_row ) {
			$tsv_row_ary = explode( "\t", $tsv_row );
			$tsv_ary[]   = $tsv_row_ary;
		}

		return $tsv_ary;
	}

	
	/**
	 * 二次元配列をtsv形式の文字列データに変換して返す
	 */
	protected function convert_array_to_tsv( $ary ) {
		$tsv = '';

		for ( $i = 0, $col_cnt = count( $ary ); $i < $col_cnt; $i++ ) {
			for ( $j = 0, $raw_cnt = count( $ary[$i] ); $j < $raw_cnt; $j++ ) {
				// 値にPHP_EOLや\tが入っていた場合はtsvの形が崩れる可能性があるので無視
				$replace = str_replace( PHP_EOL, '', $ary[ $i ][ $j ] );
				$replace = str_replace( "\t", '', $replace );
				$tsv .= $replace;

				if ( $j === $raw_cnt - 1 ) {
					if ( $i !== $col_cnt -1 ) {
						$tsv .= PHP_EOL;
					}
				} else {
					$tsv .= "\t";
				}
			}
		}

		return $tsv;
	}
}
