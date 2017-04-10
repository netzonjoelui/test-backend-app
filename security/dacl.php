<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/content_table.awp");
	require_once("lib/WindowFrame.awp");
	require_once("lib/CToolTip.awp");
	require_once("lib/Dacl.php");
	require_once("lib/CDropdownMenu.awp");
	require_once("lib/CToolTable.awp");
	require_once("lib/Button.awp");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THNAME = $USER->themeName;

	$DACLID = $_GET['id'];

	if (!$DACLID && $_GET['daclname'])
	{
		if ($_GET['inheritfrom'])
		{
			$DACL = new Dacl($dbh, rawurldecode($_GET['daclname']), false, $OBJECT_FIELD_ACLS);
			if (!$DACL->id)
			{
				$root = new Dacl($dbh, rawurldecode($_GET['inheritfrom']), true, $OBJECT_FIELD_ACLS);
				$DACL = new Dacl($dbh, rawurldecode($_GET['daclname']), true, $OBJECT_FIELD_ACLS);
				$DACL->setInheritFrom($root->id);
			}
			else
				echo "Already created";

			$DACLID = $DACL->id;
		}
		else
		{
			$DACL = new Dacl($dbh, rawurldecode($_GET['daclname']), true, $OBJECT_FIELD_ACLS);
			$DACLID = $DACL->id;
		}
	}

	$FWD = "id=$DACLID";

	// Handle updates and form submissions
	// -------------------------------------------------------------------------------
	//if ($_POST[''])
		//$dbh->Query("update seddurity_dacl set inherit_from=null where id='$DACLID'");

	if ($_GET['dgid'])
		$dbh->Query("delete from security_acle where group_id='".$_GET['dgid']."' and aclp_id in (select id from security_aclp where dacl_id='$DACLID')");

	if ($_GET['duid'])
		$dbh->Query("delete from security_acle where user_id='".$_GET['duid']."' and aclp_id in (select id from security_aclp where dacl_id='$DACLID')");

	if ($_POST['add'])
	{
		str_replace(";", ",", $_POST['add']);
		$parts = explode(",", $_POST['add']);
		foreach ($parts as $part)
		{
			if ($part)
			{
				if (substr($part, 0, 7) == "groups/")
				{
					$gid = UserGroupGetIdFromName($dbh, substr($part, 7), $ACCOUNT);
					if ($gid!=null)
					{
						$dbh->Query("insert into security_acle(group_id, aclp_id) select '$gid' as group_id, id from security_aclp where dacl_id='$DACLID'");
					}
					else
					{
						echo "Group not found!";
					}
				}
				else
				{
					$uid = UserGetIdFromName($dbh, $part, $ACCOUNT);
					if ($uid)
					{
						$dbh->Query("insert into security_acle(user_id, aclp_id) select '$uid' as user_id, id from security_aclp where dacl_id='$DACLID'");
					}
					else
					{
						echo "User not found!";
					}
				}
			}
		}

		$message = "User/group Added!";
	}
	else if (is_array($_POST['permissions']) && ($_GET['user_id'] || $_GET['group_id']))
	{
		$query = "delete from security_acle where aclp_id in (select id from security_aclp where dacl_id='$DACLID')";
		if ($_GET['user_id'])
			$query .= " and user_id='".$_GET['user_id']."'";
		if ($_GET['group_id'])
			$query .= " and group_id='".$_GET['group_id']."'";
		$dbh->Query($query);
		foreach ($_POST['permissions'] as $pid)
		{
			$dbh->Query("insert into security_acle(aclp_id, user_id, group_id) 
						 values('$pid', ".$dbh->EscapeNumber($_GET['user_id']).", ".$dbh->EscapeNumber($_GET['group_id']).");");
		}

		$message = "Permissions updated!";
	}

	$DACL = new Dacl($dbh, null, false);
	$DACL->loadById($DACLID);
	$DACL->clearCache();
	$DACL->loadById($DACLID);

	if ($_POST['inherit_from_id'] && !isset($_POST['inherit_from']))
	{
		$DACL->removeInheritFrom();
		//$dbh->Query("update security_dacl set inherit_from=null, inherit_from_old='".$_POST['inherit_from_id']."' where id='$DACLID'");
	}

	if ($_POST['inherit_from_id'] && isset($_POST['inherit_from']))
	{
		$DACL->setInheritFrom($_POST['inherit_from_id']);
		//$dbh->Query("update security_dacl set inherit_from_old=null, inherit_from='".$_POST['inherit_from_id']."' where id='$DACLID'");
	}

	$permissions = $DACL->getPermissions();
	$users = $DACL->getUsers();
	$groups = $DACL->getGroups();
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>Edit Security</title>
	<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>">
	<style type='text/css'>
	</style>
<?php
	include("../lib/aereus.lib.js/js_lib.php");
?>
	<script language="javascript" type="text/javascript" src="customer_functions.js"></script>
	<script language="javascript" type="text/javascript">
	<?php
		$permstr = "";
		foreach ($permissions as $per)
		{
			if ($permstr) $permstr .= ", ";
			$permstr .= "[".$per['id'].", \"".$per['name']."\", ".$per['parent_id']."]";
		}
		echo "var permissions = [$permstr]\n";


		$ugStr = "";
		$result = $dbh->Query("select id, name from users where active='t'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			if ($ugStr) $ugStr .= ", ";

			$ugStr .= '"'.$row['name'].'"';
		}
		$result = $dbh->Query("select id, name from user_groups");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			if ($ugStr) $ugStr .= ", ";

			$ugStr .= '"groups/'.$row['name'].'"';
		}

		echo "var g_usersArr = [$ugStr];";
	?>

	function checkItem(id)
	{
		var ckb = document.getElementById('per_'+id);
		var parentPerm = null;

		// get this items parent if any
		for (var i = 0; i < permissions.length; i++)
		{
			if (permissions[i][0] == id && permissions[2])
			{
				var parentchk = document.getElementById('per_'+permissions[i][2]);
				if (!ckb.checked)
					parentchk.checked = false;
				// TODO: handle third level relationships (not added to limit looping)
			}
		}

		// handle checking and unchecking of child/parent relationships
		for (var i = 0; i < permissions.length; i++)
		{
			if (permissions[i][2] == id)
			{
				var childchk = document.getElementById('per_'+permissions[i][0]);
				if (childchk)
				{
					childchk.checked = (ckb.checked) ?  true :  false;
					checkItem(permissions[i][0]);
				}
			}
		}
		// TODO: save
	}
	
	function save()
	{
		document.perms.submit();
	}

	function loaded()
	{
		var acobj = CAutoComplete(document.getElementById('add_name'), g_usersArr);
		
	<?php
		if ($message)
		{
			echo "ALib.statusShowAlert(\"$message\", 3000, \"bottom\", \"right\");\n";
		}
	?>
	}
	</script>
