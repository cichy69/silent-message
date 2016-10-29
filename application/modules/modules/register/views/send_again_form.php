<?php
$email = array(
    'name' => 'email',
    'id' => 'email',
    'value' => set_value('email'),
    'maxlength' => 80,
    'size' => 30,
);
?>
<div id="register_form">
    SACA<br/>
    Send Activation Code Again
    <br/>
    <hr/>
    <?php echo form_open('register/send_again'); ?>
    <?php echo form_label('Email Address', $email['id']); ?>
    <?php echo form_input($email); ?>
    <?php echo form_error($email['name']); ?>
    <?php echo isset($errors[$email['name']]) ? "<div class=\"error\">" . $errors[$email['name']] . "</div>" : ''; ?>
    <?php echo form_submit('send', 'Send'); ?>
    <?php echo form_close(); ?>
</div>
