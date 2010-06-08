<?php
 if(!defined('sugarEntry'))define('sugarEntry', true);
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Enterprise Subscription
 * Agreement ("License") which can be viewed at
 * http://www.sugarcrm.com/crm/products/sugar-enterprise-eula.html
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
//session_destroy();
require('SugarShadow.php');
SugarShadow::shadow($_SERVER['SERVER_NAME']);
if (version_compare(phpversion(),'5.2.0') < 0) {
	$msg = 'Minimum PHP version required is 5.2.0.  You are using PHP version  '. phpversion();
    die($msg);
}
$session_id = session_id();
if(empty($session_id)){
	@session_start();
}
$GLOBALS['installing'] = true;
define('SUGARCRM_IS_INSTALLING', $GLOBALS['installing']);
$GLOBALS['sql_queries'] = 0;
require_once('include/SugarLogger/LoggerManager.php');
require_once('sugar_version.php');
require_once('include/utils.php');
require_once('install/install_utils.php');
require_once('install/install_defaults.php');
require_once('include/TimeDate.php');
require_once('include/Localization/Localization.php');
require_once('include/SugarTheme/SugarTheme.php');
require_once('include/utils/LogicHook.php');
require_once('data/SugarBean.php');
require_once('include/entryPoint.php');
//check to see if the script files need to be rebuilt, add needed variables to request array
    $_REQUEST['root_directory'] = getcwd();
    $_REQUEST['js_rebuild_concat'] = 'rebuild';
    require_once('jssource/minify.php');

$timedate = new TimeDate();
// cn: set php.ini settings at entry points
setPhpIniSettings();
$locale = new Localization();

if(get_magic_quotes_gpc() == 1) {
   $_REQUEST = array_map("stripslashes_checkstrings", $_REQUEST);
   $_POST = array_map("stripslashes_checkstrings", $_POST);
   $_GET = array_map("stripslashes_checkstrings", $_GET);
}


$GLOBALS['log'] = LoggerManager::getLogger('SugarCRM');
$setup_sugar_version = $sugar_version;
$install_script = true;

///////////////////////////////////////////////////////////////////////////////
//// INSTALL RESOURCE SETUP
$css = 'install/install.css';
$icon = 'include/images/sugar_icon.ico';
$sugar_md = 'include/images/sugar_md_ent.png';
$loginImage = 'include/images/sugarcrm_login.png';
$common = 'install/installCommon.js';

///////////////////////////////////////////////////////////////////////////////
////	INSTALLER LANGUAGE

$supportedLanguages = array(
	'en_us'	=> 'English (US)',
	'ja'	=> 'Japanese - 日本語',
	'fr_fr'	=> 'French - Français',
	'zh_cn' => 'Chinese - 简体中文',
//	'ge_ge'	=> 'German - Deutch',
//	'pt_br'	=> 'Portuguese (Brazil)',
//	'es_es'	=> 'Spanish (Spain) - Español',
);

// after install language is selected, use that pack
$default_lang = 'en_us';
if(!isset($_POST['language']) && (!isset($_SESSION['language']) && empty($_SESSION['language']))) {
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$lang = parseAcceptLanguage();
		if(isset($supportedLanguages[$lang])) {
			$_POST['language'] = $lang;
		} else {
			$_POST['language'] = $default_lang;
	    }
	}
}

if(isset($_POST['language'])) {
	$_SESSION['language'] = strtolower(str_replace('-','_',$_POST['language']));
}

$current_language = isset($_SESSION['language']) ? $_SESSION['language'] : $default_lang;

if(file_exists("install/language/{$current_language}.lang.php")) {
	require_once("install/language/{$current_language}.lang.php");
} else {
	require_once("install/language/{$default_lang}.lang.php");
}

if($current_language != 'en_us') {
	$my_mod_strings = $mod_strings;
	include('install/language/en_us.lang.php');
	$mod_strings = sugarArrayMerge($mod_strings, $my_mod_strings);
}
////	END INSTALLER LANGUAGE
///////////////////////////////////////////////////////////////////////////////

