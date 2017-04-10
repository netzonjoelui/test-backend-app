<?php
/**
 * Action used to unit test various general functions of workflow actions
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	Test
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");

/**
 * Class for testing actions
 */
class WorkFlow_Action_Test extends WorkFlow_Action_Abstract
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
	 * Id of created invoice
	 *
	 * var int
	 */
	public $invoiceId = null;

	/**
	 * Execute action
	 *
	 * This extends common object creation because it has additional functions/features
	 * like sending invoice to customer and automated billing
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	public function execute($obj, $act)
	{
		$ovals = $act->getObjectValues();

		// Run child action with the 'test' event
		if ($ovals['runsubact'] == "yes")
			$act->executeChildActions("test");
	}
}
