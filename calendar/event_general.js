function tabGeneral(con)
{
	// Reminders
	var frm1 = new CWindowFrame("Reminders", null, "3px");
	var frmcon = frm1.getCon();
	frm1.print(con);
	tabGeneralReminders(frmcon);

	// Details
	var frm1 = new CWindowFrame("Details", null, "3px");
	var frmcon = frm1.getCon();
	frm1.print(con);

	var table = alib.dom.createElement("table", frmcon);
	var tbody = alib.dom.createElement("tbody", table);

	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	td.innerHTML = "Calendar:";
	var td = alib.dom.createElement("td", row);
	var sel = alib.dom.createElement("select", td);
	g_event.cb_calendar = sel;
	for (var i = 0; i < g_calendars.length; i++)
	{
		if (g_eid)
		{
			
			var selected = (g_event.calendar_id == g_calendars[i][0])?true:false;
		}
		else
		{
			var selected = (g_defcal == g_calendars[i][0])?true:false;
		}

		var cal_lbl = (g_calendars[i][2]) ? g_calendars[i][1]+" - "+g_calendars[i][2] : g_calendars[i][1];

		sel[sel.length] = new Option(cal_lbl, g_calendars[i][0], false, selected);
	}
	sel.onchange = function()
	{
		g_event.calendar_id = this.value;
	}

	var td = alib.dom.createElement("td", row);
	td.innerHTML = "This event is:";
	var td = alib.dom.createElement("td", row);
	var sel = alib.dom.createElement("select", td);
	g_event.cb_sharing = sel;
	sel[sel.length] = new Option("Private", "1", false, (g_event.sharing == "1")?true:false);
	sel[sel.length] = new Option("Public", "2", false, (g_event.sharing == "2")?true:false);
	sel.onchange = function()
	{
		g_event.sharing = this.value;
	}

	var td = alib.dom.createElement("td", row);
	td.innerHTML = "Show me as:";
	var td = alib.dom.createElement("td", row);
	var sel = alib.dom.createElement("select", td);
	g_event.cb_userStatus = sel;
	sel[sel.length] = new Option("Available", "1", false, (g_event.userStatus == "1")?true:false);
	//sel[sel.length] = new Option("Pending", "2", (g_event.userStatus == "2")?true:false);
	sel[sel.length] = new Option("Busy", "3", false, (g_event.userStatus == "3")?true:false);
	sel.onchange = function()
	{
		g_event.userStatus = this.value;
	}

	// Details
	var frm1 = new CWindowFrame("Description", null, "0px");
	var frmcon = frm1.getCon();
	frm1.print(con);
	var dv = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(dv, "margin-right", "4px");
	if (g_event.notes)
	{
		var re = new RegExp ("\n", 'gi') ;
		dv.innerHTML = "<div style='padding:3px;'>" + g_event.notes.replace(re, "<br />") + "</div>";
	}
	else
		dv.innerHTML = "<div style='height:100px;padding:3px;'></div>";
	dv.onclick = function()
	{
		this.innerHTML = "";
		var ta = alib.dom.createElement("textarea", this);
		ta.dv = this;
		var re = new RegExp ("<br />", 'gi') ;
		ta.value = g_event.notes.replace(re, "\n");
		alib.dom.styleSet(ta, "width", "100%");
		alib.dom.styleSet(ta, "height", "100px");
		alib.dom.styleSet(ta, "border", "0px");
		alib.dom.styleSet(ta, "display", "block");
		alib.dom.textAreaAutoResizeHeight(ta, 100);
		ta.focus();
		ta.onblur = function () 
		{ 
			g_event.notes = this.value; 
			this.dv.onclick = this.oldonclick; 
			if (this.value)
			{
				var re = new RegExp ("\n", 'gi') ;
				this.dv.innerHTML = "<div style='padding:3px;'>" + g_event.notes.replace(re, "<br />") + "</div>";
			}
			else
				this.dv.innerHTML = "<div style='height:100px;'></div>";
		}

		ta.oldonclick = this.onclick;
		this.onclick = function () {}
	}
}

