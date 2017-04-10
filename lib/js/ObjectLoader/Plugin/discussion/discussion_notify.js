{
	name:"discussion_notify",
	title:"Invite",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;

		this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		this.buildInterface();
	},

	/**
	 * Fired before save
	 */
	beforesaved:function()
	{
		var values = this.t_notify.getValues();
		var invite = "";
		for (var i = 0; i < values.length; i++)
		{
			if (invite != "") invite += ",";
			invite += values[i][0];
		}

		this.mainObject.setValue("notify", invite);
	},

	// Called after object has been saved
	save:function()
	{
		/*
		if (this.mainObject.id && this.t_notify)
		{
			var args = [["did", this.mainObject.id]];

			var obj_ref = this.mainObject.getValue("obj_reference");
			if (obj_ref)
				args[args.length] = ["obj_reference", obj_ref];

			var values = this.t_notify.getValues();
			for (var i = 0; i < values.length; i++)
				args[args.length] = ["notify[]", values[i][0]];
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.onsave();
            };
            ajax.exec("/controller/Object/discussionNotify", args);
		}
		else
		{
			this.onsave();
		}
		*/
		this.onsave();
	},

	onsave:function()
	{
	},

	load:function()
	{
	},

	/**
	 * Fired when the object moves between edit mode and read-only
	 *
	 * @param bool editMode If true we are editing the object
	 */
	onMainObjectToggleEdit:function(editMode)
	{
		if (editMode)
		{
			this.editRow.style.display = "table-row";
		}
		else
		{
			this.editRow.style.display = "none";
		}

	},

	/**
	 * Render interface in DOM tree
	 */
	buildInterface:function()
	{
		this.m_con.innerHTML = "";
		var table = alib.dom.createElement("table", this.m_con);
		table.cellPadding = 0;
		table.cellSpacing = 0;
		alib.dom.styleSet(table, "margin-top", "3px");
		alib.dom.styleSet(table, "width", "98%");
		var tbody = alib.dom.createElement("tbody", table);
			
		if (this.mainObject.id) // Print who was notified
		{
			var row = alib.dom.createElement("tr", tbody);
			this.viewRow = row;
			var to = this.mainObject.getValue("notified");

			if (to)
			{
				var td_lbl = alib.dom.createElement("td", row);
				alib.dom.styleSet(td_lbl, "width", "25px");
				alib.dom.styleSet(td_lbl, "padding-left", "5px");
				alib.dom.styleSetClass(td_lbl, "formLabel");
				td_lbl.innerHTML = "Invited";
				var td_inp = alib.dom.createElement("td", row);
				td_inp.innerHTML = to;
			}
		}

		var row = alib.dom.createElement("tr", tbody);
		this.editRow = row;
		var td_lbl = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_lbl, "width", "100px");
		alib.dom.styleSet(td_lbl, "padding-left", "5px");
		alib.dom.styleSetClass(td_lbl, "formLabel");
		td_lbl.innerHTML = "Invite";
		var td_inp = alib.dom.createElement("td", row);
		var inp_notify = alib.dom.createElement("input", td_inp);
		alib.dom.styleSet(inp_notify, "width", "200px");
		var opts = { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: false}}};
		this.t_notify = new CTextBoxList(inp_notify, opts);
		this.t_notify.acLoadValues("/users/json_autocomplete.php");
	}
}
