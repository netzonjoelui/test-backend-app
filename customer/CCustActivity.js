/****************************************************************************
*	
*	Class:		CCustActivity
*
*	Purpose:	Activity editor/viewer
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2009 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
var CUST_TYPE_CONTACT = 1;
var CUST_TYPE_ACCOUNT = 2;

function CCustActivity(activity_id)
{
	var aid = (activity_id) ? activity_id : null;
	this.m_aid = aid;
	this.type_id = null;;		// Set the initial type
	// Record unique ids, at least one of these must be set
	this.customer_id = null;	// Customer id
	this.lead_id = null;		// Lead id
	this.opportunity_id = null;	// Opportunity id
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Public functin for building interface.
**************************************************************************/
CCustActivity.prototype.showDialog = function()
{
	this.buildInterface();
}

/*************************************************************************
*	Function:	buildInterface
*
*	Purpose:	Display activity interface
**************************************************************************/
CCustActivity.prototype.buildInterface = function()
{
	// Title
	if (this.m_aid)
	{
		this.title = "Edit/View Activity";
	}
	else
	{
		this.title = "Enter New Activity";
	}

	var dlg = new CDialog(this.title);
	this.m_dlg = dlg;

	var dv = alib.dom.createElement("div");
	this.m_maindiv = dv;
	dv.innerHTML = "";

	var tbl = alib.dom.createElement("table", dv);
	alib.dom.styleSet(tbl, "width", "100%");
	tbl.cellPadding='0';
	tbl.cellSpacing='3';
	var tbody = alib.dom.createElement("tbody", tbl);

	// Subject
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Subject:";
	// Input
	var td_inp = alib.dom.createElement("td", row);
	this.txtSub = alib.dom.createElement("input", td_inp);
	alib.dom.styleSet(this.txtSub, "width", "100%");
	this.txtSub.maxLength = 32;

	// Type
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Type:";
	// Input
	this.dvSelCon = alib.dom.createElement("td", row);
	alib.dom.styleSet(this.dvSelCon, "margin", "0 3px 3px 58px");
	this.selTypes = alib.dom.createElement("select", this.dvSelCon);
	this.selTypes.dlgField = true; // selects are hidden by the dlg class - unhide
	this.getActivityTypes();

	// Direction
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Direction:";
	// Input
	this.dvDirCon = alib.dom.createElement("td", row);
	alib.dom.styleSet(this.dvDirCon, "margin", "0 3px 3px 58px");
	this.selDirection = alib.dom.createElement("select", this.dvDirCon);
	this.selDirection.dlgField = true; // selects are hidden by the dlg class - unhide
	this.selDirection[this.selDirection.length] = new Option("None", "", false, false);
	this.selDirection[this.selDirection.length] = new Option("Outgoing","o",  false, false);
	this.selDirection[this.selDirection.length] = new Option("Incoming", "i", false, false);

	// Date
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Date:";
	// Input
	var td_inp = alib.dom.createElement("td", row);
	this.txtDate = alib.dom.createElement("input", td_inp);
	alib.dom.styleSet(this.txtDate, "width", "75px");
	this.txtDate.value = this.getNowDateString();
	// Insert autocomplete
	var a_date = alib.dom.createElement("span", td_inp);
	a_date.innerHTML = "<img src='/images/calendar.gif' border='0'>";
	var start_ac = new CAutoCompleteCal(this.txtDate, a_date);

	// Time
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Time:";
	// Input
	var td_inp = alib.dom.createElement("td", row);
	this.txtTime = alib.dom.createElement("input", td_inp);
	alib.dom.styleSet(this.txtTime, "width", "75px");
	this.txtTime.value = this.getNowTimeString();
	var tobj = new CAutoCompleteTime(this.txtTime); // Autocomplete and time validation

	// Public
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Public:";
	// Input
	var td_inp = alib.dom.createElement("td", row);
	this.ckPublic = alib.dom.createElement("input");
	this.ckPublic.type = "checkbox";
	this.ckPublic.checked = true;
	td_inp.appendChild(this.ckPublic);

	// Notes
	// -----------------------------------------------
	var ndiv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(ndiv, "margin-right", "5px");
	alib.dom.styleSet(ndiv, "border", "0");
	this.taNotes = alib.dom.createElement("textarea", ndiv);
	alib.dom.styleSet(this.taNotes, "width", "100%");
	alib.dom.styleSet(this.taNotes, "height", "250px");
	alib.dom.styleSet(this.taNotes, "border", "1px solid");

	// Buttons
	// -----------------------------------------------
	var dv_btn = alib.dom.createElement("div", dv);
	var me = this;
	var btn = new CButton("Save", function(cls, dlg) { cls.save();  }, [me, me.m_dlg]);
	btn.print(dv_btn);
	this.btnSave = btn;

	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [me.m_dlg]);
	btn.print(dv_btn);

	dlg.customDialog(dv, 600, 420);

	if (this.m_aid)
		this.loadActivity();
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save changes or new activity to database
**************************************************************************/
CCustActivity.prototype.save = function()
{
	var args = [["name", this.txtSub.value], ["notes", this.taNotes.value], ["type_id", this.selTypes.value], ["direction", this.selDirection.value], 
				["time", this.txtTime.value], ["date", this.txtDate.value], ["f_public", (this.ckPublic.checked)?'t':'f']];
	if (this.m_aid)
		args[args.length] = ['aid', this.m_aid];
	if (this.lead_id)
		args[args.length] = ['lead_id', this.lead_id];
	if (this.opportunity_id)
		args[args.length] = ['opportunity_id', this.opportunity_id];
	if (this.customer_id)
		args[args.length] = ['customer_id', this.customer_id];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.m_dlg = this.m_dlg;
    ajax.onload = function(ret)
    {
        this.m_dlg.hide();
        this.cls.onSave(ret);
    };
    ajax.exec("/controller/Customer/activitySave", args);
}

