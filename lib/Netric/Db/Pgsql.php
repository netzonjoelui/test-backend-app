<?php
/*
 * Short description for file
 * 
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  @author joe <sky.stebnicki@aereus.com>
 *  @copyright 2014 Aereus
 */
namespace Netric\Db;

/**
 * Description of Pgsql
 *
 * @author joe
 */
class Pgsql implements DbInterface 
{
    /**
	 * Number of queries run insternal counter
	 *
	 * @var int
	 */
	public $statNumQueries = 0;

	/**
	 * Debug flag used to testing
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Actual database handle
	 *
	 * @var pg_handle
	 */
	private $dbHandle = null;

	/**
	 * Cache results from last query
	 *
	 * @var int(resource) $results
	 */
	private $results = null;

	/**
	 * Account namespace / id
	 *
	 * @var int
	 */
	public $accountId = false;

	/**
	 * Schema name
	 *
	 * @var string
	 */
	private $schema = "";

	var $cache_query;
	var $dbname;
	var $timezoneName;
	var $fDateStyleSet = false;
	var $fByteaOuputSet = false;

	// Database connection params
	var $server;
	var $database;
	var $user;
	var $password;
	var $encoding;
	var $port = 5432;
	
	/**
	 * Class constructor
	 *
	 * @param string $server The server or host where the database is hosted
	 * @param string $dbname The name of the database to connect to
	 */
	public function __construct($server, $dbname, $user=null, $password=null, $enc=null) 
	{
        $this->server = $server;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->password = $password;
		$this->dbHandle = false;
	}
	
	public function __destruct() 
	{
        //$this->close();
		//@pg_close($this->dbHandle);
		//$this->dbHandle = false;
	}

    /**
     * Close handle to database
     */
	public function close() 
	{
		$this->dicconnect();
	}

    /**
     * Check if this database has an active connection established
     * 
     * @return boolean
     */
	public function isActive()
	{
		if (!$this->dbHandle)
			return false;
		else
			return true;
	}

    /**
     * Close the connection to the database
     */
	public function dicconnect()
	{
		if ($this->dbHandle)
			@pg_close($this->dbHandle);

		$this->dbHandle = false;
	}

	/**
	 * Connect to database backend
	 */
	public function connect()
	{
		if ($this->dbname)
		{
			$this->dbHandle = @pg_connect("host=".$this->server."
										  dbname=".$this->dbname."
										  user=".$this->user." 
										  port=".$this->port." 
										  password=".$this->password);

			if (!$this->dbHandle) {
				return false;
			}

			pg_set_client_encoding($this->dbHandle, "UNICODE");
			$this->cache_query = false;
			$this->encoding = "UNICODE";

			// Set date format
			$this->query("SET datestyle = 'SQL, MDY';");

			// Set timezone
			if ($this->timezoneName)
				$this->query("SET TIME ZONE '".$this->timezoneName."';");
		}
		else
		{
			$this->dbHandle = false;
		}

		return $this->dbHandle;
	}

	/**
	 * Set schema search path
	 *
	 * @param string $namespace The schema namespace to use as the default
	 * @return bool true on success, false if schema does not exist
	 */
	public function setSchema($namespace)
	{
		$this->schema = $namespace;
		return $this->query("SET search_path TO $namespace;");
	}

	/**
	 * Get the current schema
	 *
	 * @return string
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * Get account id / namespace
	 *
	 * @return int | string
	 */
	public function getAccountNamespace()
	{
		// Best option is to return the account id
		if ($this->accountId)
			return $this->accountId;

		// Default to returning the dbname if no accountId has been set
		// This is commonly used when working with antsystem
		return $this->dbname;
	}

    /**
     * Set alternate encoding from the default UNICODE
     * 
     * @param string $enc
     */
	function encodingSet($enc)
	{
		pg_set_client_encoding($this->dbHandle, $enc);
	}
	
    /**
     * Get a handle to the database
     * 
     * @return pgsql handle
     */
	function getHandle()
	{
		return $this->dbHandle;
	}
	
    /**
     * Get any comments on the column
     * 
     * @param type $table
     * @param type $column
     * @return boolean
     */
	function getColumnComment($table, $column)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		$query = "select col_description(
					(select oid from pg_class where relname='$table'), 
					(select attnum from pg_attribute where attname = '$column' 
					and attrelid=(select oid from pg_class where relname='$table')))";
		$result = pg_query($this->dbHandle, $query);
		if (pg_num_rows($result))
		{
			$row = pg_fetch_array($result);
			$comment = $row["col_description"];
		}
		pg_free_result($result);
		
