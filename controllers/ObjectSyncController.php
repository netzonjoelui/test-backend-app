<?php
/**
* Object list actions.
*/
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../lib/AntObjectSync.php');
require_once(dirname(__FILE__).'/../lib/AntFs.php');

/**
* Actions for interacting with Ant Object Lists
*/
class ObjectSyncController extends Controller
{
    /**
	 * Get changed objects for a device.
	 *
	 * This function will return 1000 objects at a time and subsequent calls will return
	 * the next page until zero items are returned. It is desinged to be called continually
	 * to 'listen' for incremental changes.
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
	 * @return array will output json encoded array of assoc array('id'=>object_id, 'action'=>'change'|'delete')
     */
    public function getChangedObjects($params)
    {
        $dbh = $this->ant->dbh;
		$retObj = array();

        if (!$params['partner_id'])
            return $this->sendOutputJson(array("error"=>"Partner ID is required"));

        if (!$params['obj_type'])
            return $this->sendOutputJson(array("error"=>"obj_type is a required param"));

		$parentId = ($params['parent_id']) ? $params['parent_id'] : null;

		if ($params['parent_path'])
		{
			$objDef = CAntObject::factory($this->ant->dbh, $params['obj_type'], null, $this->user);
			if ($objDef->def->parentField)
			{
				// Get parent_id from parent_path
				$parentId = null;

				// Check building path
				$ftype = $objDef->getFieldType($objDef->def->parentField);
				if ("object" == $ftype['type'])
				{
					if ($ftype['subtype'] == "folder")
					{
						$antfs = new AntFs($this->ant->dbh, $this->user);
						$fldr = $antfs->openFolder($params['parent_path'], true);
						if ($fldr->id)
							$parentId = $fldr->id;
					}
					else if ($ftype['subtype'] != "")
					{
						// Use the name of the object to get the path
						$obj = CAntObject::factory($this->ant->dbh, $ftype['subtype'], null, $this->user);
						$obj = $objDef->loadByPath($params['parent_path']);
						if ($obj->id)
							$parentId = $obj->id;
					}
				}
				else if ("fkey" == $ftype['type'])
				{
					// TODO: we might not have to be a hierarcy to use grouping?
					$grp = $objDef->getGroupingEntryByPath($objDef->parentField, $params['parent_path']);
					if ($grp['id'])
						$parentId = $grp['id'];
				}
			}
		}

		// Add conditions to the collection
		$conditions = null;
		if (is_array($params['conditions']))
		{
			$conditions = array();
			foreach ($params['conditions'] as $condId)
			{
				$val = $params["conditions_value_" . $condId];
				$val = $this->convertCondVariables($params['obj_type'], $params["conditions_field_" . $condId], $val);
				$conditions[] = array(
					"blogic" => $params["conditions_blogic_" . $condId], 
					"field" => $params["conditions_field_" . $condId], 
					"operator" => $params["conditions_operator_" . $condId], 
					"condValue" => $val,
				);
			}
		}

		$sync = new AntObjectSync($dbh, $params['obj_type'], $this->user);
		$partner = $sync->getPartner($params['partner_id']);

		// last param = create if it does not exist already
		$coll = $partner->getCollection($params['obj_type'], $params['field_name'], $conditions, true); 
		$changes = $coll->getChangedObjects($parentId);

		if ('xml' == $params['output'])
			return $this->sendOutputXml($changes);
		else
			return $this->sendOutputJson($changes);
	}

	/**
	 * Determine if there are any changes to be syncrhonized for this collection
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
	 * @return bool true if changes exist and false if not
     */
    public function collectionChangesExist($params)
    {
        $dbh = $this->ant->dbh;
		$retObj = array();

        if (!$params['partner_id'])
            return $this->sendOutputJson(array("error"=>"Partner ID is required"));

        if (!$params['obj_type'])
            return $this->sendOutputJson(array("error"=>"obj_type is a required param"));

		// Add conditions to the collection
		$conditions = null;
		if (is_array($params['conditions']))
		{
			$conditions = array();
			foreach ($params['conditions'] as $condId)
			{
				$val = $params["conditions_value_" . $condId];
				$val = $this->convertCondVariables($params['obj_type'], $params["conditions_field_" . $condId], $val);
				$conditions[] = array(
					"blogic" => $params["conditions_blogic_" . $condId], 
					"field" => $params["conditions_field_" . $condId], 
					"operator" => $params["conditions_operator_" . $condId], 
					"condValue" => $val,
				);
			}
		}

		$sync = new AntObjectSync($dbh, $params['obj_type'], $this->user);
		$partner = $sync->getPartner($params['partner_id']);

		// last param = create if it does not exist already
		$coll = $partner->getCollection($params['obj_type'], $params['field_name'], $conditions, true); 
		$fChanges = $coll->changesExist();

		if ('xml' == $params['output'])
			return $this->sendOutputXml($fChanges);
		else
			return $this->sendOutputJson($fChanges);
	}

