<?php
/**
 * Aereus API Library
 *
 * Shopping cart class for ANT products.
 *
 * @category  AntApi
 * @package   AntApi_ShoppingCart
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Shopping cart that interfaces with ANT
 */
class AntApi_ShoppingCart
{
	/**
     * The cart item objects
     *
     * @var array
     */
	private $items = array();

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
	 * Session storage type
	 *
	 * Right now this can either be "zend_session" or "session"
     *
     * @var string
     */
	private $sessionType = "session";

	/**
	 * Session data object
	 *
     * @var Zend_Session_Namespace
     */
	private $sessionData = null;

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

		// Set session type
		if (class_exists('Zend_Application', false))
		{
			$this->sessionType = "zend_session";
			$this->sessionData = new Zend_Session_Namespace('shoppingCart');
		}

		// Load items in session
		$this->loadItems();
	}

	/**
	 * Cleanup
	 */
	public function __destruct()
	{
	}

	/**
	 * Add an item to this cart
	 *
	 * @param AntApi_Product $product	Instance of AntApi_Product to add to this cart
	 * @param int $quantity				The number of the selected products to add to the cart
	 */
	public function addItem(AntApi_Product $product, $quantity=1)
	{
		// First make sure this does not already exist
		foreach ($this->items as $item)
		{
			if ($item->id == $product->id)
			{
				$item->quantity += $quantity;
				$this->writeItems();
				return true;
			}
		}

		// Add item data to session array
		$item = new stdClass();
		$item->id = $product->id;
		$item->name = $product->getValue("name");
		$item->price = (float) $product->getValue("price");
		$item->quantity = $quantity;
		$this->items[] = $item;

		// Add item object to cart items list
		$this->writeItems();

		return true;
	}

	/**
	 * Remove an item from the cart by id
	 * 
	 * @param int $product_id	Unique id of product to remove from cart
	 */
	public function removeItem($product_id)
	{
		if (!$product_id)
			return false;

		$buf = $this->items;
		$this->items = array();

		foreach ($buf as $item)
		{
			if ($item->id != $product_id) // exclude deleted item
				$this->items[] = $item;
		}
		unset($buf);

		$this->writeItems();

		return true;
	}

	/**
	 * Update the order quantity for an item by product id
	 *
	 * @param int $product_id	The unique id of the product object
	 * @param int $quantity		Desired quantity of product
	 * @return bool				true if success, false if failure
	 */
	public function updateQuantity($product_id, $quantity)
	{
		if (!$product_id || !$quantity)
			return false;

		foreach ($this->items as $item)
		{
			if ($item->id == $product_id) // exclude deleted item
				$item->quantity = $quantity;
		}

		$this->writeItems();

		return true;
	}

	/**
	 * Get the total quantity of items in the cart. Each entry can have multiple items
	 */
	public function getQuantityItems()
	{
		$total = 0;

		foreach ($this->items as $item)
			$total += $item->quantity;

		return $total;
	}

	/**
	 * Get the number of line-item entries - not accounting for quantity
	 */
	public function getNumItems()
	{
		return count($this->items);
	}

	/**
	 * Clear the contents
	 */
	public function clear()
	{
		$this->items = array();
		$this->writeItems();
	}
	
	/**
	 * Get an item at the specified index
	 *
	 * @param int $ind	The offset of the item to retrieve
	 */
	public function getItem($ind)
	{
		if (count($this->items) < $ind)
			return false;

		return $this->items[$ind];
	}

	/**
	 * Get total of items without tax or shipping
	 */
	public function getSubTotal()
	{
		$total = 0;

		foreach ($this->items as $item)
			$total += ($item->price * $item->quantity);

		return $total;
	}

	/**
	 * Get total - including tax and shipping
	 */
	public function getTotal()
	{
		// TODO: we will work on this later, for now just return subtotal
		return $this->getSubTotal();
	}

	/**
	 * Create SalesOrder object from contents of cart
	 *
	 * @param string $name	The unique name of this order
	 */
	public function createOrder($name="")
	{
		if (!$name)
			$name = "Online Order - " . date("m/d/Y");

		$order = new AntApi_SalesOrder($this->server, $this->username, $this->password);
		$order->setValue("name", $name);

		// Loop through the items in this cart and add to the order
		foreach ($this->items as $item)
		{
			$order->addItem($item->name, $item->quantity, $item->price, $item->id);
		}

		return $order;
	}

	/**
	 * Load items from session into local items variable
	 */
	private function loadItems()
	{
		// Clear items
		$this->items = array();

		$sess_items = $this->getSessionData('items');

		if (is_array($sess_items))
		{
			foreach ($sess_items as $itemData)
			{
				$item = new stdClass();
				$item->id = $itemData['id'];
				$item->name = $itemData['name'];
				$item->price = $itemData['price'];
				$item->quantity = $itemData['quantity'];
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Get data from session
	 *
	 * @param string key	The unique key for the data
	 */
	private function getSessionData($key)
	{
		global $_SESSION;

		switch ($this->sessionType)
		{
		case 'zend_session':
			return $this->sessionData->cart[$key];
		default:
			return $_SESSION[$key];
		}
	}

	/**
	 * Set session data
	 *
	 * @param string key	The unique key for the data
	 * @param mixed value	The value to set for the given key
	 */
	private function setSessionData($key, $value)
	{
		global $_SESSION;

		switch ($this->sessionType)
		{
		case 'zend_session':
			$this->sessionData->cart[$key] = $value;
			break;
		default:
			$_SESSION[$key] = $value;
			break;
		}
	}

	/**
	 * Write items array to session
	 */
	private function writeItems()
	{
		$data = array();
		foreach ($this->items as $item)
		{
			$idata = array();
			$idata['id'] = $item->id;
			$idata['price'] = $item->price;
			$idata['name'] = $item->name;
			$idata['quantity'] = $item->quantity;
			$data[] = $idata;
		}

		$this->setSessionData("items", $data);
	}
}
