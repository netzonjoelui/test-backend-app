/**
* @fileoverview This sub-loader will load reports
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

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
* @param {CAntObject} obj Handle to object that is being viewed or edited
* @param {AntObjectLoader} loader Handle to base loader class
*/
function AntObjectLoader_Report(obj, loader)
{
    this.mainObject = obj;
    this.reportId = this.mainObject.id;    
    this.loaderCls = loader;
    this.reportObject = null;
    
    this.outerCon = null; // Outer container
    this.mainConMin = null; // Minified div for collapsed view
    this.mainCon = null; // Inside outcon and holds the outer table
    this.formCon = null; // inner container where form will be printed
    this.bodyCon = null;
    this.bodyFormCon = null; // Displays the form
    this.bodyNoticeCon = null; // Right above the form and used for notices and inline duplicate detection
    this.tableResultCon = null;
    this.graphCon = null;
    
    this.ctbl = null; // Content table used for frame when printed inline
    this.toolbar = null;        
    this.plugins = new Array();
    this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
    this.fEnableClose = true; // Set to false to disable "close" and "save and close"
        
    this.forceEdit = false;
    this.hideToolbar = false;
    this.editMode = false;
    this.measureHasCount = false; // Set true if measure data has count key
    this.finishBuilding = false; 
    this.filterCount = 0; // Current count of filter    
    
    // Report Objects    
    this.reportForm = new Object();    
    this.reportData = new Object();    
    this.reportDimensionData = new Object();
    this.reportMeasureData = new Object();
    this.objectData = new Object();    
    this.measureData = new Object();
    this.dimensionData = new Object();    
    
    this.dimTypeData = new Object();
    this.aggregateData = new Array();
    this.sortData = new Array();
    this.formatData = new Array();
    this.reportFilterData = new Array();
    
    // Report variables    
    this.reportType = REPORT_TYPE_OBJECT; // Default Value: Dataware Cube Report Type
    this.objType = null; // Object used to generate report
    this.tableType = null; // Object used to generate report
    this.customReport = null;
    this.graphType = null;
    this.chartWidth = "800";
    
    this.cube = null;
    this.cubePath = null;
    
    this.deleteImage = null;
}

/**
 * Refresh the form
 */
