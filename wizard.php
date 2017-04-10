<?php
	require_once("lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntFs.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/aereus.lib.php/CAntCustomer.php");
	require_once("customer/customer_functions.awp");

	$dbh = $ANT->dbh;
	$RUN = ($_GET['wizard']) ? $_GET['wizard'] : "account";
	$GOTO = "/main";
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Account Wizard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="STYLESHEET" type="text/css" href="/css/<?php print($USER->themeCss); ?>">
<script language="javascript" type="text/javascript" src="/admin/CRenewWizard.js"></script>
<script language="javascript" type="text/javascript" src="/wizards/UpdateBillingWizard.js"></script>
<script language="javascript" type="text/javascript" src="/lib/js/CAntAccountWizard.js"></script>
<script language="javascript" type="text/javascript" src="/lib/js/CAntUserWizard.js"></script>
<?php
	// Aereus lib
	require_once("lib/aereus.lib.js/js_lib.php");
	// ANT lib
	include("lib/js/includes.php");
?>
<style type='text/css'>
body, html
{
	height: 100%;
}
body
{
	padding: 0px;
	margin: 0px;
	overflow: hidden;

	background-color:#0E0E0E;

	background-image: url("/images/login/bg-5-1280.jpg");
	background-repeat: no-repeat;
	background-attachment: fixed;
	background-position: center; 
}
#maincon
{ 
	margin-top: 10px;
	margin-right: auto;
	margin-left: auto;
	width: 500px;
	height: 800px;
	text-align:center;
}
#header
{
	padding-top: 10px;
	text-align:center;
	font-weight: bold;
	font-size: 30px;
	font-family: Georgia;
	color: white;
}
</style>
<script language="javascript" type="text/javascript">
	function main()
	{
		var con = document.getElementById("bdy");

	<?php
		switch ($RUN)
		{
		case 'account':
			echo "
					var wiz = new CAntAccountWizard(".$USER->id.");
					wiz.onFinished = function()
					{
						var wiz2 = new CAntUserWizard(".$USER->id.");
						wiz2.onFinished = function()
						{
							redirect();
						}
						wiz2.onCancel = function()
						{
							redirect();
						}
						wiz2.showDialog(null, 0);
					}
					wiz.onCancel = function()
					{
						redirect();
					}
					wiz.showDialog();
				";
			break;
		
		case 'user':
			echo "
					document.title = 'Personal Settings Wizard';
					var wiz2 = new CAntUserWizard(".$USER->id.");
					wiz2.onFinished = function()
					{
						var args = [['set', 'general/f_forcewizard'],
									['val', 'f'], 
									['userid', '".$USER->id."']];						
						ajax = new CAjax('json');
						ajax.onload = function(ret)
						{
							redirect();
						};
						ajax.exec('/controller/User/setSettingUser', args);
					}
					wiz2.onCancel= function()
					{
						var args = [['set', 'general/f_forcewizard'],
									['val', 'f'], 
									['userid', '".$USER->id."']];						
						ajax = new CAjax('json');
						ajax.onload = function(ret)
						{
							redirect();
						};
						ajax.exec('/controller/User/setSettingUser', args);
					}
					wiz2.showDialog(null, 0);
				";
			break;

		case 'expired':
			echo "
					document.title = 'Account Renewal Wizard';
					var wiz = new CRenewWizard(".$USER->id.");
					wiz.onFinished = function()
					{
						redirect();
					}
					wiz.onCancel= function()
					{
						document.location='/';
					}
					wiz.showDialog(null, 0);
				";
			break;
			
		case 'ubilling':
			echo "
					document.title = 'Billing Wizard';
					var wiz = new UpdateBillingWizard(".$USER->id.");
					wiz.onFinished = function()
					{
						redirect();
					}
					wiz.onCancel= function()
					{
						document.location='/';
					}
					wiz.showDialog(null, 0);
				";
			break;
		}
	?>
	}

	function redirect()
	{
		// Create loading div
		var dlg = new CDialog();
		var dv_load = document.createElement('div');
		alib.dom.styleSetClass(dv_load, "statusAlert");
		alib.dom.styleSet(dv_load, "text-align", "center");
		dv_load.innerHTML = "Loading, please wait...";
		dlg.statusDialog(dv_load, 150, 100);

		window.location = "<?php print($GOTO); ?>";
	}

	function saySomething(say)
	{
		alert(say);
	}
</script>
</head>
<body onload='main()' id='bdy'>

<div id="maincon">

	<div id="header">
		<?php
			$company_name = $ANT->settingsGet("general/company_name");
			if ($company_name)
				echo "$company_name";
			else
				echo "Aereus Network Tools";
		?>
	</div>

</div>

</body>
</html>
