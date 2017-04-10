/**
* @fileoverview This is a Report Class Object
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

var TABLE_TABULAR           = "tabular";
var TABLE_SUMMARY           = "summary";
var TABLE_PIVOT             = "pivot_matrix";

var REPORT_TYPE_OBJECT      = "object";
var REPORT_TYPE_DATAWARE    = "dataware";
var REPORT_TYPE_CUSTOM      = "custom";
var GRAPH_TYPE_SINGLE       = "single";
var GRAPH_TYPE_MULTI        = "multi";
var TABLE_TABULAR           = "tabular";
var TABLE_SUMMARY           = "summary";
var TABLE_PIVOT             = "pivot_matrix";
var MEASURE_COUNT           = "count";

/**
* Creates an instance of AntObjectLoader_Report.
*
* @constructor
* @param {id} int Report Id
*/
function Report(id)
{
    this.reportId = id;
    this.mainObject = new CAntObject("report", this.reportId);
    
    this.aggregateData = ["sum", "avg", "max", "min"];
    this.sortData = [["asc", "Ascending"], ["desc", "Descending"]];
    this.formatData = [["Y", "Year"], ["Y Q", "Quarter"], ["m", "Month"], ["Y d", "Day"]];    
    this.dimTypeData = new Object();    
    this.measureData = new Object();
    this.dimensionData = new Object();
    
    this.filterData = new Array();
    
    this.finishBuilding = false;
    this.displayReportName = true;
    this.tableType = null;
    this.chartWidth = null;
    this.chartHeight = null;
    this.cube = null;

	/**
	 * Buffer for storing callback data/properties
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();
}

/**
* To be over-ridden by calling process to detect when definition is finished loading.
*
* @public
* @this {Report} 
* @param {Object} ret   Object that is a result from ajax
*/
Report.prototype.onloadGraphs = function(ret)
{    
}

/**
* To be over-ridden by calling process to detect when definition is finished loading.
*
* @public
* @this {Report} 
* @param {Object} ret   Object that is a result from ajax
*/
Report.prototype.onload = function(ret)
{
}

/**
* To be over-ridden by calling process to detect when definition is finished saving.
*
* @public
* @this {Report} 
* @param {Object} ret   Object that is a result from ajax
*/
Report.prototype.onsave = function(ret)
{
}

/**
* Prints the report
*
* @public
* @this {Report}
*/
Report.prototype.print = function(con)
{
    var graphData = new Object;
    graphData.type = this.reportData.chart_type;
    graphData.dimension = this.reportData.chart_dim1;
    graphData.dimensionFormat = this.reportData.chart_dim1_grp;
    graphData.grouping = this.reportData.chart_dim2;
    graphData.groupingFormat = this.reportData.chart_dim2_grp;
    graphData.measure = this.reportData.chart_measure;
    graphData.aggregate = this.reportData.chart_measure_agg;
    
    // Display Report Info
    if(this.displayReportName)
    {
        var reportName = alib.dom.setElementAttr(alib.dom.createElement("div", con), [["innerHTML", this.reportData.name]]);
        alib.dom.styleSet(reportName, "fontWeight", "bold");
        alib.dom.styleSet(reportName, "fontSize", "12px");
        alib.dom.styleSet(reportName, "margin", "5px");
    }
    
    // Display Graph Data
    this.graphCon = alib.dom.createElement("div", con);
    this.graphCon.id = "GraphCon";
    this.displayGraph(this.graphCon, graphData, this.filterData);
    
    // Display Table Data
    this.tableResultCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(this.tableResultCon, "display", "none");
    this.displayTableData()
}
     
/**
* Loads the report data using the Datacenter Controller
*
* @public
* @this {Report} 
*/
Report.prototype.loadReport = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.tableType = ret.reportData.table_type;
        this.cbData.cls.reportData = ret.reportData;
        this.cbData.cls.filterData = ret.filters;
        this.cbData.cls.reportDimensionData = ret.dimensions;
        this.cbData.cls.reportMeasureData = ret.measures;
        
        var objType = ret.reportData['obj_type'];
        var cubePath = ret.reportData['dataware_cube'];
        var customReport = ret.reportData['custom_report'];
            
        this.cbData.cls.cube = new OlapCube(objType, customReport, cubePath);
        var cube = this.cbData.cls.cube;
        
        cube.cls = this.cbData.cls;
        cube.reportData = ret;
        cube.onloadDefinition = function(dimensions, measures)
        {
            this.cls.dimensionData = dimensions;
            this.cls.measureData = measures;
            
            // Get the Dimension Types and put in an array
            for(dimension in this.cls.dimensionData)
            {
                var currentDimension = this.cls.dimensionData[dimension];        
                this.cls.dimTypeData[currentDimension.name] = currentDimension.type;
            }
            
            this.cls.finishBuilding = true;
            this.cls.onload(this.reportData);
        }
        cube.loadDefinition(this.cbData.cls);
        
    };

    var args = new Array();
    args[args.length] = ['id', this.reportId];
    ajax.exec("/controller/Datacenter/getReportData", args);
}

