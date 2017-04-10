/**
* @fileoverview This sub-loader will load user window
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of AntObjectLoader_User.
*
* @constructor
* @param {CAntObject} obj Handle to object that is being viewed or edited
* @param {AntObjectLoader} loader Handle to base loader class
*/
/**
 * Creates an instance of AntObjectLoader_User.
 *
 * @constructor
 * @param {CAntObject} obj Handle to object that is being viewed or edited
 * @param {AntObjectLoader} loader Handle to base loader class
 */
function AntObjectLoader_User(obj, loader)
{
    this.mainObject = obj;
    this.userId = this.mainObject.id;    
    this.loaderCls = loader;
    this.outerCon = null; // Outer container
    this.mainConMin = null; // Minified div for collapsed view
    this.mainCon = null; // Inside outcon and holds the outer table
    this.formCon = null; // inner container where form will be printed
    this.ctbl = null; // Content table used for frame when printed inline
    this.toolbar = null;
    this.toolbarCon = null;
    this.bodyCon = null;
    this.bodyFormCon = null; // Displays the form
    this.bodyNoticeCon = null; // Right above the form and used for notices and inline duplicate detection
    this.plugins = new Array();
    this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
    this.fEnableClose = true; // Set to false to disable "close" and "save and close"
    
    this.userForm = new Object();
    this.userData = new Object();    
}

/**
 * Refresh the form
 */
