<?php
    require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
    require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
    require_once(dirname(__FILE__).'/../../lib/Ant.php');
    require_once(dirname(__FILE__).'/../../lib/AntUser.php');
    require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
    
	$ant = new Ant();
    $dbh = $ant->dbh;
    $user = new AntUser($dbh, USER_SYSTEM);
    
    $objType = $_GET["obj_type"];
    $oid = $_GET["oid"];
    
    /*echo $query = "update $objType set notes = '$objType:$oid' where id='$oid'";
    $dbh->Query($query);*/
    
    $notes = "$objType:$oid";
    $antObject = new CAntObject($dbh, $objType, $oid, $user);
    $antObject->skipWorkflow = true;
    $antObject->setValue("notes", $notes);
    echo $antObject->save(false);
?>
