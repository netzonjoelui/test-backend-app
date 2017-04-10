/*======================================================================================
	
	Module:		CRecurrencePattern

	Purpose:	Handles Recurrence Pattern

======================================================================================*/
function CRecurrencePattern(rpid)
{
	this.type = 0; // None
	this.id = 0;
	this.save_type = "all"; // exception | all
	this.interval = 1; 		
	this.dateStart = "";
	this.dateEnd = "";
	this.timeStart = null;
	this.timeEnd = null;
	this.fAllDay = null;		
	this.dayOfMonth = 1;
	this.monthOfYear = 1;
	this.dayOfWeekMask = null;		
	this.duration = null; 			
	this.instance = null;
				
	this.fActive = true;	
	this.object_type_id = null;	
	this.object_type = null;		
	this.parentId = null;	
	this.calendarId = null;		
	this.dateProcessedTo = null;	
	this.id = null;				
	this.useId = null;				
	this.fieldDateStart = null;	
	this.fieldTimeStart = null; 	
	this.fieldDateEnd = null;		
	this.fieldTimeEnd = null;		
	this.arrChangeLog = new Array();
	
	this.day1 = 'f';
	this.day2 = 'f';
	this.day3 = 'f';
	this.day4 = 'f';
	this.day5 = 'f';
	this.day6 = 'f';
	this.day7 = 'f';
	
	this.dbFieldValues = null;
	
	this.humanDesc = '';
	
	this.dbvalLoaded = false;
	this.dbvalLoadCount = null;

	this.loadedFromArray = false;
	
	this.m_dlgElements = new Object();
	
	if(rpid)
	{
		this.load(rpid);
	}
}

/**************************************************************************
 * Function: 	fromArray
 *
 * Purpose:		Import the values from array
 **************************************************************************/
CRecurrencePattern.prototype.fromArray = function(recurrencePattern)
{
	this.id = recurrencePattern.id;
	this.type = recurrencePattern.recur_type;
	this.interval = recurrencePattern.interval;
	this.dateStart = recurrencePattern.date_start;
	this.dateEnd = recurrencePattern.date_end;
	this.dayOfMonth = recurrencePattern.day_of_month;
	this.monthOfYear = recurrencePattern.month_of_year;
	this.dayOfWeekMask = recurrencePattern.day_of_week_mask;
	this.fActive = recurrencePattern.f_active;
	this.object_type = recurrencePattern.obj_type;

	// Setup the days of week
	if (this.dayOfWeekMask) {
		var weekdays = {
			day1: 1,
			day2: 2,
			day3: 4,
			day4: 8,
			day5: 16,
			day6: 32,
			day7: 64
		}

		this.day1 = ((this.dayOfWeekMask & weekdays.day1).toString() === "0") ? "f" : "t";
		this.day2 = ((this.dayOfWeekMask & weekdays.day2).toString() === "0") ? "f" : "t";
		this.day3 = ((this.dayOfWeekMask & weekdays.day3).toString() === "0") ? "f" : "t";
		this.day4 = ((this.dayOfWeekMask & weekdays.day4).toString() === "0") ? "f" : "t";
		this.day5 = ((this.dayOfWeekMask & weekdays.day5).toString() === "0") ? "f" : "t";
		this.day6 = ((this.dayOfWeekMask & weekdays.day6).toString() === "0") ? "f" : "t";
		this.day7 = ((this.dayOfWeekMask & weekdays.day7).toString() === "0") ? "f" : "t";
	}

	this.loadedFromArray = true;
}

/**************************************************************************
 * Function: 	setRecurrenceRules
 *
 * Purpose:		Set the recurrence rules for the recurrence pattern
 **************************************************************************/
CRecurrencePattern.prototype.setRecurrenceRules = function(recurrenceRules)
{
	this.fieldDateStart = recurrenceRules.fieldDateStart;
	this.fieldTimeStart = recurrenceRules.fieldTimeStart;
	this.fieldDateEnd = recurrenceRules.fieldDateEnd;
	this.fieldTimeEnd = recurrenceRules.fieldTimeEnd;
}


/**************************************************************************
* Function: 	load	
*
* Purpose:		Load recurrence pattern from backend
**************************************************************************/
CRecurrencePattern.prototype.load = function(rpid)
{
	if (rpid) 
	{
		this.id = rpid;

		this.dbvalLoaded = false;
		this.dbvalLoadCount = 1;
		
		var ajax = new CAjax();
		ajax.m_obj = this;
		ajax.onload = function(root)
		{
			if (root.getNumChildren())
			{
				this.m_obj.dbFieldValues = JSON.parse(unescape(root.getChildNodeValByName("objpt_json")));
	
				this.m_obj.type = this.m_obj.dbFieldValues.type;
				this.m_obj.interval = this.m_obj.dbFieldValues.interval; 		
				this.m_obj.dateStart = this.m_obj.dbFieldValues.dateStart; 		
				this.m_obj.dateEnd = this.m_obj.dbFieldValues.dateEnd; 			
				this.m_obj.timeStart = this.m_obj.dbFieldValues.timeStart;		
				this.m_obj.timeEnd = this.m_obj.dbFieldValues.timeEnd;		
				this.m_obj.fAllDay = this.m_obj.dbFieldValues.fAllDay; 			
				this.m_obj.dayOfMonth = this.m_obj.dbFieldValues.dayOfMonth;
				this.m_obj.monthOfYear = this.m_obj.dbFieldValues.monthOfYear;
				this.m_obj.dayOfWeekMask = this.m_obj.dbFieldValues.dayOfWeekMask;		
				this.m_obj.duration = this.m_obj.dbFieldValues.duration; 			
				this.m_obj.instance = this.m_obj.dbFieldValues.instance; 	
						
				this.m_obj.fActive = this.m_obj.dbFieldValues.fActive;	
				this.m_obj.object_type_id = this.m_obj.dbFieldValues.object_type_id;	
				this.m_obj.object_type = this.m_obj.dbFieldValues.object_type;		
				this.m_obj.parentId = this.m_obj.dbFieldValues.parentId;	
				this.m_obj.calendarId = this.m_obj.dbFieldValues.calendarId;		
				this.m_obj.dateProcessedTo = this.m_obj.dbFieldValues.dateProcessedTo;	
				this.m_obj.id = this.m_obj.dbFieldValues.id;				
				this.m_obj.useId = this.m_obj.dbFieldValues.useId;				
				this.m_obj.fieldDateStart = this.m_obj.dbFieldValues.fieldDateStart;	
				this.m_obj.fieldTimeStart = this.m_obj.dbFieldValues.fieldTimeStart; 	
				this.m_obj.fieldDateEnd = this.m_obj.dbFieldValues.fieldDateEnd;		
				this.m_obj.fieldTimeEnd = this.m_obj.dbFieldValues.fieldTimeEnd;		
				this.m_obj.arrChangeLog = this.m_obj.dbFieldValues.arrChangeLog; 
				
				this.m_obj.day1 = this.m_obj.dbFieldValues.day1;
				this.m_obj.day2 = this.m_obj.dbFieldValues.day2;
				this.m_obj.day3 = this.m_obj.dbFieldValues.day3;
				this.m_obj.day4 = this.m_obj.dbFieldValues.day4;
				this.m_obj.day5 = this.m_obj.dbFieldValues.day5;
				this.m_obj.day6 = this.m_obj.dbFieldValues.day6;
				this.m_obj.day7 = this.m_obj.dbFieldValues.day7;
				
				this.m_obj.dbvalLoaded = true;
				this.m_obj.onchange();
				this.m_obj.onload();
			}
			
		};
	
		var url = "/objects/xml_get_rp.php?rpid=" + this.id;
		ajax.exec(url);
		
	}
	
}

