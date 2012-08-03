<?php
$message = array(
                    'name'  => 'message',
                    'id'    => 'message',
                    'value' => set_value('message'),
                    'cols'  => 80,
                    'rows'  => 10,
);

?>

<?php echo Modules::run('dashboard/dashboard/cp'); ?>

<div id="dashboard">
    <h2>Conversation: <?php echo $subject['conversation_subject'];?> </h2>
        <hr/>
    <br/>

<div id="reply">
<?php echo form_open($this->uri->uri_string()); ?>

       <?php echo form_textarea($message); ?>
       <?php echo form_error($message['name']); ?>
       <?php echo isset($errors[$message['name']])?"<div class=\"error\">".$errors[$message['name']]."</div>":''; ?>

<?php echo form_submit('send', 'Add reply'); ?>
<?php echo form_close(); ?>
</div>

<?php if(isset($conv_messages) && $conv_messages != NULL):?>
    <?php foreach($conv_messages->result() as $message):?>
    <div class="conversation">
    <span class="info <?php if($message->message_unread) echo "unread";?>">
            <?php echo $message->username; ?> [<?php echo date('d/m/Y H:i:s', $message->message_date); ?>]:
        </span>
        <br/><br/>
        <span class="text">
            <?php echo trim($message->message_text,"'"); ?><br/>
        </span>
        <br/><br/>
    </div>
    <?php endforeach;?>
<?php else:?>
No Messages ...
<?php endif;?>
</div>
