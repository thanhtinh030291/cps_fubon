<?php
/**
 * This controller displays the business logic and manages the persistence of sheets
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Sheets extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sheets_model');
        setUserContext($this);
        $this->lang->load('menu');
        $this->lang->load('column');
    }
    
	/**
	 * Page for Finance to process Bang Ke, which will upload to Vietcombank's website
	 */
	public function index()
	{
        $this->auth->checkIfOperationIsAllowed('sheet');
        $this->lang->load('fi_sheet');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        
        $twig['sheet'] = $this->sheets_model->get_sheets(TFST_BANG_KE);
        $this->load->view('finance/sheet', $twig);
	}
    
    /**
     * Add a payment from Current Sheet to selected Sheet
     * @param int $parq_id
     */
    public function add($parq_id)
    {
        $this->auth->checkIfOperationIsAllowed('add');
        $twig = getUserContext($this);
        
        $this->db->trans_start();
        list($sheet_date, $sheet_no) = explode('_', $this->input->post('sheet_name'));
        if ($this->sheets_model->set_name($parq_id, $sheet_date, $sheet_no))
        {
            $this->db->trans_complete();
            $this->session->set_flashdata('msg_success', 'Add the Payment Successful!');
        }
        else
        {
            $this->session->set_flashdata('msg_danger', 'Could not add the Payment! Please contact IT to check! Thank you.');
        }
        $this->session->set_flashdata('sheet_name', $this->input->post('sheet_name'));
        redirect('grouped_sheets');
    }
    
    /**
     * Confirm transfer for Transferring Sheet by uploading Vietcombank Returned Sheet and selecting Transfer Date
     * @param int $bk_name
     */
    public function claim_fund($sheet_name)
    {
        $this->auth->checkIfOperationIsAllowed('claim_fund');
        
        $config['upload_path'] = './assets/uploads/';
        $config['allowed_types'] = 'xls';
        $this->load->library('upload', $config);
        if ( ! $this->upload->do_upload('vietcombank'))
        {
            $this->session->set_flashdata('msg_danger', 'Error: ' . $this->upload->display_errors());
            redirect('transferring');
        }
        $file = $this->upload->data();
        list($sheet_date, $sheet_no) = explode('_', $sheet_name);
        $bangke = $this->sheets_model->get_sheets(TFST_TRANSFERRING, $sheet_date, $sheet_no);
        $this->db->trans_start();
        if ($this->sheets_model->set_vcb_seq($bangke, $file['full_path']))
        {
            $this->sheets_model->set_transfer_date($bangke, $this->input->post('tf_date'));
            $this->sheets_model->upload_mantis($bangke);
            $this->db->trans_complete();
            $this->session->set_flashdata('msg_success', "Add sheet $sheet_name to Claim Fund Successful");
        }
        else
        {
            $this->session->set_flashdata('msg_danger', "Could not add sheet $sheet_name to Claim Fund! Please check your uploaded file.");
        }
        redirect('transferring');
    }
    
    /**
	 * Close current sheet and create new empty sheet
	 */
	public function close()
	{
        $this->auth->checkIfOperationIsAllowed('close_sheet');
        $this->lang->load('fi_sheet');
        
        $this->sheets_model->close_sheet();
        $this->session->set_flashdata('msg_success', lang('fi_sheet_close_current_sheet_success'));
        redirect('sheets');
    }
    
    /**
	 * Download selected old sheet and change Transfer Status of each Payment in the sheet to Transferring
     * @param int $bk_name
	 */
	public function download($bk_name)
	{
        $this->auth->checkIfOperationIsAllowed('download_sheet');
        $twig = getUserContext($this);
        
        $this->db->trans_start();
        $sheet_data = $this->sheets_model->set_order_and_generate_sheet_data($bk_name);
        if (empty($sheet_data))
        {
            $this->session->set_flashdata('msg_danger', "Could not download Bang Ke $bk_name! Please contact IT to check! Thank you.");
        }
        else
        {
            $this->db->trans_complete();
            $this->load->model('reports_model');
            $out_name = $this->reports_model->export_vcb_sheet($bk_name, $sheet_data);
            $this->session->set_flashdata('msg_success', "Bang Ke $bk_name is generated successful! Please click the button below to get the sheet.");
            $this->session->set_flashdata('sheet_link', $out_name);
            $this->session->set_flashdata('sheet_name', basename($out_name));
        }
        redirect('grouped_sheets');
    }
    
    /**
     * Eject a payment from old Bang Ke to new Bang Ke
     * @param int $parq_id
     */
    public function eject($parq_id)
    {
        $this->auth->checkIfOperationIsAllowed('eject');
        $twig = getUserContext($this);
        
        $this->db->trans_start();
        if ($this->sheets_model->set_name($parq_id))
        {
            $this->db->trans_complete();
            $this->session->set_flashdata('msg_success', 'Eject the Payment Successful!');
        }
        else
        {
            $this->session->set_flashdata('msg_danger', 'Could not eject the Payment! Please contact IT to check! Thank you.');
        }
        $this->session->set_flashdata('sheet_name', $this->input->post('sheet_name'));
        redirect('grouped_sheets');
    }
    
    /**
	 * Page for Finance to process List of Old Sheets, which will upload to Vietcombank's website
	 */
	public function grouped_sheets()
	{
        $this->auth->checkIfOperationIsAllowed('grouped_sheets');
        $this->lang->load('fi_gsheet');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $twig['sheet_link'] = $this->session->flashdata('sheet_link');
        $twig['sheet_name'] = $this->session->flashdata('sheet_name');
        
        $twig['sheets'] = $this->sheets_model->get_list_of_sheets(TFST_BANG_KE);
        $twig['current_sheet'] = $this->sheets_model->get_sheets(TFST_BANG_KE);
        
        if ($this->session->flashdata('sheet_name'))
        {
            $twig['post']['sheet_name'] = $this->session->flashdata('sheet_name');
            $twig['post']['btnSearch'] = 'Y';
        }
        else
        {
            $twig['post'] = $this->input->post();
        }
        if ( ! empty($twig['post']))
        {
            if (isset($twig['post']['btnSearch']))
            {
                if ( ! empty($twig['post']['sheet_name']))
                {
                    list($sheet_date, $sheet_no) = explode('_', $twig['post']['sheet_name']);
                    $twig['searched_sheet'] = $this->sheets_model->get_sheets(TFST_BANG_KE, $sheet_date, $sheet_no);
                }
            }
        }
            
        $this->load->model('providers_model');
        $twig['hold_providers'] = $this->providers_model->get_hold_providers();
        
        $this->load->view('finance/grouped_sheets', $twig);
	}
    
    /**
	 * PAGE - Page for Finance to process Transferring Sheets, which will combine with Vietcombank's Returned Sheet to generate Claim Fund
     * Finance must input Transfer Date in this page
	 */
	public function transferring()
    {
        $this->auth->checkIfOperationIsAllowed('transferring');
        $this->lang->load('transferring');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        
        $twig['sheets'] = $this->sheets_model->get_list_of_sheets(TFST_TRANSFERRING);
        $twig['payments'] = $this->sheets_model->get_transferring_payments();
        
        $twig['post'] = $this->input->post();
        if ( ! empty($twig['post']))
        {
            if (isset($twig['post']['btnSearch']))
            {
                if ( ! empty($twig['post']['sheet_name']))
                {
                    list($sheet_date, $sheet_no) = explode('_', $twig['post']['sheet_name']);
                    $twig['searched_sheet'] = $this->sheets_model->get_sheets(TFST_TRANSFERRING, $sheet_date, $sheet_no);
                    $folder = substr(str_replace(['_', '-'], '', $twig['post']['sheet_name']), 0, 6);
                    $twig['searched_sheet_link'] = $this->config->item('base_url') . 'assets/dl/bk/' . $folder . '/bangke_DLVN_' . $twig['post']['sheet_name'] . '.xlsx';
                    $twig['searched_sheet_name'] = 'bangke_DLVN_' . $twig['post']['sheet_name'] . '.xlsx';
                }
            }
        }
        $this->load->view('finance/transferring', $twig);
    }
}
