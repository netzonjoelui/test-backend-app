{
	name:"event_timevalidator",
	title:"Times Validator",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
		this.members = new Array();
		this.todelete = new Array();
		this.positions = new Array();
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
	},

	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
		if (this.mainObject && fname == "ts_start")
		{
			var ts_start = new Date(this.mainObject.getValue("ts_start"));
			var ts_end = new Date(this.mainObject.getValue("ts_end"));


			if (ts_start.getTime()>=ts_end.getTime())
			{
				var ts = calDateAddSubtract(ts_start, "minute", 30);
				this.mainObject.setValue("ts_end", (ts.getMonth()+1)+"/"+ts.getDate()+"/"+ts.getFullYear() + " " + calGetClockTime(ts));

				// Refresh the parent form
				if (this.olCls)
					this.olCls.subLoader.refresh();
			}
		}
	}
}
