<?php
/**
 * This controller serves all the actions performed by finance
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Carbon\Carbon;

class FI extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('payments_model');
        $this->lang->load('fi');
        $this->lang->load('menu');
    }
    
    /**
     * PAGE - User Statistics
     */
    public function index()
    {
        $this->auth->checkIfOperationIsAllowed('fi_stats');
        $twig = getUserContext($this);
        
        $this->load->model('claim_fund_model');
        $this->load->model('claim_fund_fixed_model');
        $twig['balance'] = $this->claim_fund_model->get_balance() + $this->claim_fund_fixed_model->get_beginning_balance();
        
        // Count claimant payments
        $twig['n_approved_claimant_m'] = $this->payments_model->count_claimant_approved_m();
        $twig['n_approved_claimant_p'] = $this->payments_model->count_claimant_approved_p();
        $twig['n_sheet_claimant'] = $this->payments_model->count_claimant_sheet();
        $twig['n_transferring_claimant'] = $this->payments_model->count_claimant_transferring();
        
        // Count partner payments
        $twig['n_approved_partner'] = $this->payments_model->count_partner_approved();
        $twig['n_sheet_partner'] = $this->payments_model->count_partner_sheet();
        
        $this->lang->load('fi_stats');
        $this->load->view('fi/stats', $twig);
    }
    
    /**
     * PAGE - CLAIMANT - Show all TF_STATUS_APPROVED/TF_STATUS_NEW payments which Payment Method is not PP
     */
    public function approved($cl_type = null)
    {
        $this->auth->checkIfOperationIsAllowed('fi_approved');
        $twig = getUserContext($this);
        $this->load->model('sheets_model');
        switch ($cl_type) {
            case 'M':
                $twig['payments'] = $this->payments_model->get_claimant_approved_m();
                break;
            case 'P':
                $twig['payments'] = $this->payments_model->get_claimant_approved_p();
                break;
            default:
            $twig['payments'] = $this->payments_model->get_claimant_approved();
                break;
        }
        
        $twig['sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_CLAIMANT);
        
        $this->lang->load('columns');
        $this->lang->load('fi_approved');
        $this->load->view('fi/approved', $twig);
    }
    
    /**
     * ACTION - CLAIMANT - Figure next status if a payment is approved depend on Payment Method and Transfer Amount
     * @param int $tf_amt
     * @param string $payment_method
     * @param string $bank_name
     * @param string $acct_name
     * @param string $acct_no
     */
    private function _to_status($tf_amt, $payment_method, $bank_name, $acct_name, $acct_no)
    {
        if (empty($tf_amt))
        {
            return TF_STATUS_TRANSFERRED;
        }
        $bank_name = preg_replace('/[^a-z]/', '', strtolower($bank_name));
        if ($bank_name == 'coopbank')
        {
            return TF_STATUS_TRANSFERRING;
        }
        if ($payment_method == 'TT')
        {
            $this->load->model('foreigner_model');
            if ($bank_name == 'vietcombank' && $this->foreigner_model->is_foreigner($acct_name, $acct_no))
            {
                return TF_STATUS_TRANSFERRING;
            }
            return TF_STATUS_SHEET;
        }
        elseif ($payment_method == 'CH')
        {
            if ($bank_name == 'vietcombank')
            {
                return TF_STATUS_TRANSFERRING;
            }
            return TF_STATUS_SHEET;
        }
        elseif ($payment_method == 'CQ')
        {
            return TF_STATUS_TRANSFERRING;
        }
        elseif ($payment_method == 'PP')
        {
            return TF_STATUS_SHEET_PAYPREM;
        }
        show_error(lang('fi_to_status_error'));
    }
    
    /**
     * PAGE - CLAIMANT - Show all Sheets which have status SHEET OR TRANSFERRING
     */
    public function sheets()
    {
        $this->auth->checkIfOperationIsAllowed('fi_sheets');
        $twig = getUserContext($this);
        
        $this->load->model('sheets_model');
        $twig['sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_CLAIMANT);
        $twig['transferring_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_TRANSFERRING, SHEET_TYPE_CLAIMANT);
        foreach ($twig['sheets'] as $key => $sheet)
        {
            $twig['sheets'][$key]['n_payments'] = $this->payments_model->count_sheet_payments($sheet['SHEET_ID']);
            $twig['sheets'][$key]['total_amt'] = $this->payments_model->get_sheet_amt($sheet['SHEET_ID']);
        }
        foreach ($twig['transferring_sheets'] as $key => $sheet)
        {
            $twig['transferring_sheets'][$key]['n_payments'] = $this->payments_model->count_sheet_payments($sheet['SHEET_ID']);
            $twig['transferring_sheets'][$key]['total_amt'] = $this->payments_model->get_sheet_amt($sheet['SHEET_ID']);
        }
        $this->lang->load('fi_sheets');
        $this->load->view('fi/sheets', $twig);
    }
    
    /**
     * PAGE - Show all payments in a sheet
     */
    public function sheet($sheet_id)
    {
        $this->auth->checkIfOperationIsAllowed('fi_sheet');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->lang->load('fi_sheet');
        $this->load->model('sheets_model');
        
        $twig['sheet'] = $this->sheets_model->get($sheet_id);
        $twig['sheets_meger'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_CLAIMANT);
        if (empty($twig['sheet']) OR $twig['sheet']['SHEET_STATUS'] == SHEET_STATUS_TRANSFERRED)
        {
            redirect('fi/sheets');
        }
        $twig['sheet']['TOTAL_AMT'] = $this->payments_model->get_sheet_amt($sheet_id);
        $twig['payments'] = $this->payments_model->get_sheet_payments($sheet_id);
        if ($twig['sheet']['SHEET_TYPE'] == SHEET_TYPE_CLAIMANT)
        {
            $this->load->view('fi/sheet', $twig);
        }
        if ($twig['sheet']['SHEET_TYPE'] == SHEET_TYPE_PARTNER)
        {
            $this->load->model('partner_model');
            $twig['bank_info'] = $this->partner_model->get_bank_info();
            $this->load->view('fi/partner_sheet', $twig);
        }
    }
    
    /**
     * PAGE - Show all payments in a Vietcombank sheet
     */
    public function vcbsheet($sheet_id)
    {
        $this->auth->checkIfOperationIsAllowed('fi_vcbsheet');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        
        $this->load->model('vcbsheets_model');
        $twig['vcbsheets'] = $this->vcbsheets_model->get_VCBSheets($sheet_id);
        if (empty($twig['vcbsheets']))
        {
            redirect('fi/sheets');
        }
        $this->load->model('sheets_model');
        $twig['sheet'] = $this->sheets_model->get($sheet_id);
        $twig['sheet']['TOTAL_AMT'] = $this->payments_model->get_sheet_amt($sheet_id);
        
        if ($twig['sheet']['SHEET_STATUS'] == SHEET_STATUS_SHEET)
        {
            redirect("fi/sheet/$sheet_id");
        }
        $this->lang->load('fi_vcbsheet');
        $this->load->view('fi/vcbsheet', $twig);
    }
    
    /**
     * PAGE - CLAIMANT - Show all Transferring payments which are not in any sheets
     */
    public function transferring()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferring');
        $twig = getUserContext($this);
        
        $this->lang->load('columns');
        $twig['payments'] = $this->payments_model->get_transferring_payments();
        
        $this->lang->load('fi_transferring');
        $this->load->view('fi/transferring', $twig);
    }
    
    /**
     * PAGE - CLAIMANT - Show Pay Form for all Selected Transferring Payments which FI wants to Pay
     * Data are coming from HTML form
     */
    public function transferring_pay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferring');
        $twig = getUserContext($this);
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/transferring');
        }
        
        $payments = [];
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        foreach ($paym_ids as $paym_id)
        {
            $payments[] = $this->payments_model->get_payment($paym_id);
        }
        
        $twig['paym_ids'] = $this->input->post('paym_ids');
        $twig['payments'] = $payments;
        
        $this->lang->load('columns');
        $this->lang->load('fi_transferring');
        $this->load->view('fi/transferring_pay', $twig);
    }
    
    /**
     * PAGE & ACTION - Search all Payments
     */
    public function transferred()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferred');
        $twig = getUserContext($this);
        
        $this->load->model('transfer_status_model');
        $this->lang->load('columns');
        $this->lang->load('transferred');
        
        $post = $this->input->post();
        if ( ! empty($post) && isset($post['btnSearch']))
        {
            $condition = [];
            if ( ! empty($post['cl_no']))
            {
                $condition['CL_NO'] = $post['cl_no'];
            }
            if ( ! empty($post['tf_status_id']))
            {
                $condition['sys.TF_STATUS_ID'] = $post['tf_status_id'];
            }
            if ( ! empty($post['pocy_ref_no']))
            {
                $condition['POCY_REF_NO'] = $post['pocy_ref_no'];
            }
            if ( ! empty($post['memb_ref_no']))
            {
                $condition['MEMB_REF_NO'] = $post['memb_ref_no'];
            }
            if ( ! empty($post['memb_name']))
            {
                $condition['MEMB_NAME'] = $post['memb_name'];
            }
            if ( ! empty($post['tf_date_from']))
            {
                $condition['TF_DATE >='] = DateTime::createFromFormat('d/m/Y', $post['tf_date_from'])->format('Y-m-d');
            }
            if ( ! empty($post['tf_date_to']))
            {
                $condition['TF_DATE <='] = DateTime::createFromFormat('d/m/Y', $post['tf_date_to'])->format('Y-m-d');
            }
            if ( ! empty($post['app_date_from']))
            {
                $condition['APP_DATE >='] = DateTime::createFromFormat('d/m/Y', $post['app_date_from'])->format('Y-m-d');
            }
            if ( ! empty($post['app_date_to']))
            {
                $condition['APP_DATE <='] = DateTime::createFromFormat('d/m/Y', $post['app_date_to'])->format('Y-m-d');
            }
            if ( ! empty($post['upd_date_from']))
            {
                $condition['UPD_DATE >='] = DateTime::createFromFormat('d/m/Y', $post['upd_date_from'])->format('Y-m-d');
            }
            if ( ! empty($post['upd_date_to']))
            {
                $condition['UPD_DATE <='] = DateTime::createFromFormat('d/m/Y', $post['upd_date_to'])->format('Y-m-d');
            }
            if ( ! empty($condition))
            {
                $payments = $this->payments_model->search_payments($condition);
                if (count($payments) > MAX_RESULT)
                {
                    $twig['msg_danger'] = lang('fi_transferred_max_result');
                }
                else
                {
                    $twig['payments'] = $payments;
                }
            }
        }
        
        $twig['post'] = $post;
        $twig['transfer_status'] = $this->transfer_status_model->get_transfer_status();
        $this->load->view('fi/transferred', $twig);
    }
    
    /**
     * PAGE - Show all Returned To Claim Payments
     */
    public function returned_claim()
    {
        $this->auth->checkIfOperationIsAllowed('fi_returned_claim');
        $twig = getUserContext($this);
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        
        $this->load->model('notes_model');
        $this->load->model('sheets_model');
        
        $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_RETURNED_TO_CLAIM);
        foreach ($twig['payments'] as $id => $payment)
        {
            $twig['payments'][$id]['NOTE'] = $this->notes_model->get_last_note($payment['PAYM_ID']);
        }
        $twig['claimant_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_CLAIMANT);
        $twig['partner_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_PARTNER);
        
        $this->lang->load('columns');
        $this->lang->load('fi_returned_claim');
        $this->load->view('fi/returned_claim', $twig);
    }

    /**
     * PAGE - Show all Renewed To Claim Payments
     */
    public function renewed_claim()
    {
        $this->auth->checkIfOperationIsAllowed('fi_returned_claim');
        $twig = getUserContext($this);
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        
        $this->load->model('renew_claim_model');
        $this->load->model('sheets_model');
        
        $twig['payments'] = $this->payments_model->get_renewed_payments();
        
        $this->lang->load('columns');
        $this->lang->load('fi_returned_claim');
        $this->load->view('fi/renewed_claim', $twig);
    }
    
    /**
     * PAGE - Show Repay Form for all Selected Payments which FI wants to Repay
     * Data are coming from HTML form
     */
    public function returned_claim_repay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_returned_claim');
        $twig = getUserContext($this);
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/returned_claim');
        }
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $payments = [];
        foreach ($paym_ids as $paym_id)
        {
            $payments[] = $this->payments_model->get_payment($paym_id);
        }
        $sheet_id = $this->input->post('sheet_id');
        if ( ! empty($sheet_id))
        {
            $this->load->model('sheets_model');
            $sheet = $this->sheets_model->get($sheet_id);
            $twig['sheet_name'] = $sheet['SHEET_NAME'];
            if ( ! empty($sheet['SHEET_UNAME']))
            {
                $twig['sheet_name'] .= ' (' . $sheet['SHEET_UNAME'] . ')';
            }
        }
        else
        {
            $twig['sheet_name'] = lang('fi_modal_select_new_sheet');
        }
        $twig['paym_ids'] = $this->input->post('paym_ids');
        $twig['sheet_id'] = $sheet_id;
        $twig['payments'] = $payments;

        $this->lang->load('columns');
        $this->lang->load('fi_returned_claim');
        $this->load->view('fi/returned_claim_repay', $twig);
    }
    
    /**
     * PAGE - Show all Payments which Finance will transfer money to partner (DLVN)
     * Included statuses:
     *     - TF_STATUS_APPROVED & Payment Method is PP
     *     - TF_STATUS_NEW & Payment Method is PP
     *     - TF_STATUS_DLVN_CANCEL
     *     - TF_STATUS_DLVN_PAYPREM
     */
    public function partner()
    {
        $this->auth->checkIfOperationIsAllowed('fi_partner');
        $twig = getUserContext($this);
        
        $this->lang->load('columns');
        $twig['payments'] = $this->payments_model->get_payments_for_partner();
        
        $this->load->model('sheets_model');
        $twig['sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_PARTNER);
        
        $this->load->model('partner_model');
        $twig['bank_info'] = $this->partner_model->get_bank_info();
        
        $this->lang->load('fi_partner');
        $this->load->view('fi/partner', $twig);
    }
    
    /**
     * PAGE - Show all SHEET_TYPE_PARTNER Sheets
     */
    public function sheets_partner()
    {
        $this->auth->checkIfOperationIsAllowed('fi_sheets_partner');
        $twig = getUserContext($this);
        
        $this->load->model('sheets_model');
        $twig['sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_PARTNER);
        $twig['transferring_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_TRANSFERRING, SHEET_TYPE_PARTNER);
        foreach ($twig['sheets'] as $key => $sheet)
        {
            $twig['sheets'][$key]['n_payments'] = $this->payments_model->count_sheet_payments($sheet['SHEET_ID']);
            $twig['sheets'][$key]['total_amt'] = $this->payments_model->get_sheet_amt($sheet['SHEET_ID']);
        }
        foreach ($twig['transferring_sheets'] as $key => $sheet)
        {
            $twig['transferring_sheets'][$key]['n_payments'] = $this->payments_model->count_sheet_payments($sheet['SHEET_ID']);
            $twig['transferring_sheets'][$key]['total_amt'] = $this->payments_model->get_sheet_amt($sheet['SHEET_ID']);
        }
        $this->load->view('fi/partner_sheets', $twig);
    }
    
    /**
     * PAGE - Users will selected time range to export Claim Fund Reports
     */
    public function claim_fund()
    {
        $this->auth->checkIfOperationIsAllowed('fi_claim_fund');
        $twig = getUserContext($this);
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $this->lang->load('claim_fund');
        $this->load->model('claim_fund_model');
        
        $twig['reports'] = $this->claim_fund_model->get_list();
        $post = $this->input->post();
        $time = $this->session->flashdata('time');
        if ( ! empty($time))
        {
            $post['time'] = $time;
            $post['btnSearch'] = '';
        }
        if ( ! empty($post))
        {
            $first_report = end($twig['reports']);
            $datetime = DateTime::createFromFormat('m/Y', $post['time']);
            $twig['date_to'] = $datetime->modify('last day of')->format('d/m/Y');
            $twig['date_from'] = $datetime->modify('first day of')->format('d/m/Y');
            $twig['prev_time'] = $datetime->modify('-1 months')->format('m/Y');
            
            $this->load->model('claim_fund_fixed_model');
            $twig['bebal'] = $this->claim_fund_fixed_model->get_ending_balance($twig['prev_time']);
            $twig['enbal'] = $this->claim_fund_fixed_model->get_ending_balance($post['time']);
            $twig['total'] = $this->claim_fund_model->get_total($post['time']);
            $twig['details'] = $this->claim_fund_model->get_details($post['time']);
            if (empty($twig['bebal']))
            {
                if ($first_report['CLFU_MON_YEAR'] == $post['time'])
                {
                    $twig['set_bebal'] = 1;
                }
                else
                {
                    $twig['set_prev_enbal'] = 1;
                }
            }
            elseif (empty($twig['enbal']))
            {
                $twig['set_enbal'] = 1;
            }
            if ( ! isset($post['btnSearch']) && ! empty($twig['details']))
            {
                $this->claim_fund_model->export($twig['details'], $twig['date_from'], $twig['date_to'], $twig['bebal'], $twig['total']);
            }
        }
        $twig['post'] = $post;
        $this->load->view('fi/claim_fund', $twig);
    }
    
    /**
     * PAGE - Users will selected time range to export Claim Fund Reports
     */
    public function claim_bordereaux()
    {
        $this->auth->checkIfOperationIsAllowed('fi_claim_bordereaux');
        $twig = getUserContext($this);
        $this->lang->load('claim_bordereaux');
        
        $post = $this->input->post();
        if ( ! empty($post))
        {
            $this->load->model('claim_bordereaux_model');
            $twig['report'] = $this->claim_bordereaux_model->report($post['cb_month'], $post['cb_year']);
        }
        $twig['post'] = $post;
        $this->load->view('fi/claim_bordereaux', $twig);
    }
    
    /**
     * PAGE - Show all transferred Vietcombank Sheets
     */
    public function transferred_sheets()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferred_sheets');
        $twig = getUserContext($this);
        $this->lang->load('transferred_sheets');
        $this->load->model('vnbt_model');
        $this->load->model('sheets_model');
        
        $sheet_ids = $this->vnbt_model->get_all_sheets();
        foreach ($sheet_ids as $row)
        {
            $twig['sheets'][$row['SHEET_ID']] = $this->sheets_model->get($row['SHEET_ID']);
        }
        
        $post = $this->input->post();
        if ( ! empty($post))
        {
            $this->lang->load('fi_vcbsheet');
            $this->load->model('vcbsheets_model');
            $twig['vcbsheets'] = $this->vcbsheets_model->get_VCBSheets($post['sheet_id']);
            $twig['vnbt_header'] = $this->vnbt_model->get_header($post['sheet_id']);
            $twig['vnbt_body'] = $this->vnbt_model->get_body($post['sheet_id']);
        }
        
        $twig['post'] = $post;
        $this->load->view('fi/transferred_sheets', $twig);
    }
    
    /**
     * PAGE - Show all TRANSFERRED_DLVN_PAYPREM Payments
     */
    public function transferred_dlvn_payprem()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferred_dlvn_payprem');
        $twig = getUserContext($this);
        $this->lang->load('transferred_dlvn_payprem');
        
        $post = $this->input->post();
        if ( ! empty($post))
        {
            $twig['transfers'] = $this->transfers_model->get_all_transfers($post['cl_no']);
        }
        $twig['claims'] = $this->payments_model->get_all_claims();
        $twig['post'] = $post;
        $this->lang->load('columns');
        $this->load->view('fi/transferred_mantis', $twig);
    }
    
    /**
     * ACTION - Finance is ready to transfer multiple approved payments
     * paym_ids are coming from a HTML form, each paym_id is separated by commas (,)
     */
    public function confirm()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect($_SERVER['HTTP_REFERER']);
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('transfers_model');
        $this->load->model('claim_fund_model');
        $this->load->model('hbs_model');
        $this->load->model('sheets_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['YN_HOLD'] == 'Y')
            {
                continue;
            }
            $to_status_id = $this->_to_status($payment['TF_AMT'], $payment['PAYMENT_METHOD'], $payment['BANK_NAME'], $payment['ACCT_NAME'], $payment['ACCT_NO']);
            if ($to_status_id == TF_STATUS_TRANSFERRED)
            {
                $tf_date = date('Y-m-d');
                $vcb_seq = 'PCV';
                if ($payment['YN_CLBO'] == 'Y')
                {
                    $this->claim_bordereaux_model->add($payment, $tf_date, $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'], CLBO_TYPE_PAYMENT);
                    $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT, HIST_FIELD_YN_CLBO, $payment['YN_CLBO'], 'N');
                }
                $this->payments_model->transferred_full_deduction($paym_id, $tf_date);
                $this->transfers_model->transferred($payment, $tf_date, $vcb_seq);
                $this->claim_fund_model->pay_claim($payment, $tf_date, $vcb_seq);
                $this->hbs_model->transferred($payment, $tf_date, $vcb_seq);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT, HIST_FIELD_TF_DATE, $payment['TF_DATE'], $tf_date);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT, HIST_FIELD_VCB_SEQ, $payment['VCB_SEQ'], $vcb_seq);
            }
            elseif ($to_status_id == TF_STATUS_SHEET)
            {
                $default_sheet = $this->sheets_model->default_sheet();
                $sheet_id = $this->input->post('sheet_id');
                if (empty($sheet_id))
                {
                    $sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_CLAIMANT);
                    $_POST['sheet_id'] = $sheet_id;
                }
                $this->payments_model->to_sheet($paym_id, $sheet_id, $to_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT, HIST_FIELD_SHEET_ID, $payment['SHEET_ID'], $sheet_id);
            }
            elseif ($to_status_id == TF_STATUS_TRANSFERRING)
            {
                $this->payments_model->to_transferring($paym_id);
            }
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $to_status_id);
        }
        $this->db->trans_complete();
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ACTION - a Third Party payment is confirmed by Finance will be moved to next status
     * paym_ids are coming from a HTML form, each paym_id is separated by commas (,)
     */
    public function confirm_partner()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/partner');
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('sheets_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $sheet_id = $this->input->post('sheet_id');
        if (empty($sheet_id))
        {
            $default_sheet = $this->sheets_model->default_sheet();
            $sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_PARTNER);
        }
            
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['YN_HOLD'] == 'Y')
            {
                continue;
            }
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_APPROVED:
                case TF_STATUS_NEW:
                    $to_status = TF_STATUS_SHEET_PAYPREM;
                    break;
                case TF_STATUS_DLVN_CANCEL:
                    $to_status = TF_STATUS_SHEET_DLVN_CANCEL;
                    break;
                case TF_STATUS_DLVN_PAYPREM:
                    $to_status = TF_STATUS_SHEET_DLVN_PAYPREM;
                    break;
            }
            $this->payments_model->to_sheet($paym_id, $sheet_id, $to_status);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT_PARTNER, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $to_status);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_SELECT_PARTNER, HIST_FIELD_SHEET_ID, $payment['SHEET_ID'], $sheet_id);
        }
        $this->db->trans_complete();
        
        redirect('fi/partner');
    }
    
    /**
     * ACTION - Finance return a payment to Claim, status will be changed to Returned To Claim
     * Reason to Return is coming from an HTML form
     */
    public function return_to_claim()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        $this->load->model('notes_model');
        $this->load->model('payments_history_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $return_reason = $this->input->post('return_reason');
        
        $this->db->trans_start();
        
        foreach ($paym_ids as $paym_id)
        {
            $tf_status_id = $this->payments_model->get_value($paym_id, 'TF_STATUS_ID');

            $this->payments_model->set_transfer_status($paym_id, TF_STATUS_RETURNED_TO_CLAIM);
            $this->notes_model->add($paym_id, NOTE_TYPE_RETURN, $return_reason);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_RETURN_CL, HIST_FIELD_TF_STATUS, $tf_status_id, TF_STATUS_RETURNED_TO_CLAIM);
        }
        $this->db->trans_complete();

        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * ACTION - Finance return a payment to Claim, status will be changed to Returned To Claim
     * Reason to Return is coming from an HTML form
     */
    public function renew_to_claim()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        $this->load->model('payments_history_model');
        $this->load->model('renew_claim_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $renew_reason = $this->input->post('renew_reason');
        
        $this->db->trans_start();
        
        foreach ($paym_ids as $paym_id)
        {
            $tf_status_id = $this->payments_model->get_value($paym_id, 'TF_STATUS_ID');
            $this->payments_model->set_transfer_status($paym_id, TF_STATUS_NEW);
            $this->renew_claim_model->add($paym_id, $renew_reason);
            $this->payments_model->set_value($paym_id, 	'SHEET_ID', null);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_RENEW_CL, HIST_FIELD_TF_STATUS, $tf_status_id, TF_STATUS_NEW);
        }
        $this->db->trans_complete();

        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ACTION - Finance return a payment to Claim, status will be changed to Returned To Claim
     * @param int $paym_id
     * @param string $reason
     * Data are coming from an HTML form
     */
    public function sheet_return()
    {
        $this->auth->checkIfOperationIsAllowed('fi_return_to_claim');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        $this->form_validation->set_rules('return_reason', 'return_reason', 'trim|required|max_length[1000]');
        $sheet_id = $this->input->post('sheet_id');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("fi/sheet/$sheet_id");
        }
        
        $this->load->model('sheets_model');
        $this->load->model('payments_history_model');
        $this->load->model('notes_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $return_reason = $this->input->post('return_reason');
        $sheet = $this->sheets_model->get($sheet_id);
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            $this->payments_model->out_sheet($paym_id, TF_STATUS_RETURNED_TO_CLAIM);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_RETURN_CL, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_RETURNED_TO_CLAIM);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_RETURN_CL, HIST_FIELD_SHEET_ID, $sheet_id, '');
            $this->notes_model->add($paym_id, NOTE_TYPE_RETURN, $return_reason);
        }
        if (empty($this->payments_model->count_sheet_payments($sheet_id)))
        {
            $this->sheets_model->delete($sheet_id);
            $this->db->trans_complete();
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_CLAIMANT)
            {
                redirect("fi/sheets");
            }
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_PARTNER)
            {
                redirect("fi/partner/sheets");
            }
        }
        $this->db->trans_complete();
        
        redirect("fi/sheet/$sheet_id");
    }
    
    /**
     * ACTION - Finance confirms that a TRANSFERRED payment is refund, status will be changed to Returned To Claim
     * Data are coming from HTML form
     */
    public function refund()
    {
        $this->auth->checkIfOperationIsAllowed('fi_refund');
        $this->load->model('transfers_model');
        $this->load->model('claim_fund_model');
        $this->load->model('notes_model');
        $this->load->model('payments_history_model');
        $this->load->model('hbs_model');
    
        $post = $this->input->post();
        $payment = $this->payments_model->get_payment($post['paym_id']);
        
        $this->db->trans_start();
        $this->payments_model->set_transfer_status($post['paym_id'], TF_STATUS_RETURNED_TO_CLAIM);
        $this->transfers_model->refund($post['paym_id'], $post['refund_date'], $post['refund_vcb_seq'], $post['refund_reason']);
        $this->notes_model->add($post['paym_id'], NOTE_TYPE_RETURN, $post['refund_reason']);
        $this->claim_fund_model->refund($payment, $post['refund_date'], $post['refund_vcb_seq']);
        $this->hbs_model->cancel($payment);
        $this->payments_history_model->add($post['paym_id'], HIST_TYPE_FI_REFUND, HIST_FIELD_TF_STATUS, TF_STATUS_TRANSFERRED, TF_STATUS_RETURNED_TO_CLAIM);        
        $this->db->trans_complete();

        redirect('payments/' . $post['paym_id']);
    }
    
    /**
     * PAGE & ACTION - Finance upload a list of TRANSFERRED payments to refund, status will be changed to Returned To Claim
     * Data are coming from XLSX file
     */
    public function refund_upload()
    {
        $this->auth->checkIfOperationIsAllowed('fi_refund_upload');
        $twig = getUserContext($this);
        $this->lang->load('refund');
        $this->lang->load('columns');
        
        $post = $this->input->post();
        if ( ! empty($post) && isset($post['btnUpload']))
        {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($_FILES['returned_xlsx']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $this->load->model('transfers_model');
            $this->load->model('claim_fund_model');
            $this->load->model('notes_model');
            $this->load->model('payments_history_model');
            $this->load->model('hbs_model');
            
            $template = array(
                'A' => 'Claim No',
                'B' => 'Transfer Amt',
                'C' => 'Refund Date',
                'D' => 'Refund VCB SEQ',
                'E' => 'Refund Reason',
            );
            
            $this->db->trans_start();
            foreach ($sheetData as $key => $row)
            {
                if ($key < 2)
                {
                    foreach ($template as $col => $name)
                    {
                        if (trim($row[$col]) !== $name)
                        {
                            show_error(sprintf(lang('fi_upload_error_template'), $col, $name));
                        }
                    }
                    continue;
                }
                $row['C'] = DateTime::createFromFormat('m/d/Y', $row['C']);
                if ($row['C'] !== FALSE)
                {
                    $row['C'] = $row['C']->format('Y-m-d');
                }
                else
                {
                    $sheetData[$key]['F'] = lang('fi_upload_error_returned_date');
                    continue;
                }
                $payments = $this->payments_model->get_transferred_payments_by_claim($row['A'], $row['B']);
                $n = count($payments);
                if ($n > 1)
                {
                    $sheetData[$key]['F'] = lang('fi_upload_error_duplicate');
                    continue;
                }
                elseif ($n < 1)
                {
                    $sheetData[$key]['F'] = lang('fi_upload_error_not_found');
                    continue;
                }
                else
                {
                    $sheetData[$key]['F'] = lang('fi_upload_success');
                    foreach ($payments as $payment) {}
                }
                $this->payments_model->set_transfer_status($payment['PAYM_ID'], TF_STATUS_RETURNED_TO_CLAIM);
                $this->transfers_model->refund($payment['PAYM_ID'], $row['C'], $row['D'], $row['E']);
                $this->notes_model->add($payment['PAYM_ID'], NOTE_TYPE_RETURN, $row['E']);
                $this->claim_fund_model->refund($payment, $row['C'], $row['D']);
                $this->hbs_model->cancel($payment);
                $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_FI_REFUND, HIST_FIELD_TF_STATUS, TF_STATUS_TRANSFERRED, TF_STATUS_RETURNED_TO_CLAIM);
            }
            $this->db->trans_complete();
            $twig['sheetData'] = $sheetData;
        }
        
        $this->load->view('fi/refund_upload', $twig);
    }
    
    /**
     * ACTION - Finance cancels a Returned To Claim payment, status will be changed to Approved or Transferred
     * @param int $paym_id is coming from HTLM form
     */
    public function cancel()
    {
        $this->auth->checkIfOperationIsAllowed('fi_cancel');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/returned_claim');
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('sheets_model');
        $this->load->model('transfers_model');
        $this->load->model('claim_fund_model');
        $this->load->model('hbs_model');
        $this->load->model('transfer_status_model');
        
        $msg_success = [];
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_RETURNED_TO_CLAIM)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/returned_claim');
            }
            $prev_sheet_id = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_SHEET_ID, HIST_TYPE_FI_RETURN_CL);
            $prev_tf_status_id = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_TF_STATUS, HIST_TYPE_FI_RETURN_CL);
            if (empty($prev_tf_status_id))
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_not_found_prev'));
                redirect('fi/returned_claim');
            }
            if (in_array($prev_tf_status_id, array(TF_STATUS_APPROVED, TF_STATUS_TRANSFERRING, TF_STATUS_TRANSFERRED)))
            {
                $to_status = $prev_tf_status_id;
            }
            else
            {
                $sheet = $this->sheets_model->get($prev_sheet_id);
                if ($sheet['SHEET_STATUS'] == SHEET_STATUS_SHEET)
                {
                    $to_status = $prev_tf_status_id;
                }
                else
                {
                    switch ($prev_tf_status_id)
                    {
                        case TF_STATUS_SHEET:
                        case TF_STATUS_SHEET_PAYPREM:
                            $to_status = TF_STATUS_APPROVED;
                            break;
                        case TF_STATUS_SHEET_DLVN_CANCEL:
                            $to_status = TF_STATUS_DLVN_CANCEL;
                            break;
                        case TF_STATUS_SHEET_DLVN_PAYPREM:
                            $to_status = TF_STATUS_DLVN_PAYPREM;
                            break;    
                    }
                }
            }
            if (in_array($to_status, array(TF_STATUS_APPROVED, TF_STATUS_TRANSFERRING, TF_STATUS_TRANSFERRED)))
            {
                $this->payments_model->set_transfer_status($paym_id, $to_status);
                if ($to_status == TF_STATUS_TRANSFERRED)
                {
                    $transfer = $this->transfers_model->get($paym_id, 'Returned');
                    $payment = $this->payments_model->get_payment($paym_id);
                    $this->transfers_model->cancel_refund($paym_id);
                    $this->claim_fund_model->cancel_refund($paym_id, $transfer['REFUND_DATE'], $transfer['REFUND_VCB_SEQ']);
                    $this->hbs_model->transferred($payment, $transfer['TF_DATE'], $transfer['TF_VCB_SEQ']);
                }
            }
            else
            {
                $this->payments_model->to_sheet($paym_id, $prev_sheet_id, $to_status, false);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_CANCEL, HIST_FIELD_SHEET_ID, '', $prev_sheet_id);
            }
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_CANCEL, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, $to_status);
            $msg_success[] = $payment['CL_NO'] . ': ' . sprintf(lang('fi_move_desc'), $this->transfer_status_model->get_name($to_status));
        }
        $this->db->trans_complete();

        $this->session->set_flashdata('msg_success', implode('<br>', $msg_success));
        redirect('fi/returned_claim');
    }
    
    /**
     * ACTION - Finance edit a friendly Sheet Name
     * Data are coming from an HTML form
     */
    public function edit()
    {
        $this->auth->checkIfOperationIsAllowed('fi_edit');
        
        $this->load->model('sheets_model');
        $twig['n_sheets'] = $this->sheets_model->change_name($this->input->post('sheet_id'), $this->input->post('sheet_uname'));
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ACTION - Finance ejects multiple payment out of a sheet
     * If Sheet Type is SHEET_TYPE_CLAIMANT, status will be changed to Approved
     * If Sheet Type is SHEET_TYPE_PARTNER, status will be changed to Approved or DLVN Cancel or DLVN PayPrem
     * @param string paym_ids are coming from a HTML form, each paym_id is separated by commas (,)
     * @param int sheet_id is coming from a HTML form
     */
    public function eject()
    {
        $this->auth->checkIfOperationIsAllowed('fi_eject');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        $sheet_id = $this->input->post('sheet_id');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("fi/sheet/$sheet_id");
        }
        
        $this->load->model('sheets_model');
        $this->load->model('payments_history_model');
        
        $sheet = $this->sheets_model->get($sheet_id);
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_SHEET:
                case TF_STATUS_SHEET_PAYPREM:
                    $to_status = TF_STATUS_APPROVED;
                    break;
                case TF_STATUS_SHEET_DLVN_CANCEL:
                    $to_status = TF_STATUS_DLVN_CANCEL;
                    break;
                case TF_STATUS_SHEET_DLVN_PAYPREM:
                    $to_status = TF_STATUS_DLVN_PAYPREM;
                default:
                    show_error(lang('fi_error_another_user'));
            }
            $this->payments_model->out_sheet($paym_id, $to_status);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_EJECT, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $to_status);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_EJECT, HIST_FIELD_SHEET_ID, $sheet_id, '');
        }
        if (empty($this->payments_model->count_sheet_payments($sheet_id)))
        {
            $this->sheets_model->delete($sheet_id);
            $this->db->trans_complete();
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_CLAIMANT)
            {
                redirect("fi/sheets");
            }
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_PARTNER)
            {
                redirect("fi/partner/sheets");
            }
        }
        $this->db->trans_complete();
        redirect("fi/sheet/$sheet_id");
    }
    
    /**
     * ACTION - Finance undo a payment from TRANSFERRING to APPROVED
     * @param int paym_id is coming from a HTML form
     */
    public function undo()
    {
        $this->auth->checkIfOperationIsAllowed('fi_undo');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/transferring');
        }
        
        $this->load->model('payments_history_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_TRANSFERRING)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/transferring');
            }
            $this->payments_model->set_transfer_status($paym_id, TF_STATUS_APPROVED);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UNDO, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_APPROVED);
        }
        $this->db->trans_complete();
        
        redirect('fi/transferring');
    }
    
    /**
     * ACTION - Finance close a sheet to transfer to bank
     * @param int sheet_id is coming from a HTML form
     */
    public function close()
    {
        $this->auth->checkIfOperationIsAllowed('fi_close');
        $this->lang->load('vcbsheet');
        $this->load->model('sheets_model');
        $this->load->model('vcbsheets_model');
        $this->load->model('payments_history_model');
        
        $sheet_id = $this->input->post('sheet_id');
        $sheet_type = $this->input->post('sheet_type');
        
        $sheet = $this->sheets_model->get($sheet_id);
        if ($sheet['SHEET_STATUS'] != SHEET_STATUS_SHEET)
        {
            show_error(lang('fi_error_another_user'));
        }
        
        $this->db->trans_start();
        $this->payments_model->sheet_to_transferring($sheet_id);
        $this->sheets_model->set_status($sheet_id, SHEET_STATUS_TRANSFERRING);
        $vcbsheets = $this->payments_model->get_vcbsheet_payments($sheet_id, $sheet_type);
        if ($sheet_type == SHEET_TYPE_CLAIMANT)
        {
            if ( ! $this->vcbsheets_model->set_VCBSheets($sheet_id, $vcbsheets))
            {
                $this->session->set_flashdata('msg_danger', lang('vcbsheet_max_length_paym_ids'));
                redirect("fi/vcbsheet/$sheet_id");
            }
        }
        elseif ($sheet_type == SHEET_TYPE_PARTNER)
        {
            $this->load->model('partner_model');
            $bank_info = $this->partner_model->get_bank_info();
            if (strlen($vcbsheets['PAYM_ID']) >= MAX_LENGTH_PAYM_IDS)
            {
                $this->session->set_flashdata('msg_danger', lang('vcbsheet_max_length_paym_ids'));
                redirect("fi/vcbsheet/$sheet_id");
            }
            $this->vcbsheets_model->set_VCBSheets_partner($sheet_id, $vcbsheets, $bank_info);
        }
        $payments = $this->payments_model->get_sheet_payments($sheet_id);
        foreach ($payments as $payment)
        {
            $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_FI_CLOSE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'] - 20, $payment['TF_STATUS_ID']);
        }
        $this->db->trans_complete();
        
        redirect("fi/vcbsheet/$sheet_id");
    }
    
    /**
     * ACTION - Finance re-open a closed sheet to add/eject Payments
     * @param int $sheet_id is coming from a HTML form
     */
    public function reopen()
    {
        $this->auth->checkIfOperationIsAllowed('fi_reopen');
        $this->load->model('sheets_model');
        $this->load->model('vcbsheets_model');
        $this->load->model('payments_history_model');
        
        $sheet_id = $this->input->post('sheet_id');
        $sheet = $this->sheets_model->get($sheet_id);
        if ($sheet['SHEET_STATUS'] != SHEET_STATUS_TRANSFERRING)
        {
            show_error(lang('fi_error_another_user'));
        }
        $payments = $this->payments_model->get_sheet_payments($sheet_id);
        
        $this->db->trans_start();
        $this->payments_model->transferring_to_sheet($sheet_id);
        $this->sheets_model->set_status($sheet_id, SHEET_STATUS_SHEET);
        $this->vcbsheets_model->delete_VCBSheets($sheet_id);
        foreach ($payments as $payment)
        {
            $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_FI_OPEN, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $payment['TF_STATUS_ID'] - 20);
        }
        $this->db->trans_complete();
        
        redirect("fi/sheet/$sheet_id");
    }
    
    /**
     * ACTION - Finance upload a Vietcombank VNBT Sheet for a closed sheet to confirm the sheet is transferred
     * Upload File and Transfer Date are coming from a HTML form
     * @param int $sheet_id is coming from a HTML form
     */
    public function upload()
    {
        $this->auth->checkIfOperationIsAllowed('fi_upload');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        $this->form_validation->set_rules('sheet_type', 'sheet_type', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            redirect('fi');
        }

        $this->lang->load('fi_vcbsheet');
        $sheet_id = $this->input->post('sheet_id');
        $sheet_type = $this->input->post('sheet_type');
        
        $this->load->model('sheets_model');
        $sheet = $this->sheets_model->get($sheet_id);
        if ($sheet['SHEET_STATUS'] != SHEET_STATUS_TRANSFERRING)
        {
            show_error(lang('fi_error_another_user'));
        }
        
        $reader = IOFactory::createReader('Xls');
        $spreadsheet = $reader->load($_FILES['vietcombank']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        if ($sheetData[1]['A'] != VCB_VNBT['A1'])
        {
            $this->session->set_flashdata('msg_danger', sprintf(lang('fi_upload_flash_a1'), VCB_VNBT['A1']));
            redirect("fi/vcbsheet/$sheet_id");
        }
        foreach (VCB_VNBT['A10:N10'] as $col => $val)
        {
            if ($sheetData[10][$col] != $val)
            {
                $this->session->set_flashdata('msg_danger', sprintf(lang('fi_upload_flash_col10'), $col, $val));
                redirect("fi/vcbsheet/$sheet_id");
            }
        }
        $vnbt_sheet = array_slice($sheetData, 10, null, true);
        $this->load->model('vcbsheets_model');
        $result = $this->vcbsheets_model->compare_vnbtsheet($sheet_id, $vnbt_sheet);
        if (empty($result))
        {
            $this->session->set_flashdata('msg_danger', lang('fi_upload_flash_fail_count'));
            redirect("fi/vcbsheet/$sheet_id");
        }
        if ($result['error'])
        {
            $this->session->set_flashdata('msg_danger', sprintf(lang('fi_upload_flash_fail'), $result['data'][0], $result['data'][1]));
            redirect("fi/vcbsheet/$sheet_id");
        }
        $vcb_seq = $result['data'];
        
        $this->load->model('transfers_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('payments_history_model');
        $this->load->model('claim_fund_model');
        $this->load->model('hbs_model');
        $this->load->model('vnbt_model');
        
        $this->db->trans_start();
        foreach ($vcb_seq as $paym_id => $vcb)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            $this->payments_model->transferring_to_transferred($paym_id, $vcb['TF_DATE'], $vcb['VCB_SEQ']);
            $this->transfers_model->transferred($payment, $vcb['TF_DATE'], $vcb['VCB_SEQ']);
            if ($payment['YN_CLBO'] == 'Y')
            {
                if ($payment['TF_STATUS_ID'] == TF_STATUS_TRANSFERRING_DLVN_CANCEL)
                {
                    $this->claim_bordereaux_model->add($payment, $vcb['TF_DATE'], - $payment['TF_AMT'] - $payment['DEDUCT_AMT'] - $payment['DISC_AMT'], CLBO_TYPE_PAYMENT);
                }
                else
                {
                    $this->claim_bordereaux_model->add($payment, $vcb['TF_DATE'], $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'], CLBO_TYPE_PAYMENT);
                }
            }
            $this->claim_fund_model->pay_claim($payment, $vcb['TF_DATE'], $vcb['VCB_SEQ']);
            $this->hbs_model->transferred($payment, $vcb['TF_DATE'], $vcb['VCB_SEQ']);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $payment['TF_STATUS_ID'] + 20);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD, HIST_FIELD_TF_DATE, $payment['TF_DATE'], $vcb['TF_DATE']);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD, HIST_FIELD_VCB_SEQ, $payment['VCB_SEQ'], $vcb['VCB_SEQ']);
        }
        $this->sheets_model->set_status($sheet_id, SHEET_STATUS_TRANSFERRED);
        $vnbt_header = array_slice($sheetData, 1, 8, true);
        $this->vnbt_model->set_header($sheet_id, $vnbt_header);
        $this->vnbt_model->set_body($sheet_id, $vnbt_sheet);
        $this->db->trans_complete();
        
        if ($sheet_type == SHEET_TYPE_CLAIMANT)
        {
            redirect('fi/sheets');
        }
        if ($sheet_type == SHEET_TYPE_PARTNER)
        {
            redirect('fi/partner/sheets');
        }
    }
    
    /**
     * ACTION - Finance confirm a payment is paid by inputting Transfer Date and/or VCB SEQ
     * @param int $paym_id is coming from a HTML form
     */
    public function pay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_pay');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/transferring');
        }
        
        $this->load->model('transfers_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('claim_fund_model');
        $this->load->model('payments_history_model');
        $this->load->model('hbs_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $tf_date = DateTime::createFromFormat('d/m/Y', $this->input->post("tf_date_$paym_id"))->format('Y-m-d');
            $vcb_seq = $this->input->post("vcb_seq_$paym_id");
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_TRANSFERRING)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/transferring');
            }
            $this->payments_model->transferring_to_transferred($paym_id, $tf_date, $vcb_seq);
            $this->transfers_model->transferred($payment, $tf_date, $vcb_seq);
            if ($payment['YN_CLBO'] == 'Y')
            {
                $this->claim_bordereaux_model->add($payment, $tf_date, $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'], CLBO_TYPE_PAYMENT);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PAY, HIST_FIELD_YN_CLBO, $payment['YN_CLBO'], 'N');
            }
            $this->claim_fund_model->pay_claim($payment, $tf_date, $vcb_seq);
            $this->hbs_model->transferred($payment, $tf_date, $vcb_seq);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PAY, HIST_FIELD_TF_STATUS, TF_STATUS_TRANSFERRING, TF_STATUS_TRANSFERRED);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PAY, HIST_FIELD_TF_DATE, $payment['TF_DATE'], $tf_date);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PAY, HIST_FIELD_VCB_SEQ, $payment['VCB_SEQ'], $vcb_seq);
        }
        $this->db->trans_complete();
        
        redirect('fi/transferring');
    }
    
    /**
     * ACTION - Revert of Pay Action because of user's mistaken action
     * @param int $paym_id is coming from HTML form
     */
    public function unpay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_unpay');
        
        $this->load->model('transfers_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('claim_fund_model');
        $this->load->model('payments_history_model');
        $this->load->model('hbs_model');
        
        $paym_id = $this->input->post('paym_id');
        $prev_tf_date = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_TF_DATE);
        $prev_status = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_TF_STATUS);
        $prev_vcb_seq = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_VCB_SEQ);
        $prev_yn_clbo = $this->payments_history_model->get_prev_field($paym_id, HIST_FIELD_YN_CLBO);
        if ($prev_status != TF_STATUS_TRANSFERRING OR $prev_tf_date === false)
        {
            redirect("payments/$paym_id");
        }
        $payment = $this->payments_model->get_payment($paym_id);
        
        $this->db->trans_start();
        $this->payments_model->transferred_to_transferring($paym_id, $prev_tf_date, $prev_vcb_seq, $prev_yn_clbo);
        $this->transfers_model->cancel($payment);
        if ($prev_yn_clbo == 'Y')
        {
            $this->claim_bordereaux_model->remove($paym_id, $payment['TF_DATE'], $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT']);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UNPAY, HIST_FIELD_YN_CLBO, $payment['YN_CLBO'], $prev_yn_clbo);
        }
        $this->claim_fund_model->unpay_claim($payment);
        $this->hbs_model->cancel($payment);
        $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UNPAY, HIST_FIELD_TF_STATUS, TF_STATUS_TRANSFERRED, TF_STATUS_TRANSFERRING);
        $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UNPAY, HIST_FIELD_TF_DATE, $payment['TF_DATE'], $prev_tf_date);
        $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UNPAY, HIST_FIELD_VCB_SEQ, $payment['VCB_SEQ'], $prev_vcb_seq);
        $this->db->trans_complete();
        
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Finance move Payment from RETURNED TO CLAIM to DLVN CANCEL/DLVN PAYPREM
     * @param int $to_status_id TF_STATUS_DLVN_CANCEL or TF_STATUS_DLVN_PAYPREM
     * Data are coming from HTML form
     */
    public function partner_request($to_status_id)
    {
        $this->auth->checkIfOperationIsAllowed('fi_partner_request');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/returned_claim');
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('sheets_model');
        
        if ($to_status_id == TF_STATUS_DLVN_CANCEL)
        {
            $to_sheet_status_id = TF_STATUS_SHEET_DLVN_CANCEL;
        }
        else
        {
            $to_sheet_status_id = TF_STATUS_SHEET_DLVN_PAYPREM;
        }
        $sheet_id = $this->input->post('sheet_id');
        if ($sheet_id == 'default')
        {
            $default_sheet = $this->sheets_model->default_sheet();
            $sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_PARTNER);
        }
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_RETURNED_TO_CLAIM)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/returned_claim');
            }
            if (empty($sheet_id))
            {
                $this->payments_model->set_transfer_status($paym_id, $to_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PARTNER_REQUEST, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, $to_status_id);
            }
            else
            {
                $this->payments_model->to_sheet($paym_id, $sheet_id, $to_sheet_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PARTNER_REQUEST, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, $to_sheet_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PARTNER_REQUEST, HIST_FIELD_SHEET_ID, '', $sheet_id);
            }
        }
        $this->db->trans_complete();
        
        redirect('fi/returned_claim');
    }
    
    /**
     * ACTION - Finance move Payment from RETURNED TO CLAIM to DLVN PAYPREM
     * Will be removed in next phrase (Claim will do the action)
     * @param int $paym_id
     */
    public function dlvn_payprem($paym_id)
    {
        $this->auth->checkIfOperationIsAllowed('fi_dlvn_payprem');
        
        $this->db->trans_start();
        
        $this->payments_model->set_transfer_status($paym_id, TF_STATUS_DLVN_PAYPREM);
        
        $this->load->model('payments_history_model');
        $this->payments_history_model->add($paym_id, HIST_TYPE_FI_DLVN_PAYPREM, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, TF_STATUS_DLVN_PAYPREM);
        
        $this->db->trans_complete();

        redirect('fi/returned_claim');
    }
    
    /**
     * ACTION - Update Bank Info of Partner
     * Data are coming from a HTML form
     */
    public function update_bank_info()
    {
        $this->auth->checkIfOperationIsAllowed('fi_update_bank_info');
        
        $post = $this->input->post();
        
        $this->db->trans_start();
        
        $this->load->model('partner_model');
        $this->partner_model->set_bank_info($post['acct_name'], $post['acct_no'], $post['bank_name'], $post['bank_branch'], $post['bank_city']);
        
        $this->db->trans_complete();

        redirect('fi/partner');
    }
    
    /**
     * ACTION - Add Balance to Claim Fund
     * Data are coming from a HTML form
     */
    public function replenish()
    {
        $this->auth->checkIfOperationIsAllowed('fi_replenish');
        $this->load->model('claim_fund_model');
        
        $this->db->trans_start();
        $this->claim_fund_model->replenishment(DateTime::createFromFormat('d/m/Y', $this->input->post('replen_date'))->format('Y-m-d'), str_replace(',', '', $this->input->post('replen_amt')));
        $this->db->trans_complete();

        redirect('fi');
    }
    
    /**
     * ACTION - Repay Payments in Returned To Claim Repay page
     * Data are coming from a HTML form
     */
    public function repay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_repay');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/returned_claim');
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('sheets_model');
        $field = [];
        $post = $this->input->post();
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        if (empty($post['sheet_id']))
        {
            $default_sheet = $this->sheets_model->default_sheet();
            $post['sheet_id'] = $this->sheets_model->create($default_sheet, SHEET_TYPE_CLAIMANT);
        }
        foreach ($paym_ids as $paym_id)
        {
            $data = [];
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_RETURNED_TO_CLAIM)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/returned_claim');
            }
            $data['PAYMENT_METHOD'] = $post["payment_method_$paym_id"];
            if ($post["payment_method_$paym_id"] == 'TT')
            {
                $this->form_validation->set_rules("acct_name_$paym_id", 'acct_name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules("acct_no_$paym_id", 'acct_no', 'trim|required|max_length[30]');
                
                $field[HIST_FIELD_ACCT_NAME] = 'acct_name';
                $field[HIST_FIELD_ACCT_NO] = 'acct_no';
                
                $data['ACCT_NAME'] = $post["acct_name_$paym_id"];
                $data['ACCT_NO'] = $post["acct_no_$paym_id"];
            }
            if ($post["payment_method_$paym_id"] == 'TT' OR $post["payment_method_$paym_id"] == 'CH')
            {
                $this->form_validation->set_rules("bank_name_$paym_id", 'bank_name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules("bank_branch_$paym_id", 'bank_branch', 'trim|required|max_length[100]');
                $this->form_validation->set_rules("bank_city_$paym_id", 'bank_city', 'trim|required|max_length[100]');
                
                $field[HIST_FIELD_BANK_NAME] = 'bank_name';
                $field[HIST_FIELD_BANK_BRANCH] = 'bank_branch';
                $field[HIST_FIELD_BANK_CITY] = 'bank_city';
                
                $data['BANK_NAME'] = $post["bank_name_$paym_id"];
                $data['BANK_BRANCH'] = $post["bank_branch_$paym_id"];
                $data['BANK_CITY'] = $post["bank_city_$paym_id"];
            }
            if ($post["payment_method_$paym_id"] == 'CH' OR $post["payment_method_$paym_id"] == 'CQ')
            {
                $this->form_validation->set_rules("beneficiary_name_$paym_id", 'beneficiary_name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules("pp_no_$paym_id", 'pp_no', 'trim|required|max_length[20]');
                $this->form_validation->set_rules("pp_date_$paym_id", 'pp_date', 'trim|required|max_length[10]');
                $this->form_validation->set_rules("pp_place_$paym_id", 'pp_place', 'trim|required|max_length[100]');
                
                $field[HIST_FIELD_BENEFICIARY_NAME] = 'beneficiary_name';
                $field[HIST_FIELD_PP_NO] = 'pp_no';
                $field[HIST_FIELD_PP_DATE] = 'pp_date';
                $field[HIST_FIELD_PP_PLACE] = 'pp_place';
                
                $data['BENEFICIARY_NAME'] = $post["beneficiary_name_$paym_id"];
                $data['PP_NO'] = $post["pp_no_$paym_id"];
                $post["pp_date_$paym_id"] = DateTime::createFromFormat('d/m/Y', $post["pp_date_$paym_id"])->format('Y-m-d');
                $data['PP_DATE'] = $post["pp_date_$paym_id"];
                $data['PP_PLACE'] = $post["pp_place_$paym_id"];
            }
            if ($this->form_validation->run() == FALSE)
            {
                $this->session->set_flashdata('msg_danger', validation_errors());
                redirect('fi/returned_claim');
            }
            
            $this->payments_model->set_payment_info($paym_id, $data);
            foreach ($field as $hist => $val)
            {
                $col = strtoupper($val);
                if ($payment[$col] != $post[$val . '_' . $paym_id])
                {
                   $this->payments_history_model->add($paym_id, HIST_TYPE_FI_REPAY, $hist, $payment[$col], $post[$val . '_' . $paym_id]); 
                }
            }
            $to_status = $this->_to_status($payment['TF_AMT'], $post["payment_method_$paym_id"], $post["bank_name_$paym_id"], $post["acct_name_$paym_id"], $post["acct_no_$paym_id"]);
            if ($to_status == TF_STATUS_SHEET)
            {
                $this->payments_model->to_sheet($paym_id, $post['sheet_id'], $to_status);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_REPAY, HIST_FIELD_SHEET_ID, $payment['SHEET_ID'], $post['sheet_id']);
            }
            elseif ($to_status == TF_STATUS_TRANSFERRING)
            {
                $this->payments_model->to_transferring($paym_id);
            }
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_REPAY, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, $to_status);
        }
        $this->db->trans_complete();
        
        redirect('fi/returned_claim');
    }
    
    /**
     * ACTION - Set Beginning Balance for first Claim Fund monthly report
     * Data are coming from a HTML form
     */
    public function set_bebal()
    {
        $this->auth->checkIfOperationIsAllowed('fi_set_bebal');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('time', 'time', 'required|exact_length[7]|is_unique[claim_fund_fixed.CLFF_MON_YEAR]');
        $this->form_validation->set_rules('prev_time', 'prev_time', 'required|exact_length[7]|is_unique[claim_fund_fixed.CLFF_MON_YEAR]');
        $this->form_validation->set_rules('bebal_repl', 'bebal_repl', 'required');
        $this->form_validation->set_rules('bebal_refund', 'bebal_refund', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/claim_fund');
        }
        
        $post = $this->input->post();
        $this->load->model('claim_fund_fixed_model');
        $this->claim_fund_fixed_model->set_ending_balance($post['prev_time'], str_replace(',', '', $post['bebal_repl']), str_replace(',', '', $post['bebal_refund']));
        
        $this->session->set_flashdata('time', $post['time']);
        redirect('fi/claim_fund');
    }
    
    /**
     * ACTION - Set Ending Balance for a Claim Fund monthly report
     * Data are coming from a HTML form
     */
    public function set_enbal()
    {
        $this->auth->checkIfOperationIsAllowed('fi_set_enbal');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('time', 'time', 'required|exact_length[7]|is_unique[claim_fund_fixed.CLFF_MON_YEAR]');
        $this->form_validation->set_rules('enbal_repl', 'enbal_repl', 'required|integer');
        $this->form_validation->set_rules('enbal_refund', 'enbal_refund', 'required|integer');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/claim_fund');
        }
        
        $post = $this->input->post();
        $this->load->model('claim_fund_fixed_model');
        $this->claim_fund_fixed_model->set_ending_balance($post['time'], $post['enbal_repl'], $post['enbal_refund']);
        
        $this->session->set_flashdata('time', $post['time']);
        redirect('fi/claim_fund');
    }
    
    /**
     * ACTION - Close Case - adjust claim payment action and refund action in Claim Fund
     * Data are coming from a HTML form
     */
    public function do_not_pay()
    {
        $this->auth->checkIfOperationIsAllowed('fi_do_not_pay');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_idss', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/returned_claim');
        }
        
        $this->load->model('transfers_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('claim_fund_model');
        $this->load->model('payments_history_model');
            
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_STATUS_ID'] != TF_STATUS_RETURNED_TO_CLAIM)
            {
                $this->session->set_flashdata('msg_danger', lang('fi_error_another_user'));
                redirect('fi/returned_claim');
            }
            $this->payments_model->set_transfer_status($paym_id, TF_STATUS_DLVN_CLOSED);
            $this->transfers_model->closed($paym_id, $payment['TF_NO']);
            $this->claim_bordereaux_model->adjust_payment($paym_id);
            $this->claim_fund_model->adjust_closed($paym_id);
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_DO_NOT_PAY, HIST_FIELD_TF_STATUS, TF_STATUS_RETURNED_TO_CLAIM, TF_STATUS_DLVN_CLOSED);
        }
        $this->db->trans_complete();
        
        redirect('fi/returned_claim');
    }
    
    /**
     * ACTION - After a Payment is paid to DLVN, client wants to receive money so we must decrease money which had transferred to DLVN then progress to send money to client
     * Data are coming from a HTML form
     */
    public function pay_repaid()
    {
        $this->auth->checkIfOperationIsAllowed('fi_pay_repaid');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_id', 'paym_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('fi/transferred');
        }
        $paym_id = $this->input->post('paym_id');
        $payment = $this->payments_model->get_payment($paym_id);
        $this->load->model('claim_fund_model');
        $this->load->model('payments_history_model');
        
        $this->db->trans_start();
        $this->payments_model->set_transfer_status($paym_id, TF_STATUS_APPROVED);
        $this->claim_fund_model->adjust_repaid_refund($paym_id);
        $this->payments_history_model->add($paym_id, HIST_TYPE_FI_PAY_REPAID, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_APPROVED);
        $this->db->trans_complete();
        
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - After a Payment is transferred, user can decrease Transfer Amount then set Debt to Client or not
     * Data are coming from a HTML form
     */
    public function decrease_transferred()
    {
        $this->auth->checkIfOperationIsAllowed('fi_decrease_transferred');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_id', 'paym_id', 'required');
        $this->form_validation->set_rules('tran_id', 'tran_id', 'required');
        $this->form_validation->set_rules('cl_no', 'cl_no', 'required');
        $this->form_validation->set_rules('tf_amt', 'tf_amt', 'required');
        $this->form_validation->set_rules('decr_amt', 'decr_amt', 'required');
        $this->form_validation->set_rules('prev_decr_amt', 'prev_decr_amt', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('payments/' . $post['paym_id']);
        }
                
        $paym_id = $this->input->post('paym_id');
        $tran_id = $this->input->post('tran_id');
        $cl_no = $this->input->post('cl_no');
        $tf_amt = str_replace(',', '', $this->input->post('tf_amt'));
        $decr_amt = str_replace(',', '', $this->input->post('decr_amt'));
        $prev_decr_amt = str_replace(',', '', $this->input->post('prev_decr_amt'));
        
        if ($decr_amt == $prev_decr_amt)
        {
            redirect("payments/$paym_id");
        }
        if ($decr_amt > $tf_amt)
        {
            $this->session->set_flashdata('msg_danger', lang('fi_decrease_transferred_error'));
            redirect("payments/$paym_id");
        }
        
        $this->load->model('debt_model');
        $this->load->model('claim_fund_model');
        $this->load->model('claim_bordereaux_model');
        
        $payment = $this->payments_model->get_payment($paym_id);
        
        $this->db->trans_start();
        $diff_decr_amt = $decr_amt - $prev_decr_amt;
        $this->debt_model->set_debt($tran_id, $cl_no, $diff_decr_amt, $payment['MEMB_NAME'], $payment['MEMB_REF_NO']);
        $this->claim_fund_model->adjust_decrease($paym_id, $diff_decr_amt);
        $this->claim_bordereaux_model->adjust_decrease($paym_id, $diff_decr_amt);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Chuyển số tiền ghi nợ cho Khách Hàng thành số tiền mà PCV phải chịu 
     * Data are coming from a HTML form
     */
    public function set_expense()
    {
        $this->auth->checkIfOperationIsAllowed('fi');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('debt_ids', 'debt_ids', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            redirect('404_override');
        }
        
        $this->load->model('debt_model');
        $debt_ids = explode(',', $this->input->post('debt_ids'));
        $this->debt_model->switch_pcv_expense($debt_ids);
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ACTION - Add Client Debit for a client
     * Data are coming from a HTML form
     */
    public function add_client_debit()
    {
        $this->auth->checkIfOperationIsAllowed('fi');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('memb_ref_no', 'memb_ref_no', 'required');
        $this->form_validation->set_rules('memb_name', 'memb_name', 'required');
        $this->form_validation->set_rules('debt_amt', 'debt_amt', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            redirect('404_override');
        }
        
        $memb_ref_no = $this->input->post('memb_ref_no');
        $memb_name = $this->input->post('memb_name');
        $debt_amt = str_replace(',', '', $this->input->post('debt_amt'));
        
        if (empty($debt_amt))
        {
            redirect($_SERVER['HTTP_REFERER']);
        }
        
        $this->load->model('debt_model');
        $this->debt_model->set_debt(null, null, $debt_amt, $memb_name, $memb_ref_no);
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * ACTION - Add Client Paid when Finance receives money
     * Data are coming from a HTML form
     */
    public function add_client_paid()
    {
        $this->auth->checkIfOperationIsAllowed('fi');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('memb_ref_no', 'memb_ref_no', 'required');
        $this->form_validation->set_rules('memb_name', 'memb_name', 'required');
        $this->form_validation->set_rules('paid_amt', 'paid_amt', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            redirect('404_override');
        }
        
        $memb_ref_no = $this->input->post('memb_ref_no');
        $memb_name = $this->input->post('memb_name');
        $debt_cl_no = $this->input->post('debt_cl_no');
        $paid_amt = str_replace(',', '', $this->input->post('paid_amt'));
        
        if (empty($paid_amt))
        {
            redirect($_SERVER['HTTP_REFERER']);
        }
        
        $this->load->model('debt_model');
        $this->debt_model->paid_debt($debt_cl_no, $memb_name, $memb_ref_no, $paid_amt);
        
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * PAGE - CLAIMANT & PARTNER - Page to upload an excel file which contains list of Approved Payments to validate
     */
    public function approved_upload()
    {
        $this->auth->checkIfOperationIsAllowed('fi_approved');
        $twig = getUserContext($this);
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        
        $this->lang->load('columns');
        $this->lang->load('fi_approved_upload');
        $this->load->model('sheets_model');
        
        $post = $this->input->post();
        if ( ! empty($post) && isset($post['btnValidate']))
        {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($_FILES['claim_xlsx']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            $map = [
                'A' => 'MEMB_NAME',
                'B' => 'POCY_REF_NO',
                'C' => 'MEMB_REF_NO',
                'D' => 'CL_NO',
                'E' => 'PRES_AMT',
                'F' => 'APP_AMT',
                'G' => 'TF_AMT',
                'H' => 'DEDUCT_AMT',
                'I' => 'PAYMENT_METHOD',
                'J' => 'MANTIS_ID',
            ];
            $paym_ids = [];
            $transfers = [];
            $deducts = [];
            foreach ($sheetData as $numRow => $row)
            {
                if ($numRow < 2)
                {
                    continue;
                }
                foreach (range('A', 'J') as $char)
                {
                    $row[$char] = trim($row[$char]);
                }
                $payment = $this->payments_model->get_approved_payment_by_cl_no($row['D']);
                if (empty($payment))
                {
                    $sheetData[$numRow]['K'] = lang('fi_approved_upload_error_not_found');
                    continue;
                }
                if (strcasecmp(vn_to_str($payment['MEMB_NAME']), vn_to_str($row['A'])) != 0)
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_memb_name'), $row['A'], $payment['MEMB_NAME']);
                    continue;
                }
                if ($payment['POCY_REF_NO'] != $row['B'])
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_pocy_ref_no'), $row['B'], $payment['POCY_REF_NO']);
                    continue;
                }
                // if ($payment['MEMB_REF_NO'] != $row['C'])
                // {
                //     $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_memb_ref_no'), $row['C'], $payment['MEMB_REF_NO']);
                //     continue;
                // }
                
                if ($payment['APP_AMT'] != $row['F'])
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_app_amt'), $row['F'], $payment['APP_AMT']);
                    continue;
                }
                $row['H'] = intval($row['H']);
                if ($payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'] != $row['G'] + $row['H'])
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_tf_amt'), $row['G'] + $row['H'], $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT']);
                    continue;
                }
                if ($payment['PAYMENT_METHOD'] == 'CH')
                {
                    $payment['PAYMENT_METHOD'] = 'CA';
                }
                if (strcasecmp($payment['PAYMENT_METHOD'], $row['I']) != 0)
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_payment_method'), $row['I'], $payment['PAYMENT_METHOD']);
                    continue;
                }
                if ($payment['MANTIS_ID'] != $row['J'])
                {
                    $sheetData[$numRow]['K'] = sprintf(lang('fi_approved_upload_error_mantis_id'), $row['J'], $payment['MANTIS_ID']);
                    continue;
                }
                if (in_array($payment['PAYM_ID'], $paym_ids))
                {
                    $sheetData[$numRow]['K'] = lang('fi_approved_upload_error_duplicate');
                    continue;
                }
                $paym_ids[] = $payment['PAYM_ID'];
                $transfers[] = $row['G'];
                $deducts[] = $row['H'];
            }
            $twig['sheetData'] = $sheetData;
            $twig['paym_ids'] = implode(',', $paym_ids);
            $twig['transfers'] = implode(',', $transfers);
            $twig['deducts'] = implode(',', $deducts);
            $twig['claimant_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_CLAIMANT);
            $twig['partner_sheets'] = $this->sheets_model->get_sheets(SHEET_STATUS_SHEET, SHEET_TYPE_PARTNER);
        }
        $this->load->view('fi/approved_upload', $twig);
    }
    
    /**
     * ACTION - Read a list of Approved Payments from file then create sheet for them
     * Data are coming from a HTML form
     */
    public function confirm_upload()
    {
        $this->auth->checkIfOperationIsAllowed('fi_approved');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('transfers', 'transfers', 'required');
        $this->form_validation->set_rules('deducts', 'deducts', 'required');
        $this->form_validation->set_rules('claimant_sheet_id', 'claimant_sheet_id', 'required');
        $this->form_validation->set_rules('partner_sheet_id', 'partner_sheet_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('approved_upload');
        }
        
        $this->load->model('payments_history_model');
        $this->load->model('claim_bordereaux_model');
        $this->load->model('transfers_model');
        $this->load->model('claim_fund_model');
        $this->load->model('hbs_model');
        $this->load->model('sheets_model');
        
        $paym_ids = explode(',', $this->input->post('paym_ids'));
        $transfers = explode(',', $this->input->post('transfers'));
        $deducts = explode(',', $this->input->post('deducts'));
        $claimant_sheet_id = $this->input->post('claimant_sheet_id');
        $partner_sheet_id = $this->input->post('partner_sheet_id');
        $sheet_uname = $this->input->post('sheet_uname');
        
        $msg_success = [];
        $this->db->trans_start();
        foreach ($paym_ids as $id => $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if ($payment['TF_AMT'] != $transfers[$id] OR $payment['DEDUCT_AMT'] != $deducts[$id])
            {
                if ($payment['TF_AMT'] != $transfers[$id])
                {
                    $this->payments_model->set_payment_info($paym_id, array(
                        'TF_AMT' => $transfers[$id],
                    ));
                    $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $transfers[$id]);
                }
                if ($payment['DEDUCT_AMT'] != $deducts[$id])
                {
                    $this->payments_model->set_payment_info($paym_id, array(
                        'DEDUCT_AMT' => $deducts[$id],
                    ));
                    $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], $deducts[$id]);
                }
                $payment = $this->payments_model->get_payment($paym_id);
            }
            $to_status_id = $this->_to_status($payment['TF_AMT'], $payment['PAYMENT_METHOD'], $payment['BANK_NAME'], $payment['ACCT_NAME'], $payment['ACCT_NO']);
            if ($to_status_id == TF_STATUS_TRANSFERRED)
            {
                $tf_date = date('Y-m-d');
                $vcb_seq = 'PCV';
                if ($payment['YN_CLBO'] == 'Y')
                {
                    $this->claim_bordereaux_model->add($payment, $tf_date, $payment['TF_AMT'] + $payment['DEDUCT_AMT'], CLBO_TYPE_PAYMENT);
                    $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_YN_CLBO, $payment['YN_CLBO'], 'N');
                }
                $this->payments_model->transferred_full_deduction($paym_id, $tf_date);
                $this->transfers_model->transferred($payment, $tf_date, $vcb_seq);
                $this->claim_fund_model->pay_claim($payment, $tf_date, $vcb_seq);
                $this->hbs_model->transferred($payment, $tf_date, $vcb_seq);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_TF_DATE, $payment['TF_DATE'], $tf_date);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_VCB_SEQ, $payment['VCB_SEQ'], $vcb_seq);
                
                $msg_success[] = $payment['CL_NO'] . ': Transferred';
            }
            elseif ($to_status_id == TF_STATUS_SHEET)
            {
                if (empty($claimant_sheet_id))
                {
                    $default_sheet = $this->sheets_model->default_sheet();
                    $claimant_sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_CLAIMANT, $sheet_uname);
                }
                $this->payments_model->to_sheet($paym_id, $claimant_sheet_id, $to_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_SHEET_ID, $payment['SHEET_ID'], $claimant_sheet_id);
                
                $msg_success[] = $payment['CL_NO'] . ': Move to [' . lang('menu_claimant') . ' > ' . lang('menu_claimant_sheets') . '] "' . $default_sheet . '"';
            }
            elseif ($to_status_id == TF_STATUS_TRANSFERRING)
            {
                $this->payments_model->to_transferring($paym_id);
                
                $msg_success[] = $payment['CL_NO'] . ': Move to [' . lang('menu_claimant') . ' > ' . lang('menu_claimant_payments') . ']';
            }
            elseif ($to_status_id == TF_STATUS_SHEET_PAYPREM)
            {
                if (empty($partner_sheet_id))
                {
                    $default_sheet = $this->sheets_model->default_sheet();
                    $partner_sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_PARTNER, $sheet_uname);
                }
                $this->payments_model->to_sheet($paym_id, $partner_sheet_id, $to_status_id);
                $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_SHEET_ID, $payment['SHEET_ID'], $partner_sheet_id);
                
                $msg_success[] = $payment['CL_NO'] . ': Move to [' . lang('menu_partner') . ' > ' . lang('menu_partner_sheets') . '] "' . $default_sheet . '"';
            }
            else
            {
                show_error(lang('fi_to_status_error'));
            }
            $this->payments_history_model->add($paym_id, HIST_TYPE_FI_UPLOAD_APPROVED, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $to_status_id);
        }
        $this->db->trans_complete();
        
        $this->session->set_flashdata('msg_success', implode('<br>', $msg_success));
        redirect('approved_upload');
    }

    
    /**
     * ACTION - Finance Change  multiple payment out of a sheet
     */
    public function change_sheet()
    {
        $this->auth->checkIfOperationIsAllowed('fi_change_sheet');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $this->form_validation->set_rules('sheet_id', 'sheet_id', 'required');
        $sheet_id = $this->input->post('sheet_id');
        $paym_ids = $this->input->post('paym_ids');
        $sheet_id_old = $this->input->post('sheet_id_old');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("sheet/$sheet_id");
        }
        
        $this->load->model('sheets_model');
        $this->load->model('payments_history_model');
        
        if ($sheet_id == 0)
        {
            
            $default_sheet = $this->sheets_model->default_sheet();
            $sheet_id = $this->sheets_model->create($default_sheet, SHEET_TYPE_CLAIMANT);
        }
        
        $sheet = $this->sheets_model->get($sheet_id_old);
        
        $paym_ids = explode(',', $paym_ids);
        
        $this->db->trans_start();
        foreach ($paym_ids as $paym_id)
        {
            $payment = $this->payments_model->get_payment($paym_id);
            if($payment['TF_STATUS_ID'] == TF_STATUS_SHEET){
                $this->payments_model->to_sheet($payment['PAYM_ID'], $sheet_id, $payment['TF_STATUS_ID']);
                $this->payments_history_model->add($paym_id, HIST_TYPE_CHANGE_SHEET, HIST_FIELD_SHEET_ID, $sheet_id_old , $sheet_id);
            }
        }
        if (empty($this->payments_model->count_sheet_payments($sheet_id_old)))
        {
            $this->sheets_model->delete($sheet_id_old);
            $this->db->trans_complete();
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_CLAIMANT)
            {
                redirect("sheets");
            }
            if ($sheet['SHEET_TYPE'] == SHEET_TYPE_PARTNER)
            {
                redirect("partner/sheets");
            }
        }
        $this->db->trans_complete();
        redirect("sheet/$sheet_id_old");
    }

    // export form of bank 

    public function bank_request_form(){
        $this->load->library('form_validation');
        $this->form_validation->set_rules('paym_ids', 'paym_ids', 'required');
        $paym_ids = explode(",", $this->input->post('paym_ids')) ;
        $twig['payments'] = $this->payments_model->get_payment_by_paym_ids($paym_ids);
        $result = [];
        foreach ($twig['payments'] as $element) {
            if($element['PAYMENT_METHOD'] == "TT"){
                $result[$element['ACCT_NO']][] = $element;
            }else{
                $result['default'][] = $element;
            }
        }
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("fi/transferring");
        }
        $mpdf = new \Mpdf\Mpdf(['tempDir' => FCPATH.('assets/dl/mpdf/')]);
        foreach ($result as $key => $value) {
            if($key == 'default'){
                foreach ($value as $key2 => $value2) {
                    $tw['payment'] = $value2;
                    $tw['payment']['TF_AMT_WORD']= number_to_words($value2['TF_AMT']);
                    if($value2['PAYMENT_METHOD'] == 'CH'){
                        $ppDay = carbon::parse($value2['PP_DATE'])->format('d-m-Y');
                        
                        $tw['payment']['DESC'] = "Thanh toán bồi thường hộ FUBON cho số {$value2['CL_NO']}  (Hợp đồng BH số {$value2['POCY_REF_NO']}), Nhận tiền mặt tại {$value2['BANK_NAME']}, {$value2['BANK_CITY']}, {$value2['BANK_BRANCH']} bằng CMND số {$ppDay} của {$value2['BENEFICIARY_NAME']}, ngày cấp: {$value2['PP_DATE']}, nơi cấp: {$value2['PP_PLACE']}";
                    }else{
                        $tw['payment']['DESC'] = "Thanh toán bồi thường hộ FUBON cho số {$value2['CL_NO']}  (Hợp đồng BH số {$value2['POCY_REF_NO']}), Claim payment for Fubon";
                    }
                    $html = $this->load->view('pdf/bank_request_form', $tw, true);
                    $mpdf->AddPage();
                    $mpdf->WriteHTML($html);
                }
            }else{
                $tw['payment'] = $value[0];
                $tw['payment']['TF_AMT'] = array_sum(array_column($value, 'TF_AMT')); 
                $tw['payment']['TF_AMT_WORD']= number_to_words($tw['payment']['TF_AMT']);
                $all_cl_no = truncateString(implode(", ",array_column($value, 'CL_NO')) , 70);
                $tw['payment']['DESC'] = "Thanh toán bồi thường hộ FUBON cho số {$all_cl_no}, Claim payment for Fubon";
                $html = $this->load->view('pdf/bank_request_form', $tw, true);
                $mpdf->AddPage();
                $mpdf->WriteHTML($html);
            }
        }
        $mpdf->Output();
    }
}
