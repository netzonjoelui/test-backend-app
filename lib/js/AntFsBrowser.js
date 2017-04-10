/**
* @fileOverview AntFsBrowser is used to browse the online file system in ANT
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2003-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of AntFsBrowser
 *
 * @constructor
 * @parma {string} path Optional path to load. %userdir% will be loaded by default.
 */
function AntFsBrowser(path)
{
	/**
	 * The current path loaded/to load
	 *
	 * @private
	 * @var {string} currentPath
	 */
    this.currentPath = (typeof path != "undefined") ? path : "%userdir%";

	/**
	 * Browser is printed inline inslide an object form
	 *
	 * @private
	 * @var {bool} inline
	 */
	this.inline = false;

	/**
	 * List of AntObjectBrowserItem(s)
	 *
	 * @var {AntObjectBrowserItem[])
	 */
	this.objectList = new Array();

	/**
	 * Outer div for the whole browser
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.browserCon = null;

	/**
	 * Div container for the title
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.titleCon = null;

	/**
	 * Div container for the body of this browser
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.bodyCon = null;

	/**
	 * Div container for the toolbar
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.toolbarCon = null;

	/**
	 * Div container for pagination and number of objects display
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.objectListConNav = null;

	/**
	 * Div container for files and folders
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.objectListCon = null;

	/**
	 *Table and tbody for holding list of objects
	 *
	 * @var {DOMElement}
	 */
	this.listTable = null;
	this.listTableBody = null;
}

/**
 * Print the full main browser into con
 *
 * @public
 * @param {DOMElement} con The container to print this browser into
 */
AntFsBrowser.prototype.print = function(con)
{
	this.browserCon = alib.dom.createElement("div", con);

	this.titleCon = alib.dom.createElement("div", this.browserCon);
	this.titleCon.className = "objectLoaderHeader";
	if (this.antView)
		this.antView.getTitle(this.titleCon);
	else
		this.setTitle("Browse Files &amp; Documents");
	this.bodyCon = alib.dom.createElement("div", this.browserCon);
	this.bodyCon.className = "objectLoaderBody";

	// Create toolbar
	this.toolbarCon = alib.dom.createElement("div", this.bodyCon);

	var tb = new CToolbar();

	var button = alib.ui.Button("<img src='/images/icons/refresh_" + ((this.inline) ? "10" : "12") + ".png' />", {
		className:(this.inline) ? "b1 grCenter medium" : "b1 grCenter", tooltip:"Refresh", cls:this, 
		onclick:function() {this.cls.refresh(); }
	});
	tb.AddItem(button.getButton(), "left");
	
	tb.print(this.toolbarCon);

	this.objectListConNav = alib.dom.createElement("div", this.bodyCon);
	this.objectListCon = alib.dom.createElement("div", this.bodyCon);

	this.loadDirectory();
}

/**
 * Print a minimalized version of the browser for inclusion in object forms
 *
 * @public
 * @param {DOMElement} con The container to print this browser into
 */
AntFsBrowser.prototype.printInline = function(con)
{
}

/**
 * Display file/folder selection dialog. This will call onSelect(fid, label).
 *
 * @public
 * @this {AntFsBrowser}
 * @param {string} type May be "file" (default if blank) or "folder"
 * @param {CDialog} parent_dlg Optional parent dialog for module usage.
 */
AntFsBrowser.prototype.displaySelect = function(type, parent_dlg)
{
}

/**
 * Callback used once a user selects a file in selector mode
 *
 * Depending on the mode, fid and name could either be a file
 * or a folder.
 *
 * @public
 * @this {AntFsBrowser}
 * @param {int} fid The unique id of either the file or the folder.
 * @param {string} name The name of the file or folder selected.
 */
AntFsBrowser.prototype.onselect = function(fid, name)
{
}

/**
 * Set the header title
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} title The title string
 */
AntFsBrowser.prototype.setTitle = function(title)
{
	if (this.titleCon)
		this.titleCon.innerHTML = title;
}

/**
 * Resize the browser. This is mostly important in preview mode where 100% height is set.
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntFsBrowser.prototype.resize = function()
{
	if (!this.inline)
	{
		var minus_height = (alib.userAgent.ie) ? 30 : 0;
		var height = (getWorkspaceHeight()-minus_height);		

		if (this.titleCon)
			height -= this.titleCon.offsetHeight;

		if (this.toolbarCon)
			height -= this.toolbarCon.offsetHeight;


		if (height > 0)
			this.m_wfresults.setHeight((height - 10) + "px");
	}
}

/**
 * Load objects into browser
 *
 * @public
 * @this {AntFsBrowser}
 * @param {number} offset The offset to start displaying. If null, then start at 0
 * @param {bool} update If set to true, then only load id and revision, not the rest of the data
 */
