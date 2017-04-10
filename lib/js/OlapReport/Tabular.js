/**
* @fileOverview The tabular report will display the table options used in Olap Report Object
*
* @author:    Marl Tumulak, marl.tumulak@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Tabular Table Options
 *
 * @constructor  
 * @param {Object} obj The parent Object
 */
function AntObjectLoader_Report_Tabular(obj)
{    
    this.parentObject = obj;
    this.reportDimensionData = this.parentObject.reportDimensionData;
    
    this.dimensionData = this.parentObject.dimensionData;
    this.filterData = this.parentObject.filterData;
    this.sortData = this.parentObject.sortData;
    
    this.loaderCls = this.parentObject.loaderCls;
    this.tableType = this.parentObject.tableType;
    this.deleteImage = this.parentObject.deleteImage;
    
    this.tabularDimensionData = new Array();
    this.tabularCount = 0; // Current count of tabular dimension
    
    this.savedDataLoaded = false; // Determines if the saved data already loaded
}

/**
 * Displays the table tabular options
 *
 * @public
 * @this {AntObjectLoader_Report_Tabular} 
 * @param {DOMElement} divTabularCon The container where we can print the tabular options 
 */
AntObjectLoader_Report_Tabular.prototype.buildTableTabular = function(divTabularCon)
{
    reportTabularObj = new Object();
    
    reportTabularObj.lblDimension = alib.dom.setElementAttr(alib.dom.createElement("label"), [["innerHTML", "View Fields:"]]);    
    reportTabularObj.dimension = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_name"]]);    
    reportTabularObj.sort = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "dimension_sort"]]);
    
    this.loaderCls.buildFormInputDiv(reportTabularObj, divTabularCon, false, "10px");
    alib.dom.divClear(divTabularCon);
    
    // Tabular Link
    var divLink = alib.dom.createElement("div", divTabularCon);
    var linkData = [["innerHTML", "Add Dimension"], ["href", "javascript: void(0)"]];
    var linkAddDim = alib.dom.setElementAttr(alib.dom.createElement("a", divLink), linkData);
    
    // Dimension display
    var divTabularDimension = alib.dom.createElement("div", divTabularCon);
    
    // Set Element Style
    alib.dom.styleSet(divLink, "marginLeft", "75px");
    alib.dom.styleSet(divTabularDimension, "marginLeft", "75px");
    alib.dom.styleSet(reportTabularObj.lblDimension, "fontSize", "12px");
    alib.dom.styleSet(reportTabularObj.lblDimension.parentNode, "marginTop", "2px");    
    
    // Create Element Event
    linkAddDim.cls = this;
    linkAddDim.reportTabularObj = reportTabularObj;    
    linkAddDim.divTabularDimension = divTabularDimension;    
    linkAddDim.onclick = function()
    {
        this.cls.buildTabularDimension(this.divTabularDimension, this.cls.tabularCount);
        
        var dimObj = {id:null, name:this.reportTabularObj.dimension.value, sort:this.reportTabularObj.sort.value};        
        this.cls.setDimensionData(dimObj, this.cls.tabularCount); 
        
        this.cls.buildOlapData();        
        this.cls.tabularCount++;
    }
    
    // Add Select Options    
    this.loaderCls.buildDropdown(reportTabularObj.sort, this.sortData);
    
    for(dimension in this.dimensionData)
    {
        var currentDimension = this.dimensionData[dimension];
        var dimLen = reportTabularObj.dimension.length
        
        reportTabularObj.dimension[dimLen] = new Option(currentDimension.name, currentDimension.name);        
    }
    
    // Load Dimensions
    for(var x=0; x<this.tabularCount; x++)
        this.buildTabularDimension(divTabularDimension, x);
    
    // Load Saved Dimensions
    if(!this.savedDataLoaded)
    {
        this.displaySavedDim(divTabularDimension);        
        this.savedDataLoaded = true;
    }
    
    if(this.tableType == TABLE_TABULAR)
        this.buildOlapData();
}

/**
 * Displays the Tablar Olap Cube Data in table
 *
 * @public
 * @this {AntObjectLoader_Report_Tabular} 
 * @param {DOMElement} divTabularDimension   The container where we can print the tabular dimensions
 * @param {Integer} currentCount             Holds the current count of summarize
 */
