<?php
/**
 * This unit test specifically focusses on the adhock cube implementation.
 */
//require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../../../lib/Olap.php');

class Olap_Cube_AdhockTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $checkQuarter = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
        $this->checkQuarter = array("Q1", "Q2", "Q3", "Q4");
        
        $this->markTestSkipped('Depricated.');
    }
    
    function tearDown() 
    {
        //@unlink('/temp/test.log');    
    }
    
    /**
     * This function can be used to limit the unit test to a single function
     */
    /*function getTests()
    {        
        return array("testDimTypes");
    }*/
    
    /**
     * Test dynamically adding dimensions and measures
     *
     * This process is desinged to be very dynamic so that new cubes
     * can be created easily simply by inserting data for the first time
     */
    function testCreateCubeDimensionMeasures()
    {
        $olap = new Olap($this->dbh);

        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);

        // Now dynamically create dimension
        $dim = $cube->getDimension("time_entered");
        $this->assertTrue(sizeof($dim) > 0);
        $this->assertTrue($dim->id > 0);
        $this->assertEquals("time_entered", $dim->name);
        $this->assertEquals("timestamp", $dim->type);

        // Now dynamically create measure
        $meas = $cube->getMeasure("revision");        
        $this->assertTrue(sizeof($meas) > 0);
        $this->assertTrue($meas->id > 0);
        $this->assertEquals("revision", $meas->name);
    }

    /**
     * Test dynamically adding dimensions based on suffix
     */
    function testCreateCubeDimensionDynTypes()
    {
        $olap = new Olap($this->dbh);
        
        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);

        // Now dynamically create dimension called time which should default to type=time
        $dim = $cube->getDimension("time_entered");
        $this->assertTrue($dim->id > 0);
        $this->assertEquals("time_entered", $dim->name);
        $this->assertEquals("timestamp", $dim->type);

        // Now dynamically create measure with a *_ts suffix which should default to type=time
        $dim = $cube->getDimension("entered_ts");
        $this->assertTrue($dim->id > 0);
        $this->assertEquals("time", $dim->type);
    }

    /**
     * Test pulling data with time-series data
     */
    function testCreateCubeDimensionTimeSeries()
    {        
        $olap = new Olap($this->dbh);

        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);

        // Pull data for each quarter
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "sum");
        $query->addDimension("time_entered", "asc", "Q Y");
        $query->addDimension("name", "asc");
        $data = $cube->getData($query);
        //print_r($data); // sample output: Array ( [Q1 2012] => Array ( [revision] => 8 ) 
        $this->assertTrue(is_array($data));
        foreach($data as $quarter=>$revision)
        {
            // assert quarter
            $quarterParts = explode(" ", $quarter);
            $this->assertTrue(in_array($quarterParts[0], $this->checkQuarter));
            $this->assertTrue($quarterParts[1] > 0);
            
            // assert revision
            if(isset($revision['revision']))
            {
                $this->assertTrue(is_array($revision));
                $this->assertTrue($revision['revision'] >= 0);
            }
        }

        // Try with second dimension
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "sum");        
        $query->addDimension("owner_id");
        $query->addDimension("time_entered", "asc", "Q Y");        
        $data = $cube->getData($query);
        //print_r($data); // sample output: Array ( [-1] => Array ( [Q1 2012] => Array ( [count] => 3 [revision] => 2 ) ) 
        $this->assertTrue(is_array($data));
        foreach($data as $owner)
        {
            $this->assertTrue(is_array($owner));
            
            // loop thru owners data [Quarter=>Revision[]]
            foreach($owner as $quarter=>$revision)
            {
                // assert quarter
                $quarterParts = explode(" ", $quarter);
                $this->assertTrue(in_array($quarterParts[0], $this->checkQuarter));
                $this->assertTrue($quarterParts[1] > 0);
                
                // assert revision
                $this->assertTrue(is_array($revision));
                $this->assertTrue($revision['revision'] >= 0);
            }
        }
    }

    /**
     * Test pulling data with time-series data
     */
    function testCreateCubeDimensionTimeSeriesFilter()
    {
        $olap = new Olap($this->dbh);        

        // get adhock cube instance
        $cube = $olap->getAdhockCube("task", $this->user);
        
        // This vs last month
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "sum");
        $query->addDimension("ts_entered", "asc", "n, Y");        
        $query->addDimension("priority", "asc");
        $query->addFilter("and", "ts_entered", "last_x_months", 2);        
        $data = $cube->getData($query);
        
        //print_r($data); // sample output: Array ( [2, 2012] => Array ( [revision] => 8 ) ) 
        $this->assertTrue(is_array($data));
        foreach($data as $date=>$revision)
        {
            // assert quarter
            $dateParts = explode(" ", $date);            
            $this->assertTrue($dateParts[0] > 0);
            $this->assertTrue($dateParts[1] > 0);
            
            // assert revision
            $this->assertTrue(is_array($revision));
            $this->assertTrue($revision['revision'] >= 0);
        }

        // This vs last year
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "sum");
        $query->addDimension("ts_entered", "asc", "n, Y");
        $query->addDimension("name", "asc");
        $query->addFilter("and", "ts_entered", "last_x_years", 2);        
        $data = $cube->getData($query);        
        //print_r($data); // sample output: Array ( [2, 2012] => Array ( [revision] => 8 ) ) 
        $this->assertTrue(is_array($data));
        foreach($data as $date=>$revision)
        {
            // assert quarter
            $dateParts = explode(" ", $date);            
            $this->assertTrue($dateParts[0] > 0);
            $this->assertTrue($dateParts[1] > 0);
            
            // assert revision
            $this->assertTrue(is_array($revision));
            $this->assertTrue($revision['revision'] >= 0);
        }
        
    }
    
    /**
     * Test summing data
     */
    function testSum()
    {
    }

    /**
     * Test max aggregate
     */
    function testMax()
    {
    }

    /**
     * Test min aggregate
     */
    function testMin()
    {
    }

    /**
     * Test averaging data
     */
    function testAvg()
    {
        $olap = new Olap($this->dbh);
        
        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);
        
        // Test no dimension
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "avg");        
        $data = $cube->getData($query);
        //print_r($data); // sample data: Array ( [revision] => 82 ) 
        $this->assertTrue(is_array($data));
        $this->assertTrue($data['count'] >= 0);
        $this->assertTrue($data['revision'] >= 0);

        // Test one dimension
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "avg");
        $query->addDimension("time_entered", "asc", "Q Y");
        $query->addDimension("name", "asc");
        $data = $cube->getData($query);
        //print_r($data); // sample data: Array ( [Q1 2012] => Array ( [count] => 5 [revision] => 1.6 )
        $this->assertTrue(is_array($data));
        foreach($data as $quarter=>$revision)
        {
            // assert quarter
            $quarterParts = explode(" ", $quarter);
            $this->assertTrue(in_array($quarterParts[0], $this->checkQuarter));
            $this->assertTrue($quarterParts[1] > 0);
            
            // assert revision
            if(isset($revision['revision']))
            {
                $this->assertTrue(is_array($revision));
                $this->assertTrue($revision['count'] >= 0);
                $this->assertTrue($revision['revision'] >= 0);
            }
        }
        

        // Test two dimensions
        $query = new Olap_Cube_Query();
        $query->addMeasure("revision", "avg");        
        $query->addDimension("owner_id");
        $query->addDimension("time_entered", "asc", "Q Y");        
        $data = $cube->getData($query);
        //print_r($data); // sample output: Array ( [-1] => Array ( [Q1 2012] => Array ( [count] => 3 [revision] => 2 ) ) 
        $this->assertTrue(is_array($data));
        foreach($data as $owner)
        {
            $this->assertTrue(is_array($owner));
            
            // loop thru owners data [Quarter=>Revision[]]
            foreach($owner as $quarter=>$revision)
            {
                // assert quarter
                $quarterParts = explode(" ", $quarter);
                $this->assertTrue(in_array($quarterParts[0], $this->checkQuarter));
                $this->assertTrue($quarterParts[1] > 0);
                
                // assert revision
                $this->assertTrue(is_array($revision));
                $this->assertTrue($revision['count'] >= 0);
                $this->assertTrue($revision['revision'] >= 0);
            }
        }
    }

    /**
     * Test getting data in a tabular format (regular associative array)
     */
    function testTabular()
    {
        $olap = new Olap($this->dbh);
        
        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);
        
        // Extract the data in a single dimension
        $query = new Olap_Cube_Query();
        $query->addDimension("name");
        $query->addFilter("and", "owner_id", "is_equal", USER_SYSTEM);
        $query->addDimension("name", "asc");
        $data = $cube->getTabularData($query);        
        //print_r($data); // sample output: Array ( [0] => Array ( [name] => TestUnit CustomerName ) )
        /*$this->assertTrue(is_array($data));
        foreach($data as $customer)
            $this->assertTrue(strlen($customer['name']) > 0);*/
    }
    
    function testDimTypes()
    {
        // create customers
        //$objCust1 = new CAntObject($this->dbh, "customer", null, $this->user);
        $objCust1 = CAntObject::factory($this->dbh, "customer", null, $this->user);
        $objCust1->setValue("name", "Adhock Customer 1");
        $custId1 = $objCust1->save();
        
        //$objCust2 = new CAntObject($this->dbh, "customer", null, $this->user);
        $objCust2 = CAntObject::factory($this->dbh, "customer", null, $this->user);
        $objCust2->setValue("name", "Adhock Customer 2");
        $custId2 = $objCust2->save();
        
        $olap = new Olap($this->dbh);        

        // get adhock cube instance
        $cube = $olap->getAdhockCube("customer", $this->user);
        
        $query = new Olap_Cube_Query();
        $query->addMeasure("id", "sum");
        $query->addDimension("owner_id", "asc");
        $query->addDimension("name", "asc");
        $data = $cube->getData($query);
        
        $this->assertTrue($data['administrator']["Adhock Customer 1"]["id"] > 0);
        $this->assertTrue($data['administrator']["Adhock Customer 2"]["id"] > 0);
        
        $objCust1->removeHard();
        $objCust2->removeHard();
    }
}
