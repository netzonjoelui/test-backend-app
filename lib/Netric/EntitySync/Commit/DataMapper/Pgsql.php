<?php
/**
 * Abstract commit datamapper
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Commit\DataMapper;

class Pgsql extends DataMapperAbstract
{
	/**
	 * Database handle
	 *
	 * @var \Netric\Db\DbInterface
	 */
	private $dbh = null;

	/**
	 * Sequence name
	 *
	 * There is currently no reason to use more than one sequence even though 
	 * it will be rendering a lot of increments across all kinds of object operations.
	 * 
	 * BIGINT supports 922 quadrillion unique entries which means if we were to
	 * give a unique id to every star in the milky way (100 billion stars),
	 * then we could track 9.2 million milky way size universes before UID collision!
	 *
	 * For a real world example, let's assume one account (each account has it's own commit id)
	 * was sustaining 100,000 commits per second without pause the whole year. One bigint could
	 * keep up with those commits for 2,924,712 years before wrapping.
	 */
	private $sSequenceName = "object_commit_seq";

	/**
	 * Setup this class called from the parent constructor
	 * 
	 * @param ServiceLocator $sl The ServiceLocator container
	 */
	protected function setUp()
	{
		$this->dbh = $this->account->getServiceManager()->get("Db");
	}

	/**
	 * Get next id
	 *
	 * @param string $key
	 * @return bigint
	 */
	public function getNextCommitId($key)
	{
		$cid = $this->getNextSeqVal();
		
		// The sequence may not be defined, try creating it
		if (!$cid)
		{
			$cid = $this->createSeq();
			$cid = $this->getNextSeqVal();
		}

		return $cid;
	}

	/**
	 * Set the head commit id for an object
	 *
	 * @param string $key
	 * @param bigint $cid
	 * @return bool true on success, false on failure
	 */
	public function saveHead($key, $cid)
	{
		// Check to see if this exists already
		$existQuery = "SELECT head_commit_id FROM object_sync_commit_heads 
						WHERE type_key='" . $this->dbh->escape($key) . "';";
		if ($this->dbh->getNumRows($this->dbh->query($existQuery)))
		{
			$res = $this->dbh->query("UPDATE object_sync_commit_heads 
									  SET head_commit_id='" . $this->dbh->escape($cid) . "' 
									  WHERE type_key ='" . $this->dbh->escape($key) . "';");
		}
		else
		{
			$res = $this->dbh->query("INSERT INTO object_sync_commit_heads
									  (head_commit_id, type_key) 
									  VALUES(
									  	'" . $this->dbh->escape($cid) . "', 
									  	'" . $this->dbh->escape($key) . "'
									  );");
		}

		if (!$res)
			echo $this->dbh->getlastError();
		
		return ($res) ? true : false;
	}

	/**
	 * Get the head commit id for an object type
	 *
	 * @param string $key
	 * @return bigint
	 */
	public function getHead($key)
	{
		$res = $this->dbh->query("SELECT head_commit_id FROM object_sync_commit_heads 
								  WHERE type_key='" . $this->dbh->escape($key) . "';");
		if ($res)
			return $this->dbh->getValue($res, 0, "head_commit_id");
		else
			return 0;
	}

	/**
	 * Get the next value of the sequenece
	 */
	private function getNextSeqVal()
	{
		$res = $this->dbh->query("SELECT nextval('" . $this->sSequenceName . "');");
		if ($res)
		{
			return $this->dbh->getValue($res, 0, "nextval");
		}
	}

	/**
	 * Try to create the sequence
	 *
	 * @return int|bool current id of the sequence on success, false on failure
	 */
	private function createSeq()
	{
		$this->dbh->query("CREATE SEQUENCE " . $this->sSequenceName . ";");
	}
}