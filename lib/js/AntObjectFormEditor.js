/**
 * @fileoverview This class handles editing ant object
 *
 * Example:
 * <code>
 *     var objEditor = new AntObjectViewEditor("customer"); 
 *    objEditor.print(con);
 * </code>
 *
 * @author     Marl Tumulak, marl.tumulak@aereus.com.
 *             Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectViewEditor
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
function AntObjectFormEditor(obj_type)
{
    /**
     * Instance of CAntObject of type obj_type - loads object data if oid is defined
     *
     * @type {CAntObject}
     * @public
     */
    this.mainObject = new CAntObject(obj_type);
    
    /**
     * Container object used by outside classes to store callback properties
     *
     * @public
     * @var {Object}
     */
    this.cbData = new Object();
    
    /**
     * The main container
     *
     * @public
     * @var {DOMElement}
     */
    this.mainCon = null;
    
    /**
     * The div container for the editor
     *
     * @public
     * @var {DOMElement}
     */
    this.editorCon = null;
    
    /**
     * The div container for the toolbars
     *
     * @public
     * @var {DOMElement}
     */
    this.toolbarCon = null;
    
    /**
     * The div container of canvas
     *
     * @public
     * @var {DOMElement}
     */
    this.canvasCon = null;
    
    /**
     * Name of the dropzone
     *
     * @public
     * @var {String}
     */
    this.dropZoneName = "dropZone";
    
    /**
     * Object form scope
     *
     * @public
     * @var {String}
     */
    this.scope = "default";
    
    /**
     * Set the current type of scope
     *
     * @public
     * @var {String}
     */
    this.scopeType = null;
    
    /**
     * Current user Id
     *
     * @public
     * @var {Integer}
     */
    this.userId = null;
    
    /**
     * Current team Id
     *
     * @public
     * @var {Integer}
     */
    this.teamId = null;
    
    /**
     * Count of the containers
     *
     * @public
     * @var {Integer, incremental}
     */
    this.containerIndex = 0;
    
    /**
     * Group number of dropzone
     *
     * @public
     * @var {Integer, incremental}
     */
    this.dropZoneGroup = 0;
    
    /**
     * Current number of tabs in editor form
     *
     * @public
     * @var {Integer, incremental}
     */
    this.numTabs = 0;
    
    /**
     * Currently available tabs
     *
     * @public
     * @var {Integer, incremental}
     */
    this.availableTabs = new Array();
    
    /**
     * This will be used to create new tabs
     *
     * @public
     * @var {Object Class}
     */
    this.formViewTabs = new CTabs();
    
    /**
     * Form UIML Structure
     *
     * @public
     * @var {Object Class}
     */
     this.uiml = new Object();
     
     /**
     * Determines whether the xml code view was changed
     *
     * @public
     * @var {Boolean}
     */
     this.codeViewChanged = false;
}

/**
 * Prints the object editor
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con A dom container where the editor will be printed
 */
AntObjectFormEditor.prototype.print = function(con)
{
    this.mainCon = con;
    this.checkDefaultForm();
}

/**
 * Builds form editor interface
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.buildInterface = function()
{
    // Reset the class variables
    this.containerIndex = 0;
    this.dropZoneGroup = 0;
    this.numTabs = 0;
    this.availableTabs = new Array();
    this.formViewTabs = new CTabs();
    
    this.mainCon.innerHTML = "";
    this.toolbarCon = alib.dom.createElement("div", this.mainCon);
    this.editorCon = alib.dom.createElement("div", this.mainCon);
    
    this.buildToolbar();
    this.buildEditor();
}

/**
 * Builds toolbar buttons
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.buildToolbar = function()
{
    var toolbar = new CToolbar();
    
    // Close Button
    var buttonClose = alib.ui.Button("Close", 
                        {
                            className:"b1", callback:this,
                            onclick:function() 
                            {
                                window.close();
                            }
                        });
    toolbar.AddItem(buttonClose.getButton());
    
    // Save & Close Button
    var buttonSaveClose = alib.ui.Button("Save & Close", 
                            {
                                className:"b2", callback:this,
                                onclick:function() 
                                {
                                    this.callback.saveObjectForm(true);
                                }
                            });
    toolbar.AddItem(buttonSaveClose.getButton());
    
    // Save Button
    var buttonSave = alib.ui.Button("Save", 
                            {
                                className:"b2", callback:this,
                                onclick:function() 
                                {
                                    this.callback.saveObjectForm(false);
                                }
                            });
    toolbar.AddItem(buttonSave.getButton());
    
    // Print Toolbar
    toolbar.print(this.toolbarCon);
}

/**
 * Builds toolbar buttons
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.buildEditor = function()
{
    // Form View and Code View tabs
    this.tabsEditor = new CTabs();
    var tabForm = this.tabsEditor.addTab("Form View");
    var tabCode = this.tabsEditor.addTab("Code View", 
                    function (cls)
                    {
                        cls.setupCodeView();
                    }, [this]);
                    
    this.tabsEditor.cbData.cls = this;
    this.tabsEditor.onSelectTab = function(index)
    {
        if(index == 0)
        {
            var callack = this.cbData.cls;
            if(callack.codeViewChanged)
            {
                ALib.Dlg.confirmBox("Swtiching to Form View needs to save the changes made. Do you want to proceed?", "Confirm Changes");
                ALib.Dlg.onConfirmOk = function()
                {
                    callack.saveObjectForm(false);
                    callack.codeViewChanged = false;
                    return true;
                }
                return false;
            }
        }
        
        return true;
    }
    
    tabForm.id = "formView";
    tabCode.id = "codeView";
    this.tabsEditor.print(this.editorCon);
    
    // Form View Tab
    var containerEditor = new CSplitContainer();
    containerEditor.resizable = true;
    
    this.formCanvas = containerEditor.addPanel("*");
    this.formFields = containerEditor.addPanel("230px");
    containerEditor.print(tabForm);
    
    
    this.buildFormView();
    this.buildCodeView(tabCode);
}

/**
 * Builds toolbar buttons
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.buildFormViewToolbar = function()
{
    var toolbarCon = alib.dom.createElement("div", this.formCanvas);
    
    // Toolbar for form view
    var toolbar = new CToolbar();
    
    // Delete Button
    var buttonDelete = alib.ui.Button("Delete Tab", 
                        {
                            className:"b3", callback:this,
                            onclick:function() 
                            {
                                var cls = this.callback;
                                ALib.Dlg.confirmBox("Are you sure you want to delete this tab?", "Delete Tab");
                                ALib.Dlg.onConfirmOk = function()
                                {
                                    cls.deleteTab();
                                }
                            }
                        });
    toolbar.AddItem(buttonDelete.getButton(), "right");
    
    // Rename Button
    var buttonRename = alib.ui.Button("Rename Tab", 
                        {
                            className:"b1", callback:this,
                            onclick:function() 
                            {
                                this.callback.setOptions({id:"tab"});
                            }
                        });
    toolbar.AddItem(buttonRename.getButton(), "right");
    
    // Add Button
    var buttonAdd = alib.ui.Button("Add Tab", 
                        {
                            className:"b2", callback:this,
                            onclick:function() 
                            {
                                this.callback.addTab(this.callback.canvasCon, "New Tab", true);
                            }
                        });
    toolbar.AddItem(buttonAdd.getButton(), "right");
    
    // Print Toolbar Buttons
    toolbar.print(toolbarCon);
}

/**
 * Builds the form view canvas
 *
 */
