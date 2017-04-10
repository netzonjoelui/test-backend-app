/**
 * @fileoverview Notification manager class used to handle notification objects in netric
 */

/**
 * Constructor
 */
function NotificationMan()
{
	/**
	 * Array of notifications
	 *
	 * @var {CAntObject[]}
	 */
	this.notifications = new Array();

	/**
	 * Timer for automatic refresh
	 *
	 * @var {DOMTimer}
	 */
	this.refreshTimer = null;

	/**
	 * Icon container
	 *
	 * @var {DOMElement}
	 */
	this.iconCon = null;

	/**
	 * Outer container
	 *
	 * @var {DOMElement}
	 */
	this.outerCon = null;

	/**
	 * Container that will render the list of notifications
	 *
	 * @var {DOMElement}
	 */
	this.listCon = null;

	/**
	 * Handle to popup
	 *
	 * @var {alib.ui.Popup}
	 */
	this.popup = null;

	/**
	 * If renderd inline then an antView will be provided
	 *
	 * @var {AntView}
	 */
	this.antView = null;

	// Load current notifications
	this.getNotifications();
}

/**
 * Render into an AntView
 *
 * @param {AntView} antView Render this into an antview rather than a popup
 */
NotificationMan.prototype.renderView = function(antView)
{
	this.antView = antView;
	this.listCon = antView.con;

	this.renderList();
}

/**
 * Render into to dom tree
 *
 * @param {DOMElement} con The container to print the notifications icon into
 */
NotificationMan.prototype.anchorToEl = function(con)
{
	this.iconCon = alib.dom.createElement("a", con, "<img src='/images/icons/notification_24_off.png' style='vertical-align:middle;'>");
	this.iconCon.href = "javascript:void(0);";

	// Setup Container
	this.outerCon = alib.dom.createElement("div", document.body);
    alib.dom.styleSet(this.outerCon, "position", "absolute");
    alib.dom.styleSet(this.outerCon, "display", "none");
	alib.dom.styleSetClass(this.outerCon, "chatMessenger");

	// header container
    var header = alib.dom.createElement("div", this.outerCon);
	alib.dom.styleSetClass(header, "chatMessengerTitle");
	header.innerHTML = "Notifications";

	// List container
	this.listCon = alib.dom.createElement("div", this.outerCon);
	alib.dom.styleSetClass(this.listCon, "chatMessengerList");

	// Create Popup
	var popup = new alib.ui.Popup(this.outerCon);
	popup.anchorToEl(this.iconCon, "down");
	con.onclick = function() { popup.setVisible(); }
	this.popup = popup;

	alib.events.listen(popup, "onShow", {context:this, method:"renderList"});
}

/**
 * Render the list into the ui
 */
NotificationMan.prototype.renderList = function()
{
	if (this.listCon == null)
		return;

	this.listCon.innerHTML = "";

	if (this.notifications.length == 0)
		this.listCon.innerHTML = "There are no notifications at this time";

	for (var i in this.notifications)
	{
		var notif = this.notifications[i];

		var notRow = alib.dom.createElement("div", this.listCon);
		alib.dom.styleSetClass(notRow, "actListRow");

		if (notif.getValue('f_seen') === false)
			alib.dom.styleAddClass(notRow, "unread");
		
		notRow.not = notif;
		notRow.cls = this;
		notRow.onclick = function() {

			if (this.not.getValue("obj_reference"))
			{
				var refParts = this.not.getValue("obj_reference").split(":");
				if (refParts.length == 2)
					this.cls.loadObject(refParts[0], refParts[1]);
			}
			else
			{
				this.cls.showPopup(this.not);
			}

			// Hide popup
			if (this.cls.popup)
				this.cls.popup.setVisible();

			this.not.setValue("f_seen", true);
			this.not.save();
		}

		var buf = "";

		// If creator was not owner then add user image
		if (notif.getValue('owner_id') != notif.getValue('creator_id') && notif.getValue('creator_id'))
		{
			var path = "/files/userimages/" + notif.getValue('creator_id');
			path += "/48/48";	
			buf += "<div class='mainImage'><img src='" + path + "' style='width:48px;' /></div>";
		}

		buf += notif.getValue("description") + "<br />";
		buf += notif.getValue("ts_execute");
		buf += "<div style='clear:both;'></div>";
		notRow.innerHTML = buf;

		// Add onclick to mark notifications as read
		alib.events.listen(notRow, "click", function(evt) {
			if (evt.data.objNot.getValue("f_seen") == false)
			{
				// Refresh in two seconds to clear all notifications for this object
				// after the object form has loaded
				evt.data.cls.setRefresh(2000);

				/*
				evt.data.objNot.setValue("f_seen", true);
				evt.data.objNot.cbData.cls = evt.data.cls;
				evt.data.objNot.onsave = function() {
					this.cbData.cls.updateDisplays(); // Make sure title gets updated
				}
				evt.data.objNot.save();

				alib.dom.styleRemoveClass(evt.data.row, "unread");
				*/
			}
		}, {objNot:notif, row:notRow, cls:this});
	}
}

/**
 * Get notifications from server
 */
NotificationMan.prototype.getNotifications = function(onlyNew)
{
	if (!onlyNew)
		this.notifications = new Array();

	var list = new AntObjectList("notification");
	list.addCondition("and", "owner_id", "is_equal", -3);
	list.addCondition("and", "ts_execute", "is_less_or_equal", "now");
	//if (onlyNew)
		//list.addCondition("and", "f_seen", "is_equal", "f");
	list.cbData.cls = this;
	list.cbData.updateMode = onlyNew || false;

	list.addSortOrder("ts_execute", "desc");

	// Get notifications and store in array
	list.onLoad = function() {
		for (var i = 0; i < this.getNumObjects(); i++)
		{
			var notification = this.getObject(i);
			this.cbData.cls.addNotification(notification, this.cbData.updateMode);
		}

		// If we are rendered into an antView and the view is active
		if (this.cbData.cls.antView)
		{
			if (this.cbData.cls.antView.isActive)
				this.cbData.cls.renderList();
		}

		// Update titles and counters
		this.cbData.cls.updateDisplays();
	};

	list.getObjects(0, 25);

	// Queue next refresh in 60 seconds
	this.setRefresh();
}

