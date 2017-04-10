/**
 * @fileoverview This table creates a pivot/summary view from a populated OlapCube
 *
 * This class will take data from an OlapCube and print it out in a matrix/pivot table. 
 *
 * Example:
 *                      | Column 1  |  Column 2  | Total
 * -------------------------------------------------------
 * Row 1  | Row 1.1     | 1         | 2          | 3
 * -------------------------------------------------------
 *        | Row 1.2     | 3         | 2          | 5
 * -------------------------------------------------------
 *                Total | 4         | 5          | 8    
 * -------------------------------------------------------
 * Row 2  | Row 2.1     | 4         | 2          | 6
 * -------------------------------------------------------
 *        | Row 2.2     | 1         | 1          | 2
 * -------------------------------------------------------
 *                Total | 5         | 3          | 8    
 * -------------------------------------------------------
 *          Grand Total | 9         | 8          | 16 
 *
 * The data from the cube will be in the following structure:
 * [Row 1][Row1.1][Column 1][Column 2]["measure"]
 * [Row 1][Row1.2][Column 1][Column 2]["measure"]
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */

/**
 * Class constructor
 *
 * @constructor
 * @param {OlapCube} cube The OlapCube we will be working with to create this table
 */
function OlapCube_Table_Matrix(cube)
{
	/**
	* Data cube we are working with.
	*
	* We make a copy to try and keep the parameters all localized
	*
	* @private
	* @type {OlapCube}
	*/
	this.cube = new OlapCube(cube.obj_type, cube.customReport, cube.datawareCube);

	/**
	* Show subtotals
	*
	* @public
	* @type {bool}
	*/
	this.showSubtotals = true;

	/**
	* Array holding the grand totals of each measure
	*
	* @public
	* @type {number[]}
	*/
	this.grandTotalsCols = new Array();

	/**
	* Array holding the grand totals of each measure
	*
	* @public
	* @type {number[]}
	*/
	this.grandTotalsRows = new Array();

	/**
	* Array that stores reference to columns by dim name then value
	*
	* @private
	* @type {array[string[]]}
	*/
	this.dimColChildren = new Array();

	/**
	* Measures to include in results
	*
	* Properties of each element:
	* .name = the name of the measure
	* .aggregate = 'sum' | 'avg' | 'max' | 'min'
	*
	* @public
	* @type {Object[]}
	*/
	this.measures = new Array();

	/**
	* Dimensions for rows
	*
	* Properties of each element:
	* .name = the name of the measure
	* .sort = 'asc' | 'desc'
	* .fun = optional function - usually used for formatting time-series dimensions
	*
	* @public
	* @type {Object[]}
	*/
	this.rows = new Array();

	/**
	* Dimensions for columns
	*
	* Properties of each element:
	* .name = the name of the measure
	* .sort = 'asc' | 'desc'
	* .fun = optional function - usually used for formatting time-series dimensions
	*
	* @public
	* @type {Object[]}
	*/
	this.columns = new Array();

	/**
	* Filters or conditions
	*
	* Properties of each element:
	* .blogic = 'and' | 'or'
	* .field = The name of the field or dimension to check against
	* .operator = Any standard ANT query operators
	* .condition = The conditional text to query against.
	*
	* @public
	* @type {Object[]}
	*/
	this.filters = new Array();
}

/**
 * Print the contents of this matrix report into 'con'
 *
 * @param {DOMElement} con The element to print table in
 * @param (bool) loaded Used for recurrsive calling to make sure data is loaded before building the table
 */