AntObjectFormEditor.prototype.buildFormView = function()
{
    this.buildFormViewToolbar();
    
    this.canvasCon = alib.dom.createElement("div", this.formCanvas);    
    alib.dom.setElementAttr(alib.dom.createElement("h2", this.canvasCon), [["innerHTML", this.mainObject.title]]);
    
    var canvasHeight = (getWorkspaceHeight() - this.toolbarCon.offsetHeight - 100) + "px";
    alib.dom.styleSet(this.canvasCon, "max-height", canvasHeight);
    alib.dom.styleSet(this.canvasCon, "overflow", "auto");
    
    if("*" == this.xmlFormLayoutText)
    {
        //if("default" == this.scope || "team" == this.scope || "user" == this.scope)
            //newTab(con);        // Add tab with dropzone for default, team, user scope
        //else
            //buildTab(con);        // Just add dropzone for mobile scope
    }
    else    // Build form based on xmlFormLayout
        this.buildEditorForm(this.canvasCon, this.xmlFormLayout);
    
    this.buildFields();
}

/**
 * Builds the display for code view
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con      Container for code view
 */
AntObjectFormEditor.prototype.buildCodeView = function(con)
{
    var mainCon = alib.dom.createElement("div", con);
    this.codeViewInput = alib.dom.createElement("textarea", mainCon);
    
    // Setup onchange event
    this.codeViewInput.cls = this;
    this.codeViewInput.onchange = function()
    {
        this.cls.codeViewChanged = true;
    }
    
    alib.dom.styleSet(this.codeViewInput, "width", "99%");    
    alib.dom.styleSet(this.codeViewInput, "height", (alib.dom.getDocumentHeight()-80) + "px");
}

/**
 * Builds the editor form with drag and drop zone
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con  A dom container where the drag and drop fields will be displayed
 * @param {xml} node   Data that contains the form structure
 */
AntObjectFormEditor.prototype.buildEditorForm = function(con, node)
{
    var numCols = 0;
    var curCol = 0;
    var curRow = null;
    
    // First find out how many columns we are working with this at this level        
    for (var i = 0; i < node.getNumChildren(); i++)
    {
        var child = node.getChildNode(i);
        if (child.m_name == "column")
            numCols++;
    }
    if (!numCols)
        numCols = 1;
    
    // Create form elements
    for (var i = 0; i < node.getNumChildren(); i++)
    {
        var child = node.getChildNode(i);
        switch (child.m_name)
        {
            case "tab":
                // Create tab
                var tabCon = this.addTab(con, child.getAttribute("name"), false);
                this.buildEditorForm(tabCon.childNodes[1], child);
                
                
                break;

            case "plugin":
                var pluginCon = alib.dom.createElement("div");
                
                if(child.getAttribute("name") != "")
                    pluginCon.name = child.getAttribute("name");
                    
                pluginCon.id = "plugin";
                pluginCon.title = "Plugin name='" + child.getAttribute("name") + "'";
                this.appendXmlContainer(pluginCon, con);
                this.buildEditorForm(pluginCon, child);
                break;

            case "recurrence":
                var recurrence = alib.dom.createElement("div");
                recurrence.id = "recurrence";
                recurrence.title = "Recurrence";
                this.appendXmlContainer(recurrence, con);
                this.buildEditorForm(recurrence, child);
                break;

            case "report":
                var report = alib.dom.createElement("div");
                if(child.getAttribute("id") != "")
                    report.reportid = child.getAttribute("id");
                if(child.getAttribute("filterby") != "")
                    report.filterby = child.getAttribute("filterby");
                report.id = "report";
                report.title = "Report";
                this.appendXmlContainer(report, con);
                this.buildEditorForm(report, child);
                break;

            case "fieldset":
                var fieldsetCon = alib.dom.createElement("div");
                
                if(child.getAttribute("name") != "")
                {
                    var fname = child.getAttribute("name");
                    fname = fname.replace("&", "&amp;");
                    fieldsetCon.name = fname;
                }
                
                if(child.getAttribute("showif") != "")
                {
                    var showif = child.getAttribute("showif");
                    fieldsetCon.showifType = showif.substring(0, showif.indexOf("="));
                    fieldsetCon.showifValue = showif.substring(showif.indexOf("=")+1, showif.length);
                }                
                else
                {
                    fieldsetCon.showifType = null;
                    fieldsetCon.showifValue = null;
                }
                
                fieldsetCon.id = "fieldset";
                fieldsetCon.title = fieldsetCon.name = child.getAttribute("name");
                this.appendXmlDropContainer(fieldsetCon, con);
                this.buildEditorForm(fieldsetCon, child);
                break;

            case "objectsref":
                var objectsref = alib.dom.createElement("div");
                
                if(child.getAttribute("obj_type") != "")
                    objectsref.objType = child.getAttribute("obj_type");
                else
                    objectsref.objType = null;
                    
                if(child.getAttribute("ref_field") != "")
                    objectsref.refField = child.getAttribute("ref_field");
                else
                    objectsref.refField = null;
                    
                objectsref.id = "objectsref";
                objectsref.title = "Objectsref";                
                this.appendXmlContainer(objectsref, con);
                this.buildEditorForm(objectsref, child);
                break;

            case "spacer":
                var spacer = alib.dom.createElement("div");
                spacer.id = "spacer";
                spacer.title = "Spacer";
                this.appendXmlContainer(spacer, con);
                this.buildEditorForm(spacer, child);
                break;

            case "row":
                var rowCon = alib.dom.createElement("div");
                if(child.getAttribute("showif") != "")
                {
                    var showif = child.getAttribute("showif");
                    rowCon.showifType = showif.substring(0, showif.indexOf("="));
                    rowCon.showifValue = showif.substring(showif.indexOf("=")+1, showif.length);
                }
                else
                {
                    rowCon.showifType = null;
                    rowCon.showifValue = null;
                }
                
                rowCon.id = "row";
                rowCon.title = "Row";
                this.appendXmlDropContainer(rowCon, con);
                this.buildEditorForm(rowCon, child);
                break;

            case "column":
                if(!curRow)
                {
                    var tbl = alib.dom.createElement("table", con);
                    alib.dom.styleSet(tbl, "table-layout", "fixed");
                    alib.dom.styleSet(tbl, "width", "100%");
                    var tbody = alib.dom.createElement("tbody", tbl);
                    curRow = alib.dom.createElement("tr", tbody);
                    tbl.numCol = numCols;
                }
                
                curCol++;
                var td = alib.dom.createElement("td", curRow);
                alib.dom.styleSet(td, "vertical-align", "top");
                
                
                var column = alib.dom.createElement("div");
                column.showifType = null;
                column.showifValue = null;
                column.styleAttr = null;
                
                var width = child.getAttribute("width");
                if(width && typeof width != "undefined")
                {
                    if(width.slice(0, -2) < 200)
                        width = "200px";
                        
                    column.width = width;
                    td.style.width = width;
                }
                
                var showif = child.getAttribute("showif");
                if(showif != "")
                {
                    column.showifType = showif.substring(0, showif.indexOf("="));
                    column.showifValue = showif.substring(showif.indexOf("=")+1, showif.length);
                }
                
                var styleAttr = child.getAttribute("style");
                if(typeof styleAttr != "undefined")
                    column.styleAttr = styleAttr;
                
                column.id = "column";
                column.title = "Column";
                this.appendXmlDropContainer(column, td);
                this.buildEditorForm(column, child);
                break;

            case "field":
                var title = this.getFieldTitle(child.getAttribute("name"));
                var fieldCon = alib.dom.createElement("div");
                fieldCon.title = title;
                fieldCon.label = title;
                fieldCon.tooltip = null;
                fieldCon.part = null;
                fieldCon.hidelabel = false;
                
                // image_id special field type
                if(child.getAttribute("name") == "image_id")
                {
                    if(child.getAttribute("profile_image") == "t")
                    {
                        fieldCon.profile_image = true;
                        fieldCon.path = child.getAttribute("path");
                    }
                    else
                    {
                        fieldCon.profile_image = false;
                        fieldCon.path = "";
                    }
                }
                
                // fields with multiline and rich
                if(child.getAttribute("multiline"))
                {
                    if(child.getAttribute("multiline") == "t")
                        fieldCon.multiline = true;
                    else
                        fieldCon.multiline = false;
                    if(child.getAttribute("rich") == "t")
                        fieldCon.rich = true;
                    else
                        fieldCon.rich = false;
                }
                
                // all fields have hidelabel
                if(child.getAttribute("hidelabel") == "t")
                    fieldCon.hidelabel = true;
                    
                // Check for tooltips
                if(child.getAttribute("tooltip"))
                    fieldCon.tooltip = child.getAttribute("tooltip");
                    
                // Attribute for date picker
                if(child.getAttribute("part"))
                    fieldCon.part = child.getAttribute("part");
                    
                // Attribute that will override the title
                if(child.getAttribute("label"))
                    fieldCon.label = child.getAttribute("label");
                    
                fieldCon.type = "field";
                fieldCon.id = child.getAttribute("name");
                this.appendXmlContainer(fieldCon, con);
                this.buildEditorForm(fieldCon, child);
                break;

            case "all_additional":
                var all_additional = alib.dom.createElement("div");
                all_additional.id = "all_additional";
                all_additional.title = "All Additional";
                this.appendXmlContainer(all_additional, con);
                this.buildEditorForm(all_additional, child);
                break;
        }
    }
} 

