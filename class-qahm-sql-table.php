<?php
/**
 * QAデータベースのテーブル構造に変化を与えるSQL文をまとめるクラス
 * @package qa_heatmap
 */

class QAHM_Sql_Table extends QAHM_Base {
	
	// qa_readersテーブルの作成SQLを返す
	public function get_qa_readers_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- パーティションはunixtimeで問題のあるとされる2038/1/19まで=2037年12月まで作成する。reader,pv_log,version_histの3つで使用する。
		-- qa_readers
		drop table if exists {$wpdb->prefix}qa_readers;
		create table {$wpdb->prefix}qa_readers
		(
			reader_id int auto_increment,
			qa_id char(28) not null,
			original_id text null,
			UAos varchar(32) null,
			UAbrowser varchar(32) null,
			update_date date not null,
			primary key (reader_id, update_date),
		unique key (qa_id, update_date)
		) {$charset_collate} 
		partition by range COLUMNS(update_date) (
			partition  p202001 values less than ('2020-02-01'),
			partition  p202002 values less than ('2020-03-01'),
			partition  p202003 values less than ('2020-04-01'),
			partition  p202004 values less than ('2020-05-01'),
			partition  p202005 values less than ('2020-06-01'),
			partition  p202006 values less than ('2020-07-01'),
			partition  p202007 values less than ('2020-08-01'),
			partition  p202008 values less than ('2020-09-01'),
			partition  p202009 values less than ('2020-10-01'),
			partition  p202010 values less than ('2020-11-01'),
			partition  p202011 values less than ('2020-12-01'),
			partition  p202012 values less than ('2021-01-01'),
			partition  p202101 values less than ('2021-02-01'),
			partition  p202102 values less than ('2021-03-01'),
			partition  p202103 values less than ('2021-04-01'),
			partition  p202104 values less than ('2021-05-01'),
			partition  p202105 values less than ('2021-06-01'),
			partition  p202106 values less than ('2021-07-01'),
			partition  p202107 values less than ('2021-08-01'),
			partition  p202108 values less than ('2021-09-01'),
			partition  p202109 values less than ('2021-10-01'),
			partition  p202110 values less than ('2021-11-01'),
			partition  p202111 values less than ('2021-12-01'),
			partition  p202112 values less than ('2022-01-01'),
			partition  p202201 values less than ('2022-02-01'),
			partition  p202202 values less than ('2022-03-01'),
			partition  p202203 values less than ('2022-04-01'),
			partition  p202204 values less than ('2022-05-01'),
			partition  p202205 values less than ('2022-06-01'),
			partition  p202206 values less than ('2022-07-01'),
			partition  p202207 values less than ('2022-08-01'),
			partition  p202208 values less than ('2022-09-01'),
			partition  p202209 values less than ('2022-10-01'),
			partition  p202210 values less than ('2022-11-01'),
			partition  p202211 values less than ('2022-12-01'),
			partition  p202212 values less than ('2023-01-01'),
			partition  p202301 values less than ('2023-02-01'),
			partition  p202302 values less than ('2023-03-01'),
			partition  p202303 values less than ('2023-04-01'),
			partition  p202304 values less than ('2023-05-01'),
			partition  p202305 values less than ('2023-06-01'),
			partition  p202306 values less than ('2023-07-01'),
			partition  p202307 values less than ('2023-08-01'),
			partition  p202308 values less than ('2023-09-01'),
			partition  p202309 values less than ('2023-10-01'),
			partition  p202310 values less than ('2023-11-01'),
			partition  p202311 values less than ('2023-12-01'),
			partition  p202312 values less than ('2024-01-01'),
			partition  p202401 values less than ('2024-02-01'),
			partition  p202402 values less than ('2024-03-01'),
			partition  p202403 values less than ('2024-04-01'),
			partition  p202404 values less than ('2024-05-01'),
			partition  p202405 values less than ('2024-06-01'),
			partition  p202406 values less than ('2024-07-01'),
			partition  p202407 values less than ('2024-08-01'),
			partition  p202408 values less than ('2024-09-01'),
			partition  p202409 values less than ('2024-10-01'),
			partition  p202410 values less than ('2024-11-01'),
			partition  p202411 values less than ('2024-12-01'),
			partition  p202412 values less than ('2025-01-01'),
			partition  p202501 values less than ('2025-02-01'),
			partition  p202502 values less than ('2025-03-01'),
			partition  p202503 values less than ('2025-04-01'),
			partition  p202504 values less than ('2025-05-01'),
			partition  p202505 values less than ('2025-06-01'),
			partition  p202506 values less than ('2025-07-01'),
			partition  p202507 values less than ('2025-08-01'),
			partition  p202508 values less than ('2025-09-01'),
			partition  p202509 values less than ('2025-10-01'),
			partition  p202510 values less than ('2025-11-01'),
			partition  p202511 values less than ('2025-12-01'),
			partition  p202512 values less than ('2026-01-01'),
			partition  p202601 values less than ('2026-02-01'),
			partition  p202602 values less than ('2026-03-01'),
			partition  p202603 values less than ('2026-04-01'),
			partition  p202604 values less than ('2026-05-01'),
			partition  p202605 values less than ('2026-06-01'),
			partition  p202606 values less than ('2026-07-01'),
			partition  p202607 values less than ('2026-08-01'),
			partition  p202608 values less than ('2026-09-01'),
			partition  p202609 values less than ('2026-10-01'),
			partition  p202610 values less than ('2026-11-01'),
			partition  p202611 values less than ('2026-12-01'),
			partition  p202612 values less than ('2027-01-01'),
			partition  p202701 values less than ('2027-02-01'),
			partition  p202702 values less than ('2027-03-01'),
			partition  p202703 values less than ('2027-04-01'),
			partition  p202704 values less than ('2027-05-01'),
			partition  p202705 values less than ('2027-06-01'),
			partition  p202706 values less than ('2027-07-01'),
			partition  p202707 values less than ('2027-08-01'),
			partition  p202708 values less than ('2027-09-01'),
			partition  p202709 values less than ('2027-10-01'),
			partition  p202710 values less than ('2027-11-01'),
			partition  p202711 values less than ('2027-12-01'),
			partition  p202712 values less than ('2028-01-01'),
			partition  p202801 values less than ('2028-02-01'),
			partition  p202802 values less than ('2028-03-01'),
			partition  p202803 values less than ('2028-04-01'),
			partition  p202804 values less than ('2028-05-01'),
			partition  p202805 values less than ('2028-06-01'),
			partition  p202806 values less than ('2028-07-01'),
			partition  p202807 values less than ('2028-08-01'),
			partition  p202808 values less than ('2028-09-01'),
			partition  p202809 values less than ('2028-10-01'),
			partition  p202810 values less than ('2028-11-01'),
			partition  p202811 values less than ('2028-12-01'),
			partition  p202812 values less than ('2029-01-01'),
			partition  p202901 values less than ('2029-02-01'),
			partition  p202902 values less than ('2029-03-01'),
			partition  p202903 values less than ('2029-04-01'),
			partition  p202904 values less than ('2029-05-01'),
			partition  p202905 values less than ('2029-06-01'),
			partition  p202906 values less than ('2029-07-01'),
			partition  p202907 values less than ('2029-08-01'),
			partition  p202908 values less than ('2029-09-01'),
			partition  p202909 values less than ('2029-10-01'),
			partition  p202910 values less than ('2029-11-01'),
			partition  p202911 values less than ('2029-12-01'),
			partition  p202912 values less than ('2030-01-01'),
			partition  p203001 values less than ('2030-02-01'),
			partition  p203002 values less than ('2030-03-01'),
			partition  p203003 values less than ('2030-04-01'),
			partition  p203004 values less than ('2030-05-01'),
			partition  p203005 values less than ('2030-06-01'),
			partition  p203006 values less than ('2030-07-01'),
			partition  p203007 values less than ('2030-08-01'),
			partition  p203008 values less than ('2030-09-01'),
			partition  p203009 values less than ('2030-10-01'),
			partition  p203010 values less than ('2030-11-01'),
			partition  p203011 values less than ('2030-12-01'),
			partition  p203012 values less than ('2031-01-01'),
			partition  p203101 values less than ('2031-02-01'),
			partition  p203102 values less than ('2031-03-01'),
			partition  p203103 values less than ('2031-04-01'),
			partition  p203104 values less than ('2031-05-01'),
			partition  p203105 values less than ('2031-06-01'),
			partition  p203106 values less than ('2031-07-01'),
			partition  p203107 values less than ('2031-08-01'),
			partition  p203108 values less than ('2031-09-01'),
			partition  p203109 values less than ('2031-10-01'),
			partition  p203110 values less than ('2031-11-01'),
			partition  p203111 values less than ('2031-12-01'),
			partition  p203112 values less than ('2032-01-01'),
			partition  p203201 values less than ('2032-02-01'),
			partition  p203202 values less than ('2032-03-01'),
			partition  p203203 values less than ('2032-04-01'),
			partition  p203204 values less than ('2032-05-01'),
			partition  p203205 values less than ('2032-06-01'),
			partition  p203206 values less than ('2032-07-01'),
			partition  p203207 values less than ('2032-08-01'),
			partition  p203208 values less than ('2032-09-01'),
			partition  p203209 values less than ('2032-10-01'),
			partition  p203210 values less than ('2032-11-01'),
			partition  p203211 values less than ('2032-12-01'),
			partition  p203212 values less than ('2033-01-01'),
			partition  p203301 values less than ('2033-02-01'),
			partition  p203302 values less than ('2033-03-01'),
			partition  p203303 values less than ('2033-04-01'),
			partition  p203304 values less than ('2033-05-01'),
			partition  p203305 values less than ('2033-06-01'),
			partition  p203306 values less than ('2033-07-01'),
			partition  p203307 values less than ('2033-08-01'),
			partition  p203308 values less than ('2033-09-01'),
			partition  p203309 values less than ('2033-10-01'),
			partition  p203310 values less than ('2033-11-01'),
			partition  p203311 values less than ('2033-12-01'),
			partition  p203312 values less than ('2034-01-01'),
			partition  p203401 values less than ('2034-02-01'),
			partition  p203402 values less than ('2034-03-01'),
			partition  p203403 values less than ('2034-04-01'),
			partition  p203404 values less than ('2034-05-01'),
			partition  p203405 values less than ('2034-06-01'),
			partition  p203406 values less than ('2034-07-01'),
			partition  p203407 values less than ('2034-08-01'),
			partition  p203408 values less than ('2034-09-01'),
			partition  p203409 values less than ('2034-10-01'),
			partition  p203410 values less than ('2034-11-01'),
			partition  p203411 values less than ('2034-12-01'),
			partition  p203412 values less than ('2035-01-01'),
			partition  p203501 values less than ('2035-02-01'),
			partition  p203502 values less than ('2035-03-01'),
			partition  p203503 values less than ('2035-04-01'),
			partition  p203504 values less than ('2035-05-01'),
			partition  p203505 values less than ('2035-06-01'),
			partition  p203506 values less than ('2035-07-01'),
			partition  p203507 values less than ('2035-08-01'),
			partition  p203508 values less than ('2035-09-01'),
			partition  p203509 values less than ('2035-10-01'),
			partition  p203510 values less than ('2035-11-01'),
			partition  p203511 values less than ('2035-12-01'),
			partition  p203512 values less than ('2036-01-01'),
			partition  p203601 values less than ('2036-02-01'),
			partition  p203602 values less than ('2036-03-01'),
			partition  p203603 values less than ('2036-04-01'),
			partition  p203604 values less than ('2036-05-01'),
			partition  p203605 values less than ('2036-06-01'),
			partition  p203606 values less than ('2036-07-01'),
			partition  p203607 values less than ('2036-08-01'),
			partition  p203608 values less than ('2036-09-01'),
			partition  p203609 values less than ('2036-10-01'),
			partition  p203610 values less than ('2036-11-01'),
			partition  p203611 values less than ('2036-12-01'),
			partition  p203612 values less than ('2037-01-01'),
			partition  p203701 values less than ('2037-02-01'),
			partition  p203702 values less than ('2037-03-01'),
			partition  p203703 values less than ('2037-04-01'),
			partition  p203704 values less than ('2037-05-01'),
			partition  p203705 values less than ('2037-06-01'),
			partition  p203706 values less than ('2037-07-01'),
			partition  p203707 values less than ('2037-08-01'),
			partition  p203708 values less than ('2037-09-01'),
			partition  p203709 values less than ('2037-10-01'),
			partition  p203710 values less than ('2037-11-01'),
			partition  p203711 values less than ('2037-12-01'),
			partition  p203712 values less than ('2038-01-01'),
		PARTITION pmax VALUES LESS THAN (MAXVALUE)
		);
		
