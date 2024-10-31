var qahm = qahm || {};


// 計測不可となっている投稿タイプの記事一覧テーブルを作成
qahm.UnmeasurableTable = '';
qahm.initUnmeasurableTable = function() {
	qahm.UnmeasurableTable = new QATableGenerator();
	qahm.UnmeasurableTable.dataObj.body.header.push({isHide:true});	// url
	qahm.UnmeasurableTable.dataObj.body.header.push({title:qahml10n['page_title'],type:'string',colParm:'style="width: 70%;"',tdHtml:'<a href="%!00" target="_blank" rel="noopener noreferrer">%!me</a>'});
	qahm.UnmeasurableTable.dataObj.body.header.push({title:qahml10n['post_type'],type: 'string',colParm:'style="width: 30%;"'});
	qahm.UnmeasurableTable.visibleSort.index = 2;
	qahm.UnmeasurableTable.visibleSort.order = 'dsc';
	qahm.UnmeasurableTable.targetID = 'unmeasurable-table';
	qahm.UnmeasurableTable.progBarDiv = 'unmeasurable-table-progbar';
	qahm.UnmeasurableTable.dataTRNowBlocks.divheight = 300;
	qahm.UnmeasurableTable.prefix = 'qaunmeasurable';
	let plugindir = qahm.plugin_dir_url;
	qahm.UnmeasurableTable.workerjs = plugindir.replace(/^.*\/\/[^\/]+/, '') + 'js/tableworker.js';
 };


jQuery( function(){
	qahm.initUnmeasurableTable();
	const startTime = performance.now();
	jQuery.ajax( {
		type: 'POST',
		url: qahm.ajax_url,
		dataType : 'json',
		data: {
			'action' : 'qahm_ajax_create_unmeasurable_table',
		}
	}
	).done(
		function( postAjaxAry ){
			if ( ! postAjaxAry ) {
				return;
			}

			document.getElementById('unmeasurable-container').style.display = 'block';
			let postTableAry = [];
			for ( let postIdx = 0; postIdx < postAjaxAry.length; postIdx++ ) {
				postTableAry.push( [ postAjaxAry[postIdx].post_url, postAjaxAry[postIdx].post_title, postAjaxAry[postIdx].post_type ] );
			}

			if (typeof qahm.UnmeasurableTable !== 'undefined' && qahm.UnmeasurableTable !== '') {
				qahm.UnmeasurableTable.rawDataArray = postTableAry;
				if ( ! qahm.UnmeasurableTable.headerTableByID ) {
					qahm.UnmeasurableTable.generateTable();
				} else {
					qahm.UnmeasurableTable.updateTable(true);
				}
			}

			const endTime = performance.now();
			console.log( endTime - startTime );
		}
	).fail(
		function( jqXHR, textStatus, errorThrown ){
			qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
		}
	);
 });


// QAHMのテーブルのtypeをヒートマップ管理画面用に整形
qahm.convTableType = function( type ) {
	switch ( type ) {
		case 'home':		return qahml10n['home'];
		case 'p':			return qahml10n['post'];
		case 'page_id':		return qahml10n['page'];
		case 'cat':			return qahml10n['cat'];
		case 'tag':			return qahml10n['tag'];
		case 'tax':			return qahml10n['tax'];
		default:			return false;
	}
};

