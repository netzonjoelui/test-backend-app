{
    name:"edit_site",
    title:"",
    mainObject:null,
    toolbar:null,
    formObj:null,

    main:function(con)
    {
        this.toolbarRecreated = false;
        this.data = new Object();
        this.m_con = con;
        this.loaded = false;
        this.buildInterface();
    },

    // This function is called when the object is saved. Use it to rest forms that require an object id
    objectsaved:function()
    {
    },

    save:function()
    {
        this.toolbarRecreated = false;
        // Clear the plugin toolbar button
        if(this.olCls)
        {
            this.olCls.pluginToolbarEntries = new Array();
            this.formObj.buildToolbar();
        }
        
        this.buildInterface();
        this.onsave();
    },

    onsave:function()
    {
    },

    load:function()
    {
    },

    buildInterface:function()
    {
        this.url = this.mainObject.getValue("url");
            
        if(!this.url || typeof this.url == "undefined")
            return;
            
        if(this.url.length == 0)
            return;
     
        // Check if url has http
        var regEx = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        var result = regEx.exec(this.url);
        if(!result)
            this.url = "http://" + this.url;

        this.url = this.url + "/antapi/enteredit?page=index";
        this.createToolbarButton();
    },
    
    createToolbarButton:function()
    {
        if(this.olCls)
        {
            this.olCls.pluginAddToolbarEntry("Edit Live Site", function(cbData) { cbData.cls.redirectSite(); }, { cls:this });
            
            if(!this.toolbarRecreated)
            {
                this.formObj.buildToolbar(); // Recreate toolbar
                this.toolbarRecreated = true;
            }
        }
    },
    
    redirectSite:function()
    {
        window.open(this.url, '_blank');
    }
}
