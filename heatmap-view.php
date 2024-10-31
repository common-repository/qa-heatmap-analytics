<?php
try {
    $wp_load_path = dirname( __FILE__, 4 ) . '/wp-load.php';
	
    if ( file_exists( $wp_load_path ) ) {
        require_once( $wp_load_path );
    } else {
        exit('wp-load.php could not be found at the following path: ' . '<br>' . esc_html( $wp_load_path ) );
    }

	// GETパラメーター判定
	$version_id   = (int) filter_input( INPUT_GET, 'version_id' );
	if ( ! $version_id ) {
		throw new Exception( 'Query string has no value.' );
	}

	global $qahm_time;
	global $wp_filesystem;
	$heatmap_view_work_dir = $qahm_view_heatmap->get_data_dir_path( 'heatmap-view-work' );
	$heatmap_view_work_url = $qahm_view_heatmap->get_heatmap_view_work_dir_url();
	$file_info             = $heatmap_view_work_dir . $version_id . '-info.php';
	if ( ! $wp_filesystem->exists( $file_info ) ) {
		throw new Exception( 'heatmap view info file does not exist.' );
	}
	$content_info_ary = $wp_filesystem->get_contents_array( $file_info );


	// info ファイル読み込み
	foreach ( $content_info_ary as $content_info ) {
		$exp_info = explode( '=', $content_info );
		switch ( $exp_info[0] ) {
			case 'data_num':
				$data_num = (int) trim( $exp_info[1] );
				break;
			case 'wp_qa_type':
				$wp_qa_type = trim( $exp_info[1] );
				break;
			case 'wp_qa_id':
				$wp_qa_id = (int) trim( $exp_info[1] );
				break;
			case 'version_no':
				$version_no = (int) trim( $exp_info[1] );
				break;
			case 'device_name':
				$device_name = trim( $exp_info[1] );
				break;
			case 'time_on_page':
				$time_on_page = (float) trim( $exp_info[1] );
				$time_on_page = $qahm_time->seconds_to_timestr( $time_on_page );
				$time_on_page = substr( $time_on_page, strlen('00:') );
				break;
		}
	}
	$is_heatmap = false;
	if ( $data_num > 0 ) {
		$is_heatmap = true;
	}


	// アクセス権限判定
	if ( ! $qahm_view_heatmap->check_qahm_access_cap( 'qahm_view_reports' ) ) {
		throw new Exception( esc_html( 'You do not have access privileges.' ) );
	}

	// 翻訳ファイルの読み込み
	load_plugin_textdomain( 'qa-heatmap-analytics', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	
	$ajax_url       = admin_url( 'admin-ajax.php' );
	$plugin_dir_url = plugin_dir_url( __FILE__ );

/*
	$text_data_num = esc_html__( 'Number of data', 'qa-heatmap-analytics' );
	$text_data_num = '<i class="fas fa-users"></i> ' . $text_data_num . ': ' . $data_num;
	$text_data_num = '<span class="qahm-tooltip-bottom" data-qahm-tooltip="' . $qahm_view_heatmap->qa_langesc_attr__( 'このページでヒートマップデータを記録した数です。PV数に近い値になりますが、数秒で直帰した場合などは記録されません。', 'qa-heatmap-analytics' ) . '">' . $text_data_num . '</span>';
*/
	$html_bar        = '<ul>';
	$html_bar_mobile = '<ul>';

	// データ数
	$data_num_title   = esc_html__( 'Valid Data', 'qa-heatmap-analytics' );
	$data_num_tooltip   = esc_attr__( 'The amount of valid data for heatmap.', 'qa-heatmap-analytics' );
	$data_num_tooltip   .= esc_attr__( 'Over the data retention period (standard 28 days), the data will be deleted and no longer displayed. For a more accurate analysis, get the extended retention option.', 'qa-heatmap-analytics' );

	$data_num_icon    = '<i class="fas fa-users"></i>';
		
	// ヘルプ
	$help_title   = esc_html__( 'Help', 'qa-heatmap-analytics' );
	$help_tooltip = esc_attr__( 'Click to open Help page for heatmap view.', 'qa-heatmap-analytics' );
	$help_icon    = '<i class="far fa-question-circle"></i>';
	
	if ( $is_heatmap ) {
		
		// スクロールマップ
		$scro_map_title   = esc_html__( 'Scroll Map', 'qa-heatmap-analytics' );
		$scro_map_tooltip = esc_attr__( 'Indicates "whether this page was read all the way down"; showing the number of the users at the bottom. ', 'qa-heatmap-analytics' );
//		$scro_map_tooltip = esc_attr__( 'Indicates "whether this page was read all the way down." The top is viewed by 100% of users, and generally the number of users decreases as it goes down.', 'qa-heatmap-analytics' );
		$scro_map_icon    = '<img src="' . $qahm_view_heatmap->get_img_dir_url() . 'scroll-map.svg">';

		// アテンションマップ
		$atte_map_title   = esc_html__( 'Attention Map', 'qa-heatmap-analytics' );
		$atte_map_tooltip = esc_attr__( 'Indicates "what content the users are interested in." Well-read parts are displayed in red like a heat map.', 'qa-heatmap-analytics' );
		$atte_map_icon    = '<img src="' . $qahm_view_heatmap->get_img_dir_url() . 'attention-map.svg">';

		// クリックヒートマップ
		$heat_map_title   = esc_html__( 'Click Heatmap', 'qa-heatmap-analytics' );
		$heat_map_tooltip = esc_attr__( 'Indicates "where the users are clicking." The place a user clicks is likely to be the point of interest. The parts that have been clicked a lot are displayed in red.', 'qa-heatmap-analytics' );
		$heat_map_icon    = '<img src="' . $qahm_view_heatmap->get_img_dir_url() . 'click-heat-map.svg">';

		// クリックカウントマップ
		$count_map_title   = esc_html__( 'Click Count Map', 'qa-heatmap-analytics' );
		$count_map_tooltip = esc_attr__( 'Indicates "how many times this button or link has been pressed." You can see trends such as banner clicks.', 'qa-heatmap-analytics' );
		$count_map_icon    = '<img src="' . $qahm_view_heatmap->get_img_dir_url() . 'click-count-map.svg">';

		// 平均滞在
		$time_on_page_title   = esc_html__( 'Average Time on Page', 'qa-heatmap-analytics' );
		$time_on_page_tooltip = esc_attr__( 'Shows the average amount of time spent on the page per person, derived from the heatmap data.', 'qa-heatmap-analytics' );
		$time_on_page_icon    = '<i class="fas fa-user-clock"></i>';

		// html構築
		if ( ! isset($_COOKIE['qa_heatmap_bar_scroll']) &&
			! isset($_COOKIE['qa_heatmap_bar_scroll']) &&
			! isset($_COOKIE['qa_heatmap_bar_scroll']) &&
			! isset($_COOKIE['qa_heatmap_bar_scroll']) ) {
			$cfg_scroll      = false;
			$cfg_attention   = true;
			$cfg_click_heat  = true;
			$cfg_click_count = false;
			setcookie( 'qa_heatmap_bar_scroll', 'false', time() + 60 * 60 * 24 * 365 * 2, '/' );
			setcookie( 'qa_heatmap_bar_attention', 'true', time() + 60 * 60 * 24 * 365 * 2, '/' );
			setcookie( 'qa_heatmap_bar_click_heat', 'true', time() + 60 * 60 * 24 * 365 * 2, '/' );
			setcookie( 'qa_heatmap_bar_click_count', 'false', time() + 60 * 60 * 24 * 365 * 2, '/' );
		} else {
			$cfg_scroll      = filter_input( INPUT_COOKIE, 'qa_heatmap_bar_scroll' );
			$cfg_scroll      = $cfg_scroll === 'true' ? true : false;
			$cfg_attention   = filter_input( INPUT_COOKIE, 'qa_heatmap_bar_attention' );
			$cfg_attention   = $cfg_attention === 'true' ? true : false;
			$cfg_click_heat  = filter_input( INPUT_COOKIE, 'qa_heatmap_bar_click_heat' );
			$cfg_click_heat  = $cfg_click_heat === 'true' ? true : false;
			$cfg_click_count = filter_input( INPUT_COOKIE, 'qa_heatmap_bar_click_count' );
			$cfg_click_count = $cfg_click_count === 'true' ? true : false;
		}
		
		$html_bar .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-scroll', $scro_map_icon . $scro_map_title, $scro_map_tooltip, $cfg_scroll, false );
		$html_bar .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-attention', $atte_map_icon . $atte_map_title, $atte_map_tooltip, $cfg_attention, false );
		$html_bar .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-click-heat', $heat_map_icon . $heat_map_title, $heat_map_tooltip, $cfg_click_heat, false );
		$html_bar .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-click-count', $count_map_icon . $count_map_title, $count_map_tooltip, $cfg_click_count, false );
		$html_bar .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-data-num', $data_num_icon . $data_num_title . ': ' . $data_num, $data_num_tooltip );
		$html_bar .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-avg-time-on-page', $time_on_page_icon . $time_on_page_title . ': ' . $time_on_page, $time_on_page_tooltip );
		$html_bar .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-help', $help_icon . $help_title, $help_tooltip, false, 'https://mem.quarka.org/en/manual/to-see-heatmap-view/' );
		
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-scroll', $scro_map_icon, $scro_map_tooltip, $cfg_scroll, true );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-attention', $atte_map_icon, $atte_map_tooltip, $cfg_attention, true );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-click-heat', $heat_map_icon, $heat_map_tooltip, $cfg_click_heat, true );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_checkbox( 'heatmap-bar-click-count', $count_map_icon, $count_map_tooltip, $cfg_click_count, true );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-data-num', $data_num_icon . $data_num, $data_num_tooltip, true );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-avg-time-on-page', $time_on_page_icon . $time_on_page, $time_on_page_tooltip );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-help', $help_icon . $help_title, $help_tooltip, false, 'https://mem.quarka.org/en/manual/to-see-heatmap-view/' );

	} else {
		$html_bar        .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-data-num', $data_num_icon . $data_num_title . $data_num, $data_num_tooltip );
		$html_bar        .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-help', $help_icon . $help_title, $help_tooltip, false, 'https://mem.quarka.org/en/manual/to-see-heatmap-view/' );

		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-data-num', $data_num_icon . $data_num, $data_num_tooltip );
		$html_bar_mobile .= $qahm_view_heatmap->get_html_bar_text( 'heatmap-bar-help', $help_icon . $help_title, $help_tooltip, false, 'https://mem.quarka.org/en/manual/to-see-heatmap-view/' );
	}
	
	$html_bar        .= '</ul>';
	$html_bar_mobile .= '</ul>';
	$plugin_version   = QAHM_PLUGIN_VERSION;

	//add_action( 'wp_enqueue_scripts', array( $qahm_view_heatmap, 'enqueue_scripts' ), 100 );

} catch ( Exception $e ) {
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
	echo '<p>Error : ' . esc_html( $e->getMessage() ) . '</p>';
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=qahm-help' ) ) . '" target="_blank">' . esc_html( 'HELP' ) . '</a></p>';
	echo '</body></html>';
	exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<title>QA Heatmap View</title>
		
		<?php //wp_head(); ?>

		<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- This stylesheet is safely loaded internally for admin use and does not impact the frontend or the original WordPress site. ?>
		<link rel="stylesheet" type="text/css" href="./css/doctor-reset.css?ver=<?php echo esc_attr( $plugin_version ); ?>">
		<link rel="stylesheet" type="text/css" href="./css/common.css?ver=<?php echo esc_attr( $plugin_version ); ?>">
		<link rel="stylesheet" type="text/css" href="./css/heatmap-view.css?ver=<?php echo esc_attr( $plugin_version ); ?>">
		<?php // phpcs:enable ?>

		<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript -- This script is safely loaded internally for admin use and does not impact the frontend or the original WordPress site. ?>
		<script src="./js/lib/jquery/jquery-3.6.0.min.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
		<script src="./js/lib/sweet-alert-2/sweetalert2.min.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
		<script src="./js/alert-message.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
		<script src="./js/lib/font-awesome/all.min.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
		<?php // phpcs:enable ?>

		<?php if ( $is_heatmap ) { ?>
			<script>
				var qahm = qahm || {};
				let qahmObj = {
					'ajax_url': '<?php echo esc_js( $ajax_url ); ?>',
					'type': '<?php echo esc_js( $wp_qa_type ); ?>',
					'id': <?php echo intval( $wp_qa_id ); ?>,
					'ver': <?php echo intval( $version_no ); ?>,
					'dev': '<?php echo esc_js( $device_name ); ?>',
					'version_id': <?php echo intval( $version_id ); ?>,
					'attention_limit_time': <?php echo intval( QAHM_View_Heatmap::ATTENTION_LIMIT_TIME ); ?>,
				};
				qahm = Object.assign(qahm, qahmObj);

				var qahml10n = {
					'people': '<?php echo esc_js( esc_html_x( 'people', 'counting number (unit) of people', 'qa-heatmap-analytics' ) ); ?>',
				};
			</script>

			<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript  -- This script is safely loaded internally for admin use and does not impact the frontend or the original WordPress site. ?>
			<script src="./js/common.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/load-screen.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/cap-create.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/lib/heatmap/heatmap.min.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/heatmap-view.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/heatmap-bar.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<script src="./js/heatmap-main.js?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
			<?php // phpcs:enable ?>

			<script>
				jQuery(function () {
					jQuery('#heatmap-iframe').on('load', function () {
						jQuery('#heatmap-iframe').contents().find('head').append(
							<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- This stylesheet is safely loaded internally for admin use and does not impact the frontend or the original WordPress site. ?>
							'<link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>css/heatmap-frame.css?ver=<?php echo esc_attr( QAHM_PLUGIN_VERSION ); ?>" type="text/css" />'
						);
					});
				});
			</script>
		<?php } else { ?>
			<script>
				alert('<?php esc_html_e( 'You have no valid data to display Heatmap.', 'qa-heatmap-analytics' ); ?>');
			</script>
		<?php } ?>
	</head>
	<body>
		<div id="heatmap-bar">
			<div id="heatmap-bar-inner">
				<nav id="heatmap-nav">
					<?php 
					echo wp_kses( $html_bar, array(
						'ul'   => array(),
						'li'   => array( 'id' => array() ),
						'label' => array( 'class' => array() ),
						'span' => array( 
							'class' => array(), 
							'data-qahm-tooltip' => array(),
						),
						'img' => array( 
							'src' => array(), 
							'alt' => array(),
						),
						'input' => array( 
							'class' => array(), 
							'type' => array(), 
							'checked' => array(), 
							'disabled' => array(),
						),
						'div' => array( 'class' => array() ),
						'i' => array( 'class' => array() ),
						'a' => array(
							'href' => array(),
							'target' => array(),
							'rel' => array(),
						),
					) ); 
					?>
				</nav>
				<nav id="heatmap-mobile-nav">
					<?php 
					echo wp_kses( $html_bar_mobile, array(
						'ul'   => array(),
						'li'   => array( 'id' => array() ),
						'label' => array( 'class' => array() ),
						'span' => array( 
							'class' => array(), 
							'data-qahm-tooltip' => array(),
						),
						'img' => array( 
							'src' => array(), 
							'alt' => array(),
						),
						'input' => array( 
							'class' => array(), 
							'type' => array(), 
							'checked' => array(), 
							'disabled' => array(),
						),
						'div' => array( 'class' => array() ),
						'i' => array( 'class' => array() ),
						'a' => array(
							'href' => array(),
							'target' => array(),
							'rel' => array( 'noopener', 'noreferrer' ),
						),
					) ); 
					?>
				</nav>
			</div>
		</div>
		<div id="heatmap-iframe-container" class="frame">
			<iframe id="heatmap-iframe" src="<?php echo esc_attr( $heatmap_view_work_url . $version_id . '-cap.php' ); ?>" width="100%" height="100%"></iframe>
		</div>
		<div id="heatmap-container" class="frame">
			<div id="heatmap-content">
				<div id="heatmap-click-heat" class="qahm-hide">
					<div id="heatmap-click-heat-0"></div>
					<div id="heatmap-click-heat-1"></div>
				</div>
				<div id="heatmap-click-count" class="qahm-hide">
					<div id="heatmap-click-count-0"></div>
					<div id="heatmap-click-count-1"></div>
				</div>
				<div id="heatmap-attention-scroll">
					<div id="heatmap-scroll-tooltip" class="qahm-hide"><span id="heatmap-scroll-data-num"></span></div>
					<div id="heatmap-scroll" class="qahm-hide">
						<div id="heatmap-scroll-0"></div>
						<div id="heatmap-scroll-1"></div>
					</div>
					<div id="heatmap-attention" class="qahm-hide">
						<div id="heatmap-attention-0"></div>
						<div id="heatmap-attention-1"></div>
					</div>
				</div>
			</div>
		</div>
		<?php //wp_footer(); ?>
	</body>
</html>
