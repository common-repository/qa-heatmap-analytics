/**
 * ※ ひとつのプログレスバーを塗り進めるにあたり、ひとつのasync function としてくくる必要があります。
 * 　　（そうしないと、awaitが使えずバーの進みが前後することがあります。あるいは.then()であれば問題ないかと思われます。）
 * ※ プログレスバーasync function内で、await無しのresolve を呼ばないでください。そこでプログレスバーが途切れます。内で実行したい関数はasync化してawaitしてください。
 * 
 * ◆ 共用オブジェクトとして『commonObj』があります。
 * 　（ new drawProgBarのコンストラクト時に　this.commonObj = new Object(); されます。）　
 * 
 **      *       *       *
 * 1) プログレスバーを出すdivを用意します。
 * 
 * 2) let yourProgressBar = new drawProgBar( 'screen'または'embed', divID, バーの上に出す文 );
 * 　※barType: 'screen' にすると、画面中央に出ます。
 *            　'embed'　は内部埋め込み用です。
 * 
 * 3) yourProgressBar.readyProgBarHtml();
 * 
 * 4) 処理やバーを塗る工程を async function としてまとめます。
 *    ペイント待機時間(stayPainting)の初期設定は 0msec です。必要に応じて設定してください。
 *   ---------------------------------
 *    let yourProgressBarAsyncFunction = async function() {
 * 
 *     ■ ループの場合：
 *      ※loopLengthはループ繰り返しの回数です。
 *      ※ループを分割するにあたって、関数利用と手書きの２つの方法があります。手書きのほうが書く手間はありますが処理は速いです。
 *      (I) ループ内処理を関数オブジェクト化します。第一引数に、使用していたforの代数を入れてください。（※async化は不要。）
        *〔例〕
        * for ( let iii = 0; iii < 1000; iii++ )　だったら、iiiを第一引数に入れます。
        *  let moto = function(iii, arg1, arg2) {
        *    //do something, probably using 'iii':
        *    let keisan = iii + 1 * 2 * 3;
        *    if ( iii % 100 === 1 ) {
        *      console.log(arg1 + arg2 +iii);
        *    }
        *  }
 *     (II) ループの分割と処理実行
 *     ● 関数利用：
 *      (i)処理関数、バーの値をセット
        * yourProgressBar.setLoopExecPaintRange(
        *   {loopFunc: moto, loopStart: 0, loopLength: 1000, increase: true}, // {loopFunc: 処理関数, loopStart: ループ代数iiiスタート値, loopLength: ループ回数, increase: ++ならtrue, --ならfalse}   
        *   'loopPainting', // バーの下に出すメッセージ
        *   [0,100] // [％の始,終]
        * );
 *      (ii)実行関数呼び出し 
        * await yourProgressBar.loopExecAndPaint(arg1, arg2, ...args);
        *                
 *     ● 手書き：
 *      (i)バーの値をセット
        *  await setLoopPaintRangeForHandWite( バーの下に出すメッセージ, [％の始,終], loopLength, ペイント待機時間(msec) ); //ここで始点％を塗ります。
        *   ※ setLoopExecPaintRange()でも代用できます。
 *      (ii)ループ分割と処理関数、バーを塗る関数呼び出し 
        *〔例〕※amari, paintRange, intervalはクラス内変数です。
            if ( yourProgressBar.loopExec.loopLength >= yourProgressBar.paintRange ) {
                let kkk = 0;
                if ( yourProgressBar.amari ) {
                    let subdivLoopTarou = async function() {
                        for ( let aaa = 0; aaa < yourProgressBar.amari; aaa ++ ) {
                            moto(kkk);
                            kkk ++;
                        }
                    };
                    await subdivLoopTarou();
                }
                for ( let bbb = 0; bbb < yourProgressBar.paintRange; bbb++ ) {
                    let subdivLoopJirou = async function() {
                        for ( let ccc = 0; ccc < yourProgressBar.interval; ccc++ ) {
                            moto(kkk);
                            kkk ++;
                        }             
                    };
                    await Promise.all( [
                        subdivLoopJirou(),
                        yourProgressBar.paintInLoopForHandWrite()
                    ]);
                }
            }
            else {
                for ( let iii = 0; iii < yourProgressBar.loopLength; iii++ ) {
                    moto(iii);
                    await yourProgressBar.paintInLoopForHandWrite();
                }
            }      

 *  
 *     ■ 通常の処理／指定してバーを塗るとき
 *      await asyncFunction(); //順番が関係あるようであれば、必ずasync化のawaitしてください。
 * 
 *      await yourProgressBar.paintProgBar( バーの下に出すメッセージ, %, ペイント待機時間 );  //直接指定で塗ってみる、というのも可ですが、ペイント待機時間は前のものを引き継いでしまっているので、必要に応じて入れ直すなどしてください。
 * 
 *    ■ ループ以外の場合の関数オブジェクト配列版：
 *       await yourProgressBar.paintAlongSimpleFuncs( functionArray, paintMessageArray, paint%Array, ペイント待機時間 );
 * 
 * 
 *    ◆ 終りに 
 *      await yourProgressBar.removeProgBar(); //ここで100％を塗るようにしています。（100％が重複していても問題ありません。）
 *
 *    ◆ バーが消えた後の処理
 *      show some data, etc.
 * 
 *    }
 *   ---------------------------------
 * 
 * 5) yourProgressBarAsyncFunction(); //実行します。
 * 
 **     *       *       *
 */