/**************************************************************************
* Function: 	onload	
*
* Purpose:		Fired once the recurrence pattern has been fully loaded
**************************************************************************/
CRecurrencePattern.prototype.onload = function()
{
}

/**************************************************************************
* Function: 	set	
*
* Purpose:		Set will populate partern variables from the form
**************************************************************************/
CRecurrencePattern.prototype.set = function()
{
	this.onchange();
}

/**************************************************************************
* Function: 	onchange
*
* Purpose:		Function will be fired when pattern changes
**************************************************************************/
CRecurrencePattern.prototype.onchange = function()
{
}

/**************************************************************************
* Function: 	onsave
*
* Purpose:		Function will be fired when pattern save is finished
*
* Params:		(bool) ret : true = success, false = fail
**************************************************************************/
CRecurrencePattern.prototype.onsave = function(ret)
{
}

/**************************************************************************
 * DEPRICATED - The saving of recurrence pattern is now included in CAntObject.js::save()
 *
 * Function: 	save
 *
 * Purpose:		Save recurrence pattern to backend
 **************************************************************************/
CRecurrencePattern.prototype.save = function(silent)
{
	var obj = new Object();
	
	obj.type = this.type;
	obj.object_type = this.object_type;
	obj.object_type_id = this.object_type_id;
	obj.interval = this.interval;
	obj.dateStart = this.dateStart;
	obj.dateEnd = this.dateEnd;
	obj.timeStart = this.timeStart;
	obj.timeEnd = this.timeEnd;
	obj.fAllDay = this.fAllDay;
	obj.dayOfMonth = this.dayOfMonth;
	obj.monthOfYear = this.monthOfYear;
	obj.dayOfWeekMask = this.dayOfWeekMask;
	obj.instance = this.instance;
	
	obj.object_type_id = this.object_type_id;
	obj.object_type = this.object_type;
	obj.parentId = this.parentId;
	obj.calendarId = this.calendarId;
	obj.dateProcessedTo = this.dateProcessedTo;
	obj.id = this.id;
	obj.day1 = this.day1;
	obj.day2 = this.day2;
	obj.day3 = this.day3;
	obj.day4 = this.day4;
	obj.day5 = this.day5;
	obj.day6 = this.day6;
	obj.day7 = this.day7;
    
    /*var funct = function(ret, cls)
    {     
        // close box
        if( cls.m_dlg )
        { 
            cls.m_dlg.hide();
            ALib.statusShowAlert("Recurrence Saved!", 3000, "bottom", "right");
        }
        if( cls.m_dlg_saving ) cls.m_dlg_saving.hide(); 
        cls.onsave();
    }
	var args = 	[['objpt_json',JSON.stringify(obj)]];
    var rpc = new CAjaxRpc("/controller/Object/saveRecurrencepattern", "saveRecurrencepattern", args, funct, [this], AJAX_POST, true, "json");*/
	
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(this.cbData.cls.m_dlg )
        { 
            this.cbData.cls.m_dlg.hide();
            ALib.statusShowAlert("Recurrence Saved!", 3000, "bottom", "right");
        }
        if( this.cbData.cls.m_dlg_saving ) this.cbData.cls.m_dlg_saving.hide(); 
        this.cbData.cls.onsave();
    };
    var args = [['objpt_json',JSON.stringify(obj)]];
    ajax.exec("/controller/Object/saveRecurrencepattern", args);
}


