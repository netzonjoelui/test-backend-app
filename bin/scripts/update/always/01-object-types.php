<?php
/**
 * Add system types to the database
 */
use Netric\EntityDefinition;

// Get object types for each account
$types = require("data/account/object-types.php");

$account = $this->getAccount();
if (!$account)
    throw new \RuntimeException("This must be run only against a single account");

$entityDefinitionDataMapper = $account->getServiceManager()->get("EntityDefinition_DataMapper");

// Loop through each type and add it if it does not exist
foreach ($types as $objDefData)
{
    // First try loading to see if it already exists
    try {
        $existing = $entityDefinitionDataMapper->fetchByName($objDefData['obj_type']);
    } catch (\Exception $ex) {
        // If it fails, then we need to add it here
        $def = new EntityDefinition($objDefData['obj_type']);
        $def->fromArray($objDefData);
        $entityDefinitionDataMapper->save($def);
        if (!$def->getId()) {
            throw new \RuntimeException("Could not save " . $entityDefinitionDataMapper->getLastError());
        }
    }
}
