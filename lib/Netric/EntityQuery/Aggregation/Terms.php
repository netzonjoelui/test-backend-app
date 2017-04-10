<?php
/**
 * Terms aggregate will get distinct terms with document count
 * 
 * Returned array is
 * 
 * array(
 *   array(
 *       "count"=>num,
 *       "term"=>term
 *   ),
 * )
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */

namespace Netric\EntityQuery\Aggregation;

/**
 * Terms aggreagation will gather terms and counts from a field
 */
class Terms extends AbstractAggregation implements AggregationInterface
{
    //put your code here
}
