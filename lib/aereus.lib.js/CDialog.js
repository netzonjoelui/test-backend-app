/*======================================================================================
	
	Module:		CDialog

	Purpose:	Create custom dialog box

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Usage:		// Modal
				var dlg_d = new CDialog();
				var dv = alib.dom.createElement("div");
				dv.m_input = alib.dom.createElement("input", dv);
				var dv_btn = alib.dom.createElement("div", dv);
				var btn = new CButton("Alert", function(dv, dlg) {  ALib.Dlg.messageBox("Say Something", dlg); }, [dv, dlg_d]); // Second param makes it modal
				btn.print(dv_btn);
				var btn = new CButton("Close", function(dlg) {  dlg.hide(); }, [dlg_d]);
				btn.print(dv_btn);

				var btn = new CButton("Modal", function(dv, dlg) { dlg.customDialog(dv, 200, 200); }, [dv, dlg_d], "b1");
				btn.print(con);

======================================================================================*/

var gDialogsVisisble = 0;

/***********************************************************************************
 *
 *	Class: 		CDialog
 *
 *	Purpose:	Encapsulate custom dialog functionality
 *
 ***********************************************************************************/
function CDialog(title, parentDlg)
{
	if (title)
		this.m_title = title;
	else
		this.m_title = null;

	this.zind = (parentDlg) ? parentDlg.zind+1 : 21 + gDialogsVisisble; // put new dialogs on top of previous ones
	this.m_initialized = false;
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.f_close = false;
	//this.initdlg();
}

CDialog.prototype.fixSizeAndScroll = function() 
{
	ALib.m_evwnd.onscroll = this.scrollFix; 
	ALib.m_evwnd.onresize = this.sizeFix;
}

CDialog.prototype.posLeft = function() 
{
	return typeof ALib.m_evwnd.pageXOffset != 'undefined' ? ALib.m_evwnd.pageXOffset
			: ALib.m_document.documentElement && ALib.m_document.documentElement.scrollLeft
			? ALib.m_document.documentElement.scrollLeft
			: ALib.m_document.body.scrollLeft ? ALib.m_document.body.scrollLeft:0;
}

CDialog.prototype.posTop = function() 
{
	return typeof ALib.m_evwnd.pageYOffset != 'undefined' ? ALib.m_evwnd.pageYOffset
			: ALib.m_document.documentElement && ALib.m_document.documentElement.scrollTop
			? ALib.m_document.documentElement.scrollTop
			: ALib.m_document.body.scrollTop?ALib.m_document.body.scrollTop:0;
}

CDialog.prototype.gete= function(x)
{
	return ALib.m_document.getElementById(x);
}

CDialog.prototype.scrollFix = function()
{
	if (this.overlay)
	{
		this.overlay.style.top=alib.dom.getScrollPosTop()+'px';
		this.overlay.style.left=alib.dom.getScrollPosLeft()+'px';
	}
}

CDialog.prototype.sizeFix = function()
{
	if (this.overlay)
	{
		this.overlay.style.height=alib.dom.GetDocumentHeight()+'px';
		this.overlay.style.width=alib.dom.getDocumentWidth()+'px';
	}
}

CDialog.prototype.kp = function(e)
{
	ky=e?e.which:event.keyCode;
	if(ky==88||ky==120) this.hm();
	return false
}

CDialog.prototype.inf = function(h)
{
	if (!this.parentDlg)
	{
		/*
		tag=ALib.m_document.getElementsByTagName('select');
		
		for(i=tag.length-1;i>=0;i--)
		{
			if (!tag[i].dlgField)
				tag[i].style.visibility=h;
		}
		
		tag=ALib.m_document.getElementsByTagName('iframe');
		
		for(i=tag.length-1;i>=0;i--)
		{
			if (!tag[i].dlgField)
				tag[i].style.visibility=h;
		}
		*/
	}
	
	/*
	 * TODO: Correct this so that objects can work on dialogs but not on the background
	tag=ALib.m_document.getElementsByTagName('object');
	
	for(i=tag.length-1;i>=0;i--)
	{
		if (!tag[i].dlgField)
			tag[i].style.visibility=h;
	}
	*/
}

