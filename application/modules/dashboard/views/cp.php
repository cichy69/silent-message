<div id="cp">

    <h1>Hello <?php echo humanize($user_name); ?>!</h1>
    <?php echo anchor('dashboard/inbox', 'INBOX'); ?>
    <?php echo anchor('dashboard/new_conversation', 'New Conversation'); ?>
    <?php echo anchor('dashboard/email_change', 'Change Email'); ?>
    <?php echo anchor('dashboard/change_password', 'Change Password'); ?>
    <?php echo anchor('dashboard/logout', 'Log Out'); ?>

</div>
