<?php
/**
 * This Class contains all the business logic and the persistence layer for claim team
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class Team extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('menu');
        $this->lang->load('team');
        $this->load->model('users_model');
    }
    
    /**
	 * PAGE - Organization of Claim Team
	 */
	public function index()
	{
        $this->auth->checkIfOperationIsAllowed('cl_team');
        $twig = getUserContext($this);
        
        $post = $this->input->post();
        if (isset($post['selected_ids']))
        {
            $selected_ids = json_decode($post['selected_ids']);
            foreach ($selected_ids as $user_id)
            {
                if (isset($post['btnSetLeader']))
                {
                    $this->users_model->set_leader($user_id, ROLE_CL_LEADER);
                }
                elseif (isset($post['btnDisableUser']))
                {
                    $this->users_model->set_active($user_id, false);
                }
                elseif (isset($post['btnMoveToTeam']))
                {
                    $this->users_model->set_team($user_id, $post['selectedLeader']);
                }
            }
        }
        elseif (isset($post['btnSetUser']))
        {
            $this->users_model->set_member($post['btnSetUser']);
        }
        elseif (isset($post['btnEnableUser']))
        {
            $this->users_model->set_active($post['btnEnableUser'], true);
        }

        $leaders = $this->users_model->get_info_leaders();
        if ( ! empty($leaders))
        {
            foreach ($leaders as $index => $leader)
            {
                $leader['members'] = $this->users_model->get_members($leader['USER_ID']);
                $twig['leaders'][$leader['USER_ID']] = $leader;
            }
        }
        $twig['enabled_users'] = $this->users_model->get_info_members(null);
        $twig['disabled_users'] = $this->users_model->get_info_members(null, false);
        $this->load->view('team/team', $twig);
    }
}
