var qahm = qahm || {};
qahm.heatmapChacheDefered = new jQuery.Deferred();
qahm.statsParamDefered = new jQuery.Deferred();
qahm.pvtermStartDefered = new jQuery.Deferred();
qahm.adParamDefered = new jQuery.Deferred();
qahm.apParamDefered = new jQuery.Deferred();
qahm.lpParamDefered = new jQuery.Deferred();
qahm.postParamDefered = new jQuery.Deferred();
//new each table have to connect server
qahm.sequentDefered = new jQuery.Deferred();
qahm.nowAjaxStep = 0;
qahm.pvterm_start_date = 0;

//graph color
qahm.graphColorBase  = ['#69A4E2', '#BAD6F4', '#31356E',  '#2F5F98'];
qahm.graphColorful   = ['#69A4E2', ];
qahm.graphColorBaseA = ['rgba(105, 164, 226, 1)', 'rgba(186, 214, 244, 1)', 'rgba(49, 53, 110, 1)',  'rgba(47, 95, 152, 1)'];
qahm.graphColorGoals = ['rgba(230, 230, 126, 1)', 'rgba(201, 201, 93, 1)', 'rgba(164, 164, 76, 1)',  'rgba(111, 111, 52, 1)', 'rgba(221, 225, 229, 1)', 'rgba(189, 197, 204, 1)',  'rgba(150, 161, 173, 1)', 'rgba(102, 109, 117, 1)', 'rgba(63, 63, 63, 1)',  'rgba(0, 0, 0, 1)' ]

qahm.colorAlfaChange = function ( rgba, alfa ) {
    let rgbaary = rgba.split(',');
    let orgalfa = rgbaary[3];
    return rgba.replace( orgalfa, alfa.toString() + ')' );
};
/**
 * 日付用変数( Date Object )
 * gTodayAM0 // 00:00:00:000
 * gYesterday	// 23:59:59:999
 *
 * (number) pastDatasetDays // ajaxでデータを持ってくる日数
 * pastDatasetStart
 *
 * (number) gPastRangeDays //カレンダー・グラフ表示する日数
 * gPastRangeStart
 * gPastRangeEnd
 */
let gPastRangeStart, gPastRangeEnd;

//for when label-date changed
let updatingChart = false;



/**------------------------------ */
//確認用
let checkFirstSetDate = false;
let checkDefaultRange = false;
let checkChangedRange= false;
let checkQahmParam = false;
let checkDataMade = false;
//let barON = false;
let checkCsvDLStep = false;
/**------------------------------ */



