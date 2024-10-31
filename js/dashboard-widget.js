var qahm = qahm || {};


/**
 * 確認用------------
 */
let dateCk = false;
let visitsCk = false;
let channelCk = false;
//-------------------

moment.locale( qahm.wp_lang_set );



/** -------------------------------------
 * widget "Realtime"
 */

 jQuery(
	function(){
		qahm.updateSessionNum();
    qahm.updateRealtimeListAndGraph( false ); //updating = false
    //keep updating
		setInterval( qahm.updateSessionNum, 1000 * 10 );
    setInterval( qahm.updateRealtimeListAndGraph, ( 1000 * 60 * 5 ), true ); //updating = true
	}
);


qahm.updateRealtimeListAndGraphCnt = 0;
qahm.updateSessionNumCnt   = 0;

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
               jQuery('#realtime-users-thirtymin').text(data['session_num']);
               jQuery('#realtime-users-permin').text(data['session_num_1min']);
            }
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			jQuery( '#realtime-users-thirtymin' ).text( '-' );
			jQuery( '#realtime-users-permin' ).text( '-' );
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	).always(
		function(){
			qahm.updateSessionNumCnt--;
		}
	);
} //end function updateSessionNum


qahm.updateRealtimeListAndGraph = function( updating = false ) {
	if ( qahm.updateRealtimeListAndGraphCnt > 0 ) {
		return;
	}
	qahm.updateRealtimeListAndGraphCnt++;

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
        qahm.drawRealtimeHourlyChart( [], updating );
        qahm.msgWhenNoData( 'realtime-list-container' );
				return;
			}      
      if ( data['realtime_list'].length > 0 ) {        
        qahm.makeRealtimeSessionList( data['realtime_list'] );
        qahm.drawRealtimeHourlyChart( data['realtime_list'], updating );       
      }
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
      qahm.msgIfAjaxFailed( 'chart-container-hourly' );
		}
	).always(
		function(){
			qahm.updateRealtimeListAndGraphCnt--;
		}
	);
} //end function updateRealtimeListAndGraph


//realtime session list
qahm.makeRealtimeSessionList = function( widgetRealtimeList ) {
  let widgetRealtimeCells = [];
  let realtimeListContainer = document.getElementById('realtime-list-container');
  
  //clear previous list when updating
  if ( realtimeListContainer.hasChildNodes() ) {
    while (realtimeListContainer.firstChild) {
      realtimeListContainer.removeChild(realtimeListContainer.firstChild);
    }
  }

  //list 30 sessions max
  let listMaxLength = 30;
  if ( widgetRealtimeList.length < listMaxLength ) {
    listMaxLength = widgetRealtimeList.length;
  }
  
  for ( let iii = 0; iii < widgetRealtimeList.length; iii++ ) {

    if ( listMaxLength > 0 ) {
      //skip data without exit page title
      if ( widgetRealtimeList[iii][7] === '' ) {
        continue;
      }
      
      listMaxLength--;

      let deviceCat= '';            
      switch ( widgetRealtimeList[iii][0] ) {
        case 'pc':
          deviceCat = 'desktop';
          break;
        case 'tab':
          deviceCat = 'tablet';
          break;
        case 'smp':
          deviceCat = 'smartphone';
          break;
      }
      
      let sessionDate = moment.unix( Number( widgetRealtimeList[iii][1] ) );
      let leftTime = sessionDate.format('YYYY-MM-DD HH:mm:ss');

      let totalSec = Number( widgetRealtimeList[iii][11] );
      let sec = totalSec % 60;
      let remainMin = (totalSec - sec ) / 60;
      let min = remainMin % 60;
      let hour = ( remainMin - min ) / 60;
      const timestr = (time) => { return ( '0' + time.toString() ).slice(-2) };
      let timeOnSite = timestr(hour) + ':' + timestr(min) + ':' + timestr(sec);           

      let widgetRealtimeCell = '';
      widgetRealtimeCell += '<div class="list_session_cell">';
      widgetRealtimeCell += '<div class="list_row_info">';
      widgetRealtimeCell += '<div class="list_device"><span class="dashicons dashicons-' + deviceCat + '"></span></div>';
      widgetRealtimeCell += '<div class="list_pvs"><span class="list_row_info_icon"><i class="fas fa-file-alt"></i></span>' + widgetRealtimeList[iii][10] + 'pv</div>';
      widgetRealtimeCell += '<div class="list_timeonsite"><span class="list_row_info_icon"><i class="fas fa-stopwatch"></i></span>' + timeOnSite + '</div>';
      widgetRealtimeCell += '<div class="list_lefttime"><span class="list_row_info_icon"><i class="fas fa-calendar-minus"></i></span>'+ leftTime + '</div>';
      widgetRealtimeCell += '<div class="list_referral"><span class="list_row_info_icon"><i class="fas fa-chalkboard-teacher"></i></span>' + widgetRealtimeList[iii][9] + '</div>';
      widgetRealtimeCell += '</div>';
      widgetRealtimeCell += '<div class="list_column_page">';
      widgetRealtimeCell += '<div class="list_title">[LP] ' + widgetRealtimeList[iii][4] + '</div>';
      widgetRealtimeCell += '<div class="list_title">[' + qahml10n['page_exit'] + '] ' + widgetRealtimeList[iii][7] + '</div>';
      widgetRealtimeCell += '</div>';
      widgetRealtimeCell += '</div>';

      widgetRealtimeCells.push( widgetRealtimeCell );
    } else {
      break;
    }      
  }

  realtimeListContainer.insertAdjacentHTML( 'afterbegin', widgetRealtimeCells.join('') );

} //end function makeRealtimeSessionList


