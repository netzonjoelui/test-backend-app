/****************************************************************************
*	
*	Class:		CAntObjectImpWizard
*
*	Purpose:	Wizard for inserting a new video
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function CAntObjectImpWizard(obj_type, user_id)
{
	this.mainObject 		= new CAntObject(obj_type);
	this.user_id			= user_id;
	this.setUid();

	this.template_id		= null;			// Uid if template to pull message definition from
	this.templates 			= new Array();	// Array of available templates
	this.data_file_name		= null;
	this.data_file_id		= null;
	this.data_file_columns	= new Array();
	this.data_file_maps 	= new Array();
	this.merge_by			= new Array(); // Columns to merge by


	this.logo_file_id		= null;
	this.footer				= "";
	this.save_template_name = null;
	this.save_template_changes  = 'f';

	this.steps = new Array();
	this.steps[0] = "Upload Data";
	this.steps[1] = "Map Fields";
	this.steps[2] = "Set Defaults";
	this.steps[3] = "Set Merge Conditions";
	this.steps[4] = "Import &amp; Save";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CAntObjectImpWizard.prototype.setUid = function()
{
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].type == "fkey" && fields[i].subtype == "users")
			this.mainObject.setValue(fields[i].name, this.user_id);
	}
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CAntObjectImpWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Data Import Wizard", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 650, 510);

	this.showStep(0);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
CAntObjectImpWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.cbTemplates = null;
	this.verify_step_data = new Object();

	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", this.body_dv);
	this.pathDiv.innerHTML = "Step " + (step + 1) + " of " + this.steps.length + " - " + this.steps[step];
	alib.dom.styleSetClass(this.pathDiv, "wizardTitle");

	// Main content
	// ---------------------------------------------------------
	var div_main = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(div_main, "wizardBody");

	switch (step)
	{
	case 0:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "This wizard will guide you through importing data into ANT.";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "Programs such as Outlook and ACT usually have an export function that allows you to export your contacts. You can use such a file for importing your data.";

		var p = alib.dom.createElement("h3", div_main);
		p.innerHTML = "Select a csv data file:. ";

		var div_upload = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "-- OR --";

		var a_browse = alib.dom.createElement("a", div_main);

		var div_res = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");

		var div_display = alib.dom.createElement("div", div_main); // display the video info
		alib.dom.styleSetClass(div_display, "formLabel");
		alib.dom.styleSet(div_display, "margin", "10px");
		alib.dom.styleSet(div_display, "padding", "5px");
		alib.dom.styleSet(div_display, "text-align", "center");
		if (this.data_file_name)
		{
			alib.dom.styleSet(div_display, "border", "1px solid");
			div_display.innerHTML = "Selected File: " + this.data_file_name;
		}

		var cfupload = new AntFsUpload('%tmp%');
		cfupload.cbData.m_appcls = this;
		cfupload.cbData.div_display = div_display;
		cfupload.onUploadStarted = function () { this.cbData.m_appcls.wait_uploading = true; };
		cfupload.onQueueComplete = function () { this.cbData.m_appcls.wait_uploading = false; };
		cfupload.onUploadSuccess = function (fid, name) 
		{ 
			this.cbData.m_appcls.data_file_id = fid; 
			this.cbData.m_appcls.data_file_name = name; 
			alib.dom.styleSet(this.cbData.div_display, "border", "1px solid");
			this.cbData.div_display.innerHTML = "Selected File: " + name;
		};
		cfupload.showTmpUpload(div_upload, div_res, 'Upload CSV File', 1);


		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "csv";
		cbrowser.cbData.m_appcls = this;
		cbrowser.cbData.div_display = div_display;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cbData.m_appcls.data_file_id = fid; 
			this.cbData.m_appcls.data_file_name = name; 
			alib.dom.styleSet(this.cbData.div_display, "border", "1px solid");
			this.cbData.div_display.innerHTML = "Selected File: " + name;
		}

		a_browse.innerHTML = "Select a file from ANT File System";
		a_browse.href = 'javascript:void(0);';
		a_browse.cbrowser = cbrowser;
		a_browse.m_dlg = this.m_dlg;
		a_browse.onclick = function() { this.cbrowser.showDialog(this.m_dlg); }

		// Import Definition
		// ------------------------------------------------------------------
		var lbl = alib.dom.createElement("div", div_main);
		lbl.innerHTML = "Would you like to:";
		alib.dom.styleSetClass(lbl, "formLabel");
		
		this.cbTemplates = alib.dom.createElement("select", div_main);
		var cbTemplates = this.cbTemplates;
		cbTemplates.size = 10;
		cbTemplates.style.width = "98%";
		cbTemplates.cls = this;
		cbTemplates.onchange = function()
		{
			if (this.value)
			{
				this.cls.template_id = this.value;
				this.cls.setTemplate(this.value);
			}
		}

		var btn_delt = new CButton("Delete Selected Template", function(cls, cbTemplates) { cls.deleteTemplates(cbTemplates);  }, [this, cbTemplates]);
		btn_delt.disable();

		var div_new = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'create';
		rbtn1.checked = (this.template_id) ? false : true;
		rbtn1.cbTemplates = cbTemplates;
		rbtn1.btn_delt = btn_delt;
		rbtn1.cls = this;
		rbtn1.onchange = function() {  cbTemplates.disabled = true; this.btn_delt.disable(); this.cls.template_id = null; }
		div_new.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_new);
		lbl.innerHTML = " Create New Data Import";

		var div_template = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'create';
		rbtn1.checked = (this.template_id) ? true : false;
		rbtn1.cbTemplates = cbTemplates;
		rbtn1.btn_delt = btn_delt;
		rbtn1.onchange = function() {  cbTemplates.disabled = false; this.btn_delt.enable(); /* set to template */ }
		div_template.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_template);
		lbl.innerHTML = " Use Import Template";

		var div_select = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");
		cbTemplates.disabled = true;
		div_select.appendChild(cbTemplates);

		btn_delt.print(div_select);

		this.veriftyStep = function()
		{
			if (!this.data_file_id)
			{
				this.verify_step_data.message = "Please upload a CSV file before continuing";
				return false;
			}
			else
				return true;
		}

		// Load templates
		if (!this.templates.length)
			this.loadTemplates();
		else
			this.populateTemplates();	

		break;
	case 1:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Determine which property is defined by each column: ";

		// Map fields
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "height", "380px");
		alib.dom.styleSet(dv, "border", "1px solid");
		alib.dom.styleSet(dv, "overflow", "auto");

		dv.innerHTML = "<div class='loading></div>";

		this.loadColumns(dv);
	
		break;
	case 2:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Set default values (each object will inherit these values if not defined in imported data):";

		// Map fields
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "height", "380px");
		alib.dom.styleSet(dv, "border", "1px solid");
		alib.dom.styleSet(dv, "overflow", "auto");

		var tbl = new CToolTable("100%");
		tbl.addHeader("Property", "left", "100px");
		tbl.addHeader("Value");
		tbl.print(dv);

		var fields = this.mainObject.getFields();
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].name == "account_id" || fields[i].readonly)
				continue;

			var row = tbl.addRow();
			row.addCell(fields[i].title);

			var inp_div = alib.dom.createElement("div");
			this.mainObject.fieldGetValueInput(inp_div, fields[i].name);
			row.addCell(inp_div);
		}

		break;
	case 3:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Update existing "+this.mainObject.titlePl+" if the following conditions are met:";
		var p = alib.dom.createElement("p", div_main);
		alib.dom.styleSet(p, "margin", "3px 0 6px 0");
		p.innerHTML = "Tip: Select multiple matches for increased accuracy. For example, selecting \"First Name\" and \"Last Name\" and \"Email\" will only merge imported data with an existing record if all three conditions are met. If \"First Name\" only is selected, then there is a high probability that multiple matches will be returned and you may end up with duplicate records.";

		var div_merge = alib.dom.createElement("div");
		alib.dom.styleSet(div_merge, "display", "none");
		alib.dom.styleSet(div_merge, "height", "295px");
		alib.dom.styleSet(div_merge, "border", "1px solid");
		alib.dom.styleSet(div_merge, "overflow", "auto");

		var div_new = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'merge';
		rbtn1.checked = true;
		rbtn1.cls = this;
		rbtn1.div_merge = div_merge;
		rbtn1.onclick = function() { this.div_merge.style.display = "none"; }
		div_new.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_new);
		lbl.innerHTML = " Do not merge data - enter each row as a new record";

		var div_template = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'merge';
		rbtn1.checked = false;
		rbtn1.div_merge = div_merge;
		rbtn1.onclick = function() { this.div_merge.style.display = "block"; }
		div_template.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_template);
		lbl.innerHTML = " Try to merge data with existing records if match is found";

		div_main.appendChild(div_merge);

		for (var i = 0; i < this.data_file_columns.length; i++)
		{
			if (this.data_file_columns[i].mapTo != "")
			{
				var dv = alib.dom.createElement("div", div_merge);

				var ck = alib.dom.createElement("input");
				ck.type = "checkbox";
				ck.value = this.data_file_columns[i].mapTo;
				ck.cls = this;
				ck.onclick = function()
				{
					if (this.checked)
					{
						var bFound = false;

						for (var i = 0; i < this.cls.merge_by.length; i++)
						{
							if (this.cls.merge_by[i] == this.value)
								bFound == true;
						}

						if (!bFound)
							this.cls.merge_by[this.cls.merge_by.length] = this.value;
					}
					else
					{
						for (var i = 0; i < this.cls.merge_by.length; i++)
						{
							if (this.cls.merge_by[i] == this.value)
								this.cls.merge_by.splice(i, 1);
						}
					}
				}
				dv.appendChild(ck);

				var lbl = alib.dom.createElement("span", dv);
				lbl.innerHTML = " Column \"" + this.data_file_columns[i].colName 
								+ "\" in imported data matches property \"" + this.data_file_columns[i].mapTo + "\" in "+this.mainObject.title;
			}
		}

		break;
	case 4:
		var div_working = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_working, "margin", "5px 0 5px 0");

		var div_working = alib.dom.createElement("div", div_main);

		var div_done = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_done, "display", "none");
		alib.dom.styleSet(div_done, "margin", "5px 0 5px 0");

		var fieldset = alib.dom.createElement("fieldset", div_done);
		alib.dom.styleSet(fieldset, "margin", "20px 0 5px 0");
		var legend = alib.dom.createElement("legend", fieldset);
		legend.innerHTML = "Save Template (optional)";

		var inp_name = alib.dom.createElement("input"); // created first for reference

		if (this.template_id)
		{
			var dv = alib.dom.createElement("div", fieldset);
			alib.dom.styleSet(dv, "margin", "5px 0 5px 0");

			var lbl = alib.dom.createElement("span", dv);
			alib.dom.styleSetClass(lbl, "formLabel");
			lbl.innerHTML = "Save Changes: ";
			var inp = alib.dom.createElement("input");
			inp.type = "checkbox";
			inp.checked = false;
			inp.cls = this;
			inp.inp_name = inp_name;
			inp.onclick = function() { this.cls.save_template_changes = (this.checked) ? 't' : 'f'; this.inp_name.disabled = (this.checked) ? true : false; };
			dv.appendChild(inp);
			var lbl = alib.dom.createElement("span", dv);
			lbl.innerHTML = " (save the changes I have made to this template)";
		}

		var lbl = alib.dom.createElement("span", fieldset);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Save As: ";
		inp_name.type = "text";
		inp_name.style.width = "100px";
		inp_name.cls = this;
		inp_name.onchange = function() { this.cls.save_template_name = this.value; };
		fieldset.appendChild(inp_name);

		var lbl = alib.dom.createElement("span", fieldset);
		lbl.innerHTML = " (save settings to re-use later, leave blank if you do not wish to save)";

		var dv = alib.dom.createElement("div", fieldset);
		alib.dom.styleSet(dv, "margin", "5px 0 5px 0");

		this.importData(div_working, div_done);

		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(dv_btn, "wizardFooter");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.save(); }, [this]);
		btn.print(dv_btn);
	}
	else
	{
		var next_funct = function(cls, step)
		{
			if (cls.veriftyStep())
			{
				cls.showStep(step+1);
			}
			else
			{
				ALib.Dlg.messageBox(cls.verify_step_data.message, cls.m_dlg);
			}
		}

		var btn = new CButton("Next", next_funct, [this, step], "b2");
		btn.print(dv_btn);
	}

	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [this.m_dlg], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
CAntObjectImpWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntObjectImpWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntObjectImpWizard.prototype.onFinished = function()
{
}


/*************************************************************************
*	Function:	save
*
*	Purpose:	Save this import template
**************************************************************************/
CAntObjectImpWizard.prototype.save = function()
{
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
	else
	{
		this.m_dlg.hide();
		this.onFinished();
	}
}

/*************************************************************************
*	Function:	loadColumns
*
*	Purpose:	Get column headers for selected file
**************************************************************************/
CAntObjectImpWizard.prototype.loadColumns = function(con)
{
	/*function cbdone(ret, cls, con)
	{
		if (ret)
		{
			con.innerHTML = "";
			var tbl = new CToolTable();
			tbl.addHeader("CSV Column");
			tbl.addHeader(cls.mainObject.title + " Property");
			tbl.print(con);

			var cols = eval(ret);
			for (var i = 0; i < cols.length; i++)
			{
				cls.data_file_columns[i] = new Object();
				cls.data_file_columns[i].colName = cols[i];
				cls.data_file_columns[i].mapTo = "";

				cls.addColumn(tbl, cols[i], cls.data_file_columns[i]);
			}
		}
	}

	var args = [["data_file_id", this.data_file_id]];
	var rpc = new CAjaxRpc("/objects/xml_import_actions.php", "get_csv_headers", args, cbdone, [this, con], AJAX_POST);*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = con;
    ajax.onload = function(cols)
    {
        if (cols)
        {
            this.cbData.con.innerHTML = "";
            var tbl = new CToolTable();
            tbl.addHeader("CSV Column");
            tbl.addHeader(this.cbData.cls.mainObject.title + " Property");
            tbl.print(this.cbData.con);

            for (var i = 0; i < cols.length; i++)
            {
                this.cbData.cls.data_file_columns[i] = new Object();
                this.cbData.cls.data_file_columns[i].colName = cols[i];
                this.cbData.cls.data_file_columns[i].mapTo = "";

                this.cbData.cls.addColumn(tbl, cols[i], this.cbData.cls.data_file_columns[i]);
            }
        }
    };
    var args = [["data_file_id", this.data_file_id]];
    ajax.exec("/controller/Object/importGetHeaders", args);
}

/*************************************************************************
*	Function:	importData
*
*	Purpose:	Import data into objects
**************************************************************************/
CAntObjectImpWizard.prototype.importData = function(div_working, div_done)
{
	div_working.innerHTML = "<h2>Starting import, please wait...</h2><div class='loading'></div>";

	var args = [["data_file_id", this.data_file_id], ["obj_type", this.mainObject.name]];

	for (var i = 0; i < this.data_file_columns.length; i++)
	{
		args[args.length] = ["map_fields[]", this.data_file_columns[i].mapTo];
	}

	for (var i = 0; i < this.merge_by.length; i++)
	{
		args[args.length] = ["merge_by[]", this.merge_by[i]];
	}

	var fields = this.mainObject.getFields();
	for (var f = 0; f < fields.length; f++)
	{
		if (fields[f].type == "fkey_multi")
		{
			var mvals = this.mainObject.getMultiValues(fields[f].name);

			if (mvals)
			{
				for (var m = 0; m < mvals.length; m++)
				{
					//ALib.m_debug = true;
					//ALib.trace(fields[f].name+"[]: " + mvals[m]);
					args[args.length] = [fields[f].name+"[]", mvals[m]];
				}
			}
		}
		else
		{
			var val = this.mainObject.getValue(fields[f].name);

			if (val)
				args[args.length] = [fields[f].name, val];
		}
	}
    
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.div_working = div_working;
    ajax.cbData.div_done = div_done;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            this.cbData.div_working.innerHTML = "<h2>Data is being processed</h2><p class='notice'>This import will continue to run in the background and may take some time. You will be emailed when the process is completed. Click the \"Finish\" button below to continue working. If you have more than one CSV to import, feel free to start the import at any time. Thank you for your patience.</p>";
            this.cbData.div_done.style.display = "block";
        }
    };
    ajax.exec("/controller/Object/importRun", args);
}

