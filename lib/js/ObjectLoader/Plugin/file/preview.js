{
	name:"preview",
	title:"File Previewer",
	mainObject:null, // will be set by form class, is a reference to edited object
	outerPreviewCon:null, // Container for the preview
	innerPreviewCon:null, // This is where the preview is actually printed

	/**
	 * Called once form is fully loaded
	 *
	 * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
	 */
	main:function(con)
	{
		this.m_con = con;

		if (this.mainObject.id)
		{
			this.buildInterface();
		}
	},

	/**
	 * This function is called when the object is saved. Use it to rest forms that require an object id
	 */
	objectsaved:function()
	{
		if (this.mainObject.id)
			this.buildInterface();
	},

	/**
	 * Will be called by AntObjectLoader_Form when the user saves changes. 
	 * This MUST call this.onsave when finsihed or the browser will hang.
	 */
	save:function()
	{
		/*
		if (this.mainObject.id && this.inp_create && this.inp_create.checked)
		{
			var args = [["cid", this.mainObject.id], 
						["owner_id", this.mainObject.getValue("owner_id")],
						["case_name", this.mainObject.getValue("title")]];

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
		*/

		this.onsave();
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
		if (this.mainObject.id && this.innerPreviewCon)
		{
			var args = [["fid", this.mainObject.id]];

            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
				var buf = "";

				if (ret.urlImage)
					buf += "<img src='" +ret.urlImage+ "' />";
				else if (ret.html)
					buf += ret.html;
				/*
				else
					buf += "<img src='/images/icons/objects/" +this.cbData.cls.mainObject.iconName+ "_48.png' />";
				*/

				if (buf)
					alib.dom.setHtml(this.cbData.cls.innerPreviewCon, buf);
				else
					alib.dom.styleSet(this.cbData.cls.outerPreviewCon, "display", "none");
            };
            ajax.exec("/controller/AntFs/getFilePreview", args);
		}
	},

	/**
	 * Private function for building interface
	 */
	buildInterface:function()
	{
		var pcon = alib.dom.createElement("div", this.m_con);
		alib.dom.styleSet(pcon, "background-color", "#ccc");
		alib.dom.styleSet(pcon, "padding", "5px");
		this.outerPreviewCon = pcon;

		this.innerPreviewCon = alib.dom.createElement("div", pcon);
		alib.dom.styleSet(this.innerPreviewCon, "text-align", "center");

		this.load();
	}
}
