{
	name:"feed_categories",
	title:"Manage Feed Categories",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.categories = new Object();

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
	},

	load:function()
	{
		if (this.mainObject.id)
			this.getCategories();
		else
			this.buildInterface();
	},

	buildInterface:function()
	{
		this.m_con.innerHTML = "";

		var tb = new CToolbar();
		var btn = new CButton("Add Category", function(cls) { cls.newCategory(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		tb.print(this.m_con);

		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Use this tool to add categories specific to this feed. Any changes you make here will only apply to this feed.";

		// Print categories 
		// --------------------------------------------------------
		var frm = new CWindowFrame("Post Categories");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		this.m_relCon.innerHTML = "";
		this.m_relTable = new CToolTable("100%");
		this.m_relTable.addHeader("Name");
		this.m_relTable.addHeader("Remove", "center", "20px");
		this.m_relTable.print(this.m_relCon);

		if (!this.categories.length)
			this.m_relCon.innerHTML = "No categories have been added";
		else
		{
			for(category in this.categories)
			{
                var currentCategory = this.categories[category];
                
				this.addCategory(currentCategory.id, currentCategory.name);
			}
		}
	},

	addCategory:function(cid, name)
	{
		var rw = this.m_relTable.addRow();

		var a = alib.dom.createElement("a");
		a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		a.href = "javascript:void(0)";
		a.rw = rw;
		a.cid = cid;
		a.cls = this;
		a.onclick = function()
		{
			var dlg = new CDialog("Remove Field");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.cid = this.cid;
			dlg.confirmBox("Are you sure you want to permanantly remove this category?", "Remove Category");
			dlg.onConfirmOk = function()
			{				
                /*var rpc = new CAjaxRpc("/controller/Content/feedDeleteCategory", "feedDeleteCategory", 
                                        [["fid", this.cls.mainObject.id], ["dcat", this.cid]], function(ret, cls) { cls.rw.deleteRow(); }, [this], AJAX_POST, true, "json");*/
                                        
                ajax = new CAjax('json');
                ajax.cbData.cls = this;
                ajax.onload = function(ret)
                {
                     this.cbData.cls.rw.deleteRow();
                };
                ajax.exec("/controller/Content/feedDeleteCategory",
                            [["fid", this.cls.mainObject.id], ["dcat", this.cid]]);
			}
		}

		rw.addCell(name);
		rw.addCell(a, false, "center");

	},

	newCategory:function()
	{
		// Create loader callback
		var okfunct = function(dlg, cls, inp_name)
		{
			/*var funct = function(ret, dlg, cls)
			{
                if(!ret['error'])
                {
                    dlg.hide();
                    cls.getCategories();
                }
                else
                    ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
			}
            var rpc = new CAjaxRpc("/controller/Content/feedDeleteCategory", "feedAddCategory", 
                                        [["name", inp_name.value], ["fid", cls.mainObject.id]], funct, [dlg, cls], AJAX_POST, true, "json");*/
                                        
            ajax = new CAjax('json');
            ajax.cbData.cls = cls;
            ajax.cbData.dlg = dlg;
            ajax.onload = function(ret)
            {
                if(!ret['error'])
                {
                    this.cbData.dlg.hide();
                    this.cbData.cls.getCategories();
                }
                else
                    ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            };
            ajax.exec("/controller/Content/feedAddCategory",
                        [["name", inp_name.value], ["fid", cls.mainObject.id]]);
		}

		var dlg = new CDialog("Add Category");
		dlg.f_close = true;
		var dv = alib.dom.createElement("div");

		// Name
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Name";
		var dv_inp = alib.dom.createElement("div", dv);
		var inp_name = alib.dom.createElement("input", dv_inp);
		alib.dom.styleSet(inp_name, "width", "98%");

		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");
		var btn = new CButton("Continue", okfunct, [dlg, this, inp_name], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 450);	
	},
	
	getCategories:function()
	{
		/*var funct = function(ret, cls)
		{
			if (ret)
			{
				try
				{
					cls.categories = ret;
				}
				catch(e)
				{
					alert(e);
				}
			}

			cls.buildInterface();
		}
        var rpc = new CAjaxRpc("/controller/Content/feedGetCategories", "feedGetCategories", [["fid", this.mainObject.id]], funct, [this], AJAX_POST, true, "json");*/
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if (ret)
            {
                try
                {
                    this.cbData.cls.categories = ret;
                }
                catch(e)
                {
                    alert(e);
                }
            }

            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Content/feedGetCategories",
                    [["fid", this.mainObject.id]]);
	}
}
