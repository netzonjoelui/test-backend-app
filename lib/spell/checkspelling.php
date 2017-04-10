<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/AntConfig.php");
require_once("ant.php");
require_once("ant_user.php");
require_once("lib/Button.awp");

$dbh = $ANT->dbh;
$USERNAME = $USER->name;
$USERID =  $USER->id;
$ACCOUNT = $USER->accountId;

/*
Pungo Spell Copyright (c) 2003 Billy Cook, Barry Johnson

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

//if pspell doesn't exist, then include the pspell wrapper for aspell
if(!function_exists('pspell_suggest'))
{
	define('ASPELL_BIN','/usr/local/bin/aspell'); //set path to aspell if you need to and uncomment this line
	require_once ("pspell_comp.php");
}

//$pspell_link = pspell_new ("en", "", "", "", PSPELL_FAST|PSPELL_RUN_TOGETHER);

$pspell_config = pspell_config_create("en");
pspell_config_mode($pspell_config, PSPELL_FAST);
$pspell_link = pspell_new_config($pspell_config);

$mystr = stripslashes($_POST['spellstring']);
// can't have newlines or carriage returns in javascript string
if (strpos($mystr, "\r\n") !== false)
    $mystr = str_replace("\r\n", "_|_", $mystr);
else
    $mystr = str_replace("\n", "_|_", $mystr);

$mystr = trim($mystr);

// original that doesn't work with html
preg_match_all ( "/[[:alpha:]']+|<[^>]+>|&[^;\ ]+;/", $mystr, $alphas, PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER);

//print_r($alphas);

// this has to be done _after_ the matching.  it messes up the
// indexing otherwise.  I have not figured out exactly why this
// happens but I know this fixes it.
$mystr = str_replace("\"", "\\\"", $mystr);

$js .= 'var mispstr = "'.$mystr.'";'."\n";

$js .= 'var misps = Array(';
$curindex = 0;
for($i = 0; $i < sizeof($alphas[0]); $i++) 
{
	// if the word is an html tag or entity then skip it
	if (preg_match("/<[^>]+>|&[^;\ ]+;/", $alphas[0][$i][0]))  
		 continue; // skip this one
	
	// If word is in users dictionary then skip it
	if ($dbh->GetNumberRows($dbh->Query("select id from user_dictionary where user_id='$USERID' and word='".$dbh->Escape($alphas[0][$i][0])."';")))
		continue;
		
	if (!pspell_check ($pspell_link, $alphas[0][$i][0])) 
	{
		$js .= "new misp('" . str_replace("'", "\\'",$alphas[0][$i][0]) . "',". $alphas[0][$i][1] . "," . (strlen($alphas[0][$i][0]) + ($alphas[0][$i][1] - 1) ) . ",[";
		$suggestions = pspell_suggest ($pspell_link, $alphas[0][$i][0]);
		
		foreach ($suggestions as $suggestion)
		   $sugs[] = "'".str_replace("'", "\\'", $suggestion)."'"; 
		   
		if (sizeof($sugs)) 
			$js .= join(",", $sugs);
	   
	   unset($sugs);

	   $js .= "]),\n";
	   $sugs_found = 1;
	}
}
if ($sugs_found)
   $js = substr($js, 0, -2);
$js .= ");";

include('spellwin.php');
?>
