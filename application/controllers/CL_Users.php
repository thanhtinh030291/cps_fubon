<?php
/**
 * This controller serves all the actions performed by claim user
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class CL_Users extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('payments_model');
        $this->load->model('payments_history_model');
        $this->lang->load('menu');
        $this->lang->load('status');
        $this->lang->load('clusers');
    }
    
	/**
	 * PAGE - User Statistics
	 */
	public function index()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_stats');
        $twig = getUserContext($this);
        $this->load->model('debt_model');
        
        foreach (CL_STATUSES as $tf_status_id)
        {
            $twig['stat'][$tf_status_id] = $this->payments_model->count_user_payments($twig['user_name'], $tf_status_id);
        }
        $twig['balance_debt'] = $this->debt_model->get_balance_debt();
        
        $this->load->view('clusers/stats', $twig);
    }
    
    /**
	 * PAGE - Show all New Payments of current user
	 */
    public function new_payments()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_new');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_new');
        $twig['page_subtitle'] = lang('clusers_new_desc');
        $twig['page_color'] = 'primary';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_NEW);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - Show all Leader Rejected Payments of current user
	 */
	public function leader_rejected()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_leader_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_leader_rejected');
        $twig['page_subtitle'] = lang('clusers_leader_rejected_desc');
        $twig['page_color'] = 'leader';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_LEADER_REJECTED);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - Show all Manager Rejected Payments of current user
	 */
	public function manager_rejected()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_manager_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_manager_rejected');
        $twig['page_subtitle'] = lang('clusers_manager_rejected_desc');
        $twig['page_color'] = 'danger';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_MANAGER_REJECTED);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - Show all Director Rejected Payments of current user
	 */
	public function director_rejected()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_director_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_director_rejected');
        $twig['page_subtitle'] = lang('clusers_director_rejected_desc');
        $twig['page_color'] = 'director';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_DIRECTOR_REJECTED);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - User's Payments which are waiting for Team Leader approval
	 */
	public function leader_approval()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_leader_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_leader_approval');
        $twig['page_subtitle'] = lang('clusers_leader_approval_desc');
        $twig['page_color'] = 'leader';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_LEADER_APPROVAL);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - User's Payments which are waiting for Manager approval
	 */
	public function manager_approval()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_manager_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_manager_approval');
        $twig['page_subtitle'] = lang('clusers_manager_approval_desc');
        $twig['page_color'] = 'danger';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_MANAGER_APPROVAL);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - User's Payments which are waiting for Manager approval
	 */
	public function director_approval()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_director_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_director_approval');
        $twig['page_subtitle'] = lang('clusers_director_approval_desc');
        $twig['page_color'] = 'director';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_DIRECTOR_APPROVAL);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
	 * PAGE - Show all Returned To Claim Payments of current user
	 */
	public function returned_payments()
	{
        $this->auth->checkIfOperationIsAllowed('clusers_returned');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page_header'] = lang('clusers_returned');
        $twig['page_subtitle'] = lang('clusers_returned_desc');
        $twig['page_color'] = 'finance';
        $twig['payments'] = $this->payments_model->get_user_payments($twig['user_name'], TF_STATUS_RETURNED_TO_CLAIM);
        $this->load->view('clusers/clusers', $twig);
    }
    
    /**
     * ACTION - User takes over a request in Finance
     * @param int $paym_id
     */
    public function take_over($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_APPROVED)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('take_over');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('reject_reason', 'Reject Reason', "required|min_length[10]|max_length[1000]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $this->load->model('notes_model');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_NEW);
        $this->payments_history_model->add($paym_id, HIST_TYPE_USER_TAKE_OVER, HIST_FIELD_TF_STATUS, TF_STATUS_APPROVED, TF_STATUS_NEW);
        $this->notes_model->add($paym_id, NOTE_TYPE_USER_TAKE_OVER, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
}
