<?php
/**
 * Interfaces RPC requests via query url variable function=functionName to functions in a class
 *
 * Use this class to expose methods of any class to a script called by url, usually an ajax client.
 * It is recommended that all exposed actions/methods return either void or true/false and simply
 * print the desired results directly from the class. However, RpcSvr::run will return whatever the
 * actual return value of the function is so implementations of this class can decide to return values
 * then print them from the calling script.
 *
 * Usage Example:
 *
 * Lets assume that the class ServerClassName as a function called 'addContact' that we would like to call
 * with params for adding a new contact. The function definition would be "new public function addContact($params)"
 * inslide the ServerClassName class. The class constuctor must take two params, CAnt and AntUser. A handle to the
 * account database can be obtained from the CAnt->dbh property.
 *
 * // We will add mock request params for testing purposes
 * $_REQUEST['function'] = "addContact"; // this is normally set through a URL query variable ?function=addContact
 * $_REQUEST['firstName'] = "test"; // this will be passed in the $params variable of the method called
 *
 * // In the called script, for instance, /testsrv.php, include the RpcSvr and ServerClassName libraries and then do...
 * $svr = new RpcSvr($ANT, $USER); // User is optional, but ant is required
 * $svr->setClass("ServerClassName");
 * $sve->run();
 *
 * // You can also refer to /tests/rpcsvr.php for a working example
 *
 * @category  ANT Library
 * @package   RpcSvr
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Expose public class methods to calling script
 */
class RpcSvr
{
	/**
     * The name of the class to initiailize
     *
     * @var string
	 */
	private $className;

	/**
     * Handle to the created class
     *
     * @var mixed
	 */
	private $svrCls = null;

	/**
     * Reference to CAnt class
     *
     * @var CAnt 
	 */
	private $ant = null;

	/**
     * Reference to current user object
     *
     * @var AntUser
	 */
	private $user = null;
    
    /**
     * Determines if the class is run by unit test
     *
     * @var Boolean
     */
    public $testMode = false;

	/**
	 * Class constructor
	 */
	function __construct($ant=null, $user=null)
	{
		if ($ant)
			$this->ant = $ant;

		if ($user)
			$this->user = $user;
	}

	/**
	 * Set the class to expose methods for
	 *
	 * @param string $clsname is the name of the class to load that will process server requests
	 */
	public function setClass($clsname)
	{
		$this->className = $clsname;
	}

	/**
	 * Execute methods in server class
	 *
	 * @return true on success, false on failure
	 */
	public function run()
	{
		global $_REQUEST;

		if (!$_REQUEST['function'])
			return false;

		// Create new instance of class if it does not exist
		if ($this->className && !$this->svrCls)
		{
			$clsname = $this->className;
			$this->svrCls = new $clsname($this->ant, $this->user);
            
            if(isset($this->svrCls->testMode))
                $this->svrCls->testMode = $this->testMode;
		}
        
		if (method_exists($this->svrCls, $_REQUEST['function']))
		{
			// forward request variables in as params
			$params = array(); 
			foreach ($_POST as $varname=>$varval)
			{
				if ($varname != 'function')
					$params[$varname] = $varval;
			}

			foreach ($_GET as $varname=>$varval)
			{
				if ($varname != 'function')
					$params[$varname] = $varval;
			}
            
            if($this->testMode)
            {
                foreach ($_REQUEST as $varname=>$varval)
                {
                    if ($varname != 'function')
                        $params[$varname] = $varval;
                }
            }
            
			// Manually set output if passed as a param
			if (isset($params['output']))
				$this->svrCls->output = $params['output'];

			// Call class method and pass request params
			return call_user_func(array($this->svrCls, $_REQUEST['function']), $params);
		}
		else
		{
			return false;
		}
	}
}
