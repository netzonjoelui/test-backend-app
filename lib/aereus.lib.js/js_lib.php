<?php

	echo "<script type=\"text/javascript\">var ALIB_ROOT = \"$ALIBPATH\";</script>\n";
	
	$libs = array("jquery.min.js", "jquery/jquery.datetimepicker.js", "jquery/GrowingInput.js",
				  "jquery/TextboxList.js", "jquery/TextboxList.Autocomplete.js", "jquery/TextboxList.Autocomplete.Binary.js",
				  "CAjax.js", "CAjaxRpc.js", "userAgent.js", "CButton.js", "CContentTable.js", "CDatasheet.js", 
				  "dom.js", "CDragAndDrop.js", "CDropdownMenu.js", "CMenuBar.js", "CNavBar.js", "CAdcClient.js",
				  "CSplitContainer.js", "CTabs.js", "CToolbar.js", "CToolTable.js", "CWindowFrame.js", 
				  "CUsageTracking.js", "CTreeView.js", "CDialog.js", "CRte.js", "CEffect.js", "CAutoComplete.js", 
				  "CTextBoxList.js",
				  "CAutoCompleteCal.js", "CAutoCompleteTime.js", "CNavHistory.js", "alib.js");

	//foreach ($libs as $lib)
		//echo '<script type="text/javascript" src="'.$ALIBPATH.$lib.'"></script>'."\n";
		//include($lib);
	echo '<script type="text/javascript" src="'.$ALIBPATH."includes.php".'"></script>'."\n";
	echo '<script type="text/javascript" src="'.$ALIBPATH."ckeditor/ckeditor.js".'"></script>'."\n";
?>