/**
 * Builds form tab
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con  A dom container where the drag and drop fields will be displayed
 */
AntObjectFormEditor.prototype.buildFormTab = function(con)
{
    // Create main Dropzone div
    var attrData = [["innerHTML", "<center><h2>Drag items here</h2></center>"], ["margin", "3px 0 0 0"], ["width", "100%"]];
    var divDrop = alib.dom.setElementAttr(alib.dom.createElement("div", con), attrData);
    var mainCon = alib.dom.createElement("div", con);
    divDrop.rootdz = true;
    DragAndDrop.registerDropzone(divDrop, this.dropZoneName);
    
    divDrop.cls = this;
    divDrop.onDragEnter = function(e)
    {
        alib.dom.styleSet(this, "border", "1px solid blue");
    }
    
    divDrop.onDragExit = function(e)
    {
        alib.dom.styleSet(this, "border", "");
    }
    
    divDrop.onDragDrop = function(e)
    {
        alib.dom.styleSet(this, "border", "");
        this.cls.appendDragDrop(e, mainCon);
    }
    
    divDrop.onResort = function(e)
    {
    }

    mainCon.dropZoneName = "dz" + this.dropZoneGroup;
    DragAndDrop.registerDropzone(mainCon, mainCon.dropZoneName);
    DragAndDrop.registerSortable(mainCon);
}

/**
 * Appends object in the container
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con        A dom container where the fields will be displayed
 * @param {DOMElement} parentCon  The parent container of con
 * 
 */
AntObjectFormEditor.prototype.appendXmlContainer = function(con, parentCon)
{
    // increment container Index
    this.containerIndex++;
    
    // Specify con attributes
    con.oid = this.containerIndex;
    con.pnode = parentCon;
    con.antType = "field";
    
    var attrData = [["id", "divObjContainer"], ["margin", "5px 5px 5px 5px"], ["border", "1px solid black"], ["padding", "5px"], ["cursor", "move"]];
    var mainCon = alib.dom.setElementAttr(alib.dom.createElement("div", parentCon), attrData);
    
    // If field dropped in main dropzone, parentCon = null
    if(parentCon.id == "tab")
        DragAndDrop.registerDragableChild(null, mainCon, null, parentCon.dropZoneName);
    else
        DragAndDrop.registerDragableChild(parentCon.parentNode, mainCon, null, parentCon.dropZoneName);
        
    // Append the container to div con
    if(con.label)
        con.innerHTML = con.label; 
    else
        con.innerHTML = con.title; 
        
    alib.dom.styleSet(con, "float", "left");
    mainCon.appendChild(con);
        
    // Container for options and delete item
    var objCon = alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["float", "right"]]);
    var optionsCon = alib.dom.setElementAttr(alib.dom.createElement("div", objCon), [["float", "right"]]);
    
    // all_additional and spacer will not have options
    if(con.id != "all_additional" && con.id != "recurrence" && con.id != "spacer")
    {
        var buttonOptions = alib.ui.Button("Options", 
                            {
                                className:"b1 small", callback:this, con:con,
                                onclick:function() 
                                {
                                    this.callback.setOptions(this.con);
                                }
                            }, "link");
        optionsCon.appendChild(buttonOptions.getButton());
    }
    
    // Delete Item
    var buttonDelete = alib.ui.Button("X", 
                            {
                                className:"b3 small", callback:this, mainCon:mainCon, con:con,
                                onclick:function()
                                {
                                    this.callback.deleteItem(this.mainCon, this.con);
                                }
                            }, "link");
    optionsCon.appendChild(buttonDelete.getButton());
    
    // Set clear both for mainCon and optionsCon
    alib.dom.setElementAttr(alib.dom.createElement("div", optionsCon), [["clear", "both"]]);
    alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["clear", "both"]]);
}  

/**
 * Appends the drag and drop container
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con        A dom container where the fields will be displayed
 * @param {DOMElement} parentCon  The parent container of con
 */
