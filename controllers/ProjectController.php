<?php
/**
 * Project application actions
 */
require_once(dirname(__FILE__).'/../userfiles/file_functions.awp');
require_once(dirname(__FILE__).'/../lib/AntFs.php');
require_once(dirname(__FILE__).'/../project/project_functions.awp');

/**
 * Class for controlling customer functions
 */
class ProjectController extends Controller
{
    /**
     * Get the customer lead name
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function deleteAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $aid = $params['aid'];
        
        // Get the owner
        if (is_numeric($aid))
        {
            $dbh->Query("delete from project_files where id='$aid'");
            $ret = $aid;
        }
        else
            $ret = array("error"=>"aid is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Get the customer lead name
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getTemplates($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $result = $dbh->Query("select id, name from project_templates order by name");
        $num = $dbh->GetNumberRows($result);        
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);
            $ret[] = array("id" => $row['id'], "name" => $row['name']);            
        }
        
		return $this->sendOutputJson($ret);
    }
    
    /**
     * Delete TEmplate
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function deleteTemplate($params)
    {
        $dbh = $this->ant->dbh;
        $tid = $params['template_id'];
        
        // Get the owner
        if (is_numeric($tid))
        {
            $result = $dbh->Query("delete from project_templates where id='$tid'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"template_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Get Template Notes
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getTemplateNotes($params)
    {
        $dbh = $this->ant->dbh;
        $tid = $params['tid'];
        
        // Get the owner
        if (is_numeric($tid))
        {
            $result = $dbh->Query("select notes from project_templates where id='$tid'");
            if ($dbh->GetNumberRows($result))
            {
                $ret = $dbh->GetValue($result, 0, "notes");
                if (!$ret)
                    $ret = -1;
            }
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"template_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Case Save Attachments
	 * @depricated This has been replacted with the global attachments plugin and the AntFsController
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
    public function caseSaveAttachments($params)
    {
        $dbh = $this->ant->dbh;
        $CASEID = $params['case_id'];
        
        // Get the owner
        if (is_numeric($CASEID) && is_array($params['uploaded_file']))
        {
            $antfs = new AntFs($dbh, $this->user);
            if ($params['project_id'])
                $path = "/System/Project Files/".$params['project_id'];
            else
                $path = "/System/Project Files/Tickets/$CASEID";
            $proj_folder = $antfs->openFolder($path, true);

            foreach ($params['uploaded_file'] as $fid)
            {
                $antfs->moveTmpFile($fid, $proj_folder);
                $dbh->Query("insert into project_files(file_id, project_id, bug_id) 
                             values('$fid', ".db_CheckNumber($params['project_id']).", '$CASEID');");
            }

            $ret = 1;
        }
        else
            $ret = array("error"=>"case_id is a required param");

		return $this->sendOutputJson($ret);
    }
     */
    
    /**
     * Case Remove Attachments
	 * @depricated This has been replacted with the global attachments plugin and the AntFsController
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
    public function caseRemoveAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $CASEID = $params['case_id'];
        $FID = $params['fid'];
        
        // Get the owner
        if (is_numeric($CASEID) && is_numeric($FID))
        {
            $antfs = new CAntFs($dbh, $this->user);

            $dbh->Query("delete from project_files where file_id='$FID' and bug_id='$CASEID'");

            $ret = $antfs->removeFileById($FID);
            if ($ret)
                $ret = 1;
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"case_id is a required param");

		return $this->sendOutputJson($ret);
    }
     */
    
    /**
     * Case Get Attachments
	 * @depricated This has been replacted with the global attachments plugin and the AntFsController
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
    public function caseGetAttachments($params)
    {
        $dbh = $this->ant->dbh;
                
        $CASEID = $params['case_id'];        
        
        // Get the owner
        if (is_numeric($CASEID))
        {
            $ret = array();
            
            //$antfs = new CAntFs($dbh, $this->user->id);
            $query = "select file_title, file_size, user_files.id, project_files.id as aid from project_files, user_files
                                where project_files.file_id=user_files.id and project_files.bug_id='$CASEID'";
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i=0; $i<$num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $name = $row['file_title'];
                
                $ret[] = array("fid" => rawurlencode($row['id']), "name" => rawurlencode($row['file_title']));                
            }
            $dbh->FreeResults($result);            
        }
        else
            $ret = array("error"=>"case_id is a required param");

		return $this->sendOutputJson($ret);
    }
     */
    
