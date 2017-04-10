<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

use Netric\EntitySync\EntitySync;
use Netric\ServiceManager\AccountServiceManagerInterface;

class CollectionFactory implements CollectionFactoryInterface
{
	/**
	 * Service manager for loading collection dependencies
	 *
	 * @var AccountServiceManagerInterface
	 */
	private $serviceManager = null;

	/**
	 * Construct an instance of this factory so we can inject it as a dependency
	 *
	 * @param AccountServiceManagerInterface $serviceManager
	 */
	public function __construct(AccountServiceManagerInterface $serviceManager)
	{
		$this->serviceManager = $serviceManager;
	}

	/**
	 * Instantiated version of the static create function
	 *
	 * @param int $type The type to load as defined by \Netric\EntitySync::COLL_TYPE_*
	 * @param array $data Optional data to initialize into the collection
	 * @return CollectionInterface
	 */
	public function createCollection($type, array $data=null)
	{
		return self::create($this->serviceManager, $type, $data);
	}

	/**
	 * Factory for creating collections and injecting all dependencies
	 *
	 * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm
	 * @param int $type The type to load as defined by \Netric\EntitySync::COLL_TYPE_*
	 * @param array $data Optional data to initialize into the collection
	 * @return CollectionInterface
	 * @throws \Exception if an unsupported collection type is added
	 */
	public static function create(AccountServiceManagerInterface $sm, $type, array $data=null)
	{
		$collection = null;

		// Common dependency
		$dm = $sm->get("Netric/EntitySync/DataMapper");
		$commitManager = $sm->get("Netric/EntitySync/Commit/CommitManager");

		switch ($type)
		{
		case EntitySync::COLL_TYPE_ENTITY:
			$index = $sm->get("EntityQuery_Index");
			$collection = new EntityCollection($dm, $commitManager, $index);
			break;
		case EntitySync::COLL_TYPE_GROUPING:
			break;
		case EntitySync::COLL_TYPE_ENTITYDEF:
			break;
		default:
			throw \Exception("Unrecognized type of entity!");
			break;
		}
		
		// Initialize data if set
		if ($data && $collection)
		{
			if ($data['id'])
				$collection->setId($data['id']);
			//if ($data['object_type_id'])
			//	$collection->setObjTypeId($data['object_type_id']);
			if ($data['object_type'])
				$collection->setObjType($data['object_type']);
			if ($data['field_id'])
				$collection->setFieldId($data['field_id']);
			if ($data['field_name'])
				$collection->setFieldName($data['field_name']);
			if ($data['ts_last_sync'])
				$collection->setLastSync(new \DateTime($data['ts_last_sync']));
			if ($data['conditions'])
				$collection->setConditions($data['conditions']);
			if ($data['revision'])
				$collection->setRevision($data['revision']);
			if ($data['last_commit_id'])
				$collection->setLastCommitId($data['last_commit_id']);
		}

		return $collection;
	}
}