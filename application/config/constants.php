<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

/*
|--------------------------------------------------------------------------
| ROLE
|--------------------------------------------------------------------------
*/
define('ROLE_IT', 1);
define('ROLE_FI', 11);
define('ROLE_CL_USER', 21);
define('ROLE_CL_LEADER', 24);
define('ROLE_CL_MANAGER', 26);
define('ROLE_CL_DIRECTOR', 29);
define('ROLE_CS', 31);

/*
|--------------------------------------------------------------------------
| Mantis
|--------------------------------------------------------------------------
*/
define('MANTIS_VIEW', 'https://health-etalk.pacificcross.com.vn/view.php?id=');

/*
|--------------------------------------------------------------------------
| TRANSFER STATUS
|--------------------------------------------------------------------------
*/
// define('TF_STATUS_DEBIT', 0);
define('TF_STATUS_DELETED', 10);
define('TF_STATUS_NEW', 20);
define('TF_STATUS_LEADER_APPROVAL', 30);
// define('TF_STATUS_LEADER_REVIEW', 40);
define('TF_STATUS_LEADER_REJECTED', 50);
define('TF_STATUS_MANAGER_APPROVAL', 60);
// define('TF_STATUS_MANAGER_REVIEW', 70);
define('TF_STATUS_MANAGER_REJECTED', 80);
define('TF_STATUS_DIRECTOR_APPROVAL', 90);
// define('TF_STATUS_DIRECTOR_REVIEW', 100);
define('TF_STATUS_DIRECTOR_REJECTED', 110);

/*
|--------------------------------------------------------------------------
| TRANSFER STATUS - Finance
| TF_STATUS_TRANSFERRING = TF_STATUS_SHEET + 20
| TF_STATUS_TRANSFERRING_PAYPREM = TF_STATUS_SHEET_PAYPREM + 20
| TF_STATUS_TRANSFERRING_DLVN_CANCEL = TF_STATUS_SHEET_DLVN_CANCEL + 20
| TF_STATUS_TRANSFERRING_DLVN_PAYPREM = TF_STATUS_SHEET_DLVN_PAYPREM + 20
|--------------------------------------------------------------------------
*/
define('TF_STATUS_DLVN_CANCEL', 140);
define('TF_STATUS_DLVN_PAYPREM', 145);
define('TF_STATUS_APPROVED', 150);
define('TF_STATUS_SHEET', 160);
define('TF_STATUS_SHEET_PAYPREM', 165);
define('TF_STATUS_SHEET_DLVN_CANCEL', 170);
define('TF_STATUS_SHEET_DLVN_PAYPREM', 175);
define('TF_STATUS_TRANSFERRING', 180);
define('TF_STATUS_TRANSFERRING_PAYPREM', 185);
define('TF_STATUS_TRANSFERRING_DLVN_CANCEL', 190);
define('TF_STATUS_TRANSFERRING_DLVN_PAYPREM', 195);
define('TF_STATUS_TRANSFERRED', 200);
define('TF_STATUS_TRANSFERRED_PAYPREM', 205);
define('TF_STATUS_TRANSFERRED_DLVN_CANCEL', 210);
define('TF_STATUS_TRANSFERRED_DLVN_PAYPREM', 215);
define('TF_STATUS_RETURNED_TO_CLAIM', 216);
define('TF_STATUS_DLVN_CLOSED', 220);

define('CL_STATUSES', array(TF_STATUS_NEW, TF_STATUS_LEADER_APPROVAL, TF_STATUS_LEADER_REJECTED, TF_STATUS_MANAGER_APPROVAL, TF_STATUS_MANAGER_REJECTED, TF_STATUS_DIRECTOR_APPROVAL, TF_STATUS_DIRECTOR_REJECTED, TF_STATUS_RETURNED_TO_CLAIM));

/*
|--------------------------------------------------------------------------
| HISTORY
|--------------------------------------------------------------------------
*/
define('ADMIN', 'admin');

