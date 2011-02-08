<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php
require_once 'include/config.inc.php';

/* Retrieve authenticated user and system access */
$php_auth_user = ($krb_sso) ? explode('@', $_SERVER['PHP_AUTH_USER'], -1) : array($_SERVER['PHP_AUTH_USER']);
foreach($system_access_array as $key => $user_list) {
  if (in_array($php_auth_user[0], $user_list)) {
    $system_name_array[] = $key;
  }
}

include 'templates/header.tpl.php';
?>

<?php
//if (!empty($_POST['posted'])) {
  $uniqueid = (empty($_POST['uniqueid']) || $_POST['uniqueid'] == 'all') ? NULL : "AND uniqueid LIKE '$_POST[uniqueid]%'";
  $startmonth = empty($_POST['startmonth']) ? date('m') : $_POST['startmonth'];
  $startyear = empty($_POST['startyear']) ? date('Y') : $_POST['startyear'];
  if (empty($_POST['startday'])) {
    $startday = '01';
  } elseif (isset($_POST['startday']) && ($_POST['startday'] > date('t', strtotime("$startyear-$startmonth")))) {
    $startday = $_POST['startday'] = date('t', strtotime("$startyear-$startmonth"));
  } else {
    $startday = $_POST['startday'];
  }
  $startdate = "'$startyear-$startmonth-$startday 00:00:00'";
  $endmonth = empty($_POST['endmonth']) ? date('m') : $_POST['endmonth'];  
  $endyear = empty($_POST['endyear']) ? date('Y') : $_POST['endyear'];  
  if (empty($_POST['endday']) || (isset($_POST['endday']) && ($_POST['endday'] > date('t', strtotime("$endyear-$endmonth-01"))))) {
    $endday = $_POST['endday'] = date('t', strtotime("$endyear-$endmonth"));
  } else {
    $endday = $_POST['endday'];
  }
  $enddate = "'$endyear-$endmonth-$endday 23:59:59'";
  $date_range = "calldate BETWEEN $startdate AND $enddate";
  $mod_vars['channel'][] = empty($_POST['channel']) ? NULL : $_POST['channel'];
  $mod_vars['channel'][] = empty($_POST['channel_mod']) ? NULL : $_POST['channel_mod'];
  $mod_vars['src'][] = empty($_POST['src']) ? NULL : $_POST['src'];
  $mod_vars['src'][] = empty($_POST['src_mod']) ? NULL : $_POST['src_mod'];
  $mod_vars['clid'][] = empty($_POST['clid']) ? NULL : $_POST['clid'];
  $mod_vars['clid'][] = empty($_POST['clid_mod']) ? NULL : $_POST['clid_mod'];
  $mod_vars['dst'][] = empty($_POST['dst']) ? NULL : $_POST['dst'];
  $mod_vars['dst'][] = empty($_POST['dst_mod']) ? NULL : $_POST['dst_mod'];
  $mod_vars['userfield'][] = empty($_POST['userfield']) ? NULL : $_POST['userfield'];
  $mod_vars['userfield'][] = empty($_POST['userfield_mod']) ? NULL : $_POST['userfield_mod'];
  $mod_vars['accountcode'][] = empty($_POST['accountcode']) ? NULL : $_POST['accountcode'];
  $mod_vars['accountcode'][] = empty($_POST['accountcode_mod']) ? NULL : $_POST['accountcode_mod'];
  foreach ($mod_vars as $key => $val) {
    if (empty($val[0])) {
      unset($_POST[$key.'_mod']);
      $$key = NULL;
    } else {
      switch ($val[1]) {
        case "contains":
          $$key = "AND $key LIKE '%$val[0]%'";
        break;
        case "ends_with":
          $$key = "AND $key LIKE '%$$val[0]'";
        break;
        case "exact":
          $$key = "AND $key = '$val[0]'";
        break;
        case "begins_with":
        default:
          $$key = "AND $key LIKE '$val[0]%'";
      }
    }
  }
  $disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition = '$_POST[disposition]'";
  $duration = (!isset($_POST['dur_min']) || empty($_POST['dur_max'])) ? NULL : "AND duration BETWEEN '$_POST[dur_min]' AND '$_POST[dur_max]'";
  $order = empty($_POST['order']) ? 'ORDER BY calldate' : "ORDER BY $_POST[order]";
  $sort = empty($_POST['sort']) ? 'DESC' : $_POST['sort'];
  $group = empty($_POST['group']) ? 'day' : $_POST['group'];

