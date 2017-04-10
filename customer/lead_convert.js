{
	name:"convert",
	title:"Convert",
	mainObject:null,
	toolbar:null,

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
		if (!this.loaded)
		{
			var btn = new CButton("Convert Lead", function(cls) { cls.showDialog(); }, [this], "b2");
			this.toolbar.AddItem(btn.getButton(), "left");
			this.loaded = true;
		}
	},

	showDialog:function()
	{
		var dlg = new CDialog("Convert Lead");
		dlg.f_close = true;

		var dv = alib.dom.createElement("div");

		// Customer
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		dv_hdr.innerHTML = "Convert to Contact and Account";
		var dv_desc = alib.dom.createElement("div", dv);
		dv_desc.innerHTML = "An account and contact will automatically created upon conversion.";

		// Continue Lead
		var dv_hdr= alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(dv_hdr, "headerTwo");
		alib.dom.styleSet(dv_hdr, "margin-top", "5px");
		dv_hdr.innerHTML = "Continue Lead";
		var dv_desc = alib.dom.createElement("div", dv);
		dv_desc.innerHTML = "An opportunity means this lead has been qualified and is ready to take things to the next level.";

		var name_inp = alib.dom.createElement("input", div_row);

		var div_row = alib.dom.createElement("div", dv);
		var ck_create = alib.dom.createElement("input");
		ck_create.type = "checkbox";
		ck_create.checked = true;
		ck_create.name_inp = name_inp;
		ck_create.onclick = function() { this.name_inp.disabled = (this.checked) ? false : true; }
		div_row.appendChild(ck_create);
		var lbl = alib.dom.createElement("span", div_row);
		lbl.innerHTML = " Create Opportunity";

		var div_row = alib.dom.createElement("div", dv);
		var lbl = alib.dom.createElement("span", div_row);
		lbl.innerHTML = "Name: ";
		name_inp.value = this.mainObject.getLabel();
		alib.dom.styleSet(name_inp, "width", "250px");
		div_row.appendChild(name_inp);

		// Buttons
		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "text-align", "right");

		var btn = new CButton("Convert", function(cls, dlg, ck_create, name_inp) {  cls.convertLead(dlg, ck_create.checked, name_inp.value); }, 
								[this, dlg, ck_create, name_inp], "b2");
		btn.print(dv_btn);

		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);

		dlg.customDialog(dv, 500);
	},

	convertLead:function(dlg, f_createopp, opportunity_name)
	{
		var args = [["lead_id", this.mainObject.id], ["f_createopp", (f_createopp)?'t':'f'], ["opportunity_name", opportunity_name]];
        
        ajax = new CAjax('json');
        ajax.dlg = dlg;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
            {
                this.dlg.hide();
            }    
        };
        ajax.exec("/controller/Customer/custLeadConvert", args);
	}
}
