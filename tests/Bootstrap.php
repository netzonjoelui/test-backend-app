<?php
namespace NetricTest;

// Get application autoloader
include("../init_autoloader.php");

use Zend\Loader\StandardAutoloader;
use RuntimeException;
use Netric;
use Netric\Entity\ObjType\UserEntity;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
    protected static $account;

    public static function init()
    {
        static::initAutoloader();

        // Initialize Netric Application and Account
        // ------------------------------------------------
        // $config = new \Netric\Config();
        $configLoader = new \Netric\Config\ConfigLoader();
        $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";

        // Setup the new config
        $config = $configLoader->fromFolder(__DIR__ . "/../config", $applicationEnvironment);

        // Initialize application
        $application = new \Netric\Application\Application($config);

        // Set log path
        $application->getLog()->setLogPath(__DIR__ . "/tmp/netric.log");

        // Initialize account
        static::$account = $application->getAccount();

        // Get or create an administrator user so permissions are not limiting
        $user = self::$account->getUser(null, "automated_test");
        if (!$user) {
            $loader = static::$account->getServiceManager()->get("EntityLoader");
            $user = $loader->create("user");
            $user->setValue("name", "automated_test");
            $user->addMultiValue("groups", UserEntity::GROUP_ADMINISTRATORS);
            $loader->save($user);
        }

        static::$account->setCurrentUser($user);
    }

    public static function getAccount()
    {
        return static::$account;
    }

    protected static function initAutoloader()
    {
            
        $autoLoader = new StandardAutoloader(array(
            /*
            'prefixes' => array(
                'MyVendor' => __DIR__ . '/MyVendor',
            ),
            */
            'namespaces' => array(
                __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
            ),
            'fallback_autoloader' => true,
        ));
        $autoLoader->register();

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
}

Bootstrap::init();