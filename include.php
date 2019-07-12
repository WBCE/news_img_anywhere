<?php


// prevent this file from being accessed directly
if (defined('WB_PATH') == false) {
	exit("Cannot access this file directly");
}

/**
 * Extracts requested news from the news with images module
 * and returns a string with the parsed nia template.
 */
if (! function_exists('getImageNewsItems'))
{
    require_once WB_PATH.'/modules/news_img/functions.inc.php';

	function getImageNewsItems($options=array())
	{
		global $wb, $database, $LANG;

		// default settings
		$defaults = array(
			'group_id_type' => 'group_id',    // type used by group_id to extract news entries (supported: 'group_id', 'page_id', 'section_id', 'post_id')
			'group_id' => 0,                  // IDs of news to show, matching defined $group_id_type (default:=0, all news, 0..N, or array(2,4,5,N) to limit news to IDs matching $group_id_type)
			'display_mode' => 1,              // 1:=details (default); 2:=list; 3:=coda-slider; 4:flexslider; 4-98 (custom template: display_mode_X.htt); 99:=cheat sheet
			'start_news_item' => 0,           // start showing news from the Nth news item onwards (default:= 0, min:=-999, max:= 999); Note: -1: last item, -2: 2nd last etc.
			'max_news_items' => 10,           // maximum number of news shown (default:= 10, min:=1, max:= 999)
			'max_news_length' => -1,          // maximum length of the short news text shown (default:=-1 => full news length)
			'strip_tags' => true,             // true:=remove tags from short and long text (default:=true); false:=don´t strip tags
			'allowed_tags' => '<p><a><img>',  // tags not striped off (default:='<p><a><img>')
			'custom_placeholder' => false,    // false:= none (default), array('MY_VAR_1' => '%TAG%#', ... 'MY_VAR_N' => '#regex_N#' ...)
			'sort_by' => 1,                   // 1:=position (default), 2:=posted_when, 3:=published_when, 4:=random order
			'sort_order' => 1,                // 1:=descending (default), 2:=ascending
			'not_older_than' => 0,            // 0:=disabled (default), 0-999 (only show news `published_when` date <=x days; 12 hours:=0.5)
			'lang_id' => 'AUTO',              // language file to load and lang_id used if $lang_filer = true (default:= auto, examples: AUTO, DE, EN)
			'lang_filter' => false,	          // flag to enable language filter (default:= false, show only news from a news page, which language fits $lang_id)
            'skip' => null,                   // do not show posts with the given list of tags (default:=none)
            'tags' => null,
		);

		// merge defaults and options array and remove unsupported keys
		$settings = array_merge($defaults, $options);
		foreach($settings as $key => $value) {
			if (! array_key_exists($key, $defaults)) {
				unset($settings[$key]);
			}
		}

		// export variables into function scope
		extract($settings);

		/**
		 * Include required Anynews files and language files
		 */
		require_once ('code/nia_functions.php');

        if(!function_exists('nia_truncate')) {
		    require_once ('thirdparty/truncate.php');
        }

		$lang_id = getValidLanguageId($lang_id);
		loadLanguageFile($lang_id);

		/**
		 * Sanitize user specified function parameters
		 */
		sanitizeUserInputs($group_id, 'i{0;0;999}');
		sanitizeUserInputs($start_news_item, 'i{0;-999;999}');
		sanitizeUserInputs($max_news_items, 'i{10;1;999}');
		sanitizeUserInputs($max_news_length, 'i{-1;0;250}');
		sanitizeUserInputs($display_mode, 'i{1;1;99}');
		sanitizeUserInputs($strip_tags, 'b');
		sanitizeUserInputs($allowed_tags, 's{TRIM}');
		sanitizeUserInputs($sort_by, 'i{1;1;5}');
		sanitizeUserInputs($sort_order, 'i{1;1;2}');
		sanitizeUserInputs($not_older_than, 'd{0;0;999}');
		sanitizeUserInputs($group_id_type, 'l{group_id;group_id;page_id;section_id;post_id}');
		sanitizeUserInputs($lang_filter, 'b');
        sanitizeUserInputs($skip,'s{TRIM|STRIP|ENTITY}');
        sanitizeUserInputs($tags,'s{TRIM|STRIP|ENTITY}');

		/**
		 * Create Twig template object and configure it
		 */
		// Twig is part of WBCE since commit 79aa15e
		$loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/templates');
		$twig = new Twig_Environment($loader, array(
			'autoescape'       => false,
			'cache'            => false,
			'strict_variables' => false,
			'debug'            => false,
		));

		/**
		 * Adds new Twig filter "strftime" (defined in "code/anynews_functions.php")
		 * Allows timestamp conversion using given locale (e.g. Freitag, 20 Juni 2013)
		 * Template usage: {{ timestamp | strftime('%A, %B %Y', ['de_DE','german','deu']) }}
		 * Help on format strings: http://ch1.php.net/manual/en/functi.strftime.php
		 */
		$twig->addFilter(new Twig_SimpleFilter('strftime', 'strftime_filter'));

		/**
		 * Load Anynews Twig template specified via $display_mode
		 */
		if (file_exists(dirname(__FILE__) . '/templates/display_mode_' . $display_mode . '.htt')) {
			$tpl = $twig->loadTemplate('display_mode_' . $display_mode . '.htt');
		} else {
			$tpl = $twig->loadTemplate('display_mode_1.htt');
		}

		/**
		 * Make WB_URL and Anynews language file available in Twig template
		 * Access via: {{ lang.KEY }}, {{ WB_URL }}
		 */
		// make Anynews language file text available in Twig template via: {{ lang.KEY }}
		$data = array();
		$data['WB_URL'] = WB_URL;
		foreach ($LANG['ANYNEWS'][0] as $key => $value) {
			$data['lang'][$key] = $value;
		}

		/**
		 * Work out SQL query for group_id, limiting news to display depending by defined $news_filter
		 *  option 1: $group_id:=0 => '1'
		 *  option 2: $group_id:=X => `group_id_type` = 'X'
		 *  option 3: $group_id:=array(2,3) => `group_id_type` IN (2,3)
		 */
		// show all news items if 0 is contained in group_id array
		if (is_array($group_id) && in_array(0, $group_id)) $group_id = 0;

		// check for multiple groups or single group values
		if (is_array($group_id)) {
			// SQL query for multiple groups
			$sql_group_id = "t1.`$group_id_type` IN (" . implode(',', $group_id) . ")";
		} else {
			// SQL query for single or empty groups
			$sql_group_id = ($group_id) ? "t1.`$group_id_type` = '$group_id'" : '1';
		}

		/**
		 * Work out SQL query for the not older than option
		 * This options allows to restrict the matches to news not older than X days
		 */
		// work out current server time (also used for published_when and published_until checks)
		$server_time = time();

		$sql_not_older_than = '1';
		if ($not_older_than > 0) {
			$sql_not_older_than = ' (t1.`published_when` >= \'' . ($server_time - ($not_older_than * 24 * 60 * 60)) . '\')';
		}

		/**
		 * Work out SQL query to hide news added via news pages NOT matching $lang_id
		 * Requires to organize news items via news pages with page language set to $lang_id
		 * Returns all news entries if no news page was found matching given $lang_id
		 **/
		$sql_lang_filter = '1';
		if ($lang_filter) {
			// get all page_ids which page language match defined $lang_id
			$page_ids = getPageIdsByLanguage($lang_id);
			if (count($page_ids) > 0) {
				$sql_lang_filter = 't1.`page_id` in (' . implode(',', $page_ids) . ')';
			}
		}

		/**
		 * Work out SQL sort by and sort order query string
		 */
		// creates SQL query for sort by option
		$order_by_options = array('t1.`position`', 't1.`posted_when`', 't1.`published_when`', 'RAND()');
		$sql_order_by = $order_by_options[$sort_by - 1];

		// creates SQL query for sort order option
		$sql_sort_order = ($sort_order == 1) ? 'DESC' : 'ASC';

        // filter by tags - requires NWI >= v4.1.0
        $filter_posts = array();
        $sql_filter_posts = null;
        include_once WB_PATH.'/modules/news_img/functions.inc.php';
        if(function_exists('mod_nwi_get_tags')) {
            if(!empty($skip)) {
                $skip_tags = explode(",",$skip);
                $r = $database->query(
                    "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
                    "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $skip_tags)."') ".
                    "GROUP BY `t2`.`post_id`"
                );
                while(null!==($row=$r->fetchRow())) {
                    $filter_posts[] = $row['post_id'];
                }
                if(count($filter_posts)>0) {
                    $sql_filter_posts = " AND `t1`.`post_id` NOT IN (".implode(',',array_values($filter_posts)).") ";
                }
            }
            if(!empty($tags)) {
                $tags = explode(",",$tags);
                $r = $database->query(
                    "SELECT `t2`.`post_id` FROM `".TABLE_PREFIX."mod_news_img_tags` as `t1` ".
                    "JOIN `".TABLE_PREFIX."mod_news_img_tags_posts` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`post_id`"
                );
                while(null!==($row=$r->fetchRow())) {
                    $filter_posts[] = $row['post_id'];
                }
                if(count($filter_posts)>0) {
                    $sql_filter_posts = " AND `t1`.`post_id` IN (".implode(',',array_values($filter_posts)).") ";
                }
            }
        } 

		/**
		 * Build SQL query for Anynews
		 */
		$news_table = TABLE_PREFIX . 'mod_news_img_posts';
    	$sql = "SELECT t1.*
			FROM `$news_table` as t1
			WHERE t1.`active` = '1'
			AND $sql_group_id
			AND $sql_lang_filter
			AND (t1.`published_when` = '0' or t1.`published_when` <= '$server_time')
			AND (t1.`published_until` = '0' OR t1.`published_until` >= '$server_time')
			AND $sql_not_older_than
            $sql_filter_posts
			GROUP BY t1.`post_id`
			ORDER BY $sql_order_by $sql_sort_order
		";

		// start from N-th last news item if $start_news_items is negative
		if ($start_news_item < 0) {
			// find total news items matching SQL query
			$results = $database->query($sql);
			$total_news = ($results) ? $results->numRows() : 0;

			// adjust start_news_item to the N-th last news item
			$start_news_item = $total_news + $start_news_item;
			if ($start_news_item < 0) $start_news_item = 0;
		}

		// add user defined limits to the SQL query
		$sql .= "
			LIMIT $start_news_item, $max_news_items
		";

		/**
		 * Process database query and output the template files
		 */
		// execute SQL query
		$results = $database->query($sql);

		// process results
		$data['newsItems'] = array();
		if ($results && $results->numRows() > 0) {
			// fetch news group titles from news database table
			$news_group_titles = getNewsGroupTitles();

			// fetch user names from users database table
			$user_list = getUserNames();

			// loop through all news articles found
			$news_counter = 0;
			while ($row = $results->fetchRow()) {
				// build absolute links from [wblink] tags found in news short or long text database field
				$wb->preprocess($row['content_short']);
				$wb->preprocess($row['content_long']);

				// remove tags from short and long text if defined
				$row['content_short'] = ($strip_tags) ? strip_tags($row['content_short'], $allowed_tags) : $row['content_short'];
				$row['content_long'] = ($strip_tags) ? strip_tags($row['content_long'], $allowed_tags) : $row['content_long'];

				// shorten news text to defined news length (-1 for full text length)
				if ($max_news_length != -1 && strlen($row['content_short']) > $max_news_length) {
					// truncate text if user asked for using CakePHP truncate function
					$row['content_short'] = nia_truncate($row['content_short'], $max_news_length);
				}


				// work out group image if exists
				$group_id = $row['group_id'];
				$gimage = '';
				if (file_exists(WB_PATH . MEDIA_DIRECTORY . '/.news_img/image' . $group_id . '.jpg')) {
					$gimage = '<img src="' . WB_URL . MEDIA_DIRECTORY . '/.news_img/image' . $group_id . '.jpg' . '" alt="" />';
				}

                // post image
				$nimage = $row['image'];
				if (file_exists(WB_PATH . MEDIA_DIRECTORY . '/.news_img/'.$row['post_id'].'/' . $nimage)) {
					$nimage =  WB_URL . MEDIA_DIRECTORY . '/.news_img/' .$row['post_id'].'/'. $nimage;
				}

				// make news item data available in Twig template: {{ newsItems.Counter.KEY }}
				$data['newsItems'][$news_counter] = array(
					'GROUP_IMAGE'        => $gimage,
					'NEWS_ID'            => $news_counter + 1,
					'POST_ID'            => (int)$row['post_id'],
					'SECTION_ID'         => (int)$row['section_id'],
					'PAGE_ID'            => (int)$row['page_id'],
					'GROUP_ID'           => (int)$row['group_id'],
					'GROUP_TITLE'        => array_key_exists($row['group_id'], $news_group_titles) ? htmlentities($news_group_titles[$row['group_id']]) : '',
					'POSTED_BY'          => (int)$row['posted_by'],
					'USERNAME'           => array_key_exists($row['posted_by'], $user_list) ? htmlentities($user_list[$row['posted_by']]['USERNAME']) : '',
					'DISPLAY_NAME'       => array_key_exists($row['posted_by'], $user_list) ? htmlentities($user_list[$row['posted_by']]['DISPLAY_NAME']) : '',
					'TITLE'              => ($strip_tags) ? strip_tags($row['title']) : $row['title'],
					'LINK'               => WB_URL . PAGES_DIRECTORY . $row['link'] . PAGE_EXTENSION,
					'CONTENT_SHORT'      => $row['content_short'],
					'CONTENT_LONG'       => $row['content_long'],
					'POSTED_WHEN'        => date($LANG['ANYNEWS'][0]['DATE_FORMAT'], $row['posted_when'] + (int) TIMEZONE),
					'PUBLISHED_WHEN'     => date($LANG['ANYNEWS'][0]['DATE_FORMAT'], $row['published_when'] + (int) TIMEZONE),
					'PUBLISHED_UNTIL'    => date($LANG['ANYNEWS'][0]['DATE_FORMAT'], $row['published_until'] + (int) TIMEZONE),
					'TS_POSTED_WHEN'     => $row['posted_when'] + (int) TIMEZONE,
					'TS_PUBLISHED_WHEN'  => $row['published_when'] + (int) TIMEZONE,
					'TS_PUBLISHED_UNTIL' => $row['published_until'] + (int) TIMEZONE,
					'IMAGE'              => $nimage,
				);

				// make custom placeholders available in Twig template: {{ newsItems.Counter.SHORT|LONG_REGEX_NAME_ID }}
				$custom_vars_short_text = getCustomOutputVariables($row['content_short'], $custom_placeholder, 'SHORT');
				$custom_vars_long_text = getCustomOutputVariables($row['content_long'], $custom_placeholder, 'LONG');
				$custom_vars = array_merge($custom_vars_short_text, $custom_vars_long_text);

				// replace custom placeholders in template with values
				foreach ($custom_vars as $key => $value) {
					$data['newsItems'][$news_counter][$key] = $value;
				}

				$news_counter++;
			}
		}

		// return parsed template
		return $tpl->render($data);

	}
}

