<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\EntityQuery\Plugin;

use Netric\ServiceManager\AccountServiceManagerInterface;
use Netric\EntityQuery;
use Netric\ServiceManager\ServiceLocatorInterface;
use Netric\WorkerMan\WorkerService;

/**
 * Hook before and after querying an email_thread
 */
class EmailThreadQueryPlugin implements PluginInterface
{
    /**
     * The worker service used to queue jobs
     *
     * @var WorkerService
     */
    private $workerService = null;

    /**
     * Perform an operation before a query is executed
     *
     * @param AccountServiceManagerInterface $sl A service locator for getting dependencies
     * @param EntityQuery $query The query being executed
     * @return bool true on success, false on failure
     */
    public function onBeforeExecuteQuery(AccountServiceManagerInterface $sl, EntityQuery $query)
    {
        // Check to see if the user is querying on a specific mailbox
        $mailboxId = null;
        $wheres = $query->getWheres();
        foreach ($wheres as $where) {
            if ($where->fieldName == "mailbox_id") {
                $mailboxId = $where->value;
            }
        }

        // If user is not querying a mailbox then just exit
        if (!$mailboxId) {
            return true;
        }

        // Setup background job and queue it
        $jobData = array(
            'account_id' => $sl->getAccount()->getId(),
            'user_id' => $sl->getAccount()->getUser()->getId(),
            'mailbox_id' => $mailboxId,
        );
        $workerMan = $this->getWorkerService($sl);

        if ($workerMan->doWorkBackground("EmailMailboxSync", $jobData)) {
            return true;
        } else {
            // Something failed
            $sl->get("Log")->error("EmailTHREADQueryPlugin->onBeforeExecuteQuery: For some reason a job could not be queued");
            return false;
        }
    }

    /**
     * Perform an operation after a query is executed
     *
     * @param AccountServiceManagerInterface $sl A service locator for getting dependencies
     * @param EntityQuery $query The query being executed
     * @return bool true on success, false on failure
     */
    public function onAfterExecuteQuery(AccountServiceManagerInterface $sl, EntityQuery $query)
    {
        // So far we don't really need to do anything after the query
        return true;
    }

    /**
     * Get a workers service for queuing jobs
     *
     * @return WorkerService
     */
    private function getWorkerService(ServiceLocatorInterface $sl)
    {
        if (!$this->workerService) {
            $this->setWorkerService($sl->get("Netric/WorkerMan/WorkerService"));
        }

        return $this->workerService;
    }

    /**
     * Manually set the worker service
     *
     * This is used often for unit tests where we may want a service setup
     * to use in-memory queues for validating the process.
     *
     * @param WorkerService $service
     */
    public function setWorkerService(WorkerService $service)
    {
        $this->workerService = $service;
    }
}
