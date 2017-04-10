<?php
require_once("lib/Dacl.php");
require_once("lib/aereus.lib.php/CAntCustomer.php");
require_once("lib/CAntObjectFields.php");
require_once("lib/WorkFlow.php");

/**
* Actions for interacting with workflows in ANT
*/
class WorkFlowController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
    }

    /**
    * Get array of action data in a chain
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getWorkFlowActions($params)
    {        
		$ret = array();

		$wf = new WorkFlow($this->ant->dbh, $params['wfid']);

		// Convert all actions to associative arrays
		for ($i = 0; $i < $wf->getNumActions(); $i++)
		{
			$act = $wf->getAction($i);
			$ret[] = $act->toArray();
		}

		return $this->sendOutputJson($ret);
    }

    /**
    * Save action data and return id on success
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveAction($params)
    {
		$act = new WorkFlow_Action($this->ant->dbh, $params['id']);
		$act->type = $params['type'];
		$act->name = $params['name'];
		$act->workflow_id = $params['workflow_id'];
		$act->when_interval = $params['when_interval'];
		$act->when_unit = $params['when_unit'];
		$act->send_email_fid = $params['send_email_fid'];
		$act->update_field = $params['update_field'];
		$act->update_to = $params['update_to'];
		$act->create_obj = $params['create_obj'];
		$act->start_wfid = $params['start_wfid'];
		$act->stop_wfid = $params['stop_wfid'];
		$act->parentActionId = $params['parent_action_id'];
		$act->parentActionEvent = $params['parent_action_event'];

		if (is_array($params['conditions']))
		{
			foreach ($params['conditions'] as $actId)
			{
				$cond = $act->addCondition();
                $cond->id = $actId;                
				$cond->blogic = $params['condition_'.$actId.'_blogic'];
				$cond->fieldName = $params['condition_'.$actId.'_fieldname'];
				$cond->operator = $params['condition_'.$actId.'_operator'];
				$cond->condValue = $params['condition_'.$actId.'_condvalue'];
			}
		}

		// Set new object values
		if (is_array($params['ovals']))                                                                                          
		{                                                                                                            
			foreach ($params['ovals'] as $varname)                                                                       
			{                                                                                                           
				if (is_array($params['oval_'.$varname]))                           
				{
					$act->removetObjectMultiValues($varname);
					foreach ($params['oval_'.$varname] as $mval)
						$act->setObjectMultiValue($varname, $mval);
				}
				else
				{                                                                                                         
					$act->setObjectValue($varname, $params['oval_'.$varname]);                                         
				}
			}
		}

		$id = $act->save();

		return $this->sendOutputJson($id);
	}

    /**
    * Delete action from workflow
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function removeAction($params)
    {
		$act = new WorkFlow_Action($this->ant->dbh, $params['id']);

		if ($act->id)
			$act->remove();
		
		return $this->sendOutputJson(true);
	}
    
    /**
    * Get the workflows
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getWorkflow($params)
    {
        $dbh = $this->ant->dbh;
        
        $otypes = $params['otypes'];
        $cond = "";
        $types = explode(":", $otypes);
        foreach ($types as $type)
        {
            if(!empty($type))
                $cond .= ($cond) ? "or object_type='$type' " : "object_type='$type' ";
        }
            
        $wflist = new WorkFlow_List($dbh, $cond);
        
        $ret = array();
        for ($w = 0; $w < $wflist->getNumWorkFlows(); $w++)
        {
            $wf = $wflist->getWorkFlow($w);
            
            $id = $wf->id;
            $ret[] = array("id" => $id, "name" => $wf->name, "object_type" => $wf->object_type,"f_active" => (($wf->fActive)?"t":"f"));
        }
        
		return $this->sendOutputJson($ret);
    }
    
    /**
    * Delete the workflow  
    *   
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteWorkflow($params)
    {
        $dbh = $this->ant->dbh;
        $wid = $params['wid'];

        if($wid > 0)
        {
            $wf = new WorkFlow($dbh, $wid);
            $wf->remove();
        }
        
        $ret = 1;
		return $this->sendOutputJson($ret);
    }
    
    /**
    * Saves the workflow
    *   
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveWorkflow($params)
    {
        $dbh = $this->ant->dbh;
        
        $wid = ($params['wid']) ? $params['wid'] : null;

        $wf = new WorkFlow($dbh, $wid);
        $wf->name = $params['name'];
        $wf->notes = $params['notes'];
        $wf->object_type = $params['object_type'];
        $wf->fActive = ($params['f_active'] == 't') ? true : false;
        $wf->fOnCreate = ($params['f_on_create'] == 't') ? true : false;
        $wf->fOnUpdate = ($params['f_on_update'] == 't') ? true : false;
        $wf->fOnDelete = ($params['f_on_delete'] == 't') ? true : false;
        $wf->fOnDaily = ($params['f_on_daily'] == 't') ? true : false;
        $wf->fSingleton = ($params['f_singleton'] == 't') ? true : false;
        $wf->fAllowManual = ($params['f_allow_manual'] == 't') ? true : false;
        $wf->fConditionUnmet = ($params['f_condition_unmet'] == 't') ? true : false;
        
        $wf->removeConditions();
        if ($params['conditions'] && is_array($params['conditions']))
        {
            foreach ($params['conditions'] as $cond)
            {                
                $wfCond = $wf->addCondition();

                $wfCond->id = $cond;
                $wfCond->blogic = $params['condition_blogic_'.$cond];
                $wfCond->fieldName = $params['condition_fieldname_'.$cond];
                $wfCond->operator = $params['condition_operator_'.$cond];
                $wfCond->condValue = $params['condition_condvalue_'.$cond];
            }
        }
        
        $ret = $wf->save();
		return $this->sendOutputJson($ret);
    }
    
    /**
    * Gets the workflow
    *   
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getWorkflowDetails($params)
    {
        $dbh = $this->ant->dbh;
        $wfid = $params['wfid'];
        
        $wf = new WorkFlow($dbh, $wfid);

        $ret['wfInfo'] = array("id" => $wf->id, "name" => $wf->name, "notes" => $wf->notes, "object_type" => $wf->object_type,
                            "f_active" => $wf->fActive, "f_on_create" => $wf->fOnCreate,
                            "f_on_update" => $wf->fOnUpdate, "f_on_delete" => $wf->fOnDelete,
                            "f_on_daily" => $wf->fOnDaily, "f_singleton" => $wf->fSingleton,
                            "f_allow_manual" => $wf->fAllowManual, "f_condition_unmet" => $wf->fConditionUnmet);
                            
        for ($i = 0; $i < $wf->getNumConditions(); $i++)
        {
            $cond = $wf->getCondition($i);

            $ret['wfCondition'][] = array("id" => $cond->id, "blogic" => $cond->blogic, "field_name" => $cond->fieldName,
                                        "operator" => $cond->operator, "cond_value" => $cond->condValue);
        }
        
		return $this->sendOutputJson($ret);
    }
}
