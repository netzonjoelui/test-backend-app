<?php
/*======================================================================================
	
	class:		CSessions

	Purpose:	Sessions handler

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Usage:		

	Depends:	CDatabase
				CCache

	Variables:	1.	$ALIB_USEMEMCACHED (defaults to false)
				2. 	$ALIB_MEMCACHED_SVR (defaults to localhost)
				3. 	$ALIB_SESS_USEDB (defaults to false)
				3. 	$ALIB_SESS_DB_NAME (defaults to "sessions")
				3. 	$ALIB_SESS_DB_USER (defaults to "aereus")
				3. 	$ALIB_SESS_DB_PASS (defaults to "")
				3. 	$ALIB_SESS_DB_TYPE (defaults to "pgsql")

======================================================================================*/
if (!isset($ALIB_USEMEMCACHED))
	$ALIB_USEMEMCACHED = false;
if (!isset($ALIB_MEMCACHED_SVR))
	$ALIB_MEMCACHED_SVR = "localhost";
if (!isset($ALIB_SESS_USEDB))
	$ALIB_SESS_USEDB = false;
if (!isset($ALIB_SESS_DB_NAME))
	$ALIB_SESS_DB_NAME = "sessions";
if (!isset($ALIB_SESS_DB_USER))
	$ALIB_SESS_DB_USER = "aereus";
if (!isset($ALIB_SESS_DB_PASS))
	$ALIB_SESS_DB_PASS = "";
if (!isset($ALIB_SESS_DB_TYPE))
	$ALIB_SESS_DB_TYPE = "pgsql";

class CSessions 
{
	var $life_time;
	var $dbh;
	var $cache;

	function CSessions($server=null, $dbname=null, $user=null, $password=null, $dbtype=null, $enc="SQL_ASCII") 
	{
		global $ALIB_SESS_USEDB, $ALIB_SESS_DB_SERVER, $ALIB_SESS_DB_NAME, $ALIB_SESS_DB_USER, $ALIB_SESS_DB_PASS, $ALIB_SESS_DB_TYPE, 
				$ALIB_USEMEMCACHED;

        if(defined("ALIB_USEMEMCACHED"))
            $alibMemCached = ALIB_USEMEMCACHED;
        else if(isset($ALIB_USEMEMCACHED))
            $alibMemCached = $ALIB_USEMEMCACHED;
                
		// Read the maxlifetime setting from PHP
		$this->life_time = get_cfg_var("session.gc_maxlifetime");

		if ((!$ALIB_SESS_USEDB || !class_exists("CDatabase")) && !$alibMemCached)
			return;

		// Register this object as the session handler
		session_set_save_handler( 
									array($this, "open"), 
									array($this, "close"),
									array($this, "read"),
									array($this, "write"),
									array($this, "destroy"),
									array($this, "gc")
								);

		if ($alibMemCached)
		{
			$this->cache = CCache::getInstance();
			$this->dbh = null;
		}
		else
		{
			if (!$server)
				$server = $ALIB_SESS_DB_SERVER;
			if (!$dbname)
				$dbname = $ALIB_SESS_DB_NAME;
			if (!$user)
				$user = $ALIB_SESS_DB_USER;
			if (!$password)
				$password = $ALIB_SESS_DB_PASS;
			if (!$dbtype)
				$dbtype = $ALIB_SESS_DB_TYPE;

			$this->dbh = new CDatabase($server, $dbname, $user, $password, $dbtype, $enc);
			$this->cache = null;
		}
	}

	function open($save_path, $session_name) 
	{
		global $sess_save_path;

		$sess_save_path = $save_path;

		// Don't need to do anything. Just return TRUE.

		return true;

	}

	function close() 
	{
		return true;
	}

	function read($id) 
	{
		// Set empty result
		$data = '';

		// Fetch session data from the selected database

		$time = time();

		if ($this->cache)
		{
			$data = $this->cache->get("/SESSIONS/$id");
		}
		else if ($this->dbh)
		{
			//$newid = mysql_real_escape_string($id);
			$newid = $this->dbh->Escape($id);
			$sql = "SELECT session_data FROM sessions WHERE session_id='$newid' AND expires>'$time'";

			$rs = $this->dbh->Query($sql);                           
			$a = $this->dbh->GetNumberRows($rs);

			if($a > 0) 
			{
				$data = $this->dbh->GetValue($rs, 0, "session_data");
				//$data = $row['session_data'];
			}
		}

		return $data;
	}

	function write($id, $data) 
	{
		// Build query                
		$time = time() + $this->life_time;

		if ($this->cache)
		{
			$ret = $this->cache->set("/SESSIONS/$id", $data, $time);
		}
		else if ($this->dbh)
		{
			$newid = $this->dbh->Escape($id);
			//$newdata = mysql_real_escape_string($data);
			$newdata = $this->dbh->Escape($data);

			if ($this->dbh->GetNumberRows($this->dbh->Query("select session_id from sessions where session_id='$id'")))
			{
				$sql = "update sessions set session_data='$newdata', expires='$time' where session_id='$id'";
			}
			else
			{
				$sql = "insert into sessions (session_data, expires, session_id) values('$newdata', '$time', '$id')";
			}

			$rs = $this->dbh->Query($sql);                           
			//$rs = db_query($sql);
		}

		return TRUE;
	}
	
	function destroy( $id ) 
	{
		if ($this->cache)
		{
			$this->cache->remove($id);
		}
		else if ($this->dbh)
		{
			// Build query
			//$newid = mysql_real_escape_string($id);
			$newid = $this->dbh->Escape($id);
			$sql = "DELETE FROM sessions WHERE session_id='$newid'";

			$rs = $this->dbh->Query($sql);                           
			//db_query($sql);
		}

		return TRUE;
	}

	function gc() 
	{
		// Garbage Collection
		
		if ($this->cache)
		{
			// Will eventually purge automatically
		}
		else if ($this->dbh)
		{
			// Build DELETE query.  Delete all records who have passed the expiration time
			$sql = "DELETE FROM sessions WHERE expires<EXTRACT('epoch' FROM NOW());";
			$rs = $this->dbh->Query($sql);                           
		}

		// Always return TRUE
		return true;
	}
}

$SESS = new CSessions();
session_start();
?>
