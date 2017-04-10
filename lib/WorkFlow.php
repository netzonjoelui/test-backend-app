<?php
/**
 * WorkFlow object for ANT Objects
 *
 * Any object in ANT can trigger customizable automated workflows. Each workflow may
 * contain any number of actions each with their own conditions.
 *
 * @category  Ant
 * @package   WorkFlow
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");
require_once("lib/WorkFlow/Condition.php");
require_once("lib/WorkFlow/List.php");
// Workflow Actions
require_once("lib/WorkFlow/Action.php");
require_once("lib/WorkFlow/Action/Test.php");
require_once("lib/WorkFlow/Action/Notification.php");
require_once("lib/WorkFlow/Action/Invoice.php");
require_once("lib/WorkFlow/Action/Approval.php");
require_once("lib/WorkFlow/Action/CallPage.php");
require_once("lib/WorkFlow/Action/UpdateField.php");
require_once("lib/WorkFlow/Action/AssignRR.php");

/**
 * Define standard time units for workflow actions
 */
define("WF_TIME_UNIT_MINUTE", "1");
define("WF_TIME_UNIT_HOUR", "2");
define("WF_TIME_UNIT_DAY", "3");
define("WF_TIME_UNIT_WEEK", "4");
define("WF_TIME_UNIT_MONTH", "5");
define("WF_TIME_UNIT_YEAR", "6");

/**
 * Workflow action types
 */
define("WF_ATYPE_SENDEMAIL", "1");
define("WF_ATYPE_CREATEOBJ", "2");
define("WF_ATYPE_UPDATEFLD", "3");
define("WF_ATYPE_STARTCHLD", "4");
define("WF_ATYPE_STOPWF", "5");
define("WF_ATYPE_CUSTFUN", "6");
define("WF_ATYPE_TEST", "7"); // This is an action that can be used for testing
define("WF_ATYPE_APPROVAL", "8"); // Used to submit any object for approval
define("WF_ATYPE_CALLPAGE", "9"); // Used to submit any object for approval
define("WF_ATYPE_ASSIGNRR", "10"); // Round robin assign
define("WF_ATYPE_WAITCONDITION", "11"); // Wait Condition
define("WF_ATYPE_CHECKCONDITION", "12"); // Check Condition

/**
 * Main workflow class
 */
class WorkFlow
{
	var $id;
	var $dbh;
	var $name;
	var $notes;
	var $object_type;	// lead, customer, opportunity 
	var $fActive;
	var $user;
    var $revision;
	// Events that cuase this workflow to initiate
	var $fOnCreate;
	var $fOnUpdate;
	var $fOnDelete;
	var $fOnDaily;
	// Some option
	var $fSingleton;	// Only allow one instance of this workflow
    var $fAllowManual;    // Allow a user to manually start workflow
	var $fConditionUnmet;	// Allow a user to manually start workflow
	// Arrays
	var $actions;		// WorkFlow_Action(s) to perform array
	var $conditions;	// WorkFlow_Condition(s) array

	/**
	 * Unique name
	 *
	 * @var string
	 */
	private $uname = "";

	/**
	 * Flag used for debuggin
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active handle to account database
	 * @param int $wid optional id of workflow to load
	 * @return WorkFlow $this
	 */
	function __construct($dbh, $wid=null)
	{
		$this->dbh = $dbh;
		$this->id = $wid;
		$this->user = null;

		$this->fSingleton = true;
		$this->fAllowManual = true;

		$this->actions = array();
		$this->conditions = array();

        // Convert uname to proper workflow id        
        if ($wid)
        {
            $pos = strpos($wid, "uname:");
            if ($pos !== false)
                $this->openByName($wid);
            else
                $this->loadWorkflow();
        }
	}
    
     /**
     * Load an object by a unique name
     * 
     * @param string uname  unique name of the workflow
     * @param boolean load  Determines whether to load the workflow or not
     * @return integer      id of the workflow
     */
    function openByName($uname, $load=true)
    {
        $dbh = $this->dbh;

        // need to properly explode the $uname
        $pos = strpos($uname, "uname:");
        if ($pos !== false) 
        {
            // Get the value of uname using explode and : as delimeter
            $parts = explode(":", $uname);
            return $this->openByName($parts[1], $load);
        }

        $query = "select id from workflows where uname='".$dbh->Escape($uname)."'";
        $result = $dbh->Query($query);
        if ($dbh->GetNumberRows($result))
        {
            $this->id = $dbh->GetValue($result, 0, "id");
            
            if ($load)
                $this->loadWorkflow();
                
            return $this->id;
        }
        else
        {
            $this->id = null;
            return false;
        }
    }
    
