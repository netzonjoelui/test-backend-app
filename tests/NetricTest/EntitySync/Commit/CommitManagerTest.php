<?php
/**
 * Test to make sure the commit manager is saving snapshots as expected
 */
namespace NetricTest\EntitySync\Commit;

use Netric;
use PHPUnit_Framework_TestCase;

class CommitManagerTest extends PHPUnit_Framework_TestCase 
{
	/**
     * Tennant account
     * 
     * @var \Netric\Account\Account
     */
    private $account = null;

	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        $this->account = \NetricTest\Bootstrap::getAccount();
	}

	public function testCreateCommit()
	{
		$oCommitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");

		$lastCommit = $oCommitManager->getHeadCommit("test");

		// Create and return a new commit
		$nextCommit = $oCommitManager->createCommit("test");

		$this->assertTrue($nextCommit > 0);
		$this->assertNotEquals($lastCommit, $nextCommit);
	}

	public function testGetHeadCommit()
	{
		$oCommitManager = $this->account->getServiceManager()->get("EntitySyncCommitManager");

		// Create and return a new commit
		$nextCommit = $oCommitManager->createCommit("test");
		$currentHead = $oCommitManager->getHeadCommit("test");

		$this->assertEquals($nextCommit, $currentHead);
	}
}