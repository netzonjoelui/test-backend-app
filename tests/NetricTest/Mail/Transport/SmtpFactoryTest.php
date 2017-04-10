<?php
/**
 * Test the Smtp service factory
 */
namespace NetricTest\Mail\Transport;

use Netric\Mail\Transport\SmtpFactory;
use PHPUnit_Framework_TestCase;

class SmtpFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Save old settings so we can revert after the test
     *
     * We are doing this because the factory can return different
     * transport options if the account has manual smtp settings
     *
     * @var array
     */
    private $oldSettings = array();

    protected function setUp()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $settings = $account->getServiceManager()->get("Netric/Settings/Settings");
        $this->oldSettings = array(
            'smtp_host' => $settings->get("email/smtp_host"),
            'smtp_user' => $settings->get("email/smtp_user"),
            'smtp_password' => $settings->get("email/smtp_password"),
            'smtp_port' => $settings->get("email/smtp_port"),
        );
    }

    protected function tearDown()
    {
        // Restore cached old settings
        $account = \NetricTest\Bootstrap::getAccount();
        $settings = $account->getServiceManager()->get("Netric/Settings/Settings");
        $settings->set("email/smtp_host", $this->oldSettings['smtp_host']);
        $settings->set("email/smtp_user", $this->oldSettings['smtp_user']);
        $settings->set("email/smtp_password", $this->oldSettings['smtp_password']);
        $settings->set("email/smtp_port", $this->oldSettings['smtp_port']);
    }

    public function testCreateService()
    {
        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $this->assertInstanceOf(
            'Netric\Mail\Transport\Smtp',
            $sm->get('Netric\Mail\Transport\Smtp')
        );
    }

    public function testCreateServiceWithSettings()
    {
        $testHost = 'mail.limited.ltd';
        $testPort = 33;
        $testUser = 'testuser';
        $testPassword = 'password';

        $account = \NetricTest\Bootstrap::getAccount();
        $sm = $account->getServiceManager();
        $settings = $sm->get("Netric/Settings/Settings");
        $settings->set('email/smtp_host', $testHost);
        $settings->set('email/smtp_port', $testPort);
        $settings->set('email/smtp_user', $testUser);
        $settings->set('email/smtp_password', $testPassword);

        $smtpFactory = new SmtpFactory();
        $transport = $smtpFactory->createService($sm);

        $this->assertInstanceOf(
            'Netric\Mail\Transport\Smtp',
            $transport
        );

        $options = $transport->getOptions();
        $this->assertEquals($testHost, $options->getHost());
        $this->assertEquals($testPort, $options->getPort());
        $this->assertEquals('login', $options->getConnectionClass());
        $this->assertEquals(
            array('username'=>$testUser, 'password'=>$testPassword),
            $options->getConnectionConfig()
        );

    }
}