<?php
/**
 * Controller for account interactoin
 */
namespace Netric\Controller;

use \Netric\Mvc;

class AccountController extends Mvc\AbstractAccountController
{
    /**
     * Get the definition of an account
     */
    public function getGetAction()
    {
        // Get the service manager of the current user
        $serviceManager = $this->account->getServiceManager();

        // Load the Module Service
        $moduleService = $serviceManager->get("Netric/Account/Module/ModuleService");

        // Get the current user
        $user = $this->account->getUser();

        // Get the modules specific for the current user
        $userModules = $moduleService->getForUser($user);

        $modules = array();

        // Loop thru each module for the current user
        foreach ($userModules as $module)
        {
            /*
             * We will only get the module that has xml navigation
             *  since the xml navigation will be used as the navigation link in the frontend
             */
            if ($module->getNavigation())
            {

                // Convert the Module object into an array
                $modules[] = $module->toArray();
            }
        }

        // Setup the return details
        $ret = array(
            "id" => $this->account->getId(),
            "name" => $this->account->getName(),
            "orgName" => "", // TODO: $this->account->get
            "defaultModule" => "notes", // TODO: this should be home until it is configurable
            "modules" => $modules
        );

        return $this->sendOutput($ret);
    }

    /**
     * Just in case they use POST
     */
    public function postGetAction()
    {
        return $this->getGetAction();
    }
}
