<?php
/**
 * 
 *
 * @package qa_heatmap
 */

$qahm_admin_page_dataportal = new QAHM_Admin_Page_Dataportal();

class QAHM_Admin_Page_Dataportal extends QAHM_Admin_Page_Base {

	// スラッグ
	const SLUG = QAHM_NAME . '-dataportal';

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

		// enqueue_style
		$this->common_enqueue_style();
		wp_enqueue_style( QAHM_NAME . '-admin-page-base-css', $css_dir_url . 'admin-page-base.css', array( QAHM_NAME . '-reset' ), QAHM_PLUGIN_VERSION );

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

		$img_dir_url = plugin_dir_url(__FILE__) . 'img/';
		$lang_set = get_bloginfo('language');
		?>

    <style>
      .whitediv-ls {
        background-color: #fff;
        padding: 24px;
      }
      .ls-note {
        background-color: #dfdfdf;
        padding: 2px 12px;
      }
      .ls-sec {
        margin-top: 48px;
      }
      .ls-description p {
        font-size: 14px;
      }
      .ls-step-container {
        display: flex;
        flex-direction: column;
      }
      .ls-steprow {
        display: flex;
        margin-bottom: 10px;
      }
      .ls-steptext {
        flex: 5;
      }
      .ls-steptext p {
        font-size: 14px;
      }

      .ls-stepimg {
        flex: 5;
        margin-left: 20px;
      }
      .ls-stepimg img {
        max-width: 400px;
        border: 1px solid #ddd;
      }
      .smallimg img {
        max-height: 200px;
      }
      table.ls-connect-info, table.ls-connect-info th, table.ls-connect-info td{
        border: 1px solid #ddd;
        border-collapse: collapse;
        padding: 10px;
        background-color: #f8f8f8;
      }
      table.ls-connect-info th {
        width: 8em;
      }
    </style>

		<div id="<?php echo esc_attr( basename( __FILE__, '.php' ) ); ?>" class="qahm-admin-page">
			<div class="wrap">
				<h1><?php esc_html_e( 'Looker Studio Connector', 'qa-heatmap-analytics' ); ?></h1>
            <div class="whitediv-ls">
                <h2 class="el_underlineTitle"><?php esc_html_e( 'QA Analytics Connector', 'qa-heatmap-analytics' ); ?></h2>
                <div class="ls-description">
                  <p><?php esc_html_e( 'We\'ve prepared a "QA Analytics Connector" to enable the use of QA Analytics data as a Looker Studio data source.', 'qa-heatmap-analytics'); ?></p>
                  <p>- <?php esc_html_e( 'Data Period: Last 90 days', 'qa-heatmap-analytics'); ?><br>
                     - <?php esc_html_e( 'Data includes: Each PV data', 'qa-heatmap-analytics'); ?></p>

                  <p><?php esc_html_e( 'Utilize the QA Analytics Connector to seamlessly import your data into Looker Studio. This empowers you to craft personalized user explorers, generate periodic reports, and unlock countless other analytical possibilities.', 'qa-heatmap-analytics'); ?></p>

                  <p><?php esc_html_e( 'URL for the QA Analytics Connector', 'qa-heatmap-analytics' ); ?>:<br>
                  <a href="https://lookerstudio.google.com/datasources/create?connectorId=AKfycbxbbOBMRLw80xfmXOyuXIN7o5xsl166mKIWbAq9Erc1EbggfH68B9HccnwUit59OaOufw&authuser=0" target="_blank" rel="noopener">https://lookerstudio.google.com/datasources/create?connectorId=AKfycbxbbOBMRLw80xfmXOyuXIN7o5xsl166mKIWbAq9Erc1EbggfH68B9HccnwUit59OaOufw&authuser=0</a></p>
                </div>
                <div class="ls-note">
                  <p><i><?php esc_html_e( 'The QA Analytics Connector is designed for private use and is not Google certified.', 'qa-heatmap-analytics'); ?></i> <?php esc_html_e( 'When authorizing it, you may receive a notification stating "Google hasn\'t verified this app". Please proceed with caution.', 'qa-heatmap-analytics'); ?><br>
                  <?php esc_html_e( 'Additionally, please note that we do not respond to any inquiries sent to the developer\'s email address displayed on the panel. For assistance, we encourage you to visit our plugin support forum.', 'qa-heatmap-analytics'); ?></p>
                </div>
                <div class="ls-sec">                      
                  <h3 class=""><?php esc_html_e( 'Connecting with the QA Analytics Connector', 'qa-heatmap-analytics' ); ?></h3>
                  <div class="ls-step-container">
                    <div class="ls-steprow">
                      <div class="ls-steptext">
                        <p>1. <?php esc_html_e( 'Click on the URL provided above. This will take you to the Connector page.', 'qa-heatmap-analytics' ); ?></p>
                        <p>2. <?php esc_html_e( 'Click "Authorize".', 'qa-heatmap-analytics' ); ?></p>
                      </div>
                      <div class="ls-stepimg">
                        <img src="<?php echo esc_attr($img_dir_url . 'lookerstudio1.jpg'); ?>" alt="lookerstudio1">
                      </div>
                    </div>
                    <div class="ls-steprow">
                      <div class="ls-steptext">
                        <p>3. <?php esc_html_e( 'Choose an account.', 'qa-heatmap-analytics' ); ?></p>
                        <p>4. <?php esc_html_e( 'Allow access to your Google account.', 'qa-heatmap-analytics' ); ?></p>
                        <p>5. <?php esc_html_e( 'After allowing access to your Google account, you should return to the connector page.', 'qa-heatmap-analytics' ); ?></p>
                      </div>
                      <div class="ls-stepimg smallimg">
                        <img src="<?php echo esc_attr($img_dir_url . 'lookerstudio2.jpg'); ?>" alt="lookerstudio4">
                        <img src="<?php echo esc_attr($img_dir_url . 'lookerstudio4.jpg'); ?>" alt="lookerstudio4">
                      </div>
                    </div>
                    <div class="ls-steprow">
                      <div class="ls-steptext">
                        <p>6. <?php esc_html_e( 'Once you\'re back, you will see an information input field. Please enter the information as follows:', 'qa-heatmap-analytics' ); ?></p>
                        <table class="ls-connect-info">
                          <tbody>
                            <tr>
                              <th>User Name</th>
                              <td><?php esc_html_e( 'Enter the username of the WordPress account. The user role must be administrator.', 'qa-heatmap-analytics' ); ?></td>
                            </tr>
                            <tr>
                              <th>Password</th>
                              <td><?php esc_html_e( 'Enter the WordPress admin password for the above user.', 'qa-heatmap-analytics' ); ?></td>
                            </tr>
                            <tr>
                              <th>ajax url</th>
                              <td><?php esc_html_e( '(Copy and paste below.)', 'qa-heatmap-analytics' ); ?><p><?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?></p></td>
                            </tr>
                          </tbody>
                        </table>
                        <p>7. <?php esc_html_e( 'Then, click "Connect".', 'qa-heatmap-analytics' ); ?></p>
                      </div>
                      <div class="ls-stepimg">
                        <img src="<?php echo esc_attr($img_dir_url . 'lookerstudio5.jpg'); ?>" alt="lookerstudio5">
                      </div>
                    </div>
                    <div class="ls-steprow">
                      <div class="ls-steptext">
                        <p>8. <?php esc_html_e( 'All done! Your data source is now ready to use.', 'qa-heatmap-analytics' ); ?></p>
                      </div>
                      <div class="ls-stepimg">
                        <img src="<?php echo esc_attr($img_dir_url . 'lookerstudio6.jpg'); ?>" alt="lookerstudio6">
                      </div>

