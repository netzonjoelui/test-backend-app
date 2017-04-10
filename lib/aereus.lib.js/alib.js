/**
* @fileOverview Main loader class for the aereus library
*
* This is the base class for the entire Alib framework
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * @define {boolean} Overridden to true by the compiler when --closure_pass
 *     or --mark_as_compiled is specified.
 */
var COMPILED = false;

/**
 * The base root where the areus library is being defined
 */
var ALIB_ROOT = ALIB_ROOT || null;

/**
 * The base namespace for the alib library.
 *
 * @const
 */
var alib = alib || {}; // Identifies this file as the alib base.

/**
 * Alib class
 *
 * @constructor
 */
function Alib()
{
	this.m_appcontainer = null;

	this.setDocument();	
	this.m_evwnd = window;

	this.m_debug = false; // Used for tracing output
}

/***********************************************************************
*	Function:	setDocument
*
*	Purpose:	Used to set working ducument if different from 'document'
*
************************************************************************/
Alib.prototype.setDocument = function(doc)
{
	this.m_document = document;

	/* No longer needed - joe
	alib.dom.m_browser = this.m_browser;
	this.Effect.m_browser = this.m_browser;

	if (doc)
	{
		this.m_document = doc;
		alib.dom.setCurrentDoc(doc);
	}
	else
	{
		this.m_document = document;
		alib.dom.setCurrentDoc(document);
	}
	*/
}

/***********************************************************************
*	Function:	statusShowAlert
*
*	Arguments:	1.	content - either an element or text to display
*				2.	timeout - number of mili-seconds to display
*				3.	valign - top, middle, bottom (default=middle)
*				4.	halign - left, center, right (default=center)
*
*	Purpose:	Show status messages on document (absolute positioned)
*
************************************************************************/
Alib.prototype.statusShowAlert = function(content, timeout, valign, halign, exclusive)
{
	var vert_align = (valign) ? valign : 'middle';
	var horiz_align = (halign) ? halign : 'center';
	var modal = (exclusive) ? exclusive : false;

	if (!this.m_alert_id)
		this.m_alert_id = 0;

	try 
	{
		this.m_alert_id++;

		// Create status div
		var dv_status = this.m_document.createElement('div');
		dv_status.id = "alib_statusalert_"+this.m_alert_id;
		alib.dom.styleSetClass(dv_status, "statusAlert");
		alib.dom.styleSet(dv_status, "position", "absolute");
		alib.dom.styleSet(dv_status, "top", "150px");
			
		if (typeof content == "string" || typeof content == "number")
			dv_status.innerHTML = content;
		else
			dv_status.appendChild(content);

		this.m_document.body.appendChild(dv_status);

		// Center and display the loading div
		var ht = dv_status.offsetHeight;
		var wd = dv_status.offsetWidth;

		var sptop = alib.dom.getScrollPosTop();
		var spleft = alib.dom.getScrollPosLeft();

		// Set aligned position
		switch (vert_align)
		{
		case "top":
			var tp= sptop + 3;
			break;
		case "middle":
			var tp= sptop +((alib.dom.getClientHeight()-ht)/2)-12;
			break;
		case "bottom":
			var tp= sptop +(alib.dom.getClientHeight()-ht)-12;
			break;
		}

		switch (horiz_align)
		{
		case "left":
			var lt= spleft + 3;
			break;
		case "center":
			var lt= spleft +((alib.dom.getClientWidth()-wd)/2)-12;
			break;
		case "right":
			var lt= spleft +(alib.dom.getClientWidth()-wd)-12;
			break;
		}
		
		alib.dom.styleSet(dv_status, "left", lt + "px");
		alib.dom.styleSet(dv_status, "top", tp + "px");

		if (modal)
			this.Dlg.showOverlay();
		else
			this.Effect.fadein(dv_status, 200);

		dv_status.style.zIndex = "999";
        
		if (timeout)
		{
			var fctn = function()
			{
                dv_status.parentNode.removeChild(dv_status);
                
				if (modal)
					ALib.Dlg.hideOverlay();
			};

			window.setTimeout(fctn, timeout);
		}
		
	} catch (e) {}

	return dv_status;
}

