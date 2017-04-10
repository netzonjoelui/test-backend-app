/**
 * Field input with type='object'
 *
 * These all have the commmon interface functions:
 * setValue
 * getValue
 *
 * Should fire a 'change' event that the parent input can capture
 */

/**
 * Class constructor
 */
function AntObject_FieldInput_Object(fieldCls, con, options)
{
	/**
	 * FieldInput class
	 *
	 * @var {AntObject_FieldInput}
	 */
	this.fieldInput = fieldCls;

	if (fieldCls.field.subtype == "file")
		this.renderInputObjectFile(con, options);
	else
		this.renderInputObject(con, options);
}

/**
 * Set the value of this input
 *
 * @var {string} value The value, numeric if this is a key type like fkey or object
 * @var {string} valueName Optional name of key value if value type is key
 */
AntObject_FieldInput_Object.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Object.prototype.getValue = function()
{
}

/**
 * Render an object field with a subtype
 *
 * @private
 * @param {DOMElement} con
 * @param {Object} options
 */
AntObject_FieldInput_Object.prototype.renderInputObject = function(con, options)
{
	var browserCon = alib.dom.createElement("div", con);            
	var label = alib.dom.createElement("label", browserCon);
	
	if (this.fieldInput.valueName)
	{
		this.renderLabel(label, this.fieldInput.valueName);
	}
	else if (this.fieldInput.value)
	{
		var subLabel = alib.dom.createElement("span", label, "loading...");

		if (this.fieldInput.field.subtype)
		{
			// Replace current user (-3) with "Me" for forms
			// TODO: when queries start using AntObject_Field* classes we'll need to revisit this
			if (this.fieldInput.value == -3 && this.fieldInput.field.subtype == "user")
				subLabel.innerHTML = "Me";
			else
				objectSetNameLabel(this.fieldInput.field.subtype, this.fieldInput.value, subLabel); // get from server
		}
		else
		{
			var refParts = this.fieldInput.value.split(":");
			if (refParts.length == 2)
				objectSetNameLabel(refParts[0], refParts[1], subLabel); // get from server
		}

		this.renderLabel(label, subLabel);
	}
	else
	{
		label.innerHTML = "None Selected";
	}

	
	alib.dom.styleSet(label, "font-size", "12px");
	alib.dom.styleSet(label, "margin-right", "10px");
	
	if (this.fieldInput.field.subtype)
	{
		var btn = alib.ui.Button("Select", {
			className:"b1", 
			clsRef:this, 
			label:label, 
			objType:this.fieldInput.field.subtype,
			options:options,
			onclick:function() 
			{
				var antBrowser = new AntObjectBrowser(this.objType);
				antBrowser.cbData.label = this.label;
				antBrowser.cbData.clsRef = this.clsRef;

				if (this.options.refField && (this.options.refThis || options.refValue))
				{
					var val = (this.options.refValue) ? 
									this.options.refValue : 
									this.clsRef.fieldInput.obj.getValue(this.options.refThis);
					if (val)
						antBrowser.setFilter(this.options.refField, val);
				}
					
				antBrowser.onSelect = function(objId, objLabel) 
				{
					this.cbData.clsRef.renderLabel(this.cbData.label, objLabel);
					alib.events.triggerEvent(this.cbData.clsRef, "change", {value:objId, valueName:objLabel});
				}
				antBrowser.displaySelect();
			}
		});                            
		browserCon.appendChild(btn.getButton());
	}
	else
	{
		// First select object type
		var typeSel = new AntObjectTypeSel(this.fieldInput.value);
		typeSel.render(browserCon);
		typeSel.cbData.cls = this;
		typeSel.cbData.label = label;
		typeSel.cbData.clsRef = this;
		typeSel.onchange = function(objType)
		{
			var antBrowser = new AntObjectBrowser(objType);
			antBrowser.cbData.label = this.cbData.label;
			antBrowser.cbData.clsRef = this.cbData.clsRef;
			antBrowser.onSelect = function(objId, objLabel) 
			{
				this.cbData.clsRef.renderLabel(this.cbData.label, objLabel);
				alib.events.triggerEvent(this.cbData.clsRef, "change", {value:objType + ":" + objId, valueName:objLabel});
			}
			antBrowser.displaySelect();
		}
	}
}

/**
 * Render an object field with a subtype of file
 */
