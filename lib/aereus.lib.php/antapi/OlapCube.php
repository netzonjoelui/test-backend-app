<?php
/**
 * Aereus API Library
 *
 * The OlapCube communicates with the ANT OLap* backend.
 *
 * @category  AntApi
 * @package   OlapCube
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class for interacting with ANT Olap
 */
class AntApi_OlapCube
{
	/**
     * ANT server
     *
     * @var string
     */
	private $server;

	/**
     * Valid ANT user name
     *
     * @var string
     */
	private $username;

	/**
     * ANT user password
     *
     * @var string
     */
	private $password;
    
    /**
     * ANT controller api url
     *
     * @var string
     */
    private $apiControllerURL;
    
    private $cubeName;
    
	/**
     * Class constructor
	 *
	 * @param string $server	ANT server name
	 * @param string $username	A Valid ANT user name with appropriate permissions
	 * @param string $password	ANT user password
     */
	function __construct($server, $username, $password) 
	{
		$this->username = $username;
		$this->password = $password;
		$this->server = $server;
        
        $this->apiControllerURL = "http://".$server."/api/php/";
	}

	/**
	 * Get an instance of a data-warehouse cube by name
	 *
	 * @param string $name The unique name of the cube to retrieve
	 * @return Olap_Cube_Dataware
	 */
	public function getCube($name)
	{
        $this->cubeName = $name;
        $url = $this->apiControllerURL . "Olap/getCube";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
        $url .= "&api=1";
        
        $fields = "datawareCube=" . urlencode($this->cubeName);
        
		$ch = curl_init($url); // URL of gateway for cURL to post to
        curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
        ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
        $resp = curl_exec($ch); //execute post and get results
        curl_close ($ch);

        $ret = json_decode($resp);
        return $ret;
	}

	/**
	 * Get an instance of a adhock query cube
	 *
	 * Adhock cubes query data from objects in real time
	 *
     * @param string $obj_type The unique name of the object we will be querying
	 * @param AntUser $user The user object
	 * @return Olap_Cube_Object
	 */
	public function getAdhockCube($obj_type, $user)
	{
		return false;
	}

	/**
	 * Get an instance of a custom report cube
	 *
	 * Custom report cubes are created manually in /reports/cubes
	 *
     * @param string $name The unique name of the custom cube
	 * @param AntUser $user The user object
	 * @return Olap_Cube_Object
	 */
	public function getCustomCube($name, $user)
	{
		return false;
	}

	/**
	 * If implemented this supports writeback then write data to the cube.
	 *
	 * If the cube does not support writeback, then this function MUST be implemented and return false
	 *
	 * Example code:
	 * <code>
	 * 	$measures = array("visits"=>100);
	 * 	$dimensions = array("page"=>"/index.php", "country"=>array(1=>"us")); // 1 = "us"
	 * 	$cube->wireData($measures, $dimensions);
	 * </code>
	 * 
	 * @param array $measures Associative array of measures 'name'=>value
	 * @param array $data Associateive array of dimension data. ('dimensionanme'=>'value'). Value may be key/value array like 'id'=>'label'
	 * @param bool $increment If set to true then do not overwrite matching record, but increment it. Default = false.
	 */
	public function writeData($measures, $data, $increment=false)
	{
        $url = $this->apiControllerURL . "Olap/writeData";
        $url .= "?auth=".base64_encode($this->username).":".md5($this->password);
        
        $fields = "datawareCube=" . urlencode($this->cubeName);
        $fields .= "&measures=" . http_build_query($measures);
        $fields .= "&data=" . http_build_query($data, "", "+");
        $fields .= "&increment=" . urlencode($increment);
        
        $ch = curl_init($url); // URL of gateway for cURL to post to
        curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
        ### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
        $resp = curl_exec($ch); //execute post and get results
        curl_close ($ch);

        $ret = json_decode($resp);        
        return $ret;		
	}

	/**
	 * If implementaion supports writeback then increment data in this cube with matching data
	 *
	 * Example code:
	 * <code>
	 * 	$measures = array("visits"=>100);
	 * 	$dimensions = array("page"=>"/index.php", "country"=>array(1=>"us")); // 1 = "us"
	 * 	$cube->incrementData($measures, $dimensions);
	 * </code>
	 * 
	 * @param array $measures Associative array of measures 'name'=>value
	 * @param array $data Associateive array of dimension data. ('dimensionanme'=>'value'). Value may be key/value array like 'id'=>'label'
	 */
	public function incrementData($measures, $data)
	{
		$ret = $this->writeData($measures, $data, true);
        return $ret;
	}
}
