const queryableFunctions = {
    // 30万データに耐えるグローバル配列を作成
    createGlobalArray: function( dataObjJson ) {
        let dataObj = JSON.parse( dataObjJson );
        let rawdata = dataObj.body.row;


        //1st make visiAry
        let visiAry = [];
        if ( dataObj.meta.has_header ) {
            rawdata.forEach(( row, rowidx ) => {
                let newcol = [];
                //visible2DataIdx（index0）はdataObjのrowindexと同じにして非表示にする＝dataObjのcolumnと1つずれる=visibleはsortの時なども1列目から始まるのでわかりやすくなる
                newcol[0] = rowidx;
                row.forEach( (col, colidx ) => {
                    if ( ! dataObj.body.header[colidx].isHide ) {
                        newcol.push( col );
                    }
                })
                visiAry.push( newcol );
            })
        } else {
            rawdata.forEach(( row, rowidx ) => {
                let newcol = [];
                newcol[0] = rowidx;
                row.forEach( col => {
                   newcol.push( col );
                });
                visiAry.push( newcol );
            });
        }

        //2nd make filterary
        let revary = reverseArray(visiAry);
        let filary = [revary.length + 1];
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
            if (colidx > 0) {
                let uniqary = uniqArray(colary);
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
                                tmpary.push(revary[0][colobj[rowidx].rowid]);
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
            filary[colidx] = alluniq;
        }
        //end send json
        let visjson = JSON.stringify(visiAry);
        let filjson = JSON.stringify(filary);
        reply('createGlobalArray', visjson, filjson);
    },
    // ソート
    sortGlobalVisibleArray: function(tableidx, sort_order, dataObjJson, gVisibleJson) {
        dataObj  = JSON.parse(dataObjJson);
        gVisiary = JSON.parse(gVisibleJson);
        gVisiary.sort((first, second) => {
            let headeridx = IdxForHeader(tableidx -1, dataObj);
            let sort_result = 0;
            if (0 <= headeridx) {
                switch (dataObj.body.header[headeridx].type) {
                    case 'number':
                    case 'currency':
                    case 'second':
                    case 'unixtime':
                        if(first[tableidx] === null) {
                            first[tableidx] = 0;
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = 0;
                        }
                        sort_result =  first[tableidx] - second[tableidx];
                        break;

                    case 'datetime':
                        if(first[tableidx] === null) {
                            first[tableidx] = 0;
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = 0;
                        }
                        if (Date.parse(first[tableidx]) > Date.parse(second[tableidx])) {
                            sort_result =  1;
                        } else {
                            sort_result = -1;
                        }
                        break;

                    case 'string' :
                    default:
                        if(first[tableidx] === null) {
                            first[tableidx] = '';
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = '';
                        }
                        sort_result = first[tableidx].localeCompare(second[tableidx]);
                        break;

                }
            }
            if ( sort_order === 'dsc' ) {
                    sort_result = sort_result * -1;
            }
            return sort_result;
        });
        //end send json
        let newgVisiJson = JSON.stringify(gVisiary);
        reply('sortGlobalVisibleArray', newgVisiJson);
    },
    sortGolobalFilterArray: function(tableidx, sort_order, dataObjJson, gFilterJson) {
        dataObj  = JSON.parse(dataObjJson);
        gFiltary = JSON.parse(gFilterJson);
        gFiltary[tableidx].sort((first, second) => {
            let headeridx = IdxForHeader(tableidx -1, dataObj );
            let sort_result = 0;
            if (0 <= headeridx) {
                switch (dataObj.body.header[headeridx].type) {
                    case 'number':
                    case 'currency':
                    case 'second':
                    case 'unixtime':
                        if(first[tableidx] === null) {
                            first[tableidx] = 0;
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = 0;
                        }
                        sort_result =  first.uniqitem - second.uniqitem;
                        break;

                    case 'datetime':
                        if(first[tableidx] === null) {
                            first[tableidx] = 0;
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = 0;
                        }
                        if (Date.parse(first.uniqitem) > Date.parse(second.uniqitem)) {
                            sort_result =  1;
                        } else {
                            sort_result = -1;
                        }
                        break;
                    case 'string' :
                    default:
                        if(first[tableidx] === null) {
                            first[tableidx] = '';
                        }
                        if(second[tableidx] === null) {
                            second[tableidx] = '';
                        }
                        sort_result = first.uniqitem.localeCompare(second.uniqitem);
                        break;

                }
            }
            if ( sort_order === 'dsc' ) {
                    sort_result = sort_result * -1;
            }
            return sort_result;
        });
        //end send json
        let newgFiltJson = JSON.stringify(gFiltary);
        reply('sortGolobalFilterArray', newgFiltJson);
    },


    // フィルタsearchされた時に巨大なデータから必要なデータを作成する
    createFilterItem: function() {
    }
};

//own function
function uniqArray ( array ) {
    return Array.from(new Set(array));
}
function IdxForHeader(index, dataObj) {
        let iii = 0;
        let headerIdx = -1;
        dataObj.body.header.some( (head, headidx) => {
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
function idxTable2Header (tableidx, dataObj ) {
        let iii = 1;
        let headerIdx = -1;
        for (let colidx = 0; colidx < dataObj.body.header.length; colidx++) {
            if ( ! dataObj.body.header[colidx].isHide ) {
                if ( Number(tableidx) === Number(iii) ) {
                    headerIdx = colidx;
                    break;
                }
                iii++;
            }
        }
        return headerIdx;
	}
function reverseArray ( array ) {
    return array[ 0 ].map( ( col, colidx ) => {
        return array.map( ( row ) => {return row[ colidx ];});
    });
}
/*
    システム関数
    複数のworkerファンクションに対応するため、メインに用意したQueryableWorkerと通信するためのシステム関数。戻り値replyも複数のパターンをとる。
*/
function defaultReply(message) {
    // メインページが queryableWorker.postMessage() メソッドを直接呼び出したときに限り実行される、デフォルトの PUBLIC 関数
    // ルールがややこしくなるので、基本は使わない
}
// 各関数ごとに独自の返信（postMessage）をするためのreply関数。どの関数の返信かを渡すことがポイント（そうすればメイン側で対応ができるから）
function reply() {
    if (arguments.length < 1) { throw new TypeError('reply - not enough arguments'); return; }
    postMessage({ 'queryMethodListener': arguments[0], 'queryMethodArguments': Array.prototype.slice.call(arguments, 1) });
}

// メインから渡ってきた値を処理する。各関数名が入っているので、それに従って実行する
onmessage = function(oEvent) {
    if (oEvent.data instanceof Object && oEvent.data.hasOwnProperty('queryMethod') && oEvent.data.hasOwnProperty('queryMethodArguments')) {
        queryableFunctions[oEvent.data.queryMethod].apply(self, oEvent.data.queryMethodArguments);
    } else {
        defaultReply(oEvent.data);
    }
};