/***********************************************************************
*	Function:	statusHideAlert
*
*	Arguments:	1.	Status alert container
*
*	Purpose:	Show status messages on document (absolute positioned)
*
************************************************************************/
Alib.prototype.statusHideAlert = function(dv_status)
{
	try
	{
		alib.dom.styleSet(dv_status, "visibility", "hidden");
	} catch (e) {}
}

/***********************************************************************
*	Function:	trace
*
*	Purpose:	Create popup and send debug info
*
************************************************************************/
Alib.prototype.trace = function (txt)
{
	// Right now this only works in firefox and opera
	try
	{
		if (!this.m_debug)
			return;

		if (!this.m_debug_wnd || !this.m_debug_wnd.document)
		{
			var attribs = 'top=200,left=100,width=450,height=350,toolbar=no,menubar=no,scrollbars=yes,' +
						  'location=no,directories=no,status=no,resizable=yes';
			this.m_debug_wnd = window.open('about:blank', 'ALIB Debuger', attribs);	

			var frameHtml = "<!DOCTYPE html PUBLIC ";
			frameHtml += "\"-//W3C//DTD XHTML 1.0 Strict//EN\" ";
			frameHtml += "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
			frameHtml += "\n";
			frameHtml += "<html>\n";
			frameHtml += "<head>\n";
			frameHtml += "<title>ALib Debugger</title>\n";
			frameHtml += "</head>\n";
			frameHtml += "<body id='debugger' style='color: white; background-color: black;'>\n";
			frameHtml += "</body>\n";
			frameHtml += "</html>";

			this.m_debug_wnd.document.open();
			this.m_debug_wnd.document.write(frameHtml);
			this.m_debug_wnd.document.close();
		}

		var dv = this.m_debug_wnd.document.createElement("div");
		dv.innerHTML = "<pre>"+txt+"</pre>";

		this.m_debug_wnd.document.body.appendChild(dv);
	}
	catch (e) {}
}

/**
 * Get the base path for alib
 */
Alib.prototype.getBasePath = function()
{
	// Find out the editor directory path, based on its <script> tag.
	var path = ALIB_ROOT || '';

	if (!path)
	{
		var scripts = document.getElementsByTagName('script');

		for ( var i = 0 ; i < scripts.length ; i++ )
		{
			//var match = scripts[i].src.match( /(^|.*[\\\/])alib(?:_basic)?(?:_source)?.js(?:\?.*)?$/i );
			var match = scripts[i].src.match( /(^|.*[\\\/])alib(.*).js(.*)$/i );

			if (match)
			{
				path = match[1];
				break;
			}
		}
	}

	// In IE (only) the script.src string is the raw value entered in the
	// HTML source. Other browsers return the full resolved URL instead.
	if ( path.indexOf(':/') == -1 )
	{
		// Absolute path.
		if ( path.indexOf( '/' ) === 0 )
			path = location.href.match( /^.*?:\/\/[^\/]*/ )[0] + path;
		// Relative path.
		else
			path = location.href.match( /^[^\?]*\/(?:)/ )[0] + path;
	}

	if (!path)
		throw 'The alib installation path could not be automatically detected. Please set the global variable "ALIB_ROOT" before using the library.';

	return path;
}

/**
 * Get the index of a value for a given array
 *
 * @param {Array} arr The array to query
 * @param {mixed} value The value to check for
 * @return {int} will be index of value if found and -1 if not found
 */
Alib.prototype.indexOf = function(arr, value)
{
	return $.inArray(value, arr);
}

// Initialize AntMain();
var alib = new Alib();
var ALib = alib; // for backward compatibility

