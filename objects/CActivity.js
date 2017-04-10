/****************************************************************************
*	
*	Class:		CActivity
*
*	Purpose:	Activity editor/viewer
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2009 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/

function CActivity(activity_id, association, obj_reference)
{
	this.m_aid = (activity_id) ? activity_id : null;
	this.mainObject = new CAntObject("activity", activity_id);
	this.association = (association) ? association : ""; // var can be used to forward association "obj_type:object_id"
	if (obj_reference)
		this.mainObject.setValue("obj_reference", obj_reference);


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
CActivity.prototype.showDialog = function()
{
	this.buildInterface();
}

/*************************************************************************
*	Function:	buildInterface
*
*	Purpose:	Display activity interface
**************************************************************************/
CActivity.prototype.buildInterface = function(loaded)
{
	var obj_loaded = (loaded) ? true : false;

	if (this.m_aid && !obj_loaded)
	{
		this.mainObject.actObj = this;
		this.mainObject.onload = function() { this.actObj.buildInterface(true); };
		this.mainObject.load();
		return;
	}

	var f_readonly = (this.mainObject.getValue("f_readonly")) ? true : false;

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

	dlg.customDialog(dv, 600, 420);

	var dv_content = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_content, "overflow", "auto");
	alib.dom.styleSet(dv_content, "height", "390px");

	var tbl = alib.dom.createElement("table", dv_content);
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
	td_inp.colSpan = 3;
	if (f_readonly)
		td_inp.innerHTML = this.mainObject.getValue("name");
	else
		this.mainObject.fieldGetValueInput(td_inp, "name");

	// Type
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Type:";
	// Input
	this.dvSelCon = alib.dom.createElement("td", row);
	if (f_readonly)
		this.dvSelCon.innerHTML = this.mainObject.getValueName("type_id");
	else
		this.mainObject.fieldGetValueInput(this.dvSelCon, "type_id", {filter:["obj_type", ""]});

	// Direction
	// -----------------------------------------------
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Direction:";
	// Input
	this.dvDirCon = alib.dom.createElement("td", row);
	if (f_readonly)
		this.dvDirCon.innerHTML = this.mainObject.getValueName("direction");
	else
		this.mainObject.fieldGetValueInput(this.dvDirCon, "direction");

	// Type
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "When:";
	// Input
	this.dvWhenCon = alib.dom.createElement("td", row);
	this.dvWhenCon.colSpan = 3;
	if (f_readonly)
		this.dvWhenCon.innerHTML = this.mainObject.getValue("ts_entered");
	else
		this.mainObject.fieldGetValueInput(this.dvWhenCon, "ts_entered");

	// Associations
	// -----------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td_lbl = alib.dom.createElement("td", row);
	td_lbl.vAlign = "top";
	alib.dom.styleSetClass(td_lbl, "formLabel");
	alib.dom.styleSet(td_lbl, "width", "55px");
	td_lbl.innerHTML = "Associations:";
	// Input
	var td_inp = alib.dom.createElement("td", row);
	td_inp.colSpan = 3;
	//var assc_str = "";
	if (this.association)
	{
		this.mainObject.setMultiValue("associations", this.association);
		//assc_str = this.mainObject.setMultiValue("associations", this.association);
		//assc_str = this.association;
	}
	/*
	if (assc_str) assc_str += "; ";
	assc_str += this.mainObject.getMultiValueStr("associations");
	td_inp.innerHTML = assc_str;
	*/
	var vals = this.mainObject.getMultiValues("associations");
	for (var m = 0; m < vals.length; m++)
	{
		var parts =  vals[m].split(":");
		if (parts.length == 2)
		{
			//var lbl_parts = objectSplitLbl(this.mainObject.getValueName("associations", vals[m]));

			if (m)
			{
				var sp = alib.dom.createElement("span", td_inp);
				sp.innerHTML = "; ";
			}

			var assoc_con = alib.dom.createElement("span", td_inp);
			//assoc_con.innerHTML = lbl_parts.typeTitle + ": ";

			var a = alib.dom.createElement("a", assoc_con);
			a.obj_type = parts[0];
			a.oid = parts[1];
			a.href = "javascript:void(0)";
			a.innerHTML = this.mainObject.getValueName("associations", vals[m]); //lbl_parts.objTitle;
			a.onclick = function() { loadObjectForm(this.obj_type, this.oid); };
		}
	}

	// Notes
	// -----------------------------------------------
	var ndiv = alib.dom.createElement("div", dv_content);
	alib.dom.styleSet(ndiv, "margin-right", "5px");
	alib.dom.styleSet(ndiv, "border", "0");
	if (f_readonly)
	{
		var val = this.mainObject.getValue("notes");
		var re = new RegExp ("\n", 'gi') ;
		ndiv.innerHTML = "<div class='formLabel' style='margin-left:3px;'>Details/Notes:</div><div style='margin:5px;'>"+val.replace(re, "<br />")+"</div>";
	}
	else
	{
		this.mainObject.fieldGetValueInput(ndiv, "notes", {multiLine:true, height:"300px", width:"100%"});
	}

	// Comments
	// -----------------------------------------------
	if (this.m_aid)
	{
		var lbl = alib.dom.createElement("div", dv_content);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Comments:";
		var ob = new CAntObjectBrowser("comment");
		ob.setFilter("associations", "activity:"+this.m_aid);
		ob.printComments(dv_content, "activity:"+this.m_aid);
	}

	// Buttons
	// -----------------------------------------------
	var dv_btn = alib.dom.createElement("div", dv);
	var me = this;

	if (f_readonly)
	{
		var btn = new CButton("Close", function(dlg) {  dlg.hide(); }, [me.m_dlg]);
		btn.print(dv_btn);
	}
	else
	{
        var btn = alib.ui.Button("Save &amp; Close", 
                    {
                        className:"b2", cls:me, dlg:me.m_dlg,
                        onclick:function() 
                        {
                            this.cls.save();
                            this.dlg.hide();
                        }
                    });
        this.btnSave = btn.getButton();
        dv_btn.appendChild(this.btnSave);
        
        var btn = alib.ui.Button("Cancel", 
                    {
                        className:"b1", dlg:me.m_dlg,
                        onclick:function() 
                        {
                            this.dlg.hide();
                        }
                    });
        dv_btn.appendChild(btn.getButton());
	}
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save changes or new activity to database
**************************************************************************/
CActivity.prototype.save = function()
{
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	if (!this.mainObject.id && Ant)
	{
		this.mainObject.setValue("user_id", Ant.user.id);
	}

	this.mainObject.actObj = this;
	this.mainObject.dlgSaving = dlg;
	this.mainObject.dlgForm = this.m_dlg;
	this.mainObject.onsave = function() { this.dlgForm.hide(); this.dlgSaving.hide(); this.actObj.onSave(); }
	this.mainObject.save();
}

