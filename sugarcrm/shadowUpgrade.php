<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Professional Subscription
 * Agreement ("License") which can be viewed at
 * http://www.sugarcrm.com/crm/products/sugar-professional-eula.html
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * among other things: 1) sublicense, resell, rent, lease, redistribute, assign
 * or otherwise transfer Your rights to the Software, and 2) use the Software
 * for timesharing or service bureau purposes such as hosting the Software for
 * commercial gain and/or for the benefit of a third party.  Use of the Software
 * may be subject to applicable fees and any use of the Software without first
 * paying applicable fees is strictly prohibited.  You do not have the right to
 * remove SugarCRM copyrights from the source code or user interface.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *  (i) the "Powered by SugarCRM" logo and
 *  (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * Your Warranty, Limitations of liability and Indemnity are expressly stated
 * in the License.  Please refer to the License for the specific language
 * governing these rights and limitations under the License.  Portions created
 * by SugarCRM are Copyright (C) 2004-2010 SugarCRM, Inc.; All Rights Reserved.
 ********************************************************************************/

ini_set('memory_limit',-1);
set_time_limit(0);

function upgrade_check_errors($errors)
{
	global $logpath;
	if(count($errors) > 0) {
		foreach($errors as $error) {
			logThis("****** SilentUpgrade ERROR: {$error}", $logpath);
		}
		echo "FAILED\n";
		exit(1);
	}
}

function cleanFromCache($sugar_config, $dir)
{
	//Clean smarty from cache
	if(is_dir($sugar_config['cache_dir'].$dir)){
		$allModFiles = array();
		$allModFiles = findAllFiles($sugar_config['cache_dir'].$dir,$allModFiles);
		foreach($allModFiles as $file){
	       	if(file_exists($file)){
				unlink($file);
	       	}
	   }
	}
}

/**
 * Ensure default permissions are set in config
 * @param array $sugar_config
 */
function checkConfigForPermissions(&$sugar_config)
{
     if(!isset($sugar_config['default_permissions'])) {
             $sugar_config['default_permissions'] = array (
                     'dir_mode' => 02770,
                     'file_mode' => 0660,
                     'user' => '',
                     'group' => '',
             );
     }
}

/**
 * Ensure logging is set up in config
 * @param array $sugar_config
 */
function checkLoggerSettings(&$sugar_config)
{
	if(!isset($sugar_config['logger'])){
	    $sugar_config['logger'] =array (
			'level'=>'fatal',
		    'file' =>
		     array (
		      'ext' => '.log',
		      'name' => 'sugarcrm',
		      'dateFormat' => '%c',
		      'maxSize' => '10MB',
		      'maxLogs' => 10,
		      'suffix' => '%m_%Y',
		    ),
		  );
	 }
}

/**
 * Ensure resource management settings are set in config
 * @param array $sugar_config
 */
function checkResourceSettings(&$sugar_config)
{
	if(!isset($sugar_config['resource_management'])){
	  $sugar_config['resource_management'] =
		  array (
		    'special_query_limit' => 50000,
		    'special_query_modules' =>
		    array (
		      0 => 'Reports',
		      1 => 'Export',
		      2 => 'Import',
		      3 => 'Administration',
		      4 => 'Sync',
		    ),
		    'default_limit' => 1000,
		  );
	}
}

/**
 * This function will merge password default settings into config file
 * @param   $sugar_config
 * @param   $sugar_version
 * @return  bool true if successful
 */
function merge_passwordsetting(&$sugar_config, $sugar_version) {

     $passwordsetting_defaults = array (
        'passwordsetting' => array (
            'minpwdlength' => '',
            'maxpwdlength' => '',
            'oneupper' => '',
            'onelower' => '',
            'onenumber' => '',
            'onespecial' => '',
            'SystemGeneratedPasswordON' => '',
            'generatepasswordtmpl' => '',
            'lostpasswordtmpl' => '',
            'customregex' => '',
            'regexcomment' => '',
            'forgotpasswordON' => false,
            'linkexpiration' => '1',
            'linkexpirationtime' => '30',
            'linkexpirationtype' => '1',
            'userexpiration' => '0',
            'userexpirationtime' => '',
            'userexpirationtype' => '1',
            'userexpirationlogin' => '',
            'systexpiration' => '0',
            'systexpirationtime' => '',
            'systexpirationtype' => '0',
            'systexpirationlogin' => '',
            'lockoutexpiration' => '0',
            'lockoutexpirationtime' => '',
            'lockoutexpirationtype' => '1',
            'lockoutexpirationlogin' => '',
        ),
    );

    $sugar_config = sugarArrayMerge($passwordsetting_defaults, $sugar_config );

    // need to override version with default no matter what
    $sugar_config['sugar_version'] = $sugar_version;
}

//rebuild all relationships...
function rebuildRelations()
{
	$_REQUEST['silent'] = true;
	$_REQUEST['upgradeWizard'] = true;
	include('modules/Administration/RebuildRelationship.php');
	include('modules/ACL/install_actions.php');
}

function getHashes($dir)
{
	include("$dir/files.md5");
	return $md5_string;
}

function createMissingRels()
{
	global $db;
	$relForObjects = array('leads'=>'Leads','campaigns'=>'Campaigns','prospects'=>'Prospects');
	foreach($relForObjects as $relObjName=>$relModName){
		//assigned_user
		$guid = create_guid();
		$query = "SELECT id FROM relationships WHERE relationship_name = '{$relObjName}_assigned_user'";
		$result= $db->query($query, true);
		$a = null;
		$a = $db->fetchByAssoc($result);
		if($db->checkError()){
			//log this
		}
		if(!isset($a['id']) && empty($a['id']) ){
			$qRel = "INSERT INTO relationships (id,relationship_name, lhs_module, lhs_table, lhs_key, rhs_module, rhs_table, rhs_key, join_table, join_key_lhs, join_key_rhs, relationship_type, relationship_role_column, relationship_role_column_value, reverse, deleted)
						VALUES ('{$guid}', '{$relObjName}_assigned_user','Users','users','id','{$relModName}','{$relObjName}','assigned_user_id',NULL,NULL,NULL,'one-to-many',NULL,NULL,'0','0')";
			$db->query($qRel);
			if($db->checkError()){
				//log this
			}
		}
		//modified_user
		$guid = create_guid();
		$query = "SELECT id FROM relationships WHERE relationship_name = '{$relObjName}_modified_user'";
		$result= $db->query($query, true);
		if($db->checkError()){
			//log this
		}
		$a = null;
		$a = $db->fetchByAssoc($result);
		if(!isset($a['id']) && empty($a['id']) ){
			$qRel = "INSERT INTO relationships (id,relationship_name, lhs_module, lhs_table, lhs_key, rhs_module, rhs_table, rhs_key, join_table, join_key_lhs, join_key_rhs, relationship_type, relationship_role_column, relationship_role_column_value, reverse, deleted)
						VALUES ('{$guid}', '{$relObjName}_modified_user','Users','users','id','{$relModName}','{$relObjName}','modified_user_id',NULL,NULL,NULL,'one-to-many',NULL,NULL,'0','0')";
			$db->query($qRel);
			if($db->checkError()){
				//log this
			}
		}
		//created_by
		$guid = create_guid();
		$query = "SELECT id FROM relationships WHERE relationship_name = '{$relObjName}_created_by'";
		$result= $db->query($query, true);
		$a = null;
		$a = $db->fetchByAssoc($result);
    	if(!isset($a['id']) && empty($a['id']) ){
			$qRel = "INSERT INTO relationships (id,relationship_name, lhs_module, lhs_table, lhs_key, rhs_module, rhs_table, rhs_key, join_table, join_key_lhs, join_key_rhs, relationship_type, relationship_role_column, relationship_role_column_value, reverse, deleted)
						VALUES ('{$guid}', '{$relObjName}_created_by','Users','users','id','{$relModName}','{$relObjName}','created_by',NULL,NULL,NULL,'one-to-many',NULL,NULL,'0','0')";
			$db->query($qRel);
			if($db->checkError()){
				//log this
			}
    	}
		$guid = create_guid();
		$query = "SELECT id FROM relationships WHERE relationship_name = '{$relObjName}_team'";
		$result= $db->query($query, true);
		$a = null;
		$a = $db->fetchByAssoc($result);
		if(!isset($a['id']) && empty($a['id']) ){
			$qRel = "INSERT INTO relationships (id,relationship_name, lhs_module, lhs_table, lhs_key, rhs_module, rhs_table, rhs_key, join_table, join_key_lhs, join_key_rhs, relationship_type, relationship_role_column, relationship_role_column_value, reverse, deleted)
							VALUES ('{$guid}', '{$relObjName}_team','Teams','teams','id','{$relModName}','{$relObjName}','team_id',NULL,NULL,NULL,'one-to-many',NULL,NULL,'0','0')";
			$db->query($qRel);
			if($db->checkError()){
				//log this
			}

		}
	}
	//Also add tracker perf relationship
	$guid = create_guid();
	$query = "SELECT id FROM relationships WHERE relationship_name = 'tracker_monitor_id'";
	$result= $db->query($query, true);
	if($db->checkError()){
		//log this
	}
	$a = null;
	$a = $db->fetchByAssoc($result);
	if($db->checkError()){
		//log this
	}
	if(!isset($a['id']) && empty($a['id']) ){
		$qRel = "INSERT INTO relationships (id,relationship_name, lhs_module, lhs_table, lhs_key, rhs_module, rhs_table, rhs_key, join_table, join_key_lhs, join_key_rhs, relationship_type, relationship_role_column, relationship_role_column_value, reverse, deleted)
					VALUES ('{$guid}', 'tracker_monitor_id','TrackerPerfs','tracker_perf','monitor_id','Trackers','tracker','monitor_id',NULL,NULL,NULL,'one-to-many',NULL,NULL,'0','0')";
		$db->query($qRel);
		if($db->checkError()){
			//log this
		}
	}
}

function verifyArguments($argv,$usage_regular)
{
	if(count($argv) < 4) {
		echo $usage_regular;
		exit(1);
	}
	return true;
}

function getOldVersion($old_template)
{
	include ("{$old_template}/sugar_version.php");
	return substr(preg_replace("/[^0-9]/", "", $sugar_version),0,3);
}
////////////// START THE BUSINESS

// only run from command line
if(isset($_SERVER['HTTP_USER_AGENT'])) {
	fwrite(STDERR,'This utility may only be run from the command line or command prompt.');
	exit(1);
}

// TODO:
// arguments: instance template old-template [admin] [license_accepted?]
///////////////////////////////////////////////////////////////////////////////
////	USAGE
$usage_regular =<<<eoq2
Usage: php.exe -f shadowUpgrade.php instance template old-template [admin]
eoq2;
////	END USAGE
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
////	STANDARD REQUIRED SUGAR INCLUDES AND PRESETS
if(!defined('sugarEntry')) define('sugarEntry', true);

$_SESSION = array();
$_SESSION['schema_change'] = 'sugar'; // we force-run all SQL
$_SESSION['silent_upgrade'] = true;
$_SESSION['step'] = 'silent'; // flag to NOT try redirect to 4.5.x upgrade wizard

$_REQUEST = array();
$_REQUEST['addTaskReminder'] = 'remind';

define('SUGARCRM_INSTALL', 'SugarCRM_Install');

verifyArguments($argv,$usage_regular);
///////////////////////////////////////////////////////////////////////////////
//////  Verify that all the arguments are appropriately placed////////////////

///////////////////////////////////////////////////////////////////////////////
////	PREP LOCALLY USED PASSED-IN VARS & CONSTANTS

$path		= $argv[1];
$template	= $argv[2];
$old_template = $argv[3];
$user_name = isset($argv[4])? $argv[4]:"admin";

$subdirs	= array('full', 'langpack', 'module', 'patch', 'theme', 'temp');
$logpath = $path. "/shadow_upgrade.log";
$origVersion = getOldVersion($old_template);

define('SUGARCRM_PRE_INSTALL_FILE', 'scripts/pre_install.php');
define('SUGARCRM_POST_INSTALL_FILE', 'scripts/post_install.php');
define('SUGARCRM_PRE_UNINSTALL_FILE', 'scripts/pre_uninstall.php');
define('SUGARCRM_POST_UNINSTALL_FILE', 'scripts/post_uninstall.php');

echo "\n";
echo "********************************************************************\n";
echo "***************This Upgrade process may take sometime***************\n";
echo "********************************************************************\n";
echo "\n";

$errors = array();
chdir($path);
$cwd = $path;

ini_set('error_reporting',1);
set_include_path($path.PATH_SEPARATOR.get_include_path());

require_once('include/entryPoint.php');
require_once('include/SugarLogger/SugarLogger.php');
require_once('include/utils/zip_utils.php');

require("$cwd/config.php");
require_once("sugar_version.php"); // provides $sugar_version & $sugar_flavor
$log = LoggerManager::getLogger('SugarCRM');
$db = DBManagerFactory::getInstance();
$UWstrings		= return_module_language('en_us', 'UpgradeWizard');
$adminStrings	= return_module_language('en_us', 'Administration');
$mod_strings	= array_merge($adminStrings, $UWstrings);

/////////////////////////////////////////////////////////////////////////////
//Adding admin user to the silent upgrade

$current_user = new User();
// if being used for internal upgrades avoid admin user verification
$result = $db->query("select id from users where user_name = '" . $user_name . "' and is_admin=1", false);
$logged_user = $db->fetchByAssoc($result);
if(isset($logged_user['id']) && $logged_user['id'] != null){
    $current_user->retrieve($logged_user['id']);
} else{
	echo "FAILURE: Not an admin user in users table ($user_name). Please provide an admin user\n";
	exit(1);
}

$configOptions = $sugar_config['dbconfig'];
require_once('modules/UpgradeWizard/uw_utils.php'); // must upgrade UW first
// FIXME?
//if(function_exists('set_upgrade_vars')){
//	set_upgrade_vars();
//}

if($configOptions['db_type'] == 'mysql'){
	//Change the db wait_timeout for this session
	$que ="select @@wait_timeout";
	$result = $db->query($que);
	$tb = $db->fetchByAssoc($result);
	logThis('Wait Timeout before change ***** '.$tb['@@wait_timeout'] , $logpath);
	$query ="set wait_timeout=28800";
	$db->query($query);
	$result = $db->query($que);
	$ta = $db->fetchByAssoc($result);
	logThis('Wait Timeout after change ***** '.$ta['@@wait_timeout'] , $logpath);
}

///////////////////////////////////////////////////////////////////////////////
////	RUN SILENT UPGRADE
ob_start();
if(file_exists('ModuleInstall/PackageManager/PackageManagerDisplay.php')) {
	require_once('ModuleInstall/PackageManager/PackageManagerDisplay.php');
}

//Initialize the session variables. If upgrade_progress.php is already created
//look for session vars there and restore them
// FIXME?
//if(function_exists('initialize_session_vars')){
//	initialize_session_vars();
//}

///////////////////////////////////////////////////////////////////////////////
////	HANDLE CUSTOMIZATIONS
$old_files = getHashes($old_template);
$new_files = getHashes($template);
$moved = 0;
$backup_dir = "$path/cache/backups_".date('Y_m_d_H_i_s');
mkdir_recursive($backup_dir);
// find all files that are customized for current template
$custom_files = findAllFiles(".", array(), false, "", array("cache", "custom"));
foreach($custom_files as $custom_file) {
	if($custom_file == 'config.php' || $custom_file == 'config_override.php') continue;
	if(isset($old_files[$custom_file]) && isset($new_files[$custom_file]) &&
		$old_files[$custom_file] != $new_files[$custom_file] && $new_files[$custom_file] != md5_file($custom_file)) {
		// file was updated by upgrade
		logThis("File $custom_file updated, moving customized version out", $logpath);
	} elseif(isset($old_files[$custom_file]) && !isset($new_files[$custom_file])) {
		// file was deleted
		logThis("File $custom_file was deleted in updated version, moving customized version out", $logpath);
	} elseif(isset($new_files[$custom_file]) && !isset($old_files[$custom_file]) && $new_files[$custom_file] != md5_file($custom_file)) {
		// new file was added
		logThis("File $custom_file was added in updatef version, moving customized version out", $logpath);
	} else {
		continue;
	}
	$moved++;
	mkdir_recursive(dirname("$backup_dir/$custom_file"));
	rename($custom_file, "$backup_dir/$custom_file");
}
if($moved) {
	echo "*** $moved customized files were moved aside, please check them in $backup_dir. Please see $logpath for more details.";
}
////////////////COMMIT PROCESS BEGINS///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
////	HANDLE PREINSTALL SCRIPTS
// FIXME?
//if(empty($errors)) {
//	$file = constant('SUGARCRM_PRE_INSTALL_FILE');
//	if(is_file($file)) {
//		include($file);
//		if(!didThisStepRunBefore('commit','pre_install')){
//			set_upgrade_progress('commit','in_progress','pre_install','in_progress');
//			pre_install();
//			set_upgrade_progress('commit','in_progress','pre_install','done');
//		}
//	}
//}

//Clean smarty from cache
cleanFromCache($sugar_config, 'smarty');
upgrade_check_errors($errors);
///////////////////////////////////////////////////////////////////////////////
////	HANDLE POSTINSTALL SCRIPTS
logThis('Starting post_install()...', $logpath);

$trackerManager = TrackerManager::getInstance();
$trackerManager->pause();
$trackerManager->unsetMonitors();
// FIXME: prepare for postinstall script
if (!didThisStepRunBefore('commit', 'post_install')) {
    $file = constant('SUGARCRM_POST_INSTALL_FILE');
    if (is_file($file)) {
        //set_upgrade_progress('commit','in_progress','post_install','in_progress');
        $progArray['post_install'] = 'in_progress';
        post_install_progress($progArray, 'set');
        include ($file);
        post_install();
        executeConvertTablesSql($db->dbType, $_SESSION['allTables']);
        //set process to done
        $progArray['post_install'] = 'done';
        //set_upgrade_progress('commit','in_progress','post_install','done');
        post_install_progress($progArray, 'set');
    }
    //clean vardefs
    logThis('Performing UWrebuild()...', $logpath);
    ob_start();
    @UWrebuild();
    ob_end_clean();
    logThis('UWrebuild() done.', $logpath);

    //// ENSURE config.php HAS PROPER VARS
    logThis('begin check default permissions .', $logpath);
    checkConfigForPermissions($sugar_config);
    logThis('end check default permissions .', $logpath);

    logThis('begin check logger settings .', $logpath);
    checkLoggerSettings($sugar_config);
    logThis('begin check logger settings .', $logpath);

    logThis('begin check resource settings .', $logpath);
    checkResourceSettings($sugar_config);
    logThis('begin check resource settings .', $logpath);

    logThis('Set default_theme to Sugar', $logpath);
    $sugar_config['default_theme'] = 'Sugar';

    logThis('Set default_max_tabs to 7', $logpath);
    $sugar_config['default_max_tabs'] = '7';
    //// WRITE config.php
    ksort($sugar_config);
    if (!write_array_to_file("sugar_config", $sugar_config, "$cwd/config.php")) {
        logThis('*** ERROR: could not write $cwd/config.php! - upgrade will fail!', $logpath);
        $errors[] = "Could not write $cwd/config.php!";
    }

    logThis('post_install() done.', $logpath);
}
upgrade_check_errors($errors);
///////////////////////////////////////////////////////////////////////////////
////	REGISTER UPGRADE
// FIXME: what to do here?
logThis('Registering upgrade with UpgradeHistory', $logpath);
if(!didThisStepRunBefore('commit','upgradeHistory')){
    set_upgrade_progress('commit', 'in_progress', 'upgradeHistory', 'in_progress');
    $file_action = "copied";
    // if error was encountered, script should have died before now
    $new_upgrade = new UpgradeHistory();
    $new_upgrade->filename = $install_file;
    $new_upgrade->md5sum = md5_file($install_file);
    $new_upgrade->name = $zip_from_dir;
    $new_upgrade->description = $manifest['description'];
    $new_upgrade->type = 'patch';
    $new_upgrade->version = $sugar_version;
    $new_upgrade->status = "installed";
    $new_upgrade->manifest = (!empty($_SESSION['install_manifest']) ? $_SESSION['install_manifest'] : '');

    if ($new_upgrade->description == null) {
        $new_upgrade->description = "Silent Upgrade was used to upgrade the instance";
    } else {
        $new_upgrade->description = $new_upgrade->description .
             " Silent Upgrade was used to upgrade the instance.";
     }
     $new_upgrade->save();
     set_upgrade_progress('commit', 'in_progress', 'upgradeHistory', 'done');
     set_upgrade_progress('commit', 'done', 'commit', 'done');
}

upgrade_check_errors($errors);
//delete cache/modules before rebuilding the relations
//Clean modules from cache
cleanFromCache($sugar_config, 'modules');
cleanFromCache($sugar_config, 'themes');

ob_start();
logThis('Start rebuild relationships.', $logpath);
@rebuildRelations();
logThis('End rebuild relationships.', $logpath);
@createMissingRels();
ob_end_clean();

set_upgrade_progress('end','in_progress','end','in_progress');

if(function_exists('deleteCache')){
	set_upgrade_progress('end','in_progress','deleteCache','in_progress');
	@deleteCache();
	set_upgrade_progress('end','in_progress','deleteCache','done');
}

///////////////////////////////////////////////////////////////////////////////
////	HANDLE REMINDERS
// FIXME?
//if(empty($errors)) {
//	commitHandleReminders($skippedFiles, $logpath);
//}

require_once('modules/Administration/Administration.php');
$admin = new Administration();
$admin->saveSetting('system','adminwizard',1);

///////////////////////////////////////////////////////////////////////////////
////	HANDLE PREFERENCES
logThis('Upgrading user preferences start .', $logpath);
if(function_exists('upgradeUserPreferences')){
   upgradeUserPreferences();
}
logThis('Upgrading user preferences finish .', $logpath);

///////////////////////////////////////////////////////////////////////////////
////	HANDLE RELATIONSHIPS
fix_report_relationships($path);
require_once('modules/Administration/upgrade_custom_relationships.php');
upgrade_custom_relationships();

///////////////////////////////////////////////////////////////////////////////
////	HANDLE JS
// re-minify the JS source files
$_REQUEST['root_directory'] = $cwd;
$_REQUEST['js_rebuild_concat'] = 'rebuild';
require_once('jssource/minify.php');

upgrade_check_errors($errors);
///////////////////////////////////////////////////////////////////////////////
////	HANDLE DATABASE
logThis('About to repair the database.', $logpath);
//Use Repair and rebuild to update the database.
require_once("modules/Administration/QuickRepairAndRebuild.php");
$rac = new RepairAndClear();
$rac->repairAndClearAll(array('clearAll'), $mod_strings['LBL_ALL_MODULES'], true, false);
logThis('database repaired', $logpath);

///////////////////////////////////////////////////////////////////////////////
////	HANDLE FAVORITES
if($origVersion < '610')
{
    logThis("Begin: Migrating Sugar Reports Favorites to new SugarFavorites", $logpath);
    migrate_sugar_favorite_reports();
    logThis("Complete: Migrating Sugar Reports Favorites to new SugarFavorites", $logpath);
}

$phpErrors = ob_get_contents();
ob_end_clean();
logThis("**** Potential PHP generated error messages: {$phpErrors}", $logpath);

logThis("***** ShadowUpgrade completed successfully.", $logpath);
echo "********************************************************************\n";
echo "*************************** SUCCESS*********************************\n";
echo "********************************************************************\n";
