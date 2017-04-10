/****************************************************************************
*	
*	Class:		CWidBookmarks
*
*	Purpose:	Bookmarks widget
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidBookmarks()
{
	this.title = "Bookmarks";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Context menu set by calling process
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidBookmarks.prototype.main = function()
{
	this.loadBookmarks();
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidBookmarks.prototype.exit= function()
{
	this.m_container.innerHTML = "";
}

/*************************************************************************
*	Function:	addRootActions
*
*	Purpose:	Add actions to widget dropdown for new categories and bookmarks
**************************************************************************/
CWidBookmarks.prototype.addRootActions = function()
{
	// Create context menu
	// ----------------------------------------------------------------------------
	var cls = this;
	this.m_dm.addEntry('Add Category', function (clsref) { clsref.addCategory(null, clsref.m_tv); }, null, null, [cls]);


	var pfuct = function(dlg, dv, cid, tvnode) 
	{ 
		dlg.m_id = null; 
		dlg.name_txt.value = "";
		dlg.url_txt.value = "http://";
		dlg.m_cid = cid;
		dlg.m_tvnode = tvnode;

		dlg.customDialog(dv, 300, 300); 
	};
	this.m_dm.addEntry('Add Bookmark', pfuct, null, null, [this.dlgProperties, this.dlgProperties.m_dv, null, this.m_tv]);
}

/*************************************************************************
*	Function:	loadBookmarks
*
*	Purpose:	Get and display bookmarks
**************************************************************************/
CWidBookmarks.prototype.loadBookmarks = function()
{
	this.ajax = new CAjax();
	this.ajax.m_con = this.m_container;
	this.ajax.m_appcls = this;
	this.ajax.onload = function(root)
	{
		// Build Treeview
		this.m_appcls.m_tv = new CTreeView();
		
		this.m_appcls.loadCategory(root, this.m_appcls.m_tv);

		// Add table to div
		this.m_con.innerHTML = "";
		this.m_appcls.m_tv.print(this.m_con);

		// Create context menu now that the treeview is created
		this.m_appcls.addRootActions();
	};

	this.ajax.exec("/widgets/xml_bookmarks.php?function=get_bookmarks");
}

/*************************************************************************
*	Function:	loadCategory
*
*	Purpose:	Loop through categories
**************************************************************************/
CWidBookmarks.prototype.loadCategory= function(ajaxnode, tvnode)
{
	// Create properties dialog
	if (!this.dlgProperties)
		this.createPropertiesDialog();
	
	var num = ajaxnode.getNumChildren();
	for (var i = 0; i < num; i++)
	{
		var item = ajaxnode.getChildNode(i);
		
		// Check for categories
		if (item.m_name == "category")
		{
			var name = item.getAttribute('name');
			var id = item.getAttribute('id');
			var cnode = this.insertCategory(unescape(name), id, tvnode);
			
			// Check if category has children
			this.loadCategory(item, cnode);
		}


		// Check for bookmarks
		if (item.m_name == "bookmark")
		{
			var name = item.getChildNodeValByName("name");
			var url = item.getChildNodeValByName("url");
			var id = item.getChildNodeValByName("id");
			var cid = item.getChildNodeValByName("cid");

			this.insertBookmark(id, cid, unescape(url), unescape(name), tvnode);
		}
	}
}

/*************************************************************************
*	Function:	insertCategory
*
*	Purpose:	Insert a category into the tree view
**************************************************************************/
CWidBookmarks.prototype.insertCategory = function(name, id, tvnode)
{
	if (!name)
		name = "untitled";

	var cnode = tvnode.addNode(name, "/images/icons/closedfolderlink_small.gif");
	
	// Add ability to edit node title
	cnode.m_cid = id;
	cnode.onBodyEdit = function(val)
	{
		var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "rename_category", 
								  [["cid", this.m_cid], ["cname", val]]);
	}

	// Create right-click context menu
	var dm = cnode.createContextMenu();
	dm.addEntry("Delete Category", function(cls, id, tvnode) { cls.deleteCategory(tvnode, id); } , 
				"/images/icons/deleteTask.gif", null, [this, id, cnode]);
	dm.addEntry("Rename Category", function(cls, id, tvnode) { tvnode.editBody(); } , 
				null, null, [this, id, cnode]);
	dm.addEntry("New Subcategory", function(cls, id, tvnode) { cls.addCategory(id, tvnode); } , 
				null, null, [this, id, cnode]);

	var pfuct = function(dlg, dv, cid, tvnode) 
	{ 
		dlg.m_id = null; 
		dlg.name_txt.value = "";
		dlg.url_txt.value = "http://";
		dlg.m_cid = cid;
		dlg.m_tvnode = tvnode;

		dlg.customDialog(dv, 300, 300); 
	};
	dm.addEntry("Add Bookmark", pfuct, null, null, 
				[this.dlgProperties, this.dlgProperties.m_dv, id, cnode]);

	return cnode;
}

