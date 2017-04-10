/**
 * @fileoverview This class represents each item in a browser list
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */


/**
 * Creates an instance of AntObjectBrowser_Item
 *
 * @constructor
 * @param {Object} data Object with value for each CAntObject property
 * @param {AntObjectBrowser} bcls Browser class
 */
function AntObjectBrowser_Item(objData, bcls)
{
	/**
	 * Item renderer / view
	 *
	 * @type {AntObjectBrowser_Item_*}
	 */
	this.renderer = null;

    this.selectedObjId = null; // Object that is currently selected
	this.bcls = bcls;
	this.id = null;
	this.allow_open = null;
	this.revision = 1;
	this.parentCon = null;
	this.objData = null;
	this.tbody = null; // for table
	this.row = null; // for table
	this.listCon = null; // for detail
	this.objData = objData; // the data for this browser item
	this.seen = true;
	this.fIsSelected = false;
	this.checkbox = alib.dom.createElement("input");
	this.checkbox.type = "checkbox";
	this.checkbox.cls = this;
	this.checkbox.onclick = function()
	{
		this.cls.select(this.checked);
	}
    
	if (objData)
	{
		this.allow_open = objData.security.view;
		this.id = objData.id;
		this.objData = objData;

		if (objData.revision)
			this.revision = objData.revision;
            
        if (objData.f_seen=='f' || objData.f_seen==false)
            this.seen = false;
	}
}

/**
 * Add an item to the list
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMTBody} tbody The table container that is holding the list of objects
 * @param {AntObjectBrowser_Item} insertBeforeItem Optional item that should come right after this one
 */
AntObjectBrowser_Item.prototype.print = function(tbody, insertBeforeItem)
{
	if (!this.listCon)
		this.listCon = tbody;

	if (!this.row)
	{
		var itemRow = alib.dom.createElement("tr");
		this.row = itemRow;
		this.oid = this.id;
		this.row.cls = this;

		if (insertBeforeItem)
		{

			tbody.insertBefore(itemRow, insertBeforeItem.row);

		}
		else
		{
			tbody.appendChild(this.row);
		}

		// Set initial class
		if (this.selected())
			var outercls = "aobListRowAct";
		else
			var outercls = (this.seen) ? "aobListRow" : "aobListRowAlert";

		alib.dom.styleSetClass(this.row, outercls);
	}
	else
	{
		var itemRow = this.row;
	}

	try 
	{
		itemRow.innerHTML = "";
	} 
	catch(e) 
	{ 
		// Ignore error
	}
    
	itemRow.revision = this.revision;
	if (this.bcls.viewmode == "details")
	{
		var innerCon = alib.dom.createElement("td", this.row);
		
		if( this.bcls.mobile && this.bcls.obj_type!="comment" && this.bcls.obj_type!="activity" && this.bcls.obj_type!="status_update")
		{
			this.printObjectMobileRow(innerCon, this.objData);
		}
		else
		{
			switch (this.bcls.obj_type)
			{
			case 'comment':
				this.renderer = new AntObjectBrowser_Item_Comment(this, innerCon, this.objData);
				break;
	
			case 'notification':
				this.renderer = new AntObjectBrowser_Item_Notification(this, innerCon, this.objData);
				break;

			case 'activity':
				this.renderer = new AntObjectBrowser_Item_Activity(this, innerCon, this.objData);
				break;

			case 'status_update':
				this.renderer = new AntObjectBrowser_Item_StatusUpdate(this, innerCon, this.objData);
				break;
	
			case 'email_thread':

				this.printObjectEmlThreadRow(innerCon, this.objData);
				break;
	
			case 'email_message':
				this.printObjectEmlMessageRow(innerCon, this.objData);
				break;
	
			case 'contact_personal':
				this.printObjectContactRow(innerCon, this.objData);
				break;
			
			default:
				this.printObjectGenRow(innerCon, this.objData);
				break;
			}
		}
	}
	else // table
	{
		this.tbody = tbody;

		// Check if item is a browse/folder entry
		if (this.objData.isBrowse)
			this.printTableRowBrowse(itemRow, this.objData);
		else
			this.printTableRow(itemRow, this.objData);
	}

	//this.onMouseDownAct = function(event) { this.wasmouseup = false; this.openAct(event); };
	//this.onMouseUpAct = function() { ALib.m_debug = true; ALib.trace("Mouse Up Orig"); };

	if (this.objData.security.view)
	{
		this.openAct = function(event)
		{
			this.bcls.selectObjectRow(this.id, event);
			this.seen = true;
			this.onSetSeen();
		}
	}
	else
	{
		this.openAct = function(event)
		{
			ALib.Dlg.messageBox("You do not have permissions to view this object!");
		}
	}
}

/**
 * Check if this item is in a selected state
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @return {bool} true if the item is selected, false if it is not
 */
AntObjectBrowser_Item.prototype.selected = function()
{
	return this.fIsSelected;
}

/**
 * Toggle items selected state
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {bool} select True to set this to selected
 */
AntObjectBrowser_Item.prototype.select = function(select)
{
	this.checkbox.checked = select;

	if (select)
	{
		alib.dom.styleSetClass(this.row, "aobListRowAct");
		this.fIsSelected = true;
	}
	else
	{
		alib.dom.styleSetClass(this.row, (this.seen) ? "aobListRow" : "aobListRowAlert");
		this.fIsSelected = false;
	}
}

/**
 * Update this item to be seen - usually used to set icon
 *
 * @public
 * @this {AntObjectBrowser_Item}
 */
