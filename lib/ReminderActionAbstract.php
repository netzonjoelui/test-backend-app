<?php
/**
 * Reminder action base class
 */
require_once("lib/ReminderAction/Email.php");
require_once("lib/ReminderAction/Sms.php");
require_once("lib/ReminderAction/Popup.php");

abstract class ReminderActionAbstract
{
	/**
	 * Handle to reminder entitiy
	 *
	 * @var CAntObject_Reminder
	 */
	protected $reminder = null;

	/**
	 * Flag to handle test mode - don't actually send the email
	 *
	 * @var bool
	 */
	public $testMode = false;

	/**
	 * Class constructor
	 *
	 * @param Reminder $rem
	 */
	public function __construct($rem)
	{
		$this->reminder = $rem;
	}
}
