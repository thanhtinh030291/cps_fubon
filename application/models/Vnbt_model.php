<?php
/**
 * This model contains the business logic and manages the persistence of vietcombank vnbt's sheets - master level
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Vnbt_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Create a vietcombank vnbt sheet's header for a sent vietcombank sheet
     * @param int $sheet_id
     * @param array $vnbt_header header information of vietcombank vnbt sheet, excel row from 1 to 10
     */
    public function set_header($sheet_id, $vnbt_header)
    {
        $this->db
            ->set('SHEET_ID', $sheet_id)
            ->set('UPL_DATE', DateTime::createFromFormat('d/m/Y H:i:s', str_replace(VCB_VNBT['A2'], '', $vnbt_header[2]['A']))->format('Y-m-d H:i:s'))
            ->set('PRINT_DATE', DateTime::createFromFormat('d/m/Y H:i:s', str_replace(VCB_VNBT['N4'], '', $vnbt_header[4]['N']))->format('Y-m-d H:i:s'))
            ->set('VNBT_REF_NAME', str_replace(VCB_VNBT['A4'], '', $vnbt_header[4]['A']))
            ->set('VNBT_FILENAME', str_replace(VCB_VNBT['A5'], '', $vnbt_header[5]['A']))
            ->set('DEBIT_ACCT_NO', str_replace(VCB_VNBT['A6'], '', $vnbt_header[6]['A']))
            ->set('ORDER_NO', str_replace(VCB_VNBT['A7'], "", $vnbt_header[7]['A']))
            ->set('TF_AMT', str_replace(VCB_VNBT['A8'], "", $vnbt_header[8]['A']))
            ->set('FEE_AMT', str_replace(VCB_VNBT['E8'], "", $vnbt_header[8]['E']))
            ->set('VAT_AMT', str_replace(VCB_VNBT['I8'], "", $vnbt_header[8]['I']))
            ->set('STATUS', str_replace(VCB_VNBT['N8'], "", $vnbt_header[8]['N']))
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('vnbt_headers');
    }
    
    /**
     * Create a vietcombank vnbt sheet's detail for a sent vietcombank sheet
     * @param int $sheet_id
     * @param array $vnbt_sheet detail information of vietcombank vnbt sheet, from excel row 11
     */
    public function set_body($sheet_id, $vnbt_sheet)
    {
        $data = [];
        foreach ($vnbt_sheet as $key => $row)
        {
            $data[] = array(
                'SHEET_ID' => $sheet_id,
                'VNBS_ORDER' => $row['A'],
                'BEN_ACCT' => $row['B'],
                'BEN_NAME' => $row['C'],
                'BANK_NAME' => $row['D'],
                'PP_NO' => $row['E'],
                'PP_PLACE' => $row['F'],
                'PP_DATE' => $row['G'],
                'TF_AMT' => $row['H'],
                'FEE_AMT' => $row['I'],
                'VAT_AMT' => $row['J'],
                'TELLER_ID' => $row['K'],
                'SEQ' => $row['L'],
                'TF_DATE' => DateTime::createFromFormat('d/m/Y', $row['M'])->format('Y-m-d'),
                'STATUS' => $row['N']
            );
        }
        $this->db->insert_batch('vnbt_sheets', $data);
    }
    
    /**
     * Get id of all sheets
     * @return array
     */
    public function get_all_sheets()
    {
        return $this->db
            ->select('SHEET_ID')
            ->get('vnbt_headers')
            ->result_array();
    }
    
    /**
     * Get header of a VNBT sheet
     * @param int $sheet_id
     * @return array
     */
    public function get_header($sheet_id)
    {
        return $this->db
            ->where('SHEET_ID', $sheet_id)
            ->get('vnbt_headers')
            ->row_array();
    }
    
    /**
     * Get body of a VNBT sheet
     * @param int $sheet_id
     * @return array
     */
    public function get_body($sheet_id)
    {
        return $this->db
            ->where('SHEET_ID', $sheet_id)
            ->order_by('VNBS_ORDER')
            ->get('vnbt_sheets')
            ->result_array();
    }
}
