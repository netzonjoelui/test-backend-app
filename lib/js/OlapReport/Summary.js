/**
* @fileOverview The Summary report will display the table options used in Olap Report Object
*
* @author:    Marl Tumulak, marl.tumulak@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Summary Table Options
 *
 * @constructor  
 * @param {Object} obj The parent Object
 */
function AntObjectLoader_Report_Summary(obj)
{    
    this.parentObject = obj;
    
    this.reportDimensionData = this.parentObject.reportDimensionData;
    this.reportMeasureData = this.parentObject.reportMeasureData;
    
    this.dimensionData = this.parentObject.dimensionData;
    this.measureData = this.parentObject.measureData;    
    
    this.aggregateData = this.parentObject.aggregateData;
    this.sortData = this.parentObject.sortData;
    this.formatData = this.parentObject.formatData;
    this.dimTypeData = this.parentObject.dimTypeData;
    
    this.loaderCls = this.parentObject.loaderCls;
    this.deleteImage = this.parentObject.deleteImage;
    this.tableType = this.parentObject.tableType;
    
    this.reportForm = new Object();
    this.reportForm.summaryGroup = new Array();
    this.reportForm.summarySumm = new Array();
    
    this.summaryGroupCount = 0; // Current count of tabular dimension
    this.summarySummCount = 0; // Current count of tabular dimension
    
    this.savedDataLoaded = false; // Determines if the saved data already loaded
}

/**
 * Displays the table summary options
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @param {DOMElement} divSummaryCon The container where we can print the summary options 
 */
AntObjectLoader_Report_Summary.prototype.buildTableSummary = function(divSummaryCon)
{
    var reportSummaryObj = new Object();
    reportSummaryObj.groupBy = alib.dom.setElementAttr(alib.dom.createElement("div"), [["label", "Group By:"]]);
    reportSummaryObj.summarizeBy = alib.dom.setElementAttr(alib.dom.createElement("div"), [["label", "Summarize By:"]]);
    
    this.loaderCls.buildFormInput(reportSummaryObj, divSummaryCon);
    
    var groupParentCon = reportSummaryObj.groupBy.parentNode;
    var summarizeParentCon = reportSummaryObj.summarizeBy.parentNode;
    
    // Create Additional Elements
    var addDimensionLink = alib.dom.setElementAttr(alib.dom.createElement("a", groupParentCon), [["href", "javascript: void(0);"], ["innerHTML", "Add Dimension"]]);
    addDimensionLink.cls = this;
    addDimensionLink.groupBy = reportSummaryObj.groupBy;
    addDimensionLink.onclick = function()
    {
        this.cls.buildSummaryGroup(this.groupBy, this.cls.summaryGroupCount++);
        this.cls.buildOlapData();
    }
    
    var addMeasureLink = alib.dom.setElementAttr(alib.dom.createElement("a", summarizeParentCon), [["href", "javascript: void(0);"], ["innerHTML", "Add Measure"]]);
    addMeasureLink.cls = this;
    addMeasureLink.summarizeBy = reportSummaryObj.summarizeBy;
    addMeasureLink.onclick = function()
    {
        this.cls.buildSummarySumm(this.summarizeBy, this.cls.summarySummCount++);
        this.cls.buildOlapData();
    }
    
    // Set Element Styles
    alib.dom.styleSet(addDimensionLink, "fontSize", "11px");
    alib.dom.styleSet(groupParentCon.previousSibling, "verticalAlign", "top");
    alib.dom.styleSet(groupParentCon.previousSibling, "paddingTop", "5px");
    alib.dom.styleSet(addMeasureLink, "fontSize", "11px");
    alib.dom.styleSet(summarizeParentCon.previousSibling, "verticalAlign", "top");
    alib.dom.styleSet(summarizeParentCon.previousSibling, "paddingTop", "5px");
    
    // Load Summary Options
    for(var x=0; x<this.summaryGroupCount; x++)
        this.buildSummaryGroup(reportSummaryObj.groupBy, x);
    
    for(var x=0; x<this.summarySummCount; x++)
        this.buildSummarySumm(reportSummaryObj.summarizeBy, x);
    
    if(!this.savedDataLoaded)
    {
        this.displaySavedGroup(reportSummaryObj.groupBy);
        this.displaySavedSummarize(reportSummaryObj.summarizeBy);
        this.savedDataLoaded = true;
    }
    if(this.tableType == TABLE_SUMMARY)
        this.buildOlapData();
}

