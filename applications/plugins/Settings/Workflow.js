function Plugin_Settings_Workflow()
{
    this.mainCon = null;
    this.innerCon = null;
    this.toolbarCon = null;
    
    this.defaultDomain = null;
    this.themeName = null;
    this.currentAlias = null;
    this.objTypes = null;
}

Plugin_Settings_Workflow.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "Applications Workflow";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    this.toolbarCon = alib.dom.createElement("div", this.innerCon);
    this.bodyCon = alib.dom.createElement("div", this.innerCon);
    this.bodyCon.innerHTML = "<div class='loading'></div>";
    
    // Load the object type
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.buildInterface();
        this.cbData.cls.objTypes = ret;
    };
    ajax.exec("/controller/Object/getObjects");
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

Plugin_Settings_Workflow.prototype.buildInterface = function()
{
    var tb = new CToolbar();    
    
    // create workflow button
    var btn = new CButton("Create Workflow", 
    function(cls)
    {
        cls.openWorkflow();
    },
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");
    
    // refresh button
    var btn = new CButton("Refresh", 
    function(cls)
    {
        cls.buildWorkflow();
    },
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    tb.print(this.toolbarCon);    
    this.buildWorkflow();
}

/*************************************************************************
*    Function:    loadWorkflowData
* 
*    Purpose:    Build the table for workflow data
**************************************************************************/
Plugin_Settings_Workflow.prototype.buildWorkflow = function()
{    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {        
        this.cls.loadWorkflowData(ret)
    };    
    ajax.exec("/controller/WorkFlow/getWorkflow");    
    this.bodyCon.innerHTML = "<div class='loading'></div>";
}

/*************************************************************************
*    Function:    loadWorkflowData
* 
*    Purpose:    Build the table for workflow data
**************************************************************************/
Plugin_Settings_Workflow.prototype.loadWorkflowData = function(workflowData)
{
    this.bodyCon.innerHTML = "";
    // print CToolTable
    var wfTbl = new CToolTable("100%");
    wfTbl.addHeader("Name");
    wfTbl.addHeader("Object Type", "center", "60px");
    wfTbl.addHeader("Active", "center", "30px");
    wfTbl.addHeader("", "center", "30px");
    wfTbl.addHeader("Delete", "center", "50px");
    
    wfTbl.print(this.bodyCon);
    
    if(workflowData.length)
    {
        for (workflow in workflowData)
        {
            var currentWorkflow = workflowData[workflow];
            
            var rw = wfTbl.addRow();
            var proParams = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
            var opnParams = "toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
            
            rw.addCell(currentWorkflow.name);
            rw.addCell(currentWorkflow.object_type);
            
            if(currentWorkflow.f_active == "t")
                rw.addCell("yes");
            else
                rw.addCell("no");
                
            var btn = new CButton("open", function(cls, wid, objType) 
                                        { 
                                            cls.openWorkflow(wid, objType); 
                                        }, [this, currentWorkflow.id, currentWorkflow.object_type], "b1");
            var openBtn = btn.getButton()
            rw.addCell(openBtn);
            
            // delete link
            var deleteLink = alib.dom.createElement("a");
            deleteLink.href = "javascript: void(0);";
            deleteLink.innerHTML = "<img border='0' src='/images/icons/delete_10.png' />";            
            deleteLink.rw = rw;
            deleteLink.cls = this;
            deleteLink.wid = currentWorkflow.id;
            deleteLink.name = currentWorkflow.name;
            deleteLink.onclick = function()
            {
                if(confirm("Are you sure you want to delete " + this.name + "?"))
                    this.cls.deleteWorkflow(this.wid, this.rw);
            }
            rw.addCell(deleteLink, true, "center");
        }
    }
}

/*************************************************************************
*    Function:    deleteWorkflow
* 
*    Purpose:    Deletes the workflow
**************************************************************************/
Plugin_Settings_Workflow.prototype.deleteWorkflow = function(wid, rw)
{
    ajax = new CAjax('json');
    ajax.rw = rw;
    ajax.onload = function(ret)
    {
        this.rw.deleteRow();
    };
    
    var args = new Array();
    args[args.length] = ["wid", wid];
    ajax.exec("/controller/WorkFlow/deleteWorkflow", args);
}

Plugin_Settings_Workflow.prototype.openWorkflow = function(id, objType)
{    
    // Edit existing workflow 
    if(id && objType)
    {
        var wfWizard = new WorkflowWizard(objType, id);
        wfWizard.g_objTypes = this.objTypes;
        wfWizard.showDialog();        
    }
    // Create new workflow
    else
    {
        var wfWizard = new WorkflowWizard();
        wfWizard.g_objTypes = this.objTypes;
        wfWizard.showDialog();        
    }
    
    wfWizard.cls = this;
    wfWizard.onsave = function()
    {
        this.cls.buildWorkflow();
    }
}
