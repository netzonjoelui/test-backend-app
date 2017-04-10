/**
 * @fileoverview Starting work on new object import wizard
 */

/**
 * @constructor
 */
function AntWizard_EntityImport()
{
	/**
	 * Handle to wizard, this MUST be set by the parent class or calling procedure
	 *
	 * @private
	 * @param {AntWizard}
	 */
	this.wizard = null;

	/**
	 * Last error
	 *
	 * @public
	 * @param {string}
	 */
	this.lastErrorMessage = "";

	/**
	 * Import job data
	 *
	 * @protected
	 * @type {Object}
	 */
	this.importData = {
		obj_type : "customer",
		template_id : "",
		map_fields : new Object(),
		field_defaults : new Object(),
		mergeby : new Array()
	};

	/**
	 * Import settings templates
	 *
	 * @protected
	 * @type {Array}
	 */
	this.templates_ = null;
}

/**
 * Setup steps for this wizard
 *
 * This function is called by the AntWizard base class once the wizard has loaded for the first time
 *
 * @param {AntWizard} wizard Required handle to parent wizard class
 */
AntWizard_EntityImport.prototype.setup = function(wizard)
{
	this.wizard = wizard;

	this.wizard.title = "Import Wizard";

	var me = this;

	// Add step 1 - selecting a data source and determining what kind of entity we are importing
	this.wizard.addStep(function(con) { me.stepSelectData(con); }, "Select Data");

	// Add step 2 - mapping imcoming columns to entity/object fields
	this.wizard.addStep(function(con) { me.stepMapFields(con); }, "Map Data");

	// Add step 3 - setting default values for object
	this.wizard.addStep(function(con) { me.stepDefaultValues(con); }, "Set Default Values");

	// Add step 4 - merging records with existing entities if fields match
	this.wizard.addStep(function(con) { me.stepMergeBy(con); }, "Merge Duplicates");

	// Add step 5 - import and save
	this.wizard.addStep(function(con) { me.stepImportAndSave(con); }, "Save &amp; Import");

	// Add step 6 - finished
	this.wizard.addStep(function(con) { me.stepFinished(con); }, "Import Started");

	// Load object data
	if (wizard.cbData.obj_type)
	{
		this.importData.obj_type = wizard.cbData.obj_type;
	}
}

/**
 * This function is called every time the user advances a step
 *
 * It may be overridden by each step function below when the step function is called.
 * However, it is reset by the wizard class before each step loads so
 * verification code is limited to that step and must be set each time.
 *
 * If the function returns false, the wizard will not progress to the next step.
 * This function will not be called on the final step where "Finished" is presented.
 * Validation for that step must take place in the onFinished callback.
 *
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EntityImport.prototype.processStep = function() { return true; }

/**
 * Select data source
 *
 * @public
 * @param {DOMElement} con The container to print the step on
 */