AntObjectLoader_User.prototype.refresh = function()
{
    this.toggleEdit(this.editMode);
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_User.prototype.toggleEdit = function(setmode)
{
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_User.prototype.print = function(con, plugins)
{
    this.outerCon = con;
    this.mainCon = alib.dom.createElement("div", con);
    this.formCon = this.mainCon;

    this.toolbarCon = alib.dom.createElement("div", this.formCon);
    
    var outer_dv = alib.dom.createElement("div", this.formCon);
    
    this.bodyCon = alib.dom.createElement("div", outer_dv);    
    alib.dom.styleSet(this.bodyCon, "margin-top", "5px");
    
    // Notice container
    this.bodyNoticeCon = alib.dom.createElement("div", this.bodyCon);
    
    this.bodyFormCon = alib.dom.setElementAttr(alib.dom.createElement("div", this.bodyCon), [["id", "bodyFormCon"]]);
    alib.dom.styleSet(this.bodyFormCon, "overflow", "auto");    
    this.bodyFormCon.innerHTML = "<div class='loading'></div>";
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.userId = this.userId;
    ajax.onload = function(ret)
    {
        var userId = 0;
        if(this.cls.userId)
            userId = this.cls.userId;
            
        this.cls.userData = ret["user" + userId];
        this.cls.buildInterface();        
    };
    ajax.exec("/controller/User/getUsers", 
                [['uid', this.userId], ['userId', this.userId], ['det', 'full'], ['view_active', true]]);
    
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_User.prototype.onValueChange = function(name, value, valueName)
{    
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_User.prototype.onNameChange = function(name)
{
}

/**
 * Callback is fired any time a value changes for the mainObject 
 *
 * @this {AntObjectLoader_EmailMessage}
 * @private
 */
AntObjectLoader_User.prototype.buildInterface = function()
{
    var tb = new CToolbar();    
    
    // close button
    //save and close button
    var btn = new CButton("Close", 
    function(cls)
    {
        cls.close();
    },
    [this.loaderCls], "b1");
    if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
        tb.AddItem(btn.getButton(), "left");
    
    //save and close button
    var btn = new CButton("Save and Close", 
    function(cls)
    {
        cls.saveSettings(true);
    },
    [this], "b1");
    if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
        tb.AddItem(btn.getButton(), "left");
    
    // save changes button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        cls.saveSettings(false);
    },
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    // delete button
    var btn = new CButton("Delete",
    function(cls)
    {
        if(confirm("Are you sure to delete this user?"))
            cls.deleteUser();
    },
    [this], "b1");
    
    if(this.userId > 0)
        tb.AddItem(btn.getButton(), "left");
    
    
    tb.print(this.toolbarCon);
    
    this.bodyFormCon.innerHTML = "";    
    this.userDetails();
    this.passwordSettings();
    this.userImage();
    this.adminFlags();
    this.phoneSettings();
    //this.emailSettings();
    this.displayGroups();
    
    this.loadDropdownData();
}

/*************************************************************************
*    Function:    loadDropdownData
*
*    Purpose:    Load Dropdown Data
**************************************************************************/
AntObjectLoader_User.prototype.loadDropdownData = function()
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
            if(currentTheme.id == this.cls.userData.details.themeId)
                selected = true;
            
            this.cls.userForm.details.theme[this.cls.userForm.details.theme.length] = new Option(currentTheme.name, currentTheme.id, false, selected);
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
            if(currentTimezone == this.cls.userData.details.timezone)
                selected = true;
            
            this.cls.userForm.details.timezone[this.cls.userForm.details.timezone.length] = new Option(currentTimezone, currentTimezone, false, selected);
        }
    };
    ajax.exec("/controller/User/getTimezones");
    
    // team
    var userObject = new CAntObject("user")
    userObject.teamId = this.userData.details.teamId;
    userObject.teamDropdown = this.userForm.details.team;
    userObject.onteamsloaded = function(ret)
    {
        var teamData = ret;        
        delete ret['teamCount'];
        this.populateTeam(ret, ret[0].parentId);
        this.addSpacedPrefix(teamData);
    }
    userObject.loadTeam();
    
    // carrier    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        for(carrier in ret)
        {
            var currentCarrier = ret[carrier];
            
            var selected = false;
            if(currentCarrier.id == this.cls.userData.phone.phoneCarrier)
                selected = true;
            
            this.cls.userForm.contact.carrier[this.cls.userForm.contact.carrier.length] = new Option(currentCarrier.name, currentCarrier.id, false, selected);
        }
    };
    ajax.exec("/controller/User/getCarriers");
}


    
/*************************************************************************
*    Function:    userDetails
*
*    Purpose:    Creates the user details
**************************************************************************/
AntObjectLoader_User.prototype.userDetails = function()
{
    var divDetails = alib.dom.createElement("div", this.bodyFormCon);
    
    var divLeft = alib.dom.createElement("div", divDetails);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "User Details";
    
    var divRight = alib.dom.createElement("div", divDetails);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.userForm.details = new Object();
    
    this.userForm.details.userName = createInputAttribute(alib.dom.createElement("input"), "text", "userName", "User Name", "300px", this.userData.details.name);
    
    this.userForm.details.fullName = createInputAttribute(alib.dom.createElement("input"), "text", "fullName", "Full Name", "300px", this.userData.details.fullName);
    
    this.userForm.details.title = createInputAttribute(alib.dom.createElement("input"), "text", "title", "Title", "300px", this.userData.details.title);
    
    this.userForm.details.theme = createInputAttribute(alib.dom.createElement("select", divRight), null, "themeId", "Visual Theme", null, this.userData.details.themeId);
    this.userForm.details.theme[this.userForm.details.theme.length] = new Option("Select Theme", "");
    
    this.userForm.details.timezone = createInputAttribute(alib.dom.createElement("select", divRight), null, "timezone", "Default Timezone", null, this.userData.details.timezone);
    this.userForm.details.timezone[this.userForm.details.timezone.length] = new Option("Select Timezone", "");
    
    this.userForm.details.team = createInputAttribute(alib.dom.createElement("select", divRight), null, "teamId", "Team", null, this.userData.details.teamId);
    this.userForm.details.team[this.userForm.details.team.length] = new Option("Select Team", "");
    
    this.userForm.details.managerId = createInputAttribute(alib.dom.createElement("input"), "hidden", "managerId", null, null, this.userData.details.managerId);
    this.userForm.details.manager = createInputAttribute(alib.dom.createElement("input"), "button", "btnManager", "Manager", null, "Select Manager");    
    
    var managerName = "None Selected";
    
    if(this.userData.details.managerName)
    {
        managerName = this.userData.details.managerName;
    }    
    this.userForm.details.manager.inputLabel = " " + managerName;
    
    // build user details
    buildFormInput(this.userForm.details, tBody);
    
    // set manager label style
    var managerLabel = this.userForm.details.manager.parentNode.lastChild;
    alib.dom.styleSet(managerLabel, "fontWeight", "bold");
    alib.dom.styleSet(managerLabel, "fontSize", "12px");
    alib.dom.styleSet(managerLabel, "margin", "0 5px");
    
    // add remove manager
    var managerRemove = alib.dom.createElement("a");
    managerRemove.href = "javascript: void(0);";
    managerRemove.cls = this;
    managerRemove.managerLabel = managerLabel;
    managerRemove.onclick = function()
    {            
        this.cls.userForm.details.managerId.value = null;
        this.managerLabel.innerHTML = "None Selected";
        this.parentNode.removeChild(this);
    }
    managerRemove.innerHTML = "remove";
    
    // check if manager name is set
    if(this.userData.details.managerName)
        this.userForm.details.manager.parentNode.appendChild(managerRemove);
    
    // add select manager button feature
    this.userForm.details.manager.managerId = this.userForm.details.managerId;
    this.userForm.details.manager.managerRemove = managerRemove;
    this.userForm.details.manager.onclick = function()
    {
        var cbrowser = new CUserBrowser();
        cbrowser.cls = this;
        cbrowser.managerId = this.managerId;
        cbrowser.managerRemove = this.managerRemove;
        cbrowser.onSelect = function(cid, name) 
        {
            this.managerId.value = cid;
            this.cls.parentNode.childNodes[1].innerHTML = " " + name;
            this.cls.parentNode.appendChild(this.managerRemove);
        }
        cbrowser.showDialog();
    }
    
    divClear(divDetails);
}

