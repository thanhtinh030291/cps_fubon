<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settle_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Connect to Database
     * @peram string Database Name
     * @return database object
     */
    public function connect_db($db_name)
    {
        $db = $this->load->database($db_name, TRUE);
        $error = $db->error();
        if ( ! empty($error['code']))
        {
            return FALSE;
        }
        return $db;
    }
    
    /**
     * Get list of uploaded
     * @param object $cps_db Database Object
     * @return list of crt_date
     */
    public function get_list_of_upload($cps_db)
    {
        return $cps_db->distinct()->select('CRT_DATE')->order_by('CRT_DATE', 'DESC')
            ->get('settle')->result_array();
    }
    
    /**
     * Get data of an upload by using crt_date
     * @param object $cps_db Database Object
     * @param date $crt_date
     * @return array
     */
    public function get_data($cps_db, $crt_date)
    {
        return $cps_db->get_where('settle', array('CRT_DATE' => $crt_date))->result_array();
    }
    
    /**
     * Get data of settle note by using crt_date
     * @param object $cps_db Database Object
     * @param date $crt_date
     * @return array
     */
    public function get_data_settle_note($cps_db, $crt_date)
    {
        return $cps_db->get_where('settle_note', array('SETTLE_CRT_DATE' => $crt_date))
            ->result_array();
    }
    
    /**
     * Get fields of data of an upload by using crt_date
     * @param object $cps_db Database Object
     * @param date $crt_date
     * @param string $fields
     * @return array
     */
    public function get_fields($cps_db, $crt_date, $fields)
    {
        return $cps_db->select($fields)->get_where('settle', array('CRT_DATE' => $crt_date))->result_array();
    }
    
    /**
     * Upload Settle Payment
     * @param object $cps_db Database Object
     * @param $data list of array
     * @param $bug_id int 
     */
    public function insert_batch($cps_db, $data)
    {
        $cps_db->insert_batch('settle', $data);
    }
    
    /**
     * Delete Debit Note No of an upload
     * @param object $cps_db Database Object
     * @param date $crt_date
     */
    public function clear_note_no($cps_db, $crt_date)
    {
        $cps_db->set('DEBIT_NOTE_NO', null)->where('CRT_DATE', $crt_date)->update('settle');
        $cps_db->where('SETTLE_CRT_DATE', $crt_date)->delete('settle_note');
    }
    
    /**
     * Get Debit Note No of a list of Policy Ref No
     * @param object $hbs_db Database Object
     * @param $pocy_ref_no_list list of Policy Ref No
     * @return array - POCY_REF_NO, NOTE_NO, AMT
     */
    public function get_note_no($hbs_db, $pocy_ref_no_list)
    {
        $data = "'" . implode("', '", $pocy_ref_no_list) . "'";
        return $hbs_db->query("
            SELECT POCY_REF_NO, NOTE_NO, AMT
            FROM Vw_Bf_Debit_Note
            WHERE STATUS = 'BFTX_STATUS_O'
            AND POCY_REF_NO IN ($data)
        ")->result_array();
    }
    
    /**
     * Update Debit Note No
     * @param object $cps_db Database Object
     * @param date $crt_date
     * @param array $notes
     * @return array
     */
    public function update_debit_note($cps_db, $crt_date, $notes)
    {
        $data = [];
        foreach ($notes as $note)
        {
            $cps_db->set('DEBIT_NOTE_NO', $note['NOTE_NO'])
                ->where('POCY_REF_NO', $note['POCY_REF_NO'])
                ->where('RECEIVED_AMOUNT', $note['AMT'])
                ->where('CRT_DATE', $crt_date)
                ->update('settle');
            if (empty($cps_db->affected_rows()))
            {
                $note['SETTLE_CRT_DATE'] = $crt_date;
                $data[] = $note;
            }
        }
        $cps_db->insert_batch('settle_note', $data);
    }
}
