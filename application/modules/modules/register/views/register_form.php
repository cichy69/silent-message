<?php
if ($use_username) {
    $username = array(
        'name'          => 'username',
        'id'            => 'username',
        'value'         => set_value('username'),
        'maxlength'     => $this->config->item('username_max_length'),
        'size'          => 30,
    );
}

    $email = array(
        'name'          => 'email',
        'id'            => 'email',
        'value'         => set_value('email'),
        'maxlength'     => 80,
        'size'          => 30,
    );

    $email2 = array(
        'name'          => 'email2',
        'id'            => 'email2',
        'value'         => set_value('email2'),
        'maxlength'     => 80,
        'size'          => 30,
    );

    $password = array(
        'name'          => 'password',
        'id'            => 'password',
        'value'         => set_value('password'),
        'maxlength'     => $this->config->item('password_max_length'),
        'size'          => 30,
    );

    $password2 = array(
        'name'          => 'password2',
        'id'            => 'password2',
        'value'         => set_value('password2'),
        'maxlength'     => $this->config->item('password_max_length'),
        'size'          => 30,
    );

?>

<div id="register_form">
<h1>Create an Account</h1>

<?php
        echo form_open('register/create_account');
?>

<fieldset>
<legend>Login Info</legend>
<?php
        echo form_label('Username', $username['id']);
        echo form_input($username);
        echo form_error($username['name']);
        echo isset($errors[$username['name']]) ? "<div class=\"error\">".$errors[$username['name']]."</div>" : '';

        echo form_label('Email Address', $email['id']);
        echo form_input($email);
        echo form_error($email['name']);
        echo isset($errors[$email['name']])?"<div class=\"error\">".$errors[$email['name']]."</div>":'';

        echo form_label('Confirm Address', $email2['id']);
        echo form_input($email2);
        echo form_error($email2['name']);
        echo isset($errors[$email2['name']])?"<div class=\"error\">".$errors[$email2['name']]."</div>":'';

        echo form_label('Password', $password['id']);
        echo form_password($password);
        echo form_error($password['name']);

        echo form_label('Confirm Password', $password2['id']);
        echo form_password($password2);
        echo form_error($password2['name']);

?>
</fieldset>

<?php if ($captcha_registration): ?>

    <fieldset>

    <?php if ($use_recaptcha): ?>

    <legend>reCaptcha</legend>

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

        <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
        <?php echo form_error('recaptcha_response_field'); ?>

            <?php echo $recaptcha_html; ?>

    <?php else: ?>

    <legend>Captcha</legend>
    <p>Enter the code exactly as it appears:</p>

        <?php echo $captcha_html; ?>
        <?php echo form_label('Confirmation Code', $captcha['id']); ?>
        <?php echo form_input($captcha); ?>
        <?php echo form_error($captcha['name']); ?>

    <?php endif;?>
    </fieldset>
<?php endif;?>

<?php   echo form_submit ( 'submit' , 'Create Acccount');
        echo anchor      ( '/login' , 'Login page');
?>

<?php echo form_close(); ?>
</div>
