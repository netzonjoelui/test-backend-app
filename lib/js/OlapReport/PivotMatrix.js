/**
* @fileOverview The Pivot Matrix report will display the table options used in Olap Report Object
*
* @author:    Marl Tumulak, marl.tumulak@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

var DIMENSION_COLUMN      = 0;
var DIMENSION_ROW         = 1;

/**
 * Creates an instance of Pivot Table Options
 *
 * @constructor  
 * @param {Object} obj The parent Object
 */
function AntObjectLoader_Report_PivotMatrix(obj)
{    
    this.parentObject = obj;
    
    this.reportData = this.parentObject.reportData;
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
    this.measureHasCount = this.parentObject.measureHasCount;
    
    this.reportForm = new Object();
    this.reportForm.pivotData = new Object();
    this.reportForm.pivotColumn = new Array();
    this.reportForm.pivotRow = new Array();
    
    this.pivotColumnCount = 0; // Current count of tabular dimension
    this.pivotRowCount = 0; // Current count of tabular dimension
    
    this.savedDataLoaded = false; // Determines if the saved data already loaded
}

/**
 * Displays the table pivot matrix options
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_PivotMatrix} 
 * @param {DOMElement} divPivotCon The container where we can print the pivot matrix options 
 */
AntObjectLoader_Report_PivotMatrix.prototype.buildTablePivot = function(divPivotCon)
{
    var pivotDataId = null;
    var pivotDataMeasure = null;
    var pivotDataAggregate = null;
    if(this.reportMeasureData.length>0)
    {
        if(this.reportMeasureData[0].table_type == TABLE_PIVOT)
        {
            pivotDataId = this.reportMeasureData[0].id;
            pivotDataMeasure = this.reportMeasureData[0].name;
            pivotDataAggregate = this.reportMeasureData[0].aggregate;
        }
    }
    
    this.reportForm.pivotData.id = pivotDataId;
    this.reportForm.pivotData.showTotal = alib.dom.setElementAttr(alib.dom.createElement("div"), [["label", "Show Totals: &nbsp;"]]);
    this.reportForm.pivotData.column = alib.dom.setElementAttr(alib.dom.createElement("div"), [["label", "Columns:"]]);
    this.reportForm.pivotData.row = alib.dom.setElementAttr(alib.dom.createElement("div"), [["label", "Rows:"]]);
    this.reportForm.pivotData.measure = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "measure_name_"], ["label", "Data:"]]);    
    this.loaderCls.buildFormInput(this.reportForm.pivotData, divPivotCon);
        
    var columnParentCon = this.reportForm.pivotData.column.parentNode;
    var rowParentCon = this.reportForm.pivotData.row.parentNode;
        
    // Create Additional Elements
    this.reportForm.pivotTotals = new Object();
    var attrRow = [["type", "checkbox"], ["id", "f_row_totals"], ["label", "Row Totals"], ["checked", (this.reportData.f_row_totals=="f")?false:true]];
    this.reportForm.pivotTotals.row = alib.dom.setElementAttr(alib.dom.createElement("input"), attrRow);
    
    var attrColumn = [["type", "checkbox"], ["id", "f_column_totals"], ["label", "Column Totals"], ["checked", (this.reportData.f_column_totals=="f")?false:true]];
    this.reportForm.pivotTotals.column = alib.dom.setElementAttr(alib.dom.createElement("input"), attrColumn);
    
    var attrSub = [["type", "checkbox"], ["id", "f_sub_totals"], ["label", "Subtotals"], ["checked", (this.reportData.f_sub_totals=="f")?false:true]];
    this.reportForm.pivotTotals.sub = alib.dom.setElementAttr(alib.dom.createElement("input"), attrSub);
    
    this.loaderCls.buildFormInputDiv(this.reportForm.pivotTotals, this.reportForm.pivotData.showTotal, false, "15px");    
    
    var addDimensionLink = alib.dom.setElementAttr(alib.dom.createElement("a", columnParentCon), [["href", "javascript: void(0);"], ["innerHTML", "Add Column Dimension"]]);
    var addMeasureLink = alib.dom.setElementAttr(alib.dom.createElement("a", rowParentCon), [["href", "javascript: void(0);"], ["innerHTML", "Add Row Dimension"]]);
    
    var measureTd = this.reportForm.pivotData.measure.parentNode;
    this.reportForm.pivotData.aggregate = alib.dom.setElementAttr(alib.dom.createElement("select", measureTd), [["id", "measure_aggregate_"]]);
    
    // Set Element Events
    this.reportForm.pivotTotals.sub.cls = this;
    this.reportForm.pivotTotals.sub.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    addDimensionLink.cls = this;
    addDimensionLink.column = this.reportForm.pivotData.column;
    addDimensionLink.onclick = function()
    {
        this.cls.buildPivotColumn(this.column, this.cls.pivotColumnCount++);
        this.cls.buildOlapData();
    }
    
    addMeasureLink.cls = this;
    addMeasureLink.row = this.reportForm.pivotData.row;
    addMeasureLink.onclick = function()
    {
        this.cls.buildPivotRow(this.row, this.cls.pivotRowCount++);
        this.cls.buildOlapData();
    }
    
    this.reportForm.pivotData.measure.cls = this;
    this.reportForm.pivotData.measure.onchange = function()
    {
        if(this.value=='count')
            alib.dom.styleSet(this.cls.reportForm.pivotData.aggregate, "display", "none");
        else
            alib.dom.styleSet(this.cls.reportForm.pivotData.aggregate, "display", "inline-block");
            
        this.cls.buildOlapData();
    }
    
    this.reportForm.pivotData.aggregate.cls = this;
    this.reportForm.pivotData.aggregate.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    // Add Select Options
    if(!this.measureHasCount)   
        this.loaderCls.buildDropdown(this.reportForm.pivotData.measure, [MEASURE_COUNT], pivotDataMeasure);
        
    this.loaderCls.buildDropdown(this.reportForm.pivotData.aggregate, this.aggregateData, pivotDataAggregate);
        
    for(measure in this.measureData)
    {
        var currentMeasure = this.measureData[measure];
        var dimLen = this.reportForm.pivotData.measure.length;
        var selected = false;
        
        if(pivotDataMeasure == currentMeasure.name)
            selected = true;
            
        this.reportForm.pivotData.measure[dimLen] = new Option(currentMeasure.name, currentMeasure.name, false, selected);
    }
    
    // Set Element Styles
    alib.dom.styleSet(this.reportForm.pivotData.showTotal, "marginTop", "2px");    
    alib.dom.styleSet(this.reportForm.pivotData.aggregate, "marginLeft", "10px");
    alib.dom.styleSet(addDimensionLink, "fontSize", "11px");
    alib.dom.styleSet(columnParentCon.previousSibling, "verticalAlign", "top");
    alib.dom.styleSet(columnParentCon.previousSibling, "paddingTop", "5px");
    alib.dom.styleSet(addMeasureLink, "fontSize", "11px");
    alib.dom.styleSet(rowParentCon.previousSibling, "verticalAlign", "top");
    alib.dom.styleSet(rowParentCon.previousSibling, "paddingTop", "5px");
    
    if(pivotDataMeasure == MEASURE_COUNT || pivotDataMeasure == null)
        alib.dom.styleSet(this.reportForm.pivotData.aggregate, "display", "none");
    
    for(var x=0; x<this.pivotColumnCount; x++)
        this.buildPivotColumn(this.reportForm.pivotData.column, x);
    
    for(var x=0; x<this.pivotRowCount; x++)
        this.buildPivotRow(this.reportForm.pivotData.row, x);
    
    if(!this.savedDataLoaded)
    {
        this.displaySavedDims();        
        this.savedDataLoaded = true;
    }
    
    if(this.tableType == TABLE_PIVOT)
        this.buildOlapData();
    
}

