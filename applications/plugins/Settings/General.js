function Plugin_Settings_General()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.themeName = null;
    
    this.generalForm = new Object();
    this.generalData = new Object();
}

Plugin_Settings_General.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "General Settings";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        this.cls.generalData = ret;
        this.cls.themeName = ret.themeName;
        this.cls.buildInterface();        
    };    
    ajax.exec("/controller/Admin/getGeneralSetting");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

Plugin_Settings_General.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    var toolbar = alib.dom.createElement("div", this.innerCon);
    var tb = new CToolbar();
    
    // Add Application
    var btn = new CButton("Save", 
    function(cls)
    {
        cls.saveSettings();
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    this.basicSettings();
    //this.welcomeImage();
    this.applicationLogo();    
    this.publicLogo();    
    this.loginImage();    
    this.paymentGateway();
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

/*************************************************************************
*    Function:    basicSettings
*
*    Purpose:    Creates the Basic Settings
**************************************************************************/
Plugin_Settings_General.prototype.basicSettings = function()
{
    var divBasic = alib.dom.createElement("div", this.innerCon);    
    
    var divLeft = alib.dom.createElement("div", divBasic);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Basic Settings";
    
    var divRight = alib.dom.createElement("div", divBasic);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    var orgName = "My Company";
    if(this.generalData.orgName)
        orgName = this.generalData.orgName;
    
    this.generalForm.basic = new Object();
    this.generalForm.basic.orgName = createInputAttribute(alib.dom.createElement("input"), "text", "orgName", "Org. Name", "300px", orgName);
    this.generalForm.basic.orgName.inputLabel = " (REQUIRED) Enter the name of your organization.";
    
    var website = "www.mycompany.com";
    if(this.generalData.website)
        website = this.generalData.website;
    
    this.generalForm.basic.website = createInputAttribute(alib.dom.createElement("input"), "text", "website", "Website", "300px", website);
    this.generalForm.basic.website.inputLabel = " (OPTIONAL) Enter the website for your organization.";    
    this.generalForm.basic.lbl = createInputAttribute(alib.dom.createElement("label"));
    this.generalForm.basic.lbl.inputLabel = "Please exclude any protocol like \"http://\". Example www.mycompany.com <br />rather than http://www.mycompany.com";

    // No-reply email
	var noreply = "no-reply@aereus.com";
    if(this.generalData.noreply)
        noreply = this.generalData.noreply;
    
    this.generalForm.basic.noreply = createInputAttribute(alib.dom.createElement("input"), "text", "noreply", "No-reply Email", "300px", noreply);
    this.generalForm.basic.noreply.inputLabel = " (REQUIRED) Automated email from address.";
    this.generalForm.basic.nrlbl = createInputAttribute(alib.dom.createElement("label"));
    this.generalForm.basic.nrlbl.inputLabel = "All automated email will be sent 'From' this email address.";

    buildFormInput(this.generalForm.basic, tBody);       
    
    divClear(divBasic);
}

/*************************************************************************
*    Function:    welcomeImage
*
*    Purpose:    Creates Welcome Widget Image
**************************************************************************/
Plugin_Settings_General.prototype.welcomeImage = function()
{
    var divWelcome = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divWelcome, "borderTop", "1px solid");
    alib.dom.styleSet(divWelcome, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divWelcome);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Welcome Widget Image";
    
    var divDesc = alib.dom.createElement("div", divLeft);    
    divDesc.innerHTML = "The welcome widget is displayed<br />on the home page. You can set the<br />background image here.";
    
    var divCenter = alib.dom.createElement("div", divWelcome);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "210px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");
    alib.dom.styleSet(divCenter, "marginLeft", "65px");
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var welcomeDefault = "/images/themes/"+this.themeName+"/greeting.png";
    var welcomeImage = welcomeDefault;
    if(this.generalData.welcomeImageId)
        welcomeImage = "/antfs/images/"+this.generalData.welcomeImageId+"/210";
    
    var welcomeImg = alib.dom.createElement("img", divCenter);
    welcomeImg.setAttribute("width", "210px");
    welcomeImg.setAttribute("src", welcomeImage);
    
    
    var divRight = alib.dom.createElement("div", divWelcome);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.welcome = new Object();
    
    // Header Image Image - Hidden Input
    this.generalForm.welcome.hdnImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "welcomeImageId", null, null, this.generalData.welcomeImageId);
    
    // Welcome Image Image - Button
    this.generalForm.welcome.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnWelcome", null, "110px", "Select Image");
    this.generalForm.welcome.button.inputLabel = " Select an image to use as a background";
    
    this.generalForm.welcome.button.cls = this;
    this.generalForm.welcome.button.welcomeImg = welcomeImg;
    this.generalForm.welcome.button.onclick = function()
    {
		var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
		cbrowser.cbData.cls = this.cls;
		cbrowser.cbData.welcomeImg =this.welcomeImg;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.welcomeImg.setAttribute("src", "/antfs/images/"+id+"/210");
            this.cbData.cls.generalForm.welcome.hdnImageId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.generalForm.welcome.dfault = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "110px", "Default Image");
    this.generalForm.welcome.dfault.inputLabel = " Use the organization name for the application header";
    
    this.generalForm.welcome.dfault.cls = this;    
    this.generalForm.welcome.dfault.welcomeDefault = welcomeDefault;
    this.generalForm.welcome.dfault.welcomeImg = welcomeImg;
    this.generalForm.welcome.dfault.onclick = function()
    {
        this.welcomeImg.setAttribute("src", this.welcomeDefault);        
        this.cls.generalForm.welcome.hdnImageId.value = "";
    }
    
    buildFormInput(this.generalForm.welcome, tBody);
    
    divClear(divWelcome);
}

