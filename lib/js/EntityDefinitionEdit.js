/**
* @fileOverview Ant.EntityDefinitionEdit: Dialog to edit properties of an object
*
* @author: joe, sky.stebnicki@aereus.com; 
* Copyright (c) 2011-13 Aereus Corporation. All rights reserved.
*/
Ant.EntityDefinitionEdit = function(object_type)
{
	this.obj_type = object_type;
	this.g_antObject = new CAntObject(object_type, null);
	this.form_tbl = new CToolTable("100%");
    this.g_tbl = new CToolTable("100%");
	this.viewTbl = new CToolTable("100%");
	this.frmObj = new Object();
	this.default_mobile_form = false;
	this.default_form = false;
	this.g_references = [];
	this.g_theme = Ant.m_theme;
	this.getObjects();
}


/**
* Display dialog
*
* @param {object} parentDlg Dialog of parent
*/
Ant.EntityDefinitionEdit.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Object Editor", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");
	
	dlg.customDialog(this.body_dv, 800);
	this.buildInterface();
	dlg.reposition();
}

/**
* Build object edit interface
*/
Ant.EntityDefinitionEdit.prototype.buildInterface = function()
{	
	var main_con = alib.dom.createElement("div", this.body_dv);
	//alib.dom.styleSetClass(main_con, "wizardBody"); // removed to clear the dual scrollbars
	main_con.innerHTML = "";
	
	// Create add table frame
	var frm_gen = new CWindowFrame("General Properties", null, "3px");
	var frmcon = frm_gen.getCon();
	this.buildGeneralForm(frmcon);
	frm_gen.print(main_con);

	// Add tabs
	// --------------------------------------
	var tabs = new CTabs();
	var tabcon1 = tabs.addTab("Properties");	// Properties Details
	this.tabProperties(tabcon1);
	var tabcon2 = tabs.addTab("Forms");			// Forms
	tabcon2.id = "form_tab";
	this.tabForm(tabcon2);
	
    var viewsTab = tabs.addTab("Views");     // Views            
    viewsTab.id = "tabView";
    this.tabView(viewsTab);

    var browseTab = tabs.addTab("Browse");     // Views            
    browseTab.id = "tabBrose";
    this.tabBrowse(browseTab);
    
    // Print all tabs
    tabs.print(main_con);
	
	// Buttons
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(dv_btn, "wizardFooter");

	/*
	var btn = new CButton("Save &amp; Close", function(cls){ cls.saveObject(true); }, [this], "b1");
	btn.print(dv_btn);
	var btn = new CButton("Save Changes", function(cls){ cls.saveObject(); }, [this], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(cls){ cls.m_dlg.hide(); }, [this], "b3");
	btn.print(dv_btn);
	*/

	var btn = new CButton("Close", function(cls){ cls.refreshLocalObject(); cls.m_dlg.hide(); }, [this], "b1");
	btn.print(dv_btn);
}


/**
* Build main form
*
* @param {object} con Main container element
*/
Ant.EntityDefinitionEdit.prototype.buildGeneralForm = function(con)
{
	var tbl = alib.dom.createElement("table");
	con.appendChild(tbl);
	var tbody = alib.dom.createElement("tbody", tbl);

	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "label");
	td.innerHTML = "Title: ";
	var txtTitle = alib.dom.createElement("input");
	txtTitle.type = "text";
	txtTitle.setAttribute('maxlength', 256);
	txtTitle.value = this.g_antObject.title;
	txtTitle.onchange = function() { this.g_antObject.title = this.value; }
	td.appendChild(txtTitle);

	// Permissions
	var td = alib.dom.createElement("td", row);
    var btn = alib.ui.Button("Edit Permissions", {
		className:"b2", editCls:this,
		onclick:function() {
			loadDacl(null, '/objects/' + this.editCls.obj_type);
		}
	});                            
	btn.print(td);
}

/**
* Build properties tab
*
* @param {object} con Container of properties tab
*/
Ant.EntityDefinitionEdit.prototype.tabProperties = function(con)
{
	// Create add table frame
	var frm1 = new CWindowFrame("Add New Property", null, "3px");
	var frmcon = frm1.getCon();
	this.buildNewColForm(frmcon);
	frm1.print(con);

	// Create cols table
	var frm2 = new CWindowFrame("Properties");
	var frmcon2 = frm2.getCon();
	alib.dom.styleSet(frmcon2, "height", "258px");
	alib.dom.styleSet(frmcon2, "overflow", "auto");
	this.loadColumns(frmcon2);
	frm2.print(con);	
}

/**
* Create container to add a new field
*
* @param {object} con CWindowFrame container
*/
Ant.EntityDefinitionEdit.prototype.buildNewColForm = function(con)
{
	var tbl = ALib.m_document.createElement("table");
	tbl.cls = this;
	con.appendChild(tbl);
	var tbl_bdy = ALib.m_document.createElement("tbody");
	tbl.appendChild(tbl_bdy);
	var row = null;
	var td = null;

	// Add column name
	row = ALib.m_document.createElement("tr");
	tbl_bdy.appendChild(row);
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "Title";
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	this.frmObj.m_colname = ALib.m_document.createElement("input");
	td.appendChild(this.frmObj.m_colname);
	
	// Add column type
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "Type";
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	this.frmObj.m_coltype = ALib.m_document.createElement("select");
	this.frmObj.m_coltype.cls = this;
	this.frmObj.m_coltype.onchange = function() { this.cls.viewSubtype(this.value); }
	td.appendChild(this.frmObj.m_coltype);
	var opt = null;
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("select type", "");	
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("text", "text");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("number", "numeric");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("date", "date");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("date & time", "timestamp");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("auto incremented number", "serial");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("boolean: yes/no", "bool");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("category", "fkey");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("categories: multiple", "fkey_multi");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("File", "file");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("Folder", "folder");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("object reference: single", "object");
	this.frmObj.m_coltype[this.frmObj.m_coltype.length] = new Option("object reference: multiple", "object_multi");

	var td = alib.dom.createElement("td", row);
	td.id = "subtype_lbl";
	var td = alib.dom.createElement("td", row);
	td.id = "subtype_inp";

	// Add button
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "&nbsp;";
	td = ALib.m_document.createElement("td");
	row.appendChild(td);
	var btn = new CButton("Add", function(cls){ cls.createProperty(); }, [this], "b1");
	td.appendChild(btn.getButton());
}

