<?php

require_once 'include/config.inc.php';

header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

if (isset($_GET['audio'])) {
	$extension = strtolower(substr(strrchr($_GET[audio],"."),1));
	$ctype ='';
	switch( $extension ) {
		case "wav16":
			$ctype="audio/x-wav";
			break;
		case "wav":
			$ctype="audio/x-wav";
			break;
		case "ulaw":
			$ctype="audio/basic";
			break;
		case "alaw":
			$ctype="audio/x-alaw-basic";
			break;
		case "sln":
			$ctype="audio/x-wav";
			break;
		case "gsm":
			$ctype="audio/x-gsm";
			break;
		case "g729":
			$ctype="audio/x-g729";
			break;
		default: 
			$ctype="application/$system_audio_format";
			break ;
	}
	header("Content-Type: $ctype");
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize("$system_monitor_dir/$_GET[audio]"));
	header("Content-Disposition: attachment; filename=\"$_GET[audio]\"");
	readfile("$system_monitor_dir/$_GET[audio]");
} elseif (isset($_GET['fax'])) {
	header('Content-Type: image/tiff');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize("$system_fax_archive_dir/$_GET[fax]"));
	header("Content-Disposition: attachment; filename=\"$_GET[fax]\"");
	readfile("$system_fax_archive_dir/$_GET[fax]");
} elseif (isset($_GET['csv'])) {
	header('Content-Type: text/csv');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize("/tmp/$_GET[csv]"));
	header("Content-Disposition: attachment; filename=\"$_GET[csv]\"");
	readfile("$system_tmp_dir/$_GET[csv]");
}

exit();
?>
