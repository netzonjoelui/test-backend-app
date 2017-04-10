{
	name:"set_password",
	title:"Set and reset password for user",
	mainObject:null,
	formObj:null,

	/**
	 * Main entry point
	 *
	 * @param {DOMElement} con The container for this plugin
	 */
	main:function(con)
	{
		this.con = con;

		this.inputPassword = null;
		this.inputPasswordVerify = null;

		this.buildInterface();

		// Listen for edit mode change
		/*
		alib.events.listen(this.formObj, "changemode", function(evnt){ 
			//evnt.data.plCls.formObj.editMode;
			evnt.data.plCls.buildInterface();
		}, {plCls:this});
		*/
	},

	save:function()
	{
		if (this.inputPassword != null && this.inputPasswordVerify != null)
		{
			// Empty - do nothing
			if (this.inputPassword.value == "")
			{
				this.onsave();
				return;
			}

			if (this.inputPassword.value != this.inputPasswordVerify.value)
			{
				alert("Passwords do not match");
				this.inputPassword.focus();
				this.onsave();
				return;
			}

			var ajax = new CAjax("json");
			ajax.cbData.cls = this;
			ajax.onload = function(ret)
			{
				if (ret['error'])
					alert(ret['error']);

				this.cbData.cls.onsave();
			}
			ajax.exec("/controller/User/setPassword", [
					["uid", this.mainObject.id], 
					["new_password", this.inputPassword.value], 
					["verify_password", this.inputPasswordVerify.value]
			]);
		}
		else
		{
			// Nothing needs to be done so just call onsave
			this.onsave();
		}
	},

	// Object loader callback - required
	onsave:function()
	{
	},

	/**
	 * If the object was saved reload the reflect any default values
	 */
	objectsaved:function()
	{
	},

	/**
	 * Watch for obj_reference to change
	 *
	 * @param {string} fname The name of the field to update
	 * @param {string} fvale The value the field was set to
	 * @param {string} fkeyName If fkey or object type then fkey will be the label value
	 */
	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
	},

	/**
	 * Build timeing interface based on object referenced fields or manual precise time
	 */
	buildInterface:function()
	{
		this.con.innerHTML = "";

		var lbl = alib.dom.createElement("span", this.con, "&nbsp;&nbsp;Enter Password: ");

		this.inputPassword = alib.dom.createElement("input", this.con);
		this.inputPassword.type = "password";
		alib.dom.styleSetClass(this.inputPassword, "fancy");

		var lbl = alib.dom.createElement("span", this.con, "&nbsp;&nbsp;&nbsp;Enter Password Again: ");

		this.inputPasswordVerify = alib.dom.createElement("input", this.con);
		this.inputPasswordVerify.type = "password";
		alib.dom.styleSetClass(this.inputPasswordVerify, "fancy");
	}
}
