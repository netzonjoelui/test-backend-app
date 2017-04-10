var G_PROJ_NEWWIN_ID = 1;

function projTaskOpen(eid, onsave, assoc)
{
	var attribs = "top=200,left=100,width=620,height=550,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=no";
	var name = (tid) ? "task_"+tid : "task_new_"+G_PROJ_NEWWIN_ID;
	//var savefunc = (onsave) ? "&cbonsave="+escape(onsave) : "";
	var savefunc = (onsave) ? Base64.encode(unescape(onsave)) : '';
	var tid = (eid) ? eid : '';
	var other = ''; //(other_get) ? other_get : '';
	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			other += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open('/project/task_edit.awp?onexit='+escape("window.close();")+"&cbonsave="+savefunc+"&eid="+tid+other, name, attribs);

	G_PROJ_NEWWIN_ID++;
}

function projOpen(eid, onsave, assoc)
{
	var attribs = "width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes";
	var name = (tid) ? "proj_"+tid : "proj_new_"+G_PROJ_NEWWIN_ID;
	var savefunc = (onsave) ? "&cbonsave="+escape(onsave) : "";
	var tid = (eid) ? eid : '';
	var other = ''; //(other_get) ? other_get : '';
	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			other += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

    if (eid)
	    window.open('/project/project.awp?new_win=1&onexit='+escape("window.close();")+savefunc+"&pid="+tid+other, name, attribs);
    else
	    window.open('/project/project_add.awp?new_win=1&onexit='+escape("window.close();")+savefunc+"&pid="+tid+other, name, attribs);

	G_PROJ_NEWWIN_ID++;
}

function projTicketOpen(eid, onsave, assoc)
{
	/*
	var attribs = "top=200,left=100,width=620,height=550,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes";
	var name = (tid) ? "ticket_"+tid : "ticket_new_"+G_PROJ_NEWWIN_ID;
	var savefunc = (onsave) ? "&cbonsave="+escape(onsave) : "";
	var tid = (eid) ? eid : '';
	var other = ''; //(other_get) ? other_get : '';
	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			other += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open('/project/quality_edit.awp?new_win=1&onexit='+escape("window.close();")+savefunc+"&bugid="+tid+other, name, attribs);

	G_PROJ_NEWWIN_ID++;
	*/
	loadObjectForm("case", eid, null, null, assoc);
}
