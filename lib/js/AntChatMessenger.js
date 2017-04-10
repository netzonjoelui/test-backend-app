/**
* @fileOverview AntChatMessenger: Messenger controller for netric
*
* Usage var messenger = new AntChatMessenger();
* var messenger = new AntChatMessenger();
*
* @author:  Marl Jay Tumulak, marl.tumulak@aereus.com; Copyright (c) 2011 Aereus Corporation. All rights reserved.
* @constructor AntChatMessenger
*/

function AntChatMessenger()
{
    // div containers used to build chat client
    this.outerCon = null;               // contains the outer div
    this.mainCon = null;                // contains the main div
    this.headerCon = null;              // containes the header div
    this.bodyCon = null;                // contains the body div
    this.conChatList = null;            // contains the chat list div
    this.conFriendList = null;          // contains the friend list div
    this.conTeamList = null;            // contains the team list div
    this.conFriendListLoading = null;
    this.conTeamListLoading = null;
    this.conFriendAdd = null;
        
    this.icon_online = "<img src='/images/icons/available.png' border='0' />";
    this.icon_offline = "<img src='/images/icons/offline.png' border='0' />";    
    
    // chat client variables    
    this.chatCloseState = false;
    this.chatFloatType = "right";
    this.chatFloatMargin = "0px";
    this.chatHeight = "300";
    this.chatPopup = false;
    this.chatPopup = false;
    this.friendLoaded = false;
    
    this.teamName = null;
    this.teamId = null;
    this.onlineCount = 0;
    
    this.g_chatTimer = new Array();
    this.g_timerCount = new Array();
    this.g_friendList = new Array();
    
    this.userLoggedIn = true;

	/**
	 * Handle to popup
	 *
	 * @var {alib.ui.Popup}
	 */
	this.popup = null;

	/**
	 * Container used to print inline chats
	 *
	 * @var {DOMElement}
	 */
	this.chatsCon = null;

	/**
	 * Link con where we will print status updates like number of people online
	 *
	 * @var {DOMElement}
	 */
	this.linkCon = null;

	/**
	 * Last sound played in ms
	 *
	 * @var {int}
	 */
	this.lastSoundPlayed = 0;

	/**
	 * Loaded audio tag
	 *
	 * @var {audio}
	 */
	this.audio = null;

	/**
	 * Array of clients / sessions
	 *
	 * @var {Array}
	 */
	this.clientSessions = new Array();

	/**
	 * If renderd inline then an antView will be provided
	 *
	 * @var {AntView}
	 */
	this.antView = null;
}

/**
 * Render into an AntView
 *
 * @param {AntView} antView Render this into an antview rather than a popup
 */
AntChatMessenger.prototype.renderView = function(antView)
{
	this.antView = antView;
	this.print(antView.con);
}

