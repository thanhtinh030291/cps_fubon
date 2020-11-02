<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
//_______________________________________________
// REST API
$route['api/client_debit/(:num)'] = 'api/client_debit/$1';
$route['api/token'] = 'api/token';
$route['api/send_unc'] = 'api/send_unc';
$route['api/get_renewed_claim'] = 'api/get_renewed_claim';
$route['api/get_payments'] = 'api/get_payments';


//_______________________________________________
// Reports management
$route['reports'] = 'reports';

//_______________________________________________
// Sheet management
$route['transferring'] = 'sheets/transferring';
$route['grouped_sheets'] = 'sheets/grouped_sheets';
$route['sheets'] = 'sheets';

//_______________________________________________
// ... management
$route['cs'] = 'payments/cs';
$route['claim_bordereaux'] = 'payments/claim_bordereaux';
$route['returned_to_dlvn'] = 'payments/returned_to_dlvn';
$route['returned_to_claim'] = 'payments/returned_to_claim';
$route['transferred'] = 'payments/transferred';
$route['claim_fund'] = 'payments/fi_claim_fund';
$route['claim_fund_old'] = 'payments/fi_claim_fund_old';
$route['finance'] = 'payments/finance';
$route['manager'] = 'payments/manager';
$route['unassigned'] = 'payments/unassigned';

//_______________________________________________
// Foreigner management
$route['foreigner/delete'] = 'foreigner/delete';
$route['foreigner/edit'] = 'foreigner/edit';
$route['foreigner/add'] = 'foreigner/add';
$route['foreigner'] = 'foreigner';

//_______________________________________________
// Finance management
$route['confirm_upload'] = 'FI/confirm_upload';
$route['approved_upload'] = 'FI/approved_upload';
$route['partner_payprem'] = 'FI/partner_request/' . TF_STATUS_DLVN_PAYPREM;
$route['partner_cancel'] = 'FI/partner_request/' . TF_STATUS_DLVN_CANCEL;
$route['returned_claim_repay'] = 'FI/returned_claim_repay';
$route['add_client_paid'] = 'FI/add_client_paid';
$route['add_client_debit'] = 'FI/add_client_debit';
$route['set_expense'] = 'FI/set_expense';
$route['decrease_transferred'] = 'FI/decrease_transferred';
$route['fi/pay_repaid'] = 'FI/pay_repaid';
$route['fi/do_not_pay'] = 'FI/do_not_pay';
$route['fi/set_enbal'] = 'FI/set_enbal';
$route['fi/set_bebal'] = 'FI/set_bebal';
$route['fi/repay'] = 'FI/repay';
$route['fi/transferred_sheets'] = 'FI/transferred_sheets';
$route['fi/replenish'] = 'FI/replenish';
$route['fi/claim_bordereaux'] = 'FI/claim_bordereaux';
$route['fi/claim_fund'] = 'FI/claim_fund';
$route['fi/partner/sheets'] = 'FI/sheets_partner';
$route['fi/partner'] = 'FI/partner';
$route['fi/transferred'] = 'FI/transferred';
$route['fi/unpay'] = 'FI/unpay';
$route['fi/pay'] = 'FI/pay';
$route['fi/transferring_pay'] = 'FI/transferring_pay';
$route['fi/transferring'] = 'FI/transferring';
$route['fi/upload'] = 'FI/upload';
$route['fi/reopen'] = 'FI/reopen';
$route['fi/vcbsheet/(:num)'] = 'FI/vcbsheet/$1';
$route['fi/close'] = 'FI/close';
$route['fi/undo'] = 'FI/undo';
$route['fi/eject'] = 'FI/eject';
$route['fi/sheet_return'] = 'FI/sheet_return';
$route['fi/sheet/(:num)'] = 'FI/sheet/$1';
$route['fi/edit'] = 'FI/edit';
$route['fi/sheets'] = 'FI/sheets';
$route['fi/cancel/(:num)'] = 'FI/cancel/$1';
$route['fi/returned_to_claim'] = 'FI/returned_claim';
$route['fi/returned_claim'] = 'FI/returned_claim';
$route['fi/refund_upload'] = 'FI/refund_upload';
$route['fi/refund'] = 'FI/refund';
$route['return_to_claim'] = 'FI/return_to_claim';
$route['renew_to_claim'] = 'FI/renew_to_claim';
$route['fi/renewed_claim'] = 'FI/renewed_claim';
$route['confirm_partner'] = 'FI/confirm_partner';
$route['confirm'] = 'FI/confirm';
$route['fi/update_bank_info'] = 'FI/update_bank_info';
$route['fi/approved/(:any)'] = 'FI/approved/$1';
$route['change_sheet'] = 'FI/change_sheet';
$route['fi'] = 'FI';
$route['fi/bank_request_form'] = 'FI/bank_request_form';

