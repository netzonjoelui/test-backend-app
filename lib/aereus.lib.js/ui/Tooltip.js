/**
* @fileOverview alib.ui.tooltip class
*
* This is used to display tooltip of elements
*
* Exampl:
* <code>
*     var button = new alib.ui.Tooltip(element, "Test Tooltip");
* </code>
*
* @author:    Marl Tumulak, marl.tumulak@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*
*             joe, sky.stebnicki@aereus.com; v2 updates to use tipsy
*             Copyright (c) 2013 Aereus Corporation. All rights reserved.
*/

 /**
 * Creates an instance of alib.ui.Tooltip
 *
 * @constructor
 * @param {string} element      Html element to be attached with tooltip
 * @param {string} message      Message of the tooltip
 * @param {boolean} fModular    Determines if the tooltip will be displayed on a modular window
 */
alib.ui.Tooltip = function(element, message, fModular)
{
	if (message)
		element.setAttribute("title", message);

	var tip = $(element).tipsy({
		delayIn: 500,      // delay before showing tooltip (ms)
		delayOut: 0,     // delay before hiding tooltip (ms)
		fade: true,     // fade tooltips in/out?
		fallback: '',    // fallback text to use when no tooltip text
		gravity: $.fn.tipsy.autoBounds(0, 'n'),    // gravity
		html: true,     // is tooltip content HTML?
		live: false,     // use live event support?
		offset: 1,       // pixel offset of tooltip from element
		opacity: 0.8,    // opacity of tooltip
		title: 'title',  // attribute/callback containing tooltip text
		trigger: 'hover' // how tooltip is triggered - hover | focus | manual
	});

	alib.events.listen(element, "click", function() { 
		$(element).tipsy("hide");
	});

	/*
    var attrData = [["innerHTML", message], ["id", "toolTip"]];
    var divTooltip = alib.dom.setElementAttr(alib.dom.createElement("div", element.parentNode), attrData);    
    alib.dom.styleSet(divTooltip, "maxWidth", "400px");
    alib.dom.styleSet(divTooltip, "text-align", "justify");
    alib.dom.styleSet(divTooltip, "background-color", "#115768");
    alib.dom.styleSet(divTooltip, "color", "#FFFFFF");
    alib.dom.styleSet(divTooltip, "position", "absolute");    
    alib.dom.styleSet(divTooltip, "display", "none");
    alib.dom.styleSet(divTooltip, "borderRadius", "3px");
    alib.dom.styleSet(divTooltip, "padding", "5px");
    alib.dom.styleSet(divTooltip, "fontWeight", "normal");
    
    $(element).mousemove(function(e)
    {
        var elemPos = alib.dom.getElementPosition(element);
        //var left = e.pageX - (divTooltip.offsetWidth / 2);
        //var top = e.pageY - (divTooltip.offsetHeight + 5);
        var left = (elemPos.x + (element.offsetWidth / 2)) - (divTooltip.offsetWidth / 2);
        var top = elemPos.y - (divTooltip.offsetHeight);
        
        if((elemPos.x + divTooltip.offsetWidth) >= document.body.offsetWidth)
        {
            left = elemPos.x - (divTooltip.offsetWidth);
            top = (elemPos.y + (element.offsetHeight / 2)) - (divTooltip.offsetHeight/2);
        }

        if(left < 0)
            left = 5;
            
        if(top < 0)
            top = 5;
        
        if(fModular)
        {            
            left = (left / 2) + 30;
            top = (top / 2) + 100;
        }
        
        alib.dom.styleSet(divTooltip, "left", left);
        alib.dom.styleSet(divTooltip, "top", top);
    });
    
    $(element).hover
    (
        function()
        {
            $(divTooltip).fadeIn(500);
        },
        function()
        {
            $(divTooltip).fadeOut(500);
        }
    )
    
    this.tooltipCon = divTooltip;
	*/
}