/**
 * DEPRECATED:  Legacy wrapper for outdated cwsoft-anynews releases prior to v2.8.0
 * REPLACEMENT: echo getImageNewsItems($options);
 * NOTE: New features will be implemented in getImageNewsItems() and will not be backported to displayNewsItems().
 * Passes over parameters to getImageNewsItems() and echos return string to screen
 *
 */
if (! function_exists('displayNewsItems')) {
	function displayNewsItems(
		$group_id = 0,                  // IDs of news to show, matching defined $group_id_type (default:=0, all news, 0..N, or array(2,4,5,N) to limit news to IDs matching $group_id_type)
		$max_news_items = 10,           // maximum number of news shown (default:= 10, min:=1, max:= 999)
		$max_news_length = -1,          // maximum length of the short news text shown (default:=-1 => full news length)
		$display_mode = 1,              // 1:=details (default); 2:=list; 3:=coda-slider; 4:flexslider; 4-98 (custom template: display_mode_X.htt); 99:=cheat sheet
		$lang_id = 'AUTO',              // language file to load and lang_id used if $lang_filer = true (default:= auto, examples: AUTO, DE, EN)
		$strip_tags = true,             // true:=remove tags from short and long text (default:=true); false:=don´t strip tags
		$allowed_tags = '<p><a><img>',  // tags not striped off (default:='<p><a><img>')
		$custom_placeholder = false,    // false:= none (default), array('MY_VAR_1' => '%TAG%#', ... 'MY_VAR_N' => '#regex_N#' ...)
		$sort_by = 1,                   // 1:=position (default), 2:=posted_when, 3:=published_when, 4:=random order
		$sort_order = 1,                // 1:=descending (default), 2:=ascending
		$not_older_than = 0,            // 0:=disabled (default), 0-999 (only show news `published_when` date <=x days; 12 hours:=0.5)
		$group_id_type = 'group_id',    // type used by group_id to extract news entries (supported: 'group_id', 'page_id', 'section_id', 'post_id')
		$lang_filter = false,           // flag to enable language filter (default:= false, show only news from a news page, which language fits $lang_id)
        $skip = null,
        $tags = null
	)
	{
		// get cwsoft-anynews output for given parameters
		$output = getImageNewsItems(
			$options = array(
				'group_id_type' => $group_id_type,
				'group_id' => $group_id,
				'display_mode' => $display_mode,
				'max_news_items' => $max_news_items,
				'max_news_length' => $max_news_length,
				'strip_tags' => $strip_tags,
				'allowed_tags' => $allowed_tags,
				'custom_placeholder' => $custom_placeholder,
				'sort_by' => $sort_by,
				'sort_order' => $sort_order,
				'not_older_than' => $not_older_than,
				'lang_id' => $lang_id,
				'lang_filter' => $lang_filter,
                'skip' => $skip,
                'tags' => $tags,
			)
		);
		echo $output;
	}
}