		create index {$wpdb->prefix}qa_readers_UAos_index
			on {$wpdb->prefix}qa_readers (UAos)
		;
		
EOQ;
	}


	// qa_readersテーブルの作成SQLを返す
	public function get_qa_pages_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_pages
		drop table if exists {$wpdb->prefix}qa_pages;
		create table {$wpdb->prefix}qa_pages
		(
			page_id int auto_increment
				primary key,
			tracking_id char(16) null,
			wp_qa_type varchar(20) null,
			wp_qa_id bigint null,
			url text null,
			url_hash char(16) null,
			title varchar(128) null,
			update_date date not null,
			constraint qa_pages_page_id_uindex
				unique (page_id)
		) {$charset_collate}
		;
		
		create index {$wpdb->prefix}qa_pages_uri_hash_index
			on {$wpdb->prefix}qa_pages (url_hash)
		;
EOQ;
	}


	
	// qa_utm_mediaテーブルの作成SQLを返す
	public function get_qa_utm_media_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_utm_media
		drop table if exists {$wpdb->prefix}qa_utm_media;
		create table {$wpdb->prefix}qa_utm_media
		(
			medium_id smallint(6) auto_increment
				primary key,
			utm_medium varchar(64) null,
			description varchar(256) null,
			constraint qa_utm_medium_medium_id_uindex
				unique (medium_id),
			constraint qa_utm_medium_utm_medium_uindex
				unique (utm_medium)
		) {$charset_collate}
		;
		
		-- inital set
		delete from {$wpdb->prefix}qa_utm_media;
		alter table {$wpdb->prefix}qa_utm_media auto_increment = 1;
		INSERT INTO {$wpdb->prefix}qa_utm_media (utm_medium, description) VALUES
		('organic','Organic Search'),
		('cpc','Paid Search'),
		('ppc','Paid Search'),
		('paidsearch','Paid Search'),
		('display','Display'),
		('cpm','Display'),
		('banner','Display'),
		('cpv','Other Advertising'),
		('cpa','Other Advertising'),
		('cpp','Other Advertising'),
		('content-text','Other Advertising'),
		('affiliate','Affiliate'),
		('social','Social'),
		('social-network','Social'),
		('social-media','Social'),
		('sm','Social'),
		('social network','Social'),
		('social media','Social'),
		('email','Email')
		;
