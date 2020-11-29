<?php
defined('PRESYNCAUTORENAME_PATH') or die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Home tab                                                              |
// +-----------------------------------------------------------------------+

// send variables to template
//$template->assign(array(
//  'presyncautorename' => $conf['presyncautorename'],
//));
   
  
  // +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

if (!$conf['enable_synchronization'])
{
  die('synchronization is disabled');
}

// Check user is admin
check_status(ACCESS_ADMINISTRATOR);

if (!is_numeric($_GET['site']))
{
  die ('site param missing or invalid');
}
$site_id = $_GET['site'];

// Check that the site exists
$query='
SELECT galleries_url
  FROM '.SITES_TABLE.'
  WHERE id = '.$site_id;
list($site_url) = pwg_db_fetch_row(pwg_query($query));
if (!isset($site_url))
{
  die('site '.$site_id.' does not exist');
}
$site_is_remote = url_is_remote($site_url);

list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
define('CURRENT_DATE', $dbnow);

$error_labels = array(
  	'PWG-ERROR-NO-RENAME' => array(
    l10n('Impossible to rename'),
    l10n('The file or directory cannot be renamed (it may be that the access is denied)')
    ),
  );
$errors = array();
$infos = array();

if ($site_is_remote)
{
  fatal_error('remote sites not supported');
}
else
{
  include_once( PHPWG_ROOT_PATH.'admin/site_reader_local.php');
  $site_reader = new LocalSiteReader($site_url);
}

$general_failure = true;
if (isset($_POST['submit']))
{
  if ($site_reader->open())
  {
    $general_failure = false;
  }

  // shall we simulate only
  if (isset($_POST['simulate']) and $_POST['simulate'] == 1)
  {
    $simulate = true;
  }
  else
  {
    $simulate = false;
  }
}

// +-----------------------------------------------------------------------+
// |                      directories / categories                         |
// +-----------------------------------------------------------------------+

if (isset($_POST['submit'])
    and ($_POST['sync'] == 'dirs' or $_POST['sync'] == 'files')
    and !$general_failure)
{
  // which categories to update ?
  $query = '
SELECT id, uppercats, global_rank, status, visible
  FROM '.CATEGORIES_TABLE.'
  WHERE dir IS NOT NULL
    AND site_id = '.$site_id;
  if (isset($_POST['cat']) and is_numeric($_POST['cat']))
  {
    if (isset($_POST['subcats-included']) and $_POST['subcats-included'] == 1)
    {
      $query.= '
    AND uppercats '.DB_REGEX_OPERATOR.' \'(^|,)'.$_POST['cat'].'(,|$)\'
';
    }
    else
    {
      $query.= '
    AND id = '.$_POST['cat'].'
';
    }
  }
  $db_categories = hash_from_query($query, 'id');

  // get categort full directories in an array for comparison with file
  // system directory tree
  $db_fulldirs = get_fulldirs(array_keys($db_categories));

  // what is the base directory to search file system sub-directories ?
  if (isset($_POST['cat']) and is_numeric($_POST['cat']))
  {
    $basedir = $db_fulldirs[$_POST['cat']];
  }
  else
  {
    $basedir = preg_replace('#/*$#', '', $site_url);
  }

  // we need to have fulldirs as keys to make efficient comparison
  $db_fulldirs = array_flip($db_fulldirs);


  // finding next rank for each id_uppercat. By default, each category id
  // has 1 for next rank on its sub-categories to create
  $next_rank['NULL'] = 1;

  $query = '
SELECT id
  FROM '.CATEGORIES_TABLE;
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    $next_rank[$row['id']] = 1;
  }

  // let's see if some categories already have some sub-categories...
  $query = '
	SELECT id_uppercat, MAX(`rank`)+1 AS next_rank
	FROM '.CATEGORIES_TABLE.'
	GROUP BY id_uppercat';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    // for the id_uppercat NULL, we write 'NULL' and not the empty string
    if (!isset($row['id_uppercat']) or $row['id_uppercat'] == '')
    {
      $row['id_uppercat'] = 'NULL';
    }
    $next_rank[$row['id_uppercat']] = $row['next_rank'];
  }

  // next category id available
  $next_id = pwg_db_nextval('id', CATEGORIES_TABLE);

  // retrieve sub-directories fulldirs from the site reader
  $fs_fulldirs = $site_reader->get_full_directories($basedir);

  $dirs_to_rename = array();

  // get_full_directories doesn't include the base directory, so if it's a
  // category directory, we need to include it in our array
  if (isset($_POST['cat']))
  {
    $fs_fulldirs[] = $basedir;
  }
  // If $_POST['subcats-included'] != 1 ("Search in sub-albums" is unchecked)
  // $db_fulldirs doesn't include any subdirectories and $fs_fulldirs does
  // So $fs_fulldirs will be limited to the selected basedir
  // (if that one is in $fs_fulldirs)
  if (!isset($_POST['subcats-included']) or $_POST['subcats-included'] != 1)
  {
    $fs_fulldirs = array_intersect($fs_fulldirs, array_keys($db_fulldirs));
  }
  
  // Check for invalid directory names in arborescence
  foreach ($fs_fulldirs as $fulldir)
  {
    $dir = basename($fulldir);
    if (!preg_match($conf['sync_chars_regex'], $dir))
    {
		// Invalid directory name
		$dirs_to_rename[] = array(
			'origin' => $fulldir
		);
    }
  }
	$dirs_to_rename_r = array_reverse($dirs_to_rename);
	
	// Attemps to rename each directory with a valid pattern (all invalid characters are replaced with an undercore
	// Note : if a directory already exists, it will prefix the new name with an incremental number (_1, _2, _3, ...)
	foreach ($dirs_to_rename_r as $current_dir) {
		$inc_dir = 0;
		$tmp_newdir = dirname($current_dir['origin'])."/".preg_replace("/[^a-zA-Z0-9]/",'_', basename($current_dir['origin']));
		$tst_newdir = $tmp_newdir;
		while (is_dir($tst_newdir)) {
			$tst_newdir = $tmp_newdir."_".$inc_dir;
			$inc_dir++;				
		}
		
		if (!$simulate) {
			// Real mode, renaming directory
			if (!rename($current_dir['origin'],$tst_newdir)) {
				$errors[] = array(
					'path' => $current_dir['origin'],
					'type' => 'PWG-ERROR-NO-RENAME'
				);
			} else {
				$infos[] = array(
					'path' => l10n('Directory ').$current_dir['origin'],
					'info' => l10n('renamed to ').$tst_newdir
				);
			}
		} else {
			$infos[] = array(
				'path' => l10n('Directory ').$current_dir['origin'],
				'info' => l10n('to be renamed to ').$tst_newdir
			);
		}			
	}
}
// +-----------------------------------------------------------------------+
// |                           files / elements                            |
// +-----------------------------------------------------------------------+
if (isset($_POST['submit']) and $_POST['sync'] == 'files'
      and !$general_failure)
{


  $fs = $site_reader->get_elements($basedir);


 
  foreach (array_keys($fs) as $path)
  {
    $insert = array();
    // storage category must exist
    $dirname = dirname($path);
    
    $filename = basename($path);
    if (!preg_match($conf['sync_chars_regex'], $filename))
    {
		// Invalid file name
		$file_extension = pathinfo($filename,PATHINFO_EXTENSION);

		
		$inc_file = 0;
		$tmp_newfile = $dirname."/".preg_replace("/[^a-zA-Z0-9]/",'_', basename($filename,".".$file_extension));
		$tst_newfile = $tmp_newfile.".".$file_extension;
		while (is_file($tst_newfile)) {
			$tst_newfile = $tmp_newfile."_".$inc_file.".".$file_extension;
			$inc_file++;				
		}
		
		if (!$simulate) {
			// Real mode, renaming directory
			if (!rename($path,$tst_newfile)) {
				$errors[] = array(
					'path' => $path,
					'type' => 'PWG-ERROR-NO-RENAME'
				);
			} else {
				$infos[] = array(
					'path' => l10n('File ').$path,
					'info' => l10n('renamed to ').$tst_newfile
				);
			}
		} else {
			$infos[] = array(
				'path' => l10n('File ').$path,
				'info' => l10n('to be renamed to ').$tst_newfile
			);
		}
      //continue;
    }
  }
}





