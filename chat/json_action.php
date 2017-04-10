<?php
    require_once("../lib/AntConfig.php");
    require_once("ant.php");
    require_once("ant_user.php");    
    require_once("lib/RpcSvr.php");    
    require_once("lib/AntChat/SvrJson.php");    
    
    $dbh = $ANT->dbh;    
    $USERID =  $USER->id;    
    $FUNCTION = $_GET['function'];
    $SID = $_GET['sid'];

    // Log activity - not idle
    UserLogAction($dbh, $USERID);
    
    global $_REQUEST;
    $_REQUEST['function'] = $FUNCTION;
    $svr = new RpcSvr($ANT, $USER);
    $svr->setClass("AntChat_SvrJson");
    $svr->run();
