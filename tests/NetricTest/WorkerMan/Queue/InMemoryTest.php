<?php
namespace NetricTest\WorkerMan\Queue;

use Netric\WorkerMan\Queue\InMemory;

class InMemeoryTest extends AbstractQueueTests
{
    /**
     * Construct a job queue
     *
     * @return QueueInterface
     */
    protected function getQueue()
    {
        return new InMemory();
    }
}