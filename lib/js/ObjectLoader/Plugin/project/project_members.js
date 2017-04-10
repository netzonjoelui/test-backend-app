{
	name:"members",
	title:"Members",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.members = new Array();
		this.todelete = new Array();
		this.positions = new Array();

		this.getPositions();
	},

	save:function()
	{
		if (!this.mainObject.id)
		{
			this.onsave();
			return;
		}

		var funct = function(ret, cls)
		{
			if (ret && ret != "-1")
			{
				var saved_members = eval("("+unescape(ret)+")");
				for (var i = 0; i < saved_members.length; i++)
				{
					for (var j = 0; j < cls.members.length; j++)
					{
						// Set to actual id for new members
						if (cls.members[j].id == saved_members[i][0])
							cls.members[j].id = saved_members[i][1];
					}
				}
			}
			cls.onsave();
		}

		var args = [["project_id", this.mainObject.id]];

		// Delete
		for (var i = 0; i < this.todelete.length; i++)
		{
			args[args.length] = ["delete[]", this.todelete[i]];
		}

		// Save
		for (var i = 0; i < this.members.length; i++)
		{
			args[args.length] = ["members[]", this.members[i].user_id];
			args[args.length] = ["m_position_id_"+this.members[i].user_id, this.members[i].position_id];
		}

		//ALib.m_debug = true;
		//AJAX_TRACE_RESPONSE = true;
		var rpc = new CAjaxRpc("/project/xml_actions.php", "save_members", args, funct, [this], AJAX_POST);
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
	},

	getPositions:function()
	{
		var funct = function(ret, cls)
		{
			if (ret)
			{
				try
				{
					cls.positions = eval("("+unescape(ret)+")");
				}
				catch(e)
				{
					alert(e);
				}
			}
			cls.load(); // Load members
		}

		//ALib.m_debug = true;
		//AJAX_TRACE_RESPONSE = true;
		var rpc = new CAjaxRpc("/project/xml_actions.php", "get_positions", [["project_id", this.mainObject.id]], funct, [this]);
	},

	load:function()
	{
		if (!this.mainObject.id)
		{
			this.buildInterface();
			return;
		}

		this.m_con.innerHTML = "Loading...";

		var funct = function(ret, cls)
		{
			cls.m_con.innerHTML = "";

			if (ret)
			{
				try
				{
					var tmp_members = eval("("+unescape(ret)+")");

					if (tmp_members)
					{
						for (var i = 0; i < tmp_members.length; i++)
						{
							cls.members[i] = tmp_members[i];
						}
					}
				}
				catch(e)
				{
					alert(e);
				}
			}

			cls.buildInterface();
		}

		//ALib.m_debug = true;
		//AJAX_TRACE_RESPONSE = true;
		var rpc = new CAjaxRpc("/project/xml_actions.php", "get_members", [["project_id", this.mainObject.id]], funct, [this]);
	},

	buildInterface:function()
	{
		this.m_relTable = null;
		this.m_posTable = null;
		this.m_con.innerHTML = "";

		var tb = new CToolbar();
		var btn = new CButton("Add Member", function(cls) { cls.addMember(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		var btn = new CButton("Add Position", function(cls) { cls.newPosition(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		tb.print(this.m_con);

		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Members are users that are directly involved with and/or responsible for this project. A position title can be assigned by adding a position (click \"Add Position\" above) then applying the position to a member via the dropdown next to the remove icon.";

		// Print members
		// --------------------------------------------------------
		var frm = new CWindowFrame("Members");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		if (!this.members.length)
			this.m_relCon.innerHTML = "No members have been set";
		else
		{

			for (var i = 0; i < this.members.length; i++)
			{
				this.printMember(this.members[i].id, this.members[i].user_id, this.members[i].username, 
										this.members[i].position_name);
			}
		}

		var frm = new CWindowFrame("Positions");
		this.m_posCon = frm.getCon();
		frm.print(this.m_con);

		if (!this.positions.length)
			this.m_posCon.innerHTML = "No positions have been defined";
		else
		{
			for (var i = 0; i < this.positions.length; i++)
			{
				this.printPosition(this.positions[i].id, this.positions[i].name);
			}
		}
	},

	printMember:function(id, user_id, username, position_name)
	{
		if (!this.m_relTable)
		{
			this.m_relCon.innerHTML = "";

			this.m_relTable = new CToolTable("100%");
			this.m_relTable.addHeader("Member");
			//this.m_relTable.addHeader("&nbsp;", "center", "20px");
			this.m_relTable.addHeader("Position", "left", "150px");
			this.m_relTable.addHeader("&nbsp;", "center", "20px");
			this.m_relTable.addHeader("Remove", "center", "20px");
			this.m_relTable.print(this.m_relCon);
		}

		var rw = this.m_relTable.addRow();

		var a = alib.dom.createElement("a");
		a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		a.href = "javascript:void(0)";
		a.rw = rw;
		a.mid = id;
		a.cls = this;
		a.onclick = function()
		{
			var dlg = new CDialog("Remove Member");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.mid = this.mid;
			dlg.confirmBox("Are you sure you want to remove this member?", "Remove Member");
			dlg.onConfirmOk = function()
			{
				this.cls.todelete[this.cls.todelete.length] = this.mid;
				for (var i = 0; i < this.cls.members.length; i++)
				{
					if (this.cls.members[i].id == this.mid)
						this.cls.members.splice(i);
				}

				this.rw.deleteRow();
			}
		}

		// Create name link if id
		var namelnk = alib.dom.createElement("a");
		namelnk.href = "javascript:void(0);";
		namelnk.mid = id;
		namelnk.cls = this;
		namelnk.onclick = function() { this.cls.editMilestone(this.mid); }
		namelnk.innerHTML = username;

		positionLbl = alib.dom.createElement("span");
		positionLbl.innerHTML = position_name;

		var positionCon = alib.dom.createElement("div");
		this.buildPositionDD(positionCon, positionLbl, id);

		rw.addCell(username);
		rw.addCell(positionLbl);
		rw.addCell(positionCon, false, "center");
		rw.addCell(a, false, "center");
	},

	printPosition:function(id, name)
	{
		if (!this.m_posTable)
		{
			this.m_posCon.innerHTML = "";

			this.m_posTable = new CToolTable("100%");
			this.m_posTable.addHeader("Name");
			this.m_posTable.addHeader("Remove", "center", "20px");
			this.m_posTable.print(this.m_posCon);
		}

		var rw = this.m_posTable.addRow();

		var a = alib.dom.createElement("a");
		a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		a.href = "javascript:void(0)";
		a.rw = rw;
		a.pid = id;
		a.cls = this;
		a.onclick = function()
		{
			var dlg = new CDialog("Remove Member");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.pid = this.pid;
			dlg.confirmBox("Are you sure you want to remove this position?", "Remove Position");
			dlg.onConfirmOk = function()
			{
				var funct = function(ret, cls, pid)
				{
					if (ret && ret!='-1')
					{
						for (var i = 0; i < cls.positions.length; i++)
						{
							if (cls.positions[i].id == pid)
								cls.positions.splice(i);
						}

						for (var i = 0; i < cls.members.length; i++)
						{
							if (cls.members[i].position_id == pid)
							{
								cls.members[i].position_id = "";
								cls.members[i].position_name = "";
							}
						}

						cls.buildInterface();
					}
				}

				//ALib.m_debug = true;
				//AJAX_TRACE_RESPONSE = true;
				var rpc = new CAjaxRpc("/project/xml_actions.php", "position_delete", 
										[["pid", this.pid]], funct, [this.cls, this.pid]);
			}
		}

		rw.addCell(name);
		rw.addCell(a, false, "center");
	},

	addMember:function()
	{
		this.newUniqId = (this.newUniqId) ? (this.newUniqId+1) : 0;

		var cbrowser = new CUserBrowser();
		cbrowser.appcls = this;
		cbrowser.onSelect = function(cid, name) 
		{
			//this.appcls.onSelect(cid, name);
			//this.appcls.m_lbl.innerHTML = name;
			var newid = "new"+this.appcls.newUniqId;
			this.appcls.members[this.appcls.members.length] = {id:newid, username:name, user_id:cid, position_name:"", position_id:""};
			this.appcls.printMember(newid, cid, name, "");
		}
		cbrowser.showDialog();		
	},

	buildPositionDD:function(con, con_lbl, cid)
	{
		var dm_act = new CDropdownMenu();

		dm_act.addEntry("None", function(cls, con_lbl, cid, name, type){ con_lbl.innerHTML = name; cls.setPosition(cid, name, type); }, 
						"/images/icons/tilde.gif", null, [this, con_lbl, cid, "", ""]);

		for (var i = 0; i < this.positions.length; i++)
		{
			dm_act.addEntry(this.positions[i].name, function(cls, con_lbl, cid, name, type){ con_lbl.innerHTML = name; cls.setPosition(cid, name, type); }, 
							"/images/icons/tilde.gif", null, [this, con_lbl, cid, this.positions[i].name, this.positions[i].id]);
		}

		con.appendChild(dm_act.createButtonMenu(""));
	},

	setPosition:function(cid, position_name, position_id)
	{
		for (var i = 0; i < this.members.length; i++)
		{
			if (this.members[i].id == cid)
			{
				this.members[i].position_id = position_id;
				this.members[i].position_name = position_name;
			}
		}
	},

	newPosition:function()
	{
		var dlg = new CDialog();
		dlg.promptBox("Position", "Name this position:", "", [this]);
		dlg.onPromptOk = function(name, cls)
		{
			if (name)
			{
				var funct = function(ret, cls, name)
				{
					if (ret && ret!='-1')
					{
						cls.positions[cls.positions.length] = {id:ret, name:name};
						cls.buildInterface();
					}
				}

				//ALib.m_debug = true;
				//AJAX_TRACE_RESPONSE = true;
				var rpc = new CAjaxRpc("/project/xml_actions.php", "position_add", 
										[["name", name], ["project_id", cls.mainObject.id]], funct, [cls, name]);
			}
		}
	}
}
