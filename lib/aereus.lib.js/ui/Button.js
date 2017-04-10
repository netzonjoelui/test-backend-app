/**
* @fileOverview alib.ui.button class
*
* This is used simply to abstract common button functions in the DOM
*
* Exampl:
* <code>
* 	var button = alib.ui.Button("Button Content", {className:"blue", con:document.getElementById("toolbardiv"), tooltip:"My Text"});
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_Button
 *
 * @constructor
 */
function Alib_Ui_Button(content, options, type)
{
    // button or anchor
    if (type == 'link')
        type = 'a';

    if (type == 'span')
        type = 'span';
    
    if (!type)
        type = 'button';
	/**
	 * The button dom element
	 *
	 * @private
	 * @type {DOMElement[button]}
	 */
	this.m_main = alib.dom.createElement(type);
    
	var opts = options || new Object();
    
    if (opts.className)
        alib.dom.styleAddClass(this.m_main, opts.className);

	if (typeof content == "string")
		this.m_main.innerHTML = content;
	else
		this.m_main.appendChild(content);

	if (opts.tooltip)
		this.m_main.title = opts.tooltip;
        
    // Add cursor pointer if type is link
    if (type == 'a')
        alib.dom.styleSet(this.m_main, "cursor", "pointer");

	// Set actions for button
	// -----------------------------------------
	this.m_main.m_btnh = this;
	this.m_main.opts = opts;
	this.m_main.opts.m_btnh = this;

	if (opts.onmouseover)
		this.m_main.onmouseover = function() { if (!this.disabled) this.opts.onmouseover(); };
	if (opts.onmouseout)
		this.m_main.onmouseout = function() { if (!this.disabled) this.opts.onmouseout(); };
	if (opts.onclick)
	{
		this.m_main.clickAction = opts.onclick;
		this.m_main.onclick = function() { if (!this.disabled) this.clickAction(); };
	}

	// Set all other variables in options to this.m_main scope
	for (var prop in opts)
	{
		if (prop != "onmouseover" && 
				prop != "onmouseout" && 
					prop != "onclick" && 
						prop != "className" && 
							prop != "tooltip")
		{
			this.m_main[prop] = opts[prop];
		}
	}

	/**
	 * Options used for this button
	 *
	 * @private
	 * @type {Object}
	 */
	this.options = opts;

	/**
	 * Generic object for storing temp callback properties
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();

	/**
	 * Optional toggle state
	 *
	 * If true this button is toggled on
	 *
	 * @type {bool}
	 */
	this.toggeled = false;

	// trigger click events
	alib.events.listen(this.m_main, "click", function(evt) {
		alib.events.triggerEvent(evt.data.btncls, "click");
	}, {btncls:this});

}

/**
* Disable this button
*
* @public
* @this {Alib_Ui_Button}
*/
Alib_Ui_Button.prototype.disable = function()
{
	this.m_main.disabled = true;
	alib.dom.styleAddClass(this.m_main, "disabled");
}

/**
* Enable this button
*
* @public
* @this {Alib_Ui_Button}
*/
Alib_Ui_Button.prototype.enable = function()
{
	this.m_main.disabled = false;
	alib.dom.styleRemoveClass(this.m_main, "disabled");
}

/**
* Set the content/text of this button
*
* @public
* @this {Alib_Ui_Button}
* @param {string|DOMElement} content The text or the dom element to put in this button
*/
Alib_Ui_Button.prototype.setText = function(content)
{
	if (typeof content == "string")
		this.m_main.innerHTML = content;
	else
		this.m_main.appendChild(content);
}

/**
* Get button element
*
* @public
* @this {Alib_Ui_Button}
* @return {DOMElement} The button element
*/
Alib_Ui_Button.prototype.getButton = function ()
{
	return this.m_main;
}

/**
* Print button inside a container
*
* @public
* @this {Alib_Ui_Button}
* @param {DOMElement} con Dom container that will house this button
*/
Alib_Ui_Button.prototype.print = function(con)
{
	con.appendChild(this.m_main);

	if (this.options.toggle)
	{
		this.addEvent("click", function(opt){
			if (!opt.cls.m_main.disabled)
				opt.cls.toggle();
		}, { cls:this });
	}
}

/**
* Change state of this button
*
* @public
* @this {Alib_Ui_Button}
*/
Alib_Ui_Button.prototype.toggle = function(toggled)
{
	if (typeof toggled != "undefined")
		this.toggled = toggled;
	else
		this.toggled = (this.toggled) ? false : true;

	if (this.toggled)
		alib.dom.styleAddClass(this.m_main, "on");
	else
		alib.dom.styleRemoveClass(this.m_main, "on");
}

/**
* Get the toggled state
*
* @public
* @this {Alib_Ui_Button}
*/
Alib_Ui_Button.prototype.isOn = function()
{
	return (this.toggled) ? true : false;
}

/**
* This function only exists for backwards compatibility
*
* @depricated
* @public
* @this {Alib_Ui_Button}
* @return {DOMElement} The button element
*/
Alib_Ui_Button.prototype.getTable = function ()
{
	return this.getButton();
}

/**
* Add an event listener to this button
*
* @public
* @this {Alib_Ui_Button}
*/
Alib_Ui_Button.prototype.addEvent = function(evnt, funct, options)
{
	var cbObj = {cbfun: funct, opt:options};

	alib.dom.addEvent(this.m_main, evnt, function(){ cbObj.cbfun(cbObj.opt); });
}

/**
 * Add a class to this button
 *
 * @public
 * @param {string} className
 * @this {Alib_Ui_Button}
 */
Alib_Ui_Button.prototype.addClass = function(className) {
	alib.dom.styleAddClass(this.m_main, className);
}

/**
 * Remove a class from the style of this button
 *
 * @public
 * @param {string} className
 * @this {Alib_Ui_Button}
 */
Alib_Ui_Button.prototype.removeClass = function(className) {
	alib.dom.styleRemoveClass(this.m_main, className);
}

/**
* Get the absolute width of this button
*
* @public
* @this {Alib_Ui_Button}
* @return {int} The width in px of this button
*/
Alib_Ui_Button.prototype.getWidth = function()
{
	return alib.dom.getElementWidth(this.m_main);
}