CDialog.prototype.showOverlay = function(wd, ht)
{
	if (!this.m_initialized)
		this.initdlg();

	ALib.m_document.getElementsByTagName('body')[0].appendChild(this.m_dcon);

	var h = 'hidden';
	var b = 'block';
	var p = 'px';

	// Display overlay
	/*
	this.overlay.style.height = alib.dom.getDocumentHeight()+p;
	this.overlay.style.width = alib.dom.getDocumentWidth()+p;
	this.overlay.style.top = "0px"; //alib.dom.getScrollPosTop()+p;
	this.overlay.style.left = "0px"; //alib.dom.getScrollPosLeft()+p;
	this.overlay.style.display = b;
	this.overlay.onFadeFinished = function() { };
	ALib.Effect.fadein(this.overlay, -1);
	*/
	this.overlay.style.height = alib.dom.getDocumentHeight()+p;
	this.overlay.style.width = alib.dom.getDocumentWidth()+p;
	this.overlayOuter.style.display = b;
	this.overlayOuter.onFadeFinished = function() { };
	ALib.Effect.fadein(this.overlayOuter, -1);
	this.inf(h);
}

CDialog.prototype.show = function(wd, ht)
{
	this.sm(wd, ht);
}

CDialog.prototype.sm = function(wd, ht)
{
	var h = 'hidden';
	var b = 'block';
	var p = 'px';

	// Display overlay
	this.showOverlay();

	// Make sure the width is not wider than the client
	if (wd > alib.dom.getClientWidth())
		wd = alib.dom.getClientWidth();

	/* Moved to reposition function
	var sptop = alib.dom.getScrollPosTop();
	var spleft = alib.dom.getScrollPosLeft();

	var tp= sptop +((alib.dom.getClientHeight()-ht)/2)-12;
	var lt= spleft +((alib.dom.getClientWidth()-wd)/2)-12;

	this.m_dcon.style.top=(tp<0?0:tp)+p;
	this.m_dcon.style.left=(lt<0?0:lt)+p;
	*/
	this.m_dcon.style.width=wd +p;
	if (ht)
		this.m_dcon.style.height=ht +p;
	this.m_dcon.style.overflow="hidden";
	//this.inf(h);
	this.m_dcon.style.display=b;

	this.reposition();

	// Increment the number of dialogs visible
	gDialogsVisisble++;

	return false;
}

CDialog.prototype.hideOverlay = function()
{
	var v = 'visible';
	//var n = 'none';
	this.overlayOuter.onFadeFinished = function()
    {
        this.style.display = "none";
    };
    
	ALib.Effect.fadeout(this.overlayOuter, 200);
	//this.overlay.style.display=n;
	this.inf(v);
}

CDialog.prototype.hide = function()
{
	this.hm();
}

CDialog.prototype.hm = function()
{
	var v = 'visible';
	var n = 'none';

	// Decrement the number of dialogs visible
	gDialogsVisisble--;

	this.m_dcon.style.display=n;

	if (!this.parentDlg && gDialogsVisisble <= 0)
		this.hideOverlay();
	
	if (this.m_cleardv)
	{
		try
		{
			this.m_bodycon.removeChild(this.m_cleardv);
		} catch (e) {}

		this.m_cleardv = null;
	}

	this.m_dcon.parentNode.removeChild(this.m_dcon);
}	

CDialog.prototype.reposition = function()
{
	if (!this.m_dcon)
		return;


	var sptop = alib.dom.getScrollPosTop();
	var spleft = alib.dom.getScrollPosLeft();

	var tp= sptop + ((alib.dom.getClientHeight()/2)-(this.m_dcon.offsetHeight/2));
	var lt= spleft +((alib.dom.getClientWidth()/2)-(this.m_dcon.offsetWidth/2));

	this.m_dcon.style.top=(tp<0?0:tp)+"px";
	this.m_dcon.style.left=(lt<0?0:lt)+"px";
}

