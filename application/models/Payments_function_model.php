<?php
/**
 * This model contains the business logic and manages the persistence of payments function
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_function_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }

    /**
     * Get the list of functions which user is available to do in a Payment page
     * @param $is_assigned boolean
     * @param $is_100000000 boolean
     * @param $tf_status_id int
     * @return array
     */
    public function get($is_assigned, $is_100000000, $tf_status_id)
    {
        // return array_filter($this->db
        $this->db
            ->select('FUNC_NAME')
            ->where_in('ROLE_ID', [ROLE_CL_LEADER, ROLE_CL_MANAGER, ROLE_CL_DIRECTOR])
            ->or_where('IS_ASSIGNED', 1)
            ->where('MIN_STATUS_ID <=', $tf_status_id)
            ->where('MAX_STATUS_ID >=', $tf_status_id);
    }
}
