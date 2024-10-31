const startTime = performance.now(); // 開始時間
var qahm         = qahm || {};
qahm.initBehData = false;
qahm.initDate    = new Date();

 try {
	document.cookie = 'y';
	if ( document.cookie === '' ) {
		throw new Error( 'qa: Measurement failed because cookie is invalid.' );
	}

	var xhr = new XMLHttpRequest();
	let sendStr = 'action=qahm_ajax_init_user_data';
	sendStr += '&nonce=' + encodeURIComponent( qahm.nonce_init );
	sendStr += '&url=' + encodeURIComponent( location.href );
	sendStr += '&title=' + encodeURIComponent( document.title );
	sendStr += '&type=' + encodeURIComponent( qahm.type );
	sendStr += '&id=' + encodeURIComponent( qahm.id );
	sendStr += '&window_width=' + encodeURIComponent( window.screen.width );
	sendStr += '&window_height=' + encodeURIComponent( window.screen.height );
	sendStr += '&referrer=' + encodeURIComponent( document.referrer );
	sendStr += '&country=' + encodeURIComponent( (navigator.userLanguage||navigator.browserLanguage||navigator.language).substr(0,2) );

	// xhr.open( 'POST', qahm.ajax_url, true );
	// xhr.onload = function () {
	// 	let data = JSON.parse(xhr.response);
	// 	if ( data && data.readers_name ) {
	// 		qahm.readersName      = data.readers_name;
	// 		qahm.readersBodyIndex = data.readers_body_index;
	// 		qahm.rawName          = data.raw_name;
	// 		qahm.initBehData      = true;
	// 		console.log( 'qa: init success.' );
	// 	} else {
	// 		throw new Error( 'qa: init failed. HttpStatus: ' + xhr.statusText );
	// 	}
	// }
	// xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	// xhr.send( sendStr );

} catch (e) {
    console.error( e.message );
}

qahm.isEnableCookie = function(){
	document.cookie = 'y';
	if ( document.cookie === '' ) {
		return false;
	} else {
		return true;
	}
};

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

// マウスの絶対座標取得 ブラウザ間で取得する数値をnormalizeできるらしい
qahm.getMousePos = function(e) {
	let posx     = 0;
	let posy     = 0;
	if ( ! e ) {
		e = window.event;
	}
	if (e.pageX || e.pageY) {
		posx = e.pageX;
		posy = e.pageY;
	} else if (e.clientX || e.clientY) {
		posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
		posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
	}
	return { x : posx, y : posy };
};

qahm.getSiblingElemetsIndex = function( el, name ) {
	var index = 1;
	var sib   = el;

	while ( ( sib = sib.previousElementSibling ) ) {
		if ( sib.nodeName.toLowerCase() === name ) {
			++index;
		}
	}

	return index;
};

/**
 * エレメントからセレクタを取得
 * @returns {string} セレクタ名
 */
qahm.getSelectorFromElement = function( el ) {
	var names = [];
	if ( ! ( el instanceof Element ) ) {
		return names;
	}

	while ( el.nodeType === Node.ELEMENT_NODE ) {
		var name = el.nodeName.toLowerCase();
		if ( el.id ) {
			// id はページ内で一意となるため、これ以上の検索は不要
			// ↑ かと思ったがクリックマップを正しく構成するためには必要
			name += '#' + el.id;
			//names.unshift( name );
			//break;
		}

		// 同じ階層に同名要素が複数ある場合は識別のためインデックスを付与する
		// 複数要素の先頭 ( index = 1 ) の場合、インデックスは省略可能
		//
		var index = qahm.getSiblingElemetsIndex( el, name );
		if ( 1 < index ) {
			name += ':nth-of-type(' + index + ')';
		}

		names.unshift( name );
		el = el.parentNode;
	}

	return names;
};


/**
 * エレメントから遷移先を取得
 * @returns {string} 遷移先URL
 */
qahm.getTransitionFromSelector = function( el ) {
	while ( el.nodeType === Node.ELEMENT_NODE ) {
		if( el.href ){
			return el.href;
		}
		el = el.parentNode;
	}
	return null;
}

