<?php
/**
 * A DataMapper is responsible for writing and reading data from a persistant store
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric;

use Netric\Error;

abstract class DataMapperAbstract implements Error\ErrorAwareInterface
{
	/**
	 * Handle to current account we are mapping data for
	 *
	 * @var Netric\Account
	 */
	protected $account = "";

	/**
	 * Errors
	 *
	 * @var array(array('message','file','line'))
	 */
	protected $errors = array();
    
	/**
	 * Get account
	 * 
	 * @return Netric\Account
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * Set account
	 * 
	 * @param Netric\Account $account The account of the current tennant
	 */
	public function setAccount($account)
	{
		$this->account = $account; 
	}

	/**
	 * Function is used to return false and set the error message
	 *
	 * 
	 * @param string $message The error message
	 * @param string $file The name of the file/class that caused the error
	 * @param string $line The line number if the file that caused the error
	 * @param mixed $retVal What to return, usually a false
	 * @return mixed the value of $retVal param which is false by default
	 */
	protected function returnError($message, $file=null, $line=null, $retVal=false)
	{
		$this->errors[] = array(
			'message' => $message,
			'file' => $file,
			'line' => $line,
		);

		if ($this->getAccount()) {
			$log = $this->getAccount()->getApplication()->getLog();
			$log->error("$file: $message");
		}

		return $retVal;
	}

	/**
	 * Get the last error message
	 *
	 * @return string Last error message
	 */
	public function getLastError()
	{
		// TODO: we should move this to returning an Error
		$numErrors = count($this->errors);
		if ($numErrors > 0)
		{
			return $this->errors[$numErrors-1]['message'];
		}

		return "";
	}

	/**
	 * Get all errors sent through the system
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}