/*
|--------------------------------------------------------------------------
| HISTORY TYPE
|--------------------------------------------------------------------------
*/
define('HIST_TYPE_DEDUCT', 1);
define('HIST_TYPE_DISCOUNT', 2);
define('HIST_TYPE_ASSIGN', 3);
define('HIST_TYPE_HOLD', 4);
define('HIST_TYPE_UNHOLD', 5);
define('HIST_TYPE_DELETE', 6);
define('HIST_TYPE_UNDELETE', 7);
define('HIST_TYPE_REQUEST_LEADER', 8);
define('HIST_TYPE_REQUEST_MANAGER', 9);
define('HIST_TYPE_REQUEST_DIRECTOR', 10);
define('HIST_TYPE_REQUEST_FINANCE', 11);
define('HIST_TYPE_CANCEL_LEADER', 12);
define('HIST_TYPE_CANCEL_MANAGER', 13);
define('HIST_TYPE_CANCEL_DIRECTOR', 14);
define('HIST_TYPE_LEADER_REJECT', 15);
define('HIST_TYPE_MANAGER_REJECT', 16);
define('HIST_TYPE_DIRECTOR_REJECT', 17);
define('HIST_TYPE_USER_TAKE_OVER', 18);
define('HIST_TYPE_LEADER_TAKE_OVER', 19);
define('HIST_TYPE_MANAGER_TAKE_OVER', 20);
define('HIST_TYPE_DIRECTOR_TAKE_OVER', 21);
define('HIST_TYPE_SYSTEM_DELETE', 22);
define('HIST_TYPE_SYSTEM_UPDATE', 23);





define('HIST_TYPE_FI_SELECT', 26);
define('HIST_TYPE_FI_CANCEL', 27);
define('HIST_TYPE_FI_RETURN_CL', 28);
define('HIST_TYPE_FI_EJECT', 29);
define('HIST_TYPE_FI_CLOSE', 30);
define('HIST_TYPE_FI_OPEN', 31);
define('HIST_TYPE_FI_UPLOAD', 32);
define('HIST_TYPE_FI_PAY', 33);
define('HIST_TYPE_FI_UNPAY', 34);
define('HIST_TYPE_FI_REFUND', 35);
define('HIST_TYPE_FI_SELECT_PARTNER', 36);
define('HIST_TYPE_FI_PARTNER_REQUEST', 37);
define('HIST_TYPE_FI_REPAY', 38);
define('HIST_TYPE_FI_DO_NOT_PAY', 39);
define('HIST_TYPE_FI_PAY_REPAID', 40);
define('HIST_TYPE_FI_UPLOAD_APPROVED', 41);
define('HIST_TYPE_API_SEND_PAYMENT', 42);
define('HIST_TYPE_CHANGE_SHEET', 43);
define('HIST_TYPE_FI_RENEW_CL', 44);

/*
|--------------------------------------------------------------------------
| HISTORY FIELD
|--------------------------------------------------------------------------
*/
define('HIST_FIELD_TF_AMT', 1);
define('HIST_FIELD_DEDUCT_AMT', 2);
define('HIST_FIELD_TF_STATUS', 3);
define('HIST_FIELD_CL_USER', 4);


define('HIST_FIELD_TF_DATE', 7);
define('HIST_FIELD_SHEET_ID', 8);
define('HIST_FIELD_VCB_SEQ', 9);
define('HIST_FIELD_YN_CLBO', 10);
define('HIST_FIELD_ACCT_NAME', 11);
define('HIST_FIELD_ACCT_NO', 12);
define('HIST_FIELD_BANK_NAME', 13);
define('HIST_FIELD_BANK_BRANCH', 14);
define('HIST_FIELD_BANK_CITY', 15);
define('HIST_FIELD_BENEFICIARY_NAME', 16);
define('HIST_FIELD_PP_NO', 17);
define('HIST_FIELD_PP_DATE', 18);
define('HIST_FIELD_PP_PLACE', 19);

define('HIST_FIELD_DISC_AMT', 21);
define('HIST_FIELD_APP_DATE', 22);

/*
|--------------------------------------------------------------------------
| NOTE TYPE
|--------------------------------------------------------------------------
*/
define('NOTE_TYPE_CANCEL_LEADER', 1);
define('NOTE_TYPE_CANCEL_MANAGER', 2);
define('NOTE_TYPE_CANCEL_DIRECTOR', 3);
define('NOTE_TYPE_LEADER_REJECT', 4);
define('NOTE_TYPE_MANAGER_REJECT', 5);
define('NOTE_TYPE_DIRECTOR_REJECT', 6);
define('NOTE_TYPE_USER_TAKE_OVER', 7);
define('NOTE_TYPE_LEADER_TAKE_OVER', 8);
define('NOTE_TYPE_MANAGER_TAKE_OVER', 9);
define('NOTE_TYPE_DIRECTOR_TAKE_OVER', 10);
define('NOTE_TYPE_RETURN', 11);