//_______________________________________________
// Claim Team management
$route['team'] = 'Team';

//_______________________________________________
// Claim Director management
$route['cldirectors/undo/(:num)'] = 'CL_Directors/undo/$1';
$route['cldirectors/reject/(:num)'] = 'CL_Directors/reject/$1';
$route['cldirectors/rejected/(:any)'] = 'CL_Directors/rejected/$1';
$route['cldirectors/review/(:num)'] = 'CL_Directors/review/$1';
$route['cldirectors/reviewed/(:any)'] = 'CL_Directors/reviewed/$1';
$route['cldirectors/approved'] = 'CL_Managers/approved';
$route['cldirectors/unapprove/(:num)'] = 'CL_Directors/unapprove/$1';
$route['cldirectors/approve/(:num)'] = 'CL_Directors/approve/$1';
$route['cldirectors/approval/(:any)'] = 'CL_Directors/approval/$1';

$route['cldirectors/director_rejected/(:any)/(:any)'] = 'CL_Directors/director_rejected/$1/$2';
$route['cldirectors/director_rejected/(:any)'] = 'CL_Directors/director_rejected/$1';
$route['cldirectors/director_rejected'] = 'CL_Directors/director_rejected';
$route['cldirectors/manager_rejected/(:any)/(:any)'] = 'CL_Directors/manager_rejected/$1/$2';
$route['cldirectors/manager_rejected/(:any)'] = 'CL_Directors/manager_rejected/$1';
$route['cldirectors/manager_rejected'] = 'CL_Directors/manager_rejected';
$route['cldirectors/leader_rejected/(:any)/(:any)'] = 'CL_Directors/leader_rejected/$1/$2';
$route['cldirectors/leader_rejected/(:any)'] = 'CL_Directors/leader_rejected/$1';
$route['cldirectors/leader_rejected'] = 'CL_Directors/leader_rejected';
$route['cldirectors/manager_approval/(:any)/(:any)'] = 'CL_Directors/manager_approval/$1/$2';
$route['cldirectors/manager_approval/(:any)'] = 'CL_Directors/manager_approval/$1';
$route['cldirectors/manager_approval'] = 'CL_Directors/manager_approval';
$route['cldirectors/leader_approval/(:any)/(:any)'] = 'CL_Directors/leader_approval/$1/$2';
$route['cldirectors/leader_approval/(:any)'] = 'CL_Directors/leader_approval/$1';
$route['cldirectors/leader_approval'] = 'CL_Directors/leader_approval';
$route['cldirectors/new/(:any)/(:any)'] = 'CL_Directors/new_payments/$1/$2';
$route['cldirectors/new/(:any)'] = 'CL_Directors/new_payments/$1';
$route['cldirectors/new'] = 'CL_Directors/new_payments';
$route['cldirectors/director_approval/(:any)/(:any)'] = 'CL_Directors/director_approval/$1/$2';
$route['cldirectors/director_approval/(:any)'] = 'CL_Directors/director_approval/$1';
$route['cldirectors/director_approval'] = 'CL_Directors/director_approval';
$route['cldirectors/director_reject/(:num)'] = 'CL_Directors/director_reject/$1';
$route['cldirectors/request_finance/(:num)'] = 'CL_Directors/request_finance/$1';
$route['cldirectors'] = 'CL_Directors';

//_______________________________________________
// Claim Manager management
$route['clmanagers/undo/(:num)'] = 'CL_Managers/undo/$1';
$route['clmanagers/reject/(:num)'] = 'CL_Managers/reject/$1';
$route['clmanagers/review/(:num)'] = 'CL_Managers/review/$1';
$route['clmanagers/unapprove/(:num)'] = 'CL_Managers/unapprove/$1';
$route['clmanagers/approved'] = 'CL_Managers/approved';
$route['clmanagers/director_approval'] = 'CL_Managers/director_approval';
$route['clmanagers/send_finance/(:num)'] = 'CL_Managers/send_finance/$1';
$route['clmanagers/send_director/(:num)'] = 'CL_Managers/send_director/$1';
$route['clmanagers/rejected/(:any)'] = 'CL_Managers/rejected/$1';
$route['clmanagers/reviewed/(:any)'] = 'CL_Managers/reviewed/$1';

