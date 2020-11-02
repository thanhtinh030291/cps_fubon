<?php
/**
 * This controller serves the list of custom reports and the system reports.
 * @since         0.1.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class Reports extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('reports_model');
        $this->lang->load('menu');
        $this->lang->load('reports');
    }
    
	/**
	 * Let users search any payments
	 */
	public function index()
	{
        $twig = getUserContext($this);
        
        $this->load->model('payments_model');
        $this->load->model('transfer_status_model');
        $this->load->model('providers_model');
        
        $twig['transfer_status'] = $this->transfer_status_model->get_transfer_status();
        $twig['providers'] = $this->providers_model->get_providers();
        $twig['post'] = $this->input->post();
        
        if ( ! empty($twig['post']) && isset($twig['post']['btnSearchPayment']))
        {
            $condition = [];
            if ( ! empty($twig['post']['cl_no']))
            {
                $condition['CL_NO'] = $twig['post']['cl_no'];
            }
            if ( ! empty($twig['post']['tf_status_id']))
            {
                $condition['TF_STATUS_ID'] = $twig['post']['tf_status_id'];
            }
            if ( ! empty($twig['post']['prov_name']))
            {
                $condition['PROV_NAME'] = $twig['post']['prov_name'];
                $condition['CL_TYPE'] = 'P';
            }
            if ( ! empty($twig['post']['rcv_date']))
            {
                $condition['RCV_DATE'] = $twig['post']['rcv_date'];
            }
            if ( ! empty($twig['post']['app_date_from']))
            {
                if ( ! empty($twig['post']['app_date_to']))
                {
                    $condition['APP_DATE >='] = $twig['post']['app_date_from'];
                    $condition['APP_DATE <='] = $twig['post']['app_date_to'];
                }
                else
                {
                    $condition['APP_DATE'] = $twig['post']['app_date_from'];
                }
            }
            if ( ! empty($twig['post']['req_date_from']))
            {
                if ( ! empty($twig['post']['req_date_to']))
                {
                    $condition['REQ_DATE >='] = $twig['post']['req_date_from'];
                    $condition['REQ_DATE <='] = $twig['post']['req_date_to'];
                }
                else
                {
                    $condition['REQ_DATE'] = $twig['post']['req_date_from'];
                }
            }
            if ( ! empty($condition))
            {
                $twig['searched_payments'] = $this->payments_model->search_payments($condition);
            }
        }
        $this->load->view('reports/search', $twig);
    }
    
    /**
     * download list of payment which selected Payment Method
     */
    public function fi_list_payments()
    {
        $this->auth->checkIfOperationIsAllowed('report_list_payments');
        
        $this->load->model('payments_model');
        $payments = $this->payments_model->get_payments(TFST_APPROVED, null, $this->input->post('btnListPayments'));
        if (empty($payments))
        {
            $this->session->set_flashdata('msg_danger', 'There is no record of the Payment Method!');
            redirect('finance');
        }
        $this->reports_model->export_list_payments($payments);
    }
    
    /**
     * download list of unpaid-payments: Claim Manager has approved but Finance has not tranferred
     */
    public function unpaid_payments()
    {
        $this->auth->checkIfOperationIsAllowed('report_list_unpaid_payments');
        $twig = getUserContext($this);
        
        $this->load->model('payments_model');
        $approved_payments = $this->payments_model->get_payments(TFST_APPROVED);
        $sheet_payments = $this->payments_model->get_payments(TFST_BANG_KE);
        $transferring_payments = $this->payments_model->get_payments(TFST_TRANSFERRING);
        $returned_payments = $this->payments_model->get_payments(TFST_RETURNED_TO_CLAIM);
        
        $payments = array_merge($approved_payments, $sheet_payments, $transferring_payments, $returned_payments);
        if (empty($payments))
        {
            $this->session->set_flashdata('msg_danger', lang('unpaid_payments_danger'));
            if ($twig['is_finance'])
            {
                redirect('finance');
            }
            if ($twig['is_claim_manager'])
            {
                redirect('manager');
            }
        }
        $this->reports_model->export_list_payments($payments, 'unpaid');
    }
}
