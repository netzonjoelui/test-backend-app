/*======================================================================================
	
	Module:		CTextBoxList

	Purpose:	Advanced text box list sort of like facebook.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Usage:		

======================================================================================*/

/***********************************************************************************
 *
 *	Class: 		CTextBoxList
 *
 *	Purpose:	Encapsulate text box list
 *
 ***********************************************************************************/
function CTextBoxList(e, opts)
{
	if (!opts)
		var opts = {};

	this.t = new $.TextboxList(e, opts);
}

/***********************************************************************************
 *
 *	Function: 	add
 *
 *	Purpose:	Add an element to the list
 *
 *	Arguements:	
 *
 ***********************************************************************************/
CTextBoxList.prototype.add = function (id, value)
{
	if (!value)
		var value = id;

	// 0 = plaintext
	// 1 = id
	// 2 = html value
	this.t.add(null, id, value);
}

/***********************************************************************************
 *
 *	Function: 	getValues
 *
 *	Purpose:	Get array of values from textbox list with the following vals
 *				1 = plaintext
 *				2 = id
 *				3 = html
 *				4 = ?
 *
 ***********************************************************************************/
CTextBoxList.prototype.getValues = function ()
{
	return this.t.getValues();
}

/***********************************************************************************
 *
 *	Function: 	acLoadValues
 *
 *	Purpose:	Load json data via ajax
 *
 *	return array( id, searchable plain text, html (for the textboxlist item, if empty the plain is used), html (for the autocomplete dropdown))
 *
 ***********************************************************************************/
CTextBoxList.prototype.acLoadValues = function (strUrl)
{
	var tb = this.t;

	$.ajax({url: strUrl, dataType: 'json', success: function(r){
					tb.plugins['autocomplete'].setValues(r);
					tb.getContainer().removeClass('textboxlist-loading');
				}});	
}

/***********************************************************************************
 *
 *	Function: 	clear
 *
 *	Purpose:	Remove all entries
 *
 *	Arguements:	
 *
 ***********************************************************************************/
CTextBoxList.prototype.clear = function()
{
	this.t.clear();
}
