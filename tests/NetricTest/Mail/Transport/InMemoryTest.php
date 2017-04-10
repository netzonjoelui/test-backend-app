<?php
/**
 * Netric Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Netric Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace NetricTest\Mail\Transport;

use Netric\Mail\Message;
use Netric\Mail\Transport\InMemory;

/**
 * @group      Netric_Mail
 */
class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function getMessage()
    {
        $message = new Message();
        $message->addTo('devteam@netric.com', 'Netric DevTeam')
                ->addCc('matthew@zend.com')
                ->addBcc('zf-crteam@lists.zend.com', 'CR-Team, ZF Project')
                ->addFrom([
                    'devteam@netric.com',
                    'matthew@zend.com' => 'Matthew',
                ])
                ->setSender('ralph.schindler@zend.com', 'Ralph Schindler')
                ->setSubject('Testing Netric\Mail\Transport\Sendmail')
                ->setBody('This is only a test.');
        $message->getHeaders()->addHeaders([
            'X-Foo-Bar' => 'Matthew',
        ]);
        return $message;
    }

    public function testReceivesMailArtifacts()
    {
        $message = $this->getMessage();
        $transport = new InMemory();

        $transport->send($message);

        $this->assertSame($message, $transport->getLastMessage());
    }
}
