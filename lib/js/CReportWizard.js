/****************************************************************************
*	
*	Class:		CReportWizard
*
*	Purpose:	Wizard for creating/editing a report
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		ALib, CReport.js, CAntObject.js
*
*****************************************************************************/
function CReportWizard(report_obj)
{
	this.mainReport	= report_obj;
	this.conditionObj = null;
	//this.mainObject = null;
	this.mainObject = new CAntObject((report_obj.obj_type)?report_obj.obj_type:"object");

	this.steps = new Array();
	this.steps[0] = "Create Report";
	this.steps[1] = "Define Data";
	this.steps[2] = "Set Conditions";
	this.steps[3] = "Display Options";
	this.steps[4] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CReportWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Report Wizard", this.parentDlg);
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
CReportWizard.prototype.showStep = function(step)
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
		alib.dom.styleSet(p, "margin", "5px 0 5px 0");
		p.innerHTML = "General Report Details:";

		this.buildDetailsFrm(div_main);

		this.veriftyStep = function()
		{
			if (this.mainReport.obj_type)
			{
				if (this.mainReport.name)
					return true;
				else
				{
					this.verify_step_data.message = "Please select enter a title for this report";
					return false;
				}
			}
			else
			{
				this.verify_step_data.message = "Please select a Main Object Type to report on";
				return false;
			}
		}
		break;
	case 1:
		var p = alib.dom.createElement("h2", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 5px 0");
		p.innerHTML = "Define Data To View:";

		this.buildDataFrm(div_main);

		this.veriftyStep = function()
		{
			if (this.mainReport.fCalculate)
			{
				if (this.mainReport.dimensions.length)
				{
					if (!this.mainReport.dimensions[0].field)
					{
						this.verify_step_data.message = "Please select the \"First field / dimension\" to View Totals By";
						return false;
					}
				}
				else
				{
					this.verify_step_data.message = "Please select the \"First field / dimension\" to View Totals By";
					return false;
				}

				if (this.mainReport.measures.length)
				{
					if (this.mainReport.measures[0].field)
					{
						return true
					}
					else
					{
						this.verify_step_data.message = "Please select a field to report on";
						return false;
					}
				}
				else
				{
					this.verify_step_data.message = "Please select a field to report on";
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		break;
	case 2:
		var p = alib.dom.createElement("h2", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 5px 0");
		p.innerHTML = "Data Filter Conditions:";

		this.buildConditionFrm(div_main);

		this.veriftyStep = function()
		{
			this.mainReport.view.conditions = new Array();
			for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
			{
				var cond = this.conditionObj.getCondition(i);

				this.mainReport.view.conditions[i] = new Object();
				this.mainReport.view.conditions[i].blogic = cond.blogic;
				this.mainReport.view.conditions[i].fieldName = cond.fieldName;
				this.mainReport.view.conditions[i].operator = cond.operator;
				this.mainReport.view.conditions[i].condValue = cond.condValue;
			}

			return true;
		}

		break;
	case 3:
		var p = alib.dom.createElement("h2", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 5px 0");
		p.innerHTML = "Display Options:";

		this.buildDisplayFrm(div_main);

		this.veriftyStep = function()
		{
			if (!this.mainReport.chart_type && this.mainReport.fDisplayChart)
			{
					this.verify_step_data.message = "Please select a chart type before continuing";
					return false;
			}
			else
				return true;
		}

		break;
	case 4:
		div_main.innerHTML = "<h2>Congratulations!</h2><h3>Your report has been created.</h3>" +
							 "Click 'Finish' below to close this wizard and view you data.";
		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(dv_btn, "wizardFooter");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.save(); }, [this]);
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
CReportWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CReportWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CReportWizard.prototype.onFinished = function()
{
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
CReportWizard.prototype.save = function()
{
	this.mainReport.wizcls = this;
	this.mainReport.onsave = function(rid)
	{
		this.wizcls.onFinished(1, "Name");
		this.wizcls.m_dlg.hide();
	}

	this.mainReport.save();
}

/*************************************************************************
*	Function:	buildConditionFrm
*
*	Purpose:	Create condition
**************************************************************************/
CReportWizard.prototype.buildConditionFrm = function(con)
{

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "Create advanced search filter for this report.";

	if (this.mainObject)
	{
		var row_con = alib.dom.createElement("div", con);
		var conds = (this.mainReport.view) ? this.mainReport.view.conditions : null;
		this.conditionObj = this.mainObject.buildAdvancedQuery(row_con, conds);



		/*
		 * Order By
		 * ========================================================================
		 */
		var h = alib.dom.createElement("h3", con);
		h.innerHTML = "Define Sort Order";

		var row_con = alib.dom.createElement("div", con);
		var cols_con = alib.dom.createElement("div", row_con);
		var a_order = alib.dom.createElement("a", row_con);
		a_order.href = "javascript:void(0);";
		a_order.innerHTML = "Add Field";
		a_order.cls = this;
		a_order.cols_con = cols_con;
		a_order.onclick = function() { this.cls.addOrderBy(this.cols_con); }
		var sort_order = this.mainReport.view.sort_order.slice(0);
		this.mainReport.view.sort_order = new Array();
		for (var i = 0; i < sort_order.length; i++)
		{
			this.addOrderBy(cols_con, sort_order[i].fieldName, sort_order[i].order);
		}
	}
}

/*************************************************************************
*	Function:	buildDisplayFrm
*
*	Purpose:	Set display options
**************************************************************************/
CReportWizard.prototype.buildDisplayFrm = function(con)
{
	var dv_chartypes = alib.dom.createElement("div");

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "Use the form below to determine how you would like to view this report.";

	// Type
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Display";

	var row_con = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'rtype';
	rbtn1.report = this.mainReport;
	rbtn1.dv_chartypes = dv_chartypes;
	rbtn1.onclick = function() { this.report.fDisplayTable = true; this.report.fDisplayChart = true; this.dv_chartypes.style.display = "block"; }
	row_con.appendChild(rbtn1);
	rbtn1.checked = (this.mainReport.fDisplayTable && this.mainReport.fDisplayChart);
	var lbl = alib.dom.createElement("span", row_con);
	lbl.innerHTML = " Table and Chart ";
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'rtype';
	rbtn1.report = this.mainReport;
	rbtn1.dv_chartypes = dv_chartypes;
	rbtn1.onclick = function() { this.report.fDisplayTable = true; this.report.fDisplayChart = false; this.dv_chartypes.style.display = "none"; }
	row_con.appendChild(rbtn1);
	rbtn1.checked = (this.mainReport.fDisplayTable && !this.mainReport.fDisplayChart);
	var lbl = alib.dom.createElement("span", row_con);
	lbl.innerHTML = " Table Only ";
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'rtype';
	rbtn1.report = this.mainReport;
	rbtn1.dv_chartypes = dv_chartypes;
	rbtn1.onclick = function() { this.report.fDisplayTable = false; this.report.fDisplayChart = true; this.dv_chartypes.style.display = "block"; }
	row_con.appendChild(rbtn1);
	rbtn1.checked = (!this.mainReport.fDisplayTable && this.mainReport.fDisplayChart);
	var lbl = alib.dom.createElement("span", row_con);
	lbl.innerHTML = " Chart Only ";

	// Print Chart Types
	// ------------------------------------------------------------------------
	if (!this.mainReport.fDisplayChart)
		dv_chartypes.style.display = "none";
	con.appendChild(dv_chartypes);
	
	var h = alib.dom.createElement("h3", dv_chartypes);
	h.innerHTML = "Select Chart Type";

	this.getGraphs(dv_chartypes);
}

/*************************************************************************
*	Function:	getGraphs
*
*	Purpose:	Populate a combobox of system objects
**************************************************************************/
CReportWizard.prototype.getGraphs = function(con)
{
	function cbdone(ret, cls, con)
	{
		if (ret != "-1")
		{
			// Chart = [name, title, category]
			var chart_list = eval(ret);

			for (var i = 0; i < chart_list.length; i++)
			{
				var dv = alib.dom.createElement("div", con);

				var rbtn1 = alib.dom.createElement("input");
				rbtn1.type='radio';
				rbtn1.name = 'chart_type';
				rbtn1.value = chart_list[i][0];
				rbtn1.report = cls.mainReport;
				rbtn1.onclick = function() { this.report.chart_type = this.value; }
				dv.appendChild(rbtn1);
				rbtn1.checked = (cls.mainReport.chart_type == chart_list[i][0]);
				var lbl = alib.dom.createElement("span", dv);
				lbl.innerHTML = "&nbsp;&nbsp;"+chart_list[i][1];
			}
		}
	}

	var gtype = (this.mainReport.dimensions.length > 1) ? "multi" : "single";
	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "report_get_graph_types", [["gtype", gtype]], cbdone, [this, con]);
}

/*************************************************************************
*	Function:	buildDataFrm
*
*	Purpose:	Define data to pull
**************************************************************************/
CReportWizard.prototype.buildDataFrm = function(con)
{
	var p = alib.dom.createElement("p", con);
	p.innerHTML = "Select what you would like to view in this report.";

	if (this.mainReport.fCalculate)
	{
		/*
		 * Group By
		 * ========================================================================
		 */
		var h = alib.dom.createElement("h3", con);
		h.innerHTML = "View Totals By";

		// Dimension 1
		// ------------------------------------------------------------------------
		var row_con = alib.dom.createElement("div", con);
		var subopt_con = alib.dom.createElement("span"); // Used for group by
		// label 1
		var lbl = alib.dom.createElement("span", row_con);
		lbl.innerHTML = "First field / dimension (required):&nbsp;";
		// Field select
		var field_sel = alib.dom.createElement("select", row_con);
		field_sel.report = this.mainReport;
		field_sel.subopt_con = subopt_con;
		field_sel.mainObject = this.mainObject;
		field_sel.onchange = function() 
		{ 
			var grp = "";
			if (this.value)
			{
				var field = this.mainObject.getFieldByName(this.value);
				if (field)
				{
					if (field.type == "date" || field.type == "timestamp")
					{
						this.subopt_con.style.visibility = "visible";
						grp = this.report.timegroup_types[0][0];
					}
					else
						this.subopt_con.style.visibility = "hidden";
				}
			}
			this.report.dimensions[0] = {field:this.value, group:grp} 
		}
		var fields = this.mainObject.getFields();
		var curdim = (this.mainReport.dimensions.length) ? this.mainReport.dimensions[0].field : "";
		field_sel[0] = new Option("Select field", "", false, (curdim=="")?true:false);
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].type != "fkey_multi")
			{
				field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, 
															(curdim==fields[i].name)?true:false);
			}
		}
		
		// Subgroup
		var lbl = alib.dom.createElement("span", subopt_con);
		lbl.innerHTML = "&nbsp;&nbsp;Group By&nbsp;";

		var aggreg_sel = alib.dom.createElement("select", subopt_con);
		aggreg_sel.report = this.mainReport;
		aggreg_sel.onchange = function() 
		{ 
			var fld = (this.report.dimensions.length>1) ? this.report.dimensions[0].field : "";
			this.report.dimensions[0] = {field:fld, group:this.value} 
		}
		var curdim_grp = (this.mainReport.dimensions.length) ? this.mainReport.dimensions[0].group : "";
		var fields = this.mainObject.getFields();
		for (var i = 0; i < this.mainReport.timegroup_types.length; i++)
		{
			aggreg_sel[aggreg_sel.length] = new Option(this.mainReport.timegroup_types[i][1], this.mainReport.timegroup_types[i][0], 
														false, (curdim_grp==this.mainReport.timegroup_types[i][0])?true:false);
		}
		row_con.appendChild(subopt_con);
		subopt_con.style.visibility = "hidden";
		if (curdim)
		{
			var field = this.mainObject.getFieldByName(curdim);
			if (field)
			{
				if (field.type == "date" || field.type == "timestamp")
					subopt_con.style.visibility = "visible";
				else
					subopt_con.style.visibility = "hidden";
			}
		}

		// Dimension 2
		// ------------------------------------------------------------------------
		var row_con = alib.dom.createElement("div", con);
		var subopt_con = alib.dom.createElement("span"); // Used for group by
		var lbl = alib.dom.createElement("span", row_con);
		lbl.innerHTML = "Second field / dimension (optional):&nbsp;";
		var field_sel = alib.dom.createElement("select", row_con);
		field_sel.report = this.mainReport;
		field_sel.subopt_con = subopt_con;
		field_sel.mainObject = this.mainObject;
		field_sel.onchange = function() 
		{ 
			var grp = "";
			if (this.value)
			{
				var field = this.mainObject.getFieldByName(this.value);
				if (field)
				{
					if (field.type == "date" || field.type == "timestamp")
					{
						this.subopt_con.style.visibility = "visible";
						grp = this.report.timegroup_types[0][0];
					}
					else
						this.subopt_con.style.visibility = "hidden";
				}
			}
			this.report.dimensions[1] = {field:this.value, group:grp} 
		}
		var fields = this.mainObject.getFields();
		var curdim = (this.mainReport.dimensions.length>1) ? this.mainReport.dimensions[1].field : "";
		var curdim_grp = (this.mainReport.dimensions.length>1) ? this.mainReport.dimensions[1].group : "";
		field_sel[0] = new Option("Select field", "", false, (curdim=="")?true:false);
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].type != "fkey_multi")
			{
				field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, 
															(curdim==fields[i].name)?true:false);
			}
		}

		// Subgroup
		var lbl = alib.dom.createElement("span", subopt_con);
		lbl.innerHTML = "&nbsp;&nbsp;Group By&nbsp;";

		var aggreg_sel = alib.dom.createElement("select", subopt_con);
		aggreg_sel.report = this.mainReport;
		aggreg_sel.onchange = function() 
		{ 
			var fld = (this.report.dimensions.length>1) ? this.report.dimensions[1].field : "";
			this.report.dimensions[1] = {field:fld, group:this.value} 
		}
		var curdim_grp = (this.mainReport.dimensions.length>1) ? this.mainReport.dimensions[1].group : "";
		var fields = this.mainObject.getFields();
		for (var i = 0; i < this.mainReport.timegroup_types.length; i++)
		{
			aggreg_sel[aggreg_sel.length] = new Option(this.mainReport.timegroup_types[i][1], this.mainReport.timegroup_types[i][0], 
														false, (curdim_grp==this.mainReport.timegroup_types[i][0])?true:false);
		}
		row_con.appendChild(subopt_con);
		subopt_con.style.visibility = "hidden";
		if (curdim)
		{
			var field = this.mainObject.getFieldByName(curdim);
			if (field)
			{
				if (field.type == "date" || field.type == "timestamp")
					subopt_con.style.visibility = "visible";
				else
					subopt_con.style.visibility = "hidden";
			}
		}

		/*
		 * Measures
		 * ========================================================================
		 */
		var h = alib.dom.createElement("h3", con);
		h.innerHTML = "Report On Field - usually a numeric value or a record count";

		var row_con = alib.dom.createElement("div", con);
		var subopt_con = alib.dom.createElement("span"); // Used for group by
		var meas_sel = alib.dom.createElement("select", row_con);
		meas_sel.report = this.mainReport;
		meas_sel.subopt_con = subopt_con;
		meas_sel.onchange = function() 
		{ 
			var aggre = (this.value == "id") ? "count" : this.report.aggregate_types[0][0];
			this.report.measures[0] = {field:this.value, aggregate:aggre} 

			this.subopt_con.style.visibility = (this.value == "id") ? "hidden" : "visible";
		}
		var fields = this.mainObject.getFields();
		var curmeas = (this.mainReport.measures.length) ? this.mainReport.measures[0].field : "";
		meas_sel[0] = new Option("Click to select field", "", false, (curmeas=="")?true:false);
		meas_sel[1] = new Option("Count Records", "id", false, (curmeas=="id")?true:false);
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].type == "real" || fields[i].type == "integer" || fields[i].type == "bigint" || fields[i].type == "number"
				|| fields[i].type == "int8" || fields[i].type == "serial")
			{
				meas_sel[meas_sel.length] = new Option(fields[i].title, fields[i].name, false, 
															(curmeas==fields[i].name)?true:false);
			}
		}

		var lbl = alib.dom.createElement("span", subopt_con);
		lbl.innerHTML = "&nbsp;&nbsp;Calculation:&nbsp;";

		var aggreg_sel = alib.dom.createElement("select", subopt_con);
		aggreg_sel.report = this.mainReport;
		aggreg_sel.onchange = function() 
		{ 
			var fld = (this.report.measures.length) ? this.report.measures[0].field : "";
			this.report.measures[0] = {field:fld, aggregate:this.value} 
		}
		var fields = this.mainObject.getFields();
		var curagg = (this.mainReport.measures.length) ? this.mainReport.measures[0].aggregate : "";
		for (var i = 0; i < this.mainReport.aggregate_types.length; i++)
		{
			aggreg_sel[aggreg_sel.length] = new Option(this.mainReport.aggregate_types[i][1], this.mainReport.aggregate_types[i][0], 
														false, (curagg==this.mainReport.aggregate_types[i][0])?true:false);
		}
		row_con.appendChild(subopt_con);
		subopt_con.style.visibility = "hidden";
		// Select right aggregate
		if (curmeas && curmeas!="id")
		{
			subopt_con.style.visibility = "visible";
		}
	}

	/*
	 * View Fields
	 * ========================================================================
	 */
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Select fields to display in columns (in order)";

	var row_con = alib.dom.createElement("div", con);
	var cols_con = alib.dom.createElement("div", row_con);
	var a_order = alib.dom.createElement("a", row_con);
	a_order.href = "javascript:void(0);";
	a_order.innerHTML = "Add Field";
	a_order.cls = this;
	a_order.cols_con = cols_con;
	a_order.onclick = function() { this.cls.addViewColumn(this.cols_con); }
	if (this.mainReport.view && this.mainReport.view.view_fields)
		var view_fields = this.mainReport.view.view_fields.slice(0);
	else
		var view_fields = new Array();
	this.mainReport.view.view_fields = new Array();
	for (var i = 0; i < view_fields.length; i++)
	{
		this.addViewColumn(cols_con, view_fields[i].fieldName);
	}
}

