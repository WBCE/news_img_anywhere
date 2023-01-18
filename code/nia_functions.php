<?php
// prevent this file from being accessed directly
if (defined('WB_PATH') == false) {
	exit("Cannot access this file directly");
}

/**
 * Function to work out language ID
*/
function getValidLanguageId($lang_id)
{
	$lang_id = strtoupper($lang_id);

	switch($lang_id) {
		case 'AUTO':
			// loop over all Anynews language files ($file => DE.php, EN.php ...) and compare language id from URL
			foreach(glob(dirname(__FILE__) . '/../languages/[A-Z]*.php') as $file) {
				// check if actual page URL contains a language flag (e.g. /EN/ --> http://yourdomain.com/pages/en/xxx)
				$lang_flag = '/' . substr(basename($file), 0, -4) . '/';
				
				if (strpos(strtoupper($_SERVER['SCRIPT_NAME']), $lang_flag) !== false) {
					// return language id from the page URL
					return substr(basename($file), 0, -4);
				}
			}

			// no flag found in URL, use WebsiteBaker LANGUAGE constant
			return strtoupper(substr(LANGUAGE, 0, 2));

		default:
			return substr($lang_id, 0, 2);
	}
}

/**
 * Function to include the module language file
*/
function loadLanguageFile($lang_id)
{
	// include Anynews language file if exists, use EN.php as fallback
	global $LANG;
	$lang_path = dirname(__FILE__) . '/../languages/';
	require(file_exists($lang_path . $lang_id . '.php') ? $lang_path . $lang_id . '.php' : $lang_path . 'EN.php');
}

/**
 * Function to sanitize function input parameters
*/
function sanitizeUserInputs(&$input, $filter)
{
	// $input...        input variable to filter
	// $filter...       filter to apply for input variable
	//	Numeric filter: b|i¦d{default;min;max}
	//	List filter:    l{default;string1;string2;..;stringN}
	//	String filter:  s{TRIM|STRIP|ENTITY}

	// check if a valid filter was supplied
	if (!preg_match('#(b|i|d|s|l)#i', $filter, $match)) {
		echo 'Filter: <b>' . htmlentities($filter) . '</b> is no valid filter expression.';
		die;
	}

	// convert user input to array (allows to handle single values and array identical)
	$temp = is_array($input) ? $input : array($input);

	// evaluate filter expressions
	$filter_type = strtolower($match[1]);
	switch ($filter_type) {
		case 'b': case 'i': case 'd': // numeric filter ($input can be single value or array)
			// check if optional filter values are supplied (default, min, max)
			$advanced_filter = (preg_match('#(i|d)\{([-.0-9]+);([-.0-9]+);([-.0-9]+)\}#i', $filter, $match));

			// loop over input values
			foreach($temp as $key => $value) {
				// force type casting to either integer or double
				if ($filter_type == 'b') $temp[$key] = (boolean) $temp[$key];
				if ($filter_type == 'i') $temp[$key] = (int) $temp[$key];
				if ($filter_type == 'd') $temp[$key] = (double) $temp[$key];

				// check if value is within min/max range, if not use default value
				if ($advanced_filter) {
					$temp[$key] = ($temp[$key] >= $match[3] && $temp[$key] <= $match[4] ) ? $temp[$key] : $match[2];			
				}
			}
			break;
			
		case 'l': // list filter
			// check for correct list filter: l{default;list1;list2;..;listN}
			if (!preg_match('#(l)\{([^;]+?);(.+)\}#i', $filter, $match)) {
				echo 'List filter: <b>' . htmlentities($filter) . '</b> invalid. Usage: <b>l{default;list1;list2;..listN}</b>.';
				die;
			}

			// create array with list values from regular expression
			$list_values = explode(';', $match[3]);
			
			// loop over input values
			foreach($temp as $key => $value) {
				// check if value is in list (return default value if not in list)
				$temp[$key] = (in_array($value, $list_values) ? $value : $match[2]);
			}
			break;

		case 's': // string filter
			// check for correct string filter: s{TRIM|STRIP|ENTITY}
			if (!preg_match('#(s)\{(.+)\}#i', $filter, $match)) {
				echo 'String filter: <b>' . htmlentities($filter) . '</b> invalid. Usage: <b>s{STRIP;TRIM;ENTITIES}</b>.';
				die;
			}

			// get filter options from regular expression
			$filter_options = strtoupper($match[2]);

			// loop over input values
			foreach($temp as $key => $value) {
				// check if value is in list (return default value if not in list)
				if (strpos($filter_options, 'STRIP') !== false && $temp[$key] != null) $temp[$key] = strip_tags($temp[$key]);
				if (strpos($filter_options, 'TRIM') !== false  && $temp[$key] != null) $temp[$key] = trim($temp[$key]);
				if (strpos($filter_options, 'ENTITIES') !== false) $temp[$key] = htmlentities($temp[$key]);
			}
			break;			
	}
	
	// revert user input back to array or single value
	$input = is_array($input) ? $temp : $temp[0];
}

