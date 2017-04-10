/****************************************************************************
*	
*	Class:		CWidFriends
*
*	Purpose:	Frinds/chat application
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidFriends()
{
	this.title = "Online Friends";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Dropdown menu will be set by parent

	this.m_menus = new Array();
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidFriends.prototype.main = function()
{
	var cls = this;
	Ant.setHinst(cls, "/home/widgets/friends");

	// Create Status Dropdown
	// -----------------------------------------------------------------------------
	var dm = new CDropdownMenu();
	this.m_menus.push(dm);
	dm.addEntry("Available", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ["Available"]);
	dm.addEntry("Busy", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ["Busy"]);
	dm.addEntry("Invisible", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ["Invisible"]);
	dm.addEntry("Be Right Back", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ["Be Right Back"]);
	dm.addEntry("Not at My Desk", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ["Not at My Desk"]);
	dm.addEntry("Custom", cls.setMyStatus, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png");
	var dm_dv = dm.createButtonMenu("<span id='friends_statusid'>Loading...</span>");
	
	var pdiv = ALib.m_document.createElement("div");
	var right_div = ALib.m_document.createElement("div");
	if (Ant.m_browser.ie)
		right_div.style.styleFloat = 'right';
	else
		right_div.style.cssFloat = 'right';

	right_div.style.paddingRight = '3px';
	right_div.style.paddingTop = '3px';
	pdiv.appendChild(dm_dv);
	right_div.appendChild(pdiv);

	this.m_container.appendChild(right_div);
	
	// Build Friends Table
	// -----------------------------------------------------------------------------
	var pdiv = ALib.m_document.createElement("div");
	pdiv.style.clear = "both";
	
	var table = ALib.m_document.createElement("table");
	table.style.width = "100%";
	var tbody = ALib.m_document.createElement("tbody");
	this.m_friendsTbody = tbody;
	table.appendChild(tbody);
	pdiv.appendChild(table);
	this.m_container.appendChild(pdiv);

	// Create context menu
	// ----------------------------------------------------------------------------- 
	var wnd_params = "top=200,left=100,width=300,height=350,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes";
	this.m_dm.addEntry('Manage Friends', function(wnd_params) { window.open('/chat/ant_messenger.awp', 'messenger', wnd_params); }, null, null, [wnd_params]);
	this.m_dm.addEntry('Change My Display Name', cls.changeMyName);

	// Continue execution
	// ----------------------------------------------------------------------------- 
	this.updateFriends();
	this.getMyStatus();
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidFriends.prototype.exit= function()
{
	if (this.m_timer)
		clearTimeout(this.m_timer);

	Ant.clearHinst('/home/widgets/friends');
		
	for (var i = 0; i < this.m_menus.length; i++)
	{
		this.m_menus[i].destroyMenu();
	}
	
	this.m_container.innerHTML = "";
}

CWidFriends.prototype.updateFriends = function()
{
	var ajax = new CAjax();
	ajax.m_tbody = this.m_friendsTbody;
	ajax.m_widcls = this;
	// Set callback once xml is loaded
	ajax.onload = function(root)
	{
		try
		{
			// Get all friends
			for (i = 0; i < root.getNumChildren(); i++)
			{
				var friend = root.getChildNode(i);

				if (friend.m_name == "friend")
				{
					var uid = friend.getChildNodeValByName("uid");
					var uname = unescape(friend.getChildNodeValByName("uname"));
					var online = friend.getChildNodeValByName("online");
					var status_text = unescape(friend.getChildNodeValByName("status_text"));
					var friend_id = friend.getChildNodeValByName("friend_id");

					// Get table
					var tbl_body = this.m_tbody;

					// Look for this user in a row
					var urow = ALib.m_document.getElementById("widget_friends_row_" + uid);
					if (urow)
					{
						if (online != 1)
							tbl_body.removeChild(urow);
						else
							ALib.m_document.getElementById("widget_frinds_uid_status" + uid).innerHTML = status_text;
					}
					else
					{
						if (online == 1)
						{
							var row = ALib.m_document.createElement("tr");
							row.id = "widget_friends_row_" + uid;
							row.valign='middle';
							tbl_body.appendChild(row);
							
							var td = ALib.m_document.createElement("td");
							td.style.width = "16px";
							td.style.cursor = "pointer";
							td.align = "center";
							//td.innerHTML = "<img src='/images/themes/"+Ant.m_theme+"/icons/inviteIcon.png' border='0' />";
							td.innerHTML = "<img src='/images/icons/comments_double_16.png' border='0' />";
							td.m_uid = uid;
							td.onclick=function() { InitiateChat(this.m_uid); }
							row.appendChild(td);
							
							var td = ALib.m_document.createElement("td");
							td.innerHTML = "<a href='javascript:void(0);' onclick=\"InitiateChat('" + uid + "')\">" + uname + "</a>"
										   + " (<span style='font-size:9px;' id='widget_frinds_uid_status"  + uid + "'>" + status_text + "</span>)";
							row.appendChild(td);
							
							/*
							var td = ALib.m_document.createElement("td");
							td.style.width = "35px";
							var btn = new CButton("<img src='images/icons/chat6.png' border='0'>", "topNav.InitiateChat('" + uid + "')", null, "b2");
							td.appendChild(btn.getButton());
							row.appendChild(td);
							*/
						}
					}
				}
			}
		}
		catch(e) {}

		this.m_widcls.m_timer = window.setTimeout("Ant.getHinst('/home/widgets/friends').updateFriends()", 30000);
	};
	ajax.exec("/chat/xml_update_list.awp");
}

CWidFriends.prototype.changeMyName = function()
{
	name = prompt('Please enter your display name (128 chars) max', '');
				
	if (name && name!='null' && name!='undefined')
	{
		var funct = function(ret)
		{
			alert('Your display name has been changed to: ' + name);
		}
		var xmlrpc = new CAjaxRpc("/contacts/xml_friends_status.awp", "setname", 
								  [["myname", name]], funct);
	}
}

CWidFriends.prototype.setMyStatus = function(stat)
{
	if (!stat)
	{
		stat = prompt('Please enter your status (128 chars) max', '');
	}
	
	if (stat)
	{
		var xmlrpc = new CAjaxRpc("/contacts/xml_friends_status.awp", "setstatus", 
								  [["mystatus", stat]], function(ret){ALib.m_document.getElementById('friends_statusid').innerHTML = unescape(ret);});
	}
}

CWidFriends.prototype.getMyStatus = function()
{
	var xmlrpc = new CAjaxRpc("/contacts/xml_friends_status.awp", "getmystatus", 
							  null, function(ret){ALib.m_document.getElementById('friends_statusid').innerHTML = ret;});
}
