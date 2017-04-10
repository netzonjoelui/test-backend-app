<?php
	if ($g_headers['X-ANT-CAL-SHARE'])
	{
		echo "<iframe src='/inv_response.php?inline=1&calendar_id=".$g_headers['X-ANT-CAL-SHARE']."' style='width:95%;height:70px;border:0;' frameborder='0' SCROLLING=NO></iframe>";
	}
	if ($g_headers['X-ANT-CON-GRP-SHARE'])
	{
		echo "<iframe src='/inv_response.php?inline=1&contact_group_id=".$g_headers['X-ANT-CON-GRP-SHARE']."' style='width:95%;height:70px;border:0;' frameborder='0' SCROLLING=NO></iframe>";
	}
?>
