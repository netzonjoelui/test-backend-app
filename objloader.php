<?php
	require_once("lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("users/user_functions.php");
	require_once("lib/date_time_functions.php");
	require_once("contacts/contact_functions.awp");
	require_once("customer/customer_functions.awp");
	require_once("lib/CAutoComplete.awp");
	require_once("lib/sms.php");
	require_once("lib/aereus.lib.php/CPageCache.php");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THEME = $USER->themeName;
	
	// Get forwarded variables    
	$OID = $_GET['oid'];
	$OBJ_TYPE = $_GET['obj_type'];
    $INLINE = $_GET['inline'];
    $NOCLOSE = $_GET['noclose'];
	
    // email compose url query strings
    $fid = $_GET['fid'];
    $mid = $_GET['mid'];
    $replyMid = $_GET['reply_mid'];
    $replyType = $_GET['reply_type'];
    $automate = $_GET['automate'];
    $friend = $_GET['friend'];
    
    $inpField = $_POST['inp_field'];
    $objType = $_POST['obj_type'];
    $sendMethod = $_POST['send_method'];
    $objects = $_POST['objects'];
    $using = $_POST['using'];
    $allSelected = $_POST['all_selected'];    
    $sendTo = $_REQUEST['sendto'];    
?>
<!DOCTYPE HTML>
<html>
<head>
	<title>Edit Object</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="STYLESHEET" id='ant_css_base' type="text/css" href="/css/ant_base.css">

	<?php include("inc_jslibs.php"); ?>

	<script language="javascript" type="text/javascript">
	<?php
		echo "var g_obj_type= '".$OBJ_TYPE."';\n";
		echo "var g_oid = '$OID';\n";
		echo "var g_inline = '$INLINE';\n";
		echo "var g_userid  = $USERID;\n";    
	?>
		var g_theme = "<?php echo UserGetTheme($dbh, $USERID, 'name'); ?>";

		/**
		 * Load Ant script
		 */
		function loadAnt()
		{
			Ant.init(function() { main(); });
		}

		var ol = null;
		function main()
		{
			var con = document.body;
			con.innerHTML = "";

			// Determine if we are mobile or not
			if (alib.dom.getClientWidth() < 800)
				Ant.isMobile = true;
			
			// Print object loader
			ol = new AntObjectLoader(g_obj_type, g_oid);
			
			<?php
				if (is_array($_POST))
				{
					/* OLD non-working code!
					// posted value from inline form
					if(isset($_POST['fromInlineForm']))        
					{
						// creates argument array to be passed in AntObjectLoader
						echo "var g_args  = new Array();\n";
						foreach($_POST as $key=>$value)
						{
							if("{$prevKey}Type" == $key)
							{
								switch($value){                                
									case "bool":
										echo "g_args['$prevKey'] = [$prevValue, '$value']; \n";
										break;                                
									default:
										echo "g_args['$prevKey'] = ['$prevValue', '$value']; \n";
										break;
								}                            
							}                        
							$prevKey = $key;                            
							$prevValue = $value;
							
						}
						echo "ol.newWindowArgs = g_args;\n";
					}
					 */
				}    
			?>
				
			ol.onClose = function()
			{
				window.close();
			}
			<?php
				if (is_array($_REQUEST))
				{
					foreach ($_REQUEST as $varname=>$varval)
					{
						if ($varname != "obj_type" && $varname != "oid")
						{
							if (is_array($varval))
							{
								foreach ($varval as $subval)
								{
									echo "var val = \"".preg_replace('/((\\\\n)+)/',"$1\"+\n\"",preg_replace("/(\r\n|\n)/",'\\n',str_replace('"', "\\\"", $subval)))."\";\n";
									echo "ol.mainObject.setMultiValue('$varname', val);\n";
								}
							}
							else
							{
								echo "var val = \"".preg_replace('/((\\\\n)+)/',"$1\"+\n\"",preg_replace("/(\r\n|\n)/",'\\n',str_replace('"', "\\\"", $varval)))."\";\n";
								echo "ol.setValue('$varname', val);\n";
							}
						}
					}
				}
			?>
			
			<?php
				if($NOCLOSE)
					echo "ol.fEnableClose = false;\n";
			
				if($fid)
					echo "ol.emailArgs['fid'] = '$fid';\n";
					
				if($mid)
					echo "ol.emailArgs['mid'] = '$mid';\n";
					
				if($replyMid)
					echo "ol.emailArgs['replyMid'] = '$replyMid';\n";
				
				if($automate)
					echo "ol.emailArgs['automate'] = '$automate';\n";
					
				if($friend)
					echo "ol.emailArgs['friend'] = '$friend';\n";
				
				if($replyType)
					echo "ol.emailArgs['replyType'] = '$replyType';\n";
				else
					echo "ol.emailArgs['replyType'] = 'reply';\n";
					
				if($inpField)
					echo "ol.emailArgs['inpField'] = '$inpField';\n";
					
				if($objType)
					echo "ol.emailArgs['objType'] = '$objType';\n";
					
				if($sendMethod)
					echo "ol.emailArgs['sendMethod'] = '$sendMethod';\n";
				else
					echo "ol.emailArgs['sendMethod'] = '0';\n";
				
				if($allSelected)
					echo "ol.emailArgs['allSelected'] = '$allSelected';\n";
				
				if($sendTo)
					echo "ol.emailArgs['sendTo'] = '$sendTo';\n";
					
				if($objects)
				{
					$x = 0;
					echo "ol.emailArgs['objects'] = new Array();\n";
					foreach($objects as $object)
					{
						if($object > 0)
						{                        
							echo "ol.emailArgs['objects'][$x] = '$object';\n";
							$x++;
						}                        
					}
						
					echo "ol.emailArgs['objectsLen'] = " . sizeof($objects) . ";\n";
				}
				
				if($using)
				{
					$x = 0;
					echo "ol.emailArgs['using'] = new Array();\n";
					foreach($using as $use)
					{
						echo "ol.emailArgs['using'][$x] = '$use';\n";
						$x++;
					}
					
					echo "ol.emailArgs['usingLen'] = " . sizeof($using) . ";\n";
				}
			?>
			
			ol.print(con, true);

			resized(ol);
		}

		function closeWindow()
		{
			window.close();
		}

		function resized(objLoader)
		{
			if(objLoader)
				objLoader.resize();
			else
				ol.resize();
		}

		// Determine the client size and load appropriate css for reactive design
		var cwidth = alib.dom.getClientWidth();
		if (cwidth < 800)
		{
			// Load mobile css
			var ss = document.createElement("link");
			ss.id = "ant_css_theme";
			ss.type = "text/css";
			ss.rel = "stylesheet";
			ss.href = "/css/ant_mobile.css";
			document.getElementsByTagName("head")[0].appendChild(ss);
		}
		else
		{
			// Load the theme css
			var ss = document.createElement("link");
			ss.id = "ant_css_theme";
			ss.type = "text/css";
			ss.rel = "stylesheet";
			ss.href = "/css/<?php echo $USER->themeCss; ?>";
			document.getElementsByTagName("head")[0].appendChild(ss);
		}


	</script>
	<style type="text/css">
		html, body
		{
			height: 100%;
		}
		#bdy_outer
		{
			overflow: auto;
		}
	</style>
</head>

<body class="<?php echo ($_GET['inline'])?"inline":"popup"; ?>" onLoad="loadAnt();" onresize='resized()'>
</body>
</html>
