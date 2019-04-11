/**
 * Code snippet: cwsoft-anynews
 *
 * This code snippets grabs news entries from the WebsiteBaker news
 * module and displays them on any page you want by invoking the function
 * getNewsItems() via a page of type code or the index.php
 * file of the template.
 *
 * This file contains JavaScript code to display a message in case the 
 * jQuery files are not loaded via the index.php file of the frontend template.
 * 
 * LICENSE: GNU General Public License 3.0
 * 
 * @platform    CMS WebsiteBaker Community Edition (http://wbce.org)
 * @package     cwsoft-anynews
 * @author      cwsoft (http://cwsoft.de)
 * @copyright   cwsoft
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
*/

function showJqueryStatus(addinName) {
	if (typeof jQuery == 'undefined' || typeof(WB_URL) == "undefined") {
		// create a message to be displayed to the user
		msg = "Sorry, the required jQuery files for " + addinName + " are not loaded in your template.";
		msg += "\nPlease ensure the code below is included within the <head> section of your template 'index.php' file.";
		msg += "\nYou can use the external admin tool Addon File Editor (AFE) to modify this template file.";
		msg += "\n\n<?php\n    // automatically include optional WB module files (frontend.css, frontend.js)";
		msg += "\n    if (function_exists('register_frontend_modfiles')) {";
		msg += "\n        register_frontend_modfiles('css');";
		msg += "\n        register_frontend_modfiles('jquery');";
		msg += "\n        register_frontend_modfiles('js');"
		msg += "\n    }\n?>";
	
		// output status message so user is aware that he needs to modify the template to use all Anynews features
		alert(msg);
	}
}