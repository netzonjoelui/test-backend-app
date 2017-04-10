<?php
/**
 * This is a stream wrapper used to wrap AntFs functions into standard PHP streams
 *
 * @category  AntFs
 * @package   StreamWrapper
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

class AntFsStreamWrapper
{ 
	const PROTOCOL = "antfs";

	/**
	 * The current position of the file
	 *
	 * @var long
	 */
	private $position;

	/**
	 * The total length in bytes of the file
	 *
	 * @var long
	 */
    private $streamlength;

    /** 
     * resource context 
     * 
     * @var resource 
     */ 
    public $context; 

	/**
	 * Current file we are working with
	 *
	 * @var CAntObject_File
	 */
	public $file = null;

	/**
     * Instantiates a AntFsStreamWrapper to read a file
     *
     * @param mapistream    $mapistream     The stream to be wrapped
     *
     * @access public
     * @return MAPIStreamWrapper
     */
	static public function OpenFile($file) 
	{
        $context = stream_context_create(array(self::PROTOCOL => array('file' => &$file)));
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
        if (!isset($contextOptions[self::PROTOCOL]['file']))
            return false;

        $this->position = 0;

        // this is our stream!
        $this->file = $contextOptions[self::PROTOCOL]['file'];

        $this->streamlength = $this->file->getValue("file_size");

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
		$len = ($this->position + $len > $this->streamlength) ? ($this->streamlength - $this->position) : $len;
		$data = $this->file->read($len);
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
		return ($this->position >= $this->streamlength);
	}

    /** 
     * Enter description here... 
     * 
     * @return array 
     */ 
	public function stream_stat()
	{
		return array(
            7               => $this->streamlength,
            'size'          => $this->streamlength,
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
     * Not yet supported
     * 
     * @param int $offset 
     * @param int $whence = SEEK_SET 
     * @return bool 
    public function stream_seek($offset , $whence = SEEK_SET); 
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

stream_wrapper_register(AntFsStreamWrapper::PROTOCOL, "AntFsStreamWrapper");