/*************************************************************************
*	Function:	addColumn
*
*	Purpose:	Get column headers for selected file
**************************************************************************/
CAntObjectImpWizard.prototype.addColumn = function(tbl, colname, data_ent)
{
	var row = tbl.addRow();

	row.addCell(colname);

	// Add field name
	var field_sel = alib.dom.createElement("select");
	field_sel.data_ent = data_ent;
	field_sel[field_sel.length] = new Option("Do Not Import", '');
	field_sel[field_sel.length] = new Option("Create New Field", 'ant_create_field');
	field_sel[field_sel.length] = new Option("Create New Field With Dropdown", 'ant_create_field_dd');
	field_sel.onchange = function() { this.data_ent.mapTo = this.value; };
	var fields = this.mainObject.getFields();
	for (var f = 0; f < fields.length; f++)
	{
		if ((fields[f].type != "fkey_multi" || fields[f].name == "groups") && fields[f].name != "account_id" && fields[f].readonly != true)
		{
			var selected = (fields[f].name == colname || fields[f].title == colname)?true:false;
			if (this.data_file_maps.length)
				selected = this.colMapSet(colname, fields[f].name);

			field_sel[field_sel.length] = new Option(fields[f].title, fields[f].name, false, selected);
			if (selected)
			{
				data_ent.mapTo = fields[f].name;
			}
		}
	}
	row.addCell(field_sel);
}

