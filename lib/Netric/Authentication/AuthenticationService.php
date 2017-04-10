<?php
/**
 * Factory used to initialize the netric filesystem
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Authentication;

use Netric\ServiceManager;
use Netric\EntityLoader;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\Request\RequestInterface;

/**
 * Handle authentication from the cleint
 *
 * @package Netric\Authentication
 */
class AuthenticationService
{
	/**
	 * Private key for generating hmac
	 *
	 * @var string
	 */
	private $privateKey = null;

	/**
	 * User index
	 *
	 * @var Netric\EntityQuery\Index\IndexInterface
	 */
	private $userIndex = null;

	/**
	 * User loader
	 *
	 * @var Netric\EntityLoader
	 */
	private $userLoader = null;

	/**
	 * Update request object
	 *
	 * @var Netric\Request\RequestInterface
	 */
	private $request = null;

	/**
     * Indexes of important fields in an client-side session string
     *
     * @const int
     */
	const SESSIONPART_USERID = 0;
	const SESSIONPART_EXPIRES = 1;
	const SESSIONPART_PASSWORD = 2;
	const SESSIONPART_SIGNATURE = 3;

	/**
     * Error codes
     *
     * @const string
     */
    const IDENTITY_NOT_FOUND = 'identityNotFound';
    const IDENTITY_AMBIGUOUS = 'identityAmbiguous';
    const CREDENTIAL_INVALID = 'credentialInvalid';
    const IDENTITY_DISABLED  = 'identityDisabled';
    const UNCATEGORIZED      = 'uncategorized';
    const GENERAL            = 'general';

    /**
     * Last error code
     *
     * @var string
     */
    private $lastErrorMessage = null;

    /**
     * Error Messages
     *
     * @var array
     */
    protected $messageTemplates = array(
        self::IDENTITY_NOT_FOUND => 'Invalid identity',
        self::IDENTITY_AMBIGUOUS => 'Identity is ambiguous',
        self::IDENTITY_DISABLED  => 'User is no longer active',
        self::CREDENTIAL_INVALID => 'Invalid password',
        self::UNCATEGORIZED      => 'Authentication failed',
        self::GENERAL            => 'Authentication failed',
    );


	/**
	 * The number of params expected in each auth string
	 *
	 * @const int
	 */
	const NUM_AUTH_PARAMS = 4;

	/**
	 * The number of hash iterations to perform
	 *
	 * Must be at least 2 and should not be more than 256
	 *
	 * @const int
	 */
	const HASH_ITERATIONS = 4;

	/**
	 * Default expiration (in seconds) of new sessions
	 *
	 * -1 means the session never expires
	 *
	 * @const int
	 */
	const DEFAULT_EXPIRES = -1;

	/**
	 * Cache the currently authenticated user id so we don't re-validate every request
	 *
	 * @var int
	 */
	private $validatedIdentityUid = null;

	/**
	 * Class constructor
	 *
	 * @param string $privateKey A server-side private key for hmac
	 * @param Netric\EntityQuery\Index\IndexInterface $userIndex for querying users by id
	 * @param Netric\EntityLoader $userLoader Loader to get user entities by id
	 */
	public function __construct($privateKey, IndexInterface $userIndex, EntityLoader $userLoader, RequestInterface $request)
	{
		$this->privateKey = $privateKey;
		$this->userIndex = $userIndex;
		$this->userLoader = $userLoader;
		$this->request = $request;
	}

	/**
	 * Get the id of the current authenticated user (if any)
	 *
	 * @return string Unique id of the user who is authenticated
	 */
	public function getIdentity() 
	{
		// Check to see if this user id has already been validated
		if ($this->validatedIdentityUid)
			return $this->validatedIdentityUid;

		/*
		 * Get auth data array which is a : separated string
		 * User the SESSIONPART_* constants in this class to access the decoded array parts
		 */
		$sessionData = $this->getSessionData();

		if (!$sessionData)
			return null;

		// Get variables from the session data
		$uid = $sessionData[self::SESSIONPART_USERID];
		$expires = $sessionData[self::SESSIONPART_EXPIRES];
		$password = $sessionData[self::SESSIONPART_PASSWORD];
		$signature = $sessionData[self::SESSIONPART_SIGNATURE];

		// Validate the sessionData
		if (!$this->sessionSignatureIsValid($uid, $expires, $password, $signature))
			return null;

		// Cache because validation can be expensive.
		$this->validatedIdentityUid = $uid;

		// Return the id of the authorized user
		return $uid;
	}

	/**
	 * Get explanation for why authentication failed
	 *
	 * @return string
	 */
	public function getFailureReason()
	{
		return ($this->lastErrorMessage) ? $this->messageTemplates[$this->lastErrorMessage] : null;
	}

	/**
	 * Clear authenticated identity
	 */
	public function clearIdentity()
	{
		$this->validatedIdentityUid = null;
	}

