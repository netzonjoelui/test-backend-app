<?php
/*
 * Interface definition for indexes
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\EntityQuery\Index;

use Netric\EntityQuery;
use Netric\EntityQuery\Results;

/**
 * Main index interface for DI
 */
interface IndexInterface 
{
    /**
     * Execute a query and return the results
     *
     * @param EntityQuery $query A query to execute
     * @param Results $results Optional results set to use. Otherwise create new.
     * @return \Netric\EntityQuery\Results
     */
    public function executeQuery(EntityQuery $query, Results $results = null);
}