jQuery(
	function () {
		// msecを求めてデータに反映
		let docReadyDate = new Date();
		let speedMsec = docReadyDate.getTime() - qahm.initDate.getTime();

		// jQuery.ajax(
		// 	{
		// 		type: 'POST',
		// 		url: qahm.ajax_url,
		// 		data: {
		// 			'action'            : 'qahm_ajax_update_msec',
		// 			'nonce'             : qahm.nonce_behavioral,
		// 			'readers_name'      : qahm.readersName,
		// 			'readers_body_index': qahm.readersBodyIndex,
		// 			'speed_msec'        : speedMsec,
		// 		},
		// 	}
		// ).done(
		// 	function(){
		// 		qahm.log( speedMsec );
		// 	}
		// ).fail(
		// 	function( jqXHR, textStatus, errorThrown ){
		// 		qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		// 	}
		// );

		qahm.isInitBehavioralData = function() {
			if ( qahm.initBehData ) {
				qahm.moveBehavioralData();
				clearInterval( qahm.isInitIntervalId );
			}
		}
		qahm.isInitIntervalId = setInterval( qahm.isInitBehavioralData, 10 );
	}
);


// 測定開始時からの経過時間をミリ秒で取得
qahm.getTotalProcMilliSec   = function() {
	let nowDate       = new Date();
	let diffMilliSec  = nowDate.getTime() - qahm.focusDate.getTime();
	let totalMilliSec = qahm.blurMilliSec  + diffMilliSec;
	return totalMilliSec;
}

// 測定の制限時間を超えたか？
qahm.isOverTimeLimit = function() {
	return qahm.getTotalProcMilliSec() > qahm.limitMilliSec ? true : false;
}

qahm.addPosData = function() {
	// 画面全体のY座標
	const siteBottomY = Math.max.apply(
		null,
		[
			document.body.clientHeight,
			document.body.scrollHeight,
			document.documentElement.scrollHeight,
			document.documentElement.clientHeight
		]
	);

	// 画面中央のY座標
	const dispCenterY = jQuery( window ).scrollTop() + ( window.innerHeight / 2 );


	// 画面下のY座標
	const dispBottomY = jQuery( window ).scrollTop() + window.innerHeight;

	/*
	let percentHeightIdx = Math.floor( dispCenterY / siteBottomY * 100 );
	qahm.percentHeight[percentHeightIdx]++;
	*/

	let stayHeightIdx = 0;
	if( dispCenterY > 0 ) {
		stayHeightIdx = Math.floor( dispCenterY / 100 );
	}
	if ( ! qahm.stayHeight[stayHeightIdx] ) {
		qahm.stayHeight[stayHeightIdx] = 0;
	}
	qahm.stayHeight[stayHeightIdx]++;


	if( ! qahm.isScrollMax && ( dispBottomY / siteBottomY ) > 0.99  ) {
		qahm.isScrollMax = true;
	}
}


