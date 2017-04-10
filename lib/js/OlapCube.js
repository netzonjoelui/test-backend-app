/**
 * @fileoverview This is the base class for olap cubes in ANT
 *
 * There are a number of different engines used to generate the data for this cube found in /lib/js/OlaCube/*
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of OlapCube
 *
 * @constructor
 * @param {array} measures Array of of objects [{field, aggregate}]
 */
function OlapCube(objectType, custRpt, dwCube)
{
	/*
	this.dimensions = new Array();
	this.measures = measures; // array of objects{field, aggregate}
	this.conditions = measures; // array of objects{field, aggregate}
	*/

	/**
	 * Temporarily store dimensions used in the last query
	 *
	 * @var {array}
	 */
	this.tmpQueryDimensions = new Array();

    this.displayChart = true;
	this.chartHtml = "";
	this.chartType = "";
    this.chartWidth = 800; // default for report
	this.chartHeight = 400; // default for report

	this.obj_type = (objectType) ? objectType : ""; // Ad-hock object queries
	this.customReport = (custRpt) ? custRpt : ""; // used to load custom data
	this.datawareCube = (dwCube) ? dwCube : ""; // path to a prebuilt olap cube in the datawarehouse (optional)

	/**
	 * Variable used to store the data returned from a query
	 *
	 * @private
	 * @var {Object}
	 */
    this.cubeData = null;

	/**
	 * Used to store callback references
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();
}

/**
 * Load data for this cube
 *
 * @param {OlapCube_Query} query Query object
 */
OlapCube.prototype.loadData = function(query, tabular)
{
	if (typeof tabular == "undefined") var tabular = false;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(data)
	{
		this.cbData.cls.cubeData = data;
		this.cbData.cls.onload(this.cbData.cls.cubeData);
	};

	var args = this.getArgParams(query);    
	if (tabular)
		args[args.length] = ["format", "tabular"];
	/*
	ALib.m_debug = true;
	AJAX_TRACE_RESPONSE = true;
	*/
	ajax.exec("/controller/Olap/queryCubeJson", args);

	this.tmpQueryDimensions = query.dimensions;
}

/**
 * Load tabular data
 *
 * @param {OlapCube_Query} query Query object
 */
OlapCube.prototype.loadTabularData = function(query)
{
	this.loadData(query, true);
}

/**
 * Load Graph using Olap Cube
 *  
 * @param {OlapCube_Query} query Query object
 */
OlapCube.prototype.loadOlapGraph = function(query)
{    
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(data)
    {
        if(data.chart)
        {
            this.cbData.cls.chartHtml = data.chart;
            this.cbData.cls.onload(this.cbData.cls.chartHtml);
        }
        else
        {
            this.cbData.cls.displayChart = false;
            this.cbData.cls.chartHtml = data.message;
            this.cbData.cls.onload(this.cbData.cls.chartHtml);
        }
    };
    
    var args = this.getArgParams(query);
    args[args.length] = ["chart_type", this.chartType];
    args[args.length] = ["chart_width", this.chartWidth];
    args[args.length] = ["chart_height", this.chartHeight];
    args[args.length] = ["display_graph", true];
    /*
    ALib.m_debug = true;
    AJAX_TRACE_RESPONSE = true;
    */

    ajax.exec("/controller/Olap/processGraphDisplay", args);
}

/**
 * Get available dimensions for this cube
 *
 * @param {OlapCube_Query} query The query definition for pulling
 * @return {array} Params to be sent to server for this cube
 */
OlapCube.prototype.getArgParams = function(query)
{
	var args = new Array();
	args[args.length] = ["obj_type", this.obj_type];
	args[args.length] = ["customReport", this.customReport];
	args[args.length] = ["datawareCube", this.datawareCube];

	// Set filters
	for (var i = 0; i < query.filters.length; i++)
	{
		var cond = query.filters[i];
		args[args.length] = ["filters[]", i];
		args[args.length] = ["filter_blogic_"+i, cond.blogic];
		args[args.length] = ["filter_field_"+i, cond.field];
		args[args.length] = ["filter_operator_"+i, cond.operator];
		args[args.length] = ["filter_condition_"+i, cond.condition];
	}

	// Set measures
	for (var i = 0; i < query.measures.length; i++)
	{
		args[args.length] = ["measures[]", i];
		args[args.length] = ["measure_name_"+i, query.measures[i].name];
		args[args.length] = ["measure_aggregate_"+i, query.measures[i].aggregate];
	}

	// Set dimensions
	for (var i = 0; i < query.dimensions.length; i++)
	{
		args[args.length] = ["dimensions[]", i];
		args[args.length] = ["dimension_name_"+i, query.dimensions[i].name];
		args[args.length] = ["dimension_sort_"+i, query.dimensions[i].sort];
		args[args.length] = ["dimension_fun_"+i, query.dimensions[i].fun];
	}


	return args;
}

/**
 * Get available dimensions for this cube
 */
OlapCube.prototype.getDimensions = function()
{
}

/**
 * Get available measures for this cube
 */
OlapCube.prototype.getMeasures = function()
{
}

/**
 * Callback function called when code data has finished loading
 */
OlapCube.prototype.onload = function(data)
{
}

/**
 * Print chart into innerHTML of container (con)
 *
 * @param {DOMElement} con Container that will house the chart/graph
 */
OlapCube.prototype.printChart = function(con)
{
	con.innerHTML = this.chartHtml;
}

/**
 * Print a summary table
 *
 * @param {DOMElement} con The container that will house the summary table
 */
OlapCube.prototype.printTable = function(con)
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

/**
 * Get the value at a specified dimension
 *
 * @param {string} d1 The field name of the dimension 1
 * @param {string} d2 Optional field name of the second dimension
 * @param {number} measureind The index of the measure to pull (default to 0)
 */
OlapCube.prototype.getValue = function(d1, d2, measureind)
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

/**
 * Get the value at a specified dimension
 *
 * @param {string} dname The field name of the dimension to get children for
 */
OlapCube.prototype.getDimChildren = function(dname)
{
	// Set class property to store values
	this.dimEachChildren = new Array();

	// Get level
	var lvl = -1;
	for (var i = 0; i < this.tmpQueryDimensions.length; i++)
	{
		if (dname == this.tmpQueryDimensions[i].name)
			lvl = i;
	}

	if (-1 == lvl) // dim not found
		return false;

	this.getDimChildrenBydDepth(this.cubeData, lvl, 0);

	// TODO: now sort the values
	
	var tmpArr = this.dimEachChildren;
	this.dimEachChildren = null; // cleanup
	return tmpArr;
}

/**
 * Get the values of a dimension at a specified depth
 *
 * @param {string} dname The field name of the dimension to get children for
 */
OlapCube.prototype.getDimChildrenBydDepth = function(data, gotoDepth, currDepth)
{
	var cd = (currDepth) ? currDepth : 0;

	if (gotoDepth == cd)
	{
		for(var index in data) 
		{
			var bfound = false;
			for (var i = 0; i < this.dimEachChildren.length; i++)
			{
				if (this.dimEachChildren[i] == index)
					bfound = true;
			}

			if (!bfound)
				this.dimEachChildren.push(index);
		}
	}
	else
	{
		// Dig another level deeper
		for(var index in data) 
		{
			this.getDimChildrenBydDepth(data[index], gotoDepth, (cd + 1));
		}
	}

}

 /**
  * Loads the dimension and measures of the olap object/dataware
  *
  * @public
  * @this {OlapCube}
  * @param {type} name   description
  */
OlapCube.prototype.loadDefinition = function()
{
    var args = new Array();
    
    if(this.obj_type)
        args[args.length] = ['obj_type', this.obj_type];
        
    if(this.datawareCube)
        args[args.length] = ['datawareCube', this.datawareCube];
        
    if(this.customReport)
        args[args.length] = ['customReport', this.customReport];
        
    ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.onload = function(ret)
    {
        this.cbData.cls.onloadDefinition(ret.dimensions, ret.measures);
    };
    ajax.exec("/controller/Olap/getCubeData", args);
}

/**
* To be over-ridden by calling process to detect when definition is finished loading.
*
* @public
* @this {OlapCube} 
* @param {Object} dimensions    Object contains dimension data
* @param {Object} measures      Object contains measures data
*/
OlapCube.prototype.onloadDefinition = function(dimensions, measures)
{    
}