OlapCube_Table_Matrix.prototype.print = function(con, loaded)
{
	if (typeof loaded == "undefined") loaded = false;

	// Check to see if the data cube has been loaded
	if (!loaded)
	{
		con.innerHTML = "Loading....";

		this.cube.cbData.matTable = this;
		this.cube.cbData.con = con;

		this.cube.onload = function()
		{
			this.cbData.matTable.print(this.cbData.con, true);
		}

		var query = new OlapCube_Query();

		for (var i = 0; i < this.measures.length; i++)
			query.addMeasure(this.measures[i].name, this.measures[i].aggregate);

		for (var i = 0; i < this.rows.length; i++)
			query.addDimension(this.rows[i].name, this.rows[i].sort, this.rows[i].fun);

		for (var i = 0; i < this.columns.length; i++)
			query.addDimension(this.columns[i].name, this.columns[i].sort, this.columns[i].fun);

		for (var i = 0; i < this.filters.length; i++)
			query.addFilter(this.filters[i].blogic, this.filters[i].field, this.filters[i].operator, this.filters[i].condition);

		this.cube.loadData(query);
		return;
	}

	con.innerHTML = "";

	/// Get data
	var rows = new Array();
	for (var i = 0; i < this.rows.length; i++)
		rows[rows.length] = this.rows[i].name;

	var cols = new Array();
	for (var i = 0; i < this.columns.length; i++)
		cols[cols.length] = this.columns[i].name;

	var measures = new Array();
	for (var i = 0; i < this.measures.length; i++)
		measures[measures.length] = this.measures[i].name;

	con.innerHTML = "";
	var tbl = alib.dom.createElement("table", con);
	var tbody = alib.dom.createElement("tbody", tbl);
	this.tbody = tbody;

	// Print column headers for measures
	// ---------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// first make empty headers for the number of rows
	if (rows.length)
	{
		var td = alib.dom.createElement("th", row);
		td.setAttribute("colspan", rows.length);
		td.innerHTML = "&nbsp;";
	}
	// Now print a header for each measure/data entry
	for (var i = 0; i < cols.length; i++)
	{
		var dimChildren = this.cube.getDimChildren(cols[i]);
		this.dimColChildren = dimChildren;

		for (var j = 0; j < dimChildren.length; j++)
		{
			var td = alib.dom.createElement("th", row);
			td.innerHTML = dimChildren[j];
		}
	}

	// Now build table
	this.populateTableRows(this.cube.cubeData, rows, cols, measures, 0, false);

	// Now show grand totals
	if (this.grandTotalsCols)
	{
		var row = alib.dom.createElement("tr", this.tbody);
		// Print spacers
		for (var j = 1; j < measures.length; j++)
			var td = alib.dom.createElement("td", row, "&nbsp;");

		var td = alib.dom.createElement("td", row, "Grand Total:");
		alib.dom.styleSet(td, 'text-align', 'right');

		var grandOfCols = 0;
		for(var index in this.grandTotalsCols) 
		{
			var td = alib.dom.createElement("td", row, String(this.grandTotalsCols[index]));
			alib.dom.styleSet(td, 'text-align', 'right');
			grandOfCols += this.grandTotalsCols[index];
		}

		if (this.grandTotalsRows)
		{
			var td = alib.dom.createElement("td", row, String(grandOfCols));
			alib.dom.styleSet(td, 'text-align', 'right');
		}
	}
}

/**
 * Print the contents of this matrix report into 'con'
 *
 * @param {DOMElement} con The element to print table in
 * @param {string[]} rows Array of dimensions used for each row level
 * @param (string[]) data Array of measures that are to be summarized by
 */
OlapCube_Table_Matrix.prototype.populateTableRows = function(data, rows, cols, measures, lvl, domRow)
{
	// Check if we are in the last dimension
	var dimRowsFinished = (lvl >= rows.length) ? true : false;
	var dimFinished = (lvl >= (rows.length + cols.length)) ? true : false;
	var colTotals = new Array();

	// Loop through this level data and create cells
	var i = 0;
	for(var index in data) 
	{
		var row = (domRow) ? domRow : alib.dom.createElement("tr", this.tbody);

		// If we are not on the first item, then print spacers below parent
		if (i>0 && lvl>0 && !dimRowsFinished)
		{
			for (var j = 0; j < lvl; j++)
			{
				var td = alib.dom.createElement("td", row);
				td.innerHTML = "&nbsp;";
			}
		}

		var rowCell = alib.dom.createElement("td", row);
		rowCell.innerHTML = index;

		// Check if we are on the last dimension
		if ((lvl + 1) >= rows.length)
		{
			var rowTotal = 0;
			// Print measures for this dimension
			for (var j = 0; j < this.dimColChildren.length; j++)
			{
				var val = this.dimColChildren[j];

				var measureVal = this.getColMeasure(data[index], val, measures);

				var td = alib.dom.createElement("td", row);
				alib.dom.styleSet(td, 'text-align', 'right');
				td.innerHTML = String(measureVal);

				// Set column totals
				if (!colTotals[val])
					colTotals[val] = 0;
				colTotals[val] += parseFloat(measureVal);

				// Set row torowTotaltals
				rowTotal += parseFloat(measureVal);

				// Set grand totals
				if (!this.grandTotalsCols[val])
					this.grandTotalsCols[val] = 0;
				this.grandTotalsCols[val] += parseFloat(measureVal);
			}

			if (this.grandTotalsRows)
			{
				var td = alib.dom.createElement("td", row);
				alib.dom.styleSet(td, 'text-align', 'right');
				td.innerHTML = String(rowTotal);
			}
		}
		else
		{

			// Loop through next dimension
			this.populateTableRows(data[index], rows, cols, measures, (lvl + 1), row);
		}

		i++;
		domRow = null; // only first element may print on the parent row
	}

	// If we just printed the measures, then also print colTotals
	/*
	if (dimRowsFinished && this.showSubtotals)
	{
		var row = alib.dom.createElement("tr", this.tbody);
		// Print spacers
		for (var j = 0; j < lvl; j++)
			var td = alib.dom.createElement("td", row, "&nbsp;");

		var td = alib.dom.createElement("td", row, "Total:");
		alib.dom.styleSet(td, 'text-align', 'right');

		for(var index in colTotals) 
		{
			var td = alib.dom.createElement("td", row, String(colTotals[index]));
			alib.dom.styleSet(td, 'text-align', 'right');
		}
	}
	*/
}

