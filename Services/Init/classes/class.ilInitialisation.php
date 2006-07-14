<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* ILIAS Initialisation Utility Class
* perform basic setup: init database handler, load configuration file,
* init user authentification & error handler, load object type definitions
*
* @author Alex Killing <alex.killing@databay.de>
* @author Sascha Hofmann <shofmann@databay.de>

* @version $Id$
*/
class ilInitialisation
{
	
	/**
	* get common include code files
	*/
	function requireCommonIncludes()
	{
		global $ilBench;
		
		// get pear
		require_once("include/inc.get_pear.php");
		require_once("include/inc.check_pear.php");
		
		//include class.util first to start StopWatch
		require_once "classes/class.ilUtil.php";
		require_once "classes/class.ilBenchmark.php";
		$ilBench =& new ilBenchmark();
		$GLOBALS['ilBench'] =& $ilBench;

		$ilBench->start("Core", "HeaderInclude");
		
		// start the StopWatch
		$GLOBALS['t_pagestart'] = ilUtil::StopWatch();
		
		$ilBench->start("Core", "HeaderInclude_IncludeFiles");
		
		// Major PEAR Includes
		require_once "PEAR.php";
		require_once "DB.php";
		require_once "Auth/Auth.php";
		
		// HTML_Template_IT support
		// (location changed with 4.3.2 & higher)
		@include_once "HTML/ITX.php";
		if (!class_exists("IntegratedTemplateExtension"))
		{
			include_once "HTML/Template/ITX.php";
			include_once "classes/class.ilTemplateHTMLITX.php";
		}
		else
		{
			include_once "classes/class.ilTemplateITX.php";
		}
		require_once "classes/class.ilTemplate.php";
		
		//include classes and function libraries
		require_once "include/inc.db_session_handler.php";
		require_once "classes/class.ilDBx.php";
		require_once "classes/class.ilShibboleth.php";
		require_once "classes/class.ilias.php";
		require_once "classes/class.ilObjUser.php";
		require_once "classes/class.ilFormat.php";
		require_once "classes/class.ilSaxParser.php";
		require_once "classes/class.ilObjectDefinition.php";
		require_once "classes/class.ilStyleDefinition.php";
		require_once "classes/class.perm.php";
		require_once "classes/class.ilTree.php";
		require_once "classes/class.ilLanguage.php";
		require_once "classes/class.ilLog.php";
		require_once "classes/class.ilMailbox.php";
		require_once "classes/class.ilCtrl.php";
		require_once "classes/class.ilConditionHandler.php";
		require_once "classes/class.ilBrowser.php";
		require_once "classes/class.ilFrameTargetInfo.php";
		require_once "Services/Help/classes/class.ilHelp.php";
		require_once "include/inc.ilias_version.php";
		
		//include role based access control system
		require_once "Services/AccessControl/classes/class.ilAccessHandler.php";
		require_once "classes/class.ilRbacAdmin.php";
		require_once "classes/class.ilRbacSystem.php";
		require_once "classes/class.ilRbacReview.php";
		
		// include object_data cache
		require_once "classes/class.ilObjectDataCache.php";
		require_once 'Services/Tracking/classes/class.ilOnlineTracking.php';
				
		// ### AA 03.10.29 added new LocatorGUI class ###
		//include LocatorGUI
		require_once "classes/class.ilLocatorGUI.php";
		
		// include error_handling
		require_once "classes/class.ilErrorHandling.php";
		
		// php5 downward complaince to php 4 dom xml and clone method
		if (version_compare(PHP_VERSION,'5','>='))
		{
			require_once("include/inc.xml5compliance.php");
			//require_once("Services/CAS/phpcas/source/CAS/domxml-php4-php5.php");
			
			require_once("include/inc.xsl5compliance.php");
			require_once("include/inc.php4compliance.php");
		}
		else
		{
			require_once("include/inc.php5compliance.php");
		}
		
		$ilBench->stop("Core", "HeaderInclude_IncludeFiles");
	}
	
