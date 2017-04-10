<?php
/**
 * Abstract commit datamapper
 *
 * @category	DataMapper
 * @author		joe, sky.stebnicki@aereus.com
 * @copyright	Copyright (c) 2003-2014 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Commit\DataMapper;

interface DataMapperInterface
{
   /**
    * Get next id
    *
    * @param string $typekey Can be any unique key
    * @return bigint
    */
  public function getNextCommitId($typekey);

   /**
    * Set the head commit id for a collection
    *
    * @param string $typekey
    * @param bigint $cid
    */
  public function saveHead($typekey, $cid);

  /**
   * Get the head commit id for a collection
   *
   * @param string $typekey
   * @return bigint
   */
  public function getHead($typekey);
}