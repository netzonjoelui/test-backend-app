<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Worker;

use Netric\WorkerMan\Job;
use Netric\WorkerMan\AbstractWorker;

/**
 * This worker is used to test the WorkerMan
 */
class TestWorker extends AbstractWorker
{
    /**
     * Cache the result
     *
     * @var string
     */
    private $result = "";

    /**
     * Take a string and reverse it
     *
     * @param Job $job
     * @return mixed The reversed string
     */
    public function work(Job $job)
    {
        $workload = $job->getWorkload();

        // Example of getting the current working application
        $application = $this->getApplication();

        // Example of failing with an exception
        if (!$workload['mystring']) {
            throw new \RuntimeException("TestWorker requires 'mystring' be set in the workload params");
        }

        // Reverse the string
        $this->result = strrev($workload['mystring']);

        return $this->result;
    }

    /**
     * Get the results of the last job
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
