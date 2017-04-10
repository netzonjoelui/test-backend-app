<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Mail;

use Netric\Error\AbstractHasErrors;
use Netric\Entity\ActivityLog;
use Netric\Entity\ObjType\EmailMessageEntity;
use Netric\Entity\ObjType\ActivityEntity;
use Netric\Mail\Transport\TransportInterface;
use Netric\Log;

/**
 * Service used for sending email messages
 */
class SenderService extends AbstractHasErrors
{
    /**
     * Mail transport for sending messages
     *
     * @var TransportInterface
     */
    private $mailTransport = null;

    /**
     * Mail transport for sending bulk messages
     *
     * It is often smart to utilize a different mailserver for
     * bulk messages to keep your primary mail transport clean.
     *
     * @var TransportInterface
     */
    private $bulkMailTransport = null;

    /**
     * Log
     *
     * @var Log
     */
    private $log = null;

    /**
     * Construct the transport service
     *
     * @param TransportInterface $mailTransport Sending transport for regular mail
     * @param TransportInterface $bulkTransport Sending transport for bulk mail
     * @param Log $log
     */
    public function __construct(
        TransportInterface $mailTransport,
        TransportInterface $bulkTransport,
        Log $log
    )
    {
        $this->mailTransport = $mailTransport;
        $this->bulkMailTransport = $bulkTransport;
        $this->log = $log;
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
     * Handle sending an email message
     *
     * @param EmailMessageEntity $emailMessage Email to send
     * @return bool true on success, false on failure with $this->getLastError set
     */
    public function send(EmailMessageEntity $emailMessage)
    {
        // Get Mime Message from the entity
        $message = $emailMessage->toMailMessage();

        // Attempt to send
        try {
            $this->mailTransport->send($message);

            // Log info
            $toEmail = $message->getTo()->current();
            $this->log->info(
                "Message successfully sent to " .
                (($toEmail) ? $toEmail->toString() : "unknown")
            );

            // Save the message in the sent directory for the current user

            return true;
        } catch (Transport\Exception\RuntimeException $ex) {
            $this->addErrorFromMessage($ex->getMessage());
            $this->log->error($ex->getMessage());
            return false;
        }
    }

    /**
     * Handle sending a bulk email message
     *
     * @param EmailMessageEntity $emailMessage Email to send
     * @return bool true on success, false on failure with $this->getLastError set
     */
    public function sendBulk(EmailMessageEntity $emailMessage)
    {
        // Get Mime Message from the entity
        $message = $emailMessage->toMailMessage();

        // TODO: Check nospam flag for customer

        // Attempt to send
        try {
            $this->bulkMailTransport->send($message);

            // Log info
            $this->log->info(
                "Message successfully sent to " .
                $message->getTo()->current()->toString()
            );

            // Save activity since this is a bulk message and has no sent folder
            //$this->activityLog->log($message, ActivityEntity::VERB_SENT);

            return true;
        } catch (Transport\Exception\RuntimeException $ex) {
            $this->addErrorFromMessage($ex->getMessage());
            $this->log->error($ex->getMessage());
            return false;
        }
    }
}