window.addEventListener('DOMContentLoaded', function() {
	//csv download
	let statsDownloadBtn = document.getElementById('csv-download-btn');
	if ( statsDownloadBtn ) {
		statsDownloadBtn.addEventListener( 'click', function() {
			let gosign = window.confirm( qahm.sprintfAry( qahml10n['download_msg1'], moment(gPastRangeStart).format('ll'), moment(gPastRangeEnd).format('ll') ) + '\n' + qahml10n['download_msg2'] );
			if( gosign ) {
				qahm.statsDownloadTCsv( gPastRangeStart, gPastRangeEnd );
			}
		});
	}

	let plugindir = qahm.plugin_dir_url;
	//create audience table
	audienceTable = new QATableGenerator();
	audienceTable.dataObj.body.header.push({title:qahml10n['table_user_type'],type:'string',colParm:'style="width: 10%;"'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_device_cat'],type:'string',colParm:'style="width: 10%;"'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	audienceTable.dataObj.body.header.push({isHide:true});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_session'],type:'number',colParm:'style="width: 10%;"',calc:'sum',tdParm:'style="text-align:right;"'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_goal_conversion_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_goal_completions'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	audienceTable.dataObj.body.header.push({title:qahml10n['table_goal_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	audienceTable.progBarDiv = 'pg_audienceDevice';
	audienceTable.progBarMode = 'embed';
	audienceTable.targetID = 'tb_audienceDevice';
	audienceTable.dataTRNowBlocks.divheight = 350;
	audienceTable.prefix = 'qaaudience';
	audienceTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';


	//create channel table
	channelsTable = new QATableGenerator();
	channelsTable.dataObj.body.header.push({title:qahml10n['table_channel'],type:'string',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_new_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_session'],type:'number',colParm:'style="width: 10%;"',calc:'sum',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_goal_conversion_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_goal_completions'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.dataObj.body.header.push({title:qahml10n['table_goal_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	channelsTable.progBarDiv = 'pg_channels';
	channelsTable.progBarMode = 'embed';
	channelsTable.targetID = 'tb_channels';
	channelsTable.dataTRNowBlocks.divheight = 480;
	channelsTable.visibleSort.index = 2;
	channelsTable.visibleSort.order = 'dsc';
	channelsTable.prefix = 'qachannels';
	channelsTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';

	//create source/media table
	sourceMediumTable = new QATableGenerator();
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_referrer'],type:'string',colParm:'style="width: 30%;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_media'],type:'string',colParm:'style="width: 10%;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_new_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_session'],colParm:'style="width: 10%;"',calc:'sum',type:'number',tdParm:'style="text-align:right;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_goal_conversion_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_goal_completions'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	sourceMediumTable.dataObj.body.header.push({title:qahml10n['table_goal_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	sourceMediumTable.progBarDiv = 'pg_sourceMedium';
	sourceMediumTable.progBarMode = 'embed';
	sourceMediumTable.targetID = 'tb_sourceMedium';
	sourceMediumTable.dataTRNowBlocks.divheight = 300;
	sourceMediumTable.visibleSort.index = 3;
	sourceMediumTable.visibleSort.order = 'dsc';
	sourceMediumTable.prefix = 'qasourceMedium';
	sourceMediumTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';

	//create lp table
	landingpageTable = new QATableGenerator();
	landingpageTable.dataObj.body.header.push({isHide:true});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_title'],type:'string',colParm:'style="width: 20%;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_url'],type:'string',colParm:'style="width: 10%;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_new_session_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_new_user'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_goal_conversion_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_goal_completions'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_goal_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_edit'],type:'string',colParm:'style="width: 5%;"',hasFilter:false,tdHtml:'<a href="%!me" target="_blank"><i class="fas fa-edit"></i></a>'});
	landingpageTable.dataObj.body.header.push({title:qahml10n['table_heatmap'],type:'string',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',hasFilter:false});
	landingpageTable.progBarDiv = 'pg_landingpage';
	landingpageTable.progBarMode = 'embed';
	landingpageTable.targetID = 'tb_landingpage';
	landingpageTable.dataTRNowBlocks.divheight = 300;
	landingpageTable.visibleSort.index = 3;
	landingpageTable.visibleSort.order = 'dsc';
	landingpageTable.prefix = 'qalandingpage';
	landingpageTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';



	//create growth page table
	growthpageTable = new QATableGenerator();
	growthpageTable.dataObj.body.header.push({isHide:true});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_title'],type:'string',colParm:'style="width: 30%;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_url'],type:'string',colParm:'style="width: 15%;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_media'],type:'string',colParm:'style="width: 10%;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_past_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 10%;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_recent_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 10%;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_growth_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_goal_conversion_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_goal_completions'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_goal_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	growthpageTable.dataObj.body.header.push({title:qahml10n['table_edit'],type:'string',colParm:'style="width: 5%;"',hasFilter:false,tdHtml:'<a href="%!me" target="_blank"><i class="fas fa-edit"></i></a>'});
    growthpageTable.dataObj.body.header.push({title:qahml10n['table_heatmap'],type:'string',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',hasFilter:false});
	growthpageTable.progBarDiv = 'pg_growthpage';
	growthpageTable.progBarMode = 'embed';
	growthpageTable.targetID = 'tb_growthpage';
	growthpageTable.dataTRNowBlocks.divheight = 300;
	growthpageTable.visibleSort.index = 5;
	growthpageTable.visibleSort.order = 'dsc';
	growthpageTable.prefix = 'qagrowthpage';
	growthpageTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';


	//create all page table
	allpageTable = new QATableGenerator();
	allpageTable.dataObj.body.header.push({isHide:true});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_title'],type:'string',colParm:'style="width: 20%;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_url'],type:'string',colParm:'style="width: 15%;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_page_view_num'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_page_visit_num'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_page_avg_stay_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_entrance_num'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_exit_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_page_value'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_edit'],type:'string',colParm:'style="width: 5%;"',hasFilter:false,tdHtml:'<a href="%!me" target="_blank"><i class="fas fa-edit"></i></a>'});
	allpageTable.dataObj.body.header.push({title:qahml10n['table_heatmap'],type:'string',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"',hasFilter:false});
	allpageTable.progBarDiv = 'pg_allpage';
	allpageTable.progBarMode = 'embed';
	allpageTable.targetID = 'tb_allpage';
	allpageTable.dataTRNowBlocks.divheight = 300;
	allpageTable.visibleSort.index = 3;
	allpageTable.visibleSort.order = 'dsc';
	allpageTable.prefix = 'qaallpage';
	allpageTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';

    //goal table
	//create source/media table
	goalsmTable = new QATableGenerator();
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_referrer'],type:'string',colParm:'style="width: 30%;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_media'],type:'string',colParm:'style="width: 10%;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_new_user'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_session'],colParm:'style="width: 10%;"',calc:'sum',type:'number',tdParm:'style="text-align:right;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"',tdHtml:'%!me%'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',colParm:'style="width: 10%;"',tdParm:'style="text-align:right;"'});
	goalsmTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	goalsmTable.progBarDiv = 'pg_goalsm';
	goalsmTable.progBarMode = 'embed';
	goalsmTable.targetID = 'tb_goalsm';
	goalsmTable.dataTRNowBlocks.divheight = 300;
	goalsmTable.visibleSort.index = 3;
	goalsmTable.visibleSort.order = 'dsc';
	goalsmTable.prefix = 'qagoalsm';
	goalsmTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';




	//create lp table
	goallpTable = new QATableGenerator();
	goallpTable.dataObj.body.header.push({isHide:true});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_title'],type:'string',colParm:'style="width: 20%;"'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_url'],type:'string',colParm:'style="width: 10%;"'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_new_session_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_new_user'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_bounce_rate'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"',tdHtml:'%!me%'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_page_session'],type:'number',tdParm:'style="text-align:right;"',colParm:'style="width: 9%;"'});
	goallpTable.dataObj.body.header.push({title:qahml10n['table_avg_session_time'],type:'second',colParm:'style="width: 10%;"',tdParm:'style="text-align:center;"', format:(totalsec, _tableidx, _vrowidx, _visibleary) => {
		let sec  = totalsec % 60;
		let remainmin = (totalsec-sec) / 60;
		let min  = remainmin % 60;
		let hour = (remainmin-min) / 60;
		const timestr = (time) => { return ( '0' + time ).slice(-2)};
		return `${timestr(hour)}:${timestr(min)}:${timestr(sec)}`;
	}});
	goallpTable.progBarDiv = 'pg_goallp';
	goallpTable.progBarMode = 'embed';
	goallpTable.targetID = 'tb_goallp';
	goallpTable.dataTRNowBlocks.divheight = 300;
	goallpTable.visibleSort.index = 3;
	goallpTable.visibleSort.order = 'dsc';
	goallpTable.prefix = 'qagoallp';
	goallpTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';


    let nrdradios = document.getElementsByName( `js_nrdGoals` );
    for ( let jjj = 0; jjj < nrdradios.length; jjj++ ) {
        nrdradios[jjj].addEventListener( 'click', qahm.changeNrdGoal );
    }
    let chradios = document.getElementsByName( `js_chGoals` );
    for ( let jjj = 0; jjj < chradios.length; jjj++ ) {
        chradios[jjj].addEventListener( 'click', qahm.changeChGoal );
    }
    let smradios = document.getElementsByName( `js_smGoals` );
    for ( let jjj = 0; jjj < smradios.length; jjj++ ) {
        smradios[jjj].addEventListener( 'click', qahm.changeSmGoal );
    }
    let lpradios = document.getElementsByName( `js_lpGoals` );
    for ( let jjj = 0; jjj < lpradios.length; jjj++ ) {
        lpradios[jjj].addEventListener( 'click', qahm.changeLpGoal );
    }
    let gwradios = document.getElementsByName( `js_gwGoals` );
    for ( let jjj = 0; jjj < gwradios.length; jjj++ ) {
        gwradios[jjj].addEventListener( 'click', qahm.changeGwGoal );
    }
    let apradios = document.getElementsByName( `js_apGoals` );
    for ( let jjj = 0; jjj < apradios.length; jjj++ ) {
        apradios[jjj].addEventListener( 'click', qahm.changeApGoal );
    }

    let gsession_selector = document.getElementsByName(`js_gsession_selector`);
    for ( let gid = 0; gid < gsession_selector.length; gid++ ) {
        gsession_selector[gid].addEventListener('click', qahm.changeSelectorGoal);
    }

});
qahm.changeNrdGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeTable( audienceTable, qahm.nrdArray[gid] );
};
qahm.changeChGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeTable( channelsTable, qahm.chArray[gid] );
};
qahm.changeSmGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeTable( sourceMediumTable, qahm.smArray[gid] );
};
qahm.changeLpGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeLandingPageTable( landingpageTable, qahm.lpArray[gid] );
};
qahm.changeGwGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeGrowthPageTable( growthpageTable, qahm.gwArray[gid] );
};
qahm.changeApGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeAllPageTable( allpageTable, qahm.apArray[gid] );
};

qahm.changeSelectorGoal= function(e) {
    let checkedId = e.target.id;

    let gsession_selector = document.getElementsByName(`js_gsession_selector`);

    if ( checkedId === 'js_gsession_selectuser' ) {
        for ( let gid = 0; gid < gsession_selector.length; gid++ ) {
            let divbox = gsession_selector[gid].closest('.bl_goalBox');
            divbox.classList.remove('bl_goalBoxChecked');
        }
        let divbox = e.target.closest('.bl_goalBox');
        divbox.classList.add('bl_goalBoxChecked');
        qahm.drawSessionsView([]);
    } else {
        let idsplit   = checkedId.split('_');
        let nowgid    = Number( idsplit[3] );
        for ( let sid = 0; sid < gsession_selector.length; sid++ ) {
            let divbox = gsession_selector[sid].closest('.bl_goalBox');
            if ( sid === nowgid ) {
                divbox.classList.add('bl_goalBoxChecked');
            } else {
                divbox.classList.remove('bl_goalBoxChecked');
            }
        }
        qahm.drawSessionsView(qahm.goalsSessionData[nowgid]);
    }
};



/** -------------------------------------
 * 日付決定＆取得するデータの期間を設定
 *  set period of data to get from QA_DB
 */
//moment.locale('ja');
moment.locale(qahm.wp_lang_set);
let gToday = new Date();
//cron前の深夜は2日前にしておく
let minusday = 1;
if (gToday.getHours()< 4) {minusday = 2;}

//タイムゾーンをWP設定に合わせる。（ウィンドウズとChromeの　time-zone「ずれ」向け処理。）
let hrgap = (gToday.getTimezoneOffset()) / 60;
gToday.setHours( gToday.getHours() + hrgap + qahm.wp_time_adj );
let gTodayAM0 = gToday.setHours( 0, 0, 0, 0 );

//どの期間のデータを取得するか
let gYesterday = new Date(gTodayAM0);
gYesterday.setTime( gYesterday.getTime() - minusday *1000*60*60*24 );
gYesterday.setHours( 23, 59, 59, 999);
let gYesterdayStr = moment(gYesterday).format('YYYY-MM-DD');

// let pastDatasetDays = 365*1;
//ダッシュボードは180日
let pastDatasetDaysDash = 180;
let pastDatasetStartDash = new Date(gTodayAM0);
pastDatasetStartDash.setTime( pastDatasetStartDash.getTime() - pastDatasetDaysDash *1000*60*60*24 );
let pastDatasetStartStrDash = moment(pastDatasetStartDash).format('YYYY-MM-DD');
let gDashTermForAjax = 'date = between ' + pastDatasetStartStrDash + ' and ' + gYesterdayStr;

//レンジピッカーの初期設定は一ヶ月
let gPastRangeDays;
let threeMonthAgo = new Date();
let oneMonthAgo   = new Date();
threeMonthAgo.setMonth(threeMonthAgo.getMonth() - 3 );
oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1 );
let tempToday = new Date();
let nissuu = ( tempToday - oneMonthAgo )/ (1000*60*60*24);
gPastRangeDays = Math.floor(nissuu);
let pastDatasetStart = new Date(gTodayAM0);
pastDatasetStart.setTime( pastDatasetStart.getTime() - gPastRangeDays *1000*60*60*24 );
let pastDatasetStartStr = moment(pastDatasetStart).format('YYYY-MM-DD');
let gStatsTermForAjax = 'date = between ' + pastDatasetStartStr + ' and ' + gYesterdayStr;


let daysince = new Date(pastDatasetStart);

/** ------------------------------
 * グラフの期間＆カレンダー設定
 *  set chart-period
 */
//DEFAULT
//let gPastRangeDays = 90;

let calcDate = new Date(gTodayAM0);
//let calcDate = new Date( moment('2021-05-01', 'YYYY-MM-DD') );
calcDate.setDate( calcDate.getDate() - gPastRangeDays - 1 );

//期間の開始日と終了日
gPastRangeStart = new Date(gTodayAM0);
gPastRangeStart.setDate( gPastRangeStart.getDate() - gPastRangeDays );
gPastRangeEnd = new Date(gYesterday);

let gPickedDatesAry = [];
let gLabelDatesAry = [];
for( let ttt = 0; ttt < gPastRangeDays; ttt++ ){
	calcDate.setDate( calcDate.getDate() + 1 );
	let strDate = moment(calcDate).format('YYYY-MM-DD');
	gPickedDatesAry.push(strDate);
	gLabelDatesAry.push( strDate.substr(5,10) );
}
if ( checkDefaultRange ) {
    console.log('デフォルトgPickedDatesAry', gPickedDatesAry);
    console.log('デフォルトgLabelDatesAry', gLabelDatesAry);
}

/** -------------------------------------
 * データの取得とチャート描画実行
 *  get past data and execute drawing chart
 */
jQuery(
	function() {

		qahm.heatmapChacheDefered.resolve();
        qahm.heatmapChacheDefered.promise().then( function() {
            qahm.nowAjaxStep = 0;
            qahm.drawAllTablesAjax(gStatsTermForAjax);


            }
        );

        //post count
		jQuery.ajax(
			{
				type: 'POST',
				url: qahm.ajax_url,
				dataType : 'json',
				data: {
					'action' : 'qahm_ajax_get_each_posts_count',
					'month' : 6,
					'nonce':qahm.nonce_api
				}
			}
		).done(
			function( data ){
				qahm.postParam = data;
				qahm.postParamDefered.resolve();
			}
		).fail(
			function( jqXHR, textStatus, errorThrown ){
				qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				qahm.postParamDefered.reject();
			}
		).always(
			function(){
			}
		);


		// post count graphを書く
		qahm.postParamDefered.promise().then( function() {
		    let labels   = new Array();
		    let today    = new Date();
            let nowmonth = Number( today.getMonth() ) + 1;
            let maxlen   = qahm.postParam.length;

            let plusmonth = 0;
            for ( let iii = 0; iii < maxlen; iii++ ) {
                let month = nowmonth - iii + plusmonth;
                if ( month <= 0 ) {
                    plusmonth = plusmonth + 12;
                }
                month = nowmonth - iii + plusmonth;
                //labels[iii] = month.toString();
                labels[iii] = moment( month, 'M').format("MMM");
            }
            //reverse
            let postcounts_ary = new Array();
            let labels_ary     = new Array();
            for ( let iii = 0; iii < maxlen; iii++ ) {
                postcounts_ary[iii] = qahm.postParam[maxlen - 1 - iii];
                labels_ary[iii] = labels[maxlen - 1 - iii];
            }

            let cvPostcountGraph = document.getElementById('post_count');
            let cvPostcountChart = new Chart(cvPostcountGraph, {
                type: 'line',
                data: {
                labels: labels_ary,
                datasets: [{
                    label: qahml10n['graph_posts'],
                    fill: false,
                    lineTension: 0,
                    data: postcounts_ary,
                    borderColor: '#69A4E2',
                    backgroundColor: '#69A4E2',
                    fill: true,
                    pointStyle: 'star',
                }],
                },
                options: {
                    legend: {
                        labels: {
                            fontSize: 9
                        },
                    },
                    y: {
                        min: 0,
                        max: 40,
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: false,
                                userCallback: function(label, index, labels) {
                                    if (Math.floor(label) === label) {
                                        return label;
                                    }
                                }
                            }
                        }]
                    }
                },
            });
        });


        //let start = moment(gTodayAM0).subtract(30, 'days');
        //let end = moment(gTodayAM0).subtract(1, 'days');

        //days access
        jQuery.ajax(
            {
                type: 'POST',
                url: qahm.ajax_url,
                dataType : 'json',
                data: {
                    'action' : 'qahm_ajax_select_data',
                    'table' : 'summary_days_access',
                    'select': '*',
                    'date_or_id': gDashTermForAjax,
                    'count' : false,
                    'nonce':qahm.nonce_api
                }
            }
        ).done(
            function( data ){
                qahm.statsParam = data;
                qahm.statsParamDefered.resolve();
            }
        ).fail(
            function( jqXHR, textStatus, errorThrown ){
                qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                qahm.statsParamDefered.reject();
            }
        ).always(
            function(){
            }
        );

		qahm.statsParamDefered.promise().then(function() {
			jQuery.when( qahm.postParamDefered, qahm.pvtermStartDefered).then ( function() {
				//dashboard用に180日が戻ってくる。30日分を抜き出して渡す。
				let dashary = qahm.statsParam;
	
				qahm.statsOrganizeChartData(qahm.statsParam);
				qahm.statsDrawChartOne(gLabelDatesAry, gChartDataArray);
				qahm.statsAggrPeriodTotal();
				qahm.statsFillTotal(gSum3CountsJson);
	
				// koji
				// write dashboard
				let statlen     = dashary.length;
				let ary_lastday = dashary[statlen -1]['date'];
	
				let matsubi = moment(ary_lastday, "YYYY-MM-DD");
				let tsuitachi = matsubi.date(1);
				let last_mn = moment(tsuitachi).subtract(1, 'months');
				let this_mn = moment(tsuitachi).format('YYYY-MM-DD');
				last_mn = moment(last_mn).format('YYYY-MM-DD');
	
				let dayslen  = 180;
				let startidx = statlen - dayslen;
				let offset   = 0;
				if ( startidx < 0 ) {
					startidx = 0;
				}
	
				let goalDaySession = 0;
				let goalday = '';
				if ( qahm.siteinfoJson ) {
					let siteinfoObj = JSON.parse( qahm.siteinfoJson );
					goalDaySession = siteinfoObj['goaldaysession'];
					goalday = siteinfoObj['goalday'];
				}
	
				let dashcharts_data  = new Array();
				let goalcharts_data  = new Array();
				let dashcharts_label = new Array();
				let this_mn_sessions = 0;
				let last_mn_sessions = 0;
				for ( let iii = statlen - 1 ; startidx <= iii ; --iii ) {
					if ( moment( dashary[iii]['date'] ).isSameOrAfter( this_mn ) ) {
						this_mn_sessions += dashary[iii]['session_count'];
					}else if ( moment( dashary[iii]['date'] ).isSameOrAfter( last_mn ) ) {
						last_mn_sessions += dashary[iii]['session_count'];
					}
					dashcharts_label[iii - startidx] = dashary[iii]['date'].slice(5);
					dashcharts_data[iii - startidx]  = dashary[iii]['session_count'];
					if ( 0 < goalDaySession ) {
						goalcharts_data[iii - startidx] = goalDaySession;
					}
				}
				let ary_lastd_o      = dateStringSlicer( ary_lastday );
				let ary_lastd_month  = ary_lastd_o['M'];
				let ary_lastd_year   = ary_lastd_o['Y'];
				let pv_start_month   = qahm.pvterm_start_date.month() + 1;
				let pv_start_year    = qahm.pvterm_start_date.year();
	
				let ary_lastday_len  = ary_lastd_o['D'];
				if ( ary_lastd_month === pv_start_month && ary_lastd_year === pv_start_year ) {
					let pv_start_day = qahm.pvterm_start_date.date();
					ary_lastday_len  = ary_lastday_len - pv_start_day + 1;
				}
	
				let thismn_lastday   = new Date( ary_lastd_o['Y'], ary_lastd_o['M'], 0 )
				let thismn_lastday_n = thismn_lastday.getDate();
	
				let this_mn_estimate = 0;
				if ( ary_lastday_len !== 0 &&  thismn_lastday_n !== 0 ) {
					this_mn_estimate = Math.round( this_mn_sessions / ary_lastday_len * thismn_lastday_n );
				}
				//write
				const formatter = new Intl.NumberFormat('ja-JP');
				document.getElementById('js_last_mn_sessions').innerText = formatter.format( last_mn_sessions );
				document.getElementById('js_this_mn_sessions').innerText = formatter.format( this_mn_sessions );
				document.getElementById('js_this_mn_estimate').innerText = formatter.format( this_mn_estimate );
	
				let cvAccessGraph = document.getElementById('access_graph');
				let datasets = {
						type: 'line',
						label: qahml10n['graph_sessions'],
						fill: false,
						lineTension: 0,
						data: dashcharts_data,
						borderColor: '#69A4E2',
						borderJoinStyle: 'bevel',
						pointStyle: 'rect',
						pointRadius: 1.5,
						borderWidth: 2.5,
						pointBackgroundColor: '#69A4E2',
					};
	
				if ( 0 < goalDaySession ) {
	
					datasets = [datasets, {
						type: 'line',
						label: ' Target Access ( by ' + goalday + ' )',
						fill: false,
						lineTension: 0,
						data: goalcharts_data,
						borderColor: qahm.colorAlfaChange(qahm.graphColorGoals[0], 0.8 ),
						borderWidth: 2,
						pointRadius: 0,
						showLine: true,
					}]
	
				} else {
					datasets = [datasets];
				}
	
				let cvAccessGraphChart = new Chart(cvAccessGraph, {
					type: 'line',
					data: {
					labels: dashcharts_label,
					datasets: datasets,
					},
					options: {
						legend: {
							labels: {
								fontSize: 9
							},
						},
						scales: {
							yAxes: [{
								ticks: {
									min: 0,
								},
								beforeBuildTicks: function(axis) {
									if( axis.max < 6 ) {
										axis.max = 6;
										axis.options.ticks.stepSize = 1;
									}
								},
							}],
						},
					},
				});
	
				statDateRangePicker();
			});
		});

        //picker start day
        jQuery.ajax(
            {
                type: 'POST',
                url: qahm.ajax_url,
                dataType : 'json',
                data: {
                    'action' : 'qahm_ajax_get_pvterm_start_date',
                    'nonce':qahm.nonce_api
                }
            }
        ).done(
            function( data ){
                if (data) {
                    qahm.pvterm_start_date = moment(data, "YYYY-MM-DD");
                    qahm.pvtermStartDefered.resolve();
                }
            }
        ).fail(
            function( jqXHR, textStatus, errorThrown ){
                qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                statDateRangePicker();
                qahm.pvtermStartDefered.reject();
            }
        ).always(
            function(){
            }
        );
    }
);

