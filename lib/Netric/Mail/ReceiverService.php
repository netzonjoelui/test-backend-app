<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Mail;

use Netric\Crypt\BlockCipher;
use Netric\Crypt\VaultService;
use Netric\EntityQuery;
use Netric\EntitySync\Collection\CollectionFactoryInterface;
use Netric\EntitySync\Collection\CollectionInterface;
use Netric\EntitySync\Partner;
use Netric\Error\AbstractHasErrors;
use Netric\Entity\ObjType\EmailAccountEntity;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntitySync\EntitySync;
use Netric\EntityQuery\Where;
use Netric\Log;
use Netric\EntityGroupings\Loader as GroupingsLoader;
use Netric\Mail\Storage;
use Netric\Mail\Storage\AbstractStorage;
use Netric\Mail\Storage\Imap;
use Netric\Mail\Storage\Pop3;
use Netric\EntityLoader;
use Netric\Mail\Storage\Writable\WritableInterface;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\Config\Config;
use Netric\Mime;

/**
 * Service responsible for receiving messages and synchronizing with remote mailboxes
 *
 * @group integration
 */
class ReceiverService extends AbstractHasErrors
{
    /**
     * Log
     *
     * @var Log
     */
    private $log = null;

    /**
     * Entity sync service
     *
     * @var EntitySync
     */
    private $entitySync = null;

    /**
     * The currently logged in user
     *
     * @var UserEntity
     */
    private $user = null;

    /**
     * Collection factory
     *
     * @var CollectionFactoryInterface
     */
    private $collectionFactory = null;

    /**
     * Entity groupings loader
     *
     * @var GroupingsLoader
     */
    private $groupingsLoader = null;

    /**
     * Entity loader
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Index for querying entities
     *
     * @var IndexInterface
     */
    private $entityIndex = null;

    /**
     * Vault service for getting keys
     *
     * @var VaultService|null
     */
    private $vaultService = null;

    /**
     * Email config params
     *
     * @var Config|null
     */
    private $config = null;

    /**
     * Delivery service to put messages into an entity
     *
     * @var DeliveryService
     */
    private $deliveryService = null;

    /**
     * Construct the transport service
     *
     * @param Log $log
     * @param UserEntity $user The currently logged in user
     * @param EntitySync $entitySync Sync Service
     * @param CollectionFactoryInterface $collectionFactory Factory for constructing collections
     * @param EntityLoader $entityLoader Loader to get and save messages
     * @param GroupingsLoader $groupingsLoader For loading mailbox groupings
     * @param IndexInterface $entityIndex The index for querying entities
     * @param VaultService $vaultService Service for retrieving encrypted keys
     * @param Config $config Email portion of config for connection defaults
     */
    public function __construct(
        Log $log,
        UserEntity $user,
        EntitySync $entitySync,
        CollectionFactoryInterface $collectionFactory,
        EntityLoader $entityLoader,
        GroupingsLoader $groupingsLoader,
        IndexInterface $entityIndex,
        VaultService $vaultService,
        Config $config,
        DeliveryService $deliveryService
    ) {
        $this->log = $log;
        $this->user = $user;
        $this->entitySync = $entitySync;
        $this->collectionFactory = $collectionFactory;
        $this->entityLoader = $entityLoader;
        $this->groupingsLoader = $groupingsLoader;
        $this->entityIndex = $entityIndex;
        $this->vaultService = $vaultService;
        $this->config = $config;
        $this->deliveryService = $deliveryService;
    }

    /**
     * Set the current user for this service
     *
     * @param UserEntity $user
     */
    public function setUser(UserEntity $user)
    {
        $this->user = $user;
    }

