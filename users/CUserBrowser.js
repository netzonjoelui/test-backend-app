/****************************************************************************
*	
*	Class:		CUserBrowser
*
*	Purpose:	Browser to select users within the page (no popup)
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function CUserBrowser()
{
	this.title = "Select User";		// Customize the title
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display a user browser
**************************************************************************/
CUserBrowser.prototype.showDialog = function()
{
	var dlg = new CDialog(this.title);
	this.m_dlg = dlg;
	dlg.f_close = true;

    var dv = alib.dom.createElement("div");
    
    var divWidth = 300;    
	// Search Bar
	var divSearch = alib.dom.createElement("div", dv);    
	var spanLbl = alib.dom.createElement("span", divSearch);
	spanLbl.innerHTML = "Find: ";
    
    // span container
    var spanContainer = alib.dom.createElement("span", divSearch);
    spanContainer.className = "clearIcon";
    
    // text search
    this.m_txtSearch = createInputAttribute(alib.dom.createElement("input", spanContainer), "text");
    if (this.inline)
        alib.dom.styleAddClass(this.m_txtSearch, "small");
    alib.dom.styleSet(this.m_txtSearch, "width", divWidth + "px");    
    alib.dom.styleSet(this.m_txtSearch, "paddingRight", "25px");        
    
    // span icon
    var spanIcon = alib.dom.createElement("span", spanContainer);
    spanIcon.className = "deleteicon";
    alib.dom.styleSet(spanIcon, "visibility", "hidden");
	
    // span icon onclick
    spanIcon.cls = this;            
    spanIcon.divWidth = divWidth;
    spanIcon.m_txtSearch = this.m_txtSearch;
    spanIcon.onclick = function()
    {
        this.m_txtSearch.value = "";
        this.m_txtSearch.focus();
        alib.dom.styleSet(this, "visibility", "hidden");
        this.cls.loadUsers();
    }
	
    // text search
    this.m_txtSearch.m_cls = this;
    this.m_txtSearch.spanIcon = spanIcon;
    this.m_txtSearch.onkeyup = function(e)
    {
        if (typeof e == 'undefined') 
        {
            if (ALib.m_evwnd)
                e = ALib.m_evwnd.event;
            else
                e = window.event;
        }

        if (typeof e.keyCode != "undefined")
            var code = e.keyCode;
        else
            var code = e.which;

        if (code == 13) // keycode for a return
        {
            this.m_cls.loadUsers();
        }
        
        // display the span icon
        if(this.m_cls.m_txtSearch.value.length > 0)                        
            alib.dom.styleSet(this.spanIcon, "visibility", "visible");
        else
            alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
    }
    
	var btn = new CButton("Search", function(cls) {  cls.loadUsers(); }, [this]);
    alib.dom.styleSet(btn.m_main, "marginLeft", "10px");    
	btn.print(divSearch);
	
	// Pagination and add
	this.pag_div = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.pag_div, "margin-bottom", "3px");
	alib.dom.styleSet(this.pag_div, "text-align", "right");
	this.pag_div.innerHTML = "Page 1 of 1";

	// Results
	this.m_browsedv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.m_browsedv, "height", "350px");
	alib.dom.styleSet(this.m_browsedv, "border", "1px solid");
	//alib.dom.styleSet(this.m_browsedv, "background-color", "white");
	alib.dom.styleSet(this.m_browsedv, "overflow", "auto");
	this.m_browsedv.innerHTML = "<div style='margin:10px;vertical-align:middle;'><span class='loading'></span></div>";
	
	dlg.customDialog(dv, 600, 410);

	// Load users
	this.loadUsers();
}

/*************************************************************************
*	Function:	select
*
*	Purpose:	Internal function to select a user then fire pubic onselect
**************************************************************************/
CUserBrowser.prototype.select = function(cid, name)
{
	this.m_dlg.hide();
	this.onSelect(cid, name);
}


/*************************************************************************
*	Function:	onSelect
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CUserBrowser.prototype.onSelect = function(cid, name)
{
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CUserBrowser.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	loadUsers
*
*	Purpose:	Load users
**************************************************************************/
CUserBrowser.prototype.loadUsers = function(start)
{
    var istart = (typeof start != "undefined") ? start : 0;
    this.m_browsedv.innerHTML = "<div class='loading'></div>";
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.cbData.con = this.m_browsedv;
    ajax.onload = function(ret)
    {
        if(!ret)
        {
            this.cbData.con.innerHTML = " No records found.";
            return;
        }
        
        if (ret)
        {
            this.cbData.con.innerHTML = "";
            this.cbData.cls.pag_div.innerHTML = "";

            this.cbData.cls.m_doctbl = new CToolTable("100%");
            var tbl = this.cbData.cls.m_doctbl;
            tbl.print(this.cbData.con);

            tbl.addHeader("ID");
            tbl.addHeader("Name");
            tbl.addHeader("Title");
            tbl.addHeader("Team");
            for(user in ret)
            {                
                var currentUser = ret[user].details;
                var rw = tbl.addRow();
                
                if(!currentUser)
                    continue;
                
                var id = currentUser.id;
                var title = currentUser.title;
                var teamName = currentUser.teamName;
                var name = currentUser.fullName;                
                
                if (!name)
                    name = "untitled";
                
                var linkData = [["href", "javascript:void(0);"], ["innerHTML", name]];
                var nameLink = alib.dom.setElementAttr(alib.dom.createElement("a"), linkData);                
                nameLink.id = id;
                nameLink.name = name;
                nameLink.cls = this.cbData.cls;
                nameLink.onclick = function()
                {
                    this.cls.select(this.id, this.name);
                }
                rw.addCell(id, true, "center");
                rw.addCell(nameLink);
                rw.addCell(title);
                rw.addCell(teamName);
            }
            
            if(ret.paginate)
            {
                var prev = ret.paginate.prev;
                var next = ret.paginate.next;
                var pagStr = ret.paginate.pag_str;
                
                var lbl = alib.dom.createElement("span", this.cbData.cls.pag_div);
                lbl.innerHTML = pagStr;
                
                if (prev || next)
                {
                    var lbl = alib.dom.createElement("span", this.cbData.cls.pag_div);
                    lbl.innerHTML = " | ";

                    if (prev !== null)
                    {
                        var prevLink = alib.dom.createElement("span", this.cbData.cls.pag_div);
                        prevLink.innerHTML = "&laquo; previous";
                        alib.dom.styleSet(prevLink, "cursor", "pointer");
                        prevLink.start = prev;
                        prevLink.cls = this.cbData.cls;
                        prevLink.onclick = function()
                        {
                            this.cls.loadUsers(this.start);
                        }
                    }

                    if (next !== null)
                    {
                        var nextLink = alib.dom.createElement("span", this.cbData.cls.pag_div);
                        nextLink.innerHTML = " next &raquo;";
                        alib.dom.styleSet(nextLink, "cursor", "pointer");
                        nextLink.start = next;
                        nextLink.cls = this.cbData.cls;
                        nextLink.onclick = function()
                        {
                            this.cls.loadUsers(this.start);
                        }
                    }
                }
            }
        }
        else
            this.cbData.con.innerHTML = " No records found.";
    };
    var args = new Array();
    if (this.m_txtSearch.value && this.m_txtSearch.value != 'search here')
        args[args.length] = ['search', escape(this.m_txtSearch.value)];
    if (istart)
        args[args.length] = ['start', istart];
    
    ajax.exec("/controller/User/getUsers", args);
}
