<?php
/*======================================================================================
	
	Functions:	webforms	

	Purpose:	Standard functions used for form validataion on websites

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		// CAPTCHA
				// --------------------------------------------------------------------
				// 1. Create a file on the root called captchaimg.php with the follwing
				<?php
					include("lib/Aereus/webforms.php");
					formCaptchaCreateImage();
				?>
				
				// 2. Add to your form
					<img src='/captchaimg.php'><br />
					<input type='text' name='captcha_verify'> Enter Value of image above

				// 3. Include in the validation of your form on submit
				if (formCaptchaVerify()==false)
					// send error

======================================================================================*/
@session_start();

function formCaptchaPrint()
{
}

function formCaptchaCreateImage()
{
	global $_SESSION;

	// make a string with all the characters that we  
	// want to use as the verification code 
	$alphanum  = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789"; 

	// generate the verication code  
	$rand = substr(str_shuffle($alphanum), 0, 5); 

	// choose one of four background images 
	$bgNum = rand(1, 4); 
	//$bgNum = 2;

	// create an image object using the chosen background 
	$image = imagecreatefromjpeg("lib/Aereus/images/forms/background$bgNum.jpg"); 

	$textColor = imagecolorallocate ($image, 0, 0, 0);  

	// write the code on the background image 
	imagestring($image, 9, 5, 8,  $rand, $textColor);  
		 

	// create the hash for the verification code 
	// and put it in the session 
	//$_SESSION['image_random_value'] = md5($rand); 
	$_SESSION['captcha_curvalue'] = strtolower($rand); 
		 
	// send several headers to make sure the image is not cached     
	// taken directly from the PHP Manual 
		 
	// Date in the past  
	header("Expires: Mon, 26 Jul 2003 05:00:00 GMT");  

	// always modified  
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  

	// HTTP/1.1  
	header("Cache-Control: no-store, no-cache, must-revalidate");  
	header("Cache-Control: post-check=0, pre-check=0", false);  

	// HTTP/1.0  
	header("Pragma: no-cache");      

	// send the content type header so the image is displayed properly 
	header('Content-type: image/jpeg'); 

	// send the image to the browser 
	imagejpeg($image); 

	// destroy the image to free up the memory 
	imagedestroy($image); 
}

function formCaptchaVerify()
{
	global $_REQUEST, $_SESSION;

	if (strtolower($_REQUEST['captcha_verify']) == strtolower($_SESSION['captcha_curvalue']))
		return true;
	else
		return false;
}
?>
