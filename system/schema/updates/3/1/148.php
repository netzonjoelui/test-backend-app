<?php
/**
 * Try to move all local files that were stored in the database name folder, to account id folder
 * so we can assure it is unique and works with the new FileSystem code.
 *
 * We used to store files in the account database name, but since we moved the schemas
 * that could cause name collision, so the new FileSystem in netric moved to storing
 * local files in the account id which is always unique.
 */
require_once("lib/AntConfig.php");
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/WorkFlow.php");
require_once("lib/WorkFlow/Action.php");

if (!$ant)
    die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

// Load all files that were in the old directory
$sql = "SELECT id, dat_local_path FROM objects_file WHERE dat_local_path IS NOT NULL AND dat_local_path!=''";
$num = $dbh->GetNumberRows($results = $dbh->Query($sql));
for ($i = 0; $i < $num; $i++)
{
    $row = $dbh->GetRow($results, $i);
    $oldPath1 = AntConfig::getInstance()->data_path . "/files/" . $dbh->dbname . "/" . $row['dat_local_path'];
    $oldPath2 = AntConfig::getInstance()->data_path . "/antfs/" . $dbh->dbname . "/" . $row['dat_local_path'];
    $newPath = AntConfig::getInstance()->data_path . "/files/" . $ant->id . "/" . $row['dat_local_path'];

    // Make sure new directory exists
    $newDir = substr($newPath, 0, strrpos($newPath, '/'));

    // If the file exists in the old directly, then move it
    if (file_exists($oldPath1)) {

        // Create folder
        if (!file_exists($newDir))
            mkdir($newDir, 0777, true);

        // Move file
        rename($oldPath1, $newPath);
        echo "Moved from $oldPath1 to $newPath\n";
    } else if (file_exists($oldPath2)) {

        // Create folder
        if (!file_exists($newDir))
            mkdir($newDir, 0777, true);

        // Move file
        rename($oldPath2, $newPath);
        echo "Moved from $oldPath2 to $newPath\n";
    }
}