<?php
/**
 * This model contains the business logic and manages the persistence of claim_fund_fixed table
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Claim_fund_fixed_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Get ending balance of a Claim Fund monthly report
     * @param date $time format: m/Y, e.g. 11/2019
     * @return array
     */
    public function get_ending_balance($time)
    {
        return $this->db
            ->where('CLFF_MON_YEAR', $time)
            ->get('claim_fund_fixed')
            ->row_array();;
    }
    
    /**
     * Set ending balance
     * @param date $time format: m/Y, e.g. 11/2019
     * @param int $end_repl Tồn Bổ Sung Quỹ
     * @param int $end_refu Tồn Quỹ Hoàn Trả
     */
    public function set_ending_balance($time, $end_repl, $end_refu)
    {
        list($mon, $year) = explode('/', $time);
        $this->db
            ->set('CLFF_MON', $mon)
            ->set('CLFF_YEAR', $year)
            ->set('CLFF_END_REPL', $end_repl)
            ->set('CLFF_END_REFU', $end_refu)
            ->insert('claim_fund_fixed');
    }
    
    /**
     * Get beginning balance of Claim Fund
     * @return int
     */
    public function get_beginning_balance()
    {
        $rp = $this->db->limit(1)
        ->select('CLFF_END_BALA')
        ->get('claim_fund_fixed')
        ->row();
        return $rp ? $rp->CLFF_END_BALA : 0;
            
    }
}