/**
* Create subtype dropdown option based on type
*
* @param {string} type type selected
*/
Ant.EntityDefinitionEdit.prototype.viewSubtype = function(type)
{
	var subtconlbl = document.getElementById("subtype_lbl");
	var subtcon = document.getElementById("subtype_inp");
	subtconlbl.innerHTML = "";
	subtcon.innerHTML = "";
	this.frmObj.subtype = "";
	this.frmObj.fkey_table_key = "";
	this.frmObj.fkey_table_title = "";

	switch (type)
	{
	case 'text':
		subtconlbl.innerHTML = "Length: ";
		var sel = alib.dom.createElement("select", subtcon);
		sel.cls = this;
		sel.onchange = function() { this.cls.frmObj.subtype = this.value; }
		sel[sel.length] = new Option("Unlimited", "");
		sel[sel.length] = new Option("Up To 512 Chars", "512");
		sel[sel.length] = new Option("Up To 256 Chars", "256");
		sel[sel.length] = new Option("Up To 128 Chars", "128");
		sel[sel.length] = new Option("Up To 64 Chars", "64");
		sel[sel.length] = new Option("Up To 32 Chars", "32");
		sel[sel.length] = new Option("Up To 16 Chars", "16");
		sel[sel.length] = new Option("Up To 8 Chars", "8");
		break;
	case 'fkey':
	case 'fkey_multi':
		this.frmObj.subtype = "object_groupings";
		break;
	/*
	case 'fkey':
	case 'fkey_multi':
		subtconlbl.innerHTML = "References: ";
		var sel = alib.dom.createElement("select", subtcon);
		sel.cls = this;
		sel.onchange = function() 
		{ 
			this.cls.frmObj.subtype = this.value; 
			for(var i = 0; i < this.cls.g_references.length; i++)
			{
				if(this.cls.g_references[i][1] == this.value)
				{
					this.cls.frmObj.fkey_table_key = this.cls.g_references[i][3];
					this.cls.frmObj.fkey_table_title = this.cls.g_references[i][4];
				}
			}
		}

		for (var i = 0; i < this.cls.g_references.length; i++)
		{
			sel[sel.length] = new Option(this.cls.g_references[i][0], this.cls.g_references[i][2]);
		}

		this.cls.frmObj.fkey_table_key = this.cls.g_references[0][3];
		this.cls.frmObj.fkey_table_title = this.cls.g_references[0][4];
		break;
		*/
	case 'object':
	case 'object_multi':
		subtconlbl.innerHTML = "References: ";
		var sel = alib.dom.createElement("select", subtcon);
		sel.cls = this;
		sel.onchange = function() 
		{ 
			this.cls.frmObj.subtype = this.value; 
		}

		for (var i = 0; i < this.g_references.length; i++)
		{
			sel[sel.length] = new Option(this.g_references[i][0], this.g_references[i][1]);
		}
		break;
	case '':
		subtconlbl.innerHTML = "";
		break;
	}
}

/**
 * Create Property
 */
Ant.EntityDefinitionEdit.prototype.createProperty = function()
{
	var xhr = new alib.net.Xhr();

	// Setup callback
	alib.events.listen(xhr, "load", function(evt) { 
		var data = this.getResponse();

		if (data.error)
		{
			alert(data.error);
		}
		else
		{
			// Clear col title for next property
			evt.data.editClass.frmObj.m_colname.value = "";

			// Add field to the table
			evt.data.editClass.addProperty(data);

			// Refresh the object definition
			evt.data.editClass.refreshLocalObject();
		}

	}, {editClass:this});

	// There was some error like a timeout
	alib.events.listen(xhr, "error", function(evt) { 
	}, {editClass:this});

	// Send field to controller
	var fieldData = {
		obj_type : this.obj_type,
		name : this.escapePropertyTitle(this.frmObj.m_colname.value),
		title : this.frmObj.m_colname.value,
		notes : "",
		type : this.frmObj.m_coltype.value,
		subtype : this.frmObj.subtype,
		readonly : false,
		system : false,
		required : false
	};
	/*
	field.fkey_table_key 	= this.frmObj.fkey_table_key;
	field.fkey_table_title	= this.frmObj.fkey_table_title;
	*/

	var ret = xhr.send("/controller/Object/addField", "POST", fieldData);

	/* This was the old code
	this.g_antObject.addField(field);
	this.addProperty(field);
	*/
}

/**
 * Make field.name lowercase alphanumeric
 * @param {string} title Field.title
 */
Ant.EntityDefinitionEdit.prototype.escapePropertyTitle = function(title)
{
	var name = title.toLowerCase();
	name = name.replace(" ", "_");
	//name = namestr.replace("'", "");
	name = name.replace(/[^a-zA-Z0-9_]+/g,'');
	return name;
}

/**
* Add new field to Properties table
* @param {string} field Field to add
*/
Ant.EntityDefinitionEdit.prototype.addProperty = function(field)
{
	if (field.name && field.type)
	{
		var rw = this.g_tbl.addRow();
		// Add name
		rw.addCell(field.title);
		rw.addCell(field.name);
		// Add type
		rw.addCell(field.type);
		// Add required
		var cb = alib.dom.createElement("input");
		cb.type = "checkbox";
		cb.checked = field.required;
		cb.fname = field.name;
		cb.cls = this;
		if (field.readonly)
			cb.disabled = true;
		else
			cb.onclick = function() { this.cls.setFieldRequired(this.fname, this.checked); }
		rw.addCell(cb, false, "center");
		// Add dropdown
		var dd_div = alib.dom.createElement("div");
		if (!field.readonly && (field.type == "text" || field.type == "number"))
		{
			var ddlnk = alib.dom.createElement("a", dd_div);
			ddlnk.href = 'javascript:void(0);'
			ddlnk.innerHTML = "edit drop-down values";
			ddlnk.fname = field.name;
			ddlnk.cls = this;
			ddlnk.onclick = function() { this.cls.fieldEditOptionalValues(this.fname); }
		}
		else if (!field.readonly && (field.type == "fkey" || field.type == "fkey_multi"))
		{
			var ddlnk = alib.dom.createElement("a", dd_div);
			ddlnk.href = 'javascript:void(0);'
			ddlnk.innerHTML = "edit values";
			ddlnk.fname = field.name;
			ddlnk.cls = this;
			ddlnk.onclick = function() { this.cls.fieldEditGroupingValues(this.fname); }
		}
		rw.addCell(dd_div, false, "center");
		// Add default 
		var def_div = alib.dom.createElement("div");
		if (!field.readonly && field.type != "fkey_multi" && field.type != "object_multi")
		{
			var ddlnk = alib.dom.createElement("a", def_div);
			ddlnk.href = 'javascript:void(0);'
			ddlnk.innerHTML = "set default";
			ddlnk.fname = field.name;
			ddlnk.cls = this;
			ddlnk.onclick = function() { this.cls.fieldLoadDefault(this.fname); }
		}
		rw.addCell(def_div, false, "center");
		// Add delete
		if (field.system)
		{
			rw.addCell("&nbsp;", true, "center");
		}
		else
		{
			var del_dv = ALib.m_document.createElement("div");
			rw.addCell(del_dv, true, "center");
			del_dv.innerHTML = "<img border='0' src='/images/icons/delete_16.png' />";
			alib.dom.styleSet(del_dv, "cursor", "pointer");
			del_dv.m_rw = rw;
			del_dv.m_name = field.name;
			del_dv.cls = this;
			del_dv.onclick = function()
			{
				if (confirm("Are you sure you want to delete "+this.m_name+"?"))
				{
					this.cls.deleteCol(this.m_name, this.m_rw);
				}
			}
		}
	}
}