AntObjectLoader_Report_Tabular.prototype.buildTabularDimension = function(divTabularDimension, currentCount)
{
    var newDimension = true;
    var divDimension = alib.dom.createElement("div", divTabularDimension);
    alib.dom.styleSet(divDimension, "marginTop", "5px");
    
    if(this.tabularDimensionData[currentCount])
        newDimension = false;
    else
        this.tabularDimensionData[currentCount] = new Object();
    
    var currentDimension = this.tabularDimensionData[currentCount];
    
    if(newDimension)
    {
        // Set Current Dimension Id to null value
        currentDimension.id = null;
        currentDimension.dimension = alib.dom.setElementAttr(alib.dom.createElement("div"), [["id", "dimension_name_" + currentCount]]);
        currentDimension.sort = alib.dom.setElementAttr(alib.dom.createElement("div"), [["id", "dimension_sort_" + currentCount]]);
        currentDimension.removeDimension = alib.dom.createElement("img");
    }
    
    this.loaderCls.buildFormInputDiv(currentDimension, divDimension, false, "10px");
    alib.dom.divClear(divDimension);
    
    // Set Element Events
    currentDimension.removeDimension.divTabularDimension = divTabularDimension;
    currentDimension.removeDimension.divDimension = divDimension;
    currentDimension.removeDimension.cls = this;
    currentDimension.removeDimension.currentCount = currentCount;
    currentDimension.removeDimension.onclick = function()
    {
        this.divTabularDimension.removeChild(this.divDimension);
        
        delete this.cls.tabularDimensionData[currentCount]
        this.cls.buildOlapData();
    }
    
    // Set Element Data
    currentDimension.removeDimension.src = this.deleteImage;
    
    // Set Element Style
    alib.dom.styleSet(currentDimension.removeDimension, "cursor", "pointer");
    alib.dom.styleSet(currentDimension.removeDimension, "marginTop", "3px");
}

/**
 * Displays the Tablar Olap Cube Data in table
 *
 * @public
 * @param {Object} dimObj   Object row of the current dimension data
 */
AntObjectLoader_Report_Tabular.prototype.setDimensionData = function(dimObj, currentCount)
{
    var currentDimension = this.tabularDimensionData[currentCount];
    
    currentDimension.id = dimObj.id;
    currentDimension.dimension.innerHTML = dimObj.name;
    currentDimension.sort.innerHTML = dimObj.sort;
}

/**
 * Displays the saved dimension table 
 *
 * @public
 * @this {AntObjectLoader_Report_Tabular} 
 * @param {DOMElement} divTabularDimension   The container where we can print the tabular dimensions
 */
AntObjectLoader_Report_Tabular.prototype.displaySavedDim = function(divTabularDimension)
{
    if(this.reportDimensionData.length)
    {        
        for(dimension in this.reportDimensionData)
        {
            var currentDimension = this.reportDimensionData[dimension];
            
            if(currentDimension.table_type == TABLE_TABULAR)
            {
                this.buildTabularDimension(divTabularDimension, this.tabularCount);
            
                this.setDimensionData(currentDimension, this.tabularCount);
                this.tabularCount++;
            }            
        }
    }
}

/**
 * Displays the Tablar Olap Cube Data in table
 *
 * @public
 * @this {AntObjectLoader_Report_Tabular} 
 */
AntObjectLoader_Report_Tabular.prototype.buildOlapData = function()
{
    var tableResultCon = this.parentObject.tableResultCon;
    var filterData = this.parentObject.filterData;
    var finishBuilding = this.parentObject.finishBuilding;
    
    if(!finishBuilding)
        return; 
        
    // Set Olap Cube
    var cube = this.parentObject.cube;
    var olapCube = new OlapCube_Table_Tabular(cube);
    
    this.args = new Array();
    
    // Set Dimension Data
    for(dimension in this.tabularDimensionData)
    {
        var currentDimension = this.tabularDimensionData[dimension];
        
        this.args[this.args.length] = ["dimensions[]", dimension];
        this.args[this.args.length] = ["dimension_id_" + dimension, currentDimension.id];
        this.args[this.args.length] = ["f_column_" + dimension, false];
        this.args[this.args.length] = ["f_row_" + dimension, false];
        this.args[this.args.length] = ["dimension_name_" + dimension, currentDimension.dimension.innerHTML];
        this.args[this.args.length] = ["dimension_sort_" + dimension, currentDimension.sort.innerHTML];
        
        olapCube.addColumn(currentDimension.dimension.innerHTML, currentDimension.sort.innerHTML);
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
 * @this {AntObjectLoader_Report_Tabular} 
 * @return {Array} this.args        Data array of Table Option Values
 */
AntObjectLoader_Report_Tabular.prototype.getTableOptions = function()
{    
    return this.args;
}