// 文字列省略。全角半角対応
qahm.omitStr = function( text, len, truncation ) {
	if (truncation === undefined) {
		truncation = ''; }
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

qahm.getDeviceNumHtml = function( ver_info ) {
	let html = '';
	
	let devNotExistsStyle = 'style="opacity: 0.3; margin-right: 8px;"';
	if ( ver_info && ver_info['data_dsk'] > 0 ){
		html += '<a target="_blank" class="qahm-heatmap-link" data-device_name="dsk" data-version_id="' + ver_info['version_id_dsk'] + '">';
		html += '<span class="dashicons dashicons-desktop"></span>';
		html += '<span>(' + ver_info['data_dsk'] + ')</span>';
		html += '</a>';
	} else {
		html += '<span ' + devNotExistsStyle + '>';
		html += '<span class="dashicons dashicons-desktop"></span>';
		html += '<span>(0)</span>';
		html += '</span>';
	}
	
	if ( ver_info && ver_info['data_tab'] > 0 ){
		html += '<a target="_blank" class="qahm-heatmap-link" data-device_name="tab" data-version_id="' + ver_info['version_id_tab'] + '">';
		html += '<span class="dashicons dashicons-tablet"></span>';
		html += '<span>(' + ver_info['data_tab'] + ')</span>';
		html += '</a>';
	'</a>';
	} else {
		html += '<span ' + devNotExistsStyle + '>';
		html += '<span class="dashicons dashicons-tablet"></span>';
		html += '<span>(0)</span>';
		html += '</span>';
	}
	
	if ( ver_info && ver_info['data_smp'] > 0 ){
		html += '<a target="_blank" class="qahm-heatmap-link" data-device_name="smp" data-version_id="' + ver_info['version_id_smp'] + '">';
		html += '<span class="dashicons dashicons-smartphone"></span>';
		html += '<span>(' + ver_info['data_smp'] + ')</span>';
		html += '</a>';
	} else {
		html += '<span ' + devNotExistsStyle + '>';
		html += '<span class="dashicons dashicons-smartphone"></span>';
		html += '<span>(0)</span>';
		html += '</span>';
	}

	return html;
}

qahm.encodeHTMLSpecialWord = function( str ) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/\'/g, "&#x27;")
        .replace(/`/g, "&#x60");
};
 
qahm.decodeHTMLSpecialWord = function( str ) {
    return str
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, '"')
    .replace(/&#x27;/g, "'")
    .replace(/&#x60/g, "`");
};

// ヒートマップ管理状況のリストを作成
qahm.createList = function() {
	if ( ! document.querySelector( '#qahm-data-list' ) ) {
		return;
	}

	// リスト構築
	let searchTitle    = jQuery( '#qahm-data-search-title' ).val();
	let searchLimit     = jQuery( '#qahm-data-search-limit' ).val();

	jQuery.Deferred(
		function(d) {
			qahm.setProgressBar( 10, 'create list' );

			jQuery.ajax(
				{
					type: 'POST',
					url: qahm.ajax_url,
					dataType : 'json',
					data: {
						'action': 'qahm_ajax_create_heatmap_list',
						'search_title': searchTitle,
						'search_limit': searchLimit,
					},
				}
			).done(
				function( list ){
					qahm.list = list;
					d.resolve();
				}
			).fail(
				function( jqXHR, textStatus, errorThrown ){
					qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
					d.reject();
				}
			);
		}
	)
	.then(
		function(){
			let d = new jQuery.Deferred();

			// 検索件数
			searchLimit = parseInt( searchLimit );
			// if( searchLimit > qahm.list.length ){ searchLimit = qahm.list.length; }

			let sort_find = 0;
			let html = [];
			const data_len = qahm.list.length;
			for ( let i = 0; i < data_len; i++ ) {
				if ( searchTitle ) {
					// タイトルのチェック qahm.list[i]['title']はエンコードされているので、検索文字列もエンコードする
					let check_title = qahm.encodeHTMLSpecialWord( searchTitle );
					if ( qahm.list[i]['title'].toLowerCase().indexOf( check_title.toLowerCase() ) === -1 ) {
						continue;
					}
				}

				let title        = qahm.omitStr( qahm.list[i]['title'], 100, '…' );
				let urlObj       = new URL( qahm.list[i]['url'] );
				let slug         = decodeURIComponent( qahm.list[i]['url'].replace( urlObj.origin, '' ) );
				if ( ! slug ) {
					slug = '/';
				}

				// タイトル
				html[sort_find] = '<tr>';
				html[sort_find] += '<td>';
				html[sort_find] += '<a href="' + qahm.list[i]['url'] + '" target="_blank">' + title + '</a>';
				if ( slug !== undefined ) {
					html[sort_find] += '<br>' + '<span style="font-size:70%;">' + slug + '</span>';
				}
				html[sort_find] += '</td>';

				// タイプ
				html[sort_find] += '<td>' + qahm.convTableType( qahm.list[i]['type'] ) + '</td>';

				// 最終更新日
				html[sort_find] += '<td>' + qahm.list[i]['post_modified'] + '</td>';

				// バージョン切替
				html[sort_find] += '<td>';
				let ref_data = ' data-list_index="' + i + '"';
				ref_data += ' data-wp_qa_type="' + qahm.list[i]['type'] + '"';
				ref_data += ' data-wp_qa_id="' + qahm.list[i]['id'] + '"';
				ref_data += ' data-url="' + qahm.list[i]['url'] + '"';
				ref_data += ' data-title="' + qahm.list[i]['title'] + '"';
				html[sort_find] += '<span id="qahm-refresh-icon-' + i + '" class="qahm-refresh-icon"' + ref_data + ' style="cursor: pointer;"><i class="fas fa-sync"></i></span>';

				html[sort_find] += '<span id="qahm-refresh-text-' + i + '" style="color: #ff5353; margin-left: 6px;">';
				if ( qahm.list[i]['version_refresh'] ) {
					html[sort_find] += qahml10n['switch'] + ' Ver.' + qahm.list[i]['version_refresh'];
				}
				html[sort_find] += '</span>';
				html[sort_find] += '</td>';

				html[sort_find] += '</tr>';

				sort_find++;
				if ( sort_find >= searchLimit ) {
					break;
				}
			}

			// 作成したhtmlを分割
			// htmlの追加が環境によっては一番処理時間がかかるので、
			// htmlは分割して挿入してプログレスバーで進捗を更新している
			const html_split = 5;
			let html_append  = [];
			const html_len   = html.length;
			const split_line = html_len / html_split;

			for ( let i = 0; i < html_len; i++ ) {
				let idx = Math.floor( i / split_line );
				if ( html_append[idx] ) {
					html_append[idx] += html[i];
				} else {
					html_append[idx] = html[i];
				}
			}

			// リストの追加
			jQuery( '#qahm-data-list > tbody' ).empty();
			let prom = jQuery.Deferred().resolve().promise();
			for (let i = 0; i < html_split; i++) {
				prom = prom.then(
					(function() { return function() {
						let d_list = jQuery.Deferred();
						jQuery( '#qahm-data-list > tbody' ).append( html_append[i] );
						qahm.setProgressBar(
							40 + (i + 1) * (Math.floor( 50 / html_split )),
							'append list',
							function(){
								d_list.resolve();
								if ( i === html_split - 1 ) {
									d.resolve();
								}
							}
						);
						return d_list.promise();
					};})()
				);
			}

			return d.promise();
		}
	)
	.then(
		function() {
			// 実績処理はデータ数が必要なので削除
			qahm.setProgressBar( 100 );
		}
	);
};

qahm.loadScreen.promise()
.then(
	function() {

		// バージョン切り替え
		jQuery( document ).on(
			'click',
			'.qahm-refresh-icon',
			function(){
				let list_index = jQuery(this).data('list_index');
				let wp_qa_type = jQuery(this).data('wp_qa_type');
				let wp_qa_id   = jQuery(this).data('wp_qa_id');
				let url        = jQuery(this).data('url');
				let title      = jQuery(this).data('title');

				// urlに大文字が入っていたら警告
				if ( ! qahm.isLower( url ) ) {
					AlertMessage.alert( qahml10n['version_switch_inte_title'], qahml10n['version_switch_inte_text'], 'warning' );
					return;
				}

				let start_text = '<div id="swal2-content" class="swal2-html-container" style="display: block;">';
				start_text += qahml10n['version_switch_start_text_1'];
				start_text += '<br><span style="font-size:80%;color:gray">';
				start_text += qahml10n['version_switch_start_text_2'];
				start_text += '</span></div>';
				
				AlertMessage.confirm(
					qahml10n['version_switch'],
					start_text,
					'info',
					function ( _enter ) {
						if ( _enter ) {
							qahm.showLoadIcon();
							jQuery.ajax(
								{
									type: 'POST',
									url: qahm.ajax_url,
									dataType : 'json',
									data: {
										'action'     : 'qahm_ajax_refresh_version',
										'nonce'      : qahm.nonce_refresh,
										'wp_qa_type' : wp_qa_type,
										'wp_qa_id'   : wp_qa_id,
										'url'        : url,
										'title'      : title
									},
								}
							).done(
								function( data ){
									if ( data['result'] ) {
										AlertMessage.alert(
											qahml10n['version_switch'],
											data['version_switch_success_text'],
											'success'
											);

										qahm.list[list_index]['version_refresh'] = data['version'];
										jQuery( '#qahm-refresh-text-' + list_index ).text( qahml10n['switch'] + ' Ver.' + data['version'] );

									} else {
										AlertMessage.alert(
											qahml10n['version_switch_error_text'],
											'error'
											);
									}
									qahm.hideLoadIcon();
								}
							).fail(
								function( jqXHR, textStatus, errorThrown ){
									qahm.log_ajax_error( jqXHR, textStatus, errorThrown );
									AlertMessage.alert(
										qahml10n['version_switch'],
										qahml10n['version_switch_error_text'],
										'error'
										);
									qahm.hideLoadIcon();
								}
							);
						}
					}
				);
			}
		);

		qahm.createList();
	}
);