AntObjectFormEditor.prototype.appendXmlDropContainer = function(con, parentCon, conAttr)
{
    // increment container Index
    this.containerIndex++;

    // Specify con attributes
    con.oid = this.containerIndex;
    con.pnode = parentCon;
    con.antType = "con";
    
    var attrData = [["id", "divDropContainer"], ["margin", "5px 5px 5px 5px"], ["border", "1px solid black"], ["padding", "5px"], ["cursor", "move"]];
    var mainCon = alib.dom.setElementAttr(alib.dom.createElement("div", parentCon), attrData);
    
    if(conAttr)
    {
        for(attr in conAttr)
            alib.dom.styleSet(mainCon, attr, conAttr[attr]);
    }
        
    // Container for columns dropdown, options, and delete item
    var objCon = alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["float", "right"]]);
    var optionsCon = alib.dom.setElementAttr(alib.dom.createElement("div", objCon), [["float", "right"]]);
    
    // Columns Dropdown
    var dm = new CDropdownMenu();
    var dmSub1 = dm.addEntry("1", function(cls) { cls.addColumn(con, 1); }, null, null, [this]);
    var dmSub2 = dm.addEntry("2", function(cls) { cls.addColumn(con, 2); }, null, null, [this]);
    var dmSub3 = dm.addEntry("3", function(cls) { cls.addColumn(con, 3); }, null, null, [this]);
    var dmSub4 = dm.addEntry("4", function(cls) { cls.addColumn(con, 4); }, null, null, [this]);
    optionsCon.appendChild(dm.createLinkMenu("Columns"));
    
    // Options Button
    var buttonOptions = alib.ui.Button("Options", 
                        {
                            className:"b1 small", callback:this, con:con,
                            onclick:function() 
                            {
                                this.callback.setOptions(this.con);
                            }
                        }, "link");
    
    var btn = buttonOptions.getButton();    
    alib.dom.styleSet(btn, "margin-left", "3px");
    optionsCon.appendChild(btn);

    // Delete Button
    var buttonDelete = alib.ui.Button("X", 
                        {
                            className:"b3 small", callback:this, mainCon:mainCon, con:con,
                            onclick:function()
                            {
                                this.callback.deleteItem(this.mainCon, this.con);
                            }
                        }, "link");
    optionsCon.appendChild(buttonDelete.getButton());
    
    // Name Container
    var nameCon = alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["innerHTML", con.title], ["margin", "0 145px 0 0"]]);
    
    // Separator
    var separatorDiv = alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["height", "5px"]]);
    
    // Setup Drag and Drop
    if(con.id == "column")
        con.dropZoneName = "col" + this.dropZoneGroup;
    else
    {
        this.dropZoneGroup++;
        con.dropZoneName = "dz" + this.dropZoneGroup;
    }
    DragAndDrop.registerDropzone(con, con.dropZoneName);
    DragAndDrop.registerSortable(con);
    
    // If container dropped in main dropzone, parentCon = null
    if(parentCon.id == "tab")
        DragAndDrop.registerDragableChild(null, mainCon, null, parentCon.dropZoneName);    
    else
    {
        if(con.id == "column")
        {
            if(conAttr)
                DragAndDrop.registerDragableChild(parentCon.parentNode, mainCon, null, parentCon.parentNode.dropZoneName);
            else
                DragAndDrop.registerDragableChild(parentCon.parentNode.parentNode.parentNode.parentNode.parentNode, mainCon, null, parentCon.parentNode.dropZoneName);
        }
        else
            DragAndDrop.registerDragableChild(parentCon.parentNode, mainCon, null, parentCon.dropZoneName);
    }
    
    // Dropzone
    var attrData = [["innerHTML", "<center><strong>Drag items here</strong></center>"], ["margin", "5px"], ["padding", "5px"], ["height", "15px"]];
    var dragDropCon = alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), attrData);
    dragDropCon.cls = this;
    
    DragAndDrop.registerDropzone(dragDropCon, this.dropZoneName);
    dragDropCon.onDragEnter = function(e)
    {
        alib.dom.styleSet(this, "border", "1px solid blue");
    }
    
    dragDropCon.onDragExit = function(e)
    {
        alib.dom.styleSet(this, "border", "");
    }
    
    dragDropCon.onDragDrop = function(e)
    {
        alib.dom.styleSet(this, "border", "");
        this.cls.appendDragDrop(e, con);
    }
    
    dragDropCon.onResort = function(e)
    {
    }
    
    // Set clear both for mainCon and optionsCon
    alib.dom.setElementAttr(alib.dom.createElement("div", objCon), [["clear", "both"]]);
    alib.dom.setElementAttr(alib.dom.createElement("div", mainCon), [["clear", "both"]]);
    
    mainCon.appendChild(con);
} 

/**
 * Appends the drag and drop object
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con        A dom container where the container will be displayed
 * @param {DOMElement} parentCon  The parent container of con
 */
AntObjectFormEditor.prototype.appendDragDrop = function(con, parentCon)
{
    var newCon = alib.dom.createElement("div");
    newCon.id = con.id;
    newCon.title = con.title;
    
    switch(con.id)
    {
        case "row":
        case "fieldset":
            newCon.showifType = null;
            newCon.showifValue = null;
            this.appendXmlDropContainer(newCon, parentCon);        
            break;
            
        default:
            this.appendXmlContainer(newCon, parentCon);
            break;
    }
}

/**
 * Builds Fields List
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.buildFields = function()
{
    // Fields
    alib.dom.setElementAttr(alib.dom.createElement("label", this.formFields), [["innerHTML", "Available Fields"], ["font-weight", "bold"]]);
    
    var attrData = [["border", "1px solid"], ["height", "300px"], ["overflow", "auto"], ["margin-bottom", "15px"]];
    var fieldsCon = alib.dom.setElementAttr(alib.dom.createElement("div", this.formFields), attrData);    
    
    // Create Drag and Drop fields
    var fieldsDropCon = alib.dom.setElementAttr(alib.dom.createElement("div", fieldsCon), [["width", "99%"], ["height", "99%"]]);
    this.createDragDrop(fieldsDropCon, "all_additional", "All Additional");
    this.createDragDrop(fieldsDropCon, "objectsref", "All Objectsref");
    this.createDragDrop(fieldsDropCon, "plugin", "Plugin");
    this.createDragDrop(fieldsDropCon, "recurrence", "Recurrence");
    this.createDragDrop(fieldsDropCon, "report", "Report");
    this.createDragDrop(fieldsDropCon, "spacer", "Spacer");
    
    // Loop thru available object fields
    for(field in this.mainObject.fields)
    {
        var currentField = this.mainObject.fields[field];
        
        var fieldDragDiv = this.createDragDrop(fieldsDropCon, currentField.name, currentField.title);
        fieldDragDiv.type = currentField.type;
        fieldDragDiv.subtype = currentField.subtype;
    }
    
    // Containers
    alib.dom.setElementAttr(alib.dom.createElement("label", this.formFields), [["innerHTML", "Containers"], ["font-weight", "bold"]]);
    
    var attrData = [["border", "1px solid"], ["overflow", "auto"]];
    var containersCon = alib.dom.setElementAttr(alib.dom.createElement("div", this.formFields), attrData);
    
    // Create Drag and Drop containers
    var containersDropCon = alib.dom.setElementAttr(alib.dom.createElement("div", fieldsCon), [["width", "99%"], ["height", "99%"]]);
    this.createDragDrop(containersCon, "row", "Row");
    this.createDragDrop(containersCon, "row", "Fieldset");
}

/**
 * Builds Fields List
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con  A dom container where the drag and drop fields will be displayed
 * @param {Integer} id      Id of the field
 * @param {String} title    Title of the field
 */
AntObjectFormEditor.prototype.createDragDrop = function(con, id, title)
{
    var attrData = [["innerHTML", title], ["border", "1px solid"], ["margin", "3px"], ["padding", "3px"], ["cursor", "move"]];
    var fieldCon = alib.dom.setElementAttr(alib.dom.createElement("div", con), attrData);
    fieldCon.id = id;
    fieldCon.title = title;
    DragAndDrop.registerDragable(fieldCon, null, this.dropZoneName);
    
    return fieldCon;
}

/**
 * Saves the object form
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {Boolean} close       Determines whether to close the window or not
 */
AntObjectFormEditor.prototype.saveObjectForm = function(close)
{
    if(this.tabsEditor.curr_index==0) // No need to regenerate code if already in code view
        this.setupCodeView(); // Regenerate the xml for possible changes in editor view    
        
    if(!this.testXmlString()) // Test the xml for errors
        return;
    
    ajax = new CAjax('json');
    ajax.cbData.dlg = showDialog("Saving, please wait...");
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(ret)
        {
            this.cbData.dlg.hide();
            
            if(close)
                window.close();
            else
                this.cbData.cls.checkDefaultForm();
        }
        else
        {
            this.cbData.dlg.hide();
            ALib.statusShowAlert("Error occurred while saving changes.!", 3000, "bottom", "right");
        }
    };
    
    var args = [["obj_type", this.mainObject.obj_type], ["form_layout_xml", this.codeViewInput.value], ["default", this.scope], ["team_id", this.teamId], ["user_id", this.userId]];
    //args[args.length] = ["mobile", this.scopeType];
    ajax.exec("/controller/Object/saveForm", args);
}

