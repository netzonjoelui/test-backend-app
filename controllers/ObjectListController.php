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
class ObjectListController extends Controller
{
    /**
     * Query objects to get the list
     *
     * @param array $params An assocaitive array of parameters passed to this function. 
     */
    public function query($params)
    {
        $dbh = $this->ant->dbh;
		$retObj = array();

        if (!$params['obj_type'])
            return $this->sendOutputJson(array("error"=>"obj_type and field are required params"));

        $showdeleted = null;
        $type = null;
        $offset = 0;
        $limit = 50;
        
        if(isset($params['offset']))
		    $offset = $params['offset'];
            
        if(isset($params['limit']))
		    $limit = $params['limit'];
            
        if(isset($params['showdeleted']))
            $showdeleted = $params['showdeleted'];
            
        if(isset($params['type']))
            $type = $params['type'];

		// Build query and get list
		// ------------------------------------------------------------
		$olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);

		if ($showdeleted==1)
			$olist->hideDeleted = false;

		// Force the use of the alternate fulltext index if set
		//if ($params['obj_type'] != "email_thread") // temp hack until we resolve email thread issue
			$olist->forceFullTextOnly = true;

		// Add number of comments to minimum data being pulled
		if(isset($params['updatemode']))
			$olist->addMinField("num_comments");
            
		if ($type=="sync")
		{
			// NOTE: this is being commented out because it is creating problems when pulling partitioned tables
			//$olist->hideDeleted = false;
            
            if($params['ts_lastsync'])
			    $olist->addCondition("and", "ts_updated", "is_greater_or_equal", $params['ts_lastsync']);
		}


		// Check for private
		if ($olist->obj->isPrivate())
		{
			if ($olist->obj->def->getField("owner"))
				$olist->fields("and", "owner", "is_equal", $this->user->id);
			if ($olist->obj->def->getField("owner_id"))
				$olist->addCondition("and", "owner_id", "is_equal", $this->user->id);
			if ($olist->obj->def->getField("user_id"))
				$olist->addCondition("and", "user_id", "is_equal", $this->user->id);
		}

		// Set conditions based on UI form
		$olist->processFormConditions($params);

		// Set browseby folders
		$retObj['browseByObjects'] = array(); // initialize empty array so the js browser has something to check
		if (isset($params['browsebyfield']) && $params['browsebyfield'])
		{
			if ($params['obj_type'] == 'file')
			{
				$antfs = new AntFs($this->ant->dbh, $this->user);

				$path = $this->getBrowseByAbsolutePathFolder($params['browsebypath'], $params['browsebyroot']);
				$create = ($path == "/" || $path == "%userdir%") ? true : false;
				$folder = $antfs->openFolder($path, $create);

				if ($folder->id)
					$olist->addCondition("and", $params['browsebyfield'], "is_equal", $folder->id);

				if ($folder)
				{

					$retObj['browseByCurPath'] = $path;
					$retObj['browseByCurId'] = $folder->id;
					$retObj['browseByObjects'] = $this->setBrowseByFolders($folder, $params);
				}
				else
				{
					$retObj['browseByObjects'] = array(); // Folder not found
				}
			}
		}

		$ret = $olist->getObjects($offset, $limit);
		$num = $olist->getNumObjects();

		if ($ret == -1)
            return $this->sendOutputJson(array("error"=>$olist->lastError));

		$retObj['totalNum'] = $olist->total_num;
		$retObj['tsServerTime'] = gmdate("Y-m-d\\TG:i:s\\Z", time());

