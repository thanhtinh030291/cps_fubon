<?php
/**
 * This controller displays the list of project
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This class displays the workflow of the application and others documents
 */
class Home extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('home');
        $this->lang->load('menu');
    }
    
	/**
	 * Index Page for this controller.
	 */
    
	public function index()
	{
        switch ($this->session->userdata('role_id'))
        {
            case ROLE_CL_USER: redirect('clusers');
            case ROLE_CL_LEADER: redirect('clleaders');
            case ROLE_CL_MANAGER: redirect('clmanagers');
            case ROLE_CL_DIRECTOR: redirect('cldirectors');
            case ROLE_FI: redirect('fi');
            case ROLE_IT: redirect('users');
            default: redirect('forbidden');
        }
	}
    
    /**
     * Display a simple view indicating that the business object was not found.
     */
    public function notfound()
    {
        $twig = getUserContext($this);
        $this->load->view('home/notfound', $twig);
    }
    
    /**
     * Display a simple view indicating that connection to the business object was not accepted.
     */
    public function forbidden()
    {
        $twig = getUserContext($this);
        $this->load->view('home/forbidden', $twig);
    }
}