	/**
	 * Authenticate a user
	 *
	 * @param string $username Unique username
	 * @param string $password Clear text password for the selected user
	 * @return on success a session string, null on failure
	 */
	public function authenticate($username, $password)
	{
		// Set all initial values and remove validated cache
		$this->clearIdentity();
		$user = null;

		// Make sure we were given credentials
		if (!$username || !$password)
		{
			$this->lastErrorMessage = self::IDENTITY_AMBIGUOUS;
			return null;
		}

		// Load the user by username
		$query = new \Netric\EntityQuery("user");
		$query->where("active")->equals(true);
        $query->andWhere('name')->equals(strtolower($username));
        $query->orWhere('email')->equals(strtolower($username));
        $res = $this->userIndex->executeQuery($query);
        if (!$res->getTotalNum())
        {
        	$this->lastErrorMessage = self::IDENTITY_NOT_FOUND;
			return null;
        }
        else
        {
	        $user = $res->getEntity(0);
        }

        // Make sure user is active
        if (false == $user->getValue("active"))
        {
        	$this->lastErrorMessage = self::IDENTITY_DISABLED;
			return null;
        }

		// Get the salt
		$salt = $user->getValue("password_salt");

		// Check that the hashed passwords are the same
		$hashedPass = $this->hashPassword($password, $salt);
		if (md5($hashedPass) != $user->getValue("password"))
		{
			$this->lastErrorMessage = self::CREDENTIAL_INVALID;
			return null;
		}

		// Cache for future calls to getIdentntiy because validation can be expensive
		$this->validatedIdentityUid = $user->getId();

		// Create a session string
		return $this->getSignedSession($user->getId(), $this->getExpiresTs(), $hashedPass, $salt);
	}

	/**
	 * Generate an authentication string to send to the client
	 */
	public function getSignedSession($uid, $expires, $password, $salt)
	{
		$hashedPass = $this->hashPassword($password, $salt);

		// Put together basic data
		$sessionData = $this->packSessionString($uid, $expires, $hashedPass);

		// Sign
		$signature = $this->getHmacSignature($sessionData);

		return  $sessionData . ":" . $signature;
	}

	/**
	 * Retrieve the authentication header or cookie value
	 *
	 * @return string
	 */
	private function getSessionData()
	{
		// Get authentication from either headers/get/post
		$authStr = $this->request->getParam("Authentication");

		$authData = array();

		// Extract the parts
		$authData = explode(":", $authStr);

		// Make sure all the required data is in place and no more
		if (self::NUM_AUTH_PARAMS != count($authData))
			return null;

		// Appears to have a valid number of params
		return $authData;
	}

	/**
	 * Pack session data into a session string
	 *
	 * @param string $uid The unique id of the authenticated user
	 * @param string $expires A timestamp or -1 for no expiration
	 * @param string $password A pre-hashed encoded password (needs to be hashed once more)
	 */
	public function packSessionString($uid, $expires, $password)
	{
		$sessionDataArr = array(
			self::SESSIONPART_USERID => $uid,
			self::SESSIONPART_EXPIRES => $expires,
			self::SESSIONPART_PASSWORD => $password,
		);
		return implode(":", $sessionDataArr);
	}

	/**
	 * Get expires seconds
	 *
	 * @return int Current timestamp plus number of seconds until it expires
	 */
	public function getExpiresTs()
	{
		if (self::DEFAULT_EXPIRES > 0)
			return time() + self::DEFAULT_EXPIRES;
		else
			return -1;
	}

	/**
	 * Hash a password
	 */
	public function hashPassword($password, $salt)
	{
		// Iterate hash 4 levels
		$n = self::HASH_ITERATIONS;
	    $hpass = md5($salt . $password);

	    do {
	        $hpass = md5($hpass . $password);
	    } while (--$n);

	    return $hpass;
	}

	/**
	 * Generate a new random salt
	 *
	 * @param int $length The length of the salt
	 * @return string Unique random string
	 */
	public function generateSalt($length=128)
	{
		/*
		 * Bin2hex over the raondom bites will double the size so
		 * we are dividing by 2 to make the output match $length
		 */
		return bin2hex(openssl_random_pseudo_bytes($length/2));
	}

	/**
	 * Generate signature from a normalized string
	 *
	 * @param string $data The data string to sign
	 * @return string A hashed signature
	 */
	public function getHmacSignature($data)
	{
		return hash_hmac("sha256", $data, $this->privateKey);
	}

	/**
	 * Set the local request object
	 *
	 * This is a required dependency and injected at construction,
	 * but it can be swapped out manually for things such as
	 * unit testing.
	 *
	 * @param \Netric\Request\RequestInterface $request
	 */
	public function setRequest(\Netric\Request\RequestInterface $request)
	{
		$this->request = $request;
	}

	/**
	 * Validate that auth data retrieved from a client session is valid
	 *
	 * @param string $uid The unique id of the authenticated user
	 * @param string $expires A timestamp or -1 for no expiration
	 * @param string $password A pre-hashed encoded password (needs to be hashed once more)
	 * @param string $signature HMAC signature of the previous params
	 * @return bool true if the session is valid, false if invalid or expired
	 */
	private function sessionSignatureIsValid($uid, $expires, $password, $signature)
	{
		// Make sure session data is valid via HMAC
		$challengeSignature = $this->getHmacSignature($this->packSessionString($uid, $expires, $password));

		// Check to see if the request has been changed since we last signed it
		if ($challengeSignature != $signature)
			return false;

		/*
		 * TODO: Make sure the user's password has not changed?
		 *
		 * This Would definitely add to the security, but it also requires a user load
		 * every single request from cache. This may not be a problem however because
		 * it can be assumed if we are checking authenticated state that we will shortly
		 * be laoding the user. If we always use the \Netric\EntityLoader then it will
		 * always load only once.
		 */

		return true;
	}
}
