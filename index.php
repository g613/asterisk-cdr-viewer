<?php

require_once 'include/config.inc.php';

include 'templates/header.tpl.php';
include 'templates/form.tpl.php';

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
$starthour = empty($_POST['starthour']) ? '00' : $_POST['starthour'];
$startmin = empty($_POST['startmin']) ? '00' : $_POST['startmin'];

$startdate = "'$startyear-$startmonth-$startday $starthour:$startmin:00'";
$endmonth = empty($_POST['endmonth']) ? date('m') : $_POST['endmonth'];  
$endyear = empty($_POST['endyear']) ? date('Y') : $_POST['endyear'];  

if (empty($_POST['endday']) || (isset($_POST['endday']) && ($_POST['endday'] > date('t', strtotime("$endyear-$endmonth-01"))))) {
	$endday = $_POST['endday'] = date('t', strtotime("$endyear-$endmonth"));
} else {
	$endday = $_POST['endday'];
}
$endhour = empty($_POST['endhour']) ? '23' : $_POST['endhour'];
$endmin = empty($_POST['endmin']) ? '59' : $_POST['endmin'];

$enddate = "'$endyear-$endmonth-$endday $endhour:$endmin:59'";

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

$date_range = "calldate BETWEEN $startdate AND $enddate";
$mod_vars['channel'][] = empty($_POST['src_channel']) ? NULL : $_POST['src_channel'];
$mod_vars['channel'][] = empty($_POST['src_channel_mod']) ? NULL : $_POST['src_channel_mod'];
$mod_vars['channel'][] = empty($_POST['src_channel_neg']) ? NULL : $_POST['src_channel_neg'];
$mod_vars['src'][] = $src_number;
$mod_vars['src'][] = empty($_POST['src_mod']) ? NULL : $_POST['src_mod'];
$mod_vars['src'][] = empty($_POST['src_neg']) ? NULL : $_POST['src_neg'];
$mod_vars['clid'][] = empty($_POST['clid']) ? NULL : $_POST['clid'];
$mod_vars['clid'][] = empty($_POST['clid_mod']) ? NULL : $_POST['clid_mod'];
$mod_vars['clid'][] = empty($_POST['clid_neg']) ? NULL : $_POST['clid_neg'];
$mod_vars['dstchannel'][] = empty($_POST['dst_channel']) ? NULL : $_POST['dst_channel'];
$mod_vars['dstchannel'][] = empty($_POST['dst_channel_mod']) ? NULL : $_POST['dst_channel_mod'];
$mod_vars['dstchannel'][] = empty($_POST['dst_channel_neg']) ? NULL : $_POST['dst_channel_neg'];
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
			break;
			case "begins_with":
			default:
				$$key = "AND $key $pre_like LIKE '$val[0]%'";
		}
	}
}

if ( isset($_POST['disposition_neg']) && $_POST['disposition_neg'] == 'true' ) {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition != '$_POST[disposition]'";
} else {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition = '$_POST[disposition]'";
}

$duration = (!isset($_POST['dur_min']) || empty($_POST['dur_max'])) ? NULL : "AND duration BETWEEN '$_POST[dur_min]' AND '$_POST[dur_max]'";
$order = empty($_POST['order']) ? 'ORDER BY calldate' : "ORDER BY $_POST[order]";
$sort = empty($_POST['sort']) ? 'DESC' : $_POST['sort'];
$group = empty($_POST['group']) ? 'day' : $_POST['group'];

// Build the "WHERE" part of the query
$where = "WHERE $date_range $uniqueid $channel $dstchannel $src $clid $dst $userfield $accountcode $disposition $duration";

// Connecting, selecting database

$dbconn = mysql_connect( $db_host, $db_user, $db_pass ) or die('Could not connect: ' . mysql_error());
mysql_select_db($db_name,$dbconn);
		