    /**
     * Synchronize a mailbox with a remote server
     *
     * @param int $mailboxId The id of the mailbox we are synchronizing
     * @param EmailAccountEntity $emailAccount The email account to sync
     * @return bool true on sucess, false on failure
     */
    public function syncMailbox($mailboxId, EmailAccountEntity $emailAccount)
    {
        // When syncing emails, account type should not be empty
        if(empty($emailAccount->getValue("type"))) {
            $this->log->info("ReceiverService->syncMail: Account has no type - " . $emailAccount->getId());
            return false;
        }

        // Get the mailbox path
        $mailboxGroupings = $this->groupingsLoader->get(
            "email_message", "mailbox_id", ["user_id"=>$this->user->getId()]
        );
        $mailboxPath = $mailboxGroupings->getpath($mailboxId);

        // Right now we only want to synchronize the Inbox - Sky
        if (strtolower($mailboxPath) != "inbox") {
            $this->log->info("ReceiverService->syncMail: $mailboxPath($mailboxId) is not an inbox and we only support inbox");
            return false;
        }

        // Get mail server connection
        try {
            $mail = $this->getMailConnection($emailAccount);
        } catch (Storage\Exception\RuntimeException $ex) {

            $blockCipher = new BlockCipher($this->vaultService->getSecret("EntityEnc"));
            $password = "";
            if ($emailAccount->getValue("password")) {
                $password = $blockCipher->decrypt($emailAccount->getValue("password"));
            }

            $this->log->error(
                "ReceiverService->syncMail: Unable to log in " .
                $emailAccount->getValue("address") . " - "  . $ex->getMessage());
            return false;
        }

        // Get object sync partnership and collection
        $syncPartner = $this->entitySync->getPartner("EmailAccounts/" . $emailAccount->getId());
        if (!$syncPartner) {
            $syncPartner = $this->entitySync->createPartner(
                "EmailAccounts/" . $emailAccount->getId(),
                $this->user->getId()
            );
        }
        $syncColl = $this->getSyncCollection($syncPartner, $emailAccount->getId(), $mailboxId);

        // First send changes to server
        $this->sendChanges($syncColl, $mail);            

        // Now get new messages from the server and import
        $this->receiveChanges($syncColl, $mail, $emailAccount, $mailboxId);

        // Save the changes to the collection
        $this->entitySync->savePartner($syncPartner);

        // Close the mail connection
        $mail->close();

        return true;
    }

    /**
     * Send local changes to the server
     *
     * @param CollectionInterface $syncColl
     * @param AbstractStorage $mailServer Current mail server connection
     */
    private function sendChanges(CollectionInterface $syncColl, AbstractStorage $mailServer)
    {
        while (count($stats = $syncColl->getExportChanged(false)) > 0) {
            foreach ($stats as $stat) {

                // Load the email entity
                $emailEntity = $this->entityLoader->get("email_message", $stat['id']);

                // The entity was somehow deleted on netric without the sync knowing
                if (!$emailEntity) {
                    /*
                     * If we do not remove it here, we wuold get stuck in a continuous loop
                     * due to the while ... getExportedChanges loop above.
                     */
                    $syncColl->logExported($stat['id'], null);
                    $this->log->error("ReceiverService->sendChanges: {$stat['id']} was deleted locally without stat knowing");
                    continue;
                }

                $msgNum = null;

                try {
                    $msgNum = $mailServer->getNumberByUniqueId($emailEntity->getValue("message_uid"));
                } catch (\Exception $ex) {
                    $this->log->info("ReceiverService->sendChanges: {$stat['id']} was deleted remotely on the server without stat knowing. " . $ex->getMessage());
                }

                if ($msgNum) {
                    switch ($stat['action']) {
                        case 'change':

                            if ($mailServer instanceof WritableInterface) {
                                // Wrapping all flag sets in a try catch since setting the same flag twice causes an exception
                                try {
                                    // Handle seen flag
                                    if ($emailEntity->getValue("flag_seen") === true) {
                                        $mailServer->setFlags($msgNum, [Storage::FLAG_SEEN]);
                                    } else {
                                        $mailServer->setFlags($msgNum, [Storage::FLAG_UNSEEN]);
                                    }

                                    // Handle flagged flag
                                    if ($emailEntity->getValue("flag_flagged") === true) {
                                        $mailServer->setFlags($msgNum, [Storage::FLAG_FLAGGED]);
                                    } else {
                                        $mailServer->setFlags($msgNum, [Storage::FLAG_PASSED]);
                                    }
                                } catch (Storage\Exception\RuntimeException $ex) {
                                    $this->log->info(
                                        "ReceiverService->sendChanges: Tried to set flag on $msgNum but server failed - probably alraedy set: " .
                                        $ex->getMessage()
                                    );
                                }

                                $this->log->info("Exported: change:{$stat['id']}:{$emailEntity->getValue("commit_id")}");
                            } else {
                                // Log that this mail server does not support writing changes
                                $this->log->info("Skipping export because server does not support WritableInterface: {$stat['id']}");
                            }

                            // Log that we exported this change so we never try to export it again
                            $syncColl->logExported($stat['id'], $emailEntity->getValue("commit_id"));
                            break;

                        case 'delete':
                            $mailServer->removeMessage($msgNum);
                            $syncColl->logExported($stat['id'], null);
                            $this->log->info("Exported: delete:{$stat['id']}");
                            break;

                        default:
                            // An action was sent that we do not know how to handle
                            throw new \RuntimeException("Sync action {$stat['action']} is not handled!");
                    }
                } else {
                    // If the message can no longer be found on the server - deleted since we did a sync
                    $syncColl->logExported($stat['id'], $emailEntity->getValue("commit_id"));
                }

                // Export last commit so we don't try to re-sync these changes next time
                if ($emailEntity->getValue("commit_id")) {
                    $syncColl->setLastCommitId($emailEntity->getValue("commit_id"));
                } else if ($syncColl->getId()) {
                    // If not permanently deleted then throw exception without commit id
                    throw new \RuntimeException(
                        "Tried to synchronize an email_message without a commit id: " .
                        $syncColl->getId()
                    );
                }
            }
        }
    }

