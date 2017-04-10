<?php
/**
 * Project entity type
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager;
use Netric\Entity;
use Netric\EntityQuery\Index\Pgsql;

/**
 * Create a new project entity
 */
class ProjectFactory implements Entity\EntityFactoryInterface
{
    /**
     * Entity creation factory
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
     * @return new Entity\EntityInterface object
     */
    public static function create(ServiceManager\AccountServiceManagerInterface $sl)
    {
        $def = $sl->get("Netric/EntityDefinitionLoader")->get("project");
        $indexInterface = new Pgsql($sl->getAccount());
        $entityLoader = $sl->get("Netric/EntityLoader");
        return new ProjectEntity($def, $entityLoader, $indexInterface);
    }
}