// Build the "WHERE" part of the query
  $where = "WHERE $date_range $uniqueid $channel $src $clid $dst $userfield $accountcode $disposition $duration";

// Connecting, selecting database
  $dbconn_string = empty($db_host) ? "dbname=$db_name user=$db_user password=$db_pass" : "host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass";
  $dbconn = pg_connect("$dbconn_string")
    or die('Could not connect: ' . pg_last_error());
//NEW
  $i = count($system_name_array);
  if ($i == 1) {
    $subquery = "$system_name_array[0].$db_table_name";
  } elseif ($i > 1) {
    $subquery = '(';
    foreach ($system_name_array as $system) {
      $subquery .= "SELECT * FROM $system.$db_table_name";
      if ($i > 1) {
        $subquery .= " UNION ALL ";
        $i--;
      } else {
        $subquery .= ') AS query';
      }
    }
  }
  $query = "SELECT to_char(calldate, '$db_calldate_format') AS calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $subquery $where $order $sort NULLS FIRST LIMIT $db_result_limit";
// END NEW
  $result = pg_query($query) or die('Query failed: ' . pg_last_error());
//NEW GRAPHS
  $subquery2 = '(';
  $j = count($system_name_array);
//Prepare subquery aggregate
  foreach ($system_name_array as $system) {
    $subquery2 .= "SELECT * FROM $system.$db_table_name $where";
    if ($j > 1) {
      $subquery2 .= " UNION ALL ";
      $j--;
    } else {
      $subquery2 .= ')';
    }
  }
  $query2 = "SELECT $group, count(*) AS total_calls, sum(duration) AS total_duration FROM $subquery2 AS $group GROUP BY $group ORDER BY $group ASC LIMIT $db_result_limit";
  switch ($group) {
    case "accountcode":
      $graph_col_title = 'Account Code';
    case "dst":
      $graph_col_title = 'Destination Number';
    case "src":
      $graph_col_title = 'Source Number';
    case "userfield":
      $graph_col_title = 'User Field';
    break;
    case "hour":
      $query2 = "SELECT to_char(calldate, 'YYYY-MM-DD HH24') AS hour, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY to_char(calldate, 'YYYY-MM-DD HH24') ORDER BY hour ASC LIMIT $db_result_limit";
      $graph_col_title = 'Hour';
    break;
    case "month":
      $query2 = "SELECT to_char(calldate, 'YYYY-MM') AS month, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY to_char(calldate, 'YYYY-MM') ORDER BY month ASC LIMIT $db_result_limit";
      $graph_col_title = 'Month';
    break;
    case "day":
    default:
      $query2 = "SELECT to_char(calldate, 'YYYY-MM-DD') AS day, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY to_char(calldate, 'YYYY-MM-DD') ORDER BY day ASC LIMIT $db_result_limit";
      $graph_col_title = 'Day';
  }
  $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
  $tot_calls = pg_num_rows($result);
  if ($tot_calls > '0') {
    $tot_duration_secs = array_sum(pg_fetch_all_columns($result2, 2));
    $tot_duration = sprintf('%02d', intval($tot_duration_secs/60)).':'.sprintf('%02d', intval($tot_duration_secs%60));
    $max_calls = max(pg_fetch_all_columns($result2, 1));
    $max_duration = max(pg_fetch_all_columns($result2, 2));
  } else {
    $tot_duration = '0';
    $max_calls = '0';
    $max_duration = '0';
  }
?>
<div id="main">
<table class="cdr">
  <tr>
    <td>
      <?php include 'templates/form.tpl.php'?>
    </td>
    <td>What should I put over here? Hmmm...</td>
  </tr>
