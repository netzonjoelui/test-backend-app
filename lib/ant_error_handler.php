<?php
/*======================================================================
 * Add custom error handling to ANT
 ======================================================================*/
$g_ErrorHandlerCalled = 0;
define("E_ANT_ERROR", 100000);

function ant_error_handler($errno, $errstr, $errfile, $errline, $errcontext) 
{
	global $ANT, $g_ErrorHandlerCalled;

	// protect against endless loops with CDatabase and CEmail classes
	// This should not happen, but just in case exit out 1000 loops
	$g_ErrorHandlerCalled++;
	if ($g_ErrorHandlerCalled > 1000)
		return;

	// if error has been supressed with an @
	if (error_reporting() == 0) {
		return;
	}

	// check if function has been called by an exception
	if(func_num_args() == 5) 
	{
		// called by trigger_error()
		$exception = null;
		list($errno, $errstr, $errfile, $errline) = func_get_args();

		$backtrace = array_reverse(debug_backtrace());
	}
	else 
	{
		// caught exception
		$exc = func_get_arg(0);
		$errno = $exc->getCode();
		$errstr = $exc->getMessage();
		$errfile = $exc->getFile();
		$errline = $exc->getLine();

		$backtrace = $exc->getTrace();
	}

	$errorType = array (
			   E_ERROR            => 'ERROR',
			   E_WARNING        => 'WARNING',
			   E_PARSE          => 'PARSING ERROR',
			   E_NOTICE         => 'NOTICE',
			   E_CORE_ERROR     => 'CORE ERROR',
			   E_CORE_WARNING   => 'CORE WARNING',
			   E_COMPILE_ERROR  => 'COMPILE ERROR',
			   E_COMPILE_WARNING => 'COMPILE WARNING',
			   E_USER_ERROR     => 'USER ERROR',
			   E_USER_WARNING   => 'USER WARNING',
			   E_USER_NOTICE    => 'USER NOTICE',
			   E_STRICT         => 'STRICT NOTICE',
			   E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR',
			   E_ANT_ERROR  => 'ANT APPLICATION ERROR'
			   );

	// create error message
	if (array_key_exists($errno, $errorType)) 
	{
		$err = $errorType[$errno];
	} 
	else 
	{
		$err = 'CAUGHT EXCEPTION';
	}

	$errMsg = "$err: $errstr in $errfile on line $errline";

	// start backtrace
	foreach ($backtrace as $v) 
	{
		if (isset($v['class'])) 
		{
			$trace = 'in class '.$v['class'].'::'.$v['function'].'(';

			if (isset($v['args'])) 
			{
				$separator = '';

				foreach($v['args'] as $arg ) 
				{
					$trace .= "$separator".getErrorArgument($arg);
					$separator = ', ';
				}
			}
			$trace .= ')';
		}
		elseif (isset($v['function']) && empty($trace)) 
		{
			$trace = 'in function '.$v['function'].'(';
			if (!empty($v['args'])) 
			{
				$separator = '';

				foreach($v['args'] as $arg ) 
				{
					$trace .= "$separator".getErrorArgument($arg);
					$separator = ', ';
				}
			}
			$trace .= ')';
		}
	}

	// what to do
	switch ($errno) 
	{
	case E_NOTICE:
	case E_USER_NOTICE:
	case E_STRICT:
	case E_DEPRECATED:
		return;
		break;

	default:

		$body = "";
		if (isset($_COOKIE['uname']))
			$body .= "USER_NAME: ".$_COOKIE['uname']."\r\n";
		$body .= "Type: System\r\n";
		if (isset($_COOKIE['db']))
			$body .= "DATABASE: ".$_COOKIE['db']."\r\n";
		if (isset($_COOKIE['dbs']))
			$body .= "DATABASE_SERVER: ".$_COOKIE['dbs']."\r\n";
		if (isset($_COOKIE['aname']))
			$body .= "ACCOUNT_NAME: ".$_COOKIE['aname']."\r\n";

		$body .= "When: ".date('Y-m-d H:i:s')."\r\n";
		$body .= "URL: ".$_SERVER['REQUEST_URI']."\r\n";
		$body .= "PAGE: ".$_SERVER['PHP_SELF']."\r\n";
		$body .= "----------------------------------------------\r\n".nl2br($errMsg)."\nTrace: ".nl2br($trace);
		$body .= "\r\n----------------------------------------------\r\n";

		// Try logging with the ANT logger
		if (class_exists("AntLog"))
		{
			try
			{
				AntLog::getInstance()->error($body);
			}
			catch (Exception $e)
			{
				die("Unable to write to log file " . $e->getMessage());
			}
		}
		else
		{
			// Before the logging class was included
			// Manually log to the error file
			file_put_contents(AntConfig::getInstance()->data_path."/error.log", $body, FILE_APPEND);
			chmod(AntConfig::getInstance()->data_path."/error.log", 0777);
		}

		break;
	}
}

function getErrorArgument($arg)
{
	switch (strtolower(gettype($arg))) 
	{
	case 'string':
		return( '"'.str_replace( array("\n"), array(''), $arg ).'"' );

	case 'boolean':
		return (bool)$arg;

	case 'object':
		return 'object('.get_class($arg).')';

	case 'array':
		$ret = 'array(';
		$separtor = '';

		foreach ($arg as $k => $v) 
		{
			//$ret .= $separtor.getErrorArgument($k).' => '.getErrorArgument($v);
			$separtor = ', ';
		}
		$ret .= ')';

		return $ret;

	case 'resource':
		return 'resource('.get_resource_type($arg).')';

	default:
		return var_export($arg, true);
	}
}

// Set error handler - this must be called first
// only hande if not in unit test
/// and if netric application is not handling the error
if (!class_exists('\PHPUnit_Framework_TestCase') && !class_exists('Netric\Application', false)) 
	set_error_handler("ant_error_handler");
