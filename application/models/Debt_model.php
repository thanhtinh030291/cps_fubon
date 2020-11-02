<?php
/**
 * This Class contains all the business logic and the persistence layer for the notes.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Debt_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Set a debt to Client because transfer amt is larger than approved amount
     * @param int $tran_id
     * @param int $debt_cl_no
     * @param int $decr_amt
     * @param string $memb_name
     * @param int $memb_ref_no
     */
    public function set_debt($tran_id, $debt_cl_no, $debt_amt, $memb_name, $memb_ref_no, $debt_type =DEBT_TYPE_DEBT)
    {
        if ($debt_type == DEBT_TYPE_DEBT)
        {
            $this->db->set('DEBT_AMT', $debt_amt);
        }
        elseif($debt_type == DEBT_TYPE_PCV_EXPENSE)
        {
            $this->db->set('PCV_EXPENSE', $debt_amt);
        }
        $this->db
            ->set('DEBT_TYPE', $debt_type)
            ->set('TRAN_ID', $tran_id)
            ->set('DEBT_CL_NO', $debt_cl_no)
            ->set('MEMB_NAME', $memb_name)
            ->set('MEMB_REF_NO', $memb_ref_no)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('client_debit');
    }
    
    /**
     * Set a paid amount to Client when Client sends money back
     * @param int $tran_id
     * @param int $debt_cl_no
     * @param int $decr_amt
     * @param string $memb_name
     * @param int $memb_ref_no
     */
    public function paid_debt($debt_cl_no, $memb_name, $memb_ref_no, $paid_amt)
    {
        $this->db
            ->set('DEBT_TYPE', DEBT_TYPE_PAID_DIRECT)
            ->set('DEBT_CL_NO', $debt_cl_no)
            ->set('MEMB_NAME', $memb_name)
            ->set('MEMB_REF_NO', $memb_ref_no)
            ->set('PAID_AMT', $paid_amt)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('client_debit');
    }
    
    /**
     * Switch Debt Amt to PCV Expense, Debt Type will also set to DEBT_TYPE_PCV_EXPENSE
     * @param array $debt_ids list of debt id
     */
    public function switch_pcv_expense($debt_ids)
    {
        $this->db
            ->set('DEBT_TYPE', DEBT_TYPE_PCV_EXPENSE)
            ->set('PCV_EXPENSE', 'DEBT_AMT', false)
            ->set('DEBT_AMT', 0)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->set('CRT_DATE', date('Y-m-d H:i:s'))
            ->where_in('DEBT_ID', $debt_ids)
            ->where('PCV_EXPENSE', 0)
            ->where('DEBT_AMT !=', null)
            ->update('client_debit');
    }
    
    /**
     * Switch PCV Expense to Debt
     * @param array $debt_ids list of debt id
     */
    public function expense2debt($debt_id)
    {
        $this->db
            ->set('DEBT_TYPE', DEBT_TYPE_DEBT)
            ->set('DEBT_AMT', 'PCV_EXPENSE', false)
            ->set('PCV_EXPENSE', 0)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->set('CRT_DATE', date('Y-m-d H:i:s'))
            ->where('DEBT_ID', $debt_id)
            ->where('DEBT_AMT', 0)
            ->where('PCV_EXPENSE !=', 0)
            ->update('client_debit');
    }
    
    /**
     * Get balance of all Client Debts
     * @param int $memb_ref_no optional
     * @param int $debt_cl_no optional
     * @return int
     */
    public function get_balance_debt($memb_ref_no = null)
    {
        if ( ! empty($memb_ref_no))
        {
            $this->db
                ->where('MEMB_REF_NO', $memb_ref_no)
                ->group_by('MEMB_REF_NO')
                ->having('DEBT_BALANCE >', 0);
        }
        $result = $this->db
            ->select_sum('DEBT_BALANCE')
            ->get('client_debit')->row();
        if (empty($result))
        {
            return 0;
        }
        return intval($result->DEBT_BALANCE);
    }
    
    /**
     * Clients pay debt by deduction Transfer Amount of other Claim
     * @param int $tran_id
     * @param int $decr_amt
     * @param string $memb_name
     * @param int $memb_ref_no
     */
    public function pay_debt_deduction($memb_name, $memb_ref_no, $paid_amt, $paid_cl_no)
    {
        $this->db
            ->set('DEBT_TYPE', DEBT_TYPE_PAID_DEDUCT)
            ->set('MEMB_NAME', $memb_name)
            ->set('MEMB_REF_NO', $memb_ref_no)
            ->set('PAID_AMT', $paid_amt)
            ->set('PAID_CL_NO', $paid_cl_no)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('client_debit');
    }
    
    /**
     * Get client debit details of a client
     * @param int $memb_ref_no
     * @return array
     */
    public function get_client_debit($memb_ref_no)
    {
        return $this->db
            ->join('sys_debt_type sys', 'sys.DEBT_TYPE = client_debit.DEBT_TYPE')
            ->where('MEMB_REF_NO', $memb_ref_no)
            ->get('client_debit')
            ->result_array();
    }
    
    /**
     * Get client debit details of a client
     * @param int $debt_id
     * @return array
     */
    public function get($debt_id)
    {
        return $this->db
            ->join('sys_debt_type sys', 'sys.DEBT_TYPE = client_debit.DEBT_TYPE')
            ->where('DEBT_ID', $debt_id)
            ->get('client_debit')
            ->row_array();
    }
}
