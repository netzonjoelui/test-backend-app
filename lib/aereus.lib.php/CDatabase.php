<?php	
/*======================================================================================
	
	Class:		CDatabase

	Purpose:	Encapsulate database functions. Currently only works with PostgreSQL

	Author:		Jeff Baker, jeff.baker@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		$dbh = new CDatabase("server", "dbname", "user", "password", "dbtype");
				// Run a query
				$result = $dbh->Query("select * from table");
				// Get results
				$num = $dbh->GetNumberRows($results);
				for ($i = 0; $i < $num; $i++)
				{
					$row = $dbh->GetNextRow($result, $i);
					echo $row['id'];
				}

	Globals:	
				1. $ALIB_DB_SERVER		= "servername";
				2. $ALIB_DB_NAME		= "database";
				3. $ALIB_DB_USER		= "username";
				4. $ALIB_DB_PASS		= "userpassword";
				5. $ALIB_DB_TYPE		= "pgsql", "mysql";
				6. $ALIB_DB_ENCODING	= "unicode";
======================================================================================*/

class CDatabase
{
	var $dbHandle;
	var $results;
	var $cache_query;
	var $encoding;
	
	function CDatabase($server=null, $dbname=null, $user=null, $password=null, $dbtype="PGSQL", $enc="UNICODE") 
	{
		global $ALIB_DB_SERVER, $ALIB_DB_NAME, 
			   $ALIB_DB_USER, $ALIB_DB_PASS, $ALIB_DB_TYPE;
		
		if (!$server)
			$server = $ALIB_DB_SERVER;
		if (!$dbname)
			$dbname = $ALIB_DB_NAME;
		if (!$user)
			$user = $ALIB_DB_USER;
		if (!$password)
			$password = $ALIB_DB_PASS;
		if (!$dbtype)
			$dbtype = $ALIB_DB_TYPE;
		if ($enc)
			$this->encoding = $enc;
		else
			$this->encoding = $ALIB_DB_ENCODING;

		$this->dbHandle = pg_connect("host=$server 
									  dbname=$dbname 
									  user=$user 
									  password=$password");
		
		pg_set_client_encoding ($this->dbHandle, $this->encoding);
	}
	
	function __destruct() 
	{
		//@pg_close($this->dbHandle);
	}
	
	function GetHandle()
	{
		return $this->dbHandle;
	}
	
	function GetColumnComment($table, $column)
	{
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
	
	function GetTableColumns($table, $col = NULL, $schema = NULL)
	{
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
	
	function Query($query)
	{
		if ($this->result)
			$this->FreeResults();

		pg_set_client_encoding ($this->dbHandle, $this->encoding);
		$result = pg_query($this->dbHandle, $query);
		if ($result === FALSE)
			echo $query; 
	
		return $result;
	}

	function GetNumberRows($result)
	{
		if ($result)
			return pg_num_rows($result);
	}

	function GetNextRow($result, $num = 0, $argument = NULL)
	{
		if ($argument)
			$retval = pg_fetch_array($result, $num, $argument);
		else
			$retval = pg_fetch_array($result, $num);

		return $retval;
	}

	function GetRow($result, $num = 0, $argument = NULL)
	{
		if ($argument)
			$retval = pg_fetch_array($result, $num, $argument);
		else
			$retval = pg_fetch_array($result, $num);

		return $retval;
	}

	function GetValue($result, $num = 0, $name=0)
	{
		if ($argument)
			$row = pg_fetch_array($result, $num, $argument);
		else
			$row = pg_fetch_array($result, $num);

		return $row[$name];
	}

	function AddColumnComment($table, $column, $comment)
	{
		$this->Query("COMMENT ON COLUMN $table.$column IS '$comment';");
	}

	function FreeResults($result)
	{
		if ($result)
			pg_free_result($result);
	}

	function TableExists($tbl, $schema=null)
	{
		$query = "SELECT tablename FROM pg_tables where tablename='$tbl'";
		if ($schema) $query .= " and schemaname='$schema'";

		if ($this->GetNumberRows($this->Query($query)))
			return true;
		else
			return false;
	}

	function SchemaExists($sch)
	{
		$query = "SELECT nspname from pg_namespace where nspname='$sch'";

		if ($this->GetNumberRows($this->Query($query)))
			return true;
		else
			return false;
	}

	function IsPrimaryKey($tbl, $col=null, $schema=null)
	{
		$query = "select pg_class.relname, pg_attribute.attname 
					from 
					pg_class, pg_attribute, pg_namespace, pg_index
					where 
					pg_class.oid = pg_attribute.attrelid
					and pg_class.oid = pg_index.indrelid
					and pg_index.indkey[0] = pg_attribute.attnum
					and pg_index.indisprimary = 't'
					and pg_namespace.oid = pg_constraint.connamespace
					and relname = '$tbl' ";
		if ($schema)
			$query .= "and pg_namespace.nspname='$schema'";
		if ($col)
			$query .= "and attname='$col'";

		$result = pg_query($this->dbHandle, $query);
		if (pg_num_rows($result))
		{
			$row = pg_fetch_array($result);
			return $row['attname'];
		}
		else
		{
			return false;
		}
	}

	function Escape($text)
	{
		return pg_escape_string($text);
	}
    
    function EscapeBool($text)
    {
        if(empty($text))
            return "f";
        else
            return pg_escape_string($text);
    }

	function EscapeNumber($numbervalue)
	{
		if (is_numeric($numbervalue))
			return "'$numbervalue'";
		else
			return 'NULL';
	}

	function EscapeDate($text)
	{
		if ($text)
			return "'$text'";
		else
			return 'NULL';
	}

	function GetFieldName($result, $id)
	{
		return pg_field_name($result, $id);
	}
	
	function GetFieldType($result, $id)
	{
		return pg_field_type($result, $id);
	}

	function GetNumFields($result)
	{
		return pg_num_fields($result, $id);
	}
}

/******************************************************************************************
*	Function:		CheckNumber
*
*	Purpose:		Prepare a number for database entry
*
*	Arguments:		1. Number to validata
*******************************************************************************************/
/*
function db_CheckNumber($numbervalue)
{
	if ($numbervalue && is_numeric($numbervalue))
		return "'$numbervalue'";
	else
		return 'NULL';
}
*/

/******************************************************************************************
*	Function:		UploadDate
*
*	Purpose:		Prepare date for database entry
*
*	Arguments:		1. date to check
*******************************************************************************************/
/*
function db_UploadDate($date)
{
	if ($date && $date != "0/0/00" && strtolower($date) != "never")
		return "'".addslashes($date)."'";
	else
		return 'NULL';
}
function db_CheckDate($date)
{
	if ($date && $date != "0/0/00" && strtolower($date) != "never")
		return "'".addslashes($date)."'";
	else
		return 'NULL';
}
 */
?>