//get the url for the helper link
$help_url = get_help_button_url();



//if this license print, then redirect and exit,
if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'licensePrint')
{
    include('install/licensePrint.php');
    exit ();
}

//check to see if mysqli is enabled
if(function_exists('mysqli_connect')){
    $_SESSION['mysql_type'] = 'mysqli';
}

//if this is a system check, then just run the check and return,
//this is an ajax call and there is no need for further processing
if(isset($_REQUEST['checkInstallSystem']) && ($_REQUEST['checkInstallSystem'])){
    require_once('install/installSystemCheck.php');
    echo runCheck($install_script, $mod_strings);
    return;
}

//if this is a DB Settings check, then just run the check and return,
//this is an ajax call and there is no need for further processing
if(isset($_REQUEST['checkDBSettings']) && ($_REQUEST['checkDBSettings'])){
    require_once('install/checkDBSettings.php');
    echo checkDBSettings();
    return;
}

//maintaining the install_type if earlier set to custom
if(isset($_REQUEST['install_type']) && $_REQUEST['install_type'] == 'custom'){
	$_SESSION['install_type'] = $_REQUEST['install_type'];
}

//set the default settings into session
foreach($installer_defaults as $key =>$val){
    if(!isset($_SESSION[$key])){
        $_SESSION[$key] = $val;
    }
}

// always perform
clean_special_arguments();
print_debug_comment();
$next_clicked = false;
$next_step = 0;

// use a simple array to map out the steps of the installer page flow
$workflow = array(  'welcome.php',
                    'ready.php',
                    'license.php',
                    'installType.php',
);
                  if(isset($_SESSION['oc_install']) &&  $_SESSION['oc_install']) {
                     $_SESSION['setup_db_type'] = 'mysql';
                  }
$workflow[] =  'systemOptions.php';
$workflow[] = 'dbConfig_a.php';
//$workflow[] = 'dbConfig_b.php';

//define web root, which will be used as default for site_url
if($_SERVER['SERVER_PORT']=='80'){
    $web_root = $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
}else{
    $web_root = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
}
$web_root = str_replace("/install.php", "", $web_root);
$web_root = "http://$web_root";

 if(!isset($_SESSION['oc_install']) ||  $_SESSION['oc_install'] == false) {
    $workflow[] = 'siteConfig_a.php';
    if(isset($_SESSION['install_type'])  && !empty($_SESSION['install_type'])  && $_SESSION['install_type']=='custom'){
        $workflow[] = 'siteConfig_b.php';
    }
 } else {
    if(is_readable('config.php')) {
        require_once('config.php');
    }
 }

   // set the form's php var to the loaded config's var else default to sane settings
    if(!isset($_SESSION['setup_site_url'])  || empty($_SESSION['setup_site_url'])){
        if(isset($sugar_config['site_url']) && !empty($sugar_config['site_url'])){
            $_SESSION['setup_site_url']= $sugar_config['site_url'];
        }else{
         $_SESSION['setup_site_url']= $web_root;
        }
    }
    if(!isset($_SESSION['setup_system_name']) || empty($_SESSION['setup_system_name'])){$_SESSION['setup_system_name'] = 'SugarCRM';}
    if(!isset($_SESSION['setup_site_session_path']) || empty($_SESSION['setup_site_session_path'])){$_SESSION['setup_site_session_path']                = (isset($sugar_config['session_dir']))   ? $sugar_config['session_dir']  :  '';}
    if(!isset($_SESSION['setup_site_log_dir']) || empty($_SESSION['setup_site_log_dir'])){$_SESSION['setup_site_log_dir']                     = (isset($sugar_config['log_dir']))       ? $sugar_config['log_dir']      : '.';}
    if(!isset($_SESSION['setup_site_guid']) || empty($_SESSION['setup_site_guid'])){$_SESSION['setup_site_guid']                        = (isset($sugar_config['unique_key']))    ? $sugar_config['unique_key']   :  '';}



    //check if this is an offline client installation
    if(file_exists('config.php')) {
        global $sugar_config;
        require_once('config.php');
        if(isset($sugar_config['disc_client']) && $sugar_config['disc_client'] == true) {
            $workflow[] = 'oc_install.php';
            $_SESSION['oc_install'] = true;
        } else {
            $_SESSION['oc_install'] = false;
        }
    }

  $workflow[] = 'confirmSettings.php';
  $workflow[] = 'performSetup.php';

  if(!isset($_SESSION['oc_install']) ||  $_SESSION['oc_install'] == false){
    if(isset($_SESSION['install_type'])  && !empty($_SESSION['install_type'])  && $_SESSION['install_type']=='custom'){
        //$workflow[] = 'download_patches.php';
        $workflow[] = 'download_modules.php';
    }
  }

    $workflow[] = 'register.php';


