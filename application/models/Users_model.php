<?php
/**
 * This model contains the business logic and manages the persistence of users
 * @since         0.1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This model contains the business logic and manages the persistence of users (employees)
 * It is also used by the session controller for the authentication.
 */
class Users_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {

    }

    /**
     * Get the list of users or one user
     * @param int $user_id optional id of one user
     * @return array record of users
     */
    public function get_users($user_id = 0)
    {
        $this->db->join('roles', 'roles.ROLE_ID = users.ROLE_ID');
        if ($user_id === 0)
        {
            return $this->db->get('users')->result_array();
        }
        return $this->db->get_where('users', array('users.USER_ID' => $user_id))->row_array();
    }

    /**
     * Get id of a user's name
     * @param int $user_name
     * @return int user id
     */
    public function get_user_id($user_name)
    {
        return $this->db
            ->get_where('users', array('users.USER_NAME' => $user_name))
            ->row()->USER_ID;
    }
    
    /**
     * Insert a new user into the database. Inserted data are coming from an HTML form
     * @return string deciphered password (so as to send it by e-mail in clear)
     */
    public function set_users()
    {
        //Hash the clear password using bcrypt (8 iterations)
        $password = $this->random_password(8);
        $salt = '$2a$08$' . substr(strtr(base64_encode($this->get_random_bytes(16)), '+', '.'), 0, 22) . '$';
        $hash = crypt($password, $salt);
        
        $data = array(
            'USER_NAME' => $this->input->post('user_name'),
            'PASSWORD' => $hash,
            'FULLNAME' => $this->input->post('fullname'),
            'EMAIL' => $this->input->post('email'),
            'ROLE_ID' => $this->input->post('role_id'),
            'UPD_USER' => $this->session->userdata('user_name')
        );
        $this->db->insert('users', $data);
        return $password;
    }
    
    /**
     * Update a given user in the database. Update data are coming from an HTML form
     * @return int number of affected rows
     */
    public function update_users($user_id)
    {
        $data = array(
            'FULLNAME' => $this->input->post('fullname'),
            'EMAIL' => $this->input->post('email'),
            'ROLE_ID' => $this->input->post('role_id'),
            'ACTIVE' => $this->input->post("active"),
            'UPD_USER' => $this->session->userdata('user_name')
        );
        $this->db
            ->where('USER_ID', $user_id)
            ->update('users', $data);
    }
    
    /**
     * Update a given user in the database. Update data are coming from an HTML form
     * @return int number of affected rows
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function reset_password($user_id, $CipheredNewPassword)
    {
        //Decipher the password value (RSA encoded -> base64 -> decode -> decrypt)
        $password = '';
        $privateKey = openssl_pkey_get_private(file_get_contents('./assets/keys/private.pem', TRUE));
        openssl_private_decrypt(base64_decode($CipheredNewPassword), $password, $privateKey);
        //Hash the clear password using bcrypt (8 iterations)
        $salt = '$2a$08$' . substr(strtr(base64_encode($this->get_random_bytes(16)), '+', '.'), 0, 22) . '$';
        $hash = crypt($password, $salt);
        $this->db
            ->set('PASSWORD', $hash)
            ->where('USER_ID', $user_id)
            ->update('users');
    }
    
    /**
     * Generate a random password
     * @param int $length length of the generated password
     * @return string generated password
     */
    public function random_password($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }
    
    /**
     * Load the profile of a user from the database to the session variables
     * @param array $row database record of a user
     */
    private function loadProfile($row)
    {
        $newdata = array(
            'user_id' => $row->USER_ID,
            'user_name' => $row->USER_NAME,
            'fullname' => $row->FULLNAME,
            'user_email' => $row->EMAIL,
            'department' => $row->DEPARTMENT,
            'role_id' => $row->ROLE_ID,
            'leader_id' => $row->LEADER_ID,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($newdata);
    }
    
    /**
     * Check the provided credentials and load user's profile if they are correct
     * @param string $username username
     * @param string $password password
     * @return bool TRUE if the user is succesfully authenticated, FALSE otherwise
     */
    public function checkCredentials($username, $password)
    {
        $this->db->where('USER_NAME', $username);
        $this->db->where('ACTIVE', 1);
        $query = $this->db->get('users');
        if ($query->num_rows() == 0)
        {
            // No match found
            return FALSE;
        }
        else
        {
            $row = $query->row();
            $hash = crypt($password, $row->PASSWORD);
            if ($hash == $row->PASSWORD)
            {
                // Password does match stored password.
                $this->loadProfile($row);
                return TRUE;
            }
            else
            {
                // Password does not match stored password.
                return FALSE;
            }
        }
    }
    
    /**
     * Check if a user is active (TRUE) or inactive (FALSE)
     * @param string $username username of a user
     * @return bool active (TRUE) or inactive (FALSE)
     */
    public function isActive($username)
    {
        $this->db->where('USER_NAME', $username);
        $query = $this->db->get('users');
        if ($query->num_rows() > 0)
        {
            $row = $query->row();
            return $row->ACTIVE;
        }
        return FALSE;
    }
    
    /**
     * Generate some random bytes by using openssl
     * @param int $count length of the random string
     * @return string a string of pseudo-random bytes (must be encoded)
     */
    protected function get_random_bytes($length)
    {
        if(function_exists('openssl_random_pseudo_bytes'))
        {
            $rnd = openssl_random_pseudo_bytes($length, $strong);
            if ($strong === TRUE)
                return $rnd;
        }
        echo 'getRandomBytes';exit;
        $sha =''; $rnd ='';
        if (file_exists('/dev/urandom'))
        {
          $fp = fopen('/dev/urandom', 'rb');
          if ($fp) {
              if (function_exists('stream_set_read_buffer')) {
                  stream_set_read_buffer($fp, 0);
              }
              $sha = fread($fp, $length);
              fclose($fp);
          }
        }
        for ($i=0; $i<$length; $i++)
        {
          $sha  = hash('sha256',$sha.mt_rand());
          $char = mt_rand(0,62);
          $rnd .= chr(hexdec($sha[$char].$sha[$char+1]));
        }
        return $rnd;
    }
    
    /**
     * Get the list of members of a leader
     * @param string $leader_id user name of a leader
     * @param bool $active active (TRUE) or inactive (FALSE)
     * @return array list of users's name
     */
    public function get_members($leader_id, $active = true)
    {
        $users = [];
        if ($leader_id)
        {
            $this->db->where('LEADER_ID', $leader_id);
        }
        $query = $this->db
            ->select('USER_NAME')
            ->where('ROLE_ID', ROLE_CL_USER)
            ->where('ACTIVE', $active)
            ->get('users');
        if ($query->num_rows() == 0)
        {
            return $users;
        }
        $result = $query->result_array();
        foreach ($result as $row)
        {
            $users[] = $row['USER_NAME'];
        }
        return $users;
    }
    
    /**
     * Get the list of members of a leader and all related info of the members
     * @param string $leader_id user name of a leader
     * @param bool $active active (TRUE) or inactive (FALSE)
     * @return array
     */
    public function get_info_members($leader_id, $active = true)
    {
        if ($leader_id)
        {
            $this->db->where('LEADER_ID', $leader_id);
        }
        return $this->db
            ->where('ROLE_ID', ROLE_CL_USER)
            ->where('ACTIVE', $active)
            ->get('users')
            ->result_array();
    }
    
    /**
     * Get the list of leaders
     * @return array record of leaders's name
     */
    public function get_leaders()
    {
        $users = [];
        $this->db->select('USER_NAME');
        $this->db->where('ROLE_ID', ROLE_CL_LEADER);
        $query = $this->db->get('users');
        if ($query->num_rows() == 0)
        {
            return $users;
        }
        $result = $query->result_array();
        foreach ($result as $row)
        {
            $users[] = $row['USER_NAME'];
        }
        return $users;
    }
    
    /**
     * Get the list of leaders and all related info of the leaders 
     * @return array
     */
    public function get_info_leaders()
    {
        return $this->db
            ->where('ROLE_ID', ROLE_CL_LEADER)
            ->where('ACTIVE', 1)
            ->get('users')
            ->result_array();
    }
    
    /**
     * Get leader id of a member
     * @param string $user_name user name of a member
     * @return int leader_id
     */
    public function get_leader_id($user_name)
    {
        $query = $this->db
            ->select('LEADER_ID')
            ->where('USER_NAME', $user_name)
            ->get('users');
        if ($query->num_rows() == 0)
        {
            return false;
        }
        return $query->row()->LEADER_ID;
    }
    
    /**
     * Set a user as active (TRUE) or inactive (FALSE)
     * @param int $user_id
     * @param bool $active active (TRUE) or inactive (FALSE)
     * @return boolean
     */
    public function set_active($user_id, $active)
    {
        $this->db
            ->set('ACTIVE', $active)
            ->where('USER_ID', $user_id)
            ->update('users');
    }
    
    /**
     * downgrade a user from leader to member
     * @param int $user_id
     */
    public function set_member($user_id)
    {
        $this->db
            ->set('ROLE_ID', ROLE_CL_USER)
            ->where('USER_ID', $user_id)
            ->update('users');
        
        $this->db
            ->set('LEADER_ID', null)
            ->where('LEADER_ID', $user_id)
            ->update('users');
    }
    
    /**
     * promote a user to leader
     * @param int $user_id
     */
    public function set_leader($user_id)
    {
        $this->db
            ->set('ROLE_ID', ROLE_CL_LEADER)
            ->set('LEADER_ID', null)
            ->where('USER_ID', $user_id)
            ->update('users');
    }
    
    /**
     * set team for a member
     * @param int $user_id
     * @param int $leader_id
     */
    public function set_team($user_id, $leader_id)
    {
        $this->db
            ->set('LEADER_ID', $leader_id)
            ->where('USER_ID', $user_id)
            ->update('users');
    }
}
