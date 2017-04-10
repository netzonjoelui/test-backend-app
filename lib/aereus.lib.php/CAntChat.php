<?php
/*======================================================================================
	
	Module:		CAntChat	

	Purpose:	Remote API for ANT Chat

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2008 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// Create a new instance of the chat interface - the last param is the queue id
				$chatter = new CAntChat("testserv.aereus.com", "1");

				// Check if there are agents monitoring the queue
				$num = $chatter->queueIsActive(1);

				// Get entry id for queue
				$eid = $chatter->getQueueEid($username, "Enter notes here");

				// Read the value of field 'testfield' in post 0
				$value = $feedReader->getPostVarValue('testfield', 0);

				// Find out of someone has opened the chat
				$ret = $chatter->checkQueueEidStatus($eid);
				// JS Code below
				if (ret != "-1")
				{
					document.location="http://testserv.aereus.com/chat/pop_chat_public.php?sid=" + ret + "&ttl=<?php print(base64_encode('Aereus Customer Service')); ?>";
				}
				else
				{
					setTimeout('checkStatus();', 3000);
				}

	Variables:	

======================================================================================*/

class CAntChat
{
	var $m_server;
	var $m_queue;

	function CAntChat($server, $queue)
	{
		$this->m_server = $server;
		$this->m_queue = $queue;
	}

	function getQueueEid($name, $notes)
	{
		$ret = 0;

		$url = "http://".$this->m_server . "/chat/wapi.php?function=queue_entry_create&qid=".$this->m_queue."&name=$name&notes=".rawurlencode($notes); 

		$dom = new DomDocument();

		$dom->load($url); 

		foreach ($dom->documentElement->childNodes as $response) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($response->nodeType == 1)
			{
				switch ($response->nodeName)
				{
				case 'retval':
					$ret = rawurldecode($response->textContent);
					break;
				}
			}
		} 

		return $ret;
	}

	function checkQueueEidStatus($eid)
	{
		$ret = array();

		$url = "http://".$this->m_server . "/chat/wapi.php?function=queue_entry_get_status&queue_eid=".$eid.""; 

		$dom = new DomDocument();

		$dom->load($url); 

		foreach ($dom->documentElement->childNodes as $response) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($response->nodeType == 1)
			{
				switch ($response->nodeName)
				{
				default:
					$ret[$response->nodeName] = rawurldecode($response->textContent);
					break;
				}
			}
		} 

		return $ret;
	}

	function queueIsActive()
	{
		$ret = false;

		$url = "http://".$this->m_server . "/chat/wapi.php?function=queue_is_active&qid=".$this->m_queue; 

		$dom = new DomDocument();

		$dom->load($url); 

		foreach ($dom->documentElement->childNodes as $response) 
		{
			//if node is an element (nodeType == 1) and the name is "item" loop further
			if ($response->nodeType == 1)
			{
				switch ($response->nodeName)
				{
				case 'retval':
					$ret = $response->textContent;
					break;
				}
			}
		} 

		if ($ret == "-1")
			return false;
		else
			return true;
	}
}
?>
