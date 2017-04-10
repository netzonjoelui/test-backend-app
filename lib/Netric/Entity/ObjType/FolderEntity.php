<?php
/**
 * Provide user extensions to base Entity class
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Entity\ObjType;

use Netric\ServiceManager;
use Netric\EntityDefinition;
use Netric\EntityLoader;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Folder for entity
 */
class FolderEntity extends Entity implements EntityInterface
{
    /**
     * Entity loader for getting files and folders by id
     *
     * @var EntityLoader
     */
    private $entityLoader = null;

    /**
     * @param EntityDefinition $def
     * @param EntityLoader $entityLoader
     */
    public function __construct(EntityDefinition $def, EntityLoader $entityLoader)
    {
        $this->entityLoader = $entityLoader;

        parent::__construct($def);
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(ServiceManager\AccountServiceManagerInterface $sm)
    {
        // Check to see if they are trying to delete a system directory - should never happen
        if ($this->getValue("f_system") === true && $this->getValue("f_deleted") === true) {
            throw new \RuntimeException("A system folder cannot be deleted: " . $this->getFullPath());
        }
    }

    /**
     * Callback function used for derrived subclasses
     *
     * @param ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onAfterSave(ServiceManager\AccountServiceManagerInterface $sm)
    {
    }

    /**
     * Checks before a hard delete
     *
     * @param ServiceManager\AccountServiceManagerInterface $sm
     */
    public function onBeforeDeleteHard(ServiceManager\AccountServiceManagerInterface $sm)
    {
        if ($this->getValue("f_system") === true) {
            throw new \RuntimeException("A system folder cannot be deleted: " . $this->getFullPath());
        }
    }

    /**
     * Get the full path for this folder relative to the root
     */
    public function getFullPath()
    {
        $path = $this->getValue("name");

        // If we have no parent then we are the root (or should be)
        if (!$this->getValue("parent_id") && $path === '/')
        {
            return $path;
        }
        else if (!$this->getValue("parent_id"))
        {
            // This condition should never happen, but just in case
            // TODO: throw exception?
            return false;
        }

        $parentFolder = $this->entityLoader->get("folder", $this->getValue("parent_id"));
        $pre = $parentFolder->getFullPath();

        // If our parent is the root, then just absolute path to root and avoid returing '//"
        if ($pre === '/')
        {
            return "/" . $path;
        }
        else
        {
            return $pre . "/" . $path;
        }
    }

    /**
     * Move a folder to a new parent folder
     *
     * @param Folder $newParentFolder The folder to move this folder to
     * @return bool true on sucess, false on failure
     */
    public function move(Folder $newParentFolder)
    {
        if (!$newParentFolder->getId())
        {
            // TODO: Maybe throw exception since this should probably never happen?
            return false;
        }

        $this->setValue("parent_id", $newParentFolder->getId());
        return true;
    }
}
