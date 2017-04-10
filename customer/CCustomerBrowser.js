/****************************************************************************
*	
*	Class:		CCustomerBrowser
*
*	Purpose:	Browser to select or add customers within the page (no popup)
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2009 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
var CUST_TYPE_CONTACT = 1;
var CUST_TYPE_ACCOUNT = 2;

function CCustomerBrowser()
{
	this.title = "Select Customer";		// Customize the title
	this.customerTitle = "Customer";	// Used to change the title of a customer
	this.allowNew = true;				// Show "create new customer" option
	this.filterType = null;				// Show filter out accounts or contacts
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display a customer browser
**************************************************************************/
CCustomerBrowser.prototype.showDialog = function()
{
	var dlg = new CDialog(this.title);
	this.m_dlg = dlg;
	dlg.f_close = true;

	// Search Bar
	var dv = alib.dom.createElement("div");
	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = "Find: ";
	this.m_txtSearch = alib.dom.createElement("input", dv);
	alib.dom.styleSet(this.m_txtSearch, "width", "300px");
	this.m_txtSearch.m_cls = this;
	this.m_txtSearch.onkeyup = function(e)
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
			this.m_cls.loadCustomers();
		}
	}

	var btn = new CButton("Search", function(cls) {  cls.loadCustomers(); }, [this]);
	btn.print(dv);
	
	// Pagination and add
	this.pag_div = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.pag_div, "margin-bottom", "3px");
	alib.dom.styleSet(this.pag_div, "text-align", "right");
	this.pag_div.innerHTML = "Page 1 of 1";

	// Results
	this.m_browsedv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.m_browsedv, "height", "350px");
	alib.dom.styleSet(this.m_browsedv, "border", "1px solid");
	alib.dom.styleSet(this.m_browsedv, "background-color", "white");
	alib.dom.styleSet(this.m_browsedv, "overflow", "auto");
	this.m_browsedv.innerHTML = "<div style='margin:10px;vertical-align:middle;'><span class='loading'></span></div>";

	// New Customer Form
	this.dv_new = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.dv_new, "margin-top", "3px");
	alib.dom.styleSet(this.dv_new, "border", "1px solid");
	alib.dom.styleSet(this.dv_new, "padding", "3px");
	this.createNew();
	
	dlg.customDialog(dv, 600, 420);

	// Load customers
	this.loadCustomers();
}

/*************************************************************************
*	Function:	select
*
*	Purpose:	Internal function to select a customer then fire pubic onselect
**************************************************************************/
CCustomerBrowser.prototype.select = function(cid, name)
{
	this.m_dlg.hide();
	this.onSelect(cid, name);
}

/*************************************************************************
*	Function:	createNew
*
*	Purpose:	Create a new customer form
**************************************************************************/
CCustomerBrowser.prototype.createNew = function(showfrm)
{
	var show_form = (showfrm) ? true : false;
	this.dv_new.innerHTML = "";
	
	if (show_form)
	{
		var lbl = alib.dom.createElement("span", this.dv_new);
		lbl.innerHTML = "Type :&nbsp;";

		var sel = alib.dom.createElement("select", this.dv_new);
		sel.onchange = function () { }
		sel[sel.length] = new Option("Account", CUST_TYPE_ACCOUNT, false, true);
		sel[sel.length] = new Option("Contact", CUST_TYPE_CONTACT, false, false);

		var lbl = alib.dom.createElement("span", this.dv_new);
		lbl.innerHTML = "&nbsp;Name:&nbsp;";

		var inp = alib.dom.createElement("input");
		inp.type = 'text';
		alib.dom.styleSet(inp, "width", "150px");
		this.dv_new.appendChild(inp);

		var lbl = alib.dom.createElement("span", this.dv_new);
		lbl.innerHTML = "&nbsp;";

		function btnPressCreateNew(cls, cb_type, txt_name)
		{
			if (cb_type.value && txt_name.value)
			{
				cls.saveNewCustomer(cb_type.value, txt_name.value);
			}
			else
			{
				ALib.Dlg.messageBox("Please enter a name", cls.m_dlg);
			}
		}
		var btn = new CButton("Create", btnPressCreateNew, [this, sel, inp], "b2");
		btn.print(this.dv_new);

		var lbl = alib.dom.createElement("span", this.dv_new);
		lbl.innerHTML = "&nbsp;&nbsp;";

		var btn = new CButton("Cancel", function(cls) {  cls.createNew(); }, [this]);
		btn.print(this.dv_new);
	}
	else
	{
		var btn = new CButton("Create Contact/Account", function(cls) {  cls.createNew(true); }, [this], "b2");
		btn.print(this.dv_new);
	}
}

/*************************************************************************
*	Function:	saveNewCustomer
*
*	Purpose:	Save the new customer
**************************************************************************/
CCustomerBrowser.prototype.saveNewCustomer = function(type, name)
{
	var args = [["name",escape(name)], ["type_id", type]];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.name = name;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            this.cls.select(ret, this.name);
        }    
    };
    ajax.exec("/controller/Customer/createCustomer", args);
}

