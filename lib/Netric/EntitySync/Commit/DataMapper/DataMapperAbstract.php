<?php
/**
 * Abstract commit datamapper
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Commit\DataMapper;

abstract class DataMapperAbstract extends \Netric\DataMapperAbstract implements DataMapperInterface
{
	/**
	 * Class constructor
	 * 
	 * @param ServiceLocator $sl The ServiceLocator container
	 * @param string $accountName The name of the ANT account that owns this data
	 */
	public function __construct(\Netric\Account\Account $account)
	{
		$this->setAccount($account);
		$this->setUp();
	}
}