AntObjectLoader_Report.prototype.refresh = function()
{    
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_Report.prototype.toggleEdit = function(setmode)
{
    if(!this.buttonEdit)
        return;
    
    this.editMode = (this.editMode) ? false : true;
    
    var displayEdit = "block";
    var displayInlineEdit = "inline-block";    
    var hideEdit = "none";
    
    if (this.editMode)
        this.buttonEdit.setText("Finished Editing");
    else
    {
        displayEdit = "none";
        displayInlineEdit = "none";
        hideEdit = "inline-block";
        this.buttonEdit.setText("Edit Values");
    }
    
    // Display Elements When Edit Mode
    alib.dom.styleSet(this.reportForm.reportInfo.reportName, "display", displayInlineEdit);
    alib.dom.styleSet(this.fsGraph, "display", displayEdit);
    alib.dom.styleSet(this.fsTableOptions, "display", displayEdit);
    alib.dom.styleSet(this.tableResultCon, "display", displayEdit);
                                                                  
    // Hide Elements When Edit Mode
    alib.dom.styleSet(this.reportForm.reportInfo.lblReportName, "display", hideEdit);    
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_Report.prototype.print = function(con, plugins)
{
    this.outerCon = con;
    this.mainCon = alib.dom.createElement("div", con);
    this.formCon = this.mainCon;

    var outer_dv = alib.dom.createElement("div", this.formCon);
    
    this.bodyCon = alib.dom.createElement("div", outer_dv);    
    alib.dom.styleSet(this.bodyCon, "margin-top", "5px");
    
    // Notice container
    this.bodyNoticeCon = alib.dom.createElement("div", this.bodyCon);

    // Body container
    this.bodyFormCon = alib.dom.createElement("div", this.bodyCon);
    this.bodyFormCon.innerHTML = "<div class='loading'></div>";
    
    this.buildInterface();
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_Report.prototype.onValueChange = function(name, value, valueName)
{    
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_Report.prototype.onNameChange = function(name)
{
}

/**
 * Builds the report interface
 *
 * @this {AntObjectLoader_Report}
 * @private
 */
AntObjectLoader_Report.prototype.buildInterface = function()
{
    if(this.reportId > 0) // display existing report [edit mode]
    {
        // Instantiate the report class
        this.reportObject = new Report(this.reportId);
        this.reportObject.cls = this;
        this.reportObject.chartWidth = this.chartWidth;
        
        // over-ride the onload function
        this.reportObject.onload = function(ret)
        {
            this.cls.cube = this.cube;
            
            this.cls.deleteImage = "/images/icons/deleteTask.gif"
            this.cls.tableType = this.tableType;

            this.cls.reportData = this.reportData;
            this.cls.reportFilterData = this.filterData;
            this.cls.reportDimensionData = this.reportDimensionData;
            this.cls.reportMeasureData = this.reportMeasureData;
            
            var objType = ret.reportData['obj_type'];
            var cubePath = ret.reportData['dataware_cube'];
            var customReport = ret.reportData['custom_report'];
            
            if(objType) // Object Report
            {
                this.cls.objType = objType;
                this.cls.reportType = REPORT_TYPE_OBJECT;
            }
            else if(cubePath)   // Dataware
            {
                this.cls.cubePath = cubePath;
                this.cls.reportType = REPORT_TYPE_DATAWARE;
            }
            else if(customReport)
            {
                this.cls.customReport = customReport;
                this.cls.reportType = REPORT_TYPE_CUSTOM;
            }
            
            // Get the data array from the report class that will be used in select dropdowns
            this.cls.aggregateData = this.aggregateData;
            this.cls.sortData = this.sortData;
            this.cls.formatData = this.formatData;
            this.cls.dimTypeData = this.dimTypeData;            
            this.cls.dimensionData = this.dimensionData;
            this.cls.measureData = this.measureData;
            
            // check measureData if it has count key
            for(measure in this.measureData)
            {
                var currentMeasure = this.measureData[measure];
                
                if(currentMeasure.name == MEASURE_COUNT)
                {
                    this.cls.measureHasCount = true;
                    break;
                }
            }
            
            this.cls.buildReport();
            
            if(this.cls.forceEdit)
                this.cls.toggleEdit(true);
        }
        this.reportObject.loadReport();
    }
    else // display new report type
    {
        var newReportType = new AntObjectLoader_Report_Dialog(this);
        newReportType.cls = this;
        newReportType.onsave = function(ret)
        {
            this.cls.forceEdit = true;
            this.cls.reportId = ret;            
            this.cls.buildInterface();            
        }
        newReportType.print();
    }
}

/**
 * Builds the report
 *
 * @public
 * @this {AntObjectLoader_Report}
 */
AntObjectLoader_Report.prototype.buildReport = function()
{
    this.bodyFormCon.innerHTML = "";
    this.reportToolbar();
    
    var tableForm = alib.dom.createElement("table", this.bodyFormCon);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    // Report Details
    this.reportForm.reportInfo = new Object();
    
    var attrData = [["id", "name"], ["type", "text"], ["width", "430px"], ["label", "Report Name: "], ["value", this.reportData.name]];
    this.reportForm.reportInfo.reportName = alib.dom.setElementAttr(alib.dom.createElement("input"), attrData);
    
    this.loaderCls.buildFormInput(this.reportForm.reportInfo, tBody);
    
    // Create Additional Report Details
    var reportNameTd = this.reportForm.reportInfo.reportName.parentNode;
    this.reportForm.reportInfo.lblReportName = alib.dom.setElementAttr(alib.dom.createElement("div", reportNameTd), [["innerHTML", this.reportForm.reportInfo.reportName.value]]);
    alib.dom.styleSet(this.reportForm.reportInfo.reportName, "display", "none");
    
    // Report Filter
    this.fsFilter = alib.dom.createElement("fieldset", this.bodyFormCon);
    var legendFilter = alib.dom.setElementAttr(alib.dom.createElement("legend", this.fsFilter), [["innerHTML", "Filter"]]);    
    
    var divFilterCon = alib.dom.createElement("div", this.fsFilter);
    
    if(this.objType) // Object Report
    {
        this.buildObjectFilter(divFilterCon);
    }
    else    // Dataware
    {
        this.reportForm.reportFilter = new Array();
    
        // Displays Saved Filters
        this.displaySavedFilter(divFilterCon);    
        
        // Filter links    
        this.reportFilterLinks(this.fsFilter, divFilterCon)
    }
    
    // Graph Options
    this.fsGraph = alib.dom.createElement("fieldset", this.bodyFormCon);
    alib.dom.styleSet(this.fsGraph, "display", "none");
    var legendGraph = alib.dom.setElementAttr(alib.dom.createElement("legend", this.fsGraph), [["innerHTML", "Graph Options"]]);    
    
    var divGraphCon = alib.dom.createElement("div", this.fsGraph);
    this.buildGraphOptions(divGraphCon);
    
    // Display Graph
    this.graphCon = alib.dom.setElementAttr(alib.dom.createElement("div", this.bodyFormCon), [["innerHTML", "<div class='loading'></div>"]]);
    alib.dom.styleSet(this.graphCon, "border", "1px solid");
    alib.dom.styleSet(this.graphCon, "margin", "10px 0px");
    alib.dom.styleSet(this.graphCon, "padding", "10px");
    alib.dom.styleSet(this.graphCon, "height", "50px");
    
    // Table Options
    this.fsTableOptions = alib.dom.createElement("fieldset", this.bodyFormCon);
    alib.dom.styleSet(this.fsTableOptions, "display", "none");
    var legendTable = alib.dom.setElementAttr(alib.dom.createElement("legend", this.fsTableOptions), [["innerHTML", "Table Options"]]);
    
    // Table Results
    this.tableResultCon = alib.dom.createElement("div", this.bodyFormCon);
    alib.dom.styleSet(this.tableResultCon, "display", "none");
    alib.dom.styleSet(this.tableResultCon, "minHeight", "200px");
    
    var divTableCon = alib.dom.createElement("div", this.fsTableOptions);
    this.buildTableOptions(divTableCon);
    
    this.finishBuilding = true;    
    this.loadOlapData();    
}

/**
 * Build the report toolbar
 *
 * @public
 * @this {AntObjectLoader_Report}
 */
AntObjectLoader_Report.prototype.reportToolbar = function()
{
    if(this.hideToolbar)
        return;
    
    var tb = new CToolbar();    
    
    // close button
    //save and close button
    var btn = new CButton("Close", 
    function(cls)
    {
        cls.close();
    },
    [this.loaderCls], "b1");
    if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
        tb.AddItem(btn.getButton(), "left");
    
    //save and close button
    var btn = new CButton("Save and Close", 
    function(cls)
    {
        cls.saveReport(true);
    },
    [this], "b1");
    if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
        tb.AddItem(btn.getButton(), "left");
    
    // save changes button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        cls.saveReport(false);
    },
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    // delete button
    var btn = new CButton("Delete",
    function(cls)
    {
        if(confirm("Are you sure to delete this user?"))
            cls.deleteUser();
    },
    [this], "b3");
    tb.AddItem(btn.getButton(), "left");
    
    // Finished Editing
    this.buttonEdit = new CButton("Edit Values",
    function(cls)
    {
        cls.toggleEdit(cls.editMode);
    },
    [this], "b2");
    tb.AddItem(this.buttonEdit.getButton(), "left");
    
    // Permissions
    var btn = new CButton("Permissions",
    function(cls)
    {        
    },
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    tb.print(this.loaderCls.toolbarCon);
} 

/**
 * Creates the filter using CAntObject::buildAdvancedQuery()
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {DOMElement} divFilterCon The container where we can the print filter options
 */
AntObjectLoader_Report.prototype.buildObjectFilter = function(divFilterCon)
{
    // Create New CAntObject for current report type    
    //var currentObject = new CAntObject(this.objType);
    var currentObject = new CAntObject(this.objType);
    currentObject.loadQuerySubObjects = false;
    this.reportFilterData = currentObject.buildAdvancedQuery(divFilterCon, this.reportFilterData);
    
    var applyDataCon = alib.dom.createElement("div", divFilterCon);
    var applyData = alib.dom.setElementAttr(alib.dom.createElement("a", applyDataCon), [["innerHTML", "Apply & Refresh Data"], ["href", "javascript: void(0);"]]);
    var addConditionCon = applyData.parentNode.previousSibling;
    
    alib.dom.divClear(divFilterCon);
    
    // Set Element Style
    alib.dom.styleSet(applyDataCon, "float", "left");
    alib.dom.styleSet(applyDataCon, "marginLeft", "15px");
    alib.dom.styleSet(addConditionCon, "float", "left");
    
    // Set Element Events
    applyData.cls = this;
    applyData.onclick = function()
    {        
        this.cls.loadOlapData();
        this.cls.processGraphDisplay();
    }
}

/**
 * Creates the fieldset for filter report
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {DOMElement} divFilterCon The container where we can the print filter options
 */
AntObjectLoader_Report.prototype.buildReportFilter = function(divFilterCon)
{    
    this.reportForm.reportFilter[this.filterCount] = new Object();
        
    var currentReportFilter = this.reportForm.reportFilter[this.filterCount];
    
    var divFilter = alib.dom.createElement("div", divFilterCon);
    alib.dom.styleSet(divFilter, "marginTop", "5px");    
    
    var queryDefault = "Query Value";    
    currentReportFilter.blogic = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "filter_blogic_" + this.filterCount]]);
    currentReportFilter.field = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "filter_field_" + this.filterCount]]);
    currentReportFilter.operator = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "filter_operator_" + this.filterCount]]);
    currentReportFilter.condValue = alib.dom.setElementAttr(alib.dom.createElement("input"), [["type", "text"], ["id", "filter_value_" + this.filterCount], ["value", queryDefault]]);
    currentReportFilter.removeFilter = alib.dom.createElement("img");
    
    this.loaderCls.buildFormInputDiv(currentReportFilter, divFilter, false, "10px");
    alib.dom.divClear(divFilterCon);

    // Set Current Filter Id to null value
    currentReportFilter.id = null;
    
    // Set Element Events
    alib.dom.setInputBlurText(currentReportFilter.condValue, queryDefault, "", "", "");
    currentReportFilter.removeFilter.divFilterCon = divFilterCon;
    currentReportFilter.removeFilter.divFilter = divFilter;
    currentReportFilter.removeFilter.filterCount = this.filterCount;
    currentReportFilter.removeFilter.cls = this;
    currentReportFilter.removeFilter.onclick = function()
    {
        this.divFilterCon.removeChild(this.divFilter);        
        delete this.cls.reportForm.reportFilter[this.filterCount];
        
        this.cls.loadOlapData();
    }

    currentReportFilter.field.cls = this;    
    currentReportFilter.field.operator = currentReportFilter.operator;
    currentReportFilter.field.onchange = function()
    {        
        var currentOperator = this.operator;
        this.dimType = this.cls.dimTypeData[this.value];
        var operatorData = this.cls.getFilterOperator(this.dimType);
        currentOperator.innerHTML = "";
        this.cls.loaderCls.buildDropdown(currentOperator, operatorData);
    }    
    
    // Set Element Data
    currentReportFilter.removeFilter.src = this.deleteImage;
    
    // set element style
    alib.dom.styleSet(currentReportFilter.removeFilter, "cursor", "pointer");
    alib.dom.styleSet(currentReportFilter.removeFilter, "marginTop", "3px");
    alib.dom.styleSet(currentReportFilter.condValue, "height", "20px");
    alib.dom.styleSet(currentReportFilter.condValue, "fontSize", "12px");
    
    // Add Select Options
    var blogicData = [["and", "And"], ["or", "Or"]];    
    this.loaderCls.buildDropdown(currentReportFilter.blogic, blogicData);
    
    var defaultType = null;
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = currentReportFilter.field.length
        
        currentReportFilter.field[dimLen] = new Option(currentDimension.name, currentDimension.name);        
        currentReportFilter.field[dimLen].dimType = currentDimension.type;
        
        // get the first dimension type
        if(defaultType==null)
            defaultType = currentDimension.type;
    }
    
    var operatorData = this.getFilterOperator(defaultType);
    this.loaderCls.buildDropdown(currentReportFilter.operator, operatorData);
    
    this.filterCount++; // increment the filter count to be used for the next index
} 

