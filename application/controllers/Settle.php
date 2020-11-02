<?php
/**
 * This controller serves all the actions performed by finance to convert Payments from Travel to Upload Settlement in HBS BHV
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Settle extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('settle_model');
        $this->lang->load('menu');
    }
    
    /**
     * Temporary PAGE for CPS BHV - tool to settle debit note
     */
    public function index()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        
        $cps_bhv_db = $this->settle_model->connect_db('cps_bhv');
        $twig['list_of_upload'] = $this->settle_model->get_list_of_upload($cps_bhv_db);

        $crt_date = $this->input->post('crt_date');
        if (empty($crt_date))
        {
            $crt_date = $this->session->flashdata('crt_date');
        }
        if ( ! empty($crt_date))
        {
            $twig['data'] = $this->settle_model->get_data($cps_bhv_db, $crt_date);
            $twig['settle_note'] = $this->settle_model->get_data_settle_note($cps_bhv_db, $crt_date);
        }
        $twig['crt_date'] = $crt_date;
        $this->load->view('fi/settle', $twig);
    }
    
    /**
     * Temporary ACTION for CPS BHV - tool to settle debit note
     */
    public function upload()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        $twig = getUserContext($this);
        $cps_bhv_db = $this->settle_model->connect_db('cps_bhv');
        
        $post = $this->input->post();
        if ( ! empty($post) && isset($post['btnUpload']))
        {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($_FILES['Pay']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            $template = [
                'A' => 'POCY_NO',
                'B' => 'POCY_REF_NO',
                'C' => 'DEBIT_NOTE_NO',
                'D' => 'GENERATE_RECEIPT_TO',
                'E' => 'RECEIVED_DATE',
                'F' => 'PAYMENT_METHOD',
                'G' => 'PAYMENT_CCY',
                'H' => 'RECEIVED_AMOUNT',
                'I' => 'FX_RATE',
                'J' => 'DEDUCT_AT_SOURCE',
                'K' => 'BANK_CHARGE_CCY',
                'L' => 'BANK_CHARGE_AMOUNT',
                'M' => 'REMARKS',
                'N' => 'SETTLING_AMOUNT',
            ];
            
            foreach ($sheetData as $numRow => $row)
            {
                if ($numRow < 2)
                {
                    continue;
                }
                $row['A'] = preg_replace('/[^0-9]/', '', trim($row['A']));
                if (strlen($row['A']) > 14)
                {
                    show_error("Policy No is longer than 14 at row $numRow!");
                }
                $row['B'] = trim($row['B']);
                if (strlen($row['B']) > 25)
                {
                    show_error("Policy Ref No is longer than 25 at row $numRow!");
                }
                $row['C'] = trim($row['C']);
                if (strlen($row['C']) > 8)
                {
                    show_error("Debit Note No is longer than 8 at row $numRow!");
                }
                $row['D'] = trim($row['D']);
                if (strlen($row['D']) > 1)
                {
                    show_error("Generate Receipt To is longer than 1 at row $numRow!");
                }
                if (empty($row['D']))
                {
                    $row['D'] = 'P';
                }
                if ($row['D'] != 'B' && $row['D'] != 'P')
                {
                    show_error("Unacceptable value: Generate Receipt To at row $numRow!");
                }
                $row['E'] = preg_replace('/[^0-9]/', '', trim($row['E']));
                if (strlen($row['E']) > 8)
                {
                    show_error("Received Date is longer than 8 at row $numRow!");
                }
                if (empty($row['E']))
                {
                    show_error("Received Date is empty at row $numRow!");
                }
                $row['F'] = trim($row['F']);
                if (empty($row['F'])) {
                    show_error("Payment Method is empty at row $numRow!");
                }
                $row['F'] = strtoupper($row['F']);
                switch ($row['F'])
                {
                    case 'TT':
                        $row['F'] = 'RCPY_METHOD_TT';
                        break;
                    case 'VISA':
                        $row['F'] = 'RCPY_METHOD_VISA';
                        break;
                    case 'CASH':
                        $row['F'] = 'RCPY_METHOD_CA';
                        break;
                    case 'OTHERS':
                        $row['F'] = 'RCPY_METHOD_OTHERS';
                        break;
                    default:
                        show_error("Unacceptable value: Payment Method at row $numRow!");
                }
                $row['G'] = trim($row['G']);
                if (empty($row['G']))
                {
                    show_error("Payment CCY is empty at row $numRow!");
                }
                if ($row['G'] !== 'VND')
                {
                    show_error("Unacceptable value: Payment CCY at row $numRow!");
                }
                $row['G'] = 'CCY_VNO';
                list($row['H'], $tmp) = explode('.', trim($row['H']));
                $row['H'] = preg_replace('/[^0-9]/', '', $row['H']);
                if (empty($row['H']))
                {
                    show_error("Received Amount is empty at row $numRow!");
                }
                if (strlen($row['H']) > 15)
                {
                    show_error("Received Amount is longer than 15 at row $numRow!");
                }
                $row['I'] = trim($row['I']);
                if (strlen($row['I']) > 30)
                {
                    show_error("FX Rate is longer than 30 at row $numRow!");
                }
                $row['J'] = trim($row['J']);
                if (strlen($row['J']) > 1)
                {
                    show_error("Deduct At Source is longer than 1 at row $numRow!");
                }
                if (empty($row['J']))
                {
                    $row['J'] = 'N';
                }
                if ($row['J'] !== 'Y' && $row['J'] !== 'N')
                {
                    show_error("Unacceptable value: Deduct At Source at row $numRow!");
                }
                $row['K'] = trim($row['K']);
                if ($row['K'] !== 'VND')
                {
                    show_error("Unacceptable value: Bank Charge CCY at row $numRow!");
                }
                $row['K'] = 'CCY_VNO';
                $row['L'] = trim($row['L']);
                if ( ! empty($row['L']))
                {
                    list($row['L'], $tmp) = explode('.', $row['L']);
                    $row['L'] = preg_replace('/[^0-9]/', '', $row['L']);
                }
                
                if (strlen($row['L']) > 15)
                {
                    show_error("Bank Charge Amount is longer than 15 at row row $numRow!");
                }
                $row['M'] = trim($row['M']);
                if (strlen($row['M']) > 300)
                {
                    show_error("Remark is longer than 300 at row $numRow!");
                }
                list($row['N'], $tmp) = explode('.', trim($row['N']));
                $row['N'] = preg_replace('/[^0-9]/', '', $row['N']);
                if (strlen($row['N']) > 15)
                {
                    show_error("Settling Amount is longer than 15 at row $numRow!");
                }
                foreach ($template as $id => $val)
                {
                    if ( ! empty($row[$id]))
                    {
                        $line[$val] = $row[$id];
                    }
                }
                $line['CRT_USER'] = $twig['user_name'];
                $data[] = $line;
            }
            $this->settle_model->insert_batch($cps_bhv_db, $data);
            $this->session->set_flashdata('msg_success', 'Upload Successful!');
            redirect('settle');
        }
    }
    
    /**
     * Temporary ACTION for CPS BHV - tool to settle debit note
     */
    public function find_note_no()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('crt_date', 'crt_date', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            redirect('forbidden');
        }
        $cps_bhv_db = $this->settle_model->connect_db('cps_bhv');
        $hbs_bhv_db = $this->settle_model->connect_db('hbs_bhv');
        
        $crt_date = $this->input->post('crt_date');
        $pocy_ref_no_list = $this->settle_model->get_fields($cps_bhv_db, $crt_date, 'POCY_REF_NO');
        $pocy_ref_no_list = array_column($pocy_ref_no_list, 'POCY_REF_NO');
        $pocy_ref_no_list = array_chunk($pocy_ref_no_list, 1000);
        $notes = [];
        
        $cps_bhv_db->trans_start();
        $this->settle_model->clear_note_no($cps_bhv_db, $crt_date);
        foreach ($pocy_ref_no_list as $pocy_chunk)
        {
            $notes = array_merge($notes, $this->settle_model->get_note_no($hbs_bhv_db, $pocy_chunk));
        }
        $debit_notes = [];
        foreach ($notes as $id => $note)
        {
            $key = $note['POCY_REF_NO'] . '_' . $note['AMT'];
            if (isset($debit_notes[$key]))
            {
                $debit_notes[$key]['NOTE_NO'] .= $note['NOTE_NO'];
            }
            else
            {
                $debit_notes[$key] = $note;
            }
        }
        $this->settle_model->update_debit_note($cps_bhv_db, $crt_date, $debit_notes);
        $cps_bhv_db->trans_complete();
        
        $this->session->set_flashdata('crt_date', $crt_date);
        redirect('settle');
    }
}
