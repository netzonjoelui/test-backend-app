/**
* @fileOverview	Main chat client in ANT
*
* Usage
* var chatClient = new AntChatClient();
* chatClient.chatFriendName = friendName;
* chatClient.chatFriendServer = friendServer;
* chatClient.chatFriendImage = friendImage;
* chatClient.print(divContainer);
*
* @author: 	Marl Jay Tumulak, marl.tumulak@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*
*/

/**
 * Creates an instance of AntChatClient
 *
 * @constructor
 */
function AntChatClient()
{
	/**
	* div containers used to build chat client
	*/
    this.outerCon = null;
    this.mainCon = null;
    this.headerCon = null;
    this.bodyCon = null;    
    this.contPrevChat = null;    
    this.conChatConv = null;    
    this.conChatMessage = null;
    this.conChatNotification = null;
    this.conChatSound = null;
        
    this.userImage = "/images/icons/objects/user_48.png";
    this.chatSound = null;

	/**
	* @param {boolean} userIsTyping if user is typing or not    
	*/
    this.userIsTyping = false;
    

	/**
    * chat friend variables
	*/
    this.chatFriendName = null;
    this.chatFriendFullName = null;
    this.chatFriendServer = null;
    this.chatFriendImage = "/images/icons/objects/user_48.png";
	/**
	* @param {boolean} userIsTyping if friend is typing or not    
	*/
    this.chatFriendIsTyping = false;                // 
    this.chatFriendList = null;
        
	/**
	* @param {boolean} chatPopup determines if the client is inline or popup
	*/
    this.chatPopup = false;
    this.chatPopupClient = null;
    
	/**
    * variables used on setting id attribute of divs
	*/
    this.chatDivConId = null;
    this.chatDivInfoId = null;
    this.chatDivLastMessageId = null;    
    
	/**
    * variables used for message info
	*/
    this.chatLastMessage = null;                    // will hold the last message timestamp    
    this.chatLastMessageCount = 0;                  // a counter that will check when to display the last message timestamp
    this.chatLastMessageTs = 0;                     // will hold the last message timestamp and will be used to check if current message is new or not
    this.chatFirstMessageTs = 0;                   // will hold the first message timestamp and will passed to the new popup window
    this.chatCurrentDay = 0;
    
    this.chatClientHeight = "250";
    this.messageLimit = 10;
    
	/**
    * contains array of setTimeout functions
	*/
    this.g_chatClientTimer = new Array();
    this.g_timerCount = new Array();
    this.prevChatChecked = false;
    this.firstLoad = true;
    this.chatFocus = false;

	/**
	 * Flag to indicate if client is visible
	 *
	 * @type {bool}
	 */
	this.isVisible = false;

	/**
	 * Flag to indicate if client has been rendered or printed
	 *
	 * @type {bool}
	 */
	this.isRendered = false;

	/**
	 * Handle to xhr to get new messages
	 *
	 * Keep to redice overlapping requests
	 *
	 * @type {CAjax}
	 */
	this.ajaxGetMessages = false;

	/**
	 * If renderd inline then an antView will be provided
	 *
	 * @var {AntView}
	 */
	this.antView = null;
}

/**
 * Run application and build interface
 *
 * @param {object} con DOM element container for settings
 */
AntChatClient.prototype.print = function(con)
{    
    this.outerCon = con;
    this.mainCon = alib.dom.createElement("div", this.outerCon);
	alib.dom.styleSetClass(this.mainCon, "chatClient");
    
    this.conChatSound = alib.dom.createElement("div", this.mainCon);
    alib.dom.styleSet(this.conChatSound, "display", "none");
    
    this.bodyCon = alib.dom.createElement("div", this.mainCon);    
    
    if(!this.chatPopup && !this.antView)
    {
        this.buildHeader();
    }

    this.chatDivInfoId = "divChatInfo_" + this.chatFriendName;
    this.chatDivConId = "divChatCon_" + this.chatFriendName;
    
    this.conTopTools = alib.dom.createElement("div", this.bodyCon);

    this.conPrevChat = alib.dom.createElement("span", this.conTopTools);
    alib.dom.createElement("span", this.conTopTools, "&nbsp;");
    this.conClearChat = alib.dom.createElement("span", this.conTopTools);
	var clrHref = alib.dom.createElement("a", this.conClearChat, "Clear Messages");
	clrHref.href = "javascript: void(0);";
	clrHref.cls = this;
	clrHref.onclick = function() {
		this.cls.clearMessages();
	}
    
    this.conChatConv = alib.dom.createElement("div", this.bodyCon);
    this.conChatConv.setAttribute("id", this.chatDivConId);
    alib.dom.styleSet(this.conChatConv, "height", this.chatClientHeight + "px");
    alib.dom.styleSet(this.conChatConv, "overflow-y", "scroll");
    alib.dom.styleSet(this.conChatConv, "margin", "5px");
    this.conChatConv.innerHTML = "<div class='loading'></div>";
    
    this.conChatNotification = alib.dom.createElement("div", this.bodyCon);    
    alib.dom.styleSet(this.conChatNotification, "height", "15px");
    alib.dom.styleSet(this.conChatNotification, "marginLeft", "5px");
    
    this.conChatMessage = alib.dom.createElement("div", this.bodyCon);    
    alib.dom.styleSet(this.conChatMessage, "margin", "5px");
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {

        if (ret.retVal != "-1")
        {            
            this.cls.userImage = ret.userImage;
            this.cls.chatSound = ret.chatSound;
            this.cls.buildInterface();
        }
    };
    ajax.exec("/controller/Chat/getUserDetails");
}

