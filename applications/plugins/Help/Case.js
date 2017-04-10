function Plugin_Help_Case()
{
    this.mainCon = null;    
    this.innerCon = null;
    this.bodyCon = null;
    this.noticeCon = null;
    
    this.caseData = new Object();    
}

Plugin_Help_Case.prototype.print = function(antView)
{
    this.mainCon = antView.con;
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "objectLoaderHeader";
    this.titleCon.innerHTML = "Support Cases";
    this.bodyCon= alib.dom.createElement("div", this.mainCon);
    this.bodyCon.className = "objectLoaderBody";
    
    var toolbar = alib.dom.createElement("div", this.bodyCon);
    var tb = new CToolbar();
    
    var btn = new CButton("Refresh", 
    function(cls)
    {        
        cls.loadCaseData();
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    this.noticeCon = alib.dom.createElement("div", this.bodyCon);    
    this.innerCon = alib.dom.createElement("div", this.bodyCon);
    this.loadCaseData();
}

Plugin_Help_Case.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    this.noticeCon.innerHTML = "";
    
    var qs = window.location.href.split('?');
    
    if(false)
    {
        var p = alib.dom.createElement("p", this.noticeCon);
        alib.dom.styleSetClass(p, "success");
        p.innerHTML = "Your request has been received! We appreciate your business and will be in touch with you shortly.<br />If you wish to submit another request click \"Contact Support\" to the left or to update your case with additional comments or information select the id below.";
    }
    
    if(this.caseData)
    {
        var divtblCase = alib.dom.createElement("div", this.innerCon);
    
        // print CToolTable
        if(this.tblCase)
            this.tblCase.clear();
        else
        {
            this.tblCase = new CToolTable("100%");
            this.tblCase.addHeader("ID", "center", "20px");
            this.tblCase.addHeader("Name", "left", "300px");
            this.tblCase.addHeader("Submitted", "center", "100px");
            this.tblCase.addHeader("Status", "center", "100px");
        }
        
        this.tblCase.print(divtblCase);
        this.buildCaseRow();
    }    
}

/*************************************************************************
*    Function:    buildCaseRow
* 
*    Purpose:    Build Case Row
**************************************************************************/
Plugin_Help_Case.prototype.buildCaseRow = function()
{
    for(thisCase in this.caseData)
    {            
        var currentCase = this.caseData[thisCase];
        

		var link = "<a href=\"" + currentCase.link + "\" target='_blank'>" + currentCase.name + "</a>";
        var rw = this.tblCase.addRow();
        
        var cellId = rw.addCell(currentCase.id);
        alib.dom.styleSet(cellId, "paddingLeft", "15px");
        alib.dom.styleSet(cellId, "paddingRight", "0px");
        rw.addCell(link);
        rw.addCell(currentCase.timeEntered);
        rw.addCell(currentCase.statusName);
    }
}

/*************************************************************************
*    Function:    loadCaseData
* 
*    Purpose:    Load case data
**************************************************************************/
Plugin_Help_Case.prototype.loadCaseData = function()
{
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        if(!ret.error)
        {
            this.cls.caseData = ret;
            this.cls.buildInterface();
        }            
        else
        {
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            this.cls.innerCon.innerHTML = "No cases have been loaded.";
        }        
            
    };
    ajax.exec("/controller/Help/getCases");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}
