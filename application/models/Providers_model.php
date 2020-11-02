<?php
/**
 * This model contains the business logic and manages the persistence of providers
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Providers_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }

    /**
     * Get the list of hold providers's name
     * @return array record of hold providers
     */
    public function get_hold_providers()
    {
        $this->db->select('PROV_NAME');
        $this->db->where('IS_HOLD', 1);
        $query = $this->db->get('providers');
        $hold_providers = array();
        foreach ($query->result_array() as $row)
        {
            $hold_providers[] = $row['PROV_NAME'];
        }
        return $hold_providers;
    }
    
    /**
     * Get the list of providers or a provider
     * @param int $prov_code
     * @return array record of providers
     */
    public function get_providers($prov_code = 0)
    {
        if ($prov_code > 0)
        {
            $this->db->where('PROV_CODE', $prov_code);
            $query = $this->db->get('providers');
            return $query->row_array();
        }
        $query = $this->db->get('providers');
        return $query->result_array();
    }
    
    /**
     * Hold/unhold a provider
     * @param int $prov_code
     * @param bool $is_hold
     * @param string $upd_user
     */
    public function set_hold($prov_code, $is_hold, $upd_user)
    {
        $provider = $this->get_providers($prov_code);
        $this->db->insert('providers_history', $provider);
        
        if ($is_hold)
        {
            $this->db->set('IS_HOLD', 1);
        }
        else
        {
            $this->db->set('IS_HOLD', 0);
        }
        $this->db->set('UPD_USER', $upd_user);
        $this->db->where('PROV_CODE', $prov_code);
        $this->db->update('providers');
    }
}
