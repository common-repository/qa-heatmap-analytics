var qahm = qahm || {};

/**
 *  テーブルの構築処理
 */
qahm.gscRewriteTable = '';
qahm.initRewriteTable = function() {
	qahm.gscRewriteTable = new QATableGenerator();
	qahm.gscRewriteTable.thLabelHtml = '<th colspan="3">' + qahml10n['update_page'] + '</th><th colspan="2">' + qahml10n['impression'] + '</th><th colspan="2">' + qahml10n['click_num'] + '</th><th>&nbsp;</th>';
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['create_update_date'],type:'string',hasFilter:true,colParm:'style="width: 7%;"',tdHtml:'<span>%!me</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['title'],type: 'string',hasFilter:true,colParm:'style="width: 15%;"',tdHtml:'<span>%!me</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['url'],type: 'string',hasFilter:true,colParm:'style="width: 10%;"',tdHtml:'<span>%!me</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['total_num'],type: 'number',hasFilter:true,colParm:'style="width: 5%;"',tdHtml:'<span>%!me</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({isHide:true});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['change_rate'],type: 'number',hasFilter:true,colParm:'style="width: 5%;"',tdHtml:'<span>%!04</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['total_num'],type: 'number',hasFilter:true,colParm:'style="width: 5%;"',tdHtml:'<span>%!me</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({isHide:true});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['change_rate'],type: 'number',hasFilter:true,colParm:'style="width: 5%;"',tdHtml:'<span>%!07</span>'});
	qahm.gscRewriteTable.dataObj.body.header.push({title:qahml10n['edit'],type: 'string',hasFilter:false,colParm:'style="width: 5%;"',tdHtml:'<a href="' + qahm.site_url + '/wp-admin/post.php?post=%!me&action=edit" target="_blank" rel="noopener noreferrer">' + qahml10n['edit'] + '</a>'});

	qahm.gscRewriteTable.visibleSort.index = 1;
	qahm.gscRewriteTable.visibleSort.order = 'dsc';
	qahm.gscRewriteTable.targetID = 'rewrite-table';
	qahm.gscRewriteTable.progBarDiv = 'rewrite-table-progbar';
	qahm.gscRewriteTable.progBarMode = 'embed';
	qahm.gscRewriteTable.dataTRNowBlocks.divheightMax = 500;
	qahm.gscRewriteTable.prefix = 'qarewrite';
	let plugindir = qahm.plugin_dir_url;
	qahm.gscRewriteTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
};

qahm.gscManualKeyTable = '';
qahm.initManualKeyTable = function() {
    qahm.gscManualKeyTable = new QATableGenerator();
	qahm.gscManualKeyTable.thLabelHtml = '<th colspan="3">' + qahml10n['landing_page'] + '&nbsp;(' + qahml10n['landing_page_comment'] + ')' + '</th><th colspan="3">' + qahml10n['aggregation_period'] + '</th><th colspan="5">' + qahml10n['keyword_pos_change'] + '</th>';
    qahm.gscManualKeyTable.dataObj.meta.has_header = true;
	qahm.gscManualKeyTable.dataObj.body.header.push({isHide:true});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['search_keyword'],type: 'string',hasFilter:true,colParm:'style="width: 20%;"',tdHtml:'<a href="#seo-monitoring-keyword-detail-pos" data-wp_qa_id="%!00" data-keyword="%!01">%!me</a>'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['url'],type: 'string',hasFilter:true,colParm:'style="width: 17%;"'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['goal_complite'] + '<span class="qahm-tooltip" data-qahm-tooltip="' + qahml10n['goal_complite_tooltip'] + '"><i class="far fa-question-circle"></i></span>',type: 'number',hasFilter:true,colParm:'style="width: 7%;"',tdHtml:'%!me'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['view_count'] + '<br>' + qahml10n['total'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['click'] + '<br>' + qahml10n['total'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['ctr'] + '<br>' + qahml10n['average'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',tdHtml:'%!me%'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['prev_pos'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['recent_change'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',format:(text, tableidx, vrowidx, visibleary) => {
		let ret = text;
		if ( 1 <= Number( text ) ) {
		    ret = ret + '&nbsp;<span style="color:red;">↑</span>';
		}
		if ( Number( text ) <= -1 ) {
		    ret = ret + '&nbsp;<span style="color:blue;">↓</span>';
		}
		return ret;
    }});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['recent_pos'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['trend'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',format:(text, tableidx, vrowidx, visibleary) => {
		let ret = text + '%';
		if ( 1 <= Number( text ) ) {
		    ret = ret + '&nbsp;<span style="color:red;">↑</span>';
		}
		if ( Number( text ) <= -1 ) {
		    ret = ret + '&nbsp;<span style="color:blue;">↓</span>';
		}
		return ret;
    }});
    qahm.gscManualKeyTable.dataObj.body.header.push({title:qahml10n['reliability'],type: 'string',hasFilter:true,colParm:'style="width: 7%;"'});
    //qahm.gscManualKeyTable.rawDataArray = manuary;
    qahm.gscManualKeyTable.dataLimit = 5000;
    // qahm.gscManualKeyTable.dataTRNowBlocks.divheight = 300;
	qahm.gscManualKeyTable.dataTRNowBlocks.divheightMax = 500;
    qahm.gscManualKeyTable.targetID = "ManualKeyTable";
    qahm.gscManualKeyTable.progBarDiv = 'ManualKeyTableProg';
    qahm.gscManualKeyTable.prefix = 'manualkey';
    qahm.gscManualKeyTable.visibleSort.index = 4;
    qahm.gscManualKeyTable.visibleSort.order = 'dsc';
    //qahm.gscManualKeyTable.generateTable();
	let plugindir = qahm.plugin_dir_url;
	qahm.gscManualKeyTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';

};

