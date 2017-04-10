<?php
/*======================================================================================
	
	Module:		AnalogClient	

	Purpose:	Sending logs and profiles to Analog Server

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2012 Aereus Corporation. All rights reserved.

	Usage:		
                $analogClient = new AnalogClient('server_name', 'logging_location', 'analog_server');
                $analogClient->setAuth('app_id', 'api_key');
                
                // sending logs
                $analogClient->sendLog('log_id', 'details_in_array')
                
                // sending profile
                $analogClient->sendProfile('profile_id', 'details_in_array', 'file_path');

======================================================================================*/
class AnalogClient
{
    const SERVER_LOCAL = 'analog.aereuslocal.com';
    const SERVER_DEV = 'analog.aereusdev.com';
    const SERVER_PROD = 'analog.aereus.com';
    
    /**
     * Analog Server (local, dev, production)
     * @var string 
     */
    private $_analogServer;
    
    /**
     * Server name of the client
     * @var string 
     */
    private $_serverName;
    
    /**
     * Application ID
     * @var type 
     */
    private $_appId;
    
    /**
     * API KEY
     * @var string 
     */
    private $_apiKey;
    
    /**
     * Location of the analog client log file
     * @var type 
     */
    private $_logFile;
    
    /**
    * Log Path - This is the location where it saves the log file when sending logs or profiles
    * 
    * @param string $serverName
    * @param string $logPath
    * @param string $analogServer 
    */
    public function __construct($serverName, $logPath = null, $analogServer = self::SERVER_PROD)
    {
        $this->_serverName = $serverName;
        $this->_analogServer = $analogServer;
       
        if ($logPath)
            $this->_logFile = $logPath . '/analog.log';
    }
    
    /**
     *
     * @param type $appId Application Id
     * @param type $apiKey 
     */
    public function setAuth($appId, $apiKey)
    {
        $this->_appId = $appId;
        $this->_apiKey = $apiKey;
    }
    
    /**
     * Array must contain key's source, details, level, time (optional)
     * @param string $logId
     * @param array $data 
     * @return boolean 
     */
    public function sendLog($logId, $data)
    {
        $page = '/restapi/logs';
        $data['log_id'] = $logId;
        
        $responseData = $this->_send($page, $data);
        
        return (bool)$responseData->valid;
    }
    
    /**
     * $data expects array keys: 
     *  XHPROF - page, incl_wall, incl_cpu, incl_mem, incl_pmem, time (optional)
     *  PGSQL  - time (optional)
     * @param type $profileId
     * @param type $data
     * @param type $fileFullPath
     * @return type 
     */
    public function sendProfile($profileId, $data, $fileFullPath)
    {
        if (!file_exists($fileFullPath)) 
            throw new Exception('Analog: File not found! ' . $fileFullPath);
        
        $page = '/restapi/profiles';
        $data['profile_id'] = $profileId;
        $data['data'] = '@' . $fileFullPath;

        $responseData = $this->_send($page, $data);
        
        return (bool)$responseData->valid;
    }
    
    /**
     * 
     * @param type $page
     * @param type $data
     * @return type 
     */
    private function _send($page, $data)
    {

        $data['application_id'] = $this->_appId;
        $data['api_key'] = $this->_apiKey;
        $data['server'] = $this->_serverName;
        
        $url = $this->_analogServer . $page;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
        $responseData = curl_exec($ch);

        $decodedData = json_decode($responseData);
        // log reponse data message
        $this->_addLog($decodedData->message);
        
        return $decodedData;
    }
    
    /**
     * 
     * @param array $data
     * @return type 
     */
    private function _convertDataToPostfields(array $data)
    {
        $parsedData = '';
        // append some important data
        $data['application_id'] = $this->_appId;
        $data['api_key'] = $this->_apiKey;
        $data['server'] = $this->_serverName;
        
        // convert into string params
        foreach ($data as $key => $value) 
            $parsedData .= urlencode($key) . '=' . urlencode($value) . '&';
        
        // return with removd last character '&'
        return substr($parsedData, 0, -1);
    }
    
    /**
     * 
     * @param string $logItem 
     */
    private function _addLog($logItem)
    {
        // doesn't add log when logile is not set
        if (!$this->_logFile)
            return;
        
        if (!file_exists($this->_logFile))
            $file = fopen($this->_logFile, 'w');
        else
            $file = fopen($this->_logFile, 'a');
        
        $logItem .= PHP_EOL;
        
        fwrite($file, $logItem);
        fclose($file);
    }
}
?>
