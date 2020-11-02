<?php
/**
 * This class manages the authorization to access to pages.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This class manages the authorization to access to pages.
 */
class Auth {

    /**
     * Access to CI framework so as to use other libraries
     * @var type Code Igniter framework
     */
    private $CI;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->CI = & get_instance();
    }

    /**
     * Check if the current user can perform a given action on the system.
     * This function only prevents gross security issues when a user try to access 
     * a restricted screen.
     * Note that any operation needs the user to be connected.
     * @param string $operation Operation attempted by the user
     * @param int $id  optional object identifier of the operation (e.g. user id)
     * @return bool true if the user is granted, false otherwise
     */
    public function isAllowed($operation, $object_id = 0)
    {
        switch ($operation)
        {
            // User management
            case 'list_users':
            case 'create_user':
            case 'edit_user' :
            // case 'list_roles' :
            // case 'list_settings' :
                if ($this->CI->session->userdata('role_id') == ROLE_IT)
                {
                    return true;
                }
                return false;
            // Password management
            case 'change_password' :
                if ($this->CI->session->userdata('role_id') == ROLE_IT OR $this->CI->session->userdata('user_id') == $object_id)
                {
                    return true;
                }
                return false;
            // Configuration of Claim User objects
            case 'clusers_stats':
            case 'clusers_new':
            case 'clusers_leader_rejected':
            case 'clusers_manager_rejected':
            case 'clusers_director_rejected':
            case 'clusers_leader_approval':
            case 'clusers_manager_approval':
            case 'clusers_director_approval':
            
            // case 'clusers_returned':
            // case 'clusers_deduct':
            // case 'clusers_delete':
            // case 'clusers_undelete':
            // case 'clusers_request':
            // case 'clusers_unrequest':
            // case 'clusers_feedback_finance':
            // case 'clusers_unfeedback_finance':
            // case 'clusers_deleted':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_USER)
                {
                    return true;
                }
                return false;
            // Configuration of Claim Team Leader objects
            case 'clleaders_stats':
            case 'clleaders_leader_approval':
            case 'clleaders_new':
            case 'clleaders_leader_rejected':
            case 'clleaders_manager_rejected':
            case 'clleaders_director_rejected':
            case 'clleaders_manager_approval':
            case 'clleaders_director_approval':
            
            case 'clleaders_approve':
            case 'clleaders_unapprove':
            case 'clleaders_review':
            case 'clleaders_reject':
            case 'clleaders_undo':
            case 'clleaders_reviewed':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_LEADER)
                {
                    return true;
                }
                return false;
            // Configuration of Claim Manager objects
            case 'clmanagers_stats':
            case 'clmanagers_manager_approval':
            case 'clmanagers_new':
            case 'clmanagers_leader_approval':
            case 'clmanagers_leader_rejected':
            case 'clmanagers_manager_rejected':
            case 'clmanagers_director_rejected':
            case 'clmanagers_director_approval':
            
            case 'clmanagers_reviewed':
            case 'clmanagers_approve':
            case 'clmanagers_unapprove':
            case 'clmanagers_review':
            case 'clmanagers_reject':
            case 'clmanagers_undo':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_MANAGER)
                {
                    return true;
                }
                return false;
            // Configuration of Claim Director objects
            case 'cldirectors_stats':
            case 'cldirectors_director_approval':
            case 'cldirectors_new':
            case 'cldirectors_leader_approval':
            case 'cldirectors_manager_approval':
            case 'cldirectors_leader_rejected':
            case 'cldirectors_manager_rejected':
            case 'cldirectors_director_rejected':
            
            case 'cldirectors_approve':
            case 'cldirectors_unapprove':
            case 'cldirectors_reviewed':
            case 'cldirectors_review':
            case 'cldirectors_reject':
            case 'cldirectors_undo':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            // Configuration of Claim Team Leader & Manager & Director objects
            case 'cl_change_claim_user':
                if ($this->CI->session->userdata('role_id') >= ROLE_CL_LEADER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            // Configuration of Claim Manager & Director objects
            case 'cl_approved':
            case 'cl_team':
                if ($this->CI->session->userdata('role_id') >= ROLE_CL_MANAGER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            // Configuration of Claim objects
            case 'cl_hold':
                if ($this->CI->session->userdata('role_id') >= ROLE_CL_USER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            // Configuration of Finance objects
            case 'fi':
            case 'fi_stats':
            case 'fi_approved':
            case 'fi_partner':
            case 'fi_update_bank_info':
            case 'fi_export_approved_payments':
            case 'fi_return_to_claim':
            case 'fi_returned_claim':
            case 'fi_return_to_dlvn':
            case 'fi_cancel':
            case 'fi_sheets':
            case 'fi_sheets_partner':
            case 'fi_sheet':
            case 'fi_edit':
            case 'fi_eject':
            case 'fi_undo':
            case 'fi_undo_multi':
            case 'fi_close':
            case 'fi_vcbsheet':
            case 'fi_reopen':
            case 'fi_upload':
            case 'fi_transferring':
            case 'fi_pay':
            case 'fi_unpay':
            case 'fi_transferred':
            case 'fi_refund_upload':
            case 'fi_refund':
            case 'fi_partner_request':
            case 'fi_claim_fund':
            case 'fi_claim_bordereaux':
            case 'fi_replenish':
            case 'fi_transferred_sheets':
            case 'fi_decrease_transferred':
            case 'fi_repay':
            case 'fi_set_bebal':
            case 'fi_set_enbal':
            case 'fi_do_not_pay':
            case 'fi_pay_repaid':
            case 'fi_change_sheet':
                if ($this->CI->session->userdata('role_id') == ROLE_FI)
                {
                    return true;
                }
                return false;
            // Configuration of Foreigner
            case 'foreigner':
            case 'foreigner_add':
            case 'foreigner_edit':
            case 'foreigner_delete':
                if ($this->CI->session->userdata('role_id') == ROLE_FI)
                {
                    return true;
                }
                return false;
            /* case 'report_list_unpaid_payments':
                if ($this->CI->session->userdata('is_claim_manager') OR $this->CI->session->userdata('is_finance'))
                {
                    return true;
                }
                return false; */
            // Configuration of Claim & Finance objects
            case 'view_payment':
                return true;
            case 'deduct_payment':
            case 'discount_payment':
            case 'modal_hold':
            case 'modal_unhold':
            case 'request_leader':
            case 'request_manager':
            case 'request_director':
            case 'request_finance':
            case 'cancel_leader':
            case 'cancel_manager':
            case 'cancel_director':
                if (($this->CI->session->userdata('role_id') >= ROLE_CL_LEADER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR) OR $this->CI->session->userdata('user_name') == $object_id)
                {
                    return true;
                }
                return false;
            case 'take_over':
                if ($this->CI->session->userdata('role_id') >= ROLE_CL_USER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            case 'assign_payment':
                if ($this->CI->session->userdata('role_id') >= ROLE_CL_LEADER && $this->CI->session->userdata('role_id') <= ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            case 'modal_delete':
            case 'modal_undelete':
                if ($this->CI->session->userdata('user_name') == $object_id)
                {
                    return true;
                }
                return false;
            case 'leader_reject':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_LEADER)
                {
                    return true;
                }
                return false;
            case 'manager_reject':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_MANAGER)
                {
                    return true;
                }
                return false;
            case 'director_reject':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            case 'manager_request_finance_payment':
            case 'manager_unrequest_finance_payment':
            case 'manager_request_director_payment':
            case 'manager_unrequest_director_payment':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_MANAGER)
                {
                    return true;
                }
                return false;
            case 'director_request_payment':
            case 'director_unrequest_payment':
                if ($this->CI->session->userdata('role_id') == ROLE_CL_DIRECTOR)
                {
                    return true;
                }
                return false;
            /* case 'providers':
                if ($this->CI->session->userdata('is_claim') OR $this->CI->session->userdata('is_claim_manager') OR $this->CI->session->userdata('is_finance'))
                {
                    return true;
                }
                return false; */
            // Configuration of Customer Service objects
            /* case 'cs':
            case 'view_myprofile':
            
                return true;
                break; */
            default:
                return false;
                break;
        }
    }

    /**
     * Check if the current user can perform a given action on the system.
     * @use isAllowed
     * @param string $operation Operation attempted by the user
     * @param int $id  optional object identifier of the operation (e.g. user id)
     * @return bool true if the user is granted, false otherwise
     */
    public function checkIfOperationIsAllowed($operation, $object_id = 0)
    {
        if ( ! $this->isAllowed($operation, $object_id))
        {
            redirect('forbidden');
        }
    }
}