qahm.gscAutoKeyTable = '';
qahm.initAutoKeyTable = function() {
    qahm.gscAutoKeyTable = new QATableGenerator();
	qahm.gscAutoKeyTable.thLabelHtml = '<th colspan="3">' + qahml10n['landing_page'] + '&nbsp;(' + qahml10n['landing_page_comment'] + ')' + '</th><th colspan="3">' + qahml10n['aggregation_period'] + '</th><th colspan="5">' + qahml10n['keyword_pos_change'] + '</th>';
    qahm.gscAutoKeyTable.dataObj.meta.has_header = true;
	qahm.gscAutoKeyTable.dataObj.body.header.push({isHide:true});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['search_keyword'],type: 'string',hasFilter:true,colParm:'style="width: 20%;"',tdHtml:'<a href="#seo-monitoring-keyword-detail-pos" data-wp_qa_id="%!00" data-keyword="%!01">%!me</a>'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['url'],type: 'string',hasFilter:true,colParm:'style="width: 17%;"'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['goal_complite'] + '<span class="qahm-tooltip" data-qahm-tooltip="' + qahml10n['goal_complite_tooltip'] + '"><i class="far fa-question-circle"></i></span>',type: 'number',hasFilter:true,colParm:'style="width: 7%;"',tdHtml:'%!me'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['view_count'] + '<br>' + qahml10n['total'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['click'] + '<br>' + qahml10n['total'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['ctr'] + '<br>' + qahml10n['average'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',tdHtml:'%!me%'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['prev_pos'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['recent_change'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',format:(text, tableidx, vrowidx, visibleary) => {
		let ret = text;
		if ( 1 <= Number( text ) ) {
		    ret = ret.toString() + '&nbsp;<span style="color:red;">↑</span>';
		}
		if ( Number( text ) <= -1 ) {
		    ret = ret.toString() + '&nbsp;<span style="color:blue;">↓</span>';
		}
		return ret;
	}});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['recent_pos'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"'});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['trend'],type: 'number',hasFilter:true,colParm:'style="width: 7%;"',format:(text, tableidx, vrowidx, visibleary) => {
		let ret = text + '%';
		if ( 1 <= Number( text ) ) {
		    ret = ret + '&nbsp;<span style="color:red;">↑</span>';
		}
		if ( Number( text ) <= -1 ) {
		    ret = ret + '&nbsp;<span style="color:blue;">↓</span>';
		}
		return ret;
	}});
    qahm.gscAutoKeyTable.dataObj.body.header.push({title:qahml10n['reliability'],type: 'string',hasFilter:true,colParm:'style="width: 7%;"'});
    // qahm.gscAutoKeyTable.dataTRNowBlocks.divheight = 800;
    qahm.gscAutoKeyTable.dataLimit = 5000;
	qahm.gscAutoKeyTable.dataTRNowBlocks.divheightMax = 500;
    qahm.gscAutoKeyTable.targetID = "AutoKeyTable";
    qahm.gscAutoKeyTable.progBarDiv = 'AutoKeyTableProg';
    qahm.gscAutoKeyTable.prefix = 'autokey';
    qahm.gscAutoKeyTable.visibleSort.index = 4;
    qahm.gscAutoKeyTable.visibleSort.order = 'dsc';
	let plugindir = qahm.plugin_dir_url;
	qahm.gscAutoKeyTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';

};



