 /*======================================================================================
    
    Class:      Feedback.js

    Purpose:    Displays feedback image at the bottom right of the browser. 
                When the image is clicked, it will display a dojo dialog form.
                The form will ask for name, email, subject, message, captcha inputs.                

    Author:     Marl Jay Tumulak, marl.tumulak@aereus.com
                Copyright (c) 2011 Aereus Corporation. All rights reserved.
    
    Usage:      var b = new Feedback();
                b.imageLocation = "/images/feedback.png";
                b.displayImage();

======================================================================================*/

function Feedback()
{
    this.properties = new Object(); // used for storing references for callbacks mostly

    this.url = "/antapi/feedback";    
    this.imageLocation = "";
}

/**
* Displays the feedback image on the right side of the browser
*/
Feedback.prototype.displayImage = function()
{

    // Div container for image
    var divFeedback = document.createElement('div');
    divFeedback.style.bottom = 0;
    divFeedback.style.position = "fixed";
    divFeedback.style.width = "100%";
    divFeedback.style.zIndex = "5";
    divFeedback.style.textAlign = "right";

    // Feedback image
    var imageFeedback = document.createElement('img');
    imageFeedback.src = this.imageLocation;
    imageFeedback.title = "Feedback";
    imageFeedback.alt = "Feedback";
    imageFeedback.style.cursor = "pointer";
    imageFeedback.brcls = this
    imageFeedback.onclick = function() 
    {
        // Checks if dojo dialog form is already created
        if(dojo.byId("feedbackForm")){
            this.brcls.feedbackResponse.innerHTML = "";
            this.brcls.generateCaptcha();            
            this.brcls.show();            
        }
        else
            this.brcls.buildInterface();
    }    

    document.body.appendChild(divFeedback);
    divFeedback.appendChild(imageFeedback);
}

/**
* Show the browser dialog
*/
Feedback.prototype.show = function()
{       

    this.dlg.show();
}

/**
* Hide the browser dialog
*/
Feedback.prototype.hide = function()
{
    this.dlg.hide();
}

/**
* Saves feedback input
*/
Feedback.prototype.load = function(formName, btnSave)
{    
    var actionUrl = this.url;    
    this.generateCaptcha();
    dojo.xhrPost({
        url: actionUrl,
        form: formName,        
        load: function(data, ioArgs) 
        {            
            // Clears the input textboxes
            btnSave.inpName.value = "";
            btnSave.inpEmail.value = "";
            btnSave.inpSubject.value = "";
            btnSave.inpMessage.value = "";
            btnSave.response.innerHTML = "Feedback successfully sent.";
        },
        error: function(data, ioArgs) 
        {
            btnSave.response.innerHTML = "Feedback was not sent. Error occured";
        }        
    });
}

