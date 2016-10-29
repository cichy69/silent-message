<?php
$password = array(
    'name' => 'password',
    'id' => 'password',
    'size' => 30,
);
$email = array(
    'name' => 'email',
    'id' => 'email',
    'value' => set_value('email'),
    'maxlength' => 80,
    'size' => 30,
);
?>

<?php echo Modules::run('dashboard/dashboard/cp'); ?>

<?php echo form_open('dashboard/email_change'); ?>
<div id="register_form">
    <h1>Change Email</h1>
    <hr/>
    <br/>

    <?php echo form_label('Password', $password['id']); ?>
    <?php echo form_password($password); ?>
    <?php echo form_error($password['name']); ?>
    <?php echo isset($errors[$password['name']]) ? "<div class=\"error\">" . $errors[$password['name']] . "</div>" : ''; ?>


    <?php echo form_label('New email address', $email['id']); ?>
    <?php echo form_input($email); ?>
    <?php echo form_error($email['name']); ?>
    <?php echo isset($errors[$email['name']]) ? "<div id\"error\">" . $errors[$email['name']] . "</div>" : ''; ?>

    <?php echo form_submit('change', 'Send confirmation email'); ?>
    <?php echo form_close(); ?>
</div>