/**
 * Displays the saved dimensions
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_PivotMatrix}  
 */
AntObjectLoader_Report_PivotMatrix.prototype.displaySavedDims = function()
{
    if(this.reportDimensionData.length)
    {        
        for(dimension in this.reportDimensionData)
        {
            var currentDimension = this.reportDimensionData[dimension];
            
            if(currentDimension.table_type == TABLE_PIVOT)
            {
                if(currentDimension.f_column=="t")
                {
                    this.buildPivotColumn(this.reportForm.pivotData.column, this.pivotColumnCount);
                    this.setDimValues(this.reportForm.pivotColumn[this.pivotColumnCount], currentDimension)
                    
                    this.pivotColumnCount++;
                }
                else if(currentDimension.f_row=="t")
                {
                    this.buildPivotRow(this.reportForm.pivotData.row, this.pivotRowCount);
                    this.setDimValues(this.reportForm.pivotRow[this.pivotRowCount], currentDimension)
                    
                    this.pivotRowCount++;
                }
            }
        }        
    }
}

/**
 * Displays the saved dimensions
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_PivotMatrix}  
 * @param {Object} currentPivot         Object of the current pivot column / row
 * @param {Object} currentDimension     Object row of the current dimension data in this.reportDimensionData
 */
AntObjectLoader_Report_PivotMatrix.prototype.setDimValues = function(currentPivot, currentDimension)
{
    var dimType = this.dimTypeData[currentDimension.name];
    this.parentObject.displayFormat(dimType, currentPivot.lblRow, currentPivot.format);
    
    currentPivot.id = currentDimension.id;
    currentPivot.dimension.value = currentDimension.name;
    currentPivot.dimension.dimType = dimType;
    currentPivot.sort.value = currentDimension.sort;
    currentPivot.format.value = currentDimension.format;
}

