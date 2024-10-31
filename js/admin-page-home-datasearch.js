var qahm = qahm || {};

qahm.dsPvData        = null;
qahm.dsSessionData   = null;
qahm.dsTodayUnixTime = null;


qahm.drawSessionsView = function ( sessionary ) {
	//抽出中表示
	jQuery( '#extraction-proc-button' ).text( qahml10n['ds_cyusyutsu_cyu'] ).prop( 'disabled', true );
	jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">...</span>' + qahml10n['ds_cyusyutsu_cyu']);

	//sessionary の中身チェック
	// is_last無しのNULL配列があるセッションは、10000個で止まるようになっている（＠qahm-data-db.php L457）
	for ( let iii = 0; iii < sessionary.length; iii++ ) {
		let tempSessionPvs = [];
		if ( sessionary[iii].length  === 10000 ) {
			for ( let jjj = 0; jjj < sessionary[iii].length; jjj++ ) {
				if ( sessionary[iii][jjj] === null ) {
					tempSessionPvs[jjj-1]['is_last'] = '1';
					break;
				}
				tempSessionPvs.push(sessionary[iii][jjj]);
			}
			sessionary[iii] = tempSessionPvs;
		}		
	}

	qahm.dsPvData = null;
	qahm.dsSessionData = sessionary;
	//session recording table.js

	//make table
	let allSmAry = qahm.createSmArray( sessionary );
	if ( allSmAry.length > 0 && typeof goalsmTable !== 'undefined' && goalsmTable !== '') {
		goalsmTable.rawDataArray = allSmAry;
		if (! goalsmTable.headerTableByID) {
			goalsmTable.generateTable();
		} else {
			goalsmTable.updateTable();
		}
	}

	let alllpAry = qahm.createLpArray( sessionary );
	if ( alllpAry.length > 0 && typeof goallpTable !== 'undefined' && goallpTable !== '') {
		goallpTable.rawDataArray = alllpAry;
		if (! goallpTable.headerTableByID) {
			goallpTable.generateTable();
		} else {
			goallpTable.updateTable();
		}
	}

	// heatmap
	qahm.createHeatmapList( gPastRangeStart, gPastRangeEnd );

	let allSessionAry = qahm.createSessionArray( sessionary );
	if ( allSessionAry.length > 0 && typeof sdayTable !== 'undefined' && sdayTable !== '') {
		sdayTable.rawDataArray = allSessionAry;
		if (! sdayTable.headerTableByID) {
			sdayTable.generateTable();
		} else {
			sdayTable.updateTable();
		}
	}


	//ボタンを元に戻す
	jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
	//抽出件数を表示する
	if ( allSessionAry.length == 0 ) {
		jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">0</span>' + qahml10n['ds_cyusyutsu_kensu']);
	} else {
		jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">' + allSessionAry.length + '</span>' + qahml10n['ds_cyusyutsu_kensu']);
	}


};


