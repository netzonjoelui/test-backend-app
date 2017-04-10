<?php
/**
 * This file must be placed in the head of any ANT document
 */

// Appended to make sure updates invalidate cached old versions
$ver = 36;

echo '<script language="javascript" type="text/javascript" src="/js/vendor/react/react-with-addons.min.js?v=' . $ver . '"></script>';

if (AntConfig::getInstance()->debug)
{
	echo '<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.js?v=' . $ver . '"></script>';
	echo '<script language="javascript" type="text/javascript" src="/js/netric.js?v=' . $ver . '"></script>';
	include("lib/js/includes.php");
} 
else 
{
	echo '<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.cmp.js?v=' . $ver . '"></script>';
	echo '<script language="javascript" type="text/javascript" src="/js/netric.js?v=' . $ver . '"></script>';
	echo '<script language="javascript" type="text/javascript" src="/lib/js/ant_full.cmp.js?v=' . $ver . '"></script>';
}