CRecurrencePattern.prototype.showDialog = function()
{
	var me = this;
	
	if( this.id>0 && !this.loadedFromArray)
	{
		// load values
		if( !this.dbvalLoaded )
		{
			if( this.dlgLoadFromDB==null )
			{
				// Create loading div
				var dlgLoadFromDB = new CDialog();
				var dv_load = document.createElement('div');
				alib.dom.styleSetClass(dv_load, "statusAlert");
				alib.dom.styleSet(dv_load, "text-align", "center");
				alib.dom.styleSet(dv_load, "text-align", "center");
				dv_load.innerHTML = "Loading, please wait...";
				dlgLoadFromDB.statusDialog(dv_load);
			
				this.dlgLoadFromDB = dlgLoadFromDB;
			}
			
			if( this.dbvalLoadCount==null )
			{
				this.load();
			}
			else
			{
				this.dbvalLoadCount++;
			}

			if( this.dbvalLoadCount<100 )
			{
				var me = this;
				setTimeout(function(){ me.showDialog(); },100);
				return false;
			}
		}else{
			if( this.dlgLoadFromDB!=null )
			{
				this.dlgLoadFromDB.hide();
				this.dlgLoadFromDB=null;
			}
		}
	}
	
	// save old values
	if( this.dbFieldValues == null )
		this.dbFieldValues = new Object();
		
	this.dbFieldValues.type = this.type;
	this.dbFieldValues.interval = this.interval; 		
	this.dbFieldValues.dateStart = this.dateStart; 		
	this.dbFieldValues.dateEnd = this.dateEnd; 			
	this.dbFieldValues.timeStart = this.timeStart;		
	this.dbFieldValues.timeEnd = this.timeEnd;		
	this.dbFieldValues.fAllDay = this.fAllDay; 			
	this.dbFieldValues.dayOfMonth = this.dayOfMonth;
	this.dbFieldValues.monthOfYear = this.monthOfYear;
	this.dbFieldValues.dayOfWeekMask = this.dayOfWeekMask;		
	this.dbFieldValues.duration = this.duration; 			
	this.dbFieldValues.instance = this.instance; 	
			
	this.dbFieldValues.fActive = this.fActive;	
	this.dbFieldValues.object_type_id = this.object_type_id;	
	this.dbFieldValues.object_type = this.object_type;		
	this.dbFieldValues.parentId = this.parentId;	
	this.dbFieldValues.calendarId = this.calendarId;		
	this.dbFieldValues.dateProcessedTo = this.dateProcessedTo;	
	this.dbFieldValues.id = this.id;				
	this.dbFieldValues.useId = this.useId;				
	this.dbFieldValues.fieldDateStart = this.fieldDateStart;	
	this.dbFieldValues.fieldTimeStart = this.fieldTimeStart; 	
	this.dbFieldValues.fieldDateEnd = this.fieldDateEnd;		
	this.dbFieldValues.fieldTimeEnd = this.fieldTimeEnd;		
	this.dbFieldValues.arrChangeLog = this.arrChangeLog; 
	
	this.dbFieldValues.day1 = this.day1;
	this.dbFieldValues.day2 = this.day2;
	this.dbFieldValues.day3 = this.day3;
	this.dbFieldValues.day4 = this.day4;
	this.dbFieldValues.day5 = this.day5;
	this.dbFieldValues.day6 = this.day6;
	this.dbFieldValues.day7 = this.day7;
	
	
	if (this.id)
	{
		this.title = "Edit/View Recurrence";
	}
	else
	{
		this.title = "Enter New Recurrence";
	}
	
	var dlg = new CDialog(this.title);
	this.m_dlg = dlg;
	
	var dv = alib.dom.createElement("div");
	
	
	var frm1 = new CWindowFrame("Recurrence pattern", null, "3px");
	frm1.print(dv);
	var frmcon = frm1.getCon();
	
	var dvfield = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(dvfield, "margin-top", "3px");
	var td = alib.dom.createElement("div", dvfield);
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "120px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSet(td, "margin-left", "3px");
	td.innerHTML = "Repeats: ";
	
	var td = alib.dom.createElement("div", dvfield);
	var sel = alib.dom.createElement("select", td);
	sel.rpcls = this;
	sel[sel.length] = new Option("Does Not Repeat", "0", false, (this.type == "0")?true:false);
	sel[sel.length] = new Option("Daily", "1", false, (this.type == "1")?true:false);
	sel[sel.length] = new Option("Weekly", "2", false, (this.type == "2")?true:false);
	sel[sel.length] = new Option("Monthly", "m", false, (this.type == "3" || this.type == "4")?true:false);
	sel[sel.length] = new Option("Yearly", "y", false, (this.type == "5" || this.type == "6")?true:false);
	sel.onchange = function() 
	{ 
		if( this.value=='m' )
		{
		
			if( this.rpcls.type==0 || (this.rpcls.interval>0 && this.rpcls.dayOfMonth>0) ){
				this.rpcls.type = '3'; // monthly
			}
			else
			{
				this.rpcls.type = '4'; // monthnth
			}			
		}
		else if( this.value=='y' )
		{
			if(this.rpcls.type==0 || (this.rpcls.interval>0 && this.rpcls.dayOfMonth>0) ){
				this.rpcls.type = '5'; // yearly
			}
			else
			{
				this.rpcls.type = '6'; // yearnth
			}			
		}
		else
		{
			this.rpcls.type = this.value; 
		}
		
		this.rpcls.showRecurring(this.rpcls.type); 
	}
	

	
	this.m_dlgElements.dv_range = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(this.m_dlgElements.dv_range, "margin-left", "4px");
	alib.dom.styleSet(this.m_dlgElements.dv_range, "margin-top", "6px");

	
	
	var frm1 = new CWindowFrame("Range of recurrence", null, "3px");
	frm1.print(dv);
	var frmcon = frm1.getCon();
	
	// Start Date
	// ------------------------------------------------------------------
	var dvfield = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(dvfield, "margin-top", "3px");
	var td = alib.dom.createElement("div", dvfield);
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "120px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSet(td, "margin-left", "3px");
	td.innerHTML = "Start Date: ";
	var td = alib.dom.createElement("div", dvfield);
	alib.dom.styleSet(td, "margin-left", "55px");
	var txtDateStart = alib.dom.createElement("input");
	txtDateStart.type = "text";
	td.appendChild(txtDateStart);
	alib.dom.styleSet(txtDateStart, "width", "100px");
	
	if( !this.dateStart )
	{
		var d = new Date();	
		this.dateStart = (d.getMonth()+1)+"/"+d.getDate()+"/"+d.getFullYear();
	}
	
	txtDateStart.value = this.dateStart;
	txtDateStart.rpcls = this;
	txtDateStart.onchange = function() {  this.rpcls.dateStart = this.value;  }
	// Insert autocomplete
	var a_CalStart = alib.dom.createElement("span", td);
	a_CalStart.innerHTML = "<img src='/images/calendar.gif' border='0' />";
	var start_rng_ac = new CAutoCompleteCal(txtDateStart, a_CalStart);
	

	// End Date
	// ------------------------------------------------------------------
	var dvfield = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(dvfield, "margin-top", "3px");
	var td = alib.dom.createElement("div", dvfield);
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "120px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSet(td, "margin-left", "3px");
	td.innerHTML = "End Date: ";
	var td = alib.dom.createElement("div", dvfield);
	alib.dom.styleSet(td, "margin-left", "55px");
	var txtEndDate = alib.dom.createElement("input");
	txtEndDate.type = "text";
	td.appendChild(txtEndDate);
	alib.dom.styleSet(txtEndDate, "width", "100px");
	if (!this.dateEnd && this.type) 
	{
		txtEndDate.value = "Never";
		txtEndDate.disabled = true;
	}
	else if (this.dateEnd) 
		txtEndDate.value = this.dateEnd;
	else if (!this.type) 
	{
		txtEndDate.value = "Never";
		txtEndDate.disabled = true;
	}
	txtEndDate.onchange = function() { me.dateEnd = this.value; }
	// Insert autocomplete
	var a_CalEnd = alib.dom.createElement("span", td);
	a_CalEnd.innerHTML = "<img src='/images/calendar.gif' border='0'>";
	var end_ac = new CAutoCompleteCal(txtEndDate, a_CalEnd);
	
	// Never ends
	// ------------------------------------------------------------------
	var never_dv = alib.dom.createElement("div", frmcon);
	alib.dom.styleSet(never_dv, "padding-left", "124px");
	var never_lbl = alib.dom.createElement("span", never_dv);
	never_lbl.innerHTML = "Never ends ";
	var never_chk = alib.dom.createElement("input");
	never_chk.type = 'checkbox';
	never_chk.m_end_date = txtEndDate;
	never_dv.appendChild(never_chk);
	never_chk.checked = (!me.dateEnd) ? true :  false;
	never_chk.onclick = function()
	{
		if (this.checked)
		{
			this.m_end_date.value = "Never";
			this.m_end_date.disabled = true;
			me.dateEnd = "";
		}
		else
		{
			this.m_end_date.value = me.dateStart;
			me.dateEnd = me.dateStart;
			this.m_end_date.disabled = false;
		}
	}


	// Buttons
	// -----------------------------------------------
	var dv_btn = alib.dom.createElement("div", dv);
	dv_btn.id = 'rp_dialog_buttons';
	
	var btn = new CButton("OK", 
		function(cls, dlg) 
		{ 
			me.set();
			cls.m_dlg.hide();
		}, 
		[me, me.m_dlg]);
		
	btn.print(dv_btn);
	this.btnSave = btn;

	var btn = new CButton("Cancel", function(dlg) {  
		
		me.type = me.dbFieldValues.type;
		me.interval = me.dbFieldValues.interval; 		
		me.dateStart = me.dbFieldValues.dateStart; 		
		me.dateEnd = me.dbFieldValues.dateEnd; 			
		me.timeStart = me.dbFieldValues.timeStart;		
		me.timeEnd = me.dbFieldValues.timeEnd;		
		me.fAllDay = me.dbFieldValues.fAllDay; 			
		me.dayOfMonth = me.dbFieldValues.dayOfMonth;
		me.monthOfYear = me.dbFieldValues.monthOfYear;
		me.dayOfWeekMask = me.dbFieldValues.dayOfWeekMask;		
		me.duration = me.dbFieldValues.duration; 			
		me.instance = me.dbFieldValues.instance; 	
				
		me.fActive = me.dbFieldValues.fActive;	
		me.object_type_id = me.dbFieldValues.object_type_id;	
		me.object_type = me.dbFieldValues.object_type;		
		me.parentId = me.dbFieldValues.parentId;	
		me.calendarId = me.dbFieldValues.calendarId;		
		me.dateProcessedTo = me.dbFieldValues.dateProcessedTo;	
		me.id = me.dbFieldValues.id;				
		me.useId = me.dbFieldValues.useId;				
		me.fieldDateStart = me.dbFieldValues.fieldDateStart;	
		me.fieldTimeStart = me.dbFieldValues.fieldTimeStart; 	
		me.fieldDateEnd = me.dbFieldValues.fieldDateEnd;		
		me.fieldTimeEnd = me.dbFieldValues.fieldTimeEnd;		
		me.arrChangeLog = me.dbFieldValues.arrChangeLog; 
		
		me.day1 = me.dbFieldValues.day1;
		me.day2 = me.dbFieldValues.day2;
		me.day3 = me.dbFieldValues.day3;
		me.day4 = me.dbFieldValues.day4;
		me.day5 = me.dbFieldValues.day5;
		me.day6 = me.dbFieldValues.day6;
		me.day7 = me.dbFieldValues.day7;
		
		dlg.hide(); 
	
	}, [me.m_dlg]);
	btn.print(dv_btn);
		
	
	dlg.customDialog(dv, 320, null);
	alib.dom.styleSet(sel, "visibility", "visible");
	
	
	// show range
	this.showRecurring(this.type);
	
	
	// temporary, show all select 
 	var selects = document.getElementsByTagName("select");
    for (var i=0; i<selects.length; i++)
    	selects[i].style.visivility = "visible";
}

