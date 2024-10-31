var qahm = qahm || {};

qahm.loadEffect.promise()
.then( function () {
	if( qahm.license_confetti ) {
		qahm.startConfetti();

		if ( qahm.license_plans['friend'] ) {
			AlertMessage.alert(
					qahml10n.powerup_title,
					qahml10n.powerup_text,
					'success',
					function(){
						qahm.stopConfetti();
					}
			);

		} else {
			AlertMessage.custom(
				{
					allowOutsideClick: false,
					confirmButtonText: qahml10n.powerup_btn,
					title: qahml10n.powerup_title,
					html: qahml10n.powerup_text + '<br>' + qahml10n.powerup_text2,
					icon: 'success'
				},
				function () {
					qahm.stopConfetti();
					window.location.href = 'admin.php?page=qahm-config';
				}
			);
		}
	}
});

