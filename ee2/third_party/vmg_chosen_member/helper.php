<?php if (! defined('BASEPATH')) die('No direct script access allowed');

/**
 * VMG Chosen Member Helper Class
 *
 * @package     VMG Chosen Member
 * @version     1.6
 * @author      Luke Wilkins <luke@vectormediagroup.com>
 * @copyright   Copyright (c) 2011-2013 Vector Media Group, Inc.
 */

class Chosen_helper
{
    protected $disallowed_fields = array('password', 'unique_id', 'crypt_key', 'salt');

    public $default_search_fields = array(
        'username' => 'Username', 'screen_name' => 'Screen Name', 'email' => 'Email', 'url' => 'URL',
        'location' => 'Location', 'occupation' => 'Occupation', 'interests' => 'Interests',
        'aol_im' => 'AOL IM', 'yahoo_im' => 'Yahoo! IM', 'msn_im' => 'MSN IM', 'icq' => 'ICQ',
        'bio' => 'Bio', 'signature' => 'Signature',
    );

    public function __construct()
    {
        $this->EE =& get_instance();
    }

    /**
     * Gather associated member data info
     */
    public function member_associations($entry_id, $field_id = null, $col_id = null, $row_id = null, $var_id = null, $settings = null, $select_fields = null, $group_by = 'vcm.member_id')
    {
        // Return specific fields from query
        if (! is_null($select_fields)) {
            $this->EE->db->select($select_fields);
        } else {
            $this->EE->db->select('m.*');
        }

        $this->EE->db->from('vmg_chosen_member AS vcm')
            ->join('members AS m', 'm.member_id = vcm.member_id', 'inner');

        // Add join to member_data if that is within the select statement
        if (strpos($select_fields, 'md.') !== false) {
            $this->EE->db->join('member_data AS md', 'md.member_id = vcm.member_id', 'inner');
        }

        // Make general restrictions for this particular field
        $this->EE->db->where('vcm.entry_id', $entry_id);
        $this->EE->db->where('vcm.field_id', $field_id);
        $this->EE->db->where('vcm.col_id', $col_id);
        $this->EE->db->where('vcm.row_id', $row_id);
        $this->EE->db->where('vcm.var_id', $var_id);

        if (isset($settings['allowed_groups']) && is_array($settings['allowed_groups']) && ! empty($settings['allowed_groups'])) {
            $this->EE->db->where_in('m.group_id', $settings['allowed_groups']);
        }

        if (isset($settings['max_selections']) && is_numeric($settings['max_selections']) && $settings['max_selections'] > 0) {
            $this->EE->db->limit($settings['max_selections']);
        }

        if (! is_null($group_by)) {
            $this->EE->db->group_by($group_by);
        }

        // Handle custom search restrictions
        if (isset($settings['search']) && is_array($settings['search'])) {
            foreach ($settings['search'] AS $field => $values) {
                if (is_array($values)) {
                    $this->EE->db->where_in('m.' . $field, $values);
                } else {
                    $this->EE->db->where('m.' . $field, $values);
                }
            }
        }

        if (isset($settings['order_by']) && ! empty($settings['order_by'])) {
            $order_by = $settings['order_by'];
        } else {
            $order_by = 'vcm.order';
        }

        if (isset($settings['sort']) && strtolower($settings['sort']) == 'desc') {
            $sort = 'desc';
        } else {
            $sort = 'asc';
        }

        $this->EE->db->order_by($order_by, $sort);

        return $this->EE->db->get()
            ->result_array();
    }

    /**
     * Return list of valid Member IDs from selected list
     */
    public function validate_selections($selections, $settings)
    {
        $this->EE->db->select('member_id')
            ->from('members')
            ->where_in('member_id', $selections);

        if (isset($settings['allowed_groups']) && is_array($settings['allowed_groups']) && ! empty($settings['allowed_groups'])) {
            $this->EE->db->where_in('group_id', $settings['allowed_groups']);
        }

        if (isset($settings['max_selections']) && is_numeric($settings['max_selections']) && $settings['max_selections'] > 0) {
            $this->EE->db->limit($settings['max_selections']);
        }

        $results = $this->EE->db->get()
            ->result_array();

        $output = array();
        foreach ($results AS $result) {
            $output[] = $result['member_id'];
        }

        return $output;
    }

