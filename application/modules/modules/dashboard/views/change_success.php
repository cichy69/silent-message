<div id="register_message">
    <h1>User Panel Info</h1>
    <br/>
    <?php echo $this->session->flashdata('message'); ?>
    <br/>
    <?php echo anchor('/dashboard', 'Back to Dashboard'); ?>
</div>
