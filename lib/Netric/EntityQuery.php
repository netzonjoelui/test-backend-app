<?php
/**
 * This class is used to handle querying collections of objects
 * 
 * TODO: this is a work in progress
 */
namespace Netric;
use Netric\EntityQuery\OrderBy;
use Netric\EntityQuery\Where;

/**
 * Object query class
 * 
 * @author joe <sky.stebnicki@aereus.com>
 */
class EntityQuery
{
    /**
     * The object type we are working with
     * 
     * @var string
     */
    private $objType = "";
    
    /**
     * Array of where conditions
     * 
     * @var array [['blogic', 'field', 'operator', 'value']]
     */
    private $wheres = array();
    
    /**
     * Order by fields
     * 
     * @var array [['field', 'direction']]
     */
    private $orderBy = array();
    
    /**
     * DataMapper reference used for automatic pagination after initial load
     * 
     * @var /Netric/models/DataMapperInterface
     */
    private $dataMapper = null;
    
    /**
     * Array of entities that are loaded in this collection
     * 
     * @param \Netric\Models\Entity
     */
    private $entities = array();
    
    /**
     * Limit number of entities loaded from datamapper per page
     * 
     * @var int
     */
    private $limitPerPage = 100;
    
    /**
     * The current offset
     * 
     * @var int
     */
    private $offset = 0;
    
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
     * Aggregations to use with this query
     * 
     * @var Netric\EntityQuery\Aggregation\AbstractAggregation[]
     */
    private $aggregations = array();

    /**
     * Operator constants
     *
     * @var const string
     */
    const OP_EQUALTO = "is_equal";
    const OP_DOESNOTEQUAL = "is_not_equal";
       
    /**
     * Class constructor
     * 
     * @param string $objType Unique name of the object type we are querying
     */
    public function __construct($objType) 
    {
        $this->objType = $objType;
    }
    
    /**
     * Get the object type for this collection
     * 
     * @return string
     */
    public function getObjType()
    {
        return $this->objType;
    }
    
    /**
     * Add a where condition
     * 
     * @param string $fieldName
     * @param string $operator Optional constructor operator
     * @param mixed $value The condition value
     * @return \Netric\EntityQuery\Where
     */
    public function where($fieldName, $operator="", $value="")
    {
        $where = new Where($fieldName);
        if ($operator)
            $where->operator = $operator;
        $where->value = $value;
        $this->addCondition($where);
        return $where;
    }
    
    /**
     * Add a where condition with "and" boolean logic
     * 
     * @param string $fieldName
     * @return \Netric\EntityQuery\Where
     */
    public function andWhere($fieldName, $operator="", $value="")
    {
        return $this->where($fieldName, $operator, $value);
    }

    /**
     * Add a where condition with 'or' blogic
     * 
     * @param string $fieldName
     * @return \Netric\EntityQuery\Where
     */
    public function orWhere($fieldName, $operator="", $value="")
    {
        $where = new Where($fieldName);
		$where->bLogic = "or";
        if ($operator)
            $where->operator = $operator;
        $where->value = $value;
        $this->addCondition($where);
        return $where;
    }

    /**
     * Add where condition with where object
     *
     * @param Where $where
     */
    private function addCondition(Where $where)
    {
        $this->wheres[] = $where;
    }
    
    /**
     * Get array of wheres used to filter this collection
     * 
     * @return Netric\EntityQuery\Where[]
     */
    public function getWheres()
    {
        return $this->wheres;
    }

    /**
     * Add a field to order this by
     * 
     * @param string $fieldName
     * @param string $direction
     * @return Netric/EntityQuery
     */
    public function orderBy($fieldName, $direction = OrderBy::ASCENDING)
    {
        $this->addOrderBy(new OrderBy($fieldName, $direction));
        return $this;
    }

    /**
     * Private function for adding an order by object to this query
     *
     * @param OrderBy $orderBy
     */
    private function addOrderBy(OrderBy $orderBy)
    {
        $this->orderBy[] = $orderBy;
    }
    
    /**
     * Get array of order by used to filter this collection
     * 
     * @return array(array("field", "direction"))
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }
    
    /**
     *  Set local reference to datamapper for loading objects and auto pagination
     * 
     * @param \Netric\Models\DataMapperInterface $dm
     */
    public function setDataMapper(\Netric\Models\DataMapperInterface $dm)
    {
        $this->dataMapper = $dm;
    }
    
    /**
     * Restrict the number of entities that can be loaded per page
     * 
     * @param int $num Number of items to load per page
     */
    public function setLimit($num)
    {
        $this->limitPerPage = $num;
    }
    
