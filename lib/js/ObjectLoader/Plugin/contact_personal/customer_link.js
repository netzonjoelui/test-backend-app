{
	name:"customer_link",
	title:"Customer Link",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.custObj = new CAntObject("customer");

		if (this.toolbar)
		{
			if (this.olCls)
				this.olCls.pluginAddToolbarEntry("Link To "+this.custObj.title, function(cbdata) { cbdata.cls.linkCustomer(); }, { cls:this });
			/*
			var btn = new CButton("Link To "+this.custObj.title, function(cls) { cls.linkCustomer(); }, [this], "b1");
			this.toolbar.AddItem(btn.getButton(), "left");
			*/
		}

		this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		this.buildInterface();
	},

	save:function()
	{
		// Nothing needs to be done because customer_id is saved in the main object
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
		this.m_con.innerHTML = "";
		alib.dom.styleSet(this.m_con, "text-align", "center");
		
		var custid = this.mainObject.getValue("customer_id");
		if (custid)
		{
			var frm = new CWindowFrame("");
			var frmcon = frm.getCon();
			frm.print(this.m_con);

			frmcon.innerHTML = "This contact is linked to "+this.custObj.title+": ";
			var lblcon = alib.dom.createElement("a", frmcon);
			lblcon.href = "javascript:void(0);";
			lblcon.oid = custid;
			lblcon.onclick = function() { loadObjectForm("customer", this.oid); }
			objectSetNameLabel("customer", custid, lblcon);

			var spacer = alib.dom.createElement("span", frmcon);
			spacer.innerHTML = "&nbsp;&nbsp;&nbsp;";

			var act = alib.dom.createElement("a", frmcon);
			act.href = "javascript:void(0);";
			act.cls = this;
			act.onclick = function() { this.cls.mainObject.setValue("customer_id", ""); this.cls.buildInterface(); }
			act.innerHTML = "[unlink]";
		}
	},
	
	linkCustomer:function()
	{
		var cbrowser = new CCustomerBrowser();
		cbrowser.plugincls = this;
		cbrowser.onSelect = function(cid, name) 
		{ 
			this.plugincls.mainObject.setValue("customer_id", cid);
			this.plugincls.buildInterface();
		}
		cbrowser.showDialog();	
	}
}
