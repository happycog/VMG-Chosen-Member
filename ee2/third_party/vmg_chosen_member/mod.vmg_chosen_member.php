<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Module Class
 * 
 * @package		VMG Chosen Member
 * @version		0.8
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011 Vector Media Group, Inc.
 **/

class Vmg_chosen_member {
	
	public $return_data;

	private $default_search_fields = array(
		'username' => 'Username', 'screen_name' => 'Screen Name', 'email' => 'Email', 'url' => 'URL',
		'location' => 'Location', 'occupation' => 'Occupation', 'interests' => 'Interests', 
		'aol_im' => 'AOL IM', 'yahoo_im' => 'Yahoo! IM', 'msn_im' => 'MSN IM', 'icq' => 'ICQ',
		'bio' => 'Bio', 'signature' => 'Signature',
	);
	
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

			$search_fields = $custom_search_fields = array();
			if (empty($settings['search_fields'])) $settings['search_fields'] = array('username', 'screen_name');

			// Get member custom field list
			$db->select("m_field_name, m_field_label");
			$db->from('exp_member_fields');
			$db->order_by('m_field_order', 'asc');
			$fields = $db->get()->result_array();

			foreach ($fields AS $key => $value) $custom_search_fields[] = $value['m_field_name'];

			foreach ($settings['search_fields'] AS $field)
			{
				if (array_key_exists($field, $this->default_search_fields) || in_array($field, $custom_search_fields))
				{
					$search_fields[] = "LOWER({$field}) LIKE '%" . $query . "%'";
				}
			}
			
			// Gather and format results
			$db->select('member_id, screen_name, ' . implode($settings['search_fields'], ', '));
			$db->from('exp_members');
			$db->where_in('group_id', $settings['allowed_groups']);
			$db->where('(' . implode(' OR ', $search_fields) . ')');
			$db->limit(50);
			$results = $db->get()->result_array();

			foreach ($results AS $member)
			{
				$additional_text = '';

				foreach ($this->default_search_fields AS $field_id => $field_label)
				{
					if (isset($member[$field_id]) && empty($additional_text) && !in_array($field_id, array('username', 'screen_name')) && strpos($member[$field_id], $query) !== FALSE)
					{
						$additional_text = '&nbsp;&nbsp;&nbsp;(' . $field_label . ': ' . $member[$field_id] . ')';
					}
				}

				$result[] = array(
					'value' => $member['member_id'],
					'text' => $member['screen_name'] . $additional_text,
				);
			}
		}

		$this->EE->load->library('javascript');
		
		exit($this->EE->javascript->generate_json($result, TRUE));
	}
	
}
/* End of file mod.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mod.vmg_chosen_member.php */