/**
* @fileOverview Base class used to load widgets into a dashboard
*
* NOTE: This is in development and will eventually replace CWidgetBox but for now
* we are still going to keep using the widget box
*
* @author joe, sky.stebnicki@aereus.com Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Class constructor
 *
 * @param {string} widName The unique name of the widget to load
 */
function AntAppDashWidget(widName)
{
	this.m_id = g_CWidgetBoxId;

	this.m_con = ALib.m_document.createElement("div");
	this.m_con.m_box = this;
	DragAndDrop.registerDropzone(this.m_con, widName);
	if (height)
		alib.dom.styleSet(this.m_con, "height", height);
	alib.dom.styleSetClass(this.m_con, "CWidgetBoxDrSPOff");

	this.m_con.onDragEnter = function(e)
	{
		alib.dom.styleSetClass(this, "CWidgetBoxDrSPOver");
	}

	this.m_con.onDragExit = function(e)
	{
		alib.dom.styleSetClass(this, "CWidgetBoxDrSPOff");
	}

	this.m_con.onDragDrop = function(e)
	{
		var orig_parent = e.root.parentNode;
		var new_parent = this.parentNode;

		if (orig_parent != this)
		{
			this.m_box.onBeforeMove(e, this);

			// Remove this child
			orig_parent.parentNode.removeChild(orig_parent);

			new_parent.insertBefore(orig_parent, this);

			this.m_box.onMoved(orig_parent);
		}

		alib.dom.styleSet(this, "border-top", "5px solid transparent");
	}

	this.onMoved = new Function();
	this.onBeforeMove = new Function();

	g_CWidgetBoxId++;
}

/**
 * Get the main/outer container for this widget
 */
AntAppDashWidget.prototype.getCon = function()
{
	return this.m_con;
}

/**
 * Append this container to a dashboard column
 *
 * @param {DOMElement} e The containing element
 */
AntAppDashWidget.prototype.print = function(e)
{
	e.appendChild(this.m_con);
}

/**
 * Insert this widget before another widget
 */
AntAppDashWidget.prototype.printBefore = function(e, beforeme)
{
	e.insertBefore(this.m_con, beforeme);
}
