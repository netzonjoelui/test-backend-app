<?php
/**
 * Infocenter application actions
 */
require_once("lib/CAntFs.awp");
require_once("userfiles/file_functions.awp");
require_once("infocenter/ic_functions.php");

/**
 * Class for controlling customer functions
 */
class InfocenterController extends Controller
{
    /**
     * Save Document
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function saveDocument($params)
    {
        $dbh = $this->ant->dbh;
        $docid = null;
        
        if(isset($params['docid']))
            $docid = $params['docid'];
        $body = rawurldecode($params['body']);
        $title = rawurldecode($params['title']);
        $keywords = rawurldecode($params['keywords']);
        
        $videoFileId = null;
        if(isset($params['video_file_id']))
            $videoFileId = $params['video_file_id'];
        
        if ($docid)
        {
            $dbh->Query("update ic_documents set title='".$dbh->Escape($title)."', keywords='".$dbh->Escape($keywords)."', 
                            video_file_id=".$dbh->EscapeNumber($videoFileId).",
                            body='".$dbh->Escape($body)."' where id='".$docid."'");

            $dbh->Query("delete from ic_document_group_mem where document_id='$docid'");
            if(isset($params['groups']) && is_array($params['groups']))
            {
                foreach ($params['groups'] as $grp)
                    $dbh->Query("insert into ic_document_group_mem(document_id, group_id) values('$docid', '$grp');");
            }

            $dbh->Query("delete from ic_document_relation_mem where document_id='$docid'");
            if(isset($params['related_documents']) && is_array($params['related_documents']))
            {
                foreach ($params['related_documents'] as $doc)
                    $dbh->Query("insert into ic_document_relation_mem(document_id, related_id) values('$docid', '$doc');");
            }

            if(isset($params['video_file_id_uploaded']) && $params['video_file_id_uploaded']) // New file was uploaded
            {
                // Move temp file
                $path = "/System/InfoCenter/docs/$docid";
                $folder = $antfs->openFolder($path, true);
                if ($folder && $params['video_file_id'])
                    $antfs->moveTmpFile($params['video_file_id'], $folder);
            }

            $ret = $docid;
        }
        else
        {
            $result = $dbh->Query("insert into ic_documents(title, keywords, body, video_file_id) 
                                     values('".$dbh->Escape($title)."', '".$dbh->Escape($keywords)."', 
                                     '".$dbh->Escape($body)."', ".$dbh->EscapeNumber($videoFileId).");
                                     select currval('ic_documents_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
            {
                $row = $dbh->GetNextRow($result, 0);

                $ret = $row['id'];

                if(isset($params['groups']) && is_array($params['groups']))
                {
                    foreach ($params['groups'] as $grp)
                        $dbh->Query("insert into ic_document_group_mem(document_id, group_id) values('$ret', '$grp');");
                }

                if(isset($params['related_documents']) && is_array($params['related_documents']))
                {
                    foreach ($params['related_documents'] as $doc)
                        $dbh->Query("insert into ic_document_relation_mem(document_id, related_id) values('$ret', '$doc');");
                }

                if(isset($params['video_file_id_uploaded']) && $params['video_file_id_uploaded']) // New file was uploaded
                {
                    // Move temp file
                    $path = "/System/InfoCenter/docs/$docid";
                    $folder = $antfs->openFolder($path, true);
                    if ($folder && $params['video_file_id'])
                        $antfs->moveTmpFile($params['video_file_id'], $folder);
                }
            }
        }

        if ($ret)
        {
            $obj = new CAntObject($dbh, "infocenter_document", $ret, $this->user);
            $obj->clearCache();
            unset($obj);
            $obj = new CAntObject($dbh, "infocenter_document", $ret, $this->user);
            $obj->save();
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Delete Document
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function deleteDocument($params)
    {
        $dbh = $this->ant->dbh;
        
        $docid = $params['docid'];
        if ($docid)
        {
            $dbh->Query("delete from ic_documents where id='$docid'");
            $ret = $docid;
        }
        else
            $ret = array("error"=>"docid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Document Get Title
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function documentGetTitle($params)
    {
        $dbh = $this->ant->dbh;
        
        $docid = $params['docid'];
        if (is_numeric($docid))
        {
            $result = $dbh->Query("select title from ic_documents where id='$docid'");
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "title");
            else
                $ret = -1;
        }
        else
            $ret = array("error"=>"docid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
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
            $dbh->Query("update ic_groups set color='$color' where id='$gid'");
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
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupRename($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $name = stripslashes(rawurldecode($params['name']));

        if ($gid && $name)
        {
            $dbh->Query("update ic_groups set name='".$dbh->Escape($name)."' where id='$gid'");
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
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];

        if ($gid)
        {
            ic_GroupDelete($dbh, $gid);
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
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh;
        $pgid = null;
        
        if(isset($params['pgid']))
            $pgid = $params['pgid'];
        
        $name = stripslashes(rawurldecode($params['name']));
        $color = stripslashes(rawurldecode($params['color']));

        if ($name && $color)
        {
            $query = "insert into ic_groups(parent_id, name, color)
                      values(" . $dbh->EscapeNumber($pgid) . ", '".$dbh->Escape($name)."', '".$dbh->Escape($color)."');
                      select currval('ic_groups_id_seq') as id;";
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
            $ret = array("error"=>"name and color are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
}
