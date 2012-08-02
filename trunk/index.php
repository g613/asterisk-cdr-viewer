<?php

require_once 'include/config.inc.php';
require_once 'include/functions.inc.php';

include 'templates/header.tpl.php';
include 'templates/form.tpl.php';

try {
	$dbh = new PDO("$db_type:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
}
catch (PDOException $e) {
	echo "\nPDO::errorInfo():\n";
	print $e->getMessage();
}

// Connecting, selecting database
foreach ( array_keys($_REQUEST) as $key ) {
	$_REQUEST[$key] = preg_replace('/;/', ' ', $_REQUEST[$key]);
	$_REQUEST[$key] = substr($dbh->quote($_REQUEST[$key]),1,-1);
}

$startmonth = is_blank($_REQUEST['startmonth']) ? date('m') : $_REQUEST['startmonth'];
$startyear = is_blank($_REQUEST['startyear']) ? date('Y') : $_REQUEST['startyear'];

if (is_blank($_REQUEST['startday'])) {
	$startday = '01';
} elseif (isset($_REQUEST['startday']) && ($_REQUEST['startday'] > date('t', strtotime("$startyear-$startmonth")))) {
	$startday = $_REQUEST['startday'] = date('t', strtotime("$startyear-$startmonth"));
} else {
	$startday = sprintf('%02d',$_REQUEST['startday']);
}
$starthour = is_blank($_REQUEST['starthour']) ? '00' : sprintf('%02d',$_REQUEST['starthour']);
$startmin = is_blank($_REQUEST['startmin']) ? '00' : sprintf('%02d',$_REQUEST['startmin']);

$startdate = "'$startyear-$startmonth-$startday $starthour:$startmin:00'";
$start_timestamp = mktime( $starthour, $startmin, 59, $startmonth, $startday, $startyear );

$endmonth = is_blank($_REQUEST['endmonth']) ? date('m') : $_REQUEST['endmonth'];  
$endyear = is_blank($_REQUEST['endyear']) ? date('Y') : $_REQUEST['endyear'];  

if (is_blank($_REQUEST['endday']) || (isset($_REQUEST['endday']) && ($_REQUEST['endday'] > date('t', strtotime("$endyear-$endmonth-01"))))) {
	$endday = $_REQUEST['endday'] = date('t', strtotime("$endyear-$endmonth"));
} else {
	$endday = sprintf('%02d',$_REQUEST['endday']);
}
$endhour = is_blank($_REQUEST['endhour']) ? '23' : sprintf('%02d',$_REQUEST['endhour']);
$endmin = is_blank($_REQUEST['endmin']) ? '59' : sprintf('%02d',$_REQUEST['endmin']);

$enddate = "'$endyear-$endmonth-$endday $endhour:$endmin:59'";
$end_timestamp = mktime( $endhour, $endmin, 59, $endmonth, $endday, $endyear );

#
# asterisk regexp2sqllike
#
if ( is_blank($_REQUEST['src']) ) {
	$src_number = NULL;
} else {
	$src_number = asteriskregexp2sqllike( 'src', '' );
}

if ( is_blank($_REQUEST['dst']) ) {
	$dst_number = NULL;
} else {
	$dst_number = asteriskregexp2sqllike( 'dst', '' );
}

$date_range = "calldate BETWEEN $startdate AND $enddate";
$mod_vars['channel'][] = is_blank($_REQUEST['channel']) ? NULL : $_REQUEST['channel'];
$mod_vars['channel'][] = empty($_REQUEST['channel_mod']) ? NULL : $_REQUEST['channel_mod'];
$mod_vars['channel'][] = empty($_REQUEST['channel_neg']) ? NULL : $_REQUEST['channel_neg'];
$mod_vars['src'][] = $src_number;
$mod_vars['src'][] = empty($_REQUEST['src_mod']) ? NULL : $_REQUEST['src_mod'];
$mod_vars['src'][] = empty($_REQUEST['src_neg']) ? NULL : $_REQUEST['src_neg'];
$mod_vars['clid'][] = is_blank($_REQUEST['clid']) ? NULL : $_REQUEST['clid'];
$mod_vars['clid'][] = empty($_REQUEST['clid_mod']) ? NULL : $_REQUEST['clid_mod'];
$mod_vars['clid'][] = empty($_REQUEST['clid_neg']) ? NULL : $_REQUEST['clid_neg'];
$mod_vars['dstchannel'][] = is_blank($_REQUEST['dstchannel']) ? NULL : $_REQUEST['dstchannel'];
$mod_vars['dstchannel'][] = empty($_REQUEST['dstchannel_mod']) ? NULL : $_REQUEST['dstchannel_mod'];
$mod_vars['dstchannel'][] = empty($_REQUEST['dstchannel_neg']) ? NULL : $_REQUEST['dstchannel_neg'];
$mod_vars['dst'][] = $dst_number;
$mod_vars['dst'][] = empty($_REQUEST['dst_mod']) ? NULL : $_REQUEST['dst_mod'];
$mod_vars['dst'][] = empty($_REQUEST['dst_neg']) ? NULL : $_REQUEST['dst_neg'];
$mod_vars['userfield'][] = is_blank($_REQUEST['userfield']) ? NULL : $_REQUEST['userfield'];
$mod_vars['userfield'][] = empty($_REQUEST['userfield_mod']) ? NULL : $_REQUEST['userfield_mod'];
$mod_vars['userfield'][] = empty($_REQUEST['userfield_neg']) ? NULL : $_REQUEST['userfield_neg'];
$mod_vars['accountcode'][] = is_blank($_REQUEST['accountcode']) ? NULL : $_REQUEST['accountcode'];
$mod_vars['accountcode'][] = empty($_REQUEST['accountcode_mod']) ? NULL : $_REQUEST['accountcode_mod'];
$mod_vars['accountcode'][] = empty($_REQUEST['accountcode_neg']) ? NULL : $_REQUEST['accountcode_neg'];
$result_limit = is_blank($_REQUEST['limit']) ? $db_result_limit : intval($_REQUEST['limit']);

if ( strlen($cdr_user_name) > 0 ) {
	$cdr_user_name = asteriskregexp2sqllike( 'cdr_user_name', substr($dbh->quote($cdr_user_name),1,-1) );
	if ( isset($mod_vars['cdr_user_name']) and $mod_vars['cdr_user_name'][2] == 'asterisk-regexp' ) {
		$cdr_user_name = " AND ( dst RLIKE '$cdr_user_name' or src RLIKE '$cdr_user_name' )";
	} else {
		$cdr_user_name = " AND ( dst = '$cdr_user_name' or src = '$cdr_user_name' )";
	}
}

$search_condition = '';

// Build the "WHERE" part of the query

foreach ($mod_vars as $key => $val) {
	if (is_blank($val[0])) {
		unset($_REQUEST[$key.'_mod']);
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
			if ( isset($_REQUEST['search_mode']) && $_REQUEST['search_mode'] == 'any' ) {
				$search_condition = ' OR ';
			} else {
				$search_condition = ' AND ';
			}
		}
	}
}

