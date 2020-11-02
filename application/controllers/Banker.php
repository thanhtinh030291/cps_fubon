<?php
/**
 * This controller displays the list of project
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');
use Smalot\PdfParser\Parser;
// phpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Carbon\Carbon;
/**
 * This class displays the workflow of the application and others documents
 */
class Banker extends CI_Controller {

    /**
     * Default constructor
     */
    public function __construct()
    {
        parent::__construct();
        setUserContext($this);
        $this->lang->load('menu');
        $this->lang->load('transferred');
        $this->load->model('payments_model');
        $this->lang->load('columns');
        $this->lang->load('fi');
        $this->lang->load('transferred');
        $this->load->model('banker_model');
        $this->load->model('partner_model');
        
        if (!is_dir('./assets/dl/unc')) {
            mkdir('./assets/dl/unc' , 0777, TRUE);
        }
        if (!is_dir('./assets/dl/unc_covert')) {
            mkdir('./assets/dl/unc_covert', 0777, TRUE);
        }
        if (!is_dir('./assets/dl/unc_split')) {
            mkdir('./assets/dl/unc_split', 0777, TRUE);
        }        
        if (!is_dir('./assets/dl/unc_sign')) {
            mkdir('./assets/dl/unc_sign', 0777, TRUE);
        }
        if (!is_dir('./assets/dl/mpdf')) {
            mkdir('./assets/dl/mpdf', 0777, TRUE);
        }
    }
    
	/**
	 * Index Page for this controller.
	 */
    public function index()
    {
        $this->auth->checkIfOperationIsAllowed('fi_transferred');
        $twig = getUserContext($this);
        
        $this->load->model('transfer_status_model');
        $this->lang->load('columns');
        $this->lang->load('transferred');
        
        $post = $this->input->get();
        if ( ! empty($post) && isset($post['btnSearch']))
        {
            $condition = [];
            $condition['sys.TF_STATUS_ID >='] = TF_STATUS_TRANSFERRED;
            if ( ! empty($post['cl_no']))
            {
                $condition['CL_NO'] = $post['cl_no'];
            }
            
            if ( ! empty($post['pocy_ref_no']))
            {
                $condition['POCY_REF_NO'] = $post['pocy_ref_no'];
            }
            if ( ! empty($post['memb_ref_no']))
            {
                $condition['MEMB_REF_NO'] = $post['memb_ref_no'];
            }
            if ( ! empty($post['memb_name']))
            {
                $condition['MEMB_NAME'] = $post['memb_name'];
            }
            if ( ! empty($post['tf_date_from']))
            {
                $condition['TF_DATE >='] = $post['tf_date_from'];
            }
            if ( ! empty($post['tf_date_to']))
            {
                $condition['TF_DATE <='] = $post['tf_date_to'];
            }
            if ( ! empty($condition))
            {
                $payments = $this->payments_model->search_payments($condition);
                    $twig['payments'] = $payments;
            }
        }
        
        $twig['post'] = $post;
        $twig['transfer_status'] = $this->transfer_status_model->get_transfer_status();
        $twig['msg_danger'] = $this->session->flashdata('msg_danger');
        $this->load->view('banker/index', $twig);
    }