/*************************************************************************
*    Function:    passwordSettings
*
*    Purpose:    Creates the Password Settings
**************************************************************************/
AntObjectLoader_User.prototype.passwordSettings = function()
{
    var divPassword = alib.dom.createElement("div", this.bodyFormCon);
    alib.dom.styleSet(divPassword, "borderTop", "1px solid");
    alib.dom.styleSet(divPassword, "paddingTop", "10px");
    
    var divLeft = alib.dom.createElement("div", divPassword);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    if(this.userId)
        lblTitle.innerHTML = "Reset Password";
    else
        lblTitle.innerHTML = "User Password";    
    
    var divRight = alib.dom.createElement("div", divPassword);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.userForm.password = new Object();
    
    if(this.userId)
    {
        this.userForm.password.newP = createInputAttribute(alib.dom.createElement("input"), "password", "newPassword", "New Password", "200px");
        this.userForm.password.newP.inputLabel = " Enter the new password here";
    }        
    else
    {
        this.userForm.password.newP = createInputAttribute(alib.dom.createElement("input"), "password", "newPassword", "User Password", "200px");
        this.userForm.password.newP.inputLabel = " Enter the user password here";
    }
    
    this.userForm.password.verifyP = createInputAttribute(alib.dom.createElement("input"), "password", "verifyPassword", "Verify Password", "200px");
    
    buildFormInput(this.userForm.password, tBody);
    
    divClear(divPassword);
}

