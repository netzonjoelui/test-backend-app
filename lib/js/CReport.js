/*======================================================================================
	
	Module:		CReport

	Purpose:	Load report data

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Usage:		var rpt = new CReport();

======================================================================================*/

function CReport()
{
	this.id = null;
	this.name = "";
	this.description = "";
	this.dimensions = new Array(); // array of objects{field, group}
	this.measures = new Array(); // array of objects{field, aggregate}
	this.view = null; // CAntObjectView
	this.cube = null; // COlapCube
	this.obj_type = null; // must be set to a valid ANT object type
	this.mainObject = null; // this is set with the setObjectType function and in load after obj_type is set
	this.chart_type = ""; // must be set to a valid chart type to load chart
	this.chart_width = 800; // this can be rest by a calling process
	this.fDisplayTable = true;
	this.fDisplayChart = true;
	this.fCalculate = true;
	this.hideloading = false; // for inline reports hide loading lialog
	this.aggregate_types = [["sum", "Sum / Add"], ["avg", "Average"], ["max", "Maximum"], ["min", "Minimum"]];
	this.timegroup_types = [["minute", "Minute"], ["hour", "Hour"], ["day", "Day"], ["month", "Month"], ["quarter", "Quarter"], ["year", "Year"]];
	this.scope = "system";
	this.daclId = null;
	this.customReport = ""; // used to pull data from a customized report
	this.conditions = new Array(); // Populate conditions prior to loading the report for run-time filtering
}

/**************************************************************************
* Function: 	setObjectType	
*
* Purpose:		Set the main object type of this report
**************************************************************************/
CReport.prototype.setObjectType = function(type)
{
	this.obj_type = type;
	this.mainObject = new CAntObject(type);
	this.view = new CAntObjectView(type);
	this.view.name = "Details";
}

/**************************************************************************
* Function: 	load	
*
* Purpose:		Load data for this report
**************************************************************************/
CReport.prototype.load = function(report_id)
{
	if (report_id)
		this.id = report_id;

	if (!this.id)
	{
		this.onloadError(-1, "No report ID to load");
		return;
	}

	var ajax = new CAjax();
	ajax.m_obj = this;
	ajax.onload = function(root)
	{
		for (var i = 0; i < root.getNumChildren(); i++)
		{
			var section = root.getChildNode(i);

			switch (section.m_name)
			{
			case "name":
				this.m_obj.name = unescape(section.m_text);
				break;
			case "dimensions":
				for (var j = 0; j < section.getNumChildren(); j++)
				{
					var dim = section.getChildNode(j);
					var fld = unescape(dim.getAttribute("field"));
					if (fld != "")
						this.m_obj.dimensions[j] = {field:fld, group:unescape(dim.getAttribute("group"))};
				}
				break;
			case "measures":
				for (var j = 0; j < section.getNumChildren(); j++)
				{
					var dim = section.getChildNode(j);
					this.m_obj.measures[j] = {field:unescape(dim.getAttribute("field")), aggregate:unescape(dim.getAttribute("aggregate"))};
				}
				break;
			case "description":
				this.m_obj.description = unescape(section.m_text);
				break;
			case "custom_report":
				this.m_obj.customReport = unescape(section.m_text);
				break;
			case "obj_type":
				this.m_obj.obj_type = unescape(section.m_text);
				this.m_obj.mainObject = new CAntObject(this.m_obj.obj_type);
				this.m_obj.view = new CAntObjectView(this.m_obj.obj_type);
				this.m_obj.view.name = "Details";
				break;
			case "view":
				this.m_obj.view = new CAntObjectView(this.m_obj.obj_type);
				this.m_obj.view.loadFromXml(section); // load view definition
				break;
			case "chart_type":
				this.m_obj.chart_type = unescape(section.m_text);
				break;
			case "f_display_table":
				this.m_obj.fDisplayTable = (section.m_text=='t')?true:false;
				break;
			case "f_display_chart":
				this.m_obj.fDisplayChart = (section.m_text=='t')?true:false;
				break;
			case "f_calculate":
				this.m_obj.fCalculate = (section.m_text=='t')?true:false;
				break;
			case "dacl_id":
				this.m_obj.daclId = unescape(section.m_text);
				break;
			}
		}

		// Copy run-time conditions to this view
		for (var i = 0; i < this.m_obj.conditions.length; i++)
		{
			this.m_obj.view.addCondition(this.m_obj.conditions[i].blogic, 
										 this.m_obj.conditions[i].fieldName, 
										 this.m_obj.conditions[i].operator, 
										 this.m_obj.conditions[i].condValue);
		}

		if (this.m_obj.fCalculate)
			this.m_obj.loadCube(); // onload will be called after the cube loads
		else
			this.m_obj.onload();
	};

	var url = "/datacenter/xml_get_report.php?rid=" + this.id;
	ajax.exec(url);
}

