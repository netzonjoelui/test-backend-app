/***********************************************************************
*	Class:		CAdcClient
*
*				Copyright 2006, Aereus Corporation. All rights reserved.  
*
*	Purpose:	Act as JS clinet of ANT Datacenter Database
*
*	Security:	For security reasons, this script cannot pass user name
*				and password. There is a PHP Class called CAdcJsClient
*				that can accept quieries from this file (stored locally)
*				and it will handle authentication securely.
*
************************************************************************/

var g_CAdcClient = new Array();

function CAdcClient(url, dbid)
{
	// Set Database ID
	this.m_dbid = dbid;
	// Set URL
	this.m_url = url;
	// Set cols array
	this.m_cols = new Array();
	// Get last index
	this.m_ind = g_CAdcClient.length;
	//g_CAdcClient[this.m_ind] = new CAjax();
}
/***********************************************************************
*	Function:	query
*
*	Purpose:	Execute a query
*
************************************************************************/
CAdcClient.prototype.query = function(query)
{
	g_CAdcClient[this.m_ind] = null;
	g_CAdcClient[this.m_ind] = new CAjax();

	this.m_cols = null;
	this.m_cols = new Array();

	g_CAdcClient[this.m_ind].m_dbh = this;

	g_CAdcClient[this.m_ind].onload = function(root)
	{
		var retval = null;

		var num = root.getNumChildren();
		for (i = 0; i < num; i++)
		{
			var child = root.getChildNode(i);
			if (child.m_name == "retval")
			{
				if (child.m_text)
				{
					this.m_dbh.retval = unescape(child.m_text);
				}
			}

			if (child.m_name == "collist")
			{
				var num_cols = child.getNumChildren();
				for (j = 0; j < num_cols; j++)
				{
					var cols = child.getChildNode(j);
					if (cols.m_name == "column")
					{
						var num_vars = cols.getNumChildren();
						var name = null;
						var type_name = null;
						var type = null;
						var notes = null;

						for (m = 0; m < num_vars; m++)
						{
							var colattr = cols.getChildNode(m);
							
							switch (colattr.m_name)
							{
							case "name":
								name = (colattr.m_text) ? unescape(colattr.m_text) : "";
								break;
							case "type_name":
								type_name = (colattr.m_text) ? unescape(colattr.m_text) : "";
								break;
							case "type":
								type = (colattr.m_text) ? unescape(colattr.m_text) : "";
								break;
							case "notes":
								notes = (colattr.m_text) ? unescape(colattr.m_text) : "";
								break;
							}
						}
							
						if (name)
						{
							var ind = this.m_dbh.m_cols.length;
							
							this.m_dbh.m_cols[ind] = new Object();
							this.m_dbh.m_cols[ind].name = name;
							this.m_dbh.m_cols[ind].notes = notes;
							this.m_dbh.m_cols[ind].data_type = type_name;
							this.m_dbh.m_cols[ind].type = type;
						}
					}
				}
			}

			if (child.m_name == "dataset")
			{
				// Populate dataset and numrows
				if (child.getNumChildren())
					this.m_dbh.m_dataset = child;
			}
		}

		this.m_dbh.onload();
	};
	/*
	var dv = document.createElement("div");
	document.body.appendChild(dv);
	dv.innerHTML = this.m_url + "?dbid=" + this.m_dbid + "&query=" + escape(query);
	*/
	g_CAdcClient[this.m_ind].exec(this.m_url + "?dbid=" + this.m_dbid + "&query=" + escape(query));
}
/***********************************************************************
*	Function:	getNumRows
*
*	Purpose:	Get number of rows returned in XML document (not collis)
*
************************************************************************/
CAdcClient.prototype.getNumRows = function()
{
	if (this.m_dataset)
		return this.m_dataset.getNumChildren();
	else
		return 0;
}
/***********************************************************************
*	Function:	onload
*
*	Purpose:	Will be overloaded by client
*
************************************************************************/
CAdcClient.prototype.onload = function()
{
}
/***********************************************************************
*	Function:	getValue
*
*	Purpose:	Retrieve result at row,col_id
*
************************************************************************/
CAdcClient.prototype.getValue = function(row, col)
{
	if (this.getNumRows() > row && this.getNumCols() > col)
	{
		try
		{
			if (this.m_dataset.getChildNode(row))
				return this.m_dataset.getChildNode(row).getChildNode(col).m_text;
		}
		catch (e) {}
	}
}

/***********************************************************************
*	Function:	getNamedValue
*
*	Purpose:	Retrieve result at row,namedcol
*
************************************************************************/
CAdcClient.prototype.getNamedValue = function(row, colname)
{
	if (this.getNumRows() > row)
	{
		try
		{
			if (this.m_dataset.getChildNode(row))
			{
				var num = this.m_cols.length; //[ind].name
				for (var i = 0; i < num; i++)
				{
					if (this.m_cols[i].name == colname)
						return this.m_dataset.getChildNode(row).getChildNode(i).m_text;
				}
			}
		}
		catch (e) {}
	}

	return "";
}
CAdcClient.prototype.getNumCols = function()
{
	return this.m_cols.length;
}
CAdcClient.prototype.getColName = function(colind)
{
	return this.m_cols[colind].name;
}
CAdcClient.prototype.getCol = function(colind)
{
	return this.m_cols[colind];
}
CAdcClient.prototype.getColIndex = function(colname)
{
}
CAdcClient.prototype.escape = function(text)
{
	if (text)
	{
		var myRegExp = /[']/g;
		return text.replace(myRegExp, "\\'") ;	
	}
	else
		return text;
}
