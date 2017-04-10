<?php
/*
 * Results of entity query will be managed here
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */

namespace Netric\EntityQuery;

use Netric\Stats\StatsPublisher;

/**
 * Description of Results
 *
 * @author joe
 */
class Results 
{
    /**
     * The query used to construct these results
     * 
     * @var \Netric\EntityQuery
     */
    private $query = null;
    
    /**
     * DataMapper reference used for automatic pagination after initial load
     * 
     * @var Index\IndexInterface
     */
    private $index = null;
    
    /**
     * Array of entities that are loaded in this collection
     * 
     * @param \Netric\Models\Entity
     */
    private $entities = array();
    
    /**
     * The starting offset of the next page
     * 
     * This is set by the datamapper when the query is done
     * 
     * @var int
     */
    private $nextPageOffset = -1;
    
    /**
     * The starting offset of the previous page
     * 
     * This is set by the datamapper when the query is done
     * 
     * @var int
     */
    private $prevPageOffset = -1;
    
    /**
     * Total number of entities in the collection
     * 
     * @var int
     */
    private $totalNum = 0;
    
    /**
     * Aggregation data
     * 
     * @var array("name"=>array(data))
     */
    private $aggregations = array();
    
    /**
     * Class constructor
     * 
     * @param string $objType Unique name of the object type we are querying
     */
    public function __construct(\Netric\EntityQuery $query, Index\IndexInterface &$index = null) 
    {
        $this->query = $query;        
        if ($index)
            $this->index = $index;
    }
    
    /**
     * Get the object type for this collection
     * 
     * @return string
     */
    public function getObjType()
    {
        return $this->query->getObjType();
    }
    
    /**
     *  Set local reference to datamapper for loading objects and auto pagination
     * 
     * @param Index\IndexInterface &$index
     */
    public function setIndex(Index\IndexInterface &$index)
    {
        $this->index = $index;
    }
    
    /**
     * Get the offset of the next page for automatic pagination
     * 
     * @return int $offset
     */
    public function getNextPageOffset()
    {
        return $this->nextPageOffset;
    }
    
    /**
     * Get the offset of the previous page for automatic pagination
     * 
     * @return int $offset
     */
    public function getPrevPageOffset()
    {
        return $this->prevPageOffset;
    }
    
    /**
     * Set the offset
     * 
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->query->setOffset($offset);
    }
    
    /**
     * Get current offset
     * 
     * @return int $offset
     */
    public function getOffset()
    {
        return $this->query->getOffset();
    }
    
    /**
     * Set the total number of entities for the defined query
     * 
     * The collection will load one page at a time
     * 
     * @param int $num The total number of entities in this query collection
     */
    public function setTotalNum($num)
    {
        $this->totalNum = $num;
    }
    
    /**
     * Get the total number of entities in this collection
     * 
     * @return int Total number of entities
     */
    public function getTotalNum()
    {
        return $this->totalNum;
    }
    
    /**
     * Get the number of entities in the current loaded page
     * 
     * $return int Number of entities in the current page
     */
    public function getNum()
    {
        return count($this->entities);
    }
    
    /**
     * Add an entity to this collection
     * 
     * @param \Netric\Entity\EntityInterface $entity
     */
    public function addEntity(\Netric\Entity\EntityInterface $entity)
    {
        // Stat a cache list hit
        StatsPublisher::increment("entity.cache.queryres");
        $this->entities[] = $entity;
    }
    
    /**
     * Reset the entities array
     */
    public function clearEntities()
    {
        $this->entities = array();
    }
    
    /**
     * Retrieve an entity from the collection
     * 
     * @param int $offset The offset of the entity to get in the collection
     * @return \Netric\Entity\EntityInterface
     */
    public function getEntity($offset=0)
    {
        if ($offset >= ($this->getOffset() + $this->query->getLimit()) || $offset < $this->getOffset())
        {
            // Get total number of pages
			$leftover = $this->totalNum % $this->query->getLimit();
			if ($leftover)
				$numpages = (($this->totalNum - $leftover) / $this->query->getLimit()) + 1;
			else
				$numpages = $this->totalNum / $this->query->getLimit();
			
            // Get current page offset
            $page = floor($offset / $this->query->getLimit());
            if ($page)
                $this->setOffset($page * $this->query->getLimit());
            else
                $this->setOffset(0);
            
            
            // Automatially load the next page
            if ($this->index)
                $this->index->executeQuery($this->query, $this);
        }
        
        // Adjust offset for pagination
        $offset = $offset - $this->getOffset();
        
        if ($offset >= count($this->entities))
            return false; // TODO: can expand to get next page for progressive load
        
        return $this->entities[$offset];
    }
      
    /**
     * Set aggregation data
     * 
     * @param string $name The unique name of this aggregation
     * @param int|string|array $value
     */
    public function setAggregation($name, $value)
    {
        $this->aggregations[$name] = $value;
    }
    
    /**
     * Get aggregation data for this query by name
     * 
     * @return array()
     */
    public function getAggregation($name)
    {
        if (isset($this->aggregations[$name]))
            return $this->aggregations[$name];
        else
            return false;
    }
    
    /**
     * Get aggregations data for this query
     * 
     * @return array("name"=>array(data))
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }
    
    /**
     * Check if this query has any aggregations
     * 
     * @return bool true if aggs exist, otherwise false
     */
    public function hasAggregations()
    {
        return (count($this->aggregations)>0) ? true : false;
    }
}
