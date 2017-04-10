<?php
/**
 * Identity mapper for entity groupings
 */
namespace Netric\EntityGroupings;
use Netric\EntityGroupings;

/**
 * Class to handle to loading of object definitions
 */
class Loader
{
	/**
     * The current data mapper we are using for this object
     * 
     * @var DataMapperInterface
     */
	protected $dataMapper = null;

    /**
     * Array of loaded groupings
     * 
     * @var array
     */
    private $loadedGroupings = array();

	/**
	 * Cache
	 *
	 * @var \Netric\Cache\CacheInterface
	 */
	private $cache = null;
    
    /**
     * Setup IdentityMapper for loading objects
     * 
     * @param DataMapperInterface $dm Datamapper for entity definitions
     * @param Netric\Cache\CacheInterface $cache Optional cache object
     * @return EntityDefinitionLoader
     */
    public function __construct($dm, \Netric\Cache\CacheInterface $cache=null)
	{
		$this->cache = $cache;
		$this->dataMapper = $dm;
		return $this;
	}
   
    /**
     * Get an entity
     * 
     * @param string $objType
     * @return Entity
     */
    public function get($objType, $fieldName, $filters=array())
    {
		if (!$objType || !$fieldName)
			throw new Exception('$objType and $fieldName are required params');
			
        if ($this->isLoaded($objType, $fieldName, $filters)) 
        {
            $ret = $this->loadedGroupings[$objType][$fieldName][$this->getFiltersHash($filters)];
        }
        else
        {
            $ret = $this->loadGroupings($objType, $fieldName, $filters);
        }

        return $ret;
    }

    /**
     * Save changes to groupings
     *
     * @param EntityGroupings $groupings
     * @return mixed
     */
    public function save(EntityGroupings $groupings)
    {
        return $this->dataMapper->saveGroupings($groupings);
    }

    /**
     * Get unique filters hash
     */
    private function getFiltersHash($filters=array())
    {
        return \Netric\EntityGroupings::getFiltersHash($filters);
    }

	/**
	 * Construct the definition class
	 *
	 * @param string $objType
	 */
	private function loadGroupings($objType, $fieldName, $filters=array())
	{
		$groupings = $this->dataMapper->getGroupings($objType, $fieldName, $filters);
        $groupings->setDataMapper($this->dataMapper);
		// Cache the loaded definition for future requests
		$this->loadedGroupings[$objType][$fieldName][$this->getFiltersHash($filters)] = $groupings;
		//$this->cache->set($this->dataMapper->getAccount()->getId() . "/objects/" . $objType, $def->toArray());
		return $groupings;
	}

    
    /**
     * Check to see if the entity has already been loaded 
     * 
     * @param string $key The unique key of the loaded object
     * @return boolean
     */
    private function isLoaded($objType, $fieldName, $filters=array())
    {
        return isset($this->loadedGroupings[$objType][$fieldName][$this->getFiltersHash($filters)]);
    }
    
    /**
     * Check to see if an entity is cached
	 *
     * @param string $objType The unique name of the object to that was cached
     * @return EntityDefinition|bool EntityDefinition if found in cache, false if not cached
     */
    private function getCached($objType, $fieldName, $filters=array())
    {
        return false;
        
        /* No caching currently in use
        // Load the cache datamapper and put it into $this->loadedEntities
		$ret = $this->cache->get($this->dataMapper->getAccount()->getId() . "/objects/" . $objType);

		if ($ret)
		{
			$def = new EntityDefinition($objType);
			$def->fromArray($ret);
			return $def;
		}

		return false;
         * 
         */
    }

	/**
	 * Clear cache
	 *
	 * @param string $objType The object type name
	 */
	public function clearCache($objType, $fieldName, $filters=array())
	{
        $this->loadedGroupings[$objType][$fieldName][$this->getFiltersHash($filters)] = null;
        return;
        /*
		$this->loadedDefinitions[$objType] = null;
		$ret = $this->cache->remove($this->dataMapper->getAccount()->getId() . "/objects/" . $objType);
         */
	}
}
