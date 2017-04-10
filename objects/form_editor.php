<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/date_time_functions.php");
	require_once("contacts/contact_functions.awp");
	require_once("customer/customer_functions.awp");
	require_once("lib/sms.php");
	require_once("lib/aereus.lib.php/CPageCache.php");
	require_once("datacenter/datacenter_functions.awp");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THEME = $USER->themeName;
	
	// Get forwarded variables
	$OBJ_TYPE = $_GET['obj_type'];
	$TEAM_ID = $_GET['team_id'];
	$USER_ID = $_GET['user_id'];
	$MOBILE = $_GET['mobile'];
	$DEFAULT = $_GET['default'];
	$SCOPE = $_GET['scope'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Form Editor</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="STYLESHEET" id='ant_css_base' type="text/css" href="/css/ant_base.css">
<link rel="STYLESHEET" id='ant_css_theme' type="text/css" href="/css/<?php echo $USER->themeCss; ?>">
<?php if (AntConfig::getInstance()->debug) { ?>
	<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.js"></script>
	<?php include("lib/js/includes.php"); ?>
<?php } else { ?>
	<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.cmp.js"></script>
	<script language="javascript" type="text/javascript" src="/lib/js/ant_full.cmp.js"></script>
<?php } ?>
<script language="javascript" type="text/javascript">
<?php
	echo "var g_obj_type= '".$OBJ_TYPE."';\n";
	echo "var team_id= '".$TEAM_ID."';\n";
	echo "var user_id= '".$USER_ID."';\n";
	echo "var mobile= '".$MOBILE."';\n";
	echo "var deflt= '".$DEFAULT."';\n";
	echo "var scope= '".$SCOPE."';\n";
?>
var g_antObject = new CAntObject(g_obj_type, null);
var xmlFormLayout = null;
var xmlFormLayoutText = "";
var available_tabs = new Array();
var form_tree = new Array();
var tabs = new CTabs();
var group_num = 0;
var numTabs = 0;
var aid = 0;

	function main()
	{	
		// Root of form_tree
		form_tree = { 'type':'root', children:[] };
	
		document.getElementById("bdy").innerHTML = "Loading...";
		
		// Check for default forms
		checkDefaultForm();
	}
	
	function buildInterface()
	{
		var con = document.getElementById("bdy");
		con.innerHTML = "";
		
		// Main Toolbar
		var tb = new CToolbar();
		var btn = new CButton("Close", function(cls) {window.close();}, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		var btn = new CButton("Save &amp; Close", saveForm, [true, true], "b1 grLeft");
		tb.AddItem(btn.getButton(), "left");
		var btn = new CButton("Save", saveForm, [false, true], "b1 grRight");
		tb.AddItem(btn.getButton(), "left");
		tb.print(document.getElementById('toolbar'));
		var btn = new CButton("Delete", deleteObject, null, "b3");
		tb.AddItem(btn.getButton(), "left");
		
		var type_div = alib.dom.createElement("div", con);
		alib.dom.styleSet(type_div, "margin", "0 0 5px 0");
		var scope_type = alib.dom.createElement("div", type_div);
		alib.dom.styleSet(scope_type, "margin", "0 5px 0 0");
		scope_type.innerHTML = "<strong>" + scope + " Form" + "</strong>";
		
		if(scope == "Team")
		{
			var sel = alib.dom.createElement("select", type_div);
			alib.dom.styleSet(scope_type, "float", "left");
			sel.onchange = function() { setTeamId(this.value); }
			sel[sel.length] = new Option("Select Team", "");
			
            // Populate Team Dropdown
            var userObject = new CAntObject("user");
            userObject.teamId = null;
            userObject.teamDropdown = sel;
            userObject.onteamsloaded = function(ret)
            {
                var teamData = ret;
                delete ret['teamCount'];
                this.populateTeam(ret, ret[0].parentId);
                this.addSpacedPrefix(teamData);
            }
            userObject.loadTeam();
            
            /*var ajax = new CAjax();
            ajax.m_dm = sel;
            ajax.onload = function(root)
            {
                // Get first node
                var num = root.getNumChildren();
                for (var j = 0; j < num; j++)
                {
                    var child = root.getChildNode(j);
                    var id = child.getChildNodeValByName("id");
                    var name = unescape(child.getChildNodeValByName("name"));
                    this.m_dm[this.m_dm.length] = new Option(name, id);                    
                }
            };
            ajax.exec("/users/xml_getteams.php");*/
		}
		
		if(scope == "User")
		{
			var cur_div = alib.dom.createElement("div", type_div);
			alib.dom.styleSet(scope_type, "float", "left");
			alib.dom.styleSet(cur_div, "float", "left");
			alib.dom.styleSet(cur_div, "margin", "0 3px 0 0");
			cur_div.innerHTML = "<strong>| Current User: </strong>";
			
			var usr = alib.dom.createElement("div", type_div);
			usr.id = "user_div";
			alib.dom.styleSet(usr, "float", "left");
			alib.dom.styleSet(usr, "margin", "0 3px 0 0");
			objectSetNameLabel("user", user_id, usr);
			
			// Select user button
			var btn = new CButton("Select User", setUserId, null, "b1");
			type_div.appendChild(btn.getButton());
		}
		
		// Form View and Code View tabs
		var main_tabs = new CTabs();
		var form_view = main_tabs.addTab("Form View");
		var code_view = main_tabs.addTab("Code View", function(){ saveForm(false, false); }, null);
		form_view.id = "form_view";
		code_view.id = "code_view";
		main_tabs.print(con);
		
		// Form View Tab
		var appcon = new CSplitContainer();
		appcon.resizable = true;
		con_main = appcon.addPanel("*");
		con_right = appcon.addPanel("230px");
		appcon.print(form_view);
		buildBody(con_main);
		buildRight(con_right);
		
		var tb = document.getElementById("toolbar");
		var bdy = document.getElementById("bdy");
		var total_height = document.body.offsetHeight;
		con_main.style.height = (total_height - tb.offsetHeight - main_tabs.getTabHeight() - 28) + "px";
		
		// Code View Tab
		var ta = alib.dom.createElement("textarea", code_view);
		ta.onchange = function() 
		{ 
			if(testFrmXml("<doc>"+ta.value+"</doc>"))
			{
				xmlFormLayoutText = ta.value; 
				saveObject(false);
			}
		}
		ta.value = xmlFormLayoutText;
		ta.style.height = (alib.dom.getDocumentHeight()-80) + "px";
		ta.style.width = "99%";
	}
	
	function checkDefaultForm()
	{
        ajax = new CAjax('json');        
        ajax.onload = function(ret)
        {
            if(!ret['error'])
            {                            
                if(ret.length)
                {
                    for(form in ret)
                    {
                        var currentForm = ret[form];
                        
                        // Check if default form is overridden
                        if("default" == currentForm.scope && "Default" == scope)
                            deflt = "1";
                        if("mobile" == currentForm.scope && "Mobile" == scope)
                            mobile = "1";
                    }
                }
                getXmlForm();    // Get form
            }
        };
        var args = [["obj_type", g_obj_type]];
        ajax.exec("/controller/Object/getForms", args);
	}
	
    function getXmlForm()
    {
        var ajax = new CAjax();
        ajax.onload = function(root)
        {
            xmlFormLayout = root.getChildNodeByName("form");            
            xmlFormLayoutText = unescape(root.getChildNodeValByName("form_layout_text"));
            buildInterface();
        };

        var url = "/controller/Object/loadForm?obj_type="+g_obj_type;
        if(deflt != "")
            url += "&default=1";
        if(mobile != "")
        {
            if("0" == mobile)            // Default mobile form
                url += "&mobile=0";
            else
                url += "&mobile=1";
        }
        if(team_id != "")
            url += "&team_id="+team_id;
        if(user_id != "")
            url += "&user_id="+user_id;
        
        ajax.exec(url);
    }
	
	// Used to determine which form to save/delete
	function formSubtype(type)
	{
		var subcon = document.getElementById("form_subtype");
		switch(type)
		{
		case "default":
			subcon.innerHTML = "";
			deflt = type;
			mobile = null;
			team_id = null;
			user_id = null;
			break;
		case "mobile":
			subcon.innerHTML = "";
			mobile = type;
			deflt = null;
			team_id = null;
			user_id = null;
			break;
		case "team":
			subcon.innerHTML = "";
			var sel = alib.dom.createElement("select", subcon);
			sel.onchange = function() { setTeamId(this.value); }
			sel[sel.length] = new Option("Select Team", "");
			
            var userObject = new CAntObject_User(null);
            userObject.teamId = null;
            userObject.teamDropdown = sel;
            userObject.onload = function(ret)
            {
                var teamData = ret;
                delete ret['teamCount'];
                this.populateTeam(ret, ret[0].parentId);
                this.addSpacedPrefix(teamData);
            }
            userObject.loadTeam();
            
			/*var ajax = new CAjax();
			ajax.m_dm = sel;
			ajax.onload = function(root)
			{
				var num = root.getNumChildren();
				for (var j = 0; j < num; j++)
				{
					var child = root.getChildNode(j);
					var id = child.getChildNodeValByName("id");
					var name = unescape(child.getChildNodeValByName("name"));
					this.m_dm[this.m_dm.length] = new Option(name, id);					
				}
			};
			ajax.exec("/users/xml_getteams.php");*/
			break;
		case "user":
			subcon.innerHTML = "";
			var browser = new CUserBrowser();
			browser.onSelect = function(cid, name)
			{	
				user_id = cid;
				deflt = null;
				mobile = null;
				team_id = null;
			}
			browser.onCancel = function()
			{
			}
			browser.showDialog();
			break;
		case "":
			subcon.innerHTML = "";
			break;
		}
	}
	
	// Set team_id
	function setTeamId(id)
	{
		if(id != "")
		{
			team_id = id;
			deflt = null;
			mobile = null;
			user_id = null;
		}
	}
	
	// Set user_id
	function setUserId()
	{
		var browser = new CUserBrowser();
		browser.onSelect = function(cid, name)
		{
			user_id = cid;
			deflt = null;
			mobile = null;
			team_id = null;
			
			// update user name in div
			var user_con = document.getElementById("user_div");
			user_con.innerHTML = name;
		}
		browser.onCancel = function()
		{
		}
		browser.showDialog();
	}
	
	function buildBody(con)
	{		
		// Object Form Window Frame	
		var ctbl = new CContentTable("Object Form", "100%", "100%");		
		var ctbl_con = ctbl.getCon();
		ctbl_con.id = "root";
		buildMainForm(ctbl_con);
		ctbl.print(con);
	}
	
	function buildRight(con)
	{
		var main_con = alib.dom.createElement("div", con);
		
		// Available Fields Window Frame		
		var rdv1 = alib.dom.createElement("div", main_con);
		var av_fields = new CWindowFrame("Available Fields");
		var frmcon = av_fields.getCon();
		av_fields.print(rdv1);
		av_fields.setHeight("300px");
		buildAvailableFields(frmcon);
	
		// Containers Window Frame
		var rdv2 = alib.dom.createElement("div", main_con);
		alib.dom.styleSet(rdv2, "margin", "12px 0 0 0");
		var con_frame = new CWindowFrame("Containers");
		var frmcon = con_frame.getCon();
		con_frame.print(rdv2);
		buildContainers(frmcon);
	}
	
	function buildMainForm(con)
	{	
		// Toolbar for tab
		var tb = new CToolbar();
		var btn = new CButton("Add Tab", newTab, [con], "b2");
		tb.AddItem(btn.getButton(), "right");
		var btn = new CButton("Delete Tab", deleteTab, [con], "b3");
		tb.AddItem(btn.getButton(), "right");
		var btn = new CButton("Rename Tab", setOptions, [con], "b1");
		tb.AddItem(btn.getButton(), "right");
		tb.print(con);
		
		// Title, defined by obj_type
		var title = g_antObject.title;
		var lbl = alib.dom.createElement("h2", con);
		lbl.innerHTML = title;
		
		var form_con = alib.dom.createElement("div", con);
		form_con.id = "form_con";
		
		// Check if default form exists for this obj_type
		if("*" == xmlFormLayoutText)
		{
			if("Default" == scope || "Team" == scope || "User" == scope)
				newTab(con);		// Add tab with dropzone for default, team, user scope
			else
				buildTab(con);		// Just add dropzone for mobile scope
		}
		else	// Build form based on xmlFormLayout
			buildForm(form_con, xmlFormLayout);
	}
	
	function buildAvailableFields(con)
	{
		var m_div = alib.dom.createElement("div", con);
		var dv_drop = document.createElement("div");
		dv_drop.style.width = "100%";
		dv_drop.style.height = "100%";
		m_div.appendChild(dv_drop);
		
		// All Additional
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "All Additional";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "all_additional";
		dv_b.title = "All Additional";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");

		// Objectsref
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Objectsref";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "objectsref";
		dv_b.title = "Objectsref";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
		
		// Plugin
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Plugin";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "plugin";
		dv_b.title = "Plugin";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
		
		// Recurrence
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Recurrence";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "recurrence";
		dv_b.title = "Recurrence";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
		
		// Report
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Report";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "report";
		dv_b.title = "Report";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
		
		// Spacer
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Spacer";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "spacer";
		dv_b.title = "Spacer";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");

		// Available Fields
		for (var i = 0; i < g_antObject.fields.length; i++)
		{
			var field = g_antObject.fields[i];
			
			if (field.name == "account_id") // hidden
				continue;

			var dv_b = alib.dom.createElement("div", dv_drop);
			dv_b.innerHTML = field.title;
			dv_b.style.border = "1px solid";
			dv_b.style.margin = "3px";
			dv_b.style.padding = "3px";
			dv_b.style.cursor= "move";
			dv_b.id = field.name;
			dv_b.title = field.title;
			dv_b.type = field.type;
			dv_b.subtype = field.subtype;
			DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
		}
	}
	
	function buildContainers(con)
	{
		var m_div = alib.dom.createElement("div", con);
		var dv_drop = document.createElement("div");
		dv_drop.style.width = "100%";
		dv_drop.style.height = "100%";
		m_div.appendChild(dv_drop);
		
		// Row
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Row";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "row";
		dv_b.title = "Row";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");

		// Fieldset
		var dv_b = alib.dom.createElement("div", dv_drop);
		dv_b.innerHTML = "Fieldset";
		dv_b.style.border = "1px solid";
		dv_b.style.margin = "3px";
		dv_b.style.padding = "3px";
		dv_b.style.cursor= "move";
		dv_b.id = "fieldset";
		dv_b.title = "Fieldset";
		DragAndDrop.registerDragable(dv_b, null, "dz_multiOne");
	}
	
	// Build Form from UIML
	function buildForm(con, node)
	{
		var curRow = null;
		var numcols = 0;
		var curcol = 0;
		
		// First find out how many columns we are working with this at this level		
		for (var i = 0; i < node.getNumChildren(); i++)
		{
			var child = node.getChildNode(i);
			if (child.m_name == "column")
				numcols++;
		}
		if (!numcols)
			numcols = 1;
		
		// Create form elements
		for (var i = 0; i < node.getNumChildren(); i++)
		{
			var child = node.getChildNode(i);			
			
			switch (child.m_name)
			{
			case "tab":
				// Add tab to available tabs
				available_tabs[available_tabs.length] = { available:true };
				aid++;
				
				// Get tab name
				tname = child.getAttribute("name");
				tname = tname.replace("&", "&amp;");
				
				// Create tab
				var tabcon = tabs.addTab(tname);
				tabcon.oid = aid;
				tabcon.id = "tab";
				tabcon.antType = "con";
				tabcon.name = child.getAttribute("name");
				tabs.print(con);
				buildTab(tabcon);
				numTabs++;
				buildForm(tabcon.childNodes[1], child);
				break;

			case "plugin":
				var plugin = alib.dom.createElement("div");
				if(child.getAttribute("name") != "")
					plugin.name = child.getAttribute("name");
				plugin.id = "plugin";
				plugin.title = "Plugin name='" + child.getAttribute("name") + "'";
				appendXmlContainer(plugin, con);
				buildForm(plugin, child);
				break;

			case "recurrence":
				var recurrence = alib.dom.createElement("div");
				recurrence.id = "recurrence";
				recurrence.title = "Recurrence";
				appendXmlContainer(recurrence, con);
				buildForm(recurrence, child);
				break;

			case "report":
				var report = alib.dom.createElement("div");
				if(child.getAttribute("id") != "")
					report.reportid = child.getAttribute("id");
				if(child.getAttribute("filterby") != "")
					report.filterby = child.getAttribute("filterby");
				report.id = "report";
				report.title = "Report";
				appendXmlContainer(report, con);
				buildForm(report, child);
				break;

			case "fieldset":
				var fieldset = alib.dom.createElement("div");
				if(child.getAttribute("name") != "")
				{
					var fname = child.getAttribute("name");
					fname = fname.replace("&", "&amp;");
					fieldset.name = fname;
				}
				if(child.getAttribute("showif") != "")
				{
					var showif = child.getAttribute("showif");
					fieldset.showif_1 = showif.substring(0, showif.indexOf("="));
					fieldset.showif_2 = showif.substring(showif.indexOf("=")+1, showif.length);
				}
				else
				{
					fieldset.showif_1 = null;
					fieldset.showif_2 = null;
				}
				fieldset.id = "fieldset";
				fieldset.title = fieldset.name = child.getAttribute("name");
				appendXmlDropContainer(fieldset, con);
				buildForm(fieldset, child);
				break;

			case "objectsref":
				var objectsref = alib.dom.createElement("div");
				if(child.getAttribute("obj_type") != "")
					objectsref.obj_type = child.getAttribute("obj_type");
				else
					objectsref.obj_type = null;
				if(child.getAttribute("ref_field") != "")
					objectsref.ref_field = child.getAttribute("ref_field");
				else
					objectsref.ref_field = null;
				objectsref.id = "objectsref";
				objectsref.title = "Objectsref";
				appendXmlContainer(objectsref, con);
				buildForm(objectsref, child);
				break;

			case "spacer":
				var spacer = alib.dom.createElement("div");
				spacer.id = "spacer";
				spacer.title = "Spacer";
				appendXmlContainer(spacer, con);
				buildForm(spacer, child);
				break;

			case "row":
				var row = alib.dom.createElement("div");
				if(child.getAttribute("showif") != "")
				{
					var showif = child.getAttribute("showif");
					row.showif_1 = showif.substring(0, showif.indexOf("="));
					row.showif_2 = showif.substring(showif.indexOf("=")+1, showif.length);
				}
				else
				{
					row.showif_1 = null;
					row.showif_2 = null;
				}
				row.id = "row";
				row.title = "Row";
				appendXmlDropContainer(row, con);
				buildForm(row, child);
				break;

			case "column":
				if(!curRow)
				{
					var tbl = alib.dom.createElement("table", con);
					alib.dom.styleSet(tbl, "table-layout", "fixed");
					alib.dom.styleSet(tbl, "width", "100%");
					var tbody = alib.dom.createElement("tbody", tbl);
					curRow = alib.dom.createElement("tr", tbody);
					tbl.numCol = numcols;
				}
				curcol++;
				var td = alib.dom.createElement("td", curRow);
				alib.dom.styleSet(td, "vertical-align", "top");
				var column = alib.dom.createElement("div");
				var width = child.getAttribute("width");
				if(width && width != "undefined")
				{
					column.width = width;
					td.style.width = width;
				}
				if(child.getAttribute("showif") != "")
				{
					var showif = child.getAttribute("showif");
					column.showif_1 = showif.substring(0, showif.indexOf("="));
					column.showif_2 = showif.substring(showif.indexOf("=")+1, showif.length);
				}
				else
				{
					column.showif_1 = null;
					column.showif_2 = null;
				}
				column.id = "column";
				column.title = "Column";
				appendXmlDropContainer(column, td);
				buildForm(column, child);
				break;

			case "field":
				var field = alib.dom.createElement("div");
				var title = getFieldTitle(child.getAttribute("name"));
				
				// image_id special field type
				if(child.getAttribute("name") == "image_id")
				{
					if(child.getAttribute("profile_image") == "t")
					{
						field.profile_image = true;
						field.path = child.getAttribute("path");
					}
					else
					{
						field.profile_image = false;
						field.path = "";
					}
				}
				
				// fields with multiline and rich
				if(child.getAttribute("multiline"))
				{
					if(child.getAttribute("multiline") == "t")
						field.multiline = true;
					else
						field.multiline = false;
					if(child.getAttribute("rich") == "t")
						field.rich = true;
					else
						field.rich = false;
				}
				
				// all fields have hidelabel
				if(child.getAttribute("hidelabel") == "t")
					field.hidelabel = true;
				else
					field.hidelabel = false;
                    
                // Check for tooltips
                if(child.getAttribute("tooltip"))
                    field.tooltip = child.getAttribute("tooltip");
                    
				field.id = child.getAttribute("name");
				field.title = title;
				appendXmlContainer(field, con);
				buildForm(field, child);
				break;

			case "all_additional":
				var all_additional = alib.dom.createElement("div");
				all_additional.id = "all_additional";
				all_additional.title = "All Additional";
				appendXmlContainer(all_additional, con);
				buildForm(all_additional, child);
				break;
			}
		}
	}
	
	function buildTab(con)
	{
		// Create main Dropzone div
		var dv_drop = document.createElement("div");
		var main_con = alib.dom.createElement("div");
		alib.dom.styleSet(dv_drop, "margin", "3px 0 0 0");
		dv_drop.innerHTML = "<center><h2>Drag items here</h2></center>";
		dv_drop.style.width = "100%";
		dv_drop.rootdz = true;
		con.appendChild(dv_drop);
		DragAndDrop.registerDropzone(dv_drop, "dz_multiOne");
		dv_drop.onDragEnter = function(e)
		{
			alib.dom.styleSet(this, "border", "1px solid blue");
		}
		dv_drop.onDragExit = function(e)
		{
			alib.dom.styleSet(this, "border", "");
		}
		dv_drop.onDragDrop = function(e)
		{
			alib.dom.styleSet(this, "border", "");
									
			// Check which container was dropped
			if("row" == e.id)
			{
				appendDropContainer(e, main_con);
			}
			else if("fieldset" == e.id)
			{
				appendDropContainer(e, main_con);
			}
			else
			{
				appendContainer(e, main_con);
			}
		}
		dv_drop.onResort = function(e)
		{
		}

		main_con.dz_name = "dz"+group_num;
		DragAndDrop.registerDropzone(main_con, main_con.dz_name);
		DragAndDrop.registerSortable(main_con);
		con.appendChild(main_con);
	}
	
	// Append container from xml, container is not copied
	function appendXmlContainer(con, parent)
	{
		aid++;		// increment array id
	
		var m_div = alib.dom.createElement("div");
		alib.dom.styleSet(m_div, "margin", "5px 5px 5px 5px");
		m_div.style.border = "1px solid black";
		m_div.style.padding = "5px";
		m_div.style.cursor = "move";
		con.innerHTML = con.title;
		con.oid = aid;
		con.pnode = parent;
		con.antType = "field";
		parent.appendChild(m_div);
			
		// If field dropped in main dropzone, parent = null
		if(parent.id == "tab")
			DragAndDrop.registerDragableChild(null, m_div, null, parent.dz_name);
		else
			DragAndDrop.registerDragableChild(parent.parentNode, m_div, null, parent.dz_name);
			
		// Container for options and delete item
		var a_div = alib.dom.createElement("div");
		alib.dom.styleSet(a_div, "float", "right");
		if(con.id == "all_additional" || con.id == "recurrence" || con.id == "spacer")
			a_div.style.width = "23px";
		else
			a_div.style.width = "68px";
		var op = alib.dom.createElement("div");
        alib.dom.styleSet(op, "float", "right");
		alib.dom.styleSet(op, "width", "80px");
		
		// all_additional and spacer will not have options
		if(con.id != "all_additional" && con.id != "recurrence" && con.id != "spacer")
		{
			var btn = new CButton("Options", setOptions, [con], "b1 small", null, null, null, 'link');            
			op.appendChild(btn.getButton());
		}

		// Delete Item
		var btn = new CButton("X", deleteItem, [m_div, con], "b3 small", null, null, null, 'link');        
		op.appendChild(btn.getButton());
		a_div.appendChild(op);
		var s_div = alib.dom.createElement("div");
		s_div.style.height = "5px";
		
		// Append containers inside m_div
		m_div.appendChild(a_div);
		m_div.appendChild(con);
		m_div.appendChild(s_div);
	}
	
	// Append dropzone container from xml, container is not copied
	function appendXmlDropContainer(con, parent)
	{
		aid++;		// increment array id

		var m_div = alib.dom.createElement("div");
		var name_con = alib.dom.createElement("div");
		var i_con = alib.dom.createElement("div");
		alib.dom.styleSet(name_con, "margin", "0 145px 0 0");
		name_con.innerHTML = con.title;
		alib.dom.styleSet(m_div, "margin", "5px 5px 5px 5px");
		m_div.style.border = "1px solid black";
		m_div.style.padding = "5px";
		m_div.style.cursor = "move";
		con.oid = aid;
		con.pnode = parent;
		con.antType = "con";
		if(con.id == "column")
			con.dz_name = "col"+group_num;
		else
		{
			group_num++;
			con.dz_name = "dz"+group_num;
		}
		parent.appendChild(m_div);
		DragAndDrop.registerDropzone(con, con.dz_name);
		DragAndDrop.registerSortable(con);
		
		// If container dropped in main dropzone, parent = null
		if(parent.id == "tab")
			DragAndDrop.registerDragableChild(null, m_div, null, parent.dz_name);	
		else
		{
			if(con.id == "column")
				DragAndDrop.registerDragableChild(parent.parentNode.parentNode.parentNode.parentNode.parentNode, m_div, null, parent.parentNode.dz_name);
			else
				DragAndDrop.registerDragableChild(parent.parentNode, m_div, null, parent.dz_name);
		}
			
		// Container for columns dropdown, options, and delete item
		var a_div = alib.dom.createElement("div");
		alib.dom.styleSet(a_div, "float", "right");	
		a_div.style.width = "130px";
		var op = alib.dom.createElement("div");
        alib.dom.styleSet(op, "float", "right");
		alib.dom.styleSet(op, "width", "80px");
		
		// Columns Dropdown
		var dm = new CDropdownMenu();
		var dm_sub1 = dm.addEntry("1", function() { addColumn(1, con); });
		var dm_sub2 = dm.addEntry("2", function() { addColumn(2, con); });
		var dm_sub3 = dm.addEntry("3", function() { addColumn(3, con); });
		var dm_sub4 = dm.addEntry("4", function() { addColumn(4, con); });
		op.appendChild(dm.createLinkMenu("Columns"));
	
		// Options
		var btn = new CButton("Options", setOptions, [con], "b1 small", null, null, null, 'link');        
		op.appendChild(btn.getButton());        
	
		// Delete Item
		var btn = new CButton("X", deleteItem, [m_div, con], "b3 small", null, null, null, 'link');        
		op.appendChild(btn.getButton());
		a_div.appendChild(op);
		var s_div = alib.dom.createElement("div");
		s_div.style.height = "5px";
	
		// Dropzone
		i_con.innerHTML = "<center><strong>Drag items here</strong></center>";
		i_con.style.margin = "5px";
		i_con.style.padding = "5px";
		i_con.style.height = "15px";
		DragAndDrop.registerDropzone(i_con, "dz_multiOne");								
		i_con.onDragEnter = function(e)
		{
			alib.dom.styleSet(this, "border", "1px solid blue");
		}
		i_con.onDragExit = function(e)
		{
			alib.dom.styleSet(this, "border", "");
		}
		i_con.onDragDrop = function(e)
		{
			alib.dom.styleSet(this, "border", "");
			
			// Check which container was dropped
			if("row" == e.id)
			{
				appendDropContainer(e, con);
			}
			else if("fieldset" == e.id)
			{
				appendDropContainer(e, con);
			}
			else
			{
				appendContainer(e, con);
			}
		}
		i_con.onResort = function(e)
		{
		}
	
		// Append containers inside m_div
		m_div.appendChild(a_div);
		m_div.appendChild(name_con);
		m_div.appendChild(s_div);
		m_div.appendChild(i_con);
		m_div.appendChild(con);
	}
	
	function appendContainer(child, parent)
	{
		aid++;		// increment array id
		
		// m_div main container, i_con copy of container to append
		var m_div = alib.dom.createElement("div");
		var i_con = alib.dom.createElement("div");
		alib.dom.styleSet(m_div, "margin", "5px 5px 5px 5px");
		alib.dom.styleSet(i_con, "margin", "0 80px 0 0");
		m_div.style.border = "1px solid black";
		m_div.style.padding = "5px";
		m_div.style.cursor = "move";
		i_con.innerHTML = child.title;
		i_con.id = child.id;
		i_con.oid = aid;
		i_con.pnode = parent;
		i_con.title = child.title;
		i_con.antType = "field";
		parent.appendChild(m_div);
			
		// If field dropped in main dropzone, parent = null
		if(parent.id == "tab")
			DragAndDrop.registerDragableChild(null, m_div, null, parent.dz_name);
		else
			DragAndDrop.registerDragableChild(parent.parentNode, m_div, null, parent.dz_name);
		
		// Container for options and delete item
		var a_div = alib.dom.createElement("div");
		alib.dom.styleSet(a_div, "float", "right");
		if(child.id == "all_additional" || child.id == "recurrence" || child.id == "spacer")
			a_div.style.width = "23px";
		else
			a_div.style.width = "68px";
		var op = alib.dom.createElement("div");
        alib.dom.styleSet(op, "float", "right");
		alib.dom.styleSet(op, "width", "80px");
		
		// all_additional  and spacer will not have options
		if(child.id != "all_additional" && child.id != "recurrence" && child.id != "spacer")
		{
			var btn = new CButton("Options", setOptions, [i_con], "b1 small");            
			op.appendChild(btn.getButton());
			if(child.id == "objectsref")
			{
				i_con.obj_type = null;
				i_con.ref_field = null;
			}
			else if(child.id == "report")
			{
				i_con.reportid = "";
				i_con.filterby = "";
			}
			else if(child.id == "plugin")
				i_con.name = "";
			else if(child.id == "image_id")
				i_con.path = "";
			// Fields
			else
			{
				i_con.hidelabel = false;
				if(child.type == "text" && child.subtype == "")
				{
					i_con.multiline = false;
					i_con.rich = false;
				}
			}
		}
		
		// Delete Item
		var btn = new CButton("X", deleteItem, [m_div, i_con], "b3 small");
		op.appendChild(btn.getButton());
		a_div.appendChild(op);
		var s_div = alib.dom.createElement("div");
		s_div.style.height = "5px";        

		// Append containers inside m_div
		m_div.appendChild(a_div);
		m_div.appendChild(i_con);
		m_div.appendChild(s_div);
	}
	
	function appendDropContainer(child, parent)
	{
		aid++;		// increment array id
				
		// m_div main container, t_con area to append container, i_con dropzone container
		var m_div = alib.dom.createElement("div");
		var name_con = alib.dom.createElement("div");
		var t_con = alib.dom.createElement("div");
		var i_con = alib.dom.createElement("div");
		alib.dom.styleSet(m_div, "margin", "5px 5px 5px 5px");
		alib.dom.styleSet(name_con, "margin", "0 145px 0 0");
		name_con.innerHTML = child.title;
		m_div.style.border = "1px solid black";
		m_div.style.padding = "5px";
		m_div.style.cursor= "move";
		t_con.id = child.id;
		t_con.oid = aid;
		t_con.pnode = parent;
		t_con.antType = "con";
		t_con.showif_1 = null;
		t_con.showif_2 = null;
		if(child.id == "column")
		{
			t_con.width = child.width;
			t_con.dz_name = "col"+group_num;
		}
		else
		{
			group_num++;
			t_con.dz_name = "dz"+group_num;
			
			if(child.id == "fieldset")
				t_con.name = "";
		}
		parent.appendChild(m_div);
		DragAndDrop.registerDropzone(t_con, t_con.dz_name);
		DragAndDrop.registerSortable(t_con);
		
		// If container dropped in main dropzone, parent = null
		if(parent.id == "tab")
			DragAndDrop.registerDragableChild(null, m_div, null, parent.dz_name);	
		else
		{
			if(child.id == "column")
				DragAndDrop.registerDragableChild(parent.parentNode.parentNode.parentNode.parentNode.parentNode, m_div, null, parent.parentNode.dz_name);
			else
				DragAndDrop.registerDragableChild(parent.parentNode, m_div, null, parent.dz_name);
		}
		
		// Container for columns dropdown, options, and delete item
		var a_div = alib.dom.createElement("div");
		alib.dom.styleSet(a_div, "float", "right");	
		a_div.style.width = "130px";
		var op = alib.dom.createElement("div");
        alib.dom.styleSet(op, "float", "right");
		alib.dom.styleSet(op, "width", "80px");
		
		// Columns Dropdown
		var dm = new CDropdownMenu();
		var dm_sub1 = dm.addEntry("1", function() { addColumn(1, t_con); });
		var dm_sub2 = dm.addEntry("2", function() { addColumn(2, t_con); });
		var dm_sub3 = dm.addEntry("3", function() { addColumn(3, t_con); });
		var dm_sub4 = dm.addEntry("4", function() { addColumn(4, t_con); });
		op.appendChild(dm.createLinkMenu("Columns"));

		// Options
		var btn = new CButton("Options", setOptions, [t_con], "b1 small");        
		op.appendChild(btn.getButton());
		
		// Delete Item
		var btn = new CButton("X", deleteItem, [m_div, t_con], "b3 small");        
		op.appendChild(btn.getButton());
		a_div.appendChild(op);
		var s_div = alib.dom.createElement("div");
		s_div.style.height = "5px";
		
		// Dropzone
		i_con.innerHTML = "<center><strong>Drag items here</strong></center>";
		i_con.style.margin = "5px";
		i_con.style.padding = "5px";
		i_con.style.height = "15px";
		DragAndDrop.registerDropzone(i_con, "dz_multiOne");								
		i_con.onDragEnter = function(e)
		{
			alib.dom.styleSet(this, "border", "1px solid blue");
		}
		i_con.onDragExit = function(e)
		{
			alib.dom.styleSet(this, "border", "");
		}
		i_con.onDragDrop = function(e)
		{
			alib.dom.styleSet(this, "border", "");
			
			// Check which container was dropped
			if("row" == e.id)
			{
				appendDropContainer(e, t_con);
			}
			else if("fieldset" == e.id)
			{
				appendDropContainer(e, t_con);
			}
			else
			{
				appendContainer(e, t_con);
			}
		}
		i_con.onResort = function(e)
		{
		}
		
		// Append containers inside m_div
		m_div.appendChild(a_div);
		m_div.appendChild(name_con);
		m_div.appendChild(s_div);
		m_div.appendChild(i_con);
		m_div.appendChild(t_con);
	}
	
	function addColumn(num, con)
	{
		var table = alib.dom.createElement("table", con);
		alib.dom.styleSet(table, "table-layout", "fixed");
		var tableBody = alib.dom.createElement("tbody", table);
		var tr = alib.dom.createElement("tr", tableBody);
		table.style.width = "100%";
		table.numCol = num;
		
		// Add the columns to the table
		for(var i = 0; i < num; i++)
		{
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSet(td, "vertical-align", "top");
			var dv_b = alib.dom.createElement("div");
			dv_b.id = "column";
			dv_b.title = "Column";
			dv_b.width = 100/num + "%";
			appendDropContainer(dv_b, td);
		}
		tr.appendChild(td);
		table.appendChild(tableBody);
		
		// Restore all parent containers to dragable
		var p = con.parentNode.parent;
		while(p)
		{
			p.dragable = true;
			p = p.parent;
		}
	}
	
	function deleteItem(mcon, con)
	{
		// Remove column from table
		if("column" == con.id)
		{
			con.conState = false;	
			mcon.parentNode.style.display = "none";
			mcon.parentNode.parentNode.parentNode.parentNode.numCol--;
			
			// Restore all parent containers to dragable
			var p = mcon.parent;
			while(p)
			{
				p.dragable = true;
				p = p.parent;
			}

			// Set the width field of the remaining columns
			// TODO: Columns that have their width set and later get scaled don't get an updated width. 
			if(mcon.parentNode.parentNode.parentNode.parentNode.numCol > 0)
			{
				for(var i = 0; i < con.parentNode.parentNode.parentNode.childNodes.length; i++)
				{
					con.parentNode.parentNode.parentNode.childNodes[i].childNodes[0].childNodes[4].width = alib.dom.styleGet(con.parentNode.parentNode.parentNode.childNodes[i], "width");
				}
			}
			// Remove the table when its empty
			else
				mcon.parentNode.parentNode.parentNode.parentNode.style.display = "none";
		}
		// Remove item
		else
		{
			con.conState = false;
			mcon.style.display = "none";

			// Restore all parent containers to dragable
			var p = mcon.parent;
			while(p)
			{
				p.dragable = true;
				p = p.parent;
			}
		}
	}
	
	function newTab(con)
	{
		aid++;
		available_tabs[available_tabs.length] = { available:true };

		// Add New Tab
		var tab = tabs.addTab("New Tab");
		tab.oid = aid;
		tab.id = "tab";
		tab.antType = "con";
		tab.name = "New Tab";
		buildTab(tab);
		tabs.print(con);
		numTabs++;
				
		// Select the new tab
		if(tabs.getNumTabs() > 1)
			tabs.selectTab(numTabs-1);
	}
	
	function deleteTab(con)
	{
		var index = tabs.getIndex();
		ALib.Dlg.confirmBox("Are you sure you want to delete this tab?", "Delete Tab");
		ALib.Dlg.onConfirmOk = function()
		{	
			tabs.getPageCon(tabs.getIndex()).conState = false;
			available_tabs[index].available = false;
			tabs.deleteTab(index);

			// Create a new tab if last tab left was deleted
			if(tabs.getNumTabs() == 0)
			{
				newTab(con);
				tabs.selectTab(numTabs-1);
			}
			else
			{
				// Select first available tab
				for(var i = 0; i < available_tabs.length; i++)
				{
					if(available_tabs[i].available == true)
					{
						tabs.selectTab(i);
						break;
					}
				}
			}
		}
	}
	
	function setOptions(con)
	{
		// Restore all parent containers to dragable
		var p = con.parentNode.parent;
		while(p)
		{
			p.dragable = true;
			p = p.parent;
		}

		switch(con.id)
		{
		// Options for tab
		case "root":
			// Custom options dialog
			var dlg_d = new CDialog("Tab Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// Tab Name
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", cont);
			inp_dv.innerHTML = "<strong>Name: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input", td);
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = tabs.getPageCon(tabs.getIndex()).name;
			td.appendChild(inp1);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{ 
				if(inp1.value != "")
				{
					var tname = inp1.value;
					tabs.setTabTitle(tabs.getIndex(), tname); 
					tabs.getPageCon(tabs.getIndex()).name = tname;
				}
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 238, 48);
			break;
			
		// Options for fieldset
		case "fieldset":
			// Custom options dialog
			var dlg_d = new CDialog("Fieldset Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// Fieldset Name
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>Name: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = con.name;
			td.appendChild(inp1);
			tr.appendChild(td);
			table.appendChild(tableBody);				

			// showif='fieldname=value'
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>showif: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.setAttribute('maxLength', 128);
			inp2.type = "text";
			inp2.style.width = "80px";
			inp2.value = con.showif_1;
			td.appendChild(inp2);
			var td = alib.dom.createElement("td", tr);
			var eq = alib.dom.createElement("div");
			eq.innerHTML = "<strong> = </strong>";
			td.appendChild(eq);
			var td = alib.dom.createElement("td", tr);
			var inp3 = alib.dom.createElement("input");
			inp3.setAttribute('maxLength', 128);
			inp3.type = "text";
			inp3.style.width = "80px";
			inp3.value = con.showif_2;
			td.appendChild(inp3);
			tr.appendChild(td);			
			table.appendChild(tableBody);				
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function()
			{ 
				if(inp1.value != "")
				{
					var fname = inp1.value;
					con.name = fname;
					con.parentNode.childNodes[1].innerHTML = fname;
				}
				if(inp2.value != "" && inp3.value != "")
				{
					con.showif_1 = inp2.value;
					con.showif_2 = inp3.value;
				}
				dlg_d.hide();
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 260, 78);
			break;
			
		// Options for row
		case "row":
			// Custom options dialog
			var dlg_d = new CDialog("Row Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// showif='fieldname=value'
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>showif: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "80px";
			inp1.value = con.showif_1;
			td.appendChild(inp1);
			var td = alib.dom.createElement("td", tr);
			var eq = alib.dom.createElement("div");
			eq.innerHTML = "<strong> = </strong>";
			td.appendChild(eq);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.setAttribute('maxLength', 128);
			inp2.type = "text";
			inp2.style.width = "80px";
			inp2.value = con.showif_2;
			td.appendChild(inp2);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				if(inp1.value != "" && inp2.value != "")
				{
					con.showif_1 = inp1.value;
					con.showif_2 = inp2.value;
				}
				dlg_d.hide();
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 260, 48);
			break;
		
		// Options for column
		// TODO: Limit user input for values such as 125%, 1500px, etc. Also combined width should not exceed 100%
		case "column":
			// Custom options dialog
			var dlg_d = new CDialog("Column Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// Column Width
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>Width: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = alib.dom.styleGet(con.parentNode.parentNode, "width");
			td.appendChild(inp1);
			tr.appendChild(td);
			table.appendChild(tableBody);				
			
			// showif='fieldname=value'
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>showif: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.setAttribute('maxLength', 128);
			inp2.type = "text";
			inp2.style.width = "80px";
			inp2.value = con.showif_1;
			td.appendChild(inp2);
			var td = alib.dom.createElement("td", tr);
			var eq = alib.dom.createElement("div");
			eq.innerHTML = "<strong> = </strong>";
			td.appendChild(eq);
			var td = alib.dom.createElement("td", tr);
			var inp3 = alib.dom.createElement("input");
			inp3.setAttribute('maxLength', 128);
			inp3.type = "text";
			inp3.style.width = "80px";
			inp3.value = con.showif_2;
			td.appendChild(inp3);
			tr.appendChild(td);			
			table.appendChild(tableBody);
						
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				if(inp1.value != "")
				{
					if(con.parentNode.parentNode.parentNode.parentNode.parentNode.numCol != 1)
					{
						con.parentNode.parentNode.style.width = inp1.value;
						con.width = inp1.value;
										
						for(var i = 0; i < con.parentNode.parentNode.parentNode.childNodes.length; i++)
						{
							// Set the width field of the columns that were scaled
							if(con.parentNode.parentNode.parentNode.childNodes[i].width != inp1.value)
							{
								con.parentNode.parentNode.parentNode.childNodes[i].childNodes[0].childNodes[4].width = alib.dom.styleGet(con.parentNode.parentNode.parentNode.childNodes[i], "width");
							}
						}
					}
				}
				if(inp2.value != "" && inp3.value != "")
				{
					con.showif_1 = inp2.value;
					con.showif_2 = inp3.value;
				}
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 260, 78);
			break;
		
		// Options for objectsref
		case "objectsref":
			// Custom options dialog
			var dlg_d = new CDialog("Objectsref Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// obj_type
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>obj_type: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = con.obj_type;
			td.appendChild(inp1);
			tr.appendChild(td);
			
			// ref_field
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>ref_field: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.setAttribute('maxLength', 128);
			inp2.type = "text";
			inp2.style.width = "160px";
			inp2.value = con.ref_field;
			td.appendChild(inp2);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				// obj_type
				if(inp1.value != "" && inp2.value == "")
					con.obj_type = inp1.value;
				
				// obj_type && ref_field
				if(inp1.value != "" && inp2.value != "")
				{
					con.obj_type = inp1.value;
					con.ref_field = inp2.value;
				}
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 248, 72);
			break;
		
		// Options for plugin
		case "plugin":
			// Custom options dialog
			var dlg_d = new CDialog("Plugin Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// Plugin Name
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", cont);
			inp_dv.innerHTML = "<strong>Name: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input", td);
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = con.name;
			td.appendChild(inp1);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				if(inp1.value != "")
				{
					con.name = inp1.value;
					con.innerHTML = "Plugin name='" + inp1.value + "'";
				}
				else
				{
					con.name = "";
					con.innerHTML = "Plugin";
				}
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 238, 48);
			break;
		
		// Options for report
		case "report":
			// Custom options dialog
			var dlg_d = new CDialog("Report Options");
			var cont = alib.dom.createElement("div");
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// id
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>id: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.setAttribute('maxLength', 128);
			inp1.type = "text";
			inp1.style.width = "160px";
			inp1.value = con.reportid;
			td.appendChild(inp1);
			tr.appendChild(td);
			
			// filterby
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>filterby: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.setAttribute('maxLength', 128);
			inp2.type = "text";
			inp2.style.width = "160px";
			inp2.value = con.filterby;
			td.appendChild(inp2);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				// obj_type
				if(inp1.value != "")
					con.reportid = inp1.value;
				
				// obj_type && ref_field
				if(inp2.value != "")
					con.filterby = inp2.value;
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 248, 72);
			break;
		
		// Options for image
		case "image_id":
			// Custom options dialog
			var dlg_d = new CDialog(con.title + " Options");
			var cont = alib.dom.createElement("div");			
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			
			// Hidelabel
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp1 = alib.dom.createElement("input");
			inp1.type = "checkbox";
			inp1.checked = con.hidelabel;
			td.appendChild(inp1);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>hidelabel </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			
			// Profile image
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.type = "checkbox";
			inp2.checked = con.profile_image;
			td.appendChild(inp2);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>profile_image </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", cont);
			inp_dv.innerHTML = "<strong>Path: </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			var td = alib.dom.createElement("td", tr);
			var inp3 = alib.dom.createElement("input", td);
			inp3.setAttribute('maxLength', 128);
			inp3.type = "text";
			inp3.style.width = "160px";
			inp3.value = con.path;
			td.appendChild(inp3);
			tr.appendChild(td);
			table.appendChild(tableBody);
			
			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				if(inp1.checked == true)
					con.hidelabel = true;
				else
					con.hidelabel = false;

				if(inp2.checked == true && inp3 != "")
				{
					con.profile_image = true;
					con.path = inp3.value;
				}
				else
				{
					con.profile_image = false;
					con.path = "";
				}
				dlg_d.hide(); 
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, 228, 72);
			break;
			
		// No options for all_additional
		case "all_additional":
			break;
		
		// No options for recurrence
		case "recurrence":
			break;
		
		// No options for spacer
		case "spacer":
			break;
		
		// Options for fields
		default:		
			// Custom options dialog
			var dlg_d = new CDialog(con.title + " Options");
			var cont = alib.dom.createElement("div");			
			var table = alib.dom.createElement("table", cont);
			var tableBody = alib.dom.createElement("tbody", table);
			var dlg_height = 100;
			var dlg_width = 500;
			
			// Only for field type == 'text' && subtype == ''
			if(con.multiline != null && con.rich != null)
			{
				dlg_height = 68;
				dlg_width = 168;
				
				// Multiline
				var tr = alib.dom.createElement("tr", tableBody);
				var td = alib.dom.createElement("td", tr);
				var inp3 = alib.dom.createElement("input");
				inp3.type = "checkbox";
				inp3.checked = con.multiline;
				td.appendChild(inp3);
				var td = alib.dom.createElement("td", tr);
				var inp_dv = alib.dom.createElement("div", td);
				inp_dv.innerHTML = "<strong>multiline </strong>";
				inp_dv.style.padding = "3px";
				td.appendChild(inp_dv);

				// Rich
				var td = alib.dom.createElement("td", tr);
				var inp4 = alib.dom.createElement("input");
				inp4.type = "checkbox";
				inp4.checked = con.rich;
				td.appendChild(inp4);
				var td = alib.dom.createElement("td", tr);
				var inp_dv = alib.dom.createElement("div", td);
				inp_dv.innerHTML = "<strong>rich </strong>";
				inp_dv.style.padding = "3px";
				td.appendChild(inp_dv);
				tr.appendChild(td);	
			}
			
			// Hidelabel
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var inp2 = alib.dom.createElement("input");
			inp2.type = "checkbox";
			inp2.checked = con.hidelabel;
			td.appendChild(inp2);
			var td = alib.dom.createElement("td", tr);
			var inp_dv = alib.dom.createElement("div", td);
			inp_dv.innerHTML = "<strong>hidelabel </strong>";
			inp_dv.style.padding = "3px";
			td.appendChild(inp_dv);
			tr.appendChild(td);			
			table.appendChild(tableBody);
            
            // Tooltip
            var tooltipCon = alib.dom.createElement("div", cont);
            alib.dom.styleSet(tooltipCon, "margin-bottom", "5px");
            
            var tooltipValue = null;
            
            if(con.tooltip)
                tooltipValue = con.tooltip;
            
            alib.dom.setElementAttr(alib.dom.createElement("label", tooltipCon), [["innerHTML", "Tooltip: "]]);
            var tooltipInput = alib.dom.setElementAttr(alib.dom.createElement("input", tooltipCon), [["type", "text"], ["value", tooltipValue], ["width", "445px"]]);

			var btn_con = alib.dom.createElement("div");
			alib.dom.styleSet(btn_con, "float", "right");
			var btn = new CButton("Ok", 
			function() 
			{
				if(inp2 != null)
				{
					if(inp2.checked == true)
						con.hidelabel = true;
					else
						con.hidelabel = false;
				}
				if(inp3 != null)
				{
					if(inp3.checked == true)
						con.multiline = true;
					else
						con.multiline = false;
				}
				if(inp4 != null)
				{
					if(inp4.checked == true)
						con.rich = true;
					else
						con.rich = false;
				}
                
                con.tooltip = tooltipInput.value;
				dlg_d.hide(); 
                
			}, null, "b1");
			btn_con.appendChild(btn.getButton());
			var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
			btn_con.appendChild(btn.getButton());
			cont.appendChild(btn_con);
			dlg_d.customDialog(cont, dlg_width, dlg_height);
			break;
		}
	}
	
	function build(con)
	{
		for(var node = 0; node < con.childNodes.length; node++)
		{
			// If no antType but children, get children
			if(con.childNodes[node].antType == null && con.childNodes[node].childNodes != null)
			{
				build(con.childNodes[node]);
			}
			else
			{				
				// Only check containers with antType
				switch(con.childNodes[node].antType)
				{
				case "con":
					// Check if container was deleted
					if(con.childNodes[node].conState == false)
					{
						break;
					}						
					buildFormArray(con.childNodes[node]);
					build(con.childNodes[node]);
					break;
				case "field":
					// Check if field was deleted
					if(con.childNodes[node].conState == false)
					{
						break;
					}
					buildFormArray(con.childNodes[node]);
					break;
				default:
				}
			}
		}
	}
	
	function buildFormArray(con)
	{		
		// Tabs are children of root
		if(con.id == "tab")
		{
			form_tree.children[form_tree.children.length] = { arrayId:con.oid, name:con.name, type:con.id, children:[] };
		}
		else
		{
			// Columns have different parents than other containers
			if(con.id == "column")
			{
				if(con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid)
					addArrayItem(form_tree, con, con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid);
				else
					addArrayItem(form_tree, con, con.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.oid);
			}
			else
			{
				// If child of dropzone container, else child of tab
				if(con.pnode.oid)
					addArrayItem(form_tree, con, con.parentNode.parentNode.oid);
				else
				{
					// If child of tab, else root element (outside of tabs)
					if(con.parentNode.parentNode.parentNode.oid)
						addArrayItem(form_tree, con, con.parentNode.parentNode.parentNode.oid);
					else
					{
						if(con.id == "column")
						{
							if(con.showif_1 != null && con.showif_2 != null)
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, width:con.width, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
							else
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, width:con.width, showif:null, type:con.id, children:[] };	
						}
						else if(con.id == "fieldset")
						{
							if(con.showif_1 != null && con.showif_2 != null)
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, name:con.name, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
							else
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, name:con.name, showif:null, type:con.id, children:[] };	
						}
						else if(con.id == "plugin")
						{
							form_tree.children[form_tree.children.length] = { arrayId:con.oid, name:con.name, type:con.id, children:null };
						}
						else	// con.id == "row"
						{
							if(con.showif_1 != null && con.showif_2 != null)
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
							else
								form_tree.children[form_tree.children.length] = { arrayId:con.oid, showif:null, type:con.id, children:[] };
						}
					}
				}
			}
		}
	}
	
	function addArrayItem(childNodes, con, id)
	{
		for(var node = 0; node < childNodes.children.length; node++)
		{
			// Check if this node is the parent
			if(childNodes.children[node].arrayId == id)
			{
				// Attach container
				if(con.antType == "con")
				{
					if(con.id == "column")
					{
						if(con.showif_1 != null && con.showif_2 != null)
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, width:con.width, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
						else
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, width:con.width, showif:null, type:con.id, children:[] };	
					}
					else if(con.id == "fieldset")
					{
						if(con.showif_1 != null && con.showif_2 != null)
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, name:con.name, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
						else
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, name:con.name, showif:null, type:con.id, children:[] };	
					}
					else	// con.id == "row"
					{
						if(con.showif_1 != null && con.showif_2 != null)
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, showif:con.showif_1+"="+con.showif_2, type:con.id, children:[] };
						else
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, showif:null, type:con.id, children:[] };
					}
				}
				// Attach field
				else
				{
					if(con.id == "objectsref")
					{
						if(con.ref_field != null)
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, obj_type:con.obj_type, ref_field:con.ref_field, type:con.id, children:null };
						else
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, obj_type:con.obj_type, type:con.id, children:null };
					}
					else if(con.id == "all_additional")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, type:con.id, children:null };
					}
					else if(con.id == "recurrence")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, type:con.id, children:null };
					}
					else if(con.id == "spacer")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, type:con.id, children:null };
					}
					else if(con.id == "plugin")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, name:con.name, type:con.id, children:null };
					}
					else if(con.id == "report")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, reportid:con.reportid, filterby:con.filterby, type:con.id, children:null };
					}
					else if(con.id == "image_id")
					{
						childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, hidelabel:con.hidelabel, profileimage:con.profile_image, path:con.path, type:con.id, children:null };
					}
					// Fields
					else
					{
						if(con.multiline != null && con.rich != null)
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, hidelabel:con.hidelabel, tooltip:con.tooltip, multiline:con.multiline, rich:con.rich, type:con.id, children:null };
						else
							childNodes.children[node].children[childNodes.children[node].children.length] = { arrayId:con.oid, hidelabel:con.hidelabel, tooltip:con.tooltip, type:con.id, children:null };
					}
				}
			}
			else
			{
				// Keep searching for parent node
				if(childNodes.children[node].children != null)
				{
					addArrayItem(childNodes.children[node], con, id);
				}
			}
		}
	}
	
	function generateUIML(childNodes, space)
	{
		var s = tabSpace(space);
		
		// Loop through array and generate UIML code
		for(var node = 0; node < childNodes.children.length; node++)
		{
			// Tab UIML
			if(childNodes.children[node].type == "tab")
			{
				var name = childNodes.children[node].name;
				name = name.replace("&", "&amp;");
				xmlFormLayoutText += "<tab name='" + name + "'>\n";
				if(childNodes.children[node].children != null)
				{
					generateUIML(childNodes.children[node], space+1);
					xmlFormLayoutText += "</tab>\n";
				}
				else
				{
					xmlFormLayoutText += "</tab>\n";
				}
			}
			// Column UIML
			else if(childNodes.children[node].type == "column")
			{
				var width = "";
				if(childNodes.children[node].width != null)
					width = " width='" + childNodes.children[node].width + "'";	

				var showif = "";
				if(childNodes.children[node].showif != null)
					showif = " showif='" + childNodes.children[node].showif + "'";
				
				xmlFormLayoutText += s + "<column" + width + showif + ">\n";
				if(childNodes.children[node].children != null)
				{
					generateUIML(childNodes.children[node], space+1);
					xmlFormLayoutText += s + "</column>\n";
				}
				else
					xmlFormLayoutText += s + "</column>\n";
			}
			// Row UIML
			else if(childNodes.children[node].type == "row")
			{
				if(childNodes.children[node].showif != null)
				{
					xmlFormLayoutText += s + "<row showif='" + childNodes.children[node].showif + "'>\n";
					if(childNodes.children[node].children != null)
					{
						generateUIML(childNodes.children[node], space+1);
						xmlFormLayoutText += s + "</row>\n";
					}
					else
						xmlFormLayoutText += s + "</row>\n";
				}
				else
				{
					xmlFormLayoutText += s + "<row>\n";
					if(childNodes.children[node].children != null)
					{
						generateUIML(childNodes.children[node], space+1);
						xmlFormLayoutText += s + "</row>\n";
					}
					else
						xmlFormLayoutText += s + "</row>\n";
				}
			}
			// Fieldset UIML
			else if(childNodes.children[node].type == "fieldset")
			{
				if(childNodes.children[node].showif != null)
				{
					var name = childNodes.children[node].name;
					name = name.replace("&", "&amp;");
					xmlFormLayoutText += s + "<fieldset name='" + name + "' showif='" + childNodes.children[node].showif + "'>\n";
					if(childNodes.children[node].children != null)
					{
						generateUIML(childNodes.children[node], space+1);
						xmlFormLayoutText += s + "</fieldset>\n";
					}
					else
						xmlFormLayoutText += s + "</fieldset>\n";			
				}
				else
				{
					xmlFormLayoutText += s + "<fieldset name='" + childNodes.children[node].name.replace("&", "&amp;") + "'>\n";
					if(childNodes.children[node].children != null)
					{
						generateUIML(childNodes.children[node], space+1);
						xmlFormLayoutText += s + "</fieldset>\n";
					}
					else
						xmlFormLayoutText += s + "</fieldset>\n";
				}
			}
			// Objectsref UIML
			else if(childNodes.children[node].type == "objectsref")
			{
				if(childNodes.children[node].ref_field != null)
				{
					xmlFormLayoutText += s + "<objectsref obj_type='" + childNodes.children[node].obj_type + "' ref_field='" + childNodes.children[node].ref_field +"'>";
					xmlFormLayoutText += "</objectsref>\n";
				}
				else
				{
					xmlFormLayoutText += s + "<objectsref obj_type='" + childNodes.children[node].obj_type + "'>";
					xmlFormLayoutText += "</objectsref>\n";
				}
			}
			// Report UIML
			else if(childNodes.children[node].type == "report")
			{
				xmlFormLayoutText += s + "<report id='" + childNodes.children[node].reportid + "' filterby='" + childNodes.children[node].filterby + "'>";
				xmlFormLayoutText += "</report>\n";
			}
			// All additional UIML
			else if(childNodes.children[node].type == "all_additional")
			{
				xmlFormLayoutText += s + "<all_additional></all_additional>\n";
			}
			// Recurrence UIML
			else if(childNodes.children[node].type == "recurrence")
			{
				xmlFormLayoutText += s + "<recurrence></recurrence>\n";
			}
			// Spacer UIML
			else if(childNodes.children[node].type == "spacer")
			{
				xmlFormLayoutText += s + "<spacer></spacer>\n";
			}
			// Plugin UIML
			else if(childNodes.children[node].type == "plugin")
			{
				xmlFormLayoutText += s + "<plugin name='" + childNodes.children[node].name + "'>" + "</plugin>\n";
			}
			// Image UIML
			else if(childNodes.children[node].type == "image_id")
			{
				xmlFormLayoutText += s + "<field name='" + childNodes.children[node].type + "' ";

				if(childNodes.children[node].hidelabel == true)
					xmlFormLayoutText += "hidelabel='t'";
				else
					xmlFormLayoutText += "hidelabel='f'";
				if(childNodes.children[node].profileimage == true)
					xmlFormLayoutText += " profile_image='t' path='" + childNodes.children[node].path + "'>";
				else
					xmlFormLayoutText += ">";
				xmlFormLayoutText += "</field>\n";
			}
			// Field UIML
			else
			{
				xmlFormLayoutText += s + "<field name='" + childNodes.children[node].type + "' ";
                
                if(childNodes.children[node].tooltip)
                {
                    xmlFormLayoutText += "tooltip='" + childNodes.children[node].tooltip + "' ";
                }
				if(childNodes.children[node].multiline != null)
				{
					if(childNodes.children[node].multiline == true)
						xmlFormLayoutText += "multiline='t' ";
					else
						xmlFormLayoutText += "multiline='f' ";
				}
				if(childNodes.children[node].rich != null)
				{
					if(childNodes.children[node].rich == true)
						xmlFormLayoutText += "rich='t' ";
					else
						xmlFormLayoutText += "rich='f' ";
				}                
				if(childNodes.children[node].hidelabel != null)
				{
					if(childNodes.children[node].hidelabel == true)
						xmlFormLayoutText += "hidelabel='t'>";
					else
						xmlFormLayoutText += "hidelabel='f'>";					
				}
				xmlFormLayoutText += "</field>\n";
			}
		}
	}
	
	function printUIML()
	{
		var con = document.getElementById("code_view");
		con.childNodes[0].value = xmlFormLayoutText;
	}
	
	function getFieldTitle(fname)
	{
		for (var i = 0; i < g_antObject.fields.length; i++)
		{
			var field = g_antObject.fields[i];
			if (field.name == fname)
				return field.title;
		}	
	}
	
	function tabSpace(num)
	{
		var space = "";
		for(var i = 0; i < num; i++)
		{
			space += "\t";
		}
		return space;
	}
	
	function testFrmXml(xmlString)
	{
		if (xmlString == "")
			return true;

		try
		{
			if (window.DOMParser)
			{
				var parser=new DOMParser();
				var xmlDoc=parser.parseFromString(xmlString,"text/xml");
				
			}
			else // Internet Explorer
			{
				var xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
				xmlDoc.async="false";
				xmlDoc.loadXML(xmlString);
			} 

			var errorMsg = null;
			if (xmlDoc.parseError && xmlDoc.parseError.errorCode != 0) 
			{
                errorMsg = xmlDoc.parseError.reason
                          + " at line " + xmlDoc.parseError.line
                          + " at position " + xmlDoc.parseError.linepos;
            }
			else 
			{
				if (xmlDoc.documentElement) 
				{
					if (xmlDoc.documentElement.nodeName == "parsererror") 
					{
                        errorMsg = xmlDoc.documentElement.childNodes[0].nodeValue;
                    }
                }
				else 
				{
                    errorMsg = "XML Parsing Error!";
                }
            }

			if (errorMsg) 
				throw errorMsg;
			else
				return true;
		}
		catch (e)
		{
			alert("Error detected in XML. Please correct before saving: " + e);
			return false;
		}
	}
	
	function saveForm(close, reload)
	{
		if(scope == "Team" && team_id == "" || scope == "User" && user_id == "")
		{
			if(reload)
			{
				if(scope == "Team")
					ALib.Dlg.messageBox("Please select a team before saving");
				else
					ALib.Dlg.messageBox("Please select a user before saving");
			}
		}
		else
		{
			xmlFormLayoutText = "";
			form_tree.children.splice(0, form_tree.children.length);
			build(document.getElementById("root"));
			generateUIML(form_tree, 0);
			printUIML();
			
			if(reload == true)
			{
				if(testFrmXml("<doc>"+xmlFormLayoutText+"</doc>"))
				{
					saveObject(close);
				}
			}
		}
	}
	
	function saveObject(close)
	{
		var close = (typeof close != "undefined") ? close : false;
		
		// Create loading div
		var dlg = new CDialog();
		var dv_load = document.createElement('div');
		alib.dom.styleSetClass(dv_load, "statusAlert");
		alib.dom.styleSet(dv_load, "text-align", "center");
		dv_load.innerHTML = "Saving, please wait...";
		dlg.statusDialog(dv_load, 150, 100);
		
        //ALib.m_debug = true;
		//AJAX_TRACE_RESPONSE = true;
		if(scope == "Default")
			deflt = "default";
		if(scope == "Mobile")
			mobile = "mobile";

        ajax = new CAjax('json');        
        ajax.onload = function(ret)
        {
            if(ret)
                saveDone(dlg, close);
            else
            {
                dlg.hide();
                ALib.statusShowAlert("ERROR SAVING CHANGES!", 3000, "bottom", "right");
            }
        };
        var args = [["obj_type", g_obj_type], ["form_layout_xml", xmlFormLayoutText], ["default", deflt], ["mobile", mobile], ["team_id", team_id], ["user_id", user_id]];
        ajax.exec("/controller/Object/saveForm", args);
	}
	
	function saveDone(dlg, close)
	{
		var close = (typeof close != "undefined") ? close : false;

		dlg.hide();
		ALib.statusShowAlert(g_antObject.title + " Saved!", 3000, "bottom", "right");

		// Now check for opener callback
		if (window.opener)
		{
			updateParentObjectCache();
		<?php
			if ($OPENER_ONSAVE)
		 		echo "window.opener.$OPENER_ONSAVE;";
		?>
		}

		if(close)
			window.close();
		else
		{			
			// Load the current page being saved in same window
			var form_url = "/objects/form_editor.php?obj_type="+g_obj_type;
			if(deflt != "" && deflt != null)
				form_url += "&scope=Default&default=1";
			if(mobile != "" && mobile != null)
				form_url += "&scope=Mobile&mobile=1";
			if(team_id != "" && team_id != null)
				form_url += "&scope=Team&team_id="+team_id;
			if(user_id != "" && user_id != null)
				form_url += "&scope=User&user_id="+user_id;
			window.open(form_url, "_self");
		}
	}

	function updateParentObjectCache()
	{
		if (window.opener)
		{
			try
			{
				/*
				// Clear field cache
				if (window.opener.objectPreloadDef)
					window.opener.objectPreloadDef(g_obj_type, true);
				*/
			}
			catch(e){}
		}
	}

	function deleteObject()
	{
		if(scope == "Team" && team_id == "" || scope == "User" && user_id == "")
		{
			if(scope == "Team")
				ALib.Dlg.messageBox("Please select a team before deleting");
			else
				ALib.Dlg.messageBox("Please select a user before deleting");
		}
		else
		{
			if((scope == "Default" && deflt == "") || (scope == "Mobile" && mobile == "0"))
			{
				ALib.Dlg.messageBox("ERROR: Form cannot be deleted");
			}
			else
			{
				ALib.Dlg.confirmBox("Are you sure you want to delete this form?", "Delete Form");
				ALib.Dlg.onConfirmOk = function()
				{
					/*function cbdone(ret)
					{
						if(ret)
						{
							window.close();
						}
						else
						{
							dlg.hide();
							ALib.statusShowAlert("ERROR DELETING FORM!", 3000, "bottom", "right");
						}
					}					
					var args = [["obj_type", g_obj_type], ["default", deflt], ["mobile", mobile], ["team_id", team_id], ["user_id", user_id]];					
                    var rpc = new CAjaxRpc("/controller/Object/deleteForm", "deleteForm", args, cbdone, null, AJAX_POST, true, "json");*/
                    
                    ajax = new CAjax('json');                    
                    ajax.onload = function(ret)
                    {
                        if(ret)
                            window.close();
                        else
                        {
                            dlg.hide();
                            ALib.statusShowAlert("ERROR DELETING FORM!", 3000, "bottom", "right");
                        }
                    };
                    var args = [["obj_type", g_obj_type], ["default", deflt], ["mobile", mobile], ["team_id", team_id], ["user_id", user_id]];
                    ajax.exec("/controller/Object/deleteForm", args);
				}
			}
		}
	}

	function resized()
	{
		var tb = document.getElementById("toolbar");
		var bdy = document.getElementById("bdy_outer");
		var total_height = document.body.offsetHeight;
		alib.dom.styleSet(bdy, "height", (total_height - tb.offsetHeight) + "px");		
	}
</script>
<style type="text/css">
html, body
{
	height: 100%;
	overflow: hidden;
}
</style>
</head>
<body class='popup' onLoad="main();" onresize='resized()'>
<div id='toolbar' class='popup_toolbar'></div>
<div id='bdy_outer'>
	<div id='bdy' class='popup_body'></div>
</div>
</body>
</html>