	/**
	 * Get a collection id given the params
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
	 * @return bool true if changes exist and false if not
     */
    public function getCollectionId($params)
    {
        $dbh = $this->ant->dbh;
		$retObj = array();

        if (!$params['partner_id'])
            return $this->sendOutputJson(array("error"=>"Partner ID is required"));

        if (!$params['obj_type'])
            return $this->sendOutputJson(array("error"=>"obj_type is a required param"));

		$coll = $this->getCollection($params); 

		if ('xml' == $params['output'])
			return $this->sendOutputXml($coll->id);
		else
			return $this->sendOutputJson($coll->id);
	}

	/**
	 * Create a new partnership
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
	 * @return array will output json encoded array of assoc array('id'=>object_id, 'action'=>'change'|'delete')
	 */
	public function createPartnership($params)
	{
		$pid = uniqid("dev");

		$partn = new AntObjectSync_Partner($this->ant->dbh, $pid, $this->user);
		$partn->save();

		if ('xml' == $params['output'])
			return $this->sendOutputXml($pid);
		else
			return $this->sendOutputJson($pid);
	}

	/**
	 * Get a collection given the params
	 *
     * @param array $params An assocaitive array of parameters passed to this function. 
	 * @return AntObject_Sync_Collection
	 */
	private function getCollection($params, $parentId=null)
	{
		$dbh = $this->ant->dbh;
		$retObj = array();

        if (!$params['partner_id'])
            return false;

        if (!$params['obj_type'])
            return false;

		// Add conditions to the collection
		$conditions = null;
		if (is_array($params['conditions']))
		{
			$conditions = array();
			foreach ($params['conditions'] as $condId)
			{
				$val = $params["conditions_value_" . $condId];
				$val = $this->convertCondVariables($params['obj_type'], $params["conditions_field_" . $condId], $val);
				$conditions[] = array(
					"blogic" => $params["conditions_blogic_" . $condId], 
					"field" => $params["conditions_field_" . $condId], 
					"operator" => $params["conditions_operator_" . $condId], 
					"condValue" => $val,
				);
			}
		}

		$sync = new AntObjectSync($dbh, $params['obj_type'], $this->user);
		$partner = $sync->getPartner($params['partner_id']);

		// last param = create if it does not exist already
		$coll = $partner->getCollection($params['obj_type'], $params['field_name'], $conditions, true); 

		return $coll;
	}

	/**
	 * Convert condition variables
	 *
	 * @param string $objType The object type name
	 * @param string $fieldName The name of the field for the condition
	 * @param string $condValue The value of the condition to search for that may need to be translated
	 * @return string Converted value, for example user.-3 becomes the user id of the current user
	 */
	private function convertCondVariables($objType, $fieldName, $condValue)
	{
		$ret = $condValue;

		if (!$condValue)
			return $ret;

		$objDef = CAntObject::factory($this->ant->dbh, $objType, null, $this->user);
		$field = $objDef->def->getField($fieldName);

		if ($field)
		{
			switch ($field->type)
			{
			case 'number':
				if ($fieldName == "id" && $objType == "folder" && !is_numeric($condValue))
				{
					$antfs = new AntFs($this->ant->dbh, $this->user);
					$fldr = $antfs->openFolder($condValue, true);
					if ($fldr->id)
						$ret = $fldr->id;
				}
				break;
			case 'object':
				if ($field->subtype == "user" && $condValue == USER_CURRENT)
				{
					$ret = $this->user->id;
				}
				else if ($field->subtype == "folder" && !is_numeric($condValue))
				{
					$antfs = new AntFs($this->ant->dbh, $this->user);
					$fldr = $antfs->openFolder($condValue, true);
					if ($fldr->id)
						$ret = $fldr->id;
				}
				else if ($field->subtype != "" && !is_numeric($condValue))
				{
					// Use the name of the object to get the path
					$obj = CAntObject::factory($this->ant->dbh, $field->subtype, null, $this->user);
					$obj = $objDef->loadByPath($condValue);
					if ($obj->id)
						$ret = $obj->id;
				}
				break;
			
			case 'fkey':
				if (!is_numeric($condValue))
				{
					$grp = $objDef->getGroupingEntryByPath($fieldName, $condValue);
					if ($grp['id'])
						$ret = $grp['id'];
				}
				break;
			}
		}

		return $ret;
	}
}
