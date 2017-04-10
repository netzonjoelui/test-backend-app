/**
 * @fileOverview alib.dom Is a namespace used for interacting with the dom
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */
alib.dom = alib.dom || {}

/**
 * Flag to determine if the current window is visible
 *
 * @public
 * @var {bool}
 */
alib.dom.windowVisible = true;

/**
 * Flag to determine if the user has been active on the page within the last 5 minutes
 *
 * @public
 * @var {bool}
 */
alib.dom.userActive = true;

/**
 * Initilize namespace
 */
alib.dom.init = function()
{
	this.m_stylecache = {};
	this.m_document = document;


	// Set mouse move
	jQuery(document).ready(function(){
		$(document).mousemove(function(e){
			alib.dom.updateActive();
			alib.dom.mouse_x = e.pageX;
			alib.dom.mouse_y = e.pageY;
	   }); 
	});	


	// Set window visible
	(function() {
		var hidden = "hidden";

		// Standards:
		if (hidden in document)
			document.addEventListener("visibilitychange", onchange);
		else if ((hidden = "mozHidden") in document)
			document.addEventListener("mozvisibilitychange", onchange);
		else if ((hidden = "webkitHidden") in document)
			document.addEventListener("webkitvisibilitychange", onchange);
		else if ((hidden = "msHidden") in document)
			document.addEventListener("msvisibilitychange", onchange);
		// IE 9 and lower:
		else if ('onfocusin' in document)
			document.onfocusin = document.onfocusout = onchange;
		// All others:
		else
			window.onpageshow = window.onpagehide 
				= window.onfocus = window.onblur = onchange;

		function onchange (evt) {
			alib.m_debug = true;
			var v = 'visible', h = 'hidden',
				evtMap = { 
					focus:v, focusin:v, pageshow:v, blur:h, focusout:h, pagehide:h 
				};

			evt = evt || window.event;
			if (evt.type in evtMap)
			{
				/*
				alib.trace(evtMap[evt.type]);
				document.body.className = evtMap[evt.type];
				*/
				alib.dom.windowVisible = (evtMap[evt.type] == "visible") ? true : false;
			}
			else        
			{
				alib.dom.windowVisible = (this[hidden]) ? false : true;
				/*
				alib.trace("thhdn" + this[hidden]);
				document.body.className = this[hidden] ? "hidden" : "visible";
				*/
			}
		}
	})();
}

/***********************************************************************************
 *
 *	Function: 	setCurrentDoc
 *
 *	Purpose:	(pubic) Change local document variable to work within frames
 *
 *	Arguements:	doc		- element: the document elment to use
 *
 ***********************************************************************************/
alib.dom.setCurrentDoc = function(doc)
{


	// Reset mouse move
	/*
	var cls = this;
	if (alib.userAgent.ie)
	{
		this.m_document.detachEvent('onmousemove', cls.setMouseCoords);
		this.m_document.attachEvent('onmousemove', cls.setMouseCoords);
	}
	else
	{
		try 
		{
			this.m_document.removeEventListener('mousemove', cls.setMouseCoords, false);
			this.m_document.addEventListener('mousemove', cls.setMouseCoords, false);
		}
		catch (e) {}
	}
	*/
}

/**
 * Create a new dom element
 *
 * @public
 * @param {string} type The name of the element to create
 * @param {DOMElement} appendTo Optional parent element to append new element to
 * @param {string|DOMElement} content Either string to set innerHTML or element to append to newly created element
 */
alib.dom.createElement = function(type, appendto, content, attributes)
{
	var dv = this.m_document.createElement(type);

	if (appendto)
		appendto.appendChild(dv);

	if (typeof content != "undefined" && content != null)
	{
		if (typeof content == "string")
			dv.innerHTML = content;
		else
			dv.appendChild(content);
	}

	if (typeof attributes != "undefined" && typeof attributes != "string" && attributes != null)
	{
		for (var name in attributes)
		{
			dv.setAttribute(name, attributes[name]);
		}
	}

	return dv;
}

/**
 * Get element height
 *
 * @public
 * @this {CDom}
 * @param {DOMElement} e An element to calculate the height for
 * @return {number} The height in px of of the element
 */
alib.dom.getElementHeight = function(e, includeMargin)
{
	if (typeof includeMargin == "undefined")
		var includeMargin = true;

	return $(e).outerHeight(includeMargin);
}

/**
 * Get element width
 *
 * @public
 * @this {CDom}
 * @param {DOMElement} e An element to calculate the height for
 * @return {number} The width in px of of the element
 */
alib.dom.getElementWidth = function(e, includeMargin)
{
	if (typeof includeMargin == "undefined")
		var includeMargin = true;

	return $(e).outerWidth(includeMargin);
}

/**
 * Query - basically an alias for $("querystring")
 *
 * @param {string} qstr
 */
alib.dom.query = function(qstr, node)
{
	var pnode = node || null;
	return $(qstr, pnode);
}

/**
 * Find out if an element is scrolled into viwe
 *
 * @param {DOMElement} elem The element to check and see if it is in view
 * @param {DOMElement} parentElem An optional parent element that may contain a scroll overflow
 */