/** 
* Run application and build interface
* @param {object} con  DOM element container for settings
*/
AntChatMessenger.prototype.print = function(con)
{
	this.linkCon = con;

	// Setup Container
	if (this.antView)
		this.outerCon = alib.dom.createElement("div", con);
	else 
		this.outerCon = alib.dom.createElement("div", document.body);

    // start the styling of div container for Ant Chat
    this.outerCon.setAttribute("id", "antChatCon");
	if (!this.antView)
	{
		alib.dom.styleSet(this.outerCon, "position", "absolute");
		alib.dom.styleSet(this.outerCon, "display", "none");
		alib.dom.styleSet(this.outerCon, "z-index", "10000");
	}

	var width = alib.dom.getClientWidth() - 260;
	// Add inline chats container
	this.chatsCon = alib.dom.createElement("div", this.outerCon);
	if (!this.antView)
	{
		alib.dom.styleSet(this.chatsCon, "position", "absolute");
		alib.dom.styleSet(this.chatsCon, "left", "-" + (width) + "px");
		alib.dom.styleSet(this.chatsCon, "width", width + "px");
		alib.dom.styleSet(this.chatsCon, "margin-right", "260px");
		alib.dom.styleSet(this.chatsCon, "height", "1px"); // hide it so it does not block page clicks
	}

	// Create Popup
	if (!this.antView)
	{
		var popup = new alib.ui.Popup(this.outerCon);
		popup.anchorToEl(con, "down");
		con.onclick = function() { popup.setVisible(); }
		this.popup = popup;

		// Capture hide event to set flag of clients to indicate state
		alib.events.listen(this.popup, "onHide", function(evt) {
			for (var ind in evt.data.msngr.clientSessions)
				evt.data.msngr.clientSessions[ind].isVisible = false;
		}, {msngr:this});
		alib.events.listen(this.popup, "onShow", function(evt) {
			for (var ind in evt.data.msngr.clientSessions)
			{
				if (evt.data.msngr.clientSessions[ind].isRendered)
					evt.data.msngr.clientSessions[ind].isVisible = true;
			}
		}, {msngr:this});
	}

    this.mainCon = alib.dom.createElement("div");    
    this.mainCon.setAttribute("id", "chatMessengerCon");
	if (!this.antView)
	{
		alib.dom.styleSet(this.mainCon, "float", this.chatFloatType);
		alib.dom.styleSet(this.mainCon, "margin" + this.chatFloatType.capitalize(), this.chatFloatMargin);        
		alib.dom.styleSetClass(this.mainCon, "chatMessenger");
	}
    
    this.bodyCon = alib.dom.createElement("div", this.mainCon);
    this.bodyCon.setAttribute("id", "chatMessengerBody");
	if (!this.antView)
    	alib.dom.styleSet(this.bodyCon, "width", "250px");
    
    if(!this.chatPopup && !this.antView)
    {
        this.buildHeader();
    }
    
    this.conChatList = alib.dom.createElement("div", this.bodyCon);    
    this.conChatList.setAttribute("id", "chatMessengerList");
	if (!this.antView)
	{
		alib.dom.styleSet(this.conChatList, "height", this.chatHeight + "px");
		alib.dom.styleSet(this.conChatList, "margin", "5px");
		alib.dom.styleSet(this.conChatList, "overflow-y", "scroll");    
	}
    
    this.conFriendList = alib.dom.createElement("div", this.conChatList);
    this.conFriendList.setAttribute("id", "chatMessengerFriendList");
    
    this.conTeamList = alib.dom.createElement("div", this.conChatList);
    this.conTeamList.setAttribute("id", "chatMessengerTeamList");
    alib.dom.styleSet(this.conTeamList, "marginBottom", "15px");
    
    this.conFriendListLoading = alib.dom.createElement("div", this.conFriendList);
    this.conFriendListLoading.setAttribute("id", "chatMessengerFriendLoading");
    this.conFriendListLoading.innerHTML = " <div class='loading'></div>";    
    
    this.conFriendAdd = alib.dom.createElement("div", this.bodyCon);
    alib.dom.styleSet(this.conFriendAdd, "margin", "5px");    

	// Add HTML5 audio notification
	this.audio = alib.dom.createElement("audio", this.outerCon);
	this.audio.innerHTML = '<source src="/media/audio/ding.mp3" type="audio/mpeg">'
					+ '<source src="/media/audio/ding.wav" type="audio/wav">';
	///$('<audio id="chatAudio"><source src="notify.ogg" type="audio/ogg">
		///</audio>').appendTo('body');
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret.retVal != "-1")
        {            
            this.cls.teamName = ret.team_name;
            this.cls.teamId = ret.team_id;            
            this.cls.buildInterface();
        }
    };
    ajax.exec("/controller/Chat/getUserDetails");
    
    // For testing the logout user (session timeout)
    /*var endSession = alib.dom.setElementAttr(alib.dom.createElement("button", con.parentNode), [["innerHTML", "End User Session"]]);
    endSession.cls = this;
    endSession.onclick = function()
    {
        this.cls.userLoggedIn = false;
    }
    
    var startSession = alib.dom.setElementAttr(alib.dom.createElement("button", con.parentNode), [["innerHTML", "Start User Session"]]);
    startSession.cls = this;
    startSession.onclick = function()
    {
        this.cls.userLoggedIn = true;
    }*/
}

