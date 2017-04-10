<?php
/**
 * Our implementation of a ServiceLocator pattern
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\ServiceManager;

use Netric\Account\Account;
use Netric;

/**
 * Class for constructing, caching, and finding services by name
 */
class AccountServiceManager extends AbstractServiceManager implements AccountServiceManagerInterface
{
    /**
     * Handle to netric account
     *
     * @var Account
     */
    private $account = null;

    /**
     * Class constructor
     *
     * @param Account $account The account we are loading services for
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
        $application = $account->getApplication();
        parent::__construct($application, $application->getServiceManager());
    }

    /**
     * Map a name to a class factory
     *
     * The target will be appended with 'Factory' so
     * "test" => "Netric/ServiceManager/Test/Service",
     * will load
     * Netric/ServiceManager/Test/ServiceFactory
     *
     * Use these sparingly because it does obfuscate from the
     * client what classes are being loaded.
     *
     * @var array
     */
    protected $invokableFactoryMaps = array(
        // Test service map
        "test" => "Netric/ServiceManager/Test/Service",
        // The entity factory service will initialize new entities with injected dependencies
        "EntityFactory" => "Netric/Entity/EntityFactory",
        // The service required for saving recurring patterns
        "RecurrenceDataMapper" => "Netric/Entity/Recurrence/RecurrenceDataMapper",
        // IdentityMapper for loading/saving/caching RecurrencePatterns
        "RecurrenceIdentityMapper" => "Netric/Entity/Recurrence/RecurrenceIdentityMapper",

        "Db" => "Netric/Db/Db",

        "Config" => "Netric/Config/Config",

        "Cache" => "Netric/Cache/Cache",

        "Entity_DataMapper" => "Netric/Entity/DataMapper/DataMapper",

        "EntityDefinition_DataMapper" => "Netric/EntityDefinition/DataMapper/DataMapper",

        "EntityDefinitionLoader" => "Netric/EntityDefinitionLoader",

        "EntityLoader" => "Netric/EntityLoader",

        "EntitySync" => "Netric/EntitySync/EntitySync",

        "EntitySyncCommitManager" => "Netric/EntitySync/Commit/CommitManager",

        "EntitySyncCommit_DataMapper" => "Netric/EntitySync/Commit/DataMapper/DataMapper",

        "EntitySync_DataMapper" => "Netric/EntitySync/DataMapper",

        "EntityGroupings_Loader" => "Netric/EntityGroupings/Loader",

        "Log" => "Netric/Log",

        "EntityQuery_Index" => "Netric/EntityQuery/Index/Index",

        "Entity_RecurrenceDataMapper" => "Netric/Entity/Recurrence/RecurrenceDataMapper",

        "Application_DataMapper" => "Netric/Application/DataMapper"
    );

    /**
     * Get account instance of netric
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Get a service by name
     *
     * @param string $serviceName
     * @return mixed The service object and false on failure
     */
    public function get($serviceName)
    {
        $service = false;

        /*
         * First check to see if we have a local factory function to load the service.
         * This is the legacy way of loading services and this first if clause will
         * eventually go away and just leave the 'else' code below.
         */
        if (method_exists($this, "factory" . $serviceName)) {
            // Return cached version if already loaded
            if ($this->isLoaded($serviceName))
                return $this->loadedServices[$serviceName];

            $service = call_user_func(array($this, "factory" . $serviceName));

            // Cache the service
            if ($service) {
                $this->loadedServices[$serviceName] = $service;
            } else {
                throw new Exception\RuntimeException(sprintf(
                    '%s: A local factory function was found for "%s" but it did not return a valid service.',
                    get_class($this) . '::' . __FUNCTION__,
                    $serviceName
                ));
            }
        } else {
            $service = parent::get($serviceName);
        }

        return $service;
    }

    /*
     * TODO: All of the below factories need to be moved to actual factory classes
     * ===========================================================================
     */

    /**
     * Get DACL loader for security
     *
     * @return DaclLoader
     */
    private function factoryDaclLoader()
    {
        return DaclLoader::getInstance($this->ant->dbh);
    }

    /**
     * Get AntFs class
     *
     * @deprecated This is legacy code used only for the entity datamapper at this point
     *
     * @return \AntFs
     */
    private function factoryAntFs()
    {
        require_once(dirname(__FILE__) . "/../../AntConfig.php");
        require_once(dirname(__FILE__) . "/../../CDatabase.awp");
        require_once(dirname(__FILE__) . "/../../Ant.php");
        require_once(dirname(__FILE__) . "/../../AntUser.php");
        require_once(dirname(__FILE__) . "/../../AntFs.php");

        $ant = new \Ant($this->getAccount()->getId());
        $user = $this->getAccount()->getUser();
        if (!$user)
            $user = $this->getAccount()->getUser(\Netric\UserEntity::USER_ANONYMOUS);
        $user = new \AntUser($ant->dbh, $user->getId(), $ant);
        $antfs = new \AntFs($ant->dbh, $user);

        return $antfs;
    }

    /**
     * Get Help class
     *
     * @return Help
     */
    private function factoryHelp()
    {
        return new Help();
    }
}
