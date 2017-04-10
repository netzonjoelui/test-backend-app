<?php
	function TableContentOpen($width, $title, $height = NULL, $cellpadding = "2")
	{
		global $HTTP_SERVER_VARS;
		$docRoot = $HTTP_SERVER_VARS['DOCUMENT_ROOT'];

		echo "<table style='border-width:1px; border-style:solid; border-left-color: #A3C7E2; border-left-width:1px; 
				border-top-width:1px; border-top-color:#A3C7E2; border-bottom-color: #A3C7E2; border-bottom-width:1px;  
				border-right-color:#A3C7E2; border-right-width:1px;
		 		";
		if ($height) echo " height:$height;";	
		if ($width) echo " width:$width;";		
		echo	"' cellpadding='0' cellspacing='0' border='0'>
			   <tr style='background-color:#FFFFFF; background-image: url(\"/images/templates/windowHeaderShade_blue.gif\"); 
			   		background-position:0px 0px; background-repeat: repeat-x;'>
			    <td style='height:18px;background-color:#FFFFFF;' align='left'>
				 <table width='100%'style='height:18px;' cellpadding='0' cellspacing='0' border='0'>
				  <tr>
				   <td style='font-family: Arial, Helvetica, sans-serif; font-size:12px; font-weight: bold; color: #000000; padding-left: 7px;'>$title</td>
			      </tr>
			     </table>
			    </td>
			   </tr>
			   <tr valign='top'>
			    <td>
				 <table style='background-color:#FFFFFF; padding:0px; background-image:url(\"/images/templates/windowBodyShade.gif\"); background-position:0px 0px; 
						background-repeat:repeat-x;height:100%' border='0' cellpadding='$cellpadding' cellspacing='0' width='100%'>
				  <tr valign='top'>
				   <td>";
	}
						
	
	function TableContentClose($mdMenu = NULL)
	{
		global $HTTP_SERVER_VARS;
		$docRoot = $HTTP_SERVER_VARS['DOCUMENT_ROOT'];

		echo "</td></tr></table></td></tr><tr><td style='background-color:#FFFFFF;height:1px'></td>
			   </tr><tr style='background-color:#FFFFFF;); 
			   	background-position:0px 0px; background-repeat: repeat-x;' valign='bottom'><td style='height:10px' align='left' nowrap>";
		if ($mdMenu) echo $mdMenu;
		echo "</td></tr></table>";
	}
?>