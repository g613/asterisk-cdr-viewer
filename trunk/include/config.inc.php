<?php
/* Time Zone Configuration */
$tz = 'America/Chicago';
date_default_timezone_set($tz);

/* PostgreSQL Database Configuration */
/* An empty $db_host value will use the local Unix socket. */
$db_host = '';
$db_port = '5432';
$db_user = 'apache-asterisk';
$db_pass = 'apache-asterisk';
$db_name = 'asterisk';
$db_table_name = 'cdr';

/* $db_calldate_format is the PostgreSQL date format returned */
$db_calldate_format = 'YYYY-MM-DD HH24:MI:SS';

/* $db_result_limit is the PostgreSQL 'LIMIT' appended to the query */
$db_result_limit = '10000';

/* Asterisk Server Name & Access Configuration */
$system_access_array['host1'] = array('user1', 'user2');
$system_access_array['host2'] = array('user1');
$system_access_array['hostN'] = array('user1', 'userN');

/* Kerberos Single Sign On */
$krb_sso = TRUE;

/* $system_monitor_dir is the directory where call recordings are stored */
$system_monitor_dir = '/var/spool/asterisk/monitor';

/* $system_fax_archive_dir is the directory where sent/received fax images are stored */
$system_fax_archive_dir = '/var/spool/asterisk/fax-gw/archive';

/* Reverse lookup URL where "%n" is replace with the destination number */
$rev_lookup_url = 'http://www.whitepages.com/search/ReversePhone?full_phone=%n';


/* CDR Table Display Functions */
function formatCallDate($calldate) {
  echo "    <td class=\"record_col\">$calldate</td>\n";
}

function formatUniqueID($uniqueid) {
  global $system_monitor_dir, $system_fax_archive_dir;
  $system = explode('-', $uniqueid, 2);
  if (file_exists("$system_monitor_dir/$uniqueid.ogg")) {
    echo "    <td class=\"record_col\"><a href=\"download.php?audio=$uniqueid.ogg\" title=\"Listen to call recording\"><img src=\"/icons/small/sound.png\" alt=\"Call recording\" /></a></td>\n";
    echo "    <td class=\"record_col\"><abbr title=\"UniqueID: $uniqueid\">$system[0]</abbr></td>\n";
  } elseif (file_exists("$system_fax_archive_dir/$uniqueid.tif")) {
    echo "    <td class=\"record_col\"><a href=\"download.php?fax=$uniqueid.tif\" title=\"View FAX image\"><img src=\"/icons/small/text.png\" alt=\"FAX image\" /></a></td>\n";
    echo "    <td class=\"record_col\"><abbr title=\"UniqueID: $uniqueid\">$system[0]</abbr></td>\n";
  } else {
    echo "    <td class=\"record_col\"></td>\n";
    echo "    <td class=\"record_col\"><abbr title=\"UniqueID: $uniqueid\">$system[0]</abbr></td>\n";
  }
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
  if (strlen($dst) == 11) {
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

?>
