/****************************************************************************
*	
*	Class:		CWidStokcs
*
*	Purpose:	Main widget application for the stock ticker
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidStocks()
{
	this.title = "Stock Ticker";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Dropdown menu set by calling process
	
	this.m_menus = new Array();
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidStocks.prototype.main = function()
{
	this.m_container.innerHTML = "Loading, please wait...";

	var cls = this;
	Ant.setHinst(cls, "/widgets/stocks");

	this.loadStocks();

	var cls = this;
	this.m_dm.addEntry("Add Stock Symbol", function(cls){cls.addStock();}, 
						"/images/themes/"+Ant.m_theme+"/icons/addStock.gif", null, [cls]);
}

/*************************************************************************
*	Function:	exit
*
*	Purpose:	Perform needed clean-up on app exit
**************************************************************************/
CWidStocks.prototype.exit = function()
{
	Ant.clearHinst("/widgets/stocks");

	if (this.m_timer)
		clearTimeout(this.m_timer);

	this.m_container.innerHTML = "";
}

/*************************************************************************
*	Function:	addStock
*
*	Purpose:	Add a stock ticker to the users database
**************************************************************************/
CWidStocks.prototype.addStock = function()
{
	var ticker = '';
	var sym = prompt('Please enter a symbol to add', '');
	if (sym == '' || sym == null)
	{
		return;
	}

	var cls = this;
	var rpc = new CAjaxRpc("/stocks/xml_act_stocks.awp", "stock_add", 
							[["sym", sym]], function(ret, cls){cls.loadStocks();}, [cls]);
}

/*************************************************************************
*	Function:	deleteStock
*
*	Purpose:	Remove a stock ticker to the users database
**************************************************************************/
CWidStocks.prototype.deleteStock = function(id, name)
{
	if (confirm("Are you sure you want to remove "+name+"?"))
	{
		var cls = this;
		var rpc = new CAjaxRpc("/stocks/xml_act_stocks.awp", "stock_delete", 
								[["eid", id]], function(ret, cls){cls.loadStocks();}, [cls]);
	}
}

/*************************************************************************
*	Function:	loadStocks
*
*	Purpose:	Populate stocks table from users database
**************************************************************************/
CWidStocks.prototype.loadStocks = function()
{
	this.ajax = new CAjax();
	this.ajax.m_con = this.m_container;
	this.ajax.m_appcls = this;
	this.ajax.onload = function(root)
	{
		// Build Table
		var table = ALib.m_document.createElement("table");
		table.style.width = "100%";
		var tbody = ALib.m_document.createElement("tbody");
		table.appendChild(tbody);
		
		// Get contents of XML document
		var num = root.getNumChildren();
		for (i = 0; i < num; i++)
		{
			var stock = root.getChildNode(i);

			// Check for stocks
			if (stock.m_name == "stock")
			{
				var stock_id = "";
				var stock_memid = "";
				var stock_sym = false;
				var stock_name = "";
				var price = "";
				var price_change = "";
				var percent_change = "";
				var stock_class = "";

				// Populate vars
				var stock_num = stock.getNumChildren();
				for (j = 0; j < stock_num; j++)
				{
					stockvar = stock.getChildNode(j);
					
					switch (stockvar.m_name)
					{
					case "stock_id":
						var stock_id = stockvar.m_text;
						break;
					case "stock_memid":
						var stock_memid = stockvar.m_text;
						break;
					case "stock_sym":
						var stock_sym = stockvar.m_text;
						break;
					case "stock_name":
						var stock_name = stockvar.m_text;
						break;
					case "price":
						var price = stockvar.m_text;
						break;
					case "price_change":
						var price_change = stockvar.m_text;
						break;
					case "percent_change":
						var percent_change = stockvar.m_text;
						break;
					case "stock_class":
						var stock_class = stockvar.m_text;
						break;
					}
				}

				// The first two will always be market stocks
                if (i == 2)
				{
					// blank hr row
					var tr = ALib.m_document.createElement("tr");
					tr.className = "HStocksHeaderHr";
					var cell = ALib.m_document.createElement("td");
					cell.colspan = '5';
					tr.appendChild(cell);
					tbody.appendChild(tr);
				}
				
				if (i < 2)
					var tr_class = 'HStocksMarketsRow';
				else
					var tr_class = (i % 2) ? "HStocksRow1" : "HStocksRow2";	
				
				var tr = ALib.m_document.createElement("tr");
				tr.className = tr_class;
				var cell = ALib.m_document.createElement("td");
				cell.innerHTML = unescape(stock_name);
				tr.appendChild(cell);
				var cell = ALib.m_document.createElement("td");
				cell.innerHTML = unescape(price);
				cell.align = 'right';
				tr.appendChild(cell);
				var cell = ALib.m_document.createElement("td");
				cell.innerHTML = unescape(price_change);
				cell.className = unescape(stock_class);
				cell.align = 'right';
				tr.appendChild(cell);
				var cell = ALib.m_document.createElement("td");
				cell.innerHTML = unescape(percent_change);
				cell.className = unescape(stock_class);
				cell.align = 'right';
				tr.appendChild(cell);
				// Now create delete cell
				if (i < 2)
				{
					var cell = ALib.m_document.createElement("td");
					//cell.colspan = '2';
					tr.appendChild(cell);
				}
				else
				{
					var link = ALib.m_document.createElement("div");
					link.innerHTML = "<img src='/images/themes/"+Ant.m_theme+"/icons/deleteTask.gif' border='0'>";
					link.m_id = stock_memid;
					link.m_name = unescape(stock_name);
					link.m_appcls = this.m_appcls;
					link.onclick = function ()
					{
						this.m_appcls.deleteStock(this.m_id, this.m_name);
					}
					Ant.Dom.styleSet(link, "cursor", "pointer");
					var cell = ALib.m_document.createElement("td");
					//cell.colspan = '2';
					cell.appendChild(link);
					tr.appendChild(cell);
				}
				tbody.appendChild(tr);
			}
		}

		// Add table to div
		this.m_con.innerHTML = "";
		this.m_con.appendChild(table);

		this.m_appcls.m_timer = window.setTimeout("Ant.getHinst('/widgets/stocks').loadStocks()", 30000);
	};

	this.ajax.exec("/stocks/xml_act_stocks.awp");
}
