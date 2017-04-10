<?php
/**
* Aereus API Library
*
* This will search any string in api objects local store
*
* @category  AntApi
* @package   AntApi_Searcher
* @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
*/

require_once(ANTAPI_ROOT.'/antapi/Searcher/DocType.php');

/**
* Class for authenticating user
*/
class AntApi_Searcher
{
    /**
    * ANT server
    *
    * @var string
    */
    private $server;

    /**
    * Valid ANT user name
    *
    * @var string
    */
    private $username;

    /**
    * ANT user password
    *
    * @var string
    */
    private $password;

    /**
    * AntApi object
    *
    * @var AntApi
    */
    private $antapi = null;

    /**
    * List of doctype objects
    *
    * @var array
    */
    private $docTypes = array();
    
    /**
    * Localstore to be used.
    * Usually used for localstore PhpUnit Test
    *
    * @var Object
    */
    public $storeType = null;

    /**
    * Class constructor
    *
    * @param string $server    ANT server name
    * @param string $username    A Valid ANT user name with appropriate permissions
    * @param string $password    ANT user password
    */
    function __construct($server, $username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->server = $server;

        $this->antapi = AntApi::getInstance($server, $username, $password);
    }

    /**
    * Gets the instance of object list
    *
    * @param string $type       the name of the type of object. e.g. "customer" or "lead"    
    */
    public function addType($type)
    {
        if(!isset($this->docTypes[$type]))
            $this->docTypes[$type] = new AntApi_Searcher_DocType($type);
        
        return $this->docTypes[$type];
    }

    /**
    * Adds filter to the object list
    * 
    * @param string $searchString       Word string to be searched
    * @param integer $pageNum           the page for pagination
    */
    public function query($searchString, $pageNum=0)
    {
        $ret = array();
        foreach($this->docTypes as $type=>$obj) // Loop all doctypes that have been set
        {
            $objList = $this->antapi->getObjectList($type); // get ant instance of each doctype object list
            
            if($this->storeType)
                $objList->setStoreSource($this->storeType);
            
            foreach($obj->conditions as $key=>$cond) // Add conditions to the object list
                $objList->addCondition($cond['blogic'], $cond['field'], $cond['operator'], $cond['value']);
                
            $titleField = "name";
            if(!empty($searchString))
            {
                $objList->conditionText = $searchString;
                                
                switch($type)
                {
                    case "content_feed_post":
                        //$objList->addCondition("and", "title", "contains", $searchString);                        
                        $titleField = "title";
                        break;                    
                    default:                        
                        break;
                }
            }
            
            $num = $objList->getObjects($pageNum);
            
            for ($i = 0; $i < $num; $i++)
            {
                $min = $objList->getObject($i);
                $url = $min->getValue("uname");
                
                if($obj->urlBase)
                    $url = $obj->urlBase . "/$url";                
                
                $ret[$i] = array("title" => $min->getValue($titleField), "type" => $type, "url" => $url, "date" => $min->getValue("time_entered"));
                
                // get additional fields
                foreach($obj->fields as $fldKey=>$field)
                {
                    $ret[$i][$field['label']] = $min->getValue($field['name'], $field['foreign']);
                }
            }
        }
        
        return $ret;
    }
}