/**
* Set field required
* @param {string} fname = field name
* @param {boolean} required
*/
Ant.EntityDefinitionEdit.prototype.setFieldRequired = function(fname, required)
{
	// Check for existing field
	var field = this.g_antObject.getFieldByName(fname);
	if(field) // Update existing field
	{
        ajax = new CAjax('json');        
        var args = [["obj_type", this.g_antObject.name], ["name", fname], ["required", (required)?'t':'f']];
        ajax.exec("/controller/Object/fieldSetRequired", args);
	}
	else
	{
		for(var i = 0; i < this.g_antObject.addFields.length; i++)
		{
			if(this.g_antObject.addFields[i].name == fname)
				this.g_antObject.addFields[i].required = required;
		}
	}
}

/**
* Edit optional values of field
* @param {string} fname Field name
*/
Ant.EntityDefinitionEdit.prototype.fieldEditOptionalValues = function(fname)
{
	var field = this.g_antObject.getFieldByName(fname);
	if (!field)
	{
		ALib.Dlg.messageBox("Please save changes before editing optional values for this field", this.m_dlg);
		return;
	}

	var dlg = new CDialog("Optional Values for " + field.title, this.m_dlg);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 300);

	var cbValues = alib.dom.createElement("select");

	// new value
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Add New Value";
	var inp_dv = alib.dom.createElement("div", dv);
	var txtVal = alib.dom.createElement("input", inp_dv);
	alib.dom.styleSet(txtVal, "width", "220px");
	alib.dom.styleSet(txtVal, "margin-right", "3px");
	var btn = new CButton("Add", 
	function(cls, txtVal, cbValues, fname) 
	{ 
		cls.fieldEditOptionalValuesAdd(txtVal, cbValues, fname);
	}, [this, txtVal, cbValues, field.name], "b2");
	btn.print(inp_dv);

	// optional values
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Drop-Down Values";
	var inp_dv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(cbValues, "width", "98%");
	cbValues.size = 10;
	inp_dv.appendChild(cbValues);

	if (field.optional_vals && field.optional_vals.length)
	{
		for (var i = 0; i < field.optional_vals.length; i++)
			cbValues[cbValues.length] = new Option(field.optional_vals[i][1], field.optional_vals[i][0]);
	}

	var del = alib.dom.createElement("a", inp_dv);
	del.innerHTML = "Delete Selected";
	del.href = "javascript:void(0);";
	del.options = {cls:this, cbv:cbValues, fname:field.name}
	del.onclick = function()
	{
		this.options.cls.fieldEditOptionalValuesDeleteSel(this.options.cbv, this.options.fname);
	}

	// Action Buttons
	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");
	var btn = new CButton("Done", function(dlg) { dlg.hide(); }, [dlg], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg], "b3");
	btn.print(dv_btn);

	// make sure we are centered
	dlg.reposition();
}

/**
* Add optional values of field
* @param {string} txt Text Value
* @param {string} cb cb values
* @param {string} fname Field Names
*/
Ant.EntityDefinitionEdit.prototype.fieldEditOptionalValuesAdd = function(txt, cb, fname)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.txt = txt;
    ajax.cbData.cb = cb;    
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if (!ret.error)
        {
            this.cbData.cb[this.cbData.cb.length] = new Option(this.cbData.txt.value, this.cbData.txt.value); 

            var field = this.cbData.cls.g_antObject.getFieldByName(fname);
            if (field && field.optional_vals)
                field.optional_vals[field.optional_vals.length] = [this.cbData.txt.value, this.cbData.txt.value, false, ""];

            this.cbData.txt.value='';
        }
    };
    var args = [["obj_type", this.g_antObject.name], ["name", fname], ["value", txt.value]];
    ajax.exec("/controller/Object/fieldAddOption", args);
}

/**
* Delete optional values of field
* @param {string} cb cb values
* @param {string} fname Field Names
*/
Ant.EntityDefinitionEdit.prototype.fieldEditOptionalValuesDeleteSel = function(cb, fname)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.cb = cb;
    ajax.cbData.fname = fname;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if (!ret.error)
        {
            var field = this.cbData.cls.g_antObject.getFieldByName(this.cbData.fname);
            if (field && field.optional_vals && field.optional_vals.length)
            {
                for (var i = 0; i < field.optional_vals.length; i++)
                {
                    if (field.optional_vals[i][0] == this.cbData.cb.value)
                        field.optional_vals.splice(i, 1);
                }
            }

            for (var i = 0; i < this.cbData.cb.options.length; i++)
            {
                if (this.cbData.cb.options[i].selected)
                    this.cbData.cb.options[i] = null;
            }
        }
    };
    var args = [["obj_type", this.g_antObject.name], ["name", fname], ["value", cb.value]];
    ajax.exec("/controller/Object/fieldDeleteOption", args);
}

