<?php

    header('Content-Type: application/x-javascript; charset=utf-8');
    // キャッシュを完全に無効にする
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

    $file_name   = './js/cookie-consent-qtag.js';
    $cookie_consent = "false";

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This input is strictly controlled within the plugin and used only in a non-sensitive context, making nonce verification unnecessary.
	if ( isset($_GET['cookie_consent']) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This input is processed internally and only used for strict string comparison ("yes" or other values), ensuring no security risks arise from arbitrary input.
        if( $_GET['cookie_consent'] == "yes" ){
            $cookie_consent = "true";
        }
	}

    if( file_exists( $file_name ) ){
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped -- This file outputs JavaScript content directly within the plugin, making wp_remote_get() unnecessary and escaping irrelevant since the content is safely managed internally.
        echo str_replace('"{cookie_consent}"', $cookie_consent, file_get_contents( $file_name ));
    }

?>