/** 
* Create or print interface 
*/
AntChatMessenger.prototype.buildInterface = function()
{   
    // chat friends list    
    if(this.teamId>0)
    {
        var divFriendRow = alib.dom.createElement("div", this.conTeamList);
        alib.dom.styleSet(divFriendRow, "marginTop", "5px");
        alib.dom.styleSet(divFriendRow, "fontWeight", "bold");
        divFriendRow.innerHTML = this.teamName;
        
        this.conTeamListLoading = alib.dom.createElement("div", this.conTeamList);
        this.conTeamListLoading.setAttribute("id", "chatMessengerTeamLoading");
        this.conTeamListLoading.innerHTML = " <div class='loading'></div>";
    }
    
    
    this.updateFriends();
        
	/*
    // chat text box
    var divFriendText = alib.dom.createElement("div", this.conFriendAdd);
    alib.dom.styleSet(divFriendText, "marginTop", "5px");
    var inputFriendAdd = alib.dom.createElement("input", divFriendText);
    var t = new CTextBoxList(inputFriendAdd, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: false, minLength: 2, queryRemote: true, remote: {url:"/users/json_autocomplete.php"}}}});
    
    var divFriendButton = alib.dom.createElement("div", this.conFriendAdd);
    alib.dom.styleSet(divFriendButton, "float", "right");
    
    // add friend button
    var btnFriendAdd = alib.dom.createElement("button", divFriendButton);    
    btnFriendAdd.innerHTML = "Add Contact";
    btnFriendAdd.m_textBoxList = t;
    btnFriendAdd.m_cls = this;
    btnFriendAdd.onclick = function()
    {        
        this.m_cls.submitAddFriend (this.m_textBoxList);
    }
    
    this.divClear(this.conFriendAdd);
	*/
    
    // check for new messages    
    this.getNewMessages();    
    //this.checkFriendsOnline();
    this.checkTimeout();
    
    // sets the user's status to online
    this.setStatus(true);
    
    this.outerCon.insertBefore(this.mainCon, this.outerCon.firstChild);    
}

/** 
* build the header with title and buttons
*/
AntChatMessenger.prototype.buildHeader = function()
{
    // header container
    this.headerCon = alib.dom.createElement("div", this.bodyCon);
	alib.dom.styleSetClass(this.headerCon, "chatMessengerTitle");
    
    // header title
    var divHeaderTitle = alib.dom.createElement("div", this.headerCon);
    alib.dom.styleSet(divHeaderTitle, "float", "left");
    alib.dom.styleSet(divHeaderTitle, "width", "150px");
    divHeaderTitle.innerHTML = "Netric Chat";
    
    var divHeaderIcons = alib.dom.createElement("div", this.headerCon);
    alib.dom.styleSet(divHeaderIcons, "float", "right");    
    
    // image - minimize
	/*
    var imgMinimize = alib.dom.createElement("img", divHeaderIcons);        
    alib.dom.styleSet(imgMinimize, "cursor", "pointer");
    imgMinimize.setAttribute("src", "/images/icons/min.gif");
    alib.dom.styleSet(imgMinimize, "marginRight", "2px");
    imgMinimize.m_cls = this;
    imgMinimize.onclick = function()
    {
        // check if there's no any ant client open inline
        // if true, then hide the antChatCon (dropdown window) too
        if(this.m_cls.outerCon.childNodes.length==1)
            alib.dom.styleSet(this.m_cls.outerCon, "visibility", "hidden");
            
        alib.dom.styleSet(this.m_cls.mainCon, "display", "none");
    }
	*/
    
    // image - maximize
    var imgMaximize = alib.dom.createElement("img", divHeaderIcons);        
    alib.dom.styleSet(imgMaximize, "cursor", "pointer");
    imgMaximize.setAttribute("src", "/images/icons/new_window_16.png");                
    alib.dom.styleSet(imgMaximize, "marginRight", "2px");
    imgMaximize.m_cls = this;
    imgMaximize.onclick = function()
    {
		this.m_cls.popup.setVisible(false);
                
        this.m_cls.popupMessenger();        
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
		this.m_cls.popup.setVisible(false);
    }
    
    this.divClear(this.bodyCon);
}

/** 
 * closes the window/client and clears all the timeout functions
 */
AntChatMessenger.prototype.closeWindow = function(message)
{
    // clears all the timeout functions
    for(var x=0; x<this.g_chatTimer; x++)
        clearTimeout(this.g_chatTimer[x]);
    
    // check if there's no any ant client open inline
    // if true, then hide the antChatCon (dropdown window) too
    //if(this.outerCon.childNodes.length==1)        
        //alib.dom.styleSet(this.outerCon, "visibility", "hidden");
    
    this.outerCon.removeChild(this.mainCon);
    this.chatCloseState = true;
    
    // sets the user's status to offline
    this.setStatus(false);
}

