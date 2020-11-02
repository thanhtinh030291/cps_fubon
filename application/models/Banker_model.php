<?php
/**
 * This Class contains all the business logic and the persistence layer for the roles.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');
class Banker_model extends CI_Model {

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

        $this->db->replace('banker', $data);
    }
}
