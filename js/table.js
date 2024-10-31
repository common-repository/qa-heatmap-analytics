/*
■ 使い方
・最初の描画時
1.classをnewする
2.初期パラメーターを設定する（特にヘッダー部分）
3.データ部分をrawDataArrayにセットする
4.generateTable()をコールする

・データのアップデート時
1.データ部分をrawDataArrayにセットする
2.updateTable($is_resetFilter = false)をコールする
$is_resetFilterは、ユーザーが利用中のfilterをリセットするかどうか。初期値はfalse

 */

/**
 * フィルター等で使われている語の変換（多言語対応用）
 */
let wordsInTable = {
    'tjs_open_filter'      : '全フィルタを表示',
    'tjs_close_filter'     : '全フィルタを隠す',
    'tjs_clear_filter'     : '全フィルタをクリア',
    'tjs_sort'             : '並び替え',
    'tjs_filter'           : 'フィルター',
    'tjs_word_for_filter'  : '任意の文字',
    'tjs_include'          : 'を含む',
    'tjs_not_include'      : 'を含まない',
    'tjs_select_all'       : '全て',
    'tjs_filter_equal'     : '（等しい）',
    'tjs_filter_lt'        : '（以下）',
    'tjs_filter_gt'        : '（以上）',
    'tjs_filter_data_tani_currency' : '円',
    'tjs_filter_data_tani_second'   : '秒',
    'tjs_alert_msg_over500' : '全てチェックは501個以上できません。フィルターで条件を絞って再度お試しください。',
    'tjs_alert_msg_over100' : '全てチェックは101個以上できません。フィルターで条件を絞って再度お試しください。',
};
let wordsAryKey = [
    'tjs_open_filter',
    'tjs_close_filter',
    'tjs_clear_filter',
    'tjs_sort',
    'tjs_filter',
    'tjs_word_for_filter',
    'tjs_include',
    'tjs_not_include',
    'tjs_select_all',
    'tjs_filter_equal',
    'tjs_filter_lt',
    'tjs_filter_gt',
    'tjs_filter_data_tani_currency',
    'tjs_filter_data_tani_second',
    'tjs_alert_msg_over500',
    'tjs_alert_msg_over100',
];
if ( typeof qahml10n !== 'undefined' ) {
    for ( let iii = 0; iii < wordsAryKey.length; iii++ ) {
        if ( qahml10n[wordsAryKey[iii]] ) {
            wordsInTable[wordsAryKey[iii]] = qahml10n[wordsAryKey[iii]];
        }
    }
}




class QATableGenerator {
    constructor() {
        this.prefix = 'qa_';
        this.loadinggif = 'loading.gif';
        this.color = {base : '#fff8e7', main : '#333333' , accent : '#ffee36' }
		this.visible2DataIdx = 0;
        this.firstCheck = 99;
        this.targetID = "";
        this.isFilterShow = 2; // -2 allhidden -1 somecolumn active and hidden, 1 somecolumn active and show, 2 allshow
        this.dataObj = {
            meta : {
				title : "Table",
				has_header : true,
				tableID : '',
				tableClass : '',
				key : { Index :'', Sort  : 'dsc' }, //asc
            },
            body : {
                header: [],
					// KeyCol : sum or array
                    // name : "",
                    // type : "",
                    // format : "", <-this is function
					// thParm : style="width:X%"
					// tdParm : style="width:X%"
					// total : sum or avg
                    // title : "",
                    // description : "",
                    // hasFilter : false,
                    // isHide : false
					// tdHtml : <a ref="%!1">%!me</a>  1 is dataObj index, me is this index data(mine)
					// filterAddIndex : 2 ->2 is dataObj index
                row: []
            }
        };
        this.thLabelHtml  = '';
        this.rawDataArray = [];
        this.offset = 0;
        this.pageno = 1;
        this.dataLimit = 5000;
        this.dataMaxCount = 0;
        this.svg = {
            asc : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort-numeric-down" class="svg-inline--fa fa-sort-numeric-down fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M304 96h16v64h-16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h96a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16h-16V48a16 16 0 0 0-16-16h-48a16 16 0 0 0-14.29 8.83l-16 32A16 16 0 0 0 304 96zm26.15 162.91a79 79 0 0 0-55 54.17c-14.25 51.05 21.21 97.77 68.85 102.53a84.07 84.07 0 0 1-20.85 12.91c-7.57 3.4-10.8 12.47-8.18 20.34l9.9 20c2.87 8.63 12.53 13.49 20.9 9.91 58-24.76 86.25-61.61 86.25-132V336c-.02-51.21-48.4-91.34-101.85-77.09zM352 356a20 20 0 1 1 20-20 20 20 0 0 1-20 20zm-176-4h-48V48a16 16 0 0 0-16-16H80a16 16 0 0 0-16 16v304H16c-14.19 0-21.36 17.24-11.29 27.31l80 96a16 16 0 0 0 22.62 0l80-96C197.35 369.26 190.22 352 176 352z"></path></svg>',
			dsc : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort-numeric-down-alt" class="svg-inline--fa fa-sort-numeric-down-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M176 352h-48V48a16 16 0 0 0-16-16H80a16 16 0 0 0-16 16v304H16c-14.19 0-21.36 17.24-11.29 27.31l80 96a16 16 0 0 0 22.62 0l80-96C197.35 369.26 190.22 352 176 352zm224 64h-16V304a16 16 0 0 0-16-16h-48a16 16 0 0 0-14.29 8.83l-16 32A16 16 0 0 0 304 352h16v64h-16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h96a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zM330.17 34.91a79 79 0 0 0-55 54.17c-14.27 51.05 21.19 97.77 68.83 102.53a84.07 84.07 0 0 1-20.85 12.91c-7.57 3.4-10.8 12.47-8.18 20.34l9.9 20c2.87 8.63 12.53 13.49 20.9 9.91 58-24.77 86.25-61.61 86.25-132V112c-.02-51.21-48.4-91.34-101.85-77.09zM352 132a20 20 0 1 1 20-20 20 20 0 0 1-20 20z"></path></svg>',
			filter : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="filter" class="svg-inline--fa fa-filter fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M487.976 0H24.028C2.71 0-8.047 25.866 7.058 40.971L192 225.941V432c0 7.831 3.821 15.17 10.237 19.662l80 55.98C298.02 518.69 320 507.493 320 487.98V225.941l184.947-184.97C520.021 25.896 509.338 0 487.976 0z"></path></svg>',
			filtered : '<svg version="1.1" id="レイヤー_1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 320 512" style="enable-background:new 0 0 320 512;" xml:space="preserve"><path fill="currentColor"  d="M20.7,361h85.7c7.7,0,11.6,10.5,6.1,16.7l-42.8,48.4c-3.4,3.8-8.9,3.8-12.2,0l-42.9-48.4C9.1,371.5,13,361,20.7,361z"/><g><path fill="currentColor" d="M305.5,86H26.5c-12.8,0-19.3,17.3-10.2,27.4l111.3,123.9v138c0,5.2,2.3,10.2,6.2,13.2l48.1,37.5c9.5,7.4,22.7-0.1,22.7-13.2V237.4l111.3-123.9C324.8,103.3,318.4,86,305.5,86z"/></g></svg>',
			checkbox : '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-square" class="svg-inline--fa fa-check-square fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 32H48C21.49 32 0 53.49 0 80v352c0 26.51 21.49 48 48 48h352c26.51 0 48-21.49 48-48V80c0-26.51-21.49-48-48-48zm0 400H48V80h352v352zm-35.864-241.724L191.547 361.48c-4.705 4.667-12.303 4.637-16.97-.068l-90.781-91.516c-4.667-4.705-4.637-12.303.069-16.971l22.719-22.536c4.705-4.667 12.303-4.637 16.97.069l59.792 60.277 141.352-140.216c4.705-4.667 12.303-4.637 16.97.068l22.536 22.718c4.667 4.706 4.637 12.304-.068 16.971z"></path></svg>',
			sortdown : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort-down" class="svg-inline--fa fa-sort-down fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41z"></path></svg>',
			close : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm121.6 313.1c4.7 4.7 4.7 12.3 0 17L338 377.6c-4.7 4.7-12.3 4.7-17 0L256 312l-65.1 65.6c-4.7 4.7-12.3 4.7-17 0L134.4 338c-4.7-4.7-4.7-12.3 0-17l65.6-65-65.6-65.1c-4.7-4.7-4.7-12.3 0-17l39.6-39.6c4.7-4.7 12.3-4.7 17 0l65 65.7 65.1-65.6c4.7-4.7 12.3-4.7 17 0l39.6 39.6c4.7 4.7 4.7 12.3 0 17L312 256l65.6 65.1z"></path></svg>',
            search : '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="search" class="svg-inline--fa fa-search fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"></path></svg>'
		}
        this.visibleSort  = { index: 1, order: 'asc' };
        this.visibleArray = [];
        this.globalVisibleArray = [];
        this.globalVisibleDone = false;
        this.visibleCount = 0;
        this.filterArray =[];
        this.globalFilterArray =[];
        this.globalFilterDone = false;
        this.filterNoChecked  = true;
        this.filterAllCheckArray  = [];
        this.filterDataIdxSet = new Set();
        this.divtag   = '';
        this.filtertr = '';
        this.headerTableByID = '';
        this.dataTableByID = '';
        this.tbControlerByID = '';
        this.queueEvents = {};
        this.dataTRHeight = 0;
        this.checkTRArray = [];
        this.checkTRNowBlocks = [];
        this.checkTRArrayDone = [];
        this.dataTRArray = [];
        this.dataTRNowBlocks = { divheight:200, divheightMax:0, scrollpos:0, t_redrawpos:0, b_redrawpos:0, trinblock:0, blockcount:8, startidx:0, endidx:0, is_drawing:false };
        this.formatSpecifier = '%!';
        //filterd array
        this.graphArray = [];
        //callback
        this.drawTableComplete = '';
        this.qahmobj = '';
        this.progBarDiv = '';
        this.progBarMode = 'screen';
        this.tableScaleBtnDiv = '';
        this.workerjs = 'tableworker.js';
        this.wordsInTable = wordsInTable;

		// imai add
		this.checkedDataArray = [];
		this.checkedColMaxArray = [];
    }
    writeCSS() {
        let prefix = this.prefix;
        let css = `
            /* <style> */
			.bl_jsTb{
				width: 100%;
				table-layout: fixed;
			}
			.bl_jsTb svg {
				width: 15px;
			}

            /* Element */
			.el_fadein {
				animation: fadeIn 0.5s ease 0s 1 normal;
			}
			@keyframes fadeIn {
			  0% {opacity: 0} 
			  100% {opacity: 1}
			}
            .el_btn input[type="button"] {
                font-size: 0.8em;
                line-height: 1.2;
                padding: 0.5em;
                border-width: 2px;
                border-style: solid;
                
                display: inline-flex;
                justify-content: center;
                align-items: center;                               
                border-radius: 0.25em;
                box-shadow: 0 1px 4px hsla(220, 90%, 37%, 0.25);
            }
            div.el_uniqitem {
                margin-bottom: 2px;
            }

            #${prefix}bl_overlayReload {
              position: absolute;
              top:0;
              z-index: 10;
              width: 100%;
              height: 100%;
              visibility: hidden;
              opacity: 0;
              background: rgba(0,0,0,0.6);
            }
            #${prefix}bl_overlayReload.errorReload {
              visibility: visible;
              opacity: 1;
            }
            
            .bl_overlayFlex {
              width: 100%;
              height:100%;
              display: flex;
              justify-content: center;
              align-items: center; 
            }
            #${prefix}bl_overlayInnner {
              padding:10px 60px;
              background-color:#FFF;
              text-align:center;
            }
            
            #${prefix}btn_tableReload {
              display:block;
              margin:20px auto;
              padding:10px 30px;
              background-color:#eee;
              border:solid #ccc 1px;
              cursor: pointer;
            }

            /* tb Controler */
			.bl_tbControler {
				position: relative;
				height: 40px;
			}
			.bl_tbControler .el_caption{
				font-size: 80%;
			}
			.bl_tbControler .bl_tbControlerL {
				position: absolute;
				left: 0;
				bottom: 0;
			}
			.bl_tbControler .bl_tbControlerL div{
				display: inline-block;
				line-height: 1.5em;
			}
			.bl_tbControler .bl_tbControlerR {
				position: absolute;
				right: 0;
				bottom: 0;
			}
			.bl_tbControler .bl_tbControlerR div{
				display: inline-block;
				line-height: 1.5em;
			}

            /* Header & Filter Block */
            /* JS default set */
			td.${prefix}is_filterActive{
				visibility: visible;!important;
			}

            /* other css */
			.bl_jsTb th{
				position: relative;
			}
			.bl_filterTD {
				visibility: hidden;
				position: relative;
			}
			.bl_jsTb .bl_thSortmMark {
				position: absolute;
				left: 2%;
				top: 2%;
				opacity: 0.5;
				color: ${this.color.base};
			}
			.el_filterSvg svg{
				filter: drop-shadow(3px 3px 5px #000);
			}
			div.bl_filterContainer {
				padding: 2px;
				width: 95%;
				min-width: 100px;
				height: 300px;
				max-height: 280px;
				font-size: 85%;
				border-left: 1px solid #ccc;
				border-right: 1px solid #ccc;
				/*border: 1px solid #ccc;*/
				/*border-radius: 15px;*/
				background-color: #fff;
				z-index: 1;
			}
			/*div.bl_filterContainer:hover {*/
				/*width: 120%;*/
				/*z-index: 1;*/
				/*position: absolute;*/
				/*top :10px;*/
				/*min-width: 130px;*/
				/*max-width: 500px;*/
				/*left :10px;*/
				/*border: 2px solid #999;*/
			/*}*/

			.bl_filterUnit {
				vertical-align: top;
				max-width: 100%;
				width: 100%;
				background: #fff;
				/*border: 1px solid #777;*/
				line-height: 1.1;
				font-weight: normal;
				position: relative;
			}
			.bl_filterUnit .el_closeBtn {
				position: absolute;
				top: 0;
				right: 0;
				text-align: right;
			}
			.bl_filterUnit .el_closeBtn svg {
				width: 20px;
				height: 20px;
			}
			.bl_filterUnit form {
				max-height: 300px;
				width: 100%;
				/*overflow: hidden;*/
				/*white-space: nowrap;*/
			}
			.bl_filterUnit form:hover {
				/*overflow: scroll scroll;*/
				/*-ms-overflow-style:-ms-autohiding-scrollbar;*/
			}

			.bl_filterUnit .el_filterTitle {
				border-radius: 5px;
				margin: 5px 0 5px 0;
			    padding: 2px;
				text-align: left;
				color: white;
				background-color: #8C95B4;
				font-size: 12px;
				font-weight: bold;
			}
			.bl_filterUnit .bl_sortControl {
				text-align : left;
				padding    : 2px 0 5px 0;
			}
			.bl_filterUnit .bl_sortControl a {
				line-height: 1.5em;
			}
			.bl_filterUnit .bl_SearchBoxes {
				padding    : 5px 3px;
				border-bottom: 1px dotted #ccc;
				margin-bottom: 5px;
			}
			.bl_filterUnit .bl_SearchBoxes .${prefix}js_allCheck {
				padding    : 3px 0 3px 0 ;
				font-size: 80%;
			}
			.bl_filterUnit .bl_SearchBoxes input {
				width      : 100%;
				min-width: 60px;
				font-weight: normal;
				font-size  : 95%;
				line-height: 1.5em;
				box-sizing : border-box;
				border-radius: 3px;
				border     : 1px solid #ccc;
				box-shadow: inner 0 0 4px rgba(0, 0, 0, 0.2);
				padding: 2px;
			}
			.bl_filterUnit .bl_SearchBoxes select {
				width      : 40%;
				min-width: 90px;
				margin-top: 5px;
				margin-bottom: 5px;
				font-weight: normal;
				font-size  : 80%;
				box-sizing : border-box;
				border-radius: 3px;
				border     : 2px solid #ccc;
				box-shadow: inner 0 0 4px rgba(0, 0, 0, 0.2);
				padding: 2px;
			}
			.bl_filterUnit .bl_SearchBoxes input[type="number"] {
				width      : 40%;
			}
			.bl_filterUnit .bl_SearchBoxes input[type="datetime-local"] {
				width      : 90%;
				font-size  : 90%;
			}
			.bl_filterUnit .bl_SearchBoxes input[type="date"] {
				width      : 60%;
				min-width  : 110px; !important;
				font-size  : 80%;
			}
			.bl_filterUnit .bl_SearchBoxes input[type="time"] {
				width      : 35%;
				min-width: ; 50px;
				font-size  : 80%;
			}
			.bl_filterUnit .bl_uniqItems {
				text-align : left;
				padding    : 2px 0 2px 0;
				height: 100px;
				width: 100%;
				min-width: 100px;
				overflow-y: scroll;
				-ms-overflow-style:-ms-autohiding-scrollbar;
			}		
			#${this.targetID} table.bl_itemTable {
				border     : none !important;
				background-color: white;
				width: 100%;
				table-layout: fixed;
                /*word-break: break-all;*/
				/*overflow: hidden;*/
				border-collapse:collapse;
                border-spacing:0;                
			}

			#${this.targetID} table.bl_itemTable td{
				border     : none;
				white-space: nowrap;
				overflow: hidden;
				padding: 2px;
			}

			.bl_filterUnit .bl_uniqItems .bl_itemTable thead {
			    width: 100%;
			    border-bottom: dotted 1px #ccc;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable thead td {
                /* 縦スクロール時に固定する */
                position: -webkit-sticky;
                position: sticky;
                top: 0;
                background-color: white;
                /* tbody内のセルより手前に表示する */
                z-index: 1;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable tbody.bl_itemTableTbody {
			    /*display: block;*/
				height: 100px;
				width: 100%;
				/*overflow-y: scroll;*/
				/*overflow-x: hidden;*/
				/*-ms-overflow-style:-ms-autohiding-scrollbar;*/
			}

			.bl_filterUnit .bl_uniqItems .bl_itemTable td.el_itemTableTDL {
			    text-align: left;
			    padding: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable .el_itemTableTDR {
			    text-align: right;
			    padding: 0;
			    padding-right: 2px;
                white-space: nowrap;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable .el_checkboxDataText {
			    font-size: 80% !important;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable .el_dataSortText {
			    color: #206CFF;
			}
			.bl_filterUnit .bl_uniqItems .bl_itemTable .el_checkboxDataText svg{
			    width: 24px !important;
			}
			.bl_filterUnit .bl_uniqItems label {
				padding    : 0 10px 0 3px;
				line-height: 120%;
			}
			.bl_filterUnit .bl_uniqItems .el_uniqitem  label{
				max-width: 100%;
				transition-duration: 500ms;
				transition-property:font-weight;
				font-size: 9px;
			}
			.bl_filterUnit .el_uniqitem input:checked + label {
				font-weight: bold;
			}

            /* Data Block */
            .bl_dataViewer {
                position: relative;
                width: 100%;
                resize: vertical;
                overflow-y: scroll;
            }
            div#${prefix}js_dataViewer {
                height: ${this.dataTRNowBlocks.divheight}px;
            }
            .bl_dataViewerTable {
                position: absolute;
                top: 0;
                left: 0;
            }
            .el_flash{
                animation: flash 0.3s linear infinite;
                color:#0091EA;
                border-bottom: solid 2px #0091EA;
            }
            @keyframes flash {
              0%,100% {
                opacity: 1;
              }
              50% {
                opacity: 0;
              }
            }

			/* imai add start */
            div#${prefix}js_headerViewer {
				overflow-y: scroll;
			}

			/* テーブル拡縮ボタンまわり */
			div.sc_tableScaleBlock {
				width: 18px;
				margin: 0 0 0 auto;
				cursor: pointer;
			}

			div.sc_tableScaleUp, div.sc_tableScaleDown {
				position: relative;
				display: inline-block;
				width: 18px;
				height: 18px;
				background-color: #999;
				border-radius: 2px;
				transition: background-color 0.3s ease;
			}

			div.sc_tableScaleUp:hover, div.sc_tableScaleDown:hover {
				background-color: #777;
			}

			div.sc_tableScaleUp::before, div.sc_tableScaleDown::before {
				content: "";
				position: absolute;
				width: 0;
				height: 0;
				border-left: 5px solid transparent;
				border-right: 5px solid transparent;
			}
		
			div.sc_tableScaleUp::before {
				top: 6px;
				left: 4px;
				border-bottom: 5px solid #fff;
			}
		
			div.sc_tableScaleDown::before {
				bottom: 6px;
				left: 4px;
				border-top: 5px solid #fff;
			}
			/* imai add end */
		`;
        let cssstyle   = document.createElement('style');
        let rule       = document.createTextNode(css);
        cssstyle.media = 'screen';
        cssstyle.type  = 'text/css';
        if (cssstyle.styleSheet) {
            cssstyle.styleSheet.cssText = rule.nodeValue;
        } else {
            cssstyle.appendChild(rule);
        }
        document.getElementsByTagName('head')[0].appendChild(cssstyle);
	}