/**
 * Get the measure for a specified column
 */
OlapCube_Table_Matrix.prototype.getColMeasure = function(data, val, measures)
{
	ret = 0;

	// check to see if 'val' is
	// an array and then dig deeper if it is and print a cell for each end value

	if (data[val])
	   ret = data[val][measures[0]];


	return ret;
}

/**
 * Print the contents of this matrix report into 'con'
 *
 * @param {DOMElement} con The element to print table in
 * @param {string[]} rows Array of dimensions used for each row level
 * @param (string[]) data Array of measures that are to be summarized by
 */
OlapCube_Table_Matrix.prototype.populateTableCols = function(data, rows, cols, measures, lvl, domRow)
{
	// Check if we are in the last dimension
	var dimFinished = (lvl >= rows.length) ? true : false;
	var totals = new Array();

	// Loop through this level data and create cells
	var i = 0;
	for(var index in data) 
	{
		var row = (domRow) ? domRow : alib.dom.createElement("tr", this.tbody);

		// If we are not on the first item, then print spacers below parent
		if (i>0 && lvl>0)
		{
			for (var j = 0; j < lvl; j++)
			{
				var td = alib.dom.createElement("td", row);
				td.innerHTML = "&nbsp;";
			}
		}

		var rowCell = alib.dom.createElement("td", row);
		rowCell.innerHTML = index;

		// Check if we are on the last dimension
		if (dimFinished)
		{
			// Print measures for this dimension
			for (var j = 0; j < cols.length; j++)
			{
				var td = alib.dom.createElement("td", row);
				alib.dom.styleSet(td, 'text-align', 'right');
				td.innerHTML = String(data[index][measures[j]]);

				// Set subtotals and grand totals
				if (!totals[measures[j]])
					totals[measures[j]] = 0;
				totals[measures[j]] += parseFloat(data[index][measures[j]]);

				if (!this.grandTotalsCols[measures[j]])
					this.grandTotalsCols[measures[j]] = 0;
				this.grandTotalsCols[measures[j]] += parseFloat(data[index][measures[j]]);
			}

		}
		else
		{
			// Loop through next dimension
			this.populateTableRows(data[index], rows, cols, measures, (lvl + 1), row);
		}

		i++;
		domRow = null; // only first element may print on the parent row
	}

	// If we just printed the measures, then also print totals
	if (dimFinished && this.showTotalRows)
	{
		var row = alib.dom.createElement("tr", this.tbody);
		// Print spacers
		for (var j = 0; j < lvl; j++)
			var td = alib.dom.createElement("td", row, "&nbsp;");

		var td = alib.dom.createElement("td", row, "Total:");
		alib.dom.styleSet(td, 'text-align', 'right');

		for(var index in totals) 
		{
			var td = alib.dom.createElement("td", row, String(totals[index]));
			alib.dom.styleSet(td, 'text-align', 'right');
		}
	}
}

/**
 * Add a measure to use as data
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) agg The aggregate function to use
 */
OlapCube_Table_Matrix.prototype.addMeasure = function(dname, agg)
{
	var a = (agg) ? agg : "sum";
	this.measures[this.measures.length] = {name:dname, aggregate:a};
}

/**
 * Add a column dimension to this matrix table
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) agg The aggregate function to use
 */
OlapCube_Table_Matrix.prototype.addColumn = function(dname, s, f)
{
	this.columns[this.columns.length] = {name:dname, sort:s, fun:f};
}

/**
 * Add a row dimension to the table
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) s The order to sort this dimension in. Can be 'asc' or 'desc'. Defaults to 'asc'
 * @param (string) f Optional formatting function to use
 */
OlapCube_Table_Matrix.prototype.addRow = function(dname, s, f)
{
	this.rows[this.rows.length] = {name:dname, sort:s, fun:f};
}

/**
 * Add a filter condition
 *
 * @param {string} bl The boolean logic to use. Can be 'and' or 'or'
 * @param (string) fld The field/dimension name to query
 * @param (string) op ANT operator to use
 * @param (string) cond The condition to query against
 */
OlapCube_Table_Matrix.prototype.addFilter = function(bl, fld, op, cond)
{
	this.filters[this.filters.length] = {blogic:bl, field:fld, operator:op, condition:cond};
}
