<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Config;

use Netric\Config\Exception;
use ArrayAccess;

/**
 * Simple configuration object
 */
class Config implements ArrayAccess
{
    /**
     * Configuration properties
     *
     * @var array
     */
    private $properties = array();

    /**
     * Construct Config object and convert array to object properties
     *
     * @param array $aConfig Configuration source
     */
    public function __construct(array $aConfig)
    {
        // Set properties from the array
        $this->fromArray($aConfig);
    }

    /**
     * Magic method to get config property by name
     *
     * @param string $name The name of the property to get
     * @return string|int|bool|Config The value of the property or null of the property does not exist
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Do not allow anyone to set properties via $config->name
     *
     * @param string $name The name of the property to set
     * @param mixed $value The attempted value being set
     * @throws Exception\ViolatedReadOnlyException
     */
    public function __set($name, $value)
    {
        $exceptionMessage = "Setting properties is not allowed after initial setup";
        throw new Exception\ViolatedReadOnlyException($exceptionMessage);
    }

    /**
     * Get a property by name
     *
     * @param string $name The name of the property to get
     * @param mixed $default Optional default value to use if property has not been set
     * @return mixed The value of the property or $default of the property does not exist
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else {
            return $default;
        }
    }

    /**
     * isset() overloading
     *
     * @param string $sName
     * @return bool
     */
    public function __isset($sName)
    {
        return isset($this->properties[$sName]);
    }

    /**
     * Set a config property
     *
     * This is private to fake read-only behavior in properties.
     * A global config should stay unchanged once constructed to discourage
     * developers from using it as a global registry to pass values
     * from module to module.
     *
     * @param $sName
     * @param $mValue
     */
    private function set($sName, $mValue)
    {
        $this->properties[$sName] = $mValue;
    }

    /**
     * Set all properties from an associative array
     *
     * @param array $data Associative array of properties to set
     * @throws \Netric\Config\Exception\RuntimeException If the data array is found to be invalid
     */
    private function fromArray(array $data)
    {
        foreach ($data as $keyName=>$keyValue) {

            /**
             * If this is a nested config, then initialize a new Config class for a nested property
             * Example: $config->database->host
             */
            if (is_array($keyValue)) {
                $keyValue = new Config($keyValue);
            } else if (is_object($keyValue)) {
                throw new Exception\RuntimeException($keyName . " is invalid because a config variable cannot be an Object");
            }

            // Set the local property
            $this->set($keyName, $keyValue);
        }
    }

    /**
     * Return an associative array of the stored data.
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $data  = $this->properties;

        /** @var self $value */
        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Set a property at a specific offset
     *
     * ArrayAccess interface function. Since the config is designed
     * to be read-only after it has been constructed (to keep people from
     * using it as a global registry), we throw an exception if they
     * try to set a property like this.
     *
     * @param string $offset The name of the property to set
     * @param mixed $value The value to set
     * @throws Exception\ViolatedReadOnlyException
     */
    public function offsetSet($offset, $value)
    {
        $exceptionMessage = "Setting properties is not allowed after construction";
        throw new Exception\ViolatedReadOnlyException($exceptionMessage);
    }

    /**
     * Check if a property exists
     *
     * ArrayAccess interface function
     *
     * @param string $offset The name of the property to set
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Unset a value - not allowed since this is read-only
     *
     * ArrayAccess interface function
     *
     * @param string $offset The name of the property to set
     */
    public function offsetUnset($offset)
    {
        $exceptionMessage = "Unsetting properties is not allowed after construction";
        throw new Exception\ViolatedReadOnlyException($exceptionMessage);
    }

    /**
     * Get a property by name
     *
     * ArrayAccess interface function
     *
     * @param string $offset The name of the property to set
     * @return mixed The value of the property
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set configuration values
     *
     * We traverse through the values and set them in order
     *
     * @param array $values The values to set
     */
    public function setValues($values)
    {
        $this->fromArray($values);
    }
}