/*************************************************************************
*	Function:	onSave
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CActivity.prototype.onSave = function(aid)
{
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CActivity.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	getActivityTypes
*
*	Purpose:	Get activity types via AJAX
**************************************************************************/
CActivity.prototype.getActivityTypes = function()
{
	/*var funct = function(ret,cls)
	{
		if (ret)
		{			
			if (ret.length)
			{
				for(types in ret)
                {
                    var currentType = ret[types];
					cls.selTypes[cls.selTypes.length] = new Option(currentType.name, currentType.id, false, (cls.type_id==currentType.id)?true:false);
				}
			}
		}
	}
	var me = this;	
    var xmlrpc = new CAjaxRpc("/controller/Object/getActivityTypes", "getActivityTypes", null, funct, [me], AJAX_POST, true, "json");    */
    
    ajax = new CAjax('json');
    ajax.cbData.cls = me;
    ajax.onload = function(ret)
    {
        if (ret)
        {            
            if (ret.length)
            {
                for(types in ret)
                {
                    var currentType = ret[types];
                    this.cbData.cls.selTypes[this.cbData.cls.selTypes.length] = new Option(currentType.name, currentType.id, false, (this.cbData.cls.type_id==currentType.id)?true:false);
                }
            }
        }
    };
    ajax.exec("/controller/Object/getActivityTypes");
}

