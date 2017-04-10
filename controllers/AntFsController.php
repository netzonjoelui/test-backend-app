<?php
/**
 * Ant File Sysmte actions
 *
 * @category  Controllers
 * @package   AntFs
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../lib/AntFs.php');

/**
 * Class for controlling the online file system in ANT
 */
class AntFsController extends Controller
{
    /**
     * Refrence to AntFs class
     *
     * @var AntFs
     */
    public $antfs = null;

    /**
     * Initialize globals
     */
    public function init()
    {
        $this->antfs = new AntFs($this->ant->dbh, $this->user);
    }

    /**
     * Create new folder
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function newFolder($params)
    {
        $path = $params['path'];
        $name = $params['name'];


        if ($path && $name)
        {
            if ($path != "/")
                $path .= "/";

            $path .= $name;

            $folder = $this->antfs->openFolder($path, false);
            if ($folder && $params['useexist']!=1)
            {
                $ret = array("error"=>"A folder with that name already exists");
            }
            else
            {
                $folder = $this->antfs->openFolder($path, true);
                $ret = array("folder_id" => $folder->id, "folder_name" => $folder->name);
            }
        }
        else
        {
            $ret = array("error"=>"path is a required param");
        }

        if ($params['output'] == "xml")
            return $this->sendOutputXml($ret);
        else
            return $this->sendOutputJson($ret);
    }

    /**
     * Move files and/or folders to another folder
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function move($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $toFolder = $this->antfs->openFolderById($params['move_to_id']);

        if ($toFolder && $params['obj_type'] && (is_array($params['objects']) || $params['all_selected']))
        {
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $processed = $olist->processFormConditions($params);

            if ($processed) // make sure we don't move entire list with no form filters
            {
                $olist->getObjects();
                $num = $olist->getNumObjects();
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $olist->getObject($i);

                    if ($obj->move($toFolder))
                        $ret[] = $obj->id;
                    else
                        $ret[] = -1;

                    $olist->unsetObject($i);
                }
            }

            // Handle optional browseby deletions
            if ($params['browsebyfield'])
            {
                $objDef = new CAntObject($dbh, $params['obj_type'], null, $this->user);
                $field = $objDef->fields->getField($params['browsebyfield']);

                foreach ($params['objects'] as $bid)
                {
                    // Get only browse objects
                    if (strpos($bid, "browse:") !== false)
                    {
                        $parts = explode(":", $bid);

                        // Use special folder delete
                        if ($field['subtype'] == 'folder')
                        {
                            // Open the folder by id then delete
                            $subfolder = $this->antfs->openFolderById($parts[1]);
                            if ($subfolder->getValue("f_system") != 't')
                            {
                                if ($subfolder->move($toFolder))
                                {
                                    $ret[] = $bid;
                                }
                            }
                            else
                            {
                                $ret[] = -1;
                            }
                        }
                    }
                }
            }
        }
        else
            $ret = array("error"=>"obj_type and objects are required params");

        return $this->sendOutputJson($ret);
    }

    /**
     * Upload a file into AntFs
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function upload($params)
    {
        // Make sure we have the resources to upload this file
        ini_set("max_execution_time", "7200");
        ini_set("max_input_time", "7200");

        $folder = null;
        $ret = array();

        // If folderid has been passed the override the text path
        if ($params['folderid'])
            $folder = $this->antfs->openFolderById($params['folderid']);
        else if ($params['path'])
            $folder = $this->antfs->openFolder($params['path'], true);

        // Check if this is originating from a partner sync collection
        if ($params['object_sync_collection'])
            $folder->skipObjectSyncStatCol = $params['object_sync_collection'];

        if ($folder)
        {
            // Check if the posted files are coming from the new client/web which uses the FormData
            if(isset($_FILES['uploadedFiles']))
            {
                $uploadedFiles = array();

                // Map the uploadedFiles and mimic the structure of the normal $_FILES
                foreach($_FILES['uploadedFiles'] as $type=>$fileData)
                {
                    // Get the actual data of the uploaded files.
                    foreach($fileData as $idx=>$data)
                    {
                        $uploadedFiles[$idx][$type] = $data;
                    }
                }
            }
            else
            {

                // If data is posted from the old flash uploader, then just pass the $_FILES to the $uploadedFiles variable
                $uploadedFiles = $_FILES;
            }

            foreach($uploadedFiles as $tagname=>$file)
            {
                // First move to ANT tmp.
                // This is mostly for security to this function always handles uploaded files
                // move_uploaded_file will only move files that were just uploaded to this server with POST
                $tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
                if (!file_exists($tmpFolder))
                    @mkdir($tmpFolder, 0777);
                $tmpFile = tempnam($tmpFolder, "up");
                move_uploaded_file($file['tmp_name'], $tmpFile);

                // Import into AntFs
                $file = $folder->importFile($tmpFile, $file["name"], $params['fileid']);
                if ($file)
                    $ret[] = array("id"=>$file->id, "name"=>$file->getValue("name"), "ts_updated"=>$file->getValue("ts_updated"));
                else
                    $ret[] = -1;

                // Cleanup
                unlink($tmpFile);
            }

            if (!count($_FILES))
                $ret = array("error"=>"No files were uploaded");
        }
        else
        {
            $ret = array("error"=>"Could not open the path specified");
        }

        if ($params['output'] == "xml")
            return $this->sendOutputXml($ret);
        else
            return $this->sendOutputJson($ret);
    }

    /**
     * Retrieve source for a file preview
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getFilePreview($params)
    {
        if (!$params['fid'])
            return $this->sendOutputJson(array("error"=>"A valid file id must be provided to generate a preview"));

        $ret = array();


        $file = $this->antfs->openFileById($params['fid']);

        $ret["urlDownload"] = $this->ant->getAccBaseUrl()."/antfs/".$params['fid'];

        if ($file->isImage())
        {
            $ret["urlImage"] = $this->ant->getAccBaseUrl()."/controller/AntFs/streamImage?w=800&fid=".$params['fid'];
        }
        else
        {
            switch ($file->getExt())
            {
                case 'log':
                case 'txt':
                case 'text':
                case 'html':
                case 'htm':
                    // Send contents if under 10m
                    if ($file->size <= 10*1000*1000)
                    {
                        $ret['html'] = $file->read();

                        if ("htm" != $file->getExt() && "html" != $file->getExt())
                            $ret['html'] = str_replace("\n", "<br />", $ret['html']); // Use new lines
                    }
                    break;
                case 'pdf':
                    $ret['html'] = '<object data="/antfs/'.$file->id.'" type="application/pdf" width="100%" height="400">';
                    $ret['html'] .= '<a href="/antfs/'.$file->id.'">'.$file->getValue('name').'</a>';
                    $ret['html'] .= '</object>';
                    break;
            }
        }

        return $this->sendOutputJson($ret);
    }

    /**
     * Retrieve source for a file preview
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getPathFromId($params)
    {
        if (!$params['folder_id'])
            return $this->sendOutputJson(array("error"=>"A valid file id must be provided to generate a preview"));

        $folder = $this->antfs->openFolderById($params['folder_id']);
        $ret = $folder->getFullPath();

        return $this->sendOutputJson($ret);
    }

    /**
     * Download a file
     *
     * This streams the contents of this file
     */
    public function downloadFile($params)
    {
        if (!$params['fid'])
            echo ""; // File not found

        $file = $this->antfs->openFileById($params['fid']);

        if (!$file)
        {
            echo "";
            return;
        }

        // Set the file name, may pass alternate name through param 'fname'
        if ($params['fname'])
            $filename = $params['fname'];
        else if ($file)
            $filename = $file->getValue("name");

        $filename = $file->escapeFilename($filename);

        // Check if we are loading a previous revision
        if ($params['rev'])
        {
            $revData = $file->getRevisionData();
            if (is_array($revData[$params['rev']]) && count($revData[$params['rev']])>0)
            {
                // Reload with old revision data
                $file->load(null, $revData[$params['rev']]);
            }
        }

        if (!$filename)
            $filename = "Untitled";

        if ($param['stream'])
        {
            header("Content-Disposition: inline; filename=\"".str_replace("'", '', $filename)."\"");
            header("Content-Type: " . $file->getContentType());
        }
        else
        {
            header("Content-Disposition: attachment; filename=\"".str_replace("'", '', $filename)."\"");
            header("Content-Type: application/octet-stream");
        }

        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: private", false); // required for certain browsers
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Transfer-Encoding: binary");
        if ($file->getValue("file_size"))
            header("Content-Length: " . $file->getValue("file_size"));

        // Stream contents of file to browser
        $file->stream();
    }

