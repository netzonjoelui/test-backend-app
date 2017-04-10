{
	name:"exec_time",
	title:"Reminder Execute Time Manager",
	mainObject:null,
	formObj:null,

	/**
	 * Main entry point
	 *
	 * @param {DOMElement} con The container for this plugin
	 */
	main:function(con)
	{
		this.con = con;
		this.buildInterface();

		// Listen for edit mode change
		alib.events.listen(this.formObj, "changemode", function(evnt){ 
			//evnt.data.plCls.formObj.editMode;
			evnt.data.plCls.buildInterface();
		}, {plCls:this});
	},

	save:function()
	{
		// Nothing needs to be done so just call onsave
		this.onsave();
	},

	// Object loader callback - required
	onsave:function()
	{
	},

	/**
	 * If the object was saved reload the reflect any default values
	 */
	objectsaved:function()
	{
		if (this.mainObject.id)
			this.buildInterface();
	},

	/**
	 * Watch for obj_reference to change
	 *
	 * @param {string} fname The name of the field to update
	 * @param {string} fvale The value the field was set to
	 * @param {string} fkeyName If fkey or object type then fkey will be the label value
	 */
	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
		if (fname == "obj_reference" || fname == "field_name")
		{
			// The obj_reference has changed so we shold rebuild the input to match the object type
			this.buildInterface();
		}
	},

	/**
	 * Build timeing interface based on object referenced fields or manual precise time
	 */
	buildInterface:function()
	{
		// Only show input form in edit mode
		if (!this.formObj.editMode)
			return this.renderText();

		this.con.innerHTML = "";

		var objDef = null;

		var oref = this.mainObject.getValue("obj_reference");
		if (oref)
		{
			var refParts = oref.split(":");
			if (refParts.length == 2)
				objDef = new CAntObject(refParts[0]);
		}

		var table = alib.dom.createElement('table', this.con);
		var tbody = alib.dom.createElement('tbody', table);
		var row = alib.dom.createElement('tr', tbody);

		// Drop-down either manual or the field
		// -------------------------------------------------------------
		var td = alib.dom.createElement("td", row);
		var sel = alib.dom.createElement("select", td);
		sel[sel.length] = new Option("@ a Specific Time", "");

		if (objDef)
		{
			var fields = objDef.getFields();
			for (var i in fields)
			{
				if (fields[i].type == "date" || fields[i].type == "timestamp")
					sel[sel.length] = new Option("Before " + fields[i].title, fields[i].name, false, 
													(this.mainObject.getValue("field_name")==fields[i].name) ? true : false);
			}
		}
		
		sel.cls = this;
		sel.onchange = function() {
			this.cls.mainObject.setValue("field_name", this.value);
		};

		// Add time select portion
		if (this.mainObject.getValue('field_name'))
			this.buildFieldTime(row);
		else
			this.buildManualTime(row);

	},

	/**
	 * Build interval select if we are using a field to key off
	 */
	buildFieldTime:function(row)
	{
		if (!this.mainObject.getValue("interval"))
			this.mainObject.setValue("interval", 30);

		if (!this.mainObject.getValue("interval_unit"))
			this.mainObject.setValue("minutes", 30);

		// Interval
		var td = alib.dom.createElement("td", row);
		var intTxt = alib.dom.createElement("input", td);
		intTxt.size = 2;
		intTxt.value = this.mainObject.getValue("interval");
		intTxt.obj = this.mainObject;
		intTxt.onchange = function() {
			if (isNaN(this.value))
				this.value = 30;

			this.obj.setValue("interval", this.value);
		};

		// Interval Unit
		var td = alib.dom.createElement("td", row);
		var timeSel = alib.dom.createElement("select", td);
		timeSel[timeSel.length] = new Option("minute(s)", "minutes", false, (this.mainObject.getValue("interval_unit")=="minutes") ? true : false);
		timeSel[timeSel.length] = new Option("hour(s)", "hours", false, (this.mainObject.getValue("interval_unit")=="hours") ? true : false);
		timeSel[timeSel.length] = new Option("day(s)", "days", false, (this.mainObject.getValue("interval_unit")=="days") ? true : false);
		timeSel[timeSel.length] = new Option("week(s)", "weeks", false, (this.mainObject.getValue("interval_unit")=="weeks") ? true : false);
		timeSel[timeSel.length] = new Option("month(s)", "months", false, (this.mainObject.getValue("interval_unit")=="months") ? true : false);
		timeSel[timeSel.length] = new Option("year(s)", "years", false, (this.mainObject.getValue("interval_unit")=="years") ? true : false);
		timeSel.obj = this.mainObject
		timeSel.onchange = function() {
			this.obj.setValue("interval_unit", this.value);
		};
	},

	/**
	 * Build manual time entry input for ts_execute
	 */
	buildManualTime:function(row)
	{
		var td = alib.dom.createElement("td", row);

		var curVal = this.mainObject.getValue("ts_execute");
		var inp = new AntObject_FieldInput(this.mainObject, "ts_execute", curVal);
		inp.render(td);
	},

	/**
	 * Reder text is a new optional function for getting the text version out of edit mode
	 */
	renderText:function()
	{
		this.con.innerHTML = "";

		var strDesc = "";

		if (this.mainObject.getValue("field_name"))
		{

			strDesc += this.mainObject.getValue("interval");
			strDesc += " ";
			strDesc += this.mainObject.getValue("interval_unit");
			strDesc += " before ";

			// Get field name
			var objDef = null;
			var oref = this.mainObject.getValue("obj_reference");
			if (oref)
			{
				var refParts = oref.split(":");
				if (refParts.length == 2)
					objDef = new CAntObject(refParts[0]);
			}

			if (objDef)
			{
				var field = objDef.getFieldByName(this.mainObject.getValue("field_name"))
				strDesc += objDef.title + "." + field.title;
			}
			else
			{
				strDesc += this.mainObject.getValue("field_name");
			}
		}
		else
		{
			strDesc = "At " + this.mainObject.getValue("ts_execute");
		}

		this.con.innerHTML = strDesc;
	}
}
