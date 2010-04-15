<?php

	require_once("settings_inc.php");

	// First month to start with
	$start_date = time() - ( 60 * 60 * 24 * 30 * $past_month );

	// XML Calendar feed
	$xml_remote = $feed_url . "?max-results=" . $max_events . "&singleevents=true"
		. "&start-min=" . date("Y-m-d\TH:i:s-00:00", strtotime("-" . $past_month . " month", $start_date))
		. "&start-max=" . date("Y-m-d\TH:i:s-00:00", strtotime("+" . ($month_to_show - $past_month) . " month", $start_date));

	// When was the feed last updated
	$last_update = (time() - filemtime($xml_local)) / 60; // results converted from seconds to minutes

	// Check if the feed should be updated again
	if ($last_update > $update_frequency || $_GET["refresh"]) {

		// Update local feed using the remote xml feed
		$feed_content = file_get_contents($xml_remote);
		$handle = fopen($xml_local, 'w'); // open in write mode, create if doesn't exist
		fwrite($handle, $feed_content); // save the feed's data in the local file
		$last_update = 0; // We've just updated the feed so reset the time of the last update
	
	}

	// Cookie theme
	if ($_GET["theme"]) {
		$cssTheme = $_GET["theme"];
		setcookie("theme", $cssTheme, time() + (3600 * 24 * 365));
	} elseif ($_COOKIE["theme"]) {
		$cssTheme = $_COOKIE["theme"];
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>

	<title>Pixmin | Freelancing availability</title>

	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<meta name="author" content="Gaëtan Priour" />

	<link href="css/style.css" media="screen" rel="stylesheet" type="text/css" />
	<?php // THEME SWITCHER

		if ($cssTheme && file_exists("./css/" . $cssTheme . ".css")) {
	?><link href="css/<?php print $cssTheme; ?>.css" media="screen" rel="stylesheet" type="text/css" /><?php
		}

	?>

</head>

<body>

<div id="header">
	<h1>Freelancing availability | <em>Pixmin</em></h1>
	<h2>Current location: <em>Reykjavik, Iceland (GMT)</em></h2>
</div>

<div id="container"><?php	

	$doc = new DOMDocument(); 
	$doc->load( $xml_local );
	$entries = $doc->getElementsByTagName( "entry" );

	$busy = array();
	$offline = array();
	$out = array();
	
	foreach ( $entries as $entry ) { 

		$titles = $entry->getElementsByTagName( "title" ); 
		$title = $titles->item(0)->nodeValue;

		$times = $entry->getElementsByTagName( "when" );
		$startTime = $times->item(0)->getAttributeNode("startTime")->value;
		$endTime = $times->item(0)->getAttributeNode("endTime")->value;

		// Check the length of the event
		$length = (strtotime($endTime) - strtotime($startTime)) / 60 / 60 / 24;

		// Loads each day of the event into its respective array
		while ($length > 0) {

			// Remove one day each loop until we're done
			$length--;

			// Starting with the last day of the event and going back until the beginning
			$date_start = date("d.m.y", strtotime($startTime . " +" . $length . " day"));
			
			// Save marked days in arrays
			if (strstr($title, "offline")	)		$offline[] = 	$date_start;
			elseif (strstr($title, "busy"))		$busy[] = 		$date_start;
			elseif ($title == "out")				$out[] = 		$date_start;

		}
	}

	// Print the month calendar
	function cal_month($month, $year, $show_events = true) {
		
		global $busy, $offline, $out;
		$days_in_month = date('t', strtotime($month . "/1/" . $year));
		$day_of_week = date('N', strtotime($month . "/1/" . $year));
		$days = 0;
		$days_before = 0;

		// First fill in the blank
		while($day_of_week > 1) {
			print "<span>&nbsp;</span>\n";
			$day_of_week--;
			$days++;
			$days_before++;
		}

		// Then print the days
		for ($i=1; $i<=$days_in_month; $i++) {
		
			// Date being printed
			$date = date('d.m.y', strtotime($month . "/" . $i . "/" . $year));
			
			print "<span class='";

			// Shall we add events to the calendar?
			if ($show_events == true) {
				if (in_array($date, $busy)) { print "busy "; }
				if (in_array($date, $offline)) { print "offline "; }
				if (in_array($date, $out)) { print "out "; }
			}

			// Check if the is a WE day
			if (date("D", strtotime($month . "/" . $i . "/" . $year)) == "Sat" || date("D", strtotime($month . "/" . $i . "/" . $year)) == "Sun") {
					print " we";
			}
			
			print "'>" . $i . "</span>\n";
			$days++;
		}
		
		// Add extra line if needed
		if ($days<=35) {
			for($i=0; $i<7; $i++) {
				print "<span>&nbsp;</span>\n";
			}
			if ($days_before<1) {
				print "<span>&nbsp;</span>\n";
			}
		}

	}

	// Print all needed months
	for ($i=0; $i<$month_to_show; $i++) {
	
		// current date in the loop
		$date = strtotime("+" . $i. " month", $start_date);
		$month = date("n", $date);
		$year = date("y", $date);
	
		?>
		<div class="month<?php
		
			if ($month == date('n'))
				print " current";
			elseif ($date < strtotime("now"))
				print " past";
			
			?>">
			<h3><?php print date("F Y", $date); ?></h3>
			<?php cal_month($month, $year, $date > strtotime("now")); ?>
		</div>
		<?php
		
	}
	
?>

	<div id="legend">

		<dl>

			<dt><span class="busy">&nbsp;</span> Busy</dt>
			<dd>Working on a project, it could be yours!</dd>
			
			<dt><span class="out">&nbsp;</span> Out</dt>
			<dd>On the move, I might be able to check my emails infrequently</dd>
			
			<dt><span class="offline">&nbsp;</span> Offline</dt>
			<dd>No network, I'll answer you when I get back</dd>
			
		</dl>
		
	</div>

	<div id="footer">

		<p>Updated <?php

			print floor($last_update) . " minutes";

		?> ago. Next feed update in <?php

			print intval($update_frequency - $last_update) . " minutes.";

		?> Theme: <a href="?theme=grey">Grey</a> | <a href="?theme=chocolate">Chocolate</a> | <a href="?theme=green">Green</a></p>
		
	</div><!-- #footer -->
	
</div><!-- #container -->

<?php /*
<!-- *** DEBUG ***

Offline:
<?php var_dump($offline); ?>

Busy:
<?php var_dump($busy); ?>

Out:
<?php var_dump($out); ?>

-->
*/ ?>
</body>
</html>