alib.dom.isScrolledIntoView = function(elem, parentElem)
{
	var pnt = parentElem || window;

    var docViewTop = $(pnt).scrollTop();
    var docViewBottom = docViewTop + $(pnt).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

/***********************************************************************************
 *
 *	Function: 	getElementById
 *
 *	Purpose:	(pubic) Abstract document.getElementById
 *
 *	Arguements:	id	- string: id of element to get
 *
 ***********************************************************************************/
alib.dom.getElementById = function(id)
{
	var ele = this.m_document.getElementById(id);

	return ele;
}


/***********************************************************************************
 *
 *	Function: 	addEvntListener
 *
 *	Purpose:	(pubic) Append an event listener to an object
 *
 *	Arguements:	e		- element
 *				evnt	- string: the event to capture
 *				funct	- function: function to append
 *
 ***********************************************************************************/
alib.dom.addEvntListener = function(e, evnt, funct)
{
	if (alib.userAgent.ie)
	{
		//e.detachEvent(evnt, funct);
		e.attachEvent("on"+evnt, funct);
	}
	else
	{
		try 
		{
			//e.removeEventListener(evnt, funct, false);
			e.addEventListener(evnt, funct, false);
		}
		catch (e) {}
	}
}

/**
 * Add event listener to an element
 *
 * @return bool true on if event was added, false if not
 */
alib.dom.addEvent = function(obj, event_name, func_name)
{
	var added = false;
	if(obj.addEventListener)
	{
		obj.addEventListener(event_name, func_name, true);
		added = true;
	}
	else if (obj.attachEvent)
	{
		obj.attachEvent("on"+event_name, func_name);
		added = true;
	}
	/*
	else
	{
		obj["on"+event_name] = func_name;
	}
	*/
	return added;
}

/**
 * Removes an event from the object
 *
 * @return bool true on if event was removed, false if not
 */
alib.dom.removeEvent = function(obj,event_name,func_name)
{
	var removed = false;
	if (obj.detachEvent)
	{
		obj.detachEvent("on"+event_name,func_name);
		removed = true;
	}
	else if(obj.removeEventListener)
	{
		obj.removeEventListener(event_name,func_name,true);
		removed = true;
	}
	/*
	else
	{
		obj["on"+event_name] = null;
	}
	*/
	return removed;
}

/**
 * Stop an event from bubbling up the event DOM
 */
alib.dom.stopEvent = function(evt)
{
	evt || window.event;
	if (evt.stopPropagation){
		evt.stopPropagation();
		evt.preventDefault();
	}else if(typeof evt.cancelBubble != "undefined"){
		evt.cancelBubble = true;
		evt.returnValue = false;
	}
	return false;
}

/*    Caret Functions     */

/**
 * Get the end position of the caret in the object. Note that the obj needs to be in focus first
 */
alib.dom.getCaretEnd = function(obj)
{
	if(typeof obj.selectionEnd != "undefined"){
		return obj.selectionEnd;
	}else if(document.selection&&document.selection.createRange){
		var M=document.selection.createRange();
		try{
			var Lp = M.duplicate();
			Lp.moveToElementText(obj);
		}catch(e){
			var Lp=obj.createTextRange();
		}
		Lp.setEndPoint("EndToEnd",M);
		var rb=Lp.text.length;
		if(rb>obj.value.length){
			return -1;
		}
		return rb;
	}
}
/**
 * Get the start position of the caret in the object
 */
alib.dom.getCaretStart = function(obj)
{
	if(typeof obj.selectionStart != "undefined"){
		return obj.selectionStart;
	}else if(document.selection&&document.selection.createRange){
		var M=document.selection.createRange();
		try{
			var Lp = M.duplicate();
			Lp.moveToElementText(obj);
		}catch(e){
			var Lp=obj.createTextRange();
		}
		Lp.setEndPoint("EndToStart",M);
		var rb=Lp.text.length;
		if(rb>obj.value.length){
			return -1;
		}
		return rb;
	}
}
/**
 * sets the caret position to l in the object
 */
alib.dom.setCaret = function(obj,l)
{
	obj.focus();
	if (obj.setSelectionRange)
	{
		obj.setSelectionRange(l,l);
	}
	else if(obj.createTextRange)
	{
		m = obj.createTextRange();		
		m.moveStart('character',l);
		m.collapse();
		m.select();
	}
}

/**
 * sets the caret selection from s to e in the object
 */
alib.dom.setSelection = function (obj,s,e)
{
	obj.focus();
	if (obj.setSelectionRange){
		obj.setSelectionRange(s,e);
	}else if(obj.createTextRange){
		m = obj.createTextRange();		
		m.moveStart('character',s);
		m.moveEnd('character',e);
		m.select();
	}
}

/***********************************************************************************
 *
 *	Function: 	styleToCamel
 *
 *	Purpose:	(private) Change a hyphenated style to Camel
 *
 *	Arguements:	property	- string: propertry to convert
 *
 ***********************************************************************************/
alib.dom.styleToCamel = function(property)
{
	var change = function(prop) 
	{
		var test = /(-[a-z])/i.exec(prop);
		var ret = prop.replace(RegExp.$1, RegExp.$1.substr(1).toUpperCase());
		return ret;
	};
      
	while(property.indexOf('-') > -1)
		property = change(property);

	return property;
}

/***********************************************************************************
 *
 *	Function:	styleToHyphen
 *
 *	Purpose:	(private) Change a Camle (nameName) hyphenated (name-name)
 *
 *	Arguements:	property	- string: propertry to convert
 *
 ***********************************************************************************/
alib.dom.styleToHyphen = function(property)
{
	if (property.indexOf('-') > -1)
		return property;

	var converted = '';
	for (var i = 0, len = property.length;i < len; ++i) 
	{
		if (property.charAt(i) == property.charAt(i).toUpperCase()) 
		{
			converted = converted + '-' + property.charAt(i).toLowerCase();
		} 
		else 
		{
			converted = converted + property.charAt(i);
		}
	}

	return converted;
}

/***********************************************************************************
 *
 *	Function: 	styleMakeCache
 *
 *	Purpose:	(private) cache converted styles
 *
 *	Arguements:	property	- string: propertry to cache
 *
 ***********************************************************************************/
alib.dom.styleMakeCache = function(property) 
{
	this.m_stylecache[property] = 
	{
		camel: this.styleToCamel(property),
		hyphen: this.styleToHyphen(property)
	};
};

/***********************************************************************************
 *
 *	Function:	styleGet
 *
 *	Purpose:	(public) get style of element
 *
 *	Arguements:	element		- element: element to reference
 *				property 	- string: style property to get
 *
 ***********************************************************************************/
alib.dom.styleGet = function(element, property)
{
	var val = null;
	var dv = this.m_document.defaultView;

	if (!element)
		return null;

	// Use jquery instead
	return $(element).css(property);

	/*
	if (!this.m_stylecache[property])
		this.styleMakeCache(property);

	var camel = this.m_stylecache[property]['camel'];
	var hyphen = this.m_stylecache[property]['hyphen'];
	
	// Check for IE opacity
	if (property == 'opacity' && element.filters) 
	{
		val = 1;
		try 
		{
			val = element.filters.item('DXImageTransform.Microsoft.Alpha').opacity / 100;
		} 
		catch(e) 
		{
			try 
			{
				val = element.filters.item('alpha').opacity / 100;
			} 
			catch(e) {}
		}
	} 
	else if (element.style[camel]) // get camelCase
	{ 
		val = element.style[camel];
	}
	else if (alib.userAgent.ie && element.currentStyle && element.currentStyle[camel]) // Opera 9 "currentStyle" is broken
	{ 
		// camelCase for currentStyle; isIE to workaround broken Opera 9 currentStyle
		val = element.currentStyle[camel];
	}
	else if (dv && dv.getComputedStyle ) // hyphen-case for computedStyle
	{ 
		var computed = dv.getComputedStyle(element, '');

		if (computed && computed.getPropertyValue(hyphen)) 
		{
			val = computed.getPropertyValue(hyphen);
		}
	}

	if (property == 'color')
	{
		if (val.match(/^rgb/) != null)
		{
			var arr = val.match(/\d+/g);
			val = this.rgbToHex(arr[0], arr[1], arr[2]);
		}
	}

	return val;
	*/
}

/***********************************************************************************
 *
 *	Function:	styleSet
 *
 *	Purpose:	(public) set style of element
 *
 *	Arguements:	element		- element: element to reference
 *				property 	- string: style property to set
 *				value 		- string: value to apply to property
 *
 ***********************************************************************************/
alib.dom.styleSet = function(element, property, value)
{
	$(element).css(property, value);
	return;
	/*
	if (!this.m_stylecache[property]) 
		this.styleMakeCache(property);
         
	var camel = this.m_stylecache[property]['camel'];
	switch(property) 
	{
	case 'opacity':
		if (alib.userAgent.ie && typeof element.style.filter == 'string') 
		{ 
			// not appended
			element.style.filter = 'alpha(opacity=' + value * 100 + ')';

			if (!element.currentStyle || !element.currentStyle.hasLayout) 
			{
				// no layout or cant tell
				element.style.zoom = 1; 
			}
		} 
		else 
		{
			element.style.opacity = value;
			element.style['-moz-opacity'] = value;
			element.style['-khtml-opacity'] = value;
		}
		break;
	case 'float':
		if (alib.userAgent.ie)
			element.style['styleFloat'] = value;
		else
			element.style['cssFloat'] = value;
		break;
	default:
		try
		{
			element.style[camel] = value;
		}
		catch (e) {}
	}
	*/
}

/***********************************************************************************
 *
 *	Function: 	getClientHeight
 *
 *	Purpose:	(public) get the height of the client (window)
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getClientHeight = function()
{

	/*
	var height = -1;
    var mode = this.m_document.compatMode;
      
	if ( (mode || alib.userAgent.ie) && !alib.userAgent.opera ) 
	{
		// IE - Gecko
		switch (mode) 
		{
			case 'CSS1Compat': // Standards mode
				height = this.m_document.documentElement.clientHeight;
				break;
      
			default: // Quirks
				height = this.m_document.body.clientHeight;
		}
	} 
	else // Safari - Opera
	{ 
		height = self.innerHeight;
	}
      
	return height;
	*/
	
	// Get document height from jquery
	return $(window).height();
}
alib.dom.GetClientHeight = function()
{
	return this.getClientHeight();
}

