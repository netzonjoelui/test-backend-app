<?php
/**
 * Manage entity forms
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\Validator;

use Netric\Entity\Entity;
use Netric\Error;

/**
 * Class for validating entities
 *
 * This is mostly used in the DataMapperAbstract::save function
 * to validate basic conditions are correct before writing.
 *
 * @package Netric\Entity
 */
class EntityValidator implements Error\ErrorAwareInterface
{
    /**
     * Validation errors
     *
     * @var Error\Error[]
     */
    private $errors = array();

    /**
     * Setup the validator service
     */
    public function __construct()
    {
    }

    /**
     * Determine if an entity is valid by checking various conditions
     *
     * Conditions include:
     *  - uname is unique
     *  - All required fields have values (?)
     *  - If a field is marked as 'unique' then make sure it is unique combined with parentId
     *
     * @param Entity $entity
     * @return bool
     */
    public function isValid(Entity $entity)
    {
        return true;
    }

    /**
     * Get the last error logged
     *
     * @return Error\Error
     */
    public function getLastError()
    {
        return $this->errors[count($this->errors) - 1];
    }

    /**
     * Get all errors
     *
     * @return Error\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
