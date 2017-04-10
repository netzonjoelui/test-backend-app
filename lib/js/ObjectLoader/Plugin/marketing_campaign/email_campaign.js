/**
 * Email campaign insert for marketing campaigns
 */
{
	name:"email_campaign",
	title:"Display email campaign within a marketing campaign",
	mainObject:null,
	olCls:null, // object loader class reference

	/**
	 * Object loader form
	 *
	 * @var {AntObjectLoader_Form}
	 */
	formObj: null,

	/**
	 * Contianer to print the plugin into
	 *
	 * @var {DOMElement}
	 */
	con: null,

	/**
	 * Innter container of fieldset
	 *
	 * @var {DOMElement}
	 */
	innerCon: null,

	/**
	 * Main function called when form is ready to load the plugin
	 */
	main:function(con)
	{
		this.con = con;

		this.buildInterface();

		// Listen for change in the type to display plugin
		alib.events.listen(this.mainObject, "fieldchange", function(evnt) { 
			if (evnt.data.fieldName == "type_id")
				evnt.data.pluginCls.buildInterface();
		}, {pluginCls:this});
	},

	/**
	 * Callback called when the main object is being saved
	 */
	save:function()
	{
		this.onsave();
	},

	/**
	 * Internal callback used to let the main form know we have finished loading
	 */
	onsave:function()
	{
	},

	/**
	 * Called after the main object has been saved
	 */
	objectsaved:function()
	{
		if (this.mainObject && this.mainObject.id)
			this.buildInterface();
	},

	/**
	 * Internal funciton to buil the interface
	 *
	 * @private
	 */
	buildInterface:function()
	{
		this.con.innerHTML = "";

		// We only handle email campaigns
		if (this.mainObject.getValueName("type_id") != "Email")
			return;

		var frm = new CWindowFrame("Email Campaign");
		this.innerCon = frm.getCon();
		frm.print(this.con);

		var emailCampaignId = this.mainObject.getValue("email_campaign_id");

		if (emailCampaignId)
		{
			this.innerCon.innerHTML = "<div class='loading'></div>";

			var obj = new CAntObject("email_campaign");
			obj.cbData.plCls = this;
			obj.cbData.innerCon = this.innerCon;
			obj.onload = function()
			{
				this.cbData.innerCon.innerHTML = "";
				this.cbData.plCls.renderEdit(this);
			}
			obj.load(emailCampaignId);
		}
		else
		{
			this.renderEdit(null);
		}

		// Print buttons
		// -----------------------------------------
		/*
		var buttonRow = alib.dom.createElement("div", this.con);
		alib.dom.styleSet(buttonRow, "margin", "5px 0px 7px 0px");

		var btn = alib.ui.Button("Add Comment", {
			className:"b1 grLeft medium", tooltip:"Add a Comment", cls:this,
			onclick:function() { }
		});
		btn.toggle(true); // Toggle on to look like a tab
		btn.print(buttonRow);

		var btn = alib.ui.Button("Add Task", {
			className:"b1 grCenter medium", tooltip:"Add a Task", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("task", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Schedule Event", {
			className:"b1 grCenter medium", tooltip:"Schedule a calendar event", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("calendar_event", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Create Reminder", {
			className:"b1 grCenter medium", tooltip:"Create a reminder for yourself", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("reminder", null, [["obj_reference", "customer:" + this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		var btn = alib.ui.Button("Log Phone Call", {
			className:"b1 grRight medium", tooltip:"Log a phone call", cls:this,
			onclick:function() { this.cls.olCls.loadObjectForm("phone_call", null, [["customer_id", this.cls.mainObject.id]]); }
		});
		btn.print(buttonRow);

		// Add comment form
		// -----------------------------------------
		var commentCon = alib.dom.createElement("div", this.con);
		this.buildCommentForm(commentCon);
		*/
	},

	/**
	 * Render edit or create
	 *
	 * @param {CAntObject} emCamp The email campaign object (loaded)
	 */
	renderEdit:function(emCamp)
	{
		if (emCamp)
		{
			var lbl = alib.dom.createElement("span", this.innerCon);
			lbl.innerHTML = "Status: <strong>" + emCamp.getValueName("status") + "</strong>&nbsp;";

			// Display edit if not sending or sent
			if (emCamp.getValue("status") != 5 && emCamp.getValue("status") != 6)
			{
				var btn = alib.ui.Button("Edit", {
					className:"b2", tooltip:"Get Started Creating and Sending and Email Campaign", 
					cls:this, emCamp:emCamp,
					onclick:function() { 
						var wiz = new AntWizard("EmailCampaign", {campObj:this.emCamp});
						wiz.show(); 
					}
				});
				btn.print(this.innerCon);
			}
		}
		else
		{
			var btn = alib.ui.Button("Create &amp; Send Mass Email", {
				className:"b2", tooltip:"Get Started Creating and Sending and Email Campaign", cls:this,
				onclick:function() { 
					var wiz = new AntWizard("EmailCampaign");
					wiz.show(); 
				}
			});
			btn.print(this.innerCon);
		}
	},

	/**
	 * Render stats for a launched campaign
	 *
	 * @param {CAntObject} emCamp The email campaign object (loaded)
	 */
	renderStats:function(emCamp)
	{
		// If the campaing is sending or sent
		if (emCamp.getValue("status") == 5 || emCamp.getValue("status") == 6)
		{
			this.innerCon.innerHTML = "print report here";
		}
		else
		{
			return renderEdit(emCamp);
		}
	},
}