/**
* Edit optional values of field
* @param {string} fname Field name
*/
Ant.EntityDefinitionEdit.prototype.fieldEditGroupingValues = function(fname)
{
	var field = this.g_antObject.getFieldByName(fname);
	if (!field)
	{
		ALib.Dlg.messageBox("Please save changes before editing optional values for this field", this.m_dlg);
		return;
	}

	var dlg = new CDialog("Values for " + field.title, this.m_dlg);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 500);

	// Pre-create for the add link below
	var inp_dv = alib.dom.createElement("div");

	// Add new grouping
	var addcon = alib.dom.createElement("div", dv);
	alib.dom.styleSet(addcon, "padding", "5px");
	var add = alib.dom.createElement("a", addcon);
	add.innerHTML = "Add Value";
	add.href = "javascript:void(0);";
	add.con = inp_dv;
	add.fname = fname;
	add.cls = this;
	add.onclick = function()
	{
		this.cls.addGrouping(this.con, this.fname);
	}

	// optional values
	dv.appendChild(inp_dv);
	alib.dom.styleSet(inp_dv, "height", "200px");
	alib.dom.styleSet(inp_dv, "overflow", "auto");

	this.fieldGroupingsLoadValues(fname, inp_dv)

	// Action Buttons
	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");
	var btn = new CButton("Done", function(dlg) { dlg.hide(); }, [dlg], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg], "b3");
	btn.print(dv_btn);

	// make sure we are centered
	dlg.reposition();
}

/**
* Get grouping values
*
* @param {string} fname Field Names
*/
Ant.EntityDefinitionEdit.prototype.fieldGroupingsLoadValues = function(fname, con)
{
	con.innerHTML = "";

	var tbl = new CToolTable("100%");

	tbl.addHeader("Id", "center", "15px");
	tbl.addHeader("Name (click to rename)");
	//tbl.addHeader("Sort Order", "center", "70px");
	//tbl.addHeader("Closed", "center", "60px");
	tbl.addHeader("Color", "center", "50px");
	tbl.addHeader("Delete", "center", "20px");

	tbl.print(con);

	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.tbl = tbl;
	ajax.cbData.fname = fname;
	ajax.onload = function(groupings)
	{
		if (groupings.length)
		{
			this.cbData.cls.populateGroupings(this.cbData.tbl, groupings, this.cbData.fname);
		}
	};
	ajax.exec("/controller/Object/getGroupings", 
			  [["obj_type", this.obj_type], ["field", fname]]);
}

/**
* Populate groupings into a row
*
* @this {Ant.EntityDefinitionEdit}
* @param {CToolTable} tbl The table we are printing all the groups to
* @param {Array} groupings Array of groupings objects
* @param {string} fname The name of the field we are updating
*/
Ant.EntityDefinitionEdit.prototype.populateGroupings = function(tbl, groupings, fname)
{
	for (var i = 0; i < groupings.length; i++)
	{
		var row = tbl.addRow();

		// Rename
		var rena = alib.dom.createElement("a");
		rena.href = "javascript:void(0)";
		rena.innerHTML = groupings[i].title;
		rena.cls = this;
		rena.curname = groupings[i].title;
		rena.gid = groupings[i].id;
		rena.fname = fname;
		rena.onclick = function()
		{
			this.cls.renameGrouping(this.fname, this.gid, this.curname, this);
		}
		if (groupings[i].system)
			rena = groupings[i].title;

		// Sort order
		var sorder = alib.dom.createElement("select");
		sorder.onchange = function() { };
		for (var j = 0; j < groupings.length; j++)
		{
			sorder[sorder.length] = new Option(j+1, j+1, false, (j==i)?true:false);
		}
		if (groupings[i].system)
			sorder = "&nbsp;";

		// Closed
		var cb = alib.dom.createElement("input");
		cb.type = "checkbox";
		cb.checked = groupings[i].f_closed;
		cb.onclick = function()
		{
		}
		if (groupings[i].system)
			cb = "&nbsp;";

		// Color
		var clr_con = alib.dom.createElement("div");
		if (groupings[i].color)
			clr_con.style.backgroundColor = "#"+groupings[i].color;
		var dm = new CDropdownMenu();
		for (var j = 0; j < G_GROUP_COLORS.length; j++)
		{
			dm.addEntry(G_GROUP_COLORS[j][0], function(cls, fname, lnkcon, id, color) { cls.changeGroupingColor(fname, lnkcon, color, id) }, null, 
							  "<div style='width:9px;height:9px;background-color:#" + G_GROUP_COLORS[j][1] + "'></div>",
							  [this, fname, clr_con, groupings[i].id, G_GROUP_COLORS[j][1]]);
		}
		clr_con.appendChild(dm.createLinkMenu("set color"));

		// Delete
		var dela = alib.dom.createElement("a");
		dela.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
		dela.href = "javascript:void(0)";
		dela.cls = this;
		dela.fname = fname;
		dela.gid = groupings[i].id;
		dela.gname = groupings[i].title;
		dela.row = row;
		dela.onclick = function()
		{
			if (confirm("Are you sure you want to delete " + this.gname + "?"))
			{
				this.cls.deleteGrouping(this.fname, this.gid, this.row);
			}
		}
		if (groupings[i].system)
			dela = "&nbsp;";

		row.addCell(groupings[i].id, false, "center");
		row.addCell(rena);
		//row.addCell(sorder, false, "center");
		//row.addCell(cb, false, "center");
		row.addCell(clr_con, false, "center");
		row.addCell(dela, true, "center");
	}
}


/**
* Change the color of a grouping field like category or status
*/
Ant.EntityDefinitionEdit.prototype.changeGroupingColor = function(field_name, clrdv, clr, gid)
{
	ajax = new CAjax('json');
	ajax.cbData.clrdv = clrdv;
	ajax.cbData.clr = clr;
	ajax.onload = function(ret)
	{
		if (ret)
		{
			alib.dom.styleSet(this.cbData.clrdv, "background-color", "#"+this.cbData.clr);
		}
	};
	ajax.exec("/controller/Object/setGroupingColor", 
			  [["obj_type", this.obj_type], ["field", field_name], ["color", clr], ["gid", gid]]);
}

