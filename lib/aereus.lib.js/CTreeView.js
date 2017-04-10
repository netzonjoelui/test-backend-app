/*======================================================================================
	
	Module:		CTreeView

	Purpose:	Build TreeView GUI component

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Depends:	CDragAndDrop.js

	Usage:		var tv = new CTreeView();

				var n1 = tv.addNode("Test Node 1", null, "alert('Clicked');");

				var su1 = n1.addNode("Test Sub 1");
				var susu1 = su1.addNode("Sub Sub 1");
				var susu2 = su1.addNode("Sub Sub 2");
				var susu3 = su1.addNode("Sub Sub 3");

				var sususu1 = susu3.addNode("Sub Sub 1");
				var sususu2 = susu3.addNode("Sub Sub 2", null, function() {alert("Hi"); });
				var sususu3 = susu3.addNode("Sub Sub 3");

				var su2 = n1.addNode("Test Sub 2");
				var su3 = n1.addNode("Test Sub 3");

				tv.print(con);

======================================================================================*/

function CTreeView()
{
	this.m_nodes = new Array();
	this.m_outercon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(this.m_outercon, "CTreeViewCon");
}

/***********************************************************************************
 *
 *	Function: 	addNode
 *
 *	Purpose:	Creates root node. There can be more than one first level node.
 *
 *	Arguements:	title	- String||Element = what to put in the title
 *				icon	- (optional) string = path to icon
 *				action	- (optional) string||function = what do do on click
 *
 ***********************************************************************************/
CTreeView.prototype.addNode = function(title, icon, action)
{
	if (typeof icon == "undefined")
		var icon = null;
	
	if (typeof action == "undefined")
		var action = null;

	this.m_nodes[this.m_nodes.length] = new CTreeViewNode(this.m_outercon, 1, title, icon, action); // Root = 1
	return this.m_nodes[this.m_nodes.length - 1];
}

/***********************************************************************************
 *
 *	Function: 	getTvNodeById
 *
 *	Purpose:	Find and return a node by unique id
 *
 *	Arguements:	id	- string : the unique id of the specified node
 *
 ***********************************************************************************/
CTreeView.prototype.getTvNodeById = function(id, pnt)
{
	var parent_node = (pnt) ? pnt : this;

	for (var i = 0; i < parent_node.m_nodes.length; i++)
	{
		ALib.trace("CTreeView: getTvNodeById - checking " + id + " against " + parent_node.m_nodes[i].id);
		if (parent_node.m_nodes[i].id == id)
			return parent_node.m_nodes[i];

		var tmpnd = this.getTvNodeById(id, parent_node.m_nodes[i]);

		if (tmpnd)
			return tmpnd;
	}

	// Not found
	return null;
}

/***********************************************************************************
 *
 *	Function: 	print
 *
 *	Purpose:	Append or write TreeView html
 *
 *	Arguements:	container	- (optional) element = Will append as child
 *
 ***********************************************************************************/
CTreeView.prototype.print = function(container)
{
	if (typeof container != "undefined" && container)
		container.appendChild(this.m_outercon);
	else
		document.write(this.m_outercon.outerHTML);
}


/***********************************************************************************
 *
 *	Class:	 	CTreeViewNode
 *
 *	Purpose:	Node object - linked list of nodes
 *
 *	Arguements:	con		- element = container for tree
 *				depth	- integet = depth of current object for reference
 *				title	- string||element = what to put in the title
 *				icon	- (optional) string = path to icon
 *				action	- (optional) string||function = what do do on click
 *
 ***********************************************************************************/
