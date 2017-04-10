<?php 
	// ant
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("lib/WindowFrame.awp");
	require_once("lib/content_table.awp");
	require_once("lib/CAntFs.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/Email.php");
	require_once("lib/Button.awp");
	require_once("email/email_functions.awp");
	require_once("lib/CPageShellPublic.php");
	// ALIB
	require_once("lib/aereus.lib.php/CCache.php");
	//require_once("lib/aereus.lib.php/CSessions.php");
	// App
	require_once("customer_functions.awp");
	
	$dbh = $ANT->dbh;
	$ACCOUNT_NAME = $ANT->accountName;
	$ACCOUNT = $ANT->accountId;

	$CUSTID = $_GET['custid'];
	$OWNER_ID = CustGetOwner($dbh, $CUSTID);
	$CNAME = ($CUSTID) ? CustGetName($dbh, $CUSTID) : "Customer Profile";
	$ANONYMOUS = UserGetAnonymous($dbh, $ACCOUNT);
	$THEMENAME = "ant_os";
	$customer_title = $ANT->settingsGet("customers/customer_title");
	if (!$customer_title)
		$customer_title = "Customer";

	// Get owners account (this is where files will be stored)
	$root_cid = UserFilesGetAccountRootId($dbh, $ACCOUNT);
	$antfs = new CAntFs($dbh, null, $root_cid);

	$FWD = "secc=123149fdgdfsagda6";

	$page = new CPageShellPublic($CNAME, "home");
	$page->opts['print_subnav'] = false;
	//$g_links = array();
	//$g_links[] = array($HOME_SITE.$RETPAGE, 'website', 'Return To Website', null);
	$page->PrintShell();
	
	// Handle form data
	//---------------------------------------------------------------------------------

	// Authentication
	if ($_POST['password'])
	{
		if ($dbh->GetNumberRows($dbh->Query("select customer_id from customer_publish where password=md5('".$_POST['password']."') and customer_id='$CUSTID'")))
		{
			$_SESSION['pub_auth_cust_'.$CUSTID] = "1";

			CustActLog($dbh, $ANONYMOUS, "Successful Public Login", 
									   "$customer_title logged into their public profile page", 
									   $CUSTID, null, null);
		}
		else
		{
			$emsg = "Incorrect password!";

			CustActLog($dbh, $ANONYMOUS, "Failed Public Login", 
									   "$customer_title used incorrect password", 
									   $CUSTID, null, null);
		}
	}
	if ($_GET['logout'])
	{
		$_SESSION['pub_auth_cust_'.$CUSTID] = "";
	}

	// Rename file
	if (is_numeric($_GET['editfileid']) && $_GET['editfilename'])
		$antfs->moveFileById($_GET['editfileid'], rawurldecode($_GET['editfilename']));

	// Handle single file delete
	if (is_numeric($_GET['del_file']))
		$antfs->delFileById($_GET['del_file']);

	// Log file uploaded
	if ($_GET['uploaded'])
	{
		CustActLog($dbh, $ANONYMOUS, "Public Files Uploaded", 
								   "$customer_title uploaded new files", 
								   $CUSTID, null, null);

		if ($OWNER_ID)
		{
			// Create new email object
			$headers['From'] = AntConfig::getInstance()->email['noreply'];
			$headers['Subject'] = "$customer_title Files Notification";
			$headers['X-ANT-ACCOUNT-NAME'] = $ACCOUNT_NAME;
			$headers['X-ANT-CUSTID'] = $CUSTID;
			$body =  CustGetName($dbh, $CUSTID)." uploaded files through the public interface. To view these files open the customer, click the \"Files\" tab and open the \"Published\" folder.";
			$email = new Email();
			$status = $email->send(UserGetEmail($dbh, $OWNER_ID), $headers, $body);
			unset($email);
		}
	}

	// Create file class
	//---------------------------------------------------------------------------------
	// Set the path to where the files will be stored (this should be customizable
	//$path = "%userdir%/Project Files/$PID";
	if ($_GET['path'])
		$path = rawurldecode($_GET['path']);
	else
		$path = "/Customer Files/$CUSTID/Published";

	$proj_folder = $antfs->openFolder($path, true);

	$CATID = $proj_folder->id;

	// Get publish settings data
	//---------------------------------------------------------------------------------
	$result = $dbh->Query("select password, f_files_view, f_files_upload, f_files_modify from customer_publish where customer_id='$CUSTID'");
	if ($dbh->GetNumberRows($result))
		$VALS = $dbh->GetRow($result, 0);
