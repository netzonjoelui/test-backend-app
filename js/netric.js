/**
* @fileOverview Base namespace for netric
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/

alib.declare("netric");

/**
 * @define {boolean} Overridden to true by the compiler when --closure_pass
 *     or --mark_as_compiled is specified.
 */
var COMPILED = false;

/**
 * The root namespace for all netric code
 */
var netric = netric || {};

/**
 * Set version
 *
 * @public
 * @type {string}
 */
netric.version = "2.0.1";

/**
 * Connection status used to indicate if we are able to query the server
 *
 * Example"
 * <code>
 *	if (netric.online)
 *		server.getData();
 *	else
 * 		localStore.getData();
 * </code>
 *
 * @public
 * @var {bool}
 */
netric.online = false;

/**
 * Private reference to initialized applicaiton
 *
 * This will be set in netric.Application.load and should be used
 * with caution making sure all supporting code is called after the
 * main applicaiton has been initialized.
 *
 * @private
 * @var {netric.Application}
 */
 netric.application_ = null;

 /**
  * Get account base uri for building links
  * 
  * We need to do this because accounts are represented with
  * third level domains, like aereus.netric.com, where 'aereus'
  * is the name of the account.
  * 
  * @public
  * @return {string} URI
  */
netric.getBaseUri = function()
{
	var uri = window.location.protocol+'//'+window.location.hostname+(window.location.port 
		? ':' + window.location.port
		: '');
	return uri;
}

/**
 * Get initailized application
 *
 * @throws {Exception} If application has not yet been loaded
 * @return {netric.Application|bool}
 */
netric.getApplication = function() {
	if (this.application_ === null) {
		throw new Error("An instance of netric.Application has not yet been loaded.");
	}

	return this.application_;
}

/**
 * Inherit the prototype methods from one funciton
 *
 * <pre>
 * function ParentClass(a, b) { }
 * ParentClass.prototype.foo = function(a) { }
 *
 * function ChildClass(a, b, c) {
 *   ParentClass.call(this, a, b, c);
 * }
 * netric.inherits(ChildClass, ParentClass);
 *
 * var child = new ChildClass('a', 'b', 'see');
 * child.foo(); // works
 * </pre>
 *
 * In addition, a parent class' implementation of a method can be invoked
 * as follows:
 *
 * <pre>
 * ChildClass.prototype.foo = function(a) {
 *   ChildClass.parentClass_.foo.call(this, a);
 *   // other code
 * };
 * </pre>
 *
 * @param {Function} childCtor Child class.
 * @param {Function} parentCtor Parent class.
 */
netric.inherits = function(childCtor, parentCtor) 
{
  /** @constructor */
  function tempCtor() {};
  tempCtor.prototype = parentCtor.prototype;
  childCtor.parentClass_ = parentCtor.prototype;
  childCtor.prototype = new tempCtor();
  /** @override */
  childCtor.prototype.constructor = childCtor;
}

/**
 * Used to manage dependencies at compile time or run-time if a callback is specified
 *
 * @param {string|string[]} mDeps Mixeed, can be a string or array of strings indicating required namespaces
 * @param {function} opt_methodName Optional callback function to be called once all dependencies are loaded
 */
 netric.require = function(mDeps, opt_methodName)
 {
  // TODO: currently a stub for the compiler
 }


/**
  * Declare a namespace
  *
  * @param {string} sName The full path of the namespace to provide
  */
netric.declare = function(sName) 
{
  netric.exportPath_(sName);
};

/**
 * Create object structure for a namespace path and make sure existing object are NOT overwritten
 *
 * @param {string} path oath of the object that this file defines.
 * @param {*=} opt_object the object to expose at the end of the path.
 * @param {Object=} opt_objectToExportTo The object to add the path to; default this
 * @private
 */
netric.exportPath_ = function(name, opt_object, opt_objectToExportTo) {
  var parts = name.split('.');
  var cur = opt_objectToExportTo || window;

  // Internet Explorer exhibits strange behavior when throwing errors from
  // methods externed in this manner.
  if (!(parts[0] in cur) && cur.execScript) {
    cur.execScript('var ' + parts[0]);
  }

  // Parentheses added to eliminate strict JS warning in Firefox.
  for (var part; parts.length && (part = parts.shift());) {
    if (!parts.length && opt_object) {
      // last part and we have an object; use it
      cur[part] = opt_object;
    } else if (cur[part]) {
      cur = cur[part];
    } else {
      cur = cur[part] = {};
    }
  }
};


/**
 * @fileoverview controller namespace
 */

alib.declare("netric.controller");

alib.require("netric");

/**
 * The MVC namespace where all MVC core functionality will liveß
 */
netric.controller = netric.controller || {};

/**
 * Set controller types
 */
netric.controller.types = {
	/*
	 * Pages will hide their parent when they are brought to the foreground or hidden
	 * to make sore that only one page in the stack is visible at once.
	 */
	PAGE: 'page',

	/*
	 * A fragment is meant to be embedded inside a page controller like in the case of
	 * a desktop devicec where you might want to keep an application view present while
	 * routing to inner fragments within the application view.
	 * If a fragment has a sub-controller that is a page, the fragment will be hidden
	 * when the page gets displayed, but the fragment will not cascade up and hide its parent.
	 */
	FRAGMENT: 'fragment',
	
	/*
	 * A dialog controller simply renders its content into a (usually modal) dialog. It is a
	 * special case in that it will not make any modifications to parent PAGE or FRAGMENT
	 * controllers. However, dialog controllers can be nested in the case where a dialog
	 * invokes another dialog, then they will behave shomewhat like a PAGE in that only
	 * the currently visible dialog will be displayed at once (or moved to the foreground).
	 */
	DIALOG: 'dialog'
}
/**
 * @fileOverview Define entity definition fields
 *
 * This class is a client side mirror of /lib/EntityDefinition/Field on the server side
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.entity.definition.Field");

alib.require("netric");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Make sure entity definition namespace is initialized
 */
netric.entity.definition = netric.entity.definition || {};

/**
 * Creates an instance of netric.entity.definition.Field
 *
 * @param {Object} opt_data The definition data
 * @constructor
 */
netric.entity.definition.Field = function(opt_data) {

	var data = opt_data || new Object();

	/**
	 * Unique id if the field was loaded from a database
	 *
	 * @public
	 * @type {string}
	 */
	this.id = data.id || "";

	/**
	 * Field name (REQUIRED)
	 *
	 * No spaces or special characters allowed. Only alphanum up to 32 characters in length.
	 *
	 * @public
	 * @type {string}
	 */
	this.name = data.name || "";

	/**
	 * Human readable title
	 *
	 * If not set then $this->name will be used:
	 *
	 * @public
	 * @type {string}
	 */
	this.title = data.title || "";

	/**
	 * The type of field (REQUIRED)
	 *
	 * @public
	 * @type {string}
	 */
	this.type = data.type || "";

	/**
	 * The subtype
	 *
	 * @public
	 * @type {string}
	 */
	this.subtype = data.subtype || "";

	/**
	 * Optional mask for formatting value
	 *
	 * @public
	 * @type {string}
	 */
	this.mask = data.mask || "";

	/**
	 * Is this a required field?
	 *
	 * @public
	 * @var bool
	 */
	this.required = data.required || false;

	/**
	 * Is this a system defined field
	 *
	 * Only user fields can be deleted or edited
	 *
	 * @public
	 * @var bool
	 */
	this.system = data.system || false;

	/**
	 * If read only the user cannot set this value
	 *
	 * @public
	 * @var bool
	 */
	this.readonly = data.readonly || false;

	/**
	 * This field value must be unique across all objects
	 *
	 * @public
	 * @var bool
	 */
	this.unique = data.unique || false;

	/**
	 * Optional use_when condition will only display field when condition is met
	 *
	 * This is used for things like custom fields for posts where each feed will have special
	 * custom fields on a global object - posts.
	 *
	 * @public
	 * @type {string}
	 */
	this.useWhen = data.use_when || "";

	/**
	 * Default value to use with this field
	 *
	 * @public
	 * @var {array('on', 'value')}
	 */
	this.defaultVal = data.default_val || null;

	/**
	 * Optional values
	 *
	 * If an associative array then the id is the key, otherwise the value is used
	 *
	 * @public
	 * @var {Array}
	 */
	this.optionalValues = data.optional_values || null;

	/**
	 * Sometimes we need to automatically create foreign reference
	 *
	 * @public
	 * @type {bool}
	 */
	this.autocreate = data.autocreate || false;

	/**
	 * If autocreate then the base is used to define where to put the new referenced object
	 *
	 * @public
	 * @type {string}
	 */
	this.autocreatebase = data.autocreatebase || "";

	/**
	 * If autocreate then which field should we use for the name of the new object
	 *
	 * @public
	 * @type {string}
	 */
	this.autocreatename = data.autocreatename || "";

	/** 
	 * Add static types to a variable in 'this'
	 *
	 * @public
	 * @type {Object}
	 */
	this.types = netric.entity.definition.Field.types;
}

/**
 * Static definition of all field types
 */
netric.entity.definition.Field.types = {
	fkey : "fkey",
	fkeyMulti : "fkey_multi",
	object : "object",
	objectMulti : "object_multi",
	string : "string",
	bool : "bool",
}

/**
 * Get the default value for this vield
 *
 * @param {string} on The event to set default value on - default to null
 * @return {string}
 */
netric.entity.definition.Field.prototype.getDefault = function(on)
{
	if (!this.defaultVal)
		return "";

	if (this.defaultVal.on == on)
	{
		if (this.defaultVal.value)
			return this.defaultVal.value;
	}

	return "";
}

/**
* @fileOverview Route represents a single route segment
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2015 Aereus Corporation. All rights reserved.
*/

alib.declare("netric.location.Route");

alib.require("netric");

/**
 * Make sure namespace exists
 */
netric.location = netric.location || {};

/**
 * Route segment
 *
 * @constructor
 * @param {netric.locateion.Router} parentRouter Instance of a parentRouter that will own this route
 * @param {string} segmentName Can be a constant string or a variable with ":" prefixed which falls back to the previous route(?)
 * @param {Controller} controller The controller to load
 * @param {Object} opt_data Optional data to pass to the controller when routed to
 * @param {ReactElement} opt_element Optional parent element to render a fragment into
 */
netric.location.Route = function(parentRouter, segmentName, controller, opt_data, opt_element) {

	/**
	 * Path of this route segment
	 * 
	 * @type {string}
	 */
	this.name_ = segmentName;

	/** 
	 * Set and cache number of segments represented in this route
	 *
	 * @private
	 * @type {int}
	 */
	this.numPathSegments_ = ("/" == segmentName) ? 1 : this.name_.split("/").length;

	/**
	 * Parent inbound router
	 *
	 * @private
	 * @type {netric.location.Router}
	 */
	this.parentRouter_ = parentRouter;

	/**
	 * Controller class that acts and the UI handler for this route
	 * 
	 * This is just the class name, it has not yet been instantiated
	 *
	 * @type {classname: netric.controller.AbstractController}
	 */
	this.controllerClass_ = controller;

	/**
	 * Cached instance of this.controllerClass_
	 *
	 * We are lazy with the loading of the controller to preserve resources
	 * until absolutely necessary.
	 *
	 * @type {netric.controller.AbstractController}
	 */
	this.controller_ = null;

	/**
	 * Data to pass to the controller once instantiated
	 *
	 * @private
	 * @type {Object}
	 */
	this.controllerData_ = opt_data || {};

	/**
	 * Outbound next-hop router
	 *
	 * @private
	 * @type {netric.location.Router}
	 */
	this.nexthopRouter_ = new netric.location.Router(this.parentRouter_);

	/**
	 * The domNode that we should render this route into
	 *
	 * @private
	 * @type {ReactElement|DomElement}
	 */
	this.domNode_ = opt_element;

}

/**
 * Called when the router moves to this route for the first time
 *
 * @param {Object} opt_params Optional URL params object
 * @param {function} opt_callback If set call this function when we are finished loading route
 */
netric.location.Route.prototype.enterRoute = function(opt_params, opt_callback) {

	var doneLoadingCB = opt_callback || null;

	// Instantiate the controller if not already done (lazy load)
	if (null == this.controller_) {
		this.controller_ = new this.controllerClass_;
	}

	// Load up the controller and pass the callback if set
	this.controller_.load(this.controllerData_, this.domNode_, this.getChildRouter(), doneLoadingCB);
}

/**
 * Called when the router moves away from this route to show an alternate route
 */
netric.location.Route.prototype.exitRoute = function() {
	// Exit all childen first
	if (this.getChildRouter().getActiveRoute()) {
		this.getChildRouter().getActiveRoute().exitRoute();
	}

	// Now unload the controller
	if (this.getController()) {

		this.getController().unload();

		// Delete the controller object
		this.controller_ = null;
	}
}

/**
 * Get this route segment name
 *
 * @return {string}
 */
netric.location.Route.prototype.getName = function() {
	return this.name_;
}

/**
 * Get the full path to this route
 *
 * @return {string} Full path leading up to and including this path
 */
netric.location.Route.prototype.getPath = function() {
	return this.parentRouter_.getActivePath();
}

/**
 * Get the router for the next hops
 */
netric.location.Route.prototype.getChildRouter = function() {
	return this.nexthopRouter_;
}

/**
 * Get the number of segments in this route path name
 * 
 * This is important paths like myroute/:varA/:varB
 * because we need to pull all three segmens from a path
 * in order to determine if the route matches any given path.
 *
 * @return {int} The number of segments this route handles
 */
netric.location.Route.prototype.getNumPathSegments = function() {
	return this.numPathSegments_;
}

/**
 * Test this route against a path to see if it matches
 *
 * @param {string} path The path to test
 * @return {Object|null} If a match is found it retuns an object with .params object and nextHopPath to continue route
 */
netric.location.Route.prototype.matchesPath = function(path) {

	// If this is a simple one to one then retun a basic match and save cycles
	if (path === this.name_ || ("" == path && this.name_ == "/")) {
		return { path:path, params:{}, nextHopPath:"" }
	}

	// Pull this.numPathSegments_ from the front of the path to test
	var pathReq = this.getPathSegments(path, this.numPathSegments_);
	if (pathReq != null) {
		// Now check for a match and parse params
		var params = netric.location.path.extractParams(this.name_, pathReq.target);

		// If params is null then the path does not match at all
		if (params !== null) {
			return {
				path: pathReq.target,
				params: params,
				nextHopPath: pathReq.remainder
			}
		}
	}

	// No match was found
	return null;
}

/**
 * Extract a number of segments from a path for matching
 *
 * @param {string} path
 * @param {int} numSegments
 * @return {Object} Format: {target:"math/with/my/num/segs", remainder:"any/trailing/path"}
 */
netric.location.Route.prototype.getPathSegments = function(path, numSegments) {

	var testTarget = "";

	var parts = path.split("/");

	// If the path does not have enough segments to match this route then return
	if (parts.length < numSegments)
		return null;

	// Set the targetPath for this route
	var targetPath = "";
	for (var i = 0; i < numSegments; i++) {
		if (targetPath.length > 0 || parts[i] == "") {
			targetPath += "/";
		}

		targetPath += parts[i];
	}

	// Get the remainder
	var rem = "";
	if (parts.length != numSegments) {
		// Step over "/" if exists
		var startPos = ("/" == path[targetPath.length]) ? targetPath.length + 1 : targetPath.length;
		rem = path.substring(startPos); 
	}

	return {target:targetPath, remainder:rem}

} 

/**
 * Get the controller instance for this route
 * 
 * @return {netric.contoller.AbstractController}
 */
netric.location.Route.prototype.getController = function() {
	return this.controller_;
}

/**
* @fileOverview Location adaptor that uses hash
*
* @author:  joe, sky.stebnicki@aereus.com; 
*       Copyright (c) 2015 Aereus Corporation. All rights reserved.
*/

netric.declare("netric.location.adaptor.Hash");

netric.require("netric");

/**
 * Create global namespaces
 */
netric.location = netric.location || {};
netric.location.adaptor = netric.location.adaptor || {};

/**
 * Get the window has adaptor
 *
 * @constructor
 */
netric.location.adaptor.Hash = function() {

  /**
   * Type of action last performed
   * 
   * @type {netric.location.actions}
   */
  this.actionType_ = null;

  // Begin listening for hash changes
  alib.events.listen(window, "hashchange", function(evt) {
    // Check to see if we changes
    if (this.ensureSlash_()){
      // If we don't have an actionType_ then all we know is the hash
      // changed. It was probably caused by the user clicking the Back
      // button, but may have also been the Forward button or manual
      // manipulation. So just guess 'pop'.
      this.notifyChange_(this.actionType_ || netric.location.actions.POP);
      this.actionType_ = null;  
    }
  }.bind(this));
  
}

/**
 * A new location has been pushed onto the stack
 *
 * @param {string} path The path to push onto the history statck
 */
netric.location.adaptor.Hash.prototype.push = function (path) {
  this.actionType_ = netric.location.actions.PUSH;
  window.location.hash = netric.location.path.encode(path);
}

/**
 * The current location should be replaced
 *
 * @param {string} path The path to push onto the history statck
 */
netric.location.adaptor.Hash.prototype.replace = function (path) {
  this.actionType_ = netric.location.actions.REPLACE;
  window.location.replace(window.location.pathname + window.location.search + '#' + netric.location.path.encode(path));
}

/**
 * The most recent path should be removed from the history stack
 */
netric.location.adaptor.Hash.prototype.pop = function () {
    this.actionType_ = netric.location.actions.POP;
    History.back();
}

/**
 * Get the current path from the 'hash' including query string
 */
netric.location.adaptor.Hash.prototype.getCurrentPath = function () {
  return netric.location.path.decode(
    // We can't use window.location.hash here because it's not
    // consistent across browsers - Firefox will pre-decode it!
    window.location.href.split('#')[1] || ''
  );
}

/**
 * Assure that the path begins with a slash '/'
 */
netric.location.adaptor.Hash.prototype.ensureSlash_ = function() {
  var path = this.getCurrentPath();

  if (path.charAt(0) === '/')
    return true;

  this.replace('/' + path);

  return false;
}

/**
 * Notify the listeners that the location has changed
 */
netric.location.adaptor.Hash.prototype.notifyChange_ = function(type) {
  if (type === netric.location.actions.PUSH)
    History.length += 1;

  var data = {
    path: this.getCurrentPath(),
    type: type
  };

  alib.events.triggerEvent(this, "pathchange", data);
}

/**
* @fileOverview Static location proxy for the client to replace things like window.location
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2015 Aereus Corporation. All rights reserved.
*/

netric.declare("netric.location");

netric.require("netric");
netric.require("netric.location.adaptor.Hash");

/**
 * Create global namespace for server settings
 */
netric.location = netric.location || {};

/**
 * The type of actions that can take place in the location of this device
 */
netric.location.actions = {

  // Indicates a new location is being pushed to the history stack.
  PUSH: 'push',

  // Indicates the current location should be replaced.
  REPLACE: 'replace',

  // Indicates the most recent entry should be removed from the history stack.
  POP: 'pop'

};

/**
 * Location adaptor handle
 *
 * @private
 * @type {netric.location.adaptor}
 */
netric.location.adaptor_ = null;

/**
 * Go to a path
 */
netric.location.go = function(path) {
	// Push the location to the current adaptor
	this.getAdaptor().push(path);
}

/**
 * Setup a router to listen for location change events
 * 
 * @param {netric.location.Router} router The router that handles location changes
 * @param {netric.location.adaptor} opt_handler Manually set the location adaptor
 */
