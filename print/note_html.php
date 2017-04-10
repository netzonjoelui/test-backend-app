<?php

echo "<div><b><font size='4'>".$obj->getValue("name")."</font></b></div>";

echo "<hr />";
echo "<table width='100%'>
	<tr>
		<td align='left'><b>".$obj->getValue("website")."</b></td>
	</tr>
	</table>";
echo "<div style='margin:10px;'>" . $obj->getValue("body") . "</div>";
