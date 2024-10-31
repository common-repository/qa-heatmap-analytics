    let goalmax = qahm.goalmax;
    let stepmax = 2;
    window.addEventListener( 'load', loadFinished );
    function loadFinished() {
        //document readyが完了するまではsaveさせない
        let td_gtype_save = document.getElementsByClassName('td_gtype_save');
        for ( let iii = 0; iii < td_gtype_save.length; iii++ ) {
            td_gtype_save[iii].style.opacity = "100";
        }
        let el_loadings = document.getElementsByClassName('el_loading');
        for ( let iii = 0; iii < el_loadings.length; iii++ ) {
            el_loadings[iii].style.display = "none";
        }
//mkdummy
        let tab_item = document.getElementsByClassName('tab_item');
        for ( let iii = 0; iii < tab_item.length; iii++ ) {
            tab_item[iii].addEventListener( 'click',  (e) => {
                let idname = e.target.htmlFor;
                window.location.hash = idname;
            } );
        }
//mkdummy
        let g_clickpage = qahm.g_clickpage;

        for ( let gid = 1; gid <= goalmax; gid++ ) {
            // jQuery( `#g${gid}_event-iframe-containar` ).hide();
            let typeradios = document.getElementsByName( `g${gid}_type` );
            let iframeX = document.getElementById(`g${gid}_event-iframe`);
            iframeX.src = `${g_clickpage[gid]}`;
            iframeX.addEventListener('load',function(){
                for ( let jjj = 0; jjj < typeradios.length; jjj++ ) {
                    typeradios[jjj].addEventListener( 'click', showGoalTextboxes );
                    if (typeradios[jjj].value === 'gtype_click' && typeradios[jjj].checked ) {
                        qahm.showIframeSelector(`g${gid}_event-iframe-containar`);
                    }
                }
                let valobj = document.getElementById(`g${gid}_val`);
                if (valobj) {
	                calcSales(valobj);
			    }
            } );
        }
//mkdummy
        let hashtabid = window.location.hash;
        if ( hashtabid ) {
            hashtabid = hashtabid.replace('#', '');
            let activetab = document.getElementById( hashtabid );
            if ( activetab ) {
                activetab.checked = true;
            }
        }
//mkdummy

    }
    function calcSales( obj ) {
        let idname   = obj.id;
        let idsplit  = idname.split('_');
        let gnum     = idsplit[0].slice(1);

        let num = document.getElementById(`g${gnum}_num`).value;
        let val = document.getElementById(`g${gnum}_val`).value;

        if ( num === '' ) {
            num = 0
        } else {
            num = Number(num);
        }
        if ( val === '' ) {
            val = 0
        } else {
            val = Number(val);
        }

        let sales   = num * val;
        let calspan = document.getElementById(`g${gnum}_calcsales`);
        calspan.innerText = sales.toLocaleString();
        calspan.classList.add('highlight');

        // 500ミリ秒後にすぐ外す
        setTimeout(function() {
            calspan.classList.remove('highlight');
        }, 500);

    }
    function showGoalTextboxes(e) {
        let radioobj = e.target;
        let idname   = radioobj.id;
        let idsplit  = idname.split('_');
        let gnum     = idsplit[0].slice(1);

        switch (idsplit[2]) {
            case 'click':
                document.getElementById(`g${gnum}_page_goal`).style.display  = 'none';
                document.getElementById(`g${gnum}_click_goal`).style.display = 'block';
                document.getElementById(`g${gnum}_event_goal`).style.display = 'none';
                qahm.showIframeSelector(`g${gnum}_event-iframe-containar`);
                //required
                document.getElementById(`g${gnum}_goalpage`).required = false;
                document.getElementById(`g${gnum}_clickpage`).required = true;
                document.getElementById(`g${gnum}_clickselector`).required = true;
                document.getElementById(`g${gnum}_eventselector`).required = false;
                break;

            case 'event':
                document.getElementById(`g${gnum}_page_goal`).style.display  = 'none';
                document.getElementById(`g${gnum}_click_goal`).style.display = 'none';
                document.getElementById(`g${gnum}_event_goal`).style.display = 'block';
                jQuery( `#g${gnum}_event-iframe-containar` ).hide();
                //required
                document.getElementById(`g${gnum}_goalpage`).required = false;
                document.getElementById(`g${gnum}_clickpage`).required = false;
                document.getElementById(`g${gnum}_clickselector`).required = false;
                document.getElementById(`g${gnum}_eventselector`).required = true;
                break;

            default:
            case 'page':
                document.getElementById(`g${gnum}_page_goal`).style.display  = 'block';
                document.getElementById(`g${gnum}_click_goal`).style.display = 'none';
                document.getElementById(`g${gnum}_event_goal`).style.display = 'none';
                jQuery( `#g${gnum}_event-iframe-containar` ).hide();
                //required
                document.getElementById(`g${gnum}_goalpage`).required = true;
                document.getElementById(`g${gnum}_clickpage`).required = false;
                document.getElementById(`g${gnum}_clickselector`).required = false;
                document.getElementById(`g${gnum}_eventselector`).required = false;
                break;

        }
    }

    function siteinfoChanges(formobj) {
        // let submitobj = e.target;
        let siteinfo_form   = formobj;
        let target_customer = siteinfo_form[`target_customer`].value;
        let sitetype  = siteinfo_form[`sitetype`].value;
        let membership  = siteinfo_form[`membership`].value;
        let payment       = siteinfo_form[`payment`].value;
        let month_later  = siteinfo_form[`month_later`].value;
        let session_goal = siteinfo_form[`session_goal`].value;

		qahm.showLoadIcon();
		let start_time = new Date().getTime();
        jQuery.ajax(
            {
                type: 'POST',
                url: qahm.ajax_url,
                dataType : 'json',
                data: {
                    'action'  : 'qahm_ajax_save_siteinfo',
                    'target_customer':         target_customer,
                    'sitetype':      sitetype,
                    'membership':  membership,
                    'payment':  payment,
                    'month_later':  month_later,
                    'session_goal': session_goal,
                    'nonce':qahm.nonce_api
                }
            }
        ).done(
            function( data ){
				function saveSiteInfoDone() {
					AlertMessage.alert(
						qahml10n['alert_message_success'],
						qahml10n['site_info_saved'],
						'success',
						function(){}
					);
					qahm.hideLoadIcon();
				}

				if ( data ) {
					// 最低読み込み時間経過後に処理実行
					let now_time  = new Date().getTime();
					let load_time = now_time - start_time;
					let min_time  = 600;

					if ( load_time < min_time ) {
						// ロードアイコンを削除して新しいウインドウを開く
						setTimeout( saveSiteInfoDone, (min_time - load_time) );
					} else {
						saveSiteInfoDone();
					}
				} else {
					AlertMessage.alert(
						qahml10n['alert_message_failed'],
						qahml10n['site_info_failed'],
						'error',
						function(){}
					);
					qahm.hideLoadIcon();
				}
            }
        ).fail(
            function( jqXHR, textStatus, errorThrown ){
                qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				AlertMessage.alert(
					qahml10n['alert_message_failed'],
					qahml10n['site_info_failed'],
					'error',
					function(){}
				);
				qahm.hideLoadIcon();
            }
        ).always(
            function(){
            }
        );
    }

    function deleteGoalX( gid ) {
		AlertMessage.confirm(
			qahm.sprintf( qahml10n['cnv_delete_title'], gid ),
			qahml10n['cnv_delete_confirm'],
			'info',
			function () {
				let start_time = new Date().getTime();
				qahm.showLoadIcon();
				jQuery.ajax(
					{
						type: 'POST',
						url: qahm.ajax_url,
						dataType : 'json',
						data: {
							'action'  : 'qahm_ajax_delete_goal_x',
							'gid':      gid,
							'nonce':qahm.nonce_api
						}
					}
				).done(
					function( data ){
						function deleteGoalXDone() {
							AlertMessage.alert(
								qahml10n['alert_message_success'],
								qahm.sprintf( qahml10n['cnv_success_delete'], gid ),
								'success',
								function(){}
							);

							qahm.hideLoadIcon();
							location.reload();
						}

						if ( data ) {
							// 最低読み込み時間経過後に処理実行
							let now_time  = new Date().getTime();
							let load_time = now_time - start_time;
							let min_time  = 600;

							if ( load_time < min_time ) {
								// ロードアイコンを削除して新しいウインドウを開く
								setTimeout( deleteGoalXDone, (min_time - load_time) );
							} else {
								deleteGoalXDone();
							}
						} else {
							AlertMessage.alert(
								qahml10n['alert_message_failed'],
								qahml10n['cnv_couldnt_delete'],
								'error',
								function(){}
							);
							qahm.hideLoadIcon();
						}

					}
				).fail(
					function( jqXHR, textStatus, errorThrown ){
						qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
						AlertMessage.alert(
							qahml10n['alert_message_failed'],
							qahml10n['cnv_couldnt_delete'],
							'error',
							function(){}
						);
						qahm.hideLoadIcon();
					}
				);
			}
		);
    }


    function saveChanges(formobj) {
        // let submitobj = e.target;
        let gform     = formobj;
        let idname    = formobj.id;
        let idsplit   = idname.split('_');
        let gnum      = idsplit[0].slice(1);
        let submitobj = document.getElementById(`g${gnum}_submit`);

        let gtitle      = gform[`g${gnum}_title`].value;
        let gnum_scale  = gform[`g${gnum}_num`].value;
        let gnum_value  = gform[`g${gnum}_val`].value;
        let gtype       = gform[`g${gnum}_type`].value;
        let g_goalpage  = gform[`g${gnum}_goalpage`].value;
        let g_pagematch = gform[`g${gnum}_pagematch`].value;
        let g_clickpage = gform[`g${gnum}_clickpage`].value;
        let g_eventtype = gform[`g${gnum}_eventtype`].value;
        let g_clickselector = gform[`g${gnum}_clickselector`].value;
        let g_eventselector = gform[`g${gnum}_eventselector`].value;


        //required check
        if ( gtitle === '' ) {
            return
        }

        let uri         = new URL(window.location.href);
        let httpdomaina = uri.origin;
        let httpdomainb = httpdomaina + '/';
        if ( g_pagematch === 'pagematch_prefix' && gtype === 'gtype_page') {
            if ( g_goalpage === httpdomaina || g_goalpage === httpdomainb ) {
                alert( qahml10n['cnv_page_set_alert'] );
                return false;
            }
        }

		//IDが飛んでいないかチェック
		for ( let gid = 1; gid < gnum; gid++ ) {
	        let gidtitle = document.getElementById(`g${gid}_title`).value;
	        if ( gidtitle === '' ) {
                alert( qahml10n['cnv_goal_numbering_alert'] );
                return false;
			}
		}

        let backupvalue = submitobj.value;
        submitobj.disabled = true;
        submitobj.value = qahml10n['cnv_saving'];
		qahm.showLoadIcon();
		let start_time = new Date().getTime();
        jQuery.ajax(
            {
                type: 'POST',
                url: qahm.ajax_url,
                dataType : 'json',
                data: {
                    'action'  : 'qahm_ajax_save_goal_x',
                    'gid':         gnum,
                    'gtitle':      encodeURI(gtitle),
                    'gnum_scale':  gnum_scale,
                    'gnum_value':  gnum_value,
                    'gtype':       encodeURI(gtype),
                    'g_goalpage':  g_goalpage,
                    'g_pagematch': g_pagematch,
                    'g_clickpage': g_clickpage,
                    'g_eventtype': g_eventtype,
                    'g_clickselector': g_clickselector,
                    'g_eventselector': g_eventselector,
                    'nonce':qahm.nonce_api
                }
            }
        ).done(
            function( data ){
				function saveGoalXDone() {
					if ( data === 'no_page_id' ) {
						AlertMessage.alert(
							qahml10n['alert_message_failed'],
							qahml10n['nothing_page_id'] + '<br>' + qahml10n['nothing_page_id2'],
							'error',
							function(){}
						);
					} else if ( data === 'wrong_delimiter' ) {
						AlertMessage.alert(
							qahml10n['alert_message_failed'],
							qahml10n['wrong_regex_delimiter'],
							'error',
							function(){}
						);
					} else if(Array.isArray(data) && !data.length) {
						AlertMessage.alert(
							qahml10n['alert_message_failed'],
							'',
							'error',
							function(){}
						);

					} else {
						let count = data['count'];
						let btnstr = qahml10n['cnv_reaching_goal_notice'];
						if ( isNaN( count ) || Number( count ) === 0 ) {
							btnstr = qahml10n['goal_saved'];
						}
						submitobj.value = btnstr;
						AlertMessage.alert(
							qahml10n['alert_message_success'],
							qahm.sprintf( qahml10n['cnv_saved_1'], gnum ) + '<br>' + qahml10n['cnv_saved_2'],
							'success',
							function(){}
						);
					}

					setTimeout( function(){
						submitobj.value = backupvalue;
						submitobj.disabled = false;
					}, 2000 );
					qahm.hideLoadIcon();
				}

				// 最低読み込み時間経過後に処理実行
				let now_time  = new Date().getTime();
				let load_time = now_time - start_time;
				let min_time  = 600;

				if ( load_time < min_time ) {
					// ロードアイコンを削除して新しいウインドウを開く
					setTimeout( saveGoalXDone, (min_time - load_time) );
				} else {
					saveGoalXDone();
				}

            }
        ).fail(
            function( jqXHR, textStatus, errorThrown ){
				function saveGoalXFail() {
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					AlertMessage.alert(
						qahml10n['alert_message_failed'],
						qahml10n['cnv_couldnt_saved'],
						'error',
						function(){}
					);
					submitobj.value = backupvalue;
					submitobj.disabled = false;
					qahm.hideLoadIcon();
				}

				// 最低読み込み時間経過後に処理実行
				let now_time  = new Date().getTime();
				let load_time = now_time - start_time;
				let min_time  = 600;

				if ( load_time < min_time ) {
					// ロードアイコンを削除して新しいウインドウを開く
					setTimeout( saveGoalXFail, (min_time - load_time) );
				} else {
					saveGoalXFail();
				}
            }
        );
    }

    function detailopen( stepid ) {
        for (let iii = 1; iii < stepmax + 1; iii++) {
            let detailid  = 'step' + iii.toString();
            let detaildiv = document.getElementById( detailid );
            if ( iii === stepid ) {
                detaildiv.open = true;
            } else {
                detaildiv.open = false;
            }
        }
    }




	/**
	 * オブジェクトがELEMENT_NODEか判定
	 */
	qahm.showIframeSelector = function( idname ){
        let idsplit  = idname.split('_');
        let gid      = idsplit[0].slice(1);
        jQuery( `#g${gid}_event-iframe-containar` ).show();

		jQuery( `#g${gid}_click_pageload` ).prop( 'disabled', false ).text( qahml10n['cnv_load_page'] );
		let frameContent = jQuery( 'body', jQuery( `#g${gid}_event-iframe` ).contents() );
		frameContent.on( 'click', function(e){
			// セレクタ設定
			const names   = qahm.getSelectorFromElement( e.target );
			const selName = names.join( '>' );
			jQuery( `#g${gid}_clickselector` ).val( selName );

			// 吹き出し表示
			jQuery( `#g${gid}_event-iframe-tooltip-right` ).fadeIn( 300 ).css( 'display', 'inline' );
			setTimeout( function(){ jQuery( `#g${gid}_event-iframe-tooltip-right` ).fadeOut( 300 ); }, 1500 );
			return false;
		});

		jQuery( `#g${gid}_click_pageload` ).on( 'click', function(){
			let url = jQuery( `#g${gid}_clickpage` ).val();
			jQuery( `#g${gid}_click_pageload` ).prop( 'disabled', true ).text( qahml10n['cnv_loading'] );
			jQuery( `#g${gid}_clickselector` ).val( '' );
			jQuery( `#g${gid}_event-iframe` ).attr( 'src', url );
			jQuery( `#g${gid}_event-iframe` ).on( 'load', function(){
				qahm.showIframeSelector( jQuery(this).attr('id') );
			});
		});
	};

	/**
	 * オブジェクトがELEMENT_NODEか判定
	 */
	qahm.isElementNode = function( obj ) {
		return obj && obj.nodeType && obj.nodeType === 1;
	}

	/**
	 * 同じ階層に同名要素が複数ある場合は識別のためインデックスを付与する
	 * 複数要素の先頭 ( index = 1 ) の場合、インデックスは省略可能
	 */
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
		if ( ! qahm.isElementNode( el ) ) {
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

			var index = qahm.getSiblingElemetsIndex( el, name );
			if ( 1 < index ) {
				name += ':nth-of-type(' + index + ')';
			}

			names.unshift( name );
			el = el.parentNode;
		}

		return names;
	};







