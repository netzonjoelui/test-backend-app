<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2016 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\Mail;

use Netric\EntityQuery;
use Netric\Error\AbstractHasErrors;
use Netric\Entity\ObjType\EmailAccountEntity;
use Netric\Entity\ObjType\UserEntity;
use Netric\FileSystem\FileSystem;
use Netric\Log;
use Netric\EntityGroupings\Loader as GroupingsLoader;
use Netric\Entity\ObjType\EmailMessageEntity;
use Netric\Mail\Storage;
use Netric\Mail;
use Netric\EntityLoader;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\Mime;
use PhpMimeMailParser;

/**
 * Service responsible for delivering messages into netric
 *
 * @group integration
 */
class DeliveryService extends AbstractHasErrors
{
    /**
     * Log
     *
     * @var Log
     */
    private $log = null;


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
     * Current parser revision
     *
     * This is used to go back and re-process messages if needed
     *
     * @var int
     */
    const PARSE_REV = 16;

    /**
     * Filesystem for saving attachments
     *
     * @var FileSystem
     */
    private $fileSystem = null;

    /**
     * Construct the transport service
     *
     * @param Log $log
     * @param EntityLoader $entityLoader Loader to get and save messages
     * @param GroupingsLoader $groupingsLoader For loading mailbox groupings
     * @param IndexInterface $entityIndex The index for querying entities,
     * @param FileSystem $fileSystem For saving attachments
     */
    public function __construct(
        Log $log,
        EntityLoader $entityLoader,
        GroupingsLoader $groupingsLoader,
        IndexInterface $entityIndex,
        FileSystem $fileSystem
    ) {
        $this->log = $log;
        $this->entityLoader = $entityLoader;
        $this->groupingsLoader = $groupingsLoader;
        $this->entityIndex = $entityIndex;
        $this->fileSystem = $fileSystem;

        if (!function_exists('mailparse_msg_parse')) {
            throw new \RuntimeException("'pecl/mailparse' is a required extension.");
        }
    }

    /**
     * Import a message from a remote server into a netric entity
     *
     * @param UserEntity $user The user we are delivering on behalf of
     * @param string $uniqueId the id of the message on the server
     * @param Storage\Message $message The message retrieved from the server
     * @param EmailAccountEntity $emailAccount The account we are importing for
     * @param int $mailboxId The mailbox to place the new imssage into
     * @return int The imported message id, 0 on failure, and -1 if already imported
     */
    public function deliverMessage(UserEntity $user, $uniqueId, Storage\Message $message, EmailAccountEntity $emailAccount, $mailboxId)
    {
        $subject = (isset($message->subject)) ? $message->subject : "Untitled";

        // Check to make sure this message was not already imported - no duplicates
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($mailboxId);
        $query->andWhere("message_uid")->equals($uniqueId);
        $query->andWhere("email_account")->equals($emailAccount->getId());
        $query->andWhere("subject")->equals($subject);
        $result = $this->entityIndex->executeQuery($query);
        $num = $result->getNum();
        if ($num > 0) {
            $emailEntity = $result->getEntity(0);
            return $emailEntity->getId();
        }

        // Also checked previously deleted and return -1 if found
        $query = new EntityQuery("email_message");
        $query->where("mailbox_id")->equals($mailboxId);
        $query->andWhere("message_uid")->equals($uniqueId);
        $query->andWhere("email_account")->equals($emailAccount->getId());
        $query->andWhere("subject")->equals($subject);
        $query->andWhere("f_deleted")->equals(true);
        $result = $this->entityIndex->executeQuery($query);
        $num = $result->getNum();
        if ($num > 0) {
            return -1;
        }

        // Create EmailMessageEntity and import Mail\Message
        $emailEntity = $this->entityLoader->create("email_message");
        $this->importMailParse($emailEntity, $message);

        $emailEntity->setValue("email_account", $emailAccount->getId());
        $emailEntity->setValue("owner_id", $user->getId());
        $emailEntity->setValue("mailbox_id", $mailboxId);
        $emailEntity->setValue("message_uid", $uniqueId);
        $emailEntity->setValue("flag_seen", $message->hasFlag(Storage::FLAG_SEEN));
        $mailId = $this->entityLoader->save($emailEntity);

        // TODO: Process auto-responders?

        return $mailId;
    }

