<?php
$system_monitor_dir = '/var/spool/asterisk/monitor';
$system_fax_archive_dir = '/var/spool/asterisk/fax-gw/archive';
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
if (isset($_GET['audio'])) {
  header('Content-Type: application/ogg');
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
}
exit();
?>