/**
 * Creates the links for filter (add and apply filter)
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {DOMElement} fsFilter The fieldset container where we can the print the filter links
 * @param {DOMElement} divFilterCon The container where we can the print the filter links
 */
AntObjectLoader_Report.prototype.reportFilterLinks = function(fsFilter, divFilterCon)
{    
    var divFilterLinks = alib.dom.createElement("div", fsFilter);
    alib.dom.styleSet(divFilterLinks, "marginTop", "10px");
    
    var addData = [["href", "javascript: void(0);"], ["innerHTML", "Add Filter"]];
    var addFilter = alib.dom.setElementAttr(alib.dom.createElement("a", divFilterLinks), addData);    
    alib.dom.styleSet(addFilter, "marginRight", "15px");    
    addFilter.cls = this;
    addFilter.divFilterCon = divFilterCon;
    addFilter.onclick = function()
    {
        this.cls.buildReportFilter(this.divFilterCon);        
    }
    
    var applyData = [["href", "javascript: void(0);"], ["innerHTML", "Apply & Refresh Data"]];
    var applyFilter = alib.dom.setElementAttr(alib.dom.createElement("a", divFilterLinks), applyData);    
    applyFilter.cls = this;    
    applyFilter.onclick = function()
    {
        this.cls.loadOlapData();
    }
}

