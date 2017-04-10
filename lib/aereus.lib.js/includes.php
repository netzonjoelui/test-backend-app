<?php
	//include("jsmin.php");
	header("Content-Type: text/javascript");
	//echo "var ALIB_ROOT = \"" . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], "/")) . "\";";
	
	$libs = array(
		// jquery libraries
		"jquery-1.10.2.js", 
		"jquery/jquery-ui-1.10.3.custom.min.js",  // Add fx
		"jquery/jquery.datetimepicker.js", 
		"jquery/jquery.tipsy.js", 
		"jquery/jquery.dateformat.js", 
		"jquery/GrowingInput.js",
		"jquery/TextboxList.js", 
		"jquery/TextboxList.Autocomplete.js", 
		"jquery/TextboxList.Autocomplete.Binary.js",
		// codemirrow
		"ui/Editor/codemirror_full.js",
		// alib
		"alib.js",
		"CXml.js", 
		"CAjax.js", 
		"CAjaxRpc.js", 
		"userAgent.js", 
		"CButton.js", 
		"CContentTable.js", 
		"CDatasheet.js", 
		"dom.js", 
		"fx.js", 
		"dateTime.js",
		"CDragAndDrop.js", 
		"CDropdownMenu.js", 
		"CMenuBar.js", 
		"CNavBar.js", 
		"CAdcClient.js",
		"CSplitContainer.js", 
		"CTabs.js", 
		"CToolbar.js", 
		"CToolTable.js", 
		"CWindowFrame.js", 
		"CUsageTracking.js", 
		"CTreeView.js", 
		"CDialog.js", 
		"CRte.js", 
		"CEffect.js", 
		"CAutoComplete.js", 
		"CTextBoxList.js",
		"CAutoCompleteCal.js", 
		"CAutoCompleteTime.js", 
		"CNavHistory.js", 
		"events.js", 
		"events/EventWrapper.js", 
		"net.js", 
		"net/xhr.js", 
	);

	// Ui Classes
	$libs[] = "ui.js";
	$libs[] = "ui/Button.js";
	$libs[] = "ui/Popup.js";
	$libs[] = "ui/ButtonToggler.js";
	$libs[] = "ui/AutoComplete.js";
	$libs[] = "ui/Editor.js";
	$libs[] = "ui/Tooltip.js";
	$libs[] = "ui/SlimScroll.js";
	$libs[] = "ui/Toolbar.js";
	$libs[] = "ui/ToolbarButton.js";
	$libs[] = "ui/ToolbarToggleButton.js";
	$libs[] = "ui/ToolbarSeparator.js";
	$libs[] = "ui/Menu.js";
	$libs[] = "ui/MenuItem.js";
	$libs[] = "ui/PopupMenu.js";
	$libs[] = "ui/FilteredMenu.js";
	$libs[] = "ui/SubMenu.js";
	$libs[] = "ui/MenuButton.js";


	// Third Party
	//$libs[] = "ckeditor/ckeditor.js";
	//$libs[] = "ckeditor/adapters/jquery.js";
	
	foreach ($libs as $lib)
	{
		// If we are working with library base, set the compiled flags
		if ("alib.js" == $lib)
		{
			$code = file_get_contents($lib);
			$code = str_replace("var COMPILED = false;", "var COMPILED = true;", $code);
			echo $code;
		}
		else
		{
			include($lib);
		}
		echo "\n";
	}

	// Manually add ckeditor due to global variable and pathing
	//echo "window.CKEDITOR_BASEPATH = alib.getBasePath() + 'ckeditor'; alert(CKEDITOR_BASEPATH); ";
	//include("ckeditor/ckeditor.js");

	// Set ckeditor variable
	/*
	echo "var oHead = document.getElementsByTagName('head').item(0);";
	echo 'var oScript= document.createElement("script");';
	echo 'oScript.type = "text/javascript";';
	echo 'oScript.src=alib.getBasePath() + "ckeditor/ckeditor.js";';
	echo 'oHead.appendChild(oScript);';
	 */
?>
