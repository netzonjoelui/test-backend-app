<?php
/**
 * File DataMapper for storing files to the local disk
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore;

use Netric\Error\Error;
use Netric\Entity\DataMapperInterface;
use Netric\FileSystem\FileStore\Exception;
use Netric\Entity\ObjType\FileEntity;

/**
 * Class LocalDataMapper
 * @package Netric\FileSystem\DataMapper
 */
class LocalFileStore implements FileStoreInterface
{
    /**
     * Array of errors encountered
     *
     * @var Error[]
     */
    private $errors = array();

    /**
     * Account/tennant ID
     *
     * @var int
     */
    private $accountId = null;

    /**
     * Path where netric will store data files
     *
     * @var string
     */
    private $dataPath = "";

    /**
     * Entity DataMapper for pulling revision data
     *
     * @var DataMapperInterface
     */
    private $entityDataMapper = null;

    /**
     * Class constructor
     *
     * @param string $accountId The unique id of the tennant's account
     * @param string $dataPath The local file path where netric stores data
     * @param DataMapperInterface $dataMapper An entity DataMapper for saving entities
     */
    public function __construct($accountId, $dataPath, DataMapperInterface $dataMapper)
    {
        $this->accountId = $accountId;
        $this->dataPath = $dataPath;
        $this->entityDataMapper = $dataMapper;
    }

    /**
     * Read and return numBypes (or all) of a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param null $numBytes Number of bytes, if null then return while file
     * @param null $offset Starting offset, defaults to current pointer
     * @return mixed
     * @throws Exception\FileNotFoundException if you try to read from a file that is not there
     */
    public function readFile(FileEntity $file, $numBytes = null, $offset = null)
    {
        // If file has not yet been opened, then open it
        if (!$file->getFileHandle())
        {
            $path = $this->getFullLocalPath($file);

            if (!$path)
                return false;

            if (file_exists($path))
            {
                $file->setFileHandle(fopen($path, 'rb'));
            }
            else
            {
                throw new Exception\FileNotFoundException("File '$path' does not exist");
            }
        }

        if ($offset)
            fseek($file->getFileHandle(), $offset);

        // If the user did not indicate the number of bytes to read then whole file
        if (!$numBytes)
        {
            $numBytes = $file->getValue("file_size");
        }

        if ($file->getFileHandle())
            return fread($file->getFileHandle(), $numBytes);
        else
            return false;
    }

    /**
     * Write data to a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param $dataOrStream $data Binary data to write or a stream resource
     * @return int number of bytes written and -1 if error (call getLastError for details)
     */
    public function writeFile(FileEntity $file, $dataOrStream)
    {
        $ret = -1;

        // Must be an exisitng file to write
        if (!$file->getId())
        {
            $this->errors[] = new Error(
                "Cannot write data a file that does not exist. Please save the file before writing."
            );
            return $ret;
        }

        // Get the local account root directory
        $accPath = $this->getAccountDirectory();
        $filePath = $this->getRelativeFileDirectory($file);
        $fullPath = $accPath . '/' . $filePath;

        // Write data to the new file
        if (is_resource($dataOrStream)) {
            $tmpFile = fopen($fullPath, 'w');
            while (!feof($dataOrStream)) {
                $buf = fread($dataOrStream, 2082);
                if ($buf) {
                    fwrite($tmpFile, $buf);
                }
            }
            fclose($tmpFile);
        } else {
            file_put_contents($fullPath, $dataOrStream);
        }

        $size = @filesize($fullPath);
        chmod($fullPath, 0777);

        // Update the file entity file size and most current storage path
        $file->setValue("file_size", $size);
        //$this->setValue("storage_path", $filePath);
        $file->setValue("dat_local_path", $filePath);
        // If set, then clear. Key will remain in saved revision.
        $file->setValue("dat_ans_key", "");
        $this->entityDataMapper->save($file);

        return $size;
    }

    /**
     * Upload a file to the data store
     *
     * @param FileEntity $file Meta-data Entity for the file
     * @param string $sourcePath Path of a local file
     * @return true on success, false on failure
     */
    public function uploadFile(FileEntity $file, $sourcePath)
    {
        $ret = true;

        // Must be an exisitng file to write
        if (!file_exists($sourcePath))
        {
            return $ret;
        }

        // Close the file handle if open
        if ($file->getFileHandle())
        {
            fclose($file->getFileHandle());
            $file->setFileHandle(null);
        }

        // If the filename was not yet set for this file then get from source
        if (!$file->getValue("name"))
        {
            if (strrpos($sourcePath, "/") !== false) // unix
                $parts = explode("/", $sourcePath);
            else if (strrpos($sourcePath, "\\") !== false) // windows
                $parts = explode("\\", $sourcePath);
            else
                $parts = array($sourcePath);

            // last entry is the file name
            $file->setValue("name", $parts[count($parts)-1]);
        }

        // Get the local account root directory
        $accPath = $this->getAccountDirectory();
        $fileIdPath = $this->getRelativeFileDirectory($file);
        $destPath = $accPath . '/' . $fileIdPath;

        // Write data to the new file
        $ret = copy($sourcePath, $destPath);
        $size = @filesize($destPath);
        chmod($destPath, 0777);

        // Update file entity
        $file->setValue("file_size", $size);
        $file->setValue("dat_local_path", $fileIdPath);
        // If set, then clear. Key will remain in saved revision.
        $file->setValue("dat_ans_key", "");
        $this->entityDataMapper->save($file);

        return $ret;
    }