?>
<script language="VBScript" type="text/vbscript">
	<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
	Function VBGetSwfVer(i)
	  on error resume next
	  Dim swControl, swVersion
	  swVersion = 0
	  
	  set swControl = CreateObject("ShockwaveFlash.ShockwaveFlash." + CStr(i))
	  if (IsObject(swControl)) then
		swVersion = swControl.GetVariable("$version")
	  end if
	  VBGetSwfVer = swVersion
	End Function
	// -->
</script>
<script language="JavaScript" type="text/javascript">
	function DeleteFile(id, name)
	{
		if (confirm("Are you sure you want to delete "+name+"?"))
		{
			document.location='/customer/profile/<?php print($CUSTID); ?>?del_file=' + id + '&<?php print($FWD); ?>';
		}
	}

	function FileRename(fileid, filename)
	{
		var name = '';
		name = prompt('Please enter a new name for this file', filename);
		if (name != '' && name != null)
		{
			document.location='/customer/profile/<?php print($CUSTID); ?>?editfileid=' + fileid + '&editfilename=' + escape(name) + '&<?php print($FWD); ?>';
		}
	}

	<!--
	// -----------------------------------------------------------------------------
	// Globals
	// Major version of Flash required
	var requiredMajorVersion = 8;
	// Minor version of Flash required
	var requiredMinorVersion = 0;
	// Revision of Flash required
	var requiredRevision = 0;
	// the version of javascript supported
	var jsVersion = 1.0;
	// -----------------------------------------------------------------------------
	// -->

	<!-- // Detect Client Browser type
	var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
	var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
	var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
	jsVersion = 1.1;
	// JavaScript helper required to detect Flash Player PlugIn version information
	function JSGetSwfVer(i){
		// NS/Opera version >= 3 check for Flash plugin in plugin array
		if (navigator.plugins != null && navigator.plugins.length > 0) {
			if (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
				var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
				var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
				descArray = flashDescription.split(" ");
				tempArrayMajor = descArray[2].split(".");
				versionMajor = tempArrayMajor[0];
				versionMinor = tempArrayMajor[1];
				if ( descArray[3] != "" ) {
					tempArrayMinor = descArray[3].split("r");
				} else {
					tempArrayMinor = descArray[4].split("r");
				}
				versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
				flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
			} else {
				flashVer = -1;
			}
		}
		// MSN/WebTV 2.6 supports Flash 4
		else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1) flashVer = 4;
		// WebTV 2.5 supports Flash 3
		else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1) flashVer = 3;
		// older WebTV supports Flash 2
		else if (navigator.userAgent.toLowerCase().indexOf("webtv") != -1) flashVer = 2;
		// Can't detect in all other cases
		else {
			
			flashVer = -1;
		}
		return flashVer;
	} 
	// If called with no parameters this function returns a floating point value 
	// which should be the version of the FlasKathyh Player or 0.0 
	// ex: Flash Player 7r14 returns 7.14
	// If called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
	function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision) 
	{
		reqVer = parseFloat(reqMajorVer + "." + reqRevision);
		// loop backwards through the versions until we find the newest version	
		for (i=25;i>0;i--) {	
			if (isIE && isWin && !isOpera) {
				versionStr = VBGetSwfVer(i);
			} else {
				versionStr = JSGetSwfVer(i);		
			}
			if (versionStr == -1 ) { 
				return false;
			} else if (versionStr != 0) {
				if(isIE && isWin && !isOpera) {
					tempArray         = versionStr.split(" ");
					tempString        = tempArray[1];
					versionArray      = tempString .split(",");				
				} else {
					versionArray      = versionStr.split(".");
				}
				versionMajor      = versionArray[0];
				versionMinor      = versionArray[1];
				versionRevision   = versionArray[2];
				
				versionString     = versionMajor + "." + versionRevision;   // 7.0r24 == 7.24
				versionNum        = parseFloat(versionString);
				// is the major.revision >= requested major.revision AND the minor version >= requested minor
				if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
					return true;
				} else {
					return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );	
				}
			}
		}	
		return (reqVer ? false : 0.0);
	}
	function FlUploadComplete()
	{
		document.location='/customer/profile/<?php print($CUSTID); ?>?<?php print($FWD); ?>&uploaded=1';
	}

	function ToggleAdd()
	{
		var dsp = document.getElementById('div_addfile').style.display;
		if (dsp == 'block')
		{
			document.getElementById('div_addfile').style.display = 'none';
			document.getElementById('div_addfile').innerHTML = "";
		}
		else
		{
			document.getElementById('div_addfile').style.display = 'block';
			var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
			if(hasRightVersion) 
			{  
				<?php
					$url = "/userfiles/file_upload.swf?fwdCatid=$CATID&quota_free=$QUOTA_FREE&fwdCid=$CID&fwdAuth=".base64_encode("administrator").":".md5("Password1");
				?>
				// if we've detected an acceptable version
				var oeTags = '<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"'
							+ 'width=\"450\" height=\"57\"'
							+ 'codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab\">'
							+ '<param name=\"movie\" value=\"<?php print($url); ?>\" /><param name=\"quality\"'
							+ ' value=\"high\" /><param name=\"wmode\" value=\"transparent\" />'
							+ '<param name=\"bgcolor\" value=\"#ffffff\" /><embed src=\"<?php print($url); ?>\" '
							+ 'quality=\"high\" wmode=\"transparent\" bgcolor=\"#ffffff\" '
							+ 'width=\"450\" height=\"57\" name=\"file_upload\" align=\"middle\"'
							+ 'play=\"true\"'
							+ 'loop=\"false\"'
							+ 'quality=\"high\"'
							+ 'allowScriptAccess=\"sameDomain\"'
							+ 'type=\"application/x-shockwave-flash\"'
							+ 'pluginspage=\"http://www.macromedia.com/go/getflashplayer\">'
							+ '<\/embed>'
							+ '<\/object>';
				document.getElementById('div_addfile').innerHTML = oeTags;   // embed the flash movie
			  } 
			  else 
			  {  // flash is too old or we can't detect the plugin
				var alternateContent = 'You do not have the latest version of Macromedia Flash Player.'
				+ 'This content requires the Macromedia Flash Player 8.0 or above.'
				+ '<a href=http://www.macromedia.com/go/getflash/ target=_blank>Click here</a>'
				+ ' to download it free!';
				document.getElementById('div_addfile').innerHTML = alternateContent;  // insert non-flash content
			  }
		}
	}
	// -->
