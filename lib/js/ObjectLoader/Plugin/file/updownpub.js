{
	name:"updownpub",
	title:"Upload, Download and Publish",
	mainObject:null, // will be set by form class, is a reference to edited object

	/**
	 * Called once form is fully loaded
	 *
	 * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
	 */
	main:function(con)
	{
		this.m_con = con;
		this.load();
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
		// Only load this plugin if we are working with a new case
		//if (!this.mainObject.id)
		this.buildInterface();
	},

	/**
	 * Private function for building interface
	 */
	buildInterface:function()
	{
		this.m_con.innerHTML = "";

		if (this.mainObject.getValue("folder_id"))
		{
			var button = alib.ui.Button("Upload File", {
				className:"b1", tooltip:"Update by uploading a file on your computer", cls:this, 
				onclick:function() { this.cls.uploadFile(); }
			});
			var bcon = alib.dom.createElement("div", this.m_con);
			alib.dom.styleSet(bcon, "margin-bottom", "10px");
			button.print(bcon);
		}

		if (this.mainObject.id)
		{
			var file_link = "http://" + document.domain + "/antfs/" + this.mainObject.id;
			var file_linkPub = "http://" + document.domain + "/files/" + this.mainObject.id;

			var button = alib.ui.Button("Download File", {
				className:"b2", tooltip:"Download file to your computer", cls:this, link:file_link, 
				onclick:function() { window.open(this.link); }
			});
			var bcon = alib.dom.createElement("div", this.m_con);
			alib.dom.styleSet(bcon, "margin-bottom", "10px");
			button.print(bcon);

			// Add public link
			alib.dom.createElement("div", this.m_con, "Public Link:");
			alib.dom.createElement("div", this.m_con, file_linkPub);
		}
	},

	/**
	 * Upload a new file
	 */
	uploadFile:function()
	{
		var cfupload = new AntFsUpload();
		cfupload.setFolderId(this.mainObject.getValue("folder_id")); // Set the root folder
		cfupload.setFileId(this.mainObject.id); // Restrict to update this file
		cfupload.cbData.cls = this;
		cfupload.onUploadFinished = function()
		{
			this.cbData.cls.mainObject.save();
		}
		cfupload.showDialog();
	}
}
