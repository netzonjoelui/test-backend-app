/**
 * Profile settings application plugin
 */
function Plugin_Settings_Profile()
{
	/**
	 * Main container
	 *
	 * @var {DOMElement}
	 */
    this.mainCon = null;

	/**
	 * Inner container - inside the main contianer
	 *
	 * @var {DOMElement}
	 */
    this.innerCon = null;
    
	/**
	 * The id of the current user
	 *
	 * @var int
	 */
    this.userId = null;

	/**
	 * Current user object
	 *
	 * @var {CAntObject}
	 */
	this.userObj = new CAntObject("user");
    
	/**
	 * Object containing form params
	 *
	 * @var {Object}
	 */
    this.profileForm = new Object();
}

/**
 * Load the user and print/render the plugin to the passed antView
 *
 * @param {AntView} antView
 */
Plugin_Settings_Profile.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "My Profile";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";    
    
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.onload = function(ret)
    {
        this.cbData.cls.userId = ret.id;
		this.cbData.cls.userObj.cbData.cls = this.cbData.cls;

		this.cbData.cls.userObj.onload = function()
		{
			this.cbData.cls.buildInterface();        
		}

		this.cbData.cls.userObj.load(ret.id);
    };
    ajax.exec("/controller/User/getCurrentUser");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

/**
 * Render the interface now that we know the user object is loaded
 */
Plugin_Settings_Profile.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    
    var toolbar = alib.dom.createElement("div", this.innerCon);
    var tb = new CToolbar();
    
    var btn = alib.ui.Button("Save", {
		className:"b1", cls:this,
		onclick:function() 
		{
			this.cls.saveSettings();
		}
	});

    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    this.basicSettings();
    this.passwordSettings();
    this.profilePicture();
    this.contactSettings();
    this.facebookSettings();
    
    this.loadDropdownData();
}

/*************************************************************************
*    Function:    loadDropdownData
*
*    Purpose:    Load Dropdown Data
**************************************************************************/
Plugin_Settings_Profile.prototype.loadDropdownData = function()
{
    // theme    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        for(theme in ret)
        {
            var currentTheme = ret[theme];
            
            var selected = false;
            if(currentTheme.name == this.cls.userObj.getValue("theme"))
                selected = true;
            
            this.cls.profileForm.basic.theme[this.cls.profileForm.basic.theme.length] = new Option(currentTheme.title, currentTheme.name, false, selected);
        }
    };
    ajax.exec("/controller/User/getThemes");
    
    // timezone    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        for(timezone in ret)
        {
            var currentTimezone = ret[timezone];
            
            var selected = false;
            if(currentTimezone == this.cls.userObj.getValue("timezone"))
                selected = true;
            
            this.cls.profileForm.basic.timezone[this.cls.profileForm.basic.timezone.length] = new Option(currentTimezone, currentTimezone, false, selected);
        }
    };
    ajax.exec("/controller/User/getTimezones");
    
    // carrier    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        for(carrier in ret)
        {
            var currentCarrier = ret[carrier];
            
            var selected = false;
            if(currentCarrier.id == this.cls.userObj.getValue("phone_mobile_carrier"))
                selected = true;
            
            this.cls.profileForm.contact.carrier[this.cls.profileForm.contact.carrier.length] = new Option(currentCarrier.name, currentCarrier.id, false, selected);
        }
    };
    ajax.exec("/controller/User/getCarriers");
}

/*************************************************************************
*    Function:    basicSettings
*
*    Purpose:    Creates the Basic Settings
**************************************************************************/
Plugin_Settings_Profile.prototype.basicSettings = function()
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
    
    this.profileForm.basic = new Object();
    
    this.profileForm.basic.userName = createInputAttribute(alib.dom.createElement("input"), "text", "userName", "User Name", 
														   "300px", this.userObj.getValue("name"));
    this.profileForm.basic.userName.setAttribute("disabled", "");
    
    this.profileForm.basic.fullName = createInputAttribute(alib.dom.createElement("input"), "text", "fullName", "Full Name", 
														   "300px", this.userObj.getValue("full_name"));
    
	// Theme
	// -----------------------------------
    this.profileForm.basic.theme = createInputAttribute(alib.dom.createElement("select", divRight), 
														null, "theme", "Visual Theme", null, 
														this.userObj.getValue("theme"));
    this.profileForm.basic.theme[this.profileForm.basic.theme.length] = new Option("Select Theme", "");

	alib.dom.addEvent(this.profileForm.basic.theme, "change", function() { if (Ant) Ant.changeTheme(this.value); });

	// Timezone
	// -----------------------------------
    this.profileForm.basic.timezone = createInputAttribute(alib.dom.createElement("select", divRight), null, "timezone", 
														   "Default Timezone", null, this.userObj.getValue("timezone"));
    this.profileForm.basic.timezone[this.profileForm.basic.timezone.length] = new Option("Select Timezone", "");
    
    buildFormInput(this.profileForm.basic, tBody);
    
    divClear(divBasic);
}
 