    /**
     * @deprecated We now use importMailParse
     *
     * @param Storage\Part $mime
     * @return Mime\Message
     */
    private function getMimeFromBody(Storage\Part $mime)
    {
        $mimeMessage = new Mime\Message();

        $foundPart = null;
        $parts = new \RecursiveIteratorIterator($mime);
        foreach ($parts as $part) {

            // Initialize the part to add
            $mimePart = null;

            if ($part->isMultipart()) {
                $subMimeMessage = $this->getMimeFromBody($part);
                $mimePart = new Mime\Part($subMimeMessage->generateMessage());
                $mimePart->setType($part->contentType);
                $mimePart->setBoundary($subMimeMessage->getMime()->boundary());
            } else {
                $mimePart = new Mime\Part($part->getContent());
                $headers = $part->getHeaders();
            }

            $mimeMessage->addPart($mimePart);

            if (strtok($part->contentType, ';') == 'text/plain') {
                $foundPart = $part;
                break;
            }
        }

        return $mimeMessage;
    }

    /**
     * Use mailParse to decode raw message and import it into ANT
     *
     * Inject an email from a file into netric using mailParse to parse which is preferred because
     * Mail_mimeDecode is ineffecient because it requires you read the entire
     * message into memory to parse. This is fine for small messages but netric
     * will accept up to 2GB emails so memory can be a limitation here. It is preferrable
     * to use the php mimeParse extension and read is incrementally in to the resource.
     *
     * @param EmailMessageEntity $email The email we are importing into\
     * @param Storage\Message $message The message received from imap
     * @return bool true on success, false on failure
     */
    public function importMailParse(EmailMessageEntity &$email, Storage\Message $message)
    {
        $parser = new PhpMimeMailParser\Parser();
        //$parser->setPath($filepath);
        //$parser->setStream($message?);

        // Wrap the headers since if they are invalid, it throws an exception
        try {
            $headers = $message->getHeaders()->toString();
            $parser->setText($headers . "\r\n" . $message->getContent());
        } catch (Mail\Exception\InvalidArgumentException $ex) {
            $this->log->error("DeliveryService->importMailParse: Failed to get headers - " . $ex->getMessage());
        }

        $parser->setText($headers . "\r\n" . $message->getContent());

        $plainbody = $parser->getMessageBody('text');
        $htmlbody = $parser->getMessageBody('html');

        // Get char types
        //$htmlCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("html"));
        //$plainCharType = $this->getCharTypeFromHeaders($parser->getMessageBodyHeaders("text"));

        $spamFlag = (trim(strtolower($parser->getHeader('x-spam-flag'))) == "yes") ? true : false;

        // Make sure messages are unicode
        /*
        ini_set('mbstring.substitute_character', "none");
        $plainbody= mb_convert_encoding($plainbody, 'UTF-8', $plainCharType);
        $htmlbody= mb_convert_encoding($htmlbody, 'UTF-8', $htmlCharType);
        */

        $origDate = $parser->getHeader('date');
        if (is_array($origDate))
            $origDate = $origDate[count($origDate) - 1];
        if (!strtotime($origDate) && $origDate)
            $origDate = substr($origDate, 0, strrpos($origDate, " "));
        $messageDate = ($origDate) ? date(DATE_RFC822, strtotime($origDate)) : date(DATE_RFC822);

        // Create new mail object and save it to ANT
        $email->setValue("message_date", $messageDate);
        $email->setValue("parse_rev", self::PARSE_REV);
        $email->setValue("subject", $parser->getHeader('subject'));
        $email->setValue("sent_from", $parser->getHeader('from'));
        $email->setValue("send_to", $parser->getHeader('to'));
        $email->setValue("cc", $parser->getHeader('cc'));
        $email->setValue("bcc", $parser->getHeader('bcc'));
        $email->setValue("in_reply_to", $parser->getHeader('in-reply-to'));
        $email->setValue("flag_spam", $spamFlag);
        $email->setValue("message_id", $parser->getHeader('message-id'));
        if ($htmlbody) {
            $email->setValue("body", $htmlbody);
            $email->setValue("body_type", "html");
        } else {
            $email->setValue("body", $plainbody);
            $email->setValue("body_type", "plain");
        }

        $attachments = $parser->getAttachments();
        foreach ($attachments as $att) {
            $this->importMailParseAtt($att, $email);
        }

        // Cleanup resources
        $parser = null;

        return true;
    }

