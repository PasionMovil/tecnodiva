<?php

switch($role)
{
	case "administrator":
		$user_role_permission = "manage_options";
		break;
	case "editor":
		$user_role_permission = "publish_pages";
		break;
	case "author":
		$user_role_permission = "publish_posts";
		break;
}

if (!current_user_can($user_role_permission))
{
	return;
}
else
{
	?>
	
<img style="margin-top: 15px;" src="<?php echo plugins_url("/assets/images/logo.png" , dirname(__FILE__)); ?>" />
<h2 class="nav-tab-wrapper">
	<a class="nav-tab " id="smtp_mail" href="admin.php?page=smtp_mail"><?php _e("Settings", mail_bank);?></a>
	<a class="nav-tab " id="send_test_email" href="admin.php?page=send_test_email"><?php _e("Send Test Email", mail_bank);?></a>
</h2>
<script>
jQuery(document).ready(function()
{
	jQuery(".nav-tab-wrapper > a#<?php echo $_REQUEST["page"];?>").addClass("nav-tab-active");
});
</script>
<?php 
}
?>