/*************************************************************************
*    Function:    applicationLogo
*
*    Purpose:    Creates Application Header Logo
**************************************************************************/
Plugin_Settings_General.prototype.applicationLogo = function()
{
    var divApplication = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divApplication, "borderTop", "1px solid");
    alib.dom.styleSet(divApplication, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divApplication);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Application Header logo";
    
    var divDesc = alib.dom.createElement("div", divLeft);    
    divDesc.innerHTML = "You can select an application logo<br />to use in the header for this<br />application. It is recommended that<br />you use an image that is 210 X 60px<br />for best results.";
    
    var divCenter = alib.dom.createElement("div", divApplication);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "210px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");
    alib.dom.styleSet(divCenter, "marginLeft", "65px");
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var applicationLogoId = this.generalData.applicationLogoId;
    var lblTxtLogo = alib.dom.createElement("p", divCenter);    
    var applicationImg = alib.dom.createElement("img", divCenter);
        
    if(applicationLogoId)
    {
        if(!applicationLogoId)
            lblTxtLogo.innerHTML = this.generalData.orgName;
        else
            applicationImg.setAttribute("src", "/antfs/images/"+applicationLogoId+"/210/60");
    }        
    else
        lblTxtLogo.innerHTML = "<img src='/images/logo_24.png' />";
    
    var divRight = alib.dom.createElement("div", divApplication);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.application = new Object();
    
    // Header Image Image - Hidden Input
    this.generalForm.application.hdnApplicationLogoId = createInputAttribute(alib.dom.createElement("input"), "hidden", "applicationLogoId", null, null, applicationLogoId);
    
    // Application Image Image - Button
    this.generalForm.application.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnApplication", null, "120px", "Select Image");
    this.generalForm.application.button.inputLabel = " Select an image to use for the application header";
    
    this.generalForm.application.button.cls = this;
    this.generalForm.application.button.divCenter = divCenter;
    this.generalForm.application.button.onclick = function()
    {
		var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
		cbrowser.cbData.cls = this.cls;
		cbrowser.cbData.divCenter = this.divCenter;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.divCenter.innerHTML = "";
            var applicationImg = alib.dom.createElement("img", this.cbData.divCenter);            
            applicationImg.setAttribute("src", "/antfs/images/"+id+"/210/60");
            
            this.cbData.cls.generalForm.application.hdnApplicationLogoId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.generalForm.application.dfault = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "120px", "Default Image");
    this.generalForm.application.dfault.inputLabel = " Use the organization name for the application header";
    
    this.generalForm.application.dfault.cls = this;    
    this.generalForm.application.dfault.divCenter = divCenter;
    this.generalForm.application.dfault.onclick = function()
    {
        this.divCenter.innerHTML = "";
        var lblTxtLogo = alib.dom.createElement("p", this.divCenter);
        lblTxtLogo.innerHTML = "<img src='/images/logo_24.png' />";
        this.cls.generalForm.application.hdnApplicationLogoId.value = null;
    }
    
    buildFormInput(this.generalForm.application, tBody);
    
    divClear(divApplication);
}

