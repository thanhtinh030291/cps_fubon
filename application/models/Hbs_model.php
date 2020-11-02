<?php
/**
 * This Class contains all the business logic and the persistence layer which connection to HBS Server is required
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Hbs_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Connect to HBS Database
     * @return database object
     */
    public function connect_hbs_db()
    {
        $hbs_db = $this->load->database("hbs_dlvn", TRUE);
        $error = $hbs_db->error();
        if ( ! empty($error['code']))
        {
            return FALSE;
        }
        return $hbs_db;
    }
    
    /**
     * get claim payment information of a bug id from hbs
     * @param $hbs_db database object 
     * @param $bug_id int 
     */
    public function get_claim_payment($hbs_db, $bug_id)
    {
        $bug_id = sprintf("%'.07d", $bug_id);
        $query = $hbs_db->query(
            "SELECT
                clam.CL_NO,
                fn_get_sys_code(max(vwclli.scma_oid_cl_payment_method)) as PAYMENT_METHOD,
                max(vwclli.acct_name) as ACCT_NAME,
                max(vwclli.acct_no) as ACCT_NO,
                max(vwclli.bank_name) as BANK_NAME,
                max(vwclli.bank_city) as BANK_CITY,
                max(vwclli.bank_branch) as BANK_BRANCH,
                max(vwclli.beneficiary_name) as BENEFICIARY_NAME,
                to_char(max(vwclli.id_passport_date_of_issue), 'yyyy-mm-dd') as PP_DATE,
                max(vwclli.id_passport_no) as PP_NO,
                max(vwclli.id_passport_issue_place) as PP_PLACE,
                max(memb.mbr_last_name || ' ' || memb.mbr_first_name) as MEMB_NAME,
                max(pocy.pocy_ref_no) as POCY_REF_NO,
                max(memb.memb_ref_no) as MEMB_REF_NO,
                sum(vwclli.pres_amt) as PRES_AMT,
                sum(vwclli.app_amt) as APP_AMT,
                max(bent.ben_type) as BEN_TYPE,
                fn_get_sys_code(max(vwclli.scma_oid_cl_type)) as CL_TYPE,
                max(prov2.prov_name) as PROV_NAME,
                to_char(max(vwclli.rcv_date), 'yyyy-mm-dd') as RCV_DATE,
                to_char(max(vwclli.pay_date), 'yyyy-mm-dd') as APP_DATE,
                max(vwclli.payee) as PAYEE,
                max(invno.inv_no) as INV_NO
            FROM
                cl_claim clam
                JOIN (
                    SELECT *
                    FROM vw_cl_txn_line
                    WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    AND pay_date IS NOT NULL
                ) vwclli ON clam.clam_oid = vwclli.clam_oid
                JOIN mr_member memb ON vwclli.memb_oid = memb.memb_oid
                JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(inv_no, ',') WITHIN GROUP (ORDER BY inv_no) inv_no
                    FROM (
                        SELECT DISTINCT clam_oid, inv_no
                        FROM cl_line
                        WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) invno ON clam.clam_oid = invno.clam_oid
                LEFT JOIN mr_policy_plan popl ON vwclli.popl_oid = popl.popl_oid
                LEFT JOIN mr_policy pocy ON popl.pocy_oid = pocy.pocy_oid
                LEFT JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(TO_CHAR(prov_name), ',') WITHIN GROUP (ORDER BY prov_name) prov_name
                    FROM (
                        SELECT DISTINCT
                            clam_oid,
                            NVL(prov.prov_name, clli.prov_name) prov_name
                        FROM cl_line clli LEFT JOIN pv_provider prov ON prov.prov_oid = clli.prov_oid
                        WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) prov2 ON clam.clam_oid = prov2.clam_oid
                LEFT JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(TO_CHAR(ben_type), ',') WITHIN GROUP (ORDER BY ben_type) ben_type
                    FROM (
                        SELECT DISTINCT
                            clam_oid,
                            FN_GET_SYS_CODE(scma_oid_ben_type) ben_type
                        FROM
                            cl_line clli,
                            pd_ben_head behd
                        WHERE
                            clli.behd_oid = behd.behd_oid
                            AND scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) bent ON clam.clam_oid = bent.clam_oid
            WHERE
                (clam.scma_oid_cl_status = 'CL_STATUS_FC' OR clam.scma_oid_cl_status = 'CL_STATUS_PC')
                AND clam.barcode = '$bug_id'
            GROUP BY clam.cl_no"
        );
        return $query->row_array();
    }
    
    /**
     * get claim payment information of 1000 bug ids from hbs
     * @param $hbs_db database object 
     * @param $bug_ids array maximum 1000 bug ids
     */
    public function get_claim_payment_chunk($hbs_db , $claims)
    {
        $claims = "'".implode("', '", $claims)."'";
        return $hbs_db->query(
            "SELECT
                clam.CL_NO,
                max(clam.barcode) as MANTIS_ID,
                fn_get_sys_code(max(vwclli.scma_oid_cl_payment_method)) as PAYMENT_METHOD,
                max(vwclli.acct_name) as ACCT_NAME,
                max(vwclli.acct_no) as ACCT_NO,
                max(vwclli.bank_name) as BANK_NAME,
                max(vwclli.bank_city) as BANK_CITY,
                max(vwclli.bank_branch) as BANK_BRANCH,
                max(vwclli.beneficiary_name) as BENEFICIARY_NAME,
                to_char(max(vwclli.id_passport_date_of_issue), 'yyyy-mm-dd') as PP_DATE,
                max(vwclli.id_passport_no) as PP_NO,
                max(vwclli.id_passport_issue_place) as PP_PLACE,
                max(memb.mbr_last_name || ' ' || memb.mbr_first_name) as MEMB_NAME,
                max(pocy.pocy_ref_no) as POCY_REF_NO,
                max(memb.memb_ref_no) as MEMB_REF_NO,
                sum(vwclli.pres_amt) as PRES_AMT,
                sum(vwclli.app_amt) as APP_AMT,
                max(bent.ben_type) as BEN_TYPE,
                fn_get_sys_code(max(vwclli.scma_oid_cl_type)) as CL_TYPE,
                max(prov2.prov_name) as PROV_NAME,
                to_char(max(vwclli.rcv_date), 'yyyy-mm-dd') as RCV_DATE,
                to_char(max(vwclli.pay_date), 'yyyy-mm-dd') as APP_DATE,
                max(vwclli.payee) as PAYEE,
                max(invno.inv_no) as INV_NO
            FROM
                cl_claim clam
                JOIN (
                    SELECT *
                    FROM vw_cl_txn_line
                    WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    AND pay_date IS NOT NULL
                ) vwclli ON clam.clam_oid = vwclli.clam_oid
                JOIN mr_member memb ON vwclli.memb_oid = memb.memb_oid
                JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(inv_no, ',') WITHIN GROUP (ORDER BY inv_no) inv_no
                    FROM (
                        SELECT DISTINCT clam_oid, inv_no
                        FROM cl_line
                        WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) invno ON clam.clam_oid = invno.clam_oid
                LEFT JOIN mr_policy_plan popl ON vwclli.popl_oid = popl.popl_oid
                LEFT JOIN mr_policy pocy ON popl.pocy_oid = pocy.pocy_oid
                LEFT JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(TO_CHAR(prov_name), ',') WITHIN GROUP (ORDER BY prov_name) prov_name
                    FROM (
                        SELECT DISTINCT
                            clam_oid,
                            NVL(prov.prov_name, clli.prov_name) prov_name
                        FROM cl_line clli LEFT JOIN pv_provider prov ON prov.prov_oid = clli.prov_oid
                        WHERE scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) prov2 ON clam.clam_oid = prov2.clam_oid
                LEFT JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(TO_CHAR(ben_type), ',') WITHIN GROUP (ORDER BY ben_type) ben_type
                    FROM (
                        SELECT DISTINCT
                            clam_oid,
                            FN_GET_SYS_CODE(scma_oid_ben_type) ben_type
                        FROM
                            cl_line clli,
                            pd_ben_head behd
                        WHERE
                            clli.behd_oid = behd.behd_oid
                            AND scma_oid_cl_line_status = 'CL_LINE_STATUS_AC'
                    )
                    GROUP BY clam_oid
                ) bent ON clam.clam_oid = bent.clam_oid
            WHERE
                (clam.scma_oid_cl_status = 'CL_STATUS_FC' OR clam.scma_oid_cl_status = 'CL_STATUS_PC')
                AND clam.clam_oid IN ($claims)
            GROUP BY clam.cl_no"
        )->result_array();
    }
    
    /**
     * Get hbs_data of a bug id from HBS: Claim No, Presented Amount, Provider Name, Member Ref No, Member Name
     * @param $hbs_db database object of HBS
     * @param $from_date optional date mm/dd/yyyy
     * @param $to_date optional date mm/dd/yyyy
     */
    public function get_hbs_data($hbs_db, $from_date = null, $to_date = null)
    {
        $sql = "
            SELECT
                CL_NO,
                MAX(barcode) AS BARCODE,
                SUM(clli.pres_amt) AS PRES_AMT, 
                MAX(prov.prov_name) AS PROV_NAME,
                MAX(memb_ref_no) AS MEMB_REF_NO,
                MAX(mbr_last_name) AS MBR_LAST_NAME,
                MAX(mbr_mid_name) AS MBR_MID_NAME,
                MAX(mbr_first_name) AS MBR_FIRST_NAME
            FROM
                cl_claim clam,
                vw_cl_txn_line clli
                LEFT JOIN (
                    SELECT
                        clam_oid,
                        LISTAGG(TO_CHAR(prov_name), ';') WITHIN GROUP (ORDER BY prov_name) prov_name
                    FROM (
                        SELECT DISTINCT
                            clam_oid,
                            prov.prov_name || ' @' || prov.prov_code prov_name
                        FROM vw_cl_txn_line clli LEFT JOIN pv_provider prov ON prov.prov_oid = clli.prov_oid
                        WHERE scma_oid_cl_line_status <> 'CL_LINE_STATUS_RV'
                        AND prov.prov_code <> '000026'
                    )
                    GROUP BY clam_oid
                ) prov ON clli.clam_oid = prov.clam_oid
                JOIN mr_member memb ON memb.memb_oid = clli.memb_oid
            WHERE
                clam.clam_oid = clli.clam_oid
                AND scma_oid_cl_line_status <> 'CL_LINE_STATUS_RV'
                AND barcode IS NOT NULL";
        if ( ! empty($from_date))
        {
            $sql .= " AND trunc(clam.upd_date) >= TO_DATE('$from_date', 'yyyy-mm-dd')";
        }
        if ( ! empty($to_date))
        {
            $sql .= " AND trunc(clam.upd_date) <= TO_DATE('$to_date', 'yyyy-mm-dd')";
        }
        $sql .= " GROUP BY CL_NO";
        return $hbs_db->query($sql)->result_array();
    }
    
    /**
     * Add a new transfer info to hbs_cl_claim table, it will then upload to HBS Server by Task Scheduler
     * @param array $payment
     * @param string $status
     * @param date $tf_date
     */
    public function transferred($payment, $tf_date, $vcb_seq)
    {
        $this->db
            ->set('CL_NO', $payment['CL_NO'])
            ->set('PAYMENT_TIME', $payment['PAYMENT_TIME'])
            ->set('TF_DATE', $tf_date)
            ->set('TF_AMT', $payment['TF_AMT'])
            ->set('TF_REMARK', $vcb_seq)
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('STATUS', 'Transferred')
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('hbs_cl_claim');
    }
    
    /**
     * Add a new transfer info to hbs_cl_claim table, it will then upload to HBS Server by Task Scheduler
     * @param array $payment
     */
    public function cancel($payment)
    {
        $this->db
            ->set('CL_NO', $payment['CL_NO'])
            ->set('PAYMENT_TIME', $payment['PAYMENT_TIME'])
            ->set('PAYM_ID', $payment['PAYM_ID'])
            ->set('STATUS', '')
            ->set('CRT_USER', $this->session->userdata('user_name'))
            ->insert('hbs_cl_claim');
    }
    
    /**
     * Get all data in [CPS] hbs_cl_claim which are not uploaded to [HBS] cl_claim
     */
    public function get_non_upload()
    {
        return $this->db
            ->where('UPL_DATE', null)
            ->get('hbs_cl_claim')->result_array();
    }
    
    /**
     * Update data of [HBS] cl_claim table
     * @param $hbs_db database object of HBS
     * @param $data array
     */
    public function update_hbs_cl_claim($hbs_db, $data)
    {
        $sql = "
            UPDATE cl_claim
            SET
                TRANSFER_DATE_{$data['PAYMENT_TIME']} = TO_DATE('{$data['TF_DATE']}', 'yyyy-mm-dd'),
                TRANSFER_AMT_{$data['PAYMENT_TIME']} = '{$data['TF_AMT']}',
                TRANSFER_REMARK_{$data['PAYMENT_TIME']} = '{$data['TF_REMARK']}'
            WHERE CL_NO = '{$data['CL_NO']}'
        ";
        $hbs_db->query($sql);
    }
    
    /**
     * Set UPL_DATE of a list of id
     * @param $list_id array
     * @param $upl_date date
     */
    public function set_upl_date($list_id, $upl_date)
    {
        $this->db
            ->set('UPL_DATE', $upl_date)
            ->where_in('ID', $list_id)
            ->update('hbs_cl_claim');
    }

    /**
     * get FC/PC claims from hbs
     * @param object $hbs_db database object
     * @param date $pay_date Pay Date Y-m-d
     * @return array $claims List of Claim OID
     */
    public function get_claims($hbs_db, $pay_date)
    {
        return $hbs_db->query("
            SELECT DISTINCT clam_oid
            FROM cl_line
            WHERE pay_date >= TO_DATE('$pay_date', 'YYYY-MM-DD')
        ")->result_array();
    }
}
