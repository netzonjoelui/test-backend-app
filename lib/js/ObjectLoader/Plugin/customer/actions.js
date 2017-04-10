{
	name:"customer_actions",
	title:"Inline Actions for adding comments, tasks, events, and a phone call activity",
	mainObject:null,
	olCls:null, // object loader class reference

	/**
	 * Object loader form
	 *
	 * @var {AntObjectLoader_Form}
	 */
	formObj: null,

	/**
	 * Contianer to print the plugin into
	 *
	 * @var {DOMElement}
	 */
	com: null,

	/**
	 * Main function called when form is ready to load the plugin
	 */
	main:function(con)
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
	},

	/**
	 * Callback called when the main object is being saved
	 */
	save:function()
	{
		this.onsave();
	},

	/**
	 * Internal callback used to let the main form know we have finished loading
	 */
	onsave:function()
	{
	},

	/**
	 * Called after the main object has been saved
	 */
	objectsaved:function()
	{
		if (this.mainObject && this.mainObject.id)
			this.buildInterface();
	},

	/**
	 * Hook value changed for the main objects
	 */
	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
	},

	/**
	 * Internal funciton to buil the interface
	 *
	 * @private
	 */
	buildInterface:function()
	{
		this.con.innerHTML = "";

		// Print buttons
		// -----------------------------------------
		var buttonRow = alib.dom.createElement("div", this.con);
		alib.dom.styleSet(buttonRow, "margin", "5px 0px 7px 0px");

		/*
		var btn = alib.ui.Button("Add Comment", {
			className:"b1 grLeft medium", tooltip:"Add a Comment", cls:this,
			onclick:function() { }
		});
		btn.toggle(true); // Toggle on to look like a tab
		btn.print(buttonRow);
		*/

		var btn = alib.ui.Button("Add Task", {
			className:"b1 grLeft medium", tooltip:"Add a Task", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("task", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Schedule Event", {
			className:"b1 grCenter medium", tooltip:"Schedule a calendar event", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("calendar_event", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Create Reminder", {
			className:"b1 grCenter medium", tooltip:"Create a reminder for yourself", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("reminder", null, [["obj_reference", "customer:" + this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Log Phone Call", {
			className:"b1 grRight medium", tooltip:"Log a phone call", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("phone_call", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		// Add comment form
		// -----------------------------------------
		var commentCon = alib.dom.createElement("div", this.con);
		this.buildCommentForm(commentCon);


		//this.con.innerHTML = "Add Comment, Add Task, Add Event, Add Phone Call";
		// Loop through and refresh comments and activity types of this.formObj.objectBrowsers
		//

	},

	/**
	 * Add comment form
	 *
	 * @private
	 * @param {DOMElement} con The container where the form will printed
	 */
	buildCommentForm:function(con)
	{
		con.innerHTML = "";

		// Label
		alib.dom.createElement("h4", con, "Add Comment");

		// Image
		var imagecon = alib.dom.createElement("div", con);
		alib.dom.styleSet(imagecon, "float", "left");
		alib.dom.styleSet(imagecon, "width", "48px");
		imagecon.innerHTML = "<img src='/files/userimages/current/48/48' style='width:48px;' />";

		// Add input
		var inputDiv = alib.dom.createElement("div", con);
		alib.dom.styleSet(inputDiv, "margin-left", "51px");
		alib.dom.styleSet(inputDiv, "margin-bottom", "5px");
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
		var button = alib.ui.Button("Add Comment", {
			className:"b1 nomargin", tooltip:"Click to add your comment", cls:this, textarea:ta_comment, notify:t,
			onclick:function() { this.cls.saveComment(this.textarea, this.notify); }
		});
		var btnsp = alib.dom.createElement("div", con); // use for dynamic width
		alib.dom.styleSet(btnsp, "text-align", "right");
		button.print(btnsp);
	},

	/**
	 * Save the comment
	 *
	 * @private
	 * @param {textarea}
	 */
	saveComment:function(textarea, t_notify)
	{
		// Do nothing if the comment box is empty
		if (textarea.value.length == 0)
			return;

		var obj = new CAntObject("comment");
		obj.setValue("comment", textarea.value);
		obj.setValue("obj_reference", "customer:" + this.mainObject.id);
		obj.setMultiValue("associations", "customer:" + this.mainObject.id);

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
		obj.cbData.textarea = textarea;
		obj.cbData.plCls = this;
		obj.onsave = function() 
		{ 
			this.cbData.textarea.value = ""; // clearn input
			this.cbData.plCls.refreshBrowsers();
		}
		obj.save();
	},

	/**
	 * Refresh all comment and activity browsers to show new comment
	 *
	 * @private
	 */
	refreshBrowsers:function()
	{
		// Only run if we are in the context of an object form (which we should always be)
		if (!this.formObj)
			return; 

		// Loop through all form browsers and refresh comments and activity types
		for (var i in this.formObj.objectBrowsers)
		{
			var objb = this.formObj.objectBrowsers[i];
			if (objb.obj_type == "activity" || objb.obj_type == "comment")
				objb.refresh();
		}
	},

	/**
	 * Find users that should be in the notify
	 *
	 * @param {CTextBoxList} textList
	 */
	findNotifyCandidates:function(textList)
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
}
