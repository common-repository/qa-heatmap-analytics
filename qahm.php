<?php
/**
 * QA Analytics メインファイル
 *
 * @package qa_heatmap_analytics
 */

/*
Plugin Name: QA Analytics - with Heatmaps & Replay, Privacy Friendly
Plugin URI: https://quarka.org/en/
Description: Collects, records and visualizes visits data. You can own precise data and analyze them with stats, heatmap, session replay, and more.
Author: QuarkA
Author URI: https://quarka.org/en/
Version: 4.1.2.0
Text Domain: qa-heatmap-analytics
Requires at least: 5.6
Tested up to: 6.6.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/*
== Copyright ==

heatmap.js
Copyright (c) 2015 Patrick Wied
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://www.patrick-wied.at/static/heatmapjs/

sweetalert2.js
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://github.com/sweetalert2/sweetalert2

FontAwesome
License: CC BY 4.0 License, https://fontawesome.com/license/free
Source: https://github.com/FortAwesome/Font-Awesome

jQuery custom content scroller
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: http://manos.malihu.gr/jquery-custom-content-scroller/

Codepen
Copyright (c) 2020 ma_suwa, kanaparty, Nick Steele
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://codepen.io/

Chart.js
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://github.com/chartjs/Chart.js

Moment-with-locales.js
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://momentjs.com/

Date Range Picker
Copyright (c) 2012-2019 Dan Grossman
License: MIT License, https://opensource.org/licenses/mit-license.php
Source: https://www.daterangepicker.com/

All CSS and JavaScript code and images in plugin by QuarkA
Copyright (c) 2020 QuarkA Team All Rights Reserved.

*/

$qahm_time_start = microtime(true);

// filesystem_methodが direct or ftpextじゃなければヘルプリンクを表示
require_once ABSPATH . 'wp-admin/includes/file.php';
$access_type = get_filesystem_method();
if( ! ( $access_type === 'direct' || $access_type === 'ftpext' ) ) {
    // 直接書き込み権限がない場合は、ユーザーに通知を表示する
	add_action(
		'admin_notices',
		function () {
			echo '<div id="qahm-error-filesystem" class="error notice is-dismissible">';
			echo '<p>';
			esc_html_e( 'QA Analytics cannot be enabled because you lack the necessary write permissions for the file.', 'qa-heatmap-analytics' );
			echo '<a href="https://mem.quarka.org/en/manual/site-environment/" target="_blank" rel="noopener">';
			esc_html_e( 'See "Supported site environment"', 'qa-heatmap-analytics' );
			echo '</a></p>';
			echo '</div>';
		}
	);
	return;
}

// include
require_once dirname( __FILE__ ) . '/vendor/autoload.php';
require_once dirname( __FILE__ ) . '/qahm-const.php';

$path = WP_CONTENT_DIR . '/' . QAHM_TEXT_DOMAIN . '-data/qa-config.php';
if ( file_exists( $path ) ) {
	require_once $path;
}

require_once dirname( __FILE__ ) . '/class-qahm-base.php';
require_once dirname( __FILE__ ) . '/class-qahm-time.php';
require_once dirname( __FILE__ ) . '/class-qahm-log.php';
require_once dirname( __FILE__ ) . '/class-qahm-data-encryption.php';
require_once dirname( __FILE__ ) . '/class-qahm-file-base.php';
require_once dirname( __FILE__ ) . '/class-qahm-file-data.php';
require_once dirname( __FILE__ ) . '/class-qahm-sql-table.php';
require_once dirname( __FILE__ ) . '/class-qahm-db.php';
require_once dirname( __FILE__ ) . '/class-qahm-license.php';
require_once dirname( __FILE__ ) . '/class-qahm-update.php';
require_once dirname( __FILE__ ) . '/class-qahm-behavioral-data.php';
require_once dirname( __FILE__ ) . '/class-qahm-article-post.php';
require_once dirname( __FILE__ ) . '/class-qahm-view-base.php';
require_once dirname( __FILE__ ) . '/class-qahm-view-heatmap.php';
require_once dirname( __FILE__ ) . '/class-qahm-view-replay.php';
require_once dirname( __FILE__ ) . '/class-qahm-google-api.php';
require_once dirname( __FILE__ ) . '/class-qahm-cron-proc.php';
require_once dirname( __FILE__ ) . '/class-qahm-data-api.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-base.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-home.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-datastudio.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-heatmap.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-seo.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-realtime.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-config.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-license.php';
require_once dirname( __FILE__ ) . '/class-qahm-admin-page-help.php';
require_once dirname( __FILE__ ) . '/class-qahm-activate.php';
require_once dirname( __FILE__ ) . '/class-qahm-load.php';
require_once dirname( __FILE__ ) . '/class-qahm-dashboard-widget.php';

$qahm_loadtime = (microtime(true) - $qahm_time_start);
$qahm_loadtime = round( $qahm_loadtime, 5);
