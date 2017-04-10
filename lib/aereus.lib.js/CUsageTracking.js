/****************************************************************************
*	
*	Class:		CUsageTracking
*
*	Purpose:	Editable spreadsheet table
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*	Usage:		Include the following anywhere on the page (make sure all ALIB
*				or CUsageTracking.js is included)
*				<script language="javascript" type="text/javascript">
*					var dbid = 22; // This is the id of your usage database
*					CUsageTracking("testserv.aereus.com", dbid);
*				</script>
*				
*
*****************************************************************************/
function CUsageTracking(server, dbid, page)
{
	var send_vars = new Array();
	var path = "http://" + server + "/datacenter/svr_logwebusage.awp";
	try
	{
		var doc = (typeof ALib != "undefined") ? ALib.m_document : document;
	}
	catch (e)
	{
		var doc = document;
	}
		
	// Get page name
	if (!page)
	{
		var page = doc.location.href;
		var i = page.indexOf("://");
		if (i)
		{
			page=page.substring(i+3, page.length);
			i=page.indexOf("/");
			if (i)
				page=page.substring(i+1, page.length);

			if ("index.htm" == page || "index.html" == page ||
				"index.php" == page || "index.awp" == page || "" == page)
			{
				page = "/";
			}

			send_vars[send_vars.length] = ["page", page];
		}
	}
	else
	{
		send_vars[send_vars.length] = ["page", page];
	}


	var referrer = doc.referrer;
	if (referrer)
	{
		send_vars[send_vars.length] = ["referrer", referrer];
	}

	// Set logvisit 1 = log a new visit (don't log for each page view)
	var ses = CUTReadCookie("ant_cut_ses"); // Get session cookie
	var ret = CUTReadCookie("ant_cut_ret"); // Get feturning cookie
	if (!ses)
	{
		send_vars[send_vars.length] = ["logvisit", '1'];

		// Type: 1 = new visit, 2 = returning
		var visit_type = (ret) ? 2 : 1;
		send_vars[send_vars.length] = ["visit_type", visit_type];

		// Create retunring and session cookie
		CUTCreateCookie("ant_cut_ses", "1", null);
		CUTCreateCookie("ant_cut_ret", "1", 90);
	}

	// Set database id
	send_vars[send_vars.length] = ["dbid", dbid];	

	path += "?";
	var tmpVars = "function=log";

	for (var i = 0; i < send_vars.length; i++)
	{
		tmpVars += "&" + send_vars[i][0] + "=" + escape(send_vars[i][1]);
	}

	var img = new Image(1, 1);
	img.src = path + tmpVars;
	img.onload = function() {};
}

function CUTCreateCookie(name,value,days) 
{
	if (days) 
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function CUTReadCookie(name) 
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) 
	{
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function CUTEraseCookie(name) 
{
	createCookie(name,"",-1);
}

