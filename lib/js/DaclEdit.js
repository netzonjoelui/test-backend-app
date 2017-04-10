/**
* @fileOverview Dialg used to set Dacl permissions
*
* @author	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Class constructor
 *
 * @constructor
 * @param {string} name The unique name of this Dacl
 * @param {string} inheritFrom Optional existing DACL to inherit from
 */
function DaclEdit(name, inheritFrom)
{
	/**
	 * Dialog canvas
	 * 
	 * @var {DOMElement}
	 */
	this.con = null;

	/**
	 * The unique name of this dacl
	 *
	 * @var {string}
	 */
	this.name = name;

	/**
	 * The unique name of this dacl
	 *
	 * @var {string}
	 */
	this.inheritFrom = (inheritFrom) ? inheritFrom : null;

	/**
	 * Permissions
	 *
	 * Permissions have two properties: name and children
	 *
	 * @var {Object[]}
	 */
	this.permissions = new Array();

	/**
	 * Entries
	 *
	 * Array of dacl entries {user_id, group_id, pname}
	 *
	 * @var {Object[]}
	 */
	this.entries = new Array();

	/**
	 * Users and groups with access 
	 *
	 * Each will be an object with group_id|user_id, name
	 *
	 * @var {Object[]}
	 */
	this.usersAndGroups  = new Array();
}

/**
* Display dialog
*
* @param {object} parentDlg Dialog of parent
*/
DaclEdit.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.dlg = new CDialog("Edit Permissions", this.parentDlg);
	this.dlg.f_close = true;

	this.con = alib.dom.createElement("div");
	
	this.dlg.customDialog(this.con, 500, 450);
	this.buildInterface();
}

/**
* Build object edit interface
*/
DaclEdit.prototype.buildInterface = function()
{	
	// Clear canvas
	this.con.innerHTML = "";

	var formCon = alib.dom.createElement("div", this.con);
	alib.dom.styleSet(formCon, "overflow", "hidden");
	alib.dom.styleSet(formCon, "margin-bottom", "10px");
	alib.dom.styleSet(formCon, "height", "400px");

	// Print header / title
	var ttl = alib.dom.createElement("h2", formCon, "Modifying permissions for: " + this.name);

	// Print HR
	var hr = alib.dom.createElement("hr", formCon);

	// Add inherit
	// -------------------------------------------
	if (this.inheritFrom != null)
	{
		var inhCon = alib.dom.createElement("div", formCon);
		var cb = alib.dom.createElement("input", inhCon);
		cb.type = "checkbox";
		cb.checked =  true;
		var lbl = alib.dom.createElement("span", inhCon, "Inherit from parent object or permission");
	}

	// Print users and groups
	// -------------------------------------------
	var ttl = alib.dom.createElement("h3", formCon, "Users &amp; Groups With Access (click to view details)");

	this.usersCon = alib.dom.createElement("div", formCon);
	alib.dom.styleSet(this.usersCon, "max-height", "75px");
	alib.dom.styleSet(this.usersCon, "margin-bottom", "5px");
	alib.dom.styleSet(this.usersCon, "overflow", "auto");
	alib.dom.styleSet(this.usersCon, "padding-left", "2px");
	this.loadUserAndGroups();

	var buttonCon = alib.dom.createElement("div", formCon);

	// Add user
	var button = alib.ui.Button("Add User", {
		className:"b1", tooltip:"Click to give a specific user permission to this object", cls:this, 
		onclick:function() { this.cls.addUser(); }
	});
	button.print(buttonCon);

	// Add group
	var grpsel = new AntObjectGroupingSel("Add Group", "user", "groups");
	grpsel.cbData.cls = this;
	grpsel.onSelect = function(gid, name) 
	{ 
		this.setLabel("Add Group"); // rest, by default the selected group name will become the button name
		this.cbData.cls.insertUserGroup(null, gid, name);
		this.cbData.cls.setEntry(null, gid, "Full Control");
	}
	grpsel.print(buttonCon);
	/*
	var button = alib.ui.Button("Add Group", {
		className:"b1", tooltip:"Click to give a group access to this object", cls:this, 
		onclick:function() { this.cls.addGroup(); }
	});
	button.print(buttonCon);
	*/
	
	// Print permissions
	// -------------------------------------------
	this.permissionsTitle = alib.dom.createElement("h3", formCon, "Permissions (select a user or group above to edit permissions)");
	this.permissionsCon = alib.dom.createElement("div", formCon);
	alib.dom.styleSet(this.permissionsCon, "max-height", "146px");
	alib.dom.styleSet(this.permissionsCon, "overflow", "auto");
	alib.dom.styleSet(this.permissionsCon, "padding", "2px");
	this.loadEntries();

	// Print action buttons
	// -------------------------------------------
	var buttonCon = alib.dom.createElement("div", this.con);
	alib.dom.styleSet(buttonCon, "text-align", "right");

	// Save & close
	var button = alib.ui.Button("Save &amp; Close", {
		className:"b2", tooltip:"Save changes and close dialog", cls:this, 
		onclick:function() { this.cls.save(true); }
	});
	button.print(buttonCon);

	// Save
	var button = alib.ui.Button("Save", {
		className:"b1", tooltip:"Save any changes you have made", cls:this, 
		onclick:function() { this.cls.save(); }
	});
	button.print(buttonCon);

	// Cancel
	var button = alib.ui.Button("Cancel", {
		className:"b1", tooltip:"Close without saving any changes", cls:this, 
		onclick:function() { this.cls.dlg.hide(); }
	});
	button.print(buttonCon);
}