$route['clmanagers/director_approval/(:any)/(:any)'] = 'CL_Managers/director_approval/$1/$2';
$route['clmanagers/director_approval/(:any)'] = 'CL_Managers/director_approval/$1';
$route['clmanagers/director_approval'] = 'CL_Managers/director_approval';
$route['clmanagers/director_rejected/(:any)/(:any)'] = 'CL_Managers/director_rejected/$1/$2';
$route['clmanagers/director_rejected/(:any)'] = 'CL_Managers/director_rejected/$1';
$route['clmanagers/director_rejected'] = 'CL_Managers/director_rejected';
$route['clmanagers/manager_rejected/(:any)/(:any)'] = 'CL_Managers/manager_rejected/$1/$2';
$route['clmanagers/manager_rejected/(:any)'] = 'CL_Managers/manager_rejected/$1';
$route['clmanagers/manager_rejected'] = 'CL_Managers/manager_rejected';
$route['clmanagers/leader_rejected/(:any)/(:any)'] = 'CL_Managers/leader_rejected/$1/$2';
$route['clmanagers/leader_rejected/(:any)'] = 'CL_Managers/leader_rejected/$1';
$route['clmanagers/leader_rejected'] = 'CL_Managers/leader_rejected';
$route['clmanagers/leader_approval/(:any)/(:any)'] = 'CL_Managers/leader_approval/$1/$2';
$route['clmanagers/leader_approval/(:any)'] = 'CL_Managers/leader_approval/$1';
$route['clmanagers/leader_approval'] = 'CL_Managers/leader_approval';
$route['clmanagers/new/(:any)/(:any)'] = 'CL_Managers/new_payments/$1/$2';
$route['clmanagers/new/(:any)'] = 'CL_Managers/new_payments/$1';
$route['clmanagers/new'] = 'CL_Managers/new_payments';
$route['clmanagers/manager_approval/(:any)/(:any)'] = 'CL_Managers/manager_approval/$1/$2';
$route['clmanagers/manager_approval/(:any)'] = 'CL_Managers/manager_approval/$1';
$route['clmanagers/manager_approval'] = 'CL_Managers/manager_approval';
$route['clmanagers/manager_reject/(:num)'] = 'CL_Managers/manager_reject/$1';
$route['clmanagers/request_finance/(:num)'] = 'CL_Managers/request_finance/$1';
$route['clmanagers/request_director/(:num)'] = 'CL_Managers/request_director/$1';
$route['clmanagers'] = 'CL_Managers';

//_______________________________________________
// Claim Team Leader management
$route['clleaders/rejected/(:any)'] = 'CL_Leaders/rejected/$1';
$route['clleaders/reviewed/(:any)'] = 'CL_Leaders/reviewed/$1';
$route['clleaders/undo/(:num)'] = 'CL_Leaders/undo/$1';
$route['clleaders/reject/(:num)'] = 'CL_Leaders/reject/$1';
$route['clleaders/review/(:num)'] = 'CL_Leaders/review/$1';
$route['clleaders/unapprove/(:num)'] = 'CL_Leaders/unapprove/$1';
$route['clleaders/approve/(:num)'] = 'CL_Leaders/approve/$1';
$route['clleaders/change_claim_user/(:num)'] = 'CL_Leaders/change_claim_user/$1';

$route['clleaders/director_approval/(:any)'] = 'CL_Leaders/director_approval/$1';
$route['clleaders/director_approval'] = 'CL_Leaders/director_approval';
$route['clleaders/manager_approval/(:any)'] = 'CL_Leaders/manager_approval/$1';
$route['clleaders/manager_approval'] = 'CL_Leaders/manager_approval';
$route['clleaders/director_rejected/(:any)'] = 'CL_Leaders/director_rejected/$1';
$route['clleaders/director_rejected'] = 'CL_Leaders/director_rejected';
$route['clleaders/manager_rejected/(:any)'] = 'CL_Leaders/manager_rejected/$1';
$route['clleaders/manager_rejected'] = 'CL_Leaders/manager_rejected';
$route['clleaders/leader_rejected/(:any)'] = 'CL_Leaders/leader_rejected/$1';
$route['clleaders/leader_rejected'] = 'CL_Leaders/leader_rejected';
$route['clleaders/new/(:any)'] = 'CL_Leaders/new_payments/$1';
$route['clleaders/new'] = 'CL_Leaders/new_payments';
$route['clleaders/leader_approval/(:any)'] = 'CL_Leaders/leader_approval/$1';
$route['clleaders/leader_approval'] = 'CL_Leaders/leader_approval';
$route['clleaders/leader_reject/(:num)'] = 'CL_Leaders/leader_reject/$1';
$route['clleaders/request_manager/(:num)'] = 'CL_Leaders/request_manager/$1';
$route['clleaders'] = 'CL_Leaders';