/**
 * Displays the saved report filter
 *
 * @public
 * @this {AntObjectLoader_Report} 
 * @param {DOMElement} divFilterCon The container where we can print the filter options
 */
AntObjectLoader_Report.prototype.displaySavedFilter = function(divFilterCon)
{
    if(this.reportFilterData.length)
    {        
        for(filter in this.reportFilterData)
        {
            var currentFilter = this.reportFilterData[filter];
            this.buildReportFilter(divFilterCon);
            
            var currentReportFilter = this.reportForm.reportFilter[this.filterCount-1];
            
            currentReportFilter.id = currentFilter.id;
            currentReportFilter.blogic.value = currentFilter.blogic;
            currentReportFilter.field.value = currentFilter.fieldName;
            
            currentReportFilter.field.onchange();
            currentReportFilter.operator.value = currentFilter.operator;
            currentReportFilter.condValue.value = currentFilter.condValue;
        }
        
        this.processFilter();
    }
}

/**
 * Process and clean the filter data
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {Integer} tableType The container where we can the print the table options
 */
AntObjectLoader_Report.prototype.processFilter = function()
{
    this.filterData = new Array();
    
    if(this.reportForm.reportFilter)
    {
        for(filter in this.reportForm.reportFilter)
        {
            var currentFilter = this.reportForm.reportFilter[filter];
            
            this.filterData[filter] = new Object;
            this.filterData[filter].id = currentFilter.id;
            this.filterData[filter].blogic = currentFilter.blogic.value;
            this.filterData[filter].fieldName = currentFilter.field.value;
            this.filterData[filter].operator = currentFilter.operator.value;
            this.filterData[filter].condValue = currentFilter.condValue.value;
        }
    }
    else
    {
        for (var i = 0; i < this.reportFilterData.getNumConditions(); i++)
        {
            var currentFilter = this.reportFilterData.getCondition(i);            
            this.filterData[i] = new Object;
            this.filterData[i].id = currentFilter.condId;
            this.filterData[i].blogic = currentFilter.blogic;
            this.filterData[i].fieldName = currentFilter.fieldName;
            this.filterData[i].operator = currentFilter.operator;
            this.filterData[i].condValue = currentFilter.condValue;
        }
    }
}

