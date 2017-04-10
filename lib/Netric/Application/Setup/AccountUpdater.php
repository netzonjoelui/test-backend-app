<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Application\Setup;

use Netric\Account\Account;
use Netric\Error\AbstractHasErrors;
use Netric\Console\BinScript;

/**
 * Run updates on an account
 */
class AccountUpdater extends AbstractHasErrors
{
    /**
     * Account we are updating
     *
     * @var Account
     */
    private $account = null;

    /**
     * The current major, minor, and points versions for an account
     *
     * @var \stdClass
     */
    private $version = null;

    /**
     * Ticker used to track last updated to
     *
     * @var \stdClass
     */
    private $updatedToVersion = null;

    /**
     * The name of the name/value table that will hold the schema version
     *
     * @var string
     */
    public $tableName = "system_registry";

    /**
     * Determine whether to execute the updates or just do a dry-run
     *
     * Set boolean value false if we need just to get the system schema file version
     *
     * @var bool
     */
    private $executeUpdates = true;

    /**
     * Root path where the update scripts can be found
     *
     * @var string
     */
    private $rootPath = "";

    /**
     * Constructor
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->version = new \stdClass();
        $this->updatedToVersion= new \stdClass();

        // Set default path
        $this->rootPath = dirname(__FILE__) . "/../../../../bin/scripts/update";

        // Get the current version from settings
        $settings = $account->getServiceManager()->get("Netric/Settings/Settings");
        $version = $settings->get("system/schema_version");

        // Set current version counter
        $parts = explode(".", $version);
        $this->version->major = (isset($parts[0])) ? intval($parts[0]) : 1;
        $this->version->minor = (isset($parts[1])) ? intval($parts[1]) : 0;
        $this->version->point = (isset($parts[2])) ? intval($parts[2]) : 0;
    }

    /**
     * Save the last updated schema version to the settings for this account
     */
    public function saveUpdatedVersion()
    {
        $updated = false;

        if ($this->updatedToVersion->major > $this->version->major)
            $updated = true;
        else if ($this->updatedToVersion->major == $this->version->major && $this->updatedToVersion->minor > $this->version->minor)
            $updated = true;
        else if ($this->updatedToVersion->major == $this->version->major && $this->updatedToVersion->minor == $this->version->minor
            && $this->updatedToVersion->point > $this->version->point)
            $updated = true;

        $newversion = $this->updatedToVersion->major.".".$this->updatedToVersion->minor.".".$this->updatedToVersion->point;

        if ($updated)
        {
            $settings = $this->account->getServiceManager()->get("Netric/Settings/Settings");
            $settings->set("system/schema_version", $newversion);
        }
    }

    /**
     * Gets the latest version of database schema from the file structure
     *
     * @return bool false on failure, true on success
     */
    public function getLatestVersion()
    {
        // Flag to make this a dry run with no actual updates performed
        $this->executeUpdates = false;

        // This will get the major, minor and point versions
        $this->runOnceUpdates();

        $versionParts[] = $this->updatedToVersion->major;
        $versionParts[] = $this->updatedToVersion->minor;
        $versionParts[] = $this->updatedToVersion->point;
        $latestVersion = implode(".", $versionParts);

        // Reset the flag
        $this->executeUpdates = true;

        // Rest updatedTo
        $this->updatedToVersion->major = 0;
        $this->updatedToVersion->minor = 0;
        $this->updatedToVersion->point = 0;

        return $latestVersion;
    }

    /**
     * Update the root path to get update scripts from
     *
     * @param string $rootPath
     */
    public function setScriptsRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * Run all updates for an account
     *
     * @return string Version in xxx.xxx.xxx format
     */
    public function runUpdates()
    {
        // Now run scripts that are set to run on every update
        $this->runAlwaysUpdates();

        // Run the one time scripts first
        $version = $this->runOnceUpdates();

        return $version;
    }

    /**
     * Run all scripts in the 'always' directory
     *
     * These run every time an update is executed
     *
     * @return bool true on sucess, false on failure
     */
    public function runAlwaysUpdates()
    {
        $updatePath = $this->rootPath . "/always";

        // Get individual update scripts
        $updates = array();
        $dir = opendir($updatePath);
        if ($dir)
        {
            while($file = readdir($dir))
            {
                if(!is_dir($updatePath."/".$file) && $file != '.' && $file != '..')
                {
                    $updates[] = $file;
                }
            }
            sort($updates);
            closedir($dir);
        }

        // Now process each of the update scripts
        $allStart = microtime(true);
        foreach ($updates as $update)
        {
            // It's possible to run through this without executing the scripts
            if (substr($update, -3) == "php" && $this->executeUpdates)
            {
                // Execute a script only on the current account
                $script = new BinScript($this->account->getApplication(), $this->account);
                $script->run($updatePath."/".$update);
            }
        }
    }