	/**
	* This method provides a global instance of class ilIniFile for the 
	* ilias.ini.php file in variable $ilIliasIniFile.
	*
	* It initializes a lot of constants accordingly to the settings in
	* the ilias.ini.php file.
	*/
	function initIliasIniFile()
	{
		global $ilIliasIniFile;
		
		require_once("classes/class.ilIniFile.php");
		$ilIliasIniFile = new ilIniFile("./ilias.ini.php");
		$GLOBALS['ilIliasIniFile'] =& $ilIliasIniFile;
		$ilIliasIniFile->read();
		
		// initialize constants
		define("ILIAS_DATA_DIR",$ilIliasIniFile->readVariable("clients","datadir"));
		define("ILIAS_WEB_DIR",$ilIliasIniFile->readVariable("clients","path"));
		define("ILIAS_ABSOLUTE_PATH",$ilIliasIniFile->readVariable('server','absolute_path'));

		// logging
		define ("ILIAS_LOG_DIR",$ilIliasIniFile->readVariable("log","path"));
		define ("ILIAS_LOG_FILE",$ilIliasIniFile->readVariable("log","file"));
		define ("ILIAS_LOG_ENABLED",$ilIliasIniFile->readVariable("log","enabled"));
		define ("ILIAS_LOG_LEVEL",$ilIliasIniFile->readVariable("log","level"));
  
		// read path + command for third party tools from ilias.ini
		define ("PATH_TO_CONVERT",$ilIliasIniFile->readVariable("tools","convert"));
		define ("PATH_TO_ZIP",$ilIliasIniFile->readVariable("tools","zip"));
		define ("PATH_TO_UNZIP",$ilIliasIniFile->readVariable("tools","unzip"));
		define ("PATH_TO_JAVA",$ilIliasIniFile->readVariable("tools","java"));
		define ("PATH_TO_HTMLDOC",$ilIliasIniFile->readVariable("tools","htmldoc"));
		define ("URL_TO_LATEX",$ilIliasIniFile->readVariable("tools","latex"));
		define ("PATH_TO_FOP",$ilIliasIniFile->readVariable("tools","fop"));
		
		// read virus scanner settings
		switch ($ilIliasIniFile->readVariable("tools", "vscantype"))
		{
			case "sophos":
				define("IL_VIRUS_SCANNER", "Sophos");
				define("IL_VIRUS_SCAN_COMMAND", $ilIliasIniFile->readVariable("tools", "scancommand"));
				define("IL_VIRUS_CLEAN_COMMAND", $ilIliasIniFile->readVariable("tools", "cleancommand"));
				break;
				
			case "antivir":
				define("IL_VIRUS_SCANNER", "AntiVir");
				define("IL_VIRUS_SCAN_COMMAND", $ilIliasIniFile->readVariable("tools", "scancommand"));
				define("IL_VIRUS_CLEAN_COMMAND", $ilIliasIniFile->readVariable("tools", "cleancommand"));
				break;
				
			default:
				define("IL_VIRUS_SCANNER", "None");
				break;
		}

		$this->__buildHTTPPath();
	}
	
	/**
	* builds http path
	*/
	function __buildHTTPPath()
	{
		if($_SERVER['HTTPS'] == 'on')
		{
			$protocol = 'https://';
		}
		else
		{
			$protocol = 'http://';
		}
		$host = $_SERVER['HTTP_HOST'];

		if(!defined('ILIAS_MODULE'))
		{
			$path = pathinfo($_SERVER['REQUEST_URI']);
			if(!$path['extension'])
			{
				$uri = $_SERVER['REQUEST_URI'];
			}
			else
			{
				$uri = dirname($_SERVER['REQUEST_URI']);
			}
		}
		else
		{
			// if in module remove module name from HTTP_PATH
			$path = dirname($_SERVER['REQUEST_URI']);
			
			// dirname cuts the last directory from a directory path e.g content/classes return content
			
			$module = ilUtil::removeTrailingPathSeparators(ILIAS_MODULE);

			$dirs = explode('/',$module);
			$uri = $path;
			foreach($dirs as $dir)
			{
				$uri = dirname($uri);
			}
		}		
		return define('ILIAS_HTTP_PATH',$protocol.$host.$uri);
	}

	
	/**
	* This method determines the current client and sets the
	* constant CLIENT_ID. 
	*/
	function determineClient()
	{
		global $ilIliasIniFile;

		// check whether ini file object exists
		if (!is_object($ilIliasIniFile))
		{
			die ("Fatal Error: ilInitialisation::determineClient called without initialisation of ILIAS ini file object.");
		}

		// set to default client if empty
		if ($_GET["client_id"] != "")
		{
			setcookie("ilClientId", $_GET["client_id"]);
			$_COOKIE["ilClientId"] = $_GET["client_id"];
		}
		else if (!$_COOKIE["ilClientId"])
		{
			// to do: ilias ini raus nehmen
			$client_id = $ilIliasIniFile->readVariable("clients","default");
			setcookie("ilClientId", $client_id);
			$_COOKIE["ilClientId"] = $client_id;
//echo "set cookie";
		}
//echo "-".$_COOKIE["ilClientId"]."-";
		define ("CLIENT_ID", $_COOKIE["ilClientId"]);
	}
	
