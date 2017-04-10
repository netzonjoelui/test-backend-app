<?php
/**
 * ANT class used to load appropriate payment gateway
 *
 * @category  PaymentGateway
 * @package   PaymentGatewayManager
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

require_once("lib/PaymentGateway.php");

/**
 * Manage gateways in ant
 */
class PaymentGatewayManager
{
	/**
     * Reference to an ant account object
     *
     * @var Ant
	 */
	private $ant;

	/**
	 * Class constructor
	 * 
	 * @param Ant $ant A reference to an ant account object
	 */
	function __construct($ant)
	{
		$this->ant = $ant;
	}


	/**
	 * Check if gateway is set - this usually called statically with PaymentGatewayLoader::hasGateway($dbh)
	 * 
	 * @param CDatabase $dbh reference to a account database
	 */
	public static function hasGateway($dbh=null)
	{
		if (!$dbh && isset($this))
			$dbh = $this->ant->dbh;

		if (!$dbh)
			return false;

		$gwType = $this->ant->settingsGet("/general/paymentgateway", $dbh);

		return ($gwType) ? true : false;
	}

	/**
	 * Retrieve gateway object - this usually called statically with PaymentGatewayLoader::getGateway($dbh)
	 * 
	 * @param CDatabase $dbh reference to a account database
	 * @param int $type override the system setting
	 */
	public static function getGateway($dbh=null, $type=null)
	{
		if (!$dbh && isset($this))
			$dbh = $this->ant->dbh;

		if (!$dbh)
			return false;

		$gw = null; // Gateway object to return 

		if ($type)
			$gwType = $type;
		else
			$gwType = $this->ant->settingsGet("/general/paymentgateway", $dbh);

		switch ($gwType)
		{
		case PMTGW_AUTHDOTNET:
			$login = $this->ant->settingsGet("/general/paymentgateway/authdotnet/login", $dbh);
			$key = $this->ant->settingsGet("/general/paymentgateway/authdotnet/key", $dbh);

			if ($login && $key)
			{
				$gw = new PaymentGateway_AuthDotNet(decrypt($login), decrypt($key));
			}
			break;

		case PMTGW_LINKPOINT:
			$storeNumber = $this->ant->settingsGet("/general/paymentgateway/linkpoint/store", $dbh);
			$pem = $this->ant->settingsGet("/general/paymentgateway/linkpoint/pem", $dbh);

			if ($storeNumber && $pem)
			{
				$gw = new PaymentGateway_LinkPoint(decrypt(trim($storeNumber)), decrypt(trim($pem)));
			}
			break;

		case PMTGW_TEST:
			$gw = new PaymentGateway_Test();
			break;
		}

		return $gw;
	}

	/**
	 * Set settings for auth dot net
	 * 
	 * @param string $login The authorize.net assigned login
	 * @param string $key The authorize.net assigned transaction key
	 * @return true on success false on failure
	 */
	public function setAuthDotNet($login, $key)
	{
		if (!$login || !$key)
			return false;

		$this->ant->settingsSet("/general/paymentgateway", PMTGW_AUTHDOTNET);
		$this->ant->settingsSet("/general/paymentgateway/authdotnet/login", encrypt($login));
		$this->ant->settingsSet("/general/paymentgateway/authdotnet/key", encrypt($key));

		return true;
	}

	/**
	 * Remove gateway
	 * 
	 * @return true on success false on failure
	 */
	public function setNoGateway()
	{
		$this->ant->settingsSet("/general/paymentgateway", "");
		return true;
	}
}