/**
* Create or print interface
*/
AntChatClient.prototype.buildInterface = function()
{    
    this.getMessage();
    this.getIsTyping();
    this.saveIsTyping(0);
    //this.checkTimeout();
    
    var inputChatText = alib.dom.createElement("textarea", this.conChatMessage);    
    alib.dom.styleSet(inputChatText, "width", "97%");
    alib.dom.styleSet(inputChatText, "height", "50px");
    
    inputChatText.m_cls = this;
    
    inputChatText.onblur = function()
    {
        this.m_cls.chatFocus = false;
    }
    
    inputChatText.onfocus = function()
    {
        this.m_cls.chatFocus = true;
    }
    
    inputChatText.onclick = function()
    {
        this.m_cls.chatFocus = true;
    }
    
    inputChatText.onkeypress = function(evnt)
    {
        this.m_cls.chatFocus = true;
        evnt=evnt || window.event;
        
        this.m_cls.checkIsTyping(false);        
        if(evnt.shiftKey && evnt.keyCode==13)
        {            
            var lineBreak = document.createTextNode("line break");
            if(navigator.appName == 'Microsoft Internet Explorer')
                this.innerHTML = this.innerHTML + lineBreak.outerHTML;
            else
                this.appendChild(lineBreak);
            
            return;
        }
        
        switch (evnt.keyCode)
        {
            case 13: // Enter
                var message = this.value;                
                if((/\S/.test(message)))
                {
                    message = message.replace(/(\r\n|\r|\n)/g, "<br />");
                    this.m_cls.saveMessage(message);
                    this.value = '';
                    this.m_cls.saveIsTyping(0);
                }
                if(evnt.preventDefault)
                    evnt.preventDefault()
                else
                    evnt.returnValue = false;
                break;
        }
    }    

	// Begin last message timestamp check - if no messages for 5 seconds then print last message timestamp
	this.lastMessageCheck();

	// Assume visible when first rendered
	this.isVisible = true;
	this.isRendered = true;

	// Begin listening for messages
	this.messageTimer();
}

/**
* Build the header with title and buttons
*/
AntChatClient.prototype.buildHeader = function()
{
	/**
     * header container
	 */
    this.headerCon = alib.dom.createElement("div", this.bodyCon);
	alib.dom.styleSetClass(this.headerCon, "chatClientTitle");
	/*
    alib.dom.styleSet(this.headerCon, "backgroundColor", "#e5e5e5");
    alib.dom.styleSet(this.headerCon, "height", "15px");
    alib.dom.styleSet(this.headerCon, "padding", "5px");
    alib.dom.styleSet(this.headerCon, "borderBottom", "1px solid");
	*/
    
	/**
     * header title
	 */
    var divHeaderTitle = alib.dom.createElement("div", this.headerCon);
    alib.dom.styleSet(divHeaderTitle, "float", "left");
    alib.dom.styleSet(divHeaderTitle, "width", "150px");
    divHeaderTitle.innerHTML = this.chatFriendFullName ;
    
    var divHeaderIcons = alib.dom.createElement("div", this.headerCon);
    alib.dom.styleSet(divHeaderIcons, "float", "right");    
    
	/**
     * image - maximize
	 */
    var imgMaximize = alib.dom.createElement("img", divHeaderIcons);        
    alib.dom.styleSet(imgMaximize, "cursor", "pointer");
    imgMaximize.setAttribute("src", "/images/icons/new_window_16.png");                
    alib.dom.styleSet(imgMaximize, "marginRight", "2px");
    imgMaximize.m_cls = this;
    imgMaximize.onclick = function()
    {        
        this.m_cls.savePopupState(true);
        this.m_cls.popupChatClient();
        alib.dom.styleSet(this.m_cls.outerCon, "display", "none");
    }
    
	/**
     * image - close
	 */
    var imgClose = alib.dom.createElement("img", divHeaderIcons);
    alib.dom.styleSet(imgClose, "cursor", "pointer");
    imgClose.setAttribute("src", "/images/icons/close_16.png");
    imgClose.m_cls = this;
    imgClose.onclick = function()
    {        
        this.m_cls.closeWindow();
        this.m_cls.outerCon.parentNode.removeChild(this.m_cls.outerCon);
    }
        
    this.divClear(this.conFriendAdd);
}