/** 
* Loops thru the list of friend name
*/
AntChatMessenger.prototype.submitAddFriend = function(textBoxList)
{
    var values = textBoxList.getValues();
    for (var i = 0; i < values.length; i++)
    {
        if (values[i][0] || values[i][1])
        {
            var parts = values[i][1].split(" ");
            this.addFriend(parts[0]);
        }
    }
    textBoxList.clear();
}


/** 
* Submits the friend name to the AntChat_SvrJson::addFriend
*/
AntChatMessenger.prototype.addFriend = function(friendName)
{
    var args = [["friendName", friendName], ["teamId", this.teamId]];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret.retVal != "-1")
        {
            this.cls.updateFriends();            
        }
        else
            alert(ret.retError);
    };
    ajax.exec("/controller/Chat/addFriend", args);
}

/** 
* Retrieves the json encoded data from AntChat_SvrJson::getFriendList
*/
AntChatMessenger.prototype.updateFriends = function()
{
    var ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        this.cls.updateFriendsTimer();
        
        if(!this.cls.friendLoaded)
        {
            if(this.cls.conTeamListLoading)
                this.cls.conTeamListLoading.innerHTML = "";
                
            this.cls.conFriendListLoading.innerHTML = "";
            this.cls.friendLoaded = true;
        }
        
        if(!ret)
            return;
        
        if(ret)
        {
            var onlineCount = 0;
            // Get all friends
            for (i = 0; i < ret.length; i++)
            {
                var friend = ret[i];
                this.cls.buildFriendList(friend);
                
                onlineCount += friend.online;
            }

			var ico = (onlineCount > 0 ) ? "chat_24_on.png" : "chat_24_off.png";

			var buf = "<img src='/images/icons/" + ico + "'  style='vertical-align:middle;'>";
            
			if (!this.cls.antView)
				this.cls.linkCon.innerHTML =  buf;
        }
    };
    var args = [["teamId", this.teamId]];
    ajax.exec("/controller/Chat/getFriendList", args);
}

