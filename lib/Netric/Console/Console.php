<?php
/**
 * Console interaction class
 *
 * @author      joe, sky.stebnicki@aereus.com
 * @copyright   Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Console;

/**
 * A static, utility class for interacting with Console environment.
 */
class Console
{   
    /**
     * Flag set if we are in a console environment
     *
     * @var bool
     */
    protected static $isConsole;

    /**
     * Check if currently running under MS Windows
     *
     * @see http://stackoverflow.com/questions/738823/possible-values-for-php-os
     * @return bool
     */
    public static function isWindows()
    {
        return
            (defined('PHP_OS') && (substr_compare(PHP_OS, 'win', 0, 3, true) === 0)) ||
            (getenv('OS') != false && substr_compare(getenv('OS'), 'windows', 0, 7, true))
        ;
    }

    /**
     * Check if running under MS Windows Ansicon
     *
     * @return bool
     */
    public static function isAnsicon()
    {
        return getenv('ANSICON') !== false;
    }

    /**
     * Check if running in a console environment (CLI)
     *
     * By default, returns value of PHP_SAPI global constant. If $isConsole is
     * set, and a boolean value, that value will be returned.
     *
     * @return bool
     */
    public static function isConsole()
    {
        if (null === static::$isConsole) 
        {
            static::$isConsole = (PHP_SAPI == 'cli');
        }

        return static::$isConsole;
    }

    /**
     * Override the "is console environment" flag
     *
     * @param  null|bool $flag
     */
    public static function overrideIsConsole($flag)
    {
        if (null != $flag) 
        {
            $flag = (bool) $flag;
        }

        static::$isConsole = $flag;
    }
}
