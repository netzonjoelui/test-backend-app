/**
 * @fileoverview This class is a global object form plugin for managing membership
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Class constructor
 */
function AntObjectLoader_FormMem()
{
	this.data = new Object();

	this.name = "members";
	this.title = "Members";
	this.mainObject = null;
	this.newinvitations = false; // Flag set to true if new attendee has been added

	this.members = new Array(); // List of current attendees
	this.field = ""; // Field in mainObject that contains the list of members
	this.saveParentObject = false; // Flag used when saving. If set to true, then parent object will need to be saved when all done

	// Containers
	this.confirmedCon = null;
	this.confirmedTable = null;
	this.declinedCon = null;
	this.declinedTable = null;
	this.awaitingCon = null;
	this.awaitingTable = null;
}

/**
 * Required plugin main function
 */
AntObjectLoader_FormMem.prototype.main = function(con)
{
	this.buildInterface(con);
}

/**
 * Called from object loader when object is saved.
 *
 * This should take care of saving new members, sending invitations, and alerting existing members of changes.
 * Of course, each of the actions will usually require feedback from the end-user so the bindCallback function should 
 * be utilized by the calling class to continue processing once finished.
 */
AntObjectLoader_FormMem.prototype.save = function()
{
	for (var i = 0; i < this.members.length; i++)
	{
		this.members[i].processed = false;
	}
	
	// If no members just skip to onsave so plugin can close
	if (this.members.length < 1)
		this.onsave();
	else
		this.saveMembers();
}

/**
 * This is a recurrsive function used to process each member. When done onsave is called.
 */
AntObjectLoader_FormMem.prototype.saveMembers = function()
{
	var memtoProcess = null;

	// Loop through and look for unprocessed members
	for (var i = 0; i < this.members.length; i++)
	{
		if (!this.members[i].processed)
		{
			if ((this.members[i].obj.id && this.members[i].obj.dirty) || !this.members[i].obj.id)
			{
				memtoProcess = this.members[i];
				break;
			}
		}
	}

	if (memtoProcess)
	{
		memtoProcess.obj.cbProps.memCls = this;
		memtoProcess.obj.cbProps.memObj = memtoProcess;
		memtoProcess.wasnew = (memtoProcess.obj.id) ? false : true;

		if (memtoProcess.action == "delete")
		{
			memtoProcess.obj.onremove = function()
			{
				this.cbProps.memObj.processed = true; // set processed flag

				// Remove the member record referene into the parent object
				this.cbProps.memCls.mainObject.delMultiValue(this.cbProps.memCls.field, this.id);
				this.cbProps.memCls.saveParentObject = true;

				// Continue processing through members
				this.cbProps.memCls.saveMembers();
			}

			memtoProcess.obj.remove();
		}
		else
		{
			memtoProcess.obj.setValue("obj_reference", this.mainObject.name+":"+this.mainObject.id);
			memtoProcess.obj.onsave = function()
			{
				this.cbProps.memObj.processed = true; // set processed flag

				if (this.cbProps.memObj.wasnew)
				{
					// Save the new member record reference into the parent object
					this.cbProps.memCls.mainObject.setMultiValue(this.cbProps.memCls.field, this.id);
					this.cbProps.memCls.saveParentObject = true;
				}
				
				// Continue processing through members
				this.cbProps.memCls.saveMembers();
			}
			memtoProcess.obj.onsaveError = function()
			{
				this.cbProps.memObj.processed = true; // set processed flag
				this.cbProps.memCls.saveMembers();
			}

			memtoProcess.obj.save();
		}
	}
	else // All done
	{
		// If new members were created, then they were added to the parent object 'field' and should be saved.

		if (this.saveParentObject)
		{
			// This is causing an infinate loop because the main form calls pulugs onload.
			// Settings repressOnSave to true bypassed the onsave for this call only
			this.mainObject.save({repressOnSave:true});
		}

		// Send notifications will be responsible for calling onsave
		this.checkSendNotifications();
	}
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_FormMem.prototype.onsave = function()
{
}

/**
 * As the user if notifactions should be sent
 * 
 * Currently only invitations are sent for calendar_events. In the future this could easily be
 * expaneded to a more generic interface, but for now calendar_events will get special treatment.
 */
AntObjectLoader_FormMem.prototype.checkSendNotifications = function()
{
	if (this.members.length>0)
	{
		var ttl = "Send Updates &amp; Invitations";
		var dlg = new CDialog(ttl);

		var dv = alib.dom.createElement("div");

		var dv_lbl = alib.dom.createElement("div", dv);
		dv_lbl.innerHTML = "Would you like to send updates and/or invitations?"
		alib.dom.styleSet(dv_lbl, "padding-bottom", "5px");
		var dv_btn = alib.dom.createElement("div", dv);
		var btn = new CButton("Yes, Send", function(dlg, cls) { dlg.hide(); cls.sendNotifications(); }, [dlg, this], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Yes, Only Send New Invites", function(dlg, cls) { dlg.hide(); cls.sendNotifications(true); }, [dlg, this], "b1");
		btn.print(dv_btn);
		var btn = new CButton("No, Don't Send", function(dlg, cls) { dlg.hide(); cls.onsave(); }, [dlg, this], "b1");
		btn.print(dv_btn);
		alib.dom.styleSet(alib.dom.createElement("div", dv), "clear", "both");

		dlg.customDialog(dv, 400);
	}
	else
	{
		this.onsave();
	}
}

/**
 * Send notifications to members when event is saved
 *
 * @param bool onbynew	true if only sending new invitations and no updates
 */
AntObjectLoader_FormMem.prototype.sendNotifications = function(onlynew)
{
	var xhr = new alib.net.Xhr();

	// Force return of data immediately
	alib.events.listen(xhr, "load", function(evt) { 
		var data = this.getResponse();
		console.log(data);
		evt.data.defCls.onsave();
	}, {defCls:this});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
		evt.data.defCls.onsave();
	}, {defCls:this});

	var ret = xhr.send("/controller/Object/sendInvitations", "POST", {
		obj_type: this.mainObject.obj_type, 
		oid: this.mainObject.id, 
		field: this.field,
		onlynew: (onlynew) ? 't' : 'f'
	});
}