if ( isset($_REQUEST['disposition_neg']) && $_REQUEST['disposition_neg'] == 'true' ) {
	$disposition = (empty($_REQUEST['disposition']) || $_REQUEST['disposition'] == 'all') ? NULL : "$search_condition disposition != '$_REQUEST[disposition]'";
} else {
	$disposition = (empty($_REQUEST['disposition']) || $_REQUEST['disposition'] == 'all') ? NULL : "$search_condition disposition = '$_REQUEST[disposition]'";
}

if ( $search_condition == '' ) {
	if ( isset($_REQUEST['search_mode']) && $_REQUEST['search_mode'] == 'any' ) {
		$search_condition = ' OR ';
	} else {
		$search_condition = ' AND ';
	}
}

$where = "$channel $src $clid $dstchannel $dst $userfield $accountcode $disposition";

$duration = (!isset($_REQUEST['dur_min']) || is_blank($_REQUEST['dur_max'])) ? NULL : "duration BETWEEN '$_REQUEST[dur_min]' AND '$_REQUEST[dur_max]'";

if ( strlen($duration) > 0 ) {
	if ( strlen($where) > 7 ) {
		$where = "$where $search_condition $duration";
	} else {
		$where = "$where $duration";
	}
}

if ( strlen($where) > 8 ) {
	$where = "WHERE $date_range AND ( $where ) $cdr_user_name";
} else {
	$where = "WHERE $date_range $cdr_user_name";
}

$order = empty($_REQUEST['order']) ? 'ORDER BY calldate' : "ORDER BY $_REQUEST[order]";
$sort = empty($_REQUEST['sort']) ? 'DESC' : $_REQUEST['sort'];
$group = empty($_REQUEST['group']) ? 'day' : $_REQUEST['group'];

