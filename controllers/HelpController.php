<?php
/**
* Help application actions
*/
require_once("lib/Email.php");
require_once("lib/aereus.lib.php/CAntCase.php");
require_once("lib/aereus.lib.php/CAntCustomer.php");

/**
* Class for controlling Datacenter functions
*/
class HelpController extends Controller
{    
    public function __construct($ant, $user)
    {
        $this->ant = $ant;
        $this->user = $user;

		$config = AntConfig::getInstance();
        
        $this->server = $config->aereus['server'];
        $this->userName = $config->aereus['user'];
        $this->password = $config->aereus['password'];
    }

    /**
    * Create Database
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function submitCase($params)
    {
        if ($params['subject'] && $params['description'])
        {
            $custid = $this->user->getAereusCustomerId();

            $caseapi = new AntApi_Object($this->server, $this->userName, $this->password, "case");
            if ($params['id'])
                $caseapi->open($params['id']);
            $caseapi->setValue("title", $params['subject']);
            $caseapi->setValue("description", $params['description']);
            $caseapi->setValue("status_id", 3000265); // New - Unanswered
            $caseapi->setValue("project_id", 1656);
            $caseapi->setValue("severity_id", 1); // Low
            $caseapi->setValue("sent_by", "customer:" . $custid);
            $caseapi->setValue("customer_id", $custid);
            $ret = $caseapi->save();
        }
        else
            $ret = array("error"=>"subject and description are required params");

        echo json_encode($ret);
        return $ret;
    }
    
    /**
     * Get current cases
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getCases()
    {
        $dbh = $this->ant->dbh;
		$ret = array();
        
        $custid = $this->user->getAereusCustomerId();
        if ($custid)
        {
            $objList = new AntApi_ObjectList($this->server, $this->userName, $this->password, "case");
            $objList->addCondition("and", "customer_id", "is_equal", "$custid");
            $objList->addSortOrder("ts_entered", "DESC");
            $objList->getObjects();
            $num = $objList->getNumObjects();
            
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $objList->getObject($i);
                
                $status = $obj->getForeignValue("status_id");
                if(empty($status))
                    $status = "open";

				$ret[] = array("id" => $obj->getValue("id"), 
							   "name" => $obj->getValue('title'), 
							   "customer_id" => $custid,
							   "timeEntered" => $obj->getValue('ts_entered'), 
							   "statusName" => $status,
							   "link" => "https://" . $this->server ."/public/support/case/" . $obj->getValue("id") . "/" . $custid,
				);
            }
        }
        else
            $ret = array("error"=>"Current user has invalid customer Id.");

        return $this->sendOutputJson($ret);
    }

	/**
	 * Get tour items
	 *
	 * Accept an array of tour item ids to load, and check to see if they have already been dismissed
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function getTourItems($params)
	{
		$items = array();

		$tourIds = $params['tourIds'];
		$help = $this->ant->getServiceLocator()->get("Help");

		// Sort by name so we can control which tours load firsrt in a group
		sort($tourIds);

		// Get the content for each item
		foreach ($tourIds as $tourId)
		{
			// Check to see if this tour item has already been dismissed or not
			$dismissed = $this->user->getSetting("help/tours/" . $tourId . "/dismissed");
			if ((string)$dismissed != "1")
			{
				$items[] = array(
					"id" => $tourId,
					"html" => $help->getTourItem($tourId, $this->user),
				);
			}
		}

		return $this->sendOutput($items);
	}

	/**
	 * Indicate that a user has seen and dismissed a tour item
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function setTourItemDismissed($params)
	{
		// Set $params['tour_id'] to seen for this user
		$this->user->setSetting("help/tours/" . $params['tour_id'] . "/dismissed", '1');

		return $this->sendOutput(1);
	}
}
