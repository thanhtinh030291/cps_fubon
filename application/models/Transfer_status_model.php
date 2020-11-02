<?php
/**
 * This Class contains all the business logic and the persistence layer for the transfer status of payment request.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class Transfer_status_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }

    /**
     * Get the list of transfer status
     * @return array
     */
    public function get_transfer_status()
    {
        return $this->db
            ->order_by('TF_STATUS_ID', 'ASC')
            ->get('sys_transfer_status')
            ->result_array();
    }
    
    /**
     * Get name of transfer status by using transfer status id
     * @param int tf_status_id
     * @return string
     */
    public function get_name($tf_status_id)
    {
        return $this->db
            ->where('TF_STATUS_ID', $tf_status_id)
            ->get('sys_transfer_status')
            ->row()
            ->TF_STATUS_NAME;
    }
}
