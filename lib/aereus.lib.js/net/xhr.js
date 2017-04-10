/**
 * @fileOverview alib.net.xhr Wrapper for XMLHttpRequest
 *
 * Right now this mostly encapsulates the jquery implementation of ajax but is
 * desinged to later become independent or even use another library. For that reason
 * the $.ajax should never be returned or exposed.
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Class for handling XMLHttpRequests
 *
 * @constructor
 */
alib.net.Xhr = function() 
{
}

/**
 * Handle to ajax xhr
 *
 * @private
 * @type {$.ajax}
 */
alib.net.Xhr.prototype.ajax_ = null;

/**
 * What kind of data is being returned
 *
 * Can be xml, json, script, text, or html
 *
 * @private
 * @type {string}
 */
alib.net.Xhr.prototype.returnType_ = "json";

/**
 * Determine whether or not we will send async or hang the UI until request returns (yikes)
 *
 * @private
 * @type {bool}
 */
alib.net.Xhr.prototype.isAsync_ = true;

/**
 * Flag to inidicate if request is in progress
 *
 * @private
 * @type {bool}
 */
alib.net.Xhr.prototype.isInProgress_ = false;

/**
 * Number of seconds before the request times out
 *
 * 0 means no timeout
 *
 * @private
 * @type {int}
 */
alib.net.Xhr.prototype.timeoutInterval_ = 0;

/**
 * Buffer for response
 *
 * @private
 * @type {bool}
 */
alib.net.Xhr.prototype.response_ = null;

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
alib.net.Xhr.send = function(url, opt_callback, opt_method, opt_content, opt_timeoutInterval) 
{
	// Set defaults
	if (typeof opt_method == "undefined")
		opt_method = "GET";
	if (typeof opt_content == "undefined")
		opt_content = null;

	// Crete new Xhr instance and send
	var xhr = new alib.net.Xhr();
	if (opt_callback)
		alib.events.listen(xhr, "load", function(evt) { evt.data.cb(this.getResponse); }, {cb:opt_callback});
	if (opt_timeoutInterval)
		xhr.setTimeoutInterval(opt_timeoutInterval);
	xhr.send(url, opt_method, opt_content);
	return xhr;
};

/**
 * Instance send that actually uses XMLHttpRequest to make a server call.
 *
 * @param {string|goog.Uri} urlPath Uri to make request to.
 * @param {string=} opt_method Send method, default: GET.
 * @param {Array|Object|string=} opt_content Body data.
 */
alib.net.Xhr.prototype.send = function(urlPath, opt_method, opt_content) 
{
	var method = opt_method || "GET";
	var xhr = this;
	
	// Indicate a request is in progress
	xhr.isInProgress_ = true;

	// Check if we need to put a prefix on the request
	if (alib.net.prefixHttp != "")
		urlPath = alib.net.prefixHttp + urlPath;

	this.ajax_ = $.ajax({
        type: method,
        url: urlPath,
		dataType: this.returnType_,
        async: this.isAsync_,
        cache: false,
        data: opt_content || null,
		success: function(data) {
			// Store response in buffer
			xhr.response_ = data;

			// Trigger load events for any listeners
			alib.events.triggerEvent(xhr, "load");

			// No longer in progress of course
			xhr.isInProgress_ = false;
		},
		error: function(jqXHR, textStatus, errorThrown) {
			// Clear response
			xhr.response_ = null;

			// Trigger load events for any listeners
			alib.events.triggerEvent(xhr, "error");

			// No longer in progress of course
			xhr.isInProgress_ = false;
		}
	});
}

/**
 * Set what kind of data is being returned
 *
 * @param {string} type Can be "xml", "json", "script", "text", or "html"
 */
alib.net.Xhr.prototype.setReturnType = function(type)
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
alib.net.Xhr.prototype.setAsync = function(async)
{
	this.isAsync_ = async;
}

/**
 * Sets the number of seconds before timeout
 *
 * @param {int} seconds Number of seconds
 */
alib.net.Xhr.prototype.setTimeoutInterval = function(seconds)
{
	this.timeoutInterval_ = seconds;
}

/**
 * Abort the request
 */
alib.net.Xhr.prototype.abort = function()
{
	if (this.ajax_)
		this.ajax_.abort();
}

/**
 * Check if a request is in progress
 *
 * @return bool True if a request is in progress
 */
alib.net.Xhr.prototype.isInProgress = function()
{
	return this.isInProgress_;
}

/**
 * Get response text from xhr object
 */
alib.net.Xhr.prototype.getResponseText = function()
{
	return this.ajax_.responseText;
}

/**
 * Get response text from xhr object
 */
alib.net.Xhr.prototype.getResponseXML = function()
{
	return this.ajax_.responseXML;
}

/**
 * Get the parsed response
 */
alib.net.Xhr.prototype.getResponse = function()
{
	return this.response_;
}
