function Plugin_Settings_Applications()
{
    this.mainCon = null;
    this.innerCon = null;
	this.antView = null;
    
    this.userId = null;
    
    this.applicationData = new Object();
    this.applicationForm = new Object();
}

Plugin_Settings_Applications.prototype.print = function(antView)
{	
	this.antView = antView;
	this.mainCon = alib.dom.createElement('div', antView.con);

	// Setup view for loading settings
	this.antView.setViewsSingle(true);
	var viewItem = this.antView.addView("settings:[name]", {});
	viewItem.render = function() { }
	viewItem.onshow = function()  // draws in onshow so that it redraws every time
	{ 
		// Do not reload if this object id is already loaded
		//if (this.variable && this.options.lastLoaded == this.variable)
		if (this.options.lastLoaded == this.variable)
			return true;

		// Clear
		this.con.innerHTML = "";

		// Load app def then print
		var settings = new AntAppSettings();
		settings.onclose = function()
		{
			this.antView.goup();
		}
		settings.loadAndPrint(this.variable, this);
	}
    
	this.titleCon = alib.dom.createElement("div", this.mainCon);
	this.titleCon.className = "objectLoaderHeader";
	this.titleCon.innerHTML = "Applications";
	this.innerCon = alib.dom.createElement("div", this.mainCon);
	this.innerCon.className = "objectLoaderBody"; 
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {   
        this.cls.applicationData.applications = ret;            
        this.cls.buildInterface();        
    };    
    ajax.exec("/controller/Admin/getApplications");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

Plugin_Settings_Applications.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    
    var toolbar = alib.dom.createElement("div", this.innerCon);
    var tb = new CToolbar();
    
    // Add Application
    var btn = new CButton("Add Application", 
    function(cls)
    {
        cls.applicationModal();
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    // Refresh
    var btn = new CButton("Refresh", 
    function(cls)
    {
        cls.buildApplicationRow();
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    var divtblApplication = alib.dom.createElement("div", this.innerCon);
    
    // print CToolTable
    this.tblApplication = new CToolTable("100%");    
    this.tblApplication.addHeader("Name", "left", "300px");
    this.tblApplication.addHeader("Scope", "left", "100px");
    this.tblApplication.addHeader("&nbsp", "center", "50px");
    
    this.tblApplication.print(divtblApplication);
    this.buildApplicationRow();
    
    // user comment settings
    // commentSettings(this.innerCon);
}

/*************************************************************************
*    Function:    buildApplicationRow
* 
*    Purpose:    Build Application Row
**************************************************************************/
Plugin_Settings_Applications.prototype.buildApplicationRow = function()
{
    if(!this.applicationData.applications)
        return;
        
    // clear the current account table rows    
    this.tblApplication.clear();
    
    for(application in this.applicationData.applications)
    {            
        var currentApplication = this.applicationData.applications[application];
        
        var rw = this.tblApplication.addRow();
        
        var nameLink = alib.dom.createElement("a");    
        nameLink.innerHTML = currentApplication.title;
        nameLink.href = "javascript: void(0);";
        nameLink.cls = this;
        nameLink.name = currentApplication.name;
        nameLink.onclick = function()
        {
			this.cls.antView.navigate("settings:"+this.name);
			/*
            var url = "/app/" + this.name + "/settings";
            
            var params = 'width=800,height=600,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=yes';
            window.open(url, "applicationPopup", params);
			*/
        }
        
        rw.addCell(nameLink);
        rw.addCell(currentApplication.scope);
        
        // delete link column
        var deleteLink = "&nbsp;"; 
        if(currentApplication.scope == 'user')
        {
            // Delete Link
            deleteLink = alib.dom.createElement("a");    
            deleteLink.innerHTML = "[delete]";
            deleteLink.href = "javascript: void(0);";        
            deleteLink.cls = this;            
            deleteLink.id = currentApplication.id;
            deleteLink.onclick = function()
            {                
                if(confirm("Are you sure to delete this application?"))
                    this.cls.groupDelete(this.id);
            }                
        }
        
        rw.addCell(deleteLink, null, "center");
    }
}

/**
 * Print application settings
 *
 * @param {string} appname The application name to load settings for
 */
Plugin_Settings_Applications.prototype.loadSettings = function(appname)
{

}

/*************************************************************************
*    Function:    groupDelete
*
*    Purpose:    Delete a group
**************************************************************************/
Plugin_Settings_Applications.prototype.groupDelete = function(appId)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.appId = appId;
    ajax.onload = function(ret)
    {
        delete this.cls.applicationData.applications[this.appId];
        this.cls.buildApplicationRow();
        ALib.statusShowAlert("Application Deleted!", 3000, "bottom", "right");
    };
    ajax.exec("/controller/Admin/deleteApplication",
                [["appId", appId]]);
}

/*************************************************************************
*    Function:    applicationModal
*
*    Purpose:    Create a new application
**************************************************************************/
Plugin_Settings_Applications.prototype.applicationModal = function()
{    
    labelModal = "Create New Application";
        
    var dlg = new CDialog(labelModal);
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.applicationForm.applicationTitle = createInputAttribute(alib.dom.createElement("input"), "text", "applicationTitle", "App Title", "300px", "My Application");
    buildFormInput(this.applicationForm, tBody);
    
    // Done button
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Create Application", 
                        function(dlg, cls, applicationTitle)
                        {                            
                            ajax = new CAjax('json');
                            ajax.cls = cls;
                            ajax.title = applicationTitle.value;
                            ajax.dlg = showDialog("Creating application, please wait...");
                            ajax.onload = function(ret)
                            {
                                if(!ret['error'])
                                {
                                    var id = ret.id
                                    this.cls.applicationData.applications[id] = new Object();
                                    this.cls.applicationData.applications[id].id = id;
                                    this.cls.applicationData.applications[id].title = ret.title;
                                    this.cls.applicationData.applications[id].scope = "user";
                                    this.cls.applicationData.applications[id].name = ret.name;
                                    
                                    this.cls.buildApplicationRow();
                                    this.dlg.hide();
                                    ALib.statusShowAlert(this.title + " application has been created!", 3000, "bottom", "right");
                                }
                                else
                                    ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
                            };
                            ajax.exec("/controller/Admin/createApplication", 
                                        [["title", applicationTitle.value]]);
                            dlg.hide();                            
                        }, 
                        [dlg, this, this.applicationForm.applicationTitle], "b2");
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
