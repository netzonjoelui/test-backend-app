<?php
/**
 * Class used to provide help services
 */
class Help
{
	/**
	 * User object
	 *
	 * @var AntUser
	 */
	public $user = null;

	/**
	 * Get a tour item html from the file
	 *
	 * @param string $tourId The path and name of the file
	 * @return string The html of the tour item
	 */
	public function getTourItem($tourId, $user=null)
	{
		if ($user)
			$this->user = $user;

		$root = dirname(__FILE__)."/../views/help/tours/";

		if (file_exists($root . $tourId . ".php"))
		{
			ob_start();
			include $root . $tourId . ".php";
			$html = ob_get_clean();
			//$html = file_get_contents($root . $tourId . ".php");
		}
		else
		{
			$html = "";
		}

		return $html;
	}

	/**
	 * Check if a tour item exists
	 *
	 * @param string $tourId The path and name of the file
	 * @return bool true if there is a tour for this item
	 */
	public function tourItemExists($tourId)
	{
		$root = dirname(__FILE__)."/../views/help/tours/";

		return file_exists($root . $tourId . ".php");
	}
}
