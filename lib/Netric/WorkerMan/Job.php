<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\WorkerMan;

/**
 * A class that represents a single job being worked on
 */
class Job
{
    /**
     * The workload of this job
     *
     * @var array
     */
    private $workload = array();

    /**
     * The numarator of the status of the job (how much we have done)
     *
     * @var int
     */
    private $statusNumerator = 0;

    /**
     * The denominator of a job (how much to do total)
     *
     * @var int
     */
    private $statusDenominator = 0;

    /**
     * Set the workload of this job
     *
     * @param array $workload
     */
    public function setWorkload(array $workload)
    {
        $this->workload = $workload;
    }

    /**
     * Get the workload of this job
     *
     * @return array
     */
    public function getWorkload()
    {
        return $this->workload;
    }

    /**
     * Send the status of the current job - % done
     *
     * @param int $numerator
     * @param int $denominator
     */
    public function sendStatus($numerator, $denominator)
    {
        $this->statusNumerator = $numerator;
        $this->statusDenominator = $denominator;
    }

    /**
     * Get the number processed (can be any number the worker sends)
     *
     * @return int
     */
    public function getStatusDenominator()
    {
        return $this->statusDenominator;
    }

    /**
     * Get the total number to process (can be any number the worker sends)
     *
     * @return int
     */
    public function getStatusNumerator()
    {
        return $this->statusDenominator;
    }
}