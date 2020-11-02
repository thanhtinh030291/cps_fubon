<?php
/**
 * This helper contains a list of functions used throughout the application
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Check if user is connected, redirect to login form otherwise
 * Set the user context by retrieving infos from session
 * @param CI_Controller $controller reference to CI Controller object
 */
function setUserContext(CI_Controller $controller)
{
    if ( ! $controller->session->userdata('logged_in'))
    {
        if (filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest')
        {
            $controller->output->set_status_header('401');
        }
        else
        {
            redirect('session/login');
        }
    }
    
    $controller->user_id = $controller->session->userdata('user_id');
    $controller->user_name = $controller->session->userdata('user_name');
    $controller->fullname = $controller->session->userdata('fullname');
    $controller->user_email = $controller->session->userdata('user_email');
    $controller->role_id = $controller->session->userdata('role_id');
    $controller->leader_id = $controller->session->userdata('leader_id');
}

/**
 * Prepare an array containing information about the current user
 * @param CI_Controller $controller reference to CI Controller object
 * @return array data to be passed to the view
 */
function getUserContext(CI_Controller $controller)
{
    $data['user_id'] = $controller->user_id;
    $data['user_name'] = $controller->user_name;
    $data['fullname'] = $controller->fullname;
    $data['user_email'] = $controller->user_email;
    $data['role_id'] = $controller->role_id;
    $data['leader_id'] = $controller->leader_id;
    return $data;
}

/**
 * remove diacritic marks of a string
 */
function vn_to_str($str)
{
	$unicode = array(
		'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
		'd' => 'đ',
		'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
		'i' => 'í|ì|ỉ|ĩ|ị',
		'o' =>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
		'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
		'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
		'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
		'D' => 'Đ',
		'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
		'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
		'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
		'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
		'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
	);
	foreach ($unicode as $nonUnicode => $uni)
    {
		$str = preg_replace( "/($uni)/", $nonUnicode, $str );
	}
	return $str;
}

/**
 * Wrapper between the controller and the e-mail library
 * @param CI_Controller $controller reference to CI Controller object
 * @param string $subject Subject of the e-mail
 * @param string $message Message of the e-mail
 * @param string $to Recipient of the e-mail
 * @param string $cc (optional) Copied to recipients
 * @author Benjamin BALET <benjamin.balet@gmail.com>
 */
function sendMailByWrapper(CI_Controller $controller, $subject, $message, $to, $cc = NULL)
{
    $controller->load->library('email');
    $controller->email->subject($controller->config->item('subject_prefix') . ' ' . $subject);
    $controller->email->from($controller->config->item('from_mail'), $controller->config->item('from_name'));
    $controller->email->to($to);
    if ( ! is_null($cc))
    {
        $controller->email->cc($cc);
    }
    $controller->email->message($message);
    $controller->email->send();
}

/**
 * debug Ui beauty
 * auth tinhnguyen
 */

if (! function_exists('dd')) {
    function dd($data, $die = true, $add_var_dump = false, $add_last_query = true)
    {
        $CI = &get_instance();
        $CI->load->library('unit_test');

        $bt = debug_backtrace();
        $src = file($bt[0]["file"]);
        $line = $src[$bt[0]['line'] - 1];
        # Match the function call and the last closing bracket
        preg_match('#' . __FUNCTION__ . '\((.+)\)#', $line, $match);
        $max = strlen($match[1]);
        $varname = null;
        $c = 0;
        for ($i = 0; $i < $max; $i++) {
            if ($match[1]{$i} == "(") {
                $c++;
            } elseif ($match[1]{$i} == ")") {
                $c--;
            }
            if ($c < 0) {
                break;
            }
            $varname .= $match[1]{$i};
        }

        if (is_object($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-object">OBJECT</span>';
        } elseif (is_array($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-array">ARRAY</span>';
        } elseif (is_string($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-string">STRING</span>';
        } elseif (is_int($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-integer">INTEGER</span>';
        } elseif (is_true($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-true">TRUE [BOOLEAN]</span>';
        } elseif (is_false($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-false">FALSE [BOOLEAN]</span>';
        } elseif (is_null($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-null">NULL</span>';
        } elseif (is_float($data)) {
            $message = '<span class="vayes-debug-badge vayes-debug-badge-float">FLOAT</span>';
        } else {
            $message = 'N/A';
        }

        $output  = '<div style="clear:both;"></div>';
        $output .= '<meta charset="UTF-8" />';
        $output .= '<style>body{margin:0}::selection{background-color:#E13300!important;color:#fff}::moz-selection{background-color:#E13300!important;color:#fff}::webkit-selection{background-color:#E13300!important;color:#fff}div.debugbody{background-color:#fff;margin:0px;font:9px/12px normal;font-family:Arial,Helvetica,sans-serif;color:#4F5155;min-width:500px}a.debughref{color:#039;background-color:transparent;font-weight:400}h1.debugheader{color:#444;background-color:transparent;border-bottom:1px solid #D0D0D0;font-size:12px;line-height:14px;font-weight:700;margin:0 0 14px;padding:14px 15px 10px;font-family:\'Ubuntu Mono\',Consolas}code.debugcode{font-family:\'Ubuntu Mono\',Consolas,Monaco,Courier New,Courier,monospace;font-size:12px;background-color:#f9f9f9;border:1px solid #D0D0D0;color:#002166;display:block;margin:10px 0;padding:5px 10px 15px}code.debugcode.debug-last-query{display:none}pre.debugpre{display:block;padding:0;margin:0;color:#002166;font:12px/14px normal;font-family:\'Ubuntu Mono\',Consolas,Monaco,Courier New,Courier,monospace;background:0;border:0}div.debugcontent{margin:0 15px}p.debugp{margin:0;padding:0}.debugitalic{font-style:italic}.debutextR{text-align:right;margin-bottom:0;margin-top:0}.debugbold{font-weight:700}p.debugfooter{text-align:right;font-size:11px;border-top:1px solid #D0D0D0;line-height:32px;padding:0 10px;margin:20px 0 0}div.debugcontainer{margin:0px;border:1px solid #D0D0D0;-webkit-box-shadow:0 0 8px #D0D0D0}code.debug p{padding:0;margin:0;width:100%;text-align:right;font-weight:700;text-transform:uppercase;border-bottom:1px dotted #CCC;clear:right}code.debug span{float:left;font-style:italic;color:#CCC}.vayes-debug-badge{background:#285AA5;border:1px solid rgba(0,0,0,0);border-radius:4px;color:#FFF;padding:2px 4px}.vayes-debug-badge-object{background:#A53C89}.vayes-debug-badge-array{background:#037B5A}.vayes-debug-badge-string{background:#037B5A}.vayes-debug-badge-integer{background:#552EF3}.vayes-debug-badge-true{background:#126F0B}.vayes-debug-badge-false{background:#DE0303}.vayes-debug-badge-null{background:#383838}.vayes-debug-badge-float{background:#9E4E09}p.debugp.debugbold.debutextR.lq-trigger:hover + code{display:block}</style>';

        $output .= '<div class="debugbody"><div class="debugcontainer">';
        $output .= '<h1 class="debugheader">'.$varname.'</h1>';
        $output .= '<div class="debugcontent">';
        $output .= '<code class="debugcode"><p class="debugp debugbold debutextR">:: print_r</p><pre class="debugpre">'.$message;
        ob_start();
        print_r($data);
        $output .= "\n\n".trim(ob_get_clean());
        $output .= '</pre></code>';

        if ($add_var_dump) {
            $output .= '<code class="debugcode"><p class="debugp debugbold debutextR">:: var_dump</p><pre class="debugpre">';
            ob_start();
            var_dump($data);
            $vardump = trim(ob_get_clean());
            $vardump = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $vardump);
            $output .=  $vardump;
            $output .= '</pre></code>';
        }

        if ($add_last_query) {
            if ($CI->db->last_query()) {
                $output .= '<p class="debugp debugbold debutextR lq-trigger">Show Last Query</p>';
                $output .= '<code class="debugcode debug-last-query"><p class="debugp debugbold debutextR">:: $CI->db->last_query()</p>';
                $output .= $CI->db->last_query();
                $output .= '</code>';
            }
        }


        $output .= '</div><p class="debugfooter">Vayes Debug Helper © Yahya A. Erturan</p></div></div>';
        $output .= '<div style="clear:both;"></div>';

        if (PHP_SAPI == 'cli') {
            echo $varname . ' = ' . PHP_EOL . $output . PHP_EOL . PHP_EOL;
            return;
        }

        echo $output;
        if ($die) {
            exit;
        }
    }
}

function getTokenClaimAss(){
    $CI = get_instance();
    $CI->load->model('settings_model');
    $setting = $CI->settings_model->get_first_row();
    $headers = [
        'Content-Type' => 'application/json',
    ];
    $body = [
        'email' => $setting->user_name_cl_ass,
        'password' => $setting->password_cl_ass,
    ];
    

    $startTime = Carbon::parse($setting->updated_at);
    $now = Carbon::now();
    $totalDuration = $startTime->diffInSeconds($now);
    if($setting->token_cl_ass == null || $totalDuration >= 3500){
        $client = new \GuzzleHttp\Client([
            'headers' => $headers
        ]);
        $response = $client->request("POST", $setting->url_cl_ass .'api/login' , ['form_params'=>$body]);
        $response =  json_decode($response->getBody()->getContents());
        $token = $response->data->access_token;
        $CI->settings_model->update_token($token);
        return  $token;
    }
    return  $setting->token_cl_ass;
}

function removeFormatPrice($string) 
{
    if (empty($string)) {
        return $string;
    }
    $pattern = '/[^0-9]+/';
    $string  = preg_replace($pattern, "", $string);
    return $string;
}

function number_to_words($number)
{
    return ucfirst(str_replace(['tỷ', 'triệu', 'nghìn'], ['tỷ,', 'triệu,', 'ngàn,'], (new NumberFormatter('vi', NumberFormatter::SPELLOUT))->format($number))) . ' đồng';
}

function truncateString($str, $maxChars = 40, $holder = "...")
{
    if (strlen($str) > $maxChars) {
        return trim(substr($str, 0, $maxChars)) . $holder;
    } else {
        return $str;
    }
}