<?php

/* Recorded file */
function formatFiles($row) {
	global $system_monitor_dir, $system_fax_archive_dir, $system_audio_format;

	/* File name formats, please specify: */
	
	/* 
		caller-called-timestamp.wav 
	*/
	/* $recorded_file = $row['src'] .'-'. $row['dst'] .'-'. $row['call_timestamp'] */
	/* ============================================================================ */	

	/* 
		ends at the uniqueid.wav, for example: date-time-uniqueid.wav 
	
		thanks to Beto Reyes
	*/
	/*
	$recorded_file = glob($system_monitor_dir . '/*' . $row['uniqueid'] . '.' . $system_audio_format);
	if (count($recorded_file)>0) {
		$recorded_file = basename($recorded_file[0],".$system_audio_format");
	} else {
		$recorded_file = $row['uniqueid'];
	}
	*/
	/* ============================================================================ */	

	/* 
		uniqueid.wav 
	*/
	$recorded_file = $row['uniqueid'];
	/* ============================================================================ */	

	if (file_exists("$system_monitor_dir/$recorded_file.$system_audio_format")) {
		echo "    <td class=\"record_col\"><a href=\"download.php?audio=$recorded_file.$system_audio_format\" title=\"Listen to call recording\"><img src=\"/icons/small/sound.png\" alt=\"Call recording\" /></a></td>\n";
	} elseif (file_exists("$system_fax_archive_dir/$recorded_file.tif")) {
		echo "    <td class=\"record_col\"><a href=\"download.php?fax=$recorded_file.tif\" title=\"View FAX image\"><img src=\"/icons/small/text.png\" alt=\"FAX image\" /></a></td>\n";
	} else {
		echo "    <td class=\"record_col\"></td>\n";
	}
}

/* CDR Table Display Functions */
function formatCallDate($calldate) {
	echo "    <td class=\"record_col\">$calldate</td>\n";
}

function formatUniqueID($uniqueid) {
	$system = explode('-', $uniqueid, 2);
	echo "    <td class=\"record_col\"><abbr title=\"UniqueID: $uniqueid\">$system[0]</abbr></td>\n";
}

function formatChannel($channel) {
	$chan_type = explode('/', $channel, 2);
	echo "    <td class=\"record_col\"><abbr title=\"Channel: $channel\">$chan_type[0]</abbr></td>\n";
}

function formatSrc($src, $clid) {
	if (empty($src)) {
		echo "    <td class=\"record_col\">UNKNOWN</td>\n";
	} else {
		$clid = htmlspecialchars($clid);
		echo "    <td class=\"record_col\"><abbr title=\"Caller*ID: $clid\">$src</abbr></td>\n";
	}
}

function formatApp($app, $lastdata) {
	echo "    <td class=\"record_col\"><abbr title=\"Application: $app($lastdata)\">$app</abbr></td>\n";
}

function formatDst($dst, $dcontext) {
	global $rev_lookup_url;
	if (strlen($dst) == 11 and strlen($rev_lookup_url) > 0 ) {
		$rev = str_replace('%n', $dst, $rev_lookup_url);
		echo "    <td class=\"record_col\"><abbr title=\"Destination Context: $dcontext\"><a href=\"$rev\" target=\"reverse\">$dst</a></abbr></td>\n";
	} else {
		echo "    <td class=\"record_col\"><abbr title=\"Destination Context: $dcontext\">$dst</abbr></td>\n";
	}
}

function formatDisposition($disposition, $amaflags) {
	switch ($amaflags) {
		case 0:
			$amaflags = 'DOCUMENTATION';
			break;
		case 1:
			$amaflags = 'IGNORE';
			break;
		case 2:
			$amaflags = 'BILLING';
			break;
		case 3:
		default:
			$amaflags = 'DEFAULT';
	}
	echo "    <td class=\"record_col\"><abbr title=\"AMA Flag: $amaflags\">$disposition</abbr></td>\n";
}

function formatDuration($duration, $billsec) {
	$duration = sprintf('%02d', intval($duration/60)).':'.sprintf('%02d', intval($duration%60));
	$billduration = sprintf('%02d', intval($billsec/60)).':'.sprintf('%02d', intval($billsec%60));
	echo "    <td class=\"record_col\"><abbr title=\"Billing Duration: $billduration\">$duration</abbr></td>\n";
}

function formatUserField($userfield) {
	echo "    <td class=\"record_col\">$userfield</td>\n";
}

function formatAccountCode($accountcode) {
	echo "    <td class=\"record_col\">$accountcode</td>\n";
}

/* Asterisk RegExp parser */
function asteriskregexp2sqllike( $source_data, $user_num ) {
	$number = $user_num;
	if ( strlen($number) < 1 ) {
		$number = $_POST[$source_data];
	}
	if ( '__' == substr($number,0,2) ) {
		$number = substr($number,1);
	} elseif ( '_' == substr($number,0,1) ) {
		$number_chars = preg_split('//', substr($number,1), -1, PREG_SPLIT_NO_EMPTY);
		$number = '';
		foreach ($number_chars as $chr) {
			if ( $chr == 'X' ) {
				$number .= '[0-9]';
			} elseif ( $chr == 'Z' ) {
				$number .= '[1-9]';
			} elseif ( $chr == 'N' ) {
				$number .= '[2-9]';
			} elseif ( $chr == '.' ) {
				$number .= '.+';
			} elseif ( $chr == '!' ) {
				$_POST[ $source_data .'_neg' ] = 'true';
			} else {
				$number .= $chr;
			}
		}
		$_POST[ $source_data .'_mod' ] = 'asterisk-regexp';
	}
	return $number;
}

/* empty() wrapper. Thanks to Mikael Carlsson. */
function is_blank($value) {
	return empty($value) && !is_numeric($value);
}

?>