function statDateRangePicker() {
    let statsStart = moment(gPastRangeStart);
    let statsEnd	 = moment(gPastRangeEnd);

    function cb(statsStart, statsEnd) {
        jQuery('.chart-reportrange span').html(statsStart.format('ll') + ' ' + qahml10n['calender_kara'] + ' ' + statsEnd.format('ll'));
    }

    let datarange_opt = {
        startDate: statsStart,
        endDate: statsEnd,
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
    if (qahm.pvterm_start_date) {
        datarange_opt.minDate = qahm.pvterm_start_date;
    }
    if (qahm.statsParam) {
        datarange_opt.maxDate = moment( qahm.statsParam[qahm.statsParam.length-1].date, "YYYY-MM-DD" );
    }


    jQuery('.chart-reportrange').daterangepicker(datarange_opt, cb );

    //期間変更された時に変数に日時を格納
    jQuery('.chart-reportrange').on('apply.daterangepicker', function(ev, picker) {
        gPastRangeStart = new Date(picker.startDate);
        gPastRangeEnd = new Date(picker.endDate);

        //change "graph-label-dates"
        gPastRangeDays = Math.round( (gPastRangeEnd - gPastRangeStart) / (1000*60*60*24) ) + 1;

        let calcDate = new Date(gPastRangeStart);
        gPickedDatesAry.length = 0;
        gLabelDatesAry.length = 0;
        calcDate.setDate( calcDate.getDate() - 1 );
        for ( let ttt = 0; ttt < gPastRangeDays-1; ttt++ ) {
            calcDate.setDate( calcDate.getDate() + 1 );
            let strDate = moment(calcDate).format('YYYY-MM-DD');
            gPickedDatesAry.push(strDate);
            gLabelDatesAry.push( strDate.substr(5,10) );
        }


        // let pastDatasetStart = gPastRangeStart;
        // pastDatasetStart.setTime( pastDatasetStart.getTime() - gPastRangeDays *1000*60*60*24 );
        let pastDatasetStartStr = moment(gPastRangeStart).format('YYYY-MM-DD');
        let pastDatasetEndStr = moment(gPastRangeEnd).format('YYYY-MM-DD');
        gStatsTermForAjax = 'date = between ' + pastDatasetStartStr + ' and ' + pastDatasetEndStr;


        updatingChart = true;		//(already declared at above)
        jQuery.ajax(
            {
                type: 'POST',
                url: qahm.ajax_url,
                dataType : 'json',
                data: {
                    'action' : 'qahm_ajax_select_data',
                    'table' : 'summary_days_access',
                    'select': '*',
                    'date_or_id': gStatsTermForAjax,
                    'count' : false,
                    'nonce':qahm.nonce_api
                }
            }
        ).done(
            function( data ){
                qahm.statsParam = data;
                qahm.statsOrganizeChartData(qahm.statsParam);
                qahm.statsDrawChartOne(gLabelDatesAry, gChartDataArray);
                qahm.statsAggrPeriodTotal();
                qahm.statsFillTotal();
            }
        ).fail(
            function( jqXHR, textStatus, errorThrown ){
                qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
            }
        ).always(
            function(){
            }
        );

        //make all Tables
        qahm.nowAjaxStep = 0;
        qahm.drawAllTablesAjax(gStatsTermForAjax);

    });

    cb( statsStart, statsEnd );

}
//all table making
qahm.drawAllTablesAjax = function(datePickerTerm) {
    let pbarnrd = document.getElementById(audienceTable.progBarDiv);
    let pbarch = document.getElementById(channelsTable.progBarDiv);
    let pbarsm = document.getElementById(sourceMediumTable.progBarDiv);
    let pbarlp = document.getElementById(landingpageTable.progBarDiv);
    let pbargw = document.getElementById(growthpageTable.progBarDiv);
    let pbarap = document.getElementById(allpageTable.progBarDiv);

    switch (qahm.nowAjaxStep) {
        case 0:
            pbarnrd.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            pbarch.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            pbarsm.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            pbarlp.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            pbargw.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            pbarap.innerHTML = '<span class="el_loading">Loading<span></span></span>';
            qahm.nowAjaxStep = 'getGoals';
            qahm.drawAllTablesAjax(datePickerTerm);
            break;

        case 'getGoals':
            if ( qahm.goalsJson ) {
                qahm.goalsArray = JSON.parse( qahm.goalsJson );
            }
		    //new /repeat device table
            qahm.goalsSessionData = new Array();
            jQuery.ajax(
                {
                    type: 'POST',
                    url: qahm.ajax_url,
                    dataType : 'json',
                    data: {
                        'action' : 'qahm_ajax_get_goals_sessions',
                        'date' : datePickerTerm,
                        'nonce':qahm.nonce_api
                    }
                }
            ).done(
                function( data ){
					if (data !== null ) {
						// 重複するセッションを除外する関数定義
						function removeDuplicates(arr) {
							const seen = new Map();

							return arr.filter(subArray => {
							  const key = subArray.map(obj => JSON.stringify(obj)).join('|');
							  if (seen.has(key)) {
								return false;
							  }
							  seen.set(key, true);
							  return true;
							});
						}

						let allSessionAry = new Array();

						// 全てのゴール配列を作る。各ゴール配列をマージするが、その際に同一セッションは除外
						for (let gid = 1; gid <= Object.keys(data).length; gid++) {
							if ( data[gid].length === 0 ) {
								continue;
							}
							if ( allSessionAry.length === 0 ) {
								allSessionAry = data[gid];
								continue;
							}
							allSessionAry = allSessionAry.concat(data[gid]);
						}

						// 重複するセッションを除外
						allSessionAry = removeDuplicates(allSessionAry);

						for (let gid = 0; gid <= Object.keys(data).length; gid++) {
							if (gid === 0) {
								qahm.goalsSessionData[0] = allSessionAry;
							} else {
								qahm.goalsSessionData[gid] = data[gid];
							}
						}
					} else {
						if (qahm.goalsJson) {
							for (let gid = 0; gid <= Object.keys(qahm.goalsArray).length; gid++) {
								qahm.goalsSessionData[gid] = new Array();
							}
						}
					}
                }
            ).fail(
                function( jqXHR, textStatus, errorThrown ){
                    qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                    if ( qahm.goalsJson ) {
                        for ( let gid = 0; gid <= Object.keys(qahm.goalsArray).length; gid++ ) {
                            qahm.goalsSessionData[gid] = new Array();
                        }
                    }
                }
            ).always(
                function(){
                    qahm.nowAjaxStep = 'makeDashGoalsGraph';
                    qahm.drawAllTablesAjax(datePickerTerm);
                }
            );
            break;

        case 'makeDashGoalsGraph':
            if ( qahm.goalsJson ) {
                let lastdayobj   = new Date();
                lastdayobj.setDate( lastdayobj.getDate() -1 );

                let lmon01obj   = new Date();
                lmon01obj.setDate(1);
                lmon01obj.setMonth( lmon01obj.getMonth() -1 );
                lmon01obj.setHours(0,0,0);

                let sttdayobj  = new Date( lmon01obj );
                let nextdayobj = new Date( sttdayobj );
                nextdayobj.setDate( nextdayobj.getDate() + 1 );

                let termdate  = (lastdayobj - lmon01obj) / 86400000;
                let datelabel = [];
                let datedata  = []
                if ( qahm.g2monSessionsJson !== undefined ) {
                    let sessionary = new Array();
                    sessionary = JSON.parse(JSON.stringify(qahm.g2monSessionsJson));
                    for (let dno = 0; dno < termdate; dno++) {
                        let sno = 0;
                        let dateformat = ('00' + (sttdayobj.getMonth()+1)).slice(-2) + '-' + ('00' + sttdayobj.getDate()).slice(-2);
                        datelabel[dno] = dateformat;
                        datedata[dno] = 0;
                        while ( sno < sessionary[0].length ) {
                            let accessdobj = new Date(sessionary[0][sno][0]['access_time']);
                            if (sttdayobj <= accessdobj && accessdobj < nextdayobj) {
                                datedata[dno]++;
                            }
                            sno++;
                        }
                        sttdayobj = new Date(nextdayobj);
                        nextdayobj.setDate( nextdayobj.getDate() + 1 );
                    }

                    //make goals
                    let cvConversionGraph = document.getElementById('conversion_graph');
                    qahml10n['graph_goals'] = 'Goals';
                    let conv_charts_data = datedata;
                    let cvConversionGraphChart = new Chart(cvConversionGraph, {
                        type: 'bar',
                        data: {
                            labels: datelabel,
                            datasets: [{
                                label: qahml10n['graph_goals'],
                                fill: false,
                                lineTension: 0,
                                data: conv_charts_data,
                                borderColor: qahm.graphColorGoals[0],
                                borderJoinStyle: 'bevel',
                                pointStyle: 'rect',
                                pointRadius: 1.5,
                                borderWidth: 2.5,
                                pointBackgroundColor: qahm.graphColorGoals[0],
                            }],
                        },
                        options: {
                            legend: {
                                labels: {
                                    fontSize: 9
                                },
                            },
                            y: {
                                min: 0,
                                max: 40,
                            },
                        },
                    });
                }
            }
            qahm.nowAjaxStep = 'makeGoalsArray';
            qahm.drawAllTablesAjax(datePickerTerm);
            break;

        case 'makeGoalsArray':
            if ( qahm.goalsJson ) {
                let pidary   = new Array();
                let allgoals = 0;
                for ( let gid = 1; gid <= Object.keys(qahm.goalsArray).length; gid++ ) {
                    pidary = pidary.concat(qahm.goalsArray[gid]['pageid_ary']);
                    allgoals += Number(qahm.goalsArray[gid]['gnum_scale']);
                }
                qahm.goalsArray[0] = {'pageid_ary': pidary, 'gtitle':qahml10n['cnv_all_goals'], 'gnum_scale':allgoals };
                qahm.goalsNrdArray = new Array();
                qahm.goalsChArray  = new Array();
                qahm.goalsSmArray  = new Array();
                qahm.goalsLpArray  = new Array();
                qahm.goalsApArray  = new Array();

                //default channel group
                let paidsearch = new RegExp('^(cpc|ppc|paidsearch)$');
                let display    = new RegExp('(display|cpm|banner)$');
                let otheradv   = new RegExp('^(cpv|cpa|cpp|content-text)$');
                let social     = new RegExp('^(social|social-network|social-media|sm|social network|social media)$');

                let uri        = new URL(window.location.href);
                let domain     = uri.host;

                for ( let gid = 0; gid < Object.keys(qahm.goalsArray).length; gid++ ) {

                    //make nrd array
                    let nrdary = [ 0,0,0,0,0,0 ];
                    let ch_ary = {'Direct':0,'Organic Search':0,'Referral':0,'Social':0,'Display':0,'Email':0,'Affiliates':0,'Paid Search':0,'Other Advertising':0,'Other':0 };
                    let sm_ary = new Array();
                    let lp_ary = new Array();
                    let ap_ary = new Array();
                    for ( let sno = 0; sno < qahm.goalsSessionData[gid].length; sno++ ) {
                        let lp = qahm.goalsSessionData[gid][sno][0];
                        //nrd
                        if ( Number( lp['is_newuser'] ) ) {
                            switch ( Number( lp['device_id'] ) )  {
                                case 1:
                                    nrdary[0]++;
                                    break;

                                case 2:
                                    nrdary[1]++;
                                    break;

                                case 3:
                                    nrdary[2]++;
                                    break;
                            }
                        } else {
                            switch ( Number( lp['device_id'] ) )  {
                                case 1:
                                    nrdary[3]++;
                                    break;

                                case 2:
                                    nrdary[4]++;
                                    break;

                                case 3:
                                    nrdary[5]++;
                                    break;
                            }
                        }
                        //channel
                        if ( lp['utm_medium'] !== undefined ) {
                            switch (lp['utm_medium']) {
                                case '':
                                case null:
                                    if ( lp['source_domain'] !== undefined ) {
                                        if ( lp[ 'source_domain' ] === 'direct' || lp[ 'source_domain' ] === domain ) {
                                            ch_ary['Direct']++;
                                        } else {
                                            ch_ary["Referral"]++;
                                        }
                                    } else {
                                        ch_ary['Direct']++;
                                    }
                                break;

                                case 'direct':
                                    ch_ary['Direct']++;
                                    break;

                                case 'organic':
                                    ch_ary["Organic Search"]++;
                                    break;

                                case 'referral':
                                    ch_ary["Referral"]++;
                                    break;

                                case 'social':
                                    ch_ary["Social"]++;
                                    break;

                                case 'display':
                                    ch_ary["Display"]++;
                                    break;

                                case 'email':
                                    ch_ary["Email"]++;
                                    break;

                                case 'affiliate':
                                    ch_ary["Affiliates"]++;
                                    break;

                                case 'cpc':
                                    ch_ary["Paid Search"]++;
                                    break;

                                case 'cpv':
                                    ch_ary["Other Advertising"]++;
                                    break;

                                default:
                                    if ( lp['utm_medium'].match( paidsearch ) ){
                                        ch_ary['Paid Search']++;
                                    } else if ( lp['utm_medium'].match( display ) ){
                                        ch_ary['Display']++;
                                    } else if ( lp['utm_medium'].match( social ) ){
                                        ch_ary['Social']++;
                                    } else if ( lp['utm_medium'].match( otheradv ) ){
                                        ch_ary["Other Advertising"]++;
                                    } else {
                                        ch_ary['Other']++;
                                    }
                                    break;
                            }
                            //sm
                            if ( sm_ary[lp['source_domain']] !== undefined ) {
                                if (sm_ary[lp['source_domain']][lp['utm_medium']]!== undefined) {
                                    sm_ary[lp['source_domain']][lp['utm_medium']] ++;
                                } else {
                                    sm_ary[lp['source_domain']][lp['utm_medium']] = 1;
                                }
                            } else {
                                sm_ary[lp['source_domain']] = new Array();
                                sm_ary[lp['source_domain']][lp['utm_medium']] = 1;
                            }

                        } else {
                            ch_ary['Direct']++;
                            if ( sm_ary[lp['source_domain']] !== undefined  ) {
                                if ( sm_ary[lp['source_domain']][''] !== undefined ) {
                                    sm_ary[lp['source_domain']]['']++;
                                } else {
                                    sm_ary[lp['source_domain']][''] = 1;
                                }
                            } else {
                                sm_ary[lp['source_domain']] = new Array();
                                sm_ary[lp['source_domain']][''] = 1;
                            }
                        }

                        //lp
                        if ( lp_ary[lp['page_id']] !== undefined  ) {
                            lp_ary[lp['page_id']] ++;
                        } else {
                            lp_ary[lp['page_id']] = 1;
                        }
                        //ap
                        for ( let pno = 0; pno < qahm.goalsSessionData[gid][sno].length; pno++ ) {
                            //pageid=null or pno=null の場合はスキップ
                            if (qahm.goalsSessionData[gid][sno][pno] == null || qahm.goalsSessionData[gid][sno][pno]['page_id'] == null) {
                                continue;
                            }
                            let pageid = Number(qahm.goalsSessionData[gid][sno][pno]['page_id']);
                            //mkdummy
                                if (Number(pageid)===4726) {
                                    console.log(pageid+'-'+gid.toString()+'-'+sno.toString()+'-'+pno.toString());
                                }
                                //mkdummy end
                            let is_conversion = false;
                            for ( let lll = 0; lll < qahm.goalsArray[gid]['pageid_ary'].length; lll++ ) {
                                if ( pageid === Number( qahm.goalsArray[gid]['pageid_ary'][lll]) ) {
                                    is_conversion = true;
                                    break;
                                }
                            }
                            if ( ap_ary[pageid]  !== undefined ) {
                                ap_ary[pageid] ++;
                            } else {
                                ap_ary[pageid] = 1;
                            }
                            if ( is_conversion ) {
                                break;
                            }
                        }
                    }
                    qahm.goalsNrdArray[gid] = nrdary;
                    qahm.goalsChArray[gid]  = ch_ary;
                    qahm.goalsSmArray[gid]  = sm_ary;
                    qahm.goalsLpArray[gid]  = lp_ary;
                    qahm.goalsApArray[gid]  = ap_ary;
                }
            }
            qahm.nowAjaxStep = 'getNrd';
            qahm.drawAllTablesAjax(datePickerTerm);
            break;




        case 'getNrd':
		    //new /repeat device table
            jQuery.ajax(
                {
                    type: 'POST',
                    url: qahm.ajax_url,
                    dataType : 'json',
                    data: {
                        'action' : 'qahm_ajax_get_nrd_data',
                        'date' : datePickerTerm,
                        'nonce':qahm.nonce_api
                    }
                }
            ).done(
                function( data ){
                    let ary = data;
                    if (ary) {
                        qahm.nrdArray = new Array;

                        if ( qahm.goalsJson ) {

                            //0:all のcv率と目標達成数はgid0に入っている全目標達成数を使える。但し目標値の合計は個別に計算が必要
                            for (let iii = 0; iii < ary.length; iii++) {
                                let valueall = 0;
                                let sessions = Number(ary[iii][4]);
                                for ( let gid = 1; gid < qahm.goalsNrdArray.length; gid++ ) {
                                    let gcount = Number(qahm.goalsNrdArray[gid][iii]);
                                    let cvrate = 0;
                                    if ( 0 < sessions ) {
                                        cvrate = qahm.roundToX( gcount / sessions  * 100, 2);
                                    }
                                    let valuex   = Number(qahm.goalsArray[gid]['gnum_value']) * gcount;
                                    valueall += valuex;
                                    if ( qahm.nrdArray[gid] === undefined ) {
                                        qahm.nrdArray[gid] = new Array();
                                    }
                                    qahm.nrdArray[gid][iii] = ary[iii].concat([ cvrate, gcount, valuex ]);
                                }
                                let gcountall = Number(qahm.goalsNrdArray[0][iii]);
                                let cvrateall = 0;
                                if ( 0 < sessions ) {
                                    cvrateall = qahm.roundToX( gcountall / sessions * 100, 2);
                                }
                                if ( qahm.nrdArray[0] === undefined ) {
                                    qahm.nrdArray[0] = new Array();
                                }
                                qahm.nrdArray[0][iii] = ary[iii].concat([ cvrateall, gcountall, valueall ]);
                            }

                        } else {
                            //全部0
                            qahm.nrdArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.nrdArray[0][iii] = ary[iii].concat([ 0, 0, 0 ]);
                            }
                        }
                        qahm.makeTable( audienceTable, qahm.nrdArray[0] );
                    }
                }
            ).fail(
                function( jqXHR, textStatus, errorThrown ){
                    qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                    const nrdcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_nrd_data', audienceTable, datePickerTerm);
                    audienceTable.errorReload(nrdcb);
                }
            ).always(
                function(){
                    qahm.nowAjaxStep = 'getCh';
                    qahm.drawAllTablesAjax(datePickerTerm);
                    pbarnrd.innerHTML = '';
                }
            );
            break;

        case 'getCh':
			//channel table
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action' : 'qahm_ajax_get_ch_data',
						'date' : datePickerTerm,
						'nonce':qahm.nonce_api
					}
				}
			).done(
				function( data ){
					let ary = data;
					if (ary) {
                        qahm.chArray = new Array;
                        if ( qahm.goalsJson ) {

                            //0:all のcv率と目標達成数はgid0に入っている全目標達成数を使える。但し目標値の合計は個別に計算が必要
                            for (let iii = 0; iii < ary.length; iii++) {
                                let valueall = 0;
                                let chkey    = ary[iii][0];
                                let sessions = Number(ary[iii][3]);
                                for ( let gid = 1; gid < qahm.goalsChArray.length; gid++ ) {
                                    let gcount = qahm.goalsChArray[gid][chkey];
                                    let cvrate = 0;
                                    if ( 0 < sessions ) {
                                        cvrate = qahm.roundToX( gcount / sessions  * 100, 2);
                                    }
                                    let valuex   = qahm.goalsArray[gid]['gnum_value'] * gcount;
                                    valueall += valuex;
                                    if ( qahm.chArray[gid] === undefined ) {
                                        qahm.chArray[gid] = new Array();
                                    }
                                    qahm.chArray[gid][iii] = ary[iii].concat([ cvrate, gcount, valuex ]);
                                }
                                let gcountall = qahm.goalsChArray[0][chkey];
                                let cvrateall = 0;
                                if ( 0 < sessions ) {
                                    cvrateall = qahm.roundToX( gcountall / sessions * 100, 2);
                                }
                                if ( qahm.chArray[0] === undefined ) {
                                    qahm.chArray[0] = new Array();
                                }
                                qahm.chArray[0][iii] = ary[iii].concat([ cvrateall, gcountall, valueall ]);
                            }

                        } else {
                            //全部0
                            qahm.chArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.chArray[0][iii] = ary[iii].concat([ 0, 0, 0 ]);
                            }
                        }
                        qahm.makeTable( channelsTable, qahm.chArray[0] );
						qahm.makeChannelGraph(ary);
					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					const chcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_ch_data', channelsTable, datePickerTerm);
                    channelsTable.errorReload(chcb);
				}
			).always(
				function(){
                    qahm.nowAjaxStep = 'getSm';
                    qahm.drawAllTablesAjax(datePickerTerm);
                    pbarch.innerHTML = '';
				}
			);
			break;

        case 'getSm':
			//source media table
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action' : 'qahm_ajax_get_sm_data',
						'date' : datePickerTerm,
						'nonce':qahm.nonce_api
					}
				}
			).done(
				function( data ){
					let ary = data;
					if (ary) {
                        qahm.smArray = new Array;
                        if ( qahm.goalsJson ) {

                            //0:all のcv率と目標達成数はgid0に入っている全目標達成数を使える。但し目標値の合計は個別に計算が必要
                            for (let iii = 0; iii < ary.length; iii++) {
                                let source = ary[iii][0];
                                let media  = ary[iii][1];
                                let valueall = 0;
                                let sessions = Number(ary[iii][4]);
                                for ( let gid = 1; gid < qahm.goalsSmArray.length; gid++ ) {
                                    if (qahm.smArray[gid] === undefined) {
                                        qahm.smArray[gid] = new Array();
                                    }
                                    if ( qahm.goalsSmArray[gid][source] !== undefined) {
                                        if (qahm.goalsSmArray[gid][source][media] !== undefined) {
                                            let gcount = Number(qahm.goalsSmArray[gid][source][media]);
                                            let cvrate = 0;
                                            if (0 < sessions) {
                                                cvrate = qahm.roundToX(gcount / sessions * 100, 2);
                                            }
                                            let valuex = Number(qahm.goalsArray[gid]['gnum_value']) * gcount;
                                            valueall += valuex;
                                            qahm.smArray[gid][iii] = ary[iii].concat([cvrate, gcount, valuex]);
                                        } else {
                                            qahm.smArray[gid][iii] = ary[iii].concat([0, 0, 0]);
                                        }
                                    } else {
                                        qahm.smArray[gid][iii] = ary[iii].concat([0, 0, 0]);
                                    }
                                }
                                let gcountall = 0;
                                if ( qahm.goalsSmArray[0][source] !== undefined ) {
                                    if ( qahm.goalsSmArray[0][source][media] !== undefined ) {
                                        gcountall = qahm.goalsSmArray[0][source][media] ;
                                    }
                                }
                                let cvrateall = 0;
                                if ( 0 < sessions ) {
                                    cvrateall = qahm.roundToX( gcountall / sessions * 100, 2);
                                }
                                if ( qahm.smArray[0] === undefined ) {
                                    qahm.smArray[0] = new Array();
                                }
                                qahm.smArray[0][iii] = ary[iii].concat([ cvrateall, gcountall, valueall ]);
                            }
                        }
                        else {
                            //全部0
                            qahm.smArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.smArray[0][iii] = ary[iii].concat([ 0, 0, 0 ]);
                            }
                        }
                        qahm.makeTable( sourceMediumTable, qahm.smArray[0] );


					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					const smcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_sm_data', sourceMediumTable, datePickerTerm);
                    sourceMediumTable.errorReload(smcb);
				}
			).always(
				function(){
                    qahm.nowAjaxStep = 'getLp';
                    qahm.drawAllTablesAjax(datePickerTerm);
                    pbarsm.innerHTML = '';
				}
			);
            break;

        case 'getLp':
            //landingpage table
            jQuery.ajax(
                {
                    type: 'POST',
                    url: qahm.ajax_url,
                    dataType : 'json',
                    data: {
                        'action' : 'qahm_ajax_get_lp_data',
                        'date' : datePickerTerm,
                        'nonce':qahm.nonce_api
                    }
                }
            ).done(
                function( data ){
                    let ary = data;
                    if (ary) {
                        qahm.lpArray = new Array;
                        if ( qahm.goalsJson ) {

                            //0:all のcv率と目標達成数はgid0に入っている全目標達成数を使える。但し目標値の合計は個別に計算が必要
                            for (let iii = 0; iii < ary.length; iii++) {
                                let pageid = ary[iii][0];
                                let valueall = 0;
                                let sessions = Number(ary[iii][3]);
                                for ( let gid = 1; gid < qahm.goalsLpArray.length; gid++ ) {
                                    if (qahm.lpArray[gid] === undefined) {
                                        qahm.lpArray[gid] = new Array();
                                    }
                                    if ( qahm.goalsLpArray[gid][pageid] !== undefined) {
                                        let gcount = Number(qahm.goalsLpArray[gid][pageid]);
                                        let cvrate = 0;
                                        if (0 < sessions) {
                                            cvrate = qahm.roundToX(gcount / sessions * 100, 2);
                                        }
                                        let valuex = Number(qahm.goalsArray[gid]['gnum_value']) * gcount;
                                        valueall += valuex;
                                        qahm.lpArray[gid][iii] = ary[iii].concat([cvrate, gcount, valuex]);
                                    } else {
                                        qahm.lpArray[gid][iii] = ary[iii].concat([0, 0, 0]);
                                    }
                                }
                                let gcountall = 0;
                                if ( qahm.goalsLpArray[0][pageid] !== undefined ) {
                                    if ( qahm.goalsLpArray[0][pageid] !== undefined ) {
                                        gcountall = qahm.goalsLpArray[0][pageid] ;
                                    }
                                }
                                let cvrateall = 0;
                                if ( 0 < sessions ) {
                                    cvrateall = qahm.roundToX( gcountall / sessions * 100, 2);
                                }
                                if ( qahm.lpArray[0] === undefined ) {
                                    qahm.lpArray[0] = new Array();
                                }
                                qahm.lpArray[0][iii] = ary[iii].concat([ cvrateall, gcountall, valueall ]);
                            }
                        }
                        else {
                            //全部0
                            qahm.lpArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.lpArray[0][iii] = ary[iii].concat([ 0, 0, 0 ]);
                            }
                        }
                        qahm.makeLandingPageTable( landingpageTable, qahm.lpArray[0] );
					}
                }
            ).fail(
                function( jqXHR, textStatus, errorThrown ){
                    qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                    const lpcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_lp_data', landingpageTable, datePickerTerm);
                    landingpageTable.errorReload(lpcb);
                }
            ).always(
                function(){
                    qahm.nowAjaxStep = 'getGw';
                    qahm.drawAllTablesAjax(datePickerTerm);
                    pbarlp.innerHTML = '';
                }
            );
            break;

        case 'getGw':
			//growth landingpage table
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action' : 'qahm_ajax_get_gw_data',
						'date' : datePickerTerm,
						'nonce':qahm.nonce_api
					}
				}
			).done(
				function( data ){
					let ary = data;
					if (ary) {
						let pelm = 0;
						let relm = 11;
						let celm = 12;
						let velm = 13;
                        qahm.gwArray = new Array;
                        if ( qahm.goalsJson ) {
                            //LPから値をもってくる
                            for (let iii = 0; iii < ary.length; iii++) {
                                let pageid = ary[iii][0];
                                for ( let gid = 0; gid < qahm.lpArray.length; gid++ ) {
                                    if (qahm.gwArray[gid] === undefined) {
                                        qahm.gwArray[gid] = new Array();
                                    }
                                	let is_find = false;
									for ( let pid = 0; pid < qahm.lpArray[gid].length; pid++ ) {
										if ( qahm.lpArray[gid][pid][pelm] === pageid ) {
											is_find = true;
											qahm.gwArray[gid][iii] = ary[iii].concat([qahm.lpArray[gid][pid][relm], qahm.lpArray[gid][pid][celm], qahm.lpArray[gid][pid][velm]]);
											break;
										}
									}
									if (! is_find ) {
                                        qahm.gwArray[gid][iii] = ary[iii].concat([0, 0, 0]);
									}
                                }
                            }
                        }
                        else {
                            //全部0
                            qahm.gwArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.gwArray[0][iii] = ary[iii].concat([ 0, 0, 0 ]);
                            }
                        }
                        qahm.makeGrowthPageTable( growthpageTable, qahm.gwArray[0] );
					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					const gwcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_gw_data', growthpageTable, datePickerTerm);
                    growthpageTable.errorReload(gwcb);
                    pbargw.innerHTML = '';
				}
			).always(
				function(){
                    qahm.nowAjaxStep = 'getAp';
                    qahm.drawAllTablesAjax(datePickerTerm);
				}
			);
            break;

        case 'getAp':
            //allpage table
            jQuery.ajax(
                {
                    type: 'POST',
                    url: qahm.ajax_url,
                    dataType : 'json',
                    data: {
                        'action' : 'qahm_ajax_get_ap_data',
                        'date' : datePickerTerm,
                        'nonce':qahm.nonce_api
                    }
                }
            ).done(
                function( data ){
                    let ary = data;
                    if (ary) {
                        qahm.apArray = new Array;
                        if ( qahm.goalsJson ) {

                            //0:all のcv率と目標達成数はgid0に入っている全目標達成数を使える。但し目標値の合計は個別に計算が必要
                            for (let iii = 0; iii < ary.length; iii++) {
                                let pageid = ary[iii][0];

                                //mkdummy
                                let url = ary[iii][2];
                                let serach = '/lp-qa-analytics/?maf=3115_2623747.40932.0..1768949474.1653962871';
                                let islog = false;
                                if (url.indexOf(serach) > 0 ) {
                                    islog = true;
                                }
                                //mkdumy end
                                let pagevalueall = 0;
                                let sessions = Number(ary[iii][4]);
                                for ( let gid = 1; gid < qahm.goalsApArray.length; gid++ ) {
                                    if (qahm.apArray[gid] === undefined) {
                                        qahm.apArray[gid] = new Array();
                                    }
                                    if ( qahm.goalsApArray[gid][pageid] !== undefined) {
                                        let gcount = qahm.goalsApArray[gid][pageid];
                                        let valuex = qahm.goalsArray[gid]['gnum_value'] * gcount;
                                        //mkdummy

                                        if (islog) {
                                            console.log(pageid+'-'+gid.toString()+'-'+gcount.toString()+'-'+valuex.toString());
                                        }
                                        //mkdummy end

                                        let pagevalue = 0;
                                        if (0 < sessions) {
                                            pagevalue = Math.round( valuex / sessions );
                                        }
                                        pagevalueall += pagevalue;
                                        qahm.apArray[gid][iii] = ary[iii].concat([ pagevalue ]);
                                    } else {
                                        qahm.apArray[gid][iii] = ary[iii].concat([0]);
                                    }
                                }
                                if ( qahm.apArray[0] === undefined ) {
                                    qahm.apArray[0] = new Array();
                                }
                                qahm.apArray[0][iii] = ary[iii].concat([ pagevalueall ]);
                            }
                        }
                        else {
                            //全部0
                            qahm.apArray[0] = new Array();
                            for (let iii = 0; iii < ary.length; iii++) {
                                qahm.apArray[0][iii] = ary[iii].concat([ 0 ]);
                            }
                        }
                        qahm.makeAllPageTable(allpageTable, qahm.apArray[0]);
                    }
                }
            ).fail(
                function( jqXHR, textStatus, errorThrown ){
                    qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
                    const apcb = qahm.drawTableAjax.bind(null, 'qahm_ajax_get_ap_data', allpageTable, datePickerTerm);
                    allpageTable.errorReload(apcb);
                }
            ).always(
                function(){
                    pbarap.innerHTML = '';
                    qahm.nowAjaxStep = 'makeCVGraph';
                    qahm.drawAllTablesAjax(datePickerTerm);
                }
            );
            break;

        case 'makeCVGraph':

            if ( qahm.goalsJson ) {
                //make conversion array
                qahm.goalsSummary = new Array();
                let sttdayobj  = new Date( gPastRangeStart );
                let nextdayobj = new Date( sttdayobj );
                nextdayobj.setDate( nextdayobj.getDate() + 1 );

                let termdate  = (gPastRangeEnd - gPastRangeStart) / 86400000;
                if ( qahm.goalsSessionData !== undefined ) {
                    for (let dno = 0; dno < termdate; dno++) {
                        for (let gid = 0; gid < qahm.goalsSessionData.length; gid++) {
                            let sno = 0;
                            if (qahm.goalsSummary[gid] === undefined) {
                                qahm.goalsSummary[gid] = {'cvPerDay': [], 'nCount': 0, 'nValue': 0, 'nCvrate': 0.0};
                            }
                            qahm.goalsSummary[gid].cvPerDay[dno] = 0;

                            while (sno < qahm.goalsSessionData[gid].length) {
                                let accessdobj = new Date(qahm.goalsSessionData[gid][sno][0]['access_time']);
                                if (sttdayobj <= accessdobj && accessdobj < nextdayobj) {
                                    qahm.goalsSummary[gid].cvPerDay[dno]++;
                                    qahm.goalsSummary[gid].nCount++;
                                }
                                sno++;
                            }
                        }
                        sttdayobj = new Date(nextdayobj);
                        nextdayobj.setDate(nextdayobj.getDate() + 1);
                    }
                }
                let allvalue   = 0;
                for (let gid = 0; gid < qahm.goalsSessionData.length; gid++) {
                    if ( 1 <= gid ) {
                        qahm.goalsSummary[gid].nValue = Number(qahm.goalsSummary[gid].nCount * qahm.goalsArray[gid]['gnum_value']);
                        allvalue += Number(qahm.goalsSummary[gid].nValue);
                    }
                    qahm.goalsSummary[gid].nCvrate = Number(qahm.goalsSummary[gid].nCount) / Number(gSum3CountsJson.numSessions)
                    qahm.goalsSummary[gid].nCvrate = qahm.roundToX(qahm.goalsSummary[gid].nCvrate * 100, 2);
                }
                qahm.goalsSummary[0].nValue = allvalue;

                //make datasets
                let cvDatesets = new Array();
                for (let gid = 0; gid < qahm.goalsSessionData.length; gid++) {
                    let rgba = qahm.graphColorGoals[gid];
                    let hide = false;
                    if (gid === 0 ) { hide = true;}
                    cvDatesets[gid] = {
                                type: 'bar',
                                hidden: hide,
                                label: decodeURI(qahm.goalsArray[gid].gtitle),
                                data: qahm.goalsSummary[gid].cvPerDay,
                                backgroundColor: rgba,
                                borderColor: rgba,
                                borderJoinStyle: 'bevel',
                                borderWidth: 2,
                                borderDash: [10, 1, 2, 1],
                                pointStyle: 'rectRot',
                                lineTension: 0,
                                fill: false,
                                yAxisID: 'goals'
                    };
                }
                cvDatesets[qahm.goalsSessionData.length] = {
                    label: qahml10n['graph_sessions'],
                    data: gChartDataArray.numSessions,
                    backgroundColor: 'rgba(105,164,226, 0.1)',
                    borderWidth: 0,
                    yAxisID: 'session'

                };
                qahm.resetCanvas("cvConversionGraph", 'height=300');
                let cvConversionGraph = document.getElementById("cvConversionGraph").getContext('2d');
                if (cvConversionGraph) {
                    let conversionGraphChart = new Chart(cvConversionGraph, {
                        type: 'line',
                        data: {
                            labels: gLabelDatesAry,
                            datasets:cvDatesets,
                        },
                        options: {
                            responsive: true,
                            // maintainAspectRatio: false,
                            scales: {
                                yAxes: [{
                                    id: 'goals',
                                    position: 'left',
                                },{
                                    id: 'session',
                                    position: 'right',
                                }]
                            }
                        }
                    });
                }
                //draw gsession_selector and graph
                for (let gid = 0; gid < qahm.goalsSessionData.length; gid++) {
                    let gcomplete = 'js_gcomplete_' + gid.toString();
                    let gvalue    = 'js_gvalue_' + gid.toString();
                    let gcvrate   = 'js_gcvrate_' + gid.toString();

                    let cmp = document.getElementById(gcomplete);
                    if (cmp) { cmp.innerText = qahm.goalsSummary[gid].nCount.toString(); }
                    let val = document.getElementById(gvalue);
                    if (val) { val.innerText = qahm.goalsSummary[gid].nValue.toString(); }
                    let cvr = document.getElementById(gcvrate);
                    if (cvr) { cvr.innerText = qahm.goalsSummary[gid].nCvrate.toString() + '%'; }


                    let canvasid = 'js_gssCanvas_' + gid.toString();
                    qahm.resetCanvas(canvasid, 'height=200');
                    let canvas   = document.getElementById(canvasid);
                    if ( canvas !== null ) {
                        let goalhope     = qahm.goalsArray[gid].gnum_scale / 30 * termdate;
                        let cvGoalData   = [ qahm.goalsSummary[gid].nCount, Math.floor( goalhope ) ];
                        let cvGoalGraphChart = new Chart(canvas, {
                            type: 'bar',
                            data: {
                            labels: [ qahml10n['cnv_graph_present'], qahml10n['cnv_graph_goal'] ],
                            datasets: [{
                                label:qahml10n['cnv_graph_completions'],
                                fill: false,
                                lineTension: 0,
                                data: cvGoalData,
                                backgroundColor: [qahm.graphColorGoals[gid], qahm.colorAlfaChange(qahm.graphColorGoals[gid], 0.3) ],
                                }],
                            },
                            options: {
                                legend: {
                                    labels: {
                                        fontSize: 9
                                    },
                                },
                                barPercentage : 1,
                                scales: {
                                    xAxes: [{
                                        stacked: true, //積み上げ棒グラフにする設定
                                    }],
                                    yAxes: [{
                                        stacked: true, //積み上げ棒グラフにする設定
                                        ticks: {     // 目盛り
                                            min: 0,      // 最小値
                                            // beginAtZero: true でも同じ
                                            stepSize: 1  // 間隔
                                        }
                                    }]
                                }
                            },
                        });
                    }
                }
                qahm.drawSessionsView(qahm.goalsSessionData[0]);

            } else {
                qahm.resetCanvas("cvConversionGraph", 'style="height: 0"');
            }

            qahm.nowAjaxStep = 0;
            break;


        default:
            break;

    }
};
qahm.drawTableAjax = function(action, table, datePickerTerm) {
    //new /repeat device table
    jQuery.ajax(
        {
            type: 'POST',
            url: qahm.ajax_url,
            dataType: 'json',
            data: {
                'action': action,
                'date': datePickerTerm,
                'nonce': qahm.nonce_api
            }
        }
    ).done(
        function (data) {
            let ary = data;
            if (ary) {
                qahm.makeTable(table, ary);
                table.clearReload();
            }
        }
    ).fail(
        function (jqXHR, textStatus, errorThrown) {
            qahm.log_ajax_error(jqXHR, textStatus, errorThrown);
            const cb = qahm.drawTableAjax.bind(null, action, table, datePickerTerm);
            table.errorReload(cb);
        }
    ).always(
        function () {
        }
    );
};


