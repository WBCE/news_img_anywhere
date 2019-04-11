<?php
// initialize global $LANG variable as array if needed
global $LANG;
if (! isset($LANG) || (isset($LANG) && ! is_array($LANG))) {
	$LANG = array();
}

$LANG['ANYNEWS'][0] = array(
	// text outputs for the frontend
	'TXT_HEADER'             => 'Laatste nieuws',
	'TXT_READMORE'           => 'Lees meer',
	'TXT_NO_NEWS'            => 'Geen nieuws beschikbaar.',
	'TXT_NEWS'               => 'Nieuws',
	'TXT_NUMBER_OF_COMMENTS' => 'Aantal reakties',
	
	// date/time format: (31-12-2012, 09:12)
	'DATE_FORMAT'            => 'd-m-Y, H:i'
);