/*************************************************************************
*	Function:	addViewColumn
*
*	Purpose:	Add a column view drop-down
**************************************************************************/
CReportWizard.prototype.addViewColumn = function(con, field_name)
{
	var selected_field = (field_name) ? field_name : "";

	if (typeof this.viewCOlSerial == "undefined")
		this.viewCOlSerial = 1;
	else
		this.viewCOlSerial++;

	var dv = alib.dom.createElement("div", con);

	var ind = this.mainReport.view.view_fields.length;
	this.mainReport.view.view_fields[ind] = new Object();
	this.mainReport.view.view_fields[ind].id = this.viewCOlSerial;
	this.mainReport.view.view_fields[ind].fieldName = selected_field;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.viewobj = this.mainReport.view.view_fields[ind];
	field_sel.onchange = function() { this.viewobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		//if (fields[i].type != "fkey_multi")
		//{
			field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (fields[i].name == selected_field)?true:false);
		//}
	}

	if (!this.mainReport.view.view_fields[ind].fieldName)
		this.mainReport.view.view_fields[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.viewid = this.viewCOlSerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.mainReport.view.view_fields.length; i++)
		{
			if (this.cls.mainReport.view.view_fields[i].id == this.viewid)
				this.cls.mainReport.view.view_fields.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 

	//getFields
}

