<?php
/**
 * This is the base class for all entity indexes
 */
namespace Netric\EntityQuery\Index;

use Netric\EntityDefinition;
use Netric\Entity\ObjType\UserEntity;
use Netric\EntityQuery;
use Netric\EntityQuery\Results;
use Netric\Entity\Recurrence;
use Netric\EntityQuery\Plugin\PluginInterface;
use Netric\Entity\Entity;

abstract class IndexAbstract
{
    /**
     * Handle to current account
     * 
     * @var \Netric\Account\Account
     */
    protected $account = null;

    /**
     * Entity factory used for instantiating new entities
     *
     * @var \Netric\Entity\EntityFactory
     */
    protected $entityFactory = null;

    /**
     * Recurrence series manager to test
     *
     * @var Recurrence\RecurrenceSeriesManager
     */
    private $recurSeriesManager = null;

    /**
     * Index of plugins loaded by objName
     *
     * @var array('obj_name'=>PluginInterface)
     */
    private $pluginsLoaded = [];

    /**
     * Setup this index for the given account
     * 
     * @param \Netric\Account\Account $account
     */
    public function __construct(\Netric\Account\Account $account)
    {
        $this->account = $account;
        $this->entityFactory = $account->getServiceManager()->get("EntityFactory");
        $seriesManagerName = "Netric/Entity/Recurrence/RecurrenceSeriesManager";
        //$this->recurSeriesManager = $account->getServiceManager()->get($seriesManagerName);
        
        // Setup the index
        $this->setUp($account);
    }
    
    /**
     * Setup this index for the given account
     * 
     * @param \Netric\Account\Account $account
     */
    abstract protected function setUp(\Netric\Account\Account $account);
    
    /**
	 * Save an object to the index
	 *
     * @param \Netric\Entity\Entity $entity Entity to save
	 * @return bool true on success, false on failure
	 */
	abstract public function save(\Netric\Entity\Entity $entity);
    
    /**
	 * Delete an object from the index
	 *
     * @param string $id Unique id of object to delete
	 * @return bool true on success, false on failure
	 */
	abstract public function delete($id);

    /**
     * Execute a query and return the results
     *
     * @param EntityQuery &$query The query to execute
     * @param Results $results Optional results set to use. Otherwise create new.
     * @return \Netric\EntityQuery\Results
     */
    abstract protected function queryIndex(EntityQuery $query, Results $results = null);

    /**
     * Execute a query and return the results
     *
     * @param EntityQuery $query A query to execute
     * @param Results $results Optional results set to use. Otherwise create new.
     * @return \Netric\EntityQuery\Results
     */
    public function executeQuery(EntityQuery $query, Results $results = null)
    {
        // First check to see if we have any recurring patterns to update
        //$this->recurSeriesManager->createInstancesFromQuery($query, $results);

        // Trigger any plguins for before the query completed
        $this->beforeExecuteQuery($query);

        // Get results form the index for a query
        $ret = $this->queryIndex($query, $results);

        // Trigger any plugins after the query completed
        $this->afterExecuteQuery($query);

        return $ret;
    }
    
    /**
	 * Split a full text string into an array of terms
	 *
	 * @param string $qstring The entered text
	 * @return array Array of terms
	 */
	public function queryStringToTerms($qstring)
	{
		if (!$qstring)
			return array();

		$res = array();
		//preg_match_all('/(?<!")\b\w+\b|\@(?<=")\b[^"]+/', $qstr, $res, PREG_PATTERN_ORDER);
		preg_match_all('~(?|"([^"]+)"|(\S+))~', $qstring, $res);
		return $res[0]; // not sure why but for some reason results are in a multi-dimen array, we just need the first
	}
    
    /**
     * Get a definition by name
     * 
     * @param string $objType
     */
    public function getDefinition($objType)
    {
        $defLoader = $this->account->getServiceManager()->get("EntityDefinitionLoader");
        return $defLoader->get($objType);
    }
    
