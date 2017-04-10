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

			/* this.toolbar replaced with above pluginAddToolbarEntry
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

		// Task
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		dv_hdr.innerHTML = "Task";
		var dv_desc = alib.dom.createElement("div", dv);
		dv_desc.innerHTML = "Create a task to be completed by you or someone else in your organization.";
		var btn = new CButton("Create Task", 
							  function(dlg, obj) { loadObjectForm("task", null, null, null, [["associations[]", obj.name+":"+obj.id]]); dlg.hide(); }, 
							  [dlg, this.mainObject], "b2");
		btn.print(dv);

		// Event
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Calendar Event";
		var dv_desc = alib.dom.createElement("div", dv);
		dv_desc.innerHTML = "Create a future calendar event that is associated with this customer.";
		var btn = new CButton("Schedule Event", 
							  function(dlg, obj) { loadObjectForm("calendar_event", null, null, null, [["associations[]", obj.name+":"+obj.id]]); dlg.hide(); }, 
							  [dlg, this.mainObject], "b2");
		btn.print(dv);

		// Comments / Notes
		var assoc = this.mainObject.name+":"+this.mainObject.id;
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Record Activity";
		var dv_desc = alib.dom.createElement("div", dv);
		dv_desc.innerHTML = "Record an activity like a \"Phone Call\" or \"Sent a Letter.\"";
		var btn = new CButton("Record Activity", 
							  function(dlg, assoc) { dlg.hide(); var act = new CActivity(null, assoc, assoc); act.showDialog(); }, 
							  [dlg, assoc], "b2");
		btn.print(dv);
	

		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 450);
	}
}