/**
 * Displays the table pivot matrix column options
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_PivotMatrix} 
 * @param {divPivotColumn} divPivotColumn   The container where we can print the pivot matrix column options 
 * @param {Integer} currentCount            Holds the current count of pivot matrix column
 */
AntObjectLoader_Report_PivotMatrix.prototype.buildPivotColumn = function(divPivotColumn, currentCount)
{
    var newColumn = true;
    var divPivot = alib.dom.createElement("div", divPivotColumn);
    alib.dom.styleSet(divPivot, "marginTop", "5px");    

    if(this.reportForm.pivotColumn[currentCount])
        newColumn = false;
    else
        this.reportForm.pivotColumn[currentCount] = new Object();
        
    var currentPivotColumn = this.reportForm.pivotColumn[currentCount];
    
    if(newColumn)
    {
        currentPivotColumn.id = null;
        currentPivotColumn.dimension = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_name_" + currentCount + DIMENSION_COLUMN]]);
        currentPivotColumn.sort = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_sort_" + currentCount + DIMENSION_COLUMN]]);
        currentPivotColumn.lblColumn = alib.dom.setElementAttr(alib.dom.createElement("label"), [["innerHTML", "By"]]);
        currentPivotColumn.format = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_format_" + currentCount + DIMENSION_COLUMN]]);    
        currentPivotColumn.removeColumn = alib.dom.createElement("img");
    }
    
    this.loaderCls.buildFormInputDiv(currentPivotColumn, divPivot, false, "10px");
    alib.dom.divClear(divPivot);
    
    // Set Element Events    
    currentPivotColumn.removeColumn.divPivotColumn = divPivotColumn;
    currentPivotColumn.removeColumn.divPivot = divPivot;
    currentPivotColumn.removeColumn.cls = this;
    currentPivotColumn.removeColumn.currentCount = currentCount;
    currentPivotColumn.removeColumn.onclick = function()
    {
        this.divPivotColumn.removeChild(this.divPivot);
        delete this.cls.reportForm.pivotColumn[this.currentCount];
        this.cls.buildOlapData();
        
        // TO DO: create a list of deleted objects, so the loop wont recreate it
        // TO DO: remove the filter in the database if its already a saved filter
    }
    
    currentPivotColumn.dimension.cls = this;
    currentPivotColumn.dimension.lblColumn = currentPivotColumn.lblColumn;
    currentPivotColumn.dimension.currentPivotColumn = currentPivotColumn;
    currentPivotColumn.dimension.onchange = function()
    {           
        this.dimType = this.cls.dimTypeData[this.value];
         
        this.cls.parentObject.displayFormat(this.dimType, this.lblColumn, this.currentPivotColumn.format);        
        this.cls.buildOlapData();
    }
    
    currentPivotColumn.sort.cls = this;
    currentPivotColumn.sort.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    currentPivotColumn.format.cls = this;
    currentPivotColumn.format.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    // Set Element Data
    currentPivotColumn.removeColumn.src = this.deleteImage;
    
    // Set Element Style
    alib.dom.styleSet(currentPivotColumn.removeColumn, "cursor", "pointer");
    alib.dom.styleSet(currentPivotColumn.removeColumn, "marginTop", "3px");    
    
    if(!newColumn) // Do not re-populate the dropdown list
        return;
    
    // Add select options    
    this.loaderCls.buildDropdown(currentPivotColumn.sort, this.sortData);
    
    this.loaderCls.buildDropdown(currentPivotColumn.format, this.formatData);
    
    var defaultType = null;
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = currentPivotColumn.dimension.length
        
        switch(currentDimension.type)
        {
            case 'timestamp':
            case 'time':
            case 'date':                
            case 'numeric':
            case 'number':
            case 'real':
            case 'integer':
            case 'fkey':
            case 'fkey_multi':
            default: // Set default for the mean time
                currentPivotColumn.dimension[dimLen] = new Option(currentDimension.name, currentDimension.name);        
                currentPivotColumn.dimension[dimLen].dimType = currentDimension.type;
                
                // get the first dimension type
                if(defaultType==null)
                    defaultType = currentDimension.type;
                break;
        }
    }
    
    this.parentObject.displayFormat(defaultType, currentPivotColumn.lblColumn, currentPivotColumn.format);    
    currentCount++; // increment the count to be used for the next index
}

