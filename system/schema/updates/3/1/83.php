<?php
/**
 * This update removes duplicate folders
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

/**
 * Recurrsive function can run while return is true which means folders were moved
 *
 * @param CDatabase $dbh Handle to account database
 */
function sysMergeDups83(&$dbh)
{
	$movedFolders = false;

	// Get duplicates
	$duplicates = array();
	$result = $dbh->Query("select name, parent_id, count(name) from objects_folder_act group by name, parent_id having count(name) > 1;");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);
		$duplicates[] = array(
			"name" => $row['name'],
			"parent_id" => $row['parent_id'],
		);
	}

	// Process duplicates
	foreach ($duplicates as $folder)
	{
		$firstId = null;
		$result = $dbh->Query("SELECT id FROM objects_folder_act WHERE parent_id='" . $folder['parent_id'] . "' 
								AND name='" . $dbh->Escape($folder['name']) . "' ORDER BY id");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$fldrid = $dbh->GetValue($result, $i, "id");
			if ($firstId == null)
			{
				$firstId = $fldrid;
			}
			else
			{
				// Move all children to first id
				$dbh->Query("UPDATE objects_folder_act SET parent_id='$firstId' WHERE parent_id='$fldrid'");
				$dbh->Query("UPDATE objects_folder_del SET parent_id='$firstId' WHERE parent_id='$fldrid'");
				$dbh->Query("UPDATE objects_file_act SET folder_id='$firstId' WHERE folder_id='$fldrid'");
				$dbh->Query("UPDATE objects_file_del SET folder_id='$firstId' WHERE folder_id='$fldrid'");

				// Rename old folder
				$dbh->Query("UPDATE objects_folder_act SET name='" . $dbh->Escape($folder['name'] . " ($i)") . "' WHERE id='$fldrid'");

				// force subsequent call to make sure no moved folders are duplicates
				$movedFolders = true; 

				echo "\t - Moved from $fldrid to $firstId and named {$folder['name']} ($i)\n";

				// Move references to the folder
				sysMoveFolderReferences83($dbh, $fldrid, $firstId);
			}
		}
	}

	return $movedFolders;
}

/**
 * Update any referenced objects
 *
 * @param CDatabase $dbh Handle to account database
 * @param int $fromId The folder we are moving children from
 * @param int $toId The folder we are moving children to
 */
function sysMoveFolderReferences83(&$dbh, $fromId, $toId)
{
	$otypes = array();
	$result = $dbh->Query("select app_object_types.name, app_object_type_fields.name as fname
								from app_object_types, app_object_type_fields
								where app_object_types.id=app_object_type_fields.type_id
								and 
								((app_object_type_fields.type='object' and app_object_type_fields.subtype='folder'))
								and app_object_types.name!='file' and app_object_types.name != 'folder';");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);
		$otypes[] = array(
			"obj_type" => $row['name'],
			"field_name" => $row['fname'],
		);
	}

	// Query list for old folder id references
	foreach ($otypes as $otype)
	{
		$olist = new CAntObjectList($dbh, $otype['obj_type']);
		$olist->addCondition("and", $otype['field_name'], "is_equal", $fromId);
		$olist->getObjects(0, 10000); // will never exceed 10k in this dataset
		$num = $olist->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{
			$obj = $olist->getObject($i);
			$obj->setValue($otype['field_name'], $toId);
			$obj->save(false);
			echo "\t\t - Moved object " . $otype['obj_type'] . ":" . $obj->getName() . "\n";
		}
	}
}

// Run in a loop until all duplicates are moved
$i = 1;
while (sysMergeDups83($dbh))
{
	echo "\tFinished run $i\n";
	$i++;

	// Check for endless loop
	if ($i > 100000)
	{
		$ret = false; // fail the update for debugging
		break;
	}
}
