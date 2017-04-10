/****************************************************************************
*	
*	Class:		CWidgetBox
*
*	Purpose:	Used to contain widgets for home page and datacenter dashboard
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

g_CWidgetBoxId = 0;

function CWidgetBox(name, height, id)
{
	this.m_id = g_CWidgetBoxId;

	this.m_con = ALib.m_document.createElement("div");
	this.m_con.m_box = this;
	DragAndDrop.registerDropzone(this.m_con, name);
	if (height)
		alib.dom.styleSet(this.m_con, "height", height);
//	alib.dom.styleSet(this.m_con, "border-top", "5px solid transparent");
	alib.dom.styleSetClass(this.m_con, "CWidgetBoxDrSPOff");

	this.m_con.onDragEnter = function(e)
	{
		//alib.dom.styleSet(this, "border-top", "5px solid blue");
		alib.dom.styleSetClass(this, "CWidgetBoxDrSPOver");
	}

	this.m_con.onDragExit = function(e)
	{
		//alib.dom.styleSet(this, "border-top", "5px solid transparent");
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

/*************************************************************************
*	Function:	getCon
*
*	Purpose:	Return container
**************************************************************************/
CWidgetBox.prototype.getCon = function()
{
	return this.m_con;
}

/**
 * Append this container to a dashboard column
 *
 * @param {DOMElement} e The containing element
 */
CWidgetBox.prototype.print = function(e)
{
	e.appendChild(this.m_con);
}

/**
 * Insert this widget before another widget
 */
CWidgetBox.prototype.printBefore = function(e, beforeme)
{
	e.insertBefore(this.m_con, beforeme);
}