qahm.generateTable = function( createRewriteFlag, createManualKeyFlag, createAutoKeyFlag ) {
	let rewriteAry = [];
	let manualKeyAry = [];
	let autoKeyAry = [];
	for ( wpQaId in qahm.gscAry ) {
		if( ! qahm.gscAry.hasOwnProperty(wpQaId) ) {
			continue;
		}
		
		if ( createRewriteFlag ) {
			// ∞表記を実装しつつソートできるようにするため、table.jsのhide枠を使用している
			let chgImpNum   = qahm.gscAry[wpQaId]['change_impressions'];
			let chgClickNum = qahm.gscAry[wpQaId]['change_clicks'];
			if ( chgImpNum === 0 ) {
				chgImpNum = Number.MAX_SAFE_INTEGER;
				chgImpStr = '∞';
			} else {
				chgImpStr = chgImpNum + '%';
			}
			if ( chgClickNum === 0 ) {
				chgClickNum = Number.MAX_SAFE_INTEGER;
				chgClickStr = '∞';
			} else {
				chgClickStr = chgClickNum + '%';
			}
			rewriteAry.push( [
				qahm.gscAry[wpQaId]['date'],
				qahm.gscAry[wpQaId]['title'],
				qahm.gscAry[wpQaId]['url'],
				qahm.gscAry[wpQaId]['total_impressions'],
				chgImpStr,
				chgImpNum,
				qahm.gscAry[wpQaId]['total_clicks'],
				chgClickStr,
				chgClickNum,
				wpQaId
			] );
		}

		for ( keyword in qahm.gscAry[wpQaId]['query'] ) {
			if( ! qahm.gscAry.hasOwnProperty(keyword) ) {
				//console.log( keyword );

				if ( createManualKeyFlag ) {
					for ( let keyIdx = 0; keyIdx < qahm.seo_monitoring_keyword.length; keyIdx++ ) {
						if ( keyword === qahm.seo_monitoring_keyword[keyIdx] ) {
							manualKeyAry.push( [
								wpQaId,
								keyword,
								qahm.gscAry[wpQaId]['url'],
								0,
								qahm.gscAry[wpQaId]['query'][keyword]['total_impressions'],
								qahm.gscAry[wpQaId]['query'][keyword]['total_clicks'],
								qahm.gscAry[wpQaId]['query'][keyword]['avg_ctr'],
								qahm.gscAry[wpQaId]['query'][keyword]['prev_position'],
								Math.round( ( qahm.gscAry[wpQaId]['query'][keyword]['prev_position'] - qahm.gscAry[wpQaId]['query'][keyword]['recent_position'] ) * 10 ) / 10,
								qahm.gscAry[wpQaId]['query'][keyword]['recent_position'],
								qahm.gscAry[wpQaId]['query'][keyword]['trend'],
							] );
							break;
						}
					}
				}

				if ( createAutoKeyFlag ) {
					autoKeyAry.push( [
						wpQaId,
						keyword,
						qahm.gscAry[wpQaId]['url'],
						0,
						qahm.gscAry[wpQaId]['query'][keyword]['total_impressions'],
						qahm.gscAry[wpQaId]['query'][keyword]['total_clicks'],
						qahm.gscAry[wpQaId]['query'][keyword]['avg_ctr'],
						qahm.gscAry[wpQaId]['query'][keyword]['prev_position'],
						Math.round( ( qahm.gscAry[wpQaId]['query'][keyword]['prev_position'] - qahm.gscAry[wpQaId]['query'][keyword]['recent_position'] ) * 10 ) / 10,
						qahm.gscAry[wpQaId]['query'][keyword]['recent_position'],
						qahm.gscAry[wpQaId]['query'][keyword]['trend'],
					] );
				}
			}
		}
	}
	
	if ( createRewriteFlag && typeof qahm.gscRewriteTable !== 'undefined' && qahm.gscRewriteTable !== '' ) {
		qahm.gscRewriteTable.rawDataArray = rewriteAry;
		if( qahm.gscRewriteTable.rawDataArray.length === 0 ) {
			jQuery( '#rewrite-table' ).html( qahml10n['nodata_to_show'] );
		} else {
			jQuery( '#rewrite-table' ).text( '' );
			if ( ! qahm.gscRewriteTable.headerTableByID ) {
				qahm.gscRewriteTable.generateTable();
			} else {
				qahm.gscRewriteTable.updateTable();
			}
		}
	}
	
	if ( createManualKeyFlag && typeof qahm.gscManualKeyTable !== 'undefined' && qahm.gscManualKeyTable !== '' ) {
		qahm.gscManualKeyTable.rawDataArray = manualKeyAry;
		if ( qahm.gscManualKeyTable.rawDataArray.length === 0 ) {
			jQuery( '#ManualKeyTable' ).text( qahml10n['nodata_to_show'] );
		} else {
			jQuery( '#ManualKeyTable' ).text( '' );
			if ( ! qahm.gscManualKeyTable.headerTableByID ) {
				qahm.gscManualKeyTable.generateTable();
			} else {
				qahm.gscManualKeyTable.updateTable();
			}
		}
	}
	
	if ( createAutoKeyFlag && typeof qahm.gscAutoKeyTable !== 'undefined' && qahm.gscAutoKeyTable !== '') {
		qahm.gscAutoKeyTable.rawDataArray = autoKeyAry;
		if ( qahm.gscAutoKeyTable.rawDataArray.length === 0 ) {
			jQuery( '#AutoKeyTable' ).text( qahml10n['nodata_to_show'] );
		} else {
			jQuery( '#AutoKeyTable' ).text( '' );
			if ( ! qahm.gscAutoKeyTable.headerTableByID ) {
				qahm.gscAutoKeyTable.generateTable();
			} else {
				qahm.gscAutoKeyTable.updateTable();
			}
		}
	}
};
qahm.generateAutoTable = function( ) {
    qahm.autoKeyAry = [];
	qahm.autoKeyDetailAry = [];
    const pids = Object.keys( qahm.allGscAry );
	for ( let iii = 0; iii < pids.length; iii++ ) {
		let pid    = pids[iii];
		let pidobj = qahm.allGscAry[pid];
        let wpQaId = qahm.allGscAry[pid]['wp_qa_id'];
        let url    = qahm.allGscAry[pid]['url'];
        let title  = qahm.allGscAry[pid]['title'];
        for ( keyword in qahm.allGscAry[pid]['keyword']) {
			let key     = keyword;
			let dateary = qahm.allGscAry[pid]['keyword'][key]['date'];
			let imp     = 0;
			let clk     = 0;
			let posary  = [];
			let clkary  = [];
			let prevpos = 99;
			let lastary = qahm.allGscAry[pid]['keyword'][key]['date'][dateary.length -1];
			let lastpos = lastary['position'];
			let temppos = 0;
			let lastctr = qahm.roundToX( lastary['clicks'] / lastary['impressions'] * 100, 1 );
			let dayary  = new Array(dateary.length);
			let endDate   = jQuery("#analysis-date option:selected").data( 'end-date' );
			let endday    = new Date( endDate );
			dayary[dateary.length - 1] = endDate;
			for ( let ddd = 0; ddd < dateary.length; ddd++ ) {
				if ( 0 < ddd) {
					let unixtime = endday.setDate(endday.getDate() - 1 );
					let datetime = new Date( Number(unixtime) );
					let datestr  = qahm.getDataPeriod( datetime );
					dayary[dateary.length - ddd - 1]  = datestr;
				}
				let nowpos = qahm.allGscAry[pid]['keyword'][key]['date'][ddd]['position'];
				let nowclk = qahm.allGscAry[pid]['keyword'][key]['date'][ddd]['clicks'];
				imp = imp + dateary[ddd]['impressions'];
				clk = clk + dateary[ddd]['clicks'];
				if ( nowpos !== null ) {
					temppos = nowpos;
					if ( Number(prevpos) !== Number(nowpos) && Number(nowpos) !== Number(lastpos) ) {
						prevpos = nowpos;
					}
				}
				if ( 0 < dateary[ddd]['impressions'] ) {
					lastctr = qahm.roundToX( dateary[ddd]['clicks'] / dateary[ddd]['impressions'] * 100, 1 );
				}
				posary.push( nowpos );
				clkary.push( nowclk );
			}
			let ctr     = qahm.roundToX( ( clk / imp ) * 100, 1 );
			if ( lastpos === null ) {
				lastpos = temppos;
			}

			//トレンド（成長率）を求める。成長は1が一番大きく逆数で捉える。 5->1 = 5/1 = 500% 1->5 = 1/5 -> - 5/1 = -500%
			let dateXAry  = [posary.length];
			let dateXYAry = [];
			let correXAry = [];
			let correYAry = [];

			let rankmax = 10000;
			let rankmin = 1;
			let poscnt  = 0;
			for ( let xxx = 0; xxx < posary.length; xxx++ ) {
				dateXAry[xxx]  = xxx;
				if ( posary[xxx] !== null ) {
					dateXYAry.push( [ xxx, posary[xxx] ] );
					if ( rankmin < posary[xxx] ) {
						rankmin = posary[xxx];
					}
					if ( posary[xxx] < rankmax ) {
						rankmax = posary[xxx];
					}
					correXAry.push(xxx);
					correYAry.push(posary[xxx]);
					poscnt++;
				}
			}

			//simple-statistics.jsを使い回帰係数mと切片bが入ったオブジェクトを生成。相関係数も求める
			let regressionLineObj = ss.linearRegression( dateXYAry );

			const minDataSample = 7;
			let soukanHantei = '';
			let soukankeisu       = '';
			if ( 1 < poscnt ) {
				soukankeisu = ss.sampleCorrelation( correXAry, correYAry );
            }

			if ( isNaN( soukankeisu ) ) {
				soukanHantei = qahml10n['data_not_calc'];
				soukankeisu  = qahml10n['error'];
			} else {
				//相関係数の傾きは1が一番高い＝逆になる
				soukankeisu = qahm.roundToX( soukankeisu * -1, 2 );
				if ( poscnt < minDataSample ) {
					soukanHantei = qahml10n['data_none'];
					soukankeisu  = qahml10n['data_deficiency'];
				} else {
					let soukan = Math.abs( soukankeisu );
					if (soukan <= 0.2) {
						soukanHantei = qahml10n['data_low'];
					} else if ( soukan <=  0.4) {
						soukanHantei = qahml10n['data_middle'];
					} else if ( soukan <=  0.7) {
						soukanHantei = qahml10n['data_high'];
					} else if ( soukan <=  1) {
						soukanHantei = qahml10n['data_very_high'];
					}
				}
			}

			//回帰直線のデータ配列を作る
			let regressionAry = [posary.length];
			for ( let xxx = 0; xxx < posary.length; xxx++ ) {
				let yyy = ss.linearRegressionLine(regressionLineObj)( xxx );
				regressionAry[xxx] = yyy;
			}
			let start = 0;
			let goalx = posary.length - 1;
			let trend = regressionLineObj.m * -100;
			trend = qahm.roundToX( trend , 0 );

			//get goals
			let gcountall = 0;
			if (qahm.autoKeyAry[0] === undefined) {
				qahm.autoKeyAry[0] = new Array();
			}
            if ( qahm.goalsJson ) {
				for ( let gid = 1; gid < qahm.goalsLpArray.length; gid++ ) {
					if (qahm.autoKeyAry[gid] === undefined) {
						qahm.autoKeyAry[gid] = new Array();
					}
					let gcount = 0;
					if ( qahm.goalsLpArray[gid][pid] !== undefined) {
						gcount = Number(qahm.goalsLpArray[gid][pid]);

					}
					//配列に挿入
					qahm.autoKeyAry[gid].push( [
						wpQaId,
						key,
						url,
						gcount,
						imp,
						clk,
						ctr,
						prevpos,
						qahm.roundToX( ( prevpos - lastpos ), 1 ),
						lastpos,
						trend,
						soukanHantei
					] );
				}
				if ( qahm.goalsLpArray[0][pid] !== undefined ) {
					gcountall = qahm.goalsLpArray[0][pid] ;
				}
            }
			//配列に挿入
			qahm.autoKeyAry[0].push( [
				wpQaId,
				key,
				url,
				gcountall,
				imp,
				clk,
				ctr,
				prevpos,
				qahm.roundToX( ( prevpos - lastpos ), 1 ),
				lastpos,
				trend,
				soukanHantei
			] );

			qahm.autoKeyDetailAry.push({
				wp_qa_id:wpQaId,
				keyword:key,
				title:title,
				url:url,
				rankdiff:qahm.roundToX((prevpos - lastpos), 1),
				lastpos:lastpos,
				lastctr:lastctr,
				rankmax:rankmax,
				rankmin:rankmin,
				trend:trend,
				soukan:soukankeisu,
				hantei:soukanHantei,
				posary:posary,
				regary:regressionAry,
				dayary:dayary,
				clkary:clkary
        	});
		}
    }
    if ( 0 < qahm.autoKeyAry[0].length ) {
		qahm.gscAutoKeyTable.rawDataArray = qahm.autoKeyAry[0];

		if ( ! qahm.gscAutoKeyTable.headerTableByID ) {
			qahm.gscAutoKeyTable.generateTable();
		} else {
			qahm.gscAutoKeyTable.updateTable();
		}
		qahm.generateManualTable();
	}
};
qahm.generateManualTable = function( ) {
	qahm.manualKeyAry = [];

    if ( 0 < qahm.autoKeyAry[0].length ) {
		jQuery( '#monitor-keyword-input' ).prop( 'disabled', true );
		let key = jQuery('#monitor-keyword-textarea').val();
		let keyAry = key.split(/\r\n|\r|\n/);
		if ( 0 < keyAry.length ) {
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'text',
					data: {
						'action':  'qahm_ajax_update_seo_monitoring_keyword',
						'nonce' :  qahm.nonce_api,
						'keyword': JSON.stringify( keyAry ),
					}
			}
			).done(
				function() {
					qahm.seo_monitoring_keyword = keyAry;

					if (qahm.manualKeyAry[0] === undefined) {
						qahm.manualKeyAry[0] = new Array();
					}


					for ( let iii = 0; iii < qahm.seo_monitoring_keyword.length; iii++ ) {
						for ( let jjj = 0; jjj < qahm.autoKeyAry[0].length; jjj++ ) {
							if ( qahm.seo_monitoring_keyword[iii] === qahm.autoKeyAry[0][jjj][1] ) {
								qahm.manualKeyAry[0].push( qahm.autoKeyAry[0][jjj] );
								if ( qahm.goalsJson ) {
									for (let gid = 1; gid < qahm.goalsLpArray.length; gid++) {
										if (qahm.manualKeyAry[gid] === undefined) {
											qahm.manualKeyAry[gid] = new Array();
										}
										qahm.manualKeyAry[gid].push( qahm.autoKeyAry[gid][jjj] );
									}
								}
							}
						}
					}
					if ( 0 < qahm.manualKeyAry[0].length ) {
						qahm.gscManualKeyTable.rawDataArray = qahm.manualKeyAry[0];

						if ( ! qahm.gscManualKeyTable.headerTableByID ) {
							qahm.gscManualKeyTable.generateTable();
						} else {
							qahm.gscManualKeyTable.updateTable();
						}
					}
				}

			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				}
			).always(
				function () {
					jQuery( '#monitor-keyword-input' ).prop( 'disabled', false );
					//pbarManualKeyT.innerHTML = '';
        }
			);
		}
	}
};