qahm.postBehavioralData = function( forceRec = false ) {
	if ( ! forceRec && qahm.postBehavNum > 0 ) {
		return;
	}
	qahm.postBehavNum++;

	let isPos   = false;
	let isClick = false;
	let isEvent = false;

	isPos = true;

	if ( qahm.clickLog.length > 0 ){
		isClick = true;
	}

	if ( qahm.eventLog.length > 0 ){
		isEvent = true;
	}

	// jQuery.ajax(
	// 	{
	// 		type: 'POST',
	// 		url: qahm.ajax_url,
	// 		data: {
	// 			'action'         : 'qahm_ajax_record_behavioral_data',
	// 			'nonce'          : qahm.nonce_behavioral,
	// 			'version_pos'    : 2,
	// 			'version_click'  : 1,
	// 			'version_event'  : 1,
	// 			'is_pos'         : isPos,
	// 			'is_click'       : isClick,
	// 			'is_event'       : isEvent,
	// 			'raw_name'       : qahm.rawName,
	// 			'type'           : qahm.type,
	// 			'id'             : qahm.id,
	// 			'ua'             : navigator.userAgent.toLowerCase(),
    //
	// 			// pos
	// 			//'percent_height' : JSON.stringify( qahm.percentHeight ),
	// 			'stay_height'    : JSON.stringify( qahm.stayHeight ),
	// 			'is_scroll_max'  : qahm.isScrollMax,
    //
	// 			// click
	// 			'click_log'      : JSON.stringify( qahm.clickLog ),
    //
	// 			// event
	// 			'window_inner_w' : window.innerWidth,
	// 			'window_inner_h' : window.innerHeight,
	// 			'event_log'      : JSON.stringify( qahm.eventLog ),
	// 		},
	// 	}
	// ).done(
	// 	function( response ){
	// 		qahm.log( response );
	// 	}
	// ).fail(
	// 	function( jqXHR, textStatus, errorThrown ){
	// 		qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
	// 	}
	// ).always(
	// 	function(){
	// 		qahm.postBehavNum--;
	// 	}
	// );

	// 送信直後にクリアして次回送信までにログを蓄積させる
	qahm.clickLog.length = 0;
	qahm.eventLog.length = 0;
}


qahm.addEventListener = function() {
	jQuery( 'body' ).on( 'click', function(e){
		if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
			return;
		}

		const selAry   = qahm.getSelectorFromElement( e.target );

		let findTagIdx = -1;
		for (let i = 0, sLen = selAry.length; i < sLen; i++ ) {
			for (let j = 0, tLen = qahm.clickTagAry.length; j < tLen; j++ ) {
				if( selAry[i].indexOf( qahm.clickTagAry[j] ) !== 0 ){
					continue;
				}

				if( selAry[i].length === qahm.clickTagAry[j].length ||
					selAry[i].indexOf( qahm.clickTagAry[j] + '#' ) === 0 ||
					selAry[i].indexOf( qahm.clickTagAry[j] + ':' ) === 0 ) {
					findTagIdx = j;
					break;
				}
			}
			if( findTagIdx !== -1 ){
				break;
			}
		}

		/*
		// aタグのクリック判定
		const transition = qahm.getTransitionFromSelector( e.target );
		if ( transition ) {
			e.preventDefault();
			alert(transition);
			location.href = transition;
		}
		*/

		// クリックウェイト
		if ( findTagIdx === -1 ) {
			if ( qahm.isClickWait ) {
				return;
			}
			qahm.isClickWait = true;
			setTimeout( function(){ qahm.isClickWait = false; }, 300 );

		// タグのクリックウェイト
		} else {
			if ( qahm.clickWaitAry[findTagIdx] ) {
				return;
			}
			qahm.clickWaitAry[findTagIdx] = true;
			setTimeout( function(){ qahm.clickWaitAry[findTagIdx] = false; }, 300 );
		}

		// クリックデータ
		const names   = qahm.getSelectorFromElement( e.target );
		const selName = names.join( '>' );
		//qahm.log( 'selector:' + selName );

		// セレクタ左上
		const selPos  = jQuery( selName ).offset();
		const selTop  = Math.round( selPos.top );
		const selLeft = Math.round( selPos.left );
		//qahm.log( 'selTop: ' + selTop );
		//qahm.log( 'selLeft: ' + selLeft );

		// マウス座標
		const mousePos = qahm.getMousePos( e );
		const mouseX   = Math.round( mousePos.x );
		const mouseY   = Math.round( mousePos.y );
		//qahm.log( 'mouseX: ' + mouseX );
		//qahm.log( 'mouseY: ' + mouseY );

		// セレクタ左上からのマウス相対座標
		const relX = mouseX - selLeft;
		const relY = mouseY - selTop;

		// aタグをクリックした場合は遷移先のURLをデータに入れる
		if ( 'a' === qahm.clickTagAry[findTagIdx] ) {
			const transition = qahm.getTransitionFromSelector( e.target );
			qahm.clickLog.push( [ selName, relX, relY, transition ] );
		} else {
			qahm.clickLog.push( [ selName, relX, relY ] );
		}
		qahm.log( 'click: ' + qahm.clickLog[qahm.clickLog.length - 1] );

		// イベントデータ
		const clientX = Math.round( e.clientX );
		const clientY = Math.round( e.clientY );
		qahm.eventLog.push( [ 'c', qahm.getTotalProcMilliSec(), clientX, clientY ] );
		qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
		//qahm.log( 'event mouse click client pos: ' + clientX + ', ' + clientY );

		// 指定タグへのクリック処理が行われた際はデータを即送信
		if( -1 !== findTagIdx ) {
			qahm.postBehavioralData( true );
		} else {
			qahm.postBehavioralData( false );
		}
	});

	jQuery(window).scroll( function() {
		if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
			return;
		}

		qahm.scrollTopCur = Math.round( jQuery(window).scrollTop() );
		qahm.checkScrollEvent();
	});

	jQuery(window).mousemove( function( e ){
		if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
			return;
		}

		qahm.mouseXCur = Math.round( e.clientX );
		qahm.mouseYCur = Math.round( e.clientY );
		qahm.checkMouseMoveEvent();
	});

	jQuery(window).resize(function(){
		//if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
		if ( qahm.isOverTimeLimit() ) {
			return;
		}

		if ( qahm.resizeId !== false ) {
			clearTimeout( qahm.resizeId );
		}
		qahm.resizeId = setTimeout(function() {
			qahm.eventLog.push( [ 'r', qahm.getTotalProcMilliSec(), window.innerWidth, window.innerHeight ] );
			qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
		}, 300 );
	});