/**
 * Function to fetch user defined output variables from short/long news database fields
*/
function getCustomOutputVariables($content, $regex_array, $var_prefix = '')
{
	if (!is_array($regex_array)) return array();

	$var_prefix = strtoupper($var_prefix);
	$custom_placeholders = array();
	// loop over all regular expressions defined by the user
	foreach($regex_array as $placeholder => $regex) {
		$placeholder = strtoupper($placeholder);
		
		// check for shorthand regex to fetch content from HTML tags (%TAG%)
		if (preg_match('#%(.*?)%#i', $regex, $match)) {
			$tag = strtolower(str_replace('%', '', $match[1]));
			$tag_regex = ($tag == 'img') ? '#(<img[^>]*[/]??>)#i' : '#<' . $tag . '[^>]*>(.*?)</' . $tag . '>#i';

			// check if shorthand regex matches
			if (preg_match_all($tag_regex, $content, $match)) {
				foreach($match[1] as $index => $value) {
					$custom_placeholders[$var_prefix . '_' . $placeholder . '_' . ($index + 1)] = $value;
				}
			}

		} elseif (preg_match('#(.*)#', $regex, $match)) {
			// check for user defined regex to fetch content from
			if (preg_match_all($match[0], $content, $match)) {
				// check if a capturing group was defined (allow only one)
				$match = (count($match) == 1) ? $match : $match[1];
				
				// return all matches found
				foreach($match as $index => $value) {
					$custom_placeholders[$var_prefix . '_' . $placeholder . '_' . ($index + 1)] = $value;
				}
			}
		}
	}
	return $custom_placeholders;
}

/**
 * Function to page_ids matching selected lang_id
*/
function getPageIdsByLanguage($lang_id)
{
	global $database;
	
	$table = TABLE_PREFIX . 'pages';
	$sql = "SELECT `page_id` FROM `$table` WHERE `language` = '$lang_id'";

	// fetch data from the database
	$results = $database->query($sql);
	
	$page_ids = array();
	while ($results && $row = $results->fetchRow()) {
		$page_ids[] = $row['page_id'];
	}
	return $page_ids;
}

/**
 * Function to fetch news group titles from database
*/
function getNewsGroupTitles()
{
	global $database;

	$table = TABLE_PREFIX . 'mod_news_img_groups';
	$sql = "SELECT `group_id`, `title` FROM `$table` WHERE `active` = '1'";
	
	// fetch data from the database
	$results = $database->query($sql);
	
	$news_groups = array();
	while ($results && $row = $results->fetchRow()) {
		$news_groups[$row['group_id']] = $row['title'];
	}
	return $news_groups;
}

/**
 * Function to fetch news group titles from database
*/
function getUserNames()
{
	global $database;

	$table = TABLE_PREFIX . 'users';
	$sql = "SELECT `user_id`, `username`, `display_name` FROM `$table`";

	// fetch data from the database
	$results = $database->query($sql);
	
	$users = array();
	while ($results && $row = $results->fetchRow()) {
		$users[$row['user_id']] = array('USERNAME' => $row['username'], 'DISPLAY_NAME' => $row['display_name']);
	}
	return $users;
}

/**
 * Function to convert a timestamp via the PHP strftime function
 * Help on format strings: http://ch1.php.net/manual/en/function.strftime.php
 */
function strftime_filter($timestamp, $format, $locales) {
	// convert timestamp using PHP strftime function
	setlocale(LC_ALL, $locales);
	return strftime($format, $timestamp);
}

/**
 * Utility function to display possible Anynews group_id_types for each news entry
 * Usefull to find news matching specific "post_ids", "section_ids", "page_ids" or "group_ids"
*/
function getGroupIdTypes($sort_column = "post_id", $sort_order = "ASC", $output = true)
{
	global $database;

	// sanitize optional function parameters
	$sort_columns = array('post_id', 'section_id', 'page_id', 'group_id', 'title');
	$sort_column = in_array($sort_column, $sort_columns) ? $sort_column : 'post_id';
	$sort_order = in_array($sort_order, array('ASC', 'DESC')) ? $sort_order: 'ASC';
	$output = (bool) $output;

	$table = TABLE_PREFIX . 'mod_news_img_posts';
	$sql = "SELECT * FROM `$table` WHERE `active` = '1' ORDER BY `$sort_column` $sort_order";

	// fetch data from the database
	$results = $database->query($sql);

	$news_ids = array();
	while ($results && $row = $results->fetchRow()) {
		$news_ids[] = array(
			"title" => $row['title'],
			"post_id" => $row['post_id'],
			"section_id" => $row['section_id'],
			"page_id" => $row['page_id'],
			"group_id" => $row['group_id'],
		);
	}

	if ($output) {
		// output array in user readable form on screen
		print("<pre>" . print_r($news_ids, true) . "</pre>");
	} else {
		// return array for further usage
		return $news_ids;
	}
}