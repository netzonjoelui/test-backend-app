/****************************************************************************
*	
*	Class:		CWindowFrame
*
*	Purpose:	Window Frame
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWindowFrame(label, width, padding, context)
{
	// WFOuter
	this.m_div = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(this.m_div, "CWindowFrameOuter");
	if (width)
		alib.dom.styleSet(this.m_div, "width", width);
	
	var lbl_div = alib.dom.createElement("div", this.m_div);
	this.m_lbl_div = lbl_div;
	if (label)
		alib.dom.styleSetClass(lbl_div, "CWindowFrameLabel");

	// Context content is displayed to the right of the label - commonly used for pagination
	var con_div = alib.dom.createElement("div", lbl_div);
	alib.dom.styleSetClass(con_div, "CWindowFrameContext");
	this.m_context_div = con_div;
	if (context && lbl_div)
	{
		// WFLabel
		con_div.innerHTML = context;
	}

	if (label)
	{
		if (typeof label == "string")
		{
			var sp = alib.dom.createElement("span", lbl_div);
			sp.innerHTML = label;
		}
		else
		{
			lbl_div.appendChild(label);
		}
	}
	
	// Content
	this.m_con_div = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(this.m_con_div, "CWindowFrameContent");
	if (padding)
		alib.dom.styleSet(this.m_con_div, "padding", padding);
	this.m_div.appendChild(this.m_con_div);
}

CWindowFrame.prototype.getCon = function ()
{
	return this.m_con_div;
}

CWindowFrame.prototype.getTitleCon = function ()
{
	alib.dom.styleSetClass(this.m_lbl_div, "CWindowFrameLabel"); // May not be set due to no label, make it visible
	return this.m_lbl_div;
}

CWindowFrame.prototype.getContextCon = function ()
{
	return this.m_context_div;
}

CWindowFrame.prototype.getFrame = function()
{
	return this.m_div;
}

CWindowFrame.prototype.print = function(div_parent)
{
	if (div_parent)
		div_parent.appendChild(this.getFrame());
	else
		document.write(this.m_div.outerHTML);
}

CWindowFrame.prototype.hideContent= function()
{
	this.m_con_div.style.display = "none";
}

CWindowFrame.prototype.showContent= function()
{
	this.m_con_div.style.display = "block";
}

CWindowFrame.prototype.setHeight = function(height)
{
	alib.dom.styleSet(this.m_div, "height", height);

	var conheight = (this.m_div.offsetHeight - this.m_lbl_div.offsetHeight) + "px";
	alib.dom.styleSet(this.m_con_div, "height", conheight);
	alib.dom.styleSet(this.m_con_div, "overflow", "auto");
}

CWindowFrame.prototype.hide = function()
{
	alib.dom.styleSet(this.m_div, "display", "none");
}

CWindowFrame.prototype.show = function()
{
	alib.dom.styleSet(this.m_div, "display", "block");
}
