/****************************************************************************
*    
*    Class:        CWidgetBrowser
*
*    Purpose:    Browser to select widgets for dashboard
*
*    Author:        Marl Tumulak, marl.tumulak@aereus.com
*                Copyright (c) 2011 Aereus Corporation. All rights reserved.
*
*    Deps:        Alib
*
*****************************************************************************/
function CWidgetBrowser()
{
	this.title = "Widget Browser";
    this.title = "Add Widget";        // Customize the title
    this.appNavname = null;    
}

/*************************************************************************
*    Function:    showDialog
*
*    Purpose:    Display a widget browser
**************************************************************************/
CWidgetBrowser.prototype.showDialog = function()
{    

    var dlg = new CDialog(this.title);
    this.m_dlg = dlg;
    dlg.f_close = true;

    // Search Bar
    var dv = alib.dom.createElement("div");
    var lbl = alib.dom.createElement("span", dv);
    alib.dom.styleSet(lbl, "margin-left", "10px");
    lbl.innerHTML = "Select a widget...";    

    dlg.customDialog(dv, 600, 410);

    // Results
    this.m_browsedv = alib.dom.createElement("div", dv);
    alib.dom.styleSet(this.m_browsedv, "height", "350px");
    alib.dom.styleSet(this.m_browsedv, "border", "1px solid");    
    alib.dom.styleSet(this.m_browsedv, "overflow", "auto");
    alib.dom.styleSet(this.m_browsedv, "margin", "10px");    

    // Load widgets
    this.loadWidgets();
}

/*************************************************************************
*    Function:    select
*
*    Purpose:    Internal function to select a widgets then fire pubic onselect
**************************************************************************/
CWidgetBrowser.prototype.select = function(wid)
{    
    this.m_dlg.hide();
    this.onSelect(wid);
}


/*************************************************************************
*    Function:    onSelect
*
*    Purpose:    This function should be over-ridden
**************************************************************************/
CWidgetBrowser.prototype.onSelect = function()
{    
}

/*************************************************************************
*    Function:    onCancel
*
*    Purpose:    This function should be over-rideen
**************************************************************************/
CWidgetBrowser.prototype.onCancel = function()
{
}

/*************************************************************************
*    Function:    loadwidgets
*
*    Purpose:    Load widgets
**************************************************************************/
CWidgetBrowser.prototype.loadWidgets = function()
{    
    var dv = alib.dom.createElement("div");
    this.m_ajax = new CAjax();    
    this.m_ajax.m_browseclass = this;
    this.m_browsedv.innerHTML = "<div class='loading'></div>";
    this.m_ajax.onload = function(root)
    {                
        var active = "";
        this.m_browseclass.m_browsedv.innerHTML = "";

        var dvWidgetList = alib.dom.createElement("div", this.m_browseclass.m_browsedv);
        var dvWidgetDesc = alib.dom.createElement("div", this.m_browseclass.m_browsedv);
        var dvClear = alib.dom.createElement("div", this.m_browseclass.m_browsedv);
        var dvWidgetNameCont = alib.dom.createElement("div", dvWidgetDesc);
        var dvWidgetDescCont = alib.dom.createElement("div", dvWidgetDesc);
        var dvWidgetBtnCont = alib.dom.createElement("div", dvWidgetDesc);

        alib.dom.styleSet(dvWidgetNameCont, "font-weight", "bold");
        alib.dom.styleSet(dvWidgetNameCont, "font-size", "20px");

        alib.dom.styleSet(dvWidgetDescCont, "margin-top", "15px");
        alib.dom.styleSet(dvWidgetDescCont, "margin-bottom", "15px");
        alib.dom.styleSet(dvWidgetDescCont, "height", "225px");
        alib.dom.styleSet(dvWidgetDescCont, "overflow", "auto");
        alib.dom.styleSet(dvWidgetDescCont, "width", "300");

        alib.dom.styleSet(dvWidgetList, "float", "left");
        alib.dom.styleSet(dvWidgetList, "width", "200");
        alib.dom.styleSet(dvWidgetList, "margin", "20px");

        alib.dom.styleSet(dvWidgetDesc, "float", "left");
        alib.dom.styleSet(dvWidgetDesc, "width", "320px");
        alib.dom.styleSet(dvWidgetDesc, "margin-top", "20px");

        alib.dom.styleSet(dvClear, "clear", "both");

        var widgetList = alib.dom.createElement("select", dvWidgetList);
        widgetList.setAttribute("size","20");
        alib.dom.styleSet(widgetList, "height", "300px");
        alib.dom.styleSet(widgetList, "width", "200px");        

        var num = root.getNumChildren();        
        for (var j = 0; j < num; j++)
        {            
            var wid = root.getChildNode(j);
            var name = unescape(wid.m_text);
            var id = wid.getAttribute("id");
            var className = wid.getAttribute("class_name");
            var description = wid.getAttribute("description");

            var elOptNew = alib.dom.createElement("option")
            elOptNew.text = name;
            elOptNew.value = id;
            elOptNew.setAttribute("description", description);

            elOptNew.onclick = function()
            {
                dvWidgetNameCont.innerHTML = unescape(this.text);
                dvWidgetDescCont.innerHTML = unescape(this.getAttribute("description"));
            }

            try 
            {
                widgetList.add(elOptNew, null); // standard compliant; doesn't work in IE
            }
            catch(ex) 
            {
                widgetList.add(elOptNew); // IE only
            }

            if(j==0)
            {
                dvWidgetNameCont.innerHTML = unescape(name);
                dvWidgetDescCont.innerHTML = unescape(description);                
            }
        }        
        widgetList.selectedIndex=0;
        var btn = new CButton("Add This Widget", 
                                function(cls)
                                {
                                    cls.select(widgetList.options[widgetList.selectedIndex].value);
                                }, 
                                [this.m_browseclass]);        
        btn.print(dvWidgetBtnCont);        
    };

    var url = "/widgets/xml_settings.php?function=get_widgets";    
    this.m_ajax.exec(url);
}
