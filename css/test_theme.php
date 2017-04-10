<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Theme Tester</title>
<meta HTTP-EQUIV="content-type" CONTENT="text/html; charset=UTF-8">
<link rel="STYLESHEET" type="text/css" href="/css/ant_base.css">
<?php
	$ALIBPATH = "/lib/aereus.lib.js/";
	include("../lib/aereus.lib.js/js_lib.php");
?>
<script language='javascript'>
function main()
{
	var main_con = document.getElementById("maincon");

	// Add title
    var titleCon = alib.dom.createElement("div", main_con);
    alib.dom.styleSetClass(titleCon, "apptitle");
    var ttl = alib.dom.createElement("h1", titleCon);
	ttl.innerHTML = "Application Title";

	// Body 
    var bodyCon = alib.dom.createElement("div", main_con);

	var left = alib.dom.createElement("div", bodyCon);
	alib.dom.styleSet(left, "float", "left");
	alib.dom.styleSet(left, "width", "600px");
	buildTypeography(left);
	var sp = alib.dom.createElement("div", left);
	sp.style.height = "10px";
	buildFormElements(left);

	var left = alib.dom.createElement("div", bodyCon);
	alib.dom.styleSet(left, "margin-left", "610px");
	alib.dom.styleSet(left, "width", "600px");
	buildInputs(left);
	var sp = alib.dom.createElement("div", left);
	sp.style.height = "10px";
	buildNavigation(left);
}

function buildTypeography(con)
{
	// Create Content Table
	var ctbl = new CContentTable("Typeography", "100%");		
	var ctbl_con = ctbl.getCon();
	ctbl.print(con);

	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Header 1";

	var h = alib.dom.createElement("h2", ctbl_con);
	h.innerHTML = "Header 2";

	var h = alib.dom.createElement("h3", ctbl_con);
	h.innerHTML = "Header 3";

	var h = alib.dom.createElement("h4", ctbl_con);
	h.innerHTML = "Header 4";

	var p = alib.dom.createElement("div", ctbl_con);
	p.innerHTML = "Plain text in a div";

	var p = alib.dom.createElement("p", ctbl_con);
	p.innerHTML = "Paragraph";

	var p = alib.dom.createElement("p", ctbl_con);
	alib.dom.styleSetClass(p, "notice");
	p.innerHTML = "Paragraph. class=notice";

	var p = alib.dom.createElement("p", ctbl_con);
	alib.dom.styleSetClass(p, "error");
	p.innerHTML = "Paragraph. class=error";

	var p = alib.dom.createElement("p", ctbl_con);
	alib.dom.styleSetClass(p, "success");
	p.innerHTML = "Paragraph. class=success";
}