/*
	jQuery( 'video' ).on( 'play', function(e){
		if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
			return;
		}

		const names   = qahm.getSelectorFromElement( e.target );
		const selName = names.join( '>' );

		qahm.eventLog.push( [ 'p', qahm.getTotalProcMilliSec(), selName ] );
		qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
	});

	jQuery( 'video' ).on( 'pause', function(e){
		if ( qahm.isOverTimeLimit() || ! document.hasFocus() ) {
			return;
		}

		const names   = qahm.getSelectorFromElement( e.target );
		const selName = names.join( '>' );

		qahm.eventLog.push( [ 'a', qahm.getTotalProcMilliSec(), selName ] );
		qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
	});
*/
	/*
	test処理
	jQuery( "textarea, input[type='text'], input[type='number']," +
			"input[type='tel'], input[type='email'], input[type='url']," +
			"input[type='password'], input[type='search'], input[type='date']," +
			"input[type='datetime-local'], input[type='month'], input[type='week']," +
			"input[type='time']" ).focus(function (e) {
		const names   = qahm.getSelectorFromElement( e.target );
		const selName = names.join( '>' );
        qahm.log( selName + 'フォーカスされました。' );
    }).blur(function (e) {
		const names   = qahm.getSelectorFromElement( e.target );
		const selName = names.join( '>' );
        qahm.log( selName + 'フォーカスされなくなりました。' );
    });
	*/
}

qahm.checkScrollEvent = function() {
	if ( qahm.scrollTop !== qahm.scrollTopCur && ! qahm.isScrollWait ) {
		qahm.addScrollEvent();
	}
}

qahm.addScrollEvent = function() {
	qahm.isScrollWait = true;
	qahm.scrollTop = qahm.scrollTopCur;
	qahm.eventLog.push( [ 's', qahm.getTotalProcMilliSec(), qahm.scrollTop ] );
	qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
	setTimeout( function(){ qahm.isScrollWait = false; }, 300 );
}

qahm.checkMouseMoveEvent = function() {
	if ( qahm.mouseX !== qahm.mouseXCur || qahm.mouseY !== qahm.mouseYCur ) {
		if( ! qahm.isMouseMoveWait ) {
			qahm.addMouseMoveEvent();
		}
	}
}