function tabGeneralReminders(con)
{
	var frm_div = alib.dom.createElement("div", con);
	g_recurcon = frm_div;

	var sel = alib.dom.createElement("select", frm_div);
	var frm_con = alib.dom.createElement("span", frm_div);
	sel.m_frmcon = frm_con;
	sel[sel.length] = new Option("Add Reminder", "", false);
	sel[sel.length] = new Option("Send Email", "1", false);
	sel[sel.length] = new Option("Send Text Message (SMS)", "2", false);
	sel[sel.length] = new Option("Pop-up Alert", "3", false);
	sel.onchange = function()
	{
		// Display the right fields
		switch (this.value)
		{
		case "3":
			this.m_frmcon.innerHTML = "";

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " remind me ";

			var inp_time = alib.dom.createElement("input");
			inp_time.style.width = "20px";
			inp_time.value = 15;
			this.m_frmcon.appendChild(inp_time);
			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = "&nbsp;";

			var sel = alib.dom.createElement("select", this.m_frmcon);
			sel[sel.length] = new Option("minute(s)", "1", false);
			sel[sel.length] = new Option("hour(s)", "2", false);
			sel[sel.length] = new Option("day(s)", "3", false);
			sel[sel.length] = new Option("week(s)", "4", false);

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " before event starts ";


			var btn = alib.dom.createElement("input");
			btn.type = 'button';
			btn.value = "Add";
			btn.m_frmcon = this.m_frmcon;
			btn.m_cb = this;
			btn.m_time = inp_time;
			btn.m_interval = sel;
			btn.onclick = function()
			{
				if (this.m_time.value)
				{
					addReminder("new"+g_event.reminders.length, "3", this.m_time.value, this.m_interval.value, g_username);
					this.m_frmcon.innerHTML = "";
					this.m_cb.options[0].selected = true;

					var reminder = new Object();
					reminder.id 		= "new"+g_event.reminders.length;
					reminder.type 		= 3;
					reminder.send_to 	= g_username;
					reminder.count 		= this.m_time.value;
					reminder.interval	= this.m_interval.value;
						
					g_event.reminders[g_event.reminders.length] = reminder;
				}
				else
					alert("Please enter a time");
			}
			this.m_frmcon.appendChild(btn);
			break;
		case "2":
			this.m_frmcon.innerHTML = "";

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " to ";
			var inp_number = alib.dom.createElement("input");
			inp_number.style.width = "80px";
			inp_number.value = g_userMobilePhone;
			this.m_frmcon.appendChild(inp_number);
			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " @ ";

			var sel_carrier = alib.dom.createElement("select", this.m_frmcon);
			for (var c = 0; c < g_smscarriers.length; c++)
			{
				sel_carrier[sel_carrier.length] = new Option(g_smscarriers[c][0], g_smscarriers[c][1], false, (g_userMobilePhoneCarrier==g_smscarriers[c][1])?true:false);
			}
			/*
			sel_carrier[sel_carrier.length] = new Option("Sprint PCS", "@messaging.sprintpcs.com", false);
			sel_carrier[sel_carrier.length] = new Option("AT&T Wireless", "@mobile.att.net", false);
			sel_carrier[sel_carrier.length] = new Option("Nextel Wireless", "@messaging.nextel.com", false);
			sel_carrier[sel_carrier.length] = new Option("T Mobile", "@voicestream.net", false);
			sel_carrier[sel_carrier.length] = new Option("Cingular Wireless", "@mobile.mycingular.com", false);
			sel_carrier[sel_carrier.length] = new Option("SureWest", "@mobile.surewest.com", false);
			sel_carrier[sel_carrier.length] = new Option("Metro PCS", "@mymetropcs.com", false);
			*/

			var lbl = alib.dom.createElement("br", this.m_frmcon);

			var inp_time = alib.dom.createElement("input", this.m_frmcon);
			inp_time.style.width = "20px";
			inp_time.value = 15;
			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = "&nbsp;";

			var sel = alib.dom.createElement("select", this.m_frmcon);
			sel[sel.length] = new Option("minute(s)", "1", false);
			sel[sel.length] = new Option("hour(s)", "2", false);
			sel[sel.length] = new Option("day(s)", "3", false);
			sel[sel.length] = new Option("week(s)", "4", false);

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " before event starts ";


			var btn = alib.dom.createElement("input");
			btn.type = 'button';
			btn.value = "Add";
			btn.m_frmcon = this.m_frmcon;
			btn.m_cb = this;
			btn.m_time = inp_time;
			btn.m_interval = sel;
			btn.m_number = inp_number;
			btn.m_carrier = sel_carrier;
			btn.onclick = function()
			{
				if (this.m_number.value)
				{
					addReminder("new"+g_event.reminders.length, "2", this.m_time.value, this.m_interval.value, this.m_number.value + this.m_carrier.value);
					this.m_frmcon.innerHTML = "";
					this.m_cb.options[0].selected = true;

					var reminder = new Object();
					reminder.id 		= "new"+g_event.reminders.length;
					reminder.type 		= 2;
					reminder.send_to 	= this.m_number.value + this.m_carrier.value;
					reminder.count 		= this.m_time.value;
					reminder.interval	= this.m_interval.value;
						
					g_event.reminders[g_event.reminders.length] = reminder;
				}
				else
					alert("Please enter a phone number");
			}
			this.m_frmcon.appendChild(btn);
			break;
		case "1":
			this.m_frmcon.innerHTML = "";

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " to ";
			var inp_email = alib.dom.createElement("input", this.m_frmcon);
			inp_email.style.width = "170px";
			inp_email.value = g_user_email;
			var lbl = alib.dom.createElement("br", this.m_frmcon);
			var inp_time = alib.dom.createElement("input", this.m_frmcon);
			inp_time.style.width = "20px";
			inp_time.value = 15;
			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = "&nbsp;";

			var sel = alib.dom.createElement("select", this.m_frmcon);
			sel[sel.length] = new Option("minute(s)", "1", false);
			sel[sel.length] = new Option("hour(s)", "2", false);
			sel[sel.length] = new Option("day(s)", "3", false);
			sel[sel.length] = new Option("week(s)", "4", false);

			var lbl = alib.dom.createElement("span", this.m_frmcon);
			lbl.innerHTML = " before event starts ";


			var btn = alib.dom.createElement("input");
			btn.type = 'button';
			btn.value = "Add";
			btn.m_frmcon = this.m_frmcon;
			btn.m_cb = this;
			btn.m_time = inp_time;
			btn.m_interval = sel;
			btn.m_email = inp_email;
			btn.onclick = function()
			{
				if (this.m_email.value)
				{
					addReminder("new"+g_event.reminders.length, "1", this.m_time.value, this.m_interval.value, this.m_email.value);
					this.m_frmcon.innerHTML = "";
					this.m_cb.options[0].selected = true;

					var reminder = new Object();
					reminder.id 		= "new"+g_event.reminders.length;
					reminder.type 		= 1;
					reminder.send_to 	= this.m_email.value;
					reminder.count 		= this.m_time.value;
					reminder.interval	= this.m_interval.value;
						
					g_event.reminders[g_event.reminders.length] = reminder;
				}
				else
					alert("Please enter an email address");
			}
			this.m_frmcon.appendChild(btn);

			break;
		default:
			this.m_frmcon.innerHTML = "";
			break;
		}
	}

	for (var i = 0; i < g_event.reminders.length; i++)
	{
		var reminder = g_event.reminders[i];
		addReminder(reminder.id, reminder.type, reminder.count, reminder.interval, reminder.send_to);
	}
}

