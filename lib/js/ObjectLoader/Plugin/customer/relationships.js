{
	name:"relationships",
	title:"Relationships",
	mainObject:null,


	// TODO: save

	main:function(con)
	{
		this.m_con = con;
		this.relationships = new Array();
		this.todelete = new Array();
		this.relTypes = new Array();

		this.getRelTypes(); // load will be called after rel types have been loaded
		//this.load();
	},

	save:function()
	{
		if (!this.mainObject.id)
		{
			this.onsave();
			return;
		}

		var args = [["customer_id", this.mainObject.id]];

		// Delete
		for (var i = 0; i < this.todelete.length; i++)
		{
			args[args.length] = ["delete[]", this.todelete[i]];
		}

		// Save
		for (var i = 0; i < this.relationships.length; i++)
		{
			args[args.length] = ["relationships[]", this.relationships[i].cid];
			args[args.length] = ["r_type_id_"+this.relationships[i].cid, this.relationships[i].rtype_id];
			args[args.length] = ["r_type_name_"+this.relationships[i].cid, this.relationships[i].rname];
		}
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.onsave();
        };
        ajax.exec("/controller/Customer/saveRelationships", args);
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
	},

	load:function()
	{
		if (!this.mainObject.id)
		{
			this.buildInterface();
			return;
		}

		this.m_con.innerHTML = "Loading...";
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.m_con.innerHTML = "";

            if (ret)
            {
                try
                {                    
                    if (ret.length)
                    {
                        for(relationship in ret)
                        {
                            var currentRelationship = ret[relationship];
                            this.cbData.cls.relationships[this.cbData.cls.relationships.length] = currentRelationship;
                        }
                    }
                }
                catch(e)
                {
                    alert(e);
                }
            }

            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Customer/getRelationships",
                    [["customer_id", this.mainObject.id]]);
	},

	buildInterface:function()
	{
		var tb = new CToolbar();
		var btn = new CButton("Add Relationship", function(cls) { cls.newRelationship(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		tb.print(this.m_con);

		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Use this tool to track relationships between "+this.mainObject.titlePl+". It is important to set different relationship types to keep your data organized. For example: define if a relationship as a \"friend\", \"employee\" or \"family member\"";

		// Print relationships
		// --------------------------------------------------------
		var frm = new CWindowFrame("Relationships");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		if (!this.relationships.length)
			this.m_relCon.innerHTML = "No relationships have been set";
		else
		{
			for (var i = 0; i < this.relationships.length; i++)
			{
				this.addRelationship(this.relationships[i].cid, this.relationships[i].name, this.relationships[i].email, 
										this.relationships[i].phone, this.relationships[i].title, this.relationships[i].rname);
			}
		}
	},

	addRelationship:function(cid, name, email, phone, title, rtype, rtype_id)
	{
		if (!this.m_relTable)
		{
			this.m_relCon.innerHTML = "";

			this.m_relTable = new CToolTable("100%");
			this.m_relTable.addHeader("Name");
			this.m_relTable.addHeader("Email");
			this.m_relTable.addHeader("Phone");
			this.m_relTable.addHeader("Title");
			this.m_relTable.addHeader("Relationship Type");
			this.m_relTable.addHeader("&nbsp;", "center", "50px");
			this.m_relTable.addHeader("Remove", "center", "20px");
			this.m_relTable.print(this.m_relCon);
		}

		var rw = this.m_relTable.addRow();

		var a = alib.dom.createElement("a");
		a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		a.href = "javascript:void(0)";
		a.rw = rw;
		a.cid = cid;
		a.cls = this;
		a.onclick = function()
		{
			var dlg = new CDialog("Remove Relationship");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.cid = this.cid;
			dlg.confirmBox("Are you sure you want to remove this relationship?", "Remove Relationship");
			dlg.onConfirmOk = function()
			{
				this.cls.todelete[this.cls.todelete.length] = this.cid;
				for (var i = 0; i < this.cls.relationships.length; i++)
				{
					if (this.cls.relationships[i].cid == this.cid)
						this.cls.relationships.splice(i);
				}

				this.rw.deleteRow();
			}
		}

		rtypeConLbl = alib.dom.createElement("span");
		rtypeConLbl.innerHTML = rtype;

		// Create name link if id
		var namelnk = alib.dom.createElement("a");
		namelnk.href = "javascript:void(0);";
		namelnk.cid = cid;
		namelnk.onclick = function() { loadObjectForm("customer", this.cid); }
		namelnk.innerHTML = unescape(name);

		var rtypeCon = alib.dom.createElement("div");
		this.buildRelTypeDD(rtypeCon, rtypeConLbl, cid);

		var eml_lnk = (email) ? "<a href='javascript:void(0);' onclick=\"Ant.Emailer.compose('"+email+"');\">"+email+"</a>" : '';

		rw.addCell(namelnk);
		rw.addCell(eml_lnk);
		rw.addCell(phone);
		rw.addCell(title);
		rw.addCell(rtypeConLbl);
		rw.addCell(rtypeCon, false, "center");
		rw.addCell(a, false, "center");

	},

	newRelationship:function()
	{
		var cbrowser = new AntObjectBrowser("customer");
		cbrowser.customerTitle = this.mainObject.title;
		cbrowser.cbData.relCls = this;
		cbrowser.onSelect = function(cid, name) 
		{ 
			if (this.cbData.relCls.mainObject.id && this.cbData.relCls.mainObject.id==cid)
				ALib.Dlg.messageBox(this.cbData.relCls.mainObject.title + " cannot be a realtionship of itself!");
			else
			{
				this.cbData.relCls.relationships[this.cbData.relCls.relationships.length] = {cid:cid, name:name, phone:"", title:"", rname:"", rtype_id:""}
				this.cbData.relCls.addRelationship(cid, name, "", "", "", "");
			}
		}
		cbrowser.displaySelect();
	},

	buildRelTypeDD:function(con, con_lbl, cid)
	{
		var dm_act = new CDropdownMenu();

		for (var i = 0; i < this.relTypes.length; i++)
		{
			dm_act.addEntry(this.relTypes[i].name, function(cls, con_lbl, cid, name, type){ con_lbl.innerHTML = name; cls.setRelType(cid, name, type); }, 
							"/images/icons/tilde.gif", null, [this, con_lbl, cid, this.relTypes[i].name, this.relTypes[i].id]);
		}

		dm_act.addEntry("Other / Custom", function(cls, con_lbl, cid){ cls.addOtherRel(cid, con_lbl); }, 
						"/images/icons/tilde.gif", null, [this, con_lbl, cid]);

		con.appendChild(dm_act.createButtonMenu(""));
	},

	addOtherRel:function(cid, con_lbl)
	{
		var dlg = new CDialog();
		dlg.relCls = this;
		dlg.promptBox("Relationship", "Describe the relationship:", "", [cid, con_lbl]);
		dlg.onPromptOk = function(name, cid, con_lbl)
		{
			con_lbl.innerHTML = name;
			for (var i = 0; i < this.relCls.relationships.length; i++)
			{
				if (this.relCls.relationships[i].cid == cid)
				{
					this.relCls.relationships[i].rname = name;
					this.relCls.relationships[i].rtype_id = "";
				}
			}
		}
	},
	
	getRelTypes:function()
	{
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.load();
            if (ret)
            {
                try
                {
                    this.cbData.cls.relTypes = ret;
                }
                catch(e)
                {
                    alert(e);
                }
            }
        };
        ajax.exec("/controller/Customer/getRelationshipTypes");
	},


	setRelType:function(cid, rname, rtype_id)
	{
		for (var i = 0; i < this.relationships.length; i++)
		{
			if (this.relationships[i].cid == cid)
			{
				this.relationships[i].rtype_id = rtype_id;
				this.relationships[i].rname = rname;
			}
		}
	}
}
