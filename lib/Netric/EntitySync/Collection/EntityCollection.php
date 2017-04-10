<?php
/**
 * Sync collection for entities
 *
 * @category  AntObjectSync
 * @package   Collection
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright Copyright (c) 2003-2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\EntitySync\Collection;

use Netric\EntityQuery\Index;
use Netric\EntitySync\DataMapperInterface;
use Netric\EntitySync\EntitySync;
use Netric\EntitySync\Commit;
use Netric\EntitySync\Commit\CommitManager;

/**
 * Class used to represent a sync partner or endpoint
 */
class EntityCollection extends AbstractCollection implements CollectionInterface
{
	/**
	 * Index for querying entities
	 *
	 * @var \Netric\EntityQuery\Index\IndexInterface
	 */
	private $index = null;

	/**
	 * Constructor
	 *
	 * @param \Netric\EntitySync\DataMapperInterface $dm The sync datamapper
     * @param CommitManager $commitManager A manager used to keep track of commits
	 * @param \Netric\EntityQuery\Index\IndexInterface $idx Index for querying entities
	 */
	public function __construct(
		DataMapperInterface $dm, 
		Commit\CommitManager $commitManager, 
		Index\IndexInterface $idx)
	{
		$this->index = $idx;

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
	 * @throws \Exception if the objType was not set
	 */
	public function getExportChanged($autoFastForward=true, \DateTime $limitUpdatesAfter = null)
	{
		if (!$this->getObjType())
		{
			throw new \Exception("Object type not set! Cannot export changes.");
		}

		// Set return array
		$retStats = array();

		// Get the current commit for this collection
		$lastCollectionCommit = $this->getLastCommitId();
        if ($lastCollectionCommit === null)
            $lastCollectionCommit = 0;

		if ($this->isBehindHead())
        {
			// Query local objects for commit_id with EntityList
			$query = new \Netric\EntityQuery($this->getObjType());
	        $query->orderBy('commit_id');
	        $query->setLimit(1000);

	        // Set base/common condition
	        $query->where('commit_id')->isGreaterThan($lastCollectionCommit);
	        $query->andWhere('commit_id')->doesNotEqual('');

            // Check to see if we should only pull updates after a specific date
            if ($limitUpdatesAfter)
            {
                $query->andWhere("ts_updated")->isGreaterOrEqualTo($limitUpdatesAfter->getTimestamp());
            }

	        // Add any collection conditions
	        $conditions = $this->getConditions();
	        foreach ($conditions as $cond)
	        {
	        	if ($cond['blogic'] == 'or')
	        	{
	        		$query->orWhere($cond['field'], $cond['operator'], $cond['condValue']);
	        	}
	        	else
	        	{
	        		$query->andWhere($cond['field'], $cond['operator'], $cond['condValue']);
	        	}
	        }

	        // Execute query and get num results
	        $res = $this->index->executeQuery($query);
	  		$num = $res->getNum();

            /*
             * Get previously imported so we do not try to export a recent import.
             * Only get list if there are entities to export to save time
             */
            if ($this->getId() && $num)
            {
                $imports = $this->dataMapper->getImported($this->getId());
            }
            else
            {
                $imports = array();
            }

	        // Loop through each change
	        for ($i = 0; $i < $num; $i++)
	        {
	        	$ent = $res->getEntity($i);

                // First make sure we didn't just import this
                $skipStat = false;
                foreach ($imports as $imported)
                {
                    if (
                        $imported['local_id'] == $ent->getId() &&
                        $imported['local_revision'] == $ent->getValue("commit_id")
                    )
                    {
                        // Skip over this export because we just imported it
                        $skipStat = true;
                        break;
                    }
                }

                if (!$skipStat)
                {
                    $retStats[] = array(
                        "id" => $ent->getId(),
                        "action" => (($ent->isDeleted()) ? 'delete' : 'change'),
						"commit_id" => $ent->getValue("commit_id")
                    );

					// Sanity check, make sure we do not return the last commit id for change again
					if ($ent->getValue("commit_id") == $lastCollectionCommit)
					{
						throw new \Exception(
							"ERROR: Trying to return the commit previously returned: " .
							$lastCollectionCommit
						);
					}
                }

	        	if (($autoFastForward || $skipStat) && $ent->getValue("commit_id"))
				{
					// Fast-forward $lastCommitId to last commit_id sent
					$this->setLastCommitId($ent->getValue("commit_id"));

					// Save to exported log
					$this->logExported(
						$ent->getId(), 
						$ent->getValue("commit_id")
					);
				}
	        }

	        /*
	         * If no new changes were found, then get previously exported
	         * objects that have been updated but apparently no longer meet
	         * the conditions of this collection.
	         * 
	         * Only do this if we have conditions that might have moved an entity
	         * outside of a subset of entities (query). If all entities are being 
	         * returned by this collection then every change will be replayed in order
	         * by the above query.
	         */
	        if (0 == count($retStats))
	        {
	        	$retStats = $this->getExportedStale();
                if ($autoFastForward)
                {
                    foreach ($retStats as $stale)
                    {
                        // Save to exported log with no commit deletes the export
                        $this->logExported($stale['id'], null);
                    }
                }
	        }

			/*
			 * If we found no changes since the last commit, then fast-forward
			 * to keep us at the head of the collection.
			 * This prevents a bug where $this->isBehindHead can report true
			 * suggesting there might be changes, but querying reveals none.
			 */
			if (0 === count($retStats))
			{
				$this->fastForwardToHead();
			}

	        // TODO: Save lastCommit if changed
	        if (count($retStats) && $autoFastForward && $this->getId())
	        {
	        	// saveCollection is currently private, research...
	        	// $this->dataMapper->saveCollection($this);
	        }
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
		return EntitySync::COLL_TYPE_ENTITY;
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
     * Get the head commit for a given collection type
     *
     * @return string The last commit id for the type of data we are watching
     */
    protected function getCollectionTypeHeadCommit()
    {
        return $this->commitManager->getHeadCommit("entities/" . $this->getObjType());
    }
}