/**
 * Inherit the prototype methods from one funciton
 *
 * <pre>
 * function ParentClass(a, b) { }
 * ParentClass.prototype.foo = function(a) { }
 *
 * function ChildClass(a, b, c) {
 *   alib.base(this, a, b);
 * }
 * goog.inherits(ChildClass, ParentClass);
 *
 * var child = new ChildClass('a', 'b', 'see');
 * child.foo(); // works
 * </pre>
 *
 * In addition, a parent class' implementation of a method can be invoked
 * as follows:
 *
 * <pre>
 * ChildClass.prototype.foo = function(a) {
 *   ChildClass.parentClass_.foo.call(this, a);
 *   // other code
 * };
 * </pre>
 *
 * @param {Function} childCtor Child class.
 * @param {Function} parentCtor Parent class.
 */
alib.inherits = function(childCtor, parentCtor) 
{
  /** @constructor */
  function tempCtor() {};
  tempCtor.prototype = parentCtor.prototype;
  childCtor.parentClass_ = parentCtor.prototype;
  childCtor.prototype = new tempCtor();
  /** @override */
  childCtor.prototype.constructor = childCtor;
}


/**
 * Call up to the parent class.
 *
 * If this is called from a constructor, then this calls the superclass
 * contructor with arguments 1-N.
 *
 * If this is called from a prototype method, then you must pass
 * the name of the method as the second argument to this function. If
 * you do not, you will get a runtime error. This calls the superclass'
 * method with arguments 2-N.
 *
 * This function only works if you use alib.inherits to express
 * inheritance relationships between your classes.
 *
 * This function is a compiler primitive. At compile-time, the
 * compiler will do macro expansion to remove a lot of
 * the extra overhead that this function introduces. The compiler
 * will also enforce a lot of the assumptions that this function
 * makes, and treat it as a compiler error if you break them.
 *
 * @param {!Object} me Should always be "this".
 * @param {*=} opt_methodName The method name if calling a super method.
 * @param {...*} var_args The rest of the arguments.
 * @return {*} The return value of the superclass method.
 */
alib.base = function(me, opt_methodName, var_args) 
{
	var caller = arguments.callee.caller;

	if (alib.DEBUG) 
	{
		if (!caller) 
		{
			throw Error('arguments.caller not defined.  alib.base() expects not ' +
			'to be running in strict mode. See ' +
			'http://www.ecma-international.org/ecma-262/5.1/#sec-C');
		}
	}

	if (caller.parentClass_) 
	{
		// This is a constructor. Call the superclass constructor.
		return caller.parentClass_.constructor.apply(
			me, Array.prototype.slice.call(arguments, 1)
		);
	}

	var args = Array.prototype.slice.call(arguments, 2);
	var foundCaller = false;
	for (var ctor = me.constructor; ctor; ctor = ctor.parentClass_ && ctor.parentClass_.constructor) 
	{
		if (ctor.prototype[opt_methodName] === caller) {
			foundCaller = true;
		} 
		else if (foundCaller) 
		{
			return ctor.prototype[opt_methodName].apply(me, args);
		}
	}

	// If we did not find the caller in the prototype chain,
	// then one of two things happened:
	// 1) The caller is an instance method.
	// 2) This method was not called by the right caller.
	if (me[opt_methodName] === caller) 
	{
		return me.constructor.prototype[opt_methodName].apply(me, args);
	} 
	else 
	{
		throw Error(
		'alib.base called from a method of one name ' +
		'to a method of a different name');
	}
};

/**
 * Used to manage dependencies at compile time or run-time if a callback is specified
 *
 * @param {string|string[]} mDeps Mixeed, can be a string or array of strings indicating required namespaces
 * @param {function} opt_methodName Optional callback function to be called once all dependencies are loaded
 */
 alib.require = function(mDeps, opt_methodName)
 {
 	// TODO: currently a stub for the compiler
 }

 /**
  * Declare a namespace
  *
  * @param {string} sName The full path of the namespace to provide
  */
