/**
 * @fileoverview This class is used to create all charts from olap cubes
 *
 * All graph related functions to be encapsulated inside this class including
 * getting/setting options, generating the graph, updating, formatting, and even saving/loading data
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of OlapCube
 *
 * @constructor
 * @param {OlapCube} cube The olap cube is required to generate any graphs
 */
function OlapCube_Graph(cube)
{
	/**
	 * The OLAP cube that is used to generate this graph
	 *
	 * @type {OlapCube}
	 */
	this.cube = new OlapCube(cube.obj_type, cube.customReport, cube.datawareCube);
    
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
    this.dimensions = new Array();

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
    this.measures = new Array();

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
    
    this.chartWidth = 800;
    this.chartHeight = 400;
    this.chartType = null;
}

/**
 * Print the graph view
 *
 * @param {DOMElement} con The element to print table in
 * @param (bool) loaded Used for recurrsive calling to make sure data is loaded before building the table
 */
OlapCube_Graph.prototype.print = function(con, loaded)
{    
    if (typeof loaded == "undefined") loaded = false;
    
    // Check to see if the data cube has been loaded
    if (!loaded)
    {        
        alib.dom.styleSet(con, "height", "50px");

        this.cube.cbData.graph = this;
        this.cube.cbData.con = con;

        this.cube.onload = function()
        {
            this.cbData.graph.print(this.cbData.con, true);
        }

        var query = new OlapCube_Query();

        for (var i = 0; i < this.measures.length; i++)
            query.addMeasure(this.measures[i].name, this.measures[i].aggregate);

        for (var i = 0; i < this.dimensions.length; i++)
            query.addDimension(this.dimensions[i].name, this.dimensions[i].sort, this.dimensions[i].fun);

        for (var i = 0; i < this.filters.length; i++)
            query.addFilter(this.filters[i].blogic, this.filters[i].field, this.filters[i].operator, this.filters[i].condition);
        
        if(this.chartType)
        {
            this.cube.chartWidth = this.chartWidth;
			if (this.chartHeight) this.cube.chartHeight = this.chartHeight;
            this.cube.chartType = this.chartType;
            this.cube.loadOlapGraph(query, con);            
        }        
        return;
    }
    
    
    if(this.cube.displayChart)
        alib.dom.styleSet(con, "height", this.chartHeight);
        
    con.innerHTML = this.cube.chartHtml;
}

/**
 * Add a measure to use as data
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) agg The aggregate function to use
 */
OlapCube_Graph.prototype.addMeasure = function(mname, agg)
{
    var a = (agg) ? agg : "sum";
    this.measures[this.measures.length] = {name:mname, aggregate:a};
}

/**
 * Add a Dimension dimension to the table
 *
 * @param {string} dname Name of the dimension/field to use as a measure
 * @param (string) s The order to sort this dimension in. Can be 'asc' or 'desc'. Defaults to 'asc'
 * @param (string) f Optional formatting function to use
 */
OlapCube_Graph.prototype.addDimension = function(dname, s, f)
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
OlapCube_Graph.prototype.addFilter = function(bl, fld, op, cond)
{
    this.filters[this.filters.length] = {blogic:bl, field:fld, operator:op, condition:cond};
}