/***********************************************************************************
 *
 *	Function:	getClientWidth
 *
 *	Purpose:	(public) get the width of the client (window)
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getClientWidth = function()
{
	/*
	var width = -1;
	var mode = this.m_document.compatMode;
      
	// IE, Gecko, Opera
	if (mode || alib.userAgent.ie) 
	{ 
		switch (mode) 
		{
		case 'CSS1Compat': // Standards mode 
			width = this.m_document.documentElement.clientWidth;
			break;

		default: // Quirks
			width = this.m_document.body.clientWidth;
		}
	} 
	else // Safari
	{ 
		width = self.innerWidth;
	}

	return width;
	*/

	return $(window).width();
}

alib.dom.GetClientWidth = function()
{
	this.getClientWidth();
}

/***********************************************************************************
 *
 *	Function: 	getDocumentHeight
 *
 *	Purpose:	(public) get the height of the entire document
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getDocumentHeight = function()
{
	// Get document height via jquery
	return $(this.m_document).height();
/*
	var scrollHeight=-1;
	ALib.m_evwnd.eight=-1;
	var bodyHeight=-1;

	var marginTop = parseInt(this.styleGet(this.m_document.body, 'marginTop'), 10);

	var marginBottom = parseInt(this.styleGet(this.m_document.body, 'marginBottom'), 10);
         
	var mode = this.m_document.compatMode;
         
	if ((mode || alib.userAgent.ie) && !alib.userAgent.opera) // IE - Gecko
	{ 
		switch (mode) 
		{
		case 'CSS1Compat': // Standards mode
			scrollHeight = ((ALib.m_evwnd.innerHeight && ALib.m_evwnd.scrollMaxY) 
					?  ALib.m_evwnd.innerHeight+ALib.m_evwnd.scrollMaxY : -1);

			ALib.m_evwnd.eight = [this.m_document.documentElement.clientHeight, self.innerHeight||-1].sort(function(a, b){return(a-b);})[1];
			bodyHeight = this.m_document.body.offsetHeight + marginTop + marginBottom;
			break;

		default: // Quirks
			scrollHeight = this.m_document.body.scrollHeight;
			bodyHeight = this.m_document.body.clientHeight;
		}
		
	} 
	else // Safari - Opera
	{ 
		scrollHeight = this.m_document.documentElement.scrollHeight;
		ALib.m_evwnd.eight = self.innerHeight;
		bodyHeight = this.m_document.documentElement.clientHeight;
	}
	
	//var h = this.getDocumentHeights();
	var h = [scrollHeight,ALib.m_evwnd.eight,bodyHeight].sort(function(a, b){return(a-b);});
	return h[2];
*/
}