/*
|--------------------------------------------------------------------------
| Vietcombank VNBT Sheet
|--------------------------------------------------------------------------
*/
define('VCB_VNBT', array(
    'A1' => 'Bảng liệt kê giao dịch trả lương',
    'A2' => 'Ngày: ',
    'A4' => 'Số REF của bảng kê: ',
    'N4' => 'Ngày in bảng kê: ',
    'A5' => 'Tên bảng kê: ',
    'A6' => 'Số TK trích nợ: ',
    'A7' => 'Số lệnh: ',
    'A8' => 'Tổng tiền: ',
    'E8' => 'Tổng phí: ',
    'I8' => 'Tổng VAT: ',
    'N8' => 'Trạng thái: ',
    'A10:N10' => array(
        'A' => 'STT',
        'B' => 'Số TK hưởng',
        'C' => 'Tên TK hưởng',
        'D' => 'Ngân hàng hưởng',
        'E' => 'Số CMND',
        'F' => 'Nơi cấp',
        'G' => 'Ngày cấp',
        'H' => 'Số tiền',
        'I' => 'Phí',
        'J' => 'VAT',
        'K' => 'Teller ID',
        'L' => 'Seq',
        'M' => 'Ngày',
        'N' => 'Trạng thái'
    )
));

/*
|--------------------------------------------------------------------------
| SHEET TYPE
|--------------------------------------------------------------------------
*/
define('SHEET_TYPE_CLAIMANT', 0);
define('SHEET_TYPE_PARTNER', 1);

/*
|--------------------------------------------------------------------------
| SHEET STATUS
|--------------------------------------------------------------------------
*/
define('SHEET_STATUS_SHEET', 0);
define('SHEET_STATUS_TRANSFERRING', 1);
define('SHEET_STATUS_TRANSFERRED', 2);

/*
|--------------------------------------------------------------------------
| CLAIM BORDEREAUX TYPE
|--------------------------------------------------------------------------
*/
define('CLBO_TYPE_PAYMENT', 0);
define('CLBO_TYPE_DEDUCTION', 1);
define('CLBO_TYPE_CLOSED_PAYMENT', 2);
define('CLBO_TYPE_CLOSED_DEDUCTION', 3);
define('CLBO_TYPE_ADJUST_DECREASE', 4);

/*
|--------------------------------------------------------------------------
| CLAIM FUND TYPE
|--------------------------------------------------------------------------
*/
define('CLFU_TYPE_CLAIM_PAYMENT', 0);
define('CLFU_TYPE_REFUND', 1);
define('CLFU_TYPE_REPLENISHMENT', 2);
define('CLFU_TYPE_REPAID_REFUND', 3);
define('CLFU_TYPE_ADJUST_CLOSED', 4);
define('CLFU_TYPE_ADJUST_DECREASE', 5);
define('CLFU_TYPE_ADJUST_REPAID_REFUND', 6);

/*
|--------------------------------------------------------------------------
| DEBT TYPE
|--------------------------------------------------------------------------
*/
define('DEBT_TYPE_DEBT', 1);
define('DEBT_TYPE_PAID_DEDUCT', 2);
define('DEBT_TYPE_PAID_DIRECT', 3);
define('DEBT_TYPE_PCV_EXPENSE', 4);

/*
|--------------------------------------------------------------------------
| MANTIS STATUS
|--------------------------------------------------------------------------
*/
define('MANTIS_STATUS_CLOSED', 90);
define('MANTIS_STATUS_DECLINED', 13);

/*
|--------------------------------------------------------------------------
| MANTIS PROJECT
|--------------------------------------------------------------------------
*/
define('MANTIS_PROJECT_CL_GOP', 5);

/*
|--------------------------------------------------------------------------
| VCBSHEET
|--------------------------------------------------------------------------
*/
define('MAX_LENGTH_PAYM_IDS', 4000);


/*
|--------------------------------------------------------------------------
| TRANSFERRED SEARCH
|--------------------------------------------------------------------------
*/
define('MAX_RESULT', 1000);

/*
|--------------------------------------------------------------------------
| UNC BANK GROUP
|--------------------------------------------------------------------------
*/
define('NO_SIGN', 0);
define('WAIT_SIGN', 1);
define('SIGNED', 2);