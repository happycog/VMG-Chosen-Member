<?php

/**
 * VMG Chosen Member Config
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2014 Vector Media Group, Inc.
 */

if ( ! defined('VMG_CM_VERSION'))
{
	define('VMG_CM_VERSION', '1.6');
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
