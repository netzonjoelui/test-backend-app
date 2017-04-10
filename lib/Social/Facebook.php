<?php
/**
 * This is the base class used for social network integration
 *
 * @category AntSocial
 * @package Facebook
 * @copyright Copyright (c) 2003-2023 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/Social/facebook-sdk/facebook.php");

/**
 * Social base class
 */
class AntSocial_Facebook extends AntSocial
{
	/**
	 * Facebook API
	 *
	 * @var Facebook
	 */
	public $facebook = null;

	/**
	 * Facebook user id
	 *
	 * @var string
	 */
	public $fbUserId = null;

	/**
	 * Setup function called by base constructor
	 */
	public function setup()
	{
		$this->facebook = new Facebook(array(
											'appId'  => AntConfig::getInstance()->social['fb_appId'],
											'secret' => AntConfig::getInstance()->social['fb_secret'],
											'cookie' => false,
											'session' => false,
											));

		// If access token already exists then set it
		$this->accessToken = AntConfig::getInstance()->social['fb_access_token'];
		$this->fbUserId = $this->user->getSetting("accounts/facebook/id");
		$this->facebook->setAccessToken($this->accessToken);

		/*
		$this->accessToken = $this->user->getSetting("accounts/facebook/access_token");
		if ($this->accessToken)
			$this->facebook->setAccessToken($this->accessToken);
		 */
	}

	/**
	 * Check to see if a user is authenticated
	 *
	 * @return bool true if they are, false if they are not
	 */
	public function isAuthenticated()
	{
		//$userId = $this->facebook->getUser();
		if ($this->fbUserId)
			return true;
		else
			return false;
	}

	/**
	 * Save access_token
	 *
	 * @param string $accessToken
	 */
	public function saveAccessToken($accessToken)
	{
		// Save the access token to
		$this->user->setSetting("accounts/facebook/access_token", $accessToken);
		$this->facebook->setAccessToken($accessToken);
	}

	/**
	 * Get a users profile
	 *
	 * @return AntSocial_Profile on success, false on failure
	 */
	public function getProfile()
	{
		try
		{
			if ($this->fbUserId)
			{
				$user_profile = $this->facebook->api('/'. $this->fbUserId, 'GET');
			}
			else
			{
				$user_id = $this->facebook->getUser();
				$user_profile = $this->facebook->api('/me', 'GET');
				$this->fbUserId = $user_profile['id'];
			}

			$prof = new AntSocial_Profile();
			$prof->id = $user_profile['id'];
			$prof->name = $user_profile['name'];
			$prof->username = isset($user_profile['username']) ? $user_profile['username'] : null;
			$prof->firstName = $user_profile['first_name'];
			$prof->lastName = $user_profile['last_name'];
			$prof->link = $user_profile['link'];
			$prof->email = $user_profile['email'];
			$prof->image = "http://graph.facebook.com/" . $user_profile['id'] . "/picture?type=large";
			return $prof;

			/*
				'hometown' => array ( 
					'id' => '113504651997119', 
					'name' => 'Shelter Cove, California', 
				), 
				'location' => array ( 
					'id' => '113839305296404', 
					'name' => 'Springfield, Oregon', 
				), 
				'work' => array ( 
					0 => array ( 
						'employer' => array ( 
							'id' => '111429562217420', 
							'name' => 'Aereus Corporation', 
						), 
						'position' => array ( 
							'id' => '147416865300121', 
							'name' => 'President', 
						), 
						'start_date' => '2003-02', 
						'end_date' => '0000-00', 
					), 
				), 
				'favorite_teams' => array ( 
					0 => array ( 
						'id' => '215050995267244', 
						'name' => 'Oregon Football', 
					), 
				), 
				'inspirational_people' => array ( 
					0 => array ( 
						'id' => '104332632936376', 
						'name' => 'Jesus', 
					), 
					1 => array ( 
						'id' => '190616700999052', 
						'name' => 'C. S. Lewis', 
					), 
					2 => array ( 
						'id' => '108006125887437', 
						'name' => 'William Lane Craig', 
					), 
					3 => array ( 
						'id' => '144919108852260', 
						'name' => 'Greg Boyd', 
					), 
					4 => array ( 
						'id' => '104074609628704', 
						'name' => 'Norman Geisler', 
					), 
					5 => array ( 
						'id' => '139047272818277', 
						'name' => 'Aerin Stebnicki', 
					), 
					6 => array ( 
						'id' => '108528605842001', 
						'name' => 'Mike Mercer', 
					), 
					7 => array ( 
						'id' => '170316523003683', 
						'name' => 'Stan Stebnicki', 
					), 
					8 => array ( 
						'id' => '109224272428578', 
						'name' => 'Ravi Zacharias', 
					), 
				), 
				'education' => array ( 
					0 => array ( 
						'school' => array ( 
							'id' => '112028042150132', 
							'name' => 'Canyonville Christian Academy',
						), 
						'type' => 'High School',
					), 
					1 => array ( 
						'school' => array ( 
							'id' => '26919578879', 
							'name' => 'Saint Leo University', 
						), 
						'year' => array ( 
							'id' => '138383069535219', 
							'name' => '2005', 
						), 
						'type' => 'College', 
					), 
				), 
				'gender' => 'male', 
				'timezone' => -7, 
				'locale' => 'en_US', 
				'verified' => true, 
				'updated_time' => '2013-02-10T15:21:02+0000', )
			 */
		}
		catch(FacebookApiException $e) 
		{
			// If the user is logged out, you can have a 
			// user ID even though the access token is invalid.
			// In this case, we'll get an exception, so we'll
			// just ask the user to login again here.
			//$login_url = $facebook->getLoginUrl(); 
			//echo 'Please <a href="' . $login_url . '">login.</a>';
			//echo $e->getMessage();
			//error_log($e->getType());
			//error_log($e->getMessage());
			return false;
    	}   
	}

