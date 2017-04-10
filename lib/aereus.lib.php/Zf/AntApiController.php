<?php
/**
* Controller class used for dealing with ANTAPI actions.
*
* This can be used simply bu creating a controller that extends this class.
*
* @category  Aereus_Zf
* @package   Aereus_Zf_AntApiController
* @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
*/

/**
* Class to be extended
*/
class Aereus_Zf_AntApiController extends Zend_Controller_Action
{
    public function init()
    {
        // Get configuration
        $config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);

        // Get api & settings
        $this->antapi = Aereus_Zf_AntApi::getInstance();
        $this->antserver = $config->antapi->server;

        // Get cache
        $this->cache = Zend_Registry::get('cache');

        // Image path
        $this->apiCachePath = APPLICATION_PATH . "/../data/cache/antapi";
        if (!file_exists($this->apiCachePath))
            mkdir($this->apiCachePath);

    }

    /**
    * Layout and views will not be utilized for any actions in this controller
    */
    public function preDispatch() 
    {
        // Disable layout and views
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
    *    N index
    */
    public function indexAction()
    {
    }

    /**
    * Refresh content feed cache.
    *
    * All posts cached from a content feed should be tagged with feed_<feed_id> so
    * the ANT content manager can call this function on post update effectively "pushing" changes
    */
    public function refreshAction()
    {
        $feed_id = $this->_getParam("fid");

        if ($feed_id)
        {
			// TODO: we are purging all because the tag delete is failing for some reason
			$this->cache->clean(Zend_Cache::CLEANING_MODE_ALL);
			
			/*
            $this->cache->remove(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            array('feed_'.$feed_id)
            );
			 */
        }

        echo "1";
    }

    /**
    *    Get image from afs - then cache
    */
    public function imageAction()
    {
        // File id
        $fid = $this->_getParam("fid");
        $fname = $fid;

        if (!$fid)
            return "";

        // Optional width
        $width = $this->_getParam("w");
        if ($width)
            $fname .= "_".$width;

        // Optional height
        $height = $this->_getParam("h");
        if ($height)
            $fname .= "_".$height;

        $path = "http://".$this->antserver.'/files/images/'.$fid;
        if ($width)
            $path .= "/".$width;
        if ($height)
            $path .= "/".$height;

        if (!$this->checkForCachedFile($fname))
            $this->downloadFileToCache($path, $fid, $fname);

        $this->streamCachedFile($fname, "inline");
    }

    /**
    *    Get image from afs - then cache
    */
    public function filesAction()
    {
        // File id
        $fid = $this->_getParam("fid");
        $fname = $fid;

        if (!$fid)
            return "";

        $path = "http://".$this->antserver.'/files/images/'.$fid;
        if ($width)
            $path .= "/".$width;
        if ($height)
            $path .= "/".$height;

        if (!$this->checkForCachedFile($fname))
            $this->downloadFileToCache($path, $fid, $fname);

        $this->streamCachedFile($fname);
    }

    /**
    * Callback used by curl to set headers from a downloaded file
    */
    public function curlHeaderCallback($ch, $string)
    {
        $len = strlen($string);
        if( !strstr($string, ':') )
        {
            $this->response = trim($string);
            return $len;
        }
        list($name, $value) = explode(':', $string, 2);
        if( strcasecmp($name, 'Content-Disposition') == 0 )
        {
            $parts = explode(';', $value);
            if( count($parts) > 1 )
            {
                foreach($parts AS $crumb)
                {
                    if( strstr($crumb, '=') )
                    {
                        list($pname, $pval) = explode('=', $crumb);
                        $pname = trim($pname);
                        if( strcasecmp($pname, 'filename') == 0 )
                        {
                            $this->headers['filename'] = basename(str_replace(array("'", '"'), '', trim($pval)));
                        }
                    }
                }
            }
        }

        $this->headers[$name] = trim($value);

        return strlen($string);
    }

    /**
    * Download and cache remote file
    */
    public function downloadFileToCache($remotePath, $fid, $localfname)
    {
        // Meta data
        $metaData = array("fid"=>$fid);

        // Create headers array
        $this->headers = array();

        // Initialize curl session
        $ch = curl_init();

        // Set the URL of the page or file to download.
        curl_setopt($ch, CURLOPT_URL, $remotePath);

        // Create a new file
        $fp = fopen($this->apiCachePath."/".$localfname, 'w');
        $fpMeta = fopen($this->apiCachePath."/".$localfname.".meta", 'w');

        // Ask cURL to write the contents to a file
        curl_setopt($ch, CURLOPT_FILE, $fp);

        // Set callback for handling headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'curlHeaderCallback'));

        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Execute the cURL session
        curl_exec($ch);

        // set file name if none exists
        if (!$this->headers['filename'] && $this->headers['Content-Type'])
        {
            list($maj, $min) = explode("/", $this->headers['Content-Type']);
            $this->headers['filename'] = $maj.".".$min;
        }

        // Set meta-data to be saved locally
        $metaData['filename'] = $this->headers['filename'];
        $metaData['content-type'] = $this->headers['Content-Type'];
        $metaData['timestamp'] = time();
        $metaData['size'] = sizeof($this->apiCachePath."/".$localfname);

        $json = Zend_Json::encode($metaData);
        fwrite($fpMeta, $json, strlen($json));

        //echo $json;
        //echo var_export($this->headers, true);

        // Close cURL session and file
        curl_close ($ch);
        fclose($fp);
        fclose($fpMeta);
    }

    /**
    * Check if local file exists and is fresh
    *
    * @param string $fname = the local file name to check for
    * @param int $expires = the number of seconds until marked stale - default = 2 days
    *
    * @return bool = use local cached file
    */
    public function checkForCachedFile($fname, $expires=172800)
    {
        // Obviously if the file is missing, return false
        if (!file_exists($this->apiCachePath."/".$fname) || !file_exists($this->apiCachePath."/".$fname.".meta"))
            return false;

        // Now check timestamp to purge stale files
        if ($expires)
        {
            $ftime = @filemtime($this->apiCachePath."/".$fname);
            $dif =  time() - $ftime;
            if ($dif > $expires)
            {
                @unlink($this->apiCachePath."/".$fname);
                @unlink($this->apiCachePath."/".$fname.".meta");
                return false;
            }
        }

        return true;
    }

    /**
    * Stream local file contents
    */
    public function streamCachedFile($fname, $disposition="attachment")
    {
        // Get meta data
        if (file_exists($this->apiCachePath."/".$fname.".meta"))
        {
            $buf = file_get_contents($this->apiCachePath."/".$fname.".meta");
            $metaData = Zend_Json::decode($buf);

            header("Content-Disposition: $disposition; filename=\"".$metaData['filename']."\"");
            header("Content-Type: ".$metaData['content-type']);
            header("Content-Length: ".filesize($this->apiCachePath."/".$fname));
            //header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

			$cache_seconds = 172800; // 2 days
			header("Cache-Control: max-age=$cache_seconds");
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
			header('Pragma: cache');
        }

        // Get file contents
        if (file_exists($this->apiCachePath."/".$fname))
        {
			// Check if cached
			global $_SERVER;
			if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER))
			{
				$if_modified_since=strtotime(preg_replace('/;.*$/','',$_SERVER["HTTP_IF_MODIFIED_SINCE"]));

				if($if_modified_since >= filemtime($this->apiCachePath."/".$fname))
				{
					header("HTTP/1.0 304 Not Modified");
					exit();
				}
			}

			readfile($this->apiCachePath."/".$fname);
			/*
            $handle = fopen($this->apiCachePath."/".$fname, "r");
            if ($handle)
            {
                while (!feof($handle))
                    echo fread($handle, 8192);

                fclose($handle);
            }
			*/
        }
		else
		{
			header("HTTP/1.0 404 Not Found");
		}
    }

    /**    
    *  Handles feeedback data
    *
    * @post data coming from /lib/js/Feedback.js
    * @config data is set in /application/configs/application.ini e.g. antapi.feedback.ownerId
    *
    */
    public function feedbackAction() 
    {
        // Creates description input by looping thru post data
        foreach($_POST as $key=>$value)
        {            
            switch($key){
                case "project_id":
                case "type_id":
                    break;
                default:
                    $description .= "$key: $value <br />";
                    break;
            }
        }

        // Get the customer id
        $email = $_POST["Email"];
        if(!empty($email))
        {
            $customerId = 0;
            // Checks the customer object if email post data already exists
            $olist = $this->antapi->getObjectList("customer");                
            $olist->addCondition("and", "email", "is_equal", $email);
            $olist->addCondition("or", "email2", "is_equal", $email);
            $olist->getObjects(0, 1);
            $num = $olist->getNumObjects();
            if($num>0)
            {
                $objCustomer = $olist->getObject(0);        
                $customerId = $objCustomer->getValue('id');
            }
        }

        // Get configuration
        $config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);

        // Prepares the data to be saved in case object
        $obj = $this->antapi->getObject("case");
        $obj->setValue("description", $description);
        $obj->setValue("title", "Feedback: " . $_POST["Subject"]);

        if($config->antapi->feedback->ownerId)
            $obj->setValue("owner_id", $config->antapi->feedback->ownerId);
        if($config->antapi->feedback->projectId)
            $obj->setValue("project_id", $config->antapi->feedback->projectId);
        if($config->antapi->feedback->typeId)
            $obj->setValue("type_id", $config->antapi->feedback->typeId);
        if($customerId>0)
            $obj->setValue("customer_id", $customerId);

        // Saves the data in case object
        return $obj->save();
    }

    /**
     * Pushes the updates to the local store
     */
    public function syncObjAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $api = new Aereus_Zf_AntApi();
        
        $objectId = $this->_getParam("oid");
        $objectType = $this->_getParam("obj_type");
        
        if(!empty($objectType) && $objectId)
        {
            $obj = $api->getObject($objectType);
			$ret = $obj->syncLocalWithAnt("", array(), $objectId);
			echo $ret;
			/*
            $obj->getLocalStore()->addCondition("and", "id", "is_equal", $objectId);
            $obj->syncLocalWithAnt();
			 */
        }
    }
    
    /**
     * Authenticates the user
     */
    public function entereditAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        // Creates cookies to allow user enter edit mode
        $expireTime = time() + 3600;
        setcookie("cms_edit", "1", $expireTime, "/");
        
        $page = $this->_getParam("page");
        if(empty($page))
            $page = "/";
        
        $this->_redirect($page);
    }
    
    /**
     * Removes the cookies of authenticated user
     */
    public function deactivateEditAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $oneHourAgo = time() - 3600;
        setcookie("cms_edit", "", $oneHourAgo, "/");
        
        $ret = true;
        echo json_encode($ret);
    }
    
    /**
     * Gets the content post data
     */
    public function postEditAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $postId = $this->_getParam("id");
        $dataType = $this->_getParam("datatype");
        $cmsId = $config->antapi->cmsSiteId;
        switch($dataType)
        {
            case "cms_snippet":
                $cmsObj = $this->antapi->getCms($cmsId);
                $post = $cmsObj->getSnippet($postId);
                $title = null;
                break;
            case "cms_page":
                $cmsObj = $this->antapi->getCms($cmsId);
                $post = $cmsObj->getPageById($postId);
                $title = $post->getValue("name");
                break;
            default:
            case "content_feed_post":
                $cfeed = $this->antapi->getContentFeed();
                $post = $cfeed->getPostById($postId);
                $title = $post->getValue("title");
                break;
        }
        
        $data = utf8_encode($post->getValue("data"));
        
        if($this->_getParam("striptags") == 1)
            $data = strip_tags($data);
            
        if($this->_getParam("substr"))
        {
            $data = substr($data, 0, $this->_getParam("substr"));
            $data .= " ...";
        }
        
        $ret = array("title" => $title, "data" => $data);
        echo json_encode($ret);
    }
    
    /**
     * Includes the CMS Script in the header
     */
    public function setControllerCms()
    {
        if(isset($_COOKIE['cms_edit']) && $_COOKIE['cms_edit'])
        {
            //$view = new Zend_View();
            $cmsFile = 'http://' . $this->antserver . '/public/js/Cms.js?server=';            
            //$cmsFile = '/lib/js/Cms.js';
            
            // Append server query string to identify the server url
            $cmsFile .= '?server=' . $this->antserver;
            
            // Append the javascript file
            $this->view->headScript()->appendFile($cmsFile);
        }
    }
}