function dateStringSlicer ( yyyy_mm_dd ) {
	let obj = new Object();
	obj['Y'] = Number( yyyy_mm_dd.substr( 0,4 ) );
	obj['M'] = Number( yyyy_mm_dd.substr( 5,2 ) );
	obj['D']  = Number( yyyy_mm_dd.substr( 8,2 ) );
	return obj
}







/** ---------------------------------
 * organize chart data
 */
let gChartDataArray = {};

qahm.statsOrganizeChartData = function(qahmParam) {
	gChartDataArray = {
		numPvs:[],
		numSessions:[],
		numUsers:[],
	};

	let indexMarker = 0;

	for ( let ddd = 0; ddd < gPickedDatesAry.length; ddd++ ) {
		if ( moment(gPickedDatesAry[ddd], 'YYYY-MM-DD') >= gTodayAM0 ) {
			gChartDataArray.numPvs.push( null );
			gChartDataArray.numSessions.push( null );
			gChartDataArray.numUsers.push( null );
		} else {
			let dataExists = false;
			for ( let iii = indexMarker; iii < qahmParam.length; iii++ ) {
				if( qahmParam[iii]['date'] == gPickedDatesAry[ddd] ) {
					gChartDataArray.numPvs.push( Number(qahmParam[iii]['pv_count']) );
					gChartDataArray.numSessions.push( Number(qahmParam[iii]['session_count']) );
					gChartDataArray.numUsers.push( Number(qahmParam[iii]['user_count']) );
					indexMarker = iii;
					dataExists = true;
					break;
				}
			}
			if ( ! dataExists ) {
				gChartDataArray.numPvs.push( 0 );
				gChartDataArray.numSessions.push( 0 );
				gChartDataArray.numUsers.push( 0 );
			}
		}
	}

	if (checkDataMade){
		console.log('gChartDataArray', gChartDataArray);
	}

};



