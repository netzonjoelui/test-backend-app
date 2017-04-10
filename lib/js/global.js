// Colors array used for all groups (mail, calendar, contacts, projects, files, notes, customers, etc...)
/*
["Poly Green", "9D9C6E", "000000"], ["Shallow Bay", "C2B274", "000000"], ["Milk Chocolate", "A18E6E", "000000"], 
["Magdalene", "7C6B64", "000000"], ["Adelaide", "5A3A45", "000000"],

["Butter", "EBD877", "000000"], ["Stone", "C4BEAE", "000000"], ["Muggy", "ACC1B2", "000000"], 
["Lake Blue", "79A29A", "000000"], ["Gray Green", "AFCD98", "000000"],

["Soft Green", "B5C666", "000000"], ["Mustard", "C2AE19", "000000"], ["Rusty", "C6891E", "ffffff"]
*/
var G_GROUP_COLORS = [
						["Blue", "2A4BD7", "ffffff"], ["Black", "000000", "81c57a"], ["Gray", "575757", "ffffff"], 
						["Light Gray", "A0A0A0", "ffffff"], ["White", "FFFFFF", "000000"], 
						["Green", "1D6914", "ffee33"], ["Brown", "814A19", "ffee33"], ["Purple", "8126C0", "ffcdf3"], 
						["Light Blue", "9DAFFF", "000000"], ["Light Green", "81C57A", "000000"],
						["Tan", "E9DEBB", "814a19"], ["Red", "AD2323", "ffee33"], ["Teal", "29D0D0", "000000"],
						["Yellow", "FFEE33", "000000"], ["Orange", "FF9233", "ffffff"], ["Pink", "FFCDF3", "ad2323"]
					  ];



// =====================================================================
// Functions
// =====================================================================
function copy(text2copy) 
{
  if (window.clipboardData) 
  {
    window.clipboardData.setData("Text",text2copy);
  } 
  else 
  {
    var flashcopier = 'flashcopier';
    if(!document.getElementById(flashcopier)) 
	{
      var divholder = document.createElement('div');
      divholder.id = flashcopier;
      document.body.appendChild(divholder);
    }

    document.getElementById(flashcopier).innerHTML = '';
    var divinfo = '<embed src="/flash/_clipboard.swf" FlashVars="clipboard='+escape(text2copy)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>';
    document.getElementById(flashcopier).innerHTML = divinfo;
  }
}

/*************************************************************************
*	Function:	getColorTextForGroup
*
*	Purpose:	Get associated text color for group
**************************************************************************/
function getColorTextForGroup(color)
{
	var ret = "000000";
	for (var j = 0; j < G_GROUP_COLORS.length; j++)
	{
		if (G_GROUP_COLORS[j][1] == color)
			ret = G_GROUP_COLORS[j][2];
	}

	return ret;
}

/*************************************************************************
*	Function:	loadSupportDoc
*
*	Purpose:	This may be expanded in the future to load a support
*				document inline, but for now it will just open a page
*				going to our public support site.
**************************************************************************/
function loadSupportDoc(docid)
{
  window.open('http://www.netric.com/support/solutions/'+docid);
  return false;
}

/*************************************************************************
*	Function:	loadDacl
*
*	Purpose:	This may be expanded in the future to load a support
*				document inline, but for now it will just open a new window.
**************************************************************************/
function loadDacl(dacl, name, inheritfrom)
{
	var inh = (typeof inheritfrom != "undefined") ? inheritfrom : null;

	var dacl = new DaclEdit(name, inh);
	dacl.showDialog();
}

/*************************************************************************************
*	Description:	objectSetNameLabel	
*
*	Purpose:		Set innerHTML of con with the name of an object
**************************************************************************************/
function objectSetNameLabel(obj_type, id, con)
{
	if (obj_type && id)
	{
        ajax = new CAjax('json');
        ajax.cbData.con = con;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
                this.cbData.con.innerHTML = unescape(ret);
        };
        var args = [["obj_type", obj_type], ["id", id]];
        ajax.exec("/controller/Object/getObjName", args);
	}
}

