<?php
/**
 * Controller for FileSystem interactoin
 */
namespace Netric\Controller;

use Netric\Mvc;
use Netric\FileSystem\FileSystem;

/**
 * Class FilesController
 *
 * Handle API interactions with the FileSystem
 *
 * @package Netric\Controller
 */
class FilesController extends Mvc\AbstractAccountController
{
    /**
     * FileSystem instance
     *
     * @var FileSystem
     */
    private $fileSystem = null;

    /**
     * Path to local data directory for storing files
     *
     * @var string
     */
    private $dataPath = null;

    /**
     * Override initialization
     */
    protected function init()
    {
        // Get ServiceManager for the account
        $sl = $this->account->getServiceManager();

        // Get the FileSystem service
        $this->fileSystem = $sl->get("Netric/FileSystem/FileSystem");

        // Set the local dataPath from the system config service
        $config = $sl->get("Config");
        $this->dataPath = $config->data_path;
    }

    /**
     * Upload a new file to the filesystem via POST
     *
     * @return array Response
     */
    public function postUploadAction()
    {
        $request = $this->getRequest();

        // Make sure we have the resources to upload this file
		ini_set("max_execution_time", "7200");
		ini_set("max_input_time", "7200");

		$folder = null;
		$ret = array();

		// If folderid has been passed the override the text path

		if ($request->getParam('folderid'))
            $folder = $this->fileSystem->openFolderById($request->getParam('folderid'));
        else if ($request->getParam('path'))
            $folder = $this->fileSystem->openFolder($request->getParam('path'), true);

        // Could not create or get a parent folder. Return an error.
		if (!$folder)
            return $this->setOutput(array("error"=>"Could not open the folder specified"));

        $folderPath = $folder->getFullPath();

        // Process each file
        $files = $request->getParam('files');

        // List of files that just got uploaded
        $uploadedFiles = array();

        /**
         * When a file is uploaded it can be sent as 'input_name' or as 'input_name[]'
         */
        foreach ($files as $file)
        {
            /**
             * Check to see if input multiple was set (or multiple files were uploaded with the
             * same name) which will be represented as:
             * array(
             *	'filename' => array('file1name', 'file2name'),
             *	'filetype' => array('file1type', 'file2type'),
             * 	'tmp_name' => array('file1tmp', 'file2tmp'),
             *  'filesize' => array('100', '200'),
             * );
             *
             * This is really a poor design, but unfortunately it's how PHP handles multiple file
             * updates. We just convert it to a more sane format below where each file is it's own []
             * and the below code does not care what the form name is for the uplaoded file.
             *
             * @see http://php.net/manual/en/features.file-upload.multiple.php for more information
             */
            if (is_array($file['name']))
            {
                foreach ($file['name'] as $idx=>$filename)
                {
                    $uploadedFiles[] = array(
                        'name' => $file['name'][$idx],
                        'type' => $file['type'][$idx],
                        'tmp_name' => $file['tmp_name'][$idx],
                        'error' => $file['error'][$idx],
                        'size' => $file['size'][$idx],
                    );
                }
            }
            else
            {
                // Standard single file upload
                $uploadedFiles[] = $file;
            }
        }

        foreach($uploadedFiles as $uploadedFile)
        {
            /*
             * Make sure that the file was uploaded via HTTP_POST. This is useful to help
             * ensure that a malicious user hasn't tried to trick the script into working
             * on files upon which it should not be working--for instance, /etc/passwd.
             *
             * However, we will need to bypass this for unit tests which will be managed
             * with $this->testMode and will be set in the unit test and never anywhere else.
             */
            if (!is_uploaded_file($uploadedFile['tmp_name']) && !$this->testMode)
            {
                return $this->setOutput(
                    array(
                        "error"=>"Security Violation: " . $uploadedFile['tmp_name'] .
                        " was not uploaded via POST."
                    )
                );
            }

            // Import into netric file system
            $file = $this->fileSystem->importFile(
                $uploadedFile['tmp_name'], $folderPath, $uploadedFile["name"]
            );

            if ($file)
            {
                $ret[] = array(
                    "id" => $file->getId(),
                    "name" => $file->getValue("name"),
                    "ts_updated" => $file->getValue("ts_updated")
                );
            }
            else
            {
                $ret[] = -1;
            }

            // Cleanup
            unlink($uploadedFile['tmp_name']);
        }

        return $this->sendOutput($ret);
    }

    /**
     * PUT pass-through for uploading
     */
    public function putUploadAction()
    {
        return $this->postUploadAction();
    }
}