//realtime hourly chart
let widgetChartHourly;
qahm.drawRealtimeHourlyChart = function( widgetRealtimeList, updating ) {  
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
  if ( widgetRealtimeList.length !== 0 ) {
    for ( let iii = 0; iii < widgetRealtimeList.length; iii++ ) {
      for ( let hhh = nowHour; 0 <= hhh; hhh-- ) {
        if ( zerojiUnixtime + hhh * 3600 < Number( widgetRealtimeList[iii][1] ) ) {
          ++eachHourPvsAry[hhh];
          break;
        }
      }
    }
  }

  //clear pre-chart when updating
  if ( updating ) {
    qahm.clearPreChart( widgetChartHourly );
    qahm.resetCanvas( 'widgetChartHourly' );
  }

  let ctxhourly = document.getElementById('widgetChartHourly').getContext('2d');
  widgetChartHourly = new Chart(ctxhourly, {
    type: 'bar',
    data: {
    labels: eachHourLabel.reverse(),
    datasets: [{
      label: qahml10n['graph_hourly_sessions'],
      fill: false,
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
        text: qahml10n['Sessions_today'],
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
            display: false,
            //labelString: 'Hours',
            //fontSize: 10,
          },
          gridLines: {
            drawOnChartArea: false,
            tickMarkLength: 5,
          },

        }],
        yAxes: [{
          scaleLabel: {
            display: true,
            labelString: qahml10n['graph_sessions'],
            fontSize: 10
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
} //end function drawRealtimeHourlyChart




/** --------------------------------------
 * Daterange for widget "Visits Overview" and "Growing Pages"
 */

let qahmKyou = qahm.kyou_str;
let qahmNissu = qahm.nisuu;

let kyouMomentObj = moment( qahmKyou, 'YYYY-MM-DD');
let momentObj = {};
momentObj.made = moment( kyouMomentObj ).subtract( 1, 'day' );
momentObj.kara = moment( kyouMomentObj ).subtract( qahmNissu, 'day' );
momentObj.sevenDaysToMade = moment( momentObj.made ).subtract( 6, 'day' );
momentObj.sevenDaysFromKara = moment( momentObj.kara ).add( 6, 'day' );

let pickDateRange = 'date = between ' + moment( momentObj.kara ).format('YYYY-MM-DD') + ' and ' + moment( momentObj.made ).format('YYYY-MM-DD');

let pVisitsDaterange  = document.getElementById('visits-daterange');
let spanGrowingDaterangeEarliest = document.getElementById('growing-daterange-earliest');
let spanGrowingDaterangeLatest   = document.getElementById('growing-daterange-latest');

let daterangeTextJa = {
  kara : ( moment( momentObj.kara ).format('M') ) + '月' + ( moment( momentObj.kara ).format('D') ) + '日',
  made : ( moment( momentObj.made ).format('M') ) + '月' + ( moment( momentObj.made ).format('D') ) + '日',
  sevenDaysFromKara : ( moment( momentObj.sevenDaysFromKara ).format('M') ) + '月' + ( moment( momentObj.sevenDaysFromKara ).format('D') ) + '日',
  sevenDaysToMade :   ( moment( momentObj.sevenDaysToMade ).format('M') ) + '月' + ( momentObj.sevenDaysToMade ).format('D') + '日',
};

if ( qahm.wp_lang_set === 'ja' ) { //何故かmoment.format変換がうまくいかないので日本語表示は別。
  pVisitsDaterange.innerText = daterangeTextJa.kara + ' ～ ' + daterangeTextJa.made;
  spanGrowingDaterangeEarliest.innerText = daterangeTextJa.kara + ' ～ ' + daterangeTextJa.sevenDaysFromKara;
  spanGrowingDaterangeLatest.innerText = daterangeTextJa.sevenDaysToMade + ' ～ ' + daterangeTextJa.made;
} else {
  pVisitsDaterange.innerText = moment( momentObj.kara ).format('ll') + ' - ' + moment( momentObj.made ).format('ll');
  spanGrowingDaterangeEarliest.innerText = moment( momentObj.kara ).format('ll') + ' - ' + moment( momentObj.sevenDaysFromKara ).format('ll');
  spanGrowingDaterangeLatest.innerText = moment( momentObj.sevenDaysToMade ).format('ll') + ' - ' + moment( momentObj.made ).format('ll');
}

if ( dateCk ) {
  console.log( pickDateRange );
  console.log( moment( momentObj.kara ).format('ll') );
}

let pickedDates = [];
let chartVisitsDatesLabel = [];
for ( let iii = 0; iii < qahmNissu; iii++ ) {
  let tempDate = moment( momentObj.kara ).add( iii, 'day' );
  pickedDates.push( moment( tempDate ).format('YYYY-MM-DD') );
  chartVisitsDatesLabel.push( moment( tempDate ).format('MM-DD') );
}

if ( dateCk ) {
  console.log( pickedDates );
}



/** ------------------------------------
 * widget "Visits Overview"
 */
let chartVisitsData = {
  users : [],
  sessions : [],
  pvs : [],
};
let qaVisitsAggr = {
  users : 0,
  sessions : 0,
  pvs : 0,
}

jQuery(
	function() {
    //visits stats ---------------
    jQuery.ajax(
      {
          type: 'POST',
          url: qahm.ajax_url,
          dataType : 'json',
          data: {
              'action' : 'qahm_ajax_select_data',
              'table' : 'summary_days_access',
              'select': '*',
              'date_or_id': pickDateRange,
              'count' : false,
              'nonce':qahm.nonce_api
          }
      }
    ).done(
        function( data ){
            qahm.widgetVisitsParam = data;
            if ( visitsCk ) {
              console.log( 'original Visits Param', qahm.widgetVisitsParam );
            }
            qahm.calcWidgetVisits( qahm.widgetVisitsParam );
            qahm.fillWidgetVisitsSum();
            qahm.drawWidgetChartAudience();             
        }
    ).fail(
        function( jqXHR, textStatus, errorThrown ){
            qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
            qahm.msgIfAjaxFailed('chart-container-audience');
        }
    ).always(
        function(){
        }
    ).then(
      function(){
        //channel ----------------------
        jQuery.ajax(
          {
            type: 'POST',
            url: qahm.ajax_url,
            dataType : 'json',
            data: {
              'action' : 'qahm_ajax_get_ch_data',
              'date' : pickDateRange,
              'nonce':qahm.nonce_api
            }
          }
        ).done(
          function( data ){
            qahm.widgetChannelParam = data;
            if ( qahm.widgetChannelParam ) {
              if ( channelCk ) {
                console.log( 'original Channel Param', qahm.widgetChannelParam );
              }
              qahm.drawWidgetChartChannel( qahm.widgetChannelParam );
            }
          }
        ).fail(
          function( jqXHR, textStatus, errorThrown ){
            qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
            qahm.msgIfAjaxFailed('chart-container-channel');
          }
        ).always(
          function(){
          }
        );
      }
    );    
  }
);


qahm.calcWidgetVisits = function( qaWidgetVisitsParam ) {    
  let indexMarker = 0;
  for ( let iii = 0; iii < qahmNissu; iii++ ) {
    let dataExists = false;
    for ( let jjj = indexMarker; jjj < qaWidgetVisitsParam.length; jjj++ ) {
      if ( qaWidgetVisitsParam[jjj].date == pickedDates[iii] ) {
        indexMarker = jjj;
        dataExists = true;
        chartVisitsData.pvs.push( qaWidgetVisitsParam[jjj].pv_count );
        chartVisitsData.sessions.push( qaWidgetVisitsParam[jjj].session_count );
        chartVisitsData.users.push( qaWidgetVisitsParam[jjj].user_count );
        qaVisitsAggr.pvs += qaWidgetVisitsParam[jjj].pv_count;
        qaVisitsAggr.sessions += qaWidgetVisitsParam[jjj].session_count;
        qaVisitsAggr.users += qaWidgetVisitsParam[jjj].user_count;
        break;
      }
    }
    if ( ! dataExists ) {
      chartVisitsData.pvs.push( null );
      chartVisitsData.sessions.push( null );
      chartVisitsData.users.push( null );
    }
  }

  if( visitsCk ){
    console.log( qaVisitsAggr );
    console.log( chartVisitsData );
  }
} //end calcWidgetVisits


qahm.graphColorBaseW = [ //=qahm.graphColorBaseA << admin-page-statisitcs.js
  'rgba(105, 164, 226, 1)', //sessions
  'rgba(186, 214, 244, 1)', //pvs
  'rgba(49, 53, 110, 1)', //users
  'rgba(47, 95, 152, 1)'
];


qahm.drawWidgetChartAudience = function() {
  let ctxaudience = document.getElementById('widgetChartAudience').getContext('2d');
  let widgetChartAudience = new Chart(ctxaudience, {
    type: 'bar',
    data: {
      labels: chartVisitsDatesLabel,
      datasets: [
        {
          type: 'line',
          label: qahml10n['graph_users'],
          data: chartVisitsData.users,
          backgroundColor: qahm.graphColorBaseW[2],
          borderColor: qahm.graphColorBaseW[2],
          borderJoinStyle: 'bevel',
          borderDash: [10, 1, 2, 1],
          pointStyle: 'rectRot',
          lineTension: 0,
          fill: false,
        },
        {
          type: 'line',
          label: qahml10n['graph_sessions'],
          data: chartVisitsData.sessions,
          backgroundColor: qahm.graphColorBaseW[0],
          borderColor: qahm.graphColorBaseW[0],
          borderJoinStyle: 'bevel',
          pointStyle: 'rect',
          lineTension: 0,
          fill: false,
        },  
        {
          //type: 'bar',
          label: qahml10n['graph_pvs'],
          data: chartVisitsData.pvs,
          backgroundColor: qahm.graphColorBaseW[1],
          borderColor: qahm.graphColorBaseW[1],
          borderWidth: 2,
          maxBarThickness: 100,
        }
      ]
    },
    options: {
      spanGaps: false,
      responsive: true,
      maintainAspectRatio: false,
      title: {
        display: true,
        text: qahml10n['Visits_graph'],
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
              if( axis.max < 10 ) {
                axis.max = 10;
                axis.stepSize = 1;
              }
            },
        }],
      },
      legend: {
        display: true,
        position: 'bottom',
        labels: {
          usePointStyle: true
        }
      },

      tooltips: {
        mode: 'index',
        itemSort: function(a, b, data) {
          return (a.datasetIndex - b.datasetIndex);
        },

      },

    }
  });
} //end drawWidgetChart


qahm.fillWidgetVisitsSum = function(){
  let divSession = document.getElementById('visits-sessions');
  let divPv      = document.getElementById('visits-pvs');
  divSession.innerText = qaVisitsAggr.sessions;
  divPv.innerText = qaVisitsAggr.pvs;
} //end fillWidgetVisitsSum


qahm.drawWidgetChartChannel = function(qaWidgetChannelParam){
  //calculate
  let chartChannelLabel   = [];
  let chartChannelData = [];
  let idx = 0;
  for ( let mmm = qaWidgetChannelParam.length -1; 0 <= mmm; --mmm ) {
      if ( Number( qaWidgetChannelParam[mmm][3] ) !== 0 ) {
          chartChannelData[idx] = Number( qaWidgetChannelParam[mmm][3] );
          chartChannelLabel[idx]   = qaWidgetChannelParam[mmm][0];
          idx++;
      }
  }
  for ( let lll = 0; lll < chartChannelData.length; lll++ ) {
      for (let mmm = chartChannelData.length - 1; lll < mmm; --mmm) {
          let right_ss = chartChannelData[mmm];
          let left_ss = chartChannelData[mmm - 1];
          let right_lb = chartChannelLabel[mmm];
          let left_lb = chartChannelLabel[mmm - 1];
          if (left_ss < right_ss) {
              chartChannelData[mmm - 1] = right_ss;
              chartChannelData[mmm] = left_ss;
              chartChannelLabel[mmm - 1] = right_lb;
              chartChannelLabel[mmm] = left_lb;
          }
      }
  }
  //draw
  let ctxchannel = document.getElementById('widgetChartChannel').getContext('2d');
  let widgetChartChannel = new Chart(ctxchannel, {
    type: 'doughnut',
    data: {
      labels: chartChannelLabel,
      datasets: [{
        data: chartChannelData,
        backgroundColor:  ['#31356E','#2F5F98','#2D8BBA','#41B8D4','#6CE5E8'],
      }],
    },
    options: {
      title: {
        display: true,
        text: qahml10n['Channel'],
      },
      legend: {
        position: 'right',
      },
      cutoutPercentage: 40,
    },
  });
} //end drawWidgetChartChannel



/** ---------------------------------------
 * widget "Growing Landing Page"
 */
jQuery(
  function(){
			//growth landingpage table
			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action' : 'qahm_ajax_get_gw_data',
						'date' : pickDateRange,
						'nonce':qahm.nonce_api
					}
				}
			).done(
				function( data ){
					let ary = data;
					if (ary) {
            qahm.writeGrowingPage(ary);
					}
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
          qahm.msgIfAjaxFailed('growing-table-container');

				}
			).always(
				function(){

				}
			);
  }
);

