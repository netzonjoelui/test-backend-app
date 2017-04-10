/****************************************************************************
*    
*    Class:        CWidCalendar
*
*    Purpose:    Calendar widget
*
*    Author:        joe, sky.stebnicki@aereus.com
*                Copyright (c) 2007 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidCalendar()
{
	this.title = "Calendar";
    this.m_container = null;    // Set by calling process
    this.appNavname = null;

    this.m_menus = new Array();
}

/**
 * Entry point for application
 *
 * @public
 * @this {CWidCalendar}
 */
CWidCalendar.prototype.main = function()
{
    Ant.setHinst(this, "/widgets/calendar");
    this.m_container.innerHTML = "Loading, please wait...";
    var cls = this;
    
    // Set context menu
    var funct = function(cls, val) { cls.setSpan(val); };
    var sub2 = this.m_dm.addSubmenu("Show Events For");
    sub2.addEntry('1 Day', funct, null, "<div id='widg_home_cal_span_1'></div>", [cls, 1]);
    sub2.addEntry('2 Days', funct, null, "<div id='widg_home_cal_span_2'></div>", [cls, 2]);
    sub2.addEntry('3 Days', funct, null, "<div id='widg_home_cal_span_3'></div>", [cls, 3]);
    sub2.addEntry('4 Days', funct, null, "<div id='widg_home_cal_span_4'></div>", [cls, 4]);
    sub2.addEntry('5 Days', funct, null, "<div id='widg_home_cal_span_5'></div>", [cls, 5]);
    sub2.addEntry('6 Days', funct, null, "<div id='widg_home_cal_span_6'></div>", [cls, 6]);
    sub2.addEntry('1 Week', funct, null, "<div id='widg_home_cal_span_7'></div>", [cls, 7]);
    sub2.addEntry('2 Weeks', funct, null, "<div id='widg_home_cal_span_14'></div>", [cls, 14]);
    sub2.addEntry('3 Weeks', funct, null, "<div id='widg_home_cal_span_21'></div>", [cls, 21]);
    sub2.addEntry('4 Weeks', funct, null, "<div id='widg_home_cal_span_30'></div>", [cls, 30]);

    //this.getEvents();
    this.getSpan();
}

/**
 * Perform needed clean-up on app exit
 *
 * @public
 * @this {CWidCalendar}
 */
CWidCalendar.prototype.exit= function()
{
    Ant.clearHinst("/widgets/calendar");

    if (this.m_timer)
        clearTimeout(this.m_timer);

    for (var i = 0; i < this.m_menus.length; i++)
    {
        this.m_menus[i].destroyMenu();
    }

    this.m_container.innerHTML = "";
}

/**
 * Retrieve events from database and build table
 *
 * @public
 * @this {CWidCalendar}
 */
CWidCalendar.prototype.getEvents = function()
{
    var dateObj = new Date();
    var day = ('0' + dateObj.getDate()).slice(-2);
    var year = dateObj.getFullYear();
    var month = dateObj.getMonth() + 1;
    month = ('0' + month).slice(-2);
    
    var date_start = month + "/" + day + "/" + year;
    var date_end = date_start;
    
    if (this.m_timer)
        clearTimeout(this.m_timer);

    ajax = new CAjax('json');
    ajax.cbData.con = this.m_container;
    ajax.cbData.cls = this;
    ajax.cbData.currentDate = date_start;

    // Set callback once xml is loaded
    ajax.onload = function(ret)
    {
        this.cbData.con.innerHTML = "";
        
        if (ret.objects.length)
        {
            var eventCon = alib.dom.createElement("div", this.cbData.con);
            var lastDateStart = null;

            for(event in ret.objects)
            {
                var currentEvent = ret.objects[event];
                
                var eventId = currentEvent["id"];
                var recurId = currentEvent["recur_id"];
                var name = currentEvent["name"];
                var eventStart = currentEvent["ts_start"];
                var eventEnd = currentEvent["ts_end"];
                var allDay = currentEvent["all_day"];
                var color = currentEvent["color"];
                var calendarId = currentEvent["calendar"]["key"];
                
                var dateStart = eventStart.split(" ", 1).toString();
                var dateEnd = eventEnd.split(" ", 1).toString();
                
                var timeStart = this.cbData.cls.getEventTime(eventStart);
                var timeEnd = this.cbData.cls.getEventTime(eventEnd);
                
                if(lastDateStart !== dateStart)                
                {
                    var headerDate = new Date(dateStart);
                    var headerCon = alib.dom.createElement("div", eventCon);
                    
                    if(lastDateStart)
                        alib.dom.styleSet(headerCon, "margin-top", "10px");
                    
                    if(this.currentDate == dateStart)
                        headerDate = "Today";
                    else
                    {
                        headerDate = headerDate.toString("dddd, MMMM ,yyyy");
                        headerDate = headerDate.split(" ", 4).toString();
                        
                        var headerArr = headerDate.split(",");
                        headerDate = headerArr[0] + " " + headerArr[1] + " " + headerArr[2] + ", " + headerArr[3];                        
                    }
                    
                    // Event Header
                    var eventHeader = alib.dom.setElementAttr(alib.dom.createElement("div", headerCon), [["innerHTML", headerDate]]);
                    alib.dom.styleSet(eventHeader, "font-weight", "bold");

                    // Display horizontal line
                    var hrCon = alib.dom.createElement('div', headerCon);
                    hrCon.innerHTML = "&nbsp;";
                    alib.dom.styleSetClass(hrCon, "horizontalline");
                    alib.dom.styleSet(hrCon, "float", "left");
                    alib.dom.styleSet(hrCon, "min-width", "110px");
                    alib.dom.styleSet(hrCon, "margin-right", "10px");
                    
                    var hrCon = alib.dom.createElement('div', headerCon);
                    hrCon.innerHTML = "&nbsp;";
                    alib.dom.styleSetClass(hrCon, "horizontalline");
                    alib.dom.styleSet(hrCon, "overflow", "hidden");
                    
                    alib.dom.divClear(headerCon);
                }
                
                var detailsCon = alib.dom.createElement("div", eventCon);
                var timeCon = alib.dom.createElement("div", detailsCon);
                var nameCon = alib.dom.createElement("div", detailsCon);
                
                alib.dom.styleSet(detailsCon, "cursor", "pointer");
                alib.dom.styleSet(detailsCon, "margin-top", "3px");
                alib.dom.styleSet(timeCon, "float", "left");
                alib.dom.styleSet(timeCon, "min-width", "120px");
                alib.dom.styleSet(nameCon, "overflow", "hidden");
                
                // Event Time
                if (allDay == "t") // All Day event container
                    timeCon.innerHTML = "[All Day Event]"; 
                else
                    timeCon.innerHTML = timeStart + " - " + timeEnd;
                
                // Add event name
                nameCon.innerHTML = name;
                
                // Set onclick event
                detailsCon.eventId = eventId;
				detailsCon.cls = this.cbData.cls;
                detailsCon.onclick = function () 
                {   
					var ol = loadObjectForm("calendar_event", this.eventId);
					alib.events.listen(ol, "close", function(evt) {
						evt.data.cls.getEvents(); // refresh
					}, {cls:this.cls});
                };
                
                // Assign the last date start to check for new header
                lastDateStart = dateStart;
                
                alib.dom.divClear(detailsCon);
            }
        }
    };
    
    var args = new Array();
    
    // Set range
    var ccount = 1;
    args[args.length] = ["conditions[]", ccount];
    args[args.length] = ["condition_blogic_" + ccount, "and"];
    args[args.length] = ["condition_fieldname_" + ccount, "ts_start"];
    args[args.length] = ["condition_operator_" + ccount, "is_greater_or_equal"];
    args[args.length] = ["condition_condvalue_" + ccount, date_start];
    
    if (this.date_end)
        date_end = this.date_end;
        
    if(date_end < date_start)
        date_end = date_start;
    
    date_end = date_end + " 11:59:59 pm";    
    
    ccount = 2;
    args[args.length] = ["conditions[]", ccount];
    args[args.length] = ["condition_blogic_" + ccount, "and"];
    args[args.length] = ["condition_fieldname_" + ccount, "ts_end"];
    args[args.length] = ["condition_operator_" + ccount, "is_less_or_equal"];
    args[args.length] = ["condition_condvalue_" + ccount, date_end];
    
    // Add sort arguments    
    args[args.length] = ["order_by[]", "ts_start asc"];    
    args[args.length] = ["obj_type", "calendar_event"];
    ajax.exec("/controller/Calendar/getEvents", args);
}

