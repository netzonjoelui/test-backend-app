var g_attAwaiting = null;
var g_attDeclined = null;
var g_attConfirmed = null;

var g_confirmedcon = null;
var g_awaitingcon = null;
var g_declinedcon = null;

var g_add_att_email = null;
var g_add_att_position = null;

function tabAttendees(con)
{
	// Invite
	var frm1 = new CWindowFrame("Invite Attendee(s) - enter name or email address", null, "3px");
	var frmcon = frm1.getCon();
	frm1.print(con);

	var row = alib.dom.createElement("div", frmcon);
	var inp_notify = alib.dom.createElement("input", row);
	var t = new CTextBoxList(inp_notify, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: false, minLength: 2, queryRemote: true, remote: {url:"/users/json_autocomplete.php"}}}});

	var row = alib.dom.createElement("div", frmcon);
	var btn = new CButton("Add Addendee(s)", submitAddAttendee, [t], "b1");
	btn.print(row);


	// Confirmed 
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var frm1 = new CWindowFrame("Confirmed Attendees", null, "0px");
	g_confirmedcon = frm1.getCon();
	g_confirmedcon.m_con = subcon;
	frm1.print(subcon);

	// Awaiting Reply
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var frm1 = new CWindowFrame("Awaiting Reply", null, "0px");
	g_awaitingcon = frm1.getCon();
	g_awaitingcon.m_con = subcon;
	frm1.print(subcon);

	// Declined
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var frm1 = new CWindowFrame("Declined", null, "0px");
	g_declinedcon = frm1.getCon();
	g_declinedcon.m_con = subcon;
	frm1.print(subcon);

	// Now lost existing attendees
	for (var i = 0; i < g_event.attendees.length; i++)
	{
		addAttendee(g_event.attendees[i].id, g_event.attendees[i].email_name, g_event.attendees[i].position, g_event.attendees[i].accepted);
	}
}

function addAttendee(id, email_name, position, accepted)
{
	var f_newtbl = false;
	
	// Clean up position a bit
	if (position == null)
		position = "";

	switch (accepted)
	{
	case 't':
		if (!g_attConfirmed)
		{
			g_attConfirmed = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = g_attConfirmed;
		var con = g_confirmedcon;
		break;
	case 'f':
		if (!g_attDeclined)
		{
			g_attDeclined = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = g_attDeclined;
		var con = g_declinedcon;
		break;
	default:
		if (!g_attAwaiting)
		{
			g_attAwaiting = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = g_attAwaiting;
		var con = g_awaitingcon;
		break;
	}

	if (f_newtbl)
	{
		tbl.addHeader("User/Email");
		tbl.addHeader("Role/Position");
		tbl.addHeader("Delete", "center", "50px");
		tbl.print(con);

		con.m_con.style.display = "block";
	}

	var inp = alib.dom.createElement("input");
	alib.dom.styleSet(inp, "width", "96%");
	inp.aid = id;
	inp.value = position;
	inp.onchange = function()
	{
		for (var i = 0; i < g_event.attendees.length; i++)
		{
			if (g_event.attendees[i].id == this.aid)
				g_event.attendees[i].position = this.value;
		}
		
	}

	var rw = tbl.addRow();
	rw.addCell(email_name, null, null, "30%");
	//rw.addCell((position)?position:'Attendee');
	rw.addCell(inp);
	var del_dv = alib.dom.createElement("div");
	rw.addCell(del_dv, true, "center");
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + ((typeof(Ant)=='undefined')?g_theme:Ant.m_theme) + "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	del_dv.m_rw = rw;
	del_dv.m_id = id;
	del_dv.onclick = function()
	{
		ALib.Dlg.confirmBox("Are you sure you want to remove this attendee?", "Remove Attendee", [this.m_rw]);
		ALib.Dlg.onConfirmOk = function(row)
		{
			row.deleteRow();

			// Remove attendee
			for (var i = 0; i < g_event.attendees.length; i++)
			{
				if (g_event.attendees[i].id == id)
				{
					if (g_event.attendees[i].action == "new")
						g_event.attendees.splice(i, 1);
					else
						g_event.attendees[i].action = "deleted"; // Set deletion flag for save
				}
			}
		}
	}	
}

/*
function submitAddAttendee()
{
	if (g_add_att_email.value)
	{
		//ALib.Dlg.messageBox(this.m_email.value);

		var attendee = new Object();
		attendee.id 		= "new"+g_event.attendees.length;
		attendee.email_name	= g_add_att_email.value;
		attendee.position 	= g_add_att_position.value;
		attendee.message 	= "";
		attendee.action = 'new';
			
		g_event.attendees[g_event.attendees.length] = attendee;

		addAttendee(attendee.id, attendee.email_name, attendee.position, '');

		// Clear form
		g_add_att_email.value		= "";
		g_add_att_position.value	= "";

		// Set send invitation flag
		g_newinvitations = true;

	}
	else
	{
		alert("Please enter an email address");
	}

	g_add_att_email.focus();
}
*/

function submitAddAttendee(textBoxList)
{
	var values = textBoxList.getValues();
	for (var i = 0; i < values.length; i++)
	{
		if (values[i][0] || values[i][1])
		{
			var att_obj = (values[i][0]) ? values[i][0] : values[i][1];

			var attendee = new Object();
			attendee.id 			= "new"+g_event.attendees.length;
			attendee.email_name		= (values[i][2]) ? values[i][2] : att_obj;
			attendee.attendee_obj	= att_obj;
			attendee.position 		= ""; //g_add_att_position.value;
			attendee.message 		= "";
			attendee.action 		= 'new';
				
			g_event.attendees[g_event.attendees.length] = attendee;

			addAttendee(attendee.id, attendee.email_name, attendee.position, '');

			// Set send invitation flag
			g_newinvitations = true;
		}
	}
	textBoxList.clear();
	//g_add_att_email.focus();
}
