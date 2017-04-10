<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\EntityLoader;
use Netric\EntityQuery;
use Netric\WorkFlow\WorkFlowInstance;
use Netric\EntityQuery\Index\IndexInterface;
use Netric\EntityGroupings\Loader as GroupingsLoader;

/**
 * Action to assign an entity to a user
 *
 * Params in the 'data' field:
 *
 *  field       string REQUIRED The name of the user field we are updating.
 *  team_id     int OPTIONAL if set, we will randomize users within this team
 *  group_id    int OPTIONAL if set, we randomize users that are a member of this group
 *  users       string OPTIONAL A comma separated list of user IDs
 *
 * One of the three optional params must be set to determine what users to assign
 */
class AssignAction extends AbstractAction implements ActionInterface
{
    /**
     * Loader for entity groupings
     *
     * @var GroupingsLoader
     */
    private $groupngsLoader = null;

    /**
     * EnityQuery index for querying entities
     *
     * @var IndexInterface
     */
    private $queryIndex = null;

    /**
     * Set all dependencies
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory
     * @param GroupingsLoader $groupingsLoader
     * @param IndexInterface $queryIndex
     */
    public function __construct(
        EntityLoader $entityLoader,
        ActionFactory $actionFactory,
        GroupingsLoader $groupingsLoader,
        IndexInterface $queryIndex
    )
    {
        $this->groupngsLoader = $groupingsLoader;
        $this->queryIndex = $queryIndex;
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
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        /*
         * We used to utilize a round-robin approach to this, but now
         * we are using randomization because it is bad/odd design to
         * have the action update itself. We may end up building some sort
         * of generic queuing system later, but for now random should
         * serve to accomplish what people are looking for.
         */
        if ($params['field'])
        {
            $userId = null;

            if (isset($params['team_id'])) {
                $userId = $this->getNextUserFromTeam($params['team_id']);
            } else if (isset($params['group_id'])) {
                $userId = $this->getNextUserFromGroup($params['group_id']);
            } else if (isset($params['users'])) {
                $userId = $this->getNextUserFromList($params['users']);
            }

            if ($userId !== null) {
                $entity->setValue($params['field'], $userId);
                $this->entityLoader->save($entity);
                return true;
            }
        }

        // Could not assign it
        return false;
    }


    /**
     * Get the next user that is a member of a team
     *
     * @param int $teamId
     * @return int
     */
    private function getNextUserFromTeam($teamId)
    {
        $query = new EntityQuery("user");
        $query->where("team_id")->equals($teamId);
        $result = $this->queryIndex->executeQuery($query);
        $num = $result->getTotalNum();
        $getIndex = mt_rand(0, ($num-1));
        $user = $result->getEntity($getIndex);
        return $user->getId();
    }

    /**
     * Get the next user that is a member of a user group
     *
     * @param int $groupId
     * @return int
     */
    private function getNextUserFromGroup($groupId)
    {
        $query = new EntityQuery("user");
        $query->where("groups")->equals($groupId);
        $result = $this->queryIndex->executeQuery($query);
        $num = $result->getTotalNum();
        $getIndex = mt_rand(0, ($num-1));
        $user = $result->getEntity($getIndex);
        return $user->getId();
    }

    /**
     * Get the next user from a comma separated list
     *
     * @param string $listOfUsers
     * @return int
     */
    private function getNextUserFromList($listOfUsers)
    {
        $users = explode(',', $listOfUsers);

        if (!count($users))
            return null;

        return $users[mt_rand(0,(count($users)-1))];
    }
}
