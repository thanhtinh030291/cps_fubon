<?php
/**
 * This model contains the business logic and manages the persistence of vietcombank's sheets
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class VCBSheets_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Create a new vietcombank's sheet for SHEET_TYPE_CLAIMANT sheet
     * @param int $sheet_id
     * @param array $vcbsheets list of rows of vietcombank's sheet, each row has format:
     * Group by: 'PAYMENT_METHOD', 'ACCT_NAME', 'ACCT_NO', 'BANK_NAME', 'BANK_BRANCH', 'BANK_CITY', 'BENEFICIARY_NAME', 'PP_NO', 'PP_DATE', 'PP_PLACE'
     * GROUP_CONCAT of CL_NO
     * GROUP_CONCAT of POCY_REF_NO
     * GROUP_CONCAT of PARQ_ID
     * SUM of TF_AMT
     */
    public function set_VCBSheets($sheet_id, $vcbsheets)
    {
        $data = [];
        foreach ($vcbsheets as $key => $row)
        {
            if (strlen($row['PAYM_ID']) >= MAX_LENGTH_PAYM_IDS)
            {
                return false;
            }
            // remove diacritic marks of bank info
            foreach (array('ACCT_NAME', 'BANK_NAME', 'BANK_BRANCH', 'BANK_CITY', 'BENEFICIARY_NAME', 'PP_PLACE') as $col)
            {
                $row[$col] = vn_to_str($row[$col]);
            }
            if ($row['PAYMENT_METHOD'] == 'TT')
            {
                $ben_acct = $row['ACCT_NO'];
                $ben_name = $row['ACCT_NAME'];
                $content = sprintf(lang('vcbsheet_content_tt'), $row['CL_NO'], $row['POCY_REF_NO']);
            }
            else
            {
                $id = 'ID';
                if (strtolower($row['BANK_NAME']) == 'techcombank')
                {
                    switch (strlen($row['PP_NO']))
                    {
                        case 9:
                            if (preg_match("/[a-zA-Z]/i", $row['PP_NO']))
                            {
                                $id = 'Passport';
                            }
                            else
                            {
                                $id = 'CMND';
                            }
                            break;
                        case 12:
                            $id = 'CCCD';
                            break;
                        default:
                            $id = 'Passport';
                    }
                }
                $ben_acct = 0;
                $ben_name = $row['BENEFICIARY_NAME'];
                if (empty($row['PP_DATE']))
                {
                    $this->lang->load('columns');
                    show_error(sprintf(lang('error_empty'), $row['CL_NO'], lang('col_pp_date')));
                }
                $content = sprintf(lang('vcbsheet_content_ch'), $row['BENEFICIARY_NAME'], $id, $row['PP_NO'], DateTime::createFromFormat('Y-m-d', $row['PP_DATE'])->format('d/m/Y'), $row['PP_PLACE'], $row['BANK_NAME'], $row['BANK_BRANCH'], $row['BANK_CITY']);
            }
            $data[] = array(
                'SHEET_ID' => $sheet_id,
                'SHEET_ORDER' => $key + 1,
                'BEN_ACCT' => $ben_acct,
                'BEN_NAME' => $ben_name,
                'BANK_NAME' => $row['BANK_NAME'] . ' - ' . $row['BANK_BRANCH'] . ' - ' . $row['BANK_CITY'],
                'AMT' => $row['TF_AMT'],
                'CONTENT' => $content,
                'PAYM_IDS' => $row['PAYM_ID']
            );
        }
        $this->db->insert_batch('vcbsheets', $data);
        return true;
    }
    
    /**
     * Create a new vietcombank's sheet for SHEET_TYPE_THIRD_PARTY sheet
     * @param int $sheet_id
     * @param array $vcbsheets only one row of vietcombank's sheet has format:
     *  - GROUP_CONCAT of CL_NO
     *  - GROUP_CONCAT of POCY_REF_NO
     *  - GROUP_CONCAT of PARQ_ID
     *  - SUM of TF_AMT
     * @param array $bank_info
     */
    public function set_VCBSheets_partner($sheet_id, $vcbsheets, $bank_info)
    {
        foreach (array('ACCT_NAME', 'BANK_NAME', 'BANK_BRANCH', 'BANK_CITY') as $col)
        {
            $bank_info[$col] = vn_to_str($bank_info[$col]);
        }
        $this->db
            ->set('SHEET_ID', $sheet_id)
            ->set('SHEET_ORDER', 1)
            ->set('BEN_ACCT', $bank_info['ACCT_NO'])
            ->set('BEN_NAME', $bank_info['ACCT_NAME'])
            ->set('BANK_NAME', $bank_info['BANK_NAME'] . ' - ' . $bank_info['BANK_BRANCH'] . ' - ' . $bank_info['BANK_CITY'])
            ->set('AMT', $vcbsheets['TF_AMT'])
            ->set('CONTENT', sprintf(lang('vcbsheet_content_tt'), $vcbsheets['CL_NO'], $vcbsheets['POCY_REF_NO']))
            ->set('PAYM_IDS', $vcbsheets['PAYM_ID'])
            ->insert('vcbsheets');
    }
    
    /**
     * Get a vietcombank's sheet by using sheet's id
     * @param int $sheet_id
     * @return array
     */
    public function get_VCBSheets($sheet_id)
    {
        return $this->db
            ->where('SHEET_ID', $sheet_id)
            ->get('vcbsheets')
            ->result_array();
    }
    
    /**
     * Delete a vietcombank's sheet by using sheet's id
     * @param int $sheet_id
     */
    public function delete_VCBSheets($sheet_id)
    {
        $this->db
            ->where('SHEET_ID', $sheet_id)
            ->delete('vcbsheets');
    }
    
    /**
     * Compare vietcombank sheet with vietcombank vnbt sheet
     * @param int $sheet_id
     * @param array $vnbt_sheet Data of vietcombank vnbt sheet from row 11
     * @return array list of Teller ID - SEQ for each payment if they are matched, else empty
     */
    public function compare_vnbtsheet($sheet_id, $vnbt_sheet)
    {
        $vcbsheet = $this->get_VCBSheets($sheet_id);
        if (count($vcbsheet) != count($vnbt_sheet))
        {
            return array();
        }
        $list = [];
        foreach ($vcbsheet as $i => $row)
        {
            $j = $i + 11;
            if (trim($row['SHEET_ORDER']) != trim($vnbt_sheet[$j]['A']))
            {
                return array('error' => 1, 'data' => [$j, 'A']);
            }
            if (trim($row['BEN_ACCT']) != trim($vnbt_sheet[$j]['B']))
            {
                return array('error' => 1, 'data' => [$j, 'B']);
            }
            if (trim($row['BEN_NAME']) != trim($vnbt_sheet[$j]['C']))
            {
                return array('error' => 1, 'data' => [$j, 'C']);
            }
            if (trim($row['AMT']) != trim($vnbt_sheet[$j]['H']))
            {
                return array('error' => 1, 'data' => [$j, 'H']);
            }
            $paym_ids = explode(',', $row['PAYM_IDS']);
            foreach ($paym_ids as $paym_id)
            {
                $list[$paym_id]['VCB_SEQ'] = $vnbt_sheet[$j]['K'] . ' - ' . $vnbt_sheet[$j]['L'];
                $list[$paym_id]['TF_DATE'] = DateTime::createFromFormat('d/m/Y', $vnbt_sheet[$j]['M'])->format('Y-m-d');
            }
        }
        return array('error' => 0, 'data' => $list);
    }
}
