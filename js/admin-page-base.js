jQuery(
	function () {
		// 管理画面上部にライセンス関連のメッセージを表示
		jQuery( '.qahm-license-message > button.notice-dismiss' ).on(
			'click',
			function(){
				let no = jQuery( this ).parent().data( 'no' );
				jQuery.ajax(
					{
						type: 'POST',
						url: qahm.ajax_url,
						data: {
							'action' : 'qahm_ajax_clear_license_message',
							'no': no
						}
					}
				).done(
					function( res ){
						qahm.log( 'done : qahm_ajax_clear_license_message' );
					}
				).fail(
					function( jqXHR, textStatus, errorThrown ){
						qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					}
				);
			}
		);


		// 海外の無料ユーザー向けにフレンドプランの案内を表示
		let isSubscribed = false;
		if ( qahm.license_plans && qahm.license_plans['paid'] === true ) {
			isSubscribed = true;
		}
		if ( qahm.announce_friend_plan && qahm.language !== 'ja' && ! isSubscribed ) {
			AlertMessage.custom(
				{
					allowOutsideClick: false,
					showCancelButton: true,
					title: 'Increase PV limit for free',
					html: 'Introduce QA Analytics to your friends and you can increase the PV limit for free.',
					confirmButtonText: 'Introduce to a friend',
					cancelButtonText: 'Close',
					icon: 'info'
				},
				function () {
					window.open( 'https://quarka.org/en/referral-program/', '_blank' );
				}
			);
		}

		// 1周年キャンペーンのメッセージを表示
		/*
		if ( ! qahml10n.campaign_oneyear_popup ) {
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					data: {
						'action' : 'qahm_ajax_view_oneyear_popup',
					}
				}
			).done(
				function( res ){
					if ( res === '1' ) {
						qahm.startConfetti();
						AlertMessage.alert(
							qahml10n.campaign_oneyear_title,
							qahml10n.campaign_oneyear_text,
							'success',
							function(){
								qahm.stopConfetti();
							}
						);
					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				}
			);
		}
		*/
	}
);