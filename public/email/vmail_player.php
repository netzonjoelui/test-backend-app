<?php
/**
 * @depricated This is the old video mail player which has been phased out for the time being.
 * We leave it here as a historical reference in case we ever want to take another stab at
 * sending video emails. - joe
 */

// ant
require_once("../lib/AntConfig.php");
require_once("../settings/settings_functions.php");
require_once("../lib/CDatabase.awp");
require_once("../lib/WindowFrame.awp");
require_once("../lib/content_table.awp");
require_once("../users/user_functions.php");
require_once("../lib/CAntFs.awp");
require_once("../lib/Email.php");
require_once("../lib/Button.awp");
require_once("../email/email_functions.awp");
require_once("../userfiles/file_functions.awp");

$dbh = new CDatabase();
$ACCOUNT_NAME = settingsGetAccountName();
$ACCOUNT = settingsGetAccountId($dbh, $ACCOUNT_NAME);

$THEME = ($_GET['theme']) ? $_GET['theme'] : "white";
$TITLE = stripslashes($_GET['title']);
$SUBTITLE = stripslashes($_GET['subtitle']);
$MESSAGE = stripslashes($_GET['message']);
$FOOTER = stripslashes($_GET['footer']);
$VIDEO_FILE_ID = $_GET['video_file_id'];
$LOGO_FILE_ID = $_GET['logo_file_id'];
$VIDEO_FILE_JOBID = $_GET['video_file_jobid'];
$BUTTONS = array();
$FACEBOOK = $_GET['facebook'];
$TWITTER = $_GET['twitter'];


if ($_GET['buttons'])
{
	foreach ($_GET['buttons'] as $btn)
	{
		$parts = explode("|", $btn);

		$button = array();
		$button['label'] = stripslashes($parts[0]);
		$button['link'] = $parts[1];

		$BUTTONS[] = $button;
	}
}

if ($_GET['mid'])
{
	$result = $dbh->Query("select file_id, logo_file_id, title, subtitle, message, footer, 
							theme, facebook, twitter from email_video_messages where id='".$_GET['mid']."'");
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetRow($result, 0);
		$THEME = $row['theme'];
		$TITLE = stripslashes($row['title']);
		$SUBTITLE = stripslashes($row['subtitle']);
		$MESSAGE = stripslashes($row['message']);
		$FOOTER = stripslashes($row['footer']);
		$VIDEO_FILE_ID = $row['file_id'];
		$VIDEO_FILE_NAME = UserFilesGetFileName($dbh, $row['file_id']);
		$LOGO_FILE_ID = $row['logo_file_id'];
		$FACEBOOK = $row['facebook'];
		$TWITTER = $row['twitter'];

		$res2 = $dbh->Query("select label, link from email_video_message_buttons where message_id='".$_GET['mid']."'");
		$num = $dbh->GetNumberRows($res2);
		for ($i = 0; $i < $num; $i++)
		{
			$row2 = $dbh->GetRow($res2,$i);

			$button = array();
			$button['label'] = stripslashes($row2["label"]);
			$button['link'] = $row2["link"];

			$BUTTONS[] = $button;
		}
	}
}

$FILE_TYPE = ($VIDEO_FILE_ID) ? UserFilesGetFileType($dbh, $VIDEO_FILE_ID) : null;

if (is_numeric($THEME))
{
	$result = $dbh->Query("select html from email_video_message_themes where id='".$THEME."'");
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetRow($result, 0);
		$html = $row['html'];
	}

	$cssfile = "/css/public/vmail_cust.css?theme_id=$THEME";
}
else
{
	$cssfile = "/css/public/vmail_$THEME.css";
}

