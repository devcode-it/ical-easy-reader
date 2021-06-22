<?php
$theEvents = "";
$eventTitle = "";

include('iCalEasyReader.php');
$ical = new iCalEasyReader();
$lines = $ical->load(file_get_contents('calendar.ics'));
//$lines = $ical->load(file_get_contents('google.ics'));
//$lines = $ical->load(file_get_contents('dougtest.ics'));

/*
 * We're going to extract JUST the "VEVENT" items. Credit: salathe
 * https://stackoverflow.com/questions/16699939/how-to-slice-an-array-by-key-not-offset
 */
//make events be a reference to vevent
$events = $lines["VEVENT"];
//for each event

/**
 * Written by John J. Donna II
 * Yobo, Inc.
 * john@yobo.dev
 * 04/17/2020
 */
foreach ($events as &$event) {
	// Unsetting several items we don't need.
	unset($event["ATTENDEE"], $event["ORGANIZER"], $event["X-MICROSOFT-CDO-BUSYSTATUS"], $event["X-MICROSOFT-CDO-IMPORTANCE"], $event["X-MICROSOFT-DISALLOW-COUNTER"]);
	unset($event["X-MS-OLK-APPTSEQTIME"], $event["X-MS-OLK-AUTOFILLLOCATION"], $event["X-MS-OLK-CONFTYPE"], $event["VALARM"], $event["RECURRENCE-ID"]);
	unset($event["X-ALT-DESC"], $event["X-MS-OLK-AUTOSTARTCHECK"], $event["TRANSP"], $event["X-ALT-DESC;FMTTYPE=text/html"]);
	//	unset($event["XX"], $event["XX"], $event["XX"], $event["XX"], $event["XX"]);
	//	unset($event["XX"], $event["XX"], $event["XX"], $event["XX"], $event["XX"]);

	// We're going to ditch empty DESCRIPTION items, too. And then get rid of extra line breaks
	if (($event["DESCRIPTION"] == "\n") || ($event["DESCRIPTION"] == " \n\n") || ($event["DESCRIPTION"] == "\n\n") || ($event["DESCRIPTION"] == "")) {
		unset($event["DESCRIPTION"]);
	}

	//we now need to check if the keys inside this event are arrays
	if (isset($event["RRULE"]["FREQ"])) {
		$event["FREQ"] = &$event["RRULE"]["FREQ"];
	}
	if (isset($event["RRULE"]["UNTIL"])) {
		$event["UNTIL"] = &$event["RRULE"]["UNTIL"];
	}
	if (isset($event["RRULE"]["INTERVAL"])) {
		$event["INTERVAL"] = &$event["RRULE"]["INTERVAL"];
	}
	if (isset($event["RRULE"]["COUNT"])) {
		$event["COUNT"] = &$event["RRULE"]["COUNT"];
	}
	if (isset($event["RRULE"]["BYDAY"])) {
		$event["BYDAY"] = &$event["RRULE"]["BYDAY"];
	}
	if (isset($event["RRULE"]["BYMONTH"])) {
		$event["BYMONTH"] = &$event["RRULE"]["BYMONTH"];
	}
	//	if(isset($event["RRULE"]["XX"])) {$event["XX"] = &$event["RRULE"]["XX"];}
	//	if(isset($event["RRULE"]["XX"])) {$event["XX"] = &$event["RRULE"]["XX"];}

	unset($event["RRULE"]);
	if (@is_array($event["SUMMARY"])) {
		$event["SUMMARY"] = $event["SUMMARY"]["value"];
	}
	if (@is_array($event["DTSTART"])) {
		$event["DTSTART"] = $event["DTSTART"]["value"];
	}
	if (@is_array($event["DTEND"])) {
		$event["DTEND"] = $event["DTEND"]["value"];
	}
}
?>
<div style="display: none; width: 90%; margin: 0 auto;"><textarea style="width: 100%; height: 300px;">
</textarea></div>

<?php
echo "<pre>";
print_r($events);
echo "</pre>";