/**
 * Put a check next to the appropriate option in the drop-down
 *
 * @public
 * @this {CWidCalendar}
 * @param {String} show_for     Displays the options for show span
 */
CWidCalendar.prototype.setShowSpan= function(show_for)
{
    if (show_for)
    {
        var id_arr = new Array(["1", "widg_home_cal_span_1"], ["2", "widg_home_cal_span_2"], 
                               ["3", "widg_home_cal_span_3"], ["4", "widg_home_cal_span_4"],
                               ["5", "widg_home_cal_span_5"], ["6", "widg_home_cal_span_6"],
                               ["7", "widg_home_cal_span_7"], ["14", "widg_home_cal_span_14"],
                               ["21", "widg_home_cal_span_21"], ["30", "widg_home_cal_span_30"]);
        for (var i = 0; i < id_arr.length; i++)
        {
            if (id_arr[i][0] == show_for)
                ALib.m_document.getElementById(id_arr[i][1]).innerHTML = "<img src='/images/themes/"+ Ant.theme.name+"/icons/circle_blue.png' />";
            else
                ALib.m_document.getElementById(id_arr[i][1]).innerHTML = '';
        }
    }
}

/**
 * Set number of days events will be shown for
 *
 * @public
 * @this {CWidCalendar}
 * @param {Int} numdays     Determine what calendar events to show
 */
CWidCalendar.prototype.setSpan= function(numdays)
{
    var args = new Array();
        
    args[0] = ['val', numdays];
    args[1] = ['appNavname', this.appNavname];

    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret)
            this.cls.date_end = unescape(ret);

        this.cls.m_container.innerHTML = "Loading, please wait...";
        this.cls.getEvents();
    };
    ajax.exec("/controller/Application/setCalTimespan", args);
    
    this.numdays = numdays;
    this.setShowSpan(numdays);
}

/**
 * Get number of days events will be shown for
 *
 * @public
 * @this {CWidCalendar}
 */
CWidCalendar.prototype.getSpan= function()
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret)
            this.cls.date_end = unescape(ret);
        
        this.cls.getEvents();
    };
    ajax.exec("/controller/Application/getCalTimespan", 
                [["appNavname", this.appNavname]]);
}

/**
 * Gets the event time
 *
 * @public
 * @this {CWidCalendar}
 * @param {Timestamp} eventTS       Event Time
 */
CWidCalendar.prototype.getEventTime = function(eventTS)
{
    var eventTimeParts = eventTS.split(" ");
    var result = "";
    
    for(parts in eventTimeParts)
    {
        if(parts > 0 && parts < (eventTimeParts.length-1))
        {
            result += eventTimeParts[parts] + " ";
        }
    }
    
    return result;
}

/**
 * Refresh the widget
 *
 * @public
 * @this {CWidCalendar}
 */
CWidCalendar.prototype.refresh = function()
{
    this.getEvents();
}
