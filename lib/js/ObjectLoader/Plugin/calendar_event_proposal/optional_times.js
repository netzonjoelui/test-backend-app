{
    name:"optional_times",
    title:"Optional Times",
    mainObject:null, // will be set by form class, is a reference to edited object    

    /**
    * Called once form is fully loaded
    *
    * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
    */
    main:function(con)
    {
        this.mainCon = con;
        this.eventCoordId = this.mainObject.id;
        this.currentTheme = "softblue";
        this.dateTbl = new CToolTable("100%");
        
        this.dateFormData = new Object();     // Date and Time form data
        this.dateSaveData = new Object();     // Date and Time save data
        
        if(this.eventCoordId)
        {
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.dateSaveData = ret;
                this.cbData.cls.buildInterface();
            };
            
            var args = new Array();
            args[args.length] = ["eventCoordId", this.eventCoordId];
            ajax.exec("/controller/Calendar/getEventProposalData", args);
        }
        else
        {
            this.dateSaveData.optionalTimes = new Object();
            this.buildInterface();
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
        if(this.dateSaveData.optionalTimes)
        {            
            for(optionalDate in this.dateSaveData.optionalTimes)
            {
                x++;
                args[args.length] = ['optionalDate_' + x, this.dateSaveData.optionalTimes[optionalDate].date];
                args[args.length] = ['dateIndex_' + x, this.dateSaveData.optionalTimes[optionalDate].date_index];
                args[args.length] = ['tsStart_' + x, this.dateSaveData.optionalTimes[optionalDate].ts_start];
                args[args.length] = ['tsEnd_' + x, this.dateSaveData.optionalTimes[optionalDate].ts_end];
                args[args.length] = ['allDay_' + x, this.dateSaveData.optionalTimes[optionalDate].all_day];
                args[args.length] = ['id_' + x, this.dateSaveData.optionalTimes[optionalDate].id];
                args[args.length] = ['eventCoordId_' + x, this.eventCoordId];
            }                
        }
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            for(optionalDate in ret)
                this.cbData.cls.dateSaveData.optionalTimes[optionalDate].id = ret[optionalDate];
            
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
        var dateObj = new Date();
        var month = dateObj.getMonth() + 1;
        var day = ('0' + dateObj.getDate()).slice(-2);
        var year = dateObj.getFullYear();
        var currentDate = month + "/" + day + "/" + year;        

        // create the table element
        var table = alib.dom.createElement("table", this.mainCon);
        table.setAttribute("cellpadding", 2);
        table.setAttribute("cellspacing", 2);
        alib.dom.styleSet(table, "width", "100%");

        // create the tbody element
        var tbody = alib.dom.createElement("tbody", table);
        
        // start date - input calendar
        this.dateFormData.txtStartDate = createInputAttribute(alib.dom.createElement("input"), "text", "startDate", "Start Date", "100px", currentDate);
        
        // end date - input calendar
        this.dateFormData.txtEndDate = createInputAttribute(alib.dom.createElement("input"), "text", "endDate", "End Date", "100px", currentDate);
        
        // all day event - checkbox
        this.dateFormData.chkAllDay = createInputAttribute(alib.dom.createElement("input"), "checkbox", "allDay");        
        
        // start time - input time
        this.dateFormData.txtStartTime = createInputAttribute(alib.dom.createElement("input"), "text", "startTime", "Start Time", "70px", "08:00 AM");
        
        // end time - input time
        this.dateFormData.txtEndTime = createInputAttribute(alib.dom.createElement("input"), "text", "endTime", "End Time", "70px", "09:00 AM");
        
        // build the input form
        buildFormInput(this.dateFormData, tbody);
        
        
        // add button - input button
        var tr = buildTdLabel(tbody); 
        tr.firstChild.setAttribute("colspan", 2);
        this.dateFormData.btnAdd = createInputAttribute(alib.dom.createElement("input", tr.firstChild), "button", "btnAdd", null, null, "Add Date/Time Option");
        
        // add button onclick function
        this.dateFormData.btnAdd.cls = this;
        this.dateFormData.btnAdd.m_dateFormData = this.dateFormData;
        this.dateFormData.btnAdd.onclick = function()
        {
            var startDate = this.m_dateFormData.txtStartDate.value;
            var endDate = this.m_dateFormData.txtEndDate.value;            
            var startTime = this.m_dateFormData.txtStartTime.value;            
            var endTime = this.m_dateFormData.txtEndTime.value;
            var startTs = new Date(startDate + " " + startTime);
            var endTs = new Date(endDate + " " + endTime);
            
            if(startTs.getTime() >= endTs.getTime() && !this.m_dateFormData.chkAllDay.checked)
            {
                alert("Invalid date and time.")
                return;
            }
            
            var optionalDate = startDate;
            
            if(startDate != endDate)
                optionalDate += " - " + endDate;
                
            if(!this.m_dateFormData.chkAllDay.checked)
            {
                optionalDate += " (" + startTime;
                optionalDate += " - " + endTime + ")";
            }
            
            var pattern = "\/", reg = new RegExp(pattern, "g");
            var dateIndex = optionalDate.replace(reg, "_");
            
            if(this.cls.dateSaveData.optionalTimes[dateIndex])
                return;
            
            
            this.cls.dateSaveData.optionalTimes[dateIndex] = new Object();            
            this.cls.dateSaveData.optionalTimes[dateIndex].id = 0;                
            this.cls.dateSaveData.optionalTimes[dateIndex].date_index = dateIndex;                
            this.cls.dateSaveData.optionalTimes[dateIndex].date = optionalDate;
            this.cls.dateSaveData.optionalTimes[dateIndex].all_day = this.m_dateFormData.chkAllDay.checked;
            
            if(this.m_dateFormData.chkAllDay.checked)
            {
                this.cls.dateSaveData.optionalTimes[dateIndex].ts_start = this.m_dateFormData.txtStartDate.value;
                this.cls.dateSaveData.optionalTimes[dateIndex].ts_end = this.m_dateFormData.txtEndDate.value
            }
            else
            {
                this.cls.dateSaveData.optionalTimes[dateIndex].ts_start = this.m_dateFormData.txtStartDate.value + " " + this.m_dateFormData.txtStartTime.value;
                this.cls.dateSaveData.optionalTimes[dateIndex].ts_end = this.m_dateFormData.txtEndDate.value + " " + this.m_dateFormData.txtEndTime.value;
            }
            this.cls.buildDateRow();            
        }
        
        // all day checkbox onclick function
        var label = alib.dom.createElement("label");        
        label.innerHTML = "All day event";
        this.dateFormData.chkAllDay.parentNode.insertBefore(label, this.dateFormData.chkAllDay);
        
        this.dateFormData.chkAllDay.m_dateFormData = this.dateFormData;
        this.dateFormData.chkAllDay.onclick = function()
        {
            var trStartTime = this.m_dateFormData.txtStartTime.parentNode.parentNode;
            var trEndTime = this.m_dateFormData.txtEndTime.parentNode.parentNode;
            if(this.checked)
            {
                alib.dom.styleSet(trStartTime, "display", "none");
                alib.dom.styleSet(trEndTime, "display", "none");
            }
            else
            {
                trStartTime.removeAttribute("style");
                trEndTime.removeAttribute("style");
            }
        }
        
        // print Optional Date CToolTable
        this.dateTbl.print(this.mainCon);
        
        // start date - calendar autocomplete
        var a_CalStart = alib.dom.createElement("span", this.dateFormData.txtStartDate.parentNode);
        a_CalStart.innerHTML = "<img src='/images/calendar.gif' border='0'>";
        var start_ac = new CAutoCompleteCal(this.dateFormData.txtStartDate, a_CalStart);
        
        // end date - calendar autocomplete
        var a_CalEnd = alib.dom.createElement("span", this.dateFormData.txtEndDate.parentNode);
        a_CalEnd.innerHTML = "<img src='/images/calendar.gif' border='0'>";
        new CAutoCompleteCal(this.dateFormData.txtEndDate, a_CalEnd);
        
        // start time - autocomplete and time validation
        new CAutoCompleteTime(this.dateFormData.txtStartTime);
        
        // end time - autocomplete and time validation
        new CAutoCompleteTime(this.dateFormData.txtEndTime);
        
        this.buildDateRow();
    },
    
    /**
    * Private function for Building Optional Date Row
    */
    buildDateRow:function()
    {
            if(!this.dateSaveData.optionalTimes)
                return;
                
            // clear the current account table rows
            this.dateTbl.clear();
        
            for(optionalDate in this.dateSaveData.optionalTimes)
            {
                var optionalDateData = this.dateSaveData.optionalTimes[optionalDate];                
                var rw = this.dateTbl.addRow();
                
                // add row date
                rw.addCell(optionalDateData.date);
                
                // delete link column
                var deleteLink = alib.dom.createElement("a");
                
                deleteLink.innerHTML = "<img src='/images/themes/" + this.currentTheme + "/icons/deleteTask.gif' border='0' />";
                deleteLink.href = "javascript: void(0);";                
                deleteLink.m_optionalDateData = optionalDateData;
                deleteLink.cls = this;
                deleteLink.m_rw = rw;
                deleteLink.onclick = function()
                {
                    // confirm if user is sure to perform delete action
                    if(!confirm("Are you sure to delete this date?"))
                        return;
                    
                    var args = new Array();
                    args[args.length] = ['id', this.m_optionalDateData.id];
                    args[args.length] = ['optionalDate', this.m_optionalDateData.date];
                    args[args.length] = ['dateIndex', this.m_optionalDateData.date_index];
                    
                    ajax = new CAjax('json');
                    ajax.cbData.cls = this.cls;
                    ajax.cbData.rw = this.m_rw;
                    ajax.onload = function(ret)
                    {
                        delete this.cbData.cls.dateSaveData.optionalTimes[ret.dateIndex];

                        this.cbData.rw.deleteRow();
                        ALib.statusShowAlert("Optional date: " + ret.optionalDate + " has been Removed!", 3000, "bottom", "right");
                        
                        // remove the optional date cell in availability table
                        if(ret.id>0)
                        {
                            var aTbl = document.getElementById("availabilityTable");                                                        
                            if(aTbl)
                            {                               
                                for (var i = 0, row; row = aTbl.rows[i]; i++) 
                                {
                                    for (var j = 0, col; col = row.cells[j]; j++) 
                                    {                                        
                                        if(col.id == "rwCell_"+ret.id)
                                            row.removeChild(col);
                                    }    
                                }
                            }
                        }
                    };
                    ajax.exec("/controller/Calendar/deleteEventProposalDate", args);
                }            
                rw.addCell(deleteLink, null, "center", null, null, "CTTRowTwoBold");
        }    
    },
}
