<?php
/*
 * Interface definition for a file system data mapper
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore;

use Netric\Error;
use Netric\Entity\ObjType\FileEntity;

/**
 * Define
 */
interface FileStoreInterface extends Error\ErrorAwareInterface
{
    /**
     * Read and return numBypes (or all) of a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param null $numBytes Number of bytes, if null then return while file
     * @param null $offset Starting offset, defaults to current pointer
     * @return mixed
     */
    public function readFile(FileEntity $file, $numBytes = null, $offset = null);

    /**
     * Write data to a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param mixed $dataOrStream $data Binary data to write or a stream resource
     * @return int number of bytes written
     */
    public function writeFile(FileEntity $file, $dataOrStream);

    /**
     * Upload a file to the data store
     *
     * @param FileEntity $file Meta-data Entity for the file
     * @param $localPath Path of a local file
     * @return true on success, false on failure
     */
    public function uploadFile(FileEntity $file, $localPath);

    /**
     * Delete a file from the DataMapper
     *
     * @param FileEntity $file The file to purge data for
     * @param int $revision If set then only delete data for a specific revision
     * @return mixed
     */
    public function deleteFile(FileEntity $file, $revision = null);

    /**
     * Check to see if a file exists in the store
     *
     * @param FileEntity $file The file to purge data for
     * @return bool true if it exists, otherwise false
     */
    public function fileExists(FileEntity $file);
}