<?php
/**
 * Code snippet: cwsoft-anynews
 *
 * This code snippets grabs news entries from the WebsiteBaker news
 * module and displays them on any page you want by invoking the function
 * getNewsItems() via a page of type code or the index.php
 * file of the template.
 *
 * This file prevents directory listing.
 * 
 * LICENSE: GNU General Public License 3.0
 * 
 * @platform    CMS WebsiteBaker Community Edition (http://wbce.org)
 * @dependency  WebsiteBaker Classic News Module (https://github.com/cwsoft/wb-classic-news)
 * @package     cwsoft-anynews
 * @author      cwsoft (http://cwsoft.de)
 * @copyright   cwsoft
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
*/

// prevent this file from being accessed directly
if (defined('WB_PATH') == false) {
	exit("Cannot access this file directly");
}
