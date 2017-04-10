<?php
require_once("lib/AntConfig.php");
require_once("CPageCache.php");
require_once("CAdcClient.php");
require_once("CAntCustomer.php");
require_once("CCache.php");
require_once("CSessions.php");

class CShoppingCart extends CAntCustomer
{
	var $accountInfo;
	var $sessionCustomer;
	var $cart;
	
	
	function CShoppingCart(){
		global $ALIB_ANS_SERVER, $ALIB_ANS_ACCOUNT, $ALIB_ANS_PASS;
		
        if(defined("ALIB_ANS_SERVER"))
            $alibServer = ALIB_ANS_SERVER;
        else if(isset($ALIB_ANS_SERVER))
            $alibServer = $ALIB_ANS_SERVER;
            
        if(defined("ALIB_ANS_ACCOUNT"))
            $alibAccount = ALIB_ANS_ACCOUNT;
        else if(isset($ALIB_ANS_SERVER))
            $alibAccount = $ALIB_ANS_SERVER;
            
        if(defined("ALIB_ANS_PASS"))
            $alibPass = ALIB_ANS_PASS;
        else if(isset($ALIB_ANS_PASS))
            $alibPass = $ALIB_ANS_PASS;
        
        parent::__construct($alibServer, $alibAccount, $alibPass);
		
		$this->openSession();
		if( $this->isLogin() )
		{
			$this->setCurrentUser($this->getAttribute('id'));	
		}
	}
	
	function authUser($username, $password)
	{
		$customerID = parent::authUser($username, $password);
		
		if( $customerID>0 )
		{
			$this->setSessionVar('username',$username);
			$this->setCurrentUser($customerID);	
		}
		
		return $customerID;
	}
	
	function logout(){
		@session_start();
		$_SESSION['ses_cur_customer'] = array();
	}
	
	function getAttribute( $key ){
		if( !$this->isLogin() )	return false;
		
		return $this->sessionCustomer[$key];
	}
	
	function setCurrentUser ($customerid) 
	{ 
		if( $customerid<=0 ) return false;
		
		// get customer info and save to session
		$this->open($customerid);
		
		foreach( $this->m_attribs as $key => $value )
		{
			$this->setSessionVar($key,$value);
		}
		
		$this->saveSession();
		$this->openSession();
	}
	
	
	function cartAddProduct($productid,$qty=1)
	{	
	
		global $ALIB_ANS_SERVER2,$ALIB_ANS_ACCOUNT2,$ALIB_ANS_PASS2;
		
		$adc = new CAdcClient($ALIB_ANS_SERVER2,1,$ALIB_ANS_ACCOUNT2,$ALIB_ANS_PASS2);
		
		if( isset($this->cart['products'][$productid]['qty']) )
		{
			$this->cart['products'][$productid]['qty'] += $qty;
		}
		else
		{
			$this->cart['products'][$productid]['qty'] = $qty;
		}
		
		$this->cartSaveContents();
	}
	
	function cartRemoveProduct ($productid)
	{
		unset($this->cart['products'][$productid]);
		$this->cartSaveContents();
	}
	
	function cartUpdateProductQty ($productid,$qty=1)
	{
		if( isset($this->cart['products'][$productid]) )
		{
			$this->cart['products'][$productid]['qty'] = $qty;
		}
		$this->cartSaveContents();
	}
	
	function cartGetContents(){
		
		$cart = $this->cart;
		
		$cart['total_qty'] = 0;
		if( count($cart['products'])>0 )
		{
			foreach( $cart['products'] as $productID => $productItem )
			{
				$cart['total_qty'] += $productItem['qty'];
				$cart['products'][$productID] = array_merge($cart['products'][$productID],productGetProductItem($productID));
				// price
				$cart['products'][$productID]['price'] = ($cart['products'][$productID]['price']<=0) ? 0 : $cart['products'][$productID]['price'];
				$cart['products'][$productID]['totalprice'] = ($cart['products'][$productID]['price']*$productItem['qty']);
				$cart['total_price'] += $cart['products'][$productID]['totalprice'];
			}
		}
		
		return $cart;
	}
	
	function cartSaveContents(){
		
		if( !$this->isLogin() )
		{
			// generate cart session id
			$this->cart['cache_id'] = session_id();
		}
		else
		{
			$this->cart['cache_id'] = $this->sessionCustomer['id'];
		}
		
		
		
		$cache = new CPageCache(604800, "cart_".$this->cart['cache_id']); // 7 days
		$cache->put(serialize($this->cart));
		$cache->writeBuf();
		
	}
	
	function isLogin(){
		return $this->sessionCustomer['id']>0 ? true : false;
	}
	
	
	function setSessionVar($key, $data){
		$this->sessionCustomer[$key] = $data;
	}
	
	function saveSession(){
		/*
		$session = new CSessions();
		$session->write('ses_cur_customer',$this->sessionCustomer);
		*/
		@session_start();
		$_SESSION['ses_cur_customer'] = $this->sessionCustomer;
	}
	
	function openSession(){
		/*
		$session = new CSessions();
		return $session->read('ses_cur_customer');
		*/
		@session_start();
		$this->sessionCustomer = $_SESSION['ses_cur_customer'];
		
		if( $this->isLogin() )
		{
			$cache = new CPageCache(604800, "cart_".$this->sessionCustomer['id']); // 7 days
		}
		else
		{
			$cache = new CPageCache(604800, "cart_".session_id()); // 7 days
		}
		
		if ( !$cache->IsExpired() )
		{
			$this->cart = unserialize($cache->getCache());
		}
	}
	
	
	
	
}
?>
