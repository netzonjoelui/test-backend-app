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
use Netric\Mail;
use Netric\Mime;
use Netric\FileSystem\FileSystem;

/**
 * Email entity extension
 *
 * Example
 * <code>
 * 	$email = $entityLoader->create("email_message");
 * 	$email->setValue("sent_from", "sky.stebnicki@aereus.com");
 * 	$email->setValue("send_to", "someone@somewhere.com");
 * 	$email->setValue("body", "Hello there");
 *  $email->setValue("body_type", EmailMessageEntity::BODY_TYPE_PLAIN);
 * 	$email->addAttachment("/path/to/my/file.txt");
 * 	$sender = $serviceManager->get("Netric\Mail\Sender");
 *  $sender->send($email);
 * </code>
 */
class EmailMessageEntity extends Entity implements EntityInterface
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
     * FileSystem to work with files
     *
     * @var FileSystem
     */
    private $fileSystem = null;

    /**
     * Body types
     */
    const BODY_TYPE_PLAIN = 'plain';
    const BODY_TYPE_HTML = 'html';

    /**
     * Class constructor
     *
     * @param EntityDefinition $def The definition of the email message
     * @param EntityLoader $entityLoader Loader to get/save entities
     * @param IndexInterface $entityIndex Index to query entities
     * @param FileSystem $fileSystem Used for working with netric files
     */
    public function __construct(
        EntityDefinition $def,
        EntityLoader $entityLoader,
        IndexInterface $entityIndex,
        FileSystem $fileSystem)
    {
        $this->entityLoader = $entityLoader;
        $this->entityIndex = $entityIndex;
        $this->fileSystem = $fileSystem;
        parent::__construct($def);
    }

    /**
     * Callback function used for derived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
        // Make sure a unique message_id is set
        if (!$this->getValue('message_id'))
        {
            $this->setValue('message_id', $this->generateMessageId());
        }

        // Update num_attachments
        $attachments = $this->getValue("attachments");
        $this->setValue("num_attachments", (is_array($attachments)) ? count($attachments) : 0);

        // If this email message is not part of a thread, create one
        if (!$this->getValue("thread"))
        {
            $this->attachToThread();
        }
        else
        {
            $this->updateThreadFromMessage();
        }
    }

    /**
     * Callback function used for derived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
        if ($this->isDeleted())
        {
            $thread = $this->entityLoader->get("email_thread", $this->getValue("thread"));

            // If this is the last message, then delete the thread
            if (intval($thread->getValue("num_messages")) === 1)
            {
                $thread->setValue("num_messages", 0);
                $this->entityLoader->delete($thread);
            }
            else
            {
                // Otherwise reduce the number of messages
                $numMessages = $thread->getValue("num_messages");
                $thread->setValue("num_messages", --$numMessages);
                $this->entityLoader->save($thread);
            }
        }
    }

    /**
     * Called right before the entity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeDeleteHard(AccountServiceManagerInterface $sm)
    {
        // If purging, then clear the raw file holding our message data
        if ($this->getValue('file_id'))
        {
            $file = $this->fileSystem->openFileById($this->getValue('file_id'));
            if ($file)
            {
                $this->fileSystem->deleteFile($file, true);
            }
        }

        $thread = $this->entityLoader->get("email_thread", $this->getValue("thread"));

        // If this is the last message, then purge the thread
        if ($thread && intval($thread->getValue("num_messages")) === 1)
        {
            $this->entityLoader->delete($thread, true);
        }
    }

    /**
     * Export the contents of this entity to a mime message for sending
     *
     * @return Mail\Message
     */
    public function toMailMessage()
    {
        $message = new Mail\Message();
        $message->setEncoding('UTF-8');
        $message->setSubject($this->getValue("subject"));

        // Set from
        $from = $this->getAddressListFromString($this->getValue("sent_from"));
        if ($from) {
            $message->addFrom($from);
        }

        // Set to
        $to = $this->getAddressListFromString($this->getValue("send_to"));
        if ($to) {
            $message->addTo($to);
        }

        // Set cc
        $cc = $this->getAddressListFromString($this->getValue("cc"));
        if ($cc) {
            $message->addCc($cc);
        }

        $bcc = $this->getAddressListFromString($this->getValue("bcc"));
        if ($bcc) {
            $message->addBcc($bcc);
        }

        if ($this->getValue("in_reply_to")) {
            $message->getHeaders()->addHeaderLine("in-reply-to", $this->getValue("in_reply_to"));
        }

        /*
         * Setup the body and attachments - mime message
         */

        // HTML part
        $htmlPart = new Mime\Part($this->getHtmlBody());
        $htmlPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $htmlPart->setType(Mime\Mime::TYPE_HTML);
        $htmlPart->setCharset("UTF-8");

        // Plain text part
        $textPart = new Mime\Part($this->getPlainBody());
        $textPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $textPart->setType(Mime\Mime::TYPE_TEXT);
        $textPart->setCharset("UTF-8");

        // Create a multipart/alternative message for the text and html parts
        $bodyMessage = new Mime\Message();
        $bodyMessage->addPart($textPart);
        $bodyMessage->addPart($htmlPart);

        // Create mime message to wrap both the body and any attachments
        $mimeMessage = new Mime\Message();

        // Add text & html alternatives to the mime message wrapper
        $bodyPart = new Mime\Part($bodyMessage->generateMessage());
        $bodyPart->setType(Mime\Mime::MULTIPART_ALTERNATIVE);
        $bodyPart->setBoundary($bodyMessage->getMime()->boundary());
        $mimeMessage->addPart($bodyPart);

        // Add attachments
        $this->addMimeAttachments($mimeMessage);

        // Add the message to the mail/Message and return
        $message->setBody($mimeMessage);

        return $message;
    }

    /**
     * Import entity from a Mail\Message
     *
     * This is often used for importing new messages from a backend
     *
     * @param Mail\Message $message
     */
    public function fromMailMessage(Mail\Message $message)
    {
        $this->setValue("subject", $message->getSubject());
        $this->setValue("sent_from", $this->getAddressStringFromList($message->getFrom()));
        $this->setValue("send_to", $this->getAddressStringFromList($message->getTo()));
        $this->setValue("cc", $this->getAddressStringFromList($message->getCc()));
        $this->setValue("bcc", $this->getAddressStringFromList($message->getBcc()));
        $this->setValue("reply_to", $this->getAddressStringFromList($message->getReplyTo()));

        $headers = $message->getHeaders();

        // message_id
        if ($headers->get("message-id"))
            $this->setValue("message_id", $headers->get("message-id")->getFieldValue());

        // priority
        if ($headers->get("priority"))
            $this->setValue("priority", $headers->get("priority")->getFieldValue());

        // flag_spam
        if ($headers->get("x-spam-flag")) {
            if (strtolower(trim($headers->get("x-spam-flag")->getFieldValue())) == 'yes') {
                $this->setValue("flag_spam", true);
            }
        }

        // spam_report
        if ($headers->get("x-spam-report"))
            $this->setValue("spam_report", $headers->get("x-spam-report")->getFieldValue());

        // content_type
        if ($headers->get("content-type"))
            $this->setValue("content_type", $headers->get("content-type")->getFieldValue());

        // return_path
        if ($headers->get("return-path"))
            $this->setValue("return_path", $headers->get("return-path")->getFieldValue());

        // in_reply_to
        if ($headers->get("in-reply-to"))
            $this->setValue("in_reply_to", $headers->get("in-reply-to")->getFieldValue());

        // Date
        if ($headers->get("date"))
            $this->setValue("message_date", strtotime($headers->get("date")->getFieldValue()));

        // message_size

        // orig_header
        $this->setValue("orig_header", $headers->toString());

        // Add attachments and body
        $body = $message->getBody();
        if (is_string($body)) {
            $this->setValue("body", $body);
            if ($this->getValue("content_type")) {
                $ctypeParts = explode("/", $this->getValue("content_type"));
                $bodyType = (isset($ctypeParts[1])) ? $ctypeParts[1] : self::BODY_TYPE_PLAIN;
                $this->setValue("body_type", $bodyType);
            } else {
                $this->setValue("body_type", self::BODY_TYPE_PLAIN);
            }
        } else {
            // Multi-part message
            $parts = $message->getBody()->getParts();
            $this->fromMailMessageMultiPart($parts);
        }

    }

    /**
     * Get the HTML version of email body
     *
     * This function will convert plain to HTML if the body
     * is plain text.
     *
     * @return string
     */
    public function getHtmlBody()
    {
        $body = $this->getValue("body");

        if ($this->getValue("body_type") != self::BODY_TYPE_PLAIN) {
            return $body;
        }

        // Replace space with &bnsp; to make sure things look okay
        $body = str_replace(" ", "&nbsp;", $body);

        // Replace tab with three spaces
        $body = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $body);

        // Replace \n new lines with <br />
        $body = nl2br(htmlspecialchars($body));

        // Replace links with HTML links
        $body = preg_replace('/\s(\w+:\/\/)(\S+)/', ' <a href="\\1\\2">\\1\\2</a>', $body);

        // Return converted body
        return $body;
    }

    /**
     * Get the plain text version of email body
     *
     * @return string
     */
    public function getPlainBody()
    {
        if ($this->getValue("body_type") == self::BODY_TYPE_PLAIN) {
            return $this->getValue("body");
        }

        $body = $this->getValue("body");

        // Convert breaks to new lines
        $body = str_replace("<br>", "\n", $body);

        // Convert breaks to new lines
        $body = str_replace("<br />", "\n", $body);

        // Remove css style tags
        $body = preg_replace("/<style.*?<\/style>/is", "", $body);

        // Remove all other html tags
        $body = strip_tags($body);

        // Return the results
        return $body;
    }

    /**
     * Import body and attachments from Mime parts
     *
     * @param Mime\Part[] $parts
     */
    private function fromMailMessageMultiPart(array $parts)
    {
        foreach ($parts as $mimePart) {
            // Add all attachments if they have a name
            if ($mimePart->getFileName()) {
                // This is an attachment - could either be inline or an attachment
                $file = $this->fileSystem->createFile("%tmp%", $mimePart->getFileName(), true);
                $this->fileSystem->writeFile($file, $mimePart->getRawContent());
                $this->addMultiValue("attachments", $file->getId(), $file->getName());
            } else if ($mimePart->getType() == Mime\Mime::TYPE_HTML) {
                // If multipart/aleternative then this will come after 'plain' and overwrite
                $this->setValue("body", trim($mimePart->getRawContent()));
                $this->setValue("body_type", self::BODY_TYPE_HTML);
            } else if ($mimePart->getType() == Mime\Mime::TYPE_TEXT) {
                // Plain text part
                $this->setValue("body", trim($mimePart->getRawContent()));
                $this->setValue("body_type", self::BODY_TYPE_PLAIN);
            } else if ($mimePart->getType() == Mime\Mime::MULTIPART_ALTERNATIVE) {
                // Multipart alternative
                $mimeMessage = Mime\Message::createFromMessage($mimePart->getRawContent(), $mimePart->getBoundary());
                $altParts = $mimeMessage->getParts();
                $this->fromMailMessageMultiPart($altParts);
            }
        }
    }

    /**
     * Convert an address list to a comma separated string
     *
     * @param Mail\AddressList $addressList
     * @return string Comma separated string of addresses
     */
    private function getAddressStringFromList($addressList)
    {
        $toArr = [];
        foreach ($addressList as $emailAddress) {
            $toArr[] = $emailAddress->toString();
        }
        return implode(",", $toArr);
    }

    /**
     * Get an address list from a comma separated list of addresses
     *
     * @param string $addresses List of addresses to turn into a list
     * @return Mail\AddressList
     */
    private function getAddressListFromString($addresses)
    {
        if (!$addresses)
            return null;

        $addressList = new Mail\AddressList();

        $addressParts = preg_split("/[;,]+/", $addresses);
        foreach ($addressParts as $part) {
            if ($part) {
                $addressList->addFromString($part);
            }
        }

        return ($addressList->count()) ? $addressList : null;
    }

    /**
     * Add all attachments to a mimeMessage as parts (streams)
     *
     * @param Mime\Message $mimeMessage
     */
    private function addMimeAttachments(Mime\Message $mimeMessage)
    {
        // Add attachments to the mime message
        $attachments = $this->getValue("attachments");
        if (is_array($attachments) && count($attachments))
        {
            foreach ($attachments as $fileId)
            {
                $file = $this->fileSystem->openFileById($fileId);

                // Get a stream to reduce memory footprint
                $fileStream = $this->fileSystem->openFileStreamById($fileId);
                $attachment = new Mime\Part($fileStream);

                // Set meta-data
                $attachment->setType($file->getMimeType());
                $attachment->setFileName($file->getName());
                $attachment->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT);

                // Setting the encoding is recommended for binary data
                $attachment->setEncoding(Mime\Mime::ENCODING_BASE64);
                $mimeMessage->addPart($attachment);
            }
        }
    }

    /**
     * Search email threads to see if this message should be part of an existing thread
     *
     * @return EmailThreadEntity If a suitable thread was found
     */
    public function discoverThread()
    {
        $thread = null;

        /*
         * The easiest way to link a thread is to check and see if the created
         * message is in reply to a thread already created. We can probably do
         * a better job of detecting other possible candidates, but this should work
         * at least for cases where the sender includes in-reply-to in the header.
         */
        if (trim($this->getValue("in_reply_to")))
        {
            $query = new EntityQuery("email_message");
            $query->where("message_id")->equals($this->getValue("in_reply_to"));
            $query->andWhere("owner_id")->equals($this->getValue("owner_id"));
            $results = $this->entityIndex->executeQuery($query);
            if ($results->getNum())
            {
                $emailMessage = $results->getEntity(0);
                $thread = $this->entityLoader->get("email_thread", $emailMessage->getValue("thread"));
            }
        }

        return $thread;
    }

    /**
     * Either find and attach this message to an existing thread, or create a new one
     *
     * This should only be called one time if no thread has yet been defined for
     * an email message, once it's been set this funciton will never be called again.
     *
     * @throws \RuntimeException If this email message is already attached to a thread
     */
    private function attachToThread()
    {
        if ($this->getValue("thread"))
        {
            throw new \RuntimeException("Message is already attached to a thread");
        }

        // First check to see if we can find an existing thread we should attach to
        $thread = $this->discoverThread();

        // If we could not find a thread that already exists, then create a new one
        if (!$thread)
        {
            $thread = $this->entityLoader->create("email_thread");
            $thread->setValue("owner_id", $this->getValue("owner_id"));
            $thread->setValue("num_messages", 0);
        }

        // Change subject of thread to the subject of this (last) message
        $thread->setValue("subject", $this->getValue("subject"));

        // Increment the number of messages in the thread
        $numMessages = (int) $thread->getValue("num_messages");
        $thread->setValue("num_messages", ++$numMessages);

        // Update num_attachments in thread
        if ($this->getValue("num_attachments")) {
            $numAtt = $thread->getValue("num_attachments");
            $thread->setValue("num_attachments", $numAtt + $this->getValue("num_attachments"));
        }

        // Add email message from to thread senders
        $thread->addToSenders($this->getValue("sent_from"));

        // Add email message to to thread receivers
        $thread->addToReceivers($this->getValue("send_to"));

        // Add message body to thread body - mostly for snippets and searching
        $existingBody = $thread->getValue("body");
        $thread->setValue("body", $this->getValue("body") . "\n\n" . $existingBody);

        // Now update some common fields and save the thread
        $this->updateThreadFromMessage($thread);

        // Set the thread of this message to the discovered (or created) thread
        $this->setValue("thread", $thread->getId());
    }

    /**
     * Update the thread this message is attached to based on this message's field values
     *
     * These are values that should be updated every time the email message is saved.
     *
     * @param EmailThreadEntity $thread Optional reference to opened thread to update
     * @throws \InvalidArgumentException if no thread has been set for this message
     */
    private function updateThreadFromMessage(EmailThreadEntity $thread = null)
    {
        if (!$this->getValue("thread") && !$thread)
            throw new \InvalidArgumentException("Thread must be passed or set first");

        // If the message is deleted then do not update the thread
        if ($this->isDeleted()) {
            return;
        }

        // If a thread was not passed, the load it from value
        if (!$thread) {
            $thread = $this->entityLoader->get("email_thread", $this->getValue("thread"));
        }

        /*
         * If the seen flag of any single message is updated in the thread,
         * the thread flag should be updated as well.
         */
        $thread->setValue("f_seen", $this->getValue("flag_seen"));

        /*
         * Add this mailbox to the thread if not already set.
         * The 'mailbox_id' field in threads is a groupings (fkey_multi)
         * type and in email messages it's a single fkey field because
         * a message can only be in one mailbox but a thread can be in
         * multiple mailboxes - groupings.
         */
        if ($this->getValue("mailbox_id")) {
            // addMultiValue will not allow duplicates
            $thread->addMultiValue("mailbox_id", $this->getValue("mailbox_id"));
        }

        // Update the delivered date
        if ($this->getValue("message_date")) {
            // Only update if this is newer than the last message added
            if (!$thread->getValue("ts_delivered")
                || $thread->getValue("ts_delivered") < $this->getValue("message_date")) {
                // Set  the last delivered date of the thread to this message date
                $thread->setValue("ts_delivered", $this->getValue("message_date"));
            }
        }

        // Save the changes
        if (!$this->entityLoader->save($thread))
            throw new RuntimeException("Failed saving thread!");
    }

    /**
     * Create a unique message id for this email message
     */
    private function generateMessageId()
    {
        return '<' . sha1(microtime()) . '@netric.com>';
    }
}
