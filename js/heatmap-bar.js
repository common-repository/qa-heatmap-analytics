var qahm = qahm || {};

// エレメントの表示 / 非表示切り替え.
qahm.toggleShowElement = function( clickElem, tarElem ) {
	let type = jQuery( clickElem ).attr( 'type' );

	let iframe = jQuery( '#heatmap-iframe' );

	// iframe内のコンテンツのdocumentオブジェクト
	let ifrmDoc = iframe[0].contentWindow.document;

	if ( type === 'checkbox' ) {
		jQuery( clickElem ).on(
			'click',
			function() {
				if ( jQuery( this ).prop( 'checked' ) ) {
					jQuery( clickElem ).prop( 'checked', true );
					jQuery( tarElem ).removeClass( 'qahm-hide' );
				} else {
					jQuery( clickElem ).prop( 'checked', false );
					jQuery( tarElem ).addClass( 'qahm-hide' );
				}
			}
		);
	}
};

// デバイス選択ボックスの変更処理
qahm.changeDeviceSelectBox = function() {
	jQuery( '#qahm-bar-device > select' ).change(
		function() {
			// window.location.href = jQuery(this).val();
			qahm.createCap( qahm.type, qahm.id, qahm.ver, jQuery( this ).val(), null, null, true );
		}
	);
};

// バージョン選択ボックスのソース取得
qahm.getVersionSelectBoxSource = function() {
	bar_ver_select = '<select>';
	const verMax   = Number( qahm.verMax );
	const verView  = 30;
	let urlSplit   = location.href.split( '/' );
	/*
	・リストに表示するのは３０アイテムまで。
	・現在のリビジョン＋最新リビジョンを表示

	[例]
	バージョン50（一番上）
	バージョン100
	バージョン99
	バージョン98
	…
	バージョン70
	 */
	bar_ver_select += qahm.getVersionSelectBoxOptionSource( qahm.ver, '' );
	for ( let i = verMax; i > Math.max( 0,verMax - verView ); i-- ) {
		if ( Number( qahm.ver ) === i ) {
			continue;
		}
		urlSplit[urlSplit.length - 3] = i;
		bar_ver_select               += qahm.getVersionSelectBoxOptionSource( i, urlSplit.join( '/' ) );
	}
	bar_ver_select += '</select>';
	return bar_ver_select;
}

// バージョン選択ボックスのoptionタグを構築
qahm.getVersionSelectBoxOptionSource = function( ver, url ) {
	if ( url ) {
		url = ' value="' + url + '"';
	}
	for ( let i = 0, len = qahm.recRefresh.length; i < len; i++ ) {
		if ( qahm.recRefresh[i]['version'] == ver ) {
			if ( qahm.recRefresh[i]['end_date'] ) {
				return null;
			}
			//let date_obj = new Date( qahm.recRefresh[i]['refresh_date'] );
			let date     = qahm.recRefresh[i]['refresh_date'];//date_obj.getFullYear() + '/' + ('0' + (date_obj.getMonth() + 1)).slice( -2 ) + '/' + ('0' + date_obj.getDate()).slice( -2 );
			return '<option' + url + '>Ver.' + ver + ' ' + date + ' - </option>';
		}
	}
	return null;
};

// バージョン選択ボックスの変更処理
qahm.changeVersionSelectbox = function() {
	jQuery( '#qahm-bar-version-select > select' ).on(
		'change',
		function() {
			// 遷移先バージョン取得
			let ver = jQuery( this ).val();
			if (ver != '') {
				qahm.createCap( qahm.type, qahm.id, ver, qahm.dev, null, null, false );
			}
		}
	);
};
