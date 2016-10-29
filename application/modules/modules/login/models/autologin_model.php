<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!defined('STATUS_ACTIVATED')) define('STATUS_ACTIVATED', '1');
if (!defined('STATUS_NOT_ACTIVATED')) define('STATUS_NOT_ACTIVATED', '0');

class Autologin_model extends CI_Model
{

    private $table_name = 'user_autologin';  // user accounts
    private $error = array();

    function __construct()
    {
        parent::__construct();

        $ci =& get_instance();

        $this->table_name = $ci->config->item('db_table_prefix') . $this->table_name;
    }

    /* public create_autologin($user_id) {{{ */
    /**
     * Save data for user's autologin
     *
     * @param mixed $user_id
     * @access private
     * @return bool
     */
    function create_autologin($user_id)
    {
        $this->load->helper('cookie');
        $key = substr(md5(uniqid(rand() . get_cookie($this->config->item('sess_cookie_name')))), 0, 16);

        $this->purge($user_id);

        if ($this->set($user_id, md5($key))) {
            set_cookie(array(
                'name' => $this->config->item('autologin_cookie_name'),
                'value' => serialize(array('user_id' => $user_id, 'key' => $key)),
                'expire' => $this->config->item('autologin_cookie_life'),
            ));
            return TRUE;
        }
        return FALSE;
    }
    /* }}} */

    /* public get($user_id, $key) {{{ */

    /**
     * Purge autologin data for given user and login conditions
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    function purge($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('user_agent', substr($this->input->user_agent(), 0, 149));
        $this->db->where('last_ip', $this->input->ip_address());
        $this->db->delete($this->table_name);
    }
    /* }}} */

    /* public set($user_id, $key) {{{ */

    /**
     * Save data for user's autologin
     *
     * @param mixed $user_id
     * @param mixed $key
     * @access public
     * @return bool
     */
    function set($user_id, $key)
    {
        return $this->db->insert($this->table_name, array(
            'user_id' => $user_id,
            'key_id' => $key,
            'user_agent' => substr($this->input->user_agent(), 0, 149),
            'last_ip' => $this->input->ip_address(),
        ));
    }
    /* }}} */

    /* public delete($user_id, $key) {{{ */

    /**
     * Get user data for auto-logged in user.
     * Return NULL if given key or user ID is invalid.
     *
     * @param mixed $user_id
     * @param mixed $key
     * @access public
     * @return object
     */
    function get($user_id, $key)
    {
        $this->db->select($this->users_table_name . '.id');
        $this->db->select($this->users_table_name . '.username');

        $this->db->from($this->users_table_name);

        $this->db->join($this->table_name, $this->table_name . '.user_id = ' . $this->users_table_name . '.id');

        $this->db->where($this->table_name . '.user_id', $user_id);
        $this->db->where($this->table_name . '.key_id', $key);

        $query = $this->db->get();

        if ($query->num_rows() == 1) return $query->row();
        return NULL;
    }
    /* }}} */

    /* public clear($user_id) {{{ */

    /**
     * Delete user's autologin data
     *
     * @param mixed $user_id
     * @param mixed $key
     * @access public
     * @return void
     */
    function delete($user_id, $key)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('key_id', $key);

        $this->db->delete($this->table_name);
    }
    /* }}} */

    /* public purge($user_id) {{{ */

    /**
     * Delete all autologin data for given user
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    function clear($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->delete($this->table_name);
    }
    /* }}} */

}

/* End of file autologin_model.php */
/* Location: ./application/modules/login/models/autologin_model.php */