/**
 * Build the graph options fieldset
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {DOMElement} divGraphCon The container where we can the print the graph options
 */
AntObjectLoader_Report.prototype.buildGraphOptions = function(divGraphCon)
{
    this.reportForm.reportGraph = new Object();
    
    this.reportForm.reportGraph.type = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "chart_type"], ["label", "Type: "]]);
    this.reportForm.reportGraph.measure = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "chart_measure"], ["label", "X-Axis: "]]);
    this.reportForm.reportGraph.dimension = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "chart_dim1"], ["label", "Y-Axis: "]]);
    this.reportForm.reportGraph.grouping = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "chart_dim2"], ["label", "Grouping: "]]);
    
    this.loaderCls.buildFormInput(this.reportForm.reportGraph, divGraphCon);
    
    // create additional elements
    var measureTd = this.reportForm.reportGraph.measure.parentNode;
    this.reportForm.reportGraph.aggregate = alib.dom.setElementAttr(alib.dom.createElement("select", measureTd), [["id", "chart_measure_agg"]]);
    
    var dimensionTd = this.reportForm.reportGraph.dimension.parentNode;    
    var lblDimension = alib.dom.setElementAttr(alib.dom.createElement("label", dimensionTd), [["innerHTML", "By"]]);
    
    this.reportForm.reportGraph.dimensionFormat = alib.dom.setElementAttr(alib.dom.createElement("select", dimensionTd), [["id", "chart_dim1_grp"]]);
    
    var groupingTd = this.reportForm.reportGraph.grouping.parentNode;
    var lblGrouping = alib.dom.setElementAttr(alib.dom.createElement("label", groupingTd), [["innerHTML", "By"]]);
    
    this.reportForm.reportGraph.groupingFormat = alib.dom.setElementAttr(alib.dom.createElement("select", groupingTd), [["id", "chart_dim2_grp"]]);
    
    // set element styles
    alib.dom.styleSet(this.reportForm.reportGraph.aggregate, "marginLeft", "10px");
    alib.dom.styleSet(this.reportForm.reportGraph.aggregate, "display", "none");
    alib.dom.styleSet(lblDimension, "margin", "0px 10px");
    alib.dom.styleSet(lblGrouping, "margin", "0px 10px");
    
    // Set element events
    this.reportForm.reportGraph.type.cls = this;
    this.reportForm.reportGraph.type.onchange = function()
    {
        this.cls.processGraphDisplay();
    }
    
    this.reportForm.reportGraph.measure.cls = this;
    this.reportForm.reportGraph.measure.onchange = function()
    {
        if(this.value=='count')
            alib.dom.styleSet(this.cls.reportForm.reportGraph.aggregate, "display", "none");
        else
            alib.dom.styleSet(this.cls.reportForm.reportGraph.aggregate, "display", "inline-block");
            
        this.cls.processGraphDisplay();
    }

    this.reportForm.reportGraph.dimension.cls = this;
    this.reportForm.reportGraph.dimension.lblDimension = lblDimension;
    this.reportForm.reportGraph.dimension.onchange = function()
    {        
        this.dimType = this.cls.dimTypeData[this.value];
        this.cls.displayFormat(this.dimType, this.lblDimension, this.cls.reportForm.reportGraph.dimensionFormat);
        
        this.cls.getGraphTypes();        
    }
    
    this.reportForm.reportGraph.dimensionFormat.cls = this;
    this.reportForm.reportGraph.dimensionFormat.onchange = function()
    {
        this.cls.processGraphDisplay();
    }
    
    this.reportForm.reportGraph.grouping.cls = this;
    this.reportForm.reportGraph.grouping.lblGrouping = lblGrouping;
    this.reportForm.reportGraph.grouping.onchange = function()
    {        
        this.dimType = this.cls.dimTypeData[this.value];
        this.cls.displayFormat(this.dimType, this.lblGrouping, this.cls.reportForm.reportGraph.groupingFormat);
        
        this.cls.getGraphTypes();        
    }
    
    this.reportForm.reportGraph.groupingFormat.cls = this;
    this.reportForm.reportGraph.groupingFormat.onchange = function()
    {
        this.cls.processGraphDisplay();
    }
    
    this.reportForm.reportGraph.aggregate.cls = this;
    this.reportForm.reportGraph.aggregate.onchange = function()
    {
        this.cls.processGraphDisplay();
    }
    
    // Add select options    
    this.loaderCls.buildDropdown(this.reportForm.reportGraph.aggregate, this.aggregateData, this.reportData.chart_measure_agg);
    
    this.loaderCls.buildDropdown(this.reportForm.reportGraph.dimensionFormat, this.formatData, this.reportData.chart_dim1_grp);
    this.loaderCls.buildDropdown(this.reportForm.reportGraph.groupingFormat, this.formatData, this.reportData.chart_dim2_grp);
    
    this.loaderCls.buildDropdown(this.reportForm.reportGraph.grouping, [["", "none"]]);
    this.loaderCls.buildDropdown(this.reportForm.reportGraph.dimension, [["", "none"]]);
    
    if(!this.measureHasCount)
        this.loaderCls.buildDropdown(this.reportForm.reportGraph.measure, [MEASURE_COUNT]);
    
    for(measure in this.measureData)
    {
        var currentMeasure = this.measureData[measure];
        var dimLen = this.reportForm.reportGraph.measure.length;
        var selected = false;
        
        if(currentMeasure.name == this.reportData.chart_measure)
        {
            alib.dom.styleSet(this.reportForm.reportGraph.aggregate, "display", "inline-block");
            selected = true;
        }
            
        
        this.reportForm.reportGraph.measure[dimLen] = new Option(currentMeasure.name, currentMeasure.name, false, selected);
    }
    
    var dimDefaultType = null;
    var groupDefaultType = null;
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = this.reportForm.reportGraph.dimension.length
        var dimSelected = false;
        
        if(currentDimension.name == this.reportData.chart_dim1)
        {
            dimDefaultType = currentDimension.type;
            dimSelected = true;
        }
        
        this.reportForm.reportGraph.dimension[dimLen] = new Option(currentDimension.name, currentDimension.name, false, dimSelected);
        this.reportForm.reportGraph.dimension[dimLen].dimType = currentDimension.type;
        
        if(dimDefaultType == null)
            dimDefaultType = currentDimension.type;
        
        // Grouping
        var groupLen = this.reportForm.reportGraph.grouping.length
        var groupSelected = false;
        
        if(currentDimension.name == this.reportData.chart_dim2)
        {
            groupDefaultType = currentDimension.type;
            groupSelected = true;
        }
            
            
        this.reportForm.reportGraph.grouping[groupLen] = new Option(currentDimension.name, currentDimension.name, false, groupSelected);
        this.reportForm.reportGraph.grouping[groupLen].dimType = currentDimension.type;        
        
        if(groupDefaultType == null)
            groupDefaultType = currentDimension.type;
    }
    
    this.displayFormat(dimDefaultType, lblDimension, this.reportForm.reportGraph.dimensionFormat);
    this.displayFormat(groupDefaultType, lblGrouping, this.reportForm.reportGraph.groupingFormat);
    
    this.getGraphTypes();
}