/**
* Loads the report data using the Datacenter Controller
*
* @public
* @this {Report} 
* @param {Integer} gType    Graph Type Id
*/
Report.prototype.getGraphTypes = function(gType)
{
    ajax = new CAjax('json');    
    ajax.cbData.cls = this;    
    ajax.onload = function(ret)
    {
        this.cbData.cls.onloadGraphs(ret);
    };
    
    var args = new Array();
    args[args.length] = ['gtype', gType];
    ajax.exec("/controller/Datacenter/reportGetGraphTypes", args);
}

/**
* Gets the ANT objects
*
* @public
* @this {Report} 
*/
Report.prototype.getObjects = function()
{
    ajax = new CAjax('json');    
    ajax.cbData.cls = this;    
    ajax.onload = function(ret)
    {
        this.cbData.cls.onload(ret);
    };
    ajax.exec("/controller/Object/getObjects");
}

/**
* Loads the report data using the Datacenter Controller
*
* @public
* @this {Report} 
* @param {DOMElement} con       The container where we can print the graph
* @param {Object} graphData     Object data which contains the graph details
* @param {Object} filterData    Object data which contains the filter conditions
*/
Report.prototype.displayGraph = function(con, graphData, filterData)
{
    // Set Olap Cube
    var olapCube = new OlapCube_Graph(this.cube);
    
    if(!graphData.type)
    {
        con.innerHTML = "Report Graph Type is not set.";
        alib.dom.styleSet(con, "height", "50px");
        return;
    }
    
    con.innerHTML = "<div class='loading'></div>";
    
    // Set X-Axis
    if(graphData.dimension.length)
        olapCube.addDimension(graphData.dimension, 'asc', graphData.dimensionFormat);
        
    // Set Grouping
    if(graphData.grouping.length)
        olapCube.addDimension(graphData.grouping, 'asc', graphData.groupingFormat);
    
    // Set Y-Axis
    var aggregate = '';
    if(graphData.measure !== MEASURE_COUNT)
        aggregate = graphData.aggregate;
    
    olapCube.addMeasure(graphData.measure, aggregate)
    
    // Set Filter Data
    for(filter in filterData)
    {
        var currentFilter = filterData[filter];
        olapCube.addFilter(currentFilter.blogic, currentFilter.fieldName, currentFilter.operator, currentFilter.condValue);
    }
    
    olapCube.chartType = graphData.type;
    olapCube.chartWidth = this.chartWidth;
	if (this.chartHeight)
    	olapCube.chartHeight = this.chartHeight;
    olapCube.print(con, false);
}

