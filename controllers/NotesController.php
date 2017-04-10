<?php
/**
 * Notes application actions
 */
require_once("userfiles/file_functions.awp");
require_once("lib/CAntFs.awp");

/**
 * Class for controlling customer functions
 */
class NotesController extends Controller
{    
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
            $dbh->Query("update user_notes_categories set color='$color' where id='$gid'");
            $ret = $color;
        }
        else
            $ret = array("error"=>"gid and color are required params");

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
            $dbh->Query("delete from user_notes_categories where id='$gid'");
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
        
        $name = rawurldecode($params['name']);
        $color = rawurldecode($params['color']);

        if ($name && $color)
        {
            $query = "insert into user_notes_categories(parent_id, name, color, user_id)
                      values(" . $dbh->EscapeNumber($pgid) . ", '".$dbh->Escape($name)."', '".$dbh->Escape($color)."', '" . $this->user->id . "');
                      select currval('user_notes_categories_id_seq') as id;";
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
