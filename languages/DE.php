<?php

// initialize global $LANG variable as array if needed
global $LANG;
if (! isset($LANG) || (isset($LANG) && ! is_array($LANG))) {
	$LANG = array();
}

$LANG['ANYNEWS'][0] = array(
	// text outputs for the frontend
	'TXT_HEADER'             => 'Aktuelle Nachrichten',
	'TXT_READMORE'           => 'weiter lesen',
	'TXT_NO_NEWS'            => 'Keine Nachrichten vorhanden.',
	'TXT_NEWS'               => 'Nachricht',
	'TXT_NUMBER_OF_COMMENTS' => 'Anzahl Kommentare',
	
	// date/time format: (21:12, 31.12.2012)
	'DATE_FORMAT'            => 'H:i, d.m.Y'
);