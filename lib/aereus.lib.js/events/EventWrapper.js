/**
 * @fileoverview Event callback wrapper used to pass additional data and normalize processing
 */

/**
 * Class constructor
 *
 * @constructor
 * @var {mixed} obj Either a DOM element or a custom Object to attache event to
 * @var {string} eventName The name of the event to listen for
 * @var {function|Object} callback Can be a function or an object with {context:(object reference), method:(string name)}
 * @var {Object} data Optional data to pass to event
 */
alib.events.EventWrapper = function(obj, eventName, callback, data)
{
	/**
	 * Object being listened to
	 *
	 * @var {DOMElement|Object}
	 */
	this.target = obj;

	/**
	 * The event being listened for
	 *
	 * @var {string}
	 */
	this.eventName = eventName;

	/**
	 * Callback function or object
	 *
	 * This can either be a funciton or a class with properties context (class) and method (string)
	 */
	this.callback = callback;

	/**
	 * Optional additional data to be passed to the event.data property
	 *
	 * @var {Object}
	 */
	this.data = data || {};
}

/**
 * Fire the event and call the callback
 *
 * @var {Event} evt An event object
 */
alib.events.EventWrapper.prototype.fire = function(evt)
{
	var evt = evt || window.event; // fix IE event handling

	// Now forward additional data
	// -----------------------------------------
	if (evt.data)
	{
		// If data already exists then add this.data properties
		for (var prop in this.data)
		{
			evt.data[prop] = this.data[prop];
		}
	}
	else
	{
		evt.data = this.data;
	}

	// Call the callback
	// -----------------------------------------
	if (typeof this.callback == "function")
	{
		// Put funnction in the contxt of the object so 'this' refers to the object
		this.target.eventCallback_ = this.callback;
		this.target.eventCallback_(evt);
		this.target.eventCallback_ = null;;
		//this.callback(evt);
	}
	else if (this.callback.context && this.callback.method)
	{
		this.callback.context[this.callback.method](evt);
	}
}
