<?php
/**
 * This controller manages the connection to the application
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This class manages the connection to the application
 * CodeIgniter uses a cookie to store session's details.
 * Login page uses RSA so as to encrypt the user's password.
 */
class Connection extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load('session');
    }
    
    /**
     * Generate a random string
     * @param int $length optional length of the string
     * @return string random string
     */
    private function generateRandomString($length = 10)
    {
        $sha ='';
        $rnd ='';
        for ($i = 0; $i < $length; ++$i)
        {
          $sha  = hash('sha256', $sha . mt_rand());
          $char = mt_rand(0, 62);
          $rnd .= chr(hexdec($sha[$char] . $sha[$char + 1]));
        }
        return base64_encode($rnd);
    }
    
    /**
     * Login form
     */
    public function login()
    {
        // If we are already connected (login bookmarked), then redirect to home
        if ($this->session->userdata('logged_in'))
        {
            redirect('home');
        }
        // get string
        $twig['msg'] = $this->session->flashdata('msg');
        $this->load->library('form_validation');
        //Note that we don't receive the password as a clear string
        $this->form_validation->set_rules('username', lang('session_login_field_username'), 'trim');
        $this->form_validation->set_rules('password', lang('session_login_field_password'), 'trim|min_length[8]');
        if ($this->form_validation->run() === FALSE)
        {
            $twig['validation_errors'] = validation_errors();
            $twig['public_key'] = file_get_contents('./assets/keys/public.pem', TRUE);
            $twig['salt'] = $this->generateRandomString(rand(5, 20));
            $this->session->set_userdata('salt', $twig['salt']);
            $this->load->view('session/login', $twig);
        }
        else
        {
            $this->load->model('users_model');
            // Decipher the password value and remove the salt!
            $password = '';
            $privateKey = openssl_pkey_get_private(file_get_contents('./assets/keys/private.pem', TRUE));
            openssl_private_decrypt(base64_decode($this->input->post('CipheredValue')), $password, $privateKey); 
            // Remove the salt
            $len_salt = strlen($this->session->userdata('salt')) * (-1);
            $password = substr($password, 0, $len_salt);
            
            $loggedin = FALSE;
            $loggedin = $this->users_model->checkCredentials($this->input->post('username'), $password);
            
            if ($loggedin == FALSE)
            {
                if ($this->users_model->isActive($this->input->post('username')))
                {
                    $this->session->set_flashdata('msg', lang('session_login_flash_bad_credentials'));
                }
                else
                {
                    $this->session->set_flashdata('msg', lang('session_login_flash_account_disabled'));
                }
                redirect('session/login');
            }
            redirect('home');
        }
    }

    /**
     * Logout the user and destroy the session data
     */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('session/login');
    }
}
