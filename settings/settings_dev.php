<?php
$SETTINGS = array();

/*******************************************************************************
*	General Options
********************************************************************************/
$settings_no_https = true;
$settings_debug = true;
$settings_login_page = "login.awp";
$settings_localhost = ($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "ant.aereus.com";
$settings_localhost_root = "ant.aereus.com"; 	// Used to compare localhost for third level domain
$settings_server_root = "/home/[username]/ant.aereus.com";
$settings_data_path = "/data/antfiles";
$settings_wbxml_path = "/usr/local/bin";
$settings_php_location = "php"; 				// can be full path if needed
$settings_email_server = "localhost";
$settings_blog_gateway = "www.myablog.com";
$settings_util_gateway = "www.myablog.com";
$settings_support_gateway = "www.aereus.com";	// This should never change
$settings_default_account = "aereus";

// scaled back free version of ant
$settings_mya = true; 							

// Define the include paths
ini_set('include_path', "$settings_server_root/" . PATH_SEPARATOR . ini_get('include_path'));
ini_set("error_log", "$settings_data_path/error.log");


//$settings_login_logo = "http://mail.guarantyrv.com/mail/images/garf.gif";
//$settings_redirect_to = "customers"; // This can be used to forward a user after login

/*******************************************************************************
*	Account Number - Set by session
********************************************************************************/
if (!isset($SETTINGS_NO_SESSION)) // do not use a session for web services
{
	if (isset($_SESSION["ACCOUNT"]))
		$settings_account_number = $_SESSION["ACCOUNT"];
	else
		$settings_account_number = "1004";      // Default
}
else
	$settings_account_number = 1;

/*******************************************************************************
*	Search & Indexing
********************************************************************************/
define("ANT_INDEX_TYPE", "db");  // db, elastic
define("ANT_INDEX_SOLR_HOST", "");
define("ANT_INDEX_ELASTIC_HOST", "");
define("ANT_CACHE_LISTS", false);

/*******************************************************************************
*	Stats
********************************************************************************/
define("STATS_ENABLE", false);
define("STATS_ENGINE", 'STATSD');
define("STATS_DHOST", '192.168.0.5');
define("STATS_DPORT", '8125');
define("STATS_PREFIX", 'dev');

/*******************************************************************************
*	Contact Options
********************************************************************************/
$settings_no_reply = "no-reply@domain.com";
$settings_admin_contact = "admin@domain.com";

/*******************************************************************************
*	Aereus Network Storage Settings
********************************************************************************/
$SETTINGS['ans_server'] = "ans.ant.aereus.com";
$SETTINGS['ans_account'] = "aereus";
$SETTINGS['ans_password'] = "password";

/*******************************************************************************
*	Application settings - determine what programs are enabled
********************************************************************************/
$SETTINGS['app_email'] = true;
$SETTINGS['app_webcontent'] = true;
$SETTINGS['app_webcontent_blogs'] = true;
$SETTINGS['app_webcontent_feeds'] = true;

/*******************************************************************************
*	Database
********************************************************************************/
// Application database
$settings_db_server =  "localhost";
$settings_db_user = "aereus";
$settings_db_password = "kryptos78";
$settings_db_app = (isset($_SESSION["DATABASE"])) ? $_SESSION["DATABASE"] : "aereus_ant"; // Set on authentication

$settings_db_syshost = "localhost";
$settings_db_sysdb = "antsystem";
$settings_db_sysuser = "aereus";
$settings_db_syspass = "kryptos78";
$settings_db_type = "pgsql";
$settings_db_port = "5432";

// Mailgateway database (this will soon be moved to antsystem)
$settings_db_mailserver =  "localhost";
$settings_db_mailuser = "aereus";
$settings_db_mailpassword = "kryptos78";
$settings_db_mailname = "mailsystem";
$settings_db_mailtype = "pgsql";
$settings_db_mailport = "5432";

/*******************************************************************************
*	ALIB Settings
********************************************************************************/
$ALIBPATH = "/lib/aereus.lib.js/";		// Path to js library
// Aereus Network Storage
$ALIB_ANS_SERVER="";
$ALIB_ANS_ACCOUNT="ant";
$ALIB_ANS_PASS="kryptos78";

$ALIB_CACHE_DIR = $settings_data_path."/cache";
$ALIB_USEMEMCACHED = false;
$ALIB_MEMCACHED_SVR = "192.168.0.31";

// For saving sessions in database, will use memcached if defined
$ALIB_SESS_USEDB = false;
$ALIB_SESS_DB_SERVER = "dbsrv.aereus.com";
$ALIB_SESS_DB_NAME = "sessdb";
$ALIB_SESS_DB_USER = "admin";
$ALIB_SESS_DB_PASS = "pass";

/*******************************************************************************
*	MODULES:
********************************************************************************/

// CUSTOMERS
// =========================================================================
$SETTINGS['customers_title'] = "Customer"; // Use for chaning to something like "Members"
?>
