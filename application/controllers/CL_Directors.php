<?php
/**
 * This controller serves all the actions performed by claim director
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class CL_Directors extends CI_Controller {

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
        $this->lang->load('cldirectors');
    }
    
    /**
	 * PAGE - Director Statistics
	 */
	public function index()
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_stats');
        $twig = getUserContext($this);
        $this->load->model('debt_model');
        
        foreach (CL_STATUSES as $tf_status_id)
        {
            $twig['stat'][$tf_status_id] = $this->payments_model->count_status($tf_status_id);
        }
        $twig['balance_debt'] = $this->debt_model->get_balance_debt();
        
        $this->load->view('cldirectors/stats', $twig);
    }
    
    /**
	 * PAGE - Main task of Director - list of payments are waiting for Director to approve
	 */
    public function director_approval($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_director_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'director_approval';
        $twig['class'] = 'director';
        $twig['menu'] = 'claim';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_DIRECTOR_APPROVAL);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_DIRECTOR_APPROVAL);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_DIRECTOR_APPROVAL);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_DIRECTOR_APPROVAL);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_DIRECTOR_APPROVAL);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all New Payments
	 */
    public function new_payments($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_new');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'new';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_NEW);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_NEW);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_NEW);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_NEW);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_NEW);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all Payments which are waiting for Leader to approve
	 */
    public function leader_approval($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_leader_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'leader_approval';
        $twig['class'] = 'leader';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_LEADER_APPROVAL);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_LEADER_APPROVAL);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_LEADER_APPROVAL);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_LEADER_APPROVAL);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_LEADER_APPROVAL);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all Payments which are waiting for Manager to approve
	 */
    public function manager_approval($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_manager_approval');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'manager_approval';
        $twig['class'] = 'danger';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_MANAGER_APPROVAL);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_MANAGER_APPROVAL);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_MANAGER_APPROVAL);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_MANAGER_APPROVAL);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_MANAGER_APPROVAL);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all Payments which have been rejected by Leader
	 */
    public function leader_rejected($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_leader_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'leader_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_LEADER_REJECTED);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_LEADER_REJECTED);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_LEADER_REJECTED);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_LEADER_REJECTED);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_LEADER_REJECTED);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all Payments which have been rejected by Manager
	 */
    public function manager_rejected($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_manager_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'manager_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_MANAGER_REJECTED);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_MANAGER_REJECTED);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_MANAGER_REJECTED);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_MANAGER_REJECTED);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_MANAGER_REJECTED);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
	 * PAGE - Show all Payments which have been rejected by Director
	 */
    public function director_rejected($leader = '', $member = '')
	{
        $this->auth->checkIfOperationIsAllowed('cldirectors_director_rejected');
        $twig = getUserContext($this);
        $this->lang->load('columns');
        $this->load->model('users_model');
        $twig['page'] = 'director_rejected';
        $twig['class'] = 'primary';
        $twig['menu'] = 'team';
        
        if ($leader)
        {
            $leader_id = $this->users_model->get_user_id($leader);
            if ($member)
            {
                $twig['payments'] = $this->payments_model->get_user_payments($member, TF_STATUS_DIRECTOR_REJECTED);
            }
            else
            {
                $twig['payments'] = $this->payments_model->get_team_payments($leader_id, TF_STATUS_DIRECTOR_REJECTED);
            }
            $members = $this->users_model->get_members($leader_id);
            if ( ! empty($members))
            {
                foreach ($members as $user)
                {
                    $twig['members'][$user] = $this->payments_model->count_user_payments($user, TF_STATUS_DIRECTOR_REJECTED);
                }
            }
            $twig['member'] = $member;
        }
        else
        {
            $twig['payments'] = $this->payments_model->get_status_payments(TF_STATUS_DIRECTOR_REJECTED);
        }
        
        $leaders = $this->users_model->get_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $team)
            {
                $team_id = $this->users_model->get_user_id($team);
                $twig['leaders'][$team] = $this->payments_model->count_team_payments($team_id, TF_STATUS_DIRECTOR_REJECTED);
            }
        }
        
        $twig['leader'] = $leader;
        $this->load->view('cldirectors/cldirectors', $twig);
    }
    
    /**
     * ACTION - send request to Finance
     * @param int $paym_id
     */
    public function request_finance($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR ! in_array($payment['TF_STATUS_ID'], [TF_STATUS_NEW, TF_STATUS_LEADER_APPROVAL, TF_STATUS_LEADER_REJECTED, TF_STATUS_MANAGER_APPROVAL, TF_STATUS_MANAGER_REJECTED, TF_STATUS_DIRECTOR_APPROVAL, TF_STATUS_DIRECTOR_REJECTED]))
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('request_finance');
        $data['TF_STATUS_ID'] = TF_STATUS_APPROVED;
        $data['APP_DATE'] = date('Y-m-d H:i:s');
        
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, $data);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_FINANCE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], $data['TF_STATUS_ID']);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_FINANCE, HIST_FIELD_APP_DATE, $payment['APP_DATE'], $data['APP_DATE']);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - reject manager's request
     * @param int $paym_id
     */
    public function director_reject($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_DIRECTOR_APPROVAL)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('director_reject');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('reject_reason', 'Reject Reason', "required|min_length[10]|max_length[1000]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $this->load->model('notes_model');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_DIRECTOR_REJECTED);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_REJECT, HIST_FIELD_TF_STATUS, TF_STATUS_DIRECTOR_APPROVAL, TF_STATUS_DIRECTOR_REJECTED);
        $this->notes_model->add($paym_id, NOTE_TYPE_DIRECTOR_REJECT, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Director takes over a request in Finance
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
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_DIRECTOR_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_TAKE_OVER, HIST_FIELD_TF_STATUS, TF_STATUS_APPROVED, TF_STATUS_DIRECTOR_APPROVAL);
        $this->notes_model->add($paym_id, HIST_TYPE_DIRECTOR_TAKE_OVER, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
}
