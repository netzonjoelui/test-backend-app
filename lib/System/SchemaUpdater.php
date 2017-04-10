<?php
/**
 * Aereus Library for managing schema updates
 *
 * This can be used to manage versioned schemas for databases
 * Pulls updates from /system/schema/updates/[majornum]/[minornum]/[point].[type]
 *
 * [TYPES]
 * .sql files are treated as raw sql queries
 * .php files are actual routine scripts.
 *
 * There is also an 'always' directory found in the /system/schema/updates directory will
 * always run last no matter what the current schema revision is.
 *
 * @category  Ant
 * @package   AntSystem_SchemaUpdater
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class that manages schema updates
 */
class AntSystem_SchemaUpdater
{
	/**
     * Handle to the account database
     *
     * @var CDatabase
     */
	private $dbh = null;

	/**
     * Handle to the account base class
     *
     * @var Ant
     */
	private $ant = null;

	/**
     * Used to manage major, minor, and points for a version
     *
     * @var stdClass
     */
	private $version = null;

	/**
     * Ticker used to track last updated to
     *
     * @var stdClass
     */
	private $updatedToVersion = null;

	/**
     * The name of the name/value table that will hold the schema version
     *
     * @var stdClass
     */
	public $tableName = "system_registry";
    
    /**
     * Determine wheter to execute the sql updates
     * Set boolean value false if we need just to get the system schema file version
     *
     * @var stdClass
     */
    public $executeUpdater = true;

	/**
     * Flag used to determine if this account qualifies to use the schema updater
     *
     * @var bool
     */
	private $schemaReady = false;

	/**
	 * Print output while processing.
	 *
	 * If set to fase the clas will update silently.
     *
     * @var bool
     */
	private $printOutput = true;

	/**
     * Class constructor
	 *
	 * @param Ant $ant Handle to ANT account
	 * @param bool $printoutput If set to true class will echo progress
     */
	function __construct($ant, $printoutput=true) 
	{
		$this->dbh = $ant->dbh;
		$this->ant = $ant;
		$this->version = new stdClass();
		$this->updatedToVersion= new stdClass();
		$this->printOutput = $printoutput;

		// Get version and determine if schema is ready to be updated
		$this->schemaReady = $this->getCurrentVersion();
	}

	/**
     * Get the current version and determine if the schema is ready to be updated
     */
	public function getCurrentVersion() 
	{
		$version = $this->ant->settingsGet("system/schema_version");

		// We are not yet to version 3 which is required by the SchemaUpdater.
		// Prior versions used another update method.
		if (!$version)
		{
			return false;
		}

		// Set current version
		$parts = explode(".", $version);
		$this->version->major = (int) $parts[0];
		if (is_numeric($parts[1]))
			$this->version->minor = (int) $parts[1];
		if (is_numeric($parts[2]))
			$this->version->point = (int) $parts[2];

		// Set initial defaults if not set
		if (!$this->version->major)
			$this->version->major = 1;
		if (!$this->version->minor)
			$this->version->minor = 0;
		if (!$this->version->point)
			$this->version->point = 0;

		return true;
	}


	/**
     * Save the last updated schema version to the database settings table
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
			$this->ant->settingsSet("system/schema_version", $newversion);
	}

	/**
     * Run update scripts
	 *
	 * @param bool $processAlways If set to false scripts in the /system/schema/always will not run
	 * @return bool false on failure, true on success
     */
	public function update($processAlways=true) 
	{
		if (!$this->schemaReady)
			return false;

        $rootPath = dirname(__FILE__) . "/../../system/schema";
            
        // This will get the major, minor and point versions
		$this->processSchemaFolders($rootPath);

		if ($this->updatedToVersion->major)
		{
			$this->saveUpdatedVersion();

			if ($this->printOutput)
			{
				echo "Database schema was updated to: ".$this->updatedToVersion->major.".";
				echo $this->updatedToVersion->minor.".".$this->updatedToVersion->point."\n";
			}
		}
		else
		{
			if ($this->printOutput)
				echo "Current database schema is up-to-date!\n";
		}

		// Now run always updates if any
		if ($processAlways)
			$this->processAlwaysUpdates($rootPath."/always");

		return true;
	}