/*************************************************************************
*    Function:    publicLogo
*
*    Purpose:    Creates Public Header Logo
**************************************************************************/
Plugin_Settings_General.prototype.publicLogo = function()
{
    var divPublic = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divPublic, "borderTop", "1px solid");
    alib.dom.styleSet(divPublic, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divPublic);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Public Pages Logo";
    
    var divDesc = alib.dom.createElement("div", divLeft);    
    divDesc.innerHTML = "The public header logo is used when<br />displaying public pages like<br />calendar event proposals. It is<br />recommended that you use an<br />image that is 60px high and no more than 300px wide.";
    
    var divCenter = alib.dom.createElement("div", divPublic);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "210px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");
    alib.dom.styleSet(divCenter, "marginLeft", "65px");
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var publicImageId = this.generalData.publicImageId;
    var lblTxtLogo = alib.dom.createElement("p", divCenter);    
    var publicImg = alib.dom.createElement("img", divCenter);
    
    if(publicImageId)
    {
        if(isNaN(publicImageId))
            lblTxtLogo.innerHTML = publicImageId;
        else
            publicImg.setAttribute("src", "/antfs/images/"+publicImageId+"/210/60");
    }        
    else
        lblTxtLogo.innerHTML = "<img src='/images/logo_public.png' />";
    
    var divRight = alib.dom.createElement("div", divPublic);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.publicImage = new Object();
    
    // Header Image Image - Hidden Input
    this.generalForm.publicImage.hdnPublicImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "publicImageId", null, null, publicImageId);
    
    // Public Image Image - Button
    this.generalForm.publicImage.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnPublic", null, "120px", "Select Image");
    this.generalForm.publicImage.button.inputLabel = " Select an image to use for the public header";
    
    this.generalForm.publicImage.button.cls = this;
    this.generalForm.publicImage.button.divCenter = divCenter;
    this.generalForm.publicImage.button.onclick = function()
    {
		var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
		cbrowser.cbData.cls = this.cls;
		cbrowser.cbData.divCenter = this.divCenter;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.divCenter.innerHTML = "";
            var publicImg = alib.dom.createElement("img", this.cbData.divCenter);            
            publicImg.setAttribute("src", "/antfs/images/"+id+"/210/60");
            
            this.cbData.cls.generalForm.publicImage.hdnPublicImageId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.generalForm.publicImage.dfault = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "120px", "Use Default");
    this.generalForm.publicImage.dfault.inputLabel = " Use the organization name for the public header";
    
    this.generalForm.publicImage.dfault.cls = this;    
    this.generalForm.publicImage.dfault.divCenter = divCenter;
    this.generalForm.publicImage.dfault.onclick = function()
    {
        this.divCenter.innerHTML = "";
        var lblTxtLogo = alib.dom.createElement("p", this.divCenter);
        lblTxtLogo.innerHTML = this.cls.generalForm.basic.orgName.value;
        this.cls.generalForm.publicImage.hdnPublicImageId.value = lblTxtLogo.innerHTML;
    }
    
    buildFormInput(this.generalForm.publicImage, tBody);
    
    divClear(divPublic);
}

/*************************************************************************
*    Function:    loginImage
*
*    Purpose:    Creates Login Image
**************************************************************************/
Plugin_Settings_General.prototype.loginImage = function()
{
    var divLogin = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divLogin, "borderTop", "1px solid");
    alib.dom.styleSet(divLogin, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divLogin);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Login Widget Image";
    
    var divDesc = alib.dom.createElement("div", divLeft);    
    divDesc.innerHTML = "This is the image users will see when<br />they log into the main application.";
    
    var divCenter = alib.dom.createElement("div", divLogin);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "210px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");
    alib.dom.styleSet(divCenter, "marginLeft", "65px");
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var loginDefault = "/images/logo_login.png";
    var loginImage = loginDefault;
    if(this.generalData.loginImageId)
        loginImage = "/antfs/images/"+this.generalData.loginImageId+"/210";
    
    var loginImg = alib.dom.createElement("img", divCenter);
    loginImg.setAttribute("width", "210px");
    loginImg.setAttribute("src", loginImage);
    
    var divRight = alib.dom.createElement("div", divLogin);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.login = new Object();
    
    // Header Image Image - Hidden Input
    this.generalForm.login.hdnLoginImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "loginImageId", null, null, this.generalData.loginImageId);
    
    // Login Image Image - Button
    this.generalForm.login.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnLogin", null, "110px", "Select Image");
    this.generalForm.login.button.inputLabel = " Select an image to display when users log in";
    
    this.generalForm.login.button.cls = this;
    this.generalForm.login.button.loginImg = loginImg;
    this.generalForm.login.button.onclick = function()
    {
		var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
		cbrowser.cbData.cls = this.cls;
		cbrowser.cbData.loginImg = this.loginImg;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.loginImg.setAttribute("src", "/antfs/images/"+id+"/210");            
            this.cbData.cls.generalForm.login.hdnLoginImageId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.generalForm.login.dfault = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "110px", "Use Default");
    this.generalForm.login.dfault.inputLabel = " Use the organization name for the application header";
    
    this.generalForm.login.dfault.cls = this;    
    this.generalForm.login.dfault.loginDefault = loginDefault;
    this.generalForm.login.dfault.loginImg = loginImg;
    this.generalForm.login.dfault.onclick = function()
    {
        this.loginImg.setAttribute("src", this.loginDefault);        
        this.cls.generalForm.login.hdnLoginImageId.value = "";
    }
    
    buildFormInput(this.generalForm.login, tBody);
    
    divClear(divLogin);
}

