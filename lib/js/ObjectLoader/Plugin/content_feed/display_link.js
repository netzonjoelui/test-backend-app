{
	name:"publish_link",
	title:"Links",
	mainObject:null,
	toolbar:null,

	main:function(con)
	{
		this.data = new Object();
		this.m_con = con;
		this.loaded = false;

		if (this.mainObject.id)
			this.buildInterface();
	},

	// This function is called when the object is saved. Use it to rest forms that require an object id
	objectsaved:function()
	{
		if (!this.loaded)
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
		this.loaded = true;

		this.m_con.innerHTML = "";
		var table = alib.dom.createElement("table", this.m_con);
		table.cellPadding = 0;
		table.cellSpacing = 0;
		alib.dom.styleSet(table, "margin-top", "3px");
		alib.dom.styleSet(table, "width", "98%");
		var tbody = alib.dom.createElement("tbody", table);

		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_lbl, "width", "100px");
		alib.dom.styleSetClass(td_lbl, "formLabel");
		td_lbl.innerHTML = "XML URL:";
		var td_inp = alib.dom.createElement("td", row);
		var lnk = "http://" + document.domain + "/feeds/?fid="+this.mainObject.id;
		td_inp.innerHTML = "<a href='"+lnk+"' target='_blank'>"+lnk+"</a>";

		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_lbl, "width", "100px");
		alib.dom.styleSetClass(td_lbl, "formLabel");
		td_lbl.innerHTML = "RSS URL:";
		var td_inp = alib.dom.createElement("td", row);
		var lnk = "http://" + document.domain + "/feeds/rss.awp?fid="+this.mainObject.id;
		td_inp.innerHTML = "<a href='"+lnk+"' target='_blank'>"+lnk+"</a>";
	}
}
