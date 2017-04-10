/**
 * @fileoverview This form load will be used to load forms for objec types
 */

/**
 * Constructor
 */
AntObjectForms = {};

/**
 * Cache loaded forms
 *
 * @private
 * @type {{objType, scope, xml}}
 */
AntObjectForms.loadedForms = new Array();

/**
 * Load an object form it is not already cached
 *
 * @public
 * @param {string} objType The name of the object type to load the form for
 * @param {function} callback The function to call
 */
AntObjectForms.loadForm = function(objType, callback, cbData, scope)
{
	var scope = scope || "default";
	var callbackData = cbData || new Object();

	// First check to see if it has been loaded already
	for (var i in this.loadedForms)
	{
		if (this.loadedForms[i].objType == objType && this.loadedForms[i].scope == scope)
			return this.loaded(callback, callbackData, this.loadedForms[i].xml);
	}

	// Not cached, get xml from object controller
    var ajax = new CAjax('xml');
    ajax.cbData.cls = this;
	ajax.cbData.callback = callback;
	ajax.cbData.callbackData = callbackData;
	ajax.cbData.objType = objType;
	ajax.cbData.scope = scope;
    ajax.onload = function(root)
    {
		this.cbData.cls.loadedForms.push({
			objType:this.cbData.objType,
			scope:this.cbData.scope,
			xml:root
		});
		this.cbData.cls.loaded(this.cbData.callback, this.cbData.callbackData, root);
    };
    ajax.exec("/controller/Object/getFormUIML", 
                [["obj_type", objType], ["scope", scope]]);
}

/**
 * Get form root xml
 *
 * This function will force a non-asycn get of form if not already cached which hangs the ui.
 * It is highly recommended that you use loadFrom above with an async callback
 *
 * @param {string} objType The object type name to load
 * @param {string} scope The scope if not default
 */
AntObjectForms.getFormXml = function(objType, scope)
{
	var scope = scope || "default";

	for (var i in this.loadedForms)
	{
		if (this.loadedForms[i].objType == objType && this.loadedForms[i].scope == scope)
		{
			return this.loadedForms[i].xml;
		}
	}

	// Else get from ajax with non asyc call
	var ajax = new CAjax("xml");
    var root = ajax.exec("/controller/Object/getFormUIML", 
              			  [["obj_type", objType], ["scope", scope]], false);

	// Cache future requests
	this.cbData.cls.loadedForms.push({
		objType:objType,
		scope:scope,
		xml:root
	});

	return root;
}

/**
 * Form is loaded, call the callback
 *
 * @private
 * @param {function|Object} cb Either a function or an object with {context, method} properties
 * @param {Object} cbData Data object to pass to callback
 * @param {CXml} xml The root xml node
 */
AntObjectForms.loaded = function(cb, cbData, xml)
{
	// Call the callback
	// -----------------------------------------
	if (typeof cb == "function")
	{
		cb(xml, cbData);
	}
	else if (cb.context && cb.method)
	{
		cb.context[cb.method](xml, cbData);
	}

	return true;
}

/**
 * Clear cache for an object tyep
 *
 * @public
 * @param {string} objType The name of the object forms to clear
 */
AntObjectForms.clearCache  = function(objType)
{
	// First check to see if it has been loaded already
	for (var i in this.loadedForms)
	{
		if (this.loadedForms[i].objType == objType)
		{
			this.loadedForms.splice(i, 1);

			// Call recurrsively to remove all scopes
			this.clearCache(objType);
		}
	}
}
