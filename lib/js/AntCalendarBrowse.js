/**
 * @fileoverview This class handles building calendar browsers in the JS created UI
 *
 * Example:
 * <code>
 * 	var ob = new AntCalendarBrowse("customer");
 *	ob.print(document.body);
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntCalendarBrowse
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
function AntCalendarBrowse(appclass, calid)
{
	/**
	 * Optional AntView object used to manage resizing and rendering pages
	 *
	 * @type {AntView}
	 * @public
	 */
	this.antView = null;

	/**
	 * Child view used for displaying events
	 *
	 * @type {AntView}
	 * @public
	 */
	this.eventView = null;

	/**
	 * Outer container where to calendar resizes
	 *
	 * @type {DOMElement}
	 * @private
	 */
    this.outerCon = null;

	/**
	 * Toolbar container
	 *
	 * @type {DOMElement}
	 * @private
	 */
    this.toolbarCon = null;

	/**
	 * Current date
	 *
	 * @type {date}
	 * @private
	 */
	this.currentDate = new Date();

	/**
	 * Last date that was loaded
	 *
	 * @var {Date}
	 * @private
	 */
	this.lastDate = null;

	/**
	 * If we are in month view this is a handle to the tbody of the calendar
	 *
	 * @type {DOMTableBody}
	 * @private
	 */
	this.monthViewTbody = null;

	/**
	 * Label container used to display current date string
	 *
	 * @type {DOMElement}
	 */
	this.dateLabel = null;

	/**
	 * Application class refernced, this will be set of the browser is loaded by an applicatoin
	 *
	 * @var {AntApp}
	 */
    this.m_appclass = (appclass) ? appclass : null;

	/**
	 * Specific calendar id
	 *
	 * @var {int}
	 */
    this.m_cid = (calid) ? calid : null;

	/**
	 * Set a manual height for this browser, otherwise will be 100 percent of container
	 *
	 * @var {int}
	 */
    this.browserHeight = 0;

	/**
	 * Global settings object (minscale should be put in below)
	 *
	 * @var {Object}
	 */
    this.settings = new Object();

	/**
	 * Set scale for each minute in px
	 *
	 * IE does not handle fractional px very well so set to 1. All other browsers can deal with .75 px
	 *
	 * @var {float}
	 */
    this.min_scale = (alib.userAgent.ie) ? 1 : .75;

    // Set initial events range
    this.eventsRangeFrom = calDateAddSubtract(this.currentDate, 'day', -31);
    this.eventsRangeTo = calDateAddSubtract(this.currentDate, 'day', 31);

	/**
	 * Array of all events for a given range
	 *
	 * @var {Array}
	 */
    this.events = new Array();

	/**
	 * Array of calendars we are working with, set in loadSettings
	 *
	 * @var {Array}
	 */
    this.calendars = new Array();

	/**
	 * If set the list will automatically refresh eveny n number of seconds
	 *
	 * @var {int}
	 */
	this.refreshInterval = null;
}

/**
 * Print the browser inside a container
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {DOMElement} con The container that will house the browser
 */
AntCalendarBrowse.prototype.print = function(con)
{
	// Draw the browser
	this.outerCon = con;
    this.outerCon.innerHTML = " Loading...";

    // Load settings for calendar browser (onload will build the interface)
    this.loadSettings();
}

/**
 * Load user settings for the calendar browser
 */
AntCalendarBrowse.prototype.loadSettings = function()
{
    // Set defaults
    this.settings.defaultView = "day";
         
    // Get settings
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(resp)
    {
        if (resp)
        {
            this.cbData.cls.settings.defaultView = resp.defaultView;

            // Set calendars
            for (var i = 0; i < resp.calendars.length; i++)
            {
                this.cbData.cls.calendars[i] = resp.calendars[i]; // id, name, color, user_id
            }
        }

        // Show browser
        this.cbData.cls.buildInterface();
        this.cbData.cls.refresh();
    };
    ajax.exec("/controller/Calendar/getuserSettings", 
              [["calendar_id", this.m_cid]]);
}

/**
 * Save a setting for the current user
 *
 * @param {string} name The name of the setting to save
 * @param {string} value The value to save for the 'name' key
 */
AntCalendarBrowse.prototype.saveSettings = function(name, value)
{
    switch (name)
    {
        case 'default_view':
            this.settings.defaultView = value;
            var pref = (this.m_cid) ? "calendar/"+this.m_cid+"/default_view" : "calendar/default_view";
            ajax = new CAjax('json');
            ajax.exec("/controller/Calendar/userSetSetting", 
                    [["setting_name", pref], ["setting_value", value]]);
        break;
    }
}

/**
 * Build the interface
 */
AntCalendarBrowse.prototype.buildInterface = function ()
{
    this.outerCon.innerHTML = "";

    // Creat the browser
    this.mainCon = alib.dom.createElement("div", this.outerCon);
	alib.dom.styleSetClass(this.mainObject, "calendarBrowseCon");
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);

    this.toolbarCon = alib.dom.createElement("div", this.mainCon);
	alib.dom.styleSetClass(this.mainObject, "calendarBrowseToolbar");
	/*
    this.titleCon.className = "objectLoaderHeader";
    this.titleCon.innerHTML = "Calendar";
	*/
    this.bodyCon = alib.dom.createElement("div", this.mainCon);
    this.bodyCon.className = "objectLoaderBody";
    
    // Add Toolbar
    // ----------------------------------------------
    this.curTb = new CToolbar();

	// Views
	this.viewToggler = alib.ui.ButtonToggler();

	var btn = alib.ui.Button("Month", {
		className:"b1 grRight", tooltip:"View calendar in month view", cls:this,
		onclick:function() { this.cls.renderCal(null, "month"); }
	});
    this.curTb.AddItem(btn.getButton(), "right");
	this.viewToggler.add(btn, "month");

	var btn = alib.ui.Button("Week", {
		className:"b1 grCenter", tooltip:"View calendar in week view", cls:this,
		onclick:function() { this.cls.renderCal(null, "week"); }
	});
    this.curTb.AddItem(btn.getButton(), "right");
	this.viewToggler.add(btn, "week");

	var btn = alib.ui.Button("Day", {
		className:"b1 grLeft", tooltip:"View calendar in day view", cls:this,
		onclick:function() { this.cls.renderCal(null, "day"); }
	});
    this.curTb.AddItem(btn.getButton(), "right");
	this.viewToggler.add(btn, "day");

    // Today
	var btn = alib.ui.Button("Today", {
		className:"b1", tooltip:"Jump to today", cls:this,
		onclick:function() { this.cls.gotoToday(); }
	});
    this.curTb.AddItem(btn.getButton());
    
    // Previous date(s)
	var button = alib.ui.Button("<img src='/images/icons/arrow_left_16.png' />", {
		className:"b1 grLeft", tooltip:"Previous", cls:this,
		onclick:function() { this.cls.gotoPrev(); }
	});
	this.curTb.AddItem(button.getButton());

    // Next date(s)
	var button = alib.ui.Button("<img src='/images/icons/arrow_right_16.png' />", {
		className:"b1 grRight", tooltip:"Next", cls:this,
		onclick:function() { this.cls.gotoNext(); }
	});
	this.curTb.AddItem(button.getButton());

    // Refresh
	var btn = alib.ui.Button("<img src='/images/icons/refresh_12.png' />", {
		className:"b1", tooltip:"Refresh Events", cls:this,
		onclick:function() { this.cls.refresh(); }
	});
    this.curTb.AddItem(btn.getButton());

	// Add date label
    this.dateLabel = alib.dom.createElement("div");
    alib.dom.styleSetClass(this.dateLabel, "strong");
    alib.dom.styleSet(this.dateLabel, "margin", "8px 0 0 5px");
    this.curTb.AddItem(this.dateLabel);

    this.curTb.print(this.toolbarCon);
    
    // execute default
	this.activeView = this.settings.defaultView;
	this.gotoToday();
}

/**
 * Go to today
 */
AntCalendarBrowse.prototype.gotoToday = function()
{
	var dt = new Date(); 
	return this.renderCal(dt);

	/*
	var gotoView = (viewName) ? viewName : this.activeView;
	
	switch (gotoView)
	{
	case 'day':
		this.renderDay(td.getFullYear(), td.getMonth()+1, td.getDate());
		break;
	case 'week':
		this.renderWeek(td.getFullYear(), td.getMonth()+1, td.getDate());
		break;
	case 'month':
		this.renderMonth(td.getFullYear(), td.getMonth()+1);
		break;
	}

    this.getEvents();
	*/
}

/**
 * Go to next view page
 */

