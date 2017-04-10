<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	
	$dbh = $ANT->dbh;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$MID = $_REQUEST['mid'];
	$TID = $_REQUEST['tid'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>">
<script language="javascript" type="text/javascript">
	function load()
	{
		window.print();
	}
</script>
</head>
<body onload='load()'>
<?php

	$thread = CAntObject::factory($dbh, "email_thread", $TID, $USER);
	echo "<div><b><font size='4'>Subject: ".$thread->getValue("subject")."</font></b></div>";

	$messNum = 0;
	$olist = new CAntObjectList($dbh, "email_message", $USER);
	$olist->addCondition("and", "thread", "is_equal", $TID);
	$olist->getObjects();
	$num = $olist->getNumObjects();
	for($i = 0; $i < $num; $i++)
	{
		$msg = $olist->getObject($i);
		
		echo "<hr />";
		echo "<table width='100%'>
			<tr>
			<td align='left'><b>From: ".$msg->getValue("sent_from")."</b></td>
			<td align='right'><b>".$msg->getValue("message_date")."</b></td>
			</tr>
			<tr>
				<td><b>To: ".$msg->getValue("send_to")."</b></td>
			</td>
			<tr>
				<td><b>Subject: ".$msg->getValue("subject")."</b></td>
			</td>
			</table>";
		echo "<div style='margin:10px;'>" . $msg->getBody(true) . "</div>";
		$messNum++;
	}
?>
</body>
</html>