/*************************************************************************
*	Function:	colMapSet
*
*	Purpose:	Check if a map has been set for this field
**************************************************************************/
CAntObjectImpWizard.prototype.colMapSet = function(colname, propertyName)
{
	for (var i = 0; i < this.data_file_maps.length; i++)
	{
		var map = this.data_file_maps[i];

		if (map.colName == colname && map.propertyName == propertyName)
			return true;
	}

	return false;
}

/*************************************************************************
*	Function:	loadTemplates
*
*	Purpose:	Load previously saved templates
**************************************************************************/
CAntObjectImpWizard.prototype.loadTemplates = function()
{
	var ajax = new CAjax();
	ajax.cls = this;
	ajax.onload = function(root)
	{
		if (root.getNumChildren())
		{
			for (var i = 0; i < root.getNumChildren(); i++)
			{
				var child = root.getChildNode(i);

				var template = new Object();

				template.id 	= child.getChildNodeValByName("id");
				template.name	= unescape(child.getChildNodeValByName("name"));
				template.maps	= new Array();

				var maps_node = child.getChildNodeByName("maps");
				if (maps_node && maps_node.getNumChildren())
				{
					for (var j = 0; j < maps_node.getNumChildren(); j++)
					{
						var m_child = maps_node.getChildNode(j);

						var idx = template.maps.length;
						template.maps[idx] = new Object();
						template.maps[idx].colName = unescape(m_child.getChildNodeValByName("col_name"));
						template.maps[idx].propertyName = unescape(m_child.getChildNodeValByName("property_name"));
					}
				}

				this.cls.templates[this.cls.templates.length] = template;
			}
		}

		this.cls.populateTemplates();
	};

	var url = "/objects/xml_import_actions.php?function=get_templates&obj_type="+this.mainObject.name;
	ajax.exec(url);
}

