var qahm              = qahm || {};
qahm.initBehData      = false;
qahm.initDate         = new Date();
qahm.readersName      = null;
qahm.readersBodyIndex = 0;
qahm.rawName          = null;
qahm.speedMsec        = 0;

//QA_ID保存域
qahm.qa_id            = null;

//cookieを拒否しているかどうか
qahm.isRejectCookie   = qahm.cookieMode;

// cookieが有効か判定
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

// cookieをセットする
qahm.setCookie = function(cookie_name, value){

	let name = cookie_name + "=";
	let expires = new Date();
	expires.setTime(expires.getTime() + 60 * 60 * 24 * 365 * 2 * 1000); //有効期限は2年
	let cookie_value = name + value.toString() + ";expires=" + expires.toUTCString() + ";path=/";

	document.cookie = cookie_value;

}

// cookieを取得する
qahm.getCookie = function(cookie_name){
	
	let cookie_ary = qahm.getCookieArray();

	if(cookie_ary[cookie_name]){
		return cookie_ary[cookie_name];
	}

	return false;
}

// cookieを削除する
qahm.deleteCookie = function(cookie_name){

    let name = cookie_name+"=";
    document.cookie = name + ";expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/";

}

qahm.getQaidfromCookie = function(){

	let qa_id_obj = { value: '', is_new_user: 0 };

	let cookie_ary = qahm.getCookieArray();

	if ( cookie_ary["qa_id"] ){
		qa_id_obj.value = cookie_ary["qa_id"];
		qa_id_obj.is_new_user = 0; //新規ユーザでない
	} else {
		qa_id_obj.is_new_user = 1; //新規ユーザ
	}

	return qa_id_obj;

}

qahm.setQaid = function(){

	if( !qahm.qa_id ){
		return false;
	}
	qahm.setCookie("qa_id",qahm.qa_id);

	return true;

}

//状況に応じてCookieを更新する
//戻り値：Cookie拒否かどうか
qahm.updateQaidCookie = function() {

	if( !qahm.cookieMode ){ //Cookie同意モード以外
		//何もしない
		qahm.isRejectCookie = false;
		return;
	}

	//同意モード
	if( !qahm.cookieConsentObject ){ //同意タグがなければすべてのcookie消去
		qahm.deleteCookie("qa_id");
		qahm.deleteCookie("qahm_cookieConsent");
		qahm.isRejectCookie = true;
		return;
	}

	if( qahm.getCookie("qahm_cookieConsent") == "true" ){
		qahm.setQaid();
		qahm.isRejectCookie = false;
	}else{
		qahm.deleteCookie("qa_id");
		qahm.deleteCookie("qahm_cookieConsent");
		qahm.isRejectCookie = true;
	}

}

qahm.updateQaidCookie(); 

// if ( ! qahm.isEnableCookie() ) {
// 	console.error( 'qa: Measurement failed because cookie is invalid.' );
// 	exit;
// }

qahm.init = function() {

	//qa_idの取得
	let qa_id_obj = qahm.getQaidfromCookie();

	let action        = 'action=init_session_data';
	let nonce         = '&nonce=' + encodeURIComponent( qahm.nonce_init );
	let tracking_hash = '&tracking_hash=' + encodeURIComponent( qahm.tracking_hash );
	let url           = '&url=' + encodeURIComponent( location.href );
	let title         = '&title=' + encodeURIComponent( document.title );
	let wp_qa_type    = '&wp_qa_type=' + encodeURIComponent( qahm.type );
	let wp_qa_id      = '&wp_qa_id=' + encodeURIComponent( qahm.id );
	let referrer      = '&referrer=' + encodeURIComponent( document.referrer );
	let country       = '&country=' + encodeURIComponent( (navigator.userLanguage||navigator.browserLanguage||navigator.language).substr(0,2) );
	let is_new_user   = '&is_new_user=' + encodeURIComponent( qa_id_obj.is_new_user );
	let is_reject     = '&is_reject=' + encodeURIComponent( qahm.isRejectCookie );
	let sendStr       = action + nonce + tracking_hash + url + title + wp_qa_type + wp_qa_id + referrer + country + is_new_user + is_reject;
	if( qa_id_obj.value != '' ){
		sendStr += '&qa_id=' + encodeURIComponent( qa_id_obj.value );
	}

	let newAjaxUrl    = qahm.plugin_dir_url + 'qahm-ajax.php';

	let xhr = new XMLHttpRequest();
	xhr.open( 'POST', newAjaxUrl, true );
	xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	xhr.send( sendStr );

	// qahm-ajax通信
	xhr.onload = function () {
		try {
			let data = JSON.parse( xhr.response );
			if ( data && data.readers_name ) {
				qahm.readersName      = data.readers_name;
				qahm.readersBodyIndex = data.readers_body_index;
				qahm.rawName          = data.raw_name;
				qahm.initBehData      = true;
				qahm.qa_id            = data.qa_id;
				if(!qahm.cookieMode){ //同意モード以外なら有無を言わさずqa_idをセット
					qahm.setQaid();
				}else{
					qahm.updateQaidCookie();
				}
				console.log( 'qa: init success.' );
			} else {
				throw new Error();
			}
		} catch (e) {
			console.error( e.message );
		}
	}

}

qahm.init();

//公開メソッド
var qahm_pub = qahm_pub || {};

qahm_pub.cookieConsent = function(agree) {
	if(agree){
		qahm.setCookie("qa_cookieConsent",agree);
	}else{
		qahm.deleteCookie("qa_id");
		qahm.deleteCookie("qa_cookieConsent");
	}
}