/**
 * closes the window/client and clears all the timeout functions
 */
AntChatClient.prototype.closeWindow = function(message)
{
	// Clear all timeouts
    for(var x in this.g_chatClientTimer)
        clearTimeout(this.g_chatClientTimer[x]);
    
	this.isVisible = false;
	this.isRendered = false;
}

/**
* sends/saves message to the database
*/
AntChatClient.prototype.saveMessage = function(message)
{
    var args = [["message", message], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret.retVal != "-1")
        {
            this.cls.chatLastMessage = ret.timestamp;
            this.cls.chatDivLastMessageId = "divLastMessage_" + ret.messageTimestamp;
            ret.inline = true;
            
            var addDate = false;
            if(this.cls.chatCurrentDay > 0 && this.cls.chatCurrentDay !== ret.day)
                addDate = true;
            
            this.cls.chatCurrentDay = ret.day
            
            this.cls.buildChatInterface(ret, true, this.cls.conChatConv, addDate);
            
            /**
            * check if there's a popup client
            */
            if(this.cls.chatPopup)
            {
                var parentChatCon = window.opener.document.getElementById(this.cls.chatDivConId);
                
                if(parentChatCon)
                {
                    /**
                    * if browser is IE, use the function library (clientLib) - non-object literal notation
                    * these functions can be found at the bottom of the page
                    */
                    if(navigator.appName == 'Microsoft Internet Explorer')
                    {
                        var chatDetails = new Object();
                        chatDetails.userImage = this.cls.userImage;
                        chatDetails.chatFriendName = this.cls.chatFriendName;
                        window.opener.clientLib.buildChatInterface(ret, chatDetails, addDate);
                    }                        
                    else
                    {
                        ret.inline = false;
                        this.cls.buildChatInterface(ret, true, parentChatCon, addDate);
                    }
                }                    
            }
        }
    };
    ajax.exec("/controller/Chat/saveMessage", args);
}

/**
* get the count of previous message in chat server
*/
AntChatClient.prototype.getPrevChat = function()
{
    if(this.chatFirstMessageTs==0)                        
        return;
        
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if(ret.prevChatNum > 0)
        {
            this.cls.conPrevChat.innerHTML = "";
            var divPrevChat = alib.dom.createElement("element", this.cls.conPrevChat);
            alib.dom.styleSet(divPrevChat, "margin", "5px");
            
            var prevChatLink = alib.dom.createElement("a", divPrevChat);
            prevChatLink.setAttribute("href", "javascript: void(0);");
            prevChatLink.innerHTML = "Show Previous Message";
            prevChatLink.cls = this.cls;
            prevChatLink.onclick = function()
            {
                this.cls.conPrevChat.innerHTML = "";
                this.cls.conChatConv.innerHTML = "<div class='loading'></div>"
                this.cls.chatLastMessageTs = 0;
                this.cls.chatFirstMessageTs = 0;
                this.cls.firstLoad = true;
                this.cls.getMessage(0);
            }            
        }        
    };
    var args = [["chatFirstMessageTs", this.chatFirstMessageTs], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
    ajax.exec("/controller/Chat/getPrevChat", args);
    
    this.prevChatChecked = true;
}

/**
* get messages to the database
*/
AntChatClient.prototype.getMessage = function(limit)
{
	// Check if get is already running
	if (this.ajaxGetMessages == true)
	{
		return;
	}
	//this.ajaxGetMessages = true;


	/**
	 * set tsLastMessage to get new chat messages
	 */ 
    if(this.chatPopup && !this.g_chatClientTimer["getMessage"])
        var lastMessageTs = this.chatFirstMessageTs;
    else
        var lastMessageTs = this.chatLastMessageTs;
    
    var ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
		this.cls.ajaxGetMessages = false;
        
        if(this.cls.firstLoad)
        {            
            this.cls.conChatConv.innerHTML = "";
            this.cls.firstLoad = false;
        }            
        
        if(!ret)
            return;
        
        if (ret.retVal != "-1")
        {
            if(ret.retVal!=1)
            {                
                for (i = ret.length; i > 0; i--)
                {
                    var objMessage = ret[i-1];
                    
                    /**
                    * check if current message is new/latest 
                    */ 
                    if(objMessage.messageTimestamp > this.cls.chatLastMessageTs)
                    {
                        this.cls.buildChatSound();
                        
                        /**
                         * store the first/last message timestamp 
                         * this will be used by chat client only if in new popup window
                         */ 
                        if(this.cls.chatFirstMessageTs==0)
                        {
                            this.cls.chatFirstMessageTs = objMessage.messageTimestamp;                            
                        }                            

                        /**
                         * this will be used as chat last message sent
                         */ 
                        this.cls.chatLastMessage = objMessage.timestamp;                        
                        
                        /**
                         * this will be used to query new messages
                         */ 
                        this.cls.chatLastMessageTs = objMessage.messageTimestamp
                        this.cls.chatDivLastMessageId = "divLastMessage_" + objMessage.messageTimestamp;
                                                
                        var userChat = (objMessage.friend_message=="t") ? false:true;
                        objMessage.inline = true;
                        
                        var addDate = false;
                        if(this.cls.chatCurrentDay !== objMessage.day)
                            addDate = true;
                        
                        this.cls.chatCurrentDay = objMessage.day
                        
                        this.cls.buildChatInterface(objMessage, userChat, this.cls.conChatConv, addDate);
                    }
                }
            }
                
            if(!this.cls.prevChatChecked)
                this.cls.getPrevChat();
		
        }	
    };
    
    if(typeof limit == 'undefined')
        limit = this.messageLimit;
    
    var args = [["limit", limit], ["lastMessageTs", lastMessageTs], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
    ajax.exec("/controller/Chat/getMessage", args);

}


