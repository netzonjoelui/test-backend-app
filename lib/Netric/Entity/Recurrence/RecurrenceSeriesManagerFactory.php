<?php
/**
 * Service factory for the entity series manager
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\Recurrence;

use Netric\ServiceManager;

/**
 * Create a new Recurring Entity Series Writer service
 */
class RecurrenceSeriesManagerFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return EntitySeriesWriter
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $recurIdentityMapper = $sl->get("Netric/Entity/Recurrence/RecurrenceIdentityMapper");
        $entityLoader = $sl->get("Netric/EntityLoader");
        $entityDataMapper = $sl->get("Netric/Entity/DataMapper/DataMapper");
        $entityIndex = $sl->get("Netric/EntityQuery/Index/Index");
        $entityDefinitionLoader = $sl->get("Netric/EntityDefinitionLoader");
        return new RecurrenceSeriesManager(
            $recurIdentityMapper,
            $entityLoader,
            $entityDataMapper,
            $entityIndex,
            $entityDefinitionLoader
        );
    }
}
