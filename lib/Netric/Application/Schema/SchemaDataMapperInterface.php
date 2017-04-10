<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2015-2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Application\Schema;

use Netric\Error\ErrorAwareInterface;

/**
 * Interface for DataMappers that will handle schema creation and updates
 */
interface SchemaDataMapperInterface extends ErrorAwareInterface
{
	/**
	 * Update or create a schema for an account
	 *
     * @param int $accountId Optional account ID we are creating, otherwise assume system
     * @return bool true on success, false on failure
	 */
	public function update($accountId = null);
}