AntObjectBrowser_Item.prototype.onSetSeen = function()
{
}

/**
 * Update this item to be seen - usually used to set icon
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {Object} objData Object with properties for all the values in CAntObject
 */
AntObjectBrowser_Item.prototype.update = function(objData)
{
	this.objData = objData;
	this.revision = objData.revision;

	if (this.renderer && this.renderer.update)
		this.renderer.update(objData);
	else if (this.listCon)
		this.print(this.listCon);
	else
		throw "Item " + objData.id + " has not been printed yet";
}

/**
 * Move an item up or down in the list
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {number} toind The index in the list where we should move this item to
 */
AntObjectBrowser_Item.prototype.move = function(toind)
{
	if (this.listCon.childNodes.length > toind)
	{
		// Find the current position
		var fromind = -1; 
		for (var i = 0; i < this.bcls.objectList.length; i++)
		{
			if (this.bcls.objectList[i].id == this.id)
			{
				fromind = i;
			}
		}

		// Move in the list array
		if (fromind != -1 && toind != fromind)
		{
			this.bcls.objectList.splice(fromind, 1);
			this.bcls.objectList.splice(toind, 0, this);
		}

		// Remove in the DOM tree - should be the same fromind but just in case we get it again
		if (toind != fromind)
		{
			try
			{
				var node = this.listCon.removeChild(this.row);;
			}
			catch(e) {}

			if (node)
			{
				// Move in the listCon
				if (toind >= (this.listCon.childNodes.length-1))
				{
					this.listCon.appendChild(node);
				}
				else
				{
					// toind +1 is safe to assume because of the check above
					// if toind > list lenght then it will append
					this.listCon.insertBefore(node, this.listCon.childNodes[toind + 1]); 
				}
			}
		}

	}
}

/**
 * Remove object from the list
 *
 * @public
 * @this {AntObjectBrowser_Item}
 */
AntObjectBrowser_Item.prototype.remove = function()
{
	if (!this.listCon)
		return;

	try
	{
		//this.listCon.removeChild(this.row);
		alib.fx.fadeOut(this.row, function() { if (this.parentNode) this.parentNode.removeChild(this);}, 500);
	}
	catch(e) { alert(e); }

	var iDeletedInd = -1;

	for (var i = 0; i < this.bcls.objectList.length; i++)
	{
		if (this.bcls.objectList[i].id == this.id)
		{
			this.bcls.objectList.splice(i, 1);
			iDeletedInd = i;
		}
	}

	// Load top object of selected
	if (this.bcls.preview)
	{
		if (this.id == this.bcls.curObjLoaded)
		{
			var pullId = null;
			if (this.bcls.objectList.length > iDeletedInd)
				pullId = this.bcls.objectList[iDeletedInd].id;
			this.bcls.selectObjectRow(pullId);
		}
	}
}

/**
 * onmousedown action for opening object - over ridden
 *
 * @public
 * @this {AntObjectBrowser_Item}
 */
AntObjectBrowser_Item.prototype.onMouseDownAct = function() {}

/**
 * onmouseup action for opening object - over ridden
 *
 * @public
 * @this {AntObjectBrowser_Item}
 */
AntObjectBrowser_Item.prototype.onMouseUpAct = function() {}

/**
 * Event to fire when opening object
 *
 * @public
 * @this {AntObjectBrowser_Item}
 */
AntObjectBrowser_Item.prototype.openAct = function() {}

/**
 * Get name or label for this entry
 *
 * For now we will just look for common names for label fields. In the
 * future we might want to utilize the titleField setting for objets.
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @return {string} Name or label of this item
 */
AntObjectBrowser_Item.prototype.getName = function() 
{
	for (var ind in this.objData)
	{
		switch (ind)
		{
		case 'full_name':
		case 'name':
		case 'title':
			if (this.objData[ind])
				return this.objData[ind];
			break;
		}
	}

	return this.id;
}

