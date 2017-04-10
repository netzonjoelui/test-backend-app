<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Entity\ObjType;

use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;
use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\EntityDefinition;
use Netric\Mail\Transport\TransportInterface;
use Netric\Mail;
use Netric\Mail\Address;

/**
 * Notification entity
 */
class NotificationEntity extends Entity implements EntityInterface
{
    /**
     * Mail transport for sending messages
     *
     * @var TransportInterface
     */
    private $mailTransport = null;

    /**
     * Callback function used for derived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
        /*
         * If this is a new notification, then send messages - email, sms.
         * Notifications are almost never updated, and when they are it is usually
         * to indicate that more comments were added but the user has already been notified
         * and not yet seen the entity being commented on, so there is no need to notify
         * them over and over if they have not even seen the last notice.
         */
        if (!$this->getId() && $objRef = $this->getValue('obj_reference'))
        {
            // If the email flag is set, then send an email
            if ($this->getValue("f_email"))
                $this->sendEmailNotification($sm);

            // If the SMS flag is set, then send sms
            if ($this->getValue("f_sms"))
                $this->sendSmsNotification();
        }
    }

    /**
     * Set an alternate transport for sending messages
     *
     * This is useful for unit tests and one-off alternate sending methods
     *
     * @param TransportInterface $mailTransport For sending messages
     */
    public function setMailTransport(TransportInterface $mailTransport)
    {
        $this->mailTransport = $mailTransport;
    }

    /**
     * Send this notice via email to the owner
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    private function sendEmailNotification(AccountServiceManagerInterface $sm)
    {
        // Make sure the notification has an owner or a creator
        if (empty($this->getValue("owner_id")) || empty($this->getValue("creator_id")))
            return;

        // If mail transport is not set, then set it here
        if (!$this->mailTransport)
            $this->mailTransport = $sm->get("Netric/Mail/Transport/Transport");

        // Get the user that owns this notice
        $user = $sm->get("EntityLoader")->get("user", $this->getValue("owner_id"));

        // Get the user that triggered this notice
        $creator = $sm->get("EntityLoader")->get("user", $this->getValue("creator_id"));

        // Make sure the user has an email
        if (!$user || !$user->getValue("email"))
            return;

        // Get the referenced entity
        $objReference = Entity::decodeObjRef($this->getValue("obj_reference"));
        $entity = $sm->get("EntityLoader")->get($objReference['obj_type'], $objReference['id']);

        // Set the body
        $body = $creator->getName() . " " . $this->getValue('name') . " on ";
        $body .= date("m/d/Y") . " at " . date("h:iA T") . "\r\n";
        $body .= "---------------------------------------\r\n\r\n";
        $body .= $entity->getDescription();
        $body .= "---------------------------------------\r\n\r\n";

        // Add link to body
        $body .= $sm->getAccount()->getAccountUrl() . "/obj/";
        $body .= $objReference['obj_type'] . "/" . $objReference['id'];
        $body .= "\n\n";
        $body .= "\r\n\r\nTIP: You can respond by replying to this email.";

        // Set from
        $config = $sm->get("Netric/Config/Config");
        $fromEmail = $config->email['noreply'];

        // Add special dropbox that enables users to comment by just replying to an email
        if ($config->email['dropbox_catchall']) {
            $fromEmail = $sm->getAccount()->getName() . "-com-";
            $fromEmail .= $objReference['obj_type'] . "." . $objReference['id'];
            $fromEmail .= $config->email['dropbox_catchall'];
        }

        try {
            // Create a new message and send it
            $from = new Address($fromEmail, $creator->getName());
            $message = new Mail\Message();
            $message->addFrom($from);
            $message->addTo($user->getValue("email"));
            $message->setBody($body);
            $message->setEncoding('UTF-8');
            $message->setSubject($this->getValue("name"));
            $this->mailTransport->send($message);
        } catch (\Exception $ex) {
            /*
             * This should never happen, but in case we cannot send the email for
             * reason we should log it as an error and continue working.
             */
            $log = $sm->get("Log");
            $log->error("Could not send notification: " . $ex->getMessage(), var_export($config, true));
        }

    }

    /**
     * Send an SMS notice to the owner of this notice
     *
     * @todo Implement an SMS gateway
     */
    private function sendSmsNotification()
    {
        // TODO: Not yet implemented since we have no transport/Gateway for SMS in Netric
    }
}
