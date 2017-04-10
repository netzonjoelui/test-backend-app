/**
* @fileoverview This will load convert all divs/spans of content post into editable content
*
* @author    joe, sky.stebnicki@aereus.com
*             Copyright (c) 2014 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of cms.
*
* @constructor
*/
function Cms()
{
	var editMode = this.getCookie("cms_edit");

	if (editMode == 1)
	{
		this.connects = new Array();
		this.tooltips = new Array();
		
		this.serverUrl = "";
		this.pushUrl = "";
		
		this.getServerUrl();
		this.getPushUrl();
		this.editModeBar();
		this.initElements();
	}
}

/**
 * Get cookie contents
 *
 * @param {string} name The cookie name
 */
Cms.prototype.getCookie = function(name)
{
	var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/**
 * Initialize editable elements within this dociment
 *
 * @public
 * @this {Cms} 
 */
Cms.prototype.initElements = function()
{
    var cls = this;

	/**
	 * Enable parallelex scrolling
	 */
	$('div[data-netric-id]').each(function(){
	
		// Set this variable
		var $self = $(this);

		//var oid = dojo.getAttr(postElem, "data-netric-id");
		var oid = $self.data('netric-id');

		$self.on("mousemove", function(evt) {
			cls.showEditButton(this, evt);
		});

		$self.on("mouseout", function() {
			cls.hideEditButton(this);
		});

		/*
		cls.connects[cls.connects.length] = dojo.connect(postElem, "onmouseover", function(evt) {
			cls.showEditButton(this);
		});

		cls.connects[cls.connects.length] = dojo.connect(postElem, "onmouseout", function(evt) {
			cls.hideEditButton(this);
		});
		*/

	});
}


/**
 * Hover an edit button in the top left cornder of element
 *
 * @param {DOMElement} e The element that we will be editing
 * @param {Jquery.EVENT} evt Optional event to gather mouse coords from
 */
Cms.prototype.showEditButton = function(e, evt) {
	if (!e.editButtton)
	{
		e.editButtton = document.createElement("div");
		e.editButtton.innerHTML = "Edit";
		e.appendChild(e.editButtton);
		e.editButtton.cls = this;

		$(e.editButtton).on("click", function(evt) {
			this.cls.showDialog(this.parentNode);
		});
	}

	var pos = $(e).offset();
	var t = (evt) ? (evt.pageY - 10) : pos.top;
	$(e.editButtton).css({
		"textAlign" : "center",
		"display" : "block", 
		"position" : "absolute",
		"cursor" : "pointer", 
		"padding" : "5px",
		"zIndex" : "200",
		"opacity" : ".9",
    	"backgroundColor" : "#FFFFC0",
    	"color" : "#124C73",
		"top" : t + "px",
		"left" : pos.left + "px"
	});
}

/**
 * Hover an edit button in the top left cornder of element
 *
 * @param {DOMElement} e The element that we will be editing
 */
Cms.prototype.hideEditButton = function(e)
{
	if (e.editButtton)
		$(e.editButtton).css({"display":"none"});
}

/**
 * Shows the modal dialog where the content post can be edited
 *
 * @public
 * @this {Cms}
 * @param {DOMElement} postElem     Container of the content post
 */
Cms.prototype.showDialog = function(postElem)
{
    // Instantiate variables and post data
	var otype = $(postElem).data('netric-type');
	if (!otype)
		otype = "content_feed_post"; // assume if no data type set

	var oid = $(postElem).data('netric-id');
    
    if(!oid || typeof oid=="undefined")
    {
        alert("The object id is invalid. Cannot display Edit Dialog Box.");
        return;
    }
    
	var outerCon = $('<div class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="Edit" aria-hidden="true"></div>');
	$(document.body).append(outerCon);
	var innerCon = $('<div class="modal-dialog modal-lg"></div>');
	$(outerCon).append(innerCon);
	
    // Create Containers
    var dialogCon = $("<div>", {class: "modal-content"});
	$(innerCon).append(dialogCon);

    var headerCon = $('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button><h4 class="modal-title" id="cmsEditor">Edit Content</h4></div>');
	dialogCon.append(headerCon);

    var bodyCon = $('<div class="modal-body"></div>');
	dialogCon.append(bodyCon);

    var iframe = this.createIframe(bodyCon, oid, otype);

    // Show Dialog
	$(outerCon).modal('show');

	$(outerCon).on('hidden.bs.modal', function (e) {
		$.get("/netric/api/sync-object?obj_type=" + otype + "&oid=" + oid , function( data ) {
			location.reload();
		});
	});
	
}

/**
 * Creates the Dojo Rich Text Editor
 *
 * @public
 * @this {Cms}
 * @param {DOMElement} dialogCon    Container of the dialog modal
 * @param {Integer} antPostId       Content Post Id
 * @param {String} dataType         Type of Cms or content post
 */
Cms.prototype.createIframe = function(dialogCon, antPostId, dataType)
{
    if(!dataType || typeof dataType == "undefined")
        dataType = "content_feed_post";
    
    var url = ('https:' == document.location.protocol ? 'https://' : 'http://') + this.serverUrl + "/obj/" + dataType + "/" + antPostId + "?noclose=1&inline=1&edit=1";
    
    var dojoIframe = document.createElement("iframe");
	dojoIframe.src = url;
	$(dojoIframe).css({
		"border": "0",
		"width": "100%",
		"height": ($(window).height()-160) + "px",
	});
	$(dialogCon).append(dojoIframe);
    
    return dojoIframe;
}

/**
 * Displays the edit mode bar
 *
 * @public
 * @this {Cms}
 */
Cms.prototype.editModeBar = function()
{
    var cls = this;

	var divCon = document.createElement("div");
	document.body.appendChild(divCon);
	$(divCon).css({
    	"background-color": "red",
    	//"border-bottom-left-radius": "8px",
		//"border-bottom-right-radius": "8px",
		"color": "white",
		"cursor": "pointer",
		//"float": "left",
		//"font-size": "14px",
		//"font-weight": "bold",
		//"left": "0",
		//"margin-left": "auto",
		//"margin-right": "auto",
		"padding": "10px",
		//"position": "absolute",
		"text-align": "center",
		//"top": "0",
		"width": "100%",
		"z-index": "100"
	});

	$(divCon).html("You are in edit mode. Leave Edit Mode.");
    
	$(divCon).on("click", function(evt) {
		$(this).css({"display": "none"}); 
		cls.deactivateEditMode();
	});
}

/**
 * Deactivats edit mode and destroys cookies
 *
 * @public
 * @this {Cms} 
 */
Cms.prototype.deactivateEditMode = function()
{
	$.cookie("cms_edit", null);
	location.reload();

	/*
    dojo.xhrGet({
        url: "/antapi/deactivate-edit",
        handleAs: "json",
        load: function(ret)
        {
			// Refresh
			location.reload();
        }
    });
	*/
}

/**
 * Gets the url of the Ant Server
 *
 * @public
 * @this {Cms}
 */
Cms.prototype.getServerUrl = function()
{
	// If already set, then don't send again
	if (this.serverUrl)
		return this.serverUrl;

    var url = null;
    var scripts = document.getElementsByTagName("script");
	var src = "";
    
    for(script in scripts)
    {
        var currentScript = scripts[script];
		if (currentScript.src) {
			if(currentScript.src.indexOf("Cms2.js")>0) {
				src = currentScript.src;
				break;
			}
		}
    }

	// Create anchor which exploses hostname as a property
	var l = document.createElement("a");
    l.href = src;
	this.serverUrl = l.hostname;

	return this.serverUrl;
}

/**
 * Gets the url for push state url
 *
 * @public
 * @this {Cms}
 */
Cms.prototype.getPushUrl = function()
{
	// If already set, then don't send again
	if (this.pushUrl)
		return this.pushUrl;

    var urlParts = document.URL.split("/");
    var url = "";
    for(urlIdx in urlParts)
    {
        if(urlParts[urlIdx] == "editmode")
            break;
            
        if(urlIdx > 2)
        {
            url += "/" + urlParts[urlIdx];
        }
    }
    
    this.pushUrl = url;
}

/**
 * Instantiates the class and executes the getPostCon
 *
 * @public
 * @this {Cms}
 */
$(document).ready(function(){
    var cmsClass = new Cms();
    //cmsClass.getPostsCon(false);
});
