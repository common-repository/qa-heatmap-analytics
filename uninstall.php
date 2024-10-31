<?php

//
// 注意
// WordPress 3.1以降、パラメータ$functionに指定できる関数にはクラスメソッドを使用できない。
// 具体的には、次のような指定はNGとなり、関数の登録に失敗する。
// register_uninstall_hook( __FILE__, array( &$this, 'myplugin_uninstall' ) );
// https://elearn.jp/wpman/function/register_uninstall_hook.html
//
// uninstall.phpはプラグイン削除時に必ず実行される
// このためアンインストール処理はuninstall.phpにて行う
//

// WP_UNINSTALL_PLUGINが定義されているかチェック
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- The uninstall process may involve complex operations that could take time, so the execution time is extended to ensure completion without timeouts.
set_time_limit( 60 * 30 );

require_once dirname( __FILE__ ) . '/qahm-const.php';

// qahm wp_optionsの削除
foreach ( QAHM_OPTIONS as $key => $value ) {
	delete_option( QAHM_OPTION_PREFIX . $key );
}
// qahm wp_db_optionsの削除
foreach ( QAHM_DB_OPTIONS as $key => $value ) {
	delete_option( QAHM_OPTION_PREFIX . $key );
}
// qahm uninstall_optionsの削除
foreach ( QAHM_UNINSTALL_OPTIONS as $key ) {
	delete_option( QAHM_OPTION_PREFIX . $key );
}

// qa関連のuser_meta削除
$users = get_users( array( 'fields' => array( 'ID' ) ) );
foreach( $users as $user ){
	$user_meta_ary = get_user_meta( $user->ID );
	foreach( $user_meta_ary as $meta_key => $meta_value ){
		if ( strncmp( $meta_key, QAHM_OPTION_PREFIX, strlen( QAHM_OPTION_PREFIX ) ) === 0 ) {
			delete_user_meta( $user->ID, $meta_key );
		}
	}
}

// qahm tableの削除
global $wpdb;
$table_name = $wpdb->prefix . QAHM_RECTERM_TABLE;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . QAHM_NAME . '_recrefresh';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_pages';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_page_version_hist';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_pv_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_readers';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_search_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_utm_campaigns';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_utm_media';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_utm_sources';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );

$table_name = $wpdb->prefix . 'qa_gsc_query_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct queries are required for table cleanup, and caching is unnecessary since the tables are being removed.
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );


// dataディレクトリの削除
global $wp_filesystem;
$data_path = $wp_filesystem->wp_content_dir() . QAHM_TEXT_DOMAIN . '-data/';
if( $wp_filesystem->exists( $data_path ) ) {
	remove_dir( $data_path );
}

function remove_dir( $dir ) {
	global $wp_filesystem;

	$list = $wp_filesystem->dirlist( $dir );
	foreach( $list as $item ) {
		$path = $dir . DIRECTORY_SEPARATOR . $item['name'];

		if ( $wp_filesystem->is_dir( $path ) ) {
			// 再帰
			remove_dir( $path );
		}
		else {
			// ファイルを削除
			$wp_filesystem->delete( $path );
		}
	}

	// ディレクトリを削除
	$wp_filesystem->rmdir( $dir );
}