/**
 * Check for default form
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.checkDefaultForm = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret['error'])
            ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
        else
        {
            for(form in ret)
            {
                var currentForm = ret[form];
                
                // Check if default form is overridden
                if(currentForm.scope == "default" && this.cbData.cls.scope == "default")
                    this.cbData.cls.scopeType = "default";                
                else if(currentForm.scope == "mobile" && this.cbData.cls.scope == "mobile")
                    this.cbData.cls.scopeType = "mobile";
            }
            
            // Get the object form
            this.cbData.cls.getXmlForm();
        }
    };
    
    var args = [["obj_type", this.mainObject.obj_type]];
    ajax.exec("/controller/Object/getForms", args);
}

/**
 * Get the object form
 * 
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.getXmlForm = function()
{
    var ajax = new CAjax();
    ajax.cbData.cls = this;
    ajax.onload = function(root)
    {
        this.cbData.cls.xmlFormLayout = root.getChildNodeByName("form");
        this.cbData.cls.xmlFormLayoutText = unescape(root.getChildNodeValByName("form_layout_text"));
        this.cbData.cls.buildInterface();
    };
    
    var url = "/controller/Object/loadForm?obj_type=" + this.mainObject.obj_type;
    
    if(this.scopeType)
        url += "&" + this.scopeType + "=1";
    if(this.teamId)
        url += "&team_id=" + this.teamId;
    if(this.userId)
        url += "&user_id=" + this.teamId;
    
    ajax.exec(url);
}

/**
 * Get the object form
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {String} fieldName        A field name of main object
 */
AntObjectFormEditor.prototype.getFieldTitle = function(fieldName)
{
    var field = this.mainObject.getFieldByName(fieldName);
    return field.title;
}

/**
 * Adds column to fieldset
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con      A dom container where the columns will be printed
 * @param {Integer} num         Number of columns
 */
AntObjectFormEditor.prototype.addColumn = function(con, num)
{
    var conAttr = new Array;
    conAttr["width"] = (100/num) - 5 + "%";
    conAttr["float"] = "left";
        
    for(var i = 0; i < num; i++)
    {
        var columnCon = alib.dom.createElement("div");
        
        columnCon.id = "column";
        columnCon.title = "Column";
        columnCon.showifType = "";
        columnCon.showifValue = "";
        this.appendXmlDropContainer(columnCon, con, conAttr);
    }
    
    alib.dom.setElementAttr(alib.dom.createElement("div", con), [["clear", "both"]]);
    
    // Restore all parent containers to dragable
    var parentCon = con.parentNode.parent;
    while(parentCon)
    {
        parentCon.dragable = true;
        parentCon = parentCon.parentNode;
    }
}

/**
 * Display the options availble
 * 
 * @public
 * @this {AntObjectFormEditor} * 
 * @param {DOMElement} con      Main container of the object to be deleted
 */
