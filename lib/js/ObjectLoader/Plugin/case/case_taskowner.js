{
	name:"case_taskowner",
	title:"Create Task For Owner",
	mainObject:null, // will be set by form class, is a reference to edited object

	/**
	 * Called once form is fully loaded
	 *
	 * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
	 */
	main:function(con)
	{
		this.m_con = con;
		this.inp_create = null;

		this.load();
	},

	/**
	 * Will be called by AntObjectLoader_Form when the user saves changes. 
	 * This MUST call this.onsave when finsihed or the browser will hang.
	 */
	save:function()
	{
		if (this.mainObject.id && this.inp_create && this.inp_create.checked)
		{
			var args = [["cid", this.mainObject.id], 
						["owner_id", this.mainObject.getValue("owner_id")],
						["case_name", this.mainObject.getValue("title")]];

			/*var funct = function(ret, cls)
            {
                cls.onsave();
            }
            var rpc = new CAjaxRpc("/controller/Project/caseTaskowner", "caseTaskowner", args, funct, [this], AJAX_POST, true, "json");*/
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.onsave();                
            };
            ajax.exec("/controller/Project/caseTaskowner", args);
		}
		else
		{
			this.onsave();
		}
	},

	/**
	 * Inform the AntObjectLoader_Form object that this plugin has finished saving changes
	 */
	onsave:function()
	{
	},

	/**
	 * Private function for loading interface
	 */
	load:function()
	{
		// Only load this plugin if we are working with a new case
		if (!this.mainObject.id)
			this.buildInterface();
	},

	/**
	 * Private function for building interface
	 */
	buildInterface:function()
	{
		this.m_con.innerHTML = "";
		var table = alib.dom.createElement("table", this.m_con);
		table.cellPadding = 0;
		table.cellSpacing = 0;
		alib.dom.styleSet(table, "margin-top", "3px");
		alib.dom.styleSet(table, "width", "98%");
		var tbody = alib.dom.createElement("tbody", table);

		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_lbl, "width", "105px");
		alib.dom.styleSetClass(td_lbl, "formLabel");
		td_lbl.innerHTML = "&nbsp;";

		var td_inp = alib.dom.createElement("td", row);
		var dv = alib.dom.createElement("div", td_inp);
		alib.dom.styleSetClass(dv, "formValue");
		var inp_create = alib.dom.createElement("input");
		inp_create.type = 'checkbox';
		dv.appendChild(inp_create);
		var sp = alib.dom.createElement("span", dv);
		sp.innerHTML = " Create new task for owner";

		this.inp_create = inp_create;
	}
}