/*************************************************************************
*	Function:	objectSplitLbl
*
*	Purpose:	Split a label string into {type, id}
**************************************************************************/
function objectSplitValue(lblstr)
{
	var lbl_parts = lblstr.split(":");
	return {typeTitle:lbl_parts[0], objTitle:lbl_parts[1]};
}

/*************************************************************************
*	Function:	objectSplitLbl
*
*	Purpose:	Split a label string into title:id
**************************************************************************/
function objectSplitLbl(lblstr)
{
	var lbl_parts = lblstr.split(":");
	return {typeTitle:lbl_parts[0], objTitle:lbl_parts[1]};
}

/*************************************************************************
* 	@depricated Now use Ant.EntityDefinitionLoader
*
*	Function:	objectPreloadDef
*
*	Purpose:	Preload the definition for an object
*
*	Arguments:	type:string = object type name
*				force:bool = set to true to force a reload of cache
**************************************************************************/
function objectPreloadDef(type, force)
{
	// Make sure not already preloaded
	for (var i = 0; i < CAntObjectDefs.length; i++)
	{
		if (CAntObjectDefs[i].name == type)
		{
			if (force) // update cache
			{
				CAntObjectDefs.splice(i, 1);
				AntObjectForms.clearCache(type);
			}
			else
				return;
		}
	}

	// Get object definition
	var ajax = new CAjax();
	ajax.onload = function(root)
	{
		var def = new Object();
		def.name = type;
		def.root = root;
		CAntObjectDefs[CAntObjectDefs.length] = def;
	}

	var url = "/objects/xml_get_objectdef.php?oname=" + type;
	ajax.exec(url);
}

/**
 * Load an object loader form in a new dialog
 *
 * @param {string} obj_type The object type name
 * @param {string} oid The unique id of the object to load - optional
 * @param {DOMElement} con Depricated
 * @param {string[[]]} assoc Depricated
 * @param {Array} params Two dim array of field values to set
 * @return {AntObjectLoader} A reference to the loader
 */
function loadObjectForm(obj_type, oid, con, assoc, params)
{
	var params = (params) ? params : new Array();

	var dlg = new CDialog("");

	var oid = (oid) ? oid : "";

	var url = '/obj/' + obj_type;
	if (oid)
		url += '/' + oid;

	var objfrmCon = alib.dom.createElement("div", document.body);
	alib.dom.styleSet(objfrmCon, "height", "100%");
	alib.dom.styleSet(objfrmCon, "overflow", "auto");
	objfrmCon.cls = this;
	objfrmCon.dlg = dlg;
	objfrmCon.close = function()
	{                    
		this.dlg.hide();
	}

	// Print object loader 
	var ol = new AntObjectLoader(obj_type, oid);

	// Set params
	for (var i = 0; i < params.length; i++)
	{
		ol.setValue(params[i][0], params[i][1]);
	}
			
	ol.print(objfrmCon, this.isPopup);
		
	ol.objfrmCon = objfrmCon;
	ol.objBrwsrCls = this;
	alib.events.listen(ol, "close", function(evt) {
		evt.data.frm.close();
	}, {frm:objfrmCon});
	ol.onSave = function()
	{
	}
	ol.onRemove = function()
	{
	}

	dlg.customDialog(objfrmCon, 900, getWorkspaceHeight());

	return ol;

	/* Old New Window Code.
	 * Still may use someday for separate param to open in actual new window
	var url = '/obj/'+obj_type;
	if (oid)
		url += '/'+oid;
	
	var strWindName = (obj_type.replace(".", ""))+((oid)?oid:'new');
	var condv = alib.dom.createElement("div", alib.dom.m_document.body);
	alib.dom.styleSet(condv, "display", "none");
	alib.dom.styleSet(condv, "position", "absolute");
	
	var form = alib.dom.createElement("form", condv);
	form.setAttribute("method", "post");
	form.setAttribute("target", strWindName);
	form.setAttribute("action", url);
	
	if (params)
	{
		for (var i = 0; i < params.length; i++)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", params[i][0]);
			hiddenField.setAttribute("value", params[i][1]);
			form.appendChild(hiddenField);
		}
	}
		
	window.open(url, strWindName);

	if (form)
		form.submit();
	else
		alert('You must allow popups for this map to work.');
	
	alib.dom.m_document.body.removeChild(condv);
	*/
}

