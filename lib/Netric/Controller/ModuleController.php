<?php
/**
 * Controller for account interactoin
 */
namespace Netric\Controller;

use \Netric\Mvc;

class ModuleController extends Mvc\AbstractAccountController
{
	/**
	 * Get the definition of an account
	 */
	public function getGetAction()
	{
		$params = $this->getRequest()->getParams();

		if (!isset($params['moduleName'])) {
			return $this->sendOutput(['error'=>"moduleName is a required query param"]);
		}

		// Get the service manager of the current user
        $serviceManager = $this->account->getServiceManager();

        // Load the Module Service
        $moduleService = $serviceManager->get("Netric/Account/Module/ModuleService");

        $module = $moduleService->getByName($params['moduleName']);

		return $this->sendOutput($module->toArray());
	}
}
