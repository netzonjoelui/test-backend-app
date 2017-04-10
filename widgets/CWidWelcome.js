/****************************************************************************
*	
*	Class:		CWidWelcome
*
*	Purpose:	Main application for the data center
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidWelcome()
{    
	this.title = "Welcome";
	this.m_container = null;	// Set by calling process
	this.m_image = new Image;
    this.appNavname = null;
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidWelcome.prototype.main = function()
{    
	Ant.setHinst(this, "/widgets/welcome");

	// Create container to hold text
	// ------------------------------------------------------------------
	this.mdiv = ALib.m_document.createElement("div");
	this.m_container.appendChild(this.mdiv);
	this.mdiv.id = "wWelcomeMsgBoard";
	this.mdiv.style.height = "130px";
	this.mdiv.style.padding = '3px;'
	this.mdiv.align = "right";
	this.loadMessage(this.mdiv); // Load text	

	// Load image into this.m_container
	// ------------------------------------------------------------------
	this.loadImage();

	// Set context menu
	// ------------------------------------------------------------------
	var sub2 = this.m_dm.addSubmenu("Change Text Color");
	var colors = [["Black", "000000"], ["Aqua", "00FFFF"], ["Blue", "0000FF"], ["DarkSlate Blue", "483D8B"], 
				  ["Midnite Blue", "191970"], ["Fuchia", "FF00FF"], ["Yellow", "FFFF00"], ["Green", "008000"],
				  ["Army Green", "45463E"], ["Lime", "00FF00"], ["Maroon", "800000"], ["Navy", "000080"],
				  ["Olive", "808000"], ["Purple", "800080"], ["Mild Purple", "3A58BA"], ["Lite Purple", "666699"],
				  ["Dark Purple", "5B005B"], ["Silver", "C0C0C0"], ["Teal", "008080"], ["White", "FFFFFF"],
				  ["Gray", "808080"], ["Level 2 Grey", "333"], ["Level 3 Grey", "666"], ["Level 4 Grey", "999"]];

	funct = function(cls, clr) { cls.setTextColor(clr, true); };
	for (var i = 0; i < colors.length; i++)
	{
		sub2.addEntry(colors[i][0], funct, null, 
					  "<div style='width:9px;height:9px;background-color:#" + colors[i][1] + "'></div>", [this, colors[i][1]]);
	}

	var custImg = function(cls)
	{
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "jpg:jpeg:png:gif";
		cbrowser.cbData.m_cls = cls;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cbData.m_cls.changeImage(fid);
		}
		cbrowser.showDialog();
	}
	this.m_dm.addEntry('Use Default Background', function(cls) { cls.changeImage('default'); },
						"/images/themes/"+Ant.m_theme+"/icons/taskIcon.gif", null, [this]);
	this.m_dm.addEntry('Use Custom Background', custImg, 
						"/images/themes/"+Ant.m_theme+"/icons/taskIcon.gif", null, [this]);
	this.m_dm.addEntry('Remove Background (blank)', function(cls) { cls.changeImage('none'); },
						"/images/themes/"+Ant.m_theme+"/icons/taskIcon.gif", null, [this]);

}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidWelcome.prototype.exit= function()
{
	Ant.clearHinst("/widgets/welcome");

	this.m_container.innerHTML = "";
}

/*************************************************************************
*	Function:	loadMessage
*
*	Purpose:	Load welcome center text and the image once text is loaded
**************************************************************************/
CWidWelcome.prototype.loadMessage = function(mdiv)
{
	var style_left = "";
	var style_right = "";
	
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "font-weight", "bold");
	dv.innerHTML = "Greetings";
	mdiv.appendChild(dv);

	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "margin-bottom", "20px");
	dv.innerHTML = Ant.user.name;
	mdiv.appendChild(dv);

	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "font-weight", "bold");
	dv.innerHTML = "Getting Started";
	mdiv.appendChild(dv);

	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "margin-bottom", "5px");
	dv.innerHTML = "<img src='/images/icons/question_16.png' /> <a href='javascript:void(0);' onclick=\"loadSupportDoc(108);\" style='margin-bottom:5px;'> Watch Home Tutorial</a>";
	mdiv.appendChild(dv);

	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "margin-bottom", "5px");
	dv.innerHTML = "<img src='/images/icons/question_16.png' /> <a href='javascript:void(0);' onclick=\"loadSupportDoc(121);\" style='margin-bottom:5px;'> Watch ANT Overview</a>";
	mdiv.appendChild(dv);

	/*
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "font-weight", "bold");
	dv.innerHTML = "Storage Usage";
	mdiv.appendChild(dv);

	var quota = Ant.storage.user_quota;
	var used = Ant.storage.user_used;

	// Make quota human readable
	if (quota >= 1000000)
		var display_quota = quota/1000000 + "TB";
	else if (quota >= 1000)
		var display_quota = quota/1000 + "GB";
	else
		var display_quota = quota + "MB";

	// Makes usage human readable
	if (used >= 1000000)
		var display_used = used/1000000 + "TB";
	else if (used >= 1000)
		var display_used = used/1000 + "GB";
	else
		var display_used = used + "MB";

	// Get percent and remain
	var percent = (quota) ? Math.round((used / quota) * 100, 0) : 0;
	var remain = ((100 - percent) < 0) ? 0 : 100 - percent;

	// Diplay text label
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "margin-bottom", "2px");
	dv.innerHTML = display_used + " of " + display_quota + " (" + percent + "%)";
	mdiv.appendChild(dv);

	// Display usage status bar
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSet(dv, "width", "102px");
	mdiv.appendChild(dv);
	this.m_prog_left_dv = ALib.m_document.createElement("div");
	dv.appendChild(this.m_prog_left_dv);
	alib.dom.styleSet(this.m_prog_left_dv, "width", percent+"px");
	this.m_prog_left_dv.className = 'HomeWelcomeProgressBarLeft';

	this.m_progright_dv = ALib.m_document.createElement("div");
	dv.appendChild(this.m_progright_dv);
	alib.dom.styleSet(this.m_progright_dv, "width", remain+"px");
	this.m_progright_dv.className = 'HomeWelcomeProgressBarRight';
	*/

	this.getTextColor();
}