/** 
* Builds the chat friends list. Either Friend List or Team List
*/
AntChatMessenger.prototype.buildFriendList = function(friend)
{    
    // chat friend info
    var friendId = friend.id;
    var friendTeamId = friend.teamId;
    var friendName = friend.friend_name;
    var friendServer = friend.friendServer;
    var friendFullName = friend.fullName;
    var friendImage = friend.image;
    var friendOnline = friend.online;
    
    var statusCon = this.buildFriendStatus(friend);
    
    // store all friends inside the array
    // this array is used on getting friend's info using friendName
    this.g_friendList[friendName] = friend;
    
    // Look for this user in a row
    var divFriendRowId = ALib.m_document.getElementById("divFriend_" + friendId);
    if (divFriendRowId)
    {
        ALib.m_document.getElementById("divRowFriendStatus_" + friendId).innerHTML = (friendOnline == 1) ? this.icon_online : this.icon_offline;
        ALib.m_document.getElementById("divRowFriendStatusText_" + friendId).innerHTML = "";
        ALib.m_document.getElementById("divRowFriendStatusText_" + friendId).appendChild(statusCon);
    }
    else
    {
        var divFriendRow;
        if(friendTeamId==this.teamId)
            divFriendRow = alib.dom.createElement("div", this.conTeamList);
        else
            divFriendRow = alib.dom.createElement("div", this.conFriendList);
            
        alib.dom.styleSet(divFriendRow, "marginTop", "10px");
        divFriendRow.setAttribute("id", "divFriend_" + friendId);        
        var divFriendImage = alib.dom.createElement("div", divFriendRow);        
        alib.dom.styleSet(divFriendImage, "width", "45px");
        alib.dom.styleSet(divFriendImage, "marginRight", "5px");
        alib.dom.styleSet(divFriendImage, "float", "left");
        divFriendImage.innerHTML = "&nbsp;";
        
        var imgFriendImage = alib.dom.createElement("img", divFriendImage);        
        imgFriendImage.setAttribute("id", "imgFriend_" + friendId);
        alib.dom.styleSet(imgFriendImage, "width", "40px");
        imgFriendImage.setAttribute("src", friendImage);                
        
        var divFriendNameStatus = alib.dom.createElement("div", divFriendRow);
        alib.dom.styleSet(divFriendNameStatus, "width", "135px");
        alib.dom.styleSet(divFriendNameStatus, "float", "left");
        
        var divFriendServer = alib.dom.createElement("div", divFriendNameStatus);
        divFriendServer.setAttribute("id", "friendServer_" + friendId);
        alib.dom.styleSet(divFriendServer, "display", "none");
        divFriendServer.innerHTML = friendServer;
        
        var divFriendName = alib.dom.createElement("div", divFriendNameStatus);
        divFriendName.setAttribute("id", "friendName_" + friendId);
        divFriendName.setAttribute("title", friendName);
        
        var aFriendName = alib.dom.createElement("a", divFriendName);
        alib.dom.styleSet(aFriendName, "cursor", "pointer");
        aFriendName.innerHTML = friendFullName;
        aFriendName.m_cls = this;        
        aFriendName.m_friend = friend;
        aFriendName.onclick = function()
        {            
            var friendName = this.m_friend.friend_name;
            var friendChatClient = document.getElementById('chatClient_'+friendName);
            var friendClientPopup = document.getElementById('divChatInfo_'+friendName);
            
            if(this.m_cls.chatPopup)
            {
                this.m_cls.chatFriendPopup(this.m_friend);
            }
            else if(friendClientPopup)
            {                
            }
            else if(this.m_cls.antView)
            {                
                this.m_cls.chatFriendView(this.m_friend);
			}
            else
            {
                if(friendChatClient)
                    alib.dom.styleSet(friendChatClient, "display", "block");
                else
                    this.m_cls.chatFriend(this.m_friend, true);
            }            
        }
                        
        var divFriendStatusText = alib.dom.createElement("div", divFriendNameStatus);
        divFriendStatusText.setAttribute("id", "divRowFriendStatusText_" + friendId)
        divFriendStatusText.innerHTML = "";
        divFriendStatusText.appendChild(statusCon);
        
        var divFriendStatus = alib.dom.createElement("div", divFriendRow);
        alib.dom.styleSet(divFriendStatus, "float", "left");
        divFriendStatus.setAttribute("id", "divRowFriendStatus_" + friendId)
        divFriendStatus.innerHTML = (friendOnline == 1) ? this.icon_online : this.icon_offline;
        
        if(friendTeamId!=this.teamId || this.teamId==0)
        {
            var divFriendDelete = alib.dom.createElement("div", divFriendRow);
            alib.dom.styleSet(divFriendDelete, "float", "left");
            
            var imgFriendDelete = alib.dom.createElement("img", divFriendDelete);
            alib.dom.styleSet(imgFriendDelete, "cursor", "pointer");
            alib.dom.styleSet(imgFriendDelete, "marginTop", "2px");
            imgFriendDelete.setAttribute("src", "/images/themes/softblue/icons/deleteTask.gif");
            imgFriendDelete.m_friendRow = divFriendRow;
            imgFriendDelete.m_friendId = friendId;
            imgFriendDelete.m_cls = this;
            imgFriendDelete.onclick = function()
            {
                this.m_cls.removeFriend(this.m_friendId, this.m_friendRow);
            }
        }
        
        this.divClear(divFriendRow);        
    }    
}

/** 
* Builds the friends status
*/
AntChatMessenger.prototype.buildFriendStatus = function(friend)
{
    var friendId = friend.id;    
    var friendName = friend.friend_name;
    var statusText = friend.statusText;
    var inviteStatus = friend.inviteStatus;
    
    // Setup Status
    var statusCon = alib.dom.createElement("div");
    alib.dom.styleSet(statusCon, "width", "160px"); 
    
    var cancelLink = alib.dom.setElementAttr(alib.dom.createElement("a"), [["innerHTML", "[Cancel]"]]);
    alib.dom.styleSet(cancelLink, "cursor", "pointer"); 
    alib.dom.styleSet(cancelLink, "color", "#0000FF"); 
    
    var approveLink = alib.dom.setElementAttr(alib.dom.createElement("a"), [["innerHTML", "[Accept]"]]);
    alib.dom.styleSet(approveLink, "cursor", "pointer"); 
    alib.dom.styleSet(approveLink, "color", "#0000FF"); 
    
    var spacer = alib.dom.setElementAttr(alib.dom.createElement("span"), [["innerHTML", " | "]]);
    
    // Link Actions
    cancelLink.cls = this;
    cancelLink.friendId = friendId;
    cancelLink.friendName = friendName;
    cancelLink.onclick = function()
    {
        this.cls.processStatus(this.friendId, this.friendName, 0);
    }
    
    approveLink.cls = this;
    approveLink.friendId = friendId;
    approveLink.friendName = friendName;
    approveLink.statusCon = statusCon;
    approveLink.onclick = function()
    {
        this.cls.processStatus(this.friendId, this.friendName, 1, statusCon);
    }
    
    switch(inviteStatus)
    {
        case "1": // Pending Request
            statusCon.innerHTML = "Pending Request "
            statusCon.appendChild(cancelLink);
            break;
        case "2": // Friend Invite
            cancelLink.innerHTML = "[Decline]";
            statusCon.innerHTML = "Friend Invite "
            statusCon.appendChild(approveLink);
            statusCon.appendChild(spacer);
            statusCon.appendChild(cancelLink);
            break;
        default:
            statusCon.innerHTML = statusText;
            break;
    }
    
    return statusCon;
}
    
