/**
* @fileOverview AntFsOpen is used to select files or folders
*
*	Usage:		// Select a folder
*				var mydiv = alib.dom.createElement("div", document.body);
*				var cbrowser = new AntFsOpen();
*				cbrowser.filterType = "folder"; // can be set to file or folder
*				cbrowser.cbvalues.div = mydiv; 	// cbvalues is used to store callback specific data
*				cbrowser.onSelect = function(fid, name, path)
*				{
*					this.cbvalues.div.innerHTML = fid + " - " + name + " @ " + path;
*				}
*				cbrowser.showDialog(); // open model browser
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2003-2012 Aereus Corporation. All rights reserved.
*/

var g_AntFsBrLastLoc = null;

/**
 * Creates an instance of AntFsOpen
 *
 * @constructor
 * @parma {string} path Optional path to load. %userdir% will be loaded by default.
 */
function AntFsOpen()
{
	this.filterType = null;				// Objects to view (file types or folder)
	this.currentPath = "%userdir%";
	this.currentFolderId = null;		// The current folder id (for selecting)
	this.root_folderid = null;
	this.dirUserId = null;				// Each time a directory is loaded the current user is populated for the upload tool
	this.strTitle = "";
	this.root_name = "";				// Store the actual name of current root, is returned from readfolder

	/**
	 * Buffer to store for properties for callback functions
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();
    
    if(g_AntFsBrLastLoc)
        this.currentPath = g_AntFsBrLastLoc;
}

/**
 * Display the file browser
 */
AntFsOpen.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	if (this.strTitle)
		var title = this.strTitle;
	else
		var title = (this.filterType == "folder") ? "Select Folder" : "Select File(s)";
	this.m_dlg = new CDialog(title, this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	var dv = alib.dom.createElement("div");

	// toolbar
	// ---------------------------------------------------------
	var tbdiv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(tbdiv, "margin", "3px 0 3px 0");
	
	// Search Bar Containers
    var divSearchCon = alib.dom.setElementAttr(alib.dom.createElement("div", tbdiv), [["innerHTML", "Find: "]]);
    var divValueCon = alib.dom.createElement("div", tbdiv);
    var spanContainer = alib.dom.createElement("span", divSearchCon);    
    this.m_txtSearch = alib.dom.createElement("input", spanContainer);
    
    var btn = new CButton("Search", function(cls) {  cls.loadDirectory(); }, [this], "b1");
    btn.print(divSearchCon);
    
    // Style Set
    alib.dom.styleSet(divSearchCon, "float", "right");    
    alib.dom.styleSet(this.m_txtSearch, "width", "150px");
    alib.dom.styleSet(this.m_txtSearch, "marginRight", "5px");
    alib.dom.styleSet(this.m_txtSearch, "paddingRight", "25px");
    alib.dom.styleSet(divValueCon, "cursor", "pointer");
    spanContainer.className = "clearIcon";
    
    // span icon
    var spanIcon = alib.dom.createElement("span", spanContainer);
    spanIcon.className = "deleteicon";
    alib.dom.styleSet(spanIcon, "visibility", "hidden");
    alib.dom.styleSet(spanIcon, "right", "10px");
    
    // span icon onclick
    spanIcon.cls = this;
    spanIcon.textSearch = this.m_txtSearch;
    spanIcon.onclick = function()
    {
        this.textSearch.value = "";
        this.textSearch.focus();
        alib.dom.styleSet(this, "visibility", "hidden");
        this.cls.loadDirectory();
    }
    
    this.m_txtSearch.spanIcon = spanIcon;
    this.m_txtSearch.m_cls = this;
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
			this.m_cls.loadDirectory();
		}
        
        if(this.value.length > 0)
            alib.dom.styleSet(this.spanIcon, "visibility", "visible");
        else
            alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
	}

	// Actions
	if (this.filterType != "folder") // Select folder
	{
		var btn = new CButton("Upload File(s)", function(cls) {  cls.uploadFiles(); }, [this], "b2");
		btn.print(tbdiv);
	}
	var btn = new CButton("Create New Folder", function(cls) {  cls.createNewFolder(); }, [this], "b1");
	btn.print(tbdiv);

	
	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", dv);
	this.pathDiv.innerHTML = "&nbsp;";
	alib.dom.styleSet(this.pathDiv, "border", "1px solid");
	alib.dom.styleSet(this.pathDiv, "padding", "3px");
	//alib.dom.styleSet(this.pathDiv, "background-color", "white");
	
	
	// Pagination and add
	// ---------------------------------------------------------
	this.pag_div = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.pag_div, "margin-bottom", "3px");
	alib.dom.styleSet(this.pag_div, "text-align", "right");
	this.pag_div.innerHTML = "Page 1 of 1";

	// Results
	// ---------------------------------------------------------
	var bdv = alib.dom.createElement("div", dv);

	var appcon = new CSplitContainer("verticle", "100%", "350px");
	appcon.resizable = true;
	this.appNav = appcon.addPanel("105px");;
	this.m_browsedv = appcon.addPanel("*");
	alib.dom.styleSet(this.m_browsedv, "border", "1px solid");
	appcon.print(bdv);
	this.m_browsedv.innerHTML = "&nbsp;Loading...";

	// Buttons
	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");
	if (this.filterType == "folder") // Select folder
	{
		
		var btn = new CButton("Select This Folder", function(cls) { cls.select(cls.currentFolderId, cls.currentFolderName, cls.currentPath); }, [this], "b2");
		btn.print(dv_btn);
	}
	dlg.customDialog(dv, 600);

	// Load customers
	this.loadDirectory();

	// Build leftnav
	this.buildLeftnav();
    
	// Set starting path
	this.setPath(this.currentPath);
}

