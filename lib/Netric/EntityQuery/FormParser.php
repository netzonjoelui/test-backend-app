<?php

/**
 * Parse form params and setup the query
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\EntityQuery;

/**
 * Parse form fields to an actual query
 * 
 * $param \Netric\EntityQuery &$query The query to setup
 * @param array $params The REQUEST params to use to build the query
 */
class FormParser 
{
    public static function buildQuery(\Netric\EntityQuery &$query, $params)
    {
        // Add where conditions
        if (isset($params["where"]))
        {
            // Convert to array if only a single value
            if (!is_array($params["where"]))
                $params["where"] = array($params["where"]);
            
            // Add each where condition which is represented in csv
            // as "blogic,fieldname,operator,value" like:
            // "and,first_name,is_equal,sky"
            foreach ($params["where"] as $whereData)
            {
                $whereVals = str_getcsv($whereData);
                
                if (strtolower($whereVals[0]) == "or")
                {
                    $query->orWhere($whereVals[1], $whereVals[2], $whereVals[3]);
                }
                else 
                {
                    $query->where($whereVals[1], $whereVals[2], $whereVals[3]);
                }
            }
        }

        // Look for full text search
        if (isset($params["q"]))
        {
            $query->where("*")->equals ($params["q"]);
        }
        
        // Add order by params
        if (isset($params["order_by"]))
        {
            // Convert to array if only a single value
            if (!is_array($params["order_by"]))
                $params["order_by"] = array($params["order_by"]);
         
            // Add each where condition
            foreach ($params["order_by"] as $orderData)
            {
                $orderVals = str_getcsv($orderData);
                if (!isset($orderVals[1]))
                    $orderVals[1] = OrderBy::ASCENDING;
                
                $query->orderBy($orderVals[0], $orderVals[1]);
            }
        }
        
        // Add offset
        if (isset($params["offset"]) && is_numeric($params["offset"]))
            $query->setOffset($params["offset"]);
        
        // Add limit
        if (isset($params["limit"]) && is_numeric($params["limit"]))
            $query->setLimit($params["limit"]);
    }
}