		return $comment;
	}
	
    /**
     * Get columns for a given table
     * 
     * @param type $table
     * @param type $col
     * @param type $schema
     * @return boolean
     */
	function getTableColumns($table, $col = NULL, $schema = NULL)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;
		/*
		$query = "SELECT a.attname as colname, t.typname as coltype,
				(SELECT adsrc FROM pg_attrdef adef WHERE a.attrelid=adef.adrelid AND a.attnum=adef.adnum) AS coldefault
				FROM pg_attribute a, pg_class c, pg_type t WHERE
				c.relname = '$table' AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid";
		if ($col)
			$query .= " and a.attname = '$col' ";
		
		$query .= " ORDER BY a.attname";*/
		$query = "SELECT column_name, data_type, column_default, is_nullable, 
					table_schema FROM information_schema.columns WHERE table_name='$table'";
		if ($schema)
			$query .= " and table_schema='$schema'";
		if ($col)
			$query .= " and column_name='$col'";
		
		$result = pg_query($this->dbHandle, $query);

		if ($col)
		{
			if (pg_num_rows($result))
			{
				$row = pg_fetch_array($result, 0);
				return $row;
			}
		}
		
		return $result;
	}
	
    /**
     * Execute an SQL query
     * 
     * @param string $query SQL to execute
     * @return boolean
     */
	function query($query)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		if (isset($this->result))
			$this->freeResults();

		if (!$this->fDateStyleSet)
		{
			$query = "SET datestyle='SQL';".$query;
			$this->fDateStyleSet = true;
		}

		/*
		 * Since postgresl manages this setting per connection, it is important that
		 * we set it before every query because it's possible to do operations across multiple
		 * accounts and it will only set the path on initial connection.
		 */
		if ($this->schema)
			$query = "SET search_path=" . $this->schema . ";" . $query;

		//print($query);
		$result = @pg_query($this->dbHandle, $query);
		if ($result === false)
			$this->logError($query, $result);

		$this->statNumQueries++;

		return $result;
	}

    /**
     * Get the last pgsql error
     * 
     * @return string
     */
	function getLastError()
	{
        if ($this->dbHandle) {
            return pg_last_error($this->dbHandle);
        } else {
            return "";
        }
	}

    /**
     * @deprecated since version 4 We no longer expect the database class to log the error, but the calling class
     * with $this->getLastError()
     *
     * @param type $query
     * @param type $result
     */
	function logError($query, $result)
	{
        /*
		global $USERNAME, $_SERVER;

		$error = pg_last_error($this->dbHandle);

		if (!$error)
			return;

		$body = "User: $USERNAME\r\n";
		$body .= "Type: Database\r\n";
		$body .= "Database: ".$this->dbname."\r\n";
        $body .= "Schema: ".$this->schema."\r\n";
		$body .= "When: ".date('Y-m-d H:i:s')."\r\n";
		$body .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
		$body .= "Page: ".$_SERVER['REQUEST_URI']."\r\n";
		$body .= "Error\n";
		$body .= "----------------------------------------------\n";
		$body .= $error . "\n";
		$body .= "----------------------------------------------\n";
		$body .= "Query\n";
		$body .= "----------------------------------------------\n";
		$body .= $query . "\n";
		$body .= "----------------------------------------------\n";

		if (class_exists("AntLog") && !function_exists("phpunit_autoload")) // only log if not a unit test
		{
			AntLog::getInstance()->error($body);
		}
		else
		{
			throw new Exception('Query ERROR: ' . $error . "\n--------------------------------\n" . $query);
		}
         */
	}
    
    /**
     * Get the number of returned rows from a given result
     * 
     * @param type $result
     * @return int
     */
	public function getNumRows($result)
	{
		if ($result)
			return pg_num_rows($result);
		else
			return 0;
	}
    
    /**
     * Get a row represented by an array
     * 
     * @param type $result
     * @param type $num
     * @param type $argument
     * @return associative array
     */
	public function getRow($result, $num = 0, $argument = NULL)
	{
		return $this->getNextRow($result, $num, $argument);
	}
    
    /**
     * Get the next row in a result set
     * 
     * @param type $result
     * @param type $num
     * @param type $argument
     * @return associative array
     */
	public function getNextRow($result, $num = 0, $argument = NULL)
	{
		$retval = pg_fetch_assoc($result, $num);

		return $retval;
	}

	/**
	 * Retrieve all results in an array
	 *
	 * @return array(array(rowdata))
	 */
	public function fetchAll($result)
	{
		if (!$result)
			return null;

		return pg_fetch_all($result);
	}

	public function addColumnComment($table, $column, $comment)
	{
		$this->query("COMMENT ON COLUMN $table.$column IS '$comment';");
	}
    
    /**
     * Manually flush results 
     * 
     * @param type $result
     */
	public function freeResults($result)
	{
		if ($result !== false && $result !== null)
			pg_free_result($result);
	}

	/**
	 * Check if an index exists by name
	 *
	 * @param string $idxname The name of the index to look for
	 * @return bool true if the index was found, false if it was not
	 */
	public function indexExists($idxname) {
		$query = "select * from pg_indexes where indexname='".$this->escape($idxname)."'";
		if ($this->schema)
			$query .= " and schemaname='" . $this->schema . "'";
		if ($this->getNumRows($this->query($query)))
			return true;
		else
			return false;
	}

	function constraintExists($tbl, $conname)
	{
		$query = "select table_name from information_schema.table_constraints 
					where table_name='$tbl' and constraint_name='$conname';";

		if ($this->getNumRows($this->query($query)))
			return true;
		else
			return false;
	}

	function tableExists($tbl, $schema=null)
	{
		$query = "SELECT tablename FROM pg_tables where tablename='$tbl'";
		if ($schema) 
			$query .= " and schemaname='$schema'";
		else if ($this->schema)
			$query .= " and schemaname='" . $this->schema . "'";

		if ($this->getNumRows($this->query($query)))
			return true;
		else
			return false;
	}

	function columnExists($table, $col)
	{
		// Check if we explictely passed the schema in dot notation schema.table
		if (strpos($table, '.'))
		{
			$parts = explode(".", $table);
			$schema = $parts[0];
			$table = $parts[1];
		}
		else
		{
			$schema = "";
		}

		$query = "SELECT column_name FROM information_schema.columns WHERE table_name='$table'
					and column_name='$col'";
		if ($schema)
			$query .= " and table_schema='$schema' ";
		else if ($this->schema)
			$query .= " and table_schema='" . $this->schema . "'";

		if ($this->getNumRows($this->query($query)))
			return true;
		else
			return false;
	}
	function schemaExists($sch)
	{
		$query = "SELECT nspname from pg_namespace where nspname='$sch'";

		if ($this->getNumRows($this->query($query)))
			return true;
		else
			return false;
	}

	/**
	 * Test if a column is a primary key
	 *
	 * @param $tbl
	 * @param null $col
	 * @param null $schema
	 * @return bool
	 */
	public function isPrimaryKey($tbl, $col, $schema=null)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		if (!$col)
			return false;

		$sql = "";

		// Set default schema of not explicitly passed - only for this query
		if ($schema)
			$sql = "SET search_path=$schema;";

		$sql .= "SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type
				FROM   pg_index i
				JOIN   pg_attribute a ON a.attrelid = i.indrelid
									 AND a.attnum = ANY(i.indkey)
				WHERE  i.indrelid = '{$tbl}'::regclass
				AND    i.indisprimary ORDER BY attname;";
		$result = pg_query($this->dbHandle, $sql);
		$num = pg_num_rows($result);

		// Get sorted array of column names
		$actualPkeyName = "";
		for ($i = 0; $i < $num; $i++)
		{
			if ($actualPkeyName) $actualPkeyName .= "_";
			$actualPkeyName .= $this->getValue($result, $i, "attname");
		}

		// Check if the names are the same - have all the same columns
		$colNames = (is_array($col)) ? $col : array($col);
		asort($colNames);
		return ($actualPkeyName == implode("_", $colNames));

		/*
		$query = "select pg_class.relname, pg_attribute.attname 
					from 
					pg_class, pg_attribute, pg_namespace, pg_index, pg_constraint
					where 
					pg_class.oid = pg_attribute.attrelid
					and pg_class.oid = pg_index.indrelid
					and pg_index.indkey[0] = pg_attribute.attnum
					and pg_index.indisprimary = 't'
					and pg_namespace.oid = pg_constraint.connamespace
					and relname = '$tbl' ";
		if ($schema)
			$query .= "and pg_namespace.nspname='$schema'";
		if ($col) {

			$query .= "and (attname='" . implode("OR attname='", $colNames) . "')";
		}

		if (is_array($col))
			echo $query . "\n";
		*/
	}

	/**
	 * Check if the table has a primary key
	 *
	 * @param $tbl
	 * @param null $schema
	 * @return bool
	 */
	public function hasPrimaryKey($tbl, $schema=null)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		$sql = "";

		// Set default schema of not explicitly passed - only for this query
		if ($schema)
			$sql = "SET search_path=$schema;";

		$sql .= "SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type
				FROM   pg_index i
				JOIN   pg_attribute a ON a.attrelid = i.indrelid
									 AND a.attnum = ANY(i.indkey)
				WHERE  i.indrelid = '{$tbl}'::regclass
				AND    i.indisprimary ORDER BY attname;";
		$result = pg_query($this->dbHandle, $sql);
		$num = pg_num_rows($result);

		return ($num) ? true : false;
	}

	function loCreate()
	{
		$this->query("begin");
		$ret = pg_lo_create($this->dbHandle);
		$this->query("commit");
		$return;
	}

	function loSize($lo)
	{
		$pos = pg_lo_tell ($lo);
		pg_lo_seek ($lo, 0, PGSQL_SEEK_END);
		$size = pg_lo_tell ($lo);
		pg_lo_seek ($lo, $pos, PGSQL_SEEK_SET);
		return $size; 
	}

	function loOpen($oid, $mode='rw')
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		pg_query($this->dbHandle, "begin");
		return pg_lo_open($this->dbHandle, $oid, $mode);
	}

	function loWrite($handle, $data="")
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		pg_lo_write($handle, $data);
	}

	function loRead($handle, $len=8192)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		return pg_lo_read($handle, $len);
	}

	function loExport($oid, $path)
	{
		$this->query("begin");
		$ret = pg_lo_export($this->dbHandle, $oid, $path);
		$this->query("commit");
		return $ret;
	}

	function loImport($path)
	{
		$this->query("begin");
		$oid = pg_lo_import($this->dbHandle, $path);
		$this->query("commit");
		return $oid;
	}

	function loClose($handle)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		pg_query($this->dbHandle, "commit");
		pg_lo_close($handle);
	}
	
	function loUnlink($oid)
	{
		if (!$this->isActive())
			$this->connect();

		if (!$this->dbHandle)
			return false;

		return pg_lo_unlink($this->dbHandle, $oid);
	}

	function escape($text)
	{
		if ($this->encoding == "UNICODE")
		{
			// iconv did not appear to be stripping some non-utf chars so we are doing it manuallly below
			//$text = iconv('utf-8',"utf-8//IGNORE", $text);

			//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
			$text = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
										'|[\x00-\x7F][\x80-\xBF]+'.
										'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
										'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
										'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', '?', $text );

			//reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
			$text = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $text );
		}

		return pg_escape_string($text);
	}

	function escapeBytea($text)
	{
		return pg_escape_bytea($text);
	}

	function unEscapeBytea($text)
	{
		return pg_unescape_bytea($text);
	}
    
	function escapeNumber($numbervalue)
	{
		if (is_numeric($numbervalue))
			return "'$numbervalue'";
		else
			return 'NULL';
	}

	function escapeNull($val)
	{
		if ($val)
		{
			return "'".$this->escape($val)."'";
		}
		else
			return 'NULL';
	}

	function escapeDate($date)
	{
		$date = trim($date);
		$time = strtotime($date);
		if ($date && $date != "0/0/00" && strtolower($date) != "never" && $time !==false)
		{
			return "'".$date."'";
		}
		else
			return 'NULL';
	}

	function escapeTimestamp($date)
	{
		$date = trim($date);
		$time = strtotime($date);
		if ($date && $date != "0/0/00" && strtolower($date) != "never" && $time !==false)
		{
			return "'" . $date . "'";
		}
		else
			return 'NULL';
	}

	function getFieldName($result, $id)
	{
		return pg_field_name($result, $id);
	}

	function getFieldType($result, $id)
	{
		return pg_field_type($result, $id);
	}

	function getNumFields($result)
	{
		return pg_num_fields($result);
	}

	function getValue($result, $num = 0, $name=0)
	{
		if ($this->getNumRows($result))
		{
			$row = pg_fetch_array($result, $num);

			return $row[$name];
		}
		else
			return null;
	}

	function getTimezone($tz)
	{
		$this->timezoneName = $tz;

		if ($this->isActive())
			$this->query("SET TIME ZONE '$tz';");
	}

	/**
	 * Get the type of a column
	 *
	 * @param string $table The name of the table containing the column
	 * @param string $column The name of the column to get type for
	 * @return string Column type name
	 */
	public function getColumnType($table, $column)
	{
		$result = $this->query("select data_type, udt_name from information_schema.columns 
								where table_name='$table' and column_name='$column';");
		if ($this->getNumRows($result))
			return $this->GetValue($result, 0, "data_type");
		
		return "";
	}
}