    /**
     * Delete a file from the DataMapper
     *
     * @param FileEntity $file The file to purge data for
     * @param int $revision If set then only delete data for a specific revision
     * @return mixed
     */
    public function deleteFile(FileEntity $file, $revision = null)
    {
        if (!$file->getId())
            return false;

        // Delete current version
        try
        {
            $filePath = $this->getFullLocalPath($file);

            if (file_exists($filePath))
            {
                unlink($filePath);
            }
        }
        catch (Exception\FileNotFoundException $ex)
        {
            $this->errors[] = new Error($ex->getMessage());
        }

        // Delete all past revisions
        $revisions = $this->entityDataMapper->getRevisions("file", $file->getId());
        foreach ($revisions as $fileRev)
        {
            try
            {
                $filePath = $this->getFullLocalPath($fileRev);

                if (file_exists($filePath))
                {
                    unlink($filePath);
                }
            }
            catch (Exception\FileNotFoundException $ex)
            {
                $this->errors[] = new Error($ex->getMessage());
            }
        }

        return true;
    }

    /**
     * Get the last error thrown in an object or module
     *
     * @return Error
     */
    public function getLastError()
    {
        return (count($this->errors)) ? $this->errors[count($this->errors) - 1] : null;
    }

    /**
     * Get all errors
     *
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check to see if a file exists in the store
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @return bool true if file is present, otherwise false
     */
    public function fileExists(FileEntity $file)
    {
        try
        {
            $fullPath = $this->getFullLocalPath($file);
        }
        catch (Exception\FileNotFoundException $ex)
        {
            // This file does not have dat_local_path defined - never existed
            return false;
        }

        return file_exists($fullPath);
    }

    /**
     * Get the full path to the local file
     *
     * @param FileEntity $file
     * @return string
     * @throws Exception\FileNotFoundException
     */
    private function getFullLocalPath(FileEntity $file)
    {
        $ansRoot = $this->getAccountDirectory();
        if ($file->getValue("dat_local_path"))
        {
            return $ansRoot . "/" . $file->getValue("dat_local_path");
        }
        else
        {
            // This file does not exist on the local file system
            throw new Exception\FileNotFoundException(
                $file->getValue("name") . " was not found on the local disk"
            );
        }
    }

    /**
     * Get directory for account and create it if not exists.
     *
     * @return string Full path to account directory
     * @throws Exception\PermissionDeniedException
     */
    private function getAccountDirectory()
    {
        $path = $this->dataPath . "/files";

        // Create antfs directory in the data if it does not yet exist
        if (!file_exists($path))
        {
            mkdir($path, 0775);

            if (!chmod($path, 0775))
            {
                throw new Exception\PermissionDeniedException(
                    "Permission denied chmod($path)"
                );
            }
        }

        // Now create namespace dir for dbname
        $path .=  "/" . $this->accountId;

        if (!file_exists($path))
        {
            mkdir($path, 0775);

            if (!chmod($path, 0775))
            {
                throw new Exception\PermissionDeniedException(
                    "Permission denied chmod($path)"
                );
            }

        }

        return $path;
    }

    /**
     * Get the file path relative to the account directory
     *
     * @return string The local folder path for this file
     */
    private function getRelativeFileDirectory(FileEntity $file)
    {
        $localPath = $this->explodeIdToPath($file->getId());

        // Make sure we can save locally
        $this->verifyPathTree($localPath);

        return $localPath . "/" . $file->getId() . "-" . $file->getValue("revision");
    }

    /**
     * Explode id into directories
     *
     * @param int $id The id to explode
     * @return string $path The full path of the file after exploding the id
     */
    private function explodeIdToPath($id)
    {
        $perdir = 4;

        $len = strlen($id);

        if ($len < $perdir)
            return "0000";  // Should match the number of perdir above - all below 1k

        $first = substr($id, 0, 1);

        $path = $first . "";

        for ($i = 1; $i < $len; $i++)
            $path .= "0";

        if ($len <= $perdir)
        {
            return $path;
        }
        else
        {
            return $path . "/" . $this->explodeIdToPath(substr($id, 1));
        }
    }

    /**
     * Verify that a path exists
     *
     * @param string $path The path relative to the antfs root
     * @return bool true if tree exists/was created, otherwise false
     */
    private function verifyPathTree($path)
    {
        $base = $this->getAccountDirectory();

        if (file_exists($base . "/" . $path))
            return;

        $pathParts = explode("/", $path);
        $curr = $base;
        $allOk = true;
        foreach ($pathParts as $dirName)
        {
            // Skip over root
            if (!$dirName)
                continue;

            $curr .= "/" . $dirName;

            // Try to create the directory if we can
            if (!file_exists($curr))
            {
                if (!@mkdir($curr, 0775))
                {
                    throw new Exception\PermissionDeniedException(
                        "Permission denied mkdir($curr)"
                    );
                }
            }
        }
    }
}