/**
 * Displays the table pivot matrix row options
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_PivotMatrix} 
 * @param {divPivotColumn} divPivotRow      The container where we can print the pivot matrix row options 
 * @param {Integer} currentCount            Holds the current count of pivot matrix column
 */
AntObjectLoader_Report_PivotMatrix.prototype.buildPivotRow = function(divPivotRow, currentCount)
{
    var newRow = true;
    var divPivot = alib.dom.createElement("div", divPivotRow);
    alib.dom.styleSet(divPivot, "marginTop", "5px");    
    
    if(this.reportForm.pivotRow[currentCount])
        newRow = false;
    else
        this.reportForm.pivotRow[currentCount] = new Object();
        
    var currentPivotRow = this.reportForm.pivotRow[currentCount];
    
    if(newRow)
    {
        currentPivotRow.id = null;
        currentPivotRow.dimension = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_name_" + currentCount + DIMENSION_ROW]]);
        currentPivotRow.sort = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_sort_" + currentCount + DIMENSION_ROW]]);
        currentPivotRow.lblRow = alib.dom.setElementAttr(alib.dom.createElement("label"), [["innerHTML", "By"]]);
        currentPivotRow.format = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_format_" + currentCount + DIMENSION_ROW]]);    
        currentPivotRow.removeRow = alib.dom.createElement("img");
    }
    
    this.loaderCls.buildFormInputDiv(currentPivotRow, divPivot, false, "10px");
    alib.dom.divClear(divPivot);
    
    // Set Element Events    
    currentPivotRow.removeRow.divPivotRow = divPivotRow;
    currentPivotRow.removeRow.divPivot = divPivot;
    currentPivotRow.removeRow.cls = this;
    currentPivotRow.removeRow.pivotRowCount = currentCount;
    currentPivotRow.removeRow.onclick = function()
    {
        this.divPivotRow.removeChild(this.divPivot);
        delete this.cls.reportForm.pivotRow[this.currentCount];
        this.cls.buildOlapData();
        
        // TO DO: create a list of deleted objects, so the loop wont recreate it 
        // TO DO: remove the filter in the database if its already a saved filter
    }
    
    currentPivotRow.dimension.cls = this;
    currentPivotRow.dimension.lblRow = currentPivotRow.lblRow;
    currentPivotRow.dimension.currentPivotRow = currentPivotRow;
    currentPivotRow.dimension.onchange = function()
    {        
        this.dimType = this.cls.dimTypeData[this.value];
         
        this.cls.parentObject.displayFormat(this.dimType, this.lblRow, this.currentPivotRow.format);        
        this.cls.buildOlapData();
    }
    
    currentPivotRow.sort.cls = this;
    currentPivotRow.sort.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    currentPivotRow.format.cls = this;
    currentPivotRow.format.onchange = function()
    {
        this.cls.buildOlapData();
    }
    
    // Set Element Data
    currentPivotRow.removeRow.src = this.deleteImage;
    
    // Set Element Style
    alib.dom.styleSet(currentPivotRow.removeRow, "cursor", "pointer");
    alib.dom.styleSet(currentPivotRow.removeRow, "marginTop", "3px");    
    
    if(!newRow) // Do not re-populate the dropdown list
        return;
    
    // Add select options    
    this.loaderCls.buildDropdown(currentPivotRow.sort, this.sortData);    
    this.loaderCls.buildDropdown(currentPivotRow.format, this.formatData);
    
    var defaultType = null;
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = currentPivotRow.dimension.length
                
        switch(currentDimension.type)
        {
            case 'timestamp':
            case 'time':
            case 'date':                
            case 'numeric':
            case 'number':
            case 'real':
            case 'integer':
            case 'fkey':
            case 'fkey_multi':
            default: // Set default for the mean time
                currentPivotRow.dimension[dimLen] = new Option(currentDimension.name, currentDimension.name);        
                currentPivotRow.dimension[dimLen].dimType = currentDimension.type;
                
                // get the first dimension type
                if(defaultType==null)
                    defaultType = currentDimension.type;
                break;
        }
    }
    
    this.parentObject.displayFormat(defaultType, currentPivotRow.lblRow, currentPivotRow.format);    
    currentCount++; // increment the count to be used for the next index
}

