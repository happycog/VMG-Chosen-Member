<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Module Class
 * 
 * @package		VMG Chosen Member
 * @version		1.2
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011 Vector Media Group, Inc.
 **/

class Vmg_chosen_member {
	
	public $return_data;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ----------------------------------------------------------------
	
	function get_results()
	{
		$db = $this->EE->db;
		$result = array();
		
		$field_id = $this->EE->input->get('field_id');
		$is_matrix = ($this->EE->input->get('type') == 'matrix' ? true : false);
		$is_low_var = ($this->EE->input->get('type') == 'lowvar' ? true : false);
		$query = $db->escape_like_str(strtolower($this->EE->input->post('query')));

		if ($is_matrix)
		{
			// Check/Get field/col settings
			$db->select('mc.col_settings AS setting_data', false);
			$db->from('exp_matrix_cols AS mc');
			$db->where('mc.field_id', $field_id);
			$db->where('mc.col_type', 'vmg_chosen_member');
			$settings = $db->get()->row_array();
		}
		elseif ($is_low_var)
		{
			// Check/Get var settings
			$db->select('lv.variable_type, lv.variable_settings AS setting_data');
			$db->from('exp_low_variables AS lv');
			$db->where('lv.variable_id', $field_id);
			$db->where('lv.variable_type', 'vmg_chosen_member');
			$settings = $db->get()->row_array();
		}
		else
		{
			// Check/Get field/col settings
			$db->select('cf.field_settings AS setting_data', false);
			$db->from('exp_channel_fields AS cf');
			$db->where('cf.field_id', $field_id);
			$db->where('cf.field_type', 'vmg_chosen_member');
			$settings = $db->get()->row_array();
		}
		
		if (!empty($settings) && $this->EE->input->is_ajax_request())
		{
			if (!$is_low_var) $settings = unserialize(base64_decode($settings['setting_data']));
			else
			{
				$settings['setting_data'] = unserialize($settings['setting_data']);
				$settings = $settings['setting_data'][$settings['variable_type']];
			}
			
			// Gather and format results
			$db->select('member_id, screen_name');
			$db->from('exp_members');
			$db->where_in('group_id', $settings['allowed_groups']);
			$db->where("(LOWER(screen_name) LIKE '%" . $query . "%' OR LOWER(username) LIKE '%" . $query . "%')");
			$db->limit(20);
			$results = $db->get()->result_array();

			foreach ($results AS $member)
			{
				$result[] = array(
					'value' => $member['member_id'],
					'text' => $member['screen_name'],
				);
			}
		}
		
		exit($this->generate_json($result, TRUE));
	}

	/**
	 * Function to generate JSON output for use with PHP earlier than 5.2
	 **/
	function generate_json($result = NULL, $match_array_type = FALSE)
	{
		// JSON data can optionally be passed to this function
		// either as a database result object or an array, or a user supplied array
		if ( ! is_null($result))
		{
			if (is_object($result))
			{
				$json_result = $result->result_array();
			}
			elseif (is_array($result))
			{
				$json_result = $result;
			}
			else
			{
				return $this->prep_args($result);
			}
		}
		else return 'null';

		$json = array();
		$_is_assoc = TRUE;

		if (!is_array($json_result) && empty($json_result))
		{
			show_error("Generate JSON Failed - Illegal key, value pair.");
		}
		elseif ($match_array_type)
		{
			$_is_assoc = $this->is_associative_array($json_result);
		}

		foreach ($json_result as $k => $v)
		{
			if ($_is_assoc)
			{
				$json[] = $this->prep_args($k, TRUE) . ':' . $this->generate_json($v, $match_array_type);
			}
			else
			{
				$json[] = $this->generate_json($v, $match_array_type);
			}
		}

		$json = implode(',', $json);

		return $_is_assoc ? "{" . $json . "}" : "[" . $json . "]";
	}

	function prep_args($result, $is_key = FALSE)
	{
		if (is_null($result))
		{
			return 'null';
		}
		elseif (is_bool($result))
		{
			return ($result === TRUE) ? 'true' : 'false';
		}
		elseif (is_string($result) OR $is_key)
		{
			return '"' . str_replace(array('\\', "\t", "\n", "\r", '"', '/'), array('\\\\', '\\t', '\\n', "\\r", '\"', '\/'), $result) . '"';			
			
		}
		elseif (is_scalar($result))
		{
			return $result;
		}
	}

	function is_associative_array($arr)
	{
		foreach (array_keys($arr) as $key => $val)
		{
			if ($key !== $val)
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
}
/* End of file mod.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mod.vmg_chosen_member.php */