/**************************************************************************
* Function: 	loadCube	
*
* Purpose:		Load COlapCube after report definition
* 				this.obj_type & this.dimensions.length & this.measures.length
* 				must be set before the data can be loaded for this cube.
**************************************************************************/
CReport.prototype.loadCube = function(frm)
{
	if (this.obj_type && this.dimensions.length && this.measures.length)
	{
		if (!this.hideloading)
		{
			var dlg = new CDialog();
			var dv_load = document.createElement('div');
			alib.dom.styleSetClass(dv_load, "statusAlert");
			alib.dom.styleSet(dv_load, "text-align", "center");
			dv_load.innerHTML = "Loading report, please wait...";
			dlg.statusDialog(dv_load, 250, 100);
		}
		else
			var dlg = null;

		var d1 = this.dimensions[0].field;
		var d1_grp = this.dimensions[0].group;
		if ((this.dimensions.length > 1))
		{
			var d2 = this.dimensions[1].field;
			var d2_grp = this.dimensions[1].group;
		}
		else
		{
			var d2 = "";
			var d2_grp = "";
		}

		this.cube = new COlapCube(this.obj_type, this.view, this.measures);
		this.cube.cbObj = this;
		this.cube.loadingDlg = dlg;
		this.cube.chart_width = this.chart_width;
		this.cube.customReport = this.customReport;
		if (frm)
		{
			this.cube.onload = function() 
			{ 
				if (this.loadingDlg) this.loadingDlg.hide(); 
				this.cbObj.onCubeUpdate(); 
			};
		}
		else
		{
			this.cube.onload = function() 
			{ 
				if (this.loadingDlg) this.loadingDlg.hide(); 
				this.cbObj.onload(); 
			};
		}
		//ALib.m_debug = true;
		this.cube.load(d1, d1_grp, d2, d2_grp, this.chart_type);
	}
	else
	{
		this.onloadError(-1, "Not enough data has been set to load this report");
	}
}

