<?php
 /**
 * This is a wrapper class that can dynamically switch between parsing extensions and classes 
 * 
 * @category  AntMail
 * @package   MimeParser
 * @copyright Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
class AntMail_MimeParser_Attachment
{
	/**
	 * Temp file path with attachment data
	 *
	 * @var string
	 */
	public $filePath = "";

	/**
	 * The file name
	 *
	 * @var string
	 */
	public $name = "";

	/**
	 * Cleanup on save flag
	 *
	 * @var bool
	 */
	public $cleanFileOnSave = true;

	/**
	 * File name which is usually the same as the name
	 *
	 * @var string
	 */
	public $fileName = "";

	/**
	 * Mime encode content type
	 *
	 * @var string
	 */
	public $contentType = "";

	/**
	 * Optional content id used for inline attachments
	 *
	 * @var string
	 */
	public $contentid = "";

	/**
	 * Either 'attachment' or 'inline' attachments
	 *
	 * @var string
	 */
	public $contentDisposition = "";

	/**
	 * Origincal encoding, but will be decoded to binary by the mime-parser
	 *
	 * @var string
	 */
	public $contentTransferEncoding = "";

	/**
	 * Attachment constructor
	 *
	 * @param string $tempFile The temporary file containing attachment data
	 */
	public function __construct($filePath, $fileName)
	{
		if (!$filePath)
			throw new Exception("File path is a required param for constructing a new attachment");

		if (!file_exists($filePath))
			throw new Exception("Attachment file does not exist");

		$this->filePath = $filePath;
		$this->name = $fileName;
		$this->fileName = $fileName;
	}

	/**
	 * Cleanup temp file
	 */
	function __destruct()
	{
		// Delete temp file
		if ($this->cleanFileOnSave)
		{
			@unlink($this->filePath);
		}
	}
}
