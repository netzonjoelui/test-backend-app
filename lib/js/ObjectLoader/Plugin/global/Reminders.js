/**
 * @fileoverview This class is a global object form plugin for managing the uname
 *
 * @author     Marl Tumulak, marl.tumulak@aereus.com.
 *             Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */
 
 /**
 * Class constructor
 */
function AntObjectLoader_Reminders()
{
	/**
	 * The unique name of this plugin
	 *
	 * @var {string}
	 */
    this.name = "reminders";

	/**
	 * The title of this plugin
	 *
	 * @var {string}
	 */
    this.title = "Reminders";

	/**
	 * The object we are handling reminders for
	 *
	 * @var {CAntObject}
	 */
    this.mainObject = null;

	/**
	 * The main container where the reminders will be printed
	 *
	 * @var {DOMElement}
	 */
    this.mainCon = null;

	/**
	 * Reminders table
	 *
	 * @var {tbody}
	 */
	this.remindersTableBody = null;

	/**
	 * Array of reminders
	 *
	 * @var {CAntObject[]}
	 */
	this.reminders = new Array();

	/**
	 * Add default reminders
	 *
	 * @var {bool}
	 */
	this.addDefault = false;

	/**
	 * Get execution from a field name
	 *
	 * @var {string}
	 */
	this.fieldName = "";
}

/**
 * Required plugin main function
 */
AntObjectLoader_Reminders.prototype.main = function(con)
{
    this.mainCon = con;

    if(this.mainObject.id)
    {
		var list = new AntObjectList("reminder");
		list.addCondition("and", "obj_reference", "is_equal", this.mainObject.obj_type + ":" + this.mainObject.id);
		list.cbData.cls = this;

		// Set reminders
		list.onLoad = function() {
			for (var i = 0; i < this.getNumObjects(); i++)
			{
				this.cbData.cls.addReminder(this.getObject(i));
			}

			this.cbData.cls.buildInterface();
		};

		list.getObjects();
    }        
	else
	{
		this.buildInterface();
	}
}

/**
 * Called from object loader when object is saved.
 *
 * Save one reminder at a time until they are all done
 */
