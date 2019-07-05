<?php
/**
 * Droplet: [[getImageNewsItems?group_id=1,2&supported_paramter_in_any_order=VALUE&further_parameter_in_any_order=VALUE]]
 * USAGE:
 *   1. create a new droplet "getImageNewsItems" via Admin-Tools --> Droplets
 *   2. enter the following code into the droplet code section:
 *      if (! file_exists(WB_PATH . '/modules/news_img/anwhere/droplet/cwsoft-anynews-droplet.php')) return;
 *      include(WB_PATH . '/modules/news_img/anwhere/droplet/cwsoft-anynews-droplet.php');
 *      return $output;
 */

// prevent this file from being accessed directly
if (defined('WB_PATH') == false) {
	exit("Cannot access this file directly");
}

// ensure we have the right cwsoft-anynews version
$output = '';
require_once(WB_PATH . '/modules/news_img_anywhere/include.php');
if (! function_exists('getImageNewsItems')) return;

// supported cwsoft-anynews options
$options = array(
	'group_id_type' => 'group_id',    // type used by group_id to extract news entries (supported: 'group_id', 'page_id', 'section_id', 'post_id')
	'group_id' => 0,                  // IDs of news to show, matching defined $group_id_type (default:=0, all news, 0..N, or array(2,4,5,N) to limit news to IDs matching $group_id_type)
	'display_mode' => 1,              // 1:=details (default); 2:=list; 3:=coda-slider; 4:flexslider; 4-98 (custom template: display_mode_X.htt); 99:=cheat sheet
	'start_news_item' => 0,           // start showing news from the Nth news item onwards (default:= 0, min:=-999, max:= 999); Note: -1: last item, -2: 2nd last etc.
	'max_news_items' => 10,           // maximum number of news shown (default:= 10, min:=1, max:= 999)
	'max_news_length' => -1,          // maximum length of the short news text shown (default:=-1 => full news length)
	'strip_tags' => true,             // true:=remove tags from short and long text (default:=true); false:=don´t strip tags
	'allowed_tags' => '<p><a><img>',  // tags not striped off (default:='<p><a><img>')
	'custom_placeholder' => false,    // false:= none (default), array('MY_VAR_1' => '%TAG%#', ... 'MY_VAR_N' => '#regex_N#' ...)
	'sort_by' => 1,                   // 1:=position (default), 2:=posted_when, 3:=published_when, 4:=random order, 5:=number of comments
	'sort_order' => 1,                // 1:=descending (default), 2:=ascending
	'not_older_than' => 0,            // 0:=disabled (default), 0-999 (only show news `published_when` date <=x days; 12 hours:=0.5)
	'lang_id' => 'AUTO',              // language file to load and lang_id used if $lang_filer = true (default:= auto, examples: AUTO, DE, EN)
	'lang_filter' => false,	          // flag to enable language filter (default:= false, show only news from a news page, which language fits $lang_id)
    'skip' => null,
);

// update options with user defined values
foreach($options as $key => $value) {
	// remove options not defined via [[displayNewsItems?supported_option=VALUE]]
	if (! isset(${$key})) {
		unset($options[$key]);
		continue;
	}
	
	// update options with user defined values (will be sanitized in getImageNewsItems later on)
	if ($key == 'group_id') {
		$options[$key] = explode(',', ${$key});
	} else {
		if (in_array($key, array('allowed_tags'))) {
			// deal with fact that Droplets don't yet support urldecode by itself
			${$key} = str_replace(array('&lt;', '%3C', '&gt;', '%3E'), array('<', '<', '>', '>'), ${$key});
		}
		if (in_array($key, array('strip_tags', 'lang_filter'))) {
			// deal with boolean types ('false', 'true' are both True if converted to Boolean)
			${$key} = str_replace(array('false', 'true'), array('0', '1'), strtolower(${$key}));
		}
		// strip leading and trailing single and double quotes
		$options[$key] = trim(${$key}, '"\'');
	}
}

// receive string with parsed cwsoft-anynews template
$output = getImageNewsItems($options);