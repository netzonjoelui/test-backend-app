/**
 * @fileoverview This class is responsible for loading all object templates for editing
 *
 * AntObjectTempLoader is used to load all types of objets in ANT. This class should not be responsible
 * for printing any ui elements, but rather loading the appropriate forms for each object type.
 *
 * Below is an example:
 * <code>
 * 	var objLoader = new AntObjectTempLoader("customer");
 * 	objLoader.onclose = function() { // do something when the object is closed  }
 * 	objLoader.print(document.getElementById("body"));
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectTempLoader.
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 * @param {number} oid The optional unique id of an object instance to load
 * @param {CAntObject} objInst Optional existing of the object
 * @param {AntObjectTemp} parentTemplate If set this is a child of a parent template
 */
function AntObjectTempLoader(obj_type, tid, parentTemplate)
{
	/**
	 * Instance of CAntObject of type obj_type - loads object data if oid is defined
	 *
	 * @public
	 * @type {CAntObject}
	 */
	this.mainObject = new CAntObject(obj_type);

	/**
	 * Load the template
	 *
	 * @public
	 * @type {AntObjectTemp}
	 */
	this.template = new AntObjectTemp(obj_type, tid);

	/**
	 * Optional parent templaet
	 *
	 * @public
	 * @type {AntObjectTemp}
	 */
	this.parentTemplate = parentTemplate || null;

	/**
	 * Unique object id
	 *
	 * This will be set to a number when saving a new object.
	 *
	 * @public
	 * @type {number}
	 */
    this.tid = tid;
    
	/**
	 * Textual name of the objet type we are working with
	 *
	 * @protected
	 * @type {string}
	 */
    this.objType = obj_type;

	/**
	 * Dialog if in dialog mode
	 *
	 * @type {CDialog}
	 */
	this.dialog = null;
}

/**
 * Display a dialog with this project template
 *
 * @public
 */
AntObjectTempLoader.prototype.showDialog = function()
{
	this.dialog = new CDialog("Template Editor");
	this.dialog.f_close = true;

	var dlgCon = alib.dom.createElement("div");
	
	this.dialog.customDialog(dlgCon, 800);
	this.renderForm(dlgCon);

	// TODO: add action buttons
	
	// Make sure the dialog is positioned correctly
	this.dialog.reposition();
}

/**
 * Render the form
 *
 * @public
 * @param {DOMElement} con The container that will hold the form
 */
AntObjectTempLoader.prototype.renderForm = function(con)
{
	var fldCon = alib.dom.createElement("div", con);
	
	var tbl = alib.dom.createElement("table", fldCon);
	var tbody = alib.dom.createElement("tbody", tbl);

	var fields = this.mainObject.getFields();
	for (var i in fields)
	{
		if (fields[i].type == 'object_multi' || fields[i].readonly)
			continue;

		var row = alib.dom.createElement("tr", tbody);

		var lbl = alib.dom.createElement("td", row);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = fields[i].title;

		var valCon = alib.dom.createElement("td", row);
		alib.dom.styleSetClass(valCon, "formValue");

		// TODO: render input here
	}

	// TODO: Below is a teamporary manual hack
	if (this.objType == "project")
	{
		var frm = new CWindowFrame("Tasks");
		var frmcon = frm.getCon();
		frm.print(con);
		
		// Add task con
		var actCon = alib.dom.createElement("div", frmcon);
		var lnk = alib.dom.createElement("a", actCon, "Add Task");
		lnk.href = "javascript:void(0);";
		lnk.parentTemplate = this.template;
		lnk.onclick = function() {
			var subldr = new AntObjectTempLoader("task", null, this.parentTemplate);
			subldr.showDialog();
		}
	}
}
