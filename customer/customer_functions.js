var CUST_TYPE_CONTACT = 1;
var CUST_TYPE_ACCOUNT = 2;

var g_custnewwin = 0;
function custOpen(cid, cbonsave, assoc)
{
	loadObjectForm("customer", cid, null, null, assoc);
	/*
	var params = 'top=200,left=100,width=850,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? cbonsave : '';
	var url = "/obj/customer";
	if (cid)
		url += "/"+cid;
	if (cbonsave)
		url += "&cbonsave="+cbonsave;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	var wndname = (cid) ? cid : g_custnewwin++;

	window.open(url, 'cust_'+wndname, params);
	*/
}

function custOppOpen(id, cid, assoc)
{
	loadObjectForm("opportunity", cid, null, null, assoc);
	/*
	var url_vars = "fval=0";
	if (id)
		url_vars += id;
	//if (cid)
		//url_vars += '&custid='+cid;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url_vars += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open('/obj/opportunity'+url_vars, 'opp_'+id, 'width=750,height=550,toolbar=no,scrollbars=yes');
	*/
}

function custInvOpen(id, assoc)
{
	var url_vars = "";
	if (id)
		url_vars += 'invid='+id;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url_vars += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open('/customer/invoice.php?'+url_vars, 'inv_'+id, 'width=750,height=550,toolbar=no,scrollbars=yes');
}

function custEmailOpen(mid)
{
	window.open('/email/message_view.awp?new_win=1&mid='+mid, 'msg_'+mid, 'width=750,height=550,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes');
}

function custLeadOpen(lid, cbonsave, assoc)
{
	var url = "/customer/lead_edit.php?new_win=1";
	if (lid)
		url += "&lid="+lid;
	if (cbonsave)
		url += "&cbonsave="+cbonsave;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open(url, 'lead_'+lid, 'width=648,height=600,toolbar=no');
}