/** ---------------------------------
 * aggregate total count in the period
 */

let totalOfArray = (accumulator, currentValue) => accumulator + currentValue;

let gSum3CountsJson = {};
qahm.statsAggrPeriodTotal = function() {
	gSum3CountsJson = {
		numPvs : 0,
		numSessions : 0,
		numUsers : 0,
	};
	if ( gPickedDatesAry.length > 1 ) {
		gSum3CountsJson = {
			numPvs :			 gChartDataArray.numPvs.reduce(totalOfArray),
			numSessions : gChartDataArray.numSessions.reduce(totalOfArray),
			numUsers :	 gChartDataArray.numUsers.reduce(totalOfArray),
		};
	} else {
		gSum3CountsJson = {
			numPvs :			 gChartDataArray.numPvs[0],
			numSessions : gChartDataArray.numSessions[0],
			numUsers :  gChartDataArray.numUsers[0],
		};
	}
};



/** ------------------------------
 *  fill "total number" of period aggregation
 */
qahm.statsFillTotal = function(){
	let statsBlankAndValues = {
		'qa-num-readers': gSum3CountsJson.numUsers,
		'qa-num-sessions': gSum3CountsJson.numSessions,
		'qa-num-pvs': gSum3CountsJson.numPvs,
	};
	for ( let blankId in statsBlankAndValues ) {
		if ( isNaN(statsBlankAndValues[blankId]) ) {
			statsBlankAndValues[blankId] = '--';
		}
		let blankToFill = document.getElementById(blankId);
		blankToFill.innerText = statsBlankAndValues[blankId];
	}
};


