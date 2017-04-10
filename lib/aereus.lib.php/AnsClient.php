<?php
/****************************************************************************
*	
*	Class:		AnsCLient
*
*	Purpose:	Aereus Network Storage Client
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*	Usage:
*				// Create Object
*				$ans = new AnsCLient("ans.aereus.com", "ant", "password");
*		
*				// Put a file by a URL
*				$url = "http://mydo.com/mydo.jpg";
*				$ans->putFileByUrl($url, "/", "myimage.jpeg", "image/jpg");
*
*				// Get URL to download the file
*				echo $ans->getFileUrl("myimage.jpeg", "/");
*
*	Settings:	1. $ALIB_ANS_SERVER="servername";	//optional
*				2. $ALIB_ANS_ACCOUNT="username";	//optional
*				3. $ALIB_ANS_PASS="userpassword";	//optional
*		
*****************************************************************************/
class AnsCLient
{
	var $server;
	var $account;
	var $password;
	var $lastError;
	var $protocolVer = 2; // Required to verify libary is up-to-date 

	function AnsCLient($server=NULL, $account=NULL, $password=NULL)
	{
		global $ALIB_ANS_SERVER, $ALIB_ANS_ACCOUNT, $ALIB_ANS_PASS;

        if(defined("ALIB_ANS_SERVER"))
            $alibServer = ALIB_ANS_SERVER;
        else if(isset($ALIB_ANS_SERVER))
            $alibServer = $ALIB_ANS_SERVER;
            
        if(defined("ALIB_ANS_ACCOUNT"))
            $alibAccount = ALIB_ANS_ACCOUNT;
        else if(isset($ALIB_ANS_ACCOUNT))
            $alibAccount = $ALIB_ANS_ACCOUNT;
            
        if(defined("ALIB_ANS_PASS"))
            $alibPass = ALIB_ANS_PASS;
        else if(isset($ALIB_ANS_PASS))
            $alibPass = $ALIB_ANS_PASS;
        
		$this->server = ($server) ? $server : $alibServer;
		$this->account = ($account) ? $account : $alibAccount;
		$this->password = ($password) ? $password : $alibPass;
	}

	function delete($file, $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_delete&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file);
		$response = file_get_contents($url);
		$retval = 0;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			$retval = $xml->retval;
			$this->lastError = $xml->message;
		}

		return (1 == intval($retval)) ? true : false;
	}

	function put($localfile, $name, $folder="/")
	{
		$retval = 0; // assume fail

		if (!file_exists($localfile))
			return false;

		$size = filesize($localfile);

		if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == "443")
			$url = "https://";
		else
			$url = "http://";
		$url .= $this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_upload&folder=".rawurlencode($folder);
		$url .= "&filename=".rawurlencode($name)."&size=$size";

		//echo "<pre>Sending to $url...</pre>";
		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => new CurlFile($localfile)));
		$response = curl_exec($ch);
		//echo "<pre>RESP: $url\n\n$response</pre>";
		if (curl_errno($ch)) 
		{
			$this->lastError = curl_error($ch);
		} 
		curl_close($ch);

		// Parse response
		if ($response)
		{
			$xml = simplexml_load_string($response);
			$retval = $xml->retval;
			if ($xml->message)
				$this->lastError = $xml->message;
			else
				$this->lastError = $retval;
		}

		return (1 == intval($retval)) ? true : false;
	}

	function fileSize($file, $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_size&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file);
		
		$response = file_get_contents($url);

		$retval = false;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			$retval = intval(rawurldecode($xml->retval));
			$this->lastError = rawurldecode($xml->message);
		}
		return $retval;
	}

	function exists($file, $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_exists&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file);
		
		$response = file_get_contents($url);

		$retval = false;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			if (rawurldecode($xml->retval) == "1")
				$retval = true;
			else
				$this->lastError = rawurldecode($xml->message);
		}
		return $retval;
	}

	function rename($file, $newfile)
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_move&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&newfile=".rawurlencode($newfile);
		
		$response = file_get_contents($url);

		$retval = false;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			if ($xml)
			{
				if (rawurldecode($xml->retval) == "1")
					$retval = true;
				else
					$this->lastError = rawurldecode($xml->message);
			}
			else
			{
				$this->lastError = $response;
			}
		}
		return $retval;
	}

	function getImageResizedUrl($file, $folder="/", $stream=1, $iw=null, $ih=null)
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=image_download_resized&folder=".rawurlencode($folder)."&file=".rawurlencode($file);
		$url .= "&stream=".$stream;

		if ($iw)
			$url .= "&iw=$iw";
		if ($ih)
			$url .= "&ih=$ih";

		return $url;
	}

	function getFileUrl($file, $folder='/')
	{
		/*
		 * Work variables
		 * direct = if set then direct download
		 * filename = urlencoded
		 * ctype = urencoded content-type like image/jpeg
		 * dispostion = either 'attachment' or 'inline'
		 * file_size = size in bytes of the file
		 */

		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&pver=".$this->protocolVer;
		$url .= "&function=file_download&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file);
		return $url;
	}

	/*
	function putFileByUrl($geturl, $name, $type, $key="", $folder="", $size="")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&content_type=".base64_encode($type);
		$url .= "&function=file_upload_url&folder=".base64_encode($folder)."&geturl=".base64_encode($geturl);
		$url .= "&filename=".base64_encode($name)."&key=".rawurlencode($key)."&size=$size";

		$response = file_get_contents($url);

		$retval = null;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			$retval = rawurldecode($xml->retval);
			$this->lastError = rawurldecode($xml->message);
		}
		return $retval;
	}
	 */

	/*
	function fileRenameKey($file, $key="", $folder="/", $newkey="")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_rename_key&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key)."&newkey=".rawurlencode($newkey);
		
		//echo $url."\n";
		$response = file_get_contents($url);

		$retval = false;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			if ($xml)
			{
				if (rawurldecode($xml->retval) == "1")
					$retval = true;
				else
					$this->lastError = rawurldecode($xml->message);
			}
			else
			{
				$this->lastError = $response;
			}
		}
		return $retval;
	}
	 */
}
?>
