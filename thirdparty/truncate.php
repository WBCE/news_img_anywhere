<?php
/**
 * String handling methods.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @github        https://github.com/cakephp/cakephp/blob/master/lib/Cake/Utility/String.php
 * @since         CakePHP(tm) v 1.2.0.5551
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ellipsis if the text is longer than length.
 *
 * ### Options:
 *
 * - `ellipsis` Will be used as Ending and appended to the trimmed string (`ending` is deprecated)
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
 */
function truncate($text, $length = 100, $options = array()) {
	$default = array(
		'ellipsis' => '...', 'exact' => false, 'html' => true
	);
	if (isset($options['ending'])) {
		$default['ellipsis'] = $options['ending'];
	} elseif (!empty($options['html']) && (defined('DEFAULT_CHARSET') && strtoupper(DEFAULT_CHARSET) == 'UTF-8')) {
		$default['ellipsis'] = "\xe2\x80\xa6";
	}

	$options = array_merge($default, $options);
	extract($options);

	if ($html) {
		if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		$totalLength = mb_strlen(strip_tags($ellipsis));
		$openTags = array();
		$truncate = '';

		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($openTags, $tag[2]);
				} elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
					$pos = array_search($closeTag[1], $openTags);
					if ($pos !== false) {
						array_splice($openTags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
			if ($contentLength + $totalLength > $length) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entitiesLength <= $left) {
							$left--;
							$entitiesLength += mb_strlen($entity[0]);
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ($totalLength >= $length) {
				break;
			}
		}
	} else {
		if (mb_strlen($text) <= $length) {
			return $text;
		}
		$truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
	}
	if (!$exact) {
		$spacepos = mb_strrpos($truncate, ' ');
		if ($html) {
			$truncateCheck = mb_substr($truncate, 0, $spacepos);
			$lastOpenTag = mb_strrpos($truncateCheck, '<');
			$lastCloseTag = mb_strrpos($truncateCheck, '>');
			if ($lastOpenTag > $lastCloseTag) {
				preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
				$lastTag = array_pop($lastTagMatches[0]);
				$spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
			}
			$bits = mb_substr($truncate, $spacepos);
			preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
			if (!empty($droppedTags)) {
				if (!empty($openTags)) {
					foreach ($droppedTags as $closingTag) {
						if (!in_array($closingTag[1], $openTags)) {
							array_unshift($openTags, $closingTag[1]);
						}
					}
				} else {
					foreach ($droppedTags as $closingTag) {
						$openTags[] = $closingTag[1];
					}
				}
			}
		}
		$truncate = mb_substr($truncate, 0, $spacepos);
	}
	$truncate .= $ellipsis;

	if ($html) {
		foreach ($openTags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}

	return $truncate;
}