// +-----------------------------------------------------------------------+
// |                        template initialization                        |
// +-----------------------------------------------------------------------+
$result_title = '';
if (isset($simulate) and $simulate)
{
  $result_title.= '['.l10n('Simulation').'] ';
}

// used_metadata string is displayed to inform admin which metadata will be
// used from files for synchronization
$used_metadata = implode( ', ', $site_reader->get_metadata_attributes());
if ($site_is_remote and !isset($_POST['submit']) )
{
  $used_metadata.= ' + ...';
}

$template->assign(
  array(
    'SITE_URL'=>$site_url,
    ));

// +-----------------------------------------------------------------------+
// |                        introduction : choices                         |
// +-----------------------------------------------------------------------+
if (isset($_POST['submit']))
{
  $tpl_introduction = array(
      'sync'  => $_POST['sync'],
      'subcats_included' => isset($_POST['subcats-included']) and $_POST['subcats-included']==1,
    );

  if (isset($_POST['cat']) and is_numeric($_POST['cat']))
  {
    $cat_selected = array($_POST['cat']);
  }
  else
  {
    $cat_selected = array();
  }
}
else
{
  $tpl_introduction = array(
      'sync'  => 'dirs',
      'subcats_included' => true,
    );

  $cat_selected = array();

  if (isset($_GET['cat_id']))
  {
    check_input_parameter('cat_id', $_GET, false, PATTERN_ID);

    $cat_selected = array($_GET['cat_id']);
    $tpl_introduction['sync'] = 'files';
  }
}


$template->assign('introduction', $tpl_introduction);

$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
  WHERE site_id = '.$site_id;
display_select_cat_wrapper($query,
                           $cat_selected,
                           'category_options',
                           false);


if (count($errors) > 0)
{
  foreach ($errors as $error)
  {
    $template->append(
      'sync_errors',
      array(
        'ELEMENT' => $error['path'],
        'LABEL' => $error['type'].' ('.$error_labels[$error['type']][0].')'
        ));
  }

  foreach ($error_labels as $error_type=>$error_description)
  {
    $template->append(
      'sync_error_captions',
      array(
        'TYPE' => $error_type,
        'LABEL' => $error_description[1]
        ));
  }
}

$_POST['display_info'] = 1;
if (count($infos) > 0
    and isset($_POST['display_info'])
    and $_POST['display_info'] == 1)
{
  foreach ($infos as $info)
  {
    $template->append(
      'sync_infos',
      array(
        'ELEMENT' => $info['path'],
        'LABEL' => $info['info']
        ));
  }
}
  
  

// define template file
$template->set_filename('presyncautorename_content', realpath(PRESYNCAUTORENAME_PATH . 'admin/template/home.tpl'));


