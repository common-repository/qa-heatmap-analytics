var qahm = qahm || {};

if ( qahm.pvDataAry ) {
qahm.pvDataAry      = JSON.parse( qahm.pvDataAry );
}
if ( qahm.sessionDataAry ) {
qahm.sessionDataAry = JSON.parse( qahm.sessionDataAry );
}
qahm.devices            = JSON.parse( qahm.devices );

// for データを探す
jQuery( function(){
	// table.js 初期化
	qahm.createTotalMediaTable();
	qahm.createSourceDomainTable();

	// heatmap
	qahm.createTotalMediaList( qahm.startDate, qahm.endDate );
	qahm.createSourceDomainList( qahm.startDate, qahm.endDate );
});


/**
 *  テーブルの初期化
 */
qahm.createTotalMediaTable = function() {
	qahm.totalMediaTable = new QATableGenerator();
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// total_media_index
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// page_id
	qahm.totalMediaTable.dataObj.body.header.push({title:'メディア',type: 'string',colParm:'style="width: 11%;"'});
	qahm.totalMediaTable.dataObj.body.header.push({title:'総データ数',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span data-device_name="all" data-total_media_index="%!00">%!me</span>'});
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// class_dsk
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// version_id_dsk
	qahm.totalMediaTable.dataObj.body.header.push({title:'<i class="fas fa-desktop"></i> pc',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!04" data-device_name="dsk" data-total_media_index="%!00" data-version_id="%!05" data-utm_medium="%!02">%!me</span>'});
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// class_tab
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// version_id_tab
	qahm.totalMediaTable.dataObj.body.header.push({title:'<i class="fas fa-tablet-alt"></i> tab',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!07" data-device_name="tab" data-total_media_index="%!00" data-version_id="%!08" data-utm_medium="%!02">%!me</span>'});
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// class_smp
	qahm.totalMediaTable.dataObj.body.header.push({isHide:true});	// version_id_smp
	qahm.totalMediaTable.dataObj.body.header.push({title:'<i class="fas fa-mobile-alt"></i> smp',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!10" data-device_name="smp" data-total_media_index="%!00" data-version_id="%!11" data-utm_medium="%!02">%!me</span>'});
	qahm.totalMediaTable.dataObj.body.header.push({title:"ページバージョン：データ期間",type: 'string',colParm:'style="width: 20%;"',tdParm:'style="text-align:center;"',hasFilter:false});
	qahm.totalMediaTable.visibleSort.index = 3;
	qahm.totalMediaTable.visibleSort.order = 'dsc';
	qahm.totalMediaTable.targetID = 'total-media-table';
	qahm.totalMediaTable.progBarDiv = 'total-media-table-progbar';
	qahm.totalMediaTable.dataTRNowBlocks.divheight = 400;
	qahm.totalMediaTable.prefix = 'qatotalmedia';
	qahm.totalMediaTable.workerjs = qahm.plugin_dir_url.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
}

qahm.createSourceDomainTable = function() {
	qahm.srcDomainTable = new QATableGenerator();
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// source_domain_index
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// page_id
	qahm.srcDomainTable.dataObj.body.header.push({title:'参照元',type: 'string',colParm:'style="width: 11%;"'});
	qahm.srcDomainTable.dataObj.body.header.push({title:'メディア',type: 'string',colParm:'style="width: 11%;"'});
	qahm.srcDomainTable.dataObj.body.header.push({title:'総データ数',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span data-device_name="all" data-source_domain_index="%!00">%!me</span>'});
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// class_dsk
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// version_id_dsk
	qahm.srcDomainTable.dataObj.body.header.push({title:'<i class="fas fa-desktop"></i> pc',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!05" data-device_name="dsk" data-source_domain_index="%!00" data-version_id="%!06" data-source_domain="%!02" data-utm_medium="%!03">%!me</span>'});
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// class_tab
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// version_id_tab
	qahm.srcDomainTable.dataObj.body.header.push({title:'<i class="fas fa-tablet-alt"></i> tab',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!08" data-device_name="tab" data-source_domain_index="%!00" data-version_id="%!09" data-source_domain="%!02" data-utm_medium="%!03">%!me</span>'});
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// class_smp
	qahm.srcDomainTable.dataObj.body.header.push({isHide:true});	// version_id_smp
	qahm.srcDomainTable.dataObj.body.header.push({title:'<i class="fas fa-mobile-alt"></i> smp',type: 'number',colParm:'style="width: 8%;"',tdParm:'style="text-align:right;"',tdHtml:'<span class="%!11" data-device_name="smp" data-source_domain_index="%!00" data-version_id="%!12" data-source_domain="%!02" data-utm_medium="%!03">%!me</span>'});
	qahm.srcDomainTable.dataObj.body.header.push({title:"ページバージョン：データ期間",type: 'string',colParm:'style="width: 20%;"',tdParm:'style="text-align:center;"',hasFilter:false});
	qahm.srcDomainTable.visibleSort.index = 3;
	qahm.srcDomainTable.visibleSort.order = 'dsc';
	qahm.srcDomainTable.targetID = 'source-domain-table';
	qahm.srcDomainTable.progBarDiv = 'source-domain-table-progbar';
	qahm.srcDomainTable.dataTRNowBlocks.divheight = 400;
	qahm.srcDomainTable.prefix = 'qasourcedomain';
	qahm.srcDomainTable.workerjs = qahm.plugin_dir_url.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
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
    let freeMessage = {title:'全ページ記録するには？', text:'<a href=&quot;https://quarka.org/plan/; target=_blank>有料プラン</a>を契約すると自動で全ページ記録されます。'};

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

						//再生ボタンの判断。お友達と無料ユーザーはraw_eがない箇所については有料プラン案内。それ以外はraw_eがあるけどDB範囲外はグレー。
						if (replayTdHtml === '') {
							if ( ! qahm.license_plans['all_hm'] ) {
								replayTdHtml = `<span class="icon-replay-disable" onclick="AlertMessage.alert('${freeMessage.title}', '${freeMessage.text}', 'info', null);"><i class="fa fa-play-circle fa-2x"></i></span>`;
							}
						}

                    //make array
                    let sessionAry = [device, last_exit_time, reader_id, firstUrl, firstTitle, firstTitleEl, lastUrl, lastTitle, lastTitleEl, utm_source, source_domain, pvcnt, sec_on_site, replayTdHtml];
                    allSessionAry.push(sessionAry);
                }
            }
        }
	}
	return allSessionAry;
}



/** 
 *  ヒートマップ一覧の構築
 */
 qahm.createTotalMediaList = function( startDate, endDate ) {
	qahm.tmList = {};
	let dataAry  = null;

	// ここでいい感じに整形できるならしたい
	if ( qahm.pvDataAry ) {
		dataAry = qahm.pvDataAry;
	} else {
		dataAry = qahm.sessionDataAry;
	}

	for ( let i = 0, paramLen = dataAry.length; i < paramLen; i++ ) {
		for ( let j = 0, testLen = dataAry[i].length; j < testLen; j++ ) {
			let param = dataAry[i][j];
			if ( param.page_id !== qahm.pageId ) {
				continue;
			}

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

			// 一時変数定義
			let pageId    = param.page_id;
			let verNo     = param.version_no;
			let utmMedium = '';
			if ( param.utm_medium !== undefined ) {
				utmMedium = param.utm_medium;
				if ( utmMedium === 'organic' ) {
					utmMedium = qahml10n.medium_organic;
				}
			}

			/* 連想配列に直接代入（オブジェクトパターン） */
			// 存在チェック
			let existHMVer = false;
			if( qahm.tmList[pageId] && qahm.tmList[pageId][utmMedium] && qahm.tmList[pageId][utmMedium].verIdx && verNo ) {
				if ( qahm.tmList[pageId][utmMedium].verInfo[verNo] ) {
					qahm.tmList[pageId][utmMedium].verInfo[verNo][param.device_id].dataNum++;
					qahm.tmList[pageId][utmMedium].verInfo[verNo][param.device_id].verId = param.version_id;

					let accessTime = new Date( param.access_time );
					if ( qahm.tmList[pageId][utmMedium].verInfo[verNo].startDate > accessTime ) {
						qahm.tmList[pageId][utmMedium].verInfo[verNo].startDate = accessTime;
					} else if ( qahm.tmList[pageId][utmMedium].verInfo[verNo].endDate < accessTime ) {
						qahm.tmList[pageId][utmMedium].verInfo[verNo].endDate = accessTime;
					}
					existHMVer = true;
				}

				if ( qahm.tmList[pageId][utmMedium].verIdx < verNo ) {
					qahm.tmList[pageId][utmMedium].verIdx = verNo;
				}
			}

			if ( ! existHMVer ) {
				// アクセス速度のことを考慮して連想配列にはpage_idを入れている
				if ( ! qahm.tmList[pageId] ) {
					qahm.tmList[pageId] = {};
				}
				if ( ! qahm.tmList[pageId][utmMedium] ) {
					qahm.tmList[pageId][utmMedium] = {};
					qahm.tmList[pageId][utmMedium].url     = param.url;
					qahm.tmList[pageId][utmMedium].title   = param.title;
					qahm.tmList[pageId][utmMedium].verIdx  = verNo;
					qahm.tmList[pageId][utmMedium].verInfo = {};
				}

				if ( verNo ) {
					qahm.tmList[pageId][utmMedium].verInfo[verNo] = {};
					for ( const devType in qahm.devices ) {
						qahm.tmList[pageId][utmMedium].verInfo[verNo][qahm.devices[devType]['id']] = {};
						qahm.tmList[pageId][utmMedium].verInfo[verNo][qahm.devices[devType]['id']].dataNum = 0;
						qahm.tmList[pageId][utmMedium].verInfo[verNo][qahm.devices[devType]['id']].verId = null;
					}
					qahm.tmList[pageId][utmMedium].verInfo[verNo][param.device_id].dataNum++;
					qahm.tmList[pageId][utmMedium].verInfo[verNo][param.device_id].verId     = param.version_id;
					let accessTime = new Date( param.access_time );
					qahm.tmList[pageId][utmMedium].verInfo[verNo].startDate = accessTime;
					qahm.tmList[pageId][utmMedium].verInfo[verNo].endDate   = accessTime;
				} else {
					if ( ! qahm.tmList[pageId][utmMedium].verIdx ) {
						qahm.tmList[pageId][utmMedium].verIdx = null;
					}
				}
			}
		}
	}
	
	
	qahm.tmList = Object.entries( qahm.tmList );

	let allHeatmapAry = [];
	for ( let hmIdx = 0, tmIdx = 0, hmLen = qahm.tmList.length; hmIdx < hmLen; hmIdx++ ) {
		let pageId  = qahm.tmList[hmIdx][0];
		let hmAry   = qahm.tmList[hmIdx][1];

		Object.keys(hmAry).forEach(function (utmMedium) {
			// データ数
			let verInfo    = null;
			let classDsk   = 'qahm-heatmap-text';
			let classTab   = 'qahm-heatmap-text';
			let classSmp   = 'qahm-heatmap-text';
			let verIdDsk   = -1;
			let verIdTab   = -1;
			let verIdSmp   = -1;
			let dataDsk    = 0;
			let dataTab    = 0;
			let dataSmp    = 0;
			let dataPeriod = '';

			if ( hmAry[utmMedium].verIdx ) {
				verInfo  = hmAry[utmMedium].verInfo[hmAry[utmMedium].verIdx];
				dataDsk  = parseInt( verInfo[1]['dataNum'] );
				if ( dataDsk > 0 ) {
					classDsk = 'qahm-heatmap-link';
				}
				dataTab  = parseInt( verInfo[2]['dataNum'] );
				if ( dataTab > 0 ) {
					classTab = 'qahm-heatmap-link';
				}
				dataSmp  = parseInt( verInfo[3]['dataNum'] );
				if ( dataSmp > 0 ) {
					classSmp = 'qahm-heatmap-link';
				}
				verIdDsk = verInfo[1]['verId'];
				verIdTab = verInfo[2]['verId'];
				verIdSmp = verInfo[3]['verId'];

				// バージョン：データ期間
				dataPeriod += '<select class="qahm-version-select" data-total_media_index="' + hmIdx + '" data-list_type="tm" data-utm_medium="' + utmMedium + '">';
				for ( let i = hmAry[utmMedium].verIdx ; i > 0 ; i-- ) {
					let info = hmAry[utmMedium].verInfo[i];
					if ( info === undefined ) {
						continue;
					}
					dataPeriod += '<option value="' + i + '">';
					dataPeriod += 'ver.' + i + ' : ' + getDataPeriod( info['startDate'] ) + ' - ' + getDataPeriod( info['endDate'] );
					dataPeriod += '</option>';
				}
				dataPeriod += '</select>';
			}

			allHeatmapAry.push( [
				tmIdx,
				pageId,
				utmMedium,
				dataDsk + dataTab + dataSmp,
				classDsk,
				verIdDsk,
				dataDsk,
				classTab,
				verIdTab,
				dataTab,
				classSmp,
				verIdSmp,
				dataSmp,
				dataPeriod
			] );

			tmIdx++;
		});
	}

	if (typeof qahm.totalMediaTable !== 'undefined' && qahm.totalMediaTable !== '') {
		qahm.totalMediaTable.rawDataArray = allHeatmapAry;
		if (qahm.totalMediaTable.visibleArray.length === 0) {
			qahm.totalMediaTable.generateTable();
		} else {
			qahm.totalMediaTable.updateTable();
		}
	}
	

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


/** 
 *  ヒートマップ一覧の構築
 */
qahm.createSourceDomainList = function( startDate, endDate ) {
	qahm.sdList = {};
	let dataAry  = null;

	// ここでいい感じに整形できるならしたい
	if ( qahm.pvDataAry ) {
		dataAry = qahm.pvDataAry;
	} else {
		dataAry = qahm.sessionDataAry;
	}

	for ( let i = 0, paramLen = dataAry.length; i < paramLen; i++ ) {
		for ( let j = 0, testLen = dataAry[i].length; j < testLen; j++ ) {
			let param = dataAry[i][j];
			if ( param.page_id !== qahm.pageId ) {
				continue;
			}

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

			// 一時変数定義
			let pageId    = param.page_id;
			let verNo     = param.version_no;
			let srcDomain = param.source_domain;
			let utmMedium = '';
			if ( param.utm_medium !== undefined ) {
				utmMedium = param.utm_medium;
				if ( utmMedium === 'organic' ) {
					utmMedium = qahml10n.medium_organic;
				}
			}

			/* 連想配列に直接代入（オブジェクトパターン） */
			// 存在チェック
			let existHMVer = false;
			if( qahm.sdList[pageId] && qahm.sdList[pageId][srcDomain] && qahm.sdList[pageId][srcDomain][utmMedium] && qahm.sdList[pageId][srcDomain][utmMedium].verIdx && verNo ) {
				if ( qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo] ) {
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][param.device_id].dataNum++;
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][param.device_id].verId = param.version_id;

					let accessTime = new Date( param.access_time );
					if ( qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].startDate > accessTime ) {
						qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].startDate = accessTime;
					} else if ( qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].endDate < accessTime ) {
						qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].endDate = accessTime;
					}
					existHMVer = true;
				}

				if ( qahm.sdList[pageId][srcDomain][utmMedium].verIdx < verNo ) {
					qahm.sdList[pageId][srcDomain][utmMedium].verIdx = verNo;
				}
			}

			if ( ! existHMVer ) {
				// アクセス速度のことを考慮して連想配列にはpage_idを入れている
				if ( ! qahm.sdList[pageId] ) {
					qahm.sdList[pageId] = {};
				}
				if ( ! qahm.sdList[pageId][srcDomain] ) {
					qahm.sdList[pageId][srcDomain] = {};
				}
				if ( ! qahm.sdList[pageId][srcDomain][utmMedium] ) {
					qahm.sdList[pageId][srcDomain][utmMedium] = {};
					qahm.sdList[pageId][srcDomain][utmMedium].url     = param.url;
					qahm.sdList[pageId][srcDomain][utmMedium].title   = param.title;
					qahm.sdList[pageId][srcDomain][utmMedium].verIdx  = verNo;
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo = {};
				}

				if ( verNo ) {
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo] = {};
					for ( const devType in qahm.devices ) {
						qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][qahm.devices[devType]['id']] = {};
						qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][qahm.devices[devType]['id']].dataNum = 0;
						qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][qahm.devices[devType]['id']].verId = null;
					}
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][param.device_id].dataNum++;
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo][param.device_id].verId     = param.version_id;
					let accessTime = new Date( param.access_time );
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].startDate = accessTime;
					qahm.sdList[pageId][srcDomain][utmMedium].verInfo[verNo].endDate   = accessTime;
				} else {
					if ( ! qahm.sdList[pageId][srcDomain][utmMedium].verIdx ) {
						qahm.sdList[pageId][srcDomain][utmMedium].verIdx = null;
					}
				}
			}
		}
	}
	
	
	qahm.sdList = Object.entries( qahm.sdList );

	let allHeatmapAry = [];
	for ( let hmIdx = 0, sdIdx = 0, hmLen = qahm.sdList.length; hmIdx < hmLen; hmIdx++ ) {
		let pageId  = qahm.sdList[hmIdx][0];
		let hmAry   = qahm.sdList[hmIdx][1];

		Object.keys(hmAry).forEach(function (srcDomain) {
			Object.keys(hmAry[srcDomain]).forEach(function (utmMedium) {
				// データ数
				let verInfo    = null;
				let classDsk   = 'qahm-heatmap-text';
				let classTab   = 'qahm-heatmap-text';
				let classSmp   = 'qahm-heatmap-text';
				let verIdDsk   = -1;
				let verIdTab   = -1;
				let verIdSmp   = -1;
				let dataDsk    = 0;
				let dataTab    = 0;
				let dataSmp    = 0;
				let dataPeriod = '';

				if ( hmAry[srcDomain][utmMedium].verIdx ) {
					verInfo  = hmAry[srcDomain][utmMedium].verInfo[hmAry[srcDomain][utmMedium].verIdx];
					dataDsk  = parseInt( verInfo[1]['dataNum'] );
					if ( dataDsk > 0 ) {
						classDsk = 'qahm-heatmap-link';
					}
					dataTab  = parseInt( verInfo[2]['dataNum'] );
					if ( dataTab > 0 ) {
						classTab = 'qahm-heatmap-link';
					}
					dataSmp  = parseInt( verInfo[3]['dataNum'] );
					if ( dataSmp > 0 ) {
						classSmp = 'qahm-heatmap-link';
					}
					verIdDsk = verInfo[1]['verId'];
					verIdTab = verInfo[2]['verId'];
					verIdSmp = verInfo[3]['verId'];

					// バージョン：データ期間
					dataPeriod += '<select class="qahm-version-select" data-source_domain_index="' + hmIdx + '" data-list_type="sd" data-source_domain="' + srcDomain + '" data-utm_medium="' + utmMedium + '">';
					for ( let i = hmAry[srcDomain][utmMedium].verIdx ; i > 0 ; i-- ) {
						let info = hmAry[srcDomain][utmMedium].verInfo[i];
						if ( info === undefined ) {
							continue;
						}
						dataPeriod += '<option value="' + i + '">';
						dataPeriod += 'ver.' + i + ' : ' + getDataPeriod( info['startDate'] ) + ' - ' + getDataPeriod( info['endDate'] );
						dataPeriod += '</option>';
					}
					dataPeriod += '</select>';
				}

				// 参照元にはnullという文字列が挿入されているときがあるので、その場合は空白化
				let tempSrcDomain = '';
				if( srcDomain !== 'null' ) {
					tempSrcDomain = srcDomain;
				}

				allHeatmapAry.push( [
					sdIdx,
					pageId,
					tempSrcDomain,
					utmMedium,
					dataDsk + dataTab + dataSmp,
					classDsk,
					verIdDsk,
					dataDsk,
					classTab,
					verIdTab,
					dataTab,
					classSmp,
					verIdSmp,
					dataSmp,
					dataPeriod
				] );

				sdIdx++;
			});
		});
	}

	if (typeof qahm.srcDomainTable !== 'undefined' && qahm.srcDomainTable !== '') {
		qahm.srcDomainTable.rawDataArray = allHeatmapAry;
		if (qahm.srcDomainTable.visibleArray.length === 0) {
			qahm.srcDomainTable.generateTable();
		} else {
			qahm.srcDomainTable.updateTable();
		}
	}
	

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

	
// セレクトボックスでバージョン閲覧切り替え
jQuery( document ).off( 'change', '.qahm-version-select' );
jQuery( document ).on(
	'change',
	'.qahm-version-select',
	function(){
		let sdIdx      = jQuery(this).data( 'source_domain_index' );
		let tmIdx      = jQuery(this).data( 'total_media_index' );
		let listType   = jQuery(this).data( 'list_type' );
		let srcDomain  = jQuery(this).data( 'source_domain' );
		let utmMedium  = jQuery(this).data( 'utm_medium' );
		let verIdx     = jQuery(this).val();
		let verInfo    = null;

		if ( listType === 'sd' ) {
			verInfo = qahm.sdList[sdIdx][1][srcDomain][utmMedium]['verInfo'][verIdx];
			qahm.sdList[sdIdx][1][srcDomain][utmMedium]['verIdx'] = verIdx;
		} else if ( listType === 'tm' ) {
			verInfo = qahm.tmList[tmIdx][1][utmMedium]['verInfo'][verIdx];
			qahm.tmList[tmIdx][1][utmMedium]['verIdx'] = verIdx;
		} else {
			alert( 'データ期間切り替えに失敗しました。' );
			return;
		}

		let dataDsk  = verInfo[1]['dataNum'];
		let dataTab  = verInfo[2]['dataNum'];
		let dataSmp  = verInfo[3]['dataNum'];
		let verIdDsk = verInfo[1]['verId'];
		let verIdTab = verInfo[2]['verId'];
		let verIdSmp = verInfo[3]['verId'];
		let classDsk = 'qahm-heatmap-text';
		if ( dataDsk > 0 ) {
			classDsk = 'qahm-heatmap-link';
		}
		let classTab = 'qahm-heatmap-text';
		if ( dataTab > 0 ) {
			classTab = 'qahm-heatmap-link';
		}
		let classSmp = 'qahm-heatmap-text';
		if ( dataSmp > 0 ) {
			classSmp = 'qahm-heatmap-link';
		}

		// html書き換え。こうしないと駄目な点のメモとして、dataとattrを同時に書き換えることにより、DOMとjqueryのキャッシュの両方を書き換える必要あり
		if ( listType === 'sd' ) {
			jQuery( '[data-device_name="all"][data-source_domain_index="' + sdIdx + '"]' ).text( dataDsk + dataTab + dataSmp );
			jQuery( '[data-device_name="dsk"][data-source_domain_index="' + sdIdx + '"]' ).text( dataDsk ).data( 'version_id', verIdDsk ).attr( 'data-version_id', verIdDsk ).removeClass().addClass( classDsk );
			jQuery( '[data-device_name="tab"][data-source_domain_index="' + sdIdx + '"]' ).text( dataTab ).data( 'version_id', verIdTab ).attr( 'data-version_id', verIdTab ).removeClass().addClass( classTab );
			jQuery( '[data-device_name="smp"][data-source_domain_index="' + sdIdx + '"]' ).text( dataSmp ).data( 'version_id', verIdSmp ).attr( 'data-version_id', verIdSmp ).removeClass().addClass( classSmp );
		} else if ( listType === 'tm' ) {
			jQuery( '[data-device_name="all"][data-total_media_index="' + tmIdx + '"]' ).text( dataDsk + dataTab + dataSmp );
			jQuery( '[data-device_name="dsk"][data-total_media_index="' + tmIdx + '"]' ).text( dataDsk ).data( 'version_id', verIdDsk ).attr( 'data-version_id', verIdDsk ).removeClass().addClass( classDsk );
			jQuery( '[data-device_name="tab"][data-total_media_index="' + tmIdx + '"]' ).text( dataTab ).data( 'version_id', verIdTab ).attr( 'data-version_id', verIdTab ).removeClass().addClass( classTab );
			jQuery( '[data-device_name="smp"][data-total_media_index="' + tmIdx + '"]' ).text( dataSmp ).data( 'version_id', verIdSmp ).attr( 'data-version_id', verIdSmp ).removeClass().addClass( classSmp );
		} else {
			alert( 'データ期間切り替えに失敗しました。' );
			return;
		}
	}
);


