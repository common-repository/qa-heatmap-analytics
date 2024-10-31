<?php
/**
 * QAHMで使用するデータのクラス
 * 
 * 全てのデータファイルは下記のデータを入れる
 * 
 * 1行目に404コード
 * - セキュリティ対策。名称は404
 * 
 * 2行目にヘッダー情報
 * - ひとつしかない情報を入れる。名称はheader
 * 
 * 3行目以降にデータ本体
 * - 複数存在する情報を入れる。名称はbody
 * 
 * を書き込む。
 * 
 * ※データのルール
 * - ヘッダーの先頭は必ずデータのバージョンとする。
 * - 区切り文字はtabにしてtsvの形式で扱う。
 * - セキュリティ対策のため拡張子は.phpにする。
 * 
 * バージョンの差異を吸収する関数も作る予定だが、規模が大きくなる可能性がある。
 * その時はより細分化する予定
 *
 * @package qa_heatmap
 */

class QAHM_File_Data extends QAHM_File_Base {

	// 全てのデータに共通する行番号の指定（column）
	// wrap_get_contentsを使用する場合や、DB、ファイルに保存されているデータを抽出しようとした場合、
	// securityの部分が強制的に削除されている状態のため、この通りにならなくなってきた問題あり
	//const DATA_COLUMN_SECURITY = 0;
	//const DATA_COLUMN_HEADER   = 1;
	//const DATA_COLUMN_BODY     = 2;

	// security部は存在しないものと想定
	const DATA_COLUMN_HEADER   = 0;
	const DATA_COLUMN_BODY     = 1;

	// 以下定数はデータに格納する順序を定義（row）

	/*
	 * ヘッダーの共通データ
	 * 
	 * データの中身を見る際、必ずバージョンを判定する必要がある。
	 * 定数名にはバージョン情報が含まれているため、
	 * まずはこの値でバージョンチェックし使用する定数を選ぶ必要がある。
	 * そのため各定数内にはバージョン情報を含めていない。
	 */ 
	const DATA_HEADER_VERSION = 0;

	// セッションデータ（temp） バージョン1
	const DATA_SESSION_TEMP_1 = array(
		// header
		'TRACKING_ID'       => 1,       // トラッキングID
		'DEVICE_NAME'       => 2,       // デバイス
		'IS_NEW_USER'       => 3,       // 新規ユーザー判定。1なら新規
		'USER_AGENT'        => 4,       // ユーザーエージェント
		'FIRST_REFERRER'    => 5,       // リファラ
		'UTM_SOURCE'        => 6,       // UTM_SOURCE
		'UTM_MEDIUM'        => 7,       // UTM_MEDIUM
		'UTM_CAMPAIGN'      => 8,       // UTM_CAMPAIGN
		'UTM_TERM'          => 9,       // UTM_TERM
		'ORIGINAL_ID'       => 10,      // オリジナルID

		// body
		'PAGE_URL'          => 0,       // URL
		'PAGE_TITLE'        => 1,       // タイトル
		'PAGE_TYPE'         => 2,       // タイプ
		'PAGE_ID'           => 3,       // ID
		'ACCESS_TIME'       => 4,       // 最初にアクセスした時刻（timestamp）
		'PAGE_SPEED'        => 5,       // document.readyが終わるまでの時間
	);

	// セッションデータ（finish） バージョン1
	const DATA_SESSION_FINISH_1 = array(
		// header
		'TRACKING_ID'       => 1,       // トラッキングID
		'DEVICE_NAME'       => 2,       // デバイス名
		'IS_NEW_USER'       => 3,       // 新規ユーザー判定。1なら新規
		'USER_AGENT'        => 4,       // ユーザーエージェント
		'FIRST_REFERRER'    => 5,       // リファラ
		'UTM_SOURCE'        => 6,       // UTM_SOURCE
		'UTM_MEDIUM'        => 7,       // UTM_MEDIUM
		'UTM_CAMPAIGN'      => 8,       // UTM_CAMPAIGN
		'UTM_TERM'          => 9,       // UTM_TERM
		'ORIGINAL_ID'       => 10,      // オリジナルID

		// body
		'PAGE_URL'          => 0,       // URL
		'PAGE_TITLE'        => 1,       // タイトル
		'PAGE_TYPE'         => 2,       // タイプ
		'PAGE_ID'           => 3,       // ID
		'ACCESS_TIME'       => 4,       // 最初にアクセスした時刻（timestamp）
		'PAGE_SPEED'        => 5,       // ページスピード（document.readyが終わるまでの時間）
		'TIME_ON_PAGE'      => 6,       // ページ滞在時間
	);

