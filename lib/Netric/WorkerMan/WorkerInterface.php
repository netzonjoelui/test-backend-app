<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\WorkerMan;

/**
 * Interface for all workers
 */
interface WorkerInterface
{
    /**
     * Every worker requires a work function that takes as a param the job
     *
     * @param Job $job
     * @return mixed Anything the worker is supposed to do with the job
     */
    public function work(Job $job);
}
