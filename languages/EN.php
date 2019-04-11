<?php
// initialize global $LANG variable as array if needed
global $LANG;
if (! isset($LANG) || (isset($LANG) && ! is_array($LANG))) {
	$LANG = array();
}

$LANG['ANYNEWS'][0] = array(
	// text outputs for the frontend
	'TXT_HEADER'             => 'Latest News',
	'TXT_READMORE'           => 'read more',
	'TXT_NO_NEWS'            => 'No news available yet.',
	'TXT_NEWS'               => 'News',
	'TXT_NUMBER_OF_COMMENTS' => 'Number of comments',
	
	// date/time format: (9:12 PM, 12/31/2012)
	'DATE_FORMAT'            => 'g:i A, m/d/Y'
);