/**
 * Displays the Summary Olap Cube Data in table
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix_Summary} 
 */
AntObjectLoader_Report_PivotMatrix.prototype.buildOlapData = function()
{
    var tableResultCon = this.parentObject.tableResultCon;    
    var filterData = this.parentObject.filterData;
    var finishBuilding = this.parentObject.finishBuilding;    
    
    if(!finishBuilding)
        return; 
        
    // Set Olap Cube
    var cube = this.parentObject.cube;
    var olapCube = new OlapCube_Table_Matrix(cube);
    olapCube.showSubtotals = this.reportForm.pivotTotals.sub.checked
    
    this.args = new Array();
    
    // Set Column Data
    for(column in this.reportForm.pivotColumn)
    {
        if(column)
        {
            var currentColumn = this.reportForm.pivotColumn[column];
            
            this.buildDimensionArg(column, currentColumn, DIMENSION_COLUMN);
            
            switch(currentColumn.dimension.dimType)
            {
                case "timestamp":
                case "date":
                case "time":                    
                    olapCube.addColumn(currentColumn.dimension.value, currentColumn.sort.value, currentColumn.format.value);
                    this.args[this.args.length] = [currentColumn.format.id, currentColumn.format.value];
                    break;
                default:
                    olapCube.addColumn(currentColumn.dimension.value, currentColumn.sort.value);                    
                break;
            }
        }        
    }
    
    // Set Row Data
    for(row in this.reportForm.pivotRow)
    {
        if(row)
        {
            var currentRow = this.reportForm.pivotRow[row];
            
            this.buildDimensionArg(row, currentRow, DIMENSION_ROW);
            
            switch(currentRow.dimension.dimType)
            {
                case "timestamp":
                case "date":
                case "time":                    
                    olapCube.addRow(currentRow.dimension.value, currentRow.sort.value, currentRow.format.value);
                    this.args[this.args.length] = [currentRow.format.id, currentRow.format.value];
                    break;
                default:
                    olapCube.addRow(currentRow.dimension.value, currentRow.sort.value);
                break;
            }
        }        
    }
    
    // Set Measure Data
    var aggregate = this.reportForm.pivotData.aggregate.value;
    
    if(this.reportForm.pivotData.measure.value == MEASURE_COUNT)
        aggregate = '';
    
    olapCube.addMeasure(this.reportForm.pivotData.measure.value, aggregate);
    
    // Set Measure Args
    var summarize = 0;
    this.args[this.args.length] = ["measures[]", summarize];
    this.args[this.args.length] = ["measure_id_" + summarize, this.reportForm.pivotData.id];
    this.args[this.args.length] = [this.reportForm.pivotData.measure.id + summarize, this.reportForm.pivotData.measure.value];
    this.args[this.args.length] = [this.reportForm.pivotData.aggregate.id + summarize, aggregate];
    
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
 * Builds the dimension arg
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix}  
 * @param {Integer} idx             Index of current row/column
 * @param {object} dim              Dimension object that has the current form elements
 * @param {String} type             Determines if its column or row. (col / row)
 */
AntObjectLoader_Report_PivotMatrix.prototype.buildDimensionArg = function(idx, dim, type)
{
    this.args[this.args.length] = ["dimensions[]", idx + type];
    this.args[this.args.length] = ["dimension_id_" + idx + type, dim.id];    
    this.args[this.args.length] = [dim.dimension.id, dim.dimension.value];
    this.args[this.args.length] = [dim.sort.id, dim.sort.value];
    this.args[this.args.length] = ["f_column_" + idx + type, (type==DIMENSION_COLUMN)?true:false];
    this.args[this.args.length] = ["f_row_" + idx + type, (type==DIMENSION_ROW)?true:false];
}

/**
 * Returns the report form data object
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix} 
 * @return {Object} this.reportForm         Object data that contains the form elements
 */
AntObjectLoader_Report_PivotMatrix.prototype.getReportForm = function()
{
    return this.reportForm;
}

/**
 * Returns the array data of Table Option Values that will be used for saving the report
 *
 * @public
 * @this {AntObjectLoader_Report_PivotMatrix} 
 * @return {Array} this.args        Data array of Table Option Values
 */
AntObjectLoader_Report_PivotMatrix.prototype.getTableOptions = function()
{    
    return this.args;
}
