{
	name:"publish",
	title:"Publish",
	mainObject:null,

	main:function(con)
	{
		this.data = new Object();
		this.data.username = "";
		this.data.password = "    "; // default is four spaces
		this.data.f_files_view = false;
		this.data.f_files_upload = false;
		this.data.f_files_modify = false;
		this.data.folder_id = null;
		this.m_con = con;

		this.load();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		if (this.mainObject)
			this.load();
	},

	save:function()
	{
		var args = [["username", this.data.username], ["password", this.data.password], ["customer_id", this.mainObject.id], 
					["f_files_view", (this.data.f_files_view)?'t':'f'], ["f_files_upload", (this.data.f_files_upload)?'t':'f'], 
					["f_files_modify", (this.data.f_files_modify)?'t':'f']];
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.onsave();
        };
        ajax.exec("/controller/Customer/savePublish", args);
	},

	onsave:function()
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
                        if (!this.cbData.cls.data.username)
                            this.cbData.cls.data.username = ret[username];
                        if (!this.cbData.cls.data.f_files_view)
                            this.cbData.cls.data.f_files_view = ret[f_files_view];
                        if (!this.cbData.cls.data.f_files_upload)
                            this.cbData.cls.data.f_files_upload = ret[f_files_upload];
                        if (!this.cbData.cls.data.f_files_modify)
                            this.cbData.cls.data.f_files_modify = ret[f_files_modify];
                        this.cbData.cls.data.folder_id = ret[folder_id];
                    }
                }
                catch(e)
                {
                    alert(e);
                }
            }

            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Customer/getPublish",
                    [["customer_id", this.mainObject.id]]);
	},

	buildInterface:function()
	{
		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Sharing large files and information securely is now easier than ever. Simply send the link below to your contact or account along with a password (the default is their customer number). Once the customer clicks the link they will be asked to log in and gain access to upload and download data. ";

		// Print account info
		// --------------------------------------------------------
		var frm = new CWindowFrame("Account Information");
		var frmcon = frm.getCon();
		frm.print(this.m_con);
		var tbl = alib.dom.createElement("table", frmcon);
		var tbody = alib.dom.createElement("tbody", tbl);

		var row = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", row);
		td.innerHTML = "Username: ";
		var td = alib.dom.createElement("td", row);
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.value = this.data.username;
		inp.cls = this;
		inp.onchange = function() { this.cls.data.username = this.value; }
		td.appendChild(inp);

		var row = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", row);
		td.innerHTML = "Password: ";
		var td = alib.dom.createElement("td", row);
		var inp = alib.dom.createElement("input");
		inp.type = "password";
		inp.value = this.data.password;
		inp.cls = this;
		inp.onchange = function() { this.cls.data.password = this.value; }
		td.appendChild(inp);

		if (this.mainObject.id)
		{
			var row = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", row);
			td.innerHTML = "Link:";
			var td = alib.dom.createElement("td", row);
			var lnk = "https://"+document.domain+"/customer/profile/"+this.mainObject.id;
			td.innerHTML = "<a href=\""+lnk+"\" target='_blank'>"+lnk+"</a>&nbsp;&nbsp;";

			var btn = new CButton("Send Email", function(lnk, email) { 
					Ant.Emailer.compose(email, {
						body: "<br><br><a href='"+lnk+"'>"+lnk+"</a>"
					});
				}, 
				[lnk, this.mainObject.getValueName("email_default")], "b1");

			btn.print(td);
		}

		// Publish settings
		// --------------------------------------------------------
		var frm = new CWindowFrame("Publish Settings");
		var frmcon = frm.getCon();
		frm.print(this.m_con);

		var dvrow = alib.dom.createElement("div", frmcon);
		var chk = alib.dom.createElement("input");
		chk.type = "checkbox";
		chk.cls = this;
		chk.checked = this.data.f_files_view;
		chk.onclick = function()
		{
			this.cls.data.f_files_view = this.checked;
		}
		dvrow.appendChild(chk);
		var lbl = alib.dom.createElement("span", dvrow);
		lbl.innerHTML = "Allow "+this.mainObject.title+" to view/download published files";

		var dvrow = alib.dom.createElement("div", frmcon);
		var chk = alib.dom.createElement("input");
		chk.type = "checkbox";
		chk.cls = this;
		chk.checked = this.data.f_files_upload;
		chk.onclick = function()
		{
			this.cls.data.f_files_upload = this.checked;
		}
		dvrow.appendChild(chk);
		var lbl = alib.dom.createElement("span", dvrow);
		lbl.innerHTML = "Allow "+this.mainObject.title+" to upload new files to the published folder";

		var dvrow = alib.dom.createElement("div", frmcon);
		var chk = alib.dom.createElement("input");
		chk.type = "checkbox";
		chk.cls = this;
		chk.checked = this.data.f_files_modify;
		chk.onclick = function()
		{
			this.cls.data.f_files_modify = this.checked;
		}
		dvrow.appendChild(chk);
		var lbl = alib.dom.createElement("span", dvrow);
		lbl.innerHTML = "Allow "+this.mainObject.title+" to modify/overwrite/delete published files";

		// Published files
		// --------------------------------------------------------
		var frm = new CWindowFrame("Published files");
		this.folderCon = frm.getCon();
		frm.print(this.m_con);
		if (this.data.folder_id)
		{
			this.printFolder();
		}
		else
		{
			this.folderCon.innerHTML = "Files can be uploaded after you save the record";
		}
	},

	printFolder:function()
	{
		this.folderCon.innerHTML = "";
		var br = new CFileBrowser();
		br.setRootId(this.data.folder_id);
		br.setPath("/");
		br.printInline(this.folderCon);
	}
}