qahm.addMouseMoveEvent = function() {
	qahm.isMouseMoveWait = true;
	qahm.mouseX = qahm.mouseXCur;
	qahm.mouseY = qahm.mouseYCur;
	qahm.eventLog.push( [ 'm', qahm.getTotalProcMilliSec(), qahm.mouseX, qahm.mouseY ] );
	qahm.log( 'event: ' + qahm.eventLog[qahm.eventLog.length - 1] );
	setTimeout( function(){ qahm.isMouseMoveWait = false; }, 300 );
}


// データの保存など実行タイミングを監視
// setIntervalはフォーカスが外されていても実行し続けるが、qahmは実行させない仕様となる
// そのため内部的なタイマーを参照し実行タイミングを制御するこのシステムが必要
qahm.monitorBehavioralData = function() {
	if ( qahm.isOverTimeLimit() ) {
		clearInterval( qahm.monitorId );
		return;
	}

	if ( ! document.hasFocus() ){
		return;
	}

	let totalMS = qahm.getTotalProcMilliSec();
	//qahm.log( 'totalMS:' + totalMS );

	// 常時監視
	qahm.checkScrollEvent();
	qahm.checkMouseMoveEvent();

	// 1000ms毎のイベント
	if ( ( totalMS - qahm.monitorPrevRun1000MS ) >= 1000 ) {
		qahm.monitorPrevRun1000MS = Math.floor( totalMS / 1000 ) * 1000;
		qahm.addPosData();
		//qahm.log( '|||||' + qahm.monitorPrevRun1000MS );
	}

	// 3000ms毎のイベント
	if ( ( totalMS - qahm.monitorPrevRun3000MS ) >= 3000 ) {
		qahm.monitorPrevRun3000MS = Math.floor( totalMS / 3000 ) * 3000;
		qahm.postBehavioralData();
		//qahm.log( '*****' + qahm.monitorPrevRun3000MS );
	}
}

// データ取得
/*
qahm.recFlag.promise()
.then(
	function() {
*/
qahm.moveBehavioralData = function() {
	/*
	qahm.percentHeight = new Array(100);
	for ( let i = 0; i < qahm.percentHeight.length ; i++ ) {
		qahm.percentHeight[i] = 0;
	}
	*/
	//qahm.percentHeight.fill(0); IEでは動かないため上記処理に変更

	qahm.stayHeight    = [];

	qahm.limitMilliSec = 1000 * 60 * 30;
	qahm.focusDate     = new Date();
	qahm.blurMilliSec  = 0;

	qahm.isScrollMax     = false;
	qahm.isClickWait     = false;

	qahm.clickLog        = [];
	qahm.eventLog        = [];
	qahm.isScrollWait    = false;
	qahm.isMouseMoveWait = false;
	qahm.resizeId        = false;

	qahm.scrollTop       = 0;
	qahm.scrollTopCur    = Math.round( jQuery(window).scrollTop() );
	qahm.mouseX          = 0;
	qahm.mouseY          = 0;
	qahm.mouseXCur       = 0;
	qahm.mouseYCur       = 0;

	qahm.monitorPrevRun1000MS = 0;
	qahm.monitorPrevRun3000MS = 0;

	qahm.postBehavNum = 0;

	qahm.clickTagAry  = [ 'a','input','button','textarea' ];
	qahm.clickWaitAry = [ false, false, false, false ];

	// ウィンドウがアクティブになっているときだけ記録
	jQuery( window ).on(
		'focus',
		function(){
			qahm.focusDate = new Date();
		}
	).on(
		'blur',
		function(){
			let nowDate = new Date();
			qahm.blurMilliSec += ( nowDate.getTime() - qahm.focusDate.getTime() );
		}
	);

	// 一定間隔で動作する処理はこちらにまとめる
	qahm.monitorId = setInterval( qahm.monitorBehavioralData, 100 );

	// イベントリスナーを利用した処理はこちらにまとめる
	qahm.addEventListener();

	// 初期スクロール位置がトップではない場合の対策
	qahm.checkScrollEvent();

	return true;
}
//;);
const endTime = performance.now(); // 終了時間
qahm.clientLoadTime = (endTime - startTime)/1000;
