/**
 * @fileoverview Status updates / wall posts
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */


/**
 * Creates an instance of AntObjectBrowser_Item_StatusUpdate
 *
 * @constructor
 * @param {AntObjectBrowser_Item} item Browser class
 * @param {DOMElement} ele The parent element to render item into
 * @param {Object} data Object with value for each CAntObject property
 */
function AntObjectBrowser_Item_StatusUpdate(item, ele, objData)
{
	/**
	 * The field data for this object
	 *
	 * @private
	 * @type {Object}
	 */
	this.objData = objData;

	/**
	 * Parent DOM container
	 *
	 * @private
	 * @type {DOMElement}
	 */
	this.ele = ele;

	/**
	 * Base item class
	 *
	 * @private
	 * @type {AntObjectBrowser_Item}
	 */
	this.item = item;

	/**
	 * Comments object browser
	 *
	 * @private
	 * @type {AntObjectBrowser}
	 */
	this.commentsBr = null;

	/**
	 * The container where the comment / note is printed
	 *
	 * @private
	 * @type {DOMElement}
	 */
	this.noteCon = null;

	// Now print the status update into the dom tree
	this.render();
}

/**
 * Render the item into the DOM
 */
AntObjectBrowser_Item_StatusUpdate.prototype.render = function()
{
	var id = this.objData.id;
	var user_name = this.objData.owner_id.value;
	var user_id = this.objData.owner_id.key;

	var comment = this.objData.comment;
	var ts_entered = this.objData.ts_entered;
	var obj_reference = "";
	if (this.objData.obj_reference && this.objData.obj_reference.key)
		var obj_reference = this.objData.obj_reference.key;
	// this.objData.notified is not needed but might be useful later

	// Setup table
	alib.dom.styleSetClass(this.item.row, "aobListRowNoSelect");
	alib.dom.styleSet(this.ele, "cursor", "default");
	alib.dom.styleSet(this.ele, "padding", "10px 0px 10px 0px");
	this.ele.objid = id;

	var tbl = alib.dom.createElement("table", this.ele);
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
	img_cell.cls = this.item.bcls;
	this.item.bcls.setUserImage(img_cell, user_id, "");

	// Header: print user name
	var name_cell = alib.dom.createElement("td", row);
	name_cell.aid = id;
	name_cell.cls = this.item.bcls;
	var userNameCon = alib.dom.createElement("a", name_cell, user_name);
	userNameCon.href = "javascript:void(0);";
	alib.dom.styleSetClass(userNameCon, "aobjListBold");
	alib.dom.createElement("span", name_cell, "&nbsp;&nbsp;");
	userNameCon.traceId = user_id;
	if (user_id)
		AntObjectInfobox.attach("user", user_id, userNameCon);

	var parts =  obj_reference.split(":");
	if (parts.length > 1 && parts[0]!='status_update')
	{
		var spacer = alib.dom.createElement("span", name_cell, "&nbsp;&nbsp;&#9654;&nbsp;&nbsp;");

		var titleConLbl = alib.dom.createElement("a", name_cell);
		titleConLbl.innerHTML = this.objData.obj_reference.value;
		titleConLbl.href = "javascript:void(0);";
		titleConLbl.itemcls = this.item;
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

	// Actual Comment
	this.noteCon = alib.dom.createElement("div", name_cell);
	alib.dom.styleSet(this.noteCon, "margin", "10px 5px 10px 0px");
	this.noteCon.innerHTML = comment.replace(/\n/g, '<br />');

	// Timestamp
	var timesp = alib.dom.createElement("div", name_cell);
	alib.dom.styleSetClass(timesp, "aobListItal");
	timesp.innerHTML = alib.dateTime.format(new Date(ts_entered), "ddd, MMMM d, yyyy H:mm a");

	// Delete image & checkbox
	var act_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(act_cell, "width", "20px");
	alib.dom.styleSet(act_cell, "cursor", "default");
	var dellink = alib.dom.createElement("span", act_cell);
	alib.dom.styleSet(dellink, "cursor", "pointer");
	alib.dom.styleSet(dellink, "display", "none");
	dellink.oid = id;
	dellink.bcls = this.item.bcls; // pass reference to browser class
	dellink.onclick = function() { this.bcls.deleteObjects(this.oid); }
	dellink.innerHTML = "<img src='/images/icons/delete_10.png' />";
	alib.events.listen(row, "mouseover", function(evnt) { alib.dom.styleSet(dellink, "display", "inline"); });
	alib.events.listen(row, "mouseout", function(evnt) { alib.dom.styleSet(dellink, "display", "none"); });

	// Comments
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";
	var comm_cell = alib.dom.createElement("td", row);
	this.commentsBr = new AntObjectBrowser("comment");
	this.commentsBr.limit = 8; // Limit size
	this.commentsBr.setFilter("obj_reference", "status_update:" + id);
	// Add user
	if (user_id && user_name)
		this.commentsBr.addCommentsMember("user:" + user_id + "|" + user_name);
	this.commentsBr.printComments(comm_cell, "status_update:" + id, null, (this.objData.num_comments>0)?false:true);
}

/**
 * The update function is used to refresh data without rebuilding the entire UI
 *
 * @param {Object} data Object with value for each CAntObject property
 */
AntObjectBrowser_Item_StatusUpdate.prototype.update = function(objData)
{
	this.objData = objData;

	// Update comment / note
	this.noteCon.innerHTML = this.objData.comment.replace(/\n/g, '<br />');

	if (this.commentsBr)
		this.commentsBr.refresh();
}
