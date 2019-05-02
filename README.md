# News with Images anywhere: Addon for WBCE CMS
The code snippet `News with Images anywhere` (NIA) is designed to fetch news entries from the `News with images module´. Invoke NIA where you want the news output to appear in your frontend. Optional configuration parameters, HTML templates, content placeholders and CSS definitions allow you to style the news output the way you want. NIA ships with four templates - including two jQuery sliding effects - ready to use out of the box.

## Download
Download is available in the [WBCE CMS Add-On Repository](https://addons.wbce.org).

## License
The NIA code snippet is licensed under the [GNU General Public License (GPL) v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

## Requirements
The minimum requirements to get NIA running on your WBCE installation are as follows:

- [CMS WBCE](https://wbce.org)
- News with images module(Core module since WBCE CMS 1.4)

## Installation
1. download latest archive from [AOR](https://addons.wbce.org)
2. install the downloaded archive the usual way (WBCE Backend > Add-Ons > Modules > Install)

	
## Usage
As `NIA` is designed to fetch news items from the `News with images` module], you need to install the NWI module and add some news entries **before** you can use NIA. If no news are available, the message "No news available yet" is shown. Follow the steps below to add some news entries with the NWI module.

1. log into your WBCE backend and go to the `Pages` section
2. create a new page or section of type `News with images` (set visibility to None)
3. add some news entries (2-3) from the news page in the WBCE backend

### Use NIA from a page or section
Create a new page or section of type `Code` in the WBCE backend and enter the following code to it.
The NIA output is only visible at the pages/sections of your frontend, which contain this code.

	if (function_exists('getImageNewsItems')) {
		echo getImageNewsItems();
	}

### Use NIA from your template
To display news items at a fixed position on every page of your frontend, open the ***index.php*** file of your default frontend template with the cwsoft-addon-file-editor (available in WBCE CMS AOR). Then add the code below to the position in your template where you want the news output to appear.

	<?php
		if (function_exists('getImageNewsItems')) {
			echo getImageNewsItems();
		}
	?>

Visit the frontend of your website and check the NIA output. 

### Use NIA from a Droplet
You can invoke NIA via a Droplet call from a WYSIWYG editor or your template. The Droplet accepts the NIA function parameters in any order. To create an NIA Droplet, follow the steps below.

1. create a new Droplet called `getImageNewsItems` via WBCE Admin-Tools --> Droplets
2. enter the following code into the Droplet code section

		if (! file_exists(WB_PATH . '/modules/news_img_anywhere/droplet/nia_droplet.php')) return;
		include(WB_PATH . '/modules/news_img_anywhere/droplet/nia_droplet.php');
		return $output;
	
Now you can use the Droplet from your WYSIWYG editor or template file via:

	[[getImageNewsItems?group_id=1,2&display_mode=4]]

***Note:*** The NIA Droplet supports all NIA parameters (except the rarely used parameter 'custom_placeholder').

## Customize
The NIA output can be customized to your needs by three methods:

1. parameters/configuration array passed to the NIA function
2. customized NIA template files ***templates/display_mode_X.htt***
3. customized CSS definitions in file ***/css/NIA.css***
	
### NIA configuration
An overview of all supported configuration options is given in the section [supported configuration options](#supported-configuration-options).

Calling `getImageNewsItems` without configuration array uses the DEFAULTS below:

	$config = array(
		'group_id_type' => 'group_id',
		'group_id' => 0,
		'display_mode' => 1,
		'start_news_item' => 0,
		'max_news_items' => 10,
		'max_news_length' => -1,
		'strip_tags' => true,
		'allowed_tags' => '<p><a><img>',
		'custom_placeholder' => false,
		'sort_by' => 1,
		'sort_order' => 1,
		'not_older_than' => 0,
		'lang_id' => 'AUTO',
		'lang_filter' => false,
	);
	
	// calling getImageNewsItems() without configuration array uses the defaults above
	echo getImageNewsItems();


**Example:** To show only news associated to sectionID=8 and to set display_mode=4 the default configuration can be overwritten. Omitted paramters are set to the DEFAULTS shown above.

	// customized NIA function call
	$config = array(
		'group_id_type' => 'section_id',
		'group_id' => 8,
		'display_mode' => 4,
	);
	echo getImageNewsItems($config);
	
#### Supported configuration options

- **group_id_type**: defines group type used by group_id to extract news entries from
	[supported: 'group_id', 'page_id', 'section_id', 'post_id')]

- **group_id**: only show news which IDs match given *group_id_type* (default 'group_id')
	[0:all news, 1..N, or array(2,4,5,N) to limit news to single Id or multiple Ids, matching *group_id_type*]

- **display_mode**: ID of the NIA template to use (/templates/display_mode_X.htt)
	[1:details, 2:list, 3:better-coda-slider, 4:flexslider, 5..98 custom template *display_mode_X.htt*]
	Hint: 99:cheat sheet with ALL NIA placeholders available in the template files
	
- **start_news_item**: start showing news from the Nth news item onwards (Note: -1: last item, -2: 2nd last etc.)
	[valid: -999..999]

- **max_news_items**: max. number of news entries to show  
	[valid: 1..999]
	
- **max_news_length**: max. news length to be shown  
	[-1:= full length]
	
- **strip_tags**: flag to strip tags from news short/long text ***not*** contained in *allowed_tags*
	[true:strip tags, false:don't strip tags]
	
- **allowed_tags**: tags to keep if *strip_tags = true*
	[default: '&lt;p&gt;&lt;a&gt;&lt;img&gt;']

- **custom_placeholder**: create own placeholders for usage in template files  
	**Example:** custom\_placeholder = array('MY\_IMG' => '%img%', 'MY\_TAG' => '%author%', 'MY\_REGEX' => '#(test)#i')
	
	Stores all image URLs, all text inside &lt;author&gt;&lt;/author&gt; tags and all matches of "test" in placeholders:  {PREFIX\_MY\_IMG\_#}, {PREFIX\_MY\_TAG\_#}, {PREFIX\_MY\_REGEX\_#}, where ***PREFIX*** is either "SHORT" or "LONG", depending if the match was found in the short/long news text and ***#*** is a number between 1 and the number of matches found
	
- **sort_by**: defines the sort criteria for the news items returned  
	[1:position, 2:posted_when, 3:published_when, 4:random order]
	
- **sort_order**: defines the sort order of the returned news items  
	[1:descending, 2:=ascending]
	
- **not_older_than**: skips all news items which are older than X days  
	[0:don't skip news items, 0...999: skip news items older than x days (hint: 0.5 --> 12 hours)]

- **lang_id**: defines NIA language file to be used
	[allowed: 'AUTO', or a valid WB language file flag: 'DE', 'EN', ...]

- **lang_filter**: flag to enable language filter (requires a language flag in page URL like domain.com/EN/news.php)
	[default:= false, true:=only show news, which page language match given $lang_id]
	
***Tip:*** 
To output *news title* and all possible NIA *group_type_ids* (post_id, section_id, page_id, group_id), add the following code into a page/section of type code.

	require_once(WB_PATH . '/modules/news_img_anywhere/code/NIA_functions.php');
	getGroupIdTypes($sort_column = "post_id", $sort_order = "ASC", $output = true);

Then visit the created page/section in your frontend and extract the *group_tpye_ids* you want to use in your NIA function call.
	
### NIA Templates
The HTML skeleton of the NIA output is defined by template files **display_mode_X.htt** stored in the NIA subfolder **templates**. The template file used is defined by the NIA function parameter **$display_mode**, which defaults to 1 if no valid input is defined. To create your own NIA template, create a new file in the NIA template folder and rename it to **templates/display_mode_5.htt**. You can use the cwsoft-addon-file-editor to create and edit this file via the WBCE backend.

#### Step 1:
Add the HTML skeleton below to your custom template file. All NIA output should be wrapped in a div with class "mod_nia" to prevent CSS clashes with other modules, templates or the WBCE core. 

	<div class="mod_nia">
		<h1>NIA Header (shown only once)</h1>
		
		<!-- next three lines will be repeated for each existing news entry -->
		<h2>News Title (repeated for each news item)</h2>
		<p>Dummy news text </p>

		<!-- this line should only show up if no news item exists -->
		<p>No news available yet</p>
	</div>

#### Step 2:
Now we add control statements for the template parser [Twig](http://twig.sensiolabs.org/) used by NIA. The line `{% for news in newsItems %}` loops over all news defined in the variable `newsItems` created by NIA. The line `{% if news.TS_POSTED_WHEN > 0 %}` prevents that news just created but not yet saved are listed. Inside the loop, news data extracted from the news module is accessible from the variable `news` created by Twig. Outputs enclosed in `{% else %}` and `{% endfor %}` is only displayed if no news exist at all.

	<div class="mod_nia">
		<h1>NIA Header (shown only once)</h1>
		
		{% for news in newsItems %}		
			{% if news.TS_POSTED_WHEN > 0 %}
				<h2>News Title (repeated for each news item)</h2>
				<p>Dummy news text </p>
			{% endif %}
			
		{% else %}
			<p>No news available yet</p>
		{% endfor %}
	</div>


#### Step 3:	
Finally we replace the dummy text with placeholders provided by NIA. Data from the news module is stored in the placeholder `newsItems`. Text outputs from NIA language files is stored in the placeholder `lang`. Review the template file ***display_mode_99.htt*** (cheat sheet) to get a list of all available NIA placeholders. Remember to wrap your placeholders with double currly brackets {{ placeholder }}.

	<div class="mod_nia">
		<h1>{{ lang.TXT_HEADER }}</h1>
		
		{% for news in newsItems %}
			{% if news.TS_POSTED_WHEN > 0 %}
				<h2>{{ news.TITLE }}</h2>
				{{ news.CONTENT_LONG }}
			{% endif %}
			
		{% else %}
			<p>{{ lang.TXT_NO_NEWS }}</p>
		{% endfor %}
	</div>

If you want to create a custom template with jQuery effects, look at the template files ***display_mode_3.htt*** and ***display_mode_4.htt***, which implement 3rd party jQuery sliding effects.
To learn more about the possibilities of the template parser Twig, please have a look at the excellent [Twig user manual](http://twig.sensiolabs.org/doc/templates.html).

### NIA CSS
The NIA default templates (*/templates/display_mode_X.htt*) wrap the NIA output in a div container as shown below.

	<div class="mod_nia">
		<h2>Dummy Header</h2>
		<p>Dummy news text to explain</p>
	</div>
	
To change the news header of aboves example to green and the news text to blue, open the ***css/NIA.css*** file in the [cwsoft-addon-file-editor and add the following CSS definitions.

	div.mod_nia h2 {
		color: green;
	}

	div.mod_nia p {
		color: blue;
	}

***Note:*** It is common practice to limit the scope of the CSS defintions to the div mod_nia. This practice ensures that your CSS definitions do not overwrite styles defined in other modules, templates or the WBCE core. You should stick to this good practice when creating your own template files.
	
## Known Issues
You can track the status of known issues or report new issues found in NIA via WBCE CMS forum. 

## Questions
If you have questions or issues with NIA, please visit the [WBCE Forum](https://forum.wbce.org) and ask for help.

***Always provide the following information with your support request:***

 - detailed error description (what happens, what have you already tried ...)
 - the NIA version (go to WBCE section Add-ons / Info / News with Images anywhere)
 - your PHP version (use phpinfo(); or ask your provider if in doubt)
 - WBCE version
 - name of the WBCE frontent template used 
 - information about your operating system (e.g. Windows, Mac, Linux) incl. version
 - information of your browser and browser version used
 - information about changes you made to WBCE (if any)