</table>
<!--<p>Your PostgreSQL query was: <?php // echo $query ?></p>-->
<!-- Display Call Detail Records -->
<p class="center title"><a id="CDR"></a>Call Detail Record Search Returned <?php echo $tot_calls ?> Calls</p>
<table class="cdr">
<?php
  $i = 19;
  while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
    ++$i;
    if ($i == 20) {
?>
  <tr>
    <th class="record_col">Call Date</th>
    <th class="record_col">File</th>
    <th class="record_col">System</th>
    <th class="record_col">Channel</th>
    <th class="record_col">Source</th>
    <th class="record_col">Application</th>
    <th class="record_col">Destination</th>
    <th class="record_col">Destination Channel</th>
    <th class="record_col">Disposition</th>
    <th class="record_col">Duration</th>
    <th class="record_col">Userfield</th>
    <th class="record_col">Account</th>
    <th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="/icons/small/back.png" alt="CDR Table" /></a></th>
    <th class="img_col"><a href="#Graph" title="Go to the top of the CDR graph"><img src="/icons/small/image2.png" alt="CDR Graph" /></a></th>
  </tr>
<?php
      $i = 0;
    }
    echo "  <tr class=\"record\">\n";
    formatCallDate($row['calldate']);
    formatUniqueID($row['uniqueid']);
    formatChannel($row['channel']);
    formatSrc($row['src'], $row['clid']);
    formatApp($row['lastapp'], $row['lastdata']);
    formatDst($row['dst'], $row['dcontext']);
    formatChannel($row['dstchannel']);
    formatDisposition($row['disposition'], $row['amaflags']);
    formatDuration($row['duration'], $row['billsec']);
    formatUserField($row['userfield']);
    formatAccountCode($row['accountcode']);
    echo "    <td></td>\n";
    echo "    <td></td>\n";
    echo "  </tr>\n";
    }
?>
</table>
<!--<p>Your PostgreSQL query was: <?php // echo $query2 ?></p>-->
<!-- Display Call Usage Graph -->
<p class="center title"><a id="Graph"></a>Call Detail Record Usage Graph by <?php echo $graph_col_title ?></p>
<table class="cdr">
<?php
  $i = 9;
  while ($row = pg_fetch_array($result2, NULL, PGSQL_NUM)) {
    ++$i;
    if ($i == 10) {
?>
  <tr>
    <th class="end_col"><?php echo $graph_col_title ?></th>
    <th class="center_col">Total Calls: <?php echo $tot_calls ?> / Total Duration: <?php echo $tot_duration ?></th>
    <th class="end_col">Average Call Time</th>
    <th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="/icons/small/back.png" alt="CDR Table" /></a></th>
    <th class="img_col"><a href="#Graph" title="Go to the CDR Graph"><img src="/icons/small/image2.png" alt="CDR Graph" /></a></th>
  </tr>
<?php
      $i = 0;
    }
    $avg_call_time = sprintf('%02d', intval(($row[2]/$row[1])/60)).':'.sprintf('%02d', intval($row[2]/$row[1]%60));
    $bar_calls = $row[1]/$max_calls*100;
    $percent_tot_calls = intval($row[1]/$tot_calls*100);
    $bar_duration = $row[2]/$max_duration*100;
    $percent_tot_duration = intval($row[2]/$tot_duration_secs*100);
    $duration = sprintf('%02d', intval($row[2]/60)).':'.sprintf('%02d', intval($row[2]%60));
    echo "  <tr>\n";
    echo "    <td class=\"end_col\">$row[0]</td><td class=\"center_col\"><div class=\"bar_calls\" style=\"width : $bar_calls%\">$row[1] - $percent_tot_calls%</div><div class=\"bar_duration\" style=\"width : $bar_duration%\">$duration - $percent_tot_duration%</div></td><td class=\"end_col\">$avg_call_time</td>\n";
    echo "    <td></td>\n";
    echo "    <td></td>\n";
    echo "  </tr>\n";
  }
?>
</table>
</div>
<?php
  pg_free_result($result);
  pg_free_result($result2);
  pg_close($dbconn);
//} else {
// Insert automatic query to be executed without posting here
//}
include 'templates/footer.tpl.php';
?>
