/**
* @fileOverview The New Report will display report form to create object or dataware report
*
* @author:    Marl Tumulak, marl.tumulak@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Pivot Table Options
 *
 * @constructor  
 * @param {Object} obj The parent Object
 */
function AntObjectLoader_Report_Dialog(obj)
{
    this.parentObject = obj;
    
    this.loaderCls = this.parentObject.loaderCls;
    this.bodyFormCon = this.parentObject.bodyFormCon;
    
    this.reportType = this.parentObject.reportType;
    this.objType = this.parentObject.objType;
    this.cubePath = this.parentObject.cubePath;
    
    this.newReportForm = new Object();
}

/**
* Prints the create new report dialog
*
* @public
* @this {AntObjectLoader_Report_Dialog}
*/
AntObjectLoader_Report_Dialog.prototype.print = function()
{
    this.reportObject = new Report(null);
    this.reportObject.cls = this;
    this.buildNewReportType();
}

/**
 * Builds the New Report Dialog
 *
 * @public
 * @this {AntObjectLoader_Report_Dialog}  
 */
AntObjectLoader_Report_Dialog.prototype.buildNewReportType = function()
{
    this.newReportDlg = new CDialog("New Report Type");
    this.newReportDlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    divModal.innerHTML = "<div class='loading'></div>";
    this.newReportDlg.customDialog(divModal, 460);
    
    this.reportObject.onload = function(ret)
    {
        this.cls.objectData = ret;
        this.cls.newReportTypeForm(divModal);
    }
    this.reportObject.getObjects();
    
    var closeCon = this.newReportDlg.m_titlecon.firstChild;
    closeCon.cls = this;
    closeCon.onclick = function()
    {
        this.cls.newReportDlg.hide();
        this.cls.loaderCls.close();
    }
}

/**
 * Builds the New Report Form
 *
 * @public
 * @this {AntObjectLoader_Report_Dialog}  
 * * @param {DOMElement} divModal The container where we can print the report form
 */
AntObjectLoader_Report_Dialog.prototype.newReportTypeForm = function(divModal)
{
    divModal.innerHTML = "";
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.newReportForm.lblHeader = alib.dom.setElementAttr(alib.dom.createElement("div"));
    this.newReportForm.lblHeader.innerHTML = "What kind of report this will be?";
    
    var attrData = [["id", "reportObject"], ["type", "radio"], ["name", "reportType"], ["checked", true], ["inputLabel", "Report on an object (like customer, lead, task)"]];
    this.newReportForm.reportObject = alib.dom.setElementAttr(alib.dom.createElement("input"), attrData);    
    
    this.newReportForm.objectTypes = alib.dom.setElementAttr(alib.dom.createElement("select"), [["id", "objectTypes"]]);
    
    var attrData = [["id", "reportDataware"], ["type", "radio"], ["name", "reportType"], ["inputLabel", "Report using Dataware Cube"]];
    this.newReportForm.reportDataware = alib.dom.setElementAttr(alib.dom.createElement("input"), attrData);
    
    var attrData = [["innerHTML", "Dataware cubes are predefined sets of aggregated data. To see available cubes go to the \"Reports\" application and browse under \"Data Cubes\"."]];
    this.newReportForm.lblDataware = alib.dom.setElementAttr(alib.dom.createElement("div"), attrData);
    
    this.newReportForm.cubePath = alib.dom.setElementAttr(alib.dom.createElement("input"), [["id", "cubePath"], ["type", "text"], ["disabled", "disabled"]]);
    
    var btn = new CButton("Create Report",
                            function(cls)
                            {
                                cls.createReport();
                            },
                            [this], "b1");
    this.newReportForm.createReport = btn.getButton();
        
    this.loaderCls.buildFormInput(this.newReportForm, tBody);
    
    // add additional elements
    var cubePathParent = this.newReportForm.cubePath.parentNode;
    var lblCubePath = alib.dom.setElementAttr(alib.dom.createElement("label"), [["innerHTML", "Cube Path "]]);    
    cubePathParent.insertBefore(lblCubePath, cubePathParent.firstChild);
    
    // Set Element Events
    this.newReportForm.reportObject.cls = this;
    this.newReportForm.reportObject.onclick = function()
    {
        this.cls.newReportForm.cubePath.setAttribute("disabled", "disabled");
        this.cls.newReportForm.objectTypes.removeAttribute("disabled");
    }
    
    this.newReportForm.reportDataware.cls = this;
    this.newReportForm.reportDataware.onclick = function()
    {
        this.cls.newReportForm.objectTypes.setAttribute("disabled", "disabled");
        this.cls.newReportForm.cubePath.removeAttribute("disabled");
    }
    
    // Set Input Styles
    alib.dom.styleSet(this.newReportForm.lblHeader, "fontWeight", "bold");
    alib.dom.styleSet(this.newReportForm.objectTypes, "visibility", "visible");
    alib.dom.styleSet(this.newReportForm.objectTypes, "marginLeft", "15px");
    alib.dom.styleSet(this.newReportForm.lblDataware, "marginLeft", "15px");
    alib.dom.styleSet(this.newReportForm.createReport, "float", "right");
    alib.dom.styleSet(lblCubePath, "marginLeft", "15px");    
    alib.dom.styleSet(lblCubePath, "fontWeight", "bold");    
    
    // Add Select Options
    for(object in this.objectData)
    {            
        var currentObject = this.objectData[object];
        this.newReportForm.objectTypes[this.newReportForm.objectTypes.length] = new Option(currentObject.fullTitle, currentObject.name);
    }
    
    this.newReportDlg.reposition();
}

/**
 * Saves the report in the database
 *
 * @public
 * @this {AntObjectLoader_Report_Dialog}   
 */
AntObjectLoader_Report_Dialog.prototype.createReport = function() 
{
    this.bodyFormCon.innerHTML = "<div class='loading'></div>";
    
    if(this.newReportForm.reportDataware.checked)
    {
        this.cubePath = this.newReportForm.cubePath.value;
        this.reportType = REPORT_TYPE_DATAWARE;
    }        
    else
        this.objType = this.newReportForm.objectTypes.value;
        
    var dlg = showDialog("Creating report...");
    this.reportObject.onsave = function(ret)
    {
        if(!ret)
        {
            ALib.statusShowAlert("Error occured when saving the report data.", 3000, "bottom", "right");
            return;
        }
        
        if(!ret.error)
        {
            this.cls.newReportDlg.hide();
            this.cls.onsave(ret);
            ALib.statusShowAlert("Report successfully created!", 3000, "bottom", "right");
        }
        else
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            
        dlg.hide();
    }
    
    var args = new Array();
    args[args.length] = ['objType', this.objType];
    args[args.length] = ['reportType', this.reportType];
    args[args.length] = ['cubePath', this.cubePath];
    this.reportObject.createReport(args);
}

/**
* To be over-ridden by calling process to detect when definition is finished saving.
*
* @public
* @this {Report} 
* @param {Object} ret   Object that is a result from ajax
*/
AntObjectLoader_Report_Dialog.prototype.onsave = function(ret)
{
}