alib.dom.GetDocumentHeight = function()
{
	return this.getDocumentHeight();
}


/***********************************************************************************
 *
 *	Function: 	getDocumentHeights
 *
 *	Purpose:	(public) get the height of the entire document in scroll and more
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getDocumentHeights = function()
{
	var scrollHeight=-1;
	ALib.m_evwnd.eight=-1;
	var bodyHeight=-1;

	var marginTop = parseInt(this.styleGet(this.m_document.body, 'marginTop'), 10);

	var marginBottom = parseInt(this.styleGet(this.m_document.body, 'marginBottom'), 10);
         
	var mode = this.m_document.compatMode;
         
	if ((mode || alib.userAgent.ie) && !alib.userAgent.opera) // IE - Gecko
	{ 
		switch (mode) 
		{
		case 'CSS1Compat': // Standards mode
			scrollHeight = ((ALib.m_evwnd.innerHeight && ALib.m_evwnd.scrollMaxY) 
					?  ALib.m_evwnd.innerHeight+ALib.m_evwnd.scrollMaxY : -1);

			ALib.m_evwnd.eight = [this.m_document.documentElement.clientHeight, self.innerHeight||-1].sort(function(a, b){return(a-b);})[1];
			bodyHeight = this.m_document.body.offsetHeight + marginTop + marginBottom;
			break;

		default: // Quirks
			scrollHeight = this.m_document.body.scrollHeight;
			bodyHeight = this.m_document.body.clientHeight;
		}
		
	} 
	else // Safari - Opera
	{ 
		scrollHeight = this.m_document.documentElement.scrollHeight;
		ALib.m_evwnd.eight = self.innerHeight;
		bodyHeight = this.m_document.documentElement.clientHeight;
	}
	
	var h = [scrollHeight,ALib.m_evwnd.eight,bodyHeight];
	return h;
}

/***********************************************************************************
 *
 *	Function: 	getDocumentWidth
 *
 *	Purpose:	(public) get the width of the entire document
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getDocumentWidth = function()
{
	return $(this.m_document).width();

	/*
	var docWidth=-1,bodyWidth=-1,winWidth=-1;
	var marginRight = parseInt(this.styleGet(this.m_document.body, 'marginRight'), 10);
	var marginLeft = parseInt(this.styleGet(this.m_document.body, 'marginLeft'), 10);

	var mode = this.m_document.compatMode;
	
	// (IE, Gecko, Opera)
	if (mode || isIE) 
	{ 
		switch (mode) 
		{
		case 'CSS1Compat': // Standards
			docWidth = this.m_document.documentElement.clientWidth;
			bodyWidth = this.m_document.body.offsetWidth + marginLeft + marginRight;
			winWidth = self.innerWidth || -1;
			break;

		default: // Quirks
			bodyWidth = this.m_document.body.clientWidth;
			winWidth = this.m_document.body.scrollWidth;
			break;
		}
	} 
	else // safari
	{
		docWidth = this.m_document.documentElement.clientWidth;
		bodyWidth = this.m_document.body.offsetWidth + marginLeft + marginRight;
		winWidth = self.innerWidth;
	}

	var w = [docWidth,bodyWidth,winWidth].sort(function(a, b){return(a-b);});
	return w[2];
	*/
}

