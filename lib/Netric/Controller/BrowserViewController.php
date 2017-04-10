<?php
/**
 * Controller for handling Browser View
 */
namespace Netric\Controller;

use Netric\Mvc;
use Netric\Entity\BrowserView\BrowserView;

class BrowserViewController extends Mvc\AbstractAccountController
{
    /**
     * Save a browser view
     */
    public function postSaveAction()
    {
        $rawBody = $this->getRequest()->getBody();

        $ret = array();
        if (!$rawBody)
        {
            return $this->sendOutput(array("error"=>"Request input is not valid"));
        }

        // Decode the json structure
        $objData = json_decode($rawBody, true);

        if (!isset($objData['obj_type']))
        {
            return $this->sendOutput(array("error" => "obj_type is a required param"));
        }

        $serviceManager = $this->account->getServiceManager();
        $browserViewService = $serviceManager->get("Netric/Entity/BrowserView/BrowserViewService");

        $view = new BrowserView();
        $view->fromArray($objData);

        $result = $browserViewService->saveView($view);

        if(!$view->isSystem() && $view->isDefault())
        {
            $browserViewService->setDefaultViewForUser($view->getObjType(), $this->account->getUser(), $result);
        }

        return $this->sendOutput($result);
    }

    public function postSetDefaultViewAction ()
    {
        $rawBody = $this->getRequest()->getBody();

        $ret = array();
        if (!$rawBody)
        {
            return $this->sendOutput(array("error"=>"Request input is not valid"));
        }

        // Decode the json structure
        $objData = json_decode($rawBody, true);

        if (!isset($objData['obj_type']))
        {
            return $this->sendOutput(array("error" => "obj_type is a required param"));
        }

        $serviceManager = $this->account->getServiceManager();
        $browserViewService = $serviceManager->get("Netric/Entity/BrowserView/BrowserViewService");

        $view = new BrowserView();
        $view->fromArray($objData);

        if (!$view->getId())
        {
            return $this->sendOutput(array("error" => "Browser View should be saved first before setting as the default view."));
        }

        $browserViewService->setDefaultViewForUser($view->getObjType(), $this->account->getUser(), $view->getId());

        return $this->sendOutput($view->getId());
    }

    /**
     * Put a browser view
     */
    public function putSaveAction()
    {
        return $this->postSaveAction();
    }
}