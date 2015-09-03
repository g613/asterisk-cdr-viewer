<head>
	<title>Asterisk Call Detail Records</title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
	<link rel="stylesheet" href="style/screen.css" type="text/css" media="screen" />
	<link rel="shortcut icon" href="templates/images/favicon.ico" />
	<script language='JavaScript'>
		function NewDate( cur_date ) {
			var curr = new Date; // get current date
			var first;
			var last;
			
			if ( cur_date == 'tw' ) {
				first = curr.getDate() - curr.getDay()+1;
				last = new Date(curr.setDate(first+6));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == 'pw' ) {
				first = curr.getDate() - 7 - curr.getDay() + 1;
				last = new Date(curr.setDate(first+6));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == '3w' ) {
				first = curr.getDate() - 14 - curr.getDay() + 1;
				last = new Date(curr.setDate(first+20));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == 'td' ) {
				first = curr.getDate();
				last = new Date(curr.setDate(first));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == 'pd' ) {
				first = curr.getDate()-1;
				last = new Date(curr.setDate(first));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == '3d' ) {
				first = curr.getDate()-2;
				last = new Date(curr.setDate(first+2));
				first = new Date(curr.setDate(first));	
			} else if ( cur_date == 'tm' ) {
				last = new Date(curr.getFullYear(), curr.getMonth() + 1, 0);
				first = new Date(curr.getFullYear(), curr.getMonth(), 1);	
			} else if ( cur_date == 'pm' ) {
				last = new Date(curr.getFullYear(), curr.getMonth(), 0);
				first = new Date(curr.getFullYear(), curr.getMonth()-1, 1);	
			} else if ( cur_date == '3m' ) {
				last = new Date(curr.getFullYear(), curr.getMonth()+1, 0);
				first = new Date(curr.getFullYear(), curr.getMonth()-2, 1);	
			}
		
			if ( typeof(first) !== 'undefined' ) {
				document.getElementById("startmonth").selectedIndex = first.getMonth();
				document.getElementById("startday").value = first.getDate();
				
				var selector = document.getElementById('startyear');
				for ( i = selector.options.length-1; i>=0; i-- ) {
					if ( selector.options[i].value == first.getFullYear() ) {
						selector.selectedIndex=i;
						break;
					}
				}
				document.getElementById("endmonth").selectedIndex = last.getMonth();
				document.getElementById("endday").value = last.getDate();
				
				selector = document.getElementById('endyear');
				for ( i = selector.options.length-1; i>=0; i-- ) {
					if ( selector.options[i].value == last.getFullYear() ) {
						selector.selectedIndex=i;
						break;
					}
				}
			}
		}
	</script>
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
