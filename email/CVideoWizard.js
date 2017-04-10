/****************************************************************************
*	
*	Class:		CVideoWizard
*
*	Purpose:	Wizard for inserting a new video
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/

function CVideoWizard(user_id)
{
	this.user_id			= user_id;		// Each time a directory is loaded the current user is populated for the upload tool
	this.template_id		= null;			// Uid if template to pull message definition from
	this.templates 			= new Array();	// Array of available templates
	this.video_file_name	= null;
	this.video_file_id		= null;
	this.video_file_jobid	= 0;			// For processing files
	this.f_videoIsTmp		= false;		// Video is temporarily uploaded - should be moved as appropriate
	this.logo_file_name		= null;
	this.logo_file_id		= null;
	this.buttons 			= new Array();	// Array of objects button.name, button.link
	this.title				= "My Message";
	this.subtitle			= "My Name/title";
	this.message			= "";
	this.footer				= "";
	this.theme 				= "white";
	this.save_template_name = null;
	this.save_template_changes  = 'f';
	this.f_template_video	= 'f';
	this.facebook			= "";
	this.twitter			= "";

	this.steps = new Array();
	this.steps[0] = "Getting Started";
	this.steps[1] = "Upload Video";
	this.steps[2] = "Page Layout";
	this.steps[3] = "Select Theme &amp; Preview Message";
	this.steps[4] = "Save &amp; Finish";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CVideoWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Video Message Wizard", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 775, 520);

	this.showStep(0);
}
/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
CVideoWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.cbTemplates = null;
	this.verify_step_data = new Object();

	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", this.body_dv);
	this.pathDiv.innerHTML = "Step " + (step + 1) + " of " + this.steps.length + " - " + this.steps[step];
	alib.dom.styleSetClass(this.pathDiv, "wizardTitle");

	// Main content
	// ---------------------------------------------------------
	var div_main = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(div_main, "wizardBody");

	switch (step)
	{
	case 0:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "This wizard will guide you through sending your video message/email";

		var lbl = alib.dom.createElement("div", div_main);
		lbl.innerHTML = "Would you like to:";
		alib.dom.styleSetClass(lbl, "formLabel");
		
		this.cbTemplates = alib.dom.createElement("select", div_main);
		var cbTemplates = this.cbTemplates;
		cbTemplates.size = 20;
		cbTemplates.style.width = "98%";
		cbTemplates.cls = this;
		cbTemplates.onchange = function()
		{
			if (this.value)
			{
				this.cls.template_id = this.value;
				this.cls.setTemplate(this.value);
			}
		}

		var btn_delt = new CButton("Delete Selected Template", function(cls, cbTemplates) { cls.deleteTemplates(cbTemplates);  }, [this, cbTemplates]);
		btn_delt.disable();

		var div_new = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'create';
		rbtn1.checked = (this.template_id) ? false : true;
		rbtn1.cbTemplates = cbTemplates;
		rbtn1.btn_delt = btn_delt;
		rbtn1.cls = this;
		rbtn1.onchange = function() {  cbTemplates.disabled = true; this.btn_delt.disable(); this.cls.template_id = null; }
		div_new.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_new);
		lbl.innerHTML = " Create New Video Message";

		var div_template = alib.dom.createElement("div", div_main);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'create';
		rbtn1.checked = (this.template_id) ? true : false;
		rbtn1.cbTemplates = cbTemplates;
		rbtn1.btn_delt = btn_delt;
		rbtn1.onchange = function() {  cbTemplates.disabled = false; this.btn_delt.enable(); /* set to template */ }
		div_template.appendChild(rbtn1);
		var lbl = alib.dom.createElement("span", div_template);
		lbl.innerHTML = " Use A Template";

		var div_select = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");
		cbTemplates.disabled = true;
		div_select.appendChild(cbTemplates);

		btn_delt.print(div_select);

		// Load templates
		if (!this.templates.length)
			this.loadTemplates();
		else
			this.populateTemplates();	

		break;
	case 1:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Select a video:. ";
		var p = alib.dom.createElement("h5", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "We highly recommend the flash (FLV) format but  AVI, WMV, and MPEG files will also work";

		var div_upload = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "-- OR --";

		var a_browse = alib.dom.createElement("a", div_main);

		var div_res = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");

		var div_display = alib.dom.createElement("div", div_main); // display the video info
		alib.dom.styleSetClass(div_display, "formLabel");
		alib.dom.styleSet(div_display, "margin", "10px");
		alib.dom.styleSet(div_display, "padding", "5px");
		alib.dom.styleSet(div_display, "text-align", "center");
		if (this.video_file_name)
		{
			alib.dom.styleSet(div_display, "border", "1px solid");
			div_display.innerHTML = "Selected File: " + this.video_file_name;
		}

		var cfupload = new AntFsUpload('%tmp%', m_dlg);
		cfupload.process_function = "toflv"; // Conver the image to flv
		cfupload.m_appcls = this;
		cfupload.div_display = div_display;
		cfupload.onUploadStarted = function () { this.m_appcls.wait_uploading = true; };
		cfupload.onQueueComplete = function () { this.m_appcls.wait_uploading = false; };
		cfupload.onUploadSuccess = function (fid, name, jobid) 
		{ 
			this.m_appcls.video_file_id = fid; 
			this.m_appcls.video_file_name = name; 
			this.m_appcls.video_file_jobid = jobid; 
			this.m_appcls.f_videoIsTmp  = true; 
			alib.dom.styleSet(this.div_display, "border", "1px solid");
			this.div_display.innerHTML = "Selected File: " + name;
		};
		cfupload.showTmpUpload(div_upload, div_res, 'Upload Video', 1);


		var cbrowser = new AntFsUpload('%tmp%', m_dlg);
		cbrowser.filterType = "avi:wmv:mpg:mpeg:m4v:flv:f4v:jpeg:jpg:png:gif";
		cbrowser.m_appcls = this;
		cbrowser.div_display = div_display;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.m_appcls.video_file_id = fid; 
			this.m_appcls.video_file_name = name; 
			this.m_appcls.f_videoIsTmp = false; 
			alib.dom.styleSet(this.div_display, "border", "1px solid");
			this.div_display.innerHTML = "Selected Video: " + name;
		}

		a_browse.innerHTML = "Select a file from ANT File System";
		a_browse.href = 'javascript:void(0);';
		a_browse.cbrowser = cbrowser;
		a_browse.m_dlg = this.m_dlg;
		a_browse.onclick = function() { this.cbrowser.showDialog(this.m_dlg); }

		// Logo
		// ------------------------------------------------------------------
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Select a logo/image (will go next to your title and name): ";

		var a_browse = alib.dom.createElement("a", div_main);

		var div_logo_res = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(div_select, "margin", "5px 0 3px 0");

		var div_logo_display = alib.dom.createElement("div", div_main); // display logo info
		alib.dom.styleSetClass(div_logo_display, "formLabel");
		if (this.logo_file_id)
		{
			div_logo_display.innerHTML = "<img src='/files/"+this.logo_file_id+"' style='height:70px;' />";
		}

		// ANT File Browser
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "jpg:jpeg:gif:png";
		cbrowser.cbData.m_appcls = this;
		cbrowser.cbData.div_display = div_logo_display;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cbData.m_appcls.logo_file_id = fid; 
			this.cbData.m_appcls.logo_file_name = name; 
			this.cbData.div_display.innerHTML = "<img src='/files/"+fid+"' style='height:70px;' />";
		}
		
		a_browse.innerHTML = "Select a file from ANT File System";
		a_browse.href = 'javascript:void(0);';
		a_browse.cbrowser = cbrowser;
		a_browse.m_dlg = this.m_dlg;
		a_browse.onclick = function() { this.cbrowser.showDialog(this.m_dlg); }

		this.veriftyStep = function()
		{
			if (!this.video_file_id)
			{
				this.verify_step_data.message = "Please upload a video file before continuing";
				return false;
			}
			else
				return true;
		}
		break;
	case 2:
		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 10px 0");
		p.innerHTML = "Build the page you would like your recipient to see when they click to watch your video";

		// Title
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSetClass(dv, "formLabel");
		dv.innerHTML = "Page Header / Title:";
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "margin-bottom", "5px");
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.style.width = "98%";
		inp.value = this.title;
		inp.cls = this;
		inp.onchange = function() { this.cls.title = this.value; };
		dv.appendChild(inp);

		// Subtitle
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSetClass(dv, "formLabel");
		dv.innerHTML = "Your Name &amp; Title (optional):";
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "margin-bottom", "5px");
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.style.width = "98%";
		inp.value = this.subtitle;
		inp.cls = this;
		inp.onchange = function() { this.cls.subtitle = this.value; };
		dv.appendChild(inp);

		// Footer
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSetClass(dv, "formLabel");
		dv.innerHTML = "Page Footer:";
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "margin-bottom", "5px");
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.style.width = "98%";
		inp.value = this.footer;
		inp.cls = this;
		inp.onchange = function() { this.cls.footer = this.value; };
		dv.appendChild(inp);

		// Message
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSetClass(dv, "formLabel");
		dv.innerHTML = "Message (optional):";
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "margin-bottom", "5px");
		var inp = alib.dom.createElement("textarea");
		inp.style.width = "98%";
		inp.style.height = "75px";
		inp.value = this.message;
		inp.cls = this;
		inp.onchange = function() { this.cls.message = this.value; };
		dv.appendChild(inp);

		// Buttons
		// -----------------------------------------------------------------
		var dv = alib.dom.createElement("div", div_main);
		var lbl = alib.dom.createElement("span", dv);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Buttons - these will be to the right of your video:";
		var lbl = alib.dom.createElement("span", dv);
		lbl.innerHTML = " (TIP: enter an email address to compose email)";
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "height", "120px");
		alib.dom.styleSet(dv, "border", "1px solid");
		alib.dom.styleSet(dv, "overflow", "auto");
		
		if (this.buttons.length)
		{
			for (var i = 0; i < this.buttons.length; i++)
			{
				this.addButtonRow(dv, i);
			}
		}
		else
		{
				this.addButtonRow(dv);
				this.addButtonRow(dv);
				this.addButtonRow(dv);
		}

		var dv_add = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(a, "margin-top", "3px");
		var a = alib.dom.createElement("a", dv_add);
		a.innerHTML = "[add button]";
		a.href = "javascript:void(0);";
		a.dv = dv;
		a.cls = this;
		a.onclick = function()
		{
			this.cls.addButtonRow(this.dv);	
		}
		
		var table = alib.dom.createElement("table", div_main);
		alib.dom.styleSet(table, "margin-top", "5px");
		table.style.width = "98%";
		var tableBody = alib.dom.createElement("tbody", table);
		var tr = alib.dom.createElement("tr", tableBody);
		var td = alib.dom.createElement("td", tr);
		var dv = alib.dom.createElement("div", div_main);
		var lbl = alib.dom.createElement("strong", dv);
		lbl.innerHTML = "Facebook page: ";
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.style.width = "250px";
		inp.value = this.facebook;
		inp.cls = this;
		inp.onchange = function() { this.cls.facebook = this.value; };
		dv.appendChild(inp);
		td.appendChild(lbl);
		td.appendChild(inp);
		
		var td = alib.dom.createElement("td", tr);	
		var lbl = alib.dom.createElement("strong", dv);
		lbl.innerHTML = " Twitter url: ";
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		inp.style.width = "250px";
		inp.value = this.twitter;
		inp.cls = this;
		inp.onchange = function() { this.cls.twitter = this.value; };
		dv.appendChild(inp);
		td.appendChild(lbl);
		td.appendChild(inp);
		tr.appendChild(td);
		table.appendChild(tableBody);
		
		break;
	case 3:
		var dv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(dv, "margin", "5px 0 5px 0");

		var lbl = alib.dom.createElement("span", dv);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Select Theme: ";

		var sel = alib.dom.createElement("select", dv);
		sel[sel.length] = new Option("Default", "white", false, (this.theme=="white")?true:false);
		sel[sel.length] = new Option("Red", "red", false, (this.theme=="red")?true:false);
		sel[sel.length] = new Option("Blue", "blue", false, (this.theme=="blue")?true:false);
		this.getThemes(sel);

		var dv = alib.dom.createElement("div", div_main);
		var ifrm = alib.dom.createElement("iframe", dv);
		alib.dom.styleSet(ifrm, "height", "360px");
		alib.dom.styleSet(ifrm, "width", "100%");

		sel.ifrm = ifrm;
		sel.cls = this;
		sel.onchange = function()
		{
			this.cls.theme = this.value;
			this.cls.loadPreview(this.ifrm, this.value);
		}

		this.loadPreview(ifrm, this.theme);

		break;
	case 4:
		var hdr = alib.dom.createElement("h2", div_main);
		hdr.innerHTML = "Congratulations!";

		var hdr = alib.dom.createElement("h3", div_main);
		hdr.innerHTML = "Your Video Message is ready to be sent. Click \"Finish\" below and compose an email message to your desired recipients of this video. They will receive a link to the video.";


		var fieldset = alib.dom.createElement("fieldset", div_main);
		alib.dom.styleSet(fieldset, "margin", "20px 0 5px 0");
		var legend = alib.dom.createElement("legend", fieldset);
		legend.innerHTML = "Save Template (optional)";

		var inp_name = alib.dom.createElement("input"); // created first for reference

		if (this.template_id)
		{
			var dv = alib.dom.createElement("div", fieldset);
			alib.dom.styleSet(dv, "margin", "5px 0 5px 0");

			var lbl = alib.dom.createElement("span", dv);
			alib.dom.styleSetClass(lbl, "formLabel");
			lbl.innerHTML = "Save Changes: ";
			var inp = alib.dom.createElement("input");
			inp.type = "checkbox";
			inp.checked = false;
			inp.cls = this;
			inp.inp_name = inp_name;
			inp.onclick = function() { this.cls.save_template_changes = (this.checked) ? 't' : 'f'; this.inp_name.disabled = (this.checked) ? true : false; };
			dv.appendChild(inp);
			var lbl = alib.dom.createElement("span", dv);
			lbl.innerHTML = " (save the changes I have made to this template)";
		}

		var lbl = alib.dom.createElement("span", fieldset);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Save As: ";
		inp_name.type = "text";
		inp_name.style.width = "100px";
		inp_name.cls = this;
		inp_name.onchange = function() { this.cls.save_template_name = this.value; };
		fieldset.appendChild(inp_name);

		var lbl = alib.dom.createElement("span", fieldset);
		lbl.innerHTML = " (save settings to re-use later, leave blank if you do not wish to save)";

		var dv = alib.dom.createElement("div", fieldset);
		alib.dom.styleSet(dv, "margin", "5px 0 5px 0");

		var lbl = alib.dom.createElement("span", dv);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Save Video: ";
		var inp = alib.dom.createElement("input");
		inp.type = "checkbox";
		inp.cls = this;
		inp.onclick = function() { this.cls.f_template_video = (this.checked) ? 't' : 'f'; };
		dv.appendChild(inp);
		var lbl = alib.dom.createElement("span", dv);
		lbl.innerHTML = " (use this video any time template is loaded in the future)";

		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(dv_btn, "wizardFooter");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.save(); }, [this]);
		btn.print(dv_btn);
	}
	else
	{
		var next_funct = function(cls, step)
		{
			if (cls.veriftyStep())
			{
				cls.showStep(step+1);
			}
			else
			{
				ALib.Dlg.messageBox(cls.verify_step_data.message, cls.m_dlg);
			}
		}

		var btn = new CButton("Next", next_funct, [this, step], "b2");
		btn.print(dv_btn);
	}

	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [this.m_dlg], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
CVideoWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CVideoWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CVideoWizard.prototype.onFinished = function(mid, message)
{
}

/*************************************************************************
*	Function:	addButtonRow
*
*	Purpose:	Add a button row
**************************************************************************/
CVideoWizard.prototype.addButtonRow = function(con, idx)
{
	if (typeof idx == "undefined")
	{
		var idx = this.buttons.length;
		this.buttons[idx] = new Object();
		this.buttons[idx].id = idx;
		this.buttons[idx].label = "Button " + (idx + 1);
		this.buttons[idx].link = "";
	}

	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "margin", "3px");
	
	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = "Button label ";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 32);
	inp.type = "text";
	inp.style.width = "100px";
	inp.cls = this;
	inp.button = this.buttons[idx];
	inp.value = this.buttons[idx].label;
	inp.onchange = function() { this.button.label = this.value; };
	dv.appendChild(inp);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " link ";

	var inp = alib.dom.createElement("input");
	inp.type = "text";
	inp.style.width = "200px";
	inp.cls = this;
	inp.button = this.buttons[idx];
	inp.value = this.buttons[idx].link;
	inp.onchange = function() { this.button.link = this.value; };
	dv.appendChild(inp);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = "&nbsp;";
	
	var a = alib.dom.createElement("a", dv);
	a.innerHTML = "[remove]";
	a.href = "javascript:void(0);";
	a.thiscon = dv;
	a.parentcon = con;
	a.bid = this.buttons[idx].id;
	a.cls = this;
	a.onclick = function()
	{
		for (var i = 0; i < this.cls.buttons.length; i++)
		{
			if (this.cls.buttons[i].id == this.bid)
			{
				this.cls.buttons.splice(i, 1);
			}
		}
		this.parentcon.removeChild(this.thiscon);
	}
}