/*************************************************************************
*	Function:	onSave
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CCustActivity.prototype.onSave = function(aid)
{
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CCustActivity.prototype.onCancel = function()
{
}


/*************************************************************************
*	Function:	getNowTimeString
*
*	Purpose:	Get HH:MM AM|PM string
**************************************************************************/
CCustActivity.prototype.getNowTimeString = function()
{
	var currentTime = new Date()
	var hours = currentTime.getHours()
	var minutes = currentTime.getMinutes()

	var suffix = "AM";
	if (hours >= 12) 
	{
		suffix = "PM";
		hours = hours - 12;
	}
	if (hours == 0) 
	{
		hours = 12;
	}

	if (minutes < 10)
		minutes = "0" + minutes;


	return  hours + ":" + minutes + " " + suffix;
}

/*************************************************************************
*	Function:	getNowDateString
*
*	Purpose:	Get mm/dd/yyyy string
**************************************************************************/
CCustActivity.prototype.getNowDateString = function()
{
	var currentTime = new Date()
	var month = currentTime.getMonth() + 1
	var day = currentTime.getDate()
	var year = currentTime.getFullYear()
	
	return  month + "/" + day + "/" + year;
}


/*************************************************************************
*	Function:	getActivityTypes
*
*	Purpose:	Get activity types via AJAX
**************************************************************************/
CCustActivity.prototype.getActivityTypes = function()
{   
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            if (ret.length)
            {
                for(types in ret)
                {
                    var currentType = ret[types];
                    this.cls.selTypes[cls.selTypes.length] = new Option(currentType.name, currentType.id, false, (this.cls.type_id==currentType.name)?true:false);
                }
            }
        }    
    };
    ajax.exec("/controller/Customer/getActivityTypes");
}

