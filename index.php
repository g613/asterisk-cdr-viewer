<?php
require_once 'include/config.inc.php';

$system_name_array[] = 'cdrasterisk';
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
  
  #
  # asterisk regexp2sqllike
  #
  if ( empty($_POST['src']) ) {
  	$src_number = NULL;
  } else {
	$src_number = asteriskregexp2sqllike( 'src' );
  }
  if ( empty($_POST['dst']) ) {
  	$dst_number = NULL;
  } else {
	$dst_number = asteriskregexp2sqllike( 'dst' );
  }

  $enddate = "'$endyear-$endmonth-$endday 23:59:59'";
  $date_range = "calldate BETWEEN $startdate AND $enddate";
  $mod_vars['channel'][] = empty($_POST['channel']) ? NULL : $_POST['channel'];
  $mod_vars['channel'][] = empty($_POST['channel_mod']) ? NULL : $_POST['channel_mod'];
  $mod_vars['channel'][] = empty($_POST['channel_neg']) ? NULL : $_POST['channel_neg'];
  $mod_vars['src'][] = $src_number;
  $mod_vars['src'][] = empty($_POST['src_mod']) ? NULL : $_POST['src_mod'];
  $mod_vars['src'][] = empty($_POST['src_neg']) ? NULL : $_POST['src_neg'];
  $mod_vars['clid'][] = empty($_POST['clid']) ? NULL : $_POST['clid'];
  $mod_vars['clid'][] = empty($_POST['clid_mod']) ? NULL : $_POST['clid_mod'];
  $mod_vars['clid'][] = empty($_POST['clid_neg']) ? NULL : $_POST['clid_neg'];
  $mod_vars['dst'][] = $dst_number;
  $mod_vars['dst'][] = empty($_POST['dst_mod']) ? NULL : $_POST['dst_mod'];
  $mod_vars['dst'][] = empty($_POST['dst_neg']) ? NULL : $_POST['dst_neg'];
  $mod_vars['userfield'][] = empty($_POST['userfield']) ? NULL : $_POST['userfield'];
  $mod_vars['userfield'][] = empty($_POST['userfield_mod']) ? NULL : $_POST['userfield_mod'];
  $mod_vars['userfield'][] = empty($_POST['userfield_neg']) ? NULL : $_POST['userfield_neg'];
  $mod_vars['accountcode'][] = empty($_POST['accountcode']) ? NULL : $_POST['accountcode'];
  $mod_vars['accountcode'][] = empty($_POST['accountcode_mod']) ? NULL : $_POST['accountcode_mod'];
  $mod_vars['accountcode'][] = empty($_POST['accountcode_neg']) ? NULL : $_POST['accountcode_neg'];
  $result_limit = empty($_POST['limit']) ? $db_result_limit : $_POST['limit'];

  foreach ($mod_vars as $key => $val) {
    if (empty($val[0])) {
      unset($_POST[$key.'_mod']);
      $$key = NULL;
    } else {
	  $pre_like = '';
	  if ( $val[2] == 'true' ) {
	  	$pre_like = ' NOT ';
	  }
      switch ($val[1]) {
        case "contains":
          $$key = "AND $key $pre_like LIKE '%$val[0]%'";
        break;
        case "ends_with":
          $$key = "AND $key $pre_like LIKE '%$$val[0]'";
        break;
        case "exact":
	  	  if ( $val[2] == 'true' ) {
	          $$key = "AND $key != '$val[0]'";
		  } else {
	          $$key = "AND $key = '$val[0]'";
		  }
        break;
        case "asterisk-regexp":
          $$key = "AND $key $pre_like RLIKE '$val[0]'";
 	  $_POST[ $key .'_mod' ] = 'exact';
        break;
        case "begins_with":
        default:
          $$key = "AND $key $pre_like LIKE '$val[0]%'";
      }
    }
  }

  if ( $_POST['disposition_neg'] == 'true' ) {
  	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition != '$_POST[disposition]'";
  } else {
  	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition = '$_POST[disposition]'";
  }
  $duration = (!isset($_POST['dur_min']) || empty($_POST['dur_max'])) ? NULL : "AND duration BETWEEN '$_POST[dur_min]' AND '$_POST[dur_max]'";
  $order = empty($_POST['order']) ? 'ORDER BY calldate' : "ORDER BY $_POST[order]";
  $sort = empty($_POST['sort']) ? 'DESC' : $_POST['sort'];
  $group = empty($_POST['group']) ? 'day' : $_POST['group'];

// Build the "WHERE" part of the query
  $where = "WHERE $date_range $uniqueid $channel $src $clid $dst $userfield $accountcode $disposition $duration";

