<?php
    require_once("../lib/AntConfig.php");
    require_once("../ant.php");
    require_once("../ant_user.php");
    require_once("../lib/CToolTabs.awp");
    require_once("../lib/WindowFrame.awp");
    require_once("../users/user_functions.php");
    require_once("../lib/date_time_functions.php");
    require_once("../lib/CDropdownMenu.awp");
    require_once("../contacts/contact_functions.awp");
    require_once("../customer/customer_functions.awp");
    require_once("../lib/CAutoComplete.awp");    
    require_once("../lib/aereus.lib.php/CPageCache.php");

    $dbh = $ANT->dbh;
    $USERNAME = $USER->name;
    $USERID =  $USER->id;
    $ACCOUNT = $USER->accountId;
    $THEME = $USER->themeName;

    // Get forwarded variables    	
    $chatType = $_GET['chat_type'];        
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title><?php echo $_POST['chatFriendFullName']; ?> - Ant Chat Client</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>">
        <?php
            // Aereus lib
            include("lib/aereus.lib.js/js_lib.php");
            // ANT lib
            include("lib/js/includes.php");
        ?>
        <script language="javascript" type="text/javascript">
            document.documentElement.style.overflow = 'hidden';     // firefox, chrome            
            <?php
                echo "this.chatType= '$chatType';\n";
            ?>
            
            function main()
            {
                var con = document.body;
                con.innerHTML = "";

                // determine which class to load
                if(this.chatType=="messenger")
                {
                    this.chatLoader = new AntChatMessenger();
                }                
                else if(this.chatType=="client")
                {
                    this.chatLoader = new AntChatClient();                    
                }                    
                <?php
                    if (is_array($_POST))
                    {
                        // create the variables needed for ant chat messenger/client
                        foreach($_POST as $key=>$value)
                        {
                            echo "this.chatLoader.$key = '$value';\n";
                        }
                    }    
                ?>
                this.chatLoader.chatPopup = true;
                
                // inform the parent that the ant client was loaded in new popup window
                if(this.chatType=="messenger")
                {
                    this.chatLoader.chatFloatType = "left";
                    this.chatLoader.chatFloatMargin = "10px";
                }                
                else if(this.chatType=="client")
                {
                    this.chatLoader.chatDivInfoId = "divChatInfo_"+this.chatLoader.chatFriendName;
                    this.chatLoader.savePopupState(true);
                    if(navigator.appName == 'Microsoft Internet Explorer')
                    {
                        var friend = new Object();
                        friend.chatFriendName = this.chatLoader.chatFriendName;                    
                        friend.chatFriendServer = this.chatLoader.chatFriendServer;                    
                        friend.chatFriendFullName = this.chatLoader.chatFriendFullName;                    
                        friend.chatFriendImage = this.chatLoader.chatFriendImage;                    
                        window.opener.clientLib.buildDivInfo(friend);
                    }
                    else
                        this.chatLoader.popupChatClient();
                }
                
                this.chatLoader.print(con);
                this.chatLoader.onClose = function()
                {
                    window.close();
                }
            }

            function closeWindow()
            {                
                window.close();
            }

            function resized()
            {
                return;
                //try
                //{
                //var oRTE = document.getElementById("cmpbody");
                var tb = document.getElementById("toolbar");
                var bdy = document.getElementById("bdy_outer");
                if (g_ctbl)
                    {
                    var con = g_ctbl.getCon();
                    var total_height = con.offsetHeight;
                }
                else
                    var total_height = document.body.offsetHeight;
                alib.dom.styleSet(bdy, "height", (total_height - tb.offsetHeight) + "px");
                //}
                //catch (e) { }
            }
            
            window.onbeforeunload = function()
            {
                if(this.chatType=="client")
                {
                    this.chatLoader.savePopupState(false);
                    
                    var divName = "divChatInfo_<?php echo $_POST['chatFriendName']; ?>";
                    var divInfo = window.opener.document.getElementById(divName);
                    
                    if(divInfo)
                        window.opener.document.body.removeChild(divInfo);
                }
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

    <body class='<?php print(($INLINE)?"iframe":"popup"); ?>' onLoad="main();" onresize='resized()'>
    </body>
</html>
