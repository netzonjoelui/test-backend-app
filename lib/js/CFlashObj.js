/*======================================================================================
	
	Module:		CFlashObj

	Purpose:	Encapsulate the creation of flash ojbects for inclusion of swf media
				in ANT.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Usage:		

======================================================================================*/
function CFlashObj(id_name, movie_src)
{
	this.m_objStr = "";
	this.m_embedAttrs = new Object();
	this.m_objAttrs = new Object();
	this.m_params = new Object();

	this.isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
	this.isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
	this.isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;

	// Set defaults
	this.setAttribute("id", id_name);
	this.setAttribute("name", id_name);
	this.setAttribute("src", movie_src);
	this.setAttribute("movie", movie_src);
	this.setAttribute("classid", "clsid:166B1BCA-3F9C-11CF-8075-444553540000");
	this.setAttribute("codebase", "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0");
	this.setAttribute("quality", "high");
	this.setAttribute("pluginspage", "http://www.adobe.com/go/getflashplayer");
	this.setAttribute("align", "middle");
	this.setAttribute("play", "true");
	this.setAttribute("loop", "true");
	this.setAttribute("scale", "showall");
	this.setAttribute("wmode", "window");
	this.setAttribute("devicefont", "false");
	this.setAttribute("bgcolor", "#ffffff");
	this.setAttribute("name", "window");
	this.setAttribute("menu", "true");
	this.setAttribute("allowFullScreen", "false");
	this.setAttribute("allowScriptAccess", "sameDomain");
	this.setAttribute("salign", "");
	this.setAttribute("width", "640");
	this.setAttribute("height", "480");
}

CFlashObj.prototype.generateObj = function(objAttrs, params, embedAttrs)
{
	var str = '';
	if (this.isIE && this.isWin && !this.isOpera)
	{
		str += '<object ';
		for (var i in objAttrs)
		{
			str += i + '="' + objAttrs[i] + '" ';
		}

		str += '>';
		for (var i in params)
		{
			str += '<param name="' + i + '" value="' + params[i] + '" /> ';
		}

		str += '</object>';
	}
	else
	{
		str += '<embed ';
		for (var i in embedAttrs)
		{
			str += i + '="' + embedAttrs[i] + '" ';
		}
		str += '> </embed>';
	}

	return str;
}

CFlashObj.prototype.getObjHtml = function()
{
	return this.generateObj(this.m_objAttrs, this.m_params, this.m_embedAttrs);
}

CFlashObj.prototype.setAttribute = function(name, value)
{
	var arg = name.toLowerCase();
	var ext = ".swf";
	var srcParamName = "src";
  
    switch (arg)
	{
	case "pluginspage":
		this.m_embedAttrs[arg] = value;
		break;
	case "src":
	case "movie":	
		value = AC_AddExtension(value, ext);
		this.m_embedAttrs["src"] = value;
		this.m_params[srcParamName] = value;
		break;
	case "onafterupdate":
	case "onbeforeupdate":
	case "onblur":
	case "oncellchange":
	case "onclick":
	case "ondblclick":
	case "ondrag":
	case "ondragend":
	case "ondragenter":
	case "ondragleave":
	case "ondragover":
	case "ondrop":
	case "onfinish":
	case "onfocus":
	case "onhelp":
	case "onmousedown":
	case "onmouseup":
	case "onmouseover":
	case "onmousemove":
	case "onmouseout":
	case "onkeypress":
	case "onkeydown":
	case "onkeyup":
	case "onload":
	case "onlosecapture":
	case "onpropertychange":
	case "onreadystatechange":
	case "onrowsdelete":
	case "onrowenter":
	case "onrowexit":
	case "onrowsinserted":
	case "onstart":
	case "onscroll":
	case "onbeforeeditfocus":
	case "onactivate":
	case "onbeforedeactivate":
	case "ondeactivate":
	case "type":
	case "codebase":
	case "id":
	case "classid":
		this.m_objAttrs[arg] = value;
	break;
	case "width":
	case "height":
	case "align":
	case "vspace": 
	case "hspace":
	case "class":
	case "title":
	case "accesskey":
	case "name":
	case "tabindex":
	case "type":
		this.m_embedAttrs[arg] = this.m_objAttrs[arg] = value;
		break;
	default:
		this.m_embedAttrs[arg] = this.m_params[arg] = value;
    }
}

