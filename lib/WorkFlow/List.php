<?php
/**
 * Workflow List Class
 *
 * WorkFlow_List is used to query multiple workflows
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	List
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class represents conditions used in workflow and workflow_action classes
 */
class WorkFlow_List
{
	var $worflows;
	var $dbh;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active account database handle
	 * @param string condition an optional sql query condition
	 */
	function __construct($dbh, $condition="")
	{
		$this->workflows = array();
		$this->dbh = $dbh;

		$query = "select id from workflows where id is not null ";
		if ($condition)
			$query .= "and ($condition)";

		$result = $dbh->Query($query);
		for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
		{
			$id = $dbh->GetValue($result, $i, "id");
			$ind = count($this->workflows);
			$this->workflows[$ind] = array();
			$this->workflows[$ind][0] = $id;
			$this->workflows[$ind][1] = null;
		}
	}

	/**
	 * Get number of workflows returned
	 *
	 * @return int number of workflows
	 */
	function getNumWorkFlows()
	{
		return count($this->workflows);
	}

	/**
	 * Get workflow by index
	 *
	 * @param int $idx the array offset of the index to retrieve
	 * @return WorkFlow
	 */
	function getWorkFlow($idx)
	{
		if ($this->workflows[$idx][1] == null)
			$this->workflows[$idx][1] = new WorkFlow($this->dbh, $this->workflows[$idx][0]);
		
		return $this->workflows[$idx][1];
	}
}
