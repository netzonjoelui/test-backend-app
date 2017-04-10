<?php
/*======================================================================================
	
	class:		CCache

	Purpose:	Cache data in a hash table, will abstract memcached

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Usage:		

	Variables:	1.	$ALIB_CACHE_DIR (defaults to /tmp, only used if memcached is not used)
				2. 	$ALIB_USEMEMCACHED (defaults to false)
				3. 	$ALIB_MEMCACHED_SVR (defaults to localhost)

======================================================================================*/
if (!isset($ALIB_USEMEMCACHED))
	$ALIB_USEMEMCACHED = false;
if (!isset($ALIB_MEMCACHED_SVR))
	$ALIB_MEMCACHED_SVR = "localhost";

/**
 * Global var used for local cache
 */
$g_cCache_Local_Vals = array();

class CCache
{
	/**
	 * String to cache dir
	 *
	 * @var string
	 */
	private $sDir = "";

	/**
	 * Handle to memcached server
	 *
	 * @var Memcached
	 */
	private $memCached = null;

	/**
	 * Store the single instance of Database 
	 */
    private static $m_pInstance;

	/**
	 * Use local
	 *
	 * If set to true this class will keep cached values stored locally in an
	 * assoc array. This comes in handly for classes that need to call an object
	 * thousands of times and does not want to store values in a global
	 *
	 * @var bool
	 */
	private $useLocalCache = false;
	
	function CCache()
	{
		global $ALIB_CACHE_DISABLE;
        $cacheDisable = null;

        if(defined("ALIB_CACHE_DISABLE"))
            $cacheDisable = ALIB_CACHE_DISABLE;
        else if(isset($ALIB_CACHE_DISABLE))
            $cacheDisable = $ALIB_CACHE_DISABLE;
        
		if ($cacheDisable==true)
		{
			$this->memCached = null;
		}
		else
		{
			$this->setup();
		}
	}
	
	function __destruct() 
	{
	}

	/**
	 * Factory for singleton pattern
	 */
	public static function getInstance() 
	{ 
		if (!self::$m_pInstance) 
		{
			self::$m_pInstance = new CCache(); 
		}

		return self::$m_pInstance; 
	}

	/**
	 * Set whether or not to use the local variable cache before going to cache backend
	 *
	 * @var bool $use If true then first try to reference local global assoc array
	 */
	public function setUseLocal($use=true)
	{
		$this->useLocalCache = $use;
	}

	/**
	 * Set this cache class up to work with the right backend
	 */
	private function setup()
	{
		global $_SERVER, $ALIB_CACHE_DIR, $ALIB_USEMEMCACHED, $ALIB_MEMCACHED_SVR;

        if(defined("ALIB_CACHE_DIR"))
            $alibCacheDir = ALIB_CACHE_DIR;
        else if(isset($ALIB_CACHE_DIR))
            $alibCacheDir = $ALIB_CACHE_DIR;
            
        if(defined("ALIB_USEMEMCACHED"))
            $alibMemCached = ALIB_USEMEMCACHED;
        else if(isset($ALIB_USEMEMCACHED))
            $alibMemCached = $ALIB_USEMEMCACHED;
            
        if(defined("ALIB_MEMCACHED_SVR"))
            $alibMemSvr = ALIB_MEMCACHED_SVR;
        else if(isset($ALIB_MEMCACHED_SVR))
            $alibMemSvr = $ALIB_MEMCACHED_SVR;
        
		if($alibMemCached)
		{
			$this->memCached = new Memcache();

			// Make sure servers are not already added
			//if (!count($this->memCached->getServerList()))
			{
				if (defined("ANT_DEBUG_CLI")) echo "Setting servers to ".var_export($alibMemSvr, true)."...";
				if (is_array($alibMemSvr))
				{
					$servers = array();
					foreach ($alibMemSvr as $svr)
						$servers[] = array($svr, 11211, 100);

					$this->memCached->addServers($servers);
				}
				else
					$this->memCached->addServer($alibMemSvr, 11211);
				if (defined("ANT_DEBUG_CLI")) echo "[done]\n";
			}
			//else
			//{
		//		if (defined("ANT_DEBUG_CLI")) echo "Servers already set\n";
		//	}
		}
		else
		{
			$this->memCached = null;
            if(!empty($alibCacheDir))
				$this->sDir = $alibCacheDir;
			else
				$this->sDir = "/tmp/ccache";

			if (!file_exists($this->sDir))
				mkdir($this->sDir, 0777);
		}
	}

