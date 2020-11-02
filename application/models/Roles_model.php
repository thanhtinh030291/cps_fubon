<?php
/**
 * This Class contains all the business logic and the persistence layer for the roles.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This Class contains all the business logic and the persistence layer for the roles.
 */
class Roles_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
        /*
            8  Admin
            5  Finance
            2  Claim
         * 
         */
    }

    /**
     * Get the list of roles or one role
     * @param int $role_id optional ROLE_ID of one role
     * @return array record of roles
     */
    public function get_roles($role_id = 0)
    {
        if ($role_id === 0)
        {
            return $this->db->get('roles')->result_array();
        }
        return $this->db->get_where('roles', array('ROLE_ID' => $role_id))->row_array();
    }
}
