<?php
	class CPageCache
	{
		var $page;
		var $fileHandle;
		var $iSeconds;
		var $sDir;
		var $fExpired;
		
		function __construct($seconds, $cachedir = NULL, $checkpage = NULL)
		{
			if ($cachedir)
				$this->sDir = $cachedir;
			else
				$this->sDir = "cache";
			if ($checkpage) 
			{
				$this->page = $checkpage;
			}
			else
			{
				global $_SERVER;
				$parts = explode('/', $_SERVER['PHP_SELF']);
 				$ftocheck = $parts[count($parts) - 1];
				$this->page = $ftocheck;
			}
			
			$this->iSeconds = $seconds;

			$this->CheckLastUpdate();
		}
		
		function __destruct() 
		{
			// Output page here
			//echo "Called descturcor<br>";
			//$this->PrintFile();
			fclose($this->fileHandle);
		}
		
		function CheckLastUpdate()
		{
			$ftime = @filemtime($this->sDir."/".$this->page);
			$dif =  time() - $ftime;
			//echo $dif;
			if ($dif < $this->iSeconds)
			{
				$wstr = "r";
			}
			else
			{
				//echo "File modified<br>";
				if (file_exists($this->sDir."/".$this->page))
					$wstr = "w+";
				else
					$wstr = "x+";
			}
			
			$this->fileHandle = fopen($this->sDir."/".$this->page, $wstr);
			
			if ($dif > $this->iSeconds)
			{
				$this->$fExpired = true;
			}
			else
			{
				$this->$fExpired = false;
			}
		}
		
		function IsExpired()
		{
			return $this->$fExpired;
		}
		
		function BufWrite($bwrite)
		{
			fwrite($this->fileHandle, $bwrite);
		}
		
		function PrintFile()
		{
			fseek($this->fileHandle, 0);
			$contents = fgets($this->fileHandle);
			echo $contents;
		}
	}
?>