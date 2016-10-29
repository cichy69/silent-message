<?php
$login = array(
    'name' => 'login',
    'id' => 'login',
    'value' => set_value('login'),
    'maxlength' => 80,
    'size' => 30,
);

if ($this->config->item('use_username')) {
    $login_label = 'Email or login';
} else {
    $login_label = 'Email';
}
?>

<div id="login_form">
    Forgot password?<br/>
    <hr/>
    <?php echo form_open('/login/forgot_password'); ?>
    <?php echo form_label($login_label, $login['id']); ?>
    <?php echo form_input($login); ?>
    <?php echo form_error($login['name']); ?><?php echo isset($errors[$login['name']]) ? $errors[$login['name']] : ''; ?>
    <?php echo form_submit('reset', 'Get a new password'); ?>
    <?php echo form_close(); ?>
</div>
