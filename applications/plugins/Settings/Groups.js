function Plugin_Settings_Groups()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.groupData = new Object();
    this.groupForm = new Object();    
}

Plugin_Settings_Groups.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "User Groups / Roles";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {           
        this.cls.groupData.groups = ret;            
        this.cls.buildInterface();
        
    };    
    ajax.exec("/controller/User/getGroups");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

Plugin_Settings_Groups.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    
    var toolbar = alib.dom.createElement("div", this.innerCon);
    var tb = new CToolbar();
    
    // New Group Button
    var btn = new CButton("New Group", 
    function(cls)
    {
        cls.groupModal(true, "New User Group", 0);
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    var divtblGroup = alib.dom.createElement("div", this.innerCon);
    
    // print CToolTable
    this.tblGroup = new CToolTable("100%");
    this.tblGroup.addHeader("ID", "center", "20px");
    this.tblGroup.addHeader("Name", "left", "300px");
    this.tblGroup.addHeader("&nbsp", "center", "50px");
    this.tblGroup.addHeader("&nbsp", "center", "50px");
    
    this.tblGroup.print(divtblGroup);
    this.buildGroupRow();
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

/*************************************************************************
*    Function:    buildGroupRow
* 
*    Purpose:    Build Group Row
**************************************************************************/
Plugin_Settings_Groups.prototype.buildGroupRow = function()
{
    if(!this.groupData.groups)
        return;
        
    // clear the current account table rows    
    this.tblGroup.clear();
    
    for(group in this.groupData.groups)
    {            
        var currentGroup = this.groupData.groups[group];
        
        var rw = this.tblGroup.addRow();
        
        var cellId = rw.addCell(currentGroup.id);
        alib.dom.styleSet(cellId, "paddingLeft", "15px");
        alib.dom.styleSet(cellId, "paddingRight", "0px");
        rw.addCell(currentGroup.name);
        
        
        // Edit Link
        var editLink = alib.dom.createElement("a");    
        editLink.innerHTML = "[edit]";
        editLink.href = "javascript: void(0);";        
        editLink.cls = this;
        editLink.groupName = currentGroup.name;
        editLink.id = currentGroup.id;
        editLink.onclick = function()
        {
            this.cls.groupModal(false, this.groupName, this.id);
        }
        rw.addCell(editLink, null, "center");
        
        // delete link column
        var deleteLink = "&nbsp;"; 
        if(currentGroup.id > 0)                
        {
            // Edit Link
            deleteLink = alib.dom.createElement("a");    
            deleteLink.innerHTML = "[delete]";
            deleteLink.href = "javascript: void(0);";        
            deleteLink.cls = this;            
            deleteLink.id = currentGroup.id;
            deleteLink.onclick = function()
            {                
                if(confirm("Are you sure to delete this group?"))
                    this.cls.groupDelete(this.id);
            }                
        }
        
        rw.addCell(deleteLink, null, "center");
    }
}

/*************************************************************************
*    Function:    buildUserRow
* 
*    Purpose:    Build User Row
**************************************************************************/
Plugin_Settings_Groups.prototype.buildUserRow = function(gid)
{
    if(!this.groupData.users)
        return;
        
    // clear the current account table rows
    this.tblUser.clear();
    
    for(user in this.groupData.users)
    {            
        var currentUser = this.groupData.users[user];
        
        var rw = this.tblUser.addRow();
                
        rw.addCell(currentUser.name);
        rw.addCell(currentUser.title);        
        
        // Edit Link
        var deleteLink = alib.dom.createElement("a");    
        deleteLink.innerHTML = "[remove]";
        deleteLink.href = "javascript: void(0);";        
        deleteLink.cls = this;        
        deleteLink.id = currentUser.id;
        deleteLink.name = currentUser.name;
        deleteLink.gid = gid;
        deleteLink.rw = rw;
        deleteLink.onclick = function()
        {
            if(confirm("Are you sure to removed " + this.name + " in this group?"))
                this.cls.userDelete(this.rw, this.gid, this.id);
        }
        rw.addCell(deleteLink, null, "center");
    }
}

/*************************************************************************
*    Function:    groupDelete
*
*    Purpose:    delete group
**************************************************************************/
Plugin_Settings_Groups.prototype.groupDelete = function(gid)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.gid = gid;
    ajax.dlg = showDialog("Deleting group, please wait...");
    ajax.onload = function(ret)
    {
        delete this.cls.groupData.groups[this.gid];
        this.cls.buildGroupRow();
        this.dlg.hide();
        ALib.statusShowAlert("Group Deleted!", 3000, "bottom", "right");
    };
    ajax.exec("/controller/User/groupDelete",
                [["gid", gid]]);
}

/*************************************************************************
*    Function:    userDelete
*
*    Purpose:    delete user
**************************************************************************/
Plugin_Settings_Groups.prototype.userDelete = function(rw, gid, userId)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.rw = rw;
    ajax.dlg = showDialog("Deleting user in this group, please wait...");
    ajax.onload = function(ret)
    {
        this.dlg.hide();
        ALib.statusShowAlert("User Removed!", 3000, "bottom", "right");
        this.rw.deleteRow();
    };
    ajax.exec("/controller/User/deleteUserGroup",
                [["gid", gid], ["userId", userId]]);
}