alib.declare = function(sName) 
{
	alib.exportPath_(sName);
};


/**
 * Create object structure for a namespace path and make sure existing object are NOT overwritten
 *
 * @param {string} path oath of the object that this file defines.
 * @param {*=} opt_object the object to expose at the end of the path.
 * @param {Object=} opt_objectToExportTo The object to add the path to; default this
 * @private
 */
alib.exportPath_ = function(name, opt_object, opt_objectToExportTo) {
	var parts = name.split('.');
	var cur = opt_objectToExportTo || this;

	// Internet Explorer exhibits strange behavior when throwing errors from
	// methods externed in this manner.
	if (!(parts[0] in cur) && cur.execScript) {
		cur.execScript('var ' + parts[0]);
	}

	// Parentheses added to eliminate strict JS warning in Firefox.
	for (var part; parts.length && (part = parts.shift());) {
		if (!parts.length && opt_object) {
			// last part and we have an object; use it
			cur[part] = opt_object;
		} else if (cur[part]) {
			cur = cur[part];
		} else {
			cur = cur[part] = {};
		}
	}
};


/****************************************************************************
*	
*	Section:	Global Functions
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
if (typeof insertAfter == "undefined")
{
	function insertAfter(parnt, node, referenceNode) 
	{
		if (referenceNode.nextSibling)
			parnt.insertBefore(node, referenceNode.nextSibling);
		else
			parnt.appendChild(node);
	}
}

if (typeof(Math.sqr) == "undefined")
{
	Math.sqr = function (x)
	{
		return x*x;
	};
}

function rgb2hex(value)
{
	var x = 255;
	var hex = '';
	var i;
	var regexp=/([0-9]+)[, ]+([0-9]+)[, ]+([0-9]+)/;
	var array=regexp.exec(value);
	for(i=1;i<4;i++) hex += ('0'+parseInt(array[i]).toString(16)).slice(-2);
	return '#'+hex;
}

function encode_utf8( s )
{
  return unescape(encodeURIComponent(s));
}

function escape_utf8( s )
{
  return encodeURIComponent(s);
}

function decode_utf8(s)
{
  return decodeURIComponent(escape(s));
}

function unescape_utf8(s)
{
  return decodeURIComponent(s);
}

function rawurlencode (str) 
{
    // URL-encodes string  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/rawurlencode
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +      input by: travc
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Michael Grier
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Joris
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // %          note 1: This reflects PHP 5.3/6.0+ behavior
    // %        note 2: Please be aware that this function expects to encode into UTF-8 encoded strings, as found on
    // %        note 2: pages served as UTF-8
    // *     example 1: rawurlencode('Kevin van Zonneveld!');
    // *     returns 1: 'Kevin%20van%20Zonneveld%21'
    // *     example 2: rawurlencode('http://kevin.vanzonneveld.net/');
    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
    // *     example 3: rawurlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
    str = (str + '').toString();
 
    // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
    replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

function rawurldecode (str) 
{
    // Decodes URL-encodes string  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/rawurldecode
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +      input by: travc
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Ratheous
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: Please be aware that this function expects to decode from UTF-8 encoded strings, as found on
    // %        note 1: pages served as UTF-8
    // *     example 1: rawurldecode('Kevin+van+Zonneveld%21');
    // *     returns 1: 'Kevin+van+Zonneveld!'
    // *     example 2: rawurldecode('http%3A%2F%2Fkevin.vanzonneveld.net%2F');
    // *     returns 2: 'http://kevin.vanzonneveld.net/'
    // *     example 3: rawurldecode('http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a');
    // *     returns 3: 'http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a'
    // *     example 4: rawurldecode('-22%97bc%2Fbc');
    // *     returns 4: '-22�bc/bc'
    return decodeURIComponent(str + '');
}

String.prototype.trim = function() 
{
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() 
{
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() 
{
	return this.replace(/\s+$/,"");
}
String.prototype.escapeHTML = function(){
    var result = "";
    for(var i = 0; i < this.length; i++){
        if(this.charAt(i) == "&" 
              && this.length-i-1 >= 4 
              && this.substr(i, 4) != "&amp;"){
            result = result + "&amp;";
        } else if(this.charAt(i)== "<"){
            result = result + "&lt;";
        } else if(this.charAt(i)== ">"){
            result = result + "&gt;";
        } else {
            result = result + this.charAt(i);
        }
    }
    return result;
};


// Base64
var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
 
	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = Base64._utf8_encode(input);
 
		while (i < input.length) {
 
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
 
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
 
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
 
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
 
		}
 
		return output;
	},
 
	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) {
 
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output = output + String.fromCharCode(chr1);
 
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
 
		}
 
		output = Base64._utf8_decode(output);
 
		return output;
 
	},
 
	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
 
		return utftext;
	},
 
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
 
		while ( i < utftext.length ) {
 
			c = utftext.charCodeAt(i);
 
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
 
		}
 
		return string;
	}
 
}

/* Event Functions */

