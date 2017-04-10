<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Mail\Transport;

use Netric\Mail\Message;

interface TransportInterface
{
    /**
     * Send a message
     *
     * @param Message $message
     */
    public function send(Message $message);
}