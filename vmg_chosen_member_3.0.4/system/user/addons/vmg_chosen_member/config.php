<?php

/**
 * VMG Chosen Member Config
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2016 Vector Media Group, Inc.
 */

if ( ! defined('VMG_CM_VERSION'))
{
	define('VMG_CM_VERSION', '3.0.4');
}

/**
 * < EE 2.6.0 backward compat
 */
if ( ! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}

if ( ! defined('VMG_JS_TYPE'))
{
	// options available for js are: ['chosen','select2']
	define('VMG_JS_TYPE', 'select2');

	// Format of data to display on Tag (Selected options)
	define('VMG_TAG_TYPE', '{full_name} - (Rating: {player_rating})');

	// Format of data to display on select options
	define('VMG_OPTION_TYPE', '{full_name} - (Rating: {player_rating})');

}