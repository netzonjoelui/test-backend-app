<?php
/**
 * Service to process delayed or intervals workflow actions
 *
 * Most workflow actions are fired immediately when their originating event is triggered.
 * However, workflows can be delayed/scheduled for a later time or run at a specific interval
 * such as daily. This service is responsible for processing these actions.
 *
 * @category	Ant
 * @package		WorkFlow
 * @subpackage	ActionProcessor
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/CAntObject.php");
require_once("lib/WorkFlow.php");

/**
 * Service implementation class
 */
class WorkFlowActionProcessor extends AntService
{
	/**
	 * Keep track of the number of actions processed this run
	 *
	 * @var int
	 */
	public $numProcessed = 0;

	/**
	 * Keep track of the actions processed by id
	 *
	 * @var array(int)
	 */
	public $actionsProcessed = array();

	/**
	 * If in test mode do a better job of acounting for actions that have been processed
	 *
	 * @var bool
	 */
	public $testMode = false;

	/**
	 * main function that will be called continually by the service manager
	 *
	 * @param CDatabase $dbh handle to account database passed from AntService::run
	 * @return bool true on success, false of failure. This is important, if false or null is returned the service will stop
	 */
	public function main(&$dbh)
	{
		$this->numProcessed = 0; // reset counter

		// Do not close database handle if we are functioning in test mode
		if ($this->testMode)
			$this->closeDbh = false;

		$result = $dbh->Query("select id, action_id, instance_id from workflow_action_schedule where ts_execute <= now() and inprogress='0'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			if (!WorkFlow::instanceActInProgress($dbh, $row['id']))
			{
				$obj = WorkFlow::getInstanceObj($dbh, $row['instance_id']);
				if ($obj->getValue("f_deleted") != 't')
				{
					$act = new WorkFlow_Action($dbh, $row['action_id']);
					$wf = new WorkFlow($dbh, $act->workflow_id);
					$act->execute($obj);
					$this->numProcessed++;
					if ($this->testMode)
						$this->actionsProcessed[] = $row['action_id'];
				}

				// Clear scheduled action
				$dbh->Query("delete from workflow_action_schedule where id='".$row['id']."'");

				// Set the status of this instance to finished if all actions are done
				$wf->updateStatus($row['instance_id']);
			}
		}
		$dbh->FreeResults($result);

		$this->runDailyWorkflows($dbh);

		return true;
	}

	/**
	 * Check for actions that are set to run every day
	 *
	 * @param CDatabase $dbh handle to account database passed from AntService::run
	 */
	public function runDailyWorkflows($dbh)
	{
		$cond = "f_active='t' and f_on_daily='t' and (ts_on_daily_lastrun is null or ts_on_daily_lastrun<ts_on_daily_lastrun - INTERVAL '1 day')";
		$wflist = new WorkFlow_List($dbh, $cond);
		for ($w = 0; $w < $wflist->getNumWorkFlows(); $w++)
		{
			$wf = $wflist->getWorkFlow($w);

			// Look for/sweep for matching objects
			$ol = new CAntObjectList($dbh, $wf->object_type);
			// Build condition
			for ($j = 0; $j < $wf->getNumConditions(); $j++)
			{
				$cond = $wf->getCondition($j);
				$ol->addCondition($cond->blogic, $cond->fieldName, $cond->operator, $cond->value);
			}
			// Now get objects
			$ol->getObjects();
			for ($j = 0; $j < $ol->getNumObjects(); $j++)
			{
				$obj = $ol->getObject($j);
				if ($obj->getValue("f_deleted") != 't')
					$wf->execute($obj);
			}

			// Update last run
			$dbh->Query("UPDATE workflows SET ts_on_daily_lastrun='now' WHERE id='".$wf->id."'");
		}
	}
}
