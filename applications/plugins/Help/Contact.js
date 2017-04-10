/**
 * @fileoverview This is an inline contact form used to send requests to Aereus for support
 */

/**
 * Class contructor
 *
 * @constructor
 */
function Plugin_Help_Contact()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.contactForm = new Object();    
}

Plugin_Help_Contact.prototype.print = function(antView)
{
    this.mainCon = antView.con;
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "objectLoaderHeader";
    this.titleCon.innerHTML = "Contact Support";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    this.buildInterface();
}

Plugin_Help_Contact.prototype.buildInterface = function()
{
    var divDesc = alib.dom.createElement("div", this.innerCon);
	alib.dom.styleSetClass(divDesc, "info");
	alib.dom.styleSet(divDesc, "width", "550px");
    divDesc.innerHTML = "Use this form to request help and support. Once submitted you will be contacted shortly concerning your inquiry.";
    
    var divContact = alib.dom.createElement("div", this.innerCon);    
    
    var tableForm = alib.dom.createElement("table", divContact);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.contactForm = new Object();
    
    this.contactForm.subject = createInputAttribute(alib.dom.createElement("input"), "text", "subject", "Subject", "435px");
    
    this.contactForm.description = createInputAttribute(alib.dom.createElement("textarea"), null, "description", "How can we help you?");
    alib.dom.styleSet(this.contactForm.description, "width", "430px");
    alib.dom.styleSet(this.contactForm.description, "height", "200px");
    
    buildFormInput(this.contactForm, tBody);

	// Add the send button
    var buttonCon = alib.dom.createElement("div", this.innerCon);    
	alib.dom.styleSet(buttonCon, "margin-top", "10px");
	alib.dom.styleSet(buttonCon, "text-align", "right");
	alib.dom.styleSet(buttonCon, "width", "550px");

	var button = alib.ui.Button("Send", {
		className:"b2", tooltip:"Click to send request", cls:this, 
		onclick:function() {this.cls.submitCase(); }
	});

	button.print(buttonCon);
}

/*************************************************************************
*    Function:    submitCase
*
*    Purpose:    send Case
**************************************************************************/
Plugin_Help_Contact.prototype.submitCase = function()
{    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = showDialog("Submitting your request, please wait...");
    ajax.onload = function(ret)
    {   
        /*var url = window.location.href.split('#');
        url = url[0].split('?');
        var hash = window.location.hash.split('/');
        var href = url[0] + '?sent' + hash[0] + '/Case';
        window.location.href = href;*/
        //window.location.hash = "Help/Case";
        
        /*this.cls.mainCon.innerHTML = '';
        var app = new AntApp('help');
        app.loadPlugin('Plugin_Help_Case', this.cls.mainCon);*/
        
        this.cls.contactForm.subject.value = "";
        this.cls.contactForm.description.value = "";
        
        ALib.statusShowAlert("New case has been sent.", 3000, "bottom", "right");
        this.dlg.hide();
    };
    
    var args = new Array();
    args[args.length] = ['subject', this.contactForm.subject.value];
    args[args.length] = ['description', this.contactForm.description.value];
    ajax.exec("/controller/Help/submitCase", args);
}
