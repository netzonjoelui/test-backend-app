<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Mail;

use Netric\ServiceManager;

/**
 * Create a service for sending email
 */
class SenderServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return SenderService
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $transport = $sl->get("Netric/Mail/Transport/Transport");
        $bulkTransport = $sl->get("Netric/Mail/Transport/BulkTransport");
        $log = $sl->get("Log");
        return new SenderService($transport, $bulkTransport, $log);
    }
}
