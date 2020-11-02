<?php
/**
 * This Class contains all the business logic and the persistence layer for the roles.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');
class Group_banker_sign_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }

    /**
     * Get the list of roles or one role
     * @param int $role_id optional ROLE_ID of one role
     * @return array record of roles
     */
    public function updateOrInsert($data)
    {
        $this->db->replace('group_banker_sign', $data);
        return $this->db->insert_id();
    }
    public function set_value($id, $field, $value)
    {
        $this->db->set($field, $value)
            ->where('GBS_ID', $id)
            ->update('group_banker_sign');
    }
    public function get_group_banker_signs($id = false){
        if ( ! empty($id))
        {
            return $this->db->where('GBS_ID', $id)->get('group_banker_sign')->row_array();
        }
        return $this->db->order_by('GBS_ID', 'DESC')->get('group_banker_sign')->result_array();
    }
}
