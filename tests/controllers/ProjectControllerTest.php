<?php
require_once 'PHPUnit/Autoload.php';
// ANT Includes 
require_once(dirname(__FILE__).'/../../lib/AntConfig.php');
require_once(dirname(__FILE__).'/../../lib/CDatabase.awp');
require_once(dirname(__FILE__).'/../../lib/AntUser.php');
require_once(dirname(__FILE__).'/../../lib/Ant.php');
require_once(dirname(__FILE__).'/../../lib/Email.php');
require_once(dirname(__FILE__).'/../../lib/CAntObject.php');
require_once(dirname(__FILE__).'/../../lib/Controller.php');
require_once(dirname(__FILE__).'/../../controllers/ProjectController.php');
require_once(dirname(__FILE__).'/../../userfiles/file_functions.awp');
require_once(dirname(__FILE__).'/../../lib/CAntFs.awp');
require_once(dirname(__FILE__).'/../../project/project_functions.awp');


class ProjectControllerTest extends PHPUnit_Framework_TestCase
{
    var $dbh = null;
    var $user = null;
    var $ant = null;
    var $backend = null;

    function setUp() 
    {
        $this->ant = new Ant();
        $this->dbh = $this->ant->dbh;
        $this->user = $this->ant->getUser(USER_SYSTEM);
    }

    function tearDown() 
    {
    }

    /*function getTests()
    {        
        return array("testDeleteAttachment");        
    }    */

    /**
    * Test ANT Project - deleteAttachment($params)
    */
    function testDeleteAttachment()
    {
        // instantiate project controller
        $projectController = new ProjectController($this->ant, $this->user);
        
        // create attachment data        
    }
}