// for データを探す
jQuery( function(){

	// table.js 初期化
	qahm.createSearchSessionTable();
	qahm.createSearchHeatmapTable();

	// 変数の初期化
	let today = new Date();
	let hrgap = (today.getTimezoneOffset()) / 60;
	today.setHours( today.getHours() + hrgap + qahm.wp_time_adj );
	qahm.dsTodayUnixTime = today.setHours( 0, 0, 0, 0 );

	let periodDays = 7;
	// gPastRangeStart = new Date(qahm.dsTodayUnixTime);
	// gPastRangeStart.setDate( gPastRangeStart.getDate() - periodDays );
	// gPastRangeEnd = new Date(gYesterday);
	// gPastRangeEnd.setHours( 23, 59, 59 );

	//procDateRangePicker();
	// createDataTable();
	clickExtractButton();
	clickExtractUrl();

	function createDataTable(searchUrl, prefix) {

		let periodDays  = Math.ceil( (gPastRangeEnd - gPastRangeStart) / ( 24 * 60 * 60 * 1000 ) );
		let startMoment = moment(gPastRangeStart);
		let endMoment   = moment(gPastRangeEnd);
		let startStr    = startMoment.format('YYYY-MM-DD');
		let endStr      = endMoment.format('YYYY-MM-DD');
		let getdayStr   = endStr;

		//1st count data
		let pvcount    = 0;
		let separatepv = 10000;
		let maxpv      = 300000;
		let loopcount  = 1;
		let loopmax    = 1;
		let loopdayadd = periodDays -1;

		let ary = [];
		let isFirst = true;
		let where    = '';
		let pidcount = 0;
		let pidmax   = 100;
		let pidovermsg = qahml10n['too_many_result_pages_msg'];

		if ( searchUrl ) {

			// page_idを調べる
			let page_id  = null;

			jQuery.Deferred(
				function(d) {
					jQuery.ajax(
						{
							type: 'POST',
							url: qahm.ajax_url,
							dataType : 'json',
							data: {
								'action': 'qahm_ajax_url_to_page_id',
								'nonce' : qahm.nonce_api,
								'url'   : searchUrl,
								'prefix': prefix,
							}
					}
					).done(
						function( data ){
							if ( ! data ) {
								alert( searchUrl + qahml10n['ds_cyusyutsu_error1'] );
								jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
								d.reject();
							}
							pidcount = Object.keys(data).length;
							if ( pidmax < pidcount ) {
								let res = confirm( pidovermsg );
								if ( res === false ) {
									jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
									d.reject();
								}
							}
							if ( Object.keys(data).length == 1 ) {
								page_id = data[0][ 'page_id' ];
								where =  'page_id=' + page_id.toString();
							} else {
								let instr = 'in (';
								for ( let iii = 0; iii < Object.keys(data).length; iii++ ) {
									page_id = data[iii][ 'page_id' ];
									if ( Number(page_id) > 0 ) {
										instr = instr + page_id.toString();
									}
									if ( iii === Object.keys(data).length -1 ) {
										instr = instr + ')';
									} else {
										instr = instr + ',';
									}
								}
								where = 'page_id ' + instr;
							}
							d.resolve();
						}
					).fail(
						function( jqXHR, textStatus, errorThrown ){
							qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
							alert( searchUrl +  qahml10n['ds_cyusyutsu_error1'] );
							jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
							d.reject();
						}
					);
				}
			).then(
				function(){
					//　再帰関数の定義
					let deferred = new jQuery.Deferred;
					const getSessionData = function() {
						let table = 'vr_view_session';
						if ( isFirst ) {
							jQuery.ajax(
								{
									type: 'POST',
									url: qahm.ajax_url,
									dataType : 'json',
									data: {
										'action' : 'qahm_ajax_select_data',
										'table' : table,
										'select': '*',
										'date_or_id':`date = between ${startStr} and ${endStr}`,
										'count' : true,
										'where' : where,
										'nonce':qahm.nonce_api
									}
							}
							).done(
								function( data ){
									pvcount = Number( data );
									//2nd get data loop
									if ( maxpv < pvcount ) {
										daysince = new Date(qahm.dsTodayUnixTime);
										if ((pvcount / 2) < maxpv) {
											daysince.setTime(daysince.getTime() - 30 * 1000 * 60 * 60 * 24);
											alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 30 ) );
										} else if ((pvcount / 8) < maxpv) {
											daysince.setTime(daysince.getTime() - 7 * 1000 * 60 * 60 * 24);
											alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 7 ) );
										} else {
											daysince.setTime(daysince.getTime() - 1 * 1000 * 60 * 60 * 24);
											alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 1 ) );
										}
										startStr = moment(daysince).format('YYYY-MM-DD');
									}else if( separatepv < pvcount ) {
										let separate = Math.floor( pvcount / separatepv );
										loopmax    = separate + 1;
										loopdayadd = Math.floor( ( periodDays -1 ) / separate );
										getday     = new Date(gPastRangeEnd);
										getday.setTime( getday.getTime() + ( loopdayadd - periodDays ) *1000*60*60*24 );
										getdayStr = moment(getday).format('YYYY-MM-DD');
									}
									isFirst = false;
									getSessionData();
								}
							).fail(
								function( jqXHR, textStatus, errorThrown ){
									qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
									//エラー通信失敗のお知らせ文を出す
									jQuery( '#cyusyutsu_notice' ).html('<span style="color: red;">' + qahml10n['ds_cyusyutsu_error2'] + '</span>');
									//ボタンを元に戻す
									jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
								}
							).always(
								function(){
								}
							);
						} else {
							jQuery.ajax(
								{
									type: 'POST',
									url: qahm.ajax_url,
									dataType : 'json',
									data: {
										'action' : 'qahm_ajax_select_data',
										'table' : table,
										'select': '*',
										'date_or_id':`date = between ${startStr} and ${getdayStr}`,
										'count' : false,
										'where' : where,
										'nonce':qahm.nonce_api
									}
								}
							).done(
								function( data ){
									ary = ary.concat(data);
									loopcount++;
									if ( loopcount < loopmax ) {
										//Fromを1日進める
										getday.setTime( getday.getTime() + 1 *1000*60*60*24 );
										startStr =  moment(getday).format('YYYY-MM-DD');
										//Toをさらに loopdayadd - 1日分進める
										getday.setTime( getday.getTime() +  ( loopdayadd - 1 )*1000*60*60*24 );
										getdayStr = moment(getday).format('YYYY-MM-DD');
										getSessionData();
									} else if ( loopcount === loopmax ) {
										//Fromを1日進める
										getday.setTime( getday.getTime() + 1 *1000*60*60*24 );
										startStr =  moment(getday).format('YYYY-MM-DD');
										//Toは昨日まで
										getdayStr = endStr;
										getSessionData();
									} else {
										deferred.resolve();
									}
								}
							).fail(
								function( jqXHR, textStatus, errorThrown ){
									qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
									//エラー通信失敗のお知らせ文を出す
									jQuery( '#cyusyutsu_notice' ).html('<span style="color: red;">' + qahml10n['ds_cyusyutsu_error2'] + '</span>');
									//ボタンを元に戻す
									jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
								}
							).always(
								function(){
								}
							);
						}
						return deferred.promise();
					}; //end of 'getSessionData' difinition

					// 実際の呼び出し
					getSessionData().then( function() {
						//メモリの解放後データ配列をセット
						qahm.dsPvData = null;
						qahm.drawSessionsView( ary );
						// qahm.dsSessionData = ary;
						// console.log( qahm.dsSessionData );

						/*
						let allSessionAry = qahm.createSessionArray(ary);

						if (typeof sdayTable !== 'undefined' && sdayTable !== '') {
							sdayTable.rawDataArray = allSessionAry;
							if (sdayTable.visibleArray.length === 0) {
								sdayTable.generateTable();
							} else {
								if ( sdayTable.isNoCheck() && sdayTable.countActiveFilterBoxes() === 0 && sdayTable.isScrolled() === false ) {
									sdayTable.updateTable();
								}
							}
						}
						*/

						// //session recording table.js
						// let allSessionAry = qahm.createSessionArray(ary);
						// if (typeof sdayTable !== 'undefined' && sdayTable !== '') {
						// 	sdayTable.rawDataArray = allSessionAry;
						// 	if (! sdayTable.headerTableByID) {
						// 		sdayTable.generateTable();
						// 	} else {
						// 		sdayTable.updateTable();
						// 	}
						// }
                        //
						// // heatmap
						// qahm.createHeatmapList( gPastRangeStart, gPastRangeEnd );

						//ボタンを元に戻す
						jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
						//抽出件数を表示する
						if ( allSessionAry.length == 0 ) {
							jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">0</span>' + qahml10n['ds_cyusyutsu_kensu']);
						} else {
							jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">' + allSessionAry.length + '</span>' + qahml10n['ds_cyusyutsu_kensu']);
						}
					});
				}
			);

		} else {

			//1st count data
			let pvcount    = 0;
			let separatepv = 10000;
			let maxpv      = 300000;
			let loopcount  = 1;
			let loopmax    = 1;
			let loopdayadd = periodDays -1;

			//　再帰関数の定義
			let deferred = new jQuery.Deferred;
			const getPvData = function() {
				let table = 'vr_view_pv';
				if ( isFirst ) {
					jQuery.ajax(
						{
							type: 'POST',
							url: qahm.ajax_url,
							dataType : 'json',
							data: {
								'action' : 'qahm_ajax_select_data',
								'table' : table,
								'select': '*',
								'date_or_id':`date = between ${startStr} and ${endStr}`,
								'count' : true,
								'nonce':qahm.nonce_api
							}
					}
					).done(
						function( data ){
							pvcount = Number( data );
							//2nd get data loop
							if ( maxpv < pvcount ) {
								daysince = new Date(qahm.dsTodayUnixTime);
								if ((pvcount / 2) < maxpv) {
									daysince.setTime(daysince.getTime() - 30 * 1000 * 60 * 60 * 24);
									alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 30 ) );
								} else if ((pvcount / 8) < maxpv) {
									daysince.setTime(daysince.getTime() - 7 * 1000 * 60 * 60 * 24);
									alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 7 ) );
								} else {
									daysince.setTime(daysince.getTime() - 1 * 1000 * 60 * 60 * 24);
									alert( qahm.sprintf( qahml10n['ds_cyusyutsu_error3'], 1 ) );
								}
								startStr = moment(daysince).format('YYYY-MM-DD');
							}else if( separatepv < pvcount ) {
								let separate = Math.floor( pvcount / separatepv );
								loopmax    = separate + 1;
								loopdayadd = Math.floor( ( periodDays -1 ) / separate );
								//getday     = new Date(qahm.dsTodayUnixTime);
								getday     = new Date(gPastRangeEnd);
								getday.setTime( getday.getTime() + ( loopdayadd - periodDays ) *1000*60*60*24 );
								getdayStr = moment(getday).format('YYYY-MM-DD');
							}
							isFirst = false;
							getPvData();
						}
					).fail(
						function( jqXHR, textStatus, errorThrown ){
							qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
						}
					).always(
						function(){
						}
					);
				} else {
					jQuery.ajax(
						{
							type: 'POST',
							url: qahm.ajax_url,
							dataType : 'json',
							data: {
								'action' : 'qahm_ajax_select_data',
								'table' : table,
								'select': '*',
								'date_or_id':`date = between ${startStr} and ${getdayStr}`,
								'count' : false,
								'nonce':qahm.nonce_api
							}
						}
					).done(
						function( data ){
							ary = ary.concat(data);
							loopcount++;
							if ( loopcount < loopmax ) {
								//Fromを1日進める
								getday.setTime( getday.getTime() + 1 *1000*60*60*24 );
								startStr =  moment(getday).format('YYYY-MM-DD');
								//Toをさらに loopdayadd - 1日分進める
								getday.setTime( getday.getTime() +  ( loopdayadd - 1 )*1000*60*60*24 );
								getdayStr = moment(getday).format('YYYY-MM-DD');
								getPvData();
							} else if ( loopcount === loopmax ) {
								//Fromを1日進める
								getday.setTime( getday.getTime() + 1 *1000*60*60*24 );
								startStr =  moment(getday).format('YYYY-MM-DD');
								//Toは昨日まで
								getdayStr = endStr;
								getPvData();
							} else {
								deferred.resolve();
							}
						}
					).fail(
						function( jqXHR, textStatus, errorThrown ){
							qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
							//エラー通信失敗のお知らせ文を出す
							jQuery( '#cyusyutsu_notice' ).html('<span style="color: red;">' + qahml10n['ds_cyusyutsu_error2'] + '</span>');
							//ボタンを元に戻す
							jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
						}
					).always(
						function(){
						}
					);
				}
				return deferred.promise();
			}; //end of 'getPvData' difinition

			// 実際の呼び出し
			getPvData().then( function() {
				//メモリの解放後データ配列をセット
				qahm.dsSessionData = null;
				qahm.dsPvData = ary;
				//console.log( qahm.dsPvData );

				let allSessionAry = qahm.createSessionArray(ary);
				if (typeof sdayTable !== 'undefined' && sdayTable !== '') {
					sdayTable.rawDataArray = allSessionAry;
					if ( ! sdayTable.headerTableByID ) {
						sdayTable.generateTable();
					} else {
						sdayTable.updateTable(true);
					}
				}

				// heatmap
				qahm.createHeatmapList( gPastRangeStart, gPastRangeEnd );

				//ボタンを元に戻す
				jQuery( '#extraction-proc-button' ).text(qahml10n['ds_cyusyutsu_button']).prop( 'disabled', false );
				//抽出件数を表示する
				jQuery( '#cyusyutsu_notice' ).html('<span id="cyusyutsu_session_num">' + allSessionAry.length + '</span>' + qahml10n['ds_cyusyutsu_kensu']);
			});
		}
	}

	function procDateRangePicker() {
		// 実際のデータ取得呼び出し
		qahm.statsParamDefered.promise().then( function() {
			let startMoment = moment(gPastRangeStart);
			let endMoment   = moment(gPastRangeEnd);

			// コールバック変数
			function changeDateRangeCB( startMoment, endMoment ) {
				jQuery('.ds-chart-reportrange span').html(startMoment.format('ll') + ' ' + qahml10n['calender_kara'] + ' ' + endMoment.format('ll'));
			}

			let datarange_opt = {
				startDate: startMoment,
				endDate: endMoment,
				showCustomRangeLabel: true, //選択肢にカレンダーありか、なしか。
				//alwaysShowCalendars: true, //選択肢と一緒にカレンダーを開いて表示するか
				linkedCalendars: false, //２つのカレンダーを連動させるか（常に2か月表示）
				ranges: {
					[qahml10n['calender_kinou']]: [moment(qahm.dsTodayUnixTime).subtract(1, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
					[qahml10n['calender_kako7days']]: [moment(qahm.dsTodayUnixTime).subtract(7, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
					[qahml10n['calender_kako30days']]: [moment(qahm.dsTodayUnixTime).subtract(30, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
					[qahml10n['calender_kongetsu']]: [moment(qahm.dsTodayUnixTime).startOf('month'), moment(qahm.dsTodayUnixTime).endOf('month')],
					[qahml10n['calender_sengetsu']]: [moment(qahm.dsTodayUnixTime).subtract(1, 'month').startOf('month'), moment(qahm.dsTodayUnixTime).subtract(1, 'month').endOf('month')]
				},
				locale: {
					format: 'll',
					separator: ' ' + qahml10n['calender_kara'] + ' ',
					customRangeLabel: qahml10n['calender_erabu'],
					cancelLabel: qahml10n['calender_cancel'],
					applyLabel: qahml10n['calender_ok'],
				},
			};
			//minDate: moment(daysince), // the earliest date which user can choose
			if (qahm.statsParam) {
				datarange_opt.minDate = moment( qahm.statsParam[0].date, "YYYY-MM-DD" );
				datarange_opt.maxDate = moment( qahm.statsParam[qahm.statsParam.length-1].date, "YYYY-MM-DD" );
			}


			jQuery('.ds-chart-reportrange').daterangepicker(datarange_opt, changeDateRangeCB );

			// jQuery('.ds-chart-reportrange').daterangepicker({
			// 	startDate: startMoment,
			// 	endDate: endMoment,
			// 	showCustomRangeLabel: true, //選択肢にカレンダーありか、なしか。
			// 	//alwaysShowCalendars: true, //選択肢と一緒にカレンダーを開いて表示するか
			// 	linkedCalendars: false, //２つのカレンダーを連動させるか（常に2か月表示）
			// 	ranges: {
			// 		'昨日': [moment(qahm.dsTodayUnixTime).subtract(1, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
			// 		'過去7日間': [moment(qahm.dsTodayUnixTime).subtract(7, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
			// 		'過去30日間': [moment(qahm.dsTodayUnixTime).subtract(30, 'days'), moment(qahm.dsTodayUnixTime).subtract(1, 'days')],
			// 		'今月': [moment(qahm.dsTodayUnixTime).startOf('month'), moment(qahm.dsTodayUnixTime).endOf('month')],
			// 		'先月': [moment(qahm.dsTodayUnixTime).subtract(1, 'month').startOf('month'), moment(qahm.dsTodayUnixTime).subtract(1, 'month').endOf('month')]
			// 	},
			// 	//minDate: moment(daysince), // the earliest date which user can choose
			// 	minDate: (qahm.statParam)?qahm.statsParam[0].date:'',
			// 	maxDate: (qahm.statParam)?qahm.statsParam[qahm.statsParam.length-1].date:'',
			// 	locale: {
			// 		format: 'll',
			// 		separator: ' ～ ',
			// 		customRangeLabel: 'カレンダーから選ぶ',
			// 		cancelLabel: 'キャンセル',
			// 		applyLabel: 'OK',
			// 	},
			// }, changeDateRangeCB );

			//期間変更された時に変数に日時を格納
			jQuery('.ds-chart-reportrange').on('apply.daterangepicker', function(ev, picker) {
				// gPastRangeStart = picker.startDate;
				// gPastRangeEnd   = picker.endDate;
			});

			changeDateRangeCB( startMoment, endMoment );
		} );
	}

	// 抽出ボタンクリック
	function clickExtractButton() {
		jQuery( '#extraction-proc-button' ).on( 'click', function() {
			let uri         = new URL(window.location.href);
			let httpdomaina = uri.origin;
			let httpdomainb = httpdomaina + '/';
			const prefix = jQuery( 'input:radio[name="selectuser_pagematch"]:checked' ).val();
			const searchUrl = jQuery( '#jsSearchPageUrl' ).val();
			if ( prefix === 'pagematch_prefix' ) {
				if ( searchUrl === httpdomaina || searchUrl === httpdomainb ) {
					alert(qahml10n['result_cannot_be_all_pages']);
					return;
				}
			}
			jQuery( '#extraction-proc-button' ).text( qahml10n['ds_cyusyutsu_cyu'] ).prop( 'disabled', true );
			createDataTable(searchUrl, prefix);
		});
	}
	// 抽出URLクリック
	function clickExtractUrl() {
		jQuery( '#jsSearchPageUrl' ).on( 'click', function() {
			jQuery( '#js_gsession_selectuser' ).trigger('click');
		});
	}


});


/**
 *  「見たいデータを探す」のSource/Media一覧Table
 */
qahm.createSmArray = function( vr_sessions_ary ) {
	let allSmAry = [];
	let allSmTempAry = [];
	let is_session = false;
	let firstTitle = '';
	let firstTitleEl = '';
	let firstUrl   = '';
	let lastTitle = '';
	let lastTitleEl = '';
	let lastUrl   = '';
	let device = 'pc';
	let reader_id = 0;
	let pvcnt  = 0;
	let is_bounce = 0;
	let is_newuser = true;
	let last_exit_time = 0;
	let source_domain = '';
	let utm_medium = '';
	let sec_on_site = 0;

	if ( vr_sessions_ary[0] ) {
		for ( let iii = 0; iii < vr_sessions_ary.length; iii++ ) {
			//sessin start?
			for ( let jjj = 0; jjj < vr_sessions_ary[iii].length; jjj++ ) {
				if (Number(vr_sessions_ary[iii][jjj].pv) === 1) {
					is_session = true;
					reader_id = vr_sessions_ary[iii][jjj].reader_id;
					pvcnt = 1;
					sec_on_site = Number(vr_sessions_ary[iii][jjj].browse_sec);
					if (Number(vr_sessions_ary[iii][jjj].is_last) === 1) {
						is_bounce = 1;
					} else {
						is_bounce = 0;
					}
				} else {
					pvcnt++;
					sec_on_site = sec_on_site + Number(vr_sessions_ary[iii][jjj].browse_sec);
				}
				if (is_session) {
					//last ?
					if (Number(vr_sessions_ary[iii][jjj].is_last) === 1) {
						is_session = false;
						is_newuser = Number(vr_sessions_ary[iii][jjj].is_newuser);
						last_exit_time = (Date.parse(vr_sessions_ary[iii][jjj].access_time)) / 1000;
						source_domain = vr_sessions_ary[iii][jjj].source_domain;
						utm_medium = vr_sessions_ary[iii][jjj].utm_medium;
						if ( utm_medium === undefined ) {
							utm_medium = '';
						}

						//make array
						let smAry = [source_domain, utm_medium, [reader_id], is_newuser, 1, is_bounce, pvcnt, sec_on_site];
						let is_find = false;
						if ( allSmTempAry.length !== 0 ) {
							//search
							for ( let sss = 0; sss < allSmTempAry.length; sss++ ) {
								if ( allSmTempAry[sss][0] === source_domain && allSmTempAry[sss][1] === utm_medium ) {
									is_find = true;
									allSmTempAry[sss][2].push(reader_id);
									allSmTempAry[sss][3] += is_newuser;
									allSmTempAry[sss][4] += 1;
									allSmTempAry[sss][5] += is_bounce;
									allSmTempAry[sss][6] += pvcnt;
									allSmTempAry[sss][7] += sec_on_site;
									break;
								}
							}
						}
						if ( !is_find ) {
							allSmTempAry.push(smAry);
						}
					}
				}
			}
		}
	}
	for ( let sss = 0; sss < allSmTempAry.length; sss++ ) {
		let sessions   = allSmTempAry[sss][4];
		let uniquser   = 0;
		let bouncerate = 0;
		let pageperssn = 0;
		let avgsecsite = 0;

		uniquser   = Array.from(new Set(allSmTempAry[sss][2])).length;
		bouncerate = qahm.roundToX(allSmTempAry[sss][5] / sessions * 100 , 1);
		pageperssn = qahm.roundToX(allSmTempAry[sss][6] / sessions , 2);
		avgsecsite = qahm.roundToX(allSmTempAry[sss][7] / sessions , 0);

		allSmTempAry[sss][2] = uniquser;
		allSmTempAry[sss][5] = bouncerate;
		allSmTempAry[sss][6] = pageperssn;
		allSmTempAry[sss][7] = avgsecsite;
    }
	return allSmTempAry;
};

/**
 *  「見たいデータを探す」のlp一覧Table
 */
qahm.createLpArray = function( vr_sessions_ary ) {
	let allLpAry = [];
	let is_session = false;
	let firstTitle = '';
	let firstTitleEl = '';
	let firstUrl   = '';
	let lastTitle = '';
	let lastTitleEl = '';
	let lastUrl   = '';
	let wp_page_id = 0;
	let device = 'pc';
	let reader_id = 0;
	let pvcnt  = 0;
	let last_exit_time = 0;
	let source_domain = '';
	let utm_source = '';
	let sec_on_site = 0;
    let replayTdHtml = '';
	let is_bounce = 0;
    //url host
    let uri        = new URL(window.location.href);
    let httplen    = uri.origin.length;
	let is_newuser = 1;
	let page_id = 0;

	if ( vr_sessions_ary[0] ) { //ym wrote
		for ( let iii = 0; iii < vr_sessions_ary.length; iii++ ) {
			//sessin start?
			for ( let jjj = 0; jjj < vr_sessions_ary[iii].length; jjj++ ) {
				if (Number(vr_sessions_ary[iii][jjj].pv) === 1) {
					is_session = true;
					page_id = vr_sessions_ary[iii][jjj].page_id;
					firstTitle = vr_sessions_ary[iii][jjj].title;
					firstTitleEl = firstTitle.slice(0, 80) + '...';
					firstUrl = vr_sessions_ary[iii][jjj].url;
					firstUrl = firstUrl.slice( httplen );
					reader_id = vr_sessions_ary[iii][jjj].reader_id;
					pvcnt = 1;
					sec_on_site = Number(vr_sessions_ary[iii][jjj].browse_sec);
					replayTdHtml = '';
					if (Number(vr_sessions_ary[iii][jjj].is_last) === 1) {
						is_bounce = 1;
					} else {
						is_bounce = 0;
					}


				} else {
					pvcnt++;
					sec_on_site = sec_on_site + Number(vr_sessions_ary[iii][jjj].browse_sec);
				}
				if (is_session) {

					//last ?
					if (Number(vr_sessions_ary[iii][jjj].is_last) === 1) {
						is_session = false;
						is_newuser = Number(vr_sessions_ary[iii][jjj].is_newuser);
						source_domain = vr_sessions_ary[iii][jjj].source_domain;
						utm_source = vr_sessions_ary[iii][jjj].utm_medium;

						//make array
						let lpAry = [page_id, firstTitleEl, firstUrl, 1, is_newuser, is_newuser, is_bounce, pvcnt, sec_on_site];
						let is_find = false;
						if ( allLpAry.length !== 0 ) {
							//search
							for ( let sss = 0; sss < allLpAry.length; sss++ ) {
								if ( allLpAry[sss][0] === page_id ) {
									is_find = true;
									allLpAry[sss][3] += 1;
									allLpAry[sss][4] += is_newuser;
									allLpAry[sss][5] += is_newuser;
									allLpAry[sss][6] += is_bounce;
									allLpAry[sss][7] += pvcnt;
									allLpAry[sss][8] += sec_on_site;
									break;
								}
							}
						}
						if ( !is_find ) {
							allLpAry.push(lpAry);
						}
					}
				}
			}
		}
	}
	for ( let sss = 0; sss < allLpAry.length; sss++ ) {
		let sessions   = allLpAry[sss][3];
		let newwerrate = 0;
		let bouncerate = 0;
		let pageperssn = 0;
		let avgsecsite = 0;

		newwerrate = qahm.roundToX(allLpAry[sss][4] / sessions * 100, 1);
		bouncerate = qahm.roundToX(allLpAry[sss][6] / sessions * 100, 1);
		pageperssn = qahm.roundToX(allLpAry[sss][7] / sessions , 2);
		avgsecsite = qahm.roundToX(allLpAry[sss][8] / sessions , 0);

		allLpAry[sss][4] = newwerrate;
		allLpAry[sss][6] = bouncerate;
		allLpAry[sss][7] = pageperssn;
		allLpAry[sss][8] = avgsecsite;
    }
	return allLpAry;
}

/**
 *  「見たいデータを探す」のsession一覧Table
 */
qahm.createSessionArray = function( vr_view_ary ) {
	let allSessionAry = [];
	let is_session = false;
	let firstTitle = '';
	let firstTitleEl = '';
	let firstUrl   = '';
	let lastTitle = '';
	let lastTitleEl = '';
	let lastUrl   = '';
	let device = 'pc';
	let reader_id = 0;
	let pvcnt  = 0;
	let last_exit_time = 0;
	let source_domain = '';
	let utm_source = '';
	let sec_on_site = 0;
    let replayTdHtml = '';
    let freeMessage = {title:qahml10n['ds_free_plan_msg1'], text:qahm.sprintfAry( qahml10n['ds_free_plan_msg2'], '<a href=&quot;https://quarka.org/en/#plans; target=_blank>', '</a>') };

	if ( vr_view_ary[0] ) { //ym wrote
		for ( let iii = 0; iii < vr_view_ary.length; iii++ ) {
			//sessin start?
			for ( let jjj = 0; jjj < vr_view_ary[iii].length; jjj++ ) {
				if (Number(vr_view_ary[iii][jjj].pv) === 1) {
					is_session = true;
					firstTitle = vr_view_ary[iii][jjj].title;
					firstTitleEl = firstTitle.slice(0, 80) + '...';
					firstUrl = vr_view_ary[iii][jjj].url;
					reader_id = vr_view_ary[iii][jjj].reader_id;
					switch (Number(vr_view_ary[iii][jjj].device_id)) {
						case 2:
							device = 'tab';
							break;
						case 3:
							device = 'smp';
							break;
						case 1:
						default:
							device = 'pc';
							break;
					}
					pvcnt = 1;
					sec_on_site = Number(vr_view_ary[iii][jjj].browse_sec);
					replayTdHtml = '';
				} else {
					pvcnt++;
					sec_on_site = sec_on_site + Number(vr_view_ary[iii][jjj].browse_sec);
				}
				if (is_session) {
					//再生ボタンを一番若いpvで作成する。但し日付が古い時は暫定的なボタンを表示
					if (Number(vr_view_ary[iii][jjj].is_raw_e) === 1) {
						if (replayTdHtml === '') {
							let dataAttr = ' data-reader_id="' + vr_view_ary[iii][jjj].reader_id.toString() + '" data-replay_id="' + vr_view_ary[iii][jjj].pv.toString() + '" data-access_time="' + vr_view_ary[iii][jjj].access_time + '"';
							replayTdHtml = '<span class="icon-replay" ' + dataAttr + '><i class="fa fa-play-circle fa-2x"></i></span>';
						}
					}

					//last ?
					if (Number(vr_view_ary[iii][jjj].is_last) === 1) {
						is_session = false;
						lastTitle = vr_view_ary[iii][jjj].title;
						lastTitleEl = lastTitle.slice(0, 80) + '...';
						lastUrl = vr_view_ary[iii][jjj].url;
						last_exit_time = (Date.parse(vr_view_ary[iii][jjj].access_time)) / 1000;
						source_domain = vr_view_ary[iii][jjj].source_domain;
						utm_source = vr_view_ary[iii][jjj].utm_medium;

						//make array
						let sessionAry = [device, last_exit_time, reader_id, firstUrl, firstTitle, firstTitleEl, lastUrl, lastTitle, lastTitleEl, utm_source, source_domain, pvcnt, sec_on_site, replayTdHtml];
						allSessionAry.push(sessionAry);
					}
				}
			}
		}
	} //endif(ym wrote)
	return allSessionAry;
}

let sdayTable = '';
qahm.createSearchSessionTable = function() {
	sdayTable = new QATableGenerator();
	sdayTable.dataObj.body.header.push({title:qahml10n['table_tanmatsu'],type:'string',colParm:'style="width: 7%;"'});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_ridatsujikoku'],type: 'unixtime',colParm:'style="width: 15%;"',tdParm:'style="text-align:center;"', format:(unixtime) => {let date = new Date(1000*unixtime);
		let isostr = date.toISOString();
	// return  (isostr.slice(0,10) + ' ' + isostr.slice(11,19));
		let year = date.getFullYear();
		let month = Number(date.getMonth()) + 1;
		let day = date.getDate();
		let hour = date.getHours();
		year  = year.toString();
		month = ('0' + month.toString()).slice(-2);
		day   = ('0' + day.toString()).slice(-2);
		hour   = ('0' + hour.toString()).slice(-2);
		return `${year}-${month}-${day} ${hour}:${isostr.slice(14,19)}`;
	}});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_id'],type: 'number',colParm:'style="width: 10%;"',thParm:'class="qahm-tooltip" data-qahm-tooltip="' + qahml10n['table_id_tooltip'] + '"',tdParm:'style="text-align:right;"'});
	sdayTable.dataObj.body.header.push({isHide:true});
	sdayTable.dataObj.body.header.push({isHide:true});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_1page_me'],type: 'string',colParm:'style="width: 18%;"',tdHtml:'<a href="%!03" target="_blank" class="qahm-tooltip" data-qahm-tooltip="%!03">%!me</a>'});
	sdayTable.dataObj.body.header.push({isHide:true});
	sdayTable.dataObj.body.header.push({isHide:true});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_ridatsu_page'],type: 'string',colParm:'style="width: 18%;"',tdHtml:'<a href="%!06" target="_blank" class="qahm-tooltip" data-qahm-tooltip="%!06">%!me</a>'});
	sdayTable.dataObj.body.header.push({isHide:true});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_sanshoumoto'],type: 'string',colParm:'style="width: 15%;"',tdHtml:'<span class="qahm-tooltip" data-qahm-tooltip="%!09">%!me</span>'});
	// sdayTable.dataObj.body.header.push({title:"参照元",type: 'string',colParm:'style="width: 15%;"',format:(text, tableidx, vrowidx, visibleary) => {
	// 	let ret = text;
	// 	if (text !== 'direct') {
	// 		let referrer = visibleary[vrowidx][tableidx];
	// 		ret = `<span class="qahm-tooltip" data-qahm-tooltip="${referrer}">${text}</span>`;
	// 	}
	// 	return ret;
	// }, itemformat:(text)=>{return text;}});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_pv'],type: 'number',colParm:'style="width: 7%;"',tdParm:'style="text-align:right;"',calc:'avg'});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_site_taizaijikan'],type: 'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',calc:'avg', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	sdayTable.dataObj.body.header.push({title:qahml10n['table_saisei'],type: 'string',colParm:'style="width: 5%;"',tdParm:'style="text-align:center;"',hasFilter:false,tdHtml:'%!me'});
	sdayTable.visibleSort.index = 2;
	sdayTable.visibleSort.order = 'dsc';
	sdayTable.targetID = 'sday_table';
	sdayTable.progBarDiv = 'sday-table-progbar';
	sdayTable.progBarMode = 'embed';
	sdayTable.dataTRNowBlocks.divheight = 300;
	sdayTable.prefix = 'qasday';
	let plugindir = qahm.plugin_dir_url;
	sdayTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
}



/**
 *  ヒートマップ一覧の構築
 */
qahm.heatmapTable = '';
qahm.createSearchHeatmapTable = function() {
	qahm.heatmapTable = new QATableGenerator();
	qahm.heatmapTable.dataObj.body.header.push({isHide:true});	// page_id
	qahm.heatmapTable.dataObj.body.header.push({isHide:true});	// url
	qahm.heatmapTable.dataObj.body.header.push({title:qahml10n['table_page_title'],type:'string',colParm:'style="width: 80%;"',tdHtml:'<a href="%!01" target="_blank" class="qahm-tooltip" data-qahm-tooltip="%!01">%!me</a>'});
	qahm.heatmapTable.dataObj.body.header.push({title:qahml10n['table_session'],type: 'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'<span data-device_name="all" data-heatmap_index="%!00">%!me</span>'});
	qahm.heatmapTable.dataObj.body.header.push({title:qahml10n['table_heatmap'],type: 'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',hasFilter:false,tdHtml:
		'<span class="dashicons dashicons-desktop qahm-heatmap-link" data-device_name="dsk" data-page_id="%!00"></span>' +
		'<span class="dashicons dashicons-tablet qahm-heatmap-link" data-device_name="tab" data-page_id="%!00"></span>' +
		'<span class="dashicons dashicons-smartphone qahm-heatmap-link" data-device_name="smp" data-page_id="%!00"></span>'});
	qahm.heatmapTable.visibleSort.index = 3;
	qahm.heatmapTable.visibleSort.order = 'dsc';
	qahm.heatmapTable.targetID = 'heatmap-table';
	qahm.heatmapTable.progBarDiv = 'heatmap-table-progbar';
	qahm.heatmapTable.progBarMode = 'embed';
	qahm.heatmapTable.tableScaleBtnDiv = 'heatmap-table-scale-button';
	qahm.heatmapTable.dataTRNowBlocks.divheight = 300;
	qahm.heatmapTable.prefix = 'qaheatmap';
	let plugindir = qahm.plugin_dir_url;
	qahm.heatmapTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
}


/**
 *  ヒートマップ一覧の構築
 */
qahm.createHeatmapList = function( startDate, endDate ) {
	qahm.hmList = {};
	let dsData  = null;

	// ここでいい感じに整形できるならしたい
	if ( qahm.dsPvData ) {
		dsData = qahm.dsPvData;
	} else {
		dsData = qahm.dsSessionData;
	}

	const startTime = performance.now();
	for ( let i = 0, paramLen = dsData.length; i < paramLen; i++ ) {
		if ( dsData[i] ) {
			for ( let j = 0, testLen = dsData[i].length; j < testLen; j++ ) {
				let param = dsData[i][j];
				if ( ! param.is_raw_p && ! param.is_raw_c ) {
					continue;
				}

				// ハッシュがつくURLは今のところ除外
				if( param.url.indexOf('#') !== -1 ) {
					continue;
				}

				let accessTime = new Date( param.access_time );
				if( startDate > accessTime || endDate < accessTime ) {
					continue;
				}

				// pvData構築
				for ( const devType in qahm.devices ) {
					if( qahm.devices[devType]['id'] === parseInt( param.device_id ) ) {
						let devName = qahm.devices[devType]['name'];
						break;
					}
				}

				/* 連想配列に直接代入（オブジェクトパターン） */
				// 存在チェック
				let existHMVer = false;
				if( qahm.hmList[param.page_id] && qahm.hmList[param.page_id].verIdx && param.version_no ) {
					if ( qahm.hmList[param.page_id].verInfo[param.version_no] ) {
						qahm.hmList[param.page_id].verInfo[param.version_no][param.device_id].dataNum++;
						qahm.hmList[param.page_id].verInfo[param.version_no][param.device_id].verId = param.version_id;

						let accessTime = new Date( param.access_time );
						if ( qahm.hmList[param.page_id].verInfo[param.version_no].startDate > accessTime ) {
							qahm.hmList[param.page_id].verInfo[param.version_no].startDate = accessTime;
						} else if ( qahm.hmList[param.page_id].verInfo[param.version_no].endDate < accessTime ) {
							qahm.hmList[param.page_id].verInfo[param.version_no].endDate = accessTime;
						}
						existHMVer = true;
					}

					if ( qahm.hmList[param.page_id].verIdx < param.version_no ) {
						qahm.hmList[param.page_id].verIdx = param.version_no;
					}
				}

				if ( ! existHMVer ) {
					// アクセス速度のことを考慮して連想配列にはpage_idを入れている
					if ( ! qahm.hmList[param.page_id] ) {
						qahm.hmList[param.page_id] = {};
						qahm.hmList[param.page_id].url     = param.url;
						qahm.hmList[param.page_id].title   = param.title;
						qahm.hmList[param.page_id].verIdx  = param.version_no;
						qahm.hmList[param.page_id].verInfo = {};
					}

					if ( param.version_no ) {
						qahm.hmList[param.page_id].verInfo[param.version_no] = {};
						for ( const devType in qahm.devices ) {
							qahm.hmList[param.page_id].verInfo[param.version_no][qahm.devices[devType]['id']] = {};
							qahm.hmList[param.page_id].verInfo[param.version_no][qahm.devices[devType]['id']].dataNum = 0;
							qahm.hmList[param.page_id].verInfo[param.version_no][qahm.devices[devType]['id']].verId = null;
						}
						qahm.hmList[param.page_id].verInfo[param.version_no][param.device_id].dataNum++;
						qahm.hmList[param.page_id].verInfo[param.version_no][param.device_id].verId     = param.version_id;
						let accessTime = new Date( param.access_time );
						qahm.hmList[param.page_id].verInfo[param.version_no].startDate = accessTime;
						qahm.hmList[param.page_id].verInfo[param.version_no].endDate   = accessTime;
					} else {
						if ( ! qahm.hmList[param.page_id].verIdx ) {
							qahm.hmList[param.page_id].verIdx = null;
						}
					}
				}
			}
		} else {
			continue;
		}
	}

	const endTime = performance.now(); // 終了時間
	// console.log( endTime - startTime ); // 何ミリ秒かかったかを表示する


	// データ数順にソート
	/*
	qahm.hmList = Object.entries( qahm.hmList );
	qahm.hmList.sort(
		function( a, b ) {
			let valA = 0, valB = 0;
			for ( const devType in qahm.devices ) {
				valA += a[1]['verIdx'] ? a[1]['verInfo'][a[1]['verIdx']][qahm.devices[devType]['id']]['dataNum'] : 0;
				valB += b[1]['verIdx'] ? b[1]['verInfo'][b[1]['verIdx']][qahm.devices[devType]['id']]['dataNum'] : 0;
			}
			return( valA < valB ? 1: -1 );
		}
	);

	//qahm.hmLoadDef.promise().then( function () {
	// リスト形成
	let hmHtml = '<thead>' +
		'<th scope="col">ページ（タイトル）</th>' +
		'<th scope="col">QA Heatmap 分析（データ数）</th>' +
		'<th scope="col">ページバージョン : データ期間</th>' +
		'</thead>' +
		'<tbody>';

	for ( let hmIdx = 0, hmLen = qahm.hmList.length; hmIdx < hmLen; hmIdx++ ) {
		let hm      = qahm.hmList[hmIdx][1];
		let title   = omitStr( hm.title, 40, '…' );

		hmHtml += '<tr>';
		// タイトル
		hmHtml += '<td><a href="' + hm.url + '" target="_blank">' + title + '</a></td>';

		// データ数
		hmHtml += '<td id="qahm-heatmap-data-num-' + hmIdx + '">';
		let verInfo = null;
		if ( hm.verIdx ) {
			verInfo = hm.verInfo[hm.verIdx];
		}
		hmHtml += getDeviceNumHtml( verInfo );

		hmHtml += '</td>';

		// バージョン：データ期間
		hmHtml += '<td>';
		if ( hm.verIdx !== null ) {
			hmHtml += '<select class="qahm-version-select" data-list_index="' + hmIdx + '">';
			for ( let i = hm.verIdx ; i > 0 ; i-- ) {
				let info = hm.verInfo[i];
				if ( info === undefined ) {
					continue;
				}
				hmHtml += '<option value="' + i + '">';
				hmHtml += 'ver.' + i + ' : ' + getDataPeriod( info['startDate'] ) + ' - ' + getDataPeriod( info['endDate'] );
				hmHtml += '</option>';
			}
			hmHtml += '</select>';
		}
		hmHtml += '</td>';

		hmHtml += '</tr>';
	}

	hmHtml += '</tbody>';

	jQuery( '#stats-proc-heatmap' ).empty();
	jQuery( '#table-heatmap-list' ).html( hmHtml ).attr('data-start-date', startDate ).attr('data-end-date', endDate );
	*/

	qahm.hmList = Object.entries( qahm.hmList );

	let allHeatmapAry = [];
	for ( let hmIdx = 0, hmLen = qahm.hmList.length; hmIdx < hmLen; hmIdx++ ) {
		let pageId  = qahm.hmList[hmIdx][0];
		let hm      = qahm.hmList[hmIdx][1];

		// タイトル
		//let title   = omitStr( hm.title, 40, '…' );
		//let hmTitle = '<td><a href="' + hm.url + '" target="_blank">' + title + '</a></td>';

		// データ数
		let verInfo    = null;
		let dataDsk    = 0;
		let dataTab    = 0;
		let dataSmp    = 0;

		if ( hm.verIdx ) {
			verInfo  = hm.verInfo[hm.verIdx];
			dataDsk  = parseInt( verInfo[1]['dataNum'] );
			dataTab  = parseInt( verInfo[2]['dataNum'] );
			dataSmp  = parseInt( verInfo[3]['dataNum'] );
		}
		hm.title = qahm.truncateStr( hm.title, 80 );

		allHeatmapAry.push( [
			pageId,
			hm.url,
			hm.title,
			dataDsk + dataTab + dataSmp,
			'',
		] );
	}

	if ( allHeatmapAry.length > 0 && typeof qahm.heatmapTable !== 'undefined' && qahm.heatmapTable !== '') {
		qahm.heatmapTable.rawDataArray = allHeatmapAry;
		if ( ! qahm.heatmapTable.headerTableByID ) {
			qahm.heatmapTable.generateTable();
		} else {
			qahm.heatmapTable.updateTable();
		}
	}

	function getDeviceNumHtml( verInfo ) {
		let devIcon    = [];
		let devList    = [];
		let devDataNum = [];
		let devVerId   = [];
		let devMax     = Object.keys( qahm.devices ).length;
		let devIdx     = 0;

		for ( let devKey in qahm.devices ) {
			devIcon[devIdx]    = 'dashicons-' + devKey;
			devList[devIdx]    = qahm.devices[devKey]['name'];
			devDataNum[devIdx] = 0;
			devVerId[devIdx]   = 0;
			devIdx++;
		};

		if ( verInfo ) {
			for ( devIdx = 0; devIdx < devMax; devIdx++ ) {
				devDataNum[devIdx] = verInfo[devIdx+1]['dataNum'];
				devVerId[devIdx]   = verInfo[devIdx+1]['verId'];
			}
		}

		let html = '';

		for ( let devIdx = 0; devIdx < devMax; devIdx++ ) {
			let devNotExistsStyle = 'style="opacity: 0.3; margin-right: 8px;"';
			if ( devDataNum[devIdx] > 0 ){
				html += '<a target="_blank" class="qahm-heatmap-link" data-device_name="' + devList[devIdx] + '" data-version_id="' + devVerId[devIdx] + '">';
				html += '<span class="dashicons ' + devIcon[devIdx] + '"></span>';
				html += '<span>(' + devDataNum[devIdx] + ')</span>';
				html += '</a>';
			} else {
				html += '<span ' + devNotExistsStyle + '>';
				html += '<span class="dashicons ' + devIcon[devIdx] + '"></span>';
				html += '<span>(0)</span>';
				html += '</span>';
			}
		}

		return html;
	}

	// 文字列省略。全角半角対応
	function omitStr( text, len, truncation ) {
		if (truncation === undefined) {
			truncation = '';
		}
		let text_array = text.split( '' );
		let count      = 0;
		let str        = '';
		for (i = 0; i < text_array.length; i++) {
			let n = escape( text_array[i] );
			if (n.length < 4) {
				count++;
			} else {
				count += 2;
			}
			if (count > len) {
				return str + truncation;
			}
			str += text.charAt( i );
		}
		return text;
	};

	//日付から文字列に変換する関数
	function getDataPeriod( date ) {
		let yearStr   = date.getFullYear();
		let monthStr  = date.getMonth() + 1;
		let dayStr    = date.getDate();

		// YYYY-MM-DDの形にする
		let formatStr = yearStr;
		formatStr += '-' + ('0' + monthStr).slice(-2);
		formatStr += '-' + ('0' + dayStr).slice(-2);

		return formatStr;
	};
};


// デバイスリンククリック
jQuery( document ).off( 'click', '.qahm-heatmap-link' );
jQuery( document ).on(
	'click',
	'.qahm-heatmap-link',
	function(){
		let startMoment = moment(gPastRangeStart);
		let endMoment   = moment(gPastRangeEnd);
		let startDate   = startMoment.format('YYYY-MM-DD HH:mm:ss');
		let endDate     = endMoment.format('YYYY-MM-DD HH:mm:ss');
		let pageId      = jQuery( this ).data( 'page_id' );
		let deviceName  = jQuery( this ).data( 'device_name' );

		qahm.createCapZero( startDate, endDate, pageId, deviceName, true );
	}
);


// 再生ボタンクリック
jQuery( document ).on( 'click', '.icon-replay', function(){
	qahm.showLoadIcon();

	let start_time = new Date().getTime();
	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action':      'qahm_ajax_create_replay_file_to_data_base',
				'reader_id':   jQuery( this ).data( 'reader_id' ),
				'replay_id':   jQuery( this ).data( 'replay_id' ),
				'access_time': jQuery( this ).data( 'access_time' ),
			},
		}
	).done(
		function( data ){
			// 最低読み込み時間経過後に処理実行
			let now_time  = new Date().getTime();
			let load_time = now_time - start_time;
			let min_time  = 400;

			if ( load_time < min_time ) {
				// ロードアイコンを削除して新しいウインドウを開く
				setTimeout(
					function(){
						window.open( data, '_blank' );
					},
					(min_time - load_time)
				);
			} else {
				window.open( data, '_blank' );
			}
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			qahm.hideLoadIcon();
		}
	);
});


