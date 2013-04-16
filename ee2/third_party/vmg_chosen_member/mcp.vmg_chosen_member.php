<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VMG Chosen Member Module CP Class
 *
 * @package		VMG Chosen Member
 * @version		1.5.6
 * @author		Luke Wilkins <luke@vectormediagroup.com>
 * @copyright	Copyright (c) 2011-2013 Vector Media Group, Inc.
 **/

class Vmg_chosen_member_mcp {

	public $return_data;

	private $_base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=vmg_chosen_member';

		$this->EE->cp->set_right_nav(array(
			'module_home'	=> $this->_base_url,
		));
	}

	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		$this->EE->cp->set_variable('cp_page_title', lang('vmg_chosen_member_module_name'));
	}

}
/* End of file mcp.vmg_chosen_member.php */
/* Location: /system/expressionengine/third_party/vmg_chosen_member/mcp.vmg_chosen_member.php */
