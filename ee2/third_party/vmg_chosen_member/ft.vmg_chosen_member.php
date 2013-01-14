<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Fieldtype Class
 *
 * @package		VMG Chosen Member
 * @version		1.6
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2013 Vector Media Group, Inc.
 */

class Vmg_chosen_member_ft extends EE_Fieldtype
{

	public $info = array(
		'name' 			=> 'VMG Chosen Member',
		'version'		=> '1.6',
	);

	public $chosen_helper;
	public $has_array_data = TRUE;
	public $settings = array();
	public $ft_data = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::EE_Fieldtype();

		// Load our helper
		if (! class_exists('Chosen_helper') || ! is_a($this->chosen_helper, 'Chosen_helper')) {

			require_once PATH_THIRD.'vmg_chosen_member/helper.php';
			$this->chosen_helper = new Chosen_helper;

		}
	}

	/**
	 * Display the field
	 */
	public function display_field($data)
	{
		// Define base variables
		$this->init_data();

		// Get JSON URL and append type if applicable
		$this->ft_data['json_url'] = $this->chosen_helper->action_id('get_results', true);
		if (isset($this->cell_name)) $this->ft_data['json_url'] .= '&type=matrix';
		elseif (isset($this->var_id)) $this->ft_data['json_url'] .= '&type=lowvar';

		// Set unique identifier for this field
		$this->ft_data['unique_id'] = $this->ft_data['field_id'] . '_' . $this->ft_data['row_id'] . '_' . $this->ft_data['col_id'];

		// Get member association data
		$this->ft_data['member_associations'] = $this->chosen_helper->member_associations(
			$this->ft_data['entry_id'],
			$this->ft_data['field_id'],
			$this->ft_data['col_id'],
			$this->ft_data['row_id'],
			$this->ft_data['var_id'],
			array(
				'allowed_groups' => $this->settings['allowed_groups'],
				'max_selections' => $this->settings['max_selections'],
			),
			'm.member_id, m.screen_name'
		);

		// Include the CSS/JS
		$this->chosen_helper->include_assets();

		$default_view_path = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = PATH_THIRD . 'vmg_chosen_member/views/';

		$view = $this->EE->load->view('display_field', $this->ft_data, TRUE);

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
		$this->init_data();

		// Single tag simply returns a pipe delimited Member IDs
		if ( ! $tagdata)
		{
			if ( ! isset($this->EE->session->cache['vmg_chosen_member']['single_' . $this->ft_data['cache_key']]))
			{
				// Get associations
				$members = $this->chosen_helper->member_associations(
					$this->ft_data['entry_id'],
					$this->ft_data['field_id'],
					$this->ft_data['col_id'],
					$this->ft_data['row_id'],
					$this->ft_data['var_id'],
					array(
						'allowed_groups' => $this->settings['allowed_groups'],
						'max_selections' => $this->settings['max_selections'],
					),
					'm.member_id'
				);

				$members_list = array();
				foreach ($members AS $member) {
					$members_list[] = $member['member_id'];
				}

				$this->EE->session->cache['vmg_chosen_member']['single_' . $this->ft_data['cache_key']] = implode('|', $members_list);
			}

			return $this->EE->session->cache['vmg_chosen_member']['single_' . $this->ft_data['cache_key']];
		}
		else
		{
			if ( ! isset($this->EE->session->cache['vmg_chosen_member']['pair_' . $this->ft_data['cache_key']])) {

				$disable = ! empty($params['disable']) ? explode('|', $params['disable']) : array();
				$prefix = isset($params['prefix']) ? $params['prefix'] : 'cm_';
				$backspace = isset($params['backspace']) ? $params['backspace'] : null;

				// Processing for Better Workflow support
				if (isset($this->EE->session->cache['ep_better_workflow']['is_draft']) && $this->EE->session->cache['ep_better_workflow']['is_draft']) {
					if (is_array($field_data)) $field_data = implode($field_data, '|');
				}

				$settings = array(
					'allowed_groups' => $this->settings['allowed_groups'],
					'max_selections' => $this->settings['max_selections'],
					'search' => array(),
				);

				// Limit to specific members
				if ( ! empty($member_search)) {
					$settings['search']['member_id'] = explode('|', $params['member_id']);
				}

				// Limit to specific member groups
				if ( ! empty($params['group_id'])) {
					$settings['search']['group_id'] = explode('|', $params['group_id']);
				}

				// Order by
				if ( isset($params['orderby']) && ! empty($params['orderby'])) {
					$settings['order_by'] = $params['orderby'];
				}

				// Sort
				if ( isset($params['sort']) && strtolower($params['sort']) == 'desc') {
					$settings['sort'] = 'desc';
				} else {
					$settings['sort'] = 'asc';
				}

				// Get associations
				$results = $this->chosen_helper->member_associations(
					$this->ft_data['entry_id'],
					$this->ft_data['field_id'],
					$this->ft_data['col_id'],
					$this->ft_data['row_id'],
					$this->ft_data['var_id'],
					$settings,
					'm.*' . ( ! in_array('member_data', $disable) ? ', md.*' : '')
				);

				// Return empty if no results
				if (empty($results)) {
					return '';
				}

				// Rename member data fields if we retrieved them
				if (! in_array('member_data', $disable))
				{
					$member_fields = $this->chosen_helper->get_custom_member_fields();

					foreach ($results AS $key => $member)
					{
						foreach ($member_fields AS $field)
						{
							$results[$key][$field['m_field_name']] = $member['m_field_id_' . $field['m_field_id']];
							unset($results[$key]['m_field_id_' . $field['m_field_id']]);
						}
					}
				}

				$this->EE->session->cache['vmg_chosen_member']['pair_' . $this->ft_data['cache_key']] = $results;
			}

			$results = $this->EE->session->cache['vmg_chosen_member']['pair_' . $this->ft_data['cache_key']];

			// Add prefix if set
			$results = $this->chosen_helper->set_prefix($results, $prefix);

			$output = $this->EE->TMPL->parse_variables($tagdata, $results);

			// Handle backspace if applicable
			$output = $this->chosen_helper->backspace($output, $backspace);

			return $output;
		}

		return $field_data;
	}


	/**
	 * Total Members
	 */
	function replace_total_members($field_data, $params = array(), $tagdata = FALSE)
	{
		$this->init_data();

		// Determine number of associations
		$count_data = $this->chosen_helper->member_associations(
			$this->ft_data['entry_id'],
			$this->ft_data['field_id'],
			$this->ft_data['col_id'],
			$this->ft_data['row_id'],
			$this->ft_data['var_id'],
			array(
				'allowed_groups' => $this->settings['allowed_groups'],
				'max_selections' => $this->settings['max_selections'],
			),
			'COUNT(m.member_id) AS count',
			null
		);

		if (empty($count_data)) return 0;

		return $count_data[0]['count'];
	}

	/**
	 * Display Variable Tag
	 */
	function display_var_tag($field_data, $params = array(), $tagdata = FALSE)
	{
		$this->field_id = $params['var'];

		if (isset($params['method']) && $params['method'] == 'total_members')
		{
			return $this->replace_total_members($field_data, $params, $tagdata);
		}

		return $this->replace_tag($field_data, $params, $tagdata);
	}

	/**
	 * Display the fieldtype settings
	 */
	public function display_settings($data, $return_settings = FALSE)
	{
		// Prep member all possible groups
		$groups = $this->chosen_helper->get_member_groups();

		$member_groups = array();
		foreach ($groups AS $key => $value) {
			$member_groups[$value['group_id']] = $value['group_title'];
		}

		$search_fields = $this->chosen_helper->default_search_fields;

		// Get member custom field list
		$fields = $this->chosen_helper->get_custom_member_fields();
		foreach ($fields AS $key => $value) {
			$search_fields[$value['m_field_name']] = $value['m_field_label'];
		}

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
			array(
				'<strong>Search fields</strong><br/>Determines which member fields will be searched.<br/><i>Defaults to Username &amp; Screen Name if no selections are made.</i>',
				form_multiselect('search_fields[]', $search_fields, (!empty($data['search_fields']) ? $data['search_fields'] : array()))
			),
		);

		// Just return settings if this is matrix or low variable
		if ($return_settings) {
			return $settings;
		}

		// Return standard settings as table rows
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
			'search_fields' => (isset($data['search_fields'])) ? $data['search_fields'] : $this->EE->input->post('search_fields'),
		);

		// Ensure search field defaults if no selections were made
		if (! is_array($settings['search_fields']) || empty($settings['search_fields'])) $settings['search_fields'] = array('username', 'screen_name');

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
		$this->init_data();

		if (is_array($field_data)) {

			// Return list of valid Member IDs
			$member_ids = $this->chosen_helper->validate_selections($field_data, $this->ft_data);

			// Remove any old selections that are no longer selected
			$this->chosen_helper->clear_old_selections($member_ids, $this->ft_data);

			// Save selections to database
			$this->chosen_helper->save_selections($member_ids, $this->ft_data);

			return implode('|', $member_ids);
		}

		return '';
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

	/**
     * Build base fieldtype data array
     */
    protected function init_data()
    {
    	if (is_array($this->ft_data) && ! empty($this->ft_data)) {
    		return true;
    	}

        $this->ft_data = array(
            'entry_id' => (isset($this->row['entry_id']) ? $this->row['entry_id'] : $this->EE->input->get_post('entry_id')),
            'field_name' => (isset($this->cell_name) ? $this->cell_name : $this->field_name),
            'field_id' => (isset($this->field_id) ? $this->field_id : 0),
            'row_id' => (isset($this->row_id) ? $this->row_id : 0),
            'col_id' => (isset($this->col_id) ? $this->col_id : 0),
            'var_id' => (isset($this->var_id) ? $this->var_id : 0),
            'allowed_groups' => (isset($this->settings['allowed_groups']) ? $this->settings['allowed_groups'] : null),
            'max_selections' => (isset($this->settings['max_selections']) ? $this->settings['max_selections'] : null),
            'placeholder_text' => (isset($this->settings['placeholder_text']) ? $this->settings['placeholder_text'] : null),
            'search_fields' => (isset($this->settings['search_fields']) ? $this->settings['search_fields'] : null),
        );

		$this->ft_data['cache_key'] = md5("{$this->ft_data['entry_id']}_{$this->ft_data['field_id']}_{$this->ft_data['row_id']}_{$this->ft_data['col_id']}_{$this->ft_data['var_id']}");

		return true;
    }
}

/* End of file ft.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/ft.vmg_chosen_member.php */