	/**
	* This method provides a global instance of class ilIniFile for the 
	* client.ini.php file in variable $ilClientIniFile.
	*
	* It initializes a lot of constants accordingly to the settings in
	* the client.ini.php file.
	*
	* Preconditions: ILIAS_WEB_DIR and CLIENT_ID must be set.
	*
	* @return	boolean		true, if no error occured with client init file
	*						otherwise false
	*/
	function initClientIniFile()
	{
		global $ilClientIniFile;

		// check whether ILIAS_WEB_DIR is set.
		if (ILIAS_WEB_DIR == "")
		{
			die ("Fatal Error: ilInitialisation::initClientIniFile called without ILIAS_WEB_DIR.");
		}

		// check whether CLIENT_ID is set.
		if (CLIENT_ID == "")
		{
			die ("Fatal Error: ilInitialisation::initClientIniFile called without CLIENT_ID.");
		}

		$ini_file = "./".ILIAS_WEB_DIR."/".CLIENT_ID."/client.ini.php";

		// get settings from ini file
		require_once("classes/class.ilIniFile.php");
		$ilClientIniFile = new ilIniFile($ini_file);
		$GLOBALS['ilClientIniFile'] =& $ilClientIniFile; 
		$ilClientIniFile->read();

		// if no ini-file found switch to setup routine
		if ($ilClientIniFile->ERROR != "")
		{
			return false;
		}

		// set constants
		define ("DEBUG",$ilClientIniFile->readVariable("system","DEBUG"));
		define ("DEVMODE",$ilClientIniFile->readVariable("system","DEVMODE"));
		define ("ROOT_FOLDER_ID",$ilClientIniFile->readVariable('system','ROOT_FOLDER_ID'));
		define ("SYSTEM_FOLDER_ID",$ilClientIniFile->readVariable('system','SYSTEM_FOLDER_ID'));
		define ("ROLE_FOLDER_ID",$ilClientIniFile->readVariable('system','ROLE_FOLDER_ID'));
		define ("MAIL_SETTINGS_ID",$ilClientIniFile->readVariable('system','MAIL_SETTINGS_ID'));

		define ("SYSTEM_MAIL_ADDRESS",$ilClientIniFile->readVariable('system','MAIL_SENT_ADDRESS')); // Change SS
		define ("MAIL_REPLY_WARNING",$ilClientIniFile->readVariable('system','MAIL_REPLY_WARNING')); // Change SS

		define ("MAXLENGTH_OBJ_TITLE",$ilClientIniFile->readVariable('system','MAXLENGTH_OBJ_TITLE'));
		define ("MAXLENGTH_OBJ_DESC",$ilClientIniFile->readVariable('system','MAXLENGTH_OBJ_DESC'));

		define ("CLIENT_DATA_DIR",ILIAS_DATA_DIR."/".CLIENT_ID);
		define ("CLIENT_WEB_DIR",ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR."/".CLIENT_ID);
		define ("CLIENT_NAME",$ilClientIniFile->readVariable('client','name')); // Change SS

		// build dsn of database connection and connect
		define ("IL_DSN", $ilClientIniFile->readVariable("db","type").
					 "://".$ilClientIniFile->readVariable("db", "user").
					 ":".$ilClientIniFile->readVariable("db", "pass").
					 "@".$ilClientIniFile->readVariable("db", "host").
					 "/".$ilClientIniFile->readVariable("db", "name"));

		return true;
	}
	
	/**
	* handle maintenance mode
	*/
	function handleMaintenanceMode()
	{
		global $ilClientIniFile;
		
		if (!$ilClientIniFile->readVariable("client","access"))
		{
			if (is_file("./maintenance.html"))
			{
				ilUtil::redirect("./maintenance.html");
			}
			else
			{
				// to do: include standard template here
				die('<br /><p style="text-align:center;">The server is not '.
					'available due to maintenance. We apologise for any inconvenience.</p>');
			}
		}
	}
	
	/**
	* initialise database object $ilDB
	*
	* precondition: IL_DSN must be set
	*/
	function initDatabase()
	{
		global $ilDB;

		// check whether ILIAS_WEB_DIR is set.
		if (IL_DSN == "")
		{
			die ("Fatal Error: ilInitialisation::initDatabase called without IL_DSN.");
		}

		// build dsn of database connection and connect
		require_once("classes/class.ilDBx.php");
		$ilDB = new ilDBx(IL_DSN);
		$GLOBALS['ilDB'] =& $ilDB;
	}
	
	
	/**
	* set session handler to db
	*/
	function setSessionHandler()
	{
		global $ilErr;

		// set session handler
		if(ini_get('session.save_handler') != 'user')
		{
			ini_set("session.save_handler", "user");
		}
		if (!db_set_save_handler())
		{
			die("Please turn off Safe mode OR set session.save_handler to \"user\" in your php.ini");
		}
	}
	
