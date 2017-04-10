/****************************************************************************
*	
*	Class:		CWidRssManager
*
*	Purpose:	Bookmarks widget
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidRssManager()
{
	this.title = "RSS Manager";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Context menu set by calling process
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidRssManager.prototype.main = function()
{
	/*
	this.m_dm.addEntry('Add RSS Feed', "document.getElementById('rss_favorites').ToggleEdit()", "/images/themes/"+Ant.m_theme+"/icons/taskIcon.gif");
	*/

	mdiv = ALib.m_document.createElement("div");
	mdiv.id = "wBookmarks";
	mdiv.innerHTML = '<IFRAME ALLOWTRANSPARENCY="true" SRC="/rss_favorites.awp" WIDTH="100%" HEIGHT="50" frameborder="0" '
					 	+ 'name="rss_favorites" id="rss_favorites"></IFRAME>';
	this.m_container.appendChild(mdiv);
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidRssManager.prototype.exit= function()
{
	this.m_container.innerHTML = "";
}
