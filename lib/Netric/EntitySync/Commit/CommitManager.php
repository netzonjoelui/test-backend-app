<?php
/*
 * Commit manager handles creating commit records enabling incremental diffs
 * 
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\EntitySync\Commit;

/**
 * Manage handles creating, getting, and working with commits
 *
 * @author joe <sky.stebnicki@aereus.com>
 */
class CommitManager
{
    /**
     * DataMapper
     *
     * @var DataMapper\DataMapperInterface
     */
    private $dm = null;

    /**
     * Class constructor
     *
     * @param DataMapper\DataMapperInterface $dm
     */
    public function __construct(DataMapper\DataMapperInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Generate a new commit id for a collection
     *
     * @param string $key A unique key representing the collection
     * @return bigint A unique and incremental commit id
     */
    public function createCommit($key)
    {
        $cid = $this->dm->getNextCommitId($key);
        $this->dm->saveHead($key, $cid);
        return $cid;
    }

    /**
     * Get the last commit id for a collection name
     *
     * @param string The name of the collection to get the head commit for
     * @return bigint The last commit id for an object type
     */
    public function getHeadCommit($key)
    {
        return $this->dm->getHead($key);
    }
}
