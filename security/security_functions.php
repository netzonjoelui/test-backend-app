<?php
define('KEY', 'fdsagfdaahah354h6gf4s3h2fgs65h46');

/****************************************************************************************
*	Function: 	encrypt
*
*	Purpose:	Encrypt some text based on a global defined KEY
****************************************************************************************/
function encrypt($text)
{
	if(function_exists("mcrypt_encrypt"))
		return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, KEY, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	else
		return base64_encode($text);
}

/****************************************************************************************
*	Function: 	decrypt
*
*	Purpose:	Decrypt some text based on a global defined KEY
****************************************************************************************/
function decrypt($text)
{
	if(function_exists("mcrypt_decrypt"))
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, KEY, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	else
		return base64_decode($text);
}
?>