	/**
	* initialise $ilSettings object and define constants
	*/
	function initSettings()
	{
		global $ilSetting;
		
		require_once("Services/Administration/classes/class.ilSetting.php");
		$ilSetting = new ilSetting();
		$GLOBALS['ilSetting'] =& $ilSetting;

		// set anonymous user & role id and system role id
		define ("ANONYMOUS_USER_ID", $ilSetting->get("anonymous_user_id"));
		define ("ANONYMOUS_ROLE_ID", $ilSetting->get("anonymous_role_id"));
		define ("SYSTEM_USER_ID", $ilSetting->get("system_user_id"));
		define ("SYSTEM_ROLE_ID", $ilSetting->get("system_role_id"));
		
		// recovery folder
		define ("RECOVERY_FOLDER_ID", $ilSetting->get("recovery_folder_id"));

		// installation id
		define ("IL_INST_ID", $ilSetting->get("inst_id"));

	}
	
	
	/**
	* determine current script and path to main ILIAS directory
	*/
	function determineScriptAndUpDir()
	{
		$this->script = substr(strrchr($_SERVER["PHP_SELF"],"/"),1);
		$dirname = dirname($_SERVER["PHP_SELF"]);
		$ilurl = @parse_url(ILIAS_HTTP_PATH);
		$subdir = substr(strstr($dirname,$ilurl["path"]),strlen($ilurl["path"]));
		$updir = "";

		if ($subdir)
		{
			$num_subdirs = substr_count($subdir,"/");
	
			for ($i=1;$i<=$num_subdirs;$i++)
			{
				$updir .= "../";
			}
		}
		$this->updir = $updir;
	}
	
	/**
	* provide $styleDefinition object
	*/
	function initStyle()
	{
		global $ilBench, $styleDefinition;
		
		// load style definitions
		$ilBench->start("Core", "HeaderInclude_getStyleDefinitions");
		$styleDefinition = new ilStyleDefinition();
		$GLOBALS['styleDefinition'] =& $styleDefinition;
		$styleDefinition->startParsing();
		$ilBench->stop("Core", "HeaderInclude_getStyleDefinitions");
	}
	
	
	/**
	* set skin and style via $_GET parameters "skin" and "style"
	*/
	function handleStyle()
	{
		global $styleDefinition;
		
		if ($_GET['skin']  && $_GET['style'])
		{
			include_once("classes/class.ilObjStyleSettings.php");
			if ($styleDefinition->styleExists($_GET['skin'], $_GET['style']) &&
				ilObjStyleSettings::_lookupActivatedStyle($_GET['skin'], $_GET['style']))
			{
				$_SESSION['skin'] = $_GET['skin'];
				$_SESSION['style'] = $_GET['style'];
			}
		}
		if ($_SESSION['skin'] && $_SESSION['style'])
		{
			include_once("classes/class.ilObjStyleSettings.php");
			if ($styleDefinition->styleExists($_SESSION['skin'], $_SESSION['style']) &&
				ilObjStyleSettings::_lookupActivatedStyle($_SESSION['skin'], $_SESSION['style']))
			{
				$ilias->account->skin = $_SESSION['skin'];
				$ilias->account->prefs['style'] = $_SESSION['style'];
			}
		}
	}
	
	function initUserAccount()
	{
		global $ilUser, $ilLog, $ilAuth;
		
		//get user id
		if (empty($_SESSION["AccountId"]))
		{
			$_SESSION["AccountId"] = $ilUser->checkUserId();
	
			// assigned roles are stored in $_SESSION["RoleId"]
			$rbacreview = new ilRbacReview();
			$GLOBALS['rbacreview'] =& $rbacreview;
			$_SESSION["RoleId"] = $rbacreview->assignedRoles($_SESSION["AccountId"]);
		} // TODO: do we need 'else' here?
		else
		{
			// init user
			$ilUser->setId($_SESSION["AccountId"]);
		}
	
		// load account data of current user
		$ilUser->read();
	}
		
