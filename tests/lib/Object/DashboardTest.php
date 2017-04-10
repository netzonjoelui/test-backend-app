<?php
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../lib/Object/Dashboard.php');

class CAntObject_DashboardTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;

	/**
	 * Setup each unit test
	 */
    public function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }

	/**
	 * Test saving and loading widgets
	 */
	public function testSave()
	{
		$dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
		$dash->setValue("name", "UT_testSave");
		$dash->addWidget("CWidActivity", 0);
		$dash->addWidget("CWidTasks", 1);
		$id = $dash->save();
		unset($dash);

		// Reopen and verify widgets are loaded
		$dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
		$widgets = $dash->getWidgets();
		$this->assertEquals($dash->getValue("num_columns"), 2);
		$this->assertEquals($widgets[0][0]['widget'], "CWidActivity");
		$this->assertEquals($widgets[1][0]['widget'], "CWidTasks");
        
		// Cleanup
		$dash->removeHard();
	}

	/**
	 * Test reorganizing widgets
	 */
	public function testUpdateLayout()
	{
		$dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
		$dash->setValue("name", "UT_testUpdateLayout");
		$dash->addWidget("CWidActivity", 0); // pos 0
		$dash->addWidget("CWidTasks", 0); // pos 1
		$id = $dash->save();
		unset($dash);

		// Reopen and resport widgets
		$dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
		$widgets = $dash->getWidgets();
		// Reverse order
		$newlayout = array(
			array(
				$widgets[0][1]['id'],
				$widgets[0][0]['id'],
			),
		);
		$widgets = $dash->updateLayout($newlayout);
		$this->assertEquals($widgets[0][0]['widget'], "CWidTasks");
		$this->assertEquals($widgets[0][1]['widget'], "CWidActivity");

		// Cleanup
		$dash->removeHard();
	}

	/**
	 * Test get layout
	 */
	public function testGetLayout()
	{
		$dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
		$dash->setValue("name", "UT_testGetLayout");
		$dash->addWidget("CWidActivity", 0);
		$dash->addWidget("CWidTasks", 1);
		$id = $dash->save();
		unset($dash);

		// Reopen and get the layout
		$dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
		$layout = $dash->getLayout();
		$this->assertEquals(count($layout), 2);
		$this->assertEquals($layout[0]['widgets'][0]['widget'], "CWidActivity");
		$this->assertEquals($layout[1]['widgets'][0]['widget'], "CWidTasks");

		// Cleanup
		$dash->removeHard();
	}
    
	/**
	 * Test importing dashboard from an application dashboard layout
	 */
	public function testImportAppDashLayout()
	{
		$dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
		$dash->setValue("name", "UT_testImportAppDashLayout");
		$dash->importAppDashLayout("unit.test");
		$id = $dash->save();

		$this->assertTrue($dash->getValue("num_columns") > 0);
		$this->assertTrue(count($dash->widgets) >= 1);

		$dash->removeHard();
	}

	/**
	 * Test setting dashboard column param
	 *
	 * @group testSetColumnParam
	 */
	public function testSetColumnParam()
	{
		$dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
		$dash->setValue("name", "UT_testSetColumnParam");
		$dash->addWidget("CWidActivity", 0);
		$dash->addWidget("CWidTasks", 1);
		$dash->setColumnParam(0, "width", "200px");
		$id = $dash->save();
		unset($dash);

		// Reopen and check the layout for the width
		$dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
		$layout = $dash->getLayout();
		$this->assertEquals($layout[0]['width'], "200px");

		// Cleanup
		$dash->removeHard();
	}
    
    /**
     * Test removing the widgets
     *
     * @group testSetColumnParam
     */
    public function testRemoveWidgets()
    {
        $dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
        $dash->setValue("name", "UT_testSetColumnParam");
        $dash->addWidget("CWidActivity", 0);
        $id = $dash->save();
        unset($dash);

        // Get the widgets
        $dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
        $widgets = $dash->getWidgets();
        
        // Test Removing the Dashboard Widget
        $result = $dash->removeWidget($widgets[0][0]['id']);
        $this->assertEquals($widgets[0][0]['id'], $result);

        // Cleanup
        $dash->removeHard();
    }
    
    /**
     * Test Saving data for widgets
     *
     * @group testSetColumnParam
     */
    public function testSaveData()
    {
        $dash = new CAntObject_Dashboard($this->dbh, null, $this->user);
        $dash->setValue("name", "UT_testSetColumnParam");
        $dash->addWidget("CWidWebpage", 0);
        $id = $dash->save();
        unset($dash);

        // Get the widgets
        $dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
        $widgets = $dash->getWidgets();
        
        // Test Removing the Dashboard Widget
        $dwid = $widgets[0][0]['id'];
        $result = $dash->saveData($dwid, "testdata");
        $this->assertEquals($result, $dwid);
        unset($dash);
        
        // Get the widgets and verify if the data was saved
        $dash = new CAntObject_Dashboard($this->dbh, $id, $this->user);
        $widgets = $dash->getWidgets();
        $this->assertEquals($widgets[0][0]["data"], "testdata");
        

        // Cleanup
        $dash->removeHard();
    }
}