/**
* Build dojo dialog feedback form
*/
Feedback.prototype.buildInterface = function()
{
    this.dlg = new dijit.Dialog({
        title: "Feedback",
        style: "width: 400px"
    });

    // Main continer    
    var con = dojo.create("div", {align:"left", style:{marginLeft:"10px"}});
    var divResponse = dojo.create("div", {align:"left", id:"feedbackMessage", style:{minHeight:"20px", paddingLeft:"57px"}}, con);
    var feedbackForm = dojo.create("form", {name:"feedbackForm", id:"feedbackForm", method:"post", action:this.url}, con);

    // Name input
    var divName = dojo.create("div", {style:{marginBottom:"10px"}}, feedbackForm);
    var lblName = dojo.create("label", {innerHTML:"Name: ", style:{marginRight:"15px"}}, divName);
    var inputName = dojo.create("input", {type:"text", style:{width:"300px"}, name:"Name"}, divName);    

    // Email input
    var divEmail = dojo.create("div", {style:{marginBottom:"10px"}}, feedbackForm);
    var lblEmail = dojo.create("label", {innerHTML:"Email: ", style:{marginRight:"15px"}}, divEmail);
    var inputEmail = dojo.create("input", {type:"text", style:{width:"300px"}, name:"Email"}, divEmail);    

    // Subject input
    var divSubject = dojo.create("div", {style:{marginBottom:"10px"}}, feedbackForm);
    var lblSubject = dojo.create("label", {innerHTML:"Subject: ", style:{marginRight:"7px"}}, divSubject);
    var inputSubject = dojo.create("input", {type:"text", style:{width:"300px"}, name:"Subject"}, divSubject);    

    // Message input
    var divMessage = dojo.create("div", {style:{marginBottom:"10px"}}, feedbackForm);
    var lblMessage = dojo.create("label", {innerHTML:"Message: ", style:{marginRight:"4px", float:"left"}}, divMessage);
    var inputMessage = dojo.create("textarea", {col:14, row:5, style:{width:"300px", height:"90px"}, name:"Message"}, divMessage);    

    // Captcha input
    var divCaptcha = dojo.create("div", {style:{marginBottom:"10px"}}, feedbackForm);
    var lblCaptcha = dojo.create("label", {innerHTML:"Code: ", style:{marginRight:"24px", float:"left"}}, divCaptcha);
    var inputCaptcha = dojo.create("input", {type:"text", style:{width:"150px"}}, divCaptcha);
    var lblCaptchaCode = dojo.create("label", {style:{marginLeft:"10px", cursor:"none"}}, divCaptcha);
    this.captchaCode = lblCaptchaCode;
    this.inpCaptcha = inputCaptcha;
    this.generateCaptcha();

    // Sets the properties of captcha objects
    inputCaptcha.onclick = function()
    {
        if(this.value=="Enter code here.")
            {
            this.value = "";
        }
    }

    inputCaptcha.onblur = function()
    {
        if(this.value=="")
            {
            this.value = "Enter code here.";
        }
    }

    inputCaptcha.onkeydown = function(e)
    {

        if(e.keyCode==17){
            alert('Control key is not allowed.')
            e.preventDefault();
        }

        if(e.keyCode==16){
            alert('Shift key is not allowed.')
            e.preventDefault();
        }            
    }

    inputCaptcha.oncontextmenu = function(e) {
        e.preventDefault();
    }

    inputCaptcha.onselectstart = function(e) {
        e.preventDefault();
    }

    // Hidden textboxes
    dojo.create("input", {type:"hidden", name:"url", value:document.URL}, feedbackForm);
    dojo.create("input", {type:"hidden", name:"browser", value:navigator.appName + navigator.appVersion}, feedbackForm);
    dojo.create("input", {type:"hidden", name:"os", value:navigator.platform}, feedbackForm);

    // Div clear
    var divClear = dojo.create("div", {style:{clear:"both"}}, con);
    
    // Close button
    var btnClose = dojo.create("button", {innerHTML:"Close"}, feedbackForm);
    btnClose.brcls = this;    
    btnClose.onclick = function(e)
    {
        e.preventDefault();
        this.brcls.hide();
    }

    // Save button
    var btnSave = dojo.create("button", {innerHTML:"Save"}, feedbackForm);
    dojo.addClass(btnSave, "positive");
    btnSave.inpName = inputName;
    btnSave.inpEmail = inputEmail;
    btnSave.inpSubject = inputSubject;
    btnSave.inpMessage = inputMessage;
    btnSave.response = divResponse;    
    this.feedbackResponse = divResponse;        
    btnSave.brcls = this;
    btnSave.onclick = function(e)
    {        
        e.preventDefault();
        if(this.inpName.value==""||this.inpEmail.value=="")
            {
            this.response.innerHTML = "Invalid Name/Email";
            this.response.style.color = "#e13e3e";
        }
        else if(this.brcls.inpCaptcha.value!==this.brcls.captchaCode.innerHTML)
            {
            this.response.innerHTML = "Invalid code.";
            this.response.style.color = "#e13e3e";
        }
        else
            {
            this.response.style.color = "#000000";
            this.response.innerHTML = "Sending feedback...";        
            this.brcls.load("feedbackForm", this);
        }
    }    
    // Set content
    this.dlg.set("content", con);
    this.dlg.show();
}

/**
* Generates captcha code
*/
Feedback.prototype.generateCaptcha = function()
{
    var a = Math.ceil(Math.random() * 9)+ '';
    var b = Math.ceil(Math.random() * 9)+ '';       
    var c = Math.ceil(Math.random() * 9)+ '';  
    var d = Math.ceil(Math.random() * 9)+ '';  
    var e = Math.ceil(Math.random() * 9)+ '';  

    var code = a + b + c + d + e;
    this.captchaCode.innerHTML = code;
    this.inpCaptcha.value = "Enter code here."
}
