<?php
namespace NetricTest\WorkerMan\Queue;

use Netric\WorkerMan\Queue\Gearman;
use Netric\WorkerMan\Queue\QueueInterface;
use Netric\Worker\TestWorker;

class GearmanTest extends AbstractQueueTests
{
    /**
     * Construct a job queue
     *
     * @return QueueInterface
     */
    protected function getQueue()
    {
        $config = $this->account->getServiceManager()->get('Netric\Config\Config');
        $queue = new Gearman($config->workers->server);
        return $queue;
    }
}
