<?php
/**
* Object list actions.
*/
require_once(dirname(__FILE__).'/../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../lib/Controller.php');
require_once(dirname(__FILE__).'/../lib/AntFs.php');

/**
* Actions for interacting with Ant Object Lists
*/
class SearchController extends Controller
{
    /**
    * Query objects to get the list
    *
    * @param array $params An assocaitive array of parameters passed to this function. 
    */
    public function query($params)
    {
		$dbh = $this->ant->dbh;

		// Initialize object return
		$retObj = array();

		if ($params['q'])
		{
			$queryTypes = array(
				"email_message" => "Messages",
				"note" => "Notes",
				"customer" => "Customers",
				"task" => "Tasks",
				"case" => "Cases",
				"file" => "Files",
			);

			foreach ($queryTypes as $objType=>$name)
			{
				$retObj[$name] = $this->getTypeResults($objType, $params['q']);
			}
		}

		return $this->sendOutputJson($retObj);
    }

	/**
	 * Get results for a type of object
	 *
	 * @param string $objType The name of the type of object to query
	 * @param string $query The full-text query
	 * @return array Array of objects
	 */
	private function getTypeResults($objType, $query)
	{
		$objects = array();
		$dbh = $this->ant->dbh;

		$olist = new CAntObjectList($dbh, $objType, $this->user);
		$olist->addConditionText($query);
		$this->addTypeFilters($olist, $objType);
		$ret = $olist->getObjects(0, 10);
		$num = $olist->getNumObjects();

		if ($ret == -1)
			return $objects;

		for ($i = 0; $i < $num; $i++)
		{
			$setObj = array();

			$obj = $olist->getObject($i);

			$f_canview = $obj->dacl->checkAccess($this->user, "View", ($this->user->id==$obj->owner_id)?true:false);

			$setObj['id'] = $obj->id;
			$setObj['title'] = $obj->getName();
			$setObj['objType'] = $objType;
			$setObj['revision'] = $obj->revision;
			$setObj['hascomments'] = $obj->hasComments();
			$setObj['iconName'] = $obj->getIconName();
			$setObj['iconPath'] = $obj->getIcon(16, 16);

			// Set security
			$setObj['security'] = array();
			$setObj['security']['view'] = $f_canview;
			$setObj['security']['edit'] = $obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->owner_id)?true:false);
			$setObj['security']['delete'] = $obj->dacl->checkAccess($this->user, "Delete", ($this->user->id==$obj->owner_id)?true:false);

			$ofields = $olist->fields_def_cache->getFields();
			foreach ($ofields as $fname=>$field)
			{
				if ($fname == "id" || $fname == "dacl")
					continue;

				if (!$f_canview && $fname!="name" && $fname!="user_id" && $fname!="owner_id")
				{
					$setObj[$fname] = "";
				}
				else
				{
					if ($field->type=='fkey_multi' || $field->type=='object_multi')
					{
						$setObj[$fname] = array();

						$vals = $obj->getValue($fname);
						if (is_array($vals) && count($vals))
						{
							foreach ($vals as $val)
								$setObj[$fname][] = array("key"=>$val, "value"=>$obj->getForeignValue($fname, $val));
						}
					}
					else if ($field->type=='fkey' || $field->type=='object' || $field->type=="alias")
					{
						$val = $obj->getValue($fname);
						$setObj[$fname] = array("key"=>$val, "value"=>$obj->getForeignValue($fname, $val));
					}
					else
					{
						$val = $obj->getValue($fname, true);
						if ($fname == $olist->fields_def_cache->listTitle && $olist->fields_def_cache->parentField)
						{
							$path = $obj->getValue("path");
							if ($path)
								$val = $path."/".$val;
						}
						$setObj[$fname] = $val;
					}
				}

				$olist->unsetObject($i);
			}
			
			$objects[] = $setObj;
		}

		return $objects;
	}

	/**
	 * Add private filtering conditions based on object types
	 *
	 * This will improved the accuracy of the results because different object types
	 * will need different filters. Like 'note' should only pull users's private notes.
	 *
	 * @param CAntObjectList $olist The object list to add conditions to
	 * @param string $objType Techically we can get this from the list, but pass it anyway
	 */
	private function addTypeFilters(&$olist, $objType)
	{
		switch ($objType)
		{
		case 'file':
		case 'email_message':
		case 'case':
			$olist->addCondition("and", "owner_id", "is_equal", $this->user->id);
			break;
		case 'task':
			$olist->addCondition("and", "done", "is_not_equal", 't');
			// fall through to match user_id
		case 'note':
			$olist->addCondition("and", "user_id", "is_equal", $this->user->id);
			break;
		}
	}
}