/**------------------------------------------
 * ページデータの表示
 */
jQuery( document ).on( 'click', '.qahm-page-data', function(){

	// 一時的にURLを表示する処理
	let url = jQuery( this ).data( 'url' );
	window.open( url, '_blank' );
	return;

	qahm.showLoadIcon();

	let startTime   = new Date().getTime();
	let startMoment = moment(gPastRangeStart);
	let endMoment   = moment(gPastRangeEnd);
	let startStr    = startMoment.format('YYYY-MM-DD');
	let endStr      = endMoment.format('YYYY-MM-DD');

	let pvJson      = null;
	let sessionJson = null;
	if ( qahm.dsPvData ) {
		pvJson = JSON.stringify( qahm.dsPvData );
	} else if ( qahm.dsSessionData ) {
		sessionJson = JSON.stringify( qahm.dsSessionData );
	} else {
		return;
	}

	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action': 'qahm_ajax_create_page_data_file',
				'view_pv_ary': pvJson,
				'view_session_ary': sessionJson,
				'page_id': jQuery( this ).data( 'page_id' ),
				'start_date': startStr,
				'end_date': endStr,
			},
		}
	).done(
		function( data ){
			// 最低読み込み時間経過後に処理実行
			let nowTime  = new Date().getTime();
			let loadTime = nowTime - startTime;
			let minTime  = 400;

			if ( loadTime < minTime ) {
				// ロードアイコンを削除して新しいウインドウを開く
				setTimeout(
					function(){
						window.open( data, '_blank' );
					},
					(minTime - loadTime)
				);
			} else {
				window.open( data, '_blank' );
			}
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			qahm.hideLoadIcon();
		}
	);
});



