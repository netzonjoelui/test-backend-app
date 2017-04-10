<?php
/*
 * Generic error message used for non exception type errors (expected errors)
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Error;

/**
 * An object that is aware of and can return infor on the last error that occurred
 */
class Error
{
    /**
     * An optional unique error code - otherwise it's just error
     *
     * @var string
     */
    private $code = "error";

    /**
     * A description of the error
     *
     * @var string
     */
    private $message = "";

    /**
     * Construct a new error object
     *
     * @param $message
     * @param $code
     */
    public function __construct($message, $code = null)
    {
        if ($code !== null)
        {
            $this->code = $code;
        }

        $this->message = $message;
    }

    /**
     * Get the description of this error
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the error code if set
     *
     * @return null|string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the message for this error
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }
}