/** 
* get the friend usernames of the user that sent new messages
* Status:   0 = Cancel; 1 = Approve
*/
AntChatMessenger.prototype.processStatus = function(friendId, friendName, status, statusCon)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.friendId = friendId;
    ajax.cbData.status = status;
    ajax.cbData.statusCon = statusCon;
    ajax.onload = function(ret)
    {
        if(this.cbData.status == 0)
        {
            var friendCon = document.getElementById("divFriend_" + this.cbData.friendId);            
            friendCon.parentNode.removeChild(friendCon);
        }
        else
        {
            this.cbData.statusCon.innerHTML = "Friend successfully added.";
            this.cbData.cls.updateFriends();
        }
    };
    var args = new Array();
    args[args.length] = ['friendName', friendName];
    args[args.length] = ['status', status];
    ajax.exec("/controller/Chat/processStatus", args);
}
    
/** 
* get the friend usernames of the user that sent new messages
*/
AntChatMessenger.prototype.getNewMessages = function()
{
	/**
	 * joe: I updated the listener to use the new Ant.UpdateStream which 
	 * provides a long poll for near real-time updates without a billion requests.
	 */
	Ant.getUpdateStream().listen("chat", function(evt) {
		if (evt.data.friendName)
		{
			var friendName = evt.data.friendName;
			var friendServer = "";

			// Send notification
			var now = new Date();
			if ((alib.dom.windowVisible == false || alib.dom.userActive == false) && evt.data.messenger.lastSoundPlayed < (now.getTime()-1000))
			{
				evt.data.messenger.audio.play();
				evt.data.messenger.lastSoundPlayed = now;
			}

			var friendInfo = evt.data.messenger.g_friendList[friendName];
			var friendChatClient = document.getElementById('chatClient_'+friendName);
			var friendClientPopup = document.getElementById('divChatInfo_'+friendName);
			
			if(friendClientPopup)
			{
			}
			else if(friendChatClient)
			{                
				evt.data.messenger.popup.setVisible(true);
			}            
			else if (friendInfo && evt.data.messenger.popup != null)
			{
				evt.data.messenger.popup.setVisible(true);
				evt.data.messenger.chatFriend(friendInfo, false);
			}
			
			evt.data.messenger.updateNewMessage(friendName, friendServer);
		}
	}, {messenger:this});


	/**
	 * This is the old poll system that pinged the server every couple seconds
	 *
    var ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        this.cls.newMessageTimer();
        
        if(!ret)
            return;
        
        if(ret)
        {
            // Get the friend usernames
            for(friend in ret)
            {
                var currentFriend = ret[friend];
                var friendName = currentFriend.friendName;
                var friendServer = currentFriend.friendServer;

                if(currentFriend.isNewMessage == 0)
                    continue;

				// Send notification
				var now = new Date();
				if ((alib.dom.windowVisible == false || alib.dom.userActive == false) && this.cls.lastSoundPlayed < (now.getTime()-1000))
				{
					this.cls.audio.play();
					this.cls.lastSoundPlayed = now;
				}

                var friendInfo = this.cls.g_friendList[friendName];
                var friendChatClient = document.getElementById('chatClient_'+friendName);
                var friendClientPopup = document.getElementById('divChatInfo_'+friendName);
                
                if(friendClientPopup)
                {
                }
                else if(friendChatClient)
                {                
					this.cls.popup.setVisible(true);
                }            
                else
                {
                    if(friendInfo)
                    {
						this.cls.popup.setVisible(true);
                        this.cls.chatFriend(friendInfo, false);
                    }                    
                }
                
                this.cls.updateNewMessage(friendName, friendServer);
            }
        }
    };
    var args = [["calledFrom", "antChatMessenger"]];    
    ajax.exec("/controller/Chat/getChatSession", args);
	*/
}

