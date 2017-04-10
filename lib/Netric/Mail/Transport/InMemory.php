<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Transport;

use Netric\Mail\Message;

/**
 * InMemory transport
 *
 * This transport will just store the message in memory.  It is helpful
 * when unit testing, or to prevent sending email when in development or
 * testing.
 */
class InMemory implements TransportInterface
{
    /**
     * @var Message
     */
    protected $lastMessage;

    /**
     * Takes the last message and saves it for testing.
     *
     * @param Message $message
     */
    public function send(Message $message)
    {
        $this->lastMessage = $message;
    }

    /**
     * Get the last message sent.
     *
     * @return Message
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }
}
