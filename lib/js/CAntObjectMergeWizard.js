/****************************************************************************
*	
*	Class:		CAntObjectMergeWizard
*
*	Purpose:	Wizard for merging objects
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function CAntObjectMergeWizard(obj_type)
{
	this.objects			= new Array();
	this.fields				= new Array(); // list which object id to pull each field name [name, object_id] 
	this.obj_type			= obj_type;
	this.mainObject			= new CAntObject(this.obj_type);

	this.steps = new Array();
	this.steps[0] = "Getting Started";
	this.steps[1] = "Define Fields";
	this.steps[2] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CAntObjectMergeWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Merge "+this.mainObject.titlePl, this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 650, 510);

	this.showStep(0);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
CAntObjectMergeWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.cbTemplates = null;
	this.verify_step_data = new Object();
	this.nextStep = step+1;

	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", this.body_dv);
	this.pathDiv.innerHTML = "Step " + (step + 1) + " of " + this.steps.length + " - " + this.steps[step];
	alib.dom.styleSetClass(this.pathDiv, "wizardTitle");

	// Main content
	// ---------------------------------------------------------
	var div_main = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(div_main, "wizardBody");

	switch (step)
	{
	case 0:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Data Merge Wizard";

		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 3px 0");
		p.innerHTML = "This wizard will guide you through merging multiple "+this.mainObject.titlePl+" without losing data.";

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 1:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Select Data To Merge";

		var p = alib.dom.createElement("p", div_main);
		alib.dom.styleSet(p, "margin", "0 0 10px 0");
		p.innerHTML = "Select which record you would like to use for each value.";

		this.buildFrmData(div_main);

		this.veriftyStep = function()
		{
			return true;
		}
		break;

	case 2:
		div_main.innerHTML = "<h2>Data is ready to be merged!</h2><h3>WARNING: This cannot be undone so use caution to assure you intended to "
						   + "permanantly merge all the data.</h3>"
						   + "Click 'Finish' below to merge "+this.mainObject.titlePl+" and exit this wizard.";
		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSet(dv_btn, "margin-top", "8px");
	alib.dom.styleSet(dv_btn, "text-align", "right");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.merge(); }, [this], "b2");
		btn.print(dv_btn);
	}
	else
	{
		var next_funct = function(cls, step)
		{
			if (cls.veriftyStep())
			{
				cls.showStep(step+1);
			}
			else
			{
				ALib.Dlg.messageBox(cls.verify_step_data.message, cls.m_dlg);
			}
		}

		var btn = new CButton("Next", next_funct, [this, step], "b2");
		btn.print(dv_btn);
	}

	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [this.m_dlg], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
CAntObjectMergeWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntObjectMergeWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntObjectMergeWizard.prototype.onFinished = function()
{
}


/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
CAntObjectMergeWizard.prototype.merge = function()
{
	var args = [["obj_type", this.obj_type]];

	for (var i = 0; i < this.objects.length; i++)
	{
		args[args.length] = ["objects[]", this.objects[i].id];
	}

	// Send list of fields and which object id they will be pulled from
	for (var i = 0; i < this.fields.length; i++)
	{
		args[args.length] = ["fld_use_"+this.fields[i].name, this.fields[i].object_id];
	}

	this.m_dlg.hide();

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Merging data, please wait...";
	dlg.statusDialog(dv_load, 250, 100);
    
    /*function cbdone(ret, cls, dlg)
    {
        dlg.hide();

        if (!ret['error'])
        {
            cls.onFinished(ret, cls.message);
        }
    }
    var rpc = new CAjaxRpc("/controller/Object/mergeObjects", "mergeObjects", args, cbdone, [this, dlg], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dlg = dlg;
    ajax.onload = function(ret)
    {
        this.cbData.dlg.hide();

        if (!ret['error'])
            this.cbData.cls.onFinished(ret, this.cbData.cls.message);
    };
    ajax.exec("/controller/Object/mergeObjects", args);
}

/*************************************************************************
*	Function:	addObject
*
*	Purpose:	Add an object to the list to merge
**************************************************************************/
CAntObjectMergeWizard.prototype.addObject = function(oid)
{
	var obj = new CAntObject(this.obj_type, oid);
	obj.mergeCls = this;
	obj.onload = function()
	{
		if (this.mergeCls.nextStep == 3) // on step 2 which is the merge data screen = reload to show vals
			this.mergeCls.showStep(2);
	}
	obj.load();
	this.objects[this.objects.length] = obj;
}

/*************************************************************************
*	Function:	buildFrmData
*
*	Purpose:	Create form for selecting data
**************************************************************************/
CAntObjectMergeWizard.prototype.buildFrmData = function(con)
{
	var tbl = new CToolTable("100%");
	tbl.print(con);

	tbl.addHeader("&nbsp;");
	for (var i = 0; i < this.objects.length; i++)
	{
		tbl.addHeader("&nbsp;", "12px");
		tbl.addHeader(this.mainObject.title + " " + this.objects[i].id);
	}

	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].name != "id" && fields[i].name != "ts_created" && fields[i].name != "ts_updated" && fields[i].name != "account_id")
		{
			var rw = tbl.addRow();
			rw.addCell(fields[i].title, true);

			// Populate record data
			for (var j = 0; j < this.objects.length; j++)
			{
				var useopt = alib.dom.createElement("input");
				useopt.type = "radio";
				useopt.name = "use_opt_"+fields[i].name;
				useopt.field_name = fields[i].name;
				useopt.value = this.objects[j].id;
				useopt.cls = this;
				useopt.checked = (this.getFieldUse(fields[i].name) == this.objects[j].id) ? true : false;
				useopt.onclick = function() 
				{ 
					this.cls.setFieldUse(this.field_name, this.value);
				} 

				rw.addCell(useopt, false, "center");
				 
				if (fields[i].type == "fkey_multi")
				{
					rw.addCell(this.objects[j].getMultiValueStr(fields[i].name), false);
				}
				else if (fields[i].type == "fkey")
				{
					rw.addCell(this.objects[j].getValueName(fields[i].name), false);
				}
				else
				{
					rw.addCell(this.objects[j].getValue(fields[i].name), false);
				}
			}
		}
	}
}

/*************************************************************************
*	Function:	setFieldUse
*
*	Purpose:	Set which object_id to use
**************************************************************************/
CAntObjectMergeWizard.prototype.setFieldUse = function(field_name, oid)
{
	for (var i = 0; i < this.fields.length; i++)
	{
		if (this.fields[i].name == field_name)
		{
			this.fields[i].object_id = oid;
			return;
		}
	}

	// Not yet set, put the the first object
	this.fields[this.fields.length] = {name:field_name, object_id:oid};
	return;
}

/*************************************************************************
*	Function:	getFieldUse
*
*	Purpose:	Find out what object is to be used for field_name
**************************************************************************/
CAntObjectMergeWizard.prototype.getFieldUse = function(field_name)
{
	for (var i = 0; i < this.fields.length; i++)
	{
		if (this.fields[i].name == field_name)
			return this.fields[i].object_id;
	}

	// Not yet set
	var useobj = 0;
	for (var i = 0; i < this.objects.length; i++) // get object with value
	{
		if (this.objects[i].getValue(field_name))
		{
			useobj = i;
			break;
		}
	}
	this.setFieldUse(field_name, this.objects[useobj].id);
	return this.objects[useobj].id;
}
