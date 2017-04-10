<?php
/**
 * Aereus API Library
 *
 * The product catalog is an object used to display/manage products in ANT. For the
 * most part all the functionality is dependent on AntApi_Object and AntApi_ObjectList
 * but this class provides some shortcuts to make the product listing process
 * easier.
 *
 * @category  AntApi
 * @package   AntApi_ProductCatalog
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class for managing product catalogs
 */
class AntApi_ProductCatalog
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
    
    private $categories;

	/**
     * Class constructor
	 *
	 * @param string $server	ANT server name
	 * @param string $username	A Valid ANT user name with appropriate permissions
	 * @param string $password	ANT user password
     */
	function __construct($server, $username, $password) 
	{
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;

		// Object list for product families
		$this->objListProductFamily = new AntApi_ObjectList($this->server, $this->username, $this->password, "product_family");
	}

	/**
	 * Get product by id. Create new one if no id is sent
	 *
	 * @param string $pid	The unique id of the product to get (can be a uname)
	 */
	public function getProduct($pid=null)
	{
		$obj = new AntApi_Product($this->server, $this->username, $this->password);
		if ($pid)
			$obj->open($pid);
		return $obj;
	}

	/**
	 * Get product family by id. Create new one if no id is sent
	 *
	 * @param string $pid	The unique id of the product to get (can be a uname)
	 */
	public function getProductFamily($fid=null)
	{
		$obj = new AntApi_Object($this->server, $this->username, $this->password, "product_family");
		if ($fid)
			$obj->open($fid);
		return $obj;
	}

	/**
	 * Get AntApi_ObjectList for type=product
	 *
	 * @param bool $onlyAvailable	Defaults to true and used to filter out products that are not flagged as available
	 */
	public function getProductList($onlyAvailable=true)
	{
		$ol = new AntApi_ProductList($this->server, $this->username, $this->password);
		if ($onlyAvailable)
			$ol->addCondition("and", "f_available", "is_equal", 't');
		return $ol;
	}

	/**
	 * Get AntApi_ObjectList for type=product_family
	 *
	 * @param bool $onlyAvailable	Defaults to true and used to filter out product families that are not flagged as available
	 */
	public function getProductFamilyList($onlyAvailable=true)
	{
		if ($onlyAvailable)
			$this->objListProductFamily->addCondition("and", "f_available", "is_equal", 't');

		return $this->objListProductFamily;
	}

	/**
	 * Get categroies
	 *
	 * If a parent category is defined then subcategories will be returned
	 *
	 * @param string $catid	OPTIONAL parent category
	 */
	public function getCategories($catid=null)
	{
		if (!is_array($this->categories))
		{
			$this->categories = array(); // Initialize

            //$url = "http://".$this->server."/objects/xml_get_groupings.php";
			$url = "http://".$this->server."/api/php/Object/getGroupings";
            $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
			$url .= "&obj_type=product";
			$url .= "&field=categories";

			$ch = curl_init($url); // URL of gateway for cURL to post to
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			$resp = curl_exec($ch); //execute post and get results
			curl_close ($ch);

			/*$dom = new DomDocument();
			$dom->loadXml($resp); 
			foreach ($dom->documentElement->childNodes as $node) 
			{
				if ($node->nodeType == 1 && $node->nodeName == "value") 
				{
					$cat = new stdClass();
					$cat->id = $node->getAttribute("id");
					$cat->name = $node->getAttribute("viewname");
					$this->categories[] = $cat;
				}
			}*/
            
            $ret = json_decode($resp, true);
            foreach($ret as $key=>$category)
            {
                $cat = new stdClass();
                $cat->id = $category["id"];
                $cat->name = $category["viewname"];
                $cat->title = $category["title"];

                $this->categories[] = $cat;
            }
		}
        
        return $this->categories;
	}
}
