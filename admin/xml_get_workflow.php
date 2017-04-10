<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("lib/WorkFlow.php");
	require_once("email/email_functions.awp");
	require_once("lib/WorkFlow.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$WFID = $_GET['wfid'];

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	
	// Check to see if we are pulling a specific event
	if ($WFID)
	{
		$wf = new WorkFlow($dbh, $WFID);

		echo "<workflow>";
		echo "<id>".$wf->id."</id>";
		echo "<name>".$wf->name."</name>";
		echo "<notes>".$wf->notes."</notes>";
		echo "<object_type>".$wf->object_type."</object_type>";
		echo "<f_active>".(($wf->fActive)?'t':'f')."</f_active>";
		echo "<f_on_create>".(($wf->fOnCreate)?'t':'f')."</f_on_create>";
		echo "<f_on_update>".(($wf->fOnUpdate)?'t':'f')."</f_on_update>";
		echo "<f_on_delete>".(($wf->fOnDelete)?'t':'f')."</f_on_delete>";
		echo "<f_on_daily>".(($wf->fOnDaily)?'t':'f')."</f_on_daily>";
		echo "<f_singleton>".(($wf->fSingleton)?'t':'f')."</f_singleton>";
		echo "<f_allow_manual>".(($wf->fAllowManual)?'t':'f')."</f_allow_manual>";

		// Load conditions
		/*echo "<conditions>";
		for ($i = 0; $i < $wf->getNumConditions(); $i++)
		{
			$cond = $wf->getCondition($i);

			echo "<condition>";
            echo "<id>".$cond->id."</id>";
			echo "<blogic>".$cond->blogic."</blogic>";
			echo "<field_name>".$cond->fieldName."</field_name>";
			echo "<operator>".$cond->operator."</operator>";
			echo "<cond_value>".rawurlencode($cond->condValue)."</cond_value>";
			echo "</condition>";
		}
		echo "</conditions>";*/

		echo "<actions>";
		for ($i = 0; $i < $wf->getNumActions(); $i++)
		{
			$act = $wf->getAction($i);
			echo "<action>";
			echo "<id>".$act->id."</id>";
			echo "<type>".$act->type."</type>";
			echo "<name>".rawurlencode($act->name)."</name>";
			echo "<when_interval>".$act->when_interval."</when_interval>";
			echo "<when_unit>".$act->when_unit."</when_unit>";
			echo "<send_email_fid>".$act->send_email_fid."</send_email_fid>";
			echo "<update_field>".$act->update_field."</update_field>";
			echo "<update_to>".rawurlencode($act->update_to)."</update_to>";
			echo "<create_obj>".$act->create_obj."</create_obj>";
			echo "<start_wfid>".$act->start_wfid."</start_wfid>";
			echo "<stop_wfid>".$act->stop_wfid."</stop_wfid>";

			echo "<conditions>";
			for ($j = 0; $j < $act->getNumConditions(); $j++)
			{
				$cond = $act->getCondition($j);

				echo "<condition>";
                echo "<id>".$cond->id."</id>";
				echo "<blogic>".$cond->blogic."</blogic>";
				echo "<field_name>".$cond->fieldName."</field_name>";
				echo "<operator>".$cond->operator."</operator>";
				echo "<cond_value>".$cond->condValue."</cond_value>";
				echo "</condition>";
			}
			echo "</conditions>";

			echo "<object_values>";
			$ovals = $act->getObjectValues();
			if (is_array($ovals))
			{
				foreach ($ovals as $name=>$val)
				{
					echo "<object_value>";
					echo "<name>".rawurlencode($name)."</name>";
					if (is_array($val))
					{
						echo "<f_array>t</f_array>";
						echo "<values>";
						foreach ($val as $mval)
							echo "<value>".rawurlencode($mval)."</value>";
						echo "</values>";
					}
					else
					{
						echo "<f_array>f</f_array>";
						echo "<value>".rawurlencode($val)."</value>";
					}
					echo "</object_value>";
				}
			}
			echo "</object_values>";

			echo "</action>";
		}
		echo "</actions>";

		echo "</workflow>";
	}
	else // pull all workflows
	{
	}
?>
