{
	name:"milestones",
	title:"Milestones",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.milestones = new Array();
		this.todelete = new Array();
		this.relTypes = new Array();

		this.load();
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
				var saved_milestones = eval("("+unescape(ret)+")");
				for (var i = 0; i < saved_milestones.length; i++)
				{
					for (var j = 0; j < cls.milestones.length; j++)
					{
						// Set to actual id for new milestones
						if (cls.milestones[j].id == saved_milestones[i][0])
							cls.milestones[j].id = saved_milestones[i][1];
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
		for (var i = 0; i < this.milestones.length; i++)
		{
			args[args.length] = ["milestones[]", this.milestones[i].id];
			args[args.length] = ["m_name_"+this.milestones[i].id, this.milestones[i].name];
			args[args.length] = ["m_user_id_"+this.milestones[i].id, this.milestones[i].user_id];
			args[args.length] = ["m_deadline_"+this.milestones[i].id, this.milestones[i].deadline];
			args[args.length] = ["m_completed_"+this.milestones[i].id, this.milestones[i].f_completed];
		}

		ALib.m_debug = true;
		AJAX_TRACE_RESPONSE = true;
		var rpc = new CAjaxRpc("/project/xml_actions.php", "save_milestones", args, funct, [this], AJAX_POST);
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

		var funct = function(ret, cls)
		{
			cls.m_con.innerHTML = "";

			if (ret)
			{
				try
				{
					var tmp_milestones = eval("("+unescape(ret)+")");

					if (tmp_milestones)
					{
						for (var i = 0; i < tmp_milestones.length; i++)
						{
							cls.milestones[i] = tmp_milestones[i];
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
		var rpc = new CAjaxRpc("/project/xml_actions.php", "get_milestones", [["project_id", this.mainObject.id]], funct, [this]);
	},

	buildInterface:function()
	{
		this.m_relTable = null;
		this.m_con.innerHTML = "";

		var tb = new CToolbar();
		var btn = new CButton("Add Milestone", function(cls) { cls.editMilestone(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		tb.print(this.m_con);

		var p = alib.dom.createElement("p", this.m_con);
		alib.dom.styleSetClass(p, "notice");
		p.innerHTML = "Add notes about milestones";

		// Print milestones
		// --------------------------------------------------------
		var frm = new CWindowFrame("Milestones");
		this.m_relCon = frm.getCon();
		frm.print(this.m_con);

		if (!this.milestones.length)
			this.m_relCon.innerHTML = "No milestones have been set";
		else
		{
			for (var i = 0; i < this.milestones.length; i++)
			{
				this.addMilestone(this.milestones[i].id, this.milestones[i].name, this.milestones[i].username, 
										this.milestones[i].deadline, this.milestones[i].f_completed);
			}
		}
	},

	addMilestone:function(id, name, username, deadline, f_completed)
	{
		if (!this.m_relTable)
		{
			this.m_relCon.innerHTML = "";

			this.m_relTable = new CToolTable("100%");
			this.m_relTable.addHeader("Name");
			this.m_relTable.addHeader("Owner");
			this.m_relTable.addHeader("Deadline");
			this.m_relTable.addHeader("Completed", "center", "20px");
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
			var dlg = new CDialog("Remove Milestone");
			dlg.cls = this.cls;
			dlg.rw = this.rw;
			dlg.mid = this.mid;
			dlg.confirmBox("Are you sure you want to remove this milestone?", "Remove Milestone");
			dlg.onConfirmOk = function()
			{
				this.cls.todelete[this.cls.todelete.length] = this.mid;
				for (var i = 0; i < this.cls.milestones.length; i++)
				{
					if (this.cls.milestones[i].id == this.mid)
						this.cls.milestones.splice(i);
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
		namelnk.innerHTML = name;

		rw.addCell(namelnk);
		rw.addCell(username);
		rw.addCell(deadline);
		rw.addCell((f_completed=='t')?'yes':'no', false, "center");
		rw.addCell(a, false, "center");

	},

	editMilestone:function(id)
	{
		var mid = (id) ? id : null;
		var dlg = new CDialog(((id)?"Edit":"Create") + " Milestone");
		dlg.f_close = true;

		var ms_obj = null;

		if (id)
		{
			for (var i = 0; i < this.milestones.length; i++)
			{
				if (this.milestones[i].id == id)
					ms_obj = this.milestones[i];
			}
		}

		if (!ms_obj)
		{
			ms_obj = new Object();
			ms_obj.name = "New Milestone";
		}

		var dv = alib.dom.createElement("div");

		// Name
		var dv_hdr = alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		var dv_inp = alib.dom.createElement("div", dv);
		var inp = alib.dom.createElement("input", dv_inp);
		inp.ms_obj = ms_obj;
		inp.value = ms_obj.name;
		inp.onchange = function() { this.ms_obj.name = this.value; }


		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");

		var btn = new CButton("Ok", function(cls, dlg, mid, ms_obj) { cls.saveMilestone(mid, ms_obj); dlg.hide(); }, 
							  [this, dlg, mid, ms_obj], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 450);
	},

	saveMilestone:function(mid, ms_obj)
	{
		if (!mid)
		{
			this.newUniqId = (this.newUniqId) ? (this.newUniqId+1) : 0;
			ms_obj.id = "new" + this.newUniqId;
			this.milestones[this.milestones.length] = ms_obj;
		}

		this.buildInterface();
	}
}
