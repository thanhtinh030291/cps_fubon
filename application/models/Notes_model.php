<?php
/**
 * This Class contains all the business logic and the persistence layer for the notes.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Notes_model extends CI_Model {

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
    public function add($paym_id, $type, $text)
    {
        $this->db
            ->set('PAYM_ID', $paym_id)
            ->set('NOTE_TYPE', $type)
            ->set('NOTE_ROLE', $this->session->userdata('role_id'))
            ->set('NOTE_USER', $this->session->userdata('user_name'))
            ->set('NOTE_TEXT', $text)
            ->insert('notes');
    }
    
    /**
     * Get payment's notes with specific PARQ_ID
     * @param int $paym_id
     * @return an array
     */
    public function get($paym_id)
    {
        return $this->db
            ->join('sys_note_type sys', 'sys.NOTE_TYPE = notes.NOTE_TYPE')
            ->join('roles', 'roles.ROLE_ID = notes.NOTE_ROLE')
            ->where('PAYM_ID', $paym_id)
            ->get('notes')
            ->result_array();
    }
    
    /**
     * Get last note of a payment
     * @param int $paym_id
     * @return string
     */
    public function get_last_note($paym_id)
    {
        $row = $this->db
            ->limit(1)
            ->where('PAYM_ID', $paym_id)
            ->order_by('NOTE_ID', 'DESC')
            ->get('notes')
            ->row_array();
        if (empty($row))
        {
            return '';
        }
        return $row['NOTE_TEXT'];
    }
}
