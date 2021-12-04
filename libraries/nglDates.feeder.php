<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# date
## nglDates *extends* nglFeeder [2018-08-15]
Utilidades para operaciones con fechas y horas
Generación de Calendarios

https://github.com/hytcom/wiki/blob/master/nogal/docs/dates.md

*/
namespace nogal;

class nglDates extends nglFeeder implements inglFeeder {

	private $aSettings;
	private $aSeconds;

	final public function __init__($mArguments=null) {
		$aDates = [];

		$aDays = \explode(",", NGL_DATE_DAYS);
		$aDates["days"] = $aDates["days_short"] = [];
		for($x=0;$x<7;$x++) {
			$aDates["days"][$x+1] = $aDays[$x];
			$aDates["days_short"][$x+1] = self::call("unicode")->substr($aDays[$x], 0 ,3);
		}

		// months
		$aMonths = \explode(",", NGL_DATE_MONTHS);
		$aDates["months"] = $aDates["months_short"] = [];
		for($x=0;$x<12;$x++) {
			$aDates["months"][$x+1] = $aMonths[$x];
			$aDates["months_short"][$x+1] = self::call("unicode")->substr($aMonths[$x], 0 ,3);
		}

		$this->aSettings = $aDates;

		// seconds
		$aSeconds 			= [];
		$aSeconds["year"] 	= 31536000;
		$aSeconds["month"] 	= 2592000;
		$aSeconds["day"] 	= 86400;
		$aSeconds["hour"] 	= 3600;
		$aSeconds["minute"] = 60;
		$aSeconds["second"] = 1;
		$this->aSeconds = $aSeconds;
		
	}

	public function calendar($mDate=null, $bComplete=false, $aEvents=false) {
		$aDate = $this->info($mDate);
		return $this->CalendarMonth($aDate["year"], $aDate["month"], $bComplete, $aEvents);
	}

	public function daysdiff($sDate1, $sDate2=null) {
		$nDate1 = \strtotime($sDate1);
		$nDate2 = ($sDate2===null) ? \time() : \strtotime($sDate2);

		if(!($date1 = \date_create($sDate1))) { return false; }
		if(!($date2 = \date_create($sDate2))) { return false; }
		$interval = \date_diff($date1, $date2);
		return $interval->format("%a");
	}

	public function elapsed($mTime, $sFrom="second", $bReturnString=false) {
		$sFrom = \strtolower($sFrom);

		if(!isset($this->aSeconds[$sFrom]) && \is_string($mTime)) {
			if(!($now = \date_create($sFrom))) { return false; }
			if(!($datefrom = \date_create($mTime))) { return false; }
			$interval = \date_diff($now, $datefrom);
			$aElapsed 			= [];
			$aElapsed["year"] 	= $interval->y;
			$aElapsed["month"] 	= $interval->m;
			$aElapsed["day"] 	= $interval->d;
			$aElapsed["hour"] 	= $interval->h;
			$aElapsed["minute"] = $interval->i;
			$aElapsed["second"] = $interval->s;
		} else {
			if(!isset($this->aSeconds[$sFrom])) { $sFrom = "second"; }
			$nSeconds = ($mTime * $this->aSeconds[$sFrom]);
			$aElapsed = [];
			foreach($this->aSeconds as $sUnit => $nToken) {
				if($nSeconds < $nToken) {
					$aElapsed[$sUnit] = 0;
				} else {
					$aElapsed[$sUnit] = \floor($nSeconds/$nToken);
					$nSeconds = $nSeconds%$nToken;
				}
			}
		}

		if($bReturnString) {
			foreach(\array_keys(\array_reverse($this->aSeconds, true)) as $sKey) {
				if($aElapsed[$sKey]=="0") { unset($aElapsed[$sKey]); } else { break; }
			}

			foreach($aElapsed as $sPart => &$nValue) {
				$nValue = $nValue." ".$sPart.(($nValue!=1) ? "s" : "");
			}

			return \implode(" ", $aElapsed);
		}

		return $aElapsed;
	}

