<?php
/**
 * Z-Push backend for netric
 *
 * The reason all the files are lowercase in here is because that is the z-push standard
 * so we stick with it to be consistent.
 */

$zPushRoot = dirname(__FILE__) ."/../../";

// Interfaces we are extending
require_once($zPushRoot . 'lib/interface/ibackend.php');
require_once($zPushRoot . 'lib/interface/isearchprovider.php');

// Supporting files and exceptions
require_once($zPushRoot . 'lib/core/zpush.php');
require_once($zPushRoot . 'lib/request/request.php');
require_once($zPushRoot . 'lib/exceptions/authenticationrequiredexception.php');
require_once($zPushRoot . 'lib/exceptions/statusexception.php');

// Local backend files
require_once($zPushRoot . 'backend/netric/exportfolderchangesnetric.php');
require_once($zPushRoot . 'backend/netric/importchangesnetric.php');
require_once($zPushRoot . 'backend/netric/exportchangesnetric.php');
require_once($zPushRoot . 'backend/netric/entityprovider.php');
require_once($zPushRoot . 'backend/netric/entitysearchprovider.php');

// Include netric autoloader for all netric libraries
require_once(dirname(__FILE__)."/../../../../init_autoloader.php");

// Include provider to conver sync objects to entities
require_once(dirname(__FILE__)."/entityprovider.php");


/**
 * Netric backend class
 */
class BackendNetric implements IBackend
{
    /**
     * Netric log
     *
     * @var Netric\Log
     */
    private $log = null;

    /**
     * Current account/tenant
     *
     * @var Netric\Account\Account
     */
    private $account = null;

    /**
     * Current netric user
     *
     * @var Netric\Entity\ObjType\UserEntity
     */
    private $user = null;

    /**
     * Unique ID of the device we are talking to
     *
     * @var string
     */
    private $deviceId = null;

    /**
     * Folders we are watching for changes
     *
     * @var array
     */
    private $sinkFolders = array();

    /**
     * Provider to inetract with netric entities
     *
     * @var EntityProvider
     */
    private $entityProvider = null;

    /**
     * Service for sending out emails
     *
     * @var Netric\Mail\SenderService
     */
    private $mailSenderService = null;

    /**
     * Sync partnership with the device
     *
     * @var Netric\EntitySync\Partner
     */
    private $partnership = null;

    /**
     * Local cache of sync collections
     *
     * @var Netric\EntitySync\Collection\CollectionInterface[]
     */
    private $syncCollections = array();

    /**
     * Cleanup and save final changes
     */
    public function __destruct()
    {
        if ($this->account && $this->partnership) {
            $this->savePartner();
        }
    }

    /**
     * Returns a IStateMachine implementation used to save states
     *
     * @access public
     * @return boolean/object       if false is returned, the default Statemachine is
     *                              used else the implementation of IStateMachine
     */
    public function GetStateMachine()
    {
        // Use the default file state machine
        return false;
    }

    /**
     * Returns a ISearchProvider implementation used for searches
     *
     * @access public
     * @return object       Implementation of ISearchProvider
     */
    public function GetSearchProvider()
    {
        return new EntitySearchProvider($this->account, $this->user);
    }

    /**
     * Indicates which AS version is supported by the backend.
     * Depending on this value the supported AS version announced to the
     * mobile device is set.
     *
     * @access public
     * @return string       AS version constant
     */
    public function GetSupportedASVersion()
    {
        return ZPush::ASV_14;
    }