/**-------------------------------
 * to clear the chart
 */
qahm.clearPreChart = function(chartVar) {
	if ( typeof chartVar !== 'undefined' ) {
		chartVar.destroy();
	}
}
qahm.resetCanvas = function(canvasId, attr = '') {
	let container = document.getElementById(canvasId).parentNode;
	if (container) {
        container.innerHTML = '&nbsp;';
        container.innerHTML = `<canvas id="${canvasId}" ${attr}></canvas>`;
    }
}


/** ---------------------------------------------------
 * drawing graph chart
 */

let statsChart1;

let chartOneColor = {
	// users:		{ backgroundColor: "rgba(0, 123, 187, 1)", borderColor: "rgba(0, 123, 187, 1)" },
	// sessions: { backgroundColor: "rgba(242, 160, 161)", borderColor: "rgba(242, 160, 161)"},
	// pvs:			{ backgroundColor: "rgba(168, 201, 127)", borderColor: "rgba(168, 201, 127)"  },
	// users:		{ backgroundColor: "rgba(205, 212, 218, 1)", borderColor: "rgba(90, 110, 131, 1)" },
	// sessions: { backgroundColor: "rgba(193, 173, 193, 1)", borderColor: "rgba(136, 97, 116, 1)"},
	// pvs:			{ backgroundColor: "rgba(190, 199, 180, 1)", borderColor: "rgba(150, 165, 135, 1)"  },
	users:		{ backgroundColor: qahm.graphColorBaseA[2], borderColor: qahm.graphColorBaseA[2] },
	sessions: { backgroundColor: qahm.graphColorBaseA[0], borderColor: qahm.graphColorBaseA[0]},
	pvs:			{ backgroundColor: qahm.graphColorBaseA[1], borderColor: qahm.graphColorBaseA[1]  },


};

