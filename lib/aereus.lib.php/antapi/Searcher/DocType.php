<?php
/**
* Used to gather information and filters for object list search
*
* @category  AntApi
* @package   Searcher_DocType
* @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
*/
class AntApi_Searcher_DocType
{
    /**
    * Set to use the prefix 'uname' in the 'url' param
    *
    * @var String
    */
    public $urlBase = null;

    /**
    * Type of the doc type object
    *
    * @var String
    */
    var $type = null;

    /**
    * Array of conditions
    *
    * @var String
    */
    var $conditions = array();
    
    /**
    * Array of fields
    *
    * @var String
    */
    var $fields = array();

    /**
    * Class constructor
    *     
    */
    function __construct($type) 
    {
        $this->type = $type;
    }

    /**
    * Sets the filter to be used when searching the object list
    *
    * $param string $blogic        "and" / "or"
    * $param string $name          filed name
    * $param string $operator      operator
    * $param string $value         value to test for     
    */
    public function addFilter($blogic, $fieldName, $operator, $value)
    {
        $this->conditions[] = array("blogic"=>$blogic, "field"=>$fieldName, "operator"=>$operator, "value"=>$value);
    }
    
    /**
    * Adds a new field to be included in the query
    *    
    * $param string $fieldName      name of the field to be included in the query
    * $param string $label          label of the field
    * $param boolean $foreign       determines whether to get a foreign value    
    */
    public function addField($fieldName, $label="", $foreign=false)
    {
        if(empty($label))
            $label = $fieldName;
            
        $this->fields[] = array("name" => $fieldName, "foreign" => $foreign, "label" => $label);
    }
}