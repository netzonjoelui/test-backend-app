<?php
/**
 * Service factory for the recurrence datamapper
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\Recurrence;

use Netric\ServiceManager;

/**
 * Create a new Recurring DataMapper service
 */
class RecurrenceDataMapperFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return RecurrenceDataMapper
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $entDefLoader = $sl->get("Netric/EntityDefinitionLoader");
        $dbh = $sl->get("Netric/Db/Db");
        return new RecurrenceDataMapper($sl->getAccount(), $dbh, $entDefLoader);
    }
}