    svgChangeColor(svg, color, isNotUrl = true) {
        if (!isNotUrl) {
            color = color.replace('#', '%23');
        }
        svg = svg.replace(/fill="currentColor"/g, `fill="${color}"`);
        return svg;
    }

    /** -------------------------------------------
     * 前準備
     * データを配列に入れる処理
     */

    setDataObj(offset = 0) {
	    let datamax = this.rawDataArray.length;
	    this.dataMaxCount = this.rawDataArray.length;
	    this.dataObj.body.row = new Array(datamax);
	    for (let iii = offset; iii < datamax; iii++) {
            this.dataObj.body.row[iii] = this.rawDataArray[iii];
        }

        // データが多い場合、全配列作成をバックグラウンドで動作させる
        if ( this.dataLimit < datamax ) {
            let dataObjJson = JSON.stringify(this.dataObj);
            let qw = new queryWorker(this.workerjs);

            //send message
            qw.sendQuery('createGlobalArray', dataObjJson);
            //return
            qw.addListener('createGlobalArray', (visijson, filjson) => {
                this.globalVisibleDone = true;
                this.globalFilterDone  = true;
                this.visibleArray = JSON.parse(visijson);
                if (this.visibleSort) {
                    this.sortVisibleArray(this.visibleSort.index, this.visibleSort.order);
                }
                this.filterArray = JSON.parse(filjson);
                this.redrawTable();
            }, false);
        }
    }

    generateVisibleArray() {
        this.visibleArray = [];
        if (this.dataObj.meta.has_header) {
            let datamax = this.dataLimit;
            if ( this.dataObj.body.row.length <= datamax) {
                datamax = this.dataObj.body.row.length;
            }
            for ( let iii = 0; iii < datamax; iii++ ) {
                let newcol = [];
                //visible2DataIdx（index0）はdataObjのrowindexと同じにして非表示にする＝dataObjのcolumnと1つずれる=visibleはsortの時なども1列目から始まるのでわかりやすくなる
                newcol[this.visible2DataIdx] = iii;

                for ( let colidx = 0; colidx < this.dataObj.body.row[iii].length; colidx++ ) {
                    let col = this.dataObj.body.row[iii][colidx];
                    if (!this.dataObj.body.header[colidx].isHide) {
                        newcol.push(col);
                    }
                }
                this.visibleArray.push(newcol);
            }
        } else {
            for ( let iii = 0; iii < this.dataLimit; iii++ ) {
                let newcol = [];
                //visible2DataIdx（index0）はdataObjのrowindexと同じにして非表示にする＝dataObjのcolumnと1つずれる=visibleはsortの時なども1列目から始まるのでわかりやすくなる
                newcol[this.visible2DataIdx] = iii;
                this.dataObj.body.row[iii].forEach((col, colidx) => {
                    newcol.push(col);
                })
                this.visibleArray.push(newcol);
            }
        }
        //global対応が不要であればdone
        let datamax = this.rawDataArray.length;
        if ( datamax <= this.dataLimit ) {
            this.globalVisibleDone = true;
        }
    }

    sortVisibleArray(tableidx, sort_order) {
        this.visibleArray.sort((first, second) => {
            let headeridx = this.IdxForHeader(tableidx - 1);
            let sort_result = 0;
            if (0 <= headeridx) {
                switch (this.dataObj.body.header[headeridx].type) {
                    case 'number':
                    case 'currency':
                    case 'second':
                    case 'unixtime':
                        sort_result = first[tableidx] - second[tableidx];
                        break;

                    case 'datetime':
                        if (Date.parse(first[tableidx]) > Date.parse(second[tableidx])) {
                            sort_result = 1;
                        } else {
                            sort_result = -1;
                        }
                        break;

                    case 'string' :
                    default:
                        sort_result = first[tableidx].localeCompare(second[tableidx], this.locale);
                        break;

                }
            }
            if (sort_order === 'dsc') {
                sort_result = sort_result * -1;
            }
            this.visibleSort.index = tableidx;
            this.visibleSort.order = sort_order;
            return sort_result;
        });
    }

    sortFilterArray(tableidx, sort_order) {
        this.filterArray[tableidx].sort((first, second) => {
            let headeridx = this.IdxForHeader(tableidx - 1);
            let sort_result = 0;
            if (0 <= headeridx) {
                switch (this.dataObj.body.header[headeridx].type) {
                    case 'number':
                    case 'currency':
                    case 'second':
                    case 'unixtime':
                        sort_result = first.uniqitem - second.uniqitem;
                        break;

                    case 'datetime':
                        if (Date.parse(first.uniqitem) > Date.parse(second.uniqitem)) {
                            sort_result = 1;
                        } else {
                            sort_result = -1;
                        }
                        break;
                    case 'string' :
                    default:
                        sort_result = first.uniqitem.localeCompare(second.uniqitem, this.locale);
                        break;

                }
            }
            if (sort_order === 'dsc') {
                sort_result = sort_result * -1;
            }
            return sort_result;
        });
    }

    // generateFilterArray() {
    //     this.filterArray.push();
    //     let revary = this.reverseArray(this.visibleArray);
    //     revary.forEach((col, colidx) => {
    //         let alluniq = [];
    //         if (colidx > this.visible2DataIdx) {
    //             let uniqary = this.uniqArray(col);
    //             let allchk = uniqary.forEach((uniq, uniqidx) => {
    //                 let tmpary = [];
    //                 col.forEach((rowdata, rowidx) => {
    //                     if (rowdata === uniq) {
    //                         tmpary.push(revary[this.visible2DataIdx][rowidx]);
    //                     }
    //                 });
    //                 alluniq.push({
    //                     uniqidx: uniqidx,
    //                     uniqitem: uniq,
    //                     dataidx: tmpary,
    //                     checked: false
    //                 });
    //
    //             });
    //         }
    //         this.filterArray.push(alluniq);
    //     });
    // }

   generateFilterArray() {
        if (this.visibleArray.length > 0 ) {
            let revary = this.reverseArray(this.visibleArray);
            this.filterArray = [revary.length + 1];
            for (let colidx = 0; colidx < revary.length; colidx++) {
                let colary = revary[colidx];
                let colmax = colary.length
                let colobj = [colmax];
                for (let iii = 0; iii < colmax; iii++) {
                    colobj[iii] = {rowid: iii, value: colary[iii]};
                }
                colobj.sort((a, b) => {
                    if (a.value === null) {
                        a.value = ''
                    }
                    if (b.value === null) {
                        b.value = ''
                    }
                    if (a.value < b.value) {
                        return -1;
                    }
                    if (a.value > b.value) {
                        return 1;
                    }
                    return 0;
                })
                let alluniq = [];
                if (colidx > this.visible2DataIdx) {
                    let uniqary = this.uniqArray(colary);
                    uniqary.sort((a, b) => {
                        if (a === null) {
                            a = ''
                        }
                        if (b === null) {
                            b = ''
                        }
                        if (a < b) {
                            return -1;
                        }
                        if (a > b) {
                            return 1;
                        }
                        return 0;
                    });
                    alluniq = [uniqary.length];

                    let rowidx = 0;
                    for (let uniqidx = 0; uniqidx < uniqary.length; uniqidx++) {
                        let uniq = uniqary[uniqidx];
                        let tmpary = [];
                        //ソート済みなのでwhileで比較できる
                        let checkcontinue = true
                        while (checkcontinue) {
                            if (uniq !== null && colobj[rowidx].value !== null) {
                                if (colobj[rowidx].value.toString() === uniq.toString()) {
                                    tmpary.push(revary[this.visible2DataIdx][colobj[rowidx].rowid]);
                                    rowidx++;
                                } else {
                                    checkcontinue = false;
                                }
                                if (colmax <= rowidx) {
                                    checkcontinue = false;
                                }
                            } else {
                                if (uniq === null && colobj[rowidx].value === '') {
                                    tmpary.push(colobj[rowidx].rowid);
                                    rowidx++;
                                } else {
                                    checkcontinue = false;
                                }
                                if (colmax <= rowidx) {
                                    checkcontinue = false;
                                }

                            }
                        }
                        alluniq[uniqidx] = {
                            uniqidx: uniqidx,
                            uniqitem: uniq,
                            dataidx: tmpary,
                            checked: false
                        };
                    }
                }
                this.filterArray[colidx] = alluniq;
            }
        } else {
            this.filterArray = [];

        }
        //global対応が不要であればdone
        let datamax = this.rawDataArray.length;
        if ( datamax <= this.dataLimit ) {
            this.globalFilterDone = true;
        }
	}


    generateFilterDataIdxSet() {
        this.filterNoChecked = true;
        let allchkary = [];
        allchkary = this.createAllCheckArray();
	    this.filterAllCheckArray = allchkary;
        // 他の列にチェックが入っているか否か
        for (let ttt = 1; ttt < this.filterAllCheckArray.length; ttt++ ) {
            if ( 0 < this.filterAllCheckArray[ttt].length) {
                this.filterNoChecked = false;
            }
        }
        if (this.filterNoChecked) {
            this.filterDataIdxSet = new Set();
        } else {
            this.filterDataIdxSet = this.createViewDataIdxSet(this.filterAllCheckArray);
        }
    }
    createAllCheckArray(){
	    let allcheckary = [];
	    allcheckary.push([]);
	    for (let tableidx = 1; tableidx < this.filterArray.length; tableidx++ ) {
	        let chkary = [];
	        for (let uniqidx = 0; uniqidx < this.filterArray[tableidx].length; uniqidx++ ) {
	            let uniqitem = this.filterArray[tableidx][uniqidx];
	            if ( uniqitem.checked ) {
		 		    chkary.push({uniqidx: uniqitem.uniqidx, dataidx: uniqitem.dataidx, checked: uniqitem.checked})
                }
            }
	        allcheckary.push( chkary );
        }
	    return allcheckary;
	}

    createViewDataIdxSet(allcheckary) {
		//チェックがない列なので対応するアイテムのみを表示
		// まず全列でチェックされている値のdataidxだけの配列を作成する
		let alldataidxary = [];
		let coldataidxs = [];

		for (let tblidx = 1; tblidx < allcheckary.length; tblidx++) {
            let coldataidx = [];
            for (let checkedidx = 0; checkedidx < allcheckary[tblidx].length; checkedidx++) {
                let ary = allcheckary[tblidx][checkedidx].dataidx;
                alldataidxary = alldataidxary.concat(ary);
                coldataidx = coldataidx.concat(ary);
            }
            coldataidx = coldataidx.sort();
            coldataidxs.push( coldataidx );
        }

		alldataidxary = this.uniqArray( alldataidxary );
		alldataidxary = alldataidxary.sort();
		let nowidx = new Array(coldataidxs.length);
		nowidx.fill(0);


		// 全ての列に含まれるdataidxを配列で求める
		let allin_dataidx = [];
		let flg = false;
        for ( let uniqidx = 0; uniqidx < alldataidxary.length; uniqidx++ ) {
            let colidx = 0;
            let while_continue = true;
            while (while_continue) {
                let lpmax = coldataidxs[colidx].length;
                if ( lpmax > 0 ) {
                    let iii = nowidx[colidx];
                    flg = false;
                    while ( iii < lpmax ) {
                        if ( coldataidxs[colidx][iii] === alldataidxary[uniqidx] ) {
                            nowidx[colidx] = iii;
                            iii = lpmax + 888;
                            flg = true;
                        }
                        iii++;
                    }
                    if ( flg === false ) {
                        while_continue = false;
                    }
                }
				colidx++;
				if (colidx >= coldataidxs.length) {
				    while_continue = false;
                }
            }
            if (flg) {
                allin_dataidx.push( alldataidxary[uniqidx] )
            }
        }
		let set_allin_dataidx = new Set(allin_dataidx);
        return set_allin_dataidx;
    }
    /** -------------------------------------------
     * 描画
     * 実際の描画の各ステップ
     */
    initialize() {
        this.writeCSS();
	    //Set CheckTR Default
        let filtercnt = 1;
        if ( this.dataObj.meta.has_header ) {
            this.dataObj.body.header.forEach( (head,idx) => {
                if ( ! head.isHide ) {
                    filtercnt++;
                }
            });
        }
        this.checkTRNowBlocks = [filtercnt];
        for (let tableidx = 1; tableidx < filtercnt; tableidx++) {
            this.checkTRNowBlocks[tableidx] = { divheight:100, scrollpos:0, t_redrawpos:0, b_redrawpos:0, trinblock:0, blockcount:10, startidx:0, endidx:0, sort:'none',is_drawing:false };
        }
        this.globalVisibleDone = false;
        this.globalFilterDone = false;

        // this.setDataObj();
        // if (! this.globalVisibleDone) {
        //     this.generateVisibleArray();
        // }
        // if (this.visibleSort) {
        //     this.sortVisibleArray(this.visibleSort.index, this.visibleSort.order);
        // }
        // if (! this.globalFilterDone) {
        //     this.generateFilterArray();
        // }
    }
    updateData() {
        this.setDataObj();
            if (!this.globalVisibleDone) {
                this.generateVisibleArray();
            }
            if (this.visibleSort) {
                this.sortVisibleArray(this.visibleSort.index, this.visibleSort.order);
            }
            if (!this.globalFilterDone) {
                this.generateFilterArray();
            }
            this.generateFilterDataIdxSet();
    }

