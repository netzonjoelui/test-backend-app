/**
 * @fileoverview This is a global plugin to create a new status_update object in relation to the current object
 *
 * @author     joe, sky.stebnicki@aereus.com
 *             Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Class constructor
 */
function AntObjectLoader_StatusUpdate()
{
    this.data = new Object();

    this.name = "status_update";  // should be the same, when calling the plugin
    this.title = "Status Update";
    this.mainObject = null;    
    this.formObj = null;
    
    // Containers
    this.con = null;
}

/**
 * Required plugin main function
 */
AntObjectLoader_StatusUpdate.prototype.main = function(con)
{
    this.con = con;    
    
	if (this.mainObject.id)
	{
		this.buildInterface();
	}
	else
	{
		con.innerHTML = "";
	}
}

/**
 * Called after the main object has been saved
 */
AntObjectLoader_StatusUpdate.prototype.objectsaved = function()
{
	if (this.mainObject.id)
		this.buildInterface();
}

/**
 * Print form 
 */
AntObjectLoader_StatusUpdate.prototype.buildInterface = function()
{
    this.con.innerHTML = "";
	
	// Add title
	// -----------------------------------------
	var titleCon = alib.dom.createElement("h4", this.con);
	titleCon.innerHTML = "Add status update for this " + this.mainObject.title.toLowerCase() + ":";

	// Add comment form
	// -----------------------------------------
	var commentCon = alib.dom.createElement("div", this.con);
	this.buildCommentForm(commentCon);


	//this.con.innerHTML = "Add Comment, Add Task, Add Event, Add Phone Call";
	// Loop through and refresh comments and activity types of this.formObj.objectBrowsers
	//
}

/**
 * Called from object loader when object is saved.
 *
 * This should take care of saving attached file
 */
AntObjectLoader_StatusUpdate.prototype.save = function()
{
    this.onsave();
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_StatusUpdate.prototype.onsave = function()
{
}

/**
 * Add comment form
 *
 * @private
 * @param {DOMElement} con The container where the form will printed
 */
AntObjectLoader_StatusUpdate.prototype.buildCommentForm = function(con)
{
	con.innerHTML = "";

	// Image
	var imagecon = alib.dom.createElement("div", con);
	alib.dom.styleSet(imagecon, "float", "left");
	alib.dom.styleSet(imagecon, "width", "48px");
	imagecon.innerHTML = "<img src='/files/userimages/current/48/48' style='width:48px;' />";

	// Add input
	var inputDiv = alib.dom.createElement("div", con);
	alib.dom.styleSet(inputDiv, "margin-bottom", "5px");
		alib.dom.styleSet(inputDiv, "margin-left", "51px");
	var ta_comment = alib.dom.createElement("textarea", inputDiv);
	alib.dom.styleSet(ta_comment, "width", "100%");
	alib.dom.styleSet(ta_comment, "height", "25px");
	alib.dom.textAreaAutoResizeHeight(ta_comment, 48);

	// Clear floats
	var clear = alib.dom.createElement("div", con);
	alib.dom.styleSet(clear, "clear", "both");

	// Notification
	var lbl = alib.dom.createElement("div", con);
	alib.dom.styleSet(lbl, "float", "left");
	alib.dom.styleSet(lbl, "width", "48px");
	alib.dom.styleSet(lbl, "padding-top", "5px");
	lbl.innerHTML = "Notify:";
	var inpdv = alib.dom.createElement("div", con);
	alib.dom.styleSet(inpdv, "margin-left", "51px");
	alib.dom.styleSet(inpdv, "margin-bottom", "5px");
	var inp_notify = alib.dom.createElement("input", inpdv);
	var t = new CTextBoxList(inp_notify, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: false, minLength: 2, queryRemote: true, remote: {url:"/users/json_autocomplete.php"}}}});
	this.findNotifyCandidates(t);

	// Add submit
	var button = alib.ui.Button("Update Status", {
		className:"b1 nomargin", tooltip:"Click to save and send your status update", cls:this, textarea:ta_comment, notify:t,
		onclick:function() { 
			alib.dom.styleAddClass(this, "working");
			this.cls.saveStatusUpdate(this.textarea, this.notify, this); 
		}
	});
	var btnsp = alib.dom.createElement("div", con); // use for dynamic width
	alib.dom.styleSet(btnsp, "text-align", "right");
	button.print(btnsp);
},