AntObjectFormEditor.prototype.setOptions = function(con)
{
    // Restore all parent containers to dragable
    if(con.id != "tab")
    {
        var parentCon = con.parentNode.parent;
        while(parentCon)
        {
            parentCon.dragable = true;
            parentCon = parentCon.parentNode;
        }
    }
    
    var dlg = new CDialog();
    var dlgCon = alib.dom.createElement("div");
    var dlgHeight = 90;
    var dlgWidth = 260;
    
    dlg.m_title = con.id.capitalize() + " Options";
    
    // Button Ok Options
    var buttonOptions = {className:"b2", dlg:dlg, con:con, formViewTabs:this.formViewTabs,
                                onclick:function() 
                                {
                                    // Tab Set Option
                                    if(this.tabName)
                                    {
                                        this.formViewTabs.setTabTitle(this.tabIdx, this.tabName.value);
                                        this.formViewTabs.getPageCon(this.tabIdx).name = this.tabName.value;
                                    }
                                    
                                    // Fieldset, Column, Row Set Options
                                    if(this.name && this.name.value != "")
                                    {
                                        this.con.name = this.name.value;
                                        
                                        if(this.con.id == "plugin")
                                            this.con.parentNode.childNodes[0].innerHTML = "Plugin name='" + this.name.value + "'";
                                        else
                                            this.con.parentNode.childNodes[1].innerHTML = this.name.value;
                                    }
                                    
                                    if(this.styleAttr && this.styleAttr.value != "")
                                    {
                                        this.con.styleAttr = this.styleAttr.value;
                                    }
                                    
                                    if(this.width && this.width.value != "")
                                    {
                                        if(this.con.parentNode.parentNode.parentNode.parentNode.parentNode.numCol != 1)
                                        {
                                            this.con.parentNode.parentNode.style.width = this.width.value;
                                            this.con.width = this.width.value;
                                                            
                                            for(var i = 0; i < this.con.parentNode.parentNode.parentNode.childNodes.length; i++)
                                            {
                                                // Set the width field of the columns that were scaled
                                                if(this.con.parentNode.parentNode.parentNode.childNodes[i].width != this.width.value)
                                                {
                                                    this.con.parentNode.parentNode.parentNode.childNodes[i].childNodes[0].childNodes[4].width = alib.dom.styleGet(this.con.parentNode.parentNode.parentNode.childNodes[i], "width");
                                                }
                                            }
                                        }
                                    }
                                    
                                    if(this.showifType && this.showifType.value != "" && this.showifValue.value != "")
                                    {
                                        this.con.showifType = this.showifType.value;
                                        this.con.showifValue = this.showifValue.value;
                                    }
                                    
                                    // ObjectsRef Set Options
                                    if(this.objType)
                                        this.con.objType = this.objType.value
                                        
                                    if(this.refField)
                                        this.con.refField = this.refField.value
                                    
                                    // Default Set Options
                                    if(this.hideLabel)
                                        this.con.hidelabel = this.hideLabel.checked;
                                        
                                    if(this.tooltip)
                                        this.con.tooltip = this.tooltip.value;
                                    
                                    if(this.multiline)
                                        this.con.multiline = this.multiline.checked;
                                        
                                    if(this.rich)
                                        this.con.rich = this.rich.checked;
                                        
                                    if(this.part)
                                        this.con.part = this.part.value;
                                        
                                    if(this.label)
                                    {
                                        this.con.label = this.label.value;
                                        this.con.parentNode.childNodes[0].innerHTML = this.label.value;
                                    }
                                        
                                    this.dlg.hide();
                                }};
    
    switch(con.id)
    {
        case "plugin":
        case "fieldset":
            // Name Input
            var nameCon = alib.dom.createElement("div", dlgCon);
            var nameLabel = alib.dom.setElementAttr(alib.dom.createElement("label", nameCon), [["innerHTML", "Name:"]]);
            var nameInput = alib.dom.setElementAttr(alib.dom.createElement("input", nameCon), [["value", con.name], ["width", "190px"]]);
            
            alib.dom.styleSet(nameInput, "margin-left", "15px");
            buttonOptions.name = nameInput;
            break;
        case "column":
            dlgHeight = 110;
            // Width Input
            var widthCon = alib.dom.createElement("div", dlgCon);
            var widthLabel = alib.dom.setElementAttr(alib.dom.createElement("label", widthCon), [["innerHTML", "Width:"]]);
            var widthInput = alib.dom.setElementAttr(alib.dom.createElement("input", widthCon), [["value", alib.dom.styleGet(con.parentNode.parentNode, "width")], ["width", "85px"]]);
            
            // Style Input
            var styleCon = alib.dom.createElement("div", dlgCon);
            var styleLabel = alib.dom.setElementAttr(alib.dom.createElement("label", styleCon), [["innerHTML", "Style:"]]);
            var styleInput = alib.dom.setElementAttr(alib.dom.createElement("input", styleCon), [["value", con.styleAttr], ["width", "190px"]]);
            
            alib.dom.styleSet(widthInput, "margin-left", "14px");
            alib.dom.styleSet(styleInput, "margin", "5px 0 0 18px");
            
            buttonOptions.width = widthInput;
            buttonOptions.styleAttr = styleInput;
            break;
    }
    
    switch(con.id)
    {
        case "tab":
            var tabIdx = this.formViewTabs.getIndex();
            var nameCon = alib.dom.createElement("div", dlgCon);
            var nameLabel = alib.dom.setElementAttr(alib.dom.createElement("label", nameCon), [["innerHTML", "Name:"]]);
            var nameInput = alib.dom.setElementAttr(alib.dom.createElement("input", nameCon), [["value", this.formViewTabs.getPageCon(tabIdx).name], ["width", "200px"]]);
            
            // Set button options
            buttonOptions.tabName = nameInput;
            buttonOptions.tabIdx = tabIdx;
            
            alib.dom.styleSet(nameInput, "margin-left", "5px");
            dlgHeight = 60;
            break;
            
        case "fieldset":
        case "column":
        case "row":
            // Showif Input
            var showifCon = alib.dom.createElement("div", dlgCon);
            var showifLabel = alib.dom.setElementAttr(alib.dom.createElement("label", showifCon), [["innerHTML", "Show If:"]]);
            var showifType = alib.dom.setElementAttr(alib.dom.createElement("input", showifCon), [["value", con.showifType], ["width", "85px"]]);
            alib.dom.setElementAttr(alib.dom.createElement("label", showifCon), [["innerHTML", " = "]]);
            var showifValue = alib.dom.setElementAttr(alib.dom.createElement("input", showifCon), [["value", con.showifValue], ["width", "85px"]]);
            
            // Setup button options                    
            buttonOptions.showifType = showifType;
            buttonOptions.showifValue = showifValue;
                
            // Set Style
            alib.dom.styleSet(showifCon, "margin-top", "5px");
            alib.dom.styleSet(showifType, "margin-left", "5px");
            break;
        
        case "objectsref":
            // Object Type
            var typeCon = alib.dom.createElement("div", dlgCon);
            var typeLabel = alib.dom.setElementAttr(alib.dom.createElement("label", typeCon), [["innerHTML", "Object Type:"]]);
            var typeInput = alib.dom.setElementAttr(alib.dom.createElement("input", typeCon), [["value", con.objType], ["width", "165px"]]);
            
            // Reference Field
            var refCon = alib.dom.createElement("div", dlgCon);
            var refLabel = alib.dom.setElementAttr(alib.dom.createElement("label", refCon), [["innerHTML", "Ref Field:"]]);
            var refInput = alib.dom.setElementAttr(alib.dom.createElement("input", refCon), [["value", con.refField], ["width", "166px"]]);
            
            alib.dom.styleSet(typeInput, "margin-left", "14px");
            alib.dom.styleSet(refInput, "margin-left", "30px");
            alib.dom.styleSet(refInput, "margin-top", "5px");
            
            // Setup button options
            buttonOptions.objType = typeInput;
            buttonOptions.refField = refInput;
            break;
        
        case "plugin":
        case "all_additional":
        case "recurrence":
        case "spacer":
            break;
        
        default:
            dlgHeight += 30;
            dlg.m_title = con.title + " Options"
            
            // Label for overriding title value
            var labelCon = alib.dom.createElement("div", dlgCon);
            var labelLabel = alib.dom.setElementAttr(alib.dom.createElement("label", labelCon), [["innerHTML", "Label: "]]);
            var labelInput = alib.dom.setElementAttr(alib.dom.createElement("input", labelCon), [["value", con.label], ["width", "190px"]]);
            
            alib.dom.styleSet(labelInput, "margin-left", "7px");
            alib.dom.styleSet(labelInput, "margin-bottom", "5px");
            
            // Tooltip
            var tooltipCon = alib.dom.createElement("div", dlgCon);
            var tooltipLabel = alib.dom.setElementAttr(alib.dom.createElement("label", tooltipCon), [["innerHTML", "Tooltip: "]]);
            var tooltipInput = alib.dom.setElementAttr(alib.dom.createElement("input", tooltipCon), [["value", con.tooltip], ["width", "190px"]]);
            alib.dom.styleSet(tooltipCon, "margin-bottom", "5px");
            
            // part for datepicker
            if(con.part)
            {
                dlgHeight += 20;
                
                var partCon = alib.dom.createElement("div", dlgCon);
                var partLabel = alib.dom.setElementAttr(alib.dom.createElement("label", partCon), [["innerHTML", "Part: "]]);
                var partInput = alib.dom.setElementAttr(alib.dom.createElement("input", partCon), [["value", con.part], ["width", "190px"]]);
                
                alib.dom.styleSet(partInput, "margin-left", "12px");
                alib.dom.styleSet(partCon, "margin-bottom", "5px");
                
                buttonOptions.part = partInput;
            }
            
            // Multiline Textbox
            if(con.multiline != null && con.rich != null)
            {
                dlgHeight += 30;
                
                var multilineCon = alib.dom.createElement("div", dlgCon);
                var multilineCheck = alib.dom.setElementAttr(alib.dom.createElement("input", multilineCon), [["type", "checkbox"], ["checked", con.multiline]]);
                var multilineLabel = alib.dom.setElementAttr(alib.dom.createElement("label", multilineCon), [["innerHTML", " Multiline"]]);
                
                var richCon = alib.dom.createElement("div", dlgCon);
                var richCheck = alib.dom.setElementAttr(alib.dom.createElement("input", richCon), [["type", "checkbox"], ["checked", con.rich]]);
                var richLabel = alib.dom.setElementAttr(alib.dom.createElement("label", richCon), [["innerHTML", " Rich"]]);
                
                buttonOptions.multiline = multilineCheck;
                buttonOptions.rich = richCheck;
            }
            
            // Hide Label
            var hideCon = alib.dom.createElement("div", dlgCon);
            var hideCheck = alib.dom.setElementAttr(alib.dom.createElement("input", hideCon), [["type", "checkbox"], ["checked", con.hidelabel]]);
            var hideLabel = alib.dom.setElementAttr(alib.dom.createElement("label", hideCon), [["innerHTML", " Hide Label"]]);

            // setup button options
            buttonOptions.label = labelInput;
            buttonOptions.tooltip = tooltipInput;
            buttonOptions.hideLabel = hideCheck;
            break;
    }
    
    // Buttons
    var buttonCon = alib.dom.createElement("div", dlgCon);
    var btn = alib.ui.Button("Ok", buttonOptions);
    buttonCon.appendChild(btn.getButton());
    
    var btn = alib.ui.Button("Cancel", 
                        {
                            className:"b1", dlg:dlg,
                            onclick:function() 
                            {
                                this.dlg.hide();
                            }
                        });
    buttonCon.appendChild(btn.getButton());
    
    alib.dom.styleSet(buttonCon, "float", "right");
    alib.dom.styleSet(buttonCon, "margin-top", "5px");
    alib.dom.divClear(dlgCon);
    
    // Show Dialog
    dlg.customDialog(dlgCon, dlgWidth, dlgHeight);
}

