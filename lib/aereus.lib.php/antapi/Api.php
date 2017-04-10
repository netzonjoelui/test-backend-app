<?php
/*======================================================================================
	
	Module:		AntApi	

	Purpose:	Interact with objects locally that are automatically synchronized with ANT.
				This is the base class used by all the store implementation classes.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.

	SECURITY:	Make sure you either manually define what fields to sync with the addLocalField function or
				call the API with a user that has limited access to important fields.
	
	Depends:	

	Usage:		

	Variables:	$ALIB_OBJAPI_LCLSTORE = pgsql|mysql|elastic|mongodb - if empty then this class is not used

======================================================================================*/

class AntApi
{
	/**
	 * Store the single instance of class for singleton pattern
	 *
	 * @var $this
	 */
	private static $m_pInstance;

	// Serve location and auth variables (user/pass)
	var $server = null;
	var $username = null;
	var $password = null;
    
    /**
     * Flag used to run object index when saving
     *
     * @var bool
     */
    public $runIndexOnSave = false;
	
    // Optional local store allows data to be cached and stored locally
	var $localStore = null;
    
	/**
	 * Class constructor
	 *
	 * @param string $server The Netric server name or ip to connect to for
	 * @param string $username The user name to connect as
	 * @param string $password The password for the connecting user
	 */
	function __construct($server, $username, $password) 
	{
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
	}

	function __destruct() 
	{
	}

	/**
	 * Factory for returing a singleton reference to this class
	 *
	 * @param string $server The Netric server name or ip to connect to for
	 * @param string $username The user name to connect as
	 * @param string $password The password for the connecting user
	 * @return AntApi
	 */
	public static function getInstance($server, $username, $password) 
	{ 
		if (!self::$m_pInstance) 
		{
			self::$m_pInstance = new AntApi($server, $username, $password); 
		}

		return self::$m_pInstance; 
	}

	//====================================================================================
	//  Admin functions
	//====================================================================================
	public function settingsGet()
	{
	}

	public function settingsSet()
	{
	}

	//====================================================================================
	//  Public Get Functions
	//====================================================================================

	/*************************************************************************************
	*	Function:	getObject
	*
	*	Purpose:	Retrieve a single object of any type via a CAntObjectApi class.
	*
	*	Params:		string $type = the name of the type of object. e.g. "customer" or "lead"
	*				string $id = the unique id of the object to retrieve
	**************************************************************************************/
	public function getObject($type, $id = null)
	{
		$obj = new AntApi_Object($this->server, $this->username, $this->password, $type);
        $obj->m_antApiObj = $this;
        
		if ($id)
			$obj->open($id);

        return $obj;
	}

	/*************************************************************************************
	*	Function:	getObjectList
	*
	*	Purpose:	Retrieve AntApi_ObjectList class. getObjects will need to be called to
	*				get the actual results.
	*
	*	Params:		string $type = the name of the type of object. e.g. "customer" or "lead"
	**************************************************************************************/
	public function getObjectList($type)
	{
		$objList = new AntApi_ObjectList($this->server, $this->username, $this->password, $type);
		return $objList;
	}

	/*************************************************************************************
	*	Function:	getContentFeed
	*
	*	Purpose:	Retrieve a content feed from ANT including posts
	*
	*	Params:		string $id = the unique id of the feed to retrieve
	**************************************************************************************/
	public function getContentFeed($id = null)
	{
		$feed = new AntApi_ContentFeed($this->server, $this->username, $this->password, $id);
		return $feed;
	}

	/*************************************************************************************
	*	Function:	getBlog
	*
	*	Purpose:	Retrieve a blog encapsulation of a content feed from ANT
	*
	*	Params:		string $id = the unique id of the feed to retrieve and treat as a blog
	**************************************************************************************/
	public function getBlog($id = null)
	{
		$blog = new AntApi_Blog($this->server, $this->username, $this->password, $id);
		return $blog;
	}

	/**
	 * Get olap cube
	 */
	public function getOlapCube($cubeName = null)
	{
		$cubeapi = new AntApi_OlapCube($this->server, $this->username, $this->password);          

		if ($cubeName)
			$cubeapi->getCube($cubeName);

		return $cubeapi;
	}