/**
 * Print comment row. These look different than normal objects.
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {number} id The unique id of this comment
 * @param {number} user_id The creator of this comment
 * @param {string} user_name The textual name of user_id
 * @param {string} ts_entered Textual representation of the timestamp
 * @param {string} comment The text of this comments
 * @param {string} notified Is set to the reciepient of this comment
 * @param {string} sent_by The object reference of who sent this - can be anything like a customer or anonymous
 * @param {string} sent_by_lbl If an object, like customer, then this will contain the name/label of the object
AntObjectBrowser_Item.prototype.printObjectCommentRow = function(dv, id, user_id, user_name, ts_entered, comment, notified, sent_by, sent_by_lbl)
{
	var comment = comment;
	var re = new RegExp ("\n", 'gi') ;
	comment = comment.replace(re, "<br />");

	alib.dom.styleSetClass(dv, "aobListRowComment");
	dv.objid = id;

	var tbl = alib.dom.createElement("table", dv);
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	alib.dom.styleSet(tbl, "width", "100%");
	tbl.setAttribute("cellPadding", "0");
	tbl.setAttribute("cellSpacing", "0");
	var tbody = alib.dom.createElement("tbody", tbl);
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";

	// Print image
	var img_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(img_cell, "width", "55px");
	alib.dom.styleSet(img_cell, "text-align", "center");
	img_cell.aid = id;
	img_cell.cls = this.bcls;
	if (user_id)
		this.bcls.setUserImage(img_cell, user_id, "");

	// Print name
	var name_cell = alib.dom.createElement("td", row);
	name_cell.aid = id;
	name_cell.cls = this.bcls;
	var buf = "<span class='aobjListBold'>" + ((sent_by_lbl)?sent_by_lbl:user_name) + "</span>&nbsp;&nbsp;";
	if (notified)
	{
		buf += ">&nbsp;&nbsp;";

		var recipients = notified.split(",");

		for (var i = 0; i < recipients.length; i++)
		{
			var recipient = recipients[i].trim();

			if (i > 0) buf += ",&nbsp;";
			var notrec = getNotifiedParts(recipient);
			buf += notrec.name;

			// Add to comments array for future comments
			this.bcls.addCommentsMember(recipient);
		}

		buf += "&nbsp;&nbsp;";
	}
	buf += "<span class='aobListItal'>@ " + ts_entered + "</span>";
	buf += "<div style='margin-top:3px;width:98%;'>" + comment + "</div>";
	name_cell.innerHTML =  buf;

	// Delete image
	var act_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(act_cell, "width", "20px");
	alib.dom.styleSet(act_cell, "text-align", "center");
	alib.dom.styleSet(act_cell, "cursor", "default");
	var dellink = alib.dom.createElement("span", act_cell);
	alib.dom.styleSet(dellink, "cursor", "pointer");
	alib.dom.styleSet(dellink, "display", "none");
	dellink.oid = id;
	dellink.bcls = this.bcls; // pass reference to browser class
	dellink.onclick = function() { this.bcls.deleteObjects(this.oid); }
	dellink.innerHTML = "<img src='/images/icons/delete_10.png' />";
	alib.events.listen(row, "mouseover", function(evnt) { alib.dom.styleSet(dellink, "display", "inline"); });
	alib.events.listen(row, "mouseout", function(evnt) { alib.dom.styleSet(dellink, "display", "none"); });

	var sel_obj = alib.dom.createElement("input");
	sel_obj.type = "checkbox";
	sel_obj.cls = this.bcls;
	sel_obj.value = id;

	// Add user to notify for next comment
	if (user_id && user_name)
		this.bcls.addCommentsMember("user:" + user_id + "|" + user_name);
	else if (sent_by)
		this.bcls.addCommentsMember(sent_by);
}
 */

/**
 * Print activity row. These look different than normal objects.
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
AntObjectBrowser_Item.prototype.printObjRowActivity = function(dv, objData)
{
	var hascomments = objData.hascomments;

	var id = objData.id;
	var user_name = objData.user_id.value;
	var user_id = objData.user_id.key;

	var name = objData.name;
	var type = objData.type_id.value;
	var direction = objData.direction;
	var notes = objData.notes;
	var ts_entered = objData.ts_entered;
	var obj_reference = "";
	if (objData.obj_reference && objData.obj_reference.key)
		var obj_reference = objData.obj_reference.key;

	// Setup table
	alib.dom.styleSetClass(this.row, "aobListRowNoSelect");
	alib.dom.styleSet(dv, "cursor", "default");
	//alib.dom.styleSet(dv, "padding-bottom", "5px");
	alib.dom.styleSet(dv, "padding", "10px 0px 10px 0px");
	dv.objid = id;

	var tbl = alib.dom.createElement("table", dv);
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	alib.dom.styleSet(tbl, "width", "100%");
	tbl.setAttribute("cellPadding", "0");
	tbl.setAttribute("cellSpacing", "0");
	var tbody = alib.dom.createElement("tbody", tbl);
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";

	// Print image
	var img_cell = alib.dom.createElement("td", row);
	img_cell.setAttribute("rowSpan", 2);
	alib.dom.styleSet(img_cell, "width", "60px");
	alib.dom.styleSet(img_cell, "text-align", "center");
	alib.dom.styleSet(img_cell, "cursor", "pointer");
	img_cell.aid = id;
	img_cell.cls = this.bcls;
	this.bcls.setUserImage(img_cell, user_id, "");

	// Header: print user name
	var name_cell = alib.dom.createElement("td", row);
	name_cell.aid = id;
	name_cell.cls = this.bcls;
	var userNameCon = alib.dom.createElement("a", name_cell, user_name);
	userNameCon.href = "javascript:void(0);";
	alib.dom.styleSetClass(userNameCon, "aobjListBold");
	alib.dom.createElement("span", name_cell, "&nbsp;&nbsp;");
	userNameCon.traceId = user_id;
	if (user_id)
		AntObjectInfobox.attach("user", user_id, userNameCon);

	// Header: type and object name/link
	var lbl = "";
	if (type == null) type = "";
	var typeDesc = type + "&nbsp;&nbsp;&#9654;&nbsp;&nbsp;";

	switch(type.toLowerCase())
	{
	case 'email':
		if (direction == 'i')
			lbl = "Received " + name;
		else
			lbl = "Sent " + name;
		break;
	case 'phone call':
		if (direction == 'i')
			typeDesc = "Inbound " + type + " ";
		else
			typeDesc = "Outbound " + type + " ";
		lbl = name;
	case 'note':
		// No label needed for comments or notes
		break;
	case 'comment':
		typeDesc = "Commented on ";
		lbl = name;
		break;
	case 'status update':
		typeDesc = "Status Update";
		lbl = name;
		break;
	default:
		lbl = name;
		break;
	}

	if (lbl != "")
	{
		var titleCon = alib.dom.createElement("span", name_cell);
		var parts =  obj_reference.split(":");
		var titleConLbl = null;
		if (parts.length > 1 && parts[0]!='status_update')
		{
			titleCon.innerHTML = "::&nbsp;&nbsp;" + typeDesc;

			var titleConLbl = alib.dom.createElement("a", name_cell);
			titleConLbl.href = "javascript:void(0);";
			titleConLbl.itemcls = this;
			titleConLbl.obj_type = parts[0];
			titleConLbl.oid = parts[1];
			titleConLbl.onclick = function(event) 
			{ 
				this.itemcls.bcls.loadObjectForm(this.oid, null, null, this.obj_type); 
			};
			titleConLbl.traceId = parts[1];

			// Add infobox
			AntObjectInfobox.attach(parts[0], parts[1], titleConLbl);
		}
		else
		{
			titleCon.innerHTML = "::&nbsp;&nbsp;" + typeDesc + "&nbsp;&nbsp;";
			//var titleConLbl = alib.dom.createElement("span", name_cell);
		}

		if (titleConLbl)
			titleConLbl.innerHTML = lbl;
	}

	// Notes
	var notesdv = alib.dom.createElement("div", name_cell);
	alib.dom.styleSet(notesdv, "margin", "5px 5px 5px 0px");
	if (type == "Status Update")
	{
		notes = notes.replace(/\n/g, '<br />');
	}
	else
	{
		alib.dom.styleSet(notesdv, "overflow", "hidden");
		alib.dom.styleSet(notesdv, "white-space", "nowrap");
		alib.dom.styleSet(notesdv, "max-height", "100px");
		alib.dom.styleSet(notesdv, "text-overflow", "ellipsis");
	}
	notesdv.innerHTML = notes;

	// Timestamp
	var timesp = alib.dom.createElement("div", name_cell);
	alib.dom.styleSetClass(timesp, "aobListItal");
	timesp.innerHTML = ts_entered;

	// Delete image & checkbox
	var act_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(act_cell, "width", "20px");
	alib.dom.styleSet(act_cell, "cursor", "default");
	var dellink = alib.dom.createElement("span", act_cell);
	alib.dom.styleSet(dellink, "cursor", "pointer");
	alib.dom.styleSet(dellink, "display", "none");
	dellink.oid = id;
	dellink.bcls = this.bcls; // pass reference to browser class
	dellink.onclick = function() { this.bcls.deleteObjects(this.oid); }
	dellink.innerHTML = "<img src='/images/icons/delete_10.png' />";
	alib.events.listen(row, "mouseover", function(evnt) { alib.dom.styleSet(dellink, "display", "inline"); });
	alib.events.listen(row, "mouseout", function(evnt) { alib.dom.styleSet(dellink, "display", "none"); });

	// Comments
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";
	var comm_cell = alib.dom.createElement("td", row);
	if (type != "Comment" && obj_reference)
	{
		var parts =  obj_reference.split(":");

		var ob = new AntObjectBrowser("comment");
		ob.limit = 8; // Limit size
		ob.setFilter("obj_reference", obj_reference);
		// Add user
		if (user_id && user_name)
			ob.addCommentsMember("user:" + user_id + "|" + user_name);
		ob.printComments(comm_cell, obj_reference, null, (hascomments==1)?false:true);
	}
}
 */

