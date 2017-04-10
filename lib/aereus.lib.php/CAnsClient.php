<?php
/****************************************************************************
*	
*	Class:		CAnsCLient
*
*	Purpose:	Aereus Network Storage Client
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*	Usage:
*				// Create Object
*				$ans = new CAnsCLient("ans.aereus.com", "ant", "password");
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
class CAnsCLient
{
	var $server;
	var $account;
	var $password;
	var $lastError;

	function CAnsCLient($server=NULL, $account=NULL, $password=NULL)
	{
		global $ALIB_ANS_SERVER, $ALIB_ANS_ACCOUNT, $ALIB_ANS_PASS;

		if(defined("ALIB_ANS_SERVER"))
            $alibServer = ALIB_ANS_SERVER;
        else if(isset($ALIB_ANS_SERVER))
            $alibServer = $ALIB_ANS_SERVER;
            
        if(defined("ALIB_ANS_ACCOUNT"))
            $alibAccount = ALIB_ANS_ACCOUNT;
        else if(isset($ALIB_ANS_SERVER))
            $alibAccount = $ALIB_ANS_SERVER;
            
        if(defined("ALIB_ANS_PASS"))
            $alibPass = ALIB_ANS_PASS;
        else if(isset($ALIB_ANS_PASS))
            $alibPass = $ALIB_ANS_PASS;
        
        $this->server = ($server) ? $server : $alibServer;
        $this->account = ($account) ? $account : $alibAccount;
        $this->password = ($password) ? $password : $alibPass;
	}

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

	function deleteFile($file, $key="", $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_delete&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key);
		
		$response = file_get_contents($url);

		$retval = null;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			$retval = $xml->retval;
			$this->lastError = $xml->message;
		}
		return $retval;
	}

	function putFile($localfile, $name, $type, $key="", $folder="")
	{
		$retval = false;
		
		if (!file_exists($localfile))
			return false;

		$size = filesize($localfile);

		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password)."&content_type=".rawurlencode($type);
		$url .= "&function=file_upload&folder=".rawurlencode($folder);
		$url .= "&filename=".rawurlencode($name)."&key=".rawurlencode($key)."&size=$size";
		$response = file_get_contents($url);
		if ($response)
		{
			$xml = simplexml_load_string($response);
			$post_url =  rawurldecode($xml->retval);
			$post_url .= "&account=".$this->account."&p=".md5($this->password)."&content_type=".rawurlencode($type);
			$post_url .= "&function=file_upload&folder=".rawurlencode($folder);
			$post_url .= "&filename=".rawurlencode($name)."&key=".rawurlencode($key)."&size=$size";

			//echo "Sending to $post_url...\n";
			$ch = curl_init();
			//curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_URL, $post_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($ch, CURLOPT_INFILESIZE, $size);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array("file"=>"@".$localfile));
			$retval = curl_exec($ch);
			if (curl_errno($ch)) 
			{
				$this->lastError = curl_error($ch);
			} 
			//echo "response $retval\n";
			//print(curl_exec($ch));
			curl_close($ch);
		}

		return $retval;
	}

	function getFileUrl($file, $key="", $folder="/", $stream=1)
	{
		global $_SERVER;
		if ($_SERVER['SERVER_PORT'] == "443")
			$url = "https";
		else
			$url = "http";
		$url .= "://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_download&folder=".rawurlencode($folder)."&file=".rawurlencode($file);
		$url .= "&stream=".$stream."&key=".rawurlencode($key);

		return $url;
	}

	function fileSize($file, $key="", $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_size&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key);
		
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
	function fileExists($file, $key="", $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_exists&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key);
		
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

	function fileVerify($file, $key="", $folder="/")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_verify&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key);
		//echo $url;
		
		$response = file_get_contents($url);

		$retval = false;

		if ($response)
		{
			$xml = simplexml_load_string($response);
			if (rawurldecode($xml->retval) == "1")
				$retval = true;
			else
			{
				$this->lastError = rawurldecode($xml->message);
			}
		}
		return $retval;
	}

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

	function fileMove($file, $key="", $folder="/", $newdir="")
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=file_move&folder=".rawurlencode($folder);
		$url .= "&file=".rawurlencode($file)."&key=".rawurlencode($key)."&newdir=".rawurlencode($newdir);
		
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

	function getImageResizedUrl($file, $key="", $folder="/", $stream=1, $iw=null, $ih=null)
	{
		$url = "http://".$this->server."/server.php?account=".$this->account."&p=".md5($this->password);
		$url .= "&function=image_download_resized&folder=".rawurlencode($folder)."&file=".rawurlencode($file);
		$url .= "&stream=".$stream."&key=".rawurlencode($key);

		if ($iw)
			$url .= "&iw=$iw";
		if ($ih)
			$url .= "&ih=$ih";

		return $url;
	}
}