/**
 * Load users and groups
 *
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 */
DaclEdit.prototype.loadUserAndGroups = function(user, group)
{
	if (typeof user == "undefined") 
		var user = null;
	if (typeof group == "undefined") 
		var group = null;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;        
	ajax.cbData.selectOnLoad = {user:user, group:group, name:""};
	ajax.onload = function(ret)
	{
		this.cbData.cls.usersCon.innerHTML = "";
		for (var i = 0; i < ret.length; i++)
		{
			var usrgrp = ret[i];

			this.cbData.cls.insertUserGroup((usrgrp.user_id) ? usrgrp.user_id : null, 
											(usrgrp.group_id) ? usrgrp.group_id : null,
											usrgrp.name);
		}
		/*
		if (this.cbData.selectOnLoad.user || this.cbData.selectOnLoad.group)
		{
			this.cbData.cls.loadEntries(this.cbData.selectOnLoad.user, 
										this.cbData.selectOnLoad.group, 
										this.cbData.selectOnLoad.name);

			this.cbData.cls.selectUserGroup(this.cbData.selectOnLoad.user, this.cbData.selectOnLoad.group);
		}
		*/
	}

	var params = [["name", this.name]];
	ajax.exec("/controller/Security/loadDaclUsersAndGroups", params);
}

/**
 * Add a user|group to the array and table
 *
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 */
DaclEdit.prototype.insertUserGroup = function(user, group, name)
{
	if (typeof user == "undefined") 
		var user = null;
	if (typeof group == "undefined") 
		var group = null;

	// Add to array
	var atInd = -1;
	for (var i in this.usersAndGroups)
	{
		if (user)
		{
			if (this.usersAndGroups[i].user_id == user)
				atInd = i;
		}
		else if (group)
		{
			if (this.usersAndGroups[i].group_id == group)
				atInd = i;
		}
	}

	if (atInd == -1)
	{
		this.usersAndGroups.push({user_id:user, group_id:group, name:name});

		var con = alib.dom.createElement("div", this.usersCon);
		alib.dom.styleSetClass(con, "aobListRow");
		alib.dom.styleSet(con, 'padding', "3px");
		var icon = (user) ? "user_16.png" : "group_16.png";
		con.innerHTML = "<img src='/images/icons/" + icon + "'> " + name;
		con.usrgrp = {user_id:user, group_id:group};
		con.cls = this;
		con.onclick = function()
		{
			//this.cls.loadEntries(user, group, this.usrgrp.name);
			this.cls.printEntries(this.usrgrp.user_id, this.usrgrp.group_id);
		}
	}
}