if ( isset($_REQUEST['need_csv']) && $_REQUEST['need_csv'] == 'true' ) {
	$csv_file = md5(time() .'-'. $where ).'.csv';
	if (! file_exists("$system_tmp_dir/$csv_file")) {
		$handle = fopen("$system_tmp_dir/$csv_file", "w");
		$query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit";
		try {
			$sth = $dbh->query($query);
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}
		if ( isset($_REQUEST['use_callrates']) && $_REQUEST['use_callrates'] == 'true' ) {
			fwrite($handle,"calldate,clid,src,dst,dcontext,channel,dstchannel,lastapp,lastdata,duration,billsec,disposition,amaflags,accountcode,uniqueid,userfield,callrate,callrate_dst\n");
		} else {
			fwrite($handle,"calldate,clid,src,dst,dcontext,channel,dstchannel,lastapp,lastdata,duration,billsec,disposition,amaflags,accountcode,uniqueid,userfield\n");
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$csv_line[0] 	= $row['calldate'];
			$csv_line[1] 	= $row['clid'];
			$csv_line[2] 	= $row['src'];
			$csv_line[3] 	= $row['dst'];
			$csv_line[4] 	= $row['dcontext'];
			$csv_line[5]	= $row['channel'];
			$csv_line[6] 	= $row['dstchannel'];
			$csv_line[7] 	= $row['lastapp'];
			$csv_line[8]	= $row['lastdata'];
			$csv_line[9]	= $row['duration'];
			$csv_line[10]	= $row['billsec'];
			$csv_line[11]	= $row['disposition'];
			$csv_line[12]	= $row['amaflags'];
			$csv_line[13]	= $row['accountcode'];
			$csv_line[14]	= $row['uniqueid'];
			$csv_line[15]	= $row['userfield'];
			$data = '';
			if ( isset($_REQUEST['use_callrates']) && $_REQUEST['use_callrates'] == 'true' ) {
				$rates = callrates($row['dst'],$row['billsec'],$callrate_csv_file);
				$csv_line[16] = $rates[4];
				$csv_line[17] = $rates[2];
			}
			for ($i = 0; $i < count($csv_line); $i++) {
				$csv_line[$i] = str_replace( array( "\n", "\r" ), '', $csv_line[$i]);
				/* If the string contains a comma, enclose it in double-quotes. */
				if (strpos($csv_line[$i], ",") !== FALSE) {
					$csv_line[$i] = "\"" . $csv_line[$i] . "\"";
				}
				if ($i != count($csv_line) - 1) {
					$data = $data . $csv_line[$i] . ",";
				} else {
					$data = $data . $csv_line[$i];
				}
			}
			unset($csv_line);
			fwrite($handle,"$data\n");
		}
		fclose($handle);
		$sth = NULL;
	}
	echo "<p class='right title'><a href='download.php?csv=$csv_file'>Click here to download CSV file</a></p>";
}

