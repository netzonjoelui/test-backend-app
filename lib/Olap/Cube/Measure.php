<?php
/**
 * Used to represent an olap cube measure
 *
 * @category  Olap_Cube
 * @package   Measure
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

class Olap_Cube_Measure
{
	/**
	 * The id of this measure if it is stored in the database
	 *
	 * @var integer
	 */
	public $id = null;

	/**
	 * The name of this measure
	 *
	 * @var string
	 */
	public $name = null;
}
