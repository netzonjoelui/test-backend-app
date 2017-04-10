<?php
/**
 * Contact application actions
 */
require_once("contacts/contact_functions.awp");

/**
 * Class for controlling Contact functions
 */
class ContactController extends Controller
{    
    /**
     * get the contact name
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function contactGetName($params)
    {        
        if ($params['cid'])
            $ret = ContactGetName($this->ant->dbh, $params['cid']);
        else
            $ret = array("error"=>"name is a required param");

        $this->sendOutputJson($ret);        
        return $ret;
    }
    
    /**
     * Sync the customers
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function syncCustomers($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))        // Update specific event
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $olist->processFormConditions($params);
            $olist->getObjects();
            $num = $olist->getNumObjects();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $olist->getObject($i);
                if ($obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->getValue("user_id"))?true:false))
                {
                    CustSyncContact($dbh, $this->user->id, NULL, $obj->id, "create_customer");
                }
            }
            $ret = 1;
        }
        else
            $ret = array("error"=>"obj_type is a required param");

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
        $gid = $params['gid'];
        $color = $params['color'];

        if ($gid && $color)
        {
            $this->ant->dbh->Query("update contacts_personal_labels set color='$color' where id='$gid'");
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
        $gid = $params['gid'];
        $name = rawurldecode($params['name']);

        if ($gid && $name)
        {
            $this->ant->dbh->Query("update contacts_personal_labels set name='".$this->ant->dbh->Escape($name)."' where id='$gid'");
            $ret = $name;
        }
        else
            $ret = array("error"=>"gid and name are required params");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Group delete
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupDelete($params)
    {
        $gid = $params['gid'];

        if ($gid)
        {
            $this->ant->dbh->Query("delete from contacts_personal_labels where id='$gid'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid and name are required params");

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
            $query = "insert into contacts_personal_labels(parent_id, name, color, user_id) 
                      values(" . $dbh->EscapeNumber($pgid) . ", '".$dbh->Escape($name)."', '".$dbh->Escape($color)."', '" . $this->user->id . "');
                      select currval('contacts_personal_labes_id_seq') as id;";
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
    
    /**
     * Group Delete Share
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function groupDeleteShare($params)
    {
        $gid = $params['gid'];

        if ($gid)
        {
            $this->ant->dbh->Query("delete from contacts_personal_label_share where label_id='$gid' and user_id='" . $this->user->id . "'");
            $ret = $gid;
        }
        else
            $ret = array("error"=>"gid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
     * Get the contacts of the user
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getUserContactsEmail($params)
    {
        $ret = array();
        
        $dbh = $this->ant->dbh;
        $objList = new CAntObjectList($dbh, "contact_personal", $this->user);
        $objList->pullMinFields = array("first_name", "last_name", "company", "email", "email2", "email_spouse");
        $objList->addCondition("and", "user_id", "is_equal", $this->user->id);
		if ($params['search'])
		{
			$objList->addCondition("and", "email", "contains", $params['search']);
			$objList->addCondition("or", "email2", "contains", $params['search']);
			$objList->addCondition("or", "first_name", "contains", $params['search']);
			$objList->addCondition("or", "last_name", "contains", $params['search']);
		}
		/*
		if ($params['search'])
        	$objList->addConditionText($params['search']);
		 */
		$objList->forceFullTextOnly = true;
        $objList->getObjects();
        $num = $objList->getNumObjects();
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $objList->getObjectMin($i);
            
			$name = "";

			if ($obj['first_name'] || $obj['last_name'])
			{
				$name = "\"" . $obj['first_name'] ." ". $obj['last_name']."\" ";
			}
			else if ($obj['company'])
			{
				$name = "\"" . $obj['company'] ."\" ";
			}

            if ($obj['email'])
                $ret[] = $name."<".$obj['email'].">";
            
            if ($obj['email2']) 
                $ret[] = $name."<".$obj['email2'].">";
            
            if ($obj['email_spouse'])
                $ret[] = $name."<".$obj['email_spouse'].">";
        }

		// Add users
        $objList = new CAntObjectList($dbh, "user", $this->user);
        $objList->pullMinFields = array("full_name", "email");
        $objList->addCondition("and", "active", "is_equal", "t");
        $objList->addCondition("and", "id", "is_greater", 0);
		if ($params['search'])
		{
			$objList->addCondition("and", "email", "contains", $params['search']);
			$objList->addCondition("or", "email2", "contains", $params['search']);
			$objList->addCondition("or", "full_name", "contains", $params['search']);
		}
		/*
		if ($params['search'])
        	$objList->addConditionText($params['search']);
		*/
		$objList->forceFullTextOnly = true;
        $objList->getObjects();
        $num = $objList->getNumObjects();
        for ($i = 0; $i < $num; $i++)
        {
			$obj = $objList->getObjectMin($i);

			if ($obj['email'])
                $ret[] = '"' . $obj['full_name'] . '"' . " <".$obj['email'].">";

		}
        
        $ret = array_unique($ret);
        
        $this->sendOutputJson($ret);
        return $ret;
    }
}