/*************************************************************************
*    Function:    passwordSettings
*
*    Purpose:    Creates the Password Settings
**************************************************************************/
Plugin_Settings_Profile.prototype.passwordSettings = function()
{
    var divPassword = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divPassword, "borderTop", "1px solid");
    alib.dom.styleSet(divPassword, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divPassword);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Reset Password";
    
    var divRight = alib.dom.createElement("div", divPassword);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.profileForm.password = new Object();
    
    this.profileForm.password.currentP = createInputAttribute(alib.dom.createElement("input"), "password", "currentPassword", "Current Password", "200px");
    this.profileForm.password.currentP.inputLabel = " To reset your password, first enter your current password";
    
    this.profileForm.password.newP = createInputAttribute(alib.dom.createElement("input"), "password", "newPassword", "New Password", "200px");
    this.profileForm.password.newP.inputLabel = " Enter the new password here";
    
    this.profileForm.password.verifyP = createInputAttribute(alib.dom.createElement("input"), "password", "verifyPassword", "Verify Password", "200px");
    this.profileForm.password.verifyP.inputLabel = " Click \"Save\" above to reset your password";
    
    buildFormInput(this.profileForm.password, tBody);
    
    divClear(divPassword);
}

/*************************************************************************
*    Function:    profilePicture
*
*    Purpose:    Creates Picture Widget Image
**************************************************************************/
Plugin_Settings_Profile.prototype.profilePicture = function()
{
    var divPicture = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divPicture, "borderTop", "1px solid");
    alib.dom.styleSet(divPicture, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divPicture);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Profile Picture";
    
    var divCenter = alib.dom.createElement("div", divPicture);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "140px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");    
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var pictureImage = null;
    var imageId = this.userObj.getValue("image_id");
    if(imageId)
        pictureImage = "/files/images/"+imageId+"/140";
    
    var pictureImg = alib.dom.createElement("img", divCenter);
    pictureImg.setAttribute("width", "140px");
    pictureImg.setAttribute("src", pictureImage);
    
    var spanCenter = alib.dom.createElement("span", divCenter);    
    spanCenter.innerHTML = "&nbsp;";
    
    var divRight = alib.dom.createElement("div", divPicture);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.profileForm.picture = new Object();
    
    // Header Image Image - Hidden Input
    this.profileForm.picture.hdnImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "imageId", null, null, imageId);
    
    // Picture Image Image - Button
    this.profileForm.picture.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnPicture", null, "120px", "Select Image");
    this.profileForm.picture.button.inputLabel = " Select an image to use as your profile picture";
    
    this.profileForm.picture.button.cls = this;
    this.profileForm.picture.button.pictureImg = pictureImg;
    this.profileForm.picture.button.onclick = function()
    {
        var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
        cbrowser.cbData.cls = this.cls;
        cbrowser.cbData.pictureImg = this.pictureImg;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.pictureImg.setAttribute("src", "/files/images/"+id+"/140");
            this.cbData.cls.profileForm.picture.hdnImageId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.profileForm.picture.remove = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "120px", "Remove Image");
    this.profileForm.picture.remove.inputLabel = " Do not use this image for my profile picture";
    
    this.profileForm.picture.remove.cls = this;        
    this.profileForm.picture.remove.pictureImg = pictureImg;
    this.profileForm.picture.remove.onclick = function()
    {
        this.pictureImg.setAttribute("src", "");
        this.cls.profileForm.picture.hdnImageId.value = "";
    }
    
    buildFormInput(this.profileForm.picture, tBody);
    
    divClear(divPicture);
}

