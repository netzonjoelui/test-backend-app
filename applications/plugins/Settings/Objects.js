function Plugin_Settings_Objects()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.objectData = new Object();
    this.objectData.objCls = this;
}

Plugin_Settings_Objects.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "Manage Objects";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    this.antObject = new CAntObjects();
    
    this.buildInterface();
}

Plugin_Settings_Objects.prototype.buildInterface = function()
{
    var divToolbarCon = alib.dom.createElement("div", this.innerCon);
    var divtblObject = alib.dom.createElement("div", this.innerCon);
    
    // create object button
    var tb = new CToolbar();
    var btn = new CButton("Create Object", 
    function(cls)
    {
        cls.antObject.addNewObject();
    },
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");
    
    // refresh button
    var btn = new CButton("Refresh", 
    function(cls)
    {
        cls.antObject.loadObjects(cls.innerCon);
    },
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    tb.print(divToolbarCon);
    
    // print CToolTable
    this.tblObject = new CToolTable("100%");    
    this.tblObject.addHeader("Name", "left");
    //this.tblObject.addHeader("&nbsp", "center", "50px");
    this.tblObject.addHeader("&nbsp", "center", "100px");
    //this.tblObject.addHeader("&nbsp", "center", "100px");
    this.tblObject.addHeader("&nbsp", "center", "50px");
    
    this.antObject.tblObject = this.tblObject;
    this.tblObject.print(divtblObject);
    
    this.antObject.loadObjects(this.innerCon);
}