/**
 * Displays the Graph
 *
 * @public
 * @this {AntObjectLoader_Report} 
 */
AntObjectLoader_Report.prototype.processGraphDisplay = function()
{    
    if(!this.finishBuilding)
        return; 
        
    this.processFilter();        
    var graphObject = this.reportForm.reportGraph;
    var graphData = new Object;
    graphData.type = graphObject.type.value;
    graphData.dimension = graphObject.dimension.value;
    graphData.dimensionFormat = graphObject.dimensionFormat.value;
    graphData.grouping = graphObject.grouping.value;
    graphData.groupingFormat = graphObject.groupingFormat.value;
    graphData.measure = graphObject.measure.value;
    graphData.aggregate = graphObject.aggregate.value;
    
    this.reportObject.displayGraph(this.graphCon, graphData, this.filterData);
}

/**
 * Get the graph types in Datacenter Controller
 *
 * @public
 * @this {AntObjectLoader_Report} 
 */
AntObjectLoader_Report.prototype.getGraphTypes = function()
{
    var gType = GRAPH_TYPE_SINGLE;
    
    /*if(this.reportForm.reportGraph.dimension.value.length) // X-Axis is set
        gType = GRAPH_TYPE_MULTI;*/
    
    if(this.reportForm.reportGraph.grouping.value.length) // Grouping is set
        gType = GRAPH_TYPE_MULTI;
    
    if(this.graphType == gType)
    {
        this.processGraphDisplay();
        return;
    }
    
    var chartType = this.reportData.chart_type;
    var selectGraphType = this.reportForm.reportGraph.type;
    
    selectGraphType.innerHTML = "";
    this.loaderCls.buildDropdown(selectGraphType, [["", "none"]]);
    
    this.reportObject.onloadGraphs = function(ret)
    {        
        if(ret)
        {
            for(graphType in ret)
            {
                var currentType = ret[graphType];
                var selected = false;
                
                if(currentType.name == chartType)
                    selected = true;
                
                selectGraphType[selectGraphType.length] = new Option(currentType.title, currentType.name, false, selected);
            }
            
            this.cls.processGraphDisplay();
        }
    }
    this.reportObject.getGraphTypes(gType);
    
    this.graphType = gType;
}