EOQ;
	}


	
	// qa_readersテーブルの作成SQLを返す
	public function get_qa_utm_sources_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_utm_sources
		drop table if exists {$wpdb->prefix}qa_utm_sources;
		create table {$wpdb->prefix}qa_utm_sources
		(
			source_id smallint(6) auto_increment
				primary key,
			utm_source varchar(128) null,
			referer text null,
			source_domain varchar(128) null,
			medium_id int null,
			utm_term varchar(255) null,
			keyword varchar(255) null,
			constraint qa_sources_source_id_uindex
				unique (source_id)
		) {$charset_collate}
		;
		
		create index qa_sources_souce_domain_index
			on {$wpdb->prefix}qa_utm_sources (source_domain)
		;
		
		delete from {$wpdb->prefix}qa_utm_sources;
		alter table {$wpdb->prefix}qa_utm_sources auto_increment = 1;
		INSERT INTO {$wpdb->prefix}qa_utm_sources (utm_source, source_domain,referer,medium_id) VALUES
		('google','www.google.com','https://www.google.com/',1),
		('yahoo.co.jp','search.yahoo.co.jp','https://search.yahoo.co.jp/',1),
		('yahoo','search.yahoo.com','https://search.yahoo.com/',1),
		('bing','www.bing.com','https://www.bing.com/',1),
		('goo.ne.jp','search.goo.ne.jp','https://search.goo.ne.jp/',1),
		('google','www.google.com','https://www.google.com/',2),
		('twitter','twitter.com','https://twitter.com/',13),
		('twitter','t.co','https://t.co/',13),
		('facebook','facebook.com','https://facebook.com/',13),
		('instagram','instagram.com','https://instagram.com/',13),
		('google','www.google.ac','https://www.google.ac',1),
		('google','www.google.ad','https://www.google.ad',1),
		('google','www.google.ae','https://www.google.ae',1),
		('google','www.google.com.af','https://www.google.com.af',1),
		('google','www.google.com.ag','https://www.google.com.ag',1),
		('google','www.google.off.ai','https://www.google.off.ai',1),
		('google','www.google.am','https://www.google.am',1),
		('google','www.google.co.ao','https://www.google.co.ao',1),
		('google','www.google.com.ar','https://www.google.com.ar',1),
		('google','www.google.as','https://www.google.as',1),
		('google','www.google.at','https://www.google.at',1),
		('google','www.google.com.au','https://www.google.com.au',1),
		('google','www.google.az','https://www.google.az',1),
		('google','www.google.ba','https://www.google.ba',1),
		('google','www.google.com.bd','https://www.google.com.bd',1),
		('google','www.google.be','https://www.google.be',1),
		('google','www.google.bg','https://www.google.bg',1),
		('google','www.google.com.bh','https://www.google.com.bh',1),
		('google','www.google.bi','https://www.google.bi',1),
		('google','www.google.bj','https://www.google.bj',1),
		('google','www.google.com.bn','https://www.google.com.bn',1),
		('google','www.google.com.bo','https://www.google.com.bo',1),
		('google','www.google.com.br','https://www.google.com.br',1),
		('google','www.google.bs','https://www.google.bs',1),
		('google','www.google.co.bw','https://www.google.co.bw',1),
		('google','www.google.com.by','https://www.google.com.by',1),
		('google','www.google.com.bz','https://www.google.com.bz',1),
		('google','www.google.ca','https://www.google.ca',1),
		('google','www.google.cd','https://www.google.cd',1),
		('google','www.google.cf/','https://www.google.cf/',1),
		('google','www.google.cg','https://www.google.cg',1),
		('google','www.google.ch','https://www.google.ch',1),
		('google','www.google.ci','https://www.google.ci',1),
		('google','www.google.co.ck','https://www.google.co.ck',1),
		('google','www.google.cl','https://www.google.cl',1),
		('google','www.google.cn','https://www.google.cn',1),
		('google','www.google.com.co','https://www.google.com.co',1),
		('google','www.google.co.cr','https://www.google.co.cr',1),
		('google','www.google.com.cu','https://www.google.com.cu',1),
		('google','www.google.com.cy','https://www.google.com.cy',1),
		('google','www.google.cz','https://www.google.cz',1),
		('google','www.google.de','https://www.google.de',1),
		('google','www.google.dj','https://www.google.dj',1),
		('google','www.google.dk','https://www.google.dk',1),
		('google','www.google.dm','https://www.google.dm',1),
		('google','www.google.com.do','https://www.google.com.do',1),
		('google','www.google.dz','https://www.google.dz',1),
		('google','www.google.com.ec','https://www.google.com.ec',1),
		('google','www.google.ee','https://www.google.ee',1),
		('google','www.google.com.eg','https://www.google.com.eg',1),
		('google','www.google.es','https://www.google.es',1),
		('google','www.google.com.et','https://www.google.com.et',1),
		('google','www.google.fi','https://www.google.fi',1),
		('google','www.google.com.fj','https://www.google.com.fj',1),
		('google','www.google.fm','https://www.google.fm',1),
		('google','www.google.fr','https://www.google.fr',1),
		('google','www.google.gd','https://www.google.gd',1),
		('google','www.google.ge','https://www.google.ge',1),
		('google','www.google.gf','https://www.google.gf',1),
		('google','www.google.gg','https://www.google.gg',1),
		('google','www.google.com.gh','https://www.google.com.gh',1),
		('google','www.google.com.gi','https://www.google.com.gi',1),
		('google','www.google.gl','https://www.google.gl',1),
		('google','www.google.gm','https://www.google.gm',1),
		('google','www.google.gp','https://www.google.gp',1),
		('google','www.google.gr','https://www.google.gr',1),
		('google','www.google.com.gt','https://www.google.com.gt',1),
		('google','www.google.gy','https://www.google.gy',1),
		('google','www.google.com.hk','https://www.google.com.hk',1),
		('google','www.google.hn','https://www.google.hn',1),
		('google','www.google.hr','https://www.google.hr',1),
		('google','www.google.ht','https://www.google.ht',1),
		('google','www.google.co.hu','https://www.google.co.hu',1),
		('google','www.google.co.id','https://www.google.co.id',1),
		('google','www.google.ie','https://www.google.ie',1),
		('google','www.google.co.il','https://www.google.co.il',1),
		('google','www.google.co.im','https://www.google.co.im',1),
		('google','www.google.co.in','https://www.google.co.in',1),
		('google','www.google.is','https://www.google.is',1),
		('google','www.google.it','https://www.google.it',1),
		('google','www.google.co.je','https://www.google.co.je',1),
		('google','www.google.com.jm','https://www.google.com.jm',1),
		('google','www.google.jo','https://www.google.jo',1),
		('google','www.google.co.jp','https://www.google.co.jp',1),
		('google','www.google.co.ke','https://www.google.co.ke',1),
		('google','www.google.kg','https://www.google.kg',1),
		('google','www.google.com.kh/','https://www.google.com.kh/',1),
		('google','www.google.ki','https://www.google.ki',1),
		('google','www.google.co.kr','https://www.google.co.kr',1),
		('google','www.google.com.kw','https://www.google.com.kw',1),
		('google','www.google.kz','https://www.google.kz',1),
		('google','www.google.la','https://www.google.la',1),
		('google','www.google.com.lb','https://www.google.com.lb',1),
		('google','www.google.com.lc','https://www.google.com.lc',1),
		('google','www.google.li','https://www.google.li',1),
		('google','www.google.lk','https://www.google.lk',1),
		('google','www.google.co.ls','https://www.google.co.ls',1),
		('google','www.google.lt','https://www.google.lt',1),
		('google','www.google.lu','https://www.google.lu',1),
		('google','www.google.lv','https://www.google.lv',1),
		('google','www.google.com.ly','https://www.google.com.ly',1),
		('google','www.google.co.ma','https://www.google.co.ma',1),
		('google','www.google.md','https://www.google.md',1),
		('google','www.google.me','https://www.google.me',1),
		('google','www.google.mg','https://www.google.mg',1),
		('google','www.google.com.mk','https://www.google.com.mk',1),
		('google','www.google.mn','https://www.google.mn',1),
		('google','www.google.ms','https://www.google.ms',1),
		('google','www.google.com.mt','https://www.google.com.mt',1),
		('google','www.google.mu','https://www.google.mu',1),
		('google','www.google.mv','https://www.google.mv',1),
		('google','www.google.mw','https://www.google.mw',1),
		('google','www.google.com.mx','https://www.google.com.mx',1),
		('google','www.google.com.my','https://www.google.com.my',1),
		('google','www.google.co.mz','https://www.google.co.mz',1),
		('google','www.google.com.na','https://www.google.com.na',1),
		('google','www.google.com.nf','https://www.google.com.nf',1),
		('google','www.google.com.ng','https://www.google.com.ng',1),
		('google','www.google.com.ni','https://www.google.com.ni',1),
		('google','www.google.nl','https://www.google.nl',1),
		('google','www.google.no','https://www.google.no',1),
		('google','www.google.com.np','https://www.google.com.np',1),
		('google','www.google.nr','https://www.google.nr',1),
		('google','www.google.nu','https://www.google.nu',1),
		('google','www.google.co.nz','https://www.google.co.nz',1),
		('google','www.google.com.om','https://www.google.com.om',1),
		('google','www.google.com.pa','https://www.google.com.pa',1),
		('google','www.google.com.pe','https://www.google.com.pe',1),
		('google','www.google.com.ph','https://www.google.com.ph',1),
		('google','www.google.com.pk','https://www.google.com.pk',1),
		('google','www.google.pl','https://www.google.pl',1),
		('google','www.google.pn','https://www.google.pn',1),
		('google','www.google.com.pr','https://www.google.com.pr',1),
		('google','www.google.ps/','https://www.google.ps/',1),
		('google','www.google.pt','https://www.google.pt',1),
		('google','www.google.com.py','https://www.google.com.py',1),
		('google','www.google.com.qa','https://www.google.com.qa',1),
		('google','www.google.ro','https://www.google.ro',1),
		('google','www.google.rs','https://www.google.rs',1),
		('google','www.google.ru','https://www.google.ru',1),
		('google','www.google.rw','https://www.google.rw',1),
		('google','www.google.com.sa','https://www.google.com.sa',1),
		('google','www.google.com.sb','https://www.google.com.sb',1),
		('google','www.google.sc','https://www.google.sc',1),
		('google','www.google.se','https://www.google.se',1),
		('google','www.google.com.sg','https://www.google.com.sg',1),
		('google','www.google.sh','https://www.google.sh',1),
		('google','www.google.si','https://www.google.si',1),
		('google','www.google.sk','https://www.google.sk',1),
		('google','www.google.com.sl','https://www.google.com.sl',1),
		('google','www.google.sm','https://www.google.sm',1),
		('google','www.google.sn','https://www.google.sn',1),
		('google','www.google.st','https://www.google.st',1),
		('google','www.google.com.sv','https://www.google.com.sv',1),
		('google','www.google.co.th','https://www.google.co.th',1),
		('google','www.google.com.tj','https://www.google.com.tj',1),
		('google','www.google.tk','https://www.google.tk',1),
		('google','www.google.tm','https://www.google.tm',1),
		('google','www.google.to','https://www.google.to',1),
		('google','www.google.tp','https://www.google.tp',1),
		('google','www.google.com.tr','https://www.google.com.tr',1),
		('google','www.google.tt','https://www.google.tt',1),
		('google','www.google.com.tw','https://www.google.com.tw',1),
		('google','www.google.co.tz','https://www.google.co.tz',1),
		('google','www.google.com.ua','https://www.google.com.ua',1),
		('google','www.google.co.ug','https://www.google.co.ug',1),
		('google','www.google.co.uk','https://www.google.co.uk',1),
		('google','www.google.com.uy','https://www.google.com.uy',1),
		('google','www.google.co.uz','https://www.google.co.uz',1),
		('google','www.google.com.vc','https://www.google.com.vc',1),
		('google','www.google.co.ve','https://www.google.co.ve',1),
		('google','www.google.vg','https://www.google.vg',1),
		('google','www.google.co.vi','https://www.google.co.vi',1),
		('google','www.google.com.vn','https://www.google.com.vn',1),
		('google','www.google.vu','https://www.google.vu',1),
		('google','www.google.ws','https://www.google.ws',1),
		('google','www.google.co.za','https://www.google.co.za',1),
		('google','www.google.co.zm','https://www.google.co.zm',1),
		('google','www.google.co.zw','https://www.google.co.zw',1)
		;