/*--------------------------------------------*/
//const waitmsec= msec => new Promise(resolve => setTimeout(() => resolve(), msec));

class asyncFuncObj {
    constructor(theFunction, ...theArguments) {
        this.theFunction = theFunction;
        this.theArguments = theArguments;
    }
    funcExecute = async function() {
        this.theFunction(...this.theArguments);
    };
}
class withPaintAsyncFuncObj {
    constructor(paintupToNum, messageText, theFunction, ...theArguments) {
        this.paintupToNum = paintupToNum;
        this.messageText = messageText;
        this.theFunction = theFunction;
        this.theArguments = theArguments;
    }
    funcExecute = async function() {
        this.theFunction(...this.theArguments);
    };
}
/*--------------------------------------------*/




/*-------------------------------------------- */
class drawProgBar {
    constructor( barType = 'screen', containerId, textAbove ) {
        this.barType = barType;
        this.progContainer = document.getElementById( containerId );
        this.containerId = containerId;
        this.textId = containerId + '-text-above';
        this.barId = containerId + '-bar';
        this.percentId = containerId + '-pct';
        this.messageId = containerId + '-msg';
        this.textAbove = textAbove;
        this.commonObj = new Object();
    }
    progBar;
    progPercent;
    messageText;
    stayPainting; //waiting msec after painting

    loopExec = {};
    //loopLength;
    paintLoopCounter;
    paintRange;
    paintFrom;
    paintTo;
    amari;
    interval;
    increment;
    paintOn;
    asyncedFuncs;
    paintUpOrder;
    messageOrder;

