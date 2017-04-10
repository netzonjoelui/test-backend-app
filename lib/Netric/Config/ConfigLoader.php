<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Config;

/**
 * Construct a Config object from files
 */
class ConfigLoader
{
    /**
     * Load configuration files and merge them together into one configuration object
     *
     * Files will load in the following order and merged together:
     *
     * 1. global.php
     * 2. {$appEnv}.php
     * 3. local.php (developer overrides, should never be checked into repo)
     *
     * @param string $configPath Directory path that contains the config files
     * @param string $appEnv The name of the environment to load
     * @param array $params If set these params will be applied last after files
     * @return Config
     */
    public static function fromFolder($configPath, $appEnv="", array $params=[])
    {
        // Load and merge arrays
        $base = self::importFileArray($configPath . "/ant.ini");
        $baseLocal = self::importFileArray($configPath . "/ant.local.ini");
        $env = self::importFileArray($configPath . "/ant." . $appEnv . ".ini");
        $envLocal = self::importFileArray($configPath . "/" . $appEnv . ".local.ini");
        $local = self::importFileArray($configPath . "/local.php");

        $merged = array_replace_recursive(
            (array) $base,
            (array) $baseLocal,
            (array) $env,
            (array) $envLocal,
            (array) $local,
            (array) $params
        );

        // Return merged config
        return new Config($merged);
    }

    /**
     * Load a configuration file and turn it into an array
     *
     * @param string $filePath The path of the config file to load
     * @return array
     */
    public static function importFileArray($filePath)
    {
        if (file_exists($filePath)) {

            // Load the data pending on the type
            $path_parts = pathinfo($filePath);

            switch (strtolower($path_parts['extension'])) {
                case 'ini':
                    $data = parse_ini_file($filePath, true); // make sure we process sections
                    break;
                case 'php':
                    $data = include($filePath);
                    break;
                default:
                    throw new Exception\RuntimeException("$filePath is not a supported file type");
            }

            // Throw an exception if the returned value is not an array
            if (!is_array($data)) {
                throw new Exception\RuntimeException("$filePath did not return an array");
            }

            return $data;

        } else {
            // Return an empty array to merge
            return [];
        }
    }
}