		// Set pagination
		// ------------------------------------------------------------
		if ($olist->total_num > $limit)
		{
			$prev = -1; // Hide

			// Get total number of pages
			$leftover = $olist->total_num % $limit;
			
			if ($leftover)
				$numpages = (($olist->total_num - $leftover) / $limit) + 1; //($numpages - $leftover) + 1;
			else
				$numpages = $olist->total_num / $limit;
			// Get current page
			if ($offset > 0)
			{
				$curr = $offset / $limit;
				$leftover = $offset % $limit;
				if ($leftover)
					$curr = ($curr - $leftover) + 1;
				else 
					$curr += 1;
			}
			else
				$curr = 1;
			// Get previous page
			if ($curr > 1)
				$prev = $offset - $limit;
			// Get next page
			if (($offset + $limit) < $olist->total_num)
				$next = $offset + $limit;
			$pag_str = "Page $curr of $numpages";

			$retObj['paginate'] = array();
			$retObj['paginate']['nextPage'] = $next;
			$retObj['paginate']['prevPage'] = $prev;
			$retObj['paginate']['desc'] = $pag_str;
		}

		// Set facets
		// ------------------------------------------------------------
		$retObj['facets'] = array();
		if (count($olist->facetCounts))
		{
			foreach ($olist->facetCounts as $fname=>$cnts)
			{
				$facet = array();
				$facet['name'] = $fname;
				$facet['terms'] = array();
				foreach ($cnts as $term=>$cnt)
					$facet['terms'][] = array("term"=>$term, "count"=>$cnt);

				$retObj['facets'][] = $facet;
			}
		}

