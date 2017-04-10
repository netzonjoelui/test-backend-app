function contactOpen(cid, cbonsave, assoc)
{
	var params = 'top=200,left=100,width=850,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? cbonsave : '';
	var url = "/contacts/edit_contact.awp?new_win=1";
	if (cid)
		url += "&cid="+cid;
	if (cbonsave)
		url += "&cbonsave="+cbonsave;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open(url, 'contact_'+cid, params);
}
