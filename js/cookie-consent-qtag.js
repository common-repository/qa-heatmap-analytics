var qahm              = qahm || {};
qahm.cookieConsent = "{cookie_consent}";

if( !qahm.set_cookieConsent ){

    qahm.set_cookieConsent = function(){

        // まずは既存のクッキーをチェック
        if(document.cookie.indexOf("qahm_cookieConsent=true") !== -1) {
            // qa_cookieConsentがtrueで存在する場合は何もしない
            return;
        }
    
        // クッキーを設定する
        let name = "qahm_cookieConsent=";
        let expires = new Date();
        expires.setTime(expires.getTime() + 60 * 60 * 24 * 365 * 2 * 1000); //有効期限は2年
        let cookie_value = name + qahm.cookieConsent + ";expires=" + expires.toUTCString() + ";path=/";
    
        document.cookie = cookie_value;
    }

}

qahm.set_cookieConsent();