/*************************************************************************
*	Function:	insertBookmark
*
*	Purpose:	Insert a boodmark into the tree view
**************************************************************************/
CWidBookmarks.prototype.insertBookmark = function(id, cid, url, name, tvnode)
{
	if (!name)
		name = "untitled";

	var icon = "/images/icons/link_icon.gif";
	var tv_lnk = tvnode.addNode(unescape(name), icon);
	tv_lnk.m_url = url;
	tv_lnk.m_name = name;
	tv_lnk.m_id = id;
	tv_lnk.id = "fav" + id;

	var afunct = function(url) 
	{ 
		window.open(url); 
	};

	tv_lnk.setAction(afunct, [tv_lnk.m_url]);

	var dm = tv_lnk.createContextMenu();
	dm.addEntry("Delete Bookmark", function(cls, id, tvnode) { cls.deleteBookmark(tvnode, id); } , 
				"/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif", null, [this, id, tv_lnk]);

	var pfuct = function(dlg, dv, cid, lnknode, tvnode) 
	{ 
		dlg.m_id = lnknode.m_id; 
		dlg.m_cid = cid;
		dlg.m_tvnode = tvnode;
		dlg.name_txt.value = lnknode.m_name;
		dlg.url_txt.value = lnknode.m_url;
		dlg.customDialog(dv, 300, 300); 
	};
	dm.addEntry("Properties", pfuct, null, null, 
				[this.dlgProperties, this.dlgProperties.m_dv, cid, tv_lnk, tvnode]);
}

/*************************************************************************
*	Function:	createPropertiesDialog
*
*	Purpose:	Create and return properties dialog
**************************************************************************/
CWidBookmarks.prototype.createPropertiesDialog = function()
{
	this.dlgProperties = new CDialog("Edit Bookmark");
	this.dlgProperties.m_id = null;
	this.dlgProperties.m_cid = null;
	this.dlgProperties.m_tvnode = null;
	this.dlgProperties.m_dv = alib.dom.createElement("div");

	var lbl = alib.dom.createElement("div");
	lbl.innerHTML = "Name:";
	this.dlgProperties.m_dv.appendChild(lbl);
	this.dlgProperties.name_txt = alib.dom.createElement("input");
	alib.dom.styleSet(this.dlgProperties.name_txt, "width", "99%");
	this.dlgProperties.m_dv.appendChild(this.dlgProperties.name_txt);
	var lbl = alib.dom.createElement("div");
	lbl.innerHTML = "URL:";
	this.dlgProperties.m_dv.appendChild(lbl);
	this.dlgProperties.url_txt = alib.dom.createElement("input");
	alib.dom.styleSet(this.dlgProperties.url_txt, "width", "99%");
	this.dlgProperties.m_dv.appendChild(this.dlgProperties.url_txt);
	var okfunct = function(widcls, dlg)
	{
		widcls.editBookmark(dlg.name_txt.value, dlg.url_txt.value, dlg.m_cid, dlg.m_id, dlg.m_tvnode);
		dlg.hide();
	}

	// Create spacer div
	var dv = alib.dom.createElement("div", this.dlgProperties.m_dv);
	alib.dom.styleSet(dv, "text-align", "center");
	alib.dom.styleSet(dv, "padding-top", "5px");

	var btn = new CButton("OK", okfunct, [this, this.dlgProperties], "b2");
	btn.print(dv);

	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [this.dlgProperties], "b1");
	btn.print(dv);

	return this.dlgProperties;
}

/*************************************************************************
*	Function:	deleteBookmark
*
*	Purpose:	Delete a bookmark
**************************************************************************/
CWidBookmarks.prototype.deleteBookmark = function(tvnode, bid)
{
	ALib.Dlg.onConfirmOk = function(tvnode, bid)
	{
		var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "delete_bookmark", 
							  	  [["bid", bid]], 
								  function(ret, tvnode){ tvnode.remove();}, [tvnode]);
	}

	ALib.Dlg.confirmBox("Are you sure you want to delete this bookmark?", "Delete Bookmark", [tvnode, bid]);

	/*
	if (confirm("Are you sure you want to delete this bookmark?"))
	{
		var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "delete_bookmark", 
							  	  [["bid", bid]], 
								  function(ret, tvnode){ tvnode.remove();}, [tvnode]);
	}
	*/
}


/*************************************************************************
*	Function:	deleteCategory
*
*	Purpose:	Delete a category
**************************************************************************/
CWidBookmarks.prototype.deleteCategory = function(tvnode, cid)
{
	if (confirm("Are you sure you want to delete this category and all associated bookmarks?"))
	{
		var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "delete_category", 
							  	  [["catid", cid]], 
								  function(ret, tvnode){ tvnode.remove();}, [tvnode]);
	}
}

/*************************************************************************
*	Function:	editBookmark
*
*	Purpose:	Save changes to a bookmark
**************************************************************************/
CWidBookmarks.prototype.editBookmark = function(name, url, cid, bid, tvnode)
{
	var id = (bid) ? bid : "";

	var funct = function(ret, widcls, cid, url, name, tvnode)
	{ 
		var node = widcls.m_tv.getTvNodeById("fav" + ret);
		if (node == null)
		{
			widcls.insertBookmark(ret, cid, url, name, tvnode); 
		}
		else
		{
			node.m_url = url;
			node.m_name = name;
			node.setBody(name);
		}
	};
	var widcls = this;
	var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "edit_bookmark", 
							  [["bid", id], ["cid", cid], ["name", name], ["url", url]], 
							  funct, [widcls, cid, url, name, tvnode]);
}

/*************************************************************************
*	Function:	addCategory
*
*	Purpose:	Add a category
**************************************************************************/
CWidBookmarks.prototype.addCategory = function(pcid, tvnode)
{
	var parent_id = (pcid) ? pcid : "";

	var funct = function(cid, tvnode, widcls)
	{
		var node = widcls.insertCategory("New Category", cid, tvnode);

		node.editBody();
	}
	var widcls = this;
	var xmlrpc = new CAjaxRpc("/widgets/xml_bookmarks.php", "add_category", 
							  [["pcid", parent_id]], funct, [tvnode, widcls]);
}
