<?php
/**
 * This controller serves the user management pages and tools.
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This controller serves the user management pages and tools.
 */
class Users extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->load->model('users_model');
        $this->lang->load('menu');
        $this->lang->load('users');
    }

    /**
     * Display the list of all users
     */
    public function index()
    {
        $this->auth->checkIfOperationIsAllowed('list_users');
        $twig = getUserContext($this);
        $twig['users'] = $this->users_model->get_users();
        $twig['msg'] = $this->session->flashdata('msg');
        $this->load->view('users/users', $twig);
    }
    
    /**
     * Display a form that allows updating a given user
     * @param int $user_id User identifier
     */
    public function edit($user_id)
    {
        $this->auth->checkIfOperationIsAllowed('edit_user');
        $twig = getUserContext($this);
        $twig['user'] = $this->users_model->get_users($user_id);
        if (empty($twig['user']))
        {
            redirect('notfound');
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules('user_name', 'user_name', 'required|trim|min_length[2]|max_length[20]');
        $this->form_validation->set_rules('role_id', 'role_id', 'required|trim');
        $this->form_validation->set_rules('active', 'active', 'required|trim');
        $this->form_validation->set_rules('fullname', 'fullname', 'required|trim|min_length[3]|max_length[100]');
        $this->form_validation->set_rules('email', 'email', 'required|trim|min_length[14]|max_length[250]|valid_email');
        
        if ($this->form_validation->run() === FALSE)
        {
            $twig['msg_danger'] = validation_errors();
            $this->load->model('roles_model');
            $twig['roles'] = $this->roles_model->get_roles();
            $this->load->view('users/edit', $twig);
        }
        else
        {
            $this->users_model->update_users($user_id);
            if (isset($_GET['source']))
            {
                redirect($_GET['source']);
            }
            redirect('users');
        }
    }

    /**
     * Reset the password of a user
     * Can be accessed by the user itself or by admin
     * @param int $id User identifier
     */
    public function reset($user_id)
    {
        $this->auth->checkIfOperationIsAllowed('change_password', $user_id);
        $twig = getUserContext($this);
        $twig['user'] = $this->users_model->get_users($user_id);
        if (empty($twig['user']))
        {
            redirect('notfound');
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules('CipheredValue', 'Password', 'required');
        $this->form_validation->set_rules('password', 'password', 'required|min_length[8]|max_length[16]');
        if ($this->form_validation->run() === FALSE)
        {
            $twig['msg_danger'] = validation_errors();
            $twig['public_key'] = file_get_contents('./assets/keys/public.pem', TRUE);
            $this->load->view('users/reset', $twig);
        }
        else
        {
            $this->users_model->reset_password($user_id, $this->input->post('CipheredValue'));
            //Send an e-mail to the user so as to inform that its password has been changed
            $this->load->library('email');
            $this->load->library('parser');
            $message = $this->parser->parse('emails/password_reset', array('Name' => $twig['user']['FULLNAME']), TRUE);
            $this->email->from($this->config->item('from_mail'), $this->config->item('from_name'));
            $this->email->to($twig['user']['EMAIL']);
            $this->email->subject($this->config->item('subject_prefix') . lang('user_email_reset'));
            $this->email->message($message);
            $this->email->send();
            if (isset($_GET['source']))
            {
                redirect($_GET['source']);
            }
            redirect('users');
        }
    }

    /**
     * Display the form / action Create a new user
     */
    public function create()
    {
        $this->auth->checkIfOperationIsAllowed('create_user');
        $twig = getUserContext($this);
        
        $this->load->library('form_validation');
        $this->load->model('roles_model');
        $twig['roles'] = $this->roles_model->get_roles();
        $twig['public_key'] = file_get_contents('./assets/keys/public.pem', TRUE);

        $this->form_validation->set_rules('user_name', 'user_name', 'required|trim|min_length[2]|max_length[20]');
        $this->form_validation->set_rules('role_id', 'role_id', 'required|trim');
        $this->form_validation->set_rules('fullname', 'fullname', 'required|trim|min_length[3]|max_length[100]');
        $this->form_validation->set_rules('email', 'email', 'required|trim|min_length[14]|max_length[250]|valid_email|is_unique[users.EMAIL]');
        if ($this->form_validation->run() === FALSE)
        {
            $twig['msg_danger'] = validation_errors();
            $twig['public_key'] = file_get_contents('./assets/keys/public.pem', TRUE);
            $this->load->view('users/create', $twig);
        }
        else
        {
            $password = $this->users_model->set_users();
            // Send an e-mail to the user so as to inform that its account has been created
            $this->load->library('email');
            $this->load->library('parser');
            $data = array(
                'BaseURL' => base_url(),
                'Fullname' => $this->input->post('fullname'),
                'Username' => $this->input->post('user_name'),
                'Password' => $password
            );
            $message = $this->parser->parse('emails/new_user', $data, TRUE);
            $this->email->from($this->config->item('from_mail'), $this->config->item('from_name'));
            $this->email->to($this->input->post('email'));
            $this->email->subject($this->config->item('subject_prefix') . 'Your account has been created');
            $this->email->message($message);
            $this->email->send();
            
            redirect('users');
        }
    }
}