if ( isset($_POST['need_csv']) && $_POST['need_csv'] == 'true' ) {
	$csv_file = md5(time() .'-'. $where ).'.csv';
	if (! file_exists("$system_tmp_dir/$csv_file")) {
		$query = "(SELECT 'calldate', 'clid', 'src', 'dst','dcontext', 'channel', 'dstchannel', 'lastapp', 'lastdata', 'duration', 'billsec', 'disposition', 'amaflags', 'accountcode', 'uniqueid', 'userfield') union (SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield into outfile '$system_tmp_dir/$csv_file' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit)";
		$result = mysql_query($query) or die("Query failed: [$query] " . (mysql_error()));
	}
	echo "<p class='right title'><a href='download.php?csv=$csv_file'>Click here to download CSV file</a></p>";
}

if ( isset($_POST['need_html']) && $_POST['need_html'] == 'true' ) {
	$query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit";
	$result = mysql_query($query) or die("Query failed: [$query] " . (mysql_error()));
}

if ( isset($result) ) {
	$tot_calls_raw = mysql_num_rows($result);
} else {
	$tot_calls_raw = 0;
}

if ( $tot_calls_raw ) {
	echo '<p class="center title">Call Detail Record - Search Returned '. $tot_calls_raw .' Calls </p><table class="cdr">';
	
	$i = $h_step - 1;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		++$i;
		if ($i == $h_step) {
		?>
			<tr>
			<th class="record_col">Call Date</th>
			<th class="record_col">File</th>
			<th class="record_col">System</th>
			<th class="record_col">Src Channel</th>
			<th class="record_col">Source</th>
			<th class="record_col">Application</th>
			<th class="record_col">Destination</th>
			<th class="record_col">Dst Channel</th>
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
	echo "</table>";
}

if ( isset($result) ) {
	mysql_free_result($result);
}
?>

<!-- Display Call Usage Graph -->
<?php

echo '<a id="Graph"></a>';

//NEW GRAPHS
$group_by_field = $group;

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
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m-%d %H')";
		$graph_col_title = 'Hour';
	break;
	case "hour_of_day":
		$group_by_field = "DATE_FORMAT(calldate, '%H')";
		$graph_col_title = 'Hour of day';
	break;
	case "month":
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m')";
		$graph_col_title = 'Month';
	break;
	case "day_of_week":
		$group_by_field = "DATE_FORMAT(calldate, '%w - %W')";
		$graph_col_title = 'Day of week';
	break;
	case "minutes1":
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m-%d %H:%i')";
		$graph_col_title = 'Minute';
	break;
	case "minutes10":
		$group_by_field = "CONCAT(SUBSTR(DATE_FORMAT(calldate, '%Y-%m-%d %H:%i'),1,15), '0')";
		$graph_col_title = '10 Minutes';
	break;
	case "day":
	default:
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m-%d')";
		$graph_col_title = 'Day';
}

if ( isset($_POST['need_chart']) && $_POST['need_chart'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";
	$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());

	$tot_calls = 0;
	$tot_duration = '0';
	$max_calls = '0';
	$max_duration = '0';
	$tot_duration_secs = 0;
	$result_array = array();

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

	if ( $tot_calls ) {
		echo '<p class="center title">Call Detail Record Call - Graph by '.$graph_col_title.'</p><table class="cdr">';
	}

	$h_step = (int)($h_step/2);

	$i = $h_step - 1;
	foreach ($result_array as $row) {
		++$i;
		if ($i == $h_step) {
			?>
			<tr>
			<th class="end_col"><?php echo $graph_col_title ?></th>
			<th class="center_col">Total Calls: <?php echo $tot_calls ?> / Max Calls: <?php echo $max_calls ?> / Total Duration: <?php echo $tot_duration ?></th>
			<th class="end_col">Average Call Time</th>
			<th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="/icons/small/back.png" alt="CDR Table" /></a></th>
			<th class="img_col"><a href="#Graph" title="Go to the CDR Graph"><img src="/icons/small/image2.png" alt="CDR Graph" /></a></th>
			</tr>
			<?php
			/* $i = 0; */
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
	echo "</table>";
	mysql_free_result($result2);
}
?>

</div>

<?php

mysql_close($dbconn);

include 'templates/footer.tpl.php';

?>
