/****************************************************************************
*	
*	Class:		CWidSettings
*
*	Purpose:	Settings widget
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidSettings()
{
	this.title = "Settings";
	this.m_container = null;	// Set by calling process
	this.m_id = null;			// Set by calling process
	this.m_dashclass = null;	// Set my calling process
    this.appNavname = null;
    
	this.m_menus = new Array();
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidSettings.prototype.main = function()
{    
	//alib.dom.styleSetClass(this.m_container, "CWidContentTableBodyPadding");

	var table = ALib.m_document.createElement("table");
	var tbody = ALib.m_document.createElement("tbody");
	table.appendChild(tbody);
	
	// Theme Row
	// ------------------------------------------------------------------------------------------
	var row = ALib.m_document.createElement("tr");
	tbody.appendChild(row);
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "<img src='/images/icons/settings_16.png' border='0' />";
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "Current Theme:";
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	var dm = new CDropdownMenu();
	this.m_menus.push(dm);
	var ajax = new CAjax();
	ajax.m_dm = dm;
	ajax.m_td = td;
	// Set callback once xml is loaded
	ajax.onload = function(root)
	{
		// Get first node
		var num = root.getNumChildren();
		for (var j = 0; j < num; j++)
		{
			var theme = root.getChildNode(j);
			var title = unescape(theme.m_text);
			var id = theme.getAttribute("id");

			this.m_dm.addEntry(title, "location='main?change_theme=" + id + "'", 
							   "/images/icons/circle_blue.png")
		}

	};
	// Get xml file	
	ajax.exec("/widgets/xml_settings.php?function=get_themes");
	td.appendChild(dm.createButtonMenu(Ant.m_themeTitle));
	
	// ANTClient
	// ------------------------------------------------------------------------------------------
	var row = ALib.m_document.createElement("tr");
	tbody.appendChild(row);
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "<img src='/images/icons/windows_16.png' border='0' />";
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "Desktop Client";

	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.innerHTML = "<a href='http://www.aereus.com/downloads/antclient_setup.exe'>Download ANTClient</a>";

	// Add table to widget container
	// ------------------------------------------------------------------------------------------
	this.m_container.appendChild(table);
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidSettings.prototype.exit= function()
{
	for (var i = 0; i < this.m_menus.length; i++)
	{
		this.m_menus[i].destroyMenu();
	}

	this.m_container.innerHTML = "";
}

