<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Login extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->lang->load('auth');

        $this->load->library('form_validation');
        $this->form_validation->CI =& $this;

        $this->load->model('login/login_model');

        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
    }

    function index()
    {
        if (!$this->is_logged_in()) {
            redirect('/login/log_me_in/');

        } else {
            $data['user_id'] = $this->get_user_id();
            $data['username'] = $this->get_username();
            redirect('/dashboard');
        }
    }

    /* public login() {{{ */

    /**
     * Check if user logged in. Also test if user is activated or not.
     *
     * @param bool $activated
     * @access public
     * @return bool
     */
    function is_logged_in($activated = TRUE)
    {
        return $this->session->userdata('status') === ($activated ? STATUS_ACTIVATED : STATUS_NOT_ACTIVATED);
    }
    /* }}} */

    /* private _is_max_login_attempts_exceeded($login) {{{ */

    /**
     * Get user_id
     *
     * @access public
     * @return string
     */
    function get_user_id()
    {
        return $this->session->userdata('user_id');
    }
    /* }}} */

    /* private _create_captcha() {{{ */

    /**
     * Get username
     *
     * @access public
     * @return string
     */
    function get_username()
    {
        return $this->session->userdata('username');
    }
    /* }}} */

    /* protected _check_captcha($code) {{{ */

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

    /* private _create_recaptcha() {{{ */

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

    /* protected _check_recaptcha() {{{ */

    /**
     * Show login message to user.
     *
     * @access public
     * @return void
     */
    function message()
    {
        if ($this->session->flashdata('message') != "") {
            $this->load->view('frontend/head');
            $this->load->view('login/login_message');
            $this->load->view('frontend/footer');

        } else {
            $this->log_me_in();
        }
    }
    /* }}} */

    /* public is_logged_in($activated = TRUE) {{{ */

    /**
     * Login user on the site
     *
     * @access public
     * @return void
     */
    function log_me_in()
    {
        if ($this->is_logged_in()) {                                    // logged in
            redirect('');

        } elseif ($this->is_logged_in(FALSE)) {                        // logged in, not activated
            redirect('/register/send_again/');

        } else {
            $data['login_by_username'] = ($this->config->item('login_by_username') AND
                $this->config->item('use_username'));
            $data['login_by_email'] = $this->config->item('login_by_email');

            $this->form_validation->set_rules('login', 'Login', 'trim|required|xss_clean');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
            $this->form_validation->set_rules('remember', 'Remember me', 'integer');

            // Get login for counting attempts to login
            if ($this->config->item('login_count_attempts') AND
                ($login = $this->input->post('login'))
            ) {
                $login = $this->security->xss_clean($login);

            } else {
                $login = '';
            }

            $data['use_recaptcha'] = $this->config->item('use_recaptcha');

            if ($this->_is_max_login_attempts_exceeded($login)) {
                if ($data['use_recaptcha'])
                    $this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|xss_clean|required|callback__check_recaptcha');
                else
                    $this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|xss_clean|required|callback__check_captcha');
            }

            $data['errors'] = array();

            if ($this->form_validation->run()) {                                                                       // validation ok
                if ($this->login_model->login_do(
                    $this->form_validation->set_value('login'),
                    $this->form_validation->set_value('password'),
                    $this->form_validation->set_value('remember'),
                    $data['login_by_username'],
                    $data['login_by_email'])
                ) {                                                                     // success

                    redirect('/dashboard');

                } else {
                    $errors = $this->login_model->get_error_message();
                    if (isset($errors['banned'])) {                                                         // banned user
                        $this->_show_message($this->lang->line('auth_message_banned') . ' ' . $errors['banned']);

                    } elseif (isset($errors['not_activated'])) {              // not activated user
                        redirect('/register/send_again/');
                    } else {                                                    // fail
                        foreach ($errors as $k => $v) $data['errors'][$k] = $this->lang->line($v);
                    }
                }
            }

            $data['show_captcha'] = FALSE;

            if ($this->_is_max_login_attempts_exceeded($login)) {
                $data['show_captcha'] = TRUE;
                if ($data['use_recaptcha']) {
                    $data['recaptcha_html'] = $this->_create_recaptcha();

                } else {
                    $data['captcha_html'] = $this->_create_captcha();
                }
            }

            $this->load->view('frontend/head');
            $this->load->view('login_form', $data);
            $this->load->view('frontend/footer');

        }

    }
    /* }}} */

    /* public get_user_id() {{{ */

    /**
     * Check if login attempts exceeded max login attempts (specified in config)
     *
     * @param mixed $login
     * @access private
     * @return    bool
     */
    private function _is_max_login_attempts_exceeded($login)
    {
        if ($this->config->item('login_count_attempts')) {
            $this->load->model('login/attempts_model');
            return $this->attempts_model->get_attempts_num($this->input->ip_address(), $login)
            >= $this->config->item('login_max_attempts');
        }
        return FALSE;
    }
    /* }}} */

    /* public get_username() {{{ */

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
        redirect('/login/message');
    }
    /* }}} */

    /* public message() {{{ */

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

    /* protected _show_message($message) {{{ */

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

    /* public forgot_password() {{{ */

    /**
     * Generate reset code (to change password) and send it to user
     *
     * @access public
     * @return void
     */
    function forgot_password()
    {
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        if ($this->is_logged_in()) {                                    // logged in
            redirect('');

        } elseif ($this->is_logged_in(FALSE)) {                        // logged in, not activated
            redirect('/register/send_again/');

        } else {
            $this->form_validation->set_rules('login', 'Email or login', 'trim|required|xss_clean');

            $data['errors'] = array();

            if ($this->form_validation->run()) {                                // validation ok
                if (!is_null($data = $this->login_model->forgot_password(
                    $this->form_validation->set_value('login')))
                ) {

                    $data['site_name'] = $this->config->item('website_name');

                    // Send email with password activation link
                    $this->_send_email('forgot_password', $data['email'], $data);

                    $this->_show_message($this->lang->line('auth_message_new_password_sent'));

                } else {
                    $errors = $this->login_model->get_error_message();
                    foreach ($errors as $k => $v) $data['errors'][$k] = $this->lang->line($v);
                }
            }
            $this->load->view('frontend/head');
            $this->load->view('login/forgot_password_form', $data);
            $this->load->view('frontend/footer');
        }
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

    /* public reset_password() {{{ */
    /**
     * Replace user password (forgotten) with a new one (set by user).
     * User is verified by user_id and authentication code in the URL.
     * Can be called by clicking on link in mail.
     *
     * @access public
     * @return void
     */
    function reset_password()
    {
        $user_id = $this->uri->segment(3);
        $new_pass_key = $this->uri->segment(4);

        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean|min_length[' . $this->config->item('password_min_length') . ']|max_length[' . $this->config->item('password_max_length') . ']|alpha_dash');
        $this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|xss_clean|matches[new_password]');

        $data['errors'] = array();

        if ($this->form_validation->run()) {                                // validation ok
            if (!is_null($data = $this->reset_user_password(
                $user_id, $new_pass_key,
                $this->form_validation->set_value('new_password')))
            ) {    // success

                $data['site_name'] = $this->config->item('website_name');

                // Send email with new password
                $this->_send_email('reset_password', $data['email'], $data);

                $this->_show_message($this->lang->line('auth_message_new_password_activated') . ' ' . anchor('/login/', 'Login'));

            } else {                                                        // fail
                $this->_show_message($this->lang->line('auth_message_new_password_failed'));
            }
        } else {
            // Try to activate user by password key (if not activated yet)
            if ($this->config->item('email_activation')) {
                $this->login_model->activate_user($user_id, $new_pass_key, FALSE);
            }

            if (!$this->login_model->can_reset_password($user_id, $new_pass_key)) {
                $this->_show_message($this->lang->line('auth_message_new_password_failed'));
            }
        }
        $this->load->view('frontend/head');
        $this->load->view('login/reset_password_form', $data);
        $this->load->view('frontend/footer');
    }
    /* }}} */

    /* private reset_user_password($user_id, $new_pass_key, $new_password) {{{ */
    /**
     * Replace user password (forgotten) with a new one (set by user)
     * and return some data about it: user_id, username, new_password, email.
     *
     * @param mixed $user_id
     * @param mixed $new_pass_key
     * @param mixed $new_password
     * @access public
     * @return bool
     */
    private function reset_user_password($user_id, $new_pass_key, $new_password)
    {
        if ((strlen($user_id) > 0) AND (strlen($new_pass_key) > 0) AND (strlen($new_password) > 0)) {

            if (!is_null($user = $this->login_model->get_user_by_id($user_id, TRUE))) {

                // Hash password using phpass
                $hasher = new PasswordHash(
                    $this->config->item('phpass_hash_strength'),
                    $this->config->item('phpass_hash_portable'));
                $hashed_password = $hasher->HashPassword($new_password);

                if ($this->login_model->reset_password(
                    $user_id,
                    $hashed_password,
                    $new_pass_key,
                    $this->config->item('forgot_password_expire'))
                ) {    // success

                    // Clear all user's autologins
                    $this->load->model('login/autologin_model');
                    $this->autologin_model->clear($user->id);

                    return array(
                        'user_id' => $user_id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'new_password' => $new_password,
                    );
                }
            }
        }
        return NULL;
    }
    /* }}} */
}

/* End of file login.php */
/* Location: ./application/modules/login/controllers/login.php */
