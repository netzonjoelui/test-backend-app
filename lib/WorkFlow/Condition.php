<?php
/**
 * Workflow Condition class
 *
 * Both WorkFlow and WorkFlow_Action classes use condtions to qualify launch. This class
 * is used to represent each condition and they may be linked together with boolean logic
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	Condition
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class represents conditions used in workflow and workflow_action classes
 */
class WorkFlow_Condition
{
	var $dbh;
	var $id;
	var $blogic;
	var $fieldName;
	var $operator;
	var $condValue;

	var $workflow_id;
	var $wf_action_id;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active account database handle
	 * @param string $blogic either "and" or "or" for boolean logic
	 * @param string $fieldName the name of the field or property to check against
	 * @param string $operator the query operator like "is_equal"
	 * @param string $condValue the query string to query gainst the fieldName
	 */
	function __construct($dbh, $blogic=null, $fieldName=null, $operator=null, $condValue=null)
	{
		$this->dbh = $dbh;

		if ($blogic)
			$this->blogic = $blogic;
		if ($fieldName)
			$this->fieldName = $fieldName;
		if ($operator)
			$this->operator = $operator;
		if ($condValue)
			$this->condValue = $condValue;
	}

	/**
	 * Save this condition to the database
	 */
	public function save()
	{
		$dbh = $this->dbh;
		if (is_numeric($this->id)) // Existing condition
		{
			$this->dbh->Query("update workflow_conditions set blogic='".$dbh->Escape($this->blogic)."', 
								field_name='".$dbh->Escape($this->fieldName)."', 
								operator='".$dbh->Escape($this->operator)."', 
								cond_value='".$dbh->Escape($this->condValue)."', 
								workflow_id=".$dbh->EscapeNumber($this->workflow_id).", 
								wf_action_id=".$dbh->EscapeNumber($this->wf_action_id)." 
								where id=".$this->id.";");
		}
		else
		{
			$result = $this->dbh->Query("insert into workflow_conditions(blogic, field_name, operator, cond_value, workflow_id, wf_action_id) 
										 values('".$dbh->Escape($this->blogic)."', '".$dbh->Escape($this->fieldName)."', 
										 '".$dbh->Escape($this->operator)."','".$dbh->Escape($this->condValue)."', ".$dbh->EscapeNumber($this->workflow_id).",
                                         ".$dbh->EscapeNumber($this->wf_action_id)."); select currval('workflow_conditions_id_seq') as id;");
			if ($this->dbh->GetNumberRows($result))
			{
				$this->id = $this->dbh->GetValue($result, 0, "id");
			}
		}

		return $this->id;
	}

	/**
	 * Delete this condition
	 *
	 * Right now this does nothing because we have a foreign trigger in the workflows table that deletes conditions.
	 * However, we are slowly moving all data logic to the application rather than the database to allow for
	 * further future flexibility so this function will eventually need to be used rather than relying on database
	 * specific data integrity routines.
	 */
	function remove()
	{
		if ($this->id)
		{
			$this->dbh->Query("DELETE FROM workflow_conditions where id='".$this->id."';");
		}
	}
}