AntObject_FieldInput_Object.prototype.renderInputObjectFile = function(con, options)
{
	var label = alib.dom.createElement("label", con);
	
	if (this.fieldInput.value)
	{
		if (options.profileImage)
			label.innerHTML = "<img src=\"/antfs/images/"+this.fieldInput.value+"/48\" border='0' />";
		else
			label.innerHTML = "<a href=\"/antfs/"+this.fieldInput.value+"\">"+this.fieldInput.valueName+"</a>";
	}
	else
	{

		if (options.profileImage)
			label.innerHTML = "<img src=\"/images/icons/objects/files/image_48.png\" border='0' />";
		else
			label.innerHTML = "None Selected";
	}

	var browserCon = alib.dom.createElement("div", con);            
	var dm = new CDropdownMenu();

	var menu = new alib.ui.PopupMenu();

	// Uplaod files
	var item = new alib.ui.MenuItem("Upload File", {icon:"<img src='/images/icons/add_10.png' />"});
	item.cbData.cls = this;
	item.cbData.label = label;
	item.cbData.options = options;
	item.onclick = function() {
		// Upload a new file to a temp directory
		var cfupload = new AntFsUpload();
		cfupload.cbData.cls = this.cbData.cls;
		cfupload.cbData.opts = this.cbData.options;
		cfupload.cbData.lbl = this.cbData.label;
		if (this.cbData.options.folderRoot > 0)
			cfupload.setFolderId(this.cbData.options.folderRoot);
		else if (this.cbData.cls.fieldInput.obj.id)
			cfupload.setPath("/System/Objects/" + this.cbData.cls.fieldInput.objType + "/" + this.cbData.cls.fieldInput.obj.id);
		else
			cfupload.setPath("%tmp%");
		cfupload.onUploadFinished = function()
		{
			var file = this.getUploadedFile(0);

			if (this.cbData.opts.profileImage)
				this.cbData.lbl.innerHTML = "<img src=\"/antfs/images/"+file.id+"/48\" border='0' />";
			else
				this.cbData.lbl.innerHTML = "<a href=\"/antfs/"+file.id+"\">"+file.name+"</a>";

			alib.events.triggerEvent(this.cbData.cls, "change", {value:file.id, valueName:file.name});
		}
		cfupload.showDialog();
	};
	menu.addItem(item);

	// Browse for files
	var item = new alib.ui.MenuItem("Select Uploaded File", {icon:"<img src='/images/icons/folder_open_10.png' />"});
	item.cbData.cls = this;
	item.cbData.label = label;
	item.cbData.options = options;
	item.onclick = function() {
		// Open file browser
		var cbrowser = new AntFsOpen();
		cbrowser.cbData.cls = this.cbData.cls;
		cbrowser.cbData.opts = this.cbData.options;
		cbrowser.cbData.lbl = this.cbData.label;
		if (this.cbData.options.folderRoot)
			cbrowser.setPathById(this.cbData.options.folderRoot);
		cbrowser.onSelect = function(fid, name, path) 
		{
			if (this.cbData.opts.profileImage)
				this.cbData.lbl.innerHTML = "<img src=\"/antfs/images/"+fid+"/48\" border='0' />";
			else
				this.cbData.lbl.innerHTML = "<a href=\"/antfs/"+fid+"\">"+name+"</a>";

			alib.events.triggerEvent(this.cbData.cls, "change", {value:fid, valueName:name});
		}
		cbrowser.showDialog();
	};
	menu.addItem(item);

	// Add remove file
	var item = new alib.ui.MenuItem("Remove File", {icon:"<img src='/images/icons/delete_10.png' />"});
	item.cbData.cls = this;
	item.cbData.label = label;
	item.cbData.options = options;
	item.onclick = function() {
		// If there is a value then remove this file
		this.cbData.label.innerHTML = "None Selected";
		alib.events.triggerEvent(this.cbData.cls, "change", {value:"", valueName:""});
	};
	menu.addItem(item);

	// Render the menu
	var btn = new alib.ui.MenuButton("change", menu, {className:"b1"});
	btn.print(browserCon);
}

/**
 * Render the label of a this field
 *
 * @public
 * @param {DOMElement} label The container for the label
 * @param {string} lblText Text to set the label to
 */
AntObject_FieldInput_Object.prototype.renderLabel = function(label, lblText)
{
	if (lblText)
	{
		if (typeof lblText == "string")
			label.innerHTML = lblText;
		else
			label.appendChild(lblText);

		// Add clear icon
		if (!this.fieldInput.field.required)
		{
			var spacer = alib.dom.createElement("span", label, "&nbsp;");
			var clearLink = alib.dom.createElement("a", label);
			clearLink.href = "javascript:void(0);";
			clearLink.clsRef = this;
			clearLink.label = label;
			clearLink.onclick = function() {
				alib.events.triggerEvent(this.clsRef, "change", {value:"", valueName:""});
				this.clsRef.renderLabel(this.label, "");
			}
			clearLink.innerHTML = "<img src='/images/icons/delete_16.png' />";
		}
	}
	else
	{
		label.innerHTML = "None Selected";
	}
}