/**------------------------------------------
 * 抽出したPVデータのCSVダウンロード
 */
window.addEventListener('DOMContentLoaded', function() {
	//csv download
	let dsDownloadBtn = document.getElementById('ds-csv-download-btn');
	if ( dsDownloadBtn ) {
		dsDownloadBtn.addEventListener( 'click', function() {
			let gosign = window.confirm( qahml10n['ds_download_msg1'] );
			if( gosign ) {
				qahm.dsDownloadTCsv();
			}
		});
	}
});
qahm.dsDownloadTCsv = function(){
	//0だったらアラートを出してexit
	if ( ! qahm.dsPvData && ! qahm.dsSessionData[0]) {
		window.confirm( qahml10n['ds_download_error1'] );
		return
	}
	//jsonをcsv文字列に編集する
	// let qaHeaderTerms = [ 'pv_id', 'reader_id', 'UAos', 'UAbrowser', 'country', 'page_id', 'url', 'title', 'device_id', 'source_id', 'utm_source', 'source_domain', 'medium_id', 'utm_medium', 'campaign_id', 'utm_campaign', 'session_no', 'access_time', 'pv', 'speed_msec', 'browse_sec', 'is_last', 'is_newuser', 'version_id', 'is_raw_p', 'is_raw_c', 'is_raw_e' ];
	let qaHeaderTerms = [ 'reader_id', 'UAos', 'UAbrowser', 'url', 'title', 'device_id', 'utm_source', 'source_domain', 'utm_medium', 'utm_campaign', 'session_no', 'access_time', 'pv', 'speed_msec', 'browse_sec', 'is_last', 'is_newuser'];
	let jsonToTCsv = function(json, delimiter) {
		let header = qaHeaderTerms.join(delimiter) + "\n";
		let body = json.map(function(d){
			 return qaHeaderTerms.map(function(key) {
				 if ( key === 'device_id' ) {
					switch (d[key]) {
						case '1' :
							return 'pc';
						case '2' :
							return 'tab';
						case '3' :
							return 'smp';
						default :
							return '';
					}
				 } else {
					if( d[key] ) {
						return d[key];
					} else {
						return '';
					}
				}
			 }).join(delimiter);
		}).join("\n");
		return header + body;
	}
	//UTF8
	let bom = new Uint8Array([0xEF, 0xBB, 0xBF]);

	//ファイル作成
	let dsCsvParam;
	if ( qahm.dsPvData ) {
		dsCsvParam = qahm.dsPvData.flat(1);
	} else if ( qahm.dsSessionData ) {
		dsCsvParam = qahm.dsSessionData.flat(1);
	}
	let fileStartDate = new Date(gPastRangeStart);
	let fileEndDate = new Date(gPastRangeEnd);
	let noDataMsg = '';

	if ( dsCsvParam.length > 0 ) {
		let fileName = 'QA_FilterdData_' + moment(fileStartDate).format('YYYYMMDD') + '-' + moment(fileEndDate).format('YYYYMMDD');
		let csvData = jsonToTCsv( dsCsvParam, '\t');
//		let csvData = jsonToTCsv( dsCsvParam, ',');

		//出力ファイル名
		let exportedFilename = (fileName || 'export') + '.tsv';
//		let exportedFilename = (fileName || 'export') + '.csv';
		//BLOBに変換
		let blob = new Blob([ bom, csvData ], { 'type' : 'text/tsv' });
//		let blob = new Blob([ bom, csvData ], { 'type' : 'text/csv' });


		let downloadLink = document.createElement('a');
		downloadLink.download = exportedFilename;
		downloadLink.href = URL.createObjectURL(blob);
		downloadLink.dataset.downloadurl = ['text/plain', downloadLink.download, downloadLink.href].join(':'); //いる？いらない？　HTML要素に追加されたカスタム属性のデータを表す。これらの属性はカスタムデータ属性と呼ばれており、 HTML とその DOM 表現との間で、固有の情報を交換できるようにします。すべてのカスタムデータは、その属性を設定した要素の HTMLElement インターフェイスを通して使用することができます。 HTMLElement.dataset プロパティでカスタムデータにアクセスできます。
		downloadLink.click();

		URL.revokeObjectURL(downloadLink.href);
		
	} else {
		noDataMsg += qahml10n['ds_download_done_nodata'];
	}

	if( noDataMsg ) {
		alert(noDataMsg);
	}
}