/**************************************************************************
* Function: 	printCubeMicroForm	
*
* Purpose:		Print a small form that will be displayed at the top
* 				of a report for changing dimensions and filters
**************************************************************************/
CReport.prototype.printCubeMicroForm = function(con, fHideViewBy, fHideFilter)
{
	var fPrintViewBy = (fHideViewBy) ? false : true;
	var fPrintFilterBy = (fHideFilter) ? false : true;

	// Dimension 1
	// ------------------------------------------------------------------------
	var tbl = alib.dom.createElement("table", con);
	var tbody = alib.dom.createElement("tbody", tbl);

	if (fPrintViewBy && this.customReport == "") // Limit group by to dynamic reports only
	{
		var row_con = alib.dom.createElement("tr", tbody);
		var subopt_con1 = alib.dom.createElement("div"); // Used for group by
		// label 1
		var td = alib.dom.createElement("td", row_con);
		td.innerHTML = "View By:&nbsp;";
		// Field selectr
		var td = alib.dom.createElement("td", row_con);
		var field_sel = alib.dom.createElement("select", td);
		field_sel.report = this;
		field_sel.subopt_con1 = subopt_con1;
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
						this.subopt_con1.style.display = "inline";
						grp = this.report.timegroup_types[3][0]; // default to month
					}
					else
					{
						this.subopt_con1.style.display = "none";
					}
				}
			}
			this.report.dimensions[0] = {field:this.value, group:grp} 
			this.report.changeDimension();
		}
		var fields = this.mainObject.getFields();
		var curdim = (this.dimensions.length) ? this.dimensions[0].field : "";
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].type != "fkey_multi")
			{
				field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, 
															(curdim==fields[i].name)?true:false);
			}
		}
		
		// Subgroup
		var lbl = alib.dom.createElement("span", subopt_con1);
		lbl.innerHTML = "&nbsp;&nbsp;Group By&nbsp;";

		var aggreg_sel = alib.dom.createElement("select", subopt_con1);
		aggreg_sel.report = this;
		aggreg_sel.onchange = function() 
		{ 
			var fld = (this.report.dimensions.length>1) ? this.report.dimensions[0].field : "";
			this.report.dimensions[0] = {field:fld, group:this.value} 
			this.report.changeDimension();
		}
		var curdim_grp = (this.dimensions.length) ? this.dimensions[0].group : "month";
		var fields = this.mainObject.getFields();
		for (var i = 0; i < this.timegroup_types.length; i++)
		{
			aggreg_sel[aggreg_sel.length] = new Option(this.timegroup_types[i][1], this.timegroup_types[i][0], 
														false, (curdim_grp==this.timegroup_types[i][0])?true:false);
		}
		td.appendChild(subopt_con1);
		subopt_con1.style.display = "none";
		if (curdim)
		{
			var field = this.mainObject.getFieldByName(curdim);
			if (field)
			{
				if (field.type == "date" || field.type == "timestamp")
					subopt_con1.style.display = "inline";
				else
					subopt_con1.style.display = "none";
			}
		}
	}

	// Dimension 2
	// ------------------------------------------------------------------------
	if (fPrintViewBy && this.dimensions.length>1 && this.customReport=="")
	{
		var row_con = alib.dom.createElement("tr", tbody);
		var subopt_con2 = alib.dom.createElement("div"); // Used for group by
		var td = alib.dom.createElement("td", row_con);
		td.innerHTML = "Then By:&nbsp;";
		// Field selectr
		var td = alib.dom.createElement("td", row_con);
		var field_sel2 = alib.dom.createElement("select", td);
		field_sel2.report = this;
		field_sel2.subopt_con2 = subopt_con2;
		field_sel2.mainObject = this.mainObject;
		field_sel2.onchange = function() 
		{ 
			var grp = "";
			if (this.value)
			{
				var field = this.mainObject.getFieldByName(this.value);
				if (field)
				{
					if (field.type == "date" || field.type == "timestamp")
					{
						this.subopt_con2.style.display = "inline";
						grp = this.report.timegroup_types[3][0]; // default to month
					}
					else
						this.subopt_con2.style.display = "none";
				}
			}
			this.report.dimensions[1] = {field:this.value, group:grp} 
			this.report.changeDimension();
		}
		var fields = this.mainObject.getFields();
		var curdim = (this.dimensions.length>1) ? this.dimensions[1].field : "";
		var curdim_grp = (this.dimensions.length>1) ? this.dimensions[1].group : "";
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].type != "fkey_multi")
			{
				field_sel2[field_sel2.length] = new Option(fields[i].title, fields[i].name, false, 
															(curdim==fields[i].name)?true:false);
			}
		}

		// Subgroup
		var lbl = alib.dom.createElement("span", subopt_con2);
		lbl.innerHTML = "&nbsp;&nbsp;Group By&nbsp;";

		var aggreg_sel = alib.dom.createElement("select", subopt_con2);
		aggreg_sel.report = this;
		aggreg_sel.onchange = function() 
		{ 
			var fld = (this.report.dimensions.length>1) ? this.report.dimensions[1].field : "";
			this.report.dimensions[1] = {field:fld, group:this.value} 
			this.report.changeDimension();
		}
		var curdim_grp = (this.dimensions.length>1) ? this.dimensions[1].group : "";
		var fields = this.mainObject.getFields();
		for (var i = 0; i < this.timegroup_types.length; i++)
		{
			aggreg_sel[aggreg_sel.length] = new Option(this.timegroup_types[i][1], this.timegroup_types[i][0], 
														false, (curdim_grp==this.timegroup_types[i][0])?true:false);
		}
		td.appendChild(subopt_con2);
		subopt_con2.style.display = "none";
		if (curdim)
		{
			var field = this.mainObject.getFieldByName(curdim);
			if (field)
			{
				if (field.type == "date" || field.type == "timestamp")
					subopt_con2.style.display = "inline";
				else
					subopt_con2.style.display = "none";
			}
		}
	}
	

	//-----------------------------------------------------------------
	//	Add filter
	//-----------------------------------------------------------------
	var conds = (this.view) ? this.view.conditions : null;

	var filter_row = alib.dom.createElement("tr", tbody);
	filter_row.vAlign = "top";

	var lbl = alib.dom.createElement("td", filter_row);
	lbl.innerHTML = "Filter By:";
	
	var filter_con = alib.dom.createElement("td", filter_row);

	var fltr_query_con = alib.dom.createElement("div", filter_con);
	var fltr_btn_con = alib.dom.createElement("div", filter_con);

	// Build filter container
	// ------------------------------------------------------------
	alib.dom.styleSet(fltr_query_con, "display", "none");
	var conditionObj = this.mainObject.buildAdvancedQuery(fltr_query_con, conds);

	var act_dv = alib.dom.createElement("div", fltr_query_con);
	alib.dom.styleSet(act_dv, "text-align", "right");

	// Go link
	var lnk = alib.dom.createElement("a", act_dv);
	lnk.innerHTML = "Apply Filter";
	lnk.href = "javascript:void(0);";
	lnk.conditionObj = conditionObj;
	lnk.fltr_btn_con = fltr_btn_con;
	lnk.fltr_query_con = fltr_query_con;
	lnk.cls = this;
	lnk.onclick = function()
	{
		this.fltr_btn_con.style.display = "block";
		this.fltr_query_con.style.display = "none";

		var tmpcon = this.conditionObj.getCondDesc(this.cls.obj_type);
		this.fltr_btn_con.innerHTML = "";
		this.fltr_btn_con.appendChild(tmpcon);
		tmpcon = alib.dom.createElement("span", this.fltr_btn_con);
		tmpcon.innerHTML = "&nbsp;&nbsp;<a href='javascript:void(0);'>[click to edit]</a>";

		this.cls.view.conditions = new Array();
		for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
		{
			var cond = this.conditionObj.getCondition(i);
			this.cls.view.conditions[i] = new Object();
			this.cls.view.conditions[i].blogic = cond.blogic;
			this.cls.view.conditions[i].fieldName = cond.fieldName;
			this.cls.view.conditions[i].operator = cond.operator;
			this.cls.view.conditions[i].condValue = cond.condValue;
		}

		this.cls.changeDimension();
		this.cls.onConditionChange();
	}

	// Cancel link
	var sp = alib.dom.createElement("span", act_dv);
	sp.innerHTML = "&nbsp;";
	
	var lnk = alib.dom.createElement("a", act_dv);
	lnk.innerHTML = "Cancel Edit";
	lnk.fltr_btn_con = fltr_btn_con;
	lnk.fltr_query_con = fltr_query_con;
	lnk.href = "javascript:void(0);";
	lnk.onclick = function()
	{
		this.fltr_btn_con.style.display = "block";
		this.fltr_query_con.style.display = "none";
	}

	// Build condition string and "Edit Filter" button
	// ------------------------------------------------------------
	fltr_btn_con.fltr_query_con = fltr_query_con;
	fltr_btn_con.innerHTML = "";
	var tmpcon = conditionObj.getCondDesc(this.obj_type);
	fltr_btn_con.appendChild(tmpcon);
	tmpcon = alib.dom.createElement("span", fltr_btn_con);
	tmpcon.innerHTML = "&nbsp;&nbsp;<a href='javascript:void(0);'>[click to edit]</a>";
	fltr_btn_con.onclick = function()
	{
		this.fltr_query_con.style.display = "block";
		this.style.display = "none";
	}
}