/**
 * Deletes the item selected
 * 
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} objCon   Container of the object to be deleted
 * @param {DOMElement} con      Main container of the object to be deleted
 */
AntObjectFormEditor.prototype.deleteItem = function(objCon, con)
{
    // Remove column from table
    if(con.id == "column")
    {
        con.parentNode.conState = false;    
        alib.dom.styleSet(con.parentNode, "display", "none");
    }
    // Remove item
    else
    {
        con.conState = false;
        alib.dom.styleSet(objCon, "display", "none");
    }
    
    // Restore all parent containers to dragable
    var parentCon = objCon.parent;
    while(parentCon)
    {
        parentCon.dragable = true;
        parentCon = parentCon.parentNode;
    }
}

/**
 * Adds a new tab
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con      Main container
 * @param {String} tabNam       Name of the tab
 * @param {Boolean} isNew       Determine if tab is created from button "Add Tab"
 */
AntObjectFormEditor.prototype.addTab = function(con, tabName, isNew)
{
    // Add tab to available tabs
    this.availableTabs[this.availableTabs.length] = {available:true};
    this.containerIndex++;
    this.numTabs++;
    
    // Get tab name
    tabName = tabName.replace("&", "&amp;");
    
    // Create tab
    var tabCon = this.formViewTabs.addTab(tabName);
    tabCon.oid = this.containerIndex;
    tabCon.id = "tab";
    tabCon.antType = "con";
    tabCon.name = tabName;
    
    // Select the new tab
    if(isNew)
        this.formViewTabs.selectTab(this.numTabs-1);
    else
        this.formViewTabs.print(con);
    
    this.buildFormTab(tabCon);
    return tabCon;
}

/**
 * Deletes a tab
 *
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.deleteTab = function()
{
    var tabIdx = this.formViewTabs.getIndex();
    
    this.availableTabs[tabIdx].available = false;
    this.formViewTabs.getPageCon(tabIdx).conState = false;
    this.formViewTabs.deleteTab(tabIdx);

    // Create a new tab if last tab left was deleted
    if(this.formViewTabs.getNumTabs() == 0)
        this.addTab(this.canvasCon, "New Tab", true);
    else
    {
        for(tab in this.availableTabs)
        {
            var currentTab = this.availableTabs[tab];
            
            if(currentTab.available == true)
            {
                this.formViewTabs.selectTab(tab);
                break;
            }
        }
    }
}

/**
 * Builds the display for code view
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {xml} childNodes   Data that contains the form structure
 * @param {Integer} num     number of spaces
 */
AntObjectFormEditor.prototype.generateUIML = function(childNodes, space)
{
    var tabSpaceStr = tabSpace(space); // Creats a tabbed spaces string
        
        // Loop through array and generate UIML code
    if(childNodes.children && childNodes.children.length)
    {
        for(node in childNodes.children)
        {
            var currentNode = childNodes.children[node];
            
            switch(currentNode.type)
            {
                case "tab":
                    var name = currentNode.name.replace("&", "&amp;");
                    
                    this.xmlFormLayoutText += "<tab name='" + name + "'>\n";
                    if(currentNode.children != null)
                        this.generateUIML(currentNode, space + 1);
                        
                    this.xmlFormLayoutText += "</tab>\n";
                    break;
                    
                case "column":
                    this.xmlFormLayoutText += tabSpaceStr + "<column";
                    
                    if(currentNode.width != null)
                        this.xmlFormLayoutText += " width='" + currentNode.width + "'";    
                    
                    if(currentNode.styleAttr != null)
                        this.xmlFormLayoutText += " style='" + currentNode.styleAttr + "'";    
                    
                    if(currentNode.showif != null)
                        this.xmlFormLayoutText += " showif='" + currentNode.showif + "'";
                    
                    this.xmlFormLayoutText += ">\n";
                    if(currentNode.children != null)
                        this.generateUIML(currentNode, space+1);
                        
                    this.xmlFormLayoutText += tabSpaceStr + "</column>\n";
                    break;
                    
                case "row":
                    if(currentNode.showif != null)
                        this.xmlFormLayoutText += tabSpaceStr + "<row showif='" + currentNode.showif + "'>\n";
                    else
                        this.xmlFormLayoutText += tabSpaceStr + "<row>\n";
                    
                    if(currentNode.children != null)
                            this.generateUIML(currentNode, space+1);
                    
                    this.xmlFormLayoutText += tabSpaceStr + "</row>\n";
                    break;
                    
                case "fieldset":
                    var name = currentNode.name.replace("&", "&amp;");
                    
                    if(currentNode.showif != null)
                        this.xmlFormLayoutText += tabSpaceStr + "<fieldset name='" + name + "' showif='" + currentNode.showif + "'>\n";
                    else
                        this.xmlFormLayoutText += tabSpaceStr + "<fieldset name='" + name + "'>\n";
                    
                    if(currentNode.children != null)
                            this.generateUIML(currentNode, space+1);
                    
                    this.xmlFormLayoutText += tabSpaceStr + "</fieldset>\n";            
                    break;
                    
                case "objectsref":
                    this.xmlFormLayoutText += tabSpaceStr + "<objectsref obj_type='" + currentNode.objType + "'";
                    
                    if(currentNode.refField != null)
                        this.xmlFormLayoutText += " ref_field='" + currentNode.refField +"'";
                    
                    this.xmlFormLayoutText += "></objectsref>\n";
                    break;
                
                case "report":
                    this.xmlFormLayoutText += tabSpaceStr + "<report id='" + currentNode.reportid + "' filterby='" + currentNode.filterby + "'></report>\n";
                    break;
                    
                case "all_additional":
                case "recurrence":
                case "spacer":
                    this.xmlFormLayoutText += tabSpaceStr + "<" + currentNode.type + "></" + currentNode.type + ">\n";
                    break;
                    
                case "plugin":
                    this.xmlFormLayoutText += tabSpaceStr + "<plugin name='" + currentNode.name + "'></plugin>\n";
                    break;
                
                default:
                    this.xmlFormLayoutText += tabSpaceStr + "<field name='" + currentNode.type + "' ";
                    
                    if(typeof currentNode.multiline !== "undefined")
                    {
                        if(currentNode.multiline == true)
                            this.xmlFormLayoutText += "multiline='t' ";
                        else
                            this.xmlFormLayoutText += "multiline='f' ";
                    }
                    
                    if(typeof currentNode.rich !== "undefined")
                    {
                        if(currentNode.rich == true)
                            this.xmlFormLayoutText += "rich='t' ";
                        else
                            this.xmlFormLayoutText += "rich='f' ";
                    }                
                    
                    if(currentNode.tooltip)
                        this.xmlFormLayoutText += "tooltip='" + currentNode.tooltip + "' ";
                        
                    if(currentNode.part)
                        this.xmlFormLayoutText += "part='" + currentNode.part + "' ";
                        
                    if(currentNode.label && currentNode.label != currentNode.title)
                        this.xmlFormLayoutText += "label='" + currentNode.label + "' ";
                    
                    if(currentNode.hidelabel)
                        this.xmlFormLayoutText += "hidelabel='t'";
                    else
                        this.xmlFormLayoutText += "hidelabel='f'";
                        
                    // For image field
                    if(currentNode.profileimage)
                        this.xmlFormLayoutText += " profile_image='t' path='" + currentNode.path;
                        
                    this.xmlFormLayoutText += "></field>\n";
                    break;
            }
        }
    }
}

