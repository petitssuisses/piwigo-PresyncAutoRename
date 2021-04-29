<?php
/*
Plugin Name: Presync AutoRename
Version: 11.2
Description: Automatically corrects files and directory names in your gallery.
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=902
Author: petitssuisses
Author URI: 
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (basename(dirname(__FILE__)) != 'PresyncAutoRename')
{
  add_event_handler('init', 'skeleton_error');
  function skeleton_error()
  {
    global $page;
    $page['errors'][] = 'PluginAutoRename plugin folder name is incorrect, uninstall the plugin and rename it to "PluginAutoRename"';
  }
  return;
}

define('PRESYNCAUTORENAME_ID',      basename(dirname(__FILE__)));
define('PRESYNCAUTORENAME_PATH' ,   PHPWG_PLUGINS_PATH . PRESYNCAUTORENAME_ID . '/');
define('PRESYNCAUTORENAME_ADMIN',   get_root_url() . 'admin.php?page=plugin-' . PRESYNCAUTORENAME_ID);

if (defined('IN_ADMIN'))
{
	// file containing all admin handlers functions
	$admin_file = PRESYNCAUTORENAME_PATH . 'include/admin_events.inc.php';
	
	// init the plugin
	add_event_handler('init', 'presyncautorename_init');

	// admin plugins menu link
	add_event_handler('get_admin_plugin_menu_links', 'presyncautorename_admin_plugin_menu_links', EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_file);
}

function presyncautorename_init()  // load plugin language file
{
	global $conf;
	load_language('plugin.lang', PRESYNCAUTORENAME_PATH);
}