alib.dom.GetDocumentWidth = function()
{
	return this.getDocumentWidth();
}

/**
 * get the current position (scrolled) on the document - top
 *
 * @param {DOMElement} ele Optional element to check overflow scrolling for
 * @return {int} px from top scrolled
 */
alib.dom.getScrollPosTop = function(ele)
{
	var ele = ele || this.m_document;

	return $(ele).scrollTop();

	/*
	return typeof ALib.m_evwnd.pageYOffset != 'undefined' ? ALib.m_evwnd.pageYOffset
			: ALib.m_document.documentElement && ALib.m_document.documentElement.scrollTop
			? ALib.m_document.documentElement.scrollTop
			: ALib.m_document.body.scrollTop ? ALib.m_document.body.scrollTop:0;
			*/
}


/**
 * Get the corrent position (scrolled) on the element or document to left
 *
 * @param {DOMElement} ele Optional element to check overflow scrolling for
 * @return {int} px from left scrolled
 */
alib.dom.getScrollPosLeft = function(ele)
{
	var ele = ele || this.m_document;

	return $(ele).scrollLeft();

	/*
	return typeof ALib.m_evwnd.pageXOffset != 'undefined' ? ALib.m_evwnd.pageXOffset
			: ALib.m_document.documentElement && ALib.m_document.documentElement.scrollLeft
			? ALib.m_document.documentElement.scrollLeft
			: ALib.m_document.body.scrollLeft ? ALib.m_document.body.scrollLeft:0;
	*/
}

/***********************************************************************************
 *
 *	Function: 	setScrollPosTop
 *
 *	Purpose:	(public) get the current position (scrolled) on the document - top
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.setScrollPosTop = function(topy)
{
	ALib.m_evwnd.scrollBy(0,topy);
}

/***********************************************************************************
 *
 *	Function: 	styleAddClass
 *
 *	Purpose:	(public) append a class to an element
 *
 *	Arguements:	element		- element: element to modify
 *				className	- string: class to add
 *
 ***********************************************************************************/
alib.dom.styleAddClass = function(element, className)
{
	$(element).addClass(className);
}

alib.dom.StyleAddClass = function(element, className)
{
	this.styleAddClass(element, className);
}

/***********************************************************************************
 *
 *	Function: 	styleRemoveClass
 *
 *	Purpose:	(public) remove a class from an element
 *
 *	Arguements:	element		- element: element to modify
 *				className	- string: class to remove
 *
 ***********************************************************************************/
alib.dom.styleRemoveClass = function(element, strClass)
{
	$(element).removeClass(strClass);
}

/***********************************************************************************
 *
 *	Function: 	styleSetClass
 *
 *	Purpose:	(public) change class of element. This will replace current class
 *
 *	Arguements:	element		- element: element to modify
 *				className	- string: class to add
 *
 ***********************************************************************************/
alib.dom.styleSetClass = function(element, className)
{
	try
	{
		element['className'] = className;
	}
	catch (e) {}
}
alib.dom.setClass = function(element, className)
{
	this.styleSetClass(element, className);
}

/***********************************************************************************
 *
 *	Function:	getElementPosition
 *
 *	Purpose:	(public) get the position of an emelment in relation to doc
 *
 *	Arguements:	o		- element: element to locate
 *
 ***********************************************************************************/
alib.dom.getElementPosition = function(o)
{
	var pos = $(o).offset();
	var left	= pos.left;
	var top 	= pos.top;
	var right 	= o.offsetWidth + left;
	var bottom 	= o.offsetHeight + top;

	/*
	try
	{
		while (o.offsetParent)
		{
			left += o.offsetLeft;
			top  += o.offsetTop;
			if (o != document.body && o != document.documentElement && o != orig)
			{
				left -= o.scrollLeft;
				top  -= o.scrollTop;

				//ALib.trace(o);
				//ALib.trace("Scroll Top: " +  o.id + " " + o.scrollTop);
			}

			o = o.offsetParent;
		}
	}
	catch(e) {}
	*/

	return {x:left, y:top, r:right, b:bottom};
}

/***********************************************************************************
 *
 *	Function:	setMouseCoords
 *
 *	Purpose:	(private) set the current position of the mouse (for tracking clicks)
 *
 *	Arguements:	ev		- event: event to process
 *
 ***********************************************************************************/