/**
 * Build the left navigation
 *
 * @private
 */
AntFsOpen.prototype.buildLeftnav= function()
{
	this.m_navbar = new CNavBar();
	var sec = this.m_navbar.addSection("Navigation");
	sec.addItem("My Files", "/images/icons/folder.png", function(cls){ cls.setPath("%userdir%"); cls.loadDirectory(); }, 
				[this], "favorites_my");
	sec.addItem("Global Files", "/images/icons/world_16.png", function(cls){ cls.setPath("/"); cls.loadDirectory(); }, 
				[this], "favorites_global");

	this.m_navbar.print(this.appNav);
    
    
    var parts = this.currentPath.split("/");
    if(parts[0] == "%userdir%")
	    this.m_navbar.itemChangeState('favorites_my', 'on');
    else
        this.m_navbar.itemChangeState('favorites_global', 'on');
        
	//sec.setHeight(alib.dom.getContentHeight(this.appNav));
}

/**
 * Internal function called when an object is selected
 *
 * @private
 */
AntFsOpen.prototype.select = function(fid, name, path)
{
	this.m_dlg.hide();
	this.onSelect(fid, name, path);
}

/**
 * Callback fired when user selects a file or a folder
 *
 * @public
 * @param int fid The unique id of the file or folder
 * @param string name The name of the file or folder
 * @param string path The full path to the file or folder
 */
AntFsOpen.prototype.onSelect = function(fid, name, path)
{
}

/**
 * Callback fired when user cancels the dialog
 *
 * @public
 */
AntFsOpen.prototype.onCancel = function()
{
}

/**
 * Callback fired when user sets the path
 *
 * @public
 */
AntFsOpen.prototype.onSetPath = function(path)
{
}

/**
 * Load the contents of the current directory
 *
 * @public
 * @param int start The starting offset to begin loading
 */
