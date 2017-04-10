<?php
require_once(dirname(__FILE__).'/../lib/Email.php');
require_once(dirname(__FILE__).'/../lib/aereus.lib.php/CCache.php');
//require_once(dirname(__FILE__).'/../lib/aereus.lib.php/CSessions.php');
//require_once(dirname(__FILE__).'/../lib/aereus.lib.php/facebook/facebook.php');
require_once(dirname(__FILE__).'/../contacts/contact_functions.awp');
require_once(dirname(__FILE__).'/../email/email_functions.awp');
require_once(dirname(__FILE__).'/../lib/AntSystem.php');
require_once(dirname(__FILE__).'/../lib/AntFs.php');
require_once(dirname(__FILE__).'/../lib/Social.php');
require_once(dirname(__FILE__).'/../lib/AntChat.php');
require_once('lib/ServiceLocatorLoader.php');

/**
* Class for controlling User functions
*/
class UserController extends Controller
{    
	/**
	 * Get current user id and name
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
	 */
	public function getCurrentUser($params)
	{
		$this->sendOutputJson(array("name"=>$this->user->name, "id"=>$this->user->id));
	}

    /**
    * get the authentication string
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */     
    public function getAuthString()
    {
        $dbh = $this->ant->dbh;

        if ($this->user->id)
        {
			$ret = $this->user->getAuthString();
        }
        else
            $ret = array("error"=>"userid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Group Add
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function groupAdd($params)
    {
        $dbh = $this->ant->dbh;
        $gname = $params['name'];

        if ($gname)        // Update specific event
        {
            $result = $dbh->Query("insert into user_groups(name) values('".$dbh->Escape($gname)."');
                                    select currval('user_groups_id_seq') as id;");
            if ($dbh->GetNumberRows($result))
                $ret = $dbh->GetValue($result, 0, "id");
        }
        else
            $ret = array("error"=>"name is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Group Delete
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function groupDelete($params)
    {
        $dbh = $this->ant->dbh;
        $gid = $params['gid'];

        if ($gid)        // Update specific event
        {
            $dbh->Query("delete from user_groups where id='$gid'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"gid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Save User
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveUser($params)
    {
        $dbh = $this->ant->dbh;
        $changePassword = false;
        $uid = null;
        
        if(isset($params['uid']))
            $uid = $params['uid'];
            
        if ($params['userName'])
        {
            // check password if correct
            if (!empty($params['newPassword']) && !empty($params['verifyPassword']))
            {
                if($params['newPassword'] == $params['verifyPassword'])
                    $changePassword = true;
                else
                    $ret = array("error"=>"New Password and Verify Password didnt match. Settings was not saved.", "errorId" => 2);
            }
            
            if(empty($ret))
            {
                $userObject = new CAntObject($dbh, "user", $uid, $this->user);
                
                if(isset($params['userName']))
                    $userObject->setValue("name", strtolower($params['userName']));
                
                if(isset($params['fullName']))
                    $userObject->setValue("full_name", strtolower($params['fullName']));
                    
                if(isset($params['phone']))
                    $userObject->setValue("phone", $params['phone']);
                    
                if(isset($params['title']))
                    $userObject->setValue("title", $params['title']);
                    
                if(isset($params['active']))
                    $userObject->setValue("active", $params['active']);
                    
                if(isset($params['imageId']))
                    $userObject->setValue("image_id", $params['imageId']);
                    
                if(isset($params['imageId']))
                    $userObject->setValue("image_id", $params['imageId']);
                    
                if(isset($params['timezone']))
                    $userObject->setValue("timezone", $params['timezone']);
                    
                if(isset($params['teamId']))
                    $userObject->setValue("team_id", $params['teamId']);
                    
                if(isset($params['managerId']))
                    $userObject->setValue("manager_id", $params['managerId']);

                if(isset($params['email']))
                    $userObject->setValue("email", $params['email']);

                if(isset($params['mobilePhone']))
                    $userObject->setValue("phone_mobile", $params['mobilePhone']);

                if(isset($params['phoneCarrier']))
                    $userObject->setValue("mobile_phone_carrier", $params['phoneCarrier']);

                if(isset($params['officePhone']))
                    $userObject->setValue("phone_office", $params['officePhone']);

                if(isset($params['officeExt']))
                    $userObject->setValue("phone_ext", $params['officeExt']);
                
                if($changePassword)
                    $userObject->setValue("password", $params['newPassword']);
                else
                {
                    if(isset($params['password']))
                        $userObject->setValue("password", $params['password']);
                }
                    
                $userId = $userObject->save();
                
                if(!empty($userId))
                {
                    // Declare new Ant User for  user
                    $antEditedUser = $this->ant->getUser($userId);
                    
                    if(isset($params['mobilePhone']))
                        $antEditedUser->setSetting("mobile_phone", str_replace(array('-', '(', ')', ' '), '', $params['mobilePhone']));
                        
                    if(isset($params['phoneCarrier']))
                        $antEditedUser->setSetting("mobile_phone_carrier", $params['phoneCarrier']);
                        
                    if(isset($params['officePhone']))
                        $antEditedUser->setSetting("office_phone", str_replace(array('-', '(', ')', ' '), '', $params['officePhone']));
                        
                    if(isset($params['officeExt']))
                        $antEditedUser->setSetting("office_ext", str_replace(array('-', '(', ')', ' '), '', $params['officeExt']));
                    
                    if(isset($params['wizard'])) // force wizard
                        $antEditedUser->setSetting("general/f_forcewizard", str_replace(array('-', '(', ')', ' '), '', $params['wizard']));
                        
                    if(isset($params['email']))
                    $userObject->setValue("email", $params['email']);
                    
                    // Purge existing user groups
                    $antEditedUser->purgeGroupMembership();
                    
                    // save groups
                    foreach ($params as $key=>$value)
                    {
                        $arrKey = explode("_", $key);
                        if($arrKey[0]=='group' && $value=='t')
                            $antEditedUser->addToGroup($arrKey[1]);
                    }
                    
                    $antEditedUser->clearCache();
                    $userObject->index();
                    
                    $ret = $userId;
                }
                else
                    $ret = array("error"=>"Error occurred while saving user.");
            }
        }
        else
            $ret = array("error"=>"userName is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Save User Wizard
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveUserWiz($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        if ($params['uid'])
        {
            $updateFields = array();
            $fullName = null;            
            
            if(isset($params['full_name']))
                $fullName = $params['full_name'];
                
            if(isset($params['phone']))
                $updateFields[] = "phone='".$dbh->Escape(stripslashes($params['phone']))."'";
                
            if(isset($params['image_id']))
                $updateFields[] = "image_id=".$dbh->EscapeNumber($params['image_id'])."";
                
            if(isset($params['timezone']))
                $updateFields[] = "timezone='".$dbh->Escape($params['timezone'])."'";
            
            if (isset($params['password']) && $params['password'] && $params['password']!="000000")
                $updateFields[] = "password='".$dbh->Escape($params['password'])."'";
            
            
            $updateFields[] = "full_name='".$dbh->Escape(stripslashes($fullName))."'";
            
            if(sizeof($updateFields) > 0)
            {
                $query = "update users set ";    
                $query .= implode(", ", $updateFields);
                $query .= " where id='".$params['uid']."';";
                $result = $dbh->Query($query);
                
                if(isset($params['mobile_phone']))
                    $this->user->setSetting("mobile_phone", str_replace(array('-', '(', ')', ' '), '', $params['mobile_phone']));
                    
                $ret = $params['uid'];
            }
            else
                $ret = array("error"=>"No fields to be updated.");
        }
        else
            $ret = array("error"=>"uid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * User Delete
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function userDelete($params)
    {
        $dbh = $this->ant->dbh;

        $uid = $params['uid'];

        if ($uid)        // Update specific event
        {
            $dbh->Query("update users set active='f' where id='".$uid."'");
            $ret = 1;
        }            
        else
            $ret = array("error"=>"uid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Save Profile
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveProfile($params)
    {
        $dbh = $this->ant->dbh;

        if ($this->user->id)
        {
            // check password if correct
            if ($params['currentPassword'] && $params['newPassword'] && $params['verifyPassword'])
            {
                // Get new netric authentication service
                $sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
                $authService = $sl->get("AuthenticationService");

                // TODO: change this to the authservice
                $currentPassword = $params['currentPassword'];

                $ret = $authService->authenticate($this->user->name, $params['currentPassword']);
                if($ret)
                {
                    if($params['newPassword'] !== $params['verifyPassword'])
                        $ret = array("error"=>"New Password and Verify Password didnt match. New password was not applied.", "errorId" => 2);
                }
                else
                    $ret = array("error"=>"Invalid current password. New password was not applied.", "errorId" => 3);
            }

            if(empty($ret))
            {
				$this->user->setValue("full_name", $params['fullName']);
				$this->user->setValue("timezone", $params['timezone']);
				$this->user->setValue("phone_mobile", $params['phone_mobile']);
				$this->user->setValue("image_id", $params['imageId']);
                $this->user->setValue("theme", $params['theme']);
				$this->user->setValue("email", $params['email']);
				$this->user->setValue("phone_mobile", $params['mobilePhone']);
                $this->user->setValue("phone_mobile_carrier", $params['phoneCarrier']);
				$this->user->setValue("phone_office", $params['officePhone']);
				$this->user->setValue("phone_ext", $params['officeExt']);
                
                if(!empty($params['newPassword']))
				    $this->user->setValue("password", $params['newPassword']);
                
				$this->user->save();

				/* now fields in the user object
				$this->user->setSetting("mobile_phone", str_replace(array('-', '(', ')', ' '), '', $params['mobilePhone']));
				$this->user->setSetting("mobile_phone_carrier", $params['phoneCarrier']);
				$this->user->setSetting("office_phone", str_replace(array('-', '(', ')', ' '), '', $params['officePhone']));
				$this->user->setSetting("office_ext", str_replace(array('-', '(', ')', ' '), '', $params['officeExt']));
				 */

                $cache = CCache::getInstance();
                $cache->remove($dbh->dbname."/users/".$this->user->id."/theme");
                
                $ret = 1;
            }            
        }
        else
            $ret = array("error"=>"uid is a required param", "errorId" => 1);

        $this->sendOutputJson($ret);
        return $ret;
    }

	/**
    * Set a password
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function setPassword($params)
    {
		if (!$params['uid'])
            $ret = array("error"=>"uid is a required param", "errorId" => 1);

		// check password if correct
		if ($params['new_password'] && $params['new_password'] == $params['verify_password'])
		{
			$user = new AntUser($this->ant->dbh, $params['uid'], $this->ant);
			if ($user->userObj->dacl->checkAccess($this->user, "Edit")) // OR check current_password for updating user profile
			{
				$user->setPassword($params['new_password']);
				$ret = $user->save();
			}

			if (!$ret) // Insufficient permissions
            	$ret = array("error"=>"You do not have permissions to set the password for this user", "errorId" => 2);
		}
		else
		{
            $ret = array("error"=>"Password and password verify do not match", "errorId" => 3);
		}
			
		$this->sendOutput($ret);
	}

    /**
    * Save Flags
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveFlags($params)
    {
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Save Groups
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function saveGroups($params)
    {
        $dbh = $this->ant->dbh;

        $UID = $params['uid'];
        if ($UID)
            $dbh->Query("delete from user_group_mem where user_id='$UID';");

        if (count($params['groups']) && $UID)
        {
            $ret = array();
            foreach ($params['groups'] as $gid)
            {
                if (is_numeric($gid))
                {
                    $dbh->Query("insert into user_group_mem(user_id, group_id) values('$UID', '$gid');");
                    $ret[] = $gid;
                }
            }
        }
        else
            $ret = array("error"=>"uid is a required param");

        // Purge cache
        $cache = CCache::getInstance();
        $cache->remove($dbh->dbname."/users/$UID/groups");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Team Delete
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function teamDelete($params)
    {
        $dbh = $this->ant->dbh;

        if ($params['tid'])
        {
            // TODO: Security - including delete
            $dbh->Query("delete from user_teams where id='".$params['tid']."'");
            $ret = 1;
        }
        else
            $ret = array("error"=>"tid is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * User Get Image
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function userGetImage($params)
    {
        $dbh = $this->ant->dbh;

        if ($params['name'] && !$params['uid'])
            $userid = AntUser::getIdFromName($params['name'], $dbh);
        if ($params['uid'])
            $userid = $params['uid'];
		else
			$userid = $this->user->id;

        if (is_numeric($userid))
        {
            $img = UserGetImage($dbh, $userid);
            $ret = ($img) ? $img : array("error"=>"error occured while getting the user image");
        }
        else
            $ret = array("error"=>"user name or id is a required param");

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
	 * Load a user image by redirecting to the appropriate url
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function loadUserImage($params)
    {
        $dbh = $this->ant->dbh;

		if ($params['uid'])
            $userid = $params['uid'];
		else if ($params['uname'])
            $userid = AntUser::getIdFromName($params['uname'], $dbh);
		else
			$userid = $this->user->id;

		$path = "/images/user_default.png";

        if (is_numeric($userid))
        {
			if ($userid < 1) // Use system icon
			{
				$path = "/images/user_system.png";
			}
			else
			{
				$user = $this->ant->getUser($userid);
				$imgid = $user->getValue("image_id");
				if ($imgid)
				{
					$path = "/antfs/images/$imgid";
					if (isset($params['w']))
						$path .= "/".$params['w'];
					if (isset($params['h']))
						$path .= "/".$params['h'];
				}
			}
        }

		header("Location: $path");
        return true;
    }

    /**
	 * @depricated This is only called from antapi/AuthenticateUser to check for a valid user but code is being phased
	 * out completely so this can eventually be removed. - joe
	 *
     * Login
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function login($params)
    {
		$antsys = new AntSystem();

        $dbh = $this->ant->dbh; 
        
        if ($params['name'] && $params['password'])
        {
			$uid = AntUser::authenticate($params['name'], $params['password'], $dbh);
            if ($uid)
            {
                $ret = $uid;
            }
            else
            {
                $ret = array("error"=>"Invalid usernamd and/or password");
            }
        }
        else
            $ret = array("error"=>"name and password are required params");

        return $this->sendOutputJson($ret);
    }

    /**
     * Get facebook user id if set
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function socFbGetUserId()
    {
        $ret = $this->user->getSetting("accounts/facebook/id");
		if (!$ret)
			$ret = -1;
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Socket FB disconnect
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function socFbDisconnect()
    {
        $this->user->setSetting("accounts/facebook/id", '');

        $ret = 1;    
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Socket FB Get Profile Picture
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function socFbGetProfilePic($params)
    {
        $dbh = $this->ant->dbh;

        $fbUserid = $ret = $this->user->getSetting("accounts/facebook/id");
        if ($fbUserid)
        {
			$picbinary = file_get_contents("http://graph.facebook.com/".$fbUserid."/picture?type=large");
			if (sizeof($picbinary)>0)
			{
				$antfs = new AntFs($dbh, $this->user);
				$fldr = $antfs->openFolder("%userdir%/System", true);
				$file = $fldr->openFile("fb-profilepic.jpg", true);
				$size = $file->write($picbinary);
				if ($file->id)
					$ret = $file->id;
			}
        }

        if (!$ret)
            $ret = "-1";

        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
    * Sets the User Setting
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function setSettingUser($params)
    {
        if ($params['set'])
            $ret = $this->user->setSetting(rawurldecode($params['set']), rawurldecode($params['val']));
        else
            $ret = array("error"=>"set is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
	}

	/**
    * Gets the User Setting
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getSettingUser($params)
    {        
        if ($params['get'])
            $ret = $this->user->getSetting(rawurldecode($params['set']));
        else
            $ret = array("error"=>"get is a required param");
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the User Id
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getUserId()
    {
        $ret = $this->user->id;
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the User Groups
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getGroups($params)
    {
        $dbh = $this->ant->dbh;
        
        $query = "select id, name from user_groups";
        if(isset($params['gid']) && $params['gid'])
            $query .= " and id='".$params['gid']."' ";
            
        if(isset($params['uid']) && is_numeric($params['uid']))
            $query .= " and id in (select group_id from user_group_mem where user_id='".$params['uid']."') ";
            
        if(isset($params['search']) && $params['search'])
        {
            $search = rawurldecode($params['search']);
            $sparts = explode(" ", $search);

            $cond = "";
            foreach ($sparts as $part)
            {
                if ($cond) $cond .= " and ";
                $cond .= " (lower(name) like lower('%".$dbh->Escape($part)."%'))";
            }

            $query .= " and ($cond) ";
        }
        $query .= " order by name ";
        
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        $ret = array();
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);

            // Get name
            $id = $row['id'];
            $ret[$id] = array("id" => $id, "name" => $row['name']);
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the User Groups
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getUserGroups($params)
    {
        $dbh = $this->ant->dbh;
        
        $gid = $params['gid'];
        $ret = array();
        
        if(!empty($gid))
        {
            $query = "select * from user_group_mem 
                    inner join users on users.id = user_group_mem.user_id where group_id = '$gid'";
            
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                
                $id = $row['id'];
                $ret[$id] = array("id" => $id, "name" => $row['full_name'], "title" => $row['title']);
            }
        }
        else
            $ret = array("error"=>"gid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Deletes the user group
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteUserGroup($params)
    {
        $dbh = $this->ant->dbh;
        $gid = $params['gid'];
        $userId = $params['userId'];
        
        if(!empty($gid) && $userId)
        {
            $query = "delete from user_group_mem where user_id = '$userId' and group_id = '$gid'";
            $dbh->Query($query);
            $ret = 1;
        }
        else
            $ret = array("error"=>"gid and userId are required params");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the user
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getUsers($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        $start = 0;
        
        if(isset($params['start']))
            $start = $params['start'];
            
        if(isset($params['showper']) && $params['showper'])
            $showper = $params['showper'];
        else
            $showper = 200;
        
        $query = "select id, name, full_name, active, image_id, theme, timezone,  
                team_id, manager_id, title, phone, email from users where id is not null ";
                
        if(isset($params['uid']))
        {
            $ret['userId'] =$params['uid'];
            if(empty($params['uid']))
                $ret['userId'] = 0;
                
            $query .= " and id='{$ret['userId']}' ";
        }            
        else
        {
            if(isset($params['profile']) && $params['profile'])
            {
                $ret['userId'] = $this->user->id;
                $query .= " and id='".$this->user->id."' ";
            }   
            else
                $query .= " and name!='administrator' ";
        }
            
        $view_active = null;
        
        if(isset($params['view_active']))
            $view_active = $params['view_active'];
            
        if(!$view_active)
            $query .= " and active='t' ";
            
        if(isset($params['gid']) && $params['gid'])
            $query .= " and id in (select user_id from user_group_mem where group_id='".$params['gid']."') ";
            
        if(isset($params['search']) && $params['search'])
        {
            $search = rawurldecode($params['search']);
            $sparts = explode(" ", $search);

            $cond = "";
            foreach ($sparts as $part)
            {
                if ($cond) $cond .= " and ";
                $cond .= " (lower(name) like lower('%".$dbh->Escape($part)."%')
                            or lower(full_name) like lower('%".$dbh->Escape($part)."%'))";
            }

            $query .= " and ($cond) ";
        }
        
        $query .= " order by active, name ";
        
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);

        if($num > $showper)
        {
            // Get total number of pages
            $leftover = $num % $showper;
            
            if ($leftover)
                $numpages = (($num - $leftover) / $showper) + 1; //($numpages - $leftover) + 1;
            else
                $numpages = $num / $showper;
            // Get current page
            if ($start > 0)
            {
                $curr = $start / $showper;
                $leftover = $start % $showper;
                if ($leftover)
                    $curr = ($curr - $leftover) + 1;
                else 
                    $curr += 1;
            }
            else
                $curr = 1;
            // Get previous page
            if ($curr > 1)
                $prev = $start - $showper;
            // Get next page
            if (($start + $showper) < $num)
                $next = $start + $showper;
            $pag_str = "Page $curr of $numpages";

            $ret["paginate"] = array("prev" => $prev, "next" => $next, "pag_str" => $pag_str);            
        }
        
        if($num==0)
        {
            $userId = $ret['userId'];
            $ret["user{$userId}"]['details'][] = array();        
            $ret["user{$userId}"]['password'][] = array();            
            $ret["user{$userId}"]['wizard'][] = array();
            $ret["user{$userId}"]['phone'][] = array();
            $ret["user{$userId}"]['email'][] = array();
            $ret["user{$userId}"]['userEmail'][] = array();
            $ret["user{$userId}"]['groups'][] = array();
        }
        
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);

            if ((is_numeric($start) || $start == 0 ) && $showper < $num)
            {
                if ($i < $start)
                    continue;
                
                if ($i+1 > $start + $showper)
                    break;
            }
            
            $managerId = $row['manager_id'];
            $userId = $row['id'];
            $teamName = null;
            $managerName = null;
            
            $antUser = $this->ant->getUser($userId);
            
            if(!empty($managerId))
            {
                $args['userId'] = $managerId;
                $args['fromFunction'] = true;
                $userDetails = $this->getUserDetails($args);                
                $managerName = $userDetails['full_name'];
            }
            
            if(isset($row['team_name']))
                $teamName = $row['team_name'];
            
            $ret["user$userId"]['details'] = array("id" => $userId, "imageId" => $row['image_id'], "theme" => $row['theme'], "timezone" => $row['timezone'],
                                    "teamId" => $row['team_id'], "teamName" => $teamName, "managerId" => $managerId, 
                                    "managerName" => $managerName, "title" => $row['title'], "phone" => $row['phone'], "name" => $row['name'],
                                    "fullName" => $row['full_name'], "active" => $row['active'], "email" => $row['email']);
            
            $res2 = $dbh->Query("select id, name from user_groups where id in (select group_id from user_group_mem where user_id='$userId')");
            $num2 = $dbh->GetNumberRows($res2);
            
            for ($j = 0; $j < $num2; $j++)
            {
                $row2 = $dbh->GetNextRow($res2, $j);                
                $ret["user$userId"]['group'][]= array("id" => $row2['id'], "name" => $row2['name']);
            }
            $dbh->FreeResults($res2);
                        
            $ret["user$userId"]['email'] = array();
            if(isset($params['det']) && $params['det'] == "full")
            {                
                $ret["user$userId"]['password'][]= array("password" => "000000");

                // Get default email account
                $res2 = $dbh->Query("select id, name, address, reply_to from email_accounts where user_id='".$row['id']."' and f_default='t'");
                if ($dbh->GetNumberRows($res2))
                {
                    $row2 = $dbh->GetNextRow($res2, 0);
                    
                    $ret["user$userId"]['email']= array("displayName" => $row2['name'], "emailAddress" => $row2['address'], "replyTo" => $row2['reply_to']);
                }
                else
                    $ret["user$userId"]['email'] = array();
                    
                $dbh->FreeResults($res2);
                
                $fw = $antUser->getSetting("general/f_forcewizard");
                $ret["user$userId"]['wizard']= array("fForcewizard" => ($fw)?$fw:'f');
            }
            
            $officePhone = $antUser->getSetting("office_phone");
            $officeExt = $antUser->getSetting("office_ext");
            $ret["user$userId"]['phone'] = array("mobilePhone" => $antUser->getSetting("mobile_phone"), "phoneCarrier" => $antUser->getSetting("mobile_phone_carrier"),
                                                    "officePhone" => $officePhone, "officeExt" => $officeExt);            
            //$ret["user$userId"]['userEmail'][]= UserGetEmail($dbh, $row['id']);
        }
        
        // get user groups
        if(isset($params['uid']) && $params['uid'])
        {
            $query = "select * from user_group_mem where user_id = '{$params['uid']}'";
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            
            $ret["user$userId"]['groups'][] = array();
            
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                $groupId = $row['group_id'];
                $ret["user$userId"]['groups'][$groupId] = $groupId;
            }
        }
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets ant themes
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getThemes()
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
		$themes = $this->ant->getThemes();
		foreach ($themes as $theme)
		{
            $ret[] = array("name" => $theme['name'], "title" => $theme['title']);
		}


		/*
        $result = $dbh->Query("select id, title from themes order by f_default DESC, title");
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);            
            $ret[] = array("id" => $row['id'], "name" => $row['title']);
        }
        $dbh->FreeResults($result);
		 */
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets timezones
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getTimezones()
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
		$ret = DateTimeZone::listIdentifiers();
		/*
        $result = $dbh->Query("select id, name, code from user_timezones order by offs");
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $ret[] = array("id" => $row['id'], "code" => $row['code'], "name" => $row['name']);
        }
        $dbh->FreeResults($result);
		 */
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets carrier
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getCarriers()
    {
        require_once(dirname(__FILE__).'/../lib/sms.php');
        
        $ret = array();
        
        foreach ($SMS_CARRIERS as $carrier)
            $ret[] = array("id" => $carrier[1], "name" => $carrier[0]);
            
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets teams
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getTeams($params)
    {
        $dbh = $this->ant->dbh;
        global $TEAM_ACLS;

        $result = $dbh->Query("select id, parent_id, name from user_teams order by parent_id, name asc");
        $num = $dbh->GetNumberRows($result);
        
        $rootTeam = $this->ant->settingsGet("general/company_name");        
        $ret[0] = array("id" => 1, "name" => $rootTeam, "parentId" => null);
        $ret['teamCount'] = 1;
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            
            if($row['parent_id'] > 0)
            {
                $DACL_TEAM = new Dacl($dbh, "teams/".$row['id']);
				if (!$DACL_TEAM->id)
				{
					$DACL_TEAM->save();
				}
                
                $teamInfo = array("id" => $row['id'], "name" => $row['name'], "parentId" => $row['parent_id'], "permissionLink" => $DACL_TEAM->getEditLink()); 
                
                if(isset($params['get_obj_frm']) && $params['get_obj_frm'])
                {
                    $otid = objGetAttribFromName($dbh, $params['get_obj_frm'], "id");
                    if ($otid)
                    {
                        $res2 = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts where team_id='".$row['id']."' and type_id='$otid'");
                        if ($dbh->GetNumberRows($res2))                        
                            $teamInfo = array_merge($teamInfo, array("formLayoutText" => $dbh->GetValue($res2, 0, "form_layout_xml")));
                    }
                }

                $ret[$row['id']] = $teamInfo;
                $ret['teamCount'] += 1;
            }            
            else
            {
                if(empty($rootTeam))
                    $rootTeam = $row['name'];
                $ret[0] = array("id" => $row['id'], "name" => $rootTeam, "parentId" => null);
            }                
        }
        $dbh->FreeResults($result);
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Delete Team
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteTeam($params)
    {
        $dbh = $this->ant->dbh;
        $tid = $params['tid'];        
        
        if($tid)
        {
            // delete using parent id
            $query = "delete from user_teams where parent_id = '$tid'";
            $dbh->Query($query);
            
            // delete using team id
            $query = "delete from user_teams where id = '$tid'";
            $dbh->Query($query);
            $ret = 1;
        }
        else
            $ret = array("error"=>"tid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Gets the User Teams
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getUserTeams($params)
    {
        $dbh = $this->ant->dbh;
        
        $tid = $params['tid'];
        $ret = array();
        
        if(!empty($tid))
        {
            $query = "select * from users where team_id = '$tid'";
            
            $result = $dbh->Query($query);
            $num = $dbh->GetNumberRows($result);
            for ($i = 0; $i < $num; $i++)
            {
                $row = $dbh->GetNextRow($result, $i);
                
                $ret[] = array("id" => $row['id'], "name" => $row['full_name'], "title" => $row['title']);
            }
        }
        else
            $ret = array("error"=>"tid is a required param");
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Remove the user to the team
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteUserTeam($params)
    {
        $dbh = $this->ant->dbh;
        
        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Save team
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function teamAdd($params)
    {
        $dbh = $this->ant->dbh;
        $teamName = $params['name'];
        $parentId = $params['parent_id'];
        $tid = null;
        
        if(isset($params['tid']))
            $tid = $params['tid'];

        if ($teamName )        // Update specific event
        {
            if($tid > 0)
            {
                if($parentId > 0)
                    $parentSet = ", parent_id = '" . $dbh->Escape($parentId) . "'";
                    
                $dbh->Query("update user_teams set name = '".$dbh->Escape($teamName)."' $parentSet where id = '" . $dbh->Escape($tid) . "';");
                $ret = $tid;
            }
            else
            {                
                $result = $dbh->Query("insert into user_teams(name, parent_id) values('".$dbh->Escape($teamName)."', '" . $dbh->Escape($parentId) . "');
                                    select currval('user_teams_id_seq') as id;");
                if ($dbh->GetNumberRows($result))
                    $ret = $dbh->GetValue($result, 0, "id");
            }
        }
        else
            $ret = array("error"=>"teamName  is a required param.");

        $this->sendOutputJson($ret);
        return $ret;
    }
    
    /**
    * Get User Details
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function getUserDetails($params)
    {
        $dbh = $this->ant->dbh;
        if(isset($params['userId']))        
            $userId = $params['userId'];
        else
            $userId = $this->user->id;
        
        $ret = array();
        $query = "select * from users where id = '$userId'";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        if($num > 0)
            $ret = $dbh->GetNextRow($result, 0);
        
        if(!isset($params['fromFunction']))
            $this->sendOutputJson($ret);
            
        return $ret;
    }
    
    /**
    * Checks the user if active
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function userCheckin($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['registerActive']==1)
		{
            $dbh->Query("update users set active_timestamp='now', checkin_timestamp='now' where id='" . $this->user->id . "'");
		}
		else
		{
        	$dbh->Query("update users set checkin_timestamp='now' where id='" . $this->user->id . "'");
		}
        
        // TO DO
        // Add chat status to check for inactive users
        
        $ret = 1;
        $this->sendOutputJson($ret);
        return $ret;
    }

    /**
     * Track status update activity
     *
     * @param array $params An assocaitive array of parameters passed to this function.
     */
    public function logStatusUpdate($params)
    {
        $dbh = $this->ant->dbh;
        
        if ($params['status'])
		{
			$objstat = CAntObject::factory($dbh, "status_update", null, $this->user);
			$objstat->setValue("comment", $params['status']);

			// Add associations
			$objstat->addAssociation("user", $this->user->id, "associations");
            
            if ($params["notify"])
                $objstat->setValue("notify", $params["notify"]);

			// Associate with all team members
			if ($this->user->teamId)
			{
				$userList = new CAntObjectList($dbh, "user", $this->user);
				$userList->addCondition("and", "team_id", "is_equal", $this->user->teamId);
				$userList->addCondition("and", "id", "is_not_equal", $this->user->id); // Exclude current user of course
				$userList->getObjects();
				for ($i = 0; $i < $userList->getNumObjects(); $i++)
				{
					$odat = $userList->getObjectMin($i);
					$objstat->addAssociation("user", $odat['id'], "associations");
				}
			}

			// Associate with manager
			if ($this->user->getValue("manager_id"))
				$objstat->addAssociation("user", $this->user->getValue("manager_id"), "associations");

			$aid = $objstat->save();
		}
        
        return $this->sendOutputJson(1);
    }

    /**
     * Check if a user name is valid
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function checkUserName($params)
    {
        $dbh = $this->ant->dbh;

		if (!$params['name'])
			return $this->sendOutput("Name is a required field");

		// Check for uniqueness of the user name
		$userList = new CAntObjectList($dbh, "user", $this->user);
		$userList->addCondition("and", "name", "is_equal", $params['name']);
		if ($params['uid'])
			$userList->addCondition("and", "id", "is_not_equal", $params['uid']); // Exclude current user of course
		$userList->getObjects();
		if ($userList->getNumObjects()>0)
			return $this->sendOutput("User name {$params['name']} is already in use");

		// Now check for alpha numeric chars only
		return $this->sendOutput(1);
	}

	/**
	 * Get session data
	 *
	 * @param array $params An assocaitive array of parameters passed to this function
	 * @return string json stream
     */
	public function getSession($params)
	{
		$dbh = $USERNAME = $USERID = $ACCOUNT = $theme_name = null;
		
		if(isset($ANT)) {
			$dbh = $ANT->dbh;
		}
		
		if(isset($USER)) {
			$USERNAME = $USER->name;
			$USERID =  $USER->id;
			$ACCOUNT = $USER->accountId;
			$theme_name = $USER->themeName;
		}

		$theme_title = UserGetTheme($dbh, $USERID, 'title');
		if (!$theme_title)
			$theme_title = "ANT Soft Blue";
		if (!$theme_name)
			$theme_name = "skygrey";
		$theme_css = UserGetTheme($dbh, $USERID, 'css');
		if (!$theme_css)
			$theme_css = "ant_skygrey.css";

		$session = array();

		// Get account data
		$session['account'] = array(
			"id" => $this->ant->id,
			"name" => $this->ant->name,
			"companyName" => $this->ant->settingsGet("general/company_name")
		);
		
		// Get user data
		$session['user'] = array(
			"id" => $this->user->id,
			"name" => $this->user->name,
			"fullName" => $this->user->fullName,
		);

		// Get current theme
		$session['theme'] = array(
			"name" => $this->user->themeName,
		);

		return $this->sendOutput($session);
	}

	/**
	 * Get user update stream
	 *
	 * @param array $params An assocaitive array of parameters passed to this function
	 * @return string json stream
     */
    public function getUpdateStream($params)
	{
		$updates = array();

		// Create a new chat server for this user
		$chatSvr = new AntChat($this->ant->dbh, $this->user);

		// TODO: notices
		// TODO: presence - a user logs on or off

		// Run the loop checking for updates until we have something to return
		$passes = 0; // We return every 20 seconds
		while(count($updates) == 0 && $passes<20)
		{
			// First check for new chats
			$newChats = $chatSvr->getNewMessages();
			if (count($newChats))
			{
				foreach ($newChats as $newChatFrom)
				{
					$updates[] = array(
						"type" => "chat",
						"data" => array(
							"friendName" => $newChatFrom,
						),
					);
				}
			}

			// For testing we may bust out of the loop
			if (isset($params['forceReturn']))
				break;
			else
			{
				$passes++;
				sleep(1);
			}
		}
		
		return $this->sendOutput($updates);
	}
}