alib.dom.setMouseCoords = function (ev)
{
	alib.dom.updateActive();

	ev = CDomFixEvent(ev);
	if(ev.pageX || ev.pageY)
	{
		alib.dom.mouse_x = ev.pageX;
		alib.dom.mouse_y = ev.pageY;
	}
	else
	{
		try
		{
			alib.dom.mouse_x = ev.clientX + ALib.m_document.body.scrollLeft - ALib.m_document.body.clientLeft;
			alib.dom.mouse_y = ev.clientY + ALib.m_document.body.scrollTop  - ALib.m_document.body.clientTop;
		}
		catch (e) {}
	}
}

/***********************************************************************************
 *
 *	Function:	 getMouseCoords
 *
 *	Purpose:	(public) return x and y of current mouse position
 *
 *	Arguements:	
 *
 ***********************************************************************************/
alib.dom.getMouseCoords = function ()
{
	return { x:alib.dom.mouse_x, y:alib.dom.mouse_y	};

}

/**
 * Set flag that the user is interacting with the document
 *
 * @private
 */
alib.dom.updateActive = function ()
{
	if (this.acTimer)
		clearTimeout(this.acTimer);

	alib.dom.userActive = true;

	this.acTimer = setTimeout(function() { alib.dom.userActive = false; }, 1000*60*3); // 3 minutes 
}

/***********************************************************************************
 *
 *	Function:	changeFontSize
 *
 *	Purpose:	(public) change the size of font inside any container
 *
 *	Arguements:	e	- string/element : the id of the container or the container
 *				type - string : + or -
 *
 ***********************************************************************************/
alib.dom.changeFontSize = function(e, type, min, max)
{
	if (typeof e == "string")
		e = this.getElementById(e);

	if (!min)
		var min = 8;

	if (!max)
		var max = 18;

    if(e.style.fontSize) 
	{
	    var s = parseInt(e.style.fontSize.replace("px",""));
    } 
	else 
	{
    	var s = 12;
    }

	if (type == "-")
	{
		if(s!=min) 
		{
			s -= 1;
		}
	}
	else
	{
		if(s!=max) 
		{
			s += 1;
		}
	}
	
 	e.style.fontSize = s+"px"
	
}

/***********************************************************************************
 *
 * @depricated This is no longer needed now that HTML5 suports .placeholder attribute
 *
 *	Function:	setInputBlurText
 *
 *	Purpose:	(public) Put text inside input until user clicks on it
 *
 *	Arguements:	e	- string/input : the id of the container or the container
 *				type - string : + or -
 *
 ***********************************************************************************/
alib.dom.setInputBlurText = function(e, text, blurclass, onclass, overclass)
{
	if (typeof e == "string")
		e = this.getElementById(e);

	e.placeholder = text;

	/*
	if (onclass)
		e.onclass = onclass;
	if (blurclass)
		e.blurclass = blurclass;
	if (overclass)
		e.overclass = overclass;

	e.blurtext = text;
	e.value = text;

	if (blurclass)
		this.styleSetClass(e, blurclass);
        
	e.onfocus = function() { 
		if (this.overclass)
		{
			this.onmouseover = function()
			{
				alib.dom.styleAddClass(this, this.overclass); 
			}

			this.onmouseout = function()
			{
				alib.dom.styleRemoveClass(this, this.overclass); 

				if (this.onclass)
					alib.dom.styleAddClass(this, this.onclass); 
			}
		}

		if (this.value == this.blurtext)
			this.value = ""; 

		if (this.onclass)
			alib.dom.styleAddClass(this, this.onclass); 
		else if (this.blurclass)
			alib.dom.styleRemoveClass(this, this.blurclass); 
	};

	e.onblur = function()
	{ 
		if (this.overclass)
		{
			this.onmouseover = function()
			{
				alib.dom.styleRemoveClass(this, this.blurclass); 
				alib.dom.styleAddClass(this, this.overclass); 
			}

			this.onmouseout = function()
			{
				if (this.blurclass)
					alib.dom.styleAddClass(this, this.blurclass); 
				else
					alib.dom.styleRemoveClass(this, this.overclass); 
			}
		}

		if (this.blurclass)
				alib.dom.styleAddClass(this, this.blurclass); 

		if (this.value == "")
		{
			this.value = this.blurtext;
		}
	}

	if (overclass)
	{
		e.onmouseover = function()
		{
			alib.dom.styleaAddClass(this, this.overclass); 
		}

		e.onmouseout = function()
		{
			if (this.blurclass)
				alib.dom.styleAddtClass(this, this.blurclass); 
			else
				alib.dom.styleRemoveClass(this, this.overclass); 
		}
	}
	*/
}

/***********************************************************************************
 *
 *	Function:	textAreaAutoResize
 *
 *	Purpose:	(public) Make a textarea autoresize
 *
 *	Arguements:	e	- string/input : the id of the container or the container
 *				type - string : + or -
 *				min	- minimum height
 *				max	- maximum height
 *
 ***********************************************************************************/
