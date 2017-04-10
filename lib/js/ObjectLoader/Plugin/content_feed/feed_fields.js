{
	name:"feed_fields",
	title:"Manage Custom Fields",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.fields = new Object();

		this.load();
	},

	save:function()
	{
		this.onsave();
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
        this.buildInterface();
	},

	load:function()
	{
		if (this.mainObject.id)
			this.getFields();
		else
			this.buildInterface();
	},

	buildInterface:function()
	{
		this.m_con.innerHTML = "";

        if(!this.mainObject.id)
        {
            var p = alib.dom.createElement("p", this.m_con);            
            p.innerHTML = "Please save changes to view more details";
            return;
        }
        
		var tb = new CToolbar();
		var btn = new CButton("Add Field", function(cls) { cls.newField(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		tb.print(this.m_con);

		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Use this tool to add custom fields for this feed. Any changes you make here will only apply to this feed. To add fields globaly (to all feeds) please modify the settings for this application.";

		// Print fields
		// --------------------------------------------------------
		var frm = new CWindowFrame("Custom Fields");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		this.m_relCon.innerHTML = "";
		this.m_relTable = new CToolTable("100%");
		this.m_relTable.addHeader("Title");
		this.m_relTable.addHeader("System Name");
		this.m_relTable.addHeader("Type");
		this.m_relTable.addHeader("Remove", "center", "20px");
		this.m_relTable.print(this.m_relCon);

		if (!this.fields.length)
			this.m_relCon.innerHTML = "No fields have been added";
		else
		{
			for(field in this.fields)
			{
                var currentField = this.fields[field];
				this.addField(currentField.id, currentField.name, currentField.title, currentField.type);
			}
		}
	},

	addField:function(fid, name, title, type)
	{
		var rw = this.m_relTable.addRow();

		var a = alib.dom.createElement("a");
		a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		a.href = "javascript:void(0)";
		a.rw = rw;
		a.fname = name;
		a.cls = this;
		a.onclick = function()
		{
			var dlg = new CDialog("Remove Field");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.fname = this.fname;
			dlg.confirmBox("Are you sure you want to permanantly remove this field?", "Remove Field");
			dlg.onConfirmOk = function()
			{                
                ajax = new CAjax('json');
                ajax.cbData.cls = this;
                ajax.onload = function(ret)
                {
                    this.cbData.cls.rw.deleteRow();
					Ant.EntityDefinitionLoader.get("content_feed_post").load();
                };
                ajax.exec("/controller/Content/feedDeleteField",
                            [["fid", this.cls.mainObject.id], ["dfield", this.fname]]);
			}
		}

		rw.addCell(title);
		rw.addCell(name);
		rw.addCell(type);
		rw.addCell(a, false, "center");

	},

	newField:function()
	{
		// Create loader callback
		var okfunct = function(dlg, cls, inp_name, sel_types)
		{
            ajax = new CAjax('json');
            ajax.cbData.cls = cls;
            ajax.cbData.dlg = dlg;
            ajax.onload = function(ret)
            {
                if(!ret)
                    return;
                    
                if(!ret['error'])
                {
					Ant.EntityDefinitionLoader.get("content_feed_post").load();
                    this.cbData.dlg.hide();
                    this.cbData.cls.getFields();
                }
                else
                    ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            };
            ajax.exec("/controller/Content/feedAddField",
                        [["name", inp_name.value], ["type", sel_types.value], ["fid", cls.mainObject.id]]);
		}

		var dlg = new CDialog("Add Custom Field");
		dlg.f_close = true;
		var dv = alib.dom.createElement("div");

		// Name
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Field Name";
		var dv_inp = alib.dom.createElement("div", dv);
		var inp_name = alib.dom.createElement("input", dv_inp);
		alib.dom.styleSet(inp_name, "width", "98%");

		// Type
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Field Type";
		var dv_inp = alib.dom.createElement("div", dv);
		var sel_types = alib.dom.createElement("select", dv_inp);
		sel_types.dlgField = true; // selects are hidden by the dlg class - unhide
		sel_types[sel_types.length] = new Option("Text", "text", false, true);
		sel_types[sel_types.length] = new Option("Date", "date", false, false);
		sel_types[sel_types.length] = new Option("Number", "number", false, false);
		sel_types[sel_types.length] = new Option("File", "file", false, false);
		//this.getTemplates(sel_templates);

		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");
		var btn = new CButton("Continue", okfunct, [dlg, this, inp_name, sel_types], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 450);	
	},
	
	getFields:function()
	{
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if (ret)
            {
                try
                {
                    this.cbData.cls.fields = ret;
                }
                catch(e)
                {
                    alert(e);
                }
            }

            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Content/feedGetFields",
                    [["fid", this.mainObject.id]]);
	}
}
