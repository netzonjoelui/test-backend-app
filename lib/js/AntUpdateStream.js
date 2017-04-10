/**
 * @fileOverview This client handles listening to the server for stream updates
 *
 * It is desinged to eventually use Websockets, but for now relies on "long poll:
 * with ajax to simulate real-time two-way communication.
 *
 * Clients can listen to the stream for updates by adding an listen event for the
 * type they are looking for.
 *
 * Example of listening for an event of type 'chat'
 * <code>
 * var updateStream = new AntUpdateStream();
 * alib.events.listen(updateStream, "chat", function(evt) { alert(evt.data.friendName); });
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntUpdateStream
 *
 * @constructor
 */
Ant.UpdateStream = function() {
	// Begin polling server for updates
	this.getUpdates();
}

/**
 * Poll server for updates
 */
Ant.UpdateStream.prototype.getUpdates = function()
{
	// Poll the server until we get data or timeout
	var xhr = new alib.net.Xhr();

	// Retrieve results
	alib.events.listen(xhr, "load", function(evt) { 

		var updates = this.getResponse();

		if (updates.length)
		{
			// For each update trigger an event for listeners
			for (var i in updates)
			{
				try {
					alib.events.triggerEvent(Ant.getUpdateStream(), updates[i].type, updates[i].data);
				}
				catch (e) { alert(e); }
			}
		}
		
		// Load again in 1 second
		setTimeout(function() { Ant.getUpdateStream().getUpdates(); }, 1000);
	});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
		// Try again in 3 seconds
		setTimeout(function() { Ant.getUpdateStream().getUpdates(); }, 3000);
	});

	xhr.send("/controller/User/getUpdateStream");
}

/**
 * Poll server for updates
 *
 * @param {string} evntName The name of the event
 * @param {Function} callback The callback function to call when event is triggered
 * @param {Object} data Optional data to foward
 */
Ant.UpdateStream.prototype.listen = function(evntName, callback, data)
{
	var fwdData = data || new Object();

	alib.events.listen(this, evntName, callback, fwdData);
}
