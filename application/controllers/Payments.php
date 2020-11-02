<?php
/**
 * This controller displays the workflow of the application and others documents
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This class displays the workflow of the application and others documents
 */
class Payments extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('payments_model');
        $this->load->model('payments_history_model');
        $this->lang->load('payment');
        $this->lang->load('menu');
        $this->lang->load('columns');
        $this->lang->load('status');
    }
    
	/**
	 * PAGE - a payment details
     * @param int $parq_id
	 */
    
	public function index($paym_id)
	{
        $this->auth->checkIfOperationIsAllowed('view_payment');
        $twig = getUserContext($this);
        $twig['msg_success'] = $this->session->flashdata('msg_success');
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        
        $this->load->model('debt_model');
        $this->load->model('users_model');
        $this->load->model('notes_model');
        $this->load->model('payments_function_model');
        $this->load->model('transfers_model');
        
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }

        if ( ! $payment['IS_DELETED'] && ($payment['CL_USER'] == $twig['user_name'] OR ($twig['role_id'] >= ROLE_CL_LEADER && $twig['role_id'] <= ROLE_CL_DIRECTOR)))
        {
            if ($payment['TF_STATUS_ID'] <= TF_STATUS_APPROVED)
            {
                if ($payment['YN_HOLD'] == 'N')
                {
                    $twig['toggle'][] = 'hold';
                }
                else
                {
                    $twig['toggle'][] = 'unhold';
                }
            }
        }
        // for Toggle Delete/Undelete function - Claim User only
        if ($payment['CL_USER'] == $twig['user_name'])
        {
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                    if ($payment['IS_DELETED'])
                    {
                        $twig['toggle'][] = 'undelete';
                    }
                    else
                    {
                        $twig['toggle'][] = 'delete';
                    }
                    break;
            }
        }
        // for popover - Claim User
        if ( ! $payment['IS_DELETED'] && $payment['CL_USER'] == $twig['user_name'])
        {
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['popover_deduct'] = 1;
                    $twig['popover_discount'] = 1;
                    break;
            }
        }
        // for popover - Claim Leader
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_LEADER)
        {
            $twig['popover_assign'] = 1;
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_APPROVAL:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['popover_deduct'] = 1;
                    $twig['popover_discount'] = 1;
                    break;
            }
        }
        // for popover - Claim Manager
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_MANAGER)
        {
            $twig['popover_assign'] = 1;
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_APPROVAL:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['popover_deduct'] = 1;
                    $twig['popover_discount'] = 1;
                    break;
            }
        }
        // for popover - Claim Director
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_DIRECTOR)
        {
            $twig['popover_assign'] = 1;
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                case TF_STATUS_DIRECTOR_APPROVAL:
                    $twig['popover_deduct'] = 1;
                    $twig['popover_discount'] = 1;
                    break;
            }
        }
        // for Workflow function - Claim User
        if ( ! $payment['IS_DELETED'] && $payment['CL_USER'] == $twig['user_name'])
        {
            $twig['controller'] = 'payments';
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                    $twig['workflow'][] = 'request_leader';
                    break;
                case TF_STATUS_LEADER_APPROVAL:
                    $twig['workflow'][] = 'cancel_leader';
                    break;
                case TF_STATUS_MANAGER_APPROVAL:
                    $twig['workflow'][] = 'cancel_manager';
                    break;
                case TF_STATUS_MANAGER_REJECTED:
                    $twig['workflow'][] = 'request_manager';
                    break;
                case TF_STATUS_DIRECTOR_APPROVAL:
                    $twig['workflow'][] = 'cancel_director';
                    break;
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['workflow'][] = 'request_director';
                    break;
                case TF_STATUS_APPROVED:
                    $twig['workflow'][] = 'user_take_over';
                    break;
            }
        }
        // for Workflow function - Claim Leader
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_LEADER)
        {
            $twig['controller'] = 'clleaders';
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                    $twig['workflow'][] = 'request_manager';
                    break;
                case TF_STATUS_LEADER_APPROVAL:
                    $twig['workflow'][] = 'leader_reject';
                    $twig['workflow'][] = 'request_manager';
                    break;
                case TF_STATUS_MANAGER_APPROVAL:
                    $twig['workflow'][] = 'cancel_manager';
                    $twig['controller'] = 'payments';
                    break;
                case TF_STATUS_DIRECTOR_APPROVAL:
                    $twig['workflow'][] = 'cancel_director';
                    $twig['controller'] = 'payments';
                    break;
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['workflow'][] = 'request_director';
                    $twig['controller'] = 'payments';
                    break;
                case TF_STATUS_APPROVED:
                    $twig['workflow'][] = 'leader_take_over';
                    $twig['controller'] = 'payments';
                    break;
            }
        }
        // for Workflow function - Claim Manager
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_MANAGER)
        {
            $twig['controller'] = 'clmanagers';
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_APPROVAL:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_REJECTED:
                    if ($payment['APP_AMT'] > 100000000)
                    {
                        $twig['workflow'][] = 'request_director';
                    }
                    else
                    {
                        $twig['workflow'][] = 'request_finance';
                    }
                    break;
                case TF_STATUS_MANAGER_APPROVAL:
                    $twig['workflow'][] = 'manager_reject';
                    if ($payment['APP_AMT'] > 100000000)
                    {
                        $twig['workflow'][] = 'request_director';
                    }
                    else
                    {
                        $twig['workflow'][] = 'request_finance';
                    }
                    break;
                case TF_STATUS_DIRECTOR_APPROVAL:
                    $twig['workflow'][] = 'cancel_director';
                    $twig['controller'] = 'payments';
                    break;
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['workflow'][] = 'request_director';
                    $twig['controller'] = 'payments';
                    break;
                case TF_STATUS_APPROVED:
                    $twig['workflow'][] = 'manager_take_over';
                    $twig['controller'] = 'payments';
                    break;
            }
        }
        // for Workflow function - Claim Director
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_CL_DIRECTOR)
        {
            $twig['controller'] = 'cldirectors';
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_NEW:
                case TF_STATUS_LEADER_APPROVAL:
                case TF_STATUS_LEADER_REJECTED:
                case TF_STATUS_MANAGER_APPROVAL:
                case TF_STATUS_MANAGER_REJECTED:
                case TF_STATUS_DIRECTOR_REJECTED:
                    $twig['workflow'][] = 'request_finance';
                    break;
                case TF_STATUS_DIRECTOR_APPROVAL:
                    $twig['workflow'][] = 'director_reject';
                    $twig['workflow'][] = 'request_finance';
                    break;
                case TF_STATUS_APPROVED:
                    $twig['workflow'][] = 'director_take_over';
                    $twig['controller'] = 'payments';
                    break;
            }
        }
        
        // for Workflow function - Finance
        if ( ! $payment['IS_DELETED'] && $twig['role_id'] == ROLE_FI)
        {
            $twig['controller'] = 'fi';
            switch ($payment['TF_STATUS_ID'])
            {
                case TF_STATUS_TRANSFERRED:
                    $twig['workflow'][] = 'refund';
                    if (empty($payment['SHEET_ID']))
                    {
                        $twig['workflow'][] = 'unpay';
                    }
                    break;
                case TF_STATUS_TRANSFERRED_PAYPREM:
                case TF_STATUS_TRANSFERRED_DLVN_CANCEL:
                case TF_STATUS_TRANSFERRED_DLVN_PAYPREM:
                    $twig['workflow'][] = 'pay_repaid';
                    break;
            }
        }

        $twig['payment'] = $payment;
        $twig['users'] = $this->users_model->get_members(null);
        $twig['relationship_payments'] = $this->payments_model->get_relationship_payments($payment['CL_NO']);
        $twig['debt'] = $this->debt_model->get_balance_debt($payment['MEMB_REF_NO']);
        $twig['notes'] = $this->notes_model->get($paym_id);
        $twig['history'] = $this->payments_history_model->get_payment_history($paym_id);
        $twig['transfers'] = $this->transfers_model->get_all_transfers($payment['CL_NO']);
        $twig['client_debit'] = $this->debt_model->get_client_debit($payment['MEMB_REF_NO']);
        
        $this->load->view('payment/payment', $twig);
	}

    /**
     * ACTION: deduct transfer amount of a payment because of client debt
     * Data are coming from HTML form
     * @param int $paym_id
     */
    public function deduct($paym_id)
    {
        $data['DEDUCT_AMT'] = $this->input->post('deduct_amt');
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['DEDUCT_AMT'] == $data['DEDUCT_AMT'])
        {
            redirect("payments/$paym_id");
        }
        $this->load->model('debt_model');
        $debt = $this->debt_model->get_balance_debt($payment['MEMB_REF_NO']);
        if (empty($debt))
        {
            redirect("payments/$parq_id");
        }
        $max_deduct_amt = ($payment['TF_AMT'] > $debt ? $debt : $payment['TF_AMT']) + $payment['DEDUCT_AMT'];
        $this->auth->checkIfOperationIsAllowed('deduct_payment', $payment['CL_USER']);
        $this->load->library('form_validation');
        $this->form_validation->set_rules('deduct_amt', 'Deduct Amount', "required|greater_than_equal_to[0]|less_than_equal_to[{$max_deduct_amt}]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $data['TF_AMT'] = $payment['TF_AMT'] + $payment['DEDUCT_AMT'] - $data['DEDUCT_AMT'];
        
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, $data);
        $this->debt_model->pay_debt_deduction($payment['MEMB_NAME'], $payment['MEMB_REF_NO'], $data['DEDUCT_AMT'] - $payment['DEDUCT_AMT'], $payment['CL_NO']);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DEDUCT, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $data['TF_AMT']);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DEDUCT, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], $data['DEDUCT_AMT']);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION: Set discount amount of a payment
     * Data are coming from HTML form
     * @param int $paym_id
     */
    public function discount($paym_id)
    {
        $data['DISC_AMT'] = $this->input->post('disc_amt');
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['DISC_AMT'] == $data['DISC_AMT'])
        {
            redirect("payments/$paym_id");
        }
        $max_disc_amt = $payment['TF_AMT'] + $payment['DISC_AMT'];
        $this->auth->checkIfOperationIsAllowed('discount_payment', $payment['CL_USER']);
        $this->load->library('form_validation');
        $this->form_validation->set_rules('disc_amt', 'Discount Amount', "required|greater_than_equal_to[0]|less_than_equal_to[{$max_disc_amt}]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $data['TF_AMT'] = $max_disc_amt - $data['DISC_AMT'];
        
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, $data);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DISCOUNT, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $data['TF_AMT']);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DISCOUNT, HIST_FIELD_DISC_AMT, $payment['DISC_AMT'], $data['DISC_AMT']);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - assign a payment to a user
     * Data are coming from HTML form
     * @param int $paym_id
     */
    public function assign($paym_id)
    {
        $user_name = $this->input->post('user_name');
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['CL_USER'] == $user_name)
        {
            redirect("payments/$paym_id");
        }
        $this->auth->checkIfOperationIsAllowed('assign_payment');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_name', 'Username', "required");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'CL_USER', $user_name);
        $this->payments_history_model->add($paym_id, HIST_TYPE_ASSIGN, HIST_FIELD_CL_USER, $payment['CL_USER'], $user_name);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - HOLD a payment in order to pause Finance processing
     * Data are coming from HTML form
     * @param int $paym_id
     */
    public function hold($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['YN_HOLD'] == 'Y' OR $payment['TF_STATUS_ID'] >= TF_STATUS_TRANSFERRING)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('modal_hold', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'YN_HOLD', 'Y');
        $this->payments_history_model->add($paym_id, HIST_TYPE_HOLD);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - UNHOLD a payment in order to resume Finance processing
     * Data are coming from HTML form
     * @param int $paym_id
     */
    public function unhold($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['YN_HOLD'] == 'N' OR $payment['TF_STATUS_ID'] >= TF_STATUS_TRANSFERRING)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('modal_unhold', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'YN_HOLD', 'N');
        $this->payments_history_model->add($paym_id, HIST_TYPE_UNHOLD);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Delete a payment, deduct amt will be removed
     * @param int $paym_id
     */
    public function delete($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR ! in_array($payment['TF_STATUS_ID'], [TF_STATUS_NEW, TF_STATUS_LEADER_REJECTED, TF_STATUS_MANAGER_REJECTED, TF_STATUS_DIRECTOR_REJECTED]))
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('modal_delete', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'IS_DELETED', 1);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DELETE);
        if ($payment['DEDUCT_AMT'])
        {
            $data['TF_AMT'] = $payment['TF_AMT'] + $payment['DEDUCT_AMT'];
            $data['DEDUCT_AMT'] = 0;
            $this->payments_model->set_values($paym_id, $data);
            $this->load->model('debt_model');
            $this->debt_model->pay_debt_deduction($payment['MEMB_NAME'], $payment['MEMB_REF_NO'], -$payment['DEDUCT_AMT'], $payment['CL_NO']);
            $this->payments_history_model->add($paym_id, HIST_TYPE_DELETE, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $data['TF_AMT']);
            $this->payments_history_model->add($paym_id, HIST_TYPE_DELETE, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], $data['DEDUCT_AMT']);
        }
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Un-Delete a payment
     * @param int $paym_id
     * @param int $prev_tf_status_id
     */
    public function undelete($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ( ! $payment['IS_DELETED'] && in_array($payment['TF_STATUS_ID'], [TF_STATUS_NEW, TF_STATUS_LEADER_REJECTED, TF_STATUS_MANAGER_REJECTED, TF_STATUS_DIRECTOR_REJECTED]))
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('modal_undelete', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'IS_DELETED', 0);
        $this->payments_history_model->add($paym_id, HIST_TYPE_UNDELETE);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - send request to Leader
     * @param int $paym_id
     */
    public function request_leader($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR ! in_array($payment['TF_STATUS_ID'], [TF_STATUS_NEW, TF_STATUS_LEADER_REJECTED]))
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('request_leader', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_LEADER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_LEADER, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_LEADER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
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
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_MANAGER_REJECTED)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('request_manager', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_MANAGER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_MANAGER, HIST_FIELD_TF_STATUS, TF_STATUS_MANAGER_REJECTED, TF_STATUS_MANAGER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - send request to Director
     * @param int $paym_id
     */
    public function request_director($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_DIRECTOR_REJECTED)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('request_director', $payment['CL_USER']);
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_DIRECTOR_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_REQUEST_DIRECTOR, HIST_FIELD_TF_STATUS, TF_STATUS_DIRECTOR_REJECTED, TF_STATUS_DIRECTOR_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - cancel a request in Leader Approval
     * @param int $paym_id
     */
    public function cancel_leader($paym_id)
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
        $this->auth->checkIfOperationIsAllowed('cancel_leader', $payment['CL_USER']);
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
        $this->payments_history_model->add($paym_id, HIST_TYPE_CANCEL_LEADER, HIST_FIELD_TF_STATUS, TF_STATUS_LEADER_APPROVAL, TF_STATUS_LEADER_REJECTED);
        $this->notes_model->add($paym_id, NOTE_TYPE_CANCEL_LEADER, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - cancel a request in Manager Approval
     * @param int $paym_id
     */
    public function cancel_manager($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        if ($payment['IS_DELETED'] OR $payment['TF_STATUS_ID'] != TF_STATUS_MANAGER_APPROVAL)
        {
            redirect('forbidden');
        }
        $this->auth->checkIfOperationIsAllowed('cancel_manager', $payment['CL_USER']);
        $this->load->library('form_validation');
        $this->form_validation->set_rules('reject_reason', 'Reject Reason', "required|min_length[10]|max_length[1000]");
        if ($this->form_validation->run() === FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect("payments/$paym_id");
        }
        $this->load->model('notes_model');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_MANAGER_REJECTED);
        $this->payments_history_model->add($paym_id, HIST_TYPE_CANCEL_MANAGER, HIST_FIELD_TF_STATUS, TF_STATUS_MANAGER_APPROVAL, TF_STATUS_MANAGER_REJECTED);
        $this->notes_model->add($paym_id, NOTE_TYPE_CANCEL_MANAGER, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - User cancel a request in Director Approval
     * @param int $paym_id
     */
    public function cancel_director($paym_id)
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
        $this->auth->checkIfOperationIsAllowed('cancel_director', $payment['CL_USER']);
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
        $this->payments_history_model->add($paym_id, HIST_TYPE_CANCEL_DIRECTOR, HIST_FIELD_TF_STATUS, TF_STATUS_DIRECTOR_APPROVAL, TF_STATUS_DIRECTOR_REJECTED);
        $this->notes_model->add($paym_id, NOTE_TYPE_CANCEL_DIRECTOR, $this->input->post('reject_reason'));
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Leader call back request from Manager
     * @param int $paym_id
     */
    public function leader_unrequest($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('leader_unrequest_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_LEADER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_LEADER_UNREQUEST, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_LEADER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Manager send request to Finance
     * @param int $paym_id
     */
    public function manager_request_finance($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('manager_request_finance_payment');
        
        $app_date = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, array(
            'TF_STATUS_ID' => TF_STATUS_APPROVED,
            'APP_DATE' => $app_date,
        ));
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_REQUEST_FINANCE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_APPROVED);
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_REQUEST_FINANCE, HIST_FIELD_APP_DATE, $payment['APP_DATE'], $app_date);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Manager call back request from Finance
     * @param int $paym_id
     */
    public function manager_unrequest_finance($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('manager_unrequest_finance_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, array(
            'TF_STATUS_ID' => TF_STATUS_MANAGER_APPROVAL,
            'APP_DATE' => null,
        ));
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_UNREQUEST_FINANCE, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_MANAGER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_UNREQUEST_FINANCE, HIST_FIELD_APP_DATE, $payment['APP_DATE'], null);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Manager send request to Director
     * @param int $paym_id
     */
    public function manager_request_director($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('manager_request_director_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_DIRECTOR_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_REQUEST_DIRECTOR, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_DIRECTOR_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Manager call back request from Director
     * @param int $paym_id
     */
    public function manager_unrequest_director($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('manager_unrequest_director_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_MANAGER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_MANAGER_UNREQUEST_DIRECTOR, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_MANAGER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Director send request to Finance
     * @param int $paym_id
     */
    public function director_request($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('director_request_payment');
        
        $app_date = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, array(
            'TF_STATUS_ID' => TF_STATUS_APPROVED,
            'APP_DATE' => $app_date,
        ));
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_REQUEST, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_APPROVED);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_REQUEST, HIST_FIELD_APP_DATE, $payment['APP_DATE'], $app_date);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Director call back request from Finance
     * @param int $paym_id
     */
    public function director_unrequest($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('director_unrequest_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_values($paym_id, array(
            'TF_STATUS_ID' => TF_STATUS_DIRECTOR_APPROVAL,
            'APP_DATE' => null,
        ));
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_UNREQUEST, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_DIRECTOR_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_DIRECTOR_UNREQUEST, HIST_FIELD_APP_DATE, $payment['APP_DATE'], null);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * ACTION - Leader request User to review
     * @param int $paym_id
     */
    public function leader_review($paym_id)
    {
        $payment = $this->payments_model->get_payment($paym_id);
        if (empty($payment))
        {
            redirect('notfound');
        }
        $this->auth->checkIfOperationIsAllowed('leader_review_payment');
        
        $this->db->trans_start();
        $this->payments_model->set_value($paym_id, 'TF_STATUS_ID', TF_STATUS_MANAGER_APPROVAL);
        $this->payments_history_model->add($paym_id, HIST_TYPE_LEADER_REQUEST, HIST_FIELD_TF_STATUS, $payment['TF_STATUS_ID'], TF_STATUS_MANAGER_APPROVAL);
        $this->db->trans_complete();
        redirect("payments/$paym_id");
    }
    
    /**
     * Open CPS by using bugnote id of Mantis system
     * @param int $bug_id
     */
    public function mantis($bug_id)
    {
        $id = $this->payments_model->get_max_id_of_mantis_id($bug_id);
        if (empty($id))
        {
            redirect('notfound');
        }
        redirect("payments/$id");
    }
}
