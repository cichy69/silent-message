<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('PasswordHash')) {
    require_once("phpass-0.3/PasswordHash.php");
}

if (!defined('STATUS_ACTIVATED')) define('STATUS_ACTIVATED', '1');
if (!defined('STATUS_NOT_ACTIVATED')) define('STATUS_NOT_ACTIVATED', '0');

class Account_model extends CI_Model
{

    private $table_name = 'users';            // user accounts
    private $profile_table_name = 'user_profiles';    // user profiles
    private $error = array();

    function __construct()
    {
        parent::__construct();

        $ci =& get_instance();

        $this->table_name = $ci->config->item('db_table_prefix') . $this->table_name;
        $this->profile_table_name = $ci->config->item('db_table_prefix') . $this->profile_table_name;
    }

    /* public create_user($username, $email, $password, $email_activation) {{{ */
    /**
     * Create new user on the site and return some data about it:
     * user_id, username, password, email, new_email_key (if any).
     *
     * @param mixed $username
     * @param mixed $email
     * @param mixed $password
     * @param mixed $email_activation
     * @access public
     * @return array
     */
    function create_user($username, $email, $password, $email_activation)
    {
        if ((strlen($username) > 0) AND !$this->is_username_available($username)) {
            $this->error = array('username' => 'auth_username_in_use');

        } elseif ((strlen($username) > 0) AND !$this->is_email_available($email)) {
            $this->error = array('email' => 'auth_email_in_use');

        } else {
            // Hash password using phpass
            $hasher = new   PasswordHash(
                $this->config->item('phpass_hash_strength'),
                $this->config->item('phpass_hash_portable')
            );
            $hashed_password = $hasher->HashPassword($password);

            $data = array(
                'username' => $username,
                'password' => $hashed_password,
                'email' => $email,
                'last_ip' => $this->input->ip_address(),
            );

            if ($email_activation) {
                $data['new_email_key'] = md5(rand() . microtime());
            }

            if (!is_null($res = $this->crud_account_create($data, !$email_activation))) {
                $data['user_id'] = $res['user_id'];
                $data['password'] = $password;
                unset($data['last_ip']);
                return $data;
            }
        }

        return NULL;
    }
    /* }}} */

    /* public crud_account_create($data, $activated = TRUE) {{{ */

    /**
     * Check if username available for registering.
     *
     * @param mixed $username
     * @access public
     * @return bool
     */
    function is_username_available($username)
    {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(username)=', strtolower($username));

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }
    /* }}} */

    /* private crud_profile_create($user_id) {{{ */

    /**
     * Check if email available for registering.
     *
     * @param mixed $email
     * @access public
     * @return bool
     */
    function is_email_available($email)
    {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(email)=', strtolower($email));
        $this->db->or_where('LOWER(new_email)=', strtolower($email));

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 0;
    }
    /* }}} */

    /* private crud_profile_create($user_id) {{{ */

    /**
     * Create new user record
     * Using Codeigniter builtin transactions support.
     *
     * @param mixed $data
     * @param bool $activated
     * @access public
     * @return bool
     */
    function crud_account_create($data, $activated = TRUE)
    {
        $data['created'] = date('Y-m-d H:i:s');
        $data['activated'] = $activated ? 1 : 0;

        $this->db->trans_start(); //start transaction
        if ($this->db->insert($this->table_name, $data)) {
            $user_id = $this->db->insert_id();
            if ($activated) $this->crud_profile_create($user_id);
            $this->db->trans_complete(); //end transaction if IF is true
            return array('user_id' => $user_id);
        }

        $this->db->trans_complete(); //finish transaction if IF is false

        return NULL;
    }
    /* }}} */

    /* public is_username_available($username) {{{ */

    /**
     * Create an empty profile for a new user
     *
     * @param mixed $user_id
     * @access private
     * @return bool
     */
    private function crud_profile_create($user_id)
    {
        $this->db->set('user_id', $user_id);
        return $this->db->insert($this->profile_table_name);
    }
    /* }}} */

    /* public is_email_available($email) {{{ */

    /**
     * Can be invoked after any failed operation such as login or register.
     *
     * @access public
     * @return string
     */
    function get_error_message()
    {
        return $this->error;
    }
    /* }}} */

    /* public get_error_message() {{{ */

