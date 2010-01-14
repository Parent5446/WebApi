<?php

if(!defined("API")) { return false; }

/**
 * This program was created to make a set of classes to be used for easy
 * website creation. Usage: simply extend the DB_Object class to make models
 * and the OUT_Request class to make controllers. HTML is stored in templates
 * with special tags. See the OUT_Template class for more details.
 * Made by Tyler Romeo <tylerromeo@gmail.com>
 *
 * Copyright (C) 2009 Tyler Romeo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Loads an HTML template from a local file, and replaces specific tags
 * with the given values.
 *
 * Tags take the following form:
 * <!options[[tagname]]>
 * replacing options with a comma separated list of options and tagname
 * with the name of the tag.
 *
 * For options, each option is delimited by a comma, and requires
 * an option name. If '=' and then a value is placed after the option
 * name, the value following the equals sign is assigned as the option value.
 * Valid options are:
 *  * array=separator - The value given to the template will be an array. Implode
 *                      the array using the given separator.
 *  * switch          - If set, instead of using the value in the template, determine
 *                      which of two static values to use, either the switch_true option
 *                      if $value is true, or the switch_false option if false.
 *  * noescape        - Do not escape HTML characters in the value
 *  * trim=4          - Trim the value to the given number of characters
 *  * padding=2       - Pad the value to the given number of characters
 *  * padvalue=\n     - Use the given value to pad the value rather than spaces
 *  * template        - Instead of inserting data from the database, it inserts another template
 *
 * @package API
 * @author Tyler Romeo <tylerromeo@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @copyright Copyright (c) 2009, Tyler Romeo (Some Rights Reserved)
 */
class OUT_Template
{
	/**
	 * Filename for the template.
	 * @private
	 */
	private $filename;

	/**
	 * The actual HTML template with tags.
	 * @private
	 */
	private $template;

	/**
	 * The values to replace tags within the template with.
	 * @private
	 */
	private $values;

	/**
	 * Reads an HTML template from the given filename and stores it in
	 * in the object. See class description for instructions on creating
	 * templates.
	 *
	 * @param string $filename Filename of template
	 */
	public function __construct($filename) {
		if(!file_exists($filename) || !is_readable($filename)) {
			return new MAIN_Error(MAIN_Error::WARNING, get_class($this) . '::__construct', 'Parameter is not the correct data type.', $this->log);
		}

		$this->filename = $filename;
		$this->template = file_get_contents($filename);
	}

	/**
	 * Gets a option from the option list.
	 *
	 * @param string $option Name of the option to retrieve
	 *
	 * @return string The option value
	 */
	public function getOption($option) {
		return isset($this->values[$option]) ? $this->values[$option] : false;
	}

	/**
	 * Stores a given value as an option in the template. Tags in the template
	 * with the same tag name as the option will be replaced with the value.
	 *
	 * @param string $option Name of the option
	 * @param string $value  Value of the option
	 *
	 * @return bool True on success, false otherwise
	 */
	public function putOption($option, $value) {
		if(!is_string($option) || !is_string($value)) {
			return false;
		} $this->values[$option] = $value;
		return true;
	}

	/**
	 * Scan the template and replace the tags with the given
	 * options.
	 *
	 * @return string Template with substituted tags
	 */
	public function execute() {
		$pattern  = '(<!([a-zA-Z0-9]*)\[\[([a-zA-Z0-9]+)\]\]>)';
		$callback = array(&$this, 'replaceTag');
		$subject  = $this->template;
		print preg_replace_callback($pattern, $callback, $subject);
	}

	/**
	 * Takes a tag that was matched in the template and replaces
	 * the entire tag with the stored value.
	 *
	 * Acts as the callback function for regular expression replacement
	 * in the execute() function. How the function replaces the tag
	 * varies depending on what options are placed in the tag in the template.
	 *
	 * @param array $matches Array with the matched tag and each parenthetical
	 *                       subpattern (given to function by preg_replace_callback)
	 *
	 * @return string Replacement string
	 */
	private function replaceTag($matches) {
		// Retrieve variables:
		//    whole      - Entire tag
		//    rawoptions - Options before being processed (separated, etc.)
		//    options    - Options specified in the tag
		//    tagname    - Name of the tag
		//    value      - Value corresponding to the tag name
		$whole      = $matches[0];
		$rawoptions = strpos($matches[1], ',') ? implode(',', $matches[1]) : array();
		$tagname    = trim($matches[2]);
		$value      = isset($this->values[$tagname]) ? $this->values[$tagname] : false;

		// Put options into proper array
		$options = array();
		foreach($rawoptions as $key => $value) {
			$temp = explode('=', $value);
			if(isset($temp[1])) {
				if($temp[1] === true) { $options[$temp[0]]  = true;  }
				if($temp[1] === false) { $options[$temp[0]] = false; }
				$options[$temp[0]] = $temp[1];
			} else {
				$options[$temp[0]] = true;
			}
		}

		// If the template option is set, stop here and load the second template.
		if(array_key_exists('template', $options)) {
			$filename = dirname($this->filename) . "/$tagname.template";
			$template = new OUT_Template($filename);
			$value    = $template->execute();
			$options['noescape'] = true;
		}

		// If array option is set, value should be an array of items.
		// Items are individually escaped, and then imploded using the given
		// option value as the glue. If the noescape option is set, the array
		// values will not be escaped.
		if(array_key_exists('array', $options) && !empty($options['array'])) {
			$separator = $options['array'];
			foreach($value as &$curval) {
				if( !(array_key_exists('noescape', $options)  &&  $options['noescape']) &&
		 		    (!array_key_exists('escape',   $options) ||  $options['escape']  )) {
					$curval = htmlentities($curvalue);
				}
			}
			$options['noescape'] = true;
			if(!is_array($value)) { return false; }
			$value = implode($separator, $value);
		}

		// If the switch option is set, instead of using the value as content, evaluate it
		// as true or false, to see which of two static given values to use.
		if(array_key_exists('switch',       $options) &&
		   array_key_exists('switch_true',  $options) &&
		   array_key_exists('switch_false', $options)     ) {
			if($value) {
				$value = $options['switch_true'];
			} else {
				$value = $options['switch_false'];
			} $options['noescape'] = true;
		}

		// Unless noescape option is set to true,
		// or escape option is set to false, escape value.
		if( !(array_key_exists('noescape', $options)  &&  $options['noescape']) &&
		     (!array_key_exists('escape',   $options) ||  $options['escape']  )) {
			$value = htmlentities($value);
		}

		// If trim is set, trim value to certain length.
		if(array_key_exists('trim', $options) && $options['trim'] !== true) {
			$value = substr(0, $options['trim']);
		}

		// If padding is set, pad value to that number of characters.
		// Default padding is whitespace. Use padvalue to set otherwise.
		if(array_key_exists('padding', $options) && $options['padding'] !== true) {
			$padlen = $options['padding'];
			$padval = array_key_exists('padvalue', $options) && !empty($options['padvalue']) ?
			                       $padvalue : ' ';
			$value = str_pad($value, $padlen, $padval);
		}

		return $value;
	}
}