function buildInputs(con)
{
	// Create Content Table
	var ctbl = new CContentTable("Input Controls", "100%");		
	var ctbl_con = ctbl.getCon();
	ctbl.print(con);

	// Test buttons
	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Test Buttons";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	var btn1 = new CButton("Test 1", null, null, "b1");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 2", null, null, "b2");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 3", null, null, "b3");
	btn1.print(btn_con);
	var btn1 = new CButton("Disabled", null, null, "b1");
	btn1.disable();
	btn1.print(btn_con);
	var h = alib.dom.createElement("h3", ctbl_con);
	h.innerHTML = "Collapsed";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	var btn1 = new CButton("Left", null, null, "b1 grLeft");
	btn1.print(btn_con);
	var btn1 = new CButton("Center", null, null, "b1 grCenter");
	btn1.print(btn_con);
	var btn1 = new CButton("Right", null, null, "b1 grRight");
	btn1.print(btn_con);

	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Test Buttons (medium)";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	var btn1 = new CButton("Test 1", null, null, "b1 medium");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 2", null, null, "b2 medium");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 3", null, null, "b3 medium");
	btn1.print(btn_con);
	var btn1 = new CButton("Disabled", null, null, "b1 medium");
	btn1.disable();
	btn1.print(btn_con);

	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Test Buttons (small)";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	var btn1 = new CButton("Test 1", null, null, "b1 small");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 2", null, null, "b2 small");
	btn1.print(btn_con);
	var btn1 = new CButton("Test 3", null, null, "b3 small");
	btn1.print(btn_con);
	var btn1 = new CButton("Disabled", null, null, "b1 small");
	btn1.disable();
	btn1.print(btn_con);

	// Inputs side-by-side
	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Side-By-Side Inputs";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	var inp = alib.dom.createElement("input");
	inp.type = "text";
	btn_con.appendChild(inp);
	var btn1 = new CButton("Button", null, null, "b1");
	btn1.print(btn_con);
	var inp = alib.dom.createElement("select", btn_con);

	// Other
	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Other Form Input Elements";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	btn_con.innerHTML = "<input type='text'><br /><input type='checkbox'><br />"
					  + "<textarea></textarea><br /><select><option>Test</option></select><br /><input type='radio'>";

	// Test working button
	var h = alib.dom.createElement("h1", ctbl_con);
	h.innerHTML = "Test Working Button";
	var btn = new alib.ui.Button("Toggle WOrking", {
		className:"b1", 
		tooltip:"Click to set this button as working" 
	});
	btn.print(ctbl_con);

	alib.events.listen(btn, "click", function(evt) { 
		this.working = this.working || false;

		if (this.working)
		{
			this.removeClass("working"); 
			this.working = false;
		}
		else
		{
			this.addClass("working"); 
			this.working = true;
		}
	});
}

function buildFormElements(con)
{
	// Create Content Table
	var ctbl = new CContentTable("Form Elements", "100%");		
	var ctbl_con = ctbl.getCon();
	ctbl.print(con);
	// Add context menu (text only)
	var cdiv = ctbl.get_ctitle();
	cdiv.innerHTML = "[X]";

	// Tabs
	var h = alib.dom.createElement("div", ctbl_con);
	alib.dom.styleSetClass(h, "formTitle");
	h.innerHTML = "Form Title: class=formTitle";
	var btn_con = alib.dom.createElement("div", ctbl_con);
	alib.dom.styleSet(btn_con, "margin-bottom", "10px");
	var tabs = new CTabs();
	var tabcon = tabs.addTab("Tab 1");
	var tabcon = tabs.addTab("Tab 2");
	var tabcon = tabs.addTab("Tab 3");
	var tabcon = tabs.addTab("Tab 4");
	tabs.print(btn_con);
	
	var frm = new CWindowFrame("Window Frame");
	var content = frm.getCon();
	frm.print(ctbl_con);
	var tb = new CToolbar();
	var btn = new CButton("Toolbar", function() { }, null, "b1");
	tb.AddItem(btn.getButton(), "left");
	tb.print(content);

	var frm = new CWindowFrame("Object Form: read");
	var content = frm.getCon();
	content.innerHTML = "<table style='width:100%;' cellpadding='0' cellspacing='0'><body>"
					  + "<tr><td class='formLabel' style='width:100px;'>Property Name</td><td><div class='formValue'>Property Value</div></td></tr>"
					  + "<tr><td class='formLabel'>Property Name</td><td><div class='formValue'>Property Value</div></td></tr>"
					  + "<tr><td class='formLabel'>Property Name</td><td><div class='formValue'>Property Value</div></td></tr>"
					  + "</tbody></table>";
	frm.print(ctbl_con);

	var frm = new CWindowFrame("Object Form: edit");
	var content = frm.getCon();
	content.innerHTML = "<table style='width:100%;' cellpadding='0' cellspacing='0'><body>"
					  + "<tr><td class='formLabel' style='width:100px;'>Property Name</td><td><div class='formValue'><input type='text'></div></td></tr>"
					  + "<tr><td class='formLabel'>Property Name</td><td><div class='formValue'><input type='text'></div></td></tr>"
					  + "<tr><td class='formLabel'>Property Name</td><td><div class='formValue'><input type='text'></div></td></tr>"
					  + "</tbody></table>";
	frm.print(ctbl_con);
}