    generateTable() {
        this.initialize();
        this.divtag = document.getElementById(this.targetID);
        if (qahm !== undefined) {
            this.qahmobj = qahm;
        }
        let prefix = this.prefix;

        if (this.rawDataArray.length > 0 ) {
            // start
            const allfunc = async function () {
                this.updateData();
                if (this.dataObj.body.row.length > 0) {
                    //まずDataTRArrayを作る
                    await pBar.paintProgBar( 'create data array',10 );
                    const createdata = async function() {this.createDataTRArray();}.bind(this);
                    await createdata();

                    await pBar.paintProgBar( 'create filter and header',20 );
                    let trHTML = this.dataTRArray[0];
                    let headerHTML = '';
                    headerHTML += this.createQAFilter();
                    this.divtag.insertAdjacentHTML('afterbegin', headerHTML);


                    //フィルターが存在するならCheckTRArray配列を作っていく
                    const createcheck = async function () {
                        for (let tableidx = 1; tableidx < this.filterArray.length; tableidx++) {
                            if (typeof this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter !== 'undefined') {
                                if (this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter) {
                                    this.createCheckTRArray(tableidx);
                                    this.redrawCheckTRBlocks(tableidx, true);

                                } else {
                                    let mkdummy = 1;
                                }
                            } else {
                                this.createCheckTRArray(tableidx);
                                this.redrawCheckTRBlocks(tableidx, true);
                            }
                        }
                    }.bind(this);
                    await pBar.paintProgBar( 'create checkbox',40 );
                    await createcheck();
                    await pBar.paintProgBar( 'draw items',70 );
                    this.headerTableByID = document.getElementById(`${prefix}js_headerTable`);
                    this.filtertr = document.getElementById(`${prefix}js_filtertTR`);
                    let tbcontroler = this.createTbControler();
                    this.divtag.insertAdjacentHTML('afterbegin', tbcontroler);
                    this.tbControlerByID = document.getElementById(`${prefix}js_tbControler`);

                    const colfunc = async function(){
                        let colGroup = this.createColGroup()
                        this.divtag.insertAdjacentHTML('beforeend', `<div id="${prefix}js_dataViewer" class="bl_dataViewer"><div><table class="bl_jsTb bl_dataViewerTable el_fadein" id="${prefix}js_dataTable">${colGroup}<tbody id="${prefix}js_dataTbody">${trHTML}</tbody></table><div id="${prefix}bl_overlayReload" class="noReload"><div id="${prefix}bl_overlayInnner"><button id="${prefix}btn_tableReload" class="overlayEvent" type="button">Reload</button></div></div></div>`);
                        }.bind(this);
                    await colfunc();
                    this.dataTableByID = document.getElementById(`${prefix}js_dataTable`);

                    const redrawTR = async function() {this.redrawTRBlocks(true)}.bind(this);
                    const setevents = async function(){this.setAllEvents();this.setCheckboxEvents();}.bind(this);
                    const showhide  = async function() {
                        this.showVisibleSort();
                        this.hideAllFilterBoxes();
                    }.bind(this);
                    await pBar.paintProgBar( 'draw data',80 );
                    await redrawTR();
                    await pBar.paintProgBar( 'set events',90 );
                    await setevents();
                    await pBar.paintProgBar( 'show hide items',95 );
                    await showhide();
                    if (typeof this.drawTableComplete === 'function') {
                        this.drawTableComplete();
                    }
                    pBar.removeProgBar();
                }
            }.bind(this);
            // if ( this.qahmobj.setProgressBar !== undefined ) {this.qahmobj.setProgressBar( 10, 'create list' );}
            let pBar = new drawProgBar(this.progBarMode, this.progBarDiv, 'draw Table');
            pBar.readyProgBarHtml();
			
			// imai add table scale create start
			// this.tableScaleBtnDivにボタンを追加
			let tableScaleBtnDiv = document.getElementById(this.tableScaleBtnDiv);
			if ( tableScaleBtnDiv ) {
				tableScaleBtnDiv.classList.add('sc_tableScaleBlock');
				let tableScaleBtn = document.createElement('div');
				tableScaleBtn.id = `${prefix}js_tableScaleBtn`;
				tableScaleBtn.classList.add('sc_tableScaleBtn');
				tableScaleBtn.innerHTML = '<div class="sc_tableScaleDown"></div>';
				tableScaleBtnDiv.appendChild(tableScaleBtn);
				tableScaleBtn.addEventListener('click', () => {
					const dataViewer = document.getElementById(`${prefix}js_dataViewer`);
					if (!dataViewer) {
						console.error(`Element with ID "${prefix}js_dataViewer" not found.`);
						return;
					}
					dataViewer.style.transition = "height 0.5s";
					let height = parseInt( dataViewer.style.height );
					if ( ! height ) {
						height = this.dataTRNowBlocks.divheight;
					}
					if ( height < this.dataTRNowBlocks.divheight + 300 ) {
						dataViewer.style.height = (this.dataTRNowBlocks.divheight + 300) + "px";
					} else {
						dataViewer.style.height = this.dataTRNowBlocks.divheight + "px";
					}
					// transition終了後にスタイルを削除
					const transitionEndHandler = () => {
						dataViewer.style.transition = "";
						dataViewer.removeEventListener("transitionend", transitionEndHandler);
					};
					// transitionend イベントのリスナーを追加
					dataViewer.addEventListener("transitionend", transitionEndHandler);

					//this.tableScaleBtnClick();
				});
			}
			// imai add table scale create end

            allfunc();
        } else {
            let headerHTML = this.createQAFilter();
            this.divtag.insertAdjacentHTML('afterbegin', headerHTML);
            this.headerTableByID = document.getElementById(`${prefix}js_headerTable`);
            this.filtertr = document.getElementById(`${prefix}js_filtertTR`);
            let tbcontroler = this.createTbControler();
            this.divtag.insertAdjacentHTML('afterbegin', tbcontroler);
            this.tbControlerByID = document.getElementById(`${prefix}js_tbControler`);

            let colGroup = this.createColGroup();
            let trGroup = this.createEmptyTRGroup();
            this.divtag.insertAdjacentHTML('beforeend', `<div id="${prefix}js_dataViewer" class="bl_dataViewer"><table class="bl_jsTb bl_dataViewerTable el_fadein" id="${prefix}js_dataTable">${colGroup}<tbody id="${prefix}js_dataTbody">${trGroup}</tbody></table><div id="${prefix}bl_overlayReload" class="noReload"><div id="${prefix}bl_overlayInnner"><button id="${prefix}btn_tableReload" class="overlayEvent" type="button">Reload</button></div></div></div>`);
            this.dataTableByID = document.getElementById(`${prefix}js_dataTable`);
            this.setAllEvents();
            this.hideAllFilterBoxes();
        }
    }
    updateTable($is_resetFilter = false) {
        // if (this.rawDataArray.length > 0 ) {
            this.globalVisibleDone = false;
            this.globalFilterDone = false;
            if ($is_resetFilter) {
                this.clearAllCheck();
                this.clearActiveAllFilterBoxes();
                this.backToDfltGrf = true;
                this.updateData();
                this.clearDateFilter();
                this.redrawTable();
                this.hideAllFilterBoxes();
            }else{
                this.updateData();
                this.redrawTable();
            }
        // }
        // else {
        //     this.globalVisibleDone = false;
        //     this.globalFilterDone = false;
        //     if ($is_resetFilter) {
        //         this.clearAllCheck();
        //         this.clearActiveAllFilterBoxes();
        //         this.backToDfltGrf = true;
        //         this.updateData();
        //         this.redrawTable();
        //         this.hideAllFilterBoxes();
        //     }else{
        //         this.updateData();
        //         this.redrawTable();
        //     }
        // }
    }
    redrawTable(){
        if (this.dataObj.body.row.length > 0 ) {
            let prefix = this.prefix;

            //まずDataTRArrayを作る
            const cdta = async function(){this.createDataTRArray();}.bind(this);
            const rdtb = async function(){this.redrawTRBlocks(true);}.bind(this);
            const ccta = async function() {
                for (let tableidx = 1; tableidx < this.filterArray.length; tableidx++) {
                    if (typeof this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter !== 'undefined') {
                        if (this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter) {
                            this.createCheckTRArray(tableidx);
                            this.redrawCheckTRBlocks(tableidx, true);
                        }else{
                            let mkdummy = 1;
                        }
                    }else{
                        let is_serach = false;
                        let searchitemid = `${this.prefix}js_searchBox-${tableidx}`;
                        let searchitem = document.getElementById(searchitemid);
                        if (searchitem.value) {
                            is_serach = true;
                        }
                        this.createCheckTRArray(tableidx, is_serach);
                        this.redrawCheckTRBlocks(tableidx, true);
                    }
                }
                this.setCheckboxEvents();
            }.bind(this);

            const refreshfunc = async function() {
                this.showVisibleSort();
                //this.showFilteredTh();
                this.showOrHideFilterBoxes();
                this.refreshTbControler();
            }.bind(this);


            const redraw = async function() {
                await pBar.paintProgBar( 'Create Data Viewer',5 );
                await cdta();
                await pBar.paintProgBar( 'Redraw Data Viewer',20 );
                await rdtb();
                await pBar.paintProgBar( 'Create Filter Viewer',30 );
                await ccta();
                await pBar.paintProgBar( 'refresh calclate',80 );
                await refreshfunc();
                pBar.removeProgBar();
            }
            let pBar = new drawProgBar(this.progBarMode, this.progBarDiv, 'redraw Table');
            pBar.readyProgBarHtml();
            redraw();

            if (typeof this.drawTableComplete === 'function') {
                this.drawTableComplete();
            }
        } else {
            let tableidx = 1;
            if ( this.dataObj.meta.has_header ) {
                this.dataObj.body.header.forEach( (head,idx) => {
                    if ( ! head.isHide ) {
                        if ( head.hasFilter !== false ) {
                            this.clearCheckTRBlocks(tableidx);
                        }
                        tableidx++;
                    }
                });
            }
            this.clearTRBlocks();
            this.refreshTbControler();
        }
    }
    refreshTbControler() {
        let calcResult = this.createCalcResult();
        let calcresult = document.getElementById(`${this.prefix}js_calcResult`);
        calcresult.innerHTML = '';
        calcresult.innerHTML = `${calcResult}`;
        let datacount = document.getElementById(`${this.prefix}js_dataCount`);
        datacount.innerHTML = '';
        datacount.innerHTML = `(${this.visibleCount}/${this.visibleArray.length})`;

        //mkdummy
        let divtag = document.getElementById(this.targetID);

    }
    errorReload( cbfunc ) {
        // オーバレイを開閉する関数
        let prefix = this.prefix;
        const overlay = document.getElementById(`${prefix}bl_overlayReload`);
        if (overlay) {
            overlay.classList.add('errorReload');
        }

        const reloadBtn = document.getElementById(`${prefix}btn_tableReload`);
        if (reloadBtn) {
            reloadBtn.innerText = 'Reload';
            const cb = function () {
                reloadBtn.innerText = 'Connect...';
                cbfunc();
            };
            reloadBtn.addEventListener('click', cb, false);
        }
    }
    clearReload() {
        let prefix = this.prefix;
        // オーバレイを開閉する関数
        const overlay = document.getElementById(`${prefix}bl_overlayReload`);
        overlay.classList.remove(`${prefix}errorReload`);

        const reloadBtn = document.getElementById(`${prefix}btn_tableReload`);
        reloadBtn.innerText = 'Reload';
    }


	/** -------------------------------------------
	 * 実際の描画
	 * 各HTMLパーツの作成
	 */

    // ----------
    // Header
    // ----------
	createColGroup() {
	    let prefix = this.prefix;
        //col group
		let allcol = '';
        if ( this.dataObj.meta.has_header ) {
            allcol = this.dataObj.body.header.reduce( ( coltag, head, idx ) => {
                if ( ! head.isHide ) {
				    let colParam = '';
				    if ( typeof this.dataObj.body.header[idx].colParm !== 'undefined' ) {
				    	colParam = this.dataObj.body.header[idx].colParm;
					}
                    return coltag + `<col ${colParam}>`;
                } else {
                    return coltag;
                }
            }, '');
        }
		let colgroup = `<colgroup>${allcol}</colgroup>`;
        return colgroup;
    }

    // ----------
    // Data TR
    // ----------
	createEmptyTRGroup() {
	    let prefix = this.prefix;
        //col group
		let alltr = '';
        if ( this.dataObj.meta.has_header ) {
            alltr = this.dataObj.body.header.reduce( ( trtag, head, idx ) => {
                if ( ! head.isHide ) {
				    let tdParam = '<td>';
				    if ( typeof this.dataObj.body.header[idx].tdParm !== 'undefined' ) {
				    	tdParam = this.dataObj.body.header[idx].tdParm;
						tdParm = `<td ${tdParm}>`;
					}
                    return trtag + tdParam + '&nbsp;</td>';
                } else {
                    return trtag;
                }
            }, '');
        }
		let trgroup = `<tr>${alltr}</tr>`;
        return trgroup;
    }
    
    
    // ----------
    // Filter
    // ----------
	createCheckTRArray ( tableidx , is_show_check = false) {

    	//checkboxの配列をクリア
        if (this.checkTRArray.length === this.filterArray.length) {
            //作成済みをクリア
            this.checkTRArray[tableidx] = [];
        }else if (this.checkTRArray.length === 0) {
            //未作成なので作成してクリア
            this.checkTRArray = [this.filterArray.length];
            this.checkTRArray[tableidx] = [];
        }else{
            //作成済みなので該当だけクリア
            this.checkTRArray[tableidx] = [];
        }
		// チェックがある列ならばチェックされたアイテムを先に表示し、その後その他アイテムを表示する。なければチェックをすべて確認し、対応するアイテムのみを表示
		let checkidx      = 0;
		let otheritem     = [];
		let prefix = this.prefix;
		if ( this.filterAllCheckArray[ tableidx ].length > 0 ) {
			// チェックがある列ならばチェックありとなしをわけてアイテム保存
            //mkdummy
            for (let itemidx = 0; itemidx < this.filterArray[tableidx].length; itemidx++) {
			    let uniqdata = this.filterArray[tableidx][itemidx];
			    let hideitem = '';
			    let newitem  = this.createItemValue(uniqdata.uniqitem, tableidx, uniqdata.dataidx );
			    let newlabel = this.createItemLabel(uniqdata.uniqitem, tableidx, uniqdata.dataidx );
			    if (newitem === null) {
			        newitem = '';
                }
                let is_show = true;
			    if (is_show_check) {
			        is_show = this.filterCheckItem(tableidx, newitem);
                }
			    if (!is_show) {
			        continue;
                }
                const checkId = 'qa_js_filterText_' + tableidx + '_' + uniqdata.uniqidx;
                let datacount = uniqdata.dataidx.length;
                let dispcount = datacount.toString();

                if (uniqdata.checked) {
                    this.checkTRArray[tableidx][checkidx] = {html:`<tr><td class="el_itemTableTDL"${hideitem}><div class="el_uniqitem ${prefix}js_uniqItem"><input type="checkbox" id="${checkId}" data-tableidx="${tableidx}" data-uniqidx="${uniqdata.uniqidx}" value="${newitem}" checked><label for="${checkId}">${newlabel}</label></div></td><td class="el_itemTableTDR el_checkboxDataText"${hideitem}>${dispcount}</td></tr>`,sum:datacount,is_show:is_show,uniqidx:uniqdata.uniqidx};
                    checkidx++;
                } else {
                    otheritem.push({html:`<tr><td class="el_itemTableTDL"${hideitem}><div class="el_uniqitem ${prefix}js_uniqItem"><input type="checkbox" id="${checkId}" data-tableidx="${tableidx}" data-uniqidx="${uniqdata.uniqidx}" value="${newitem}"><label for="${checkId}">${newlabel}</label></div></td><td class="el_itemTableTDR el_checkboxDataText"${hideitem}>${dispcount}</td></tr>`,sum:datacount,is_show:is_show,uniqidx:uniqdata.uniqidx});
                }
			}
		} else {
			for (let itemidx = 0; itemidx < this.filterArray[tableidx].length; itemidx++) {
                let uniqdata = this.filterArray[tableidx][itemidx];
                let hideitem = '';
                let newitem = this.createItemValue(uniqdata.uniqitem, tableidx, uniqdata.dataidx);
                let newlabel = this.createItemLabel(uniqdata.uniqitem, tableidx, uniqdata.dataidx);
			    if (newitem === null) {
			        newitem = '';
                }
                let is_show = true;
			    if (is_show_check) {
			        is_show = this.filterCheckItem(tableidx, newitem);
                }
			    if (!is_show) {
                    continue;
                }
			    let datacount = uniqdata.dataidx.length;
                let dispcount = datacount.toString();
                const checkId = 'qa_js_filterText_' + tableidx + '_' + uniqdata.uniqidx;
                if (this.filterNoChecked) {
                    //問答無用で追加
                   otheritem.push({html:`<tr><td class="el_itemTableTDL"${hideitem}><div class="el_uniqitem ${prefix}js_uniqItem"><input type="checkbox" id="${checkId}" data-tableidx="${tableidx}" data-uniqidx="${uniqdata.uniqidx}" value="${newitem}"><label for="${checkId}">${newlabel}</label></div></td><td class="el_itemTableTDR el_checkboxDataText"${hideitem}>${dispcount}</td></tr>`,sum:datacount,is_show:is_show,uniqidx:uniqdata.uniqidx});
                } else {
                    // dataidxが重なるものだけ追加
                    dispcount = '-';
                    let isin = uniqdata.dataidx.some(dataidx => {
                        if (this.filterDataIdxSet.has(dataidx)) {
                            return true;
                        }
                    });
                    if (isin) {
                        otheritem.push({html:`<tr><td class="el_itemTableTDL"${hideitem}><div class="el_uniqitem ${prefix}js_uniqItem"><input type="checkbox" id="${checkId}" data-tableidx="${tableidx}" data-uniqidx="${uniqdata.uniqidx}" value="${newitem}"><label for="${checkId}">${newlabel}</label></div></td><td class="el_itemTableTDR el_checkboxDataText"${hideitem}>${dispcount}</td></tr>`,sum:datacount,is_show:is_show,uniqidx:uniqdata.uniqidx});
                    }
                }
            }
		}
		 //最後に今回の配列（otheritem）を並び替えて、checkTRArrayにセットする
		if (otheritem.length) {
		    let sortfunc = '';
		    switch (this.checkTRNowBlocks[tableidx].sort) {
                case 'asc':
        		    otheritem.sort( (a,b) => { if(a.sum<b.sum){return -1}if(a.sum>b.sum){return 1}if(a.sum===b.sum){return 0}})
                    break;

                case 'dsc':
        		    otheritem.sort( (a,b) => { if(a.sum<b.sum){return 1}if(a.sum>b.sum){return -1}if(a.sum===b.sum){return 0}})
                    break;

                case 'none':
                default:
                    break;
            }
		    this.checkTRArray[tableidx] = this.checkTRArray[tableidx].concat(otheritem);
        }
	}
    createItemValue( uniqitem, tableidx, dataidxs ) {
	    let header = this.dataObj.body.header[this.idxTable2Header(tableidx)];
	    if ( typeof header.filterAddIndex !== 'undefined' ) {
			let addidx = header.filterAddIndex;
			dataidxs.forEach( dataidx => {
			    uniqitem += this.dataObj.body.row[dataidx][addidx];
			})
		}
		return uniqitem;
	}
    createItemLabel( uniqitem, tableidx, dataidxs ) {
	    let header = this.dataObj.body.header[this.idxTable2Header(tableidx)];
		if ( typeof header.format !== 'undefined' ) {
            if (typeof header.itemformat !== 'undefined'){
                uniqitem = header.itemformat(uniqitem);
            }else {
                uniqitem = header.format(uniqitem);
            }
		}

	    if ( typeof header.filterAddIndex !== 'undefined' ) {
			let addidx = header.filterAddIndex;
			dataidxs.forEach( dataidx => {
			    uniqitem += this.dataObj.body.row[dataidx][addidx];
			})
		}
		return uniqitem;
	}

	filterCheckItem( tableidx, uniqitem, newitem ) {
	    let searchbox = '';
	    let id = `${this.prefix}js_searchBox-${tableidx}`;
	    let nowobj = document.getElementById(id);
	    let nowval = '';
	    if (nowobj) {
	    	nowval = nowobj.value;
		}
		if ( nowval === '') {
	        //フィルターがセットされていないのでそのままスルーで表示
	        return true;
        }
		let selectbox = '';
	    let selected = '';
	    let option = '';
	    let ret = true;
		switch (this.dataObj.body.header[this.idxTable2Header( tableidx )].type) {
			case 'number':
			case 'currency':
			case 'second':
			    selectbox = document.getElementById(`${id}-1`);
			    if ( selectbox ) {
			        let optval = selectbox.value;
			        switch (optval) {
						case 'equal':
							ret = ( Number(nowval) === Number(uniqitem )) ? true : false;
						    break;

						case 'lt':
							ret = ( Number(nowval) >= Number(uniqitem )) ? true : false;
						    break;

						case 'gt':
							ret = ( Number(nowval) <= Number(uniqitem )) ? true : false;
						    break;

						case 'none':
						default:
						    ret = false;
						    break;
					}
				}
				break;

			case 'datetime':
				let nowtim  = new Date();
				let nowdate = this.getDateValue( nowtim );
				let nowtime = this.getTimeValue( nowtim );
				let oldtim  = this.getOlderDateObj( tableidx );
				let olddate = this.getDateValue( oldtim );
				let oldtime = '00:00:00';

				if ( nowval !== '') {
				    olddate = nowval;
				}
				let oldtimeobj = document.getElementById(`${id}-1`);
				if ( oldtimeobj ) {
				    if ( oldtimeobj.value !== '') {
                        oldtime = oldtimeobj.value;
                    }
				}
				let nowdateobj = document.getElementById(`${id}-2`);
				if ( nowdateobj ) {
				    if ( nowdateobj.value !== '') {
						nowdate = nowdateobj.value;
					}
				}
				let nowtimeobj = document.getElementById(`${id}-3`);
				if ( nowtimeobj ) {
				    if ( nowtimeobj.value !== '') {
                        nowtime = nowtimeobj.value;
                    }
				}
				let fromdate = Date.parse( olddate + 'T' + oldtime );
				let todate = Date.parse( nowdate + 'T' + nowtime );
				ret =  ( fromdate <= Date.parse( uniqitem ) && todate >= Date.parse( uniqitem ) ) ? true : false;
				break;

			case 'unixtime':
			    //分までしか表示されないので1分進める
				let nowtimux = this.getNewerUnixtime( tableidx )+60;
				let nowtimx  = new Date(nowtimux*1000);
				let nowdatex = this.getDateValue( nowtimx );
				let nowtimex = this.getTimeValue( nowtimx );
				let oldtimux = this.getOlderUnixtime( tableidx );
				let oldtimx  = new Date(1000 * oldtimux);
				let olddatex = this.getDateValue( oldtimx );
				let oldtimex = '00:00:00';

				if ( nowval !== '') {
				    olddatex = nowval;
				}
				let oldtimeobjx = document.getElementById(`${id}-1`);
				if ( oldtimeobjx ) {
				    if ( oldtimeobjx.value !== '') {
                        oldtimex = oldtimeobjx.value;
                    }
				}
				let nowdateobjx = document.getElementById(`${id}-2`);
				if ( nowdateobjx ) {
				    if ( nowdateobjx.value !== '') {
						nowdatex = nowdateobjx.value;
					}
				}
				let nowtimeobjx = document.getElementById(`${id}-3`);
				if ( nowtimeobjx ) {
				    if ( nowtimeobjx.value !== '') {
                        nowtimex = nowtimeobjx.value;
                    }
				}
				let fromdatex = Date.parse( olddatex + 'T' + oldtimex );
				let todatex = Date.parse( nowdatex + 'T' + nowtimex );
				ret =  ( fromdatex <= (uniqitem*1000) && todatex >= (uniqitem*1000) ) ? true : false;
				break;


			case 'string' :
			default:
			    selectbox = document.getElementById(`${id}-1`);
			    if ( selectbox && nowval ) {
			        let optval = selectbox.value;
			        let luniq  = uniqitem.toLowerCase();
			        let lnowv  = nowval.toLowerCase();
			        switch (optval) {
						case 'include':
							ret = ( luniq.indexOf(lnowv) >= 0 ) ? true : false;
						    break;

						case 'exclude':
							ret = ( luniq.indexOf(lnowv) < 0 ) ? true : false;
						    break;

						default:
						    ret = false;
						    break;
					}
				}
				break;
		}
		return ret;
	}