/*************************************************************************
*	Function:	addOrderBy
*
*	Purpose:	Add a sort order entry
**************************************************************************/
CReportWizard.prototype.addOrderBy = function(con, fieldName, order)
{
	var sel_field = (fieldName) ? fieldName : "";
	var sel_order = (order) ? order : "asc";

	if (typeof this.orderBySerial == "undefined")
		this.orderBySerial = 1;
	else
		this.orderBySerial++;

	var dv = alib.dom.createElement("div", con);

	if (this.mainReport.view.sort_order.length)
	{
		var lbl = alib.dom.createElement("span", dv);
		lbl.innerHTML = "Then By: ";
	}

	var ind = this.mainReport.view.sort_order.length;
	this.mainReport.view.sort_order[ind] = new Object();
	this.mainReport.view.sort_order[ind].id = this.orderBySerial;
	this.mainReport.view.sort_order[ind].fieldName = sel_field;
	this.mainReport.view.sort_order[ind].order = sel_order;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.orderobj = this.mainReport.view.sort_order[ind];
	field_sel.onchange = function() { this.orderobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].type != "fkey_multi")
		{
			field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (sel_field==fields[i].name)?true:false);
		}
	}

	if (!this.mainReport.view.sort_order[ind].fieldName)
		this.mainReport.view.sort_order[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";
	
	// Add order (asc/desc)
	var order_sel = alib.dom.createElement("select", dv);
	order_sel.orderobj = this.mainReport.view.sort_order[ind];
	order_sel.onchange = function() { this.orderobj.order = this.value; };
	order_sel[order_sel.length] = new Option("Ascending", "asc", false, (sel_order == "asc")?true:false);
	order_sel[order_sel.length] = new Option("Descending", "desc", false, (sel_order == "desc")?true:false);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.orderid = this.orderBySerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.mainReport.view.sort_order.length; i++)
		{
			if (this.cls.mainReport.view.sort_order[i].id == this.orderid)
				this.cls.mainReport.view.sort_order.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 
}

