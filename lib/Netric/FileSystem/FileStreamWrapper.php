<?php
/**
 * FileSystem service
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem;

use Netric\Error;
use Netric\EntityQuery;
use Netric\Entity\ObjType\FileEntity;

/**
 * Standard PHP stream wrapper for FileSystem
 */
class FileStreamWrapper
{
    /**
     * Name of this stream wrapper
     */
    const PROTOCOL = "netric";

    /**
     * The current position of the file
     *
     * @var int
     */
    private $position = 0;

    /**
     * The total length in bytes of the file
     *
     * @var int
     */
    private $streamLength = 0;

    /**
     * Resource context
     *
     * @var resource
     */
    private $context;

    /**
     * Current file we are working with
     *
     * @var FileEntity
     */
    private $file = null;

    /**
     * FileSystem instance for manipulating files
     *
     * @var FileSystem
     */
    private $fileSystem = null;

    /**
     * Flag to indicate if the protocol was registered already or not
     *
     * @var bool
     */
    private static $isRegistered = false;

    /**
     * Instantiates a AntFsStreamWrapper to read a file
     *
     * @param FileSystem $fileSystem Instance of filesystem for loading files
     * @param FileEntity $file The file we have opened
     * @access public
     * @return resource
     */
    static public function open(FileSystem $fileSystem, FileEntity $file)
    {
        // Register wrapper if this is our first time executing
        if (!self::$isRegistered) {
            stream_wrapper_register(self::PROTOCOL, get_class());
            self::$isRegistered = true;
        }

        /*
         * We have to register the fileSystem each time because it could span accounts
         * and we need a different fileSystem for each account due to the database
         * being different.
         */
        $openContext = array("filesystem"=>&$fileSystem, "file"=>&$file);
        $context = stream_context_create(array(self::PROTOCOL => $openContext));
        return fopen(self::PROTOCOL . "://",'r', false, $context);
    }

    /**
     * Open the stream
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string &$opened_path
     * @return bool
     */
    public function stream_open($path , $mode , $options , &$opened_path)
    {
        $contextOptions = stream_context_get_options($this->context);

        // Make sure the file was set
        if (!isset($contextOptions[self::PROTOCOL]['file']) || !isset($contextOptions[self::PROTOCOL]['filesystem']))
            return false;

        $this->position = 0;

        // Setup properties for this stream
        $this->file = $contextOptions[self::PROTOCOL]['file'];
        $this->fileSystem = $contextOptions[self::PROTOCOL]['filesystem'];
        $this->streamLength = $this->file->getValue("file_size");

        return true;
    }

    /**
     * Read number of bytes from a file
     *
     * @param int $len
     * @return string
     */
    public function stream_read($len)
    {
        $len = ($this->position + $len > $this->streamLength)
            ? ($this->streamLength - $this->position) : $len;
        $data = $this->fileSystem->readFile($this->file, $len);
        $this->position += sizeof($data);
        return $data;
    }

    /**
     * Determine if we are at the end of the file being read
     *
     * @return bool
     */
    public function stream_eof()
    {
        return ($this->position >= $this->streamLength);
    }

    /**
     * Enter description here...
     *
     * @return array
     */
    public function stream_stat()
    {
        return array(
            7               => $this->streamLength,
            'size'          => $this->streamLength,
        );
    }

    /**
     * Enter description here...
     *
     * @return int
     */
    public function stream_tell()
    {
        return $this->position;
    }

    /**
     * Rewind this stream
     *
     * @return bool
     */
    public function stream_rewind()
    {
        $this->position = 0;
        return true;
    }

    /**
     * Set position to a byte offset
     *
     * @param int $offset
     * @param int $whence = SEEK_SET
     * @return bool
     */
    public function stream_seek($offset , $whence = SEEK_SET)
    {
        $this->position = $offset;
        true;
    }

    // Below are stream functions not yet implemented
    // ======================================================================================

    /**
     * Close a directory, not needed for AntFs
     *
     * @return bool
    public function dir_closedir()
    {
    return true;
    }
     */

    /**
     * Open a folder by a path
     *
     * @param string $path
     * @param int $options
     * @return bool
    public function dir_opendir($path , $options)
    {
    return false;
    }
     */

    /**
     * Get the next file in the list
     *
     * @return string
    public function dir_readdir();
     */

    /**
     * Start again at the first file in the currently opened directory
     *
     * @return bool
    public function dir_rewinddir();
     */

    /**
     * Create a new directory
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
    public function mkdir($path , $mode , $options);
     */

    /**
     * Rename a file or a folder
     *
     * @param string $path_from
     * @param string $path_to
     * @return bool
    public function rename($path_from , $path_to);
     */

    /**
     * Delete a directory
     *
     * @param string $path
     * @param int $options
     * @return bool
    public function rmdir($path , $options);
     */

    /**
     * Enter description here...
     *
     * @param int $cast_as
     * @return resource
    public function stream_cast($cast_as);
     */

    /**
     * Function not really needed
     *
    public function stream_close();
     */

    /**
     * AntFs flushes automatically
     *
     * @return bool
    public function stream_flush();
     */

    /**
     * Locking is not currently supported in AntFs
     *
     * @param mode $operation
     * @return bool
    public function stream_lock($operation);
     */



    /**
     * Enter description here...
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     * @return bool
    public function stream_set_option($option , $arg1 , $arg2);
     */

    /**
     * Not supported, currently we are only working in read-only mode
     *
     * @param string $data
     * @return int
    public function stream_write($data);
     */

    /**
     * Not supported, currently we are only working in read-only mode
     *
     * @param string $path
     * @return bool
    public function unlink($path);
     */

    /**
     * Enter description here...
     *
     * @param string $path
     * @param int $flags
     * @return array
    public function url_stat($path , $flags);
     */
}