    /**
     * Process attachments for a message being parsed by mimeparse
     *
     * @param PhpMimeMailParser\Attachment $parserAttach The attachment to import
     * @param EmailMessageEntity $email The email we are adding attachments to
     * @return bool true on success, false on failure
     */
    private function importMailParseAtt(
        PhpMimeMailParser\Attachment &$parserAttach,
        EmailMessageEntity &$email)
    {
        /*
         * Write attachment to temp file
         *
         * It is important to use streams here to try and keep the attachment out of
         * memory if possible. The parser should already have decoded the bodies for
         * us so no need to use base64_decode or any other decoding.
         */
        $tmpFile = tmpfile();
        $buf = null;
        while (($buf = $parserAttach->read()) != false) {
            fwrite($tmpFile, $buf);
        }

        // Rewind stream
        fseek($tmpFile, 0);

        // Stream the temp file into the fileSystem
        $file = $this->fileSystem->createFile("%tmp%", $parserAttach->getFilename(), true);
        $this->fileSystem->writeFile($file, $tmpFile);
        $email->addMultiValue("attachments", $file->getId(), $file->getName());
    }

    /**
     * Process filters and actions for an email message
     *
     * @param CAntObject_Email $email Handle to email to check, if null and not called statically then use $this
     * @param CDatabase $dbh Handle to database quired if called statically
     * @param AntUser $user Handle to current user required if called statically
     */
    private function importProcessFilters($email=null, $dbh=null, $user=null)
    {
        /*
        // Check for spam status
        // ------------------------------------------------
        $fromEmail = EmailAdressGetDisplay($email->getHeader("from"), 'address');
        if ("t" == $email->getValue("flag_spam"))
        {
            // First make sure this user is not in the whitelist
            $query = "select id from email_settings_spam where preference='whitelist_from' 
						and '".strtolower($fromEmail)."' like lower(replace(value, '*', '%'))
						and user_id='".$user->id."'";
            if (!$dbh->GetNumberRows($dbh->Query($query)))
            {
                $email->move($email->getGroupId("Junk Mail"));
                return; // No futher filters should be processed if this is junk
            }
        }
        else
        {
            // Now make sure this user is not in the blacklist
            $query = "select id from email_settings_spam where preference='blacklist_from' 
						and '".strtolower($fromEmail)."' like lower(replace(value, '*', '%'))
						and user_id='".$user->id."'";
            if ($dbh->GetNumberRows($dbh->Query($query)))
            {
                $email->move($email->getGroupId("Junk Mail"));
                //$email->setGroup("Junk Mail");
                return;
            }
        }

        // Check for filters
        // ------------------------------------------------
        $query = "select kw_subject, kw_to, kw_from, kw_body, act_mark_read, act_move_to 
					from email_filters where user_id='".$user->id."'";
        $result = $dbh->Query($query);
        $num = $dbh->GetNumberRows($result);
        for ($i = 0; $i < $num; $i++)
        {
            $row = $dbh->GetNextRow($result, $i);
            $fSkipFilter = false;

            if ($row['kw_subject'] && $email->getHeader("subject"))
            {
                if (stristr(strtolower($email->getHeader("subject")), strtolower($row['kw_subject']))!==false)
                {
                    $fSkipFilter = false;
                }
                else
                {
                    $fSkipFilter = true;
                }
            }

            if ($row['kw_to'] && $email->getHeader("to"))
            {
                if (stristr(strtolower($email->getHeader("to")), strtolower($row['kw_to']))!==false)
                {
                    $fSkipFilter = false;
                }
                else
                {
                    $fSkipFilter = true;
                }
            }

            if ($row['kw_from'] && $email->getHeader("from"))
            {
                if (stristr(strtolower($email->getHeader("from")), strtolower($row['kw_from']))!==false)
                {
                    $fSkipFilter = false;
                }
                else
                {
                    $fSkipFilter = true;
                }
            }

            if ($row['kw_body'] && $email->getBody())
            {
                $body = strtolower(strip_tags($email->getBody()));
                if (stristr($body, strtolower($row['kw_body']))!==false)
                {
                    $fSkipFilter = false;
                }
                else
                {
                    $fSkipFilter = true;
                }
            }

            if (!$fSkipFilter)
            {
                if ($row['act_move_to'])
                    $email->move($row['act_move_to']);
                //$email->setGroupId($row['act_move_to']);

                if ($rpw['act_mark_read'] == 't')
                    $email->markRead();
                //$email->f_seen = 'f';
            }
        }
        $dbh->FreeResults($result);

        // Check for a future date which is almost always junk mail
        // ------------------------------------------------
        if (strtotime("+30 days") < $email->getValue("message_date"))
        {
            $email->move($email->getGroupId("Junk Mail"));
        }
        */
    }

    /**
     * Process this message checking for auto responders
     */
    private function importProcessAutoResp()
    {
        // TODO: add auto-responder
    }
}
