<?php
/**
 * Email Account entity extension
 *
 * @author Marl Tumulak <marl.tumulak@aereus.com>
 * @copyright 2016 Aereus
 */

namespace Netric\Entity\ObjType;

use Netric\Crypt\BlockCipher;
use Netric\Entity\Entity;
use Netric\Entity\EntityInterface;

/**
 * Activty entity used for logging activity logs
 */
class EmailAccountEntity extends Entity implements EntityInterface
{
    /**
     * Callback function used for derrived subclasses
     *
     * @param \Netric\ServiceManager\AccountServiceManagerInterface $sm Service manager used to load supporting services
     */
    public function onBeforeSave(\Netric\ServiceManager\AccountServiceManagerInterface $sm)
    {
        // If the password was updated for this user then encrypt it
        if ($this->fieldValueChanged("password"))
        {
            $vaultService = $sm->get("Netric/Crypt/VaultService");
            $blockCipher = new BlockCipher($vaultService->getSecret("EntityEnc"));
            $this->setValue("password", $blockCipher->encrypt($this->getValue("password")));
        }
    }
}
