<?php

function au_callrates() {
	global $dbh, $db_name, $db_table_name, $group_by_field, $where, $result_limit, $graph_col_title;

	/**************************** Config ****************************************************/
	$au_call_rates = array(
						"Local / Nat'l" 	=> "(dst LIKE '4%' OR dst LIKE '07%' OR dst LIKE '02%' OR dst LIKE '03%' OR dst LIKE '08%' OR dst LIKE '3%')",
						"Mobile"			=> "(dst LIKE '04%')",
						"one3Call"			=> "(dst LIKE '13%')",
						"Int'l"				=> "(dst LIKE '001%')"
	);

	$au_callrates_csv_file = '/var/www/asterisk-cdr-viewer/include/plugins/au_callrates.csv';

	/****************************************************************************************/
	$au_bill_tototal_q = "SELECT $group_by_field AS group_by_field FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";
	

	$au_callrates_total = array();
	foreach ( array_keys($au_call_rates) as $key ) {
		$au_call_rates_total["$key"] = 0;
	}
	$au_call_rates_total["summ"] = 0;

	echo '<p class="center title">Australia rates ( plugin example )</p><table class="cdr">
		<tr>
			<th>'.$graph_col_title.'</th>
			<th colspan=5>CALLS TO</th>
		</tr>
		<tr><th>&nbsp;</th>';

	foreach ( array_keys($au_call_rates) as $key ) {
		echo "<th>$key</th>";
	}
	
	echo "<th>TOTAL<br/>(inc GST)</th></tr>";

	try {
		$sth = $dbh->query($au_bill_tototal_q);
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
		}

		$result = $sth->fetchAll(PDO::FETCH_NUM);

		$sth = NULL;

		foreach ( $result as $row ) {
			$summ = 0;
			echo "<tr class=\"record\">";
			echo "<td>". $row[0] ."</td>";
			foreach ( array_keys($au_call_rates) as $key ) {
				$au_bill_ch_q = "SELECT dst, billsec FROM $db_name.$db_table_name $where and $group_by_field = '". $row[0] ."' and " . $au_call_rates["$key"];
				$summ_local = 0;
				
				$sth2 = $dbh->query($au_bill_ch_q);
				if (!$sth2) {
					echo "\nPDO::errorInfo():\n";
					print_r($dbh->errorInfo());
				}
				
				while ($bill_row = $sth2->fetch(PDO::FETCH_NUM)) {
					$rates = callrates( $bill_row[0], $bill_row[1], $au_callrates_csv_file );
					$summ_local += $rates[4];
				}
				$sth2 = NULL;

				$au_call_rates_total["$key"] += $summ_local;
				$summ += $summ_local;
				formatMoney($summ_local);
			}
			$au_call_rates_total["summ"] += $summ;
			formatMoney($summ);
			echo "</tr>";

		}
	}
	catch (PDOException $e) {
		print $e->getMessage();
	}

	echo "<tr class=\"chart_data\">";
	echo "<td>Total</td>";
	foreach ( array_keys($au_call_rates_total) as $key ) {
		formatMoney($au_call_rates_total["$key"]);
	}
	echo "</tr>";

	echo "</table>";
}

?>
