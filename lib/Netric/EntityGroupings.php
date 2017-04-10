<?php
/**
 * Work with groupings for an entity
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric;

/**
 * Description of EntityGroupings
 */
class EntityGroupings 
{
    /**
     * Array of groupings for this entity
     * 
     * @var EntityGroupings\Group
     */
    private $groups = array();
    
    /**
     * Removed groupings
     * 
     * @param array
     */
    private $deleted = array();
    
    /**
     * Get object type
     * 
     * @var string
     */
    private $objType = "";
    
    /**
     * Field name we are working with
     * 
     * @var string
     */
    private $fieldName = "";
    
    /**
     * Optional datamapper to call this->save through the EntityGroupings\Loader class
     * 
     * @var Entity\DataMapperInterface
     */
    private $dataMapper = null;

    /**
     * Default filters that should be applied to all groups within this groupings container
     *
     * @var array
     */
    private $filters = array();
    
    /**
     * Initialize groupings
     * 
     * @param string $objType Set object type
     * @param string $fieldName The name of the field we are working with
     * @param array $filters Key/Value filter conditions for each group
     */
    public function __construct($objType, $fieldName="", $filters=array()) 
    {
        $this->objType = $objType;
        if ($fieldName)
            $this->fieldName= $fieldName;

        $this->filters = $filters;
    }
    
    /**
     * Set datammapper for groups
     * 
     * @param \Netric\Entity\DataMapperInterface
     */
    public function setDataMapper(\Netric\Entity\DataMapperInterface $dm)
    {
        $this->dataMapper = $dm;
    }
    
    /**
     * Save groupings to internally set DataMapper
     * 
     * @throws Exception
     */
    public function save()
    {
        if (!$this->dataMapper)
            throw new Exception ("You cannot save groups without first calling setDatamapper");
        
        $this->dataMapper->saveGroupings($this);
    }
    
    /**
     * Get the object type for this grouping
     * 
     * @return string The name of the object type
     */
    public function getObjType()
    {
        return $this->objType;
    }
    
    /**
     * Get the field name for this grouping
     * 
     * @return string The name of the field that stores these groupings
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Get array of filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Get a group that is hierarchical by path
     * 
     * @param string $path The full path to a grouping separated by '/'
     */
    public function getByPath($path)
    {
        $parts = explode("/", $path);
		$ret = null;

		// Loop through the path and get the last entry
		foreach ($parts as $grpname)
		{
			if ($grpname)
			{
				$parent = ($ret) ? $ret->id : "";
				$ret = $this->getByName($grpname, $parent);
			}
		}
        
        return $ret;
    }

	/**
	 * Get grouping path by id
	 *
	 * Grouping paths are constructed using the parent id. For instance Inbox/Subgroup would be constructed
	 * for a group called "Subgroup" whose parent group is "Inbox"
	 *
	 * @param string $gid The unique id of the group to get a path for
	 * @return string The full path of the heiarchy
	 */
	public function getPath($gid)
	{
		$grp = $this->getById($gid);

		$path = "";

        if (!$grp)
            return $path;

		if ($grp->parentId)
			$path .= $this->getPath($grp->parentId) . "/";

		$path .= $grp->name;

		return $path;
	}

	/**
	 * Retrive grouping data by a unique name
	 *
	 * @param string $nameValue The unique value of the group to retrieve
	 * @param int $paren Optional parent id for querying unique names of sub-groupings
	 * @return array See getGroupingData return value for definition of grouping data entries
	 */
	public function getByName($nameValue, $parent=null)
	{
        foreach ($this->groups as $grp)
        {
            if ($grp->name == $nameValue && $grp->parentId == $parent)
                return $grp;
        }
        
       return false;
	}

	/**
	 * Get groups
     * 
	 * @return \Netric\EntityGroupings\Group[]
	 */
	public function getAll()
	{
		return $this->groups;
	}

    /**
     * Recurrsively return all as an array
     *
     * @return arrray
     */
    public function toArray()
    {
        $ret = array();

        foreach ($this->groups as $grp)
        {
            $ret[] = $grp->toArray();
        }

        return $ret;
    }
    