function addReminder(id, type, time, interval, send_to)
{
	if (!g_recurtbl)
	{
		g_recurtbl = new CToolTable("100%");
		g_recurtbl.addHeader("Type");
		g_recurtbl.addHeader("To");
		g_recurtbl.addHeader("Time");
		g_recurtbl.addHeader("Delete", "center", "50px");
		g_recurtbl.print(g_recurcon);
	}

	var rw = g_recurtbl.addRow();
	switch (type)
	{
	case "1":
		rw.addCell("Send Email");
		break;
	case "2":
		rw.addCell("Send SMS Text Message");
		break;
	case "3":
		rw.addCell("Pop-up Alert");
		break;
	}
	rw.addCell((typeof send_to != "undefined") ? send_to : "");

	var lbl = time + " ";
	switch (interval)
	{
	case "1":
		lbl += "minutes";
		break;
	case "2":
		lbl += "hours";
		break;
	case "3":
		lbl += "days";
		break;
	case "4":
		lbl += "weeks";
		break;
	}
	lbl += " before event starts";
	rw.addCell(lbl);

	var del_dv = alib.dom.createElement("div");
	rw.addCell(del_dv, true, "center");
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + ((typeof(Ant)=='undefined')?g_theme:Ant.m_theme) + "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	del_dv.m_rw = rw;
	del_dv.m_id = id;
	del_dv.onclick = function()
	{
		ALib.Dlg.confirmBox("Are you sure you want to remove this reminder?", "Remove Reminder", [this.m_rw]);
		ALib.Dlg.onConfirmOk = function(row)
		{
			row.deleteRow();

			// Remove group from document
			for (var i = 0; i < g_event.reminders.length; i++)
			{
				if (g_event.reminders[i].id == id)
					g_event.reminders.splice(i, 1);
			}
		}
	}	
}
