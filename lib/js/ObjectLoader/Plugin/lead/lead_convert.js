{
	name:"convert",
	title:"Convert",
	mainObject:null,
	toolbar:null,

	/**
	 * Handle to dialog
	 *
	 * @type {CDialog}
	 */
	dlg:null,

	/**
	 * Conversion data object
	 *
	 * @type {Object}
	 */
	formData:new Object(),

	/**
	 * Handle to the "Convert" button
	 *
	 * @type {alib.ui.Button}
	 */
	convertButton:null,

	/**
	 * Entry point for plugin
	 *
	 * @param {DOMElement} con The contianer to print theplugin into
	 */
	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.loaded = false;

		if (this.toolbar && this.mainObject.id)
			this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		if (this.mainObject.id)
			this.buildInterface();
	},

	save:function()
	{
		this.onsave();
	},

	onsave:function()
	{
	},

	load:function()
	{
	},

	buildInterface:function()
	{
		if (!this.loaded && this.olCls && this.mainObject.getValue("f_converted") != true)
			this.olCls.pluginAddToolbarEntry("Convert Lead ", function(cbdata) { cbdata.cls.showDialog(); }, { cls:this });
			
		this.loaded = true;
	},

	/**
	 * Build dialog
	 */
	showDialog:function()
	{
		var dlg = new CDialog("Convert Lead");
		dlg.f_close = true;
		this.dlg = dlg;

		var dv = alib.dom.createElement("div");

		// Customer
		var dv_desc = alib.dom.createElement("p", dv);
		dv_desc.innerHTML = "Once you have qualified a lead as a potential customer, it can be converted to an organziation/account, a contact, and an optional sales opportunity.";

		this.renderAccount(dv);
		/*
		// Create Organization / Account
		if (this.mainObject.getValue('company'))
		{
			var dv_hdr = alib.dom.createElement("h3", dv, "Organization / Account&nbsp;");
			var hlp = alib.dom.createElement("img", dv_hdr);
			hlp.src = "/images/icons/help_12.png";
			alib.dom.styleSet(hlp, "cursor", "help");
			alib.ui.Tooltip(hlp, "If the contact for this lead is part of an organization or as some CRMs call it, an account, then create a new organization account from the company name in the lead.", true);

			// Create New
			var rowOpt = alib.dom.createElement("div", dv);
			var rdo = alib.dom.createElement("input", rowOpt);
			alib.dom.styleSet(rdo, "vertical-align", "middle");
			rdo.type = 'radio';
			rdo.name = "account";
			rdo.value = 'new';
			rdo.checked = true;
			rdo.cls = this;
			rdo.onclick = function() { }
			var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Create a new organization from  from company name in lead");
			alib.dom.styleSet(lbl, "vertical-align", "middle");

			var rowOpt = alib.dom.createElement("div", dv);
			var rdo = alib.dom.createElement("input", rowOpt);
			alib.dom.styleSet(rdo, "vertical-align", "middle");
			rdo.type = 'radio';
			rdo.name = "account";
			rdo.value = 'existing';
			rdo.checked = false;
			var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Select existing organization")
			alib.dom.styleSet(lbl, "vertical-align", "middle");

			var existLbl = alib.dom.createElement("span", rowOpt)
			alib.dom.styleSet(existLbl, "vertical-align", "middle");
			alib.dom.styleSetClass(existLbl, "strong");

			rdo.cls = this;
			rdo.lbl = existLbl;
			rdo.dlg = dlg;
			rdo.onclick = function() { 
				var ob = new AntObjectBrowser("customer");
				ob.cbData.lbl = this.lbl;
				ob.cbData.pluginClass = this.cls;
				ob.onSelect = function(oid, name) 
				{
					this.cbData.pluginClass.organizationId = oid;
					this.cbData.lbl.innerHTML = ":&nbsp;" + name;
				}
				ob.displaySelect(this.dlg);
			}
		}
		*/

		this.renderContact(dv);

		// Create Opportunity
		// ---------------------------------------------------

		// Set default to true
		this.formData.f_createopp = 't';

		var dv_hdr= alib.dom.createElement("h3", dv, "Sales Opportunity&nbsp;");
		var hlp = alib.dom.createElement("img", dv_hdr);
		hlp.src = "/images/icons/help_12.png";
		alib.dom.styleSet(hlp, "cursor", "help");
		alib.ui.Tooltip(hlp, "Opportunities are how you manage possible business with customers. Think of them as a chance to make a sale or deal.", true);

		var name_inp = alib.dom.createElement("input", div_row);

		var div_row = alib.dom.createElement("div", dv);
		var ck_create = alib.dom.createElement("input");
		ck_create.type = "checkbox";
		ck_create.checked = true;
		ck_create.cls = this;
		ck_create.name_inp = name_inp;
		ck_create.onclick = function() { 
			this.cls.formData.f_createopp = (this.checked) ? 't' : 'f'; 
			this.name_inp.disabled = (this.checked) ? false : true; 
		}
		div_row.appendChild(ck_create);
		var lbl = alib.dom.createElement("span", div_row);
		lbl.innerHTML = " Create Opportunity";

		var div_row = alib.dom.createElement("div", dv);
		var lbl = alib.dom.createElement("span", div_row);
		lbl.innerHTML = "Name: ";
		div_row.appendChild(name_inp);
		name_inp.cls = this;
		name_inp.onchange = function() {
			this.cls.formData.opportunity_name = this.value;
		}
		name_inp.value = this.mainObject.getLabel();
		this.formData.opportunity_name = name_inp.value;
		alib.dom.styleSet(name_inp, "width", "250px");


		// Add button bar
		// ---------------------------------------------------
		
		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");

		/*
		var btn = new CButton("Convert", function(cls, dlg, ck_create, name_inp) {  cls.convertLead(dlg, ck_create.checked, name_inp.value); }, 
								[this, dlg, ck_create, name_inp], "b2");
		btn.print(dv_btn);
		*/
		var btn = new alib.ui.Button("Convert", {
			className:"b2", 
			tooltip:"Click to convert this lead", 
			cls: this,
			onclick:function(evt) {
				this.cls.validate();
			}
		});
		btn.print(dv_btn);
		this.convertButton = btn;

		var btn = new alib.ui.Button("Cancel", {
			className:"b1", 
			tooltip:"Cancel Conversion", 
			cls: this,
			onclick:function(evt) {
				this.cls.dlg.hide();
			}
		});
		btn.print(dv_btn);

		dlg.customDialog(dv, 500);
	},

	/**
	 * Build account/organiation part of the field
	 *
	 * @param {DOMElement} con The container to print the account form into
	 */
	renderAccount:function(con)
	{
		// Create Organization / Account
		if (this.mainObject.getValue('company'))
		{
			var dv_hdr = alib.dom.createElement("h3", con, "Organization / Account&nbsp;");
			var hlp = alib.dom.createElement("img", dv_hdr);
			hlp.src = "/images/icons/help_12.png";
			alib.dom.styleSet(hlp, "cursor", "help");
			alib.ui.Tooltip(hlp, "If the contact for this lead is part of an organization or as some CRMs call it, an account, then create a new organization account from the company name in the lead.", true);

			// Label used for storing reference to existing customer
			var existLbl = alib.dom.createElement("span")
			alib.dom.styleSetClass(existLbl, "strong");
			alib.dom.styleSet(existLbl, "vertical-align", "middle");

			// Create new option
			var rowOpt = alib.dom.createElement("div", con);
			var rdoNew = alib.dom.createElement("input", rowOpt);
			alib.dom.styleSet(rdoNew, "vertical-align", "middle");
			rdoNew.type = 'radio';
			rdoNew.name = "account";
			rdoNew.value = 'new';
			rdoNew.checked = true;
			rdoNew.cls = this;
			rdoNew.existLbl = existLbl;
			rdoNew.onclick = function() { 
				this.cls.formData.org_id = ""; 
				this.existLbl.innerHTML = "";
			}
			var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Create a new organization from  from company name in lead");
			alib.dom.styleSet(lbl, "vertical-align", "middle");

			// Use existing option
			var rowOpt = alib.dom.createElement("div", con);
			var rdo = alib.dom.createElement("input", rowOpt);
			alib.dom.styleSet(rdo, "vertical-align", "middle");
			rdo.type = 'radio';
			rdo.name = "account";
			rdo.value = 'existing';
			rdo.checked = false;
			rdo.cls = this;
			rdo.lbl = existLbl;
			rdo.dlg = this.dlg;
			rdo.onclick = function(e) { 
				var ob = new AntObjectBrowser("customer");
				ob.cbData.lbl = this.lbl;
				ob.cbData.pluginClass = this.cls;
				ob.onSelect = function(oid, name) 
				{
					this.cbData.pluginClass.formData.org_id = oid;
					this.cbData.lbl.innerHTML = ":&nbsp;" + name + "&nbsp;";

					// Add change
					var act = alib.dom.createElement("a", this.cbData.lbl, "change");
					act.href = "javascript:void(0);";
					act.onclick = function(e) {
						rdo.onclick(e);
					}
				}
				ob.displaySelect(this.dlg);
			}
			var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Select existing organization")
			alib.dom.styleSet(lbl, "vertical-align", "middle");

			// Put existing label at the end
			rowOpt.appendChild(existLbl);
		}
	},

	/**
	 * Build contact/person part of the field
	 *
	 * @param {DOMElement} con The container to print the contact form into
	 */
	renderContact:function(con)
	{
		// Create Contact header
		var dv_hdr = alib.dom.createElement("h3", con, "Person / Contact&nbsp;");
		var hlp = alib.dom.createElement("img", dv_hdr);
		hlp.src = "/images/icons/help_12.png";
		alib.dom.styleSet(hlp, "cursor", "help");
		alib.ui.Tooltip(hlp, "This is the actual person you will be working with on this opportunity.", true);

		// Label used for storing reference to existing customer
		var existLbl = alib.dom.createElement("span")
		alib.dom.styleSetClass(existLbl, "strong");
		alib.dom.styleSet(existLbl, "vertical-align", "middle");

		// Create new option
		var rowOpt = alib.dom.createElement("div", con);
		var rdoNew = alib.dom.createElement("input", rowOpt);
		alib.dom.styleSet(rdoNew, "vertical-align", "middle");
		rdoNew.type = 'radio';
		rdoNew.name = "contact";
		rdoNew.value = 'new';
		rdoNew.checked = true;
		rdoNew.cls = this;
		rdoNew.existLbl = existLbl;
		rdoNew.onclick = function() { 
			this.cls.formData.per_id = ""; 
			this.existLbl.innerHTML = "";
		}
		var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Create a new person from lead");
		alib.dom.styleSet(lbl, "vertical-align", "middle");

		// Use existing option
		var rowOpt = alib.dom.createElement("div", con);
		var rdo = alib.dom.createElement("input", rowOpt);
		alib.dom.styleSet(rdo, "vertical-align", "middle");
		rdo.type = 'radio';
		rdo.name = "contact";
		rdo.value = 'existing';
		rdo.checked = false;
		rdo.cls = this;
		rdo.lbl = existLbl;
		rdo.dlg = this.dlg;
		rdo.onclick = function(e) { 
			var ob = new AntObjectBrowser("customer");
			ob.cbData.lbl = this.lbl;
			ob.cbData.pluginClass = this.cls;
			ob.onSelect = function(oid, name) 
			{
				this.cbData.pluginClass.formData.per_id = oid;
				this.cbData.lbl.innerHTML = ":&nbsp;" + name + "&nbsp;";

				// Add change
				var act = alib.dom.createElement("a", this.cbData.lbl, "change");
				act.href = "javascript:void(0);";
				act.onclick = function(e) {
					rdo.onclick(e);
				}
			}
			ob.displaySelect(this.dlg);
		}
		var lbl = alib.dom.createElement("span", rowOpt, "&nbsp;Select existing person")
		alib.dom.styleSet(lbl, "vertical-align", "middle");

		// Put existing label at the end
		rowOpt.appendChild(existLbl);
	},

	/**
	 * Validate input data
	 */
	validate:function()
	{
		this.convertLead();
	},

	/**
	 * Convert data from options
	 */
	convertLead:function() 
	{
		this.convertButton.addClass("working");
		/*
		var args = [["lead_id", this.mainObject.id], ["f_createopp", (f_createopp)?'t':'f'], ["opportunity_name", opportunity_name]];        
        
        ajax = new CAjax('json');
        ajax.cbData.dlg = dlg;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
            {
                this.cbData.dlg.hide();
            }
        };
        ajax.exec("/controller/Customer/custLeadConvert", args);
		*/

		var xhr = new alib.net.Xhr();

		// If request was successful, then load the opportunity
		alib.events.listen(xhr, "load", function(evt) { 
			// Remove spinner
			evt.data.plCls.convertButton.removeClass("working");

			var ret = this.getResponse();
            if (ret['error'])
			{
				alert(ret['error']);
				return;
			}

			// Hide dialog and close the lead
           	evt.data.dlg.hide();
			evt.data.plCls.olCls.close();

			// Load converted object to continue work
			if (ret['opportunity_id'])
				loadObjectForm("opportunity", ret['opportunity_id']);
			else if (ret['contact_id'])
				loadObjectForm("customer", ret['contact_id']);
			else if (ret['account_id'])
				loadObjectForm("customer", ret['account_id']);
		}, {dlg:this.dlg, plCls:this});

		// Timed out
		alib.events.listen(xhr, "error", function(evt) { 
			// Remove spinner
			evt.data.plCls.convertButton.removeClass("working");
			alert("There was a problem contacting the server. Please try again.");
		}, {plCls:this});

		// Send request
		var data = this.formData;
		data.lead_id = this.mainObject.id;

		var ret = xhr.send("/controller/Customer/custLeadConvert", "POST", data);
	}
}
