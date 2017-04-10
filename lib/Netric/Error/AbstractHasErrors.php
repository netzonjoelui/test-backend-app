<?php
/*
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015-206 Aereus
 */
namespace Netric\Error;

/**
 * Generic functions used for a class that has errors
 */
abstract class AbstractHasErrors implements ErrorAwareInterface
{
    /**
     * Array of errors for this object
     *
     * @var Error[]
     */
    protected $errors = [];

    /**
     * Get the last error
     *
     * @return Error|null
     */
    public function getLastError()
    {
        return (count($this->errors)) ? array_pop($this->errors) : null;
    }

    /**
     * Get all errors
     *
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add array of errors to current array
     *
     * @param Error[] $errors
     */
    public function mergeErrors(array $errors)
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    /**
     * Construct and add a new Error from a message
     * @param $message
     */
    public function addErrorFromMessage($message)
    {
        $this->errors[] = new Error($message);
    }
}