<?php
header('Content-Type: text/plain; charset=UTF-8');
include('iCalEasyReader.php');
$ical = new iCalEasyReader();
$lines = $ical->load(file_get_contents(__DIR__ . '/microsoft.ics'));

echo json_encode($lines, JSON_PRETTY_PRINT);
//var_dump( $lines );