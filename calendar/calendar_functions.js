var g_evid = 0;

function calEventOpen(eid, cbonsave, assoc, newwin)
{
	var params = 'width=1080,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? Base64.encode(unescape(cbonsave)) : '';
	var wndid = (eid) ? eid : g_evid++;
	var newwin = (typeof newwin != "undefined") ? newwin : true;
	var url = '/obj/calendar_event?cbonsave='+onsave;

	if (eid)
		url += "&oid="+eid;
        
	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	if (newwin)
		window.open(url, 'event_'+wndid, params);
	else
		document.location = url;
	
	/*
	var params = 'width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? Base64.encode(unescape(cbonsave)) : '';
	var wndid = (eid) ? eid : g_evid++;
	var newwin = (typeof newwin != "undefined") ? newwin : true;
	var url = '/calendar/event?cbonsave='+onsave;

	if (eid)
		url += "&eid="+eid;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	if (newwin)
		window.open(url, 'event_'+wndid, params);
	else
		document.location = url;
		*/
}

function calEventCoordOpen(ecid, cbonsave, assoc)
{
	var params = 'width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? Base64.encode(unescape(cbonsave)) : '';
	var wndid = (ecid) ? ecid : g_evid++;
    //var url = '/calendar/event_coord.php?cbonsave='+onsave;
	var url = '/obj/calendar_event_proposal';

	if (ecid)
		url += "&ecid="+ecid;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + assoc[i][1];
		}
	}

	window.open(url, 'eventcooord_'+wndid, params);
}

function calGetMonthName(month_number)
{
	var mn=['January','February','March','April','May','June','July','August','September','October','November','December'];
	if (month_number && month_number <= 12)
		return mn[month_number-1];
	else
		return -1;
}

function calGetMonthNumDays(year, month)
{
	var oD = new Date(year, month-1, 1); //DD replaced line to fix date bug when current day is 31st
	oD.od=oD.getDay()+1; //DD replaced line to fix date bug when current day is 31st

	var dim=[31,0,31,30,31,30,31,31,30,31,30,31]; // Dumber of days in each month
	// Handle number of days in february
	dim[1]=(((oD.getFullYear()%100!=0)&&(oD.getFullYear()%4==0))||(oD.getFullYear()%400==0))?29:28;

	return dim[month-1];
}

/*****************************************************************************
* Function:		calGetWeekStartDate
*
* Purpose:		Get the starting day for the current week
*****************************************************************************/
function calGetWeekStartDate(year, month, day) 
{
	var od=new Date(year, month-1, day);
	var weekday = od.getDay();

	if (weekday)
	{
		od = calDateAddSubtract(od, "day", -weekday);
	}

	return od;
}

/*****************************************************************************
* Function:		calDateAddSubtract
*
* Purpose:		Add or subtract a given number of days,weeks,months,years
*****************************************************************************/
function calDateAddSubtract(od, unit, interval) 
{
	var dte = new Date(od);
	switch (unit)
	{
	case "minute":
		//var dinc=60*1000;  //1 day in milisec
		var tm=dte.getTime();  //milliseconds, 0=January 1, 1970
		tm = tm+(dinc*interval);
		dte.setMinutes(od.getMinutes()+interval);
		break;
	case "hour":
		//var dinc=3600*1000;  //1 day in milisec
		var tm=dte.getTime();  //milliseconds, 0=January 1, 1970
		tm = tm+(dinc*interval);
		dte.setTime(tm);
		break;
	case "day":
		var dinc=3600*24*1000;  //1 day in milisec
		dte.setDate(od.getDate()+interval);
		break;
	case "month":
		if (interval == 1)
		{
			var thisMonth = dte.getMonth();
			dte.setMonth(thisMonth+1);
			if(dte.getMonth() != thisMonth+1 && dte.getMonth() != 0)
				dte.setDate(0)
		}
		else if (interval == -1)
		{
			var thisMonth = dte.getMonth();
			dte.setMonth(thisMonth-1);
			if (dte.getMonth() != thisMonth-1 && (dte.getMonth() != 11 || (thisMonth == 11 && dte.getDate() == 1)))
				dte.setDate(0);
		}
		break;
	}

	
	return dte;
}


/*****************************************************************************
* Function:		calDaysBetweenDates
*
* Purpose:		Get the number of days between two dates
*****************************************************************************/
function calDaysBetweenDates(date1, date2) 
{
    // The number of milliseconds in one day
    var ONE_DAY = 1000 * 60 * 60 * 24

    // Convert both dates to milliseconds
    var date1_ms = date1.getTime()
    var date2_ms = date2.getTime()

    // Calculate the difference in milliseconds
    var difference_ms = Math.abs(date1_ms - date2_ms)
    
    // Convert back to days and return
    return Math.round(difference_ms/ONE_DAY)
}

/*****************************************************************************
* Function:		calGetClockTime
*
* Purpose:		Get the time in a date as HH:MM AM/PM
*****************************************************************************/
function calGetClockTime(now)
{
   var hour   = now.getHours();
   var minute = now.getMinutes();
   var second = now.getSeconds();
   var ap = "AM";
   if (hour   > 11) { ap = "PM";             }
   if (hour   > 12) { hour = hour - 12;      }
   if (hour   == 0) { hour = 12;             }
   if (hour   < 10) { hour   = "0" + hour;   }
   if (minute < 10) { minute = "0" + minute; }
   if (second < 10) { second = "0" + second; }
   var timeString = hour +
					':' +
					minute +
					" " +
					ap;
   return timeString;
}