//v1.7
// Flash Player Version Detection
// Detect Client Browser type
// Copyright 2005-2007 Adobe Systems Incorporated.  All rights reserved.
var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;

function ControlVersion()
{
	var version;
	var axo;
	var e;

	// NOTE : new ActiveXObject(strFoo) throws an exception if strFoo isn't in the registry

	try {
		// version will be set for 7.X or greater players
		axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
		version = axo.GetVariable("$version");
	} catch (e) {
	}

	if (!version)
	{
		try {
			// version will be set for 6.X players only
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
			
			// installed player is some revision of 6.0
			// GetVariable("$version") crashes for versions 6.0.22 through 6.0.29,
			// so we have to be careful. 
			
			// default to the first public version
			version = "WIN 6,0,21,0";

			// throws if AllowScripAccess does not exist (introduced in 6.0r47)		
			axo.AllowScriptAccess = "always";

			// safe to call for 6.0r47 or greater
			version = axo.GetVariable("$version");

		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 4.X or 5.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
			version = axo.GetVariable("$version");
		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 3.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
			version = "WIN 3,0,18,0";
		} catch (e) {
		}
	}

	if (!version)
	{
		try {
			// version will be set for 2.X player
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
			version = "WIN 2,0,0,11";
		} catch (e) {
			version = -1;
		}
	}
	
	return version;
}

// JavaScript helper required to detect Flash Player PlugIn version information
function GetSwfVer()
{
	// NS/Opera version >= 3 check for Flash plugin in plugin array
	var flashVer = -1;
	
	if (navigator.plugins != null && navigator.plugins.length > 0) {
		if (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
			var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
			var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
			var descArray = flashDescription.split(" ");
			var tempArrayMajor = descArray[2].split(".");			
			var versionMajor = tempArrayMajor[0];
			var versionMinor = tempArrayMajor[1];
			var versionRevision = descArray[3];
			if (versionRevision == "") {
				versionRevision = descArray[4];
			}
			if (versionRevision[0] == "d") {
				versionRevision = versionRevision.substring(1);
			} else if (versionRevision[0] == "r") {
				versionRevision = versionRevision.substring(1);
				if (versionRevision.indexOf("d") > 0) {
					versionRevision = versionRevision.substring(0, versionRevision.indexOf("d"));
				}
			}
			var flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
		}
	}
	// MSN/WebTV 2.6 supports Flash 4
	else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1) flashVer = 4;
	// WebTV 2.5 supports Flash 3
	else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1) flashVer = 3;
	// older WebTV supports Flash 2
	else if (navigator.userAgent.toLowerCase().indexOf("webtv") != -1) flashVer = 2;
	else if ( isIE && isWin && !isOpera ) {
		flashVer = ControlVersion();
	}	
	return flashVer;
}

// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
{
	versionStr = GetSwfVer();
	if (versionStr == -1 ) {
		return false;
	} else if (versionStr != 0) {
		if(isIE && isWin && !isOpera) {
			// Given "WIN 2,0,0,11"
			tempArray         = versionStr.split(" "); 	// ["WIN", "2,0,0,11"]
			tempString        = tempArray[1];			// "2,0,0,11"
			versionArray      = tempString.split(",");	// ['2', '0', '0', '11']
		} else {
			versionArray      = versionStr.split(".");
		}
		var versionMajor      = versionArray[0];
		var versionMinor      = versionArray[1];
		var versionRevision   = versionArray[2];

        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
		if (versionMajor > parseFloat(reqMajorVer)) {
			return true;
		} else if (versionMajor == parseFloat(reqMajorVer)) {
			if (versionMinor > parseFloat(reqMinorVer))
				return true;
			else if (versionMinor == parseFloat(reqMinorVer)) {
				if (versionRevision >= parseFloat(reqRevision))
					return true;
			}
		}
		return false;
	}
}

