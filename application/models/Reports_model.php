<?php
/**
 * This model serves the list of custom reports and the system reports.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * This model contains the business logic and manages the persistence of users (employees)
 * It is also used by the session controller for the authentication.
 */
class Reports_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }
    
    /**
     * export Claim Bordereaux Report - Excel5 file
     * @param array $data list of payments which have same month and year of CB_DATE
     * @return string fullpath of generated files: xlsx and csv
     */
    public function export_claim_bordereaux($data)
    {
        // path of output
        $path = 'assets/dl/cb/';
        if ( ! file_exists($path))
        {
            mkdir($path);
        }
        $path .= date('Y');
        if ( ! file_exists($path))
        {
            mkdir($path);
        }
        $month_year = date('n-Y', strtotime($data[0]['CB_DATE']));
        $out_name = $path . '/ClaimBordereaux_DLVN_' . $month_year . '.xlsx';
        $csv_out_name = $path . '/UploadTransfer_HBS_DLVN_' . $month_year . '.csv';

        // create ClaimBordereaux_DLVN.xlsx
        $header = [
            'A' => 'No',
            'B' => 'Claimant',
            'C' => 'Client ID',
            'D' => 'Policy Ref No.',
            'E' => 'Claim No.',
            'F' => 'Invoice No.',
            'G' => 'Claim (VND)',
            'H' => 'Paid (VND)',
            'I' => 'Visit for Benefit',
            'J' => 'Payment Date',
            'K' => 'Payee',
            'L' => 'Transfer Amt',
            'M' => 'Transfer Date'
        ];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet(0);
        $sheet->setCellValue('F1', 'DAI-ICHI CLAIMS PAID');
        foreach ($header as $col => $name)
        {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->setCellValue($col . '3', $name);
        }
        $sheet->getStyle('A1:' . $col . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:' . $col . '3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:' . $col . '3')->getFont()->setBold(true);
        
        // create UploadTransfer_HBS_DLVN.csv
        $UploadTransfer = fopen($csv_out_name, 'w');
        fputcsv($UploadTransfer, array('Claim No', 'Transfer Amount', 'Transfer Date', 'Transfer Remark', 'Overwrite', 'Overwrite To'));
    
        $i = 3;
        $total_pres_amt = 0;
        $total_app_amt = 0;
        $total_tf_amt = 0;
        foreach ($data as $row)
        {
            ++$i;
            if ($row['CL_TYPE'] === 'M')
            {
                $Payee = 'Ind';
            }
            else
            {
                $Payee = $row['PAYEE'];
            }
            // write to xlsx
            $sheet->setCellValue('A' . $i, $i - 3);
            $sheet->setCellValue('B' . $i, $row['MEMB_NAME']);
            $sheet->setCellValue('C' . $i, $row['MEMB_REF_NO']);
            $sheet->setCellValue('D' . $i, $row['POCY_REF_NO']);
            $sheet->setCellValue('E' . $i, $row['CL_NO']);
            $sheet->setCellValue('F' . $i, $row['INV_NO']);
            $sheet->setCellValue('G' . $i, $row['PRES_AMT']);
            $sheet->setCellValue('H' . $i, $row['APP_AMT']);
            $sheet->setCellValue('I' . $i, $row['BEN_TYPE']);
            $sheet->setCellValue('J' . $i, $row['PAYMENT_DATE']);
            $sheet->setCellValue('K' . $i, $Payee);
            $sheet->setCellValue('L' . $i, $row['CB_TF_AMT']);
            $sheet->setCellValue('M' . $i, $row['CB_DATE']);
            
            // write to csv
            if ($row['TF_AMT'] > 0)
            {
                fputcsv($UploadTransfer, array($row['CL_NO'], $row['TF_AMT'], date('m/d/Y', strtotime($row['CB_DATE'])), '', 'Y', $row['TF_TIMES']));
            }
            
            // calculate sum of amt
            $total_pres_amt += $row['PRES_AMT'];
            $total_app_amt += $row['APP_AMT'];
            $total_tf_amt += $row['CB_TF_AMT'];
        }
        $sheet->getStyle('G4:H' . $i)->getNumberFormat()->setFormatCode("#,###,###,###,###");
        $sheet->getStyle('L4:L' . $i)->getNumberFormat()->setFormatCode("#,###,###,###,###");
        $sheet->setCellValue('G2', $total_pres_amt);
        $sheet->setCellValue('H2', $total_app_amt);
        $sheet->setCellValue('L2', $total_tf_amt);
        $sheet->getStyle('G2:L2')->getNumberFormat()->setFormatCode("#,###,###,###,###");
        
        // close xlsx
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($out_name);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        // close csv
        fclose($UploadTransfer);
    }
        
    /**
     * export list of payments to Claim Fund Report - Excel5 file
     * @param array of payment $payments
     * @param date $cf_date YmdHis
     * @return string fullpath of generated file
     */
    public function export_claim_fund($payments, $cf_date)
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
        $out_name = $path . '/ClaimFund_DLVN_' . $cf_date . '.xlsx';
        
        // create ClaimFund_DLVN.xlsx
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet(0);
        
        // header
        $sheet->setCellValue('A1', "Ngày\nDate");
        $sheet->mergeCells('B1:C1');
        $sheet->setCellValue('B1', "Chứng từ thanh toán\nVoucher No.");
        $sheet->mergeCells('D1:E1');
        $sheet->setCellValue('D1', "Thông tin bồi thường\nClaim information");
        $sheet->setCellValue('F1', "Diễn giải - Description");
        $sheet->mergeCells('G1:J1');
        $sheet->setCellValue('G1', "Số tiền - AMOUNT");
        $sheet->setCellValue('B2', "Ngân hàng\nBank");
        $sheet->setCellValue('C2', "Tiền mặt\nCash");
        $sheet->setCellValue('D2', "Số hợp đồng\nPolicy number");
        $sheet->setCellValue('E2', "Số hồ sơ bồi thường\nClaim number");
        $sheet->setCellValue('G2', "Bổ sung quỹ\nReplenishment");
        $sheet->setCellValue('H2', "Thanh toán bồi thường\nClaim payment");
        $sheet->setCellValue('I2', "Thanh toán các khoản bị trả về\nRepaid refund Claim");
        $sheet->setCellValue('J2', "Tồn quỹ\nBalance");
        $i = 0;
        foreach (range('A', 'J') as $id)
        {
            ++$i;
            $sheet->setCellValue($id . '3', $i);
            $sheet->getColumnDimension($id)->setAutoSize(true);
        }
        $sheet->setCellValue('F4', "Tồn quỹ đầu kỳ - Beginning balance");
        $sheet->getStyle('A1:J3')->getAlignment()->setWrapText(true)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:J4')->getFont()->setBold(true);
        
        // content
        $i = 4;
        foreach ($payments as $payment)
        {
            ++$i;
            $sheet->setCellValue('A' . $i, date('Y-m-d', $payment['TF_DATE']));
            $sheet->setCellValue('B' . $i, $payment['VCB_SEQ']);
            $sheet->setCellValue('D' . $i, $payment['POCY_REF_NO']);
            $sheet->setCellValue('E' . $i, $payment['CL_NO']);
            $sheet->setCellValue('F' . $i, 'Thanh toán bồi thường cho KH ' . $payment['MEMB_NAME']);
            $sheet->setCellValue('H' . $i, $payment['TF_AMT']);
            $sheet->getStyle('H' . $i )->getNumberFormat()->setFormatCode("#,###,###,###,###");
        }
        $sheet->getStyle('A5:E' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical( PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // output
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($out_name);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        return $out_name;
    }
    
    /**
     * export list of payments to Excel5 file
     * @param array of payment $payments
     */
    public function export_list_payments($payments, $name='')
    {
        $header = [
            'A' => 'PARQ_ID',
            'B' => 'CL_NO',
            'C' => 'TF_TIMES',
            'D' => 'TF_AMT',
            'E' => 'DEDUCT_AMT',
            'F' => 'CL_USER_ID',
            'G' => 'PAYMENT_METHOD',
            'H' => 'ACCT_NAME',
            'I' => 'ACCT_NO',
            'J' => 'BANK_NAME',
            'K' => 'BANK_CITY',
            'L' => 'BANK_BRANCH',
            'M' => 'BENEFICIARY_NAME',
            'N' => 'PP_DATE',
            'O' => 'PP_PLACE',
            'P' => 'PP_NO',
            'Q' => 'MEMB_NAME',
            'R' => 'POCY_REF_NO',
            'S' => 'MEMB_REF_NO',
            'T' => 'PRES_AMT',
            'U' => 'APP_AMT',
            'V' => 'BEN_TYPE',
            'W' => 'CL_TYPE',
            'X' => 'PROV_NAME',
            'Y' => 'PAYEE',
            'Z' => 'INV_NO',
            'AA' => 'UPD_DATE',
        ];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet(0);
        foreach ($header as $key => $val)
        {
            $sheet->getColumnDimension($key)->setAutoSize(true);
            $sheet->setCellValue($key . '1', $val);
        }
        $sheet->getStyle('A1:' . $key . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:' . $key . '1')->getFont()->setBold(true);
        $i = 1;
        foreach ($payments as $payment)
        {
            ++$i;
            foreach ($header as $key => $val)
            {
                $sheet->setCellValue($key . $i, $payment[$val]);
            }
        }
        // output
        if ($name === 'unpaid')
        {
            
            $out_name = 'export_Unpaid_Payments_DLVN_' . date('Y-m-d-H-i-s') . '.xlsx';
        }
        else
        {
            $out_name = 'export_ReadyToTransfer_' . $payment['PAYMENT_METHOD'] . '_DLVN_' . date('Y-m-d-H-i-s') . '.xlsx';
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=$out_name");
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }
    
    /**
     * export list of payments to Excel5 file
     * @param array of payment $payments
     */
    public function payments_export($payments)
    {
        $header = [
            'A' => 'PARQ_ID',
            'B' => 'CL_NO',
            'C' => 'TF_TIMES',
            'D' => 'TF_AMT',
            'E' => 'DEDUCT_AMT',
            'F' => 'YN_HOLD',
            'G' => 'CL_USER',
            'H' => 'APP_DATE',
            'I' => 'PAYMENT_METHOD',
            'J' => 'ACCT_NAME',
            'K' => 'ACCT_NO',
            'L' => 'BANK_NAME',
            'M' => 'BANK_CITY',
            'N' => 'BANK_BRANCH',
            'O' => 'BENEFICIARY_NAME',
            'P' => 'PP_DATE',
            'Q' => 'PP_PLACE',
            'R' => 'PP_NO',
            'S' => 'MEMB_NAME',
            'W' => 'POCY_REF_NO',
            'X' => 'MEMB_REF_NO',
            'Y' => 'CL_TYPE',
            'Z' => 'PROV_NAME',
            'AA' => 'PAYEE',
            'AB' => 'INV_NO'
        ];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet(0);
        foreach ($header as $key => $val)
        {
            $sheet->getColumnDimension($key)->setAutoSize(true);
            $sheet->setCellValue($key . '1', $val);
        }
        $sheet->getStyle('A1:' . $key . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:' . $key . '1')->getFont()->setBold(true);
        $i = 1;
        foreach ($payments as $payment)
        {
            ++$i;
            foreach ($header as $key => $val)
            {
                $sheet->setCellValue($key . $i, $payment[$val]);
            }
        }
        // output
        $out_name = 'export_ReadyToTransfer_' . $payment['PAYMENT_METHOD'] . '_DLVN_' . date('Y-m-d-H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=$out_name");
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }
}
