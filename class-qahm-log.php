<?php
/**
 * プラグインのログを管理
 *
 * @package qa_heatmap
 */

$qahm_log = new QAHM_Log();

class QAHM_Log extends QAHM_Base {

	// 現在設定しているログの出力レベル
	const LEVEL = self::DEBUG;

	// ログの出力レベル一覧
	const ERROR = 0;                    // エラー
	const WARN  = 1;                    // エラーではないが例外的な事
	const INFO  = 2;                    // 記録したい情報
	const DEBUG = 3;                    // 開発時に必要な情報

	// ログを削除する際に残す最大行数
	const DELETE_LINE  = 10000;

	/**
	 * ログファイルのパスを取得
	 */
	public function get_log_file_path() {
		global $wp_filesystem;
		$path = $this->get_data_dir_path() . 'log/';
		if ( ! $wp_filesystem->exists( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		$path .= 'qalog.txt';
		return $path;
	}

	/**
	 * ログの公開鍵ファイルのパスを取得
	 */
	public function get_key_file_path() {
		return plugin_dir_path( __FILE__ ) . 'key/qalog.pem';
	}

	/**
	 * 一定の行数まで溜まったログを削除
	 */
	public function delete() {
		global $wp_filesystem;
		$path_log = $this->get_log_file_path();
		if ( ! $wp_filesystem->exists( $path_log ) ) {
			return;
		}

		$log_contents = $wp_filesystem->get_contents( $path_log );
		$log_ary      = explode( PHP_EOL, $log_contents );

		if ( self::DELETE_LINE >= count( $log_ary ) ) {
			return;
		}

		array_splice( $log_ary, self::DELETE_LINE );
		$log_contents = implode( PHP_EOL, $log_ary );
		$wp_filesystem->put_contents( $path_log, $log_contents );
	}

	/**
	 * ログ出力
	 */
	private function log( $log, $level, $backtrace ) {
		if ( self::LEVEL < $level ) {
			return '';
		}
		
		global $wp_filesystem;
		$path_log = $this->get_log_file_path();
		$path_key = $this->get_key_file_path();

		switch ( $level ) {
			case self::ERROR:
				$level = 'ERROR';
				break;
			case self::WARN:
				$level = 'WARNING';
				break;
			case self::INFO:
				$level = 'INFO';
				break;
			case self::DEBUG:
				$level = 'DEBUG';
				break;
		}

		// ファイル名
		$file = basename( $backtrace[0]['file'] );
		
		// 行数
		$line = $backtrace[0]['line'];

		// ログが配列なら文字列化
		if ( is_array( $log ) ){
			$log = $this->array_to_string( $log );
		}

		global $qahm_time;
		if( method_exists( $qahm_time, 'now_str' ) ) {
			$time = '[' . $qahm_time->now_str() . ']';
		} else {
			$time = '[Unknown time]';
		}
		$log  = sprintf( '%s %s, %s, %s:%s, %s', $time, $level, QAHM_PLUGIN_VERSION, $file, $line, $log );

		if ( QAHM_DEBUG >= QAHM_DEBUG_LEVEL['debug'] || ( defined( 'QAHM_CONFIG_VIEW_LOG' ) && QAHM_CONFIG_VIEW_LOG === true ) ) {
			$this->file_put_contents_prepend( $path_log, $log.PHP_EOL );
		} else {
			$key  = $wp_filesystem->get_contents( $path_key );
			openssl_public_encrypt( $log, $crypted, $key );
			$crypted = base64_encode( $crypted );
			$this->file_put_contents_prepend( $path_log, $crypted.PHP_EOL );
		}
		return $log;
	}

	/**
	 * 先頭行にログを追加
	 */
	private function file_put_contents_prepend( $path, $data ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem->exists( $path ) ) {
			$wp_filesystem->put_contents( $path, $data );
			return;
		}

		$contents = $wp_filesystem->get_contents( $path );
		$new_data = $data . $contents;

		$wp_filesystem->put_contents( $path, $new_data );
	}

	/**
	 * errorレベルのログを出力
	 */
	public function error( $log ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- This function is retained due to its low logging frequency and its importance in providing essential debugging information for ongoing maintenance and troubleshooting.
		$log = $this->log( $log, self::ERROR, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) );
		return $log;
	}

	/**
	 * warningレベルのログを出力
	 */
	public function warning( $log ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- This function is retained due to its low logging frequency and its importance in providing essential debugging information for ongoing maintenance and troubleshooting.
		$log = $this->log( $log, self::WARN, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) );
		return $log;
	}

	/**
	 * infoレベルのログを出力
	 */
	public function info( $log ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- This function is retained due to its low logging frequency and its importance in providing essential debugging information for ongoing maintenance and troubleshooting.
		$log = $this->log( $log, self::INFO, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) );
		return $log;
	}

	/**
	 * debugレベルのログを出力
	 */
	public function debug( $log ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- This function is retained due to its low logging frequency and its importance in providing essential debugging information for ongoing maintenance and troubleshooting.
		$log = $this->log( $log, self::DEBUG, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 ) );
		return $log;
	}

	/**
	 * 配列を文字列に変換
	 * print_rではなく自作している理由はPlugin Check対策
	 */
	private function array_to_string( $array, $indent = 0 ) {
		$output = '';
		$prefix = str_repeat( ' ', $indent * 4 ); // インデントをスペースで作成

		foreach ( $array as $key => $value ) {
			// キーを出力
			$output .= $prefix . '[' . $key . '] => ';

			// 値が配列の場合は再帰的に処理
			if ( is_array( $value ) ) {
				$output .= "Array\n";
				$output .= array_to_string( $value, $indent + 1 ); // 再帰呼び出しでネストに対応
			} else {
				// 値が配列以外の場合はそのまま出力
				$output .= $value . "\n";
			}
		}

		return $output;
	}
}