/**
 * Check to see if we have received a message in the last 6 seconds and print timestamp if not
 */ 
AntChatClient.prototype.lastMessageCheck = function()
{
	// Clear timeout
	if (this.g_chatClientTimer["lastMessageTimestamp"])
	{
		clearTimeout(this.g_chatClientTimer["lastMessageTimestamp"]);
		this.g_chatClientTimer["lastMessageTimestamp"] = null;
	}

    this.chatLastMessageCount++;
        
    if(this.chatLastMessageCount>=6 && this.chatLastMessage)
    {
        this.lastMessageNotification(this.conChatConv);
                            
		/**
         * check if there's a popup client        
		 */ 
        if(this.chatPopup)
        {        
            var parentChatCon = window.opener.document.getElementById(this.chatDivConId);        
            var parentChatLastMessage = window.opener.document.getElementById(this.chatDivLastMessageId);
            
            if(!parentChatLastMessage && parentChatCon)
            {                
				/**
                 * if browser is IE, use the function library (clientLib) - non-object literal notation
                 * these functions can be found at the bottom of the page                
				 */ 
                if(navigator.appName == 'Microsoft Internet Explorer')
                {                    
                    var notificationDetails = new Object();
                    notificationDetails.chatDivLastMessageId = this.chatDivLastMessageId;
                    notificationDetails.chatLastMessage = this.chatLastMessage;
                    notificationDetails.chatFriendName = this.chatFriendName;
                    
                    window.opener.clientLib.lastMessageNotification(notificationDetails);
                }                    
                else
                    this.lastMessageNotification(parentChatCon);
            }   
        }
        
        this.chatLastMessageCount = 0;
        this.chatLastMessage = null;
    }

        
	var functCls = this;
	var callback = function() {
		functCls.lastMessageCheck();
	}
	this.g_chatClientTimer["lastMessageTimestamp"] = window.setTimeout(callback, 5000);
}
    
/**
 * displays last message timestamp
 */ 
AntChatClient.prototype.lastMessageNotification = function(divChatCon)
{    
    if (typeof divChatCon == 'undefined')     
        return;
    
    var popupChatLastMessage = document.getElementById(this.chatDivLastMessageId);
    
    if(popupChatLastMessage)
        return;
    
	/**
	* add last message
	*/ 
    var divChatLastMessage = alib.dom.createElement("div", divChatCon);
    divChatLastMessage.setAttribute("id", this.chatDivLastMessageId);
    alib.dom.styleSet(divChatLastMessage, "textAlign", "center");
    alib.dom.styleSet(divChatLastMessage, "width", "200px");
    alib.dom.styleSet(divChatLastMessage, "marginBottom", "10px");
    alib.dom.styleSet(divChatLastMessage, "padding", "5px");
    alib.dom.styleSet(divChatLastMessage, "fontStyle", "italic");
    
    divChatLastMessage.innerHTML = this.chatLastMessage;
    divChatCon.scrollTop = divChatCon.scrollHeight;
}
    
