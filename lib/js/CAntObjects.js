/**
* @fileoverview This is a Object used to display Ant Objects
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of AntObjectLoader_Dashboard.
*
* @constructor
*/
function CAntObjects()
{
    this.objCls = null;
    this.tblObject = null;
    this.appName = null;
    this.fObjectReference = false;
}

/**
 * Call back function after listing the ant objects
 *
 * @public
 * @this {class}
 */
CAntObjects.prototype.onLoadObjects = function()
{
}

/**
 * Opens a dialog form to save a new object
 *
 * @public
 * @this {class}
 */
CAntObjects.prototype.addNewObject = function()
{
    var dlg = new CDialog("Create New Object");
    var objectCon = alib.dom.createElement("div");
    var nameCon = alib.dom.createElement("div", objectCon);
    var buttonCon = alib.dom.createElement("div", objectCon);
    alib.dom.styleSet(buttonCon, "margin-top", "20px");
    buttonCon.align = "right";
    
    alib.dom.setElementAttr(alib.dom.createElement("label", nameCon), [["innerHTML", "Object Name: "]]);
    var objectName = alib.dom.setElementAttr(alib.dom.createElement("input", nameCon));    
    alib.dom.styleSet(objectName, "width", "200px");
    
    var btn = new CButton("Save Object", 
    function(cls, objectName, dlg)
    {
        cls.saveNewObject(objectName.value);
        dlg.hide();
    },
    [this, objectName, dlg], "b2");
    buttonCon.appendChild(btn.getButton());
    
    // refresh button
    var btn = new CButton("Cancel", 
    function(cls, dlg)
    {
		dlg.hide();
    },
    [this, dlg], "b1");
    buttonCon.appendChild(btn.getButton());
    
    dlg.customDialog(objectCon, 280);
}

/**
 * Saves a new object
 *
 * @public
 * @this {class}
 */
CAntObjects.prototype.saveNewObject = function(objectName)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dlg = showDialog("Saving, please wait...");
    ajax.cbData.objectName = objectName;
    ajax.onload = function(ret)
    {
        this.cbData.dlg.hide();
        if(!ret)
            return;
            
        if(ret['error'])
            ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
        else
        {
            var newAntObj = new Object();
            newAntObj.fullTitle = this.cbData.objectName;
            newAntObj.id = ret.id;
            newAntObj.name = ret.name;
            this.cbData.cls.listObject(newAntObj);
            ALib.statusShowAlert("Object " + this.cbData.objectName + " successfully saved", 3000, "bottom", "right");
        }
    };
    
    var args = new Array();
    args[args.length] = ["obj_name", objectName];
    args[args.length] = ["app", this.appName];
    ajax.exec("/controller/Application/createObject", args);
}

/**
 * Display the Ant Objects
 *
 * @public
 * @this {class}
 * @param {DOMElement} con      Container of CTooltable List
 */
CAntObjects.prototype.loadObjects = function(con)
{
    this.tblObject.clear();
    loadingCon = alib.dom.createElement("element", con);
    loadingCon.innerHTML = "<div class='loading'></div>";
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.cbData.loadingCon = loadingCon;
    ajax.onload = function(ret)
    {
        this.cbData.loadingCon.parentNode.removeChild(this.cbData.loadingCon);
        this.cbData.cls.mapObject(ret);
        this.cbData.cls.onLoadObjects();
    };
    
    if(this.fObjectReference)
    {
        var args = new Array();
        args[args.length] = ['app', this.appName];
        ajax.exec("/controller/Application/getObjectReference", args);
    }
    else
        ajax.exec("/controller/Object/getObjects");
}

/**
 * Display the Ant Objects
 *
 * @public
 * @this {class}
 * @param {Object} objectData       Contains the data of Ant Objects
 */
CAntObjects.prototype.mapObject = function(objectData)
{
    for(object in objectData)
    {            
        this.listObject(objectData[object]);
        
    }
}
    
/**
 * Display the Ant Objects
 *
 * @public
 * @this {class}
 * @param {Object} currentObject        Contains the data of the current object
 */
CAntObjects.prototype.listObject = function(currentObject)
{
    var rw = this.tblObject.addRow();    
    var objName = currentObject.fullTitle;
    
    if(typeof objName == "undefined")
        objName = currentObject.name;
    
    // Edit object
    var lnk = alib.dom.createElement("a");
    lnk.innerHTML = objName;
    lnk.href = "javascript:void(0);";
    lnk.obj_type = currentObject.name;
    lnk.onclick = function() 
    {
        var objedt_dlg = new Ant.EntityDefinitionEdit(this.obj_type);
        objedt_dlg.showDialog();            
    }        
    rw.addCell(lnk, false, "left");
    
    // Browse Objects
    if(!this.fObjectReference)
    {
		/*
        var lnk = alib.dom.createElement("a");
        lnk.innerHTML = "[browse/view]";
        lnk.href = "javascript:void(0);";
        lnk.obj_type = currentObject.name;
        lnk.onclick = function() 
        {
            window.open("/objb/" + this.obj_type);
        }
        rw.addCell(lnk, false, "center");
		*/
    }        

    // Import data
    var lnk = alib.dom.createElement("a");
    lnk.innerHTML = "[import data]";
    lnk.href = "javascript:void(0);";
    lnk.obj_type = currentObject.name;
    lnk.onclick = function() 
    {
		var wiz = new AntWizard("EntityImport", {obj_type:this.obj_type});
		wiz.show();
    }
    rw.addCell(lnk, false, "center");

	/*(
    // Edit permissions
    var lnk = alib.dom.createElement("a");
    lnk.innerHTML = "[edit permissions]";
    lnk.href = "javascript:void(0);";
    lnk.obj_type = currentObject.name;
    lnk.onclick = function() { loadDacl(null, '/objects/' + this.obj_type); }
    rw.addCell(lnk, false, "center");
	*/
    
    // Delete custom objects
    if (currentObject.fSystem)
        rw.addCell("&nbsp;", false, "center");
    else
    {
        var lnk = alib.dom.createElement("a");
        lnk.innerHTML = "[delete]";
        lnk.href = "javascript:void(0);";
        lnk.objectName = currentObject.name;
        lnk.cls = this;
        lnk.row = rw;
        lnk.onclick = function() { this.cls.deleteObject(this.objectName, this.row); }
        rw.addCell(lnk, false, "center");
    }
}

/**
 * Display the Ant Objects
 *
 * @public
 * @this {class} 
 * @param {String} objectName              Current Object Name
 * @param {DOMElement} row              Current row to be deleted
 */
CAntObjects.prototype.deleteObject = function(objectName, row)
{
    if(confirm("Are you sure you want to delete this " + objectName + " object?", "Delete Referenced Object"))
    {
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.dlg = showDialog("Deleting, please wait...");
        ajax.cbData.row = row;
        ajax.cbData.objectName = objectName;
        ajax.onload = function(ret)
        {
            this.cbData.dlg.hide();
            if(!ret)
                return;
                
            if(ret['error'])
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            else
            {
                this.cbData.row.deleteRow();
                ALib.statusShowAlert("Object " + this.cbData.objectName + " successfully saved", 3000, "bottom", "right");
            }
        };
        
        var args = new Array();
        args[args.length] = ["obj_type", objectName];
        args[args.length] = ["app", this.appName];
        args[args.length] = ["f_obj_reference", this.fObjectReference];
        ajax.exec("/controller/Application/deleteObjectReference", args);
    }
}
