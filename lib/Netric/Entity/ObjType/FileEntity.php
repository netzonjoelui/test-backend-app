<?php
/**
 * Provides extensions for the File object
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Folder for entity
 */
class FileEntity extends Entity implements EntityInterface
{
    /**
     * File handle reference
     *
     * @var resource
     */
    private $fileHandle = null;

    /**
     * Clean-up file handle if not closed
     */
    public function __destruct()
    {
        if ($this->fileHandle) {
            @fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Called right before the endity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeDeleteHard(AccountServiceManagerInterface $sm)
    {
        $fileStore = $sm->get("Netric/FileSystem/FileStore/FileStore");

        // When this file gets purged we should delete the raw data from the fileStore
        $fileStore->deleteFile($this);
    }

    /**
     * Get a file handle if set
     *
     * @return resource
     */
    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    /**
     * Set a file handle
     *
     * @var resource $fileHandle
     */
    public function setFileHandle($fileHandle)
    {
        $this->fileHandle = $fileHandle;
    }

    /**
     * Get the file type from the extension
     *
     * @return string
     */
    public function getType()
    {
        $ext = substr($this->getValue("name"), strrpos($this->getValue("name"), '.') + 1);
        return strtolower($ext);
    }

    /**
     * Get a mime type from the extension
     */
    public function getMimeType()
    {
        $type = $this->getType();

        switch ($type)
        {
            case 'jpg':
            case 'jpeg':
                return "image/jpeg";
            case 'png':
                return "image/png";

            default:
                return "application/octet-stream";
        }

    }
}
