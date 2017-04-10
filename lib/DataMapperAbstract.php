<?php
/**
 * A DataMapper is responsible for writing and reading data from a persistant store
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2013 Aereus Corporation (http://www.aereus.com)
 */
abstract class DataMapperAbstract
{
	/**
	 * Unique account name used to partition data
	 *
	 * @var string
	 */
	protected $accountName = "";
    
	/**
	 * Get account name
	 * 
	 * @return string The account name of the current tennant
	 */
	public function getAccountName()
	{
		return $this->accountName;
	}

	/**
	 * Set account name
	 * 
	 * @param string $name The account name of the current tennant
	 */
	public function setAccountName($name)
	{
		$this->accountName = $name; 
	}
}