	/*************************************************************************************
	*	Function:	getInfoCenter
	*
	*	Purpose:	Retrieve a infocenter encapsulation of a ant objects from ANT
	*
	*	Params:		string $rootGid = The id of the root group to publish in the infocenter
	**************************************************************************************/
	public function getInfoCenter($rootGid = null)
	{
		$blog = new AntApi_InfoCenter($this->server, $this->username, $this->password, $rootGid);
		return $blog;
	}

	/*************************************************************************************
	*	Function:	getCustomer
	*
	*	Purpose:	Retrieve a customer object which inherits AntApi_Object
	*
	*	Params:		string $id = the unique id of the customer to retrieve
	**************************************************************************************/
	public function getCustomer($id = null)
	{
		$cust = new AntApi_Customer($this->server, $this->username, $this->password);
		if ($id)
			$cust->open($id);
		return $cust;
	}

	/*************************************************************************************
	*	Function:	getWiki
	*
	*	Purpose:	Retrieve a infocenter_document object which inherits AntApi_Object
	*
	*	Params:		string $id = the unique id of the customer to retrieve
	**************************************************************************************/
	public function getWiki($id = null)
	{
		$cust = new AntApi_wiki($this->server, $this->username, $this->password);
		if ($id)
			$cust->open($id);
		return $cust;
	}

	/**
	 * Get CMS object for a site
	 *
	 * @param int $siteId Required site id
	 * @return AntApi_Cms Reference to cms object
	 */
	public function getCms($siteId)
	{
		$cms = new AntApi_Cms($this->server, $this->username, $this->password, $siteId);
		return $cms;
	}

	/*************************************************************************************
	*	Function:	getShoppingCart
	*
	*	Purpose:	Create and return a shopping cart object
	**************************************************************************************/
	public function getShoppingCart()
	{
		$cart = new AntApi_ShoppingCart($this->server, $this->username, $this->password);
		return $cart;
	}

	/*************************************************************************************
	*	Function:	getProductCatalog
	*
	*	Purpose:	Create and return a catalog object
	**************************************************************************************/
	public function getProductCatalog()
	{
		$catalog = new AntApi_ProductCatalog($this->server, $this->username, $this->password);
		return $catalog;
	}
    

	/**
	 * Authenticate a netric user
	 *
	 * @param string $username The name of the user to authenticate
	 * @param string $password The password for the authenticate user
	 * @return AntApi_AuthenticateUser object
	 */
    public function authenticateUser($username, $password)
    {
        $authenticate = new AntApi_AuthenticateUser($this->server, $authInfo["username"], $authInfo["password"], $authInfo["account"]);
        
        $authenticate->userId = 1; // Temporarily enables the edit mode until the user authentication is fixed
        if($authenticate->userId)
        {
        }
        
        return $authenticate;
    }
    
    /**
     * Creates a searcher for object lists
     *     
     * @return AntApi_Searcher object
     */
    public function createSearcher()
    {
        $searcher = new AntApi_Searcher($this->server, $this->username, $this->password);
        return $searcher;
    }

	/**
	 * Internal function for sending and receiving data
	 *
	 * Can be called statically if params are all set
	 *
	 * @param string $controller The controller name to call
	 * @param string $action The action function to call
	 * @param array $data Data to be sent (POST) to the server
	 */
	public function sendRequest($controller, $action, $data, $server=null, $user=null, $pass=null)
	{
		if (!$server && isset($this))
			$server = $this->server;

		if (!$user && isset($this))
			$user = $this->username;

		if (!$pass && isset($this))
			$pass = $this->password;

		if (ANTAPI_NOHTTPS)
			$url = "http://";
		else
			$url = "https://";

		if (!$server)
			throw new Exception("Server is a required param to send requests through the AntApi");

		$url .= $server . "/api/php/$controller/$action";
		$url .= "?auth=".base64_encode($user).":".md5($pass);

        if($this->runIndexOnSave)
            $url .= "&runindex=1";

        $ret = -1; // Assume fail

		$fields = "";
		foreach ($data as $fname=>$fval)
		{
            if ($fields) $fields .= "&";
            
			if (is_array($fval))
			{
                $addAmp = false;
				foreach ($fval as $idx=>$subval)
                {
                    if ($addAmp) $fields .= "&";
                    
                    $fields .= $fname . "[]=" . urlencode($subval);
                    $addAmp = true;
                }
			}
			else
			{
                $fields .= $fname . "=" . urlencode($fval);
			}
		}

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		return $resp;
	}
}

/* The below is used only for backwards compatibility */
class CAntApi extends AntApi
{
}
?>