    readyProgBarHtml() {
        this.clearProgBar();
        let proghtml = '';
        if ( this.barType == 'screen' ) {
            proghtml += '<div class="scrn-prog-text-above"><p' + this.textId + '">' + this.textAbove + '</p></div>';
            proghtml += '<div class="scrn-prog-frame"><div id="' + this.barId + '"></div></div>';
            proghtml += '<div class="scrn-prog-text-below">';
            proghtml += '<p class="scrn-prog-below-left" id="' + this.messageId + '"></p>';
            proghtml += '<p class="scrn-prog-below-right" id="' + this.percentId + '">0%</p>';
            proghtml += '</div>';
        } else if ( this.barType == 'embed' ) {
            proghtml += '<div><p id="' + this.textId + '">' + this.textAbove + '</p></div>';
            proghtml += '<div class="embed-prog-frame"><div id="'+ this.barId + '"></div></div>';
            proghtml += '<div class="embed-prog-text-below">';
            proghtml += '<p class="embed-prog-below-left" id="' + this.messageId + '"></p>';
            proghtml += '<p class="embed-prog-below-right" id="' + this.percentId + '">0%</p>';
            proghtml += '</div>';
        }
        
        this.progContainer.insertAdjacentHTML( 'afterbegin', proghtml );
        if ( this.barType == 'screen' ) {
            this.progContainer.classList.add("scrn-prog-shade");
        } else if ( this.barType == 'embed' ) {
            this.progContainer.classList.add("embed-prog-container");
        }
        this.progBar = document.getElementById( this.barId );
        this.progPercent = document.getElementById( this.percentId );
        this.progMessage = document.getElementById( this.messageId );
    }
    clearProgBar() {
        if (this.progContainer) {
            this.progContainer.innerHTML = '';
        }
    }

    async paintProgBar( message, progressing, stayPainting = 0 ) {
        this.progBar.style.width   = progressing + '%';
        this.progPercent.innerText = progressing + '%';
        this.progMessage.innerText = message;
        this.stayPainting = stayPainting;
        await new Promise(resolve => setTimeout(() => resolve(), this.stayPainting));
        }
    async removeProgBar() {
        await this.paintProgBar('', 100);
        await new Promise(resolve => setTimeout(() => resolve(), 300));
        this.progContainer.innerHTML = '';
        if ( this.barType == 'screen') {
            this.progContainer.classList.remove("scrn-prog-shade");
        } else if ( this.barType == 'embed' ) {
            this.progContainer.classList.remove("embed-prog-container");
        }
    }

    /**--------------------------------------
     * ループ分割自動の場合
     */
    async setLoopExecPaintRange(loopExec = {loopFunc: null, loopStart: 0, loopLength:null, increase:true}, messageText, [paintFrom, paintTo], stayPainting = 0 ) {
        this.loopExec = loopExec; 
        this.messageText = messageText;
        this.paintRange = paintTo - paintFrom;
        this.paintFrom = paintFrom;
        this.paintTo = paintTo;
        this.stayPainting = stayPainting;
        this.amari = 0;
        this.interval = 0;
        this.increment = 0;
        this.paintLoopCounter = 0;
        
        if ( this.loopExec.loopLength >= this.paintRange ) {
            this.amari = this.loopExec.loopLength % this.paintRange;
            this.interval = (this.loopExec.loopLength - this.amari)/this.paintRange;
        }
        else { //loopLength < paintRange
            this.amari = this.paintRange % this.loopExec.loopLength;
            this.increment = Math.floor(this.paintRange / this.loopExec.loopLength);
        }

        await this.paintProgBar(this.messageText, this.paintFrom, this.stayPainting);
    }

    async loopExecAndPaint(...theArgs) {
        let loopCounter = this.loopExec.loopStart;
        let loopCounterChange;
        if ( this.loopExec.increase ) {
            loopCounterChange = function() { loopCounter++; }
        }
        else {
            loopCounterChange = function() { loopCounter--; }
        }
        let aaaRange = this.amari;
        let bbbRange = this.paintRange;
        let cccRange = this.interval;
        let dddRange = this.loopExec.loopLength;
        let acdFunc = this.loopExec.loopFunc;
        if ( this.loopExec.loopLength >= this.paintRange ) {
            if ( aaaRange > 0 ) {
                let subdivLoopAmari = async function() {
                    for ( let aaa = 0; aaa < aaaRange; aaa ++ ) {
                        acdFunc( loopCounter, ...theArgs );
                        loopCounterChange();
                    }        
                };
                await subdivLoopAmari();
            }
            for ( let bbb = 0; bbb < bbbRange; bbb++ ) {
                let subdivLoopFuncUnit = async function() {
                    for ( let ccc = 0; ccc < cccRange; ccc++ ) {
                        acdFunc( loopCounter, ...theArgs );
                        loopCounterChange();
                    }             
                };
                this.paintLoopCounter++;
                let percentage =  this.paintLoopCounter + this.paintFrom;
                await Promise.all( [
                    subdivLoopFuncUnit(),
                    this.paintProgBar(this.messageText, percentage, this.stayPainting)
                ]);
            }
        }            
        else { // loopLength < paintRange
            for ( let ddd = 0; ddd < dddRange; ddd ++ ) {
                acdFunc( loopCounter, ...theArgs );
                loopCounterChange();
                this.paintLoopCounter++;
                let percentage;
                if ( this.paintLoopCounter <= dddRange - aaaRange ) {
                    percentage = this.paintLoopCounter * this.increment + this.paintFrom;
                }
                else {
                    percentage =   this.paintLoopCounter * this.increment + this.paintFrom + (this.paintLoopCounter + this.amari - this.loopExec.loopLength);
                }
                await this.paintProgBar(this.messageText, percentage, this.stayPainting);
            }
        }
    }

