<?php
/**
 * Contact application actions
 */
    require_once(dirname(__FILE__).'/../lib/AntConfig.php');
    require_once(dirname(__FILE__).'/../lib/CAntObject.php');
    require_once(dirname(__FILE__).'/../lib/CAntObjectList.php');
    require_once(dirname(__FILE__).'/../lib/WorkFlow.php');
    require_once(dirname(__FILE__).'/../email/email_functions.awp');
    //require_once(dirname(__FILE__).'/../community/feed_functions.awp');
    require_once(dirname(__FILE__).'/../lib/Controller.php');
    
    ini_set("max_execution_time", "7200");    
    ini_set("max_input_time", "7200");    
    
class ContentController extends Controller
{
    /**
     * Feed Add Fields
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedAddField($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['name'] && $params['type'] && $params['fid'])
        {
            $col_title = $params['name'];
            $col_name = str_replace(" ", "_", $col_title);
            $col_name = str_replace("'", "", $col_name);
            $col_name = str_replace('"', "", $col_name);
            $col_name = str_replace('&', "", $col_name);
            $col_name = str_replace('%', "", $col_name);
            $col_name = str_replace('$', "", $col_name);
            $col_name = str_replace("\\", "", $col_name);
            
            $obj = new CAntObject($dbh, "content_feed_post", null, $this->user);

            if ($params['type'] == "file")
            {
                $fdef = array('title'=>$col_title, 'type'=>'object', 'subtype'=>'file', 'system'=>false, 'use_when'=>"feed_id:".$params['fid']);
            }
            else
            {
                $fdef = array('title'=>$col_title, 'type'=>$params['type'], 'subtype'=>'', 'system'=>false, 'use_when'=>"feed_id:".$params['fid']);
            }
            $obj->addField(strtolower($col_name), $fdef);
            unset($obj);

            $ret = 1;
        }
        else
            $ret = array("error"=>"name, fid, and type are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Feed Delete Field
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedDeleteField($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['dfield'])
        {
            $obj = new CAntObject($dbh, "content_feed_post", null, $this->user);
            $obj->removeField($params['dfield']);
            $ret = 1;
        }
        else
            $ret = array("error"=>"dfield are required params");

        $this->sendOutputJson($ret);
        return $ret;        
    }
    
    /**
     * Feed Get Field
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedGetFields($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['fid'])
        {
            $ret = array();
            $obj = new CAntObject($dbh, "content_feed_post", null, $this->user);
            $ofields = $obj->def->getFields();
            foreach ($ofields as $fname=>$field)
            {
                if ($field->getUseWhen() == "feed_id:".$params['fid'])
                {
                    $pid = null;
                    if(isset($params['pid']))
                        $pid = $params['pid'];
                        
                    //$value = FeedFieldGetVal($dbh, $field->id, $pid);
                    
                    $ret[] = array("id" => $field->id, "name" => $fname, "title" => $field->title,
                                    "type" => $field->type, "value" => $value);
                }
            }
        }
        else
            $ret = array("error"=>"fid and dfield are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Feed Post Publish
	 *
	 * @depricated We now use workflows to publish
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedPostPublish($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['fid'])
        {
            //FeedPushUpdates($dbh, $params['fid']);
            $ret = 1;
        }
        else
            $ret = array("error"=>"fid and dfield are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Set Color
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupSetColor($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $color = $params['color'];

        if ($gid && $color)
        {
            $dbh->Query("update xml_feed_groups set color='$color' where id='$gid'");
            $ret = $color;
        }
        else
            $ret = array("error"=>"gid and color are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Rename
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupRename($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);

        if ($gid && $name)
        {
            $dbh->Query("update xml_feed_groups set name='".$dbh->Escape($name)."' where id='$gid'");
            $ret = $name;
        }
        else
            $ret = array("error"=>"gid and name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Delete
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh;        
        $gid = $params['gid'];

        if ($gid)
        {
            $dbh->Query("delete from xml_feed_groups where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group Add
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh;
        $pgid = null;
        $name = rawurldecode($params['name']);
        $color = rawurldecode($params['color']);
        
        if(isset($params['pgid']))
            $pgid = $params['pgid'];

        if ($name && $color)
        {
            $query = "insert into xml_feed_groups(parent_id, name, color) 
                      values(" . $dbh->EscapeNumber($pgid) . ", '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
                      select currval('xml_feed_groups_id_seq') as id;";
            $result = $dbh->Query($query);
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);
                $ret = $row['id'];
            }
            else
                $ret = array("error"=>"Error occured when saving content group.");
        }
        else
            $ret = array("error"=>"gid and name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Feed Add Category
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedAddCategory($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['name'] && $params['fid'])
        {            
            $result = $dbh->Query("insert into xml_feed_post_categories(name, feed_id)
                        values('".$dbh->Escape($params['name'])."', '".$params['fid']."');
                        select currval('xml_feed_post_categories_id_seq') as id;");
                        
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
        }
        else
            $ret = array("error"=>"name and fid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Feed Delete Category
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedDeleteCategory($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['fid'] && $params['dcat'])
        {
            $dbh->Query("delete from xml_feed_post_categories where id='".$params['dcat']."' and feed_id='".$params['fid']."'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"dcat and fid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Feed Get Categories
     *
     * @param array $params    An assocaitive array of parameters passed to this function. 
     */
    public function feedGetCategories($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['fid'])
        {
            $ret = array();
            $result = $dbh->Query("select id, name from xml_feed_post_categories where feed_id='".$params['fid']."' order by name");
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetRow($result, $i);
                $ret[] = array("id" => $row['id'], "name" => $row['name']);
            }            
        }
        else
            $ret = array("error"=>"dcat and fid are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    public function feedPostSaveFields($params)
    {
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }
}
