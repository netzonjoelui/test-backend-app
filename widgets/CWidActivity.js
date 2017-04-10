/**
 * @fileoverview Activity dashboard widget
 *
 * @author	joe, sky.stebnicki@aereus.com
 * @copyright (c) 2012 Aereus Corporation. All rights reserved.
 */

/**
 * Class constructor
 */
function CWidActivity()
{
	this.title = "News & Updates";
    this.m_container = null;    // Set by calling process
    this.m_cct = null;          // Get the Contect Table Instance
    this.m_dm = null;           // Dropdown menu will be set by parent
    this.m_data = null;         // If data is set, this will be passed by parent process
    this.m_id = null;           // The id of the dashboard widget
    this.appNavname = null;     // The name of the current application
    this.dashboardCls = null;   // Holds the class for dashboard application
    this.m_webpageId = null;    
    this.widgetWidth = null;
	this.antView = null;		// If set this is the current antView rendered

	/**
	 * Activity browser references
	 *
	 * @var {AntObjectBrowser}
	 */
	this.activityBrowser = null;
}

/**
 * Entry point for application
 *
 * @public
 * @this {CWidActivity} 
 */
CWidActivity.prototype.main = function()
{
	this.m_container.innerHTML = "";

	// List status
	var statusCon = alib.dom.createElement("div", this.m_container);
	alib.dom.styleSet(statusCon, "margin-bottom", "10px");
	this.buildStatusUpdateForm(statusCon);

	// Display Activity Log
	var objb = new AntObjectBrowser("status_update");
	//if (this.antView)
		//objb.setAntView(this.antView);
	//objb.addCondition('and', 'level', 'is_greater_or_equal', "4"); // high level overview
	//objb.addCondition('and', 'type_id', 'is_not_equal', "comment");
	objb.addCondition('and', 'associations', 'is_equal', "user:-3");
	objb.addCondition('or', 'owner_id.team_id', 'is_equal', "-3");
	//objb.setFilter('user.team_id', "-3");
	
	var activityCon = alib.dom.createElement("div", this.m_container);
	objb.printInline(activityCon, true);
	this.activityBrowser = objb;
	if (this.antView)
		objb.setAntView(this.antView);
}

/**
 * Perform needed clean-up on app exit
 *
 * @public
 * @this {CWidActivity} 
 */
CWidActivity.prototype.exit = function()
{
    this.m_container.innerHTML = "";    
}

/**
 * Build status update form
 *
 * @param {DOMElement} con Status container for the form
 */
CWidActivity.prototype.buildStatusUpdateForm = function(con)
{
	con.innerHTML = "";


    /* Old input form used to post updates to everyone, but too many people got into trouble
     * so adding manual notify box
	// Add input
	var ta_comment = alib.dom.createElement("textarea", con);
	ta_comment.placeholder = "What are you working on right now?";
	alib.dom.styleSet(ta_comment, "display", "block");
	alib.dom.styleSet(ta_comment, "width", "100%");
	alib.dom.textAreaAutoResizeHeight(ta_comment);

	// Add submit
	var button = alib.ui.Button("Post Update", {
		className:"b1 nomargin", tooltip:"Click to publish your post", cls:this, textarea:ta_comment,
		onclick:function() { alib.dom.styleAddClass(this, "working"); this.cls.postStatusUpdate(this.textarea, this); }
	});
	var btnsp = alib.dom.createElement("div", con); // use for dynamic width
	alib.dom.styleSet(btnsp, "margin-top", "5px");
	alib.dom.styleSet(btnsp, "text-align", "right");
	button.print(btnsp);
    */
   
   // Image
	var imagecon = alib.dom.createElement("div", con);
	alib.dom.styleSet(imagecon, "float", "left");
	alib.dom.styleSet(imagecon, "width", "48px");
	imagecon.innerHTML = "<img src='/files/userimages/current/48/48' style='width:48px;' />";

	// Add input
	var inputDiv = alib.dom.createElement("div", con);
	alib.dom.styleSet(inputDiv, "margin-bottom", "5px");
		alib.dom.styleSet(inputDiv, "margin-left", "51px");
	var ta_comment = alib.dom.createElement("textarea", inputDiv);
    ta_comment.placeholder = "What are you working on right now?";
	alib.dom.styleSet(ta_comment, "width", "100%");
	alib.dom.styleSet(ta_comment, "height", "25px");
	alib.dom.textAreaAutoResizeHeight(ta_comment, 48);

	// Clear floats
	var clear = alib.dom.createElement("div", con);
	alib.dom.styleSet(clear, "clear", "both");

	// Notification
	var lbl = alib.dom.createElement("div", con);
	alib.dom.styleSet(lbl, "float", "left");
	alib.dom.styleSet(lbl, "width", "48px");
	alib.dom.styleSet(lbl, "padding-top", "5px");
	lbl.innerHTML = "Notify:";
	var inpdv = alib.dom.createElement("div", con);
	alib.dom.styleSet(inpdv, "margin-left", "51px");
	alib.dom.styleSet(inpdv, "margin-bottom", "5px");
	var inp_notify = alib.dom.createElement("input", inpdv);
	var t = new CTextBoxList(inp_notify, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: false, minLength: 2, queryRemote: true, remote: {url:"/users/json_autocomplete.php"}}}});

	// Add submit
	var button = alib.ui.Button("Save Status Update", {
		className:"b1 nomargin", tooltip:"Click to save and send your status update", cls:this, textarea:ta_comment, notify:t,
		onclick:function() { 
			alib.dom.styleAddClass(this, "working");
			this.cls.postStatusUpdate(this.textarea, this.notify, this); 
		}
	});
	var btnsp = alib.dom.createElement("div", con); // use for dynamic width
	alib.dom.styleSet(btnsp, "text-align", "right");
	button.print(btnsp);
}

/**
 * Send status update to ANT then refresh activity log
 * 
 * @param {textarea} textarea The textarea contianing the comment
 */
CWidActivity.prototype.postStatusUpdate = function(textarea, t_notify, btn)
{
    var notify = "";
	var values = t_notify.getValues();
	for (var i = 0; i < values.length; i++)
	{
		if (notify) notify += ",";
		if (values[i][0])
        {
			 notify += values[i][0];
             }
		else if (values[i][1]) // email, no object
			 notify += values[i][1];
	}
    
	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.textarea = textarea;
	ajax.cbData.btn = btn;
    ajax.cbData.t_notify = t_notify;
	ajax.onload = function(ret)
	{
		this.cbData.textarea.value = "";

		if (this.cbData.cls.activityBrowser)
			this.cbData.cls.activityBrowser.refresh();

        this.cbData.t_notify.clear(); // clear input

		alib.dom.styleRemoveClass(this.cbData.btn, "working");
	};        
	ajax.exec("/controller/User/logStatusUpdate", [["status", textarea.value], ["notify", notify]]);
}

/**
 * Build notices list
 *
 * @param {DOMElement} con Status container for the form
 */
CWidActivity.prototype.buildNotices = function(con)
{
	con.innerHTML = "";

	// Add header
	var ttl = alib.dom.createElement("div", con, "Unread Notices");
	alib.dom.styleSet(ttl, "font-weight", "bold");

	// Add mock notice
	var ncon = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(ncon, "notice");
	ncon.innerHTML = "<h3>Title</h3>Put notice here";
}