/**
 * Print email thread detail row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectEmlThreadRow = function(dv, objData)
{
	var id = objData.id;
	var senders = objData.senders;
	var time_updated = objData.time_updated;
	var time_delivered = objData.ts_delivered;
	var subject = objData.subject;
	var num_messages = objData.num_messages;
	if (!num_messages) num_messages = 1;
	var num_attachments = objData.num_attachments;
	var flag_flagged = objData.f_flagged;;

	// Change email icon if viewed
	/*
	this.onSetSeen = function()
	{
		this.iconCon.innerHTML = "<img src='/images/icons/email_16_opened.png' border=''>";
	}
	*/

	this.seen = true;
	if (objData.f_seen=='f' || objData.f_seen==false)
		this.seen = false;
        
	if (objData.flag_seen=='f' || objData.flag_seen==false)
		this.seen = false;
    
	dv.oid = id;
	dv.objid = id;
	// Disabling for now - joe
	//DragAndDrop.registerDragable(dv, null, "dzNavbarDrop_" + this.bcls.obj_type);
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "padding", "3px");
	
	// Set drag icon
	var dv_icon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv_icon, "statusAlert");
	dv_icon.innerHTML = "Move Item(s)";
	// Disabling for now - joe
	//DragAndDrop.setDragGuiCon(dv, dv_icon, 15, 15);

	var tbl = alib.dom.createElement("table", dv);
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	var tbody = alib.dom.createElement("tbody", tbl);

	var tr_main = alib.dom.createElement("tr", tbody);
	
	// Display checkbox & icon
	this.iconCon = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(this.iconCon, "width", "23px");
	alib.dom.styleSet(this.iconCon, "text-align", "center");
	this.iconCon.vAlign = "middle";
	this.iconCon.rowSpan = 2;
	if (!this.bcls.hideCheckbox)
		this.iconCon.appendChild(this.checkbox);

	// Display Details
	// ---------------------------------------------------------
	
	// Senders
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "aobjListBold");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) { this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) { this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = senders;

	// Num messages in thread
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "width", "10px");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) {this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) {this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = "("+num_messages+")";

	// Time sent
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "DynDivTopright");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "width", "65px");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) {this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) {this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = emailListFormatTimeDel(time_delivered);
	
	// Subject
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.colSpan = 3;
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) { this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) { this.m_itemcls.onMouseUpAct(event); };
	if (num_attachments!="" && num_attachments!="0"  && num_attachments!=null)
		td.innerHTML = "<img border='0' style='float:right;' src='/images/icons/clip.gif'>"+subject;
	else
		td.innerHTML = subject;

	// Flag
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(td, "width", "12px");
	td.vAlign = "middle";
	td.rowSpan = 2;
	var img = alib.dom.createElement("img", td);
	alib.dom.styleSet(img, "cursor", "pointer");
	img.border = '0';
	img.src = '/images/icons/flag_'+((flag_flagged=='t')?'on':'off')+'_12.png';
	img.tid = id;
	img.flagged = (flag_flagged=='t')?true:false;
	img.onclick = function()
	{		
        var functName = (this.flagged)?'markUnflagged':"markFlag";
                                
        ajax = new CAjax('json');
        ajax.img = this;        
        ajax.onload = function(ret)
        {
            if (this.img.flagged)
            {
                this.img.flagged = false;
                this.img.src = '/images/icons/flag_off_12.png';
            }
            else
            {
                this.img.flagged = true;
                this.img.src = '/images/icons/flag_on_12.png';
            }
        };
        ajax.exec("/controller/Email/" + functName, 
                    [["obj_type", "email_thread"], ["objects[]", this.tid]]);
	}
}

