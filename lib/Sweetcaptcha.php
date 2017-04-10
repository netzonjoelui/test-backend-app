<?php
/**
 * Sweetcaptcha code
 *
 * Example
 * <code>
 * <?php
		require_once '/lib/Sweetcaptcha.php';
		?>

		<head>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
		</head>

		<? if (empty($_POST)) {?>

			<div id="captcha">
			<form method="post" action="">
			<?= $sweetcaptcha->get_html()?>
			<input type="submit" value="submit"/>
			</form>
			</div>

		<? } else {

				$scValues = array('sckey' => $_POST['sckey'], 'scvalue' => $_POST['scvalue'], 'scvalue2' => $_POST['scvalue2']);
				if ($sweetcaptcha->check($scValues) == "true") {
					echo 'GOOD CAPTCHA';
				}
				else {
					echo 'BAD CAPTCHA';
				}
			
		} ?>
	</code>
 */

define('APP_ID', AntConfig::getInstance()->captcha['app_id']);
define('SWEETCAPTCHA_KEY', AntConfig::getInstance()->captcha['key']) ;
define('SWEETCAPTCHA_SECRET', AntConfig::getInstance()->captcha['secret']);
define('SWEETCAPTCHA_PUBLIC_URL', 'sweetcaptcha.php');

$sweetcaptcha = new Sweetcaptcha(
	APP_ID, 
	SWEETCAPTCHA_KEY, 
	SWEETCAPTCHA_SECRET, 
	SWEETCAPTCHA_PUBLIC_URL
);

/*
 * Do not change below here.
 */


/**
 * Handles remote negotiation with Sweetcaptcha.com.
 *
 * @version 1.0.8
 * @since December 14th, 2010
 * 
 */

if (isset($_POST['ajax']) and $method = $_POST['ajax']) {
	echo $sweetcaptcha->$method(isset($_POST['params']) ? $_POST['params'] : array());
}

class Sweetcaptcha {
	
	private $appid;
	private $key;
	private $secret;
	private $path;
	
	const API_URL = 'www.sweetcaptcha.com';
	
	function __construct($appid, $key, $secret, $path) {
		$this->appid = $appid;
		$this->key = $key;
		$this->secret = $secret;
		$this->path = $path;
	}
	
	private function api($method, $params) {
		
		$basic = array(
			'method' => $method,
			'appid' => $this->appid,
			'key' => $this->key,
			'secret' => $this->secret,
			'path' => $this->path,
			'is_mobile' => preg_match('/mobile/i', $_SERVER['HTTP_USER_AGENT']) ? 'true' : 'false',
			'user_ip' => $_SERVER['REMOTE_ADDR'],
		);
		
		return $this->call(array_merge(isset($params[0]) ? $params[0] : $params, $basic));
	}
	
	private function call($params) {
		$param_data = "";		
		foreach ($params as $param_name => $param_value) {
			$param_data .= urlencode($param_name) .'='. urlencode($param_value) .'&'; 
		}
		
		if (  !($fs = fsockopen(self::API_URL, 80, $errno, $errstr, 10) ) ) {
			die ("Couldn't connect to server");
        }
		
		$req = "POST /api.php HTTP/1.0\r\n";
		$req .= "Host: www.sweetcaptcha.com\r\n";
		$req .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$req .= "Referer: " . $_SERVER['HTTP_HOST']. "\r\n";
		$req .= "Content-Length: " . strlen($param_data) . "\r\n\r\n";
		$req .= $param_data;		
	
		$response = '';
		fwrite($fs, $req);
		
		while ( !feof($fs) ) {
			$response .= fgets($fs, 1160);
		}
		
		fclose($fs);
		
		$response = explode("\r\n\r\n", $response, 2);
		
		return $response[1];	
	}
	
	public function __call($method, $params) {
		return $this->api($method, $params);
	}
}
?>
