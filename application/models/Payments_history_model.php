<?php
/**
 * This Class contains all the business logic and the persistence layer for the payments.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_history_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * get previous transfer status of a payment
     * @param int $paym_id
     * @return int
     */
    public function get_prev_status($paym_id)
    {
        $query = $this->db
            ->limit(1)
            ->select('OLD_VALUE')
            ->where('PAYM_ID', $paym_id)
            ->where('FIELD', HIST_FIELD_TF_STATUS)
            ->order_by('CRT_DATE', 'DESC')
            ->get('payments_history');
        if ($query->num_rows() === 0)
        {
            return false;
        }
        return $query->row()->OLD_VALUE;
    }
    
    /**
     * record an action of user
     * @param int $paym_id
     * @param int $type action of user
     * @param int $field_id
     * @param string $old_value
     * @param string $new_value
     * @return array of array
     */
    public function add($paym_id, $type, $field_id = null, $old_value = null, $new_value = null)
    {
        $this->db
            ->set('PAYM_ID', $paym_id)
            ->set('HIST_TYPE', $type)
            ->set('USER_NAME', $this->session->userdata('user_name'))
            ->set('FIELD', $field_id)
            ->set('OLD_VALUE', $old_value)
            ->set('NEW_VALUE', $new_value)
            ->insert('payments_history');
    }
    
    /**
     * get previous value of a field of a payment
     * @param int $paym_id
     * @param int $hist_field_id
     * @return any
     */
    public function get_prev_field($paym_id, $hist_field_id, $hist_type = HIST_TYPE_FI_PAY)
    {
        $query = $this->db
            ->limit(1)
            ->select('OLD_VALUE')
            ->where('PAYM_ID', $paym_id)
            ->where('FIELD', $hist_field_id)
            ->where('HIST_TYPE', $hist_type)
            ->order_by('CRT_DATE', 'DESC')
            ->get('payments_history');
        if ($query->num_rows() === 0)
        {
            return false;
        }
        return $query->row()->OLD_VALUE;
    }
    
    /**
     * get history of a payment
     * @param int $paym_id
     * @return array of array
     */
    public function get_payment_history($paym_id)
    {
        return $this->db
            ->select('payments_history.*, sys.HIST_DESC, sys2.*, sys3.TF_STATUS_NAME as OLD_STATUS, sys3.TF_STATUS_COLOR as OLD_COLOR, sys4.TF_STATUS_NAME as NEW_STATUS, sys4.TF_STATUS_COLOR as NEW_COLOR')
            ->join('sys_history_type sys', 'sys.HIST_TYPE = payments_history.HIST_TYPE')
            ->join('sys_history_field sys2', 'sys2.HIST_FIELD = payments_history.FIELD', 'left')
            ->join('sys_transfer_status sys3', 'sys3.TF_STATUS_ID = payments_history.OLD_VALUE AND payments_history.FIELD = ' . HIST_FIELD_TF_STATUS, 'left')
            ->join('sys_transfer_status sys4', 'sys4.TF_STATUS_ID = payments_history.NEW_VALUE AND payments_history.FIELD = ' . HIST_FIELD_TF_STATUS, 'left')
            ->where('PAYM_ID', $paym_id)
            ->get('payments_history')
            ->result_array();
    }
}
