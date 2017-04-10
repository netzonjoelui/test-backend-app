<?php
/**
 * Action to handle callpage requests
 *
 * @category    Ant
 * @package        WorkFlow_Action
 * @subpackage    callpage
 * @copyright    Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/WorkFlow/Action/Abstract.php");

/**
 * Class for callpage workflow actions
 */
class WorkFlow_Action_CallPage extends WorkFlow_Action_Abstract
{
    /**
     * Execute action
     *
     * This extends common object creation because it has additional functions/features
     * for creating callpage object types and launching workflows
     *
     * @param CAntObject $obj object that we are running this workflow action against
     * @param WorkFlow_Action $act current action object
     */
    public function execute($obj, $act)
    {
        // Load a webpage url here
        $ovals = $act->getObjectValues();
        $act->replaceMergeVars($ovals, $obj); // replace <%vars%> with values from object

        
        $search = array("(", ")", " ", "\"", "'");
        $replace = array("%28", "%29", "%20", "%22", "%27");

        $url = str_replace($search, $replace, $ovals["url"]);

		AntLog::getInstance()->info("WorkFlow_Action_CallPage: " . $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resultUrl = curl_exec($ch);
        curl_close($ch);
        
        // Used this to test that the workflow was executed.
        //$this->dbh->Query("insert into customers (name, website) values('Workflow CallPage', '" . $this->dbh->Escape($url) . "')");
        
        return $resultUrl;
    }
}