netric.location.setupRouter = function(router, opt_adaptor) {
	// Check to see if we should be manually setting the adaptor
	if (opt_adaptor) {
		this.setAdaptor(opt_adaptor);
	}

	/*
	 * Get the location adaptor which will setup a listener which calls this.triggerPathChange_
	 * when the location of the adaptor changes.
	 */
	var adaptor = this.getAdaptor();

	// Listen for a path change and tell the router to go to that path
	alib.events.listen(this, "pathchange", function(evt) {
		router.go(evt.data.path);
	});

	// Go to the current path in the adaptor
	var currentPath = adaptor.getCurrentPath();
	if (currentPath) {
		router.go(currentPath);
	} else {
		router.go("/");
	}
}

/** 
 * Temp hack
 */
netric.location.checkNav = function() {
	var load = "";
	if (document.location.hash)
	{
		var load = document.location.hash.substring(1);
	}
    
	if (load == "" && this.defaultRoute != "")
		load = this.defaultRoute;

	if (load != "" && load != this.lastLoaded)
	{
		this.lastLoaded = load;
		//ALib.m_debug = true;
		//ALib.trace(load);
		this.triggerPathChange_(load, netric.location.actions.PUSH);
	}
}

/**
 * Trigger location change events
 *
 * @param {string} path The path we changed to
 * @param {netric.location.actions} type The type of action that triggered the event
 */
netric.location.triggerPathChange_ = function(path, type) {
	alib.events.triggerEvent(this, "pathchange", {path:path, actionType:type});
}

/**
 * Get the location adaptor
 * 
 * @return {netric.location.adaptor}
 */
netric.location.getAdaptor = function() {
	// If we do not have an adaptor set then get the best option with setAdaptor
	if (null == this.adaptor_)
		this.setAdaptor();

	return this.adaptor_;
}

/**
 * Initialize location managers
 *
 * @param {Object} opt_handler Manually set the location adaptor
 */
netric.location.setAdaptor = function(opt_adaptor) {

	// Set local location adaptor
	this.adaptor_ = opt_adaptor || this.getBestAdaptor_();

	alib.events.listen(this.adaptor_, "pathchange", function(evt) {
		netric.location.triggerPathChange_(evt.data.path, evt.data.type);
	});

	return this.adaptor_;
}

/**
 * Detect best adaptor for current device
 *
 * @private
 */
netric.location.getBestAdaptor_ = function() {
	return new netric.location.adaptor.Hash();
}
/**
* @fileOverview Utilities for dealing with a path
*
* @author:  joe, sky.stebnicki@aereus.com; 
*       Copyright (c) 2015 Aereus Corporation. All rights reserved.
*/

alib.declare("netric.location.path");

alib.require("netric.location")

/** 
 * Create path namespace for path utility funtions
 */
netric.location.path = netric.location.path || {};

/**
 * Setup patterns
 */
netric.location.path.patterns = {
  paramCompileMatcher: /:([a-zA-Z_$][a-zA-Z0-9_$]*)|[*.()\[\]\\+|{}^$]/g,
  paramInjectMatcher:  /:([a-zA-Z_$][a-zA-Z0-9_$?]*[?]?)|[*]/g,
  paramInjectTrailingSlashMatcher: /\/\/\?|\/\?/g,
  queryMatcher: /\?(.+)/
}

/**
 * Cache compiled patterns
 *
 * @private
 * @type {Object}
 */
netric.location.path.compiledPatterns_ = {};

/**
 * Safely decodes special characters in the given URL path.
 */
netric.location.path.decode = function (path) {
  return decodeURI(path.replace(/\+/g, ' '));
}

/**
 * Safely encodes special characters in the given URL path.
 */
netric.location.path.encode = function (path) {
  return encodeURI(path).replace(/%20/g, '+');
}

/**
 * Compile a pattern and cache it so we don't do a RegExp evey single route change
 *
 * @private
 * @param {string} pattern The pattern to look for in the given path
 */
netric.location.path.compilePattern_ = function(pattern) {

  if (!(pattern in this.compiledPatterns_)) {
    var paramNames = [];
    var source = pattern.replace(this.patterns.paramCompileMatcher, function (match, paramName) {
      if (paramName) {
        paramNames.push(paramName);
        return '([^/?#]+)';
      } else if (match === '*') {
        paramNames.push('splat');
        return '(.*?)';
      } else {
        return '\\' + match;
      }
    });

    this.compiledPatterns_[pattern] = {
      matcher: new RegExp('^' + source + '$', 'i'),
      paramNames: paramNames
    };
  }

  return this.compiledPatterns_[pattern];
}

/**
 * Returns an array of the names of all parameters in the given pattern.
 *
 * @public
 * @param {string} pattern The pattern to look for in the given path
 * @returns {Object} Object with a .matcher RegExp and a .paramNames array
 */
netric.location.path.extractParamNames = function(pattern) {
  return this.compilePattern_(pattern).paramNames;
}

/**
   * Extracts the portions of the given URL path that match the given pattern
   * and returns an object of param name => value pairs. Returns null if the
   * pattern does not match the given path.
   */
netric.location.path.extractParams = function(pattern, path) {

  var object = this.compilePattern_(pattern);
  var match = path.match(object.matcher);

  if (!match)
    return null;

  var params = {};

  object.paramNames.forEach(function(paramName, index) {
    params[paramName] = match[index + 1];
  });

  return params;
}

/**
 * Returns a version of the given route path with params interpolated. Throws
 * if there is a dynamic segment of the route path for which there is no param.
 */
netric.location.path.injectParams = function(pattern, params) {
  params = params || {};

  var splatIndex = 0;
  

  return pattern.replace(this.patterns.paramInjectMatcher, function(match, paramName) {
    paramName = paramName || 'splat';

    // If param is optional don't check for existence
    if (paramName.slice(-1) !== '?') {
      if (params[paramName] == null) {
        throw 'Missing "' + paramName + '" parameter for path "' + pattern + '"';  
      }
      
    } else {
      paramName = paramName.slice(0, -1);

      if (params[paramName] == null)
        return '';
    }

    var segment;
    if (paramName === 'splat' && Array.isArray(params[paramName])) {
      segment = params[paramName][splatIndex++];

      if (segment == null) {
        throw 'Missing splat #' + splatIndex + ' for path "' + pattern + '"';  
      }
      
    } else {
      segment = params[paramName];
    }

    return segment;
  }).replace(netric.location.path.patterns.paramInjectTrailingSlashMatcher, '/');
}

/**
 * Returns an object that is the result of parsing any query string contained
 * in the given path, null if the path contains no query string.
 */
netric.location.path.extractQuery = function(path) {
  var match = path.match(this.patterns.queryMatcher);
  return match && this.parseQuery(match[1]);
}

/**
 * Returns a version of the given path without the query string.
 */
netric.location.path.withoutQuery = function(path) {
  return path.replace(this.patterns.queryMatcher, '');
}

/**
 * Returns true if the given path is absolute.
 */
netric.location.path.isAbsolute = function(path) {
  return path.charAt(0) === '/';
}

/**
 * Returns a normalized version of the given path.
 */
