<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$RID = $_GET['rid'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Report Viewer</title>
	<meta HTTP-EQUIV="content-type" CONTENT="text/html; charset=UTF-8">
	<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>">
	<style type='text/css'>
	html, body
	{
		height: 100%;
	}
	</style>
	<?php
		include("lib/aereus.lib.js/js_lib.php");
		// ANT lib
		include("lib/js/includes.php");
	?>
	<script language="javascript" type="text/javascript" src="/lib/js/CReportWizard.js"></script>
	<script language='javascript' type='text/javascript'>

	var g_rid = <?php print($RID?$RID:"null"); ?>;
	var g_report = null;
	var objb = null;
	var bodyCon = null;
	var dataCon = null;
	function main()
	{
		bodyCon = document.getElementById("bdy");
		g_report = new CReport();

		// Print toolbar
		// ---------------------------------------------
		var tb = new CToolbar();
		var btn = new CButton("Close", function() { window.close(); }, null, "b1");
		tb.AddItem(btn.getButton(), "left");
		var btn = new CButton("Modify Report", function() { runWizard(); }, null, "b1");
		tb.AddItem(btn.getButton(), "left");
		var btn = new CButton("Edit Security", function() { editSecurity(); }, null, "b3");
		tb.AddItem(btn.getButton(), "left");
		tb.print(document.getElementById('toolbar'));

		// Print Body
		// ---------------------------------------------
		if (g_rid)
		{
			g_report.onload = function()
			{
				printReport();
			}
			g_report.load(g_rid);
		}
		else
		{
			printInstruction();
		}
	}

	function printReport()
	{
		bodyCon.innerHTML = "";

		objb = new CAntObjectBrowser(g_report.obj_type);
		objb.loadView(g_report.view);

		var centerCon = alib.dom.createElement("div", bodyCon);
		alib.dom.styleSet(centerCon, "width", "800px");
		alib.dom.styleSet(centerCon, "margin-left", "auto");
		alib.dom.styleSet(centerCon, "margin-right", "auto");

		// Set title
		document.title = g_report.name;
		var titleCon = alib.dom.createElement("h1", centerCon);
		titleCon.innerHTML = g_report.name;

		if (g_report.fCalculate && g_report.fDisplayChart)	
		{
			g_report.printCubeMicroForm(centerCon);
			var sumCon = alib.dom.createElement("div", centerCon);
			printSummary(sumCon);

			g_report.frmSumCon = sumCon;
			g_report.onCubeUpdate = function()
			{
				printSummary(this.frmSumCon);
			}

			g_report.onConditionChange = function()
			{
				printReport();
			}
		}

		//============================================================================
		//	Print data
		//============================================================================
		dataCon = alib.dom.createElement("div", centerCon);
		alib.dom.styleSet(dataCon, "margin-top", "10px");
		objb.print(dataCon);
	}

	function printSummary(sumCon)
	{
		sumCon.innerHTML = "";

		//============================================================================
		//	Print dimensions
		//============================================================================
		var con = alib.dom.createElement("div", sumCon);
		//g_report.cube.printChart(con);

		//============================================================================
		//	Print graph
		//============================================================================
		var con = alib.dom.createElement("div", sumCon);
		g_report.cube.printChart(con);

		//============================================================================
		//	Print summary
		//============================================================================
		var con = alib.dom.createElement("div", sumCon);
		alib.dom.styleSet(con, "margin-top", "10px");
		g_report.cube.printTable(con);
	}

	function printDetails(con)
	{
		objb.printReport(con);
	}

	function printInstruction()
	{
		var centerCon = alib.dom.createElement("div", bodyCon);
		alib.dom.styleSet(centerCon, "width", "800px");
		alib.dom.styleSet(centerCon, "margin-left", "auto");
		alib.dom.styleSet(centerCon, "margin-right", "auto");
		//centerCon.innerHTML = "Please create report by clicking \"Modify Report\" above.";

		runWizard();
	}

	function runWizard()
	{
		var wiz = new CReportWizard(g_report);
		wiz.onFinished = function(rid, rptname)
		{
			if (!g_rid)
				g_rid = rid;

			g_report.onload = function() { printReport(); }
			g_report.loadCube();
		}
		wiz.showDialog();
	}

	function editSecurity()
	{
		if (g_rid)
		{
			if (g_report.daclId)
				loadDacl(g_report.daclId);
			else
				ALib.Dlg.messageBox("There was a problem loading permissions for this object. Please log out and try again.");
		}
		else
		{
			ALib.Dlg.messageBox("Please create a report before modifying security");
		}
	}
	
	function resized()
	{
	}
	</script>
</head>

<body onload='main()' onresize='resized()' class='popup'>
<?php
	//============================================================================
	//	Print toolbar
	//============================================================================
	echo "<div id='toolbar' class='popup_toolbar'></div>";

	echo "<div id='bdy' class='popup_body'></div>";
?>
</body>
</html>