/**
 * Print email thread detail row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectEmlMessageRow = function(dv, objData)
{
	var id = objData.id;
	var senders = objData.sent_from;
	var send_to = objData.send_to;
	var time_updated = objData.message_date;
	var subject = objData.subject;
	var num_attachments = objData.num_attachments;
	var flag_flagged =objData.flag_flagged;

	var fSeen = false;
	if (objData.flag_seen == 't' ||  objData.flag_seen == true)
		fSeen = true;
	this.seen = fSeen;

	alib.dom.styleSetClass(dv, outercls);
	dv.oid = id;
	dv.objid = id;
	// Disabling for now - joe
	//DragAndDrop.registerDragable(dv, null, "dzNavbarDrop_" + this.bcls.obj_type);
	//this.bcls.objectRows[this.bcls.objectRows.length] = dv;
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "padding", "3px");
	
	// Set drag icon
	var dv_icon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv_icon, "statusAlert");
	dv_icon.innerHTML = "Move Item(s)";
	// Disabling for now - joe
	//DragAndDrop.setDragGuiCon(dv, dv_icon, 15, 15);

	var tbl = alib.dom.createElement("table", dv);
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	var tbody = alib.dom.createElement("tbody", tbl);

	var tr_main = alib.dom.createElement("tr", tbody);
	
	// Display checkbox & icon
	this.iconCon = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(this.iconCon, "width", "23px");
	alib.dom.styleSet(this.iconCon, "text-align", "center");
	this.iconCon.innerHTML = "<img src='/images/icons/email_16_"+((fSeen)?"opened":"unopened")+".png' border=''>";
	this.iconCon.vAlign = "middle";
	this.iconCon.rowSpan = 2;
	//if (!this.bcls.hideCheckbox)
		//td.appendChild(this.checkbox);

	// Display Details
	// ---------------------------------------------------------
	
	// Send to 
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "aobjListBold");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) {this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) {this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = send_to;

	// Time sent
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "DynDivTopright");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "width", "55px");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) {this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) {this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = emailListFormatTimeDel(time_updated);
	
	// Subject
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.colSpan = 2;
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) { this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) { this.m_itemcls.onMouseUpAct(event); };
	if (num_attachments!="" && num_attachments!="0")
		td.innerHTML = "<img border='0' style='float:right;' src='/images/icons/clip.gif'>"+subject;
	else
		td.innerHTML = subject;

	// Flag
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(td, "width", "12px");
	td.vAlign = "middle";
	td.rowSpan = 2;
	var img = alib.dom.createElement("img", td);
	alib.dom.styleSet(img, "cursor", "pointer");
	img.border = '0';
	img.src = '/images/icons/flag_'+((flag_flagged=='t')?'on':'off')+'_12.png';
	img.tid = id;
	img.flagged = (flag_flagged=='t')?true:false;
	img.onclick = function()
	{   
        var functName = (this.flagged)?'markUnflagged':"markFlag";
                                
        ajax = new CAjax('json');
        ajax.img = this;
        ajax.onload = function(ret)
        {
            if (this.img.flagged)
            {
                this.img.flagged = false;
                this.img.src = '/images/icons/flag_off_12.png';
            }
            else
            {
                this.img.flagged = true;
                this.img.src = '/images/icons/flag_on_12.png';
            }
        };
        ajax.exec("/controller/Email/" + functName, 
                    [["obj_type", "email_message"], ["objects[]", this.tid]]);
	}
}

/**
 * Print generic object row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectGenRow = function(dv, objData)
{
	var id = objData.id;

	if (this.allow_open)
	{
		var openFunc = function(event)
		{
			this.m_browseclass.selectObjectRow(this.m_id, event);
		}
	}
	else
	{
		var openFunc = function()
		{
			ALib.Dlg.messageBox("You do not have permissions to view this object!");
		}			
	}

	dv.objid = id;
	// Disabling for now - joe
	//DragAndDrop.registerDragable(dv, null, "dzNavbarDrop_" + this.bcls.obj_type);

	// Set drag icon
	var dv_icon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv_icon, "statusAlert");
	dv_icon.innerHTML = "Move Item(s)";
	// Disabling for now - joe
	//DragAndDrop.setDragGuiCon(dv, dv_icon, 15, 15);

	//this.bcls.objectRows[this.bcls.objectRows.length] = dv;
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "padding", "3px");

	var tbl = alib.dom.createElement("table", dv);
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	var tbody = alib.dom.createElement("tbody", tbl);


	var tr = alib.dom.createElement("tr", tbody);
	
	// Display checkbox
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "width", "23px");
	td.vAlign = "middle";
	td.rowSpan = 2;
	var sel_obj = null;
	// See if checkbox already exists
	/*
	for (var i = 0; i < this.bcls.chkBoxes.length; i++)
	{
		if (this.bcls.chkBoxes[i].value == id)
			sel_obj = this.bcls.chkBoxes[i];
	}
	if (!sel_obj)
	{
		sel_obj = alib.dom.createElement("input");
		sel_obj.type = "checkbox";
		sel_obj.cls = this.bcls;
		sel_obj.value = id;
		this.bcls.chkBoxes[this.bcls.chkBoxes.length] = sel_obj;
	}
	dv.chkbox = sel_obj;
	if (!this.bcls.hideCheckbox)
		td.appendChild(sel_obj);
		*/

	// Display Details
	// ---------------------------------------------------------
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "aobjListBold");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onmousedown = openFunc;
	if (this.bcls.view_fields.length)
	{
		var val = objData[this.bcls.view_fields[0].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else
			{
				buf = val;
			}
		}
		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "DynDivTopright");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onclick = openFunc;
	if (this.bcls.view_fields.length > 1)
	{
		var val = objData[this.bcls.view_fields[1].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else
			{
				buf = val;
			}
		}
		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
	
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.colSpan = 2;
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onclick = openFunc;
	if (this.bcls.view_fields.length > 2)
	{
		var val = objData[this.bcls.view_fields[2].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else
			{
				buf = val;
			}
		}
		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
}

/**
 * Print mobile object row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectMobileRow = function(dv, objData)
{
	return this.printObjectGenRow(dv, objData);

	var icon = objData.icon;
	var title = "";
	var type = this.bcls.obj_type;
	var name = objData.name;

	var id = objData.id;

	if (!objData.security.view)
	{
		return false;
	}

	var fSeen = true;
	if (objData.f_seen=='f' || objData.f_seen==false)
		fSeen = false;
	if (objData.flag_seen=='f' || objData.flag_seen==false)
		fSeen = false;

	switch( type)
	{
	case "activity":
		var hascomments = (typeof objData.hascomments != "undefined") ? objData.hascomments : -1;
		var user_name = objData.user_id.value;
		var user_id = objData.user_id.key;
		var act_type = objData.type_id.value;
		var direction = objData.direction;
		var notes = objData.notes;
		var ts_entered = objData.ts_entered;
		
		var buf = user_name + "&nbsp;&nbsp;";
		if (act_type == "Email")
		{
			if (direction == 'i')
				buf += "Received ";
			else
				buf += "Sent ";
		}
	
		buf += "::&nbsp;&nbsp;";
		var parts =  obj_reference.split(":");
		buf += act_type;
		buf += "&nbsp;&nbsp;-&nbsp;&nbsp;";
		
		title =  buf + "&nbsp;&nbsp;@ " + ts_entered;
		
		break;
		
	case "email_thread":
		var senders = objData.senders;
		var time_updated = cobjData.time_updated;
		var subject = objData.subject;
		var num_messages = objData.num_messages;
		if (!num_messages) num_messages = 1;
		var num_attachments = objData.num_attachments;
		var flag_flagged = objData.f_flagged;

		icon = "/images/icons/email_16_"+((fSeen)?"opened":"unopened")+".png";
		title = "From: "+unescape(senders)+" / "+ unescape(subject)+emailListFormatTimeDel(unescape(time_updated))+"("+num_messages+")";
		
		break;
		
	case "email_message":
		var senders = objData.sent_from;
		var send_to = objData.send_to;
		var time_updated = objData.message_date;
		var subject = objData.subject;
		var num_attachments = objData.num_attachments;
		var flag_flagged = objData.flag_flagged;
		
		icon = "/images/icons/email_16_"+((fSeen)?"opened":"unopened")+".png";
		title = "To: "+unescape(send_to)+" / "+ unescape(subject)+emailListFormatTimeDel(unescape(time_updated));
		
		break;
		
	case 'contact_personal':
		var phone_cell = objData.phone_cell;
		var phone_home = objData.phone_home;
		var phone_work = objData.phone_work;
		var email_default = objData.email_default;
		var image_id = objData.image_id.key;
		var phone = (phone_cell) ? phone_cell : (phone_work) ? phone_work : phone_home;
				
		title = name + " / " + email_default;
		
		break;
	
	case "comment":
	default:
		if (this.bcls.view_fields.length)
		{
			var val = objData[this.bcls.view_fields[0].fieldName];
			var buf = "";
			if (val)
			{
				if (val instanceof Array) // mval
				{
					var buf = "";
					for (var m = 0; m < val.length; m++)
					{
						if (buf) buf += ", ";
						buf += val[m].value;
					}
				}
				else
				{
					buf = val;
				}
			}
			title = buf;
		}
		break;
	
	}


	var entry = alib.dom.createElement("article", dv);
	alib.dom.styleSetClass(entry, "listItem");
	entry.m_itemcls = this;
	entry.onclick = function(event) { this.m_itemcls.openAct(event); };
	var icnHtm = (String(icon).length>0 ) ? "<span class='icon'><img src='"+icon+"' /></span>" : "";
	entry.innerHTML = icnHtm+ "<h3>"+title+"</h3></a>";


	/*
	entry.view = this.bcls.antView;
	entry.name = type+"_"+id;
	var einnerHTML = "<a behavior='selectable' href=\"#"+this.bcls.antView.getPath()+"/"+type+":"+id+"\">";

	if( String(icon).length>0 )
	{
		einnerHTML += "<span class='icon'><img src='"+icon+"' /></span>";
	}
	einnerHTML += "<h3>"+title+"</h3></a>";
	entry.innerHTML = einnerHTML;
	*/
}

