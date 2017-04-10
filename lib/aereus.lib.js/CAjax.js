/*======================================================================================
	
	Module:		CAjax

	Purpose:	Handle remote XML documents

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.
	
	Usage:		// XML
				ajax = new CAjax('xml');
				ajax.onload = function(root)
				{
					// Get first node
					var num = root.getNumChildren();
					for (i = 0; i < num; i++)
					{
						// Get child nodes
						var model = root.getChildNode(i);
						if (model.m_name == "mynode")
						{
							document.write(model.m_name);
							document.write(model.m_text);
						}
					}
				};
				ajax.exec("/path/to/xml.xml");

				// HTML
				ajax = new CAjax('html');
				ajax.onload = function(data)
				{
					alert("This is my html: " + data);
				};
				ajax.exec("/path/to/html.html");

======================================================================================*/

// Define constants
// -----------------------------------------------------------
var AJAX_POST = 1;
var AJAX_GET = 2;

// Node Types
var AJAX_NODE_TEXT = 3;
var AJAX_NODE_HTML = 1;

// Debugging
var AJAX_TRACE_RESPONSE = false;


/***********************************************************************************
 *
 *	Class: 		CAjax
 *
 *	Purpose:	Encapsulate AJAX functionality
 *
 ***********************************************************************************/
function CAjax(dataType)
{
	this.m_xmlLocal = null;
	this.m_response = null;
	//this.m_firstNode = null;
	this.m_method = AJAX_POST;
	this.dataType = (dataType) ? dataType : "xml";
	this.cbData = new Object();
	this.debug = false;
	
	/*
	if (window.XMLHttpRequest) 
	{
		this.m_xmlLocal = new XMLHttpRequest();
	}
	else
	{
		var msxmlhttp = new Array('Msxml2.XMLHTTP.5.0',
								  'Msxml2.XMLHTTP.4.0',
								  'Msxml2.XMLHTTP.3.0',
								  'Msxml2.XMLHTTP',
								  'Microsoft.XMLHTTP');

		for (var i = 0; i < msxmlhttp.length; i++) 
		{
			try 
			{
				this.m_xmlLocal = new ActiveXObject(msxmlhttp[i]);
			} 
			catch (e) 
			{
				this.m_xmlLocal = null;
			}
		}
	}
	*/

	/**
	 * Alias to Xhr object
	 *
	 * @var {alib.net.Xhr}
	 */
	this.Xhr = new alib.net.Xhr();
}

/**
 * Alias to new alib.net.Xhr.send function
 *
 * @param string url The remote url
 * @param array args Array to convert to object to send to Xhr
 * @param bool asycn If true then this is asycn (default) if false, then sync
 * @return Null if async, data if sync
 */