// increment/decrement the workflow pointer
if(!empty($_REQUEST['goto'])) {
    switch($_REQUEST['goto']) {
        case $mod_strings['LBL_CHECKSYS_RECHECK']:
            $next_step = $_REQUEST['current_step'];
            break;
        case $mod_strings['LBL_BACK']:
            $next_step = $_REQUEST['current_step'] - 1;
            break;
        case $mod_strings['LBL_NEXT']:
        case $mod_strings['LBL_START']:
            $next_step = $_REQUEST['current_step'] + 1;
            $next_clicked = true;
            break;
        case 'SilentInstall':
            $next_step = 9999;
            break;
		case 'oc_convert':
            $next_step = 9191;
            break;
    }
}
// Add check here to see if a silent install config file exists; if so then launch silent installer
elseif ( is_file('config_si.php') && empty($sugar_config['installer_locked'])) {
    echo <<<EOHTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <meta http-equiv="Content-Style-Type" content="text/css">
   <meta http-equiv="Refresh" content="1; url=install.php?goto=SilentInstall&cli=true">
   <title>{$mod_strings['LBL_WIZARD_TITLE']} {$mod_strings['LBL_TITLE_WELCOME']} {$setup_sugar_version} {$mod_strings['LBL_WELCOME_SETUP_WIZARD']}</title>
   <link REL="SHORTCUT ICON" HREF="{$icon}">
   <link rel="stylesheet" href="{$css}" type="text/css">
</head>
<body>
    <table cellspacing="0" cellpadding="0" border="0" align="center" class="shell">
    <tr>
        <td colspan="2" id="help"><a href="{$help_url}" target='_blank'>{$mod_strings['LBL_HELP']} </a></td></tr>
    <tr>
      <th width="500">
		<p>
		<img src="{$sugar_md}" alt="SugarCRM" border="0">
		</p>
		{$mod_strings['LBL_TITLE_WELCOME']} {$setup_sugar_version} {$mod_strings['LBL_WELCOME_SETUP_WIZARD']}</th>

      <th width="200" height="30" style="text-align: right;"><a href="http://www.sugarcrm.com" target="_blank"><IMG src="{$loginImage}" width="145" height="30" alt="SugarCRM" border="0"></a>
      </th>
    </tr>
    <tr>
      <td colspan="2"  id="ready_image"><IMG src="include/images/install_themes.jpg" width="698" height="247" alt="Sugar Themes" border="0"></td>
    </tr>

    <tr>
      <td colspan="2" id="ready">{$mod_strings['LBL_LAUNCHING_SILENT_INSTALL']} </td>
    </tr>
    </table>
</body>
</html>
EOHTML;
    die();
}



