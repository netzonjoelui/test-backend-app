<?php
/**
 * Common Action functionality
 *
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\WorkFlow\WorkFlow;
use Netric\Entity\EntityInterface;
use Netric\EntityQuery\Where;
use Netric\EntityLoader;
use Netric\WorkFlow\WorkFlowInstance;
use Netric\Error\ErrorAwareInterface;
use Netric\Error\Error;

/**
 * Base class for all actions
 */
abstract class AbstractAction implements ErrorAwareInterface
{
    /**
     * Unique id of this action
     *
     * @var int
     */
    private $id = null;

    /**
     * A human-entered name for this action
     */
    private $name = null;

    /**
     * Action type name
     *
     * All actions must have a getType function that returns a
     * unique type name that correlates to the action class name.
     *
     * For example:
     *  'test' will load Netric\WorkFlow\Action\TestAction.php
     *  'email' will load Netric\WorkFlow\Action\EmailAction.php
     *
     * @var string
     */
    private $type = null;

    /**
     * Id of the workflow this action is a member of
     *
     * @var int
     */
    private $workflowId = null;

    /**
     * Actions can be children of other actions
     *
     * @var int
     */
    private $parentActionId = null;

    /**
     * Parent action id event
     *
     * Child actions can be limited to only fire after a certain event provided by the parent
     *
     * @var string
     */
    public $parentActionEvent = null;

    /**
     * Child actions
     *
     * @var ActionInterface[]
     */
    private $childActions = array();

    /**
     * Child actions that have been removed and a queued for deletion on save
     *
     * @var ActionInterface[]
     */
    private $removedChildActions = array();

    /**
     * Param data for this activity
     *
     * All activities have parameter data for performing the
     * action. Most of this is user-entered params during creation
     * such as who to send emails to, which templates to ues, etc...
     *
     * @var array
     */
    private $params = array();

    /**
     * Entity loader
     *
     * @var EntityLoader
     */
    protected $entityLoader = null;

    /**
     * Action factory
     *
     * @var ActionFactory
     */
    protected $actionFactory = null;

    /**
     * Array of errors for ErrorAwareInterface
     *
     * @var Error[]
     */
    protected $errors = array();

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workFlowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    abstract public function execute(WorkFlowInstance $workFlowInstance);

    /**
     * This must be called by all derived classes, or $entityLoader should be set in their constructor
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory For constructing child actions
     */
    public function __construct(EntityLoader $entityLoader, ActionFactory $actionFactory)
    {
        $this->entityLoader = $entityLoader;
        $this->actionFactory = $actionFactory;

        // Set the 'type' for this action
        $this->type = $this->getTypeName();
    }

    /**
     * Load from a data array
     *
     * @param array $data
     */
    public function fromArray(array $data)
    {
        if (isset($data['id']) && is_numeric($data['id']))
            $this->id = $data['id'];

        if (isset($data['name']))
            $this->name = $data['name'];

        if (isset($data['type']))
            $this->type = $data['type'];

        if (isset($data['workflow_id']) && is_numeric($data['workflow_id']))
            $this->workflowId = $data['workflow_id'];

        if (isset($data['parent_action_id']) && is_numeric($data['parent_action_id']))
            $this->parentActionId = $data['parent_action_id'];

        if (isset($data['params']))
        {
            foreach ($data['params'] as $pname=>$pval)
                $this->setParam($pname, $pval);
        }

        // Load child actions
        if (isset($data['actions']) && is_array($data['actions']))
        {
            /*
             * Queue current actions for deletion since we are setting all actions
             * and not just adding actions it is assumed anything missing from $data['actions']
             * has been deleted since the last save.
             *
             * When $this->addAction is called below it will remove it from the removedActions
             * queue to keep it from being deleted on the next save
             */
            foreach ($this->childActions as $actionToRemove)
            {
                $this->removeAction($actionToRemove);
            }

            // Now add to child actions array
            foreach ($data['actions'] as $childActionData)
            {
                if (!isset($childActionData['type']))
                    throw new \RuntimeException("Invalid action data: " . var_export($childActionData, true));

                $childAction = $this->actionFactory->create($childActionData['type']);
                $childAction->fromArray($childActionData);
                $this->addAction($childAction);
            }
        }
    }

    /**
     * Convert this action into an associative array
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            "id" => $this->id,
            "name" => $this->name,
            "type" => $this->type,
            "workflow_id" => $this->workflowId,
            "parent_action_id" => $this->parentActionId,
            "params" => $this->params,
        );

        // Set child actions
        $data['actions'] = array();
        foreach ($this->childActions as $childAction)
        {
            $data['actions'][] = $childAction->toArray();
        }

        return $data;
    }

    /**
     * Get the id of this action
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the id of this action
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Remove a child action
     *
     * @param ActionInterface $action The action to remove
     * @returns bool true if removed, false if not found
     */
    public function removeAction(ActionInterface $action)
    {
        for ($i = 0; $i < count($this->childActions); $i++)
        {
            if ($action === $this->childActions[$i] ||
                ($action->getId() != null && $action->getId() === $this->childActions[$i]->getId()))
            {
                array_splice($this->childActions, $i, 1);

                // If previously saved then queue it to be purged on save
                if ($action->getId())
                {
                    $this->removedChildActions[] = $action;
                }

                return true;
            }
        }

        // Not found so nothing to remove
        return true;
    }