/**
* builds and styles the chat interface
*/ 
AntChatClient.prototype.buildChatInterface = function(objMessage, userChat, divChatCon, addDate)
{
    if (typeof divChatCon == 'undefined')     
        return;
    
    if(userChat)
    {
        var divChatAlign = "left";
        var divImageAlign = "right";
        var chatImage = this.userImage;
        var chatMarginRight = "0px";
        var imageMarginLeft = "5px";
        var imageMarginRight = "5px";
        var divChatEntryId = "myMessage_" + objMessage.messageTimestamp;
    }
    else
    {
        var divChatAlign = "right";
        var divImageAlign = "left";
        var chatImage = this.chatFriendImage;
        var chatMarginRight = "5px";
        var imageMarginLeft = "0px";
        var imageMarginRight = "0px";
        var divChatEntryId = "friendMessage_" + objMessage.messageTimestamp;
    }
    
    
    if(objMessage.inline)
        var divChatEntry = document.getElementById(divChatEntryId)
    else
        var divChatEntry = window.opener.document.getElementById(divChatEntryId)

    if(!divChatEntry)
    {
        if(addDate)
        {
            var divDateId = "dateId_" + objMessage.currentTimestamp;
            var divDate = document.getElementById(divChatEntryId);
            
            if(!divDate)
            {
                divDate = alib.dom.createElement("div", divChatCon);
                divDate.id = divDateId;
                alib.dom.styleSet(divDate, "margin", "10px 0");
                alib.dom.styleSet(divDate, "padding", "5px 0");
                alib.dom.styleSet(divDate, "borderTop", "solid 1px");
                alib.dom.styleSet(divDate, "borderBottom", "solid 1px");
                alib.dom.styleSet(divDate, "fontWeight", "bold");
                divDate.innerHTML = objMessage.date
            }
        }
        
        var divChatEntry = alib.dom.createElement("div", divChatCon);
        
        divChatEntry.setAttribute("id", divChatEntryId);
        alib.dom.styleSet(divChatEntry, "width", "240px");    
        alib.dom.styleSet(divChatEntry, "marginBottom", "10px");
        
        var divChatMessage = alib.dom.createElement("div", divChatEntry);
		alib.dom.styleSetClass(divChatMessage, "chatClientMessage")
        alib.dom.styleSet(divChatMessage, "float", divChatAlign);
        alib.dom.styleSet(divChatMessage, "marginRight", chatMarginRight);
                       
        divChatMessage.innerHTML = this.processMessage(objMessage.message);
        
        var divChatImage = alib.dom.createElement("div", divChatEntry);
        alib.dom.styleSet(divChatImage, "float", divImageAlign);
        alib.dom.styleSet(divChatImage, "width", "40px");
        alib.dom.styleSet(divChatImage, "marginLeft", imageMarginLeft);
        alib.dom.styleSet(divChatImage, "marginRight", imageMarginRight);
        
        var imgChatImage = alib.dom.createElement("img", divChatImage);                
        alib.dom.styleSet(imgChatImage, "width", "40px");
        imgChatImage.setAttribute("src", chatImage);
        imgChatImage.setAttribute("title", objMessage.timestamp);
            
        divChatCon.scrollTop = divChatCon.scrollHeight;
            
        this.divClear(divChatEntry);
        
        this.conChatNotification.innerHTML = "";
        this.chatLastMessageCount = 0;
        
        var friendClientPopup = document.getElementById(this.chatDivInfoId);
        if(!this.chatPopup && !friendClientPopup)
        {        
            alib.dom.styleSet(this.outerCon, "display", "block");
            alib.dom.styleSet(this.outerCon.parentNode, "opacity", "100");
            alib.dom.styleSet(this.outerCon.parentNode, "visibility", "visible");
        }
    }
}


/**
* checks and sets the isTyping state
*/ 
AntChatClient.prototype.checkIsTyping = function(fromCallback)
{
    if(fromCallback)
    {
        this.userIsTyping = false;
        this.saveIsTyping(0);
        clearTimeout(this.g_typingTimer);
    }
    else if(!this.userIsTyping)
    {
        this.userIsTyping = true;
        this.saveIsTyping(1);
        
		/**
		* set timer check for isTyping state
		*/ 
        var functCls = this;            
        var callback = function()
        {
            functCls.checkIsTyping(true);
        }
        clearTimeout(this.g_chatClientTimer["checkIsTyping"]);
        
        if(this.isVisible)
            this.g_chatClientTimer["checkIsTyping"] = window.setTimeout(callback, 15000);
    }    
}

