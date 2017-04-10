<?php
require_once('lib/Social.php');

/**
* Actions for interacting with Admin Controller
*/
class SocialController extends Controller
{
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;        
    }

    /**
    * Facebook login
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function fb($params)
    {        
		$fb = new AntSocial_Facebook($this->user);

		if ($params['code'])
		{
			//die("Auth Code: " . $params['code']);

			// Store access token
			//$fb->saveAccessToken($params['access_token']);

			$prof = $fb->getProfile();

			if ($prof)
			{
				// Save the profile id for this user
				if ($prof->id)
				{
					$this->user->setSetting("accounts/facebook/id", $prof->id);
					$this->setUserProfileFromSoc($prof);
				}
				echo "You are currently logged in as: <strong>" . $prof->name . "</strong>";
				echo "<script type='text/javascript'>window.close();</script>";
			}
			else
			{
				echo "ERROR: We were unable to authenticate your account";
			}
		}
		else if ($params['error'])
		{
			echo "ERROR: " . str_replace("_", " ", $params['error_reason']);
		}
		else
		{
			header("Location: " . $fb->getLoginUrl($this->ant->getAccBaseUrl(true) . "/controller/Social/fb"));
			//echo $fb->getLoginUrl($this->ant->getAccBaseUrl(true) . "/controller/Social/fb");
		}
    }

	/**
	 * Twitter login
	 *
	 * @param array $params
	 */
	public function twitter($params)
	{ 
		/*
		if (!isset($params['oauth_verifier'])) 
		{
			// gets a request token
			$reply = $cb->oauth_requestToken(array(
				'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			));

			// stores it
			$cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
			$_SESSION['oauth_token'] = $reply->oauth_token;
			$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;

			// gets the authorize screen URL
			$auth_url = $cb->oauth_authorize();
			header('Location: ' . $auth_url);
			die();

		} 
		else if (!isset($_SESSION['oauth_verified'])) 
		{
			// gets the access token
			$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$reply = $cb->oauth_accessToken(array(
				'oauth_verifier' => $params['oauth_verifier']
			));
			// store the authenticated token, which may be different from the request token (!)
			$_SESSION['oauth_token'] = $reply->oauth_token;
			$_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
			$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$_SESSION['oauth_verified'] = true;
		}


		// TODO: use this for application level token
		$reply = $cb->oauth2_token();
		$bearer_token = $reply->access_token;
		\Codebird\Codebird::setBearerToken('YOURBEARERTOKEN');
		 */
	}

	/**
	 * Set profile pic from social only if profile pic is not already set
	 */
	private function setUserProfileFromSoc($socProfile)
	{
		// Only set image if image id is not already set
		if ($this->user->getValue("image_id"))
			return;

		$url = $socProfile->image;

		if (!$url)
			return;
		
		$picbinary = @file_get_contents($url);
		if (sizeof($picbinary)>0)
		{
			$antfs = new AntFs($this->ant->dbh, $this->user);
			$fldr = $antfs->openFolder("%userdir%/System", true);
			$file = $fldr->openFile("profilepic.jpg", true);
			$size = $file->write($picbinary);

			if ($file->id)
			{
				$this->user->setValue('image_id', $file->id);
				$this->user->save();
			}
		}
		$picbinary = null;
	}
}
