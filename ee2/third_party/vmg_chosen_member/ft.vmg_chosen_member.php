<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
require_once PATH_THIRD.'vmg_chosen_member/config.php';

/**
 * VMG Chosen Member Fieldtype Class
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2014 Vector Media Group, Inc.
 */
class Vmg_chosen_member_ft extends EE_Fieldtype
{
	public $info = array(
		'name' 			=> 'VMG Chosen Member',
		'version'		=> VMG_CM_VERSION,
	);

	public $chosen_helper;
	public $has_array_data = true;
	public $settings = array();
	public $ft_data = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Load our helper
		if ( ! class_exists('ChosenHelper') || ! is_a($this->chosen_helper, 'ChosenHelper')) {
			require_once PATH_THIRD.'vmg_chosen_member/helper.php';
			$this->chosen_helper = new ChosenHelper;
		}

		// Prep cache
		if ( ! isset(ee()->session->cache['vmg_chosen_member'])) {
			ee()->session->cache['vmg_chosen_member'] = array();
		}

		$this->cache =& ee()->session->cache['vmg_chosen_member'];
	}

	/**
	 * Display the field
	 */
	public function display_field($data)
	{
		// Define base variables
		$this->chosen_helper->initData($this);

		// Get JSON URL and append type if applicable
		$this->ft_data['json_url'] = $this->chosen_helper->actionId('get_results', true) . '&' . http_build_query(array(
			'field_id' => $this->ft_data['field_id'],
			'col_id' => $this->ft_data['col_id'],
			'var_id' => $this->ft_data['var_id'],
		));

		// Set unique identifier for this field
		$this->ft_data['unique_id'] = $this->ft_data['field_id'] . '_' . $this->ft_data['row_id'] . '_' . $this->ft_data['col_id'] . '_' . $this->ft_data['var_id'];

		// Get member association data
		$this->ft_data['member_associations'] = $this->chosen_helper->memberAssociations(
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

		// Include the CSS/JS automatically if in CP
		$current = ee()->functions->create_url($_SERVER['PHP_SELF']);
		$cp = ee()->functions->create_url(SYSDIR);
		if (strstr($current, $cp) !== false) {
			$this->chosen_helper->includeAssets();
		}

		ee()->load->add_package_path(PATH_THIRD . 'vmg_chosen_member/');

		$view = ee()->load->view('display_field', $this->ft_data, true);

		ee()->load->remove_package_path();

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
	public function display_var_field($data)
	{
		return $this->display_field($data);
	}

	/**
	 * Display Tag
	 */
	public function replace_tag($data, $params = array(), $tagdata = false)
	{
		$this->chosen_helper->initData($this);

		$prefix = isset($params['prefix']) ? $params['prefix'] : 'cm_';
		$backspace = isset($params['backspace']) ? $params['backspace'] : null;

		// Build settings array
		$settings = array(
			'allowed_groups' => $this->settings['allowed_groups'],
			'max_selections' => $this->settings['max_selections'],
			'search' => array(),
		);

		// Limit to specific members
		if ( ! empty($params['member_id'])) {
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

		// Limit
		if ( isset($params['limit']) && ! empty($params['limit'])) {
			$settings['limit'] = $params['limit'];
		}

		// Single tag simply returns a pipe delimited Member IDs
		if ( ! $tagdata) {

			// Get associations
			$members = $this->chosen_helper->memberAssociations(
				$this->ft_data['entry_id'],
				$this->ft_data['field_id'],
				$this->ft_data['col_id'],
				$this->ft_data['row_id'],
				$this->ft_data['var_id'],
				$settings,
				'm.member_id'
			);

			$members_list = array();
			foreach ($members AS $member) {
				$members_list[] = $member['member_id'];
			}

			return implode('|', $members_list);

		} else {

			$disable = ! empty($params['disable']) ? explode('|', $params['disable']) : array();

			// Get associations
			$results = $this->chosen_helper->memberAssociations(
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
			if ( ! in_array('member_data', $disable)) {
				$member_fields = $this->chosen_helper->getCustomMemberFields();

				foreach ($results AS $key => $member) {
					foreach ($member_fields AS $field) {
						$results[$key][$field['m_field_name']] = $member['m_field_id_' . $field['m_field_id']];
						unset($results[$key]['m_field_id_' . $field['m_field_id']]);
					}
				}
			}

			// Handle prefix if applicable
			$results = $this->chosen_helper->setPrefix($results, $prefix);

			$output = ee()->TMPL->parse_variables($tagdata, $results);

			// Handle backspace if applicable
			$output = $this->chosen_helper->backspace($output, $backspace);

			return $output;
		}

		return $data;
	}


	/**
	 * Total Members
	 */
	public function replace_total_members($data, $params = array(), $tagdata = false)
	{
		$this->chosen_helper->initData($this);

		// Determine number of associations
		$count_data = $this->chosen_helper->memberAssociations(
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
	public function display_var_tag($data, $params = array(), $tagdata = false)
	{
		$this->field_id = $params['var'];

		if (isset($params['method']) && $params['method'] == 'total_members') {
			return $this->replace_total_members($data, $params, $tagdata);
		}

		return $this->replace_tag($data, $params, $tagdata);
	}

	/**
	 * Display the fieldtype settings
	 */
	public function display_settings($data, $return_settings = false)
	{
		// Prep member all possible groups
		$groups = $this->chosen_helper->getMemberGroups();

		$member_groups = array();
		foreach ($groups AS $key => $value) {
			$member_groups[$value['group_id']] = $value['group_title'];
		}

		$search_fields = $this->chosen_helper->default_search_fields;

		// Get member custom field list
		$fields = $this->chosen_helper->getCustomMemberFields();
		foreach ($fields AS $key => $value) {
			$search_fields[$value['m_field_name']] = $value['m_field_label'];
		}

		// Build up the settings array
		$settings = array(
			array(
				'<strong>Allowed groups</strong>',
				form_multiselect('allowed_groups[]', $member_groups, ( ! empty($data['allowed_groups']) ? $data['allowed_groups'] : array()))
			),
			array(
				'<strong>Max selections allowed</strong><br/>Leave blank for no limit.',
				form_input('max_selections', ( ! empty($data['max_selections']) ? $data['max_selections'] : ''))
			),
			array(
				'<strong>Placeholder text</strong><br/>Displayed if <i>"Max selections allowed"</i> does not equal 1.',
				form_input(array('name' => 'placeholder_text', 'class' => 'fullfield'), ( ! empty($data['placeholder_text']) ? $data['placeholder_text'] : 'Begin typing a member\'s name...'))
			),
			array(
				'<strong>Search fields</strong><br/>Determines which member fields will be searched.<br/><i>Defaults to Username &amp; Screen Name if no selections are made.</i>',
				form_multiselect('search_fields[]', $search_fields, ( ! empty($data['search_fields']) ? $data['search_fields'] : array()))
			),
		);

		// Just return settings if this is matrix or low variable
		if ($return_settings) {
			return $settings;
		}

		// Return standard settings as table rows
		foreach ($settings as $setting) {
			ee()->table->add_row($setting[0], $setting[1]);
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
	public function display_var_settings($data)
	{
		return $this->display_settings($data, true);
	}

	/**
	 * Save the fieldtype settings
	 */
	public function save_settings($data)
	{
		$settings = array(
			'allowed_groups' => (isset($data['allowed_groups'])) ? $data['allowed_groups'] : ee()->input->post('allowed_groups'),
			'max_selections' => (isset($data['max_selections'])) ? $data['max_selections'] : ee()->input->post('max_selections'),
			'placeholder_text' => (isset($data['placeholder_text'])) ? $data['placeholder_text'] : ee()->input->post('placeholder_text'),
			'search_fields' => (isset($data['search_fields'])) ? $data['search_fields'] : ee()->input->post('search_fields'),
		);

		// Ensure search field defaults if no selections were made
		if ( ! is_array($settings['search_fields']) || empty($settings['search_fields'])) $settings['search_fields'] = array('username', 'screen_name');

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
	public function save($data)
	{
		// Save selections for later
		$this->chosen_helper->initData($this);
		$this->cache[$this->ft_data['cache_key']]['selections'] = $data;

		if (is_array($data)) {

			// Return list of valid Member IDs
			$member_ids = $this->chosen_helper->validateSelections($data, $this->settings);

			return implode('|', $member_ids);
		}

		return '';
	}

	/**
	* Save Cell
	*/
	public function save_cell($cell_data)
	{
		return $this->save($cell_data);
	}

	/**
	* Save variable data
	*/
	public function save_var_field($var_data)
	{
		return $this->save($var_data);
	}

	/**
	 * Post Save
	 */
	public function post_save($data)
	{
		$this->chosen_helper->initData($this);

		if (isset($this->cache[$this->ft_data['cache_key']]['selections']) && is_array($this->cache[$this->ft_data['cache_key']]['selections'])) {

			$selections = $this->cache[$this->ft_data['cache_key']]['selections'];

			// Return list of valid Member IDs
			$member_ids = $this->chosen_helper->validateSelections($selections, $this->ft_data);

			// Remove any old selections
			$this->chosen_helper->clearOldSelections($member_ids, $this->ft_data);

			// Save selections to database
			$this->chosen_helper->saveSelections($member_ids, $this->ft_data);

			// Remove from cache
			unset($this->cache[$this->ft_data['cache_key']]);
		}

		// Cleanup old records 10% of the time
		if (rand(1, 10) == 1) {
			$this->chosen_helper->cleanUp();
		}

		return true;
	}

	/**
	 * Post Save Cell
	 */
	public function post_save_cell($data)
	{
		return $this->post_save($data);
	}

	/**
	 * Post Save Var
	 */
	public function post_save_var($data)
	{
		return $this->post_save($data);
	}

	/**
	 * Better Workflow - Save
	 */
	public function draft_save($data, $draft_action)
	{
		$this->cache['is_draft'] = true;

		$this->save($data);
		return $this->post_save($data);
	}

	/**
	 * Better Workflow - Delete
	 */
	function draft_discard()
	{
		$this->chosen_helper->discardDraft($this->settings);

		return;
	}

	/**
	 * Better Workflow - Publish
	 */
	function draft_publish()
	{
		$this->chosen_helper->publishDraft($this->settings);

		return;
	}

}

/* End of file ft.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/ft.vmg_chosen_member.php */
