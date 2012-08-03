<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends MX_Controller
{
    /* protected __construct() {{{ */
    /**
     * __construct
     *
     * Use HMVC to check if user is logged in to site.
     * Load need library, models, language files, etc.
     *
     * @access protected
     * @return void
     */
    function __construct()
    {
        parent::__construct();

      //$this->output->enable_profiler(TRUE);

      //No direct access, if username is not logged in, redirect to login page
      if (!modules::run('login/is_logged_in')) {                            // not logged in or activated
          redirect('/login/');
      }

       $this->load->library('form_validation');
       $this->form_validation->CI =& $this;

        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        $this->lang->load('auth');

        $this->load->model('dashboard/dashboard_model');
    }
    /* }}} */

    /* public index() {{{ */
    /**
     * Default method,redirect to inbox page
     *
     * @access public
     * @return void
     */
    function index()
    {
        $this->inbox();
    }
    /* }}} */

    /* public inbox() {{{ */
    /**
     * Show inbox to logged user
     *
     * @access public
     * @return void
     */
    function inbox($sort_type="by_date")
    {
        $sort = $this->security->xss_clean($sort_type);

        $data['messages'] = $this->dashboard_model->fetch_user_conversation($this->session->userdata('user_id'),$sort);

        $this->load->view('frontend/head');
        $this->load->view('dashboard/inbox',$data);
        $this->load->view('frontend/footer');
    }
    /* }}} */

    /* public export() {{{ */
    /**
     * Export user conversation list from inbox to excel spredsheet.
     *
     * @access public
     * @return void
     */
    function export()
    {
        $this->load->helper('date');
        $this->load->library('excel');

        $data = $this->dashboard_model->fetch_user_conversation($this->session->userdata('user_id'),"by_date");

        //print_r($data->result_array());

         $titles = array(
                            'Conversation Subject', 'Last Message Date'
                         );

        $array = array();

        foreach($data->result_array() as $row)
        {
            $array[] = array($row['conversation_subject'], date("d/m/Y H:i:s",$row['conversation_last_reply']));
        }

        $this->excel->filename = $this->session->userdata('username').".".date("d/m/Y-H:i:s",now());
        $this->excel->make_from_array($titles, $array);
        //     $this->excel->make_from_db($data);
    }
    /* }}} */

    /* public delete($id) {{{ */
    /**
     * Delete message from user inbox.
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    function delete($id)
    {
        $conversation_id = (int) $id;

        if($this->dashboard_model->validate_and_delete($conversation_id))
        {
            $this->_show_message($this->lang->line('del_success'));

        } else  {
                    $this->_show_message($this->lang->line('del_failed'));
                }

    }
    /* }}} */

    /* public cp() {{{ */
    /**
     * Control Panel widget, fetch by HMVC call.
     *
     * @access public
     * @return void
     */
    function cp()
    {
       $data['user_id']   = modules::run('login/get_user_id');
       $data['user_name'] = modules::run('login/get_username');

       $this->load->view('dashboard/cp',$data);
    }
    /* }}} */

//--->controll panel actions

    /* public logout() {{{ */
    /**
     * Logout from page, clear autologin.
     *
     * @access public
     * @return void
     */
    function logout()
    {
        $this->load->model('login/login_model');

        $this->login_model->logout();

        redirect('/login');
    }
    /* }}} */

    /* public email_change() {{{ */
    /**
     * Change user email
     *
     * @access public
     * @return void
     */
    function email_change()
    {
            $this->form_validation->set_rules('password' , 'Password' , 'trim|required|xss_clean');
            $this->form_validation->set_rules('email'    , 'Email'    , 'trim|required|valid_email|xss_clean');

            $data['errors'] = array();

            if ($this->form_validation->run()) {                                // validation ok
                if (!is_null($data = $this->dashboard_model->set_new_email(
                        $this->form_validation->set_value('email'),
                        $this->form_validation->set_value('password')))) {            // success

                    $data['site_name'] = $this->config->item('website_name');

                    // Send email with new email address and its activation link
                    $this->_send_email('change_email', $data['new_email'], $data);

                    $this->_show_message(sprintf($this->lang->line('auth_message_new_email_sent'), $data['new_email']));

                } else {
                    $errors = $this->dashboard_model->get_error_message();
                    foreach ($errors as $k => $v)    $data['errors'][$k] = $this->lang->line($v);
                }
            }

            $this->load->view('frontend/head');
            $this->load->view('dashboard/change_email_form', $data);
            $this->load->view('frontend/footer');
    }
    /* }}} */

    /* public change_password() {{{ */
    /**
     * Change user password
     *
     * @access public
     * @return void
     */
    function change_password()
    {
            $this->form_validation->set_rules('old_password', 'Old Password', 'trim|required|xss_clean');
            $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean|min_length['.$this->config->item('password_min_length').']|max_length['.$this->config->item('password_max_length').']|alpha_dash');
            $this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|xss_clean|matches[new_password]');

            $data['errors'] = array();

            if ($this->form_validation->run()) {
                if ($this->dashboard_model->change_password_model(
                        $this->form_validation->set_value('old_password'),
                        $this->form_validation->set_value('new_password'))) {    // success
                    $this->_show_message($this->lang->line('auth_message_password_changed'));

                } else {                                                        // fail
                    $errors = $this->dashboard_model->get_error_message();
                    foreach ($errors as $k => $v)    $data['errors'][$k] = $this->lang->line($v);
                }
            }

            $this->load->view('frontend/head');
            $this->load->view('dashboard/change_password_form', $data);
            $this->load->view('frontend/footer');
    }
    /* }}} */

    /* public reset_email() {{{ */
    /**
     * Replace user email with a new one.
     * User is verified by user_id and authentication code in the URL.
     * Can be called by clicking on link in mail.
     *
     * @access public
     * @return void
     */
    function reset_email()
    {
        $user_id       = $this->uri->segment(3);
        $new_email_key = $this->uri->segment(4);

        // Reset email
        if ($this->dashboard_model->activate_new_email($user_id, $new_email_key)) {    // success
            $this->dashboard_model->logout();
            $this->_show_message($this->lang->line('auth_message_new_email_activated').' '.anchor('/login/', 'Login'));

        } else {                                                                // fail
            $this->_show_message($this->lang->line('auth_message_new_email_failed'));
        }
    }
    /* }}} */

    /* public new_conversation() {{{ */
    /**
     * Start new conversation. Validate send form, and add data to database
     * using builtin transaction from codeigniter to ensecure data integrity.
     *
     * @access public
     * @return void
     */
    function new_conversation()
    {
            $data = array();

            $this->form_validation->set_rules('to'      , 'To'        , 'trim|required|xss_clean|callback__check_recipments');
            $this->form_validation->set_rules('subject' , 'Subject'   , 'trim|required|xss_clean');
            $this->form_validation->set_rules('text'    , 'Text area' , 'trim|required|xss_clean');

            if ($this->form_validation->run())
            {
                    $names = explode(',',$this->form_validation->set_value('to'));
                    foreach($names as &$name)
                    {
                        $name = trim($name);
                    }
                    $ids = $this->dashboard_model->fetch_ids($names);

                if($this->dashboard_model->create_conversation(
                                                                array_unique($ids),
                                                                $this->form_validation->set_value('subject'),
                                                                $this->form_validation->set_value('text')
                                                              ))
                {

                    $this->_show_message(sprintf($this->lang->line('new_message_send')));

                } else {
                            $errors = $this->dashboard_model->get_error_message();
                            foreach ($errors as $k => $v)    $data['errors'][$k] = $this->lang->line($v);
                       }
            }

            $data['users'] = $this->dashboard_model->get_all_users();

            $this->load->view('frontend/head');
            $this->load->view('dashboard/new_conversation', $data);
            $this->load->view('frontend/footer');
    }
    /* }}} */

    /* protected _check_recipments() {{{ */
    /**
     *  Callback function to new conversation form validation.We check if
     *  requested users list is in right format (comma separated values), and
     *  show message to user if there is any errors.
     *
     * @access protected
     * @return void
     */
    function _check_recipments()
    {
        $users = $this->input->post('to');


        if(preg_match('#^[A-Za-z0-9, ]+$#i',$users) === 0)
        {

            $this->form_validation->set_message('_check_recipments', $this->lang->line('recipment_list_error'));
            return FALSE;

        } else {
                    $names = explode(',',$users);
                    foreach($names as &$name)
                    {
                        $name = trim($name);
                    }

                    $user_ids = $this->dashboard_model->fetch_ids($names);

                    if(count($name) !== count($user_ids) && count(array_diff($names, array_keys($user_ids)))>0 )
                    {
                        $this->form_validation->set_message('_check_recipments', $this->lang->line('users_not_found'). implode(' , ',array_diff($names, array_keys($user_ids)))  . ".");
                        return FALSE;

                    } else return TRUE;
               }

    }
    /* }}} */

    /* public show_message($id) {{{ */
    /**
     * Show conversation detail. Check if user is part of conversation, and add
     * replay if user send form.
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    function show_message($id)
    {
        $safe_id = (int) $id;
        $updated = FALSE;

        if($this->dashboard_model->validate_message($safe_id))
        {
            $this->form_validation->set_rules('message' , 'Message'   , 'trim|required|xss_clean');

            if ($this->form_validation->run())
            {
                $this->dashboard_model->add_message_to_conversation($safe_id,$this->form_validation->set_value('message'));
                $this->dashboard_model->update_last_view($safe_id);
                $updated = TRUE;

            } else {
                            $errors = $this->dashboard_model->get_error_message();
                            foreach ($errors as $k => $v)    $data['errors'][$k] = $this->lang->line($v);
                   }


        $data['subject'] = $this->dashboard_model->fetch_conversation_subject($id);

        if(!$updated) $this->dashboard_model->update_last_view($safe_id);


            $data['conv_messages'] = $this->dashboard_model->fetch_conversation_messages($safe_id);

            $this->load->view('frontend/head');
            $this->load->view('dashboard/conversation', $data);
            $this->load->view('frontend/footer');

        } else {
                    $this->_show_message($this->lang->line('wrong_id'));
               }
    }
    /* }}} */

//--->GENERAL PUROPSE METHODS

    /* public message() {{{ */
    /**
     * Show result message to user.
     *
     * @access public
     * @return void
     */
    function message()
    {
        if($this->session->flashdata('message')!="")
        {
            $this->load->view('frontend/head');
            $this->load->view('dashboard/change_success');
            $this->load->view('frontend/footer');

        } else { $this->inbox();}
    }
    /* }}} */

    /* protected _show_message($message) {{{ */
    /**
     * Show info message
     *
     * @param mixed $message
     * @access protected
     * @return void
     */
    private function _show_message($message)
    {
        $this->session->set_flashdata('message', $message);
        redirect('/dashboard/message');
    }
    /* }}} */

    /* protected _send_email($type, $email, &$data) {{{ */
    /**
     * Send email message of given type (activate, forgot_password, etc.)
     *
     * @param mixed $type
     * @param mixed $email
     * @param mixed $data
     * @access protected
     * @return void
     */
    private function _send_email($type, $email, &$data)
    {
        $this->load->library          ( 'email');
        $this->email->from            ( $this->config->item ( 'webmaster_email'), $this->config->item ( 'website_name'));
        $this->email->reply_to        ( $this->config->item ( 'webmaster_email'), $this->config->item ( 'website_name'));
        $this->email->to              ( $email);
        $this->email->subject         ( sprintf             ( $this->lang->line                       ( 'auth_subject_'.$type), $this->config->item ( 'website_name')));
        $this->email->message         ( $this->load->view   ( ''.$type.'-html', $data, TRUE));
        $this->email->set_alt_message ( $this->load->view   ( ''.$type.'-txt', $data, TRUE));
        $this->email->send            ( );
    }
    /* }}} */

}

/* End of file dashboard.php */
/* Location: ./application/modules/dashboard/controllers/dashboard.php */
