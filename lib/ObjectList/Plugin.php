<?php
/**
 * Base empty class used to define the plugin interface
 *
 * @category  	AntObjectList
 * @package   	Plugin
 * @author 		joe <sky.stebnicki@aereus.com>
 * @copyright 	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Main Olap Cube class that must be implemented with interface subclass
 */
class AntObjectList_Plugin
{
	/**
	 * Handle to object list
	 *
	 * @var CAntObjectList
	 */
	public $objectList = null;

	/**
	 * Class constructor
	 *
	 * @param CAntObjectList $objectList Handle to current object list that will be calling this plugin
	 */
	public function __construct($objectList)
	{
		$this->objectList = $objectList;
	}

	/**
	 * Called just before a query is executed in the object list
	 */
	public function onQueryObjectsBefore()
	{
	}

	/**
	 * Called just after a query is executed
	 */
	public function onQueryObjectsAfter()
	{
	}
}
