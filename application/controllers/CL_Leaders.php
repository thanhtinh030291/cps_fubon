<?php
/**
 * This controller serves all the actions performed by claim leaders
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class CL_Leaders extends CI_Controller {

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
        $this->lang->load('clleaders');
    }
    
	/**
	 * PAGE - Leader Statistics
	 */
	public function index()
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_stats');
        $twig = getUserContext($this);
        $this->load->model('debt_model');
        
        foreach (CL_STATUSES as $tf_status_id)
        {
            $twig['stat'][$tf_status_id] = $this->payments_model->count_team_payments($twig['user_id'], $tf_status_id);
        }
        $twig['balance_debt'] = $this->debt_model->get_balance_debt();
        
        $this->load->view('clleaders/stats', $twig);
    }
    
    /**
	 * PAGE - Main task of leader - list of payments are waiting for Leader to approve
	 */
    public function leader_approval($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_leader_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'leader_approval';
        $twig['class'] = 'leader';
        $twig['menu'] = 'claim';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_LEADER_APPROVAL);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_LEADER_APPROVAL);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_LEADER_APPROVAL);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all New Payments of team
	 */
    public function new_payments($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_new');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'new';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_NEW);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_NEW);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_NEW);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all Payments of team which leader have rejected
	 */
    public function leader_rejected($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_leader_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'leader_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_LEADER_REJECTED);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_LEADER_REJECTED);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_LEADER_REJECTED);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all Payments of team which manager have rejected
	 */
    public function manager_rejected($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_manager_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'manager_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_MANAGER_REJECTED);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_MANAGER_REJECTED);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_MANAGER_REJECTED);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all Payments of team which director have rejected
	 */
    public function director_rejected($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_director_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'director_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_DIRECTOR_REJECTED);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_DIRECTOR_REJECTED);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_DIRECTOR_REJECTED);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all Payments of team which are waiting for Manager to approve
	 */
    public function manager_approval($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_manager_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'manager_approval';
        $twig['class'] = 'danger';
        $twig['menu'] = 'approval';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_MANAGER_APPROVAL);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_MANAGER_APPROVAL);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_MANAGER_APPROVAL);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
	 * PAGE - Show all Payments of team which are waiting for Director to approve
	 */
    public function director_approval($user_name = '')
	{
        $this->auth->checkIfOperationIsAllowed('clleaders_director_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $twig['page'] = 'director_approval';
        $twig['class'] = 'director';
        $twig['menu'] = 'approval';

        if ($user_name)
        {
            $twig['payments'] = $this->payments_model->get_user_payments($user_name, TF_STATUS_DIRECTOR_APPROVAL);
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_team_payments($twig['user_id'], TF_STATUS_DIRECTOR_APPROVAL);
        }
        
        $this->load->model('users_model');
        $members = $this->users_model->get_members($twig['user_id']);
        if ( ! empty($members))
        {
            foreach ($members as $member)
            {
                $twig['members'][$member] = $this->payments_model->count_user_payments($member, TF_STATUS_DIRECTOR_APPROVAL);
            }
        }
        
        $twig['member'] = $user_name;
        $this->load->view('clleaders/clleaders', $twig);
    }
    
    /**
     * ACTION - send request to Manager
     * @param int $paym_id
     */
    public function request_manager($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR ! in_array($payment['TF_STATUS_ID'], [TF_STATUS_NEW, TF_STATUS_LEADER_APPROVAL, TF_STATUS_LEADER_REJECTED, TF_STATUS_MANAGER_REJECTED]))
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('request_manager');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_MANAGER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_MANAGER, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_MANAGER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - reject user's request
     * @param int $paym_id
     */
    public function leader_reject($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_LEADER_APPROVAL)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('leader_reject');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('reject_reason', 'Reject Reason', "required|min_length[10]|max_length[1000]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $this->load->model('notes_model');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_LEADER_REJECTED);
        $this->payments_history_model->add($paym_id, HIST_TYPE_LEADER_REJECT, HIST_FIELD_TF_STATUS, TF_STATUS_LEADER_APPROVAL, TF_STATUS_LEADER_REJECTED);
        $this->notes_model->add($paym_id, NOTE_TYPE_LEADER_REJECT, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Leader takes over a request in Finance
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
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_LEADER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_LEADER_TAKE_OVER, HIST_FIELD_TF_STATUS, TF_STATUS_APPROVED, TF_STATUS_LEADER_APPROVAL);
        $this->notes_model->add($paym_id, NOTE_TYPE_LEADER_TAKE_OVER, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
}
