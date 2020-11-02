<?php


defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;

class Settings_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
        $first_row = $this->db->where('id', 1)->get('settings')->row();
        if ($first_row == null){
            $this->db->insert('settings', ['id' => 1]);
        }
    }

    public function get_first_row(){
        return $this->db->where('id', 1)->get('settings')->row();
    }

    public function update_settings()
    {
        $data = array(
            'email_notify_claim' => $this->input->post('email_notify_claim'),
            'email_notify_finance' => $this->input->post('email_notify_finance'),
            'url_cl_ass' => $this->input->post('url_cl_ass'),
            'user_name_cl_ass' => $this->input->post('user_name_cl_ass'),
            'password_cl_ass' => $this->input->post('password_cl_ass'),
        );
        $this->db
            ->where('id', 1)
            ->update('settings', $data);
    }

    public function update_token($token)
    {
        $data = array(
            'token_cl_ass' => $token,
            'updated_at' => Carbon::now()
        );
        $this->db
            ->where('id', 1)
            ->update('settings', $data);
    }
}
