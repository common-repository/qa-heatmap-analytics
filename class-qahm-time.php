<?php
/**
 * 世界のロケールにあわせた日付時刻を返すためのクラス。
 * 基本的な考え方としては、unixtimeを使ってUTC基準の絶対値で計算し、2038年問題に対応するdatetime classを使ってロケールなどを加工する（本classは2038年問題に対応できることをデバッグ済み）。
 * 多くのシステムではUTCがdefault_timezoneになっているので、WordPressのロケールを活用してあわせていく。
 * なおunixtimeは32bitサーバー上で動く場合にint 32bitで扱われ、phpもそれにあわせてmakeされるため2038年問題が発生する。64bit環境で動くPHPであればtimestampもint 64bitで扱うため問題は発生しない（つまりサーバー依存）。
 * @package qa_heatmap
 */

$qahm_time = new QAHM_Time();
class QAHM_Time {
	/**
	 *
	 */
	const DEFAULT_DATE_FORMAT     = 'Y-m-d';
	const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s';
	const DEFAULT_TIME_FORMAT     = 'H:i:s';
	const DEFAULT_TIME_DELIMITER  = ':';

	public $timezone_string;
	public $utc_offset;
	public $timezone_obj;

	public function __construct() {
		// Asia/Tokyo
		$this->timezone_string = get_option( 'timezone_string' );
		// 9
		$this->utc_offset = get_option( 'gmt_offset' );

		if ( ! empty( $this->timezone_string ) ) {
			$this->timezone_obj = new DateTimeZone( $this->timezone_string );
		} else {
			if ( ! empty( $this->utc_offset ) ) {
				if ( $this->utc_offset >= 0 ) {
					$this->timezone_obj = new DateTimeZone( '+' . $this->utc_offset . '00' );
				} else {
					$this->timezone_obj = new DateTimeZone( $this->utc_offset . '00'  );
				}
			} else {
				$this->timezone_obj = new DateTimeZone( date_default_timezone_get() );
			}
		}

	}

	/**
	 * 現在年の数値
	 */
	public function year( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return (int) $d->format( 'Y' );
	}

	/**
	 * 現在月の数値
	 */
	public function month( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return (int) $d->format( 'n' );
	}

	/**
	 * 現在月の数値
	 */
	public function monthstr( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return $d->format( 'm' );
	}

	/**
	 * 現在日の数値
	 */
	public function day( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return (int) $d->format( 'j' );
	}

	/**
	 * 現在時刻の数値
	 */
	public function hour( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return (int) $d->format( 'G' );
	}

	/**
	 * 現在分の数値
	 */
	public function minute( $datetime_str = 'now' ) {
		$d = new DateTime( $datetime_str, $this->timezone_obj);
		return (int) $d->format( 'i' );
	}

	/**
	 * 本日の日付文字列
	 */
	public function today_str( $format = self::DEFAULT_DATE_FORMAT ) {
		$d = new DateTime( '', $this->timezone_obj);
		return $d->format( $format );
	}

	/**
	 * 現在の日付時刻文字列
	 */
	public function now_str( $format = self::DEFAULT_DATETIME_FORMAT  ) {
		$d = new DateTime( '', $this->timezone_obj);
		return $d->format( $format );
	}

	/**
	 * 現在のunixtime
	 */
	public function now_unixtime() {
		$d = new DateTime( '', $this->timezone_obj);
		return $d->getTimestamp();
	}

	/**
	 * 引数$datetime_strに対し、差分の日付を求める
	 * 引数$modifierには'+1 day'や'-1 month'などを入力
	 */
	public function diff_str( $datetime_str, $modifier, $format = self::DEFAULT_DATETIME_FORMAT ) {
		$date = new DateTime( $datetime_str, $this->timezone_obj );
		$date->modify( $modifier );
		return $date->format( $format );
	}


	/**
	 * 月の加減算をして日付文字列に
	 */
	public function xmonth_str( $months, $from_datetime_str = 'now', $format = self::DEFAULT_DATE_FORMAT) {
		$d = new DateTime( $from_datetime_str , $this->timezone_obj);

		//次月からb_monを求める
		$d_year = (int)$d->format('Y');
		$d_monx = (int)$d->format('n');
		$d_dayx = (int)$d->format('j');
		$ret_hour = (int)$d->format('H');
		$ret_minx = (int)$d->format('i');
		$ret_secx = (int)$d->format('s');

		$n_monx = $d_monx + (int)$months;
		if ( $n_monx <= 0 ) {
			$plusminusyear = floor( ($n_monx -1) / 12);
			$ret_monx = $n_monx % 12;
			$ret_monx = 12 + $ret_monx;
		} else {
			$plusminusyear = floor( $n_monx / 12 );
			$ret_monx = $n_monx % 12;
		}			
		$ret_year = $d_year + $plusminusyear;
		$ret_monx = sprintf('%02d', $ret_monx);
		//最終日判定
		$lastday = (new DateTimeImmutable())->modify('last day of' . $ret_year . '-' . $ret_monx)->format('j');
		if ( $d_dayx > $lastday ) {
			$ret_dayx = $lastday;
		}else{
			$ret_dayx = $d_dayx;
		}
		$ret_dayx = sprintf('%02d', $ret_dayx);

		$dn = new DateTime( $ret_year . '-' . $ret_monx . '-' . $ret_dayx . ' ' . $ret_hour . ':' . $ret_minx . ':' . $ret_secx , $this->timezone_obj);
		return $dn->format( $format );
	}


