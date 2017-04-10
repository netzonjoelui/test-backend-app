function Plugin_Settings_Teams()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.teamForm = new Object();
    this.teamData = new Object();
    this.teamLeftData = new Array();
    this.teamRightData = new Array();    
}

Plugin_Settings_Teams.prototype.print = function(antView)
{    
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "User Teams";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    var userObject = new CAntObject("user")
    userObject.cls = this;
    userObject.onteamsloaded = function(ret)
    {
        this.cls.teamData.teams = ret;
        this.cls.buildInterface();
    }
    userObject.loadTeam();
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

Plugin_Settings_Teams.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";    
    
    var parentId = 0;
    var divLeft = alib.dom.createElement("div", this.innerCon);
    var divRight = alib.dom.createElement("div", this.innerCon);    
    
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "600px");
    alib.dom.styleSet(divRight, "float", "left");    
    divClear(this.innerCon);
    for(team in this.teamData.teams)
    {
        var currentTeam = this.teamData.teams[team];
        var teamId = currentTeam.id;        
        var teamDiv = null;
        
        if(!currentTeam.name)
            continue;
        
        if(currentTeam.parentId > 0)
        {
            parentId = currentTeam.parentId;
            divLeft = this.teamLeftData[parentId].div;
            divRight = this.teamRightData[parentId].div;
        }
        
        if(!this.teamLeftData[parentId])
        {
            // create parent team left div
            this.teamLeftData[parentId] = new Object();            
            this.teamLeftData[parentId].div = alib.dom.createElement("div", divLeft);
            
            // create parent team right div
            this.teamRightData[parentId] = new Object();
            this.teamRightData[parentId].div = alib.dom.createElement("div", divRight);
        }
        
        // create team data div
        teamLeftDiv = alib.dom.createElement("div", this.teamLeftData[parentId].div);        
        teamRightDiv = alib.dom.createElement("div", this.teamRightData[parentId].div);        
        
        // set arrow image if parent has a child
        if(parentId > 0)
        {
            var parentArrow = this.teamLeftData[parentId].arrow;
            parentArrow.innerHTML = "";
            var arrowImg = alib.dom.createElement("img", parentArrow);
            arrowImg.setAttribute("src", "/images/icons/tri.png");            
        }        
        
        if(!this.teamLeftData[teamId])
        {
            this.teamLeftData[teamId] = new Object();
            this.teamRightData[teamId] = new Object();
            
            // team arrow icon div
            var arrowIcon = this.teamLeftData[teamId].arrow = alib.dom.createElement("div", teamLeftDiv);            
            alib.dom.styleSet(arrowIcon, "float", "left");            
            alib.dom.styleSet(arrowIcon, "width", "12px");
            arrowIcon.innerHTML = "&nbsp;";
        }
        
        // display team data
        var teamName = alib.dom.createElement("div", teamLeftDiv);
        alib.dom.styleSet(teamName, "float", "left");        
        teamName.innerHTML = currentTeam.name;        
                
        // sub link
        var subLinkDiv = alib.dom.createElement("div", teamRightDiv);        
        alib.dom.styleSet(subLinkDiv, "float", "left");
        alib.dom.styleSet(subLinkDiv, "marginLeft", "25px");
        var subLink = alib.dom.createElement("a", subLinkDiv);
        subLink.innerHTML = "[add sub-team]";
        subLink.href = "javascript: void(0);";        
        subLink.cls = this;
        subLink.teamId = teamId;
        subLink.onclick = function()
        {
            this.cls.teamModal("New Team", null, this.teamId);
        }
        
        // edit link        
        var editLinkDiv = alib.dom.createElement("div", teamRightDiv);        
        alib.dom.styleSet(editLinkDiv, "float", "left");
        alib.dom.styleSet(editLinkDiv, "marginLeft", "25px");
        var editLink = alib.dom.createElement("a", editLinkDiv);
        editLink.innerHTML = "[edit]";
        editLink.href = "javascript: void(0);";        
        editLink.cls = this;
        editLink.teamId = teamId;
        editLink.parentId = parentId;
        editLink.teamName = currentTeam.name;
        editLink.onclick = function()
        {            
            this.cls.teamModal(this.teamName, this.teamId, this.parentId);
        }
        
        // delete link
        var deleteLinkDiv = alib.dom.createElement("div", teamRightDiv);        
        alib.dom.styleSet(deleteLinkDiv, "float", "left");
        alib.dom.styleSet(deleteLinkDiv, "marginLeft", "25px");
        if(parentId > 0)
        {
            var deleteLink = alib.dom.createElement("a", deleteLinkDiv);
            deleteLink.innerHTML = "[delete]";
            deleteLink.href = "javascript: void(0);";        
            deleteLink.cls = this;
            deleteLink.teamId = teamId;            
            deleteLink.onclick = function()
            {                
                if(confirm("Are you sure to delete this team?"))
                    this.cls.teamDelete(this.teamId);
            }
        }
        
        divClear(teamRightDiv);
        divClear(teamLeftDiv);
        
        // create sub team left div
        var subteamLeftDiv = this.teamLeftData[teamId].div = alib.dom.createElement("div", teamLeftDiv);        
        alib.dom.styleSet(subteamLeftDiv, "marginLeft", "15px");
        alib.dom.styleSet(subteamLeftDiv, "marginTop", "5px");
        
        // create sub team right div
        var subteamRightDiv = this.teamRightData[teamId].div = alib.dom.createElement("div", teamRightDiv);        
        alib.dom.styleSet(subteamRightDiv, "marginTop", "5px");        
    }
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

