<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager;
use Netric\Entity;

/**
 * Create a new email entity
 */
class EmailMessageFactory implements Entity\EntityFactoryInterface
{
    /**
     * Entity creation factory
     *
     * @param ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return new Entity\EntityInterface object
     */
    public static function create(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $def = $sl->get("Netric/EntityDefinitionLoader")->get("email_message");
        $entityLoader = $sl->get("Netric/EntityLoader");
        $entityQueryIndex = $sl->get("Netric/EntityQuery/Index/Index");
        $fileSystem = $sl->get("Netric/FileSystem/FileSystem");
        return new EmailMessageEntity($def, $entityLoader, $entityQueryIndex, $fileSystem);
    }
}
