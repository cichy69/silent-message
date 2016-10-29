<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Login_attempts
 *
 * This model serves to watch on all attempts to login on the site
 * (to protect the site from brute-force attack to user database)
 *
 * @basedon    Tank_auth - Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Attempts_model extends CI_Model
{
    private $table_name = 'login_attempts';

    function __construct()
    {
        parent::__construct();

        $ci =& get_instance();
        $this->table_name = $ci->config->item('db_table_prefix') . $this->table_name;
    }

    /* public get_attempts_num($ip_address, $login) {{{ */
    /**
     * Get number of attempts to login occured from given IP-address or login
     *
     * @param mixed $ip_address
     * @param mixed $login
     * @access public
     * @return int
     */
    function get_attempts_num($ip_address, $login)
    {
        $this->db->select('1', FALSE);
        $this->db->where('ip_address', $ip_address);

        if (strlen($login) > 0) $this->db->or_where('login', $login);

        $qres = $this->db->get($this->table_name);
        return $qres->num_rows();
    }
    /* }}} */

    /* public increase_attempt($ip_address, $login) {{{ */
    /**
     * Increase number of attempts for given IP-address and login
     *
     * @param mixed $ip_address
     * @param mixed $login
     * @access public
     * @return void
     */
    function increase_attempt($ip_address, $login)
    {
        $this->db->insert($this->table_name, array('ip_address' => $ip_address, 'login' => $login));
    }
    /* }}} */

    /* public clear_attempts($ip_address, $login, $expire_period = 86400) {{{ */
    /**
     * Clear all attempt records for given IP-address and login.
     * Also purge obsolete login attempts (to keep DB clear).
     *
     * @param mixed $ip_address
     * @param mixed $login
     * @param int $expire_period
     * @access public
     * @return void
     */
    function clear_attempts($ip_address, $login, $expire_period = 86400)
    {
        $this->db->where(array('ip_address' => $ip_address, 'login' => $login));

        // Purge obsolete login attempts
        $this->db->or_where('UNIX_TIMESTAMP(time) <', time() - $expire_period);

        $this->db->delete($this->table_name);
    }
    /* }}} */
}

/* End of file login_attempts.php */
/* Location: ./application/models/auth/login_attempts.php */