/*==========================================================================#
# * Function for adding a Filter to an Input Field                          #
# * @param  : [filterType  ] Type of filter 0=>Alpha, 1=>Num, 2=>AlphaNum   #
# * @param  : [evt         ] The Event Object                               #
# * @param  : [allowDecimal] To allow Decimal Point set this to true        #
# * @param  : [allowCustom ] Custom Characters that are to be allowed       #
#==========================================================================*/
function filterInput(filterType, evt, allowDecimal, allowCustom)
{
	var keyCode, Char, inputField, filter = '';
	var alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var num   = '0123456789';
	// Get the Key Code of the Key pressed if possible else - allow
	if(window.event){
		keyCode = window.event.keyCode;
		evt = window.event;
	}else if (evt)keyCode = evt.which;
	else return true;
	// Setup the allowed Character Set
	if(filterType == 0) filter = alpha;
	else if(filterType == 1) filter = num;
	else if(filterType == 2) filter = alpha + num;
	if(allowCustom)filter += allowCustom;
	if(filter == '')return true;
	// Get the Element that triggered the Event
	inputField = evt.srcElement ? evt.srcElement : evt.target || evt.currentTarget;
	// If the Key Pressed is a CTRL key like Esc, Enter etc - allow
	if((keyCode==null) || (keyCode==0) || (keyCode==8) || (keyCode==9) || (keyCode==13) || (keyCode==27) )return true;
	// Get the Pressed Character
	Char = String.fromCharCode(keyCode);
	// If the Character is a number - allow
	if((filter.indexOf(Char) > -1)) return true;
	// Else if Decimal Point is allowed and the Character is '.' - allow
	else if(filterType == 1 && allowDecimal && (Char == '.') && inputField.value.indexOf('.') == -1)return true;
	else return false;
}

/*************************************************************************
*	Function:	getWorkspaceHeight
*
*	Purpose:	Get the hieght of the workspace for 100% height tools
**************************************************************************/
function getWorkspaceHeight()
{
	var total = alib.dom.getClientHeight();

	// Subtract the height of the header
	var header = document.getElementById("appheader");
	if (header)
		total -= alib.dom.getElementHeight(header, true);

	// Subtract the height of the tabs
	var tabs = document.getElementById("appnav");
	if (tabs)
		total -= alib.dom.getElementHeight(tabs, true);
	//total -= 10; // for top margin

	// Now get application title height if set
	if (typeof Ant != "undefined")
	{
		var app = Ant.getActiveApp();
		if (app)
		{
			total -= app.getAppHeaderHeight();
		}
	}

	// Subtract for margin on the bottom
	//total -= 10;

	//ALib.m_debug = true;
	//ALib.trace("WorkspaceHeight: "+total);
	return total;
}

/*************************************************************************
*	Function:	getThreadClass
*
*	Purpose:	Get a thread class name for a send/uid
**************************************************************************/
var g_EmailThreadGetAdressColorCache = new Array();
function EmailThreadGetAdressColor(senderaddress, useraddress)
{
	/*
	global $EML_THD_COLORS, $EML_THD_CLRS_CUR_INDX;
	if (!$EML_THD_CLRS_CUR_INDX) 
		$EML_THD_CLRS_CUR_INDX = 0;
	if (!is_array($EML_THD_COLORS))
		$EML_THD_COLORS = array();
	$retval = '';
	$getindex = ($type == 'expanded') ? 0 : 1;
	
	$sederCol = array("EmailThreadSenderExp", "EmailThreadSenderCol");
	$arrCol = array(array("EmailThreadRand1Exp", "EmailThreadRand1Col"), 
					array("EmailThreadRand2Exp", "EmailThreadRand2Col"), 
					array("EmailThreadRand3Exp", "EmailThreadRand3Col"), 
					array("EmailThreadRand4Exp", "EmailThreadRand4Col"), 
					array("EmailThreadRand5Exp", "EmailThreadRand5Col"), 
					array("EmailThreadRand6Exp", "EmailThreadRand6Col"), 
					array("EmailThreadRand7Exp", "EmailThreadRand7Col"), 
					array("EmailThreadRand8Exp", "EmailThreadRand8Col"), 
					array("EmailThreadRand9Exp", "EmailThreadRand9Col"), 
					array("EmailThreadRand10Exp", "EmailThreadRand10Col"));
	
	if ($senderaddress == $useraddress)
	{
		$retval = $sederCol[$getindex];
	}
	else
	{
		foreach ($EML_THD_COLORS as $addr=>$colr)
		{
			if ($addr == $senderaddress)
				$retval = $colr[$getindex];
		}
		// New sender
		if ($retval == '')
		{
			$EML_THD_COLORS[$senderaddress] = $arrCol[$EML_THD_CLRS_CUR_INDX];
			$retval = $arrCol[$EML_THD_CLRS_CUR_INDX][$getindex];
			
			$num_clrs = count($arrCol);
			if ($EML_THD_CLRS_CUR_INDX <= ($num_clrs-1))
			{
				$EML_THD_CLRS_CUR_INDX++;
			}
			else
			{
				$EML_THD_CLRS_CUR_INDX = 0;
			}
		}
	}
	return $retval;
	*/
}

