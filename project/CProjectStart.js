/****************************************************************************
*	
*	Class:		CProjectStart
*
*	Purpose:	Dialog to start a new project
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2009 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/

function CProjectStart()
{
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Public functin for building interface.
**************************************************************************/
CProjectStart.prototype.showDialog = function()
{
	this.buildInterface();
}

/*************************************************************************
*	Function:	buildInterface
*
*	Purpose:	Display activity interface
**************************************************************************/
CProjectStart.prototype.buildInterface = function()
{
	// Create loader callback
	var okfunct = function(dlg, cls, inp_name, sel_templates, inp_date_start, inp_date_deadline)
	{
        ajax = new CAjax('json');
        ajax.cbData.cls = cls;
        ajax.cbData.dlg = dlg;
        ajax.onload = function(ret)
        {
            this.cbData.dlg.hide();
            if (!ret['error'])
                this.cbData.cls.onSave(ret);
            else
                this.cbData.cls.onSaveError();
        };
        ajax.exec("/controller/Project/createProject",
                    [["name", inp_name.value], ["template_id", sel_templates.value], ["date_deadline", inp_date_deadline.value], ["date_started", inp_date_start.value]]);
	}

	var dlg = new CDialog("Create New Project");
	dlg.f_close = true;
	var dv = alib.dom.createElement("div");

	// Name
	var dv_hdr= alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(dv_hdr, "headerTwo");
	alib.dom.styleSet(dv_hdr, "margin-top", "5px");
	dv_hdr.innerHTML = "Project Name (required)";
	var dv_inp = alib.dom.createElement("div", dv);
	var inp_name = alib.dom.createElement("input", dv_inp);
	alib.dom.styleSet(inp_name, "width", "98%");

	// Template
	var dv_hdr= alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(dv_hdr, "headerTwo");
	alib.dom.styleSet(dv_hdr, "margin-top", "5px");
	dv_hdr.innerHTML = "Use Template";
	var dv_inp = alib.dom.createElement("div", dv);
	var sel_templates = alib.dom.createElement("select", dv_inp);
	sel_templates.dlgField = true; // selects are hidden by the dlg class - unhide
	sel_templates[sel_templates.length] = new Option("None", "", false, true);
	this.getTemplates(sel_templates);

	// Date Start
	var dv_hdr= alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(dv_hdr, "headerTwo");
	alib.dom.styleSet(dv_hdr, "margin-top", "5px");
	dv_hdr.innerHTML = "Start Date (required)";
	var dv_inp = alib.dom.createElement("div", dv);
	var inp_date_start = alib.dom.createElement("input", dv_inp);
	alib.dom.styleSet(inp_date_start, "width", "100px");
	var currentTime = new Date();
	inp_date_start.value = (currentTime.getMonth() + 1)+"/"+currentTime.getDate()+"/"+currentTime.getFullYear();

	// Date Deadline
	var dv_hdr= alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(dv_hdr, "headerTwo");
	alib.dom.styleSet(dv_hdr, "margin-top", "5px");
	dv_hdr.innerHTML = "Deadline (optional - if blank then project will be ongoing)";
	var dv_inp = alib.dom.createElement("div", dv);
	var inp_date_deadline = alib.dom.createElement("input", dv_inp);
	alib.dom.styleSet(inp_date_deadline, "width", "100px");

	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");
	var btn = new CButton("Continue", okfunct, [dlg, this, inp_name, sel_templates, inp_date_start, inp_date_deadline], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
	btn.print(dv_btn);

	dlg.customDialog(dv, 450);
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save changes or new activity to database
**************************************************************************/
CProjectStart.prototype.save = function()
{
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Creating project, please wait...";
	dlg.statusDialog(dv_load, 150, 100);
}

/*************************************************************************
*	Function:	onSave
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CProjectStart.prototype.onSave = function(pid)
{
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CProjectStart.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	getActivityTypes
*
*	Purpose:	Get activity types via AJAX
**************************************************************************/
CProjectStart.prototype.getTemplates = function(selTemplates)
{
    ajax = new CAjax('json');    
    ajax.cbData.selTemplates = selTemplates;
    ajax.onload = function(ret)
    {
        if (ret.length)
        {            
            for(template in ret)
            {
                var currentTemplate = ret[template];
                this.cbData.selTemplates[this.cbData.selTemplates.length] = new Option(currentTemplate.name, currentTemplate.id, false, false);
            }
        }
    };
    ajax.exec("/controller/Project/getTemplates");
}