/**
* Add a grouping field value
*/
Ant.EntityDefinitionEdit.prototype.addGrouping = function(con, field_name)
{
    var name = prompt('Enter a name for new subgroup', "New Group");

    if (!name)
        return;

	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.field_name = field_name;
	ajax.cbData.con = con;
	ajax.onload = function(ret)
	{
		if (ret && ret.id)
		{
			this.cbData.cls.fieldGroupingsLoadValues(this.cbData.field_name, this.cbData.con);
			// Update AntObject cached definition
			Ant.EntityDefinitionLoader.get(this.cbData.cls.obj_type).load();
		}
	};
	ajax.exec("/controller/Object/createGrouping", 
			  [["obj_type", this.obj_type], ["field", field_name], ["parent_id", ""], ["title", name]]);
}

/**
* Rename a grouping field value
*/
Ant.EntityDefinitionEdit.prototype.renameGrouping = function(field_name, gid, gname, lbldv)
{
    var ldiv = (typeof(lbldv) != "undefined") ? lbldv : null;

    var name = prompt('Enter a name', gname);

    if (!name)
        return;

	ajax = new CAjax('json');
	ajax.cbData.lbldv = ldiv;
	ajax.cbData.title = name;
	ajax.onload = function(ret)
	{
		if (ret && this.cbData.lbldv)
		{
			this.cbData.lbldv.innerHTML = this.cbData.title;
		}
	};
	ajax.exec("/controller/Object/renameGrouping", 
			  [["obj_type", this.obj_type], ["field", field_name], ["title", name], ["gid", gid]]);
}

/**
* Delete a grouping field value
**/
Ant.EntityDefinitionEdit.prototype.deleteGrouping = function(field_name, gid, row)
{
	ajax = new CAjax('json');
	ajax.cbData.row = row;
	ajax.onload = function(ret)
	{
		if (ret)
			this.cbData.row.deleteRow();
	};
	ajax.exec("/controller/Object/deleteGrouping", 
			  [["obj_type", this.obj_type], ["field", field_name], ["gid", gid]]);
}

/**
* Load default field
* @param {string} fname Field Names
*/
Ant.EntityDefinitionEdit.prototype.fieldLoadDefault = function(fname)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.fname = fname;
    ajax.onload = function(ret)
    {
        if(ret)
            this.cbData.cls.fieldEditDefault(this.cbData.fname, ret);
    };
    var args = [["obj_type", this.g_antObject.name], ["name", fname]];
    ajax.exec("/controller/Object/fieldGetDefault", args);
}

/**
* Edit defaults for a field
* @param {string} fname Field Names
* @param {string} currdef  Default value
*/
Ant.EntityDefinitionEdit.prototype.fieldEditDefault = function(fname, currdef)
{
	var field = this.g_antObject.getFieldByName(fname);
	if (!field)
	{
		ALib.Dlg.messageBox("Please save changes before editing defaults for this field", this.m_dlg);		
		return;
	}
		
	var frmData = {on:currdef.on, value:currdef.value, fname:fname}; // Store from values

	var dlg = new CDialog("Default values for " + field.title, this.m_dlg);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 300);

	// new value
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Use default when field value is:";
	var inp_dv = alib.dom.createElement("div", dv);
	var cbOn = alib.dom.createElement("select");
	cbOn[cbOn.length] = new Option("none - no default value", "", false, (currdef.on=="")?true:false);
	cbOn[cbOn.length] = new Option("null - value is not set", "null", false, (currdef.on=="null")?true:false);
	cbOn[cbOn.length] = new Option("update - object is updated", "update", false, (currdef.on=="update")?true:false);
	cbOn[cbOn.length] = new Option("create - object is created", "create", false, (currdef.on=="create")?true:false);
	cbOn.frmData = frmData;
	cbOn.onchange = function() { this.frmData.on = this.value; }
	inp_dv.appendChild(cbOn);

	// optional values
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Set value to:";
	var inp_div = alib.dom.createElement("div", dv);
	this.g_antObject.fieldCreateValueInput(inp_div, field.name, currdef.value);
	if (inp_div.inpRef)
	{
		inp_div.inpRef.frmData = frmData;
		frmData.value = inp_div.inpRef.value;
		switch(inp_div.inptType)
		{
		case "checkbox":
			inp_div.inpRef.onclick = function() { this.frmData.value = (this.checked) ? 't' : 'f'; }
			break;
		case "text":
		case "input":
			alib.dom.styleSet(inp_div.inpRef, "width", "90%");
		case "select":
			inp_div.inpRef.onchange = function() { this.frmData.value = this.value; }
			break;
		case "dynselect":
			inp_div.inpRef.onSelect = function() { this.frmData.value = this.value; }
			break;
		}
	}

	// Action Buttons
	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");
	var btn = new CButton("Done", 
	function(cls, dlg, frmData) 
	{  
		cls.fieldEditDefaultSet(frmData);
		dlg.hide(); 
	}, [this, dlg, frmData], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg]);
	btn.print(dv_btn);

	dlg.reposition();
}

/**
* Edit default set of field
* @param {string} frmData Form data
*/
Ant.EntityDefinitionEdit.prototype.fieldEditDefaultSet = function(frmData)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.status)
            ALib.statusShowAlert(ret.status, 3000, "bottom", "right");
    };
    var args = [["obj_type", this.g_antObject.name], ["name", frmData.fname], ["on", frmData.on], ["value", frmData.value]];
    ajax.exec("/controller/Object/fieldSetDefault", args);
}

/**
* Build form tab
* @param {object} con Container of form tab
*/
Ant.EntityDefinitionEdit.prototype.tabForm = function(con)
{
	con.innerHTML = "";

	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "notice");
	p.innerHTML = "Forms define how objects are viewed and edited. Each object type will always have two "+
					"required form views: a default view for everyone and then a mobile view for mobile/smartphone "+
					"users. Both of these forms can be fully customized. In addition to the defaults, new forms can be "+
					"created for individual users and teams.";
	
	this.form_tbl.print(con);
	//this.form_tbl.addHeader("Type Id");
	this.form_tbl.addHeader("Scope");
	this.form_tbl.addHeader("Team");
	this.form_tbl.addHeader("User");
	this.form_tbl.addHeader("Edit");
	this.form_tbl.addHeader("Delete");
	
	// Add forms to form_tbl
	this.addFrmTbl();
	
	var op_con = alib.dom.createElement("div", con);
	
	var sel = alib.dom.createElement("select", op_con);
	alib.dom.styleSet(sel, "float", "left");
	sel.cls = this;
	sel.onchange = function() { this.cls.newForm(this.value); }
	sel[sel.length] = new Option("Create New Form", "");
	sel[sel.length] = new Option("Team Form", "team");
	sel[sel.length] = new Option("User Form", "user");
	
	var btn_con = alib.dom.createElement("div", op_con);
	var btn = new CButton("Refresh", function(cls){ cls.addFrmTbl(); }, [this], "b1");
	btn_con.appendChild(btn.getButton());
	
}