    /**
     * Authenticates the user
     *
     * @param string        $username
     * @param string        $domain
     * @param string        $password
     *
     * @access public
     * @return boolean
     * @throws FatalException   e.g. some required libraries are unavailable
     */
    public function Logon($username, $domain, $password)
    {
        // Setup config
        $configLoader = new Netric\Config\ConfigLoader();
        $applicationEnvironment = (getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : "production";
        $config = $configLoader->fromFolder(dirname(__FILE__)."/../../../../config", $applicationEnvironment);
        $application = new Netric\Application\Application($config);
        $this->log = $application->getLog();
        $this->account = $application->getAccount(null, $domain);

        if (!$this->account) {
            throw new FatalException(
                sprintf("Logon('%s'): Account could not be loaded", $domain)
            );
        }

        // Get the authentication service and authenticate the credentials
        $sm = $this->account->getServiceManager();
        $authService = $sm->get("/Netric/Authentication/AuthenticationService");
        if ($authService->authenticate($username, $password))
        {
            $this->user = $this->account->getUser(null, $username);
            /*
            if ($this->user->timezoneName)
                date_default_timezone_set($this->user->timezoneName);
             */
        }
        else
        {
            throw new AuthenticationRequiredException("Bad username and/or password");
        }

        // The deviceId is required
        if (!$this->deviceId)
            $this->deviceId = Request::GetDeviceID();

        if (!$this->deviceId)
            throw new AuthenticationRequiredException("No device ID is defined in this request and it is required");

        // Setup the entity provider
        $this->entityProvider = new EntityProvider($this->account, $this->user);

        // Return auth results
        if ($this->user)
        {
             ZLog::Write(LOGLEVEL_INFO,"Login: $username, $domain, [success]");
            return true;
        }
        else
        {
             ZLog::Write(LOGLEVEL_INFO,"Login: $username, $domain, [failed]");
            return false;
        }
    }

    /**
     * Setup the backend to work on a specific store or checks ACLs there.
     * If only the $store is submitted, all Import/Export/Fetch/Etc operations should be
     * performed on this store (switch operations store).
     * If the ACL check is enabled, this operation should just indicate the ACL status on
     * the submitted store, without changing the store for operations.
     * For the ACL status, the currently logged on user MUST have access rights on
     *  - the entire store - admin access if no folderid is sent, or
     *  - on a specific folderid in the store (secretary/full access rights)
     *
     * The ACLcheck MUST fail if a folder of the authenticated user is checked!
     *
     * @param string $store Target store, could contain a "domain\user" value
     * @param boolean $checkACLonly If set to true, Setup() should just check ACLs
     * @param string $folderid If set, only ACLs on this folderid are relevant
     * @return boolean true on success
     */
    public function Setup($store, $checkACLonly = false, $folderid = false)
    {
        if (!isset($this->user))
            return false;

        return true;
    }

    /**
     * Logs off
     * non critical operations closing the session should be done here
     *
     * @return boolean
     */
    public function Logoff()
    {
        if ($this->account && $this->partnership) {
            $this->savePartner();
        }

        $this->user = null;
        $this->account = null;
        return true;
    }

    /**
     * Returns an array of SyncFolder types with the entire folder hierarchy
     * on the server (the array itself is flat, but refers to parents via the 'parent' property
     *
     * provides AS 1.0 compatibility
     *
     * @return array SYNC_FOLDER
     */
    public function GetHierarchy()
    {
        $folders = $this->entityProvider->getAllFolders();
        ZLog::Write(LOGLEVEL_INFO,"GetHierarchy: returning with " . count($folders));
        return $folders;
    }

    /**
     * Returns the importer to process changes from the mobile
     * If no $folderid is given, hierarchy data will be imported
     * With a $folderid a content data will be imported
     *
     * @param string $folderid Optional folder ID to import to
     * @return IImportChanges
     * @throws StatusException
     */
    public function GetImporter($folderid = false)
    {
         ZLog::Write(LOGLEVEL_INFO,"BackendNetric->GetImporter For $folderid");
        return new ImportChangesNetric(
            $this->log,
            $this->getSyncCollection($folderid),
            $this->entityProvider,
            $folderid
        );
    }

    /**
     * Returns the exporter to send changes to the mobile
     * If no $folderid is given, hierarchy data should be exported
     * With a $folderid a content data is expected
     *
     *  @param string $folderid Optional folder ID to export to
     * @return IExportChanges
     * @throws StatusException
     */
    public function GetExporter($folderid = false)
    {
        if ($folderid) {
             ZLog::Write(LOGLEVEL_INFO,"BackendNetric->GetExporter Got entity exporter for $folderid");
            return new ExportChangeNetric(
                $this->log,
                $this->getSyncCollection($folderid),
                $this->entityProvider,
                $folderid
            );
        } else {
             ZLog::Write(LOGLEVEL_INFO,"BackendNetric->GetExporter Got folder exporter");
            return new ExportFolderChangeNetric(
                $this->log,
                $this->entityProvider
            );
        }
    }

    /**
     * Sends an e-mail
     * This messages needs to be saved into the 'sent items' folder
     *
     * Basically two things can be done
     *      1) Send the message to an SMTP server as-is
     *      2) Parse the message, and send it some other way
     *
     * @param SyncSendMai $sm SyncSendMail object to send
     * @return boolean true on success
     * @throws StatusException
     */
    public function SendMail($sm)
    {
        // Convert to a Netric\Mail\Message object
        $message = Netric\Mail\Message::fromString($sm->mime);

        // Create a new EmailMessage entity from the mail message
        $entityLoader = $this->account->getServiceManager()->get("EntityLoader");
        $emailEntity = $entityLoader->create("email_message");
        $emailEntity->setValue("owner_id", $this->user->getId());

        // Import the mail message into the entity
        $emailEntity->fromMailMessage($message);

        // Setup the sender service
        $senderService = $this->mailSenderService;

        // Get SenderService from the service manager if it is not set
        if (!$senderService) {
            $senderService = $this->account->getServiceManager()->get("Netric/Mail/SenderService");
            $this->mailSenderService = $senderService;
        }

        // Send
        return $senderService->send($emailEntity);
    }

    /**
     * Returns all available data of a single message
     *
     * @param string            $folderid
     * @param string            $id
     * @param ContentParameters $contentParameters flag
     *
     * @access public
     * @return object(SyncObject)
     * @throws StatusException
     */
    public function Fetch($folderid, $id, $contentParameters)
    {
         ZLog::Write(LOGLEVEL_INFO,"Fetch: $folderid, $id");
        return $this->entityProvider->getSyncObject($folderid, $id, $contentParameters);
    }

    /**
     * Returns the waste basket
     *
     * The waste basked is used when deleting items; if this function returns a valid folder ID,
     * then all deletes are handled as moves and are sent to the backend as a move.
     * If it returns FALSE, then deletes are handled as real deletes
     *
     * @return string|bool
     */
    public function GetWasteBasket()
    {
        // In netric we actually just delete an item which flags it
        return false;
    }

    /**
     * Returns the content of the named attachment as stream. The passed attachment identifier is
     * the exact string that is returned in the 'AttName' property of an SyncAttachment.
     * Any information necessary to locate the attachment must be encoded in that 'attname' property.
     * Data is written directly - 'print $data;'
     *
     * @param int $fileId Unique id of the file representing the attachment
     * @return SyncItemOperationsAttachment
     * @throws StatusException
     */
    public function GetAttachmentData($fileId)
    {
        if (!is_numeric($fileId))
        {
            throw new StatusException(
                sprintf("GetAttachmentData('%s'): Attachment requested for non-existing file", $fileId),
                SYNC_ITEMOPERATIONSSTATUS_INVALIDATT
            );
        }

        $fileSystem = $this->account->getServiceManager()->get("Netric/FileSystem/FileSystem");
        $file = $fileSystem->openFileById($fileId);

        // Make sure file is valid and was not deleted
        if (!$file)
        {
            // TODO: In the future the filesystem will handle permissions so we will need to call
            // $fileSystem->getLastError to populate the contents of the exception.
            throw new StatusException(
                sprintf("GetAttachmentData('%s'): No file by the requested id exists", $fileId),
                SYNC_ITEMOPERATIONSSTATUS_INVALIDATT
            );
        }

        $attachment = new SyncItemOperationsAttachment();
        $attachment->data = $fileSystem->openFileStreamById($fileId);;
        $attachment->contenttype = $file->getMimeType();
        return $attachment;
    }

    /**
     * Deletes all contents of the specified folder.
     * This is generally used to empty the trash (wastebasked), but could also be used on any
     * other folder.
     *
     * @param string $folderid
     * @param bool $includeSubfolders (opt) also delete sub folders, default true
     * @return boolean
     * @throws StatusException
     */
    public function EmptyFolder($folderid, $includeSubfolders = true)
    {
        // TODO: Not supported
        return false;
    }

    /**
     * Processes a response to a meeting request.
     * CalendarID is a reference and has to be set if a new calendar item is created
     *
     * @param string        $requestid      id of the object containing the request
     * @param string        $folderid       id of the parent folder of $requestid
     * @param string        $response
     * @return string id of the created/updated calendar obj
     * @throws StatusException
     */
    public function MeetingResponse($requestid, $folderid, $response)
    {

    }

    /**
     * Indicates if the backend has a ChangesSink.
     * A sink is an active notification mechanism which does not need polling.
     *
     * @return boolean
     */
    public function HasChangesSink()
    {
        return true;
    }

    /**
     * The folder should be considered by the sink.
     * Folders which were not initialized should not result in a notification
     * of IBacken->ChangesSink().
     *
     * @param string $folderid
     * @return bool false if there is any problem with that folder
     */
    public function ChangesSinkInitialize($folderid)
    {
        $this->sinkFolders[] = $folderid;
        return true;
    }

    /**
     * The actual ChangesSink.
     * For max. the $timeout value this method should block and if no changes
     * are available return an empty array.
     * If changes are available a list of folderids is expected.
     *
     * @param int $timeout Max. amount of seconds to block
     * @return array
     */
    public function ChangesSink($timeout = 30)
    {
        $notifications = array();
        $stopat = time() + $timeout - 1;

        while($stopat > time() && count($notifications)==0)
        {
            foreach ($this->sinkFolders as $folderId)
            {
                $collection = $this->getSyncCollection($folderId);

                // Check if this collection is behind the head (last) change
                if ($collection->isBehindHead())
                {
                    $notifications[] = $folderId;
                }
            }

            if (count($notifications)==0)
                sleep(5);
        }

         ZLog::Write(LOGLEVEL_INFO,"ChangeSync: returning with " . count($notifications) . " changed folders");
        return $notifications;
    }

    /**
     * Applies settings to and gets informations from the device
     *
     * @param SyncObject  $settings (SyncOOF or SyncUserInformation possible)
     * @return SyncObject $settings
     */
    public function Settings($settings)
    {
        if ($settings instanceof SyncOOF) {
            $this->settingsOOF($settings);
        }

        if ($settings instanceof SyncUserInformation) {
            $this->settingsUserInformation($settings);
        }

        return $settings;
    }

    /**
     * Resolves recipients
     *
     * @param SyncObject        $resolveRecipients
     * @return SyncResolveRecipients $resolveRecipients
     */
    public function ResolveRecipients($resolveRecipients)
    {
        // TODO: This is a function of search
        ZLog::Write(LOGLEVEL_ERROR,"Called ResolveRecipients but not supported");
        return false;
        /*
        if ($resolveRecipients instanceof SyncResolveRecipients) {
            $resolveRecipients->status = SYNC_RESOLVERECIPSSTATUS_SUCCESS;
            $resolveRecipients->recipient = array();
            foreach ($resolveRecipients->to as $i => $to) {
                $recipient = $this->resolveRecipient($to);
                if ($recipient instanceof SyncResolveRecipient) {
                    $resolveRecipients->recipient[$i] = $recipient;
                }
                elseif (is_int($recipient)) {
                    $resolveRecipients->status = $recipient;
                }
            }

            return $resolveRecipients;
        }

         ZLog::Write(LOGLEVEL_INFO,>warning("Not a valid SyncResolveRecipients object.");
        // return a SyncResolveRecipients object so that sync doesn't fail
        $r = new SyncResolveRecipients();
        $r->status = SYNC_RESOLVERECIPSSTATUS_PROTOCOLERROR;
        $r->recipient = array();
        return $r;
        */
    }

    /**
     * Returns the email address and the display name of the user. Used by autodiscover.
     *
     * @param string $username The username
     * @return Array(fullname, emailaddress)|bool
     */
    public function GetUserDetails($username)
    {
        $user = $this->account->getUser(null, $username);
        if ($user)
        {
            return array(
                'emailaddress' => $user->getValue('email'),
                'fullname' => $user->getValue("full_name")
            );
        }
        else
        {
            return false;
        }
    }

    /**
     * Returns the username of the currently active user
     *
     * @return string
     */
    public function GetCurrentUsername()
    {
        return $this->user->getName();
    }


    /**
     * Manually set the deviceId
     *
     * This is normally set from Request::getDeviceId but there
     * are occasions (like unit tests) where we want to set it manually
     *
     * @param string $deviceId
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * Manually set the mail sender service
     *
     * If this is not set then the SendMessage function will pull
     * it in from the service locator.
     *
     * @param Netric\Mail\SenderService
     */
    public function setSenderService(Netric\Mail\SenderService $senderService)
    {
        $this->mailSenderService = $senderService;
    }

    // Private utility functions
    // ====================================================================================

    /**
     * Get AntObjectSync partnership collection based on folder id
     *
     * @param string $folderid The folder we are synchronizing
     * @return Netric\EntitySync\Collection\CollectionInterface
     * @throws StatusException If there is no partnership setup
     */
    private function getSyncCollection($folderid)
    {
        $parent = null;

        if (!$this->partnership && $this->deviceId)
        {
            $serviceManager = $this->account->getServiceManager();
            $entitySync = $serviceManager->get("EntitySync");
            $this->partnership = $entitySync->getPartner($this->deviceId);
            if (!$this->partnership)
            {
                $this->partnership = $entitySync->createPartner($this->deviceId, $this->user->getId());
            }
        }

        if (!$this->partnership)
            throw new StatusException("Could not create partnership");

        // Check if we have already loaded the collection
        if (isset($this->syncCollections[$folderid]))
            return $this->syncCollections[$folderid];

        // Properties used to create a new collection if needed
        $cond = array();
        $objType = null;

        // Get the parts of the folderid since it is in form {type}-{id}
        $folder = $this->entityProvider->unpackFolderId($folderid);

        // get object collection
        switch ($folder['type'])
        {
            case EntityProvider::FOLDER_TYPE_CONTACT:
                $objType = "contact_personal";
                $cond = array(
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field" => "user_id",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue" => $this->user->getId()
                    )
                );
                break;

            case EntityProvider::FOLDER_TYPE_CALENDAR:
                $objType = "calendar_event";
                $cond = array(
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field" => "calendar",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue" => $folder['id']
                    )
                );
                break;

            case EntityProvider::FOLDER_TYPE_NOTE:
                $objType = "note";
                $cond = array(
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field"=>"user_id",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue"=>$this->user->getId()
                    ),
                );

