/*======================================================================================
	
	Module:		COlapCube

	Purpose:	OnLine Analytical Processing Cube 

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Usage:		var data = new COlap();
				data.load(report_id);
				data.printTable(container);
				data.printChart(container);

======================================================================================*/

function COlapCube(obj_type, view, measures)
{
	this.dimensions = new Array();
	this.measures = measures; // array of objects{field, aggregate}
	this.chartHtml = "";
	this.chart_type = "";
	this.chart_width = 800; // default for report
	this.obj_type = obj_type;
	this.dataXml = null;
	this.view = (view)?view:null;
	this.customReport = ""; // used to load custom data
}

/**************************************************************************
* Function: 	load	
*
* Purpose:		Load data for this cube
*
* Arguments:	d1 = field for dimension 1
* 				d1_grp = optional grouping for dimension 1
*				d2 = field for dimension 2
* 				d2_grp = optional grouping for dimension 2
**************************************************************************/
COlapCube.prototype.load = function(d1, d1_grp, d2, d2_grp, chart_type)
{
	var ajax = new CAjax();
	ajax.m_obj = this;
	ajax.onload = function(root)
	{
		for (var i = 0; i < root.getNumChildren(); i++)
		{
			var section = root.getChildNode(i);

			switch (section.m_name)
			{
			case "dimensions":
				for (var j = 0; j < section.getNumChildren(); j++)
				{
					var dim = section.getChildNode(j);
					var entries_arr = new Array();

					for (var d = 0; d < dim.getNumChildren(); d++)
					{
						var ent = dim.getChildNode(d);
						entries_arr[d] = {value:unescape(ent.getAttribute("value")), label:unescape(ent.getAttribute("label")), total:0};
					}

					this.m_obj.dimensions[j] = {name:unescape(dim.getAttribute("name")), label:unescape(dim.getAttribute("label")), entries:entries_arr};
				}
				break;
			case "chart":
				this.m_obj.chartHtml = unescape(section.m_text);
				break;
			case "data":
				this.m_obj.dataXml = section;
				break;
			case "query":
				if (ALib.m_debug)
					ALib.trace("OLAP Query: " + unescape(section.m_text));
				break;
			}
		}

		this.m_obj.onload();
	};

	var args = new Array();
	if (this.view)
	{
		for (var i = 0; i < this.view.conditions.length; i++)
		{
			var cond = this.view.conditions[i];
			args[args.length] = ["conditions[]", i];
			args[args.length] = ["condition_blogic_"+i, cond.blogic];
			args[args.length] = ["condition_fieldname_"+i, cond.fieldName];
			args[args.length] = ["condition_operator_"+i, cond.operator];
			args[args.length] = ["condition_condvalue_"+i, cond.condValue];
		}
	}

	for (var i = 0; i < this.measures.length; i++)
	{
		args[args.length] = ["measures[]", i];
		args[args.length] = ["measure_field_"+i, this.measures[i].field];
		args[args.length] = ["measure_aggregate_"+i, this.measures[i].aggregate];
	}

	var url = "/datacenter/xml_get_olap.php?obj_type="+this.obj_type;
	url += "&dim1="+d1;
	url += "&dim1_group="+d1_grp;
	url += "&dim2="+d2;
	url += "&dim2_group="+d2_grp;
	url += "&chart_type="+chart_type;
	url += "&chart_width="+this.chart_width;
	url += "&custom_report="+escape(this.customReport);

	/*
	ALib.m_debug = true;
	AJAX_TRACE_RESPONSE = true;
	*/

	ajax.m_method = AJAX_POST;
	ajax.exec(url, args);
}

/**************************************************************************
* Function: 	onload	
*
* Purpose:		To be over-ridden by calling process
**************************************************************************/
COlapCube.prototype.onload = function()
{
}

/**************************************************************************
* Function: 	printChart	
*
* Purpose:		Print chart into innerXML of container
*
* Arguments:	Container where the chart will reside
**************************************************************************/
COlapCube.prototype.printChart = function(con)
{
	con.innerHTML = this.chartHtml;
}

