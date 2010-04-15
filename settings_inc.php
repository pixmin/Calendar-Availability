<?php

	/*
		Calendar settings
	*/

	// Feed (in google, in your calendar settings, XML Private Address and replace "basic" by "full"
	$feed_url = "";

	// File used to cache/save the feed data, refreshed only if needed
	$xml_local = "cal.xml";

	// How many month should be displayed in total
	$month_to_show = 8;

	// How many past months should appear before the current month
	$past_month = 1;

	// Frequency of feed updates in minutes (using the cached version until it expires)
	$update_frequency = 60;

	// How many events should be fetched from the feed
	$max_events = $month_to_show * 30; // One event per day max times the number of month to show

?>
