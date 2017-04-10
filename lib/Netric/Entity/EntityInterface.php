<?php
/**
 * All entities/objects should implement this interface
 */
namespace Netric\Entity;

interface EntityInterface
{
    /**
     * Get the object type of this object
     * 
     * @return string
     */
    public function getObjType();

	/**
	 * Get unique id of this object
	 */
	public function getId();
    
    /**
	 * Set the unique id of this object
     * 
     * @param string $id The unique id of this object instance
	 */
	public function setId($id);

	/**
	 * Get definition
	 *
	 * @return EntityDefinition
	 */
	public function getDefinition();
    
    /**
     * Return either the string or an array of values if *_multi
     * 
     * @param string $strname
     * @return string|array
     */
    public function getValue($strname);
    
    /**
     * Get fkey name for key/value field types like fkey and fkeyMulti
     * 
     * @param string $strName The name of the field to pull
	 * @param string $id If set, get the label for the id
     * @return string
     */
    public function getValueName($strName, $id=null);

    /**
     * Get fkey name array for key/value field types like fkey and fkeyMulti
     * 
     * @param string $strName The name of the field to pull
     * @return array(array("id"=>"name"))
     */
    public function getValueNames($strName);
    
    /**
     * Set a field value for this object
     * 
     * @param string $strName
     * @param mixed $value
     * @param string $valueName If this is an object or fkey then cache the foreign value
     */
    public function setValue($strName, $value, $valueName=null);

    /**
     * Add a multi-value entry to the *_multi type field
     * 
     * @param string $strName
     * @param string|int $value
     * @param string $valueName Optional value name if $value is a key
     */
    public function addMultiValue($strName, $value, $valueName="");
    
    /**
     * Remove a value from a *_multi type field
     * 
     * @param string $strName
     * @param string|int $value
     */
    public function removeMultiValue($strName, $value);

    /**
	 * Set values from array
	 *
	 * @param array $data Associative array of values
	 */
	public function fromArray($data);

    /**
	 * Get all values and return them as an array
	 *
	 * @return array Associative array of all fields in array(field_name=>value) format
	 */
	public function toArray();

	/**
	 * Save this object to a datamapper
	 *
	 * @param Entity_DataMapperInterface $dm The datamapper for saving data
	 * @param AntUser $user The user who is saving this object
	 */
	//public function save(Entity_DataMapperInterface $dm, $user);

	/**
	 * Callback function used for derrived subclasses
	 *
	 * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
	 */
	public function onBeforeSave(\Netric\ServiceManager\AccountServiceManagerInterface $sm);

	/**
	 * Callback function used for derrived subclasses
	 *
	 * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
	 */
	public function onAfterSave(\Netric\ServiceManager\AccountServiceManagerInterface $sm);

	/**
	 * Check if a field value changed since created or opened
	 *
	 * @param string $checkfield The field name
	 * @return bool true if it is dirty, false if unchanged
	 */
	public function fieldValueChanged($checkfield);

	/**
	 * Reset is dirty indicating no changes need to be saved
	 */
	public function resetIsDirty();

	/**
	 * Check if the object values have changed
	 *
	 * @return true if object has been edited, false if not
	 */
	public function isDirty();

	/**
	 * Get name of this object based on common name fields
	 *
	 * @return string The name/label of this object
	 */
	public function getName();
	
	/**
	 * Check if the deleted flag is set for this object
	 *
	 * @return bool
	 */
	public function isDeleted();
	
	/**
	 * Set defaults for a field given an event
	 *
	 * @param string $event The event we are firing
	 * @param AntUser $user Optional current user for default variables
	 */
	public function setFieldsDefault($event, $user=null);

	/**
	 * Get the local recurrence pattern
	 *
	 * @return Recurrence\RecurrencePattern
	 */
	public function getRecurrencePattern();
}
