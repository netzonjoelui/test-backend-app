<?php
/**
 * This is just a simple test controller
 */

class TestController extends Controller
{
	public function testOutput($params)
	{
		if ('xml' == $params['output'])
			return $this->sendOutputXml(array("retval"=>1, "message"=>"You successfully called this functions"));
		else
			return $this->sendOutputRaw("You successfully called this functions");
	}
}