                  </div>
                  <div class="ls-description">
                      <p><?php esc_html_e( 'For those who wish to create their own connector, we are currently in the process of preparing to make the source code of the QA Analytics Connector available. Please stay tuned for updates.', 'qa-heatmap-analytics' ); ?></p>
                  </div>
                </div>

                <div class="ls-sec">
                  <h3 class="el_underlineTitle"><?php esc_html_e( 'Possible Encounters with Data Set Configuration Errors', 'qa-heatmap-analytics' ); ?></h3>
                  <p><?php esc_html_e( 'There may be instances where you encounter errors preventing a successful data set configuration, resulting in the data source being unusable.', 'qa-heatmap-analytics' ); ?></p>
                  <p><img class="el_manualimg" width="400px" src="<?php echo esc_attr($img_dir_url . 'ls-dataset-error.jpg'); ?>" /></p>
                  <p><?php esc_html_e( 'When using the QA Analytics Connector, your WordPress site connects to Looker Studio from the U.S. ', 'qa-heatmap-analytics' ); ?><br>
                  <?php esc_html_e( 'However, this process may encounter errors primarily due to security and server performance considerations, such as:', 'qa-heatmap-analytics' ); ?></p>
                  <ul style="list-style: disc; padding: 0 2em;">
                      <li><?php esc_html_e( 'Your hosting server is configured to block connections from the U.S.', 'qa-heatmap-analytics' ); ?></li>
                      <li><?php esc_html_e( 'Security plugins like Wordfence may impose login restrictions with reCAPTCHA, etc.', 'qa-heatmap-analytics' ); ?></li>
                      <li><?php esc_html_e( 'Web Application Firewalls (WAFs), firewalls, or similar measures may block external communications.', 'qa-heatmap-analytics' ); ?></li>
                      <li><?php esc_html_e( 'Attempting to fetch excessively large amounts of data.', 'qa-heatmap-analytics' ); ?></li>
                      <li><?php esc_html_e( 'Consistently poor server performance, resulting in unresponsiveness to Looker Studio.', 'qa-heatmap-analytics' ); ?></li>
                  </ul>

                  <p><?php esc_html_e( 'Please note that we are unable to address inquiries related to your plugins, hosting server, or other environmental factors, nor can we offer guidance on Looker Studio. We recommend consulting Google\'s official documentation or your hosting provider before attempting to use this data connector.', 'qa-heatmap-analytics' ); ?></p>
                  
                  <p><?php
						
						$msg = sprintf(
							/* translators: placeholders are for the link */
              esc_html__( 'Essentially, the concept aligns with that of the MySQL connector. Therefore, you may find useful guidance in Looker Studio Help under "%1$sConnect to MySQL%2$s."', 'qa-heatmap-analytics' ),
							'<a href="' . esc_url( 'https://support.google.com/looker-studio/answer/7088031' ) . '">',
							'</a>'
						);
						echo wp_kses( $msg, array(
							'a' => array(
								'href' => array(),
							),
						));
                  ?></p>

                  <p><?php						
						$msg = sprintf(
              /* translators: placeholders are for the link */
							esc_html__( 'If you encounter any insights regarding the connection, such as compatibility issues with other plugins or recommended adjustments, please share them with us on our %1$splugin support forum%2$s. Your feedback is invaluable and will help us improve the system and develop a comprehensive user guide.', 'qa-heatmap-analytics' ),
							'<a href="' . esc_url( 'https://wordpress.org/support/plugin/qa-heatmap-analytics/' ) . '">',
							'</a>'
						);
						echo wp_kses( $msg, array(
							'a' => array(
								'href' => array(),
							),
						));
                  ?></p>
                </div>
            </div>
			</div>
		</div>
		<?php
	}
} // end of class