jQuery( function(){

	// tableの初期化
	qahm.initRewriteTable();
	qahm.initManualKeyTable();
	qahm.initAutoKeyTable();

	let startDate = jQuery("#analysis-date option:selected").data( 'start-date' );
	let endDate   = jQuery("#analysis-date option:selected").data( 'end-date' );
	let dateTerm  = 'date = between ' + startDate + ' and ' + endDate;

	let graphExist = false; //グラフ多重描画の防止用

	//まずは「Loading...」を出す
	let pbarRewriteT = document.getElementById(qahm.gscRewriteTable.progBarDiv);
	//let pbarManualKeyT = document.getElementById(qahm.gscManualKeyTable.progBarDiv);
	let pbarAutoKeyT = document.getElementById(qahm.gscAutoKeyTable.progBarDiv);
	pbarRewriteT.innerHTML = '<span class="el_loading">Loading<span></span></span>';
	//pbarManualKeyT.innerHTML = '<span class="el_loading">Loading<span></span></span>';
	pbarAutoKeyT.innerHTML = '<span class="el_loading">Loading<span></span></span>';


	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action': 'qahm_ajax_get_rewrite_table',
				'nonce' : qahm.nonce_api,
				'start_date' : startDate,
				'end_date' : endDate,
			}
	}
	).done(
		function( resultAry ){
			if ( resultAry ) {
				qahm.gscAry = resultAry;
				// qahm.generateTable( true, true, true );
				//mkdummy
				qahm.generateTable( true, false, false );
			}
		}

	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			pbarRewriteT.innerHTML = '';
		}
	);

	//mkdummy
	jQuery.ajax(
		{
			type: 'POST',
			url: qahm.ajax_url,
			dataType : 'json',
			data: {
				'action': 'qahm_ajax_get_all_keyword_param_table',
				'nonce' : qahm.nonce_api,
				'start_date' : startDate,
				'end_date' : endDate,
			}
	}
	).done(
		function( resultAry ){
			if ( resultAry ) {
				qahm.allGscAry = resultAry;
				qahm.getGoalsAndTable( dateTerm );
			}
		}

	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			pbarAutoKeyT.innerHTML = '';
		}
	);

    let manualradios = document.getElementsByName( `js_manualGoals` );
    for ( let jjj = 0; jjj < manualradios.length; jjj++ ) {
        manualradios[jjj].addEventListener( 'click', qahm.changeManualGoal );
    }
    let autoradios = document.getElementsByName( `js_autoGoals` );
    for ( let jjj = 0; jjj < autoradios.length; jjj++ ) {
        autoradios[jjj].addEventListener( 'click', qahm.changeAutoGoal );
    }

	// 手動キーワードを表に反映
	jQuery( document ).on( 'click',	'#monitor-keyword-input',
		function(){
			jQuery( '#monitor-keyword-input' ).prop( 'disabled', true );
			qahm.generateManualTable();
		}
	);


	// データ分析期間の変更
	//jQuery( document ).off( 'change', '#analysis-date' );
	jQuery( document ).on(
		'change',
		'#analysis-date',
		function(){
			// jQuery( '#seo-monitoring-keyword-detail' ).fadeOut();
			let startDate = jQuery("#analysis-date option:selected").data( 'start-date' );
			let endDate   = jQuery("#analysis-date option:selected").data( 'end-date' );
			let dateTerm  = 'date = between ' + startDate + ' and ' + endDate;

			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action': 'qahm_ajax_get_rewrite_table',
						'nonce' : qahm.nonce_api,
						'start_date' : startDate,
						'end_date' : endDate,
					}
				}
			).done(
				function( resultAry ){
					if ( resultAry ) {
						qahm.gscAry = resultAry;
						//mkdummy
						// qahm.generateTable( true, true, true );
						qahm.generateTable( true, false, false );
					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				}
			);
			//mkdummy
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action': 'qahm_ajax_get_all_keyword_param_table',
						'nonce' : qahm.nonce_api,
						'start_date' : startDate,
						'end_date' : endDate,
					}
			}
			).done(
				function( resultAry ){
					if ( resultAry ) {
						qahm.allGscAry = resultAry;
						qahm.getGoalsAndTable( dateTerm );
					}
				}

			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
				}
			);

		}
	);


	// 検索キーワード詳細情報
	jQuery( document ).on( 'click',	'a[href^="#"]',function() {
		let wpQaId    = jQuery( this ).data( 'wp_qa_id' );
		let keyword   = jQuery( this ).data( 'keyword' );

		let cvPostcountChart;

		// スムーススクロールの計算はここでする
		let adjust   = 0;
		let speed    = 400;
		let href     = jQuery(this).attr("href");
		let target   = jQuery(href == "#" || href == "" ? 'html' : href);
		let position = target.offset().top + adjust;

		for ( let iii = 0; iii < qahm.autoKeyDetailAry.length; iii++ ) {
			if ( qahm.autoKeyDetailAry[iii]['wp_qa_id'] === wpQaId &&  qahm.autoKeyDetailAry[iii]['keyword'] === keyword ) {

				// 表の更新
				let rankingDiff  = document.getElementById('rankingDiff');
				let ranking      = document.getElementById('ranking');
				let ctr          = document.getElementById('ctr');
				let generalCtr   = document.getElementById('generalCtr');
				let rankingMax   = document.getElementById('rankingMax');
				let rankingMin   = document.getElementById('rankingMin');
				let rankingTrend = document.getElementById('rankingTrend');
				let correlation  = document.getElementById('correlation');

				let rankDiffStr      = Number(qahm.autoKeyDetailAry[iii]['rankdiff']);
				if ( rankDiffStr < 0 ) {
					rankDiffStr = qahm.roundToX( rankDiffStr, 1 ).toString() + '&nbsp;<span style="color:blue">↓</span>'
				} else if ( 0 < rankDiffStr ) {
					rankDiffStr = qahm.roundToX( rankDiffStr, 1 ).toString() + '&nbsp;<span style="color:red">↑</span>'
				} else {
					rankDiffStr = qahm.roundToX( rankDiffStr, 1 ).toString();
				}



				let trendstr = '0';
				let trend    = qahm.autoKeyDetailAry[iii]['trend'];
				if ( 0 < trend ) {
					trendstr = trend.toString() + '%&nbsp;<span style="color:red">↑</span>';
				} else {
					trendstr = trend.toString() + '%&nbsp;<span style="color:blue">↓</span>';
				}

				let title   = qahm.autoKeyDetailAry[iii]['title'];
				let url     = qahm.autoKeyDetailAry[iii]['url'];
				let lastctr = qahm.autoKeyDetailAry[iii]['lastctr'];
				let lastpos = qahm.autoKeyDetailAry[iii]['lastpos'];
				let rankmax = qahm.autoKeyDetailAry[iii]['rankmax'];
				let rankmin = qahm.autoKeyDetailAry[iii]['rankmin'];
				let soukan  = qahm.autoKeyDetailAry[iii]['soukan'];
				let posary  = qahm.autoKeyDetailAry[iii]['posary'];
				let clkary  = qahm.autoKeyDetailAry[iii]['clkary'];
				let regary  = qahm.autoKeyDetailAry[iii]['regary'];
				let dayary  = qahm.autoKeyDetailAry[iii]['dayary'];
				let hantei  = qahm.autoKeyDetailAry[iii]['hantei'];

				const ctrResearch   = [0, 8.17, 3.82, 2.43, 1.63, 1.11, 0.84, 0.67, 0.54, 0.52, 0.44];
				let gCtr = '--';
				if ( lastpos <= 10 ) {
					let gCtrRound = qahm.roundToX( lastpos, 0);
					gCtr = ctrResearch[gCtrRound];
				}

				jQuery( '#seo-keyword-detail-keyword strong' ).text( keyword );
				jQuery( '#seo-keyword-detail-keyword a' ).attr( 'href', 'https://www.google.com/search?q=' + keyword );
				jQuery( '#seo-keyword-detail-title' ).text( title );
				jQuery( '#seo-keyword-detail-url' ).text( url );
				jQuery( '#seo-keyword-detail-edit' ).attr( 'href', qahm.site_url + '/wp-admin/post.php?post=' + wpQaId + '&action=edit' );
				jQuery( '#seo-keyword-detail-change-pos' ).html( rankDiffStr );
				jQuery( '#seo-keyword-detail-recent-pos' ).text( lastpos );
				jQuery( '#seo-keyword-detail-general-ctr' ).text( gCtr.toString() + '%' );
				jQuery( '#seo-keyword-detail-recent-ctr' ).text( lastctr );
				jQuery( '#seo-keyword-detail-high-pos' ).text( rankmax );
				jQuery( '#seo-keyword-detail-low-pos' ).text( rankmin );
				jQuery( '#seo-keyword-detail-trend' ).html( trendstr );
				jQuery( '#seo-keyword-detail-trend-reliability' ).html( hantei + '<span style="font-size:12px;">(' + soukan + ')</span>' );

				let dataset = new Array();
				dataset[0] = {
							type: 'line',
							label: qahml10n['keyword_pos_change'],
							lineTension: 0,
							data: posary,
							borderColor: '#313562',
							backgroundColor: '#69A4E2',
							borderJoinStyle: 'bevel',
							borderWidth: 2,
							fill: false,
							pointStyle: 'star',
							spanGaps: false,
							yAxisID: 'ranking'
						};
				dataset[1] = {
							type: 'line',
							label: qahml10n['trend_line'],
							// lineTension: 0,
							data: regary,
							borderColor: '#69a4e2',
							backgroundColor: '#ffffff',
							// backgroundColor: '#69A4E2',
							borderDash: [1,10],
							borderJoinStyle: 'bevel',
							fill: false,
							pointBackgroundColor: "transparent",
							yAxisID: 'ranking'
						};
				dataset[2] = {
							type: 'bar',
							label: qahml10n['click_num'],            
							lineTension: 0,
							data: clkary,
							borderColor: 'rgba(105,164,226, 0.1)',
							backgroundColor: 'rgba(105,164,226, 0.1)',
							//borderWidth: 2,
							fill: false,
							spanGaps: false,
							yAxisID: 'clicks'
						};


				//グラフを書く

				//前のグラフが残っていたらクリアする
				if ( graphExist ) {
					qahm.clearPreChart( cvPostcountChart );
					qahm.resetCanvas('keyRankGraph', 'height="400px"');
					graphExist = false;
				}

				let cvKewRankGraph = document.getElementById('keyRankGraph');
				cvPostcountChart = new Chart(cvKewRankGraph, {
					type: 'line',
					data: {
					labels: dayary,
					datasets: dataset,
					},
					options: {
						legend: {
							labels: {
								fontSize: 9
							},
						},
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							yAxes: [{
								id: 'ranking',
								position: 'left',
								ticks: {
									min: 1,
									suggestedMin: 1,
									userCallback: function(label, index, labels) {
										if (Math.floor(label) === label) {
											return label;
										}
									},
									reverse: true
									},
									scaleLabel: {
										display: true,
										labelString: 'Position', //qahml10n['position_axis'],
										//fontColor: "black",
										//fontSize: 12
									},									
								},{
								id: 'clicks',
								position: 'right',
								ticks: {
									userCallback: function (label, index, labels) {
											if (Math.floor(label) === label) {
													return label;
											}
									}
								},
								scaleLabel: {
									display: true,
									labelString: 'Clicks', //qahml10n['click_axis'],
									//fontColor: "black",
									//fontSize: 12
								},
							}]
						}
					},
				});
				cvKewRankGraph.style.cssText = 'height: 400px !important;width: 100% !important;';

				graphExist = true;

				// スクロールしてフェードイン
				jQuery('body,html').animate({scrollTop:position}, speed, 'swing');
				jQuery( '#seo-monitoring-keyword-detail' ).fadeIn();







				break;
			}

		}


    });

} );
//all table making
qahm.getGoalsAndTable = function(datePickerTerm) {

    if (qahm.goalsJson) {
        qahm.goalsArray = JSON.parse(qahm.goalsJson);
    }
    //new /repeat device table
    qahm.goalsSessionData = new Array();
    jQuery.ajax(
        {
            type: 'POST',
            url: qahm.ajax_url,
            dataType: 'json',
            data: {
                'action': 'qahm_ajax_get_goals_sessions',
                'date': datePickerTerm,
                'nonce': qahm.nonce_api
            }
        }
    ).done(
        function (data) {
			if (data !== null ) {
				let ary = new Array();
				for (let gid = 1; gid <= Object.keys(data).length; gid++) {
					ary = ary.concat(data[gid]);
				}
				for (let gid = 0; gid <= Object.keys(data).length; gid++) {
					if (gid === 0) {
						qahm.goalsSessionData[0] = ary;
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
        function (jqXHR, textStatus, errorThrown) {
            qahm.log_ajax_error(jqXHR, textStatus, errorThrown);
            if (qahm.goalsJson) {
                for (let gid = 0; gid <= Object.keys(qahm.goalsArray).length; gid++) {
                    qahm.goalsSessionData[gid] = new Array();
                }
            }
        }
    ).always(
        function () {
            if (qahm.goalsJson) {
                let pidary = new Array();
                let allgoals = 0;
                for (let gid = 1; gid <= Object.keys(qahm.goalsArray).length; gid++) {
                    pidary = pidary.concat(qahm.goalsArray[gid]['pageid_ary']);
                    allgoals += Number(qahm.goalsArray[gid]['gnum_scale']);
                }
                qahm.goalsArray[0] = {
                    'pageid_ary': pidary,
                    'gtitle': qahml10n['cnv_all_goals'],
                    'gnum_scale': allgoals
                };
                qahm.goalsLpArray = new Array();
                for (let gid = 0; gid < Object.keys(qahm.goalsArray).length; gid++) {

                    //make nrd array
                    let lp_ary = new Array();
                    for (let sno = 0; sno < qahm.goalsSessionData[gid].length; sno++) {
                        let lp = qahm.goalsSessionData[gid][sno][0];

                        //lp
                        if (lp_ary[lp['page_id']] !== undefined) {
                            lp_ary[lp['page_id']]++;
                        } else {
                            lp_ary[lp['page_id']] = 1;
                        }
                    }
                    qahm.goalsLpArray[gid] = lp_ary;
                }
            }
            qahm.generateAutoTable();
        }
    );
};

qahm.changeManualGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeTable( qahm.gscManualKeyTable, qahm.manualKeyAry[gid] );
};
qahm.changeAutoGoal = function(e) {
    let checkedId = e.target.id;
    let idsplit   = checkedId.split('_');
    let gid       = Number( idsplit[2] );
    qahm.makeTable( qahm.gscAutoKeyTable, qahm.autoKeyAry[gid] );
};
qahm.makeTable  = function(table, ary) {
    table.rawDataArray = ary;
    if ( ! table.headerTableByID ) {
        table.generateTable();
    } else {
        table.updateTable();
    }
};

// キーワード詳細情報の作成
qahm.gscGenerateKeywordDetail = function( labelsAry, rankingAry ) {

}


/**-------------------------------
 * to clear the chart グラフのクリア
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