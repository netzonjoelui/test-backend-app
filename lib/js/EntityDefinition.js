/**
 * @fileOverview Handle defintion of entities.
 *
 * This class is a client side mirror of /lib/EntityDefinition
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of EntityDefinition
 *
 * @constructor
 * @param {string} objType The object type to load
 */
Ant.EntityDefinition = function(objType)
{
	/**
	 * The object type for this definition
	 *
	 * @public
	 * @type {string}
	 */
	this.objType = objType;

	/**
	 * The object type title
	 *
	 * @public
	 * @type {string}
	 */
	this.title = "";

	/**
	 * Recurrence rules
	 *
	 * @public
	 * @type {string}
	 */
	this.recurRules = "";

	/**
	 * Unique id of this object type
	 *
	 * @public
	 * @type {string}
	 */
	this.id = "";

	/**
	 * The current schema revision
	 *
	 * @public
	 * @type {int}
	 */
	this.revision = 0;

	/**
	 * Determine if this object type is private
	 *
	 * @public
	 * @type {bool}
	 */
	this.isPrivate = false;

	/**
	 * If object is heirarchial then this is the field that will store a reference to the parent
	 *
	 * @public
	 * @type {string}
	 */
	this.parentField = "";

	/**
	 * Default field used for printing the name/title of objects of this type
	 *
	 * @public
	 * @type {string}
	 */
	this.listTitle = "";

	/**
	 * The base icon name used for this object.
	 *
	 * This may be over-ridden by individual objects for more dynamic icons, but this serves
	 * as the base in case the individual object did not yet define an icon.
	 *
	 * @public
	 * @type {string}
	 */
	this.icon = "";

	/**
	 * Browser mode for the current user
	 *
	 * @public
	 * @type {string}
	 */
	this.browserMode = "";

	/**
	 * Is this a system level object
	 *
	 * @public
	 * @type {bool}
	 */
	this.system = true;

	/**
	 * Fields associated with this object type
	 *
	 * For definition see EntityDefinition_Field::toArray on backend
	 *
	 * @private
	 * @type {Object{}}
	 */
	this.fields = new Array();

	/**
	 * Array of object views
	 *
	 * @private
	 * @type {AntObjectBrowserView[]}
	 */
	this.views = new Array();

	/**
	 * Browser list blank state content
	 *
	 * This is used when there are no objects
	 *
	 * @private
	 * @type {string}
	 */
	this.browserBlankContent = "";
}

/**
 * Load the definition from controller
 *
 * @param {bool} forceNoAsync If true the do not use async which will suspend execution until retrieved
 */
Ant.EntityDefinition.prototype.load = function(forceNoAsync)
{
	var xhr = new alib.net.Xhr();

	// Force return of data immediately
	if (forceNoAsync)
	{
		xhr.setAsync(false);
	}
	else
	{
		// Setup callback
		alib.events.listen(xhr, "load", function(evt) { 
			var data = this.getResponse();
			evt.data.defCls.fromData(data);
		}, {defCls:this});
	}

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
	}, {defCls:this});

	var ret = xhr.send("/controller/Object/getDefinition", "POST", {obj_type:this.objType});

	if (forceNoAsync)
		this.fromData(xhr.getResponse());
}

/**
 * Initialize this object from data
 *
 * @public
 * @param {Object} data Initialize values of this defintion based on data
 */
Ant.EntityDefinition.prototype.fromData = function(data)
{
	this.id = data.id;
	this.title = data.title;
	this.revision = data.revision;
	this.isPrivate = data.is_private;
	this.recurRules = data.recur_rules;
	this.parentField = data.parent_field;
	this.listTitle = data.list_title;
	this.icon = data.icon;
	this.browserMode = data.browser_mode;
	this.browserBlankContent = data.browser_blank_content;
	this.system = data.system;
	this.fields = new Array();

	for (var fname in data.fields)
	{
		var field = new Ant.EntityDefinition.Field();
		field.fromArray(data.fields[fname]);
		this.fields.push(field);
	}

	// Load views
	this.views = new Array();
	for (var i in data.views)
	{
		var view = new AntObjectBrowserView();
		view.fromData(data.views[i]);
		this.views.push(view);
	}

	// Let any listeners know that we are finished loading
	alib.events.triggerEvent(this, "load");
}

/**
 * Get a field by name
 *
 * @public
 * @param {Object} data Initialize values of this defintion based on data
 */
Ant.EntityDefinition.prototype.getField = function(fname)
{
	for (var i in this.fields)
	{
		if (this.fields[i].name == fname)
			return this.fields[i];
	}
	return false;
}

/**
 * Get fields
 *
 * @public
 * @return {Ant.EntityDefinition.Field[]}
 */
Ant.EntityDefinition.prototype.getFields = function()
{
	return this.fields;
}

/**
 * Get views
 *
 * @public
 * @return {AntObjectBrowserView[]}
 */
Ant.EntityDefinition.prototype.getViews = function()
{
	return this.views;
}

/**
 * Get browser blank state content
 *
 * @public
 * @return {string}
 */
Ant.EntityDefinition.prototype.getBrowserBlankContent = function()
{
	return this.browserBlankContent;
}