// Connecting, selecting database
  //$dbconn_string = empty($db_host) ? "dbname=$db_name user=$db_user password=$db_pass" : "host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass";
  //$dbconn = mysql_connect("$dbconn_string")
  $dbconn = mysql_connect( $db_host, $db_user, $db_pass )
    or die('Could not connect: ' . mysql_error());
  mysql_select_db($db_name,$dbconn);
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
  #$query = "SELECT to_char(calldate, '$db_calldate_format') AS calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $subquery $where $order $sort NULLS FIRST LIMIT $result_limit";
 
  $csv_header = 'What should I put over here? Hmmm...';
 
  if ( $_POST['need_csv'] == 'true' ) {
	$csv_file = md5(time() .'-'. $where ).'.csv';
  	$query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield into outfile '/tmp/$csv_file' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' FROM $subquery $where $order $sort LIMIT $result_limit";
  	$result = mysql_query($query) or die("Query failed: [$query] " . (mysql_error()));
  	//mysql_free_result($result);
	$csv_header = "<h2><a href='download.php?csv=$csv_file'> Download CSV file </a></h2>";
  }  else {
	  $query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $subquery $where $order $sort LIMIT $result_limit";
// END NEW
	  $result = mysql_query($query) or die("Query failed: [$query] " . (mysql_error()));
	}
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
  $query2 = "SELECT $group, count(*) AS total_calls, sum(duration) AS total_duration FROM $subquery2 AS $group GROUP BY $group ORDER BY $group ASC LIMIT $result_limit";
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
      $query2 = "SELECT DATE_FORMAT(calldate, '%Y-%m-%d %H') AS hour, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY DATE_FORMAT(calldate, '%Y-%m-%d %H') ORDER BY hour ASC LIMIT $result_limit";
      $graph_col_title = 'Hour';
    break;
    case "month":
      $query2 = "SELECT DATE_FORMAT(calldate, '%Y-%m') AS month, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY DATE_FORMAT(calldate, '%Y-%m') ORDER BY month ASC LIMIT $result_limit";
      $graph_col_title = 'Month';
    break;
    case "day":
    default:
      $query2 = "SELECT DATE_FORMAT(calldate, '%Y-%m-%d') AS day, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_table_name $where GROUP BY DATE_FORMAT(calldate, '%Y-%m-%e') ORDER BY day ASC LIMIT $result_limit";
      $graph_col_title = 'Day';
  }
  $tot_calls_raw = mysql_num_rows($result);
  $result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());
  $tot_calls = 0;
  $tot_duration = '0';
  $max_calls = '0';
  $max_duration = '0';
  $result_array = array();
  $tot_duration_secs = 0;

  //echo $query2;

  while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
	$tot_duration_secs += $row[2];
	$tot_calls += $row[1];
	if ( $row[1] > $max_calls ) {
		$max_calls = $row[1];
	}
	if ( $row[2] > $max_duration ) {
		$max_duration = $row[2];
	}
	array_push($result_array,$row);
  }
  $tot_duration = sprintf('%02d', intval($tot_duration_secs/60)).':'.sprintf('%02d', intval($tot_duration_secs%60));
?>
<div id="main">
<table class="cdr">
  <tr>
    <td>
      <?php include 'templates/form.tpl.php'?>
    </td>
    <td><?php echo $csv_header ?></td>
  </tr>
</table>

<!--<p>Your PostgreSQL query was: <?php // echo $query ?></p>-->
<!-- Display Call Detail Records -->
<?php
	if ( $tot_calls_raw ) {
		echo '<p class="center title"><a id="CDR"></a>Call Detail Record Search Returned '. $tot_calls_raw .' Calls </p>';
	}
?>
<table class="cdr">
<?php
  $i = 19;
  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
<!--<p>Your PostgreSQL query was: <?php echo $query2 ?></p>-->
<!-- Display Call Usage Graph -->
<p class="center title"><a id="Graph"></a>Call Detail Record Usage Graph by <?php echo $graph_col_title ?></p>
<table class="cdr">
<?php
  $i = 9;
  foreach ($result_array as $row) {
    ++$i;
    if ($i == 10) {
?>
  <tr>
    <th class="end_col"><?php echo $graph_col_title ?></th>
    <th class="center_col">Total Calls: <?php echo $tot_calls ?> / Max Calls: <?php echo $max_calls ?> / Total Duration: <?php echo $tot_duration ?></th>
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
  mysql_free_result($result);
  mysql_free_result($result2);
  mysql_close($dbconn);
//} else {
// Insert automatic query to be executed without posting here
//}
include 'templates/footer.tpl.php';
?>