/**
* Open default form in form_editor
* @param {string} type Rype of form (team/user)
*/
Ant.EntityDefinitionEdit.prototype.newForm = function(type)
{
	if(type == "team")
	{
		var url = "/objects/form_editor.php?obj_type="+this.obj_type+"&scope=Team";
		window.open(url, "", "fullscreen=yes");
	}
	if(type == "user")
	{
		var url = "/objects/form_editor.php?obj_type="+this.obj_type+"&scope=User";
		window.open(url, "", "fullscreen=yes");
	}
}

/**
* Get forms to add to form_tbl
*/
Ant.EntityDefinitionEdit.prototype.addFrmTbl = function()
{
	// Purge any existing rows in form_tbl
	this.form_tbl.clear();

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(ret)
	{
		if(ret)
		{						
			if(ret.length > 0)
			{
				this.cbData.cls.createFrmTbl(ret);
			}
			this.cbData.cls.addDefaultForms();
		}
	};
	ajax.exec("/controller/Object/getForms", [["obj_type", this.obj_type]]);
}

/**
* Populate form table
* @param {object} forms array of forms to add to table
*/
Ant.EntityDefinitionEdit.prototype.createFrmTbl = function(forms)
{
	for(form in forms)
	{
        var currentForm = forms[form];
		var row = this.form_tbl.addRow();
		var frm_obj = new Object();
		
		// type_id
		var type_id = currentForm.type_id;
		//row.addCell(type_id);

		// scope
		var scope = currentForm.scope;
		if(scope == "default")
			this.default_form = true;				// default form overridden
		if(scope == "mobile")
			this.default_mobile_form = true;		// default mobile form overridden
		row.addCell(scope);
		
		// team
		var team_id = currentForm.team_id;
		var team_name = currentForm.team_name;
		row.addCell(team_name);
		
		// user
		var user = alib.dom.createElement("div");
		var user_id = "";
		user_id = currentForm.user_id;
		objectSetNameLabel("user", user_id, user);
		row.addCell(user);
		
		frm_obj.obj_type = this.obj_type;
		frm_obj.type_id = type_id;
		frm_obj.scope = scope;
		frm_obj.user_id = user_id;
		frm_obj.team_id = team_id;
		
		// edit
		var edit_frm = alib.dom.createElement("div");
		var form_lnk = alib.dom.createElement("a", edit_frm);
		form_lnk.href = 'javascript:void(0);'
		form_lnk.innerHTML = "edit form";
		form_lnk.cls = this;
		form_lnk.form = frm_obj;
		form_lnk.onclick = function() 
		{
			var form_url = "/objects/form_editor.php?obj_type="+this.cls.obj_type;
			if(this.form.scope == "default")
				form_url += "&scope=Default";
			if(this.form.scope == "mobile")
				form_url += "&scope=Mobile";
			if(this.form.team_id != "" && this.form.team_id != null)
				form_url += "&scope=Team&team_id="+this.form.team_id;
			if(this.form.user_id != "" && this.form.user_id != null)
				form_url += "&scope=User&user_id="+this.form.user_id;
			window.open(form_url, "", "fullscreen=yes");
		}
		row.addCell(edit_frm);
		
		// delete
		var btn = new CButton("X", function(cls, form) { cls.deleteForm(form); }, [this, frm_obj], "b3");
		row.addCell(btn.getButton());
	}
}

/**
* Add default forms to table
* @param {object} forms array of forms to add to table
*/
Ant.EntityDefinitionEdit.prototype.addDefaultForms = function()
{
	// Only add static default form if not present in app_object_type_frm_layouts
	if(!this.default_form)
	{
		var row = this.form_tbl.addRow();
		row.addCell("default");
		row.addCell("");
		row.addCell("");
		var edit_frm = alib.dom.createElement("div");
		var form_lnk = alib.dom.createElement("a", edit_frm);
		form_lnk.href = 'javascript:void(0);'
		form_lnk.innerHTML = "edit form";
		form_lnk.cls = this;
		form_lnk.onclick = function()
		{
			var form_url = "/objects/form_editor.php?obj_type="+this.cls.obj_type+"&scope=Default";
			window.open(form_url, "", "fullscreen=yes");
		}
		row.addCell(edit_frm);
		row.addCell("");
	}
	
	// Only add static default mobile form if not present in app_object_type_frm_layouts
	if(!this.default_mobile_form)
	{
		var row = this.form_tbl.addRow();
		row.addCell("mobile");
		row.addCell("");
		row.addCell("");
		var edit_frm = alib.dom.createElement("div");
		var form_lnk = alib.dom.createElement("a", edit_frm);
		form_lnk.href = 'javascript:void(0);'
		form_lnk.innerHTML = "edit form";
		form_lnk.cls = this;
		form_lnk.onclick = function() 
		{
			var form_url = "/objects/form_editor.php?obj_type="+this.cls.obj_type+"&scope=Mobile&mobile=0";
			window.open(form_url, "", "fullscreen=yes");
		}
		row.addCell(edit_frm);
		row.addCell("");
	}
}