    /**
     * Add a child action
     *
     * @param ActionInterface $actionToAdd
     * @throws \InvalidArgumentException If a use tries to add itself as a child
     */
    public function addAction(ActionInterface $actionToAdd)
    {
        // Make sure an action never adds itself or any of the children add itself
        if ($this->childActionIsCircular($actionToAdd))
        {
            throw new Exception\CircularChildActionsException(
                "One of the children of the actions is this action which is a bad circular reference"
            );
        }

        // First make sure we didn't previously remove this action
        for ($i = 0; $i < count($this->removedChildActions); $i++)
        {
            if ($actionToAdd === $this->removedChildActions[$i] || (
                    $actionToAdd->getId() != null
                    && $actionToAdd->getId() === $this->removedChildActions[$i]->getId()
                )
            )
            {
                // Remove it from deletion queue, apparently the user didn't mean to delete it
                array_splice($this->removedChildActions, $i, 1);
            }
        }

        // Check if previously added
        $previouslyAddedAt = -1;
        for ($i = 0; $i < count($this->childActions); $i++)
        {
            if ($actionToAdd->getId() &&
                $this->childActions[$i]->getId() === $actionToAdd->getId())
            {
                $previouslyAddedAt = $i;
                break;
            }
        }

        // If this action was not previously added then push the new action, otherwise replace
        if ($previouslyAddedAt === -1)
            $this->childActions[] = $actionToAdd;
        else
            $this->childActions[$previouslyAddedAt] = $actionToAdd;
    }

    /**
     * Get child actions to remove
     *
     * @return ActionInterface[]
     */
    public function getRemovedActions()
    {
        return $this->removedChildActions;
    }

    /**
     * Get child actions array
     *
     * @return ActionInterface[]
     */
    public function getActions()
    {
        return $this->childActions;
    }

    /**
     * Set param
     *
     * @param string $name The unique name of the param to set
     * @param string $value The value of the given param
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Get a param by name
     *
     * @param string $name The unique name of the param to get
     * @param EntityInterface $mergeWithEntity Optional entity to merge variables with
     * @return string
     */
    public function getParam($name, EntityInterface $mergeWithEntity = null)
    {
        $paramValue = (isset($this->params[$name])) ? $this->params[$name] : null;

        // Check if we should merge variables before returning
        if ($mergeWithEntity && $paramValue)
        {
            $paramValue = $this->replaceParamVariables($mergeWithEntity, $paramValue);
        }

        return $paramValue;
    }

    /**
     * Get param data for this activity
     *
     * All activities have parameter data for performing the
     * action. Most of this is user-entered params during creation
     * such as who to send emails to, which templates to ues, etc...
     *
     * @param EntityInterface $mergeWithEntity The entity we are acting on and to get values from
     * @return array
     */
    protected function getParams(EntityInterface $mergeWithEntity = null)
    {
        // If no merge entity has been passed, then return the unmerged raw values for params
        if ($mergeWithEntity === null)
        {
            return $this->params;
        }

        // Copy pre-processed params into $data array so we can merge variables with $mergeWithEntity
        $data = $this->params;

        foreach ($data as $paramName=>$paramValue)
        {
            /*
             * Begin legacy hack:
             * This is legacy where some workflows would set these fields for sending email
             * but just provide a field with a user id and not the .email attribute appended
             */
            if (!is_array($paramValue) && ($paramName === 'to' || $paramName === 'cc' || $paramName === 'bcc'))
            {
                $matches = array();
                preg_match_all("/<%(.*?)%>/", $paramValue, $matches);
                // Above sets $matches to (array(array('matches'), array('variable_names'))
                if (isset($matches[1]))
                {
                    foreach ($matches[1] as $fieldName)
                    {
                        $field = $mergeWithEntity->getDefinition()->getField($fieldName);
                        if ($field && $field->type === 'object' && $field->subtype === 'user')
                        {
                            // Dereference email from user object
                            $paramValue = str_replace("<%$fieldName%>", "<%$fieldName.email%>", $paramValue);
                        }
                    }
                }
            }
            /*
             * End legacy hack:
             */

            if (is_array($paramValue))
            {
                foreach ($paramValue as $valueIndex=>$subValue)
                {
                    $data[$paramName][$valueIndex] = $this->replaceParamVariables($mergeWithEntity, $subValue);
                }
            }
            else
            {
                $data[$paramName] = $this->replaceParamVariables($mergeWithEntity, $paramValue);
            }
        }

        return $data;
    }

