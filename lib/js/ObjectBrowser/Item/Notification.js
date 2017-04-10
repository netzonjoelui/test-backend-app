/**
 * @fileoverview Notification view
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */


/**
 * Creates an instance of AntObjectBrowser_Item_Notification
 *
 * @constructor
 * @param {Object} data Object with value for each CAntObject property
 * @param {AntObjectBrowser_Item} item Browser class
 */
function AntObjectBrowser_Item_Notification(item, dv, objData)
{
	dv.objid = objData.id;

	alib.dom.styleSetClass(item.row, "aobListRowNoSelect");
	alib.dom.styleSetClass(dv, "aobListRowActivity");
	var con = alib.dom.createElement("div", dv);

	// Add creator image
	//if (notif.getValue('owner_id') != notif.getValue('creator_id') && notif.getValue('creator_id'))
	//{
		var iconCon = alib.dom.createElement("span", con);

		var path = "/files/userimages/" + objData.creator_id.key + "/16/16";
		iconCon.innerHTML = "<img src='" + path + "' style='width:16px;' />&nbsp;";
	//}

	// User name who triggered the notification
	var whoName = (objData.owner_id.key == objData.creator_id.key) ? "You" : objData.creator_id.value;

	var nameCon = alib.dom.createElement("a", con, whoName);
	nameCon.href = "javascript:void(0);";
	AntObjectInfobox.attach("user", objData.creator_id.key, nameCon);

	// Space
	alib.dom.createElement("span", con, "&nbsp;");

	// Action name
	alib.dom.createElement("span", con, objData.name.toLowerCase() + ":");

	// Space
	alib.dom.createElement("span", con, "&nbsp;");

	// Reference link
	if (objData.obj_reference.key)
	{
		var parts =  objData.obj_reference.key.split(":");
		if (parts.length > 1)
		{
			var refLink = alib.dom.createElement("a", con);
			refLink.innerHTML = (objData.obj_reference.value == parts[1]) ? parts[0] : objData.obj_reference.value;
			refLink.href = "javascript:void(0);";
			refLink.itemcls = item;
			refLink.obj_type = parts[0];
			refLink.oid = parts[1];
			refLink.onclick = function(evt) { 
				loadObjectForm(this.obj_type, this.oid);
			};
		}
	}

	// Time
	alib.dom.createElement("span", con, "&nbsp;@ " + objData.ts_execute);
}