	createQAFilter() {
	    let prefix = this.prefix;
        let headerhtml = `<div id="${prefix}js_headerViewer"><table class="bl_jsTb" id="${prefix}js_headerTable"></div>`;
        let filtertd = '';
        let trtag = '';
		// 各列で集計できるように転置
        if (this.visibleArray.length > 0) {
            let revary = this.reverseArray( this.visibleArray );
            filtertd = revary.reduce( ( allfilter, col, tableidx ) => {
                if ( tableidx > this.visible2DataIdx ) {
                    let searchbox = this.createSearchbox( tableidx );
                    let boxactiveclass = '';
                    let trobj = this.filtertr;
                    if ( trobj ) {
                        if (trobj.childNodes[tableidx -1].classList.contains(`${this.prefix}is_filterActive`)) {
                            boxactiveclass = `${this.prefix}is_filterActive`;
                        }
                    }
                    let filtertag = '';
                    // let uniqary = this.uniqArray( col );

                    if ( this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter === false) {
                        filtertag = `<td class="bl_filterTD ${boxactiveclass}" data-tableidx="${tableidx}"></td>`;
                    } else {
                        filtertag = `<td class="bl_filterTD ${boxactiveclass}" data-tableidx="${tableidx}">
                                        <div class="bl_filterContainer">
                                            <div class="bl_filterUnit">
                                                <div class="el_closeBtn ${prefix}js_closeBtn" data-tableidx="${tableidx}">${this.svg.close}</div>
                                                <form onsubmit="return false;">
                                                    <div class="bl_sortControl">
                                                        <h4 class="el_filterTitle">${this.wordsInTable['tjs_sort']}</h4>
                                                        <span class="${prefix}linkasc" id="js_linkasc_${tableidx}" style="text-decoration: none;color: #206CFF;font-size: 90%;cursor: pointer;">${this.svg.asc} 1-9(A→Z)</span><br>
                                                        <span class="${prefix}linkdsc" id="js_linkdsc_${tableidx}" style="text-decoration: none;color: #206CFF;font-size: 90%;cursor: pointer;">${this.svg.dsc} 9-1(Z→A)</span>
                                                    </div>
                                                    <div class="${prefix}bl_filterControl">
                                                        <h4 class="el_filterTitle">${this.wordsInTable['tjs_filter']}</h4>
                                                        <div class="bl_SearchBoxes ${prefix}js_SearchBoxes" id="${prefix}js_SearchBoxes-${tableidx}">
                                                            ${searchbox}
                                                        </div>
                                                        <div class="bl_uniqItems ${prefix}js_uniqItems" id="${prefix}js_uniqItems-${tableidx}">
                                                            <table class="bl_itemTable" id="${prefix}js_uniqItemsTable-${tableidx}">
                                                            <colgroup>
                                                                <col style="width: 80%">
                                                                <col style="width: 20%">
                                                            <thead>
                                                                <td class="el_itemTableTDL"><div style="text-decoration: none;color: #206CFF;font-size: 8px;cursor: pointer;"><input type="checkbox" id="${prefix}js_allChkLink-${tableidx}" data-tableidx="${tableidx}" class="${prefix}js_allChkLink ${prefix}js_allCheckbox"><label for="${prefix}js_allChkLink-${tableidx}">${this.wordsInTable['tjs_select_all']}</label></div></td>
                                                                <td class="el_itemTableTDR el_dataSortText el_checkboxDataText" id="${prefix}js_checkboxHeaderText-${tableidx}">▼</td>
                                                            </thead>
                                                            <tbody id="${prefix}js_checkTbody-${tableidx}" class="bl_itemTableTbody"></tbody></table>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        </td>`;

                    }
                    return allfilter + filtertag;
                } else {
                    return '';
                }
            }, '');
        } else {
            let prefix = this.prefix;
            if ( this.dataObj.meta.has_header ) {
                for ( let idx = 0; idx < this.dataObj.body.header.length; idx++ ) {
                    let head = this.dataObj.body.header[idx];
                    if ( ! head.isHide ) {
                        let filtertag = '';
                        let tableidx = this.idxHeader2Table(idx);
                        let searchbox = this.createSearchbox( tableidx );
                        if ( this.dataObj.body.header[idx].hasFilter === false) {
                            filtertag = `<td class="bl_filterTD" data-tableidx="${tableidx}"></td>`;
                        } else {
                            filtertag = `<td class="bl_filterTD" data-tableidx="${tableidx}">
                                            <div class="bl_filterContainer">
                                                <div class="bl_filterUnit">
                                                    <div class="el_closeBtn ${prefix}js_closeBtn" data-tableidx="${tableidx}">${this.svg.close}</div>
                                                    <form>
                                                        <div class="bl_sortControl">
                                                            <h4 class="el_filterTitle">${this.wordsInTable['tjs_sort']}</h4>
                                                            <span class="${prefix}linkasc" id="js_linkasc_${tableidx}" style="text-decoration: none;color: #206CFF;font-size: 90%;cursor: pointer;">${this.svg.asc} 1-9(A→Z)</span><br>
                                                            <span class="${prefix}linkdsc" id="js_linkdsc_${tableidx}" style="text-decoration: none;color: #206CFF;font-size: 90%;cursor: pointer;">${this.svg.dsc} 9-1(Z→A)</span>
                                                        </div>
                                                        <div class="${prefix}bl_filterControl">
                                                            <h4 class="el_filterTitle">${this.wordsInTable['tjs_filter']}</h4>
                                                            <div class="bl_SearchBoxes ${prefix}js_SearchBoxes" id="${prefix}js_SearchBoxes-${tableidx}">
                                                                ${searchbox}
                                                            </div>
                                                            <div class="bl_uniqItems ${prefix}js_uniqItems" id="${prefix}js_uniqItems-${tableidx}">
                                                                <table class="bl_itemTable" id="${prefix}js_uniqItemsTable-${tableidx}">
                                                                <colgroup>
                                                                    <col style="width: 80%">
                                                                    <col style="width: 20%">
                                                                <thead>
                                                                    <td class="el_itemTableTDL"><div style="text-decoration: none;color: #206CFF;font-size: 8px;cursor: pointer;"><input type="checkbox" id="${prefix}js_allChkLink-${tableidx}" data-tableidx="${tableidx}" class="${prefix}js_allChkLink ${prefix}js_allCheckbox"><label for="${prefix}js_allChkLink-${tableidx}">${this.wordsInTable['tjs_select_all']}</label></div></td>
                                                                    <td class="el_itemTableTDR el_dataSortText el_checkboxDataText" id="${prefix}js_checkboxHeaderText-${tableidx}">▼</td>
                                                                </thead>
                                                                <tbody id="${prefix}js_checkTbody-${tableidx}" class="bl_itemTableTbody"></tbody></table>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            </td>`;

                        }
                        trtag =  trtag + filtertag;
                    } else {
                        continue;
                    }
                }
            }
            filtertd = trtag;
        }

        // thead
        let thead = '';
        let allth = '';
        if ( this.dataObj.meta.has_header ) {
            allth = this.dataObj.body.header.reduce( ( th_html, head, idx ) => {
                if ( ! head.isHide ) {
                    let tableidx = this.idxHeader2Table(idx);
                    let thParam = '';
                    if ( typeof this.dataObj.body.header[idx].thParm !== 'undefined' ) {
                        thParam = this.dataObj.body.header[idx].thParm;
                    }

                    let spansvg = '';
                    spansvg = `<span id="${this.prefix}js_filterSvg-${tableidx}" class="el_filterSvg" data-tableidx="${tableidx}"></span>`;
                    if ( head.hasFilter !== false) {
                        let thfilterclass = `class="${this.prefix}is_filterd"`;
                        let allcheckary = this.createAllCheckArray();
                        if ( allcheckary.length > 1 ) {
                            spansvg = `<span id="${this.prefix}js_filterSvg-${tableidx}" class="el_filterSvg" data-tableidx="${tableidx}">${this.svgChangeColor(this.svg.sortdown,this.color.base)}</span>`;
                            if (allcheckary[tableidx].length > 0) {
                                if (allcheckary[tableidx][0].checked !== this.firstCheck ) {
                                    spansvg = `<span id="${this.prefix}js_filterSvg-${tableidx}" class="el_filterSvg" data-tableidx="${tableidx}">${this.svgChangeColor(this.svg.filtered, this.color.accent)}</span>`;
                                }
                            }
                        }
                    }
                    return th_html + `<th class="${this.prefix}js_filterTh" data-tableidx="${tableidx}" ${thParam}>${head.title} ${spansvg}</th>`;
                } else {
                    return th_html;
                }
            }, '');
        }
        thead = this.createColGroup();
        if ( this.thLabelHtml !== '' ) {
            thead = thead + '<tr>' + this.thLabelHtml + '</tr>';
        }
        thead = thead + '<tr>' + allth + '</tr>';
        thead = thead + `<tr id="${prefix}js_filtertTR">${filtertd}</tr>`;
        thead = thead + '</thead>';

        headerhtml = headerhtml + thead + '</table>';
		return headerhtml;
	}
    clearCheckTRBlocks( tableidx ) {
        let prefix = this.prefix;
        let tbody = document.getElementById(`${prefix}js_checkTbody-${tableidx}`);
	    tbody.innerHTML = '';
    }
    redrawCheckTRBlocks( tableidx, is_1sttime = false ){
	    this.checkTRNowBlocks[tableidx].is_drawing = true;
        // まずviewerのdivと高さをgetし、その中のtbodyをGETする
        let prefix = this.prefix;
        let div_viewer = document.getElementById(`${prefix}js_uniqItems-${tableidx}`);
        // let div_viewer = document.getElementById(`${prefix}js_checkTbody-${tableidx}`);

        let div_viewer_height = 0;
        if (div_viewer !== null) {
            div_viewer_height = this.getHight(div_viewer);
        }else{
            console.log(tableidx.toString() + 'redrawCheck null');
        }
        if (div_viewer_height === 0) {div_viewer_height = 100;}
        let tbody = document.getElementById(`${prefix}js_checkTbody-${tableidx}`);


        // 次に、今回のcheckTRArray（全てのチェック）の個数を数えておく。それがスクロールの最大値になる
        let alltrcount = 0;
        let showmaxidx = 0;
        for (let rowidx = 0; rowidx < this.checkTRArray[tableidx].length; rowidx++) {
            if (this.checkTRArray[tableidx][rowidx].is_show) {
                alltrcount++;
                showmaxidx = rowidx;
            }
        }
        // もし全カウントが0だったらクリアして、何もしなくてよい
        if (alltrcount === 0 ) {
            tbody.innerHTML = '';
            this.checkTRNowBlocks[tableidx].is_drawing = false;
            return;
        }

        // trノードの高さを求め、そこからTable全体の高さを求めておく
        let trelms = div_viewer.querySelectorAll('TR');
        let tr_height = 25;
        // if (typeof trelms[1] !== 'undefined') {
        //     tr_height = this.getHight(trelms[1]);
        //     if ( 25 < tr_height) {
        //         tr_height = 25;
        //     }
        // }
        // if (tr_height === 0) {tr_height = 25;}
        let tableheight = tr_height * alltrcount;

        // 今回viewerの中に表示できるtrの数を求めておく
        let show_tr_count = Math.floor(div_viewer_height / tr_height);
        this.checkTRNowBlocks[tableidx].trinblock = show_tr_count;


        // 現在のスクロール位置が全体の何％かを求め、現在表示中のcheckboxのインデックスを求めておく
        let nowpercent = this.checkTRNowBlocks[tableidx].scrollpos / tableheight;
        let now_tridx  = Math.floor( alltrcount * nowpercent );

        // -----
        // 準備が終わったので、具体的な表示用のtrタグを再構築する作業を開始
        // -----

        // まず、現在表示中のcheckboxのインデックスが範囲外なのかを判定する。
        // 現在表示中のcheckboxのインデックスの表示範囲を求める。表示範囲は、show_tr_count

        let now_btm_tridx = now_tridx - Math.floor( show_tr_count / 4);
        let now_top_tridx = now_tridx + Math.floor( show_tr_count / 4);

        let is_redraw = false;
        if ( now_btm_tridx < this.checkTRNowBlocks[tableidx].startidx ) {
            is_redraw = true;
        }
        if ( this.checkTRNowBlocks[tableidx].endidx < now_top_tridx ) {
            is_redraw = true;
        }
        if ( alltrcount <= tbody.childNodes.length -1) {
            is_redraw = false;
        } else {
            is_redraw = true;
        }

        let create_tr_count = show_tr_count * this.checkTRNowBlocks[tableidx].blockcount;
        if (is_1sttime) {
            this.checkTRNowBlocks[tableidx].endidx = this.checkTRNowBlocks[tableidx].startidx + create_tr_count;
            if ( this.checkTRArray[tableidx].length <= this.checkTRNowBlocks[tableidx].endidx) {
                this.checkTRNowBlocks[tableidx].endidx = this.checkTRArray[tableidx].length -1;
            }
            // 一旦表示をクリアし、書込開始
            tbody.innerHTML = '';
            for (let iii = this.checkTRNowBlocks[tableidx].startidx; iii <= this.checkTRNowBlocks[tableidx].endidx ; iii ++) {
                let trhtml = '';
                let tr = document.createElement('tr');
                trhtml = this.checkTRArray[tableidx][iii].html;
                tr.innerHTML = trhtml;
                tbody.appendChild(tr);
            }
            let endtr = document.createElement('tr');
            endtr.id = `checkendtr-${tableidx}`;
            tbody.appendChild(endtr);
            let end_h = tr_height * (alltrcount - this.checkTRNowBlocks[tableidx].endidx + 1);
            endtr.style.height = end_h.toString() + 'px';

            // redraw eventのためにセット
            this.checkTRNowBlocks[tableidx].t_redrawpos = 0;
            this.checkTRNowBlocks[tableidx].b_redrawpos = tr_height * show_tr_count * ( this.checkTRNowBlocks[tableidx].blockcount  - 2 );
            if ( end_h < this.checkTRNowBlocks[tableidx].b_redrawpos ) {
                this.checkTRNowBlocks[tableidx].b_redrawpos = end_h;
            }
        } else {
            if (is_redraw) {
                let new_startidx = now_tridx - (create_tr_count/2);
                if (new_startidx < 0) { new_startidx = 0; }
                let new_endidx = now_tridx + (create_tr_count/2);
                if ( showmaxidx <= new_endidx ) { new_endidx = showmaxidx; }

                this.checkTRNowBlocks[tableidx].startidx = new_startidx;
                this.checkTRNowBlocks[tableidx].endidx = new_endidx;

                // 一旦表示をクリアし、書込開始
                tbody.innerHTML = '';
                let firsttr = document.createElement('tr');
                tbody.appendChild(firsttr);
                //まず下駄をはかせる
                let start_h = tr_height * this.checkTRNowBlocks[tableidx].startidx;
                tbody.firstElementChild.style.height = start_h.toString() + 'px';
                //今回の表示範囲を描画
                let is_show_idx = 0;
                for (let iii = this.checkTRNowBlocks[tableidx].startidx; iii <= this.checkTRNowBlocks[tableidx].endidx ; iii ++) {
                    let trhtml = '';
                    let tr = document.createElement('tr');
                    trhtml = this.checkTRArray[tableidx][iii].html;
                    tr.innerHTML = trhtml;
                    tbody.appendChild(tr);
                }
                let endtr = document.createElement('tr');
                endtr.id = `checkendtr-${tableidx}`;
                tbody.appendChild(endtr);
                let end_h = tr_height * (alltrcount - this.checkTRNowBlocks[tableidx].endidx + 1);
                endtr.style.height = end_h.toString() + 'px';
                this.setCheckboxEvents();
                // redraw eventのためにセット
                this.checkTRNowBlocks[tableidx].t_redrawpos = start_h + tr_height * show_tr_count *  2;
                if ( end_h < this.checkTRNowBlocks[tableidx].t_redrawpos ) {
                    this.checkTRNowBlocks[tableidx].t_redrawpos = start_h;
                }
                this.checkTRNowBlocks[tableidx].b_redrawpos = start_h + tr_height * show_tr_count * ( this.checkTRNowBlocks[tableidx].blockcount  - 2 );
                if ( end_h < this.checkTRNowBlocks[tableidx].b_redrawpos ) {
                    this.checkTRNowBlocks[tableidx].b_redrawpos = end_h;
                }
            }
        }
        this.checkTRNowBlocks[tableidx].is_drawing = false;
    }

