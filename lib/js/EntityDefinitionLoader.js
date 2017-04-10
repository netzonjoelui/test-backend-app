/**
 * @fileOverview Handle loading and caching EntityDefinitions
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 *			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Static namespace
 */
Ant.EntityDefinitionLoader = {};

/**
 * Array of loaded definitions so we only load once
 *
 * @private
 * @type {EntityDefinition[]}
 */
Ant.EntityDefinitionLoader.definitions_ = new Array();

/**
 * Get a definition for an object type
 *
 * @param {string} objType The object type name to get
 * @param {function} callback If set use async to pull the definition (recommended)
 */
Ant.EntityDefinitionLoader.get = function(objType, callback) {
	// If already cached return
	if (this.definitions_[objType])
		return this.definitions_[objType];

	// Load the definition
	var forceNoAsync = (callback) ? false : true;
	var def = new Ant.EntityDefinition(objType);
	if (callback)
		alib.events.listen(def, "load", callback);
	def.load(forceNoAsync);
	this.definitions_[objType] = def;

	return def;
}