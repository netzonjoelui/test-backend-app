<?php
/**
 * MongoDB Adapter
 *
 * @category Aereus
 * @category Aereus_Adapter
 * @copyright Copyright (c) 2005-2012 Aereus Corporation. (http://www.aereus.com)
 */

abstract class Aereus_Zf_MongoDBModel
{
    
    private $_db;
    /**
     * Choose collection
     * @var type 
     */
    private $_collectionConnect;
    
    /**
     * Converted Document into array could be performed by query or instance
     * @var array
     */
    protected $_attributes;
    
    /**
     * Query criteria
     * @param array
     */
    protected $_query;
    
    /**
     * Add limit when performing query
     * @var int 
     */
    protected $_limit;
    
    /**
     *
     * @var type 
     */
    protected $_orderBy;
    
    /**
     * used by orderBy() method of this class for increasing order
     */
    const ORDER_BY_ASC = 1;
    
    /**
     * used by orderBy() method of this class for decreasing order
     */
    const ORDER_BY_DESC = -1;
    
    /**
     * 
     * @param array $attributes 
     */
    public function __construct(array $attributes = array()) 
    {
        $this->_attributes = $attributes;
        // init to empty array
        $this->_query = array();
        
        $this->_db = Zend_Registry::get('mongodb');
        if (!$this->_db)
            throw new Exception('Database not found!');
        
        $this->_collectionConnect = $this->_db->selectCollection($this->collectionName());
    }
    
    /**
     *
     * @return sub class 
     */
    public static function getInstance($className)
    {
        return new $className();
    }
    
    /**
     *
     */
    public function command($command)
    {
        return $this->_db->command($command);
    }
    
    /**
     *
     * @return MongoCursor 
     */
    public function db()
    {
        return $this->_db;
    }
    
    /**
     * Mongo collection adapter
     * @return type 
     */
    public function mongo()
    {
        return $this->_collectionConnect;
    }
    
    /**
     * 
     * @param type $id string 
     */
    public function findById($id)
    {
        $data = $this->mongo()->findOne(array('_id' => new MongoId($id)));
        return $data ? (new $this($data)) : NULL;
    }
    
    /**
     * As for now, there will be 3 validation types: required, range and valid sets of attributes
     * @return array 
     */
    protected function rules()
    {
        return array();
    }
    
    /**
     * Validates the data before saving to be sure what data should be saved
     * @return boolean 
     */
    public function validate()
    {
        $rules = $this->rules();
        
        foreach ($rules as $rule) {

            $funcName = '_validate' . $rule[0];
            
            if (!$this->$funcName($rule))
                return false;
        }
        
        return true;
    }
    
    /**
     * Before saving, it validates the required attributes. Each of it must not
     * contains an empty value and attribute must be existed.
     * 
     * @param type $string
     * @return boolean
     * @throws Exception 
     */
    private function _validateRequired(array $data)
    {
        $attributes = explode(', ', $data[1]);
        
        foreach ($attributes as $attr) {
            $attrValue = $this->_attributes[$attr];
           
            // checking empty string with whitespace/s
            if (is_string($attrValue) && ctype_space($attrValue))
                $attrValue = '';
            
            if (empty($attrValue))
                throw new Exception('Required attribute \'' . $attr . '\' not found!');
        }
        return true;
    }
    
    /**
     * Validates an attribute which must exist from a range of data
     * 
     * @param array $data
     * @return boolean
     * @throws Exception 
     */
    private function _validateRange(array $data)
    {
        $attributes = explode(', ', $data[1]);
        
        $rangeOfData = $data[2];

        foreach ($attributes as $attr) {
            if (!in_array($this->_attributes[$attr], $rangeOfData))
                throw new Exception('Data not in the range: Attribute name \'' . $attr . '\'');
        }
        return true;
    }
    
    /**
     * All attributes in $this->_attributes must all existed on
     * the given attribute names
     * 
     * @param type $attributes
     * @return boolean 
     */
    private function _validateAttributes(array $data)
    {
        $attributes = explode(', ', $data[1]);
        
        foreach ($this->_attributes as $attr => $value) {
            if (!in_array($attr, $attributes))
                throw new Exception($attr . '\' is not a valid attribute');
        }
        return true;
    }
    
    /**
     * Get attribute value provided by a key
     * 
     * @param type $attr
     * @return type 
     */
    public function getAttribute($attr)
    {
        return $this->_attributes[$attr];
    }
    
    /**
     * set attribute value provided by a key
     * 
     * @param type $attr
     * @param type $value 
     */
    public function setAttribute($attr, $value)
    {
        $this->_attributes[$attr] = $value;
    }
    
    /**
     * Runs validation first then save if succesful
     * 
     * @return boolean 
     */
    public function save()
    {
        if (empty($this->_attributes))
            return false;
        
        if (!$this->validate())
            return false;
        
        return (bool) $this->mongo()->save($this->_attributes);
    }
    
    /**
     * Directly deletes data from mongodb
     * @return boolean 
     */
    public function remove()
    {
        if (!array_key_exists('_id', $this->_attributes))
            return false;
        
        return (bool) $this->mongo()->remove($this->_attributes);
    }
    
    /**
     * Usually used by findAll method to convert array of query results into objects
     * @param type $dataArray
     * @return \this 
     */
    public function setArrayOfdata($dataArray)
    {
        $results = array();
        foreach ($dataArray as $data)
            $results[] = new $this($data);
        return $results;
    }
    
    /**
     * 
     * @return type 
     */
    public function findAll()
    {
        if (!is_array($this->_query))
            $this->_query = array();

        $cursorData = $this->mongo()->find($this->_query);
        
        // perform limit operation
        if ($this->_limit)
            $cursorData->limit($this->_limit);
        
        // perform sorting
        if ($this->_orderBy)
            $cursorData->sort($this->_orderBy);
        
        $dataArray = iterator_to_array($cursorData);
        
        return $this->setArrayOfdata($dataArray);
    }
    
    /**
     * 
     * @return inhereted object 
     */
    public function find()
    {
        if (!is_array($this->_query))
            $this->_query = array();
        
        $data = $this->mongo()->findOne($this->_query);

        return $data ? new $this($data) : NULL;
    }
    
    /**
     * 
     * @return string
     */
    public function getId()
    {
        if (!array_key_exists('_id', $this->_attributes))
            return null;
                
        return $this->_attributes['_id'];
    }
    
    /**
     * Get stored attributes
     * 
     * @return array 
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }
    
    /**
     * Replace/set attributes
     * 
     * @param array $attr 
     */
    public function setAttributes(array $attr)
    {
        $this->_attributes = $attr;
    }
    
    /**
     *
     * @param int $limit
     * @return Aereus_Zf_MongoDBModel 
     */
    public function limit($limit)
    {
        $this->_limit = (int) $limit;
        return $this;
    }
    
    /**
     * 
     * @param string $name
     * @param int $orderType
     * @return Aereus_Zf_MongoDBModel 
     */
    public function orderBy($name, $orderType = self::ORDER_BY_ASC)
    {
        $this->_orderBy = array($name => $orderType);
        return $this;
    }
}
?>
