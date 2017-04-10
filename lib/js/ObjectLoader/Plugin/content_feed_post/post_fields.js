{
	name:"feed_post_fields",
	title:"Custom Fields",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.fields = new Object();

		this.load();
	},

	save:function()
	{
		if (this.mainObject.id)
		{
			var args = [["pid", this.mainObject.id]];
			for(field in this.fields)
			{
                var currentField = this.fields[field];
                
				args[args.length] = ["fields[]", currentField.id];
				args[args.length] = ["field_value_"+currentField.id, currentField.value];
			}
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.onsave();
            };
            ajax.exec("/controller/Content/feedPostSaveFields", args);
		}
		else
		{
			this.onsave();
		}
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
	},

	load:function()
	{
		if (this.mainObject.getValue("feed_id"))
			this.getFields();
		else
			this.buildInterface();
	},

	buildInterface:function()
	{
		this.m_con.innerHTML = "";

		// Print fields
		// --------------------------------------------------------
		var frm = new CWindowFrame("Custom Fields");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		this.m_relCon.innerHTML = "";
		this.m_relTable = new CToolTable("100%");
		this.m_relTable.addHeader("Title", "left", "100px");
		this.m_relTable.addHeader("Value");
		this.m_relTable.print(this.m_relCon);

		if (!this.fields.length)
			this.m_relCon.innerHTML = "This feed does not have any custom feeds";
		else
		{
			for (var i = 0; i < this.fields.length; i++)
			{
				this.addField(this.fields[i].id, this.fields[i].name, this.fields[i].title, this.fields[i].type, this.fields[i].value);
			}
		}
	},

	addField:function(fid, name, title, type, value)
	{
		var rw = this.m_relTable.addRow();

		var inp_con = alib.dom.createElement("div");
		switch (type)
		{
		case 'file':
			var inp = alib.dom.createElement("input", inp_con);
			inp.type = "text";
			inp.value = value;
			inp.style.width = "200px";

			var cbrowser = new AntFsOpen();
			cbrowser.cbData.inp = inp;
			cbrowser.cbData.cls = this;
			cbrowser.cbData.fieldId = fid;
			cbrowser.onSelect = function(fid, name, path) 
			{
				this.cbData.inp.value = fid;
				this.cbData.cls.setValue(this.cbData.fieldId, fid);
				//var lbl = document.getElementById("file_"+field_name);
				//lbl.innerHTML = "<a href=\"/files/"+fid+"\">"+name+"</a>&nbsp;&nbsp;&nbsp;";
			}

			var btn = new CButton("Select File", function(cbrowser) { cbrowser.showDialog();  }, [cbrowser], "b1");
			btn.print(inp_con);
			break;
		case 'text':
		case 'number':
		case 'date':
		default:
			var inp = alib.dom.createElement("input", inp_con);
			inp.type = "text";
			inp.value = value;
			inp.style.width = "200px";
			inp.cls = this;
			inp.fieldId = fid;
			inp.onchange = function()
			{
				this.cls.setValue(this.fieldId, this.value);
			}
			break;
		}
		rw.addCell(title);
		rw.addCell(inp_con);
	},
	
	setValue:function(fid, value)
	{
		for (var i = 0; i < this.fields.length; i++)
		{
			if (this.fields[i].id == fid)
				this.fields[i].value = value;
		}
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
                    [["fid", this.mainObject.getValue("feed_id")], ["pid", this.mainObject.id]]);
	}
}

