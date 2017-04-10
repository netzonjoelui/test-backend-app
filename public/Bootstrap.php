<?php
 /**
 * Application boostrap used by all new public code
 */
namespace NetricPublic;

require_once(__DIR__ "/../init_application.php");

use Zend\Loader\StandardAutoloader;
use RuntimeException;
use Netric;

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
	protected static $account;

	public static function init()
    {
    	global $application, $account; // from ../init_application.php

        // Initialize account
        static::$account = $account;
    }

	/*
    protected static $account;

    public static function init()
    {
        static::initAutoloader();

        // Initialize Netric Application and Account
        // ------------------------------------------------
        $config = new \Netric\Config();

        // Initialize application
        $application = new \Netric\Application($config);

        // Initialize account
        static::$account = $application->getAccount();
    }

    public static function getAccount()
    {
        return static::$account;
    }

    protected static function initAutoloader()
    {
        $zf2Path = false;

        if (is_dir('lib/ZF2/library')) {
            $zf2Path = 'lib/ZF2/library';
        } elseif (getenv('ZF2_PATH')) {      // Support for ZF2_PATH environment variable or git submodule
            $zf2Path = getenv('ZF2_PATH');
        } elseif (get_cfg_var('zf2_path')) { // Support for zf2_path directive value
            $zf2Path = get_cfg_var('zf2_path');
        }

        if ($zf2Path) {
            include $zf2Path . '/Zend/Loader/StandardAutoloader.php';
            
            if (!class_exists('Zend\Loader\StandardAutoloader')) {
                throw new RuntimeException('Unable to load ZF2. Define a ZF2_PATH environment variable.');
            }
            
            $autoLoader = new StandardAutoloader(array(

                'namespaces' => array(
                    'Netric' => __DIR__ . '/../lib/Netric',
                    'Zend' => $zf2Path,
                    __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                ),
                'fallback_autoloader' => true,
            ));
            $autoLoader->register();
        }
        else {
            throw new RuntimeException('Unable to load ZF2. Define a ZF2_PATH environment variable.');
        }

    }

    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }
        return $dir . '/' . $path;
    }
    */
}

Bootstrap::init();