if ( isset($_REQUEST['need_html']) && $_REQUEST['need_html'] == 'true' ) {
	$query = "SELECT count(*) FROM $db_name.$db_table_name $where LIMIT $result_limit";
	try {
		$sth = $dbh->query($query);
	}
	catch (PDOException $e) {
		print $e->getMessage();
	}
	if (!$sth) {
		echo "\nPDO::errorInfo():\n";
		print_r($dbh->errorInfo());
	} else {
		$tot_calls_raw = $sth->fetchColumn();
		$sth = NULL;
	}

	if ( $tot_calls_raw ) {

		if ( $tot_calls_raw > $result_limit ) {
			echo '<p class="center title">Call Detail Record - Search Returned '. $result_limit .' of '. $tot_calls_raw .' Calls </p><table class="cdr">';
		} else {
			echo '<p class="center title">Call Detail Record - Search Returned '. $tot_calls_raw .' Calls </p><table class="cdr">';
		}

		$i = $h_step - 1;

		try {
		
		$query = "SELECT calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield, unix_timestamp(calldate) as call_timestamp FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit";
		$sth = $dbh->query($query);
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			++$i;
			if ($i == $h_step) {
			?>
				<tr>
				<th class="record_col">Call Date</th>
				<th class="record_col">File</th>
				<th class="record_col">Src Channel</th>
				<?php
					if ( isset($display_column['clid']) and $display_column['clid'] == 1 ) {
						echo '<th class="record_col">CallerID</th>';
					}
				?>
				<th class="record_col">Source</th>
				<th class="record_col">Application</th>
				<th class="record_col">Destination</th>
				<th class="record_col">Dst Channel</th>
				<th class="record_col">Disposition</th>
				<th class="record_col">Duration</th>
				<th class="record_col">Userfield</th>
				<?php
					if ( isset($display_column['accountcode']) and $display_column['accountcode'] == 1 ) {
						echo '<th class="record_col">Account</th>';
					}
				?>
				<?php
				if ( isset($_REQUEST['use_callrates']) && $_REQUEST['use_callrates'] == 'true' ) {
					echo '<th class="record_col">CallRate</th><th class="record_col">CallRate Dst</th>';
				}
				?>
				<th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="/icons/small/back.png" alt="CDR Table" /></a></th>
				<th class="img_col"><a href="#Graph" title="Go to the top of the CDR graph"><img src="/icons/small/image2.png" alt="CDR Graph" /></a></th>
				</tr>
				<?php
				$i = 0;
			}
			echo "  <tr class=\"record\">\n";
			formatCallDate($row['calldate'],$row['uniqueid']);
			formatFiles($row);
			formatChannel($row['channel']);
			if ( isset($display_column['clid']) and $display_column['clid'] == 1 ) {
				formatClid($row['clid']);
			}
			formatSrc($row['src'],$row['clid']);
			formatApp($row['lastapp'], $row['lastdata']);
			formatDst($row['dst'], $row['dcontext']);
			formatChannel($row['dstchannel']);
			formatDisposition($row['disposition'], $row['amaflags']);
			formatDuration($row['duration'], $row['billsec']);
			formatUserField($row['userfield']);
			if ( isset($display_column['accountcode']) and $display_column['accountcode'] == 1 ) {
				formatAccountCode($row['accountcode']);
			}
			if ( isset($_REQUEST['use_callrates']) && $_REQUEST['use_callrates'] == 'true' ) {
				$rates = callrates($row['dst'],$row['billsec'],$callrate_csv_file);
				formatMoney($rates[4]);
				echo "<td>". htmlspecialchars($rates[2]) ."</td>\n";
			}
			echo "    <td></td>\n";
			echo "    <td></td>\n";
			echo "  </tr>\n";
		}
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
		echo "</table>";
		$sth = NULL;
	}
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
	case "clid":
		$graph_col_title = 'Caller*ID';
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

if ( isset($_REQUEST['need_chart']) && $_REQUEST['need_chart'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";

	$tot_calls = 0;
	$tot_duration = 0;
	$max_calls = 0;
	$max_duration = 0;
	$tot_duration_secs = 0;
	$result_array = array();

	try {
		$sth = $dbh->query($query2);
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}
		while ($row = $sth->fetch(PDO::FETCH_NUM)) {
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
	}
	catch (PDOException $e) {
		print $e->getMessage();
	}
	$sth = NULL;
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
}
if ( isset($_REQUEST['need_chart_cc']) && $_REQUEST['need_chart_cc'] == 'true' ) {
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

		try {
			$sth = $dbh->query($query3);
			if (!$sth) {
				echo "\nPDO::errorInfo():\n";
				print_r($dbh->errorInfo());
			}
			$group_by_str = '';
			while ($row = $sth->fetch(PDO::FETCH_NUM)) {
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
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
		$sth = NULL;
	} else {
		/* data fields */
		$query3 = "SELECT unix_timestamp(calldate) AS ts, duration FROM $db_name.$db_table_name $where ORDER BY unix_timestamp(calldate) ASC LIMIT $result_limit";
		$group_by_str = '';
		
		try {
			$sth = $dbh->query($query3);
			if (!$sth) {
				echo "\nPDO::errorInfo():\n";
				print_r($dbh->errorInfo());
			}
			while ($row = $sth->fetch(PDO::FETCH_NUM)) {
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
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
		$sth = NULL;
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
}

if ( isset($_REQUEST['need_minutes_report']) && $_REQUEST['need_minutes_report'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration), sum(billsec) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";

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

	try {
		$sth = $dbh->query($query2);
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}
		while ($row = $sth->fetch(PDO::FETCH_NUM)) {
			$html_duration = sprintf('%02d', intval($row[3]/60)).':'.sprintf('%02d', intval($row[3]%60));
			$html_duration_avg	= sprintf('%02d', intval(($row[3]/$row[1])/60)).':'.sprintf('%02d', intval(($row[3]/$row[1])%60));

			echo "  <tr  class=\"record\">\n";
			echo "    <td class=\"end_col\">$row[0]</td><td class=\"chart_data\">$row[1]</td><td class=\"chart_data\">$row[3]</td><td class=\"chart_data\">$html_duration</td><td class=\"chart_data\">$html_duration_avg</td>\n";
			echo "  </tr>\n";
			
			$tot_duration += $row[3];
			$tot_calls += $row[1];
		}
	}
	catch (PDOException $e) {
		print $e->getMessage();
	}
	$sth = NULL;
	
	$html_duration = sprintf('%02d', intval($tot_duration/60)).':'.sprintf('%02d', intval($tot_duration%60));
	$html_duration_avg = sprintf('%02d', intval(($tot_duration/$tot_calls)/60)).':'.sprintf('%02d', intval(($tot_duration/$tot_calls)%60));

	echo "  <tr>\n";
	echo "    <th class=\"chart_data\">Total</th><th class=\"chart_data\">$tot_calls</th><th class=\"chart_data\">$tot_duration</th><th class=\"chart_data\">$html_duration</th><th class=\"chart_data\">$html_duration_avg</th>\n";
	echo "  </tr>\n";
	echo "</table>";
}

if ( isset($_REQUEST['need_asr_report']) && $_REQUEST['need_asr_report'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, disposition, count(*) AS total_calls, sum(billsec) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field,disposition ORDER BY group_by_field ASC LIMIT $result_limit";

	$tot_calls = 0;
	$tot_duration = 0;

	echo '<p class="center title">Call Detail Record - ASR / ACD report by '.$graph_col_title.'</p><table class="cdr">
		<tr>
			<th class="end_col">'. $graph_col_title . '</th>
			<th class="end_col">ASR</th>
			<th class="end_col">ACD</th>
			<th class="end_col">All calls</th>
			<th class="end_col">Answered calls</th>
			<th class="end_col">Billable Sec</th>
		</tr>';

	$asr_cur_key = '';
	$asr_answered_calls = 0;
	$asr_total_calls = 0;
	$asr_bill_secs = 0;

	$all_asr_answered_calls = 0;
	$all_asr_total_calls = 0;
	$all_asr_bill_secs = 0;
	
	try {
		$sth = $dbh->query($query2);
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}
		while ($row = $sth->fetch(PDO::FETCH_NUM)) {
			if ( $asr_cur_key != '' and $row[0] != $asr_cur_key ) {
				echo "  <tr  class=\"record\">\n";
				echo "    <td class=\"end_col\">$asr_cur_key</td></td><td class=\"chart_data\">",intval(($asr_answered_calls/$asr_total_calls)*100),"</td><td class=\"chart_data\">",intval($asr_bill_secs/($asr_answered_calls?$asr_answered_calls:1)),"<td class=\"chart_data\">$asr_total_calls</td><td class=\"chart_data\">$asr_answered_calls</td><td class=\"chart_data\">$asr_bill_secs</td>\n";
				echo "  </tr>\n";
				$asr_answered_calls = $asr_total_calls = $asr_bill_secs = 0;
			}
			$asr_total_calls += $row[2];
			$asr_bill_secs += $row[3];
			
			$all_asr_total_calls += $row[2];
			$all_asr_bill_secs += $row[3];
			
			if ( $row[1] == 'ANSWERED' ) {
				$asr_answered_calls += $row[2];
				$all_asr_answered_calls += $row[2]; 
			}
			$asr_cur_key = $row[0];
		}
	}
	catch (PDOException $e) {
		print $e->getMessage();
	}
	$sth = NULL;

	if ( $asr_cur_key != '' ) {
		echo "  <tr  class=\"record\">\n";
		echo "    <td class=\"end_col\">$asr_cur_key</td></td><td class=\"chart_data\">",intval(($asr_answered_calls/$asr_total_calls)*100),"</td><td class=\"chart_data\">",intval($asr_bill_secs/($asr_answered_calls?$asr_answered_calls:1)),"<td class=\"chart_data\">$asr_total_calls</td><td class=\"chart_data\">$asr_answered_calls</td><td class=\"chart_data\">$asr_bill_secs</td>","\n";
		echo "  </tr>\n";
	}

	echo "  <tr>\n";
	echo "    <th class=\"chart_data\">Total</th></th><th class=\"chart_data\">",intval(($all_asr_answered_calls/$all_asr_total_calls)*100),"</th><th class=\"chart_data\">",intval($all_asr_bill_secs/($all_asr_answered_calls?$all_asr_answered_calls:1)),"<th class=\"chart_data\">$all_asr_total_calls</th><th class=\"chart_data\">$all_asr_answered_calls</th><th class=\"chart_data\">$all_asr_bill_secs</th>","\n";
	echo "  </tr>\n";
	echo "</table>";

}

/* run Plugins */
foreach ( $plugins as &$p_key ) {
	if ( ! empty($_REQUEST['need_'.$p_key]) && $_REQUEST['need_'.$p_key] == 'true' ) { 
		eval( $p_key . '();' );
	}
}

?>

</div>

<?php

$dbh = NULL;

include 'templates/footer.tpl.php';

?>