/**
* Loads the report data using the Datacenter Controller
*
* @public
* @this {Report} 
*/
Report.prototype.displayTableData = function()
{    
    if(!this.tableType)
        return;
    
    switch(this.tableType)
    {
        case TABLE_TABULAR:
            var olapTableCls = new AntObjectLoader_Report_Tabular(this);
            
            // Dimensions
            if(this.reportDimensionData.length)
            {                
                for(dimension in this.reportDimensionData)
                {
                    var currentDimension = this.reportDimensionData[dimension];
                    
                    olapTableCls.tabularDimensionData[dimension] = new Object();
                    var dimObject = olapTableCls.tabularDimensionData[dimension];
                    
                    dimObject.dimension = alib.dom.setElementAttr(alib.dom.createElement("div"), [["innerHTML", currentDimension.name]]);
                    dimObject.sort = alib.dom.setElementAttr(alib.dom.createElement("div"), [["innerHTML", currentDimension.sort]]);
                }
            }
            break;
        case TABLE_SUMMARY:
            var olapTableCls = new AntObjectLoader_Report_Summary(this);
            
            // Dimensions
            if(this.reportDimensionData.length)
            {        
                for(dimension in this.reportDimensionData)
                {
                    var currentDimension = this.reportDimensionData[dimension];
                        
                    olapTableCls.reportForm.summaryGroup[dimension] = new Object();
                    var dimObject = olapTableCls.reportForm.summaryGroup[dimension];
                    var dimType = this.dimTypeData[currentDimension.name];
                    
                    dimObject.dimension = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.name], ["dimType", dimType]]);
                    dimObject.sort = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.sort]]);
                    dimObject.format = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.format]]);
                }        
            }
            
            // Measures
            if(this.reportMeasureData.length)
            {        
                for(measure in this.reportMeasureData)
                {            
                    var currentMeasure = this.reportMeasureData[measure];
                    
                    olapTableCls.reportForm.summarySumm[measure] = new Object();
                    var measObject = olapTableCls.reportForm.summarySumm[measure];
                    
                    measObject.measure = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentMeasure.name]]);
                    measObject.aggregate = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentMeasure.aggregate]]);                    
                }        
            }
            break;
        case TABLE_PIVOT:
            var olapTableCls = new AntObjectLoader_Report_PivotMatrix(this);
            
            // Dimensions
            if(this.reportDimensionData.length)
            {        
                for(dimension in this.reportDimensionData)
                {
                    var currentDimension = this.reportDimensionData[dimension];
                    var dimType = this.dimTypeData[currentDimension.name];
                    
                    if(currentDimension.f_column=="t")
                    {
                        olapTableCls.reportForm.pivotColumn[dimension] = new Object();
                        var dimObject = olapTableCls.reportForm.pivotColumn[dimension];
                    }                        
                    else if(currentDimension.f_row=="t")
                    {
                        olapTableCls.reportForm.pivotRow[dimension] = new Object();
                        var dimObject = olapTableCls.reportForm.pivotRow[dimension];
                    }
                    
                    dimObject.dimension = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.name], ["dimType", dimType]]);
                    dimObject.sort = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.sort]]);
                    dimObject.format = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentDimension.format]]);
                }        
            }
            
            // Measures
            if(this.reportMeasureData.length)
            {        
                for(measure in this.reportMeasureData)
                {            
                    var currentMeasure = this.reportMeasureData[measure];
                    var measObject = olapTableCls.reportForm.pivotData;
                    
                    measObject.measure = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentMeasure.name]]);
                    measObject.aggregate = alib.dom.setElementAttr(alib.dom.createElement("hidden"), [["value", currentMeasure.aggregate]]);                    
                }        
            }
            
            // Total Checkboxes
            olapTableCls.reportForm.pivotTotals = new Object();
            var attrSub = [["type", "checkbox"], ["checked", (this.reportData.f_sub_totals=="f")?false:true]];
            olapTableCls.reportForm.pivotTotals.sub = alib.dom.setElementAttr(alib.dom.createElement("input"), attrSub);
            break;
        default:
            return;
    }
    
    olapTableCls.buildOlapData();
}

/**
* Saves the report data using the Datacenter Controller
*
* @public
* @this {Report} 
* @param {Array} args   Arguments used for controller
*/
Report.prototype.saveReport = function(args)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.onsave(ret);
    };
    ajax.exec("/controller/Datacenter/updateReportData", args);
}

/**
* Creates the report
*
* @public
* @this {Report} 
* @param {Array} args   Arguments used for controller
*/
Report.prototype.createReport = function(args)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.onsave(ret);
    };
    ajax.exec("/controller/Datacenter/saveReportData", args);
}

/**
 * Toggles the display from chart to table data and vice versa
 * This function is used in report widget
 *
 * @public
 * @this {Report} 
 * @param {Boolean} displayChart     Determines whether to display chart or table data
 */
Report.prototype.toggleDisplay = function(displayChart)
{
    alib.dom.styleSet(this.tableResultCon, "display", "none");     
    alib.dom.styleSet(this.graphCon, "display", "none");     
    
    if(displayChart)
        alib.dom.styleSet(this.graphCon, "display", "block");
    else
        alib.dom.styleSet(this.tableResultCon, "display", "block");
     
}
