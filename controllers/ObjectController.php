<?php
/**
* Object actions.
*/
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/AntFs.php');
require_once(dirname(__FILE__).'/../lib/WorkerMan.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../email/email_functions.awp');
require_once(dirname(__FILE__).'/../lib/Object/Approval.php');
require_once(dirname(__FILE__).'/../lib/ServiceLocatorLoader.php');

/**
* Actions for interacting with Ant Objects
*/
class ObjectController extends Controller
{
    var $cache;
    
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
        $this->cache = CCache::getInstance();
    }
    
    /**
     * Get a calendar by name
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getGroupings($params)
    {
        $dbh = $this->ant->dbh;
        
        if (!$params['obj_type'] || !$params['field'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = CAntObject::factory($dbh, $params['obj_type'], null, $this->user);
        
        // Setup filter
		$filters = array();
		foreach($params as $field=>$value)
		{
			switch($field)
			{
				case "apim":
				case "controller":
				case "auth":
				case "obj_type":
				case "field":
					break;
				default: // consider other param fields as filter
					$filters[$field] = $value;
					break;
			}
		}

		// Add conditions
		// joe: I'm not sure about this code or what is using it
        $conditions = array();
        if(isset($params['apim']) && isset($params['auth']))
        {
            foreach($params as $field=>$value)
            {
                switch($field)
                {
                    case "apim":
                    case "controller":
                    case "auth":
                    case "obj_type":
                    case "field":
                        break;
                    default: // consider other param fields as filter
                        $conditions[] = array("blogic" => "and", "field" => $field, "operator" => "=", "condValue" => $value);
                        break;
                }
            }
        }
        
        $data = $obj->getGroupingData($params['field'], $conditions, $filters);

        if(isset($params['noPrint']))
            return $data;
        else
            return $this->sendOutputJson($data);
    }

    /**
     * Get a grouping name/label by id
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getGroupingById($params)
    {
        $dbh = $this->ant->dbh;
        
        if (!$params['obj_type'] || !$params['field'] || !$params['gid'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
        
        $data = $obj->getGroupingById($params['field'], $params['gid']);

        return $this->sendOutputJson($data);
    }

    /**
     * Set the color of a group - fkey or fkey_multi
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function setGroupingColor($params)
    {
        $dbh = $this->ant->dbh;
        
        if (!$params['obj_type'] || !$params['field'] || !$params['gid'] || !$params['color'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
        $ret = $obj->updateGroupingEntry($params['field'], $params['gid'], null, $params['color']);

        return $this->sendOutputJson($ret);
    }

    /**
    * Rename a group - fkey or fkey_multi
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function renameGrouping($params)
    {
        $dbh = $this->ant->dbh;
        
        if (!$params['obj_type'] || !$params['field'] || !$params['gid'] || !$params['title'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
        $ret = $obj->updateGroupingEntry($params['field'], $params['gid'], $params['title']);

        $this->sendOutputJson($ret);
        return true;
    }

    /**
    * Create a new group entry - fkey or fkey_multi
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function createGrouping($params)
    {
        $dbh = $this->ant->dbh;
        $color = null;
        $sort = null;
        $parentId = null;
        
        if(isset($params['color']))
            $color = $params['color'];
            
        if(isset($params['sort_order']))
            $sort = $params['sort_order'];
            
        if(isset($params['parent_id']))
            $parentId = $params['parent_id'];
        
        if (!$params['obj_type'] || !$params['field'] || !$params['title'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
        $data = $obj->addGroupingEntry($params['field'], $params['title'], 
										$color, $sort, $parentId);

        $this->sendOutputJson($data);
        return $data;
    }

    /**
    * Delete group entry - fkey or fkey_multi
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteGrouping($params)
    {
        $dbh = $this->ant->dbh;
        
        if (!$params['obj_type'] || !$params['field'] || !$params['gid'])
			return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
        $ret = $obj->deleteGroupingEntry($params['field'], $params['gid']);

        $this->sendOutputJson($ret);
        return true;
    }

	/**
	 * Retrieve data for an object
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getObject($params)
	{
		$ret = array();
        
        if ($params['obj_type'] && $params['oid'])
        {
			$obj = CAntObject::factory($this->ant->dbh, $params['obj_type'], $params['oid'], $this->user);

			$ret = $obj->getDataArray();

			$ret['security'] = array();
			$ret['security']['view'] = $obj->dacl->checkAccess($this->user, "View", ($this->user->id==$obj->owner_id)?true:false);
			$ret['security']['edit'] = $obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->owner_id)?true:false);
			$ret['security']['delete'] = $obj->dacl->checkAccess($this->user, "Delete", ($this->user->id==$obj->owner_id)?true:false);
            
            // include icon name in object details
            $ret['iconName'] = $obj->getIconName();
		}

        return $this->sendOutputJson($ret);
	}

    /**
    * Save an object
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveObject($params)
    {        
        $dbh = $this->ant->dbh;
        
        if($params['obj_type'])
        {
            $oid = null;
            
            if(isset($params['oid']))
                $oid = $params['oid'];
                
            $obj = CAntObject::factory($dbh, $params['obj_type'], $oid, $this->user);
            if ($obj->getValue("f_readonly") != 't') // Do not write to read-only
            {            
                $ofields = $obj->def->getFields();
                foreach ($ofields as $fname=>$field)
                {
                    if($field->type=='fkey_multi' || $field->type=='object_multi')
                    {
                        // Purge
                        $obj->removeMValues($fname);

                        if(isset($params[$fname]) && is_array($params[$fname]) && count($params[$fname]))
                        {
                            // Add new
                            foreach ($params[$fname] as $val)
                                $obj->setMValue($fname, $val);
                        }
                    }
                    else
                    {
                        $value = null;
                        
                        if(isset($params[$fname]))
                            $value = $params[$fname];

                        // This will fix the issue if a reserved param variable has the same name as object fields                            
                        if(isset($params["field:$fname"]))
                            $value = $params["field:$fname"];
                        
                        // Empty the uname value if it is base64 encoded and object id is empty
                        // The uname value is passed from controller loader and contains the user's uname
                        if (base64_decode($value, true) && $fname == "uname" && empty($obj->id))
                            $value = "";
                        
                        $obj->setValue($fname, $value);
                    }
                }

                // Set recurrence if exists
                if(isset($params['save_recurrence_pattern']) && $params['save_recurrence_pattern']) 
                {

                    $rp_newvobj =  json_decode($params['objpt_json']);

                    if ($rp_newvobj->save_type == "exception")
                    {
                        $obj->recurrenceException = true;
                    }
                    else
                    {                    
                        $rp = $obj->getRecurrencePattern();

                        $rp->type = $rp_newvobj->type; 
                        $rp->interval = $rp_newvobj->interval;
                        $rp->dateStart = $rp_newvobj->dateStart;  
                        $rp->dateEnd = $rp_newvobj->dateEnd; 
                        $rp->fAllDay = $rp_newvobj->fAllDay; 
                        $rp->dayOfMonth = $rp_newvobj->dayOfMonth; 
                        $rp->monthOfYear = $rp_newvobj->monthOfYear;  
                        $rp->instance = $rp_newvobj->instance;

                        if ($rp_newvobj->day1 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
                        if ($rp_newvobj->day2 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
                        if ($rp_newvobj->day3 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_TUESDAY;
                        if ($rp_newvobj->day4 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
                        if ($rp_newvobj->day5 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_THURSDAY;
                        if ($rp_newvobj->day6 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_FRIDAY;
                        if ($rp_newvobj->day7 == 't')
                            $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SATURDAY;
                    }
                }

                if(isset($params['runindex']))
                    $obj->runIndexOnSave = true;
                
                $ret = $obj->save();
                if (!$ret) // Insufficient permissions?
                    $ret = -2;
            }
        }
        else
            $ret = array("error"=>"obj_type is a required param");
        
        return $this->sendOutputJson($ret);
    }

    /**
    * Delete an object
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteObject($params)
    {        
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && $params['oid'])        
        {
            $obj = CAntObject::factory($dbh, $params['obj_type'], $params['oid'], $this->user);
			if ($params['object_sync_collection'])
				$obj->skipObjectSyncStat = $params['object_sync_collection'];

			// If working with a series, check if we have an exception
            if (isset($params['recurrence_save_type']) && "exception" == $params['recurrence_save_type'])
            	$obj->recurrenceException = true;

            $obj->remove();
            $ret = true;
        }
        else
            $ret = array("error"=>"obj_type and object id are required params");

		if ($params['output'] == "xml")
        	return $this->sendOutputXml($ret);
		else
        	return $this->sendOutputJson($ret);
    }

    /**
    * Save a form
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveForm($params)
    {
        $dbh = $this->ant->dbh;
        
        $obj_type = $params['obj_type'];
        $otid = objGetAttribFromName($dbh, $obj_type, "id");
        if ($obj_type)
        {
            $team_id = null;
            $user_id = null;
            $form_layout_xml = null;
            $scope = "";
            $default = $params['default'];
            $mobile = $params['mobile'];
            
            if(isset($params['team_id']))
                $team_id = $params['team_id'];
                
            if(isset($params['user_id']))
                $user_id = $params['user_id'];
            
            if(isset($params['form_layout_xml']))
                $form_layout_xml = $params['form_layout_xml'];
            
            if($default != null)
            {
                $scope = "default";
                if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='default'")))
                {
                    $dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
                                    ('$otid', '$scope', '".$dbh->Escape($form_layout_xml)."');");    
                }
                else
                {
                    $dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($form_layout_xml)."' 
                                    where type_id='$otid' and scope='default'");
                }
            }
            if($mobile != null)
            {
                $scope = "mobile";
                if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'")))
                {
                    $dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, form_layout_xml) values
                                    ('$otid', '$scope', '".$dbh->Escape($form_layout_xml)."');");    
                }
                else
                {
                    $dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($form_layout_xml)."' 
                                    where type_id='$otid' and scope='mobile'");
                }
            }
            if($team_id != null)
            {
                $scope = "team";
                if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$team_id'")))
                {
                    $dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, team_id, form_layout_xml) values
                                    ('$otid', '$scope', '$team_id', '".$dbh->Escape($form_layout_xml)."');");    
                }
                else
                {
                    $dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($form_layout_xml)."' 
                                    where type_id='$otid' and scope='team' and team_id='$team_id'");
                }
            }
            if($user_id != null)
            {
                $scope = "user";
                if(!$dbh->GetNumberRows($dbh->Query("select id from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$user_id'")))
                {
                    $dbh->Query("insert into app_object_type_frm_layouts(type_id, scope, user_id, form_layout_xml) values
                                    ('$otid', '$scope', '$user_id', '".$dbh->Escape($form_layout_xml)."');");    
                }
                else
                {
                    $dbh->Query("update app_object_type_frm_layouts set form_layout_xml='".$dbh->Escape($form_layout_xml)."' 
                                    where type_id='$otid' and scope='user' and user_id='$user_id'");
                }
            }
            $ret = true;
        }
        else
            $ret = array("error"=>"obj_type is a required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Delete a form
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteForm($params)
    {
        $dbh = $this->ant->dbh;
        
        $obj_type = $params['obj_type'];
        $otid = objGetAttribFromName($dbh, $obj_type, "id");
        if ($obj_type)
        {
            $team_id = null;
            $user_id = null;
            $default = $params['default'];
            $mobile = $params['mobile'];
            
            if(isset($params['team_id']))
                $team_id = $params['team_id'];
                
            if(isset($params['user_id']))
                $user_id = $params['user_id'];
            
            if($default)
                $dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='default'");
            
            if($mobile)
                $dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='mobile'");
            
            if($team_id > 0)
                $dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='team' and team_id='$team_id'");
            
            if($user_id > 0)
                $dbh->Query("delete from app_object_type_frm_layouts where type_id='$otid' and scope='user' and user_id='$user_id'");
            
            $ret = 1;
        }        
        else
            $ret = array("error"=>"obj_type is a required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Loads a form
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function loadForm($params)
    {        
        $dbh = $this->ant->dbh;
                
        $obj_type = $params['obj_type'];
        $otid = objGetAttribFromName($dbh, $obj_type, "id");
        
        header("Content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
        echo "<response>";
        if ($obj_type)
        {
            $default = $params['default'];
            $mobile = $params['mobile'];
            $team_id = $params['team_id'] == 'null' ? null : $params['team_id'];
            $user_id = $params['user_id'] == 'null' ? null : $params['user_id'];
            $result = $dbh->Query("select type_id, scope, team_id, user_id, form_layout_xml from app_object_type_frm_layouts order by id");
            $num = $dbh->GetNumberRows($result);
        
            if($default == null && ($mobile == null or $mobile == 0) && $team_id == null && $user_id == null)
            {
                // default static form
                $obj = new CAntObjectFields($dbh, $obj_type);
                echo "<form>" . $obj->default_form_xml . "</form>";
                echo "<form_layout_text>" . rawurlencode($obj->default_form_xml) . "</form_layout_text>";
            }
        
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                if($default != null)
                {
                    if($otid == $row['type_id'] && "default" == $row['scope'])
                    {
                        echo "<form>" . $row['form_layout_xml'] . "</form>";
                        echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
                    }
                }
                if($mobile != null && $mobile != 0)
                {
                    if($otid == $row['type_id'] && "mobile" == $row['scope'])
                    {
                        echo "<form>" . $row['form_layout_xml'] . "</form>";
                        echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
                    }
                }
                if($team_id != null)
                {
                    if($otid == $row['type_id'] && "team" == $row['scope'] && $team_id == $row['team_id'])
                    {
                        echo "<form>" . $row['form_layout_xml'] . "</form>";
                        echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
                    }
                }
                if($user_id != null)
                {
                    if($otid == $row['type_id'] && "user" == $row['scope'] && $user_id == $row['user_id'])
                    {
                        echo "<form>" . $row['form_layout_xml'] . "</form>";
                        echo "<form_layout_text>" . rawurlencode($row['form_layout_xml']) . "</form_layout_text>";
                    }
                }
            }
        }
        echo "<message>" . rawurlencode($message) . "</message>";
        echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
        echo "</response>";
    }

    /**
     * Get the forms
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getForms($params)
    {
        $dbh = $this->ant->dbh;
        
        $obj_type = $params['obj_type'];
        $otid = objGetAttribFromName($dbh, $obj_type, "id");
        
        if($obj_type)
        {
            $ret = array();
            $result = $dbh->Query("select type_id, scope, user_id, team_id from app_object_type_frm_layouts order by id");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                    
                // only return forms with matching type_id
                if($otid == $row['type_id'])
                {   
                    $ret[] = array("type_id" => $row['type_id'], "scope" => $row['scope'], "team_id" => $row['team_id'], 
                                    "team_name" => UserGetTeamName($dbh, $row['team_id']), "user_id" => $row['user_id']);
                }
            }
            $dbh->FreeResults($result);            
        }
        else
            $ret = array("error"=>"obj_type is a required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Get the UIML for a form
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getFormUIML($params)
    {
        $dbh = $this->ant->dbh;
        
		$obj_type = $params['obj_type'];
		$obj = CAntObject::factory($dbh, $params['obj_type'], null, $this->user);

		$frmXml = $obj->getUIML($this->user, $params['scope']);

		$this->setContentType("xml");
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
		$xml .= "<form>" . $frmXml . "</form>";

		$this->sendOutputRaw($xml);
	}

    /**
     * undelete the object
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function undeleteObject($params)
    {
        $dbh = $this->ant->dbh;
        
        if(isset($params['obj_type']) && $params['obj_type'] && $params['oid'])
        {
            $obj = new CAntObject($dbh, $params['obj_type'], $params['oid'], $this->user);
            $obj->setValue("f_deleted", "f");
            $obj->save();
            $ret = $params['oid'];
        }
        else
            $ret = array("error"=>"obj_type and oid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * edit the objects
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function editObjects($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                $field = $obj->def->getField($params['field_name']);

                if ($field)
                {
                    if ($field->type == "fkey_multi")
                    {
                        if ($params['action'] == 'remove')
                        {
                            $obj->removeMValue($params['field_name'], $params['value']);
                        }
                        else // add
                        {
                            $obj->setMValue($params['field_name'], $params['value']);
                        }
                    }
                    else
                    {
                        $val = $params['value'];
                        $all_fields = $obj->def->getFields();
                        foreach ($all_fields as $fname=>$fdef)
                        {
                            if ($fdef->type != "object_multi" && $fdef->type != "fkey_multi")
                            {
                                if ($val == "<%".$fname."%>")
                                    $val = $obj->getValue($fname);
                            }
                        }

                        $obj->setValue($params['field_name'], $val);
                    }

                    $obj->save();
                }

                $olist->unsetObject($i);
            }

            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type and objects are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the objects
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getObjects()
    {
        $dbh = $this->ant->dbh;
        
        $result = $dbh->Query("select name, title, object_table, f_system from app_object_types order by title");
        $num = $dbh->GetNumberRows($result);
        $ret = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);
            $objdef = new CAntObject($dbh, $row['name']);
                
			$ret[] = array(
				// New definition
				'obj_type' => $row['name'], 
				'title' => $row['title'], 
				'icon' => $objdef->getIcon(),

				// Legacy
				'name' => $row['name'], 
				'objectTable' => $row['object_table'], 
                'fullTitle' => $objdef->fullTitle, 
				'listTitle' => $objdef->def->listTitle, 
				'fSystem' => ($row['f_system'] == 't') ? true : false
			);
        }
        $dbh->FreeResults($result);        

        return $this->sendOutputJson($ret);
    }

    /**
    * get the plugins
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getPlugins($params)
    {
        $ret = array();

        // Load all plugins in the objects/ofplugins/[object_type] directory
        $path = dirname(__FILE__) . "/../lib/js/ObjectLoader/Plugin/".$params['obj_type'];        
        if (file_exists($path))
        {            
            $dir_handle = opendir($path);
            if ($dir_handle)
            {                
                while($file = readdir($dir_handle))
                {
                    if(!is_dir($path."/".$file) && $file != '.' && $file != '..' && substr($file, -3)==".js")
                    {                        
                        $ret[] = rawurlencode(file_get_contents($path."/".$file));
                    }
                }
                closedir($dir_handle);                
            }            
        }

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Merge objects
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function mergeObjects($params)
    {
        $dbh = $this->ant->dbh;
        
        $ret = 1;        
        if ($params['obj_type'] && isset($params['objects']) && is_array($params['objects']))
        {
            $objs = array();
            // Create array of objects but skip first one
            for ($i = 1; $i < count($params['objects']); $i++)
            {
                $objs["o_".$params['objects'][$i]] = new CAntObject($dbh, $params['obj_type'], $params['objects'][$i], $this->user);
            }

            // All objects will be merged into thie first = 0
            $ant_obj = new CAntObject($dbh, $params['obj_type'], $params['objects'][0], $this->user);
            $ofields = $ant_obj->def->getFields();
            foreach ($ofields as $fname=>$field)
            {
                // Only update if field is drawing from another object
                if (isset($params['fld_use_'.$fname]) && is_numeric($params['fld_use_'.$fname]) && $params['fld_use_'.$fname]!=$ant_obj->id)
                {
                    $val = $objs["o_".$params['fld_use_'.$fname]]->getValue($fname);

                    if ($field->type=='fkey_multi')
                    {
                        // Purge
                        $ant_obj->removeMValues($fname);

                        if (is_array($val) && count($val))
                        {
                            // Add new
                            foreach ($val as $ent_val)
                                $ant_obj->setMValue($fname, $ent_val);
                        }
                    }
                    else if ($field->type=='object_multi' || ($field->type=='object' && !$field->subtype))
                        {
                            // This will be updated via associations below
                        }
                        else
                        {
                            $ant_obj->setValue($fname, $val);
                    }
                }

            }

            // Save changes
            $ret = $ant_obj->save();

            // Now update references to this object
            $result = $dbh->Query("select id, name, object_table from app_object_types 
									where id in (select type_id from app_object_type_fields where type='fkey' 
									and subtype='".$ant_obj->object_table."')");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                $odef = new CAntObject($dbh, $row['name'], null, $this->user);
                $ofields = $odef->def->getFields();

                // Loop though the merged objects - skip first object of course - to search for references
                // If this funciton was run a lot, it might be better to add multiple conditions with the or operator
                foreach ($objs as $chkobj)
                {
                    foreach ($ofields as $fname=>$field)
                    {
                        if ($field->type == 'fkey' && $field->subtype==$ant_obj->object_table)
                        {
                            $olist = new CAntObjectList($dbh, $row['name'], $this->user);
                            $olist->addCondition("and", $fname, "is_equal", $chkobj->id);
                            $olist->getObjects();
                            $num2 = $olist->getNumObjects();
                            for ($m = 0; $m < $num2; $m++)
                            {
                                $refobj = $olist->getObject($m);
                                $refobj->setValue($fname, $ant_obj->id);
                                $refobj->save();
                            }
                        }
                    }
                }
            }
            $dbh->FreeResults($result);

            // Set object reference code
            foreach ($objs as $chkobj)
            {
                if ($ant_obj->object_type_id && $chkobj->id)
                {
                    // Move everything objects reference
                    $result = $dbh->Query("update object_associations set object_id='".$ant_obj->id."' where 
                    						type_id='".$ant_obj->object_type_id."' and object_id='".$chkobj->id."'");
                    // Move everything that references objects
                    $result = $dbh->Query("update object_associations set assoc_object_id='".$ant_obj->id."' where 
                    						assoc_type_id='".$ant_obj->object_type_id."' and assoc_object_id='".$chkobj->id."'");
                }
            }

            // Delete all but the first object
            foreach ($objs as $chkobj)
            {
				$chkobj->setMovedTo($ant_obj->id);
                $chkobj->remove();
            }

            // return value of main object
            $ret = $ant_obj->id;
        }
        else
            $ret = array("error"=>"obj_type and objects are required params");            

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the foreign key value name
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getFkeyValName($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && $params['field'] && isset($params['id']))
        {            
            $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
            $field = $obj->def->getField($params['field']);

            if ($field->type == "object" && $field->subtype)
            {
                $obj = new CAntObject($dbh, $field->subtype, $params['id'], $this->user);
                $ret = $obj->getName();

                if (!$ret)
                    $ret = -1;
            }
            else if (($field->type == "fkey" || $field->type == "fkey_multi") && is_array($field->fkeyTable))
			{
				$query = "select ".$field->fkeyTable['key']." as key";
				if ($field->fkeyTable['title'])
					$query .= ", ".$field->fkeyTable['title']." as title";
				if ($field->fkeyTable['parent'])
				$query .= ", ".$field->fkeyTable['parent']." as parent";
                $query .= " from ".$field->subtype;
                $query .= " where ".$field->fkeyTable['key']."='".$params['id']."'";
                $result = $dbh->Query($query);
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetRow($result, $i);
                    $ret = $row['title'];
                }
            }
        }
        else
            $ret = array("error"=>"obj_type and field are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the foreign key default
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getFkeyDefault($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && $params['field'])
        {
            $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
            $field = $obj->def->getField($params['field']);
            if (($field->type == "object" && $field->subtype=="user") || (($params['field']=="user_id" || $params['field']=="owner_id") && $field->type == "fkey"))
            {
                // Set current user
                $ret = "{id:\"".$this->user->id."\", name:\"".$this->user->name."\"}";
            }
            else if ($field->type == "object" && $field->subtype)
			{
				$olist = new CAntObjectList($dbh, $field->subtype, $this->user);
				$olist->processFormConditions($params);
				$olist->getObjects(0, 1); // only get the top result - offset 0 limit 1
				$num = $olist->getNumObjects();
				if ($num)
				{
					$obj = $olist->getObject(0);    
					$ret = "{id:\"".$obj->id."\", name:\"".$obj->getName()."\"}";
				}
            }
            else if (($field->type == "fkey" || $field->type == "fkey_multi") && is_array($field->fkeyTable))
			{
				$query = "select ".$field->fkeyTable['key']." as key";
				if ($field->fkeyTable['title'])
					$query .= ", ".$field->fkeyTable['title']." as title";
				if ($field->fkeyTable['parent'])
				$query .= ", ".$field->fkeyTable['parent']." as parent";
                $query .= " from ".$field->subtype;
                $result = $dbh->Query($query);
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetRow($result);
                    $ret = "{id:\"".$row['key']."\", name:\"".$row['title']."\"}";
                }
            }

            if (!$ret)
                $ret = -1;
        }
        else
            $ret = array("error"=>"obj_type and field are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the object name
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getObjName($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && $params['id'])
        {
            $obj = new CAntObject($dbh, $params['obj_type'], $params['id'], $this->user);
            $ret = $obj->getName();

            if (!$ret)
                $ret = array("error"=>"Error occurred when getting object name.");
        }
        else
            $ret = array("error"=>"obj_type and id are required params");

		if ($params['output'] == "xml")
        	return $this->sendOutputXml($ret);
		else
        	return $this->sendOutputJson($ret);
    }

    /**
    * association add
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function associationAdd($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && $params['obj_id'] && $params['assoc_obj_type'] && $params['assoc_obj_id'])
        {
            $obj = new CAntObject($dbh, $params['obj_type'], $params['obj_id'], $this->user);
            $ret = $obj->addAssociation($params['assoc_obj_type'], $params['assoc_obj_id']);
        }
        else
            $ret = array("error"=>"obj_type, obj_id, assoc_obj_type, and assoc_obj_id are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the folder id
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getFolderId($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['field'] && $params['obj_type'] && $params['oid'])
        {            
            $obj = new CAntObject($dbh, $params['obj_type'], $params['oid'], $this->user);
            $field = $obj->def->getField($params['field']);            
            $folder_id = $obj->getValue($params['field']);

            if (!$folder_id && is_array($field->fkeyTable) && $field->fkeyTable["autocreate"] 
            && $field->fkeyTable["autocreatebase"] && $field->fkeyTable["autocreatename"])
            {
                $antfs = new AntFs($dbh, $this->user);
                $path = $field->fkeyTable["autocreatebase"]."/".$obj->getValue($field->fkeyTable["autocreatename"]);                
                $folder = $antfs->openFolder($path, true);
                if ($folder)
                    $folder_id = $folder->id;
            }

            if ($folder_id)
                $ret = $folder_id;
            else
                $ret = array("error"=>"Error occurred while trying to get the folder id");
        }
        else
            $ret = array("error"=>"obj_type, field, and oid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * get the activity type
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getActivityTypes()
    {
        $dbh = $this->ant->dbh;
        
        $ret = array();
        $result = $dbh->Query("select id, name, obj_type from activity_types order by name");
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetRow($result, $i);            
            $ret[] = array("id" => $row['id'], "name" => $row['name'], "obj_type" => $row['obj_type']);
        }        

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Save Activity Type
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveActivityType($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['name'])
        {
            $id = null;
            
            if(isset($params['id']))
                $id = $params['id'];
                
            if(!isset($params['obj_type']))
                $params['obj_type'] = "activity";

            $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);
            $ret = $obj->saveActivityType($id, $params['name']);
        }
        else
            $ret = array("error"=>"obj_type, field, and oid are required params");

        return $this->sendOutputJson($ret);
    }

    /**
    * save the recurrencepattern
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveRecurrencepattern($params)
    {
        $dbh = $this->ant->dbh;
        
        $rp_newvobj =  json_decode(stripslashes($params['objpt_json']));

        if( is_object($rp_newvobj) )
        {
            if( $rp_newvobj->parentId<1 )
            {  
                $ret = -1;
            }
            else
            { 
                $ant_obj = new CAntObject($dbh, $rp_newvobj->object_type, $rp_newvobj->parentId, $this->user); 
            }        

            $rp = new CRecurrencePattern($dbh);

            $rp->id = $rp_newvobj->id;
            $rp->object_type_id = $rp_newvobj->object_type_id; 
            $rp->object_type = $rp_newvobj->object_type; 
            $rp->dateProcessedTo = $rp_newvobj->dateProcessedTo; 
            $rp->parentId = $rp_newvobj->parentId; 
            $rp->type = $rp_newvobj->type; 
            $rp->interval = $rp_newvobj->interval;
            $rp->dateStart = $rp_newvobj->dateStart; 
            $rp->dateEnd = $rp_newvobj->dateEnd; 
            $rp->timeStart = $rp_newvobj->timeStart; 
            $rp->timeEnd = $rp_newvobj->timeEnd; 
            $rp->fAllDay = $rp_newvobj->fAllDay; 
            $rp->dayOfMonth = $rp_newvobj->dayOfMonth; 
            $rp->monthOfYear = $rp_newvobj->monthOfYear; 
            $rp->fActive = $rp_newvobj->fActive;
            $rp->instance = $rp_newvobj->insdatetance;

            if ($rp_newvobj->day1 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SUNDAY;
            if ($rp_newvobj->day2 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_MONDAY;
            if ($rp_newvobj->day3 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_TUESDAY;
            if ($rp_newvobj->day4 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_WEDNESDAY;
            if ($rp_newvobj->day5 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_THURSDAY;
            if ($rp_newvobj->day6 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_FRIDAY;
            if ($rp_newvobj->day7 == 't')
                $rp->dayOfWeekMask = $rp->dayOfWeekMask | WEEKDAY_SATURDAY;

            $ret = $rp->save();

            // Save recurrence id to object
            if ($rp->id && $ant_obj && !$ant_obj->getValue($ant_obj->def->recurRules['field_recur_id']))
            {
                $ant_obj->setValue($ant_obj->def->recurRules['field_recur_id'], $rp->id);
                $ant_obj->save(false);
            }
        }
        else
        {
            $ret = -3;
        }

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Move objects by grouping
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function moveByGrouping($params)
    {
		$dbh = $this->ant->dbh;
		$numMoved = 0;

		if (!$params['obj_type'] || !$params['field_name'] || !$params['move_to'])
			return $this->sendOutputJson(array("error"=>"Params: obj_type, field_name, move_from, and move_to are all required"));

		if (is_array($params['objects']) || $params['all_selected'])
		{
			// Get special groups/mailboxes if we are dealing with email
			/* replaced with object move
			if ($params['obj_type'] == "email_thread" || $params['obj_type'] == "email_message")
			{
				$junkid = EmailGetSpecialBoxId($dbh, $this->user->id, "Junk Mail");
				$sentid = EmailGetSpecialBoxId($dbh, $this->user->id, "Sent");
				$trashid = EmailGetSpecialBoxId($dbh, $this->user->id, "Trash");
                
                if(!isset($params['addMailbox']))
                {
                    // Get all mailboxes
                    $mParams = array("field" => "mailbox_id", "obj_type" => "email_thread", "noPrint" => 1);
                    $mailBoxes = $this->getGroupings($mParams);
                }
			}
			*/

			$olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
			$olist->processFormConditions($params);
			$olist->getObjects();
			$num = $olist->getNumObjects();
			for ($i = 0; $i < $num; $i++)
			{
				$obj = $olist->getObject($i);

				if ($params['obj_type'] == "email_thread" || $params['obj_type'] == "email_message")
				{
					$obj->move($params['move_to']);
					/*
					if ($params['move_to'] == $trashid)
					{
						$obj->remove();
					}
					else
					{
                        // Remove all mailboxes except for sentid
                        if(!isset($params['addMailbox']))
                        {
                            foreach($mailBoxes as $mbox)
                            {
                                if($mbox['id'] != $sentid) // if mailbox id is sent id, do not remove
                                {
                                    $obj->removeMValue('mailbox_id', $mbox['id']);
                                    $obj->save();
                                }
                            }
                        }
                        
						$un_ident = ($params['obj_type'] == "email_thread") ? "thread" : "id";
						$result = EmailMoveMessage($dbh, $un_ident, $obj->id, $params['move_to'], $sentid, $trashid, null);
					}
					*/
				}
				else
				{
					$obj->removeMValue($params['field_name'], $params['move_from']);
					$obj->setMValue($params['field_name'], $params['move_to']);
					$obj->save();
				}
			}

			$numMoved = $num;
		}

		$this->sendOutputJson($numMoved);
		return true;
	}
    
    /**
    * Saves the attachments
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function saveAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $typeName = $params['typeName'];
        $id = $params['id'];
        $attachedFiles = $params['attachedFiles'];
        
        if (!empty($typeName) && !empty($attachedFiles) && $id > 0)
        {
            //make sure upload data is in array format
            if(!is_array($attachedFiles))
                $attachedFiles = explode(",", $attachedFiles);
            
            $antfs = new AntFs($dbh);
            $path = "/System/Objects/$typeName/$id";
            $fileFolder = $antfs->openFolder($path, true);
            
            foreach ($attachedFiles as $fid)
            {
				$file = $antfs->openFileById($fid);
				$file->move($fileFolder);
            }

            $ret = $fileFolder->id;
        }
        else
            $ret = 0;

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get the attachments
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $typeName = $params['typeName'];
        $id = $params['id'];
        
        $ret = array();
        if (!empty($typeName) && $id > 0)
        {
            
            $antfs = new AntFs($dbh);
            $path = "/System/Objects/$typeName/$id";
            $fileFolder = $antfs->openFolder($path, true);
            $folderId = $fileFolder->id;
            
            if($folderId > 0)
            {
				$fileList = new CAntObjectList($dbh, "file", $this->user);
				$fileList->addCondition("folder_id", $folderId);
				$fileList->getObjects();
				for ($i = 0; $i < $fileList->getNumObjects(); $i++)
				{
					$file = $fileId->getObjectMin();
                    $ret[$file['id']] = array("id" => $file['id'], "name" => $file['name']);
				}
            }
            
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    /**
    * Remove Attachment
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function removeAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $id = $params['id'];
        
        if ($id > 0)
        {
            $antfs = new AntFs($dbh);
            $antfs->removeFileById($id);
            $ret = 1;
        }
        else
            $ret = 0;
            
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Change approval request status
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function approvalChangeStatus($params)
    {
		if (!$params['oid'] || !$params['status'])
			return $this->sendOutputJson(array("error"=>"Params: oid and status are required"));

		$obj = new CAntObject($this->ant->dbh, "approval", $params['oid'], $this->user);

		// First make sure the owner is the one making the change
		if ($this->user->id != $obj->getValue("owner_id"))
			return $this->sendOutputJson(array("error"=>"Only the owner of this request can change the status"));

		$obj->setValue("status", $params['status']);
		$obj->setValue("ts_status_change", 'now');
		$ret = $obj->save();

		return $this->sendOutputJson($ret);
	}
    
    /**
    * Get the default value of the field
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fieldGetDefault($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array("on" => "", "value" => "", "coalesce" => "");
        
        $obj_type = $params['obj_type'];
        $name = $params['name'];
        
        if ($name && $obj_type)
        {
            $obj = new CAntObject($dbh, $obj_type);
            $query = "select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
                $fid = $dbh->GetValue($result, 0, "id");

            $dbh->FreeResults($result);
            
            if ($fid)
            {
                $query = "select id, on_event, value, coalesce from app_object_field_defaults where field_id='$fid'";
                $result = $dbh->Query($query);
                if ($dbh->GetNumberRows($result))
                {
                    $row = $dbh->GetRow($result, 0);                    
                    $ret = array("on" => $row['on_event'], "value" => $row['value'], "coalesce" => $row['coalesce']);
                }
            }
        }
        
        $this->sendOutputJson($ret);
    }
    
    /**
    * Set the default value of the field
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fieldSetDefault($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $obj_type = $params['obj_type'];
        $name = $params['name'];
        $on = $params['on'];
        $value = $params['value'];
        
        if ($name && $obj_type)
        {
            $obj = new CAntObject($dbh, $obj_type);
            $query = "select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'";
            $result = $dbh->Query($query);
            
            if ($dbh->GetNumberRows($result))
                $fid = $dbh->GetValue($result, 0, "id");

            $dbh->FreeResults($result);    
            
            if ($on && $fid)
            {
                if ($dbh->GetNumberRows($dbh->Query("select id from app_object_field_defaults where field_id='$fid'")))
                {
                    $dbh->Query("update app_object_field_defaults set on_event='".$dbh->Escape($on)."', value='".$dbh->Escape($value)."'
                                    where field_id='$fid';");
                }
                else
                {
                    $dbh->Query("insert into app_object_field_defaults(field_id, on_event, value) 
                                                values('$fid', '".$dbh->Escape($on)."', '".$dbh->Escape($value)."');");
                }
                
                $ret = array("status" => "$name field has set a default value!");
            }            
            else if ($fid) // clear default
            {
                $dbh->Query("delete from app_object_field_defaults where field_id='$fid';");
                $ret = array("status" => "No default values was saved!");
            }                
            else
                $ret = array("status" => "Error occured while saving default value.", "error" => 1);
        }
        else
            $ret = array("status" => "Name and Object Type are required parameters.", "error" => 1);

        $this->sendOutputJson($ret);
    }
    
    /**
    * Set Field Required
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fieldSetRequired($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $obj_type = $params['obj_type'];
        $name = $params['name'];
        $required = $params['required'];
        
        if ($name && $obj_type)
        {
            if(empty($required))
                $required = "f";
            
            $obj = new CAntObject($dbh, $obj_type);
            $query = "update app_object_type_fields set f_required='$required' where name='$name' 
                                    and type_id='".$obj->object_type_id."'";
            $result = $dbh->Query($query);
            
            $ret = array("status" => "$name required field has been updated!");
        }
        else        
            $ret = array("status" => "Name and Object Type are required parameters.", "error" => 1);
            
        $this->sendOutputJson($ret);
    }
    
    /**
    * Add Field Option
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fieldAddOption($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $obj_type = $params['obj_type'];
        $name = $params['name'];
        $value = $params['value'];
        
        if ($name && $obj_type && $value)
        {
            $obj = new CAntObject($dbh, $obj_type);
            $db_schema = "public";
            $oname = $obj_type;
            
            // Get schema (if other than public)
            if (strpos($obj_type, ".") !== false)
            {
                $parts = explode(".", $obj_type);
                $db_schema = "zudb_".$parts[0];
                $oname = $parts[1];
            }

            $result = $dbh->Query("select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'");
            if ($dbh->GetNumberRows($result))
            {
                $fid = $dbh->GetValue($result, 0, "id");

                if ($fid)
                {
                    $dbh->Query("insert into app_object_field_options(field_id, key, value) 
                                                values('$fid', '".$dbh->Escape($value)."', '".$dbh->Escape($value)."');");
                    $this->cache->remove($dbh->dbname."/objectdefs/fieldoptions/".$obj->object_type_id."/".$fid);
                    
                    $ret = array("status" => "$name field option successfully saved.");
                }
            }
        }
        else        
            $ret = array("status" => "Name, Object Type and Value are required parameters.", "error" => 1);
            
        $this->sendOutputJson($ret);
    }
    
    /**
    * Field Delete Option
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fieldDeleteOption($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        $obj_type = $params['obj_type'];
        $name = $params['name'];
        $value = $params['value'];
        
        if ($name && $obj_type && $value)
        {
            $obj = new CAntObject($dbh, $obj_type);
            $db_schema = "public";
            $oname = $obj_type;
            
            // Get schema (if other than public)
            if (strpos($obj_type, ".") !== false)
            {
                $parts = explode(".", $obj_type);
                $db_schema = "zudb_".$parts[0];
                $oname = $parts[1];
            }

            $result = $dbh->Query("select id from app_object_type_fields where name='$name' and type_id='".$obj->object_type_id."'");
            if ($dbh->GetNumberRows($result))
            {
                $fid = $dbh->GetValue($result, 0, "id");

                if ($fid)
                {
                    $dbh->Query("delete from app_object_field_options where field_id='$fid' and key='".$dbh->Escape($value)."'");
                    $this->cache->remove($dbh->dbname."/objectdefs/fieldoptions/".$obj->object_type_id."/".$fid);
                    $ret = array("status" => "$name field option has been removed.");
                }
            }
        }
        else        
            $ret = array("status" => "Name, Object Type and Value are required parameters.", "error" => 1);
            
        $this->sendOutputJson($ret);
    }

	/**
	 * Get headers from imported csv file
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
	public function importGetHeaders($params)
	{
		$dbh = $this->ant->dbh;
		$antfs = new AntFs($dbh, $this->user);
        $ret = array();

		if ($params['data_file_id'])
		{
			$headers_buf = "";

			$file = $antfs->openFileById($params['data_file_id']);

			$tmpfname = $file->copyToTemp();

			if ($tmpfname)
			{
				$fh = fopen($tmpfname, "r");

				$data = fgetcsv($fh, 1024, ',', '"');
				$num = count($data);

				for ($i = 0; $i < $num; $i++)
				{
					$ret[] = $data[$i];
				}

				fclose($fh);
				unlink($tmpfname);
			}
		}

		$this->sendOutputJson($ret);
	}

	/**
	 * Get import templates
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
	public function importGetTemplates($params)
	{
		$dbh = $this->ant->dbh;
		$antfs = new AntFs($dbh, $this->user);
        $ret = array();

		if ($params['obj_id'])
		{
			// TODO: get templates
		}

		$this->sendOutputJson($ret);
	}

	/**
	 * Import file given the params
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
	public function importRun($params)
	{
        $ret = array();

		$jobdata = json_decode($params['import_data'], true); // second param forces array
		$jobdata["send_notifaction_to"] = $this->user->getEmail();
		$jobdata["data_file_id"] = $jobdata['file_id'];

		$wp = new WorkerMan($this->ant->dbh);
		$jobid = $wp->runBackground("lib/object/import", serialize($jobdata));

		$this->sendOutputJson($jobid);
	}
    
    /**
     * Gets the unique name of the object 
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getUniqueName($params)
    {
        $ret = array();
        
        if ($params['objType'] && $params['objId'])
        {
            $obj = CAntObject::factory($this->ant->dbh, $params['objType'], $params['objId'], $this->user);

			// Set field values for namespace
			$fields = $obj->def->getFields();
			foreach ($fields as $fname=>$field)
			{
				if ($field->type == "fkey" || $field->type == "object")
					$obj->setValue($fname, $params[$fname]);
			}

            $uname = $obj->getUniqueName(false);
            
            $ret = array("uniqueName" => $uname);
        }
        else
            $ret = array("error" => "Object type and id are required params!");
        
        $this->sendOutputJson($ret);
    }
    
    /**
     * Verifies the unique name
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function verifyUniqueName($params)
    {
        $ret = array();
        
        if ($params['objType'] && $params['objId'])
        {
            $obj = CAntObject::factory($this->ant->dbh, $params['objType'], $params['objId'], $this->user);

			// Set field values for namespace
			$fields = $obj->def->getFields();
			foreach ($fields as $fname=>$field)
			{
				if ($field->type == "fkey" || $field->type == "object")
					$obj->setValue($fname, $params[$fname]);
			}

            if($obj->verifyUniqueName($params['uniqueName']))
                $ret = array("value" => 1, "message" => "", "uniqueName" => $params['uniqueName']);
            else
                $ret = array("value" => -1, "message" => "Name already in use.", "uniqueName" => $params['uniqueName']);
        }
        else
            $ret = array("error" => "Object type and id are required params!");
        
        $this->sendOutputJson($ret);
    }
    
    /**
     * Saves the unique name
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function saveUniqueName($params)
    {
        $ret = array();
        
        if ($params['objType'] && $params['objId'] && $params['uniqueName'])
        {
            $obj = CAntObject::factory($this->ant->dbh, $params['objType'], $params['objId'], $this->user);

			// Set field values for namespace
			$fields = $obj->def->getFields();
			foreach ($fields as $fname=>$field)
			{
				if ($field->type == "fkey" || $field->type == "object")
					$obj->setValue($fname, $params[$fname]);
			}
            
            // Verify unique name
            if($obj->verifyUniqueName($params['uniqueName']))
            {
                $obj->setValue("uname", $params['uniqueName']);
                $objId = $obj->save();
                
                if($objId)
                {
                    $ret = array("currentName" => $params['uniqueName']);
                }
                else
                    $ret = array("error" => "Error while saving uname!");
            }
            else
            {
				// Generate a new unique name
				$newUname = $obj->getUniqueName();
                $ret = array("currentName" => $newUname);
            }
        }
        else
            $ret = array("error" => "Uname and Object type and id are required params!");
        
        $this->sendOutputJson($ret);
    }
    
    /**
     * Save the view
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function saveView($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['name'] && $params['obj_type'])        
        {
            $obj = new CAntObject($dbh, $params['obj_type'], null, $this->user);        
            $ret = $obj->saveView($params);
        }
        else
            $ret = array("error"=>"obj_type and name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Delete the view
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function deleteView($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['dvid'])        
        {
            $dbh->Query("delete from app_object_views where id='".$params['dvid']."'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"dvid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Set the default view
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function setViewDefault($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['view_id'] && $params['obj_type'])        
        {
			/*
            if (isset($params['filter_key']) && $params['filter_key'])
                $this->user->setSetting("/objects/views/default/".$params['filter_key']."/".$params['obj_type'], $params['view_id']);
            else
			 */
            $this->user->setSetting("/objects/views/default/".$params['obj_type'], $params['view_id']);

            $ret = 1;
        }
        else
            $ret = array("error"=>"view_id and obj_type are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get the object views
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getViews($params)
    {
        $objectType = $params['objectType'];
        $ret = array();
        
        if($objectType)
        {
            $obj = CAntObject::factory($this->ant->dbh, $objectType, null, $this->user);        
        
            $obj->loadViews($params['fromViewManager']);
            $num = $obj->getNumViews();
            for ($i = 0; $i < $num; $i++)
            {
                $view = $obj->getView($i);
                $ret[] = $view;
            }
        }
        else
            $ret = array("error"=>"objectType is a required param!");
        
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Set viewed flag for an object
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function setViewed($params)
    {
        if ($params['oid'] && $params['obj_type'])        
		{
			$obj = CAntObject::factory($this->ant->dbh, $params['obj_type'], $params['oid'], $this->user);
			$obj->setViewed();

			return $this->sendOutputJson(1);
		}

		return $this->sendOutputJson(-1); // fail
	}

    /**
     * Get list of recently viewed objects for this user
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getRecent($params)
    {
        $ret = array();
        
		$recent = $this->cache->get($this->ant->dbh->dbname . "/RecentObjects/" . $this->user->id);
		if (is_array($recent) && count($recent))
		{
			foreach ($recent as $oref)
			{
				$parts = explode(":", $oref);

				if (count($parts)==2)
				{
					$obj = CAntObject::factory($this->ant->dbh, $parts[0], $parts[1], $this->user);

					if ($obj && $obj->id && $obj->getValue("f_deleted") != 't')
					{
						$ret[] = array(
							'obj_type' => $obj->object_type,
							'id' => $obj->id,
							'name' => $obj->getName(),
							'icon' => $obj->getIcon(16, 16),
						);
					}
				}
			}
		}
        
        return $this->sendOutputJson($ret);
    }

    /**
	 * Get the object definition
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getDefinition($params)
	{
		$objType = $params['obj_type'];
		if (!$objType)
            return $this->sendOutput(array("error"=>"objectType is a required param!"));

		$defLoader = $this->ant->getServiceLocator()->get("EntityDefinitionLoader");

		if (isset($params['clearcache'])) // force a refresh of cache
			$defLoader->clearCache($objType);

		$def = $defLoader->get($objType);
		$ret = $def->toArray();

		// Get views with old CAntObject
		// TODO: As soon as views are included in EntityDefinition then update here
		$obj = new CAntObject($this->ant->dbh, $params['obj_type'], null, $this->user);
		$num = $obj->getNumViews();
		$ret['views'] = array();
		for ($i = 0; $i < $num; $i++)
		{
			$view = $obj->getView($i);
			$ret['views'][] = $view->toArray($this->user->id);
		}

		// Get browser mode preference
		$browserMode = $this->user->getSetting("/objects/browse/mode/" . $params['obj_type']);
		// Set default view modes
		if (!$browserMode) 
		{
			switch ($params['obj_type'])
			{
			case 'email_thread':
			case 'note':
				$browserMode = "previewV";
				break;
			default:
				$browserMode = "table";
				break;
			}
		}
		$ret["browser_mode"] = $browserMode;

		// Get browser blank content
		$ret['browser_blank_content'] = $defLoader->getBrowserBlankContent($params['obj_type']);

		/*
		$obj = CAntObject::factory($this->ant->dbh, $objType, null, $this->user);
		$objf = $obj->def;
		$ofields = $objf->getFields();

		if ($v['clearcache']) // force a refresh of cache
		{
			$obj->clearDefinitionCache();
			$obj->load();
		}

		if (!$obj->title)
		{
			$oname_tmp = $objType;
			if (strpos($objType, ".") !== false)
			{
				$parts = explode(".", $objType);
				$oname_tmp = $parts[1];
			}
			$obj->title = ucfirst($oname_tmp);
		}

		$ret = array(
			"title" => $obj->title,
			"name_field" => $obj->listTitle,
			"icon_name" => $obj->getIconName(),
		);

		// Child secuirty
		// ------------------------------------------------------------
		$ret['security'] = array();
		if (is_array($obj->def->childDacls) && count($obj->def->childDacls))
		{
			foreach ($obj->def->childDacls as $chldobj)
				$ret['security']['child_object'] = $chldobj;
		}

		// Recurrence
		// ------------------------------------------------------------
		$ret['recurrence'] = array(
			'hasrecur' => ($obj->def->recurRules) ? true : false,
		);
		if ($obj->def->recurRules)
		{
			$ret['recurrence']['field_time_start'] = $obj->def->recurRules['field_time_start'];
			$ret['recurrence']['field_time_end'] = $obj->def->recurRules['field_time_end'];
			$ret['recurrence']['field_date_start'] = $obj->def->recurRules['field_date_start'];
			$ret['recurrence']['field_date_start'] = $obj->def->recurRules['field_date_start'];
			$ret['recurrence']['field_recur_id'] = $obj->def->recurRules['field_recur_id'];
		}

		// Fields
		// ------------------------------------------------------------
		$ret['fields'] = array();
		foreach ($ofields as $fname=>$fdef)
		{
			$field = array(
				'name' => $fname,
				'title' => $fdef->title,
				'type' => $fdef->type,
				'subtype' => $fdef->subtype,
				'use_when' => $fdef->getUseWhen(),
				'default' => $fdef->default,
				'readonly' => $fdef->readonly,
				'system' => $fdef->system,
				'unique' => $fdef->unique,
				'required' => $fdef->required,
				'auto' => false, // TODO: check into this
				//'auto' => ($field['auto']) ? true : false,
			);

			$field['optional_values'] = array();
			if ($fdef->optionalValues && is_array($fdef->optionalValues) && count($fdef->optionalValues))
			{
				foreach ($fdef->optionalValues as $key=>$val)
				{
					if (($key || $val) && $key!="0" && $val!="0")
					{
						$field['optional_values'][] = array(
							'key' => $key,
							'value' => $val,
						);
					}
				}
			}
			else if ($fdef->type == "alias")
			{
				foreach ($ofields as $afname=>$afield)
				{
					if ($afield->type == $fdef->subtype)
					{
						$field['optional_values'][] = array(
							'key' => $afname,
							'value' => $afield->title,
						);
					}
				}
			}

			$ret['fields'][] = $field;
		}
		 */

		return $this->sendOutput($ret);
	}

	/**
     * Clone an object
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function cloneObjectReferences($params)
    {
		if (!$params['oid'] || !$params['obj_type'] || !$params['from_id'])
			return $this->sendOutput(array("error"=>"oid, obj_type, and from_id are all required params"));

		$obj = CAntObject::factory($this->ant->dbh, $params['obj_type'], $params['oid'], $this->user);
		$obj->cloneObjectReferences($params['from_id']);

		return $this->sendOutput(1);
	}

	/**
	 * Mark an object as seen and clear all notifications for the current user
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function markSeen($params)
	{
		if (!$params['oid'] || !$params['obj_type'])
			return $this->sendOutput(array("error"=>"oid and obj_type are all required params"));

		$obj = AntObjectLoader::getInstance($this->ant->dbh)->byId($params['obj_type'], $params['oid']);
		if ($obj->def->getField("f_seen"))
		{
			$obj->setValue("f_seen", 't');
			$obj->save();
		}

		// Clear notifications
		$list = new CAntObjectList($this->ant->dbh, "notification", $this->user);
		$list->addCondition("and", "owner_id", "is_equal", $this->user->id);
		$list->addCondition("and", "obj_reference", "is_equal", $params['obj_type'] . ":" . $params['oid']);
		$list->addCondition("and", "f_seen", "is_equal", 'f');
		$list->getObjects();
		$num = $list->getNumObjects();
		for ($j = 0; $j < $num; $j++)
		{
			$notif = $list->getObject($j);
			$notif->setValue("f_seen", 't');
			$notif->save(false);
		}

		return $this->sendOutput(1);
	}

	/**
	 * Mark an object as seen and clear all notifications for the current user
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function addField($params)
	{
		if (!$params['obj_type'] || !$params['name'])
			return $this->sendOutput(array("error"=>"obj_type and field name are required parameters"));

		$def = $this->ant->getServiceLocator()->get("EntityDefinitionLoader")->get($params['obj_type']);

		// check if the field already exists
		if ($def->getField($param['name']))
			return $this->sendOutput(array("error"=>"A field by this name already exists"));

		// TODO: Test permissions for the current user

		// Crete and add field
		$field = new \Netric\EntityDefinition\Field();
		$field->fromArray($params);
		$def->addField($field);

		// Get datamapper and save
		$dm = $this->ant->getServiceLocator()->get("EntityDefinition_DataMapper");
		$dm->save($def);

		return $this->sendOutput($field->toArray());
	}

	/**
	 * Remove a field from an object definition
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function removeField($params)
	{
		if (!$params['obj_type'] || !$params['fname'])
			return $this->sendOutput(array("error"=>"obj_type and field name are required parameters"));

		$def = $this->ant->getServiceLocator()->get("EntityDefinitionLoader")->get($params['obj_type']);
		$field = $def->getField($params['fname']);

		// check if the field exists
		if (!$field)
			return $this->sendOutput(array("error"=>"There are no fields by that name"));

		if ($field->system)
			return $this->sendOutput(array("error"=>"System fields cannot be deleted"));

		// TODO: Test permissions for the current user

		// Crete and add field
		$def->removeField($params['fname']);

		// Get datamapper and save
		$dm = $this->ant->getServiceLocator()->get("EntityDefinition_DataMapper");
		$dm->save($def);

		return $this->sendOutput(1);
	}

	/**
	 * Send member invitations to an object
	 *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function sendInvitations($params)
	{
		if (!$params['obj_type'] || !$params['oid'] || !$params['field'])
			return $this->sendOutput(array("error"=>"obj_type and field are required parameters"));

		$obj = CAntObject::factory($this->ant->dbh, $params['obj_type'], $params['oid'], $this->user);
		$obj->sendInvitations($params['field'], ($params['onlynew']=='t') ? true : false);

		return $this->sendOutput(1);
	}
}
