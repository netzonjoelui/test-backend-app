<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Transport;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\ServiceManager\AccountServiceLocatorInterface;

/**
 * Create the default SMTP transport
 */
class TransportFactory implements AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param AccountServiceManagerInterface $serviceManager ServiceLocator for injecting dependencies
     * @return TransportInterface
     * @throws Exception\InvalidArgumentException if a transport could not be created
     */
    public function createService(AccountServiceManagerInterface $serviceManager)
    {
        // Get the required method
        $config = $serviceManager->get("Config");
        $transportMode = $config->email['mode'];

        // Create transport variable to set
        $transport = null;

        /*
         * If email is being suppressed via a config param, then return InMemory transport
         * so we do not try to send out emails in a development/test environment.
         */
        if (isset($config->email['supress']))
        {
            return new InMemory();
        }

        // Call the factory to return simple transports
        switch ($transportMode)
        {
            case 'smtp':
                return $serviceManager->get("Netric/Mail/Transport/Smtp");
            case 'in-memory':
                return new InMemory();
            case 'sendmail':
                return new Sendmail();
        }

        throw new Exception\InvalidArgumentException("No transport for method " . $transportMode);
    }
}
