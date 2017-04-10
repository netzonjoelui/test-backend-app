/****************************************************************************
*	
*	Class:		CWidWebsearch
*
*	Purpose:	Websearch application
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidWebsearch()
{
	this.title = "Web Search";
	this.m_container = null;	// Set by calling process

	this.m_menus = new Array();
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidWebsearch.prototype.main = function()
{
	var div = ALib.m_document.createElement("div");
	div.id='websearch_action';
	div.style.height = '0px';
	div.style.width = '0px';

	this.m_container.appendChild(div);

	webform = ALib.m_document.createElement('form');
	webform.name = 'webform';
	webform.id = 'webform_id';
	webform.method = 'get';
	webform.target = '_blank';
	webform.action = 'http://www.google.com/search';

	var table = ALib.m_document.createElement("table");
	table.style.width = '100%';
	var tbody = ALib.m_document.createElement("tbody");
	table.appendChild(tbody);

	var row = ALib.m_document.createElement("tr");
	tbody.appendChild(row);
	
	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.align = 'center';
	td.innerHTML = "<input maxLength='500' id='websearch_input' name='q' value='' class='websearchStyles'>";

	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.align = 'center';
	td.style.width = '63px';
	td.style.paddingLeft = '6px';
	var dm = new CDropdownMenu();
	this.m_menus.push(dm);
	dm.addEntry("Google", "HomeSearchSelect('google')", "/images/icons/google_fav.png");
	dm.addEntry("MSN", "HomeSearchSelect('msn')", "/images/icons/msn_fav.png");
	dm.addEntry("Dogpile", "HomeSearchSelect('dogpile')", "/images/icons/dog_fav.png");
	dm.addEntry("AlltheWeb", "HomeSearchSelect('alltheweb')", "/images/icons/allweb_fav.png");
	dm.addEntry("Yahoo!", "HomeSearchSelect('yahoo')", "/images/icons/yahoo_fav.png");
	td.appendChild(dm.createButtonMenu("Engine"));

	var td = ALib.m_document.createElement("td");
	row.appendChild(td);
	td.align = 'center';
	td.style.width = '50px';
	td.style.paddingLeft = '2px';
	var btn = new CButton("Search", "ALib.m_document.websearch.submit()");
	td.appendChild(btn.getButton());

	webform.appendChild(table);

	this.m_container.appendChild(webform);
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidWebsearch.prototype.exit= function()
{
	this.m_container.innerHTML = "";
}

