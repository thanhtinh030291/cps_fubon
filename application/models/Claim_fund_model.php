<?php
/**
 * This model contains the business logic and manages the persistence of claim fund
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Claim_fund_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * Get list of Claim Fund
     * @return array
     */
    public function get_list()
    {
        return $this->db
            ->distinct()
            ->select('CLFU_MON_YEAR')
            ->order_by('CLFU_YEAR, CLFU_MON', 'DESC')
            ->get('claim_fund')
            ->result_array();
    }
    
    /**
     * Get current balance of claim fund
     * @return int
     */
    public function get_balance()
    {
        return $this->db
            ->select_sum('BALANCE')
            ->get('claim_fund')
            ->row()
            ->BALANCE;
    }
    
    /**
     * Get total replenishment of fund, refund, claim of a Claim Fund monthly report
     * @param date $time format: m/Y, e.g. 11/2019
     * @return array
     */
    public function get_total($time)
    {
        return $this->db
            ->select_sum('REPLENISHMENT')
            ->select_sum('REFUND')
            ->select_sum('TF_AMT')
            ->select_sum('REPAID_REFUND')
            ->where('CLFU_MON_YEAR', $time)
            ->get('claim_fund')
            ->row_array();
    }
    
    /**
     * Add replenishment
     * @param date replen_date
     * @param int replen_amt
     */
    public function replenishment($replen_date, $replen_amt)
    {
        $this->db
            ->set('DESCRIPTION', lang('fi_cl_fund_replenishment'))
            ->set('TF_DATE', $replen_date)
            ->set('REPLENISHMENT', $replen_amt)
            ->set('CLFU_TYPE', CLFU_TYPE_REPLENISHMENT)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->set('CLFU_YEAR', date('Y'))
            ->set('CLFU_MON', date('m'))
            ->insert('claim_fund');
    }
    
    /**
     * Add a new record to claim fund after paying a claim to claimant
     * @param array $payment
     * @param date $tf_date
     * @param string $vcb_seq
     */
    public function pay_claim($payment, $tf_date, $vcb_seq)
    {
        $this->db->set('TF_DATE', $tf_date);
        if ($payment['PAYMENT_METHOD'] == 'CQ')
        {
            $this->db->set('PCV_SEQ', $vcb_seq);
        }
        else
        {
            $this->db->set('VCB_SEQ', $vcb_seq);
        }
        $this->db->set('POCY_REF_NO', $payment['POCY_REF_NO']);
        $this->db->set('CL_NO', $payment['CL_NO']);
        if ($payment['TF_STATUS_ID'] == TF_STATUS_TRANSFERRING)
        {
            $this->db->set('DESCRIPTION', sprintf(lang('fi_cl_fund_pay_desc'), $payment['MEMB_NAME']));
        }
        elseif ($payment['TF_STATUS_ID'] == TF_STATUS_TRANSFERRING_PAYPREM OR $payment['TF_STATUS_ID'] == TF_STATUS_TRANSFERRING_DLVN_PAYPREM)
        {
            $this->db->set('DESCRIPTION', sprintf(lang('fi_cl_fund_payprem_desc'), $payment['MEMB_NAME']));
        }
        elseif ($payment['TF_STATUS_ID'] == TF_STATUS_TRANSFERRING_DLVN_CANCEL)
        {
            $this->db->set('DESCRIPTION', sprintf(lang('fi_cl_fund_repay_desc'), $payment['MEMB_NAME']));
        }
        if ($payment['YN_CLBO'] == 'Y')
        {
            $this->db->set('TF_AMT', $payment['TF_AMT']);
            $this->db->set('CLFU_TYPE', CLFU_TYPE_CLAIM_PAYMENT);
        }
        else
        {
            $this->db->set('REPAID_REFUND', $payment['TF_AMT']);
            $this->db->set('CLFU_TYPE', CLFU_TYPE_REPAID_REFUND);
        }
        $this->db
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->set('CLFU_YEAR', date('Y'))
            ->set('CLFU_MON', date('m'))
            ->insert('claim_fund');
    }
    
    /**
     * Delete a claim fund record because of user's mistaken action
     * @param array $payment
     */
    public function unpay_claim($payment)
    {
        if ($payment['PAYMENT_METHOD'] == 'CQ')
        {
            $field = 'PCV_SEQ';
        }
        else
        {
            $field = 'VCB_SEQ';
        }
        $this->db
            ->where('TF_DATE', $payment['TF_DATE'])
            ->where($field, $payment['VCB_SEQ'])
            ->where('CLFU_TYPE', CLFU_TYPE_CLAIM_PAYMENT)
            ->where('PAYM_ID', $payment['PAYM_ID'])
            ->delete('claim_fund');
    }
    
    /**
     * Add a new record to claim fund after revert a transferred payment to transferring or move a transferred payment to Returned To Claim
     * @param array $payment
     * @param date $returned_date
     * @param string $return_vcb_seq
     */
    public function refund($payment, $returned_date, $return_vcb_seq)
    {
        if ($payment['PAYMENT_METHOD'] == 'CQ')
        {
            $field = 'PCV_SEQ';
        }
        else
        {
            $field = 'VCB_SEQ';
        }
        $this->db
            ->set('TF_DATE', $returned_date)
            ->set($field, $return_vcb_seq)
            ->set('POCY_REF_NO', $payment['POCY_REF_NO'])
            ->set('CL_NO', $payment['CL_NO'])
            ->set('DESCRIPTION', sprintf(lang('fi_cl_refund_desc'), $payment['MEMB_NAME']))
            ->set('REFUND', $payment['TF_AMT'])
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('CLFU_TYPE', CLFU_TYPE_REFUND)
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->set('CLFU_YEAR', date('Y'))
            ->set('CLFU_MON', date('m'))
            ->insert('claim_fund');
    }
    
    /**
     * Delete a record of claim fund because of user's mistaken Refund action
     * @param int $paym_id
     * @param date $returned_date
     * @param string $return_vcb_seq
     */
    public function cancel_refund($paym_id, $returned_date, $return_vcb_seq)
    {
        if ($payment['PAYMENT_METHOD'] == 'CQ')
        {
            $field = 'PCV_SEQ';
        }
        else
        {
            $field = 'VCB_SEQ';
        }
        $this->db
            ->where('TF_DATE', $returned_date)
            ->where($field, $return_vcb_seq)
            ->where('CLFU_TYPE', CLFU_TYPE_REFUND)
            ->where('PAYM_ID', $paym_id)
            ->delete('claim_fund');
    }
    
    /**
     * Get details of a Claim Fund monthly report
     * @param date $time format: m/Y, e.g. 11/2019
     * @return array
     */
    public function get_details($time)
    {
        return $this->db
            ->where('CLFU_MON_YEAR', $time)
            ->order_by('CLFU_ID', 'ASC')
            ->get('claim_fund')
            ->result_array();
    }
    
    /**
     * Export report of Claim Fund
     * @param array $data
     * @param date $date_from
     * @param date $date_to
     * @param array $bebal beginning balance
     * @param array $total total balance in period
     */
    public function export($data, $date_from, $date_to, $bebal, $total)
    {
        // path of output
        $path = 'assets/dl/cf/';
        if ( ! file_exists($path))
        {
            mkdir($path);
        }
        $path .= date('Y');
        if ( ! file_exists($path))
        {
            mkdir($path);
        }
        $out_name = $path . '/Report_Dai-ichi_Claim_Fund_' . str_replace('/', '-', $date_from . '_' . $date_to) . '.xlsx';
        
        // create ClaimFund_DLVN.xlsx
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet(0);
        
        // add logo
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath('./assets/img/claim_fund_logo.png');
        $drawing->setHeight(120);
        $drawing->setWorksheet($sheet);
        
        // add header
        $sheet->mergeCells('A5:K5');
        $sheet->setCellValue('A5', lang('cf_report_title'));
        $sheet->mergeCells('A6:K6');
        $sheet->setCellValue('A6', sprintf(lang('cf_report_desc'), $date_from, $date_to));
        
        $sheet->mergeCells('A8:A9');
        $sheet->setCellValue('A8', str_replace("<br>", "\n", lang('col_cf_date')));
        $sheet->mergeCells('B8:C8');
        $sheet->setCellValue('B8', str_replace("<br>", "\n", lang('col_cf_voucher_no')));
        $sheet->mergeCells('D8:E8');
        $sheet->setCellValue('D8', str_replace("<br>", "\n", lang('col_cf_claim_information')));
        $sheet->setCellValue('F8', lang('col_cf_description'));
        $sheet->mergeCells('G8:K8');
        $sheet->setCellValue('G8', lang('col_cf_amount'));
        $sheet->setCellValue('B9', str_replace("<br>", "\n", lang('col_cf_bank_no')));
        $sheet->setCellValue('C9', str_replace("<br>", "\n", lang('col_cf_cash')));
        $sheet->setCellValue('D9', str_replace("<br>", "\n", lang('col_cf_policy_number')));
        $sheet->setCellValue('E9', str_replace("<br>", "\n", lang('col_cf_claim_number')));
        $sheet->setCellValue('G9', str_replace("<br>", "\n", lang('col_cf_replenishment')));
        $sheet->setCellValue('H9', str_replace("<br>", "\n", lang('col_cf_refund')));
        $sheet->setCellValue('I9', str_replace("<br>", "\n", lang('col_cf_claim_payment')));
        $sheet->setCellValue('J9', str_replace("<br>", "\n", lang('col_cf_repaid_refund_claim')));
        $sheet->setCellValue('K9', str_replace("<br>", "\n", lang('col_cf_balance')));
        $i = 0;
        foreach (range('A', 'K') as $id)
        {
            ++$i;
            $sheet->setCellValue($id . '10', $i);
            $sheet->getColumnDimension($id)->setAutoSize(true);
        }
        $sheet->setCellValue('F11', lang('cf_beginning_balance'));
        $sheet->setCellValue('G11', $bebal['CLFF_END_REPL']);
        $sheet->setCellValue('H11', $bebal['CLFF_END_REFU']);
        $sheet->setCellValue('K11', $bebal['CLFF_END_BALA']);
        $sheet->getStyle('A8:K10')->getAlignment()->setWrapText(true)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('F11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A8:K11')->getFont()->setBold(true);
        
        // add content
        $balance = $bebal['CLFF_END_BALA'];
        $i = 11;
        foreach ($data as $row)
        {
            ++$i;
            $balance += $row['BALANCE'];
            $sheet->setCellValue('A' . $i, $row['TF_DATE']);
            $sheet->setCellValue('B' . $i, $row['VCB_SEQ']);
            $sheet->setCellValue('C' . $i, $row['PCV_SEQ']);
            $sheet->setCellValue('D' . $i, $row['POCY_REF_NO']);
            $sheet->setCellValue('E' . $i, $row['CL_NO']);
            $sheet->setCellValue('F' . $i, $row['DESCRIPTION']);
            $sheet->getCell('G' . $i)->setValueExplicit($row['REPLENISHMENT'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('H' . $i, $row['REFUND']);
            $sheet->setCellValue('I' . $i, $row['TF_AMT']);
            $sheet->setCellValue('J' . $i, $row['REPAID_REFUND']);
            $sheet->setCellValue('K' . $i, $balance);
        }
        $sheet->getStyle('A5:E' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical( PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        ++$i;
        $sheet->setCellValue('F' . $i, lang('cf_total'));
        $sheet->getCell('G' . $i)->setValueExplicit($total['REPLENISHMENT'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValue('H' . $i, $total['REFUND']);
        $sheet->setCellValue('I' . $i, $total['TF_AMT']);
        $sheet->setCellValue('J' . $i, $total['REPAID_REFUND']);
        $sheet->getStyle("F$i:K$i")->getFont()->setBold(true);
        ++$i;
        $sheet->setCellValue('F' . $i, lang('cf_ending_balance'));
        $sheet->setCellValue('K' . $i, $balance);
        $sheet->getStyle("F$i:K$i")->getFont()->setBold(true);
        ++$i;
        $sheet->setCellValue('E' . $i, lang('cf_ending_include'));
        $sheet->setCellValue('F' . $i, lang('cf_ending_refund'));
        $sheet->setCellValue('K' . $i, $bebal['CLFF_END_REFU'] + $total['REFUND'] - $total['REPAID_REFUND']);
        $sheet->getStyle("F$i:K$i")->getFont()->setBold(true);
        ++$i;
        $sheet->setCellValue('F' . $i, lang('cf_ending_claim'));
        $sheet->setCellValue('K' . $i, $bebal['CLFF_END_REPL'] + $total['REPLENISHMENT'] - $total['TF_AMT']);
        $sheet->getStyle("F$i:K$i")->getFont()->setBold(true);
        $sheet->getStyle('F' . ($i - 3) . ":F$i")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $border_style = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ),
            ),
        );
        $sheet->getStyle('A8:K' . $i)->applyFromArray($border_style);
        // signature
        $i += 2;
        $sheet->setCellValue("A$i", lang('cf_in_words'));
        $sheet->setCellValue("F$i", $this->_vietnamese_number_to_words($total['TF_AMT']));
        $i += 2;
        $sheet->setCellValue("B$i", lang('cf_sign_dlvn'));
        $sheet->setCellValue("H$i", sprintf(lang('cf_sign_pcv_city'), $date_to));
        $sheet->setCellValue('H' . ($i + 1), lang('cf_sign_pcv'));
        $sheet->setCellValue('H' . ($i + 2), lang('cf_sign_position'));
        $sheet->setCellValue('H' . ($i + 8), lang('cf_sign_name'));
        $sheet->getStyle("H$i:H" . ($i + 8))->getFont()->setBold(true);
        $sheet->getStyle("H$i:H" . ($i + 8))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G11:K$i")->getNumberFormat()->setFormatCode("#,###,###,###,###");
        $sheet->getStyle('A5')->getFont()->setBold(true);
        // save
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($out_name);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        // download
        $this->load->helper('download');
        force_download($out_name, NULL);
    }
    
    private function _vietnamese_number_to_words($number)
    {
        return ucfirst(str_replace(['tỷ', 'triệu', 'nghìn'], ['tỷ,', 'triệu,', 'ngàn,'], (new NumberFormatter('vi', NumberFormatter::SPELLOUT))->format($number))) . ' đồng';
    }
    
    /**
     * When a claim is closed, insert 2 rows to adjust CLAIM_PAYMENT and REFUND of the claim
     * @param int $paym_id
     */
    public function adjust_closed($paym_id)
    {
        $mon = date('m');
        $year = date('Y');
        $user_name = $this->session->userdata('user_name');
        
        $row = $this->_get_latest_by_type($paym_id, CLFU_TYPE_CLAIM_PAYMENT);
        $row['TF_AMT'] = -$row['TF_AMT'];
        $row['CLFU_MON'] = $mon;
        $row['CLFU_YEAR'] = $year;
        $row['CLFU_TYPE'] = CLFU_TYPE_ADJUST_CLOSED;
        $row['CRT_USER'] = $user_name;
        $this->db->insert('claim_fund', $row);
        
        $row = $this->_get_latest_by_type($paym_id, CLFU_TYPE_REFUND);
        $row['REFUND'] = -$row['REFUND'];
        $row['CLFU_MON'] = $mon;
        $row['CLFU_YEAR'] = $year;
        $row['CLFU_TYPE'] = CLFU_TYPE_ADJUST_CLOSED;
        $row['CRT_USER'] = $user_name;
        $this->db->insert('claim_fund', $row);
    }
    
    /**
     * Get latest record of a payment by Claim Fund Type
     * @param int $paym_id
     * @param int $clfu_type
     * @return array
     */
    private function _get_latest_by_type($paym_id, $clfu_type)
    {
        return $this->db
            ->select('TF_DATE, VCB_SEQ, PCV_SEQ, POCY_REF_NO, CL_NO, DESCRIPTION, REPLENISHMENT, REFUND, TF_AMT, REPAID_REFUND, PAYM_ID')
            ->limit(1)
            ->order_by('CLFU_ID', 'desc')
            ->where('PAYM_ID', $paym_id)
            ->where('CLFU_TYPE', $clfu_type)
            ->get('claim_fund')
            ->row_array();
    }
    
    /**
     * When adjust transfer amount of a claim, insert a row to adjust CLAIM_PAYMENT record of the claim
     * @param int $paym_id
     * @param int $decr_amt
     */
    public function adjust_decrease($paym_id, $decr_amt)
    {
        $row = $this->_get_latest_by_type($paym_id, CLFU_TYPE_CLAIM_PAYMENT);
        $row['TF_AMT'] = -$decr_amt;
        $row['CLFU_MON'] = date('m');
        $row['CLFU_YEAR'] = date('Y');
        $row['CLFU_TYPE'] = CLFU_TYPE_ADJUST_DECREASE;
        $row['CRT_USER'] = $this->session->userdata('user_name');
        $this->db->insert('claim_fund', $row);
    }
    
    /**
     * Adjust Repaid Refund Claim because Daiichi wants to send money to client again
     * @param int $paym_id
     */
    public function adjust_repaid_refund($paym_id)
    {
        $row = $this->_get_latest_by_type($paym_id, CLFU_TYPE_REPAID_REFUND);
        $row['REPAID_REFUND'] = -$row['REPAID_REFUND'];
        $row['CLFU_MON'] = date('m');
        $row['CLFU_YEAR'] = date('Y');
        $row['CLFU_TYPE'] = CLFU_TYPE_ADJUST_REPAID_REFUND;
        $row['CRT_USER'] = $this->session->userdata('user_name');
        $this->db->insert('claim_fund', $row);
    }
}
