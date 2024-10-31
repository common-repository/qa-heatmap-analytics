<?php
/**
 * QA Analytics定数の宣言
 *
 * constを優先して使用。defineでのみ対応可能な定数はdefine
 *
 * @package qa_heatmap
 */

// プラグイン冒頭で入力されている値のdefine
define( 'QAHM_PLUGIN_NAME', 'QA Analytics' );
define( 'QAHM_PLUGIN_VERSION', get_file_data( dirname( __FILE__ ) . '/qahm.php', array( 'version' => 'Version' ) )['version'] );
define( 'QAHM_TEXT_DOMAIN', get_file_data( dirname( __FILE__ ) . '/qahm.php', array( 'text_domain' => 'Text Domain' ) )['text_domain'] );

// プラグイン用
const QAHM_NAME          = 'qahm';
const QAHM_OPTION_PREFIX = QAHM_NAME . '_';

// rectermを完全削除する時に消す
const QAHM_RECTERM_TABLE    = QAHM_NAME . '_recterm';

const QAHM_DEBUG_LEVEL = array (
	'release' => 0,
	'staging' => 1,
	'debug'   => 2
);

const QAHM_DEVICES = array(
	'desktop' => array(
		'name'         => 'dsk',
		'id'           => 1,
		'display_name' => 'desktop',
	),
	'tablet' => array(
		'name'         => 'tab',
		'id'           => 2,
		'display_name' => 'tablet',
	),
	'smartphone' => array(
		'name'         => 'smp',
		'id'           => 3,
		'display_name' => 'smartphone',
	),
);

/*
	qahm用のwp_option 右は初期値
	ここに登録したパラメーターはアンインストール時にwp_optionsから自動で削除される
*/
const QAHM_OPTIONS = array(
	'access_role'                  => 'administrator',
	'achievements'                 => '',
	'announce_friend_plan'         => true,
	'anontrack'                    => 0,
	'cb_init_consent'              => 'yes',
	'cb_sup_mode'                  => 'no',
	'data_retention_dur'           => 90,
	'goals'                        => '',
	'google_credentials'           => '',
	'google_is_redirect'           => false,
	'heatmap_sort_view'            => 10,
	'is_first_heatmap_setting'     => true,
	'license_plans'                => null,
	'license_option'               => null,
	'license_key'                  => '',
	'license_id'                   => '',
	'license_message'              => '',
	'license_activate_time'        => 0,
	'plugin_version'               => QAHM_PLUGIN_VERSION,
	'plugin_first_launch'          => true,
	'pv_limit_rate'                => 0,
	'pv_warning_mail_month'        => null,
	'pv_over_mail_month'           => null,
	'send_email_address'           => '',
	'siteinfo'                     => '',
);

// アンインストール時に削除する専用のオプションを羅列していく。
// こちらの配列には、今は使用していないが旧バージョンで使用していたQAHM_OPTIONSのパラメータを追加するイメージ
const QAHM_UNINSTALL_OPTIONS = array(
	'campaign_oneyear_popup',
	'cap_article',
	'cron_exec_date',
	'email_notice',
	'data_save_month',
	'data_save_pv',
	'heatmap_measure_max',
	'heatmap_sort_rec',
	'is_raw_save_all',
	'license_password',
	'license_plan',
	'over_mail_time',
	'recterm_version',
);

const QAHM_DB_OPTIONS = array(
	'qa_readers_version'           => 1,
	'qa_pages_version'             => 1,
	'qa_utm_media_version'         => 1,
	'qa_utm_sources_version'       => 1,
	'qa_utm_campaigns_version'     => 1,
	'qa_pv_log_version'            => 1,
	'qa_search_log_version'        => 1,
	'qa_page_version_hist_version' => 1,
	'qa_gsc_query_log_version'     => 1,
);

require_once dirname( __FILE__ ) . '/qahm-const-ignore.php';
require_once dirname( __FILE__ ) . '/qahm-const-domain.php';

// メモリーの初期値など各ファイル共通
const QAHM_MEMORY_LIMIT_MIN = 256;

const QAHM_USE_LSCMD_LISTFILE = false;