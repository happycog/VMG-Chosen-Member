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
		
		exit(htmlspecialchars(json_encode($result), ENT_NOQUOTES));
	}
	
}
/* End of file mod.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mod.vmg_chosen_member.php */