	/**
	 * Get list of friends for the current user
	 *
	 * @return array of AntSocial_Friend objects or false if not authenticated
	 */
	public function getFriends()
	{
		try
		{
			if ($this->fbUserId)
			{
				$friends = $this->facebook->api('/'. $this->fbUserId . "/friends", 'GET');
			}
			else
			{
				$user_id = $this->facebook->getUser();
				$friends = $this->facebook->api('/me/friends', 'GET');
			}

			// TODO: we might need to paginate if $friends['paging']['next'] is set

			$ret = array();
			if (is_array($friends))
			{
				foreach ($friends['data'] as $friend)
				{
					$fobj = new AntSocial_Friend();
					$fobj->id = "";
					$fobj->name = "";
					$ret[] = $fobj;
				}
			}

			return $ret;
		}
		catch(FacebookApiException $e) 
		{
			// If the user is logged out, you can have a 
			// user ID even though the access token is invalid.
			// In this case, we'll get an exception, so we'll
			// just ask the user to login again here.
			//$login_url = $facebook->getLoginUrl(); 
			//echo 'Please <a href="' . $login_url . '">login.</a>';
			//error_log($e->getType());
			//error_log($e->getMessage());
			return false;
    	}   
	}

	/**
	 * Get login url
	 *
	 * @param string $returnUrl The URL that will be called once authentication is done
	 * @return string
	 */
	public function getLoginUrl($returnUrl)
	{
		$scopes = array(
			"email", // Access to user's email address
			"offline_access", // Enable access token
			"read_stream", // Read the users stream
			"publish_stream", // Write to the users stream
			"user_location", // Access the user location
			"user_about_me", // Get about me section for user
			"user_education_history", // Get education history for user
			"user_work_history", // Get work history for user
			"friends_work_history", // Get work history for friends
			"user_website", // Get user's website
			"email", // Access to user's email address
		);

		$scope = "";
		foreach ($scopes as $s)
		{
			if ($scope) $scope .= ",";
			$scope .= $s;
		}

		$params = array(
		  'scope' => $scope,
		  'redirect_uri' => $returnUrl
		);

		$loginUrl = $this->facebook->getLoginUrl($params);
		
		return $loginUrl;
	}

	/**
	 * Get profile picture
	 */
	public function getProfilePictureUrl()
	{
		if (!$this->fbUserId)
			return false;

		return "http://graph.facebook.com/" . $this->fbUserId . "/picture?type=large";
		/*
		//echo "Updating contact ".$obj->getName()."\n";
		$picbinary = file_get_contents("http://graph.facebook.com/".$friend['id']."/picture?type=large");
		if (sizeof($picbinary)>0)
		{
			$antfs = new CAntFs($dbh, $user);
			$fldr = $antfs->openFolder("%userdir%/Contact Files/".$obj->id, true);
			$file = $fldr->createFile("profilepic.jpg");
			$size = $file->write($picbinary);
			if ($file->id)
			{
				$obj->setValue('image_id', $file->id);
			}
			$obj->save();
		}
		$picbinary = null;
		*/
	}

	/**
	 * Get a user picture url
	 */
	public function getUserPictureUrl($username)
	{
		if (!$this->fbUserId)
			return false;

		return "http://graph.facebook.com/" . $this->fbUserId . "/picture?type=large";
	}
}