/*************************************************************************
*    Function:    userImage
*
*    Purpose:    Creates Picture Widget Image
**************************************************************************/
AntObjectLoader_User.prototype.userImage = function()
{
    var divPicture = alib.dom.createElement("div", this.bodyFormCon);
    alib.dom.styleSet(divPicture, "borderTop", "1px solid");
    alib.dom.styleSet(divPicture, "paddingTop", "10px");    
    
    var divLeft = alib.dom.createElement("div", divPicture);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "User Image";
    
    var divCenter = alib.dom.createElement("div", divPicture);        
    alib.dom.styleSet(divCenter, "float", "left");
    alib.dom.styleSet(divCenter, "width", "140px");
    alib.dom.styleSet(divCenter, "overflow", "hidden");    
    alib.dom.styleSet(divCenter, "fontWeight", "bold");
    
    var pictureImage = null;
    var imageId = this.userData.details.imageId;
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
    
    this.userForm.picture = new Object();
    
    // Header Image Image - Hidden Input
    this.userForm.picture.hdnImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "imageId", null, null, imageId);
    
    // Picture Image Image - Button
    this.userForm.picture.button = createInputAttribute(alib.dom.createElement("input"), "button", "btnPicture", null, "120px", "Select Image");
    this.userForm.picture.button.inputLabel = " Select an image to use as your user image";
    
    this.userForm.picture.button.cls = this;
    this.userForm.picture.button.pictureImg = pictureImg;
    this.userForm.picture.button.onclick = function()
    {
        var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";        
        cbrowser.cbData.cls = this.cls;
        cbrowser.cbData.pictureImg = this.pictureImg;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.pictureImg.setAttribute("src", "/files/images/"+id+"/140");
            this.cbData.cls.userForm.picture.hdnImageId.value = id;
        }
        cbrowser.showDialog();
    }
    
    // Default Image Image - Button
    this.userForm.picture.remove = createInputAttribute(alib.dom.createElement("input"), "button", "btnDefault", null, "120px", "Remove Image");
    this.userForm.picture.remove.inputLabel = " Do not use this image for my user image";
    
    this.userForm.picture.remove.cls = this;        
    this.userForm.picture.remove.pictureImg = pictureImg;
    this.userForm.picture.remove.onclick = function()
    {
        this.pictureImg.setAttribute("src", "");
        this.cls.userForm.picture.hdnImageId.value = "";
    }
    
    buildFormInput(this.userForm.picture, tBody);
    
    divClear(divPicture);
}

 /*************************************************************************
*    Function:    adminFlags
*
*    Purpose:    Creates admin flags checkboxes
**************************************************************************/
AntObjectLoader_User.prototype.adminFlags = function()
{
    var divFlags = alib.dom.createElement("div", this.bodyFormCon);
    alib.dom.styleSet(divFlags, "borderTop", "1px solid");
    alib.dom.styleSet(divFlags, "paddingTop", "10px");    
    
    var divLeft = alib.dom.createElement("div", divFlags);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Admin Flags";
    
    var divRight = alib.dom.createElement("div", divFlags);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.userForm.flags = new Object();
    
    this.userForm.flags.active = createInputAttribute(alib.dom.createElement("input"), "checkbox", "active", null, null);
    
    this.userForm.flags.active.checked = true;
    if(this.userData.details.active=='f')
        this.userForm.flags.active.checked = false;
    this.userForm.flags.active.inputLabel = " Active";
    
    this.userForm.flags.wizard = createInputAttribute(alib.dom.createElement("input"), "checkbox", "wizard", null, null);
    
    if(this.userData.wizard.fForcewizard=='t')
        this.userForm.flags.wizard.checked = true;
    this.userForm.flags.wizard.inputLabel = " Force User Wizard";
    
    buildFormInput(this.userForm.flags, tBody);
    
    divClear(divFlags);
}

/*************************************************************************
*    Function:    phoneSettings
*
*    Purpose:    Creates the Phone Settings
**************************************************************************/
AntObjectLoader_User.prototype.phoneSettings = function()
{
    var divPhone = alib.dom.createElement("div", this.bodyFormCon);    
    alib.dom.styleSet(divPhone, "borderTop", "1px solid");
    alib.dom.styleSet(divPhone, "paddingTop", "10px");
    
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
    
    this.userForm.contact = new Object();
    
    this.userForm.contact.mobilePhone = createInputAttribute(alib.dom.createElement("input"), "text", "mobilePhone", "Mobile Phone", "155px", this.userData.phone.mobilePhone, "95px");
    
    this.userForm.contact.officePhone = createInputAttribute(alib.dom.createElement("input"), "text", "officePhone", "Office Phone", "155px", this.userData.phone.officePhone, "95px");
    this.userForm.contact.officePhone.inputLabel = " Enter your direct line number here";
    
    this.userForm.contact.officeExt = createInputAttribute(alib.dom.createElement("input"), "text", "officeExt", "Office Ext", "155px", this.userData.phone.officeExt, "95px");
    this.userForm.contact.officeExt .inputLabel = " If applicable, enter your extension number here";
    
    this.userForm.contact.emailAddress = createInputAttribute(alib.dom.createElement("input"), "text", "email", "Email Address", "300px", this.userData.details.email, "95px");
    
    buildFormInput(this.userForm.contact, tBody);
    
    var mobilePhoneCon = this.userForm.contact.mobilePhone.parentNode;
    var carrierLabel = createInputAttribute(alib.dom.createElement("label", mobilePhoneCon));
    carrierLabel.innerHTML = " Carrier";
    
    this.userForm.contact.carrier = createInputAttribute(alib.dom.createElement("select", mobilePhoneCon), null, "phoneCarrier");
    
    divClear(divPhone);
}

