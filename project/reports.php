<?php
	// DEPRICATED
	// THis is only kept around to see an example of the gantt chart

	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAutoComplete.awp");
	require_once("lib/CDropdownMenu.awp");
	require_once("lib/CToolTable.awp");	
	require_once("lib/content_table.awp");
	require_once("lib/WindowFrame.awp");
	require_once("lib/CAntFs.awp");
	require_once("lib/CAutoCompleteCal.awp");
	require_once("lib/CDropdownMenu.awp");
	require_once("lib/Dacl.php");
	require_once("calendar/calendar_functions.awp");
	require_once("project_functions.awp");

	// Aereus.lib.php libraries
	require_once("../lib/aereus.lib.php/CChart.php");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THEMENAME = $USER->themeName;
	$USER = new AntUser($dbh);

    $VIEW = ($_REQUEST['view']) ? $_REQUEST['view'] : "my";

	$DATE_FROM = ($_POST['date_from']) ? $_POST['date_from'] : date("m/d/Y", strtotime("-1 month"));
	$DATE_FROM_TS = strtotime($DATE_FROM);
	$DATE_TO = ($_POST['date_to']) ? $_POST['date_to'] : date("m/d/Y", strtotime("+4 months"));
	$DATE_TO_TS = strtotime($DATE_TO);

	$DAYS_BETWEEN = CalGetDaysBetweenDates($DATE_FROM, $DATE_TO);
	$WEEKS_BETWEEN = CalGetDaysBetweenDates($DATE_FROM, $DATE_TO);

	if (is_numeric($VIEW))
		$CHILD_TEAMS = UserTeamsGetChildrenArray($dbh, $VIEW);
?>		
<html>
<head>
	<title>Project Reports</title>
	
	<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css') ?>">
<?php
	include("../lib/aereus.lib.js/js_lib.php");
?>
	<script language="javascript" type="text/javascript">
	function DeleteProject(act, id)
	{
		if (confirm("Are you sure you want to delete this project?"))
		{
			document.location = "projects.awp?action="+act+"&dpid="+id;
		}
	}
	function DeleteMember(act, id)
	{
		if (confirm("Are you sure you want to delete this project?"))
		{
			document.location = "projects.awp?action="+act+"&dmid="+id;
		}
	}
	</script>
</head>

