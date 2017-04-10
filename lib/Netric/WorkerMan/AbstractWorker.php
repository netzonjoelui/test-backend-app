<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\WorkerMan;
use Netric\Application\Application;

/**
 * Base worker that every worker ../Worker should extend
 */
abstract class AbstractWorker implements WorkerInterface
{
    /**
     * Cache the result
     *
     * @var Application
     */
    private $application = null;

    /**
     * Setup worker in the context of a current running application
     *
     * If a worker extends the constructor, it MUST call:
     * parent::__construct in order to setup the worker property.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Get the current running application instance
     *
     * @return Application
     */
    protected function getApplication()
    {
        return $this->application;
    }
}
