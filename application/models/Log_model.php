<?php
/**
 * This Class contains all the business logic and the persistence layer for system log tables
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Log_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Get all logs of bug_of_exists table which have not fixed
     */
    public function get_bug_not_exists()
    {
        return $this->db
            ->get('log_bug_not_exists')
            ->result_array();
    }
    
    /**
     * Update/Insert info of a bug in log_bug_not_exists table
     * @param $bug array - bug_id, username
     */
    public function replace_bug_not_exists($bug)
    {
        $data['bug_id'] = $bug['bug_id'];
        $data['username'] = $bug['username'];
        $this->db->replace('log_bug_not_exists', $data);
    }
    
    /**
    * When a bug is exist in HBS, remove it from log_bug_not_exists
     * @param $bug_ids array
     */
    public function remove_bug_not_exists($bug_ids)
    {
        $this->db
            ->where_in('bug_id', $bug_ids)
            ->delete('log_bug_not_exists');
    }
    
    /**
     * Record claim info of a bug in log_hbs_get_claim table
     * @param $claims array
     */
    public function record_hbs_get_claim($claims)
    {
        $this->db->insert_batch('log_hbs_get_claim', $claims);
    }
    
    /**
     * Insert all data of cps_trigger table in Mantis to log_mantis_cps_trigger in CPS
     * @param $bugs list of bug, each bug is an array
     */
    public function insert_batch_mantis_cps_trigger($bugs)
    {
        $this->db->insert_batch('log_mantis_cps_trigger', $bugs);
    }
}