    /**
     * Replace any variables in a value either from a macro like entity_link or the entity value
     *
     * @param EntityInterface $mergeWithEntity The entity we are acting on to get values from
     * @param string $value The precompiled string that can contain <%varname%> merge variables
     * @return string
     * @throws \RuntimeException if we end up in an infinite loop for any reason
     */
    protected function replaceParamVariables(EntityInterface $mergeWithEntity, $value)
    {
        // Only check strings
        if (!is_string($value))
            return $value;

        // Keep track of iterations to protect against infinite loops
        $iterations = 0;

        // Buffer for matches
        $matches = array();

        while (preg_match("/<%(.*?)%>/", $value, $matches))
        {
            $variableName = $matches[1];

            switch ($variableName)
            {
                case 'entity_link':
                case 'object_link':

                    /*
                     * Create a link to the entity in question
                     */
                    $baseUrl = $this->getAccountBaseUrl();
                    $objType = $mergeWithEntity->getDefinition()->getObjType();
                    $value = str_replace(
                        "<%$variableName%>",
                        $baseUrl . "/obj/" . $objType . '/' . $mergeWithEntity->getId(),
                        $value
                    );

                    break;

                // Legacy before we used <%id%>
                case 'oid':
                    $fieldValue = $mergeWithEntity->getId();
                    $value = str_replace("<%$variableName%>", $fieldValue, $value);
                    break;

                case 'obj_type':
                    $value = str_replace("<%$variableName%>", $mergeWithEntity->getDefinition()->getObjType(), $value);
                    break;

                default:

                    /*
                     * Entity field value
                     */
                    $fieldValue = $this->getParamVariableFieldValue($mergeWithEntity, $variableName);
                    $value = str_replace("<%$variableName%>", $fieldValue, $value);

                    break;
            }

            // Prevent infinite loop
            $iterations++;
            if ($iterations > 5000)
            {
                throw new \RuntimeException("Too many iterations");
            }
        }

        return $value;
    }

    /**
     * Get the actual value of an entity field
     *
     * The field could be cross-entity with dot '.' notation like
     * user.manager.name
     *
     * @param EntityInterface $entity The entity to get the value from
     * @param string $fieldName The name of the field or field chain (see function notes)
     * @return array|string Could either be an array if *_multi field or string
     */
    private function getParamVariableFieldValue(EntityInterface $entity, $fieldName)
    {
        /*
         * Check if this is an associated field name.
         * Variables can call associated entity fields with dot notation like
         * user.manager.name which would load th name of the user's manager.
         */
        if (strpos($fieldName, '.') === false)
        {
            // Just get the value from the fieldName if we are not referencing another entity
            return $entity->getValue($fieldName);
        }
        else
        {
            /*
             * The variable name will be something like user.name
             * where 'user' is the name of the field in $mergeWithEntity
             * containing the id of the user, and 'name' being the field
             * name of the referenced user.
             */
            $fieldParts = explode(".", $fieldName);

            // Get first element '$fieldName' and shorted $fieldParts
            $fieldName = array_shift($fieldParts);

            // Concat the remainder of the field names minus the first element for traversing
            $fieldNameRemainder = implode(".", $fieldParts);

            // Get the value of the entity from $entity
            $referencedEntityId = $entity->getValue($fieldName);

            // Get the field of the referenced value
            $field = $entity->getDefinition()->getField($fieldName);

            if ($referencedEntityId && $field->type === 'object' && $field->subtype)
            {
                // Load the referenced entity
                $referencedEntity = $this->entityLoader->get($field->subtype, $referencedEntityId);

                // Recursively call until we are at the last element of the fieldName
                return $this->getParamVariableFieldValue($referencedEntity, $fieldNameRemainder);
            }
        }

        // Return empty by default
        return "";
    }

    /**
     * Get the URL for this account for links
     *
     * @return string https://accountname.netric.com
     */
    private function getAccountBaseUrl()
    {
        return "";
    }

    /**
     * Make sure that there are no circular references in the action
     *
     * @param ActionInterface $action
     * @return bool
     */
    private function childActionIsCircular(ActionInterface $action)
    {
        if ($action === $this || ($action->getId() && $action->getId() === $this->getId()))
        {
           return true;
        }

        // Check all children
        $children = $action->getActions();
        foreach ($children as $childAction)
        {
            if ($this->childActionIsCircular($childAction))
                return true;
        }

        return false;
    }

    /**
     * Get last error if any
     *
     * @return Error|null
     */
    public function getLastError()
    {
        return (count($this->errors)) ? $this->errors[count($this->errors) - 1] : null;
    }

    /**
     * Get all errors encountered, if any
     *
     * @return \Netric\Error\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the type name from the class name
     *
     * @return string
     */
    private function getTypeName()
    {
        $className = get_class($this);

        // Remove namespace
        if (preg_match('@\\\\([\w]+)$@', $className, $matches)) {
            $className = $matches[1];
        }

        // Remove the 'Action' postfix
        $type = substr($className, 0, strlen($className) - strlen("Action"));

        // Convert to under_score from camelCase
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $type, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }
}
