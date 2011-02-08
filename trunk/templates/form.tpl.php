<form action="index.php" method="post" enctype="application/x-www-form-urlencoded">
<fieldset>
<legend class="title">Call Detail Record Search</legend>
<table>
  <tr>
    <th>Order By</th>
    <th></th>
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
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'uniqueid') { echo 'checked="checked"'; } ?> type="radio" name="order" value="uniqueid" />&nbsp;<label for="uniqueid">System Name:</label></td>
    <td>
      <select name="uniqueid" id="uniqueid">
        <option <?php if (isset($_POST['uniqueid']) && $_POST['uniqueid'] == 'all') { echo 'selected="selected"'; } ?> value="all">All Systems</option>
<?php
foreach ($system_name_array as $value) {
  if (isset($_POST['uniqueid']) && $_POST['uniqueid'] == "$value") {
    echo "        <option value=\"$value\" selected=\"selected\">$value</option>\n";
  } else {
    echo "        <option value=\"$value\">$value</option>\n";
  }
}
?>
      </select>
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'channel') { echo 'checked="checked"'; } ?> type="radio" name="order" value="channel" />&nbsp;<label for="channel">Channel:</label></td>
    <td><input type="text" name="channel" id="channel" value="<?php if (isset($_POST['channel'])) { echo $_POST['channel']; } ?>" />
      Begins With:<input <?php if (empty($_POST['channel_mod']) || $_POST['channel_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="exact" />
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'src') { echo 'checked="checked"'; } ?> type="radio" name="order" value="src" />&nbsp;<label for="src">Source:</label></td>
    <td><input type="text" name="src" id="src" value="<?php if (isset($_POST['src'])) { echo $_POST['src']; } ?>" />
      Begins With:<input <?php if (empty($_POST['src_mod']) || $_POST['src_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="exact" />
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'clid') { echo 'checked="checked"'; } ?> type="radio" name="order" value="clid" />&nbsp;<label for="clid">Caller*ID</label></td>
    <td><input type="text" name="clid" id="clid" value="<?php if (isset($_POST['clid'])) { echo $_POST['clid']; } ?>" />
      Begins With:<input <?php if (empty($_POST['clid_mod']) || $_POST['clid_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="exact" />
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'dst') { echo 'checked="checked"'; } ?> type="radio" name="order" value="dst" />&nbsp;<label for="dst">Destination:</label></td>
    <td><input type="text" name="dst" id="dst" value="<?php if (isset($_POST['dst'])) { echo $_POST['dst']; } ?>" />
      Begins With:<input <?php if (empty($_POST['dst_mod']) || $_POST['dst_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="exact" />
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'userfield') { echo 'checked="checked"'; } ?> type="radio" name="order" value="userfield" />&nbsp;<label for="userfield">Userfield:</label></td>
    <td><input type="text" name="userfield" id="userfield" value="<?php if (isset($_POST['userfield'])) { echo $_POST['userfield']; } ?>" />
      Begins With:<input <?php if (empty($_POST['userfield_mod']) || $_POST['userfield_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="exact" />
    </td>
  </tr>
  <tr>
    <td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'accountcode') { echo 'checked="checked"'; } ?> type="radio" name="order" value="accountcode" />&nbsp;<label for="userfield">Account Code:</label></td>
    <td><input type="text" name="accountcode" id="accountcode" value="<?php if (isset($_POST['accountcode'])) { echo $_POST['accountcode']; } ?>" />
      Begins With:<input <?php if (empty($_POST['accountcode_mod']) || $_POST['accountcode_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="begins_with" />
      Contains:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="contains" />
      Ends With:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="ends_with" />
      Is Exactly:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="exact" />
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
    <td><label for="group">Group By:</label>
      <select name="group" id="group">
        <optgroup label="Account Information">
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'accountcode') { echo 'selected="selected"'; } ?> value="accountcode">Account Code</option>
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'userfield') { echo 'selected="selected"'; } ?> value="userfield">User Field</option>
        </optgroup>
        <optgroup label="Date/Time">
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'hour') { echo 'selected="selected"'; } ?> value="hour">Hour</option>
          <option <?php if (empty($_POST['group']) || $_POST['group'] == 'day') { echo 'selected="selected"'; } ?> value="day">Day</option>
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'month') { echo 'selected="selected"'; } ?> value="month">Month</option>
        </optgroup>
        <optgroup label="Telephone Number">
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'src') { echo 'selected="selected"'; } ?> value="src">Source Number</option>
          <option <?php if (isset($_POST['group']) && $_POST['group'] == 'dst') { echo 'selected="selected"'; } ?> value="dst">Destination Number</option>
        </optgroup>
      </select>
    </td>
  </tr>
</table>
</fieldset>
<div>
  <input type="hidden" name="posted" value="TRUE" />
  <input type="submit" value="Search" />
</div>
</form>