/*************************************************************************
*    Function:    phoneSettings
*
*    Purpose:    Creates the Phone Settings
**************************************************************************/
Plugin_Settings_Profile.prototype.contactSettings = function()
{
    var divPhone = alib.dom.createElement("div", this.innerCon);    
    alib.dom.styleSet(divPhone, "borderTop", "1px solid");
    alib.dom.styleSet(divPhone, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divPhone);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Contact";
    
    var divRight = alib.dom.createElement("div", divPhone);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.profileForm.contact = new Object();

    this.profileForm.contact.email = createInputAttribute(alib.dom.createElement("input"), "text", "email", "Email", "150px", this.userObj.getValue("email"), "95px");
    
    this.profileForm.contact.mobilePhone = createInputAttribute(alib.dom.createElement("input"), "text", "mobilePhone", "Mobile Phone", "150px", this.userObj.getValue("phone_mobile"), "95px");
    
    this.profileForm.contact.officePhone = createInputAttribute(alib.dom.createElement("input"), "text", "officePhone", "Office Phone", "150px", this.userObj.getValue("phone_office"), "95px");
    this.profileForm.contact.officePhone.inputLabel = " Enter your direct line number here";
    
    this.profileForm.contact.officeExt = createInputAttribute(alib.dom.createElement("input"), "text", "officeExt", "Office Ext", "150px", this.userObj.getValue("phone_ext"), "95px");
    this.profileForm.contact.officeExt .inputLabel = " If applicable, enter your extension number here";
    
    buildFormInput(this.profileForm.contact, tBody);
    
    var mobilePhoneCon = this.profileForm.contact.mobilePhone.parentNode;
    var carrierLabel = createInputAttribute(alib.dom.createElement("label", mobilePhoneCon));
    carrierLabel.innerHTML = " Carrier";
    
    this.profileForm.contact.carrier = createInputAttribute(alib.dom.createElement("select", mobilePhoneCon), null, "phoneCarrier");
    
    divClear(divPhone);
}

/*************************************************************************
*    Function:    emailSettings
*
*    Purpose:    Creates the Email Settings
**************************************************************************/
/*
Plugin_Settings_Profile.prototype.emailSettings = function()
{
    var divEmail = alib.dom.createElement("div", this.innerCon);    
    alib.dom.styleSet(divEmail, "borderTop", "1px solid");
    alib.dom.styleSet(divEmail, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divEmail);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Email";
    
    var divRight = alib.dom.createElement("div", divEmail);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.profileForm.email = new Object();
    
    this.profileForm.email.displayName = createInputAttribute(alib.dom.createElement("input"), "text", "displayName", "Display Name", "160px", this.profileData.email.displayName, "95px");
    this.profileForm.email.displayName.inputLabel = " Your full name to be displayed when email is sent";
    
    this.profileForm.email.emailAddress = createInputAttribute(alib.dom.createElement("input"), "text", "emailAddress", "Email Address", "160px", this.profileData.email.emailAddress, "95px");
    this.profileForm.email.emailAddress .inputLabel = " This is where all email correspondence will be sent";
    
    this.profileForm.email.replyTo = createInputAttribute(alib.dom.createElement("input"), "text", "replyTo", "Reply To", "160px", this.profileData.email.replyTo, "95px");
    this.profileForm.email.replyTo.inputLabel = " Enter only if you have different email from email address above";
    
    buildFormInput(this.profileForm.email, tBody);
    
    divClear(divEmail);
}
*/

/*************************************************************************
*    Function:    facebookSettings
*
*    Purpose:    Creates the Facebook Settings
**************************************************************************/
Plugin_Settings_Profile.prototype.facebookSettings = function()
{
    var divFacebook = alib.dom.createElement("div", this.innerCon);    
    alib.dom.styleSet(divFacebook, "borderTop", "1px solid");
    alib.dom.styleSet(divFacebook, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divFacebook);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Facebook Integration";
    
    var divRight = alib.dom.createElement("div", divFacebook);
    alib.dom.styleSet(divRight, "float", "left");
    
    var divDesc = alib.dom.createElement("div", divRight);    
    divDesc.innerHTML = "Linking your facebook account allows for personalized information like photos<br />and interests to be integrated into your account.";
    
    var divLink = alib.dom.createElement("div", divRight);
    this.socialFb(divLink);
    
    divClear(divFacebook);
}

