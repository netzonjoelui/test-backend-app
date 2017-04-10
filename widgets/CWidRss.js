/****************************************************************************
*	
*	Class:		CWidRss
*
*	Purpose:	Rss Widget will probably be used more than once
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidRss()
{
	this.title = "RSS Reader";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Context menu set by calling process
	this.m_data = null;			// If data is set, this will be passed by parent process
    this.appNavname = null;
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidRss.prototype.main = function()
{
	alib.dom.styleSet(this.m_container, "padding", "3px");

	if (!this.m_data)
	{
		var dv = ALib.m_document.createElement("div");
		dv.m_widcls = this;
		dv.onclick = function() { this.m_widcls.setData(); };
		alib.dom.styleSet(dv, "cursor", "pointer");
		dv.innerHTML = "Click here to set RSS url";
		this.m_container.appendChild(dv);
	}
	else
		this.loadRss();
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidRss.prototype.exit= function()
{
	this.m_container.innerHTML = "";
}

/*************************************************************************
*	Function:	loadRss
*
*	Purpose:	Load contents of RSS file
**************************************************************************/
CWidRss.prototype.loadRss = function()
{
	var ajax = new CAjax();
	ajax.m_widcls = this;
	// Set callback once xml is loaded
	ajax.onload = function(root)
	{
		this.m_widcls.m_container.innerHTML = "";

		var num = root.getNumChildren();
		for (var i = 0; i < num; i++)
		{
			var channel = root.getChildNode(i);
			var channel_title = "";

			var tbl = ALib.m_document.createElement("table");
			var tbl_body = ALib.m_document.createElement("tbody");
			tbl.appendChild(tbl_body);

			for (var j = 0; j < channel.getNumChildren(); j++)
			{
				var node = channel.getChildNode(j);

				if ("title" == node.m_name)
					channel_title = unescape(node.m_text);

				if ("item" == node.m_name)
				{
					var title = node.getChildNodeValByName("title");
					var link = node.getChildNodeValByName("link");
					var desc = node.getChildNodeValByName("description");

					// Add row for icon and title
					var tr = ALib.m_document.createElement("tr");
					tbl_body.appendChild(tr);

					var td = ALib.m_document.createElement("td");
					tr.appendChild(td);
					td.innerHTML = "<img src='/images/icons/rss_link2.gif' border='0'>";

					var td = ALib.m_document.createElement("td");
					tr.appendChild(td);
					td.innerHTML = "<a target='_blank' href='"+unescape(link)+"'>"+unescape(title)+"</a>";

					// Add row for description
					var tr = ALib.m_document.createElement("tr");
					tbl_body.appendChild(tr);

					var td = ALib.m_document.createElement("td");
					tr.appendChild(td);

					var td = ALib.m_document.createElement("td");
					tr.appendChild(td);
					td.innerHTML = unescape(desc);
				}
			}

			if (channel_title)
				this.m_widcls.m_cct.setTitle(channel_title);
			this.m_widcls.m_container.appendChild(tbl);
		}
	};
	// Get xml file	
	ajax.exec("/widgets/xml_rss_reader.php?url=" + escape(this.m_data) + "&id=" + this.m_id);
}

/*************************************************************************
*	Function:	setData
*
*	Purpose:	Change URL(data) for this widget
**************************************************************************/
CWidRss.prototype.setData = function()
{
	var url = (this.m_data) ? this.m_data : 'http://examplefeed.com/path/to/rss.xml';
	var data = prompt('Please enter an rss url', url);
	this.m_container.innerHTML = "Setting url, please wait";
	var cls = this;
    
    var args = new Array();
        
    args[0] = ['id', cls.m_id];
    args[1] = ['data', data];
    args[2] = ['appNavname', cls.appNavname];
                                
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.m_data = unescape(ret);
        this.cbData.cls.loadRss();
    };
    ajax.exec("/controller/Application/setRssData", args);
}
