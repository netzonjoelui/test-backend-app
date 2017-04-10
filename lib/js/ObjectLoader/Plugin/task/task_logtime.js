{
	name:"logtime",
	title:"Time Logger",
	mainObject:null,
	toolbar:null,
	addtime:null,
	olCls:null, // object loader class reference

	main:function(con)
	{
		this.addtime = new Array();
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
				this.olCls.pluginAddToolbarEntry("Log Time Spent ", function(cbdata) { cbdata.cls.showDialog(); }, { cls:this });

			this.loaded = true;
		}
	},

	showDialog:function()
	{
		var dlg = new CDialog("Log Time Spent On This Task");
		dlg.f_close = true;

		var dv = alib.dom.createElement("div");

		// Add time spent form
		var dv_form = alib.dom.createElement("div", dv);
		var tbl = alib.dom.createElement("table", dv_form);
		var tbody = alib.dom.createElement("tbody", tbl);

		// Date
		var row = alib.dom.createElement("tr", tbody);
		var lbl = alib.dom.createElement("td", row);
		lbl.innerHTML = "Date: ";
		var inpCell = alib.dom.createElement("td", row);
		var inpDate = alib.dom.createElement("input");
		inpDate.type = "text";
		inpDate.size = 10;
		inpCell.appendChild(inpDate);
		var a_CalStart = alib.dom.createElement("span", inpCell);
		a_CalStart.innerHTML = "<img src='/images/calendar.gif' border='0'>";
		var start_ac = new CAutoCompleteCal(inpDate, a_CalStart);

		// Hours
		var row = alib.dom.createElement("tr", tbody);
		var lbl = alib.dom.createElement("td", row);
		lbl.innerHTML = " Hours: ";
		var inpCell = alib.dom.createElement("td", row);
		var inpHours = alib.dom.createElement("input");
		inpHours.type = "text";
		inpHours.size = 3;
		inpCell.appendChild(inpHours);

		// Description 
		var row = alib.dom.createElement("tr", tbody);
		var lbl = alib.dom.createElement("td", row);
		lbl.innerHTML = " Description: ";
		var inpCell = alib.dom.createElement("td", row);
		var inpDesc = alib.dom.createElement("input");
		inpDesc.type = "text";
		inpDesc.size = 42;
		inpCell.appendChild(inpDesc);

		// Done button
		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");
		var btn = new CButton("Add To Time Log", function(dlg, cls, frmval) { cls.addTimeLog(dlg, frmval); }, 
							  [dlg, this, {date:inpDate, hours:inpHours, desc:inpDesc}], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 450);
	},

	addTimeLog:function(dlg, frmval)
	{
		//this.olCls.refreshReferences("time");

		if (!frmval.date.value)
		{
			alert("Please enter the date before saving");
			return;
		}

		if (!frmval.hours.value)
		{
			alert("Please enter the number of hours worked on this task");
			return;
		}

		if (!frmval.desc.value)
		{
			alert("Please enter a short description");
			return;
		}
		
		dlg.hide();
        
        ajax = new CAjax('json');
        ajax.cls = this;
        ajax.onload = function(ret)
        {
            if (ret['error'])
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            else
                this.cls.olCls.setValue("cost_actual", ret);
        };
        var args = [["task_id", this.mainObject.id], 
                    ["name", frmval.desc.value], 
                    ["date_applied", frmval.date.value], 
                    ["hours", frmval.hours.value],];
        ajax.exec("/controller/Project/taskLogTime", args);        
	}
}