/**
 * Displays the saved group table 
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @param {DOMElement} divSummaryGroup The container where we can print the table options group
 */
AntObjectLoader_Report_Summary.prototype.displaySavedGroup = function(divSummaryGroup)
{    
    if(this.reportDimensionData.length)
    {        
        for(dimension in this.reportDimensionData)
        {
            var currentDimension = this.reportDimensionData[dimension];
            
            if(currentDimension.table_type == TABLE_SUMMARY)
            {                
                this.buildSummaryGroup(divSummaryGroup, this.summaryGroupCount);            
                var currentSummaryGroup = this.reportForm.summaryGroup[this.summaryGroupCount];
                
                var dimType = this.dimTypeData[currentDimension.name];
                this.parentObject.displayFormat(dimType, currentSummaryGroup.lblRow, currentSummaryGroup.format);
                
                currentSummaryGroup.id = currentDimension.id;
                currentSummaryGroup.dimension.value = currentDimension.name;
                currentSummaryGroup.dimension.dimType = dimType;
                currentSummaryGroup.sort.value = currentDimension.sort;
                currentSummaryGroup.format.value = currentDimension.format;
                
                this.summaryGroupCount++;
            }
        }        
    }    
}

/**
 * Builds the summary group elements
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @param {DOMElement} divSummaryGroup  The container where we can print the summary group options 
 * @param {Integer} currentCount        Holds the current count of summary group
 */
AntObjectLoader_Report_Summary.prototype.buildSummaryGroup = function(divSummaryGroup, currentCount)
{
    var newGroup = true;
    var divSummary = alib.dom.createElement("div", divSummaryGroup);
    alib.dom.styleSet(divSummary, "marginTop", "5px");    
    
    if(this.reportForm.summaryGroup[currentCount])
        newGroup = false;
    else
        this.reportForm.summaryGroup[currentCount] = new Object();        
        
    var currentSummaryGroup = this.reportForm.summaryGroup[currentCount];
    
    if(newGroup)
    {
        // Set Current Summary Group Id to null value
        currentSummaryGroup.id = null;
        currentSummaryGroup.dimension = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_name_" + currentCount]]);
        currentSummaryGroup.sort = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_sort_" + currentCount]]);
        currentSummaryGroup.lblGroup = alib.dom.setElementAttr(alib.dom.createElement("label"), [["innerHTML", "By"]]);
        currentSummaryGroup.format = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_format_" + currentCount]]);    
        currentSummaryGroup.removeDimension = alib.dom.createElement("img");
    }
    
    this.loaderCls.buildFormInputDiv(currentSummaryGroup, divSummary, false, "10px");
    alib.dom.divClear(divSummary);
    
    // Set Element Events    
    currentSummaryGroup.removeDimension.divSummaryGroup = divSummaryGroup;
    currentSummaryGroup.removeDimension.divSummary = divSummary;
    currentSummaryGroup.removeDimension.cls = this;
    currentSummaryGroup.removeDimension.currentCount = currentCount;
    currentSummaryGroup.removeDimension.onclick = function()
    {
        this.divSummaryGroup.removeChild(this.divSummary);
        delete this.cls.reportForm.summaryGroup[this.currentCount];
        this.cls.buildOlapData();
        
        // TO DO: create a list of deleted objects, so the loop wont recreate it 
        // TO DO: remove the filter in the database if its already a saved filter
    }
    
    currentSummaryGroup.dimension.cls = this;
    currentSummaryGroup.dimension.lblGroup = currentSummaryGroup.lblGroup;
    currentSummaryGroup.dimension.currentSummaryGroup = currentSummaryGroup;
    currentSummaryGroup.dimension.onchange = function()
    {           
        this.dimType = this.cls.dimTypeData[this.value];
         
        this.cls.parentObject.displayFormat(this.dimType, this.lblGroup, this.currentSummaryGroup.format);        
        this.cls.buildOlapData();
    }
    
    currentSummaryGroup.sort.cls = this;
    currentSummaryGroup.sort.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    currentSummaryGroup.format.cls = this;
    currentSummaryGroup.format.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    // Set Element Data
    currentSummaryGroup.removeDimension.src = this.deleteImage;
    
    // Set Element Style
    alib.dom.styleSet(currentSummaryGroup.removeDimension, "cursor", "pointer");
    alib.dom.styleSet(currentSummaryGroup.removeDimension, "marginTop", "3px");    
    
    if(!newGroup) // Do not re-populate the dropdown list
        return;
    
    // Add select options    
    this.loaderCls.buildDropdown(currentSummaryGroup.sort, this.sortData);
    this.loaderCls.buildDropdown(currentSummaryGroup.format, this.formatData);
    
    var defaultType = null;
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = currentSummaryGroup.dimension.length
        
        currentSummaryGroup.dimension[dimLen] = new Option(currentDimension.name, currentDimension.name);        
        currentSummaryGroup.dimension[dimLen].dimType = currentDimension.type;
        
        // get the first dimension type
        if(defaultType==null)
            defaultType = currentDimension.type;
    }
    
    this.parentObject.displayFormat(defaultType, currentSummaryGroup.lblGroup, currentSummaryGroup.format);        
}