	createSearchbox ( tableidx ) {
	    let searchbox = '';
	    let id = `${this.prefix}js_searchBox-${tableidx}`;
	    let nowobj = document.getElementById(id);
	    let nowval = '';
	    if ( nowobj ) {
	    	nowval = nowobj.value;
		}
		let selectbox = '';
	    let selected = '';
	    let option = '';
	    let tani = '';
	    let datatype = this.dataObj.body.header[this.idxTable2Header( tableidx )].type;
		switch (datatype) {
			case 'currency':
			case 'second':
			case 'number':
			    selectbox = document.getElementById(`${id}-1`);
			    selected = '';
			    option = `
				<option value="equal" selected>=${this.wordsInTable['tjs_filter_equal']}</option>
				<option value="lt">&lt;=${this.wordsInTable['tjs_filter_lt']}</option>
				<option value="gt">&gt;=${this.wordsInTable['tjs_filter_gt']}</option>
			    `;
			    if ( selectbox ) {
			        let optval = selectbox.value;
			        option = option.replace(`${optval}"`, `${optval}" selected`);
				}
                switch (datatype) {
                    case 'currency':
                        tani = this.wordsInTable['tjs_filter_data_tani_currency'];
                        break;

                    case 'second':
                        tani = this.wordsInTable['tjs_filter_data_tani_second'];
                        break;
                }
				searchbox  = `
				<input class="${this.prefix}js_searchBox" type="number" id="${id}" value="${nowval}">${tani}<br>
				<select data-tableidx="${tableidx}" id="${id}-1">
				${option}
				</select>
				`;
				break;

			case 'datetime':

				let nowtim  = new Date();
				let nowdate = this.getDateValue( nowtim );
				let nowtime = this.getTimeValue( nowtim );
				let oldtim  = this.getOlderDateObj( tableidx );
				let olddate = this.getDateValue( oldtim );
				let oldtime = this.getTimeValue( oldtim );

				if ( nowval !== '') {
				    olddate = nowval;
				}
				let oldtimeobj = document.getElementById(`${id}-1`);
				if ( oldtimeobj ) {
				    if ( oldtimeobj.value !== '') {
                        oldtime = oldtimeobj.value;
                    }
				}
				let nowdateobj = document.getElementById(`${id}-2`);
				if ( nowdateobj ) {
				    if ( nowdateobj.value !== '') {
						nowdate = nowdateobj.value;
					}
				}
				let nowtimeobj = document.getElementById(`${id}-3`);
				if ( nowtimeobj ) {
				    if ( nowtimeobj.value !== '') {
                        nowtime = nowtimeobj.value;
                    }
				}

				//strnow = '2018-06-12T19:30';
				searchbox  = `<input class="${this.prefix}js_searchBox" type="date" id="${id}" value="${olddate}"><input class="${this.prefix}js_searchBox" type="time" id="${id}-1" value="${oldtime}"><br>
							  <input class="${this.prefix}js_searchBox" type="date" id="${id}-2" value="${nowdate}"><input class="${this.prefix}js_searchBox" type="time" id="${id}-3" value="${nowtime}">`;
				break;

			case 'unixtime':
			    //分までしか表示されないので1分進める
				let nowtimux = this.getNewerUnixtime( tableidx )+60;
				let nowtimx  = new Date(nowtimux*1000);
				let nowdatex = this.getDateValue( nowtimx );
				let nowtimex = this.getTimeValue( nowtimx );
				let oldtimux = this.getOlderUnixtime( tableidx );
				let oldtimx  = new Date(1000*oldtimux );
				let olddatex = this.getDateValue( oldtimx );
				let oldtimex = this.getTimeValue( oldtimx );

				if ( nowval !== '') {
				    olddatex = nowval;
				}
				let oldtimeobjx = document.getElementById(`${id}-1`);
				if ( oldtimeobjx ) {
				    if ( oldtimeobjx.value !== '') {
                        oldtimex = oldtimeobjx.value;
                    }
				}
				let nowdateobjx = document.getElementById(`${id}-2`);
				if ( nowdateobjx ) {
				    if ( nowdateobjx.value !== '') {
						nowdatex = nowdateobjx.value;
					}
				}
				let nowtimeobjx = document.getElementById(`${id}-3`);
				if ( nowtimeobjx ) {
				    if ( nowtimeobjx.value !== '') {
                        nowtimex = nowtimeobjx.value;
                    }
				}

				//strnow = '2018-06-12T19:30';
				searchbox  = `<input class="${this.prefix}js_searchBox" type="date" id="${id}" value="${olddatex}" max="${nowdatex}" min="${olddatex}"><input class="${this.prefix}js_searchBox" type="time" id="${id}-1" value="${oldtimex}"><br>
							  <input class="${this.prefix}js_searchBox" type="date" id="${id}-2" value="${nowdatex}" max="${nowdatex}" min="${olddatex}"><input class="${this.prefix}js_searchBox" type="time" id="${id}-3" value="${nowtimex}">`;
				break;

			case 'string' :
			default:
			    selectbox = document.getElementById(`${id}-1`);
			    selected = '';
			    option = `
				<option value="include" selected>${this.wordsInTable['tjs_include']}</option>
				<option value="exclude">${this.wordsInTable['tjs_not_include']}</option>
			    `;
			    if ( selectbox ) {
			        let optval = selectbox.value;
			        option = option.replace(`${optval}"`, `${optval}" selected`);
				}
				searchbox  = `<input class="${this.prefix}js_searchBox ${this.prefix}js_searchText" type="text" id="${id}" value="${nowval}" placeholder="${this.wordsInTable['tjs_word_for_filter']}"><br>
				<select data-tableidx="${tableidx}" id="${id}-1">
				${option}
				</select>`;
				break;
		}
		searchbox = searchbox + `&nbsp;<span class="el_searchbtn" id="${this.prefix}js_searchBtn-${tableidx}">${this.svg.search}</span>`;
		return searchbox;
	}



    // ----------
    // DataView
    // ----------
    createDataTRArray() {
	    let prefix = this.prefix;

        //まずクリアする
        this.visibleCount = 0;
        this.dataTRArray = [];
        this.graphArray = [];

        //TRs
        this.dataObj.body.header.forEach( (head, headidx) =>{
            if ( typeof head.calc !== 'undefined') {
                head.sumResult = 0
            }
        });
        let header_colmax = this.dataObj.body.header.length;
        let rowdata_colmax = this.visibleArray[0].length;

		// checkedDataArrayの初期化
		for (let colIdx = 0; colIdx < header_colmax; colIdx++ ) {
			if ( this.checkedDataArray[colIdx] === undefined ) {
				this.checkedDataArray[colIdx] = [];
			}
		}

        let dataidx_col = 0;
        for (let rowidx = 0; rowidx < this.visibleArray.length; rowidx++ ) {
            let now_dataidx = this.visibleArray[rowidx][dataidx_col];

            if (this.filterNoChecked) {
                this.visibleCount++;
                this.graphArray.push(this.dataObj.body.row[this.visibleArray[rowidx][dataidx_col]]);

                //sumResult
                for (let headidx = 0; headidx < header_colmax; headidx++ ) {
                    let head = this.dataObj.body.header[headidx];
                    if ( typeof head.calc !== 'undefined') {
                        head.sumResult = head.sumResult + Number(this.dataObj.body.row[now_dataidx][headidx]);
                    }
                }

                let alltd_html = '';
                // for (let colidx = 1; colidx < this.visibleArray[rowidx].length; colidx++ ) {
                for (let colidx = 1; colidx < rowdata_colmax; colidx++ ) {
                    let tdParm = '<td>';
                    if ( typeof this.dataObj.body.header[this.idxTable2Header(colidx)].tdParm !== 'undefined' ) {
                        tdParm = this.dataObj.body.header[this.idxTable2Header(colidx)].tdParm;
						tdParm = `<td ${tdParm}>`;
					}
                    alltd_html = alltd_html + tdParm + this.createTdHtml(this.visibleArray[rowidx][colidx], colidx, rowidx) + '</td>';
                }
                let trtag = `<tr id="${prefix}js_rowTR-${this.visibleArray[rowidx][dataidx_col]}">${alltd_html}</tr>`;
                this.dataTRArray.push(trtag);
            } else {
                if ( this.filterDataIdxSet.has(this.visibleArray[rowidx][dataidx_col]) ) {
                    this.visibleCount++;
                    this.graphArray.push(this.dataObj.body.row[this.visibleArray[rowidx][dataidx_col]]);

                    //sumResult
                    for (let headidx = 0; headidx < header_colmax; headidx++ ) {
                        let head = this.dataObj.body.header[headidx];
                        if ( typeof head.calc !== 'undefined') {
                            head.sumResult = head.sumResult + Number(this.dataObj.body.row[now_dataidx][headidx]);
                        }
                    }

                    let alltd_html = '';
                    // for (let colidx = 1; colidx < this.visibleArray[rowidx].length; colidx++ ) {
                    for (let colidx = 1; colidx < rowdata_colmax; colidx++ ) {
                        let tdParm = '<td>';
                        if ( typeof this.dataObj.body.header[this.idxTable2Header(colidx)].tdParm !== 'undefined' ) {
                            tdParm = this.dataObj.body.header[this.idxTable2Header(colidx)].tdParm;
							tdParm = `<td ${tdParm}>`;
						}
                        alltd_html = alltd_html + tdParm + this.createTdHtml(this.visibleArray[rowidx][colidx], colidx, rowidx) + '</td>';
                    }
                    let trtag = `<tr id="${prefix}js_rowTR-${this.visibleArray[rowidx][dataidx_col]}">${alltd_html}</tr>`;
                    this.dataTRArray.push(trtag);
                }
            }
        }
    }

