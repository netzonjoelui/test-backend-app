var WF_TIME_UNIT_MINUTE	= 1;
var WF_TIME_UNIT_HOUR	= 2;
var WF_TIME_UNIT_DAY	= 3;
var WF_TIME_UNIT_WEEK	= 4;
var WF_TIME_UNIT_MONTH	= 5;
var WF_TIME_UNIT_YEAR	= 6;

var WF_TTYPE_SENDEMAIL 	= 1;
var WF_TTYPE_CREATEOBJ 	= 2;
var WF_TTYPE_UPDATEFLD 	= 3;
var WF_TTYPE_STARTCHLD 	= 4;
var WF_TTYPE_STOPWF 	= 5;

function wfGetTimeUnits()
{
	var buf = new Array();

	buf[0] = new Array(WF_TIME_UNIT_MINUTE, "Minute(s)");
	buf[1] = new Array(WF_TIME_UNIT_HOUR, "Hour(s)");
	buf[2] = new Array(WF_TIME_UNIT_DAY, "Day(s)");
	buf[3] = new Array(WF_TIME_UNIT_WEEK, "Week(s)");
	buf[4] = new Array(WF_TIME_UNIT_MONTH, "Month(s)");
	buf[5] = new Array(WF_TIME_UNIT_YEAR, "Year(s)");

	return buf;
}

function wfGetTimeUnitName(unit)
{
	var buf = "";
	var units = wfGetTimeUnits();

	for (var i = 0; i < units.length; i++)
	{
		if (units[i][0] == unit)
			buf = units[i][1];
	}

	return buf;
}
