<?php
$login = array(
    'name' => 'login',
    'id' => 'login',
    'value' => set_value('login'),
    'maxlength' => 80,
    'size' => 30,
);

if ($login_by_username AND $login_by_email) {
    $login_label = 'Email or login';

} else if ($login_by_username) {
    $login_label = 'Login';

} else {
    $login_label = 'Email';
}

$password = array(
    'name' => 'password',
    'id' => 'password',
    'size' => 30,
);

$remember = array(
    'name' => 'remember',
    'id' => 'remember',
    'value' => 1,
    'checked' => set_value('remember'),
    'style' => 'margin:0;padding:0',
);

$captcha = array(
    'name' => 'captcha',
    'id' => 'captcha',
    'maxlength' => 8,
);
?>

<?php echo form_open('/login/log_me_in'); ?>
<div id="login_form">

    <h1>Login</h1>

    <?php echo form_label($login_label, $login['id']); ?>
    <?php echo form_input($login); ?>
    <?php echo form_error($login['name']); ?>
    <?php echo isset($errors[$login['name']]) ? "<div class=\"error\">" . $errors[$login['name']] . "</div>" : ''; ?>

    <?php echo form_label('Password', $password['id']); ?>
    <?php echo form_password($password); ?>
    <?php echo form_error($password['name']); ?>
    <?php echo isset($errors[$password['name']]) ? "<div class=\"error\">" . $errors[$password['name']] . "</div>" : ''; ?>

    <?php if ($show_captcha): ?>


        <?php if ($use_recaptcha): ?>

            <div id="recaptcha_image"></div>
            <br/>
            <a href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
            <br/><br/>     <?php
            /*  <div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')">Get an audio CAPTCHA</a></div>
                <div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')">Get an image CAPTCHA</a></div>
                <div class="recaptcha_only_if_audio">Enter the numbers you hear</div>
            */
            ?>

            <div class="recaptcha_only_if_image">Enter the words above</div>

            <input type="text" id="recaptcha_response_field" name="recaptcha_response_field"/>
            <?php echo form_error('recaptcha_response_field'); ?>

            <?php echo $recaptcha_html; ?>

        <?php else: ?>

            <p>Enter the code exactly as it appears:</p>

            <?php echo $captcha_html; ?>
            <?php echo form_label('Confirmation Code', $captcha['id']); ?>
            <?php echo form_input($captcha); ?>
            <?php echo form_error($captcha['name']); ?>

        <?php endif; ?>
    <?php endif; ?>

    <?php echo form_checkbox($remember); ?>
    <?php echo form_label('Remember me', $remember['id']); ?><br/><br/>
    <?php echo form_submit('submit', 'LOGIN'); ?>
    <?php echo anchor('/login/forgot_password/', 'Forgot password'); ?>
    <br/><br/><br/>
    <?php if ($this->config->item('allow_registration')) echo "<div class=\"right\">" . anchor('/register/create_account', 'Register') . "</div>"; ?>
    <br/> <br/>
    <?php echo form_close(); ?>

</div>
<br/>