alib.dom.textAreaAutoResizeHeight = function(e, min_height, max_height)
{
	var minHeight = (typeof min_height != "undefined") ? min_height : null;
	var maxHeight = (typeof max_height != "undefined") ? max_height : null;

	if (typeof e == "string")
		e = this.getElementById(e);

	e.minHeight = minHeight;
	e.maxHeight = maxHeight;
	e.style.resize = 'none';

	// Create a clone
	var ta = alib.dom.createElement("textarea");
	ta.setAttribute("tabIndex", "-1");
	alib.dom.styleSet(ta, "position", "absolute");
	alib.dom.styleSet(ta, "top", "0");
	alib.dom.styleSet(ta, "left", "-9999px");
	if (e.placeholder)
		ta.value = e.placeholder; // use this to set initial height
	e.parentNode.insertBefore(ta, e);
	e.ta = ta;
	e.autoResizeHeight = function()
	{
		if (!alib.userAgent.ie)
		{
			if (e.minHeight)
				this.ta.style.height = this.minHeight + "px";
			else
				this.ta.style.height = "0px";

			this.ta.style.width = alib.dom.getElementWidth(this) + "px";

			this.ta.value = this.value;

			// Firefox does not account for padding, so add 10px for safety
			if (alib.userAgent.firefox)
				var height = this.ta.scrollHeight + 10;
			else
				var height = this.ta.scrollHeight;
		}
		else
		{
			var height = this.scrollHeight;
		}

		alib.dom.styleSet(this, "overflow-y", "hidden");

		if (this.minHeight && height < this.minHeight)
		{
			if (!alib.userAgent.ie)
				this.style.height = this.minHeight + "px";
			return true;
		}
		else if (this.maxHeight && height > this.maxHeight)
		{
			alib.dom.styleSet(this, "overflow-y", "auto");
			alib.dom.styleSet(this, "height", this.maxHeight + 5 + "px");
			//return true;
		}
		else
		{
			this.style.height = height+"px";
		}

		return height;
	}

	function intervalMethod() 
	{
		var res = e.autoResizeHeight();
		if (res)
		{
			clearInterval(e.initInterval);
		}
	}
	e.initInterval = setInterval(intervalMethod, 200);

	var funct = function(e)
	{
		if (alib.userAgent.ie)
			var ta = ALib.m_evwnd.event.srcElement;
		else
			var ta = this;

		ta.autoResizeHeight();
	}

	if (alib.userAgent.ie)
	{
		e.attachEvent('onkeyup', funct);
		e.attachEvent('onfocus', funct);
	}
	else
	{
		try 
		{
			e.addEventListener('keyup', funct, false);
			e.addEventListener('focus', funct, false);
		}
		catch (e) {}
	}
}

/***********************************************************************************
 *
 *	Function: 	getContentHeight
 *
 *	Purpose:	(public) get the content height of an element (minus px)
 *
 *	Arguements:	e = element
 *
 ***********************************************************************************/
alib.dom.getContentHeight = function(e)
{
	var height = $(e).height();
	/*
	var height = -1;
    
	var marginTop = parseInt(this.styleGet(this.m_document.body, 'marginTop'), 10);
	var marginBottom = parseInt(this.styleGet(this.m_document.body, 'marginBottom'), 10);
	var paddingTop = parseInt(this.styleGet(this.m_document.body, 'paddingTop'), 10);
	var paddingBottom = parseInt(this.styleGet(this.m_document.body, 'paddingBottom'), 10);

	height = e.offsetHeight-marginTop-marginBottom-paddingTop-paddingBottom;
	*/

	/*
	if (alib.userAgent.ie)
		height = height - 20;
		*/
      
	return height;
}

/***********************************************************************************
 *
 *	Function: 	getScrollBarWidth
 *
 *	Purpose:	(public) get the width of the system scrollbar
 *
 *	Arguements:	e = element
 *
 ***********************************************************************************/
alib.dom.getScrollBarWidth = function(e)
{
	var inner = this.createElement('p');
	inner.style.width = "100%";
	inner.style.height = "100px";

	var outer = this.createElement('div');
	outer.style.position = "absolute";
	outer.style.top = "0px";
	outer.style.left = "0px";
	outer.style.visibility = "hidden";
	outer.style.width = "50px";
	outer.style.height = "50px";
	outer.style.overflow = "hidden";
	outer.appendChild (inner);

	this.m_document.body.appendChild(outer);
	var w1 = inner.offsetWidth;
	outer.style.overflow = 'scroll';
	var w2 = inner.offsetWidth;
	if (w1 == w2) w2 = outer.clientWidth;

	this.m_document.body.removeChild (outer);

	return (w1 - w2);
}

/***********************************************************************************
 *
 *	Function: 	function
 *
 *	Purpose:	(public) get the hex value for an RGB structure
 *
 *	Arguements:	red, green, blue = 1-255 color values
 *
 ***********************************************************************************/
alib.dom.rgbToHex = function(red, green, blue)
{
	return this.intToHex(red) + this.intToHex(green) + this.intToHex(blue);
}

/***********************************************************************************
 *
 *	Function: 	intToHex
 *
 *	Purpose:	(public) convert an integer to a hex
 *
 *	Arguements:	integer value
 *
 ***********************************************************************************/
alib.dom.intToHex = function(value)
{
	value = parseInt(value).toString(16);
	return value.length < 2 ? value + "0" : value;
}

/***********************************************************************************
 *
 *	Function:	getEventSource
 *
 *	Purpose:	(public) get the source element from an event
 *
 *	Arguements:	e	- event: event to process/translate
 *
 ***********************************************************************************/