/**
 * Save the update
 *
 * @private
 * @param {textarea}
 * @param {TextInputList}
 * @param {alib.ui.Button} btn Handle to button to clear working class when finished
 */
AntObjectLoader_StatusUpdate.prototype.saveStatusUpdate = function(textarea, t_notify, btn)
{
	// Do nothing if the comment box is empty
	if (textarea.value.length == 0)
		return;

	var obj = new CAntObject("status_update");
	obj.setValue("comment", textarea.value);
	obj.setValue("obj_reference", this.mainObject.obj_type + ":" + this.mainObject.id);
	obj.setMultiValue("associations", this.mainObject.obj_type + ":" + this.mainObject.id);

	var notify = "";
	var values = t_notify.getValues();
	for (var i = 0; i < values.length; i++)
	{
		if (notify) notify += ",";
		if (values[i][0])
			 notify += values[i][0];
		else if (values[i][1]) // email, no object
			 notify += values[i][1];
	}
	if (notify)
		obj.setValue("notify", notify);

	obj.setValue("owner_id", "-3");
	//obj.t_notify = t_notify;
	obj.cbData.textarea = textarea;
	obj.cbData.t_notify = t_notify;
	obj.cbData.plCls = this;
	obj.cbData.btn = btn;
	obj.onsave = function() 
	{ 
		this.cbData.textarea.value = ""; // clearn input
		this.cbData.t_notify.clear(); // clearn input
		this.cbData.plCls.refreshBrowsers();

		alib.dom.styleRemoveClass(this.cbData.btn, "working");
	}
	obj.save();
},

/**
 * Refresh all  activity browsers to show new status update
 *
 * @private
 */
AntObjectLoader_StatusUpdate.prototype.refreshBrowsers = function()
{
	// Only run if we are in the context of an object form (which we should always be)
	if (!this.formObj)
		return; 

	// Loop through all form browsers and refresh comments and activity types
	for (var i in this.formObj.objectBrowsers)
	{
		var objb = this.formObj.objectBrowsers[i];
		if (objb.obj_type == "activity")
			objb.refresh();
	}
},

/**
 * Find users that should be in the notify
 *
 * @param {CTextBoxList} textList
 */
AntObjectLoader_StatusUpdate.prototype.findNotifyCandidates = function(textList)
{
	// for now we do nothing, this is for possible future expansion
	/*
	if (this.mainObject)
	{
		var fields = this.mainObject.getFields();
		for (var j = 0; j < fields.length; j++)
		{
			var field = fields[j];
			var field_val = "";
			var field_lbl = "";
			var otype = "";

			if (field.type == "object" && (field.subtype == "user" || field.subtype == "customer"))
			{
				field_val = this.mainObject.getValue(field.name);
				field_lbl = this.mainObject.getValueName(field.name);
				otype = field.subtype;
			}

			if (field_val)
			{
				var bFound = false;
				for (var i = 0; i < this.comment_users.length; i++)
				{
					if (this.comment_users[i].id == otype+":"+field_val)
						bFound = true;
				}

				if (!bFound)
					this.comment_users[this.comment_users.length] = {id:otype+":"+field_val, name:field_lbl};
			}
		}
	}

	// Loop through added users/customers to be notified
	if (this.comment_users)
	{
		for (var i = 0; i < this.comment_users.length; i++)
		{
			if ((g_userid && this.comment_users[i].id != "user:"+g_userid) || !g_userid)
				t.add(this.comment_users[i].id, this.comment_users[i].name);
		}
	}
	*/
}