CAjax.prototype.exec = function(url, args, async)
{
	this.async = (async != null) ? async : true;
	var post_data = null;
	//var xmlLocal = this.m_xmlLocal;
	this.wasaborted = false;
	var objref = this;

	this.loading = true;
	this.Xhr.setAsync(this.async);
	this.Xhr.setReturnType(this.dataType);

    // If this is a syncronus request we don't need callback
    if (this.async == true)
    {
		// Listen for load event
		alib.events.listen(this.Xhr, "load", function(evt) { 

			// Make sure object is still loaded, it may have been destroyed
			if (evt.data.ajaxCls.dataType == "xml")
				evt.data.ajaxCls.onload(evt.data.ajaxCls.parseXml(this.getResponseXML()));
			else
				evt.data.ajaxCls.onload(this.getResponse());

		}, {ajaxCls:this});

		alib.events.listen(this.Xhr, "error", function(evt) { 
			evt.data.ajaxCls.onload(false);
		}, {ajaxCls:this});
	}

	// Set data
	var data = "";

	// If args is already a string, then no need to loop thru it. Instead assign directly as our data
	if (typeof args === 'string') {
		data = args;
	} if (typeof args != "undefined" && args!=null){
		/*
		var data = new Object();
		if (args.length)
		{
			// Arguments are pass as [[name, value]]
			for (i = 0; i < args.length; i++)
				data[args[i][0]] = args[i][1];
		}
		*/

		numargs = args.length;
		if (numargs)
		{
			// Arguments are pass as {name, value}
			for (i = 0; i < numargs; i++)
			{
				if (data) 
					data += "&";
				else
					data = "";

				data += args[i][0] + "=";
				if (typeof args[i][1] != "undefined" && args[i][1] != null)
				 data += escape_utf8(args[i][1]);
			}
		}
	}

	this.Xhr.send(url, ((this.m_method == AJAX_GET) ? "GET" : "POST"), data);

	if (this.async == false)
	{
		switch (this.dataType)
		{
		case "xml":
			return this.parseXml(this.Xhr.getResponseXML());
		case "json":
			if (this.Xhr.getResponseText())
			{
				try
				{
					if (typeof JSON != "undefined")
						return JSON.parse(this.Xhr.getResponseText());
					else
						return eval('(' + this.Xhr.getResponseText() + ')');
				}
				catch (e)
				{
					if (AJAX_TRACE_RESPONSE || alib.m_debug)
					{
						if (typeof JSON == "undefined")
							ALib.trace("Ajax Error: JSON was undefined: <pre>" + this.Xhr.getResponseText()+"</pre>");
						else
							ALib.trace("Ajax Error: Problem parsing JSON object with eval: <pre>" + this.Xhr.getResponseText()+"</pre>");
					}
				}
			}
			break;
		default:
			return this.Xhr.getResponseText();
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	exec
 *
 *	Purpose:	Send request to server using http get
 *
 *	Arguements:	url   - string: path to xml document
 *				async - bool: defaults to true. Be careful if set to false, it can
 *						hang the browser until the xml doc is loaded. Users generally
 *						don't like that too much.
 *
 ***********************************************************************************/
CAjax.prototype.execOld = function(url, args, async)
{
	this.async = (async != null) ? async : true;
	var post_data = null;
	var xmlLocal = this.m_xmlLocal;
	this.wasaborted = false;
	var objref = this;

	//ALib.m_debug = true;
	//ALib.trace("AJAX GET: " + url);
    
    // If this is a syncronus request we don't need callback
    if (this.async == true)
    {
	    //this.m_xmlLocal.onreadystatechange = inlineLoaded;
		this.m_xmlLocal.ajaxClsRef = this;
	    this.m_xmlLocal.onreadystatechange = function() { 
			// May not be set anymore if the ajax object got distroyed
			if (this.ajaxClsRef)
				this.ajaxClsRef.readyStateChange(); 
		}
	}

	this.loading = true;

	if (this.m_method == AJAX_GET)
	{
		this.m_xmlLocal.open("GET", url, this.async);
	}
	else if (this.m_method == AJAX_POST)
	{
		// Get arguments
		var numargs = 0;
		if (typeof args != "undefined" && args!=null)
		{
			numargs = args.length;
			if (numargs)
			{
				// Arguments are pass as {name, value}
				for (i = 0; i < numargs; i++)
				{
					if (post_data) 
						post_data += "&";
					else
						post_data = "";

					post_data += args[i][0] + "=";
					if (typeof args[i][1] != "undefined" && args[i][1] != null)
					 post_data += escape_utf8(args[i][1]);
				}
			}
		}

		this.m_xmlLocal.open("POST", url, this.async);
		//Send the proper header information along with the request
		this.m_xmlLocal.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		//this.m_xmlLocal.setRequestHeader("Content-length", post_data.length);
		//this.m_xmlLocal.setRequestHeader("Connection", "close");
	}

	this.m_xmlLocal.send(post_data);

    // If this is a syncronus request we don't need callback, just process data
    if (this.async == false)
    {
        return this.readyStateChange();
    }
}

/***********************************************************************************
 *
 *	Function: 	readyStateChange
 *
 *	Purpose:	Private function that handles readystate change for request.
 *
 ***********************************************************************************/
CAjax.prototype.readyStateChange = function ()
{
	if (this.m_xmlLocal && this.m_xmlLocal.readyState == 4) 
	{
		if (this.m_xmlLocal.status == 200) 
		{
			var data = null;

			if (AJAX_TRACE_RESPONSE)
				ALib.trace("Response: <pre>" + this.m_xmlLocal.responseText+"</pre>");

			switch (this.dataType)
			{
				case 'xml':

					// If a valid xml document has not been loaded then exit gracefully
					if (this.m_xmlLocal.responseXML == null)
					{
						if (ALib.m_debug == true)
							ALib.trace("XML Failed: " + this.m_xmlLocal.responseText);
					}
					else if (this.m_xmlLocal.responseXML.documentElement == null)
					{
						if (ALib.m_debug == true)
							ALib.trace("XML Failed: " + this.m_xmlLocal.responseText);
					}
					else
					{
						data = this.parseXml(this.m_xmlLocal.responseXML);
						//data = this.m_firstNode;
					}
					break;
				case 'json':
					if (this.m_xmlLocal.responseText)
					{
						try
						{
							if (typeof JSON != "undefined")
								data = JSON.parse(this.m_xmlLocal.responseText);
							else
								data = eval('(' + this.m_xmlLocal.responseText + ')');
						}
						catch (e)
						{
							if (AJAX_TRACE_RESPONSE || alib.m_debug)
							{
								if (typeof JSON == "undefined")
									ALib.trace("Ajax Error: JSON was undefined: <pre>" + this.m_xmlLocal.responseText+"</pre>");
								else
									ALib.trace("Ajax Error: Problem parsing JSON object with eval: <pre>" + this.m_xmlLocal.responseText+"</pre>");
							}
						}
					}
					break;
				case 'html':
				case 'text':
					data = this.m_xmlLocal.responseText;
					break;
				case 'script':
					data = this.m_xmlLocal.responseText;
					break;
			}
			
			// Populate text
			this.responseText = this.m_xmlLocal.responseText;

			this.loading = false;

			// Call user defined loaded
			if (!this.wasaborted)
				this.onload(data);

			if (!this.async)
				return data;
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	parseXml
 *
 *	Purpose:	Private function that parses the xml document once it is loaded
 *
 ***********************************************************************************/
CAjax.prototype.parseXml = function (responseXML)
{
	// Get the parent node
	this.m_response  = responseXML.documentElement;
	
	var rootNode = new CXml("root", "");
	rootNode.m_xmlcld = this.m_response;

	// Parse tree
	this.parseNodes(rootNode, this.m_response);
	return rootNode;
}

/***********************************************************************************
 *
 *	Function: 	abort
 *
 *	Purpose:	Stop Processing
 *
 ***********************************************************************************/
CAjax.prototype.abort = function ()
{
	/*
	try
	{
		this.m_xmlLocal.abort();
		this.wasaborted = true;
	}
	catch (e) {}
	*/
	try
	{
		this.Xhr.abort();
	}
	catch (e) {}
}

/***********************************************************************************
 *
 *	Function: 	parseNodes
 *
 *	Purpose:	Private function that parses each xml node
 *
 *	Arguements:	ajax_node   - CXml: Branch to parse
 *				xml_child	- xml_node: xml object node to copy in CXml
 *
 ***********************************************************************************/
CAjax.prototype.parseNodes = function (ajax_node, xml_child)
{
	if (!ajax_node || !xml_child)
		return 0;
	
	var iNumSubNodes = ajax_node.m_children.length;
	var children  = xml_child.childNodes;
	var iNewIndex = 0;

	if (children)
	{
		var num = children.length;
		for(var i = 0; i < num; i++)
		{
			var child = children[i];

			// Element Node
			if (child.nodeType == AJAX_NODE_HTML)
			{
				ajax_node.m_children[iNumSubNodes + iNewIndex] = new CXml(child.nodeName, "");
				ajax_node.m_children[iNumSubNodes + iNewIndex].m_xmlcld = child;

				if (child.childNodes && child.childNodes.length)
					this.parseNodes(ajax_node.m_children[iNumSubNodes + iNewIndex], child);

				iNewIndex++;
			}

			// Text Node
			if (child.nodeType == AJAX_NODE_TEXT)
			{
				ajax_node.m_text += child.nodeValue;
			}
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	onload
 *
 *	Purpose:	This function should be redefined by the public calling procedure.
 *
 ***********************************************************************************/
CAjax.prototype.onload = function(data)
{
}