                break;

            case EntityProvider::FOLDER_TYPE_TASK:
                $objType = "task";
                $cond = array(
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field"=>"user_id",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue"=>$this->user->getId()
                    )
                );
                break;

            case EntityProvider::FOLDER_TYPE_EMAIL:
                $objType = "email_message";
                $cond = array(
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field"=>"owner_id",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue"=>$this->user->getId()
                    ),
                    array(
                        "blogic" => Netric\EntityQuery\Where::COMBINED_BY_AND,
                        "field" => "mailbox_id",
                        "operator" => Netric\EntityQuery\Where::OPERATOR_EQUAL_TO,
                        "condValue" => $folder['id']
                    ),
                );
                break;
        }

        $coll = $this->partnership->getEntityCollection($objType, $cond);
        if (!$coll)
        {
            // Get service locator for account
            $serviceManager = $this->account->getServiceManager();
            $coll = \Netric\EntitySync\Collection\CollectionFactory::create(
                $serviceManager,
                \Netric\EntitySync\EntitySync::COLL_TYPE_ENTITY
            );
            $coll->setObjType($objType);
            $coll->setConditions($cond);
            $this->partnership->addCollection($coll);
            $serviceManager->get("EntitySync_DataMapper")->savePartner($this->partnership);
        }

        // Cache for future requests
        $this->syncCollections[$folderid] = $coll;

        return $coll;
    }

    /**
     * The meta function for out of office settings.
     *
     * @param SyncObject $oof
     *
     * @access private
     * @return void
     */
    private function settingsOOF(&$oof)
    {
        //if oof state is set it must be set of oof and get otherwise
        if (isset($oof->oofstate)) {
            $this->settingsOOFSEt($oof);
        }
        else {
            $this->settingsOOFGEt($oof);
        }
    }

    /**
     * Gets the out of office settings
     *
     * @param SyncObject $oof
     *
     * @access private
     * @return void
     */
    private function settingsOOFGEt(&$oof)
    {
        $oof->oofstate = SYNC_SETTINGSOOF_DISABLED;
        $oof->Status = SYNC_SETTINGSSTATUS_SUCCESS;

        /* TODO: Implement
        $oofprops = mapi_getprops($this->defaultstore, array(PR_EC_OUTOFOFFICE, PR_EC_OUTOFOFFICE_MSG, PR_EC_OUTOFOFFICE_SUBJECT));

        if ($oofprops != false) {
            $oof->oofstate = isset($oofprops[PR_EC_OUTOFOFFICE]) ? ($oofprops[PR_EC_OUTOFOFFICE] ? SYNC_SETTINGSOOF_GLOBAL : SYNC_SETTINGSOOF_DISABLED) : SYNC_SETTINGSOOF_DISABLED;
            //TODO external and external unknown
            $oofmessage = new SyncOOFMessage();
            $oofmessage->appliesToInternal = "";
            $oofmessage->enabled = $oof->oofstate;
            $oofmessage->replymessage = (isset($oofprops[PR_EC_OUTOFOFFICE_MSG])) ? w2u($oofprops[PR_EC_OUTOFOFFICE_MSG]) : "";
            $oofmessage->bodytype = $oof->bodytype;
            unset($oofmessage->appliesToExternal, $oofmessage->appliesToExternalUnknown);
            $oof->oofmessage[] = $oofmessage;
        }
        else {
            ZLog::Write(LOGLEVEL_WARN, "Unable to get out of office information");
        }

        //unset body type for oof in order not to stream it
        unset($oof->bodytype);
        */
    }

    /**
     * Sets the out of office settings.
     *
     * @param SyncObject $oof
     *
     * @access private
     * @return void
     */
    private function settingsOOFSEt(&$oof)
    {
        $oof->Status = SYNC_SETTINGSSTATUS_SUCCESS;

        /* TODO: Implement
        $props = array();
        if ($oof->oofstate == SYNC_SETTINGSOOF_GLOBAL || $oof->oofstate == SYNC_SETTINGSOOF_TIMEBASED) {
            $props[PR_EC_OUTOFOFFICE] = true;
            foreach ($oof->oofmessage as $oofmessage) {
                if (isset($oofmessage->appliesToInternal)) {
                    $props[PR_EC_OUTOFOFFICE_MSG] = isset($oofmessage->replymessage) ? u2w($oofmessage->replymessage) : "";
                    $props[PR_EC_OUTOFOFFICE_SUBJECT] = "Out of office";
                }
            }
        }
        elseif($oof->oofstate == SYNC_SETTINGSOOF_DISABLED) {
            $props[PR_EC_OUTOFOFFICE] = false;
        }

        if (!empty($props)) {
            @mapi_setprops($this->defaultstore, $props);
            $result = mapi_last_hresult();
            if ($result != NOERROR) {
                ZLog::Write(LOGLEVEL_ERROR, sprintf("Setting oof information failed (%X)", $result));
                return false;
            }
        }

        return true;
        */
    }

    /**
     * Gets the user's email address
     *
     * @param SyncObject $userinformation
     *
     * @access private
     * @return bool
     */
    private function settingsUserInformation(&$userinformation) {
        if (!isset($this->user)) {
            ZLog::Write(LOGLEVEL_WARN, "The store or user are not available for getting user information");
            return false;
        }

        // Add user's email address
        $userinformation->Status = SYNC_SETTINGSSTATUS_USERINFO_SUCCESS;
        $userinformation->emailaddresses[] = $this->user->getValue("email");
        return true;
    }

    /**
     * Save changes to the partnership
     */
    private function savePartner()
    {
        /*
         * We need to save any changes made to the partnership
         * during the sync, including any updates to commit logs
         */
        if ($this->partnership) {
            $serviceManager = $this->account->getServiceManager();
            $serviceManager->get("EntitySync_DataMapper")->savePartner($this->partnership);
             ZLog::Write(LOGLEVEL_INFO,"Saved partnership: " . $this->partnership->getId());
        }
    }
}