		// Set objects
		// ------------------------------------------------------------
		$retObj['objects'] = array();
        $timeOpen = 0;
        $timeDacl = 0;
        $timeParams = 0;
        $timeSecurity = 0;
		for ($i = 0; $i < $num; $i++)
		{
			$setObj = array();

			if(isset($params['updatemode']) && $params['updatemode']) // Only get id and revision
			{
				$objMin = $olist->getObjectMin($i);	

				$setObj['id'] = $objMin['id'];
				$setObj['revision'] = $objMin['revision'];
				$setObj['num_comments'] = $objMin['num_comments'];
				// TODO: hascomments needs to be sent for status updates but nothing else really at this point
				//$setObj['hascomments'] = $obj->hasComments();
			}
			else // Print full details
			{
				$tmpStart = microtime(true);
				$obj = $olist->getObject($i);
				$timeOpen += microtime(true) - $tmpStart;

				$tmpStart = microtime(true);
				$f_canview = $obj->dacl->checkAccess($this->user, "View", ($this->user->id==$obj->owner_id)?true:false);
				$timeDacl += microtime(true) - $tmpStart;

				$tmpStart = microtime(true);
				$setObj['id'] = $obj->id;
				$setObj['revision'] = $obj->revision;
				$setObj['hascomments'] = $obj->hasComments();
				$setObj['iconName'] = $obj->getIconName();
				$setObj['iconPath'] = $obj->getIcon(16, 16);
				$timeParams += microtime(true) - $tmpStart;

				// Set security
				$tmpStart = microtime(true);
				$setObj['security'] = array();
				$setObj['security']['view'] = $f_canview;
				$setObj['security']['edit'] = $obj->dacl->checkAccess($this->user, "Edit", ($this->user->id==$obj->owner_id)?true:false);
				$setObj['security']['delete'] = $obj->dacl->checkAccess($this->user, "Delete", ($this->user->id==$obj->owner_id)?true:false);
				$timeSecurity += microtime(true) - $tmpStart;

				$tmpStart = microtime(true);
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
							/** Removed because we are trying to align the object list and individual object with same exact data
							if ($fname == $olist->fields_def_cache->listTitle && $olist->fields_def_cache->parentField)
							{
								$path = $obj->getValue("path");
								if ($path)
									$val = $path."/".$val;
							}
							 */
							$setObj[$fname] = $val;
						}
					}
				}

				$olist->unsetObject($i);
			}
			
			$retObj['objects'][] = $setObj;
		}

		return $this->sendOutputJson($retObj);
    }

    /**
    * delete the objects
    *
    * @param array $params An assocaitive array of parameters passed to this function.
    */
    public function deleteObjects($params)
    {
        $dbh = $this->ant->dbh;
        $ret = array();
        
        if ($params['obj_type'] && is_array($params['objects']) || $params['all_selected'])
        {            
            $olist = new CAntObjectList($dbh, $params['obj_type'], $this->user);
            $processed = $olist->processFormConditions($params);

			if ($processed) // make sure we don't delete entire list with no form filters
			{
				$olist->getObjects();
				$num = $olist->getNumObjects();
				for ($i = 0; $i < $num; $i++)
				{
					$obj = $olist->getObject($i);
					//if ($obj->remove(false)) // The first param keeps from committing to index until done
					if ($obj->remove())
						$ret[] = $obj->id;
					$olist->unsetObject($i);
				}

				// Now commit changes to index
				//$obj = CAntObject::factory($dbh, $params['obj_type']);
				//$obj->indexCommit();
			}

			// Handle optional browseby deletions
			if ($params['browsebyfield'])
			{
				$objDef = new CAntObject($dbh, $params['obj_type'], null, $this->user);
				$field = $objDef->def->getField($params['browsebyfield']);

				foreach ($params['objects'] as $bid)
				{
					// Get only browse objects
					if (strpos($bid, "browse:") !== false)
					{
						$parts = explode(":", $bid);

						// Use special folder delete
						if ($field->subtype == 'folder')
						{
							// Open the folder by id then delete
							if (AntFs::removeFolderById($parts[1], $dbh, $this->user))
								$ret[] = $bid;
						}
						else
						{
							/*
							// Standard object deletion
							$obj = new CAntObject($dbh, $field['subtype'], $parts[1], $this->user);
							if ($obj->remove())
								$ret[] = $bid;
							 */
						}
					}
				}
			}
        }        
        else
            $ret = array("error"=>"obj_type and objects are required params");

		return $this->sendOutputJson($ret);
    }

	/**
	 * Set browseby objects
	 *
	 * @return array of objects id id and name
	 */
	private function setBrowseByFolders($folder, $params)
	{
		$ret = array();

		if (!$folder)
			return $ret;

		$olist = $folder->getFoldersList();
		$olist->processFormConditions($params);
		$olist->addOrderBy("name");
		$olist->getObjects(0, 1000);
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$subfolder = $olist->getObject($i);

			$ret[] = array(
				"id"		=> "browse:" . $subfolder->id,
				"iconName"	=> $subfolder->getIconName(),
				"iconPath" 	=> $subfolder->getIcon(16, 16),
				"name"		=> $subfolder->getValue('name'),
				'security'	=> array('view'=>true, 'edit'=>true, 'delete'=>true),
			);
		}

		return $ret;
	}

	/**
	 * Get the absolute path for a folder object depending on the params passed to the query
	 *
	 * @param string $browseByPath The path passed to the query, may be absolute or relative with 'root' defined
	 * @param int $rootId Optional root id of folder to use with relative $browseByPath
	 * @return string Absolute path
	 */
	private function getBrowseByAbsolutePathFolder($browseByPath, $rootId)
	{
		$path = "";
		if ($rootId && $browseByPath[0] != '/') // use root if path is not absolute
		{
			// pull relative path
			$root = CAntObject::factory($this->ant->dbh, "folder", $rootId, $this->user);
			if ($root->id)
			{
				$path= $root->getFullPath();
				if ($path == '/') // if root then make empty because below will absolute path
					$path = "";

				$relativePath = $browseByPath;
				if ($relativePath[0]=='.' && $relativePath[1]=='/') // Current dir with children
					$relativePath = substr($relativePath, 2);
				else if ($relativePath[0]=='.') // current dir all alone
					$relativePath = substr($relativePath, 1);

				if ($relativePath) // anything other than just current dir '.'
					$path = $path . "/" . $relativePath;
				else
					$path = ($path) ? $path : "/";
			}
		}
		else
		{
			// absolute path
			$path = ($browseByPath) ? ($browseByPath) : "/";
		}

		return $path;
	}
}