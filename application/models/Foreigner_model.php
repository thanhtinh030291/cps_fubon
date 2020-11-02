<?php
/**
 * This model contains the business logic and manages the persistence of foreginer bank info
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Foreigner_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Get Bank Account information of a foreigner or all foreigners
     * @param $forn_id int
     * @return array list of foreigner's bank account
     */
    public function get_foreigners($forn_id = false)
    {
        if ( ! empty($forn_id))
        {
            return $this->db->where('FORN_ID', $forn_id)->get('foreigner')->row_array();
        }
        return $this->db->get('foreigner')->result_array();
    }
    
    /**
     * Add new Foreigner Vietcombank Account
     * @param $acct_name string
     * @param $acct_no string
     * @param $bank_branch string
     * @param $bank_city string
     */
    public function add($acct_name, $acct_no, $bank_branch = '', $bank_city = '')
    {
        $this->db
            ->set('ACCT_NAME', $acct_name)
            ->set('ACCT_NO', $acct_no)
            ->set('BANK_NAME', 'vietcombank')
            ->set('BANK_BRANCH', $bank_branch)
            ->set('BANK_CITY', $bank_city)
            ->insert('foreigner');
    }
    
    /**
     * Edit a current Foreigner Vietcombank Account
     * @param $forn_id int
     * @param $acct_name string
     * @param $acct_no string
     * @param $bank_branch string
     * @param $bank_city string
     */
    public function edit($forn_id, $acct_name, $bank_branch, $bank_city)
    {
        $this->db
            ->set('ACCT_NAME', $acct_name)
            ->set('BANK_BRANCH', $bank_branch)
            ->set('BANK_CITY', $bank_city)
            ->where('FORN_ID', $forn_id)
            ->update('foreigner');
    }
    
    /**
     * Delete a current Foreigner Vietcombank Account
     * @param $forn_id int
     */
    public function delete($forn_id)
    {
        $this->db
            ->where('FORN_ID', $forn_id)
            ->delete('foreigner');
    }
    
    /**
     * Check an Vietcombank Account is in Foreigner List or not
     * @param $acct_name string
     * @param $acct_no int
     */
    public function is_foreigner($acct_name, $acct_no)
    {
        $result = $this->db
            ->where('ACCT_NAME', $acct_name)
            ->where('ACCT_NO', $acct_no)
            ->count_all_results('foreigner');
        if ($result)
        {
            return true;
        }
        return false;
    }
}
