<?php
/**
 * プラグインを有効化
 * DBのテーブルに関しては自前のクラス内でアクティベート処理を実行している
 * これはregister_activation_hook関数内ではグローバル変数のアクセス権がないためである
 * 参考：https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/register_activation_hook
 *
 * アンインストール処理についてはuninstall.phpを参照
 * 上記URLの理由にてqahm-uninstall.phpという名称には出来なかった
 *
 * @package qa_heatmap
 */

// データの初期化
new QAHM_Activate();

class QAHM_Activate extends QAHM_File_Base {

	const HOOK_CRON_DATA_MANAGE = QAHM_OPTION_PREFIX . 'cron_data_manage';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

		$path = $this->get_plugin_main_file_path();
		register_activation_hook( $path, array( $this, 'activation' ) );
		register_deactivation_hook( $path, array( $this, 'deactivation' ) );
		
		// スケジュールイベントを設定（消失用にこのタイミングで。念のため）
		add_action( 'wp_loaded', array( $this, 'set_schedule_event_list' ) );
	}

	/**
	 * add_filter 2分毎に実行するcronのスケジュール
	 */
	public function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['2min'] ) ) {
			$schedules['2min'] = array(
				'interval' => 2 * 60,
				'display'  => 'Once every 2 minutes',
			);
		}
		return $schedules;
	}

	/**
	 * プラグイン有効化時の処理
	 */
	public function activation() {
		$this->wrap_mkdir( $this->get_data_dir_path( 'readers' ) );
		$this->wrap_mkdir( $this->get_data_dir_path( 'heatmap-view-work' ) );

		// 念のため
		$this->deactivation();

		// wp_optionsの初期値設定
		foreach ( QAHM_OPTIONS as $key => $value ) {
			$this->check_exist_update( $key, $value );
		}

		// QAHM専用ユーザー権限の追加
		add_role(
			'qahm-manager',
			__( 'QA Analytics Manager', 'qa-heatmap-analytics' ),
			array(
				'read' 					=> true, // WordPress のデフォルト権限、ダッシュボードへのアクセスを許可
				'qahm_manage_settings' 	=> true, // カスタム権限（current_user_can($capability)関数で使える）
				'qahm_view_reports' 	=> true,
			)
		);
		add_role(
			'qahm-viewer',
			__( 'QA Analytics Viewer', 'qa-heatmap-analytics' ),
			array(
				'read'         			=> true,
				'qahm_manage_settings' 	=> false,
				'qahm_view_reports' 	=> true,
			)
		);
	}

	/**
	 * プラグイン無効化時の処理
	 */
	public function deactivation() {
		wp_clear_scheduled_hook( self::HOOK_CRON_DATA_MANAGE );
		// QA専用ユーザー権限の削除
		remove_role( 'qahm-manager' );
		remove_role( 'qahm-viewer' );
	}

	/**
	 * オプションが存在しなければアップデート
	 */
	private function check_exist_update( $option, $value ) {
		if ( $this->wrap_get_option( $option, -123454321 ) === -123454321 ) {
			$this->wrap_update_option( $option, $value );
		}
	}

	/**
	 * スケジュールイベントを設定。全てのcronスケジュールをここに登録
	 */
	public function set_schedule_event_list() {
		$this->set_schedule_event( '2min', self::HOOK_CRON_DATA_MANAGE );
	}

	/**
	 * スケジュールイベントを設定
	 */
	private function set_schedule_event( $recurrence, $hook ) {
		if ( ! wp_next_scheduled( $hook ) ) {
			$timestamp = time();

			// WordPressのタイムゾーンを考慮してスケジュールイベントを登録
			$gmt_timestamp = current_time( 'timestamp', true );

			// スケジュールイベントを登録
			wp_schedule_event( $gmt_timestamp, $recurrence, $hook );
		}
	}
}