<?php
/**
 * ヒートマップビューやリプレイビューに共通する処理を書く基本クラス
 *
 * @package qa_heatmap
 */

class QAHM_View_Base extends QAHM_File_Data {

	/**
	 * wp_loadへの相対パスをtempディレクトリのファイルに書き込み
	 */
	protected function write_wp_load_path() {
		global $wp_filesystem;
		$view_path = plugin_dir_path( __FILE__ );
		$temp_dir  = plugin_dir_path( __FILE__ ) . 'temp/';
		if ( ! $wp_filesystem->exists( $temp_dir ) ) {
			$wp_filesystem->mkdir( $temp_dir );
		}

		$wp_load_rel_path      = $this->get_relative_path( $view_path, ABSPATH . 'wp-load.php' );
		$wp_filesystem->put_contents( $temp_dir . 'wp-load-path.php', $wp_load_rel_path );
	}

	/**
	 * 2つの絶対パス間の相対パスを取得
	 */
	protected function get_relative_path( $base_abs_path, $tar_abs_path, $option = true ) {
		// 戻り値（$url）を $option に基づいて初期化
		$url = ( $option ) ? './' : '';

		// 構成要素を '/' で分解
		$base_abs_path   = explode( '/', $base_abs_path );
		$tar_abs_path = explode( '/', $tar_abs_path );

		// 要素をはじめから順番に比較し同じ要素は排除
		do {
			$b = array_shift( $base_abs_path );
			$t = array_shift( $tar_abs_path );
		} while ( $b == $t );

		// 要素をひとつ捨てすぎたので配列に戻す
		array_unshift( $base_abs_path, $b );
		array_unshift( $tar_abs_path, $t );

		// 残りの要素数を数える
		$bcount = count( $base_abs_path );
		$tcount = count( $tar_abs_path );

		// ひとつずつしか残ってないので同じディレクトリ
		if ( $bcount == 1 && $tcount == 1 ) {
			// ならばファイル名だけを $url に格納
			$url .= array_pop( $tar_abs_path );
		} else {
			// 上位へ走査が必要な分 '../' を出力
			if ( $bcount > 1 ) {
				$url = str_repeat( '../', $bcount - 1 );
			}
			// $tar_abs_path のパスを '/' で連結して $url に格納
			$url .= implode( '/', $tar_abs_path );
		}

		// 出来上がったところで出力
		return $url;
	}

	/**
	 * base_htmlをqahm用に最適化
	 * サイトの状態を保つためにviewportの書き換えは現在行っていないが、今後する可能性はある
	 * 上記の理由により、dev_nameは現在使用していない
	 */
	protected function opt_base_html( $current_path, $base_html, $base_url, $dev_name ) {
		// サイトの言語
		$locale = get_locale();

		// htmlファイルは最低限しか書き換えないようにする方針
		// あまり書き換えすぎると、html構成をバージョンアップした際に支障が出るため

		// php部分
		// セキュリティ
		$php = '<?php ' .
			'require_once("' . $this->get_relative_path( $current_path, ABSPATH . 'wp-load.php' ) . '");' .
			'if( !is_user_logged_in() ){ http_response_code(404);exit; }' .
			'if ( ! ' . $this->check_qahm_access_cap( "qahm_view_reports" ) . ') { http_response_code(404);exit; }' .
			'?>';

		$html = $php;
		if ( $base_html ) {
			$html .= $base_html;
		}

		// 正規表現で<head>タグを正確に検出し、<base>タグを追加する
		$pattern = '/<head\b([^>]*)>/i'; // \bは単語境界を表し、headのみをマッチさせる
		$replacement = '<head$1>' . "\n" . '<base href="' . $base_url . '">';

		// 置き換えを実行
		$html = preg_replace($pattern, $replacement, $html);

		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet --  This stylesheet is safely loaded internally for admin use and does not impact the frontend or the original WordPress site.
		$css_reset = '<link rel="stylesheet" type="text/css" href="' . plugins_url( 'css/reset.css', __FILE__ ) . '">';
		$css_style = '<link rel="stylesheet" type="text/css" href="' . plugins_url( 'css/style.css', __FILE__ ) . '">';
		// phpcs:enable
		$html = str_replace( '</head>', $css_reset . $css_style . '</head>', $html );

		return $html;
	}


	/**
	 * 特定のタグを削除したhtmlを返す
	 * 現在は未使用
	 */
	protected function delete_specific_tag( $html, $tag, $target ) {
		while ( true ) {
			$html_user_f = strstr( $html, $target, true );
			$html_user_b = strstr( $html, $target, false );
			if ( $html_user_f && $html_user_b ) {
				$pos_user_f  = strrpos( $html_user_f, '<' . $tag );
				$pos_user_b  = strpos( $html_user_b, '/' . $tag . '>' ) + strlen( '/' . $tag . '>' );
				$pos_user_b += strpos( $html, $target );
				$str_user    = substr( $html, $pos_user_f, $pos_user_b - $pos_user_f );
				$html        = str_replace( $str_user, '', $html );
			} else {
				break;
			}
		}
		return $html;
	}

	/**
	 * 連想配列に指定したキーが存在し、値が空でない場合、配列の中身を返す
	 * 存在しない場合、または値が空であれば第三引数を返す
	 */ 
	protected function array_key_exists_val( $key, $ary, $not_val = '' ) {
		if ( ! array_key_exists( $key, $ary ) ) {
			return $not_val;
		}
		if ( $ary[$key] === '' ) {
			return $not_val;
		}
		return $ary[$key];
	}
} // end of class

