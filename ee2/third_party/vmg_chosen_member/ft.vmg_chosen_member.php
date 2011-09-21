<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Fieldtype Class
 * 
 * @package		VMG Chosen Member
 * @version		1.2
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011 Vector Media Group, Inc.
 **/

class Vmg_chosen_member_ft extends EE_Fieldtype
{
	
	/* --------------------------------------------------------------
	 * VARIABLES
	 * ------------------------------------------------------------ */
	public $info = array(
		'name' 			=> 'VMG Chosen Member',
		'version'		=> '1.2',
	);
	
	public $has_array_data = TRUE;
	public $settings = array();
	
	/* --------------------------------------------------------------
	 * GENERIC METHODS
	 * ------------------------------------------------------------ */
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::EE_Fieldtype();
	}
	
	/* --------------------------------------------------------------
	 * FIELDTYPE API
	 * ------------------------------------------------------------ */
	
	/**
	 * Display the field
	 */
	public function display_field($data)
	{
		$db = $this->EE->db;
		$populate = $selections = $member_data = array();
		
		// Generate values for pre-populated fields
		$selections = explode('|', $data);
		if (is_array($selections) && !empty($selections))
		{
			$db->select("member_id, screen_name");
			$db->from('exp_members');
			$db->where_in('member_id', $selections);
			if (!empty($this->settings['allowed_groups'])) $db->where_in('group_id', $this->settings['allowed_groups']);
			$member_data = $db->get()->result_array();
		}
		
		// Get ajax action id
		$action = $db->select('action_id')->from('exp_actions')->where('class', 'Vmg_chosen_member')->where('method', 'get_results')->get()->row_array();
		
		// Build data for view
		$vars = array(
			'member_data' => $member_data,
			'json_url' => $this->EE->functions->create_url('?ACT=' . (empty($action) ? '' : $action['action_id'])),
		);
		
		// Merge settings in the vars array
		$vars += array(
			'field_id' => (isset($this->var_id) ? $this->var_id : $this->field_id),
			'field_name' => (isset($this->row_id) ? $this->cell_name : $this->field_name),
			'row_id' => (isset($this->row_id) ? $this->row_id : 0),
			'col_id' => (isset($this->col_id) ? $this->col_id : 0),
			'max_selections' => $this->settings['max_selections'],
			'placeholder_text' => $this->settings['placeholder_text'],
			'is_low_var' => (isset($this->var_id) ? true : false),
		);
		
		if (!isset($this->EE->session->cache['vmg_chosen_member']['assets_included']))
		{
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->functions->create_url('themes/third_party/vmg_chosen_member/chosen/chosen.css') . '" />');
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->functions->create_url('themes/third_party/vmg_chosen_member/vmg_chosen_member.css') . '" />');
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->EE->functions->create_url('themes/third_party/vmg_chosen_member/chosen/chosen.jquery.js') . '"></script>');
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->EE->functions->create_url('themes/third_party/vmg_chosen_member/vmg_chosen_member.js') . '"></script>');

			$this->EE->session->cache['vmg_chosen_member']['assets_included'] = true;
		}
		
		$default_view_path = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = PATH_THIRD . 'vmg_chosen_member/views/';
		
		$view = $this->EE->load->view('display_field', $vars, TRUE);
		
		$this->EE->load->_ci_view_path = $default_view_path;
		
		return $view;
	}
	
	/**
	 * Display the field (as cell)
	 */
	public function display_cell($data)
	{
		return $this->display_field($data);
	}

	/**
	 * Display Variable Field
	 */
	function display_var_field($data)
	{
		return $this->display_field($data);
	}
	
	/**
     * Display Tag
     */
    function replace_tag($field_data, $params = array(), $tagdata = FALSE)
	{
		$db = $this->EE->db;
		$disable = (!empty($params['disable']) ? explode('|', $params['disable']) : array());
		
		// Single tag simply returns member list (pipe delimited)
		if (!$tagdata)
        {
            return $field_data;
    	}
    	else
		{
			if (!isset($this->EE->session->cache['vmg_chosen_member'][$this->settings['field_name'] . '_' . $field_data]))
			{
				$members = explode('|', $field_data);

				// Gather user data
				$db->select('m.*' . (!in_array('member_data', $disable) ? ', md.*' : ''));
				$db->from('exp_members AS m');
				if (!in_array('member_data', $disable)) $db->join('exp_member_data AS md', 'md.member_id = m.member_id', 'left');
				$db->where_in('m.member_id', $members);
				if (!empty($this->settings['allowed_groups'])) $db->where_in('m.group_id', $this->settings['allowed_groups']);
				if (!empty($params['group_id'])) $db->where_in('m.group_id', explode('|', $params['group_id']));
				if (!empty($params['orderby']))
				{
					if ($params['sort']) $sort = $params['sort'];
					else $sort = 'asc';
					
					$db->order_by($params['orderby'], $sort); 
				}
				elseif (!empty($params['sort'])) $db->order_by('m.member_id', $params['sort']);
				if (!empty($params['limit']) && is_numeric($params['limit'])) $db->limit($params['limit']);
				$results = $db->get()->result_array();
				
				if (empty($results)) return $this->EE->TMPL->no_results();

				// Rename member data fields if we retrieved them
				if (!in_array('member_data', $disable))
				{
					$member_fields = $db->select('m_field_id, m_field_name')->from('exp_member_fields')->get()->result_array();

					foreach ($results AS $key => $member)
					{
						foreach ($member_fields AS $field)
						{
							$results[$key][$field['m_field_name']] = $member['m_field_id_' . $field['m_field_id']];
							unset($results[$key]['m_field_id_' . $field['m_field_id']]);
						}
					}
				}

				$this->EE->session->cache['vmg_chosen_member'][$this->settings['field_name'] . '_' . $field_data] = $results;
			}
			else $results = $this->EE->session->cache['vmg_chosen_member'][$this->settings['field_name'] . '_' . $field_data];
			
			// Add prefix if set
			if (!empty($params['prefix']))
			{

				foreach ($results AS $key => $member)
				{
					foreach ($member AS $item => $value)
					{
						$results[$key][$params['prefix'] . $item] = $value;
						unset($results[$key][$item]);
					}
				}
			}

            $output = $this->EE->TMPL->parse_variables($tagdata, $results);
            
            return $output;
    	}
    }


    /**
	 * Total Members
	 */
	function replace_total_members($field_data, $params = array(), $tagdata = FALSE)
	{
		$db = $this->EE->db;

		// Determine number of results if not cached already
		if (!isset($this->EE->session->cache['vmg_chosen_member'][$this->settings['field_name'] . '_' . $field_data]))
		{
			$members = explode('|', $field_data);

			$db->from('exp_members AS m');
			$db->where_in('m.member_id', $members);
			$results = $db->get()->num_rows();

			return $results;
		}
		
		return count($this->EE->session->cache['vmg_chosen_member'][$this->settings['field_name'] . '_' . $field_data]);
	}

	/**
	 * Display Variable Tag
	 */
	function display_var_tag($field_data, $params = array(), $tagdata = FALSE)
	{
		$this->settings['field_name'] = $params['var'];

		if ($params['method'] == 'total_members')
		{
			return $this->replace_total_members($field_data, $params, $tagdata);
		}

		return $this->replace_tag($field_data, $params, $tagdata);
	}
	
	/**
	 * Display the fieldtype settings
	 */
	public function display_settings($data, $matrix = FALSE)
	{
		$db = $this->EE->db;
		
		// Get member groups for current site
		$db->select("group_id, group_title");
		$db->from('exp_member_groups');
		$db->where('site_id', $this->EE->config->config['site_id']);
		$groups = $db->get()->result_array();
		
		$member_groups = array();
		foreach ($groups AS $key => $value) $member_groups[$value['group_id']] = $value['group_title'];
		
		// Build up the settings array
		$settings = array(
			array(
				'<strong>Allowed groups</strong>', 
				form_multiselect('allowed_groups[]', $member_groups, (!empty($data['allowed_groups']) ? $data['allowed_groups'] : array()))
			),
			array(
				'<strong>Max selections allowed</strong><br/>Leave blank for no limit.', 
				form_input('max_selections', (!empty($data['max_selections']) ? $data['max_selections'] : ''))
			),
			array(
				'<strong>Placeholder text</strong><br/>Displayed if <i>"Max selections allowed"</i> does not equal 1.', 
				form_input(array('name' => 'placeholder_text', 'class' => 'fullfield'), (!empty($data['placeholder_text']) ? $data['placeholder_text'] : 'Begin typing a member\'s name...'))
			),
		);
		
		// Just return if this is in a matrix
		if ($matrix) return $settings;
		
		// Not in matrix, so build table rows
		foreach ($settings as $setting) {
			$this->EE->table->add_row($setting[0], $setting[1]);
		}
	}
	
	/**
	 * Display the fieldtype cell settings
	 */
	public function display_cell_settings($data)
	{
		return $this->display_settings($data, true);
	}

	/**
	 * Display Variable Settings
	 */
	function display_var_settings($data)
	{
		return $this->display_settings($data, true);
	}
	
	/**
	 * Save the fieldtype settings
	 */
	public function save_settings($data)
	{
		$settings = array(
			'allowed_groups' => (isset($data['allowed_groups'])) ? $data['allowed_groups'] : $this->EE->input->post('allowed_groups'),
			'max_selections' => (isset($data['max_selections'])) ? $data['max_selections'] : $this->EE->input->post('max_selections'),
			'placeholder_text' => (isset($data['placeholder_text'])) ? $data['placeholder_text'] : $this->EE->input->post('placeholder_text'),
		);
		
		return $settings;
	}
	
	/**
	 * Save the fieldtype cell settings
	 */
	public function save_cell_settings($data)
	{
		return $this->save_settings($data);
	}

	/**
	 * Save the variable settings
	 */
	public function save_var_settings($data)
	{
		return $this->save_settings($data);
	}
	
	/**
     * Save Field
     */
    function save($field_data)
    {
		$db = $this->EE->db;
		$result_data = '';
		
		if (is_array($field_data))
		{
			// Validate member groups before saving
			$db->select('member_id, group_id');
			$db->from('exp_members');
			$db->where_in('member_id', $field_data);
			$results = $db->get()->result_array();
			
			foreach ($results AS $key => $member)
			{
				if (in_array($member['group_id'], $this->settings['allowed_groups'])) $result_data[$key] = $member['member_id'];
			}
			
			// Enforce max selections if applicable
			if ($this->settings['max_selections'] > 0)
			{
				while (count($result_data) > $this->settings['max_selections']) array_pop($result_data);
			}
			
			$result_data = implode('|', $result_data);
		}
		
    	return $result_data;
    }
    
    
    /**
     * Save Cell
     */
    function save_cell($cell_data)
    {
        return $this->save($cell_data);
    }

    /**
     * Save variable data
     */
    function save_var_field($var_data)
    {
    	return $this->save($var_data);
    }
}