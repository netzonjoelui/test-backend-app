<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Mail;

use Netric\EntitySync\Collection\CollectionFactory;
use Netric\ServiceManager;

/**
 * Create a service for receiving mail from a mail server
 */
class ReceiverServiceFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return SenderService
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $user = $sl->getAccount()->getUser();
        $collectionFactory = new CollectionFactory($sl);
        $entitySyncServer = $sl->get("Netric/EntitySync/EntitySync");
        $entityLoader = $sl->get("EntityLoader");
        $groupingsLoader = $sl->get("Netric/EntityGroupings/Loader");
        $log = $sl->get("Log");
        $index = $sl->get("EntityQuery_Index");
        $vaultService = $sl->get("Netric/Crypt/VaultService");
        $config = $sl->get("Config");
        $deliveryService = $sl->get("Netric/Mail/DeliveryService");

        return new ReceiverService(
            $log,
            $user,
            $entitySyncServer,
            $collectionFactory,
            $entityLoader,
            $groupingsLoader,
            $index,
            $vaultService,
            $config->email,
            $deliveryService
        );
    }
}