$exclude_files = array('register.php','download_modules.php');
if(isset($next_step) && isset($workflow[$next_step]) && !in_array($workflow[$next_step],$exclude_files) && isset($sugar_config['installer_locked']) && $sugar_config['installer_locked'] == true) {
    $the_file = 'installDisabled.php';
	$disabled_title = $mod_strings['LBL_DISABLED_DESCRIPTION'];
	$disabled_title_2 = $mod_strings['LBL_DISABLED_TITLE_2'];
	$disabled_text =<<<EOQ
		<p>{$mod_strings['LBL_DISABLED_DESCRIPTION']}</p>
		<pre>
			'installer_locked' => false,
		</pre>
		<p>{$mod_strings['LBL_DISABLED_DESCRIPTION_2']}</p>

		<p>{$mod_strings['LBL_DISABLED_HELP_1']} <a href="{$mod_strings['LBL_DISABLED_HELP_LNK']}" target="_blank">{$mod_strings['LBL_DISABLED_HELP_2']}</a>.</p>
EOQ;
}
else{
$validation_errors = array();
// process the data posted
if($next_clicked) {
	// store the submitted data because the 'Next' button was clicked
    switch($workflow[trim($_REQUEST['current_step'])]) {
        case 'welcome.php':
        	$_SESSION['language'] = $_REQUEST['language'];
   			$_SESSION['setup_site_admin_user_name'] = 'admin';
        break;
      case 'license.php':
                $_SESSION['setup_license_accept']   = get_boolean_from_request('setup_license_accept');
                $_SESSION['license_submitted']      = true;
                

           // eventually default all vars here, with overrides from config.php
            if(is_readable('config.php')) {
            	global $sugar_config;
                include_once('config.php');
            }

            $default_db_type = 'mysql';
            $default_db_type = 'oci8';

            if(!isset($_SESSION['setup_db_type'])) {
                $_SESSION['setup_db_type'] = empty($sugar_config['dbconfig']['db_type']) ? $default_db_type : $sugar_config['dbconfig']['db_type'];
            }

            break;
        case 'installType.php':
            $_SESSION['install_type']   = $_REQUEST['install_type'];
            if(isset($_REQUEST['setup_license_key']) && !empty($_REQUEST['setup_license_key'])){
                $_SESSION['setup_license_key']  = $_REQUEST['setup_license_key'];
            }
            $_SESSION['licenseKey_submitted']      = true;



            break;

        case 'systemOptions.php':
            $_SESSION['setup_db_type'] = $_REQUEST['setup_db_type'];
            $validation_errors = validate_systemOptions();
            if(count($validation_errors) > 0) {
                $next_step--;
            }
            break;

        case 'dbConfig_a.php':
            //validation is now done through ajax call to checkDBSettings.php
            if(isset($_REQUEST['setup_db_drop_tables'])){
                $_SESSION['setup_db_drop_tables'] = $_REQUEST['setup_db_drop_tables'];
                if($_SESSION['setup_db_drop_tables']=== true || $_SESSION['setup_db_drop_tables'] == 'true'){
                    $_SESSION['setup_db_create_database'] = false;
                }
            }
            break;

        case 'siteConfig_a.php':
            if(isset($_REQUEST['setup_site_url'])){$_SESSION['setup_site_url']          = $_REQUEST['setup_site_url'];}
            if(isset($_REQUEST['setup_system_name'])){$_SESSION['setup_system_name']    = $_REQUEST['setup_system_name'];}
            $_SESSION['setup_site_admin_user_name']             = $_REQUEST['setup_site_admin_user_name'];
            $_SESSION['setup_site_admin_password']              = $_REQUEST['setup_site_admin_password'];
            $_SESSION['setup_site_admin_password_retype']       = $_REQUEST['setup_site_admin_password_retype'];
            $_SESSION['siteConfig_submitted']               = true;

            $validation_errors = array();
            $validation_errors = validate_siteConfig('a');
            if(count($validation_errors) > 0) {
                $next_step--;
            }
            break;
        case 'siteConfig_b.php':
            $_SESSION['setup_site_sugarbeet_automatic_checks'] = get_boolean_from_request('setup_site_sugarbeet_automatic_checks');

            $_SESSION['setup_site_custom_session_path']     = get_boolean_from_request('setup_site_custom_session_path');
            if($_SESSION['setup_site_custom_session_path']){
                $_SESSION['setup_site_session_path']            = $_REQUEST['setup_site_session_path'];
            }else{
                $_SESSION['setup_site_session_path'] = '';
            }

            $_SESSION['setup_site_custom_log_dir']          = get_boolean_from_request('setup_site_custom_log_dir');
            if($_SESSION['setup_site_custom_log_dir']){
                $_SESSION['setup_site_log_dir']                 = $_REQUEST['setup_site_log_dir'];
            }else{
                $_SESSION['setup_site_log_dir'] = '.';
            }

            $_SESSION['setup_site_specify_guid']            = get_boolean_from_request('setup_site_specify_guid');
            if($_SESSION['setup_site_specify_guid']){
                $_SESSION['setup_site_guid']                    = $_REQUEST['setup_site_guid'];
            }else{
                $_SESSION['setup_site_guid'] = '';
            }
            $_SESSION['siteConfig_submitted']               = true;
            if(isset($_REQUEST['setup_site_sugarbeet_anonymous_stats'])){
                $_SESSION['setup_site_sugarbeet_anonymous_stats'] = get_boolean_from_request('setup_site_sugarbeet_anonymous_stats');
            }else{
                $_SESSION['setup_site_sugarbeet_anonymous_stats'] = 0;
            }

            $validation_errors = array();
            $validation_errors = validate_siteConfig('b');
            if(count($validation_errors) > 0) {
                $next_step--;
            }
            break;
         case 'oc_install.php':
            	$_SESSION['oc_server_url']	= $_REQUEST['oc_server_url'];
            	$_SESSION['oc_username']    = $_REQUEST['oc_username'];
            	$_SESSION['oc_password']   	= $_REQUEST['oc_password'];
				$_SESSION['is_oc_conversion'] = false;

            	//do not allow demo data to be populated during an offline client installation
            	$_SESSION['demoData'] = 'no';
                if(empty($_SESSION['setup_license_key_users'])) {
                    $_SESSION['setup_license_key_users'] = 1;
                }
                if(empty($_SESSION['setup_license_key_expire_date'])) {
                    $_SESSION['setup_license_key_expire_date'] = '2090-12-12';
                }
                if(empty($_SESSION['setup_license_key'])) {
                    $_SESSION['setup_license_key'] = 'sugar';
                }
                if(empty($_SESSION['setup_num_lic_oc'])) {
                    $_SESSION['setup_num_lic_oc'] = 1;
                }
                $_SESSION['licenseKey_submitted']      = true;
                $validation_errors = array();
            	$validation_errors = validate_offlineClientConfig();
            	if(count($validation_errors) > 0) {
               	 $next_step--;
            	}
            break;
}
    }

if($next_step == 9999) {
    $the_file = 'SilentInstall';
}else if($next_step == 9191) {
	$_SESSION['oc_server_url']	= $_REQUEST['oc_server_url'];
    $_SESSION['oc_username']    = $_REQUEST['oc_username'];
    $_SESSION['oc_password']   	= $_REQUEST['oc_password'];
    $the_file = 'oc_convert.php';
}
else{
        $the_file = $workflow[$next_step];

}

switch($the_file) {
    case 'welcome.php':
    case 'license.php':
			//
			// Check to see if session variables are working properly
			//
			$_SESSION['test_session'] = 'sessions are available';
        @session_write_close();
			unset($_SESSION['test_session']);
        @session_start();

			if(!isset($_SESSION['test_session']))
			{
                $the_file = 'installDisabled.php';
				// PHP.ini location -
				$phpIniLocation = get_cfg_var("cfg_file_path");
				$disabled_title = $mod_strings['LBL_SESSION_ERR_TITLE'];
				$disabled_title_2 = $mod_strings['LBL_SESSION_ERR_TITLE'];
				$disabled_text = $mod_strings['LBL_SESSION_ERR_DESCRIPTION']."<pre>{$phpIniLocation}</pre>";
            break;
			}
        // check to see if installer has been disabled
        if(is_readable('config.php') && (filesize('config.php') > 0)) {
            include_once('config.php');
			
            if(!isset($sugar_config['installer_locked']) || $sugar_config['installer_locked'] == true) {
                $the_file = 'installDisabled.php';
				$disabled_title = $mod_strings['LBL_DISABLED_DESCRIPTION'];
				$disabled_title_2 = $mod_strings['LBL_DISABLED_TITLE_2'];
				$disabled_text =<<<EOQ
					<p>{$mod_strings['LBL_DISABLED_DESCRIPTION']}</p>
					<pre>
						'installer_locked' => false,
					</pre>
					<p>{$mod_strings['LBL_DISABLED_DESCRIPTION_2']}</p>

					<p>{$mod_strings['LBL_DISABLED_HELP_1']} <a href="{$mod_strings['LBL_DISABLED_HELP_LNK']}" target="_blank">{$mod_strings['LBL_DISABLED_HELP_2']}</a>.</p>
EOQ;
				                //if this is an offline client installation but the conversion did not succeed,
                //then try to convert again
                if(isset($sugar_config['disc_client']) && $sugar_config['disc_client'] == true && isset($sugar_config['oc_converted']) && $sugar_config['oc_converted'] == false) {
					 header('Location: index.php?entryPoint=oc_convert&first_time=true');
					exit ();
                }
            }
        }
        break;
    case 'register.php':
        session_unset();
        break;
    case 'SilentInstall':
        $si_errors = false;
        pullSilentInstallVarsIntoSession();
        $validation_errors = validate_dbConfig('a');
        if(count($validation_errors) > 0) {
            $the_file = 'dbConfig_a.php';
            $si_errors = true;
        }
        $validation_errors = validate_siteConfig('a');
        if(count($validation_errors) > 0) {
            $the_file = 'siteConfig_a.php';
            $si_errors = true;
        }
        $validation_errors = validate_siteConfig('b');
        if(count($validation_errors) > 0) {
            $the_file = 'siteConfig_b.php';
            $si_errors = true;
        }

        if(!$si_errors){
            $the_file = 'performSetup.php';
        }
        //since this is a SilentInstall we still need to make sure that
        //the appropriate files are writable
        // config.php
        make_writable('./config.php');

        // custom dir
        make_writable('./custom');

        // modules dir
        recursive_make_writable('./modules');

        // data dir
        make_writable('./data');
        make_writable('./data/upload');

        // cache dir
        make_writable('./cache/custom_fields');
        make_writable('./cache/dyn_lay');
        make_writable('./cache/images');
        make_writable('./cache/import');
        make_writable('./cache/layout');
        make_writable('./cache/pdf');
        make_writable('./cache/upload');
        make_writable('./cache/xml');

        // check whether we're getting this request from a command line tool
        // we want to output brief messages if we're outputting to a command line tool
        $cli_mode = false;
        if(isset($_REQUEST['cli']) && ($_REQUEST['cli'] == 'true')) {
            $_SESSION['cli'] = true;
            // if we have errors, just shoot them back now
            if(count($validation_errors) > 0) {
                foreach($validation_errors as $error) {
                    print($mod_strings['ERR_ERROR_GENERAL']."\n");
                    print("    " . $error . "\n");
                    print("Exit 1\n");
                    exit(1);
                }
            }
        }
        $offline_client_install = false;
        if(isset($_REQUEST['oc_install']) && ($_REQUEST['oc_install'] == 'true')) {
            $_SESSION['oc_install'] = true;
        }
        else
        {
        	$_SESSION['oc_install'] = false;
        }
        break;
	}
}


$the_file = clean_string($the_file, 'FILE');
// change to require to get a good file load error message if the file is not available.
require('install/' . $the_file);

?>