AntWizard_EntityImport.prototype.stepSelectData = function(con)
{
	// Welcome
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "Welcome to the import wizard. This tool will guide you through importing a CSV (Comma Separated Value) file. "
				+ "Many programs, like Outlook and ACT, provide an export to CSV feature. Please refer to the support documentation "
				+ "of whatever service or program you are using if you need help exporting.";

	// Object Type
	var row = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(row, "row mgb1");

	var td = alib.dom.createElement("div", row);
	alib.dom.styleSetClass(td, "col-2 wizardLabel");
	td.innerHTML = "Import data as:";

	var td = alib.dom.createElement("div", row);
	alib.dom.styleSetClass(td, "col-10");
	var dm = alib.dom.createElement("select");
	dm.cls = this;
	dm.onchange = function() 
	{
		if(this.value != "")
			this.cls.importData.obj_type = this.value;
	}
	td.appendChild(dm);
	this.populateObjects(dm);

	var notes = alib.dom.createElement("span", td, " select the type of object to import");

	// Upload file
	var uploadCon = alib.dom.createElement("fieldset", con);
	var legend = alib.dom.createElement("legend", uploadCon, "Upload a CSV File to Import");
	
	var div_uploader = alib.dom.createElement("div", uploadCon);

	var div_fileUpload = alib.dom.createElement("div", uploadCon); // display the video info
	alib.dom.styleSet(div_fileUpload, "margin", "10px");
	alib.dom.styleSet(div_fileUpload, "padding", "5px");
	if (this.importData.file_name)
	{
		alib.dom.styleSet(div_fileUpload, "display", "block");
		div_fileUpload.innerHTML = "Selected File: <a href='/files/" + this.importData.file_id + "' target=_blank>" + this.importData.file_name + "</a>";
	}
	else
	{
		alib.dom.styleSet(div_fileUpload, "display", "none");
	}

	var cfupload = new AntFsUpload('%tmp%');
	cfupload.cbData.m_appcls = this;
	cfupload.cbData.div_fileUpload = div_fileUpload;
	cfupload.onUploadStarted = function () { this.cbData.m_appcls.wait_uploading = true; };
	cfupload.onQueueComplete = function () { this.cbData.m_appcls.wait_uploading = false; };
	cfupload.onUploadSuccess = function (fid, name) 
	{ 
		this.cbData.m_appcls.importData.file_id = fid; 
		this.cbData.m_appcls.importData.file_name = name; 
		alib.dom.styleSet(this.cbData.div_fileUpload, "display", "block");
		this.cbData.div_fileUpload.innerHTML = "Selected File: <a href='/files/" + fid + "' target=_blank>" + name + "</a>";
	};
	cfupload.showTmpUpload(div_uploader, div_fileUpload, 'Upload CSV File', 1);

	// Template
	var templateCon = alib.dom.createElement("fieldset", con);
	var legend = alib.dom.createElement("legend", templateCon, "What is the source of the data?");
	
	cbTemplates = alib.dom.createElement("select", templateCon);
	cbTemplates.size = 10;
	alib.dom.styleSet(cbTemplates, "display", "block");
	alib.dom.styleSet(cbTemplates, "width", "100%");
	cbTemplates.cls = this;
	cbTemplates.onchange = function()
	{
		this.cls.importData.template_id = this.value;
	}
	this.populateTemplates(cbTemplates);

	var delLnk = alib.dom.createElement("a", templateCon, "Delete Selected Template");
	delLnk.href = "javascript:void(0);";
	delLnk.cbTemplates = cbTemplates;
	delLnk.cls = this;
	delLnk.onclick = function() {
		this.cls.deleteTemplate(this.cbTemplates.value);
	}

	// Handle next action
	this.processStep = function()
	{
		if (!this.importData.file_id)
		{
			this.lastErrorMessage = "Please upload a file before continuing!";    
			return false;
		}
		
		return true;
	}
}

/**
 * Map fields
 *
 * @public
 * @param {DOMElement} con The container to print the step on
 */
AntWizard_EntityImport.prototype.stepMapFields = function(con)
{
	alib.dom.createElement("h1", con, "Set Default Values");

	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "Map data from your import to object fields below.";

	// Map fields
	// -----------------------------------------------------------------
	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "height", "400px");
	alib.dom.styleSet(dv, "overflow", "auto");

	dv.innerHTML = "<div class='loading></div>";

	this.loadMapColumns(dv);
}

/**
 * Display default values step
 *
 * @public
 * @param {DOMElement} con The container to print the step on
 */
AntWizard_EntityImport.prototype.stepDefaultValues = function(con)
{
	alib.dom.createElement("h1", con, "Set Default Values");

	// Description
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "These will be used for every imported record if the data is not set in the import data. "
				+ "For example, if your imported data does not contain a field called 'Groups' or 'Categories' you can set the "
				+ "Groups value below which will act as if the imported data had a 'Groups' column.";

	// List fields
	var tblCon = alib.dom.createElement("div", con);

	var tbl = new CToolTable("100%");
	tbl.addHeader("Field", "left", "100px");
	tbl.addHeader("Value");
	tbl.print(tblCon);

	var fields = Ant.EntityDefinitionLoader.get(this.importData.obj_type).getFields();
	for (var i in fields)
	{
		if (fields[i].name == "account_id" || fields[i].readonly || fields[i].type == "object_multi")
			continue;

		var row = tbl.addRow();
		row.addCell(fields[i].title);

		var inp_div = alib.dom.createElement("div");
		var field = new AntObject_FieldInput(this.importData.obj_type, fields[i].name, this.importData.field_defaults[fields[i].name]);
		field.render(inp_div);

		alib.events.listen(field, "change", function (evt) {
			evt.data.cls.importData.field_defaults[evt.data.fld.getName()] = evt.data.fld.getValue();
		}, {fld: field, cls: this});

		row.addCell(inp_div);
	}
}

