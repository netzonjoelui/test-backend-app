<?php
/*
 * Interface definition for an error
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Error;

/**
 * An object that is aware of and can return infor on the last error that occurred
 */
interface ErrorAwareInterface
{
    /**
     * Get the last error thrown in an object or module
     *
     * @return Error
     */
    public function getLastError();

    /**
     * Get all errors
     *
     * @return Error[]
     */
    public function getErrors();
}