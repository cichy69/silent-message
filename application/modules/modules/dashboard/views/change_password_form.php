<?php
$old_password = array(
    'name'    => 'old_password',
    'id'    => 'old_password',
    'value' => set_value('old_password'),
    'size'     => 30,
);
$new_password = array(
    'name'    => 'new_password',
    'id'    => 'new_password',
    'maxlength'    => $this->config->item('password_max_length'),
    'size'    => 30,
);
$confirm_new_password = array(
    'name'    => 'confirm_new_password',
    'id'    => 'confirm_new_password',
    'maxlength'    => $this->config->item('password_max_length'),
    'size'     => 30,
);
?>

<?php echo Modules::run('dashboard/dashboard/cp'); ?>

<div id="register_form">
<?php echo form_open($this->uri->uri_string()); ?>
    <?php echo form_label('Old Password', $old_password['id']); ?>
    <?php echo form_password($old_password); ?>
    <?php echo form_error($old_password['name']); ?>
    <?php echo isset($errors[$old_password['name']])?"<div class=\"error\">".$errors[$old_password['name']]."</div>":''; ?>


    <?php echo form_label('New Password', $new_password['id']); ?>
    <?php echo form_password($new_password); ?>
    <?php echo form_error($new_password['name']); ?>
    <?php echo isset($errors[$new_password['name']])?"<div class=\"error\">".$errors[$new_password['name']]."</div>":''; ?>


    <?php echo form_label('Confirm New Password', $confirm_new_password['id']); ?>
    <?php echo form_password($confirm_new_password); ?>
    <?php echo form_error($confirm_new_password['name']); ?>
    <?php echo isset($errors[$confirm_new_password['name']])?"<div class=\"error\">".$errors[$confirm_new_password['name']]."</div>":''; ?>

<?php echo form_submit('change', 'Change Password'); ?>
<?php echo form_close(); ?>
</div>