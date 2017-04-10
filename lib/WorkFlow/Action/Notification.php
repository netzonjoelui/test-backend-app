<?php
/**
 * Action used to unit test various general functions of workflow actions
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	Notification
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");

/**
 * Class for testing actions
 */
class WorkFlow_Action_Notification extends WorkFlow_Action_Abstract
{
	/**
	 * Execute creating a new notification
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	public function execute($obj, $act)
	{
		$ovals = $act->getObjectValues();
		$act->replaceMergeVars($ovals, $obj); // replace <%vars%> with values from object

		// Make sure we have an owner for this notification
		if (!$ovals['owner_id'])
			return;

		// Add notification for user
		$notification = CAntObject::factory($this->dbh, "notification", null, $act->getWorkflowUser());
		$notification->setValue("name", $ovals['name']);
		$notification->setValue("description", $ovals['description']);
		$notification->setValue("obj_reference", $obj->object_type.":".$obj->id);
		$notification->setValue("f_popup", $ovals['f_popup']);
		$notification->setValue("f_email", $ovals['f_email']);
		$notification->setValue("f_sms", $ovals['f_sms']);
		$notification->setValue("f_seen", 'f');
		$notification->setValue("owner_id", $ovals['owner_id']);
		$nid = $notification->save();
	}
}