netric.location.path.normalize = function(path, parentRoute) {
  return path.replace(/^\/*/, '/');
}

/**
 * Joins two URL paths together.
 */
netric.location.path.join = function(a, b) {
  return a.replace(/\/*$/, '/') + b;
}

/**
 * Returns a version of the given path with the parameters in the given
 * query merged into the query string.
 */
netric.location.path.withQuery = function(path, query) {
  var existingQuery = this.extractQuery(path);

  // Merge query objects
  if (existingQuery)
  {
    var merged = {};
    for (var attrname in existingQuery) { merged[attrname] = existingQuery[attrname]; }
    for (var attrname in query) { merged[attrname] = query[attrname]; }
    query = merged;
  }

  //var queryString = query && qs.stringify(query);
  var queryString = this.stringifyQuery(query);

  if (queryString)
    return this.withoutQuery(path) + '?' + queryString;

  return path;
}

/**
 * Convert an object into a query string
 *
 * @param {Object} queryObj An objet with key values
 * @return {string} A string representation of the object
 */
netric.location.path.stringifyQuery = function(queryObj) {
  return queryObj ? Object.keys(queryObj).map(function (key) {
      var val = queryObj[key];

      if (Array.isArray(val)) {
        return val.map(function (val2) {
          return encodeURIComponent(key + "[]") + '=' + encodeURIComponent(val2);
        }).join('&');
      }

      return encodeURIComponent(key) + '=' + encodeURIComponent(val);
    }).join('&') : '';
}

/**
 * Parse a query string and return an object
 * 
 * @param {string} str The query string to parse
 * @return {} Key value for each query string param
 */
netric.location.path.parseQuery = function (str) {
  if (typeof str !== 'string') {
    return {};
  }

  str = str.trim().replace(/^(\?|#)/, '');

  if (!str) {
    return {};
  }

  return str.trim().split('&').reduce(function (ret, param) {
    var parts = param.replace(/\+/g, ' ').split('=');
    var key = parts[0];
    var val = parts[1];

    key = decodeURIComponent(key);

    // If key is an array it will be postfixed with [] which should be removed
    key = key.replace("[]", "");

    // missing `=` should be `null`:
    // http://w3.org/TR/2012/WD-url-20120524/#collect-url-parameters
    val = val === undefined ? null : decodeURIComponent(val);

    if (!ret.hasOwnProperty(key)) {
      ret[key] = val;
    } else if (Array.isArray(ret[key])) {
      ret[key].push(val);
    } else {
      ret[key] = [ret[key], val];
    }

    return ret;
  }, {});
};

/**
* @fileOverview Proxy to handle errors and logging
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/

alib.declare("netric.log");

alib.require("netric");

/**
 * Create global namespace for server settings
 */
netric.log = netric.log || {};

/**
 * Write an error to the log
 *
 * @public
 * @var {string} message
 */
netric.log.error = function(message) {
	// Get the name of the calling function
	var myName = arguments.callee.toString();
	/*
	myName = myName.substr('function '.length);
	myName = myName.substr(0, myName.indexOf('('));
	*/

	console.log(myName + ":" + message);
}
/**
* @fileOverview Modules are sub-applications within the application framework
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.module.Module");

alib.require("netric");

/**
 * Make sure module namespace is initialized
 */
netric.module = netric.module || {};

/**
 * Application module instance
 *
 * @param {Object} opt_data Optional data for loading the module
 */
netric.module.Module = function(opt_data) {
	var data = opt_data || new Object();

	/**
	 * Unique name for this module
	 * 
	 * @public
	 * @type {string}
	 */
	this.name = data.name || "";

	/**
	 * Human readable title
	 * 
	 * @public
	 * @type {string}
	 */
	this.title = data.title || "";
}

/**
 * Static function used to load the module
 *
 * @param {function} opt_cbFunction Optional callback function once module is loaded
 */
netric.module.Module.load = function(opt_cbFunction) {
	// TODO: load module definition
}

/**
 * Run the loaded module
 *
 * @param {DOMElement} domCon Container to render module into
 */
netric.module.Module.prototype.run = function(domCon) {
	// TODO: render module into domCon
}
/**
* @fileOverview Module loader
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.module.loader");

alib.require("netric");
alib.require("netric.module.Module")

/**
 * Make sure module namespace is initialized
 */
netric.module = netric.module || {};

/**
 * Global module loader
 *
 * @param {netric.Application} application Application instance
 */
netric.module.loader = netric.module.loader || {};

/**
 * Loaded applications
 *
 * @private
 * @param {Array}
 */
netric.module.loader.loadedModules_ = new Array();

/**
 * Static function used to load the module
 *
 * @param {string} moduleName The name of the module to load
 * @param {function} cbLoaded Callback function once module is loaded
 */
netric.module.loader.get = function(moduleName, cbLoaded)
{
	// Return (or callback callback) cached module if already loaded
	if (this.loadedModules_[moduleName]) {
		
		if (cbLoaded) {
			cbLoaded(this.loadedModules_[moduleName]);
		}

		return this.loadedModules_[moduleName];
	}

	var request = new netric.BackendRequest();

	if (cbLoaded) {
		alib.events.listen(request, "load", function(evt) {
			var module = netric.module.loader.createModuleFromData(this.getResponse());
			cbLoaded(module);
		});
	} else {
		// Set request to be synchronous if no callback is set	
		request.setAsync(false);
	}

	request.send("/svr/module/get", "GET", {name:moduleName});

	// If no callback then construct netric.module.Module from request date (synchronous)
	if (!cbLoaded) {
		return this.createModuleFromData(request.getResponse());
	}
}

/** 
 * Map data to an module object
 *
 * @param {Object} data The data to create an module from
 */
netric.module.loader.createModuleFromData = function(data) {
	
	var module = new netric.module.Module(data);

	// Make sure the name was set to something other than ""
	if (module.name.length) {
		this.loadedModules_[module.name] = module;		
	}

	return module;
}

/** 
 * Preload/cache modules from data
 *
 * Use data to preload or cache modules by name
 *
 * @param {Object[]} modulesData
 */
netric.module.loader.preloadFromData = function(modulesData) {
	for (var i in modulesData) {
		this.createModuleFromData(modulesData[i]);
	}
}
/**
 * @fileoverview mvc namespace
 */

alib.declare("netric.mvc");

alib.require("netric");

/**
 * The MVC namespace where all MVC core functionality will liveß
 */
netric.mvc = netric.mvc || {};
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function notUsed()
{
    // This function is not used
}
/**
* @fileOverview Server settings object
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/

alib.declare("netric.server");

alib.require("netric");

/**
 * Create global namespace for server settings
 */
netric.server = netric.server || {};

/**
 * Server host
 * 
 * If = "" then assume server is hosted from the same origin
 * as the client, as in from the web server.
 *
 * If this is set, then make sure the auth token has been
 * negotiated and set.
 *
 * @public
 * @var {string}
 */
netric.server.host = "";
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
alib.declare("subfunction");

function subfunction()
{
    return "This is test from the subfunction!";
}
/** 
 * @fileoverview View templates for the application in full desktop mode
 */
 alib.declare("netric.template.application.large");

 /**
  * Make sure tha namespace exists for template
  */

 /**
 * Make sure module namespace is initialized
 */
netric.template = netric.template || {};
netric.template.application = netric.template.application || {};

/**
 * Large and medium templates will use this same template
 *
 * @param {Object} data Used for rendering the template
 * @return {string|netric.mvc.ViewTemplate} Either returns a string or a ViewTemplate object
 */
netric.template.application.large = function(data) {
	
	/*
	<!-- application header -->
	<div id='appheader' class='header'>
		<!-- right actions -->
		<div id='headerActions'>
			<table border='0' cellpadding="0" cellspacing="0">
			<tr valign="middle">			
				<!-- notifications -->
				<td style='padding-right:10px'><div id='divAntNotifications'></div></td>

				<!-- chat -->
				<td style='padding-right:10px'><div id='divAntChat'></div></td>

				<!-- new object dropdown -->
				<td style='padding-right:10px'><div id='divNewObject'></div></td>

				<!-- settings -->
				<td style='padding-right:10px'>
					<a href="javascript:void(0);" class="headerLink" 
						onclick="document.location.hash = 'settings';" 
						title='Click to view system settings'>
							<img src='/images/icons/main_settings_24.png' />
					</a>
				</td>

				<!-- help -->
				<td style='padding-right:10px' id='mainHelpLink'>
					<a href='javascript:void(0);' title='Click to get help'><img src='/images/icons/help_24_gs.png' /></a>
				 </td>
				<td id='mainProfileLink'>
					<a href='javascript:void(0);' title='Logged in as <?php echo $USER->fullName; ?>'><img src="/files/userimages/current/0/24" style='height:24px;' /></a>
				</td>
			</tr>
			</table>
		</div>

		<!-- logo -->
		<div class='headerLogo'>
		<?php
			$header_image = $ANT->settingsGet("general/header_image");
			if ($header_image)
			{
				echo "<img src='/antfs/images/$header_image' />";
			}
			else
			{
				echo "<img src='/images/netric-logo-32.png' />";

			}
		?>
		</div> 
		<!-- end: logo -->
		
		<!-- middle search -->
		<div id='headerSearch'><div id='divAntSearch'></div></div>

		<div style="clear:both;"></div>
	</div>
	<!-- end: application header -->

	<!-- application tabs -->
	<div id='appnav'>
		<div class='topNavbarHr'></div>
		<div class='topNavbarBG' id='apptabs'></div>
		<div class='topNavbarShadow'></div>
	</div>
	<!-- end: application tabs -->

	<!-- application body - where the applications load -->
	<div id='appbody'>
	</div>
	<!-- end: application body -->

	<!-- welcome dialog -->
	<div id='tour-welcome' style='display:none;'>
		<div data-tour='apps/netric' data-tour-type='dialog'></div>
	</div>
	<!-- end: welcome dialog -->
	*/

	var vt = new netric.mvc.ViewTemplate();

	// Add header
	// ------------------------------------------
	vt.header = alib.dom.createElement("div", null, null, {id:"app-header"});
	alib.dom.styleAddClass(vt.header, "app-header app-header-large");
	vt.addElement(vt.header);

	// Add logo
	var headerLogo = alib.dom.createElement("div", vt.header);
	alib.dom.styleAddClass(headerLogo, "app-header-logo-con");
	headerLogo.innerHTML = "<i class=\"fa fa-bars fa-lg\"></i> <img src=\"" + data.logoSrc + "\" id='app-header-logo' />";

	// Add search box
	vt.headerSearchCon = alib.dom.createElement("div", vt.header, null, {id:"app-header-search"});
	alib.dom.styleAddClass(vt.headerSearchCon, "app-header-search-con");
	vt.headerSearchCon.innerHTML = "Search goes here";

	// Add profile area
	vt.headerProfileCon = alib.dom.createElement("div", vt.header, null, {id:"app-header-search"});
	alib.dom.styleAddClass(vt.headerProfileCon, "app-header-profile-con");
	vt.headerProfileCon.innerHTML = "<i class=\"fa fa-camera-retro fa-lg\"></i>";

	// Add module body
	// ------------------------------------------
	vt.bodyCon = alib.dom.createElement("div", null, null, {id:"app-body"});
	alib.dom.styleAddClass(vt.bodyCon, "app-body");
	alib.dom.styleAddClass(vt.bodyCon, "app-body-large");
	vt.bodyCon.innerHTML = "Put the app body here!";
	vt.addElement(vt.bodyCon);

	return vt;
}

/** 
 * @fileoverview View templates for the application in full desktop mode
 */
 alib.declare("netric.template.application.small");

 /**
 * Make sure module namespace is initialized
 */
netric.template = netric.template || {};
netric.template.appication = netric.template.application || {};

/**
 * Large and medium views will use this same template
 *
 * @param {Object} data Used for rendering the template
 */
netric.template.application.small = function(data) {
	var vt = new netric.mvc.ViewTemplate();

	var header = alib.dom.createElement("div", null, null, {id:"app-header-small"});
	header.innerHTML = "Mobile Header";
	vt.addElement(header);
	vt.header = header; // Add for later reference

	vt.bodyCon = alib.dom.createElement("p");
	vt.bodyCon.innerHTML = "Put the app body here!";
	vt.addElement(vt.bodyCon);

	return vt;
}
/**
 * Main application toolbar for small/mobile devices
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.ActionBar");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Small application component
 */
netric.ui.ActionBar = React.createClass({displayName: "ActionBar",

	render: function() {

		var navBtn = "X";

		// Set the back/menu button
		if (this.props.onNavBtnClick) {
			var navBtn = React.createElement("i", {className: "fa fa-bars fa-lg", onClick: this.props.onNavBtnClick});
		}

		return (
		  React.createElement("div", {className: "app-header app-header-small"}, 
		    navBtn, 
		    React.createElement("span", null, this.props.title)
		  )
		);
	}
});
/**
 * Render an entity browser
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.EntityBrowser");

alib.require("netric.ui.ActionBar");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Module shell
 */
netric.ui.EntityBrowser = React.createClass({displayName: "EntityBrowser",

  getInitialState: function() {
    return {name: "Browser"};
  },

  /*
  componentDidMount: function() {

    netric.module.loader.get("messages", function(mdl){
      this.setState({name: mdl.name});
    }.bind(this));
  },
  */

  render: function() {

    var actionBar = "";

    if (this.props.onNavBtnClick) {
      actionBar = React.createElement(netric.ui.ActionBar, {title: this.state.name, onNavBtnClick: this.menuClick_});
    } else {
      actionBar = React.createElement(netric.ui.ActionBar, {title: this.state.name});
    }

    return (
      React.createElement("div", null, 
        React.createElement("div", null, 
          actionBar
        ), 
        React.createElement("div", {ref: "moduleMain"}, 
          "Browser loaded"
        )
      )
    );
  },

  // The menu item was clicked
  menuClick_: function(evt) {
    if (this.props.onNavBtnClick)
      this.props.onNavBtnClick(evt);
  },

});

/**
 * LeftNav componenet
 *
 * @jsx React.DOM
 */

/** 
 * Make sure ui namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Small application component
 */
netric.ui.LeftNav = React.createClass({displayName: "LeftNav",

  //mixins: [Classable, WindowListenable],

  propTypes: {
    docked: React.PropTypes.bool,
    header: React.PropTypes.element,
    onChange: React.PropTypes.func,
    menuItems: React.PropTypes.array.isRequired,
    selectedIndex: React.PropTypes.number
  },

  windowListeners: {
    'keyup': '_onWindowKeyUp'
  },

  getDefaultProps: function() {
    return {
      docked: true
    };
  },

  getInitialState: function() {
    return {
      open: this.props.docked,
      selected: ""
    };
  },

  toggle: function() {
    this.setState({ open: !this.state.open });
    return this;
  },

  close: function() {
    this.setState({ open: false });
    return this;
  },

  open: function() {
    this.setState({ open: true });
    return this;
  },

  render: function() {
    // Set the classes
    var classes = "left-nav";
    if (!this.state.open) {
      classes += " closed";
    } 

    classes += (this.props.docked) ? " docked" : " floating";

    var selectedIndex = this.props.selectedIndex,
      overlay;

    if (!this.props.docked) 
      overlay = React.createElement(netric.ui.Overlay, {show: this.state.open, onClick: this._onOverlayTouchTap});

    /* We should nest the menu eventually
    <Menu 
            ref="menuItems"
            zDepth={0}
            menuItems={this.props.menuItems}
            selectedIndex={selectedIndex}
            onItemClick={this._onMenuItemClick} />
            */

    // Add each menu item
    var items = [];
    for (var i in this.props.menuItems) {
        var sltd = (this.state.selected == this.props.menuItems[i].route) ? "*" : "";
        items.push(React.createElement("div", {onClick: this._sendClick.bind(null, i)}, this.props.menuItems[i].name, " ", sltd));
    }

    return (
      React.createElement("div", {className: classes}, 

        overlay, 
        React.createElement(netric.ui.Paper, {
          ref: "clickAwayableElement", 
          className: "left-nav-menu", 
          zDepth: 2, 
          rounded: false}, 
          
          this.props.header, 
          
          React.createElement("div", null, 
            items
          )
        )
      )
    );
  },

  /**
   * Temp click sender to this.onMenuItemClick
   */
  _sendClick: function(i) {
    this._onMenuItemClick(null, i, this.props.menuItems[i]);
  },

  /** 
   * When the menu fires onItemClick it will pass the index and the item data as payload
   *
   * @param {Event} e
   * @param {int} key The index or unique key of the menu entry
   * @param {Object} payload the meny item object
   */
  _onMenuItemClick: function(e, key, payload) {
    if (!this.props.docked) this.close();
    if (this.props.onChange && this.props.selectedIndex !== key) {
      this.props.onChange(e, key, payload);
    }
  },

  _onOverlayTouchTap: function() {
    this.close();
  },

  _onWindowKeyUp: function(e) {
    if (e.keyCode == KeyCode.ESC &&
        !this.props.docked &&
        this.state.open) {
      this.close();
    }
  }

});
/**
 * Menu class used in both drop-downs, overflows, and left navigations
 *
 * TODO: This is a work in progress!
 *
 * @jsx React.DOM
 */

/** 
 * Make sure ui namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Menu component
 */
netric.ui.Menu = React.createClass({displayName: "Menu",

	propTypes: {
		autoWidth: React.PropTypes.bool,
		onItemClick: React.PropTypes.func,
		onToggleClick: React.PropTypes.func,
		menuItems: React.PropTypes.array.isRequired,
		selectedIndex: React.PropTypes.number,
		hideable: React.PropTypes.bool,
		visible: React.PropTypes.bool,
		zDepth: React.PropTypes.number
	},

	getInitialState: function() {
		return { nestedMenuShown: false }
	},

	getDefaultProps: function() {
		return {
			autoWidth: true,
			hideable: false,
			visible: true,
			zDepth: 1
		};
	},

	componentDidMount: function() {
		var el = this.getDOMNode();

		//Set the menu with
		this._setKeyWidth(el);

		//Save the initial menu height for later
		this._initialMenuHeight = el.offsetHeight + KeyLine.Desktop.GUTTER_LESS;

		//Show or Hide the menu according to visibility
		this._renderVisibility();
	},

	componentDidUpdate: function(prevProps, prevState) {
		if (this.props.visible !== prevProps.visible) this._renderVisibility();
	},

  render: function() {
    var classes = this.getClasses('mui-menu', {
      'mui-menu-hideable': this.props.hideable,
      'mui-visible': this.props.visible
    });

    return (
      React.createElement(Paper, {ref: "paperContainer", zDepth: this.props.zDepth, className: classes}, 
        this._getChildren()
      )
    );
  },

  _getChildren: function() {
    var children = [],
      menuItem,
      itemComponent,
      isSelected;

    //This array is used to keep track of all nested menu refs
    this._nestedChildren = [];

    for (var i=0; i < this.props.menuItems.length; i++) {
      menuItem = this.props.menuItems[i];
      isSelected = i === this.props.selectedIndex;

      switch (menuItem.type) {

        case MenuItem.Types.LINK:
          itemComponent = (
            React.createElement("a", {key: i, index: i, className: "mui-menu-item", href: menuItem.payload}, menuItem.text)
          );
        break;

        case MenuItem.Types.SUBHEADER:
          itemComponent = (
            React.createElement("div", {key: i, index: i, className: "mui-subheader"}, menuItem.text)
          );
          break;

        case MenuItem.Types.NESTED:
          itemComponent = (
            React.createElement(NestedMenuItem, {
              ref: i, 
              key: i, 
              index: i, 
              text: menuItem.text, 
              menuItems: menuItem.items, 
              zDepth: this.props.zDepth, 
              onItemClick: this._onNestedItemClick})
          );
          this._nestedChildren.push(i);
          break;

        default:
          itemComponent = (
            React.createElement(MenuItem, {
              selected: isSelected, 
              key: i, 
              index: i, 
              icon: menuItem.icon, 
              data: menuItem.data, 
              attribute: menuItem.attribute, 
              number: menuItem.number, 
              toggle: menuItem.toggle, 
              onClick: this._onItemClick, 
              onToggle: this._onItemToggle}, 
              menuItem.text
            )
          );
      }
      children.push(itemComponent);
    }

    return children;
  },

  _setKeyWidth: function(el) {
    var menuWidth = this.props.autoWidth ?
      KeyLine.getIncrementalDim(el.offsetWidth) + 'px' :
      '100%';

    //Update the menu width
    Dom.withoutTransition(el, function() {
      el.style.width = menuWidth;
    });
  },

  _renderVisibility: function() {
    var el;

    if (this.props.hideable) {
      el = this.getDOMNode();
      var innerContainer = this.refs.paperContainer.getInnerContainer().getDOMNode();
      
      if (this.props.visible) {

        //Open the menu
        el.style.height = this._initialMenuHeight + 'px';

        //Set the overflow to visible after the animation is done so
        //that other nested menus can be shown
        CssEvent.onTransitionEnd(el, function() {
          //Make sure the menu is open before setting the overflow.
          //This is to accout for fast clicks
          if (this.props.visible) innerContainer.style.overflow = 'visible';
        }.bind(this));

      } else {

        //Close the menu
        el.style.height = '0px';

        //Set the overflow to hidden so that animation works properly
        innerContainer.style.overflow = 'hidden';
      }
    }
  },

  _onNestedItemClick: function(e, index, menuItem) {
    if (this.props.onItemClick) this.props.onItemClick(e, index, menuItem);
  },

  _onItemClick: function(e, index) {
    if (this.props.onItemClick) this.props.onItemClick(e, index, this.props.menuItems[index]);
  },

  _onItemToggle: function(e, index, toggled) {
    if (this.props.onItemToggle) this.props.onItemToggle(e, index, this.props.menuItems[index], toggled);
  }
});

/**
 * Render a module
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.Module");

alib.require("netric.ui.ActionBar");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Module shell
 */
netric.ui.Module = React.createClass({displayName: "Module",

  getInitialState: function() {
    return {name: "Loading..."};
  },

  getDefaultProps: function() {
    return {
      leftNavDocked: false
    };
  },

  componentDidMount: function() {

    netric.module.loader.get("messages", function(mdl){
      this.setState({name: mdl.name});
    }.bind(this));

    
  },

  render: function() {

    // Set module main 
    var moduleMainClass = "module-main";
    if (this.props.leftNavDocked) {
      moduleMainClass += " left-nav-docked";
    }      

    return (
      React.createElement("div", null, 
        React.createElement(netric.ui.LeftNav, {onChange: this.onLeftNavChange_, ref: "leftNav", menuItems: this.props.leftNavItems, docked: this.props.leftNavDocked}), 
        React.createElement("div", {ref: "moduleMain", className: moduleMainClass}
        )
      )
    );
  },

  // The left navigation was changed
  onLeftNavChange_: function(evt, index, payload) {
    if (this.props.onLeftNavChange) {
      this.props.onLeftNavChange(evt, index, payload);
    }
  }

});

/**
 * Overlay used to hide elements below current displayed componenet
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.Overlay");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Small application component
 */
netric.ui.Overlay = React.createClass({displayName: "Overlay",
	// mixins: [Classable],

	propTypes: {
		show: React.PropTypes.bool
	},

	render: function() {

		var classes = "overlay";
		if (this.props.show) {
			classes += " is-shown";
		}

		return (
			React.createElement("div", React.__spread({},  this.props, {className: classes}))
		);
	}
});
/**
 * Paper is a concept taken from google Material design standards
 *
 * @jsx React.DOM
 */

/** 
 * Make sure ui namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};

/**
 * Small application component
 */
netric.ui.Paper = React.createClass({displayName: "Paper",

  //mixins: [Classable],

  propTypes: {
    circle: React.PropTypes.bool,
    innerClassName: React.PropTypes.string,
    rounded: React.PropTypes.bool,
    zDepth: React.PropTypes.oneOf([0,1,2,3,4,5])
  },

  getDefaultProps: function() {
    return {
      innerClassName: '',
      rounded: true,
      zDepth: 1
    };
  },

  render: function() {

      var classes = "";

      if (this.props.className) {
        classes += this.props.className + " ";
      } 

      classes += "paper z-depth-" + this.props.zDepth;
      
      if (this.props.rounded) {
        classes += " rounded";
      }
      
      if (this.props.circle) {
        classes += " mui-circle"; 
      }
      
      var insideClasses = 
        this.props.innerClassName + ' ' +
        'paper-container ' +
        'z-depth-bottom';

    return (
      React.createElement("div", React.__spread({},  this.props, {className: classes}), 
        React.createElement("div", {ref: "innerContainer", className: insideClasses}, 
          this.props.children
        )
      )
    );
  },

  getInnerContainer: function() {
    return this.refs.innerContainer;
  }

});
/**
 * Render the application shell for a large device
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.application.Large");

alib.require("netric.ui.ActionBar");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};
netric.ui.application = netric.ui.application || {};

/**
 * Large application component
 */
netric.ui.application.Large = React.createClass({displayName: "Large",
  getInitialState: function() {
    return {orgName: this.props.orgName};
  },
  render: function() {
    return (
      React.createElement("div", null, 
        React.createElement("div", {id: "app-header", className: "app-header app-header-large"}, 
          React.createElement("div", {id: "app-header-logo", className: "app-header-logo-con"}, 
            React.createElement("img", {src: this.props.logoSrc, id: "app-header-logo"})
          ), 
          React.createElement("div", {id: "app-header-search", className: "app-header-search-con"}, 
            "Search goes here ", React.createElement("a", {onClick: function() {netric.location.go("/messages")}}, "Go to messages")
          ), 
          React.createElement("div", {className: "app-header-profile-con"}, 
            React.createElement("i", {className: "fa fa-camera-retro fa-lg"})
          )
        ), 
        React.createElement("div", {id: "app-body", ref: "appMain", className: "app-body app-body-large"}
        )
      )
    );
  }
});

/**
 * Render the application shell for a small device
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.application.Small");

alib.require("netric.ui.ActionBar");

/** 
 * Make sure namespace exists
 */
var netric = netric || {};
netric.ui = netric.ui || {};
netric.ui.application = netric.ui.application || {};

/**
 * Small application component
 */
netric.ui.application.Small = React.createClass({displayName: "Small",
  
  getInitialState: function() {
    return {orgName: this.props.orgName};
  },

  render: function() {
    return (
      React.createElement("div", null, 
        React.createElement("a", {href: "javascript:netric.location.go('" + this.props.basePath + "messages" + "')"}, "Go to messages"), 
        React.createElement("div", {ref: "appMain"})
      )
    );
  }
});

/**
 * My first test component
 *
 * @jsx React.DOM
 */

alib.declare("netric.ui.component.test");

var netric = netric || {};
netric.ui = netric.ui || {};
netric.ui.component = netric.ui.component || {};

netric.ui.TestSubElement = React.createClass({displayName: "TestSubElement",
  render: function() {
    return (
      React.createElement("p", null, "Your name is: ", this.props.name, "...")
    );
  }
});

netric.ui.component.test = React.createClass({displayName: "test",
  getInitialState: function() {
    return {name: this.props.name};
  },
  changeName: function(changeNameTo) {
    this.setState({name: changeNameTo});
  },
  render: function() {
    return (
      React.createElement("div", null, 
        React.createElement("h1", null, "Hello ", this.state.name, "!"), 
        React.createElement(netric.ui.TestSubElement, {name: this.state.name})
      )
    );
  }
});

/**
* @fileOverview Base DataMapper to be used throughout netric for loading server data into objects
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.AbstractDataMapper");

alib.require("netric");

/**
 * Abstract base datamapper used for all datamappers throughout netric
 */
netric.AbstractDataMapper = function() {
	// All datamappers should first try to load locally
	//	If it exists then start an update sync if we are connected and one is not running
	//  If it does not exist, then try to connect
}

/**
 * Get data from a local or remote store
 *
 * Example:
 * <code>
 *	dm.get("entity/email_message/100001");
 *	dm.get("objdefs/email_message");
 * </code>
 *
 * @private
 * @param {string} sourcePath The unique path of the data to get
 */
netric.AbstractDataMapper.prototype.open = function(sourcePath) {
}

/**
 * Save data
 * 
 * Example:
 * <code>
 *	var data = {amount:"100", name: "Text Name"};
 *	dm.save("entity/customer/100", data);
 * </code>
 *
 * @private
 * @param {string} sourcePath The unique path of the data to get
 */
netric.AbstractDataMapper.prototype.save = function(sourcePath, data) {

}

/**
 * Query a list of data
 *
 * @private
 * @param {string} sourcePath The path to the list to query
 * @param {int} offset What page to start on
 * @param {int} limit The maximum number of items to return
 * @param {Object} conditions QueryDSL(?) conditions object
 */
netric.AbstractDataMapper.prototype.query = function(sourcePath, offset, limit, conditions) {

}
/**
* @fileOverview Backend request object used to direct requests local or to remote server
*
* Example:
* <code>
* 	var request = new netric.BackendRequest();
*	
*	// Setup callback for successful load
*	alib.events.listen(request, "load", function(evt) { 
* 		var data = this.getResponse();
*		alert(evt.data.passVarToEvtData); // Prompts "MyData"
*	}, {passVarToEvtData:"MyData"});
*
*	// Set callback on error
*	alib.events.listen(request, "error", function(evt) { } );
*
*	var ret = request.send("/controller/object/getDefinition", "POST", {obj_type:this.objType});
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.BackendRequest");

alib.require("netric");
alib.require("netric.server");

/**
 * Class for handling XMLHttpRequests
 *
 * @constructor
 */
netric.BackendRequest = function() {
	/**
	 * Handle to server xhr to use when connected
	 *
	 * @private
	 * @type {alib.net.Xhr}
	 */
	this.netXhr_ = new alib.net.Xhr();
}

/**
 * What kind of data is being returned
 *
 * Can be xml, json, script, text, or html
 *
 * @private
 * @type {string}
 */
netric.BackendRequest.prototype.returnType_ = "json";

/**
 * Determine whether or not we will send async or hang the UI until request returns (yikes)
 *
 * @private
 * @type {bool}
 */
netric.BackendRequest.prototype.isAsync_ = true;

/**
 * Number of seconds before the request times out
 *
 * 0 means no timeout
 *
 * @private
 * @type {int}
 */
netric.BackendRequest.prototype.timeoutInterval_ = 0;

/**
 * Buffer for response
 *
 * @private
 * @type {bool}
 */
netric.BackendRequest.prototype.response_ = null;

/**
 * Static send that creates a short lived instance.
 *
 * @param {string} url Uri to make request to.
 * @param {Function=} opt_callback Callback function for when request is complete.
 * @param {string=} opt_method Send method, default: GET.
 * @param {Object|Array} opt_content Body data if POST.
 * @param {number=} opt_timeoutInterval Number of milliseconds after which an
 *     incomplete request will be aborted; 0 means no timeout is set.
 */
netric.BackendRequest.send = function(url, opt_callback, opt_method, opt_content, opt_timeoutInterval) 
{
	// Set defaults
	if (typeof opt_method == "undefined")
		opt_method = "GET";
	if (typeof opt_content == "undefined")
		opt_content = null;

	// Crete new Xhr instance and send
	var request = new netric.BackendRequest();
	if (opt_callback) {
		alib.events.listen(request, "load", function(evt) { 
			evt.data.cb(this.getResponse); 
		}, {cb:opt_callback});
	}
	
	if (opt_timeoutInterval) {
		request.setTimeoutInterval(opt_timeoutInterval);
	}

	request.send(url, opt_method, opt_content);
	return request;
};

/**
 * Instance send that actually makes a server call.
 *
 * @param {string|goog.Uri} urlPath Uri to make request to.
 * @param {string=} opt_method Send method, default: GET.
 * @param {Array|Object|string=} opt_content Body data.
 */
netric.BackendRequest.prototype.send = function(urlPath, opt_method, opt_content) 
{
	var method = opt_method || "GET";
	var data = opt_content || null;

	// Check if we need to put a prefix on the request
	if (netric.server.host != "") {
		alib.net.prefixHttp = netric.server.host;
	}

	// Set local variable for closure
	var xhr = this.netXhr_;
	var request = this;
	
	// Fire load event
	alib.events.listen(xhr, "load", function(evt){
		alib.events.triggerEvent(request, "load");
	});

	// Fire error event
	alib.events.listen(xhr, "error", function(evt){
		alib.events.triggerEvent(request, "error");
	});

	xhr.send(urlPath, method, data);
}

/**
 * Set what kind of data is being returned
 *
 * @param {string} type Can be "xml", "json", "script", "text", or "html"
 */
netric.BackendRequest.prototype.setReturnType = function(type)
{
	this.returnType_ = type;
}

/**
 * Sets whether or not this request will be made asynchronously
 *
 * Warning: if set to false the UI will hang until the request completes which is annoying
 *
 * @param {bool} asyc If true then set request to async
 */
netric.BackendRequest.prototype.setAsync = function(async)
{
	this.netXhr_.setAsync(async);
}

/**
 * Sets the number of seconds before timeout
 *
 * @param {int} seconds Number of seconds
 */
netric.BackendRequest.prototype.setTimeoutInterval = function(seconds) {
	this.netXhr_.setTimeoutInterval(seconds);
}

/**
 * Abort the request
 */
netric.BackendRequest.prototype.abort = function() {
	if (this.netXhr_)
		this.netXhr_.abort();
}

/**
 * Check if a request is in progress
 *
 * @return bool True if a request is in progress
 */
netric.BackendRequest.prototype.isInProgress = function() {
	return this.netXhr_.isInProgress();
}

/**
 * Get response text from xhr object
 */
netric.BackendRequest.prototype.getResponseText = function() {
	return this.netXhr_.getResponseText();
}

/**
 * Get response text from xhr object
 */
netric.BackendRequest.prototype.getResponseXML = function() {
	return this.netXhr_.getResponseXML();
}

/**
 * Get the parsed response
 */
netric.BackendRequest.prototype.getResponse = function() {
	return this.netXhr_.getResponse();
}
/**
 * @fileoverview Device information class
 * 
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */

alib.declare("netric.Device");

alib.require("netric");

// Information about the current device
netric.Device = function() {
	// Try to determine the current devices size
	this.getDeviceSize_();
}

/**
 * Static device sizes
 * 
 * @const
 * @public
 */
netric.Device.sizes = {
	// Phones and small devices
	small : 1,
	// Tablets
	medium : 3,
	// Desktops
	large : 5
};

/**
 * The size of the current device once loaded
 *
 * @type {netric.Device.sizes}
 */
netric.Device.prototype.size = netric.Device.sizes.large;

/**
 * Detect the size of the current device and set this.size
 *
 * @private
 */
 netric.Device.prototype.getDeviceSize_ = function() {
 	var width = alib.dom.getClientWidth();
 	
 	if (width <= 768) {
 		this.size = netric.Device.sizes.small;
 	} else if (width > 768 && width < 1200) {
 		this.size = netric.Device.sizes.medium;
 	} else if (width >= 1200) {
 		this.size = netric.Device.sizes.large;
 	}
 }
/**
 * @fileOverview Object represents the netric account user
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.User");
alib.require("netric");

/**
 * User instance
 *
 * @param {Object} opt_data Optional data used to initialize the user
 */
netric.User = function(opt_data)
{
	// Initialize empty object if opt_data was not set
	var initData = opt_data || new Object();

	/**
	 * Unique id for this user
	 * 
	 * @public
	 * @type {string}
	 */
	this.id = initData.id || "";

	/**
	 * Unique username for this user
	 * 
	 * @public
	 * @type {string}
	 */
	this.name = initData.name || "";

	/**
	 * Full name is usually combiation of first and last name
	 * 
	 * @public
	 * @type {string}
	 */
	this.fullName = initData.fullName || "";
}
/**
 * @fileoverview Base DataMapper for loading accounts
 */
alib.declare("netric.account.AbstractDataMapper");

alib.require("netric");

/**
 * Make sure account namespace is initialized
 */
netric.account = netric.account || {};

netric.account.AbstractDataMapper = function() 
{
	
}
/**
 * @fileOverview Object represents the netric account object
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.account.Account");
alib.require("netric");
alib.require("netric.module.loader");
alib.require("netric.User");

/**
 * Make sure account namespace is initialized
 */
netric.account = netric.account || {};

/**
 * Account instance
 *
 * @param {Object} opt_data Optional data used to initialize the account
 */
netric.account.Account = function(opt_data)
{
	// Initialize empty object if opt_data was not set
	var initData = opt_data || new Object();

	/**
	 * Account ID
	 *
	 * @public
	 * @type {string}
	 */
	this.id = initData.id || "";

	/**
	 * Unique account name
	 *
	 * @public
	 * @type {string}
	 */
	this.name = initData.name || "";

	/**
	 * Organization name
	 * 
	 * @public
	 * @type {string}
	 */
	this.orgName = initData.orgName || "";

	/**
	 * Currently authenticated user
	 * 
	 * @public
	 * @type {netric.User}
	 */
	this.user = (initData.user) ? new netric.User(initData.user) : null;

	/**
	 * If modules have been pre-loaded in the application data then set
	 */
	if (initData.modules)
		netric.module.loader.preloadFromData(initData.modules);
}
/**
 * @fileoverview DataMapper for loading accounts from the local store
 */
alib.declare("netric.account.DataMapperLocal");

alib.require("netric");

/**
 * Make sure account namespace is initialized
 */
netric.account = netric.account || {};

/**
 * Local DataMapper constructor
 *
 * @constructor
 */
netric.account.DataMapperLocal = function() {
	
}

/**
 * Get account from the data store
 *
 * @return {netric.account.Account|bool} Account on sucess, false if not found
 */
netric.account.DataMapperLocal.prototype.load = function() {

}

/**
 * Save account to the data store
 *
 * @param {netric.account.Account} acct The account to save data for
 * @reutrn {bool} True on success, false on failure
 */
netric.account.DataMapperLocal.prototype.save = function(acct) {
	//  TODO: save the actt
}
/**
 * @fileoverview DataMapper for loading accounts from the server
 */
alib.declare("netric.account.DataMapperServer");
alib.require("netric");
/**
 * Make sure account namespace is initialized
 */
netric.account = netric.account || {};

/**
 * Server DataMapper constructor
 *
 * @constructor
 */
netric.account.DataMapperServer = function() {
	
}

/**
 * Get account from the data store
 *
 * @return {netric.account.Account|bool} Account on sucess, false if not found
 */
netric.account.DataMapperServer.prototype.load = function(cbLoadedFunction) {

}

/**
 * Save account to the data store
 *
 * @param {netric.account.Account} acct The account to save data for
 * @reutrn {bool} True on success, false on failure
 */
netric.account.DataMapperServer.prototype.save = function(cbSavedFunction) {
	//  TODO: save the actt
}
/**
* @fileOverview Account loader
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.account.loader");

alib.require("netric");

alib.require("netric.account.Account");
alib.require("netric.BackendRequest");

/**
 * Make sure account namespace is initialized
 */
netric.account = netric.account || {};

/**
 * Global module loader
 *
 * @param {netric.Application} application Application instance
 */
netric.account.loader = netric.account.loader || {};

/**
 * Keep a reference to last loaded application to reduce requests
 *
 * @private
 * @param {Array}
 */
netric.account.loader.accountCache_ = null;

/**
 * Static function used to load the module
 *
 * If no callback is set then this function will try to return the account
 * from cache. If it has not yet been loaded then it will force a non-async
 * request which will HANG THE UI so it should only be used as a last resort.
 *
 * @param {function} cbLoaded Callback function once account is loaded
 * @return {netric.account.Account|void} If no callback is provded then force a return
 */
netric.account.loader.get = function(cbLoaded) {
	
	// Return (or callback callback) cached account if already loaded
	if (this.accountCache_ != null) {
		
		if (cbLoaded) {
			cbLoaded(this.accountCache_);
		}

		return this.accountCache_;
	}

	var request = new netric.BackendRequest();

	if (cbLoaded) {
		alib.events.listen(request, "load", function(evt) {
			var account = netric.account.loader.createAccountFromData(this.getResponse());
			cbLoaded(account);
		});
	} else {
		// Set request to be synchronous if no callback is set	
		request.setAsync(false);
	}

	request.send("/svr/account/get");

	// If no callback then construct netric.account.Account from request date (synchronous)
	if (!cbLoaded) {
		return this.createAccountFromData(request.getResponse());
	}
}

/**
 * Map data to an account object
 *
 * @param {Object} data The data to create an account from
 */
netric.account.loader.createAccountFromData = function(data) {

	// Construct account and initialize with data	
	var account = new netric.account.Account(data);
	
	// Cache it for future requests
	this.accountCache_ = account;

	return this.accountCache_;
}
/**
 * @fileoverview This is the bast controller that all other controllers should extend
 *
 * All instances of this class should call in their constructor:
 * 	netric.controller.AbstractController.call(this, args...);
 *
 * The lifecycle of a controller is:
 * ::load -> onLoad is the first function called when the controllers is first loaded
 * ::render is called after the controller is loaded and any time params change
 * ::unload -> onUnload when the controller is removed from the document - cleanup!
 * ::pause - onPause Is called if the controller gets moved to the background
 * ::resume - onResume Is called if the controller was paused in the background but gets moved to the foreground again
 *
 * And immediately after the constructor definition call:
 * netric.inherits(netric.controller.<thiscontrollername>, netric.controller.AbstractController);
 */
netric.declare("netric.controller.AbstractController");

netric.require("netric.controller");
netric.require("netric.log");

/**
 * Abstract controller
 *
 * @constructor
 */
netric.controller.AbstractController = function() {

	/*
	 * We try not to include too much in the base constructor because there is
	 * no way we can assure that all inherited classes call netric.controller.AbstractController.call
	 */

	// Call base class constructor
	//netric.controller.AbstractController.call(this, domCon);

}

/** 
 * Define properties forwarded to this controller
 * 
 * @protected
 * @type {Object}
 */
netric.controller.AbstractController.prototype.props = {};

/**
 * DOM node to render everything into
 *
 * @private
 * @type {RactElement|DOMElement}
 */
netric.controller.AbstractController.prototype.domNode_ = null;

/**
 * The type of controller this is.
 * 
 * @see comments on netric.location.controller.types property
 */
netric.controller.AbstractController.prototype.type_ = null;

/**
 * Handle to the parent router of this controller
 * 
 * @type {netric.location.Router}
 */
netric.controller.AbstractController.prototype.router_ = null;

/** 
 * Flag to indicate if it is paused
 *
 * @private 
 * @type {bool}
 */
netric.controller.AbstractController.prototype.isPaused_ = false;

/**
 * All child classes should extend this base class with:
 */
//netric.inherits(netric.controller.ModuleController, netric.controller.AbstractController);


/**
 * Handle loading and setting up this controller but not yet rendering it
 *
 * @param {Object} data Optional data to pass to the controller including data.params for URL params
 * @param {ReactElement|DomElement} opt_domNode Optional parent node to render controller into
 * @param {netric.location.Router} opt_router The parent router of this controller
 * @param {function} opt_callback If set call this function when we are finished loading
 */
netric.controller.AbstractController.prototype.load = function(data, opt_domNode, opt_router, opt_callback) {

	this.domNode_ = null;

	// Local variables passed to this controller
	this.props = data;

	// Setup the type
	this.type_ = data.type || netric.controller.types.PAGE;

	// Set reference to the parent router
	this.router_ = opt_router || null;

	// Parent DOM node to render into
	var parentDomNode = opt_domNode || null;

	// onLoad may be over-ridden by child classes for additional processing
	this.onLoad(function(){
		
		// Set the root dom node for this controller
		this.setupDomNode_(parentDomNode)

		// Pause parent controller (if a page)
		if (this.getParentController() && this.type_ == netric.controller.types.PAGE) {
			this.getParentController().pause();
		}

		// Render the controller
		this.render();

		if (opt_callback) {
			opt_callback();
		}

	}.bind(this));
}

/**
 * Unload the controller
 *
 * This is where we will cleanup
 */
netric.controller.AbstractController.prototype.unload = function() {
	// The onUnload callback for child classes needs to be called first
	this.onUnload();

	// Remove the elements from the page
	if (this.domNode_) {
		if (this.domNode_.parentElement) {
			this.domNode_.parentElement.removeChild(this.domNode_);
		} else {
			this.domNode_.innerHTML = "";
		}
	}

	// Resume the parent controller if it has been paused
	if (this.getParentController()) {
		if (this.getParentController().isPaused()) {
			this.getParentController().resume();
		}
	}
}

/**
 * Resume this controller and move it back to the foreground
 */
netric.controller.AbstractController.prototype.resume = function() {
	// If this controller is of type PAGE then hide the parent (if exists)
	if (this.type_ == netric.controller.types.PAGE && this.isPaused()) {

		// Hide me
		if (this.domNode_) {
			alib.dom.styleSet(this.domNode_, "display", "block");
		}

		// Set paused flag for resuming later
		this.isPaused_ = false;

		this.onResume();
	}
}

/**
 * Pause this controller and move it into the background
 */
netric.controller.AbstractController.prototype.pause = function() {
	// If this controller is of type PAGE then hide the parent (if exists)
	if (this.type_ == netric.controller.types.PAGE) {

		// Hide me
		if (this.domNode_) {
			alib.dom.styleSet(this.domNode_, "display", "none");
		}

		// Set paused flag for resuming later
		this.isPaused_ = true;

		// Get my parent and pause it
		var parentRouter = this.getParentRouter();
		
		// Get the parent controller of this controller
		var parentController = this.getParentController();
		if (parentController) {
			// Pause/hide parent controller before we render this controller
			parentController.pause();
		}

		this.onPause();
	}
}

/**
 * Add a subroute to the nexthop router if it exists
 *
 * @param {string} path The path pattern
 * @param {netric.controller.AbstractController} controllerClass The ctrl class to load
 * @param {Object} data Any data to pass to the controller
 * @param {DOMElement} domNode The node to load this controller into
 * @return {bool} true if route added, false if it failed
 */
netric.controller.AbstractController.prototype.addSubRoute = function(path, controllerClass, data, domNode) {
	if (this.getChildRouter()) {
		this.getChildRouter().addRoute(path, controllerClass, data, domNode);
		return true;
	} else {
		// TODO: use dialog?
		return false;
	}
}

/**
 * Get my parent controller
 *
 * @return {netric.location.Router} Router than owns the route tha rendered this controller
 */
netric.controller.AbstractController.prototype.getParentController = function() {

	// Get the parent router of this controller
	var parentRouter = this.getParentRouter();
	
	if (parentRouter) {
		// Get the parent router to my parent
		var grandparentRouter = parentRouter.getParentRouter();
		// Find out if my parent router is the child of another router
		if (grandparentRouter) {
			// This should always return a route, but never assume anything!
			var activeRoute = grandparentRouter.getActiveRoute();
			if (activeRoute) {
				return activeRoute.getController();
			} else {
				throw "Problem! Could not find an active route from withing a controller.";
			}	
		}
	}

	return null;
}

/**
 * Set the root dom node to render this controller into
 *
 * @param {DOMElement} opt_domNode Optional DOM node. Usually only used for fragments but also for custom root node.
 */
netric.controller.AbstractController.prototype.setupDomNode_ = function(opt_domNode) {

	var parentNode = null;

    switch (this.type_) {
    	/*
		 * If this is of type page then we need to walk up the tree of
		 * controllers to get the top page controller's dom parent because
		 * pages will hide their parents so a child page cannot be a child dom
		 * element.
		 */
    	case netric.controller.types.PAGE:
    		
    		/* 
    		 * We can set a default root node to use if no parent nodes exists.
    		 * If no default is defined and there are no parent pages the new controller
    		 * pages will be rendered into document.body.
    		 */
    		var defaultRootNode = opt_domNode || null;
    		parentNode = this.getTopPageNode(defaultRootNode);
    		break;

    	/*
    	 * A fragment is a controller that loads in a child DOM of another controler.
    	 * It is unique in that it cannot hide its parent so the contianing controller
    	 * will always be visible.
    	 */
    	case netric.controller.types.FRAGMENT:
    		if (opt_domNode) {
    			parentNode = opt_domNode;	
    		} else {
    			throw "Cannot render a fragment controller without passing a valid DOM element";
    		}
    		break;

    	/*
		 * If this is a dialog then render a new dialog into the dom and get the inner container to render controller
		 */
    	case netric.controller.types.DIALOG:
    		// TODO: create dialog
    		break;
    }
	

	this.domNode_ = alib.dom.createElement("div", parentNode, null, {id:this.getParentRouter().getActiveRoute().getPath()});
}

/**
 * Get the topmost page node for rendering child pages
 * 
 * This is important because child pages can hide their parent
 * so child controllers of type PAGE cannot be in a child in the DOM tree
 * or they will disappear along with the parent when we pause/hide the parent.
 *
 * @public
 * @param {DOMElement} opt_rootDomNode An optional default root in case none is found (like this is a root conroller)
 * @return {DOMElement} The parent of the topmost page in this tree (will stop at a fragment or top)
 */
netric.controller.AbstractController.prototype.getTopPageNode = function(opt_rootDomNode) {
	
	if (this.getParentController()) {
		if (this.getParentController().getType() == netric.controller.types.PAGE) {
			return this.getParentController().getTopPageNode();
		}
	}

	// No parent pages were found so simply return my parent node
	if (this.domNode_) {
		if (this.domNode_.parentNode) {
			return this.domNode_.parentNode;
		}
	}

	// This must be a new root page controller because we cound't find any parent DOM elements
	if (opt_rootDomNode) {
		return opt_rootDomNode;
	} else {
		return document.body;
	}

}

/**
 * Get the router that owns this controller
 *
 * @return {netric.location.Router} Router than owns the route tha rendered this controller
 */
netric.controller.AbstractController.prototype.getParentRouter = function() {

	if (this.router_) {
		return this.router_.getParentRouter();
	}

	return null;
}

/**
 * Get the router assoicated with next-hops
 *
 * @return {netric.location.Router} Handle to the router for child routes
 */
netric.controller.AbstractController.prototype.getChildRouter = function() {
	return this.router_;
}

/**
 * Get the current route path to this controller
 *
 * If this is a dialog or an inline contoller with no route then
 * it will simply return null.
 *
 * @return {stirng} Absolute path of the current controller.
 */
netric.controller.AbstractController.prototype.getRoutePath = function() {
	if (this.getParentRouter()) {
		return this.getParentRouter().getActiveRoute().getPath();
	}

	// This controller does not appear to be part of a route which
	// means it is either a dialog or inline.
	return null;
}

/**
 * Get the type of controller
 *
 * @return {netric.controller.types}
 */
netric.controller.AbstractController.prototype.getType = function() {
	return this.type_;
}


/**
 * Detect if this controller was previously paused
 *
 * @return {bool} true if the controller was paused, false if not
 */
netric.controller.AbstractController.prototype.isPaused = function() {
	return this.isPaused_;
}

/**
 * Render is called to enter the controller into the Dom
 * 
 * @abstract
 * @param {ReactElement|DomElement} ele The element to render into
 * @param {Object} data Optiona forwarded data
 */
netric.controller.AbstractController.prototype.render = function() {}

/**
 * Function called when controller is first loaded but before the dom ready to render
 *
 * This can be over-ridden by child classes to extend what gets done while loading the controller.
 * One common use is to setup runtiem sub-routes based on some asyncrhonously loaded data.
 *
 * @param {function} opt_callback If set call this function when we are finished loading
 */
netric.controller.AbstractController.prototype.onLoad = function(opt_callback) {
	// By default just immediately execute the callback because nothing needs to be done
	if (opt_callback)
		opt_callback();
}

/**
 * Called when the controller is unloaded from the page
 */
netric.controller.AbstractController.prototype.onUnload = function() {}

/**
 * Called when this controller is paused and moved to the background
 */
netric.controller.AbstractController.prototype.onPause = function() {}

/**
 * Called when this function was paused but it has been resumed to the forground
 */
netric.controller.AbstractController.prototype.onResume = function() {}

/**
 * @fileoverview Entity browser
 */
netric.declare("netric.controller.EntityBrowserController");

netric.require("netric.controller.AbstractController");

/**
 * Controller that loads an entity browser
 */
netric.controller.EntityBrowserController = function() {
}

/**
 * Extend base controller class
 */
netric.inherits(netric.controller.EntityBrowserController, netric.controller.AbstractController);

/**
 * Handle to root ReactElement where the UI is rendered
 *
 * @private
 * @type {ReactElement}
 */
netric.controller.EntityBrowserController.prototype.rootReactNode_ = null;

/**
 * Function called when controller is first loaded but before the dom ready to render
 *
 * @param {function} opt_callback If set call this function when we are finished loading
 */
netric.controller.EntityBrowserController.prototype.onLoad = function(opt_callback) {

	// By default just immediately execute the callback because nothing needs to be done
	if (opt_callback)
		opt_callback();
}

/**
 * Render this controller into the dom tree
 */
netric.controller.EntityBrowserController.prototype.render = function() { 
	// Set outer application container
	var domCon = this.domNode_;

	var data = {
		name: "Loading...",
		onNavBtnClick: this.props.onNavBtnClick || null
	}

	// Render application component
	this.rootReactNode_ = React.render(
		React.createElement(netric.ui.EntityBrowser, data),
		domCon
	);

	/*
	// Add route to compose a new entity
	this.addSubRoute("compose", 
		netric.controller.TestController, 
		{ type: netric.controller.types.FRAGMENT }, 
		this.rootReactNode_.refs.moduleMain.getDOMNode()
	);

	// Add route to compose a new entity
	this.addSubRoute("browse", 
		netric.controller.TestController, 
		{ type: netric.controller.types.FRAGMENT }, 
		this.rootReactNode_.refs.moduleMain.getDOMNode()
	);
	*/
}

/**
 * User selected an alternate menu item in the left navigation
 */
netric.controller.EntityBrowserController.prototype.onLeftNavChange_ = function(evt, index, payload) {
	if (payload && payload.route) {
		var basePath = this.getRoutePath();
		netric.location.go(basePath + "/" + payload.route);
	}
}

/**
 * @fileoverview This is the main controller used for the base application
 */
netric.declare("netric.controller.MainController");

netric.require("netric.controller");

/**
 * Main application controller
 */
netric.controller.MainController = function() {

	this.application = netric.getApplication();

}

/**
 * Extend base controller class
 */
netric.inherits(netric.controller.MainController, netric.controller.AbstractController);

/**
 * Function called when controller is first loaded but before the dom ready to render
 *
 * @param {function} opt_callback If set call this function when we are finished loading
 */
netric.controller.MainController.prototype.onLoad = function(opt_callback) {

	// By default just immediately execute the callback because nothing needs to be done
	if (opt_callback)
		opt_callback();
}

/**
 * Render this controller into the dom tree
 */
netric.controller.MainController.prototype.render = function() { 
	// Set outer application container
	var domCon = this.domNode_;

	// Get a view component for rendering
	switch (netric.getApplication().device.size)
	{
	case netric.Device.sizes.small:
		this.appComponent_ = netric.ui.application.Small;
		break;
	case netric.Device.sizes.medium:
		this.appComponent_ = netric.ui.application.Large;
		break;
	case netric.Device.sizes.large:
		this.appComponent_ = netric.ui.application.Large;
		break;
	}

	// Setup application data
	var data = {
		orgName : netric.getApplication().getAccount().orgName,
		module : "messages",
		logoSrc : "img/netric-logo-32.png",
		basePath : this.getParentRouter().getActivePath()
	}

	// Render application component
	var view = React.render(
		React.createElement(this.appComponent_, data),
		domCon
	);

	// Add dynamic route to the module
	this.addSubRoute(":module", 
		netric.controller.ModuleController, 
		{}, 
		view.refs.appMain.getDOMNode()
	);

	// Set a default route to messages
	this.getChildRouter().setDefaultRoute("messages");
}

/**
 * @fileoverview Main application controller
 */
netric.declare("netric.controller.ModuleController");

netric.require("netric.controller.AbstractController");

/**
 * Controller that loads modules into the applicatino
 */
netric.controller.ModuleController = function() {
}

/**
 * Extend base controller class
 */
netric.inherits(netric.controller.ModuleController, netric.controller.AbstractController);

/**
 * Handle to root ReactElement where the UI is rendered
 *
 * @private
 * @type {ReactElement}
 */
netric.controller.ModuleController.prototype.rootReactNode_ = null;

/**
 * Function called when controller is first loaded but before the dom ready to render
 *
 * @param {function} opt_callback If set call this function when we are finished loading
 */
netric.controller.ModuleController.prototype.onLoad = function(opt_callback) {

	// Change the type based on the device size
	switch (netric.getApplication().device.size)
	{
	case netric.Device.sizes.small:
	case netric.Device.sizes.medium:
		this.type_ = netric.controller.types.PAGE;
		break;
	case netric.Device.sizes.large:
		this.type_ = netric.controller.types.FRAGMENT;
		break;
	}

	// By default just immediately execute the callback because nothing needs to be done
	if (opt_callback)
		opt_callback();
}

/**
 * Render this controller into the dom tree
 */
netric.controller.ModuleController.prototype.render = function() { 
	// Set outer application container
	var domCon = this.domNode_;

	var data = {
		name: "Loading...",
		leftNavDocked: (netric.getApplication().device.size == netric.Device.sizes.small) ? false : true,
		leftNavItems: [
			{name: "Create New Entity", "route": "compose"},
			{name: "Browse Entity", "route": "browse"},
			{name: "Third Menu Entry"}
		],
		onLeftNavChange: this.onLeftNavChange_.bind(this)
	}

	// Render application component
	this.rootReactNode_ = React.render(
		React.createElement(netric.ui.Module, data),
		domCon
	);

	// Add route to compose a new entity
	this.addSubRoute("compose", 
		netric.controller.TestController, 
		{ type: netric.controller.types.FRAGMENT }, 
		this.rootReactNode_.refs.moduleMain.getDOMNode()
	);

	// Add route to compose a new entity
	this.addSubRoute("browse", 
		netric.controller.EntityBrowserController, 
		{ 
			type: netric.controller.types.FRAGMENT,
			onNavBtnClick: function(e) { this.rootReactNode_.refs.leftNav.toggle(); }.bind(this) 
		}, 
		this.rootReactNode_.refs.moduleMain.getDOMNode()
	);

	/* 
	 * Add listener to update leftnav state when a child route changes
	 */
	if (this.getChildRouter() && this.rootReactNode_.refs.leftNav) {
		alib.events.listen(this.getChildRouter(), "routechange", function(evt) {
			this.rootReactNode_.refs.leftNav.setState({ selected: evt.data.path });
		}.bind(this));
	}

	// Set a default route to messages
	this.getChildRouter().setDefaultRoute("browse");
}

/**
 * User selected an alternate menu item in the left navigation
 */
netric.controller.ModuleController.prototype.onLeftNavChange_ = function(evt, index, payload) {
	if (payload && payload.route) {
		var basePath = this.getRoutePath();
		netric.location.go(basePath + "/" + payload.route);
	}
}

/**
 * @fileoverview This is a test controller used primarily for unit tests
 */
netric.declare("netric.controller.TestController");

netric.require("netric.controller");
netric.require("netric.controller.AbstractController");

/**
 * Test controller
 */
netric.controller.TestController = function() {}

/**
 * Extend base controller class
 */
netric.inherits(netric.controller.TestController, netric.controller.AbstractController);


/**
 * Function called when controller is first loaded
 */
netric.controller.TestController.prototype.onload = function() { }

/**
 * Render the contoller into the dom
 */
netric.controller.TestController.prototype.onload = function() { }
/**
 * @fileOverview Handle defintion of entities.
 *
 * This class is a client side mirror of /lib/EntityDefinition
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.entity.Definition");

alib.require("netric.entity.definition.Field");

alib.require("netric");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Creates an instance of EntityDefinition
 *
 * @constructor
 * @param {Object} opt_data The definition data
 */
netric.entity.Definition = function(opt_data) {

	var data = opt_data || new Object();

	/**
	 * The object type for this definition
	 *
	 * @public
	 * @type {string}
	 */
	this.objType = data.obj_type || "";;

	/**
	 * The object type title
	 *
	 * @public
	 * @type {string}
	 */
	this.title = data.title || "";

	/**
	 * Recurrence rules
	 *
	 * @public
	 * @type {string}
	 */
	this.recurRules = data.recur_rules || null;

	/**
	 * Unique id of this object type
	 *
	 * @public
	 * @type {string}
	 */
	this.id = data.id || "";

	/**
	 * The current schema revision
	 *
	 * @public
	 * @type {int}
	 */
	this.revision = data.revision || "";

	/**
	 * Determine if this object type is private
	 *
	 * @public
	 * @type {bool}
	 */
	this.isPrivate = data.is_private || false;

	/**
	 * If object is heirarchial then this is the field that will store a reference to the parent
	 *
	 * @public
	 * @type {string}
	 */
	this.parentField = data.parent_field || "";

	/**
	 * Default field used for printing the name/title of objects of this type
	 *
	 * @public
	 * @type {string}
	 */
	this.listTitle = data.list_title || "";

	/**
	 * The base icon name used for this object.
	 *
	 * This may be over-ridden by individual objects for more dynamic icons, but this serves
	 * as the base in case the individual object did not yet define an icon.
	 *
	 * @public
	 * @type {string}
	 */
	this.icon = data.icon || "";

	/**
	 * Browser mode for the current user
	 *
	 * @public
	 * @type {string}
	 */
	this.browserMode = data.browser_mode || "";

	/**
	 * Is this a system level object
	 *
	 * @public
	 * @type {bool}
	 */
	this.system = data.system || "";;

	/**
	 * Fields associated with this object type
	 *
	 * For definition see EntityDefinition_Field::toArray on backend
	 *
	 * @private
	 * @type {netric.entity.definition.Field[]}
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
	this.browserBlankContent = data.browser_blank_content || "";;

	/*
	 * Initialize fields if set in the data object
	 */
	if (data.fields) {
		for (var fname in data.fields) {
			var field = new netric.entity.definition.Field(data.fields[fname]);
			this.fields.push(field);
		}
	}

	/*
	 * Initialize views for this object definition
	 */
	if (data.views) {
		for (var i in data.views) {
			var view = new AntObjectBrowserView();
			view.fromData(data.views[i]);
			this.views.push(view);
		}
	}

}

/**
 * Get a field by name
 *
 * @public
 * @param {Object} data Initialize values of this defintion based on data
 */
netric.entity.Definition.prototype.getField = function(fname) {
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
 * @return {netric.entity.Definition.Field[]}
 */
netric.entity.Definition.prototype.getFields = function() {
	return this.fields;
}

/**
 * Get views
 *
 * @public
 * @return {AntObjectBrowserView[]}
 */
netric.entity.Definition.prototype.getViews = function() {
	return this.views;
}

/**
 * Get browser blank state content
 *
 * @public
 * @return {string}
 */
netric.entity.Definition.prototype.getBrowserBlankContent = function() {
	return this.browserBlankContent;
}

/**
 * @fileOverview Base entity may be extended
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.entity.Entity");

alib.require("netric");
alib.require("netric.entity.Definition");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Entity represents a netric object
 *
 * @constructor
 * @param {netric.entity.Definition} entityDef Required definition of this entity
 * @param {Object} opt_data Optional data to load into this object
 */
netric.entity.Entity = function(entityDef, opt_data) {

	/** 
	 * Unique id of this object entity
	 *
	 * @public
	 * @type {string}
	 */
	this.id = "";

	/** 
	 * The object type of this entity
	 *
	 * @public
	 * @type {string}
	 */
	this.objType = "";

	/**
	 * Entity definition
	 *
	 * @public
	 * @type {netric.entity.Definition}
	 */
	this.def = entityDef;

	/**
	 * Flag to indicate fieldValues_ have changed for this entity
	 *
	 * @private
	 * @type {bool}
	 */
	this.dirty_ = false;

	/**
	 * Field values
	 * 
	 * @private
	 * @type {Object}
	 */
	this.fieldValues_ = new Object();

	/**
	 * Security
	 * 
	 * @public
	 * @type {Object}
	 */
	this.security = {
		view : true,
		edit : true,
		del : true,
		childObject : new Array()
	};

	// If data has been passed then load it into this entity
	if (opt_data) {
		this.loadData(opt_data);
	}
}

/**
 * Load data from a data object in array form
 * 
 * If we are loading in array form that means that properties are not camel case
 * 
 * @param {Object} data
 */
netric.entity.Entity.prototype.loadData = function (data) {
	
	// Data is a required param and we should fail if called without it
	if (!data) {
		throw "'data' is a required param to loadData into an entity";
	}

	// Make sure that the data passed is valid data
	if (!data.id || !data.obj_type) {
		var err = "Data passed is not a valid entity";
		console.log(err + JSON.strigify(data));
		throw err;
	}

	// First set common public properties
	this.id = data.id.toString();
	this.objType = data.obj_type;

	// Now set all the values for this entity
}

/**
 * Set the value of a field of this entity
 *
 * @param {string} name The name of the field to set
 * @param {mixed} value The value to set the field to
 * @param {string} opt_valueName The label if setting an fkey/object value
 */
netric.entity.Entity.prototype.setValue = function(name, value, opt_valueName) {
    
    // Can't set a field without a name
    if(typeof name == "undefined")
        return;

	var valueName = opt_valueName || null;

    var field = this.def.getField(name);
	if (!field)
		return;

	// Check if this is a multi-field
	if (field.type == field.types.fkeyMulti || field.type == field.types.objectMulti) {
		if (value instanceof Array) {
			for (var j in value) {
				this.setMultiValue(name, value[j]);
			}
		} else {
			this.setMultiValue(name, value, valueName);
		}

		return true;
	}

	// Handle bool conversion
	if (field.type == field.types.bool) {
		switch (value)
		{
		case 1:
		case 't':
		case 'true':
			value = true;
			break;
		case 0:
		case 'f':
		case 'false':
			value = false;
			break;
		}
	}
    
    // Referenced object fields cannot be updated
    if (name.indexOf(".")!=-1) {
        return;
    }

    // A value of this entity is about to change
    this.dirty_ = true;

    // Set the value and optional valueName label for foreign keys
    this.fieldValues_[name] = {
    	value: value,
    	valueName: (valueName) ? valueName : null
    }

    // Trigger onchange event to alert any observers that this value has changed
	alib.events.triggerEvent(this, "fieldchange", {fieldName: name, value:value, valueName:valueName});
    
}

/**
 * Get the value for an object entity field
 * 
 * @public
 * @param {string} name The unique name of the field to get the value for
 */
netric.entity.Entity.prototype.getValue = function(name) {
    if (!name)
        return null;

    // Get value from fieldValue
    if (this.fieldValues_[name]) {
    	return this.fieldValues_[name].value;
    }  
    
    return null;
}

/**
 * Get the name/lable of a key value
 * 
 * @param {string} name The name of the field
 * @param {val} opt_val If querying *_multi type values the get the label for a specifc key
 * @reutrn {string} the textual representation of the key value
 */
netric.entity.Entity.prototype.getValueName = function(name, opt_val) {
	// Get value from fieldValue
    if (this.fieldValues_[name]) {
    	if (opt_val && this.fieldValues_[name].valueName instanceof Array) {
    		for (var i in this.fieldValues_[name].valueName) {
    			if (this.fieldValues_[name].valueName[i].key == name) {
    				return this.fieldValues_[name].valueName[i].value;
    			}
    		}
    	} else {
    		return this.fieldValues_[name].valueName;    		
    	}
    }
	/*
    var field = this.getFieldByName(name);
    if (field && field.type == "alias")
    {
        if (!val)
            var val = this.getValue(name);
        return this.getValue(val); // Get aliased value
    }

    if (field.type == "object" || field.type == "fkey" || field.type == "object_multi" || field.type == "fkey_multi")
    {
        for (var i = 0; i < this.values.length; i++)
        {
            if (this.values[i][0] == name)
            {
                if (val) // multival
                {
                    for (var m = 0; m < this.values[i][1].length; m++)
                    {
                        if (this.values[i][1][m] == val && this.values[i][2])
                            return this.values[i][2][m];
                    }
                }
                else
                {
                    if (this.values[i][2]!=null && this.values[i][2]!="null")
                        return this.values[i][2];
                }
            }
        }
    }
	else if (field.optional_vals.length)
	{
		for (var i = 0 ; i < field.optional_vals.length; i++)
		{
			if (field.optional_vals[i][0] == this.getValue(name))
			{
				return field.optional_vals[i][1];
			}
		}
	}
    else
    {
        return this.getValue(name);
    }
    */
    
    return "";
}

/**
 * Get the human readable name of this object
 *
 * @return {string} The name of this object based on common name fields like 'name' 'title 'subject'
 */
netric.entity.Entity.prototype.getName = function()
{
    if (this.getValue("name")) {
        return this.getValue("name");
    } else if (this.getValue("title")) {
        return this.getValue("title");
    } else if (this.getValue("subject")) {
        return this.getValue("subject");
    } else if (this.getValue("first_name") || this.getValue("last_name")) {
    	return (this.getValue("first_name")) 
    		? this.getValue("first_name") + " " + this.getValue("last_name")
    		: this.getValue("last_name");
    } else if (this.getValue("id")) {
        return this.getValue("id");
    } else {
        return "";
    }
}
/**
 * @fileOverview Entity query
 *
 * Example:
 * <code>
 * 	var query = new netric.entity.Query("customer");
 * 	query.where('first_name').equals("sky");
 *  query.andWhere('last_name').contains("steb");
 *	query.orderBy("last_name", netric.entity.Query.orderByDir.desc);
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.entity.Query");

alib.require("netric");
alib.require("netric.entity.Definition");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Entity represents a netric object
 *
 * @constructor
 * @param {string} objType Name of the object type we are querying
 */
netric.entity.Query = function(objType) {
	/**
	 * Object type for this list
	 *
	 * @type {string}
	 * @private
	 */
	this.objType_ = obj_type;

	/**
	 * Array of condition objects {blogic, fieldName, operator, condValue}
	 *
	 * @type {array}
	 * @private
	 */
	this.conditions_ = new Array();


	/**
	 * Array of sort order objects
	 *
	 * @type {array}
	 * @private
	 */
	this.orderBy_ = new Array();

	/**
	 * The current offset of the total number of items
	 *
	 * @type {number}
	 * @private
	 */
	this.offset_ = 0;

	/**
	 * Number of items to pull each query
	 *
	 * @type {number}
	 * @private
	 */
	this.limit_ = 100;

	/**
	 * Total number of objects in this query set
	 *
	 * @type {number}
	 * @private
	 */
	this.totalNum = 0;

	/**
	 * Copy static order by direction to this so we can access through this.orderByDir
	 *
	 * @public
	 * @type {netric.entity.Query.orderByDir}
	 */
	this.orderByDir = netric.entity.Query.orderByDir;
}

/**
 * Static order by direction
 * 
 * @const
 */
netric.entity.Query.orderByDir = {
	asc : "ASC",
	desc : "DESC"
}

/**
 * Proxy used to add the first where condition to this query
 *
 * @param {string} fieldName The name of the field to query
 * @return {netric.entity.query.Where}
 */
netric.entity.Query.prototype.where = function(fieldName) {
	return this.andWhere(fieldName);
}

/**
 * Add a where condition using the logical 'and' operator
 * 
 * @param {string} fieldName The name of the field to query
 * @return {netric.entity.query.Where}
 */
netric.entity.Query.prototype.andWhere = function(fieldName) {
	// TODO: return netri.entity.query.Where
}

/**
 * Add a where condition using the logical 'and' operator
 * 
 * @param {string} fieldName The name of the field to query
 * @return {netric.entity.query.Where}
 */
netric.entity.Query.prototype.orWhere = function(fieldName) {
	// TODO: return netri.entity.query.Where
}

/**
 * Add an order by condition
 * 
 * @param {string} fieldName The name of the field to sort by
 * @param {netric.entity.Query.orderByDir} The direction of the sort
 */
netric.entity.Query.prototype.orderBy = function(fieldName, direction) {
	// TODO: add order by condition
}

/** 
 * Get the conditions for this entity query
 * 
 * @return {Array}
 */
netric.entity.Query.prototype.getConditions = function() {
	return this.conditions_;
}

/** 
 * Get the order for this entity query
 * 
 * @return {Array}
 */
netric.entity.Query.prototype.getOrderBy = function() {
	return this.orderBy_;
}

/**
 * Set the offset for this query
 * 
 * @param {int} offset
 */
netric.entity.Query.prototype.setOffset = function(offset) {
	this.offset_ = offset;
}
/**
 * Get the current offset
 * 
 * @return {int}
 */
netric.entity.Query.prototype.getOffset = function() {
	return this.offset_;
}

/**
 * Set the limit for this query
 * 
 * @param {int} limit
 */
netric.entity.Query.prototype.setLimit = function(limit) {
	this.limit_ = limit;
}
/**
 * Get the current limit
 * 
 * @return {int}
 */
netric.entity.Query.prototype.getLimit = function() {
	return this.limit_;
}
/**
* @fileOverview Definition loader
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.entity.definitionLoader");

alib.require("netric");

alib.require("netric.entity.Definition");
alib.require("netric.BackendRequest");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Entity definition loader
 *
 * @param {netric.Application} application Application instance
 */
netric.entity.definitionLoader = netric.entity.definitionLoader || {};

/**
 * Keep a reference to loaded definitions to reduce requests
 *
 * @private
 * @param {Array}
 */
netric.entity.definitionLoader.definitions_ = new Array();

/**
 * Static function used to load an entity definition
 *
 * If no callback is set then this function will try to return the definition
 * from cache. If it has not yet been loaded then it will force a non-async
 * request which will HANG THE UI so it should only be used as a last resort.
 *
 * @param {string} objType The object type we are loading a definition for
 * @param {function} cbLoaded Callback function once definition is loaded
 * @return {netric.entity.Definition|void} If no callback is provded then force a return
 */
netric.entity.definitionLoader.get = function(objType, cbLoaded) {
	
	// Return (or callback callback) cached definition if already loaded
	if (this.definitions_[objType] != null) {
		
		if (cbLoaded) {
			cbLoaded(this.definitions_[objType]);
		}

		return this.definitions_[objType];
	}

	var request = new netric.BackendRequest();

	if (cbLoaded) {
		alib.events.listen(request, "load", function(evt) {
			var def = netric.entity.definitionLoader.createFromData(this.getResponse());
			cbLoaded(def);
		});
	} else {
		// Set request to be synchronous if no callback is set	
		request.setAsync(false);
	}

	request.send("svr/entity/getDefinition", "GET", {obj_type:objType});

	// If no callback then construct netric.entity.Definition from request date (synchronous)
	if (!cbLoaded) {
		return this.createFromData(request.getResponse());
	}
}

/**
 * Map data to an entity definition object
 *
 * @param {Object} data The data to create the definition from
 */
netric.entity.definitionLoader.createFromData = function(data) {

	// Construct definition and initialize with data	
	var def = new netric.entity.Definition(data);
	
	// Cache it for future requests
	this.definitions_[def.objType] = def;

	return this.definitions_[def.objType];
}

/**
 * Get a pre-loaded / cached object definition
 *
 * @param {string} objType The uniqy name of the object entity type
 * @return {netric.entity.Definition} Entity defintion on success, null if not cached
 */
netric.entity.definitionLoader.getCached = function(objType) {
	if (this.definitions_[objType]) {
		return this.definitions_[objType];
	}

	return null;
}
/**
* @fileOverview Entity loader / identity mapper
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.entity.loader");

alib.require("netric");
alib.require("netric.entity.definitionLoader");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Global entity loader namespace
 */
netric.entity.loader = netric.entity.loader || {};

/**
 * Array of already loaded entities
 *
 * @private
 * @var {Array}
 */
netric.entity.loader.entities_ = new Object();

/**
 * Static function used to load the entity
 *
 * @param {string} objType The object type to load
 * @param {string} entId The unique entity to load
 * @param {function} cbLoaded Callback function once entity is loaded
 * @param {bool} force If true then force the entity to reload even if cached
 */
netric.entity.loader.get = function(objType, entId, cbLoaded, force) {
	// Return (or callback callback) cached entity if already loaded
	var ent = this.getCached(objType, entId);
	if (ent && !force) {

		if (cbLoaded) {
			cbLoaded(ent);
		}

		return ent;
	}

	/*
	 * Load the entity data
	 */
	var request = new netric.BackendRequest();

	if (cbLoaded) {
		alib.events.listen(request, "load", function(evt) {
			var entity = netric.entity.loader.createFromData(this.getResponse());
			cbLoaded(entity);
		});
	} else {
		// Set request to be synchronous if no callback is set	
		request.setAsync(false);
	}

	// Create request data
	var requestData = {
		obj_type:objType, 
		id:entId
	}

	// Add definition if it is not loaded already.
	// This will cause the backend to include a .definition property in the resp
	if (netric.entity.definitionLoader.getCached(objType) == null) {
		requestData.loadDef = 1;
	}

	request.send("/svr/entity/get", "GET", requestData);

	// If no callback then construct netric.entity.Entity from request date (synchronous)
	if (!cbLoaded) {
		return this.createFromData(request.getResponse());
	}
}

/**
 * Static function used to create a new object entity
 *
 * This function may need to get the definition from the server. If it is called
 * with no opt_cbCreated it will do a non-async request which could hang the entire UI
 * until the request returns so be careful in this instance because users don't much
 * like that. Try to include the callback param as much as possible. 
 *
 * @param {string} objType The object type to load
 * @param {function} opt_cbCreated Optional callback function once entity is initialized
 */
netric.entity.loader.factory = function(objType, opt_cbCreated) {

	var entDef = netric.entity.definitionLoader.getCached(data.obj_type);

	if (opt_cbCreated) {
		netric.entity.definitionLoader.get(objType, function(def) {
			var ent = new netric.entity.Entity(def);
			opt_cbCreated(ent);
		});
	} else {
		// Force a syncronous request with no second param (callback)
		var def = netric.entity.definitionLoader.get(objType);
		return new netric.entity.Entity(def);
	}
}

/** 
 * Map data to an entity object
 *
 * @param {Object} data The data to create an entity from
 */
netric.entity.loader.createFromData = function(data) {

	if (typeof data === 'undefined') {
		throw "data is a required param to create an object";
	}

	// Get cached object definition
	var entDef = netric.entity.definitionLoader.getCached(data.obj_type);
	// If cached definition is not found then the data object should include a .definition prop
	if (entDef == null && data.definition) {
		entDef = netric.entity.definitionLoader.createFromData(data.definition);
	}

	// If we don't have a definition to work with we should throw an error
	if (entDef == null) {
		throw "Could not load a definition for " + data.obj_type;
	}
	
	// Check to see if we have previously already loaded this object
	var ent = this.getCached(entDef.objType, data.id);
	if (ent != null) {
		ent.loadData(data);
	} else {
		ent = new netric.entity.Entity(entDef, data);

		// Make sure the name was set to something other than "" and place it in cache
		if (ent.id && ent.objType) {
			this.cacheEntity(ent);	
		}
	}
	
	return ent;
}

/**
 * Put an entity in the local cache for future quick loading
 *
 * @param {netric.entity.Entity} ent The entity to store
 */
netric.entity.loader.cacheEntity = function(ent) {

	if (!this.entities_[ent.objType]) {
		this.entities_[ent.objType] = new Object();	
	}

	this.entities_[ent.objType][ent.id] = ent;

}

/** 
 * Get an object entity from cache
 *
 * @param {string} objType The object type to load
 * @param {string} entId The unique entity to load
 * @return {netric.entity.Entity} or null if not cached
 */
netric.entity.loader.getCached = function(objType, entId) {

	// Check to see if the entity is already loaded and return it
	if (this.entities_[objType]) {
		if (this.entities_[objType][entId]) {
			return this.entities_[objType][entId];
		}
	}

	return null;
}
/**
* @fileOverview Main router for handling hashed URLS and routing them to views
*
* Views are a little like pages but stay within the DOM. The main advantage is 
* hash codes are used to navigate though a page. Using views allows you to 
* bind function calls to url hashes. Each view only handles one lovel in the url 
* but can have children so /my/url would be represented by views[my].views[url].show
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2015 Aereus Corporation. All rights reserved.
*/

netric.declare("netric.location.Router");

netric.require("netric");
netric.require("netric.location");
netric.require("netric.location.Route");

/**
 * Creates an instance of AntViewsRouter
 *
 * @constructor
 * @param {netric.location.Router} parentRouter If set this is a sub-route router
 */
netric.location.Router = function(parentRouter) {
	
	/**
	 * Routes for this router
	 *
	 * We store them in an array rather than an object with named params
	 * so that we can simulate fall-through where the first match is
	 * navigated to when it loops throught the routes to find a match.
	 *
	 * @private
	 * @type {netric.location.Route[]}
	 */
	this.routes_ = new Array();

	/**
	 * Parent router
	 *
	 * @private
	 * @type {netric.location.Router}
	 */
	this.parentRouter_ = parentRouter || null;

	/**
	 * Cache the last path loaded so we do not reload a path every route follow
	 *
	 * @private
	 * @type {string}
	 */
	this.lastRoutePath_ = "";

	/**
	 * Store a reference to the currently active route
	 *
	 * @private
	 * @type {netric.location.Route}
	 */
	this.activeRoute_ = null;

	/**
	 * Set a default route path to call if no match is found
	 *
	 * @private
	 * @type {Object} .pattern, path, data
	 */
	this.defaultRoute_ = null;
}

/**
 * Add a route segment to the current level
 * 
 * @param {string} segmentPath Can be a constant string or a variable with ":" prefixed which falls back to the previous route(?)
 * @param {Controller} controller The controller to load
 * @param {Object} opt_data Optional data to pass to the controller when routed to
 * @param {ReactElement} opt_element Optional parent element to render a fragment into
 */
netric.location.Router.prototype.addRoute = function(segmentPath, controller, opt_data, opt_element) {

	// Set defaults
	var data = opt_data || {};
	var ele = opt_element || null;

	// Make sure route does not already exist
	this.removeRoute(segmentPath);

	// Create a fresh new route
	var route = new netric.location.Route(this, segmentPath, controller, data, ele);

	// Add to this.routes_
	this.routes_.push(route);

	// Return for post-add setup
	return route;
}

/**
 * Delete remote a route and cleanup it's children
 *
 * @param {string} segmentPath The path/pattern for the route to remove
 */
netric.location.Router.prototype.removeRoute = function(segmentPath) {
	var route = this.getRoute(segmentPath);
	// TODO: cascade remove route and all its children calling cleanup on constructors
}

/**
 * Get a route by a segment name
 *
 * @param {string} name The name of the route to get
 * @return {netric.location.Route|bool} A route if one exists by that name, otherwise return false
 */
netric.location.Router.prototype.getRoute = function(name) {
	for (var i in this.routes_) {
		if (this.routes_[i].getName() == name) {
			return this.routes_[i];
		}
	}

	return false;
}

/**
 * Get the parent router of this router
 *
 * @return {netric.location.Router}
 */
netric.location.Router.prototype.getParentRouter = function() {
	return this.parentRouter_;
}

/**
 * Get the active path leading up to and including this router
 *
 * @return {string} Full path
 */
netric.location.Router.prototype.getActivePath = function() {
	var pre = "";

	if (this.getParentRouter()) {
		pre = this.getParentRouter().getActivePath();
	}

	if (pre && pre != "/") {
		pre += "/";
	}

	return pre + this.lastRoutePath_;
}

/**
 * Go to a route by path
 * 
 * @param {string} path Path to route to
 * @return {bool} return true if route was found and followed, false if no route matched path
 */
netric.location.Router.prototype.go = function(path) {

	var route = null;

	/*
	 * TODO: future optimization
	 * If we cached not only the path last loaded, but that pattern
	 * that was matched from the route, we could probably determine right
	 * here if anything needs to happen or if we should just jump to the next
	 * hop of this.activeRoute_ and forgo looping through each route and 
	 * matching the pattern to the path.
	 * - joe
	 */

	// Loop through each route and see if we have a match
	for (var i in this.routes_) {
		var matchObj = this.routes_[i].matchesPath(path);
		if (matchObj) {
			// Follow matched route down to next hope
			return this.followRoute(this.routes_[i], matchObj.path, matchObj.params, matchObj.nextHopPath);
		}
	}

	/*
	 * No match was found. Check to see if there is a default that is 
	 * differed (circular calls to self = bad),
	 */
	if (this.defaultRoute_ && this.defaultRoute_ != path) {
		return this.go(this.defaultRoute_);
	}

	return false;
}

/**
 * Goto a specific route
 *
 * @param {netric.location.Route} route The route to load
 * @param {string} opt_path If we are loading a route from a path, what the actual path was
 * @param {Object} opt_params URL params
 * @param {string} opt_remainingPath The rest of the path to continue loading past route
 */
netric.location.Router.prototype.followRoute = function(route, opt_path, opt_params, opt_remainingPath) {
	var segPath = opt_path || "";
	var params = opt_params || {};
	var remPath = opt_remainingPath || "";

	// Check to see if we have already loaded this path
	if (segPath != this.lastRoutePath_) {
		// Set local history to keep from re-rendering when a subroute changes
		this.lastRoutePath_ = segPath;

		// Exit the last active route
		if (this.activeRoute_) {
			this.activeRoute_.exitRoute();
		}

		// Trigger route change event
		alib.events.triggerEvent(this, "routechange", { path: segPath});

		// Save new active route
		this.activeRoute_ = route;

		// Load up and enter the route
		route.enterRoute(params, function() {
			if (remPath) {
				// If we have more hops then continue processing full path at next hop
				route.getChildRouter().go(remPath);
			} else {
				// There may be a default route for the next router to navigate to
				route.getChildRouter().goToDefaultRoute();
			}
		});

	} else  if (remPath) {
		// Send the remainder down to the next hop because this segment is unchanged
		route.getChildRouter().go(remPath);
	} else {
		/* 
		 * This is the end of the path. Find out if the next router has a default 
		 * we should load, otherwise exit.
		 */
		if (!route.getChildRouter().goToDefaultRoute()) {
			route.getChildRouter().exitActiveRoute();
		}
	}
	
}

/**
 * Exit active route
 */
netric.location.Router.prototype.exitActiveRoute = function() {
	var actRoute = this.getActiveRoute();
	if (actRoute) {
		actRoute.exitRoute();
	}

	this.activeRoute_ = null;
	this.lastRoutePath_ = "";
}

/**
 * Get the currently active route
 *
 * @return {netric.location.Route} Route if active, null of no routes are active
 */
netric.location.Router.prototype.getActiveRoute = function() {
	return this.activeRoute_;
}

/**
 * Set the default route to use if no route is provided
 *
 * @param {string} defaultRoute
 */
netric.location.Router.prototype.setDefaultRoute = function(defaultRoute) {
	this.defaultRoute_ = defaultRoute;
}

/**
 * Navigate to the default route if one exists
 *
 * @return {bool} true if there was a default route, false if no default exists
 */
netric.location.Router.prototype.goToDefaultRoute = function() {

	if (this.defaultRoute_) {
		// Get the base path
		var basePath = this.getActivePath();
		if (basePath != "/") {
			basePath += "/";
		}

		// We can't just call this.go because we need to change the location object
		netric.location.go(basePath + this.defaultRoute_);
		return true;
	} 

	// There was no default route
	return false;
}
/**
 * @fileoverview Base controller for MVC
 *
 * TODO: this class is a concept and a work in progress
 */

alib.declare("netric.mvc.Controller");

alib.require("netric");
alib.require("netric.mvc");

/**
 * Make sure module namespace is initialized
 */
netric.mvc = netric.mvc || {};

/**
 * Controller constructor
 *
 * @param {string} name The name for this controller often used for unique routes
 * @param {netric.mvc.Controller} parentController Optional parent controller
 */
netric.mvc.Controller = function(name, parentController) {
	
	/**
	 * Type determins how this controller is loaded into the document
	 *
	 * @private
	 */
	this.type_ = netric.mvc.Controller.types.page;
}

/**
 * Define the controller types to determine how they are rendered
 *
 * @public
 */
netric.mvc.Controller.types = {
	
	// Page will hide parent when loaded
	page : 1,
	
	// A fragmen is loaded inline in the context of a page
	fragment : 3,

	// Modals are opened in a dialog
	modal : 5
}

/**
 * Render an action into the dom
 *
 * @param {string} actionName The name of the action which maps to 'name'Action function
 * @param {Object} params Optional object of params set to be passed to action
 * @param {string} postFix Optional trailing path to be loaded after this
 */
netric.mvc.Controller.prototype.loadAction = function(actionName, params, postFix) {
	
	// Use the default action if none has been set
	if (!actionName && this.defaultAction)
		actionName = this.defaultAction;

	var bFound = false;

	if (!postFix)
		var postFix = "";

	// Loop through child views, hide all but the action to be rendered
	for (var i = 0; i < this.views.length; i++)
	{
		// Find the view by name
		if (this.views[i].name == actionName)
		{
			//this.views[i].variable = params;

			// Flag that we found the view
			bFound = true;

			/*
			 * If we are a child view and the views are set to single pages only
			 * the last view in the list should be viewable and the parent will be hidden
			 */
			if (this.pageViewSingle && this.views[i].parentView)
				this.views[i].parentView.hide();

			if (postFix!="") // This is not the top level view - there are children to display in the path
			{
				/*
				 * Check to see if this view has been rendered 
				 * already - we only render the first time
				 * It is possible in a scenario where a deep url is loaded
				 * like /my/path to have 'my' never shown because we jump
				 * straight to 'path' but we still need to make sure it is rendered.
				 */
				if (this.views[i].isRendered == false)
				{
					this.views[i].render();
					this.views[i].isRendered = true;
				}

				/*
				 * As mentioned above, if we are in singleView mode then 
				 * don't show views before the last in the list
				 */
				if (!this.pageViewSingle)
					this.views[i].show();

				// Continue loading the remainder of the path - the child view(s)
				this.views[i].load(postFix);
			}
			else // This is a top-level view meaning there are no children
			{
				this.views[i].show(); // This will also render if the view has not yet been rendered
				this.views[i].hideChildren();
			}

			// Call load callbacks for view
			this.views[i].triggerEvents("load");
		}
		else if (this.pageView) // Hide this view if we are in pageView because it was not selected
		{
			/*
			 * pageView is often used for tab-like behavior where you toggle 
			 * through pages/views at the same level - not affecting parent views
			 */
			this.views[i].hide();
			this.views[i].hideChildren();
		}
	}

	return bFound;
}

/**
 * Called from the constructor to automatically add actions this.prototype.*Action
 */
netric.mvc.Controller.prototype.init = function() {
	// TODO: look for any functions in 'this' that are actions and automatically
	// add a view for that function
}

/**
 * Event called when the controller is shown
 */
netric.mvc.Controller.prototype.onShow = function() {
	
}

/**
 * Event called when the controller is hidden
 */
netric.mvc.Controller.prototype.onHide = function() {
	
}

/**
* @todo: This is a port from AntViewManager and a work in progress
*
* Add a new action and view to this controller
*
* @param {string} name The unique name (in this controller) of this view
* @param {object} optionsargs Object of optional params that populates this.options
* @param {object} con Contiaining lement. If passed, then a sub-con will automatically be created. 
* 							If not passed, then pure JS is assumed though utilizing the onshow 
* 							and onhide callbacks for this view			
* @param {object} parentView An optional reference to the parent view. 
* 							This is passed when the view.addView function is called to maintain heiarchy.		 
*
AntViewManager.prototype.addAction = function(name, optionargs, con, parentView)
{
	var pView = (parentView) ? parentView : null;
	var useCon = (con) ? con : null;

	// Make sure this view is unique
	for (var i = 0; i < this.views.length; i++)
	{
		if (this.views[i].nameMatch(name))
			return this.views[i];
	}

	var view = new AntView(name, this, pView);
	view.options = optionargs;
	if (useCon)
	{
		view.conOuter = useCon;
	}
	else if (parentView)
	{
		if (parentView.conOuter)
			view.conOuter = parentView.conOuter;
	}
	if (this.isMobile)
	{
		var contentCon = document.getElementById(view.getPath()+"_con");
		if (!contentCon)
		{
			var path = view.getPath();
			var pageCon = alib.dom.createElement("div", document.getElementById("main"));
			pageCon.style.display="none";
			pageCon.style.position="absolute";
			pageCon.style.top="0px";
			pageCon.style.width="100%";
			pageCon.id = path;

			// Main header container
			var headerCon = alib.dom.createElement("div", pageCon);
			alib.dom.styleSetClass(headerCon, "header");

			// Right button container
			var rightButton = alib.dom.createElement("button", headerCon);
			alib.dom.styleSetClass(rightButton, "right");

			// Left button container
			if (view.hasback())
			{
				var leftButton = alib.dom.createElement("button", headerCon, "Back");
				alib.dom.styleSetClass(leftButton, "left arrow");
				leftButton.view = view;
				leftButton.onclick = function() { view.goup(); }
			}

			// Title container
			var title = alib.dom.createElement("h1", headerCon);

			if (typeof Ant != "undefined")
				title.innerHTML = view.getTitle();
				//title.innerHTML = Ant.account.companyName;

			// joe: I believe this may be depriacted but needs to be verified
			var conAppTitle = alib.dom.createElement("div", headerCon);
			
			var contentCon = alib.dom.createElement("div", pageCon);
			contentCon.id = path+"_con";
			alib.dom.styleSetClass(contentCon, "viewBody");

			// Used by the AntApp class to set the title of the application
			view.conAppTitle = conAppTitle;
		}
		
		view.con = contentCon;
	}
	else
	{
		view.con = (view.conOuter) ? alib.dom.createElement("div", view.conOuter) : null;
		if (view.con)
			view.con.style.display = 'none';
	}

	this.views[this.views.length] = view;
	return view;
}
*/

/**
 * Resize the active view and it's children
 *
AntViewManager.prototype.resizeActiveView = function()
{
	if (this.currViewName)
	{
		var actView = this.getView(this.currViewName);
		if (actView)
			actView.resize();
	}

}
*/

/**
* Load a view by converting a path to a name
*
* @param {string} path path like my/app/name will load "my" view of this viewManager
*
AntViewManager.prototype.load = function(path)
{
	this.path = path;
	var postFix = "";
	var nextView = "";

	if (this.path.indexOf("/")!=-1)
	{
		var parts = this.path.split("/");
		this.currViewName = parts[0];
		if (parts.length > 1)
		{
			for (var i = 1; i < parts.length; i++) // Skip of first which is current view
			{
				if (postFix != "")
					postFix += "/";
				postFix += parts[i];
			}
		}
	}
	else
		this.currViewName = path;

	var variable = "";
	var parts = this.currViewName.split(":");
	if (parts.length > 1)
	{
		this.currViewName = parts[0];
		variable = parts[1];
	}

	return this.loadView(this.currViewName, variable, postFix);
}
*/

/**
* Get a view by name
*
* @param {string} name unique name of the view to load
*
AntViewManager.prototype.getView = function(name)
{
	for (var i = 0; i < this.views.length; i++)
	{
		// Find the view by name
		if (this.views[i].name == name)
			return this.views[i];
	}

	return null
}
*/


/**
* Change fToggle flag. If true, then only one view is visible at a time. If one is shown, then all other views are looped through and hidden. This is great for tabs.
*
* @param {boolean} fToggle toggle view; default: true
*
AntViewManager.prototype.setViewsToggle = function(fToggle)
{
	this.pageView = fToggle;
}
*/

/**
* Change pageViewSingle flag. If true, then only one view is visible at a time and the parent view is hidden. This setting is per ViewManager and isolated to one level so you can have: 
* viewRoot (pageView - tabs) -> viewNext (will leave root alone) 
* viewApp (single will hide/replace viewNext)
*
* @param {boolean} fToggle toggle view; default: true
*
AntViewManager.prototype.setViewsSingle = function(fToggle)
{
	this.pageViewSingle = fToggle;
}
*/

/**
 * Get active views at this manager level only
 *
 * @public
 * @return {AntViews[]}
 *
AntViewManager.prototype.getActiveViews = function()
{
	var ret = new Array();

	for (var i in this.views)
	{
		if (this.views[i].isActive())
			ret.push(this.views[i]);
	}

	return ret;
}
*/

/*
 * Usage
 * 
 * Can either extend the controller or build it inline

netric.controller.MyController(args...) {
	// Call parent class constructor
	netric.mvc.Controller.call(this, args...);
}

// Set base class
alib.extends(netric.controller.MyController, netric.mvc.Controller);

// Default action
netric.controller.MyController.prototype.actionIndex = function(view) {
	// This is basically the new render function

	// Build UI elements here using view.con

	// Can add sub-controllers to the route by initializing in the aciton
	// and retuning the controller. This controller will link the subcontroller into
	// the automatic routing system so that childController.mainAction will load
	// by default if it exists
	vat con = alib.dom.createElement("div", view.con); // Chilld of view container
	var ctlr = new netric.controller.ObjectBrowserController(con);
	return ctlr;
}
*/

/*
// Old way
var view = this.view.addSubView("appname");
view.render = function() {

}

// New way which calls view.subcontroller.addAction in the view class
var controllerAction = this.view.addSubAction("open/:id", function(view, params) {

});
*/
/**
* @fileOverview Main router for handling hashed URLS and routing them to views
*
* Views are a little like pages but stay within the DOM. The main advantage is 
* hash codes are used to navigate though a page. Using views allows you to 
* bind function calls to url hashes. Each view only handles one lovel in the url 
* but can have children so /my/url would be represented by views[my].views[url].show
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/

alib.declare("netric.mvc.Router");

alib.require("netric");
alib.require("netric.mvc");

/**
 * Creates an instance of AntViewsRouter
 *
 * @constructor
 * @param {netric.mvc.Route} parentRoute If set this is a sub-route router
 */
netric.mvc.Router = function(parentRoute) {
	
	/**
	 * Keep a record of the last route loaded
	 * 
	 * @private
	 * @type {string}
	 */
	this.lastLoaded = "";
	
	/**
	 * Default route name
	 *
	 * @public
	 * @type {string}
	 */
	this.defaultRoute = "";
	
	/**
	 * Additional free-form options
	 * 
	 * @type {Object}
	 */
	this.options = new Object();

	// Begin watching/pinging the hash of the document location
	var me = this;
	this.interval = window.setInterval(function(){ me.checkNav(); }, 50);
}

/** 
 * Query the navigation from either the history or hash
 * 
 * Currently this only supports using the hash, but in the 
 * future we may use the HTML history API to load the pages
 * without a hash tag.
 */
netric.mvc.Router.prototype.checkNav = function() {
	var load = "";
	if (document.location.hash)
	{
		var load = document.location.hash.substring(1);
	}
    
	if (load == "" && this.defaultRoute != "")
		load = this.defaultRoute;

	if (load != "" && load != this.lastLoaded)
	{
		this.lastLoaded = load;
		//ALib.m_debug = true;
		//ALib.trace(load);
		this.onchange(load);
	}
}

/**
 * Callback can be overridden and triggered when a hash changes in the URL
 */
netric.mvc.Router.prototype.onchange = function(path) {
}

/**
 * Add a route segment to the current level
 * 
 * @param {string} segmentName Can be a constant string or a variable with ":" prefixed which falls back to the previous route(?)
 * @param {Controller} controller The controller to load
 * @param {Object} data Optional data to pass to the controller when routed to
 * @param {ReactElement} opt_element Optional parent element to render a fragment into
 */
netric.mvc.Router.prototype.addRoute = function(segmentName, controller, data, opt_element) {

}

/**
* @fileOverview netric.mvc.View(s) allow dom elements to be treated like pages and mapped to URL
*
* Each view has a parent manager (reposible for showing and hiding it) then  
* a child manager to handle sub-views. These are basically simple routers.
*
* Views enable a single-page application (no refresh) to have multi-level views
* and deep-linking through the use of a hash url.
*
* Example:
* <code>
* 	parentView.setViewsSingle(true); // Only display one view at a time - children hide parent view
*
* 	var viewItem = parentView.addView("viewname", {});
*
*	viewItem.options.param = "value to forward"; // options is a placeholder object for passing vars to callbacks
*
*	viewItem.render = function() // called only the first time the view is shown
*	{ 
*		this.con.innerHTML = "print my form here"; // this.con is automatically created
*	} 
*
*	viewItem.onshow = function()  // draws in onshow so that it redraws every time the view is displayed
*	{ 
*	};
*
*	viewItem.onhide = function()  // is called every time the view is hidden
*	{ 
*	};
* </code>
*
* @author: 	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.mvc.View");

alib.require("netric");
alib.require("netric.mvc");

/**
* Create a view instance of netric.mvc.View and place in in an array in viewman
*
* @constructor
* @param {string} name Required unique string name of this view
* @param {object} viewman Required reference to netric.mvc.ViewManager object
* @param {object} parentView reference to a parent netric.mvc.View object
*/
netric.mvc.View = function(name, viewman, parentView)
{

	this.parentViewManager = viewman;
	this.viewManager = null;
	this.parentView = (parentView) ? parentView : null;
	this.activeChildView = null;
	this.isActive = false;
	this.isRendered = false;
	this.options = new Object();
	this.conOuter = null; 		// container passed to place view in
	this.con = null;	 		// child container of conOuter that holds this rendered view
	this.conChildren = null; 	// child container of conOuter that holds all rendered children
	this.variable = ""; 		// variables can exist in url part. Example: view p:pnum would render view p with variable num
	this.pathRelative = ""; 	// path relative to this view
	this.title = "";			// optional human readable title
	this.defaultView = "";
	this.setOnTitleChange = new Array();
    this.fromClone = false;     // determines if the object was being cloned
    
	var parts = name.split(":");
	this.name = parts[0];
	if (parts.length > 1)
	{
		// Possible save the variable name if we are going to use multiple variables
	}
}

/**
 * Call all bound callback functions because this view just loaded
 *
 * @param {string} evname The name of the event that was fired
 */
netric.mvc.View.prototype.triggerEvents = function(evname)
{
	alib.events.trigger(this, evname);
	/*
	for (var i = 0; i < this.boundEvents.length; i++)
	{
		if (this.boundEvents[i].name == evname)
		{
			if ("load" == evname) // add extra param
				this.boundEvents[i].cb(this.boundEvents[i].opts, this.pathRelative);
			else
				this.boundEvents[i].cb(this.boundEvents[i].opts);
		}
	}
	*/
}

/**
 * Call all bound callback functions because this view just loaded
 */
netric.mvc.View.prototype.setDefaultView = function(viewname)
{
	this.defaultView = viewname;
}

/**
 * Call this function to fire all resize events
 *
 * @param {bool} resizeChildren If set to true, all active children will be resized
 */
netric.mvc.View.prototype.resize = function(resizeChildren)
{
	var resizeCh = (resizeChildren) ? true : false;

	if (resizeCh)
		this.viewManager.resizeActiveView();

	this.onresize();
}

/**
 * Internal function to show this view. Will call render on the first time. redner shold be overridden.
 */
netric.mvc.View.prototype.show = function()
{
	//ALib.m_debug = true;
	//ALib.trace("Show: " + this.getPath());

	if (!this.isRendered)
	{
		this.isRendered = true;
		this.render();
	}

	if (this.con)
	{
		//alib.fx.fadeIn(this.con, function() { alib.dom.styleSet(this, "display", "block");  });
		alib.dom.styleSet(this.con, "display", "block");
	}

	if (this.parentViewManager.isMobile)
	{
		var isBack = (window.avChangePageback) ? true : false;
		changePage(this.getPath(true), isBack); // The true param excludes vars to make change to containers rather than specific ids
		window.avChangePageback = false; // Reset flag for next time
	}

	if (this.defaultView && !this.hasChildrenVisible())
		this.navigate(this.defaultView);
	
	this.triggerEvents("show");
	this.onshow();
	this.onresize();
	this.isActive = true;
}

/**
* Internal function to hide this view.
*/
netric.mvc.View.prototype.hide = function(exclChild)
{
	if (!this.isActive)
		return false;

	if (this.con)
	{
		this.con.style.display = "none";
		//alib.fx.fadeOut(this.con, function() { alib.dom.styleSet(this, "display", "none"); });
	}

	this.isActive = false;

	if (this.isRendered)
	{
		this.triggerEvents("hide");
		this.onhide();
	}
}

/**
* Pass-through to this.parentViewManager.addView with this as parent
* See netric.mvc.ViewManager::addView
*/
netric.mvc.View.prototype.addView = function(name, optionargs, con)
{
	var usecon = (con) ? con : null;

	if (this.viewManager == null)
	{
		this.viewManager = new netric.mvc.ViewManager();
	}

	//ALib.m_debug = true;
	//ALib.trace("Adding View: " + this.getPath() + "/" + name);

	return this.viewManager.addView(name, optionargs, usecon, this);
}

/**
* Get a child view by name
*
* @param {string} name unique name of the view to load
* @return {netric.mvc.View} View if found by name, null if no child view exists
*/
netric.mvc.View.prototype.getView = function(name)
{
	if (this.viewManager)
	{
		return this.viewManager.getView(name);
	}

	return null
}

/**
* Pass-through to this.parentViewManager.setViewsToggle
* See netric.mvc.ViewManager::addView
*/
netric.mvc.View.prototype.setViewsToggle = function(fToggle)
{
	if (this.viewManager == null)
	{
		this.viewManager = new netric.mvc.ViewManager();
	}
	
	this.viewManager.setViewsToggle(fToggle);
}

/**
* Pass-through to this.parentViewManager.setViewsToggle. When a child shows then hide this view - used for heiarch
* See netric.mvc.ViewManager::setViewsSingle
*/
netric.mvc.View.prototype.setViewsSingle = function(fToggle)
{
	if (this.viewManager == null)
	{
		this.viewManager = new netric.mvc.ViewManager();
	}
	
	this.viewManager.setViewsSingle(fToggle);
}

/**
 * Get the parent view if set
 *
 * @return {netric.mvc.View|bool} View if parent is set, false if there is no parent
 */
netric.mvc.View.prototype.getParentView = function()
{
	return (this.parentView) ? this.parentView : false;
}

/**
* Traverse views and get the full path of this view:
* view('app').view('customers') = 'app/customers'
*
* @param bool excludevars If set to true, then vars will not be included in the returned path
*/
netric.mvc.View.prototype.getPath = function(excludevars)
{
	var name = this.name;
	var doNotPrintVar = (typeof excludevars != "undefined") ? excludevars : false;
    
	// Make sure the variable in included
	if (this.variable && doNotPrintVar == false)
		name += ":" + this.variable;
        
	if (this.parentView)
		var path = this.parentView.getPath() + "/" + name;
	else
		var path = name;

	return path;
}

/**
* Get a numan readable title. If not set, then create one.
*
* @this {netric.mvc.View}
* @public
* @param {DOMElement} el An optional dom element to bind 'onchange' event to. When title of view changes, the innerHTML of el will change
* @return {string} The title of this view
*/
netric.mvc.View.prototype.getTitle = function(el)
{
	if (this.title)
	{
		var title = this.title;
	}
	else
	{
		// replace dash with space
		var title = this.name.replace('-', ' ');
		// replace underline with space
		var title = this.name.replace('_', ' ');
		// ucword
		title = title.replace(/^([a-z])|\s+([a-z])/g, function ($1) { return $1.toUpperCase(); });
	}

	if (typeof el != "undefined")
	{
		el.innerHTML = title;
		this.setOnTitleChange[this.setOnTitleChange.length] = el;
	}
	else
	{
		return title;
	}
}

/**
* Set a human readable title
*/
netric.mvc.View.prototype.setTitle = function(title)
{
	this.title = title;
	for (var i = 0; i < this.setOnTitleChange.length; i++)
	{
		try
		{
			this.setOnTitleChange[i].innerHTML = title;
		}
		catch(e) {}
	}
}

/**
* Check url part to see if the name matches this view
*/
netric.mvc.View.prototype.nameMatch = function(name)
{
	if (typeof name == "undefined")
	{
		throw "No view name was passed to netric.mvc.View::nameMatch for " + this.getPath();
	}

	var parts = name.split(":");
	name = parts[0];

	return (name == this.name) ? true : false;
}

/**
* Set the hash and load a view programatically. 
*/
netric.mvc.View.prototype.navigate = function(viewname)
{
	document.location.hash = "#" + this.getPath() + "/" + viewname;
}

/**
* Check if going back a view is an option (are we on first level).
*
* This does not rely entirely on the history object because if 
* we are at the root view (home), then we don't want to go back.
*/ 
netric.mvc.View.prototype.hasback = function()
{
	var ret = false; // Assume we are on the root
	var path = this.getPath();

	if (path.indexOf("/")!=-1)
	{
		if (history.length > 1)
			ret = true;
	}

	return ret;
}

/**
* Navigate up to parent view
*/
netric.mvc.View.prototype.goup = function(depth)
{
	if (this.parentView)
	{
		document.location.hash = "#" + this.parentView.getPath();
	}
	else
	{
		history.go(-1);
	}

	// global avChangePageback is used in mobile to determine transition direction
	window.avChangePageback = true;
}

/**
* Check if child views are being shown = check for deep linking
*
* @return {bool} True if the hash path has child views visible, otherwise false
*/
netric.mvc.View.prototype.hasChildrenVisible = function()
{
	if (document.location.hash == "#" + this.getPath() || document.location.hash == "") // last assumes default
		return false;
	else
		return true;
}

/**
* Go back
*/
netric.mvc.View.prototype.goback = function(depth)
{
	history.go(-1);

	// global avChangePageback is used in mobile to determine transition direction
	window.avChangePageback = true;
}

/**
* Pass-through to this.parentViewManager.load
* See netric.mvc.ViewManager::load
*/
netric.mvc.View.prototype.load = function(path)
{
	this.pathRelative = path; // path relative to this view

	if (this.viewManager != null)
	{
		if (!this.viewManager.load(path))
		{
			this.m_tmpLoadPath = path; // If it failed to load, cache for later just in case views are still loading
		}
	}
	else
	{
		this.m_tmpLoadPath = path; // If it failed to load, cache for later just in case views are still loading
	}
}

/**
* Clear loading flag that will cause all subsequent load calls to be queued until setViewsLoaded is called.
*/
netric.mvc.View.prototype.setViewsLoaded = function()
{
	//ALib.m_debug = true;
	//ALib.trace("View ["+this.name+"] finished loading ");

	if (this.m_tmpLoadPath)
	{
		//ALib.m_debug = true;
		//ALib.trace("Found delayed load " + this.m_tmpLoadPath);
		this.load(this.m_tmpLoadPath);
		this.m_tmpLoadPath = "";
	}

	if (this.defaultView && !this.hasChildrenVisible())
		this.navigate(this.defaultView);

	// Call load callbacks for view
	this.triggerEvents("load");
}

/**
* Find out if this view had children views
*/
netric.mvc.View.prototype.hideChildren = function()
{
	if (this.viewManager)
	{
		for (var i = 0; i < this.viewManager.views.length; i++)
		{
			this.viewManager.views[i].hide();
			this.viewManager.views[i].hideChildren();
		}
	}

	this.pathRelative = ""; // Reset relative path
}

/**
 * Gets the object id from hash url string
 *
 * @public
 * @this {netric.mvc.View}
 * @param {string} objName      Object Name to be checked
 */
netric.mvc.View.prototype.getHashObjectId = function(objName)
{
    if(this.name == objName)
        return this.variable;
        
    if (this.parentView)
        var objId = this.parentView.getHashObjectId(objName);
    
    if(objId)
        return objId;
    else
        return false;
}

/**
* Used to draw view and should be overriden. 
*
* If a containing element was passed on new netric.mvc.View then this.con 
* will be populated with a div that can be manipulated with contents. 
* this.options is also available for any processing.
*/
netric.mvc.View.prototype.render = function()
{
}

/**
* Can be overridden. Fires once a view is shown.
*/
netric.mvc.View.prototype.onshow = function()
{
}

/**
* Can be overridden. Fires once a view is hidden.
*/
netric.mvc.View.prototype.onhide = function()
{
}

/**
* Can be overridden. Fires once a view is shown for resizing.
*/
netric.mvc.View.prototype.onresize = function()
{
	//alib.m_debug = true;
	//alib.trace("Resize: " + this.name);
}

/**
* @fileOverview Load instance of netric application
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.mvc.ViewTemplate");

alib.require("netric.mvc");

/**
 * Application instance
 *
 * @param {netric.Account} account The current account netric is running under
 */
netric.mvc.ViewTemplate = function(opt_html) {
	/**
	 * Raw HTML for this template
	 * 
	 * 
	 * @private
	 * @type {string}
	 */
	this.html_ = opt_html || "";

	/**
	 * Dom elements for template
	 * 
	 * @type {DOMElement[]}
	 */
	this.domElements_ = null;

	/**
	 * Associateve array of dom elements that can be referenced later by name
	 *
	 * @private
	 * @type {Array()}
	 */
	this.domExports_ = null;
}

/**
 * Get the dom element for this template
 * 
 * @public
 * @return {DOMElement}
 */
netric.mvc.ViewTemplate.prototype.getDom = function() {
	if (this.domElements_ != null) {
		return this.domElements_;
	}

	// TODO: render this.html_ to dom and return
}

/**
 * Add a dom element to the template
 * 
 * @param {DOMElement} domEl The element to add to this template
 * @param {string} opt_exportName Optional name to export for reference by this.<name>
 */
netric.mvc.ViewTemplate.prototype.addElement = function(domEl, opt_exportName) {

	if (this.domElements_ == null) {
		this.domElements_ = new Array();
	}

	// Add the element to the template
	this.domElements_.push(domEl);

	if (opt_exportName) {
		this[opt_exportName] = domEl;
	}
}

/**
 * Render the template into a dom element
 * 
 * @param {DOMElement}
 */
netric.mvc.ViewTemplate.prototype.render = function(domCon) {
	if (this.domElements_ != null) {
		for (var i in this.domElements_) {
			domCon.appendChild(this.domElements_[i]);
		}

	} else {
		domCon.innerHTML = this.html_;
	}
}
/**
* @fileOverview Load instance of netric application
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/
netric.declare("netric.Application");

netric.require("netric");
netric.require("netric.account.loader");
netric.require("netric.location");
netric.require("netric.location.Router");
netric.require("netric.Device");
netric.require("netric.controller.MainController");

/**
 * Application instance
 *
 * @param {netric.Account} account The current account netric is running under
 */
netric.Application = function(account) {
	/**
	 * Represents the actual netric account
	 *
	 * @public
	 * @var {netric.Application.Account}
	 */
	this.account = account;

	/**
	 * Device information class
	 *
	 * @public
	 * @var {netric.Device}
	 */
	this.device = new netric.Device();
};

/**
 * Static function used to load the application
 *
 * @param {function} cbFunction Callback function once app is loaded
 */
netric.Application.load = function(cbFunction) {

	/*
	 * The first thing we need to do is load the current account so
	 * we can inject it as a dependency to the application instance.
	 */
	netric.account.loader.get(function(acct){

		// Create appliation instance for loaded account
		var app = new netric.Application(acct);

		// Set global reference to application to enable netric.getApplication();
		netric.application_ = app;  

		// Callback passing initialized application
		if (cbFunction) {
			cbFunction(app);	
		}
	});
}

/**
 * Get the current account
 *
 * @return {netric.Account}
 */
netric.Application.prototype.getAccount = function() {
	return this.account;
}

/**
 * Run the loaded application
 *
 * @param {DOMElement} domCon Container to render applicaiton into
 */
netric.Application.prototype.run = function(domCon) {

	// Load up the new router
	var router = new netric.location.Router();

	// Create the root route which is also the default
	router.addRoute("/", netric.controller.MainController, {}, domCon);

	// Setup location change listener
	netric.location.setupRouter(router);

	// Create root application view
	//var appView = new netric.ui.ApplicationView(this);

	/*
	 * Setup the router so that any change to the URL will route through
	 * the redner action for the front contoller which will propogate the new
	 * url path down through all children contollers as well.
	 */
	//var router = new netric.mvc.Router();
	//router.onchange = function(path) {
	//	appView.load(path);
	//}

	// Render application
	//appView.render(domCon);
}
/**
 * @fileoverview Main application controller
 */
alib.declare("netric.controller.AppController");

alib.require("netric.mvc.Controller");
alib.require("netric.controller");

// Include views
alib.require("netric.template.application.small");
alib.require("netric.template.application.large");

/**
 * TMake sure the netric controller namespace exists
 */
netric.controller = netric.controller || {};

netric.controller.AppController = function(domCon) {
	// Case base class constructor
	netric.mvc.Controller.call(this, domCon);
}

/**
 * Extend base controller class
 */
alib.inherits(netric.controller.AppController, netric.mvc.Controller);

/**
 * Default action will be called if action was specified
 *
 * @param {netric.mvc.View}
 */
netric.controller.AppController.prototype.mainAction = function(view) {

	switch (netric.getApplication().device.size)
	{
	case netric.Device.sizes.small:
		view.setTemplate(netric.view.application.small);
		break;
	case netric.Device.sizes.medium:
	case netric.Device.sizes.large:
		view.setTemplate(netric.view.application.large);
		break;
	}

	// Add modules controller
	
}
/**
 * @fileOverview Collection of entities
 *
 * Example:
 * <code>
 * 	var query = new netric.entity.Query("customer");
 * 	query.where('first_name').equals("sky");
 *  query.andWhere('last_name').contains("steb");
 *	query.orderBy("last_name", "desc");
 *	netric.entity.collectionLoader.get(query, function(collection) {
 *		// TODO: do something with the data in collection
 *	});
 *	
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */
alib.declare("netric.entity.Collection");

alib.require("netric");
alib.require("netric.entity.Definition");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Entity represents a netric object
 *
 * @constructor
 * @param {netric.entity.Definition} entityDef Required definition of this entity
 * @param {Object} opt_data Optional data to load into this object
 */
netric.entity.Collection = function(entityDef, opt_data) {
}

/**
* @fileOverview collection loader
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.entity.collectionLoader");

alib.require("netric");

alib.require("netric.entity.Collection");
alib.require("netric.BackendRequest");

/**
 * Make sure entity namespace is initialized
 */
netric.entity = netric.entity || {};

/**
 * Entity collection loader
 *
 * @param {netric.Application} application Application instance
 */
netric.entity.collectionLoader = netric.entity.collectionLoader || {};

/**
 * Static function used to load an entity collection
 *
 * If no callback is set then this function will try to return the collection
 * from cache. If it has not yet been loaded then it will force a non-async
 * request which will HANG THE UI so it should only be used as a last resort.
 *
 * @param {string} objType The object type we are loading a collection for
 * @param {function} cbLoaded Callback function once collection is loaded
 */
netric.entity.collectionLoader.get = function(query, cbLoaded) {

	var collection = new netric.entity.Collection();
	this.loadCollection(query, collection, cbLoaded);
	
}

/**
 * Querty the backend and set the results for a collection
 *
 * @param {netric.entity.Query} query
 * @param {netric.entity.Collection} collection Collection to store results in
 * @param {function} cbLoaded Callback function once collection is loaded
 */
netric.entity.collectionLoader.loadCollection = function(query, collection, cbLoaded) {
	var request = new netric.BackendRequest();

	if (cbLoaded) {
		alib.events.listen(request, "load", function(evt) {
			var def = netric.entity.collectionLoader.createFromData(this.getResponse());
			cbLoaded(def);
		});
	} else {
		// Set request to be synchronous if no callback is set	
		request.setAsync(false);
	}

	request.send("svr/entity/getDefinition", "GET", {obj_type:objType});
}
/**
* @fileOverview Manage single layer of views in an array.
*
* Each view has a parent manager (reposible for showing and hiding it) then  
* a child manager to handle sub-views. These are basically simple routers.
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/
alib.declare("netric.mvc.ViewManager");

alib.require("netric");
alib.require("netric.mvc");
alib.require("netric.mvc.ViewTemplate")

/**
 * Creates an instance of netric.mvc.ViewManager
 *
 * @constructor
 */
netric.mvc.ViewManager = function()
{
	this.path = "";
	this.currViewName = "";
	this.views = new Array();
	this.pageView = false; 			// Pageview means only one view is avaiable at a time
	this.pageViewSingle = false; 	// pageViewSingle means that if a child view shows, this view is hidden
	this.isMobile = false;			// Handle creating things differently
}

/**
* Add a new view
*
* @param {string} name The unique name (in this viewmanager) of this view
* @param {object} optionsargs Object of optional params that populates this.options
* @param {object} con Contiaining lement. If passed, then a sub-con will automatically be created. 
* 							If not passed, then pure JS is assumed though utilizing the onshow 
* 							and onhide callbacks for this view			
* @param {object} parentView An optional reference to the parent view. 
* 							This is passed when the view.addView function is called to maintain heiarchy.		 
*/
netric.mvc.ViewManager.prototype.addView = function(name, optionargs, con, parentView)
{
	var pView = parentView || null;
	var useCon = con || null;

	// Make sure this view is unique
	for (var i = 0; i < this.views.length; i++)
	{
		// If a view by this name already exists, then return it
		if (this.views[i].nameMatch(name))
			return this.views[i];
	}

	// Create new view
	var view = new netric.mvc.View(name, this, pView);
	view.options = optionargs;
	if (useCon)
	{
		view.conOuter = useCon;
	}
	else if (parentView)
	{
		if (parentView.conOuter)
			view.conOuter = parentView.conOuter;
	}

	if (this.isMobile)
	{
		var contentCon = document.getElementById(view.getPath()+"_con");
		if (!contentCon)
		{
			var path = view.getPath();
			var pageCon = alib.dom.createElement("div", document.getElementById("main"));
			pageCon.style.display="none";
			pageCon.style.position="absolute";
			pageCon.style.top="0px";
			pageCon.style.width="100%";
			pageCon.id = path;

			// Main header container
			var headerCon = alib.dom.createElement("div", pageCon);
			alib.dom.styleSetClass(headerCon, "header");

			// Right button container
			var rightButton = alib.dom.createElement("button", headerCon);
			alib.dom.styleSetClass(rightButton, "right");

			// Left button container
			if (view.hasback())
			{
				var leftButton = alib.dom.createElement("button", headerCon, "Back");
				alib.dom.styleSetClass(leftButton, "left arrow");
				leftButton.view = view;
				leftButton.onclick = function() { view.goup(); }
				/*
				var goback = alib.dom.createElement("img", leftButton);
				goback.src = '/images/icons/arrow_back_mobile_24.png';
				goback.view = view;
				goback.onclick = function() { view.goup(); }
				*/
			}

			// Title container
			var title = alib.dom.createElement("h1", headerCon);

			if (typeof Ant != "undefined")
				title.innerHTML = view.getTitle();
				//title.innerHTML = Ant.account.companyName;

			// joe: I believe this may be depriacted but needs to be verified
			var conAppTitle = alib.dom.createElement("div", headerCon);
			
			var contentCon = alib.dom.createElement("div", pageCon);
			contentCon.id = path+"_con";
			alib.dom.styleSetClass(contentCon, "viewBody");

			// Used by the AntApp class to set the title of the application
			view.conAppTitle = conAppTitle;
		}
		
		view.con = contentCon;
	}
	else
	{
		view.con = (view.conOuter) ? alib.dom.createElement("div", view.conOuter) : null;
		if (view.con)
			view.con.style.display = 'none';
	}

	this.views[this.views.length] = view;
	return view;
}

/**
 * Resize the active view and it's children
 */
netric.mvc.ViewManager.prototype.resizeActiveView = function()
{
	if (this.currViewName)
	{
		var actView = this.getView(this.currViewName);
		if (actView)
			actView.resize();
	}

}

/**
* Load a view by converting a path to a name
*
* @param {string} path path like my/app/name will load "my" view of this viewManager
*/
netric.mvc.ViewManager.prototype.load = function(path)
{
	this.path = path;
	var postFix = "";
	var nextView = "";

	if (this.path.indexOf("/")!=-1)
	{
		var parts = this.path.split("/");
		this.currViewName = parts[0];
		if (parts.length > 1)
		{
			for (var i = 1; i < parts.length; i++) // Skip of first which is current view
			{
				if (postFix != "")
					postFix += "/";
				postFix += parts[i];
			}
		}
	}
	else
		this.currViewName = path;

	var variable = "";
	var parts = this.currViewName.split(":");
	if (parts.length > 1)
	{
		this.currViewName = parts[0];
		variable = parts[1];
	}

	return this.loadView(this.currViewName, variable, postFix);
}

/**
* Even fires when all views have finished loading
*/
netric.mvc.ViewManager.prototype.onload = function()
{
}

/**
* Get a view by name
*
* @param {string} name unique name of the view to load
*/
netric.mvc.ViewManager.prototype.getView = function(name)
{
	for (var i = 0; i < this.views.length; i++)
	{
		// Find the view by name
		if (this.views[i].name == name)
			return this.views[i];
	}

	return null
}

/**
* Load a view by name
*
* @param {string} name unique name of the view to load
* @param {string}  variable if view has a nane like id:[number] then a variable of number would be passed
* @param {string} postFix  traling URL hash my/app would translate to name = "my" and postFix = "app"
*/
netric.mvc.ViewManager.prototype.loadView = function(name, variable, postFix)
{
	var bFound = false;

	if (!postFix)
		var postFix = "";

	// Loop through child views, hide all but the {name} field
	for (var i = 0; i < this.views.length; i++)
	{
		// Find the view by name
		if (this.views[i].name == name)
		{
			this.views[i].variable = variable;

			// Flag that we found the view
			bFound = true;

			/*
			* If we are a child view and the views are set to single pages only
			* the last view in the list should be viewable and the parent will be hidden
			*/
			if (this.pageViewSingle && this.views[i].parentView)
				this.views[i].parentView.hide();

			if (postFix!="") // This is not the top level view - there are children to display in the path
			{
				/*
				* Check to see if this view has been rendered 
				* already - we only render the first time
				* It is possible in a scenario where a deep url is loaded
				* like /my/path to have 'my' never shown because we jump
				* straight to 'path' but we still need to make sure it is rendered.
				*/
				if (this.views[i].isRendered == false)
				{
					this.views[i].render();
					this.views[i].isRendered = true;
				}

				/*
				* As mentioned above, if we are in singleView mode then 
				* don't show views before the last in the list
				*/
				if (!this.pageViewSingle)
					this.views[i].show();

				// Continue loading the remainder of the path - the child view(s)
				this.views[i].load(postFix);
			}
			else // This is a top-level view meaning there are no children
			{
				this.views[i].show(); // This will also render if the view has not yet been rendered
				this.views[i].hideChildren();
			}

			// Call load callbacks for view
			this.views[i].triggerEvents("load");
		}
		else if (this.pageView) // Hide this view if we are in pageView because it was not selected
		{
			/*
			 * pageView is often used for tab-like behavior where you toggle 
			 * through pages/views at the same level - not affecting parent views
			 */
			this.views[i].hide();
			this.views[i].hideChildren();
		}
	}

	//ALib.m_debug = true;
	//ALib.trace("Showing: " + name + " - " + bFound);
	return bFound;
}

/**
* Change fToggle flag. If true, then only one view is visible at a time. If one is shown, then all other views are looped through and hidden. This is great for tabs.
*
* @param {boolean} fToggle toggle view; default: true
*/
netric.mvc.ViewManager.prototype.setViewsToggle = function(fToggle)
{
	this.pageView = fToggle;
}

/**
* Change pageViewSingle flag. If true, then only one view is visible at a time and the parent view is hidden. This setting is per ViewManager and isolated to one level so you can have: 
* viewRoot (pageView - tabs) -> viewNext (will leave root alone) 
* viewApp (single will hide/replace viewNext)
*
* @param {boolean} fToggle toggle view; default: true
*/
netric.mvc.ViewManager.prototype.setViewsSingle = function(fToggle)
{
	this.pageViewSingle = fToggle;
}

/**
 * Get active views at this manager level only
 *
 * @public
 * @return {AntViews[]}
 */
netric.mvc.ViewManager.prototype.getActiveViews = function()
{
	var ret = new Array();

	for (var i in this.views)
	{
		if (this.views[i].isActive())
			ret.push(this.views[i]);
	}

	return ret;
}