function CTreeViewNode(con, depth, title, icon, action, args, parent_node)
{
	this.m_expanded = false;
	this.id = null;
	this.m_title =(typeof title != "undefined") ? title : "";				// Title to display - can be string
	this.m_icon = (typeof icon != "undefined") ? icon : "";					// Node Icon
	this.m_depth = depth;
	this.m_outercon = con;
	if (parent_node)
		this.m_parent = parent_node;
	else
		this.m_parent = null;
	
	//this.onclick = (typeof action != "undefined") ? action : null;			// Onclick action
	//this.ondoubleclick = null;		// Onclick action
	
	// Used for drag and drop
	this.registerDropzone = null;	
	this.onDragEnter = null;
	this.onDragExit = null;
	this.onDragDrop = null;
	// Subnodes (if any)
	this.m_nodes = new Array();

	// Now create div
	this.m_row = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(this.m_row, "CTreeViewRow");
	if (this.m_parent)
	{
		var after = null;

		// Check if we are the first child
		if (this.m_parent.m_nodes.length)
		{
			insertAfter(con, this.m_row, this.m_parent.getLastChildNode().m_row);
		}
		else
		{
			// Look like this is the first child node - put it after the parent
			insertAfter(con, this.m_row, this.m_parent.m_row);
		}

		this.m_nodes[this.m_nodes.length]
	}
	else
		con.appendChild(this.m_row);

	for (var i = 1; i < depth; i++)
	{
		var dv = ALib.m_document.createElement("div");
		alib.dom.styleSet(dv, "float", "left");
		alib.dom.styleSetClass(dv, "CTreeViewSpaceLine");
		this.m_row.appendChild(dv);
	}

	// Add tilde
	this.tilde_dv = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeNoSub");
	alib.dom.styleSet(this.tilde_dv, "float", "left");
	this.tilde_dv.m_node = this;
	this.tilde_dv.onclick = function()
	{
		if (this.m_node.m_expanded)
			this.m_node.collapse();
		else
			this.m_node.expand();
	}
	this.m_row.appendChild(this.tilde_dv);

	// Add icon (if any)
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv, "CTreeViewIcon");
	alib.dom.styleSet(dv, "float", "left");
	this.m_row.appendChild(dv);
	if (this.m_icon)
		dv.innerHTML = "<img src='" + this.m_icon + "' border='0' />";

	// Add body
	this.m_bodydv = ALib.m_document.createElement("div");
	alib.dom.styleSet(this.m_bodydv, "display", "inline");
	alib.dom.styleSetClass(this.m_bodydv, "CTreeViewBodyOut");
	this.m_row.appendChild(this.m_bodydv);
	this.m_bodydv.innerHTML = "<span>" + this.m_title + "</span>";

	// Clear floats
	var cdiv = ALib.m_document.createElement("div");
	alib.dom.styleSet(cdiv, "clear", "both");
	this.m_row.appendChild(cdiv);

	// Set action
	if (action)
	{
		if (args)
			this.setAction(action, args);
		else
			this.setAction(action);
	}

	// Set mouse states
	this.setMouseHandlers(true);

	// Set default display status
	if (depth > 1)
	{
		if (parent_node && parent_node.m_expanded)
		{
			this.show();
		}
		else
			this.hide();
	}
}

/***********************************************************************************
 *
 *	Function: 	setAction
 *
 *	Purpose:	Set onclick action for this node
 *
 *	Arguements:	function, args
 *
 ***********************************************************************************/
CTreeViewNode.prototype.setAction = function(action, args)
{
	if (action)
	{
		alib.dom.styleSet(this.m_bodydv, "cursor", "pointer");
		if (typeof action == "string")
		{
			this.m_bodydv.m_actstr = action;
			this.m_bodydv.onclick = function() { eval(this.m_actstr); };
		}
		else
		{
			this.m_bodydv.cb_function = action;
			this.m_bodydv.m_cb_args = args;
			this.m_bodydv.onclick = function()
			{
				if (this.m_cb_args)
				{
					switch (this.m_cb_args.length)
					{
					case 1:
						this.cb_function(this.m_cb_args[0]);
						break;
					case 2:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1]);
						break;
					case 3:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], this.m_cb_args[2]);
						break;
					case 4:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3]);
					case 5:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4]);
					case 6:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
										 this.m_cb_args[5]);
					case 7:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
										 this.m_cb_args[5], this.m_cb_args[6]);
					case 8:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
										 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7]);
						break;
					case 9:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
										 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7],
										 this.m_cb_args[8]);
					case 10:
						this.cb_function(this.m_cb_args[0], this.m_cb_args[1], 
										 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
										 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7],
										 this.m_cb_args[8], this.m_cb_args[9]);
						break;
					}
				}
				else
				{
					this.cb_function();
				}
			}
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	addNode
 *
 *	Purpose:	Creates sub node
 *
 *	Arguements:	title	- String||Element = what to put in the title
 *				icon	- (optional) string = path to icon
 *				action	- (optional) string||function = what do do on click
 *
 ***********************************************************************************/