	function checkUserClientIP()
	{
		global $ilUser, $ilLog, $ilAuth;
		
		// check client ip
		$clientip = $ilUser->getClientIP();
		if (trim($clientip) != "" and $clientip != $_SERVER["REMOTE_ADDR"])
		{
			$ilLog ->logError(1,
				$ilias->account->getLogin().":".$_SERVER["REMOTE_ADDR"].":".$message);
			$ilAuth->logout();
			@session_destroy();
			ilUtil::redirect("login.php?wrong_ip=true");
		}
	}
	
	function checkUserAgreement()
	{
		global $ilUser, $ilAuth;

		// are we currently in user agreement acceptance?
		$in_user_agreement = false;
		if (strtolower($_GET["cmdClass"]) == "ilstartupgui" &&
			(strtolower($_GET["cmd"]) == "getacceptance" ||
			(is_array($_POST["cmd"]) &&
			key($_POST["cmd"]) == "getAcceptance")))
		{
			$in_user_agreement = true;
		}
		
		// check wether user has accepted the user agreement
		//	echo "-".$script;
		if (!$ilUser->hasAcceptedUserAgreement() &&
			$ilAuth->getAuth() &&
			!$in_user_agreement &&
			$ilUser->getId() != ANONYMOUS_USER_ID)
		{
			ilUtil::redirect("ilias.php?baseClass=ilStartUpGUI&cmdClass=ilstartupgui&cmd=getAcceptance");
		}
	}
	
	
	/**
	* go to public section
	*/
	function goToPublicSection()
	{
		global $ilAuth;

		// logout and end previous session
		$ilAuth->logout();
		session_unset();
		session_destroy();
		
		// new session and login as anonymous
		$this->setSessionHandler();
		session_start();
		$_POST["username"] = "anonymous";
		$_POST["password"] = "anonymous";
		ilAuthUtils::_initAuth();
		$ilAuth->start();

		if (ANONYMOUS_USER_ID == "")
		{
			die ("Public Section enabled, but no Anonymous user found.");
		}
		if (!$ilAuth->getAuth())
		{
			die("ANONYMOUS user with the object_id ".ANONYMOUS_USER_ID." not found!");
		}

		// if target given, try to go there
		if ($_GET["target"] != "")
		{
			$this->initUserAccount();
			
			// target is accessible -> goto target
			include_once("Services/Init/classes/class.ilStartUpGUI.php");
			if	(ilStartUpGUI::_checkGoto($_GET["target"]))
			{
				ilUtil::redirect(ILIAS_HTTP_PATH.
					"/goto.php?target=".$_GET["target"]);
			}
			else	// target is not accessible -> login
			{
				$this->goToLogin();
			}
		}
		
		$_GET["ref_id"] = ROOT_FOLDER_ID;

		$_GET["cmd"] = "frameset";
		$jump_script = "repository.php";
		$script = $this->updir.$jump_script."?cmd=".$_GET["cmd"]."&ref_id=".$_GET["ref_id"];
		
		// todo do it better, if JS disabled
		echo "<script language=\"Javascript\">\ntop.location.href = \"".$script."\";\n</script>\n";
		exit;
	}
	
	
	/**
	* go to login
	*/
	function goToLogin($a_auth_stat = "")
	{
		session_unset();
		session_destroy();

		$script = $this->updir."login.php?target=".$_GET["target"]."&client_id=".$_COOKIE["ilClientId"].
			"&auth_stat=".$a_auth_stat;

		// todo do it better, if JS disabled
		// + this is, when session "ends", so
		// we should try to prevent some information about current
		// location
		echo "<script language=\"Javascript\">\ntop.location.href = \"".$script."\";\n</script>\n";
		exit;

	}
	
	/**
	* $lng initialisation
	*/
	function initLanguage()
	{
		global $ilBench, $lng, $ilUser;
		
		//init language
		$ilBench->start("Core", "HeaderInclude_initLanguage");
		
		if (is_null($_SESSION['lang']))
		{
			$_GET["lang"] = ($_GET["lang"]) ? $_GET["lang"] : $ilUser->getPref("language");
		}
		
		if ($_POST['change_lang_to'] != "")
		{
			$_GET['lang'] = $_POST['change_lang_to'];
		}
		
		$_SESSION['lang'] = ($_GET['lang']) ? $_GET['lang'] : $_SESSION['lang'];
		
		// prefer personal setting when coming from login screen
		if (is_object($ilUser) && $ilUser->getId() != ANONYMOUS_USER_ID)
		{
			$_SESSION['lang'] = $ilUser->getPref("language");
		}
		
		$lng = new ilLanguage($_SESSION['lang']);
		$GLOBALS['lng'] =& $lng;
		$ilBench->stop("Core", "HeaderInclude_initLanguage");

	}
	
