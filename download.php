<?php

require_once 'include/config.inc.php';

header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

if (isset($_GET['audio'])) {
  header("Content-Type: application/$system_audio_format");
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