    /**--------------------------------------
     * 手書きでループ分割する場合
     */
    async setLoopPaintRangeForHandWrite( messageText, [paintFrom, paintTo], loopLength, stayPainting = 0 ) {
        this.messageText = messageText;
        this.paintRange = paintTo - paintFrom;
        this.paintFrom = paintFrom;
        this.paintTo = paintTo;
        this.loopExec.loopLength = loopLength;
        this.stayPainting = stayPainting;
        this.amari = 0;
        this.interval = 0;
        this.increment = 0;
        this.paintLoopCounter = 0;
        
        if ( this.loopExec.loopLength >= this.paintRange ) {
            this.amari = this.loopExec.loopLength % this.paintRange;
            this.interval = (this.loopExec.loopLength - this.amari)/this.paintRange;
        }
        else { //loopLength < paintRange
            this.amari = this.paintRange % this.loopExec.loopLength;
            this.increment = Math.floor(this.paintRange / this.loopExec.loopLength);
        }

        await this.paintProgBar(this.messageText, this.paintFrom, this.stayPainting);
    }

    async paintInLoopForHandWrite() {
        this.paintLoopCounter++;
        let percentage;
        if ( this.loopExec.loopLength >= this.paintRange ) {
            percentage =  this.paintLoopCounter + this.paintFrom;
        }            
        else { // loopLength < paintRange
            if ( this.paintLoopCounter <= this.loopExec.loopLength - this.amari ) {
                percentage = this.paintLoopCounter * this.increment + this.paintFrom;
            }
            else {
                percentage =   this.paintLoopCounter * this.increment + this.paintFrom + (this.paintLoopCounter + this.amari - this.loopExec.loopLength);
            }
        }
        await this.paintProgBar(this.messageText, percentage, this.stayPainting);
    }


    /** ------------------------------------
     * 関数オブジェクト配列用（あまり役に立たないかも）
     */
    async paintAlongSimpleFuncs( asyncedFuncs = [], messageOrder = [], paintUpOrder = [], stayPainting = 0 ) {
        this.asyncedFuncs = asyncedFuncs;
        this.messageOrder = messageOrder;
        this.paintUpOrder = paintUpOrder;
        this.stayPainting = stayPainting;

        for ( let fff = 0; fff < this.asyncedFuncs.length; fff++ ) {
            await this.asyncedFuncs[fff].funcExecute();
            await this.paintProgBar(this.messageOrder[fff], this.paintUpOrder[fff]);           
        }
    }

    async executeFuncsWithPresetPaint( asyncedPaintFuncs = [] ) {
        this.asyncedFuncs = asyncedPaintFuncs;
        for ( let fff = 0; fff < this.asyncedFuncs.length; fff++ ) {
            await this.asyncedFuncs[fff].funcExecute();
            await this.paintProgBar(this.asyncedFuncs[fff].messageText, this.asyncedFuncs[fff].paintupToNum);           
        }
    }


}