/*************************************************************************
*    Function:    teamDelete
*
*    Purpose:    team delete
**************************************************************************/
Plugin_Settings_Teams.prototype.teamDelete = function(tid)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.tid = tid;
    ajax.dlg = showDialog("Deleting team, please wait...");
    ajax.onload = function(ret)
    {           
        // reset the team data
        this.cls.teamLeftData = new Object();        
        this.cls.teamRightData = new Object();        
        this.cls.teamData = new Object();
        
        // rebuild interface
        this.cls.mainCon.innerHTML = "";
        this.cls.print(this.cls.mainCon);
        this.dlg.hide();
        ALib.statusShowAlert("Team Deleted!", 3000, "bottom", "right");
    };
    ajax.exec("/controller/User/deleteTeam",
                [["tid", tid]]);
}

/*************************************************************************
*    Function:    teamModal
*
*    Purpose:    Create a new team
**************************************************************************/
Plugin_Settings_Teams.prototype.teamModal = function(teamName, tid, parentId)
{
    var labelModal = "New Team";
    if(tid > 0)
        labelModal = "Edit Team";
        
    var dlg = new CDialog(labelModal);
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
        
    this.teamForm.teamName = createInputAttribute(alib.dom.createElement("input"), "text", "teamName", "Team Name", "300px", teamName);
    buildFormInput(this.teamForm, tBody);
    
    // Parent Label
    var divParent = alib.dom.createElement("div", divModal);        
    alib.dom.styleSet(divParent, "fontWeight", "bold");
    divParent.innerHTML = "Parent Team: ";
    
    var divParentName = alib.dom.createElement("label", divParent);
    divParentName.innerHTML = "None";
    alib.dom.styleSet(divParentName, "fontWeight", "normal");
    
    if(parentId > 0)
    {
        if(this.teamData.teams[parentId])
            divParentName.innerHTML = this.teamData.teams[parentId].name;
        else
            divParentName.innerHTML = this.teamData.teams[0].name;
    }
    
    
    if(tid > 0)
    {
        this.tblUser = new CToolTable("100%");
        var divUsers = alib.dom.createElement("div", divModal);
        divUsers.innerHTML = "<div class='loading'></div>";
        
        ajax = new CAjax('json');
        ajax.cls = this;
        ajax.tid = tid;
        ajax.divUsers = divUsers;
        ajax.onload = function(ret)
        {
            divUsers.innerHTML = "";
            this.cls.tblUser.print(divUsers);
            
            this.cls.teamData.users = ret;
            this.cls.buildUserRow(this.tid);
        };
        ajax.exec("/controller/User/getUserTeams", 
                    [["tid", tid]]);
    }
    
    // Done button
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Save and Close", 
                        function(dlg, cls, teamName, tid, parentId)
                        {
                            cls.teamSave(teamName, tid, parentId);
                            dlg.hide();
                        }, 
                        [dlg, this, this.teamForm.teamName, tid, parentId], "b1");
    btn.print(divButton);
    
    var btn = new CButton("Cancel", 
                        function(dlg) 
                        {  
                            dlg.hide(); 
                        }, 
                        [dlg], "b1");
    btn.print(divButton);

    dlg.customDialog(divModal, 450);
}

/*************************************************************************
*    Function:    buildUserRow
* 
*    Purpose:    Build team Row
**************************************************************************/
Plugin_Settings_Teams.prototype.buildUserRow = function(tid)
{
    if(!this.teamData.users)
        return;
        
    // clear the current account table rows
    this.tblUser.clear();
    
    for(user in this.teamData.users)
    {            
        var currentUser = this.teamData.users[user];
        
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
        deleteLink.tid = tid;
        deleteLink.rw = rw;
        deleteLink.onclick = function()
        {
            if(confirm("Are you sure to remove " + this.name + " in this team?"))
                this.cls.userDelete(this.rw, this.tid, this.id);
        }
        rw.addCell(deleteLink, null, "center");
    }
}

/*************************************************************************
*    Function:    teamSave
*
*    Purpose:    Save team
**************************************************************************/
Plugin_Settings_Teams.prototype.teamSave = function(teamName, tid, parentId)
{    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = showDialog("Saving team, please wait...");
    ajax.onload = function()
    {
        this.dlg.hide();
        ALib.statusShowAlert("Team Saved!", 3000, "bottom", "right");
        this.cls.teamLeftData = new Object();        
        this.cls.teamRightData = new Object();        
        this.cls.teamData = new Object();
        
        this.cls.mainCon.innerHTML = "";
        this.cls.print(this.cls.mainCon);
    };
    ajax.exec("/controller/User/teamAdd",
                [["name", teamName.value], ["tid", tid], ['parent_id', parentId]]);
}

/*************************************************************************
*    Function:    userDelete
*
*    Purpose:    delete user
**************************************************************************/
Plugin_Settings_Teams.prototype.userDelete = function(rw, gid, userId)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.rw = rw;
    ajax.dlg = showDialog("Deleting user in this team, please wait...");
    ajax.onload = function(ret)
    {
        this.dlg.hide();
        ALib.statusShowAlert("User Removed!", 3000, "bottom", "right");
        this.rw.deleteRow();
    };
    ajax.exec("/controller/User/deleteUserTeam",
                [["gid", gid], ["userId", userId]]);
}
