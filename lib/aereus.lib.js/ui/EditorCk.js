/**
* @fileOverview alib.ui.Editor class
*
* This is the editor class to be used with alib.
* Currently this employs CKEditor but abstracts all access for possible future re-write
*
* Exampl:
* <code>
* 	var button = alib.ui.Editor(document.getElementById("mytextarea"), {className:"blue"});
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_Editor
 *
 * @constructor
 */
function Alib_Ui_Editor(el, options)
{
	/**
	 * Handle to editor
	 *
	 * @private
	 * @var {CKEDITOR.editor}
	 */
	this.editor = null;

}

/**
 * Print the rte
 *
 * @public
 */
Alib_Ui_Editor.prototype.print = function(con, width, height, html)
{
	/*
	var inp = alib.dom.createElement("textarea", con);
	this.editor = $(inp).ckeditor();
	*/

	//window.CKEDITOR_BASEPATH = alib.getBasePath() + '/ckeditor';

	/**
	 * Current width
	 *
	 * @param {string|int}
	 */
	this.width = width;

	/**
	 * Current height
	 *
	 * @param {string|int}
	 */
	this.height = height;

	var disablePl = "elementspath,save,styles,stylescombo,about,pagebreak,flash,"
					+ 'newpage,doprops,preview,templates,forms,bidi,smiley,specialchar,print,'
					+ "scayt";

	//var enablePl = "tabletools";
	var enablePl = "";
	var imgAct  = "Image"; // use standard insert image as a link

	if (typeof AntFsOpen != "undefined")
	{
		enablePl += ',antimg';
		imgAct = "AntImg";
	}


	var opt = {
		width: width,
		height : height,
		toolbar : "alib",
		//extraPlugins : enablePl,
		removePlugins : disablePl,
		disableNativeSpellChecker : false
	};
	opt.toolbar_alib =
	[
		{ name: 'basicstyles',	items : [ 'Bold','Italic','Underline', '-',
										 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-',
										 'Outdent','Indent', '-','TextColor','BGColor'] },
		
		{ name: 'styles',		items : [ 'Font','FontSize', 'Format' ] },
		{ name: 'insert',		items : [ 'Link', imgAct, 'Table','HorizontalRule', '-', 'NumberedList','BulletedList',
										  '-', 'PasteText','PasteFromWord'] },
		//{ name: 'clipboard',	items : [ 'PasteText','PasteFromWord' /*,'-','Undo','Redo' */ ]  },
		//{ name: 'editing',	items : [ 'Find','Replace' ] },
		{ name: 'tools',		items : [ 'Source', 'Maximize' ] }
	];
	
	
	this.editor = CKEDITOR.appendTo(con, opt, html); // , config, data

	// Force the editor to fire blur right away
	CKEDITOR.focusManager.prototype.orig_blur = CKEDITOR.focusManager.prototype.blur;
	CKEDITOR.focusManager.prototype.blur = function() { CKEDITOR.focusManager.prototype.orig_blur.call(this,true); };

	var me = this;
	this.editor.on('blur', function(e) { me.onChange(); });
}

/**
 * Onchange callback
 *
 * @public
 */
Alib_Ui_Editor.prototype.onChange = function()
{
}

/**
 * Get the value of this editor
 *
 * @public
 */
Alib_Ui_Editor.prototype.getValue = function()
{
	return this.editor.getData();
}

/**
 * Set the value of this editor
 *
 * @public
 * @param {string} html The html string to put into the editor
 */
Alib_Ui_Editor.prototype.setValue = function(html)
{
	this.editor.setData(html);
}

/**
 * Set the height of the editor
 *
 * @public
 */
Alib_Ui_Editor.prototype.setHeight = function(height)
{
	this.editor.resize(this.width, height);
}

/**
 * Focus on the input
 *
 * @public
 */
Alib_Ui_Editor.prototype.focus = function()
{
	this.editor.focus();
}

/**
 * Replace the innerhtml of an element by id
 *
 * @public
 * @param {string} id The id of the element
 * @param {strng} html The html to put inside the container with the given id
 */
Alib_Ui_Editor.prototype.setElementHtml = function(id, html)
{
	var doc = this.editor.document.$;
	var e = doc.getElementById(id);
	if (e)
		e.innerHTML = html;

	/*
	// TODO: IE seems to have trouble with timeing and finding the element
	if(alib.userAgent.ie)
	{
		var e = this.idoc.getElementById(id);
	}
	else
		var e = this.ifrm.contentDocument.getElementById(id);
	if (e)
		e.innerHTML = html;
	*/
}
