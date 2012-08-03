<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if(!class_exists('PasswordHash'))
{
    require_once("phpass-0.3/PasswordHash.php");
}

if (!defined('STATUS_ACTIVATED'))     define('STATUS_ACTIVATED', '1');
if (!defined('STATUS_NOT_ACTIVATED')) define('STATUS_NOT_ACTIVATED', '0');

class Dashboard_model extends CI_Model {

    private $table_name         = 'users';            // user accounts
    private $attemps_table_name = 'login_attemps';    // user profiles
    private $error              = array();

    function __construct()
    {
        parent::__construct();

        $ci =& get_instance();

        $this->table_name            = $ci->config->item('db_table_prefix').$this->table_name;
        $this->attemps_table_name    = $ci->config->item('db_table_prefix').$this->attemps_table_name;


    }

    /* public change_password($old_pass, $new_pass) {{{ */
    /**
     * Change user password (only when user is logged in)
     *
     * @param mixed $old_pass
     * @param mixed $new_pass
     * @access public
     * @return bool
     */
    function change_password_model($old_pass, $new_pass)
    {
        $user_id = $this->session->userdata('user_id');

        if (!is_null($user = $this->get_user_by_id($user_id, TRUE)))
        {

            // Check if old password correct
            $hasher = new PasswordHash(
                                        $this->config->item('phpass_hash_strength'),
                                        $this->config->item('phpass_hash_portable'));

            if ($hasher->CheckPassword($old_pass, $user->password)) {            // success

                // Hash new password using phpass
                $hashed_password = $hasher->HashPassword($new_pass);

                // Replace old password with new one
                $this->change_password_do($user_id, $hashed_password);
                return TRUE;

            } else {                                                            // fail
                     $this->error = array('old_password' => 'auth_incorrect_password');
                   }
        }
        return FALSE;
    }
    /* }}} */