// Add an event to the obj given
// event_name refers to the event trigger, without the "on", like click or mouseover
// func_name refers to the function callback when event is triggered


// Get the obj that starts the event
function getElement(evt){
	if (window.event){
		return window.event.srcElement;
	}else{
		return evt.currentTarget;
	}
}
// Get the obj that triggers off the event
function getTargetElement(evt){
	if (window.event){
		return window.event.srcElement;
	}else{
		return evt.target;
	}
}
// For IE only, stops the obj from being selected
function stopSelect(obj){
	if (typeof obj.onselectstart != 'undefined'){
		alib.dom.addEvent(obj,"selectstart",function(){ return false;});
	}
}



/*    Escape function   */
String.prototype.addslashes = function(){
	return this.replace(/(["\\\.\|\[\]\^\*\+\?\$\(\)])/g, '\\$1');
}
String.prototype.trim = function () {
    return this.replace(/^\s*(\S*(\s+\S+)*)\s*$/, "$1");
};
/* --- Escape --- */

/* Offset position from top of the screen */
function curTop(obj){
	toreturn = 0;
	while(obj){
		toreturn += obj.offsetTop;
		obj = obj.offsetParent;
	}
	return toreturn;
}
function curLeft(obj){
	toreturn = 0;
	while(obj){
		toreturn += obj.offsetLeft;
		obj = obj.offsetParent;
	}
	return toreturn;
}
/* ------ End of Offset function ------- */

/* Types Function */

// is a given input a number?
function isNumber(a) {
    return typeof a == 'number' && isFinite(a);
}

/* Object Functions */

function replaceHTML(obj,text){
	while(el = obj.childNodes[0]){
		obj.removeChild(el);
	};
	obj.appendChild(document.createTextNode(text));
}



Number.prototype.format = function(format) {
   var hasComma = -1 < format.indexOf(','),
     psplit = format.stripNonNumeric().split('.'),
     that = this;
  
   // compute precision
   if (1 < psplit.length) {
     // fix number precision
     that = that.toFixed(psplit[1].length);
   }
   // error: too many periods
   else if (2 < psplit.length) {
     throw('NumberFormatException: invalid format, formats should have no more than 1 period: ' + format);
   }
   // remove precision
   else {
     that = that.toFixed(0);
   } 
  
   // get the string now that precision is correct
   var fnum = that.toString();
 
   // format has comma, then compute commas
   if (hasComma) {
     // remove precision for computation
     psplit = fnum.split('.');
  
     var cnum = psplit[0],
       parr = [],
       j = cnum.length,
       m = Math.floor(j / 3),
       n = cnum.length % 3 || 3; // n cannot be ZERO or causes infinite loop
  
     // break the number into chunks of 3 digits; first chunk may be less than 3
     for (var i = 0; i < j; i += n) {
       if (i != 0) {n = 3;}
       parr[parr.length] = cnum.substr(i, n);
       m -= 1;
     }
  
     // put chunks back together, separated by comma
     fnum = parr.join(',');
  
    // add the precision back in
    if (psplit[1]) {fnum += '.' + psplit[1];}
  } 
 
  // replace the number portion of the format with fnum
  return format.replace(/[\d,?\.?]+/, fnum);
};


// Number Functions
//
// obj.value = new NumberFormat(obj.value, 2).toFormatted();	
// nf.setCurrency(true); // add $
// .toUnformatted = get the original number
//
// -----------------------------------------------------------------------
function NumberFormat(num, inputDecimal)
{
	this.VERSION = 'Number Format v1.5.4';
	this.COMMA = ',';
	this.PERIOD = '.';
	this.DASH = '-'; 
	this.LEFT_PAREN = '('; 
	this.RIGHT_PAREN = ')'; 
	this.LEFT_OUTSIDE = 0; 
	this.LEFT_INSIDE = 1;  
	this.RIGHT_INSIDE = 2;  
	this.RIGHT_OUTSIDE = 3;  
	this.LEFT_DASH = 0; 
	this.RIGHT_DASH = 1; 
	this.PARENTHESIS = 2; 
	this.NO_ROUNDING = -1 
	this.num;
	this.numOriginal;
	this.hasSeparators = false;  
	this.separatorValue;  
	this.inputDecimalValue; 
	this.decimalValue;  
	this.negativeFormat; 
	this.negativeRed; 
	this.hasCurrency;  
	this.currencyPosition;  
	this.currencyValue;  
	this.places;
	this.roundToPlaces; 
	this.truncate; 
	this.setNumber = setNumberNF;
	this.toUnformatted = toUnformattedNF;
	this.setInputDecimal = setInputDecimalNF; 
	this.setSeparators = setSeparatorsNF; 
	this.setCommas = setCommasNF;
	this.setNegativeFormat = setNegativeFormatNF; 
	this.setNegativeRed = setNegativeRedNF; 
	this.setCurrency = setCurrencyNF;
	this.setCurrencyPrefix = setCurrencyPrefixNF;
	this.setCurrencyValue = setCurrencyValueNF; 
	this.setCurrencyPosition = setCurrencyPositionNF; 
	this.setPlaces = setPlacesNF;
	this.toFormatted = toFormattedNF;
	this.toPercentage = toPercentageNF;
	this.getOriginal = getOriginalNF;
	this.moveDecimalRight = moveDecimalRightNF;
	this.moveDecimalLeft = moveDecimalLeftNF;
	this.getRounded = getRoundedNF;
	this.preserveZeros = preserveZerosNF;
	this.justNumber = justNumberNF;
	this.expandExponential = expandExponentialNF;
	this.getZeros = getZerosNF;
	this.moveDecimalAsString = moveDecimalAsStringNF;
	this.moveDecimal = moveDecimalNF;
	this.addSeparators = addSeparatorsNF;
	if (inputDecimal == null) {
	this.setNumber(num, this.PERIOD);
	} else {
	this.setNumber(num, inputDecimal); 
	}
	this.setCommas(true);
	this.setNegativeFormat(this.LEFT_DASH); 
	this.setNegativeRed(false); 
	this.setCurrency(false); 
	this.setCurrencyPrefix('$');
	this.setPlaces(2);
}

function setInputDecimalNF(val)
{
	this.inputDecimalValue = val;
}

function setNumberNF(num, inputDecimal)
{
	if (inputDecimal != null) 
	{
		this.setInputDecimal(inputDecimal); 
	}
	this.numOriginal = num;
	this.num = this.justNumber(num);
}
function toUnformattedNF()
{
	return (this.num);
}

function getOriginalNF()
{
	return (this.numOriginal);
}

function setNegativeFormatNF(format)
{
	this.negativeFormat = format;
}
function setNegativeRedNF(isRed)
{
	this.negativeRed = isRed;
}
function setSeparatorsNF(isC, separator, decimal)
{
	this.hasSeparators = isC;
	if (separator == null) separator = this.COMMA;
	if (decimal == null) decimal = this.PERIOD;
	if (separator == decimal) 
	{
		this.decimalValue = (decimal == this.PERIOD) ? this.COMMA : this.PERIOD;
	} 
	else 
	{
		this.decimalValue = decimal;
	}
	this.separatorValue = separator;
}
function setCommasNF(isC)
{
	this.setSeparators(isC, this.COMMA, this.PERIOD);
}
function setCurrencyNF(isC)
{
	this.hasCurrency = isC;
}

function setCurrencyValueNF(val)
{
	this.currencyValue = val;
}
function setCurrencyPrefixNF(cp)
{
	this.setCurrencyValue(cp);
	this.setCurrencyPosition(this.LEFT_OUTSIDE);
}

function setCurrencyPositionNF(cp)
{
	this.currencyPosition = cp
}

function setPlacesNF(p, tr)
{
	this.roundToPlaces = !(p == this.NO_ROUNDING); 
	this.truncate = (tr != null && tr); 
	this.places = (p < 0) ? 0 : p; 
}

function addSeparatorsNF(nStr, inD, outD, sep)
{
	nStr += '';
	var dpos = nStr.indexOf(inD);
	var nStrEnd = '';
	if (dpos != -1) 
	{
		nStrEnd = outD + nStr.substring(dpos + 1, nStr.length);
		nStr = nStr.substring(0, dpos);
	}

	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(nStr)) 
	{
		nStr = nStr.replace(rgx, '$1' + sep + '$2');
	}
	return nStr + nStrEnd;
}
function toFormattedNF()
{	
var pos;
var nNum = this.num; 
var nStr;            
var splitString = new Array(2);   
if (this.roundToPlaces) {
nNum = this.getRounded(nNum);
nStr = this.preserveZeros(Math.abs(nNum)); 
} else {
nStr = this.expandExponential(Math.abs(nNum)); 
}
if (this.hasSeparators) {
nStr = this.addSeparators(nStr, this.PERIOD, this.decimalValue, this.separatorValue);
} else {
nStr = nStr.replace(new RegExp('\\' + this.PERIOD), this.decimalValue); 
}
var c0 = '';
var n0 = '';
var c1 = '';
var n1 = '';
var n2 = '';
var c2 = '';
var n3 = '';
var c3 = '';
var negSignL = (this.negativeFormat == this.PARENTHESIS) ? this.LEFT_PAREN : this.DASH;
var negSignR = (this.negativeFormat == this.PARENTHESIS) ? this.RIGHT_PAREN : this.DASH;
if (this.currencyPosition == this.LEFT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c0 = this.currencyValue;
} else if (this.currencyPosition == this.LEFT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c1 = this.currencyValue;
}
else if (this.currencyPosition == this.RIGHT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c2 = this.currencyValue;
}
else if (this.currencyPosition == this.RIGHT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c3 = this.currencyValue;
}
nStr = c0 + n0 + c1 + n1 + nStr + n2 + c2 + n3 + c3;
if (this.negativeRed && nNum < 0) {
nStr = '<font color="red">' + nStr + '</font>';
}
return (nStr);
}
function toPercentageNF()
{
nNum = this.num * 100;
nNum = this.getRounded(nNum);
return nNum + '%';
}
function getZerosNF(places)
{
var extraZ = '';
var i;
for (i=0; i<places; i++) {
extraZ += '0';
}
return extraZ;
}
function expandExponentialNF(origVal)
{
if (isNaN(origVal)) return origVal;
var newVal = parseFloat(origVal) + ''; 
var eLoc = newVal.toLowerCase().indexOf('e');
if (eLoc != -1) {
var plusLoc = newVal.toLowerCase().indexOf('+');
var negLoc = newVal.toLowerCase().indexOf('-', eLoc); 
var justNumber = newVal.substring(0, eLoc);
if (negLoc != -1) {
var places = newVal.substring(negLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, true, parseInt(places));
} else {
if (plusLoc == -1) plusLoc = eLoc;
var places = newVal.substring(plusLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, false, parseInt(places));
}
newVal = justNumber;
}
return newVal;
} 
function moveDecimalRightNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, false);
} else {
newVal = this.moveDecimal(val, false, places);
}
return newVal;
}
function moveDecimalLeftNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, true);
} else {
newVal = this.moveDecimal(val, true, places);
}
return newVal;
}
function moveDecimalAsStringNF(val, left, places)
{
var spaces = (arguments.length < 3) ? this.places : places;
if (spaces <= 0) return val; 
var newVal = val + '';
var extraZ = this.getZeros(spaces);
var re1 = new RegExp('([0-9.]+)');
if (left) {
newVal = newVal.replace(re1, extraZ + '$1');
var re2 = new RegExp('(-?)([0-9]*)([0-9]{' + spaces + '})(\\.?)');		
newVal = newVal.replace(re2, '$1$2.$3');
} else {
var reArray = re1.exec(newVal); 
if (reArray != null) {
newVal = newVal.substring(0,reArray.index) + reArray[1] + extraZ + newVal.substring(reArray.index + reArray[0].length); 
}
var re2 = new RegExp('(-?)([0-9]*)(\\.?)([0-9]{' + spaces + '})');
newVal = newVal.replace(re2, '$1$2$4.');
}
newVal = newVal.replace(/\.$/, ''); 
return newVal;
}
function moveDecimalNF(val, left, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimalAsString(val, left);
} else {
newVal = this.moveDecimalAsString(val, left, places);
}
return parseFloat(newVal);
}
function getRoundedNF(val)
{
val = this.moveDecimalRight(val);
if (this.truncate) {
val = val >= 0 ? Math.floor(val) : Math.ceil(val); 
} else {
val = Math.round(val);
}
val = this.moveDecimalLeft(val);
return val;
}
function preserveZerosNF(val)
{
var i;
val = this.expandExponential(val);
if (this.places <= 0) return val; 
var decimalPos = val.indexOf('.');
if (decimalPos == -1) {
val += '.';
for (i=0; i<this.places; i++) {
val += '0';
}
} else {
var actualDecimals = (val.length - 1) - decimalPos;
var difference = this.places - actualDecimals;
for (i=0; i<difference; i++) {
val += '0';
}
}
return val;
}
function justNumberNF(val)
{
newVal = val + '';
var isPercentage = false;
if (newVal.indexOf('%') != -1) {
newVal = newVal.replace(/\%/g, '');
isPercentage = true; 
}
var re = new RegExp('[^\\' + this.inputDecimalValue + '\\d\\-\\+\\(\\)eE]', 'g');	
newVal = newVal.replace(re, '');
var tempRe = new RegExp('[' + this.inputDecimalValue + ']', 'g');
var treArray = tempRe.exec(newVal); 
if (treArray != null) {
var tempRight = newVal.substring(treArray.index + treArray[0].length); 
newVal = newVal.substring(0,treArray.index) + this.PERIOD + tempRight.replace(tempRe, ''); 
}
if (newVal.charAt(newVal.length - 1) == this.DASH ) {
newVal = newVal.substring(0, newVal.length - 1);
newVal = '-' + newVal;
}
else if (newVal.charAt(0) == this.LEFT_PAREN
&& newVal.charAt(newVal.length - 1) == this.RIGHT_PAREN) {
newVal = newVal.substring(1, newVal.length - 1);
newVal = '-' + newVal;
}
newVal = parseFloat(newVal);
if (!isFinite(newVal)) {
newVal = 0;
}
if (isPercentage) {
newVal = this.moveDecimalLeft(newVal, 2);
}
return newVal;
}