/**
 * Display merge by step
 *
 * @public
 * @param {DOMElement} con The container to print the step on
 */
AntWizard_EntityImport.prototype.stepMergeBy = function(con)
{
	alib.dom.createElement("h1", con, "Update existing records if the following conditions are met:");

	// Description
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "Tip: Select multiple matches for increased accuracy. For example, selecting \"First Name\" and \"Last Name\" and \"Email\" "
				+ "will only merge imported data with an existing record if all three conditions are met. If only \"First Name\" "
				+ "is selected, then there is a high probability that multiple matches will be found and merged.";

	var div_merge = alib.dom.createElement("fieldset");
	alib.dom.createElement("legend", div_merge, "Set Merge Fields");

	// Hide if we have nothing set yet
	if (this.importData.mergeby.length == 0)
		alib.dom.styleSet(div_merge, "display", "none");

	// Create radios that either hide or show the mergby option
	var div_new = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.checked = (this.importData.mergeby.length == 0) ? true : false;
	rbtn1.cls = this;
	rbtn1.div_merge = div_merge;
	rbtn1.onclick = function() { 
		// Reset values
		this.cls.importData.mergeby = new Array();
		// Hide mergeby fields
		alib.dom.styleSet(this.div_merge, "display", "none"); 
	}
	div_new.appendChild(rbtn1);
	var lbl = alib.dom.createElement("span", div_new);
	lbl.innerHTML = " Do not merge data - enter each row as a new record";

	var div_template = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(div_template, "mgb1");
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.checked = (this.importData.mergeby.length > 0) ? true : false;
	rbtn1.div_merge = div_merge;
	rbtn1.onclick = function() { alib.dom.styleSet(this.div_merge, "display", "block"); }
	div_template.appendChild(rbtn1);
	var lbl = alib.dom.createElement("span", div_template);
	lbl.innerHTML = " Try to merge data with existing records if match is found";

	// Add container after the radio to put it in the right order
	con.appendChild(div_merge);

	for (var i in this.importData.map_fields)
	{
		if (this.importData.map_fields[i] != "")
		{
			var dv = alib.dom.createElement("div", div_merge);

			var ck = alib.dom.createElement("input");
			ck.type = "checkbox";
			ck.value = this.importData.map_fields[i];
			ck.cls = this;
			ck.checked = (alib.indexOf(this.importData.mergeby, this.importData.map_fields[i]) != -1) ? true : false;
			ck.colName = i;
			ck.onclick = function()
			{
				if (this.checked)
				{
					if (alib.indexOf(this.cls.importData.mergeby, this.value) == -1)
						this.cls.importData.mergeby.push(this.value);
				}
				else
				{
					var ind = alib.indexOf(this.cls.importData.mergeby, this.value);
					if (ind != -1)
						this.cls.importData.mergeby.splice(ind, 1);
				}
			}
			dv.appendChild(ck);

			var lbl = alib.dom.createElement("span", dv);
			lbl.innerHTML = " Column \"" + i 
						  + "\" in imported data matches property \"" + this.importData.map_fields[i] 
						  + "\" in " + Ant.EntityDefinitionLoader.get(this.importData.obj_type).title;
		}
	}
}

/**
 * Display process and save
 *
 * @public
 * @param {DOMElement} con The container to print the step onto
 */