/**
 * Print contact detail row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectContactRow = function(dv, objData)
{
	var id = objData.id;
	var name = objData.name;
	var phone_cell = objData.phone_cell;
	var phone_home = objData.phone_home;
	var phone_work = objData.phone_work;
	var email_default = (objData.email) ? objData.email : objData.email2;
	var image_id = objData.image_id.key;
	var phone = (phone_cell) ? phone_cell : (phone_work) ? phone_work : phone_home;

	dv.oid = id;
	dv.objid = id;
	//DragAndDrop.registerDragable(dv, null, "dzNavbarDrop");
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "padding", "3px");
	
	// Set drag icon
	var dv_icon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv_icon, "statusAlert");
	dv_icon.innerHTML = "Move Item(s)";
	//DragAndDrop.setDragGuiCon(dv, dv_icon, 15, 15);

	var tbl = alib.dom.createElement("table", dv);
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	var tbody = alib.dom.createElement("tbody", tbl);

	var tr_main = alib.dom.createElement("tr", tbody);
	
	// Display checkbox & icon
	this.iconCon = alib.dom.createElement("td", tr_main);
	alib.dom.styleSet(this.iconCon, "width", "50px");
	alib.dom.styleSet(this.iconCon, "text-align", "center");
	if (image_id)
		this.iconCon.innerHTML = "<img src='/files/images/"+image_id+"/48/33' border=''>";
	this.iconCon.vAlign = "middle";
	this.iconCon.rowSpan = 2;
	//if (!this.bcls.hideCheckbox)
		//td.appendChild(this.checkbox);

	// Display Details
	// ---------------------------------------------------------
	
	// Name
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "aobjListBold");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) { this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) { this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = unescape(name);

	// Phone
	var td = alib.dom.createElement("td", tr_main);
	alib.dom.styleSetClass(td, "DynDivTopright");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	alib.dom.styleSet(td, "width", "100px");
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) {this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) {this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = unescape(phone);
	
	// Email
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.colSpan = 2;
	td.m_id = id;
	td.m_itemcls = this;
	td.onclick = function(event) { this.m_itemcls.openAct(event); };
	//td.onmousedown = function(event) { this.m_itemcls.onMouseDownAct(event); };
	//td.onmouseup = function(event) { this.m_itemcls.onMouseUpAct(event); };
	td.innerHTML = unescape(email_default);
}

/**
 * Print table row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {TR} rw The row for this object
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printTableRow = function(rw, objData)
{
	var id = objData.id;

	//var sel_obj = alib.dom.createElement("input");
	//sel_obj.type = "checkbox";
	//sel_obj.cls = this.bcls;
	//sel_obj.value = id;
	//this.chkBoxes[this.chkBoxes.length] = sel_obj;

	if (!this.bcls.hideCheckbox)
	{
		var td = alib.dom.createElement("td", rw);
		alib.dom.styleSet(td, "text-align", "center");
		alib.dom.styleSet(td, "padding-right", "5px");
        
        if(objData.id == this.selectedObjId)
            this.checkbox.checked = true;
            
		td.appendChild(this.checkbox);
		//rw.addCell(this.checkbox);
	}

	for (var j = 0; j < this.bcls.view_fields.length; j++)
	{
		var val = objData[this.bcls.view_fields[j].fieldName];
		var field = this.bcls.mainObject.getFieldByName(this.bcls.view_fields[j].fieldName);

		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else if (val instanceof Object)
			{
				if (val.value)
					buf = val.value;
				else
					buf = val.key;
			}
			else if ("bool" == field.type && (val === true || val == 't'))
			{
				buf = "Yes";
			}
			else if ("bool" == field.type && (val === false || val == 'f'))
			{
				buf = "No";
			}
			else
			{
				buf = val;
			}
		}

		if (!buf)
			buf = "&nbsp;";

		// Make the name/title field bold
		if (this.bcls.mainObject.nameField == this.bcls.view_fields[j].fieldName)
		{
			/*
			if (objData.image_id && objData.image_id.key)
				buf = "<img src='/antfs/images/" + objData.image_id.key + "/16/16'> " + buf;
			else if (objData.iconName)
				buf = "<img src='/images/icons/objects/" + objData.iconName+ "_16.png'> " + buf;
				*/

			if (objData.iconPath)
				buf = "<img src='" + objData.iconPath+ "'> " + buf;

			buf = "<span class=\"aobjListBold\">" + buf + "</span>";
		}

		var td = alib.dom.createElement("td", rw);
		td.m_itemcls = this;
		td.onclick = function(event) { this.m_itemcls.openAct(event); };
		//td.onmousedown = function(event) { this.innerHTML = "DOWN"; this.m_itemcls.onMouseDownAct(event); };
		//td.onmouseup = function(event) { this.innerHTML = "UP"; this.m_itemcls.onMouseUpAct(event); };
		td.innerHTML = buf;
	}
}

