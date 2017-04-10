<?php
/**
 * Test protocol used for unit tests
 *
 * @category  AntMail
 * @package   Test
 * @author	  joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
require_once('lib/AntMail/Protocol/Abstract.php');

/**
 * Test protocal definition
 */
class AntMail_Protocol_Test extends AntMail_Protocol_Abstract
{
    /**
     * Test messages
     *
     * @var array
     */
    private $messages = null;

    /**
     * Setup function must be implemented
     */
    public function setup()
    {
        $this->messages = array(
            "Inbox" => array(
                array(
                    "msgno" => 1,
                    "uid" => 101,
                    "message_id" => "TEST",
                    "subject" => "Test",
                    "from" => "foo@bar.com",
                    "to" => "bar@foo.com",
                    "date" => "20150227",
                    "seen" => true,
                    "size" => 100,
                    "message" => "From:test@test.com\r\nSubject:Test\r\nTo:bar@foo.com\r\n\r\nTest Message",
                ),
                array(
                    "msgno" => 2,
                    "uid" => 102,
                    "message_id" => "TEST2",
                    "subject" => "Test2",
                    "from" => "foo@bar.com",
                    "to" => "bar@foo.com",
                    "date" => "20150227",
                    "seen" => true,
                    "size" => 100,
                    "message" => "From:test@test.com\r\nSubject:Test2\r\nTo:bar@foo.com\r\n\r\nTest Message",
                ),
            ),
        );

        // Set unique to on
        $this->hasUniqueId = true;

        // Set two way sync to on
        $this->syncTwoWay = true;
    }

    /**
     * Get the last error
     */
    public function getLastError()
    {
    }

    /**
     * Gets the list of messages
     *
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
     * @param string $updateSince Some protocols support pulling updates after a variable
     */
    public function getMessageList($mailboxPath = null, $updateSince = null)
    {
        if (!$mailboxPath)
            $mailboxPath = "Inbox";

        return $this->messages[$mailboxPath];
    }

    /**
     * Get the number of messages in a mailbox
     *
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
     * @return int|bool number of messages on success, false on failure
     */
    public function getNumMessages($mailbox = "Inbox")
    {
        return count($this->messages[$mailbox]);
    }

    /**
     * Get a full mime message
     *
     * @param string $msgNo The number of the message in this mailbox to retrieve
     * @return string The full mime message
     */
    public function getFullMessage($msgno)
    {
        foreach ($this->messages as $mbox)
        {
            foreach ($mbox as $msg)
            {
                if ($msgno == $msg['msgno'])
                   return $msg['message'];
            }
        }

        return "";
    }

    /**
     * Deletes a message in IMAP Server
     *
     * @param integer $uid Message number to be deleted
     * @param string $mailboxPath Path of the mailbox e.g. [Gmail]/Drafts
     */
    public function deleteMessage($uid, $mailboxPath = null)
    {
        if (!$mailboxPath)
            $mailboxPath = "Inbox";

        for ($i = 0; $i < count($this->messages[$mailboxPath]); $i++)
        {
            if ($uid == $this->messages[$mailboxPath][$i]['uid'])
            {
                array_splice($this->messages[$mailboxPath], $i, 1);
                break;
            }
        }
    }
}