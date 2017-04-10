<?php
/**
 * The identity map (loader) is responsible for loading a specific entity and caching it for future calls.
 */
namespace Netric;

use Netric\Entity\Entity;
use Netric\Entity\DataMapperInterface;
use Netric\Entity\EntityInterface;
use Netric\Stats\StatsPublisher;

class EntityLoader
{
	/**
 	 * Cached entities
	 */
	private $loadedEntities = array();

	/**
	 * Store the single instance of the loader
	 */
    private static $m_pInstance;

	/**
	 * Datamapper for entities
	 *
	 * @var DataMapperInterface
	 */
	private $dataMapper = null;

	/**
	 * Entity definition loader for getting definitions
	 *
	 * @var EntityDefinitionLoader
	 */
	private $definitionLoader = null;

    /**
     * Entity factory used for instantiating new entities
     *
     * @var \Netric\Entity\EntityFactory
     */
    protected $entityFactory = null;

	/**
	 * Cache
	 *
	 * @var Cache
	 */
	private $cache = null;

	/**
	 * Class constructor
	 *
	 * @param Entity_DataMapper $dm The entity datamapper
	 * @param EntityDefinitionLoader $defLoader The entity definition loader
	 */
	public function __construct(DataMapperInterface $dm, EntityDefinitionLoader $defLoader)
	{
		$this->dataMapper = $dm;
		$this->definitionLoader = $defLoader;
        $this->entityFactory = $dm->getAccount()->getServiceManager()->get("EntityFactory");
		$this->cache = $dm->getAccount()->getServiceManager()->get("Cache");
		return $this;
	}

	/**
	 * Factory
	 *
	 * @param Entity_DataMapperInterface $dm The entity datamapper
	 * @param EntityDefinitionLoader $defLoader The entity definition loader
	 */
	public static function getInstance(DataMapperInterface $dm, $defLoader)
	{
		if (!self::$m_pInstance)
			self::$m_pInstance = new EntityLoader($dm, $defLoader);

		// If we have switched accounts then reload the cache
		if ($dm->getAccount()->getName() != self::$m_pInstance->dataMapper->getAccount()->getName())
		{
			self::$m_pInstance->loadedEntities = array();
			self::$m_pInstance->dataMapper = $dm;
			self::$m_pInstance->definitionLoader = $defLoader;
		}

		return self::$m_pInstance;
	}

	/**
	 * Determine if an entity is already cached in memory
	 *
	 * @param string $id
	 * @return bool true if the entity was already loaded into memory, false if not
	 */
	private function isLoaded($objType, $id)
	{
		if (isset($this->loadedEntities[$objType][$id]) && $this->loadedEntities[$objType][$id] != null)
			return true;
		else
			return false;
	}

	/**
	 * Determine if an entity is in the cache layer
	 *
	 * @param string $objType The type of objet we are loading
	 * @param string $id
	 * @return array|bool Array of data if cached or false if nut found
	 */
	private function getCached($objType, $id)
	{
		return $this->cache->get($this->dataMapper->getAccount()->getName() . "/objects/" . $objType . "/" . $id);
	}

	/**
	 * Get the post by id from the datamapper
	 *
	 * @param string $objType The type of object we are getting
	 * @param string $id The unique id of the object
	 * @return EntityInterface
	 */
	public function get($objType, $id)
	{
		if ($this->isLoaded($objType, $id)) {
			return $this->loadedEntities[$objType][$id];
		}

		// Create entity to load data into
		$entity = $this->create($objType);

		// First check to see if the object is cached
		$data = $this->getCached($objType, $id);
		if ($data)
		{
			$entity->fromArray($data);
			if ($entity->getId())
			{
				// Clear dirty status
				$entity->resetIsDirty();

				// Save in loadedEntities so we don't hit the cache again
				$this->loadedEntities[$objType][$id] = $entity;

				// Stat a cache hit
				StatsPublisher::increment("entity.cache.hit");

				return $entity;
			}
		}

		// Stat a cache miss
		StatsPublisher::increment("entity.cache.miss");

		// Load from datamapper
		if ($this->dataMapper->getById($entity, $id))
		{
			$this->loadedEntities[$objType][$id] = $entity;
			$this->cache->set($this->dataMapper->getAccount()->getName() . "/objects/" . $objType . "/" . $id, $entity->toArray());
			return $entity;
		}
		else
		{
			// TODO: make sure it is deleted from the index?
		}

		// Could not be loaded
		return null;
	}

	/**
	 * Shortcut for constructing an Entity
	 *
	 * @param string $objType The name of the object type
	 * @return \Netric\Entity\EntityInterface
	 */
	public function create($objType)
	{
        return $this->entityFactory->create($objType);
	}

	/**
	 * Delete an entity
	 *
	 * @param EntityInterface $entity The entity to save
	 * @return int|string|null Id of entity saved or null on failure
	 */
	public function save(EntityInterface $entity)
	{
        $ret = $this->dataMapper->save($entity);

        if ($entity->getId()) {
            $this->clearCache($entity->getDefinition()->getObjtype(), $entity->getId());
        }

        return $ret;
	}

    /**
     * Save an entity
     *
     * @param EntityInterface $entity The entity to delete
     * @param bool $forceHard If true the force a hard delete - purge!
     * @return \Netric\Entity\Entity
     */
    public function delete(EntityInterface $entity, $forceHard = false)
    {
		$this->clearCache($entity->getDefinition()->getObjType(), $entity->getId());

        return $this->dataMapper->delete($entity, $forceHard);
    }

	/**
	 * Clear cache
	 *
	 * @param string $objType The object type name
	 * @param int $id The id of the entity to clear
	 */
	public function clearCache($objType, $id)
	{
		if (isset($this->loadedEntities[$objType][$id]))
			$this->loadedEntities[$objType][$id] = null;

		$ret = $this->cache->remove($this->dataMapper->getAccount()->getName() . "/objects/" . $objType . "/" . $id);
	}
}