    /**
     * Put all the groupings into a hierarchical structure with group->children being populated
     * 
     * @param int $parentId Get all at the level of this parent
     * @return \Netric\EntityGroups\Group[] with $group->children populated
     */
    public function getHeirarch($parentId=null)
    {
        $ret = array();
        foreach ($this->groups as $grp)
        {
            if ($grp->parentId == $parentId)
            {
                // If existing group, then get the children setting parent to group id
                if ($grp->id)
                    $grp->children = $this->getHeirarch($grp->id);
                
                $ret[] = $grp;
            }
        }
        return $ret;
    }
    
    /**
     * Get all children in a flat one dimensional array
     * 
     * @param int $parentId Get all at the level of this parent
     * @param $arr &$arr If set, then put children here
     * @return \Netric\EntityGroups\Group[] with $group->children populated
     */
    public function getChildren($parentId=null, &$ret=null)
    {
        if ($ret == null)
            $ret = array();
        
        foreach ($this->groups as $grp)
        {
            if ($grp->parentId == $parentId)
            {
                $ret[] = $grp;
                
                // If existing group, then get the children setting parent to group id
                if ($grp->id)
                    $this->getChildren($grp->id, $ret);
            }
        }
        
        return $ret;
    }
    
    /**
     * Get deleted groupings
     * 
     * @return int[]
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
    
    /**
     * Get changed or added groupings
     * 
     * @return \Netric\EntityGroupings\Group[]
     */
    public function getChanged()
    {
        $ret = array();
        
        foreach ($this->groups as $grp)
        {
            if ($grp->isDirty())
                $ret[] = $grp;
        }
        
        return $ret;
    }

	/**
	 * Insert a new entry into the table of a grouping field (fkey)
	 *
	 * @param \Netric\EntityGroupings\Group $group The group to add to the array
	 * @return true on success, false on failure
	 */
	public function add($group)
	{
        // Check to see if a grouping with this name already exists
        if ($group->parentId)
            $exists = $this->getByName($group->name, $group->parentId);
        else
            $exists = $this->getByName($group->name);
        
        if ($exists)
            return false;
        
        if ($group->parentId)
        {
            // TODO: check for circular reference in the chain
        }

        // Make sure we have filters before we evaluate the group
        if($this->filters)
        {
            // Set filters to match the defaults set in this container
            foreach ($this->filters as $name=>$value)
            {
                if ($value && !$group->getFilteredVal($name))
                {
                    $group->setValue($name, $value);
                }
            }
        }
        
		$this->groups[] = $group;
        
		return true;
	}

    /**
     * Get the grouping entry by id
     *
     * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
     * @param int $entryId the id to delete
     * @return bool true on sucess, false on failure
     */
    public function getById($entryId)
    {
        $ret = false;
        
        foreach ($this->groups as $grp)
        {
            if ($grp->id == $entryId)
                $ret = $grp;
        }

        return $ret;
    }
    
    /**
     * Create a new grouping
     * 
     * @param string $name Optional name of grouping
     */
    public function create($name="")
    {
        $group = new \Netric\EntityGroupings\Group();
        $group->setDirty(true);
        if ($name)
            $group->name = $name;
        return $group;
    }

	/**
	 * Delete and entry from the table of a grouping field (fkey)
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @param int $entryId the id to delete
	 * @return bool true on sucess, false on failure
	 */
	public function delete($entryId)
	{
		for ($i = 0; $i < count($this->groups); $i++)
        {
            if ($this->groups[$i]->id == $entryId)
            {
                // Move to deleted queue
                $this->deleted[] = $this->groups[$i];

                // Remove group from this grouping collection
                array_splice($this->groups, $i, 1);

                break;
            }
        }

		return true;
	}

    /**
     * Get unique filters hash
     */
    static public function getFiltersHash($filters=array())
    {
        // Make sure we have filters provided
        if($filters)
        {
            $buf = $filters; // copy array
            ksort($buf);

            $ret = "";

            foreach ($buf as $fname=>$fval)
            {
                if ($fval)
                    $ret .= $fname . "=" . $fval;
            }

            if ("" == $ret)
                $ret = 'none';

            return $ret;
        }
    }
}
