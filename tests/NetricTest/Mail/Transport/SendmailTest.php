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
use Netric\Mail\Transport\Sendmail;

/**
 * @group      Netric_Mail
 */
class SendmailTest extends \PHPUnit_Framework_TestCase
{
    public $transport;
    public $to;
    public $subject;
    public $message;
    public $additional_headers;
    public $additional_parameters;

    public function setUp()
    {
        $this->transport = new Sendmail();
        $this->transport->setCallable(function ($to, $subject, $message, $additional_headers, $additional_parameters = null) {
            $this->to                    = $to;
            $this->subject               = $subject;
            $this->message               = $message;
            $this->additional_headers    = $additional_headers;
            $this->additional_parameters = $additional_parameters;
        });
        $this->operating_system      = strtoupper(substr(PHP_OS, 0, 3));
    }

    public function tearDown()
    {
        $this->to                    = null;
        $this->subject               = null;
        $this->message               = null;
        $this->additional_headers    = null;
        $this->additional_parameters = null;
    }

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

    public function testReceivesMailArtifactsOnUnixSystems()
    {
        if ($this->operating_system == 'WIN') {
            $this->markTestSkipped('This test is *nix-specific');
        }

        $message = $this->getMessage();
        $this->transport->setParameters('-R hdrs');

        $this->transport->send($message);
        $this->assertEquals('Netric DevTeam <devteam@netric.com>', $this->to);
        $this->assertEquals('Testing Netric\Mail\Transport\Sendmail', $this->subject);
        $this->assertEquals('This is only a test.', trim($this->message));
        $this->assertNotContains("To: Netric DevTeam <devteam@netric.com>\n", $this->additional_headers);
        $this->assertContains("Cc: matthew@zend.com\n", $this->additional_headers);
        $this->assertContains("Bcc: \"CR-Team, ZF Project\" <zf-crteam@lists.zend.com>\n", $this->additional_headers);
        $this->assertContains("From: devteam@netric.com,\n Matthew <matthew@zend.com>\n", $this->additional_headers);
        $this->assertContains("X-Foo-Bar: Matthew\n", $this->additional_headers);
        $this->assertContains("Sender: Ralph Schindler <ralph.schindler@zend.com>\n", $this->additional_headers);
        $this->assertEquals('-R hdrs -fralph.schindler@zend.com', $this->additional_parameters);
    }

    public function testReceivesMailArtifactsOnWindowsSystems()
    {
        if ($this->operating_system != 'WIN') {
            $this->markTestSkipped('This test is Windows-specific');
        }

        $message = $this->getMessage();

        $this->transport->send($message);
        $this->assertEquals('devteam@netric.com', $this->to);
        $this->assertEquals('Testing Netric\Mail\Transport\Sendmail', $this->subject);
        $this->assertEquals('This is only a test.', trim($this->message));
        $this->assertContains("To: Netric DevTeam <devteam@netric.com>\r\n", $this->additional_headers);
        $this->assertContains("Cc: matthew@zend.com\r\n", $this->additional_headers);
        $this->assertContains("Bcc: \"CR-Team, ZF Project\" <zf-crteam@lists.zend.com>\r\n", $this->additional_headers);
        $this->assertContains("From: devteam@netric.com,\r\n Matthew <matthew@zend.com>\r\n", $this->additional_headers);
        $this->assertContains("X-Foo-Bar: Matthew\r\n", $this->additional_headers);
        $this->assertContains("Sender: Ralph Schindler <ralph.schindler@zend.com>\r\n", $this->additional_headers);
        $this->assertNull($this->additional_parameters);
    }

    public function testLinesStartingWithFullStopsArePreparedProperlyForWindows()
    {
        if ($this->operating_system != 'WIN') {
            $this->markTestSkipped('This test is Windows-specific');
        }

        $message = $this->getMessage();
        $message->setBody("This is the first line.\n. This is the second");
        $this->transport->send($message);
        $this->assertContains("line.\n.. This", trim($this->message));
    }

    public function testAssertSubjectEncoded()
    {
        $message = $this->getMessage();
        $message->setEncoding('UTF-8');
        $this->transport->send($message);
        $this->assertEquals('=?UTF-8?Q?Testing=20Netric\Mail\Transport\Sendmail?=', $this->subject);
    }
}
