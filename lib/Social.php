<?php
/**
 * This is the base class used for social network integration
 *
 * @category ANT
 * @package Social
 * @copyright Copyright (c) 2003-2023 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Social/Profile.php");
require_once("lib/Social/Friend.php");
require_once("lib/Social/Facebook.php");


/**
 * Social base class
 */
abstract class AntSocial
{
	/**
	 * Current user
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * Class constructor
	 */
	public function __construct($user)
	{
		$this->user = $user;

		$this->setup();
	}

	/**
	 * Optional setup function used for derrived classes
	 */
	public function setup()
	{
	}

	/**
	 * Check to see if a user is authenticated
	 *
	 * @return bool true if they are, false if they are not
	 */
	abstract function isAuthenticated();

	/**
	 * Get a users profile
	 *
	 * @return AntSocial_Profile on success, false on failure
	 */
	abstract function getProfile();

	/**
	 * Get list of friends for the current user
	 *
	 * @return array of AntSocial_Friend objects or false if not authenticated
	 */
	abstract function getFriends();
}