/**
 * Print form
 *
 * @param DOMElement con	The container where the members form will reside
 */
AntObjectLoader_FormMem.prototype.buildInterface = function(con)
{
	var frmDiv = alib.dom.createElement("div", con);

	// Will float this right
	var btn_div = alib.dom.createElement("div", frmDiv);

	var inp_div = alib.dom.createElement("div", frmDiv);
	var inp_notify = alib.dom.createElement("input", inp_div);
	var t = new CTextBoxList(inp_notify, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, 
							 plugins: {autocomplete: { placeholder: false, minLength: 2, queryRemote: true, 
							 							remote: {url:"/users/json_autocomplete.php"}}}});
	var btn = new alib.ui.Button("Add", {
		className:"b1 grRight", 
		tooltip:"Add", 
		cls: this,
		t: t,
		onclick:function(evt) {
			this.cls.submitAddAttendee(this.t);
		}
	});
	btn.print(btn_div);

	// Set widths and floats
	alib.dom.styleSet(inp_div, "margin-right", (btn.getWidth()) + "px");
	alib.dom.styleSet(btn_div, "float", "right");

	// Confirmed 
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var hdr = alib.dom.createElement("h3", subcon);
	hdr.innerHTML = "Confirmed";
	this.confirmedCon = subcon;

	// Awaiting Reply
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var hdr = alib.dom.createElement("h3", subcon);
	hdr.innerHTML = "Awaiting Reply";
	this.awaitingCon = subcon;

	// Declined
	var subcon = alib.dom.createElement("div", con);
	subcon.style.display = "none";
	var hdr = alib.dom.createElement("h3", subcon);
	hdr.innerHTML = "Declined";
	this.declinedCon = subcon;


	// Load existing members
	if (this.field)
	{
		var vals = this.mainObject.getMultiValues(this.field);

		for (var i = 0; i < vals.length; i++)
		{
			if (!vals[i] || vals[i] == 0)
				continue;

			var member = new Object();
			member.id 			= vals[i];
			member.obj 			= new CAntObject("member", vals[i]);
			member.action 		= 'save';

			member.obj.cbProps.cls = this;
			member.obj.onload = function()
			{
				this.cbProps.cls.addAttendee(this.id, this.getValue("name"), this.getValue("role"), this.getValue("f_accepted"));
			}
			member.obj.load(vals[i]);

			this.members[this.members.length] = member;
		}
	}
}