CDialog.prototype.initdlg = function()
{
	var ab='absolute';
	var n='none';
	var obody=ALib.m_document.getElementsByTagName('body')[0];
	var frag=ALib.m_document.createDocumentFragment();

	// Create document overlay - this should only exist once
	// we use overlayOuter so it can be faded without directly effecting
	// the styles of CDialogOverlay. That way an opacity can be set in a style
	// sheet and the fade will just set the opacity for the outer container
	this.overlay = alib.dom.getElementById('CDialogOverlay');
	this.overlayOuter = alib.dom.getElementById('CDialogOverlayOuter');
	if (!this.overlay)
	{
		this.overlayOuter = alib.dom.createElement('div', this.overlayOuter);
		alib.dom.styleSet(this.overlayOuter, "display", "none");
		alib.dom.styleSet(this.overlayOuter, "position", "absolute");
		alib.dom.styleSet(this.overlayOuter, "top", "0");
		alib.dom.styleSet(this.overlayOuter, "left", "0");
		this.overlayOuter.style.zIndex = "20";
		alib.dom.styleSet(this.overlayOuter, "width", "100%");
		this.overlayOuter.setAttribute('id','CDialogOverlayOuter');

		this.overlay = alib.dom.createElement('div', this.overlayOuter);
		this.overlay.setAttribute('id','CDialogOverlay');

		obody.appendChild(this.overlayOuter);

		/*
		this.overlay = alib.dom.createElement('div');
		this.overlay.setAttribute('id','CDialogOverlay');
		alib.dom.styleSet(this.overlay, "display", "none");
		alib.dom.styleSet(this.overlay, "position", "absolute");
		alib.dom.styleSet(this.overlay, "top", "0");
		alib.dom.styleSet(this.overlay, "left", "0");
		this.overlay.style.zIndex = "10";
		alib.dom.styleSet(this.overlay, "width", "100%");
		obody.appendChild(this.overlay);
		*/
	}

	// Create dialog container - there can be many dialogs in a document
	this.m_dcon = alib.dom.createElement('div');
	alib.dom.setClass(this.m_dcon, "CDialogCon");
	alib.dom.styleSet(this.m_dcon, "display", "none");
	alib.dom.styleSet(this.m_dcon, "position", "absolute");
	this.m_dcon.style.zIndex = this.zind;

	// Add title
	if (!this.m_titlecon)
	{
		this.m_titlecon = alib.dom.createElement("div");
		this.m_dcon.appendChild(this.m_titlecon);
		alib.dom.setClass(this.m_titlecon, "CDialogTitle");
		this.m_titlecon.style.display=n;	
	}
	if (this.m_title)
	{
		this.m_titlecon.innerHTML = this.m_title;
		this.m_titlecon.style.display="block";
	}
	
	// Add body
	this.m_bodycon = alib.dom.createElement("div");
	this.m_dcon.appendChild(this.m_bodycon);
	alib.dom.setClass(this.m_bodycon, "CDialogBody");

	//obody.appendChild(this.m_dcon);
	
	this.m_initialized = true;
}

CDialog.prototype.messageBox = function(msg, parentdlg)
{
	if (!this.m_initialized)
		this.initdlg();

	if (!this.m_title)
	{
		this.m_titlecon.innerHTML = "Message";
		this.m_titlecon.style.display="block";
	}

	var old_parent = null;
	if (parentdlg)
	{
		var old_parent = this.parentDlg;
		this.parentDlg = parentdlg;
	}

	var dlg = this;
    
    this.m_bodycon.innerHTML = "";
	var dv = alib.dom.createElement("div", this.m_bodycon);

	var dv_inner = alib.dom.createElement("div");
	alib.dom.styleSet(dv_inner, "text-align", "center");
	dv.appendChild(dv_inner);

	var sp = alib.dom.createElement("div");
	dv_inner.appendChild(sp);
	sp.innerHTML = msg;

	var bdv = alib.dom.createElement("div");
	bdv.setAttribute("align", "center");
	dv_inner.appendChild(bdv);
	var dlg_btn = new CButton("OK", function(dlg, old_parent, cls) { dlg.hm(); cls.parentDlg = old_parent;  }, [dlg, old_parent, this], "b1");
	dlg_btn.print(bdv);

	var len = msg.length * 10;
	this.sm(len, null);

	this.m_cleardv = dv;
}