<body class='appTopSpacer'>
<?php
	/********************************************************************************************
	*	Begin Project Report List
	*********************************************************************************************/
	TableContentOpen("100%", "Project Reports", NULL, 3);

    WindowFrameToolbarStart("100%");
    echo "<form method='post' action='reports.php?$FWD' name='report'>";

    echo " View: ";
    echo "<select name='view' onchange='document.report.submit();'>";
    echo "<option id='my'>My Projects</option>";
    // Get teams
	echo UserGetTeamOptionStr($dbh, $USER, $_REQUEST['view']);

	/*
    $result = $dbh->Query("select id, name from user_teams where account_id='$ACCOUNT'");
    $num = $dbh->GetNumberRows($result);
    for ($i = 0; $i < $num; $i++)
    {
        $row = $dbh->GetRow($result, $i);
        $DACL_TEAM = new Dacl($dbh, "teams/".$row['id']);
        if ($DACL_TEAM->checkAccess($USERID, "View Team"))
        {
            echo "<option value='".$row['id']."' ".(($_REQUEST['view']==$row['id'])?'selected':'').">".$row['name']."</option>";
		}
    }
    $dbh->FreeResults($result);
	*/

    echo "</select>";

	/*
    echo "Report: ";
    echo "<select name='type' onchange='document.report.submit();'>";
    $types = array("gantt"=>"Project Timeline (Gantt)");
    foreach ($types as $typename=>$typetitle)
        echo "<option id='gantt' ".(($_REQUEST['type']==$typename)?'selected':'').">$typetitle</option>";
    echo "</select>";
	*/

    echo "</form>";
    WindowFrameToolbarEnd();

	echo "<div style='height:3px;'></div>";

	WindowFrameStart("Filter", '100%');
	echo "<form name='filter' method='post' action='reports.php?$FWD&view=".$_GET['view']."'>";
	// Date from
	$ac_dfrom = new CAutoCompleteCal("date_from");
	echo "<span class='formLabel'>Date From: </span><input type='text' size='12' name='date_from' id='date_from' value='$DATE_FROM'>".$ac_dfrom->GetAc();
	// Date to
	$ac_dto = new CAutoCompleteCal("date_to");
	echo "<span class='formLabel'>Date To: </span><input type='text' size='12' name='date_to' id='date_to' value='$DATE_TO'>".$ac_dto->GetAc();
	// Owner
	if (is_numeric($VIEW)) // != my
	{
		echo "<span class='formLabel'>Owner: </span><select name='owner_id'><option value=''>All</option>";

		$users_query = "(team_id='$VIEW'";
		for ($i = 0; $i < count($CHILD_TEAMS); $i++)
			$users_query .= " or team_id='".$CHILD_TEAMS[$i]."'";
		$users_query .= ") ";

		$result = $dbh->Query("select id, name from users where $users_query order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			echo "<option value='".$row['id']."' ";
			if ($_REQUEST['owner_id'] == $row['id']) echo "selected";
			echo ">".$row['name']."</option>";
		}
		$dbh->FreeResults($result);
		echo "</select>";
	}


	echo "&nbsp;";
	echo ButtonCreate("Update", "SUBMIT:filter:go");

	echo "</form>";
	WindowFrameEnd();

	echo "<div align='center'>";

	// Project timeline
	// ----------------------------------------------------------------------
	$chart = new CChart("Gantt");

	$cdata = $chart->creatXmlData();
	$cdata->setProcessAttrib("headerText", "Project");
	$cdata->setProcessAttrib("fontSize", "11");
	$cdata->setProcessAttrib("isBold", "1");
	$cdata->setTaskAttrib("showname", "0");

	$catset = $cdata->addCategorySet();
	$catset->addCategory("Projects Timeline", null, "$DATE_FROM", "$DATE_TO");

	// Print months
	$catset2 = $cdata->addCategorySet();
	$cur_time = $DATE_FROM_TS;
	for ($i = 0; $cur_time <= $DATE_TO_TS; $i++)
	{
		$cur_month = date("m", $cur_time);
		$cur_year = date("Y", $cur_time);
		$cur_day = date("d", $cur_time);

		if (strtotime("+1 month", $cur_time) > $DATE_TO_TS)
			$last_day = date("d", $DATE_TO_TS);
		else
			$last_day = date("d", strtotime("-1 day", strtotime("+1 month", strtotime("$cur_month/1/$cur_year"))));

		$catset2->addCategory(date("M", $cur_time), null, "$cur_month/$cur_day/$cur_year", "$cur_month/$last_day/$cur_year");

		$next_tmp = strtotime("+1 month", $cur_time);
		$cur_time = strtotime(date("m", $next_tmp)."/1/".date("Y", $next_tmp));
	}

	if (is_numeric($VIEW))
	{
		$users_query = "(select id from users where team_id='$VIEW'";
		for ($i = 0; $i < count($CHILD_TEAMS); $i++)
			$users_query .= " or team_id='".$CHILD_TEAMS[$i]."'";
		$users_query .= ") ";

		$query = "select id, name, date_started, to_char(date_started, 'MM/DD/YYYY') as date_started_fmt,
		   			date_deadline, to_char(date_deadline, 'MM/DD/YYYY') as date_deadline_fmt from projects where 
					(user_id in $users_query or id in (select project_id from project_membership where user_id in $users_query and accepted='t'))
					and date_deadline is not null and date_deadline>='$DATE_FROM' and date_started<='$DATE_TO';";
	}
	else // my
	{
		$query = "select id, name, date_started, to_char(date_started, 'MM/DD/YYYY') as date_started_fmt,
		   			date_deadline, to_char(date_deadline, 'MM/DD/YYYY') as date_deadline_fmt from projects where 
					(user_id='$USERID' or id in (select project_id from project_membership where user_id='$USERID' and accepted='t'))
					and date_deadline is not null and date_deadline>='$DATE_FROM' and date_started<='$DATE_TO';";
	}

	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);
		//echo $row['name']."<br />";

		$cdata->addProcess(stripslashes($row['name']), $row['id']); // ID has to be unique
		
		if (strtotime($row['date_started_fmt']) < $DATE_FROM_TS)
			$row['date_started_fmt'] = $DATE_FROM;
		if (strtotime($row['date_deadline_fmt']) > $DATE_TO_TS)
			$row['date_deadline_fmt'] = $DATE_TO;
		$cdata->addTask($row['date_started_fmt'], $row['date_deadline_fmt'], $row['id'], stripslashes($row['name']), "0", "project.awp?pid=".$row['id']);
	}
	$dbh->FreeResults($result);

	$height = ($num*20)+150;
	echo $chart->getChart(800, $height);

	/*
	WindowFrameStart("Outstanding Projects", '800px', "0px");
	if (is_numeric($VIEW))
	{
		$users_query = "(select id from users where team_id='$VIEW'";
		for ($i = 0; $i < count($CHILD_TEAMS); $i++)
			$users_query .= " or team_id='".$CHILD_TEAMS[$i]."'";
		$users_query .= ") ";

		$query = "select id, name, date_started, to_char(date_started, 'MM/DD/YYYY') as date_started_fmt,
		   			date_deadline, to_char(date_deadline, 'MM/DD/YYYY') as date_deadline_fmt from projects where 
					(user_id in $users_query or id in (select project_id from project_membership where user_id in $users_query and accepted='t'))
					and date_deadline is not null and date_deadline>='$DATE_FROM' and date_started<='$DATE_TO';";
	}
	else // my
	{
		$query = "select id, name, date_started, to_char(date_started, 'MM/DD/YYYY') as date_started_fmt,
		   			date_deadline, to_char(date_deadline, 'MM/DD/YYYY') as date_deadline_fmt from projects where 
					(user_id='$USERID' or id in (select project_id from project_membership where user_id='$USERID' and accepted='t'))
					and date_deadline is not null and date_deadline>='$DATE_FROM' and date_started<='$DATE_TO';";
	}
	echo "<div style='padding:3px;'>There are no outstanding projects</div>";
	WindowFrameEnd();

	WindowFrameStart("Outstanding Tasks", '800px', "0px");
	echo "<div style='padding:3px;'>There are no outstanding tasks</div>";
	WindowFrameEnd();
	*/


	/*
	$chart = new CChart("Gantt");

	$cdata = $chart->creatXmlData();
	$cdata->setProcessAttrib("headerText", "Project");
	$cdata->setProcessAttrib("fontSize", "11");
	$cdata->setProcessAttrib("isBold", "1");
	
	//$catset = $cdata->addCategorySet();
	//$catset->addCategory("2009", null, "10/15/2009 00:00:00", "12/01/2009 23:59:59");
	//$catset->addCategory("2010", null, "01/01/2010 00:00:00", "03/15/2010 23:59:59");

	$catset = $cdata->addCategorySet();
	$catset->addCategory("Feb", null, "10/15/2009", "10/31/2009");
	$catset->addCategory("Mar", null, "11/01/2009", "11/30/2009");
	$catset->addCategory("Apr", null, "12/01/2009", "12/31/2009");
	$catset->addCategory("May", null, "01/01/2010", "01/31/2010");
	$catset->addCategory("Jun", null, "02/01/2010", "02/28/2010");
	$catset->addCategory("Jul", null, "03/01/2010", "03/15/2010");

	$catset->addCategory("Feb", null, "02/1/2007 00:00:00", "02/28/2007 23:59:59");
	$catset->addCategory("Mar", null, "03/01/2007 00:00:00", "03/31/2007 23:59:59");
	$catset->addCategory("Apr", null, "04/01/2007 00:00:00", "04/30/2007 23:59:59");
	$catset->addCategory("May", null, "05/01/2007 00:00:00", "05/31/2007 23:59:59");
	$catset->addCategory("Jun", null, "06/01/2007 00:00:00", "06/30/2007 23:59:59");
	$catset->addCategory("Jul", null, "07/01/2007 00:00:00", "07/31/2007 23:59:59");
	$catset->addCategory("Aug", null, "08/01/2007 00:00:00", "08/31/2007 23:59:59");

	$cdata->addProcess("Project 1", 1); // ID has to be unique
	$cdata->addProcess("Project 2", 2); // ID has to be unique
	$cdata->addProcess("Project 3", 3); // ID has to be unique


	$cdata->addTask("02/04/2007 00:00:00", "04/06/2007 00:00:00", 1, "Project 1", "0");
	$cdata->addTask("02/04/2007 00:00:00", "02/10/2007 00:00:00", 2, "Project 2", "0");
	$cdata->addTask("02/08/2007 00:00:00", "02/19/2007 00:00:00", 3, "Project 3", "0");

	$cdata->addTrendline("08/14/2007 00:00:00", null, "Today", "333333", 2);
	//$cdata->addTrendline("05/3/2007 00:00:00", "05/10/2007 00:00:00", "Vacation", "FF5904", null, "1", 20);

	echo $chart->getChart(800, 400);
	 */

	echo "</div>";

	// Close the content table
	TableContentClose();
?>
</body>
</html>
