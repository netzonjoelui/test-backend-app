<?php
/**
 * Add default modules to each account
 */

$account = $this->getAccount();
if (!$account)
    throw new \RuntimeException("This must be run only against a single account");

// Get modules from data
$modules = require("data/account/modules.php");

// Get the module service
$serviceLocator = $account->getServiceManager();
$moduleService = $serviceLocator->get("Netric/Account/Module/ModuleService");

foreach ($modules as $moduleData) {
    if (!$moduleService->getByName($moduleData['name'])) {
        $module = new Netric\Account\Module\Module();
        $module->fromArray($moduleData);
        $moduleService->save($module);
    }
}