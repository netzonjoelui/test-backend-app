<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$FUNCTION = $_GET['function'];

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	// ---------------------------------------------------------
	// Save Workflow
	// ---------------------------------------------------------
	case "save_workflow":
		$wid = ($_POST['wid']) ? $_POST['wid'] : null;

		$wf = new WorkFlow($dbh, $wid);
		$wf->name = $_POST['name'];
		$wf->notes = $_POST['notes'];
		$wf->object_type = $_POST['object_type'];
		$wf->fActive = ($_POST['f_active'] == 't') ? true : false;
		$wf->fOnCreate = ($_POST['f_on_create'] == 't') ? true : false;
		$wf->fOnUpdate = ($_POST['f_on_update'] == 't') ? true : false;
		$wf->fOnDelete = ($_POST['f_on_delete'] == 't') ? true : false;
		$wf->fOnDaily = ($_POST['f_on_daily'] == 't') ? true : false;
		$wf->fSingleton = ($_POST['f_singleton'] == 't') ? true : false;
		$wf->fAllowManual = ($_POST['f_allow_manual'] == 't') ? true : false;

		//$wf->removeConditions();

		if ($_POST['conditions'] && is_array($_POST['conditions']))
		{
			foreach ($_POST['conditions'] as $condid)
			{
				if (is_numeric($condid)) // Existing condition
				{
					$cond = $wf->getConditionById($condid);
				}
				else
				{
					$cond = $wf->addCondition();
				}

				$cond->blogic = $_POST['condition_blogic_'.$condid];
				$cond->fieldName = $_POST['condition_fieldname_'.$condid];
				$cond->operator = $_POST['condition_operator_'.$condid];
				$cond->condValue = $_POST['condition_condvalue_'.$condid];
			}
		}

		$wid = $wf->save();

		$retval = $wid;
		break;
	// ---------------------------------------------------------
	// Save Workflow Action
	// ---------------------------------------------------------
	case "save_workflow_action":
		$wid = ($_POST['wid']) ? $_POST['wid'] : null;
		$aid = ($_POST['aid']) ? $_POST['aid'] : null;
		$vals = "";

		if ($wid && is_array($_POST['actions']))
		{
			$wf = new WorkFlow($dbh, $wid);

			foreach ($_POST['actions'] as $actid)
			{
				if (is_numeric($actid)) // Existing condition
				{
					$act = $wf->getActionById($actid);
					if ($_POST['action_edit_status_'.$actid] == "delete")
					{
						$act->remove();
						continue; // Skip to next item
					}
				}
				else
				{
					$act = $wf->addAction();
				}

				$act->type = $_POST['action_type_'.$actid];
				$act->name = $_POST['action_name_'.$actid];
				$act->when_interval = $_POST['action_when_interval_'.$actid];
				$act->when_unit = $_POST['action_when_unit_'.$actid];
				$act->send_email_fid = $_POST['action_send_email_fid_'.$actid];
				$act->update_field = $_POST['action_update_field_'.$actid];
				$act->update_to = $_POST['action_update_to_'.$actid];
				$act->create_object = $_POST['action_create_object_'.$actid];
				$act->start_wfid = $_POST['action_start_wfid_'.$actid];
				$act->stop_wfid = $_POST['action_stop_wfid_'.$actid];
				$act->stop_wfid = $_POST['action_stop_wfid_'.$actid];

				$act->removeConditions();

				if (is_array($_POST['action_'.$actid.'_conditions']))
				{
					foreach ($_POST['action_'.$actid.'_conditions'] as $acid)
					{
						if (is_numeric($acid)) // Existing condition
						{
							$cond = $act->getConditionById($acid);
						}
						else
						{
							$cond = $act->addCondition();
						}

						$cond->blogic = $_POST['action_'.$actid.'_condition_blogic_'.$acid];
						$cond->fieldName = $_POST['action_'.$actid.'_condition_fieldname_'.$acid];
						$cond->operator = $_POST['action_'.$actid.'_condition_operator_'.$acid];
						$cond->condValue = $_POST['action_'.$actid.'_condition_condvalue_'.$acid];
					}
				}

				// Set new object values
				if (is_array($_POST['action_'.$actid.'_ovals']))
				{
					foreach ($_POST['action_'.$actid.'_ovals'] as $varname)
					{
						if (is_array($_POST['action_'.$actid.'_oval_'.$varname]))
						{
							$act->removetObjectMultiValues($varname);
							foreach ($_POST['action_'.$actid.'_oval_'.$varname] as $mval)
								$act->setObjectMultiValue($varname, $mval);
						}
						else
						{
							$act->setObjectValue($varname, $_POST['action_'.$actid.'_oval_'.$varname]);
						}
					}
				}

				$id = $act->save();

				if ($id)
				{
					if ($vals) $vals .= ", ";
					$vals .= "['$actid', $id]";
				}
			}
		}

		$retval = "[$vals]";
		break;
	// ---------------------------------------------------------
	// Save Workflow
	// ---------------------------------------------------------
	case "delete_workflow":
		$wid = ($_POST['wid']) ? $_POST['wid'] : null;

		$wf = new WorkFlow($dbh, $wid);
		$wf->remove();
		
		$retval = 1;
		break;
	}

	// Check for RPC
	if ($retval)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
		echo "</response>";
	}
?>