/**
* Delete Form
* @param {object} form Form to delete
*/
Ant.EntityDefinitionEdit.prototype.deleteForm = function(form)
{
	var dlg = new CDialog("Delete Form", this.m_dlg);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 240, 40);

	var lbl = alib.dom.createElement("div", dv);
	lbl.innerHTML = "Are you sure you want to delete this form?";
	dv.appendChild(lbl);
	
	var btn_dv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(btn_dv, "text-align", "right");
	var btn = new CButton("Ok", 
	function(cls, dlg, form) 
	{
		var deflt = "";
		var mobile = "";
		var scope = form.scope;
		if(form.scope == "default")
			deflt = form.scope;
		if(form.scope == "mobile")
			mobile = form.scope
			
		/*function cbdone(ret, cls, dlg)
		{
			if(ret != "-1")
			{
				// if overriden default or mobile form was deleted, default form will be used
				if("default" == scope)
					cls.default_form = false;
				if("mobile" == scope)
					cls.default_mobile_form = false;
			
				dlg.hide();
				cls.addFrmTbl();
				ALib.statusShowAlert("Form Deleted!", 3000, "bottom", "right");
			}
			else
			{
				dlg.hide();
				ALib.statusShowAlert("ERROR DELETING FORM!", 3000, "bottom", "right");
			}
		}
		var args = [["obj_type", form.obj_type], ["default", deflt], ["mobile", mobile], ["team_id", form.team_id], ["user_id", form.user_id]];
        var rpc = new CAjaxRpc("/controller/Object/deleteForm", "deleteForm", args, cbdone, [cls, dlg, scope], AJAX_POST, true, "json");*/
        
        ajax = new CAjax('json');
        ajax.cbData.cls = cls;
        ajax.cbData.dlg = dlg;
        ajax.cbData.scope = scope;
        ajax.onload = function(ret)
        {
            this.cbData.dlg.hide();
            if(ret != "-1")
            {
                // if overriden default or mobile form was deleted, default form will be used
                if("default" == this.cbData.scope)
                    this.cbData.cls.default_form = false;
                if("mobile" == this.cbData.scope)
                    this.cbData.cls.default_mobile_form = false;
                    
                this.cbData.cls.addFrmTbl();
                ALib.statusShowAlert("Form Deleted!", 3000, "bottom", "right");
            }
            else
                ALib.statusShowAlert("ERROR DELETING FORM!", 3000, "bottom", "right");
        };
        var args = [["obj_type", form.obj_type], ["default", deflt], ["mobile", mobile], ["team_id", form.team_id], ["user_id", form.user_id]];
        ajax.exec("/controller/Object/deleteForm", args);
        
	}, [this, dlg, form], "b1");
	btn.print(btn_dv);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg], "b1");
	btn.print(btn_dv);
}

/**
* Delete Column
* @param {string} cname column name
* @param {object} row Row
*/
Ant.EntityDefinitionEdit.prototype.deleteCol = function(cname, row)
{
	var field = Ant.EntityDefinitionLoader.get(this.obj_type).getField(cname);

	if (cname && row)
	{
		if (!field)
			row.deleteRow();
		else
		{
			var xhr = new alib.net.Xhr();

			// Setup callback
			alib.events.listen(xhr, "load", function(evt) { 
				var data = this.getResponse();

				if (data.error)
				{
					alert(data.error);
				}
				else
				{
					// Remove from table
					evt.data.row.deleteRow();

					// Refresh the object definition
					evt.data.editClass.refreshLocalObject();
				}

			}, {editClass:this, row:row});

			// There was some error like a timeout
			alib.events.listen(xhr, "error", function(evt) { 
			}, {editClass:this});

			var ret = xhr.send("/controller/Object/removeField", "POST", {obj_type:this.obj_type, fname:cname});
		}
	}
}

/**
* Create main properties table for objects
* @param {string} cname column name
* @param {object} con = CWindowFrame container
*/
Ant.EntityDefinitionEdit.prototype.loadColumns = function(con)
{
	this.g_tbl.print(con);
	
	// Add headers
	this.g_tbl.addHeader("Name");
	this.g_tbl.addHeader("System Name");
	this.g_tbl.addHeader("Type");
	this.g_tbl.addHeader("Required");
	this.g_tbl.addHeader("&nbsp;");
	this.g_tbl.addHeader("&nbsp;");
	//this.g_tbl.addHeader("Description");
	this.g_tbl.addHeader("Delete", "center", "50px");

	for (var i = 0; i < this.g_antObject.fields.length; i++)
	{
		var field = this.g_antObject.fields[i];

		if (field.name == "account_id") // hidden
			continue;

		this.addProperty(field);
	}
}


/**
* Reload properties table for objects
*/
Ant.EntityDefinitionEdit.prototype.reloadFieldTable = function()
{
	this.g_tbl.clear();
	this.g_antObject = new CAntObject(this.obj_type, null);
	
	for (var i = 0; i < this.g_antObject.fields.length; i++)
	{
		var field = this.g_antObject.fields[i];

		if (field.name == "account_id") // hidden
			continue;

		this.addProperty(field);
	}
}
 
/**
 * Build view tab
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 * @param {object} con Container of form tab
 */
Ant.EntityDefinitionEdit.prototype.tabView = function(con)
{
    con.innerHTML = "";

    var p = alib.dom.createElement("p", con);
    alib.dom.styleSetClass(p, "notice");
    p.innerHTML = "Views define how users see lists of this object. A view has three scopes: (1) 'Eveyone' views are applied to everyone. (2) 'team' views are only available to users who are part of a selected team. (3) 'user' views are unique to individual users. Each of these scopes can have a default view meaning the first time a user loads the object list they will see the 'default' view. 'user' defaults override 'team' defaults and 'team' defaults will override 'everyone' defaults if set.";
    
    this.viewTbl.print(con);
    this.viewTbl.addHeader("Name");
    this.viewTbl.addHeader("Scope");
    this.viewTbl.addHeader("User");
    this.viewTbl.addHeader("Team");    
    this.viewTbl.addHeader("Default", "left", "50px");
    this.viewTbl.addHeader("Edit", "left", "50px");
    this.viewTbl.addHeader("Delete", "left", "50px");
    
    // Add forms to form_tbl
    this.getViews();
    
    var btnCon = alib.dom.createElement("div", con);
    
    var btn = new CButton("Create New View", function(cls){ cls.dialogView(null, true); }, [this], "b1");    
    btnCon.appendChild(btn.getButton());
    
    var btn = new CButton("Refresh Views", function(cls){ cls.getViews(); }, [this], "b1");    
    btnCon.appendChild(btn.getButton());
}

/**
 * Get forms to add to form_tbl
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 */
Ant.EntityDefinitionEdit.prototype.getViews = function()
{
    // Purge any existing rows in form_tbl
    this.viewTbl.clear();

    // Create loading div
    var row = this.viewTbl.addRow();
    row.addCell("<div class='loading'></div>");
    
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        
        if(ret)
        {
            if(ret.length > 0)
            {
                this.cbData.cls.displayViews(ret);
            }
        }
    };
    ajax.exec("/controller/Object/getViews", [["objectType", this.obj_type], ["fromViewManager", true]]);
}

