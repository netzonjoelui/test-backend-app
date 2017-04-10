<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\Error\Error;
use Netric\WorkFlow\WorkFlowInstance;
use Zend\Http\Client;

/**
 * Action to call an external page - very useful for API integration
 *
 * Params in the 'data' field:
 *
 *  url string REQUIRED the URL to call when the action is executed
 */
class WebhookAction extends AbstractAction implements ActionInterface
{
    /**
     * Alternate adaptor
     *
     * @var Client\Adapter\AdapterInterface
     */
    private $adapeter = null;

    /**
     * Response from the server
     *
     * @var string
     */
    private $response = null;

    /**
     * Set an alternate adapter to use with the client
     *
     * @param Client\Adapter\AdapterInterface $adapter
     */
    public function setClientAdapter($adapter)
    {
        $this->adapeter = $adapter;
    }

    /**
     * Get the response received from the last call
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        // Get the entity being acted on
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        $search = array("(", ")", " ", "\"", "'");
        $replace = array("%28", "%29", "%20", "%22", "%27");

        $url = str_replace($search, $replace, $params["url"]);

        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resultUrl = curl_exec($ch);
        $ret = (curl_errno($ch)) ? false : true;
        curl_close($ch);
        */

        $client = new Client($url, array(
            'maxredirects' => 10,
            'timeout'      => 30,
        ));
        if ($this->adapeter)
            $client->setAdapter($this->adapeter);

        try {
            $this->response = $client->send();
            return ($client->getResponse()->getStatusCode() === 200) ? true : false;
        } catch (Client\Adapter\Exception\RuntimeException $e) {
            $this->errors[] = new Error($e->getMessage());
            return false;
        }
    }
}