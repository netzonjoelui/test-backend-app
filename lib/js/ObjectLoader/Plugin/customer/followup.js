{
	name:"followup",
	title:"Followup",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.loaded = false;

		if (this.toolbar && this.mainObject.id)
			this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		if (this.mainObject.id)
			this.buildInterface();
	},

	save:function()
	{
		this.onsave();
	},

	onsave:function()
	{
	},

	load:function()
	{
	},

	buildInterface:function()
	{
		if (!this.loaded)
		{
			if (this.olCls)
				this.olCls.pluginAddToolbarEntry("Follow-Up ", function(cbdata) { cbdata.cls.showDialog(); }, { cls:this });

			/* this.toolbar no longer used, the above function is required for plugins
			var btn = new CButton("Follow-Up", function(cls) { cls.showDialog(); }, [this], "b1");
			this.toolbar.AddItem(btn.getButton(), "left");
			*/
			this.loaded = true;
		}
	},

	showDialog:function()
	{
		var dlg = new CDialog("Select Follow-Up Action");
		dlg.f_close = true;

		var dv = alib.dom.createElement("div");

		var tbl = alib.dom.createElement("table", dv);
		var tbody = alib.dom.createElement("tbody", tbl);


		// Task Button
		var row = alib.dom.createElement("tr", tbody);
        var btn = alib.ui.Button("Create Task", {
			className:"b2", dlg:dlg, olCls: this.olCls, obj:this.mainObject,
			onclick:function() 
			{
				this.dlg.hide(); 
				this.olCls.loadObjectForm("task", null, [["customer_id", this.obj.id]]);
			}
		});
		var td_btn = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_btn, "width", "120px");
		td_btn.appendChild(btn.getButton());
		var td_desc = alib.dom.createElement("td", row);
		td_desc.innerHTML = "Create a task to be completed by you or someone else in your organization.";
        
		// Event
		var row = alib.dom.createElement("tr", tbody);
		var btn = alib.ui.Button("Schedule Event", {
			className:"b2", dlg:dlg, olCls: this.olCls, obj:this.mainObject,
			onclick:function() 
			{
				this.dlg.hide(); 
				this.olCls.loadObjectForm("calendar_event", null, [["customer_id", this.obj.id]]);
			}
		});
		var td_btn = alib.dom.createElement("td", row);
        td_btn.appendChild(btn.getButton());
		var td_desc = alib.dom.createElement("td", row);
		td_desc.innerHTML = "Create a future calendar event that is associated with this customer.";
        

		// Comments / Notes
		var row = alib.dom.createElement("tr", tbody);
        var btn = alib.ui.Button("Record Activity", {
			className:"b2", dlg:dlg, olCls: this.olCls, oid: this.mainObject.id,
			onclick:function() {
				this.dlg.hide(); 
				this.olCls.loadObjectForm("activity", null, [["obj_reference", "customer:" + this.oid]]);
			}
		});
		var td_btn = alib.dom.createElement("td", row);
        td_btn.appendChild(btn.getButton());
		var td_desc = alib.dom.createElement("td", row);
		td_desc.innerHTML = "Record an activity like a \"Phone Call\" or \"Sent a Letter.\"";
	

        // Cancel Button
        var btn = alib.ui.Button("Close", 
                    {
                        className:"b1", dlg:dlg,
                        onclick:function() 
                        {
                            this.dlg.hide(); 
                        }
                    });
                    
		var divCancel = alib.dom.createElement("div", dv);
		alib.dom.styleSet(divCancel, "text-align", "right");
        divCancel.appendChild(btn.getButton());

		dlg.customDialog(dv, 450);
	}
}
