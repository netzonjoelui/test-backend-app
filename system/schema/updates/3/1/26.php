<?php
/**
 * Handle moving folder references to new directory
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntFs.php");

if (!$ant)
	die("This script must be called from the system schema manager and ant mut be set");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

// Modify DACL
if (!$dbh->ColumnExists("security_acle", "pname"))
{
	$dbh->Query("ALTER TABLE security_acle ADD COLUMN pname character varying(128);");
	$dbh->Query("update security_acle set pname=(select security_aclp.name from security_aclp where security_aclp.id=security_acle.aclp_id);");
}

if (!$dbh->ColumnExists("security_acle", "dacl_id"))
{
	$dbh->Query("ALTER TABLE security_acle ADD COLUMN dacl_id bigint;");
	$dbh->Query("update security_acle set dacl_id=(select security_aclp.dacl_id from security_aclp where security_aclp.id=security_acle.aclp_id);");
}

// Get antfs
$antfs = new AntFs($dbh, $user);

// Get objects and fields that reference folders
$query = "select app_object_types.name, app_object_type_fields.name as fname
			from app_object_types, app_object_type_fields
			where app_object_types.id=app_object_type_fields.type_id
			and 
			((app_object_type_fields.type='fkey' and app_object_type_fields.subtype='user_file_categories')
			or (app_object_type_fields.type='object' and app_object_type_fields.subtype='folder'))
			and app_object_types.name!='file' and app_object_types.name != 'folder';";
$result = $dbh->Query($query);
$num = $dbh->GetNumberRows($result);
for ($i = 0; $i < $num; $i++)
{
	$row = $dbh->GetRow($result, $i);

	$olist = new CAntObjectList($dbh, $row['name'], $user);
	$olist->addCondition("and", $row['fname'], "is_not_equal", ""); // not null
	$olist->getObjects(0, 5000000);
	$num2 =  $olist->getNumObjects();
	for ($j = 0; $j < $num2; $j++)
	{
		$obj = $olist->getObject($j);
		$oldCid = $obj->getValue($row['fname']);

		if (is_numeric($oldCid) && $oldCid<888029) // make sure it is an old dir
		{
			$antFsPath = sysUfToAntFsGetCatPath($dbh, $oldCid);
			$folder = $antfs->openFolder($antFsPath, true);

			echo "setting ".$row['name'].": $j of $num2 from $oldCid to ".$folder->id."\n";

			$obj->setValue($row['fname'], $folder->id);
			$obj->debug = true;
			$obj->save(false);
		}
	}
}

// Now drop all constraints to user_file_categores and user_files
// ---------------------------------------------------------------------
if ($dbh->constraintExists("contacts_personal", "contacts_personal_folder_fkey"))
	$dbh->Query("ALTER TABLE contacts_personal DROP CONSTRAINT contacts_personal_folder_fkey;");

if ($dbh->constraintExists("customers", "customers_folder_fkey"))
	$dbh->Query("ALTER TABLE customers DROP CONSTRAINT customers_folder_fkey;");

if ($dbh->constraintExists("dc_database_folders", "dc_database_folders_fid_fkey"))
	$dbh->Query("ALTER TABLE dc_database_folders DROP CONSTRAINT dc_database_folders_fid_fkey;");

if ($dbh->constraintExists("email_messages", "email_messages_fid_fkey"))
	$dbh->Query("ALTER TABLE email_messages DROP CONSTRAINT email_messages_fid_fkey;");

if ($dbh->constraintExists("email_video_message_themes", "email_video_message_themes_fidbtn_fkey"))
	$dbh->Query("ALTER TABLE email_video_message_themes DROP CONSTRAINT email_video_message_themes_fidbtn_fkey;");

if ($dbh->constraintExists("email_video_message_themes", "email_video_message_themes_fidftr_fkey"))
	$dbh->Query("ALTER TABLE email_video_message_themes DROP CONSTRAINT email_video_message_themes_fidftr_fkey;");

if ($dbh->constraintExists("email_video_message_themes", "email_video_message_themes_fidhdr_fkey"))
	$dbh->Query("ALTER TABLE email_video_message_themes DROP CONSTRAINT email_video_message_themes_fidhdr_fkey;");

if ($dbh->constraintExists("email_video_messages", "email_video_messages_fid_fkey"))
	$dbh->Query("ALTER TABLE email_video_messages DROP CONSTRAINT email_video_messages_fid_fkey;");

if ($dbh->constraintExists("email_video_messages", "email_video_messages_lfid_fkey"))
	$dbh->Query("ALTER TABLE email_video_messages DROP CONSTRAINT email_video_messages_lfid_fkey;");

if ($dbh->constraintExists("project_template_tasks", "project_template_tasks_fid_fkey"))
	$dbh->Query("ALTER TABLE project_template_tasks DROP CONSTRAINT project_template_tasks_fid_fkey;");

if ($dbh->constraintExists("users", "users_imgid_fkey"))
	$dbh->Query("ALTER TABLE users DROP CONSTRAINT users_imgid_fkey;");

if ($dbh->constraintExists("ic_documents", "video_file_id_fid_fkey"))
	$dbh->Query("ALTER TABLE ic_documents DROP CONSTRAINT video_file_id_fid_fkey;");

if ($dbh->constraintExists("workflow_actions", "workflow_actions_send_email_fid_fkey"))
	$dbh->Query("ALTER TABLE workflow_actions DROP CONSTRAINT workflow_actions_send_email_fid_fkey;");

/**
 * Function used to convert catid of old system to new path string
 * 
 * @return string The path to be opened in the new AntFs system
 */
function sysUfToAntFsGetCatPath(&$dbh, $CATID)
{
	global $USERID;
	$retval = NULL;
	if (is_numeric($CATID))
	{
		$result = $dbh->Query("select name, parent_id, user_id from user_file_categories where id='$CATID'");
			
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			
			//$retval = "<span style='color:#FFFFFF;'>&nbsp;/&nbsp;</span>";
			//if ($row['name'] != '/')
			
			$retval = $row['name'];
			
			if ($row['parent_id'])
			{
				$pre = sysUfToAntFsGetCatPath($dbh, $row['parent_id']);
				if ($pre == '/')
					$retval = $pre.$retval;
				else
					$retval = $pre."/".$retval;
			}
			else
			{
				if ($row['name'] == '/' && is_numeric($row['user_id'])) // this is a user root dir
				{
					$retval = "/System/Users/".$row['user_id'];
				}
			}
		}
		$dbh->FreeResults($result);
	}

	return $retval;
}

$ret = true;
