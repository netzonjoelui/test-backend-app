<?php
/**
 * Test entity definition loader class that is responsible for creating and initializing exisiting definitions
 */
namespace NetricTest\Entity\DataMapper;

use \Netric\Entity\DataMapper;

class PgsqlTest extends DmTestsAbstract 
{
	/**
	 * Setup datamapper for the parent DataMapperTests class
	 *
	 * @return DataMapperInterface
	 */
	protected function getDataMapper()
	{
		return new DataMapper\Pgsql($this->account);
	}

	/**
	 * Test conversion from entity to escaped column values
	 */
	public function testGetColsVals()
	{
		$dm = $this->getDataMapper();

        // Create a few test groups
        $groupingsStat = $dm->getGroupings("customer", "status_id");
        $statGrp = $groupingsStat->create("Unit Test Status");
        $groupingsStat->add($statGrp);
        $dm->saveGroupings($groupingsStat);
        
        $groupingsGroups = $dm->getGroupings("customer", "groups");
        $groupsGrp = $groupingsGroups->create("Unit Test Group");
        $groupingsGroups->add($groupsGrp);
        $dm->saveGroupings($groupingsGroups);
        

		// Create an entity and initialize values
		$customer = $this->createCustomer();
		// fkey
		$customer->setValue("status_id", $statGrp->id, $statGrp->name);
		// fkey_multi - groups
		$customer->addMultiValue("groups", $groupsGrp->id, $groupsGrp->name);

		// Get access to private checkObjColumn with reflection object
		$refIm = new \ReflectionObject($dm);
		$getColsVals = $refIm->getMethod("getColsVals");
		$getColsVals->setAccessible(true);
		$data = $getColsVals->invoke($dm, $customer);

		// Test escaped data
		$this->assertEquals($data['name'], "'Entity_DataMapperTests'");
		$this->assertEquals($data['owner_id'], "'" . $this->user->getId() . "'");
		$this->assertEquals($data['groups'], "'[\"" . $groupsGrp->id. "\"]'");
		$this->assertEquals($data['groups_fval'], "'{\"" . $groupsGrp->id. "\":\"" . $groupsGrp->name. "\"}'");

		// TODO: Cleanup
		$groupingsStat->delete($statGrp->id);
        $dm->saveGroupings($groupingsStat);
        
        $groupingsGroups->delete($groupsGrp->id);
        $dm->saveGroupings($groupingsGroups);
		$dm->delete($customer, true);
	}
}
