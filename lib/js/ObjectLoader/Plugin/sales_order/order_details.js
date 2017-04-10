{
	name:"order_details",
	title:"Details",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.loaded = false;
		this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
	},

	save:function()
	{
		if (!this.mainObject.id)
		{
			this.onsave();
			return;
		}

		var args = [["order_id", this.mainObject.id]];

		var rows = this.datasheet.getRows();
		var num = 0;
		for (var i in rows) 
		{ 
			if (i != "new")
			{
				var quantity = rows[i].getValue(0);
				var name = rows[i].getValue(1);
				var amount = rows[i].getValue(2);
				amount = new NumberFormat(amount).toUnformatted();

				args[args.length] = ["entries[]", num];
				args[args.length] = ["ent_quantity_"+num, quantity];
				args[args.length] = ["ent_name_"+num, name];
				args[args.length] = ["ent_amount_"+num, amount];
				num++;
			}
		}
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.onsave();
        };
        ajax.exec("/controller/Sales/orderSaveDetail", args);
	},

	onsave:function()
	{
	},

	load:function()
	{
	},

	buildInterface:function()
	{
		// Add print button
		// ----------------------------------------------------
		if (this.olCls)
			this.olCls.pluginAddToolbarEntry("Print PDF ", function(cbdata) { cbdata.cls.printPdf(); }, { cls:this });

		// Add details
		// ----------------------------------------------------
		var con = this.m_con;

		var datasheet = new CDatasheet("100%");
		this.datasheet = datasheet;
		datasheet.clicksToEdit = "single";
		datasheet.invDetCls = this;
		datasheet.onCellChange = function(rowname, colname)
		{
			var quantity = this.getValue(rowname, 0);
			var amount = this.getValue(rowname, 2);
			amount = new NumberFormat(amount).toUnformatted();
			var fmt = new NumberFormat(amount);	
			fmt.setCurrency(true);
			this.setValue(rowname, 2, fmt.toFormatted());

			var fmt = new NumberFormat(quantity*amount);	
			fmt.setCurrency(true);
			this.setValue(rowname, 3, fmt.toFormatted());

			this.invDetCls.updateTotals();
		}

		datasheet.addHeader("Quantity", "left", "50px");
		datasheet.addHeader("Description");
		datasheet.addHeader("Unit Price", "left", "100px");
		datasheet.addHeader("Line Total", "left", "75px");

		if (this.mainObject.id)
			this.getEntries();
		else
			this.addNewEntryRow();

		datasheet.print(con);

		var dv = alib.dom.createElement("div", con);
		var tbl = alib.dom.createElement("table", dv);
		alib.dom.styleSet(tbl, "width", "100%");
		alib.dom.styleSet(tbl, "text-align", "right");
		var tbody = alib.dom.createElement("tbody", tbl);

		var tr = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSetClass(td, "formLabel");
		td.innerHTML = "Subtotal";
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSet(td, "width", "75px");
		td.innerHTML = "$0.00";
		this.cellSubtotal = td;

		var tr = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSetClass(td, "formLabel");
		td.innerHTML = "Tax";
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSet(td, "width", "75px");
		td.innerHTML = "$0.00";
		this.cellTax = td;

		var tr = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSetClass(td, "formLabel");
		td.innerHTML = "Total";
		var td = alib.dom.createElement("td", tr);
		alib.dom.styleSet(td, "width", "75px");
		td.innerHTML = "$0.00";
		this.cellTotal = td;
	},

	updateTotals:function()
	{
		var rows = this.datasheet.getRows();
		var total = 0;
		for (var i in rows) 
		{ 
			var line_ttl = rows[i].getValue(3);
			line_ttl = new NumberFormat(line_ttl).toUnformatted();
			total += parseFloat(line_ttl);
		}

		var fmt = new NumberFormat(total);	
		fmt.setCurrency(true);
		this.cellSubtotal.innerHTML = fmt.toFormatted();

		var tax_rate = this.mainObject.getValue("tax_rate");
		var tax = (tax_rate.length && !tax_rate.Nan) ? (total*(tax_rate/100)): 0;
		var fmt = new NumberFormat(tax);	
		fmt.setCurrency(true);
		this.cellTax.innerHTML = fmt.toFormatted();

		var total_all = total + tax;
		var fmt = new NumberFormat(total_all);	
		fmt.setCurrency(true);
		this.cellTotal.innerHTML = fmt.toFormatted();

		this.mainObject.setValue("amount", total_all);
	},

	getEntries:function()
	{
		var args = [["order_id", this.mainObject.id]];
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
            {
                try
                {                    
                    for (entry in ret)
                    {
                        var currentEntry = ret[entry];
                        var fmt = new NumberFormat(currentEntry.amount);    
                        
                        fmt.setCurrency(true);
                        var amount = fmt.toFormatted();
                        var fmt = new NumberFormat(currentEntry.quantity*currentEntry.amount);    
                        fmt.setCurrency(true);
                        var total = fmt.toFormatted();


                        var rw = this.cbData.cls.datasheet.addRow(currentEntry.id, i+1);
                        rw.addCell(currentEntry.quantity, "center");
                        rw.addCell(currentEntry.name);
                        rw.addCell(amount, "right");
                        rw.addCell(total, "right", null, null, true);
                    }
                }
                catch (e) { alert(e); }
            }

            this.cbData.cls.addNewEntryRow();
            this.cbData.cls.updateTotals();
        };
        ajax.exec("/controller/Sales/orderGetDetail", args);
	},

	addNewEntryRow:function()
	{
		rw = this.datasheet.addRow("new", "*");
		rw.addCell("", "center");
		rw.addCell("");
		rw.addCell("", "right");
		rw.addCell("", "right", null, null, true);

		this.datasheet.onCellUpdate = function(rowname, colname) // Every change made as key is pressed
		{
			if (rowname == "new")
			{
				var numrows = this.numRows();
				this.rows(rowname).setTitle(numrows);
				this.rows(rowname).setName("rowid"+numrows);

				var newrow = this.addRow("new", "*");
				newrow.addCell("", "center");
				newrow.addCell("");
				newrow.addCell("", "right");
				newrow.addCell("", "right", null, null, true);
			}
		}
	},

	printPdf:function()
	{
		var condv = alib.dom.createElement("div", alib.dom.m_document.body);
		alib.dom.styleSet(condv, "display", "none");
		alib.dom.styleSet(condv, "position", "absolute");

		var form = alib.dom.createElement("form", condv);
		form.setAttribute("method", "post");
		form.setAttribute("target", "_blank");
		form.setAttribute("action", "/sales/print_order.php");

		// Owner id
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "owner_id");
		hiddenField.setAttribute("value", this.mainObject.getValue("owner_id"));
		form.appendChild(hiddenField);
		// Owner id
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "name");
		hiddenField.setAttribute("value", this.mainObject.getValue("name"));
		form.appendChild(hiddenField);
		// payment_terms
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "payment_terms");
		hiddenField.setAttribute("value", this.mainObject.getValue("payment_terms"));
		form.appendChild(hiddenField);
		// Owner id
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "date_due");
		hiddenField.setAttribute("value", this.mainObject.getValue("date_due"));
		form.appendChild(hiddenField);
		// customer_id
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "customer_id");
		hiddenField.setAttribute("value", this.mainObject.getValue("customer_id"));
		form.appendChild(hiddenField);
		// tax_rate
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "tax_rate");
		hiddenField.setAttribute("value", this.mainObject.getValue("tax_rate"));
		form.appendChild(hiddenField);
		// template_id
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "template_id");
		hiddenField.setAttribute("value", this.mainObject.getValue("template_id"));
		form.appendChild(hiddenField);
		// number
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "number");
		hiddenField.setAttribute("value", this.mainObject.id);
		form.appendChild(hiddenField);
		// send_to
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "send_to");
		hiddenField.setAttribute("value", this.mainObject.getValue("send_to"));
		form.appendChild(hiddenField);
		// send_to_cbill
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "send_to_cbill");
		hiddenField.setAttribute("value", (this.mainObject.getValue("send_to_cbill"))?'t':'f');
		form.appendChild(hiddenField);


		var num = 0;
		var rows = this.datasheet.getRows();
		for (var i in rows) 
		{ 
			if (i != "new")
			{
				var quantity = rows[i].getValue(0);
				var name = rows[i].getValue(1);
				var amount = rows[i].getValue(2);
				amount = new NumberFormat(amount).toUnformatted();

				var hiddenField = alib.dom.createElement("input");              
				hiddenField.setAttribute("type", "hidden");
				hiddenField.setAttribute("name", "entries[]");
				hiddenField.setAttribute("value", num);
				form.appendChild(hiddenField);

				var hiddenField = alib.dom.createElement("input");              
				hiddenField.setAttribute("type", "hidden");
				hiddenField.setAttribute("name", "ent_quantity_"+num);
				hiddenField.setAttribute("value", quantity);
				form.appendChild(hiddenField);

				var hiddenField = alib.dom.createElement("input");              
				hiddenField.setAttribute("type", "hidden");
				hiddenField.setAttribute("name", "ent_name_"+num);
				hiddenField.setAttribute("value", name);
				form.appendChild(hiddenField);

				var hiddenField = alib.dom.createElement("input");              
				hiddenField.setAttribute("type", "hidden");
				hiddenField.setAttribute("name", "ent_amount_"+num);
				hiddenField.setAttribute("value", amount);
				form.appendChild(hiddenField);

				num++;
			}
		}

		form.submit();
		alib.dom.m_document.body.removeChild(condv);
	}
}
