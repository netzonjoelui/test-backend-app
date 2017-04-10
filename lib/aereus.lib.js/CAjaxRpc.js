/*======================================================================================
	
	Module:		CAjaxRpc

	Purpose:	Execute remote procedures and return value via ajax

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.
	
	Usage:		// Create ajaxrpc object
				var rpc = new CAjaxRpc("/path/to/xml.xml", "function_name", 
									   [["argument_name", "value"]], callback_function, cb_args);

======================================================================================*/

// Define globals
// -----------------------------------------------------------
var g_CAjaxRpc = new Array();


/***********************************************************************
*	Class:		CAjaxRpc
*
*	Purpose:	Create CAjaxRpc class
*
*	Arguments:	url			- string: path to server file
*				f_name 		- string: name of function to process on server
*				args		- array[][]: arguments to send to server.
*						      Sent via get using name=value
*				finished_cb	- string or function ref: function to call
*							  with return value after server has processed
*							  request. Define: function name(retval);
*
************************************************************************/
function CAjaxRpc(url, f_name, args, finished_cb, cb_args, method, async, dataType)
{
	var send_method = (method) ? method : AJAX_GET;
    var is_async = (async != null) ? async : true;
	var resp_type = (dataType) ? dataType : "xml";

	// Get last index
	var ind = g_CAjaxRpc.length;
	g_CAjaxRpc[ind] = new CAjax(resp_type);
	g_CAjaxRpc[ind].m_method = send_method;

	if (typeof cb_args != "undefined")
		g_CAjaxRpc[ind].m_cb_args = cb_args;
	else
		g_CAjaxRpc[ind].m_cb_args = null;

	g_CAjaxRpc[ind].onload = function(root)
	{
		var retval = null;

		// The result will be held in a variable called 'retval'
		if (root)
		{
			if (this.dataType == "xml")
			{
				var num = root.getNumChildren();
				if (num)
				{
					for (i = 0; i < num; i++)
					{
						var child = root.getChildNode(i);
						if (child.m_name == "retval")
						{
							if (child.m_text)
								retval = unescape_utf8(child.m_text);
						}

						if (child.m_name == "message")
						{
							if (child.m_text)
								this.message = unescape_utf8(child.m_text);
						}
					}
					
				}
			}
			else // All other formats just return object/string
			{
				retval = root;
			}


			if (this.cb_function)
			{
				try
				{
					if (typeof this.cb_function == "string")
					{
						if (this.m_cb_args)
						{
							var passargs = "\"" + retval + "\"";
							for (var j = 0; j < m_cb_args.length; j++)
							{
								passargs += ", \"" + m_cb_args[j] + "\"";
							}

							eval(this.cb_function + "(" + passargs + ")");
						}
						else
						{
							eval(this.cb_function + "(\"" + retval + "\")");
						}
					}
					else
					{
						if (this.m_cb_args)
						{
							switch (this.m_cb_args.length)
							{
							case 1:
								this.cb_function(retval, this.m_cb_args[0]);
								break;
							case 2:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1]);
								break;
							case 3:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], this.m_cb_args[2]);
								break;
							case 4:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3]);
								break;
							case 5:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4]);
								break;
							case 6:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
												 this.m_cb_args[5]);
								break;
							case 7:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
												 this.m_cb_args[5], this.m_cb_args[6]);
								break;
							case 8:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
												 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7]);
								break;
							case 9:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
												 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7],
												 this.m_cb_args[8]);
								break;
							case 10:
								this.cb_function(retval, this.m_cb_args[0], this.m_cb_args[1], 
												 this.m_cb_args[2], this.m_cb_args[3], this.m_cb_args[4],
												 this.m_cb_args[5], this.m_cb_args[6], this.m_cb_args[7],
												 this.m_cb_args[8], this.m_cb_args[9]);
								break;
							}
						}
						else
						{
							this.cb_function(retval);
						}
					}
				}
				catch (e) { alert(e); }
			}

			CAjaxRpcCleanup(this);
		}
	};

	var exec_url = url;
	exec_url += "?function=" + f_name;
	
	// Get callback (optional)
	if (finished_cb)
		g_CAjaxRpc[ind].cb_function = finished_cb;
	
	if (send_method == AJAX_POST && typeof args != "undefined")
	{
		g_CAjaxRpc[ind].exec(exec_url, args);
	}
	else
	{
		if (typeof args != "undefined" && args!=null)
		{
			var numargs = args.length;
			if (numargs)
			{
				// Arguments are pass as {name, value}
				for (i = 0; i < numargs; i++)
				{
					var val = (args[i][1]) ? escape_utf8(args[i][1]) : "";
					exec_url += "&" + args[i][0] + "=" + val;
				}
			}
		}
		g_CAjaxRpc[ind].exec(exec_url);
	}
}


/***********************************************************************
*	Function:	CAjaxRpcCleanup
*
*	Purpose:	Removes reference to ajax from global array
*
************************************************************************/
function CAjaxRpcCleanup(ref)
{
	var num = g_CAjaxRpc.length;
	for (i = 0; i < num; i++)
	{
		if (g_CAjaxRpc[i] == ref)
		{
			g_CAjaxRpc[i] = null;
		}
	}
}

