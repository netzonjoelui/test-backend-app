<?php
/**
 * Aereus Object Customer
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * function like "sendInvitations" for calendar events
 *
 * @category	CAntObject
 * @package		Customer 
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
define("CUST_TYPE_CONTACT", 1);
define("CUST_TYPE_ACCOUNT", 2);

/**
 * Object extensions for managing customers
 */
class CAntObject_Customer extends CAntObject
{
	/**
	 * Array of cards for this customer
	 *
	 * @var array(array('name', 'number', 'exp_month', 'exp_year'))
	 */
	private $creditCards = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The customer id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "customer", $eid, $user);
	}

	/**
	 * Fired before save
	 */
	protected function beforesaved()
	{
		if ($this->getValue("type_id") == 1)
		{
			$this->setValue("name", $this->getValue("first_name") . " " . $this->getValue("last_name"));
		}

		// Try to get image from facebook
		if (!$this->getValue("image_id") && $this->getValue("facebook"))
		{
			if (strpos($this->getValue("facebook"), "/pages/") === false)
			{
				preg_match('#https?\://(?:www\.)?facebook\.com/(\d+|[A-Za-z0-9\.]+)/?#',$this->getValue("facebook"),$matches);
				$username = $matches[1];
			}
			else
			{
				preg_match('#https?\://(?:www\.)?facebook\.com/pages/([A-Za-z0-9\._-]+)/(\d+|[A-Za-z0-9\.]+)/?#',$this->getValue("facebook"),$matches);
				$username = $matches[2];
			}

			if ($username)
			{
				$picbinary = file_get_contents("http://graph.facebook.com/".$username."/picture?type=large");
				if (sizeof($picbinary)>0)
				{
					$antfs = new AntFs($this->dbh, $this->user);
					$fldr = $antfs->openFolder("%tmp%", true); // Will be moved after saved with id
					$file = $fldr->openFile("fb-profilepic.jpg", true);
					$size = $file->write($picbinary);
					if ($file->id)
						$this->setValue("image_id", $file->id);
				}
			}
		}
	}
	
	/**
	 * Function used for derrived classes to hook save event
	 *
	 * This is called after CAntObject base saves all properties
	 */
	protected function saved()
	{
        $dbh = $this->dbh;
		// Insert new credit cards
		if (is_array($this->creditCards))
		{
			foreach ($this->creditCards as $card)
			{
				if (isset($card['id']))
				{
					// Insert new card into database
					$result = $dbh->Query("insert into customer_ccards(ccard_name, ccard_number, ccard_exp_month, ccard_exp_year, 
																	 customer_id, enc_ver, f_default)
											values('".$dbh->Escape($card['nameoncard'])."', 
													'".$dbh->Escape(encrypt($card['number']))."', 
													'".$card['exp_month']."', '".$card['exp_year']."', 
													".$dbh->EscapeNumber($this->id).", 
													'1', '".(($card['default'])?'t':'f')."'); 
													select currval('customer_ccards_id_seq') as id;");
					$id = $dbh->GetValue($result, 0, "id");
				}
			}
		}

		// Set primary contact of primary_account if not already set and make sure it is never set to this->id
		if ($this->getValue("primary_account") && $this->getValue("primary_account")!=$this->id)
		{
			$custAcct = CAntObject::factory($this->dbh, "customer", $this->getValue("primary_account"), $this->user);
			if (!$custAcct->getValue("primary_contact"))
			{
				$custAcct->setValue("primary_contact", $this->id);
				$custAcct->save();
			}
		}
	}

	/**
	 * Add a credit card
	 *
	 * @param string $number the actual credit card number
	 * @param int $expMonth the month the card expires
	 * @param int #expYear the year the card expires
	 * @param string $nameOnCard the full cardholder name
	 * @param bool $default if set to true, this card will be used by default
	 * @return true on success, false on failure
	 */
	public function addCreditCard($number, $expMonth, $expYear, $nameOnCard, $default=true)
	{
		$cardData = array();
		$cardData['nameoncard'] = $nameOnCard;
		$cardData['exp_year'] = $expYear;
		$cardData['exp_month'] = $expMonth;
		$cardData['number'] = $number;
		$cardData['type'] = ""; // no longer used
		$cardData['last4'] = substr($number, -4, 4);
		$cardData['default'] = $default;
		$this->creditCards[] = $cardData;
	}

	/**
	 * Get default credit card
	 *
	 * @return array('number', 'exp_month', 'exp_year', 'nameoncard')
	 */
	public function getDefaultCreditCard()
	{
		if (!is_array($this->creditCards))
			return false;

		foreach ($this->creditCards as $card)
		{
			if ($card['default'])
			{
				return $card;
			}
		}

		return false;
	}

	/**
	 * Get customer credit cards array
	 *
	 * @return array(array('number', 'exp_month', 'exp_year', 'nameoncard'))
	 */
	public function getCreditCards()
	{
		if (!$this->id)
			return array(); // return empty array if customer not yet saved

		if (is_array($this->creditCards))
			return $this->creditCards;

		$dbh = $this->dbh;
		$this->creditCards = array();

		$result = $dbh->Query("select id, ccard_number, ccard_exp_month, ccard_exp_year, ccard_type, f_default,
								customer_id, enc_ver, ccard_name from customer_ccards where customer_id='".$this->id."'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$cardData = array();
			$cardData['id'] = $row['id'];
			$cardData['nameoncard'] = $row['ccard_name'];
			$cardData['exp_year'] = $row['ccard_exp_year'];
			$cardData['exp_month'] = $row['ccard_exp_month'];
			$cardData['number'] = decrypt($row['ccard_number']);
			$cardData['type'] = $row['type'];
			$cardData['last4'] = substr($cardData['number'], -4, 4);
			$cardData['default'] = ($row['f_default'] == 't') ? true : false;
			$this->creditCards[] = $cardData;
		}
		$dbh->FreeResults($result);

		return $this->creditCards;
	}

	/**
	 * Called when the object has been removed
	 *
	 * @param bool $hard If set to true the data has been purged from the database
	 */
	public function removed($hard = false)
	{
		if ($hard)
			$this->dbh->Query("DELETE FROM customer_publish WHERE customer_id='".$this->id."'");
	}
	
	/**
	 * Override the default because files can have different icons based on file type
	 *
	 * @return string The base name of the icon for this object if it exists
	 */
	public function getIconName()
	{
		if ($this->getValue("image_id"))
		{
		}

		if ($this->getValue("type_id") == CUST_TYPE_ACCOUNT)
		{
			// Organization/Account
			return "customers/organization";
		}
		else
		{
			// Person/Individual
			return "customers/person";
		}
	}
	
	/**
	 * Get default email address
	 *
	 * @return string The default email address to use
	 */
	public function getDefaultEmail()
	{
		$emailDefault = $this->getValue("email_default");
		if(empty($emailDefault))
			$emailDefault = "email";
			
		$email = $this->getValue($emailDefault);

		if (empty($email))
			$email = $this->getValue("email2");

		if (empty($email))
			$email = $this->getValue("email");

		return $email;
	}

	/**
	 * Find a customer by email address, if not create it
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $email The email address to search for existing customers before creating
	 * @param AntUser $user The current user to look for private contacts
	 * @param bool $createIfNotFound If not found, then make a new customer
	 * @return CAntObject_Customer|bool If found, a customer is returned, otherwise false
	 */
	public static function findCustomerByEmail($dbh, $email, $user, $createIfNotFound=true)
	{
		$ret = false;

		$list = new CAntObjectList($dbh, "customer", $user);
		$list->addCondition("and", "email", "is_equal", $email);
		$list->addCondition("or", "email2", "is_equal", $email);
		/*
		$list->addCondition("and", "f_private", "is_not_equal", 't');
		$list->addCondition("or", "f_private", "is_equal", 't');
		$list->addCondition("and", "owner_id", "is_equal", $user->user);
		 */
		$list->getObjects();
		for ($i = 0; $i < $list->getNumObjects(); $i++)
		{
			$cust = $list->getObject($i);

			// Start with the first record
			if ($i == 0)
				$ret = $cust;
			else if ($cust->getValue("owner_id") == $user->id && $cust->getValue("f_private") == 't')
				$ret = $cust; // Prefer private if more than one is found after the first record
		}

		return $ret;
	}
}