/**
 * Print generic object row
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printObjectGenRow = function(dv, objData)
{
	var id = objData.id;

	if (this.allow_open)
	{
		var openFunc = function(event)
		{
			this.m_browseclass.selectObjectRow(this.m_id, event);
		}
	}
	else
	{
		var openFunc = function()
		{
			ALib.Dlg.messageBox("You do not have permissions to view this object!");
		}			
	}

	dv.objid = id;
	DragAndDrop.registerDragable(dv, null, "dzNavbarDrop_" + this.bcls.obj_type);

	// Set drag icon
	var dv_icon = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv_icon, "statusAlert");
	dv_icon.innerHTML = "Move Item(s)";
	DragAndDrop.setDragGuiCon(dv, dv_icon, 15, 15);

	//this.bcls.objectRows[this.bcls.objectRows.length] = dv;
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "padding", "3px");

	var tbl = alib.dom.createElement("table", dv);
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	var tbody = alib.dom.createElement("tbody", tbl);


	var tr = alib.dom.createElement("tr", tbody);
	
	// Display checkbox
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "width", "23px");
	td.vAlign = "middle";
	td.rowSpan = 2;
	var sel_obj = null;
	// See if checkbox already exists	
	for (var i = 0; i < this.bcls.chkBoxes.length; i++)
	{
		if (this.bcls.chkBoxes[i].value == id)
			sel_obj = this.bcls.chkBoxes[i];
	}
	if (!sel_obj)
	{
		sel_obj = alib.dom.createElement("input");
		sel_obj.type = "checkbox";
		sel_obj.cls = this.bcls;
		sel_obj.value = id;
		this.bcls.chkBoxes[this.bcls.chkBoxes.length] = sel_obj;
	}
	dv.chkbox = sel_obj;
	if (!this.bcls.hideCheckbox)
		td.appendChild(sel_obj);

	// Display Details
	// ---------------------------------------------------------
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "aobjListBold");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onmousedown = openFunc;
	if (this.bcls.view_fields.length)
	{
		var val = objData[this.bcls.view_fields[0].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else
			{
				buf = val;
			}
		}
		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "DynDivTopright");
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "text-align", "right");
	alib.dom.styleSet(td, "padding", "0 3px 5px 0");
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onclick = openFunc;
	if (this.bcls.view_fields.length > 1)
	{
		var val = objData[this.bcls.view_fields[1].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else if (val instanceof Object)
			{
				if (val.value)
					buf = val.value;
				else
					buf = val.key;
			}
			else if (val === true || val == 't')
			{
				buf = "Yes";
			}
			else if (val === false || val == 'f')
			{
				buf = "No";
			}
			else
			{
				buf = val;
			}
		}

		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
	
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "cursor", "pointer");
	alib.dom.styleSet(td, "overflow", "hidden");
	alib.dom.styleSet(td, "white-space", "nowrap");
	alib.dom.styleSet(td, "overflow", "hidden");
	td.colSpan = 2;
	td.m_id = id;
	td.m_browseclass = this.bcls;
	td.onclick = openFunc;
	if (this.bcls.view_fields.length > 2)
	{
		var val = objData[this.bcls.view_fields[2].fieldName];
		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else if (val instanceof Object)
			{
				if (val.value)
					buf = val.value;
				else
					buf = val.key;
			}
			else
			{
				buf = val;
			}
		}
		//td.innerHTML = buf;
		alib.dom.setText(td, buf);
	}
}

/**
 * Print generic row for browsing - like folders
 *
 * @public
 * @this {AntObjectBrowser_Item}
 * @param {DOMElement} dv The list container
 * @param {Object} objData The properties of the object represented with this row
 */