    /**
     * Activate user if activation key is valid.
     * Can be called for not activated users only.
     *
     * @param mixed $user_id
     * @param mixed $activation_key
     * @param mixed $activate_by_email
     * @access public
     * @return bool
     */
    function activate_user($user_id, $activation_key, $activate_by_email = TRUE)
    {
        $this->db->trans_start(); //transaction start
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);

        if ($activate_by_email) {
            $this->db->where('new_email_key', $activation_key);

        } else {
            $this->db->where('new_password_key', $activation_key);
        }

        $this->db->where('activated', 0);
        $query = $this->db->get($this->table_name);

        if ($query->num_rows() == 1) {
            $this->db->set('activated', 1);
            $this->db->set('new_email_key', NULL);
            $this->db->where('id', $user_id);
            $this->db->update($this->table_name);

            $this->crud_profile_create($user_id);
            $this->db->trans_complete(); //transaction end - commit
            return TRUE;
        }

        $this->db->trans_complete(); //transaction end rollback
        return FALSE;
    }
    /* }}} */

    /* public activate_user($user_id, $activation_key, $activate_by_email) {{{ */

    /**
     * Logout user from the site
     *
     * @access public
     * @return void
     */
    function logout()
    {
        $this->_delete_autologin();

        // See http://codeigniter.com/forums/viewreply/662369/ as the reason for the next line
        $this->session->set_userdata(array('user_id' => '', 'username' => '', 'status' => ''));

        //$this->session->sess_destroy();
    }
    /* }}} */

    /* public logout() {{{ */

    /**
     * Clear user's autologin data
     *
     * @access private
     * @return void
     */
    private function _delete_autologin()
    {
        $this->load->helper('cookie');

        if ($cookie = get_cookie($this->config->item('autologin_cookie_name'), TRUE)) {
            //TODO: AUTOLOGIN
            //$data = unserialize($cookie);

            //$this->ci->load->model('tank_auth/user_autologin');
            //$this->ci->user_autologin->delete($data['user_id'], md5($data['key']));

            //delete_cookie($this->ci->config->item('autologin_cookie_name', 'tank_auth'));
        }
    }
    /* }}} */

    /* private delete_autologin() {{{ */

    /**
     * Change email for activation and return some data about user:
     * user_id, username, email, new_email_key.
     * Can be called for not activated users only.
     *
     * @param mixed $email
     * @access public
     * @return array
     */
    function change_email($email)
    {
        $user_id = $this->session->userdata('user_id');

        if (!is_null($user = $this->get_user_by_id($user_id, FALSE))) {

            $data = array(
                'user_id' => $user_id,
                'username' => $user->username,
                'email' => $email,
            );

            if (strtolower($user->email) == strtolower($email)) {        // leave activation key as is
                $data['new_email_key'] = $user->new_email_key;
                return $data;

            } elseif ($this->is_email_available($email)) {
                $data['new_email_key'] = md5(rand() . microtime());
                $this->ci->users->set_new_email($user_id, $email, $data['new_email_key'], FALSE);
                return $data;

            } else {
                $this->error = array('email' => 'auth_email_in_use');
            }
        }
        return NULL;
    }
    /* }}} */

    /* public change_email($email) {{{ */

    /**
     * Get user record by Id
     * get_user_by_id
     *
     * @param mixed $user_id
     * @param mixed $activated
     * @access public
     * @return object
     */
    function get_user_by_id($user_id, $activated)
    {
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row();
        return NULL;
    }
    /* }}} */

    /* public get_user_by_id($user_id, $activated) {{{ */

    /**
     * Set new email for user (may be activated or not).
     * The new email cannot be used for login or notification before it is activated.
     *
     * @param mixed $user_id
     * @param mixed $new_email
     * @param mixed $new_email_key
     * @param mixed $activated
     * @access public
     * @return bool
     */
    function set_new_email($user_id, $new_email, $new_email_key, $activated)
    {
        $this->db->set($activated ? 'new_email' : 'email', $new_email);
        $this->db->set('new_email_key', $new_email_key);

        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    /* public set_new_email($user_id, $new_email, $new_email_key, $activated) {{{ */

    /**
     * Delete user profile
     *
     * @param mixed $user_id
     * @access private
     * @return void
     */
    private function crud_profile_delete($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->delete($this->profile_table_name);
    }
    /* }}} */
}

/* End of file account_model.php */
/* Location: ./application/modules/register/models/account_model.php */
