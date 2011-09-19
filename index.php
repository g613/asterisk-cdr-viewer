<?php

require_once 'include/config.inc.php';
require_once 'include/functions.inc.php';

include 'templates/header.tpl.php';
include 'templates/form.tpl.php';

// Connecting, selecting database
$dbconn = mysql_connect( "$db_host:$db_port", $db_user, $db_pass ) or die('Could not connect: ' . mysql_error());
mysql_select_db($db_name,$dbconn);
		
foreach ( array_keys($_POST) as $key ) {
	$_POST[$key] = preg_replace('/;/', ' ', $_POST[$key]);
	$_POST[$key] = mysql_real_escape_string($_POST[$key]);
}

$startmonth = is_blank($_POST['startmonth']) ? date('m') : $_POST['startmonth'];
$startyear = is_blank($_POST['startyear']) ? date('Y') : $_POST['startyear'];

if (is_blank($_POST['startday'])) {
	$startday = '01';
} elseif (isset($_POST['startday']) && ($_POST['startday'] > date('t', strtotime("$startyear-$startmonth")))) {
	$startday = $_POST['startday'] = date('t', strtotime("$startyear-$startmonth"));
} else {
	$startday = sprintf('%02d',$_POST['startday']);
}
$starthour = is_blank($_POST['starthour']) ? '00' : sprintf('%02d',$_POST['starthour']);
$startmin = is_blank($_POST['startmin']) ? '00' : sprintf('%02d',$_POST['startmin']);

$startdate = "'$startyear-$startmonth-$startday $starthour:$startmin:00'";
$start_timestamp = mktime( $starthour, $startmin, 59, $startmonth, $startday, $startyear );

$endmonth = is_blank($_POST['endmonth']) ? date('m') : $_POST['endmonth'];  
$endyear = is_blank($_POST['endyear']) ? date('Y') : $_POST['endyear'];  

if (is_blank($_POST['endday']) || (isset($_POST['endday']) && ($_POST['endday'] > date('t', strtotime("$endyear-$endmonth-01"))))) {
	$endday = $_POST['endday'] = date('t', strtotime("$endyear-$endmonth"));
} else {
	$endday = sprintf('%02d',$_POST['endday']);
}
$endhour = is_blank($_POST['endhour']) ? '23' : sprintf('%02d',$_POST['endhour']);
$endmin = is_blank($_POST['endmin']) ? '59' : sprintf('%02d',$_POST['endmin']);

$enddate = "'$endyear-$endmonth-$endday $endhour:$endmin:59'";
$end_timestamp = mktime( $endhour, $endmin, 59, $endmonth, $endday, $endyear );

#
# asterisk regexp2sqllike
#
if ( is_blank($_POST['src']) ) {
	$src_number = NULL;
} else {
	$src_number = asteriskregexp2sqllike( 'src', '' );
}

if ( is_blank($_POST['dst']) ) {
	$dst_number = NULL;
} else {
	$dst_number = asteriskregexp2sqllike( 'dst', '' );
}

$date_range = "calldate BETWEEN $startdate AND $enddate";
$mod_vars['channel'][] = is_blank($_POST['channel']) ? NULL : $_POST['channel'];
$mod_vars['channel'][] = empty($_POST['channel_mod']) ? NULL : $_POST['channel_mod'];
$mod_vars['channel'][] = empty($_POST['channel_neg']) ? NULL : $_POST['channel_neg'];
$mod_vars['src'][] = $src_number;
$mod_vars['src'][] = empty($_POST['src_mod']) ? NULL : $_POST['src_mod'];
$mod_vars['src'][] = empty($_POST['src_neg']) ? NULL : $_POST['src_neg'];
$mod_vars['clid'][] = is_blank($_POST['clid']) ? NULL : $_POST['clid'];
$mod_vars['clid'][] = empty($_POST['clid_mod']) ? NULL : $_POST['clid_mod'];
$mod_vars['clid'][] = empty($_POST['clid_neg']) ? NULL : $_POST['clid_neg'];
$mod_vars['dstchannel'][] = is_blank($_POST['dstchannel']) ? NULL : $_POST['dstchannel'];
$mod_vars['dstchannel'][] = empty($_POST['dstchannel_mod']) ? NULL : $_POST['dstchannel_mod'];
$mod_vars['dstchannel'][] = empty($_POST['dstchannel_neg']) ? NULL : $_POST['dstchannel_neg'];
$mod_vars['dst'][] = $dst_number;
$mod_vars['dst'][] = empty($_POST['dst_mod']) ? NULL : $_POST['dst_mod'];
$mod_vars['dst'][] = empty($_POST['dst_neg']) ? NULL : $_POST['dst_neg'];
$mod_vars['userfield'][] = is_blank($_POST['userfield']) ? NULL : $_POST['userfield'];
$mod_vars['userfield'][] = empty($_POST['userfield_mod']) ? NULL : $_POST['userfield_mod'];
$mod_vars['userfield'][] = empty($_POST['userfield_neg']) ? NULL : $_POST['userfield_neg'];
$mod_vars['accountcode'][] = is_blank($_POST['accountcode']) ? NULL : $_POST['accountcode'];
$mod_vars['accountcode'][] = empty($_POST['accountcode_mod']) ? NULL : $_POST['accountcode_mod'];
$mod_vars['accountcode'][] = empty($_POST['accountcode_neg']) ? NULL : $_POST['accountcode_neg'];
$result_limit = is_blank($_POST['limit']) ? $db_result_limit : $_POST['limit'];

