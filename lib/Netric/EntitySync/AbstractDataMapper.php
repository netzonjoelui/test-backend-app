<?php
/**
 * Abstract DataMapper for sync library
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */

 namespace Netric\EntitySync;

 abstract class AbstractDataMapper extends \Netric\DataMapperAbstract
 {
	/**
	 * Handle to database
	 *
	 * @var \Netric\Db\Pgsql
	 */
	protected $dbh = null;
	
	/**
	 * Class constructor
	 * 
	 * @param \Netric\Account\Account $account Account for tennant that we are mapping data for
	 * @param \Netric\Db\DbInterface $dbh Handle to database
	 */
	public function __construct(\Netric\Account\Account $account, \Netric\Db\DbInterface $dbh)
	{
		$this->setAccount($account);

		$this->dbh = $dbh;
	}
}