/*************************************************************************
*    Function:    buildTable
* 
*    Purpose:    Build Table for every form input
**************************************************************************/
function buildTable(con)
{
    var table = alib.dom.createElement("table", con);
    table.setAttribute("cellpadding", 2);
    table.setAttribute("cellspacing", 2);
    alib.dom.styleSet(table, "width", "100%");
    
    var tbody = alib.dom.createElement("tbody", table);
    
    return tbody;
}

/*************************************************************************
*    Function:    buildFormInput
* 
*    Purpose:    Build form inputs inside table
**************************************************************************/
function buildFormInput(inputFormData, tbody)
{    
    for(formData in inputFormData)
    {
        // Row Label
        var rowInput = inputFormData[formData];
        var tr = buildTdLabel(tbody, rowInput.label, rowInput.labelWidth);        
        switch(rowInput.type)
        {            
            case "checkbox":
                var td = tr.firstChild;
                td.innerHTML = "";
                td.setAttribute("colspan", 2);
                alib.dom.styleSetClass(td, "formValue");
                td.appendChild(rowInput);
                if(rowInput.label)
                {
                    var label = alib.dom.createElement("label", td);
                    label.innerHTML = rowInput.label;
                }
                break;            
            case "hidden":
                alib.dom.styleSet(tr, "display", "none");
                var td = tr.firstChild;
                td.setAttribute("colspan", 2);
                td.appendChild(rowInput);
                break;            
            default:
                var td = alib.dom.createElement("td", tr);        
                alib.dom.styleSetClass(td, "formValue");
                alib.dom.styleSetClass(rowInput, "fancy");
                td.appendChild(rowInput);
                break;
        }
        
        if(rowInput.inputLabel)
        {
            var label = alib.dom.createElement("label", td);
            label.innerHTML = rowInput.inputLabel;
            alib.dom.styleSet(label, "fontSize", "11px");
        }
    }
    
    // return the last tr
    return tr;
}

/*************************************************************************
*    Function:    buildFormInputDiv
* 
*    Purpose:    Build form inputs inside div
**************************************************************************/
function buildFormInputDiv(inputFormData, con, setClear, marginRight)
{
    if(typeof marginRight == "undefined")
        marginRight = "3px";
        
    con.innerHTML = "";
    for(formData in inputFormData)
    {
        // Row Label
        var rowInput = inputFormData[formData];        
        switch(rowInput.type)
        {            
            default:
                var divCon = alib.dom.createElement("div", con);
                alib.dom.styleSet(divCon, "float", "left");
                alib.dom.styleSet(divCon, "marginRight", marginRight);
                divCon.appendChild(rowInput);
                
                if(rowInput.label)
                {
                    var label = alib.dom.createElement("label", divCon);                    
                    alib.dom.styleSet(label, "fontSize", "11px");
                    label.innerHTML = rowInput.label;
                    
                    if(rowInput.floatDir)
                        alib.dom.styleSet(label, "float", rowInput.floatDir);
                    else
                        alib.dom.styleSet(label, "float", "right");
                        
                    if(rowInput.labelWidth)
                        alib.dom.styleSet(label, "width", rowInput.labelWidth);
                }
                break;
        }
        
        if(setClear)
        {
            alib.dom.styleSet(divCon, "marginBottom", "5px");
            divClear(con);
        }            
            
    }
    
    // return the last tr
    return divCon;
}

