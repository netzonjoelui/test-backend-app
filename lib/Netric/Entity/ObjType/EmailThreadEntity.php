<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Entity\ObjType;

use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;
use Netric\EntityDefinition;
use Netric\EntityLoader;
use Netric\EntityQuery;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\ServiceManager\AccountServiceManagerInterface;

/**
 * Email thread extension
 */
class EmailThreadEntity extends Entity implements EntityInterface
{
    /**
     * Loader used to get email threads and attachments
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Entity query index for finding threads
     *
     * @var IndexInterface
     */
    private $entityIndex = null;

    /**
     * Class constructor
     *
     * @param EntityDefinition $def The definition of the email message
     * @param EntityLoader $entityLoader Loader to get/save entities
     * @param IndexInterface $entityIndex Index to query entities
     */
    public function __construct(
        EntityDefinition $def,
        EntityLoader $entityLoader,
        IndexInterface $entityIndex)
    {
        $this->entityLoader = $entityLoader;
        $this->entityIndex = $entityIndex;
        parent::__construct($def);
    }

    /**
     * Callback function used for derived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Callback function used for derived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
        // Check it see if the user deleted the whole thread
        if ($this->isDeleted()) {
            $this->removeMessages(false);
        } else if ($this->fieldValueChanged("f_deleted")) {
            // Check if we un-deleted the thread
            $this->restoreMessages();
        }
    }

    /**
     * Called right before the entity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterDeleteHard(AccountServiceManagerInterface $sm)
    {
        // Purge all messages that were in this thread
        $this->removeMessages(true); // Now purge
    }

    /**
     * Merge an address or comma separated list of addresses to the senders list
     *
     * @param string $senders
     */
    public function addToSenders($senders)
    {
        $this->mergeAddressesWithField("senders", $senders);
    }

    /**
     * Merge an address or comma separated list of addresses to receivers list
     *
     * @param string $receivers
     */
    public function addToReceivers($receivers)
    {
        $this->mergeAddressesWithField("receivers", $receivers);
    }

    /**
     * Merge an address or comma separated list of addresses to a field
     *
     * @param string $fieldName The name of the field we are updating
     * @param string $addresses Comma separated list of addresses to add
     */
    private function mergeAddressesWithField($fieldName, $addresses)
    {
        // Combine existing with the new
        $newAddresses = explode(",", $addresses);

        // Trim
        for ($i = 0; $i < count($newAddresses); $i++)
        {
            $newAddresses[$i] = trim($newAddresses[$i]);
        }

        $oldAddresses = ($this->getValue($fieldName)) ? explode(",", $this->getValue($fieldName)) : [];
        $combined= array_merge($newAddresses, $oldAddresses);

        // Make the receivers unique so we only see a name once
        $combined = array_unique($combined);

        // Update value
        $this->setValue($fieldName, implode(",", $combined));
    }

    /**
     * Remove all messages in this thread
     *
     * @param bool $hard Flag to indicate if we should just soft delete (save with flag) or purge
     */
    private function removeMessages($hard = false)
    {
        if (!$this->getId())
            return;

        $query = new EntityQuery("email_message");
        $query->where("thread")->equals($this->getId());
        $results = $this->entityIndex->executeQuery($query);
        $num = $results->getTotalNum();
        for ($i = 0; $i < $num; $i++)
        {
            $emailMessage = $results->getEntity($i);
            $this->entityLoader->delete($emailMessage, $hard);
        }

        // If we are doing a hard delete, then also get previously deleted
        if ($hard)
        {
            $query = new EntityQuery("email_message");
            $query->where("thread")->equals($this->getId());
            $query->andWhere("f_deleted")->equals(true);
            $results = $this->entityIndex->executeQuery($query);
            $num = $results->getTotalNum();
            for ($i = 0; $i < $num; $i++)
            {
                $emailMessage = $results->getEntity($i);
                $this->entityLoader->delete($emailMessage, true);
            }
        }
    }

    /**
     * Restore all soft-deleted messages by setting deleted flag to false
     */
    private function restoreMessages()
    {
        if (!$this->getId())
            return;

        $query = new EntityQuery("email_message");
        $query->where("thread")->equals($this->getId());
        $query->andWhere("f_deleted")->equals(true);
        $results = $this->entityIndex->executeQuery($query);
        $num = $results->getTotalNum();
        for ($i = 0; $i < $num; $i++)
        {
            $emailMessage = $results->getEntity($i);
            $emailMessage->setValue("f_deleted", false);
            $this->entityLoader->save($emailMessage);
        }
    }
}
