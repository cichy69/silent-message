<?php echo Modules::run('dashboard/dashboard/cp'); ?>

<div id="dashboard">
    <h2>New Conversation</h2>
    <hr/>
    <br/>

    <?php
    $to = array(
        'name' => 'to',
        'id' => 'to',
        'value' => set_value('to'),
        'maxlength' => 200,
        'size' => 30,
    );

    $subject = array(
        'name' => 'subject',
        'id' => 'subject',
        'value' => set_value('subject'),
        'size' => 30,
    );

    $text = array(
        'name' => 'text',
        'id' => 'text',
        'value' => set_value('text'),
        'cols' => 100,
        'rows' => 30,
    );

    ?>

    <?php echo form_open('/dashboard/new_conversation'); ?>

    <?php if (isset($users) && $users->result()): ?>
        <div class="users_info">
            Available Users:<br/><br/>
            <?php foreach ($users->result() as $user): ?>
                <?php echo $user->username; ?>,
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php echo form_label('To:', $to['id']); ?>
    <?php echo form_input($to); ?>
    <?php echo form_error($to['name']); ?>
    <?php echo isset($errors[$to['name']]) ? "<div class=\"error\">" . $errors[$to['name']] . "</div>" : ''; ?>

    <?php echo form_label('Subject', $subject['id']); ?>
    <?php echo form_input($subject); ?>
    <?php echo form_error($subject['name']); ?>
    <?php echo isset($errors[$subject['name']]) ? "<div class=\"error\">" . $errors[$subject['name']] . "</div>" : ''; ?>

    <?php echo form_textarea($text); ?>
    <?php echo form_error($text['name']); ?>
    <?php echo isset($errors[$text['name']]) ? "<div class=\"error\">" . $errors[$text['name']] . "</div>" : ''; ?>

    <br/><br/>
    <?php echo form_submit('submit', 'Send'); ?>
    <?php echo form_close(); ?>

</div>
<br/>
</div>