CTreeViewNode.prototype.addNode = function(title, icon, action, args)
{
	if (typeof icon == "undefined")
		var icon = null;

	if (typeof action == "undefined")
		var action = null;

	if (typeof args == "undefined")
		var args = null;

	this.m_nodes[this.m_nodes.length] = new CTreeViewNode(this.m_outercon, this.m_depth + 1, title, icon, action, args, this);
	this.setHasChildren(true);

	return this.m_nodes[this.m_nodes.length - 1];
}

/***********************************************************************************
 *
 *	Function: 	editBody
 *
 *	Purpose:	Allow user to change the text in the body (only recommended for text)
 *
 ***********************************************************************************/
CTreeViewNode.prototype.editBody = function()
{
	var bdy = this.m_bodydv.childNodes.item(0);
	var buf = bdy.innerHTML;
	bdy.innerHTML = "";

	var inp = ALib.m_document.createElement("input");
	alib.dom.styleSet(inp, "height", "100%");
	inp.value = buf;
	inp.m_node = this;
	inp.onblur = function()
	{
		var val = this.value;
		this.m_node.m_bodydv.childNodes.item(0).innerHTML = val;
		this.m_node.onBodyEdit(val);
	}
	bdy.appendChild(inp);
	inp.select();
	inp.focus();
}

/***********************************************************************************
 *
 *	Function: 	onBodyEdit
 *
 *	Purpose:	Callback will be fired when node has been edited
 *
 ***********************************************************************************/
CTreeViewNode.prototype.onBodyEdit = function(val)
{
	
}

/***********************************************************************************
 *
 *	Function: 	setBody
 *
 *	Purpose:	Change the text in the body of the node
 *
 ***********************************************************************************/
CTreeViewNode.prototype.setBody = function(val)
{
	this.m_bodydv.childNodes.item(0).innerHTML = val;
}

/***********************************************************************************
 *
 *	Function: 	createContextMenu
 *
 *	Purpose:	Creates a dm menu when user right-clicks this node
 *
 *	Arguements:	N/A
 *
 ***********************************************************************************/
CTreeViewNode.prototype.createContextMenu = function()
{
	this.setMouseHandlers(false); // Allow dropdown to handle classes

	var dm = new CDropdownMenu();
	dm.createContextMenu(this.m_bodydv, "CTreeViewBodyOut", "CTreeViewBodyOver", "CTreeViewBodyOn");

	return dm;
}

/***********************************************************************************
 *
 *	Function: 	setHasChildren
 *
 *	Purpose:	Change node the style of one with children
 *
 *	Arguements:	haschildren	- bool = does this have children
 *
 ***********************************************************************************/
CTreeViewNode.prototype.setHasChildren = function(haschildren)
{
	if (haschildren)
	{
		if (this.m_expanded)
			alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubOpen");
		else
			alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubClosed");
	}
	else
		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeNoSub");
}

/***********************************************************************************
 *
 *	Function: 	getLastChildNode
 *
 *	Purpose:	Traverse the nodes to get the last child node for inserting before/after
 *
 ***********************************************************************************/