/*************************************************************************
*	Function:	loadPreview
*
*	Purpose:	Reset iframe
**************************************************************************/
CVideoWizard.prototype.loadPreview = function(ifrm, theme)
{
	var url = "/email/vmail_player.php?title="+((this.title)?escape(this.title):'Untitled');
	if (this.subtitle)
		url += "&subtitle=" + escape(this.subtitle);
	if (this.video_file_id)
		url += "&video_file_id=" + escape(this.video_file_id);
	if (this.logo_file_id)
		url += "&logo_file_id=" + escape(this.logo_file_id);
	if (this.video_file_jobid)
		url += "&video_file_jobid=" + escape(this.video_file_jobid);
	for (var i = 0; i < this.buttons.length; i++)
	{
		url += "&buttons[]=" + escape(this.buttons[i].label)+"|"+ escape(this.buttons[i].link);
	}
	if (this.message)
		url += "&message=" + escape(this.message);
	if (this.footer)
		url += "&footer=" + escape(this.footer);
	if (theme)
		url += "&theme=" + escape(theme);
	if(this.facebook)
		url += "&facebook=" + escape(this.facebook);
	if(this.twitter)
		url += "&twitter=" + escape(this.twitter);

	ifrm.src = url;
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save this video message
**************************************************************************/
CVideoWizard.prototype.save = function()
{
	var args = [["video_file_id", this.video_file_id], ["logo_file_id", this.logo_file_id], ["title", this.title], ["subtitle", this.subtitle],
				["message", this.message], ["footer", this.footer], ["theme", this.theme], ["save_template_name", this.save_template_name],
				["f_template_video", this.f_template_video], ["template_id", this.template_id], ["save_template_changes", this.save_template_changes],
				["facebook", this.facebook], ["twitter", this.twitter], ["f_video_is_tmp", (this.f_videoIsTmp)?'t':'f']];

	for (var i = 0; i < this.buttons.length; i++)
	{
		args[args.length] = ["buttons[]", this.buttons[i].label+"|"+this.buttons[i].link];
	}

	this.m_dlg.hide();

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Preparing message, please wait...";
	dlg.statusDialog(dv_load, 250, 100);
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            this.cls.onFinished(ret, this.cls.message);
        }

        this.dlg.hide();            
    };
    ajax.exec("/controller/Email/saveVideoMail", args);
}


