<?php
/**
 * This is the main administration page, if you have only one admin page you can put
 * directly its code here or using the tabsheet system like bellow
 */
defined('PRESYNCAUTORENAME_PATH') or die('Hacking attempt!');

global $template, $page, $conf;


// get current tab
$page['tab'] = isset($_GET['tab']) ? $_GET['tab'] : $page['tab'] = 'home';

// include page
include(PRESYNCAUTORENAME_PATH . 'admin/' . $page['tab'] . '.php');

// template vars
$template->assign(array(
  'PRESYNCAUTORENAME_PATH'=> PRESYNCAUTORENAME_PATH, // used for images, scripts, ... access
  'PRESYNCAUTORENAME_ABS_PATH'=> realpath(PRESYNCAUTORENAME_PATH), // used for template inclusion (Smarty needs a real path)
  'PRESYNCAUTORENAME_ADMIN' => PRESYNCAUTORENAME_ADMIN,
  ));

// send page content
$template->assign_var_from_handle('ADMIN_CONTENT', 'presyncautorename_content');