AntWizard_EntityImport.prototype.stepImportAndSave = function(con)
{
	alib.dom.createElement("h1", con, "Ready to Import");

	// Description
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "We have everything we need to import your data. Click \"Continue\" below to start the import process.";

	// Save as dialog
	var fieldset = alib.dom.createElement("fieldset", con);
	alib.dom.createElement("legend", fieldset, "Save Template (optional)");

	// TODO: hiding for now
	alib.dom.styleSet(fieldset, "display", "none");

	var inp_name = alib.dom.createElement("input"); // created first for reference

	if (this.importData.template_id)
	{
		var dv = alib.dom.createElement("div", fieldset);

		var lbl = alib.dom.createElement("span", dv, "Save Changes: ");
		alib.dom.styleSetClass(lbl, "wizardLabel");

		var inp = alib.dom.createElement("input");
		inp.type = "checkbox";
		inp.checked = false;
		inp.cls = this;
		inp.inp_name = inp_name;
		inp.onclick = function() { 
			this.cls.save_template_changes = (this.checked) ? 't' : 'f'; 
			this.inp_name.disabled = (this.checked) ? true : false; 
		};
		dv.appendChild(inp);

		alib.dom.createElement("span", dv, " (save the changes made to this template)");
	}

	var lbl = alib.dom.createElement("span", fieldset, "Save As: ");
	alib.dom.styleSetClass(lbl, "wizardLabel");

	alib.dom.styleSet(inp_name, "width", "200px");
	inp_name.type = "text";
	inp_name.cls = this;
	inp_name.onchange = function() { 
		this.cls.save_template_name = this.value; 
	};
	fieldset.appendChild(inp_name);

	var lbl = alib.dom.createElement("span", fieldset);
	lbl.innerHTML = " (Save settings to re-use later. Leave blank if you do not wish to save.)";


	// Handle next action
	this.processStep = function() {
		this.startImport();
		return true;
	}
}

/**
 * Display finished confirmation
 *
 * @public
 * @param {DOMElement} con The container to print the step onto
 */
AntWizard_EntityImport.prototype.stepFinished = function(con)
{
	alib.dom.createElement("h1", con, "Finished! Your import is being processed now.");

	// Description
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "success");
	p.innerHTML = "This import will continue to run in the background and may take some time. You will be emailed when the process "
			 	+ "is completed. Click the \"Finish\" button below to close this wizard. If you have more than one CSV to import, feel "
				+ "free to start another import at any time.";
}

/**
 * Populate object dropdown
 *
 * @private
 * @param {DOMSelect} selectInput
 */
AntWizard_EntityImport.prototype.populateObjects  = function(selectInput)
{
	// Clear Select 
	selectInput.options.length = 0;

	// First check to see if we have data cached
	if (this.objectTypes_)
	{
		for(i in this.objectTypes_)
		{
			var otype = this.objectTypes_[i];
			selectInput[selectInput.length] = new Option(otype.title, otype.name);
		}
		
		// Set current selection
		selectInput.value = this.importData.obj_type;

		return;
	}

	// Add select text
	selectInput[0] = new Option("Loading...", "");
    
	var xhr = new alib.net.Xhr();

	// Populate objects array and call the populateObjects function again on load
	alib.events.listen(xhr, "load", function(evt) { 
		var ret = this.getResponse();
		evt.data.wizCls.objectTypes_ = ret;
		evt.data.wizCls.populateObjects(evt.data.sel);
	}, {wizCls:this, sel:selectInput});

	var ret = xhr.send("/controller/Object/getObjects");
}

/**
 * Populate object dropdown
 *
 * @private
 * @param {DOMSelect} selectInput
 */
AntWizard_EntityImport.prototype.populateTemplates = function(selectInput)
{
	// Clear Select 
	selectInput.options.length = 0;

	// First check to see if we have data cached
	if (this.templates_ != null)
	{
		for(i in this.templates_)
		{
			var temp = this.templates_[i];
			selectInput[selectInput.length] = new Option(temp.name, temp.id);
		}

		// Add empty default
		selectInput[selectInput.length] = new Option("Other/Not Listed", "");
		
		// Set current selection
		if(this.importData.template_id)
			selectInput.value = this.importData.template_id;
		else
			selectInput.value = ""; // Use default

		return;
	}
	
	selectInput[0] = new Option("Loading", "");
    
	var xhr = new alib.net.Xhr();

	alib.events.listen(xhr, "load", function(evt) { 
		var ret = this.getResponse();
		evt.data.wizCls.templates_ = ret;
		evt.data.wizCls.populateTemplates(evt.data.sel);
	}, {wizCls:this, sel:selectInput});

	xhr.send("/controller/Object/importGetTemplates", "POST", {obj_type:this.importData.obj_type});
}

/**
 * Delete a template
 *
 * @private
 * @param {string} tid The id of the template to delete
 */