/*************************************************************************
*	Function:	loadTemplates
*
*	Purpose:	Load previously saved templates
**************************************************************************/
CVideoWizard.prototype.loadTemplates = function()
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret.length)
        {
            for(template in ret)
            {
                var currentTemplate = ret[template];
                
                this.cls.templates[this.cls.templates.length] = currentTemplate;
            }
        }

        cls.populateTemplates();
    };
    ajax.exec("/controller/Email/getVmailTemplates");
}

/*************************************************************************
*	Function:	loadTemplates
*
*	Purpose:	Load previously saved templates
**************************************************************************/
CVideoWizard.prototype.getThemes = function(sel)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.sel = sel;
    ajax.onload = function(ret)
    {
        if (ret.length)
        {
            for(theme in ret)
            {
                var currentTheme = ret[theme];
                
                var id = currentTheme.id;
                var name = currentTheme.name;
                this.sel[this.sel.length] = new Option(name, id, false, (this.cls.theme==id)?true:false);

            }
        }   
    };
    ajax.exec("/controller/Email/getVmailThemes");
}

/*************************************************************************
*	Function:	populateTemplates
*
*	Purpose:	Place templates in select box
**************************************************************************/
CVideoWizard.prototype.populateTemplates = function()
{
	if (!this.cbTemplates)
		return;

	for (var i = 0; i < this.templates.length; i++)
	{
		var template = this.templates[i];

		this.cbTemplates[this.cbTemplates.length] = new Option(template.name, template.id, false, (template.id == this.template_id)?true:false);
	}
}

