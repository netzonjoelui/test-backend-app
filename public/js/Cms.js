/**
* @fileoverview This will load convert all divs/spans of content post into editable content
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of cms.
*
* @constructor
*/

dojo.require("dojo.dom-geometry");
dojo.require("dijit.Tooltip");
dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.Editor");
dojo.require("dijit._editor.plugins.AlwaysShowToolbar");

function Cms()
{
	var editMode = this.getCookie("cms_edit");

	if (editMode == 1)
	{
		this.connects = new Array();
		this.tooltips = new Array();
		
		this.serverUrl = null;
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
    dojo.query('[data-netric-id]').forEach(function(postElem){
		var oid = dojo.getAttr(postElem, "data-netric-id");

		cls.connects[cls.connects.length] = dojo.connect(postElem, "onmouseover", function(evt) {
			cls.showEditButton(this);
		});

		cls.connects[cls.connects.length] = dojo.connect(postElem, "onmouseout", function(evt) {
			cls.hideEditButton(this);
		});

		/*
		cls.tooltips[cls.tooltips.length] = new dijit.Tooltip({
		   connectId: postElem,
		   label: "Double click to edit.",
		   position: ["before"]
		});
		*/
		
		/*
		cls.connects[cls.connects.length] = dojo.connect(postElem, "ondblclick", function(evt) {
			cls.showDialog(postElem);
		});
		*/

		/*
		var regEx = new RegExp('(antPostId)',["i"]);
		var result = regEx.exec(postElem.id);
		if (result != null)
		{
			if(deactivate)
				dojo.setStyle(postElem, {"cursor": "default"}); 
			else
			{
				cls.tooltips[cls.tooltips.length] = new dijit.Tooltip(
													{
													   connectId: postElem.id,
													   label: "Double click to edit.",
													   position: ["before"]
													});
				
				dojo.setStyle(postElem, {"cursor": "pointer"}); 
				cls.connects[cls.connects.length] = dojo.connect(postElem, "ondblclick", function(evt)
													{
														cls.showDialog(postElem);
													});
			}
		}
		*/
	});
}


/**
 * Hover an edit button in the top left cornder of element
 *
 * @param {DOMElement} e The element that we will be editing
 */
Cms.prototype.showEditButton = function(e)
{
	if (!e.editButtton)
	{
		e.editButtton = dojo.doc.createElement("div");
		e.editButtton.innerHTML = "Edit";
		e.appendChild(e.editButtton);
		e.editButtton.cls = this;

		dojo.connect(e.editButtton, "onclick", function(evt) {
			this.cls.showDialog(this.parentNode);
		});
	}

	//dojo.setStyle(e, {"border": "1px dashed"});
		
	var obj = dojo.position(e, true);
	dojo.setStyle(e.editButtton, {
		"width" : "32px",
		"height" : "20px",
		"textAlign" : "center",
		"display" : "block", 
		"position" : "absolute",
		"cursor" : "pointer", 
		"padding" : "5px",
		"zIndex" : "200",
		"opacity" : ".9",
    	"backgroundColor" : "#FFFFC0",
    	"color" : "#124C73",
		"top" : obj.y + "px",
		"left" : obj.x + "px"
	});
}

/**
 * Hover an edit button in the top left cornder of element
 *
 * @param {DOMElement} e The element that we will be editing
 */
Cms.prototype.hideEditButton = function(e)
{
	//dojo.setStyle(e, {"border": "1px dashed"});

	if (e.editButtton)
		dojo.setStyle(e.editButtton, {"display":"none"});
}

/**
 * @depricated This is no longer in use
 * gets the content posts div/span
 *
 * @public
 * @this {Cms} 
 * @param {Boolean} deactivate     Determine whether to disconnect the double click event
 */
Cms.prototype.getPostsCon = function(deactivate)
{
    var cls = this;
    
    dojo.query('div').forEach
    (
        function(postElem)
        {
            var regEx = new RegExp('(antPostId)',["i"]);
            var result = regEx.exec(postElem.id);
            if (result != null)
            {
                if(deactivate)
                    dojo.setStyle(postElem, {"cursor": "default"}); 
                else
                {
                    cls.tooltips[cls.tooltips.length] = new dijit.Tooltip(
                                                        {
                                                           connectId: postElem.id,
                                                           label: "Double click to edit.",
                                                           position: ["before"]
                                                        });
                    
                    dojo.setStyle(postElem, {"cursor": "pointer"}); 
                    cls.connects[cls.connects.length] = dojo.connect(postElem, "ondblclick", function(evt)
                                                        {
                                                            cls.showDialog(postElem);
                                                        });
                }
            }
        }
    )
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
	var otype = dojo.getAttr(postElem, "data-netric-type");
	if (!otype)
		otype = "content_feed_post"; // assume if no data type set

	var oid = dojo.getAttr(postElem, "data-netric-id");
    
    if(!oid || typeof oid=="undefined")
    {
        alert("Ant Post Id is invalid. Cannot display Edit Dialog Box.");
        return;
    }
    
    var cls = this;
    var dojoDiag;
    
    var postTitle = dojo.query('*:first-child', postElem)[0];
    var postType;
    switch(otype)
    {
        case "cms_page":
            postType = "Page";
            break;
        case "cms_snipper":
            postType = "Snippet";
            break;
        case "content_feed_post":
            postType = "Post";
            break;
        default:
            postType = "Content";
            break;
    }
    
    var pTitle = postTitle.innerHTML;
    dojo.query("*", postTitle).forEach(function(node, index, arr)
    {
        pTitle = node.innerHTML;
    });
    
    // Create modal dialog
    dojoDiag = new dijit.Dialog({
        title: "Edit " + postType + ": " + pTitle,
        style: "width: 960px; text-align: justify;",
        draggable: true
    });
    
    // Create Containers
    var dialogCon = dojo.create("div");
    var loadingCon = this.createLoading(dialogCon);    
    var dojoIframe = this.createIframe(dialogCon, oid, otype);
    
    dojo.html.set(loadingCon, "Loading...");
    dojo.connect(dojoIframe, "onload", function(evt)
    {
        dojo.html._emptyNode(loadingCon);
    });
        
    dojoDiag.onHide = function()
    {
        //cls.retrieveContentPost(oid, postElem);
		location.reload();
        this.destroy();
    }
        
    // Show Dialog
    dojoDiag.set("content", dialogCon);
    dojoDiag.show();
    dojoDiag.closeButtonNode.title = 'Close';
}

/**
 * Creates the loading div
 *
 * @public
 * @this {Cms}
 * @param {DOMElement} dialogCon    Container of the dialog modal
 */
Cms.prototype.createLoading = function(dialogCon)
{
    var style = "color: #F07E13;";
    style += "position: absolute;";
    style += "font-size: 18px;";
    style += "font-weight: bold;";
    style += "padding: 5px;";
    style += "font-family: Tahoma,Arial,Verdana,Helvetica,sans-serif;";
    
    var loadingCon = dojo.create("div", {
        "style": style
    }, dialogCon);
    
    return loadingCon;
}
    
/**
 * Creates the Dojo Rich Text Editor
 *
 * @public
 * @this {Cms}
 * @param {DOMElement} dialogCon    Container of the dialog modal
 */
Cms.prototype.createEditor = function(dialogCon)
{
    var dojoEditor = new dijit.Editor({
        height: '600px',
        focusOnLoad: true,
        extraPlugins: [dijit._editor.plugins.AlwaysShowToolbar]
    }, dialogCon);
    
    return dojoEditor;
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
    
    var url = ('https:' == document.location.protocol ? 'https://' : 'http://') + this.serverUrl + "/obj/" + dataType + "/" + antPostId + "?noclose=1";
    
    var dojoIframe = dojo.create("iframe", {
        "src": url,
        "style": "border: 0; width: 100%; height: 600px"
    }, dialogCon)
    
    return dojoIframe;
}

/**
 * Creates a Dojo button
 *
 * @public
 * @this {Cms}
 * @param {DOMElement} buttonCon    Container for the button
 * @param {String} label            The label/caption of the button
 * @param {Function} funct          Function that is executed when button is clicked
 */
Cms.prototype.createButton = function(buttonCon, label, funct)
{
    new dijit.form.Button(
    {
        label: label,
        onClick: funct
    }, buttonCon);
}

/**
 * @depriacted Simply reload
 * Retrieves the content post data using dojo xhr
 *
 * @public
 * @this {Cms}
 * @param {Integer} antPostId       Content Post Id
 * @param {DOMElement} postElem     Container of the content post
 */
Cms.prototype.retrieveContentPost = function(antPostId, postElem)
{
    var contentUrl = "/antapi/post-edit?id=" + antPostId;
    
    var subStr = dojo.getAttr(postElem, "data-substr");
    var stripTags = dojo.getAttr(postElem, "data-striptags");
    var dataType = dojo.getAttr(postElem, "data-type");
    var snippetPosition = dojo.getAttr(postElem, "data-snippet-position");
                
    if(subStr)
        contentUrl += "&substr=" + subStr;
        
    if(stripTags)
        contentUrl += "&striptags=" + stripTags;
        
    if(dataType)
        contentUrl += "&datatype=" + dataType;
        
    if(!snippetPosition || typeof snippetPosition=="undefined")
        snippetPosition = 2;
        
    dojo.xhrGet({
        url: contentUrl,
        handleAs: "json",
        load: function(ret)
        {
            if(!ret)
                return;
            
            var postTitle = dojo.query('*:first-child', postElem)[0];
            var postData = dojo.query('*:nth-child(' + snippetPosition + 'n)', postElem)[0];
            
            dojo.query("*", postTitle).forEach(function(node, index, arr)
            {
                postTitle = node;
            });
            
            if(ret["title"] && postTitle)
                dojo.html.set(postTitle, ret["title"]);
                
            if(ret["data"] && postData)
            {
                dojo.html.set(postData, ret["data"]);
            }
        }
    });
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
    
    var style = "background-color: #FFFFC0;";
    style += "border-bottom-left-radius: 8px;";
    style += "border-bottom-right-radius: 8px;";
    style += "color: #124C73;";
    style += "cursor: pointer;";
    style += "float: left;";
    style += "font-size: 14px;";
    style += "font-weight: bold;";
    style += "left: 0;";
    style += "margin-left: auto;";
    style += "margin-right: auto;";
    style += "padding: 10px;";
    style += "position: absolute;";
    style += "text-align: center;";
    style += "top: 0;";
    style += "width: 100%;";
    style += "z-index: 100;";
    
    var divCon = dojo.create("div", {
        "style": style
    }, dojo.body(), "first");    
    dojo.html.set(divCon, "You are in edit mode. Leave Edit Mode.");
    
    dojo.connect(divCon, "onclick", function(evt) {
        dojo.setStyle(divCon, {"display": "none"}); 
        cls.deactivateEditMode();
        
		/*
        // Disconnect double click events
        dojo.forEach(cls.connects, function(handle) 
        {
            dojo.disconnect(handle);
            
        });
        
        // Destroy tooltips
        dojo.forEach(cls.tooltips, function(handle) 
        {
            handle.destroy();
            
        });
        
        // Scan again the posts and remove the cursor pointer
        cls.getPostsCon(true);
        
        // Change the url
        window.history.pushState('', '', cls.pushUrl);
		*/
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
    dojo.xhrGet({
        url: "/antapi/deactivate-edit",
        handleAs: "json",
        load: function(ret)
        {
			// Refresh
			location.reload();
        }
    });
}

/**
 * Gets the url of the Ant Server
 *
 * @public
 * @this {Cms}
 */
Cms.prototype.getServerUrl = function()
{
    var url = null;
    var scripts = document.getElementsByTagName("script");
    
    for(script in scripts)
    {
        var currentScript = scripts[script];
        if(currentScript.src.indexOf("Cms.js")>0)
        {
            src = currentScript.src;
            break;
        }
    }

	// Create anchor which exploses hostname as a property
	var l = document.createElement("a");
    l.href = src;
	this.serverUrl = l.hostname;
    
	/*
    var srcParts = src.split("server=");
    
    if(srcParts[1]) // server url is set.
        url = srcParts[1];
    else // if server is not set, try to get the server url when including the javascript file in header
    {
        var re1='.*?';    // Non-greedy match on filler
        var re2='((?:[a-z][a-z\\.\\d\\-]+)\\.(?:[a-z][a-z\\-]+))(?![\\w\\.])';    // Fully Qualified Domain Name 1

        var p = new RegExp(re1+re2,["i"]);
        var m = p.exec(src);
        if (m != null)
            var url = m[0];
    }
    
    if(!url) // if url has no value, try to use the localhost
        url = "localhost";
    else
    {
        // Check if url has http
        var regEx = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        var result = regEx.exec(url);    
        if(!result)
            url = "http://" + url;
    }
    
    this.serverUrl = url;
	*/
}

/**
 * Gets the url for push state url
 *
 * @public
 * @this {Cms}
 */
Cms.prototype.getPushUrl = function()
{
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
dojo.ready(function()
{
    var cmsClass = new Cms();
    //cmsClass.getPostsCon(false);
});
