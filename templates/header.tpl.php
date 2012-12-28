<head>
	<title>Asterisk Call Detail Records</title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
	<link rel="stylesheet" href="style/screen.css" type="text/css" media="screen" />
	<link rel="shortcut icon" href="templates/images/favicon.ico" />
</head>
<body>
	<table id="header">
		<tr>
			<td id="header_logo" rowspan="2" align="left"><a href="/" title="Home"><img src="templates/images/asterisk.gif" alt="Asterisk CDR Viewer" /></a></td>
			<td id="header_title">Asterisk CDR Viewer</td>
			<td align='right'>
				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=XVUVZY5D922JJ&lc=RU&item_name=i%2eo%2e&item_number=asterisk%2dcdr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted"><img src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" align="center"/></a>
			</td>
		</tr>
		<tr>
		<td id="header_subtitle">&nbsp;</td>
			<td align='right'>
			<?php
			if ( strlen(getenv('REMOTE_USER')) ) {
				echo "<a href='/acdr/index.php?action=logout'>logout: ". getenv('REMOTE_USER') ."</a>";
			}
			?>
		</td>
		</tr>
		</table>