/*************************************************************************
*	Function:	getTemplateById
*
*	Purpose:	Get a template by id
**************************************************************************/
CVideoWizard.prototype.setTemplate = function(id)
{
	for (var i = 0; i < this.templates.length; i++)
	{
		if (this.templates[i].id == id)
		{
			this.title = this.templates[i].title;
			this.video_file_id = this.templates[i].file_id;
			this.video_file_name = this.templates[i].file_name;
			this.logo_file_id = this.templates[i].logo_file_id;
			this.logo_file_name = this.templates[i].logo_file_name;
			this.subtitle = this.templates[i].subtitle;
			this.message = this.templates[i].message;
			this.facebook = this.templates[i].facebook;
			this.twitter = this.templates[i].twitter;
			this.name = this.templates[i].name;
			this.footer = this.templates[i].footer;
			this.theme = this.templates[i].theme;

			this.buttons = new Array();

			for (var j = 0; j < this.templates[i].buttons.length; j++)
			{
				this.buttons[j] = new Object();
				this.buttons[j].id = this.templates[i].buttons[j].id;
				this.buttons[j].label = this.templates[i].buttons[j].label;
				this.buttons[j].link= this.templates[i].buttons[j].link;
			}

			return;
		}
	}
}

/*************************************************************************
*	Function:	deleteTemplates
*
*	Purpose:	Delete a template
**************************************************************************/
CVideoWizard.prototype.deleteTemplates = function(cbTemplates)
{	
	var args = [["tid", cbTemplates.value]];
    
    ajax = new CAjax('json');    
    ajax.cbTemplates = cbTemplates;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            for (var i = 0; i < this.cbTemplates.options.length; i++)
            {
                if (this.cbTemplates.options[i].selected)
                    this.cbTemplates.options[i] = null;
            }
        }
    };
    ajax.exec("/controller/Email/deleteVmailTemplate", args);
}