</head>
<body class='popup' onload='loaded();'>
<div id='toolbar' class='popup_toolbar'>
<?php
	WindowFrameToolbarStart("100%");
	echo ButtonCreate("Save Changes", "save()", "b2");
	echo ButtonCreate("Close", "window.close();", "b1");
	WindowFrameToolbarEnd();
?>
</div>
<div id='bdy' class='popup_body'>
<?php

	echo "<form name='perms' method='post' action='dacl.php?$FWD&user_id=".$_GET['user_id']."&group_id=".$_GET['group_id']."' autocomplete='off'>";

	if ($DACL->inherit_from)
	{
		echo "<div style='text-align:center;margin:5px;'>
				<input type='checkbox' name='inherit_from' checked onclick='document.perms.submit();'> Inherit Permissions From Parent Object. Uncheck to edit.
			  </div>";
		echo "<input type='hidden' name='inherit_from_id' value='".$DACL->inherit_from."'>";
	}
	else
	{
		if ($DACL->inherit_from_old)
		{
			echo "<div style='text-align:center;margin:5px;'>
					<input type='checkbox' name='inherit_from' onclick='document.perms.submit();'> Inherit Permissions From Parent Object.
				  </div>";
			echo "<input type='hidden' name='inherit_from_id' value='".$DACL->inherit_from_old."'>";
		}

		echo "<div style='margin-bottom:3px;'>Add User/Group <input type='text' id='add_name' name='add' value=''> ".ButtonCreate("Add", "save()")."</div>";
	}

	WindowFrameStart("Users &amp; Groups", "100%");
	///images/themes/".UserGetTheme($dbh, $USERID, 'name')."/deleteTask.gif
	echo "<div class='DynDivMain'>";
	// Groups
	foreach ($groups as $group)
	{
		if ($group['id'] == GROUP_ADMINISTRATORS || $DACL->inherit_from)
			$rem_lnk = "";
		else
			$rem_lnk = "<span onclick=\"document.location='dacl.php?$FWD&dgid=".$group['id']."'\">[remove]</span>";

		echo "<div class='".(($_GET['group_id']==$group['id'])?"DynDivAct":"DynDivInact")."'>
				<table style='width:100%;'><tr>
				<td onclick=\"document.location='dacl.php?$FWD&group_id=".$group['id']."'\">groups/".$group['name']."</td>
				<td style='width:50px;text-align:center;'>$rem_lnk</td>
				</tr></table>
			  </div>";
	}

	// Users
	foreach ($users as $user)
	{
		if ($group['id'] == GROUP_ADMINISTRATORS || $DACL->inherit_from)
			$rem_lnk = "";
		else
			$rem_lnk = "<span onclick=\"document.location='dacl.php?$FWD&duid=".$user['id']."'\">[remove]</span>";

		echo "<div class='".(($_GET['user_id']==$user['id'])?"DynDivAct":"DynDivInact")."'>
				<table style='width:100%;'><tr>
				<td onclick=\"document.location='dacl.php?$FWD&user_id=".$user['id']."'\">".$user['name']."</td>
				<td style='width:50px;text-align:center;'>$rem_lnk</td>
				</tr></table>
			  </div>";
	}
	echo "</div>";

	WindowFrameEnd();

	WindowFrameStart("Permissions", "100%", "0px");
	$tbl = new CToolTable();
	foreach ($permissions as $per)
	{
		//echo $per[0]." - ".$per[1]." - ".$per[2]."<br />";
		$checked = "";
		if ($_GET['user_id'])
		{
			if ($DACL->checkUserPermission($_GET['user_id'], $per['name']))
				$checked = "checked";
		}
		if ($_GET['group_id'])
		{
			if ($DACL->checkGroupPermission($_GET['group_id'], $per['name']))
				$checked = "checked";
		}

		$buf = "<input id='per_".$per['id']."' name='permissions[]' value='".$per['id']."' type='checkbox' $checked ";
		if ($_GET['group_id'] == GROUP_ADMINISTRATORS || (!$_GET['user_id'] && !$_GET['group_id']) || $DACL->inherit_from)
			$buf .= " disabled='true' ";
		$buf .= " onclick=\"checkItem(".$per['id'].");\">";

		$tbl->StartRow();
		$tbl->AddCell($per['name']);
		$tbl->AddCell($buf, true, 'center', null, '50px');
		$tbl->EndRow();
	}
	$tbl->PrintTable();
	WindowFrameEnd();

	echo "</form>";
?>
</div>
</body>
</html>