	// リアルタイムビューデータ バージョン1
	const DATA_REALTIME_VIEW_1 = array(
		// body
		'SESSION_FILE'      => 0,       // セッションファイル名
		'TRACKING_ID'       => 1,       // トラッキングID
		'DEVICE_NAME'       => 2,       // デバイス名
		'IS_NEW_USER'       => 3,       // 新規ユーザー判定
		'USER_AGENT'        => 4,       // ユーザーエージェント
		'FIRST_REFERRER'    => 5,       // リファラ
		'UTM_SOURCE'        => 6,       // UTM_SOURCE
		'UTM_MEDIUM'        => 7,       // UTM_MEDIUM
		'UTM_CAMPAIGN'      => 8,       // UTM_CAMPAIGN
		'UTM_TERM'          => 9,       // UTM_TERM
		'ORIGINAL_ID'       => 10,      // オリジナルID
		'FIRST_ACCESS_TIME' => 11,      // 流入ページの最初にアクセスした時刻（timestamp）
		'FIRST_URL'         => 12,      // 流入ページのURL
		'FIRST_TITLE'       => 13,      // 流入ページのタイトル
		'LAST_EXIT_TIME'    => 14,      // 離脱ページの離脱時刻（timestamp）
		'LAST_URL'          => 15,      // 離脱ページのURL
		'LAST_TITLE'        => 16,      // 離脱ページのタイトル
		'PV_NUM'            => 17,      // PV数
		'TIME_ON_SITE'      => 18,      // サイト滞在時間
	);

	// 位置データ バージョン1
	const DATA_POS_1 = array(
		// body
		'PERCENT_HEIGHT'    => 0,       // 高さを百分率で求めた値
		'TIME_ON_HEIGHT'    => 1,       // 高さあたりの滞在時間（秒）
	);

	// 位置データ バージョン2
	const DATA_POS_2 = array(
		// body
		'STAY_HEIGHT'       => 0,       // 高さを百で割った位置
		'STAY_TIME'         => 1,       // 高さを百で割った位置あたりの滞在時間（秒）
	);

	// クリックデータ バージョン1
	const DATA_CLICK_1 = array(
		// body
		'SELECTOR_NAME'     => 0,       // セレクタ名
		'SELECTOR_X'        => 1,       // セレクタ左上からの相対座標X
		'SELECTOR_Y'        => 2,       // セレクタ左上からの相対座標Y
		'TRANSITION'        => 3,       // 遷移先のURL
	);

	// イベントデータ バージョン1
	const DATA_EVENT_1 = array(
		// header
		'WINDOW_INNER_W'    => 1,       // 解像度W
		'WINDOW_INNER_H'    => 2,       // 解像度H
		'DEVICE_NAME'       => 3,       // デバイス名 削除
		'COUNTRY'           => 4,       // 国 readersに移動するので削除

		// body
		'TYPE'              => 0,       // イベントタイプ
		'TIME'              => 1,       // イベントの発生時刻（読み込み完了からのms）
		'CLICK_X'           => 2,       // クリックイベントのX座標
		'CLICK_Y'           => 3,       // クリックイベントのY座標
		'SCROLL_Y'          => 2,       // スクロールイベントのY座標
		'MOUSE_X'           => 2,       // マウスの移動イベントのX座標
		'MOUSE_Y'           => 3,       // マウスの移動イベントのY座標
		'RESIZE_X'          => 2,       // リサイズイベントのX座標
		'RESIZE_Y'          => 3,       // リサイズイベントのY座標
	);

	// マージしたヒートマップデータ バージョン1
	const DATA_MERGE_CLICK_1 = array(
		// body
		'SELECTOR_NAME'     => 0,       // セレクタ名
		'SELECTOR_X'        => 1,       // セレクタ相対座標X
		'SELECTOR_Y'        => 2,       // セレクタ相対座標Y
	);

	// マージしたアテンションデータ バージョン1
	const DATA_MERGE_ATTENTION_SCROLL_1 = array(
		// body
		'PERCENT'           => 0,       // 100分率した番号位置
		'STAY_TIME'         => 1,       // 100分率した番号位置の平均滞在時間（秒）
		'STAY_NUM'          => 2,       // 100分率した番号位置に滞在した読者の数
		'EXIT_NUM'          => 3,       // 離脱した読者の数
	);

	// マージしたアテンションデータ バージョン2
	const DATA_MERGE_ATTENTION_SCROLL_2 = array(
		// body
		'STAY_HEIGHT'       => 0,       // 高さを百で割った位置
		'STAY_TIME'         => 1,       // 高さを百で割った位置の平均滞在時間（秒）
		'STAY_NUM'          => 2,       // 高さを百で割った位置に滞在した読者の数
		'EXIT_NUM'          => 3,       // この地点で離脱した読者の数
	);
}
