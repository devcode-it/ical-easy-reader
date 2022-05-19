<?php
$theEvents = "Akce na severu";
$eventTitle = "";

include('iCalEasyReader.php');
$ical = new iCalEasyReader();
$lines = $ical->load(file_get_contents('https://events.gcm.cz/calendar.ics?location=Rumburk&radius=100'));

$events = $lines["VEVENT"];
foreach ($events as &$event) {
	unset($event["ATTENDEE"], $event["ORGANIZER"], $event["X-MICROSOFT-CDO-BUSYSTATUS"], $event["X-MICROSOFT-CDO-IMPORTANCE"], $event["X-MICROSOFT-DISALLOW-COUNTER"]);
	unset($event["X-MS-OLK-APPTSEQTIME"], $event["X-MS-OLK-AUTOFILLLOCATION"], $event["X-MS-OLK-CONFTYPE"], $event["VALARM"], $event["RECURRENCE-ID"]);
	unset($event["X-ALT-DESC"], $event["X-MS-OLK-AUTOSTARTCHECK"], $event["TRANSP"], $event["X-ALT-DESC;FMTTYPE=text/html"]);
	if (($event["DESCRIPTION"] == "\n") || ($event["DESCRIPTION"] == " \n\n") || ($event["DESCRIPTION"] == "\n\n") || ($event["DESCRIPTION"] == "")) {
		unset($event["DESCRIPTION"]);
	}
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