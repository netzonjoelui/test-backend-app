<?php

echo "<div><b><font size='4'>Subject: ".$obj->getValue("subject")."</font></b></div>";

echo "<hr />";
echo "<table width='100%'>
	<tr>
	<td align='left'><b>From: ".$obj->getValue("sent_from")."</b></td>
	<td align='right'><b>".$obj->getValue("message_date")."</b></td>
	</tr>
	<tr>
		<td><b>To: ".$obj->getValue("send_to")."</b></td>
	</td>
	<tr>
		<td><b>Subject: ".$obj->getValue("subject")."</b></td>
	</td>
	</table>";
echo "<div style='margin:10px;'>" . $obj->getBody(true) . "</div>";