/**
 * Display the object view
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 * @param {Object} viewsObj   View Object info
 */
Ant.EntityDefinitionEdit.prototype.displayViews = function(viewsObj)
{
    this.viewTbl.clear();
    for(view in viewsObj)
    {
        var currentView = viewsObj[view];
        var row = this.viewTbl.addRow();    
        row.addCell(currentView['name']);
        row.addCell(currentView['scope']);
        row.addCell(currentView['userName']);
        row.addCell(currentView['teamName']);
        
        var fDefault = "no";
        if(currentView['fDefault'] || currentView['fDefault']=='t')
            fDefault = "yes";
        
        currentView.fSystem = currentView.f_system;
        row.addCell(fDefault);
        
        var editLink = "";
        var deleteLink = "";
        if(currentView['f_system'])
        {
            // Edit Link
            var attrData = [["href", "javascript:void(0);"], ["innerHTML", "[view]"]];
            editLink = alib.dom.setElementAttr(alib.dom.createElement("a"), attrData);            
            
            // Edit Event
            editLink.cls = this;
            editLink.view = currentView;
            editLink.onclick = function()
            {
                this.cls.dialogView(this.view);
            }            
        }
        else
        {
            // Edit Link
            var attrData = [["href", "javascript:void(0);"], ["innerHTML", "[edit]"]];
            editLink = alib.dom.setElementAttr(alib.dom.createElement("a"), attrData);            
            
            // Edit Event
            editLink.cls = this;
            editLink.view = currentView;
            editLink.onclick = function()
            {
                this.cls.dialogView(this.view);
            }
            
            // Delete Link
            var attrData = [["src", "/images/icons/delete_10.png"]];
            deleteLink = alib.dom.setElementAttr(alib.dom.createElement("img"), attrData);
            alib.dom.styleSet(deleteLink, "cursor", "pointer");
            
            // Delete Event
            deleteLink.cls = this;
            deleteLink.row = row;
            deleteLink.view = currentView;
            deleteLink.onclick = function()
            {
                if(confirm("Are you sure you want to delete " + this.view['name'] + "?"))
                    this.cls.deleteView(this.view, this.row);
            }
        }
        
        row.addCell(editLink);
        row.addCell(deleteLink);
    }
}

/**
 * Displays the view dialog
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 * @param {Object} view   View info
 */
Ant.EntityDefinitionEdit.prototype.dialogView = function(view, parentDlg)
{
    var viewEditor = new AntObjectViewEditor(this.obj_type, view);
    
    viewEditor.fEditor = true;
    viewEditor.showDialog(parentDlg);
    
    // Callback Events
    viewEditor.cbData.cls = this;
    viewEditor.onSave = function(result)
    {
        this.cbData.cls.getViews();
    }
}

/**
 * Deletes the view object
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 * @param {Object} view   View info
 * @param {Element} row   View table row Element
 */
Ant.EntityDefinitionEdit.prototype.deleteView = function(view, row)
{
    ajax = new CAjax('json');
    ajax.row = row;
    ajax.onload = function(ret)
    {
        this.row.deleteRow();
    };
    
    var args = new Array();
    args[args.length] = ["dvid", view['id']];
    ajax.exec("/controller/Object/deleteView", args);
}

/**
* Populates g_references array
*/
Ant.EntityDefinitionEdit.prototype.getObjects = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(ret)
        {
            var obj_array = [];
            for(object in ret)
            {
                var currentObject = ret[object];
                obj_array[obj_array.length] = [currentObject.fullTitle, currentObject.name, currentObject.ObjectTable, "id", currentObject.listTitle];
            }
        }
        this.cbData.cls.g_references = obj_array;
    };
    ajax.exec("/controller/Object/getObjects");
}

/**
 * @deprecated We no longer use this because updates are real-time
 * Save Object
 * @param {boolean} close close dialog after saving 
Ant.EntityDefinitionEdit.prototype.saveObject = function(close)
{
	var close = (typeof close != "undefined") ? close : false;

	// Create loading div
	var dlg = new CDialog();
	dlg.parentDlg = this.m_dlg;
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";

	dlg.statusDialog(dv_load, 150, 100);

	this.g_antObject.cls = this;
	this.g_antObject.dlg = dlg;
	this.g_antObject.m_close = close;
	this.g_antObject.onsavedefinition = function()
	{
		Ant.EntityDefinitionLoader.get(this.cls.obj_type).load();
		this.cls.saveDone(this.dlg, this.m_close);
		this.cls.refreshLocalObject();
	}
	this.g_antObject.onsavedefinitionError = function()
	{
		this.dlg.hide();
		ALib.statusShowAlert("ERROR SAVING CHANGES!", 3000, "bottom", "right");
	}
	this.g_antObject.saveDefinition();
}
*/

/**
* Save Object
* @param {boolean} close close dialog after saving 
*/
Ant.EntityDefinitionEdit.prototype.refreshLocalObject = function()
{
	this.mainObject = new CAntObject(this.obj_type);

	var def = Ant.EntityDefinitionLoader.get(this.obj_type);
	def.load(); // Async reload
}

/**
* Save Object
* @param {object} dlg Save Dialog
* @param {boolean} close close dialog after saving 
*/
Ant.EntityDefinitionEdit.prototype.saveDone = function(dlg, close)
{
	var close = (typeof close != "undefined") ? close : false;
	dlg.hide();

	ALib.statusShowAlert(this.g_antObject.title + " Saved!", 3000, "bottom", "right");
	
	if (close)
		this.m_dlg.hide();				// close object edit dialog
	else
		this.reloadFieldTable();		// reload properties table
}

/**
 * Browse objects
 *
 * @public
 * @this {Ant.EntityDefinitionEdit}
 * @param {object} con Container of form tab
 */
Ant.EntityDefinitionEdit.prototype.tabBrowse = function(con)
{
    con.innerHTML = "";

	var innerCon = alib.dom.createElement("div", con);
	alib.dom.styleSet(innerCon, "height", "300px");
	alib.dom.styleSet(innerCon, "overflow", "auto");

    var objb = new AntObjectBrowser(this.obj_type);
	objb.printInline(innerCon);
}
