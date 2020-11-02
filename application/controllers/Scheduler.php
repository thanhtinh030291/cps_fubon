<?php
/**
 * This controller do some tasks scheduler
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;

class Scheduler extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Task Scheduler will call this function only
     * Depend on schedule time, one or many functions will be running
     */
    public function index()
    {
        set_time_limit(0);
        $this->load->model('scheduler_model');
        
        $tasks = $this->scheduler_model->get_available_tasks();
        if (empty($tasks))
        {
            return;
        }
        foreach ($tasks as $task)
        {
            $start_time = date('Y-m-d H:i:s');
            $func = $task['SCHE_NAME'];
            $this->$func();
            $end_time = date('Y-m-d H:i:s');
            $next_time = $this->_add_gap($task['NEXT_TIME'], $task['GAP']);
            while ($next_time < $end_time)
            {
                $next_time = $this->_add_gap($next_time, $task['GAP']);
            }
            $this->scheduler_model->update_time($task['SCHE_ID'], $next_time, $start_time, $end_time);
        }
    }
    
    /**
     * Add a gap to a datetime to get next datetime
     * @param $datetime datetime
     * @param $gap DateInterval object
     * @return datetime
     */
    private function _add_gap($datetime, $gap)
    {
        $datetime = new DateTime($datetime);
        $datetime->add(new DateInterval($gap));
        return $datetime->format('Y-m-d H:i:s');
    }
    
    /**
     * Intended Time: every 15 minutes each day, from 8:00 to 21:00
     * First, get new ACCEPTED/PARTIALLY ACCEPTED bug ids in Mantis DLVN - cps_dlvn table
     * Then, get all payment info of these bug ids in HBS DLVN
     * Last, insert/update it's information to Claim Payment System (CPS) DLVN
     */
    private function _query_payments()
    {
        
        set_time_limit(0);
        $this->load->model('hbs_model');
        $this->load->model('payments_model');
        $this->load->model('mantis_model');
        $this->session->set_userdata(array('user_name' => 'admin'));

        $hbs_db = $this->hbs_model->connect_hbs_db();
        $now = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $run_date = $now->modify('-120 days')->format('Y-m-d');
        $claims = $this->hbs_model->get_claims($hbs_db, $run_date);
        if (empty($claims))
        {
            return;
        }
        
        $claims = array_chunk(array_column($claims, 'CLAM_OID'), 1000);
        
        $this->db->trans_start();
        foreach ($claims as $cl_chunk)
        {   
            $payments = $this->hbs_model->get_claim_payment_chunk($hbs_db, $cl_chunk);
            foreach ($payments as $payment)
            {
                // $mantis_db= $this->mantis_model->connect_mantis_db();
                // $payment['MANTIS_ID'] = $this->mantis_model->find_bug_id($mantis_db, $payment['CL_REF_NO']);
                // $payment['CL_USER'] =  $this->mantis_model->get_claim_user($mantis_db, $payment['MANTIS_ID']);
                
                $this->payments_model->update_payments_table($payment);
                
            }
        }
        $this->db->trans_complete();
    }
    
    /**
     * Intended Time: 1 time each day at 21:15
     * issues are accepted but there are no payment requests because of claims are not done in HBS
     * check every day to ensure when claims are done in HBS so system will generate payment requests for these claims in CPS
     */
    private function _check_bug_not_exists()
    {
        set_time_limit(0);
        $this->load->model('log_model');
        $this->load->model('mantis_model');
        $this->load->model('hbs_model');
        $this->load->model('payments_model');
        $this->session->set_userdata(array('user_name' => 'admin'));
        $mantis_db = $this->mantis_model->connect_mantis_db();
        $hbs_db = $this->hbs_model->connect_hbs_db();
        
        $bug_not_exists = $this->log_model->get_bug_not_exists();
        if (empty($bug_not_exists))
        {
            return;
        }
        
        $bug_ids = array_column($bug_not_exists, 'bug_id');
        $bug_ids = array_chunk($bug_ids, 1000);
        foreach ($bug_ids as $bug_ids_chunk)
        {
            $bugs_closed = $this->mantis_model->bug_get_closed_status($mantis_db, $bug_ids_chunk);
            if ( ! empty($bugs_closed))
            {
                $this->log_model->remove_bug_not_exists(array_column($bugs_closed, 'id'));
            }
            $bugs_declined = $this->mantis_model->bug_get_declined_status($mantis_db, $bug_ids_chunk);
            if ( ! empty($bugs_declined))
            {
                $this->log_model->remove_bug_not_exists(array_column($bugs_declined, 'id'));
            }
        }
        
        $bug_not_exists = $this->log_model->get_bug_not_exists();
        if (empty($bug_not_exists))
        {
            return;
        }

        $this->db->trans_start();  
        $bug_not_exists = array_chunk($bug_not_exists, 1000);
        foreach ($bug_not_exists as $bugs_chunk)
        {
            $mantis_bug_ids = array_column($bugs_chunk, 'bug_id');
            $bugs_chunk = array_combine($mantis_bug_ids, $bugs_chunk);
            $claims = $this->hbs_model->get_claim_payment_chunk($hbs_db, $mantis_bug_ids);
            if (empty($claims))
            {
                continue;
            }
            $hbs_barcodes = array_column($claims, 'MANTIS_ID');
            
            $diff_bug_ids = array_diff($mantis_bug_ids, $hbs_barcodes);
            if ( ! empty($diff_bug_ids))
            {
                foreach ($diff_bug_ids as $bug_id)
                {
                    $this->log_model->replace_bug_not_exists($bugs_chunk[$bug_id]);
                }
            }
            $this->log_model->record_hbs_get_claim($claims);
            $this->log_model->remove_bug_not_exists($hbs_barcodes);
            foreach ($claims as $claim)
            {
                $claim['CL_USER'] = $bugs_chunk[$claim['MANTIS_ID']]['username'];
                $this->payments_model->update_payments_table($claim);
            }
        }
        $this->db->trans_complete();
        
        $bug_not_exists = $this->log_model->get_bug_not_exists();
        if ( ! empty($bug_not_exists))
        {
            $this->load->library('parser');
            $message = $this->parser->parse('emails/check_bug_not_exists', array(
                'data' => $bug_not_exists
            ), TRUE);
            sendMailByWrapper($this, 'Status of Issues are ACCEPTED but they are not completed in HBS', $message, $this->config->item('to_admin'));
        }
    }
    
    /**
     * Intended Time: 1 time at 21:30 first day of every month 
     * Task Scheduler: backup log_hbs_get_claim and log_mantis_cps_trigger table
     */
    private function _backup_log()
    {
        // Load the DB utility class
        $this->load->dbutil();
        
        // Backup your entire database and assign it to a variable
        $prefs = array(
            'tables'        => array('log_hbs_get_claim', 'log_mantis_cps_trigger'), // Array of tables to backup.
            'ignore'        => array(),                     // List of tables to omit from the backup
            'format'        => 'gzip',                      // gzip, zip, txt
            'filename'      => 'cps_dlvn_backup_log.sql',              // File name - NEEDED ONLY WITH ZIP FILES
            'add_drop'      => TRUE,                        // Whether to add DROP TABLE statements to backup file
            'add_insert'    => TRUE,                        // Whether to add INSERT data to backup file
            'newline'       => "\n"                         // Newline character used in backup file
        );
        $backup = $this->dbutil->backup($prefs);

        // Load the file helper and write the file to your server
        $this->load->helper('file');
        write_file('backup/cps_dlvn_backup_log_' . date('Y_m_d_H_i_s') . '.gzip', $backup);
        
        // Truncate log_hbs_get_claim and log_mantis_cps_trigger table
        $this->db->truncate('log_hbs_get_claim');
        $this->db->truncate('log_mantis_cps_trigger');
    }
    
    /**
     * Intended Time: 1 time each day at 21:45
     * Get hbs_data (Claim No, Presented Amount, Provider Name, Member Ref No, Member Name) from HBS to update to Mantis
     */
    private function _query_hbs_data()
    {
        set_time_limit(0);
        $this->load->model('mantis_model');
        $this->load->model('hbs_model');
        $mantis_db = $this->mantis_model->connect_mantis_db();
        $hbs_db = $this->hbs_model->connect_hbs_db();
        
        $result = $this->hbs_model->get_hbs_data($hbs_db, date('Y-m-d', strtotime('yesterday')));
        if (empty($result))
        {
            return;
        }
        foreach ($result as $row)
        {
            $row['BARCODE'] = intval($row['BARCODE']);
            $this->mantis_model->update_hbs_data($mantis_db, $row);
            $this->mantis_model->update_steps_to_reproduce($mantis_db, $row);
        }
    }
    
    /**
     * Intended Time: every 15 minutes each day, from 8:00 to 21:00
     * Upload transfers and refund data to [Mantis] cps_data table
     */
    private function _update_cps_data()
    {
        set_time_limit(0);
        $this->load->model('mantis_model');
        $this->load->model('transfers_model');
        $mantis_db = $this->mantis_model->connect_mantis_db();
        
        $transfers = $this->transfers_model->get_non_upload();
        if (empty($transfers))
        {
            return;
        }
        foreach ($transfers as $transfer)
        {
            $this->mantis_model->update_cps_data($mantis_db, $transfer);
            $uploaded_data[] = $transfer['TRAN_ID'];
        }
        $this->transfers_model->set_upl_mantis_date($uploaded_data, date('Y-m-d H:i:s'));
    }
    
    /**
     * Intended Time: once a day at 7:45 AM
     * Compare cps_data and hbs_data tables of Mantis Server
     */
    private function _cps_hbs_cmp()
    {
        set_time_limit(0);
        $this->load->model('mantis_model');
        $mantis_db = $this->mantis_model->connect_mantis_db();
        
        $result_1 = $this->mantis_model->get_same_cl_no_but_bug_id($mantis_db);
        $result_2 = $this->mantis_model->get_same_bug_id_but_cl_no($mantis_db);
        
        if ( ! empty($result_1) OR ! empty($result_2))
        {
            $this->load->library('parser');
            $message = $this->parser->parse('emails/cps_hbs_cmp', array(
                'TIME' => date('d/m/Y H:i:s'),
                'result_1' => $result_1,
                'result_2' => $result_2
            ), TRUE);
            sendMailByWrapper($this, 'Compare CPS Data with HBS Data', $message, $this->config->item('to_admin'));
        }
    }
    
    /**
     * Intended Time: once a day at 22:00
     * Upload data from [CPS] hbs_cl_claim table to [HBS] cl_claim table
     */
    private function _update_hbs_cl_claim()
    {
        set_time_limit(0);
        $this->load->model('hbs_model');
        $hbs_db = $this->hbs_model->connect_hbs_db();
        
        $data = $this->hbs_model->get_non_upload();
        if (empty($data))
        {
            return;
        }
        foreach ($data as $row)
        {
            $this->hbs_model->update_hbs_cl_claim($hbs_db, $row);
            $uploaded_data[] = $row['ID'];
        }
        
        $this->hbs_model->set_upl_date($uploaded_data, date('Y-m-d H:i:s'));
    }
    
    // public function data_patch()
    // {
        // set_time_limit(0);
        
        // $filePath = 'assets/dlvn_claim_payment_processing_2001020087.xlsx';
        // $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        // $reader->open($filePath);
        
        // $data = [];
        // foreach ($reader->getSheetIterator() as $sheet)
        // {
            // foreach ($sheet->getRowIterator() as $rowNumber => $row)
            // {
                // if ($rowNumber < 2)
                // {
                    // continue;
                // }
                // $tmp = $row->toArray();
                
                // $data[] = array(
                    // 'CL_NO' => $tmp[0],
                    // 'PAYMENT_TIME' => $tmp[1],
                    // 'TF_AMT' => $tmp[2],
                    // 'DEDUCT_AMT' => $tmp[3],
                    // 'DISC_AMT' => $tmp[4],
                    // 'APP_DATE' => empty($tmp[5]) ? null: $tmp[5],
                    // 'TF_DATE' => $tmp[6],
                    // 'TF_STATUS_ID' => $tmp[7],
                    // 'TF_NO' => 1,
                    // 'PROCESS_ORDER' => empty($tmp[8]) ? null: $tmp[8],
                    // 'VCB_SEQ' => empty($tmp[9]) ? null: $tmp[9],
                    // 'MANTIS_ID' => $tmp[10],
                    // 'PAYMENT_METHOD' => empty($tmp[11]) ? null: $tmp[11],
                    // 'ACCT_NAME' => empty($tmp[12]) ? null: $tmp[12],
                    // 'ACCT_NO' => empty($tmp[13]) ? null: $tmp[13],
                    // 'BANK_NAME' => empty($tmp[14]) ? null: $tmp[14],
                    // 'BANK_CITY' => empty($tmp[15]) ? null: $tmp[15],
                    // 'BANK_BRANCH' => empty($tmp[16]) ? null: $tmp[16],
                    // 'BENEFICIARY_NAME' => empty($tmp[17]) ? null: $tmp[17],
                    // 'PP_DATE' => empty($tmp[18]) ? null: $tmp[18],
                    // 'PP_PLACE' => empty($tmp[19]) ? null: $tmp[19],
                    // 'PP_NO' => empty($tmp[20]) ? null: $tmp[20],
                    // 'MEMB_NAME' => empty($tmp[21]) ? null: $tmp[21],
                    // 'POCY_REF_NO' => empty($tmp[22]) ? null: $tmp[22],
                    // 'MEMB_REF_NO' => empty($tmp[23]) ? null: $tmp[23],
                    // 'PRES_AMT' => empty($tmp[24]) ? null: $tmp[24],
                    // 'APP_AMT' => empty($tmp[25]) ? null: $tmp[25],
                    // 'RCV_DATE' => empty($tmp[26]) ? null: $tmp[26],
                    // 'BEN_TYPE' => empty($tmp[27]) ? null: $tmp[27],
                    // 'CL_TYPE' => empty($tmp[28]) ? null: $tmp[28],
                    // 'PROV_NAME' => empty($tmp[29]) ? null: $tmp[29],
                    // 'PAYEE' => empty($tmp[30]) ? null: $tmp[30],
                    // 'INV_NO' => empty($tmp[31]) ? null: $tmp[31],
                // );
                    
            // }
        // }
        // $reader->close();
        // $this->db->insert_batch('payments', $data);
        // echo 'OK';
    // }
}