/**************************************************************************
* Function: 	changeDimension	
*
* Purpose:		Change dimension parameters
**************************************************************************/
CReport.prototype.changeDimension = function()
{
	this.loadCube(true);
}

/**************************************************************************
* Function: 	save	
*
* Purpose:		Save report to backend
**************************************************************************/
CReport.prototype.save = function()
{
	function cbdone(ret, cls, dlg)
	{
		dlg.hide();

		if (ret)
		{
			cls.id = ret;
			if (cls.view)
			{
				cls.view.reportId = ret;
				cls.view.save();
			}
			cls.onsave(ret);
		}
	}

	var args = [["name", this.name], ["description", this.description], ["obj_type", this.obj_type], 
				["scope", this.scope],
				["chart_type", this.chart_type],
				["f_display_table", (this.fDisplayTable)?'t':'f'],
				["f_display_chart", (this.fDisplayChart)?'t':'f'],
				["f_calculate", (this.fCalculate)?'t':'f']];

	if (this.dimensions.length)
	{
		args[args.length] = ["dim_one_fld", this.dimensions[0].field];
		args[args.length] = ["dim_one_grp", this.dimensions[0].group];
	}
	if (this.dimensions.length>1)
	{
		args[args.length] = ["dim_two_fld", this.dimensions[1].field];
		args[args.length] = ["dim_two_grp", this.dimensions[1].group];
	}
	if (this.measures.length)
	{
		args[args.length] = ["measure_one_fld", this.measures[0].field];
		args[args.length] = ["measure_one_agg", this.measures[0].aggregate];
	}

	if (this.id)
		args[args.length] = ["rid", this.id];

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving report, please wait...";
	dlg.statusDialog(dv_load, 250, 100);

	/*
	ALib.m_debug = true;
	AJAX_TRACE_RESPONSE = true;
	*/

	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "report_save", args, cbdone, [this, dlg], AJAX_POST);
}