	public function info($mTime=null) {
		if($mTime===null) {
			$nTime = \time();
		} else {
			$nTime = (!\preg_match("/^[0-9]+$/", $mTime)) ? \strtotime($mTime) : $mTime;
		}

		$aDate = [];
		$aDate["timestamp"]				= $nTime;
		$aDate["date"]					= \date("Y-m-d", $nTime);
		$aDate["datetime"]				= \date("Y-m-d H:i:s", $nTime);
		$aDate["number"]				= \date("j", $nTime);
		$aDate["day"]					= \date("d", $nTime);
		$aDate["month"]					= \date("m", $nTime);
		$aDate["year"]					= \date("Y", $nTime);
		$aDate["week"]					= \date("W", $nTime);
		$aDate["day_week"]				= \date("w", $nTime);
		$aDate["week_ini"] 				= \date("Y-m-d", \strtotime($aDate["date"]." -".$aDate["day_week"]." day"));
		$aDate["week_end"] 				= \date("Y-m-d", \strtotime($aDate["date"]." +".(6-$aDate["day_week"])." day"));
		$aDate["single_month"]			= \date("n", $nTime);
		$aDate["single_year"]			= \date("y", $nTime);
		$aDate["day_name"]				= $this->aSettings["days"][\date("w", $nTime)+1];
		$aDate["day_shortname"]			= $this->aSettings["days_short"][\date("w", $nTime)+1];
		$aDate["month_name"]			= $this->aSettings["months"][\date("n", $nTime)];
		$aDate["month_shortname"]		= $this->aSettings["months_short"][\date("n", $nTime)];
		$aDate["ampm"]					= \date("A", $nTime);
		$aDate["hour_12"]				= \date("h", $nTime);
		$aDate["hour"]					= \date("H", $nTime);
		$aDate["time"]					= \date("H:i:s", $nTime);
		$aDate["minutes"]				= \date("i", $nTime);
		$aDate["seconds"]				= \date("s", $nTime);
		
		return $aDate;
	}

	public function microtimer($nTimeIni=null) {
		if($nTimeIni===null) { $nTimeIni = self::startime(); }
		$nReturn = \microtime(true) - (float)$nTimeIni;
		return (!\strpos($nReturn, "E")) ? $nReturn : 0;
	}

	public function monthsdiff($sDate1, $sDate2=null) {
		$nDate1 = \strtotime($sDate1);
		$nDate2 = ($sDate2===null) ? \time() : \strtotime($sDate2);
		
		$sMonth1 = \date("Y", $nDate1)*12 + \date("n", $nDate1);
		$sMonth2 = \date("Y", $nDate2)*12 + \date("n", $nDate2);		
		return \abs(\floor($sMonth1 - $sMonth2));
	}

	public function timesdiff($sTime1, $sTime2=null) {
		$nTime1 = \strtotime($sTime1);
		$nTime2 = ($sTime2===null) ? \time() : \strtotime($sTime2);
		
		if($nTime2 < $nTime1) {
			$nTime2 += 86400;
			return ($nTime2 - $nTime1);
		}
		return ($nTime2 - $nTime1);
	}

	public function settings() {
		return $this->aSettings;
	}

	private function CalendarMonth($nYear, $nMonth, $bComplete, $aEvents=false) {
		$nDay			= 1;
		$nTime			= \mktime(0,0,0,$nMonth, $nDay, $nYear);
		$nDaysOfMonth	= \date("t", $nTime);
		$nStartDay		= \date("w", $nTime);

		$aMonth = [];
		$aWeek = \array_fill(0, 7, array());

		for($y=0;$y<7;$y++) {
			if($y<$nStartDay) {
				if($bComplete) {
					$aWeek[$y] = $this->info(\mktime(0,0,0,$nMonth, ($y-$nStartDay+1), $nYear));
					$aWeek[$y]["day_of_month"] = "previus";
				}
			} else {
				$aWeek[$y] = $this->info(\mktime(0,0,0,$nMonth, $nDay, $nYear));
				$aWeek[$y]["day_of_month"] = "current";
				$nDay++;
			}
		}
		$aMonth[] = $aWeek;

		$nWeeksLimit = ($nDaysOfMonth-$nDay+1);
		for($x=0; $x<$nWeeksLimit; $x+=7) {
			$aWeek = \array_fill(0, 7, []);
			for($y=0;$y<7;$y++) {
				if($nDaysOfMonth<$nDay && !$bComplete) { break; }
				$aWeek[$y] = $this->info(\mktime(0,0,0,$nMonth, $nDay, $nYear));
				$aWeek[$y]["day_of_month"] = ($nDay>$nDaysOfMonth) ? "next" : "current";
				$nDay++;
			}
			$aMonth[] = $aWeek;
			if($nDaysOfMonth<$nDay && !$bComplete) { break; }
		}

		$aFinalMonth = [];
		foreach($aMonth as $aWeek) {
			$aFinalWeek = [];
			foreach($aWeek as $nDay => $aDay) {
				if(isset($aDay["date"])) {
					// eventos
					$aDay["events"] = [];
					if(\is_array($aEvents) && isset($aEvents[$aDay["date"]])) {
						$aDay["events"] = $aEvents[$aDay["date"]];
					}
					$aFinalWeek[$aDay["date"]] = $aDay;
				} else {
					$aFinalWeek[$nDay] = $aDay;
				}
			}
			$aFinalMonth[] = $aFinalWeek;
		}

		return $aFinalMonth;
	}
}

?>