function AC_AddExtension(src, ext)
{
  if (src.indexOf('?') != -1)
    return src.replace(/\?/, ext+'?'); 
  else
    return src + ext;
}

function AC_Generateobj(objAttrs, params, embedAttrs) 
{ 
  var str = '';
  if (isIE && isWin && !isOpera)
  {
    str += '<object ';
    for (var i in objAttrs)
    {
      str += i + '="' + objAttrs[i] + '" ';
    }
    str += '>';
    for (var i in params)
    {
      str += '<param name="' + i + '" value="' + params[i] + '" /> ';
    }
    str += '</object>';
  }
  else
  {
    str += '<embed ';
    for (var i in embedAttrs)
    {
      str += i + '="' + embedAttrs[i] + '" ';
    }
    str += '> </embed>';
  }

  document.write(str);
}

function AC_FL_RunContent(){
  var ret = 
    AC_GetArgs
    (  arguments, ".swf", "movie", "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
     , "application/x-shockwave-flash"
    );
  AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
}

function AC_SW_RunContent(){
  var ret = 
    AC_GetArgs
    (  arguments, ".dcr", "src", "clsid:166B1BCA-3F9C-11CF-8075-444553540000"
     , null
    );
  AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
}

function AC_GetArgs(args, ext, srcParamName, classid, mimeType){
  var ret = new Object();
  ret.embedAttrs = new Object();
  ret.params = new Object();
  ret.objAttrs = new Object();
  
  for (var i=0; i < args.length; i=i+2)
  {
    var currArg = args[i].toLowerCase();    

    switch (currArg){	
      case "classid":
        break;
      case "pluginspage":
        ret.embedAttrs[args[i]] = args[i+1];
        break;
      case "src":
      case "movie":	
        args[i+1] = AC_AddExtension(args[i+1], ext);
        ret.embedAttrs["src"] = args[i+1];
        ret.params[srcParamName] = args[i+1];
        break;
      case "onafterupdate":
      case "onbeforeupdate":
      case "onblur":
      case "oncellchange":
      case "onclick":
      case "ondblclick":
      case "ondrag":
      case "ondragend":
      case "ondragenter":
      case "ondragleave":
      case "ondragover":
      case "ondrop":
      case "onfinish":
      case "onfocus":
      case "onhelp":
      case "onmousedown":
      case "onmouseup":
      case "onmouseover":
      case "onmousemove":
      case "onmouseout":
      case "onkeypress":
      case "onkeydown":
      case "onkeyup":
      case "onload":
      case "onlosecapture":
      case "onpropertychange":
      case "onreadystatechange":
      case "onrowsdelete":
      case "onrowenter":
      case "onrowexit":
      case "onrowsinserted":
      case "onstart":
      case "onscroll":
      case "onbeforeeditfocus":
      case "onactivate":
      case "onbeforedeactivate":
      case "ondeactivate":
      case "type":
      case "codebase":
      case "id":
        ret.objAttrs[args[i]] = args[i+1];
        break;
      case "width":
      case "height":
      case "align":
      case "vspace": 
      case "hspace":
      case "class":
      case "title":
      case "accesskey":
      case "name":
      case "tabindex":
        ret.embedAttrs[args[i]] = ret.objAttrs[args[i]] = args[i+1];
        break;
      default:
        ret.embedAttrs[args[i]] = ret.params[args[i]] = args[i+1];
    }
  }
  ret.objAttrs["classid"] = classid;
  if (mimeType) ret.embedAttrs["type"] = mimeType;
  return ret;
}

