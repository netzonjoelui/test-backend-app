<?php
	require_once('lib/File/IMC.php');
	require_once('lib/File/IMC/Parse.php');
	require_once('lib/File/IMC/Exception.php');
	require_once('lib/File/IMC/Parse/Vcalendar.php');

	class CImcCalendar
	{
		var $parser;	// File_IMC parse object
		var $caldata;	// The data buffer
		var $events;
		var $method="";

		function CImcCalendar()
		{
			$this->parser = File_IMC::parse('vCalendar');

			$this->events = array();
			$this->caldata = null;
		}

		function parseText($buf)
		{
			$this->caldata = $this->parser->fromText($buf);
			$this->parseData();
		}

		function parseData()
		{
			$this->method = $this->caldata['VCALENDAR'][0]['METHOD'][0]['value'][0][0];
			if ($this->caldata)
			{
				for ($i = 0; $i < count($this->caldata['VCALENDAR'][0]['VEVENT']); $i++)
				{
					$this->events[] = new CImcCalendarEvent($this->caldata['VCALENDAR'][0]['VEVENT'][$i], $this->parser);
				}
			}
		}

		function getNumEvents()
		{
			return count($this->events);
		}

		function getEvent($ind=0)
		{
			if ($this->events[$ind])
				return $this->events[$ind];
			else
				return null;
		}
	}

	class CImcCalendarEvent
	{
		var $organizer="";
		var $description="";
		var $ts_start="";
		var $ts_end="";
		var $timezone="";
		var $summary="";
		var $uid="";
		var $class="";
		var $priority="";
		var $dtstamp="";
		var $status="";
		var $sequence="";
		var $location="";
		var $parser = null;

		var $attendees = array();

		function CImcCalendarEvent($arr_evnt=null, $parser = null)
		{
			$this->parser = $parser;

			if ($arr_evnt)
			{
				$this->description = $arr_evnt['DESCRIPTION'][0]['value'][0][0];
				$this->summary = $arr_evnt['SUMMARY'][0]['value'][0][0];
				$this->ts_start = $arr_evnt['DTSTART'][0]['value'][0][0];
				$this->ts_end = $arr_evnt['DTEND'][0]['value'][0][0];
				$this->timezone = $arr_evnt['DTEND'][0]['param']['TZID'][0];
				$this->uid = $arr_evnt['UID'][0]['value'][0][0];
				$this->class = $arr_evnt['CLASS'][0]['value'][0][0];
				$this->priority = $arr_evnt['PRIORITY'][0]['value'][0][0];
				$this->dtstamp = $arr_evnt['DTSTAMP'][0]['value'][0][0];
				$this->status = isset($arr_evnt['STATUS']) ? $arr_evnt['STATUS'][0]['value'][0][0] : null;
				$this->sequence = $arr_evnt['SEQUENCE'][0]['value'][0][0];
				$this->location = $arr_evnt['LOCATION'][0]['value'][0][0];

				for ($i = 0; $i < count($arr_evnt['ATTENDEE']); $i++)
				{
					$this->attendees[] = new CImcCalendarEventAttendee($arr_evnt['ATTENDEE'][$i]);
				}

				if ($this->ts_start)
					$this->ts_start = strtotime($this->ts_start);
				if ($this->ts_end)
					$this->ts_end = strtotime($this->ts_end);
			}
		}

		function getNumAttendees()
		{
			return count($this->attendees);
		}

		function getAttendee($ind=0)
		{
			return $this->attendees[$ind];
		}
	}

	class CImcCalendarEventAttendee
	{
		var $role = "";
		var $partstat = "";
		var $rsvp = "";
		var $cn = "";
		var $name = ""; // value

		function CImcCalendarEventAttendee($arr_att = null)
		{
			if ($arr_att)
			{
				$this->role = $arr_att['param']['ROLE'][0];
				$this->partstat = $arr_att['param']['PARTSTAT'][0];
				$this->rsvp = $arr_att['param']['RSVP'][0];
				$this->cn = $arr_att['param']['CN'][0];
				$this->name = $arr_att['value'][0][0];
			}
		}
	}
?>
