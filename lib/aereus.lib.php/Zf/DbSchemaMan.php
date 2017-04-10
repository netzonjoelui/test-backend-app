<?php
/**
 * Aereus Library for managing database schemas
 *
 * This can be used to manage versioned schemas for databases
 * written for applications using the Zend Framework. Normally the standard usage is as follows:
 * 1. Create a /system/db directory in the project
 * 2. In that directory create a file called update.php. Example:
 * 		ini_set('include_path', "../../library/" . PATH_SEPARATOR . ini_get('include_path'));
 *		require_once("Zend/Config.php");
 *		require_once("Zend/Config/Ini.php");
 *		require_once("Zend/Db.php");
 *		require_once("Zend/Db/Adapter/Pdo/Pgsql.php");
 *		require_once("Aereus/Zf/DbSchemaMan.php");
 *
 *		$config = new Zend_Config_Ini('../../application/configs/application.ini', getenv('APPLICATION_ENV'));
 *			
 *		$db = new Zend_Db_Adapter_Pdo_Pgsql(array(
 *			'host'     => $config->resources->db->params->host,
 *			'username' => $config->resources->db->params->username,
 *			'password' => $config->resources->db->params->password,
 *			'dbname'   => $config->resources->db->params->dbname
 *		));
 *
 *		$schemaMan = new Aereus_Zf_DbSchemaMan($db);
 *		$schemaMan->update();
 * 3. Create a directory structure under /system/db/schema that follows majorversion/minorvers/point.sql
 *    Each name MUST be unique and incremental. For instance 1/1/1.sql will correspend to scema version 1.1.1
 *    The next sequential sql statement must go in 1/1/2.sql. We try to keep the numbers to three digits or less
 *    so rather than using 1/1000/1.sql you would make a new subdir 2/1/1.sql incrementing the major when the minor
 *    moves beyond three digits.
 * 4. To update the schema, simply go to /system/db and run "php update.php"
 *
 * @category  Aereus_Zf
 * @package   Aereus_Zf_DbSchemaMan
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class that manages schemas
 */
class Aereus_Zf_DbSchemaMan
{
	/**
     * Handle to the PDO database adapter
     *
     * @var Zend_Db_Adapter
     */
	private $db = null;

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
	public $tableName = "sys_settings";

	/**
     * Class constructor
	 *
	 * @param Zend_Db_Adapter $db	Handle to the pdo database adapter connection
     */
	function __construct($db) 
	{
		$this->db = $db;
		$this->version = new stdClass();
		$this->updatedToVersion= new stdClass();

		// Get version and initialize table if needed
		$this->getCurrentVersion();
	}

	/**
     * Get the current version. Initialize if settings teable does not exist.
     */
	public function getCurrentVersion() 
	{
		// First check to see if the sys_settings table exists
		$desc = $this->db->describeTable($this->tableName);
		if (!count($desc))
		{
			// Table has not yet been created, try to initialize
			$stmt = $this->db->query(
				"CREATE TABLE ".$this->tableName."
				 (
				   \"name\" character varying(256),
				   \"value\" text,
				   CONSTRAINT ".$this->tableName."_pkey PRIMARY KEY (\"name\")
				 )"
			 );
		}

		// Check for current version
		$query = "SELECT value FROM ".$this->tableName." WHERE name=?";
		$version = $this->db->fetchOne($query, "schema_version");
		if ($version)
		{
			$parts = explode(".", $version);

			$this->version->major = (int) $parts[0];
			if (is_numeric($parts[1]))
				$this->version->minor = (int) $parts[1];
			if (is_numeric($parts[2]))
				$this->version->point = (int) $parts[2];
		}
		else
		{
			// Insert
			$data = array(
				'name'		=> "schema_version",
				'value'		=> "1.0.0"
			);
			$this->db->insert($this->tableName, $data);
		}

		// Set initial defaults if not set
		if (!$this->version->major)
			$this->version->major = 1;
		if (!$this->version->minor)
			$this->version->minor = 0;
		if (!$this->version->point)
			$this->version->point = 0;
	}


	/**
     * Save the last updated schema version to the database settings table
     */
	public function saveUpdatedVersion() 
	{
		$data = array(
			'value' => $this->updatedToVersion->major.".".$this->updatedToVersion->minor.".".$this->updatedToVersion->point
		);
		 
		$this->db->update($this->tableName, $data, "name='schema_version'");
	}

	/**
     * Run update scripts
	 *
	 * @param string $rootPath The path of the schema directory
     */
	public function update($rootPath=".") 
	{
		$path = $rootPath."/schema";

		// Get major version directories
		$majors = array();
		$dir_handle = opendir($path);
		if ($dir_handle)
		{
			while($file = readdir($dir_handle))
			{
				if(is_dir($path."/".$file) && $file[0] != '.')
				{
					if ($this->version->major <= (int) $file)
						$majors[] = (int) $file;
				}
			}
			sort($majors);
			closedir($dir_handle);
		}

		// Get minor version directories
		foreach ($majors as $dir)
			$this->processMinorDirs($dir, $path);

		if ($this->updatedToVersion->major)
		{
			$this->saveUpdatedVersion();

			echo "Database schema was updated to: ".$this->updatedToVersion->major.".";
			echo $this->updatedToVersion->minor.".".$this->updatedToVersion->point."\n";
		}
		else
		{
			echo "Current database schema is update to date\n";
		}
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
						$minors[] = (int) $file;
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
				}
			}

			// Sort updates by points
			array_multisort($points, $updates);

			closedir($dir_handle);
		}

		// Pull updates/points from minor dirs
		foreach ($updates as $update)
		{
			echo "Process [$major.$minor.$update]\t".substr($update, -3)."\n";

			switch (substr($update, -3))
			{
			case 'sql':
				$query = file_get_contents($path."/".$update);

				try
				{
					//$stmt = $this->db->query($query);
					$this->db->getConnection()->exec($query);
				}
				catch (Exception $ex)
				{
					echo "SQL ERROR: ".$ex->getMessage()."\n\n";
					return false;
				}
				break;
			}

			$this->updatedToVersion->major = (int) $major;
			$this->updatedToVersion->minor = (int) $minor;
			$this->updatedToVersion->point = (int) substr($update, 0, -4);
		}
	}
}