/**
 * Displays the table options fieldset
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {DOMElement} divTableCon The container where we can the print the table options
 */
AntObjectLoader_Report.prototype.buildTableOptions = function(divTableCon)
{    
    // Instantiate Table Options Classes
    this.tabularReport = new AntObjectLoader_Report_Tabular(this);
    this.summaryReport = new AntObjectLoader_Report_Summary(this);
    this.pivotReport = new AntObjectLoader_Report_PivotMatrix(this);
    
    this.reportForm.reportTable = new Object();
    
    this.reportForm.reportTable.type = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "table_type"], ["label", "Type: "]]);
    this.loaderCls.buildFormInput(this.reportForm.reportTable, divTableCon);
    
    this.tableDataCon = alib.dom.createElement("div", divTableCon);    
    // Set Element Events    
    this.reportForm.reportTable.type.cls = this;    
    this.reportForm.reportTable.type.onchange = function()
    {        
        this.cls.tableDataCon.innerHTML = "";
        this.cls.tableResultCon.innerHTML = "";
        this.cls.tableType = this.value;
        
        switch(this.cls.tableType)
        {            
            case TABLE_TABULAR:
                this.cls.tabularReport.buildTableTabular(this.cls.tableDataCon);                
                break;
            case TABLE_SUMMARY:
                this.cls.summaryReport.buildTableSummary(this.cls.tableDataCon);                
                break;
            case TABLE_PIVOT:
                this.cls.pivotReport.buildTablePivot(this.cls.tableDataCon);                
                break;
        }        
        this.cls.loadOlapData();
    }
    
    // Add select options
    var typeData = [["", "none"], [TABLE_TABULAR, "Tabular"], [TABLE_SUMMARY, "Summary"], [TABLE_PIVOT, "Pivot / Matrix"]];
    this.loaderCls.buildDropdown(this.reportForm.reportTable.type, typeData, this.tableType);    
    this.reportForm.reportTable.type.onchange();
}
   
/**
 * Loads the olap data
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {Integer} tableType The container where we can the print the table options
 */
AntObjectLoader_Report.prototype.loadOlapData = function()
{
    // Apply the filter first before loading the olap data
    this.processFilter();

    if(!this.finishBuilding)
        return;
        
    switch(this.tableType)
    {
        case TABLE_TABULAR:
            this.tabularReport.buildOlapData();
            break;
        case TABLE_SUMMARY:
            this.summaryReport.buildOlapData();
            break;
        case TABLE_PIVOT:
            this.pivotReport.buildOlapData();
            break;
    }
}

/**
 * Checks if when to display the format dimension dropdown
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {String} dimType Determines the type of the dimension
 * @param {lblFormat} dimType Determines the type of the dimension
 * @param {DOMElement} lblFormat The label element with innerHTML "By"
 * @param {DOMElement} objElement The select dropdown element where we populate the display format
 */
