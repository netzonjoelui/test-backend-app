<?php
/**
 * Sync collection for entity groupings
 *
 * @category  AntObjectSync
 * @package   Collection
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

use Netric\Entity;
use Netric\EntitySync\DataMapperInterface;
use Netric\EntitySync\EntitySync;
use Netric\EntitySync\Commit;

/**
 * Class used to represent a sync partner or endpoint
 */
class GroupingCollection extends AbstractCollection implements CollectionInterface
{
	/**
	 * Index for querying entities
	 *
	 * @var \Netric\EntityQuery\Index\IndexInterface
	 */
	private $entityDataMapper = null;

	/**
	 * Constructor
	 *
	 * @param \Netric\EntitySync\DataMapperInterface $dm The sync datamapper
	 * @param \Netirc\EntitySync\Commit\CommitManager $commitManager Manage system commits
	 * @param \Netric\Entity\DataMapperInterface $entityDataMapper Entity DataMapper
	 */
	public function __construct(
		DataMapperInterface $dm, 
		Commit\CommitManager $commitManager, 
		Entity\DataMapperInterface $entityDataMapper)
	{
		$this->entityDataMapper = $entityDataMapper;

		// Pass datamapper to parent
		parent::__construct($dm, $commitManager);
	}

    /**
     * Get a stats list of what has changed locally since the last sync
     *
	 * @param bool $autoFastForward If true (default) then fast-forward collection commit_id on return
	 * @param \DateTime $limitUpdatesAfter If set, only pull updates after a specific date
	 * @return array of associative array [
	 *      [
	 *          "id", // Unique id of local object
	 *          "action", // 'change'|'delete',
	 *          "commit_id" // Incremental id of the commits - global revision
	 *      ]
	 *  ]
	 */
	public function getExportChanged($autoFastForward=true, \DateTime $limitUpdatesAfter = null)
	{
		if (!$this->getObjType())
		{
			throw new \InvalidArgumentException("Object type not set! Cannot export changes.");
		}

		if (!$this->getFieldName())
		{
			throw new \InvalidArgumentException("Field name is not set! Cannot export changes.");
		}

		// Set return array
		$retStats = array();

		// Get the current commit for this collection
		$lastCollectionCommit = $this->getLastCommitId();
        if ($this->isBehindHead())
		{
            // Get previously imported so we do not try to export a recent import
            if ($this->getId())
            {
                $imports = $this->dataMapper->getImported($this->getId());
            }
            else
            {
                $imports = array();
            }

	        // Get groupings
	        $filters = $this->getFiltersFromConditions();
	        $groupings = $this->entityDataMapper->getGroupings($this->getObjType(), $this->getFieldName(), $filters);

	        // Loop through each change
	        $grps = $groupings->getAll();
	        for ($i = 0; $i < count($grps); $i++)
	        {
	        	$grp = $grps[$i];

	        	if ($grp->commitId > $lastCollectionCommit || !$grp->commitId)
	        	{
                    // First make sure we didn't just import this
                    $skipStat = false;
                    foreach ($imports as $imported)
                    {
                        if ($imported['local_id'] == $grp->id
                            && $imported['local_revision'] == $grp->commitId)
                        {
                            // Skip over this export because we just imported it
                            $skipStat = true;
                            break;
                        }
                    }

                    if (!$skipStat) {
                        $retStats[] = array(
                            "id" => $grp->id,
                            "action" => 'change',
							"commit_id" => $grp->commitId
                        );
                    }

                    if (($autoFastForward && $grp->commitId) || $skipStat)
                    {
                        // Fast-forward $lastCommitId to last commit_id sent
                        $this->setLastCommitId($grp->commitId);

                        // Save to exported log
                        $logRet = $this->logExported(
                            $grp->id,
                            $grp->commitId
                        );
                    }
	        	}	
	        }

	        /*
	         * Deleted groupings are marked after bing deleted by there is no reference
	         * so it will be in the stale log.
	         */
            $staleStats = $this->getExportedStale();
            if ($autoFastForward)
            {
                foreach ($staleStats as $stale)
                {
                    // Save to exported log with no commit deletes the export
                    $logRet = $this->logExported($stale['id'], null);
                }
            }
            $retStats = array_merge($retStats, $staleStats);
		}

		return $retStats;
	}

	/**
	 * Get a collection type id
	 *
	 * @return int Type from \Netric\EntitySync::COLL_TYPE_*
	 */
	public function getType()
	{
		return EntitySync::COLL_TYPE_GROUPING;
	}

	/**
	 * Fast forward this collection to current head which resets it to only get future changes
	 */
	public function fastForwardToHead()
	{
		$headCommitId = $this->getCollectionTypeHeadCommit();

		if ($headCommitId)
			$this->setLastCommitId($headCommitId);
	}

	/** 
	 * Convert collection conditions to simpler groupings filter which only supports equals
	 *
	 * @return array
	 */
	private function getFiltersFromConditions()
	{
		$filters = array();
		$conditions = $this->getConditions();
        foreach ($conditions as $cond)
        {
        	if ($cond['blogic'] == 'and' && $cond['operator'] == 'id_equal')
        	{
        		$filters[$cond['field']] = $cond['condValue'];
        	}
        }
        return $filters;
	}

	/**
	 * Construct unique commit identifier for this collection
	 *
	 * @return string
	 */
	private function getCommitHeadIdent()
	{
		// Convert collection conditions to simpler filters for groupings
		$filters = $this->getFiltersFromConditions();
        $filtersHash = \Netric\EntityGroupings::getFiltersHash($filters);

		// TODO: if private then add the user_id as a filter field
		return "groupings/" . $this->getObjType() . "/" . $this->getFieldName() . "/" . $filtersHash;
	}

    /**
     * Get the head commit for a given collection type
     *
     * @return string The last commit id for the type of data we are watching
     */
    protected function getCollectionTypeHeadCommit()
    {
        return $this->commitManager->getHeadCommit($this->getCommitHeadIdent());
    }
}
