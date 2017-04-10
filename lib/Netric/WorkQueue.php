<?php
/**
 * Store lists of work to be done
 * 
 * Use to store potentially very long lists in the database.
 * Example usage would be bulk mail, all the addresses will be
 * queued up, and then the process can send x number at a time
 * and pick up where it left off previously.
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric;

/**
 * Description of WorkQueue
 */
class WorkQueue 
{
    /**
     * Push an item into the queue
     * 
     * @param string $name Unique name of the queue
     * @param mixed $data Data to be serialized
     */
    public function push($name, $data)
    {
        
    }
    
    public function shift($name)
    {
        
    }
    
    public function pop($name)
    {
        
    }
}