	public function add_unc_index()
	{
        $twig = getUserContext($this);
        $config['file_name'] = md5(time()) . '.pdf';;
        $config['upload_path'] = './assets/dl/unc';
        $config['allowed_types'] = 'gif|jpg|png|pdf|PDF';
        $this->load->library('upload', $config);
        
        if($this->input->post('save')){
            
            $this->upload->do_upload('fileu');
            $data =  $this->upload->data();
            
            $patch_file_upload = FCPATH .'assets/dl/unc/'. $data['file_name'];
            $patch_file_convert = FCPATH .'assets/dl/unc_covert/'. $data['file_name'];
            
            $cm_run ="gs -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sOutputFile=". $patch_file_convert ." ".$patch_file_upload;
            exec($cm_run, $output);
            $parser = new Parser();
            $pdf = $parser->parseFile($patch_file_convert);
            $pages  = $pdf->getPages();
            $paymented =  $this->payments_model->get_payment_transfed_unc($this->input->post('tf_date_from') , $this->input->post('tf_date_to'));
            $daichi = $this->partner_model->get_bank_info();
            $payment_merge = [];
            foreach ($pages as $key_page => $page) {
                $text = $page->getText();
                foreach ($paymented as $key_payment => $payment) {
                    if($payment['CL_TYPE'] == 'M'){
                        if($payment['PAYMENT_METHOD'] == "TT"){
                                $claim_no = $payment['CL_NO'];
                                preg_match("/$claim_no/", $text, $matches_cl_no);
                                $name = trim(strtoupper(vn_to_str($payment['ACCT_NAME'])));
                                preg_match("/$name/", $text, $matches_name);
                                $tf_amount = number_format($payment['TF_AMT']);
                                preg_match("/$tf_amount/", $text, $matches_tf_amt);
                                if ($matches_cl_no && $matches_name && $matches_tf_amt) {
                                    unset($paymented[$key_payment]);
                                    $payment_merge[$key_payment] = $payment;
                                    $payment_merge[$key_payment]['page'] = $key_page + 1;
                                    break;
                                } 
                        }elseif($payment['PAYMENT_METHOD'] == "CH"){
                            $name = trim(strtoupper(vn_to_str($payment['BENEFICIARY_NAME'])));
                            preg_match("/$name/",  $text, $matches_name);
                            $tf_amount = number_format($payment['TF_AMT']);
                            preg_match("/$tf_amount/", $text, $matches_tf_amt);
                            $pp_no = trim($payment['PP_NO']);
                            preg_match("/$pp_no/",  $text, $matches_pp_no);
                            if($matches_pp_no && $matches_name && $matches_tf_amt){
                                unset($paymented[$key_payment]);
                                $payment_merge[$key_payment] = $payment; 
                                $payment_merge[$key_payment]['page'] = $key_page + 1;
                            break;
                            }
                        }elseif($payment['PAYMENT_METHOD'] == "PP"){
                            $acc_no = trim($daichi['ACCT_NO']);
                            preg_match("/$acc_no/",  $text, $matches_acc_no);
                            $claim_no = $payment['CL_NO'];
                            preg_match("/$claim_no/", $text, $matches_cl_no);
                            if ($matches_acc_no && $matches_cl_no) {
                                unset($paymented[$key_payment]);
                                $payment_merge[$key_payment] = $payment;
                                $payment_merge[$key_payment]['page'] = $key_page + 1;
                                
                            } 
                        }
                    }else{
                        $claim_no = $payment['CL_NO'];
                        preg_match("/$claim_no/", $text, $matches_cl_no);
                        $ACCT_NO = trim($payment['ACCT_NO']);
                        preg_match("/$ACCT_NO/", $text, $matches_acct_no);
                        if($matches_cl_no && $matches_acct_no){
                            unset($paymented[$key_payment]);
                            $payment_merge[$key_payment] = $payment; 
                            $payment_merge[$key_payment]['page'] = $key_page + 1;
                        
                        }
                    }
                }
            }
            $twig['payment_merge'] = $payment_merge;
            $twig['file_name'] = $data['file_name'];
            $twig['url_file_name'] = base_url().'assets/dl/unc/'.$data['file_name'];
        }
        $twig['post'] = $this->input->post();
        $this->load->view('banker/add_unc', $twig);
    }
    
    public function add_unc(){
        $file_name = $this->input->post('file_name');
        $patch_file_convert = FCPATH .'assets/dl/unc_covert/'. $file_name;
        $patch_file_split = FCPATH .'assets/dl/unc_split/'. explode(".",$file_name)[0];
        $paym_ids = explode(",",$this->input->post('paym_ids'));
        $paym_pages = explode(",",$this->input->post('paym_pages'));
        foreach ($paym_pages as $key => $paym_page) {
            $cm_run = "gs -sDEVICE=pdfwrite -q -dNOPAUSE -dBATCH -sOutputFile=$patch_file_split-$paym_page.pdf -dFirstPage=$paym_page -dLastPage=$paym_page $patch_file_convert";
            exec($cm_run);
        }
        
        foreach ($paym_ids as $key => $paym_id) {
            
            $this->banker_model->updateOrInsert(
                [
                    'PAYM_ID' => $paym_id,
                    'URL_UNC' => explode(".",$file_name)[0] . "-" . $paym_pages[$key] .".pdf"
                ]
            );
        }
        redirect('banker');
        
    }

    public function update_unc(){
        $this->load->library('form_validation');
        $this->form_validation->set_rules('PAGE[]', 'PAGE', 'required');
        
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('msg_danger', validation_errors());
            redirect($_SERVER['HTTP_REFERER']);
        }
        $file_name = md5(time());
        $config['file_name'] = $file_name . '.pdf';;
        $config['upload_path'] = './assets/dl/unc';
        $config['allowed_types'] = 'gif|jpg|png|pdf|PDF';
        $this->load->library('upload', $config);
        $this->upload->do_upload('fileu');
        $data =  $this->upload->data();
        $patch_file_upload = FCPATH .'assets/dl/unc/'. $data['file_name'];
        $patch_file_convert = FCPATH .'assets/dl/unc_covert/'. $data['file_name'];
        $cm_run ="gs -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sOutputFile=". $patch_file_convert ." ".$patch_file_upload;
        exec($cm_run);
        $patch_file_split = FCPATH .'assets/dl/unc_split/'. $file_name;
        $paym_ids = $this->input->post('PAYM_ID');
        $paym_pages = $this->input->post('PAGE');
        