AntFsOpen.prototype.loadDirectory = function(start)
{
	var istart = (typeof start != "undefined") ? start : 0;

    
    if(!this.m_browsedv)
        this.m_browsedv = alib.dom.createElement("div");
        
    if(!this.pag_div)
        this.pag_div = alib.dom.createElement("div");
        
    
	this.m_browsedv.innerHTML = "&nbsp;Loading...";

	if (typeof offset == "undefined")
		var offset = 0;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(resp)
	{
		this.cbData.cls.m_browsedv.innerHTML = "";
		this.cbData.cls.pag_div.innerHTML = "";

		this.cbData.cls.m_doctbl = new CToolTable("100%");
		var tbl = this.cbData.cls.m_doctbl;
		tbl.print(this.cbData.cls.m_browsedv);

		tbl.addHeader("&nbsp;", "center", "20px");
		tbl.addHeader("Name");
		tbl.addHeader("Size");

		if (resp.browseByCurId)
			this.cbData.cls.currentFolderId = resp.browseByCurId;

		// Populate browseby(folders) if set
		// -------------------------------------------
		if (resp.browseByObjects && resp.browseByObjects.length)
		{
			var num_folders = parseInt(resp.browseByObjects.length);
			for (var i = 0; i < resp.browseByObjects.length; i++)
			{
				var objData = resp.browseByObjects[i];
				objData.isBrowse = true;
				var path = this.cbData.cls.currentPath;
				if (this.cbData.cls.currentPath != "/") // leave root out because it is implied in the / path
					path += "/";
				path += objData.name;

				var alnk = alib.dom.createElement("a");
				alnk.innerHTML = objData.name;
				alnk.href = "javascript:void(0);";
				alnk.m_id = objData.id;
				alnk.m_name = objData.name;
				alnk.m_browseclass = this.cbData.cls;
				alnk.m_path = path;
				alnk.onclick = function()
				{
					this.m_browseclass.setPath(this.m_path);
					this.m_browseclass.loadDirectory();
				}

				var rw = tbl.addRow();
				rw.addCell("<img src='/images/icons/folder_16.png' border='0'>", false, "center");
				rw.addCell(alnk);
				rw.addCell("&nbsp;", false, 'right');
			}
		}

		// Load files
		// -------------------------------------------
		for (var i = 0; i < resp.objects.length; i++)
		{
			var alnk = alib.dom.createElement("a");
			alnk.innerHTML = resp.objects[i].name;
			alnk.href = "javascript:void(0);";
			alnk.m_id = resp.objects[i].id;
			alnk.m_name = resp.objects[i].name;
			alnk.m_browseclass = this.cbData.cls;
			alnk.m_path = path;
			alnk.onclick = function()
			{
				this.m_browseclass.select(this.m_id, this.m_name, this.m_path);
			}

			var rw = tbl.addRow();
			rw.addCell("<img src='/images/icons/generic.gif' border='0'>", false, "center");
			rw.addCell(alnk);
			rw.addCell((resp.objects[i].file_size)?resp.objects[i].file_size:"&nbsp;", false, 'right');
		}

		// Handle pagination
		// -------------------------------------------
		if (resp.next || resp.prev)
		{
			var lbl = alib.dom.createElement("span", this.cbData.cls.pag_div);
			lbl.innerHTML = resp.desc;

			var lbl = alib.dom.createElement("span", this.cbData.cls.pag_div);
			lbl.innerHTML = " | ";

			if (resp.prev)
			{
				var lnk = alib.dom.createElement("span", this.cbData.cls.pag_div);
				lnk.innerHTML = "&laquo; previous";
				alib.dom.styleSet(lnk, "cursor", "pointer");
				lnk.start = resp.prev;
				lnk.m_browseclass = this.cbData.cls;
				lnk.onclick = function()
				{
					this.m_browseclass.loadDirectory(this.start);
				}
			}

			if (resp.next)
			{
				var lnk2 = alib.dom.createElement("span", this.cbData.cls.pag_div);
				lnk2.innerHTML = " next &raquo;";
				alib.dom.styleSet(lnk2, "cursor", "pointer");
				lnk2.start = resp.next;
				lnk2.m_browseclass = this.cbData.cls;
				lnk2.onclick = function()
				{
					this.m_browseclass.loadDirectory(this.start);
				}
			}
		}
	};

	// Set basic query vars
	var args = [["obj_type", "file"], ["offset", istart], ["limit", 250], ["browsebyfield", "folder_id"], ["browsebypath", this.currentPath]];

	if (this.m_txtSearch && this.m_txtSearch.value && this.m_txtSearch.value != 'search here')
		args[args.length] = ["cond_search", this.m_txtSearch.value];
        
	ajax.exec("/controller/ObjectList/query", args);
    
    g_AntFsBrLastLoc = this.currentPath;
}


