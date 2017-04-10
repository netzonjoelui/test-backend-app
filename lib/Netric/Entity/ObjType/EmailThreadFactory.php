<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager;
use Netric\Entity;

/**
 * Create a new email thread entity
 */
class EmailThreadFactory implements Entity\EntityFactoryInterface
{
    /**
     * Entity creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return new Entity\EntityInterface object
     */
    public static function create(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $def = $sl->get("Netric/EntityDefinitionLoader")->get("email_thread");
        $entityLoader = $sl->get("Netric/EntityLoader");
        $entityQueryIndex = $sl->get("Netric/EntityQuery/Index/Index");
        return new EmailThreadEntity($def, $entityLoader, $entityQueryIndex);
    }
}