	/**
     * Process minor subdirectories in the major dir
	 *
	 * @param string $major	The name of a major directory 
	 * @param string $base	The base or root of the path where the major dir is located
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
					$point = substr($file, 0, -4); // remove .sql to get point number

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
			if ($this->printOutput)
				echo "Process [$major/$minor/$update]\t".substr($update, -3)."\n";

			$stop = false;

            // Do not execute if we just need to get the system schema version
            if($this->executeUpdater)
            {

                switch (substr($update, -3))
                {
				case 'sql':
					$query = file_get_contents($path."/".$update);
					$ret = $this->dbh->Query($query);
					if (!$ret)
					{
						if ($this->printOutput)
							echo "SQL ERROR: ".$this->dbh->getLastError()."\n\n";

						//return false;
						$stop = true;
					}
					break;
				case 'php':
					$ant = $this->ant;
					$ret = true; // If the script fails, it can update $ret
					$printOutput = $this->printOutput;
					include($path."/".$update);
					if (!$ret)
					{
						$stop = true;

						if ($this->printOutput)
							echo "PHP update failed!!!\n\n";
					}

					break;
                }

            }

			if ($stop)
				break;
		}
        
        $this->updatedToVersion->major = (int) $major;
        $this->updatedToVersion->minor = (int) $minor;
        
        if(!empty($update)) // Use the latest executed update
            $this->updatedToVersion->point = (int) substr($update, 0, -4);
        else // Get the available latest version
            $this->updatedToVersion->point = (int) $pointsVersion[0];
            
	}

	/**
     * Process all files in the schema/always directory
	 *
	 * @param string $base The base path where the 'always' directory is located
     */
	private function processAlwaysUpdates($path) 
	{
		// Get individual update points
		$updates = array();
		$dir_handle = opendir($path);
		if ($dir_handle)
		{
			while($file = readdir($dir_handle))
			{
				if(!is_dir($path."/".$file) && $file != '.' && $file != '..')
				{
					$updates[] = $file;
				}
			}
			sort($updates);
			closedir($dir_handle);
		}

		// process scripts
		foreach ($updates as $update)
		{
			if ($this->printOutput)
				echo "Process always update $update\t".substr($update, -3)."\n";

			switch (substr($update, -3))
			{
			case 'sql':
				$query = file_get_contents($path."/".$update);
				$ret = $this->dbh->Query($query);
				if (!$ret)
				{
					if ($this->printOutput)
						echo "SQL ERROR: ".$this->dbh->getLastError()."\n\n";
					return false;
				}
				break;
			case 'php':
				$ant = $this->ant;
				$ret = true; // If the script fails, it can update $ret
				include($path."/".$update);
				if (!$ret)
					return false;
				break;
			}
		}
	}

	/**
     * Load default data - should only be run on newly created schema
	 *
	 * @return bool false on failure, true on success
     */
	public function loadData()
	{
		if (!$this->schemaReady)
			return false;

		$path = dirname(__FILE__) . "/../../system/schema/data";

		// Get individual update points
		$updates = array();
		$dir_handle = opendir($path);
		if ($dir_handle)
		{
			while($file = readdir($dir_handle))
			{
				if(!is_dir($path."/".$file) && $file != '.' && $file != '..' && substr($file, -3)=="php")
				{
					$updates[] = $file;
				}
			}
			sort($updates);
			closedir($dir_handle);
		}

		// process scripts
		foreach ($updates as $update)
		{
			$tbl = substr($update, 0, -4); // remove .php

			if ($this->printOutput)
				echo "Importing data $tbl\t";

			if (!$this->dbh->TableExists($tbl))
			{
				$this->lastError = "Tried to import data on a non-existant table";
				return false;
			}

			$data = array();

			// Include ANT in case we need to do some special processing
			// Inserting data always comes last in the creation process
			$ant = $this->ant;
			include($path . "/" . $update);

			foreach ($data as $row)
			{
				$query = "INSERT INTO $tbl(";
				$cols = "";
				foreach ($row as $cname=>$cval)
				{
					if ($cols) $cols .= ", ";
					$cols .= $cname;
				}
				$query .= $cols . ") VALUES(";
				$cols = "";
				foreach ($row as $cname=>$cval)
				{
					if ($cols) $cols .= ", ";
					$cols .= "'" . $this->dbh->Escape($cval) . "'";
				}
				$query .= $cols . ");";

				$ret = $this->dbh->Query($query);
				if ($ret === false)
				{
					echo "<pre>".$this->dbh->getLastError()."</pre>";
					$this->lastError = "Error trying to import data: ".$this->dbh->getLastError();
					return false;
				}

			}
		}

		return true;
	}
    
    /**
     * Gets the latest version of database schema from the file structure
     *
     * @return bool false on failure, true on success
     */
    public function getLatestVersion()
    {
        $rootPath = dirname(__FILE__) . "/../../system/schema";
            
        // This will get the major, minor and point versions
        $this->processSchemaFolders($rootPath);
        
        $versionParts[] = $this->updatedToVersion->major;
        $versionParts[] = $this->updatedToVersion->minor;
        $versionParts[] = $this->updatedToVersion->point;
        
        $latestVersion = implode(".", $versionParts);
        if ($this->printOutput)
            echo $latestVersion;
            
        return $latestVersion;
    }
    
    /**
     * This will get the major, minor and point versions
     *
     * @param string $rootPath  The root path of system schema
     */
    private function processSchemaFolders($rootPath)
    {
        $updatePath = $rootPath . "/updates";

        // Get major version directories
        $majors = array();
        $dir_handle = opendir($updatePath);
        if ($dir_handle)
        {
            while($file = readdir($dir_handle))
            {
                if(is_dir($updatePath."/".$file) && $file[0] != '.')
                {
                    if ($this->version->major <= (int) $file)
                        $majors[] = $file;
                }
            }
            sort($majors);
            closedir($dir_handle);
        }

        // Get minor version directories
        foreach ($majors as $dir)
            $this->processMinorDirs($dir, $updatePath);
    }
}
