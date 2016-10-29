<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Register extends MX_Controller
{

    /* protected __construct() {{{ */
    /**
     * Constructor: load validation library and override $CI
     * from MY_validation_library for HMVC extension.
     *
     * @basedon: https://bitbucket.org/wiredesignz/codeigniter-modular-extensions-hmvc/wiki/Home
     * @access protected
     * @return void
     */
    function __construct()
    {
        parent::__construct();
        $this->lang->load('auth');

        $this->load->library('form_validation');
        $this->form_validation->CI =& $this;

        $this->load->model('register/account_model');

        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        //$this->output->enable_profiler(TRUE);
    }
    /* }}} */

    /* public index() {{{ */
    /**
     * Default method redirect to account creation.
     *
     * @access public
     * @return void
     */
    function index()
    {
        $this->create_account();
    }

    /* }}} */

    function create_account()
    {
        // if ($this->tank_auth->is_logged_in()) {                                 // logged in
        //     redirect('');

        // } elseif ($this->tank_auth->is_logged_in(FALSE)) {                      // logged in, not activated
        //     redirect('/auth/send_again/');

        // } elseif (!$this->config->item('allow_registration', 'tank_auth')) {    // registration is off
        //     $this->_show_message($this->lang->line('auth_message_registration_disabled'));

        // } else {

        //$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        $use_username = $this->config->item('use_username');

        if ($use_username) {
            $this->form_validation->set_rules('username', 'Username', 'trim|required|xss_clean|min_length[' . $this->config->item('username_min_length') . ']|max_length[' . $this->config->item('username_max_length') . ']|alpha_dash');
        }

        $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
        $this->form_validation->set_rules('email2', 'Confirm Email', 'trim|required|xss_clean|valid_email|matches[email]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean|min_length[' . $this->config->item('password_min_length') . ']|max_length[' . $this->config->item('password_max_length') . ']|alpha_dash');
        $this->form_validation->set_rules('password2', 'Confirm Password', 'trim|required|xss_clean|matches[password]');

        $captcha_registration = $this->config->item('captcha_registration');
        $use_recaptcha = $this->config->item('use_recaptcha');

        if ($captcha_registration) {
            if ($use_recaptcha) {
                $this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|xss_clean|required|callback__check_recaptcha');

            } else {
                $this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|xss_clean|required|callback__check_captcha');
            }
        }

        $data['errors'] = array();

        $email_activation = $this->config->item('email_activation');

        if ($this->form_validation->run())                                                // validation ok
        {
            if (!is_null($data = $this->account_model->create_user(
                $use_username ? $this->form_validation->set_value('username') : '',
                $this->form_validation->set_value('email'),
                $this->form_validation->set_value('password'),
                $email_activation))
            ) {                                                                             // success

                $data['site_name'] = $this->config->item('website_name');

                if ($email_activation) {                                                                        // send "activate" email
                    $data['activation_period'] = $this->config->item('email_activation_expire') / 3600;

                    $this->_send_email('activate', $data['email'], $data);

                    unset($data['password']); // Clear password (just for any case)

                    $this->_show_message($this->lang->line('auth_message_registration_completed_1'));

                } else {
                    if ($this->config->item('email_account_details')) // send "welcome" email
                    {
                        $this->_send_email('welcome', $data['email'], $data);
                    }

                    unset($data['password']); // Clear password (just for any case)

                    $this->_show_message($this->lang->line('auth_message_registration_completed_2') . ' ' . anchor('/login/', 'Login'));
                }
            } else {
                $errors = $this->account_model->get_error_message();
                foreach ($errors as $k => $v) $data['errors'][$k] = $this->lang->line($v);
            }
        }

        if ($captcha_registration) {
            if ($use_recaptcha) {
                $data['recaptcha_html'] = $this->_create_recaptcha();

            } else {
                $data['captcha_html'] = $this->_create_captcha();
            }
        }

        $data['use_username'] = $use_username;
        $data['captcha_registration'] = $captcha_registration;
        $data['use_recaptcha'] = $use_recaptcha;

        $this->load->view('frontend/head');
        $this->load->view('register/register_form', $data);
        $this->load->view('frontend/footer');
    }
    //}

    /* public message() {{{ */

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
        $this->load->library('email');
        $this->email->from($this->config->item('webmaster_email'), $this->config->item('website_name'));
        $this->email->reply_to($this->config->item('webmaster_email'), $this->config->item('website_name'));
        $this->email->to($email);
        $this->email->subject(sprintf($this->lang->line('auth_subject_' . $type), $this->config->item('website_name')));
        $this->email->message($this->load->view('' . $type . '-html', $data, TRUE));
        $this->email->set_alt_message($this->load->view('' . $type . '-txt', $data, TRUE));
        $this->email->send();
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
        redirect('/register/message');
    }
    /* }}} */

    /* protected _send_email($type, $email, &$data) {{{ */

    /**
     * Create reCAPTCHA JS and non-JS HTML to verify user as a human
     *
     * @access protected
     * @return string
     */
    private function _create_recaptcha()
    {
        $this->load->helper('recaptcha');

        // Add custom theme so we can get only image
        $options = "<script type=\"text/javascript\">var RecaptchaOptions = {theme: 'custom', custom_theme_widget: 'recaptcha_widget'};</script>\n";

        // Get reCAPTCHA JS and non-JS HTML
        $html = recaptcha_get_html($this->config->item('recaptcha_public_key'));

        return $options . $html;
    }
    /* }}} */

    /* public activate() {{{ */

    /**
     * Create CAPTCHA image to verify user as a human
     *
     * @access protected
     * @return    string
     */
    private function _create_captcha()
    {
        $this->load->helper('captcha');

        $cap = create_captcha(array(
            'img_path' => './' . $this->config->item('captcha_path'),
            'img_url' => base_url() . $this->config->item('captcha_path'),
            'font_path' => './' . $this->config->item('captcha_fonts_path'),
            'font_size' => $this->config->item('captcha_font_size'),
            'img_width' => $this->config->item('captcha_width'),
            'img_height' => $this->config->item('captcha_height'),
            'show_grid' => $this->config->item('captcha_grid'),
            'expiration' => $this->config->item('captcha_expire'),
        ));

        // Save captcha params in session
        $this->session->set_flashdata(array(
            'captcha_word' => $cap['word'],
            'captcha_time' => $cap['time'],
        ));

        return $cap['image'];
    }
    /* }}} */

    /* private _create_captcha() {{{ */

    /**
     * Show registration message to user.
     *
     * @access public
     * @return void
     */
    function message()
    {
        if ($this->session->flashdata('message') != "") {
            $this->load->view('frontend/head');
            $this->load->view('register/register_success');
            $this->load->view('frontend/footer');

        } else {
            $this->create_account();
        }
    }
    /* }}} */

    /* private _check_captcha($code) {{{ */

    /**
     * Activate user account.
     * User is verified by user_id and authentication code in the URL.
     * Can be called by clicking on link in mail.
     *
     * @access public
     * @return void
     */
    function activate()
    {
        $user_id = $this->uri->segment(3);
        $new_email_key = $this->uri->segment(4);

        // Activate user
        if ($this->account_model->activate_user($user_id, $new_email_key))                      //success
        {
            $this->account_model->logout();
            $this->_show_message($this->lang->line('auth_message_activation_completed') . ' ' . anchor('/login/', 'Login'));

        } else {                                                                                // fail
            $this->_show_message($this->lang->line('auth_message_activation_failed'));
        }
    }
    /* }}} */

    /* private _create_recaptcha() {{{ */

    /**
     * Callback function. Check if CAPTCHA test is passed.
     *
     * @param mixed $code
     * @access protected
     * @return bool
     */
    function _check_captcha($code)
    {
        $time = $this->session->flashdata('captcha_time');
        $word = $this->session->flashdata('captcha_word');

        list($usec, $sec) = explode(" ", microtime());
        $now = ((float)$usec + (float)$sec);

        if ($now - $time > $this->config->item('captcha_expire')) {
            $this->form_validation->set_message('_check_captcha', $this->lang->line('auth_captcha_expired'));
            return FALSE;

        } elseif (($this->config->item('captcha_case_sensitive') AND
                $code != $word) OR
            strtolower($code) != strtolower($word)
        ) {
            $this->form_validation->set_message('_check_captcha', $this->lang->line('auth_incorrect_captcha'));
            return FALSE;
        }
        return TRUE;
    }
    /* }}} */

    /* protected _check_recaptcha() {{{ */

    /**
     * Callback function. Check if reCAPTCHA test is passed.
     *
     * @access protected
     * @return bool
     */
    function _check_recaptcha()
    {
        $this->load->helper('recaptcha');

        $resp = recaptcha_check_answer($this->config->item('recaptcha_private_key'),
            $_SERVER['REMOTE_ADDR'],
            $_POST['recaptcha_challenge_field'],
            $_POST['recaptcha_response_field']);

        if (!$resp->is_valid) {
            $this->form_validation->set_message('_check_recaptcha', $this->lang->line('auth_incorrect_captcha'));
            return FALSE;
        }
        return TRUE;
    }
    /* }}} */


    /* public send_again() {{{ */
    /**
     * Send activation email again, to the same or new email address
     * send_again
     *
     * @access public
     * @return void
     */
    function send_again()
    {
        if (!modules::run('login/is_logged_in', FALSE)) {                            // not logged in or activated
            redirect('/login/');

        } else {
            $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');

            $data['errors'] = array();

            if ($this->form_validation->run()) {                                // validation ok
                if (!is_null($data = $this->account_model->change_email(
                    $this->form_validation->set_value('email')))
                ) {            // success

                    $data['site_name'] = $this->config->item('website_name', 'tank_auth');
                    $data['activation_period'] = $this->config->item('email_activation_expire', 'tank_auth') / 3600;

                    $this->_send_email('activate', $data['email'], $data);

                    $this->_show_message(sprintf($this->lang->line('auth_message_activation_email_sent'), $data['email']));

                } else {
                    $errors = $this->account_model->get_error_message();
                    foreach ($errors as $k => $v) $data['errors'][$k] = $this->lang->line($v);
                }
            }
            $this->load->view('frontend/head');
            $this->load->view('register/send_again_form', $data);
            $this->load->view('frontend/footer');
        }
    }
    /* }}} */
}

/* End of file login.php */
/* Location: ./application/modules/register/controllers/register.php */