/*************************************************************************
*    Function:    buildTdLabel
* 
*    Purpose:    Build Td Row for every form input
**************************************************************************/
function buildTdLabel(tbody, label, width)
{
    var tr = alib.dom.createElement("tr", tbody);
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSet(td, "fontSize", "12px");
    alib.dom.styleSet(td, "vertical-align", "middle");
    alib.dom.styleSet(td, "paddingBottom", "8px");
    if(width)
        td.setAttribute("width", width);
    
    if(label)
        td.innerHTML = label;
        
    return tr;
}

/*************************************************************************
*    Function:    inputAttribute - Depreciated
* 
*    Purpose:    Sets input attribute
**************************************************************************/
function createInputAttribute(input, type, id, label, width, value, labelWidth, floatDir)
{ 
    if(id)
        input.id = id;
    
    if(type)
        input.type = type;
        
    if(label)
        input.label = label;
        
    if(value)
        input.value = value;
        
    if(width)
        alib.dom.styleSet(input, "width", width);
        
    if(labelWidth)
        input.labelWidth = labelWidth;
        
    if(floatDir)
        input.floatDir = floatDir;
    
    return input;
}

/*************************************************************************
*    Function:    setElementAttr - (New)
* 
*    Purpose:    Sets Element attribute 
**************************************************************************/
function setElementAttr(input, attrData)
{ 
    for(attribute in attrData)
    {
        var attr = attrData[attribute][0];
        var value = attrData[attribute][1];
        
        switch(attr)
        {            
            case "width":                
                alib.dom.styleSet(input, "width", value);                
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
function divClear(parentDiv)
{
    var divClear = alib.dom.createElement("div", parentDiv);
    alib.dom.styleSet(divClear, "clear", "both");
    alib.dom.styleSet(divClear, "visibility", "hidden");
}

/*************************************************************************
*    Function:    showDialog
* 
*    Purpose:    Shows dialog message
**************************************************************************/
function showDialog(message)
{
    var dlg = new CDialog();
    var dv_load = document.createElement('div');
    alib.dom.styleSetClass(dv_load, "statusAlert");
    alib.dom.styleSet(dv_load, "text-align", "center");
    dv_load.innerHTML = message;
    dlg.statusDialog(dv_load, 250, 100);
    
    return dlg;
}

/*************************************************************************
*    Function:    getWindowSize
* 
*    Purpose:    Gets the width and height of the window
**************************************************************************/
function getWindowSize(getWidth) {
  var myWidth = 0, myHeight = 0;
  
  if( typeof( window.innerWidth ) == 'number' ) //Non-IE
  {    
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } 
  else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) //IE 6+ in 'standards compliant mode'
  {    
    myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } 
  else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) //IE 4 compatible
  {    
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  
  if(getWidth)
    return myWidth;
  else
    return myHeight;
}

/*************************************************************************
*    Function:    commentSettings
* 
*    Purpose:    displays the comments of the user
**************************************************************************/
function commentSettings(con, userId)
{
    if(typeof userId == "undefined" || userId == null)
    {
        ajax = new CAjax('json');
        ajax.cls = this;                
        ajax.onload = function(ret)
        {
            buildCommentSettings(con, ret);
        };
        ajax.exec("/controller/User/getUserId");        
    }
    else
        buildCommentSettings(con, userId);
}

function buildCommentSettings(con, userId)
{
    var divComment = alib.dom.createElement("div", con);
    alib.dom.styleSet(divComment, "borderTop", "1px solid");
    alib.dom.styleSet(divComment, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divComment);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Comments";
    
    var divRight = alib.dom.createElement("div", divComment);
    alib.dom.styleSet(divRight, "float", "left");
    
    var objComment = new AntObjectBrowser("comment");
    objComment.setFilter('associations', "user:"+userId);
    objComment.printComments(divRight, "user:"+userId);
    
    divClear(divComment);
}

/*************************************************************************
*    Function:    getHeaderHeights
* 
*    Purpose:    Gets all the heights of header
**************************************************************************/
function getHeaderHeights()
{
    // App Header
    var appheaderHeight = 0;
    var appHeader = document.getElementById('appheader');
    if(appHeader)
        appheaderHeight = alib.dom.getContentHeight(appHeader);
    
    // App Nav
    var appNavHeight = 0;
    var appNav = document.getElementById('appnav');
    if(appNav)
        appNavHeight = alib.dom.getContentHeight(appNav);
    
    // App Nav
    var appTitleHeight = 0;
    var appTitle = document.getElementById('apptitle');
    if(appTitle)
        appTitleHeight = alib.dom.getContentHeight(appTitle);
    
    var totalHeaderHeight = (appheaderHeight + appNavHeight + appTitleHeight);
    
    var ret = new Object;
    ret.appheaderHeight = appheaderHeight;
    ret.appNavHeight = appNavHeight;
    ret.appTitleHeight = appTitleHeight;
    ret.totalHeaderHeight = totalHeaderHeight;
    
    return ret;
}

/*************************************************************************
*    Function:    buildDropdown
* 
*    Purpose:    builds the dropdown using the array
**************************************************************************/
function buildDropdown(objElement, dataArray, currentValue)
{
    for(data in dataArray)
    {
        var currentData = dataArray[data];
        var objLen = objElement.length;
        var selected = false;
        
        if(typeof currentData == "object")
        {
            var value = currentData[0];
            var text = currentData[1];
        }        
        else
        {
            var value = currentData;
            var text = currentData;
        }
        
        if(currentValue == value)
            selected = true;
        
        objElement[objLen] = new Option(text, value, false, selected);
    }
}

/*************************************************************************
*    Function:    checkObjectData
* 
*    Purpose:    Checks the object if it has data
**************************************************************************/
function checkObjectData(objectData)
{
    for(object in objectData) 
        return true;
    
    return false;
}

/**
 * Put notified string into object
 *
 * @param {string} A notified string [recipeint]|label
 * @return {Object} {type:"", id:"", name:""};
 */
function getNotifiedParts(notified)
{
	var ret = {type:"text", id:"", email:"", name:""};

	var parts = notified.split("|");
	ret.name = parts[0]; // Assume text entry for backwards compatibility

	// Check if this is an object reference
	var fromParts = parts[0].split(":");
	if (fromParts.length > 1)
	{
		ret.type = fromParts[0];
		ret.id = fromParts[1];
	}
	else
	{
		// Check to see if we are working with an email address
		var fromParts = parts[0].split("@");
		if (fromParts.length > 1)
			ret.email = parts[0];
	}

	// Now get the name/label
	if (parts.length > 1)
	{
		ret.name = parts[1];
	}

	return ret;
}

/**
 * Creates a series of space
 *
 * @this {global}
 * @param {Integer} num     number of spaces
 */
function tabSpace(num)
{
    var space = "";
    for(var i = 0; i < num; i++)
    {
        space += "\t";
    }
    return space;
}

/**
 * Checks the folder/filename for special characters
 *
 * @this {global}
 * @param {String} type     file or folder
 * @param {String} value    the value to be checked
 * @param {DOM} element     field input
 */
function checkSpecialCharacters(type, value, element)
{
    var testSpecialChar = /^[a-zA-Z0-9_ %\[\]\.\(\)%&-]*$/.test(value);
    if(!testSpecialChar)
    {
        var cbFunc = function() 
        {
            alert("A " + type + " name can't contain any of the following characters: \n\n / ? < > \ : * | \"");
            
            if(element)
                element.focus();
        }
        
        setTimeout(cbFunc, 0);
        return false;
    }
    
    return true;
}

/** 
* it will capitalize the first letter of the string.
*/
String.prototype.capitalize = function()
{
    return this.replace( /(^|\s)([a-z])/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
};

/** 
* Change the url string to a link
*/
String.prototype.linkify = function()
{
    var replaceText, replacePattern1, replacePattern2, replacePattern3;

    if(this.match(/\.(jpeg|jpg|gif|png|bmp)$/))
        return this;
    
    //URLs starting with http://, https://, or ftp://
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = this.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');
    
    return replacedText;
};
