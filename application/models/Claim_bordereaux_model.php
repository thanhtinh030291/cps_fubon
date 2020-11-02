<?php
/**
 * This Class contains all the business logic and the persistence layer for the notes.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Claim_bordereaux_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Add a payment to claim bordereaux
     * @param array $payment
     * @param date $tf_date Transfer Date
     * @param int $tf_amt Transfer Amount
     * @param int $type Claim Bordereaux Type
     */
    public function add($payment, $tf_date, $tf_amt, $type)
    {
        $this->db
            ->set('CLBO_MON', date('m'))
            ->set('CLBO_YEAR', date('Y'))
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('CLAIMANT', $payment['MEMB_NAME'])
            ->set('CLIENT_ID', $payment['MEMB_REF_NO'])
            ->set('POCY_REF_NO', $payment['POCY_REF_NO'])
            ->set('CL_NO', $payment['CL_NO'])
            ->set('INV_NO', $payment['INV_NO'])
            ->set('PRES_AMT', $payment['PRES_AMT'])
            ->set('APP_AMT', $payment['APP_AMT'])
            ->set('VISIT_FOR_BENEFIT', $payment['BEN_TYPE'])
            ->set('PAYMENT_DATE', $payment['APP_DATE'])
            ->set('PAYEE', $payment['PAYEE'])
            ->set('TF_AMT', $tf_amt)
            ->set('TF_DATE', $tf_date)
            ->set('CLBO_TYPE', $type)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('claim_bordereaux');
    }
    
    /**
     * Remove a payment of claim bordereaux
     * @param int $paym_id Payment Request ID
     * @param date $tf_date Transfer Date
     * @param int $tf_amt Transfer Amount
     */
    public function remove($paym_id, $tf_date, $tf_amt)
    {
        $this->db
            ->where('PAYM_ID', $paym_id)
            ->where('TF_DATE', $tf_date)
            ->where('TF_AMT', $tf_amt)
            ->delete('claim_bordereaux');
    }
    
    /**
     * Get a CLBO_TYPE_PAYMENT record of a payment then add a new record to adjust it's payment information
     * @param int $paym_id Payment Request ID
     */
    public function adjust_payment($paym_id)
    {
        $row = $this->_get_latest_by_type($paym_id, CLBO_TYPE_PAYMENT);
        if ( ! empty($row))
        {
            $row['TF_AMT'] = -$row['TF_AMT'];
            $row['CLBO_MON'] = date('m');
            $row['CLBO_YEAR'] = date('Y');
            $row['CLBO_TYPE'] = CLBO_TYPE_CLOSED_PAYMENT;
            $row['CRT_USER'] = $this->session->userdata('user_name');
            $this->db->insert('claim_bordereaux', $row);
        }
    }
    
    /**
     * Get a CLBO_TYPE_PAYMENT then add a new record to adjust it's transfer amount
     * @param int $paym_id Payment Request ID
     * @param int $decr_amt Decrease Amount
     */
    public function adjust_decrease($paym_id, $decr_amt)
    {
        $row = $this->_get_latest_by_type($paym_id, CLBO_TYPE_PAYMENT);
        if ( ! empty($row))
        {
            $row['TF_AMT'] = -$decr_amt;
            $row['CLBO_MON'] = date('m');
            $row['CLBO_YEAR'] = date('Y');
            $row['CLBO_TYPE'] = CLBO_TYPE_ADJUST_DECREASE;
            $row['CRT_USER'] = $this->session->userdata('user_name');
            $this->db->insert('claim_bordereaux', $row);
        }
    }
    
    /**
     * Get latest record of a payment by Claim Bordereaux Type
     * @param int $paym_id
     * @param int $clfu_type
     * @return array
     */
    private function _get_latest_by_type($paym_id, $clbo_type)
    {
        return $this->db
            ->select('PAYM_ID, CLAIMANT, CLIENT_ID, POCY_REF_NO, CL_NO, INV_NO, PRES_AMT, APP_AMT, VISIT_FOR_BENEFIT, PAYMENT_DATE, PAYEE, TF_AMT, TF_DATE')
            ->limit(1)
            ->order_by('CLBO_ID', 'desc')
            ->where('PAYM_ID', $paym_id)
            ->where('CLBO_TYPE', $clbo_type)
            ->get('claim_bordereaux')
            ->row_array();
    }
    
    /**
     * Get a CLBO_TYPE_DEDUCTION record of a recharge payment then add a new record to adjust it's payment information
     * @param int $paym_id Payment Request ID
     */
    public function adjust_recharge_payment($paym_id)
    {
        $row = $this->_get_latest_by_type($paym_id, CLBO_TYPE_DEDUCTION);
        if ( ! empty($row))
        {
            $row['TF_AMT'] = -$row['TF_AMT'];
            $row['CLBO_MON'] = date('m');
            $row['CLBO_YEAR'] = date('Y');
            $row['CLBO_TYPE'] = CLBO_TYPE_CLOSED_DEDUCTION;
            $row['CRT_USER'] = $this->session->userdata('user_name');
            $this->db->insert('claim_bordereaux', $row);
        }
    }
    
    /**
     * Get report of Claim Bordereaux by using Transfer Date
     * @param int $month
     * @param int $year
     */
    public function report($month, $year)
    {
        $this->db->where('MONTH(`TF_DATE`)', $month);
        $this->db->where('YEAR(`TF_DATE`)', $year);
        $this->db->order_by('TF_DATE', 'ASC');
        $result = $this->db->get('claim_bordereaux');
        return $result->result_array();
    }
}
