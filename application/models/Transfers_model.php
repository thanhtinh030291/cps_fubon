<?php
/**
 * This Class contains all the business logic and the persistence layer for transfers.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Transfers_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Transferred deduction payment
     * @param int $paym_id
     * @param int $deduct_amt
     * @param string $deduct_reason
     */
    public function deduction($paym_id, $deduct_amt, $deduct_reason)
    {
        $this->db
            ->set('PAYM_ID', $paym_id)
            ->set('TRAN_TIMES', 0)
            ->set('TRAN_STATUS', 'Deduction')
            ->set('DEDUCT_DATE', date('Y-m-d'))
            ->set('DEDUCT_AMT', $deduct_amt)
            ->set('DEDUCT_REASON', $deduct_reason)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('transfers');
    }
    
    /**
     * Transferred payment
     * @param array $payment
     * @param date $tf_date
     * @param string $vcb_seq
     */
    public function transferred($payment, $tf_date, $vcb_seq)
    {
        $this->db
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('TRAN_TIMES', $payment['TF_NO'] + 1)
            ->set('TRAN_STATUS', 'Transferred')
            ->set('SHEET_ID', $payment['SHEET_ID'])
            ->set('TF_VCB_SEQ', $vcb_seq)
            ->set('TF_DATE', $tf_date)
            ->set('PAYMENT_METHOD', $payment['PAYMENT_METHOD'])
            ->set('ACCT_NAME', $payment['ACCT_NAME'])
            ->set('ACCT_NO', $payment['ACCT_NO'])
            ->set('BANK_NAME', $payment['BANK_NAME'])
            ->set('BANK_CITY', $payment['BANK_CITY'])
            ->set('BANK_BRANCH', $payment['BANK_BRANCH'])
            ->set('BENEFICIARY_NAME', $payment['BENEFICIARY_NAME'])
            ->set('PP_DATE', $payment['PP_DATE'])
            ->set('PP_PLACE', $payment['PP_PLACE'])
            ->set('PP_NO', $payment['PP_NO'])
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('transfers');
    }
    
    /**
     * Cancel a Transferred payment because of user's mistaken action
     * @param array $payment
     */
    public function cancel($payment)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', null)
            ->set('IS_DELETED', 1)
            ->set('UPD_USER', $this->session->userdata('user_name'))
            ->set('UPD_DATE', date('Y-m-d H:i:s'))
            ->where('PAYM_ID', $payment['PAYM_ID'])
            ->where('TRAN_TIMES', $payment['TF_NO'])
            ->where('TRAN_STATUS', 'Transferred')
            ->where('SHEET_ID', $payment['SHEET_ID'])
            ->where('TF_VCB_SEQ', $payment['VCB_SEQ'])
            ->where('TF_DATE', $payment['TF_DATE'])
            ->update('transfers');
    }
    
    /**
     * Refund a Transferred payment
     * @param array $paym_id
     * @param date $refund_date
     * @param string $refund_vcb_seq
     * @param string $refund_reason
     */
    public function refund($paym_id, $refund_date, $refund_vcb_seq, $refund_reason)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', null)
            ->set('TRAN_STATUS', 'Returned')
            ->set('REFUND_DATE', $refund_date)
            ->set('REFUND_VCB_SEQ', $refund_vcb_seq)
            ->set('REFUND_REASON', $refund_reason)
            ->set('UPD_USER', $this->session->userdata('user_name'))
            ->set('UPD_DATE', date('Y-m-d H:i:s'))
            ->where('PAYM_ID', $paym_id)
            ->where('TRAN_STATUS', 'Transferred')
            ->update('transfers');
    }
    
    /**
     * Cancel a Refund action because of user's mistaken action
     * @param array $payment
     * @param date $tf_date
     * @param string $vcb_seq
     */
    public function cancel_refund($paym_id)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', null)
            ->set('TRAN_STATUS', 'Transferred')
            ->set('REFUND_DATE', null)
            ->set('REFUND_VCB_SEQ', null)
            ->set('REFUND_REASON', null)
            ->set('UPD_USER', $this->session->userdata('user_name'))
            ->set('UPD_DATE', date('Y-m-d H:i:s'))
            ->where('PAYM_ID', $paym_id)
            ->where('TRAN_STATUS', 'Returned')
            ->update('transfers');
    }
    
    /**
     * Get a transfer of a payment
     * @param int $paym_id
     * @param string $tran_status
     * @return array
     */
    public function get($paym_id, $tran_status)
    {
        return $this->db
            ->where('PAYM_ID', $paym_id)
            ->where('TRAN_STATUS', $tran_status)
            ->get('transfers')
            ->row_array();
    }
    
    /**
     * Get all transferred claims
     * @return array
     */
    public function get_all_claims()
    {
        return $this->db
            ->distinct()
            ->select('CL_NO')
            ->join('payments', 'payments.PAYM_ID = transfers.PAYM_ID')
            ->where('transfers.IS_DELETED', 0)
            ->get('transfers')
            ->result_array();
    }
    
    /**
     * Get all transfers of a claim
     * @param int $cl_no
     * @return array
     */
    public function get_all_transfers($cl_no)
    {
        return $this->db
            ->select('transfers.*, payments.TF_AMT')
            ->select_sum('client_debit.DEBT_BALANCE')
            ->join('payments', 'payments.PAYM_ID = transfers.PAYM_ID')
            ->join('client_debit', 'client_debit.TRAN_ID = transfers.TRAN_ID', 'left')
            ->where('payments.CL_NO', $cl_no)
            ->where('transfers.IS_DELETED', 0)
            ->group_by('transfers.TRAN_ID')
            ->get('transfers')
            ->result_array();
    }
    
    /**
     * Close current transfer of a payment request
     * @param int $paym_id
     * @param int $tf_no
     */
    public function closed($paym_id, $tf_no)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', null)
            ->set('TRAN_STATUS', 'Closed')
            ->set('UPD_USER', $this->session->userdata('user_name'))
            ->set('UPD_DATE', date('Y-m-d H:i:s'))
            ->where('PAYM_ID', $paym_id)
            ->where('TRAN_TIMES', $tf_no)
            ->where('TRAN_STATUS', 'Returned')
            ->update('transfers');
    }
    
    /**
     * Close recharge payments of a payment request
     * @param int $paym_id
     */
    public function closed_deduction($paym_id)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', null)
            ->set('TRAN_STATUS', 'Closed Deduction')
            ->set('UPD_USER', $this->session->userdata('user_name'))
            ->set('UPD_DATE', date('Y-m-d H:i:s'))
            ->where('PAYM_ID', $paym_id)
            ->where('TRAN_TIMES', 0)
            ->where('TRAN_STATUS', 'Deduction')
            ->update('transfers');
    }
    
    /**
     * Get all transfers which are not uploaded to Mantis
     * @param int $paym_id
     */
    public function get_non_upload()
    {
        return $this->db
            ->select('transfers.*, payments.PAYMENT_TIME, payments.TF_AMT, payments.CL_NO, payments.MANTIS_ID, payments.TF_STATUS_ID')
            ->join('payments', 'payments.PAYM_ID = transfers.PAYM_ID')
            ->where('UPL_MANTIS_DATE', null)
            ->where('transfers.IS_DELETED', 0)
            ->get('transfers')->result_array();
    }
    
    /**
     * set UPL_MANTIS_DATE of a list of transfer
     * @param int $list_id
     * @param date $upl_date
     */
    public function set_upl_mantis_date($list_id, $upl_date)
    {
        $this->db
            ->set('UPL_MANTIS_DATE', $upl_date)
            ->where_in('TRAN_ID', $list_id)
            ->update('transfers');
    }
}
