<?php
/**
 * Email reminder action
 *
 * @category  Reminder
 * @package   Popup
 * @copyright Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
class ReminderAction_Popup extends ReminderActionAbstract
{
	/**
	 * Execute this action
	 */
	public function execute()
	{
		if (!$this->reminder->getValue("owner_id"))
			return false;

		$user = new AntUser($this->reminder->dbh, $this->reminder->getValue("owner_id"));

		// Insert a notifications system backend
		$notification = CAntObject::factory($this->reminder->dbh, "notification", null, $user);
		$notification->setValue("name", $this->getName());
		$notification->setValue("description", $this->getDescription());
		$notification->setValue("obj_reference", $this->reminder->getValue("obj_reference"));
		$notification->setValue("f_popup", 't');
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $this->reminder->getValue("owner_id"));
		$notification->save();

		if($this->testMode)
		{
			return $notification;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Construct the subject of the reminder email
	 */
	public function getName()
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
	public function getDescription()
	{
		$obj = $this->reminder->getReferencedObject();

		$body = "";

		if ($obj)
		{
			if ($obj->object_type == "calendar_event")
			{
				$body .= $this->getDescCalendarEvent();
			}
			else
			{
				$body .= "Reminder: " . $obj->getName() . "<br />";
				$body .= $this->reminder->getValue("notes") . "<br />";
			}
		}

		return $body;
	}

	/**
	 * Get body snippet for calendar events
	 */
	public function getDescCalendarEvent()
	{
		$obj = $this->reminder->getReferencedObject();
		if (!$obj)
			return "";

		$body = "Calendar Event: " . $obj->getName() . "<br />";
		if ($obj->getValue("all_day") == 't')
		{
			$body .= "Add Day Event<br />";
			$body .= "Starts: " . date("m/d/Y", strtotime($obj->getValue("ts_start"))) . "<br />";
			$body .= "Ends: " . date("m/d/Y", strtotime($obj->getValue("ts_end"))) . "<br />";
		}
		else
		{
			$body .= "Starts: " . $obj->getValue("ts_start") . "<br />";
			$body .= "Ends: " . $obj->getValue("ts_end") . "<br />";
		}

		// Add location
		$body .= "<br />Location: " . $obj->getValue("location");

		// Add notes
		$body .= "<br />" . $obj->getValue("notes");

		return $body;
	}
}
