/**
* @fileoverview This sub-loader will load Create Calendar
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of AntObjectLoader_Calendar.
*
* @constructor
* @param {CAntObject} obj Handle to object that is being viewed or edited
* @param {AntObjectLoader} loader Handle to base loader class
*/
function AntObjectLoader_Calendar(obj, loader)
{
    this.mainObject = obj;
    this.dashboardId = this.mainObject.id;
    this.loaderCls = loader;
    
    this.outerCon = null; // Outer container
    this.mainConMin = null; // Minified div for collapsed view
    this.mainCon = null; // Inside outcon and holds the outer table
    this.formCon = null; // inner container where form will be printed
    this.bodyCon = null;
    this.bodyFormCon = null; // Displays the form
    this.bodyNoticeCon = null; // Right above the form and used for notices and inline duplicate detection
    
    this.ctbl = null; // Content table used for frame when printed inline
    this.toolbar = null;        
    this.plugins = new Array();
    this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
    this.fEnableClose = true; // Set to false to disable "close" and "save and close"
    
    // Sub Loader Variables
    this.newCalendar = false; // Determine whether to display newly added calendar in the sidebar
}

/**
 * Refresh the form
 */
AntObjectLoader_Calendar.prototype.refresh = function()
{
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_Calendar.prototype.toggleEdit = function(setmode)
{    
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_Calendar.prototype.onValueChange = function(name, value, valueName)
{    
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_Calendar.prototype.onNameChange = function(name)
{
}

/**
 * Inistialize the Subloader
 */
AntObjectLoader_Calendar.prototype.initSubloader = function()
{
    this.displayObjectForm();
    if(!this.mainObject.id)
        this.newCalendar = true;
    
    //this.updateMyCalendar();
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_Calendar.prototype.print = function(con, plugins)
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
    
    // Calendar Container
    this.calendarFormCon = alib.dom.createElement("div", this.bodyFormCon);
    
    this.initSubloader();
}

/**
 * Displays the dashboad form
 *
 * @this {AntObjectLoader_Calendar} 
 */
AntObjectLoader_Calendar.prototype.displayObjectForm = function()
{    
    var formLoader = new AntObjectLoader_Form(this.mainObject, this.loaderCls);
    formLoader.print(this.calendarFormCon);
    
    // Execute callback onSave when form is saved
    this.loaderCls.cls = this;    
    this.loaderCls.onSave = function()
    {
        if(this.cls.newCalendar)
            this.cls.updateMyCalendar();
            
        this.cls.newCalendar = false;
    }
}

/**
 * Update My Calendar Lists
 *
 * @this {AntObjectLoader_Calendar} 
 */
AntObjectLoader_Calendar.prototype.updateMyCalendar = function()
{
    var navBarHeader = document.getElementsByTagName("*");
    for(nbh in navBarHeader)
    {
        currentNbh = navBarHeader[nbh];
        
        if(currentNbh.className == "CNavBarSectionHeader")
        {
            if(currentNbh.innerHTML == "My Calendars")
            {
                var navBarSection = currentNbh.nextSibling;
                var calendarTable = navBarSection.firstChild;
                var calendarTbody = calendarTable.firstChild;
                
                var tr = alib.dom.createElement("tr", calendarTbody);
                var td = alib.dom.createElement("td", tr);
                
                var calendarCon = alib.dom.createElement("div", td);
                var checkboxCon = alib.dom.createElement("div", calendarCon);
                var colorCon = alib.dom.createElement("div", calendarCon);
                var nameCon = alib.dom.createElement("div", calendarCon);
                var dropdownCon = alib.dom.createElement("div", calendarCon);
                var colorListCon = alib.dom.createElement("div", dropdownCon);
                
                // Set Style
                alib.dom.styleSet(calendarCon, "margin-top", "5px");
                alib.dom.styleSet(checkboxCon, "margin-left", "3px");
                alib.dom.styleSet(checkboxCon, "float", "left");
                
                alib.dom.styleSet(colorCon, "float", "left");
                alib.dom.styleSet(colorCon, "margin", "0px 5px");
                alib.dom.styleSet(colorCon, "width", "15px");
                alib.dom.styleSet(colorCon, "border-radius", "3px");
                
                
                alib.dom.styleSet(nameCon, "width", "132px");
                alib.dom.styleSet(nameCon, "float", "left");
                alib.dom.styleSet(nameCon, "cursor", "pointer");
                
                alib.dom.styleSet(dropdownCon, "float", "left");
                alib.dom.styleSet(dropdownCon, "margin-top", "-15px");
                divClear(calendarCon);
                
                // Populate Containers
                var calendarCheckbox = alib.dom.setElementAttr(alib.dom.createElement("input", checkboxCon), [["type", "checkbox"], ["checked", "checked"]]);
                nameCon.innerHTML = this.mainObject.getValue("name");
                colorCon.innerHTML = "&nbsp;";
                
                // Add Dropdown
                var dm = new CDropdownMenu();
                dropdownCon.appendChild(dm.createImageMenu("/images/icons/rightclick_off.gif", "/images/icons/rightclick_over.gif", "/images/icons/rightclick_on.gif"));                
                var colorent = dm.addSubmenu("Change Color");
                
                /** 
                * Create color div
                */
                var clr = alib.dom.createElement("div", colorListCon);
                alib.dom.styleSet(clr, "float", "left");
                alib.dom.styleSet(clr, "width", "14px");
                alib.dom.styleSet(clr, "height", "10px");
                alib.dom.styleSet(clr, "margin-top", "3px");
                alib.dom.styleSet(clr, "background-color", "#FFFFFF");
                ALib.Effect.round(clr, 2);
                
                for (var j = 0; j < G_GROUP_COLORS.length; j++)
                {
                    colorent.addEntry(G_GROUP_COLORS[j][0], 
                                        function(cls, calid, color, clr) 
                                        {
                                            cls.changeCalColor(calid, color, clr);
                                        }, null, "<div style='width:9px;height:9px;background-color:#" + G_GROUP_COLORS[j][1] + "'></div>",
                                        [this, this.mainObject.id, G_GROUP_COLORS[j][1], colorCon]);
                }

                var delname = "Delete Calendar";
                dm.addEntry(delname, function(cls, cid, share_id, name){ if (share_id) cls.removeShare(share_id, name); else cls.deleteCalendar(cid, name); }, 
                "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif", 
                null, [this, this.mainObject.id, null, unescape(this.mainObject.getValue("name"))]);
                
                dm.addEntry("Share Calendar", 
                            function(cls, cid)
                            { 
                                emailComposeOpen(null, [["calendar_share", cid]]); 
                            }, "/images/icons/share_16.png", null, [this, this.mainObject]);
                break;
            }
        }
    }
    
}

/**
 * Change the color for a calendar
 *
 * @this {AntObjectLoader_Calendar} 
 */
AntObjectLoader_Calendar.prototype.changeCalColor= function(calendarId, color, colorCon)
{
    var params = [["color", color], ["calendar_id", calendarId]];
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.colorCon = colorCon;
    ajax.onload = function(ret)
    {
        alib.dom.styleSet(this.cbData.colorCon, "background-color", '#'+ret);
        //this.cbData.cls.calendarBrowser.refreshEvents();
    };
    ajax.exec("/controller/Calendar/calSetColor", params);
}