/**************************************************************************
* Function: 	printTable	
*
* Purpose:		Print a summary table
*
* Arguments:	Container where the chart will reside
**************************************************************************/
COlapCube.prototype.printTable = function(con)
{
	// Print table
	var tbl = new CToolTable("100%");
	tbl.print(con);

	// Print headers for dimension 2 if exists
	if (this.dimensions[1] && this.dimensions[1].entries.length)
	{
		tbl.addHeader("&nbsp;", "right");
		//var rw = tbl.addRow();
		for (var j = 0; j < this.dimensions[1].entries.length; j++)
		{
			tbl.addHeader(this.dimensions[1].entries[j].label, "right");
		}
		tbl.addHeader("Total", "right");
	}
	var val = 0;

	var totalOfTotals = 0;
	//ALib.m_debug = true;
	// Print data
	for (var i = 0; i < this.dimensions[0].entries.length; i++)
	{
		var rw = tbl.addRow();
		var d1 = this.dimensions[0].entries[i].value;

		rw.addCell(this.dimensions[0].entries[i].label, true);

		if (this.dimensions[1] && this.dimensions[1].entries.length)
		{
			var linetotal = 0;
			for (var j = 0; j < this.dimensions[1].entries.length; j++)
			{
				val = this.getValue(d1, this.dimensions[1].entries[j].value, 0);
				//ALib.trace(val);
				//val = new NumberFormat(String(val), 0).toFormatted();
				rw.addCell(String(val), false, "right");
				linetotal += val;
				this.dimensions[1].entries[j].total += val;
			}
			//var val = new NumberFormat(String(linetotal), 0).toFormatted();
			//rw.addCell(val, true, "right");
			rw.addCell(String(linetotal), true, "right");
			totalOfTotals += linetotal;
		}
		else
		{
			val = this.getValue(d1, null, 0);
			rw.addCell(String(val), false, "right");
			totalOfTotals += val;
		}
	}

	// Print dimension2 totals
	if (this.dimensions[1])
	{
		var rw = tbl.addRow();
		rw.addCell("Total", true, "right");
		for (var j = 0; j < this.dimensions[1].entries.length; j++)
		{
			//var val = new NumberFormat(String(this.dimensions[1].entries[j].total), 0).toFormatted();
			//rw.addCell(val, false, "right");
			var val = this.dimensions[1].entries[j].total;
			rw.addCell(String(val), false, "right");
		}
		//var val = new NumberFormat(String(totalOfTotals), 0).toFormatted();
		//rw.addCell(val, true, "right");
		rw.addCell(String(totalOfTotals), true, "right");
	}
	else
	{
		var rw = tbl.addRow();
		rw.addCell("Total", true, "right");
		//var val = new NumberFormat(String(totalOfTotals), 0).toFormatted();
		rw.addCell(String(totalOfTotals), true, "right");
	}
}

/**************************************************************************
* Function: 	getValue	
*
* Purpose:		Get the value at a specified dimension
*
* Arguments:	dimen
**************************************************************************/
COlapCube.prototype.getValue = function(d1, d2, measureind)
{
	if (!this.dataXml)
		return "";

	for (var i = 0; i < this.dataXml.getNumChildren(); i++)
	{
		var dim = this.dataXml.getChildNode(i);
		var val = unescape(dim.getAttribute("value"));
		if (val == d1)
		{
			if (d2)
			{
				for (var j = 0; j < dim.getNumChildren(); j++)
				{
					var dim2 = dim.getChildNode(j);
					var val2 = unescape(dim2.getAttribute("value"));

					if (val2 == d2)
					{
						var meas_val = unescape(dim2.getChildNode(measureind).m_text);
						if (meas_val)
							meas_val = parseFloat(meas_val);
						else
							meas_val = 0;
						return meas_val;
					}
				}
			}
			else
			{
				var meas_val = unescape(dim.getChildNode(measureind).m_text);
				if (meas_val)
					meas_val = parseFloat(meas_val);
				else
					meas_val = 0;
				return meas_val;
			}
		}
	}

	return 0;
}