        foreach ($paym_pages as $key => $paym_page) {
            $cm_run = "gs -sDEVICE=pdfwrite -q -dNOPAUSE -dBATCH -sOutputFile=$patch_file_split-$paym_page.pdf -dFirstPage=$paym_page -dLastPage=$paym_page $patch_file_convert";
            exec($cm_run);
        }
        foreach ($paym_ids as $key2 => $paym_id) {
            
            $this->banker_model->updateOrInsert(
                [
                    'PAYM_ID' => $paym_id,
                    'URL_UNC' => $file_name . "-" . $paym_pages[$key2] .".pdf"
                ]
            );
        }
        redirect($_SERVER['HTTP_REFERER']);
        
    }



    public function export_pdf_sign(){
        $paym_ids = explode(",", $this->input->post('paym_ids')) ;
        $twig['payments'] = $this->payments_model->get_payment_by_paym_ids($paym_ids);
        $twig['tf_dates'] = $this->payments_model->get_distinct_tf_date_by_paym_ids($paym_ids);
        $file_name = md5(time());
        $patch_file_sign = FCPATH .'assets/dl/unc_sign/'. $file_name ."_nosign.pdf";
        $patch_file_unc = FCPATH .'assets/dl/unc_sign/'. $file_name ."_unc.pdf";
        $mpdf = new \Mpdf\Mpdf(['tempDir' => FCPATH.('assets/dl/mpdf/')]);
        $html = $this->load->view('pdf/sign_pdf',$twig,true);
        $mpdf->AddPage('L');
        $mpdf->WriteHTML($html);
        $mpdf->Output($patch_file_sign, 'F');

        $mpdf_unc = new \Mpdf\Mpdf(['tempDir' => FCPATH.('assets/dl/mpdf/')]);
        
        foreach ($twig['payments'] as $key => $value) {
            $filename =  FCPATH .'assets/dl/unc_split/'. $value['URL_UNC'];
            $mpdf_unc->AddPage();
            if (file_exists($filename)) {
                $pagesInFile = $mpdf_unc->SetSourceFile($filename);

                for ($i = 1; $i <= $pagesInFile; $i++) {
                    $tplId = $mpdf_unc->ImportPage($i);
                    $mpdf_unc->UseTemplate($tplId);
                    
                }
            }
        }
        $mpdf_unc->Output($patch_file_unc, 'F');

        $this->load->model('group_banker_sign_model');
        $name_group = "";
        foreach ($twig['tf_dates'] as $key => $value) {
            $name_group .= " " .$value['TF_DATE'];
        }
        $id_group = $this->group_banker_sign_model->updateOrInsert([
            'NAME' => 'unc-sign'."($name_group)",
            'STATUS' => NO_SIGN,
            'URL_NO_SIGN' => $file_name ."_nosign.pdf",
            'URL_ALL_UNC' => $file_name ."_unc.pdf"
        ]);
        redirect('groupbanker');
    }

    public function add_unc_code_index()
	{
        $twig = getUserContext($this);
        if($this->input->post('save')){
            $post = $this->input->post();
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($_FILES['fileu']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            $condition['(sys.TF_STATUS_ID = "'.TF_STATUS_TRANSFERRED.'" or sys.TF_STATUS_ID = "' .TF_STATUS_TRANSFERRED_PAYPREM. '" or sys.TF_STATUS_ID = "' .TF_STATUS_RETURNED_TO_CLAIM.'")'] = null;
            if ( ! empty($post['tf_date_from']))
            {
                $condition['TF_DATE ='] = $post['tf_date_from'];
            }
            $payments = $this->payments_model->search_payments($condition);
            $payment_merge = [];
            foreach ($payments as $key_payment => $payment) {
                foreach ($sheetData as $key => $row)
                {
                    if ($key < 2)
                    {
                        continue;
                    }
                    if(removeFormatPrice(trim($row['A'])) == $payment['TF_AMT'] && preg_replace('/\s+/','', trim($row['C'])) == preg_replace('/\s+/','', $payment['VCB_SEQ']) ){
                        $payment_merge[$key_payment] = $payment;
                        $payment_merge[$key_payment]['TF_VCB_CODE'] = trim($row['D']);
                        break;
                    }
                }
                
            }
            $twig['payment_merge'] = $payment_merge;
        }
        $twig['post'] = $this->input->post();
        $this->load->view('banker/add_unc_code', $twig);
    }

    public function add_unc_code(){

        $paym_ids = explode(",",$this->input->post('paym_ids'));
        $paym_codes = explode(",",$this->input->post('paym_codes'));
        foreach ($paym_ids as $key => $paym_id) {
            $this->payments_model->set_values($paym_id, [
                'VCB_CODE' => $paym_codes[$key]
            ]);
            
        }
        redirect('banker');
    }
}
