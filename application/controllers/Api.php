<?php
/**
 * This controller is the entry point for the REST API
 */

if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

/**
 * This class implements a REST API served through an OAuth2 server.
 * In order to use it, you need to insert an OAuth2 client into the database, for example :
 * INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ("testclient", "testpass", "http://fake/");
 * where "testclient" and "testpass" are respectively the login and password.
 * Examples are provided into tests/rest folder.
 */
class Api extends CI_Controller {
    
    /**
     * OAuth2 server used by all methods in order to determine if the user is connected
     * @var OAuth2\Server Authentication server 
     */
    protected $server; 
    
    /**
     * Default constructor
     * Initializing of OAuth2 server
     */
    public function __construct()
    {
        parent::__construct();
        require_once(APPPATH . 'third_party/OAuth2/Autoloader.php');
        OAuth2\Autoloader::register();
        $dsn = 'mysql:dbname=' . $this->db->database . ';host=' . $this->db->hostname;
        $username = $this->db->username;
        $password = $this->db->password;
        $storage = new OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));
        $this->server = new OAuth2\Server($storage);
        $this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
        $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
    }

    /**
     * Get a OAuth2 token
     */
    public function get_token()
    {
        require_once(APPPATH . 'third_party/OAuth2/Server.php');
        $this->server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
    }
    
    /**
     * Get client debit progress of a Insured Client by using MEMB_REF_NO
     * @param int $memb_ref_no
     */
    public function get_client_debit($memb_ref_no)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('debt_model');
            $result = $this->debt_model->get_client_debit($memb_ref_no);
            echo json_encode($result);
        }
    }
    
    /**
     * Get payment progress of a Claim No
     * @param int $cl_no
     */
    public function get_payment($cl_no)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('payments_model');
            $result = $this->payments_model->get_payments_by_claim($cl_no);
            echo json_encode($result);
        }
    }
    
    /**
     * Set a part of transfer amount to pcv expense
     * @param int $paym_id
     * @param int $pcv_expense coming from post
     */
    public function set_pcv_expense($paym_id)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('transfers_model');
            $this->load->model('payments_model');
            $this->load->model('debt_model');
            $this->lang->load('api');
            
            $message = [];
            $transfer = $this->transfers_model->get($paym_id, 'Transferred');
            if (empty($transfer))
            {
                $message['code'] = '01';
                $message['description'] = lang('api_set_pcv_expense_not_found');
            }
            else
            {
                $payment = $this->payments_model->get_payment($paym_id);
                $pcv_expense = $this->input->post('pcv_expense');
                if ($pcv_expense > $payment['TF_AMT'] OR $pcv_expense <= 0)
                {
                    $message['code'] = '02';
                    $message['description'] = lang('api_set_pcv_expense_error_amt');
                }
                else
                {
                    $username = $this->input->post('username');
                    if (empty($username))
                    {
                        $username = 'claimassistant';
                    }
                    $this->session->set_userdata(array('user_name' => "$username"));
                    $this->debt_model->set_debt($transfer['TRAN_ID'], $payment['CL_NO'], $pcv_expense, $payment['MEMB_NAME'], $payment['MEMB_REF_NO'], DEBT_TYPE_PCV_EXPENSE);
                    $message['code'] = '00';
                    $message['description'] = lang('api_success');
                }
            }
            echo json_encode($message);
        }
    }
    
    /**
     * Switch pcv expense to debt
     * @param int $debt_id
     */
    public function set_debt($debt_id)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('debt_model');
            $this->lang->load('api');
            
            $message = [];
            $debt = $this->debt_model->get($debt_id);
            if (empty($debt))
            {
                $message['code'] = '01';
                $message['description'] = lang('api_debt_not_found');
            }
            else
            {
                if ($debt['DEBT_TYPE'] != DEBT_TYPE_PCV_EXPENSE)
                {
                    $message['code'] = '02';
                    $message['description'] = lang('api_set_debt_wrong_type');
                }
                else
                {
                    $username = $this->input->post('username');
                    if (empty($username))
                    {
                        $username = 'claimassistant';
                    }
                    $this->session->set_userdata(array('user_name' => "$username"));
                    $this->debt_model->expense2debt($debt['DEBT_ID']); 
                    $message['code'] = '00';
                    $message['description'] = lang('api_success');
                }
            }
            echo json_encode($message);
        }
    }
    
    /**
     * Set Paid Amt of a debt
     * @param int $debt_id
     * @param int $memb_ref_no coming from post
     */
    public function pay_debt($memb_ref_no)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('debt_model');
            $this->lang->load('api');
            
            $memb_name = $this->input->post('memb_name');
            $cl_no = $this->input->post('cl_no');
            $paid_amt = $this->input->post('paid_amt');
            $username = $this->input->post('username');
            if (empty($username))
            {
                $username = 'claimassistant';
            }
            $this->session->set_userdata(array('user_name' => "$username"));
            $this->debt_model->paid_debt($cl_no, $memb_name, $memb_ref_no, $paid_amt);
            $message = [];
            $message['code'] = '00';
            $message['description'] = lang('api_success');
            
            echo json_encode($message);
        }
    }
    
    /**
     * Claim User sends a Payment to Finance
     * @param int $debt_id
     * @param int $paid_amt
     */
    public function send_payment($cl_no)
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->lang->load('api');
            $this->lang->load('fi_approved_upload');
            $this->load->model('payments_model');
            
            $memb_name = $this->input->post('memb_name');
            $pocy_ref_no = $this->input->post('pocy_ref_no');
            $memb_ref_no = $this->input->post('memb_ref_no');
            $pres_amt = $this->input->post('pres_amt');
            $app_amt = $this->input->post('app_amt');
            $tf_amt = $this->input->post('tf_amt');
            $deduct_amt = $this->input->post('deduct_amt');
            $payment_method = $this->input->post('payment_method');
            $mantis_id = $this->input->post('mantis_id');
            
            $message = [];  
            $payment = $this->payments_model->get_new_payment_by_cl_no($cl_no);    
            if (empty($payment))
            {
                $message['code'] = '01';
                $message['description'] = lang('fi_approved_upload_error_not_found');
            }
            else
            {
                if (strcasecmp(vn_to_str($payment['MEMB_NAME']), vn_to_str($memb_name)) != 0)
                {
                    $message['code'] = '02';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_memb_name'), $memb_name, $payment['MEMB_NAME']);
                }
                elseif ($payment['POCY_REF_NO'] != $pocy_ref_no)
                {
                    $message['code'] = '03';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_pocy_ref_no'), $pocy_ref_no, $payment['POCY_REF_NO']);
                }
                elseif ($payment['MEMB_REF_NO'] != $memb_ref_no)
                {
                    $message['code'] = '04';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_memb_ref_no'), $memb_ref_no, $payment['MEMB_REF_NO']);
                }
                elseif ($payment['APP_AMT'] != $app_amt)
                {
                    $message['code'] = '05';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_app_amt'), $app_amt, $payment['APP_AMT']);
                }
                elseif ($payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT'] != $tf_amt + $deduct_amt)
                {
                    $message['code'] = '06';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_tf_amt'), $tf_amt + $deduct_amt, $payment['TF_AMT'] + $payment['DEDUCT_AMT'] + $payment['DISC_AMT']);
                }
                elseif (strcasecmp($payment['PAYMENT_METHOD'], $payment_method) != 0)
                {
                    $message['code'] = '07';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_payment_method'), $payment_method, $payment['PAYMENT_METHOD']);
                }
                elseif ($payment['MANTIS_ID'] != $mantis_id)
                {
                    $message['code'] = '08';
                    $message['description'] = sprintf(lang('fi_approved_upload_error_mantis_id'), $mantis_id, $payment['MANTIS_ID']);
                }
                else
                {
                    $username = $this->input->post('username');
                    if (empty($username))
                    {
                        $username = 'claimassistant';
                    }
                    $this->session->set_userdata(array('user_name' => "$username"));
                    $this->load->model('payments_history_model');
                    $this->db->trans_start();
                    if ($payment['TF_AMT'] != $tf_amt)
                    {
                        $this->payments_model->set_payment_info($payment['PAYM_ID'], array(
                            'TF_AMT' => $tf_amt,
                        ));
                        $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_API_SEND_PAYMENT, HIST_FIELD_TF_AMT, $payment['TF_AMT'], $tf_amt);
                    }
                    if ($payment['DEDUCT_AMT'] != $deduct_amt)
                    {
                        $this->payments_model->set_payment_info($payment['PAYM_ID'], array(
                            'DEDUCT_AMT' => $deduct_amt,
                        ));
                        $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_API_SEND_PAYMENT, HIST_FIELD_DEDUCT_AMT, $payment['DEDUCT_AMT'], $deduct_amt);
                    }
                    $this->payments_model->set_payment_info($payment['PAYM_ID'], array(
                        'TF_STATUS_ID' => TF_STATUS_APPROVED,
                    ));
                    $this->payments_history_model->add($payment['PAYM_ID'], HIST_TYPE_API_SEND_PAYMENT, HIST_FIELD_TF_STATUS, TF_STATUS_NEW, TF_STATUS_APPROVED);
                    $this->db->trans_complete();
                    $message['code'] = '00';
                    $message['description'] = lang('api_success');
                    $message['data'] = $this->payments_model->get_payment($payment['PAYM_ID']);
                }
            }
            echo json_encode($message);
        }
    }

    /**
     * Manager Claim send unn sgn
     * 
     */
    public function send_unc()
    {
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('group_banker_sign_model');

            
            $url_file = $this->input->post('url_file');
            $id = $this->input->post('id');
            try {
                $file = file_get_contents($url_file);
            } catch (\Throwable $th) {
                $message['code'] = '01';
                $message['description'] = "File not found";
                echo json_encode($message);
                return;
            }
            $file_name =  md5(time()) ;
            $patch_file_unc = FCPATH .'assets/dl/unc_sign/'. $file_name ."_unc_singed.pdf";
            $patch_file_unc_final = FCPATH .'assets/dl/unc_sign/'. $file_name ."_unc_final.pdf";
            file_put_contents($patch_file_unc, $file);
            $group_unc = $this->group_banker_sign_model->get_group_banker_signs($id);
            $patch_file_unc_bank = FCPATH .'assets/dl/unc_sign/'. $group_unc['URL_ALL_UNC'];
            $cm_run = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile=".$patch_file_unc_final. 
        " -dBATCH " . "$patch_file_unc $patch_file_unc_bank" ;
            exec($cm_run);
            $this->group_banker_sign_model->set_value($id,'STATUS',SIGNED);
            $this->group_banker_sign_model->set_value($id,'URL_SIGNED',$file_name ."_unc_final.pdf");
            $message['code'] = '00';
            $message['description'] = "send success";
            echo json_encode($message);

        }
    }
    /**
     * get payment renew
     * 
     */
    public function get_renewed_claim(){
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('payments_model');
            $message['code'] = '00';
            $message['description'] = "success";
            $message['data'] = $this->payments_model->get_renewed_payments();
            echo json_encode($message);
        }
    }

    /**
     * get payments 
     * json paym_ids
     */
    public function get_payments(){
        if ( ! $this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
        {
            $this->server->getResponse()->send();
        }
        else
        {
            $this->load->model('payments_model');
            $message['code'] = '00';
            $message['description'] = "success";
            $paym_ids = json_decode($this->input->post('paym_ids'),true);
            $message['data'] = $this->payments_model->get_payment_by_paym_ids($paym_ids);
            echo json_encode($message);
        }
    }
}
