<?php
/**
 * This Class contains all the business logic and the persistence layer for claim team
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ...
 */
class Settings extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('menu');
        $this->load->model('users_model');
        $this->load->model('settings_model');
    }
    
    /**
	 * PAGE - Organization of Claim Team
	 */
	public function index()
	{
        $twig = getUserContext($this);
        
        $post = $this->input->post();
        if($this->input->post('save_setting')){
            $this->settings_model->update_settings();
        }
        $twig['setting'] = $this->settings_model->get_first_row();
        $this->load->view('settings/index', $twig);
    }
}