CTreeViewNode.prototype.getLastChildNode = function()
{
	var last = null;

	if (this.m_nodes.length)
		last = this.m_nodes[this.m_nodes.length - 1].getLastChildNode();
	else
		last = this;

	return last;
}

/***********************************************************************************
 *
 *	Function: 	hide
 *
 *	Purpose:	Hide this and child nodes
 *
 ***********************************************************************************/
CTreeViewNode.prototype.hide = function()
{
	alib.dom.styleSet(this.m_row, "display", "none");
}

/***********************************************************************************
 *
 *	Function: 	show
 *
 *	Purpose:	Display this node
 *
 ***********************************************************************************/
CTreeViewNode.prototype.show = function()
{
	alib.dom.styleSet(this.m_row, "display", "block");
}

/***********************************************************************************
 *
 *	Function: 	expand
 *
 *	Purpose:	Expand this node (display children if they exist)
 *
 ***********************************************************************************/
CTreeViewNode.prototype.expand = function()
{
	if (this.m_nodes.length)
	{
		for (var i = 0; i < this.m_nodes.length; i++)
			this.m_nodes[i].show();

		this.m_expanded = true;

		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubOpen");
	}
}

/***********************************************************************************
 *
 *	Function: 	collapse
 *
 *	Purpose:	Collapse this node (collapse children if they exist)
 *
 ***********************************************************************************/
CTreeViewNode.prototype.collapse = function()
{
	if (this.m_nodes.length)
	{
		for (var i = 0; i < this.m_nodes.length; i++)
		{
			this.m_nodes[i].collapse();
			this.m_nodes[i].hide();
		}

		this.m_expanded = false;

		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubClosed");
	}
}

/***********************************************************************************
 *
 *	Function: 	deleteNode
 *
 *	Purpose:	Remove node child nodes
 *
 ***********************************************************************************/
CTreeViewNode.prototype.deleteNode = function(node)
{
	if (this.m_nodes.length)
	{
		for (var i = 0; i < this.m_nodes.length; i++)
		{
			if (this.m_nodes[i] == node)
			{
				this.m_nodes[i] = null;
				this.m_nodes.splice(i, 1);
				break;
			}
		}

		if (!this.m_nodes.length)
			this.setHasChildren(false);
			
	}
}

/***********************************************************************************
 *
 *	Function: 	remove
 *
 *	Purpose:	Remove this node (remove children if they exist)
 *
 ***********************************************************************************/
CTreeViewNode.prototype.remove = function()
{
	if (this.m_nodes.length)
	{
		for (var i = 0; i < this.m_nodes.length; i++)
			this.m_nodes[i].remove();
	}

	this.m_outercon.removeChild(this.m_row);

	this.m_parent.deleteNode(this);
}

/***********************************************************************************
 *
 *	Function: 	setMouseHandlers
 *
 *	Purpose:	Add and remove event listners for setting on/over/out states
 *
 ***********************************************************************************/
CTreeViewNode.prototype.setMouseHandlers = function(set)
{
	var funover = function()
	{
		alib.dom.styleSetClass(this, "CTreeViewBodyOver");
	}

	var funout = function()
	{
		alib.dom.styleSetClass(this, "CTreeViewBodyOut");
	}

	if (set)
	{
		if (alib.userAgent.ie)
		{	
			this.m_bodydv.attachEvent('mouseover', funover);
			this.m_bodydv.attachEvent('mouseout', funout);
		}
		else
		{
			this.m_bodydv.addEventListener('mouseover', funover, false);
			this.m_bodydv.addEventListener('mouseout', funout, false);
		}
	}
	else
	{
		if (alib.userAgent.ie)
		{	
			this.m_bodydv.detachEvent('mouseover', funover);
			this.m_bodydv.detachEvent('mouseout', funout);
		}
		else
		{
			this.m_bodydv.removeEventListener('mouseover', funover, false);
			this.m_bodydv.removeEventListener('mouseout', funout, false);
		}
	}
}