/*************************************************************************
*	Function:	populateTemplates
*
*	Purpose:	Place templates in select box
**************************************************************************/
CAntObjectImpWizard.prototype.populateTemplates = function()
{
	if (!this.cbTemplates)
		return;

	for (var i = 0; i < this.templates.length; i++)
	{
		var template = this.templates[i];

		this.cbTemplates[this.cbTemplates.length] = new Option(template.name, template.id, false, (template.id == this.template_id)?true:false);
	}
}

/*************************************************************************
*	Function:	getTemplateById
*
*	Purpose:	Get a template by id
**************************************************************************/
CAntObjectImpWizard.prototype.setTemplate = function(id)
{
	for (var i = 0; i < this.templates.length; i++)
	{
		if (this.templates[i].id == id)
		{
			this.data_file_maps = new Array();

			for (var j = 0; j < this.templates[i].maps.length; j++)
			{
				this.data_file_maps[j] = new Object();
				this.data_file_maps[j].colName = this.templates[i].maps[j].colName;
				this.data_file_maps[j].propertyName = this.templates[i].maps[j].propertyName;
			}

			return;
		}
	}
}

/*************************************************************************
*	Function:	deleteTemplates
*
*	Purpose:	Delete a template
**************************************************************************/
CAntObjectImpWizard.prototype.deleteTemplates = function(cbTemplates)
{
	/*function cbdone(ret, cls, cbTemplates)
	{
		if (ret)
		{
			for (var i = 0; i < cbTemplates.options.length; i++)
			{
				if (cbTemplates.options[i].selected)
					cbTemplates.options[i] = null;
			}
		}
	}
	var args = [["tid", cbTemplates.value]];
	var rpc = new CAjaxRpc("/email/xml_import_actions.php", "delete_import_template", args, cbdone, [this, cbTemplates]);*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.cbTemplates = cbTemplates;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            for (var i = 0; i < this.cbData.cbTemplates.options.length; i++)
            {
                if (this.cbData.cbTemplates.options[i].selected)
                    this.cbData.cbTemplates.options[i] = null;
            }
        }
    };
    var args = [["function", "delete_import_template"], ["tid", cbTemplates.value]];
    ajax.exec("/email/xml_import_actions.php", args);
}
