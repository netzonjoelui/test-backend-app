<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\Entity\ObjType;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Member represents a single member on any entity
 */
class MemberEntity extends Entity implements EntityInterface
{

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Called right before the entity is purged (hard delete)
     *
     * @param AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeDeleteHard(AccountServiceManagerInterface $sm)
    {

    }
}