if (!$html)
{
	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			<link rel="STYLESHEET" type="text/css" href="'.$cssfile.'">
			<title>Video Message: <%TITLE%></title>
		</head>
		<body>
		<div id="globalContainer">
			<div id="header">';
	if ($LOGO_FILE_ID)
		$html .= "<img src='/files/$LOGO_FILE_ID' style='height:60px;' />";
	$html .= '	<h1><%TITLE%></h1>
				<h2><%SUBTITLE%></h2>
				<div style="clear:both;"></div>
			</div>
			<div id="contentBody">
				<div id="sidebar">
					<%BUTTONS%>
				</div>
				<div id="content">
					<div style="text-align:center;">
						<div style="width:420px;display:inline-block;">
						<%VIDEO%>
						</div>
					</div>
					<div style="text-align:center;">
						<%MESSAGE%>
					</div>
				</div>
				<div style="text-align:center;">
					<%SOCIAL%>
				</div>
				<div style="clear:both;"></div>
			</div>
			<div id="globalFooter">
				<div>
					<%FOOTER%>
				</div>
				<div>Video Email powered by <a href="http://www.aereus.com/ant/video-email" target="_blank">Aereus Network Tools</a></div>
			</div>
		</div>
		</body>
		</html>';
}

// <%TITLE%>
// <%SUBTITLE%>
// <%BUTTONS%>
// <%VIDEO%>
// <%MESSAGE%>
// <%SOCIAL%>
// <%FOOTER%>

$html = str_replace("<%TITLE%>", $TITLE, $html);
$html = str_replace("<%SUBTITLE%>", $SUBTITLE, $html);


// Buttons
$btn_htm = "";
foreach ($BUTTONS as $button)
{
	$link = $button['link'];
	if (strpos($link, "@") !== false)
	{
		$link = "mailto:".$link;
	}
	else if (substr($link, 0, 4)!="http")
	{
		$link = "http://".$link;
	}

	$btn_htm .= "<div class='buttonwrapper'><a class=\"boldbuttons\" target='_blank' href=\"$link\"><span>".$button['label']."</span></a></div>";
}
$html = str_replace("<%BUTTONS%>", $btn_htm, $html);

// Follow on Facebook and/or Twitter
$soc_htm = "";
if($FACEBOOK)
{
	$soc_htm .= "<a href='$FACEBOOK' target='_blank'><img src='/images/facebook_follow.gif' style='border-style: none'/></a>";
}
if($TWITTER)
{
	$soc_htm .= "<a href='$TWITTER' target='_blank'><img src='/images/twitter_follow.gif' style='border-style: none'/></a>";
}
$html = str_replace("<%SOCIAL%>", $soc_htm, $html);

// Video
// ----------------------------------------------------------------------------------------
$vdo_htm = '<script type="text/javascript" src="/lib/js/qtobject.js"></script>';
$vdo_htm .= '<script type="text/javascript" src="/lib/js/AC_RunActiveContent.js"></script>';

// Check if still processing
if ($VIDEO_FILE_JOBID)
{
	$worker = new CWorkerPool();
	$ret = $worker->getJobStatus($VIDEO_FILE_JOBID);

	if (($ret[0] || $ret[0]==-1) && strtolower($FILE_TYPE)!="flv" && strtolower($FILE_TYPE)!='f4v')
	{
		$VIDEO_FILE_JOBID = $ret[1];
		$FILE_TYPE = "inprocess";
	}
	else
		$VIDEO_FILE_JOBID = 0;
}

switch (strtolower($FILE_TYPE))
{
case "inprocess":
	$vdo_htm .= '<div style="text-align:center;">File is in the process of being converted to flash... <br /><img src="/images/loading.gif" /></div>';
	$vdo_htm .= '<script language="JavaScript" type="text/javascript">';
	$vdo_htm .= " 	setTimeout('window.location.reload(false);', 10000);
				 </script>";
	break;
case "flv":
case "f4v":
	$vars = "xmlFilePath=".rawurlencode("/email/xml_vmail_file.php?fid=".$VIDEO_FILE_ID);
	$vars .= "&feedbackVideoButtonScale=2";
	$vars .= "&navButtonsAppearance=All Visible";
	$vars .= "&galleryRows=3";
	$vars .= "&navAppearance=Hidden";
	$vars .= "&videoAutoStart=Off";
	$vars .= "&fullScreenTakeOver=On";
	$vdo_htm .= '<script language="JavaScript" type="text/javascript">';
	$vdo_htm .= " 	AC_FL_RunContent(
									'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0',
									'width', '420',
									'height', '351',
									'src', '/flash/slideshowpro',
									'quality', 'high',
									'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
									'align', 'middle',
									'play', 'false',
									'loop', 'false',
									'scale', 'showall',
									'wmode', 'window',
									'devicefont', 'false',
									'id', 'player',
									'bgcolor', '#ffffff',
									'name', 'player',
									'menu', 'true',
									'allowFullScreen', 'true',
									'allowScriptAccess','sameDomain',
									'FlashVars','$vars',
									'movie', '/flash/slideshowpro',
									'salign', ''
								);
				</script>";

	// &btncolor=0x30292b&accentcolor=0x20b3f7&txtcolor=0xffffff&volume=80&previewimage=
	
	$vdo_htm .= '<noscript>
					<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" width="420" height="236" id="vmail_player" align="middle">
					<param name="allowScriptAccess" value="sameDomain" />
					<param name="allowFullScreen" value="false" />
					<param name="movie" value="vmail_player.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" />	<embed src="vmail_player.swf" quality="high" bgcolor="#ffffff" width="420" height="236" name="vmail_player" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" />
					</object>
				</noscript>';
	break;
