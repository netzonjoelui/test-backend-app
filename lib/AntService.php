<?php
/**
 * Class that turns php scripts into a continually running service
 *
 * Command Line Options
 * -a,--account = account_name (optional) if set then limit this service to only run for the given account name
 *
 * @category  Ant
 * @package   Service
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntProfiler.php");

/**
 * Base service class
 */
class AntService
{
	/**
	 * Run service per account db
	 *
	 * @var bool
	 */
	public $perAccount = true;

	/**
	 * Close the database handle on each run, useful for services, not for unit tests
	 *
	 * @var bool
	 */
	public $closeDbh = true;

	/**
	 * Only allow a single instance of this service to run at once
	 *
	 * @var bool
	 */
	public $singleton = false;

	/**
	 * File lock handle
	 *
	 * @var handle
	 */
	public $flock = null;

	/**
	 * Currently connected ANT account
	 *
	 * @var Ant
	 */
	public $ant = null;

	/**
	 * The system user
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * Class constructor
	 */
	public function __construct($ant=null)
	{
		if ($ant)
			$this->ant = $ant;
	}

	/**
	 * Cleanup
	 */
	public function __destruct()
	{
		if ($this->flock)
			fclose($this->flock);
	}

	/**
	 * Main function used to run the service.
	 */
	public function run()
	{
		if (!$this->singletonOk())
			return false;

		$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

		if ($this->perAccount)
		{
			// Get database to use from account
			$query = "SELECT id FROM accounts WHERE ";
			if (AntConfig::getInstance()->version) // limit to current version
				$query .= "version='".AntConfig::getInstance()->version."'";
			else
				$query .= " version is NULL";

			// Check if command line argument was passed for a specific account
			$options = getopt("a:", array("account:"));
			if ($options['a'] || $options['account'] )
				$query .= " AND name='" . $dbh_sys->Escape(($options['a'])?$options['a']:$options['account']) . "'";
			
			$res_sys = $dbh_sys->Query($query);
			$num_sys = $dbh_sys->GetNumberRows($res_sys);
			for ($s = 0; $s < $num_sys; $s++)
			{
				$this->startProfile();

				$this->ant = new Ant($dbh_sys->GetValue($res_sys, $s, 'id'));

				if ($this->ant->accountId)
				{

					$dbh = $this->ant->dbh;
					$this->user = new AntUser($dbh, USER_SYSTEM); // create system user

					$this->main($dbh);

					// Leaving this out because it may be causing ongoing problems - joe like scripts failing to run
					if ($this->closeDbh)
						$dbh->close(); // clean up connection
					
					unset($this->ant);
					$this->ant = null;
				}

				$this->endProfile();
			}
		}
		else
		{
			$this->main($dbh_sys);
		}

		// Be very careful about cleanup because services run forever
		$dbh_sys->FreeResults($res_sys);
		//$dbh_sys->close(); // Trying to leave this open because we appear to be having problems with db connections after a while
		unset($dbh_sys);

		return true;
	}

	/**
	 * If this service is running as a singleton (only one allowed) then check file system lock
	 *
	 * @return bool true if we can run, false if we should exit because process already exists
	 */
	public function singletonOk()
	{
		if (!$this->singleton)
			return true;

		$fname = AntConfig::getInstance()->data_path."/tmp/svc_".get_class($this) . "_" . AntConfig::getInstance()->version;

		// Open and lock process file to keep more than one copy of this from running
		$pidFp = fopen($fname, 'w+');
		if(!flock($pidFp, LOCK_EX | LOCK_NB)) 
		{
			echo 'Unable to obtain lock to ' . $fname . ' - process already running';
			return false;
		}

		$this->flock = $pidFp;

		return true;
	}

	/**
	 * This is the function that must be overridden by the actual service
	 */
	public function main(&$dbh)
	{
	}

	/**
	 * Begin profile run
	 */
	protected function startProfile()
	{
		AntProfiler::startProfile();
	}

	/**
	 * End profile
	 */
	protected function endProfile()
	{
		AntProfiler::endProfile(__CLASS__);
	}
}