/**
 * Maps the HTML Structure of the code editor
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con      Current container on where to look the fields/structures
 */
AntObjectFormEditor.prototype.mapNodes = function(con)
{
    for(node in con.childNodes)
    {
        var currentNode = con.childNodes[node];
        
        // If no antType but children, get children
        if(currentNode.antType == null && currentNode.childNodes != null)
            this.mapNodes(currentNode);
        else
        {
            // Only check containers with antType
            switch(currentNode.antType)
            {
                case "con":
                    // Check if container was deleted
                    if(currentNode.conState == false)
                        break;
                        
                    this.buildFormArray(currentNode);
                    this.mapNodes(currentNode);
                    break;
                    
                case "field":
                    // Check if field was deleted
                    if(currentNode.conState == false)
                        break;
                        
                    this.buildFormArray(currentNode);
                    break;
                default:
            }
        }
    }
}

/**
 * Builds the form view to code view in array format
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {DOMElement} con      Current container on where to look the fields/structures
 */
AntObjectFormEditor.prototype.buildFormArray = function(con)
{
    // Create default object values for current UIML index
    var nodeObject = {conId:con.oid, type:con.id, children:[]};
    
    if(con.name)
        nodeObject.name = con.name;
    
    // Tabs are children of root
    if(con.id == "tab")
        this.uiml.children[this.uiml.children.length] = nodeObject;
    else
    {
        // Columns have different parents than other containers
        if(con.id == "column")
        {
            if(con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid)
                this.mapChildNodes(this.uiml, con, con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid);
            else
                this.mapChildNodes(this.uiml, con, con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid);
        }
        else
        {
            // If child of dropzone container, else child of tab
            if(con.pnode.oid)
                this.mapChildNodes(this.uiml, con, con.parentNode.parentNode.oid);
            else
            {
                // If child of tab, else root element (outside of tabs)
                if(con.parentNode.parentNode.parentNode.oid)
                    this.mapChildNodes(this.uiml, con, con.parentNode.parentNode.parentNode.oid);
                else
                {
                    switch(con.id)
                    {
                        case "plugin":
                            nodeObject.children = null;
                            break;
                            
                        case "column":
                            nodeObject.width = con.width;
                            nodeObject.styleAttr = con.styleAttr;
                        default:
                            nodeObject.showif = null;                            
                            if(con.showifType != null && con.showifValue != null)
                                nodeObject.showif = con.showifType + "=" + con.showifValue;
                                
                            if(con.part)
                                nodeObject.part = con.part;
                            break;
                    }
                    
                    this.uiml.children[this.uiml.children.length] = nodeObject;
                }
            }
        }
    }
}

/**
 * Maps the child structures/fields
 *
 * @public
 * @this {AntObjectFormEditor}
 * @param {Array} childNodes    Current instance of the parent structure
 * @param {DOMElement} con      Current container on where to look the fields/structures
 * @param {Integer} id          Field/Structure Id
 */
AntObjectFormEditor.prototype.mapChildNodes = function(childNodes, con, id)
{
    if(childNodes.children && childNodes.children.length)
    {
        for(node in childNodes.children)
        {
            var currentNode = childNodes.children[node];
            // Check if this node is the parent
            if(currentNode.conId == id)
            {
                // Create default object values for current UIML index
                var childObject = {conId:con.oid, type:con.id, children:[]};
                currentNode.children[currentNode.children.length] = childObject;
                
                if(con.name)
                    childObject.name = con.name;
                
                // Attach container
                if(con.antType == "con")
                {
                    switch(con.id)
                    {
                        case "column":
                            childObject.width = con.width;
                            childObject.styleAttr = con.styleAttr;
                        default:
                            childObject.showif = null;
                            if(con.showifType != null && con.showifValue != null)
                                childObject.showif = con.showifType + "=" + con.showifValue
                            
                            break;
                    }
                }
                // Attach field
                else
                {
                    childObject.children = null; // set all attached field children as null;
                    switch(con.id)
                    {
                        case "all_additional":
                        case "recurrence":
                        case "spacer":
                        case "plugin":
                            break;
                            
                        case "objectsref":
                            if(con.refField != null)
                                childObject.refField = con.refField;
                            break;
                        
                        case "report":
                            childObject.reportid = con.reportid;
                            childObject.filterby = con.filterby;
                            break;
                            
                        case "image_id":
                            childObject.profileimage = con.profile_image;
                            childObject.path = con.path;
                            childObject.hidelabel = con.hidelabel;
                            break;
                        
                        default:
                            childObject.label = con.label;
                            childObject.title = con.title;
                            childObject.tooltip = con.tooltip;
                            childObject.hidelabel = con.hidelabel;
                            
                            if(con.multiline != null && con.rich != null)
                            {
                                childObject.multiline = con.multiline;
                                childObject.rich = con.rich;
                            }
                            
                            if(con.part)
                                childObject.part = con.part;
                            break;
                    }
                }
            }
            else
            {
                // Keep searching for parent node
                if(currentNode.children != null)
                {
                    this.mapChildNodes(currentNode, con, id);
                }
            }
        }
    }
}

/**
 * Sets up the code view
 *
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.setupCodeView = function()
{
    this.xmlFormLayoutText = "";
    this.uiml = {'type':'root', children:[]};
    this.mapNodes(this.canvasCon);
    this.generateUIML(this.uiml, 0);
    
    this.codeViewInput.value = this.xmlFormLayoutText;
}

/**
 * Sets up the code view
 *
 * @public
 * @this {AntObjectFormEditor}
 */
AntObjectFormEditor.prototype.testXmlString = function()
{
    if (this.codeViewInput.value == "")
            return true;
            
    var xmlString = "<doc>" + this.codeViewInput.value + "</doc>";

    try
    {
        if (window.DOMParser)
        {
            var parser = new DOMParser();
            var xmlDoc = parser.parseFromString(xmlString, "text/xml");
            
        }
        else // Internet Explorer
        {
            var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
            xmlDoc.async = "false";
            xmlDoc.loadXML(xmlString);
        } 

        var errorMsg = null;
        if (xmlDoc.parseError && xmlDoc.parseError.errorCode != 0) 
        {
            errorMsg = xmlDoc.parseError.reason
                      + " at line " + xmlDoc.parseError.line
                      + " at position " + xmlDoc.parseError.linepos;
        }
        else 
        {
            if (xmlDoc.documentElement) 
            {
                if (xmlDoc.documentElement.nodeName == "parsererror") 
                    errorMsg = xmlDoc.documentElement.childNodes[0].nodeValue;
            }
            else 
                errorMsg = "XML Parsing Error!";
        }

        if (errorMsg) 
            throw errorMsg;
        else
            return true;
    }
    catch (e)
    {
        alert("Error detected in XML. Please correct before saving: " + e);
        return false;
    }
}
