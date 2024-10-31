<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_admin_page_license = new QAHM_Admin_Page_License();

class QAHM_Admin_Page_License extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-license';

	// nonce
	const NONCE_ACTION = self::SLUG . '-nonce-action';
	const NONCE_NAME   = self::SLUG . '-nonce-name';

	private static $error_msg = array();

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_init', array( $this, 'save_config' ) );
		$this->regist_ajax_func( 'ajax_clear_license_message' );
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

		// enqueue_style
		$this->common_enqueue_style();

		// enqueue script
		$this->common_enqueue_script();
		wp_enqueue_script( QAHM_NAME . '-admin-page-license', plugins_url( 'js/admin-page-license.js', __FILE__ ), array( QAHM_NAME . '-effect' ), QAHM_PLUGIN_VERSION, false );

		// inline script
		$scripts = $this->get_common_inline_script();
		// 紙吹雪エフェクト。エフェクト実行後はajaxでupdateして紙吹雪フラグをオフにするのが適切だが、そこにかける時間ももったいないのでここでupdateしている
		$msg_ary = $this->wrap_get_option( 'license_message' );
		if ( ! empty( $msg_ary[0]['confetti'] ) ) {
			$scripts['license_confetti'] = $msg_ary[0]['confetti'];
			unset( $msg_ary[0]['confetti'] );
			$this->wrap_update_option( 'license_message', $msg_ary );
		}
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		$localize['powerup_title'] = esc_html__( 'Success!', 'qa-heatmap-analytics' );
		$localize['powerup_text']  = esc_html__( 'Your license is now active and authenticated.', 'qa-heatmap-analytics' );
		$localize['powerup_text2']  = esc_html__( 'Next, please set a data retention period that comfortably fits within the capacity of your server.', 'qa-heatmap-analytics' );
		$localize['powerup_btn']   = esc_html( __( 'Go to Set Retention Period', 'qa-heatmap-analytics' ) );
		wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
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

		global $qahm_time;
		$plan      = $this->wrap_get_option( 'license_plans' );
		if( $this->get_license_plan( 'friend' ) ) {
			$is_friend_plan = true;
		} else {
			$is_friend_plan = false;
		}
		$key       = $this->wrap_get_option( 'license_key' );
		$id        = $this->wrap_get_option( 'license_id' );
		$act_time  = $this->wrap_get_option( 'license_activate_time' );
		$act_time  = $qahm_time->unixtime_to_str( $act_time );
		$input_read = '';
		if ( $plan || $is_friend_plan ) {
			$input_read = ' readonly';
		}
		?>

