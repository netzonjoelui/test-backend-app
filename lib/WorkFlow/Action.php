<?php
/**
 * WorkFlow Action object for ANT Objects
 *
 * Any object in ANT can trigger customizable automated workflows. Each workflow may
 * contain any number of actions each with their own conditions.
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	Action
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Action class for workflows
 */
class WorkFlow_Action
{
	var $dbh;
	var $id;    
	var $type;
    var $object_type;    
	var $workflow_id;
	var $name;
	var $when_interval; 	// Every nth
	var $when_unit; 		// unit: WF_TIME_UNIT_MINUTE
	var $workflowUser = null; // there is a special type of user for workflows

	/**
	 * Parent action id
	 *
	 * @var int
	 */
	public $parentActionId = null;

	/**
	 * Parent action id event
	 *
	 * Child actions can be limited to only fire after a certian event provided by the parent
	 *
	 * @var string 
	 */
	public $parentActionEvent = null;

	/**
	 * The object being processed with this action
	 *
	 * @var CAntObject
	 */
	public $objectBeingProcessed = null;

	/**
	 * Track when children actions were executed
	 *
	 * @var string 
	 */
	public $childActionsExecuted = array();

	/**
	 * Ant object
	 *
	 * @var Ant
	 */
	private $ant = null;

	var $conditions; 		// Array of WorkFlow_Condition(s)
	var $object_values;		// Array of values to be applied to a new object

	// WF_ATYPE_SENDEMAIL
	var $send_email_fid;	// the template file id

	// WF_ATYPE_UPDATEFLD
	var $update_field;		// The field_name to update
	var $update_to;			// the value to update to

	// WF_ATYPE_CREATEOBJ
	var $create_obj;		// Name of the object to create (lead, customer)

	// WF_ATYPE_STARTCHLD
	var $start_wfid;		// Id of workflow to start

	// WF_ATYPE_STOPWF
	var $stop_wfid;			// Id of workflow to stop

	// Child Actions
	var $actions = array();		// child WorkFlow_Action(s)