/**
* updates the new message session
*/ 
AntChatMessenger.prototype.updateNewMessage = function(friendName, friendServer)
{
	// Update is still running
	if (this.ajaxClear)
		return;
	
    this.ajaxClear = new CAjax('json');
	this.ajaxClear.cbData.cls = this;
	this.ajaxClear.onload = function() { this.cbData.cls.ajaxClear = null }

    var args = [["type", "isNewMessage"], ["value", 0], ["friendName", friendName], ["friendServer", friendServer]];
    this.ajaxClear.exec("/controller/Chat/clearChatSession", args);
}

/** 
 * Builds the chat client. 
 */
AntChatMessenger.prototype.chatFriend = function(friend, chatFocus)
{    
    var friendName = friend.friend_name;
    var friendServer = friend.friendServer;
    var friendFullName = friend.fullName;
    var friendImage = friend.image;    

    var divChatFriend = alib.dom.createElement("div", this.chatsCon);
    divChatFriend.setAttribute("id", "chatClient_"+friendName);
    alib.dom.styleSet(divChatFriend, "float", this.chatFloatType);
    alib.dom.styleSet(divChatFriend, "width", "275px");
    alib.dom.styleSet(divChatFriend, "margin" + this.chatFloatType.capitalize(), "2px");
    
    var chatClient = new AntChatClient();
    chatClient.chatFriendName = friendName;
    chatClient.chatFriendFullName = friendFullName;
    chatClient.chatFriendServer = friendServer;
    chatClient.chatFriendImage = friendImage;    
    chatClient.chatFocus = chatFocus;
    chatClient.print(divChatFriend);    
	this.clientSessions[friendName] = chatClient;
}

/** 
* Builds the chat client in new popup window
*/
AntChatMessenger.prototype.chatFriendPopup = function(friend)
{    
    var chatFriendName = friend.friend_name;
    var chatFriendServer = friend.friendServer;
    var chatFriendFullName = friend.fullName;
    var chatFriendImage = friend.image;
    
    // sets div chat info id
    var chatDivInfoId = "divChatInfo_" + chatFriendName;
    var divChatInfo = document.getElementById(chatDivInfoId);
    
    // removes the existing div
    if(divChatInfo)
        document.body.removeChild(divChatInfo);
    
    // create div inside body element to hold the chat friend info
    var divInfo = alib.dom.createElement("div");    
    divInfo.setAttribute("id", chatDivInfoId);
    alib.dom.styleSet(divInfo, "display", "none");
    
    // dynamic form sent to new window
    var form = alib.dom.createElement("form", divInfo);
    form.setAttribute("method", "post");        

    form.setAttribute("target", "formChatInfo_"+chatFriendName);
    
    // form inputs for chat friend info
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
    
    // open new window if messenger in popup window
    if(this.chatPopup)
    {        
        document.body.appendChild(divInfo);
        
        var url = "/chatloader/client";
        form.setAttribute("action", url);
                
        var params = 'width=285,height=360,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=yes';    

        window.open(url, "popupClient", params);
        form.submit();
    }
}

/** 
 * Load a chat client into an antView
 */
AntChatMessenger.prototype.chatFriendView = function(friend)
{    
    var friendName = friend.friend_name;
    var friendServer = friend.friendServer;
    var friendFullName = friend.fullName;
    var friendImage = friend.image;    

	var chatView = this.antView.getView(friendName);
	if (!chatView)
	{
		chatView = this.antView.addView(friendName, {cls:this});
		chatView.render = function() {
			var chatClient = new AntChatClient();
			chatClient.chatFriendName = friendName;
			chatClient.chatFriendFullName = friendFullName;
			chatClient.chatFriendServer = friendServer;
			chatClient.chatFriendImage = friendImage;
			chatClient.antView = this;
			chatClient.print(this.con);    
	
			this.options.cls.clientSessions[friendName] = chatClient;
		}
	}

	// Load the view
	this.antView.navigate(friendName);
}

