<?php
/**
 *
 *
 * @package qa_heatmap
 */

new QAHM_Article_Post();

class QAHM_Article_Post extends QAHM_File_Base {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'admin_head', array( $this, 'update_version' ) ); // save_postでキャプチャフラグを立てた場合に実行

		//ページバージョン更新用の　meta boxes
		add_action( 'add_meta_boxes', array($this, 'qahm_add_custom_box'), 10, 2 );
		add_action( 'save_post', array($this, 'qahm_save_postmeta'), 10, 3 );
		add_action( 'updated_postmeta', array($this, 'qahm_save_pageversion'), 10, 4 );
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_meta_styles') );
	}


	/**
	 *
	 */
	public function save_post( $post_id, $post, $update ) {
		// 自動保存対策
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// ゴミ箱に入れた時に更新されないよう対策
		if ( empty( $post ) ) {
			return;
		}
		// 記事新規作成画面を開いたときなどに、実行されないようにする対策
		if ( ! $update ) {
			return;
		}

		$type = 'p';
		if ( $post->post_type === 'page' ) {
			$type = 'page_id';
		}

		// $post->guidはパーマリンクの設定とは違うURLが取得されるためコメントアウト

		// 今の仕様では自動バージョンアップ処理を入れない。よってこの部分をコメントアウトしている
		// ※後々実装予定
		/*
		$this->wrap_update_option(
			'post_update',
			array(
				'type'  => $type,
				'id'    => $post_id,
				//'url'   => $post->guid,
				'title' => $post->post_title
			)
		);
		*/

		// save_postのアクションフックは多重実行される可能性があるためremoveする
		remove_action( 'save_post', array( $this, 'update' ) );
	}


	/**
	 * 
	 */
	public function update_version() {
		$post_update = $this->wrap_get_option( 'post_update' );
		if ( ! $post_update ) {
			return;
		}
		$this->wrap_update_option( 'post_update', '' );

		global $wpdb;
		global $qahm_time;

		$type      = $post_update['type'];
		$id        = $post_update['id'];
		$url       = $this->get_base_url( $type, $id );
		$title     = $post_update['title'];
		$today_str = $qahm_time->today_str();
		$now_str   = $qahm_time->now_str();

		// db更新
		$table_name = $wpdb->prefix . 'qa_pages';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
		$qa_pages   = $wpdb->get_results( $wpdb->prepare( "SELECT page_id FROM " . esc_sql($table_name) . " WHERE url_hash = %s", hash( 'fnv164', $url ) ) );

		// qa_pagesがこの時点で存在しなければINSERT
		// インサートはひとつずつループで行うと失敗する可能性があるので、バルクで行うよう処理を変更する必要あり
		$page_id = null;
		if ( $qa_pages ) {
			// url_hashで検索しているのでこの方法で良い
			$page_id = (int) $qa_pages[0]->page_id;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct database call is necessary in this case due to the complexity of the SQL query.
			$wpdb->insert(
				$table_name,
				array(
					'tracking_id' => $this->get_tracking_id(),
					'wp_qa_type'  => $type,
					'wp_qa_id'    => $id,
					'url'         => $url,
					'url_hash'    => hash( 'fnv164', $url ),
					'title'       => $title,
					'update_date' => $today_str,
				),
				array(
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			$page_id = $wpdb->insert_id;
		}

		if ( $page_id ) {
			// 最新バージョンを調べる。存在しなければバージョン1作成
			// 存在していれば全デバイスそのバージョンを作成＆新規バージョン追加
			$table_name = $wpdb->prefix . 'qa_page_version_hist';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct database call is necessary in this case due to the complexity of the SQL query. Caching would not provide significant performance benefits in this context.
			$qa_page_version_hist = $wpdb->get_results(
					$wpdb->prepare( "
						SELECT version_id, device_id, version_no
						FROM " . esc_sql( $table_name ) . "
						WHERE page_id = %d
						AND version_no = (
							SELECT MAX(version_no)
							FROM " . esc_sql( $table_name ) . "
							WHERE page_id = %d
						)
					",
					$page_id,
					$page_id
					)
				);

			$dev_id_ary = array();
			$cur_ver_no = 0;

			if ( $qa_page_version_hist ) {
				foreach ( $qa_page_version_hist as $hist ) {
					$dev_id_ary[] = $hist->device_id;
					$cur_ver_no   = $hist->version_no;
				}
			}

			// 今のバージョンは全デバイス分データを作成
			foreach ( QAHM_DEVICES as $qahm_dev ) {
				$dev_name  = $this->device_id_to_device_name( $qahm_dev['id'] );
				$response = $this->wrap_remote_get( $url, $dev_name );
				if ( is_wp_error( $response ) ) {
					continue;
				} elseif( $response['response']['code'] !== 200 ) {
					continue;
				} else {
					$base_html = $response['body'];
				}

                if ( $this->is_zip( $base_html ) ) {
                    $temphtml = gzdecode( $base_html );
                    if ( $temphtml !== false ) {
                        $base_html = $temphtml;
                    }
                }
				if ( $qa_page_version_hist ) {
					$find = false;
					if ( $dev_id_ary !== array() ) {
						foreach ( $dev_id_ary as $dev_id ) {
							if ( $qahm_dev['id'] === (int) $dev_id ) {
								$find = true;
								break;
							}
						}
					}

					// 現行バージョンで作られていないデバイスのversion histを作成
					if ( ! $find ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct database call is necessary in this case due to the complexity of the SQL query.
							$wpdb->insert(
							$table_name,
							array(
								'page_id'         => $page_id,
								'device_id'       => $qahm_dev['id'],
								'version_no'      => $cur_ver_no,
								'base_html'       => $base_html,
								'update_date'     => $today_str,
								'insert_datetime' => $now_str,
							),
							array(
								'%d',
								'%d',
								'%d',
								'%s',
								'%s',
								'%s',
							)
						);
					}
				}

				// バージョン追加
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct database call is necessary in this case due to the complexity of the SQL query.
				$wpdb->insert(
					$table_name,
					array(
						'page_id'         => $page_id,
						'device_id'       => $qahm_dev['id'],
						'version_no'      => $cur_ver_no + 1,
						'base_html'       => $base_html,
						'update_date'     => $today_str,
						'insert_datetime' => $now_str,
					),
					array(
						'%d',
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
					)
				);
			}
		}
	}


/**
 * 投稿画面にメタボックスを表示
 */
	public function enqueue_meta_styles( $hook ){
		//投稿画面以外は読み込まない
		if ( 'post.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( QAHM_NAME . '-meta-boxes', plugins_url( 'css/meta-boxes.css', __FILE__ ), array(), QAHM_PLUGIN_VERSION );
	}

	public function qahm_add_custom_box( $post_type, $post ) {
		$avoid_types = array( 'attachment', 'revision', 'nav_menu_item');
		$use_metabox = false;
		if ( apply_filters( 'replace_editor', false, $post ) !== true ) {
			if ( function_exists('use_block_editor_for_post') ) {
				if ( use_block_editor_for_post( $post ) ) {
					// block editor
					$use_metabox = true;
				} else {
					// classic editor
				}
			}
		}

		if ( $use_metabox ) {
			if ( ( ! in_array($post_type, $avoid_types) ) && $post->post_status == 'publish' ) {
					add_meta_box(
							'qahm_metabox',              // Unique ID
							'QA Analytics',      // Box title
							array($this, 'qahm_custom_metabox_html'),  // Content callback, must be of type callable
							$post_type                // Post type
							//'side',									// Context -Optional.(normal, advanced, or side). Default: advanced.
							//'high'											// Priority -Optional.(high, core, default, or low). Default: default.
						);
			}
		}
	}

	//*Outputs the content of the meta box
	public function qahm_custom_metabox_html( $post ) {
		$img_dir_url = plugin_dir_url(__FILE__) . 'img/';
		$qahm_q_logo = $img_dir_url . 'qa-q-logo.png';

		wp_nonce_field( basename( __FILE__ ), 'qahm_meta_nonce' );
    //$qahm_stored_meta = get_post_meta( $post->ID, '_qahm_meta_key', true  );
    ?>
		<div class="qahm_meta-box">
			<div class="qahm-meta-icon">
				<div class="qahm-meta-q-logo"><img src="<?php echo esc_url( $qahm_q_logo ); ?>" alt="qa-logo"></div>
				<?php //echo $svg_icon; ?>
			</div>

			<div class="qahm-meta-contents">
				<p class="qahm-meta-checkp"><input type="checkbox" id="qahm_meta-save_pagever" name="qahm_meta-save_pagever" value="yes" <?php //if($qahm_stored_meta =='yes'){echo ' checked';} ?>><label for="qahm_meta-save_pagever"><?php esc_html_e( 'Switch this pageversion of QA Heatmap after update', 'qa-heatmap-analytics' ); ?></label></p>
				<p><?php esc_html_e( '*Changing the contents of a post means changing how its page looks. Reflect it to the Heatmap!', 'qa-heatmap-analytics' ); ?><br>
				( <?php esc_html_e( 'QA User Guide', 'qa-heatmap-analytics' ); ?> : <a href="https://mem.quarka.org/en/manual/whats-pageversion/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'What is Page Version?', 'qa-heatmap-analytics' ); ?></a> )</p>

			</div>
		</div>
		<?php
	}

	//* Saves the custom meta input
	public function qahm_save_postmeta( $post_id, $post, $update ) {
		// Checks save status
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST['qahm_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qahm_meta_nonce'] ) ), basename( __FILE__ ) ) ) ? 'true' : 'false';

		// Exits script depending on save status
		if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
			return;
		}

		// Checks post status
		$post_status = get_post_status( $post_id );
		if ( $post_status == 'publish' && $update == true ) {
			// Checks for input and saves if needed
			if ( isset( $_POST['qahm_meta-save_pagever'] ) ) {
				update_post_meta(
					$post_id,
					'_qahm_meta_key',
					sanitize_text_field( wp_unslash( $_POST['qahm_meta-save_pagever'] ) )
				);
			} else {
				update_post_meta(
					$post_id,
					'_qahm_meta_key',
					''
				);
			}
		}
	}

	//*Save the page-version for QA (when meta updated)
	public function qahm_save_pageversion( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key === '_qahm_meta_key' ) {
			if( $meta_value =='yes' ) {
				//ここでページバージョンを保存する
				$post = get_post($object_id);
				$type = 'p';
				if ( $post->post_type === 'page' ) {
					$type = 'page_id';
				}
				$this->wrap_update_option(
					'post_update',
					array(
						'type'  => $type,
						'id'    => $post->ID,
						'title' => $post->post_title
					)
				);
				$this->update_version();
				delete_post_meta(	$post->ID, '_qahm_meta_key', '' );
			}
		}
	}


} //end of class
