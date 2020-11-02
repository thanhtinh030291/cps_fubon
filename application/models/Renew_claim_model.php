<?php
/**
 * This Class contains all the business logic and the persistence layer for the notes.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Renew_claim_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Add a note of a payment
     * @param int $paym_id Payment ID
     * @param int $type Type of note: Reject, ...
     * @param int $text Note's content
     */
    public function add($paym_id, $text)
    {
        $this->db
            ->set('PAYM_ID', $paym_id)
            ->set('USERNAME', $this->session->userdata('user_name'))
            ->set('REASON', $text)
            ->insert('renew_claim');
    }
    
    /**
     * Get payment's notes with specific PARQ_ID
     * @param int $paym_id
     * @return an array
     */
    public function get($paym_id)
    {
        return $this->db
            ->where('PAYM_ID', $paym_id)
            ->get('renew_claim')
            ->result_array();
    }
    
    /**
     * Get last renew of a payment
     * @param int $paym_id
     * @return string
     */
    public function get_last_renew($paym_id)
    {
        $row = $this->db
            ->limit(1)
            ->where('PAYM_ID', $paym_id)
            ->order_by('RENEW_ID', 'DESC')
            ->get('renew_claim')
            ->row_array();
        if (empty($row))
        {
            return '';
        }
        return $row['REASON'];
    }
}