case "wmv":
	$vdo_htm .= '<div style="text-align:center;">File is in the process of being converted to flash...</div>
				<object id="MediaPlayer" width=420 height=236 classid="CLSID:22D6f312-B0F6-11D0-94AB-0080C74C7E95" standby="Loading Windows Media Player components..." type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,7,1112"> 
				 
				<param name="filename" value="/files/$VIDEO_FILE_ID"> 
				<param name="Showcontrols" value="True"> 
				<param name="autoStart" value="True"> 
				 
				<embed type="application/x-mplayer2" src="/files/$VIDEO_FILE_ID" name="MediaPlayer" width=420 height=236></embed> 
				 
				</object> ';
	break;
case "mp4":
	$vdo_htm .= '<div style="text-align:center;">File is in the process of being converted to flash...</div>
				<OBJECT CLASSID="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
				CODEBASE="http://www.apple.com/qtactivex/qtplugin.cab"
				WIDTH="420" HEIGHT="236" >
				<PARAM NAME="src" VALUE="/files/$VIDEO_FILE_ID">
				<PARAM NAME="autoplay" VALUE="true">
				<PARAM NAME="controller" value="true">
				<EMBED SRC="QTMimeType.pntg" TYPE="image/x-macpaint"
				PLUGINSPAGE="http://www.apple.com/quicktime/download" QTSRC="/files/$VIDEO_FILE_ID"
				WIDTH="420" HEIGHT="236" AUTOPLAY="true" CONTROLLER="true">
				</EMBED>
				</OBJECT>';
	break;
case "avi":
	$vdo_htm .= '<object data="/files/$VIDEO_FILE_ID" type="video/avi" />';
	break;
case "mpeg":
	$vdo_htm .= '<div style="text-align:center;">File is in the process of being converted to flash...</div>
				 <object id="Player" height="420" width="236"
				  CLASSID="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6">
				  <param name="URL" value="/files/$VIDEO_FILE_ID">
				  <param name="autoStart" value="true">
				 </object>';
	break;
case "jpg":
case "jpeg":
case "png":
case "gif":
	$vdo_htm .= "<img src='/files/$VIDEO_FILE_ID' border='0' style='width:420px;'>";
	break;
case "mov":
case "m4v":
	$vdo_htm .= '<div style="text-align:center;">File is in the process of being converted to flash...</div><div id="video">
				 <script type="text/javascript">
				 // <![CDATA[
			
				// create the qtobject and write it to the page, this includes plugin detection
				// be sure to add 15px to the height to allow for the controls
				var myQTObject = new QTObject("/files/$VIDEO_FILE_ID/$VIDEO_FILE_NAME", "video_message", "420", "236");
				myQTObject.addParam("href", "/files/$VIDEO_FILE_ID/$VIDEO_FILE_NAME");
				myQTObject.addParam("target", "myself");
				myQTObject.addParam("controller", "true");
				myQTObject.write();
			
			// ]]>
			</script>
			</div>';
	break;
}
$html = str_replace("<%VIDEO%>", $vdo_htm, $html);

// Message
$html = str_replace("<%MESSAGE%>", str_replace("\n", "<br />", $MESSAGE), $html);

// Footer
$html = str_replace("<%FOOTER%>", str_replace("\n", "<br />", $FOOTER), $html);

echo $html;
?>