    /**
	 * Get ids of all parent ids in a parent-child relationship
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyUp(\Netric\EntityDefinition\Field $field, $this_id)
	{
		$dbh = $this->dbh;
		$parent_arr = array($this_id);
        
        // TODO: finish
        /*
		if ($this_id && $parent_field)
		{
			$query = "select $parent_field as pid from $table where id='$this_id'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$subchildren = $this->getHeiarchyUp($table, $parent_field, $row['pid']);

				if (count($subchildren))
					$parent_arr = array_merge($parent_arr, $subchildren);
			}
			$dbh->FreeResults($result);
		}
         */

		return $parent_arr;
	}

	/**
	 * Get ids of all child entries in a parent-child relationship
     * 
     * This function may be over-ridden in specific indexes for performance reasons
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyDownGrp(\Netric\EntityDefinition\Field $field, $this_id)
	{
		$children_arr = array($this_id);
        
        

		return $children_arr;
	}

	/**
	 * Get ids of all parent entries in a parent-child relationship of an object
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 */
	public function getHeiarchyUpObj($objType, $oid)
	{
		$ret = array($oid);

        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $ent = $loader->get($objType, $oid);
        $ret[] = $ent->getId();
        if ($ent->getDefinition()->parentField)
        {
            // Make sure parent is set, is of type object, and the object type has not crossed over (could be bad)
            $field = $ent->getDefinition()->getField($ent->getDefinition()->parentField);
            if ($ent->getValue($field->name) && $field->type == "object" && $field->subtype == $objType)
            {
                $children = $this->getHeiarchyUpObj($field->subtype, $ent->getValue($field->name));
                if (count($children))
                    $ret = array_merge($ret, $children);
            }
        }

		return $ret;
	}
    
    /**
	 * Get ids of all child entries in a parent-child relationship of an object
	 *
	 * @param string $table The table to query
	 * @param string $parent_field The field containing the id of the parent entry
	 * @param int $this_id The id of the child element
	 * @param int[] $aProtectCircular Hold array of already referenced objects to chk for array
	 */
	public function getHeiarchyDownObj($objType, $oid, $aProtectCircular=array())
	{
		// Check for circular refrences
		if (in_array($oid, $aProtectCircular))
			throw new \Exception("Circular reference found in $objType:$oid");
			//return array();

		$ret = array($oid);
		$aProtectCircular[] = $oid;

        $loader = $this->account->getServiceManager()->get("EntityLoader");
        $ent = $loader->get($objType, $oid);
        //$ret[] = $ent->getId();
        if ($ent->getDefinition()->parentField)
        {
            // Make sure parent is set, is of type object, and the object type has not crossed over (could be bad)
            $field = $ent->getDefinition()->getField($ent->getDefinition()->parentField);
            if ($field->type == "object" && $field->subtype == $objType)
            {
                $index = $this->account->getServiceManager()->get("EntityQuery_Index");
                $query = new \Netric\EntityQuery($field->subtype);
                $query->where($ent->getDefinition()->parentField)->equals($ent->getId());
                $res = $index->executeQuery($query);
                for ($i = 0; $i < $res->getTotalNum(); $i++)
                {
                    $subEnt = $res->getEntity($i);
                    $children = $this->getHeiarchyDownObj($objType, $subEnt->getId(), $aProtectCircular);
                    if (count($children))
                        $ret = array_merge($ret, $children);
                }
            }
        }

		return $ret;
	}

    /**
     * Sanitize condition values for querying
     *
     * This function also takes care of translating environment varials such as
     * current user and current user's team into IDs for the query.
     *
     * @param EntityDefinition\Field $field
     * @param mixed $value
     */
    public function sanitizeWhereCondition(EntityDefinition\Field $field, $value)
    {
        $user = $this->account->getUser();

        // Cleanup bool
        if ("bool" == $field->type && is_string($value))
        {
            switch ($value)
            {
                case 'true':
                case 't':
                case true:
                    return true;
                default:
                    return false;
            }
        }

        // Cleanup dates and times
        if (("date" == $field->type || "timestamp" == $field->type))
        {
            // Convert \DateTime to a timestamp
            if ($value instanceof \DateTime) {
                $value = $value->format("Y-m-d h:i:s A e");
            }
            /*
             * The below is causing things fail due to complex queries like
             * monthIsEqual and dayIsEqual. Probably needs some more thought.
            else if (is_numeric($value) && !is_string($value)) {
                $value = date("Y-m-d h:i:s A e", $value);
            }
            */
        }

        // Replace user vars
        if ($user)
        {
            // Replace current user
            if (intval($value) === UserEntity::USER_CURRENT && (
                    ($field->type == "object" && $field->subtype == "user") ||
                    (
                        ($field->type == "fkey" || $field->type == "fkey_multi")
                        && $field->subtype == "users"
                    )
                )
            )
            {
                $value = $user->getId();
            }

            /*
             * TODO: Handle the below conditions
             *
            // Replace dereferenced current user team
            if ($field->type == "object" && $field->subtype == "user" && $ref_field == "team_id"
                && ($value==USER_CURRENT || $value==TEAM_CURRENTUSER)  && $user->teamId)
                $value = $user->teamId;

            // Replace current user team
            if ($field->type == "fkey" && $field->subtype == "user_teams"
                && ($value==USER_CURRENT || $value==TEAM_CURRENTUSER) && $user->teamId)
                $value = $user->teamId;


             */
            // Replace object reference with user variables
            if (($field->type == "object" || $field->type == "object_multi") && !$field->subtype
                && $value == "user:" . UserEntity::USER_CURRENT)
                $value = "user:" . $user->getId();
        }

        /*
        // TODO: Replace grouping labels with id
        if (($field->type == "fkey" || $field->type == "fkey_multi") && $value && !is_numeric($value))
        {
            $grp = $this->obj->getGroupingEntryByName($fieldParts[0], $value);
            if ($grp)
                $value = $grp['id'];
            else
                return;
        }
        */

        // If querying an object type then only leave the number if the value has the object type
        if (($field->type == "object" || $field->type == "object_multi") && $field->subtype) {
            $objRefParts = Entity::decodeObjRef($value);
            if ($objRefParts) {
                $value = $objRefParts['id'];
            }
        }

        return $value;
    }

    /**
     * Check to see if we have any plugins listening before the query executes
     *
     * @param EntityQuery $query The query that is about to run
     */
    private function beforeExecuteQuery(EntityQuery $query)
    {
        $plugin = $this->getPlugin($query->getObjType());
        if ($plugin) {
            $plugin->onBeforeExecuteQuery($this->account->getServiceManager(), $query);
        }
    }

    /**
     * Check to see if we have any plugins listening after the query executes
     *
     * @param EntityQuery $query The query that just ran
     */
    private function afterExecuteQuery(EntityQuery $query)
    {
        $plugin = $this->getPlugin($query->getObjType());
        if ($plugin) {
            $plugin->onAfterExecuteQuery($this->account->getServiceManager(), $query);
        }
    }

    /**
     * Look for and constuct a query plugin if it exists
     *
     * @param string $objType The object type name
     * @return PluginInterface|null
     */
    private function getPlugin($objType)
    {
        $plugin = null;

        // Check if we have already loaded this plugin
        if (isset($this->pluginsLoaded[$objType])) {
            return $this->pluginsLoaded[$objType];
        }

        $objClassName = str_replace("_", " ", $objType);
        $objClassName = ucwords($objClassName);
        $objClassName = str_replace(" ", "" , $objClassName);

        $pluginName = "\\Netric\\EntityQuery\\Plugin\\" . $objClassName . 'QueryPlugin';
        if (class_exists($pluginName)) {

            // Construct a new plugin
            $plugin = new $pluginName();

            // Cache for future calls
            $this->pluginsLoaded[$objType] = $plugin;
        }

        return $plugin;
    }
}
