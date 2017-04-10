<?php
/**
 * This defines the base aggregate for queries
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\EntityQuery\Aggregation;
/**
 * Base aggregate class
 */
interface AggregationInterface
{
    /**
     * Set the name of this aggregation
     * 
     * @param string $name
     */
    public function setName($name);

    /**
     * Retrieve the name of this aggregation
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Set the field for this aggregation
     * 
     * @param string $field the name of the document field on which to perform this aggregation
     */
    public function setField($field);
    
    /**
     * Get the field for this aggregation
     * 
     * @return string
     */
    public function getField();
    
    /**
     * Tries to guess the name of the aggregate, based on its class
     * Example: \Netric\EntityQuery\Aggregations\TermsFilter => terms_filter
     *
     * @param string|object Class or Class name
     * @return string parameter name
     */
    public function getTypeName();
}