    /**
     * Clear selections from database that are no longer selected
     */
    public function clear_old_selections($selections, $settings)
    {
        // Make general restrictions for this particular field
        $this->EE->db->where('entry_id', $settings['entry_id']);
        $this->EE->db->where('field_id', $settings['field_id']);
        $this->EE->db->where('col_id', $settings['col_id']);
        $this->EE->db->where('row_id', $settings['row_id']);
        $this->EE->db->where('var_id', $settings['var_id']);

        // Clear everything for this field if no selections are made
        if (empty($selections)) {
            $selections = array(0);
        }

        $this->EE->db->where_not_in('member_id', $selections)
            ->delete('vmg_chosen_member');

        return $this->EE->db->affected_rows();
    }

    /**
     * Save current selections to database
     */
    public function save_selections($selections, $settings)
    {
        // Build base row data
        $data = array(
            $settings['entry_id'],
            $settings['field_id'],
            $settings['col_id'],
            $settings['row_id'],
            $settings['var_id'],
        );

        foreach ($selections AS $key => $selection) {

            $this->EE->db->query("INSERT INTO exp_vmg_chosen_member SET entry_id = ?, field_id = ?, col_id = ?, row_id = ?, var_id = ?, member_id = ?, `order` = ? ON DUPLICATE KEY UPDATE `order` = ?", array_merge($data, array(
                    $selection,
                    $key,
                    $key,
                )
            ));

        }
    }

    /**
     * Get all available member groups
     */
    public function get_member_groups()
    {
        return $this->EE->db->select("mg.group_id, mg.group_title")
            ->from('exp_member_groups AS mg')
            ->group_by('mg.group_id')
            ->get()
            ->result_array();
    }

    /**
     * Get all custom member fields
     */
    public function get_custom_member_fields()
    {
        return $this->EE->db->select("m_field_id, m_field_name, m_field_label")
            ->from('exp_member_fields')
            ->order_by('m_field_order', 'asc')
            ->get()
            ->result_array();
    }

    /**
     * Get action_id for a specific method
     */
    public function action_id($method, $full_path = false)
    {
        if (! isset($this->EE->session->cache['vmg_chosen_member']['action'][$method])) {
            $action = $this->EE->db->select('action_id')
                ->from('actions')
                ->where('class', 'Vmg_chosen_member')
                ->where('method', $method)
                ->get()
                ->row_array();

            $this->EE->session->cache['vmg_chosen_member']['action'][$method] = $action;
        }

        if (isset($this->EE->session->cache['vmg_chosen_member']['action'][$method]['action_id'])) {

            if ($full_path) {
                return $this->EE->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $this->EE->session->cache['vmg_chosen_member']['action'][$method]['action_id'];
            }

            return $this->EE->session->cache['vmg_chosen_member']['action'][$method]['action_id'];
        }

        return null;
    }

    /**
     * Include the required CSS and JS
     */
    public function include_assets()
    {
        if (! isset($this->EE->session->cache['vmg_chosen_member']['assets_included']))
        {
            $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/vmg_chosen_member/chosen/chosen.css' . '" />');
            $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/vmg_chosen_member/vmg_chosen_member.css' . '" />');
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/vmg_chosen_member/chosen/chosen.jquery.js' . '"></script>');
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/vmg_chosen_member/vmg_chosen_member.js' . '"></script>');

            $this->EE->session->cache['vmg_chosen_member']['assets_included'] = true;
        }

        return true;
    }

    /**
     * Prefix array for custom output
     */
    public function set_prefix($array, $prefix = '')
    {
        // Bail now if no prefix set
        if (empty($prefix)) {
            return $array;
        }

        foreach ($array AS $key => $member) {
            foreach ($member AS $item => $value) {

                // Set prefix where applicable
                if (!in_array($item, $this->disallowed_fields)) {
                    $array[$key][$prefix . $item] = $value;
                }

                // Unset pre-prefix field and disallowed fields
                if (!empty($prefix) || in_array($item, $this->disallowed_fields)) {
                    unset($array[$key][$item]);
                }

            }
        }

        return $array;
    }

    /**
     * Remove X number of characters from end of string
     */
    public function backspace($string, $backspace)
    {
        if (is_numeric($backspace) && $backspace > 0) {
            $string = substr($string, 0, ($backspace * -1));
        }

        return $string;
    }
}
