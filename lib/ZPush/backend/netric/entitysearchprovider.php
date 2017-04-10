<?php
/**
 * Search provider for finding entities
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */
$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces we are extending
require_once($zPushRoot . 'lib/interface/isearchprovider.php');

// Supporting files and exceptions
require_once($zPushRoot . 'lib/core/zpush.php');
require_once($zPushRoot . 'lib/core/contentparameters.php');
require_once($zPushRoot . 'lib/request/request.php');
require_once($zPushRoot . 'lib/exceptions/authenticationrequiredexception.php');
require_once($zPushRoot . 'lib/exceptions/statusexception.php');

// Include netric autoloader for all netric libraries
require_once(dirname(__FILE__)."/../../../../init_autoloader.php");

/**
 * Simple diff of changes
 */
class EntitySearchProvider implements ISearchProvider
{
    /**
     * Authenticated user
     *
     * @var \Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    /**
     * Current account
     *
     * @var \Netric\Account\Account
     */
    private $account = null;

    /**
     * Constructor
     *
     * @param \Netric\Account\Account $account Current netric account
     * @param \Netric\Entity\ObjType\UserEntity $user The current user
     * @throws StatusException, FatalException
     */
    public function __construct(
        \Netric\Account\Account $account,
        \Netric\Entity\ObjType\UserEntity $user
    )
    {
        if (!$account) {
            throw new StatusException("Cannot setup search without an account");
        }

        $this->account = $account;
        $this->user = $user;
    }

    /**
     * Indicates if a search type is supported by this SearchProvider
     *
     * Currently only the type SEARCH_GAL (Global Address List) and SEARCH_MAILBOX are implemented
     *
     * @param string $searchtype Matching ISearchProvider::SEARCH_* constant
     * @return boolean If true then a search type is supported, otherwise false
     */
    public function SupportsType($searchtype)
    {

        if ($searchtype === ISearchProvider::SEARCH_GAL)
            return true;

        if ($searchtype === ISearchProvider::SEARCH_MAILBOX)
            return true;

        // False for all other types
        return false;
    }

    /**
     * Searches the Global Address List - users
     *
     * @param string $searchquery The string to search for
     * @param string $searchrange String with offset and limit in form "numstart-numend"
     * @return array
     * @throws StatusException
     */
    public function GetGALSearchResults($searchquery, $searchrange)
    {
        // Range for the search results
        $offset = 0;
        $limit = 50;

        if ($searchrange != '0' && $searchrange != '0-0') {
            $pos = strpos($searchrange, '-');
            $offset = (int) substr($searchrange, 0, $pos);
            $rangeend = (int) substr($searchrange, ($pos + 1));

            // We set limit from offset, calculate by subtracting end-start range
            $limit = $rangeend - $offset;
        }

        $items = array();

        // Create entity query
        $query = new \Netric\EntityQuery("user");
        $query->where("*")->contains($searchquery);
        $query->setOffset($offset);
        $query->setLimit($limit);

        // Execute the query and get the results
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $num = $results->getNum();
        $totalNum = $results->getTotalNum();

        // Loop through each user and add them to the list
        for ($i = 0; $i < $num; $i++) {
            $user = $results->getEntity($i);
            $item = array();

            // Skip if the user does not have an email address
            if (!$user->getValue("email")) {
                ZLog::Write(LOGLEVEL_WARN, "EntitySearchProvider->GetGALSearchResults: User " . $user->getId() . " does not have an email address and will be ignored.");
                continue;
            }

            $item[SYNC_GAL_DISPLAYNAME] = $user->getValue("name");
            $item[SYNC_GAL_ALIAS] = 0;
            $item[SYNC_GAL_FIRSTNAME] = $user->getFirstName();
            $item[SYNC_GAL_LASTNAME] = $user->getLastName();

            if (!$item[SYNC_GAL_LASTNAME]) {
                $item[SYNC_GAL_LASTNAME] = $item[SYNC_GAL_DISPLAYNAME];
            }

            $item[SYNC_GAL_EMAILADDRESS] = $user->getValue('email');

            //check if an user has an office number or it might produce warnings in the log
            if ($user->getValue('phone_office'))
                $item[SYNC_GAL_PHONE] = $user->getValue('phone_office');

            //check if an user has a mobile number or it might produce warnings in the log
            if ($user->getValue('phone_mobile'))
                $item[SYNC_GAL_MOBILEPHONE] = $user->getValue('phone_mobile');

            //check if an user has a home number or it might produce warnings in the log
            if ($user->getValue('phone_home'))
                $item[SYNC_GAL_HOMEPHONE] = $user->getValue('phone_home');

            /*
            if (isset($abentries[$i][PR_COMPANY_NAME]))
                $items[$i][SYNC_GAL_COMPANY] = w2u($abentries[$i][PR_COMPANY_NAME]);
            */

            if ($user->getValue('job_title'))
                $item[SYNC_GAL_TITLE] = $user->getValue('job_title');

            if ($user->getValue('city') || $user->getValue('state')) {
                $item[SYNC_GAL_OFFICE] = $user->getValue('city') . " " . $user->getValue('state');
            }



            // Add item/user to the results
            $items[] = $item;
        }

        $nrResults = count($items);
        $items['range'] = ($nrResults > 0) ? $offset . '-' . ($nrResults - 1) : '0-0';
        $items['searchtotal'] = $totalNum;
        return $items;
    }

