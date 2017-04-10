<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Permissions;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\Entity\ObjType\UserEntity;

/**
 * Identity mapper for DACLs to make sure we are only loading each one once
 */
class DaclLoader 
{
	/**
	 * Entity loader to get parent entities
	 *
	 * @var EntityLoader
	 */
	private $entityLoader = null;
    
    /**
     * Class constructor
     * 
     * @param EntityLoader $entityLoader The loader for the entity
     */
    public function __construct(EntityLoader $entityLoader)
    {
		$this->entityLoader = $entityLoader;
    }

	/**
	 * Get a DACL for an entity
	 *
	 * 1. Check if the entity has its own dacl
	 * 2. Check to see if the entity has a parent which has a dacl (recurrsive)
	 * 3. If there is no parent dacl, then use the dacl for the object type
	 *
	 * @param EntityInterface $entity
     * @param bool $fallBackToObjType If true and no entity dacl is found get dacl for all objects of that type
     * @return Dacl Access control list
	 */
	public function getForEntity(EntityInterface $entity, $fallBackToObjType = true)
	{
        $daclData = $entity->getValue("dacl");
        if (!empty($daclData)) {
            $decoded = json_decode($daclData, true);
            if ($decoded !== false) {
                return new Dacl($decoded);
            }
        }

        // Check to see if the entity type has a parent
        $objDef = $entity->getDefinition();
        if ($objDef->parentField) {
            $fieldDef = $objDef->getField($objDef->parentField);
            if ($entity->getValue($objDef->parentField) && $fieldDef->subtype) {
                $parentEntity = $this->entityLoader->get($fieldDef->subtype, $entity->getValue($objDef->parentField));
                if ($parentEntity) {
                    $dacl = $this->getForEntity($parentEntity, false);
                    if ($dacl) {
                        return $dacl;
                    }
                }
            }
        }

        // Now try to get DACL for obj type
        if ($fallBackToObjType) {

            // Try to get for from the object definition if permissions have been customized
            if ($objDef->getDacl()) {
                return $objDef->getDacl();
            }

            // If none is found, return a default where admin and creator owner has access only
            $default = new Dacl();
            $default->allowGroup(UserEntity::GROUP_ADMINISTRATORS, Dacl::PERM_FULL);
            $default->allowGroup(UserEntity::GROUP_CREATOROWNER, Dacl::PERM_FULL);
            return $default;
        }

        return null;
	}

    /**
	 * Get an access controll list by name
	 * 
	 * @param string $key The name of the list to pull
	 * @return Dacl
	 */
	public function byName($key, $cache=true)
	{
        /* Old code... should now get from $this->dm
		$key = $this->dbh->dbname . "/" . $key;

		if (isset($this->dacls[$key]) && $cache)
			return $this->dacls[$key];

		// Not yet loaded, create then store
		if ($cache)
		{
			$this->dacls[$key] = new Dacl($this->dbh, $key);
			return $this->dacls[$key];
		}
		else
		{
			$dacl = new Dacl($this->dbh, $key);
			return $dacl;
		}
         */
	}
}
