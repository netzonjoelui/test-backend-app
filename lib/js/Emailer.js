/**
 * @fileOverview Plugin used for handling composing new emails
 *
 * Example of composing a new email
 * <code>
 * Ant.Emailer.compose("my@address.com", { subject: "My Test Subject" });
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */

/**
 * Static namespace
 */
Ant.Emailer = {};

/**
 * Flag to determine if settings have been retrieved
 *
 * @private
 * @type {bool}
 */
Ant.Emailer.settingsRetrieved_ = false;

/**
 * Flag to determine if we are using netric to compose emails or the 'mailto' client
 *
 * @private
 * @type {bool}
 */
Ant.Emailer.composeNetric_ = false;

/**
 * Flag to determine if we should be logging all mail (add bcc to mailto)
 *
 * @private
 * @type {bool}
 */
Ant.Emailer.logAllMail_ = false;


/**
 * Compose a new email
 *
 * @param {string|array} to Can either be a single address, or an array of addresses
 * @param {Object} opt Additional options like cc, bcc, subject...
 */
Ant.Emailer.compose = function(to, opt) {

	var options = opt || new Object();
	var sendTo = null;

	if (typeof to === "string")
	{
		sendTo = to;
	}
	else if (typeof to != "undefined" || to.length > 0)
	{
		for (var i in to)
		{
			sendTo += (sendTo) ? "," + to[i] : to[i];
		}
	}
	
	if (!this.settingsRetrieved_)
	{
		this.getSettings_(sendTo, options);
		return;
	}

	if (this.composeNetric_)
	{
		var args = new Array();
		if (sendTo)
			args.push(["send_to", sendTo]);
		else if (options.to)
			args.push(["send_to", options.to]);
			
		if (options.subject)
			args.push(["subject", options.subject])
		if (options.cc)
			args.push(["cc", options.cc])
		if (options.bcc)
			args.push(["bcc", options.bcc])
		if (options.body)
			args.push(["body", options.body])

		// No need to log because that will happen on the backend
		loadObjectForm("email_message", null, null, null, args);
	}
	else
	{
		// Use mailto
		// Check if we have a reference we can use to log this email
		if (this.logAllMail_)
		{
			if (options.obj_type && options.oid)
			{
				options.bcc = Ant.account.name + "-act-" + options.obj_type + "." + options.oid + "@" + Ant.settings.email.dropbox_catchall;
			}
		}

		var cmpStr = "mailto:";

		if (sendTo)
			cmpStr += sendTo;
		else if (options.to)
			cmpStr += options.to;

		cmpStr += "?fv=1";

		if (options.subject)
			cmpStr += "&subject=" + escape(options.subject);
		if (options.cc)
			cmpStr += "&cc=" + escape(options.cc);
		if (options.bcc)
			cmpStr += "&bcc=" + escape(options.bcc);
		if (options.body)
			cmpStr += "&body=" + escape(options.body);

		window.location.href = cmpStr;
	}
}

/**
 * Get and cache composer settings
 *
 * @param {string|array} to Optional queued compose request to call once settings are loaded
 * @param {Object} opt Optional options to send with compose request
 */
Ant.Emailer.getSettings_ = function(to, opt) {
	// Poll the server until we get data or timeout
	var xhr = new alib.net.Xhr();

	// Retrieve results
	alib.events.listen(xhr, "load", function(evt) { 

		var settings = this.getResponse();

		if (!settings["error"])
		{
			if (settings["email/compose_netric"])
				evt.data.cls.composeNetric_ = (settings["email/compose_netric"] == 1) ? true : false;

			if (settings["email/log_allmail"])
				evt.data.cls.logAllMail_ = (settings["email/log_allmail"] == 1) ? true : false;
		}

		evt.data.cls.settingsRetrieved_ = true;

		Ant.Emailer.compose(to, opt);
	}, {cls:this});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
		// Display error?
	});

	xhr.send("/controller/Admin/getSetting", "POST", {get:["email/compose_netric", "email/log_allmail"]});
}