    /**
     * Get changes from a remote server and sync them locally
     *
     * @param CollectionInterface $syncColl
     * @param AbstractStorage $mailServer Current mail server connection
     * @param EmailAccountEntity $emailAccount The email account to sync
     * @param int $mailboxId The mailbox to place the message into
     */
    private function receiveChanges(
        CollectionInterface $syncColl,
        AbstractStorage $mailServer,
        EmailAccountEntity $emailAccount,
        $mailboxId
    )
    {
        $importList = array();
        $numMessages = count($mailServer);
        for ($id = 1; $id <= $numMessages; $id++) {
            // Wrap in a try/catch in case anything goes weong getting the message
            try {
                $message = $mailServer->getMessage($id);
                $importList[] = array(
                    "remote_id" => $mailServer->getUniqueId($id),
                    "remote_revision"=>1,
                    "message" => $message
                );
            } catch (\Exception $ex) {
                $this->log->warning("Could not import message $id: " . $ex->getMessage());
            }
        }

        $stats = $syncColl->getImportChanged($importList);
        $junkMailId = $this->getJunkMailboxForUser($this->user);

        // $stat = array('remote_id', 'remote_revision', 'local_id', 'action', 'local_revision')
        foreach ($stats as $stat) {
            switch ($stat['action']) {
                case 'change':
                    // Set email meta data from server list
                    $message = null;
                    foreach ($importList as $toImport) {
                        if ($toImport['remote_id'] == $stat['remote_id']) {
                            $message = $toImport['message'];
                            break; // stop the loop
                        }
                    }

                    /*
                     * This condition should never happen since the stats are rendered
                     * directly from the importList, but just in case we should throw
                     * an exception if ever we cannot find a message in the list
                     * returned from the server.
                     */
                    if (!$message) {
                        throw new \RuntimeException("Could not find message in mailbox");
                    }

                    // Set return variable for keeping track of import
                    $importMid = 0;

                    if (isset($stat['local_id'])) {
                        $emailEntity = $this->entityLoader->get("email_message", $stat['local_id']);
                        $emailEntity->setValue("flag_seen", $message->hasFlag(Storage::FLAG_SEEN) ? true : false);
                        $emailEntity->setValue("flag_flagged", $message->hasFlag(Storage::FLAG_FLAGGED) ? true : false);
                        if ($emailEntity->fieldValueChanged("flag_seen") || $emailEntity->fieldValueChanged("flag_flagged")) {
                            $this->entityLoader->save($emailEntity);
                            $this->log->info("ReceiverService->receiveChanges: Imported change {$stat['local_id']}");
                        } else {
                            $importMid = $stat['local_id'];
                        }
                    } else {

                        // Check if the message was marked as spam
                        try {
                            if (strtolower($message->getHeader('x-spam-flag', 'string')) == "yes") {
                                if ($junkMailId && $junkMailId != $mailboxId) {
                                    $mailboxId = $junkMailId;
                                }
                            }
                        } catch (Storage\Exception\InvalidArgumentException $ex){
                            // Header was not found, keep going
                        }
                        

                        $importMid = $this->deliverMessage(
                            $stat['remote_id'],
                            $message,
                            $emailAccount,
                            $mailboxId
                        );
                        $this->log->info("ReceiverService->receiveChanges: Imported new $importMid");
                    }

                    // Log delivery result
                    if ($importMid > 0) {
                        $emailEntity = $this->entityLoader->get("email_message", $importMid);
                        $syncColl->logImported(
                            $stat['remote_id'],
                            $stat['remote_revision'],
                            $emailEntity->getId(),
                            $emailEntity->getValue("commit_id")
                        );
                        $this->log->info("ReceiverService->receiveChanges: Message delivered to $importMid");
                    } else if ($importMid == -1) {
                        // This message was previously imported and then deleted so delete on the server
                        $msgNum = $mailServer->getNumberByUniqueId($stat['remote_id']);
                        if ($msgNum) {
                            $mailServer->removeMessage($msgNum);
                            $syncColl->logImported($stat['remote_id']);
                            $this->log->info("ReceiverService->receiveChanges: Deleted stale imported message");
                        } else {
                            $this->log->error("ReceiverService->receiveChanges: Could not locate report message number from id");
                        }
                    } else {
                        // If there was an error it $this->importEmail will return zero which
                        // will do nothing. This will cause the system to try again nex time
                        $this->log->error("ReceiverService->receiveChanges: Error trying to import message {$stat['remote_id']}");
                    }

                    break;

                case 'delete':

                    if (isset($stat['local_id'])) {

                        $emailEntity = $this->entityLoader->get("email_message", $stat['local_id']);
                        if ($emailEntity && $emailEntity->getValue("f_deleted") === false) {
                            $this->entityLoader->delete($emailEntity);
                            $this->log->info("ReceiverService->receiveChanges: Imported delete {$stat['local_id']}");
                        }

                    }

                    $syncColl->logImported($stat['remote_id']);

                    break;
            }
        }
    }

