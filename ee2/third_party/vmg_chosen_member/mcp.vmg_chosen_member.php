<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
require_once PATH_THIRD.'vmg_chosen_member/config.php';

/**
 * VMG Chosen Member Module CP Class
 *
 * @package		VMG Chosen Member
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2015 Vector Media Group, Inc.
 */
class Vmg_chosen_member_mcp
{
	public $return_data;
	public $chosen_helper;
	private $_base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=vmg_chosen_member';

		// Load our helper
		if ( ! class_exists('ChosenHelper') || ! is_a($this->chosen_helper, 'ChosenHelper')) {
			require_once PATH_THIRD.'vmg_chosen_member/helper.php';
			$this->chosen_helper = new ChosenHelper;
		}

		ee()->cp->set_right_nav(array(
			'module_home'	=> $this->_base_url,
		));
	}

	/**
	 * Index Function
	 * @return 	void
	 */
	public function index()
	{
		if (isset($_POST['convert_data_go']) && $_POST['convert_data_go'] == 'yes') {

			// Convert data from standard fields
			$this->chosen_helper->convertStandardFieldData();

			ee()->session->set_flashdata('message_success', 'Successfully built VMG Chosen Member data!');

			return ee()->functions->redirect($this->_base_url);
		}

		$data['base_url'] = $this->_base_url;

		return ee()->load->view('cp_main', $data, true);
	}

}

/* End of file mcp.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mcp.vmg_chosen_member.php */