/*************************************************************************
*	Function:	loadActivity
*
*	Purpose:	Load activity details
**************************************************************************/
CCustActivity.prototype.loadActivity = function(aid)
{
	this.m_ajax = new CAjax();
	var me = this;
	this.m_ajax.m_ccls = me;
	this.m_ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		for (i = 0; i < num; i++)
		{
			var child = root.getChildNode(i);

			if (child.m_name == "activity")
			{
				var id = child.getChildNodeValByName("id");
				var name = child.getChildNodeValByName("name");
				var type_id = child.getChildNodeValByName("type_id");
				var direction = child.getChildNodeValByName("direction");
				var type_name = child.getChildNodeValByName("type_name");
				var time_entered = child.getChildNodeValByName("time_entered");
				var date_entered = child.getChildNodeValByName("date_entered");
				var f_public = child.getChildNodeValByName("public");
				var f_readonly = child.getChildNodeValByName("f_readonly");
				var notes = child.getChildNodeValByName("notes");
				var email_id = child.getChildNodeValByName("email_id");
				var opportunity_id = child.getChildNodeValByName("opportunity_id");
				var lead_id = child.getChildNodeValByName("lead_id");
				var customer_id = child.getChildNodeValByName("customer_id");

				if (opportunity_id)
					this.m_ccls.opportunity_id = opportunity_id;
				if (lead_id)
					this.m_ccls.lead_id = lead_id;
				if (customer_id)
					this.m_ccls.customer_id = customer_id;

				if (!name) name = "untitled";
				
				// Now check for read only
				if (f_readonly == 't')
				{
					this.m_ccls.txtSub.disabled = true;
					this.m_ccls.taNotes.disabled = true;
					this.m_ccls.txtTime.disabled = true;
					this.m_ccls.txtDate.disabled = true;
					this.m_ccls.ckPublic.disabled = true;
					this.m_ccls.selTypes.disabled = true;
					this.m_ccls.selDirection.disabled = true;
					this.m_ccls.btnSave.disable();
				}

				// Populate values
				this.m_ccls.txtSub.value = unescape(name);
				this.m_ccls.taNotes.value = unescape(notes);
				this.m_ccls.txtTime.value = unescape(time_entered);
				this.m_ccls.txtDate.value = unescape(date_entered);
				this.m_ccls.ckPublic.checked = (f_public == 't') ? true : false;

				if (type_id)
				{
					for( intIndex = 0; intIndex < this.m_ccls.selTypes.options.length; intIndex++ )
					{
						// Is this the ID we are looking for?
						if(this.m_ccls.selTypes.options[intIndex].value == type_id)
						{
							// Select it
							this.m_ccls.selTypes.selectedIndex = intIndex;
							// Yes, so stop searching
							break;
						}
					}
				}
				else
				{
					this.m_ccls.dvSelCon.innerHTML = type_name;
				}
				
				if (direction)
				{
					for( intIndex = 0; intIndex < this.m_ccls.selDirection.options.length; intIndex++ )
					{
						// Is this the ID we are looking for?
						if(this.m_ccls.selDirection.options[intIndex].value == direction)
						{
							this.m_ccls.selDirection.selectedIndex = intIndex;
							break;
						}
					}
				}

				if (email_id)
				{
					/*
					// Create email link
					var alnk = alib.dom.createElement("a");
					alnk.href = "javascript:void(0);";
					alnk.innerHTML = unescape(name);
					alnk.m_id = unescape(id);
					alnk.m_browseclass = this.m_browseclass;
					alnk.cid = id;
					alnk.onclick = function()
					{
						var params = 'top=200,left=100,width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
						window.open('/customer/edit_customer.awp?custid='+this.cid, 'cust_'+this.cid, params);
					}
					*/
				}

				// Populate fields
			}
		}
		if (!num)	
			this.m_ccls.m_maindiv.innerHTML = " Error loading activity";
	};

	var url = "/customer/xml_get_activities.php?";
	if (this.m_aid)
		url += "aid=" + this.m_aid;
	this.m_ajax.exec(url);
}