EOQ;
	}


	
	// qa_readersテーブルの作成SQLを返す
	public function get_utm_campaigns_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_utm_campaigns
		drop table if exists {$wpdb->prefix}qa_utm_campaigns;
		create table {$wpdb->prefix}qa_utm_campaigns
		(
			campaign_id int auto_increment
				primary key,
			utm_campaign varchar(128) collate utf8mb4_bin null,
			constraint qa_utm_campaigns_campaign_id_uindex
				unique (campaign_id),
			constraint qa_utm_campaigns_utm_campaign_uindex
				unique (utm_campaign)
		) {$charset_collate}
		;
EOQ;
	}


	
	// qa_readersテーブルの作成SQLを返す
	public function get_qa_pv_log_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_pv_log
		drop table if exists {$wpdb->prefix}qa_pv_log;
		create table {$wpdb->prefix}qa_pv_log
		(
			pv_id int auto_increment,
			reader_id int null,
			page_id int null,
			device_id tinyint null,
			source_id int null,
			medium_id smallint(6) null,
			campaign_id smallint(6) null,
			session_no tinyint null,
			access_time datetime default '0000-00-00 00:00:00' not null,
			pv smallint(6) null,
			speed_msec smallint(5) unsigned null,
			browse_sec smallint(5) unsigned null,
			is_last tinyint(1) null,
			is_newuser tinyint(1) null,
			is_cv_session tinyint(1) null,
			flag_bit int null,
			version_id mediumint null,
			raw_p text null,
			raw_c text null,
			raw_e text null,
			primary key (pv_id, access_time),
			constraint uniq_access
			unique (reader_id, access_time)
		) {$charset_collate} 
		partition by range COLUMNS(access_time) (
			partition  p202001 values less than ('2020-02-01 00:00:00'),
			partition  p202002 values less than ('2020-03-01 00:00:00'),
			partition  p202003 values less than ('2020-04-01 00:00:00'),
			partition  p202004 values less than ('2020-05-01 00:00:00'),
			partition  p202005 values less than ('2020-06-01 00:00:00'),
			partition  p202006 values less than ('2020-07-01 00:00:00'),
			partition  p202007 values less than ('2020-08-01 00:00:00'),
			partition  p202008 values less than ('2020-09-01 00:00:00'),
			partition  p202009 values less than ('2020-10-01 00:00:00'),
			partition  p202010 values less than ('2020-11-01 00:00:00'),
			partition  p202011 values less than ('2020-12-01 00:00:00'),
			partition  p202012 values less than ('2021-01-01 00:00:00'),
			partition  p202101 values less than ('2021-02-01 00:00:00'),
			partition  p202102 values less than ('2021-03-01 00:00:00'),
			partition  p202103 values less than ('2021-04-01 00:00:00'),
			partition  p202104 values less than ('2021-05-01 00:00:00'),
			partition  p202105 values less than ('2021-06-01 00:00:00'),
			partition  p202106 values less than ('2021-07-01 00:00:00'),
			partition  p202107 values less than ('2021-08-01 00:00:00'),
			partition  p202108 values less than ('2021-09-01 00:00:00'),
			partition  p202109 values less than ('2021-10-01 00:00:00'),
			partition  p202110 values less than ('2021-11-01 00:00:00'),
			partition  p202111 values less than ('2021-12-01 00:00:00'),
			partition  p202112 values less than ('2022-01-01 00:00:00'),
			partition  p202201 values less than ('2022-02-01 00:00:00'),
			partition  p202202 values less than ('2022-03-01 00:00:00'),
			partition  p202203 values less than ('2022-04-01 00:00:00'),
			partition  p202204 values less than ('2022-05-01 00:00:00'),
			partition  p202205 values less than ('2022-06-01 00:00:00'),
			partition  p202206 values less than ('2022-07-01 00:00:00'),
			partition  p202207 values less than ('2022-08-01 00:00:00'),
			partition  p202208 values less than ('2022-09-01 00:00:00'),
			partition  p202209 values less than ('2022-10-01 00:00:00'),
			partition  p202210 values less than ('2022-11-01 00:00:00'),
			partition  p202211 values less than ('2022-12-01 00:00:00'),
			partition  p202212 values less than ('2023-01-01 00:00:00'),
			partition  p202301 values less than ('2023-02-01 00:00:00'),
			partition  p202302 values less than ('2023-03-01 00:00:00'),
			partition  p202303 values less than ('2023-04-01 00:00:00'),
			partition  p202304 values less than ('2023-05-01 00:00:00'),
			partition  p202305 values less than ('2023-06-01 00:00:00'),
			partition  p202306 values less than ('2023-07-01 00:00:00'),
			partition  p202307 values less than ('2023-08-01 00:00:00'),
			partition  p202308 values less than ('2023-09-01 00:00:00'),
			partition  p202309 values less than ('2023-10-01 00:00:00'),
			partition  p202310 values less than ('2023-11-01 00:00:00'),
			partition  p202311 values less than ('2023-12-01 00:00:00'),
			partition  p202312 values less than ('2024-01-01 00:00:00'),
			partition  p202401 values less than ('2024-02-01 00:00:00'),
			partition  p202402 values less than ('2024-03-01 00:00:00'),
			partition  p202403 values less than ('2024-04-01 00:00:00'),
			partition  p202404 values less than ('2024-05-01 00:00:00'),
			partition  p202405 values less than ('2024-06-01 00:00:00'),
			partition  p202406 values less than ('2024-07-01 00:00:00'),
			partition  p202407 values less than ('2024-08-01 00:00:00'),
			partition  p202408 values less than ('2024-09-01 00:00:00'),
			partition  p202409 values less than ('2024-10-01 00:00:00'),
			partition  p202410 values less than ('2024-11-01 00:00:00'),
			partition  p202411 values less than ('2024-12-01 00:00:00'),
			partition  p202412 values less than ('2025-01-01 00:00:00'),
			partition  p202501 values less than ('2025-02-01 00:00:00'),
			partition  p202502 values less than ('2025-03-01 00:00:00'),
			partition  p202503 values less than ('2025-04-01 00:00:00'),
			partition  p202504 values less than ('2025-05-01 00:00:00'),
			partition  p202505 values less than ('2025-06-01 00:00:00'),
			partition  p202506 values less than ('2025-07-01 00:00:00'),
			partition  p202507 values less than ('2025-08-01 00:00:00'),
			partition  p202508 values less than ('2025-09-01 00:00:00'),
			partition  p202509 values less than ('2025-10-01 00:00:00'),
			partition  p202510 values less than ('2025-11-01 00:00:00'),
			partition  p202511 values less than ('2025-12-01 00:00:00'),
			partition  p202512 values less than ('2026-01-01 00:00:00'),
			partition  p202601 values less than ('2026-02-01 00:00:00'),
			partition  p202602 values less than ('2026-03-01 00:00:00'),
			partition  p202603 values less than ('2026-04-01 00:00:00'),
			partition  p202604 values less than ('2026-05-01 00:00:00'),
			partition  p202605 values less than ('2026-06-01 00:00:00'),
			partition  p202606 values less than ('2026-07-01 00:00:00'),
			partition  p202607 values less than ('2026-08-01 00:00:00'),
			partition  p202608 values less than ('2026-09-01 00:00:00'),
			partition  p202609 values less than ('2026-10-01 00:00:00'),
			partition  p202610 values less than ('2026-11-01 00:00:00'),
			partition  p202611 values less than ('2026-12-01 00:00:00'),
			partition  p202612 values less than ('2027-01-01 00:00:00'),
			partition  p202701 values less than ('2027-02-01 00:00:00'),
			partition  p202702 values less than ('2027-03-01 00:00:00'),
			partition  p202703 values less than ('2027-04-01 00:00:00'),
			partition  p202704 values less than ('2027-05-01 00:00:00'),
			partition  p202705 values less than ('2027-06-01 00:00:00'),
			partition  p202706 values less than ('2027-07-01 00:00:00'),
			partition  p202707 values less than ('2027-08-01 00:00:00'),
			partition  p202708 values less than ('2027-09-01 00:00:00'),
			partition  p202709 values less than ('2027-10-01 00:00:00'),
			partition  p202710 values less than ('2027-11-01 00:00:00'),
			partition  p202711 values less than ('2027-12-01 00:00:00'),
			partition  p202712 values less than ('2028-01-01 00:00:00'),
			partition  p202801 values less than ('2028-02-01 00:00:00'),
			partition  p202802 values less than ('2028-03-01 00:00:00'),
			partition  p202803 values less than ('2028-04-01 00:00:00'),
			partition  p202804 values less than ('2028-05-01 00:00:00'),
			partition  p202805 values less than ('2028-06-01 00:00:00'),
			partition  p202806 values less than ('2028-07-01 00:00:00'),
			partition  p202807 values less than ('2028-08-01 00:00:00'),
			partition  p202808 values less than ('2028-09-01 00:00:00'),
			partition  p202809 values less than ('2028-10-01 00:00:00'),
			partition  p202810 values less than ('2028-11-01 00:00:00'),
			partition  p202811 values less than ('2028-12-01 00:00:00'),
			partition  p202812 values less than ('2029-01-01 00:00:00'),
			partition  p202901 values less than ('2029-02-01 00:00:00'),
			partition  p202902 values less than ('2029-03-01 00:00:00'),
			partition  p202903 values less than ('2029-04-01 00:00:00'),
			partition  p202904 values less than ('2029-05-01 00:00:00'),
			partition  p202905 values less than ('2029-06-01 00:00:00'),
			partition  p202906 values less than ('2029-07-01 00:00:00'),
			partition  p202907 values less than ('2029-08-01 00:00:00'),
			partition  p202908 values less than ('2029-09-01 00:00:00'),
			partition  p202909 values less than ('2029-10-01 00:00:00'),
			partition  p202910 values less than ('2029-11-01 00:00:00'),
			partition  p202911 values less than ('2029-12-01 00:00:00'),
			partition  p202912 values less than ('2030-01-01 00:00:00'),
			partition  p203001 values less than ('2030-02-01 00:00:00'),
			partition  p203002 values less than ('2030-03-01 00:00:00'),
			partition  p203003 values less than ('2030-04-01 00:00:00'),
			partition  p203004 values less than ('2030-05-01 00:00:00'),
			partition  p203005 values less than ('2030-06-01 00:00:00'),
			partition  p203006 values less than ('2030-07-01 00:00:00'),
			partition  p203007 values less than ('2030-08-01 00:00:00'),
			partition  p203008 values less than ('2030-09-01 00:00:00'),
			partition  p203009 values less than ('2030-10-01 00:00:00'),
			partition  p203010 values less than ('2030-11-01 00:00:00'),
			partition  p203011 values less than ('2030-12-01 00:00:00'),
			partition  p203012 values less than ('2031-01-01 00:00:00'),
			partition  p203101 values less than ('2031-02-01 00:00:00'),
			partition  p203102 values less than ('2031-03-01 00:00:00'),
			partition  p203103 values less than ('2031-04-01 00:00:00'),
			partition  p203104 values less than ('2031-05-01 00:00:00'),
			partition  p203105 values less than ('2031-06-01 00:00:00'),
			partition  p203106 values less than ('2031-07-01 00:00:00'),
			partition  p203107 values less than ('2031-08-01 00:00:00'),
			partition  p203108 values less than ('2031-09-01 00:00:00'),
			partition  p203109 values less than ('2031-10-01 00:00:00'),
			partition  p203110 values less than ('2031-11-01 00:00:00'),
			partition  p203111 values less than ('2031-12-01 00:00:00'),
			partition  p203112 values less than ('2032-01-01 00:00:00'),
			partition  p203201 values less than ('2032-02-01 00:00:00'),
			partition  p203202 values less than ('2032-03-01 00:00:00'),
			partition  p203203 values less than ('2032-04-01 00:00:00'),
			partition  p203204 values less than ('2032-05-01 00:00:00'),
			partition  p203205 values less than ('2032-06-01 00:00:00'),
			partition  p203206 values less than ('2032-07-01 00:00:00'),
			partition  p203207 values less than ('2032-08-01 00:00:00'),
			partition  p203208 values less than ('2032-09-01 00:00:00'),
			partition  p203209 values less than ('2032-10-01 00:00:00'),
			partition  p203210 values less than ('2032-11-01 00:00:00'),
			partition  p203211 values less than ('2032-12-01 00:00:00'),
			partition  p203212 values less than ('2033-01-01 00:00:00'),
			partition  p203301 values less than ('2033-02-01 00:00:00'),
			partition  p203302 values less than ('2033-03-01 00:00:00'),
			partition  p203303 values less than ('2033-04-01 00:00:00'),
			partition  p203304 values less than ('2033-05-01 00:00:00'),
			partition  p203305 values less than ('2033-06-01 00:00:00'),
			partition  p203306 values less than ('2033-07-01 00:00:00'),
			partition  p203307 values less than ('2033-08-01 00:00:00'),
			partition  p203308 values less than ('2033-09-01 00:00:00'),
			partition  p203309 values less than ('2033-10-01 00:00:00'),
			partition  p203310 values less than ('2033-11-01 00:00:00'),
			partition  p203311 values less than ('2033-12-01 00:00:00'),
			partition  p203312 values less than ('2034-01-01 00:00:00'),
			partition  p203401 values less than ('2034-02-01 00:00:00'),
			partition  p203402 values less than ('2034-03-01 00:00:00'),
			partition  p203403 values less than ('2034-04-01 00:00:00'),
			partition  p203404 values less than ('2034-05-01 00:00:00'),
			partition  p203405 values less than ('2034-06-01 00:00:00'),
			partition  p203406 values less than ('2034-07-01 00:00:00'),
			partition  p203407 values less than ('2034-08-01 00:00:00'),
			partition  p203408 values less than ('2034-09-01 00:00:00'),
			partition  p203409 values less than ('2034-10-01 00:00:00'),
			partition  p203410 values less than ('2034-11-01 00:00:00'),
			partition  p203411 values less than ('2034-12-01 00:00:00'),
			partition  p203412 values less than ('2035-01-01 00:00:00'),
			partition  p203501 values less than ('2035-02-01 00:00:00'),
			partition  p203502 values less than ('2035-03-01 00:00:00'),
			partition  p203503 values less than ('2035-04-01 00:00:00'),
			partition  p203504 values less than ('2035-05-01 00:00:00'),
			partition  p203505 values less than ('2035-06-01 00:00:00'),
			partition  p203506 values less than ('2035-07-01 00:00:00'),
			partition  p203507 values less than ('2035-08-01 00:00:00'),
			partition  p203508 values less than ('2035-09-01 00:00:00'),
			partition  p203509 values less than ('2035-10-01 00:00:00'),
			partition  p203510 values less than ('2035-11-01 00:00:00'),
			partition  p203511 values less than ('2035-12-01 00:00:00'),
			partition  p203512 values less than ('2036-01-01 00:00:00'),
			partition  p203601 values less than ('2036-02-01 00:00:00'),
			partition  p203602 values less than ('2036-03-01 00:00:00'),
			partition  p203603 values less than ('2036-04-01 00:00:00'),
			partition  p203604 values less than ('2036-05-01 00:00:00'),
			partition  p203605 values less than ('2036-06-01 00:00:00'),
			partition  p203606 values less than ('2036-07-01 00:00:00'),
			partition  p203607 values less than ('2036-08-01 00:00:00'),
			partition  p203608 values less than ('2036-09-01 00:00:00'),
			partition  p203609 values less than ('2036-10-01 00:00:00'),
			partition  p203610 values less than ('2036-11-01 00:00:00'),
			partition  p203611 values less than ('2036-12-01 00:00:00'),
			partition  p203612 values less than ('2037-01-01 00:00:00'),
			partition  p203701 values less than ('2037-02-01 00:00:00'),
			partition  p203702 values less than ('2037-03-01 00:00:00'),
			partition  p203703 values less than ('2037-04-01 00:00:00'),
			partition  p203704 values less than ('2037-05-01 00:00:00'),
			partition  p203705 values less than ('2037-06-01 00:00:00'),
			partition  p203706 values less than ('2037-07-01 00:00:00'),
			partition  p203707 values less than ('2037-08-01 00:00:00'),
			partition  p203708 values less than ('2037-09-01 00:00:00'),
			partition  p203709 values less than ('2037-10-01 00:00:00'),
			partition  p203710 values less than ('2037-11-01 00:00:00'),
			partition  p203711 values less than ('2037-12-01 00:00:00'),
			partition  p203712 values less than ('2038-01-01 00:00:00'),
		PARTITION pmax VALUES LESS THAN (MAXVALUE)
		);
		
		create index qa_pv_log_is_cv_session_index
			on {$wpdb->prefix}qa_pv_log (is_cv_session)
		;
		
		create index qa_pv_log_reader_id_index
			on {$wpdb->prefix}qa_pv_log (reader_id)
		;
		
		create index qa_pv_log_source_id_index
			on {$wpdb->prefix}qa_pv_log (source_id)
		;
		CREATE index qa_pv_log_version_id_index
			on {$wpdb->prefix}qa_pv_log (version_id)
		;