AntObjectLoader_Reminders.prototype.save = function()
{
	// Get reminders to save
	var toSave = new Array();
	for (var i in this.reminders)
	{
		if (this.reminders[i].dirty)
		{
			this.reminders[i].setValue("obj_reference", this.mainObject.obj_type + ":" + this.mainObject.id);
			this.reminders[i].setValue("field_name", this.fieldName);
			toSave[toSave.length] = this.reminders[i];
		}
	}

	if (toSave.length)
	{
		// Recurrsively save until finished
		toSave[0].cbData.cls = this;
		toSave[0].onsave = function(){
			// Save will set this.dirty to false
			this.cbData.cls.save();
		}
		toSave[0].save();
	}
	else
	{
		// Finished or none to process
		this.onsave();
	}
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_Reminders.prototype.onsave = function()
{
}

/**
 * Print form 
 */
AntObjectLoader_Reminders.prototype.buildInterface = function()
{
	if (this.addDefault && this.reminders.length==0 && !this.mainObject.id)
	{
		var obj = new CAntObject("reminder");
		obj.setValue("action_type", "sms");
		obj.setValue("interval", "30");
		obj.setValue("interval_unit", "minutes");
		this.addReminder(obj);

		var obj2 = new CAntObject("reminder");
		obj2.setValue("action_type", "popup");
		obj2.setValue("interval", "30");
		obj2.setValue("interval_unit", "minutes");
		this.addReminder(obj2);
	}

	// Show Add Reminder link
	var lnkCon = alib.dom.createElement("div", this.mainCon);
	var lnk = alib.dom.createElement("a", lnkCon);
	lnk.href = "javascript:void(0);";
	lnk.cls = this;
	lnk.onclick = function(e) {
		this.cls.addReminder();
	};
	lnk.innerHTML = "Add Reminder";

}

/**
 * Add reminder entry
 */
AntObjectLoader_Reminders.prototype.addReminder = function(remObj)
{
	// Create default reminder object
	if (typeof remObj == "undefined")
	{
		var remObj = new CAntObject("reminder");
		remObj.setValue("action_type", "email");
		remObj.setValue("interval", "30");
		remObj.setValue("interval_unit", "minutes");
	}

	if (this.fieldName)
		this.renderRowWithField(remObj);
	
	// Add reminder object
	this.reminders[this.reminders.length] = remObj;
}

/**
 * Remove a reminder from the table from the the array of reminders to save
 *
 * @var {CAntObject}
 */
AntObjectLoader_Reminders.prototype.removeReminder = function(remObj)
{
	remObj.cbData.row.parentNode.removeChild(remObj.cbData.row);

	// delete if there is an id
	if (remObj.id)
		remObj.remove();

	// remove from this.reminders
	for (var i in this.reminders)
	{
		if (this.reminders[i] == remObj)
			this.reminders.splice(i, 1);
	}
}


/**
 * Render row for reminders with the field set
 */
AntObjectLoader_Reminders.prototype.renderRowWithField = function(remObj)
{
	var tbody = this.getTable();

	var row = alib.dom.createElement("tr", tbody);
	remObj.cbData.row = row; // Keep for removal

	// Drop-down for type
	var td = alib.dom.createElement("td", row);
	var sel = alib.dom.createElement("select", td);
	sel[sel.length] = new Option("Send Email", "email", false, (remObj.getValue("action_type")=="email") ? true : false);
	sel[sel.length] = new Option("Send Text Message (SMS)", "sms", false, (remObj.getValue("action_type")=="sms") ? true : false);
	sel[sel.length] = new Option("Pop-up Alert", "popup", false, (remObj.getValue("action_type")=="popup") ? true : false);
	sel.obj = remObj;
	sel.onchange = function() {
		this.obj.setValue("action_type", this.value);
	};

	// Interval
	var td = alib.dom.createElement("td", row);
	var intTxt = alib.dom.createElement("input", td);
	intTxt.size = 2;
	intTxt.value = remObj.getValue("interval");
	intTxt.obj = remObj;
	intTxt.onchange = function() {
		if (isNaN(this.value))
			this.value = 30;

		this.obj.setValue("interval", this.value);
	};

	// Interval Unit
	var td = alib.dom.createElement("td", row);
	var timeSel = alib.dom.createElement("select", td);
	timeSel[timeSel.length] = new Option("minute(s)", "minutes", false, (remObj.getValue("interval_unit")=="minutes") ? true : false);
	timeSel[timeSel.length] = new Option("hour(s)", "hours", false, (remObj.getValue("interval_unit")=="hours") ? true : false);
	timeSel[timeSel.length] = new Option("day(s)", "days", false, (remObj.getValue("interval_unit")=="days") ? true : false);
	timeSel[timeSel.length] = new Option("week(s)", "weeks", false, (remObj.getValue("interval_unit")=="weeks") ? true : false);
	timeSel[timeSel.length] = new Option("month(s)", "months", false, (remObj.getValue("interval_unit")=="months") ? true : false);
	timeSel[timeSel.length] = new Option("year(s)", "years", false, (remObj.getValue("interval_unit")=="years") ? true : false);
	timeSel.obj = remObj
	timeSel.onchange = function() {
		this.obj.setValue("interval_unit", this.value);
	};

	// Add remove button
	var td = alib.dom.createElement("td", row);
	var delImg = alib.dom.createElement("img", td);
	alib.dom.styleSet(delImg, "cursor", "pointer");
	delImg.src = "/images/icons/delete_16.png";
	delImg.obj = remObj;
	delImg.cls = this;
	delImg.onclick = function(e) {
		this.cls.removeReminder(this.obj);
	};
}

/**
 * Init & get table body
 *
 * @return {TBODY}
 */
AntObjectLoader_Reminders.prototype.getTable = function()
{
	if (this.remindersTableBody)
		return this.remindersTableBody;

	var tbl = alib.dom.createElement("table", this.mainCon);
	this.remindersTableBody = alib.dom.createElement("tbody", tbl);

	return this.remindersTableBody;
}