    /**
     * Map for converting names to IDS
     *
     * @var array
     */
    static public $types = array(
        'send_email' => WF_ATYPE_SENDEMAIL,
        'create_entity' => WF_ATYPE_CREATEOBJ,
        'update_field' => WF_ATYPE_UPDATEFLD,
        'start_workflow' => WF_ATYPE_STARTCHLD,
        'stop_workflow' => WF_ATYPE_STOPWF,
        'custom_function' => WF_ATYPE_CUSTFUN, // May be depricated?
        'test' => WF_ATYPE_TEST,
        'approval' => WF_ATYPE_APPROVAL,
        'webhook' => WF_ATYPE_CALLPAGE,
        'assign' => WF_ATYPE_ASSIGNRR,
    );

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active account database handle
	 * @param int $aid unique id of workflow action to load
	 * @param int $parentId Optional parent action id
	 */
	function __construct(&$dbh, $aid=null, $parentId=null)
	{
		$this->dbh = $dbh;
        $this->id = $aid;
		$this->type = WF_ATYPE_UPDATEFLD;

		if ($parentId)
			$this->parentActionId = $parentId;

		$this->conditions = array();
		$this->object_values = array();

        if ($aid)
        {
            $pos = strpos($aid, "uname:");
            if ($pos !== false)
                $aid = $this->openByName($aid, false); // Do not load workflow action
        }
        
		if ($aid)
		{
			$result = $dbh->Query("select name, type_name, when_interval, when_unit, send_email_fid, update_field, update_to, create_object,
									start_wfid, stop_wfid, workflow_id, type, parent_action_id, parent_action_event
									from workflow_actions where id='".$this->id."'");
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetRow($result, 0);
				$this->type = $row['type'];
				$this->name = $row['name'];
				$this->workflow_id = $row['workflow_id'];
				$this->when_interval = $row['when_interval'];
				$this->when_unit = $row['when_unit'];
				$this->send_email_fid = $row['send_email_fid'];
				$this->update_field = $row['update_field'];
				$this->update_to = $row['update_to'];
				$this->create_obj = $row['create_object'];
				$this->start_wfid = $row['start_wfid'];
				$this->stop_wfid = $row['stop_wfid'];
				$this->parentActionId = $row['parent_action_id'];

                // The new actions types are names and not IDs, translate old code here
                if ($row['type_name'] && !$row['type'])
                    $this->type = self::getTypeIdFromName($row['type_name']);

				if ($row['parent_action_event']) // We need to keep this null if not set
					$this->parentActionEvent = $row['parent_action_event'];
			}

			$this->loadConditions();
			$this->loadObjectValues();
			$this->loadActions();
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

        $query = "select id from workflow_actions where uname='".$dbh->Escape($uname)."'";
        $result = $dbh->Query($query);
        if ($dbh->GetNumberRows($result))
        {
            $this->id = $dbh->GetValue($result, 0, "id");
            
            if ($load)
            {
                $this->loadConditions();
                $this->loadObjectValues();
                $this->loadActions();
            }
                
            return $this->id;
        }
        else
        {
            $this->id = null;
            return false;
        }
    }

	/**
	 * Get workflow user object
	 */
	public function getWorkflowUser()
	{
		if (!$this->workflowUser) 
			$this->workflowUser = new AntUser($this->dbh, USER_WORKFLOW);

		return $this->workflowUser;
	}

	function save()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$this->dbh->Query("update workflow_actions set name='".$dbh->Escape($this->name)."',
			                    type='".$dbh->Escape($this->type)."',
			                    type_name='" . $dbh->Escape(self::getTypeNameFromId($this->type)) . "'
								workflow_id=".$dbh->EscapeNumber($this->workflow_id).",
								when_interval=".$dbh->EscapeNumber($this->when_interval).", when_unit=".$dbh->EscapeNumber($this->when_unit).",
								send_email_fid=".$dbh->EscapeNumber($this->send_email_fid).", update_field='".$dbh->Escape($this->update_field)."',
								update_to='".$dbh->Escape($this->update_to)."', create_object='".$dbh->Escape($this->create_obj)."',
								start_wfid=".$dbh->EscapeNumber($this->start_wfid).", stop_wfid=".$dbh->EscapeNumber($this->stop_wfid).",
								parent_action_id=".$dbh->EscapeNumber($this->parentActionId).", 
								parent_action_event='".$dbh->Escape($this->parentActionEvent)."'
								where id='".$this->id."'");
		}
		else
		{
            $uname = $this->createUniqueName();
			$result = $this->dbh->Query("insert into workflow_actions(name, when_interval, when_unit, send_email_fid, update_field, update_to, create_object, 
                                        start_wfid, stop_wfid, workflow_id, type, type_name, parent_action_id, parent_action_event, uname)
										 values('".$dbh->Escape($this->name)."', 
											".$dbh->EscapeNumber($this->when_interval).", ".$dbh->EscapeNumber($this->when_unit).",
											".$dbh->EscapeNumber($this->send_email_fid).", '".$dbh->Escape($this->update_field)."',
											'".$dbh->Escape($this->update_to)."', '".$dbh->Escape($this->create_obj)."',
											".$dbh->EscapeNumber($this->start_wfid).", ".$dbh->EscapeNumber($this->stop_wfid).",
											".$dbh->EscapeNumber($this->workflow_id).", '".$this->type."', '" . self::getTypeNameFromId($this->type) . "',
											".$dbh->EscapeNumber($this->parentActionId).", '".$dbh->Escape($this->parentActionEvent)."',
                                            '" . $dbh->Escape($uname) . "');
											select currval('workflow_actions_id_seq') as id;");

			if ($this->dbh->GetNumberRows($result))
			{
				$this->id = $this->dbh->GetValue($result, 0, "id");
			}
		}

		if ($this->id)
		{
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
            
            $query = "delete from workflow_conditions where workflow_id = '" . $this->workflow_id . "' and wf_action_id = '" . $this->id . "' $deleteCond;";
            $dbh->Query($query);
        
			foreach ($this->conditions as $cond)
			{
                $cond->workflow_id = $this->workflow_id;
				$cond->wf_action_id = $this->id;
				$cond->save();
			}

			foreach ($this->object_values as $oValName=>$oVal)
			{
				if (is_array($oVal))
				{
					$dbh->Query("delete from workflow_object_values where action_id='".$this->id."' and field='".$oValName."'");
					$result = $dbh->Query("insert into workflow_object_values(field, value, f_array, parent_id, action_id)
											values('".$oValName."', '', 't', NULL, '".$this->id."');
											select currval('workflow_object_values_id_seq') as id;");
					if ($dbh->GetNumberRows($result))
					{
						$pid = $dbh->GetValue($result, 0, "id");

						foreach ($oVal as $val)
						{
							$dbh->Query("insert into workflow_object_values(field, value, f_array, parent_id, action_id)
										 values('ANT_MULTIVAL', '".$dbh->Escape($val)."', 'f', '$pid', '".$this->id."');");
						}
					}
				}
				else
				{
					if ($dbh->GetNumberRows($dbh->Query("select id from workflow_object_values where action_id='".$this->id."' and field='".$oValName."'")))
					{
						$dbh->Query("update workflow_object_values set value='".$dbh->Escape($oVal)."' where action_id='".$this->id."' and field='".$oValName."'");
					}
					else
					{
						$dbh->Query("insert into workflow_object_values(field, value, f_array, parent_id, action_id)
									 values('".$oValName."', '".$dbh->Escape($oVal)."', 'f', NULL, '".$this->id."');");
					}
				}
			}
		}

		return $this->id;
	}

	function remove()
	{
		if ($this->id)
		{
			$this->dbh->Query("delete from workflow_actions where id='".$this->id."';");
			$this->id = null;
		}
	}

	function loadConditions()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$result = $dbh->Query("select id, blogic, field_name, operator, cond_value, workflow_id from 
								   workflow_conditions where wf_action_id='".$this->id."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$wfCondObj = new WorkFlow_Condition($this->dbh, $row['blogic'], $row['field_name'], $row['operator'], $row['cond_value']);
                $wfCondObj->id = $row['id'];
                $wfCondObj->wf_action_id = $this->id;
                
                $this->conditions[] = $wfCondObj;
			}
		}
	}

	function getCondition($idx)
	{
		return $this->conditions[$idx];
	}

	function addCondition($blogic=null, $fieldName=null, $operator=null, $condValue=null, $condId=null)
	{
        $condObj = new WorkFlow_Condition($this->dbh, $blogic, $fieldName, $operator, $condValue);
        $condObj->id = $condId;
		$this->conditions[] = $condObj;
		return $condObj;
	}

	function getConditionById($id)
	{
		foreach ($this->conditions as $cond)
		{
			if ($cond->id == $id)
				return $cond;
		}
	}

	function removeCondition($id)
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

	function getNumConditions()
	{
		return count($this->conditions);
	}

	function loadObjectValues()
	{
		$dbh = $this->dbh;

		if ($this->id)
		{
			$result = $dbh->Query("select id, field, value, f_array from 
								   workflow_object_values where action_id='".$this->id."' and parent_id is null");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			
			{
				$row = $dbh->GetRow($result, $i);

				if ($row['f_array'] == 't')
				{
					$res2 = $dbh->Query("select value from workflow_object_values where action_id='".$this->id."' and parent_id='".$row['id']."'");
					$num2 = $dbh->GetNumberRows($res2);
					for ($j = 0; $j < $num2; $j++)
					{
						$this->setObjectMultiValue($row['field'], $dbh->GetValue($res2, $j, "value"));
					}
				}
				else
				{
					$this->setObjectValue($row['field'], $row['value']);
				}
			}
		}
	}

	function setObjectValue($name, $value)
	{
		$this->object_values[$name] = $value;
	}

	function setObjectMultiValue($name, $value)
	{
		if (isset($this->object_values[$name]) && !is_array($this->object_values[$name]))
			$this->object_values[$name] = array();

		// Make sure this value does not already exists
		$bFound = false;
		if(isset($this->object_values[$name]) && count($this->object_values[$name]))
		{
			foreach ($this->object_values[$name] as $val)
			{
				if ($val == $value)
					$bFound = true;
			}
		}

		if (!$bFound)
			$this->object_values[$name][] = $value;
	}

	function removetObjectMultiValues($name)
	{
		$this->object_values[$name] = null;
		$this->object_values[$name] = array();
	}

	function getNumObjectValues()
	{
		return count($this->object_values);
	}

	function getObjectValues()
	{
		return $this->object_values;
	}

	/********************************************************************
	 * Function:	execute
	 *
	 * Purpose:		Execute a given action, if delayed, then put in db 
	 * 				for future execution
	 ********************************************************************/
	function execute(&$obj)
	{
		global $USER;
        
		$dbh = $this->dbh;
		$ovals = $this->getObjectValues();
		$this->replaceMergeVars($ovals, $obj); // replace <%vars%> with values from object
		$this->objectBeingProcessed = $obj;
 
		// execute now
		//echo "Execute: ".$this->name." ".$this->type."<br />";
        
		switch ($this->type)
		{
		case WF_ATYPE_SENDEMAIL:
			$email = CAntObject::factory($this->dbh, "email_message", null, $this->getWorkflowUser());
            
			if (isset($ovals["fid"]))
			{
                $templateObj = CAntObject::factory($this->dbh, "html_template", $ovals["fid"], $this->getWorkflowUser());
                $templateBody = $templateObj->getValue("body_html");
				$email->setHeader("Subject", ($templateObj->getValue("subject")) ? $templateObj->getValue("subject") : $templateObj->getValue("name"));
                $email->setBody($templateBody, "html");
			}
			else
			{
				$email->setHeader("Subject", $ovals['subject']);
				$email->setBody($ovals['body'], "plain");
			}

			// From
			$email->setHeader("From", $ovals['from']);
			$email->setHeader("Reply-to", $ovals['from']);
            
			// To
			$to = "";
			if (isset($ovals['to']) && is_array($ovals['to']))
			{
				foreach ($ovals['to'] as $rec)
				{
					if ($to) $to .= ", ";
					$to .= $rec;
				}
			}
			if (isset($ovals['to_other']))
			{
				if ($to) $to .= ", ";
				$to .= $ovals['to_other'];
			}
            
			$email->setHeader("To", $to);

			// Cc
			$to = "";
			if (isset($ovals['cc']) && is_array($ovals['cc']))
			{
				foreach ($ovals['cc'] as $rec)
				{
					if ($to) $to .= ", ";
					$to .= $rec;
				}
			}
			if (isset($ovals['cc_other']))
			{
				if ($to) $to .= ", ";
				$to .= $ovals['cc_other'];
			}
			$email->setHeader("Cc", $to);

			// Bcc
			//$ovals['bcc'][] = 'sky.stebnicki@aereus.com';
			$to = "";
			if (isset($ovals['bcc']) && is_array($ovals['bcc']))
			{
				foreach ($ovals['bcc'] as $rec)
				{
					if ($to) $to .= ", ";
					$to .= $rec;
				}
			}
			if (isset($ovals['bcc_other']))
			{
				if ($to) $to .= ", ";
				$to .= $ovals['bcc_other'];
			}
			$email->setHeader("Bcc", $to);
			
			if (isset($account))
			{
				/* TODO: investigate how to make this work better for associations
				$email->setHeader("X-ANT-ACCOUNT-NAME", $account);
				$email->setHeader("X-ANT-OBJ", $obj->object_type . ":" . $obj->id));
				*/
			}

			$email->setMergeFields($obj);

			// Check for "No bulk mail"
			$send = true;
			if ($obj->object_type == "customer")
			{
				if ($obj->getValue("f_noemailspam") == 't' || $obj->getValue("f_nocontact") == 't')
					$send = false;
			}
            
			if (isset($send))
			{
				$email->send();

				// This is a temporary solution Log activity for object
				$obj->addActivity("sent", "Workflow Email: ".$email->getHeader("subject"), 
								  "To: " . $email->getHeader("To") . "\n" . $email->getBody(true), null, 'o', 't', USER_WORKFLOW);
			}

			break;

		case WF_ATYPE_CREATEOBJ:

			switch ($this->create_obj)
			{
			case 'task':
				$taskobj = new CAntObject($dbh, "task", null, $this->getWorkflowUser());
				$taskobj->setValue("name", ($ovals['name']) ? $ovals['name'] : 'Automated Task');
				$taskobj->setValue("notes", $ovals['notes']);
				$taskobj->setValue("user_id", $ovals['user_id']);
				$taskobj->setValue("done", 'f');
				$taskobj->setValue("priority", ($ovals['priority']) ? $dbh->Escape($ovals['priority']) : '1');
				if (!$ovals['due_interval'] || !$ovals['due_unit'])
					$ts_deadline = time();
				else
					$ts_deadline = strtotime("+".$ovals['due_interval']." ".WorkFlow::getTimeUnitName($ovals['due_unit'])."s", time());
				$taskobj->setValue("deadline", date("Y-m-d", $ts_deadline));
				$taskobj->setValue("start_date", 'now');
				$taskobj->addAssociation($obj->object_type, $obj->id, "associations");
				$tid = $taskobj->save();
				if ($tid)
				{

					switch ($obj->object_type)
					{
					case "lead":
						$dbh->Query("insert into customer_tasks(task_id, lead_id) values('".$tid."', '".$obj->id."');");
						break;
					case "opportunity":
						$dbh->Query("insert into customer_tasks(task_id, opportunity_id) values('".$tid."', '".$obj->id."');");
						break;
					case "customer":
						$dbh->Query("update project_tasks set customer_id='".$obj->id."' where id='$tid'");
						break;
					}
				}

				break;

			case 'notification':
				$actNotif = new WorkFlow_Action_Notification($this->dbh);
				$actNotif->execute($obj, $this);
				break;
			case 'invoice':
				$actInv = new WorkFlow_Action_Invoice($this->dbh);
				$actInv->execute($obj, $this);
				break;
			}
			break;

		case WF_ATYPE_APPROVAL:
			$actApp = new WorkFlow_Action_Approval($this->dbh);
			$actApp->execute($obj, $this);
			break;

		case WF_ATYPE_UPDATEFLD:
			$actApp = new WorkFlow_Action_UpdateField($this->dbh);
			$actApp->execute($obj, $this);
			/*
			// First check if we are updating an associated field
			if (strpos($this->update_field, '.'))
			{
				// 0 = field_name, 1 = ref_obj_type, 2 = ref_obj_field
				$parts = explode(".", $this->update_field);
				if (count($parts) == 3)
				{
					$fld = $obj->fields->getField($parts[0]);

					if ($fld["type"] == "object" && !$fld['subtype'])
					{
						$val = $obj->getValue($parts[0]);

						if ($val)
						{
							$ref_parts = explode(":", $val);

							if (count($ref_parts) > 1)
							{
								if ($ref_parts[0] == $parts[1]) // Make sure we are working with the same type of object
								{
									$ref_obj = new CAntObject($this->dbh, $ref_parts[0], $ref_parts[1], $this->user);
									$ref_obj->setValue($parts[2], $this->update_to);
									$ref_obj->save();
								}
							}
						}
					}
					else if ($fld["type"] == "object" && $fld['subtype'] && $fld['subtype'] == $parts[1])
					{
						$val = $obj->getValue($parts[0]);

						if ($val)
						{
							$ref_obj = new CAntObject($this->dbh, $fld['subtype'], $val, $this->user);
							$ref_obj->setValue($parts[2], $this->update_to);
							$ref_obj->save();
						}
					}
				}
			}
			else
			{
				$field = $obj->fields->getField($this->update_field);
				if ($field['type'] == "fkey_multi" || $field['type'] == "object_multi")
				{
					$obj->setMValue($this->update_field, $this->update_to);
				}
				else
				{
					$update_to = $this->update_to;

					$all_fields = $obj->fields->getFields();
					foreach ($all_fields as $fname=>$fdef)
					{
						if ($fdef['type'] != "object_multi" && $fdef['type'] != "fkey_multi")
						{
							if ($update_to == "<%".$fname."%>")
								$update_to = $obj->getValue($fname);
						}
					}

					$obj->setValue($this->update_field, $update_to);
				}
				$obj->save();
			}
			 */
			break;

		case WF_ATYPE_STARTCHLD:
			if ($this->start_wfid)
			{
				$wf = new WorkFlow($this->dbh, $this->start_wfid);
				if ($this->user)
					$wf->user = $this->user;

				if ($wf->conditionsMatch($obj) && $this->id!=$wf->id)
				{
					//$this->processed_workflows[] = $wf->id;
					$wf->execute($obj);
				}
			}

			break;
		case WF_ATYPE_STOPWF:
			//var $stop_wfid;			// Id of workflow to stop
			break;
		case WF_ATYPE_CUSTFUN:
			// TODO: handle custom functions
			// $this->customFunction;
			// $this->customData;
			break;
		case WF_ATYPE_TEST:
			$actApp = new WorkFlow_Action_Test($this->dbh);
			$actApp->execute($obj, $this);
			break;
        
        case WF_ATYPE_CALLPAGE:
            $actApp = new WorkFlow_Action_CallPage($this->dbh);
            $actApp->execute($obj, $this);
            break;
        case WF_ATYPE_ASSIGNRR:
            $actApp = new WorkFlow_Action_AssignRR($this->dbh);
            $actApp->execute($obj, $this);
            break;
		}

		// Execute child workflows without any events
		$this->executeChildActions();
	}

	/**
	 * Launch child actions
	 *
	 * @param string $event Optional event to filter specific actions - like (on)'update' or (on)'success'
	 * @param CAntObject $obj Optional obj to be processed. This is normally set in '$this::execute'
	 */
	function executeChildActions($event=null, $obj=null)
	{
		if ($obj)
			$this->objectBeingProcessed = $obj;

		foreach ($this->actions as $action)
		{
			if ($action->parentActionEvent == $event) // Both will be null if no event
			{
				if ($this->objectBeingProcessed)
				{
					$action->execute($this->objectBeingProcessed);
					$this->childActionsExecuted[] = $action->id;	
				}
			}
		}
	}
    
	/**
	 * Replace merge variables with hard values from the object
	 */
	public function replaceMergeVars(&$ovars, $object)
	{
		// Put update to in the ovars for the merge
		if ($this->update_to)
			$ovars['update_to'] = $this->update_to;

		foreach ($ovars as $varname=>$varval)
		{
			if (is_array($varval))
			{
				for ($i = 0; $i < count($varval); $i++)
				{
					$matches = array();
					$iterations = 0; // for safety
					while (preg_match("/<%(.*?)%>/", $varval[$i], $matches))
					{
						$pull_var = $matches[1];

						// Handle system variables
                        // Removed the switch case and transfer the handling of system variables in if statements
						/*switch ($pull_var)
						{
						case 'object_link':
							$ovars[$varname][$i] = str_replace("<%object_link%>", 
																   "https://$settings_localhost/obj/" . $object->object_type . '/' . $object->id, 
																   $varval[$i]);
                            
							break;
						}*/

						// Check if this is an associated variable
						if (strpos($pull_var, '.') === false)
						{
							$type = $object->getFieldType($pull_var);
							if ((($type['type'] == 'object' && $type['subtype'] == "user")) 
								 && ($varname=='to' || $varname=='cc' || $varname=='bcc'))
							{
								$userId = $object->getValue($pull_var);
								if ($userId)
								{
									$refUser = new AntUser($this->dbh, $userId);
									$ovars[$varname][$i] = str_replace("<%$pull_var%>", 
																	   $refUser->getEmail(), 
																	   $varval[$i]);
								}

							}
                            else if($pull_var=="object_link")
                            {
                                $ovars[$varname][$i] = str_replace("<%object_link%>", 
                                              $this->getAccBaseUrl()."/obj/" . $object->object_type . '/' . $object->id, 
                                              $varval[$i]);
                            }
                            else if($pull_var=="email_default")
                            {
								$useField = $object->getValue($pull_var);
								$ovars[$varname][$i] = str_replace("<%$pull_var%>", 
																   $object->getValue($useField), 
																   $varval[$i]);
                            }
							else
							{
								$ovars[$varname][$i] = str_replace("<%$pull_var%>", 
																   $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), 
																   $varval[$i]);
							}
						}
						else
						{
							$parts = explode(".", $pull_var);
							$fieldName = $parts[0];
							$ref_field = $parts[1];

							$type = $object->getFieldType($fieldName);
							$tmpobj = new CAntObject($this->dbh, $type['subtype']);

							// Check for user_id to email conversion
							$type = $tmpobj->getFieldType($ref_field);
							if ((($type['type'] == 'object' && $type['subtype'] == "user")) 
									&& ($varname=='to' || $varname=='cc' || $varname=='bcc'))
							{
								$userId = $tmpobj->getValue($ref_field);
								if ($userId)
								{
									$refUser = new AntUser($this->dbh, $userId);
									$ovars[$varname][$i] = str_replace("<%$pull_var%>", 
																	   $refUser->getEmail(), 
																	   $ovars[$varname][$i]);
								}
							}
							else
							{
								//$ovars[$varname][$i] = $tmpobj->getValue($ref_field, ($type['type'] == 'alias')?true:false);
								$ovars[$varname][$i] = str_replace("<%$pull_var%>", 
																   $tmpobj->getValue($ref_field, ($type['type'] == 'alias')?true:false), 
																   $ovars[$varname][$i]);
							}
						}

                        // This should be used to break while loop if $ovars already has a value
                        /*if(isset($ovars[$varname][$i]))
                            break;*/
                            
						// Prevent infinite loop
						$iterations++;
						if ($iterations > 5000)
							break;
					}
				}
			}
			else
			{
				$matches = array();
				$iterations = 0; // for safety
				while (preg_match("/<%(.*?)%>/", $varval, $matches))
				{
					$pull_var = $matches[1];
                    
                    if($pull_var == "object_type" && !empty($object->object_type))
                    {
                        $varval = str_replace("<%$pull_var%>", $object->object_type, $varval);
                    }
					else if (strpos($pull_var, '.') === false) // Check if this is an associated variable
					{
						$type = $object->getFieldType($pull_var);
						if ((($type['type'] == 'fkey' && $type['subtype'] == "users") || ($type['type'] == 'object' && $type['subtype'] == "user")) 
							&& ($varname=='to' || $varname=='cc' || $varname=='bcc'))
						{
							$userId = $object->getValue($pull_var);
							if ($userId)
							{
								$refUser = new AntUser($this->dbh, $userId);
								$varval = str_replace("<%$pull_var%>", $refUser->getEmail(), $varval);
							}
						}
                        else if($pull_var=="object_link")
                        {
                            $varval = str_replace("<%object_link%>", $this->getAccBaseUrl()."/obj/" . $object->object_type . '/' . $object->id, $varval);
                        }
						else
						{
							$varval = str_replace("<%$pull_var%>", $object->getValue($pull_var, ($type['type'] == 'alias')?true:false), $varval);
						}
					}
					else
					{
						// Legacy: make sure that user object vars map to email fields
						if ($varname=='to' || $varname=='cc' || $varname=='bcc')
						{
							$parts = explode(".", $pull_var);
							$fieldName = $parts[0];
							$ref_field = $parts[1];

							$type = $object->getFieldType($fieldName);
							$tmpobj = new CAntObject($this->dbh, $type['subtype']);

							// Check for user_id to email conversion
							$type = $tmpobj->getFieldType($ref_field);
							if ((($type['type'] == 'fkey' && $type['subtype'] == "users") || ($type['type'] == 'object' && $type['subtype'] == "user")))
							{
								$pull_var .= ".email"; // pull email address
							}
						}
						
						$varval = str_replace("<%$pull_var%>", $object->getValueDeref($pull_var), $varval);
					}

					$ovars[$varname] = $varval;

					// Prevent infinite loop
					$iterations++;
					if ($iterations > 5000)
						break;
				}
			}
		}

		// Put merged value back for UpdateField actions
		$this->update_to = isset($ovars['update_to']) ? $ovars['update_to'] : null;
	}

	function scheduleAction($instnace_id)
	{
		$ts_execute = strtotime("+".$this->when_interval." ".WorkFlow::getTimeUnitName($this->when_unit)."s", time());
		$this->dbh->Query("insert into workflow_action_schedule(action_id, ts_execute, instance_id)
							 values('".$this->id."', '".date("Y-m-d g:i a", $ts_execute)."', '$instnace_id');");
	}

	function conditionsMatch($obj)
	{
		$ret = true;

		for ($i = 0; $i < $this->getNumConditions(); $i++)
		{
			$cond = $this->getCondition($i);
			/*
			$cond->blogic;
			$cond->fieldName;
			$cond->operator;
			$cond->condValue;
			*/

			// TODO: Add blogic
			$field_type = $obj->getFieldType($cond->fieldName);

			switch ($cond->operator)
			{
			case 'is_equal':
				if ($field_type["type"] == "fkey_multi" || $field_type["type"] == "object_multi")
				{
					if (!$obj->getMValueExists($cond->fieldName, $cond->condValue))
						$ret = false;
				}
				else
				{
					$actual_value = $obj->getValue($cond->fieldName);
					if (strtolower($actual_value) != strtolower($cond->condValue))
						$ret = false;
				}
				break;
			case 'is_not_equal':
				if ($field_type["type"] == "fkey_multi" || $field_type["type"] == "object_multi")
				{
					if ($obj->getMValueExists($cond->fieldName, $cond->condValue))
						$ret = false;
				}
				else
				{
					$actual_value = $obj->getValue($cond->fieldName);
					if (strtolower($actual_value) == strtolower($cond->condValue))
						$ret = false;
				}
				break;
			case 'is_greater':
				$actual_value = $obj->getValue($cond->fieldName);
				if ($actual_value <= $cond->condValue)
					$ret = false;
				break;
			case 'is_less':
				$actual_value = $obj->getValue($cond->fieldName);
				if ($actual_value >= $cond->condValue)
					$ret = false;
			case 'is_greater_or_equal':
				$actual_value = $obj->getValue($cond->fieldName);
				if ($actual_value < $cond->condValue)
					$ret = false;
				break;
			case 'is_less_or_euqal':
				$actual_value = $obj->getValue($cond->fieldName);
				if ($actual_value > $cond->condValue)
					$ret = false;
				break;
			case 'begins_with':
				$actual_value = $obj->getValue($cond->fieldName);
				if (strtolower(substr($actual_value, 0, strlen($cond->condValue))) != strtolower($cond->condValue))
					$ret = false;
				break;
			case 'contains':
				$actual_value = $obj->getValue($cond->fieldName);
				if (strtolower(stripos($actual_value, strtolower($cond->condValue))) === false)
					$ret = false;
				break;
			}
		}

		return $ret;
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
									parent_action_id='".$this->id."' 
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
		if ($this->workflow_id)
			$this->actions[count($this->actions)-1]->workflow_id = $this->workflow_id;
		if ($this->id)
			$this->actions[count($this->actions)-1]->parentActionId = $this->id;

		return $this->actions[count($this->actions)-1];
	}

	/**
	 * Covert properties to an array and return data (used for json mostly)
	 *
	 * @return array Associative array of the properties of this action
	 */
	public function toArray()
	{
		// Set general properties
		$ret = array(
			"id" => $this->id,
			"type" => $this->type,
			"workflow_id" => $this->workflow_id,
			"name" => $this->name,
			"when_interval" => $this->when_interval,
			"when_unit" => $this->when_unit,
			"parentActionId" => $this->parentActionId,
			"parentActionEvent" => $this->parentActionEvent,
			"object_values" => $this->object_values,
			"send_email_fid" => $this->send_email_fid,
			"update_field" => $this->update_field,
			"update_to" => $this->update_to,
			"create_obj" => $this->create_obj,
			"stop_wfid" => $this->stop_wfid,
			"stop_wfid" => $this->stop_wfid,
		);


		// Set conditions
		$ret['conditions'] = array();
		foreach ($this->conditions as $cond)
		{
			$ret['conditions'][] = array(
                "id" => $cond->id,
				"blogic" => $cond->blogic,
				"fieldName" => $cond->fieldName,
				"operator" => $cond->operator,
				"condValue" => $cond->condValue,
			);
		}

		// Set child actions array
		$ret['child_actions'] = array();
		for ($i = 0; $i < $this->getNumActions(); $i++)
		{
			$cact = $this->getAction($i);
			$ret['child_actions'][] = $cact->toArray();
		}

		return $ret;
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

	/**
	 * Get the base url for this account
	 *
	 * @return string
	 */
	public function getAccBaseUrl()
	{
		if (!$this->ant)
			$this->ant = new Ant($this->dbh->accountId);
		
		return $this->ant->getAccBaseUrl();
	}

	/**
	 * Get the old 'id' from a name
	 *
	 * @param string $name Get the old id from a name
     * @return int
	 */
	static public function getTypeIdFromName($name)
	{
		return self::$types[$name];
	}

	/**
	 * Get the new 'name' from the old 'id'
	 *
	 * @param int $id
     * @return string
	 */
	static public function getTypeNameFromId($id)
	{
		foreach (self::$types as $aname=>$aid)
        {
            if ($aid === $id)
                return $aname;
        }

        return null;
	}
}