    /**
     * Group Set Color
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupSetColor($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $color = $params['color'];

        if ($gid && $color)
        {
            $dbh->Query("update project_groups set color='$color' where id='$gid'");
            $ret = $color;
        }
        else
            $ret = array("error"=>"gid and color are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Group Rename
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupRename($params)
    {
        $dbh = $this->ant->dbh;
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);

        if ($gid && $name)
        {
            $dbh->Query("update project_groups set name='".$dbh->Escape($name)."' where id='$gid'");
            $ret = $name;
        }
        else
            $ret = array("error"=>"gid and name are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Group Delete
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh;
        $gid = $params['gid'];

        if ($gid)
        {
            $dbh->Query("delete from project_groups where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Group Add
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh;
        
        $pgid = ($params['pgid'] && $params['pgid'] != "null") ? "'".$params['pgid']."'" : "NULL";
        $name = rawurldecode($params['name']);
        $color = rawurldecode($params['color']);

        if ($name && $color)
        {
            $query = "insert into project_groups(parent_id, name, color) 
                      values($pgid, '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
                      select currval('project_groups_id_seq') as id;";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $ret = $row['id'];
            }
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"color and name are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Group Add
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getCodes($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {
            $ret = array();
            $result = $dbh->Query("select * from ".$params['tbl']." order by sort_order");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i, PGSQL_ASSOC);
                $retArray = array();
                foreach ($row as $name=>$val)                    
                    $retArray[] = array($name => $val);
                    
                $ret[] = $retArray;
                
            }
            $dbh->FreeResults($result);
            
        }
        else
            $ret = array("error"=>"tbl is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Save Code
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function saveCode($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {
            // TODO: veryify user has access to modify customer settings - typically only admins

            // Sort order
            if ($params['id'] && $params['sorder'])
            {
                $result = $dbh->Query("select sort_order from ".$params['tbl']." where id='".$params['id']."'");
                if ($dbh->GetNumberRows($result))
                    $cur_order = $dbh->GetValue($result, 0, "sort_order");

                if ($cur_order && $cur_order!=$params['sorder'])
                {
                    // Moving up or down
                    if ($cur_order < $params['sorder'])
                        $direc = "down";
                    else
                        $direc = "up";

                    $result = $dbh->Query("select id  from ".$params['tbl']." where id!='".$params['id']."'
                                            and sort_order".(($direc=="up")?">='".$params['sorder']."'":"<='".$params['sorder']."'")." order by sort_order");
                    $num = $dbh->GetNumberRows($result);
                    for ($i = 0; $i < $num; $i++)
                    {
                        $id = $dbh->GetValue($result, $i, "id");
                        $newval = ("up" == $direc) ? $params['sorder']+1+$i : $i+1;
                        $dbh->Query("update ".$params['tbl']." set sort_order='$newval' where id='".$id."'");
                    }
                    $dbh->Query("update ".$params['tbl']." set sort_order='".$params['sorder']."' where id='".$params['id']."'");
                }
            }

            // Color
            if ($params['id'] && $params['color'])
            {
                $dbh->Query("update ".$params['tbl']." set color='".$params['color']."' where id='".$params['id']."'");
            }

            // Name and enter new
            if ($params['name'])
            {
                if ($params['id'])
                {
                    $dbh->Query("update ".$params['tbl']." set name='".$dbh->Escape($params['name'])."' where id='".$params['id']."'");
                }
                else 
                {
                    $result = $dbh->Query("select sort_order from ".$params['tbl']." order by sort_order DESC limit 1");
                    if ($dbh->GetNumberRows($result))
                        $sorder = $dbh->GetValue($result, 0, "sort_order");

                    $dbh->Query("insert into ".$params['tbl']."(name, sort_order) 
                                values('".$dbh->Escape($params['name'])."', '".($sorder+1)."');");
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"tbl is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Delete Code
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function deleteCode($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['tbl'])        // Update specific event
        {
            // TODO: veryify user has access to modify customer settings - typically only admins

            // Sort order
            if ($params['id'])
            {
                $dbh->Query("delete from ".$params['tbl']." where id='".$params['id']."'");
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"tbl is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Set Case Stat Closed
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function setCaseStatClosed($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['id'] && $params['f_closed'])
        {
            // TODO: veryify user has access to modify customer settings - typically only admins
            $dbh->Query("update project_bug_status set f_closed='".$dbh->Escape($params['f_closed'])."' where id='".$params['id']."'");

            $ret = 1;
        }
        else
            $ret = array("error"=>"id and f_closed are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Save Members
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function saveMembers($params)
    {
        $dbh = $this->ant->dbh;
        
        $PID = $params['project_id'];
        if ($PID)
        {
            $obj = new CAntObject($dbh, "project", $PID, $this->user);
            if (is_array($params['delete']) && count($params['delete']))
            {
                for ($i = 0; $i < count($params['delete']); $i++)
                    $obj->removeMValue("members", $params['delete'][$i]);
            }

            if (is_array($params['members']) && count($params['members']))
            {
                for ($i = 0; $i < count($params['members']); $i++)
                {
                    $obj->setMValue("members", $params['members'][$i]);
                }

                $obj->save(false);

                // Save positions
                for ($i = 0; $i < count($params['members']); $i++)
                {
                    $dbh->Query("update project_membership set position_id=".$dbh->EscapeNumber($params['m_position_id_'.$params['members'][$i]])."
                                        where project_id='".$PID."' and user_id='".$params['members'][$i]."'");                    
                }
            }

            $ret = 1;
        }
        else
            $ret = array("error"=>"lead_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Get Members
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getMembers($params)
    {
        $dbh = $this->ant->dbh;
        $PID = $params['project_id'];
        
        if ($PID)
        {            
            $query = "select project_membership.id, project_membership.user_id, users.name as username, project_positions.name as position_name,
                            project_membership.accepted, project_membership.position_id from users, project_membership 
                            left outer join project_positions on (project_membership.position_id = project_positions.id)
                            where project_membership.project_id='$PID' and project_membership.user_id=users.id
                            
                            order by username";
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i=0; $i<$num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $name = CustGetName($dbh, $row['cid']);
                $email = CustGetEmail($dbh, $row['cid']);
                $phone = CustGetPhone($dbh, $row['cid']);
                $title = CustGetColVal($dbh, $row['cid'], "job_title");
                $rname = $row['relationship_name'];
                $relationship = ($row['type_name']) ? $row['type_name'] : $row['relationship_name'];
                
                $ret[] = array("id" => rawurlencode($row['id']), "user_id" => rawurlencode($row['user_id']), "username" => rawurlencode($row['username']),
                                "position_name" => rawurlencode($row['position_name']), "position_id" => rawurlencode($row['position_id']));
            }
            $dbh->FreeResults($result);            
        }
        else
            $ret = array("error"=>"project_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Get Positions
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getPositions($params)
    {
        $dbh = $this->ant->dbh;
        
        $PID = $params['project_id'];
        if ($PID)
        {
            $ret = array();
            $result = $dbh->Query("select id, name from project_positions where project_id='$PID' order by name");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                $ret[] = array("id" => $row['id'], "name" => $row['name']);                
            }            
        }
        else
            $ret = array("error"=>"project_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Position Add
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function positionAdd($params)
    {
        $dbh = $this->ant->dbh;
        $PID = $params['project_id'];
        
        if ($PID)
        {
            $result = $dbh->Query("insert into project_positions(name, project_id) values('".$dbh->Escape($params['name'])."', '$PID'); 
                                    select currval('project_positions_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
        }
        else
            $ret = array("error"=>"project_id is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Position Delete
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function positionDelete($params)
    {
        $dbh = $this->ant->dbh;
        $pid = $params['pid'];
        
        if ($pid)
        {
            $dbh->Query("delete from project_positions where id='$pid'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"pid is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Update Project Hooks
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function updateProjectHooks($params)
    {                       
        $dbh = $this->ant->dbh;
        $pid = $params['project_id'];
        $date_completed = rawurldecode($params['date_completed']);
        
        if ($pid && $date_completed && $date_completed!="null")
        {
            // Update tasks
            $olist = new CAntObjectList($dbh, "task", $this->user);
            $olist->addCondition("and", "done", "is_not_equal", 't');
            $olist->addCondition("and", "date_completed", "is_equal", '');
            $olist->addCondition("and", "project", "is_equal", $pid);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                $obj->setValue("done", 't');
                $obj->setValue("date_completed", $date_completed);
                $obj->save();
            }

            // Update milestones
            $olist = new CAntObjectList($dbh, "project_milestone", $this->user);
            $olist->addCondition("and", "f_completed", "is_not_equal", 't');
            $olist->addCondition("and", "project_id", "is_equal", $pid);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                $obj->setValue("f_completed", 't');
                $obj->save();
            }
            
            $ret = 1;
        }
        else
            $ret = array("error"=>"project_id and date_completed are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Update Template Project
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function updateTemplatedProject($params)
    {
        $dbh = $this->ant->dbh;
        $pid = $params['project_id'];
        $tid = $params['template_id'];
        
        if ($pid && $tid)
        {
            $obj = new CAntObject($dbh, "project", $pid, $this->user);
            $date_started = $obj->getValue("date_started");
            $date_deadline = $obj->getValue("date_deadline");

            $query = "select id, name, start_interval, start_count, due_interval, due_count, 
                         timeline, type, file_id, user_id, timeline_date_begin, timeline_date_due from
                         project_template_tasks where template_id='".$tid."'";
            $task_result = $dbh->Query($query);
            $task_num = $dbh->GetNumberRows($task_result);
            for ($j = 0; $j < $task_num; $j++)
            {
                $task_row = $dbh->GetNextRow($task_result, $j);
                $tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
                $tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';
                
                if (($tl_date_begin == "date_deadline" || $tl_date_due == "date_deadline") && $obj->getValue("date_deadline")=="")
                    continue; // Skip over, deadline is not provided

                $query = "update project_tasks set ts_updated='now', revision=revision+1,
                            start_date='".ProjectGetExeTime($dbh, $obj->getValue($tl_date_begin), $task_row['start_count'], 
                            $task_row['start_interval'], $task_row['timeline'])."', 
                            deadline='".ProjectGetExeTime($dbh, $obj->getValue($tl_date_begin), $task_row['due_count'], 
                            $task_row['due_interval'], $task_row['timeline'])."'
                            where template_task_id='".$task_row['id']."' and project='".$pid."'";
                $dbh->Query($query);
            }
            $dbh->FreeResults($task_result);
        }
        else
            $ret = array("error"=>"project_id and template_id are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Update Template Project
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function createProject($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['name'])
        {
            $obj = new CAntObject($dbh, "project", null, $this->user);
            $obj->setValue("user_id", $this->user->id);
            $obj->setValue("name", $params['name']);
            $obj->setValue("date_started", $params['date_started']);
            $obj->setValue("date_deadline", $params['date_deadline']);
            $obj->setValue("template_id", $params['template_id']);
            $obj->setValue("priority", 1);
            $obj->setValue("notes", $params['notes']);
			$obj->setMValue("members", $this->user->id);
            $id = $obj->save();
            
            if ($id)
            {
                $ret = $id;

                // Add user as the first member
                $dbh->Query("update project_membership set title='Project Creator' where user_id='" . $this->user->id . "' and project_id='$id'");
                
                if (is_numeric($params['template_id']))
                {
                    $query = "select id, name, start_interval, start_count, due_interval, due_count, notes, cost_estimated,
                              timeline, type, file_id, user_id, position_id, timeline_date_begin, timeline_date_due 
                              from project_template_tasks where template_id='".$params['template_id']."'";
                                 
                    $task_result = $dbh->Query($query);
                    $task_num = $dbh->GetNumberRows($task_result);
                    for ($j = 0; $j < $task_num; $j++)
                    {
                        $task_row = $dbh->GetNextRow($task_result, $j);
                        $tl_date_begin = ($task_row['timeline_date_begin']) ? $task_row['timeline_date_begin'] : 'date_deadline';
                        $tl_date_due = ($task_row['timeline_date_due']) ? $task_row['timeline_date_due'] : 'date_deadline';
                        
                        $query = "insert into project_tasks (name, user_id, position_id, done, date_entered, start_date,
                                    entered_by, project, priority, deadline, type, notes, file_id, template_task_id, cost_estimated)
                                    values
                                    ('".$dbh->Escape($task_row['name'])."',  
                                    ".db_CheckNumber($task_row['user_id']).", 
                                    ".db_CheckNumber($task_row['position_id']).",
                                    'f', '".date("m/d/Y")."', 
                                    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $params[$tl_date_begin], $task_row['start_count'], 
                                                         $task_row['start_interval'], $task_row['timeline'])).", 
                                    '" . $this->user->name . "', '$id', '1',
                                    ".$dbh->EscapeDate(ProjectGetExeTime($dbh, $params[$tl_date_due], $task_row['due_count'], 
                                                         $task_row['due_interval'], $task_row['timeline'])).",
                                    ".db_CheckNumber($task_row['type']).", 
                                    '".$dbh->Escape(stripslashes($task_row['notes']))."',  
                                    ".db_CheckNumber($task_row['file_id']).", 
                                    ".db_CheckNumber($task_row['id']).",
                                    ".db_CheckNumber($task_row['cost_estimated']).")";
                        $dbh->Query($query);
                    }
                    $dbh->FreeResults($task_result);
                    
                    $query = "select user_id from project_template_members where template_id='".$params['template_id']."'";
                    $task_result = $dbh->Query($query);
                    $task_num = $dbh->GetNumberRows($task_result);
                    for ($j = 0; $j < $task_num; $j++)
                    {
                        $task_row = $dbh->GetNextRow($task_result, $j);
                        
                        if ($task_row['user_id'] != $this->user->id)
                        {
                            $query = "insert into project_membership (user_id, project_id, title,
                                        invite_by, accepted)
                                        values
                                        ('".$task_row['user_id']."', '$id', 
                                        'Invited Member', 
                                        '" . $this->user->name . "', 'f')";
                            $dbh->Query($query);
                        }
                    }
                    $dbh->FreeResults($task_result);

                    $query = "select id, name from project_positions where template_id='".$params['template_id']."'";
                    $task_result = $dbh->Query($query);
                    $task_num = $dbh->GetNumberRows($task_result);
                    for ($j = 0; $j < $task_num; $j++)
                    {
                        $task_row = $dbh->GetNextRow($task_result, $j);
                        
                        if ($task_row['id'])
                        {
                            $query = "insert into project_positions (name, project_id)
                                        values
                                        ('".$dbh->Escape($task_row['name'])."', '$id');
                                      select currval('project_positions_id_seq') as posid;";
                            $pos_res = $dbh->Query($query);
                            if ($dbh->GetNumberRows($pos_res))
                            {
                                $pos_row = $dbh->GetNextRow($pos_res, 0);
                                $dbh->Query("update project_tasks set position_id='".$pos_row['posid']."' where 
                                             project='$id' and position_id='".$task_row['id']."'");
                            }
                        }
                    }                    
                    $dbh->FreeResults($task_result);
                }
            }
        }
        else
            $ret = array("error"=>"name is a required param");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Task Log Time
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function taskLogTime($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['task_id'] && $params['hours'] && $params['date_applied'] && $params['name'])
        {
            $objTime = new CAntObject($dbh, "time", null, $this->user);
            $objTime->setValue("name", $params['name']);
            $objTime->setValue("date_applied", $params['date_applied']);
            $objTime->setValue("hours", $params['hours']);
            $objTime->setValue("task_id", $params['task_id']);
            $timeid = $objTime->save();

            // Get aggregated value
            $obj = new CAntObject($dbh, "task", $params['task_id'], $this->user);
            $ret = $obj->getValue("cost_actual");
        }
        else
            $ret = array("error"=>"task_id, date_applied, name and hours are required params");

		return $this->sendOutputJson($ret);
    }
    
    /**
     * Case Task Owner
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function caseTaskowner($params)
    {
        $dbh = $this->ant->dbh;
        
        $case_id = $params['cid'];
        $owner_id = $params['owner_id'];
        $case_name = $params['case_name'];
        // Get the owner
        if (is_numeric($case_id) && is_numeric($owner_id))
        {
            $task = new CAntObject($dbh, "task", null, $this->user);
            $task->setValue("name", "Address Case: $case_name");
            $task->setValue("notes", "NOTE: Be sure and close the case when you complete this task");
            $task->setValue("user_id", $owner_id);
            $task->setValue("case_id", $case_id);
            $ret = $task->save();
        }
        else
            $ret = array("error"=>"cid, owner_id, and case_name are required params");

		return $this->sendOutputJson($ret);
    }
    
    public function checkTask($params)
    {
        $dbh = $this->ant->dbh;
        $userId = $this->user->id;
        
        $name = base64_decode($params['data']);
        
        $taskId = $params['task_id'];
        $taskObj = CAntObject::factory($this->ant->dbh, "task", $taskId, $this->user);
        
        switch($params['type'])
        {
            case "delete":
                $taskObj->removeHard();
                $ret = 1;
                break;
            default:
                $updRes = $params['task_res'];
				$taskObj->setValue("done", $params['task_res']);
				$taskObj->save();
                $ret = 1;
                break;
        }
        
		return $this->sendOutputJson($ret);
    }
}
?>
