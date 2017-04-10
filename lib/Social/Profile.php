<?php
/**
 * This is the base class used to represent a users profile
 *
 * @category AntSocial
 * @package Profile
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */


/**
 * Generic profile class
 */
class AntSocial_Profile
{
	/**
	 * Unique id
	 *
	 * @var string
	 */
	public $id = null;

	/**
	 * User name
	 *
	 * @var string
	 */
	public $username = null;

	/**
	 * Full name
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * First name
	 *
	 * @var string
	 */
	public $firstName = null;

	/**
	 * Last Name
	 *
	 * @var string
	 */
	public $lastName = null;

	/**
	 * Email
	 *
	 * @var string
	 */
	public $email = null;

	/**
	 * Profile image url
	 *
	 * @var string
	 */
	public $image = null;

	/**
	 * Link to social network profile
	 *
	 * @var string
	 */
	public $link = null;
}
