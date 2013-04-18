<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Module Class
 *
 * @package		VMG Chosen Member
 * @version		1.6
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2013 Vector Media Group, Inc.
 */
class Vmg_chosen_member {

	public $return_data;
	public $chosen_helper;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		// Load our helper
		if (! class_exists('ChosenHelper') || ! is_a($this->chosen_helper, 'ChosenHelper')) {
			require_once PATH_THIRD.'vmg_chosen_member/helper.php';
			$this->chosen_helper = new ChosenHelper;
		}
	}

	/**
	 * Return JSON results for member autocomplete
	 */
	public function get_results()
	{
		$result = array();
		$field_id = $this->EE->input->get('field_id');
		$col_id = $this->EE->input->get('col_id');
		$var_id = $this->EE->input->get('var_id');
		$query = $this->EE->db->escape_like_str(strtolower($this->EE->input->post('query')));

		// Retrieve settings for this field
		$settings = $this->chosen_helper->fieldSettings($field_id, $col_id, $var_id);

		if ($settings !== false && $this->EE->input->is_ajax_request())
		{
			$search_fields = $search_fields_where = $custom_search_fields = $custom_field_map = array();
			if (empty($settings['search_fields'])) $settings['search_fields'] = array('username', 'screen_name');

			// Get member custom field list
			foreach ($this->chosen_helper->customMemberFields() AS $key => $value) {
				$custom_search_fields[$value['m_field_name']] = $value['m_field_label'];
				$custom_field_map[$value['m_field_name']] = 'm_field_id_' . $value['m_field_id'];
			}

			foreach ($settings['search_fields'] AS $field) {
				if (array_key_exists($field, $this->chosen_helper->default_search_fields) || array_key_exists($field, $custom_search_fields)) {

					if (array_key_exists($field, $custom_search_fields)) $field = $custom_field_map[$field];

					$search_fields[] = $field;
					$search_fields_where[] = "LOWER({$field}) LIKE '%" . $query . "%'";
				}
			}

			// Gather and format results
			$results = $this->chosen_helper->memberAutoComplete($settings, $search_fields, $search_fields_where);

			foreach ($results AS $member) {
				$additional_text = '';

				// Add "additional" text for default fields
				foreach ($this->chosen_helper->default_search_fields AS $field_id => $field_label) {
					if (isset($member[$field_id]) && empty($additional_text) && ! in_array($field_id, array('username', 'screen_name')) && strpos($member[$field_id], $query) !== false) {
						$additional_text = '&nbsp;&nbsp;&nbsp;(' . $field_label . ': ' . $this->chosen_helper->cleanFieldPreview($member[$field_id], $query) . ')';
					}
				}

				// Add "additional" text for custom fields
				if (empty($additional_text)) {
					foreach ($custom_search_fields AS $field_id => $field_label) {

						if (isset($member[$custom_field_map[$field_id]]) && empty($additional_text) && strpos($member[$custom_field_map[$field_id]], $query) !== false) {
							$additional_text = '&nbsp;&nbsp;&nbsp;(' . $field_label . ': ' . $this->chosen_helper->cleanFieldPreview($member[$custom_field_map[$field_id]], $query) . ')';
						}

					}
				}

				$result[] = array(
					'value' => $member['member_id'],
					'text' => $member['screen_name'] . $additional_text,
				);
			}
		}

		$this->EE->load->library('javascript');
		exit($this->EE->javascript->generate_json($result, true));
	}

	/**
	 * Return Channel Entries that a user has been selected in
	 */
	public function assoc_entries()
	{
		// Assist parsing of global variables as parameters
		foreach ($this->EE->TMPL->tagparams AS $key => $val)
		{
			$this->EE->TMPL->tagparams[$key] = $this->EE->TMPL->parse_globals($val);
		}

		$prefix = $this->EE->TMPL->fetch_param('prefix', 'cm_');
		$field = $this->EE->TMPL->fetch_param('field');
		$col = $this->EE->TMPL->fetch_param('col');
		$member_id = $this->EE->TMPL->fetch_param('member_id', '');
		$display_entries = $this->EE->TMPL->fetch_param('display_entries', 'yes');

		$field_data = $this->chosen_helper->convertFieldName($field, $col);
		$entries = $this->chosen_helper->associatedChannelEntries(
			$field_data['field_id'],
			$field_data['col_id'],
			explode('|', $member_id)
		);

		if (empty($entries)) return $this->EE->TMPL->no_results();

		// Trick EE in to thinking this is a Channel Entries Loop
		if ($display_entries == 'yes') {

			$entry_id_param = (! $this->EE->TMPL->fetch_param('orderby')) ? 'fixed_order' : 'entry_id';
			$this->EE->TMPL->tagparams[$entry_id_param] = '0|' . implode('|', $entries);
			$this->EE->TMPL->tagparams['dynamic'] = 'no';

			if (! isset($this->EE->TMPL->tagparams['disable'])) {
				$this->EE->TMPL->tagparams['disable'] = 'categories|category_fields|member_data|pagination';
			}

			$vars = $this->EE->functions->assign_variables($this->EE->TMPL->tagdata);
			$this->EE->TMPL->var_single = $vars['var_single'];
			$this->EE->TMPL->var_pair = $vars['var_pair'];

			if (method_exists($this->EE->TMPL, '_fetch_site_ids')) {
				$this->EE->TMPL->_fetch_site_ids();
			}

			if (! class_exists('Channel')) {
				require PATH_MOD.'channel/mod.channel.php';
			}

			$channel = new Channel();
    		return $channel->entries();
		}

		$results = array(
			$prefix . 'entry_ids' => implode('|', array_unique($entries))
		);

		if (empty($this->EE->TMPL->tagdata)) return $results[$prefix . 'entry_ids'];

		return $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $results);
	}

	/**
	 * Return all members selected through a specific field
	 */
	public function assoc_field_members()
	{
		$prefix = $this->EE->TMPL->fetch_param('prefix', 'cm_');
		$field = $this->EE->TMPL->fetch_param('field');
		$col = $this->EE->TMPL->fetch_param('col');
		$backspace = $this->EE->TMPL->fetch_param('backspace');
		$disable = $this->EE->TMPL->fetch_param('disable');

		$disable = ! empty($params['disable']) ? explode('|', $params['disable']) : array();
		$field_data = $this->chosen_helper->convertFieldName($field, $col);

		// Bail if field couldn't be found
		if (empty($field_data['field_id']) || empty($field_data['field_name'])) {
			return $this->EE->TMPL->no_results();
		}

		$settings = $this->chosen_helper->fieldSettings($field_data['field_id'], $field_data['col_id']);

		// Get associations
		$results = $this->chosen_helper->memberAssociations(
			null,
			$field_data['field_id'],
			$field_data['col_id'],
			null,
			null,
			$settings,
			'm.*' . (! in_array('member_data', $disable) ? ', md.*' : '')
		);

		if (empty($results)) return $this->EE->TMPL->no_results();

		// Rename member data fields if we retrieved them
		if (! in_array('member_data', $disable)) {
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

		$output = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $results);

		// Handle backspace if applicable
		$output = $this->chosen_helper->backspace($output, $backspace);

		return $output;
	}

}

/* End of file mod.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mod.vmg_chosen_member.php */