    /**
     * Get the limit per page that can be loaded
     * 
     * @return int
     */
    public function getLimit()
    {
        return $this->limitPerPage;
    }
    
    /**
     * Determine if this query is searching for deleted items or active
     * 
     * @return boolean True if we are looking for deleted items
     */
    public function isDeletedQuery()
    {
        $ret = false;
        $wheres = $this->getWheres();
        foreach ($wheres as $where)
        {
            if ("f_deleted" == $where->field && true == $where->value)
            {
                $ret = true;
                break;
            }
        }
        return $ret;
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
        $this->offset = $offset;
    }
    
    /**
     * Get current offset
     * 
     * @return int $offset
     */
    public function getOffset()
    {
        return $this->offset;
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
     * Add an entity to this collection
     * 
     * @param \Netric\Models\EntityAbstract $entity
     */
    public function addEntity(\Netric\Models\EntityAbstract $entity)
    {
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
     * @return \Netric\Models\EntityAbstract
     
    public function getEntity($offset=0)
    {
        if ($offset >= ($this->offset + $this->limitPerPage) || $offset < $this->offset)
        {
            // Get total number of pages
			$leftover = $this->totalNum % $this->limitPerPage;
			if ($leftover)
				$numpages = (($this->totalNum - $leftover) / $this->limitPerPage) + 1;
			else
				$numpages = $this->totalNum / $this->limitPerPage;
			
            // Get current page offset
            $page = floor($offset / $this->limitPerPage);
            if ($page)
                $this->setOffset($page * $this->limitPerPage);
            else
                $this->setOffset(0);
            
            // Automatially load the next page
            if ($this->dataMapper)
                $this->dataMapper->loadCollection($this);
        }
        
        // Adjust offset for pagination
        $offset = $offset - $this->offset;
        
        if ($offset >= count($this->entities))
            return false; // TODO: can expand to get next page for progressive load
        
        return $this->entities[$offset];
    }
     */
    
    /**
     * Add a facet count to the list of facets
     * 
     * @param type $facetName
     * @param type $term
     * @param double $count
     
    public function addFacetCount($facetName, $term, $count)
    {
        // TODO: handle facets
        // Look at the elastica addFacet for an example
    }
     * */

    /**
     * Add aggregation to this query
     * 
     * @param Netric\EntityQuery\Aggregation\AbstractAggregation
     */
    public function addAggregation(EntityQuery\Aggregation\AbstractAggregation $agg)
    {
        $this->aggregations[] = $agg;
    }
    
    /**
     * Get aggregations for this query
     * 
     * @return Netric\EntityQuery\Aggregation\AbstractAggregation
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
    
    /**
     * Execute the query for this collection
     * 
     * @return boolean|int Number of entities loaded if success and datamapper is set, false on failure
     */
    public function load()
    {
        if ($this->dataMapper)
            return $this->dataMapper->loadCollection($this);
        else
            return false;
    }

    /**
     * Convert this query to an array
     *
     * @return array ('conditions'=>Wheres[]->toArray, 'order_by'=>
     */
    public function toArray()
    {
        $ret = array(
            "obj_type" => $this->objType,
            "limit" => $this->limitPerPage,
            "offset" => $this->offset,
        );

        // Add all where conditions
        $ret['conditions'] = array();
        $wheres = $this->getWheres();
        foreach ($wheres as $whereCondition)
        {
            $ret['conditions'][] = $whereCondition->toArray();
        }

        // Add order by
        $ret['order_by'] = array();
        $orderBy = $this->getOrderBy();
        foreach ($orderBy as $sortDef)
        {
            $ret['order_by'][] = $sortDef->toArray();
        }

        return $ret;
    }

    /**
     * Load in a query from an array
     *
     * @param array $data The query to load
     * @throws \InvalidArgumentException if the data query is invalid
     */
    public function fromArray(array $data)
    {
        // Basic level validation
        if (!isset($data['obj_type']))
            throw new \InvalidArgumentException("obj_type is a required query index");

        $this->objType = $data['obj_type'];

        if (isset($data['limit']))
            $this->setLimit($data['limit']);

        if (isset($data['offset']))
            $this->setOffset($data['offset']);

        // Add conditions if they were passed
        if (isset($data['conditions']) && is_array($data['conditions']))
        {
            foreach ($data['conditions'] as $condData)
            {
                $where = new Where();
                $where->fromArray($condData);
                $this->addCondition($where);
            }
        }

        // Add order_by if they were passed
        if (isset($data['order_by']) && is_array($data['order_by']))
        {
            foreach ($data['order_by'] as $sortData)
            {
                $order = new OrderBy();
                $order->fromArray($sortData);
                $this->addOrderBy($order);
            }
        }
    }
}