	// Expires is only used for local file caching with get
	function get($key, $expires=0)
	{
		global $ALIB_CACHE_DISABLE;
        $cacheDisable = null;
        
        if(defined("ALIB_CACHE_DISABLE"))
            $cacheDisable = ALIB_CACHE_DISABLE;
        else if(isset($ALIB_CACHE_DISABLE))
            $cacheDisable = $ALIB_CACHE_DISABLE;
        
		if ($cacheDisable==true)
			return false;

		if ($this->useLocalCache && isset($g_cCache_Local_Vals[$key]) && $g_cCache_Local_Vals[$key]!=null)
			return $g_cCache_Local_Vals[$key];

		if ($this->memCached)
		{
			$ret = $this->memCached->get($key);

			if ($this->useLocalCache)
				$g_cCache_Local_Vals[$key] = $ret;

			return $ret;
		}
		else
		{
			//$key = str_replace("/", "____", $key);
			$key .= ".cache"; // add to avoid problems with files and dirs haveing the same name

			if ($expires)
			{
				if(file_exists($this->sDir."/".$key))
				{
					$ftime = @filemtime($this->sDir."/".$key);
					$dif =  time() - $ftime;
					if ($dif > $expires)
					{
						$this->remove($key);
					}
				}
			}

			if(file_exists($this->sDir."/".$key))
			{
				$contents = @file_get_contents($this->sDir."/".$key);
				if ($contents)
					return unserialize($contents);
				else
					return false;
			}
		}

		return false;
	}
	
	function set($key, $val, $expires=0)
	{
		$this->add($key, $val, $expires);
	}

	function add($key, $val, $expires=0)
	{
		global $ALIB_CACHE_DISABLE;
        $cacheDisable = null;
        
        if(defined("ALIB_CACHE_DISABLE"))
            $cacheDisable = ALIB_CACHE_DISABLE;
        else if(isset($ALIB_CACHE_DISABLE))
            $cacheDisable = $ALIB_CACHE_DISABLE;
            
		if ($cacheDisable==true)
			return false;

		if ($this->useLocalCache && isset($g_cCache_Local_Vals[$key]))
			$g_cCache_Local_Vals[$key] = null;

		if ($this->memCached)
		{
			$this->memCached->set($key, $val, false, $expires);
		}
		else
		{
			if (!is_writable($this->sDir))
				return false;

			$key .= ".cache"; // add to avoid problems with files and dirs haveing the same name

			$this->checkFolders($key);
			//$key = str_replace("/", "____", $key);

			if(file_exists($this->sDir."/".$key))
				@unlink($this->sDir."/".$key);

			@file_put_contents($this->sDir."/".$key, serialize($val));
		}
	}

	function remove($key)
	{
		if ($this->memCached)
		{
			$this->memCached->delete($key);
		}
		else
		{
			$key .= ".cache"; // add to avoid problems with files and dirs haveing the same name

			//$key = str_replace("/", "____", $key);
			if(file_exists($this->sDir."/".$key))
				@unlink($this->sDir."/".$key);

			if (!is_dir($this->sDir."/".$key))
				$this->purgeEmptyFolders($key);
		}
	}

	function is_set($key)
	{
		if ($this->memCached)
		{
			return $this->memCached->get($key);
		}
		else
		{
			//$key = str_replace("/", "____", $key);
			$key .= ".cache"; // add to avoid problems with files and dirs haveing the same name

			return file_exists($this->sDir."/".$key);
		}
	}

	function purge()
	{
	}


	/*
	 * The below files are used to manage local folder cache
	 * --------------------------------------------------------*/
	function checkFolders($key)
	{
		$parts = explode("/", $key);
		if (count($parts)>1)
		{
			$tmp_path = $this->sDir;
			for ($i = 0; $i <= count($parts)-2; $i++)
			{
				if (!file_exists($tmp_path."/".$parts[$i]))
					@mkdir($tmp_path."/".$parts[$i]);

				$tmp_path = $tmp_path."/".$parts[$i];
			}
		}
	}

	function purgeEmptyFolders($key)
	{
		$parts = explode("/", $key);
		if (count($parts)>1)
		{
			$this->recursive_remove_directory($this->sDir."/".$parts[1], $parts);
		}
	}

	function recursive_remove_directory($directory, $arr, $ind=2)
	{
		// if the path has a slash at the end we remove it here
		if(substr($directory,-1) == '/')
		{
			$directory = substr($directory,0,-1);
		}

		// if the path is not valid or is not a directory ...
		if(!file_exists($directory) || !is_dir($directory))
		{
			// ... we return false and exit the function
			return FALSE;

			// ... if the path is not readable
		}
		else if(!is_readable($directory))
		{
			// ... we return false and exit the function
			return FALSE;

			// ... else if the path is readable
		}
		else
		{
			// we open the directory
			$handle = opendir($directory);

			// and scan through the items inside
			while (FALSE !== ($item = readdir($handle)))
			{
				// if the filepointer is not the current directory
				// or the parent directory
				if($item != '.' && $item != '..' && $item==$arr[$ind])
				{
					// we build the new path to delete
					$path = $directory.'/'.$item;

					// if the new path is a directory
					if(is_dir($path)) 
					{
						// we call this function with the new path
						$this->recursive_remove_directory($path, $arr, $ind+1);
					}
				}
			}

			// close the directory
			closedir($handle);

			if ($this->is_empty_dir($directory))
				rmdir($directory);

			// return success
			return TRUE;
		}
	}

	function is_empty_dir($dir)
	{
		if (($files = @scandir($dir)) && count($files) <= 2) 
		{
			return true;
		}
		return false;
	}
}
?>
