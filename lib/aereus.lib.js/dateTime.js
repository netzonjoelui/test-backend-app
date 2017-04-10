/**
 * @fileOverview alib.ui.dateTime Date and time functions, mostly wraps jquery-dateFormat
 *
 * Date Time Patterns
 * yy = short year
 * yyyy = long year
 * M = month (1-12)
 * MM = month (01-12)
 * MMM = month abbreviation (Jan, Feb � Dec)
 * MMMM = long month (January, February � December)
 * d = day (1 - 31)
 * dd = day (01 - 31)
 * ddd = day of the week in words (Monday, Tuesday � Sunday)
 * D - Ordinal day (1st, 2nd, 3rd, 21st, 22nd, 23rd, 31st, 4th�)
 * h = hour in am/pm (0-12)
 * hh = hour in am/pm (00-12)
 * H = hour in day (0-23)
 * HH = hour in day (00-23)
 * mm = minute
 * ss = second
 * SSS = milliseconds
 * a = AM/PM marker
 * p = a.m./p.m. marker
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2014 Aereus Corporation. All rights reserved.
 */

/**
 * Create dateTime namespace
 *
 * @var {Object}
 */
alib.dateTime = {};

/**
 * Format a date value into the format provided
 *
 * @param {string|Date} value The date to format
 * @param {string} format The string to use as the format template
 */
alib.dateTime.format = function(value, format)
{
	if (format == "long_ago")
		return jQuery.format.prettyDate(value);
	else
		return jQuery.format.date(value, format);
}

/**
 * Convert a date with a timezone to browser time
 *
 * @param {string|Date} value The date to format
 */
alib.dateTime.toBrowserTimeZone = function(value)
{
	return jQuery.format.toBrowserTimeZone(value);
}