/*************************************************************************
*    Function:    emailSettings
*
*    Purpose:    Creates the Email Settings
**************************************************************************/
AntObjectLoader_User.prototype.emailSettings = function()
{
    var divEmail = alib.dom.createElement("div", this.bodyFormCon);    
    alib.dom.styleSet(divEmail, "borderTop", "1px solid");
    alib.dom.styleSet(divEmail, "paddingTop", "10px");
    
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
    
    this.userForm.email = new Object();
    
    this.userForm.email.displayName = createInputAttribute(alib.dom.createElement("input"), "text", "displayName", "Display Name", "160px", this.userData.email.displayName, "95px");
    this.userForm.email.displayName.inputLabel = " Your full name to be displayed when email is sent";
    
    this.userForm.email.emailAddress = createInputAttribute(alib.dom.createElement("input"), "text", "emailAddress", "Email Address", "160px", this.userData.email.emailAddress, "95px");
    this.userForm.email.emailAddress .inputLabel = " This is where all email correspondence will be sent";
    
    this.userForm.email.replyTo = createInputAttribute(alib.dom.createElement("input"), "text", "replyTo", "Reply To", "160px", this.userData.email.replyTo, "95px");
    this.userForm.email.replyTo.inputLabel = " Enter only if you have different email from email address above";
    
    buildFormInput(this.userForm.email, tBody);
    
    divClear(divEmail);
}

/*************************************************************************
*    Function:    displayGroups
*
*    Purpose:    Creates the groups display
**************************************************************************/
AntObjectLoader_User.prototype.displayGroups = function()
{
    var divGroups = alib.dom.createElement("div", this.bodyFormCon);
    
    alib.dom.styleSet(divGroups, "borderTop", "1px solid");
    alib.dom.styleSet(divGroups, "paddingTop", "10px");
    
    var divLeft = alib.dom.createElement("div", divGroups);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Groups";
    
    var divRight = alib.dom.createElement("div", divGroups);
    alib.dom.styleSet(divRight, "float", "left");
    
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.userForm.groups = new Object();
    
    divRight.innerHTML = "<div class='loading'></div>";
    divClear(divGroups);
    
    ajax = new CAjax('json');
    ajax.cls = this;        
    ajax.divRight = divRight;
    ajax.onload = function(ret)
    {
        this.divRight.innerHTML = "";        
        this.cls.buildGroupRow(ret, this.divRight);
    }
    ajax.exec("/controller/User/getGroups");
}

/*************************************************************************
*    Function:    buildGroupRow
* 
*    Purpose:    Build Group Row
**************************************************************************/
AntObjectLoader_User.prototype.buildGroupRow = function(groupsData, con)
{   
    if(!groupsData)
        return;
        
    // clear the current account table rows    
    // print CToolTable
    var groupTbl = new CToolTable("100%");    
    groupTbl.addHeader("Name", "left", "300px");
    groupTbl.addHeader("Member", "center", "50px");
    
    groupTbl.print(con);
    
    this.userForm.groups = new Object();
    
    var gCount = 0;
    for(group in groupsData)
    {
        gCount+=5;
        var currentGroup = groupsData[group];
        
        var rw = groupTbl.addRow();
        rw.addCell(currentGroup.name);
        
        this.userForm.groups[currentGroup.id] = createInputAttribute(alib.dom.createElement("input"), "checkbox", "group_" + currentGroup.id, null, null, currentGroup.id);        
            
        switch(currentGroup.name)
        {
            case 'Users':
            case 'Everyone':
            //case 'Creator Owner':
                this.userForm.groups[currentGroup.id].checked = true;
                this.userForm.groups[currentGroup.id].onclick = function(e)
                {
                    e.preventDefault();
                }
                break;
            default:
                if(this.userData.groups[currentGroup.id])
                    this.userForm.groups[currentGroup.id].checked = true;
                break;
        }
            
        rw.addCell(this.userForm.groups[currentGroup.id], null, "center");
    }
    
    // Body container
    var headerHeights = getHeaderHeights();    
    var height = (getWorkspaceHeight()- headerHeights.totalHeaderHeight) - gCount;
    alib.dom.styleSet(this.bodyFormCon, "height", height + "px");
}

