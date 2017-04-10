<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\WorkFlow\DataMapper\DataMapperInterface;
use Netric\WorkFlow\WorkFlow;
use Netric\WorkFlow\workFlowInstance;

/**
 * Action used for delaying the execution of child actions
 *
 * Params in the 'data' field:
 *  when_unit       int REQUIRED A time unit from WorkFlow::TIME_UNIT_*
 *  when_interval   int REQUIRED An interval to use with the unit like 1 month or 1 day
 */
class WaitConditionAction extends AbstractAction implements ActionInterface
{
    /**
     * WorkFlow data mapper for getting and setting scheduled actions
     *
     * @var DataMapperInterface
     */
    private $workFlowDataMapper = null;

    /**
     * Set dependencies
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory
     * @param DataMapperInterface $workFlowDataMapper
     */
    public function __construct(EntityLoader $entityLoader, ActionFactory $actionFactory, DataMapperInterface $workFlowDataMapper)
    {
        $this->workFlowDataMapper = $workFlowDataMapper;

        // Should always call the parent constructor for base dependencies
        parent::__construct($entityLoader, $actionFactory);
    }

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        // Get the entity being acted on
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        // Execute now if no interval is set or it's been set to 'execute immediately'
        if (!isset($params['when_unit']) || !isset($params['when_interval']) || $params['when_interval'] === 0)
            return true;

        // We cannot set future actions if we are not running in a workflow instance
        if (!$workflowInstance)
            throw new \RuntimeException("Cannot schedule the action because workFlowInstance was not set");

        // We can only schedule an action that was previously saved
        if (!$this->getId())
            throw new \RuntimeException("Cannot schedule the action because it has not been saved yet");

        /*
         * Now that we know that this action is setup correctly, we can execute the schedule logic.
         * The first thing we will do is find out if we are re-executing on a previously
         * saved action. This is expected when the scheduled task finally launches.
         */
        if ($this->workFlowDataMapper->getScheduledActionTime($workflowInstance->getId(), $this->getId()))
        {
            // Delete the scheduled action since we are now finished processing it.
            $this->workFlowDataMapper->deleteScheduledAction($workflowInstance->getId(), $this->getId());

            // Return true to continue processing children.
            return true;
        }

        /*
         * Determine the execute date from $params.
         * This will eventually be a lot more complex where we can key off of
         * any field in $workFlowInstance->getEntityId() but right now we
         * just schedule everything from the start of the workflow.
         */
        $executeDate = $this->getExecuteDate($params['when_unit'], $params['when_interval']);

        // Schedule the action for later
        $this->workFlowDataMapper->scheduleAction(
            $workflowInstance->getId(),
            $this->getId(),
            $executeDate
        );

        // Do not process children, but set no errors
        return false;
    }

    /**
     * Get the real date this workflow should execute based on params
     *
     * @param int $whenUnit A unit of time from Where::TIME_UNIT_*
     * @param int $whenInterval How many whenUnits to add
     * @return \DateTime
     */
    public function getExecuteDate($whenUnit, $whenInterval)
    {
        $intervalUnit = $this->getDateIntervalUnit($whenUnit);
        /*
         * The unit will return lower case 'm' for minutes, since \DateInterval
         * stupidly uses a preceding 'T' before time intervals but the same character
         * 'M' to represent month as it does minutes. We just have getDateIntervalUnits return
         * a lower case 'm' for minutes, then prepend the 'T' below.
         */
        $prefix = ($intervalUnit === 'H' || $intervalUnit === 'm') ? $pre = "PT" : 'P';

        // Translate our 'm' (lowercase) for minute back to uppercase 'M' for \DateInterval (see above)
        if ($intervalUnit === 'm')
            $intervalUnit = 'M';

        $dateInterval = new \DateInterval($prefix . $whenInterval . $intervalUnit);
        $executeDate = new \DateTime();
        $executeDate->add($dateInterval);
        return $executeDate;
    }

    /**
     * Convert a WorkFlow::TIME_UNIT_* to a DateInterval textual unit
     *
     * @param int $unit A unit id from WorkFlow::TIME_UNIT_*
     * @return string Unit character used for PHP's DateInterval constructor
     * @throws \InvalidArgumentException if we do not recognize the constant being passed
     */
    private function getDateIntervalUnit($unit)
    {
        switch ($unit)
        {
            case WorkFlow::TIME_UNIT_YEAR:
                return 'Y';

            case WorkFlow::TIME_UNIT_MONTH:
                return 'M';

            case WorkFlow::TIME_UNIT_WEEK:
                return 'W';

            case WorkFlow::TIME_UNIT_DAY:
                return 'D';

            case WorkFlow::TIME_UNIT_HOUR:
                return 'H';

            case WorkFlow::TIME_UNIT_MINUTE:
                return 'm';

            default:
                // This should never happen, but if it does throw an exception
                throw new \InvalidArgumentException("No DateTinerval conversion for unit $unit");
        }
    }

}