	/**
	 * 日の加減算をして日付文字列に
	 */
	public function xday_str( $days, $from_datetime_str = 'now', $format = self::DEFAULT_DATE_FORMAT) {
		$d = new DateTime( $from_datetime_str , $this->timezone_obj);
		$interval_spec = 'P' . abs( $days ) . 'D';
		if ( $days >= 0 ) {
			$d->add( new DateInterval( $interval_spec ) );
		} else {
			$d->sub( new DateInterval( $interval_spec ) );
		}
		return $d->format( $format );
	}

	/**
	 * 引数に渡された２つの日付文字列の差分を求めて日数を返す
	 * 対象の日付を過ぎていた場合はマイナスの値が返る
	 * 関数名は上記関数に合わせましたが、適切ではない場合は変更していただいて構いません。imai
	 */
	public function xday_num( $end_datetime_str, $start_datetime_str = 'now' ) {
		$start = new DateTime( $start_datetime_str, $this->timezone_obj );
		$end   = new DateTime( $end_datetime_str, $this->timezone_obj );
		$start->setTime( 0, 0 );
		$end->setTime( 0, 0 );
		$diff = $start->diff( $end );
		$ret = (int) $diff->days;
		if ( $diff->invert === 1 ) {
			$ret = -$ret;
		}
		return $ret;
	}

	/**
	 * 引数に渡された２つの日付文字列の差分を求めて秒数を返す
	 * 対象の時間を過ぎていた場合はマイナスの値が返る
	 */
	public function xsec_num( $end_datetime_str, $start_datetime_str = 'now' ) {
		$start = new DateTime( $start_datetime_str, $this->timezone_obj );
		$end   = new DateTime( $end_datetime_str, $this->timezone_obj );
		return $end->getTimestamp() - $start->getTimestamp();
	}

	/**
	 * unixtimeから日付時刻
	 */
	public function unixtime_to_str( $unixtime, $format = self::DEFAULT_DATETIME_FORMAT  ) {
		$d = new DateTime( '', $this->timezone_obj);
		$d->setTimestamp( $unixtime );
		return $d->format( $format );
	}

	/**
	 * WPのdate_i18n()でunixtimeをUTC基準ではなくロケール変換して保存した場合の関数。この場合はUTCに戻さないといけない
	 */
	public function wpunixtime_to_str( $unixtime, $format = self::DEFAULT_DATETIME_FORMAT  ) {
		$t = new DateTimeZone( 'UTC' );
		$d = new DateTime( '', $t);
		$d->setTimestamp( $unixtime );
		return $d->format( $format );
	}

	/**
	 * 日付時刻からunixtime
	 */
	public function str_to_unixtime( $datetime_str, $format = self::DEFAULT_DATETIME_FORMAT ) {
		$d = DateTime::createFromFormat( $format, $datetime_str, $this->timezone_obj);
		return $d->getTimestamp();
	}

	/**
	 * 秒から時刻文字列
	 */
	public function seconds_to_timestr( $seconds, $delimiter = self::DEFAULT_TIME_DELIMITER ) {
		$hhh = floor( $seconds / 3600 );
		$mmm = floor( ( $seconds / 60 ) % 60 );
		$sss = floor( $seconds % 60 );

		/*
		if ( $seconds < 60 ) {
			$str = sprintf( '%d', $sss );
		} elseif ( $seconds < 3600 ) {
			$str = sprintf ( '%d' . $delimiter . '%02d', $mmm, $sss );
		} else {
			$str = sprintf ( '%d' . $delimiter . '%02d'. $delimiter . '%02d', $hhh, $mmm, $sss );
		}*/

		// ↑ 00:00:00のフォーマットに変更 imai
		$str = sprintf( '%02d', $hhh ) . $delimiter . sprintf( '%02d', $mmm ) . $delimiter . sprintf( '%02d', $sss );

		return $str;
	}
}