AntObjectBrowser_Item.prototype.printTableRowBrowse = function(rw, objData)
{
	var id = objData.id;

	if (!this.bcls.hideCheckbox)
	{
		var td = alib.dom.createElement("td", rw);
		alib.dom.styleSet(td, "text-align", "center");
		alib.dom.styleSet(td, "padding-right", "5px");
		td.appendChild(this.checkbox);
	}

	// This will utilize any common names so it closely represents the main object
	for (var j = 0; j < this.bcls.view_fields.length; j++)
	{
		var val = objData[this.bcls.view_fields[j].fieldName];
		var field = this.bcls.mainObject.getFieldByName(this.bcls.view_fields[j].fieldName);

		var buf = "";
		if (val)
		{
			if (val instanceof Array) // mval
			{
				var buf = "";
				for (var m = 0; m < val.length; m++)
				{
					if (buf) buf += ", ";
					buf += val[m].value;
				}
			}
			else
			{
				if (field.type == "fkey" || field.type == "object" || field.type == "alias")
					buf = val.value;
				else
					buf = val;
			}
		}

		if (!buf)
			buf = "&nbsp;";

		// Make the name/title field bold
		if (this.bcls.mainObject.nameField == this.bcls.view_fields[j].fieldName)
		{
			/*
			if (objData.image_id && objData.image_id.key)
				buf = "<img src='/antfs/images/" + objData.image_id.key + "/16/16'> " + buf;
			else if (objData.iconName)
				buf = "<img src='/images/icons/objects/" + objData.iconName + "_16.png'> " + buf;
				*/

			if (objData.iconPath)
				buf = "<img src='" + objData.iconPath+ "'> " + buf;

			buf = "<span class=\"aobjListBold\">" + buf + "</span>";

		}

		var td = alib.dom.createElement("td", rw);
		td.m_itemcls = this;
		td.onclick = function(event) { this.m_itemcls.openAct(event); };
		//td.onmousedown = function(event) { this.innerHTML = "DOWN"; this.m_itemcls.onMouseDownAct(event); };
		//td.onmouseup = function(event) { this.innerHTML = "UP"; this.m_itemcls.onMouseUpAct(event); };
		td.innerHTML = buf;
	
	}
}