/*************************************************************************
*	Function:	buildDetailsFrm
*
*	Purpose:	Select object type, set title, and description
**************************************************************************/
CReportWizard.prototype.buildDetailsFrm = function(con)
{
	var p = alib.dom.createElement("p", con);
	p.innerHTML = "Creating and editing reports is a very simple process. Simple follow each step in this wizard.";

	// Object
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Select Main Object To Report On";

	var row_con = alib.dom.createElement("div", con);
	var obj_sel = alib.dom.createElement("select", row_con);
	obj_sel.report = this.mainReport;
	obj_sel.cls = this;
	obj_sel.onchange = function() { this.report.setObjectType(this.value); this.cls.mainObject = new CAntObject(this.value); }
	obj_sel[0] = new Option("Click to select object", "", false, ("" == this.mainReport.obj_type)?true:false);
	this.populateObjectsSelect(obj_sel);

	// Type
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Display";

	var row_con = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='checkbox';
	rbtn1.name = 'summary';
	rbtn1.report = this.mainReport;
	rbtn1.onclick = function() { this.report.fCalculate = this.checked; }
	row_con.appendChild(rbtn1);
	rbtn1.checked = this.mainReport.fCalculate;
	var lbl = alib.dom.createElement("span", row_con);
	lbl.innerHTML = " Summarize / Display Totals - select this option if you wish to report on calculations like totals/counts/sums/averages";

	// Title
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Report Title";

	var row_con = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input", row_con);
	inp.style.width = "98%";
	inp.value = this.mainReport.name;
	inp.report = this.mainReport;
	inp.onchange = function() { this.report.name = this.value; };

	// Description
	var h = alib.dom.createElement("h3", con);
	h.innerHTML = "Describe This Report (optional)";

	var row_con = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("textarea", row_con);
	inp.style.width = "98%";
	inp.style.height = "100px";
	inp.value = this.mainReport.description;
	inp.report = this.mainReport;
	inp.onchange = function() { this.report.description = this.value; };
}

/*************************************************************************
*	Function:	populateObjectsSelect
*
*	Purpose:	Populate a combobox of system objects
**************************************************************************/
CReportWizard.prototype.populateObjectsSelect = function(sel)
{
	function cbdone(ret, sel, report)
	{
		if (!ret['error'])
		{   
			for(object in ret)
			{
                var currentObject = ret[object];
				sel[sel.length] = new Option(currentObject.title, currentObject.name, false, (currentObject.name == report.obj_type)?true:false);
			}
		}
	}
    	
    var rpc = new CAjaxRpc("/controller/Object/getObjects", "getObjects", null, cbdone, [sel, this.mainReport], AJAX_POST, true, "json");
}