    /**
     * Searches for the emails on the server
     *
     * @param ContentParameters $cpo
     * @return array
     */
    public function GetMailboxSearchResults($cpo)
    {
        $searchText = $cpo->GetSearchFreeText();
        $searchGreater = strtotime($cpo->GetSearchValueGreater());
        $searchLess = strtotime($cpo->GetSearchValueLess());
        $searchRange = explode('-', $cpo->GetSearchRange());
        $searchFolderId = $cpo->GetSearchFolderid();
        $mailboxId = null;
        $searchFolders = array();

        // Decode folder id to get the group id for the mailbox_id field
        if ($searchFolderId) {
            $parts = explode("-", $searchFolderId);
            if (count($parts) === 2) {
                $mailboxId = $parts[1];
            }
        }

        $items = array();

        // Create entity query
        $query = new \Netric\EntityQuery("email_message");
        $query->where("*")->contains($searchText);
        if ($searchGreater)
            $query->andWhere("ts_entered")->isGreaterOrEqualTo($searchGreater);
        if ($searchLess)
            $query->andWhere("ts_entered")->isLessOrEqualTo($searchLess);

        if ($mailboxId) {
            if ($cpo->GetSearchDeepTraversal()) {
                $query->andWhere("mailbox_id")->isLessOrEqualTo($mailboxId);
            } else {
                $query->andWhere("mailbox_id")->equals($mailboxId);
            }
        }

        // Filter by user id of course
        $query->andWhere("owner_id")->equals($this->user->getId());

        if (isset($searchRange[0]) && is_numeric($searchRange[0]))
            $query->setOffset($searchRange[0]);
        if (isset($searchRange[1]) && is_numeric($searchRange[1]))
            $query->setLimit($searchRange[1] - $searchRange[0]);

        // Execute the query and get the results
        $index = $this->account->getServiceManager()->get("EntityQuery_Index");
        $results = $index->executeQuery($query);
        $num = $results->getNum();
        $totalNum = $results->getTotalNum();

        // Loop through each user and add them to the list
        for ($i = 0; $i < $num; $i++) {
            $email = $results->getEntity($i);
            $items[] = array(
                'class' => 'Email',
                'longid' => $email->getId(),
                'folderid' => \EntityProvider::FOLDER_TYPE_EMAIL . "-" . $email->getValue("mailbox_id")
            );
        }

        $items['searchtotal'] = $totalNum;
        $items["range"] = $cpo->GetSearchRange();
        return $items;
    }

    /**
     * Terminates a search for a given PID
     *
     * @param int $pid
     *
     * @return boolean
     */
    public function TerminateSearch($pid)
    {
        // We don't really need to do this since we don't have threads in PHP
        return true;
    }


    /**
     * Disconnects from the current search provider
     *
     * @access public
     * @return boolean
     */
    public function Disconnect()
    {
        // No need to disconnect from anything
        return true;
    }
}