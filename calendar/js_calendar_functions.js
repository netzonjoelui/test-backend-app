
function SetStartDate(day, month, monthname, year)
{
	//var i = document.getElementById('start_time_val');
	//i.innerHTML = monthname + " " + day + ", " + year;
	TPopup('start_time', 'hidden');
	document.evnt.start_date.value = monthname + " " + day + ", " + year;
	
	var sdate = new Date(document.evnt.start_date.value);
	var edate = new Date(document.evnt.end_date.value);
	if (edate.getTime() < sdate.getTime())
	{
		document.evnt.end_date.value = monthname + " " + day + ", " + year;
	}
}
function SetEndDate(day, month, monthname, year)
{
	//var i = document.getElementById('end_time_val');
	//i.innerHTML = monthname + " " + day + ", " + year;
	TPopup('end_time', 'hidden');
	
	document.evnt.end_date.value = monthname + " " + day + ", " + year;
	
	var sdate = new Date(document.evnt.start_date.value);
	var edate = new Date(document.evnt.end_date.value);
	
	if (edate.getTime() < sdate.getTime())
	{
		document.evnt.start_date.value = monthname + " " + day + ", " + year;
	}
}
function SetAllDay()
{
	if (document.evnt.all_day.checked)
	{
		document.evnt.start_time_hour.disabled = true;
		document.evnt.start_time_min.disabled = true;
		document.evnt.start_time_ampm.disabled = true;
		document.evnt.end_time_hour.disabled = true;
		document.evnt.end_time_min.disabled = true;
		document.evnt.end_time_ampm.disabled = true;
	}
	else
	{
		document.evnt.start_time_hour.disabled = false;
		document.evnt.start_time_min.disabled = false;
		document.evnt.start_time_ampm.disabled = false;
		document.evnt.end_time_hour.disabled = false;
		document.evnt.end_time_min.disabled = false;
		document.evnt.end_time_ampm.disabled = false;
	}
}

function SetNoEnd()
{
	var odate = new Date();
	if (document.evnt.noend.checked)
	{
		document.evnt.end_date.disabled = true;
		document.evnt.end_date.value = "Never";
	}
	else
	{
		document.evnt.end_date.disabled = false;
		document.evnt.end_date.value = odate.getMonth()+"/"+odate.getDate()+"/"+odate.getFullYear();
	}
}

function EndTimeHour()
{
	if (document.evnt.end_time_hour.value == 0 &&
		document.evnt.end_date.value == document.evnt.start_date.value)
	{
		var monthname=new Array("January","February","March","April","May","June","July","August","September","October","November","December")
		sdate = new Date(document.evnt.end_date.value);
		// Create new date with end date + 1 day or 86400000 mseconds
		var odate = new Date(sdate.getTime() + 86400000);
	
		document.evnt.end_date.value = monthname[odate.getMonth()] + " " + odate.getDate() + ", " + odate.getFullYear();
	}
}
function ValidateTimes(frmSave)
{
	var bSubmit = true;
	sdate = new Date(document.evnt.start_date.value);
	stime = document.evnt.start_time_hour.value;
	stimeAmPm = document.evnt.start_time_ampm.value;
	edate = new Date(document.evnt.end_date.value);
	etime = document.evnt.end_time_hour.value;
	etimeAmPm = document.evnt.end_time_ampm.value;
	
	if (document.evnt.evname.value == '')
	{
		alert("Please enter a name for this event!");
		bSubmit = false;
	}

	if (sdate.getTime() <= edate.getTime())
	{
		if (sdate.getTime() == edate.getTime())
		{
			switch (stimeAmPm)
			{
			case 'AM':
				if (etimeAmPm == 'AM')
				{
					if (parseInt(stime) > parseInt(etime))
					{
						alert("Start time cannot be later than end time!");
						bSubmit = false;
					}
				}
				break;
			case 'PM':
				if (etimeAmPm == 'AM')
				{
					alert("Start time cannot be later than end time!");
					bSubmit = false;
				}
				else
				{
					if (parseInt(stime) > parseInt(etime))
					{
						alert("Start time cannot be later than end time!");
						bSubmit = false;
					}
				}
				break;
			}
		}
	}
	else
	{
		if (document.evnt.end_date.value != "Never")
		{
			alert("'Start Date' cannot be later than 'End Date'!");
			bSubmit = false;
		}
	}
	
	if (frmSave == "save_recur" && document.evnt['iType'].value < 1)
	{
		alert("You have not selected a recurring type. Please select a type below before saving!");
		bSubmit = false;
	}
	
	if (bSubmit == true)
	{
		GBL_CHECKFORDIRTY = false;
		document.evnt[frmSave].value = '1';
		document.evnt.submit();
	}
}

function RecurringSetHiddenVal(valname, val)
{
	document.evnt[valname].value=val;
}

function DeleteEvnt(eid, rid, retpage, recur_id, exception_date)
{
	var retLink = "event.awp?retpage="+retpage+"&recur_id="+recur_id+"&exception_date="+exception_date;
	if (eid)
	{
		if (confirm("Are you sure you want to delete this event?"))
		{
			document.location = retLink+"&deid="+eid;
		}
	}
	if (rid)
	{
		if (confirm("Are you sure you want to delete this recurring event?"))
		{
			document.location = retLink+"&drid="+rid;
		}
	}
}


function AttendeesSetHiddenVal(valname, val)
{
	document.evnt.elements[valname].value = val;
}

function AttendeesSubmit()
{
	document.evnt.submit();
}

function RecurringGetVal()
{
	if (navigator.appName.indexOf("Microsoft Internet")!=-1)
		alert(window.recurring_mov.GetVariable("iTest"));
	else
		alert(document.recurring_mov.GetVariable("iTest"));
}

function recurring_mov_DoFSCommand(command, args) 
{
    document.evnt[command].value=args;
}

function ChangeRemType(fwdvars)
{
	switch (document.evnt['type'].value)
	{
	case '1':
		document.location = 'event.awp?' + fwdvars + "&type=1";
		break;
	case '2':
		document.location = 'event.awp?' + fwdvars + "&type=2";
		break;
	case '3':
		document.location = 'event.awp?' + fwdvars + "&type=3";
		break;
	default:
		document.location = 'event.awp?' + fwdvars;
		break;
	}
}