alib.dom.getEventSource = function(evt)
{
	evt = CDomFixEvent(evt);

	if (evt)
	{
		if (alib.userAgent.ie)
		{
			return rvt.srcElement;
		}
		else
		{
			return evt.target;
		}
	}
}

/**
 * Place text into an element safely
 *
 * @param {DOMElement} e The element to put text into
 * @param {string} html The text to put in the element
 */
alib.dom.setText = function(e, text)
{
	$(e).text(text);
}

/**
 * Place HTML into an element safely
 *
 * @param {DOMElement} e The element to put text into
 * @param {string} html The html to put in the element
 */
alib.dom.setHtml = function(e, html)
{
	$(e).html(html);
}

/*************************************************************************
*    Function:    buildTdLabel
* 
*    Purpose:    Build Td Row for every form input
**************************************************************************/
alib.dom.buildTdLabel = function(tbody, label, width)
{
    var tr = alib.dom.createElement("tr", tbody);
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSet(td, "fontSize", "12px");
    alib.dom.styleSet(td, "verticalAlign", "middle");
    alib.dom.styleSet(td, "paddingBottom", "8px");
    if(width)
        td.setAttribute("width", width);
    
    if(label)
        td.innerHTML = label;
        
    return tr;
}

/*************************************************************************
*    Function:    setElementAttr - (New)
* 
*    Purpose:    Sets Element attribute 
**************************************************************************/
alib.dom.setElementAttr = function(input, attrData)
{ 
    for(attribute in attrData)
    {
        var attr = attrData[attribute][0];
        var value = attrData[attribute][1];
        
        switch(attr)
        {
            case "padding":
            case "margin":
            case "margin-right":
            case "margin-left":
            case "margin-bottom":
            case "margin-top":
            case "width":
            case "height":
            case "font-weight":
            case "font-size":
            case "border":
            case "cursor":
            case "overflow":
            case "float":
            case "clear":
                alib.dom.styleSet(input, attr, value);
            default:
                input[attr] = value;
                break;
        }
    }
    
    return input;
}

/*************************************************************************
*    Function:    divClear
* 
*    Purpose:    clear the divs
**************************************************************************/
alib.dom.divClear = function(parentDiv)
{
    var divClear = alib.dom.createElement("div", parentDiv);
    alib.dom.styleSet(divClear, "clear", "both");
    alib.dom.styleSet(divClear, "visibility", "hidden");
}

/***********************************************************************************
 *
 *    Function:    styleSet Using a class name
 *
 *    Purpose:    (public) set style of element
 *
 *    Arguements: className    - string: Name of the class
 *                property     - string: style property to set
 *                value        - string: value to apply to property
 *
 ***********************************************************************************/
alib.dom.styleSetUsingClass = function(className, property, value)
{
    $("." + className).css(property, value);
}

// Initialize dom
alib.dom.init();

/***********************************************************************************
 *
 *	Function:	CDomFixEvent
 *
 *	Purpose:	(private) process and return standard event
 *
 *	Arguements:	e	- event: event to process/translate
 *
 ***********************************************************************************/
function CDomFixEvent(e)
{
	if (typeof e == 'undefined') 
	{
		if (ALib.m_evwnd)
			e = ALib.m_evwnd.event;
		else
			e = window.event;
	}
	
	if (e)
	{
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
	else
		return null;
}

/** 
 *  Another TextArea Autogrow plugin (0.2) alpha's alpha
 *  by Nikolay Borisov aka KOSIASIK
 *  mne@figovo.com
 *
 *  http://figovo.com/
 *
 *  Example: 
 *  $('textarea').ata();
 *
 *  jQuery required. Download it at http://jquery.com/
 *
 */
(function(jQuery){

	jQuery.fn.ata = function(options){

		options = jQuery.extend({
			timer:100
		}, options);
	
		return this.each(function(i){
	
			var $t = jQuery(this),
				t = this;

			t.style.resize = 'none';
			t.style.overflow = 'hidden';

			var tVal = t.value;			
			t.style.height = '0px';
			t.value = "W\nW\nW";
			var H3 = t.scrollHeight;
			t.value = "W\nW\nW\nW";
			var H4 = t.scrollHeight;
			var H = H4 - H3;
			t.value = tVal;
			tVal = null;

			$t.before("<div id=\"ataa_"+i+"\"></div>");

			var $c = jQuery('#ataa_'+i),
				c = $c.get(0);

			c.style.padding = '0px';
			c.style.margin = '0px';

			$t.appendTo($c);

			$t.bind('focus', function(){
				t.startUpdating()
			}).bind('blur', function(){
				t.stopUpdating()
			});

			this.heightUpdate = function(){

				if (tVal != t.value){

					tVal = t.value;
					t.style.height = '0px';
					var tH = t.scrollHeight + H;
					t.style.height = tH + 'px';
					c.style.height = 'auto';
					c.style.height = c.offsetHeight + 'px';

				}

			}

			this.startUpdating = function(){
				t.interval = window.setInterval(function(){
					t.heightUpdate()
				}, options.timer);
			}

			this.stopUpdating = function(){
				clearInterval(t.interval);	
			}

			jQuery(function(){
				t.heightUpdate()
			});

		});

	};

})(jQuery);
