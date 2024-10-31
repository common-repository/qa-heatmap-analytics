var qahm = qahm || {};

qahm.updateRealtimeListCnt = 0;
qahm.updateSessionNumCnt   = 0;

var tdayTable = '';
window.addEventListener('DOMContentLoaded', function() {
// window.onload = function () {
	tdayTable = new QATableGenerator();
	tdayTable.dataObj.body.header.push({title:qahml10n['table_tanmatsu'],type:'string',colParm:'style="width: 10%;"'});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_ridatsujikoku'],type: 'unixtime',colParm:'style="width: 15%;"',tdParm:'style="text-align:center;"', format:(unixtime) => {let date = new Date(1000*unixtime);
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
	tdayTable.dataObj.body.header.push({isHide:true});
	tdayTable.dataObj.body.header.push({isHide:true});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_1page_me'],type: 'string',colParm:'style="width: 20%;"',tdHtml:'<a href="%!02" target="_blank" class="qahm-tooltip" data-qahm-tooltip="%!03">%!me</a>'});
	tdayTable.dataObj.body.header.push({isHide:true});
	tdayTable.dataObj.body.header.push({isHide:true});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_ridatsu_page'],type: 'string',colParm:'style="width: 20%;"',tdHtml:'<a href="%!05" target="_blank" class="qahm-tooltip" data-qahm-tooltip="%!06">%!me</a>'});
	tdayTable.dataObj.body.header.push({isHide:true});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_sanshoumoto'],type: 'string',colParm:'style="width: 15%;"',format:(text, tableidx, vrowidx, visibleary) => {
		let ret = text;
		if (text !== 'direct') {
			let referrer = visibleary[vrowidx][tableidx];
			ret = `<a href="//${referrer}" target="_blank" rel="noopener" class="qahm-tooltip" data-qahm-tooltip="${referrer}">${text}</a>`;
		}
		return ret;
	}, itemformat:(text)=>{return text;}});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_pv'],type: 'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',calc:'avg'});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_site_taizaijikan'],type: 'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',calc:'avg', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	tdayTable.dataObj.body.header.push({title:qahml10n['table_saisei'],type: 'string',colParm:'style="width: 5%;"',tdParm:'style="text-align:center;"',hasFilter:false,tdHtml:'<span class="icon-replay" data-work_base_name="%!me"><i class="fa fa-play-circle fa-2x"></i></span></td>'});
	tdayTable.visibleSort.index = 2;
	tdayTable.visibleSort.order = 'dsc';
	tdayTable.targetID = 'tday_table';
	tdayTable.progBarDiv = 'tday-table-progbar';
	tdayTable.dataTRNowBlocks.divheight = 500;
	tdayTable.prefix = 'qatday';
	let plugindir = qahm.plugin_dir_url;
	tdayTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';


	if ( typeof qahm.updateRealtimeList === 'function' ) {
		qahm.updateRealtimeList();
		setInterval( qahm.updateRealtimeList, 1000 * 60 );
	} else {
		setTimeout( qahm.updateRealtimeList, 1000 * 5 );
		setInterval( qahm.updateRealtimeList, 1000 * 60 );
	}
});

