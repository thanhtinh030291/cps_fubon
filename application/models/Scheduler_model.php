<?php
/**
 * This Class contains all the business logic and the persistence layer of scheduler
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Scheduler_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
    }
    
    /**
     * Get all available tasks
     */
    public function get_available_tasks()
    {
        return $this->db
            ->where('NEXT_TIME <=', date('Y-m-d H:i:s'))
            ->get('scheduler')->result_array();
    }
    
    /**
     * Update next, start, end time of a task
     * @param $sche_id id of a task scheduler
     * @param $next datetime
     * @param $start datetime
     * @param $end datetime
     */
    public function update_time($sche_id, $next, $start, $end)
    {
        $this->db
            ->set('NEXT_TIME', $next)
            ->set('START_TIME', $start)
            ->set('END_TIME', $end)
            ->where('SCHE_ID', $sche_id)
            ->update('scheduler');
    }
}
