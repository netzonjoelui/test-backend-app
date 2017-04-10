/**
 * @fileOverview alib.ui.events Is used to manage and normalize events
 *
 * TODO: This class is a work in progress and is being modeled after the google event handler goog.events in Closure
 * 		 http://closure-library.googlecode.com/svn/docs/closure_goog_events_events.js.html
 *
 * DOM Example
 * <code>
 * 	var button = document.getElementById("exitingbutton");
 * 	alib.events.listen(button, "click", function(e) { alert(e.data.myMessage); ), {myMessage:"What I want to say is hi!"}}
 * </code>
 *
 * Custom Event Example
 * <code>
 * 	var custObj = new Object();
 * 	custObj.propVal = "test";
 * 	custObj.load = function()
 * 	{
 * 		// Load data here
 * 		alib.events.triggerEvent(this, "load");
 * 	}
 * 	alib.events.listen(custObj, "load", function(e) { alert(e.target.propVal); )}
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Create events namespace
 *
 * @object
 */
alib.events = {};

/**
 * Add event listener to an element
 *
 * @var {mixed} obj Either a DOM element or a custom Object to attache event to
 * @var {string} eventName The name of the event to listen for
 * @var {function|Object} callback Can be a function or an object with {context:(object reference), method:(string name)}
 * @var {Object} data Optional data to pass to event
 */
alib.events.listen = function(obj, eventName, callBack, data)
{
	var eventWrapper = new alib.events.EventWrapper(obj, eventName, callBack, data);

	// If the object does not support dispatching events then we will need to handle manually
	if (typeof obj.dispatchEvent == "undefined")
	{
		// Add private events array to custom object
		if (typeof obj.events_ == "undefined")
			obj.events_ = new Array();

		// Initialize event type into the private events array
		if (typeof obj.events_[eventName] == "undefined")
			obj.events_[eventName] = new Array();

		obj.events_[eventName][obj.events_[eventName].length] = eventWrapper;
	}
	else 
	{
		// use native dom
		alib.dom.addEvent(obj, eventName, function(evt) { 
			eventWrapper.fire(evt); 
		});
	}
}

/**
 * Removes an event from the object
 */
alib.events.unlisten = function(obj, eventName, callBack)
{

	// Manually call the event
	if (obj.events_)
	{
		if (eventName)
		{
			if (obj.events_[eventName])
			{
			}
		}
		else
		{
			// remove all events
			obj.events_ = null;
		}
	}
	else
	{
		var ret = alib.dom.removeEvent(obj, eventName, callBack);
	}
}

/**
 * Stop an event from bubbling up the event DOM
 */
alib.events.stop = function(evt)
{
}

/**
 * Manually trigger an event by name if event type is not an included DOM event (like a custom event)
 *
 * This will not be needed for DOM dispatched events so only use it when defining custom events.
 *
 * @var {mixed} obj The context of the event being fired
 * @var {string} eventName The name of the event being fired
 * @var {Object} data Optional data to be passed to the callback in event.data
 */
alib.events.triggerEvent = function(obj, eventName, data)
{
	// Event & UIEvent construcors are replcing createEvent
	if (typeof CustomEvent == "function")
	{
		var evt = new CustomEvent(eventName);
	}
	else
	{
		// Legacy
		var evt = document.createEvent("Event");
		evt.initEvent(eventName, true, true); 
	}

	// Add custom data
	evt.data = data || {};

	if (!evt.type)
		evt.type = eventName;

	if (!evt.target)
		evt.target = obj;

	// Dispatch a DOM event
	if (obj.dispatchEvent)
	{
		obj.dispatchEvent(evt);
	}
	else
	{
		// Manually call the event
		if (obj.events_)
		{
			if (obj.events_[eventName])
			{
				for (var i in obj.events_[eventName])
				{
					obj.events_[eventName][i].fire(evt);
				}
			}
		}
	}
}
