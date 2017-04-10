<?php
/**
 * Set a user field for assignment utilizng a round-robin
 *
 * @category	Ant
 * @package		WorkFlow_Action
 * @subpackage	AssignRR
 * @copyright	Copyright (c) 2014 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");
require_once("lib/AntUser.php");
require_once("lib/CAntObjectList.php");

/**
 * Class for assigning objects through round robin
 */
class WorkFlow_Action_AssignRR extends WorkFlow_Action_Abstract
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
        
        // Assign the first element
       	if ($ovals['update_to'])
       	{
       		$userNames = explode(",", $ovals['update_to']);

       		// Get first element/username
       		$nextUser = array_shift($userNames);

       		// Cleanup
       		$nextUser = trim($nextUser);	

       		// Get user id
       		$user = $this->getUser($nextUser);

       		// If this is an invalid user, then remove it from the list and go to the next one
       		if ($user === false)
       		{
       			// Save the new array without the bad entry
       			$act->update_to = implode(",", $userNames);
       			$act->save();

       			// Run again on the next value if there are other entries
       			if (count($userNames) > 0)
       				$this->execute($obj, $act); 
       		}
       		else
       		{
       			// user is valid, assign the lead
       			$obj->setValue($act->update_field, $user->id);
       			$obj->save();

       			// Put this user on the end of the round-robin list for subsequent calls
       			$userNames[] = $user->name;
       			$act->update_to = implode(",", $userNames);
       			$act->save();
       		}
       	}
	}

	/**
	 * Get a usr from the username
	 *
	 * @param string $username A unique username to get
	 * @return Antuser|bool false if user is not found
	 */
	public function getUser($userName)
	{
		if (empty($userName))
			return false;
		
		// Get Ant user
		$ant = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->getAnt();

		$olist = new CAntObjectList($this->dbh, "user", $ant->getUser());
		$olist->addCondition("and", "name", "is_equal", strtolower($userName));
		$olist->getObjects();
		if ($olist->getNumObjects()<1)
			return false;

		$userData = $olist->getObjectMin(0);
		$user = new AntUser($this->dbh, $userData["id"]);
		return $user;
	}
}
