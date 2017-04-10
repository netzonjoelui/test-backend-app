/****************************************************************************
*	
*	Class:		CSearch
*
*	Purpose:	Global object search application
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CSearch()
{
	// Set some basic variables here
	this.m_document = null; 	// Application Document - set by ant
	this.m_processid = null;	// Process id - set by Ant
	this.m_container = null;	// Handle to application container - set by Ant
	this.m_applications = new Array(); // List of applications
	this.m_arguments = new Array(); // Optional arguments [["varname", varval]]
	this.searchString = "";
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CSearch.prototype.main = function()
{
	this.m_container.innerHTML = "";

	// Check arguments
	for (var i = 0; i < this.m_arguments.length; i++)
	{
		if (this.m_arguments[i][0] == "search")
			this.searchString = this.m_arguments[i][1];
	}

	alib.dom.styleAddClass(this.m_container, "appTopSpacer");

	var ctbl = new CContentTable("Enter Search Terms Below", "100%");
	ctbl.print(this.m_container);
	this.appCon = ctbl.get_cdiv();

	// Add Toolbar
	// ----------------------------
	var tb = new CToolbar();
	var inp = alib.dom.createElement("input");
	alib.dom.styleSet(inp, "width", "350px");
	Ant.Dom.setInputBlurText(inp, "search here", "CToolbarInputBlur", "", "");
	tb.AddItem(inp);
	inp.m_cls = this;
	if (this.searchString)
		inp.value = this.searchString;
	inp.onkeyup = function(e)
	{
		if (typeof e == 'undefined') 
		{
			if (ALib.m_evwnd)
				e = ALib.m_evwnd.event;
			else
				e = window.event;
		}

		if (typeof e.keyCode != "undefined")
			var code = e.keyCode;
		else
			var code = e.which;

		if (code == 13) // keycode for a return
		{
			this.m_cls.searchString = this.value;
			this.m_cls.getResults();
		}
	}
	btn = new CButton("Search", function(cls, inp) { cls.searchString = inp.value; cls.getResults(); }, [this, inp], "b2");
	tb.AddItem(btn.getButton());
	tb.print(this.appCon);

	// Results
	// ----------------------------
	var frm1 = new CWindowFrame("Results");
	frm1.print(this.appCon);
	this.m_resultsCon = frm1.getCon();
	this.getResults();
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CSearch.prototype.exit = function()
{
	// Perform any cleanup tasks here;
}
/*************************************************************************
*	Function:	getResults
*
*	Purpose:	Query server and get results
**************************************************************************/
CSearch.prototype.getResults = function(offset)
{
	if (!this.searchString)
	{
		this.m_resultsCon.innerHTML = "<div class='headerThree'>Begin by entering search terms in the box above.</div>";
		return;
	}

	var offset = (offset) ? offset : 0;

	this.m_resultsCon.innerHTML = " <div class='loading'></div>";

	this.m_ajax = new CAjax();
	this.m_ajax.m_browseclass = this;
	this.m_ajax.onload = function(root)
	{
		this.m_browseclass.chkBoxes = new Array();
		var num_objects = 0;

		// The result will be held in a variable called 'retval'
		var num = root.getNumChildren();
		if (num)
		{
			this.m_browseclass.m_resultsCon.innerHTML = "";

			for (i = 0; i < num; i++)
			{

				var child = root.getChildNode(i);

				// Enter object row
				if (child.m_name == "object")
				{
					var dv = alib.dom.createElement("div", this.m_browseclass.m_resultsCon);
					this.m_browseclass.printObjectRow(dv, child);
				}
				else if (child.m_name == "paginate")
				{
					/*
					var prev = child.getChildNodeValByName("prev");
					var next = child.getChildNodeValByName("next");
					var pag_str = child.getChildNodeValByName("pag_str");					
					
					var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
					lbl.innerHTML = " | "+pag_str;

					if (prev || next)
					{
						var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
						lbl.innerHTML = " | ";

						if (prev)
						{
							var lnk = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
							lnk.innerHTML = "&laquo; previous";
							alib.dom.styleSet(lnk, "cursor", "pointer");
							lnk.start = prev;
							lnk.m_browseclass = this.m_browseclass;
							lnk.onclick = function()
							{
								this.m_browseclass.getObjects(this.start);
							}
						}

						if (next)
						{
							var lnk2 = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
							lnk2.innerHTML = " next &raquo;";
							alib.dom.styleSet(lnk2, "cursor", "pointer");
							lnk2.start = next;
							lnk2.m_browseclass = this.m_browseclass;
							lnk2.onclick = function()
							{
								this.m_browseclass.getObjects(this.start);
							}
						}
					}
					*/
				}
				else if (child.m_name == "num")
				{
					/*
					if (this.m_browseclass.m_wfresults)
					{
						var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
						lbl.innerHTML = child.m_text + " " + this.m_browseclass.mainObject.titlePl;
						num_objects = parseInt(child.m_text);
					}
					*/
				}
			}

		}
		else
		{
			this.m_browseclass.m_resultsCon.innerHTML = "<div class='headerThree'>You search \""+this.m_browseclass.searchString+"\" "
													  + "did not return any results. Please modify your keywords and try again.</div>";
		}
	};

	var url = "/objects/xml_search_idx.php?fval=1";
	//if (this.m_txtSearch.value && this.m_txtSearch.value != 'search here')
		//url += "&search=" + escape(this.m_txtSearch.value);
	if (offset)
		url += "&offset=" + offset;
	if (this.limit)
		url += "&limit=" + this.limit;
	var args = [["search", this.searchString]];
	this.m_ajax.m_method = AJAX_POST;
	//ALib.m_debug = true;
	//AJAX_TRACE_RESPONSE = true;
	this.m_ajax.exec(url, args);
}

/*************************************************************************
*	Function:	printObjectRow
*
*	Purpose:	Print results row
**************************************************************************/
CSearch.prototype.printObjectRow = function(rowscon, child)
{
	var id = unescape(child.getChildNodeValByName("id"));
	var name = unescape(child.getChildNodeValByName("name"));
	var obj_type = unescape(child.getChildNodeValByName("obj_type"));
	var obj_type_title = unescape(child.getChildNodeValByName("obj_type_title"));
	var snippet = unescape(child.getChildNodeValByName("snippet"));

	var dv = alib.dom.createElement("div", rowscon);
	alib.dom.styleSetClass(dv, "DynDivInact");
	alib.dom.styleSet(dv, "cursor", "default");
	alib.dom.styleSet(dv, "margin", "10px 5px 0px 5px");
	alib.dom.styleSet(dv, "padding-bottom", "5px");
	//dv.innerHTML = obj_type_title + ": " + name;
	
	var a = alib.dom.createElement("a", dv);
	a.href = "javascript:void(0);";
	a.innerHTML = obj_type_title + ": " + name;
	a.obj_type = obj_type;
	a.object_id = id;
	a.onclick = function()
	{
		loadObjectForm(this.obj_type, this.object_id);
	}

	var dv_snippet = alib.dom.createElement("div", dv);
	dv_snippet.innerHTML = snippet;
}
