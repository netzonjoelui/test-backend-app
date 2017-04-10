{
	name:"invoice_checkout",
	title:"Checkout",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.loaded = false;

		if (this.olCls)
		{
			this.olCls.pluginAddToolbarEntry("Process Payment", function(cbdata) { cbdata.cls.showDialog(); }, { cls:this });
			/*
			var btn = new CButton("Process Payment", function(cls) { cls.showDialog(); }, [this], "b1");
			this.toolbar.AddItem(btn.getButton(), "left");
			*/
		}
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
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

	showDialog:function()
	{
		if (!this.mainObject.id)
		{
			alert("Please save changes to this invoice before processing payments");
			return;
		}

		var billdata = new Object();
		// Set defaults
		billdata.method = "credit";
		if (this.mainObject.getValue("customer_id"))
		{
			billdata.customer_id = this.mainObject.getValue("customer_id");
		}
		else
		{
			alert("A customer must be selected for this invoice before processing payments");
			return;
		}

		var dlg = new CDialog("Process Payment");
		billdata.dlg = dlg;
		var dv = alib.dom.createElement("div");
		dlg.customDialog(dv, 300, 200);

		// Method
		var lbl = alib.dom.createElement("div", dv);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = "Select Payment Method:";
		var method_sel = alib.dom.createElement("select", dv);
		method_sel[method_sel.length] = new Option("Credit/Debit Card", "credit", false, (billdata.method=="credit")?true:false);
		method_sel[method_sel.length] = new Option("Cash", "cash", false, (billdata.method=="cash")?true:false);
		//method_sel[method_sel.length] = new Option("Check", "check", false, (billdata.method=="check")?true:false);
		method_sel.billdata = billdata;
		method_sel.cls = this;
		method_sel.onchange = function()
		{
			this.billdata.method = this.value;
			this.cls.checkoutFormBody(this.billdata);
		}

		var bdiv = alib.dom.createElement("div", dv);
		alib.dom.styleSet(bdiv, "margin-top", "10px");
		billdata.dv = bdiv;
		this.checkoutFormBody(billdata);

		var dv_btn = alib.dom.createElement("div", dv);
		var btn = new CButton("Complete Transaction", function(dlg, billdata, bdiv, cls) {  bdiv.innerHTML = "Completing transaction, please wait...";  cls.checkoutBill(billdata); }, 
								[dlg, billdata, bdiv, this], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);
	},

	checkoutFormBody:function(billdata, error)
	{
		var dv = billdata.dv;
		dv.innerHTML = "";

		if (error)
		{
			var p = alib.dom.createElement("p", dv);
			alib.dom.styleSetClass(p, "error");
			p.innerHTML = "ERROR: "+error;
		}

		var tbl = alib.dom.createElement("table", dv);
		var tbody = alib.dom.createElement("tbody", tbl);
		switch (billdata.method)
		{
		case 'credit':
			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Use:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("select", td);
			inp[inp.length] = new Option("Enter New Card", "", false, false);
			billdata.ccid = "";
			this.checkoutGetCcards(inp, billdata);
			inp.billdata = billdata;
			inp.cls = this;
			inp.onchange = function() 
			{ 
				this.billdata.ccid = this.value; 
				this.cls.enableDisableElements(this.billdata);
			}

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Name on Card:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("input", td);
			inp.billdata = billdata;
			billdata.inpName = inp;
			inp.onchange = function() { this.billdata.name = this.value; }

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Card Type:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("select", td);
			inp[inp.length] = new Option("Visa", "visa", false, true);
			inp[inp.length] = new Option("Master Card", "mastercard", false, false);
			billdata.inpType = inp;

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Card Number:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("input", td);
			inp.billdata = billdata;
			billdata.inpNumber = inp;
			inp.onchange = function() { this.billdata.ccnum = this.value; }

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Expires:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("input", td);
			inp.size=2;
			inp.maxLength = 2;
			inp.billdata = billdata;
			billdata.inpExpMo = inp;
			inp.onchange = function() { this.billdata.exp_month = this.value; }
			var lbl = alib.dom.createElement("span", td);
			lbl.innerHTML = "&nbsp;(mm)&nbsp;";
			var inp = alib.dom.createElement("input", td);
			inp.size=4;
			inp.maxLength = 4;
			inp.billdata = billdata;
			billdata.inpExpYr = inp;
			inp.onchange = function() { this.billdata.exp_year = this.value; }
			var lbl = alib.dom.createElement("span", td);
			lbl.innerHTML = "&nbsp;(yyyy)&nbsp;";
			break;
		case 'cash':
			var total_owned = this.mainObject.getValue("amount")

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Amount Owned:";
			var td = alib.dom.createElement("td", tr);
			var fmt = new NumberFormat(total_owned);	
			fmt.setCurrency(true);
			td.innerHTML = fmt.toFormatted();

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Cash Tendered:";
			var td = alib.dom.createElement("td", tr);
			var inp = alib.dom.createElement("input", td);
			inp.billdata = billdata;
			inp.total_owned = total_owned;
			inp.onchange = function() 
			{ 
				if (this.tdOwned && this.value && !this.total_owned.Nan)
				{
					var left = this.value - this.total_owned;
					var fmt = new NumberFormat(left);	
					fmt.setCurrency(true);
					this.tdOwned.innerHTML = fmt.toFormatted();
				}
			}

			var tr = alib.dom.createElement("tr", tbody);
			var td = alib.dom.createElement("td", tr);
			alib.dom.styleSetClass(td, "formLabel");
			td.innerHTML = "Change Due:";
			var td = alib.dom.createElement("td", tr);
			inp.tdOwned = td;
			break;
		}
	},

	enableDisableElements:function(billdata)
	{
		var dis = (billdata.ccid=="") ? false : true;
		billdata.inpName.disabled = dis;
		billdata.inpType.disabled = dis;
		billdata.inpNumber.disabled = dis;
		billdata.inpExpMo.disabled = dis;
		billdata.inpExpYr.disabled = dis;
	},

	checkoutBill:function(billdata)
	{
		if (billdata.method == "cash")
		{
			billdata.dlg.hide();
			return;
		}

		var args = [["invoice_id", this.mainObject.id],
					["billmethod", billdata.method], 
					["ccid", billdata.ccid], 
					["ccard_name", billdata.name], 
					["ccard_exp_year", billdata.exp_year], 
					["ccard_exp_month", billdata.exp_month], 
					["ccard_number", billdata.ccnum],
					["testmode", 1],
					["price", this.mainObject.getValue("amount")]];
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.billdata = billdata;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
            {
                this.cbData.cls.checkoutFormBody(this.cbData.billdata, this.message);
            }
            else
            {
                this.cbData.billdata.dlg.hide();
                ALib.Dlg.messageBox(this.message);
            }
        };

		alert(JSON.stringify(ret));

        ajax.exec("/controller/Sales/invoiceBill", args);
	},

	checkoutGetCcards:function(sel, billdata)
	{
		var funct = function(ret, sel, billdata, cls)
		{
			if (!ret['error'])
			{
				try
				{					
					for (ccard in ret)
					{
                        var currentCcard = ret[ccard];
						sel[sel.length] = new Option(currentCcard.type + " ending in " + currentCcard.last_four, currentCcard.id, false, currentCcard.default);
                        
						if (currentCcard.default)
						{
							billdata.ccid = currentCcard.id;
							cls.enableDisableElements(billdata);
						}
					}
				}
				catch (e) {  }
			}
		}

		var args = [["customer_id", billdata.customer_id]];
		//ALib.m_debug = true;
		//AJAX_TRACE_RESPONSE = true;		        
        var rpc = new CAjaxRpc("/controller/Sales/customerGetCcards", "customerGetCcards", args, funct, [sel, billdata, this], AJAX_POST, true, "json");
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.sel = sel;
        ajax.cbData.billdata = billdata;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
            {
                try
                {                    
                    for (ccard in ret)
                    {
                        var currentCcard = ret[ccard];
                        this.cbData.sel[this.cbData.sel.length] = new Option(currentCcard.type + " ending in " + currentCcard.last_four, currentCcard.id, false, currentCcard.default);
                        
                        if (currentCcard.default)
                        {
                            this.cbData.billdata.ccid = currentCcard.id;
                            this.cbData.cls.enableDisableElements(this.cbData.billdata);
                        }
                    }
                }
                catch (e) {  }
            }
        };
        ajax.exec("/controller/Sales/customerGetCcards", args);
	}
}
