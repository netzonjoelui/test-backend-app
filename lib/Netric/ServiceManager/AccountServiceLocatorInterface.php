<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\ServiceManager;

/**
 * Service factories are classes that handle the construction of complex/cumbersome services
 */
interface AccountServiceLocatorInterface extends ServiceFactoryInterface
{
	/**
	 * Service creation factory for account specific services
	 *
	 * @param AccountServiceManagerInterface $sl ServiceLocator for injecting dependencies
	 * @return mixed Initialized service object
	 */
	public function createService(AccountServiceManagerInterface $sl);
}