EOQ;
	}


	// qa_readersテーブルの作成SQLを返す
	public function get_search_log_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_search_log
		drop table if exists {$wpdb->prefix}qa_search_log;
		create table {$wpdb->prefix}qa_search_log
		(
			pv_id int null,
			query varchar(128) null
		) {$charset_collate}
		;
EOQ;
	}


	
	// qa_readersテーブルの作成SQLを返す
	public function get_qa_page_version_hist_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_page_version_hist
		drop table if exists {$wpdb->prefix}qa_page_version_hist;
		create table {$wpdb->prefix}qa_page_version_hist
		(
			version_id mediumint auto_increment,
			page_id int null,
			device_id tinyint null,
			version_no smallint(6) null,
			base_html longtext null,
			base_selector mediumtext null,
			update_date date not null,
			insert_datetime datetime null,
			primary key (version_id, update_date)
		) {$charset_collate} 
		partition by range COLUMNS(update_date) (
			partition  p202001 values less than ('2020-02-01'),
			partition  p202002 values less than ('2020-03-01'),
			partition  p202003 values less than ('2020-04-01'),
			partition  p202004 values less than ('2020-05-01'),
			partition  p202005 values less than ('2020-06-01'),
			partition  p202006 values less than ('2020-07-01'),
			partition  p202007 values less than ('2020-08-01'),
			partition  p202008 values less than ('2020-09-01'),
			partition  p202009 values less than ('2020-10-01'),
			partition  p202010 values less than ('2020-11-01'),
			partition  p202011 values less than ('2020-12-01'),
			partition  p202012 values less than ('2021-01-01'),
			partition  p202101 values less than ('2021-02-01'),
			partition  p202102 values less than ('2021-03-01'),
			partition  p202103 values less than ('2021-04-01'),
			partition  p202104 values less than ('2021-05-01'),
			partition  p202105 values less than ('2021-06-01'),
			partition  p202106 values less than ('2021-07-01'),
			partition  p202107 values less than ('2021-08-01'),
			partition  p202108 values less than ('2021-09-01'),
			partition  p202109 values less than ('2021-10-01'),
			partition  p202110 values less than ('2021-11-01'),
			partition  p202111 values less than ('2021-12-01'),
			partition  p202112 values less than ('2022-01-01'),
			partition  p202201 values less than ('2022-02-01'),
			partition  p202202 values less than ('2022-03-01'),
			partition  p202203 values less than ('2022-04-01'),
			partition  p202204 values less than ('2022-05-01'),
			partition  p202205 values less than ('2022-06-01'),
			partition  p202206 values less than ('2022-07-01'),
			partition  p202207 values less than ('2022-08-01'),
			partition  p202208 values less than ('2022-09-01'),
			partition  p202209 values less than ('2022-10-01'),
			partition  p202210 values less than ('2022-11-01'),
			partition  p202211 values less than ('2022-12-01'),
			partition  p202212 values less than ('2023-01-01'),
			partition  p202301 values less than ('2023-02-01'),
			partition  p202302 values less than ('2023-03-01'),
			partition  p202303 values less than ('2023-04-01'),
			partition  p202304 values less than ('2023-05-01'),
			partition  p202305 values less than ('2023-06-01'),
			partition  p202306 values less than ('2023-07-01'),
			partition  p202307 values less than ('2023-08-01'),
			partition  p202308 values less than ('2023-09-01'),
			partition  p202309 values less than ('2023-10-01'),
			partition  p202310 values less than ('2023-11-01'),
			partition  p202311 values less than ('2023-12-01'),
			partition  p202312 values less than ('2024-01-01'),
			partition  p202401 values less than ('2024-02-01'),
			partition  p202402 values less than ('2024-03-01'),
			partition  p202403 values less than ('2024-04-01'),
			partition  p202404 values less than ('2024-05-01'),
			partition  p202405 values less than ('2024-06-01'),
			partition  p202406 values less than ('2024-07-01'),
			partition  p202407 values less than ('2024-08-01'),
			partition  p202408 values less than ('2024-09-01'),
			partition  p202409 values less than ('2024-10-01'),
			partition  p202410 values less than ('2024-11-01'),
			partition  p202411 values less than ('2024-12-01'),
			partition  p202412 values less than ('2025-01-01'),
			partition  p202501 values less than ('2025-02-01'),
			partition  p202502 values less than ('2025-03-01'),
			partition  p202503 values less than ('2025-04-01'),
			partition  p202504 values less than ('2025-05-01'),
			partition  p202505 values less than ('2025-06-01'),
			partition  p202506 values less than ('2025-07-01'),
			partition  p202507 values less than ('2025-08-01'),
			partition  p202508 values less than ('2025-09-01'),
			partition  p202509 values less than ('2025-10-01'),
			partition  p202510 values less than ('2025-11-01'),
			partition  p202511 values less than ('2025-12-01'),
			partition  p202512 values less than ('2026-01-01'),
			partition  p202601 values less than ('2026-02-01'),
			partition  p202602 values less than ('2026-03-01'),
			partition  p202603 values less than ('2026-04-01'),
			partition  p202604 values less than ('2026-05-01'),
			partition  p202605 values less than ('2026-06-01'),
			partition  p202606 values less than ('2026-07-01'),
			partition  p202607 values less than ('2026-08-01'),
			partition  p202608 values less than ('2026-09-01'),
			partition  p202609 values less than ('2026-10-01'),
			partition  p202610 values less than ('2026-11-01'),
			partition  p202611 values less than ('2026-12-01'),
			partition  p202612 values less than ('2027-01-01'),
			partition  p202701 values less than ('2027-02-01'),
			partition  p202702 values less than ('2027-03-01'),
			partition  p202703 values less than ('2027-04-01'),
			partition  p202704 values less than ('2027-05-01'),
			partition  p202705 values less than ('2027-06-01'),
			partition  p202706 values less than ('2027-07-01'),
			partition  p202707 values less than ('2027-08-01'),
			partition  p202708 values less than ('2027-09-01'),
			partition  p202709 values less than ('2027-10-01'),
			partition  p202710 values less than ('2027-11-01'),
			partition  p202711 values less than ('2027-12-01'),
			partition  p202712 values less than ('2028-01-01'),
			partition  p202801 values less than ('2028-02-01'),
			partition  p202802 values less than ('2028-03-01'),
			partition  p202803 values less than ('2028-04-01'),
			partition  p202804 values less than ('2028-05-01'),
			partition  p202805 values less than ('2028-06-01'),
			partition  p202806 values less than ('2028-07-01'),
			partition  p202807 values less than ('2028-08-01'),
			partition  p202808 values less than ('2028-09-01'),
			partition  p202809 values less than ('2028-10-01'),
			partition  p202810 values less than ('2028-11-01'),
			partition  p202811 values less than ('2028-12-01'),
			partition  p202812 values less than ('2029-01-01'),
			partition  p202901 values less than ('2029-02-01'),
			partition  p202902 values less than ('2029-03-01'),
			partition  p202903 values less than ('2029-04-01'),
			partition  p202904 values less than ('2029-05-01'),
			partition  p202905 values less than ('2029-06-01'),
			partition  p202906 values less than ('2029-07-01'),
			partition  p202907 values less than ('2029-08-01'),
			partition  p202908 values less than ('2029-09-01'),
			partition  p202909 values less than ('2029-10-01'),
			partition  p202910 values less than ('2029-11-01'),
			partition  p202911 values less than ('2029-12-01'),
			partition  p202912 values less than ('2030-01-01'),
			partition  p203001 values less than ('2030-02-01'),
			partition  p203002 values less than ('2030-03-01'),
			partition  p203003 values less than ('2030-04-01'),
			partition  p203004 values less than ('2030-05-01'),
			partition  p203005 values less than ('2030-06-01'),
			partition  p203006 values less than ('2030-07-01'),
			partition  p203007 values less than ('2030-08-01'),
			partition  p203008 values less than ('2030-09-01'),
			partition  p203009 values less than ('2030-10-01'),
			partition  p203010 values less than ('2030-11-01'),
			partition  p203011 values less than ('2030-12-01'),
			partition  p203012 values less than ('2031-01-01'),
			partition  p203101 values less than ('2031-02-01'),
			partition  p203102 values less than ('2031-03-01'),
			partition  p203103 values less than ('2031-04-01'),
			partition  p203104 values less than ('2031-05-01'),
			partition  p203105 values less than ('2031-06-01'),
			partition  p203106 values less than ('2031-07-01'),
			partition  p203107 values less than ('2031-08-01'),
			partition  p203108 values less than ('2031-09-01'),
			partition  p203109 values less than ('2031-10-01'),
			partition  p203110 values less than ('2031-11-01'),
			partition  p203111 values less than ('2031-12-01'),
			partition  p203112 values less than ('2032-01-01'),
			partition  p203201 values less than ('2032-02-01'),
			partition  p203202 values less than ('2032-03-01'),
			partition  p203203 values less than ('2032-04-01'),
			partition  p203204 values less than ('2032-05-01'),
			partition  p203205 values less than ('2032-06-01'),
			partition  p203206 values less than ('2032-07-01'),
			partition  p203207 values less than ('2032-08-01'),
			partition  p203208 values less than ('2032-09-01'),
			partition  p203209 values less than ('2032-10-01'),
			partition  p203210 values less than ('2032-11-01'),
			partition  p203211 values less than ('2032-12-01'),
			partition  p203212 values less than ('2033-01-01'),
			partition  p203301 values less than ('2033-02-01'),
			partition  p203302 values less than ('2033-03-01'),
			partition  p203303 values less than ('2033-04-01'),
			partition  p203304 values less than ('2033-05-01'),
			partition  p203305 values less than ('2033-06-01'),
			partition  p203306 values less than ('2033-07-01'),
			partition  p203307 values less than ('2033-08-01'),
			partition  p203308 values less than ('2033-09-01'),
			partition  p203309 values less than ('2033-10-01'),
			partition  p203310 values less than ('2033-11-01'),
			partition  p203311 values less than ('2033-12-01'),
			partition  p203312 values less than ('2034-01-01'),
			partition  p203401 values less than ('2034-02-01'),
			partition  p203402 values less than ('2034-03-01'),
			partition  p203403 values less than ('2034-04-01'),
			partition  p203404 values less than ('2034-05-01'),
			partition  p203405 values less than ('2034-06-01'),
			partition  p203406 values less than ('2034-07-01'),
			partition  p203407 values less than ('2034-08-01'),
			partition  p203408 values less than ('2034-09-01'),
			partition  p203409 values less than ('2034-10-01'),
			partition  p203410 values less than ('2034-11-01'),
			partition  p203411 values less than ('2034-12-01'),
			partition  p203412 values less than ('2035-01-01'),
			partition  p203501 values less than ('2035-02-01'),
			partition  p203502 values less than ('2035-03-01'),
			partition  p203503 values less than ('2035-04-01'),
			partition  p203504 values less than ('2035-05-01'),
			partition  p203505 values less than ('2035-06-01'),
			partition  p203506 values less than ('2035-07-01'),
			partition  p203507 values less than ('2035-08-01'),
			partition  p203508 values less than ('2035-09-01'),
			partition  p203509 values less than ('2035-10-01'),
			partition  p203510 values less than ('2035-11-01'),
			partition  p203511 values less than ('2035-12-01'),
			partition  p203512 values less than ('2036-01-01'),
			partition  p203601 values less than ('2036-02-01'),
			partition  p203602 values less than ('2036-03-01'),
			partition  p203603 values less than ('2036-04-01'),
			partition  p203604 values less than ('2036-05-01'),
			partition  p203605 values less than ('2036-06-01'),
			partition  p203606 values less than ('2036-07-01'),
			partition  p203607 values less than ('2036-08-01'),
			partition  p203608 values less than ('2036-09-01'),
			partition  p203609 values less than ('2036-10-01'),
			partition  p203610 values less than ('2036-11-01'),
			partition  p203611 values less than ('2036-12-01'),
			partition  p203612 values less than ('2037-01-01'),
			partition  p203701 values less than ('2037-02-01'),
			partition  p203702 values less than ('2037-03-01'),
			partition  p203703 values less than ('2037-04-01'),
			partition  p203704 values less than ('2037-05-01'),
			partition  p203705 values less than ('2037-06-01'),
			partition  p203706 values less than ('2037-07-01'),
			partition  p203707 values less than ('2037-08-01'),
			partition  p203708 values less than ('2037-09-01'),
			partition  p203709 values less than ('2037-10-01'),
			partition  p203710 values less than ('2037-11-01'),
			partition  p203711 values less than ('2037-12-01'),
			partition  p203712 values less than ('2038-01-01'),
			PARTITION pmax VALUES LESS THAN (MAXVALUE)
		);
		create index {$wpdb->prefix}qa_page_version_hist_page_id_index
			on {$wpdb->prefix}qa_page_version_hist (page_id)
		;
