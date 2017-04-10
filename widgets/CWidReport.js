/****************************************************************************
*	
*	Class:		CWidReport
*
*	Purpose:	Rss Widget will probably be used more than once
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidReport()
{
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Context menu set by calling process
	this.m_data = null;			// If data is set, this will be passed by parent process
	this.m_report = null;
	this.m_show_graph= true;
	this.m_show_summary= true;
    this.appNavname = null;
    this.widgetWidth = null;
    this.m_reportid = null;
    this.dashboardCls = null;
	this.title = "Report";
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidReport.prototype.main = function()
{
	// Get and parse report data
	if (this.m_data)
	{
		var args = eval(this.m_data); // [report_id, show_graph, show_summary]
		this.m_reportid = args[0];
		this.m_show_graph = (args[1] == 'f')?false:true;
		this.m_show_summary = (args[2] == 'f')?false:true;
	}

	// Add view options
	this.m_dm.addEntry('Select Report', 
                        function(cls) 
                        { 
                            cls.setReport();
                        }, null, null, [this]);
	this.m_dm.addEntry('Toggle Chart Display', 
                        function(cls) 
                        { 
                            cls.m_report.toggleDisplay(true);	
                        }, null, null, [this]);
	this.m_dm.addEntry('Toggle Table Display', 
                        function(cls) 
                        {
                            cls.m_report.toggleDisplay(false);  
                        }, null, null, [this]);
                        
    // Get the last child so we can edit the title
    this.toggleTitleCon = this.m_dm.m_tbody.lastChild;
                        
	this.m_dm.addEntry('View Full Report', 
						function(cls) 
                        { 
                            if (cls.m_report) 
                                loadObjectForm("report", cls.m_reportid); 
                            else 
                                alert("Please load a report first");  
                        }, null, null, [this]);
    
	if (!this.m_data)
	{
		var dv = ALib.m_document.createElement("div");
		dv.m_widcls = this;
		dv.onclick = function() 
        { 
            this.m_widcls.setReport(); 
        };
		alib.dom.styleSet(dv, "cursor", "pointer");
		dv.innerHTML = "Click here to select report";
		this.m_container.appendChild(dv);
	}
	else
	{
		this.loadReport();
	}
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidReport.prototype.exit = function()
{
	this.m_container.innerHTML = "";
}

/*************************************************************************
*	Function:	loadReport
*
*	Purpose:	Load report object
**************************************************************************/
CWidReport.prototype.loadReport = function()
{    
    this.m_container.innerHTML = "";
    
    var objType = "report";
    var chartWidth;
    
    if(this.m_container.offsetWidth)
        chartWidth = this.m_container.offsetWidth;
    else
        chartWidth = this.widgetWidth;        
    
    // Initialize Report Instance    
    this.m_report = new Report(this.m_reportid);        
    this.m_report.cls = this;
    this.m_report.chartWidth = chartWidth - 10;
    
    // over-ride the onload function
    this.m_report.onload = function(ret)
    {        
        var toggleTitle = this.cls.toggleTitleCon.childNodes[1];
        
        if(ret.reportData.table_type)
            toggleTitle.innerHTML = "Toggle Table Display (" + ret.reportData.table_type.capitalize() + ")";
        
        this.print(this.cls.m_container);
    }
    
    this.m_report.loadReport();
}

/*************************************************************************
*	Function:	setReport
*
*	Purpose:	Set the report to pull from
**************************************************************************/
CWidReport.prototype.setReport = function()
{
	var ob = new AntObjectBrowser("report");
	ob.cbData.cls = this;
	ob.onSelect = function(rid) 
	{ 
		this.cbData.cls.m_reportid = rid; 
		this.cbData.cls.loadReport(); 
        
        if(this.cbData.cls.dashboardCls)
        {
            var data = "["+this.cbData.cls.m_reportid+"]";            
            this.cbData.cls.dashboardCls.saveData(this.cbData.cls.m_id, data);
        }            
        else
		    this.cbData.cls.saveSettings(); 
	}
	ob.displaySelect();
}

/*************************************************************************
*	Function:	saveSettings
*
*	Purpose:	Set the report to pull from
**************************************************************************/
CWidReport.prototype.saveSettings = function()
{
        
	var data = "["+this.m_reportid+", '"+((this.m_show_graph)?'t':'f')+"', '"+((this.m_show_summary)?'t':'f')+"']";

    var args = new Array();
    args[0] = ['id', this.m_id];
    args[1] = ['data', data];
    args[2] = ['appNavname', this.appNavname];
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Application/widgetSetData", args);
}
