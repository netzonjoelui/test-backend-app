<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */

namespace Netric\Mail;

use Netric\Entity\ObjType\EmailMessageEntity;
use Netric\EntityLoader;
use Netric\Mail\Transport\TransportInterface;

/**
 * Service for sending out Email entities
 */
class EntityMailer
{
    /**
     * Loader to get and save entities
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * Default mail transport for messages sent by individual users
     *
     * @var TransportInterface
     */
    private $defaultTransport = null;

    /**
     * Bulk mail transport for mass or automated emails
     *
     * @var TransportInterface
     */
    private $bulkTransport = null;

    /**
     * Send an entity that is an email message
     *
     * @param EmailMessage $emailMessage The email message to send
     * @param bool|false $bulk A flag to indicate if this is a bulk message or sent by an individual
     */
    public function send(EmailMessage $emailMessage, $bulk = false)
    {
        // TODO: create and send the email message here
    }
}