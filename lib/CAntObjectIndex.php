<?php
require_once("lib/CAntObject.php");
require_once("lib/aereus.lib.php/elastic.php");
require_once("lib/obj_indexers/db.php");
if (defined('ANT_INDEX_SOLR_HOST'))
	require_once("lib/obj_indexers/solr.php");
require_once("lib/obj_indexers/elastic.php");
require_once("lib/obj_indexers/entityquery.php");

//$OBJECT_INDEX_TYPES = array('1'=>"db", '2'=>"elastic", '3'=>"solr");
$G_OBJ_IND_EXISTS = array();

// base class to be extended
class CAntObjectIndex
{
	var $dbh = null;
	var $obj = null;
	var $objList = null;
	var $cachable = false; // Does this index support returning data in result set
	var $lastError = null; // Used to store the last error message
	// Increment this if major changes are made to the way objects are indexed
	// causing them to be reindexed
	var $engineRev = 1;
	// facet fields
	var $facetFields = array();
	var $aggregateFields = array();

	/**
	 * The unique id of this index
	 *
	 * @var int
	 */
	public $indexId = 1; // db

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Account database
	 * @param CAntObject $obj Instance of ant object we are indexing
	 */
	public function __construct($dbh, $obj)
	{
		$this->dbh = $dbh;
		$this->obj = $obj;

		// Check if the index needs to be initailized
		$cache = CCache::getInstance();
		$rev = $cache->get($this->dbh->dbname . "/object/indexinit/" . $this->indexId);
		if (!$rev)
		{
			$rev = $this->dbh->GetValue($this->dbh->Query("SELECT revision FROM object_indexes WHERE id='" . $this->indexId . "'"), 0, "revision");
			if ($rev)
				$rev = $cache->set($this->dbh->dbname . "/object/indexinit/" . $this->indexId, $this->engineRev);
		}

		if (!$rev)
		{
			$ret = $this->init();
			if ($ret)
			{
				$this->dbh->Query("DELETE FROM object_indexes WHERE id='" . $this->indexId . "'");
				$this->dbh->Query("INSERT INTO object_indexes(id, revision) 
								   VALUES(".$this->dbh->EscapeNumber($this->indexId).", ".$this->dbh->EscapeNumber($this->engineRev).")");
				$rev = $cache->set($this->dbh->dbname . "/object/indexinit/" . $this->indexId, $this->engineRev);
			}
		}
	}

	/**
	 * Initialize the index
	 *
	 * @param int $lastRev Revision of the last init, if 0 then never yet initialized
	 * @return bool true on success, false on failure
	 */
	public function init($lastRev=0)
	{
		// Default to do nothing
		return true;
	}

	/**
	 * Uninitialize an index
	 *
	 * This is used to rebuild an index
	 *
	 * @return bool true on success, false on failure
	 */
	public function uninit()
	{
		$cache = CCache::getInstance();
		$this->dbh->Query("DELETE FROM object_indexes WHERE id='" . $this->indexId . "'");
		$cache->remove($this->dbh->dbname . "/object/indexinit/" . $this->indexId);
		$this->dbh->Query("DELETE FROM object_indexes WHERE id='" . $this->indexId . "'");
		$this->dbh->Query("DELETE FROM object_indexed WHERE index_type='".$this->indexId."'");

		// TODO: we should call a function in the derrived indexes to actually purge the index

		return true;
	}

	// Index an object
	function indexObject($obj, $commit=true)
	{
	}

	// Remove object
	function removeObject($obj)
	{
	}

	// Commit and/or refresh index (if needed)
	function commit()
	{
	}

	// Optimize/vacuum/defrag an index
	function optimize()
	{
	}

	// Remove by query
	function removeByQuery()
	{
	}

	/**
	 * Get index types array
	 *
	 * @return array("id"=>name)
	 */
	static public function getIndexTypes()
	{
		return array('1'=>"db", '2'=>"elastic", '3'=>"solr", '4'=>'entityquery');
	}

	/**
	 * Query an index and populate $objList with results.
	 *
	 * @param CAntObjectList $objList Instance of object list that is calling this index
	 * @param string $conditionText Optional full-text query string
	 * @param array $conditions Conditions array - array(array('blogic', 'field', 'operator', 'value'))
	 * @param array $orderBy = array(array('fieldname'=>'asc'|'desc'))
	 * @param int $offset Start offset
	 * @param int $limit The number of items to return with each query
	 */
	public function queryObjects($objList, $conditionText="", $conditions=array(), $orderBy=array(), $offset=0, $limit=250)
	{
		$this->objList = $objList;
		$this->obj = $objList->obj;
	}

	/**
	 * Split a full text string into an array of terms
	 *
	 * @param string $qstring The entered text
	 * @return array Array of terms
	 */
	public function queryStringToTerms($qstring)
	{
		if (!$qstring)
			return array();

		$res = array();
		//preg_match_all('/(?<!")\b\w+\b|\@(?<=")\b[^"]+/', $qstr, $res, PREG_PATTERN_ORDER);
		preg_match_all('~(?|"([^"]+)"|(\S+))~', $qstring, $res);
		return $res[0]; // not sure why but for some reason results are in a multi-dimen array, we just need the first
	}

	/*
	function buildConditionString($conditionText, $conditions)
	{
	}

	function buildAdvancedConditionString($conditions)
	{
	}
	*/
}


// Helper functions
// -----------------------------------------------------------------------


/**************************************************************************
* Function: 	getIndexAvailable
*
* Purpose:		Find out if an index type is available
*
* Params:		(string) $type = the name of the indexer (db, elatic....)
**************************************************************************/
function index_is_available($type)
{
	$ret = false;

	switch ($type)
	{
	case 'elastic':
		if (defined("ANT_INDEX_ELASTIC_HOST") && ANT_INDEX_ELASTIC_HOST != "")
			$ret = true;
		break;
	case 'solr':
		if (defined("ANT_INDEX_SOLR_HOST") && ANT_INDEX_SOLR_HOST != "")
			$ret = true;
		break;
    case 'entityquery':
	case 'db':
		$ret = true; // db is always available
		break;
	}

	return $ret;
}
?>
