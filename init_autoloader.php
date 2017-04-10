<?php
/**
 * Make sure that the autoloader is setup to load all dependencies and the netric namespace
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */


// Autoload composer dependencies
$loader = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $loader = include __DIR__ . '/vendor/autoload.php';
} else {
    throw new \RuntimeException("Please run composer install to install required dependencies");
}

/*
// Load the zend framework
$zf2Path = false;
if (!class_exists('Zend\Loader\StandardAutoloader'))
{
    // Support for ZF2_PATH environment variable
    if (getenv('ZF2_PATH'))
    {
        $zf2Path = getenv('ZF2_PATH');
    }
    else if (get_cfg_var('zf2_path'))
    {
        // Support for zf2_path ini value
        $zf2Path = get_cfg_var('zf2_path');
    }

    // Include autoloader
    if ($zf2Path)
    {
        include $zf2Path . '/Zend/Loader/StandardAutoloader.php';
    }
}
*/
/*
if (isset($loader))
{
    $loader->add('Netric', __DIR__ . '/lib/Netric');
    $loader->add('NetricPublic', __DIR__ . '/public');
    if ($zf2Path) {
        $loader->add('Zend', $zf2Path);
    }
}
else
{
*/

$autoLoader = new Zend\Loader\StandardAutoloader(array(
    /*
    'prefixes' => array(
        'MyVendor' => __DIR__ . '/MyVendor',
    ),
    */
    /*
    'namespaces' => array(
        'Netric' => ,
        'NetricPublic' => __DIR__ . '/public',
        //'Elastica' => __DIR__ . '/lib/Elastica',
        'Zend' => $zf2Path,
    ),
    */
    'fallback_autoloader' => true,
));
$autoLoader->registerNamespace('Netric', __DIR__ . '/lib/Netric');
$autoLoader->registerNamespace('NetricPublic', __DIR__ . '/public');

/*
if ($zf2Path)
    $autoLoader->registerNamespace('Zend', $zf2Path);
*/

$autoLoader->register();
//}

if (!class_exists('Zend\Loader\StandardAutoloader')) {
    throw new RuntimeException('Unable to load ZF2. Define a ZF2_PATH environment variable.');
}