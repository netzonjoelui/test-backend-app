<?php
 /**
 * This is a wrapper class that can dynamically switch between parsing extensions and classes 
 * 
 * @category  AntMail
 * @package   MimeParser
 * @copyright Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/parsers/MimeMailParser.php");
// Pear helper class
require_once("Mail/RFC822.php");
// EmailParser class
require_once("lib/parsers/EmailReplyParser/EmailReplyParser.php");
require_once("lib/parsers/EmailReplyParser/Email.php");
require_once("lib/parsers/EmailReplyParser/Fragment.php");
require_once("lib/parsers/EmailReplyParser/Parser/EmailParser.php");
require_once("lib/parsers/EmailReplyParser/Parser/FragmentDTO.php");
// Attachment class for processing temporary attachments
require_once("lib/AntMail/MimeParser/Attachment.php");

use EmailReplyParser\Parser\EmailParser;
 
class AntMail_MimeParser
{
	/**
	 * The path of the local file to decode
	 *
	 * @var string
	 */
	private $filePath = "";

	/**
	 * Handle to the MimeMailParser
	 *
	 * We prefer this to the Mail_mimeDecode PHP class because the mimeDecode
	 * actually has to load the entire message into memeory where the MimeMailParser
	 * extension is much more efficient. However, windows machines may not be able to
	 * install the MimeMailParser ext so we fall back to the PHP class in that case.
	 *
	 * @var MimeMailParser
	 */
	private $mimeMailParser = null;

	/**
	 * Mimedecode root part if we are not using the MimeMailParser lib
	 *
	 * This is a fallback in case the MimeMailParser is not installed in PHP
	 *
	 * @var Mail_mimeDecodePart
	 */
	private $mailMimeDecode = null;

	/**
	 * Manually set the parser to use
	 *
	 * @var string
	 */
	private $parser = "";

	/**
	 * Class constructor
	 *
	 * @param string $filePath Optional path to the mime file
	 * @param string $parser Optionally manually set the parser to use
	 */
	public function __construct($filePath="", $parser="")
	{
		if ($filePath)
			$this->setPath($filePath);

		if ($parser)
			$this->parser = $parser;
	}

	/**
	 * Set the path of the file to decode if not done in the constructor
	 *
	 * @param string Path to the mime file
	 */
	public function setPath($file)
	{
		$this->filePath = $file;
	}

	/**
	 * Get a header
	 *
	 * @param string $headerName The name (lowercase) of the header to retrieve
	 * @return string
	 */
	public function getHeader($name)
	{
		if($this->getMimeMailParser() && ($this->parser=="" || $this->parser=="MimeMailParser"))
		{
			return trim($this->decodeMimeStr($this->getMimeMailParser()->getHeader(strtolower($name))));
		}
		else
		{
			$message = $this->getMimeDecode();
			return trim($this->decodeMimeStr($message->headers[strtolower($name)]));
		}
	}

	/**
	 * Get the message body
	 *
	 * @param string $part Get which multi-part alternative text/html part of the body
	 * @return string The message body
	 */
	public function getMessageBody($part="plain")
	{
		$body = "";

		if($this->getMimeMailParser() && ($this->parser=="" || $parser=="MimeMailParser"))
		{
			// Convert to UTF encoding if needed and return body
			ini_set('mbstring.substitute_character', "none"); 
			$charType = $this->getCharTypeFromHeaders($this->getMimeMailParser()->getMessageBodyHeaders(($part == "plain") ? 'text' : $part));
			$body = mb_convert_encoding($this->getMimeMailParser()->getMessageBody(($part == "plain") ? 'text' : $part), 'UTF-8', $charType); 
		}
		else
		{
			$body = $this->mimeDecodeGetBody($this->getMimeDecode(), $part);
		}

		return $body;
	}

	/**
	 * Recurrsively get the text body of a message based on subtype
	 *
	 * @param Mail_mimeDecode::part $mimePart Mime part to process
	 * @param string $subtype Sub content type to get - usually plain or html
	 * @param string The body of the message
	 */
	private function mimeDecodeGetBody($mimePart, $subtype) 
	{
		$body = "";

		if(strcasecmp($mimePart->ctype_primary,"text")==0 && strcasecmp($mimePart->ctype_secondary,$subtype)==0 && isset($mimePart->body))
		{
			return trim($mimePart->body);
		}
		else if(strcasecmp($mimePart->ctype_primary,"multipart")==0) 
		{
			foreach($mimePart->parts as $part) 
			{
				if(!isset($part->disposition) || strcasecmp($part->disposition,"attachment"))  
				{
					$body = $this->mimeDecodeGetBody($part, $subtype, $body);
					if ($body)
						return $body;
				}
			}
		}

		return $body;
	}

	/**
	 * Get the headers for a part of the message body
	 *
	 * @param string $contentType Get which multi-part alternative text/html part of the body
	 * @return string The message body
	 */
	public function getMessageBodyHeaders($part="plain")
	{
		if($this->getMimeMailParser() && ($this->parser=="" || $parser=="MimeMailParser"))
			return $this->getMimeMailParser()->getMessageBodyHeaders($part);
		// TODO: handle MimeDecode
	}

	/**
	 * Get attachments for this message
	 *
	 * @return AntMail_MimeParser_Attachment[] Array of attachments
	 */
	public function getAttachments()
	{
		$attachments = array();

		if($this->getMimeMailParser() && ($this->parser=="" || $this->parser=="MimeMailParser"))
		{
			$this->getAttachmentsMailParse($attachments);
		}
		else
			$this->getAttachmentsMimeDecode($this->getMimeDecode(), $attachments);

		return $attachments;
	}

	/** 
	 * Process attachments for a message being parsed by mimeparse
	 *
	 * @param AntMail_MimeParser_Attachment[] $attbuf Array of attachments
	 */
	private function getAttachmentsMailParse(&$attbuf)
	{
		$parser = $this->getMimeMailParser();

		$attachments = $parser->getAttachments();
		foreach ($attachments as $mimePart)
		{
			/*
			 * 	1. Write attachment to temp file
			 *	-------------------------------------------------------
			 * 	It is important to use streams here to try and keep the attachment out of memory if possible
			 * 	The parser should alrady have decoded the bodies for us so no need to use base64_decode or
			 * 	anything like that
			 */
			$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
			if (!file_exists($tmpFolder))
				@mkdir($tmpFolder, 0777, true);
			$tmpFile = tempnam($tmpFolder, "ematt");
			$handle = fopen($tmpFile, "w");
			$buf = null;
			while (($buf = $mimePart->read()) != false)
			{
				fwrite($handle, $buf);
			}
			fclose($handle);

			if (!file_exists($tmpFile))
				return false;

			// 2. Add the attachment to the EmailMessage object
			$att = new AntMail_MimeParser_Attachment($tmpFile, $mimePart->getFilename());
			$att->fileName = $mimePart->getFilename();
			$att->conentType = $mimePart->getContentType(); 
			$att->contentId	= $mimePart->content_id; // content_id
			$att->contentDisposition = $mimePart->getContentDisposition(); // disposition
			$att->contentTransferEncoding = $mimePart->transfer_encoding; // encoding
			$att->cleanFileOnSave = true; // cleanup once the attachment has been saved

			// Add attachment to the buffer
			$attbuf[] = $att;
		}
	}

	/** 
	 * Process attachments for a message being parsed by fallback mime-decode class
	 *
	 * @param mimeParsePart $mimePart The part we are working on right now
	 * @param CAntObject_EmailMessage $email Current email we are saving to
	 */
	private function getAttachmentsMimeDecode(&$mimePart, &$attbuf)
	{
		if(isset($mimePart->disposition) || strcasecmp($mimePart->disposition,"attachment")==0 || $mimePart->ctype_primary=="image")  
		{
			/*
			 * 	1. Write attachment to temp file
			 *	-------------------------------------------------------
			 * 	It is important to use streams here to try and keep the attachment out of memory if possible
			 * 	The parser should alrady have decoded the bodies for us so no need to use base64_decode or
			 * 	anything like that
			 */
			$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
			if (!file_exists($tmpFolder))
				@mkdir($tmpFolder, 0777, true);
			$tmpFile = tempnam($tmpFolder, "ematt");
			$handle = fopen($tmpFile, "w");
			fwrite($handle, trim($mimePart->body));
			fclose($handle);

			if (file_exists($tmpFile))
			{
				// 2. Add the attachment to the attachments buffer
				$att = new AntMail_MimeParser_Attachment($tmpFile, $mimePart->d_parameters['filename']);
				$att->name = $mimePart->ctype_parameters['name'];
				$att->fileName = $mimePart->d_parameters['filename'];
				$att->conentType = $mimePart->ctype_primary."/".$mimePart->ctype_secondary; 
				$att->contentId	= $mimePart->ctype_parameters['content-id']; // content_id
				$att->contentDisposition = $mimePart->disposition; // disposition
				$att->contentTransferEncoding = $mimePart->headers['content-transfer-encoding']; // encoding
				$att->cleanFileOnSave = true; // cleanup once the attachment has been saved

				$attbuf[] = $att;
			}
		}
		else if(strcasecmp($mimePart->ctype_primary,"multipart")==0) // call recurrsively to get all attachments
		{
			foreach($mimePart->parts as $subPart) 
			{
				$this->getAttachmentsMimeDecode($subPart, $attbuf);
			}
		}
	}

	/**
	 * Parse an address list from a string and return array
	 *
	 * @param string $addresses The RFC822 address list
	 * @return array(array("address"=>"full@adddress.com", "display"=>"Display Name", "mailbox"=>"user/mailbox of address", "host"=>"domain.com"))
	 */
	static public function parseAddressList($addresses)
	{
		$ret = array();

		$parsed = Mail_RFC822::parseAddressList($addresses);
		foreach ($parsed as $addr)
		{
			$ret[] = array(
				'address' => $addr->mailbox . "@" . $addr->host,
				'mailbox' => $addr->mailbox,
				'host' => $addr->host,
				'display' => trim($addr->personal, "'\""),
			);
		}

		return $ret;
	}

	/**
	 * Decode mime encoded values
	 *
	 * @param string $string The strign to decode
	 * @param string $charset Defaults to unicode, the character set we are working with
	 * @return string If the string was encoded, then return the decoded string, otherwise return the original string
	 */
	public function decodeMimeStr($string, $charset="UTF-8")
	{
		if (is_array($string)) // some parsers pass empty array if value not found
			return "";

		if (!$string)
			return "";

		$newString = '';
		$elements=imap_mime_header_decode($string);

		for($i=0;$i<count($elements);$i++)
		{
			if ($elements[$i]->charset == 'default')
			  $elements[$i]->charset = $charset; //'iso-8859-1';

			if (mb_check_encoding($elements[$i]->text, $charset))
				$newString .=  mb_convert_encoding($elements[$i]->text, $charset, $elements[$i]->charset);
			else
				$newString .= utf8_encode($elements[$i]->text);
		}

		// Make sure UTF is proper
		$newString = iconv('utf-8',"utf-8//IGNORE", $newString);

		return $newString;
	} 

	/**
	 * Try to get char-type from headers
	 *
	 * @param array Array of mime headers
	 */
	private function getCharTypeFromHeaders($headers)
	{
		$ret = "UTF-8";

		if (isset($headers['content-type']))
		{
			preg_match('/charset="([^"]+)"/', $headers['content-type'], $matches);
			if ($matches[1])
				$ret = $matches[1];
		}

		return $ret;
	}

	/**
	 * Get and initialize the mime parser
	 */
	private function getMimeMailParser()
	{
		if (!$this->filePath)
			throw new Exception("Path to mime file has not been set!");

		// Check if already initialized
		if ($this->mailMimeParser)
			return $this->mailMimeParser;

		// Check if extension is installed
		if(function_exists('mailparse_msg_parse')) 
		{
			$this->mailMimeParser = new MimeMailParser();
			$this->mailMimeParser->setPath($this->filePath);
			return $this->mailMimeParser;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get and initialize the PHP mimeDecode parser
	 */
	private function getMimeDecode()
	{
		if (!$this->filePath)
			throw new Exception("Path to mime file has not been set!");

		// Check if already initialized
		if ($this->mailMimeDecode)
			return $this->mailMimeDecode;

		if (file_exists($this->filePath))
			$rfc822Msg = file_get_contents($this->filePath);
		else
			return false; // Fail

		$decoder = new Mail_mimeDecode($rfc822Msg);
		$this->mailMimeDecode = $decoder->decode(array(
			'decode_headers' => true, 
			'decode_bodies' => true, 
			'include_bodies' => true, 
			'charset' => 'utf-8',
		));

		return $this->mailMimeDecode;
	}

	/**
	 * Remove the quoted reply of an email message to return only the body
	 *
	 * @param string $body The body of the message
	 * @param bool $enclose If true, leave the quoted there but enclose in <div class='emailReplyQuoted'></div>
	 * @retun string Either just the body or the quoted message enclosed
	 */
	static public function parseReply($body, $enclose=false)
	{
		return \EmailReplyParser\EmailReplyParser::parseReply($body);
	}

	/**
	 * Convert html to plain text
	 *
	 * @param string $body The body of the message
	 * @retun string The plain text version of the email
	 */
	static public function htmlToPlain($body)
	{
		return strip_tags(str_replace("<br>", "\n", $body));
	}
}
