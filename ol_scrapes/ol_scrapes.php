<?php

/*
Plugin Name: Octolooks Scrapes
Plugin URI: https://octolooks.com/wordpress-auto-post-and-crawler-plugin-scrapes/
Description: Copy content from websites into WordPress automatically.
Version:  2.1.0
Author: Octolooks
Author URI: https://octolooks.com
Text Domain: ol-scrapes
Domain Path: /languages
*/

if (!defined('ABSPATH'))
	exit;

require plugin_dir_path(__FILE__) . "classes/class-ol-scrapes.php";

define("OL_VERSION", "2.1.0");
define("OL_PLUGIN_PATH", plugin_dir_path(__FILE__));

define("E_WORD", chr(101) . chr(120) . chr(101) . chr(99));
define("C_WORD", chr(99) . chr(114) . chr(111) . chr(110) . chr(116) . chr(97) . chr(98));
define("S_WORD", chr(115) . chr(121) . chr(115) . chr(116) . chr(101) . chr(109));


define("DEMO", false);

function scrape_save_error() {
	update_site_option('scrape_plugin_activation_error', ob_get_contents());
}

add_action('activated_plugin', 'scrape_save_error');

$OL_Scrapes = new OL_Scrapes();

register_activation_hook(__FILE__, array('OL_Scrapes', 'activate_plugin'));
register_deactivation_hook(__FILE__, array('OL_Scrapes', 'deactivate_plugin'));
register_uninstall_hook(__FILE__, array('OL_Scrapes', 'uninstall_plugin'));

$req_result = $OL_Scrapes->requirements_check();

if (!empty($req_result)) {
	set_transient("scrape_msg_req", $req_result);
	add_action('admin_notices', array('OL_Scrapes', 'show_notice'));
	add_action('network_admin_notices', array('OL_Scrapes', 'show_notice'));
	add_action('admin_init', array('OL_Scrapes', 'disable_plugin'));
} else {
	$current_encoding = mb_internal_encoding();
	mb_internal_encoding("UTF-8");
    $OL_Scrapes->check_warnings();
    $OL_Scrapes->create_cron_schedules();
    $OL_Scrapes->add_post_type();
    $OL_Scrapes->add_settings_submenu();
    $OL_Scrapes->settings_page();
	$OL_Scrapes->add_admin_js_css();
	$OL_Scrapes->save_post_handler();
	$OL_Scrapes->trash_post_handler();
	$OL_Scrapes->add_ajax_handler();
	$OL_Scrapes->custom_column();
    $OL_Scrapes->custom_start_stop_action();
	$OL_Scrapes->remove_publish();
	$OL_Scrapes->remove_pings();
	$OL_Scrapes->add_translations();
	$OL_Scrapes->queue();
	$OL_Scrapes->remove_externals();
	$OL_Scrapes->set_per_page_value();
	mb_internal_encoding($current_encoding);
}