/**
 * Displays the saved summarize 
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @param {DOMElement} divSummaryGroup The container where we can print the table options group
 */
AntObjectLoader_Report_Summary.prototype.displaySavedSummarize = function(divSummarySumm)
{
    if(this.reportMeasureData.length)
    {        
        for(measure in this.reportMeasureData)
        {            
            var currentMeasure = this.reportMeasureData[measure];
            
            if(currentMeasure.table_type == TABLE_SUMMARY)
            {
                this.buildSummarySumm(divSummarySumm, this.summarySummCount);            
                var currentSummarySumm = this.reportForm.summarySumm[this.summarySummCount];
                
                currentSummarySumm.id = currentMeasure.id;
                currentSummarySumm.measure.value = currentMeasure.name;
                
                if(currentMeasure.aggregate.length > 0 && currentSummarySumm.aggregate)
                    currentSummarySumm.aggregate.value = currentMeasure.aggregate;
                    
                this.summarySummCount++;
            }
        }
    }
    else // display count dropdown as default
    {
        this.buildSummarySumm(divSummarySumm, this.summarySummCount);
        this.summarySummCount++;
    }
}

/**
 * Builds the summarize elements
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @param {DOMElement} divSummarySumm   The container where we can print the summarize options 
 * @param {Integer} currentCount        Holds the current count of summarize
 */
AntObjectLoader_Report_Summary.prototype.buildSummarySumm = function(divSummarySumm, currentCount)
{
    var newSumm = true;
    var divSummary = alib.dom.createElement("div", divSummarySumm);
    alib.dom.styleSet(divSummary, "marginTop", "5px");    
    
    if(this.reportForm.summarySumm[currentCount])
        newSumm = false;
    else
        this.reportForm.summarySumm[currentCount] = new Object();
        
    var currentSummarySumm = this.reportForm.summarySumm[currentCount];
    
    if(newSumm)
    {
        // Set Current Summarize Id to null value
        currentSummarySumm.id = null;
        currentSummarySumm.measure = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "measure_name_" + currentCount]]);
    
        if(currentCount>0)
        {
            currentSummarySumm.aggregate = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "measure_aggregate_" + currentCount]]);
            currentSummarySumm.removeMeasure = alib.dom.createElement("img");
        }
    }
    
    this.loaderCls.buildFormInputDiv(currentSummarySumm, divSummary, false, "10px");
    alib.dom.divClear(divSummary);
    
    // Set Element Events
    currentSummarySumm.measure.cls = this;
    currentSummarySumm.measure.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    if(currentCount > 0) // display other measures
    {
        // Set Element Events
        currentSummarySumm.aggregate.cls = this;
        currentSummarySumm.aggregate.onchange = function()
        {
            this.cls.buildOlapData();
        }
        
        currentSummarySumm.removeMeasure.divSummarySumm = divSummarySumm;
        currentSummarySumm.removeMeasure.divSummary = divSummary;
        currentSummarySumm.removeMeasure.cls = this;
        currentSummarySumm.removeMeasure.currentCount = currentCount;
        currentSummarySumm.removeMeasure.onclick = function()
        {
            this.divSummarySumm.removeChild(this.divSummary);
            delete this.cls.reportForm.summarySumm[this.currentCount];
            this.cls.buildOlapData();
        }
        
        // Set Element Data
        currentSummarySumm.removeMeasure.src = this.deleteImage;
        
        // Set Element Style
        alib.dom.styleSet(currentSummarySumm.removeMeasure, "cursor", "pointer");
        alib.dom.styleSet(currentSummarySumm.removeMeasure, "marginTop", "3px");

        if(!newSumm) // Do not re-populate the dropdown list
            return;
        
        // Set Element Events        
        this.loaderCls.buildDropdown(currentSummarySumm.aggregate, this.aggregateData);
    
        for(measure in this.measureData)
        {
            var currentMeasure = this.measureData[measure];
            var dimLen = currentSummarySumm.measure.length;
            
            currentSummarySumm.measure[dimLen] = new Option(currentMeasure.name, currentMeasure.name);        
        }
    }
    else // Display Count Only
    {
        if(!newSumm) // Do not re-populate the dropdown list
            return;
            
        this.loaderCls.buildDropdown(currentSummarySumm.measure, [MEASURE_COUNT]);
    }
}