/*
let gOneBkgrdcolor = [
	[ "rgba(0, 123, 187, 1)","rgba(242, 160, 161)","rgba(168, 201, 127)" ],
	[ "rgba(0,123,187,0.2)","rgba(242,160,161,0.2)","rgba(168,201,127,0.2)" ]
]; 
let gOneBdrcolor = [
	[ "rgba(0, 123, 187, 1)","rgba(242, 160, 161)","rgba(168, 201, 127)" ],
	[ "rgba(0,123,187,0.4)","rgba(242,160,161,0.4)","rgba(168,201,127,0.5)" ]
];
*/
let filterSvg = '<svg width="15px" height="15px" version="1.1" id="レイヤー_1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 320 512" style="enable-background:new 0 0 320 512;" xml:space="preserve"><path fill="#fcc800" d="M20.7,361h85.7c7.7,0,11.6,10.5,6.1,16.7l-42.8,48.4c-3.4,3.8-8.9,3.8-12.2,0l-42.9-48.4C9.1,371.5,13,361,20.7,361z"></path><g><path fill="#fcc800" d="M305.5,86H26.5c-12.8,0-19.3,17.3-10.2,27.4l111.3,123.9v138c0,5.2,2.3,10.2,6.2,13.2l48.1,37.5c9.5,7.4,22.7-0.1,22.7-13.2V237.4l111.3-123.9C324.8,103.3,318.4,86,305.5,86z"></path></g></svg>';
let legendItems;


qahm.statsDrawChartOne = function( labelDateAry, dataAry ) {
	if ( updatingChart ) {
		qahm.clearPreChart(statsChart1);
		qahm.resetCanvas('statsChart1');
	}
	let axisMax;
	let axisStepSize;
	let keepAxis = false;
	let linePointStyle = {};
	if ( labelDateAry.length <= 3 ) {
		linePointStyle = {
			borderWidth: 3,
			radius: 10,
			pointHoverRadius: 15,
		}
	} else if ( labelDateAry.length > 150 ) {
		linePointStyle = {
			borderWidth: 2,
			radius: 0,
			pointHoverRadius: 3,
		}
	} else if ( labelDateAry.length >= 85 ) {
		linePointStyle = {
			borderWidth: 2.5,
			radius: 2,
			pointHoverRadius: 3,
		}
	}else {
		linePointStyle = {
			borderWidth: 3,
			radius: 4,
			pointHoverRadius: 6,
		}
	}

	let ctx = document.getElementById("statsChart1").getContext('2d');
	statsChart1 = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: labelDateAry,
			datasets: [
				{
					type: 'line',
					label: qahml10n['graph_users'],
					data: dataAry.numUsers,
					backgroundColor: chartOneColor.users.backgroundColor,
					borderColor: chartOneColor.users.borderColor,
					borderJoinStyle: 'bevel',
					borderWidth: linePointStyle.borderWidth,
					borderDash: [10, 1, 2, 1],
					pointStyle: 'rectRot',
					radius: linePointStyle.radius,
					pointHoverRadius: linePointStyle.pointHoverRadius,
					lineTension: 0,
					fill: false,
					yAxisID: 'hidden-y-axis',
					order: 3
				},
				{
					type: 'line',
					label: qahml10n['graph_sessions'],
					data: dataAry.numSessions,
					backgroundColor: chartOneColor.sessions.backgroundColor,
					borderColor: chartOneColor.sessions.borderColor,
					borderJoinStyle: 'bevel',
					borderWidth: linePointStyle.borderWidth,
					pointStyle: 'rect',
					radius: linePointStyle.radius,
					pointHoverRadius: linePointStyle.pointHoverRadius,
					lineTension: 0,
					fill: false,
					yAxisID: 'hidden-y-axis',
					order: 4
				},  
				{
					//type: 'bar',
					label: qahml10n['graph_pvs'],
					data: dataAry.numPvs,
					backgroundColor: chartOneColor.pvs.backgroundColor,
					borderColor: chartOneColor.pvs.borderColor,
					borderWidth: 2,
					maxBarThickness: 100,
					yAxisID: 'main-y-axis',
					order: 6
				}
			]
		},
		options: {
			spanGaps: false,
			responsive: true,
			maintainAspectRatio: false,
			title: {
				display: false,
				text: 'graph title',
				padding:3
			},
			scales: {
				yAxes: [{
					id: 'main-y-axis',
					position: 'left',
          type: 'linear',
					ticks: {
						min: 0,
					},
					beforeBuildTicks: function(axis) {
						if ( keepAxis ) {
							axis.max = axisMax;
							axis.stepSize = axisStepSize;
						} else {
							if( axis.max < 10 ) {
								axis.max = 10;
								axis.stepSize = 1;
							}
							axisMax = axis.max;
							axisStepSize = axis.stepSize;
							keepAxis = true;
						}
					},
				}, {
					id: 'hidden-y-axis',
					position: 'right',
          type: 'linear',
					gridLines: {
					display: false
					},
					ticks: {
						min: 0,
						display: false
					},
					beforeBuildTicks: function(axis) {
							axis.max = axisMax;
							axis.stepSize = axisStepSize;
					} 
				}],
				xAxes: [{
					stacked: true,
				}]
			},
			legend: {
				display: false,
				position: 'top',
				labels: {
					usePointStyle: true
				}
			},
			legendCallback: function(chart) {
				let legendHtml = [];
				let labelDeco;
				legendHtml.push('<ul>');
				let dataSet = chart.data.datasets;
				for ( let lll = 0; lll < dataSet.length; lll++ ) {
					let meta = statsChart1.getDatasetMeta(lll);
					if( meta.hidden === true ){
						labelDeco = 'style="text-decoration:line-through;"'
					} else {
						labelDeco = '';
					}
					/*
					if( lll == 3 ) {
						legendHtml.push('</ul><ul>');
					}
					*/
					legendHtml.push('<li>');
					legendHtml.push('<div class="pt-'+dataSet[lll].pointStyle+'" style="background-color:' + dataSet[lll].backgroundColor +'; border-color:'+dataSet[lll].borderColor +'"></div>');
					/*
					if ( lll >= 3 ) {
						legendHtml.push('<span>'+filterSvg+'</span>');
					}
					*/
					legendHtml.push('<span class="legend-label"'+labelDeco +'>'+dataSet[lll].label+'</span>');
					legendHtml.push('</li>');
				}
				legendHtml.push('</ul>');
				return legendHtml.join("");
			},
			tooltips: {
				mode: 'index',
				itemSort: function(a, b, data) {
					return (a.datasetIndex - b.datasetIndex);
				},
				callbacks: {
					label: function(tooltipItem, data) {
							let label;
							//if ( tooltipItem.datasetIndex < 3 ) {
								label = data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel;
							//} else {
							//	label = 'filter';
							//	label += data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel;;
							//}
							return label;
					}
			}

			},
			/*
			onClick: function(e) {
				let element = statsChart1.getElementAtEvent(e);
				//console.log(element);
				if (! element || element.length === 0) return;
				let meIndex = element[0]._index;
				let meLabelDate = labelDateAry[meIndex];
				console.log('clicked date:', meLabelDate);
			},
			*/
		}
	});
	qahm.legendHere();

}; //end of "statsDrawChartOne" definition


qahm.legendHere = function() {
	document.getElementById('chart1-legend').innerHTML = statsChart1.generateLegend();

	legendItems = document.getElementById('chart1-legend').getElementsByTagName('li');
	for (let i = 0; i < legendItems.length; i++) {
		legendItems[i].addEventListener("click", (e) =>
			updateDataset(e.target.parentNode, i)
		);
	}
	//to switch dataset when the legend clicked
	let updateDataset = (legendLi, index) => {
		let meta = statsChart1.getDatasetMeta(index);
		let labelSpan = legendLi.querySelector('span.legend-label');
		let hiddenData = meta.hidden === true ? true : false;
		if (hiddenData) {
			labelSpan.style.textDecoration = "none";
			meta.hidden = null;
		} else {
			labelSpan.style.textDecoration = "line-through";
			meta.hidden = true;
		}
		statsChart1.update();
	};

};



/** ---------------------------------------------------
 * 過去データCSVダウンロード
 *  to download past data as csv
 */