// デバイスリンククリック
jQuery( document ).off( 'click', '.qahm-heatmap-link' );
jQuery( document ).on(
	'click',
	'.qahm-heatmap-link',
	function(){
		// date型からphp用のフォーマットを作成
		//let startDateStr = qahm.startDate.getFullYear() + '-' + ( qahm.startDate.getMonth() + 1 ) + '-' + qahm.startDate.getDate() + ' ' + qahm.startDate.getHours() + ':' + qahm.startDate.getMinutes() + ':' + qahm.startDate.getSeconds();
		//let endDateStr   = qahm.endDate.getFullYear() + '-' + ( qahm.endDate.getMonth() + 1 ) + '-' + qahm.endDate.getDate() + ' ' + qahm.endDate.getHours() + ':' + qahm.endDate.getMinutes() + ':' + qahm.endDate.getSeconds();

		let verId     = jQuery( this ).data( 'version_id' );
		let devName   = jQuery( this ).data( 'device_name' );
		let srcDomain = jQuery( this ).data( 'source_domain' );
		let utmMedium = jQuery( this ).data( 'utm_medium' );
		let viewPvAry = [];
		if ( qahm.pvDataAry ) {
			for ( let dayIdx = 0, dayLen = qahm.pvDataAry.length; dayIdx < dayLen; dayIdx++ ) {
				for ( let pvIdx = 0, pvLen = qahm.pvDataAry[dayIdx].length; pvIdx < pvLen; pvIdx++ ) {
					let pvData = qahm.pvDataAry[dayIdx][pvIdx];
					if( pvData.version_id == verId ) {
						if( srcDomain === undefined || ( srcDomain && pvData.source_domain == srcDomain ) ) {
							if( pvData.utm_medium ) {
								if( pvData.utm_medium == utmMedium ) {
									viewPvAry.push( pvData );
								}
							} else if ( utmMedium == '' ) {
								viewPvAry.push( pvData );
							}
						}
					}
				}
			}
		} else if ( qahm.sessionDataAry ) {
			for ( let sesIdx = 0, sesLen = qahm.sessionDataAry.length; sesIdx < sesLen; sesIdx++ ) {
				for ( let pvIdx = 0, pvLen = qahm.sessionDataAry[sesIdx].length; pvIdx < pvLen; pvIdx++ ) {
					let pvData = qahm.sessionDataAry[sesIdx][pvIdx];
					if( pvData.version_id == verId ) {
						if ( srcDomain === undefined || ( srcDomain && pvData.source_domain == srcDomain ) ) {
							if( pvData.utm_medium ) {
								if( pvData.utm_medium == utmMedium ) {
									viewPvAry.push( pvData );
								}
							} else if ( utmMedium == '' ) {
								viewPvAry.push( pvData );
							}
						}
					}
				}
			}
		}

		qahm.createCapToViewPv( verId, devName, viewPvAry, true );
	}
);
