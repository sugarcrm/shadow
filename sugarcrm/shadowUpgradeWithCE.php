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

function writeLog($message)
{
	static $fp;
	if(empty($fp)) {
		$fp = fopen($GLOBALS['logpath'], "a+");
		if(!$fp) {
			die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']."!");
		}
	}
	$line = date('r').' - '.$message."\n";
	if(@fwrite($fp, $line) === false) {
		die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']."!!");
	}
	fflush($fp);
	if(!empty($GLOBALS['log'])) {
		$GLOBALS['log']->info("UPGRADE: $message");
	}
}

function upgrade_check_errors($errors)
{
	global $logpath;
	if(count($errors) > 0) {
		foreach($errors as $error) {
			writeLog("****** SilentUpgrade ERROR: {$error}");
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

// we need custom repair because system repair produces errors on upgrade due to acl_fields not being there
function repairTables()
{
	global $db;
	VardefManager::clearVardef();
	$repairedTables = array();

	include 'include/modules.php';
	foreach ($beanFiles as $bean => $file) {
		if(file_exists($file)){
			require_once ($file);
			unset($GLOBALS['dictionary'][$bean]);
			$focus = new $bean ();
			if (($focus instanceOf SugarBean) && !isset($repairedTables[$focus->table_name])) {
			    $db->repairTable($focus, true);
			    $repairedTables[$focus->table_name] = true;
			}
		}
	}

	unset ($dictionary);
	include ('modules/TableDictionary.php');

	foreach ($dictionary as $meta) {

		if ( !isset($meta['table']) || isset($repairedTables[$meta['table']]))
               continue;

        $tablename = $meta['table'];
		$fielddefs = $meta['fields'];
		$indices = $meta['indices'];
		$engine = isset($meta['engine'])?$meta['engine']:null;
		$db->repairTableParams($tablename, $fielddefs, $indices, true, $engine);
		$repairedTables[$tablename] = true;
	}
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
	return array(substr(preg_replace("/[^0-9]/", "", $sugar_version),0,3), $sugar_flavor);
}

function runSqlFiles($origVersion,$destVersion)
{
	global $logpath;
	global $unzip_dir;

	writeLog("Upgrading the database from {$origVersion} to version {$destVersion}");
	$origVersion = substr($origVersion, 0, 2) . 'x';
	$destVersion = substr($destVersion, 0, 2) . 'x';
	if(strcmp($origVersion, $destVersion) == 0) {
		writeLog("*** Skipping schema upgrade for point release.");
		return;
	}

	$schemaFileName = "$unzip_dir/scripts/{$origVersion}_to_{$destVersion}_".$GLOBALS['db']->getScriptName().".sql";
	if(is_file($schemaFileName)) {
		writeLog("Running SQL file $schemaFileName");
		ob_start();
		@parseAndExecuteSqlFile($schemaFileName);
		ob_end_clean();
	} else {
		writeLog("*** ERROR: Schema change script [{$schemaFileName}] could not be found!");
	}
}

/////////////////////////////////////////////////////////////////
///////////////////////////////////////////// START THE BUSINESS
/////////////////////////////////////////////////////////////////

// only run from command line
$sapi_type = php_sapi_name();
if(isset($_SERVER['HTTP_USER_AGENT']) || substr($sapi_type, 0, 3) != 'cli') {
	fwrite(STDERR,'This utility may only be run from the command line or command prompt.');
	exit(1);
}

// TODO:
// arguments: instance template old-template [admin] [license_accepted?]
///////////////////////////////////////////////////////////////////////////////
////	USAGE
$usage_regular =<<<eoq2
Usage: php -f shadowUpgrade.php instance template old-template [admin]

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

define('SUGARCRM_INSTALL', 'SugarCRM_Install');

verifyArguments($argv,$usage_regular);
///////////////////////////////////////////////////////////////////////////////
//////  Verify that all the arguments are appropriately placed////////////////

///////////////////////////////////////////////////////////////////////////////
////	PREP LOCALLY USED PASSED-IN VARS & CONSTANTS

$instance_path		= $argv[1];
$template	= $argv[2];
$old_template = $argv[3];
$user_name = isset($argv[4])? $argv[4]:"admin";

$unzip_dir = getcwd();
$logpath = $path = $instance_path. "/shadow_upgrade.log";

// FIXME: can we use regular pre-post scripts?
define('SUGARCRM_PRE_INSTALL_FILE', "$unzip_dir/scripts/shadow_pre_install.php");
define('SUGARCRM_POST_INSTALL_FILE', "$unzip_dir/scripts/shadow_post_install.php");

echo "\n";
echo "********************************************************************\n";
echo "************** This Upgrade process may take some time *************\n";
echo "********************************************************************\n";
echo "\n";

$errors = array();
chdir($instance_path);
$cwd = $instance_path;

//ini_set('error_reporting',1);
//set_include_path($template.PATH_SEPARATOR.$instance_path.PATH_SEPARATOR.get_include_path());
shadow($template, $instance_path, array('cache', 'custom', 'config.php'));

require_once('include/entryPoint.php');
require_once('include/SugarLogger/SugarLogger.php');
require_once('include/utils/zip_utils.php');
require_once('modules/UpgradeWizard/uw_utils.php');
require_once("modules/Administration/QuickRepairAndRebuild.php");
require_once('modules/Administration/Administration.php');
require_once('modules/Administration/upgrade_custom_relationships.php');
require_once('modules/MySettings/TabController.php');

require("$cwd/config.php");
require_once("sugar_version.php"); // provides $sugar_version & $sugar_flavor
$log = LoggerManager::getLogger('SugarCRM');
$db = DBManagerFactory::getInstance();
$UWstrings		= return_module_language('en_us', 'UpgradeWizard');
$adminStrings	= return_module_language('en_us', 'Administration');
$mod_strings	= $mod_strings_copy = array_merge($adminStrings, $UWstrings);
$app_list_strings = return_app_list_strings_language('en_us');
list($old_version, $origFlavor) = getOldVersion($old_template);

$origVersion = substr(preg_replace("/[^0-9]/", "", $old_version),0,3);
$destVersion = substr(preg_replace("/[^0-9]/", "", $sugar_version),0,3);

if($origFlavor == 'CE' && $sugar_flavor != 'CE') {
	$_SESSION['upgrade_from_flavor'] = 'SugarCE to SugarPro';
	$ce_to_pro_ent = true;
} else {
	$ce_to_pro_ent = false;
}
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

if($configOptions['db_type'] == 'mysql'){
	//Change the db wait_timeout for this session
	$que ="select @@wait_timeout";
	$result = $db->query($que);
	$tb = $db->fetchByAssoc($result);
	writeLog('Wait Timeout before change ***** '.$tb['@@wait_timeout'] );
	$query ="set wait_timeout=28800";
	$db->query($query);
	$result = $db->query($que);
	$ta = $db->fetchByAssoc($result);
	writeLog('Wait Timeout after change ***** '.$ta['@@wait_timeout'] );
}

///////////////////////////////////////////////////////////////////////////////
////	RUN SILENT UPGRADE
ob_start();

///////////////////////////////////////////////////////////////////////////////
////	HANDLE CUSTOMIZATIONS
// temporarily disable Shadow
ini_set('shadow.enabled', 0);
$old_files = getHashes($old_template);
$new_files = getHashes($template);
$moved = 0;
$backup_dir = "$instance_path/cache/backups_".date('Y_m_d_H_i_s');
mkdir_recursive($backup_dir);
// find all files that are customized for current template
$custom_files = findAllFiles(".", array(), false, "", array("./cache", "./custom"));
foreach($custom_files as $custom_file) {
	if($custom_file == 'config.php' || $custom_file == 'config_override.php') continue;
	if(isset($old_files[$custom_file]) && isset($new_files[$custom_file]) &&
		$old_files[$custom_file] != $new_files[$custom_file] && $new_files[$custom_file] != md5_file($custom_file)) {
		// file was updated by upgrade
		writeLog("File $custom_file updated, moving customized version out");
	} elseif(isset($old_files[$custom_file]) && !isset($new_files[$custom_file])) {
		// file was deleted
		writeLog("File $custom_file was deleted in updated version, moving customized version out");
	} elseif(isset($new_files[$custom_file]) && !isset($old_files[$custom_file]) && $new_files[$custom_file] != md5_file($custom_file)) {
		// new file was added
		writeLog("File $custom_file was added in updated version, moving customized version out");
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
// reenable Shadow
ini_set('shadow.enabled', 1);

////////////////COMMIT PROCESS BEGINS///////////////////////////////////////////////////////////////
$trackerManager = TrackerManager::getInstance();
$trackerManager->pause();
$trackerManager->unsetMonitors();

//Clean smarty from cache
@deleteCache();
upgrade_check_errors($errors);

// Run SQL upgrades
runSqlFiles($origVersion,$destVersion);
// Set new version
updateVersions($sugar_version);
///////////////////////////////////////////////////////////////////////////////
////	HANDLE DATABASE
writeLog('About to repair DB tables.');
//Use Repair and rebuild to update the database.
repairTables();
writeLog('DB tables repaired');
if($ce_to_pro_ent) {
	// Add languages
	if(is_file('install/lang.config.php')){
		writeLog('install/lang.config.php exists lets import the file/array insto sugar_config/config.php');
		require_once('install/lang.config.php');

		foreach($config['languages'] as $k=>$v){
			$sugar_config['languages'][$k] = $v;
		}
	} else {
		writeLog('*** ERROR: install/lang.config.php was not found and writen to config.php!!');
	}

	if(isset($sugar_config['sugarbeet']))
	{
		//$sugar_config['sugarbeet'] is only set in COMM
		unset($sugar_config['sugarbeet']);
	}
	if(isset($sugar_config['disable_team_access_check']))
	{
		//no need to write to config.php
		unset($sugar_config['disable_team_access_check']);
	}

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

	writeLog('Set default_max_tabs to 7');
	$sugar_config['default_max_tabs'] = '7';

	writeLog('Set default_theme to Sugar');
	$sugar_config['default_theme'] = 'Sugar';

	writeLog('Upgrading tracker dashlets for sales and marketing start.');
	upgradeDashletsForSalesAndMarketing();
	writeLog('Done upgrading tracker dashlets.');

	// Update admin values
	writeLog("Updating license values");
	$admin = new Administration();
	$admin->saveSetting('license', 'users', 0);
	$key = array('num_lic_oc','key','expire_date');
	foreach($key as $k){
		$admin->saveSetting('license', $k, '');
	}
	writeLog("Done updating license values");
}

$sugar_config['sugar_version'] = $sugar_version;
if(empty($sugar_config['js_lang_version'])) {
	$sugar_config['js_lang_version'] = 1;
} else {
	$sugar_config['js_lang_version'] += 1;
}

ksort( $sugar_config );
if( !write_array_to_file( "sugar_config", $sugar_config, "config.php" ) ) {
	writeLog('*** ERROR: could not write language config information to config.php!!');
	$errors[] = 'Could not write config.php!';
}else{
	writeLog('sugar_config array in config.php has been updated');
}
///////////////////////////////////////////////////////////////////////////////
////	HANDLE POSTINSTALL SCRIPTS
writeLog('Starting post_install()...');

// FIXME: prepare for postinstall script
$file = constant('SUGARCRM_POST_INSTALL_FILE');
if (is_file($file)) {
	include ($file);
    post_install();
}

upgrade_check_errors($errors);


//////////// UPDATE TABS
//check to see if there are any new files that need to be added to systems tab
//retrieve old modules list
writeLog('check to see if new modules exist');
$oldModuleList = array();
$newModuleList = array();
include($old_template.'/include/modules.php');
$oldModuleList = $moduleList;
$moduleList = array();
include('include/modules.php');
$newModuleList = $moduleList;

///////////////////////////////////////////////////////////////////////////////
////	HANDLE DATABASE
$mod_strings = $mod_strings_copy;
writeLog('About to repair the database.');
//Use Repair and rebuild to update the database.
$rac = new RepairAndClear();
$rac->repairAndClearAll(array('clearAll'), array($mod_strings['LBL_ALL_MODULES']), true, false);
writeLog('database repaired');

//include tab controller
$newTB = new TabController();

//make sure new modules list has a key we can reference directly
$newModuleList = $newTB->get_key_array($newModuleList);
$oldModuleList = $newTB->get_key_array($oldModuleList);

//iterate through list and remove commonalities to get new modules
foreach ($newModuleList as $remove_mod){
	if(in_array($remove_mod, $oldModuleList)){
		unset($newModuleList[$remove_mod]);
	}
}

if($ce_to_pro_ent) {
	$must_have_modules= array(
			'Activities'=>'Activities',
			'Calendar'=>'Calendar',
			'Reports' => 'Reports',
			'Quotes' => 'Quotes',
			'Products' => 'Products',
			'Forecasts' => 'Forecasts',
			'Contracts' => 'Contracts',
			'KBDocuments' => 'KBDocuments'
	);
	$newModuleList = array_merge($newModuleList,$must_have_modules);
}

//new modules list now has left over modules which are new to this install, so lets add them to the system tabs
writeLog('new modules to add are '.var_export($newModuleList,true));

//grab the existing system tabs
$tabs = $newTB->get_system_tabs();

//add the new tabs to the array
foreach($newModuleList as $nm ){
	$tabs[$nm] = $nm;
}

//now assign the modules to system tabs
$newTB->set_system_tabs($tabs);
writeLog('module tabs updated');
///////////////////////////////////////////////////////////////////////////////
////	HANDLE JS
// re-minify the JS source files
writeLog("Minyfying JS files.");
$_REQUEST['root_directory'] = $cwd;
$_REQUEST['js_rebuild_concat'] = 'rebuild';
require_once('jssource/minify.php');
writeLog("Done minyfying JS files.");

upgrade_check_errors($errors);

ob_start();
writeLog('Start rebuild relationships.');
@rebuildRelations();
writeLog('End rebuild relationships.');
@createMissingRels();
ob_end_clean();

$admin = new Administration();
$admin->saveSetting('system','adminwizard',1);

///////////////////////////////////////////////////////////////////////////////
////	HANDLE PREFERENCES
writeLog('Start upgrading user preferences.');
$mod_strings_backup = $mod_strings;
upgradeUserPreferences();
$mod_strings = $mod_strings_backup;
writeLog('Done upgrading user preferences.');

///////////////////////////////////////////////////////////////////////////////
////	HANDLE RELATIONSHIPS
writeLog('Start upgrading relationships.');
upgrade_custom_relationships();
writeLog('Done upgrading relationships.');

///////////////////////////////////////////////////////////////////////////////
////	REGISTER UPGRADE
writeLog('Registering upgrade with UpgradeHistory');

// if error was encountered, script should have died before now
// FIXME: what values to use here?
$new_upgrade = new UpgradeHistory();
$new_upgrade->filename = $sugar_version;
$new_upgrade->md5sum = md5_file("sugar_version.php");
$new_upgrade->name = '';
$new_upgrade->description = 'Shadow Silent Upgrade was used to upgrade the instance';
$new_upgrade->type = 'shadow upgrade';
$new_upgrade->version = $sugar_version;
$new_upgrade->status = "installed";
$new_upgrade->manifest = '';
$new_upgrade->save();

upgrade_check_errors($errors);

// rebuild dashlet cache
require_once('include/Dashlets/DashletCacheBuilder.php');
$dc = new DashletCacheBuilder();
$dc->buildCache();

if($ce_to_pro_ent) {
	//add the global team if it does not exist
	$globalteam = new Team();
	$globalteam->retrieve('1');
	require_once('modules/Administration/language/en_us.lang.php');
	if(isset($globalteam->name)){
		writeLog("Global team exists");
	}else{
		$globalteam->create_team("Global", $mod_strings['LBL_GLOBAL_TEAM_DESC'], $globalteam->global_team);
	}

	writeLog(" Start Building private teams");

	upgradeModulesForTeam();
	writeLog(" Finish Building private teams");

	writeLog(" Start Building the team_set and team_sets_teams");
	upgradeModulesForTeamsets();
	writeLog(" Finish Building the team_set and team_sets_teams");

	writeLog(" Start modules/Administration/upgradeTeams.php");
	include('modules/Administration/upgradeTeams.php');
	writeLog(" Finish modules/Administration/upgradeTeams.php");

	if(check_FTS()){
		writeLog("Initializing FTS");
		$GLOBALS['db']->full_text_indexing_setup();
		writeLog("Done initializing FTS");
	}

	// update dashlets
	writeLog("Upgrading dashlets");
	update_iframe_dashlets();
	writeLog("Done upgrading dashlets");

	// create default reports
	writeLog("Creating default reports");
    require_once('modules/Reports/SavedReport.php');
	require_once('modules/Reports/SeedReports.php');
    create_default_reports();
    writeLog("Done creating default reports");

    // Create portal configs
    writeLog("Building portal config");
    require_once("install/install_utils.php");
    handlePortalConfig();
    writeLog("Done building portal config");

    // install default connectors
    writeLog("Initializing default connectors");
	require('modules/Connectors/InstallDefaultConnectors.php');
	writeLog("Done initializing default connectors");

	// repair teams
	writeLog("Initializing teams");
	require_once('modules/Teams/Team.php');
	require_once('modules/Administration/RepairTeams.php');
	process_team_access(false, false,true,'1');
	writeLog("Done initializing teams");

	// repair roles
	writeLog("Initializing roles");
	include('modules/ACLActions/actiondefs.php');
	include('include/modules.php');
	require_once('modules/ACLFields/ACLField.php');
	include("modules/ACL/install_actions.php");
	writeLog("Done initializing roles");

	// set system_system_id
    require_once('modules/Administration/System.php');
    $system = new System();
    $system->system_key = $sugar_config['unique_key'];
    $system->user_id = 1;
    $system->last_connect_date = date($GLOBALS['timedate']->get_date_time_format(),mktime());
    $system_id = $system->retrieveNextKey(false, true);
    $db->query( "INSERT INTO config (category, name, value) VALUES ( 'system', 'system_id', '" . $system_id . "')" );
}

// cleaup all caches at the end
@deleteCache();

//delete cache/modules before rebuilding the relations
cleanFromCache($sugar_config, 'Expressions');

//remove lanugage cache files
require_once('include/SugarObjects/LanguageManager.php');
LanguageManager::clearLanguageCache();

// rebuild dashlet cache
require_once('include/Dashlets/DashletCacheBuilder.php');
$dc = new DashletCacheBuilder();
$dc->buildCache();

//Upgrade connectors
writeLog('Begin upgrade_connectors');
upgrade_connectors();
writeLog('End upgrade_connectors');

if(function_exists('imagecreatetruecolor'))
{
	rebuildSprites(true);
}

$phpErrors = ob_get_contents();
ob_end_clean();
writeLog("**** Potential PHP generated error messages: {$phpErrors}");

writeLog("***** ShadowUpgrade completed successfully.");
echo "********************************************************************\n";
echo "*************************** SUCCESS ********************************\n";
echo "********************************************************************\n";