AntObjectLoader_Report.prototype.displayFormat = function(dimType, lblFormat, objElement)
{
    switch(dimType)
    {
        case "timestamp":
        case "date":
        case "time":
            alib.dom.styleSet(lblFormat, "display", "inline-block");
            alib.dom.styleSet(objElement, "display", "inline-block");
            break;
        default:
            alib.dom.styleSet(lblFormat, "display", "none");
            alib.dom.styleSet(objElement, "display", "none");
            break;
    }
}

/**
 * Gets the filter operator array
 *
 * @public
 * @this {AntObjectLoader_Report}
 * @param {String} type Determines the type of the filter
 * 
 * @return {Array} operator Returns the array of operators
 */
AntObjectLoader_Report.prototype.getFilterOperator = function(type)
{
    // TO DO: Add fkey and multi_fkey
    
    var operator = new Array();    
    switch(type)
    {
        case 'timestamp':
        case 'time':
        case 'date':
            operator = ["is_equal", "is_not_equal", "is_greater", "is_less", "is_greater_or_equal", "is_less_or_equal",
                        "day_is_equal", "year_is_equal", "last_x_days", "last_x_weeks", "last_x_months", "last_x_years",
                        "next_x_days", "next_x_weeks", "next_x_months", "next_x_years"];            
            break;
        case 'numeric':
        case 'number':
        case 'real':
        case 'integer':
            operator = ["is_equal", "is_not_equal", "is_greater", "is_less", "is_greater_or_equal", "is_less_or_equal"];
            break;
        case 'string':
        case 'text':
        case 'object':
        case 'multi_fkey':
            operator = ["is_equal", "is_not_equal", "begins_with", "contains"];
        default:            
            break;
    }
    
    return operator;
}

/**
 * Saves the report details
 *
 * @public
 * @this {AntObjectLoader_Report} 
 * @param {Boolean} isClose     Determines if the loader will be closed after saving
 */
AntObjectLoader_Report.prototype.saveReport = function(isClose)
{
    var args = new Array();
    
    // Report Details
    args[args.length] = ['id', this.reportId];
    args[args.length] = ['name', this.reportForm.reportInfo.reportName.value];
    args[args.length] = ['table_type', this.tableType];
    
    if(this.reportForm.reportTable.type.value == TABLE_PIVOT)
    {
        // Get the this.reportForm of PivotMatrix.js
        var pivotReportForm = this.pivotReport.getReportForm();
        for(pivot in pivotReportForm.pivotTotals)
        {
            var currentPivot = pivotReportForm.pivotTotals[pivot];
            
            if(currentPivot.type == "checkbox")
                args[args.length] = [currentPivot.id, currentPivot.checked];
        }
    }
    else
    {
        args[args.length] = ['f_row_totals', false];
        args[args.length] = ['f_column_totals', false];
        args[args.length] = ['f_sub_totals', false];
    }
    
    // Graph Data
    for(graph in this.reportForm.reportGraph)
    {
        var currentGraph = this.reportForm.reportGraph[graph];
        
        if(currentGraph.id && currentGraph.style.display !== "none")
            args[args.length] = [currentGraph.id, currentGraph.value];
    }
    
    this.processFilter()
    // Filter Data
    for(filter in this.filterData)
    {
        var currentFilter = this.filterData[filter];
    
        args[args.length] = ["filters[]", filter];
        args[args.length] = ["filter_id_" + filter, currentFilter.id];
        args[args.length] = ["filter_blogic_" + filter, currentFilter.blogic];
        args[args.length] = ["filter_field_" + filter, currentFilter.fieldName];
        args[args.length] = ["filter_operator_" + filter, currentFilter.operator];
        args[args.length] = ["filter_value_" + filter, currentFilter.condValue];
    }
    
    // Table Options Data
    var tableOptionsData = new Array();
    switch(this.tableType)
    {
        case TABLE_TABULAR:
            args = args.concat(this.tabularReport.getTableOptions());
            break;
        case TABLE_SUMMARY:
            args = args.concat(this.summaryReport.getTableOptions());
            break;
        case TABLE_PIVOT:            
            args = args.concat(this.pivotReport.getTableOptions());
            break;
    }
        
    var dlg = showDialog("Saving report...");
    this.reportObject.onsave = function(ret)
    {
        dlg.hide();
        if(isClose)
            this.cls.loaderCls.close();
        
        if(!ret)
        {
            ALib.statusShowAlert("Error occured when saving the report data.", 3000, "bottom", "right");
            return;
        }
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        else
            ALib.statusShowAlert("Report was successfully saved!", 3000, "bottom", "right");
    }
    this.reportObject.saveReport(args);
}
