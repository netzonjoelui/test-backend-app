<?php
/*===========================================================================
	
	Module:		object_functions

	Purpose:	Various class independent functions for objects in ANT

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.

============================================================================*/
$g_obj_cache = null; // cache in memory for multiple calls
function objGetNameFromId($dbh, $id, $get="name")
{
	global $g_obj_cache;

	if ($g_obj_cache)
	{
		if (isset($g_obj_cache[$dbh->dbname][$id][$get]))
			return $g_obj_cache[$dbh->dbname][$id][$get];
	}
	else
	{
		if (!isset($g_obj_cache[$dbh->dbname]))
			$g_obj_cache[$dbh->dbname] = array();

		$result = $dbh->Query("select id, name, title from app_object_types order by title");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$g_obj_cache[$dbh->dbname][$row['id']] = array("name"=>$row['name'], "title"=>$row['title'], "id"=>$row['id']);
		}
		$dbh->FreeResults($result);

		if (isset($g_obj_cache[$dbh->dbname][$id][$get]))
			return $g_obj_cache[$dbh->dbname][$id][$get];
	}

	return "";
}

function objGetAttribFromName($dbh, $uni, $get="title")
{
	global $g_obj_cache;

	if ($g_obj_cache && $g_obj_cache[$dbh->dbname])
	{
		foreach ($g_obj_cache[$dbh->dbname] as $oid=>$attribs)
		{
			if ($attribs["name"] == $uni)
				return $attribs[$get];
		}	
	}
	else
	{
		if (!isset($g_obj_cache[$dbh->dbname]))
			$g_obj_cache[$dbh->dbname] = array();

		$result = $dbh->Query("select id, name, title from app_object_types order by title");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$g_obj_cache[$dbh->dbname][$row['id']] = array("name"=>$row['name'], "title"=>$row['title'], "id"=>$row['id']);
		}
		$dbh->FreeResults($result);

		foreach ($g_obj_cache[$dbh->dbname] as $oid=>$attribs)
		{
			if ($attribs["name"] == $uni)
				return $attribs[$get];
		}	
	}

	return "";
}

function objGetAssocLabel($dbh, $obj_type, $oid, $USER=null)
{
	$ret = "";

	$obj = new CAntObject($dbh, $obj_type, $oid, $USER);
	$ret = objGetAttribFromName($dbh, $obj_type, "title");
	$ret .= ": ".$obj->getName();
	
	return $ret;
}

function objGetFldIdFromName($dbh, $obj_type, $fldname)
{
	$obj = new CAntObject($dbh, $obj_type);
	$fdef = $obj->fields->getField($fldname);
	return $fdef['id'];
}

/**
 * @depricated Only used in legacy code. Can be removed
 */
function objSplitValue($value)
{
	$parts = explode(":", $value);
	if (count($parts)>1)
	{
		// Check for full name added after bar '|'
		$parts2 = explode("|", $parts[1]);
		if (count($parts2)>1)
		{
			$parts[1] = $parts2[0];
			$parts[2] = $parts2[1];
		}

		return $parts;
	}
	else
		return null;
}

function objGetName($dbh, $obj_type, $oid, $USER=null)
{
	$ret = "";

	$cache = CCache::getInstance();
	$ret = $cache->get($dbh->dbname."/object/getname/".$obj_type."/".$oid);
	
	if (!$ret)
	{
		$obj = CAntObject::factory($dbh, $obj_type, $oid, $USER);
		$ret = $obj->getName();

		$cache->set($dbh->dbname."/object/getname/".$obj_type."/".$oid, $ret);
	}
	
	return $ret;
}

/**************************************************************************
 * Function: 	objFldHeiarchRoot
 *
 * Purpose:		Get the root id for a heiarch field reference (parent)
 *
 * Params:		$dbh
 * 				$idfld = the name of the id field which is usually "id"
 * 				$parentfld = the name of the parent field indicator
 * 				$tbl = the table to query
 **************************************************************************/
function objFldHeiarchRoot($dbh, $idfld, $parentfld, $tbl, $curid)
{
	$ret = $curid;

	if ($curid)
	{
		$result = $dbh->Query("select $parentfld from $tbl where $idfld='$curid'");
		if ($dbh->GetNumberRows($result))
		{
			$val = $dbh->GetValue($result, 0, $parentfld);
			if ($val)
			{
				$ret = objFldHeiarchRoot($dbh, $idfld, $parentfld, $tbl, $val);
			}
		}
	}

	return $ret;
}

/**************************************************************************
 * Function: 	objCreateType
 *
 * Purpose:		Create a new object type
 *
 * Params:		CDatabase $dbh
 * 				string $name = the name of the new object - must be unique
 * 				string $title = the title of the new object
 * 				int appid = application id that owns this object (optional)
 **************************************************************************/
function objCreateType($dbh, $name, $title, $appid = null)
{
	$otid = 0;

	// Prepend with 'co' (custom object) to prevent system object name collision
	$obj_name = "co_".$name;

	// Test to make sure object name does not already exist
	if ($dbh->GetNumberRows($dbh->Query("select id from app_object_types where name='$obj_name'")))
		return $otid; // fail

	$def = new \Netric\EntityDefinition($obj_name);
	$def->title = $title;
	$def->applicationId = $appid;
	$def->system = false;

	// Save the new object type
    $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
	$dm = $sl->get("EntityDefinition_DataMapper");
	$dm->save($def);

	/*
	// Create new object table and insert into app_object_types
	$dbh->Query("CREATE TABLE objtbl_".$obj_name." (id serial, CONSTRAINT objtbl_".$obj_name."s_pkey PRIMARY KEY (id));");
	$results = $dbh->Query("insert into  app_object_types(name, title, object_table, application_id) 
							 values('".$obj_name."', '$title', 'objtbl_".$obj_name."', ".$dbh->EscapeNumber($appid).");
							 select currval('app_object_types_id_seq') as id;");
	if ($dbh->GetNumberRows($results))
	{
		$otid = $dbh->GetValue($results, 0, "id");

		if ($otid)
		{
			// Make sure default feilds are in place
			$odef = new CAntObject($dbh, $obj_name);
			$odef->fields->verifyDefaultFields();

			// Associate object with application
			if ($appid)
				objAssocTypeWithApp($dbh, $otid, $appid);

			// Create associations partition
			$tblName = "object_assoc_$otid";
		}
	}
	*/

	return $otid;
}

/**************************************************************************
 * Function:     objDeleteType
 *
 * Purpose:      Deletes ant object type
 *
 * Params:        CDatabase $dbh
 *                int $otid = the it of the object type to associate
 **************************************************************************/
function objDeleteType($dbh, $otid)
{
    $dbh->Query("delete from app_object_types where id = '$otid' and f_system='f';");
    return true;
}

/**************************************************************************
 * Function: 	objAssocTypeWithApp
 *
 * Purpose:		Associate an object type with an application
 *
 * Params:		CDatabase $dbh
 * 				int $otid = the it of the object type to associate
 * 				int $appid = application id that owns this object
 **************************************************************************/
/**
 * @depricated We now use datamapper - joe
function objAssocTypeWithApp($dbh, $otid, $appid)
{
	if (!is_numeric($otid) || !is_numeric($appid))
		return false;

	if (!$dbh->GetNumberRows($dbh->Query("select id from application_objects where application_id='$appid' and object_type_id='$otid'")))
		$dbh->Query("insert into application_objects(application_id, object_type_id) values('$appid', '$otid');");

	return true;
}
*/
?>