qahm.statsDownloadTCsv = function(fromDate, toDate) {

	//jsonをtsv/csv文字列に編集する関数
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


	// データ数によって、ファイル期間を決定
	let dataBetween = [];
	let fromDateStr = moment(fromDate).format('YYYY-MM-DD');
	let toDateStr = moment(toDate).format('YYYY-MM-DD');

	if ( gSum3CountsJson.numPvs < 300000 ) {
		dataBetween.push( [fromDateStr, toDateStr] );
	} else {
		let gessuu = toDate.getMonth() - fromDate.getMonth();
		if ( gessuu > 0 ) {
			//月ごとに区切る
			for ( let mmm = 0; mmm <= gessuu; mmm++ ) {
				if( mmm === 0 ) {
					dataBetween.push( [fromDateStr, moment(fromDate).endOf('month').format('YYYY-MM-DD')] );
				} else if ( mmm === gessuu ) {
					dataBetween.push( [moment(toDate).startOf('month').format('YYYY-MM-DD'), toDateStr] );
				} else {
					dataBetween.push( [moment(fromDate).add( mmm, 'months').startOf('month').format('YYYY-MM-DD'), moment(fromDate).add( mmm, 'months').endOf('month').format('YYYY-MM-DD')] );
				}
			}
		} else {
			//（一応　エンタープライズになっているはず。）
			dataBetween.push( [fromDateStr, toDateStr] );
			/*
			//半分に区切ってみる？
			let nissuu = toDate.getDate() - fromeDate.getDate();
			let hanbun = Math.floor( nissuu / 2 );
			dataBetween.push( [fromDateStr, moment(fromDate).add(hanbun, 'days').format('YYYY-MM-DD')] );
			dataBetween.push( [moment(fromDate).add(hanbun+1, 'days').format('YYYY-MM-DD'), toDateStr] );
			*/
		}
	}

	if(checkCsvDLStep){
		console.log('dataBetween:', dataBetween);
	}


	//データ取得とファイルダウンロード
	jQuery(
		function(){
			let noDataMsg = '';
			let nnn = 0;

			let getAndDownloadCsvData = function() {
				let deferredDl = new jQuery.Deferred;
				qahm.statsCsvParam = null;

				if ( nnn < dataBetween.length ) {
					jQuery.ajax(
						{
							type: 'POST',
							url: qahm.ajax_url,
							dataType : 'json',
							data: {
								'action' : 'qahm_ajax_select_data',
								'table' : 'view_pv',
								'select': '*',
								'date_or_id':`date = between ${dataBetween[nnn][0]} and ${dataBetween[nnn][1]}`,
								'count' : false,
								'nonce':qahm.nonce_api
							}
						}
					).done(
						function( data ){
							qahm.statsCsvParam = data;

							let csvParam = qahm.statsCsvParam.flat(1);
								if(checkCsvDLStep){
									console.log('csvParam:', csvParam);
								}

							if ( csvParam.length > 0 ) {
								let fileName = 'QA_Data_' + moment(dataBetween[nnn][0], 'YYYY-MM-DD').format('YYYYMMDD') + '-' + moment(dataBetween[nnn][1], 'YYYY-MM-DD').format('YYYYMMDD');
									if(checkCsvDLStep){
										console.log('fileName', fileName );
									}
								let csvData = jsonToTCsv( csvParam, '\t');
//								let csvData = jsonToTCsv( csvParam, ',');
						
								//出力ファイル名
								let exportedFilename = (fileName || 'export') + '.tsv';
//								let exportedFilename = (fileName || 'export') + '.csv';
								//BLOBに変換
								let blob = new Blob([ bom, csvData ], { 'type' : 'text/tsv' });
//								let blob = new Blob([ bom, csvData ], { 'type' : 'text/csv' });
						
								let downloadLink = document.createElement('a');
								downloadLink.download = exportedFilename;
								downloadLink.href = URL.createObjectURL(blob);
								downloadLink.dataset.downloadurl = ['text/plain', downloadLink.download, downloadLink.href].join(':'); //いる？いらない？　HTML要素に追加されたカスタム属性のデータを表す。これらの属性はカスタムデータ属性と呼ばれており、 HTML とその DOM 表現との間で、固有の情報を交換できるようにします。すべてのカスタムデータは、その属性を設定した要素の HTMLElement インターフェイスを通して使用することができます。 HTMLElement.dataset プロパティでカスタムデータにアクセスできます。
								downloadLink.click();
						
								URL.revokeObjectURL(downloadLink.href);
								
							} else {
								noDataMsg += qahm.sprintfAry( qahml10n['download_done_nodata'], dataBetween[nnn][0], dataBetween[nnn][1] );
							}
							
							nnn++;
							getAndDownloadCsvData();		
						}
					).fail(
						function( jqXHR, textStatus, errorThrown ){
							qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
							deferredDl.reject();
						}
					).always(
						function(){
						}
					);

				} else {
					deferredDl.resolve();
				}
				return deferredDl.promise();
			}; //end of 'getAndDownloadCsvData' definition
	

			//実行			
			getAndDownloadCsvData().then(
			function(){
				if ( noDataMsg ) {
					alert(noDataMsg);
				}
			}, function(e){
				alert( qahml10n['download_error1'] + '\n' + qahml10n['download_error2'] );
			}
			);
		}
	)
};
/** ------------------------------
 * 各Tableの作成
 *
 */

qahm.makeTable  = function(table, ary) {
    table.rawDataArray = ary;
    if ( ! table.headerTableByID ) {
        table.generateTable();
    } else {
        table.updateTable();
    }
};

qahm.makeChannelGraph = function (ary) {
    //generate channel graph
    let chlabels   = new Array();
    let chsessions = new Array();
    let idx = 0;
    for ( let mmm = ary.length -1; 0 <= mmm; --mmm ) {
        if ( Number( ary[mmm][3] ) !== 0 ) {
            chsessions[idx] = Number( ary[mmm][3] );
            chlabels[idx]   = ary[mmm][0];
            idx++;
        }
    }
    for ( let lll = 0; lll < chsessions.length; lll++ ) {
        for (let mmm = chsessions.length - 1; lll < mmm; --mmm) {
            let right_ss = chsessions[mmm];
            let left_ss = chsessions[mmm - 1];
            let right_lb = chlabels[mmm];
            let left_lb = chlabels[mmm - 1];
            if (left_ss < right_ss) {
                chsessions[mmm - 1] = right_ss;
                chsessions[mmm] = left_ss;
                chlabels[mmm - 1] = right_lb;
                chlabels[mmm] = left_lb;
            }
        }
    }

    let ctxchannel = document.getElementById('channel_graph');
    let myChart = new Chart(ctxchannel, {
      type: 'doughnut',
      data: {
        labels: chlabels,
        datasets: [{
          data: chsessions,
          backgroundColor:  ['#31356E','#2F5F98','#2D8BBA','#41B8D4','#6CE5E8'],
        }],
      },
      options: {
          legend: {
              labels: {
                  fontSize: 9
              },
          },
        },
    });
};

qahm.makeLandingPageTable = function (table, ary) {
    let newary = new Array();
    let uri        = new URL(window.location.href);
    let httplen    = uri.origin.length;

    //generate lp table
    for ( let nnn = 0; nnn < ary.length; nnn++ ) {
        let editurl  = qahm.ajax_url.replace('admin-ajax.php', 'post.php');

		newary[nnn] = new Array();
		newary[nnn][0]  = ary[nnn][0];
		newary[nnn][1]  = ary[nnn][1];
		newary[nnn][2]  = ary[nnn][2].slice( httplen );
		newary[nnn][3]  = ary[nnn][3];
		newary[nnn][4]  = 0;
		newary[nnn][5]  = ary[nnn][5];
		newary[nnn][6]  = 0;
		newary[nnn][7]  = 0;
		newary[nnn][8]  = 0;
		newary[nnn][9]  = ary[nnn][11];
		newary[nnn][10] = ary[nnn][12];
		newary[nnn][11] = ary[nnn][13];
		newary[nnn][12] = editurl + '?post=' + ary[nnn][9].toString()+ '\&action=edit';
		newary[nnn][13] = '';

        let session    = ary[nnn][3];
        let newsession = ary[nnn][4];
        let bounce     = ary[nnn][6];
        let pvcount    = ary[nnn][7];
        let timeon     = ary[nnn][8];
		let page_id     = ary[nnn][10];

        //ヒートマップリンク
		newary[nnn][13] = '<span class="mainblue dashicons dashicons-desktop qahm-heatmap-link" data-device_name="dsk" data-page_id="'+ page_id + '"></span>' +
			'<span class="mainblue dashicons dashicons-tablet qahm-heatmap-link"  data-device_name="tab" data-page_id="'+ page_id + '"></span>' +
			'<span class="mainblue dashicons dashicons-smartphone qahm-heatmap-link"  data-device_name="smp" data-page_id="'+ page_id + '"></span>';

        if ( 0 < session ) {
            //新規セッション率
            let newsessionrate   = ( newsession / session ) * 100;
            newary[nnn][4] = newsessionrate.toFixed(2);
            //ページ／セッション
            let pagesession = (pvcount / session);
            newary[nnn][7] = pagesession.toFixed(2);
            //平均セッション時間
            let avgsessiontime   = (timeon / session);
            newary[nnn][8] = avgsessiontime.toFixed(0);
            //直帰率はLPのうちの直帰数
            let bouncerate = (bounce / session) * 100;
            newary[nnn][6] = bouncerate.toFixed(1);
        }
    }

    table.rawDataArray = newary;
    if ( ! table.headerTableByID ) {
        table.generateTable();
    } else {
        table.updateTable();
    }
};

qahm.makeGrowthPageTable = function (table, ary) {
    let newary  = new Array();
    let uri     = new URL(window.location.href);
    let httplen = uri.origin.length;

    for ( let iii = 0; iii < ary.length; iii++ ) {
        newary[iii] = new Array();
		newary[iii][0]  = ary[iii][0];
		newary[iii][1]  = ary[iii][1];
		newary[iii][2]  = ary[iii][2].slice( httplen );
		newary[iii][3]  = ary[iii][3];
		newary[iii][4]  = ary[iii][4];
		newary[iii][5]  = ary[iii][5];
		newary[iii][6]  = ary[iii][6];
		newary[iii][7]  = ary[iii][8];
		newary[iii][8]  = ary[iii][9];
		newary[iii][9]  = ary[iii][10];
		newary[iii][10] = ary[iii][7];
		
		//ヒートマップリンク
		let page_id     = ary[iii][0];
		newary[iii][11] = '<span class="mainblue dashicons dashicons-desktop qahm-heatmap-link" data-device_name="dsk" data-page_id="'+ page_id + '"></span>' +
        '<span class="mainblue dashicons dashicons-tablet qahm-heatmap-link"  data-device_name="tab" data-page_id="'+ page_id + '"></span>' +
        '<span class="mainblue dashicons dashicons-smartphone qahm-heatmap-link"  data-device_name="smp" data-page_id="'+ page_id + '"></span>';
    }

    table.rawDataArray = newary;
    if ( ! table.headerTableByID ) {
        table.generateTable();
    } else {
        table.updateTable();
    }
};


qahm.makeAllPageTable = function (table, ary) {
    let newary = new Array();
	let uri      = new URL(window.location.href);
	let httplen  = uri.origin.length;

    //generate allpage table
    for ( let nnn = 0; nnn < ary.length; nnn++ ) {
        let editurl  = qahm.ajax_url.replace('admin-ajax.php', 'post.php');

		newary[nnn]     = new Array();
		newary[nnn][0]  = ary[nnn][0];
		newary[nnn][1]  = ary[nnn][1];
        newary[nnn][2]  = ary[nnn][2].slice( httplen );
		newary[nnn][3]  = ary[nnn][3];
		newary[nnn][4]  = ary[nnn][4];
		newary[nnn][5]  = 0;
		newary[nnn][6]  = ary[nnn][6];
		newary[nnn][7]  = 0;
		newary[nnn][8]  = 0;
        newary[nnn][9]  = ary[nnn][11];
		newary[nnn][10] = editurl + '?post=' + ary[nnn][9].toString()+ '\&action=edit';
		newary[nnn][11] = '';

        let pvcounts = ary[nnn][3];
        let times    = ary[nnn][5];
        let lpcounts = ary[nnn][6];
        let bounces  = ary[nnn][7];
        let exits    = ary[nnn][8];
		let page_id  = ary[nnn][10];

        //ヒートマップリンク
		newary[nnn][11] = '<span class="mainblue dashicons dashicons-desktop qahm-heatmap-link"  data-device_name="dsk" data-page_id="'+ page_id + '"></span>' +
			'<span class="mainblue dashicons dashicons-tablet qahm-heatmap-link"  data-device_name="tab" data-page_id="'+ page_id + '"></span>' +
			'<span class="mainblue dashicons dashicons-smartphone qahm-heatmap-link"  data-device_name="smp" data-page_id="'+ page_id + '"></span>';

        if ( 0 < pvcounts ) {
            //平均ページ滞在時間（秒）
            let sessiontim = (times / pvcounts);
            newary[nnn][5] = sessiontim.toFixed(0);
            //離脱率はPV数のうちの離脱数
            let exitrate   = (exits / pvcounts) * 100;
            newary[nnn][8] = exitrate.toFixed(1);
        }
        if ( 0 < lpcounts ) {
            //直帰率はLPのうちの直帰数
            let bouncerate = (bounces / lpcounts) * 100;
            newary[nnn][7] = bouncerate.toFixed(1);
        }

    }

    table.rawDataArray = newary;
    if ( ! table.headerTableByID ) {
        table.generateTable();
    } else {
        table.updateTable();
    }
};