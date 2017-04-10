<?php
/**
 * Collection factory interface
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

use Netric\ServiceManager\AccountServiceManagerInterface;

interface CollectionFactoryInterface
{
	/**
	 * Factory for creating collections and injecting all dependencies
	 *
	 * @param AccountServiceManagerInterface $sm
	 * @param int $type The type to load as defined by \Netric\EntitySync::COLL_TYPE_*
	 * @param array $data Optional data to initialize into the collection
	 */
	public static function create(AccountServiceManagerInterface $sm, $type, array $data=null);

	/**
	 * Instantiated version of the static create function
	 *
	 * @param int $type The type to load as defined by \Netric\EntitySync::COLL_TYPE_*
	 * @param array $data Optional data to initialize into the collection
	 * @return CollectionInterface
	 */
	public function createCollection($type, array $data=null);
}
