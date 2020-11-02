<?php
/**
 * This Class contains all the business logic and the persistence layer for the payments.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Count number of TF_STATUS_APPROVED, TF_STATUS_NEW payments which Payment Method is not PP
     * @return int
     */
    public function count_claimant_approved()
    {
        return $this->db
            ->where('TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->count_all_results('payments');
    }

     /**
     * Count number of TF_STATUS_APPROVED, TF_STATUS_NEW payments which Payment Method is not PP
     * @return int
     */
    public function count_claimant_approved_m()
    {
        return $this->db
            ->where('TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->where('CL_TYPE = ', 'M')
            ->count_all_results('payments');
    }

     /**
     * Count number of TF_STATUS_APPROVED, TF_STATUS_NEW payments which Payment Method is not PP
     * @return int
     */
    public function count_claimant_approved_p()
    {
        return $this->db
            ->where('TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->where('CL_TYPE = ', 'P')
            ->count_all_results('payments');
    }
    
    /**
     * Count number of TF_STATUS_SHEET, TF_STATUS_TRANSFERRING payments in all sheets
     * @return int
     */
    public function count_claimant_sheet()
    {
        return $this->db
            ->group_start()
                ->where('TF_STATUS_ID', TF_STATUS_SHEET)
                ->or_where('TF_STATUS_ID', TF_STATUS_TRANSFERRING)
            ->group_end()
            ->where('SHEET_ID !=', null)
            ->count_all_results('payments');
    }
    
    /**
     * Count number of TF_STATUS_TRANSFERRING payments which are not in any sheets
     * @return int
     */
    public function count_claimant_transferring()
    {
        return $this->db
            ->where('TF_STATUS_ID', TF_STATUS_TRANSFERRING)
            ->where('SHEET_ID', null)
            ->count_all_results('payments');
    }
    
    /**
     * Count number of TF_STATUS_APPROVED, TF_STATUS_NEW payments which Payment Method is PP
     * Count number of TF_STATUS_DLVN_CANCEL, TF_STATUS_DLVN_PAYPREM payments
     * @return int
     */
    public function count_partner_approved()
    {
        return $this->db
            ->group_start()
                ->where('TF_STATUS_ID', TF_STATUS_APPROVED)
                ->where('PAYMENT_METHOD', 'PP')
            ->group_end()
            ->or_where('TF_STATUS_ID', TF_STATUS_DLVN_CANCEL)
            ->or_where('TF_STATUS_ID', TF_STATUS_DLVN_PAYPREM)
            ->count_all_results('payments');
    }
    
    /**
     * Count number of TF_STATUS_SHEET_PAYPREM, TF_STATUS_SHEET_DLVN_CANCEL, TF_STATUS_SHEET_DLVN_PAYPREM, TF_STATUS_TRANSFERRING_PAYPREM, TF_STATUS_TRANSFERRING_DLVN_CANCEL, TF_STATUS_TRANSFERRING_DLVN_PAYPREM payments
     * @return int
     */
    public function count_partner_sheet()
    {
        return $this->db
            ->group_start()
                ->where('TF_STATUS_ID', TF_STATUS_SHEET_PAYPREM)
                ->or_where('TF_STATUS_ID', TF_STATUS_TRANSFERRING_PAYPREM)
                ->or_where('TF_STATUS_ID', TF_STATUS_SHEET_DLVN_CANCEL)
                ->or_where('TF_STATUS_ID', TF_STATUS_TRANSFERRING_DLVN_CANCEL)
                ->or_where('TF_STATUS_ID', TF_STATUS_SHEET_DLVN_PAYPREM)
                ->or_where('TF_STATUS_ID', TF_STATUS_TRANSFERRING_DLVN_PAYPREM)
            ->group_end()
            ->where('SHEET_ID !=', null)
            ->count_all_results('payments');
    }
    
    /**
     * Get TF_STATUS_APPROVED/TF_STATUS_NEW payments which Payment Method is not PP
     * @return array
     */
    public function get_claimant_approved()
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('sys.TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->get('payments')
            ->result_array();
    }

    /**
     * Get TF_STATUS_APPROVED/TF_STATUS_NEW payments which Payment Method is not PP and type M
     * @return array
     */
    public function get_claimant_approved_m()
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('sys.TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->where('CL_TYPE = ', 'M')
            ->get('payments')
            ->result_array();
    }

    /**
     * Get TF_STATUS_APPROVED/TF_STATUS_NEW payments which Payment Method is not PP and type P
     * @return array
     */
    public function get_claimant_approved_p()
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('sys.TF_STATUS_ID', TF_STATUS_APPROVED)
            ->where('PAYMENT_METHOD !=', 'PP')
            ->where('CL_TYPE = ', 'P')
            ->get('payments')
            ->result_array();
    }

    public function get_payment_transfed_unc($tf_date_from = null , $tf_date_to = null)
    {
        $query = $this->db->select('payments.* , sys.*')
        ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
        ->join('banker', 'banker.PAYM_ID  = payments.PAYM_ID ','left')
        ->where('sys.TF_STATUS_ID >=',TF_STATUS_TRANSFERRED)
        ->where('PAYMENT_METHOD !=', 'CQ')
        ->where('banker.URL_UNC =', null);
        
        if($tf_date_from){
            $query = $query->where('TF_DATE >=', $tf_date_from);
        }
        if($tf_date_to){
            $query = $query->where('TF_DATE <=', $tf_date_to);
        }
        return $query->get('payments')->result_array();
    }
    /**
     * Get payment with specific paym_id
     * @param int $paym_id
     * @return an array
     */
    public function get_payment($paym_id)
    {
        return $this->db
            ->join('sys_transfer_status sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('PAYM_ID', $paym_id)
            ->get('payments')
            ->row_array();
    }
    
    /**
     * change ZERO TF_AMT payment from APPROVED/NEW to TRANSFERRED
     * @param int $paym_id
     * @param date $tf_date
     */
    public function transferred_full_deduction($paym_id, $tf_date)
    {
        $this->db
            ->set('TF_STATUS_ID', TF_STATUS_TRANSFERRED)
            ->set('TF_DATE', $tf_date)
            ->set('YN_CLBO', 'N')
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    
    /**
     * move payment from APPROVED/NEW/RETURNED TO CLAIM to SHEET
     * @param int $paym_id
     * @param int $sheet_id
     * @param int $tf_status_id
     * @param int $process_order
     */
    public function to_sheet($paym_id, $sheet_id, $tf_status_id, $set_order = true)
    {
        if ($set_order)
        {
            $this->db->set('PROCESS_ORDER', $this->get_max_process_order() + 1);
        }
        $this->db
            ->set('TF_STATUS_ID', $tf_status_id)
            ->set('SHEET_ID', $sheet_id)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
        
    /**
     * change payment from SHEET or APPROVED/NEW to TRANSFERRING
     * @param int $PAYM_ID
     */
    public function to_transferring($paym_id)
    {
        $this->db
            ->set('TF_STATUS_ID', TF_STATUS_TRANSFERRING)
            ->set('PROCESS_ORDER', $this->get_max_process_order() + 1)
            ->set('SHEET_ID',null)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
        
    /**
     * set transfer status of a payment
     * @param int $paym_id
     * @param int $tf_status_id
     * @return boolean
     */
    public function set_transfer_status($paym_id, $tf_status_id)
    {
        $this->db
            ->set('TF_STATUS_ID', $tf_status_id)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    
    /**
     * Count number of payments in a sheet
     * @param int $sheet_id
     * @return int
     */
    public function count_sheet_payments($sheet_id)
    {
        return $this->db
            ->where('SHEET_ID', $sheet_id)
            ->count_all_results('payments');
    }
    
    /**
     * Get Total Transfer Amount of a sheet
     * @param int $sheet_id
     * @return int
     */
    public function get_sheet_amt($sheet_id)
    {
        return $this->db
            ->select_sum('TF_AMT')
            ->where('SHEET_ID', $sheet_id)
            ->get('payments')
            ->row()
            ->TF_AMT;
    }
    
    /**
     * Get all payments of a sheet
     * @param int $sheet_id
     * @return array
     */
    public function get_sheet_payments($sheet_id)
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('SHEET_ID', $sheet_id)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * change all payments of a sheet from SHEET to TRANSFERRING
     * constant of TRANSFERRING = constant of SHEET + 20
     * @param int $sheet_id
     */
    public function sheet_to_transferring($sheet_id)
    {
        $this->db
            ->set('TF_STATUS_ID', 'TF_STATUS_ID + 20', false)
            ->where('SHEET_ID', $sheet_id)
            ->update('payments');
    }
    
    /**
     * Get payments in a sheet - vietcombank template
     * @param int $sheet_id
     * @param int $sheet_type
     * @return array
     */
    public function get_vcbsheet_payments($sheet_id, $sheet_type)
    {
        $this->db->simple_query('SET SESSION group_concat_max_len=' . MAX_LENGTH_PAYM_IDS);
        $this->db
            ->select_sum('TF_AMT')
            ->select('GROUP_CONCAT(CL_NO) AS CL_NO', false)
            ->select('GROUP_CONCAT(POCY_REF_NO) AS POCY_REF_NO', false)
            ->select('GROUP_CONCAT(PAYM_ID) AS PAYM_ID', false)
            ->where('SHEET_ID', $sheet_id)
            ->order_by('PROCESS_ORDER', 'ASC');
        if ($sheet_type == SHEET_TYPE_CLAIMANT)
        {
            return $this->db
                ->select('PAYMENT_METHOD, ACCT_NAME, ACCT_NO, BANK_NAME, BANK_BRANCH, BANK_CITY, BENEFICIARY_NAME, PP_NO, PP_DATE, PP_PLACE')
                ->group_by(array('PAYMENT_METHOD', 'ACCT_NAME', 'ACCT_NO', 'BANK_NAME', 'BANK_BRANCH', 'BANK_CITY', 'BENEFICIARY_NAME', 'PP_NO', 'PP_DATE', 'PP_PLACE'))
                ->get('payments')
                ->result_array();
        }
        elseif ($sheet_type == SHEET_TYPE_PARTNER)
        {
            return $this->db
                ->get('payments')
                ->row_array();
        }
    }
    
    /**
     * eject a payment out of a sheet
     * @param int $paym_id
     * @param int $tf_status_id
     */
    public function out_sheet($paym_id, $tf_status_id)
    {
        $this->db
            ->set('TF_STATUS_ID', $tf_status_id)
            ->set('SHEET_ID', null)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    
    /**
     * change payments in a sheet from TRANSFERRING to SHEET
     * contant of SHEET = constant of TRANSFERRING - 20
     * @param int $sheet_id
     */
    public function transferring_to_sheet($sheet_id)
    {
        $this->db
            ->set('TF_STATUS_ID', 'TF_STATUS_ID - 20', false)
            ->where('SHEET_ID', $sheet_id)
            ->update('payments');
    }
    
    /**
     * change payment from TRANSFERRING to TRANSFERRED
     * constant of TRANSFERRED = constant of TRANSFERRING + 20
     * @param int $paym_id
     * @param date $tf_date
     * @param date $vcb_seq for Claims which have Payment Method is TT or CH
     * @param bool $process_order set process_order or not
     */
    public function transferring_to_transferred($paym_id, $tf_date, $vcb_seq)
    {
        $this->db
            ->set('TF_STATUS_ID', 'TF_STATUS_ID + 20', false)
            ->set('TF_DATE', $tf_date)
            ->set('VCB_SEQ', $vcb_seq)
            ->set('YN_CLBO', 'N')
            ->set('TF_NO', 'TF_NO + 1', false)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    
    /**
     * Get all transferring payments which are not in any sheets
     * @return int
     */
    public function get_transferring_payments()
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->where('sys.TF_STATUS_ID', TF_STATUS_TRANSFERRING)
            ->where('SHEET_ID', null)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * Get payments which Transfer Status is TF_STATUS_APPROVED and Payment Method is PP
     * Get payments which Transfer Status is TF_STATUS_DLVN_CANCEL or TF_STATUS_DLVN_PAYPREM
     * @return array
     */
    public function get_payments_for_partner()
    {
        return $this->db
            ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            ->group_start()
                ->where('sys.TF_STATUS_ID', TF_STATUS_APPROVED)
                ->where('PAYMENT_METHOD', 'PP')
            ->group_end()
            ->or_where('sys.TF_STATUS_ID', TF_STATUS_DLVN_CANCEL)
            ->or_where('sys.TF_STATUS_ID', TF_STATUS_DLVN_PAYPREM)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * API - Get all payments of a claim no
     * @param int $cl_no
     * @return array
     */
    public function get_payments_by_claim($cl_no)
    {
        return $this->db
            ->select('PAYM_ID, CL_NO, PAYMENT_TIME, TF_AMT, DEDUCT_AMT, DISC_AMT, TF_DATE, sys.TF_STATUS_ID, TF_STATUS_NAME, TF_NO, CL_USER')
            ->join('sys_transfer_status sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
            // ->join('sheets', 'sheets.SHEET_ID = payments.SHEET_ID', 'left')
            ->where('CL_NO', $cl_no)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * change payment from transferred to transferring
     * @param int $paym_id
     * @param date $prev_tf_date
     * @param string $prev_vcb_seq
     * @param char(1) $prev_yn_clbo
     */
    public function transferred_to_transferring($paym_id, $prev_tf_date, $prev_vcb_seq, $prev_yn_clbo)
    {
        $this->db
            ->set('TF_STATUS_ID', TF_STATUS_TRANSFERRING)
            ->set('TF_DATE', $prev_tf_date)
            ->set('VCB_SEQ', $prev_vcb_seq)
            ->set('YN_CLBO', $prev_yn_clbo)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    /**
     * Get TF_STATUS_TRANSFERRED payments with specific claim no and tf_amt
     * @param int $cl_no
     * @return array
     */
    public function get_transferred_payments_by_claim($cl_no, $tf_amt)
    {
        return $this->db
            ->where('CL_NO', $cl_no)
            ->where('TF_AMT', $tf_amt)
            ->where('TF_STATUS_ID', TF_STATUS_TRANSFERRED)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * Get all payments which have specific status
     * @param int $tf_status_id Transfer Status ID
     * @return array
     */
    public function get_status_payments($tf_status_id)
    {
        return $this->db
            ->where('TF_STATUS_ID', $tf_status_id)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * Update Payment Information of a Payment
     * @param int $paym_id
     * @param array $data
     */
    public function set_payment_info($paym_id, $data)
    {
        $this->db
            ->where('PAYM_ID', $paym_id)
            ->update('payments', $data);
    }
    
    /**
     * Count number of payments of a user for each status
     * @param string $user_name User Name
     * @param int $tf_status_id Transfer Status
     * @return int
     */
    public function count_user_payments($user_name, $tf_status_id)
    {
        return $this->db
            ->where('CL_USER', $user_name)
            ->where('TF_STATUS_ID', $tf_status_id)
            ->count_all_results('payments');
    }
    
    /**
     * Get payments, which has been assigned to user, with specific status
     * @param string $user_name User Name
     * @param int $tf_status_id Transfer Status
     * @return array
     */
    public function get_user_payments($user_name, $tf_status_id)
    {
        return $this->db
            ->where('CL_USER', $user_name)
            ->where('TF_STATUS_ID', $tf_status_id)
            ->where('IS_DELETED', 0)
            ->get('payments')
            ->result_array();
    }
        
    /**
     * Count number of payments of a team for each status
     * @param int $leader_id Leader ID
     * @param int $tf_status_id Transfer Status
     * @return int
     */
    public function count_team_payments($leader_id, $tf_status_id)
    {
        return $this->db
            ->join('users', 'payments.CL_USER = users.USER_NAME')
            ->where('LEADER_ID', $leader_id)
            ->where('TF_STATUS_ID', $tf_status_id)
            ->count_all_results('payments');
    }
    
    /**
     * Get payments, which has been assigned to users of a team, with specific status
     * @param int $leader_id Leader ID
     * @param int $tf_status_id Transfer Status
     * @return array
     */
    public function get_team_payments($leader_id, $tf_status_id)
    {
        return $this->db
            ->join('users', 'payments.CL_USER = users.USER_NAME')
            ->where('LEADER_ID', $leader_id)
            ->where('TF_STATUS_ID', $tf_status_id)
            ->get('payments')
            ->result_array();
    }
    
    /**
     * Get ids of payments which have same claim no
     * @param int $cl_no
     * @return array of payments
     */
    public function get_relationship_payments($cl_no)
    {
        return $this->db
            ->select('PAYM_ID, PAYMENT_TIME')
            ->where('CL_NO', $cl_no)
            ->order_by('PAYM_ID', 'ASC')
            ->get('payments')
            ->result_array();
    }
    
    /**
     * get paid (total transfer) amount of a claim no
     * @param int $cl_no
     * @return int
     */
    public function get_paid_amt($cl_no)
    {
        return intval($this->db
            ->select('SUM(TF_AMT) + SUM(DEDUCT_AMT) + SUM(DISC_AMT) as PAID_AMT')
            ->where('CL_NO', $cl_no)
            ->where('TF_STATUS_ID >=', TF_STATUS_SHEET)
            ->where('TF_STATUS_ID <=', TF_STATUS_RETURNED_TO_CLAIM)
            ->get('payments')->row()->PAID_AMT);
    }
    
    /**
     * Count number of payments for specifiec status
     * @param int $tf_status_id Transfer Status
     * @return int
     */
    public function count_status($tf_status_id)
    {
        return $this->db
            ->where('TF_STATUS_ID', $tf_status_id)
            ->count_all_results('payments');
    }
   
   
   
   
   
   
    /**
     * get list of payments in Claim Bordereaux report at specified time
     * @param int $month
     * @param int $year
     */
    public function get_claim_bordereaux($month, $year)
    {
        $this->db->select('CB_DATE, MEMB_NAME, MEMB_REF_NO, POCY_REF_NO, CL_NO, INV_NO, PRES_AMT, TF_AMT, DEDUCT_AMT, BEN_TYPE, APP_DATE, PAYEE, CB_TF_AMT');
        $this->db->join('payments', 'payments.PAYM_ID = claim_bordereaux.PAYM_ID_FK');
        $this->db->where('MONTH(CB_DATE)', $month);
        $this->db->where('YEAR(CB_DATE)', $year);
        $query = $this->db->get('claim_bordereaux');
        return $query->result_array();
    }
       
    /**
     * Get the list of claim fund reports
     * @return array
     */
    public function get_list_of_claim_fund_reports()
    {
        $this->db->distinct();
        $this->db->select('CF_DATE');
        $this->db->where('CF_DATE IS NOT NULL', null);
        $this->db->order_by('CF_DATE', 'ASC');
        $query = $this->db->get('payments');
        return $query->result_array();
    }
    
    /**
     * Get last payment of a Claim No
     * @param int $cl_no
     * @return array: a payment
     */
    public function get_max_id_of_mantis_id($mantis_id)
    {
        return $this->db
            ->select_max('PAYM_ID')
            ->where('MANTIS_ID', $mantis_id)
            ->get('payments')
            ->row()->PAYM_ID;
    }
    
    /**
     * get max payment time of a claim no
     * @param int $cl_no
     * @return int
     */
    private function _get_max_payment_time($cl_no)
    {
        return intval($this->db
            ->select_max('PAYMENT_TIME')
            ->where('CL_NO', $cl_no)
            ->get('payments')
            ->row()->PAYMENT_TIME);
    }
    
    /**
     * Get payment with specific claim no and transfer times
     * @param int $cl_no
     * @param int $tf_times
     * @return an array
     */
    public function get_payment_by_claim($cl_no, $tf_times)
    {
        $this->db->where('CL_NO', $cl_no);
        $this->db->where('TF_TIMES', $tf_times);
        $query = $this->db->get('payments');
        return $query->row_array();
    }
    
    /**
     * Get a transferred payment with specific VCB SEQ, Transfer Date and Transfer Amount
     * @return array: list of payments
     */
    public function get_payment_by_vnbt($vcb_seq, $tf_amt, $tf_date)
    {
        $this->db->where('VCB_SEQ', $vcb_seq);
        $this->db->where('TF_DATE', $tf_date);
        $this->db->where('TF_AMT', $tf_amt);
        $this->db->where('TF_STATUS_ID', TF_STATUS_TRANSFERRED);
        $query = $this->db->get('payments');
        return $query->row_array();
    }
    
    /**
     * Every claim has no or only one unpaid payment so get the unpaid payment if exists
     * @param int $cl_no
     * @return an array
     */
    public function get_unpaid_payment($cl_no)
    {
        return $this->db
            ->where('CL_NO', $cl_no)
            ->where('TF_STATUS_ID >', TF_STATUS_DELETED)
            ->where('TF_STATUS_ID <', TF_STATUS_SHEET)
            ->get('payments')
            ->row_array();
    }
    
    /**
     * Get max process order of payments table
     * @return int
     */
    public function get_max_process_order()
    {
        return $this->db->select_max('PROCESS_ORDER')
            ->get('payments')
            ->row()->PROCESS_ORDER;
    }
    
    /**
     * search all payments which are matching conditions
     * @param array $condition any columns of payments table
     * @return array of array
     */
    public function search_payments($condition)
    {
        $this->db->select('payments.* , sys.* , sheets.* , banker.URL_UNC , renew_claim_new.REASON');
        $this->db->from('payments');
        $this->db->join('sys_transfer_status sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID');
        $this->db->join('sheets', 'sheets.SHEET_ID = payments.SHEET_ID', 'left');
        $this->db->join('banker', 'payments.PAYM_ID = banker.PAYM_ID', 'left');
        $this->db->join('(
                    SELECT
                        `renew_claim`.`PAYM_ID`,
                        `renew_claim`.`REASON`
                    FROM
                        `renew_claim`
                    JOIN(
                        SELECT
                            `renew_claim`.`PAYM_ID`,
                            MAX(`renew_claim`.`UPD_DATE`) `UPD_DATE`
                        FROM
                            renew_claim
                        GROUP BY
                            `renew_claim`.`PAYM_ID`
                    ) AS lt
                WHERE
                    `renew_claim`.`PAYM_ID` = lt.`PAYM_ID` AND `renew_claim`.`UPD_DATE` = lt.`UPD_DATE`
                GROUP BY
                    PAYM_ID
                ) AS renew_claim_new', 'payments.PAYM_ID = renew_claim_new.PAYM_ID', 'left');
        foreach ($condition as $key => $val)
        {
            $this->db->where($key, $val);
        }
        return $this->db->get()->result_array();
    }
    
    /**
     * set value for a Payment's field
     * @param int $paym_id
     * @param string $field
     * @param any $value
     */
    public function set_value($paym_id, $field, $value)
    {
        $this->db->set($field, $value)
            ->where('PAYM_ID', $paym_id)
            ->update('payments');
    }
    
    /**
     * set value for specified fields of a payment
     * @param int $paym_id
     * @param array $data
     */
    public function set_values($paym_id, $data)
    {
        $this->db->where('PAYM_ID', $paym_id)
            ->update('payments', $data);
    }
    
    /**
     * After query get all claim information of a bug id from HBS, update/insert it's information to Claim Payment System
     * @param array $claim
     */
    public function update_payments_table($claim)
    {
        // format Bank Account and PASSPORT NO
        $claim['ACCT_NO'] = preg_replace('/[^0-9a-zA-Z]/', '', $claim['ACCT_NO']);
        $claim['PP_NO'] = preg_replace('/[^0-9a-zA-Z]/', '', $claim['PP_NO']);
        
        // in case Claim Type is Provider, Bank Account is Payee
        if ($claim['CL_TYPE'] === 'P')
        {
            $claim['ACCT_NAME'] = $claim['PAYEE'];
        }
        // round all amount to integer
        $claim['APP_AMT'] = round($claim['APP_AMT']);
        $claim['PRES_AMT'] = round($claim['PRES_AMT']);
        
        if ( ! $this->_payment_exists($claim['CL_NO']) && $claim['APP_AMT'] > 0)
        {
            // not exist, create a payment record of claim no
            $claim['TF_AMT'] = $claim['APP_AMT'];
            $this->db->insert('payments', $claim);
            return;
        }
        // payment existed, sync Mantis ID of the Claim No
        $this->_sync_mantis_id($claim['CL_NO'], $claim['MANTIS_ID']);
        
        // calculate new Transfer Amt = new Approved Amt - Paid Amount
        $paid_amt = $this->get_paid_amt($claim['CL_NO']);
        $claim['TF_AMT'] = $claim['APP_AMT'] - $paid_amt;
        
        // get the Unpaid Payment of Claim No
        $payment = $this->get_unpaid_payment($claim['CL_NO']);
        if (empty($payment))
        {
            if ($claim['TF_AMT'] > 0)
            {
                $claim['PAYMENT_TIME'] = $this->_get_max_payment_time($claim['CL_NO']) + 1;
                $this->db->insert('payments', $claim);
            }
        }
        else
        {
            $this->load->model('payments_history_model');
            if ($claim['TF_AMT'] > 0)
            {
                if ($claim['TF_AMT'] != $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'])
                {
                    if ($payment['TF_STATUS_ID'] != TF_STATUS_NEW)
                    {
                        $claim['TF_STATUS_ID'] = TF_STATUS_NEW;
                        $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_UPDATE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_NEW);
                    }
                    if ($payment['DEDUCT_AMT'] > 0)
                    {
                        if ($claim['TF_AMT'] > $payment['DEDUCT_AMT'])
                        {
                            $claim['DEDUCT_AMT'] = $payment['DEDUCT_AMT'];
                            $claim['TF_AMT'] -= $payment['DEDUCT_AMT'];
                        }
                        else
                        {
                            $claim['DEDUCT_AMT'] = 0;
                            $this->debt_model->pay_debt_deduction($payment['MEMB_NAME'], $payment['MEMB_REF_NO'], - $payment['DEDUCT_AMT'], $payment['CL_NO']);
                            $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_UPDATE, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], 0);
                        }
                    }
                    if ($payment['DISC_AMT'] > 0)
                    {
                        $claim['DISC_AMT'] = 0;
                        $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_UPDATE, HIST_FIELD_DISC_AMT, $payment['DISC_AMT'], 0); 
                    }
                    $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_UPDATE, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $claim['TF_AMT']);
                }
            }
            else
            {
                $claim['TF_STATUS_ID'] = TF_STATUS_DELETED;
                $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_DELETE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_DELETED);
                if ($payment['DEDUCT_AMT'] > 0)
                {
                    $claim['DEDUCT_AMT'] = 0;
                    $this->debt_model->pay_debt_deduction($payment['MEMB_NAME'], $payment['MEMB_REF_NO'], - $payment['DEDUCT_AMT'], $payment['CL_NO']);
                    $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_DELETE, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], 0);
                }
                if ($payment['DISC_AMT'] > 0)
                {
                    $claim['DISC_AMT'] = 0;
                    $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_DELETE, HIST_FIELD_DISC_AMT, $payment['DISC_AMT'], 0); 
                }
                $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_SYSTEM_DELETE, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $claim['TF_AMT']);
            }
            $this->db->where('PAYM_ID', $payment['PAYM_ID'])->update('payments', $claim);
        }
    }
    
    /**
     * Check if there is exist a payment of a claim no
     * @param int cl_no integer representing claim no
     * @return bool true if payment exists, false otherwise
     */
    private function _payment_exists($cl_no)
    {
        $result = $this->db->where('CL_NO', $cl_no)->get('payments');
        if ($result->num_rows())
        {
            return true;
        }
        return false;
    }
    
    /**
     * Sync mantis id of a claim no
     * @param int cl_no
     * @param int mantis_id
     */
    private function _sync_mantis_id($cl_no, $mantis_id)
    {
        $this->db->set('MANTIS_ID', $mantis_id)
            ->where('CL_NO', $cl_no)
            ->update('payments');
    }
    
    /**
     * get value for a Payment's field
     * @param int $paym_id
     * @param string $field
     * @param any $value
     */
    public function get_value($paym_id, $field)
    {
        return $this->db
            ->select($field)
            ->get_where('payments', array('PAYM_ID' => $paym_id))
            ->row()
            ->$field;
    }
    
    /**
     * Get payment of a claim no which status is APPROVED/NEW - It should have only one
     * @param int $cl_no
     * @return array
     */
    public function get_approved_payment_by_cl_no($cl_no)
    {
        return $this->db
            ->select('PAYM_ID, CL_NO, MEMB_NAME, POCY_REF_NO, MEMB_REF_NO, PRES_AMT, APP_AMT, TF_AMT, DEDUCT_AMT, DISC_AMT, PAYMENT_METHOD, MANTIS_ID')
            ->group_start()
                ->where('TF_STATUS_ID', TF_STATUS_APPROVED)
                ->or_where('TF_STATUS_ID', TF_STATUS_NEW)
            ->group_end()
            ->where('CL_NO', $cl_no)
            ->get('payments')
            ->row_array();
    }
    
    /**
     * Get payment of a claim no which status is NEW - It should have only one
     * @param int $cl_no
     * @return array
     */
    public function get_new_payment_by_cl_no($cl_no)
    {
        return $this->db
            ->select('PAYM_ID, CL_NO, MEMB_NAME, POCY_REF_NO, MEMB_REF_NO, PRES_AMT, APP_AMT, TF_AMT, DEDUCT_AMT, DISC_AMT, PAYMENT_METHOD, MANTIS_ID')
            ->where('TF_STATUS_ID', TF_STATUS_NEW)
            ->where('CL_NO', $cl_no)
            ->get('payments')
            ->row_array();
    }

    /**
     * Get payment have paym_ids
     * @param int $paym_ids
     * @return array
     */
    public function get_payment_by_paym_ids($paym_ids)
    {
        $query = $this->db->select('payments.* , sys.* , banker.URL_UNC')
        ->join('sys_transfer_status as sys', 'sys.TF_STATUS_ID = payments.TF_STATUS_ID')
        ->join('banker', 'payments.PAYM_ID = banker.PAYM_ID', 'left')
        ->where_in('payments.	PAYM_ID', $paym_ids);
        return $query->get('payments')->result_array();
    }

    /**
     * Get payment have paym_ids distenc paydate
     * @param int $paym_ids
     * @return array
     */
    public function get_distinct_tf_date_by_paym_ids($paym_ids)
    {
        $query = $this->db->select('payments.TF_DATE')->distinct('payments.TF_DATE')
        ->where_in('payments.	PAYM_ID', $paym_ids);
        return $query->get('payments')->result_array();
    }

    /**
     * Get all payments which have renew status and reson
     * @param int $tf_status_id Transfer Status ID
     * @return array
     */
    public function get_renewed_payments()
    {
        return $this->db
            ->where('TF_STATUS_ID', TF_STATUS_NEW)
            ->join('(SELECT RENEW_ID, rn.PAYM_ID ,rn.REASON, ROW_NUMBER() OVER (PARTITION BY PAYM_ID ORDER BY RENEW_ID DESC) as numr  from  renew_claim as rn ) as T2','payments.PAYM_ID=T2.PAYM_ID')
            ->where('numr', 1)
            ->get('payments')
            ->result_array();
    }
}