	// imai add checkbox attribute end
	// HTMLを簡易に字句解析する
	tokenizeHTMLEasy(html, option={}) {
		const stack = [];

		let lastIndex = 0;
		const findTag = /<[!/?A-Za-z][^\t\n\f\r />]*([\t\n\f\r /]+[^\t\n\f\r /][^\t\n\f\r /=]*([\t\n\f\r ]*=[\t\n\f\r ]*("[^"]*"|'[^']*'|[^\t\n\f\r >]*))?)*[\t\n\f\r /]*>/g;
		for (let m; m=findTag.exec(html); ) {
			if (lastIndex < m.index) {
				let text = html.substring(lastIndex, m.index);
				if (option.trim) { text = text.trim(); }
				if (text.length > 0) { stack.push(text); }
			}
			lastIndex = findTag.lastIndex;

			let tag = m[0];
			if (option.trim) { tag = tag.trim(); }
			stack.push(tag);
		}
		return stack;
	}

	// dataTRArray内に収められている文字列が対象。checkboxを加工する
	processCheckboxAttribute( trhtml ) {
		let htmlAry = this.tokenizeHTMLEasy(trhtml);

		/*
		　※考え方
		　htmlAryには<tr>から</tr>まで使用されているタグごとに配列に格納される。
		　その中でも、<td>から</td>をひとつのブロックとして考え、tdInIdxAry配列に格納する。

		　<td></td>までの間にユーザーがhtmlタグを自主的に追加している可能性があるので、インデックス番号を決め打ちすることはできない
		　その事を考慮し、<td>から</td>までのインデックス番号をtdInIdxAry配列に格納していく
		　tdIdxAryにはひとつのtd開始からtd終了までのインデックス番号を入れている
		*/
		let tdInIdxAry = [];
		let tdInIdx    = 0;
		let isTdIn     = false;
		for (let htmlIdx = 0, htmlMax = htmlAry.length; htmlIdx < htmlMax; htmlIdx++ ) {
			let html = htmlAry[htmlIdx];
			if ( html.indexOf('<td') === 0 && html.lastIndexOf('>') === html.length - 1 ) {
				isTdIn = true;
				tdInIdxAry[tdInIdx] = [];
			} else if ( html.indexOf('</td>') === 0 ) {
				isTdIn = false;
				tdInIdx++;
			} else if ( isTdIn ) {
				tdInIdxAry[tdInIdx].push(htmlIdx);
			}
		}

		// add cheked
		let find    = false;
		for (let colIdx = 0, colMax = this.checkedDataArray.length; colIdx < colMax; colIdx++ ) {
			const colId = colIdx;

			for (let rowIdx = 0, rowMax = this.checkedDataArray[colIdx].length; rowIdx < rowMax; rowIdx++ ) {
				const rowId = this.checkedDataArray[colIdx][rowIdx];

				for (let tdInIdx = 0, tdInMax = tdInIdxAry[colId].length; tdInIdx < tdInMax; tdInIdx++ ) {
					const htmlIdx = tdInIdxAry[colId][tdInIdx];

					if ( htmlAry[htmlIdx].indexOf('<input') === 0 && htmlAry[htmlIdx].lastIndexOf('>') === htmlAry[htmlIdx].length - 1 ) {
						const matchColId = htmlAry[htmlIdx].match( 'data-col-id="(.*?)"' );
						const matchRowId = htmlAry[htmlIdx].match( 'data-row-id="(.*?)"' );
						if ( colId === Number( matchColId[1] ) && rowId === Number( matchRowId[1] ) ) {
							htmlAry[htmlIdx] = htmlAry[htmlIdx].replace('>', ' checked>');
							trhtml = htmlAry.join('');
							find   = true;
							break;
						}
					}
				}
				if ( find ) {
					break;
				}
			}
			if ( find ) {
				break;
			}
		}

		// add disabled
		if ( this.checkedColMaxArray.length > 0 ) {
			let find    = false;
			for ( let colIdx = 0, colMax = this.checkedColMaxArray.length; colIdx < colMax; colIdx++ ) {
				const colId   = colIdx;

				for (let tdInIdx = 0, tdInMax = tdInIdxAry[colId].length; tdInIdx < tdInMax; tdInIdx++ ) {
					const htmlIdx = tdInIdxAry[colId][tdInIdx];
					const checkedMax   = this.checkedColMaxArray[colId];
					if ( this.checkedDataArray[colId].length < checkedMax ) {
						continue;
					}

					if ( htmlAry[htmlIdx].indexOf('<input') === 0 &&
						htmlAry[htmlIdx].lastIndexOf('>') === htmlAry[htmlIdx].length - 1 &&
						htmlAry[htmlIdx].indexOf( ' checked>' ) === -1 )
					{
						htmlAry[htmlIdx] = htmlAry[htmlIdx].replace('>', ' disabled>');
						trhtml = htmlAry.join('');
						break;
					}
				}
				
				if ( find ) {
					break;
				}
			}
		}

		return trhtml;
	}
	// imai add checkbox attribute end

    createTdHtml( tddata, tableidx, vrowidx ) {
	    let drowidx = this.visibleArray[vrowidx][0];
	    let header = this.dataObj.body.header[this.idxTable2Header(tableidx)];
		let datahtml = tddata;
		if ( header.format ) {
	    	datahtml = header.format(tddata, tableidx, vrowidx, this.visibleArray);
		}
		let tdhtml = datahtml;
	    if ( header.tdHtml ) {
			let newhtml = header.tdHtml;
            let fmtstr = this.formatSpecifier;
            let strpos = [64];
            strpos[0] = 0;
            let iii = 0;
            let findpos = 0;
            let replace_str = '';
            let replace_idx = 0;
            let replace_idxstr = '';
            let while_continue = true;

			//search and replace
            while ( while_continue ) {
                findpos = newhtml.indexOf(fmtstr, strpos[iii]);
                if ( findpos >= 0 ) {
                    replace_str = replace_str + newhtml.slice(strpos[iii], findpos);
                    replace_idxstr = newhtml.slice(findpos + 2, findpos + 4);
                    if ( replace_idxstr === 'me' ) {
                        replace_str = replace_str + datahtml;
                    } else {
                        replace_idx = Number(replace_idxstr.slice(0,1)) * 10 + Number(replace_idxstr.slice(1,2));
                        replace_str = replace_str + this.dataObj.body.row[drowidx][replace_idx];
                    }
                    iii++;
                    strpos[iii] = findpos + 4;
                } else {
                    while_continue = false;
                }
            }
            let endstr = newhtml.slice(strpos[iii]);
			tdhtml = replace_str + endstr;
		}
		return tdhtml;
	}
    clearTRBlocks() {
        let prefix = this.prefix;
        let tbody = document.getElementById(`${prefix}js_dataTbody`);
	    tbody.innerHTML = '';
        let div_viewer = document.getElementById(`${prefix}js_dataViewer`);
        div_viewer.style.height = this.dataTRNowBlocks.divheight.toString() + 'px';
    }
    redrawTRBlocks( is_1sttime = false ){
	    this.dataTRNowBlocks.is_drawing = true;
	    // まずviewerのdivと高さをgetし、その中のtbodyをGETする
        let prefix = this.prefix;
        let tbody = document.getElementById(`${prefix}js_dataTbody`);

        let div_viewer_height = 0;
        let div_viewer = document.getElementById(`${prefix}js_dataViewer`);
        if (div_viewer !== null) {
            div_viewer_height = this.getHight(div_viewer);
        }else{
            console.log('clearTR null');
        }
        if (div_viewer_height === 0) {
            div_viewer_height = this.dataTRNowBlocks.divheight;
        }

        // 次に、今回のdataTRArrayの個数を数えておく。それがスクロールの最大値になる
        let alltrcount = this.dataTRArray.length;
        // もし全カウントが0だったらクリアして、何もしなくてよい
        if (alltrcount === 0 ) {
            tbody.innerHTML = '';
            div_viewer.style.height = this.dataTRNowBlocks.divheight + 'px';
    	    this.dataTRNowBlocks.is_drawing = false;
            return;
        }

        //maruyama add 20220916
        if ( div_viewer_height <= 200 && 6 < alltrcount) {
            if ( this.dataTRNowBlocks.divheight < this.dataTRNowBlocks.divheightMax ) {
                div_viewer_height =  this.dataTRNowBlocks.divheightMax;
                div_viewer.style.height = div_viewer_height.toString() + 'px';
            }
        }

        // trノードの高さを求め、そこからTable全体の高さを求めておく
        let trelms = this.dataTableByID.querySelectorAll('TR');
        let tr_height = 50;

        //tr heightが可変してしまうので、50で固定する
        // if (typeof trelms[1] !== 'undefined') {
        //     tr_height = this.getHight(trelms[1]);
        // }
        // if (tr_height === 0) {tr_height = 50;}
        let tableheight = tr_height * alltrcount;

        // 今回viewerの中に表示できるtrの数を求めておく
        let show_tr_count = Math.floor(div_viewer_height / tr_height);
        this.dataTRNowBlocks.trinblock = show_tr_count;


        // 現在のスクロール位置が全体の何％かを求め、現在表示中のcheckboxのインデックスを求めておく
        let nowpercent = this.dataTRNowBlocks.scrollpos / tableheight;
        let now_tridx  = Math.floor( alltrcount * nowpercent );


        // -----
        // 準備が終わったので、具体的な表示用のtrタグを再構築する作業を開始
        // -----

        // まず、現在表示中のtrのインデックスが範囲外なのかを判定する。
        // 現在表示中のtrのインデックスの表示範囲を求める。表示範囲は、show_tr_count
        let plusminus = Math.floor( this.dataTRNowBlocks.blockcount / 2 );
        let now_btm_tridx = now_tridx - Math.floor( show_tr_count / plusminus);
        let now_top_tridx = now_tridx + Math.floor( show_tr_count / plusminus);

        let is_redraw = false;
        if ( this.dataTRNowBlocks.scrollpos < this.dataTRNowBlocks.t_redrawpos ) {
            is_redraw = true;
        }
        if ( this.dataTRNowBlocks.b_redrawpos < this.dataTRNowBlocks.scrollpos ) {
            is_redraw = true;
        }
        if ( alltrcount <= tbody.childNodes.length -1) {
            is_redraw = false;
        }

        //create tr tags
        let create_tr_count = show_tr_count * this.dataTRNowBlocks.blockcount;
        if (is_1sttime) {
            this.dataTRNowBlocks.startidx = 0;
            this.dataTRNowBlocks.endidx = this.dataTRNowBlocks.startidx + create_tr_count;
            if ( this.dataTRArray.length <= this.dataTRNowBlocks.endidx) {
                this.dataTRNowBlocks.endidx = this.dataTRArray.length -1;
            }
            // 一旦表示をクリアし、書込開始
            tbody.innerHTML = '';
            for (let iii = this.dataTRNowBlocks.startidx; iii <= this.dataTRNowBlocks.endidx ; iii ++) {
                let trhtml = '';
                let tr = document.createElement('tr');
                trhtml = this.dataTRArray[iii];

				// imai add start
				trhtml = this.processCheckboxAttribute( trhtml );
				// imai add end

                tr.innerHTML = trhtml;
                tbody.appendChild(tr);
            }
            let endtr = document.createElement('tr');
            endtr.id = `endtr`;
            tbody.appendChild(endtr);
            let end_h = tr_height * (alltrcount - this.dataTRNowBlocks.endidx + 1);
            endtr.style.height = end_h.toString() + 'px';

            // redraw eventのためにセット
            this.dataTRNowBlocks.t_redrawpos = 0;
            let redrawblock = Math.floor(this.dataTRNowBlocks.blockcount /4);
            this.dataTRNowBlocks.b_redrawpos = tr_height * show_tr_count * ( this.dataTRNowBlocks.blockcount  - redrawblock );
            if ( end_h < this.dataTRNowBlocks.b_redrawpos ) {
                this.dataTRNowBlocks.b_redrawpos = end_h;
            }
            let prefix = this.prefix;
            let div = document.getElementById( `${prefix}js_dataViewer` );
            if (div !== null) {
                div.scrollTop = 0;
                this.dataTRNowBlocks.scrollpos = 0;
            }

			// imai add event set start
			var thisobj = this;
			let chkobj = tbody.querySelectorAll('input[type="checkbox"]');
			chkobj.forEach( elm => {
				elm.addEventListener('change',function(){
					thisobj.setCheckedDataArray( this.dataset.colId, this.dataset.rowId, this.checked );
				});
			});
			// imai add event set end

        } else {
            if (is_redraw) {
                let new_startidx = now_tridx - (create_tr_count/2);
                if (new_startidx < 0) { new_startidx = 0; }
                let new_endidx = now_tridx + (create_tr_count/2);
                if (alltrcount <= new_endidx ) { new_endidx = alltrcount -1; }

                this.dataTRNowBlocks.startidx = new_startidx;
                this.dataTRNowBlocks.endidx = new_endidx;

                // 一旦表示をクリアし、書込開始
                tbody.innerHTML = '';
                let firsttr = document.createElement('tr');
                tbody.appendChild(firsttr);
                //まず下駄をはかせる
                let start_h = tr_height * this.dataTRNowBlocks.startidx;
                tbody.firstElementChild.style.height = start_h.toString() + 'px';
                //今回の表示範囲を描画
                let is_show_idx = 0;
                if ( this.dataTRArray.length <= this.dataTRNowBlocks.endidx) {
                    this.dataTRNowBlocks.endidx = this.dataTRArray.length -1;
                }
                for (let iii = this.dataTRNowBlocks.startidx; iii <= this.dataTRNowBlocks.endidx ; iii ++) {
                    let trhtml = '';
                    let tr = document.createElement('tr');
                    trhtml = this.dataTRArray[iii];

					// imai add start
					trhtml = this.processCheckboxAttribute( trhtml );
					// imai add end

                    tr.innerHTML = trhtml;
                    tbody.appendChild(tr);
                }
                let endtr = document.createElement('tr');
                endtr.id = `endtr`;
                tbody.appendChild(endtr);
                let end_h = tr_height * (alltrcount - this.dataTRNowBlocks.endidx + 1);
                endtr.style.height = end_h.toString() + 'px';
                this.setCheckboxEvents();
                // redraw eventのためにセット
                this.dataTRNowBlocks.t_redrawpos = start_h + tr_height * show_tr_count *  2;
                // if ( end_h < this.dataTRNowBlocks.t_redrawpos ) {
                //     this.dataTRNowBlocks.t_redrawpos = start_h;
                // }
                this.dataTRNowBlocks.b_redrawpos = start_h + tr_height * show_tr_count * ( this.dataTRNowBlocks.blockcount  - 2 );
                if ( tableheight < this.dataTRNowBlocks.b_redrawpos ) {
                    this.dataTRNowBlocks.b_redrawpos = tableheight;
                }
				
				// imai add event set start
				var thisobj = this;
				let chkobj = tbody.querySelectorAll('input[type="checkbox"]');
				chkobj.forEach( elm => {
					elm.addEventListener('change',function(){
						thisobj.setCheckedDataArray( this.dataset.colId, this.dataset.rowId, this.checked );
					});
				});
				// imai add event set end
            }
        }

		// imai add table scale design start
		let tableScaleBtnDiv = document.getElementById(this.tableScaleBtnDiv);
		if ( tableScaleBtnDiv ) {
			let tableScaleBtn = document.getElementById(`${prefix}js_tableScaleBtn`);
			let dataViewer = document.getElementById(`${prefix}js_dataViewer`);
			let height = parseInt( dataViewer.style.height );
			if ( ! height ) {
				height = this.dataTRNowBlocks.divheight;
			}
			let html = '';
			if ( height < this.dataTRNowBlocks.divheight + 300 ) {
				html = '<div class="sc_tableScaleDown"></div>';
			} else {
				html = '<div class="sc_tableScaleUp"></div>';
			}
			if ( tableScaleBtn.innerHTML !== html ) {
				tableScaleBtn.innerHTML = html;
			}
		}
		// imai add table scale design end
		
    	this.dataTRNowBlocks.is_drawing = false;

        //create
        // let start_createidx = 0;
        // let start_h = 0;
        // let end_h = 0;
        // if ( is_1sttime ) {
        //     tbody.innerHTML = '';
        //     this.dataTRNowBlocks.endidx = create_tr_count -1;
        //     if ( this.dataTRArray.length <= this.dataTRNowBlocks.endidx ) {
        //         this.dataTRNowBlocks.endidx = this.dataTRArray.length -1;
        //     }
        //
        //     for (let iii = this.dataTRNowBlocks.startidx; iii <= this.dataTRNowBlocks.endidx ; iii ++) {
        //         let tr = document.createElement('tr');
        //         tr.innerHTML = this.dataTRArray[iii];
        //         tbody.appendChild(tr);
        //     }
        //     let endtr = document.createElement('tr');
        //     endtr.id = 'endtr';
        //     tbody.appendChild(endtr);
        //     tbody.firstElementChild.style.height = '0';
        //     let end_h = tr_height * (this.dataTRArray.length - this.dataTRNowBlocks.endidx + 1 );
        //     endtr.style.height = end_h.toString() + 'px';
        // } else {
        //     let redraw = -1;
        //     // add bottom
        //     if ( this.dataTRNowBlocks.trinblock < this.dataTRNowBlocks.endidx ) {
        //         if ((this.dataTRNowBlocks.endidx - this.dataTRNowBlocks.trinblock) < now_tridx && now_tridx <= this.dataTRNowBlocks.endidx ) {
        //             redraw = 1;
        //             start_createidx = this.dataTRNowBlocks.endidx + 1;
        //             this.dataTRNowBlocks.endidx = this.dataTRNowBlocks.endidx + Math.floor(this.dataTRNowBlocks.trinblock * this.dataTRNowBlocks.blockcount);
        //             if ( alltrcount < this.dataTRNowBlocks.endidx ) {
        //                 this.dataTRNowBlocks.endidx = alltrcount - 1;
        //             }
        //             create_tr_count = this.dataTRNowBlocks.endidx - start_createidx;
        //         }
        //     }
        //
        //     if ( now_tridx < this.dataTRNowBlocks.startidx || this.dataTRNowBlocks.endidx < now_tridx ){
        //         redraw = 2;
        //         this.dataTRNowBlocks.startidx = now_tridx - Math.floor(this.dataTRNowBlocks.trinblock * this.dataTRNowBlocks.blockcount / 2);
        //         if ( this.dataTRNowBlocks.startidx < 0 ) {
        //             this.dataTRNowBlocks.startidx = 0;
        //         }
        //         this.dataTRNowBlocks.endidx = now_tridx + Math.floor(this.dataTRNowBlocks.trinblock * this.dataTRNowBlocks.blockcount / 2);
        //         if ( alltrcount < this.dataTRNowBlocks.endidx ) {
        //             this.dataTRNowBlocks.endidx = alltrcount -1;
        //         }
        //         start_createidx = this.dataTRNowBlocks.startidx;
        //         create_tr_count = this.dataTRNowBlocks.endidx - this.dataTRNowBlocks.startidx;
        //     }
        //
        //
        //     if (redraw > 0) {
        //         let before_endtr = document.getElementById('endtr');
        //         if (before_endtr){before_endtr.remove();}
        //         if (redraw === 2) {
        //             tbody.innerHTML = '';
        //             let firsttr = document.createElement('tr');
        //             tbody.appendChild(firsttr);
        //         }
        //         start_h = tr_height * this.dataTRNowBlocks.startidx;
        //         tbody.firstElementChild.style.height = start_h.toString() + 'px';
        //
        //         for (let iii = start_createidx; iii < start_createidx + create_tr_count + 1; iii ++) {
        //             let tr = document.createElement('tr');
        //             tr.innerHTML = this.dataTRArray[iii];
        //             tbody.appendChild(tr);
        //         }
        //         let endtr = document.createElement('tr');
        //         endtr.id = 'endtr';
        //         tbody.appendChild(endtr);
        //         end_h = tr_height * (alltrcount - this.dataTRNowBlocks.endidx + 1);
        //         endtr.style.height = end_h.toString() + 'px';
        //     }
        // }
    }




    // ----------
    // Other
    // ----------
	createTbControler() {
	    let calcResult = this.createCalcResult();
	    let prefix = this.prefix;
	    let controler = `<div class="bl_tbControler" id="${prefix}js_tbControler">
							<div class="bl_tbControlerL">
								<div class="el_btn" id="${this.prefix}js_showFilter"><input id="${this.prefix}js_showFilterBtn" class="showFilterBtn" type="button" value="${this.wordsInTable['tjs_open_filter']}"></div>
								<div class="el_btn" id="${this.prefix}js_closeFilter"><input id="${this.prefix}js_closeFilterBtn" class="closeFilterBtn" type="button" value="${this.wordsInTable['tjs_close_filter']}"></div>
								<div class="el_caption"><span id="${this.prefix}js_clearAllBtn" class="clearAllBtn" style="text-decoration: none;color: #206CFF;font-size: 90%;cursor: pointer;">${this.wordsInTable['tjs_clear_filter']}</span></div>
							</div>
							<div class="bl_tbControlerR">
								<div class="el_caption ${this.prefix}" id="${this.prefix}js_calcResult">${calcResult}</div>
								<div class="el_caption" id="${this.prefix}js_dataCount">(${this.visibleCount}/${this.visibleArray.length})*Max:${this.dataMaxCount}</div>
							</div>
						</div>`;
	    return controler;
	}
	createCalcResult() {
	    let result = '';
        this.dataObj.body.header.forEach( (head, headidx) =>{
            if ( typeof head.calc !== 'undefined' && typeof head.sumResult !== 'undefined' ) {
                switch (head.calc) {
                    case 'sum':
                        result += `@${head.title}: ${head.sumResult.toString()} (Sum) | `;
                        break;

                    case 'avg':
                        let avg = head.sumResult / this.visibleCount;
                        let res = '';
                        switch (head.type) {
                            case 'number':
                                avg = avg.toFixed(2);
                                res = avg.toString();
                                break;
                            case 'second':
                                if (head.format) {
                                    avg = Math.round(avg);
                                    res = head.format(avg);
                                }else{
                                    avg = avg.toFixed(2);
                                    res = avg.toString();
                                }
                                break;
                        }
                        result += `@${head.title}: ${res} (Avg.) | `;
                        break;
                }
            }
        });
        return result;
    }

    /** -------------------------------------------
     * クリア関連の処理
     * 入力値をクリアするための処理
     */
    clearDateFilter() {
        let dates = this.filtertr.querySelectorAll('input[type="date"]');
        dates.forEach(elm => {
            elm.value = '';
        });
        let times = this.filtertr.querySelectorAll('input[type="time"]');
        times.forEach(elm => {
            elm.value = '';
        });
        let datetimes = this.filtertr.querySelectorAll('input[type="datetime"]');
        datetimes.forEach(elm => {
            elm.value = '';
        });
    }

	/** -------------------------------------------
	 * イベントの追加
	 * 各種イベントの設定
	 */
	setAllEvents() {
	    if ( this.filtertr ) {
			this.setHeaderEvents();
			this.setTbControlerEvents();
			this.setSortLinkEvents();
			// this.setCheckboxEvents();
			this.setCheckLinkEvents();
			this.setCloseBtnEvents();
			this.setFilterSearchEvents();
			this.setDataViewScrollEvents();
			this.setDataViewResizeEvents();
            for (let tableidx = 1; tableidx < this.checkTRArray.length; tableidx++){
                let has_filter = true;
                if ( typeof this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter !== 'undefined' ) {
                    if ( ! this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter ) {
                        has_filter = false;
                    }
                }
                if ( has_filter ) {
                    this.setCheckViewScrollEvents(tableidx);
                    this.setCheckboxHeaderEvents(tableidx);
                }
            }
		}

	}

	//----------
	// top button
	setTbControlerEvents() {
	    let shfbtn = document.getElementById(`${this.prefix}js_showFilterBtn`);
	    let clfbtn = document.getElementById(`${this.prefix}js_closeFilterBtn`);
		shfbtn.addEventListener('click', e => { this.evtAllFilterBoxesVisible(e) });
		clfbtn.addEventListener('click', e => { this.evtAllFilterBoxesHidden(e) });
	    let cfabtn = document.getElementById(`${this.prefix}js_clearAllBtn`);
        cfabtn.addEventListener("click", e => { this.evtResetAll(e) });
	    // let datcnt = document.getElementById(`${this.prefix}js_dataCount`);
	    // // datcnt.innerHTML = `(${this.visibleCount}/${this.visibleArray.length}) [Page# <a href="#${this.prefix}js_pageNation">1</a> / ${this.dataMaxCount}]`;
	    // datcnt.innerHTML = `(${this.visibleCount}/${this.visibleArray.length})`;
	    // let calrst = document.getElementById(`${this.prefix}js_calcResult`);
	    // let calcResult = this.createCalcResult();
	    // calrst.innerText = `${calcResult}`;
	}

	//----------
	// header
	setHeaderEvents() {
        // let thobj = this.divtag.querySelectorAll('th');
        let thobj = document.getElementsByClassName( this.prefix + 'js_filterTh');
        for ( let iii = 0; iii < thobj.length; iii++ ) {
            if ( this.dataObj.body.header[this.idxTable2Header(iii+1)].hasFilter === false) {
                let dummy = 1;
            } else {
                thobj[iii].addEventListener("click", e => { this.evtThClickFilter(e) })
            }
        }
        // thobj.forEach( (elm, thidx) => {
        //     if ( this.dataObj.body.header[this.idxTable2Header(thidx+1)].hasFilter === false) {
        //         let dummy = 1;
        //     } else {
        //         elm.addEventListener("click", e => { this.evtThClickFilter(e) })
        //     }
        // });
    }

	//----------
	// filter
    setCloseBtnEvents(){
        let closebtns = document.getElementsByClassName( this.prefix + 'js_closeBtn');
        for (let iii = 0; iii < closebtns.length; iii++ ) {
        	closebtns[iii].addEventListener("click", e => { this.evtCloseFilterBox(e) });
		}
	}

	//---
	// sort
    setSortLinkEvents() {
        let sortlink = this.getSpantagObj(`bl_sortControl`);
        sortlink.forEach( (elm, idx) => {
            elm.addEventListener( "click", e => {
                let idname = e.target.id;
                let idsplit   = idname.split('_');
                let tableindex = idsplit[2];
				// let tableindex = Math.floor( idx / 2 ) + 1;
				let sortorder  = e.target.className.replace( this.prefix + 'link', '');
				// this.sortVisibleArray(tableindex, sortorder);
				// this.sortFilterArray(tableindex, sortorder);
				// this.checkTRNowBlocks[tableindex].sort = 'none';
				// this.redrawTable();
                this.visibleSort.index = tableindex;
                this.visibleSort.order = sortorder;
                let sortlink = e.target;
                let orghtml = sortlink.innerHTML;
                sortlink.innerHTML = orghtml + '...sorting';

                if (this.globalVisibleDone && this.globalFilterDone) {
                    this.checkTRArrayDone[tableindex] = 'sortstart';
                    let dataObjJson  = JSON.stringify(this.dataObj);
                    let gVisibleJson = JSON.stringify(this.visibleArray);
                    let gFilterJson  = JSON.stringify(this.filterArray);

                    let vqw = new queryWorker(this.workerjs);
                    vqw.addListener('sortGlobalVisibleArray',(visijson) => {
                        this.visibleArray = JSON.parse(visijson);
                        this.globalVisibleDone = true;
                        this.createDataTRArray();
                        this.redrawTRBlocks(true);
                    });
                    this.globalVisibleDone = false;
                    vqw.sendQuery('sortGlobalVisibleArray', tableindex, sortorder, dataObjJson, gVisibleJson);

                    let fqw = new queryWorker(this.workerjs);
                    fqw.addListener('sortGolobalFilterArray',(filtjson) => {
                        this.filterArray = JSON.parse(filtjson);
                        this.globalFilterDone = true;
                        this.checkTRArrayDone[tableindex] = 'sorted';
                        this.createCheckTRArray(tableindex);
                        this.redrawCheckTRBlocks(tableindex, true);
                        this.setCheckboxEvents();
                        sortlink.innerHTML = orghtml;
                        this.showVisibleSort();
                    });
                    this.globalFilterDone = false;
                    fqw.sendQuery('sortGolobalFilterArray', tableindex, sortorder, dataObjJson, gFilterJson);
                }
            })
        });
    }

	setCheckLinkEvents(){
        // let checklink = this.getSpantagObj(`${this.prefix}js_allCheck`);
        // checklink.forEach(atag => {atag.addEventListener("click", e => { this.evtClickCheckLink(e) })});
        let checkboxes = document.getElementsByClassName(`${this.prefix}js_allCheckbox`);
        for ( let iii = 0; iii < checkboxes.length; iii++ ) {
            checkboxes[iii].addEventListener("change", e => {this.evtAllCheckbox(e);});
        }

        // checkbox.addEventListener("change", e => { this.evtAllCheckbox(e) });
	}

	//---
	// checkbox
    setFilterSearchEvents() {
       let searchbtns  = this.filtertr.getElementsByClassName('el_searchbtn');
        for ( let iii = 0; iii < searchbtns.length; iii++ ) {
            searchbtns[iii].addEventListener("click", e => {this.evtSearchBtnClick(e);});
        }
       let searchtexts = this.filtertr.getElementsByClassName(`${this.prefix}js_searchText`);
        for ( let iii = 0; iii < searchtexts.length; iii++ ) {
            searchtexts[iii].addEventListener("keypress", e => {this.evtSearchTextEnter(e);});
        }
        let inputs  = this.filtertr.querySelectorAll(`.${this.prefix}js_searchBox`);
        let selects = this.filtertr.querySelectorAll('SELECT');
        inputs.forEach( elm => {elm.addEventListener("keyup", e => {this.evtSearchInputChange(e);})});
        inputs.forEach( elm => {elm.addEventListener("change", e => {this.evtSearchInputChange(e);})});
        selects.forEach( elm => {elm.addEventListener("change", e => {this.evtSearchInputChange(e);})});
	}

    setCheckboxEvents() {
		// filter
        let chkobj = this.filtertr.querySelectorAll('input[type="checkbox"]');
        chkobj.forEach( elm => {
            if (! elm.classList.contains(`${this.prefix}js_allCheckbox`)) {
                elm.addEventListener("change", e => { this.evtChangeCheckbox(e)})}
            }
        );
	}
	setDataViewScrollEvents(){
	    let prefix = this.prefix;
        let div = document.getElementById( `${prefix}js_dataViewer` );
	    div.addEventListener("scroll", e => { this.evtDataViewScroll(e)}, {passive: true});
    }
    setDataViewResizeEvents(){
        let resizeObserver = new ResizeObserver(entries => {this.redrawTRBlocks();});
        let prefix = this.prefix;
        let div = document.getElementById( `${prefix}js_dataViewer` );
        resizeObserver.observe(div);
    }
	setCheckViewScrollEvents(tableidx){
        let prefix = this.prefix;
        let div = document.getElementById( `${prefix}js_uniqItems-${tableidx}` );
        if ( div !== null ) {
            div.addEventListener("scroll", (e) => { this.evtCheckViewScroll(e)}, {passive: true});
        }
    }
    setCheckboxHeaderEvents(tableidx){
	    let prefix   = this.prefix;
        let headertd = document.getElementById( `${prefix}js_checkboxHeaderText-${tableidx}` );
        if ( headertd !== null ) {
    	    headertd.addEventListener("click", e => { this.evtCheckboxHeaderText(e) })
        }
    }


	/** -------------------------------------------
	 * イベントの受け関数群
	 * ターゲットを確認して次の処理に渡す
	 */

    doQueue(job, timeout) {
        if(job in this.queueEvents) {
            window.clearTimeout(this.queueEvents[job]);
        }
        this.queueEvents[job] = window.setTimeout(()=> {
            if ( this.queueEvents[job] ) {
                delete this.queueEvents[job];
            }
            try {
                job.call();
            } catch(e) {
                alert("Too many Operation!");
            }
        }, timeout);
    }

	evtAllFilterBoxesVisible(e) {
		this.setActiveAllFilterBoxes();
		this.showAllFilterBoxes(true);
	}
	evtAllFilterBoxesHidden(e) {
		this.clearActiveAllFilterBoxes();
		this.hideAllFilterBoxes(true);
	}

	evtResetAll(e) {
        const dofunc = function() {
            this.clearAllCheck();
            this.clearActiveAllFilterBoxes();
            this.backToDfltGrf = true;
            this.generateFilterDataIdxSet();
            this.redrawTable();
            this.hideAllFilterBoxes();

        }.bind(this);
        event.preventDefault();

        if ( this.qahmobj.showLoadIcon !== undefined ){
            this.qahmobj.showLoadIcon(function(){
                dofunc();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            dofunc();
        }
	}

	evtThClickFilter(e) {
	    let tableidx = e.target.dataset.tableidx;
	    if ( typeof tableidx === 'undefined') {
			let parentth = this.searchParentTagName(e.target, 'TH');
			tableidx = parentth.dataset.tableidx;
		}
	    let targettd = this.filtertr.childNodes[ tableidx-1 ];
	    if (targettd.classList.contains(`${this.prefix}is_filterActive`)) {
            this.clearActiveFilterBox(tableidx);
        }else {
            this.setActiveFilterBox(tableidx);
        }
	    if ( this.countActiveFilterBoxes() === 0 ) {
	        this.hideAllFilterBoxes(true);
		}else{
	        this.showAllFilterBoxes(true);
		}
		e.stopPropagation();
	}

	evtCloseFilterBox(e) {
	    let parentdiv = this.searchParentTagName(e.target, 'DIV');
	    let tableidx = parentdiv.dataset.tableidx;
	    this.clearActiveFilterBox(tableidx);
	    let boxcnt = this.countActiveFilterBoxes();
	    if ( boxcnt === 0 ) {
	        this.hideAllFilterBoxes(true);
		}
		e.stopPropagation();
	}


	evtSearchInputChange(e) {
         if ( e.keyCode !== 13 ) {
            let id = e.target.id;
            let id_split = id.split('-');
            let tableidx = Number(id_split[1]);
            let searchbtn = document.getElementById(`${this.prefix}js_searchBtn-${tableidx}`);
            // searchbtn.style.color = '#206CFF';
            // searchbtn.style.borderBottom = 'solid 2px #206CFF';
            searchbtn.classList.add('el_flash');
            e.stopPropagation();
         }
	}

	evtSearchBtnClick(e) {
        let searchbtn = this.searchParentTagName(e.target, 'SPAN');
	    let id = searchbtn.id;
	    let id_split = id.split('-');
	    let tableidx = Number(id_split[1]);
	    if ( 0 < tableidx ) {
            this.createCheckTRArray(Number(tableidx), true);
            this.redrawCheckTRBlocks(Number(tableidx), true);
            this.setCheckboxEvents();
            // searchbtn.style.color = '#000';
            // searchbtn.style.borderBottom = '';
            searchbtn.classList.remove('el_flash');
        }
	    e.stopPropagation();
	}

	evtSearchTextEnter(e) {
        if ( e.keyCode === 13 ) {
            let searchtext = e.target;
            let id = searchtext.id;
            let id_split = id.split('-');
            let tableidx = Number(id_split[1]);
            let searchbtn = document.getElementById(`${this.prefix}js_searchBtn-${tableidx}`);
            if ( 0 < tableidx ) {
                this.createCheckTRArray(Number(tableidx), true);
                this.redrawCheckTRBlocks(Number(tableidx), true);
                this.setCheckboxEvents();
                // searchbtn.style.color = '#000';
                // searchbtn.style.borderBottom = '';
                searchbtn.classList.remove('el_flash');
            }
            e.stopPropagation();
        }
	}


	evtChangeCheckbox(e) {
        const dofunc = function () {
            let checkbox = e.target;
            let checked  = checkbox.checked;
            let inputchkid = checkbox.id;
            let inputidsep = inputchkid.split('_')
            let tableidx   = Number(inputidsep[3]);
            let uniqidx    = Number(inputidsep[4]);
            let checkclass = `${this.prefix}js_uniqItem`;
            let parentdiv = this.searchParentTagName(checkbox, 'DIV');
            if (parentdiv.classList.contains(checkclass)) {
                let is_search = true;
                let iii = 0;
                while (is_search) {
                    if (Number(this.filterArray[tableidx][iii].uniqidx) === uniqidx) {
                        is_search = false;
                        if (checked) {
                            this.filterArray[tableidx][iii].checked = true;
                        }else{
                            this.filterArray[tableidx][iii].checked = false;
                        }
                        this.generateFilterDataIdxSet();
                        this.redrawTable();
                        // this.createDataTRArray();
                        // this.redrawTRBlocks(true);
                        // this.createCheckTRArray(tableidx);
                        // this.redrawCheckTRBlocks(true);
                        break;
                    }
                    iii++;
                }
                let mkdummy = e;
                // this.redrawTable();
            }
        }.bind(this);

        if ( this.qahmobj.showLoadIcon !== undefined ){
            this.qahmobj.showLoadIcon(function () {
                dofunc();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            dofunc();
        }
        e.stopPropagation();

	}
	evtAllCheckbox(e) {
        event.preventDefault();
        let is_filterActive = false;
		this.filtertr.childNodes.forEach( td => {
		    if (td.classList.contains(`${this.prefix}is_filterActive`) === true) {
		        is_filterActive = true;
            }
		})
        const dofunc = function(){
            let cbox = e.target;
            let tableidx = cbox.dataset.tableidx;
            if (cbox.checked && is_filterActive){
                // 1st check checkTRArray
                let is_showcnt = 1;
                if ( 500 < this.checkTRArray[tableidx].length ) {
                    //alert('全てチェックは501個以上できません。フィルターで条件を絞って再度お試しください。');
                    alert( this.wordsInTable['tjs_alert_msg_over500'] );
                    cbox.checked = false;
                    this.changeTableidxCheck(tableidx, false);
                    return;
                }

                for (let uidx = 0; uidx < this.checkTRArray[tableidx].length; uidx++) {
                    let whilecnt = 0;
                    let whilemax = this.filterArray[tableidx].length -1;
                    let is_show  = this.checkTRArray[tableidx][uidx].is_show;
                    let uniqidx  = this.checkTRArray[tableidx][uidx].uniqidx;
                    if (is_show) {
                        this.filterArray[tableidx][uniqidx].checked = true;
                        is_showcnt++;
                    }
                }
                this.generateFilterDataIdxSet();
                this.redrawTable();
            }else if( is_filterActive ){
                this.changeTableidxCheck(tableidx, false);
                this.clearSearchInput(tableidx);
                this.generateFilterDataIdxSet();
                let  uniqItemdiv = document.getElementById(`${this.prefix}js_uniqItems-${tableidx}"`)
                if (uniqItemdiv !== null) {
                    uniqItemdiv.scrollTop = 0;
                    this.checkTRNowBlocks[tableidx].scrollpos = 0;
                }
                this.redrawTable();
                if (this.countActiveFilterBoxes() === 0 ) {
                    this.hideAllFilterBoxes();
                } else {
                    this.showAllFilterBoxes();
                }
            }
        }.bind(this);

        if ( this.qahmobj.showLoadIcon !== undefined ){
            this.qahmobj.showLoadIcon(function () {
                dofunc();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            dofunc();
        }
        e.stopPropagation();
	}


	evtClickCheckLink(e) {
        event.preventDefault();
        const dofunc = function(){
            let atag = e.target;
            let tableidx = atag.dataset.tableidx;
            if (atag.classList.contains(`${this.prefix}js_allChkLink`)){
                // 1st check checkTRArray
                let is_showcnt = 1;
                for (let uidx = 0; uidx < this.checkTRArray[tableidx].length; uidx++) {
                    let whilecnt = 0;
                    let whilemax = this.filterArray[tableidx].length -1;
                    let is_show  = this.checkTRArray[tableidx][uidx].is_show;
                    let uniqidx  = this.checkTRArray[tableidx][uidx].uniqidx;
                    if (is_show) {
                        if ( 100 < is_showcnt) {
                            //alert('全てチェックは101個以上できません。フィルターで条件を絞って再度お試しください。');
                            alert( this.wordsInTable['tjs_alert_msg_over100']);
                            this.changeTableidxCheck(tableidx, false);
                            return;
                        }
                        this.filterArray[tableidx][uidx].checked = true;
                        is_showcnt++;
                    }
                }
                this.generateFilterDataIdxSet();
                this.redrawTable();
            }
            if (atag.classList.contains(`${this.prefix}js_clearChkLink`)){
                this.changeTableidxCheck(tableidx, false);
                this.clearSearchInput(tableidx);
                this.generateFilterDataIdxSet();
                this.redrawTable();
                if (this.countActiveFilterBoxes() === 0 ) {
                    this.hideAllFilterBoxes();
                } else {
                    this.showAllFilterBoxes();
                }
            }
        }.bind(this);

        if ( this.qahmobj.showLoadIcon !== undefined ){
            this.qahmobj.showLoadIcon(function () {
                dofunc();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            dofunc();
        }
        e.stopPropagation();
	}

    evtDataViewScroll (e)  {
        let prefix = this.prefix;
        let tableheight = this.getHight(this.dataTableByID);

        this.dataTRNowBlocks.scrollpos = e.target.scrollTop;
        if ( this.dataTRNowBlocks.b_redrawpos < this.dataTRNowBlocks.scrollpos || this.dataTRNowBlocks.scrollpos < this.dataTRNowBlocks.t_redrawpos) {
            if ( ! this.dataTRNowBlocks.is_drawing ) {
                this.redrawTRBlocks();
            }
        }


        // if ( 0 < this.dataTRNowBlocks.scrollpos && this.dataTRNowBlocks.scrollpos < tableheight) {
        //     this.redrawTRBlocks();
        // }
    }
    evtCheckViewScroll (e)  {
        let prefix = this.prefix;
        let div_viewer = e.target;
        let divid      = div_viewer.id;
        let dividsplit = divid.split('-');
        let tableidx = Number(dividsplit[1]);
        let checktable  = document.getElementById(`${prefix}js_uniqItemsTable-${tableidx}`)
        // let checktable  = document.getElementById(`${prefix}js_checkTbody-${tableidx}`)
        if (checktable !== null) {
            let tableheight = this.getHight(checktable);
        }
        this.checkTRNowBlocks[tableidx].scrollpos = div_viewer.scrollTop;
        if ( this.checkTRNowBlocks[tableidx].b_redrawpos < this.checkTRNowBlocks[tableidx].scrollpos || this.checkTRNowBlocks[tableidx].scrollpos < this.checkTRNowBlocks[tableidx].t_redrawpos) {
            if ( ! this.checkTRNowBlocks[tableidx].is_drawing ) {
                this.redrawCheckTRBlocks(Number(tableidx));
            }
        }
    }
    evtCheckboxHeaderText(e) {
        let prefix     = this.prefix;
        let tdtag      = e.target;
        let tdtagid    = tdtag.id;
        let tdtagsplit = tdtagid.split('-');
        let tableidx   = Number(tdtagsplit[1]);
        switch (this.checkTRNowBlocks[tableidx].sort) {
            case 'none':
            case 'asc':
                this.checkTRNowBlocks[tableidx].sort = 'dsc';
                tdtag.innerText = '▼'
                break;
            case 'dsc':
                this.checkTRNowBlocks[tableidx].sort = 'asc';
                tdtag.innerText = '▲'
                break;

            default:
                this.checkTRNowBlocks[tableidx].sort = 'none';
                tdtag.innerText = '▼'
                break;
        }
        if (typeof this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter !== 'undefined') {
            if (this.dataObj.body.header[this.idxTable2Header(tableidx)].hasFilter) {
                this.createCheckTRArray(tableidx, true);
                this.redrawCheckTRBlocks(tableidx, true);
                this.setCheckboxEvents();
            }else{
                let mkdummy = 1;
            }
        }else{
            this.createCheckTRArray(tableidx, true);
            this.redrawCheckTRBlocks(tableidx, true);
            this.setCheckboxEvents();
        }
    }

	/** -------------------------------------------
	 * 見え方の加工
	 * 現状のデータ内容に従い、現状の見え方を加工
	 */

	//---
	// about filter box
    showOrHideFilterBoxes(){
        if (this.countActiveFilterBoxes() === 0 ) {
            this.hideAllFilterBoxes();
        } else {
            this.showAllFilterBoxes();
        }
    }
	showAllFilterBoxes(is_showLoadIcon = false) {
        const showfilter = function () {
            this.filtertr.style.display = 'table-row';
            // this.filtertr.style.visibility = 'visible';
            this.isFilterShow = 2;
        }.bind(this);

        //今のTableのフィルターが表示されているかどうか
        let rect = this.divtag.getBoundingClientRect();
        let isInView = 0 < rect.bottom && rect.top < window.innerHeight;

        if ( this.qahmobj.showLoadIcon !== undefined && isInView && is_showLoadIcon ){
            this.qahmobj.showLoadIcon(function(){
                showfilter();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            showfilter();
        }
	}
	hideAllFilterBoxes(is_showLoadIcon = false) {
        const hidefilter = function () {
            this.filtertr.style.display = 'none';
            // this.filtertr.style.visibility = 'hidden';
            this.isFilterShow = -1;
        }.bind(this);

        //今のTableのフィルターが表示されているかどうか
        let rect = this.divtag.getBoundingClientRect();
        let isInView = 0 < rect.bottom && rect.top < window.innerHeight;

        if ( this.qahmobj.showLoadIcon !== undefined && isInView && is_showLoadIcon ){
            this.qahmobj.showLoadIcon(function(){
                hidefilter();
                if ( this.qahmobj.hideLoadIcon !== undefined ){this.qahmobj.hideLoadIcon(); }
            }.bind(this));
        } else {
            hidefilter();
        }
	}

	showFilterBox(tableidx) {
	    this.filtertr.childNodes[(tableidx-1)].classList.add(`${this.prefix}is_filterActive`);
	    this.showAllFilterBoxes();
	}

	setActiveAllFilterBoxes() {
	    this.filtertr.childNodes.forEach( td => {
	    	td.classList.add(`${this.prefix}is_filterActive`);
		});
	}
	setActiveFilterBox (tableidx) {
	    this.filtertr.childNodes[(tableidx-1)].classList.add(`${this.prefix}is_filterActive`);
	}
	clearActiveAllFilterBoxes() {
	    this.filtertr.childNodes.forEach( td => {
	    	td.classList.remove(`${this.prefix}is_filterActive`);
		});
	}
	clearActiveFilterBox (tableidx) {
	    this.filtertr.childNodes[(tableidx-1)].classList.remove(`${this.prefix}is_filterActive`);
	}

    showVisibleSort() {
	    let sortsvg   = this.svg.asc;
		let atagclass = this.prefix + 'linkasc';
		if ( this.visibleSort.order === 'dsc' ) {
			sortsvg = this.svg.dsc;
			atagclass = this.prefix + 'linkdsc';
		}
		let sortmark   = `<div class="bl_thSortmMark ${this.prefix}js_thSortmMark">${sortsvg}</div>`;

        // sortsvg fot th
        const removeSortSvg = async function() {
            let sortmarkdivs = document.getElementsByClassName(`${this.prefix}js_thSortmMark`);
            for ( let iii = 0; iii < sortmarkdivs.length; iii++ ) {
                sortmarkdivs[iii].remove();
            }
        }.bind(this);
        const addSortSvg = async function() {
            let spantag = document.getElementById(`${this.prefix}js_filterSvg-${this.visibleSort.index}`);
            if (spantag !== null) {
                spantag.insertAdjacentHTML('beforebegin', sortmark);
            }
        }.bind(this);
        const sortsvgInsert = async function() {
            await removeSortSvg();
            await addSortSvg();
        }
        sortsvgInsert();

		// fot filter box
        let sortlink = this.getSpantagObj(`bl_sortControl`);
        sortlink.forEach( (elm, idx) => {
            let tableindex = Math.floor( idx / 2 ) + 1;
			if ( tableindex === ( this.visibleSort.index ) ) {
				if ( elm.className === this.prefix + 'link' + this.visibleSort.order) {
				    elm.style.fontWeight = 'bolder';
				} else {
				    elm.style.fontWeight = 'normal';
				}
			} else {
				elm.style.fontWeight = 'normal';
			}
        })
    }
    showFilteredTh() {
        let allcheckary = this.createAllCheckArray();
	    for (let tableidx = 1; tableidx < allcheckary.length; tableidx++ ) {
	        let spansvg = this.svgChangeColor(this.svg.sortdown,this.color.base);
            if (allcheckary[tableidx].length > 0) {
                if (allcheckary[tableidx][0].checked ) {
                    spansvg = this.svgChangeColor(this.svg.filtered, this.color.accent);
                }
            }
            let spantbidx = document.getElementById(`${this.prefix}js_filterSvg-${tableidx}`);
	        if (spantbidx !== null) {
                spantbidx.innerHTML = spansvg;
            }
        }
    }

	clearSearchInput( tableidx ) {
		let sboxid = `${this.prefix}js_SearchBoxes-${tableidx}`;
		let inputs = document.getElementById(sboxid).querySelectorAll('INPUT');
		inputs.forEach( elm => {elm.value="";});
		let selects = document.getElementById(sboxid).querySelectorAll('SELECT');
		selects.forEach( elm => {elm.options[0].selected = true; })
	}




	//---
	// data(array) abject
	changeTableidxCheck(tableidx, checked) {
    	this.filterArray[tableidx].forEach((uniqdata, rowidx) => {
    	    uniqdata.checked =  checked;
        });
	}

	clearIfNoChecked() {
	    let is_nocheck = this.isNoCheck();
	    if ( is_nocheck ) {
	        this.filterArray.forEach( ( col, colidx) => {
	            if ( colidx > 0 ) {
			        this.changeTableidxCheck( colidx, false );
				}
			} )
		}
	}
	clearAllCheck( checked = false ) {
        this.filterArray.forEach( ( col, colidx ) => {
            if ( colidx > 0 ) {
	            col.forEach( uniqdata => {
	            	uniqdata.checked = checked;
                } );
	            if ( this.dataObj.body.header[this.idxTable2Header(colidx)].hasFilter === false) {
	            	let dummy = 1;
                } else {
		        	this.clearSearchInput(colidx);
				}
			}
		})
		this.filtertr.childNodes.forEach( td => {
		    td.classList.remove(`${this.prefix}is_filterActive`);
		})
        let checkboxes = document.getElementsByClassName(`${this.prefix}js_allCheckbox`);
        for ( let iii = 0; iii < checkboxes.length; iii++ ) {
            checkboxes[iii].checked = false;
        }
        let uniqItemdivs = document.getElementsByClassName(`${this.prefix}js_uniqItems`);
        for ( let iii = 0; iii < uniqItemdivs.length; iii++ ) {
            uniqItemdivs[iii].scrollTop = 0;
            this.checkTRNowBlocks[iii+1].scrollpos = 0;
        }
	}

	/** -------------------------------------------
	 * 関数群
	 * 役に立つ関数群
	 */
	countActiveFilterBoxes() {
	    let count = 0;
	    this.filtertr.childNodes.forEach( td => {
	        if (td.classList.contains(`${this.prefix}is_filterActive`)) {
	        	count++;
			}
        });
	    return count;
	}
	isNoCheck() {
	    let is_nocheck = true;
	    this.filterArray.forEach( ( col, colidx ) => {
	        if ( colidx > 0 ) {
				col.forEach( ( row, uniqidx ) => {
					if ( row.checked === true ) {
						is_nocheck = false;
					}
				});
			}
		});
	    return is_nocheck;
    }

	searchParentTagName ( child, tagname ) {
        let parent = child.parentNode;
        if ( parent.tagName === tagname.toUpperCase() ) {
            return parent;
		}
		if ( parent.tagName === 'BODY') {
            return false;
		}
		return this.searchParentTagName( parent, tagname );
	}

    reverseArray ( array ) {
		return array[ 0 ].map( ( col, colidx ) => {
		    return array.map( ( row ) => {return row[ colidx ];});
		});
	}

    uniqArray ( array ) {
    	return Array.from(new Set(array));
	}

    IdxForHeader(index) {
        let iii = 0;
        let headerIdx = -1;
        this.dataObj.body.header.some( (head, headidx) => {
            if ( ! head.isHide ) {
                if ( index === iii ) {
                    headerIdx =  headidx;
                    return true;
                }
                iii++;
            }
        })
        return headerIdx;
    }
    idxTable2Header ( tableidx ) {
        let iii = 1;
        let headerIdx = -1;
        for (let colidx = 0; colidx < this.dataObj.body.header.length; colidx++) {
            if ( ! this.dataObj.body.header[colidx].isHide ) {
                if ( Number(tableidx) === Number(iii) ) {
                    headerIdx = colidx;
                    break;
                }
                iii++;
            }
        }
        return headerIdx;
	}

	idxHeader2Table ( headeridx ) {
        let iii = 1;
        let tableidx = -1;
        this.dataObj.body.header.some( (head, headidx) => {
            if ( ! head.isHide ) {
                if ( headidx === headeridx ) {
                    tableidx =  iii;
                    return true;
                }
                iii++;
            }
        })
        return tableidx;
	}


    getDatetimeValue (date) {
        let nowtim = date;
    	return nowtim.getFullYear() + '-' + ('0' + ( nowtim.getMonth() + 1 )).slice(-2) + '-' + ('0' + nowtim.getDate()).slice(-2) + '' +  ('0' + nowtim.getHours()).slice(-2) + ':' + ('0' + nowtim.getMinutes()).slice(-2);
	}
    getDateValue (date) {
        let nowtim = date;
    	return nowtim.getFullYear() + '-' + ('0' + ( nowtim.getMonth() + 1 )).slice(-2) + '-' + ('0' + nowtim.getDate()).slice(-2);
	}
    getTimeValue (date) {
        let nowtim = date;
    	return ('0' + nowtim.getHours()).slice(-2) + ':' + ('0' + nowtim.getMinutes()).slice(-2);
	}
	getOlderDateObj ( tableidx ) {
        let mindate = 0;
        if (this.filterArray.length > 0) {
            if (this.filterArray[tableidx][0].uniqitem) {
                mindate = Date.parse(this.filterArray[tableidx][0].uniqitem);
            }
            let minstr  = '1970/01/01 00:00:00';
            if (this.filterArray[tableidx][0].uniqitem) {
                minstr  = this.filterArray[tableidx][0].uniqitem;
            }
            this.filterArray[tableidx].forEach( elm => {
                if ( Date.parse( elm.uniqitem ) < mindate ) {
                    mindate = Date.parse( elm.uniqitem );
                    minstr  = elm.uniqitem;
                }
            });
        }
		return new Date( mindate );
	}

	getOlderUnixtime ( tableidx ) {
        let mintime  = '1970/01/01 00:00:00';
        if (this.filterArray.length > 0) {

            if (this.filterArray[tableidx][0].uniqitem) {
                mintime = Number(this.filterArray[tableidx][0].uniqitem);
            }
            this.filterArray[tableidx].forEach( elm => {
                if ( Number( elm.uniqitem ) <= mintime ) {
                    mintime  = Number(elm.uniqitem);
                }
            });
        }
		return mintime;
	}
	getNewerUnixtime ( tableidx ) {
	    let maxtime = '1970/01/01 00:00:00';
	    if (this.filterArray.length > 0) {
            if (this.filterArray[tableidx][0].uniqitem) {
                maxtime  = Number(this.filterArray[tableidx][0].uniqitem);
            }
            this.filterArray[tableidx].forEach( elm => {
                if ( Number( elm.uniqitem ) >= maxtime ) {
                    maxtime  = Number(elm.uniqitem);
                }
            });
        }
		return maxtime;
	}

	getAtagObj ( parentdivclassname ) {
        let ret = [];
        let aobjs = this.filtertr.querySelectorAll('a');
        aobjs.forEach(aobj => {
            let parentdiv = this.searchParentTagName(aobj, 'DIV');
            if (parentdiv.classList.contains(parentdivclassname)) {
                ret.push(aobj);
            }
        });
        return ret;
    }

	getSpantagObj ( parentdivclassname ) {
        let ret = [];
        let spanobjs = this.filtertr.querySelectorAll('span');
        spanobjs.forEach(spanobj => {
            let parentdiv = this.searchParentTagName(spanobj, 'DIV');
            if (parentdiv.classList.contains(parentdivclassname)) {
                ret.push(spanobj);
            }
        });
        return ret;
    }

    // createAllCheckArray(){
	 //    let allcheckary = [];
	 //    allcheckary.push([]);
	 //    for (let tableidx = 1; tableidx < this.filterArray.length; tableidx++ ) {
	 //        let chkary = [];
	 //        for (let uniqidx = 0; uniqidx < this.filterArray[tableidx].length; uniqidx++ ) {
	 //            let uniqitem = this.filterArray[tableidx][uniqidx];
	 //            if ( uniqitem.checked ) {
		//  		    chkary.push({uniqidx: uniqitem.uniqidx, dataidx: uniqitem.dataidx, checked: uniqitem.checked})
    //             }
    //         }
	 //        allcheckary.push( chkary );
    //     }
	 //    return allcheckary;
    // }
    //
    // createViewDataIdxSet(allcheckary) {
		// //チェックがない列なので対応するアイテムのみを表示
		// // まず全列でチェックされている値のdataidxだけの配列を作成する
		// let alldataidxary = [];
		// let coldataidxs = [];
    //
		// for (let tblidx = 1; tblidx < allcheckary.length; tblidx++) {
    //         let coldataidx = [];
    //         for (let checkedidx = 0; checkedidx < allcheckary[tblidx].length; checkedidx++) {
    //             let ary = allcheckary[tblidx][checkedidx].dataidx;
    //             alldataidxary = alldataidxary.concat(ary);
    //             coldataidx = coldataidx.concat(ary);
    //         }
    //         coldataidx = coldataidx.sort();
    //         coldataidxs.push( coldataidx );
    //     }
    //
		// alldataidxary = this.uniqArray( alldataidxary );
		// alldataidxary = alldataidxary.sort();
		// let nowidx = new Array(coldataidxs.length);
		// nowidx.fill(0);
    //
    //
		// // 全ての列に含まれるdataidxを配列で求める
		// let allin_dataidx = [];
		// let flg = false;
    //     for ( let uniqidx = 0; uniqidx < alldataidxary.length; uniqidx++ ) {
    //         let colidx = 0;
    //         let while_continue = true;
    //         while (while_continue) {
    //             let lpmax = coldataidxs[colidx].length;
    //             if ( lpmax > 0 ) {
    //                 let iii = nowidx[colidx];
    //                 flg = false;
    //                 while ( iii < lpmax ) {
    //                     if ( coldataidxs[colidx][iii] === alldataidxary[uniqidx] ) {
    //                         nowidx[colidx] = iii;
    //                         iii = lpmax + 888;
    //                         flg = true;
    //                     }
    //                     iii++;
    //                 }
    //                 if ( flg === false ) {
    //                     while_continue = false;
    //                 }
    //             }
		// 		colidx++;
		// 		if (colidx >= coldataidxs.length) {
		// 		    while_continue = false;
    //             }
    //         }
    //         if (flg) {
    //             allin_dataidx.push( alldataidxary[uniqidx] )
    //         }
    //     }
		// let set_allin_dataidx = new Set(allin_dataidx);
    //     return set_allin_dataidx;
    // }
    getHight(elm) {
	    let Rect = elm.getBoundingClientRect();
        let marginTop = parseInt(this.getStyle(elm, 'margin-top'));
        let marginBottom = parseInt(this.getStyle(elm, 'margin-bottom'));
	    return Rect.height + marginTop + marginBottom;
    }
    getStyle( elm, parm ) {
	    let style = window.getComputedStyle(elm);
	    return style.getPropertyValue(parm);
    }
	// imai add start
	getCheckedDataArray( colId ){
		if ( this.checkedDataArray === null ){
			return null;
		}
		let checkIdAry = this.checkedDataArray[colId];
		let resultAry  = [];
		for ( let checkIdx = 0; checkIdx < checkIdAry.length; checkIdx++ ) {
			let rowId = checkIdAry[checkIdx];
			resultAry.push( this.dataObj.body.row[rowId] );
		}
		return resultAry;
	}
	setCheckedDataArray( colId, rowId, isCheck ){
		colId = Number( colId );
		rowId = Number( rowId );

		if ( this.checkedDataArray[colId] === undefined ) {
			this.checkedDataArray[colId] = [];
		}
		
		let tarIdx  = null;
		for ( let iii = 0; iii < this.checkedDataArray[colId].length; iii++ ) {
			if ( this.checkedDataArray[colId][iii] === rowId ) {
				tarIdx = iii;
				break;
			}
		}
		
		if ( tarIdx !== null && ! isCheck ) {
			this.checkedDataArray[colId].splice( tarIdx, 1 );
		} else if ( tarIdx === null && isCheck ) {
			this.checkedDataArray[colId].push( rowId );
		}

		// setCheckedColMaxArrayが設定されており、その最大数に達していた場合、チェックがついてないものはdisabledにする
		if ( this.checkedColMaxArray[colId] !== undefined ) {
			let maxNum = this.checkedColMaxArray[colId];
			let prefix = this.prefix;
			let tbody = document.getElementById(`${prefix}js_dataTbody`);
			let chkobj = tbody.querySelectorAll('input[data-col-id="' + colId + '"]');
			if ( this.checkedDataArray[colId].length >= maxNum ) {
				chkobj.forEach( elm => {
					if ( elm.checked === false ) {
						elm.disabled = true;
					}
				});
			} else {
				chkobj.forEach( elm => {
					elm.disabled = false;
				});
			}
		}
	}
	clearCheckedDataArray() {
		this.checkedDataArray = [];
	}
	setCheckedColMaxArray( colId, maxNum ){
		this.checkedColMaxArray[colId] = maxNum;
	}
	clearCheckedColMaxArray() {
		this.checkedColMaxArray = [];
	}
	// imai add end
	consolenow(msg) {
        let now = new Date();
        console.log(msg + ',' + now.getTime());
    }
    isScrolled() {
        let prefix = this.prefix;
        let div_viewer = document.getElementById(`${prefix}js_dataViewer`);
        let div_height = this.getHight(div_viewer);
        return (div_viewer.scrollTop > div_height) ? true : false;
    }
	/** -------------------------------------------
	 * API
	 * 外部からtable.jsに命令するための関数群
	 */
    apiFilterTable(filterObj) {
        //1st make filterArray check
        this.clearAllCheck(false);
        for( let iii = 0; iii < filterObj.length ; iii++ ) {
            let tidx   = filterObj[iii].tableidx;
            let type   = this.dataObj.body.header[this.idxTable2Header(tidx)].type;
            let chkobj = filterObj[iii].filteritems;
            let isNoCheck  = true;
            for (let itemidx   = 0; itemidx < chkobj.length; itemidx++) {
                let filteritem = chkobj[itemidx].uniqitem;
                let isFind     = false;
                let rowidx     = 0;

                while (! isFind ) {
                    let uniqdata = this.filterArray[tidx][rowidx];
                    let uniqitem = uniqdata.uniqitem;
                    let nowcheck = false;
                    switch (type) {
                        case 'number':
                        case 'currency':
                        case 'second':
                            if (isNaN(filteritem)) {
                                if (chkobj[itemidx].compare === 'eq') {
                                    nowcheck = ( filteritem === uniqdata.uniqitem ) ? true : false;
                                    if (nowcheck) {
                                        isFind = true;
                                    }
                                }else{
                                    nowcheck = false;
                                }
                            }else{
                                switch (chkobj[itemidx].compare) {
                                    case 'eq':
                                        nowcheck = ( Number(filteritem) === Number(uniqdata.uniqitem)) ? true : false;
                                        if (nowcheck) {
                                            isFind = true;
                                        }
                                        break;

                                    case 'le':
                                        nowcheck = ( Number(filteritem) >= Number(uniqdata.uniqitem)) ? true : false;
                                        break;

                                    case 'lt':
                                        nowcheck = ( Number(filteritem) > Number(uniqdata.uniqitem)) ? true : false;
                                        break;

                                    case 'ge':
                                        nowcheck = ( Number(filteritem) <= Number(uniqdata.uniqitem)) ? true : false;
                                        break;

                                    case 'gt':
                                        nowcheck = ( Number(filteritem) < Number(uniqdata.uniqitem)) ? true : false;
                                        break;

                                    case 'none':
                                    default:
                                        nowcheck = false;
                                        break;
                                }
                            }
                            break;

                        case 'datetime':
                            let fromdate = Date.parse( filteritem  );
                            if (isNaN(fromdate)) {
                                if (chkobj[itemidx].compare === 'eq') {
                                    nowcheck = ( filteritem === uniqdata.uniqitem ) ? true : false;
                                    if (nowcheck) {
                                        isFind = true;
                                    }
                                }else{
                                    nowcheck = false;
                                }
                            }else{
                                switch (chkobj[itemidx].compare) {
                                    case 'eq':
                                        nowcheck = ( fromdate === Date.parse(uniqdata.uniqitem) ) ? true : false;
                                        if (nowcheck) {
                                            isFind = true;
                                        }
                                        break;

                                    case 'le':
                                        nowcheck = ( fromdate >= Date.parse(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'lt':
                                        nowcheck = ( fromdate > Date.parse(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'ge':
                                        nowcheck = ( fromdate <= Date.parse(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'gt':
                                        nowcheck = ( fromdate < Date.parse(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'none':
                                    default:
                                        nowcheck = false;
                                        break;
                                }
                            }
                            break;

                        case 'unixtime':
                            let fromdatex = Date.parse( filteritem  );
                            if (isNaN(fromdatex)) {
                                if (chkobj[itemidx].compare === 'eq') {
                                    nowcheck = ( filteritem === uniqdata.uniqitem ) ? true : false;
                                    if (nowcheck) {
                                        isFind = true;
                                    }
                                }else{
                                    nowcheck = false;
                                }
                            }else{
                                switch (chkobj[itemidx].compare) {
                                    case 'eq':
                                        nowcheck = ( fromdatex === 1000*(uniqdata.uniqitem) ) ? true : false;
                                        if (nowcheck) {
                                            isFind = true;
                                        }
                                        break;

                                    case 'le':
                                        nowcheck = ( fromdatex >= 1000*(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'lt':
                                        nowcheck = ( fromdatex > 1000*(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'ge':
                                        nowcheck = ( fromdatex <= 1000*(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'gt':
                                        nowcheck = ( fromdatex < 1000*(uniqdata.uniqitem) ) ? true : false;
                                        break;

                                    case 'none':
                                    default:
                                        nowcheck = false;
                                        break;
                                }
                            }
                            break;

                        case 'string' :
                        default:
                            switch (chkobj[itemidx].compare) {
                                case 'eq':
                                    nowcheck = ( filteritem === uniqdata.uniqitem) ? true : false;
                                    if (nowcheck) {
                                        isFind = true;
                                    }
                                    break;

                                case 'in':
                                    nowcheck = (0 <= uniqdata.uniqitem.indexOf(filteritem)) ? true : false;
                                    break;

                                case 'none':
                                default:
                                    nowcheck = false;
                                    break;
                            }
                            break;
                    }
                    if ( 0 < itemidx ) {
                        if ( nowcheck && uniqdata.checked) {
                            uniqdata.checked = true;
                            isNoCheck = false;
                        } else {
                            uniqdata.checked = false;
                        }
                    } else {
                        if( nowcheck ) {
                            uniqdata.checked = true;
                            isNoCheck = false;
                        } else {
                            uniqdata.checked = false;
                        }
                    }

                    rowidx++;
                    if ( this.filterArray[tidx].length <= rowidx ) {
                        isFind = true;
                        break;
                    }

                }

            }
            //AND検索なので、filterObjで一つもチェックがなかったら、クリアして抜ける
            if (isNoCheck) {
                this.clearAllCheck(false);
                break;
            }

        }
        //2nd redrawTable
        this.redrawTable();
    }
}


/*--------------------------------------------*/
// worker側で複数のfunctionを裁くための関数。https://developer.mozilla.org/ja/docs/Web/API/Web_Workers_API/Using_web_workers
// wokerを作成し、そのworkerに対して行う処理を列挙。
// 主に必要なのは、メインで戻りを処理する複数listenerの登録と、onmessageで返ってきた時にそのlistenerを呼ぶこと。及びsendQueryで該当ファンクションを実行させること。
class queryWorker {
    constructor(url, defaultListener, onError) {
        this.url = url;
        this.onError = onError;
        this.worker = new Worker(url);
        this.listeners = {};
        this.instance = this;
        this.defaultListener = defaultListener || function() {};
        if (onError) {this.worker.onerror = onError;}
    }

    postMessage(message) {
        worker.postMessage(message);
    }

    terminate() {
        this.worker.terminate();
    }

    addListener(name, listener) {
        this.listeners[name] = listener;
        this.worker.onmessage = this.onmessage;
    }

    removeListener(name) {
        delete this.listeners[name];
        this.worker.onmessage = this.onmessage;
    }

    /*
    実際にworkerを実行するsendQuery関数を定義。第一引数に関数名が入ってくる。それをJSONにしてworkerに渡す。
    */
    sendQuery() {
        if (arguments.length < 1) {
            throw new TypeError('QueryableWorker.sendQuery takes at least one argument');
            return;
        }
        // slice.callは正規の書き方。配列風オブジェクトを配列に変換できる。
        // https://www.konosumi.net/entry/2019/05/26/220321
        this.worker.postMessage({
            'queryMethod': arguments[0],
            'queryMethodArguments': Array.prototype.slice.call(arguments, 1)
        });
    }
    // onmessageは、メイン⇔workerでpostMessageがコールされた時にその引数を受け取って稼働する。
    onmessage = function(event) {
        if (event.data instanceof Object &&
            event.data.hasOwnProperty('queryMethodListener') &&
            event.data.hasOwnProperty('queryMethodArguments')) {
            //applyで関数呼び出し。第二引数は配列
            this.listeners[event.data.queryMethodListener].apply(this.instance, event.data.queryMethodArguments);
        } else {
            this.defaultListener.call(this.instance, event.data);
        }
    }.bind(this);
}