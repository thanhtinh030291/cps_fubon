<?php
/**
 * This Class contains all the business logic and the persistence layer which connection to Mantis Server is required
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Mantis_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Connect to Mantis Database
     * @return database object
     */
    public function connect_mantis_db()
    {
        $mantis_db = $this->load->database("mantis_dlvn", TRUE);
        $error = $mantis_db->error();
        if ( ! empty($error['code']))
        {
            return FALSE;
        }
        return $mantis_db;
    }
    
    /**
     * Get all data in cps_trigger table at Mantis Database Server
     * @param @mantis_db database object
     */
    public function get_cps_trigger($mantis_db)
    {
        return $mantis_db->get('cps_trigger')->result_array();
    }
    
    /**
     * Empty the cps_trigger table after running Task Scheduler
     * @param @mantis_db database object
     */
    public function clear_cps_trigger($mantis_db)
    {
        $mantis_db->empty_table('cps_trigger');
    }
    
    /**
     * Update data of [Mantis] hbs_data table
     * @param $mantis_db database object of Mantis
     * @param $data array
     */
    public function update_hbs_data($mantis_db, $data)
    {
        $mantis_db->set('cl_no', $data['CL_NO'])
            ->set('bug_id', $data['BARCODE'])
            ->set('pres_amt', $data['PRES_AMT'])
            ->set('prov_name', $data['PROV_NAME'])
            ->set('mbr_last_name', vn_to_str(mb_strtolower($data['MBR_LAST_NAME'])))
            ->set('mbr_mid_name', vn_to_str(mb_strtolower($data['MBR_MID_NAME'])))
            ->set('mbr_first_name', vn_to_str(mb_strtolower($data['MBR_FIRST_NAME'])))
            ->replace('hbs_data');
    }
    
    /**
     * Update steps_to_reproduce of mantis_bug_text_table
     * @param $mantis_db database object of Mantis
     * @param $data array
     */
    public function update_steps_to_reproduce($mantis_db, $data)
    {
        $mantis_db->query("
            UPDATE mantis_bug_text_table bug_text
            JOIN mantis_bug_table bug ON bug.bug_text_id = bug_text.id
            SET steps_to_reproduce = '{$data['CL_NO']} - {$data['MEMB_REF_NO']}'
            WHERE bug.id = {$data['BARCODE']}
        ");
    }
    
    /**
     * Update data of [Mantis] cps_data table
     * @param $mantis_db database object of Mantis
     * @param $data array
     */
    public function update_cps_data($mantis_db, $data)
    {
        $mantis_db->set('tran_id', $data['TRAN_ID'])
            ->set('bug_id', $data['MANTIS_ID'])
            ->set('cl_no', $data['CL_NO'])
            ->set('tf_times', $data['PAYMENT_TIME'])
            ->set('tf_no', $data['TRAN_TIMES'])
            ->set('tf_amt', $data['TF_AMT'])
            ->set('tf_date', $data['TF_DATE'])
            ->set('tf_status', $data['TRAN_STATUS'])
            ->set('tf_type', $data['TF_STATUS_ID'])
            ->set('vcb_seq', $data['TF_VCB_SEQ'])
            ->set('mdtc', $data['MDTC'])
            ->set('returned_date', $data['REFUND_DATE'])
            ->set('returned_vcb_seq', $data['REFUND_VCB_SEQ'])
            ->set('returned_reason', $data['REFUND_REASON'])
            ->set('payment_method', $data['PAYMENT_METHOD'])
            ->set('acct_name', $data['ACCT_NAME'])
            ->set('acct_no', $data['ACCT_NO'])
            ->set('bank_name', $data['BANK_NAME'])
            ->set('bank_city', $data['BANK_CITY'])
            ->set('bank_branch', $data['BANK_BRANCH'])
            ->set('beneficiary_name', $data['BENEFICIARY_NAME'])
            ->set('pp_date', $data['PP_DATE'])
            ->set('pp_place', $data['PP_PLACE'])
            ->set('pp_no', $data['PP_NO'])
            ->replace('cps_data');
    }
    
    /**
     * Get conflict bug_id of cl_no
     * @param $mantis_db database object of Mantis
     */
    public function get_same_cl_no_but_bug_id($mantis_db)
    {
        return $mantis_db->select('cps.cl_no, cps.bug_id cps_bug_id, hbs.bug_id hbs_bug_id')
            ->join('hbs_data as hbs', 'cps.cl_no = hbs.cl_no')
            ->where('cps.bug_id >', 0)
            ->where('cps.bug_id <> hbs.bug_id')
            ->get('cps_data cps')
            ->result_array();
    }
    
    /**
     * Get conflict cl_no of bug_id
     * @param $mantis_db database object of Mantis
     */
    public function get_same_bug_id_but_cl_no($mantis_db)
    {
        return $mantis_db->select('cps.bug_id, cps.cl_no cps_cl_no, hbs.cl_no hbs_cl_no')
            ->join('hbs_data as hbs', 'cps.bug_id = hbs.bug_id')
            ->where('cps.cl_no <> hbs.cl_no')
            ->get('cps_data cps')
            ->result_array();
    }
    
    /**
     * Get Claim bugs which status is CLOSED
     * @param object $mantis_db database object
     * @param array $bug_ids maximum 1000 bug_id
     */
    public function bug_get_closed_status($mantis_db, $bug_ids)
    {
        return $mantis_db->select('id')
            ->where('status', MANTIS_STATUS_CLOSED)
            ->where_in('id', $bug_ids)
            ->get('mantis_bug_table')
            ->result_array();
    }
    
    /**
     * Get Claim GOP bugs which status is DECLINED 
     * @param object $mantis_db database object
     * @param array $bug_ids maximum 1000 bug_id
     */
    public function bug_get_declined_status($mantis_db, $bug_ids)
    {
        return $mantis_db->select('id')
            ->where('status', MANTIS_STATUS_DECLINED)
            ->where('project_id', MANTIS_PROJECT_CL_GOP)
            ->where_in('id', $bug_ids)
            ->get('mantis_bug_table')
            ->result_array();
    }
}
