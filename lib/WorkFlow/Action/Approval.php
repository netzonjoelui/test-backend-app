<?php
/**
 * Action to handle approval requests
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	Approval
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");
require_once("lib/Object/Approval.php");

/**
 * Class for approval workflow actions
 */
class WorkFlow_Action_Approval extends WorkFlow_Action_Abstract
{
	/**
	 * Payment gateway type override
	 *
	 * This can be set to a specific payment gateway type to override the default
	 *
	 * var int
	 */
	public $pgwType = null;

	/**
	 * Execute action
	 *
	 * This extends common object creation because it has additional functions/features
	 * for creating approval object types and launching workflows
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	public function execute($obj, $act)
	{
		$ovals = $act->getObjectValues();
		$act->replaceMergeVars($ovals, $obj); // replace <%vars%> with values from object
        
        // Create
		$appObj = new CAntObject_Approval($this->dbh, null, $this->user);
		$appObj->setValue("name", $act->name);
		$appObj->setValue("workflow_action_id", $act->id);
		$appObj->setValue("requested_by", $this->user->id);
		$appObj->setValue("owner_id", $ovals['owner_id']);
		$appObj->setValue("obj_reference", $obj->object_type.":".$obj->id);
		$appObj->save();

		//$act->executeChildActions("approved");
		//$act->executeChildActions("declined");
	}
}
