/****************************************************************************
*    
*    Class:        CWidWebpage
*
*    Purpose:    Webpage Widget
*
*    Author:        Marl Tumulak, marl.tumulak@aereus.com
*                Copyright (c) 2012 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidWebpage()
{
    this.title = "Web Page";
    this.m_container = null;    // Set by calling process
    this.m_widTitle = null;          // Get the Contect Table Instance
    this.m_dm = null;           // Dropdown menu will be set by parent
    this.m_data = null;         // If data is set, this will be passed by parent process
    this.m_id = null;           // The id of the dashboard widget
    this.appNavname = null;     // The name of the current application
    this.dashboardCls = null;   // Holds the class for dashboard application
    this.m_webpageId = null;    
    this.widgetWidth = null;
}

/**
 * Entry point for application
 *
 * @public
 * @this {CWidWebpage} 
 */
CWidWebpage.prototype.main = function()
{
    if(this.m_container.offsetWidth==0)
        return;
    
    // Dropdown Entries
    this.m_dm.addEntry('Set Webpage Url', 
                        function(cls) 
                        { 
                            cls.setWidgetData();
                        }, null, null, [this]);

    this.m_container.innerHTML = "";
    this.loadWidgetData();
}

/**
 * Perform needed clean-up on app exit
 *
 * @public
 * @this {CWidWebpage} 
 */
CWidWebpage.prototype.exit= function()
{
    this.m_container.innerHTML = "";    
}

/**
 * Sets the widget data
 *
 * @public
 * @this {CWidWebpage} 
 */
CWidWebpage.prototype.setWidgetData = function()
{
    var dlg = new CDialog("Webpage Details");
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    var title = "";
    var url = "";
    
    if(this.m_data)
    {
        var data = eval(this.m_data);
        title = data[0];
        url = data[1];
    }
    
    var urlForm = new Object();
    urlForm.title = alib.dom.setElementAttr(alib.dom.createElement("input"), [["type", "text"], ["label", "Title: "], ["value", title], ["width", "200px"]]);
    urlForm.url = alib.dom.setElementAttr(alib.dom.createElement("input"), [["type", "text"], ["label", "Url: "], ["value", url], ["width", "400"]]);    
    buildFormInput(urlForm, tBody);
    
    // Buttons
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Save and Close",
                        function(dlg, cls, urlForm)
                        {
                            cls.saveWidgetData(urlForm);
                            dlg.hide();
                        }, 
                        [dlg, this, urlForm], "b1");
    btn.print(divButton);
    
    var btn = new CButton("Cancel",
                        function(dlg) 
                        {  
                            dlg.hide(); 
                        }, 
                        [dlg], "b1");
    btn.print(divButton);

    dlg.customDialog(divModal, 450);
}

/**
 * Saves the widget data
 *
 * @public
 * @this {CWidWebpage} 
 */
CWidWebpage.prototype.saveWidgetData = function(urlForm)
{
    var url = urlForm.url.value;
    var title = urlForm.title.value;
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.title = title;
    ajax.cbData.dlg = showDialog("Saving, please wait...");
    ajax.onload = function(ret)
    {
        this.cbData.cls.loadWidgetData();
        
        // Set the Webpage Widget Title
        if(this.cbData.title)
            this.cbData.cls.m_widTitle.innerHTML = "Web Page - " + this.cbData.title;
        
        this.cbData.dlg.hide();
        ALib.statusShowAlert("Webpage Url successfully saved!", 3000, "bottom", "right");
    };
    
    this.m_data = "['" + title + "', '" + url + "']";
    
    var args = new Array();
    args[args.length] = ['data', this.m_data];
    
    if(this.dashboardCls)
    {
        var controller = "/controller/Dashboard/saveData";
        args[args.length] = ['dwid', this.m_id];
    }
    else
    {
        var controller = "/controller/Application/widgetSetData";
        args[args.length] = ['id', this.m_id];
        args[args.length] = ['appNavname', this.appNavname];
    }
    
    ajax.exec(controller, args);
}

/**
 * Loads the url of the webpage widget
 *
 * @public
 * @this {CWidWebpage} 
 */
CWidWebpage.prototype.loadWidgetData = function()
{    
    if(!this.m_data)
    {
        var divClick = alib.dom.setElementAttr(alib.dom.createElement("div", this.m_container), [["innerHTML", "Click to enter a page location"]]);
        alib.dom.styleSet(divClick, "width", "100%");
        alib.dom.styleSet(divClick, "cursor", "pointer");
        
        divClick.cls = this;
        divClick.onclick = function()
        {
            this.cls.setWidgetData();
        }
        
        return;
    }
    
    var data = eval(this.m_data);
    title = data[0];
    url = data[1];
    
	this.m_widTitle.innerHTML = "Web Page - " + title;
    
    if(!url)
        return;
    
    var widgetWidth = this.m_container.offsetWidth - 15;
    var cleanUrl = url.replace(/<%width%>/g, widgetWidth);
    
    this.m_container.innerHTML = "<div class='loading'></div>";
    ajax = new CAjax("json");
    ajax.cbData.cls = this;
    ajax.cbData.con = this.m_container;
    ajax.cbData.widgetWidth = widgetWidth;
    ajax.cbData.cleanUrl = cleanUrl;
    ajax.onload = function(ret)
    {
        this.cbData.con.innerHTML = "";
        var webpageCon = alib.dom.createElement("div", this.cbData.con);
        alib.dom.styleSet(webpageCon, "width", this.cbData.widgetWidth);
        alib.dom.styleSet(webpageCon, "overflow", "auto");
        alib.dom.styleSet(webpageCon, "maxHeight", "700px");
        
        switch(ret.type)
        {
            case "image":
                alib.dom.setElementAttr(alib.dom.createElement("img", webpageCon), [["src", this.cbData.cleanUrl]]);
                break;
            default:
                var pageData = ret.data;
                
                if(!pageData)
                    pageData = "Webpage Url was not able to retrieve any data.";
                    
                alib.dom.setElementAttr(alib.dom.createElement("div", webpageCon), [["innerHTML", pageData]]);
                break;
        }
    };
    
    var args = new Array();
    args[args.length] = ['url', cleanUrl];
    ajax.exec("/controller/Application/loadWidgetUrl", args);
}