    /**
     * Run versioned update scripts that only run once then increment version
     *
     * @return string Last processed version in xxx.xxx.xxx format
     */
    public function runOnceUpdates()
    {
        $updatePath = $this->rootPath . "/once";

        // Get major version directories
        $majors = array();
        $dir = opendir($updatePath);
        if ($dir)
        {
            while($file = readdir($dir))
            {
                if(is_dir($updatePath."/".$file) && $file[0] != '.')
                {
                    if ($this->version->major <= (int) $file)
                        $majors[] = $file;
                }
            }
            sort($majors);
            closedir($dir);
        }

        // Get minor version directories
        foreach ($majors as $dir)
            $this->processMinorDirs($dir, $updatePath);

        // Save the last updated version
        $this->saveUpdatedVersion();

        return $this->updatedToVersion->major . "." . $this->updatedToVersion->minor . "." . $this->updatedToVersion->point;
    }

    /**
     * Process minor subdirectories in the major dir
     *
     * @param string $major	The name of a major directory
     * @param string $base	The base or root of the path where the major dir is located
     * @return bool true on sucess, false on failure
     */
    private function processMinorDirs($major, $base)
    {
        $path = $base."/".$major;

        // Get major version directories
        $minors = array();
        $dir_handle = opendir($path);
        if ($dir_handle)
        {
            while($file = readdir($dir_handle))
            {
                if(is_dir($path."/".$file) && $file[0] != '.')
                {
                    if (($this->version->major == (int) $major
                            && $this->version->minor <= (int) $file)
                        || ($this->version->major < (int) $major))
                    {
                        $minors[] = $file;
                    }
                }
            }
            sort($minors);
            closedir($dir_handle);
        }

        // Pull updates/points from minor dirs
        foreach ($minors as $minor)
        {
            $ret = $this->processPoints($minor, $major, $base);
            if (!$ret) // there was an error so stop processing
                return false;
        }

        return true;
    }

    /**
     * Process minor subdirectories in the major dir
     *
     * @param string $minor The minor id we are working in now
     * @param string $major The major id we are working in now
     * @param string $base The base or root of the path where the major dir is located
     */
    private function processPoints($minor, $major, $base)
    {
        $path = $base."/".$major."/".$minor;

        // Get individual update points
        $updates = array();
        $points = array();
        $pointsVersion = array();
        $dir_handle = opendir($path);
        if ($dir_handle)
        {
            while($file = readdir($dir_handle))
            {
                if(!is_dir($path."/".$file) && $file != '.' && $file != '..')
                {
                    $point = substr($file, 0, -4); // remove .php to get point number

                    if (($this->version->major < (int) $major)
                        || ($this->version->major == (int) $major
                            && $this->version->minor < (int) $minor)
                        || ($this->version->major == (int) $major
                            && $this->version->minor == (int) $minor
                            && $this->version->point < (int) $point))
                    {
                        $points[] = (int) $point;
                        $updates[] = $file;
                    }
                    $pointsVersion[] = (int) $point;
                }
            }

            // Sort updates by points
            array_multisort($points, $updates);

            // Sort Points
            sort($pointsVersion);
            $pointsVersion = array_reverse($pointsVersion);

            closedir($dir_handle);
        }

        // Pull updates/points from minor dirs
        foreach ($updates as $update)
        {
            // Make sure it is a php script
            if (substr($update, -3) == 'php')
            {
                // It's possible to run through this without executing the scripts
                if($this->executeUpdates)
                {
                    // Execute a script only on the current account
                    $script = new BinScript($this->account->getApplication(), $this->account);
                    $script->run($path."/".$update);
                }

                // Update the point
                $this->updatedToVersion->point = (int) substr($update, 0, -4);
            }
        }

        $this->updatedToVersion->major = (int) $major;
        $this->updatedToVersion->minor = (int) $minor;

        // If we didn't find any updates to run, then set to 0
        if (!isset($this->updatedToVersion->point))
            $this->updatedToVersion->point = 0;
    }
}