    /* public change_password_do($user_id, $new_pass) {{{ */
    /**
     * Change user password
     *
     * @param mixed $user_id
     * @param mixed $new_pass
     * @access public
     * @return bool
     */
    function change_password_do($user_id, $new_pass)
    {
        $this->db->set('password', $new_pass);
        $this->db->where('id', $user_id);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    function fetch_ids($recipments)
    {
        $this->load->helper('security');

        foreach($recipments as &$recipment)
        {
            $this->db->escape($recipment);
        }

        $this->db->select('`id`, `username`');
        $this->db->from($this->table_name);
        $this->db->where_in('`username`',$recipments);

        $query = $this->db->get();

        $result = array();

        if ($query->num_rows() > 0)
        {
            foreach($query->result_array() as $row)
            {
                $result[$row['username']] = $row['id'];
            }

            return $result;

        } else {
                    return array();
               }

    }

    function fetch_conversation_subject($safe_id)
    {
        $this->db->select('`conversations`.`conversation_subject`');
        $this->db->from('`conversations`');
        $this->db->where('`conversations`.`conversation_id`',$safe_id);
        $this->db->limit(1);

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {

            return $result->row_array();

        } else {
                    return FALSE;
               }

    }

    function update_last_view($safe_id)
    {
        $this->load->helper('date');

        $data = array(
                       'conversation_last_view' => now()
                     );

        $this->db->where('conversation_id', $safe_id);
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->update('conversations_members', $data);

    }

    function add_message_to_conversation($safe_id,$message)
    {
        $this->load->helper('date');

        $safe_message = $this->db->escape(htmlentities($message));

        $data = array(
                        'conversation_id' => $safe_id,
                        'user_id'         => $this->session->userdata('user_id'),
                        'message_date'    => now(),
                        'message_text'    => $safe_message
                     );

        $this->db->insert('conversations_messages', $data);


    }

    function get_all_users()
    {
        $this->db->select('`users`.`username`');
        $this->db->from('`users`');

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {

            return $result;

        } else {
                    return NULL;
               }

    }

    function fetch_conversation_messages($conversation_id)
    {
        $this->db->select('`conversations_messages`.`message_date`');
        $this->db->select('`conversations_messages`.`message_date` > `conversations_members`.`conversation_last_view` AS `message_unread`');
        $this->db->select('`conversations_messages`.`message_text`');
        $this->db->select('`users`.`username`');

        $this->db->from('conversations_messages');

        $this->db->join('`users`', 'conversations_messages.user_id = users.id','inner');
        $this->db->join('`conversations_members`', 'conversations_messages.conversation_id = conversations_members.conversation_id','inner');

        $this->db->where('`conversations_messages`.`conversation_id`', $conversation_id);
        $this->db->where('`conversations_members`.`user_id`', $this->session->userdata('user_id'));

        $this->db->order_by('`conversations_messages`.`message_date`',"desc");

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {

            return $result;

        } else {
                    return FALSE;
               }

    }

    function validate_message($safe_id)
    {
        //check if user is part of given conversation
        $this->db->select('COUNT(1)');
        $this->db->from('`conversations_members`');
        $this->db->where('conversation_id',$safe_id);
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('conversation_deleted',0);

        $query = $this->db->get();

        if($query->num_rows() == 1)
        {
            return TRUE;
        } return FALSE;

    }

    function validate_and_delete($safe_id)
    {
        $this->db->trans_start();
        if($this->validate_message($safe_id))
        { //user is memeber od conversation

            //fetch other users message status, distinct to lower numbers of data in big conversation
            $this->db->distinct();
            $this->db->select('`conversation_deleted`');
            $this->db->from('`conversations_members`');
            $this->db->where('user_id !=',$this->session->userdata('user_id'));
            $this->db->where('conversation_id',$safe_id);

            $result = $this->db->get();

            if($result->num_rows() == 1 && $result->row_array() == 1)
            { //everybody except user deleted conversation, so remove entry from database
                $this->db->delete('conversations', array('conversation_id' => $safe_id));
                $this->db->delete('conversations_memebers', array('conversation_id' => $safe_id));
                $this->db->delete('conversations_messages', array('conversation_id' => $safe_id));

                $this->db->trans_complete();

                        if ($this->db->trans_status() === FALSE)
                        {

                           return FALSE;

                        } else {

                                   return TRUE;
                               }

            } else { //still active users, mark given message as deleted for logged user
                        $data = array(
                                       'conversation_deleted' => 1
                                     );

                        $this->db->where('conversation_id', $safe_id);
                        $this->db->where('user_id', $this->session->userdata('user_id'));
                        $this->db->update('conversations_members', $data);

                        $this->db->trans_complete();

                        if ($this->db->trans_status() === FALSE)
                        {

                           return FALSE;

                        } else {

                                   return TRUE;
                               }


                   }


        } else {//stop transaction and return false, user is not member of given conversation
                $this->db->trans_complete();
                return FALSE;
               }

    }

    function fetch_user_conversation($user_id)
    {
        $this->db->select('`conversations`.`conversation_id`');
        $this->db->select('`conversations`.`conversation_subject`');
        $this->db->select('MAX(`conversations_messages`.`message_date`) AS `conversation_last_reply`');
        $this->db->select('MAX(`conversations_messages`.`message_date`) > `conversations_members`.`conversation_last_view` AS `conversation_undread`');

        $this->db->from('`conversations`');

        $this->db->join('conversations_messages', 'conversations.conversation_id = conversations_messages.conversation_id', 'left');
        $this->db->join('conversations_members', 'conversations.conversation_id = conversations_members.conversation_id', 'inner');

        $this->db->where('`conversations_members`.`user_id`', $user_id);
        $this->db->where('`conversations_members`.`conversation_deleted` = ', 0);

        $this->db->group_by('`conversations`.`conversation_id`');

        $this->db->order_by('`conversation_last_reply`',"desc");

        $query = $this->db->get();

        if ($query->num_rows() > 0)
        {
            return $query;

        } else {
                    return FALSE;
               }


    }


    function create_conversation($ids, $subject, $text)
    {
        $this->load->helper('date');

        $this->db->escape(htmlentities($subject));
        $this->db->escape(htmlentities($text));

        $this->db->trans_start(); //transaction start

            //create entry in conversations table
            $data = array(
                           'conversation_subject' => $subject
                         );
            $this->db->insert('conversations', $data);

            //fetch last conversation id
            $conversation_id = $this->db->insert_id();

            //create entry in conversations_messeage table
            $data2 = array(
                            'conversation_id' => $conversation_id,
                            'user_id'         => $this->session->userdata('user_id'),
                            'message_date'    => now(),
                            'message_text'    => $text
                          );
            $this->db->insert('conversations_messages', $data2);

            //add current user to user list
            $ids[] = $this->session->userdata('user_id');

            //create VALUE part of query, insted of running multiple query, create single
            $values = array();

            foreach($ids as $id)
            {
                $id = (int) $id;

                $values[] = "({$conversation_id}, {$id}, 0, 0)";
            }

            //create entrys in conversations_members. CI activeRecord don't support multiple VALUES in insert(), so we just run query
            $this->db->query("INSERT INTO `conversations_members` (`conversation_id`, `user_id`, `conversation_last_view`, `conversation_deleted`)
                              VALUES " . implode(', ',$values));


        $this->db->trans_complete(); //transaction

        if ($this->db->trans_status() === FALSE)
        {

            return FALSE;

        } else {

                    return TRUE;

               }
    }

    /* public get_error_message() {{{ */
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


    /* public get_user_by_id($user_id, $activated) {{{ */
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

    /* public activate_new_email($user_id, $new_email_key) {{{ */
    /**
     * Activate new email (replace old email with new one) if activation key is valid.
     *
     * @param mixed $user_id
     * @param mixed $new_email_key
     * @access public
     * @return bool
     */
    function activate_new_email($user_id, $new_email_key)
    {
        $this->db->set('email', 'new_email', FALSE);
        $this->db->set('new_email', NULL);
        $this->db->set('new_email_key', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('new_email_key', $new_email_key);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    /* public activate_new_email_do($user_id, $new_email_key) {{{ */
    /**
     * Activate new email (replace old email with new one) if activation key is valid.
     *
     * @param mixed $user_id
     * @param mixed $new_email_key
     * @access public
     * @return bool
     */
    function activate_new_email_do($user_id, $new_email_key)
    {
        $this->db->set('email', 'new_email', FALSE);
        $this->db->set('new_email', NULL);
        $this->db->set('new_email_key', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('new_email_key', $new_email_key);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */

    /* public set_new_email($new_email, $password) {{{ */
    /**
     * Change user email (only when user is logged in) and return some data about user:
     * user_id, username, new_email, new_email_key.
     * The new email cannot be used for login or notification before it is activated.
     *
     * @param mixed $new_email
     * @param mixed $password
     * @access public
     * @return array
     */
    function set_new_email($new_email, $password)
    {
        $user_id = $this->session->userdata('user_id');

        if (!is_null($user = $this->get_user_by_id($user_id, TRUE))) {

            // Check if password correct
            $hasher = new PasswordHash(
                    $this->config->item('phpass_hash_strength'),
                    $this->config->item('phpass_hash_portable'));
            if ($hasher->CheckPassword($password, $user->password)) {            // success

                $data = array(
                    'user_id'    => $user_id,
                    'username'    => $user->username,
                    'new_email'    => $new_email,
                );

                if ($user->email == $new_email) {
                    $this->error = array('email' => 'auth_current_email');

                } elseif ($user->new_email == $new_email) {        // leave email key as is
                    $data['new_email_key'] = $user->new_email_key;
                    return $data;

                } elseif ($this->is_email_available($new_email)) {
                    $data['new_email_key'] = md5(rand().microtime());
                    $this->set_new_email_do($user_id, $new_email, $data['new_email_key'], TRUE);
                    return $data;

                } else {
                    $this->error = array('email' => 'auth_email_in_use');
                }
            } else {                                                            // fail
                $this->error = array('password' => 'auth_incorrect_password');
            }
        }
        return NULL;
    }
    /* }}} */

    /* public is_email_available($email) {{{ */
    /**
     * Check if email available for registering
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

    /* public set_new_email($user_id, $new_email, $new_email_key, $activated) {{{ */
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
    function set_new_email_do($user_id, $new_email, $new_email_key, $activated)
    {
        $this->db->set($activated ? 'new_email' : 'email', $new_email);
        $this->db->set('new_email_key', $new_email_key);
        $this->db->where('id', $user_id);
        $this->db->where('activated', $activated ? 1 : 0);

        $this->db->update($this->table_name);
        return $this->db->affected_rows() > 0;
    }
    /* }}} */


}
/* End of file Dashboard_model.php */
/* Location: ./application/modules/system/models/dashboard_model.php */