function buildConversation(con)
{
	// Create Content Table
	var ctbl = new CContentTable("Form Elements", "800px");		
	var ctbl_con = ctbl.getCon();
	ctbl.print(con);

	var users = ["user 1", "user2", "user3", "user4", "user5", "user6", "user7", "user8", "user9", "user10"];

	var dv = alib.dom.createElement("div", ctbl_con);
	alib.dom.styleSetClass(dv, "EmailThreadMessageHeader");
	alib.dom.styleAddClass(dv, "EmailThreadSenderExp");
	var lbl = alib.dom.createElement("div", dv);
	lbl.innerHTML = "Sender/Me";

	var dv2 = alib.dom.createElement("div", ctbl_con);
	alib.dom.styleSetClass(dv2, "EmaiThreadMessageBody");
	dv2.innerHTML = "Body";

	for (var i = 0; i < users.length; i++)
	{
		var dv = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSetClass(dv, "EmailThreadMessageHeader");
		alib.dom.styleAddClass(dv, "EmailThreadRand"+(i+1)+"Exp");
		var lbl = alib.dom.createElement("div", dv);
		lbl.innerHTML = users[i];

		var dv2 = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSetClass(dv2, "EmaiThreadMessageBody");
		dv2.innerHTML = "Body";
		
	}

	var dv = alib.dom.createElement("div", ctbl_con);
	alib.dom.styleSetClass(dv, "EmailCollapsedMessage");
	alib.dom.styleAddClass(dv, "EmailThreadSenderCol");
	var lbl = alib.dom.createElement("div", dv);
	lbl.innerHTML = "Sender/Me";
	for (var i = 0; i < users.length; i++)
	{
		var dv = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSetClass(dv, "EmailCollapsedMessage");
		alib.dom.styleAddClass(dv, "EmailThreadRand"+(i+1)+"Col");
		var lbl = alib.dom.createElement("div", dv);
		lbl.innerHTML = users[i];
	}

	/*
	echo "<div class='EmailCollapsedMessage $coll_class' onclick=\"ToggleView('$t');\" id='bid$t' $coll_style>
					<div style='float:left;padding-top:0px;padding-right:5px;'> <img src='/images/icons/email-add_16.png' border='0'> </div>
					<div style='float:left;'>".$display_from."</div>
					<div style='float:left;'>&nbsp;&nbsp;--&nbsp;&nbsp;".$subject."</div>
					<div style='float:right;padding-right:5px;white-space:nowrap;'>".$date_str."</div>
					<div style='clear:both;'></div>
				  </div>";
	 */
}

function buildNavigation(con)
{
	// Create Content Table
	/*
	var ctbl = new CContentTable("Navigation", "100%");		
	var ctbl_con = ctbl.getCon();
	ctbl.print(con);
	 */

	var navDiv = alib.dom.createElement("div", con);
	navDiv.style.width = "200px";

	var nb = new CNavBar();
	nb.print(navDiv);

	var sec = nb.addSection("Actions");
	
	sec.addItem("Test Navigation Item 1", "/images/icons/add_10.png", function(){ }, [], "tes1");
	sec.addItem("Test Navigation Item 2", "/images/icons/add_10.png", function(){ }, [], "tes2");
	sec.addItem("Test Navigation Item 3", "/images/icons/add_10.png", function(){ }, [], "tes3");
	sec.addItem("Test Navigation Item 4", "/images/icons/add_10.png", function(){ }, [], "tes4");
	sec.addItem("Test Navigation Item 5", "/images/icons/add_10.png", function(){ }, [], "tes5");
}

</script>
<body onload='main();'>
<div id='maincon'>
</div>
</body>
</html>