	/**
	* $ilAccess and $rbac... initialisation
	*/
	function initAccessHandling()
	{
		global $ilBench, $rbacsystem, $rbacadmin, $rbacreview;
		
		$ilBench->start("Core", "HeaderInclude_initRBAC");
		$rbacsystem = new ilRbacSystem();
		$GLOBALS['rbacsystem'] =& $rbacsystem;
		$rbacadmin = new ilRbacAdmin();
		$GLOBALS['rbacadmin'] =& $rbacadmin;
		$rbacreview = new ilRbacReview();
		$GLOBALS['rbacreview'] =& $rbacreview;
		$ilAccess =& new ilAccessHandler();
		$GLOBALS["ilAccess"] =& $ilAccess;		
		$ilBench->stop("Core", "HeaderInclude_initRBAC");
	}
	
	
	/**
	* ilias initialisation
	*/
	function initILIAS()
	{
		global $ilDB, $ilUser, $ilLog, $ilErr, $ilClientIniFile, $ilIliasIniFile,
			$ilSetting, $ilias, $https, $ilObjDataCache,
			$ilLog, $objDefinition, $lng, $ilCtrl, $ilBrowser, $ilHelp,
			$ilTabs, $ilMainMenu, $rbacsystem;
		
		// include common code files
		$this->requireCommonIncludes();
		global $ilBench;
		
		
		// set error handler (to do: check preconditions for error handler to work)
		$ilBench->start("Core", "HeaderInclude_GetErrorHandler");
		$ilErr = new ilErrorHandling();
		$GLOBALS['ilErr'] =& $ilErr;
		$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		$ilBench->stop("Core", "HeaderInclude_GetErrorHandler");
		
		
		// prepare file access to work with safe mode (has been done in class ilias before)
		umask(0117);
		
		
		// $ilIliasIniFile initialisation
		$this->initIliasIniFile();
		
		
		// CLIENT_ID determination
		$this->determineClient();
		
		
		// $ilClientIniFile initialisation
		if (!$this->initClientIniFile())
		{
			ilUtil::redirect("./setup/setup.php");	// to do: this could fail in subdirectories
													// this is also source of a bug (see mantis)
		}
		
		
		// maintenance mode
		$this->handleMaintenanceMode();
		
		
		// $ilDB initialisation
		$this->initDatabase();

		// set session handler
		$this->setSessionHandler();
		
		// $ilSetting initialisation
		$this->initSettings();

		// $ilAuth initialisation
		require_once("classes/class.ilAuthUtils.php");
		ilAuthUtils::_initAuth();
		global $ilAuth;
//var_dump($_SESSION);
		// $ilias initialisation
		$ilBench->start("Core", "HeaderInclude_GetILIASObject");
		$ilias =& new ILIAS();
		$GLOBALS['ilias'] =& $ilias;
		$ilBench->stop("Core", "HeaderInclude_GetILIASObject");
//var_dump($_SESSION);
		
		// test: trace function calls in debug mode
		if (DEVMODE)
		{
			if (function_exists("xdebug_start_trace"))
			{
				//xdebug_start_trace("/tmp/test.txt");
			}
		}
		
		
		// $https initialisation
		require_once './classes/class.ilHTTPS.php';
		$https =& new ilHTTPS();
		$GLOBALS['https'] =& $https;
		$https->checkPort();
		
		
		// $ilObjDataCache initialisation
		$ilObjDataCache = new ilObjectDataCache();
		$GLOBALS['ilObjDataCache'] =& $ilObjDataCache;
		
		
		// workaround: load old post variables if error handler 'message' was called
		if ($_SESSION["message"])
		{
			$_POST = $_SESSION["post_vars"];
		}
		
		
		// put debugging functions here
		require_once "include/inc.debug.php";
		
		
		// $ilLog initialisation
		$log = new ilLog(ILIAS_LOG_DIR,ILIAS_LOG_FILE,$ilias->getClientId(),ILIAS_LOG_ENABLED,ILIAS_LOG_LEVEL);
		$GLOBALS['log'] =& $log;
		$ilLog =& $log;
		$GLOBALS['ilLog'] =& $ilLog;
		
		// $objDefinition initialisation
		$ilBench->start("Core", "HeaderInclude_getObjectDefinitions");
		$objDefinition = new ilObjectDefinition();
		$GLOBALS['objDefinition'] =& $objDefinition;
		$objDefinition->startParsing();
		$ilBench->stop("Core", "HeaderInclude_getObjectDefinitions");

		// $ilAccess and $rbac... initialisation
		$this->initAccessHandling();

		// init tree
		$tree = new ilTree(ROOT_FOLDER_ID);
		$GLOBALS['tree'] =& $tree;

		// authenticate & start session
		PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array($ilErr, "errorHandler"));
		$ilBench->start("Core", "HeaderInclude_Authentication");
//var_dump($_SESSION);
		$ilAuth->start();
//var_dump($_SESSION);
		$ilias->setAuthError($ilErr->getLastError());
		$ilBench->stop("Core", "HeaderInclude_Authentication");
		
