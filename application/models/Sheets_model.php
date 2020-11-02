<?php
/**
 * This model contains the business logic and manages the persistence of sheets
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Sheets_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Count number of sheets which have specified status
     * @param int $type Sheet Type ID, view constants.php
     * @param int $status Sheet Status ID, view constants.php
     * @return int
     */
    // public function sheets_count($type, $status)
    // {
        // $this->db->from('sheets');
        // $this->db->where('SHEET_TYPE', $type);
        // $this->db->where('SHEET_STATUS', $status);
        // return $this->db->count_all_results();
    // }
    
    /**
     * Get all sheets which have specified status and/or type
     * @param int $status Sheet Status ID likes Transfer Status ID
     * @param int $type Sheet Type ID
     * @return array list of sheets
     */
    public function get_sheets($status, $type)
    {
        $query = $this->db
            ->where('SHEET_STATUS', $status)
            ->where('SHEET_TYPE', $type)
            ->get('sheets');
        return $query->result_array();
    }
    
    /**
     * Get default new name
     * @param int $status Sheet Status ID likes Transfer Status ID
     * @param int $bk_name optional Bang Ke's Name
     * @return array record of payments
     */
    public function default_sheet()
    {
        $date = date('Y-m-d');
        $result = $this->db
            ->select_max('SHEET_NO')
            ->where('SHEET_DATE', date('Y-m-d'))
            ->get('sheets')
            ->row();
        if (empty($result->SHEET_NO))
        {
            return $date . '_1';
        }
        return $date . '_' . sprintf("%'.02d", $result->SHEET_NO + 1);
    }
    
    /**
     * Create a new sheet with specified name and sheet type
     * @param string $name Sheet's Name
     * @param int $type Sheet's Type
     * @return sheet's id
     */
    public function create($name, $type, $uname = null)
    {
        list($sheet_date, $sheet_no) = explode('_', $name);
        $this->db
            ->set('SHEET_DATE', $sheet_date)
            ->set('SHEET_NO', $sheet_no)
            ->set('SHEET_TYPE', $type);
        if ( ! empty($uname))
        {
            $this->db->set('SHEET_UNAME', $uname);
        }
        if ($this->db->insert('sheets'))
        {
            return $this->db->insert_id();
        }
        return FALSE;
    }
    
    /**
     * Get all information of a sheet in sheet table
     * @param int $sheet_id
     * @return array
     */
    public function get($sheet_id)
    {
        return $this->db
            ->where('SHEET_ID', $sheet_id)
            ->get('sheets')
            ->row_array();
    }
    
    /**
     * Set sheet status
     * @param int $sheet_id
     * @param int $status Sheet Status
     */
    public function set_status($sheet_id, $status)
    {
        $this->db
            ->set('SHEET_STATUS', $status)
            ->where('SHEET_ID', $sheet_id)
            ->update('sheets');
    }
    
    /**
     * Delete an empty sheet
     * @param int $sheet_id
     * @return array
     */
    public function delete($sheet_id)
    {
        $this->db
            ->where('SHEET_ID', $sheet_id)
            ->delete('sheets');
    }
    
    /**
     * close current sheet and create new empty sheet
     * @return boolean
     */
    public function close_sheet()
    {
        $row['SHEET_DATE'] = date('Y-m-d');
        
        $this->db->select_max('SHEET_NO');
        $this->db->where('SHEET_DATE', $row['SHEET_DATE']);
        $query = $this->db->get('sheets');
        $result = $query->row_array();
        $row['SHEET_NO'] = $result['SHEET_NO'] + 1;
        
        $sheet = $this->get_sheets(TFST_BANG_KE);
        foreach ($sheet as $payment)
        {
            $row['PARQ_ID_FK'] = $payment['PARQ_ID'];
            
            // $sql = $this->db->set($row)->get_compiled_insert('sheets');
            // echo $sql;exit;
            
            $this->db->insert('sheets', $row);
        }
        return true;
    }
    
    /**
     * Get the list of sheets which are not downloaded
     * @param int $tf_status_id
     * @return array record of payments
     */
    public function get_list_of_sheets($tf_status_id)
    {
        $this->db->distinct();
        $this->db->select("CONCAT(SHEET_DATE, '_', SHEET_NO) AS SHEET_NAME");
        $this->db->join('sheets', 'sheets.PARQ_ID_FK = payments.PARQ_ID');
        $this->db->where('TF_STATUS_ID', $tf_status_id);
        $this->db->order_by('SHEET_DATE, SHEET_NO', 'ASC');
        $query = $this->db->get('payments');
        return $query->result_array();
    }
    
    /**
     * Get the list of payment which have been grouped into a sheet or not
     * @param int $tf_status_id
     * @param int $bk_name optional Bang Ke's Name
     * @return array record of payments
     */
    // public function get_sheets($tf_status_id, $sheet_date = null, $sheet_no = null)
    // {
        // $this->db->select('PARQ_ID, CL_NO, TF_TIMES, TF_AMT, TF_DATE, PAYMENT_METHOD, ACCT_NAME, ACCT_NO, BANK_NAME, BANK_BRANCH, BANK_CITY, BENEFICIARY_NAME, PP_NO, PP_DATE, PP_PLACE, POCY_REF_NO, MEMB_NAME, INV_NO, SHEET_ORDER, MANTIS_ID, MDTC, VCB_SEQ');
        // $this->db->join('sheets', 'sheets.PARQ_ID_FK = payments.PARQ_ID', 'left');
        // $this->db->where('TF_STATUS_ID', $tf_status_id);
        // $this->db->where('SHEET_DATE', $sheet_date);
        // $this->db->where('SHEET_NO', $sheet_no);
        // $this->db->order_by('PROCESS_ORDER', 'ASC');
        // $query = $this->db->get('payments');
        // return $query->result_array();
    // }
    
    /**
     * Get the list of transferring payment which payment method is "Cash At PCV" OR Cash At Bank, which bank name is Vietcombank
     * @return array record of payments
     */
    public function get_transferring_payments()
    {
        $this->db->select('PARQ_ID, CL_NO, TF_TIMES, PAYMENT_METHOD, BANK_NAME, TF_TIMES');
        $this->db->join('sheets', 'sheets.PARQ_ID_FK = payments.PARQ_ID', 'left');
        $this->db->where('TF_STATUS_ID', TFST_TRANSFERRING);
        $this->db->where('SHEET_DATE', null);
        $this->db->order_by('PROCESS_ORDER', 'ASC');
        $query = $this->db->get('payments');
        return $query->result_array();
    }
    
    /**
     * Set bk_name of a payment
     * @param int $parq_id
     * @param date $sheet_date yyyy-mm-dd
     * @param int $sheet_no
     * @return boolean
     */
    // public function set_name($parq_id, $sheet_date = null, $sheet_no = null)
    // {
        // if (empty($sheet_date))
        // {
            // $this->db->where('PARQ_ID_FK', $parq_id);
            // return $this->db->delete('sheets');
        // }
        // $this->db->set('SHEET_DATE', $sheet_date);
        // $this->db->set('SHEET_NO', $sheet_no);
        // $this->db->set('PARQ_ID_FK', $parq_id);
        // return $this->db->insert('payments');
    // }
    
    /**
     * Change SHEET_UNAME of a sheet
     * @param int $sheet_id
     */
    public function change_name($sheet_id, $sheet_uname)
    {
        $this->db->set('SHEET_UNAME', $sheet_uname);
        $this->db->where('SHEET_ID', $sheet_id);
        $this->db->update('sheets');
    }
    
    /**
     * Set bk_order of a payment which selected bk_name
     * @param string $sheet_name
     * @return array $sheet_data
     */
    public function set_order_and_generate_sheet_data($sheet_name)
    {
        list($sheet_date, $sheet_no) = explode('_', $sheet_name);
        $sheet = $this->get_sheets(TFST_BANG_KE, $sheet_date, $sheet_no);
        $BKM = [];
        $sheet_data = [];
        foreach ($sheet as $payment)
        {
            if ($payment['PAYMENT_METHOD'] == 'TT')
            {
                $key = 'TT#' . $payment['ACCT_NAME'] . '#' . $payment['ACCT_NO'] . '#' . $payment['BANK_NAME'] . '#' . $payment['BANK_BRANCH'] . '#' . $payment['BANK_CITY'];
            }
            else
            {
                $payment['PP_DATE'] = date('d/m/Y', $payment['PP_DATE']);
                $key = 'CH#' . $payment['BENEFICIARY_NAME'] . '#' . $payment['PP_NO'] . '#' . $payment['PP_DATE'] . '#' . $payment['PP_PLACE'] . '#' . $payment['BANK_NAME'] . '#' . $payment['BANK_BRANCH'] . '#' . $payment['BANK_CITY'];
            }
            if (isset($BKM[$key]))
            {
                $BKM[$key]['TF_AMT'] += $payment['TF_AMT'];
            }
            else
            {
                $BKM[$key]['TF_AMT'] = $payment['TF_AMT'];
            }
            $BKM[$key]['CL_NO'][] = $payment['CL_NO'];
            $BKM[$key]['POCY_REF_NO'][] = $payment['POCY_REF_NO'];
            $BKM[$key]['PARQ_ID'][] = $payment['PARQ_ID'];
        }
        if (empty($BKM))
        {
            return $sheet_data;
        }
        $i = 0;
        foreach ($BKM as $key => $val)
        {
            ++$i;
            
            $this->db->set('SHEET_ORDER', $i);
            $this->db->where_in('PARQ_ID_FK', $val['PARQ_ID']);
            $this->db->update('sheets');
            
            $list = explode('#', $key);
            if ($list[0] === 'TT')
            {
                $cl_no = implode(', ', $val['CL_NO']);
                $pocy_ref_no = implode(', ', $val['POCY_REF_NO']);
                $sheet_data[] = [
                    $i, '',
                    $list[2], '', '', '',
                    $list[1],
                    $list[3] . ' - ' . $list[4] . ' - ' . $list[5],
                    $val['TF_AMT'], 'vnd',
                    'Thanh toán bồi thường hộ Dai-ichi cho số ' . $cl_no . ' (Hợp đồng BH số ' . $pocy_ref_no . '), Claim payment for Dai-ichi'
                ];
            }
            else
            {
                $sheet_data[] = [
                    $i, '', 0, '', '', '',
                    $list[1],
                    $list[5] . ' - ' . $list[6] . ' - ' . $list[7],
                    $val['TF_AMT'], 'vnd',
                    $list[1] . ', ID: ' . $list[2] . ', NGAY: ' . $list[3] . ', NOI: ' . $list[4] . ', NHAN TM TAI: ' . $list[5] . ' - ' . $list[6] . ' - ' . $list[7]
                ];
            }
        }
        // $this->db->set('TF_STATUS_ID', TFST_TRANSFERRING);
        // $this->db->where('SHEET_DATE', $sheet_date);
        // $this->db->where('SHEET_NO', $sheet_no);
        // $this->db->update('payments LEFT JOIN sheets ON sheets.PARQ_ID_FK = payments.PARQ_ID');
        $this->db->query('
            UPDATE payments LEFT JOIN sheets ON sheets.PARQ_ID_FK = payments.PARQ_ID
            SET TF_STATUS_ID = ' . TFST_TRANSFERRING . "
            WHERE SHEET_DATE = '$sheet_date' AND SHEET_NO = $sheet_no
        ");
        return $sheet_data;
    }
    
    /**
     * Set TF_DATE of a sheet
     * @param array $bangke list of payments
     * @param date $tf_date
     */
    public function set_transfer_date($bangke, $tf_date)
    {
        $this->load->model('payments_model');
        foreach ($bangke as $payment)
        {
            $this->payments_model->set_transfer_date($payment['PARQ_ID'], strtotime($tf_date), $payment['TF_DATE'], $payment['TF_AMT']);
            $this->payments_model->set_transfer_status($payment['PARQ_ID'], TFST_TRANSFERRED);
        }
    }
    
    /**
     * Set VCB_SEQ for each payment of a sheet
     * @param array $bangke list of payments
     * @param int $path full path of uploaded vietcombank file
     * @return boolean
     */
    public function set_vcb_seq($bangke, $path)
    {
        $input = [
            'A' => 'STT',
            'B' => 'Số TK hưởng',
            'C' => 'Tên TK hưởng',
            'H' => 'Số tiền',
            'K' => 'Teller ID',
            'L' => 'Seq'
        ];
        $reader = IOFactory::createReader('Xls');
        $spreadsheet = $reader->load($path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unlink($path);
        foreach ($input as $col => $name)
        {
            if (trim($sheetData[10][$col]) !== $name)
            {
                return false;
            }
        }
        // load data của bangkemau
        $data = [];
        foreach ($bangke as $payment)
        {
            if ($payment['PAYMENT_METHOD'] === 'CH')
            {
                $account = 0;
                $name = $payment['BENEFICIARY_NAME'];
            }
            else
            {
                $account = $payment['ACCT_NO'];
                $name = $payment['ACCT_NAME'];
            }
            $key = $payment['SHEET_ORDER'] . '#' .  $account . '#' . $name;
            if ( ! isset($data[$key]['TF_AMT']))
            {
                $data[$key]['TF_AMT'] = 0;
            }
            $data[$key]['TF_AMT'] += $payment['TF_AMT'];
            $data[$key]['PARQ_ID'][] = $payment['PARQ_ID'];
        }
        $n = count($sheetData);
        for ($i = 11; $i <= $n; ++$i)
        {
            $key = $sheetData[$i]['A'] . '#' . $sheetData[$i]['B'] . '#' . $sheetData[$i]['C'];
            if (intval($data[$key]['TF_AMT']) == intval($sheetData[$i]['H']))
            {
                $this->db->set('VCB_SEQ', $sheetData[$i]['K'] . '-' . $sheetData[$i]['L']);
                $this->db->where_in('PARQ_ID', $data[$key]['PARQ_ID']);
                $this->db->where('VCB_SEQ', '');
                $this->db->update('payments');
            }
            else
            {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Upload Transfer Info of a sheet to Mantis DLVN
     * @param array $bangke list of payments
     * @return boolean
     */
    public function upload_mantis($bangke)
    {
        $this->load->model('payments_model');
        foreach ($bangke as $payment)
        {
            $this->payments_model->upload_mantis_tf_info($payment);
        }
        return TRUE;
    }
}
