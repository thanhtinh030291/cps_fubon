<?php
/**
 * This controller serves all the actions performed by finance
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Foreigner extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('foreigner_model');
        $this->lang->load('foreigner');
        $this->lang->load('menu');
        $this->lang->load('columns');
    }
    
    /**
     * PAGE - Show all Foreigner Bank Account Information
     */
    public function index()
    {
        $this->auth->checkIfOperationIsAllowed('foreigner');
        $twig = getUserContext($this);
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        
        $twig['foreigners'] = $this->foreigner_model->get_foreigners();
        
        $this->load->view('fi/foreigner', $twig);
    }
    
    /**
     * ACTION - Add a new Foreigner - Vietcombank Account
     */
    public function add()
    {
        $this->auth->checkIfOperationIsAllowed('foreigner_add');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('acct_name', 'acct_name', 'required');
        $this->form_validation->set_rules('acct_no', lang('col_acct_no'), 'required|is_unique[foreigner.ACCT_NO]');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('foreigner');
        }
        
        $post = $this->input->post();
        $this->foreigner_model->add(trim($post['acct_name']), trim($post['acct_no']), trim($post['bank_branch']), trim($post['bank_city']));
        redirect('foreigner');
    }
    
    /**
     * ACTION - Modify a current Foreigner - Vietcombank Account
     */
    public function edit()
    {
        $this->auth->checkIfOperationIsAllowed('foreigner_edit');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('forn_id', 'forn_id', 'required');
        $this->form_validation->set_rules('acct_name', 'acct_name', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('foreigner');
        }
        
        $post = $this->input->post();
        $this->foreigner_model->edit($post['forn_id'], trim($post['acct_name']), trim($post['bank_branch']), trim($post['bank_city']));
        redirect('foreigner');
    }
    
    /**
     * ACTION - Delete a current Foreigner - Vietcombank Account
     */
    public function delete()
    {
        $this->auth->checkIfOperationIsAllowed('foreigner_delete');
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('forn_id', 'forn_id', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect('foreigner');
        }
        
        $this->foreigner_model->delete($this->input->post('forn_id'));
        redirect('foreigner');
    }
}