		// workaround: force login
		if ($_GET["cmd"] == "force_login" || $this->script == "login.php")
		{
			$ilAuth->logout();
			$_SESSION["AccountId"] = "";
			$ilAuth->start();
			$ilias->setAuthError($ilErr->getLastError());
		}
		
		// check correct setup
		if (!$ilias->getSetting("setup_ok"))
		{
			die("Setup is not completed. Please run setup routine again.");
		}

		// $ilUser initialisation (1)
		$ilBench->start("Core", "HeaderInclude_getCurrentUser");
		$ilUser = new ilObjUser();
		$ilias->account =& $ilUser;
		$GLOBALS['ilUser'] =& $ilUser;
		$ilBench->stop("Core", "HeaderInclude_getCurrentUser");
		
		
		// $ilCtrl initialisation
		$ilCtrl = new ilCtrl();
		$GLOBALS['ilCtrl'] =& $ilCtrl;

		// determin current script and up-path to main directory
		// (sets $this->script and $this->updir)
		$this->determineScriptAndUpDir();
		
		// $styleDefinition initialisation and style handling for login and co.
		$this->initStyle();
		if (in_array($this->script,
			array("login.php", "register.php", "view_usr_agreement.php"))
			|| $_GET["baseClass"] == "ilStartUpGUI")
		{
			$this->handleStyle();
		}
		
		
		// handle ILIAS 2 imported users:
		// check ilias 2 password, if authentication failed
		// only if AUTH_LOCAL
//echo "A";
		if (AUTH_CURRENT == AUTH_LOCAL && !$ilAuth->getAuth() && $this->script == "login.php" && $_POST["username"] != "")
		{
			if (ilObjUser::_lookupHasIlias2Password($_POST["username"]))
			{
				if (ilObjUser::_switchToIlias3Password($_POST["username"], $_POST["password"]))
				{
					$ilAuth->start();
					$ilias->setAuthError($ilErr->getLastError());
					ilUtil::redirect("index.php");
				}
			}
		}
		
		// dirty hack for the saving of java applet questions in tests. Unfortunately
		// some changes in this file for ILIAS 3.6 caused this script to stop at the
		// following check (worked in ILIAS <= 3.5).
		// So we return here, because it's only necessary to get the $ilias class for
		// the database connection
		// TODO: Find out what happens here. Talk to Alex Killing
		// Alex: I don't know :-) Helmut should! Maybe this is not needed anymore
		//       due to SOAP based handling of java applet quesions.
		if (strpos($_SERVER["SCRIPT_FILENAME"], "save_java_question_result") !== FALSE)
		{
			$lng = new ilLanguage($_SESSION['lang']);
			$GLOBALS['lng'] =& $lng;
			return;
		}
		
		
		//
		// SUCCESSFUL AUTHENTICATION
		//
//echo "<br>B-".$ilAuth->getAuth()."-".$ilAuth->_sessionName."-";
//var_dump ($session[_authsession]);
		if ($ilAuth->getAuth() && $ilias->account->isCurrentUserActive())
		{
//echo "C";
			$ilBench->start("Core", "HeaderInclude_getCurrentUserAccountData");
//var_dump($_SESSION);
			// get user data
			$this->initUserAccount();
//var_dump($_SESSION);
			// check client IP of user
			$this->checkUserClientIP();
			
			// check user agreement
			$this->checkUserAgreement();
			
			// update last_login date once the user logged in
			if ($this->script == "login.php" ||
				$_GET["baseClass"] == "ilStartUpGUI")
			{
				$ilUser->refreshLogin();
			}
		
			// set hits per page for all lists using table module
			$_GET['limit'] = $_SESSION['tbl_limit'] = (int) $ilUser->getPref('hits_per_page');
			
			// the next line makes it impossible to save the offset somehow in a session for
			// a specific table (I tried it for the user administration).
			// its not posssible to distinguish whether it has been set to page 1 (=offset = 0)
			// or not set at all (then we want the last offset, e.g. being used from a session var).
			// So I added the wrapping if statement. Seems to work (hopefully).
			// Alex April 14th 2006
			if ($_GET['offset'] != "")							// added April 14th 2006
			{
				$_GET['offset'] = (int) $_GET['offset'];		// old code
			}
		
			$ilBench->stop("Core", "HeaderInclude_getCurrentUserAccountData");
		}
		elseif (
					$this->script != "login.php" 
					and $this->script != "shib_login.php"
					and $this->script != "error.php" 
					and $this->script != "index.php"
					and $this->script != "view_usr_agreement.php" 
					and $this->script != "register.php" 
					and $this->script != "chat.php"
					and $this->script != "pwassist.php"
				)
		{
			//
			// AUTHENTICATION FAILED
			//

			// authentication failed due to inactive user?
			if ($ilAuth->getAuth() && !$ilUser->isCurrentUserActive())
			{
				$inactive = true;
			}

			// jump to public section (to do: is this always the indended
			// behaviour, login could be another possibility (including
			// message)
			if ($_GET["baseClass"] != "ilStartUpGUI")
			{
				// $lng initialisation
				$this->initLanguage();

				if ($ilSetting->get("pub_section"))
				{
					$this->goToPublicSection();
				}
				else
				{
					$this->goToLogin($ilAuth->getStatus());
				}
				// we should not get here
				exit;
			}
		}
		
