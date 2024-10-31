var qahm = qahm || {};

// ログイン判定
qahm.loadScreen.promise().then(
	function () {
		let d = new jQuery.Deferred();
		qahm.showLoadIcon();
		jQuery.ajax( {
			type: 'POST',
			url: qahm.ajax_url,
			data: {
				'action': 'qahm_ajax_init_heatmap_view',
				'type': qahm.type,
				'id': qahm.id,
				'ver': qahm.ver,
				'dev': qahm.dev,
				'version_id': qahm.version_id,
			},
			dataType: 'json',
		}
		).done(
			function (data) {
				// debugレベルがちゃんと動作しているか確かめる
				qahm.const_debug = data['const_debug'];
				qahm.const_debug_level = data['const_debug_level'];
				qahm.locale = data['locale'];
				qahm.dataNum = data['data_num'];
				qahm.verMax = data['ver_max'];
				qahm.recFlag = data['rec_flag'];
				qahm.allRecFlag = data['is_raw_save_all'];
				qahm.freeRecFlag = data['free_rec_flag'];
				qahm.recRefresh = data['rec_refresh'];
				qahm.mergeC = data['merge_c'];
				qahm.mergeASV1 = data['merge_as_v1'];
				qahm.mergeASV2 = data['merge_as_v2'];
				qahm.iframeWin  = document.getElementById('heatmap-iframe').contentWindow;
				qahm.iframeDoc  = document.getElementById('heatmap-iframe').contentWindow.document;
				qahm.iframeHtml = qahm.iframeDoc.documentElement;
				qahm.iframeBody = qahm.iframeDoc.body;

				// 定数 わかりやすいように大文字
				qahm.DATA_HEATMAP_SELECTOR_NAME = data['DATA_HEATMAP_SELECTOR_NAME'];
				qahm.DATA_HEATMAP_SELECTOR_X    = data['DATA_HEATMAP_SELECTOR_X'];
				qahm.DATA_HEATMAP_SELECTOR_Y    = data['DATA_HEATMAP_SELECTOR_Y'];

				qahm.DATA_ATTENTION_SCROLL_PERCENT_V1   = data['DATA_ATTENTION_SCROLL_PERCENT_V1'];
				qahm.DATA_ATTENTION_SCROLL_STAY_TIME_V1 = data['DATA_ATTENTION_SCROLL_STAY_TIME_V1'];
				qahm.DATA_ATTENTION_SCROLL_STAY_NUM_V1  = data['DATA_ATTENTION_SCROLL_STAY_NUM_V1'];
				qahm.DATA_ATTENTION_SCROLL_EXIT_NUM_V1  = data['DATA_ATTENTION_SCROLL_EXIT_NUM_V1'];

				qahm.DATA_ATTENTION_SCROLL_STAY_HEIGHT_V2 = data['DATA_ATTENTION_SCROLL_STAY_HEIGHT_V2'];
				qahm.DATA_ATTENTION_SCROLL_STAY_TIME_V2   = data['DATA_ATTENTION_SCROLL_STAY_TIME_V2'];
				qahm.DATA_ATTENTION_SCROLL_STAY_NUM_V2    = data['DATA_ATTENTION_SCROLL_STAY_NUM_V2'];
				qahm.DATA_ATTENTION_SCROLL_EXIT_NUM_V2    = data['DATA_ATTENTION_SCROLL_EXIT_NUM_V2'];

				let cookieAry = qahm.getCookieArray();
				if ( cookieAry['qa_heatmap_bar_scroll'] === 'true' ) {
					jQuery( '#heatmap-scroll' ).removeClass( 'qahm-hide' );
					jQuery( '#heatmap-scroll-tooltip' ).removeClass( 'qahm-hide' );
				}
				if ( cookieAry['qa_heatmap_bar_attention'] === 'true' ) {
					jQuery( '#heatmap-attention' ).removeClass( 'qahm-hide' );
				}
				if ( cookieAry['qa_heatmap_bar_click_heat'] === 'true' ) {
					jQuery( '#heatmap-click-heat' ).removeClass( 'qahm-hide' );
				}
				if ( cookieAry['qa_heatmap_bar_click_count'] === 'true' ) {
					jQuery( '#heatmap-click-count' ).removeClass( 'qahm-hide' );
				}

				d.resolve();
			}
		).fail(
			function (jqXHR, textStatus, errorThrown) {
				qahm.log_ajax_error(jqXHR, textStatus, errorThrown);
				d.reject();
			}
		);
		return d.promise();
	}
)
.then(
	function () {
		if (qahm.dataNum > 0) {
			qahm.initMapParam();
			qahm.createBlockArray();
			qahm.createClickHeatMap();
			qahm.createScrollMap();
			qahm.addScrollMapEvent();
			qahm.updateScrollMapTooltipDataNum( 0 );
			qahm.createAttentionMap();
			qahm.createClickCountMap();
			qahm.toggleShowElement('.heatmap-bar-scroll', '#heatmap-scroll' );
			qahm.toggleShowElement('.heatmap-bar-scroll', '#heatmap-scroll-tooltip' );
			qahm.toggleShowElement('.heatmap-bar-attention', '#heatmap-attention' );
			qahm.toggleShowElement('.heatmap-bar-click-heat', '#heatmap-click-heat' );
			qahm.toggleShowElement('.heatmap-bar-click-count', '#heatmap-click-count' );
			//qahm.resizeWindow();
			qahm.changeBarConfig();

			qahm.addIframeEvent();
			qahm.correctScroll();

			setInterval( qahm.checkUpdateMap, 1000 );
		}

		jQuery( 'a', qahm.iframeDoc ).on( 'click', function() {
			return false;
		});
		qahm.disabledConfig( false );
		qahm.hideLoadIcon();
	}
);


// cookie値を連想配列として取得する
qahm.getCookieArray = function(){
	var arr = new Array();
	if ( document.cookie !== '' ) {
		var tmp = document.cookie.split( '; ' );
		for (var i = 0;i < tmp.length;i++) {
			var data     = tmp[i].split( '=' );
			arr[data[0]] = decodeURIComponent( data[1] );
		}
	}
	return arr;
};