AntCalendarBrowse.prototype.gotoNext = function()
{
	var dt = new Date(this.currentDate);

	switch (this.activeView)
	{
	case 'day':
		dt.setDate(this.currentDate.getDate() + 1);	
		break;
	case 'week':
		dt.setDate(this.currentDate.getDate() + 7);	
		break;
	case 'month':
		dt.setMonth(this.currentDate.getMonth() + 1);
		break;
	}

	this.renderCal(dt); 
}

/**
 * Go to prev view page
 */
AntCalendarBrowse.prototype.gotoPrev = function()
{
	var dt = new Date(this.currentDate);

	switch (this.activeView)
	{
	case 'day':
		dt.setDate(this.currentDate.getDate() - 1);	
		break;
	case 'week':
		dt.setDate(this.currentDate.getDate() - 7);	        
		break;
	case 'month':
		dt.setMonth(this.currentDate.getMonth() - 1);        
		break;
	}

	this.renderCal(dt); 
}

/**
 * Render the calendar
 *
 * @param {Date} date The date to render
 * @param {string} viewName Optional view name, if null then use this.activeView
 */
AntCalendarBrowse.prototype.renderCal = function(date, viewName)
{
	var gotoView = viewName || this.activeView;
	var dt = date || new Date();
	this.currentDate = dt;
	
	switch (gotoView)
	{
	case 'day':
		this.renderDay(dt.getFullYear(), dt.getMonth()+1, dt.getDate());
		break;
	case 'week':
		this.renderWeek(dt.getFullYear(), dt.getMonth()+1, dt.getDate());
		break;
	case 'month':
		this.renderMonth(dt.getFullYear(), dt.getMonth()+1);
		break;
	}

    this.getEvents();
}


/**
 * Display the day view
 *
 * @param {int} year The year to load
 * @param {int} month The number of the month to load
 * @param {int} day The day to load
 */