/**************************************************************************
* Function: 	addFilterCondition
*
* Purpose:		Add a condition to filter by
**************************************************************************/
CReport.prototype.addFilterCondition = function(blogic, fieldName, operator, condValue) 
{
	var ind = this.conditions.length;
	this.conditions[ind] = new Object();
	this.conditions[ind].blogic = blogic;
	this.conditions[ind].fieldName =fieldName;
	this.conditions[ind].operator = operator;
	this.conditions[ind].condValue = condValue;
}

/**************************************************************************
* Function: 	onsave	
*
* Purpose:		To be over-ridden by calling process to detect when
* 				definition is finished saving.
**************************************************************************/
CReport.prototype.onsave = function(rid)
{
}

/**************************************************************************
* Function: 	onload	
*
* Purpose:		To be over-ridden by calling process to detect when
* 				definition is finished loading.
**************************************************************************/
CReport.prototype.onload = function()
{
}

/**************************************************************************
* Function: 	onloadError	
*
* Purpose:		Handle load errors
**************************************************************************/
CReport.prototype.onloadError = function(id, message)
{
}

/**************************************************************************
* Function: 	onCubeUpdate	
*
* Purpose:		To be over-ridden by calling process to detect when
* 				the dimensioins have been changed by the micro-form
**************************************************************************/
CReport.prototype.onCubeUpdate = function()
{
}

/**************************************************************************
* Function: 	onConditionChange	
*
* Purpose:		To be over-ridden by calling process to detect when
* 				the condtions for this report have been modified
**************************************************************************/
CReport.prototype.onConditionChange = function()
{
}
