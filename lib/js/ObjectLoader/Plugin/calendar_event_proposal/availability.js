{
    name:"availability",
    title:"Attendee Availability",
    mainObject:null, // will be set by form class, is a reference to edited object    
    
    /**
     * Called once form is fully loaded
     *
     * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
     */
    main:function(con)
    {
         var fields = this.mainObject.getFields();        
            for (var i = 0; i < fields.length; i++)
            {
                var currField = fields[i];
                var objFieldType = currField.type;
                var objFieldName = currField.name;            
                var objFieldValue = this.mainObject.getValue(objFieldName);                
                switch(objFieldName)
                {
                    case "attendees":
                        objFieldValue = this.mainObject.getMultiValues(objFieldName);                        
                        break;
                    default:                        
                        break;
                }
            }
       
        this.mainCon = con;
        this.divDateCon = null;
        this.eventCoordId = this.mainObject.id;        
        
        this.memberDateSaveData = new Object();     // Date and Time save data
        this.dateFormData = new Object();     // Date and Time form data
        
        if(this.eventCoordId)
        {
            this.loadData();
        }
        else
        {
            this.memberDateSaveData.attendees = new Object();
            this.memberDateSaveData.dateAttendee = new Object();
            this.memberDateSaveData.optionalTimes = new Object();            
            this.buildInterface();
        }
    },
    
    /**
    * Will load the data needed, then build the interface
    */
    loadData:function()
    {
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.memberDateSaveData = ret;
            this.cbData.cls.buildInterface();
        };
        var args = new Array();
        args[args.length] = ["eventCoordId", this.eventCoordId];
        ajax.exec("/controller/Calendar/getEventProposalData", args);
    },
    
    
    /**
     * This function is called by the object form every time any field is updated in the mainObject.
     */
    onMainObjectValueChange:function(fname, fvalue, fkeyName)
    {        
        if(fname == "attendees")
        {
            // check if there's new member added
            if(!this.memberDateSaveData.attendees[fvalue])
            {
                // get the member data from multi value posted by member plugin
                var memberValue = this.mainObject.getValueName("attendees", fvalue).split(":");
                
                // if the data retrieved is null, load the data from the database
                if(typeof memberValue[1] == "undefined" || memberValue[1].length == 0)                
                {
                    var functCls = this;
                    var callback = function()
                    {                        
                        functCls.loadData();
                    }   
                    
                    window.setTimeout(callback, 3000);        
                }
                else
                {
                    // add the newly added member to the attendee object
                    this.memberDateSaveData.attendees[fvalue] = new Object();
                    this.memberDateSaveData.attendees[fvalue].id = fvalue;                
                    this.memberDateSaveData.attendees[fvalue].name = memberValue[1];
                    
                    // rebuild the attendee table
                    this.mainCon.removeChild(this.memberDateTbl.m_table);
                    this.buildMemberDateRow();
                }
            }
            
        }
    },

    /**
     * Will be called by AntObjectLoader_Form when the user saves changes. 
     * This MUST call this.onsave when finsihed or the browser will hang.
     */
    save:function()
    {
        this.eventCoordId = this.mainObject.id;            
        
        var args = new Array();
        var x = 0;
        if(this.memberDateSaveData.optionalTimes)
        {            
            for(optionalDate in this.memberDateSaveData.optionalTimes)
            {
                x++;
                args[args.length] = ['optionalDate_' + x, this.memberDateSaveData.optionalTimes[optionalDate].date];
                args[args.length] = ['dateIndex_' + x, this.memberDateSaveData.optionalTimes[optionalDate].date_index];
                args[args.length] = ['tsStart_' + x, this.memberDateSaveData.optionalTimes[optionalDate].ts_start];
                args[args.length] = ['tsEnd_' + x, this.memberDateSaveData.optionalTimes[optionalDate].ts_end];
                args[args.length] = ['allDay_' + x, this.memberDateSaveData.optionalTimes[optionalDate].all_day];
                args[args.length] = ['id_' + x, this.memberDateSaveData.optionalTimes[optionalDate].id];
                args[args.length] = ['eventCoordId_' + x, this.eventCoordId];
            }                
        }        
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.onsave();            
        };
        ajax.exec("/controller/Calendar/saveEventProposalDate", args);
    },

    /**
     * Inform the AntObjectLoader_Form object that this plugin has finished saving changes
     */
    onsave:function()
    {
    },
    
    /**
     * Will be called by AntObjectLoader_Form when the object is deleted
     */
    remove:function(id)
    {        
    	this.onremove();
    },
    
    /**
     * Inform the AntObjectLoader_Form object that this plugin has finished deleting reminder
     */
    onremove:function()
    {        
    },

    /**
     * Private function for loading interface
     */
    load:function()
    {
        // Only load this plugin if we are working with a new case        
        this.buildInterface();
    },

    /**
     * Private function for building interface
     */
    buildInterface:function()
    {   
        // create date / time options
        var dateObj = new Date();
        var month = dateObj.getMonth() + 1;
        var day = ('0' + dateObj.getDate()).slice(-2);
        var year = dateObj.getFullYear();
        var currentDate = month + "/" + day + "/" + year;

        if(!this.divDateCon)
        {
            this.divDateCon = alib.dom.createElement("div", this.mainCon);            
            alib.dom.styleSet(this.divDateCon, "marginBottom", "10px");
        }
        
        // Start Date - input calendar
        this.dateFormData.txtStartDate = createInputAttribute(alib.dom.createElement("input"), "text", "startDate", null, "100px", currentDate);        
        
        // start time - input time
        this.dateFormData.txtStartTime = createInputAttribute(alib.dom.createElement("input"), "text", "startTime", null, "70px", "08:00 AM");        
        
        // label to
        this.dateFormData.lblTo = alib.dom.createElement("span", null, "to");
		alib.dom.styleSetClass(this.dateFormData.lblTo, "formValueLabel");
        
        // end date - input calendar
        this.dateFormData.txtEndDate = createInputAttribute(alib.dom.createElement("input"), "text", "endDate", null, "100px", currentDate);        
        
        // end time - input time
        this.dateFormData.txtEndTime = createInputAttribute(alib.dom.createElement("input"), "text", "endTime", null, "70px", "09:00 AM");        
        
        // all day event - checkbox
        this.dateFormData.chkAllDay = createInputAttribute(alib.dom.createElement("input"), "checkbox", "allDay", "All Day");                
        
        // add button - input button
        this.dateFormData.btnAdd = createInputAttribute(alib.dom.createElement("input"), "button", "btnAdd", null, null, "Add Time");
        //alib.dom.styleSet(this.dateFormData.btnAdd, "padding", "0");
        //alib.dom.styleSet(this.dateFormData.btnAdd, "marginTop", "-3px");
		alib.dom.styleSetClass(this.dateFormData.btnAdd, "b1");
                
        // build form inside div
        buildFormInputDiv(this.dateFormData, this.divDateCon)
        divClear(this.divDateCon);
        
        // start date - calendar autocomplete        
        var start_ac = new CAutoCompleteCal(this.dateFormData.txtStartDate);
        
        // end date - calendar autocomplete        
        new CAutoCompleteCal(this.dateFormData.txtEndDate);
        
        // start time - autocomplete and time validation
        new CAutoCompleteTime(this.dateFormData.txtStartTime);
        
        // end time - autocomplete and time validation
        new CAutoCompleteTime(this.dateFormData.txtEndTime);
        
        // start date on change
        this.dateFormData.txtStartDate.cls = this;
        this.dateFormData.txtStartDate.onchange = function()
        {
            this.cls.correctDate();
        }
        
        // start time on change
        this.dateFormData.txtStartTime.cls = this;
        this.dateFormData.txtStartTime.onchange = function()
        {
            this.cls.correctDate();
        }
        
        // end date on change
        this.dateFormData.txtEndDate.cls = this;
        this.dateFormData.txtEndDate.onchange = function()
        {
            this.cls.correctDate();
        }
        
        // end time on change
        this.dateFormData.txtEndTime.cls = this;
        this.dateFormData.txtEndTime.onchange = function()
        {
            this.cls.correctDate();
        }
        
                
        // add button onclick function
        this.dateFormData.btnAdd.cls = this;
        this.dateFormData.btnAdd.m_dateFormData = this.dateFormData;        
        this.dateFormData.btnAdd.onclick = function()
        {
            var startDate = this.m_dateFormData.txtStartDate.value;
            var endDate = this.m_dateFormData.txtEndDate.value;            
            var startTime = this.m_dateFormData.txtStartTime.value;            
            var endTime = this.m_dateFormData.txtEndTime.value;
            
            if(this.m_dateFormData.chkAllDay.checked)
                var startDateTs = new Date(startDate + " 12:01 AM");
            else
                var startDateTs = new Date(startDate + " " + startTime);
                
            var endDateTs = new Date(endDate + " " + endTime);
                        
            startTs = startDateTs.getTime();
            endTs = endDateTs.getTime();
            
            var optionalDate = startDate;
            
            if(startDate != endDate)
                optionalDate += " - " + endDate;
                
            if(!this.m_dateFormData.chkAllDay.checked)
            {
                optionalDate += "<br/>(" + startTime;
                optionalDate += " - " + endTime + ")";
            }
            
            var pattern = "\/", reg = new RegExp(pattern, "g");
            var dateIndex = optionalDate.replace(reg, "_");
            
            if(this.cls.memberDateSaveData.optionalTimes[dateIndex])
                return;
                
            this.cls.memberDateSaveData.optionalTimes[dateIndex] = new Object();            
            this.cls.memberDateSaveData.optionalTimes[dateIndex].id = 0;                
            this.cls.memberDateSaveData.optionalTimes[dateIndex].sort = startTs;
            this.cls.memberDateSaveData.optionalTimes[dateIndex].date_index = dateIndex;            
            this.cls.memberDateSaveData.optionalTimes[dateIndex].date = optionalDate;
            this.cls.memberDateSaveData.optionalTimes[dateIndex].all_day = this.m_dateFormData.chkAllDay.checked;            
            
            if(this.m_dateFormData.chkAllDay.checked)
            {
                this.cls.memberDateSaveData.optionalTimes[dateIndex].ts_start = this.m_dateFormData.txtStartDate.value;
                this.cls.memberDateSaveData.optionalTimes[dateIndex].ts_end = this.m_dateFormData.txtEndDate.value
            }
            else
            {
                this.cls.memberDateSaveData.optionalTimes[dateIndex].ts_start = this.m_dateFormData.txtStartDate.value + " " + this.m_dateFormData.txtStartTime.value;
                this.cls.memberDateSaveData.optionalTimes[dateIndex].ts_end = this.m_dateFormData.txtEndDate.value + " " + this.m_dateFormData.txtEndTime.value;
            }
            
            if(this.cls.memberDateTbl)
                this.cls.mainCon.removeChild(this.cls.memberDateTbl.m_table);
                
            this.cls.buildMemberDateRow();
        }
        
        // all day event - onclick of checkbox
        this.dateFormData.chkAllDay.m_dateFormData = this.dateFormData;
        this.dateFormData.chkAllDay.onclick = function()
        {
            if(this.checked)
            {
                this.m_dateFormData.txtStartTime.setAttribute("disabled", "disabled");
                this.m_dateFormData.txtEndTime.setAttribute("disabled", "disabled");
            }
            else
            {
                this.m_dateFormData.txtStartTime.removeAttribute("disabled");
                this.m_dateFormData.txtEndTime.removeAttribute("disabled");
            }
        }
        
        // if new event proposal        
        if(this.eventCoordId)
        {
            if(this.memberDateTbl)
                this.mainCon.removeChild(this.memberDateTbl.m_table);
                
            this.buildMemberDateRow();
        }                    
    },
    
    /**
    * Private function for Building Optional Member Row
    */
    buildMemberDateRow:function()
    {   
        this.memberDateTbl = new CToolTable("100%");
        this.memberDateTbl.print(this.mainCon);
        
        // clear the current account table rows
        this.memberDateTbl.clear();
            
        // data for optional date and time
        this.memberDateTbl.addHeader("", "center");
        for(optionalDate in this.memberDateSaveData.optionalTimes)
        {
            var optionalDateData = this.memberDateSaveData.optionalTimes[optionalDate];
            this.memberDateTbl.addHeader(optionalDateData.date, "center");           
        }
        
        // loop the attendee data
        for(attendee in this.memberDateSaveData.attendees)
        {
            var firstCell = true;
            var attendeeData = this.memberDateSaveData.attendees[attendee];
            
            if(attendeeData.name)
            {
                // then loop the optional date/time data, these will create the column header
                for(optionalDate in this.memberDateSaveData.optionalTimes)
                {
                    // create an entry index using attendee id and optional date id                
                    var optionalDateData = this.memberDateSaveData.optionalTimes[optionalDate];                
                    var dateAttendeeIndex = optionalDateData.id + "_" + attendeeData.id;
                    var dateAttendeeData = this.memberDateSaveData.dateAttendee[dateAttendeeIndex];
                    
                    // create the column and rows
                    if(dateAttendeeData)
                    {                    
                        var divResponse = (dateAttendeeData.response==1)?"Available":"Unavailable";                    
                        if(firstCell)
                        {
                            var rw = this.memberDateTbl.addRow();
                            rw.addCell(attendeeData.name);
                        }
                        
                        rw.addCell(divResponse, 1, "center", null, null, "CalendarUserStatus"+((dateAttendeeData.response==1)?'1':'3'));
                    }
                    else
                    {                    
                        if(firstCell)
                        {
                            var rw = this.memberDateTbl.addRow();
                            rw.addCell(attendeeData.name);
                        }   
                        rw.addCell("No Reply", 1, "center", null, null, "CalendarUserStatus2");
                    }                
                    firstCell = false;
                }
            }
        }

		// Add create / delete links
		var rw = this.memberDateTbl.addRow();
		rw.addCell("&nbsp;");
        for(optionalDate in this.memberDateSaveData.optionalTimes)
        {
            var divFooter = alib.dom.createElement("div");
            
            divFooter.appendChild(this.createEventLink(optionalDateData));
            
            var label = alib.dom.createElement("label", divFooter, " | ");
            
            divFooter.appendChild(this.createDeleteLink(optionalDateData));
            rw.addCell(divFooter, false, "center");
        }
    },
    
    /**
    * Private function for Building the create event link
    */
    createEventLink:function(optionalDateData)
    {
        var ceLink = alib.dom.createElement("a");
        ceLink.innerHTML = "create event";
        ceLink.m_optionalDateData = optionalDateData;
        ceLink.m_mainObject = this.mainObject;
        ceLink.m_memberDateSaveData = this.memberDateSaveData;
        ceLink.href = "javascript:void(0);";
        ceLink.cls = this;
        ceLink.onclick = function()
        {
             // ask if the user wants to delete the proposal
            if(confirm("You would like to close the meeting proposal?"))
            {
                this.m_mainObject.setValue("f_closed", 't'); 
                this.m_mainObject.save();
				alib.events.listen(this.m_mainObject, "save", function(evt) { 
						evt.data.olCls.close();
					},
					{olCls: this.cls.olCls}
				);
            }

			// Setup params to forward
			var params = new Array();
                
			var attendees = new Array();
            for(attendee in this.m_memberDateSaveData.attendees)
            {
                var attendeeData = this.m_memberDateSaveData.attendees[attendee];
				attendees.push(attendeeData.id);
            }            
			params.push(["attendees", attendees]);

			params.push(["name", this.cls.mainObject.getValue("name")]);
			params.push(["location", this.cls.mainObject.getValue("location")]);
			params.push(["ts_start", this.m_optionalDateData.ts_start]);
			params.push(["ts_end", this.m_optionalDateData.ts_end]);
            
			// Load object form with params
			loadObjectForm("calendar_event", null, null, null, params);
        }
        
        return ceLink;
    },
    
    /**
    * Private function for Building create delete link
    */
    createDeleteLink:function(optionalDateData)
    {
        var deleteLink = alib.dom.createElement("a");
        
        deleteLink.href = "javascript: void(0);";                
        deleteLink.innerHTML = "delete option"; 
        deleteLink.m_optionalDateData = optionalDateData;
        deleteLink.cls = this;        
        deleteLink.onclick = function()
        {
            // confirm if user is sure to perform delete action
            if(!confirm("Are you sure to delete this date?"))
                return;
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this.cls;
            ajax.onload = function(ret)
            {
                delete this.cbData.cls.memberDateSaveData.optionalTimes[ret.dateIndex];
                
                this.cbData.cls.mainCon.removeChild(this.cbData.cls.memberDateTbl.m_table);                
                this.cbData.cls.buildMemberDateRow();
                
                ALib.statusShowAlert("Optional date: " + ret.optionalDate.replace("<br/>"," ") + " has been Removed!", 3000, "bottom", "right");
            };
            
            var args = new Array();
            args[args.length] = ['id', this.m_optionalDateData.id];
            args[args.length] = ['optionalDate', this.m_optionalDateData.date];
            args[args.length] = ['dateIndex', this.m_optionalDateData.date_index];
            ajax.exec("/controller/Calendar/deleteEventProposalDate", args);
        }
        
        return deleteLink;
    },
    
    /**
    * Private function for correcting the end date/time
    * if the start date is greater than end date/time
    */
    correctDate:function()
    {
        var startDate = this.dateFormData.txtStartDate.value;
        var endDate = this.dateFormData.txtEndDate.value;            
        var startTime = this.dateFormData.txtStartTime.value;            
        var endTime = this.dateFormData.txtEndTime.value;
        
        if(this.dateFormData.chkAllDay.checked)
            var startDateTs = new Date(startDate + " 12:01 AM");
        else
            var startDateTs = new Date(startDate + " " + startTime);
            
        var endDateTs = new Date(endDate + " " + endTime);
                    
        startTs = startDateTs.getTime();
        endTs = endDateTs.getTime();
        
        if(startTs>=endTs)
        {
            endDateTs.setTime(startDateTs.getTime() + (60 * 60 * 1000));
            endTs = endDateTs.getTime();
            var month = endDateTs.getMonth() + 1;
            var day = ('0' + endDateTs.getDate()).slice(-2);
            var year = endDateTs.getFullYear();
            var hours = endDateTs.getHours()
            var minutes = ('0' + endDateTs.getMinutes()).slice(-2);
            
            if (hours > 12) 
            {
              hours = hours - 12;
              time = " PM";
            }
            else                                  
              time = " AM";
            
            if (hours == 12)
              time = " PM";
              
            if (hours == 00)                
              hours = "12";                
            
            endDate = month + "/" + day + "/" + year;
            endTime = hours + ":" + minutes + time;
            
            this.dateFormData.txtEndDate.value = endDate;
            this.dateFormData.txtEndTime.value = endTime;
        }
    }
}
