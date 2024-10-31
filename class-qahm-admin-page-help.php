<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_admin_page_help = new QAHM_Admin_Page_Help();

class QAHM_Admin_Page_Help extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-help';

	// nonce
	const NONCE_ACTION = self::SLUG . '-nonce-action';
	const NONCE_NAME   = self::SLUG . '-nonce-name';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 初期化
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if( $this->hook_suffix !== $hook_suffix ||
			! $this->is_enqueue_jquery()
		) {
			return;
		}

		$css_dir_url = $this->get_css_dir_url();

		// enqueue_style
		$this->common_enqueue_style();
		wp_enqueue_style( QAHM_NAME . '-admin-page-help-css', $css_dir_url . 'admin-page-help.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );	


		// enqueue script
		$this->common_enqueue_script();
	
		// inline script
		$scripts = $this->get_common_inline_script();
		wp_add_inline_script( QAHM_NAME . '-common', 'var ' . QAHM_NAME . ' = ' . QAHM_NAME . ' || {}; let ' . QAHM_NAME . 'Obj = ' . wp_json_encode( $scripts ) . '; ' . QAHM_NAME . ' = Object.assign( ' . QAHM_NAME . ', ' . QAHM_NAME . 'Obj );', 'before' );

		// localize
		$localize = $this->get_common_localize_script();
		wp_localize_script( QAHM_NAME . '-common', QAHM_NAME . 'l10n', $localize );
	}

	/**
	 * ページの表示
	 */
	public function create_html() {
		$lang_set = get_bloginfo('language');
		$php_version = phpversion();
		$php_memory_limit = ini_get( 'memory_limit' );
		$php_max_execution_time = ini_get( 'max_execution_time' ); 
		global $wp_version;
		?>

		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1>QA <?php esc_html_e( 'Help', 'qa-heatmap-analytics' ); ?></h1>
				<?php //$this->view_announce_html(); ?>

				<div id="qa-help">
					<div class="help01">
						<h2><?php esc_html_e( 'QA Analytics and Site info', 'qa-heatmap-analytics' ); ?></h2>
						<h2 class="note-version"><?php esc_html_e( 'Installed Version', 'qa-heatmap-analytics' ); ?>: <?php echo esc_html( QAHM_PLUGIN_VERSION ); ?></h2>
						<table>
							<thead>
								<tr>
									<th></th>
									<td class="yours"><?php esc_html_e( 'Your Site', 'qa-heatmap-analytics' ); ?></td><td class="qas">/ <?php esc_html_e( 'QA Supported Environment', 'qa-heatmap-analytics' ); ?></td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th><?php esc_html_e( 'WordPress version', 'qa-heatmap-analytics' ); ?></th>
									<td class="yours"><?php echo esc_html($wp_version); ?></td><td class="qas">/ <?php /* translators: placeholders represent the supported version number */ printf( esc_html__( '%s or higher', 'qa-heatmap-analytics' ), '5.9' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'PHP version', 'qa-heatmap-analytics' ); ?></th>
									<td class="yours"><?php echo esc_html($php_version); ?></td><td class="qas">/ <?php /* translators: placeholders represent the supported version number */  printf( esc_html__( '%s or higher', 'qa-heatmap-analytics' ), '5.6' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'PHP memory limit', 'qa-heatmap-analytics' ); ?></th>
									<td class="yours"><?php echo esc_html($php_memory_limit); ?></td><td class="qas">/ <?php esc_html_e( '1G+(1024M+) recommended', 'qa-heatmap-analytics' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'PHP max execution time', 'qa-heatmap-analytics' ); ?></th>
									<td class="yours"><?php echo esc_html($php_max_execution_time); ?></td><td class="qas">/ <?php esc_html_e( '240 seconds recommended', 'qa-heatmap-analytics' ); ?></td>
								</tr>
							</tbody>
						</table>
						<p><a href="https://mem.quarka.org/en/manual/site-environment/" target="_blank" rel="noopener"><?php esc_html_e( 'Supported site environment', 'qa-heatmap-analytics' ); ?><span class="qahm_link-mark"><i class="fas fa-external-link-alt"></i></span></a></p>
					</div>

					

					<div class="h-frame help03">
						<h2><?php esc_html_e( 'Support Site', 'qa-heatmap-analytics' ); ?></h2>
						<h3><a href="https://mem.quarka.org/en/manual/" target="_blank" rel="noopener"><?php esc_html_e( ' User Guide / Manual', 'qa-heatmap-analytics' ); ?></a></h3>
						<p><?php esc_html_e( 'Get information about how to use and set up QA Analytics, troubleshooting, and more.', 'qa-heatmap-analytics' ); ?></p>						
						<p><?php esc_html_e( '[Please Note]', 'qa-heatmap-analytics' ); ?><br>
						<?php esc_html_e( 'The language switcher on our support site may not always function as expected. To change languages, simply click the "language" button located at the top right corner of the page.', 'qa-heatmap-analytics' ); ?>
						<?php if ( $lang_set !== 'ja' ) : ?>
						<br><?php esc_html_e( 'Additionally, please note that some articles on our support site may only be available in Japanese. We would greatly appreciate it if you could utilize a browser\'s translation service or another tool to read these articles.', 'qa-heatmap-analytics' ); ?>
						<?php endif; ?>
						</p>
					</div>

					<div class="h-frame help03">
						<?php
							if ( $lang_set === 'ja') {
								$support_form_url = 'https://mem.quarka.org/memberhome/';
							} else {
								$support_form_url = 'https://mem.quarka.org/en/direct-support/';
							}
						?>	
						<h2><?php esc_html_e( 'Help', 'qa-heatmap-analytics' ); ?></h2>
						<h3><a href="<?php echo esc_url($support_form_url); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Direct Support for Subscribers', 'qa-heatmap-analytics' ); ?></a></h3>
						<p><?php esc_html_e( 'Subscribers to our paid plans can access a dedicated contact form on our site to reach our support team directly. Password required.', 'qa-heatmap-analytics' ); ?><br>
						<?php esc_html_e( 'For technical inquiries, including plugin operations, you can find the necessary "Debug" information at the bottom of this Help page.', 'qa-heatmap-analytics' ); ?></p>
						<h3><a href="https://wordpress.org/support/plugin/qa-heatmap-analytics/" target="_blank" rel="noopener"><?php esc_html_e( 'WordPress Support Forum', 'qa-heatmap-analytics' ); ?></a></h3>
						<p><?php esc_html_e( 'This forum, provided by WordPress.org, is a public space for discussions about QA Analytics.', 'qa-heatmap-analytics' ); ?><br>
						<?php esc_html_e( 'If you have any questions, feel free to ask them here. Helpful members, as well as our support team, are available to assist you. We also welcome any feedback or observations you may have.', 'qa-heatmap-analytics' ); ?><br>
						<?php esc_html_e( '(Please note: You need to log in with your WordPress.org account to use the forum. If you do not have one, please create an account.)', 'qa-heatmap-analytics' ); ?></p>
					</div>

							
					<div class="h-frame help04">
						<h2><?php esc_html_e( 'Glossary', 'qa-heatmap-analytics' ); ?></h2>
						<p><?php esc_html_e( 'This briefly explains the terms that appear on the QA Analytics pages.', 'qa-heatmap-analytics' ); ?><br>
						<?php /* translators: placeholders are for the link */ printf( esc_html__( 'For more details, see %1$s User Guide / Manual %2$s in our support site.', 'qa-heatmap-analytics' ), '<a href="https://mem.quarka.org/en/manual/" target="_blank" rel="noopener">', '</a>'); ?></p>
						<table>
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Sessions (Earliest 7days)', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'This metric counts the total number of sessions that occurred during the earliest 7-day period within the selected date range.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'If the date range is 7 days or shorter, it considers the earliest single day to prevent duplicating the total value for both the earliest and latest days.', 'qa-heatmap-analytics' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Sessions (Latest 7days)', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'This metric counts the total number of sessions that occurred during the most recent 7-day period within the selected date range.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'If the date range is 7 days or shorter, it considers the latest single day to prevent duplicating the total value for both the earliest and latest days.', 'qa-heatmap-analytics' ); ?></td>
								</tr>								
								<tr>
									<th><?php esc_html_e( 'Average Time on Page', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'This metric is calculated by dividing the total time spent on the page by the number of visitors who accessed the page. It provides insight into the average engagement duration for each visitor on the specific page.', 'qa-heatmap-analytics' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Time on Site', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'QA Analytics measures the time spent on your site with an accuracy of +/- 3 seconds.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'It specifically tracks the time during which a user is actively browsing your site, excluding any time spent on other websites or using other applications. This provides a more accurate representation of the time range that users spend on your site.', 'qa-heatmap-analytics' ); ?></td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Users in Last 30 Min', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'Represents the total number of users who have visited your website in the last 30 minutes, including the current moment.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'This metric encompasses users who have either landed on a page, performed any action on the page (such as scrolling), or transitioned between pages.', 'qa-heatmap-analytics' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Active Users in Last Min', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'Shows the number of users actively engaging with your website in the last minute.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'Activity includes page navigation, actions performed within pages (such as scrolling), and interaction with the website\'s features. This metric provides real-time insight into the current activity level on the website.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'If a user does not perform any actions within a page for more than 30 minutes, their session is considered complete.', 'qa-heatmap-analytics' ); ?></td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Download Data (as TSV)', 'qa-heatmap-analytics' ); ?></th>
									<td><?php esc_html_e( 'Provides recorded pageview data in a tab-separated values (TSV) format.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'QA Analytics captures detailed information for every pageview, including operating system, browser, page URL and title, device category, UTM parameters (source, medium, campaign), session details (number, access time, ordinal), browsing speed in milliseconds, browsing duration in seconds, and indicators for the last pageview in a session and whether the user is new or returning.', 'qa-heatmap-analytics' ); ?><br>
									<?php esc_html_e( 'TSV is a text-based format where each value is separated by tabs, enabling easy import and analysis in various data processing tools.', 'qa-heatmap-analytics' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>



				</div>

			</div>
		</div>

		
		
		
		<!-- Start debug -->
		<?php
			global $wpdb;
		
			$my_theme     = wp_get_theme();
			$site_plugins = get_plugins();
			$plugin_names = [];
		
			foreach( $site_plugins as $main_file => $plugin_meta ) {
				if ( ! is_plugin_active( $main_file ) ) {
					continue;
				}
				$plugin_names[] = sanitize_text_field( $plugin_meta['Name'] . ' ' . $plugin_meta['Version'] );
			}
			
			$options = '';
			/*
			foreach ( QAHM_OPTIONS as $key => $value ) {
				$options .= '<p><strong>' . $key . ':</strong><br>' . $this->wrap_get_option( $key ) . '</p>';
			}
			*/
			$options .= '<p><strong>Plugin version:</strong><br>' . QAHM_PLUGIN_VERSION . '</p>';

			$cron_status = $this->wrap_get_contents( $this->get_data_dir_path() . 'cron_status' );
		?>
		<div id="qahm-help-debug">
			<h3>Debug</h3>
			<hr>
			<p><strong>WordPress Server IP address:</strong><br><?php echo esc_html( $this->wrap_filter_input( INPUT_SERVER, 'SERVER_ADDR' ) ); ?></p>
			<p><strong>PHP version:</strong><br><?php echo esc_html($php_version); ?></p>
			<p><strong>PHP memory limit:</strong><br><?php echo esc_html($php_memory_limit); ?></p>
			<p><strong>max_execution_time:</strong><br><?php echo esc_html($php_max_execution_time); ?></p>
			<p><strong>PHP extensions:</strong><br><?php echo esc_html( implode( ', ', get_loaded_extensions() ) ); ?></p>			
			<p><strong>Database version:</strong><br>
			<?php
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required to get the database version.
			echo esc_html( $wpdb->get_var( "SELECT VERSION();" ) ); 
			?></p>
			<p><strong>InnoDB availability:</strong><br>
			<?php
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required to get the InnoDB availability.
			echo esc_html( $wpdb->get_var( "SELECT SUPPORT FROM INFORMATION_SCHEMA.ENGINES WHERE ENGINE = 'InnoDB';" ) ); 
			?></p>
			<p><strong>WordPress version:</strong><br><?php echo esc_html($wp_version); ?></p>
			<p><strong>Multisite:</strong><br>
			<?php
			 $is_multisite = ( function_exists( 'is_multisite' ) && is_multisite() ) ? 'Yes' : 'No';
			 echo esc_html( $is_multisite ); 
			?>
			</p>
			<p><strong>Active plugins:</strong><br><?php echo esc_html( implode( ', ', $plugin_names ) ); ?></p>
			<p><strong>Theme:</strong><br>
			<?php
			 $theme =  $my_theme->get( 'Name' ) . ' (' . $my_theme->get('Version') . ') by ' . $my_theme->get('Author');
			 echo esc_html($theme);
			?>
			</p>
			<?php echo wp_kses_post($options); ?>
			<p><strong>qalog.txt:</strong><br><?php echo esc_url( $this->get_data_dir_url( 'log' ) . 'qalog.txt' ); ?></p>
			<p><strong>cron_status:</strong><br><?php echo esc_html($cron_status); ?></p>
		</div>
		<!-- End debug -->

		<?php
	}
} // end of class