CDialog.prototype.confirmBox = function(msg, title, args)
{
	if (!this.m_initialized)
		this.initdlg();

	if (title)
	{
		this.m_titlecon.innerHTML = title;
		this.m_titlecon.style.display="block";
	}

	var dlg = this;

	var dv = alib.dom.createElement("div");
	this.m_bodycon.appendChild(dv);

	var dv_inner = alib.dom.createElement("div");
	alib.dom.styleSet(dv_inner, "text-align", "center");
	dv.appendChild(dv_inner);

	var sp = alib.dom.createElement("div");
	dv_inner.appendChild(sp);
	sp.innerHTML = msg;

	var bdv = alib.dom.createElement("div");
	bdv.setAttribute("align", "center");
	dv_inner.appendChild(bdv);

	function yesClicked()
	{
		dlg.hide();
		if (args)
		{
			switch (args.length)
			{
			case 1:
				dlg.onConfirmOk(args[0]);
				break;
			case 2:
				dlg.onConfirmOk(args[0], args[1]);
				break;
			case 3:
				dlg.onConfirmOk(args[0], args[1], args[2]);
				break;
			case 4:
				dlg.onConfirmOk(args[0], args[1], args[2], args[3]);
				break;
			case 5:
				dlg.onConfirmOk(args[0], args[1], args[2], args[3], args[4]);
				break;
			case 6:
				dlg.onConfirmOk(args[0], args[1], args[2], args[3], args[4], args[5]);
				break;
			}
		}
		else
			dlg.onConfirmOk();

		dlg.onConfirmOk = new Function();
	}
	
	var dlg_btn = new CButton("Yes", yesClicked, null, "b1");
	dlg_btn.print(bdv);

	// Add spacer
	var spcr = alib.dom.createElement("span", bdv);
	spcr.innerHTML = "&nbsp;";

	function noClicked()
	{
		dlg.hide();
		if (args)
		{
			switch (args.length)
			{
			case 1:
				dlg.onConfirmCancel(args[0]);
				break;
			case 2:
				dlg.onConfirmCancel(args[0], args[1]);
				break;
			case 3:
				dlg.onConfirmCancel(args[0], args[1], args[2]);
				break;
			case 4:
				dlg.onConfirmCancel(args[0], args[1], args[2], args[3]);
				break;
			case 5:
				dlg.onConfirmCancel(args[0], args[1], args[2], args[3], args[4]);
				break;
			case 6:
				dlg.onConfirmCancel(args[0], args[1], args[2], args[3], args[4], args[5]);
				break;
			}
		}
		else
			dlg.onConfirmCancel();

		dlg.onConfirmCancel = new Function();
	}

	var dlg_btn = new CButton("No", noClicked, null, "b1");
	dlg_btn.print(bdv);

	//var len = msg.length * 10;
	this.sm(300, null);

	this.m_cleardv = dv;
}

CDialog.prototype.onConfirmOk = function()
{
}

CDialog.prototype.onConfirmCancel = function()
{
}

