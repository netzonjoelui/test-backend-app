{
	name:"approval_status",
	title:"Approval Status Manager",
	mainObject:null,

	main:function(con)
	{
		this.con = con;

		if (this.mainObject.id)
			this.buildInterface();
		else
			this.con.innerHTML = "Status will be available after object is saved";
	},

	save:function()
	{
		this.onsave();
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
		if (this.mainObject.id)
			this.buildInterface();

	},

	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
	},

	buildInterface:function()
	{
		this.con.innerHTML = "";

		if (this.mainObject.getValue("status") == "approved" || this.mainObject.getValue("status") == "declined")
		{
			var curStatus = alib.dom.createElement("div", this.con);
			curStatus.innerHTML = this.mainObject.getValueName("status")
								+ " by "
								+ this.mainObject.getValueName("owner_id")
								+ " on "
								+ this.mainObject.getValue("ts_status_change")
								+ " "; // add space for following link
		}

		switch (this.mainObject.getValue("status"))
		{
		case 'approved':
			var lnk = alib.dom.createElement("a", curStatus);
			lnk.href = "javascript:void(0);";
			lnk.pluginCls = this;
			lnk.innerHTML = "[change to 'declined']";
			lnk.onclick = function()
			{
				this.pluginCls.updateStatus('declined');
			}
			break;

		case 'declined':
			var lnk = alib.dom.createElement("a", curStatus);
			lnk.href = "javascript:void(0);";
			lnk.pluginCls = this;
			lnk.innerHTML = "[change to 'approved']";
			lnk.onclick = function()
			{
				this.pluginCls.updateStatus('approved');
			}
			break;

		case 'awaiting':
		default:
			// Create approve button
			var button = alib.ui.Button("Approve", {
				className : "b2",
				pluginCls : this,
				onclick : function()
				{
					this.pluginCls.updateStatus("approved");
				}
			});
			button.print(this.con);

			// Create decline button
			var button2 = alib.ui.Button("Decline", {
				className : "b3",
				pluginCls : this,
				onclick : function()
				{
					this.pluginCls.updateStatus("declined");
				}
			});
			button2.print(this.con);
			break;
		}
	},

	/**
	 * Update the status of this request
	 *
	 * Upon setting the status then the mainObject will be realoded and buildInterface will be called again
	 *
	 * @param {string} stat Either 'approved' or 'declined'
	 */
	updateStatus:function(stat)
	{
		this.con.innerHTML = "Updating status, please wait...";

		var args = [["oid", this.mainObject.id], ["status", stat]];
		ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			if (ret['error'])
			{
				alert("ERROR: " + ret['error']);
				this.cbData.cls.buildInterface(); // reload interface without any changes
			}
			else
			{
				this.cbData.cls.mainObject.load(); // load the new values. This will call this.objectsaved in the plugin for rebuilding inteface`
			}   
		};
		ajax.exec("/controller/Object/approvalChangeStatus", args);
	}
}