    /**
     * Get an entity sync collection
     *
     * @param Partner $syncPartner The sync parter representing the email account
     * @param $accountId
     * @param $mailboxId
     * @return CollectionInterface
     * @throws \Exception
     */
    private function getSyncCollection(Partner $syncPartner, $accountId, $mailboxId)
    {
        $conditions = array(
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field" => "email_account",
                "operator" => Where::OPERATOR_EQUAL_TO,
                "condValue" => $accountId,
            ),
            array(
                "blogic" => Where::COMBINED_BY_AND,
                "field" => "mailbox_id",
                "operator" => Where::OPERATOR_EQUAL_TO,
                "condValue" => $mailboxId,
            ),
        );

        $syncColl = $syncPartner->getEntityCollection("email_message", $conditions);

        // Create collection if it does not yet exist
        if (!$syncColl)
        {
            $this->log->info("ReceiverService->syncMailbox: Creating a new collection for $mailboxId");

            $syncColl = $this->collectionFactory->createCollection(EntitySync::COLL_TYPE_ENTITY);
            $syncColl->setObjType("email_message");
            $syncColl->setConditions($conditions);
            $syncPartner->addCollection($syncColl);
            $this->entitySync->savePartner($syncPartner);
        }

        return $syncColl;
    }

    /**
     * Get a mail connection from an email account
     *
     * @param EmailAccountEntity $emailAccount
     * @return AbstractStorage
     * @throws \RuntimeException if an unsupported email account is found
     */
    private function getMailConnection(EmailAccountEntity $emailAccount)
    {
        $blockCipher = new BlockCipher($this->vaultService->getSecret("EntityEnc"));
        $password = "";
        if ($emailAccount->getValue("password")) {
            $password = $blockCipher->decrypt($emailAccount->getValue("password"));
        }

        $type = $emailAccount->getValue("type");
        $host = $emailAccount->getValue("host");

        // System generated email addresses should use global configs
        if ($emailAccount->getValue("f_system")) {
            $type = $this->config->default_type;
            $host = $this->config->imap_host;
        }

        switch ($type) {
            case 'imap':
                return new Imap(array(
                    'host'     => $host,
                    'user'     => $emailAccount->getValue("username"),
                    'password' => $password
                ));
                break;
            case 'pop3':
                return new Pop3(array(
                    'host'     => $host,
                    'user'     => $emailAccount->getValue("username"),
                    'password' => $password
                ));
                break;
            default:
                throw new \RuntimeException("Mail account not supported: " . $emailAccount->getValue("type"));
        }
    }

    /**
     * Import a message from a remote server into a netric entity
     *
     * @param string $uniqueId the id of the message on the server
     * @param Storage\Message $message The message retrieved from the server
     * @param EmailAccountEntity $emailAccount The account we are importing for
     * @param int $mailboxId The mailbox to place the new imssage into
     * @return int The imported message id, 0 on failure, and -1 if already imported
     */
    private function deliverMessage($uniqueId, Storage\Message $message, EmailAccountEntity $emailAccount, $mailboxId)
    {
        return $this->deliveryService->deliverMessage(
            $this->user,
            $uniqueId,
            $message,
            $emailAccount,
            $mailboxId
        );
    }

    /**
     * Get the junk mailbox for a user
     *
     * @param UserEntity $user
     * @return int Id of junk mailbox or null on failure
     */
    private function getJunkMailboxForUser(UserEntity $user)
    {
        $maiboxes = $this->groupingsLoader->get(
            "email_message",
            "mailbox_id",
            ["user_id"=>$user->getId()]
        );

        $junk = $maiboxes->getByPath("Junk Mail");
        if (!$junk) {
            return null;
        }

        return $junk->id;
    }
}