/*************************************************************************
*	Function:	setTextColor
*
*	Purpose:	Change and save custom color for text
**************************************************************************/
CWidWelcome.prototype.setTextColor = function(color, save)
{
	alib.dom.styleSet(this.mdiv, "color", "#"+color);
	/*
	alib.dom.styleSet(this.m_prog_left_dv, "background-color", "#"+color);
	alib.dom.styleSet(this.m_prog_left_dv, "border-left-color", "#"+color);
	alib.dom.styleSet(this.m_prog_left_dv, "border-top-color", "#"+color);
	alib.dom.styleSet(this.m_prog_left_dv, "border-bottom-color", "#"+color);
	alib.dom.styleSet(this.m_progright_dv, "border-right-color", "#"+color);
	alib.dom.styleSet(this.m_progright_dv, "border-top-color", "#"+color);
	alib.dom.styleSet(this.m_progright_dv, "border-bottom-color", "#"+color);
	*/

	if (save)
		this.savePref("setWelColor", color);
}

/*************************************************************************
*	Function:	getTextColor
*
*	Purpose:	Call xml_actions.awp to get saved text color
**************************************************************************/
CWidWelcome.prototype.getTextColor = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(ret)
            this.cbData.cls.setTextColor(ret);
    };
    ajax.exec("/controller/Application/getWelColor",
                [["appNavname", this.appNavname]]);
}

/*************************************************************************
*	Function:	savePref
*
*	Purpose:	Save a preference - with no callback or verification
**************************************************************************/
CWidWelcome.prototype.savePref = function(name, val)
{
    var args = new Array();        
    args[0] = ['val', val];
    args[1] = ['appNavname', this.appNavname];
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Application/" + name, args);
}

/*************************************************************************
*	Function:	loadImage
*
*	Purpose:	Load image into welcome center inner container
**************************************************************************/
CWidWelcome.prototype.loadImage = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.widImage = this.m_image;
    ajax.onload = function(ret)
    {
        this.cbData.widImage.cls = this.cbData.cls;
        this.cbData.widImage.onload = function()
        {
            if (this.height > 130)
                this.cls.m_container.style.height = this.height + "px";

            this.cls.m_container.style.backgroundImage = 'url('+this.src+')';
            this.cls.m_container.style.backgroundRepeat='no-repeat';
            this.cls.m_container.style.backgroundPosition = "top left";    
        }
        this.cbData.widImage.src = unescape(ret);
    };
    
    var args = new Array();        
    args[0] = ['width', this.mdiv.offsetWidth];
    args[1] = ['appNavname', this.appNavname];
    ajax.exec("/controller/Application/getWelImage", args);
}

/*************************************************************************
*	Function:	changeImage
*
*	Purpose:	Switch welcome center image. 'default' uses theme default.
*				'none' set to blank and will not try to load an image.
**************************************************************************/
CWidWelcome.prototype.changeImage = function(fileid, name)
{
	if (fileid == 'default')
	{
		this.m_cust_img_id = null;
		this.setTextColor('none');
		this.savePref('setWelImgDef', fileid);
	}
	else if (fileid == "none")
	{
		this.m_cust_img_id = null;
		this.m_container.style.backgroundImage = '';
		this.savePref('setWelImg', fileid);
	}
	else
	{
		this.m_cust_img_id = fileid;
		
		this.savePref('setWelImg', fileid);
	}
	
	this.loadImage();
}