/*************************************************************************
*    Function:    paymentGateway
*
*    Purpose:    Creates Payment Gateway
**************************************************************************/
Plugin_Settings_General.prototype.paymentGateway = function()
{
    var divPayment = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divPayment, "borderTop", "1px solid");
    alib.dom.styleSet(divPayment, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divPayment);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Payment Gateway";
    
    var divDesc = alib.dom.createElement("div", divLeft);    
    divDesc.innerHTML = "Entering your payment gateway<br />information will allow ANT to accept<br />credit/debit cards to pay invoices";
    
    var divRight = alib.dom.createElement("div", divPayment);
    alib.dom.styleSet(divRight, "float", "left");
    alib.dom.styleSet(divRight, "marginLeft", "65px");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.payment = new Object();
    this.generalForm.payment.gateway = createInputAttribute(alib.dom.createElement("select", divRight), null, "paymentGateway");
    var divFields = alib.dom.createElement("div", divRight);
    
    this.generalForm.payment.gateway[this.generalForm.payment.gateway.length] = new Option("No Gateway Selected", "");
    
    for(pmtgw in this.generalData.pmtgw)
    {
        var currentPmgtw = this.generalData.pmtgw[pmtgw];
        var selected = false;
        
        if(currentPmgtw.id == this.generalData.paymentGateway)
        {            
            switch(currentPmgtw.id)
            {
                case 1: // Authorized.net
                    this.loadAuthDotNet(divFields);
                    break;
				case 3: // LinkPoint
					this.loadLinkPoint(divFields);
					break;
            }
            selected = true;
        }            
        
        this.generalForm.payment.gateway[this.generalForm.payment.gateway.length] = new Option(currentPmgtw.name, currentPmgtw.id, false, selected);
    }
    
    
    this.generalForm.payment.gateway.cls = this;
    this.generalForm.payment.gateway.divFields = divFields;
    this.generalForm.payment.gateway.onchange = function()
    {
        this.divFields.innerHTML = "";
        switch(this.value)
        {
		case "1": // Authorized.net
			this.cls.loadAuthDotNet(divFields);
			break;
		case "3": // LinkPoint
			this.cls.loadLinkPoint(divFields);
			break;
        }
    }
    
    divClear(divPayment);
}

/*************************************************************************
*    Function:    loadAuthDotNet
*
*    Purpose:    load the fields for authorize.net
**************************************************************************/
Plugin_Settings_General.prototype.loadAuthDotNet = function(con)
{
    var tableForm = alib.dom.createElement("table", con);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.authDotNet = new Object();
    this.generalForm.authDotNet.login = createInputAttribute(alib.dom.createElement("input"), "text", "authDotNetLogin", "Login", "150px", this.generalData.authDotNetLogin);
    
    this.generalForm.authDotNet.key = createInputAttribute(alib.dom.createElement("input"), "text", "authDotNetKey", "Key", "150px", this.generalData.authDotNetKey);
    buildFormInput(this.generalForm.authDotNet, tBody);
}

/**
 * Build form for gathering linkpoint payment gateway params
 *
 * @param {DOMElement} com The container to print the form in
 */
Plugin_Settings_General.prototype.loadLinkPoint = function(con)
{
    var tableForm = alib.dom.createElement("table", con);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.generalForm.linkPoint = new Object();
    this.generalForm.linkPoint.store = createInputAttribute(alib.dom.createElement("input"), "text", "pmtLinkPointStore", "Store/ConfigFile", "150px", this.generalData.pmtLinkPointStore);
    
    this.generalForm.linkPoint.pem = createInputAttribute(alib.dom.createElement("textarea"), null, "pmtLinkPointPem", "PEM/Cert Text", "450px", this.generalData.pmtLinkPointPem);
    buildFormInput(this.generalForm.linkPoint, tBody);
}

/*************************************************************************
*    Function:    saveSettings
*
*    Purpose:    Saves the Settings
**************************************************************************/
Plugin_Settings_General.prototype.saveSettings = function()
{
    var args = new Array();
    for(var setting in this.generalForm)
    {
        var currentSetting = this.generalForm[setting];
        for(var subSetting in currentSetting)
        {
            var currentSubSetting = currentSetting[subSetting];
            
            var value = "";
            switch(currentSubSetting.type)
            {
                case "button":
                    continue;
                    break;
                case "checkbox":
                    value = (currentSubSetting.checked) ? 1:0;
                    break;
                case "select-one":
                case "text":
                default:
                    value = currentSubSetting.value; 
                    break;
            }
            args[args.length] = [currentSubSetting.id, value];
        }
    }   
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = showDialog("Saving general settings, please wait...");
    ajax.onload = function(ret)
    {   
        this.dlg.hide();
        ALib.statusShowAlert("General Settings Saved!", 3000, "bottom", "right");
    }
    ajax.exec("/controller/Admin/saveGeneral", args);
}