	/**
	 * Load workflow definition from database
	 * @return bool true on success, false of failues
	 */
	public function loadWorkflow()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$result = $dbh->Query("select id, name, uname, notes, object_type, f_on_create, f_on_update, f_on_delete, f_on_daily,
									f_singleton, f_allow_manual, f_active, f_condition_unmet, revision from workflows where id='".$this->id."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);                
				$this->name 		= $row['name'];
				$this->uname 		= $row['uname'];
				$this->notes 		= $row['notes'];
                $this->object_type  = $row['object_type'];
				$this->revision 	= $row['revision'];
				$this->fOnCreate 	= ($row['f_on_create'] == 't') ? true : false;
				$this->fOnUpdate 	= ($row['f_on_update'] == 't') ? true : false;
				$this->fOnDelete 	= ($row['f_on_delete'] == 't') ? true : false;
				$this->fOnDaily		= ($row['f_on_daily'] == 't') ? true : false;
				$this->fSingleton 	= ($row['f_singleton'] == 't') ? true : false;
                $this->fAllowManual = ($row['f_allow_manual'] == 't') ? true : false;
				$this->fConditionUnmet = ($row['f_condition_unmet'] == 't') ? true : false;
				$this->fActive 		= ($row['f_active'] == 't') ? true : false;

				$this->loadActions();
				$this->loadConditions();

				return true;
			}
		}

		return false;
	}

	/**
	 * Load actions from database
	 */
	public function loadActions()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$result = $dbh->Query("select id from workflow_actions where 
									workflow_id='".$this->id."' and parent_action_id is NULL 
									order by when_interval");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$this->addAction($dbh->GetValue($result, $i, "id"));
			}
		}
	}

	/**
	 * Load actions from database
	 *
	 * @param int $idx the index of the action to retrieve
	 */
	public function getAction($idx)
	{
		return $this->actions[$idx];
	}

	/**
	 * Load actions from database
	 *
	 * @param int $id the index of the action to retrieve
	 */
	public function getActionById($id)
	{
		for ($i = 0; $i < count($this->actions); $i++)
		{
			if ($this->actions[$i]->id == $id)
				return $this->actions[$i];
		}
		return null;
	}

	/**
	 * Get the number of actions associated with this workflow
	 */
	public function getNumActions()
	{
		return count($this->actions);
	}

	/**
	 * Add an action to this workflow
	 *
	 * @param CDatabase $dbh active handle to account database
	 * @param int $aid optional id of the action to add
	 * @return WorkFlow_Action new action
	 */
	public function addAction($aid=null)
	{
		$this->actions[] = new WorkFlow_Action($this->dbh, $aid);
		if ($this->id)
			$this->actions[count($this->actions)-1]->workflow_id = $this->id;

		return $this->actions[count($this->actions)-1];
	}
    
	/**
	 * Get number of conditions associated with this workflow
	 *
	 * @return int number of conditions
	 */
	public function getNumConditions()
	{
		return count($this->conditions);
	}

	/**
	 * Get a condition at a specified index
	 *
	 * @param int $idx the index of the condition to retrieve
	 * @return WorkFlow_Condition
	 */
	public function getCondition($idx)
	{
		return $this->conditions[$idx];
	}

	/**
	 * Get array of conditions that cam be used for CAntObjectList
	 * @return array(array('condition_blogic_x', 'condition_fieldname_x', 'condition_operator_x', condition_condvalue_x'))
	 */
	public function getConditionObjListVars()
	{
		$conditions = array();

		for ($i = 0; $i < $this->getNumConditions(); $i++)
		{
			$cond = $this->getCondition($i);
			/*
			$cond->blogic;
			$cond->fieldName;
			$cond->operator;
			$cond->condValue;
			*/
			$conditions['conditions'][] = $i;
			$conditions['condition_blogic_'.$i] = $cond->blogic;
			$conditions['condition_fieldname_'.$i] = $cond->fieldName;
			$conditions['condition_operator_'.$i] = $cond->operator;
			$conditions['condition_condvalue_'.$i] = $cond->condValue;
		}

		return $conditions;
	}

	/**
	 * Load conditions attributed to this workflow from the database
	 */
	function loadConditions()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$result = $dbh->Query("select id, blogic, field_name, operator, cond_value, workflow_id from 
								   workflow_conditions where workflow_id='".$this->id."' and wf_action_id IS NULL");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$wfCondObj = new WorkFlow_Condition($this->dbh, $row['blogic'], $row['field_name'], $row['operator'], $row['cond_value']);				
                $wfCondObj->id = $row['id'];
                $wfCondObj->wf_action_id = $row['workflow_id'];
                
                $this->conditions[] = $wfCondObj;
			}
		}
	}

	/**
	 * Save workflow
	 *
	 * @return int $id the unique id of the workflow or false on failure
	 */
	public function save()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
            $query = "update workflows set 
                            name='".$dbh->Escape($this->name)."', 
                            notes='".$dbh->Escape($this->notes)."',  
                            object_type='".$dbh->Escape($this->object_type)."',   
                            revision=".$dbh->EscapeNumber($this->revision + 1).",
                            f_active='".(($this->fActive)?'t':'f')."',
                            f_on_create='".(($this->fOnCreate)?'t':'f')."',
                            f_on_update='".(($this->fOnUpdate)?'t':'f')."',
                            f_on_delete='".(($this->fOnDelete)?'t':'f')."',
                            f_on_daily='".(($this->fOnDaily)?'t':'f')."',
                            f_singleton='".(($this->fSingleton)?'t':'f')."',
                            f_allow_manual='".(($this->fAllowManual)?'t':'f')."',
                            f_condition_unmet='".(($this->fConditionUnmet)?'t':'f')."' 
                            where id='".$this->id."'";
			$dbh->Query($query);
		}
		else
		{
            $uname = $this->createUniqueName();
            $query = "insert into workflows(name, notes, object_type, f_on_create, f_on_update, f_on_delete, f_on_daily,
                                    f_singleton, f_allow_manual, f_active, f_condition_unmet, revision, uname) 
                                    values('".$dbh->Escape($this->name)."', '".$dbh->Escape($this->notes)."',  
                                    '".$dbh->Escape($this->object_type)."', '".(($this->fOnCreate)?'t':'f')."', 
                                    '".(($this->fOnUpdate)?'t':'f')."',
                                    '".(($this->fOnDelete)?'t':'f')."', 
                                    '".(($this->fOnDaily)?'t':'f')."', 
                                    '".(($this->fSingleton)?'t':'f')."', 
                                    '".(($this->fAllowManual)?'t':'f')."', 
                                    '".(($this->fActive)?'t':'f')."',
                                    '".(($this->fConditionUnmet)?'t':'f')."',
                                    1,
                                    '" . $dbh->Escape($uname) . "');
                                   select currval('workflows_id_seq') as id;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$this->id = $dbh->GetValue($result, 0, "id");
			}
		}

        $condId = array();
        $deleteCond = null;
        
        // Remove the unused conditions
        foreach ($this->conditions as $cond)
        {                
            if (is_numeric($cond->id)) // valid numeric id (Not the new + integer)
                $condId[] = $cond->id;
        }
        
        if(sizeof($condId) > 0)
        {
            $deleteCond = "and id not in (" . implode(",", $condId) . ")";
        }
        
		if ($this->id)
		{
			$query = "delete from workflow_conditions where workflow_id = '" . $this->id . "' and wf_action_id IS NULL $deleteCond;";
			$dbh->Query($query);
		}
        
        //save conditions
		foreach ($this->conditions as $cond)
		{            
			$cond->workflow_id = $this->id;
			$cond->save();
		}

        // save actions
		foreach ($this->actions as $act)
		{
			$act->workflow_id = $this->id;
			$act->save();
		}

		return $this->id;
	}

	/**
	 * Delete this workflow
	 */
	public function remove()
	{
		if ($this->id)
		{
			$this->dbh->Query("delete from workflows where id='".$this->id."';");
			$this->id = null;
		}
	}
    
	/**
	 * Add a condition with values
	 *
	 * @param bool $blogic either 'and' or 'or
	 * @param string $fieldName the name of the field to check against
	 * @param string $operator the conditional operator, ie. 'is_equal'
	 * @param string $condValue the value to compare $fieldName against with $operator
	 * @return WorkFlow_Condition
	 */
	public function addCondition($blogic=null, $fieldName=null, $operator=null, $condValue=null, $condId=null)
	{
        $condObj = new WorkFlow_Condition($this->dbh, $blogic, $fieldName, $operator, $condValue);
        $condObj->id = $condId;
		$this->conditions[] = $condObj;
        return $condObj;
	}
    
	/**
	 * Get a condition by id
	 *
	 * @param int $id the unique id of the condition to retrieve
	 * @return WorkFlow_Condition on success, false on failure
	 */
	public function getConditionById($id)
	{
		foreach ($this->conditions as $cond)
		{
			if ($cond->id == $id)
				return $cond;
		}

		return false;
	}

	/**
	 * Remove all conditions from this workflow
	 */
	public function removeConditions()
	{		
		$this->conditions = null;
		$this->conditions = array();
	}

	/**
	 * Remove a condition by id
	 *
	 * @param int $id the unique id of the condition to remove
	 */
	public function removeCondition($id)
	{
		for ($i = 0; $i < count($this->conditions); $i++)
		{
			if ($this->conditions[$i]->id == $id)
			{
				$this->conditions[$i]->remove();
				$this->conditions = array_splice($this->conditions, $i, 1);
			}
		}
	}

	/**
	 * Check to see if object conditions match
	 *
	 * Check to see if object in question meets the conditional requirements
	 * for this workfow. Using CAntObjectList allows us to standardize
	 * all conditional process. If any objects are returned with the qualifying
	 * "id"= condition then we know the conditions for this workflow match the object
	 *
	 * @param CAntObject $obj the object to check against to see if conditions match
	 */
	public function conditionsMatch($obj)
	{
		$ret = true;

        $ol = new CAntObjectList($this->dbh, $obj->object_type, $obj->user);
        $ol->addCondition("and", "id", "is_equal", $obj->id);

		// Check if we are working with a deleted object
		if ('t' == $obj->getValue("f_deleted"))
        	$ol->addCondition("and", "f_deleted", "is_equal", 't');

        $fieldChanged = false;
        for ($i = 0; $i < $this->getNumConditions(); $i++)
        {
            $cond = $this->getCondition($i);
            $ol->addCondition($cond->blogic, $cond->fieldName, $cond->operator, $cond->condValue);

            if($obj->fieldValueChanged($cond->fieldName))
                $fieldChanged = true;
        }

        // If condition is met with this id (set above) then object meets execution requirements
        //$ol->hideDeleted = false;
        $ol->getObjects();        
        if ($ol->getNumObjects())
        {
            if(!$fieldChanged && $this->fConditionUnmet)
                $ret = false;
            else
                $ret = true;
        }            
        else
        {
            $ret = false;
        }

        return $ret;
	}

	/**
	 * Execute actions for a specific object
	 *
	 * @param CAntObject $obj the object that this workflow is being fired from
	 */
	public function execute($obj)
	{
		$dbh = $this->dbh;

		// Make sure we are not violating any singleton rules
		if ($this->fSingleton && !$this->singletonOk($obj))
			return;

		$result = $dbh->Query("insert into workflow_instances(workflow_id, object_type_id, object_type, object_uid, ts_started, f_completed)
								 values('".$this->id."', '".$obj->object_type_id."', '" . $obj->object_type . "', '".$obj->id."', 'now', 'f');
								 select currval('workflow_instances_id_seq') as id;");
		if ($dbh->GetNumberRows($result))
			$this->instance_id = $dbh->GetValue($result, 0, "id");

		// Log activity for object
		$obj->addActivity("started", "Workflow:".$this->name, $this->notes, null, '', 't', USER_WORKFLOW);

		// Loop through actions and eexecute
		for ($a = 0; $a < $this->getNumActions(); $a++)
		{
			$act = $this->getAction($a);

			if ($act->conditionsMatch($obj))
			{
				/**
				 * joe: We may move everything to a background process due to performance hits
				if (!AntConfig::getInstance()->workers['background_enabled'])
					$act->execute($obj);
				else
					$act->scheduleAction($this->instance_id);
				 */
				
				if ($act->when_interval == 0)
					$act->execute($obj);
				else
					$act->scheduleAction($this->instance_id);
			}
		}

		// Close workflow if all tasks have been completed
		$this->updateStatus($this->instance_id);
	}

	/**
	 * Check if an instance of this workflow is already running for this object
	 *
	 * @param CAntObject $obj the object that is firing the workflow
	 */
	public function singletonOk($obj)
	{
		$dbh = $this->dbh;

		$result = $dbh->Query("select id from workflow_instances where object_type_id='".$obj->object_type_id."'
								and	object_uid='".$obj->id."' and workflow_id='".$this->id."' and f_completed is not true;");
		if ($dbh->GetNumberRows($result))
			return false;

		return true;
	}

	/**
	 * Check if there are any outstanding actions to perform.
	 *
	 * If there are no additional actions, then this workflow instance will be set to completed.
	 * Each workflow when fired against an object is done so in a specific instance. Updating the
	 * completed status for this workflow only applied to the current instance
	 *
	 * @param int $instance_id required unique identifier to the instance to update
	 */
	public function updateStatus($instance_id)
	{
		$dbh = $this->dbh;
		$result = $dbh->Query("select id from workflow_action_schedule where instance_id='".$instance_id."';");
		if (!$dbh->GetNumberRows($result))
		{
			// All actions are done
			$dbh->Query("update workflow_instances set ts_completed='now', f_completed='t' 
								where id='$instance_id';");
		}
	}

	/**
	 * Get a textual name for the time unit
	 *
	 * @param int $uid the time unit id to convert to a textual name
	 * @return string $name of unit, null of failure
	 */
	public function getTimeUnitName($uid)
	{
		switch ($uid)
		{
		case WF_TIME_UNIT_MINUTE;
			return "minute";
		case WF_TIME_UNIT_HOUR;
			return "hour";
		case WF_TIME_UNIT_DAY;
			return "day";
		case WF_TIME_UNIT_WEEK;
			return "week";
		case WF_TIME_UNIT_MONTH;
			return "month";
		case WF_TIME_UNIT_YEAR;
			return "year";
		}

		return null;
	}

	/**
	 * Get object from instance id
	 *
	 * @param CDatabase $dbh active handle to account database
	 * @param int $instance_id the unique id of the instante to poll
	 * @return CAntObject
	 */
	public function getInstanceObj($dbh, $instance_id)
	{
		if ($instance_id)
		{
			$result = $dbh->Query("select workflow_instances.object_type_id, workflow_instances.object_uid,
									app_object_types.name
									from workflow_instances, app_object_types
									where workflow_instances.object_type_id=app_object_types.id
									and workflow_instances.id='".$instance_id."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);

				return new CAntObject($dbh, $row['name'], $row['object_uid']);
			}
		}
	}

	/**
	 * Find out if an activity is pending for a workflow instance
	 *
	 * @param CDatabase $dbh active handle to account database
	 * @param int $actschedId the unique id of the scheduled action
	 * @return bool true if in progress, false if none are in the queue
	 */
	function instanceActInProgress($dbh, $actschedId)
	{
		if ($actschedId)
		{
			$result = $dbh->Query("select inprogress from workflow_action_schedule where id='".$actschedId."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);

				if ($row['inprogress'] == '1')
					return true;
				else
				{
					$dbh->Query("update workflow_action_schedule set inprogress='1' where id='".$actschedId."'");
					return false;
				}
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Set the uname of this workflow
	 *
	 * @param string $uname The unique name to set
	 */
	public function setUname($uname)
	{
		$this->uname = $uname;
	}
    
    /**
     * Get a name alias for this object
     *
     * @return string uname     Unique name of the workflow entry
     */
    private function createUniqueName()
    {
        $dbh = $this->dbh;
        $name = $this->name;

		// If already set then use what was set
		if ($this->uname)
			return $this->uname;

        // Escape
        $uname = strtolower($name);
        $uname = str_replace(" ", "-", $uname);
        $uname = str_replace("?", "", $uname);
        $uname = str_replace("&", "_and_", $uname);
        $uname = preg_replace('/[^A-Za-z0-9_-]/', '', $uname);

        // Now make sure this name is unique, and increment if so
        $query = "select uname from workflows where uname='$uname'";
        $result = $dbh->Query($query);
        if ($dbh->GetNumberRows($result))
        {
            if ($this->id)
            {
                // Append id which is always unique
                $uname = $uname . "-" . $this->id;
            }
            else
            {
                $useModifiedName = "";

                // Append a number to the unique name
                for ($i = 0; $i < 300; $i++)
                {
                    unset($result);
                    
                    $modified_name = $uname."-".($i+1);
                    $query = "select uname from workflows where uname='$modified_name'";
                    $result = $dbh->Query($query);
                    if (!$dbh->GetNumberRows($result))
                    {
                        $useModifiedName = $modified_name;
                        break;
                    }
                }

                if ($useModifiedName)
                    $uname = $useModifiedName;
                else
                    $uname = $uname."-u".substr(microtime(), 0, 8); // create unique id
            }
        }
        
        return $uname;
    }
}