if ( strlen($cdr_user_name) > 0 ) {
	$cdr_user_name = asteriskregexp2sqllike( 'cdr_user_name', mysql_real_escape_string($cdr_user_name) );
	if ( isset($mod_vars['cdr_user_name']) and $mod_vars['cdr_user_name'][2] == 'asterisk-regexp' ) {
		$cdr_user_name = " AND ( dst RLIKE '$cdr_user_name' or src RLIKE '$cdr_user_name' )";
	} else {
		$cdr_user_name = " AND ( dst = '$cdr_user_name' or src = '$cdr_user_name' )";
	}
}

$search_condition = '';

foreach ($mod_vars as $key => $val) {
	if (is_blank($val[0])) {
		unset($_POST[$key.'_mod']);
		$$key = NULL;
	} else {
		$pre_like = '';
		if ( $val[2] == 'true' ) {
			$pre_like = ' NOT ';
		}
		switch ($val[1]) {
			case "contains":
				$$key = "$search_condition $key $pre_like LIKE '%$val[0]%'";
			break;
			case "ends_with":
				$$key = "$search_condition $key $pre_like LIKE '%$val[0]'";
			break;
			case "exact":
				if ( $val[2] == 'true' ) {
					$$key = "$search_condition $key != '$val[0]'";
				} else {
					$$key = "$search_condition $key = '$val[0]'";
				}
			break;
			case "asterisk-regexp":
				$ast_dids = preg_split('/\s*,\s*/', $val[0], -1, PREG_SPLIT_NO_EMPTY);
				$ast_key = '';
				foreach ($ast_dids as $did) {
					if (strlen($ast_key) > 0 ) {
						if ( $pre_like == ' NOT ' ) {
							$ast_key .= " and ";
						} else {
							$ast_key .= " or ";
						}
						if ( '_' == substr($did,0,1) ) {
							$did = substr($did,1);
						}
					}
					$ast_key .= " $key $pre_like RLIKE '^$did\$'";
				}
				$$key = "$search_condition ( $ast_key )";
			break;
			case "begins_with":
			default:
				$$key = "$search_condition $key $pre_like LIKE '$val[0]%'";
		}
		if ( $search_condition == '' ) {
			if ( isset($_POST['search_mode']) && $_POST['search_mode'] == 'any' ) {
				$search_condition = ' OR ';
			} else {
				$search_condition = ' AND ';
			}
		}
	}
}

if ( isset($_POST['disposition_neg']) && $_POST['disposition_neg'] == 'true' ) {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "$search_condition disposition != '$_POST[disposition]'";
} else {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "$search_condition disposition = '$_POST[disposition]'";
}

$duration = (!isset($_POST['dur_min']) || is_blank($_POST['dur_max'])) ? NULL : "$search_condition duration BETWEEN '$_POST[dur_min]' AND '$_POST[dur_max]'";
$order = empty($_POST['order']) ? 'ORDER BY calldate' : "ORDER BY $_POST[order]";
$sort = empty($_POST['sort']) ? 'DESC' : $_POST['sort'];
$group = empty($_POST['group']) ? 'day' : $_POST['group'];

// Build the "WHERE" part of the query
$where = "$channel $dstchannel $src $clid $dst $userfield $accountcode $disposition $duration $cdr_user_name";
if ( strlen($where) > 9 ) {
	$where = "WHERE $date_range AND ( $where )";
} else {
	$where = "WHERE $date_range";
}