/**
 * Load permissions and entries for this DACL
 */
DaclEdit.prototype.loadEntries = function()
{	
	this.permissionsCon.innerHTML = "<div class='loading'></div>";
	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(ret)
	{
		// Set permissions
		this.cbData.cls.permissions = ret.permissions;
		this.cbData.cls.entries = ret.entries;
		//this.cbData.cls.permissionsCon.innerHTML = "";
		this.cbData.cls.printEntries();
	}
	ajax.exec("/controller/Security/loadDaclPermissions", [["name", this.name]]);
}

/**
 * Load permissions
 *
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 * @param {int} lvl The number of levels deep we are (for the prefix of the name)
 * @param {Array} childPerms Array of permissions to print
 * @param {DOMElement} pntCon The parent container
 */
DaclEdit.prototype.printEntries = function(user, group, lvl, parentCon, childPerms)
{	
	var user = (user) ? user : null;
	var group = (group) ? group : null;

	if (user || group)
	{
		this.permissionsTitle.innerHTML = "What would you like " + this.getUsrGrpName(user, group)+ " to have access to? (uncheck all to delete)";
		this.selectUserGroup(user, group);
	}
	
	// Create prefix
	var level = (typeof lvl != "undefined") ? lvl : 0;
	var pre = "";
	for (var i = 0; i < lvl; i++) pre += "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	if (typeof childPerms == "undefined") this.permissionsCon.innerHTML = ""; // clear container

	var permissions = (typeof childPerms != "undefined") ? childPerms : this.permissions;

	// Loop through all available permissions and print
	for (var i = 0; i < permissions.length; i++)
	{
		var per = permissions[i];

		var entCon = alib.dom.createElement("div", this.permissionsCon);
		entCon.cls = this;
		entCon.name = per.name;
		entCon.usrgrp = {user_id: user, group_id: group};
		entCon.childCons = new Array();

		var spacer = alib.dom.createElement("span", entCon, pre);
		entCon.chk = alib.dom.createElement("input", entCon);
		entCon.chk.type = "checkbox";
		entCon.chk.entCon = entCon;
		entCon.chk.onclick = function() { this.entCon.setToggled(this.checked); }
		entCon.chk.checked = this.isEntry(user, group, per.name);
		if (!entCon.chk.checked && parentCon)
			entCon.chk.checked = parentCon.chk.checked;

		// We can only set permissions for actual user or group
		if (user==null && group==null)
			entCon.chk.disabled = true;

		var lbl = alib.dom.createElement("span", entCon, "&nbsp;" + per.name);

		// Set access function
		entCon.setToggled = function(on)
		{
			this.chk.checked = on;

			if (on)
				this.cls.setEntry(this.usrgrp.user_id, this.usrgrp.group_id, this.name);
			else
				this.cls.removeEntry(this.usrgrp.user_id, this.usrgrp.group_id, this.name);

			if (this.pnt)
				this.pnt.childrenChanged(on);
			
			for (var i = 0 ; i < this.childCons.length; i++)
				this.childCons[i].setToggled(on);
		}

		// Event fired when a child changes
		entCon.childrenChanged = function(on)
		{
			if (this.chk.checked && !on)
				this.chk.checked = on;
			else
			{
				var allSel = true;
				for (var i = 0; i < this.childCons.length; i++)
					if (!this.childCons[i].chk.checked) allSel = false;

				if (allSel)
					this.chk.checked = on;
			}
		}

		// Check if we have a parent container
		entCon.pnt = (typeof parentCon != "undefined") ? parentCon : null;
		if (typeof parentCon != "undefined")
			parentCon.childCons.push(entCon);

		if (per.children && per.children.length)
			this.printEntries(user, group, ++level, entCon, per.children);
	}
}

/**
 * Select specific user or group
 */
