<?php
/**
 * 
 * @package qa_heatmap
 */

class QAHM_Update extends QAHM_File_Data {

	public function __construct() {
	}

	public function check_version() {
		global $qahm_license;
		global $qahm_db;
		global $qahm_time;
		global $qahm_log;

		$def = -123454321;
		$ver = $this->wrap_get_option( 'plugin_version', $def );
		
		if ( $ver === $def ) {
			$ver = '0.8.1.0';
			$this->wrap_update_option( 'plugin_version', '0.8.1.0' );
		} else {
			if ( $ver === QAHM_PLUGIN_VERSION ) {
				$this->delete_maintenance_file();
				return;
			}
		}
		
		if( version_compare( '0.8.2.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'achievements', '' );
			$this->wrap_update_option( 'plugin_version', '0.8.2.0' );
		}

		// データの変換＆メンテナンス画面を実装する
		if( version_compare( '1.0.2.0', $ver, '>' ) ) {
			// readers, realtime_view_tsv データの変換
			$readers_dir_path = $this->get_data_dir_path( 'readers' );
			$this->v1020_conv_realtime_view_data( $readers_dir_path . 'realtime_view_tsv.php', $readers_dir_path . 'realtime_view.php' );

			$temp_dir_path    = $readers_dir_path . 'temp/';
			$file_list = $this->wrap_dirlist( $temp_dir_path );
			if ( $file_list ) {
				foreach ( $file_list as $file ) {
					$file_path = $temp_dir_path . $file['name'];
					$this->v1020_conv_readers_temp_data( $file_path, $file_path );
				}
			}
			
			$finish_dir_path  = $readers_dir_path . 'finish/';
			$file_list = $this->wrap_dirlist( $finish_dir_path );
			if ( $file_list ) {
				foreach ( $file_list as $file ) {
					$file_path = $finish_dir_path . $file['name'];
					$this->v1020_conv_readers_finish_data( $file_path, $file_path );
				}
			}

			$dbin_dir_path    = $readers_dir_path . 'dbin/';
			$file_list = $this->wrap_dirlist( $dbin_dir_path );
			if ( $file_list ) {
				foreach ( $file_list as $file ) {
					$file_path = $dbin_dir_path . $file['name'];
					$this->v1020_conv_readers_finish_data( $file_path, $file_path );
				}
			}
			$this->wrap_update_option( 'plugin_version', '1.0.2.0' );
		}
		
		if( version_compare( '1.0.5.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'is_first_heatmap_setting', '' );
			$this->wrap_update_option( 'plugin_version', '1.0.5.0' );
		}
		
		if( version_compare( '1.0.8.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'heatmap_measure_max', 1 );
			$this->wrap_update_option( 'campaign_oneyear_popup', false );
			$this->wrap_update_option( 'plugin_version', '1.0.8.0' );
		}
		
		if( version_compare( '1.1.0.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'data_save_month', 2 );
			$this->wrap_update_option( 'plugin_version', '1.1.0.0' );
		}

		if( version_compare( '2.9.0.0', $ver, '>' ) ) {
			$plan = (int) $this->wrap_get_option( 'license_plan' );
			if ( 0 < $plan ) {
				// ライセンス情報を新しい形式に一新するので、この時点で強制的にライセンス認証を行う
				$key = $this->wrap_get_option( 'license_key' );
				$id  = $this->wrap_get_option( 'license_id' );
				$qahm_license->activate( $key, $id );
			}
			$this->wrap_update_option( 'plugin_version', '2.9.0.0' );
		}

		if( version_compare( '3.3.0.0', $ver, '>' ) ) {
			$qahm_sql_table = new QAHM_Sql_Table;
			$check_exists   = -123454321;
			$ver = $this->wrap_get_option( 'qa_gsc_query_log_version', $check_exists );
			if ( $ver === $check_exists ) {
				$query = $qahm_sql_table->get_qa_gsc_query_log_create_table();
				if ( $query ) {
					// queryのコメント、先頭末尾のスペースやTAB等を削除
					$query_ary = explode( PHP_EOL, $query );
					for ( $query_idx = 0, $query_max = count( $query_ary ); $query_idx < $query_max; $query_idx++ ) {
						$query_ary[$query_idx] = trim( $query_ary[$query_idx], " \t" );
						if ( substr( $query_ary[$query_idx], 0, 2 ) === '--' ) {
							unset( $query_ary[$query_idx] );
						}
					}
					$query = implode( '', $query_ary );
	
					// クエリ実行
					$query_ary = explode( ';', $query );
					for ( $query_idx = 0, $query_max = count( $query_ary ); $query_idx < $query_max; $query_idx++ ) {
						if ( $query_ary[$query_idx] ) {
							$qahm_db->query( $query_ary[$query_idx] );
						}
					}
					$this->wrap_put_contents( 'qa_gsc_query_log_version', QAHM_DB_OPTIONS['qa_gsc_query_log_version'] );
				}
			}

			$this->wrap_update_option( 'google_credentials', '' );
			$this->wrap_update_option( 'google_is_redirect', false );
			$this->wrap_update_option( 'plugin_version', '3.3.0.0' );
		}


		if( version_compare( '3.9.9.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'cb_sup_mode', 'no' );
			$this->wrap_update_option( 'pv_limit_rate', 0 );
			$this->wrap_update_option( 'data_retention_dur', 90 );
			$this->wrap_update_option( 'license_option', null );
			$this->wrap_update_option( 'plugin_first_launch', false );
			$this->wrap_update_option( 'pv_limit_rate', 0 );
			$this->wrap_update_option( 'pv_warning_mail_month', null );
			$this->wrap_update_option( 'pv_over_mail_month', null );
			$this->wrap_update_option( 'plugin_version', '3.9.9.0' );
		}

		if( version_compare( '3.9.9.1', $ver, '>' ) ) {
			$this->wrap_update_option( 'send_email_address', get_option( 'admin_email' ) );
			$this->wrap_update_option( 'plugin_version', '3.9.9.1' );
		}

		if( version_compare( '3.9.9.3', $ver, '>' ) ) {
			$this->license_activate();
			if ( $this->is_subscribed() ) {
				$this->wrap_update_option( 'data_retention_dur', 30 * 12 * 5 );
			}
			$this->wrap_update_option( 'anontrack', 0 );
			$this->wrap_update_option( 'cb_init_consent', 'yes' );
			$this->wrap_update_option( 'plugin_version', '3.9.9.3' );
		}

		if( version_compare( '4.0.1.0', $ver, '>' ) ) {
			$this->wrap_update_option( 'announce_friend_plan', true );
			$this->wrap_update_option( 'plugin_version', '4.0.1.0' );
		}

		if( version_compare( '4.1.0.0', $ver, '>' ) ) {
			//QAHM専用ユーザー権限を追加する
			add_role(
				'qahm-manager',
				__( 'QA Analytics Manager', 'qa-heatmap-analytics' ),
				array(
					'read' 					=> true,
					'qahm_manage_settings' 	=> true,
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
			$this->wrap_update_option( 'plugin_version', '4.1.0.0' );
		}
		

		// 最終的にプラグインバージョンを現行のものに変更
		$this->wrap_update_option( 'plugin_version', QAHM_PLUGIN_VERSION );

		// js取得用 ライセンス認証
		$this->license_activate();

		// メンテナンスファイルを削除
		$this->delete_maintenance_file();
	}


	/**
	 * ライセンス認証 js取得したり最新のアクティベート処理を強制的にかけたりするのに使う
	 */
	private function license_activate() {
		global $qahm_license;
		$license_plan = $this->wrap_get_option( 'license_plans' );
		if ( $license_plan ) {
			$key = $this->wrap_get_option( 'license_key' );
			$id  = $this->wrap_get_option( 'license_id' );
			$qahm_license->activate( $key, $id );
		}
	}


	/**
	 * メンテナンスファイルの削除
	 */
	private function delete_maintenance_file() {
		global $wp_filesystem;
		$maintenance_path = $this->get_temp_dir_path() . 'maintenance.php';
		if ( $wp_filesystem->exists( $maintenance_path ) ) {
			$wp_filesystem->delete( $maintenance_path );
			//mail
			$qahome_url = admin_url( 'admin.php?page=qahm-home' );
			$subject = esc_html__( 'QA Analytics updated completely', 'qa-heatmap-analytics' );
			$message = esc_html__( 'The QA Analytics system maintenance has been successfully completed. You can now access the dashboard and view the data.', 'qa-heatmap-analytics' );
			$message = $message . PHP_EOL . $qahome_url;
			$this->qa_mail( $subject, $message );
		}
	}
	
	/**
	 * データの変換テスト
	 */
	private function v1020_conv_readers_temp_data( $old_path, $new_path ) {
		$old_tsv = $this->wrap_get_contents( $old_path );
		$old_ary = $this->convert_tsv_to_array( $old_tsv );

		if ( isset( $old_ary['head']['version'] ) ) {
			return;
		}

		$new_ary = array();
		$new_ary['body'] = array();

		$new_ary['head']['version']        = 1;
		$new_ary['head']['tracking_id']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['TRACKING_ID']];
		$new_ary['head']['device_name']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['DEVICE_NAME']];
		$new_ary['head']['is_new_user']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['IS_NEW_USER']];
		$new_ary['head']['user_agent']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['USER_AGENT']];
		$new_ary['head']['first_referrer'] = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['FIRST_REFERRER']];
		$new_ary['head']['utm_source']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['UTM_SOURCE']];
		$new_ary['head']['utm_medium']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['UTM_MEDIUM']];
		$new_ary['head']['utm_campaign']   = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['UTM_CAMPAIGN']];
		$new_ary['head']['utm_term']       = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['UTM_TERM']];
		$new_ary['head']['original_id']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_TEMP_1['ORIGINAL_ID']];
		$new_ary['head']['country']        = '';

		for( $i = self::DATA_COLUMN_BODY; $i < count( $old_ary ); $i++ ) {
			$body = array();
			$body['page_url']    = $old_ary[$i][self::DATA_SESSION_TEMP_1['PAGE_URL']];
			$body['page_title']  = $old_ary[$i][self::DATA_SESSION_TEMP_1['PAGE_TITLE']];
			$body['page_type']   = $old_ary[$i][self::DATA_SESSION_TEMP_1['PAGE_TYPE']];
			$body['page_id']     = $old_ary[$i][self::DATA_SESSION_TEMP_1['PAGE_ID']];
			$body['access_time'] = $old_ary[$i][self::DATA_SESSION_TEMP_1['ACCESS_TIME']];
			$body['page_speed']  = $old_ary[$i][self::DATA_SESSION_TEMP_1['PAGE_SPEED']];
			array_push( $new_ary['body'], $body );
		}

		$this->wrap_put_contents( $new_path, $this->wrap_serialize( $new_ary ) );
	}
	
	private function v1020_conv_readers_finish_data( $old_path, $new_path ) {
		$old_tsv = $this->wrap_get_contents( $old_path );
		$old_ary = $this->convert_tsv_to_array( $old_tsv );

		if ( isset( $old_ary['head']['version'] ) ) {
			return;
		}

		$new_ary = array();
		$new_ary['body'] = array();

		$new_ary['head']['version']        = 1;
		$new_ary['head']['tracking_id']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['TRACKING_ID']];
		$new_ary['head']['device_name']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['DEVICE_NAME']];
		$new_ary['head']['is_new_user']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['IS_NEW_USER']];
		$new_ary['head']['user_agent']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['USER_AGENT']];
		$new_ary['head']['first_referrer'] = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['FIRST_REFERRER']];
		$new_ary['head']['utm_source']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['UTM_SOURCE']];
		$new_ary['head']['utm_medium']     = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['UTM_MEDIUM']];
		$new_ary['head']['utm_campaign']   = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['UTM_CAMPAIGN']];
		$new_ary['head']['utm_term']       = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['UTM_TERM']];
		$new_ary['head']['original_id']    = $old_ary[self::DATA_COLUMN_HEADER][self::DATA_SESSION_FINISH_1['ORIGINAL_ID']];
		$new_ary['head']['country']        = '';

		for( $i = self::DATA_COLUMN_BODY; $i < count( $old_ary ); $i++ ) {
			$body = array();
			$body['page_url']    = $old_ary[$i][self::DATA_SESSION_FINISH_1['PAGE_URL']];
			$body['page_title']  = $old_ary[$i][self::DATA_SESSION_FINISH_1['PAGE_TITLE']];
			$body['page_type']   = $old_ary[$i][self::DATA_SESSION_FINISH_1['PAGE_TYPE']];
			$body['page_id']     = $old_ary[$i][self::DATA_SESSION_FINISH_1['PAGE_ID']];
			$body['access_time'] = $old_ary[$i][self::DATA_SESSION_FINISH_1['ACCESS_TIME']];
			$body['page_speed']  = $old_ary[$i][self::DATA_SESSION_FINISH_1['PAGE_SPEED']];
			$body['sec_on_page'] = $old_ary[$i][self::DATA_SESSION_FINISH_1['TIME_ON_PAGE']];
			array_push( $new_ary['body'], $body );
		}

		$this->wrap_put_contents( $new_path, $this->wrap_serialize( $new_ary ) );
	}
	
	private function v1020_conv_realtime_view_data( $old_path, $new_path ) {
		global $wp_filesystem;
		$old_tsv = $wp_filesystem->get_contents( $old_path );
		$old_ary = $this->convert_tsv_to_array( $old_tsv );

		if ( isset( $old_ary['head']['version'] ) ) {
			return;
		}

		$new_ary = array();
		$new_ary['body'] = array();

		$new_ary['head']['version'] = 1;
	
		for( $i = self::DATA_COLUMN_BODY; $i < count( $old_ary ); $i++ ) {
			// 空行対策
			if ( ! $old_ary[$i][0] ) {
				break;
			}
			$body = array();
			$body['file_name']         = $old_ary[$i][self::DATA_REALTIME_VIEW_1['SESSION_FILE']];
			$body['tracking_id']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['TRACKING_ID']];
			$body['device_name']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['DEVICE_NAME']];
			$body['is_new_user']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['IS_NEW_USER']];
			$body['user_agent']        = $old_ary[$i][self::DATA_REALTIME_VIEW_1['USER_AGENT']];
			$body['first_referrer']    = $old_ary[$i][self::DATA_REALTIME_VIEW_1['FIRST_REFERRER']];
			$body['utm_source']        = $old_ary[$i][self::DATA_REALTIME_VIEW_1['UTM_SOURCE']];
			$body['utm_medium']        = $old_ary[$i][self::DATA_REALTIME_VIEW_1['UTM_MEDIUM']];
			$body['utm_campaign']      = $old_ary[$i][self::DATA_REALTIME_VIEW_1['UTM_CAMPAIGN']];
			$body['utm_term']          = $old_ary[$i][self::DATA_REALTIME_VIEW_1['UTM_TERM']];
			$body['original_id']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['ORIGINAL_ID']];
			$body['country']           = '';
			$body['first_access_time'] = $old_ary[$i][self::DATA_REALTIME_VIEW_1['FIRST_ACCESS_TIME']];
			$body['first_url']         = $old_ary[$i][self::DATA_REALTIME_VIEW_1['FIRST_URL']];
			$body['first_title']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['FIRST_TITLE']];
			$body['last_exit_time']    = $old_ary[$i][self::DATA_REALTIME_VIEW_1['LAST_EXIT_TIME']];
			$body['last_url']          = $old_ary[$i][self::DATA_REALTIME_VIEW_1['LAST_URL']];
			$body['last_title']        = $old_ary[$i][self::DATA_REALTIME_VIEW_1['LAST_TITLE']];
			$body['page_view']         = $old_ary[$i][self::DATA_REALTIME_VIEW_1['PV_NUM']];
			$body['sec_on_site']       = $old_ary[$i][self::DATA_REALTIME_VIEW_1['TIME_ON_SITE']];
			array_push( $new_ary['body'], $body );
		}

		$this->wrap_put_contents( $new_path, $this->wrap_serialize( $new_ary ) );
	}

}
