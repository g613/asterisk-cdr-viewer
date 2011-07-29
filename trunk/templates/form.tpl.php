<div id="main">
<table class="cdr">
<tr>
<td>

<form method="post" enctype="application/x-www-form-urlencoded">
<fieldset>
<legend class="title">Call Detail Record Search</legend>
<table width="100%">
<tr>
<th>Order By</th>
<th>Search conditions</th>
<th>&nbsp;</th>
</tr>
<tr>
<td><input <?php if (empty($_POST['order']) || $_POST['order'] == 'calldate') { echo 'checked="checked"'; } ?> type="radio" name="order" value="calldate" />&nbsp;Call Date:</td>
<td>From:
<input type="text" name="startday" id="startday" size="2" maxlength="2" value="<?php if (isset($_POST['startday'])) { echo $_POST['startday']; } else { echo '01'; } ?>" />
<select name="startmonth" id="startmonth">
<?php
$months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');
foreach ($months as $i => $month) {
	if ((empty($_POST['startmonth']) && date('m') == $i) || (isset($_POST['startmonth']) && $_POST['startmonth'] == $i)) {
		echo "        <option value=\"$i\" selected=\"selected\">$month</option>\n";
	} else {
		echo "        <option value=\"$i\">$month</option>\n";
	}
}
?>
</select>
<select name="startyear" id="startyear">
<?php
for ( $i = 2000; $i <= date('Y'); $i++) {
	if ((empty($_POST['startyear']) && date('Y') == $i) || (isset($_POST['startyear']) && $_POST['startyear'] == $i)) {
		echo "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
	} else {
		echo "        <option value=\"$i\">$i</option>\n";
	}
}
?>
</select>
<input type="text" name="starthour" id="starthour" size="2" maxlength="2" value="<?php if (isset($_POST['starthour'])) { echo $_POST['starthour']; } else { echo '00'; } ?>" />
:
<input type="text" name="startmin" id="startmin" size="2" maxlength="2" value="<?php if (isset($_POST['startmin'])) { echo $_POST['startmin']; } else { echo '00'; } ?>" />
To:
<input type="text" name="endday" id="endday" size="2" maxlength="2" value="<?php if (isset($_POST['endday'])) { echo $_POST['endday']; } else { echo '31'; } ?>" />
<select name="endmonth" id="endmonth">
<?php
foreach ($months as $i => $month) {
	if ((empty($_POST['endmonth']) && date('m') == $i) || (isset($_POST['endmonth']) && $_POST['endmonth'] == $i)) {
		echo "        <option value=\"$i\" selected=\"selected\">$month</option>\n";
	} else {
		echo "        <option value=\"$i\">$month</option>\n";
	}
}
?>
</select>
<select name="endyear" id="endyear">
<?php
for ( $i = 2000; $i <= date('Y'); $i++) {
	if ((empty($_POST['endyear']) && date('Y') == $i) || (isset($_POST['endyear']) && $_POST['endyear'] == $i)) {
		echo "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
	} else {
		echo "        <option value=\"$i\">$i</option>\n";
	}
}
?>
</select>
<input type="text" name="endhour" id="endhour" size="2" maxlength="2" value="<?php if (isset($_POST['endhour'])) { echo $_POST['endhour']; } else { echo '23'; } ?>" />
:
<input type="text" name="endmin" id="endmin" size="2" maxlength="2" value="<?php if (isset($_POST['endmin'])) { echo $_POST['endmin']; } else { echo '59'; } ?>" />
</td>
<td rowspan="10" valign='top' align='right'>
<fieldset>
<legend class="title">Extra options</legend>
<table>
<tr>
<td>Report type : </td>
<td>
<input <?php if ( (empty($_POST['need_html']) && empty($_POST['need_chart']) && empty($_POST['need_chart_cc']) && empty($_POST['need_csv'])) || ( ! empty($_POST['need_html']) &&  $_POST['need_html'] == 'true' ) ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_html" value="true" /> : CDR search<br />
<input <?php if ( ! empty($_POST['need_csv']) && $_POST['need_csv'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_csv" value="true" /> : CSV file<br/>
<input <?php if ( ! empty($_POST['need_chart']) && $_POST['need_chart'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_chart" value="true" /> : Call Graph<br />
<input <?php if ( ! empty($_POST['need_chart_cc']) && $_POST['need_chart_cc'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_chart_cc" value="true" /> : Concurrent Calls<br />
</td>
</tr>
<tr>
<td><label for="Result limit">Result limit : </label></td>
<td>
<input value="<?php 
if (isset($_POST['limit']) ) { 
	echo $_POST['limit'];
} else {
	echo $db_result_limit;
} ?>" name="limit" size="6" />
</td>
</tr>
</table>
</fieldset>
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'channel') { echo 'checked="checked"'; } ?> type="radio" name="order" value="channel" />&nbsp;<label for="channel">Src channel:</label></td>
<td><input type="text" name="channel" id="channel" value="<?php if (isset($_POST['channel'])) { echo $_POST['channel']; } ?>" />
Not:<input <?php if ( isset($_POST['channel_neg'] ) && $_POST['channel_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="channel_neg" value="true" />
Begins With:<input <?php if (empty($_POST['channel_mod']) || $_POST['channel_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'src') { echo 'checked="checked"'; } ?> type="radio" name="order" value="src" />&nbsp;<label for="src">Source:</label></td>
<td><input type="text" name="src" id="src" value="<?php if (isset($_POST['src'])) { echo $_POST['src']; } ?>" />
Not:<input <?php if ( isset($_POST['src_neg'] ) && $_POST['src_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="src_neg" value="true" />
Begins With:<input <?php if (empty($_POST['src_mod']) || $_POST['src_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'clid') { echo 'checked="checked"'; } ?> type="radio" name="order" value="clid" />&nbsp;<label for="clid">Caller*ID</label></td>
<td><input type="text" name="clid" id="clid" value="<?php if (isset($_POST['clid'])) { echo $_POST['clid']; } ?>" />
Not:<input <?php if ( isset($_POST['clid_neg'] ) && $_POST['clid_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="clid_neg" value="true" />
Begins With:<input <?php if (empty($_POST['clid_mod']) || $_POST['clid_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'dstchannel') { echo 'checked="checked"'; } ?> type="radio" name="order" value="dstchannel" />&nbsp;<label for="dstchannel">Dst channel:</label></td>
<td><input type="text" name="dstchannel" id="dstchannel" value="<?php if (isset($_POST['dstchannel'])) { echo $_POST['dstchannel']; } ?>" />
Not:<input <?php if ( isset($_POST['dstchannel_neg'] ) && $_POST['dstchannel_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="dstchannel_neg" value="true" />
Begins With:<input <?php if (empty($_POST['dstchannel_mod']) || $_POST['dstchannel_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'dst') { echo 'checked="checked"'; } ?> type="radio" name="order" value="dst" />&nbsp;<label for="dst">Destination:</label></td>
<td><input type="text" name="dst" id="dst" value="<?php if (isset($_POST['dst'])) { echo $_POST['dst']; } ?>" />
Not:<input <?php if ( isset($_POST['dst_neg'] ) &&  $_POST['dst_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="dst_neg" value="true" />
Begins With:<input <?php if (empty($_POST['dst_mod']) || $_POST['dst_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'userfield') { echo 'checked="checked"'; } ?> type="radio" name="order" value="userfield" />&nbsp;<label for="userfield">Userfield:</label></td>
<td><input type="text" name="userfield" id="userfield" value="<?php if (isset($_POST['userfield'])) { echo $_POST['userfield']; } ?>" />
Not:<input <?php if (  isset($_POST['userfield_neg'] ) && $_POST['userfield_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="userfield_neg" value="true" />
Begins With:<input <?php if (empty($_POST['userfield_mod']) || $_POST['userfield_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'accountcode') { echo 'checked="checked"'; } ?> type="radio" name="order" value="accountcode" />&nbsp;<label for="userfield">Account Code:</label></td>
<td><input type="text" name="accountcode" id="accountcode" value="<?php if (isset($_POST['accountcode'])) { echo $_POST['accountcode']; } ?>" />
Not:<input <?php if ( isset($_POST['accountcode_neg'] ) &&  $_POST['accountcode_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="accountcode_neg" value="true" />
Begins With:<input <?php if (empty($_POST['accountcode_mod']) || $_POST['accountcode_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="begins_with" />
Contains:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="contains" />
Ends With:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="ends_with" />
Exactly:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="exact" />
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'duration') { echo 'checked="checked"'; } ?> type="radio" name="order" value="duration" />&nbsp;<label>Duration:</label></td>
<td>Between:
<input type="text" name="dur_min" value="<?php if (isset($_POST['dur_min'])) { echo $_POST['dur_min']; } ?>" size="3" maxlength="5" />
And:
<input type="text" name="dur_max" value="<?php if (isset($_POST['dur_max'])) { echo $_POST['dur_max']; } ?>" size="3" maxlength="5" />
Seconds
</td>
</tr>
<tr>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'disposition') { echo 'checked="checked"'; } ?> type="radio" name="order" value="disposition" />&nbsp;<label for="disposition">Disposition:</label></td>
<td>
Not:<input <?php if ( isset($_POST['dispositio_neg'] ) && $_POST['disposition_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="disposition_neg" value="true" />
<select name="disposition" id="disposition">
<option <?php if (empty($_POST['disposition']) || $_POST['disposition'] == 'all') { echo 'selected="selected"'; } ?> value="all">All Dispositions</option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'ANSWERED') { echo 'selected="selected"'; } ?> value="ANSWERED">Answered</option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'BUSY') { echo 'selected="selected"'; } ?> value="BUSY">Busy</option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'FAILED') { echo 'selected="selected"'; } ?> value="FAILED">Failed</option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'NO ANSWER') { echo 'selected="selected"'; } ?> value="NO ANSWER">No Answer</option>
</select>
</td>
</tr>
<tr>
<td>
<select name="sort" id="sort">
<option <?php if (isset($_POST['sort']) && $_POST['sort'] == 'ASC') { echo 'selected="selected"'; } ?> value="ASC">Ascending</option>
<option <?php if (empty($_POST['sort']) || $_POST['sort'] == 'DESC') { echo 'selected="selected"'; } ?> value="DESC">Descending</option>
</select>
</td>
<td><table width="100%"><tr><td>
<label for="group">Group By:</label>
<select name="group" id="group">
<optgroup label="Account Information">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'accountcode') { echo 'selected="selected"'; } ?> value="accountcode">Account Code</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'userfield') { echo 'selected="selected"'; } ?> value="userfield">User Field</option>
</optgroup>
<optgroup label="Date/Time">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'minutes1') { echo 'selected="selected"'; } ?> value="minutes1">Minute</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'minutes10') { echo 'selected="selected"'; } ?> value="minutes10">10 Minutes</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'hour') { echo 'selected="selected"'; } ?> value="hour">Hour</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'hour_of_day') { echo 'selected="selected"'; } ?> value="hour_of_day">Hour of day</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'day_of_week') { echo 'selected="selected"'; } ?> value="day_of_week">Day of week</option>
<option <?php if (empty($_POST['group']) || $_POST['group'] == 'day') { echo 'selected="selected"'; } ?> value="day">Day</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'week') { echo 'selected="selected"'; } ?> value="week">Week ( Sun-Sat )</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'month') { echo 'selected="selected"'; } ?> value="month">Month</option>
</optgroup>
<optgroup label="Telephone Number">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'src') { echo 'selected="selected"'; } ?> value="src">Source Number</option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'dst') { echo 'selected="selected"'; } ?> value="dst">Destination Number</option>
</optgroup>
</select></td><td align="left" width="40%">
<input type="submit" value="Search" />
</td></td></table>
</td>
</tr>
</table>
</fieldset>
</form>
</td>
</tr>
</table>
<a id="CDR"></a>