EOQ;
	}


	
	// qa_readersテーブルの作成SQLを返す
	public function get_qa_gsc_query_log_create_table() {
		global $wpdb;
		$charset_collate = '';
		
		// charsetを指定する
		if ( $wpdb->charset ) {
			$charset_collate = 'DEFAULT CHARACTER SET ' . $wpdb->charset;
		}

		// 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
		if ( $wpdb->collate ) {
			$charset_collate .= ' COLLATE ' . $wpdb->collate;
		}

		return <<< EOQ
		-- qa_gsc_query_log
		drop table if exists {$wpdb->prefix}qa_gsc_query_log;
		create table {$wpdb->prefix}qa_gsc_query_log
		(
			query_id int auto_increment,
			keyword varchar(190) not null,
			update_date date not null,
			primary key (query_id, update_date),
			unique key (keyword, update_date)
		) {$charset_collate} 
		partition by range COLUMNS(update_date) (
			partition  p202001 values less than ('2020-02-01 00:00:00'),
			partition  p202002 values less than ('2020-03-01 00:00:00'),
			partition  p202003 values less than ('2020-04-01 00:00:00'),
			partition  p202004 values less than ('2020-05-01 00:00:00'),
			partition  p202005 values less than ('2020-06-01 00:00:00'),
			partition  p202006 values less than ('2020-07-01 00:00:00'),
			partition  p202007 values less than ('2020-08-01 00:00:00'),
			partition  p202008 values less than ('2020-09-01 00:00:00'),
			partition  p202009 values less than ('2020-10-01 00:00:00'),
			partition  p202010 values less than ('2020-11-01 00:00:00'),
			partition  p202011 values less than ('2020-12-01 00:00:00'),
			partition  p202012 values less than ('2021-01-01 00:00:00'),
			partition  p202101 values less than ('2021-02-01 00:00:00'),
			partition  p202102 values less than ('2021-03-01 00:00:00'),
			partition  p202103 values less than ('2021-04-01 00:00:00'),
			partition  p202104 values less than ('2021-05-01 00:00:00'),
			partition  p202105 values less than ('2021-06-01 00:00:00'),
			partition  p202106 values less than ('2021-07-01 00:00:00'),
			partition  p202107 values less than ('2021-08-01 00:00:00'),
			partition  p202108 values less than ('2021-09-01 00:00:00'),
			partition  p202109 values less than ('2021-10-01 00:00:00'),
			partition  p202110 values less than ('2021-11-01 00:00:00'),
			partition  p202111 values less than ('2021-12-01 00:00:00'),
			partition  p202112 values less than ('2022-01-01 00:00:00'),
			partition  p202201 values less than ('2022-02-01 00:00:00'),
			partition  p202202 values less than ('2022-03-01 00:00:00'),
			partition  p202203 values less than ('2022-04-01 00:00:00'),
			partition  p202204 values less than ('2022-05-01 00:00:00'),
			partition  p202205 values less than ('2022-06-01 00:00:00'),
			partition  p202206 values less than ('2022-07-01 00:00:00'),
			partition  p202207 values less than ('2022-08-01 00:00:00'),
			partition  p202208 values less than ('2022-09-01 00:00:00'),
			partition  p202209 values less than ('2022-10-01 00:00:00'),
			partition  p202210 values less than ('2022-11-01 00:00:00'),
			partition  p202211 values less than ('2022-12-01 00:00:00'),
			partition  p202212 values less than ('2023-01-01 00:00:00'),
			partition  p202301 values less than ('2023-02-01 00:00:00'),
			partition  p202302 values less than ('2023-03-01 00:00:00'),
			partition  p202303 values less than ('2023-04-01 00:00:00'),
			partition  p202304 values less than ('2023-05-01 00:00:00'),
			partition  p202305 values less than ('2023-06-01 00:00:00'),
			partition  p202306 values less than ('2023-07-01 00:00:00'),
			partition  p202307 values less than ('2023-08-01 00:00:00'),
			partition  p202308 values less than ('2023-09-01 00:00:00'),
			partition  p202309 values less than ('2023-10-01 00:00:00'),
			partition  p202310 values less than ('2023-11-01 00:00:00'),
			partition  p202311 values less than ('2023-12-01 00:00:00'),
			partition  p202312 values less than ('2024-01-01 00:00:00'),
			partition  p202401 values less than ('2024-02-01 00:00:00'),
			partition  p202402 values less than ('2024-03-01 00:00:00'),
			partition  p202403 values less than ('2024-04-01 00:00:00'),
			partition  p202404 values less than ('2024-05-01 00:00:00'),
			partition  p202405 values less than ('2024-06-01 00:00:00'),
			partition  p202406 values less than ('2024-07-01 00:00:00'),
			partition  p202407 values less than ('2024-08-01 00:00:00'),
			partition  p202408 values less than ('2024-09-01 00:00:00'),
			partition  p202409 values less than ('2024-10-01 00:00:00'),
			partition  p202410 values less than ('2024-11-01 00:00:00'),
			partition  p202411 values less than ('2024-12-01 00:00:00'),
			partition  p202412 values less than ('2025-01-01 00:00:00'),
			partition  p202501 values less than ('2025-02-01 00:00:00'),
			partition  p202502 values less than ('2025-03-01 00:00:00'),
			partition  p202503 values less than ('2025-04-01 00:00:00'),
			partition  p202504 values less than ('2025-05-01 00:00:00'),
			partition  p202505 values less than ('2025-06-01 00:00:00'),
			partition  p202506 values less than ('2025-07-01 00:00:00'),
			partition  p202507 values less than ('2025-08-01 00:00:00'),
			partition  p202508 values less than ('2025-09-01 00:00:00'),
			partition  p202509 values less than ('2025-10-01 00:00:00'),
			partition  p202510 values less than ('2025-11-01 00:00:00'),
			partition  p202511 values less than ('2025-12-01 00:00:00'),
			partition  p202512 values less than ('2026-01-01 00:00:00'),
			partition  p202601 values less than ('2026-02-01 00:00:00'),
			partition  p202602 values less than ('2026-03-01 00:00:00'),
			partition  p202603 values less than ('2026-04-01 00:00:00'),
			partition  p202604 values less than ('2026-05-01 00:00:00'),
			partition  p202605 values less than ('2026-06-01 00:00:00'),
			partition  p202606 values less than ('2026-07-01 00:00:00'),
			partition  p202607 values less than ('2026-08-01 00:00:00'),
			partition  p202608 values less than ('2026-09-01 00:00:00'),
			partition  p202609 values less than ('2026-10-01 00:00:00'),
			partition  p202610 values less than ('2026-11-01 00:00:00'),
			partition  p202611 values less than ('2026-12-01 00:00:00'),
			partition  p202612 values less than ('2027-01-01 00:00:00'),
			partition  p202701 values less than ('2027-02-01 00:00:00'),
			partition  p202702 values less than ('2027-03-01 00:00:00'),
			partition  p202703 values less than ('2027-04-01 00:00:00'),
			partition  p202704 values less than ('2027-05-01 00:00:00'),
			partition  p202705 values less than ('2027-06-01 00:00:00'),
			partition  p202706 values less than ('2027-07-01 00:00:00'),
			partition  p202707 values less than ('2027-08-01 00:00:00'),
			partition  p202708 values less than ('2027-09-01 00:00:00'),
			partition  p202709 values less than ('2027-10-01 00:00:00'),
			partition  p202710 values less than ('2027-11-01 00:00:00'),
			partition  p202711 values less than ('2027-12-01 00:00:00'),
			partition  p202712 values less than ('2028-01-01 00:00:00'),
			partition  p202801 values less than ('2028-02-01 00:00:00'),
			partition  p202802 values less than ('2028-03-01 00:00:00'),
			partition  p202803 values less than ('2028-04-01 00:00:00'),
			partition  p202804 values less than ('2028-05-01 00:00:00'),
			partition  p202805 values less than ('2028-06-01 00:00:00'),
			partition  p202806 values less than ('2028-07-01 00:00:00'),
			partition  p202807 values less than ('2028-08-01 00:00:00'),
			partition  p202808 values less than ('2028-09-01 00:00:00'),
			partition  p202809 values less than ('2028-10-01 00:00:00'),
			partition  p202810 values less than ('2028-11-01 00:00:00'),
			partition  p202811 values less than ('2028-12-01 00:00:00'),
			partition  p202812 values less than ('2029-01-01 00:00:00'),
			partition  p202901 values less than ('2029-02-01 00:00:00'),
			partition  p202902 values less than ('2029-03-01 00:00:00'),
			partition  p202903 values less than ('2029-04-01 00:00:00'),
			partition  p202904 values less than ('2029-05-01 00:00:00'),
			partition  p202905 values less than ('2029-06-01 00:00:00'),
			partition  p202906 values less than ('2029-07-01 00:00:00'),
			partition  p202907 values less than ('2029-08-01 00:00:00'),
			partition  p202908 values less than ('2029-09-01 00:00:00'),
			partition  p202909 values less than ('2029-10-01 00:00:00'),
			partition  p202910 values less than ('2029-11-01 00:00:00'),
			partition  p202911 values less than ('2029-12-01 00:00:00'),
			partition  p202912 values less than ('2030-01-01 00:00:00'),
			partition  p203001 values less than ('2030-02-01 00:00:00'),
			partition  p203002 values less than ('2030-03-01 00:00:00'),
			partition  p203003 values less than ('2030-04-01 00:00:00'),
			partition  p203004 values less than ('2030-05-01 00:00:00'),
			partition  p203005 values less than ('2030-06-01 00:00:00'),
			partition  p203006 values less than ('2030-07-01 00:00:00'),
			partition  p203007 values less than ('2030-08-01 00:00:00'),
			partition  p203008 values less than ('2030-09-01 00:00:00'),
			partition  p203009 values less than ('2030-10-01 00:00:00'),
			partition  p203010 values less than ('2030-11-01 00:00:00'),
			partition  p203011 values less than ('2030-12-01 00:00:00'),
			partition  p203012 values less than ('2031-01-01 00:00:00'),
			partition  p203101 values less than ('2031-02-01 00:00:00'),
			partition  p203102 values less than ('2031-03-01 00:00:00'),
			partition  p203103 values less than ('2031-04-01 00:00:00'),
			partition  p203104 values less than ('2031-05-01 00:00:00'),
			partition  p203105 values less than ('2031-06-01 00:00:00'),
			partition  p203106 values less than ('2031-07-01 00:00:00'),
			partition  p203107 values less than ('2031-08-01 00:00:00'),
			partition  p203108 values less than ('2031-09-01 00:00:00'),
			partition  p203109 values less than ('2031-10-01 00:00:00'),
			partition  p203110 values less than ('2031-11-01 00:00:00'),
			partition  p203111 values less than ('2031-12-01 00:00:00'),
			partition  p203112 values less than ('2032-01-01 00:00:00'),
			partition  p203201 values less than ('2032-02-01 00:00:00'),
			partition  p203202 values less than ('2032-03-01 00:00:00'),
			partition  p203203 values less than ('2032-04-01 00:00:00'),
			partition  p203204 values less than ('2032-05-01 00:00:00'),
			partition  p203205 values less than ('2032-06-01 00:00:00'),
			partition  p203206 values less than ('2032-07-01 00:00:00'),
			partition  p203207 values less than ('2032-08-01 00:00:00'),
			partition  p203208 values less than ('2032-09-01 00:00:00'),
			partition  p203209 values less than ('2032-10-01 00:00:00'),
			partition  p203210 values less than ('2032-11-01 00:00:00'),
			partition  p203211 values less than ('2032-12-01 00:00:00'),
			partition  p203212 values less than ('2033-01-01 00:00:00'),
			partition  p203301 values less than ('2033-02-01 00:00:00'),
			partition  p203302 values less than ('2033-03-01 00:00:00'),
			partition  p203303 values less than ('2033-04-01 00:00:00'),
			partition  p203304 values less than ('2033-05-01 00:00:00'),
			partition  p203305 values less than ('2033-06-01 00:00:00'),
			partition  p203306 values less than ('2033-07-01 00:00:00'),
			partition  p203307 values less than ('2033-08-01 00:00:00'),
			partition  p203308 values less than ('2033-09-01 00:00:00'),
			partition  p203309 values less than ('2033-10-01 00:00:00'),
			partition  p203310 values less than ('2033-11-01 00:00:00'),
			partition  p203311 values less than ('2033-12-01 00:00:00'),
			partition  p203312 values less than ('2034-01-01 00:00:00'),
			partition  p203401 values less than ('2034-02-01 00:00:00'),
			partition  p203402 values less than ('2034-03-01 00:00:00'),
			partition  p203403 values less than ('2034-04-01 00:00:00'),
			partition  p203404 values less than ('2034-05-01 00:00:00'),
			partition  p203405 values less than ('2034-06-01 00:00:00'),
			partition  p203406 values less than ('2034-07-01 00:00:00'),
			partition  p203407 values less than ('2034-08-01 00:00:00'),
			partition  p203408 values less than ('2034-09-01 00:00:00'),
			partition  p203409 values less than ('2034-10-01 00:00:00'),
			partition  p203410 values less than ('2034-11-01 00:00:00'),
			partition  p203411 values less than ('2034-12-01 00:00:00'),
			partition  p203412 values less than ('2035-01-01 00:00:00'),
			partition  p203501 values less than ('2035-02-01 00:00:00'),
			partition  p203502 values less than ('2035-03-01 00:00:00'),
			partition  p203503 values less than ('2035-04-01 00:00:00'),
			partition  p203504 values less than ('2035-05-01 00:00:00'),
			partition  p203505 values less than ('2035-06-01 00:00:00'),
			partition  p203506 values less than ('2035-07-01 00:00:00'),
			partition  p203507 values less than ('2035-08-01 00:00:00'),
			partition  p203508 values less than ('2035-09-01 00:00:00'),
			partition  p203509 values less than ('2035-10-01 00:00:00'),
			partition  p203510 values less than ('2035-11-01 00:00:00'),
			partition  p203511 values less than ('2035-12-01 00:00:00'),
			partition  p203512 values less than ('2036-01-01 00:00:00'),
			partition  p203601 values less than ('2036-02-01 00:00:00'),
			partition  p203602 values less than ('2036-03-01 00:00:00'),
			partition  p203603 values less than ('2036-04-01 00:00:00'),
			partition  p203604 values less than ('2036-05-01 00:00:00'),
			partition  p203605 values less than ('2036-06-01 00:00:00'),
			partition  p203606 values less than ('2036-07-01 00:00:00'),
			partition  p203607 values less than ('2036-08-01 00:00:00'),
			partition  p203608 values less than ('2036-09-01 00:00:00'),
			partition  p203609 values less than ('2036-10-01 00:00:00'),
			partition  p203610 values less than ('2036-11-01 00:00:00'),
			partition  p203611 values less than ('2036-12-01 00:00:00'),
			partition  p203612 values less than ('2037-01-01 00:00:00'),
			partition  p203701 values less than ('2037-02-01 00:00:00'),
			partition  p203702 values less than ('2037-03-01 00:00:00'),
			partition  p203703 values less than ('2037-04-01 00:00:00'),
			partition  p203704 values less than ('2037-05-01 00:00:00'),
			partition  p203705 values less than ('2037-06-01 00:00:00'),
			partition  p203706 values less than ('2037-07-01 00:00:00'),
			partition  p203707 values less than ('2037-08-01 00:00:00'),
			partition  p203708 values less than ('2037-09-01 00:00:00'),
			partition  p203709 values less than ('2037-10-01 00:00:00'),
			partition  p203710 values less than ('2037-11-01 00:00:00'),
			partition  p203711 values less than ('2037-12-01 00:00:00'),
			partition  p203712 values less than ('2038-01-01 00:00:00'),
		PARTITION pmax VALUES LESS THAN (MAXVALUE)
		);
EOQ;
	}
}
