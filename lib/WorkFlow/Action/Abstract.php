<?php
/**
 * Workflow actions
 *
 * WorkFlow_Action_Abstract is the based class for all derrived action types. Action types
 * can be Email, Object, Update, or any other cutom type defined.
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	Action(s)
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Base class used for specific action implementations
 */
abstract class WorkFlow_Action_Abstract
{
	/**
	 * Handle to active account database
	 *
	 * var CDatabase
	 */
	public $dbh = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active handle to account database
	 */
	function __construct($dbh)
	{
		$this->dbh = $dbh;
	}

	/**
	 * The execute function must be defined in each available action type
	 *
	 * @param CAntObject $obj object that we are running this workflow action against
	 * @param WorkFlow_Action $act current action object
	 */
	abstract public function execute($obj, $act);
}