/*************************************************************************
*    Function:    saveSettings
*
*    Purpose:    Saves the Settings
**************************************************************************/
AntObjectLoader_User.prototype.saveSettings = function(saveClose)
{
    var userNameLabel = this.userForm.details.userName.parentNode.parentNode.firstChild;
    alib.dom.styleSet(userNameLabel, "color", "#000000");
        
    var newPLabel = this.userForm.password.newP.parentNode.parentNode.firstChild;
    var verifyPLabel = this.userForm.password.verifyP.parentNode.parentNode.firstChild;
    
    alib.dom.styleSet(newPLabel, "color", "#000000");
    alib.dom.styleSet(verifyPLabel, "color", "#000000");
    
    var errorValue = false;
    var args = new Array();
    
    if (this.userId)
        args[args.length] = ["uid", this.userId];
        
    for(var setting in this.userForm)
    {
        var currentSetting = this.userForm[setting];
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
                    if(currentSubSetting.checked)
                        value = 't';
                    else
                        value = 'f';
                    break;
                case "select-one":
                case "text":
                default:
                    value = currentSubSetting.value.replace(/^\s+|\s+$/g, ""); 
                    break;
            }
            
            switch(currentSubSetting.id)
            {
                case "userName":                    
                    if(value.length==0)
                    {
                        alib.dom.styleSet(userNameLabel, "color", "red");
                        errorValue = true;                        
                    }                        
                    break;
                case 'newPassword':
                case 'verifyPassword':                    
                    if(!this.userId)
                    {
                        if(value.length == 0 || this.userForm.password.newP.value !== this.userForm.password.verifyP.value)
                        {
                            alib.dom.styleSet(newPLabel, "color", "red");
                            alib.dom.styleSet(verifyPLabel, "color", "red");
                            errorValue = true;
                        }
                    }
                break;
            }
            
            args[args.length] = [currentSubSetting.id, value];
        }
    }
    
    // check if theres an error
    if(errorValue)
    {
        ALib.statusShowAlert("Invalid input values!", 3000, "bottom", "right");
        return;
    }
    
    // save profile    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.args = args;    
    ajax.newPLabel = newPLabel;
    ajax.verifyPLabel = verifyPLabel;
    ajax.saveClose = saveClose;
    ajax.dlg = showDialog("Saving user details, please wait...");
    ajax.onload = function(ret)
    {
        if(ret.error)
        {
            ALib.statusShowAlert(ret.error, 5000, "bottom", "right");
            if(ret.errorId > 0)
            {
                alib.dom.styleSet(this.newPLabel, "color", "red");
                alib.dom.styleSet(this.verifyPLabel, "color", "red");
            }
        }
        else
        {
            this.cls.userId = ret;
            args[args.length] = ["uid", ret];
            //this.cls.saveEmail(this.args, this.saveClose, this.dlg);
        }
        
        this.dlg.hide();
    }
    ajax.exec("/controller/User/saveUser", args);
}

/*************************************************************************
*    Function:    saveEmail
*
*    Purpose:    Saves the Email Settings
**************************************************************************/
AntObjectLoader_User.prototype.saveEmail = function(args, saveClose, dlg)
{    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.saveClose = saveClose;
    ajax.loaderCls = this.loaderCls;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        ALib.statusShowAlert("Profile Settings Saved!", 3000, "bottom", "right");
        this.dlg.hide();
        if(this.saveClose)
            this.loaderCls.close();
    }
    ajax.exec("/controller/Email/saveDefaultEmail", args);
}

/*************************************************************************
*    Function:    deleteUser
*
*    Purpose:    Deletes the user
**************************************************************************/
AntObjectLoader_User.prototype.deleteUser = function()
{    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.dlg = showDialog("Deleting user, please wait...");
    ajax.loaderCls = this.loaderCls;    
    ajax.onload = function(ret)
    {
        ALib.statusShowAlert("User has been deleted!", 3000, "bottom", "right");
        this.dlg.hide();
        this.loaderCls.close();
    };
    var args = [["uid", this.userId]];
    ajax.exec("/controller/User/userDelete", args);
}
