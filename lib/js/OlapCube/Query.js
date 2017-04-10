/**
 * @fileoverview This object is used to build queries for olap cubes
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
function OlapCube_Query()
{
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
	* Dimensions to pull
	*
	* Properties of each element:
	* .name = the name of the measure
	* .sort = 'asc' | 'desc'
	* .fun = optional function - usually used for formatting time-series dimensions
	*
	* @public
	* @type {Object[]}
	*/
	this.dimensions = new Array();

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
 * Add a measure to this query
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) agg The aggregate function to use
 */
OlapCube_Query.prototype.addMeasure = function(dname, agg)
{
	var a = (agg) ? agg : "sum";
	this.measures[this.measures.length] = {name:dname, aggregate:a};
}

/**
 * Add a dimension to the query
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) s The order to sort this dimension in. Can be 'asc' or 'desc'. Defaults to 'asc'
 * @param (string) f Optional formatting function to use
 */
OlapCube_Query.prototype.addDimension = function(dname, s, f)
{
	this.dimensions[this.dimensions.length] = {name:dname, sort:s, fun:f};
}

/**
 * Add a filter condition
 *
 * @param {string} bl The boolean logic to use. Can be 'and' or 'or'
 * @param (string) fld The field/dimension name to query
 * @param (string) op ANT operator to use
 * @param (string) cond The condition to query against
 */
OlapCube_Query.prototype.addFilter = function(bl, fld, op, cond)
{
	this.filters[this.filters.length] = {blogic:bl, field:fld, operator:op, condition:cond};
}