/**
* saves the isTyping state
*/ 
AntChatClient.prototype.saveIsTyping = function(isTyping)
{
    ajax = new CAjax('json');
    
    var args = [["type", "isTyping"], ["value", isTyping], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
    ajax.exec("/controller/Chat/saveChatSession", args);
    
    /*if(isTyping)
    {
        var args = [["type", "isTyping"], ["value", 1], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
        ajax.exec("/controller/Chat/saveChatSession", args);
    }
    else
    {
        var args = [["type", "isTyping"], ["value", 0], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];
        ajax.exec("/controller/Chat/clearChatSession", args);
    }*/
}

/**
* gets the isTyping state
* @returns {boolean}
*/ 
AntChatClient.prototype.getIsTyping = function()
{
	// If chat is not visible then don't make this request
	if (!this.isVisible)
	{
		var cls = this;
        this.g_chatClientTimer["getIsTyping"] = window.setTimeout(function() { cls.getIsTyping(); }, 3000);
		return;
	}

    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        clearTimeout(this.cls.g_chatClientTimer["getIsTyping"]);
        
        var functCls = this.cls;
        var callback = function()
        {
            functCls.getIsTyping();
        }
        
        if(this.cls.isVisible)
            this.cls.g_chatClientTimer["getIsTyping"] = window.setTimeout(callback, 3000);
            
        if(!ret)
            return;
            
        if(ret.isTyping==1)
            this.cls.conChatNotification.innerHTML = this.cls.chatFriendName + " is typing...";
        else
            this.cls.conChatNotification.innerHTML = "";
        
    };
    var args = [["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer], ["calledFrom", "antChatClient"]];    
    ajax.exec("/controller/Chat/getChatSession", args);
}

/**
* creates a new window for the popup chat client
*/   
AntChatClient.prototype.popupChatClient = function()
{
    if(this.chatPopup)
    {        
        var divChatInfo = window.opener.document.getElementById(this.chatDivInfoId);
        
		/**
        * check if chat friend info already created
        * if true delete the existing
		*/   
        if(divChatInfo)
            window.opener.document.body.removeChild(divChatInfo);

		/**
        * create a div chat friend info        
		*/   
        var divInfo = alib.dom.createElement("div", window.opener.document.body);        
    }
    else
    {
        var divChatInfo = document.getElementById(this.chatDivInfoId);
        
		/**
        * removes the existing div info
		*/   
        if(divChatInfo)
            document.body.removeChild(divChatInfo);
            
		/**
        * creates the div info and input form
		*/   
        var divInfo = alib.dom.createElement("div", document.body);
    }    
        
    divInfo.setAttribute("id", this.chatDivInfoId);
    alib.dom.styleSet(divInfo, "display", "none");
    
	/**
    * dynamic form sent to new window    
	*/   
    var form = alib.dom.createElement("form", divInfo);
    form.setAttribute("method", "post");        

    var popupClientName = "popupClient_"+this.chatFriendName;
    form.setAttribute("target", "popupClient");
    
	/**
    * form inputs for chat friend info
	*/   
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendName");
    hiddenField.setAttribute("value", this.chatFriendName);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendFullName");
    hiddenField.setAttribute("value", this.chatFriendFullName);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendServer");
    hiddenField.setAttribute("value", this.chatFriendServer);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendImage");
    hiddenField.setAttribute("value", this.chatFriendImage);
    
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFirstMessageTs");
    hiddenField.setAttribute("value", this.chatFirstMessageTs);
    
	/**
    * do not open new window if client already in popup window
	*/   
    if(!this.chatPopup)    
    {
        var url = "/chatloader/client";
        form.setAttribute("action", url);

        var height = parseInt(this.chatClientHeight) + 110;
        var params = 'width=285,height='+height+',toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=yes';    

        this.chatPopupClient = window.open(url, "popupClient", params);     
        form.submit();
    }
}

/**
* saves the popup state to the database
*/   
AntChatClient.prototype.savePopupState = function(chatPopup)
{
    ajax = new CAjax('json');    
    var args = [["type", "isPopup"], ["value", chatPopup], ["friendName", this.chatFriendName], ["friendServer", this.chatFriendServer]];    
    ajax.exec("/controller/Chat/saveChatSession", args);
}

/**
* creates a div element that will clear floats
*/   
AntChatClient.prototype.divClear = function(parentDiv)
{
    var divClear = alib.dom.createElement("div", parentDiv);
    alib.dom.styleSet(divClear, "clear", "both");
}

/**
* saves the popup state to the database
*/   
AntChatClient.prototype.buildDayDiv = function(divChatCon, divDate)
{
    var divDay = alib.dom.createElement("div", divChatCon);
    alib.dom.styleSet(divDay, "margin", "10px");
    divDay.innerHTML = divDate;
}

/**
* Builds sound for chat notification
*/   
AntChatClient.prototype.buildChatSound = function()
{
	/*
    if(this.chatFocus)
        return;
    

    this.conChatSound.innerHTML = "";
    var sound = alib.dom.createElement("embed", this.conChatSound);    
    sound.setAttribute("src", this.chatSound);
    sound.setAttribute("autostart", true);
    sound.setAttribute("width", 1);
    sound.setAttribute("height", 1);
    sound.setAttribute("enablejavascript", true);


	if (alib.dom.windowVisible == false || alib.dom.userActive == false)
	{
		/*
		 * This is pretty broken turn on logging to test
		//$('<audio id="chatAudio"><source src="notify.ogg" type="audio/ogg">
		/<source src="notify.mp3" type="audio/mpeg"><source src="notify.wav" type="audio/wav"></audio>').appendTo('body');
		* TODO /
	}
	*/
}

/** 
 * @depricated We now use UpdateStream to notify us of new chats
 *
 * Checks the setTimeout functions if still in the loop
 */
AntChatClient.prototype.checkTimeout = function()
{
    if(this.g_chatClientTimer)
    {
        if(this.g_chatClientTimer["getMessage"]==null)
        {
            clearTimeout(this.g_chatClientTimer["getMessage"]);
            this.messageTimer();
        }
        
        this.g_chatClientTimer["getMessage"] = null;
    }
    
    var functCls = this;
    var callback = function()
    {
        functCls.checkTimeout();
    }
    
    clearTimeout(this.g_chatClientTimer["checkTimeout"]);
    this.g_chatClientTimer["checkTimeout"] = window.setTimeout(callback, 60000);
}

/**
 * timer for getting new message
 *
 * @public
 * @this {AntChatClient} 
 */
AntChatClient.prototype.messageTimer = function()
{
	/**
	 * joe: I updated the listener to use the new Ant.UpdateStream which 
	 * provides a long poll for near real-time updates without a billion requests.
	 */
	Ant.getUpdateStream().listen("chat", function(evt) {
		if (evt.data.friendName && evt.data.friendName == evt.data.messenger.chatFriendName)
		{
			evt.data.messenger.getMessage();
		}
	}, {messenger:this});

	/*
    var functCls = this;
    var callback = function()
    {
        functCls.getMessage();
    }        
    
    if(!this.chatEndSession)
        this.g_chatClientTimer["getMessage"] = window.setTimeout(callback, 3000);
	*/
}

/**
 * Clear all current and past messages
 */
AntChatClient.prototype.clearMessages = function()
{
	var xhr = new alib.net.Xhr();
	xhr.send("/controller/Chat/removeOldMessage?friend_name=" + this.chatFriendName);
	this.conChatConv.innerHTML = "";
}

/**
 * Activate links and replace emoticons
 *
 * @param {string} message The text to process
 * @return {string} Processed message
 */ 
AntChatClient.prototype.processMessage = function(message)
{
	/**
	 * Emoticons
	 */
	var emoticons = [
		{ text: ":)", image: "smile.png" },
		{ text: ":-)", image: "smile.png" },
		{ text: ":D", image: "smile_big.png" },
		{ text: ":$", image: "blush.png" },
		{ text: ":*", image: "kiss.png" },
		{ text: ":(", image: "sad.png" },
		{ text: ";(", image: "cry.png" },
		{ text: ";)", image: "wink.png" },
		{ text: "<3", image: "heart.png" },
		{ text: "</3", image: "heartbreak.png" },
		{ text: ":O", image: "surprise.png" },
		{ text: ":o", image: "surprise.png" },
		{ text: ":P", image: "tongue.png" }
	];

	// Convert to regular expression
	for (var i in emoticons)
	{
		var escStr = emoticons[i].text.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
		message = message.replace(new RegExp(escStr, 'g'), "<img src='/images/icons/emoticons/" + emoticons[i].image + "' />");
	}

	// Handle links
	//var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    //message = message.replace(exp,"<a href='$1' target='_blank'>$1</a>");

	//URLs starting with http://, https://, or ftp://
    var exp = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    message = message.replace(exp, '<a href="$1" target="_blank">$1</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    exp = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    message = message.replace(exp, '$1<a href="http://$2" target="_blank">$2</a>');

    //Change email addresses to mailto:: links.
    exp = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
    message = message.replace(exp, '<a href="mailto:$1">$1</a>');

	return message;
}

var clientLib = {};
/**
* A non-object literal notation that will be called by 
* child (client popup window) to create a user chat message 
* in the parent window
*/   
clientLib.buildChatInterface = function(objMessage, chatDetails, addDate)
{
	/**
	* create variables from the argument object
	*/  
    var userImage = chatDetails.userImage;
    var chatFriendName = chatDetails.chatFriendName
    
    var divChatAlign = "left";
    var divImageAlign = "right";    
    var chatMarginRight = "0px";
    var imageMarginLeft = "5px";
    var imageMarginRight = "5px";    
    
    var divChatEntryId = "myMessage_" + objMessage.messageTimestamp;    
    var divChatEntry = document.getElementById(divChatEntryId)
    
    var chatDivConId = "divChatCon_" + chatFriendName;
    var divChatCon = document.getElementById(chatDivConId)
    
    if(!divChatEntry && divChatCon)
    {
        if(addDate)
        {
            var divDateId = "dateId_" + objMessage.currentTimestamp;
            var divDate = document.getElementById(divChatEntryId);
            
            if(!divDate)
            {
                divDate = alib.dom.createElement("div", divChatCon);
                divDate.id = divDateId;
                alib.dom.styleSet(divDate, "margin", "10px");
                divDate.innerHTML = objMessage.date
            }
        }
        
        var divChatEntry = alib.dom.createElement("div", divChatCon);
        
        divChatEntry.setAttribute("id", divChatEntryId);
        alib.dom.styleSet(divChatEntry, "width", "240px");    
        alib.dom.styleSet(divChatEntry, "marginBottom", "10px");
        
        var divChatMessage = alib.dom.createElement("div", divChatEntry);
		alib.dom.styleSetClass(divChatMessage, "chatClientMessage")
        alib.dom.styleSet(divChatMessage, "float", divChatAlign);
        alib.dom.styleSet(divChatMessage, "margin-right", chatMarginRight);
                       
        divChatMessage.innerHTML = objMessage.message;
        
        var divChatImage = alib.dom.createElement("div", divChatEntry);
        alib.dom.styleSet(divChatImage, "float", divImageAlign);
        alib.dom.styleSet(divChatImage, "width", "40px");
        alib.dom.styleSet(divChatImage, "marginLeft", imageMarginLeft);
        alib.dom.styleSet(divChatImage, "marginRight", imageMarginRight);
        
        var imgChatImage = alib.dom.createElement("img", divChatImage);                
        alib.dom.styleSet(imgChatImage, "width", "40px");
        imgChatImage.setAttribute("src", userImage);
        imgChatImage.setAttribute("title", objMessage.timestamp);
            
        divChatCon.scrollTop = divChatCon.scrollHeight;
        
        var divClear = alib.dom.createElement("div", divChatEntry);
        alib.dom.styleSet(divClear, "clear", "both");
    }
}

/**
* A non-object literal notation that will be called by 
* child (client popup window) to create the last message timestamp
*/  
clientLib.lastMessageNotification = function(notificationDetails)
{
	/**
	* create variables from the argument object
	*/  
    var chatDivLastMessageId = notificationDetails.chatDivLastMessageId;
    var chatLastMessage = notificationDetails.chatLastMessage;
    var chatFriendName = notificationDetails.chatFriendName;
    
    var popupChatLastMessage = document.getElementById(chatDivLastMessageId);
    
    if(popupChatLastMessage)
        return;
    
    var chatDivConId = "divChatCon_" + chatFriendName;
    var divChatCon = document.getElementById(chatDivConId)
    
	/**
    * add last message
	*/  
    var divChatLastMessage = alib.dom.createElement("div", divChatCon);
    divChatLastMessage.setAttribute("id", chatDivLastMessageId);
    alib.dom.styleSet(divChatLastMessage, "textAlign", "center");
    alib.dom.styleSet(divChatLastMessage, "width", "200px");
    alib.dom.styleSet(divChatLastMessage, "marginBottom", "10px");
    alib.dom.styleSet(divChatLastMessage, "padding", "5px");
    alib.dom.styleSet(divChatLastMessage, "fontStyle", "italic");
    
    divChatLastMessage.innerHTML = chatLastMessage;
    divChatCon.scrollTop = divChatCon.scrollHeight;    
}

/**
*A non-object literal notation that will be called by 
*child (client popup window) to recreate the div info of the friend.
*/  
clientLib.buildDivInfo = function(friend)
{
	/**
    * create variables from the argument object
	*/  

    var chatFriendName = friend.chatFriendName;
    var chatFriendServer = friend.chatFriendServer;
    var chatFriendFullName = friend.chatFriendFullName;
    var chatFriendImage = friend.chatFriendImage;
    
	/**
    * sets div chat info id
	*/  
    var chatDivInfoId = "divChatInfo_" + chatFriendName;
    var divChatInfo = document.getElementById(chatDivInfoId);
    
	/**
    * removes the existing div
	*/  
    if(divChatInfo)
        document.body.removeChild(divChatInfo);
    
	/**
    * creates and div info and input form
	*/  
    var divInfo = alib.dom.createElement("div", document.body);
        
    divInfo.setAttribute("id", chatDivInfoId);
    alib.dom.styleSet(divInfo, "display", "none");
    
	/**
    * dynamic form sent to new window    
	*/  
    var form = alib.dom.createElement("form", divInfo);
    form.setAttribute("method", "post");        

    var popupClientName = "popupClient_"+chatFriendName;
    form.setAttribute("target", "popupClient");
    
	/**
    * form inputs for chat friend info
	*/  
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendName");
    hiddenField.setAttribute("value", chatFriendName);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendFullName");
    hiddenField.setAttribute("value", chatFriendFullName);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendServer");
    hiddenField.setAttribute("value", chatFriendServer);
        
    var hiddenField = alib.dom.createElement("input", form);
    hiddenField.setAttribute("name", "chatFriendImage");
    hiddenField.setAttribute("value", chatFriendImage);    
}