/**
 * Prase a path with links
 *
 * @private
 */
AntFsOpen.prototype.parsePath = function()
{
	this.pathDiv.innerHTML = "";

	var parts = this.currentPath.split("/");
	var fullPath = "";
    var fullPathCheck = 0;
	for (var i = 0; i < parts.length; i++)
	{
		var part = parts[i];
		var title = part;

		if (i == 0 && part == "") // root
		{
			title = "Global Files";
			part = "/";
            fullPathCheck = 1;
		}
		else if (i == 0 && part == "%userdir%")
		{
			title = "My Files";
            fullPathCheck = 0;
		}

		if (i > fullPathCheck)
			fullPath += "/";
		
		fullPath += part;

		if (i > 0)
			var sp = alib.dom.createElement("span", this.pathDiv, "&nbsp;/&nbsp;");

		var a = alib.dom.createElement("a", this.pathDiv);
		a.innerHTML = title;
		a.href = "javascript:void(0);";
		a.fullPath = fullPath;
		a.bcls = this;
		a.onclick = function()
		{
			this.bcls.setPath(this.fullPath);
			this.bcls.loadDirectory();
		}
	}

}

/**
 * Set the current path by a folder id
 *
 * @public
 * @param string the current full path
 */
AntFsOpen.prototype.setPathById = function(folderId)
{
	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(ret)
	{
		if(!ret['error'])
		{
			this.cbData.cls.setPath(ret);
			this.cbData.cls.loadDirectory()
		}
		else 
			alert(ret['error']);
	};
	ajax.exec("/controller/AntFs/getPathFromId", [["folder_id", folderId]]);
}

/**
 * Set the current path
 *
 * @public
 * @param string the current full path
 */
AntFsOpen.prototype.setPath = function(path)
{
	this.currentPath = path;
	if (this.pathDiv) // if form is printed
		this.parsePath();
    
    this.onSetPath(path);
}

/**
 * Set the dialog title
 *
 * @public
 * @param string title The title of this dialog
 */
AntFsOpen.prototype.setTitle = function(title)
{
	this.strTitle = title;
}

/**
 * Create new browseby (folder) in the current directory
 *
 * @public
 */
AntFsOpen.prototype.createNewFolder = function()
{
	var dlg_p = new CDialog();
    dlg_p.parentDlg = true;
	dlg_p.promptBox("Name:", "New Folder Name", "New Folder");
	dlg_p.m_cls = this;
	dlg_p.onPromptOk = function(val)
	{
		var args = [["path", this.m_cls.currentPath], ["name", val]];
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this.m_cls;
        ajax.onload = function(ret)
        {
            if(!ret['error'])
                this.cbData.cls.loadDirectory();
			else 
				alert(ret['error']);
        };
        ajax.exec("/controller/AntFs/newFolder", args);
        
        g_AntFsBrLastLoc = this.m_cls.currentPath;
	}
    
    dlg_p.m_input.onblur = function ()
    {
        checkSpecialCharacters("folder", this.value, this);
    }
}

/*************************************************************************
*	Function:	setPath
*
*	Purpose:	Set the current path
**************************************************************************/
AntFsOpen.prototype.uploadFiles = function()
{
	var cfupload = new AntFsUpload(this.currentPath, this.m_dlg);
	cfupload.cbData.m_browseclass = this;
	cfupload.onUploadFinished = function()
	{
		this.cbData.m_browseclass.loadDirectory();
	}
	cfupload.showDialog();
}