AntWizard_EntityImport.prototype.deleteTemplate = function(tid)
{
	if (!tid)
	{
		alert("System templates cannot be deleted");
		return;
	}

	// TODO: this was never implemented right
}

/**
 * Map columns of import to object fields
 *
 * @private
 * @param {DOMElement} con The div to print maps into
 */
AntWizard_EntityImport.prototype.loadMapColumns = function(con)
{
	if (!this.importData.file_id)
		return;

    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = con;
	ajax.cbData.entityDef = Ant.EntityDefinitionLoader.get(this.importData.obj_type);
    ajax.onload = function(cols)
    {
        if (cols)
        {
            this.cbData.con.innerHTML = "";
            var tbl = new CToolTable();
            tbl.addHeader("CSV Column");
            tbl.addHeader(this.cbData.entityDef.title + " Field");
            tbl.print(this.cbData.con);

            for (var i = 0; i < cols.length; i++)
            {
                this.cbData.cls.loadMapColumnRow(tbl, i, cols[i]);
            }
        }
    };
    var args = [["data_file_id", this.importData.file_id]];
    ajax.exec("/controller/Object/importGetHeaders", args);
}

/**
 * Print collmap row from data
 *
 * @private
 * @param {CToolTable} tbl The tool table to add rows to
 * @param {string} colid The unique id, for CSV this is the column number
 * @param {string} colname The name of the current column (column header)
 */
AntWizard_EntityImport.prototype.loadMapColumnRow = function(tbl, colid, colname)
{
	var row = tbl.addRow();

	row.addCell(colname);

	// Add field name
	var field_sel = alib.dom.createElement("select");
	field_sel.cls = this;
	field_sel.colid = colid;
	field_sel[field_sel.length] = new Option("Do Not Import", '');
	field_sel[field_sel.length] = new Option("Create New Field", 'ant_create_field');
	field_sel[field_sel.length] = new Option("Create New Field With Dropdown", 'ant_create_field_dd');
	field_sel.onchange = function() { this.cls.importData.map_fields[this.colid] = this.value; };

	// Loop through entity fields
	var fields = Ant.EntityDefinitionLoader.get(this.importData.obj_type).getFields();
	for (var f in fields)
	{
		if ((fields[f].type != "fkey_multi" || fields[f].name == "groups") && fields[f].name != "account_id" && fields[f].readonly != true)
		{
			field_sel[field_sel.length] = new Option(fields[f].title, fields[f].name);

			if ((fields[f].name == colname.toLowerCase() || fields[f].title.toLowerCase() == colname.toLowerCase()) 
				&& !this.importData.map_fields[colid])
				this.importData.map_fields[colid] = fields[f].name;
		}
	}

	// Set select
	if (this.importData.map_fields[colid])
		field_sel.value = this.importData.map_fields[colid];

	row.addCell(field_sel);
}

/**
 * Start the import job
 *
 * @private
 */
AntWizard_EntityImport.prototype.startImport = function()
{
	var xhr = new alib.net.Xhr();

	// Setup callback
	alib.events.listen(xhr, "load", function(evt) { 
		var data = this.getResponse();
	}, {defCls:this});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
	}, {defCls:this});

	var ret = xhr.send("/controller/Object/importRun", "POST", {import_data: JSON.stringify(this.importData)});
}
/**
 * Save settings as a template
 *
 * TODO: this is not yet done
 */
AntWizard_EntityImport.prototype.save = function()
{
	/*
	if (this.save_template_name)
	{
		var args = [["obj_type", this.mainObject.name], ["save_template_name", this.save_template_name],
					["template_id", this.template_id], ["save_template_changes", this.save_template_changes]];

		for (var i = 0; i < this.data_file_columns.length; i++)
		{
			args[args.length] = ["maps[]", this.data_file_columns[i].colName+":::"+this.data_file_columns[i].mapTo];
		}
        
        ajax = new CAjax();
        ajax.cbData.cls = this;
        ajax.cbData.dlg = this.m_dlg;
        ajax.onload = function(ret)
        {
            if (ret)
                this.cbData.cls.onFinished();

            this.cbData.dlg.hide();
        };
        args[args.length] = ["function", "save_template"];
        ajax.exec("/objects/xml_import_actions.php", args);
	}
	*/
}
