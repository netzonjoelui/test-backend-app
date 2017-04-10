<?php
/**
 * Email reminder action
 *
 * @category  Reminder
 * @package   Email
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Email.php");

class ReminderAction_Email extends ReminderActionAbstract
{
	/**
	 * Execute this action
	 */
	public function execute()
	{
		if (!$this->reminder->getValue("send_to"))
		{
			if ($this->reminder->getValue("owner_id"))
			{
				$user = new AntUser($this->reminder->dbh, $this->reminder->getValue("owner_id"));
				$this->reminder->setValue("send_to", $user->getEmail());
			}
				
			// If still empty
			if (!$this->reminder->getValue("send_to"))
				return false;
		}

		// Create new email object
		$headers = array();
		$headers['From'] = AntConfig::getInstance()->email['noreply'];
		$headers['To'] = $this->reminder->getValue("send_to");
		$headers['Subject'] = $this->getSubject();
		$body = $this->getBody();
		$email = new Email();

		if($this->testMode)
		{
			// Debug data being set
			return array(
				"headers" => $headers,
				"body" => $body,
			);
		}
		else
		{
			$status = $email->send($headers['To'], $headers, $body);
			unset($email);
			return true;
		}
	}

	/**
	 * Construct the subject of the reminder email
	 */
	public function getSubject()
	{
		$subject = "Reminder: ";

		$obj = $this->reminder->getReferencedObject();
		if ($obj)
			$subject .= $obj->getName();

		return $subject;
	}

	/**
	 * Create the body of the reminder
	 */
	public function getBody()
	{
		$obj = $this->reminder->getReferencedObject();

		$body = "";

		if ($obj)
		{
			if ($obj->object_type == "calendar_event")
			{
				$body .= $this->getBodyCalendarEvent();
			}
			else
			{
				$body .= "Reminder: " . $obj->getName() . "\r\n";
				$body .= $this->reminder->getValue("notes") . "\r\n";
			}

			$body .= "\r\n\r\nClick below for more details:\r\n";
			$body .= $obj->getAccBaseUrl() . "/obj/" . $obj->object_type . "/" . $obj->id;
		}

		return $body;
	}

	/**
	 * Get body snippet for calendar events
	 */
	public function getBodyCalendarEvent()
	{
		$obj = $this->reminder->getReferencedObject();
		if (!$obj)
			return "";

		$body = "Calendar Event: " . $obj->getName() . "\r\n";
		if ($obj->getValue("all_day") == 't')
		{
			$body .= "Add Day Event\r\n";
			$body .= "Starts: " . date("m/d/Y", strtotime($obj->getValue("ts_start"))) . "\r\n";
			$body .= "Ends: " . date("m/d/Y", strtotime($obj->getValue("ts_end"))) . "\r\n";
		}
		else
		{
			$body .= "Starts: " . $obj->getValue("ts_start") . "\r\n";
			$body .= "Ends: " . $obj->getValue("ts_end") . "\r\n";
		}

		// Add notes
		$body .= "\r\n" . $obj->getValue("notes");

		return $body;
	}
}
