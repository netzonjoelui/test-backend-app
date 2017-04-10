<?php
/**
 * This is just a simple test controller
 */
namespace Netric\Controller;

use \Netric\Mvc;

class TestController extends Mvc\AbstractAccountController
{
	/**
	 * For public tests
	 */
	public function getTestAction()
	{
        return $this->sendOutput("test");
	}

	public function postTestAction()
	{
		$rawBody = $this->getRequest()->getBody();
		return $this->sendOutput(json_decode($rawBody, true));
	}

	/**
	 * For console requests
	 */
	public function consoleTestAction()
	{
		return $this->getTestAction();
	}
}