    /**
     * Stream image
     *
     * This streams the contents of this file
     */
    public function streamImage($params)
    {
        if (!$params['fid'])
            return;

        $cache_seconds = 172800; // 2 days
        $maxWidth = $params['w'];
        $maxHeight = $params['h'];

        // If we are not resizing then just stream the raw file
        if (!$maxWidth && !$maxHeight)
        {
            $params['stream'] = '1';
            return $this->downloadFile($params);
        }

        $file = $this->antfs->openFileById($params['fid']);

        if (!$file)
            return;

        /*
        // Create temp resied thumbnail
        $thumbFile = $file->resizeImage($maxWidth, $maxHeight);

        // Make sure we resized successfully
        if (!$thumbFile || !file_exists($thumbFile))
            return;
         */

        // Set the file name, may pass alternate name through param 'fname'
        if ($params['fname'])
            $filename = $params['fname'];
        else
            $filename = $file->getValue("name");

        $filename = $file->escapeFilename($filename);

        if (!$filename)
            $filename = "Untitled";
        header("Content-Disposition: inline; filename=\"".str_replace("'", '', $filename)."\"");
        header("Content-Type: " . $file->getContentType());
        header("Cache-Control: max-age=$cache_seconds");
        header('Pragma: cache');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');
        header("Content-Transfer-Encoding: binary");

        // Check if the file has been modified since the last time it was downloaded
        global $_SERVER;
        if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && $file->getValue("ts_updated"))
        {
            if(strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) == strtotime($file->getValue("ts_updated")))
            {
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', strtotime($file->getValue("ts_updated"))).' GMT', true, 304);
                exit();
            }
        }

        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($file->getValue("ts_updated"))) . ' GMT');

        // If resizing then download locally and resize
        if ($maxWidth || $maxHeight)
        {
            // Create temp resied thumbnail
            $thumbFile = $file->resizeImage($maxWidth, $maxHeight);

            header("Content-Length: " . filesize($thumbFile));
            flush(); // force headers flush for faster response

            // Send browser to client
            readfile($thumbFile);

            // Cleanup
            unlink($thumbFile);
        }
        else
        {
            // No resize, just stream raw file
            if ($file->getValue("file_size"))
                header("Content-Length: " . $file->getValue("file_size"));
            flush(); // force headers flush for faster response

            $file->stream();
        }
    }

    /**
     * Saves the attachment file using the new AntFs
     *
     * This streams the contents of this file
     */
    public function saveAttachment($params)
    {
        $folder = null;
        $ret = array();

        $typeName = $params['typeName'];
        $id = $params['id'];
        $attachedFiles = $params['attachedFiles'];

        if(empty($attachedFiles))
            return;

        $path = "system/objects/$typeName/$id";
        $folder = $this->antfs->openFolder($path, true);
        if ($folder)
        {
            //make sure upload data is in array format
            if(!is_array($attachedFiles))
                $attachedFiles = explode(",", $attachedFiles);

            foreach ($attachedFiles as $fid)
            {
                $file = $this->antfs->openFileById($fid);
                if($file->move($folder))
                {
                    $ret[] = array("id"=>$file->id, "name"=>$file->getValue("name"), "ts_updated"=>$file->getValue("ts_updated"), "status" => "moved");
                }
                else
                {
                    $ret[] = array("id"=>$file->id, "name"=>$file->getValue("name"), "ts_updated"=>$file->getValue("ts_updated"), "status" => "purged");
                    $file-removeHard();
                }
            }
        }
        else
        {
            $ret = array("error"=>"Could not open the path specified");
        }

        return $this->sendOutputJson($ret);
    }

    /**
     * Get the attachments
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function getAttachment($params)
    {
        $folder = null;
        $ret = array();

        $typeName = $params['typeName'];
        $id = $params['id'];

        $path = "system/objects/$typeName/$id";
        $folder = $this->antfs->openFolder($path);
        if ($folder)
        {
            $olist = new CAntObjectList($this->ant->dbh, "file", $this->user);
            $olist->addCondition("and", "folder_id", "is_equal", $folder->id);
            $olist->addOrderBy("name");
            $olist->getObjects(0, 1000);
            for ($i = 0; $i < $olist->getNumObjects(); $i++)
            {
                $subfolder = $olist->getObject($i);

                $ret[] = array(
                    "id"        => $subfolder->id,
                    "icon"        => $subfolder->getIconName(),
                    "name"        => $subfolder->getValue('name')
                );
            }
        }
        else
        {
            $ret = array("error"=>"Could not open the path specified");
        }

        return $this->sendOutputJson($ret);
    }

    /**
     * Remove Attachment
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function removeAttachment($params)
    {
        $dbh = $this->ant->dbh;
        $id = $params['id'];

        if ($id > 0)
        {
            $file = $this->antfs->openFileById($id);
            if($file->removeHard())
                $ret = 1;
            else
                $ret = array("error"=> "Error while tring to delete attachment!");
        }
        else
            $ret = array("error"=> "File Id is a required param!");

        $this->sendOutputJson($ret);
    }
}
