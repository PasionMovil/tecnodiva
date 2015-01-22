<?php
/*
Plugin Name: Wp Mail Bank
Plugin URI: http://tech-banker.com
Description: WP Mail Bank reconfigures the wp_mail() function and make it more enhanced.
Author: Tech Banker
Version: 1.1
Author URI: http://tech-banker.com
*/


/////////////////////////////////////  Define  WP Mail Bank Constants  ////////////////////////////////////////

if (!defined("MAIL_BK_PLUGIN_DIR")) define("MAIL_BK_PLUGIN_DIR",  plugin_dir_path( __FILE__ ));
if (!defined("MAIL_BK_PLUGIN_DIRNAME")) define("MAIL_BK_PLUGIN_DIRNAME", plugin_basename(dirname(__FILE__)));
if (!defined("mail_bank")) define("mail_bank", "mail-bank");

global $phpmailer;

function create_global_menus_for_mail_bank()
{
	
	include_once MAIL_BK_PLUGIN_DIR . "/lib/wp-include-menus.php";
}
/////////////////////////////////////  Functions for Returing Table Names  ////////////////////////////////////////
function wp_mail_bank()
{
	global $wpdb;
	return $wpdb->prefix . "mail_bank";
}
/////////////////////////////////////  Call CSS & JS Scripts - Back End ////////////////////////////////////////

function backend_plugin_js_scripts_mail_bank()
{
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery.validate.min.js",  plugins_url("/assets/js/jquery.validate.min.js",__FILE__));
}

function backend_plugin_css_scripts_mail_bank()
{
	wp_enqueue_style("stylesheet.css", plugins_url("/assets/css/stylesheet.css",__FILE__));
	wp_enqueue_style("system-message.css", plugins_url("/assets/css/system-message.css",__FILE__));
}

function wp_mail_bank_configure($phpmailer) 
{
	global $wpdb;
	$data=$wpdb->get_row
	(
		"SELECT * FROM ".wp_mail_bank()
	);
	$mail_type = $data->mailer_type;
	$phpmailer->Mailer = $data->mailer_type == 0 ? "smtp" : "mail"; 
	$phpmailer->FromName = $data->from_name;
	$phpmailer->From = $data->from_email;
	$phpmailer->Sender =  $data->return_path == 0 ? $data->return_email : $data->from_email;
	$phpmailer->WordWrap = $data->word_wrap;
	if($data->mailer_type == 0)
	{	
		switch($data->encryption)
		{
			case 0 :
				$phpmailer->SMTPSecure ="";
				break;
			case 1 :
				$phpmailer->SMTPSecure ="ssl";
				break;
			case 2 :
				$phpmailer->SMTPSecure ="tls";
				break;
		}
		$phpmailer->Host = $data->smtp_host;
		$phpmailer->Port = $data->smtp_port;
		if($data->smtp_keep_alive == 1)
		{
			$phpmailer->SMTPKeepAlive  = TRUE;
		}
		if($data->authentication == 1)
		{
			$phpmailer->SMTPAuth = TRUE;
			$phpmailer->Username =  $data->smtp_username;
			$phpmailer->Password =  $data->smtp_password;
		}
	}
}

////////////////////////////////////// Class Files Action Functions START here /////////////////////////////////////////
if(isset($_REQUEST["action"]))
{
	switch($_REQUEST["action"])
	{
		case "add_mail_library":
		add_action( "admin_init", "add_mail_library");
		function add_mail_library()
		{
			global $wpdb,$current_user,$user_role_permission;
			$role = $wpdb->prefix . "capabilities";
			$current_user->role = array_keys($current_user->$role);
			$role = $current_user->role[0];
			include_once MAIL_BK_PLUGIN_DIR . "/lib/add_mail_class_file.php";
		}
		break;
	}
}
/////////////////////////////////////  Call Languages for Multi-Lingual ////////////////////////////////////////

function mail_bank_plugin_load_text_domain()
{
	if (function_exists("load_plugin_textdomain"))
	{
		load_plugin_textdomain(mail_bank, false, MAIL_BK_PLUGIN_DIRNAME . "/lang");
	}
}
/////////////////////////////////////  Call Install Script on Plugin Activation ////////////////////////////////////////

function plugin_install_script_for_mail_bank()
{
	include_once MAIL_BK_PLUGIN_DIR . "/lib/wp-install-script.php";
}
add_action('phpmailer_init','wp_mail_bank_configure');
add_action("plugins_loaded", "mail_bank_plugin_load_text_domain");
add_action("admin_menu","create_global_menus_for_mail_bank");
add_action("admin_menu","backend_plugin_js_scripts_mail_bank");
add_action("admin_init","backend_plugin_css_scripts_mail_bank");
register_activation_hook(__FILE__, "plugin_install_script_for_mail_bank");
?>
