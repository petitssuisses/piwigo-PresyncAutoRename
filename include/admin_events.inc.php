<?php
defined('PRESYNCAUTORENAME_PATH') or die('Hacking attempt!');

/**
 * admin plugins menu link
 * 
 * Deprecated, replaced with the Has Setting label in main.inc.php 
 * Since Piwigo 11
 */
function presyncautorename_admin_plugin_menu_links($menu)
{
  $menu[] = array(
    'NAME' => l10n('Presync AutoRename'),
    'URL' => PRESYNCAUTORENAME_ADMIN,
    );

  return $menu;
}
