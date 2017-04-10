{
	name:"calendar_sel",
	title:"Event Calendar Selector",
	mainObject:null,

	main:function(con)
	{
		this.con = con;
		this.buildInterface();
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
		if (this.mainObject && fname == "calendar")
		{
			// TODO: Possibly update input if value changes
		}
	},

	buildInterface:function()
	{
		var sel = alib.dom.createElement("select", this.con);
		sel.mainObject = this.mainObject;
		sel.onchange = function()
		{
			this.mainObject.setValue("calendar", this.value);
		}

		/*var cbdone = function(ret, sel, cls)
		{			
			var calid = cls.mainObject.getValue("calendar");

			// Get default calendar
			if (ret.myCalendars.length && !calid)
			{
				for(calendar in ret.myCalendars)
				{
					var currentCalendar = ret.myCalendars[calendar];
					if (currentCalendar.default || ret.myCalendars.length==1)
					{
						cls.mainObject.setValue("calendar", currentCalendar.id);
						calid = currentCalendar.id;
					}
				}
			}

			var bCalFound = false;

			for(calendar in ret.myCalendars)
            {
				var currentCalendar = ret.myCalendars[calendar];
				sel[sel.length] = new Option(unescape(currentCalendar.name), currentCalendar.id, false, (currentCalendar.id == calid)?true:false);

				if (currentCalendar.id == calid)
					bCalFound = true;
			}

			for(calendar in ret.otherCalendars)
            {
                var currentCalendar = ret.otherCalendars[calendar];
				sel[sel.length] = new Option(unescape(currentCalendar.name), currentCalendar.id, false, (currentCalendar.id == calid)?true:false);

				if (currentCalendar.id == calid)
					bCalFound = true;
			}

			
			// Calendar is not in users list of calendars. Add to form as read-only text.
			if (!bCalFound)
			{
			}
		}		
        var rpc = new CAjaxRpc("/controller/Calendar/getCalendars", "getCalendars", null, cbdone, [sel, this], AJAX_POST, true, "json");*/
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.cbData.sel = sel;
        ajax.onload = function(ret)
        {
            var calid = this.cbData.cls.mainObject.getValue("calendar");

            // Get default calendar
            if (ret.myCalendars.length && !calid)
            {
                for(calendar in ret.myCalendars)
                {
                    var currentCalendar = ret.myCalendars[calendar];
                    if (currentCalendar.default || ret.myCalendars.length==1)
                    {
                        this.cbData.cls.mainObject.setValue("calendar", currentCalendar.id);
                        calid = currentCalendar.id;
                    }
                }
            }

            var bCalFound = false;

            for(calendar in ret.myCalendars)
            {
                var currentCalendar = ret.myCalendars[calendar];
                this.cbData.sel[this.cbData.sel.length] = new Option(unescape(currentCalendar.name), currentCalendar.id, false, (currentCalendar.id == calid)?true:false);

                if (currentCalendar.id == calid)
                    bCalFound = true;
            }

            for(calendar in ret.otherCalendars)
            {
                var currentCalendar = ret.otherCalendars[calendar];
                this.cbData.sel[this.cbData.sel.length] = new Option(unescape(currentCalendar.name), currentCalendar.id, false, (currentCalendar.id == calid)?true:false);

                if (currentCalendar.id == calid)
                    bCalFound = true;
            }
        };
        ajax.exec("/controller/Calendar/getCalendars");
	}
}
