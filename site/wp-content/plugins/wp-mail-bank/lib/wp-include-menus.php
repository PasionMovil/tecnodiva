<?php
//--------------------------------------------------------------------------------------------------------------//
// CODE FOR CREATING MENUS
//---------------------------------------------------------------------------------------------------------------//
global $wpdb,$current_user;
$role = $wpdb->prefix . "capabilities";
$current_user->role = array_keys($current_user->$role);
$role = $current_user->role[0];

switch($role)
{
	case "administrator":
		add_menu_page(" Mail Bank", __(" Mail Bank", mail_bank), "read", "smtp_mail","", plugins_url("/assets/images/mail.png" , dirname(__FILE__)));
		add_submenu_page("smtp_mail", "Settings", __("Settings", mail_bank), "read", "smtp_mail","smtp_mail");
		add_submenu_page("", "", "", "read", "send_test_email",  "send_test_email");
		add_submenu_page("smtp_mail", "System Status", __("System Status", mail_bank), "read", "mail_system_status", "mail_system_status" );
		break;
	case "editor":
		add_menu_page(" Mail Bank", __(" Mail Bank", mail_bank), "read", "smtp_mail","", plugins_url("/assets/images/mail.png" , dirname(__FILE__)));
		add_submenu_page("smtp_mail", "Settings", __("Settings", mail_bank), "read", "smtp_mail","smtp_mail");
		add_submenu_page("", "", "", "read", "send_test_email",  "send_test_email");
		add_submenu_page("smtp_mail", "System Status", __("System Status", mail_bank), "read", "mail_system_status", "mail_system_status" );
		break;
	case "author":
		add_menu_page(" Mail Bank", __(" Mail Bank", mail_bank), "read", "smtp_mail","", plugins_url("/assets/images/mail.png" , dirname(__FILE__)));
		add_submenu_page("smtp_mail", "Settings", __("Settings", mail_bank), "read", "smtp_mail","smtp_mail");
		add_submenu_page("", "", "", "read", "send_test_email",  "send_test_email");
		add_submenu_page("smtp_mail", "System Status", __("System Status", mail_bank), "read", "mail_system_status", "mail_system_status" );
		break;
}

//--------------------------------------------------------------------------------------------------------------//
// CODE FOR CREATING PAGES
//---------------------------------------------------------------------------------------------------------------//

function smtp_mail()
{
	global $wpdb,$current_user,$user_role_permission;
	$role = $wpdb->prefix . "capabilities";
	$current_user->role = array_keys($current_user->$role);
	$role = $current_user->role[0];
	include_once MAIL_BK_PLUGIN_DIR ."/views/mail_header.php";
	include_once MAIL_BK_PLUGIN_DIR ."/views/mail_settings.php";
}

function mail_system_status()
{
	global $wpdb,$current_user,$user_role_permission,$wp_version;
	$role = $wpdb->prefix . "capabilities";
	$current_user->role = array_keys($current_user->$role);
	$role = $current_user->role[0];
	include_once MAIL_BK_PLUGIN_DIR . "/views/wp_system_status.php";
}
function send_test_email()
{
	global $wpdb,$current_user,$user_role_permission,$wp_version;
	$role = $wpdb->prefix . "capabilities";
	$current_user->role = array_keys($current_user->$role);
	$role = $current_user->role[0];
	include_once MAIL_BK_PLUGIN_DIR ."/views/mail_header.php";
	include_once MAIL_BK_PLUGIN_DIR . "/views/test_email.php";
}

?>