/**
 * Displays the Summary Olap Cube Data in table
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 */
AntObjectLoader_Report_Summary.prototype.buildOlapData = function()
{
    var tableResultCon = this.parentObject.tableResultCon;    
    var filterData = this.parentObject.filterData;
    var finishBuilding = this.parentObject.finishBuilding;
    
    if(!finishBuilding)
        return; 
    
    // Set Olap Cube
    var cube = this.parentObject.cube;
    var olapCube = new OlapCube_Table_Summary(cube);
    
    this.args = new Array();
    
    // Set Dimension Data
    for(group in this.reportForm.summaryGroup)
    {
        if(group)
        {            
            var currentGroup = this.reportForm.summaryGroup[group];
            
            this.args[this.args.length] = ["dimensions[]", group];
            this.args[this.args.length] = ["dimension_id_" + group, currentGroup.id];
            this.args[this.args.length] = ["f_column_" + group, false];
            this.args[this.args.length] = ["f_row_" + group, false];
            this.args[this.args.length] = [currentGroup.dimension.id, currentGroup.dimension.value];
            this.args[this.args.length] = [currentGroup.sort.id, currentGroup.sort.value];
            
            switch(currentGroup.dimension.dimType)
            {
                case "timestamp":
                case "date":
                case "time":                    
                    olapCube.addRow(currentGroup.dimension.value, currentGroup.sort.value, currentGroup.format.value);
                    this.args[this.args.length] = [currentGroup.format.id, currentGroup.format.value];
                    break;
                default:                    
                    olapCube.addRow(currentGroup.dimension.value, currentGroup.sort.value);                    
                    break;
            }
        }        
    }
    
    // Set Measure Data
    for(summarize in this.reportForm.summarySumm)
    {        
        if(summarize)
        {
            var currentSummarize = this.reportForm.summarySumm[summarize];
            
            this.args[this.args.length] = ["measures[]", summarize];
            this.args[this.args.length] = ["measure_id_" + summarize, currentSummarize.id];
            this.args[this.args.length] = [currentSummarize.measure.id, currentSummarize.measure.value];
            
            var aggregate = '';
            if(currentSummarize.measure.value !== MEASURE_COUNT)
            {
                aggregate = currentSummarize.aggregate.value;
                this.args[this.args.length] = [currentSummarize.aggregate.id, aggregate];
            }
                
            olapCube.addMeasure(currentSummarize.measure.value, aggregate)
        }        
    }
    
    // Set Filter Data
    for(filter in filterData)
    {
        var currentFilter = filterData[filter];
        olapCube.addFilter(currentFilter.blogic, currentFilter.fieldName, currentFilter.operator, currentFilter.condValue);
    }
    
    if(this.args.length > 0)
        olapCube.print(tableResultCon);    
}

/**
 * Returns the array data of Table Option Values that will be used for saving the report
 *
 * @public
 * @this {AntObjectLoader_Report_Summary} 
 * @return {Array} this.args        Data array of Table Option Values
 */
AntObjectLoader_Report_Summary.prototype.getTableOptions = function()
{    
    return this.args;
}
