<?php
/**
 * Used to represent an olap cube dimension
 *
 * @category  Olap_Cube
 * @package   Dimension
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

class Olap_Cube_Dimension
{
	/**
	 * The id of this dimension if it is stored in the database
	 *
	 * @var integer
	 */
	public $id = null;

	/**
	 * The name of this dimension
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * The type of this dimension
	 *
	 * Here is a list of available types:
	 * 1. string
	 * 2. time
	 * 3. geography
	 * 4. numeric
	 *
	 * @var string
	 */
	public $type = null;

	/**
	 * A dimension can optionally refer to a parent dimension
	 *
	 * For instance, the dimension 'user' can roll up to a 'team' dimension
	 *
	 * @var string
	 */
	public $parentDimensionName = null;

	/**
	 * A dimension can optionally refer to a parent dimension
	 *
	 * For instance, the dimension 'team' can drill down to a 'user' dimension
	 *
	 * @var string
	 */
	public $childDimensionName = null;
}
