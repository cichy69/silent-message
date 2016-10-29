<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('PasswordHash')) {
    require_once("phpass-0.3/PasswordHash.php");
}

if (!defined('STATUS_ACTIVATED')) define('STATUS_ACTIVATED', '1');
if (!defined('STATUS_NOT_ACTIVATED')) define('STATUS_NOT_ACTIVATED', '0');

class Login_model extends CI_Model
{

    private $table_name = 'users';            // user accounts
    private $attemps_table_name = 'login_attemps';    // user profiles
    private $error = array();

    function __construct()
    {
        parent::__construct();

        $ci =& get_instance();

        $this->table_name = $ci->config->item('db_table_prefix') . $this->table_name;
        $this->attemps_table_name = $ci->config->item('db_table_prefix') . $this->attemps_table_name;

        $this->load->model('login/autologin_model', 'login/attempts_model');
    }

    /**
     * Get number of attempts to login occured from given IP-address or login
     *
     * @param    string
     * @param    string
     * @return    int
     */
    function get_attempts_num($ip_address, $login)
    {
        $this->db->select('1', FALSE);
        $this->db->where('ip_address', $ip_address);
        if (strlen($login) > 0) $this->db->or_where('login', $login);

        $qres = $this->db->get($this->attemps_table_name);
        return $qres->num_rows();
    }

    /* public login($login, $password, $remember, $login_by_username, $login_by_email) {{{ */
    /**
     * Login user on the site. Return TRUE if login is successful
     * (user exists and activated, password is correct), otherwise FALSE.
     *
     * @param mixed $login (username or email or both depending on settings in config file)
     * @param mixed $password
     * @param mixed $remember
     * @param mixed $login_by_username
     * @param mixed $login_by_email
     * @access public
     * @return bool
     */
    function login_do($login, $password, $remember, $login_by_username, $login_by_email)
    {
        if ((strlen($login) > 0) AND (strlen($password) > 0)) {
            // Which function to use to login (based on config)
            if ($login_by_username AND $login_by_email) {
                $get_user_func = 'get_user_by_login';

            } else if ($login_by_username) {
                $get_user_func = 'get_user_by_username';

            } else {
                $get_user_func = 'get_user_by_email';
            }

            if (!is_null($user = $this->login_model->$get_user_func($login))) {                                                                    // login ok
                // Does password match hash in database?
                $hasher = new PasswordHash(
                    $this->config->item('phpass_hash_strength'),
                    $this->config->item('phpass_hash_portable'));

                if ($hasher->CheckPassword($password, $user->password)) {        // password ok

                    if ($user->banned == 1) {                                                            // fail - banned
                        $this->error = array('banned' => $user->ban_reason);

                    } else {
                        $this->session->set_userdata(array(
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'status' => ($user->activated == 1) ? STATUS_ACTIVATED : STATUS_NOT_ACTIVATED,
                        ));

                        if ($user->activated == 0) {                                                // fail - not activated
                            $this->error = array('not_activated' => '');

                        } else {                                         // success
                            if ($remember) {
                                $this->load->model('login/autologin_model');
                                $this->autologin_model->create_autologin($user->id);
                            }

                            $this->_clear_login_attempts($login);

                            $this->update_login_info(
                                $user->id,
                                $this->config->item('login_record_ip'),
                                $this->config->item('login_record_time'));

                            return TRUE;
                        }
                    }
                } else {                                                        // fail - wrong password
                    $this->_increase_login_attempt($login);
                    $this->error = array('password' => 'auth_incorrect_password');
                }

            } else {                                                            // fail - wrong login
                $this->_increase_login_attempt($login);
                $this->error = array('login' => 'auth_incorrect_login');
            }
        }

        return FALSE;
    }
    /* }}} */

    /* public is_max_login_attempts_exceeded($login) {{{ */

    /**
     * Clear all attempt records for given IP-address and login
     * (if attempts to login is being counted)
     *
     * @param mixed $login
     * @access private
     * @return void
     */
    private function _clear_login_attempts($login)
    {
        if ($this->config->item('login_count_attempts')) {
            $this->load->model('login/attempts_model');
            $this->attempts_model->clear_attempts(
                $this->input->ip_address(),
                $login,
                $this->config->item('login_attempt_expire'));
        }
    }
    /* }}} */

    /* private increase_login_attempt($login) {{{ */

    /**
     * Update user login info, such as IP-address or login time, and
     * clear previously generated (but not activated) passwords.
     *
     * @param mixed $user_id
     * @param mixed $record_ip
     * @param mixed $record_time
     * @access public
     * @return void
     */
    function update_login_info($user_id, $record_ip, $record_time)
    {
        $this->db->set('new_password_key', NULL);
        $this->db->set('new_password_requested', NULL);

        if ($record_ip) $this->db->set('last_ip', $this->input->ip_address());
        if ($record_time) $this->db->set('last_login', date('Y-m-d H:i:s'));

        $this->db->where('id', $user_id);
        $this->db->update($this->table_name);
    }
    /* }}} */

    /* public update_login_info($user_id, $record_ip, $record_time) {{{ */

    /**
     * Increase number of attempts for given IP-address and login
     * (if attempts to login is being counted)
     *
     * @param mixed $login
     * @access private
     * @return void
     */
    private function _increase_login_attempt($login)
    {
        if ($this->config->item('login_count_attempts')) {
            if (!$this->is_max_login_attempts_exceeded($login)) {
                $this->attempts_model->increase_attempt($this->input->ip_address(), $login);
            }
        }
    }
    /* }}} */

    /* private clear_login_attempts($login) {{{ */