/** 
* saves the user status (online or not)
*/
AntChatMessenger.prototype.setStatus = function(isOnline)
{
    var args = [["type", "isOnline"], ["value", isOnline], ["friendName", "[all]"]];    
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Chat/saveChatSession", args);
}


/** 
* Get number of chat friends that are currently online
*/
/*AntChatMessenger.prototype.checkFriendsOnline = function(count)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if(ret)
        {
            // Get all friends
            var spanFriendOnline = document.getElementById('chatFriendOnline');
            
            // reload friend list
            if(this.cls.friendLoaded && this.cls.onlineCount !== ret.onlineCount)
            {
                this.cls.updateFriends();            
            }
            
            this.cls.onlineCount = ret.onlineCount;
            
            if(spanFriendOnline)
                spanFriendOnline.innerHTML = ret.onlineCount;
                
            this.cls.friendOnlineTimer();
        }
    };
    ajax.exec("/controller/Chat/countFriendOnline");
}*/

/** 
* Removes a friend in the chat list
*/
AntChatMessenger.prototype.removeFriend = function(friendId, cell)
{
    if (confirm("Are you sure you want to permanantly remove this friend?"))
    {
        var args = [["friendId", friendId]];
        
        ajax = new CAjax('json');
        ajax.cell = cell;
        ajax.onload = function(ret)
        {
            if (ret.retVal != "-1")
            {
                this.cell.parentNode.removeChild(cell);
            }
        };
        ajax.exec("/controller/Chat/deleteFriend", args);
    }
}

/** 
* creates a new window for the popup chat messenger
*/
AntChatMessenger.prototype.popupMessenger = function()
{        
    var height = parseInt(this.chatHeight) + 90;
    var params = 'width=270,height='+height+',toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=no';
    
    var url = "/chatloader/messenger";    
    window.open(url, "popupMessenger", params);
}

/** 
* creates a div element that will clear floats
*/
AntChatMessenger.prototype.divClear = function(parentDiv)
{
    var divClear = alib.dom.createElement("div", parentDiv);
    alib.dom.styleSet(divClear, "clear", "both");
}

/** 
* Checks the setTimeout functions if still in the loop
*/
AntChatMessenger.prototype.checkTimeout = function()
{
    if(this.g_chatTimer)
    {
        if(this.g_chatTimer["getNewMessages"]==null)
        {
            clearTimeout(this.g_chatTimer["getNewMessages"]);
            clearTimeout(this.g_chatTimer["updateFriends"]);
            this.updateFriendsTimer();
            this.newMessageTimer();
        }
        
        this.g_chatTimer["getNewMessages"] = null;
    }
    
    var functCls = this;
    var callback = function()
    {            
        functCls.checkTimeout();
    }
    
    clearTimeout(this.g_chatTimer["checkTimeout"]);    
    this.g_chatTimer["checkTimeout"] = window.setTimeout(callback, 60000);
}

/**
 * Timer for friends online
 *
 * @public
 * @this {AntChatMessenger} 
 */
/*AntChatMessenger.prototype.friendOnlineTimer = function()
{
    clearTimeout(this.g_chatTimer["checkFriendsOnline"]);
    if(alib.dom.userActive)
    {
        var functCls = this;
        var callback = function()
        {            
            functCls.checkFriendsOnline();
        }
        
        this.g_chatTimer["checkFriendsOnline"] = window.setTimeout(callback, 5000);
    }
    else
        this.g_chatTimer["checkFriendsOnline"] = null;
}*/

/**
 * Timer for update friends
 *
 * @public
 * @this {AntChatMessenger} 
 */
AntChatMessenger.prototype.updateFriendsTimer = function()
{
    var functCls = this;        
    var callback = function()
    {            
        functCls.updateFriends();
    }

    this.g_chatTimer["updateFriends"] = window.setTimeout(callback, 50000);
}

/**
 * Timer for new messages
 *
 * @public
 * @this {AntChatMessenger} 
 */
AntChatMessenger.prototype.newMessageTimer = function()
{
    var functCls = this;        
    var callback = function()
    {
        functCls.getNewMessages();            
    }
    
    this.g_chatTimer["getNewMessages"] = window.setTimeout(callback, 3000);
}