if ( isset($_POST['need_csv']) && $_POST['need_csv'] == 'true' ) {
	$csv_file = md5(time() .'-'. $where ).'.csv';
	if (! file_exists("$system_tmp_dir/$csv_file")) {
		$query = "(SELECT 'calldate', 'clid', 'src', 'dst','dcontext', 'channel', 'dstchannel', 'lastapp', 'lastdata', 'duration', 'billsec', 'disposition', 'amaflags', 'accountcode', 'uniqueid', 'userfield') union (SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield into outfile '$system_tmp_dir/$csv_file' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit)";
		$result = mysql_query($query) or die("Query failed: [$query] " . (mysql_error()));
	}
	echo "<p class='right title'><a href='download.php?csv=$csv_file'>Click here to download CSV file</a></p>";
}

if ( isset($_POST['need_html']) && $_POST['need_html'] == 'true' ) {
	$query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield, unix_timestamp(calldate) as call_timestamp FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit";
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
		formatFiles($row);
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
// ConcurrentCalls
$group_by_field_php = array( '', 32, '' );

switch ($group) {
	case "disposition_by_day":
		$graph_col_title = 'Disposition by day';
		$group_by_field_php = array('%Y-%m-%d / ',17,'');
		$group_by_field = "CONCAT(DATE_FORMAT(calldate, '$group_by_field_php[0]'),disposition)";
	break;
	case "disposition_by_hour":
		$graph_col_title = 'Disposition by hour';
		$group_by_field_php = array( '%Y-%m-%d %H / ', 20, '' );
		$group_by_field = "CONCAT(DATE_FORMAT(calldate, '$group_by_field_php[0]'),disposition)";
	break;
	case "disposition":
		$graph_col_title = 'Disposition';
	break;
	case "dcontext":
		$graph_col_title = 'Destination context';
	break;
	case "accountcode":
		$graph_col_title = 'Account Code';
	break;
	case "dst":
		$graph_col_title = 'Destination Number';
	break;
	case "src":
		$graph_col_title = 'Source Number';
	break;
	case "userfield":
		$graph_col_title = 'User Field';
	break;
	case "hour":
		$group_by_field_php = array( '%Y-%m-%d %H', 13, '' );
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = 'Hour';
	break;
	case "hour_of_day":
		$group_by_field_php = array('%H',2,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = 'Hour of day';
	break;
	case "week":
		$group_by_field_php = array('%V',2,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]') ";
		$graph_col_title = 'Week ( Sun-Sat )';
	break;
	case "month":
		$group_by_field_php = array('%Y-%m',7,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = 'Month';
	break;
	case "day_of_week":
		$group_by_field_php = array('%w - %A',20,'');
		$group_by_field = "DATE_FORMAT( calldate, '%w - %W' )";
		$graph_col_title = 'Day of week';
	break;
	case "minutes1":
		$group_by_field_php = array( '%Y-%m-%d %H:%M', 16, '' );
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m-%d %H:%i')";
		$graph_col_title = 'Minute';
	break;
	case "minutes10":
		$group_by_field_php = array('%Y-%m-%d %H:%M',15,'0');
		$group_by_field = "CONCAT(SUBSTR(DATE_FORMAT(calldate, '%Y-%m-%d %H:%i'),1,15), '0')";
		$graph_col_title = '10 Minutes';
	break;
	case "day":
	default:
		$group_by_field_php = array('%Y-%m-%d',10,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = 'Day';
}

if ( isset($_POST['need_chart']) && $_POST['need_chart'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";
	$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());

	$tot_calls = 0;
	$tot_duration = 0;
	$max_calls = 0;
	$max_duration = 0;
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
		echo '<p class="center title">Call Detail Record - Call Graph by '.$graph_col_title.'</p><table class="cdr">
		<tr>
			<th class="end_col">'. $graph_col_title . '</th>
			<th class="center_col">Total Calls: '. $tot_calls .' / Max Calls: '. $max_calls .' / Total Duration: '. $tot_duration .'</th>
			<th class="end_col">Average Call Time</th>
			<th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="/icons/small/back.png" alt="CDR Table" /></a></th>
			<th class="img_col"><a href="#Graph" title="Go to the CDR Graph"><img src="/icons/small/image2.png" alt="CDR Graph" /></a></th>
		</tr>';
	
		foreach ($result_array as $row) {
			$avg_call_time = sprintf('%02d', intval(($row[2]/$row[1])/60)).':'.sprintf('%02d', intval($row[2]/$row[1]%60));
			$bar_calls = $row[1]/$max_calls*100;
			$percent_tot_calls = intval($row[1]/$tot_calls*100);
			$bar_duration = $row[2]/$max_duration*100;
			$percent_tot_duration = intval($row[2]/$tot_duration_secs*100);
			$html_duration = sprintf('%02d', intval($row[2]/60)).':'.sprintf('%02d', intval($row[2]%60));
			echo "  <tr>\n";
			echo "    <td class=\"end_col\">$row[0]</td><td class=\"center_col\"><div class=\"bar_calls\" style=\"width : $bar_calls%\">$row[1] - $percent_tot_calls%</div><div class=\"bar_duration\" style=\"width : $bar_duration%\">$html_duration - $percent_tot_duration%</div></td><td class=\"chart_data\">$avg_call_time</td>\n";
			echo "    <td></td>\n";
			echo "    <td></td>\n";
			echo "  </tr>\n";
		}
		echo "</table>";
	}
	mysql_free_result($result2);
}
if ( isset($_POST['need_chart_cc']) && $_POST['need_chart_cc'] == 'true' ) {
	$date_range = "( (calldate BETWEEN $startdate AND $enddate) or (calldate + interval duration second  BETWEEN $startdate AND $enddate) or ( calldate + interval duration second >= $enddate AND calldate <= $startdate ) )";
	$where = "$channel $dstchannel $src $clid $dst $userfield $accountcode $disposition $duration $cdr_user_name";
	if ( strlen($where) > 9 ) {
		$where = "WHERE $date_range AND ( $where )";
	} else {
		$where = "WHERE $date_range";
	}
	
	$tot_calls = 0;
	$max_calls = 0;
	$result_array_cc = array();
	$result_array = array();

	if ( strpos($group_by_field,'DATE_FORMAT') === false ) {
		/* not date time fields */
		$query3 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, unix_timestamp(calldate) AS ts, duration FROM $db_name.$db_table_name $where GROUP BY group_by_field, unix_timestamp(calldate) ORDER BY group_by_field ASC LIMIT $result_limit";

		$result3 = mysql_query($query3) or die("Query failed[ $query3 ]: " . mysql_error());
		$group_by_str = '';
		while ($row = mysql_fetch_array($result3, MYSQL_NUM)) {
			if ( $group_by_str != $row[0] ) {
				$group_by_str = $row[0];
				$result_array = array();
			}
			for ( $i=$row[2]; $i<=$row[2]+$row[3]; ++$i ) {
				if ( isset($result_array[ "$i" ]) ) {
					$result_array[ "$i" ] += $row[1];
				} else {
					$result_array[ "$i" ] = $row[1];
				}
				if ( $max_calls < $result_array[ "$i" ] ) {
					$max_calls = $result_array[ "$i" ];
				}
				if ( ! isset($result_array_cc[ $row[0] ]) || $result_array_cc[ $row[0] ][1] < $result_array[ "$i" ] ) {
					$result_array_cc[ "$row[0]" ][0] = $i;
					$result_array_cc[ "$row[0]" ][1] = $result_array[ "$i" ];
				}
			}
			$tot_calls += $row[1];
		}
	} else {
		/* data fields */
		$query3 = "SELECT unix_timestamp(calldate) AS ts, duration FROM $db_name.$db_table_name $where ORDER BY unix_timestamp(calldate) ASC LIMIT $result_limit";
		$result3 = mysql_query($query3) or die("Query failed[ $query3 ]: " . mysql_error());
		$group_by_str = '';
		while ($row = mysql_fetch_array($result3, MYSQL_NUM)) {
			$group_by_str_cur = substr(strftime($group_by_field_php[0],$row[0]),0,$group_by_field_php[1]) . $group_by_field_php[2];
			if ( $group_by_str_cur != $group_by_str ) {
				if ( $group_by_str ) {
					for ( $i=$start_timestamp; $i<$row[0]; ++$i ) {
						if ( ! isset($result_array_cc[ "$group_by_str" ]) || ( isset($result_array["$i"]) && $result_array_cc[ "$group_by_str" ][1] < $result_array["$i"] ) ) {
							$result_array_cc[ "$group_by_str" ][0] = $i;
							$result_array_cc[ "$group_by_str" ][1] = isset($result_array["$i"]) ? $result_array["$i"] : 0;
						}
						unset( $result_array[$i] );
					}
					$start_timestamp = $row[0];
				}
				$group_by_str = $group_by_str_cur;
			}
			for ( $i=$row[0]; $i<=$row[0]+$row[1]; ++$i ) {
				if ( isset($result_array["$i"]) ) {
					++$result_array["$i"];
				} else {
					$result_array["$i"]=1;
				}
				if ( $max_calls < $result_array["$i"] ) {
					$max_calls = $result_array["$i"];
				}
			}
			$tot_calls++;
		}
		for ( $i=$start_timestamp; $i<=$end_timestamp; ++$i ) {
			$group_by_str = substr(strftime($group_by_field_php[0],$i),0,$group_by_field_php[1]) . $group_by_field_php[2];
			if ( ! isset($result_array_cc[ "$group_by_str" ]) || ( isset($result_array["$i"]) && $result_array_cc[ "$group_by_str" ][1] < $result_array["$i"] ) ) {
				$result_array_cc[ "$group_by_str" ][0] = $i;
				$result_array_cc[ "$group_by_str" ][1] = isset($result_array["$i"]) ? $result_array["$i"] : 0;
			}
		}
	}
	if ( $tot_calls ) {
		echo '<p class="center title">Call Detail Record - Concurrent Calls by '.$graph_col_title.'</p><table class="cdr">
		<tr>
			<th class="end_col">'. $graph_col_title . '</th>
			<th class="center_col">Total Calls: '. $tot_calls .' / Max Calls: '. $max_calls .'</th>
			<th class="end_col">Time</th>
		</tr>';
	
		ksort($result_array_cc);

		foreach ( array_keys($result_array_cc) as $group_by_key ) {
			$full_time = strftime( '%Y-%m-%d %H:%M:%S', $result_array_cc[ "$group_by_key" ][0] );
			$group_by_cur = $result_array_cc[ "$group_by_key" ][1];
			$bar_calls = $group_by_cur/$max_calls*100;
			echo "  <tr>\n";
			echo "    <td class=\"end_col\">$group_by_key</td><td class=\"center_col\"><div class=\"bar_calls\" style=\"width : $bar_calls%\">&nbsp;$group_by_cur</div></td><td>$full_time</td>\n";
			echo "  </tr>\n";
		}

		echo "</table>";
	}
	mysql_free_result($result3);
}
if ( isset($_POST['need_minutes_report']) && $_POST['need_minutes_report'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration), sum(billsec) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";
	$result2 = mysql_query($query2) or die('Query failed: ' . mysql_error());

	$tot_calls = 0;
	$tot_duration = 0;

	echo '<p class="center title">Call Detail Record - Minutes report by '.$graph_col_title.'</p><table class="cdr">
		<tr>
			<th class="end_col">'. $graph_col_title . '</th>
			<th class="end_col">Call counts</th>
			<th class="end_col">Billable Sec</th>
			<th class="end_col">Billable Minutes</th>
			<th class="end_col">AVG Minutes</th>
		</tr>';

	while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
			
			$html_duration = sprintf('%02d', intval($row[3]/60)).':'.sprintf('%02d', intval($row[3]%60));
			$html_duration_avg	= sprintf('%02d', intval(($row[3]/$row[1])/60)).':'.sprintf('%02d', intval(($row[3]/$row[1])%60));

			echo "  <tr>\n";
			echo "    <td class=\"end_col\">$row[0]</td><td class=\"chart_data\">$row[1]</td><td class=\"chart_data\">$row[3]</td><td class=\"chart_data\">$html_duration</td><td class=\"chart_data\">$html_duration_avg</td>\n";
			echo "  </tr>\n";
			
			$tot_duration += $row[3];
			$tot_calls += $row[1];
	}
	
	$html_duration = sprintf('%02d', intval($tot_duration/60)).':'.sprintf('%02d', intval($tot_duration%60));
	$html_duration_avg = sprintf('%02d', intval(($tot_duration/$tot_calls)/60)).':'.sprintf('%02d', intval(($tot_duration/$tot_calls)%60));

	echo "  <tr>\n";
	echo "    <th class=\"chart_data\">Total</th><th class=\"chart_data\">$tot_calls</th><th class=\"chart_data\">$tot_duration</th><th class=\"chart_data\">$html_duration</th><th class=\"chart_data\">$html_duration_avg</th>\n";
	echo "  </tr>\n";
	echo "</table>";
	mysql_free_result($result2);
}
?>

</div>

<?php

mysql_close($dbconn);

include 'templates/footer.tpl.php';

?>
