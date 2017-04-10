<?php
/**
 * Aereus Object Approval
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for approval processes
 *
 * @category  CAntObject
 * @package   CAntObject_Approval
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing approvals in ANT
 */
class CAntObject_Approval extends CAntObject
{
	/**
	 * Items in this invoice
	 *
	 * @var array(stdCls(id, invoice_id, quantity, name, amount, product_id))
	 */
	private $itemsDetail = array();

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "approval", $eid, $user);
	}

	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
	}

	/**
	 * Function used for derrived classes to hook onload event
	 *
	 * This is called after CAntObject base loads all properties
	 */
	protected function loaded()
	{
	}
}