DaclEdit.prototype.selectUserGroup = function(user, group)
{	
	for (var i = 0; i < this.usersCon.childNodes.length; i++)
	{
		var el = this.usersCon.childNodes[i];
		var selected = false;
		if (group)
		{
			if (el.usrgrp.group_id && el.usrgrp.group_id  == group)
				selected = true;
		}
		else if (user)
		{
			if (el.usrgrp.user_id && el.usrgrp.user_id == user)
				selected = true;
		}

		alib.dom.styleSetClass(el, (selected) ? "aobListRowAct" : "aobListRow");
	}
}

/**
 * Save entries for this DACL
 *
 * @private
 * @param {bool} close If set to true then close on success
 */
DaclEdit.prototype.save = function(close)
{
	var params = [["name", this.name]];

	// Loop through all entries and see if they are checked
	for (var i in this.entries)
		params.push(["entries[]", JSON.stringify(this.entries[i])]);

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.closeOnSave = (typeof close != "undefined") ? close : false;
	ajax.onload = function(ret)
	{
		if (this.cbData.closeOnSave)
			this.cbData.cls.dlg.hide();
	}
	ajax.exec("/controller/Security/saveDaclEntries", params);
}

/**
 * Add a user and start them off with full controll
 *
 * @private
 */
DaclEdit.prototype.addUser = function()
{
	var ob = new AntObjectBrowser("user");
	ob.cbData.cls = this;
	ob.onSelect = function(oid, name) 
	{
		this.cbData.cls.insertUserGroup(oid, null, name);
		this.cbData.cls.setEntry(oid, null, "Full Control");
	}
	ob.displaySelect(this.dlg);
}

/**
 * Check if a user or group has access to a permission entry
 *
 * @private
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 * @param {string} perName The name of the permission entry
 */
DaclEdit.prototype.isEntry = function(user_id, group_id, perName)
{
	var user = (user_id) ? user_id : null;
	var group = (group_id) ? group_id : null;

	for (var i in this.entries)
	{
		if (this.entries[i].pname == perName)
		{
			if ((user && this.entries[i].user_id==user) || (group && this.entries[i].group_id==group))
				return true;
		}
	}

	return false;
}

/**
 * Set a user or group to have access to a permission
 *
 * @private
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 * @param {string} perName The name of the permission entry
 */
DaclEdit.prototype.setEntry = function(user_id, group_id, perName)
{
	var user = (user_id) ? user_id : null;
	var group = (group_id) ? group_id : null;
	var atInd = -1;

	// Check if entry already exists
	for (var i in this.entries)
	{
		if (this.entries[i].pname == perName)
		{
			if ((user && this.entries[i].user_id==user) || (group && this.entries[i].group_id==group))
				atInd = i;
		}
	}

	// If not already added, then add to array
	// all entries denote access
	if (atInd == -1)
	{
		this.entries.push({user_id:user, group_id:group, pname:perName});
	}
}

/**
 * Save entries for the given user or group
 *
 * @private
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 */
DaclEdit.prototype.removeEntry = function(user_id, group_id, perName)
{
	var user = (user_id) ? user_id : null;
	var group = (group_id) ? group_id : null;

	for (var i in this.entries)
	{
		if (this.entries[i].pname == perName)
		{
			if ((user && this.entries[i].user_id==user) || (group && this.entries[i].group_id==group))
			{
				this.entries.splice(i, 1);
			}
		}
	}
}

/**
 * Get the name of a user or group
 *
 * @param {int} user If set load permissions for a specific user
 * @param {int} group If set load permissions for a specific group
 * @param {string} The name of the user or group
 */
DaclEdit.prototype.getUsrGrpName = function(user, group)
{	
	for (var i in this.usersAndGroups)
	{
		if (user)
		{
			if (this.usersAndGroups[i].user_id == user)
				return this.usersAndGroups[i].name;
		}
		else if (group)
		{
			if (this.usersAndGroups[i].group_id == group)
				return this.usersAndGroups[i].name;
		}
	}

	// Not found
	return (user) ? "User" : "Group";
}
