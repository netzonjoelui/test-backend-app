<?php
/**
 * Aereus API Library
 *
 * The authenticate user is an object used to verify if the user is a valid ANT user
 *
 * @category  AntApi
 * @package   AntApi_AuthenticateUser
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class for authenticating user
 */
class AntApi_AuthenticateUser
{
    /**
     * ANT server
     *
     * @var string
     */
    private $server;

    /**
     * Valid ANT user name
     *
     * @var string
     */
    private $username;

    /**
     * ANT user password
     *
     * @var string
     */
    private $password;
    
    /**
     * Account name of the ANT production (e.g. aereus)
     *
     * @var string
     */
    private $account;
        
    /**
     * Ant User Id
     *
     * @var Integer
     */
    var $userId = null;

	/**
	 * AntApi object
	 *
	 * @var AntApi
	 */
	private $antapi = null;
    
    /**
     * Class constructor
     *
     * @param string $server ANT server name
     * @param string $username A Valid ANT user name with appropriate permissions to authenticate users
     * @param string $password ANT user password
     */
    function __construct($server, $username, $password, $account="")
    {
        $this->username = $username;
        $this->password = $password;
        $this->server = $server;
        $this->account = $account;

        if(!empty($_COOKIE["uid"]))
			$this->userId = base64_decode($_COOKIE["uid"]);

		$this->antapi = AntApi::getInstance($server, $username, $password);
    }
    
	/**
	 * Authenticate a user against Netric
	 *
	 * @param string $username The username to check
	 * @param string $password The password to check
	 * @param bool $setCookie if True then set a cookie for the authenticated user
	 */
    public function authenticate($username, $password, $setCookie=true)
    {

		/*
        $url = "http://" . $this->server . "/controller/User/login";
        $url .= "?auth=" . base64_encode("administrator") . ":" . md5("Password1");
        
        $ch = curl_init($url); // URL of gateway for cURL to post to
        curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
        $resp = curl_exec($ch); //execute post and get results
        curl_close ($ch);
        
        $this->userId = json_decode($resp);
        
		*/

		$this->userId = $this->antapi->sendRequest("User", "login", array("name"=>$username, "password"=>$password));

        if($setCookie)
        {
            if($this->userId) // Authenticated
            {
                $expireTime = time() + 3600;
                setcookie("uname", base64_encode($this->username), $expireTime, "/");
                setcookie("uid", base64_encode($this->userId), $expireTime, "/");
                setcookie("aname", base64_encode($this->account), $expireTime, "/");
            }
            else
            {
                $this->destroyCookies();
            }
        }

		return ($this->userId) ? $this->userId : false;
    }
    
	/**
	 * Clear all cookies for this authenticated user
	 */
    public function destroyCookies()
    {
        $oneHourAgo = time() - 3600;
        setcookie("uname", "", $oneHourAgo, "/");
        setcookie("uid", "", $oneHourAgo, "/");
        setcookie("aname", "", $oneHourAgo, "/");
        
        return 1;
    }
}
