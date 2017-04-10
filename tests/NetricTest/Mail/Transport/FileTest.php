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
use Netric\Mail\Transport\File;
use Netric\Mail\Transport\FileOptions;

/**
 * @group      Netric_Mail
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->tempDir = sys_get_temp_dir() . '/mail_file_transport';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        } else {
            $this->cleanup($this->tempDir);
        }

        $fileOptions = new FileOptions([
            'path' => $this->tempDir,
        ]);
        $this->transport  = new File($fileOptions);
    }

    public function tearDown()
    {
        $this->cleanup($this->tempDir);
        rmdir($this->tempDir);
    }

    protected function cleanup($dir)
    {
        foreach (glob($dir . '/*.*') as $file) {
            unlink($file);
        }
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

    public function testReceivesMailArtifacts()
    {
        $message = $this->getMessage();
        $this->transport->send($message);

        $this->assertNotNull($this->transport->getLastFile());
        $file = $this->transport->getLastFile();
        $test = file_get_contents($file);

        $this->assertEquals($message->toString(), $test);
    }
}