<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1>QA <?php esc_html_e( 'License Activation', 'qa-heatmap-analytics' ); ?></h1>
				<?php //$this->view_announce_html(); ?>
				<form method="post" action="" id="license_form">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="license_key">
										<?php esc_attr_e( 'License Key', 'qa-heatmap-analytics' ); ?>
									</label>
								</th>
								<td>
									<input name="license_key" type="text" id="license_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text"<?php echo esc_attr($input_read); ?>>

								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="license_id">
										<?php esc_html_e( 'Your Email', 'qa-heatmap-analytics' ); ?>
									</label>
								</th>
								<td>
									<input name="license_id" type="email" id="license_id" value="<?php echo esc_attr( $id ); ?>" class="regular-text" placeholder="test@example.com"<?php echo esc_attr($input_read); ?>>
								</td>
							</tr>

							<tr>
								<td colspan="2">
									<?php if ( ! $plan && ! $is_friend_plan ) { ?>
										<p class="submit">
											<input type="hidden" name="license_cmd" value="check">
											<input type="hidden" name="license_url" value="https://mem.quarka.org/tsushin/">
											<!--<input type="hidden" name="license_url" value="https://kensyo.caddy.jp//tsushin/">-->
											<input type="submit" name="submit" id="license_activate" class="button button-primary" value="<?php esc_attr_e( 'Activate the license', 'qa-heatmap-analytics' ); ?>">
										</p>
									<?php } else { ?>
										<p class="submit">
											<input type="hidden" name="license_cmd" value="deactivate">
											<input type="hidden" name="license_url" value="https://mem.quarka.org/tsushin/">
											<!--<input type="hidden" name="license_url" value="https://kensyo.caddy.jp//tsushin/">-->
											<input type="submit" name="submit" id="license_deactivate" class="button button-primary" value="<?php esc_attr_e( 'Deactivate this license', 'qa-heatmap-analytics' ); ?>">
										</p>
									<?php } ?>
								</td>
							</tr>
							
							<?php if ( $plan || $is_friend_plan ) { ?>
								<tr>
									<th scope="row">
										<?php esc_html_e( 'Last authentication time', 'qa-heatmap-analytics' ); ?>
									</th>
									<td>
										<?php echo esc_html( $act_time ); ?>
									</td>
								</tr>

							<?php } ?>
						</tbody>
					</table>
				</form>

				<?php
				// プラン内容
				$plan_features = array(
					'max_pv' => esc_html_x('Max PV/month', 'Plan Feature', 'qa-heatmap-analytics'),
					'heatmap' => esc_html_x('Heatmaps on All Pages', 'Plan Feature', 'qa-heatmap-analytics'),
					'session_replay' => esc_html_x('Session Replay across All Pages', 'Plan Feature', 'qa-heatmap-analytics'),
					'gsc' => esc_html_x('Google Search Console Integration', 'Plan Feature', 'qa-heatmap-analytics'),
					'goal' => esc_html_x('Max Goal Setting', 'Plan Feature', 'qa-heatmap-analytics'),
					'retention' => esc_html_x('Data Retention', 'Plan Feature', 'qa-heatmap-analytics'),
					'set_your_own' => esc_html_x('Set it on the settings page.', 'a plan detail, note on data retention', 'qa-heatmap-analytics'),
				);

				// $myplanの値によって条件を分岐させる
				$myplan = "";
				if ( ! $plan || $is_friend_plan ) {
					$myplan = "free"; 
				} else {
					if ( $this->get_license_plan( 'pack_light' ) ) {
						$myplan = "light";
					} elseif ( $this->get_license_plan( 'pack_pro' ) ) {
						$myplan = "pro";
					}
				}

				$light_style = "";
				$pro_style = "";

				// $myplanの値に応じて条件を設定
				switch ($myplan) {
					case "free":
						$light_style = "grey-border";
						$pro_style = "grey-border";
						break;
					case "light":
						$light_style = "light-selected";
						$pro_style = "grey-border";
						break;
					case "pro":
						$light_style = "hide-plan";
						$pro_style = "pro-selected";
						break;
					default:
						// デフォルトの処理
				}

				?>
				<style>
					.show-plan {
						display: block;
					}
					.hide-plan {
						display: none;
					}
					.grey-border {
						background-color: white;
						border: 2px solid grey;
					}

					.free-style {
						background-color: white;
						border: 3px solid #2271b1;
					}

					.light-selected {
						background-color: white;
						border: 2px solid green;
					}

					.pro-selected {
						background-color: white;
						border: 2px solid green;
					}

					.plans-container {
						display: flex;
						margin-top: 20px;
						align-items: flex-end;
					}
					.plan-sec {
						margin: 0 10px;
						padding: 10px;
					}
					.plan-sec table {
						width: 100%;
						border-collapse: collapse;
						font-size: small;
					}
					.plan-sec tr:nth-child(even) {
						background-color: #f2f2f2;
					}

					.upgrade_notice {
						margin: 20px 20px 20px 30px;
					}
					.upgrade_button {
					display: inline-block;
					padding: 10px 20px;
					background-color: #eb8281;
					color: #fff;
					border-radius: 5px;
					cursor: pointer;
					font-size: 16px;
					font-weight: bold;
					}

					.upgrade_button:hover {
					background-color: #f9cdc5;
					color: #fff;
					}
					.upgrade_button a {
					color: inherit;
					text-decoration: none;
					}
				</style>

				<hr>

				<?php if ( ! $plan || $is_friend_plan ) { 
					$lang_set = get_bloginfo('language');
					if ( $lang_set === "ja" ) {
						$plan_link = "https://quarka.org/plan/";
						$referral_link_atag = '<a href="' . esc_url('https://quarka.org/referral-program/') . '" target="_blank" rel="noopener">'; 
					} else {
						$plan_link = "https://quarka.org/en/#plans";
						$referral_link_atag = '<a href="' . esc_url('https://quarka.org/en/referral-program/') . '" target="_blank" rel="noopener">'; 
					}
					$td_max_pv = '<td>10K</td>';
					if ( $is_friend_plan ) {
						$measure   = $this->get_license_option( 'measure' );
						if ( $measure ) {
							$max_pv = $measure / 1000 . 'K';
							$td_max_pv = '<td style="font-weight: bold; background-color: #ceed91;">' . esc_html($max_pv) . '<br><span style="font-size:90%;">Thank you friend:)</span></td>';
						}
					}
				?>
				<div class="plans-container">
					<div id="free-plan" class="plan-sec free-style">
						<h3 class="title">Free</h3>
						<table>
							<tr>
								<td><?php echo esc_html($plan_features['max_pv']); ?></td>
								<?php echo wp_kses_post($td_max_pv); ?>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['heatmap']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['session_replay']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['gsc']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['goal']); ?></td>
								<td>1</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['retention']); ?></td>
								<td>90 days</td>
							</tr>
						</table>
					</div>

					<div class="upgrade_notice">
						<div style="margin-bottom: 54px;">
						<p style="font-size: 1.2em;"><span class="dashicons dashicons-superhero"></span>
							<?php 							 
							$referral_text = sprintf(
								/* translators: placeholders are for the link */ 
								esc_html__( 'Need more PV capacity? %1$sRefer friends%2$s to earn it!', 'qa-heatmap-analytics' ), 
								$referral_link_atag, 
								'</a>' 
							);
							echo wp_kses( $referral_text, array(
								'a' => array(
									'href' => array(),
									'target' => array(),
									'rel' => array(),
								),
							));
							?>
						</p>
						</div>
						<div class="upgrade_button">
							<a href="<?php echo esc_url($plan_link); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Subscribe to the Premium Plan', 'qa-heatmap-analytics' ); ?></a>
						</div>
					</div>
				</div>
				<?php } ?>

				<div class="plans-container">
					<div id="light-plan" class="plan-sec <?php echo esc_attr($light_style); ?>">
						<h3 class="title">Light <?php if($myplan === "light" ) { echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>'; } ?></h3>
						<table>
							<tr>
								<td><?php echo esc_html($plan_features['max_pv']); ?></td>
								<td>100K</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['heatmap']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['session_replay']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['gsc']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['goal']); ?></td>
								<td>3</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['retention']); ?></td>
								<td>Custom<br><span><?php echo esc_html($plan_features['set_your_own']); ?></span></td>
							</tr>
						</table>
					</div>
					<div id="pro-plan" class="plan-sec <?php echo esc_attr($pro_style); ?>">
						<h3 class="title">Pro <?php if($myplan === "pro") { echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>'; } ?></h3>
						<table>
							<tr>
								<td><?php echo esc_html($plan_features['max_pv']); ?></td>
								<td>300K</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['heatmap']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['session_replay']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['gsc']); ?></td>
								<td>&#10003;</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['goal']); ?></td>
								<td>10</td>
							</tr>
							<tr>
								<td><?php echo esc_html($plan_features['retention']); ?></td>
								<td>Custom<br><span><?php echo esc_html($plan_features['set_your_own']); ?></span></td>
							</tr>
						</table>
					</div>

				</div>



			</div><!-- /endof .wrap -->
		</div>
		<?php

		// ライセンスページで1度のみ表示するメッセージであればこの段階で非表示設定にする
		$msg_ary = $this->wrap_get_option( 'license_message' );
		if ( $msg_ary ) {
			foreach ( $msg_ary as &$msg ) {
				if ( $msg['view'] === QAHM_License::MESSAGE_VIEW['license'] ) {
					$msg['view'] = QAHM_License::MESSAGE_VIEW['hidden'];
				}
			}
			$this->wrap_update_option( 'license_message', $msg_ary );
		}
	}

	/**
	 * 設定画面の項目をデータベースに保存する
	 */
	public function save_config() {
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$creds = false;

			$access_type = get_filesystem_method();
			if ( $access_type === 'ftpext' ) {
				$creds = request_filesystem_credentials( '', '', false, false, null );
			}
			if ( ! WP_Filesystem( $creds ) ) {
				throw new Exception( 'WP_Filesystem Invalid Credentials' );
			}
		}

		$post = array();
		// nonceで設定したcredentialのチェック
		// 設定画面
		if ( isset( $_POST[ self::NONCE_NAME ] ) ) {
			if ( check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME ) ) {
				global $qahm_license;
				
				$cmd = $this->wrap_filter_input( INPUT_POST, 'license_cmd' );
				$key = $this->wrap_filter_input( INPUT_POST, 'license_key' );
				$id  = $this->wrap_filter_input( INPUT_POST, 'license_id' );
				$url = $this->wrap_filter_input( INPUT_POST, 'license_url' );

				// フォームの値を更新
				$this->wrap_update_option( 'license_key', $key );
				$this->wrap_update_option( 'license_id', $id );

				if ( 'check' === $cmd ) {
					// アクティベート
					$res = $qahm_license->activate( $key, $id, QAHM_License::MESSAGE_VIEW['license'], $url );

				} elseif ( 'deactivate' === $cmd ) {
					// ディアクティベート
					$res = $qahm_license->deactivate( $key, $id,QAHM_License::MESSAGE_VIEW['license'], $url );
				}
			}
		}
	}

	/**
	* ライセンスメッセージのクリア
	*/
	public function ajax_clear_license_message() {
		$no  = (int) $this->wrap_filter_input( INPUT_POST, 'no' );

		$msg_ary  = $this->wrap_get_option( 'license_message' );
		if ( ! $msg_ary ) {
			die();
		}

		foreach ( $msg_ary as &$msg ) {
			if( $msg['no'] === $no ) {
				$msg['view'] = QAHM_License::MESSAGE_VIEW['hidden'];
			}
		}
		
		$this->wrap_update_option( 'license_message', $msg_ary );
		die();
	}
} // end of class