CRecurrencePattern.prototype.showRecurring = function(type)
{
	type = parseInt(type);
	var me = this;

	
	this.m_dlgElements.dv_range.innerHTML = "";
	
	
	var dvfield = alib.dom.createElement("div", this.m_dlgElements.dv_range);
	switch (type)
	{
	case 1: // daily
	
		dvfield.style.display = "block";
		var lbl = alib.dom.createElement("span", dvfield);
		lbl.innerHTML = "Every ";
		var inp = alib.dom.createElement("input", dvfield);
		inp.size = 2;
		inp.value = (me.interval) ? me.interval : 1;
		inp.onchange = function()
		{
			me.interval = this.value;
		}
		var lbl = alib.dom.createElement("span", dvfield);
		lbl.innerHTML = " days";
		break;
		
	case 2: // weekly

		dvfield.style.display = "block";
		var lbl = alib.dom.createElement("span", dvfield);
		lbl.innerHTML = "Every ";
		var inp = alib.dom.createElement("input", dvfield);
		inp.size = 2;
		inp.value = (me.interval) ? me.interval : 1;
		inp.onchange = function()
		{
			me.interval = this.value;
		}
		var lbl = alib.dom.createElement("span", dvfield);
		lbl.innerHTML = " week(s) on: <br />";

		// Day 1
		var row = alib.dom.createElement("div", dvfield);
		var daychk1 = alib.dom.createElement("input");
		daychk1.type = "checkbox";
		row.appendChild(daychk1);
		daychk1.checked = (me.day1 == 't') ? true : false;
		daychk1.onchange = function()
		{
			me.day1 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Sunday";
		lbl.onclick = function()
		{
			daychk1.checked = daychk1.checked ? false : true;
			me.day1 = (daychk1.checked) ? 't' : 'f';
		}

		// Day 2
		var row = alib.dom.createElement("div", dvfield);
		var daychk2 = alib.dom.createElement("input");
		daychk2.type = "checkbox";
		row.appendChild(daychk2);
		daychk2.checked = (me.day2 == 't') ? true : false;
		daychk2.onchange = function()
		{
			me.day2 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Monday";
		lbl.onclick = function()
		{
			daychk2.checked = daychk2.checked ? false :true;
			me.day2 = (daychk2.checked) ? 't' : 'f';
		}

		// Day 3
		var row = alib.dom.createElement("div", dvfield);
		var daychk3 = alib.dom.createElement("input");
		daychk3.type = "checkbox";
		row.appendChild(daychk3);
		daychk3.checked = (me.day3 == 't') ? true : false;
		daychk3.onchange = function()
		{
			me.day3 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Tuesday";
		lbl.onclick = function()
		{
			daychk3.checked = daychk3.checked ? false :true;
			me.day3 = (daychk3.checked) ? 't' : 'f';
		}

		// Day 4
		var row = alib.dom.createElement("div", dvfield);
		var daychk4 = alib.dom.createElement("input");
		daychk4.type = "checkbox";
		row.appendChild(daychk4);
		daychk4.checked = (me.day4 == 't') ? true : false;
		daychk4.onchange = function()
		{
			me.day4 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Wednesday";
		lbl.onclick = function()
		{
			daychk4.checked = daychk4.checked ? false :true;
			me.day4 = (daychk4.checked) ? 't' : 'f';
		}

		// Day 5
		var row = alib.dom.createElement("div", dvfield);
		var daychk5 = alib.dom.createElement("input");
		daychk5.type = "checkbox";
		row.appendChild(daychk5);
		daychk5.checked = (me.day5 == 't') ? true : false;
		daychk5.onchange = function()
		{
			me.day5 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Thursday";
		lbl.onclick = function()
		{
			daychk5.checked = daychk5.checked ? false :true;
			me.day5 = (daychk5.checked) ? 't' : 'f';
		}

		// Day 6
		var row = alib.dom.createElement("div", dvfield);
		var daychk6 = alib.dom.createElement("input");
		daychk6.type = "checkbox";
		row.appendChild(daychk6);
		daychk6.checked = (me.day6 == 't') ? true : false;
		daychk6.onchange = function()
		{
			me.day6 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Friday";
		lbl.onclick = function()
		{
			daychk6.checked = daychk6.checked ? false :true;
			me.day6 = (daychk6.checked) ? 't' : 'f';
		}

		// Day 7
		var row = alib.dom.createElement("div", dvfield);
		var daychk7 = alib.dom.createElement("input");
		daychk7.type = "checkbox";
		row.appendChild(daychk7);
		daychk7.checked = (me.day7 == 't') ? true : false;
		daychk7.onchange = function()
		{
			me.day7 = (this.checked) ? 't' : 'f';
		}
		var lbl = alib.dom.createElement("label", row);
		lbl.innerHTML = " Saturday";
		lbl.onclick = function()
		{
			daychk7.checked = daychk7.checked ? false :true;
			me.day7 = (daychk7.checked) ? 't' : 'f';
		}

		break;
		
	case 3: // monthly
	case 4: // monthnth
		
		dvfield.style.display = "block";
		var tbl = alib.dom.createElement("table", dvfield);
		var tbody = alib.dom.createElement("tbody", tbl);
	
		var row = alib.dom.createElement("row", tbody);
		var td = alib.dom.createElement("td", row);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name='monthly_type';
		rbtn1.value='day';
		rbtn1.rpcls = this;
		rbtn1.onchange = function()
		{  
			this.rpcls.type = 3; // monthly
			this.rpcls.recurToggleMontlyType(); 
		}
		td.appendChild(rbtn1);
		//  day
		var td = alib.dom.createElement("td", row);
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = "Day ";
		this.m_dlgElements.rt_m_d_day = alib.dom.createElement("input");
		this.m_dlgElements.rt_m_d_day.style.width = "20px";
	
		this.m_dlgElements.rt_m_d_day.value = (me.dayOfMonth) ? me.dayOfMonth : 1;
		this.m_dlgElements.rt_m_d_day.onchange = function()
		{
			me.dayOfMonth = this.value;
		}
		td.appendChild(this.m_dlgElements.rt_m_d_day);
		
		// interval
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = " of every ";
		this.m_dlgElements.rt_m_d_int = alib.dom.createElement("input");
		this.m_dlgElements.rt_m_d_int.style.width = "20px";
		if (me.interval)
		{
			this.m_dlgElements.rt_m_d_int.value = me.interval;
		}
		else
		{
			this.m_dlgElements.rt_m_d_int.value = 1;
			me.interval = 1;
		}
		this.m_dlgElements.rt_m_d_int.onchange = function()
		{
			me.interval = this.value;
		}
		td.appendChild(this.m_dlgElements.rt_m_d_int);
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = " month(s)";

		
		var row = alib.dom.createElement("row", tbody);
		var td = alib.dom.createElement("td", row);

		var rbtn2 = alib.dom.createElement("input");
		rbtn2.type='radio';
		rbtn2.name='monthly_type';
		rbtn2.value='relative';
		rbtn2.rpcls = this;
		rbtn2.onchange = function()
		{  
			this.rpcls.type = 4; // monthth
			this.rpcls.recurToggleMontlyType(); 			 
		}
		td.appendChild(rbtn2);
		rbtn2.checked= true;
		
		
		// monthlynth elements
		var td = alib.dom.createElement("td", row);
		// Relative Type
		this.m_dlgElements.rt_m_rel_nth = alib.dom.createElement("select", td);
		var vals = new Array("First", "Second", "Third", "Fourth", "Last");
		for (var i = 0; i < vals.length; i++)
			this.m_dlgElements.rt_m_rel_nth[this.m_dlgElements.rt_m_rel_nth.length] = new Option("The " + vals[i], (i+1), false, (me.instance == (i + 1))?true:false);
		this.m_dlgElements.rt_m_rel_nth.onchange = function()
		{
			me.instance = this.value;
		}
		
		// Relavtive Section or Day
		var lbl = alib.dom.createElement("br", td);
		this.m_dlgElements.rt_m_day_nth = alib.dom.createElement("select", td);

		var vals = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
		var sel_selected = false;
		for (var i = 0; i < vals.length; i++)
		{
			sel_selected = false;
			switch(i)
			{

				case 0:	sel_selected = (me.day1 == 't')?true:false; break;
				case 1:	sel_selected = (me.day2 == 't')?true:false; break;
				case 2:	sel_selected = (me.day3 == 't')?true:false; break;
				case 3:	sel_selected = (me.day4 == 't')?true:false; break;
				case 4:	sel_selected = (me.day5 == 't')?true:false; break;
				case 5:	sel_selected = (me.day6 == 't')?true:false; break;
				case 6:	sel_selected = (me.day7 == 't')?true:false; break;
			}
			this.m_dlgElements.rt_m_day_nth[this.m_dlgElements.rt_m_day_nth.length] = new Option(vals[i], (i+1), false, sel_selected);
		}
		this.m_dlgElements.rt_m_day_nth.onchange = function()
		{
			me.day1 = (this.value == 1)?'t':'f';
			me.day2 = (this.value == 2)?'t':'f';
			me.day3 = (this.value == 3)?'t':'f';
			me.day4 = (this.value == 4)?'t':'f';
			me.day5 = (this.value == 5)?'t':'f';
			me.day6 = (this.value == 6)?'t':'f';
			me.day7 = (this.value == 7)?'t':'f';
		}
		
		// interval monthlynth
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = "<br />of every ";
		this.m_dlgElements.rt_m_d_int_nth = alib.dom.createElement("input");
		this.m_dlgElements.rt_m_d_int_nth.style.width = "20px";
	
		if (me.interval)
		{
			this.m_dlgElements.rt_m_d_int_nth.value = me.interval;
		}
		else
		{
			this.m_dlgElements.rt_m_d_int_nth.value = 1;
			me.interval = 1;
		}
		this.m_dlgElements.rt_m_d_int_nth.onchange = function()
		{
			me.interval = this.value;
		}
		td.appendChild(this.m_dlgElements.rt_m_d_int_nth);
		
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = " month(s)";

		if (this.type == 3 )
		{
			this.recurToggleMontlyType();
			rbtn1.checked = true;
		}
		else
		{
			this.recurToggleMontlyType();
			rbtn2.checked = true;
		}

		break;
	case 5: // yearly
	case 6: // yearnth
		
		dvfield.style.display = "block";

		var months = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

		var tbl = alib.dom.createElement("table", dvfield);
		var tbody = alib.dom.createElement("tbody", tbl);

		
		var row = alib.dom.createElement("row", tbody);
		var td = alib.dom.createElement("td", row);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = "yearly_type";
		rbtn1.rpcls = this;
		rbtn1.onchange = function() 
		{  
			this.rpcls.type = 5;
			this.rpcls.recurToggleYearlyType(); 
		}
		td.appendChild(rbtn1);
		//  Specific Day of the moonth
		var td = alib.dom.createElement("td", row);
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = "Every ";
		this.m_dlgElements.rt_y_m = alib.dom.createElement("select", td);
		this.m_dlgElements.rt_y_m.id = "rt_y_m";
		for (var i = 0; i < months.length; i++)
			this.m_dlgElements.rt_y_m[this.m_dlgElements.rt_y_m.length] = new Option(months[i], (i+1), false, (me.monthOfYear == (i + 1))?true:false);
		this.m_dlgElements.rt_y_m.onchange = function()
		{
			me.monthOfYear = this.value;
		}
		// interval
		var lbl = alib.dom.createElement("span", td);
		lbl.innerHTML = "&nbsp;";
		this.m_dlgElements.rt_y_d = alib.dom.createElement("input");
		this.m_dlgElements.rt_y_d.style.width = "20px";
		
		this.m_dlgElements.rt_y_d.value = (me.dayOfMonth) ? me.dayOfMonth : 1;
		this.m_dlgElements.rt_y_d.onchange = function()
		{
			me.dayOfMonth = this.value;
		}
		td.appendChild(this.m_dlgElements.rt_y_d);


		var row = alib.dom.createElement("row", tbody);
		var td = alib.dom.createElement("td", row);
		var rbtn2 = alib.dom.createElement("input");
		rbtn2.type='radio';
		rbtn2.name = "yearly_type";
		rbtn2.rpcls = this;
		rbtn2.onchange = function() 
		{
			this.rpcls.type = 6;
			this.rpcls.recurToggleYearlyType(); 
		}
		td.appendChild(rbtn2);

		var td = alib.dom.createElement("td", row);

		// Relative Type
		this.m_dlgElements.rt_y_rel = alib.dom.createElement("select", td);
		//this.m_dlgElements.rt_y_rel.id = "rt_y_rel";
		var vals = new Array("First", "Second", "Third", "Fourth", "Last");
		
		for (var i = 0; i < vals.length; i++)
			this.m_dlgElements.rt_y_rel[this.m_dlgElements.rt_y_rel.length] = new Option("The " + vals[i], (i+1), false, (me.instance == (i + 1))?true:false);
		this.m_dlgElements.rt_y_rel.onchange = function()
		{
			me.instance = this.value;
		}
		// Relavtive Section or Day of the week
		var lbl = alib.dom.createElement("br", td);
		this.m_dlgElements.rt_y_day = alib.dom.createElement("select", td);
		//this.m_dlgElements.rt_y_day.id = "rt_y_day";
		var vals = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
		var sel_selected = false;
		for (var i = 0; i < vals.length; i++)
		{
			sel_selected = false;
			switch(i)
			{
				case 0:	sel_selected = (me.day1 == 't')?true:false; break;
				case 1:	sel_selected = (me.day2 == 't')?true:false; break;
				case 2:	sel_selected = (me.day3 == 't')?true:false; break;
				case 3:	sel_selected = (me.day4 == 't')?true:false; break;
				case 4:	sel_selected = (me.day5 == 't')?true:false; break;
				case 5:	sel_selected = (me.day6 == 't')?true:false; break;
				case 6:	sel_selected = (me.day7 == 't')?true:false; break;
			}
			this.m_dlgElements.rt_y_day[this.m_dlgElements.rt_y_day.length] = new Option(vals[i], (i+1), false, sel_selected);
		}
		this.m_dlgElements.rt_y_day.onchange = function()
		{
			me.day1 = (this.value == 1)?'t':'f';
			me.day2 = (this.value == 2)?'t':'f';
			me.day3 = (this.value == 3)?'t':'f';
			me.day4 = (this.value == 4)?'t':'f';
			me.day5 = (this.value == 5)?'t':'f';
			me.day6 = (this.value == 6)?'t':'f';
			me.day7 = (this.value == 7)?'t':'f';
		}
		//  Month
		var lbl = alib.dom.createElement("br", td);
		this.m_dlgElements.rt_y_r_m = alib.dom.createElement("select", td);
		//this.m_dlgElements.rt_y_r_m.id = "rt_y_r_m";
		for (var i = 0; i < months.length; i++)
			this.m_dlgElements.rt_y_r_m[this.m_dlgElements.rt_y_r_m.length] = new Option("of " + months[i], (i+1), false, (me.monthOfYear == (i + 1))?true:false);
		this.m_dlgElements.rt_y_r_m.onchange = function()
		{
			me.monthOfYear = this.value;
		}
		
				
		if (me.type==0 || (me.instance==null && me.dayOfMonth!=null))
		{
			this.type = 5;
			this.recurToggleYearlyType(); 
			rbtn1.checked = true;
		}
		else
		{			
			this.type = 6;
			me.recurToggleYearlyType(); 
			rbtn2.checked = true;
		}

		break;
	default: // Blank - no recurrence
		dvfield.innerHTML = "";
		dvfield.style.display = "none";
		break;
	}
}


CRecurrencePattern.prototype.getHumanDesc = function()
{ 
	this.humanDesc = '';
	var me = this;
	
	if( this.id>0 )
	{
		// load values
	}
	
	switch( parseInt(this.type) )
	{
		case 1: // Daily
			
			// interval
			if( this.interval>1 )
			{
				this.humanDesc += ' Every '+this.interval+' days';
			}
			else
			{
				this.humanDesc += 'Every day ';
			}
			break;
		
		case 2: // Weekly
			
			// interval
			if( this.interval>1 )
			{
				this.humanDesc += 'Every '+this.interval+' weeks on ';
			}
			else
			{
				this.humanDesc += 'Every ';
			}
			
			// week days
			if( this.day1=='t' )
				this.humanDesc += 'Sunday, ';
			if( this.day2=='t' )
				this.humanDesc += 'Monday, ';
			if( this.day3=='t' )
				this.humanDesc += 'Tuesday, ';
			if( this.day4=='t' )
				this.humanDesc += 'Wednesday, ';
			if( this.day5=='t' )
				this.humanDesc += 'Thursday, ';
			if( this.day6=='t' )
				this.humanDesc += 'Friday, ';
			if( this.day7=='t' )
				this.humanDesc += 'Saturday, ';
			
			this.humanDesc = this.humanDesc.replace(/, $/,"");

			break;
		
		case 3: // Monthly
		
			var n = parseInt(this.dayOfMonth) % 100;
			var suff = ["th", "st", "nd", "rd", "th"];
			var ord= n<21?(n<4 ? suff[n]:suff[0]): (n%10>4 ? suff[0] : suff[n%10]);
			this.humanDesc += this.dayOfMonth+ord+' day of every ';
			
			if( parseInt(this.interval)>1 )
			{
				this.humanDesc += parseInt(this.interval)+' months';
			}
			else
			{
				this.humanDesc += parseInt(this.interval)+' month';
			}
			break;
			
		case 4: // Monthnth
		
			this.humanDesc += 'The ';
			switch( parseInt(this.instance) )
			{
				case  1: this.humanDesc += 'first ';
					break;
				case  2: this.humanDesc += 'second ';
					break;
				case  3: this.humanDesc += 'third ';
					break;
				case  4: this.humanDesc += 'fourth ';
					break;
				case  5: this.humanDesc += 'last ';
					break;
			}
			
			this.humanDesc += (this.day1=='t' ? ' Sunday ' : '');
			this.humanDesc += (this.day2=='t' ? ' Monday ' : '');
			this.humanDesc += (this.day3=='t' ? ' Tuesday ' : '');
			this.humanDesc += (this.day4=='t' ? ' Wednesday ' : '');
			this.humanDesc += (this.day5=='t' ? ' Thursday ' : '');
			this.humanDesc += (this.day6=='t' ? ' Friday ' : '');
			this.humanDesc += (this.day7=='t' ? ' Saturday ' : '');
			
			if( parseInt(this.interval)>1 )
			{
				this.humanDesc += ' of every '+parseInt(this.interval)+' months';
			}
			else
			{
				this.humanDesc += ' of every month';
			}
			break;
		
		case 5: // Yearly
		
			var n = parseInt(this.dayOfMonth) % 100;
			var suff = ["th", "st", "nd", "rd", "th"];
			var ord= n<21?(n<4 ? suff[n]:suff[0]): (n%10>4 ? suff[0] : suff[n%10]);
			this.humanDesc += 'Every '+this.dayOfMonth+ord+' day of ';
			
			switch(parseInt(this.monthOfYear))
			{
				case 1: this.humanDesc += ' January'; break;
				case 2: this.humanDesc += ' February'; break;
				case 3: this.humanDesc += ' March'; break;
				case 4: this.humanDesc += ' April'; break;
				case 5: this.humanDesc += ' May'; break;
				case 6: this.humanDesc += ' June'; break;
				case 7: this.humanDesc += ' July'; break;
				case 8: this.humanDesc += ' August'; break;
				case 9: this.humanDesc += ' September'; break;
				case 10: this.humanDesc += ' October'; break;
				case 11: this.humanDesc += ' November'; break;
				case 12: this.humanDesc += ' December'; break;
			}
			break;
		
		case 6: // Yearnth
		
			this.humanDesc += 'The ';
			switch( parseInt(this.instance) )
			{
				case  1: this.humanDesc += 'first ';
					break;
				case  2: this.humanDesc += 'second ';
					break;
				case  3: this.humanDesc += 'third ';
					break;
				case  4: this.humanDesc += 'fourth ';
					break;
				case  5: this.humanDesc += 'last ';
					break;
			}
			
			this.humanDesc += (me.day1=='t' ? ' Sunday ' : '');
			this.humanDesc += (me.day2=='t' ? ' Monday ' : '');
			this.humanDesc += (me.day3=='t' ? ' Tuesday ' : '');
			this.humanDesc += (me.day4=='t' ? ' Wednesday ' : '');
			this.humanDesc += (me.day5=='t' ? ' Thursday ' : '');
			this.humanDesc += (me.day6=='t' ? ' Friday ' : '');
			this.humanDesc += (me.day7=='t' ? ' Saturday ' : '');
			
			this.humanDesc += 'of ';
			
			switch(parseInt(this.monthOfYear))
			{
				case 1: this.humanDesc += ' January'; break;
				case 2: this.humanDesc += ' February'; break;
				case 3: this.humanDesc += ' March'; break;
				case 4: this.humanDesc += ' April'; break;
				case 5: this.humanDesc += ' May'; break;
				case 6: this.humanDesc += ' June'; break;
				case 7: this.humanDesc += ' July'; break;
				case 8: this.humanDesc += ' August'; break;
				case 9: this.humanDesc += ' September'; break;
				case 10: this.humanDesc += ' October'; break;
				case 11: this.humanDesc += ' November'; break;
				case 12: this.humanDesc += ' December'; break;
				
			}
			
			break;
		default:
			this.humanDesc = "Does not repeat";	
			return this.humanDesc;		
	}
	
	// date
	var strdateStart = new Date(this.dateStart);
	this.humanDesc += ' effective ' + alib.dateTime.format(strdateStart, "MM/dd/yyyy");
	// end date
	if( this.dateEnd )
	{
		var strendStart = new Date(this.dateEnd);
		this.humanDesc += ' until ' + alib.dateTime.format(strendStart, "MM/dd/yyyy");
	}
	
	// time
	if( this.fAllDay == 'f' ){
		this.humanDesc += ' at '+this.timeStart+' to '+this.timeEnd;
	}
	
	return this.humanDesc;

}

CRecurrencePattern.prototype.recurToggleMontlyType = function()
{ 
	
	if (this.type == 3)
	{
	
		this.m_dlgElements.rt_m_d_day.disabled = false; 
		this.m_dlgElements.rt_m_d_int.disabled = false; 
		
		this.m_dlgElements.rt_m_rel_nth.disabled = true; 
		this.m_dlgElements.rt_m_day_nth.disabled = true; 
		this.m_dlgElements.rt_m_d_int_nth.disabled = true; 

		this.instance = 1;
		this.day1 = (this.m_dlgElements.rt_m_d_day.value == 1)?'t':'f';
		this.day2 = (this.m_dlgElements.rt_m_d_day.value == 2)?'t':'f';
		this.day3 = (this.m_dlgElements.rt_m_d_day.value == 3)?'t':'f';
		this.day4 = (this.m_dlgElements.rt_m_d_day.value == 4)?'t':'f';
		this.day5 = (this.m_dlgElements.rt_m_d_day.value == 5)?'t':'f';
		this.day6 = (this.m_dlgElements.rt_m_d_day.value == 6)?'t':'f';
		this.day7 = (this.m_dlgElements.rt_m_d_day.value == 7)?'t':'f';
		this.interval = this.m_dlgElements.rt_m_d_day.value;

	}
	else
	{
		this.m_dlgElements.rt_m_d_day.disabled = true; 
		this.m_dlgElements.rt_m_d_int.disabled = true; 
		
		this.m_dlgElements.rt_m_rel_nth.disabled = false; 
		this.m_dlgElements.rt_m_day_nth.disabled = false; 
		this.m_dlgElements.rt_m_d_int_nth.disabled = false; 
		
		this.day1 = (this.m_dlgElements.rt_m_day_nth.value == 1)?'t':'f';
		this.day2 = (this.m_dlgElements.rt_m_day_nth.value == 2)?'t':'f';
		this.day3 = (this.m_dlgElements.rt_m_day_nth.value == 3)?'t':'f';
		this.day4 = (this.m_dlgElements.rt_m_day_nth.value == 4)?'t':'f';
		this.day5 = (this.m_dlgElements.rt_m_day_nth.value == 5)?'t':'f';
		this.day6 = (this.m_dlgElements.rt_m_day_nth.value == 6)?'t':'f';
		this.day7 = (this.m_dlgElements.rt_m_day_nth.value == 7)?'t':'f';
		
		this.dayOfMonth = this.m_dlgElements.rt_m_day_nth.value;
		this.interval = this.m_dlgElements.rt_m_d_int_nth.value;
		this.instance = this.m_dlgElements.rt_m_rel_nth.value;
	}
}

CRecurrencePattern.prototype.recurToggleYearlyType = function()
{ 

	if (this.type == 5)
	{ 	// yearly
		this.m_dlgElements.rt_y_m.disabled = false; 
		this.m_dlgElements.rt_y_d.disabled = false; 

		this.m_dlgElements.rt_y_rel.disabled = true; 
		this.m_dlgElements.rt_y_day.disabled = true; 
		this.m_dlgElements.rt_y_r_m.disabled = true; 
		
		this.monthOfYear = this.m_dlgElements.rt_y_m.value;
		this.dayOfMonth = this.m_dlgElements.rt_y_d.value;
		
		this.instance =  null;
	}
	else
	{

		// yearnth
		this.m_dlgElements.rt_y_m.disabled = true; 
		this.m_dlgElements.rt_y_d.disabled = true; 

		this.m_dlgElements.rt_y_rel.disabled = false; 
		this.m_dlgElements.rt_y_day.disabled = false; 
		this.m_dlgElements.rt_y_r_m.disabled = false; 
		
		this.instance = this.m_dlgElements.rt_y_rel.value;
		this.monthOfYear = this.m_dlgElements.rt_y_r_m.value;
		
		this.dayOfMonth = null;

	}
} 
