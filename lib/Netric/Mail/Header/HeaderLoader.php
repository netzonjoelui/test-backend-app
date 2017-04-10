<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Header;

use Zend\Loader\PluginClassLoader;

/**
 * Plugin Class Loader implementation for HTTP headers
 */
class HeaderLoader extends PluginClassLoader
{
    /**
     * @var array Pre-aliased Header plugins
     */
    protected $plugins = [
        'bcc'                       => 'Netric\Mail\Header\Bcc',
        'cc'                        => 'Netric\Mail\Header\Cc',
        'contenttype'               => 'Netric\Mail\Header\ContentType',
        'content_type'              => 'Netric\Mail\Header\ContentType',
        'content-type'              => 'Netric\Mail\Header\ContentType',
        'contenttransferencoding'   => 'Netric\Mail\Header\ContentTransferEncoding',
        'content_transfer_encoding' => 'Netric\Mail\Header\ContentTransferEncoding',
        'content-transfer-encoding' => 'Netric\Mail\Header\ContentTransferEncoding',
        'date'                      => 'Netric\Mail\Header\Date',
        'from'                      => 'Netric\Mail\Header\From',
        'message-id'                => 'Netric\Mail\Header\MessageId',
        'mimeversion'               => 'Netric\Mail\Header\MimeVersion',
        'mime_version'              => 'Netric\Mail\Header\MimeVersion',
        'mime-version'              => 'Netric\Mail\Header\MimeVersion',
        'received'                  => 'Netric\Mail\Header\Received',
        'replyto'                   => 'Netric\Mail\Header\ReplyTo',
        'reply_to'                  => 'Netric\Mail\Header\ReplyTo',
        'reply-to'                  => 'Netric\Mail\Header\ReplyTo',
        'sender'                    => 'Netric\Mail\Header\Sender',
        'subject'                   => 'Netric\Mail\Header\Subject',
        'to'                        => 'Netric\Mail\Header\To',
    ];
}