    /**
     * Check if login attempts exceeded max login attempts (specified in config)
     *
     * @param mixed $login
     * @access public
     * @return bool
     */
    function is_max_login_attempts_exceeded($login)
    {
        if ($this->config->item('login_count_attempts')) {
            return $this->attempts_model->get_attempts_num($this->input->ip_address(), $login)
            >= $this->config->item('login_max_attempts');
        }
        return FALSE;
    }
    /* }}} */

    /* public logout() {{{ */

    /**
     * Locationgout user from the site
     *
     * @access public
     * @return void
     */
    function logout()
    {
        $this->delete_autologin();

        // See http://codeigniter.com/forums/viewreply/662369/ as the reason for the next line
        $this->session->set_userdata(array('user_id' => '', 'username' => '', 'status' => ''));

        $this->session->sess_destroy();
    }
    /* }}} */

    /* private delete_autologin() {{{ */
    /**
     * Clear user's autologin data
     * delete_autologin
     *
     * @access private
     * @return void
     */
    private function delete_autologin()
    {
        $this->load->helper('cookie');
        if ($cookie = get_cookie($this->config->item('autologin_cookie_name'), TRUE)) {

            $data = unserialize($cookie);

            $this->load->model('login/autologin_model');
            $this->autologin_model->delete($data['user_id'], md5($data['key']));

            delete_cookie($this->config->item('autologin_cookie_name'));
        }
    }
    /* }}} */


    /* public get_user_by_id($user_id, $activated) {{{ */
    /**
     * Get user record by Id
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

    /* public get_user_by_login($login) {{{ */

    /**
     * Get user record by username
     *
     * @param mixed $username
     * @access public
     * @return object
     */
    function get_user_by_username($username)
    {
        $this->db->where('LOWER(username)=', strtolower($username));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row();
        return NULL;
    }
    /* }}} */

    /* public get_user_by_username($username) {{{ */

    /**
     * Get user record by email
     *
     * @param mixed $email
     * @access public
     * @return object
     */
    function get_user_by_email($email)
    {
        $this->db->where('LOWER(email)=', strtolower($email));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row();
        return NULL;
    }
    /* }}} */

    /* public get_user_by_email($email) {{{ */

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
     * Set new password key for user and return some data about user:
     * user_id, username, email, new_pass_key.
     * The password key can be used to verify user when resetting his/her password.
     *
     * @param mixed $login
     * @access public
     * @return array
     */
    function forgot_password($login)
    {
        if (strlen($login) > 0) {
            if (!is_null($user = $this->get_user_by_login($login))) {

                $data = array(
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'new_pass_key' => md5(rand() . microtime()),
                );

                $this->_set_password_key($user->id, $data['new_pass_key']);
                return $data;

            } else {
                $this->error = array('login' => 'auth_incorrect_email_or_username');
            }
        }
        return NULL;
    }
    /* }}} */

    /* public forgot_password($login) {{{ */

    /**
     * Get user record by login (username or email)
     *
     * @param mixed $login
     * @access public
     * @return object
     */
    function get_user_by_login($login)
    {
        $this->db->where('LOWER(username)=', strtolower($login));
        $this->db->or_where('LOWER(email)=', strtolower($login));

        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row();
        return NULL;
    }
    /* }}} */

    /* private _set_password_key($user_id, $new_pass_key) {{{ */

    /**
     * Set new password key for user.
     * This key can be used for authentication when resetting user's password.
     *
     * @param mixed $user_id
     * @param mixed $new_pass_key
     * @access public
     * @return bool
     */
    private function _set_password_key($user_id, $new_pass_key)
    {
        $this->db->set('new_password_key', $new_pass_key);
        $this->db->set('new_password_requested', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    /* public reset_password($user_id, $new_pass, $new_pass_key, $expire_period = 900) {{{ */
    /**
     * Change user password if password key is valid and user is authenticated.
     *
     * @param mixed $user_id
     * @param mixed $new_pass
     * @param mixed $new_pass_key
     * @param int $expire_period
     * @access public
     * @return bool
     */
    function reset_password($user_id, $new_pass, $new_pass_key, $expire_period = 900)
    {
        $this->db->set('password', $new_pass);
        $this->db->set('new_password_key', NULL);
        $this->db->set('new_password_requested', NULL);

        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
        $this->db->where('UNIX_TIMESTAMP(new_password_requested) >=', time() - $expire_period);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    /* public activate_user($user_id, $activation_key, $activate_by_email) {{{ */
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
    function activate_user($user_id, $activation_key, $activate_by_email)
    {
        $this->db->trans_start();

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

            $this->create_profile($user_id);
            $this->db->trans_complete();
            return TRUE;
        }

        $this->db->trans_complete();
        return FALSE;
    }
    /* }}} */

    /* private create_profile($user_id) {{{ */
    /**
     * Create an empty profile for a new user
     *
     * @param mixed $user_id
     * @access private
     * @return bool
     */
    private function create_profile($user_id)
    {
        $this->db->set('user_id', $user_id);
        return $this->db->insert($this->profile_table_name);
    }
    /* }}} */

    /* public can_reset_password($user_id, $new_pass_key, $expire_period = 900) {{{ */
    /**
     * Check if given password key is valid and user is authenticated.
     *
     * @param mixed $user_id
     * @param mixed $new_pass_key
     * @param int $expire_period
     * @access public
     * @return void
     */
    function can_reset_password($user_id, $new_pass_key, $expire_period = 900)
    {
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);
        $this->db->where('new_password_key', $new_pass_key);
        $this->db->where('UNIX_TIMESTAMP(new_password_requested) >', time() - $expire_period);

        $query = $this->db->get($this->table_name);
        return $query->num_rows() == 1;
    }
    /* }}} */
}

/* End of file login_model.php */
/* Location: ./application/modules/login/models/login_model.php */
