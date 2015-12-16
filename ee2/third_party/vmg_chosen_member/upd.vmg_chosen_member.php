<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
require_once PATH_THIRD.'vmg_chosen_member/config.php';

/**
 * VMG Chosen Member Update Class
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2015 Vector Media Group, Inc.
 */
class Vmg_chosen_member_upd
{
	public $version = VMG_CM_VERSION;
	public $chosen_helper;

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
			'has_cp_backend'		=> 'y',
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

		// Load our helper
		if ( ! class_exists('ChosenHelper') || ! is_a($this->chosen_helper, 'ChosenHelper')) {
			require_once PATH_THIRD.'vmg_chosen_member/helper.php';
			$this->chosen_helper = new ChosenHelper;
		}

		if (version_compare($current, '2.0', '<'))
		{
			// We now store our data in its own table
			$this->create_table();

			// Convert data from standard fields
			$this->chosen_helper->convertStandardFieldData();
		}

		if (version_compare($current, '2.2', '<'))
		{
			ee()->db->where('module_name', 'Vmg_chosen_member')
				->update('modules', array('has_cp_backend' => 'y'));
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