</script>

<?php
	echo "<div class='g12'>";
	if (!$_SESSION['pub_auth_cust_'.$CUSTID])
	{
		WindowFrameStart("Login");
		echo "Welcome ".CustGetName($dbh, $CUSTID)."<br />Please enter your password<br /><br />";
		echo "<form method='post' action='/customer/profile/$CUSTID' name='login'>";
		echo "<input type='password' name='password'>";
		echo ButtonCreate("Login", "SUBMIT:login:send");
		if ($emsg) echo " <span class='alert'>$emsg</span>";
		echo "</form>";
		WindowFrameEnd();
	}
	else
	{
		WindowFrameStart("Account Information");
		echo "<table>";
		echo "<tr><td>Name:</td><td>".CustGetName($dbh, $CUSTID)." - [<a href='/customer/profile/$CUSTID?logout=1'>logoff</a>]</td></tr>";
		echo "<tr><td>ID:</td><td>".$CUSTID."</td></tr>";
		echo "</table>";
		WindowFrameEnd();

		if ($VALS['f_files_view']=='t')
		{
			WindowFrameStart("Files");

			// Begin Toolbar
			//---------------------------------------------------------------------------------
			if ($VALS['f_files_upload']=='t')
			{
				WindowFrameToolbarStart('100%');
					echo ButtonCreate("Upload File(s)", "ToggleAdd()", "b2");
				//	echo ButtonCreate("Add Folder", "JSAddCat()");
				WindowFrameToolbarEnd();
			}
			
			// Create div for add file swf
			//---------------------------------------------------------------------------------
			echo "<div id='div_addfile' style='display:none;background-color:#CCCCCC; border-width:1px; ";
			echo "						border-color:#333333;border-style:solid;width:452px;color:#000000;'>";
			
			echo "</div>";

			echo "<div style='padding:3px;'>";

			// Loop through files
			echo "<table border='0'>";

			// Create back
			if ($path != "/Customer Files/$CUSTID/Published")
			{
				echo "<tr>
						<td><img border='0' src='/images/icons/goback_small.gif'></td>
						<td style='width:200px;'>
							<a href=\"/customer/profile/$CUSTID?$FWD\" 
							style='text-decoration:none;font-weight:bold;'>Root Folder</a>
						</td>
					  </tr>";
			}

			// Loop through folders
			for ($i = 0; $i < $proj_folder->numFolders(); $i++)
			{
				$folder = $proj_folder->folders[$i];
				//echo "<a href='project.awp?$FWD&path=".rawurlencode($folder->name)."'>".$folder->name."</a><br />";
				echo "<tr>
						<td width='10'><img border='0' src='/images/themes/$THEMENAME/icons/managefilesfolder_small.gif'></td>
						<td style='width:200px;'>
							<a href=\"/customer/profile/$CUSTID?$FWD&path=".rawurlencode($path."/".$folder->name)."\" style='text-decoration:none;'>".$folder->name."</a>
						</td>
						<td></td>
						<td><strong>FOLDER</strong></td>
					  </tr>";
			}

			$last_header = "";
			for ($i = 0; $i < $proj_folder->numFiles(); $i++)
			{
				$file = $proj_folder->files[$i];

				if ($file->type != $last_header)
				{
					echo "<tr><td colspan='7'>";
					echo "<div class='headerThree'>".strtoupper($file->type)." FILES</div><div class='headerBar'></div>";
					echo "</td></tr>";
					$last_header = $file->type;
				}

				switch ($file->type)
				{
				case "adf":
					$viewlink = "javscript:void();";
					$view_target = "";
					$onclk = "window.open('".$file->url_stream."', 'editor".$file_row['id']."',";
					$onclk .= "'top=200,left=100,width=818,toolbar=no,menubar=no,scrollbars=no,location=no,";
					$onclk .= "directories=no,status=no,resizable=yes');";
					$act_btn = ButtonCreate("Download", "document.location='file_download.awp?fid=".$file_row['id']."'");
					break;
				case "emt":
					$viewlink = "javscript:void();";
					$view_target = "";
					$viewlink = "window.open('".$file->url_stream."', 'composer".$file_row['id']."',";
					$viewlink .= "'top=200,left=100,width=648,toolbar=no,menubar=no,scrollbars=auto,location=no,";
					$viewlink .= "directories=no,status=no,resizable=yes');";
					$act_btn = "";
					break;
				default:
					$viewlink = $file->url_stream;
					$view_target = "target='_blank'";
					$onclk = "";
					$act_btn = ButtonCreate("Download", "document.location='$filelink'");
				}

				echo "<tr>
					  <td width='10' align='left'>
						<img border='0' src='/images/themes/$THEMENAME/icons/".UserFilesGetTypeIcon($file->type)."'>
					  </td>
					  <td style='width:200px;'>
						<a href='$viewlink' $view_target ".(($onclk)?"onclick=\"$onclk\"":'').">".$file->name."</a>
					  </td>
					  <td>".number_format($file->size*.01, 0)."k</td>
					  <td><strong>".strtoupper($file->type)."</strong></td>
					  <td>".$file->time_modified."</td>
					  <td>";
				if ($file->url_download)
					echo ButtonCreate("Download", "document.location='".$file->url_download."';");
				echo "</td><td>";
				if ($VALS['f_files_modify']=='t')
					echo ButtonCreate("Rename", "FileRename('".$file->id."', '".$file->name."');");
				echo "</td><td>";
				if ($VALS['f_files_modify']=='t')
					echo ButtonCreate("Delete", "DeleteFile('".$file->id."', '".$file->name."')", "b3");
				echo "</td></tr>";
			}

			echo "</table>";

			echo "</div>";

			WindowFrameEnd();
		}
	}
	echo "</div>";
?>