/*************************************************************************
*	Function:	onSelect
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CCustomerBrowser.prototype.onSelect = function(cid, name)
{
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CCustomerBrowser.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	loadCustomers
*
*	Purpose:	Load customers
**************************************************************************/
CCustomerBrowser.prototype.loadCustomers = function(start)
{
	var istart = (typeof start != "undefined") ? start : 0;

	this.m_browsedv.innerHTML = "<div class='loading'></div>";

	this.m_ajax = new CAjax();
	var me = this;
	this.m_ajax.m_browseclass = me;
	this.m_ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			this.m_browseclass.m_browsedv.innerHTML = "";
			this.m_browseclass.pag_div.innerHTML = "";

			this.m_browseclass.m_doctbl = new CToolTable("100%");
			var tbl = this.m_browseclass.m_doctbl;
			tbl.print(this.m_browseclass.m_browsedv);

			tbl.addHeader("ID");
			tbl.addHeader("Name");
			tbl.addHeader("&nbsp;", "center", "20px");
			tbl.addHeader("Phone");
			tbl.addHeader("Email");
			tbl.addHeader("Group(s)");
			for (i = 0; i < num; i++)
			{
				var rw = tbl.addRow();

				var child = root.getChildNode(i);

				if (child.m_name == "contact")
				{
					var id = child.getChildNodeValByName("id");
					var phone = child.getChildNodeValByName("number");
					var email = child.getChildNodeValByName("email");
					var labels = child.getChildNodeValByName("labels");
					var name = child.getChildNodeValByName("name");
					if (!name) name = "untitled";
					var alnk = alib.dom.createElement("a");
					alnk.href = "javascript:void(0);";
					alnk.innerHTML = unescape(name);
					alnk.m_id = unescape(id);
					alnk.m_browseclass = this.m_browseclass;
					alnk.m_cid = id;
					alnk.m_cname = unescape(name);;
					alnk.onclick = function()
					{
						this.m_browseclass.select(this.m_cid, this.m_cname);
					}
					rw.addCell(id, true, "center");
					rw.addCell(alnk);
					var sel_dv = ALib.m_document.createElement("div");
					sel_dv.innerHTML = "[open]";
					alib.dom.styleSet(sel_dv, "cursor", "pointer");
					sel_dv.m_cid = id;
					sel_dv.m_cname = unescape(name);;
					sel_dv.m_browseclass = this.m_browseclass;
					sel_dv.onclick = function()
					{
						loadObjectForm('customer', this.cid);
						//var params = 'top=200,left=100,width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
						//window.open('/customer/edit_customer.awp?custid='+this.m_cid, 'cust_'+this.cid, params);
					}
					rw.addCell(sel_dv, true, "center");
					rw.addCell(unescape(phone));
					rw.addCell(unescape(email));
					rw.addCell(unescape(labels));
				}
				else if (child.m_name == "paginate")
				{
					var prev = child.getChildNodeValByName("prev");
					var next = child.getChildNodeValByName("next");
					var pag_str = child.getChildNodeValByName("pag_str");	
					
					var lbl = alib.dom.createElement("span", this.m_browseclass.pag_div);
					lbl.innerHTML = pag_str;

					if (prev || next)
					{
						var lbl = alib.dom.createElement("span", this.m_browseclass.pag_div);
						lbl.innerHTML = " | ";

						if (prev)
						{
							var lnk = alib.dom.createElement("span", this.m_browseclass.pag_div);
							lnk.innerHTML = "&laquo; previous";
							alib.dom.styleSet(lnk, "cursor", "pointer");
							lnk.start = prev;
							lnk.m_browseclass = this.m_browseclass;
							lnk.onclick = function()
							{
								this.m_browseclass.loadCustomers(this.start);
							}
						}

						if (next)
						{
							var lnk2 = alib.dom.createElement("span", this.m_browseclass.pag_div);
							lnk2.innerHTML = " next &raquo;";
							alib.dom.styleSet(lnk2, "cursor", "pointer");
							lnk2.start = next;
							lnk2.m_browseclass = this.m_browseclass;
							lnk2.onclick = function()
							{
								this.m_browseclass.loadCustomers(this.start);
							}
						}
					}
				}
			}
		}
		else
			this.m_browseclass.m_browsedv.innerHTML = " No records found.";
	};

	var url = "/customer/xml_get_customers.awp?fval=0";
	if (this.m_txtSearch.value && this.m_txtSearch.value != 'search here')
		url += "&search=" + escape(this.m_txtSearch.value);
	if (this.filterType)
		url += "&type_id=" + this.filterType;
	if (istart)
		url += "&start=" + istart;
	this.m_ajax.exec(url);
}