qahm.writeGrowingPage = function( qahmGrowingPageParam) {
  let growingTableTbody = document.getElementById('growing-table-tbody');
  let widgetGrowingTBody = [];
  let rowCounter = 0;
  let classRowEven = '';

  let putCrownTdIdx = 0;
  let putCrownTdIdxAryObj = {};
  let topGrowth = 0;
  let putCrownTdAry = [ 
    [],
    [],
    []
  ];
  let crownColor = 0;
  
  //skip data without page title
  let validPageParam = [];
  for ( let iii = 0; iii < qahmGrowingPageParam.length; iii++ ) {
    if ( qahmGrowingPageParam[iii][1] !== '' ) {
      validPageParam.push( qahmGrowingPageParam[iii] );
    }
  }


  if ( validPageParam.length === 0 ) {
    qahm.msgWhenNoData( 'growing-table-container' );

  } else {
    //sort by growth rate
    validPageParam.sort(function(a, b) {
      return b[6] - a[6];
    });

    for ( let iii = 0; iii < validPageParam.length; iii++ ) {
      if ( rowCounter > 30 ) {
        break;
      }    
      rowCounter++;
      if ( rowCounter % 2 === 0 ) {
        classRowEven = ' class="row-even"'
      } else {
        classRowEven = '';
      }
      let faTdId = 'fatd' + rowCounter.toString();
      let growingFa = '';
      if ( Number( validPageParam[iii][6] ) > 0 ) {
        growingFa = '<span class="growing-positive-fa"><i class="fas fa-arrow-circle-up"></i></span>';
      } else if ( Number( validPageParam[iii][6] ) == 0 ) {
        growingFa = '<span class="growing-unchanged-fa"><i class="fas fa-arrow-circle-right"></i></span>';
      }
              
      //want to put "crown" on top growth rate
      if ( crownColor < 3 ) {
        if ( rowCounter === 1 ) {
          topGrowth = Number( validPageParam[iii][6] );
          if ( Number( validPageParam[iii][6] > 0 ) ) {
            putCrownTdAry[crownColor].push( faTdId );
          }
        } else {
          if( Number( validPageParam[iii][6] === topGrowth ) ) {          
            if ( Number( validPageParam[iii][6] > 0 ) ) {
              putCrownTdAry[crownColor].push( faTdId );
            }
          } else if ( Number( validPageParam[iii][6] ) < topGrowth ) {
            crownColor++;      
            topGrowth = Number( validPageParam[iii][6] );
            if ( Number( validPageParam[iii][6] > 0 ) && ( crownColor < 3 ) ) {
              putCrownTdAry[crownColor].push( faTdId );
            }
          }
        }
      }
        
      let widgetGrowingTr = '';
      widgetGrowingTr += '<tr' + classRowEven + '>';
      widgetGrowingTr += '<td>' + validPageParam[iii][1] + '</td>';
      widgetGrowingTr += '<td>' + validPageParam[iii][3] + '</td>';
      widgetGrowingTr += '<td class="growing-pct">' + validPageParam[iii][6] + '%</td>';
      widgetGrowingTr += '<td id="' + faTdId + '">' + growingFa + '</td>';
      widgetGrowingTr += '</tr>';

      widgetGrowingTBody.push( widgetGrowingTr );
    

    }  
    growingTableTbody.insertAdjacentHTML( 'afterbegin', widgetGrowingTBody.join('') );

    //put crown
    for ( let jjj = 0; jjj < putCrownTdAry.length; jjj++ ) {
      if ( jjj === 0 ) {
        crownHtml = '<span class="growing-crown-fa-gold"><i class="fas fa-crown"></i></span>';
      } else if ( jjj === 1 ) {
        crownHtml = '<span class="growing-crown-fa-silver"><i class="fas fa-crown"></i></span>';
      } else {
        crownHtml = '<span class="growing-crown-fa-bronze"><i class="fas fa-crown"></i></span>';
      }
      for ( let kkk = 0; kkk < putCrownTdAry[jjj].length; kkk++ ) {
        let putCrownTd = document.getElementById( putCrownTdAry[jjj][kkk] );
        putCrownTd.insertAdjacentHTML( 'beforeend', crownHtml );
      }
    }

  }
}



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



/**-------------------------------
 * message when ajax failed
 *         when no data
 */
qahm.msgIfAjaxFailed = function( htmlElementId ) {
  let fillDiv = document.getElementById( htmlElementId );
  if ( fillDiv.children.length === 0 ) {
    fillDiv.insertAdjacentHTML( 'afterbegin', ('<p>' + qahml10n['msg_ajax_failed_cannot_display_data'] + '</p>') );
  }
}

qahm.msgWhenNoData = function( htmlElementId ) {
  let fillDiv = document.getElementById( htmlElementId );
  if ( fillDiv.children.length === 0 ) {
    fillDiv.insertAdjacentHTML( 'afterbegin', ('<p class="msg_no_data">' + qahml10n['msg_there_is_no_data'] + '</p>') );
  }
}