qahm.openReplayView = function() {
	jQuery( document ).on( 'click', '.icon-replay', function(){
		qahm.showLoadIcon();

		let start_time = new Date().getTime();
		jQuery.ajax(
			{
				type: 'POST',
				url: qahm.ajax_url,
				dataType : 'text',
				data: {
					'action'        : 'qahm_ajax_create_replay_file_to_raw_data',
					'work_base_name': jQuery( this ).data( 'work_base_name' ),
					'replay_id'     : 1,
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
}

qahm.updateSessionNum = function() {
	if ( qahm.updateSessionNumCnt > 0 ) {
		return;
	}
	qahm.updateSessionNumCnt++;

	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action' : 'qahm_ajax_get_session_num',
			},
		}
	).done(
		function( data ){
			if ( data ) {
                jQuery('#session_num').text(data['session_num']);
                jQuery('#session_num_1min').text(data['session_num_1min']);
            }
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			jQuery( '#session_num' ).text( 'please reload' );
			jQuery( '#session_num_1min' ).text( 'please reload' );
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			qahm.updateSessionNumCnt--;
		}
	);
}


qahm.updateRealtimeList = function() {
	if ( qahm.updateRealtimeListCnt > 0 ) {
		return;
	}
	qahm.updateRealtimeListCnt++;

	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action' : 'qahm_ajax_get_realtime_list',
			},
		}
	).done(
		function( data ){
			if ( ! data ) {
				return;
			}
			if (typeof tdayTable !== 'undefined' && tdayTable !== '') {
				if ( data['realtime_list'].length > 0 ) {
					tdayTable.rawDataArray = data['realtime_list'];
					//table
					if ( ! tdayTable.headerTableByID ) {
						jQuery( '#update_time' ).hide().text(data['update_time']).fadeIn(4000,'swing');
						tdayTable.generateTable();
					} else {
						if ( tdayTable.isNoCheck() && tdayTable.countActiveFilterBoxes() === 0 && tdayTable.isScrolled() === false ) {
							jQuery('#update_time').hide().text(data['update_time']).fadeIn(4000, 'swing');
							tdayTable.updateTable();
						}
					}
					//graph
					let now = new Date();
					let nowHour = now.getHours();
					let zerojiUnixtime = new Date( now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0 )/1000;
					let eachHourPvsAry = [];
					let eachHourLabel = [];
					for ( let hhh = 23; 0 <= hhh; hhh-- ) {
						if ( hhh > nowHour ) {
							eachHourPvsAry[hhh] = null;
							eachHourLabel.push( hhh.toString() );
						} else if ( hhh === nowHour ) {
							eachHourPvsAry[hhh] = 0;
							eachHourLabel.push( '(now)' + hhh.toString() );
						} else {
							eachHourPvsAry[hhh] = 0;
							eachHourLabel.push( hhh.toString() );
						}
					}


					for ( let iii = 0; iii < data['realtime_list'].length; iii++ ) {
						for ( let hhh = nowHour; 0 <= hhh; hhh-- ) {
							if ( zerojiUnixtime + hhh * 3600 < Number( data['realtime_list'][iii][1] ) ) {
								++eachHourPvsAry[hhh];
								break;
							}
						}
					}

					let realChart;

					//clear pre-chart when updating
					if ( realChart !== undefined ) {
						qahm.clearPreChart( realChart );
						qahm.resetCanvas( 'realtime' );
					}
					
					let ctxreal = document.getElementById('realtime').getContext('2d');
					realChart = new Chart(ctxreal, {
						type: 'bar',
						data: {
						labels: eachHourLabel.reverse(),
						datasets: [{
							label: qahml10n['graph_hourly_sessions'],
							fill: false,
							lineTension: 0,
							data: eachHourPvsAry,
							backgroundColor: function(context) {
								var index = context.dataIndex;
								return index == nowHour ? '#FCC800' : '#69A4E2'; // draw hour of now in 'sun-flower'-yellow             
							},
						}],
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							title: {
								display: true,
								text: qahml10n['sessions_today'],
							},
							legend: {
								display: false,
								labels: {
									fontSize: 9
								},
							},
							scales: {
								xAxes: [{
									scaleLabel: {
										display: true,
										labelString: qahml10n['graph_hours'],
										fontColor: "#black",
										fontSize: 12
									}
								}],
								yAxes: [{
									scaleLabel: {
										display: true,
										labelString: qahml10n['graph_sessions'],
										fontColor: "black",
										fontSize: 12
									},
									ticks: {
										min: 0,
									},
									beforeBuildTicks: function(axis) {
										if( axis.max <= 5 ) {
											axis.max = 5;
											axis.options.ticks.stepSize = 1;
										}									
									},
								}],
							}
						},
					});
				}
			}
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			jQuery( '#update_time' ).text( 'please reload' );
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			qahm.updateRealtimeListCnt--;
		}
	);
}


jQuery(
	function(){

		qahm.openReplayView();
		qahm.updateSessionNum();

		setInterval( qahm.updateSessionNum, 1000 * 10 );
	}
);


/**-------------------------------
 * to clear the chart
 */
 qahm.clearPreChart = function(chartVar) {
	if ( typeof chartVar !== 'undefined' ) {
		chartVar.destroy();
	}
}
qahm.resetCanvas = function(canvasId) {
  let container = document.getElementById(canvasId).parentNode;
	container.innerHTML = '&nbsp;';
	container.innerHTML = `<canvas id="${canvasId}"></canvas>`;
}