/**
 * Print form
 *
 * @param CTextBoxList textBoxList The list of items to add
 */
AntObjectLoader_FormMem.prototype.submitAddAttendee = function(textBoxList)
{
	var values = textBoxList.getValues();
	for (var i = 0; i < values.length; i++)
	{
		if (values[i][0] || values[i][1])
		{
			var att_obj = (values[i][0]) ? values[i][0] : values[i][1];

			var member = new Object();
			member.id 			= "new"+this.members.length;
			member.obj 			= new CAntObject("member");
			member.action 		= 'save';

			member.obj.setValue("name", (values[i][2]) ? values[i][2] : att_obj);

			// If there is an object reference for this member then save
			if (values[i][0])
			{
				var parts = values[i][0].split(":");
				if (parts.length > 1)
					member.obj.setValue("obj_member", values[i][0]);
			}

			this.members[this.members.length] = member;

			this.addAttendee(member.id, member.obj.getValue("name"), member.obj.getValue("role"), '');

			// Set send invitation flag
			this.newinvitations = true;
		}
	}
	textBoxList.clear();
}

/**
 * Add an attendee to the UI
 *
 * @param CTextBoxList textBoxList The list of items to add
 */
AntObjectLoader_FormMem.prototype.addAttendee = function(id, email_name, position, accepted)
{
	var f_newtbl = false;
	
	// Clean up position a bit
	if (position == null)
		position = "";

	switch (accepted)
	{
	case 't':
		if (!this.confirmedTable)
		{
			this.confirmedTable = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = this.confirmedTable;
		var con = this.confirmedCon;
		break;
	case 'f':
		if (!this.declinedTable)
		{
			this.declinedTable = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = this.declinedTable;
		var con = this.declinedCon;
		break;
	default:
		if (!this.awaitingTable)
		{
			this.awaitingTable = new CToolTable("100%");
			f_newtbl = true;
		}
		var tbl = this.awaitingTable;
		var con = this.awaitingCon;
		break;
	}

	if (f_newtbl)
	{
		tbl.addHeader("User/Email");
		tbl.addHeader("Role/Position");
		tbl.addHeader("Delete", "center", "50px");
		tbl.print(con);

		con.style.display = "block";
	}

	var inp = alib.dom.createElement("input");
	alib.dom.styleSet(inp, "width", "96%");
	inp.aid = id;
	inp.value = position;
	inp.cls = this;
	inp.onchange = function()
	{
		for (var i = 0; i < this.cls.members.length; i++)
		{
			if (this.cls.members[i].id == this.aid)
				this.cls.members[i].obj.setValue("role", this.value);
		}
		
	}

	var rw = tbl.addRow();
	rw.addCell(email_name, null, null, "30%");
	rw.addCell(inp);
	var del_dv = alib.dom.createElement("div");
	rw.addCell(del_dv, true, "center");
	del_dv.innerHTML = "<img border='0' src='/images/icons/delete_16.png' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	del_dv.m_rw = rw;
	del_dv.m_id = id;
	del_dv.cls = this;
	del_dv.onclick = function()
	{
		ALib.Dlg.confirmBox("Are you sure you want to remove this attendee?", "Remove Attendee", [this.m_rw, this.cls]);
		ALib.Dlg.onConfirmOk = function(row, cls)
		{
			row.deleteRow();

			// Remove attendee
			for (var i = 0; i < cls.members.length; i++)
			{
				if (cls.members[i].id == id)
				{
					if (!cls.members[i].obj.id)
						cls.members.splice(i, 1);
					else
						cls.members[i].action = "delete"; // Set deletion flag for save
				}
			}
		}
	}	
}