CDialog.prototype.promptBox = function(msg, title, def_value, args)
{
	if (!this.m_initialized)
		this.initdlg();

	if (title)
	{
		this.m_titlecon.innerHTML = title;
		this.m_titlecon.style.display="block";
	}

	var dlg = this;

	var dv = alib.dom.createElement("div");
	this.m_bodycon.appendChild(dv);

	var dv_inner = alib.dom.createElement("div");
	alib.dom.styleSet(dv_inner, "text-align", "center");
	dv.appendChild(dv_inner);

	var sp = alib.dom.createElement("div");
	dv_inner.appendChild(sp);
	sp.innerHTML = msg;

	var bdv = alib.dom.createElement("div");
	bdv.setAttribute("align", "center");
	dv_inner.appendChild(bdv);

	var inpdv = alib.dom.createElement("div", bdv);
	this.m_input = alib.dom.createElement("input", inpdv);
	alib.dom.styleSet(this.m_input, "width", "95%");
	this.m_input.value = def_value;

	function okClicked()
	{
		if (args)
		{
			switch (args.length)
			{
			case 1:
				dlg.onPromptOk(dlg.m_input.value, args[0]);
				break;
			case 2:
				dlg.onPromptOk(dlg.m_input.value, args[0], args[1]);
				break;
			case 3:
				dlg.onPromptOk(dlg.m_input.value, args[0], args[1], args[2]);
				break;
			case 4:
				dlg.onPromptOk(dlg.m_input.value, args[0], args[1], args[2], args[3]);
				break;
			case 5:
				dlg.onPromptOk(dlg.m_input.value, args[0], args[1], args[2], args[3], args[4]);
				break;
			case 6:
				dlg.onPromptOk(dlg.m_input.value, args[0], args[1], args[2], args[3], args[4], args[5]);
				break;
			}
		}
		else
			dlg.onPromptOk(dlg.m_input.value);

        dlg.hide();
		dlg.onPromptOk = new Function();
	}
	
	var dlg_btn = new CButton("Ok", okClicked, null, "b1");
	dlg_btn.print(bdv);


	// Add spacer
	var spcr = alib.dom.createElement("span", bdv);
	spcr.innerHTML = "&nbsp;";

	function cancelClicked()
	{
		dlg.hide();
		if (args)
		{
			switch (args.length)
			{
			case 1:
				dlg.onPromptCancel(args[0]);
				break;
			case 2:
				dlg.onPromptCancel(args[0], args[1]);
				break;
			case 3:
				dlg.onPromptCancel(args[0], args[1], args[2]);
				break;
			case 4:
				dlg.onPromptCancel(args[0], args[1], args[2], args[3]);
				break;
			case 5:
				dlg.onPromptCancel(args[0], args[1], args[2], args[3], args[4]);
				break;
			case 6:
				dlg.onPromptCancel(args[0], args[1], args[2], args[3], args[4], args[5]);
				break;
			}
		}
		else
			dlg.onPromptCancel();

		dlg.onPromptCancel = new Function();
	}

	var dlg_btn = new CButton("Cancel", cancelClicked, null, "b1");
	dlg_btn.print(bdv);

	if (typeof title != "undefined")
		var len = (msg.length > title.length) ? msg.length * 10 : title.length * 10;
	else
		var len = msg.length * 10;
	this.sm(len, null);

	this.m_cleardv = dv;
}

CDialog.prototype.onPromptOk = function()
{
}

CDialog.prototype.onPromptCancel = function()
{
}

CDialog.prototype.customDialog = function(con, width, height)
{
	if (!this.m_initialized)
		this.initdlg();

	if (this.m_title)
	{
		this.m_titlecon.innerHTML = "";

		if (this.f_close)
		{
			var closedv = alib.dom.createElement("div", this.m_titlecon);
			alib.dom.setClass(closedv, "CDialogTitleClose");
			alib.dom.styleSet(closedv, "float", "right");
			closedv.m_dlg = this;
			closedv.onclick = function() { this.m_dlg.hide(); }
			//closedv.innerHTML = "X";
		}

		var ttlsp = alib.dom.createElement("span", this.m_titlecon);
		ttlsp.innerHTML = this.m_title;
		this.m_titlecon.style.display="block";
	}

	var dlg = this;

	con.m_dialog = dlg;
	this.m_bodycon.appendChild(con);
	if (height)
	{
		this.m_bodycon.style.overflow = "auto";
		this.m_bodycon.style.height = height + "px";
	}

	this.m_cleardv = con;

	this.sm(width, null);
}

CDialog.prototype.statusDialog = function(con, width, height)
{
	if (!this.m_initialized)
		this.initdlg();
	
	alib.dom.setClass(this.m_bodycon, "");
	alib.dom.setClass(this.m_dcon, "");

	var dlg = this;

	con.m_dialog = dlg;
	this.m_bodycon.appendChild(con);

	this.m_cleardv = con;

	this.sm(width, height);
}

CDialog.prototype.setTitle = function(title)
{
    if (title)
    {
        this.m_titlecon.innerHTML = title;
        this.m_titlecon.style.display="block";
    }
}

// Eventually we will move to alib.ui.dialog namespace, but for now this is a work-around
alib.Dlg  = new CDialog();