		//
		// SUCCESSFUL AUTHENTICATED or NON-AUTH-AREA (Login, Registration, ...)
		//
		
		// $lng initialisation
		$this->initLanguage();
		
		// store user language in tree
		$GLOBALS['tree']->initLangCode();
		
		// instantiate main template
		$tpl = new ilTemplate("tpl.main.html", true, true);
		$GLOBALS['tpl'] =& $tpl;
		
		
		// ### AA 03.10.29 added new LocatorGUI class ###
		// when locator data array does not exist, initialise
		if ( !isset($_SESSION["locator_level"]) )
		{
			$_SESSION["locator_data"] = array();
			$_SESSION["locator_level"] = -1;
		}
		// initialise global ilias_locator object
		$ilias_locator = new ilLocatorGUI();			// deprecated
		$ilLocator = new ilLocatorGUI();
		$GLOBALS['ilias_locator'] =& $ilias_locator;	// deprecated
		$GLOBALS['ilLocator'] =& $ilLocator;
		
		// load style definitions
		$ilBench->start("Core", "HeaderInclude_getStyleDefinitions");
		$styleDefinition = new ilStyleDefinition();
		$GLOBALS['styleDefinition'] =& $styleDefinition;
		$styleDefinition->startParsing();
		$ilBench->stop("Core", "HeaderInclude_getStyleDefinitions");
				
		// load style sheet depending on user's settings
		$location_stylesheet = ilUtil::getStyleSheetLocation();
		$tpl->setVariable("LOCATION_STYLESHEET",$location_stylesheet);
		$tpl->setVariable("LOCATION_JAVASCRIPT",dirname($location_stylesheet));
		
		// init infopanel
		// to do: revise that
		if ($mail_id = ilMailbox::hasNewMail($_SESSION["AccountId"]))
		{
			$mbox = new ilMailbox($_SESSION["AccountId"]);
			$mail =& new ilMail($_SESSION['AccountId']);
			if($rbacsystem->checkAccess('mail_visible',$mail->getMailObjectReferenceId()))
			{
				$folder_id = $mbox->getInboxFolder();
				
				$_SESSION["infopanel"] = array ("link"	=> "mail_frameset.php?target=".
												htmlentities(urlencode("mail_read.php?mobj_id=".$folder_id."&mail_id=".$mail_id)),
												"text"	=> "new_mail"
												//"img"	=> "icon_mail.gif"
					);
			}
		}
				
		// provide global browser information
		$ilBrowser = new ilBrowser();
		$GLOBALS['ilBrowser'] =& $ilBrowser;
		
		// provide global help object
		$ilHelp = new ilHelp();
		$GLOBALS['ilHelp'] =& $ilHelp;
		
		// main tabs gui
		include_once 'classes/class.ilTabsGUI.php';
		$ilTabs = new ilTabsGUI();
		$GLOBALS['ilTabs'] =& $ilTabs;
		
		// main menu
		include_once 'classes/class.ilMainMenuGUI.php';
		$ilMainMenu = new ilMainMenuGUI("_top");
		$GLOBALS['ilMainMenu'] =& $ilMainMenu;
		
		// Store online time of user
		ilOnlineTracking::_updateAccess($ilUser->getId());
				
		$ilBench->stop("Core", "HeaderInclude");
		$ilBench->save();

	}
}
?>
