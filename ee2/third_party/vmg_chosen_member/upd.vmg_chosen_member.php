<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
require_once PATH_THIRD.'vmg_chosen_member/config.php';

/**
 * VMG Chosen Member Update Class
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2014 Vector Media Group, Inc.
 */
class Vmg_chosen_member_upd
{
	public $version = VMG_CM_VERSION;

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	true
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> 'Vmg_chosen_member',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> "n",
			'has_publish_fields'	=> 'n'
		);

		ee()->db->insert('modules', $mod_data);

		// Install Actions
		ee()->db->insert('actions', array(
			'class' => 'Vmg_chosen_member',
			'method' => 'get_results'
		));

		$this->create_table();

		return true;
	}

	/**
	 * Module Updater
	 *
	 * @return 	boolean 	true
	 */
	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return false;
		}

		if (version_compare($current, '1.6', '<'))
		{
			// We now store our data in its own table
			$this->create_table();

			// Convert data from standard fields
			$fields = ee()->db->select('field_id')
				->from('channel_fields')
				->where('field_type', 'vmg_chosen_member')
				->get()
				->result_array();

			foreach ($fields AS $field) {
				$entries = ee()->db->select("entry_id, '".$field['field_id']."' AS field_id, field_id_".$field['field_id']." AS member_ids", false)
					->from('channel_data')
					->where('field_id_' . $field['field_id'] . ' !=', '')
					->get()
					->result_array();

				foreach ($entries AS $entry) {
					$this->save_association($entry);
				}
			}

			// Convert data from matrix fields
			if (ee()->db->table_exists('matrix_cols')) {
				$fields = ee()->db->select('col_id, field_id, var_id')
					->from('matrix_cols')
					->where('col_type', 'vmg_chosen_member')
					->get()
					->result_array();

				foreach ($fields AS $field) {
					$entries = ee()->db->select("entry_id, '".$field['field_id']."' AS field_id, '".$field['col_id']."' AS col_id, '".$field['var_id']."' AS var_id, row_id, col_id_".$field['col_id']." AS member_ids", false)
						->from('matrix_data')
						->where('col_id_' . $field['col_id'] . ' !=', '')
						->get()
						->result_array();

					foreach ($entries AS $entry) {
						$this->save_association($entry);
					}
				}
			}

			// Convert data from low variable fields
			if (ee()->db->table_exists('low_variables')) {
				$entries = ee()->db->select("lv.variable_id AS var_id, gv.variable_data AS member_ids", false)
					->from('low_variables AS lv')
					->join('global_variables AS gv', 'gv.variable_id = lv.variable_id', 'inner')
					->where('lv.variable_type', 'vmg_chosen_member')
					->where('gv.variable_data !=', '')
					->get()
					->result_array();

				foreach ($entries AS $entry) {
					$this->save_association($entry);
				}
			}
		}

		// Update ft data
		ee()->db->where('name', 'vmg_chosen_member')
			->update('fieldtypes', array('version' => $this->version));

		return true;
	}

	private function create_table()
	{
		ee()->load->dbforge();

		ee()->dbforge->add_field(array(
			'entry_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0, 'null' => false),
			'field_id' => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'default' => 0, 'null' => false),
			'col_id' => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'default' => 0, 'null' => false),
			'row_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0, 'null' => false),
			'var_id' => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'default' => 0, 'null' => false),
			'member_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0, 'null' => false),
			'order' => array('type' => 'int', 'constraint' => 4, 'unsigned' => true, 'default' => 0, 'null' => false),
			'is_draft' => array('type' => 'TINYINT', 'constraint' => '1', 'unsigned' => TRUE, 'default' => 0),
		));

		ee()->dbforge->create_table('vmg_chosen_member', true);

		ee()->db->query("ALTER TABLE " . ee()->db->dbprefix . "vmg_chosen_member ADD UNIQUE KEY `unique_all` (`entry_id`, `field_id`, `col_id`, `row_id`, `var_id`, `member_id`, `is_draft`)");

		return true;
	}

	private function save_association($entry)
	{
		$member_ids = explode('|', $entry['member_ids']);
		unset($entry['member_ids']);

		$order = 0;
		foreach ($member_ids AS $member_id) {
			ee()->db->set('member_id', $member_id)
				->set('order', $order++)
				->insert('vmg_chosen_member', $entry);
		}
	}

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	true
	 */
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
			->get_where('modules', array(
				'module_name'	=> 'Vmg_chosen_member'
			))
			->row('module_id');

		ee()->db->where('module_id', $mod_id)
			->delete('module_member_groups');

		ee()->db->where('module_name', 'Vmg_chosen_member')
			->delete('modules');

		ee()->db->where('class', 'Vmg_chosen_member')
			->delete('actions');

		return true;
	}

}

/* End of file upd.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/upd.vmg_chosen_member.php */
