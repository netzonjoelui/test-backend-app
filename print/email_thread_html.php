<?php
echo "<div><b><font size='4'>Thread: ".$obj->getValue("subject")."</font></b></div>";

$msgList = new CAntObjectList($ANT->dbh, "email_message", $USER);
$msgList->addCondition("and", "thread", "is_equal", $obj->id);
$msgList->getObjects();
$numMsg = $msgList->getNumObjects();
for($j = 0; $j < $numMsg; $j++)
{
	$msg = $msgList->getObject($j);
	
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
}
