/**
 * @fileoverview This table creates a tabular view from a populated OlapCube
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
function OlapCube_Table_Tabular(cube)
{
	/**
	* Data cube we are working with
	*
	* @private
	* @type {OlapCube}
	*/
	this.cube = new OlapCube(cube.obj_type, cube.customReport, cube.datawareCube);


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
OlapCube_Table_Tabular.prototype.print = function(con, loaded)
{
	if (typeof loaded == "undefined") loaded = false;

	// Check to see if the data cube has been loaded
	if (!loaded)
	{
		con.innerHTML = "Loading....";

		this.cube.cbData.sumTable = this;
		this.cube.cbData.con = con;

		this.cube.onload = function()
		{
			this.cbData.sumTable.print(this.cbData.con, true);
		}

		var query = new OlapCube_Query();

		for (var i = 0; i < this.columns.length; i++)
			query.addDimension(this.columns[i].name, this.columns[i].sort, this.columns[i].fun);

		for (var i = 0; i < this.filters.length; i++)
			query.addFilter(this.filters[i].blogic, this.filters[i].field, this.filters[i].operator, this.filters[i].condition);

		this.cube.loadTabularData(query);
		return;
	}

	con.innerHTML = "";

	/// Get data
	// Build table
	var tbl = alib.dom.createElement("table", con);
	var tbody = alib.dom.createElement("tbody", tbl);
	this.tbody = tbody;

	// Print column headers
	// ---------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// first make empty headers for the number of rows
	for (var i = 0; i < this.columns.length; i++)
		var td = alib.dom.createElement("th", row, this.columns[i].name);

	// Now print each row
	for (var i = 0; i < this.cube.cubeData.length; i++)
	{
		var row = alib.dom.createElement("tr", tbody);

		for (var j = 0; j < this.columns.length; j++)
		{
			var val = (this.cube.cubeData[i][this.columns[j].name]) ? this.cube.cubeData[i][this.columns[j].name] : ""
			var td = alib.dom.createElement("td", row, val);
		}
	}
}

/**
 * Add a column dimension to the table
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) s The order to sort this dimension in. Can be 'asc' or 'desc'. Defaults to 'asc'
 * @param (string) f Optional formatting function to use
 */
OlapCube_Table_Tabular.prototype.addColumn = function(dname, s, f)
{
	this.columns[this.columns.length] = {name:dname, sort:s, fun:f};
}

/**
 * Add a filter condition
 *
 * @param {string} bl The boolean logic to use. Can be 'and' or 'or'
 * @param (string) fld The field/dimension name to query
 * @param (string) op ANT operator to use
 * @param (string) cond The condition to query against
 */
OlapCube_Table_Tabular.prototype.addFilter = function(bl, fld, op, cond)
{
	this.filters[this.filters.length] = {blogic:bl, field:fld, operator:op, condition:cond};
}
