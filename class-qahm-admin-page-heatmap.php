<?php
/**
 *
 *
 * @package qa_heatmap
 */

$qahm_admin_page_heatmap = new QAHM_Admin_Page_Heatmap();

class QAHM_Admin_Page_Heatmap extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-heatmap';

	// nonce
	const NONCE_ACTION  = self::SLUG . '-nonce-action';
	const NONCE_NAME    = self::SLUG . '-nonce-name';
	const NONCE_REFRESH = 'refresh';

	private static $error_msg = array();
	private $license_plan;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();

		$this->regist_ajax_func( 'ajax_refresh_version' );
		$this->regist_ajax_func( 'ajax_create_heatmap_list' );
		$this->regist_ajax_func( 'ajax_set_achievements' );
		$this->regist_ajax_func( 'ajax_get_data_num' );
		$this->regist_ajax_func( 'ajax_create_unmeasurable_table' );

		add_action( 'init', array( $this, 'init_wp_filesystem' ) );
	}

	/**
	 * 初期化
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		if( ! $this->is_enqueue_jquery() ) {
			return;
		}

		if( $this->is_maintenance() ) {
			return;
		}

		if( $this->wrap_get_option( 'plugin_first_launch' ) ) {
			$this->common_enqueue_style();
			$this->common_enqueue_script();
			$scripts = $this->get_common_inline_script();
			wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );
			$localize = $this->get_common_localize_script();
			wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
			return;
		}

		$css_dir_url = $this->get_css_dir_url();
		$js_dir_url  = $this->get_js_dir_url();

		// enqueue_style
		$this->common_enqueue_style();
		wp_enqueue_style( QAHM_NAME . '-admin-page-heatmap', $css_dir_url . 'admin-page-heatmap.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );	
		wp_enqueue_style( QAHM_NAME . '-table', $css_dir_url . 'table.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );	
	
		// enqueue script
		$this->common_enqueue_script();
		wp_enqueue_script( QAHM_NAME . '-table', $js_dir_url . 'table.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-progress-bar', $js_dir_url . '/progress-bar-exec.js', null, QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-cap-create', $js_dir_url . 'cap-create.js', array( QAHM_NAME . '-admin-page-base' ), QAHM_PLUGIN_VERSION, false );
		wp_enqueue_script( QAHM_NAME . '-admin-page-heatmap', $js_dir_url . 'admin-page-heatmap.js', array( QAHM_NAME . '-admin-page-base' ), QAHM_PLUGIN_VERSION, false );
	
		// inline script
		$scripts = $this->get_common_inline_script();
		$scripts['achievements']             = $this->wrap_get_option( 'achievements' );
		$scripts['nonce_refresh']            = wp_create_nonce( self::NONCE_REFRESH );
		$scripts['is_first_heatmap_setting'] = $this->wrap_get_option( 'is_first_heatmap_setting' );
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		$localize['home']                             = esc_html__( 'Home', 'qa-heatmap-analytics' );
		$localize['post']                             = esc_html__( 'Post', 'qa-heatmap-analytics' );
		$localize['page']                             = esc_html__( 'Page', 'qa-heatmap-analytics' );
		$localize['cat']                              = esc_html__( 'Category', 'qa-heatmap-analytics' );
		$localize['tag']                              = esc_html__( 'Tag', 'qa-heatmap-analytics' );
		$localize['tax']                              = esc_html__( 'Custom taxonomy', 'qa-heatmap-analytics' );
		$localize['ver']                              = esc_html__( 'ver.', 'qa-heatmap-analytics' );
		$localize['limit_measure_title']              = esc_html__( 'About measurement', 'qa-heatmap-analytics' );
		//$localize['limit_measure_text']               = esc_html__( 'Measurable for heatmap is limited to %d page(s) with your QA.', 'qa-heatmap-analytics' ) . '<br>';
		//$localize['limit_free_text']                  = sprintf( esc_html__( '%1$s Upgrade %2$s to collect event data on more pages.', 'qa-heatmap-analytics' ), '<a href="https://quarka.org/en/#plans" target="_blank" rel="noopener">', '</a>' );
		//$localize['limit_friend_text']                = sprintf( esc_html__( '%1$s Get upgrade options %2$s.', 'qa-heatmap-analytics' ), '<a href="https://quarka.org/en/#plans" target="_blank" rel="noopener">', '</a>' );
		$localize['switch']                           = esc_html__( 'Updaded to', 'qa-heatmap-analytics' );
		$localize['version_switch']                   = esc_html__( 'Update Page Version', 'qa-heatmap-analytics' );
		$localize['version_switch_error_text']        = esc_html__( 'Failed to update the page version.', 'qa-heatmap-analytics' ) . '<br>' . esc_html__( 'Please try again aftertime.', 'qa-heatmap-analytics' );
		$localize['version_switch_start_text_1']      = esc_html__( 'Upon updating the page version, measurement will automatically begin with the new version.', 'qa-heatmap-analytics' );
		$localize['version_switch_start_text_2']      = esc_html__( 'Are you sure you want to proceed?', 'qa-heatmap-analytics' );
		$localize['version_switch_inte_title']        = esc_html__( 'Data is in the process of integration', 'qa-heatmap-analytics' );
		$localize['version_switch_inte_text']         = esc_html__( 'Sorry, updating the page version is prevented.<br>This page is being combined to the data by QA version 3.0. Please wait until the data gets ready.', 'qa-heatmap-analytics' );
		//$localize['achievements_data_num_1_title']    = esc_html__( 'Now on taking the first step!', 'qa-heatmap-analytics' );
		//$localize['achievements_data_num_1_text']     = esc_html__( 'Congratulations!', 'qa-heatmap-analytics' ) . '<br>' . esc_html__( 'Data has been collected for the first time.', 'qa-heatmap-analytics' );
		//$localize['achievements_data_num_1000_title'] = esc_html__( 'Improved analytical skills!', 'qa-heatmap-analytics' );
		//$localize['achievements_data_num_1000_text']  = esc_html__( 'In total, %d data have been collected in QA!', 'qa-heatmap-analytics' );
		$localize['measure_comment_text']             = esc_html__( 'You have reached the limit of measuable pages.', 'qa-heatmap-analytics' );
		$localize['page_title']                       = esc_html__( 'Page Title', 'qa-heatmap-analytics' );
		$localize['post_type']                        = esc_html__( 'Post Type', 'qa-heatmap-analytics' );
		wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );

		$this->license_plan = (int) $this->wrap_get_option( 'license_plans' );
	}


	/**
	 * ページの表示
	 */
	public function create_html() {
		if( ! $this->is_enqueue_jquery() ) {
			$this->view_not_enqueue_jquery_html();
			return;
		}

		if( $this->is_maintenance() ) {
			$this->view_maintenance_html();
			return;
		}

		if( $this->wrap_get_option( 'plugin_first_launch' ) ) {
			$this->view_first_launch_html();
			return;
		}

		// データを取得
		$heatmap_sort_view = $this->wrap_get_option( 'heatmap_sort_view' );

		$lang_set = get_bloginfo('language');
		if ( $lang_set == 'ja' ) {
			$upgrade_link_atag = '<a href="' . esc_url('https://quarka.org/plan/') . '" target="_blank" rel="noopener">';
			$referral_link_atag = '<a href="' . esc_url('https://quarka.org/referral-program/') . '" target="_blank" rel="noopener">';
		} else {
			$upgrade_link_atag = '<a href="' . esc_url('https://quarka.org/en/#plans') . '" target="_blank" rel="noopener">';
			$referral_link_atag = '<a href="' . esc_url('https://quarka.org/en/referral-program/') . '" target="_blank" rel="noopener">';
		}
		?>

		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1>QA <?php echo esc_html( __( 'Page Version Hub', 'qa-heatmap-analytics' ) );?></h1>
				<?php 
				$license_plan = $this->wrap_get_option( 'license_plans' );
				$is_subscribed = $this->is_subscribed();
				if ( ! $is_subscribed ) {
                    $msg_yuryouikaga = '<div class="qahm-using-free-announce"><span class="qahm_margin-right4"><span class="dashicons dashicons-megaphone"></span></span><span class="qahm_fontsize12em">';					
					$msg_yuryouikaga .= sprintf( 
						/* translators: placeholders are for the link */
						esc_html__( 'Upgrade for better insights! Gain more PV capacity for free by %1$sreferring friends%2$s, or choose our %3$sPremium Plan%4$s to increase PV limits and unlock more goals.', 'qa-heatmap-analytics' ), $referral_link_atag, '</a>', $upgrade_link_atag, '</a>'
					);
                    $msg_yuryouikaga .= '</span></div>';
                    echo wp_kses_post($msg_yuryouikaga);
				}
				?>

				<?php
					// メッセージ表示
					if ( ! empty( self::$error_msg ) ) {
						foreach ( self::$error_msg as $msg ) {
							echo '<div class="error notice is-dismissible"><p><strong>' . esc_html__( 'Error', 'qa-heatmap-analytics' ) . ' : ' . esc_html( $msg ) . '</strong></p></div>';
						}
					} elseif ( isset( $_POST[ self::NONCE_NAME ] ) && check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME ) ) {
						echo '<div class="updated notice is-dismissible"><p><strong>' . esc_html__( 'Saved settings.', 'qa-heatmap-analytics' ) . '</strong></p></div>';
					}
				?>

				<p>
					<?php
						echo esc_html( __( 'Manage the versions of your pages and posts from this interface.', 'qa-heatmap-analytics' ) );
						echo '<br>';
						esc_html_e( 'Use the search bar to quickly find specific pages or posts by title. Click the "Update Page Version" icon to update the page version.', 'qa-heatmap-analytics' );
					?>
				</p>
				<p>
					<?php
						/* translators: placeholders refer to the report home page */
						echo ( sprintf( esc_html__( 'For comprehensive analytics, including heatmap and session replay data, for your entire website, access the %1$sHome Report%2$s.', 'qa-heatmap-analytics' ), '<a href="./admin.php?page=qahm-home">', '</a>' ) );
					?>
				</p>
				<div class="page-list-sec">
					<p><?php esc_html_e( 'Input the title to find matching pages and posts, then click "Find".', 'qa-heatmap-analytics' ); ?></p>
					<form action="" method="post" id="qahm-data-sort-form" onsubmit="qahm.createList(); return false;">
						<div class="tablenav top">
							<div class="alignleft">
								<input type="text" id="qahm-data-search-title" placeholder="<?php esc_attr_e( 'Page Title', 'qa-heatmap-analytics' ); ?>" class="regular-text">
							</div>

							<select id="qahm-data-search-limit">
								<option value="10"
									<?php
									if ( $heatmap_sort_view === '10' ) {
										echo esc_attr( ' selected' );
									}
									?>
								>
									<?php 
									/* translators: placeholders refer to the number of items to show on the list */
									printf( esc_html__( 'Show %d', 'qa-heatmap-analytics' ), 10 ); 
									?>
								</option>

								<option value="25"
									<?php
									if ( $heatmap_sort_view === '25' ) {
										echo esc_attr( ' selected' );
									}
									?>
								>
									<?php 
									/* translators: placeholders refer to the number of items to show on the list */
									printf( esc_html__( 'Show %d', 'qa-heatmap-analytics' ), 25 ); 
									?>
								</option>

								<option value="50"
									<?php
									if ( $heatmap_sort_view === '50' ) {
										echo esc_attr( ' selected' );
									}
									?>
								>
									<?php 
									/* translators: placeholders refer to the number of items to show on the list */
									printf( esc_html__( 'Show %d', 'qa-heatmap-analytics' ), 50 ); 
									?>
								</option>
							</select>

							<input type="submit" id="qahm-data-sort-button" class="button" value="<?php esc_attr_e( 'Find', 'qa-heatmap-analytics' ); ?>">
							<br class="clear">
						</div>
					</form>

					<form action="" method="post" id="dataForm" name="dataForm">
						<?php // ②：nonceの設定 ?>
						<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
						
						<table id="qahm-data-list" class="widefat striped">
							<thead>
								<tr>
									<th scope="col">
										<?php esc_html_e( 'Page Title', 'qa-heatmap-analytics' ); ?>
									</th>
									<th scope="col">
										<?php esc_html_e( 'Type', 'qa-heatmap-analytics' ); ?>
									</th>
									<th scope="col">
										<?php echo esc_html( __( 'Last Update', 'qa-heatmap-analytics' ) ); ?>
									</th>
									<th scope="col">
										<?php esc_html_e( 'Update Page Version', 'qa-heatmap-analytics' ); ?>
										<span class="qahm-tooltip" data-qahm-tooltip="<?php echo(
											esc_attr__( 'Save current page version.', 'qa-heatmap-analytics' ) . ' ' .
											esc_attr__( 'Click this icon after making updates to the page (post) or editing the HTML code.', 'qa-heatmap-analytics' )
											); ?>">
											<i class="far fa-question-circle"></i>
										</span>
									</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</form>
				</div>

				<p style="margin-top: 32px;"><?php esc_html_e( 'Please note there may be cases where custom post types specific to your theme are not displayed.', 'qa-heatmap-analytics' ); ?><br>
				<?php esc_html_e( 'An exciting new feature named "Automatic Page Version Updating," which will seamlessly integrate with your theme-specific custom post types as part of our premium plan, is currently in progress and will be available in the future.', 'qa-heatmap-analytics' ); ?></p>
			</div>
		</div>
		<style>
			.page-list-sec {
				background-color: #fafafa;
    			padding: 12px;
			}
			.page-list-sec p {
				margin-bottom: 0;
			}
		</style>
		<?php
	}

	/**
	 * QAHMで使いやすい形のリストを作成
	 */
	public function create_heatmap_list( $search_title, $search_limit ) {
		global $qahm_db;
		$list = array();

		$table_name = $qahm_db->prefix . 'posts';
		$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
		$where    = " WHERE post_status = 'publish' AND post_type IN ('" . implode( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "') AND post_title LIKE '%" . esc_sql( $search_title ) . "%'";
		$order    = ' ORDER BY post_modified DESC';
		$limit    = ' LIMIT ' . $search_limit;
		$my_query    = 'SELECT ID,post_title,post_type,post_modified FROM ' . $table_name . $where . $order . $limit;
		$post_ary = $qahm_db->get_results( $my_query, ARRAY_A );

		$add_post_type = $this->wrap_get_option( 'add_post_type' );
		if ( $add_post_type ) {
			$in_search_post_types = $this->wrap_unserialize( $add_post_type );
			$where    = " WHERE post_status = 'publish' AND post_type IN ('" . implode( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "') AND post_title LIKE '%" . esc_sql( $search_title ) . "%'";
			$order    = ' ORDER BY post_modified DESC';
			$limit    = ' LIMIT ' . $search_limit;
			$my_query    = 'SELECT ID,post_title,post_type FROM ' . $table_name . $where . $order;		$add_post_ary = $qahm_db->get_results( $my_query, ARRAY_A );
			$post_ary = array_merge( $post_ary, $add_post_ary );
		}


		foreach ( $post_ary as $post ) {
			switch ( $post['post_type'] ) {
				case 'post':
					$type = 'p';
					break;
				case 'page':
					$type = 'page_id';
					break;
				// default: $type = 'カスタム投稿'; break;
				default:
					$type = 'p';
					break;
			}
			$list[] = array(
				'url'           => esc_url( get_permalink( $post['ID'] ) ),
				'title'         => esc_html( $post['post_title'] ),
				'id'            => (int) $post['ID'],
				'type'          => $type,
				'post_modified' => esc_html( $post['post_modified'] ),
				'change'        => false,
			);
		}

		$update_limit = $search_limit - count( $list );
		if ( $update_limit <= 0 ) {
			return $list;
		}

		// カテゴリ、タグ、カスタムタクソノミー
		$terms = get_terms( array( 'get' => 'all' ) );
		$table_name_term = $qahm_db->prefix . 'terms';
		$table_name_tax  = $qahm_db->prefix . 'term_taxonomy';
		$where    = " WHERE t.name LIKE '%" . esc_sql( $search_title ) . "%'";
		$order    = ' ORDER BY t.name ASC';
		$limit    = ' LIMIT ' . $update_limit;
		$my_query    = 'SELECT t.term_id,t.name,tt.taxonomy FROM ' . $table_name_term . ' AS t INNER JOIN ' . $table_name_tax . ' AS tt ON t.term_id = tt.term_id' . $where . $order . $limit;
		$term_ary = $qahm_db->get_results( $my_query, ARRAY_A );

		foreach ( $term_ary as $term ) {
			switch ( $term['taxonomy'] ) {
				case 'category':
					$type = 'cat';
					break;
				case 'post_tag':
					$type = 'tag';
					break;
				default:
					$type = 'tax';
					break;
			}
			$list[] = array(
				'url'           => esc_url( get_term_link( (int) $term['term_id'] ) ),
				'title'         => esc_html( $term['name'] ),
				'id'            => (int) $term['term_id'],
				'type'          => $type,
				'post_modified' => esc_html( $post['post_modified'] ),
				'change'        => false,
			);
		}

		$update_limit = $search_limit - count( $list );
		if ( $update_limit <= 0 ) {
			return $list;
		}

		// ホーム
		if ( $search_title === '' || strpos( esc_html__( 'Home', 'qa-heatmap-analytics' ), $search_title ) !== false ) {
			$list[] = array(
				'url'           => esc_url( get_home_url() ),
				'title'         => esc_html__( 'Home', 'qa-heatmap-analytics' ),
				'id'            => 1,
				'type'          => 'home',
				'post_modified' => '',
				'change'        => false,
			);
		}

		return $list;
	}

	/**
	 * テーブルの存在確認
	 * 他でも使うようならqahm baseに持っていっても良い
	 */
	private function exists_database_table( $table_name ) {
		global $wpdb;
		//$my_prepare = $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $wpdb->esc_like( $table_name ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This query will not be called frequently, so caching is not necessary.
		$exist_table_name = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $wpdb->esc_like( $table_name ) ) );
		if ( $exist_table_name === $table_name ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * 計測中の情報をリストに付与
	 */
	public function add_refresh_info( $heatmap_list ) {
		$cache_dir_path = $this->get_data_dir_path() . 'cache/';
		if ( ! $this->wrap_exists( $cache_dir_path ) ) {
			$this->wrap_mkdir( $cache_dir_path );
		}

		$refresh_path = $cache_dir_path . 'heatmap_list_refresh.php';
		if ( $this->wrap_exists( $refresh_path ) ) {
			$refresh_list = $this->wrap_unserialize( $this->wrap_get_contents( $refresh_path ) );

			foreach ( $refresh_list as $refresh ) {
				foreach( $heatmap_list as &$heatmap ) {
					if( $heatmap['url'] === $refresh['url'] ) {
						$heatmap['version_refresh'] = $refresh['version_no'];
						break;
					}
				}
			}

		}

		return $heatmap_list;
	}

	/**
	 * 計測中情報を削除
	 */
	public function delete_refresh_info() {
		$cache_dir_path = $this->get_data_dir_path() . 'cache/';
		if ( ! $this->wrap_exists( $cache_dir_path ) ) {
			$this->wrap_mkdir( $cache_dir_path );
		}

		$refresh_path = $cache_dir_path . 'heatmap_list_refresh.php';
		$this->wrap_delete( $refresh_path );
	}

	/**
	 * ヒートマップリストを取得
	 */
	public function ajax_create_heatmap_list() {
		global $wp_filesystem;

		$search_title = $this->wrap_filter_input( INPUT_POST, 'search_title' );
		$search_limit = $this->wrap_filter_input( INPUT_POST, 'search_limit' );

		$list = $this->create_heatmap_list( $search_title, $search_limit );
		$list = $this->add_refresh_info( $list );

		echo wp_json_encode( $list );
		die();
	}

	/**
	 * バージョン切り替え
	 */
	public function ajax_refresh_version() {
		$nonce = $this->wrap_filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_REFRESH ) ) {
			http_response_code( 400 );
			die( 'wp_verify_nonce error' ); 
		}

		global $wp_filesystem;
		global $wpdb;
		global $qahm_time;
		global $qahm_db;
		$today_str  = $qahm_time->today_str();
		$now_str    = $qahm_time->now_str();

		$wp_qa_type = $this->wrap_filter_input( INPUT_POST, 'wp_qa_type' );
		$wp_qa_id   = (int) $this->wrap_filter_input( INPUT_POST, 'wp_qa_id' );
		$url        = $this->wrap_filter_input( INPUT_POST, 'url' );
		$title      = $this->wrap_filter_input( INPUT_POST, 'title' );
		$page_id    = null;

		// page_idを求める。wp_qa_idとwp_qa_typeで検索をかけ、最大値を取得
		$table_name   = $qahm_db->prefix . 'qa_pages';
		$my_query        = 'SELECT page_id,url FROM ' . $table_name . ' WHERE wp_qa_id = %d AND wp_qa_type = %s';
		$qa_pages_ary = $qahm_db->get_results( $qahm_db->prepare( $my_query, $wp_qa_id, $wp_qa_type ), ARRAY_A );
		if( $qa_pages_ary ) {
			$table_name = $qahm_db->prefix . 'view_pv';
			$my_query      = 'SELECT count(*) FROM ' . $table_name . ' WHERE page_id = %d';
			$pv_max_idx = 0;
			$pv_max_num = 0;
			for ( $qa_pages_idx = 0, $qa_pages_max = count( $qa_pages_ary ); $qa_pages_idx < $qa_pages_max; $qa_pages_idx++ ) {
				$count = $qahm_db->get_results( $qahm_db->prepare( $my_query, $qa_pages_ary[$qa_pages_idx]['page_id'] ), ARRAY_A );
				if ( $pv_max_num < $count ) {
					$pv_max_idx = $qa_pages_idx;
					$pv_max_num = $count;
				}
			}
			$qa_pages = $qa_pages_ary[$pv_max_idx];
			$page_id  = (int) $qa_pages['page_id'];
		}

		if ( empty( $page_id ) ) {
			// qa_pages insert
			$table_name = $wpdb->prefix . 'qa_pages';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using $wpdb->insert() is necessary here for direct data manipulation as it fits the specific requirements of this operation.
			$wpdb->insert( 
				$table_name, 
				array( 
					'tracking_id' => $this->get_tracking_id(),
					'wp_qa_type'  => $wp_qa_type, 
					'wp_qa_id'    => $wp_qa_id,
					'url'         => $url,
					'url_hash'    => hash( 'fnv164', $url ),
					'title'       => $title,
					'update_date' => $today_str
				), 
				array( 
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s'
				) 
			);
			$page_id = $wpdb->insert_id;
		}

		$ver_hist_bulk_ary         = array();
		$ver_hist_place_holder_ary = array();

		// 最新バージョンを調べる。存在しなければバージョン1作成
		// 存在していれば全デバイスそのバージョンを作成＆新規バージョン追加
		$table_name           = $wpdb->prefix . 'qa_page_version_hist';
		//$my_query                = 'SELECT version_id,device_id,version_no FROM ' . $table_name . ' WHERE page_id=%d and version_no=(select max(version_no) FROM ' . $table_name . ' WHERE page_id=%d)';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->get_results() is necessary for retrieving data efficiently in this context, and it's not feasible to use the standard API methods for this specific query. This query will not be called frequently, so caching is not necessary.
		$qa_page_version_hist = $wpdb->get_results( $wpdb->prepare( 'SELECT version_id,device_id,version_no FROM ' . esc_sql($table_name) . ' WHERE page_id=%d and version_no=(select max(version_no) FROM ' . esc_sql($table_name) . ' WHERE page_id=%d)', $page_id, $page_id ) );

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
			
			// デバイスによるユーザーエージェント指定
			$response = $this->wrap_remote_get( $url, $dev_name );
			if ( is_wp_error( $response ) ) {
				http_response_code( 400 );
				die( 'wp_remote_get is wp error' ); 
			} elseif( $response['response']['code'] !== 200 ) {
				http_response_code( 400 );
				die( 'wp_remote_get status code is other than 200' ); 
			} else {
				$base_html = $response['body'];
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
					$ver_hist_bulk_ary[]         = $page_id;
					$ver_hist_bulk_ary[]         = $qahm_dev['id'];
					$ver_hist_bulk_ary[]         = $cur_ver_no;
					$ver_hist_bulk_ary[]         = $base_html;
					$ver_hist_bulk_ary[]         = $today_str;
					$ver_hist_bulk_ary[]         = $now_str;
					$ver_hist_place_holder_ary[] = '(%d, %d, %d, %s, %s, %s)';
				}
			}

			// バージョン追加
			$ver_hist_bulk_ary[] = $page_id;
			$ver_hist_bulk_ary[] = $qahm_dev['id'];
			$ver_hist_bulk_ary[] = $cur_ver_no + 1;
			$ver_hist_bulk_ary[] = $base_html;
			$ver_hist_bulk_ary[] = $today_str;
			$ver_hist_bulk_ary[] = $now_str;
			$ver_hist_place_holder_ary[] = '(%d, %d, %d, %s, %s, %s)';
		}

		// バルクインサート
		//$my_query = 'INSERT INTO ' . $table_name . ' ' .
		//		'(page_id, device_id, version_no, base_html, update_date, insert_datetime) ' .
		//		'VALUES ' . join( ',', $ver_hist_place_holder_ary ) . ' ' .
		//		'ON DUPLICATE KEY UPDATE ' .
		//		'page_id = VALUES(page_id), ' .
		//		'device_id = VALUES(device_id), ' .
		//		'version_no = VALUES(version_no), ' .
		//		'base_html = VALUES(base_html), ' .
		//		'update_date = VALUES(update_date), ' .
		//		'insert_datetime = VALUES(insert_datetime)';
		$verhist_placeholders = join( ',', $ver_hist_place_holder_ary );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->query() is essential for executing this specific SQL command that cannot be efficiently managed by standard API methods. This query will not be called frequently, so caching is not necessary.
		$data['result']  = $wpdb->query( $wpdb->prepare( 
			'INSERT INTO ' . esc_sql($table_name) . ' ' .
				'(page_id, device_id, version_no, base_html, update_date, insert_datetime) ' .
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- using $wpdb->prepare and manually made placeholders
				'VALUES ' . $verhist_placeholders . ' ' .
				'ON DUPLICATE KEY UPDATE ' .
				'page_id = VALUES(page_id), ' .
				'device_id = VALUES(device_id), ' .
				'version_no = VALUES(version_no), ' .
				'base_html = VALUES(base_html), ' .
				'update_date = VALUES(update_date), ' .
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholders are stored in $verhist_placeholders
				'insert_datetime = VALUES(insert_datetime)',
			$ver_hist_bulk_ary
		) );
		$data['version'] = $cur_ver_no + 1;
		/* translators: placeholders represent the page version number */
		$data['version_switch_success_text'] = sprintf( esc_html__( 'The page version has been successfully updated to version %d.', 'qa-heatmap-analytics' ), $data['version'] );
		$data['version_switch_success_text'] .=  '<br>';
		$data['version_switch_success_text'] .= esc_html__( 'The updated data will be available tomorrow or later.', 'qa-heatmap-analytics' );

		
		// その日のバージョンアップファイルを作成
		$cache_dir_path = $this->get_data_dir_path() . 'cache/';
		if ( ! $wp_filesystem->exists( $cache_dir_path ) ) {
			$wp_filesystem->mkdir( $cache_dir_path );
		}

		$refresh_path = $cache_dir_path . 'heatmap_list_refresh.php';
		$find  = false;
		$index = 0;
		if ( $wp_filesystem->exists( $refresh_path ) ) {
			$refresh_list = $this->wrap_unserialize( $this->wrap_get_contents( $refresh_path ) );

			foreach ( $refresh_list as &$refresh ) {
				if( $refresh['page_id'] === $page_id ) {
					$refresh['version_no'] = $data['version'];
					$find                = true;
					break;
				}
				$index++;
			}

		} else {
			$refresh_list = array();
		}

		if ( ! $find ) {
			$refresh_list[ $index ] = array(
				'page_id'    => $page_id,
				'version_no' => $data['version'],
				'url'        => $url
			);
		}

		$this->wrap_put_contents( $refresh_path, $this->wrap_serialize( $refresh_list ) );

		echo wp_json_encode( $data );
		die();
	}

	/**
	 * 実績の設定
	 */
	public function ajax_set_achievements() {
		$achievements = json_decode( $this->wrap_filter_input( INPUT_POST, 'achievements' ), true );
		$this->wrap_update_option( 'achievements', $achievements );
	}


	/**
	 * 計測不可となっている投稿タイプの記事一覧配列を作成
	 */
	public function ajax_create_unmeasurable_table() {
		global $qahm_db;

		// wp_postsから記事一覧を取得
		$table_name = $qahm_db->prefix . 'posts';
		$my_query      = 'SELECT ID, post_title, post_type FROM ' . $table_name . ' WHERE post_status = "publish"';
		$wp_posts   = $qahm_db->get_results( $my_query );
		
		// リストファイル読み込み
		$cache_dir_path    = $this->get_data_dir_path() . 'cache/';
		$list_path = $cache_dir_path . 'heatmap_list.php';
		$list_ary  = null;
		if ( $this->wrap_exists( $list_path ) ) {
			$list_ary  = $this->wrap_unserialize( $this->wrap_get_contents( $list_path ) );
		} else {
			echo wp_json_encode( null );
			die();
		}

		/*
		// 除外対象の投稿タイプ配列を作成
		$exclude_type_ary = array();
		foreach ( $list_ary as $list_val ) {
			if ( ! in_array( $list_val['type'], $exclude_type_ary ) ) {
				$exclude_type_ary[] = $list_val['type'];
			}
		}

		$post_ary = array();
		foreach ( $wp_posts as $wp_post ) {
			//if ( in_array( $wp_post->post_type, $exclude_type_ary ) ) {
			//	continue;
			//}
			$exclude_post_find = false;
			foreach ( $list_ary as $list_val ) {
				if ( $wp_post->post_title === $list_val['title'] || in_array( $wp_post->post_type, $exclude_type_ary ) ) {
					$exclude_post_find = true;
					break;
				}
			}
			if ( $exclude_post_find ) {
				continue;
			}

			$post_ary[] = array(
				'post_url'   => get_permalink( $wp_post->ID ),
				'post_title' => $wp_post->post_title,
				'post_type'  => $wp_post->post_type,
			);
		}
		*/

		// 除外対象の投稿タイプ配列を作成
		// ヒートマップリストのキャッシュでは元々の投稿タイプの情報が抜け落ちているので、ここではget_postsで除外する投稿タイプを取得する
		$exclude_type_ary = array(
			'post',
			'page',
			'attachment',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
		);
		/*
		$exclude_post_ary = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => 'any',
			)
		);*/
		
		$table_name = $qahm_db->prefix . 'posts';
		$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
		$where = " WHERE post_status = 'publish' AND post_type IN ('" . implode( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "')";
		$order = ' ORDER BY post_date DESC';
		$my_query = 'SELECT ID,post_title,post_type FROM ' . $table_name . $where . $order;
		$exclude_post_ary = $qahm_db->get_results( $my_query, ARRAY_A );

		foreach ( $exclude_post_ary as $exclude_post ) {
			if ( ! in_array( $exclude_post['post_type'], $exclude_type_ary ) ) {
				$exclude_type_ary[] = $exclude_post['post_type'];
			}
		}

		$post_ary = array();
		foreach ( $wp_posts as $wp_post ) {
			// 除外対象か確認
			if ( in_array( $wp_post->post_type, $exclude_type_ary ) || $wp_post->post_title === '' ) {
				continue;
			}

			// ヒートマップ記事一覧と被りのタイトルがあるか確認
			$exclude_find = false;
			foreach ( $list_ary as $list_val ) {
				if ( $wp_post->post_title === $list_val['title'] ) {
					$exclude_find = true;
					break;
				}
			}
			if ( $exclude_find ) {
				continue;
			}

			// 除外対象じゃない場合、計測不可リストに追加
			$post_ary[] = array(
				'post_url'   => get_permalink( $wp_post->ID ),
				'post_title' => esc_html( $wp_post->post_title ),
				'post_type'  => esc_html( $wp_post->post_type ),
			);
		}
		if ( ! $post_ary ) {
			echo wp_json_encode( null );
			die();
		}

		echo wp_json_encode( $post_ary );
		die();
	}

} // end of class
