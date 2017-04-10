/**
 * @fileoverview Activity view
 *
 * This is not yet implemented
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */


/**
 * Creates an instance of AntObjectBrowser_Item
 *
 * @constructor
 * @param {Object} data Object with value for each CAntObject property
 * @param {AntObjectBrowser_Item} item Browser class
 */
function AntObjectBrowser_Item_Comment(item, dv, objData)
{
	var id = objData.id;
	var user_name = objData.owner_id.value;
	var user_id = objData.owner_id.key;
	var comment = objData.comment;
	var notified = objData.notified;
	var ts_entered = objData.ts_entered;
	var sent_by = objData.sent_by.key;
	var sent_by_lbl = (objData.sent_by.value) ? objData.sent_by.value : objData.sent_by.key;
	var obj_reference = objData.sent_by.key;


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
	img_cell.cls = item.bcls;
	if (user_id && (("user:" + user_id) == objData.sent_by.key || objData.sent_by.key == ""))
		item.bcls.setUserImage(img_cell, user_id, "");
	else
		img_cell.innerHTML = "<img src='/images/user_default.png' style='width:48px;' />";

	// Print name
	var name_cell = alib.dom.createElement("td", row);
	name_cell.aid = id;
	name_cell.cls = item.bcls;
	var buf = "<span class='aobjListBold'>" + ((sent_by_lbl) ? sent_by_lbl : user_name) + "</span>&nbsp;&nbsp;";
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
			item.bcls.addCommentsMember(recipient);
		}

		buf += "&nbsp;&nbsp;";
	}
	buf += "<span class='aobListItal'>@ " + alib.dateTime.format(new Date(ts_entered), "ddd, MMMM d, yyyy H:mm a") + "</span>";
	buf += "<div style='margin-top:3px;width:98%;'>" + comment + "</div>";
	name_cell.innerHTML =  buf;

	// Attachments
	if (objData.attachments && objData.attachments.length>0)
	{
		var attCon = alib.dom.createElement("div", name_cell);
		alib.dom.styleSet(attCon, "margin-top", "5px");
		for (var i in objData.attachments)
		{
			if (objData.attachments[i].key)
			{
				// Check for image
				var isImg = (objData.attachments[i].value) ? objData.attachments[i].value.match(/(gif|png|jpg|jpeg)$/i) : false;

				if (isImg)
				{
					var imgcon  = alib.dom.createElement("div", attCon);
					imgcon.innerHTML = "<img src='/antfs/images/" + objData.attachments[i].key + "' style='max-width:100%;' />";
				}

				var a = alib.dom.createElement("a", attCon, (objData.attachments[i].value)?objData.attachments[i].value:objData.attachments[i].key);
				a.href = "/antfs/" + objData.attachments[i].key;
				alib.dom.createElement("span", attCon, "<br />");
			}
		}
	}

	// Delete image
	var act_cell = alib.dom.createElement("td", row);
	alib.dom.styleSet(act_cell, "width", "20px");
	alib.dom.styleSet(act_cell, "text-align", "center");
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

	var sel_obj = alib.dom.createElement("input");
	sel_obj.type = "checkbox";
	sel_obj.cls = item.bcls;
	sel_obj.value = id;

	// Add user to notify for next comment
	if (user_id && user_name)
		item.bcls.addCommentsMember("user:" + user_id + "|" + user_name);
	else if (sent_by)
		item.bcls.addCommentsMember(sent_by);
}
