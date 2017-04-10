var g_email_cmpid=1;

function emailComposeOpen(cbonsave, assoc)
{
	var params = 'width=780,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? Base64.encode(cbonsave) : '';
	g_email_cmpid++;
	//var url = '/email/compose.awp?new_win=1&cbonsave='+onsave;
    var url = '/obj/email_message';

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += (i == 0) ? "?" : "&";
			url += assoc[i][0] + "=" + escape(assoc[i][1]);
		}
	}

	var cmp = window.open(url, null, params);
}

/**
 * @depricated No longer referenced anywhere
function emailInvCalAccept(share_id, conid)
{
	if (typeof conid == "string")
		conid = document.getElementById(conid);
    
    ajax = new CAjax('json');
    ajax.conid = conid;
    ajax.onload = function(ret)
    {
        this.conid.innerHTML = "Invitation Accepted. Open the calendar application to view this calendar.";
    };
    ajax.exec("/controller/Email/acceptCalShare", 
                [["share_id", share_id]]);
}
 */

/**
 * @depricated No longer referenced anywhere
function emailInvConGrpAccept(share_id, conid)
{
	if (typeof conid == "string")
		conid = document.getElementById(conid);
    
    ajax = new CAjax('json');
    ajax.conid = conid;
    ajax.onload = function(ret)
    {
        this.conid.innerHTML = "Invitation Accepted. Open the contacts application to view this group.";   
    };
    ajax.exec("/controller/Email/acceptCongrpShare", 
                [["share_id", share_id]]);
}
 */

/**
 * @depricated Use emailAssocObj
function emailAssocCustomer(mid)
{
	var cbrowser = new CCustomerBrowser();
	cbrowser.onSelect = function(cid, name) 
	{
        ajax = new CAjax('json');        
        ajax.name = name;
        ajax.onload = function(ret)
        {
            ALib.statusShowAlert("Email Activity Created For " + this.name, 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/assocWithCust", 
                    [["mid", mid], ["custid", cid]]);
	}
	cbrowser.showDialog();
}
 */ 

/**
 * Used by the email loader to associate any object with the message
 *
 * TODO: we may want to make this more generic in the CAntObject class
 *
 * @param {int} mid The object id
 * @param {string} obj_type The object type name to associate with
 */
function emailAssocObj(mid, obj_type)
{
	var cbrowser = new AntObjectBrowser(obj_type);
	cbrowser.cbData.emailId = mid;
	cbrowser.cbData.assocWithOtype = obj_type;
	cbrowser.onSelect = function(oid) 
	{
        ajax = new CAjax('json');        
        ajax.onload = function(ret)
        {
            ALib.statusShowAlert("Email Activity Created ", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/assocWithObj", 
                    [["mid", this.cbData.emailId], ["object_id", oid], ["obj_type", this.cbData.assocWithOtype]]);
	}
	cbrowser.displaySelect();
}

function emailListFormatTimeDel(timeEnteredStr)
{
	var parts = timeEnteredStr.split("-");
	if (parts.length>1)
		timeEnteredStr = parts[0];

	var parts = timeEnteredStr.split(" ");
	if (parts.length>1)
		timeEnteredStr = parts[0];

	try
	{
		/*
		var time = new Date(timeEnteredStr);
		var today = new Date();
		timeEnteredStr = "";
		timeEnteredStr = time.getMonth()+1;
		timeEnteredStr += "/"+time.getDate();
		timeEnteredStr += "/"+time.getFullYear();

		if (today.getMonth() == time.getMonth() && today.getDay() == time.getDate() && today.getFullYear() == time.getFullYear())
		{
			// If today then display time
		}
		else
		{
			// Display date
		}
		*/

	}
	catch (e)
	{
		//ALib.m_debug = true;
		//ALib.trace(e);
	}

	return timeEnteredStr;
}
