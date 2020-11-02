<?php
/**
 * This model contains the business logic and manages the persistence of third party
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Partner_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Get current Bank Info of Partner
     */
    public function get_bank_info()
    {
        return $this->db
            ->where('PARTNER_ID', 1)
            ->get('partner')
            ->row_array();
    }
    
    /**
     * Update Bank Info of Partner
     * @param string $acct_name
     * @param string $acct_no
     * @param string $bank_name
     * @param string $bank_branch
     * @param string $bank_city
     */
    public function set_bank_info($acct_name, $acct_no, $bank_name, $bank_branch, $bank_city)
    {
        $this->db->set('ACCT_NAME', $acct_name);
        $this->db->set('ACCT_NO', $acct_no);
        $this->db->set('BANK_NAME', $bank_name);
        $this->db->set('BANK_BRANCH', $bank_branch);
        $this->db->set('BANK_CITY', $bank_city);
        $this->db->where('PARTNER_ID', 1);
        $this->db->update('partner');
    }
}