/*************************************************************************
*    Function:    groupModal
*
*    Purpose:    Create a new group
**************************************************************************/
Plugin_Settings_Groups.prototype.groupModal = function(newGroup, groupName, gid)
{
    var labelModal = "Edit Group";
    if(newGroup)
        labelModal = "New Group";
        
    var dlg = new CDialog(labelModal);
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    this.groupForm.groupName = createInputAttribute(alib.dom.createElement("input"), "text", "groupName", "Group Name", "300px", groupName);
    buildFormInput(this.groupForm, tBody);
    
    if(gid < 0)
        this.groupForm.groupName.setAttribute("disabled", "");
    else if(gid!=0)
    {
        this.tblUser = new CToolTable("100%");    
        var divUsers = alib.dom.createElement("div", divModal);
        divUsers.innerHTML = "<div class='loading'></div>";
        
        ajax = new CAjax('json');
        ajax.cls = this;
        ajax.gid = gid;
        ajax.onload = function(ret)
        {
            divUsers.innerHTML = "";
            this.cls.tblUser.print(divUsers);
            
            this.cls.groupData.users = ret;
            this.cls.buildUserRow(this.gid);
        };
        ajax.exec("/controller/User/getUserGroups", 
                    [["gid", gid]]);
    }
    
    // Done button
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Save and Close", 
                        function(dlg, cls, groupName, gid)
                        {
                            if(gid >= 0)
                            {
                                ajax = new CAjax('json');
                                ajax.cls = cls;
                                ajax.groupName = groupName.value;
                                ajax.dlg = showDialog("Saving group, please wait...");
                                ajax.onload = function(ret)
                                {
                                    if(ret > 0)
                                    {
                                        if(gid > 0)
                                            this.cls.groupData.groups[gid].name = this.groupName;
                                        else
                                        {
                                            this.cls.groupData.groups[ret] = new Object();
                                            this.cls.groupData.groups[ret].id = ret;
                                            this.cls.groupData.groups[ret].name = this.groupName;
                                        }
                                        
                                        this.cls.buildGroupRow();
                                        this.dlg.hide();
                                        ALib.statusShowAlert("Group Name Saved!", 3000, "bottom", "right");
                                    }                                    
                                };
                                ajax.exec("/controller/User/groupAdd", 
                                            [["name", groupName.value]]);
                            }
                            else
                                ALib.statusShowAlert("Group Name Saved!", 3000, "bottom", "right");
                            
                            dlg.hide();                            
                        }, 
                        [dlg, this, this.groupForm.groupName, gid], "b1");
    btn.print(divButton);
    
    var btn = new CButton("Cancel", 
                        function(dlg) 
                        {  
                            dlg.hide(); 
                        }, 
                        [dlg]);
    btn.print(divButton);

    dlg.customDialog(divModal, 450);
}
