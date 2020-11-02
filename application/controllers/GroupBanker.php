<?php
/**
 * This Class contains all the business logic and the persistence layer for claim team
 * @since         0.2.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
/**
 * ...
 */
class GroupBanker extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('menu');
        $this->load->model('group_banker_sign_model');
    }
    
    /**
	 * PAGE - Organization of Claim Team
	 */
	public function index()
	{
        $twig = getUserContext($this);
        $twig['groups'] = $this->group_banker_sign_model->get_group_banker_signs();
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $this->load->view('groupbanker/index', $twig);
    }

    public function request_signing(){
        $id =  $this->input->post('id');
        $group = $this->group_banker_sign_model->get_group_banker_signs($id);
        $token = getTokenClaimAss();
        $this->load->model('settings_model');
        $setting = $this->settings_model->get_first_row();
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'bearer ' . $token,
        ];
        $body = [
            'name' => $group['NAME'],
            'url_file_sign' => base_url() . "assets/dl/unc_sign/" .$group['URL_NO_SIGN'],
            'group_unc_id' => $id
        ];
        $client = new Client([
                'headers' => $headers
            ]);
        try {
            $request = $client->request("POST", $setting->url_cl_ass. "api/requestSign" , ['form_params'=>$body]);
            $response = $request->getBody();
            $kq = json_decode($response->getContents(), true);
            if($kq['status'] == "success"){
                $this->session->set_flashdata('msg_success', $kq['message']);
                $this->group_banker_sign_model->set_value($id,"STATUS", WAIT_SIGN);
            }
        }catch (ClientException $e) {
            $response = $e->getResponse()->getBody(true);
            $this->session->set_flashdata('msg_danger', 'System error');
        }
        
        
        redirect('groupbanker');

    }
}