AntCalendarBrowse.prototype.renderDay = function(year, month, day)
{
    this.bodyCon.innerHTML = "";
	this.viewToggler.select("day");
    
    var toolbarDay = alib.dom.createElement("div", this.bodyCon);
    var displayDay = alib.dom.createElement("div", this.bodyCon);    
    
    this.saveSettings("default_view", "day");

    this.activeView = "day";

    // Make sure the events range is right
	//this.currentDate = new Date(year, month-1, day);
    //this.updateDateRange(this.currentDate, this.currentDate);

    // Get number of days in the current month
    var num_days = calGetMonthNumDays(year, month);

    // Set navigator
    try
    {
        if (this.m_appclass && this.m_appclass.calNav)
            this.m_appclass.calNav(year, month, day, 'day');
    }
    catch(e){}

	// Set label
	this.dateLabel.innerHTML = calGetMonthName(month) + " " + day + ", " + year;

    // ----------------------------------------------------------------------------
    // Display dayview
    // ----------------------------------------------------------------------------
    var min_scale = this.min_scale; // px per minute

    // All Day
    // ------------------------------------------------------
    this.m_events_allday = alib.dom.createElement("div", displayDay);
    this.header_dv = this.m_events_allday;
    alib.dom.styleSet(this.m_events_allday, "margin-left", "69px");
    alib.dom.styleSet(this.m_events_allday, "margin-right", alib.dom.getScrollBarWidth()+"px");
    alib.dom.styleSetClass(this.m_events_allday, "CalendarDayAllDay");

    // Time
    // ------------------------------------------------------
    
	/*
    if(this.browserHeight == 0)
    {
        this.headerHeights = getHeaderHeights();
        this.browserHeight =  alib.dom.getContentHeight(this.outerCon) - this.headerHeights.appNavHeight;
    }
    var height = (this.browserHeight - this.headerHeights.totalHeaderHeight);
    alib.dom.styleSet(displayDay, "height", height+"px");
    alib.dom.styleSet(displayDay, "overflow", "auto");
	*/

    //var cdv = this.m_events_day;
    this.scrollTimedEvents = alib.dom.createElement("div", displayDay);
    var cdv = this.scrollTimedEvents;    
    alib.dom.styleSet(cdv, "position", "relative");
    alib.dom.styleSet(cdv, "overflow", "auto");

    var tdiv = alib.dom.createElement("div", cdv);
    alib.dom.styleSet(tdiv, "float", "left");
    alib.dom.styleSet(tdiv, "width", "70px");    

    this.m_events_day = alib.dom.createElement("div", cdv);
    alib.dom.styleSet(this.m_events_day, "position", "relative");
    alib.dom.styleSet(this.m_events_day, "margin-left", "71px");

    var oD = new Date(year, month-1, 1); //DD replaced line to fix date bug when current day is 31st
    oD.od=oD.getDay()+1; //DD replaced line to fix date bug when current day is 31st

    var todaydate=new Date() //DD added
    var is_today=(year==todaydate.getFullYear() && month==todaydate.getMonth()+1 && day==todaydate.getDate())? 1 : 0;

    if (is_today)
    {
        var hrs = todaydate.getHours();
        var mins = todaydate.getMinutes();
        var pos = ((hrs*60) + mins)*min_scale;

        var sliderdv = alib.dom.createElement("div", cdv);
        alib.dom.styleSet(sliderdv, "position", "absolute");
        alib.dom.styleSet(sliderdv, "top", (pos-6)+"px");
        alib.dom.styleSet(sliderdv, "left", "65px");
        sliderdv.innerHTML = "<img src='/images/icons/tilde_alert.png' border='0'>";

        var bardv = alib.dom.createElement("div", cdv);
        alib.dom.styleSet(bardv, "height", "1px");
        alib.dom.styleSet(bardv, "width", (cdv.scrollWidth-90)+"px");
        alib.dom.styleSet(bardv, "background-color", "red");
        alib.dom.styleSet(bardv, "position", "absolute");
        alib.dom.styleSet(bardv, "top", pos+"px");
        alib.dom.styleSet(bardv, "left", "70px");
    }

    for (var i = 0; i <24; i++)
    {
        var name = (i < 12) ? (((i==0)?12:i) + " AM") : ((i==12)?i:i-12) + " PM";

        var time_dv = alib.dom.createElement("div", tdiv);
        alib.dom.styleSet(time_dv, "height", ((min_scale*60)-1)+"px");
        alib.dom.styleSet(time_dv, "font-weight", "bold");
        alib.dom.styleSet(time_dv, "border-top", "1px solid #999999");
        alib.dom.styleSet(time_dv, "border-right", "1px solid #999999");
        //alib.dom.styleSet(time_dv, "float", "left");
        //alib.dom.styleSet(time_dv, "width", "70px");
        time_dv.innerHTML = name;

        var time_1 = (i < 12) ? (((i==0)?12:i) + ":00 AM") : ((i==12)?i:i-12) + ":00 PM";
        var time_2 = (i < 12) ? (((i==0)?12:i) + ":30 AM") : ((i==12)?i:i-12) + ":30 PM";
        var time_3 = ((i+1) < 12) ? ((((i+1)==0)?12:(i+1)) + ":00 AM") : (((i+1)==12)?(i+1):(i+1)-12) + ":00 PM";

        var hour_dv = alib.dom.createElement("div", this.m_events_day);
        hour_dv.times = [time_1, time_2];
        hour_dv.date_str= month+"/"+day+"/"+year;
        hour_dv.calid = this.m_cid;
        hour_dv.cls = this;
        var height = (alib.userAgent.webkit) ? min_scale*30 : (min_scale*30)-1;
        alib.dom.styleSet(hour_dv, "height", height+"px");
        alib.dom.styleSet(hour_dv, "border-top", "1px solid #999999");
        //alib.dom.styleSet(hour_dv, "margin-left", "71px");
        hour_dv.onmouseover = function()
        {
            this.style.backgroundColor = "#f1f1f1";
        }
        hour_dv.onmouseout = function()
        {
            this.style.backgroundColor = "";
        }
        hour_dv.ondblclick = function()
        {

            var vals = [
                        ['ts_start', this.date_str + " " + this.times[0]], ['ts_end', this.date_str + " " + this.times[1]], 
                        ['calid', this.calid]
                       ];

            //calEventOpen(null, "top.Ant.getHinst('cal_browse').refresh()", vals)
            //loadObjectForm("calendar_event", null, null, null, vals);
            this.cls.loadEvent(null, vals);
        }
        DragAndDrop.registerDropzone(hour_dv, "dzDay");
        hour_dv.m_cls = this;
        hour_dv.date_cur = new Date(year, month-1, day, i, 0);
        hour_dv.onDragDrop = function(e)
        {
            //alib.dom.styleSet(this, "border", "1px solid black");
            var ev = this.m_cls.getEventFromArray(e.eid);

            var ts_start = new Date((ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start);
            var ts_end = new Date((ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end);

            var dif = ts_end.getTime() - ts_start.getTime();

            ev.time_start = calGetClockTime(this.date_cur);
            ev.date_start = new Date((this.date_cur.getMonth()+1)+"/"+this.date_cur.getDate()+"/"+this.date_cur.getFullYear());
            ev.start_block = (this.date_cur.getHours()*60) + this.date_cur.getMinutes();
            ts_start = this.date_cur;

            ts_end.setTime(this.date_cur.getTime() + dif);

            ev.time_end = calGetClockTime(ts_end);
            ev.date_end = new Date((ts_end.getMonth()+1)+"/"+ts_end.getDate()+"/"+ts_end.getFullYear());
            ev.end_block = (ts_end.getHours()*60) + ts_end.getMinutes();

            this.m_cls.redrawEvents();
            /*
            var args = [
                        ["time_start", ev.time_start],
                        ["time_end", ev.time_end],
                        ["date_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear()],
                        ["date_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear()]
                        ];
            */
            var args = [
                        ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                        ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                        ];
            this.m_cls.updateEvent(e.eid, args);
        }

        var hour_dv2 = alib.dom.createElement("div", this.m_events_day);
        hour_dv2.times = [time_2, time_3];
        hour_dv2.date_str= month+"/"+day+"/"+year;
        hour_dv2.calid = this.m_cid;
        hour_dv2.cls = this;
        var height = (alib.userAgent.webkit) ? height-2 : height;
        alib.dom.styleSet(hour_dv2, "height", height+"px");
        alib.dom.styleSet(hour_dv2, "border-top", "1px solid #cccccc");
        //alib.dom.styleSet(hour_dv2, "margin-left", "71px");
        hour_dv2.onmouseover = function()
        {
            this.style.backgroundColor = "#f1f1f1";
        }
        hour_dv2.onmouseout = function()
        {
            this.style.backgroundColor = "";
        }
        hour_dv2.ondblclick = function()
        {

            /*
            var vals = [['date_start', this.date_str], ['date_end', this.date_str], ['time_start', this.times[0]], ['time_end', this.times[1]], ['calid', this.calid]];

            calEventOpen(null, "top.Ant.getHinst('cal_browse').refresh()", vals)
            */


            var vals = [
                        ['ts_start', this.date_str + " " + this.times[0]], ['ts_end', this.date_str + " " + this.times[1]], 
                        ['calid', this.calid]
                       ];

            //loadObjectForm("calendar_event", null, null, null, vals);
            this.cls.loadEvent(null, vals);
        }

        DragAndDrop.registerDropzone(hour_dv, "dzDay");
        hour_dv.m_cls = this;
        hour_dv.date_cur = new Date(year, month-1, day, i, 30);
        hour_dv.onDragDrop = function(e)
        {
            //alib.dom.styleSet(this, "border", "1px solid black");
            var ev = this.m_cls.getEventFromArray(e.eid);

            var ts_start = new Date((ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start);
            var ts_end = new Date((ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end);

            var dif = ts_end.getTime() - ts_start.getTime();

            ev.time_start = calGetClockTime(this.date_cur);
            ev.date_start = new Date((this.date_cur.getMonth()+1)+"/"+this.date_cur.getDate()+"/"+this.date_cur.getFullYear());
            ev.start_block = (this.date_cur.getHours()*60) + this.date_cur.getMinutes();

            ts_end.setTime(this.date_cur.getTime() + dif);

            ev.time_end = calGetClockTime(ts_end);
            ev.date_end = new Date((ts_end.getMonth()+1)+"/"+ts_end.getDate()+"/"+ts_end.getFullYear());
            ev.end_block = (ts_end.getHours()*60) + ts_end.getMinutes();

            this.m_cls.redrawEvents();
            /*
            var args = [
                        ["time_start", ev.time_start],
                        ["time_end", ev.time_end],
                        ["date_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear()],
                        ["date_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear()]
                        ];
                        */
            var args = [
                        ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                        ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                        ];
            this.m_cls.updateEvent(e.eid, args);
        }
    }

    // Resize
    this.resize();

    // Add events
    this.populateDayEvents(year, month, day);
}

/**
 * Display the week view
 *
 * @param {int} year The year to load
 * @param {int} month The month to load
 * @param {int} day The day of today's date
 */
AntCalendarBrowse.prototype.renderWeek = function(year, month, day)
{
    this.bodyCon.innerHTML = "";
	this.viewToggler.select("week");
    
    var toolbarWeek = alib.dom.createElement("div", this.bodyCon);
    var displayWeek = alib.dom.createElement("div", this.bodyCon);
    
    this.saveSettings("default_view", "week");
    this.activeView = "week";

    // Get number of days in the current month
    var num_days = calGetMonthNumDays(year, month);

    // Todays date
    var todaydate=new Date() //DD added

    // Get start date
    var dateWeekStart = calGetWeekStartDate(year, month, day);
    var dateWeekEnd = calDateAddSubtract(dateWeekStart, "day", 6);
	//this.currentDate = dateWeekStart;

    // Make sure the events range is right
    //this.updateDateRange(dateWeekStart, dateWeekEnd);

    // Set navigator
    try
    {
        if (this.m_appclass && this.m_appclass.calNav)
            this.m_appclass.calNav(year, month, day, 'week');
    }
    catch(e){}

	// set label
    this.dateLabel.innerHTML = calGetMonthName(dateWeekStart.getMonth()+1) + " " + dateWeekStart.getDate() + ", " + dateWeekStart.getFullYear() +
                    " - " + calGetMonthName(dateWeekEnd.getMonth()+1) + " " + dateWeekEnd.getDate() + ", " + dateWeekEnd.getFullYear()
     
    // ----------------------------------------------------------------------------
    // Display weekview
    // ----------------------------------------------------------------------------
    var min_scale = this.min_scale; // px per minute

    // All Day
    // ------------------------------------------------------
    var addv = alib.dom.createElement("div", displayWeek);

    // Time
    // ------------------------------------------------------
    this.m_weekdaycolsad = new Array();

    // Headers
    var dy=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    this.header_dv = alib.dom.createElement("div", displayWeek);
    alib.dom.styleSet(this.header_dv, "padding-top", "3px");
    alib.dom.styleSet(this.header_dv, "margin-left", "70px");
    alib.dom.styleSet(this.header_dv, "margin-right", alib.dom.getScrollBarWidth()+"px");
    
    var dateCur = new Date();
    dateCur = dateWeekStart;
    var tbl = alib.dom.createElement("table", this.header_dv);
    alib.dom.styleSet(tbl, "table-layout", "fixed");
    tbl.cellSpacing = 0;
    tbl.cellPadding = 0;
    alib.dom.styleSet(tbl, "width", "100%");
    alib.dom.styleSet(tbl, "border-bottom", "1px solid");
    var tbody = alib.dom.createElement("tbody", tbl);
    var tr1 = alib.dom.createElement("tr", tbody);
    var tr2 = alib.dom.createElement("tr", tbody);
    tr2.vAlign='top';
    for(s=0;s<7;s++)
    {
        var td = alib.dom.createElement("td", tr1);
        alib.dom.styleSet(td, "width", "14%");
        /*
        alib.dom.styleSet(td, "border-left", "1px solid");
        alib.dom.styleSet(td, "font-weight", "bold");
        alib.dom.styleSet(td, "padding", "3px");
        */

        var is_today = (todaydate.getFullYear()==dateCur.getFullYear() && todaydate.getMonth()==dateCur.getMonth() && todaydate.getDate()==dateCur.getDate())?1:0;
        alib.dom.styleSetClass(td, ((is_today)?"CalendarWeekColHdrToday":"CalendarWeekColHdr"));
        td.innerHTML = "<div style='float:right;'>"+dateCur.getDate()+"</div>" + dy[s];

        // Print All Day Cells
        var td2 = alib.dom.createElement("td", tr2);
        this.m_weekdaycolsad[s] = td2;
        alib.dom.styleSetClass(td2, "CalendarWeekColAllDay");
        alib.dom.styleSet(td2, "width", "14%");
        td2.date_cur = dateCur;
        td2.m_cls = this;
        DragAndDrop.registerDropzone(td2, "dzAllDay");
        td2.onDragDrop = function(e)
        {
            //alib.dom.styleSet(this, "border", "1px solid black");
            var ev = this.m_cls.getEventFromArray(e.eid);
            var days = calDaysBetweenDates(ev.date_start, ev.date_end);
            if (days > 0)
            {
                ev.date_start = this.date_cur;
                ev.date_end = calDateAddSubtract(this.date_cur, 'day', days);
            }
            else
            {
                ev.date_start = this.date_cur;
                ev.date_end = this.date_cur;
            }
            this.m_cls.redrawEvents();
            
            var args = [
                        ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                        ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                        ];
            this.m_cls.updateEvent(e.eid, args);
        }
        /*
        alib.dom.styleSet(td2, "border-left", "1px solid");
        alib.dom.styleSet(td2, "border-top", "1px solid");
        alib.dom.styleSet(td2, "padding-top", "8px");
        */

        dateCur = calDateAddSubtract(dateCur, 'day', 1);
    }

    // Time div height
    this.m_events_week = alib.dom.createElement("div", displayWeek);
    var cdv = this.m_events_week;
    alib.dom.styleSet(cdv, "height", "200px");
    alib.dom.styleSet(cdv, "position", "relative");
    alib.dom.styleSet(cdv, "overflow", "auto");

    var oD = new Date(year, month-1, 1); //DD replaced line to fix date bug when current day is 31st
    oD.od=oD.getDay()+1; //DD replaced line to fix date bug when current day is 31st

    var is_today=(year==todaydate.getFullYear() && month==todaydate.getMonth()+1 && day==todaydate.getDate())? 1 : 0;

    if (is_today)
    {
        var hrs = todaydate.getHours();
        var mins = todaydate.getMinutes();
        var pos = ((hrs*60) + mins)*min_scale;

        var sliderdv = alib.dom.createElement("div", cdv);
        alib.dom.styleSet(sliderdv, "position", "absolute");
        alib.dom.styleSet(sliderdv, "top", (pos-6)+"px");
        alib.dom.styleSet(sliderdv, "left", "65px");
        sliderdv.innerHTML = "<img src='/images/icons/tilde_alert.png' border='0'>";

        try
        {
            var bardv = alib.dom.createElement("div", cdv);
            alib.dom.styleSet(bardv, "height", "1px");
            alib.dom.styleSet(bardv, "width", (cdv.scrollWidth-90)+"px");
            alib.dom.styleSet(bardv, "background-color", "red");
            alib.dom.styleSet(bardv, "position", "absolute");
            alib.dom.styleSet(bardv, "top", pos+"px");
            alib.dom.styleSet(bardv, "left", "70px");
        }
        catch(e){}
    }

    // Populate times
    var time_left = alib.dom.createElement("div", cdv);
    alib.dom.styleSet(time_left, "float", "left");
    alib.dom.styleSet(time_left, "width", "70px");    
    for (var i = 0; i < 24; i++)
    {
        var name = (i < 12) ? (((i==0)?12:i) + " AM") : ((i==12)?i:i-12) + " PM";

        var time_dv = alib.dom.createElement("div", time_left);
        alib.dom.styleSet(time_dv, "height", ((min_scale*60)-1)+"px");
        alib.dom.styleSet(time_dv, "font-weight", "bold");
        alib.dom.styleSet(time_dv, "border-top", "1px solid #999999");
        time_dv.innerHTML = name;
    }

    // populate days and events
    var time_days = alib.dom.createElement("div", cdv);
    alib.dom.styleSet(time_days, "margin-left", "70px");

    var tbl = alib.dom.createElement("table", time_days);
    alib.dom.styleSet(tbl, "table-layout", "fixed");
    alib.dom.styleSet(tbl, "width", "100%");
    tbl.cellPadding = '0';
    tbl.cellSpacing = '0';
    var tbody = alib.dom.createElement("tbody", tbl);
    var trow = alib.dom.createElement("tr", tbody);
    this.m_weekdaycols = new Array();

    var dateCur = new Date();
    dateCur = dateWeekStart;

    for (var d = 0; d < 7; d++)
    {
        var td_day = alib.dom.createElement("td", trow);
        alib.dom.styleSetClass(td_day, "CalendarWeekCol");
        alib.dom.styleSet(td_day, "width", "14%");
        //alib.dom.styleSet(td_day, "border-left", "1px solid #333333");
        this.m_weekdaycols[d] = alib.dom.createElement("div", td_day);
        alib.dom.styleSet(this.m_weekdaycols[d], "width", "100%");
        alib.dom.styleSet(this.m_weekdaycols[d], "position", "relative");

        for (var i = 0; i < 24; i++)
        {
            var time_1 = (i < 12) ? (((i==0)?12:i) + ":00 AM") : ((i==12)?i:i-12) + ":00 PM";
            var time_2 = (i < 12) ? (((i==0)?12:i) + ":30 AM") : ((i==12)?i:i-12) + ":30 PM";
            var time_3 = ((i+1) < 12) ? ((((i+1)==0)?12:(i+1)) + ":00 AM") : (((i+1)==12)?(i+1):(i+1)-12) + ":00 PM";

            var hour_dv = alib.dom.createElement("div", td_day);
            var height = (alib.userAgent.webkit) ? min_scale*30 : (min_scale*30)-1;
            alib.dom.styleSet(hour_dv, "height", height+"px");
            alib.dom.styleSet(hour_dv, "border-top", "1px solid #999999");
            hour_dv.times = [time_1, time_2];
            hour_dv.date_str= (dateCur.getMonth()+1)+"/"+dateCur.getDate()+"/"+dateCur.getFullYear();
            hour_dv.calid = this.m_cid;
            hour_dv.cls = this;
            hour_dv.onmouseover = function()
            {
                this.style.backgroundColor = "#f1f1f1";
            }
            hour_dv.onmouseout = function()
            {
                this.style.backgroundColor = "";
            }
            hour_dv.ondblclick = function()
            {
                var vals = [
                            ['ts_start', this.date_str + " " + this.times[0]], ['ts_end', this.date_str + " " + this.times[1]], 
                            ['calid', this.calid]
                           ];
                this.cls.loadEvent(null, vals);
            }

            DragAndDrop.registerDropzone(hour_dv, "dzDay");
            hour_dv.m_cls = this;
            hour_dv.date_cur = new Date(dateCur.getFullYear(), dateCur.getMonth(), dateCur.getDate(), i, 0);
            hour_dv.onDragDrop = function(e)
            {
                var ev = this.m_cls.getEventFromArray(e.eid);

                var ts_start = new Date((ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start);
                var ts_end = new Date((ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end);

                var dif = ts_end.getTime() - ts_start.getTime();

                ev.time_start = calGetClockTime(this.date_cur);
                ev.date_start = new Date((this.date_cur.getMonth()+1)+"/"+this.date_cur.getDate()+"/"+this.date_cur.getFullYear());
                ev.start_block = (this.date_cur.getHours()*60) + this.date_cur.getMinutes();

                ts_end.setTime(this.date_cur.getTime() + dif);

                ev.time_end = calGetClockTime(ts_end);
                ev.date_end = new Date((ts_end.getMonth()+1)+"/"+ts_end.getDate()+"/"+ts_end.getFullYear());
                ev.end_block = (ts_end.getHours()*60) + ts_end.getMinutes();

                this.m_cls.redrawEvents();
               
                var args = [
                            ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                            ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                            ];
                this.m_cls.updateEvent(e.eid, args);
            }

            var hour_dv2 = alib.dom.createElement("div", td_day);
            var height = (alib.userAgent.webkit) ? height-2 : height;
            alib.dom.styleSet(hour_dv2, "height", height+"px");
            alib.dom.styleSet(hour_dv2, "border-top", "1px solid #cccccc");
            hour_dv2.times = [time_2, time_3];
            hour_dv2.date_str= (dateCur.getMonth()+1)+"/"+dateCur.getDate()+"/"+dateCur.getFullYear();
            hour_dv2.calid = this.m_cid;
            hour_dv2.cls = this;
            hour_dv2.onmouseover = function()
            {
                this.style.backgroundColor = "#f1f1f1";
            }
            hour_dv2.onmouseout = function()
            {
                this.style.backgroundColor = "";
            }
            hour_dv2.ondblclick = function()
            {
                var vals = [
                            ['ts_start', this.date_str + " " + this.times[0]], ['ts_end', this.date_str + " " + this.times[1]], 
                            ['calid', this.calid]
                           ];

                this.cls.loadEvent(null, vals);
            }

            DragAndDrop.registerDropzone(hour_dv2, "dzDay");
            hour_dv2.m_cls = this;
            hour_dv2.date_cur = new Date(dateCur.getFullYear(), dateCur.getMonth(), dateCur.getDate(), i, 30);
            hour_dv2.onDragDrop = function(e)
            {
                //alib.dom.styleSet(this, "border", "1px solid black");
                var ev = this.m_cls.getEventFromArray(e.eid);

                var ts_start = new Date((ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start);
                var ts_end = new Date((ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end);

                var dif = ts_end.getTime() - ts_start.getTime();

                ev.time_start = calGetClockTime(this.date_cur);
                ev.date_start = new Date((this.date_cur.getMonth()+1)+"/"+this.date_cur.getDate()+"/"+this.date_cur.getFullYear());
                ev.start_block = (this.date_cur.getHours()*60) + this.date_cur.getMinutes();

                ts_end.setTime(this.date_cur.getTime() + dif);

                ev.time_end = calGetClockTime(ts_end);
                ev.date_end = new Date((ts_end.getMonth()+1)+"/"+ts_end.getDate()+"/"+ts_end.getFullYear());
                ev.end_block = (ts_end.getHours()*60) + ts_end.getMinutes();

                this.m_cls.redrawEvents();

                var args = [
                            ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                            ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                            ];
                this.m_cls.updateEvent(e.eid, args);
            }
        }

        dateCur = calDateAddSubtract(dateCur, 'day', 1);
    }
 
    // Resize
    this.resize();

    // Add events
    this.populateWeekEvents(dateWeekStart.getFullYear(), dateWeekStart.getMonth()+1, dateWeekStart.getDate());
}

/**
 * Render the month view
 *
 * @param {int} year The year to render
 * @param {int} month The month to render
 */
AntCalendarBrowse.prototype.renderMonth = function(year, month)
{    
    this.bodyCon.innerHTML = "";
    this.saveSettings("default_view", "month");
	this.viewToggler.select("month");

	// Update current date if different than alread loaded month/year
	//if (this.currentDate.getFullYear() != year || (this.currentDate.getMonth()+1)!=month)
		//this.currentDate = new Date(year, month-1, 1);

    this.activeView = "month";

    var num_days = calGetMonthNumDays(year, month);

    // Make sure the events range is right
    //var actDayFrom = new Date(year, month-1, 1);
    //var actDayTo = new Date(year, month-1, num_days);
    //this.updateDateRange(actDayFrom, actDayTo);

    // cells for each day of the month
    this.m_monthdaycells = new Array();

    // Set minical navigator 
    try
    {
        if (this.m_appclass && this.m_appclass.calNav)
            this.m_appclass.calNav(year, month, 1, 'month');
    }
    catch(e){}

	// set label
    this.dateLabel.innerHTML = calGetMonthName(this.currentDate.getMonth()+1) + " " + this.currentDate.getFullYear();


    // --------------------------------------------------------------------------------------
    // Display monthview
    // --------------------------------------------------------------------------------------
    var dv = alib.dom.createElement("div", this.bodyCon);
    alib.dom.styleSet(dv, "margin-top", "3px");

    var dy=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    

    var oD = new Date(year, month-1, 1); //DD replaced line to fix date bug when current day is 31st
    oD.od=oD.getDay()+1; //DD replaced line to fix date bug when current day is 31st

    var todaydate=new Date() //DD added
    var scanfortoday=(year==todaydate.getFullYear() && month==todaydate.getMonth()+1)? todaydate.getDate() : 0 //DD added

    var tbl = alib.dom.createElement("table", dv);
    tbl.id = "CalendarMonthMainTable";
    alib.dom.styleSet(tbl, "table-layout", "fixed");
    tbl.cellPadding = 0;
    tbl.cellSpacing = 0;
    
    alib.dom.styleSet(tbl, "width", "100%");
    alib.dom.styleSetClass(tbl, "CalendarMonthMainTable");    
    
    /*
    alib.dom.styleSet(tbl, "border-left", "1px solid");
    alib.dom.styleSet(tbl, "border-bottom", "1px solid");
    */
    var tbody = alib.dom.createElement("tbody", tbl);
	this.monthViewTbody = tbody;

    var headers_tr = alib.dom.createElement("tr", tbody);
    for(s=0;s<7;s++)
    {
        var td = alib.dom.createElement("td", headers_tr);
        alib.dom.styleSetClass(td, "CalendarMonthWeekdayHdr");
        td.innerHTML = dy[s];

    }
    var tr = alib.dom.createElement("tr", tbody);
    tr.vAlign = "top";
    var d = 0; // number of days
    this.m_monthnumrows = 1;
    for(i=1; i<=42; i++)
    {
        var x=((i-oD.od>=0)&&(i-oD.od<num_days))? i-oD.od+1 : '&nbsp;';

        var td = alib.dom.createElement("td", tr);
        alib.dom.styleSetClass(td, "CalendarMonthCell");
        alib.dom.styleSet(td, "width", "14%");
        alib.dom.styleSet(td, "overflow", "auto");
        var td_dv = alib.dom.createElement("div", td);                
        alib.dom.styleSet(td_dv, "overflow", 'hidden');    

    
        if (x != "&nbsp;")
        {
            var act_lnk = alib.dom.createElement("a");
            act_lnk.innerHTML = "+ add";
            act_lnk.href = "javascript:void(0);";
            alib.dom.styleSet(act_lnk, "text-decoration", "none");
            act_lnk.mvars = [['ts_start', month+"/"+x+"/"+year], ['ts_end', month+"/"+x+"/"+year], ['all_day', 't'], ['calendar', this.m_cid]];
            act_lnk.cls = this;
            act_lnk.onclick = function() 
            {
                this.cls.loadEvent(null, this.mvars);
            }

            this.m_monthdaycells[d] = td;
            d++;

            DragAndDrop.registerDropzone(td, "dzDays");
            td.m_cls = this;
            td.date_cur = new Date(year, month-1, x);
            td.onDragEnter = function(e)
            {
                //alib.dom.styleSet(this, "border", "1px solid red");
            }
            td.onDragExit = function(e)
            {
                //alib.dom.styleSet(this, "border", "1px solid black");
            }
            td.onDragDrop = function(e)
            {
                //alib.dom.styleSet(this, "border", "1px solid black");
                var ev = this.m_cls.getEventFromArray(e.eid);
                var days = calDaysBetweenDates(ev.date_start, ev.date_end);
                if (days > 0)
                {
                    ev.date_start = this.date_cur;
                    ev.date_end = calDateAddSubtract(this.date_cur, 'day', days);
                }
                else
                {
                    ev.date_start = this.date_cur;
                    ev.date_end = this.date_cur;
                }
                this.m_cls.redrawEvents();
                var args = [
                            ["ts_start", (ev.date_start.getMonth()+1)+"/"+ev.date_start.getDate()+"/"+ev.date_start.getFullYear() + " " + ev.time_start],
                            ["ts_end", (ev.date_end.getMonth()+1)+"/"+ev.date_end.getDate()+"/"+ev.date_end.getFullYear() + " " + ev.time_end]
                            ];
                this.m_cls.updateEvent(e.eid, args);
            }
        }
        else
            var act_lnk = null;

        // Header
        var hdv = alib.dom.createElement("div", td_dv);
        if (x==scanfortoday) //DD added
            alib.dom.styleSetClass(hdv, "CalendarMonthHdrToday");
        else
            alib.dom.styleSetClass(hdv, "CalendarMonthHdr");
        if (act_lnk)
        {
            var add = alib.dom.createElement("div");
            alib.dom.styleSet(add, "float", "right");
            hdv.appendChild(add);
            add.appendChild(act_lnk);
        }
        var lbl = alib.dom.createElement("span", hdv);
        if (x != "&nbsp;")
        {
            var a_daylnk = alib.dom.createElement("a", lbl);
            a_daylnk.innerHTML = x;
            a_daylnk.m_cls = this;
            a_daylnk.year = year;
            a_daylnk.month = month;
            a_daylnk.day = x;
            a_daylnk.href = "javascript:void(0)";
            a_daylnk.onclick = function()
            {
                this.m_cls.renderDay(this.year, this.month, this.day);
            }
        }
        else
            lbl.innerHTML = x;

        if(((i)%7==0)&&(d<num_days))
        {
            tr = alib.dom.createElement("tr", tbody);
            tr.vAlign = "top";
            this.m_monthnumrows++;
        }
        else if(((i)%7==0)&&(d>=num_days))
        {
            break;
        }
    }
    
    // Resize
    this.resize();

    // Populate events
    this.populateMonthEvents(year, month);
}

/**
 * Resize browser interface
 */
AntCalendarBrowse.prototype.resize = function()
{
	if (!this.inline)
	{
		var minus_height = (alib.userAgent.ie) ? 30 : 0;
		var height = (getWorkspaceHeight()-minus_height);		

		if (this.titleCon)
			height -= this.titleCon.offsetHeight;

		if (this.toolbarCon)
			height -= this.toolbarCon.offsetHeight;

		if (height > 0)
			alib.dom.styleSet(this.bodyCon, "height", (height-10)+"px");
	}

    switch (this.activeView)
    {
    case 'day':
        return this.resizeDay();
    case 'week':
        return this.resizeWeek();
    case 'month':
        return this.resizeMonth();
    case 'list':
        break;
    }
}

/**
 * Resize the month view elements
 */
AntCalendarBrowse.prototype.resizeMonth = function()
{
	if (!this.monthViewTbody)
		return;

    var height = alib.dom.getContentHeight(this.bodyCon) -5;

	// Get the height of the first row
	var bodyHeight = height - this.monthViewTbody.childNodes[0].offsetHeight;

	// Get the number of days rows in this month view
	var numRows = this.monthViewTbody.childNodes.length - 1; // minus month labels

	// Loop through rows skipping the first row
	for (var i = 1; i < this.monthViewTbody.childNodes.length; i++)
	{
		// Set the height of each cell
		for (var j in this.monthViewTbody.childNodes[i].childNodes)
		{
			try
			{
				if (this.monthViewTbody.childNodes[i].childNodes[j])
					alib.dom.styleSet(this.monthViewTbody.childNodes[i].childNodes[j], "height", (bodyHeight/numRows) + "px");
			}
			catch (e)
			{
				// Fail gracefully because the table nodes may not be loaded yet
			}
		}
	}
}

/**
 * Resize the week view elements
 */
AntCalendarBrowse.prototype.resizeWeek= function()
{
    var height = alib.dom.getContentHeight(this.bodyCon) - this.header_dv.offsetHeight;
    alib.dom.styleSet(this.m_events_week, "height", height+"px");    

    this.m_events_week.scrollTop = (this.m_events_week.scrollHeight/2)-(this.m_events_week.offsetHeight/2);
}


/**
 * Resize the day view elements
 */
AntCalendarBrowse.prototype.resizeDay = function()
{
    var height = alib.dom.getContentHeight(this.bodyCon) - this.header_dv.offsetHeight -20;

    alib.dom.styleSet(this.scrollTimedEvents, "height", height+"px");

    this.scrollTimedEvents.scrollTop = (this.scrollTimedEvents.scrollHeight/2)-(this.scrollTimedEvents.offsetHeight/2);
}

/*************************************************************************
*    Function:    populateDayEvents
*
*    Purpose:    Put events on the canvas (day, week, month)
**************************************************************************/
AntCalendarBrowse.prototype.populateDayEvents = function(year, month, day, tcon_num)
{
    var curDate = new Date(year, month-1, day);
    
    if (typeof tcon_num != "undefined")
    {
        var use_con = this.m_weekdaycols[tcon_num];
        var use_allday_con = this.m_weekdaycolsad[tcon_num];
    }
    else
    {
        var use_con = this.m_events_day;
        var use_allday_con = this.m_events_allday;
    }

    for (var i = 0 ; i < this.events.length; i++)
    {
        var ev = this.events[i];

        var fUse = false;
        var start_block = ev.start_block;
        var end_block = ev.end_block;

        if (ev.date_start.getTime() == ev.date_end.getTime() && ev.date_start.getTime() == curDate.getTime())
        {
            fUse = true;
        }
        else if (ev.date_start.getTime() <= curDate.getTime() && ev.date_end.getTime() >= curDate.getTime())
        {
            fUse = true;

            if (ev.date_start.getTime() != curDate.getTime())
                start_block = 0;
            if (ev.date_end.getTime() != curDate.getTime())
                end_block = 1440;
        }

        if (fUse)
        {
            if (ev.allDay)
            {
                var evdv = alib.dom.createElement("div", use_allday_con);
                //alib.dom.styleSet(evdv, "width", "98%"); // 2 hours
                alib.dom.styleSet(evdv, "background-color", "#"+ev.color);
                alib.dom.styleSet(evdv, "color", "#"+getColorTextForGroup(ev.color));
                alib.dom.styleSet(evdv, "padding", "3px");
                alib.dom.styleSet(evdv, "margin", "2px");
                alib.dom.styleSet(evdv, "overflow", "hidden");
                alib.dom.styleSet(evdv, "white-space", "nowrap");
                alib.dom.styleSet(evdv, "text-overflow", "ellipsis");
                alib.dom.styleSet(evdv, "cursor", "pointer"); // 2 hours
                evdv.innerHTML = ev.name;
                evdv.eid = ev.eid;
                evdv.calid = this.m_cid;                
                evdv.cls = this;
                evdv.onclick = function() 
                { 
                    this.cls.loadEvent(this.eid, [["calid", this.calid]]);
                    //loadObjectForm("calendar_event", this.eid, null, null, [["calid", this.calid]]); 
                }
                DragAndDrop.registerDragable(evdv, null, "dzAllDay"); // , null, "dzGroup1"
            }
            else
            {
                var height = (this.min_scale*(end_block-start_block - 1)); // Add 1 px for space
                if (height < this.min_scale*10)
                    height = 14;
                else if (height < 12)
                    height = 12;

                var det_htm = "<div style='overflow:hidden;height:100%;margin-left:3px;'><div style='font-weight:bold;margin-bottom:5px;'>" + ev.name + "</div>";
                if (ev.loc) det_htm += "<div>" + ev.loc + "</div>";
                if (ev.time_start) det_htm += "<div>" + ev.time_start + " - " + ev.time_end + "</div>";
                if (ev.notes) det_htm += "<div>" + ev.notes + "</div>";
                det_htm += "</div>";

                var wandm = this.getEventWidth(ev);
                var evdv = alib.dom.createElement("div", use_con);
                this.events[i].m_div = evdv;
                alib.dom.styleSet(evdv, "position", "absolute");
                alib.dom.styleSet(evdv, "top", (this.min_scale*start_block)+"px"); // 8 am - add +5 for the rounded borders
                alib.dom.styleSet(evdv, "left", "0px"); // 8 am - add +5 for the rounded borders
                alib.dom.styleSet(evdv, "height", height+"px"); // 2 hours  - subtract 1-px for the rounded borders
                alib.dom.styleSet(evdv, "width", wandm[0]+"%");
                if (wandm[1])
                    alib.dom.styleSet(evdv, "margin-left", wandm[1]+"%");
                alib.dom.styleSet(evdv, "background-color", "#"+ev.color);
                alib.dom.styleSet(evdv, "color", "#"+getColorTextForGroup(ev.color));
                alib.dom.styleSet(evdv, "cursor", "pointer"); // 2 hours
                evdv.innerHTML = det_htm;
                evdv.eid = ev.eid;
                evdv.calid = this.m_cid;
                //evdv.onclick = function() { calEventOpen(this.eid, "top.Ant.getHinst('cal_browse').refresh()", [["calid", this.calid]]); }
                evdv.cls = this;
                evdv.onclick = function() 
                { 
                    this.cls.loadEvent(this.eid, [["calid", this.calid]]);
                    //loadObjectForm("calendar_event", this.eid, null, null, [["calid", this.calid]]); 
                }            
                //ALib.Effect.round(evdv, 5);

                DragAndDrop.registerDragable(evdv, null, "dzDay"); // , null, "dzGroup1"
            }
        }
    }

    if (typeof tcon_num == "undefined")
        this.resizeDay();
}

/*************************************************************************
*    Function:    populateWeekEvents
*
*    Purpose:    Put events on the canvas (day, week, month)
**************************************************************************/
AntCalendarBrowse.prototype.populateWeekEvents= function(year, month, day)
{
    var dateCur = new Date(year, month-1, day);

    for(s=0;s<7;s++)
    {
        this.populateDayEvents(dateCur.getFullYear(), dateCur.getMonth()+1, dateCur.getDate(), s);
        
        dateCur = calDateAddSubtract(dateCur, 'day', 1);
    }

    this.resizeWeek();
}

/*************************************************************************
*    Function:    populateMonthEvents
*
*    Purpose:    Call populateMonthDayEvents for each day
**************************************************************************/
AntCalendarBrowse.prototype.populateMonthEvents= function(year, month)
{
    // Get number of days in the current month
    var num_days = calGetMonthNumDays(year, month);
    //var dateStart = new Date(year, month-1, 1);
    //var dateEnd = new Date(year, month-1, num_days);

    for(s=0;s<num_days;s++)
        this.populateMonthDayEvents(year, month, s+1, this.m_monthdaycells[s].childNodes.item(0), 3);

    //this.resizeMonth();
}

/*************************************************************************
*    Function:    populateMonthDayEvents
*
*    Purpose:    Put events on the canvas for the month view
**************************************************************************/
AntCalendarBrowse.prototype.populateMonthDayEvents = function(year, month, day, tcon, displayCount, dlg)
{
    var curDate = new Date(year, month-1, day);    
    var eventCount = 0;
    var moreCount = 0;
    
    for (var i = 0 ; i < this.events.length; i++)
    {
        var ev = this.events[i];
        var fUse = false;

        if (ev.date_start.getTime() == ev.date_end.getTime() && ev.date_start.getTime() == curDate.getTime())
            fUse = true;
        else if (ev.date_start.getTime() <= curDate.getTime() && ev.date_end.getTime() >= curDate.getTime())
            fUse = true;

        if (fUse)
        {
            if(eventCount<displayCount)
            {
                eventCount++;
                var evhtml = ((!ev.allDay)?ev.time_start + " ":'') + "<span style='background-color:#" + ev.color  +"'>&nbsp;</span> " + ev.name;

                var evdv = alib.dom.createElement("div", tcon);
                ev.m_div = evdv;
                alib.dom.styleSet(evdv, "padding", "2px");
                alib.dom.styleSet(evdv, "overflow", "hidden");
                alib.dom.styleSet(evdv, "text-overflow", "ellipsis");
                alib.dom.styleSet(evdv, "white-space", "nowrap");
                alib.dom.styleSet(evdv, "cursor", "pointer");
                alib.dom.styleSet(evdv, "border-left", "3px solid #"+ev.color);                
                evdv.innerHTML = ((!ev.allDay)?ev.time_start + " ":'') +  ev.name;
                evdv.eid = ev.eid;
                evdv.calid = this.m_cid;
                evdv.cls = this;                
                evdv.onclick = function() 
                {
                    if(dlg)
                        dlg.hide();
                    this.cls.loadEvent(this.eid, [["calid", this.calid]]); 
                }
                DragAndDrop.registerDragable(evdv, null, "dzDays");
            }
            else
                moreCount++;
        }
    }
    
    if(moreCount > 0)
    {
        var evdv = alib.dom.createElement("div", tcon);
        ev.m_div = evdv;
        alib.dom.styleSet(evdv, "padding", "2px");
        alib.dom.styleSet(evdv, "overflow", "hidden");
        alib.dom.styleSet(evdv, "text-overflow", "ellipsis");
        alib.dom.styleSet(evdv, "white-space", "nowrap");
        alib.dom.styleSet(evdv, "cursor", "pointer");        
        evdv.innerHTML = "[Show all events]";        
        evdv.cls = this;        
        evdv.onclick = function() // show all calendar events for this day in modal window
        {
            var totalEvents = moreCount + displayCount;
            var dateTitle = calGetMonthName(month) + " " + day + ", " + year + " - " + totalEvents + " events";
            var dlg = new CDialog(dateTitle);
            var divModal = alib.dom.createElement("div");
            
            this.cls.populateMonthDayEvents(year, month, day, divModal, totalEvents, dlg);
            
            dlg.f_close = true;
            dlg.customDialog(divModal, 450);
        }                    
    }
}

/*************************************************************************
*    Function:    getEventWidth
*
*    Purpose:    Get the width of an event and adjust for overlapping
**************************************************************************/
AntCalendarBrowse.prototype.getEventWidth = function(evnt)
{
    var width = 100;
    var overlapping = 1;
    var margin = 0;
    var arrEvents = new Array();
    for (var i = 0 ; i < this.events.length; i++)
    {
        var ev = this.events[i];

        if (ev.ts_start.getTime() <= evnt.ts_start.getTime() && ev.ts_end.getTime() > evnt.ts_start.getTime() && ev.eid!=evnt.eid)
        {
            overlapping+=1;
            arrEvents[arrEvents.length] = ev;
        }
    }

    for (var i = 0; i < arrEvents.length; i++)
    {
        var ev = arrEvents[i];

        if (ev.m_div)
        {
            width = 100/overlapping;

            var evdv = ev.m_div;
            alib.dom.styleSet(evdv, "width", width+"%");
            if (margin)
                alib.dom.styleSet(evdv, "margin-left", margin+"%");

            margin = margin + width;
        }
    }

    return [width, margin];
}

/*************************************************************************
*    Function:    redrawEvents
*
*    Purpose:    Redraw events for the current view
**************************************************************************/
AntCalendarBrowse.prototype.redrawEvents = function()
{
    switch (this.activeView)
    {
    case 'day':
        this.renderDay(this.currentDate.getFullYear(), this.currentDate.getMonth()+1, this.currentDate.getDate());
        break;
    case 'week':
        this.renderWeek(this.currentDate.getFullYear(), this.currentDate.getMonth()+1, this.currentDate.getDate());
        break;
    case 'month':
        this.renderMonth(this.currentDate.getFullYear(), this.currentDate.getMonth()+1);
        break;
    }
}

/*************************************************************************
*    Function:    updateDateRange
*
*    Purpose:    Make sure we are within our already established date range
**************************************************************************/
AntCalendarBrowse.prototype.updateDateRange = function(date_from, date_to)
{
    if (date_from.getTime() < this.eventsRangeFrom.getTime() || date_to.getTime() > this.eventsRangeTo.getTime())
    {
        var activeDate = new Date(this.currentDate.getFullYear(), this.activeMonth-1, this.currentDate.getDate());
        this.eventsRangeFrom = calDateAddSubtract(activeDate, 'day', -31);
        this.eventsRangeTo = calDateAddSubtract(activeDate, 'day', 31);

        this.refresh();
    }
}

/**
 * Refresh events and other data
 */
AntCalendarBrowse.prototype.refresh = function()
{
    var from = (this.eventsRangeFrom.getMonth()+1)+"/"+this.eventsRangeFrom.getDate()+"/"+this.eventsRangeFrom.getFullYear();
    var to = (this.eventsRangeTo.getMonth()+1)+"/"+this.eventsRangeTo.getDate()+"/"+this.eventsRangeTo.getFullYear();

    this.getEvents(from, to);
}

/**
 * Set this browser to refresh automatically
 *
 * @public
 * @param {number} interval The interval to refresh, if < 1 (0 or null) then disable
 * @this {AntObjectBrowser}
 */
AntCalendarBrowse.prototype.setAutoRefresh = function(interval)
{	
	if (!interval)
	{
		clearTimeout(this.refreshTimer);
		this.refreshTimer = null;
	}

	if (!this.refreshTimer && interval)
	{
		var cls = this;
		this.refreshTimer = setTimeout(function() { cls.refresh(); }, this.refreshInterval);
	}
}


/**
 * Get the events from the backend
 *
 * Will use this.currentDate and pull all events for the currently selected month/year
 */
AntCalendarBrowse.prototype.getEvents = function(date_start, date_end)
{

    if (this.m_timer)
        clearTimeout(this.m_timer);

	// Get start date = first of month - 7 days into previous month
	var startDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), -6);
	var date_start = (startDate.getMonth()+1)+"/" + startDate.getDate() + "/"+ startDate.getFullYear();

	// Get end date = last of month + days into next month
	var endDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()+1, 7);
	var date_end = (endDate.getMonth()+1)+"/" + endDate.getDate() + "/" + endDate.getFullYear();

    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        this.cbData.cls.events = new Array();

        if (ret.objects.length)
        {
            for(event in ret.objects)
            {
                var currentEvent = ret.objects[event];
                
                var event_id = currentEvent["id"];
                var event_rid = currentEvent["recur_id"];
                var event_name = currentEvent["name"];
                var color = currentEvent["color"];
                var ts_start = currentEvent["ts_start"];
                var ts_end = currentEvent["ts_end"];
                var event_calid = currentEvent["calendar"]["key"];
                var loc = currentEvent["location"];
                var notes = currentEvent["notes"];

                var date_start = new Date(ts_start);
                var date_end = new Date(ts_end);
                
                var m = this.cbData.cls.events.length;
                this.cbData.cls.events[m] = new Object();
                this.cbData.cls.events[m].date_start = new Date((date_start.getMonth()+1)+"/"+date_start.getDate()+"/"+date_start.getFullYear());
                this.cbData.cls.events[m].date_end = new Date((date_end.getMonth()+1)+"/"+date_end.getDate()+"/"+date_end.getFullYear());
                this.cbData.cls.events[m].start_block = (date_start.getHours()*60) + date_start.getMinutes();
                this.cbData.cls.events[m].end_block = (date_end.getHours()*60) + date_end.getMinutes(); 
                this.cbData.cls.events[m].time_start = calGetClockTime(date_start);
                this.cbData.cls.events[m].time_end = calGetClockTime(date_end);
                this.cbData.cls.events[m].allDay = (currentEvent["all_day"]=='t') ? true : false;
                this.cbData.cls.events[m].name = unescape(event_name);
                this.cbData.cls.events[m].loc = unescape(loc);
                this.cbData.cls.events[m].notes = unescape(notes);
                this.cbData.cls.events[m].eid = event_id;
                this.cbData.cls.events[m].recud_id = event_rid;
                this.cbData.cls.events[m].color = this.cbData.cls.getCalendarColor(event_calid);
                this.cbData.cls.events[m].ts_start = date_start;
                this.cbData.cls.events[m].ts_end = date_end;
            }
        }
        
        this.cbData.cls.redrawEvents();
    };
    
    var args = new Array();

    // Set date range to current month
    var ccount = 1;
    args[args.length] = ["conditions[]", ccount];
    args[args.length] = ["condition_blogic_"+ccount, "and"];
    args[args.length] = ["condition_fieldname_"+ccount, "ts_start"];
    args[args.length] = ["condition_operator_"+ccount, "is_greater_or_equal"];
    args[args.length] = ["condition_condvalue_"+ccount, date_start];
    ccount = 2;
    args[args.length] = ["conditions[]", ccount];
    args[args.length] = ["condition_blogic_"+ccount, "and"];
    args[args.length] = ["condition_fieldname_"+ccount, "ts_end"];
    args[args.length] = ["condition_operator_"+ccount, "is_less_or_equal"];
    args[args.length] = ["condition_condvalue_"+ccount, date_end];

    // Set calendars
    if (this.m_cid)
    {
        ccount++;
        args[args.length] = ["conditions[]", ccount];
        args[args.length] = ["condition_blogic_"+ccount, "and"];
        args[args.length] = ["condition_fieldname_"+ccount, "calendar"];
        args[args.length] = ["condition_operator_"+ccount, "is_equal"];
        args[args.length] = ["condition_condvalue_"+ccount, this.m_cid];
    }
    else if (this.calendars)
    {
        var blogic = "and";
        for(calendar in this.calendars)
        {
            var currentCalendar = this.calendars[calendar];
            
            ccount++;
            args[args.length] = ["conditions[]", ccount];
            args[args.length] = ["condition_blogic_"+ccount, blogic];
            args[args.length] = ["condition_fieldname_"+ccount, "calendar"];
            args[args.length] = ["condition_operator_"+ccount, "is_equal"];
            args[args.length] = ["condition_condvalue_"+ccount, currentCalendar.id];
            
            if(blogic=="and")
                blogic = "or";
        }
    }

    args[args.length] = ["obj_type", "calendar_event"];
    args[args.length] = ["limit", "1000"]; // Load a maximum of 1000 events per month
    ajax.exec("/controller/Calendar/getEvents", args);
}

/*************************************************************************
*    Function:    getEventFromArray
*
*    Purpose:    pull an event out of the array by id
**************************************************************************/
AntCalendarBrowse.prototype.getEventFromArray = function(id)
{
    for (var i = 0; i < this.events.length; i++)
    {
        if (this.events[i].eid == id)
            return this.events[i];
    }

    return false;
}

/*************************************************************************
*    Function:    getColorTextForGroup
*
*    Purpose:    Get associated text color for group
**************************************************************************
AntCalendarBrowse.prototype.getColorTextForGroup = function(color)
{
    var ret = "000000";
    for (var j = 0; j < G_GROUP_COLORS.length; j++)
    {
        if (G_GROUP_COLORS[j][1] == color)
            ret = G_GROUP_COLORS[j][2];
    }

    return ret;
}
*/

/*************************************************************************
*    Function:    updateEvent
*
*    Purpose:    Update an event
**************************************************************************/
AntCalendarBrowse.prototype.updateEvent = function(eid, args)
{
    // Set defaults
    this.settings.defaultView = "day";
      
    args[args.length] = ["eid", eid];
    args[args.length] = ["save_type", "this_event"];
    args[args.length] = ["only_defined", "true"];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            ALib.statusShowAlert("Event Updated!", 3000, "bottom", "right");
        }

		// Delay refresh for 1 second because we are
		// working with almost real-time indexes (elasticsearch) now
		var bcls = this.cls;
		setTimeout(function(){ bcls.refresh(); }, 1000);
        //this.cls.refresh(); 
    };
    ajax.exec("/controller/Calendar/saveEvent", args);
}

/**
 * Get calendar color
 *
 * @param int calid the id of the calendar to get the color for
 */
AntCalendarBrowse.prototype.getCalendarColor = function(calid)
{
    var color = "eeeeee";

    for(calendar in this.calendars)
    {
        var currentCalendar = this.calendars[calendar];
        
        if (currentCalendar.id == calid && currentCalendar.color)
            color = currentCalendar.color;
    }

    return color;
}


/**
 * Set AntView class for managing pages and views
 */
AntCalendarBrowse.prototype.setAntView = function(parentView)
{
    this.antView = parentView;
    this.antView.setViewsSingle(true);
	this.antView.options.cal = this;
	this.antView.onresize = function()
	{
		this.options.cal.resize();
	}

	// Add auto-refresh when displayed and clear when hidden - every minute
	this.antView.on("show", function(opts) { opts.cls.setAutoRefresh(1000*60); }, { cls:this });
	this.antView.on("hide", function(opts) { opts.cls.setAutoRefresh(null); }, { cls:this });


    var viewItem = this.antView.addView("calendar_event:[id]", {});
	viewItem.options.obj_type = "calendar_event";
	viewItem.options.bwserCls = this;
	viewItem.options.loadLoaded = null;
	viewItem.render = function() { }
	viewItem.onshow = function()  // draws in onshow so that it redraws every time
	{ 
		this.con.innerHTML = "";
		this.title = ""; // because objects are loaded in the same view, clear last title

		var ol = new AntObjectLoader(this.options.obj_type, this.variable);
		ol.setAntView(this);

        // Set associations and values
        if (this.options.params)
        {
            for (var i = 0; i < this.options.params.length; i++)
            {
                ol.setValue(this.options.params[i][0], this.options.params[i][1]);
            }
        }

		ol.print(this.con);
		ol.cbData.antView = this;
		ol.cbData.bwserCls = this.options.bwserCls;
		ol.onClose = function() 
		{ 
			this.cbData.antView.options.lastLoaded = this.mainObject.id; // Set so this form reloads to new form if newly saved id
			this.cbData.bwserCls.refresh(); 
			this.cbData.antView.goup(); 
		}
		ol.onRemove = function() { this.cbData.bwserCls.refresh(); }

		this.options.lastLoaded = this.variable;
	};
	this.eventView = viewItem;
}

/**
 * Load an event by id
 */
AntCalendarBrowse.prototype.loadEvent = function(oid, params)
{
    if (this.antView)
    {
		if (this.eventView && params)
			this.eventView.options.params = (params) ? params : new Object();

		if (oid == null) oid = ""; // convert null to an empty string
        this.antView.navigate("calendar_event:"+oid);
    }
    else if (this.outerCon)
    {
        var oldScrollTop = alib.dom.getScrollPosTop(); // this.browserCon.scrollTop;
        
        alib.dom.styleSet(this.mainCon, "display", "none");
        var objfrmCon = alib.dom.createElement("div", this.outerCon);
        objfrmCon.cls = this;
        objfrmCon.oldScrollTop = oldScrollTop;
        objfrmCon.close = function()
        {                        
            this.style.display = "none";
            alib.dom.styleSet(this.cls.mainCon, "display", "block");
            objfrmCon.cls.outerCon.removeChild(this);
            alib.dom.setScrollPosTop(this.oldScrollTop);
        }

        // Print object loader 
        var ol = new AntObjectLoader("calendar_event", oid);
            
        // Set associations and values
        if (params)
        {
            for (var i = 0; i < params.length; i++)
            {
                ol.setValue(params[i][0], params[i][1]);
            }
        }
            
        // Use ol.print only for default               
        ol.print(objfrmCon);
        
        ol.objfrmCon = objfrmCon;
        ol.objBrwsrCls = this;
        ol.onClose = function()
        {                    
            this.objfrmCon.close();
        }
        if (!this.preview)
        {
            ol.onSave = function()
            {                
                this.objBrwsrCls.refresh();
            }
        }
        ol.onRemove = function()
        {
            this.objBrwsrCls.refresh();
        }
    }
    else
    {
        loadObjectForm("calendar_event", oid, null, null, params);
    }
}
