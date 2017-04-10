<?php
/**
 * Return the identity mapper service for recurrence patterns
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\Recurrence;

use Netric\ServiceManager;

/**
 * Create a new recurrence indentity mapper service
 */
class RecurrenceIdentityMapperFactory implements ServiceManager\AccountServiceLocatorInterface
{
    /**
     * Service creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return RecurrenceIdentityMapper
     */
    public function createService(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $recurDataMapper = $sl->get("Netric/Entity/Recurrence/RecurrenceDataMapper");
        return new RecurrenceIdentityMapper($recurDataMapper);
    }
}
