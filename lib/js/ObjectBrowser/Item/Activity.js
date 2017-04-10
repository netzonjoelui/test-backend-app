/**
 * @fileoverview Activity view
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */


/**
 * Creates an instance of AntObjectBrowser_Item_Activity
 *
 * @constructor
 * @param {Object} data Object with value for each CAntObject property
 * @param {AntObjectBrowser_Item} item Browser class
 */
function AntObjectBrowser_Item_Activity(item, dv, objData)
{
	var hascomments = objData.hascomments;

	var id = objData.id;
	var user_name = objData.user_id.value;
	var user_id = objData.user_id.key;

	var name = objData.name;
	var verb = objData.verb;
	var type = objData.type_id.value;
	var direction = objData.direction;
	var notes = objData.notes;
	var ts_entered = objData.ts_entered;
	var obj_reference = "";
	if (objData.obj_reference && objData.obj_reference.key)
		var obj_reference = objData.obj_reference.key;

	// Setup table
	alib.dom.styleSetClass(item.row, "aobListRowNoSelect");
	alib.dom.styleSetClass(dv, "aobListRowActivity");
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
	img_cell.cls = item.bcls;
	item.bcls.setUserImage(img_cell, user_id, "");

	// Header
	var name_cell = alib.dom.createElement("td", row);

	// Timestamp
	var timesp = alib.dom.createElement("div", name_cell, alib.dateTime.format(new Date(ts_entered), "ddd, MMMM d, yyyy H:mm a"));
	alib.dom.styleSetClass(timesp, "aobjListBold");
	//alib.dom.createElement("span", name_cell, " &#9654; ");

	var headerRow2 = alib.dom.createElement("div", name_cell);
	alib.dom.styleSetClass(headerRow2, "aobListItal");

	// Add user
	headerRow2.aid = id;
	headerRow2.cls = item.bcls;
	var userNameCon = alib.dom.createElement("a", headerRow2, user_name);
	userNameCon.href = "javascript:void(0);";
	//alib.dom.createElement("span", headerRow2, "&nbsp;&nbsp;");
	userNameCon.traceId = user_id;
	if (user_id)
		AntObjectInfobox.attach("user", user_id, userNameCon);

	// Header: type and object name/link
	var lbl = "";
	if (type == null) type = "";
	var typeDesc = type + "&nbsp;&nbsp;:&nbsp;&nbsp;";

	switch(type.toLowerCase())
	{
	case 'email':
		if (direction == 'i')
			lbl = "received an email ";
		else
			lbl = "sent an email ";
		break;
	case 'phone call':
		if (direction == 'i')
			typeDesc = "logged an innbound call ";
		else
			typeDesc = "logged an outbound call ";
		lbl = name;
	case 'comment':
		typeDesc = "commented on ";
		lbl = name;
		break;
	case 'status update':
		typeDesc = "added a ";
		lbl = type;
		break;
	default:
		lbl = name;

		switch (verb)
		{
		case 'create':
		case 'created':
			typeDesc = "created a new " + type.toLowerCase() + " ";
			notes = ""; // TODO: may become snippet, but for now hide
			break;
		/*
		case 'read':
			break;
		case 'updated':
			break;
		case 'deleted':
			break;
		case 'sent':
			break;
		case 'processed':
			break;
		case 'completed':
			break;
			*/

		default:
			typeDesc = verb + " ";
			break;
		}
		break;
	}

	var titleCon = alib.dom.createElement("span", headerRow2);
	var parts =  obj_reference.split(":");

	// Make sure we are not referencing this object if loaded in the context of an object form
	var sameAsContext = false;
	if (item.bcls.objectContext && parts.length > 1)
	{
		if (item.bcls.objectContext.obj_type == parts[0] && item.bcls.objectContext.id == parts[1])
			sameAsContext = true;
	}

	var titleConLbl = null;
	if (parts.length > 1 && !sameAsContext)
	{
		titleCon.innerHTML = "&nbsp;" + typeDesc;

		var titleConLbl = alib.dom.createElement("a", headerRow2);
		titleConLbl.href = "javascript:void(0);";
		titleConLbl.itemcls = item;
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
	else if (parts.length > 1 && sameAsContext)
	{
		titleCon.innerHTML = "&nbsp;" + typeDesc;

		var titleConLbl = alib.dom.createElement("span", headerRow2);
	}
	else
	{
		titleCon.innerHTML = "&nbsp;" + typeDesc + "&nbsp;&nbsp;";
		//var titleConLbl = alib.dom.createElement("span", headerRow2);
	}

	if (titleConLbl)
		titleConLbl.innerHTML = lbl;

	// Notes
	var notesdv = alib.dom.createElement("div", name_cell);
	alib.dom.styleSet(notesdv, "margin", "10px 5px 5px 0px");
	//if (type == "Status Update" || type=="Comment")
	//{
		notes = notes.replace(/\n/g, '<br />');
	//}
	
	// Attachments
	if (objData.attachments && objData.attachments.length>0)
	{
		var attCon = alib.dom.createElement("div", name_cell);
		alib.dom.styleSet(attCon, "margin-top", "5px");
		for (var i in objData.attachments)
		{
			if (objData.attachments[i].key)
			{
				var a = alib.dom.createElement("a", attCon, (objData.attachments[i].value)?objData.attachments[i].value:objData.attachments[i].key);
				a.href = "/antfs/" + objData.attachments[i].key;
				alib.dom.createElement("span", attCon, "<br />");
			}
		}
	}


	alib.dom.styleSet(notesdv, "overflow", "hidden");
	/*alib.dom.styleSet(notesdv, "white-space", "nowrap");*/
	alib.dom.styleSet(notesdv, "max-height", "500px");
	alib.dom.styleSet(notesdv, "text-overflow", "ellipsis");
	notesdv.innerHTML = notes;

	// Delete image & checkbox
	var act_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(act_cell, "width", "20px");
	alib.dom.styleSet(act_cell, "cursor", "default");
	var dellink = alib.dom.createElement("span", act_cell);
	alib.dom.styleSet(dellink, "cursor", "pointer");
	alib.dom.styleSet(dellink, "display", "none");
	dellink.oid = id;
	dellink.bcls = item.bcls; // pass reference to browser class
	dellink.onclick = function() { this.bcls.deleteObjects(this.oid); }
	dellink.innerHTML = "<img src='/images/icons/delete_10.png' />";
	alib.events.listen(row, "mouseover", function(evnt) { alib.dom.styleSet(dellink, "display", "inline"); });
	alib.events.listen(row, "mouseout", function(evnt) { alib.dom.styleSet(dellink, "display", "none"); });
}
