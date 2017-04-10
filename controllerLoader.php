<?php
	// Set headers to allow CORS since we are using /svr resources in multiple clients
	// @see http://www.html5rocks.com/en/tutorials/cors/#toc-adding-cors-support-to-the-server
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
	header("Access-Control-Allow-Headers: Authentication");

    //require_once("lib/AntConfig.php");
    require_once("lib/AntConfig.php");
    require_once("ant.php");
    require_once("ant_user.php");    
    require_once("lib/RpcSvr.php");    
    require_once("lib/Controller.php");    
    
    $dbh = $ANT->dbh;    
    $USERID =  $USER->id;    
    $controller = $_GET['controller'];
    $apimodule = isset($_GET['apim']) ? $_GET['apim'] : null;



	if ($apimodule)
	{
		$path = "controllers/api/$apimodule/".$controller."Controller.php";

		// Get api version of the interface for this module
		$controller = "Api_".ucfirst(strtolower($apimodule))."_".$controller;

	}
	else
	{
		$path = "controllers/".$controller."Controller.php";
	}

	// Load controller class
	if (file_exists($path))
	{
		include($path);
	}
	else
	{
		echo "Controller not found";
		exit;
	}

    // Log activity - not idle
    UserLogAction($dbh, $USERID);
    
    $svr = new RpcSvr($ANT, $USER);
    $svr->setClass($controller."Controller");
    $svr->run();