//_______________________________________________
// Claim User management
$route['clusers/hold/(:num)'] = 'CL_Users/hold/$1';
$route['clusers/unfeedback/(:num)'] = 'CL_Users/unfeedback/$1';
$route['clusers/feedback/(:num)'] = 'CL_Users/feedback/$1';
$route['clusers/undelete/(:num)'] = 'CL_Users/undelete/$1';
$route['clusers/del/(:num)'] = 'CL_Users/del/$1';
$route['clusers/unrequest/(:num)'] = 'CL_Users/unrequest/$1';
$route['clusers/request/(:num)'] = 'CL_Users/request/$1';



$route['clusers/deleted'] = 'CL_Users/deleted';
$route['clusers/returned'] = 'CL_Users/returned_payments';

$route['clusers/director_approval'] = 'CL_Users/director_approval';
$route['clusers/manager_approval'] = 'CL_Users/manager_approval';
$route['clusers/leader_approval'] = 'CL_Users/leader_approval';
$route['clusers/director_rejected'] = 'CL_Users/director_rejected';
$route['clusers/manager_rejected'] = 'CL_Users/manager_rejected';
$route['clusers/leader_rejected'] = 'CL_Users/leader_rejected';
$route['clusers/new'] = 'CL_Users/new_payments';
$route['clusers'] = 'CL_Users';

//_______________________________________________
// Payment management
$route['payments/leader_reject/(:num)'] = 'payments/leader_reject/$1';
$route['payments/leader_review/(:num)'] = 'payments/leader_review/$1';
$route['payments/director_unrequest/(:num)'] = 'payments/director_unrequest/$1';
$route['payments/director_request/(:num)'] = 'payments/director_request/$1';
$route['payments/manager_unrequest_director/(:num)'] = 'payments/manager_unrequest_director/$1';
$route['payments/manager_request_director/(:num)'] = 'payments/manager_request_director/$1';
$route['payments/manager_unrequest_finance/(:num)'] = 'payments/manager_unrequest_finance/$1';
$route['payments/manager_request_finance/(:num)'] = 'payments/manager_request_finance/$1';
$route['payments/leader_unrequest/(:num)'] = 'payments/leader_unrequest/$1';

$route['payments/director_take_over/(:num)'] = 'CL_Directors/take_over/$1';
$route['payments/manager_take_over/(:num)'] = 'CL_Managers/take_over/$1';
$route['payments/leader_take_over/(:num)'] = 'CL_Leaders/take_over/$1';
$route['payments/user_take_over/(:num)'] = 'CL_Users/take_over/$1';
$route['payments/cancel_director/(:num)'] = 'payments/cancel_director/$1';
$route['payments/cancel_manager/(:num)'] = 'payments/cancel_manager/$1';
$route['payments/cancel_leader/(:num)'] = 'payments/cancel_leader/$1';
$route['payments/request_director/(:num)'] = 'payments/request_director/$1';
$route['payments/request_manager/(:num)'] = 'payments/request_manager/$1';
$route['payments/request_leader/(:num)'] = 'payments/request_leader/$1';
$route['payments/undelete/(:num)'] = 'payments/undelete/$1';
$route['payments/delete/(:num)'] = 'payments/delete/$1';
$route['payments/unhold/(:num)'] = 'payments/unhold/$1';
$route['payments/hold/(:num)'] = 'payments/hold/$1';
$route['payments/change_user/(:num)'] = 'payments/change_cl_user/$1';
$route['payments/discount/(:num)'] = 'payments/discount/$1';
$route['payments/deduct/(:num)'] = 'payments/deduct/$1';
$route['payments/(:num)'] = 'payments/index/$1';

//_______________________________________________
// Connect from external using Mantis ID
$route['mantis/(:num)'] = 'payments/mantis/$1';

//_______________________________________________
// Session management
$route['session/login'] = 'connection/login';
$route['session/logout'] = 'connection/logout';

//_______________________________________________
// Admin : user management
$route['users/edit/(:num)'] = 'users/edit/$1';
$route['users'] = 'users';

//_______________________________________________
// Home
$route['home'] = 'home';

//UNC Banker
$route['banker'] = 'Banker';
$route['banker/add_unc_index'] = 'Banker/add_unc_index';
$route['banker/add_unc'] = 'Banker/add_unc';
$route['banker/update_unc'] = 'Banker/update_unc';
$route['banker/export_pdf_sign'] = 'Banker/export_pdf_sign';

$route['banker/add_unc_code_index'] = 'Banker/add_unc_code_index';
$route['banker/add_unc_code'] = 'Banker/add_unc_code';


$route['groupbanker'] = 'GroupBanker';
$route['groupbanker/request_signing'] = 'GroupBanker/request_signing';

//_______________________________________________
// Default controllers
$route['default_controller'] = 'home';
$route['forbidden'] = 'home/forbidden';
$route['404_override'] = 'home/notfound';