/**
 * Add notification
 *
 * @param {CAntObject} notification The notification object to display
 * @param {bool} updateMode If true new items should be put at the top of the list
 */
NotificationMan.prototype.addNotification = function(notification, updateMode)
{
	// Make sure we have not already added this notification
	for (var i in this.notifications)
	{
		var not = this.notifications[i];

		if (not.id == notification.id)
		{
			if (not.getValue("f_seen") != notification.getValue("f_seen"))
				this.notifications[i] = notification; // replace with updated notification

			return; // Do not add again, already in the queue
		}
	}

	if (updateMode)
		this.notifications.unshift(notification);
	else
		this.notifications.push(notification);


	if (notification.getValue('f_popup') == true && notification.getValue("f_seen") == false)
		this.showPopup(notification);
}

/**
 * Display notification popup
 *
 * @param {CAntObject} notification The notification object to display
 */
NotificationMan.prototype.showPopup = function(notification)
{
 	var dlg = new CDialog("Notificaiton");

	var con = alib.dom.createElement("div");

	var header = alib.dom.createElement("h2", con);
	header.innerHTML = notification.getValue("name");

	// Add description
	var descCon = alib.dom.createElement("p", con);
	var desc = notification.getValue("description");
	descCon.innerHTML = desc.replace(/\n/g, "<br />");

	// Print object reference link
	var objReference = notification.getValue("obj_reference");
	if (objReference)
	{
		var refParts = objReference.split(":");
		if (refParts.length == 2)
		{
			var linkCon = alib.dom.createElement("p", con);
			var lnk = alib.dom.createElement("a", linkCon);
			lnk.href = "javascript:void(0);";
			lnk.dlg = dlg;
			lnk.objType = refParts[0];
			lnk.oid = refParts[1];
			lnk.cls = this;
			lnk.notification = notification;
			lnk.onclick = function() {
				loadObjectForm(this.objType, this.oid);
				this.cls.dismiss(this.notification);
				this.dlg.hide();
			};
			lnk.innerHTML = "Click for more information";
		}
	}

	// Print buttons bar
	// ------------------------------------------------
	var btnCon = alib.dom.createElement("div", con);
    var btn = alib.ui.Button("Dismiss", {
		className:"b1", tooltip:"Dismiss this notice", dlg:dlg, 
		cls:this, notification:notification,
		onclick:function() {
			this.dlg.hide();
			this.cls.dismiss(this.notification);
		}
	});
    btn.print(btnCon);

	dlg.customDialog(con, 500);
}

/**
 * Dismiss a notification and set it as seen
 *
 * @param {CAntObject} notification The notification object to display
 */
NotificationMan.prototype.dismiss = function(notification)
{
	notification.setValue("f_seen", true);
	notification.save();
}

/**
 * Set refresh in 60 seconds
 */
NotificationMan.prototype.setRefresh = function(inMs)
{
	if (this.refreshTimer)
	{
		if (inMs)
			window.clearTimeout(this.refreshTimer); // Push an update
		else
			return; // Already queued - no useless overlaps here
	}

	var delay = inMs || 1000 * 10;

	var cls = this;
	this.refreshTimer = window.setTimeout(function() {
		cls.refreshTimer = null;
		cls.getNotifications(true);
	}, delay); // Refresh in one minute
}


/**
 * Update UI elements based on the number of unseen notifications
 */
NotificationMan.prototype.updateDisplays = function()
{
	var unseen = 0;
	for (var i in this.notifications)
	{
		if (this.notifications[i].getValue("f_seen") != true)
			unseen++;
	}

	if (unseen)
	{
		if (this.iconCon)
			this.iconCon.innerHTML = "<img src='/images/icons/notification_24_on.png' style='vertical-align:middle;'> (" + unseen + ")";
	}
	else
	{
		if (this.iconCon)
			this.iconCon.innerHTML = "<img src='/images/icons/notification_24_off.png' style='vertical-align:middle;'>";
	}

	if (typeof Ant != "undefined")
		Ant.updateAppTitle(unseen, "notifications");
}


/**
 * Load an object
 */
NotificationMan.prototype.loadObject = function(objType, oid)
{
	if (!this.antView)
	{
		loadObjectForm(objType, oid);
		return;
	}

	// See if we need to setup the view for this object type
	if (!this.antView.getView(objType + ":" + oid))
	{
		var viewItem = this.antView.addView(objType+":[id]", {obj_type:objType});
		viewItem.render = function() { }
		viewItem.onshow = function()  // draws in onshow so that it redraws every time
		{ 
			// Do not reload if this object id is already loaded
			if (this.options.lastLoaded == this.variable)
				return true;
				
			this.con.innerHTML = "";
			this.title = ""; // because objects are loaded in the same view, clear last title
			var ol = new AntObjectLoader(this.options.obj_type, this.variable);
			ol.setAntView(this);
			ol.print(this.con);
			ol.cbData.antView = this;
			ol.onClose = function() 
			{ 
				this.cbData.antView.goup(); 
			}
			ol.onRemove = function() { }

			this.options.lastLoaded = this.variable;
		};
	}

	// Load object into view
	this.antView.navigate(objType + ":" + oid);
}