jQuery( function(){
	jQuery( document ).on( 'click', '#plugin-submit', function(){
		qahm.showLoadIcon();

		let dataDay     = jQuery('#data_retention_dur').val();
		let isCbChecked = jQuery('#cb_sup_mode').is(':checked');
		let email       = jQuery('#send_email_address').val();
		
		let start_time = new Date().getTime();
		jQuery.ajax(
			{
				type: 'POST',
				url: qahm.ajax_url,
				dataType : 'text',
				data: {
					'action'            : 'qahm_ajax_save_plugin_config',
					'security'		    : qahml10n['nonce_qahm_options'],
					'data_retention_dur': dataDay,
					'cb_sup_mode'       : isCbChecked,
					'send_email_address': email,
				},
			}
		).done(
			function(){
				function savePluginConfigDone() {
					AlertMessage.alert(
						qahml10n['alert_message_success'],
						qahml10n['setting_option_saved'],
						'success',
						function(){}
					);
					qahm.hideLoadIcon();
				}

				// 最低読み込み時間経過後に処理実行
				let now_time  = new Date().getTime();
				let load_time = now_time - start_time;
				let min_time  = 500;

				if ( load_time < min_time ) {
					// ロードアイコンを削除して新しいウインドウを開く
					setTimeout( savePluginConfigDone, (min_time - load_time));
				} else {
					savePluginConfigDone();
				}
			}
		).fail(
			function( jqXHR, textStatus, errorThrown ){
				qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				AlertMessage.alert(
					qahml10n['alert_message_failed'],
					qahml10n['setting_option_failed'],
					'error',
					function(){}
				);
				qahm.hideLoadIcon();
			}
		);
	});
});