/*************************************************************************
*    Function:    saveSettings
*
*    Purpose:    Saves the Settings
**************************************************************************/
Plugin_Settings_Profile.prototype.saveSettings = function()
{
    var args = new Array();
    for(var setting in this.profileForm)
    {
        var currentSetting = this.profileForm[setting];
        for(var subSetting in currentSetting)
        {
            var currentSubSetting = currentSetting[subSetting];
            
            var value = "";
            switch(currentSubSetting.type)
            {
                case "button":                    
                case "checkbox":
                    continue;
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

    // save profile
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.args = args;
    ajax.dlg = showDialog("Saving Profile, please wait...");
    ajax.onload = function(ret)
    {
        var currentPLabel = this.cls.profileForm.password.currentP.parentNode.parentNode.firstChild;
        var newPLabel = this.cls.profileForm.password.newP.parentNode.parentNode.firstChild;
        var verifyPLabel = this.cls.profileForm.password.verifyP.parentNode.parentNode.firstChild;
        
        alib.dom.styleSet(currentPLabel, "color", "#000000");
        alib.dom.styleSet(newPLabel, "color", "#000000");
        alib.dom.styleSet(verifyPLabel, "color", "#000000");
        
        if(ret.error)
        {
            ALib.statusShowAlert(ret.error, 5000, "bottom", "right");
            if(ret.errorId == 3)
                alib.dom.styleSet(currentPLabel, "color", "red");
            else
            {
                alib.dom.styleSet(newPLabel, "color", "red");
                alib.dom.styleSet(verifyPLabel, "color", "red");
            }
            this.dlg.hide();
        }
		else
		{
			this.dlg.hide();
			ALib.statusShowAlert("Profile Settings Saved!", 3000, "bottom", "right");
		}
    }
    ajax.exec("/controller/User/saveProfile", args);
}

/**
 * Print connect with facebook button or connetion status if already connected
 *
 * @param {DOMElement} divFb The contianer where the connect to facebook button will reside
 */
Plugin_Settings_Profile.prototype.socialFb = function(divFb)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.divFb = divFb;
    ajax.onload = function(ret)
    {
        if (ret == "-1")
        {
            this.divFb.innerHTML = "";
            var img = alib.dom.createElement("img", this.divFb);
            img.src = '/images/facebook_connect.gif';
            img.style.cursor = "pointer";
            img.cls = this.cls;
            img.divFb = this.divFb;
            img.onclick = function()
            {
                 var params = 'width=600,height=300,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
                window.open('/controller/Social/fb', 'soc_fb', params);
                this.cls.socialBeginFbStatusCheck(this.divFb);
                this.divFb.innerHTML = "<div class='loading'></div>";
            }
        }
        else
        {
            this.divFb.innerHTML = "<strong>Congratulations, your Facebook account has been linked</strong> &nbsp;";
            var FbUnlink = alib.dom.createElement("a", this.divFb);
            FbUnlink.href = 'javascript:void(0);';
            FbUnlink.innerHTML = "[unlink]";
            FbUnlink.cls = this.cls;
            FbUnlink.divFb = this.divFb;
            FbUnlink.onclick = function()
            {                
                ajax = new CAjax('json');
                ajax.cls = this.cls;
                ajax.divFb = this.divFb;
                ajax.onload = function()
                {
                    this.cls.socialFb(this.divFb);
                };
                ajax.exec("/controller/User/socFbDisconnect");
                
                this.divFb.innerHTML = "<div class='loading'></div>";
            }
        }
    };
    ajax.exec("/controller/User/socFbGetUserId");
}

/**
 * Wait to see if the user has approved
 */
Plugin_Settings_Profile.prototype.socialBeginFbStatusCheck = function(dv)
{
    // Now save email info
    // -------------------------------------------    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.cbData.dv = dv;
    ajax.onload = function(ret)
    {
        if (ret == "-1")
        {
			var me = this;
            this.cls.socialBeginFbTimer = setTimeout(function() { me.cls.socialBeginFbStatusCheck(me.cbData.dv); }, 5000); // Evert 5 seconds
        }
        else
        {
            this.cls.socialFb(this.cbData.dv);
        }
    };
    ajax.exec("/controller/User/socFbGetUserId");
}
