<?php

class CAntObjectIndexEq extends CAntObjectIndex
{
    public function __construct($dbh, $obj) 
	{ 
        parent::__construct($dbh, $obj); 

		// Increment this if major changes are made to the way objects are indexed
		// causing them to be reindexed
		$this->engineRev = 1; 
    }
    
    /**
	 * Query an index and populate $objList with results.
	 *
	 * @param CAntObjectList $objList Instance of object list that is calling this index
	 * @param string $conditionText Optional full-text query string
	 * @param array $conditions Conditions array - array(array('blogic', 'field', 'operator', 'value'))
	 * @param array $orderBy = array(array('fieldname'=>'asc'|'desc'))
	 * @param int $offset Start offset
	 * @param int $limit The number of items to return with each query
	 */
	public function queryObjects($objList, $conditionText="", $conditions=array(), $orderBy=array(), $offset=0, $limit=500)
	{
        parent::queryObjects($objList, $conditionText, $conditions, $orderBy, $offset, $limit); 
        
        
        
        $query = new \Netric\EntityQuery($this->obj->object_type);
        $query->setOffset($offset);
        $query->setLimit($limit);
        
        // Add conditions
        if (count($conditions))
        {
            foreach ($conditions as $cond)
            {
                if ('t' === $cond['value'])
                    $cond['value'] = true;
                
                if ('f' === $cond['value'])
                    $cond['value'] = false;
                
                if (strtolower($cond['blogic']) == "or")
                    $query->orWhere($cond['field'], $cond['operator'], $cond['value']);
                else
                    $query->andWhere($cond['field'], $cond['operator'], $cond['value']);
            }
        }
        
        // Add fulltext query
        if ($conditionText)
            $query->where('*')->fullText($conditionText);
        
        // Add orderby
        foreach ($orderBy as $sortObj)
        {
            $query->orderBy($sortObj->fieldName, $sortObj->direction);
        }
        
        // Add facet or (terms) aggregations
        if (is_array($this->facetFields) && count($this->facetFields))
        {
            foreach ($this->facetFields as $fldname=>$fldcnt)
            {
                $agg = new \Netric\EntityQuery\Aggregation\Terms($fldname);
                $agg->setField($fldname);
                $query->addAggregation($agg);
            }
        }
        
        // Add simple aggregations
        if (is_array($this->aggregateFields) && count($this->aggregateFields))
        {
            foreach ($this->aggregateFields as $fldname=>$type)
            {
                $agg = null;
                
                switch ($type)
                {
                case 'avg':
                    $agg = new \Netric\EntityQuery\Aggregation\Avg($fldname);
                    break;
                case 'sum':
                default:
                    $agg = new \Netric\EntityQuery\Aggregation\Sum($fldname);
                }
                
                if ($agg)
                {
                    $agg->setField($fldname);
                    $query->addAggregation($agg);
                }
            }
        }
        
        $sl = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator();
        $index = $sl->get("EntityQuery_Index");
        $res = $index->executeQuery($query);
        
		$this->objList->lastQuery = $query;
		//echo "<query>$query</query>";
		$this->objList->total_num = $res->getTotalNum();

		// Get fields for this object type (used in decoding multi-valued fields)
		$ofields = $this->obj->fields->getFields();

        for ($i = 0; $i < $res->getNum(); $i++)
        {
            $ent = $res->getEntity($i+$offset);
            
            $this->objList->objects[$i] = array();
            $this->objList->objects[$i]['id'] = $ent->getId();
            $this->objList->objects[$i]['obj'] = null;
            $this->objList->objects[$i]['revision'] = $ent->getValue("revision");
            $this->objList->objects[$i]['owner_id'] = ($ent->getValue("owner_id")) ? $ent->getValue("owner_id") : null;
            $this->objList->objects[$i]['data_min'] = $ent->toArray();
            $this->objList->objects[$i]['data'] = $ent->toArray();
        }	

        // Get facets - in the object list these were always a simple terms aggregate
        // ----------------------------------------
        if (is_array($this->facetFields) && count($this->facetFields))
        {
            foreach ($this->facetFields as $fldname=>$fldcnt)
            {
                $agg = $res->getAggregation($fldname);
                
                foreach ($agg as $termStat)
                {
                    if(!isset($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();
                    else if(!is_array($this->objList->facetCounts[$fldname]))
                        $this->objList->facetCounts[$fldname] = array();

                    $this->objList->facetCounts[$fldname][$termStat['term']] = $termStat['count'];
                }
            }
        }		

        // Get aggregates
        // ----------------------------------------
        if (is_array($this->aggregateFields) && count($this->aggregateFields))
        {
            foreach ($this->aggregateFields as $fldname=>$type)
            {
                if(!isset($this->objList->aggregateCounts[$fldname]))
                    $this->objList->aggregateCounts[$fldname] = array();
                else if(!is_array($this->objList->aggregateCounts[$fldname]))
                    $this->objList->aggregateCounts[$fldname] = array();

                $this->objList->aggregateCounts[$fldname][$type] = $res->getAggregation($fldname);
            }
		}
        
        return $res->getNum();
    }
}