AntFsBrowser.prototype.loadDirectory= function(offset, update)
{
	if (typeof offset == "undefined")
		var offset = 0;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(resp)
	{
		this.cbData.cls.totalNum = resp.totalNum;

		// Load folders
		for (var i = 0; i < resp.folders.length; i++)
		{
			this.cbData.cls.populateFolder(resp.folders[i]);
		}

		// Load files
		for (var i = 0; i < resp.files.length; i++)
		{
			this.cbData.cls.populateFile(resp.files[i]);
		}

		//this.cbData.cls.onLoad();
	};

	// Set basic query vars
	var args = [["offset", this.offset], ["limit", 250], ["path", this.currentPath]];

	// Add conditions
	/*
	for (var i = 0; i < this.conditions.length; i++)
	{
		var cond = this.conditions[i];

		args[args.length] = ["conditions[]", i];
		args[args.length] = ["condition_blogic_"+i, cond.blogic];
		args[args.length] = ["condition_fieldname_"+i, cond.fieldName];
		args[args.length] = ["condition_operator_"+i, cond.operator];
		args[args.length] = ["condition_condvalue_"+i, cond.condValue];
	}
	*/
	
	// Get order by
	/*
	for (var i = 0; i < this.sortOrder.length; i++)
	{
		args[args.length] = ["order_by[]", this.sortOrder[i].fieldName+" "+this.sortOrder[i].order];
	}
	*/

	ajax.exec("/controller/AntFs/readFolder", args, this.async);
}

/**
 * Load folders intodirectory 
 *
 * @public
 * @this {AntFsBrowser}
 */
AntFsBrowser.prototype.loadDirFolders = function()
{
	// Get folder list
	// ---------------------------------
	var fldrList = new AntObjectList("folder");
	fldrList.cbData.antFsB = this;
	fldrList.onLoad = function()
	{
		this.cbData.antFsB.populateFolders(this);
	}
	fldrList.getObjects();

	// Get file list
	// ---------------------------------
	var fileList = new AntObjectList("file");
	fileList.cbData.antFsB = this;
	fileList.onLoad = function()
	{
		this.cbData.antFsB.populateFiles(this);
	}
	fileList.getObjects();
}

/**
 * Load files into directory
 *
 * @public
 * @this {AntFsBrowser}
 * @param {number} offset The offset to start displaying. If null, then start at 0
 * @param {bool} update If set to true, then only load id and revision, not the rest of the data
 */
AntFsBrowser.prototype.loadDirFiles = function(offset, update)
{
	if (this.skipLoad) // skip first time
	{
		this.skipLoad = false;
		return;
	}

	if (typeof offset != "undefined" && offset != null)
	{
		if (offset != this.lastOffset)
		{
			var update = false;
			this.firstLoaded = false;
		}

		this.lastOffset = offset;
	}
	else
	{
		this.lastOffset = 0;
		offset = 0;
	}

	if (!update)
    {
        //this.objectListCon.innerHTML = "<div class='loading'></div>";
    }		

	// Get file list
	// ---------------------------------
	var fileList = new AntObjectList("file");
	fileList.cbData.antFsB = this;
	fileList.onLoad = function()
	{
		this.cbData.antFsB.populateFiles(this);
	}
	fileList.getObjects();
}

/**
 * Populate folders into the browser table
 *
 * @param AntObjectList list The populated list of folder objects
 */
AntFsBrowser.prototype.populateFolder = function(folderData)
{
	var dv = alib.dom.createElement("div", this.objectListCon);
	/*
	alib.dom.styleSet(dv, "border-bottom", "1px solid red");
	alib.dom.styleSet(dv, "margin-bottom", "20px");
	//dv.innerHTML = JSON.stringify(folderData);
	dv.innerHTML = folderData.id + " - " + folderData.name;
	*/

	if (!this.m_listTable)
	{
		// Setup the containing table
		this.m_listTable = alib.dom.createElement("table", this.objectListCon);
		alib.dom.styleSetClass(this.m_listTable, "aobListTable");
		this.m_listTable.cellPadding = 0;
		this.m_listTable.cellSpacing = 0;
		this.m_listTableBody = alib.dom.createElement("tbody", this.m_listTable);

		// Print headers
		/*
		if (this.viewmode == "table")
		{
			var rw = alib.dom.createElement("tr", this.m_listTableBody);

			var sel_all = alib.dom.createElement("input");
			sel_all.type = "checkbox";
			sel_all.cls = this;
			sel_all.onclick = function() { this.cls.fAllSelected = this.checked; this.cls.toggleSelectAll(); }
			if (!this.hideCheckbox)
			{
				var th = alib.dom.createElement("th", rw);
				alib.dom.styleSet(th, "text-align", "center");
				alib.dom.styleSet(th, "padding-left", "0px");
				alib.dom.styleSet(th, "padding-right", "5px");
				th.appendChild(sel_all);
			}

			// Add static id header
			//var th = alib.dom.createElement("th", rw, "ID");

			// Now add the rest of the fields
			for (var j = 0; j < this.view_fields.length; j++)
			{
				var fld_def = this.mainObject.getFieldByName(this.view_fields[j].fieldName);
				var th = alib.dom.createElement("th", rw, fld_def.title);
			}

		}
		*/
	}

	folderData.isBrowse = true;
	var objListItem = new AntObjectBrowserItem(folderData, this);
	objListItem.print(this.m_listTableBody);
}

/**
 * Populate files into the browser table
 *
 * @param AntObjectList list The populated list of file objects
 */
AntFsBrowser.prototype.populateFile = function(fileData)
{
	var dv = alib.dom.createElement("div", this.objectListCon);
	alib.dom.styleSet(dv, "border-bottom", "1px solid blue");
	alib.dom.styleSet(dv, "margin-bottom", "20px");
	//dv.innerHTML = JSON.stringify(fileData);
	dv.innerHTML = fileData.id + " - " + fileData.name;
}
